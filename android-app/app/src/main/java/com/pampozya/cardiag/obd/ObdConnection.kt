package com.pampozya.cardiag.obd

import android.annotation.SuppressLint
import android.bluetooth.BluetoothDevice
import android.bluetooth.BluetoothSocket
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.delay
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.coroutines.withContext
import kotlinx.coroutines.withTimeout
import java.io.IOException
import java.io.InputStream
import java.io.OutputStream
import java.util.UUID

/**
 * Wraps a classic Bluetooth RFCOMM socket to an OBD-II / ELM327-style adapter.
 * All IO happens on Dispatchers.IO. Only one command may be in flight at a time.
 */
class ObdConnection {

    companion object {
        private val SPP_UUID: UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")
        private const val PROMPT_CHAR = '>'
        private const val POLL_DELAY_MS = 25L
    }

    private var socket: BluetoothSocket? = null
    private var input: InputStream? = null
    private var output: OutputStream? = null
    private val commandMutex = Mutex()

    val isConnected: Boolean
        get() = socket?.isConnected == true

    @SuppressLint("MissingPermission")
    suspend fun connect(device: BluetoothDevice): Result<Unit> = withContext(Dispatchers.IO) {
        try {
            try {
                // Cancel discovery to avoid slowing/breaking the connection.
                val adapter = android.bluetooth.BluetoothAdapter.getDefaultAdapter()
                adapter?.cancelDiscovery()
            } catch (e: SecurityException) {
                // Ignore — proceed with connect attempt regardless.
            }

            var newSocket: BluetoothSocket? = try {
                device.createRfcommSocketToServiceRecord(SPP_UUID)
            } catch (e: SecurityException) {
                null
            } catch (e: IOException) {
                null
            }

            var connected = false
            if (newSocket != null) {
                try {
                    newSocket.connect()
                    connected = true
                } catch (e: IOException) {
                    try {
                        newSocket.close()
                    } catch (_: IOException) {
                    }
                    newSocket = null
                } catch (e: SecurityException) {
                    newSocket = null
                }
            }

            if (!connected) {
                // Fallback to insecure RFCOMM.
                val insecureSocket: BluetoothSocket? = try {
                    device.createInsecureRfcommSocketToServiceRecord(SPP_UUID)
                } catch (e: SecurityException) {
                    null
                } catch (e: IOException) {
                    null
                }
                if (insecureSocket == null) {
                    return@withContext Result.failure(
                        IOException("Could not create Bluetooth socket (permission or device error).")
                    )
                }
                try {
                    insecureSocket.connect()
                    newSocket = insecureSocket
                    connected = true
                } catch (e: IOException) {
                    try {
                        insecureSocket.close()
                    } catch (_: IOException) {
                    }
                    return@withContext Result.failure(e)
                } catch (e: SecurityException) {
                    return@withContext Result.failure(e)
                }
            }

            socket = newSocket
            input = newSocket?.inputStream
            output = newSocket?.outputStream
            Result.success(Unit)
        } catch (e: IOException) {
            Result.failure(e)
        } catch (e: SecurityException) {
            Result.failure(e)
        }
    }

    suspend fun sendCommand(cmd: String, timeoutMs: Long = 5000): String = commandMutex.withLock {
        withContext(Dispatchers.IO) {
            val out = output ?: throw IOException("Not connected")
            val inp = input ?: throw IOException("Not connected")
            val raw = withTimeout(timeoutMs) {
                out.write((cmd + "\r").toByteArray(Charsets.US_ASCII))
                out.flush()

                val buffer = StringBuilder()
                var sawPrompt = false
                while (!sawPrompt) {
                    val available = inp.available()
                    if (available > 0) {
                        val bytes = ByteArray(available)
                        val read = inp.read(bytes, 0, available)
                        if (read > 0) {
                            for (i in 0 until read) {
                                val c = (bytes[i].toInt() and 0xFF).toChar()
                                if (c == PROMPT_CHAR) {
                                    sawPrompt = true
                                    break
                                }
                                buffer.append(c)
                            }
                        }
                    } else {
                        delay(POLL_DELAY_MS)
                    }
                }
                buffer.toString()
            }
            normalizeResponse(raw)
        }
    }

    private fun normalizeResponse(raw: String): String {
        val normalized = raw.replace("\r\n", "\n").replace('\r', '\n')
        return normalized
            .lineSequence()
            .map { it.trim() }
            .filter { it.isNotEmpty() && it != "SEARCHING..." }
            .joinToString("\n")
            .trim()
    }

    suspend fun close() = withContext(Dispatchers.IO) {
        try {
            input?.close()
        } catch (_: IOException) {
        }
        try {
            output?.close()
        } catch (_: IOException) {
        }
        try {
            socket?.close()
        } catch (_: IOException) {
        }
        input = null
        output = null
        socket = null
    }
}
