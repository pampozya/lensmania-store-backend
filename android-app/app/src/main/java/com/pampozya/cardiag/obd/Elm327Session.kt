package com.pampozya.cardiag.obd

/**
 * Result of an ELM327 initialization sequence.
 */
data class InitResult(
    val success: Boolean,
    val transcript: String,
    val elmVersion: String?
)

/**
 * Talks ELM327 AT-command dialect over an [ObdConnection] and exposes
 * higher-level OBD-II queries (live data, VIN, DTCs).
 */
class Elm327Session(private val connection: ObdConnection) {

    private var elmVersion: String? = null

    suspend fun initialize(): InitResult {
        val transcript = StringBuilder()

        suspend fun send(cmd: String, timeoutMs: Long = 5000): String {
            val resp = try {
                connection.sendCommand(cmd, timeoutMs)
            } catch (e: Exception) {
                "ERROR: ${e.message}"
            }
            transcript.append("> ").append(cmd).append('\n').append(resp).append("\n\n")
            return resp
        }

        val atzResp = send("ATZ", 3000)
        elmVersion = atzResp.trim().takeIf { it.isNotBlank() && !it.startsWith("ERROR") }

        send("ATE0")
        send("ATL0")
        send("ATS0")
        send("ATH0")
        send("ATSP6")

        var probe = send("0100")
        var success = !containsInitError(probe)

        if (!success) {
            send("ATSP0")
            probe = send("0100")
            success = !containsInitError(probe)
        }

        return InitResult(success = success, transcript = transcript.toString(), elmVersion = elmVersion)
    }

    private fun containsInitError(response: String): Boolean {
        val upper = response.uppercase()
        return upper.contains("UNABLE TO CONNECT") ||
            upper.contains("CAN ERROR") ||
            upper.contains("NO DATA") ||
            upper.startsWith("ERROR")
    }

    // ---- Live data ----

    suspend fun rpm(): Int? = queryPid("010C", 0x41, 0x0C) { b ->
        if (b.size >= 2) ((b[0] * 256) + b[1]) / 4 else null
    }

    suspend fun speedKmh(): Int? = queryPid("010D", 0x41, 0x0D) { b ->
        b.getOrNull(0)
    }

    suspend fun coolantC(): Int? = queryPid("0105", 0x41, 0x05) { b ->
        b.getOrNull(0)?.let { it - 40 }
    }

    suspend fun intakeC(): Int? = queryPid("010F", 0x41, 0x0F) { b ->
        b.getOrNull(0)?.let { it - 40 }
    }

    suspend fun throttlePct(): Int? = queryPid("0111", 0x41, 0x11) { b ->
        b.getOrNull(0)?.let { it * 100 / 255 }
    }

    suspend fun engineLoadPct(): Int? = queryPid("0104", 0x41, 0x04) { b ->
        b.getOrNull(0)?.let { it * 100 / 255 }
    }

    suspend fun batteryVolts(): Double? {
        val resp = try {
            connection.sendCommand("ATRV")
        } catch (e: Exception) {
            return null
        }
        if (containsInitError(resp)) return null
        val match = Regex("-?\\d+(\\.\\d+)?").find(resp.replace("V", "", ignoreCase = true))
        return match?.value?.toDoubleOrNull()
    }

    // ---- VIN ----

    suspend fun vin(): String? {
        val resp = try {
            connection.sendCommand("0902")
        } catch (e: Exception) {
            return null
        }
        if (containsInitError(resp)) return null

        val cleaned = resp.replace(Regex("[^0-9A-Fa-f]"), "").uppercase()
        val idx = cleaned.indexOf("4902")
        if (idx == -1) return null

        var rest = cleaned.substring(idx + 4)
        // Drop the sequence/index byte that follows the "4902" header.
        if (rest.length >= 2) {
            rest = rest.substring(2)
        }

        val sb = StringBuilder()
        var i = 0
        while (i + 2 <= rest.length) {
            val byteVal = rest.substring(i, i + 2).toIntOrNull(16)
            i += 2
            if (byteVal != null && byteVal in 0x20..0x7E) {
                sb.append(byteVal.toChar())
            }
        }
        val text = sb.toString()
        val vinMatch = Regex("[A-HJ-NPR-Z0-9]{17}").find(text)
        return vinMatch?.value ?: text.trim().ifBlank { null }
    }

    // ---- DTCs ----

    suspend fun storedDtcs(): List<String> = readDtcs("03", 0x43)

    suspend fun pendingDtcs(): List<String> = readDtcs("07", 0x47)

    suspend fun clearDtcs(): Boolean {
        val resp = try {
            connection.sendCommand("04")
        } catch (e: Exception) {
            return false
        }
        return resp.uppercase().contains("44")
    }

    private suspend fun readDtcs(cmd: String, modeResponse: Int): List<String> {
        val resp = try {
            connection.sendCommand(cmd)
        } catch (e: Exception) {
            return emptyList()
        }
        if (containsInitError(resp)) return emptyList()

        val bytes = hexBytes(resp)
        val idx = bytes.indexOf(modeResponse)
        if (idx == -1) return emptyList()
        val payload = bytes.drop(idx + 1)
        val hex = payload.joinToString("") { String.format("%02X", it) }
        return DtcDecoder.decode(hex)
    }

    // ---- Helpers ----

    private suspend fun <T> queryPid(cmd: String, modeResponse: Int, pid: Int, transform: (List<Int>) -> T?): T? {
        val resp = try {
            connection.sendCommand(cmd)
        } catch (e: Exception) {
            return null
        }
        if (containsInitError(resp)) return null
        val payload = extractBytesAfterHeader(resp, modeResponse, pid) ?: return null
        return transform(payload)
    }

    private fun extractBytesAfterHeader(response: String, modeResponse: Int, pid: Int): List<Int>? {
        val bytes = hexBytes(response)
        for (i in bytes.indices) {
            if (bytes[i] == modeResponse && i + 1 < bytes.size && bytes[i + 1] == pid) {
                return bytes.drop(i + 2)
            }
        }
        return null
    }

    private fun hexBytes(response: String): List<Int> {
        val tokens = response.replace('\n', ' ').split(Regex("\\s+")).filter { it.isNotBlank() }
        val bytes = mutableListOf<Int>()
        for (token in tokens) {
            if (token.matches(Regex("^[0-9A-Fa-f]{2}$"))) {
                bytes.add(token.toInt(16))
            }
        }
        return bytes
    }
}
