package com.pampozya.cardiag.ui

import android.bluetooth.BluetoothDevice
import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.pampozya.cardiag.databinding.ItemDeviceBinding

class DeviceAdapter(
    private val onDeviceClick: (BluetoothDevice) -> Unit
) : RecyclerView.Adapter<DeviceAdapter.DeviceViewHolder>() {

    private val devices = mutableListOf<BluetoothDevice>()

    fun submitList(newDevices: List<BluetoothDevice>) {
        devices.clear()
        devices.addAll(newDevices)
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): DeviceViewHolder {
        val binding = ItemDeviceBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return DeviceViewHolder(binding)
    }

    override fun onBindViewHolder(holder: DeviceViewHolder, position: Int) {
        holder.bind(devices[position], onDeviceClick)
    }

    override fun getItemCount(): Int = devices.size

    class DeviceViewHolder(private val binding: ItemDeviceBinding) : RecyclerView.ViewHolder(binding.root) {
        @android.annotation.SuppressLint("MissingPermission")
        fun bind(device: BluetoothDevice, onDeviceClick: (BluetoothDevice) -> Unit) {
            val name = try {
                device.name
            } catch (e: SecurityException) {
                null
            } ?: "Unknown device"
            binding.textDeviceName.text = name
            binding.textDeviceAddress.text = device.address
            binding.rowRoot.setOnClickListener { onDeviceClick(device) }
        }
    }
}
