package com.pampozya.cardiag.obd

/**
 * Decodes raw OBD-II DTC payload bytes (as returned after the mode/PID header,
 * e.g. "43 01 33 00 00" -> "0133") into standard DTC strings like "P0133",
 * and provides human-readable descriptions for common codes.
 */
object DtcDecoder {

    private val firstCharByBits = mapOf(
        0 to 'P',
        1 to 'C',
        2 to 'B',
        3 to 'U'
    )

    /**
     * [payloadHex] should be the hex payload (no spaces or with spaces, both fine)
     * containing pairs of bytes, each pair encoding one DTC. "0000" pairs are skipped.
     */
    fun decode(payloadHex: String): List<String> {
        val cleaned = payloadHex.replace(Regex("[^0-9A-Fa-f]"), "").uppercase()
        if (cleaned.length < 4) return emptyList()

        val codes = mutableListOf<String>()
        var i = 0
        while (i + 4 <= cleaned.length) {
            val pair = cleaned.substring(i, i + 4)
            i += 4
            if (pair == "0000") continue

            val byte1 = pair.substring(0, 2).toIntOrNull(16) ?: continue
            val nibble3 = pair[2]
            val rest = pair.substring(3)

            val typeBits = (byte1 shr 6) and 0x03
            val firstDigit = (byte1 shr 4) and 0x03
            val typeChar = firstCharByBits[typeBits] ?: 'P'

            val code = "$typeChar$firstDigit$nibble3$rest"
            codes.add(code)
        }
        return codes
    }

    fun describe(code: String): String {
        return DESCRIPTIONS[code.uppercase()] ?: "Unknown code — look it up for MG/SAIC specifics"
    }

    private val DESCRIPTIONS: Map<String, String> = mapOf(
        "P0100" to "Mass or Volume Air Flow (MAF) circuit malfunction",
        "P0101" to "MAF circuit range/performance problem",
        "P0102" to "MAF circuit low input",
        "P0103" to "MAF circuit high input",
        "P0113" to "Intake Air Temperature (IAT) sensor circuit high input",
        "P0117" to "Engine Coolant Temperature (ECT) sensor circuit low input",
        "P0118" to "Engine Coolant Temperature (ECT) sensor circuit high input",
        "P0128" to "Coolant thermostat — temperature below regulating value",
        "P0131" to "O2 sensor circuit low voltage (Bank 1, Sensor 1)",
        "P0133" to "O2 sensor circuit slow response (Bank 1, Sensor 1)",
        "P0141" to "O2 sensor heater circuit malfunction (Bank 1, Sensor 2)",
        "P0171" to "Fuel system too lean (Bank 1)",
        "P0172" to "Fuel system too rich (Bank 1)",
        "P0174" to "Fuel system too lean (Bank 2)",
        "P0175" to "Fuel system too rich (Bank 2)",
        "P0201" to "Injector circuit malfunction — Cylinder 1",
        "P0202" to "Injector circuit malfunction — Cylinder 2",
        "P0203" to "Injector circuit malfunction — Cylinder 3",
        "P0204" to "Injector circuit malfunction — Cylinder 4",
        "P0217" to "Engine over temperature condition",
        "P0300" to "Random / multiple cylinder misfire detected",
        "P0301" to "Cylinder 1 misfire detected",
        "P0302" to "Cylinder 2 misfire detected",
        "P0303" to "Cylinder 3 misfire detected",
        "P0304" to "Cylinder 4 misfire detected",
        "P0325" to "Knock sensor 1 circuit malfunction",
        "P0335" to "Crankshaft position sensor circuit malfunction",
        "P0340" to "Camshaft position sensor circuit malfunction",
        "P0401" to "Exhaust gas recirculation (EGR) — insufficient flow detected",
        "P0402" to "EGR flow excessive detected",
        "P0410" to "Secondary air injection system malfunction",
        "P0420" to "Catalyst system efficiency below threshold (Bank 1)",
        "P0430" to "Catalyst system efficiency below threshold (Bank 2)",
        "P0440" to "Evaporative emission control system malfunction",
        "P0442" to "Evaporative emission control system — small leak detected",
        "P0455" to "Evaporative emission control system — large leak detected",
        "P0456" to "Evaporative emission control system — very small leak detected",
        "P0460" to "Fuel level sensor circuit malfunction",
        "P0500" to "Vehicle speed sensor malfunction",
        "P0506" to "Idle control system — RPM lower than expected",
        "P0507" to "Idle control system — RPM higher than expected",
        "P0562" to "System voltage low",
        "P0563" to "System voltage high",
        "P0600" to "Serial communication link malfunction",
        "P0601" to "Internal control module memory checksum error",
        "P0700" to "Transmission control system malfunction (fault stored in TCM)",
        "P0705" to "Transmission range sensor circuit malfunction (PRNDL input)",
        "P0715" to "Input/turbine speed sensor circuit malfunction",
        "P0720" to "Output speed sensor circuit malfunction",
        "P0741" to "Torque converter clutch — stuck off",
        "P0AA6" to "Hybrid/EV battery voltage system isolation fault",
        "P1101" to "Manufacturer-specific — MAF sensor out of range",
        "U0100" to "Lost communication with ECM/PCM",
        "U0101" to "Lost communication with TCM",
        "U0121" to "Lost communication with ABS control module",
        "U0140" to "Lost communication with body control module",
        "U0155" to "Lost communication with instrument panel cluster",
        "U0401" to "Invalid data received from ECM/PCM",
        "B0001" to "Restraint system — driver frontal deployment control (example)",
        "B0012" to "Restraint system — passenger frontal deployment control",
        "B1000" to "ECU malfunction (body)",
        "C0035" to "Left front wheel speed sensor circuit malfunction",
        "C0040" to "Right front wheel speed sensor circuit malfunction",
        "C0045" to "Left rear wheel speed sensor circuit malfunction",
        "C0050" to "Right rear wheel speed sensor circuit malfunction",
        "C0110" to "ABS pump motor circuit malfunction"
    )
}
