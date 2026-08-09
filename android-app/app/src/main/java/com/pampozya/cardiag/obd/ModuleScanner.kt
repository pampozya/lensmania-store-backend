package com.pampozya.cardiag.obd

/**
 * A candidate ECU on the vehicle CAN bus, addressed by its 11-bit diagnostic
 * request ID. The response ID is normally request+8 for the standard
 * ISO 15765-4 diagnostic range (7E0..7E7); for other addresses we derive a
 * best-effort response ID the same way and also let the adapter listen openly.
 */
data class EcuCandidate(
    val name: String,
    val requestId: Int
)

/**
 * Result of probing a single ECU address.
 *
 * [responded] is true when the module (or the gateway on its behalf) returned
 * anything that proves the address is live — a UDS positive response (0x50/0x7E)
 * or even a negative response (0x7F), which still confirms an ECU is there.
 */
data class ModuleProbe(
    val candidate: EcuCandidate,
    val responded: Boolean,
    val raw: String,
    val gatewayBlocked: Boolean
)

/**
 * Full DTC read for one discovered module.
 */
data class ModuleDtcResult(
    val candidate: EcuCandidate,
    val codes: List<String>,
    val raw: String
)

/**
 * Sends UDS (ISO 14229) diagnostic requests to individual ECU CAN addresses,
 * ELM327-style: set the transmit header with ATSH, set the receive filter with
 * ATCRA, then send the service request and read the response. This is direct
 * module addressing (as used by FORScan and similar), not a gateway bypass —
 * each request is well-formed and the gateway decides whether to forward it.
 * The scan simply reports what actually answered.
 */
class ModuleScanner(private val connection: ObdConnection) {

    companion object {
        /**
         * Curated candidate list. The 7E0..7E7 block is the ISO 15765-4
         * standardized diagnostic range (powertrain). The 7xx entries are
         * common 11-bit body/chassis addresses used by many OEMs including
         * SAIC/MG platforms; not all will exist on every car — the scan
         * discovers which ones actually answer.
         */
        val DEFAULT_CANDIDATES: List<EcuCandidate> = listOf(
            EcuCandidate("Engine / ECM", 0x7E0),
            EcuCandidate("Transmission / TCM", 0x7E1),
            EcuCandidate("ECU #3 (7E2)", 0x7E2),
            EcuCandidate("ECU #4 (7E3)", 0x7E3),
            EcuCandidate("ECU #5 (7E4)", 0x7E4),
            EcuCandidate("ECU #6 (7E5)", 0x7E5),
            EcuCandidate("ECU #7 (7E6)", 0x7E6),
            EcuCandidate("ECU #8 (7E7)", 0x7E7),
            EcuCandidate("ABS / ESP", 0x760),
            EcuCandidate("SRS / Airbag", 0x780),
            EcuCandidate("Body Control (BCM)", 0x740),
            EcuCandidate("Instrument Cluster (IPC)", 0x720),
            EcuCandidate("Gateway", 0x710),
            EcuCandidate("Power Steering (EPS)", 0x730),
            EcuCandidate("HVAC / Climate", 0x7A0),
            EcuCandidate("Infotainment / Head Unit", 0x7B0),
            EcuCandidate("TPMS", 0x750),
            EcuCandidate("Parking Aid / PDC", 0x770)
        )

        private const val UDS_EXTENDED_SESSION = "1003"
        private const val UDS_TESTER_PRESENT = "3E00"
        private const val UDS_READ_DTC = "1902FF"
    }

    /**
     * Probe every candidate. Assumes an 11-bit 500 kbaud CAN protocol is
     * already selected (ATSP6). Restores the broadcast header afterwards.
     */
    suspend fun scanAll(
        candidates: List<EcuCandidate> = DEFAULT_CANDIDATES,
        onProgress: (ModuleProbe) -> Unit = {}
    ): List<ModuleProbe> {
        val results = mutableListOf<ModuleProbe>()
        try {
            connection.sendCommand("ATCAF1")
        } catch (_: Exception) {
        }

        for (candidate in candidates) {
            val probe = probe(candidate)
            results.add(probe)
            onProgress(probe)
        }

        restoreDefaults()
        return results
    }

    private suspend fun probe(candidate: EcuCandidate): ModuleProbe {
        setHeaders(candidate.requestId)

        val transcript = StringBuilder()
        var responded = false
        var gatewayBlocked = false

        for (request in listOf(UDS_EXTENDED_SESSION, UDS_TESTER_PRESENT)) {
            val resp = try {
                connection.sendCommand(request, timeoutMs = 3000)
            } catch (e: Exception) {
                "ERROR: ${e.message}"
            }
            transcript.append("> ").append(request).append('\n').append(resp).append('\n')

            when {
                isPositiveOrNegativeUds(resp) -> {
                    responded = true
                }
                isGatewayRefusal(resp) -> {
                    gatewayBlocked = true
                }
            }
            if (responded) break
        }

        return ModuleProbe(
            candidate = candidate,
            responded = responded,
            raw = transcript.toString().trim(),
            gatewayBlocked = gatewayBlocked && !responded
        )
    }

    /**
     * Read stored DTCs from an already-discovered module via UDS service 0x19
     * (ReadDTCInformation, sub-function 0x02). Falls back to raw display when
     * the response can't be decoded.
     */
    suspend fun readModuleDtcs(candidate: EcuCandidate): ModuleDtcResult {
        setHeaders(candidate.requestId)
        val resp = try {
            connection.sendCommand(UDS_READ_DTC, timeoutMs = 5000)
        } catch (e: Exception) {
            restoreDefaults()
            return ModuleDtcResult(candidate, emptyList(), "ERROR: ${e.message}")
        }
        val codes = decodeUdsDtcs(resp)
        restoreDefaults()
        return ModuleDtcResult(candidate, codes, resp)
    }

    private suspend fun setHeaders(requestId: Int) {
        val reqHex = String.format("%03X", requestId and 0x7FF)
        val respHex = String.format("%03X", (requestId + 8) and 0x7FF)
        try {
            connection.sendCommand("ATSH$reqHex")
            connection.sendCommand("ATCRA$respHex")
        } catch (_: Exception) {
        }
    }

    private suspend fun restoreDefaults() {
        try {
            connection.sendCommand("ATCRA")
            connection.sendCommand("ATSH7DF")
        } catch (_: Exception) {
        }
    }

    private fun isPositiveOrNegativeUds(response: String): Boolean {
        val bytes = hexTokens(response)
        // Positive response = request SID + 0x40 (e.g. 10 -> 50, 3E -> 7E).
        // Negative response = 0x7F <sid> <nrc>; still proves the ECU exists.
        val idx = bytes.indexOfFirst { it == "50" || it == "7E" || it == "7F" }
        return idx != -1
    }

    private fun isGatewayRefusal(response: String): Boolean {
        val upper = response.uppercase()
        return upper.contains("NO DATA") ||
            upper.contains("UNABLE TO CONNECT") ||
            upper.contains("CAN ERROR") ||
            upper.contains("BUFFER FULL") ||
            upper.contains("TIMEOUT")
    }

    private fun decodeUdsDtcs(response: String): List<String> {
        val bytes = hexTokens(response)
        val idx = bytes.indexOf("59")
        if (idx == -1) return emptyList()
        // After 59 02 <availabilityMask>, DTCs are 4-byte records:
        // 3 bytes of DTC + 1 status byte.
        val payload = bytes.drop(idx + 3)
        val codes = mutableListOf<String>()
        var i = 0
        while (i + 4 <= payload.size) {
            val b0 = payload[i].toIntOrNull(16) ?: break
            val b1 = payload[i + 1].toIntOrNull(16) ?: break
            val b2 = payload[i + 2].toIntOrNull(16) ?: break
            i += 4
            if (b0 == 0 && b1 == 0 && b2 == 0) continue
            val twoByteHex = String.format("%02X%02X", b0, b1)
            val decoded = DtcDecoder.decode(twoByteHex).firstOrNull()
            if (decoded != null) {
                codes.add(decoded)
            }
        }
        return codes
    }

    private fun hexTokens(response: String): List<String> {
        return response.replace('\n', ' ')
            .split(Regex("\\s+"))
            .filter { it.matches(Regex("^[0-9A-Fa-f]{2}$")) }
            .map { it.uppercase() }
    }
}
