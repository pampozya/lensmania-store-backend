package com.pampozya.cardiag.ui

import android.graphics.Color
import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.pampozya.cardiag.databinding.ItemModuleBinding
import com.pampozya.cardiag.obd.ModuleProbe

/**
 * UI row state for a scanned module: the probe result plus any DTC detail
 * text loaded after tapping an online module.
 */
data class ModuleRow(
    val probe: ModuleProbe,
    val detail: String? = null
)

class ModuleAdapter(
    private val onClick: (ModuleRow) -> Unit
) : RecyclerView.Adapter<ModuleAdapter.ModuleViewHolder>() {

    private val rows = mutableListOf<ModuleRow>()

    fun submitList(newRows: List<ModuleRow>) {
        rows.clear()
        rows.addAll(newRows)
        notifyDataSetChanged()
    }

    fun upsert(row: ModuleRow) {
        val idx = rows.indexOfFirst { it.probe.candidate.requestId == row.probe.candidate.requestId }
        if (idx == -1) {
            rows.add(row)
            notifyItemInserted(rows.size - 1)
        } else {
            rows[idx] = row
            notifyItemChanged(idx)
        }
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ModuleViewHolder {
        val inflater = LayoutInflater.from(parent.context)
        return ModuleViewHolder(ItemModuleBinding.inflate(inflater, parent, false))
    }

    override fun onBindViewHolder(holder: ModuleViewHolder, position: Int) {
        holder.bind(rows[position])
    }

    override fun getItemCount(): Int = rows.size

    inner class ModuleViewHolder(private val binding: ItemModuleBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(row: ModuleRow) {
            val probe = row.probe
            binding.textModuleName.text = probe.candidate.name
            val reqHex = String.format("%03X", probe.candidate.requestId)
            val respHex = String.format("%03X", probe.candidate.requestId + 8)
            binding.textModuleAddress.text = "req $reqHex · resp $respHex"

            val (label, color) = when {
                probe.responded -> "ONLINE" to Color.parseColor("#2E7D32")
                probe.gatewayBlocked -> "BLOCKED" to Color.parseColor("#C62828")
                else -> "NO RESP" to Color.parseColor("#757575")
            }
            binding.textModuleStatus.text = label
            binding.textModuleStatus.setTextColor(color)

            if (row.detail != null) {
                binding.textModuleDetail.visibility = android.view.View.VISIBLE
                binding.textModuleDetail.text = row.detail
            } else {
                binding.textModuleDetail.visibility = android.view.View.GONE
            }

            binding.root.setOnClickListener { onClick(row) }
        }
    }
}
