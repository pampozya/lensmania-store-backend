package com.pampozya.cardiag.ui

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.pampozya.cardiag.databinding.ItemDtcBinding
import com.pampozya.cardiag.databinding.ItemDtcHeaderBinding
import com.pampozya.cardiag.obd.DtcDecoder

sealed class DtcListItem {
    data class Header(val title: String) : DtcListItem()
    data class Code(val code: String) : DtcListItem()
}

private const val TYPE_HEADER = 0
private const val TYPE_CODE = 1

class DtcAdapter : RecyclerView.Adapter<RecyclerView.ViewHolder>() {

    private val items = mutableListOf<DtcListItem>()

    fun submitList(newItems: List<DtcListItem>) {
        items.clear()
        items.addAll(newItems)
        notifyDataSetChanged()
    }

    override fun getItemViewType(position: Int): Int = when (items[position]) {
        is DtcListItem.Header -> TYPE_HEADER
        is DtcListItem.Code -> TYPE_CODE
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): RecyclerView.ViewHolder {
        val inflater = LayoutInflater.from(parent.context)
        return if (viewType == TYPE_HEADER) {
            HeaderViewHolder(ItemDtcHeaderBinding.inflate(inflater, parent, false))
        } else {
            CodeViewHolder(ItemDtcBinding.inflate(inflater, parent, false))
        }
    }

    override fun onBindViewHolder(holder: RecyclerView.ViewHolder, position: Int) {
        when (val item = items[position]) {
            is DtcListItem.Header -> (holder as HeaderViewHolder).bind(item)
            is DtcListItem.Code -> (holder as CodeViewHolder).bind(item)
        }
    }

    override fun getItemCount(): Int = items.size

    class HeaderViewHolder(private val binding: ItemDtcHeaderBinding) : RecyclerView.ViewHolder(binding.root) {
        fun bind(item: DtcListItem.Header) {
            binding.textHeader.text = item.title
        }
    }

    class CodeViewHolder(private val binding: ItemDtcBinding) : RecyclerView.ViewHolder(binding.root) {
        fun bind(item: DtcListItem.Code) {
            binding.textCode.text = item.code
            binding.textDescription.text = DtcDecoder.describe(item.code)
        }
    }
}
