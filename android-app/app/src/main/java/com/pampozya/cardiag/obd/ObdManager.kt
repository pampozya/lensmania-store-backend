package com.pampozya.cardiag.obd

import android.annotation.SuppressLint
import android.bluetooth.BluetoothDevice
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

sealed class ConnState {
    data object Disconnected : ConnState()
    data object Connecting : ConnState()
    data object Connected : ConnState()
    data class Error(val message: String) : ConnState()
}

/**
 * App-wide singleton holding the current OBD connection, session and state.
 */
object ObdManager {

    private val _state = MutableStateFlow<ConnState>(ConnState.Disconnected)
    val state: StateFlow<ConnState> = _state.asStateFlow()

    var connection: ObdConnection? = null
        private set

    var session: Elm327Session? = null
        private set

    var deviceName: String? = null
        private set

    var deviceAddress: String? = null
        private set

    data class ConnectOutcome(
        val connected: Boolean,
        val initResult: InitResult?,
        val error: String? = null
    )

    @SuppressLint("MissingPermission")
    suspend fun connect(device: BluetoothDevice): ConnectOutcome {
        _state.value = ConnState.Connecting
        val name = try {
            device.name ?: device.address
        } catch (e: SecurityException) {
            device.address
        }
        deviceName = name
        deviceAddress = device.address

        val newConnection = ObdConnection()
        val connectResult = newConnection.connect(device)
        if (connectResult.isFailure) {
            val msg = connectResult.exceptionOrNull()?.message ?: "Connection failed"
            _state.value = ConnState.Error(msg)
            return ConnectOutcome(connected = false, initResult = null, error = msg)
        }

        connection = newConnection
        val newSession = Elm327Session(newConnection)
        session = newSession

        val initResult = try {
            newSession.initialize()
        } catch (e: Exception) {
            val msg = e.message ?: "Initialization failed"
            _state.value = ConnState.Error(msg)
            return ConnectOutcome(connected = false, initResult = null, error = msg)
        }

        _state.value = if (initResult.success) ConnState.Connected else ConnState.Error(
            "No ELM327 response"
        )

        return ConnectOutcome(connected = true, initResult = initResult)
    }

    suspend fun disconnect() {
        connection?.close()
        connection = null
        session = null
        deviceName = null
        deviceAddress = null
        _state.value = ConnState.Disconnected
    }
}
