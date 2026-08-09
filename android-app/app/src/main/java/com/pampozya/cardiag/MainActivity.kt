package com.pampozya.cardiag

import android.annotation.SuppressLint
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothDevice
import android.bluetooth.BluetoothManager
import android.content.Context
import android.content.Intent
import android.os.Build
import android.os.Bundle
import android.provider.Settings
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.appcompat.app.AlertDialog
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.pampozya.cardiag.databinding.ActivityMainBinding
import com.pampozya.cardiag.databinding.DialogConnectingBinding
import com.pampozya.cardiag.obd.ObdManager
import com.pampozya.cardiag.ui.DeviceAdapter
import kotlinx.coroutines.launch

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding
    private lateinit var deviceAdapter: DeviceAdapter
    private var connectingDialog: AlertDialog? = null

    private val requiredPermissions: Array<String>
        get() = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            arrayOf(
                android.Manifest.permission.BLUETOOTH_CONNECT,
                android.Manifest.permission.BLUETOOTH_SCAN
            )
        } else {
            arrayOf(android.Manifest.permission.ACCESS_FINE_LOCATION)
        }

    private val permissionLauncher =
        registerForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) { results ->
            val allGranted = results.values.all { it }
            updatePermissionUi(allGranted)
            if (allGranted) {
                refreshDeviceList()
            }
        }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)
        setSupportActionBar(binding.toolbar)

        deviceAdapter = DeviceAdapter { device -> connectToDevice(device) }
        binding.recyclerDevices.layoutManager = LinearLayoutManager(this)
        binding.recyclerDevices.adapter = deviceAdapter

        binding.buttonOpenBluetoothSettings.setOnClickListener {
            startActivity(Intent(Settings.ACTION_BLUETOOTH_SETTINGS))
        }

        binding.buttonGrantPermissions.setOnClickListener {
            permissionLauncher.launch(requiredPermissions)
        }

        if (hasAllPermissions()) {
            updatePermissionUi(true)
        } else {
            updatePermissionUi(false)
        }
    }

    override fun onResume() {
        super.onResume()
        if (hasAllPermissions()) {
            updatePermissionUi(true)
            refreshDeviceList()
        }
    }

    private fun hasAllPermissions(): Boolean {
        return requiredPermissions.all {
            ContextCompat.checkSelfPermission(this, it) == android.content.pm.PackageManager.PERMISSION_GRANTED
        }
    }

    private fun updatePermissionUi(granted: Boolean) {
        binding.textPermissionRationale.visibility = if (granted) android.view.View.GONE else android.view.View.VISIBLE
        binding.buttonGrantPermissions.visibility = if (granted) android.view.View.GONE else android.view.View.VISIBLE
    }

    @SuppressLint("MissingPermission")
    private fun refreshDeviceList() {
        val adapterManager = getSystemService(Context.BLUETOOTH_SERVICE) as? BluetoothManager
        val btAdapter: BluetoothAdapter? = adapterManager?.adapter

        if (btAdapter == null || !btAdapter.isEnabled) {
            deviceAdapter.submitList(emptyList())
            binding.textNoDevices.visibility = android.view.View.VISIBLE
            return
        }

        val bonded: Set<BluetoothDevice> = try {
            btAdapter.bondedDevices ?: emptySet()
        } catch (e: SecurityException) {
            emptySet()
        }

        deviceAdapter.submitList(bonded.toList())
        binding.textNoDevices.visibility = if (bonded.isEmpty()) android.view.View.VISIBLE else android.view.View.GONE
    }

    @SuppressLint("MissingPermission")
    private fun connectToDevice(device: BluetoothDevice) {
        val deviceLabel = try {
            device.name ?: device.address
        } catch (e: SecurityException) {
            device.address
        }

        showConnectingDialog(deviceLabel)

        lifecycleScope.launch {
            val outcome = ObdManager.connect(device)
            dismissConnectingDialog()

            if (outcome.connected && outcome.initResult?.success == true) {
                startActivity(Intent(this@MainActivity, DiagnosticsActivity::class.java))
            } else {
                val transcript = outcome.initResult?.transcript ?: outcome.error.orEmpty()
                showInitFailedDialog(transcript)
            }
        }
    }

    private fun showConnectingDialog(deviceLabel: String) {
        val dialogBinding = DialogConnectingBinding.inflate(layoutInflater)
        dialogBinding.textConnectingMessage.text = getString(R.string.connecting_message, deviceLabel)
        connectingDialog = MaterialAlertDialogBuilder(this)
            .setView(dialogBinding.root)
            .setCancelable(false)
            .show()
    }

    private fun dismissConnectingDialog() {
        connectingDialog?.dismiss()
        connectingDialog = null
    }

    private fun showInitFailedDialog(transcript: String) {
        MaterialAlertDialogBuilder(this)
            .setTitle(R.string.init_failed_title)
            .setMessage(getString(R.string.init_failed_thinkdiag_note) + "\n\n" + transcript)
            .setPositiveButton(R.string.open_terminal_anyway) { _, _ ->
                startActivity(Intent(this, DiagnosticsActivity::class.java))
            }
            .setNegativeButton(R.string.cancel, null)
            .show()
    }
}
