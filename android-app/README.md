# CarDiag

A Bluetooth OBD-II car diagnostic app for Android, built for a **2026 MG ZS petrol**
(VIN `LSJWS4U37TZ022642`) and a **ThinkDiag 2** Bluetooth adapter — but it works with
any classic-Bluetooth ELM327-compatible OBD-II adapter and any OBD-II compliant car.

It reads live sensor data (RPM, speed, coolant/intake temperature, throttle,
engine load, battery voltage), the VIN, and stored/pending diagnostic trouble
codes (DTCs), and includes a raw terminal for sending AT/OBD commands directly
to the adapter.

## Getting the APK

This project has no local Android SDK requirement to build in CI. GitHub
Actions builds the debug APK automatically:

1. Push to any branch (or run the workflow manually from the **Actions** tab —
   `Android APK` workflow, `Run workflow`).
2. Once the run finishes, open it and download the `CarDiag-debug-apk`
   artifact from the **Artifacts** section.
3. Unzip it to get `app-debug.apk`.

## Sideloading

1. Copy `app-debug.apk` to your phone.
2. On the phone, allow installs from your file manager / browser
   ("Install unknown apps") when prompted.
3. Open the APK file and install.

## Pairing your OBD adapter

1. Plug the OBD adapter (ThinkDiag 2, ELM327 dongle, OBDLink, etc.) into the
   car's OBD-II port and turn the ignition to the ON position (engine can be
   off for most PIDs, but RPM/speed need the engine running).
2. On your phone, go to **Settings → Bluetooth**, scan, and pair with the
   adapter. Most ELM327 adapters use PIN `1234` or `0000`.
3. Open CarDiag. The paired adapter appears in the device list. Tap it to
   connect.
4. CarDiag attempts to initialize an ELM327 session (`ATZ`, `ATE0`, `ATL0`,
   `ATS0`, `ATH0`, `ATSP6`, then a live probe). On success you land on the
   diagnostics screen with Live Data / Trouble Codes / Terminal tabs.

## ThinkDiag 2 caveat

The ThinkDiag 2 (and other Thinkcar/Autel-style "smart" scan tools) primarily
speaks a **proprietary Thinkcar protocol** over Bluetooth, not the standard
ELM327 AT-command set. That means:

- CarDiag's ELM327 initialization may fail against a ThinkDiag 2 — you'll see
  a dialog explaining this, with an option to **"Open Terminal anyway"**.
- In the **Terminal** tab you can send raw commands and see exactly what the
  adapter replies with, which is useful for figuring out whether it will ever
  answer standard OBD-II mode 01/03/09 requests over this link.
- If you need reliable ELM327 behavior, use any **ELM327-compatible** or
  **OBDLink** Bluetooth adapter — those work with CarDiag out of the box with
  no special setup.
- Full ThinkDiag 2 module-level diagnostics (ABS, airbag, TPMS, etc.) require
  Thinkcar's own app; CarDiag only speaks generic OBD-II.

## Secure gateway caveat (2026 MG ZS)

Modern MG/SAIC vehicles (including the 2026 MG ZS) have a secure gateway
module between the OBD-II port and the vehicle's internal CAN buses. In
practice this means:

- **Mandated emissions-related OBD-II access works fine**: live sensor data,
  VIN (mode 09 PID 02), and generic powertrain DTCs (modes 03/04/07) should
  all be reachable, since regulations require this to remain open.
- **Module-level / manufacturer-specific diagnostics are blocked** by the
  gateway unless unlocked with the OEM's own diagnostic tool and credentials
  (e.g. ABS, airbag, body control modules, bi-directional actuator tests).
- **Clearing codes (mode 04) may also be restricted** on some models/software
  levels, even though reading them works. CarDiag's "Clear codes" button
  warns about this before sending the command.
- For anything beyond generic OBD-II, you'll need Thinkcar's own app with the
  ThinkDiag 2 (or an equivalent OEM-capable tool).

## Building locally in Android Studio

1. Open the `android-app/` folder in Android Studio (Ladybug or newer
   recommended).
2. Let Gradle sync — it uses AGP 8.7.3, Kotlin 2.0.21, and Gradle 8.14.3 via
   the included wrapper (`./gradlew`).
3. Run the `app` configuration on a device or emulator, or build
   `Build > Build Bundle(s) / APK(s) > Build APK(s)`.

Requirements: JDK 17, Android SDK with `compileSdk`/`targetSdk` 35 installed
(Android Studio will prompt to install it if missing). `minSdk` is 26
(Android 8.0+).

## Architecture

- `obd/ObdConnection.kt` — raw Bluetooth RFCOMM socket I/O (classic
  Bluetooth SPP UUID `00001101-0000-1000-8000-00805F9B34FB`).
- `obd/Elm327Session.kt` — ELM327 AT-command initialization and OBD-II PID
  queries (live data, VIN, DTCs) on top of `ObdConnection`.
- `obd/ObdManager.kt` — app-wide singleton holding the current connection,
  session, and connection state (`StateFlow<ConnState>`).
- `obd/DtcDecoder.kt` — decodes raw DTC payload bytes into `P0XXX`/`C0XXX`/
  `B0XXX`/`U0XXX` codes and human-readable descriptions.
- `MainActivity` — permissions, paired device list, connect flow.
- `DiagnosticsActivity` — tabbed Live Data / Trouble Codes / Terminal UI.

No Compose — classic Android Views with Material 3 components and
viewBinding throughout.
