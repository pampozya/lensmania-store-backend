package com.pampozya.cardiag.ui

import android.os.Bundle
import android.view.View
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.pampozya.cardiag.R
import com.pampozya.cardiag.databinding.FragmentDtcBinding
import com.pampozya.cardiag.obd.ObdManager
import kotlinx.coroutines.launch

class DtcFragment : Fragment(R.layout.fragment_dtc) {

    private var _binding: FragmentDtcBinding? = null
    private val binding get() = _binding!!

    private val adapter = DtcAdapter()

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        _binding = FragmentDtcBinding.bind(view)

        binding.recyclerDtcs.layoutManager = LinearLayoutManager(requireContext())
        binding.recyclerDtcs.adapter = adapter

        binding.buttonReadCodes.setOnClickListener { readCodes() }
        binding.buttonClearCodes.setOnClickListener { confirmClearCodes() }
    }

    private fun readCodes() {
        val session = ObdManager.session ?: return
        setLoading(true)

        viewLifecycleOwner.lifecycleScope.launch {
            val stored = session.storedDtcs()
            val pending = session.pendingDtcs()
            setLoading(false)
            renderResults(stored, pending)
        }
    }

    private fun renderResults(stored: List<String>, pending: List<String>) {
        val items = mutableListOf<DtcListItem>()
        if (stored.isNotEmpty()) {
            items.add(DtcListItem.Header(getString(R.string.section_stored)))
            items.addAll(stored.map { DtcListItem.Code(it) })
        }
        if (pending.isNotEmpty()) {
            items.add(DtcListItem.Header(getString(R.string.section_pending)))
            items.addAll(pending.map { DtcListItem.Code(it) })
        }

        adapter.submitList(items)
        val empty = items.isEmpty()
        binding.textEmpty.visibility = if (empty) View.VISIBLE else View.GONE
        binding.recyclerDtcs.visibility = if (empty) View.GONE else View.VISIBLE
    }

    private fun confirmClearCodes() {
        MaterialAlertDialogBuilder(requireContext())
            .setTitle(R.string.clear_codes_confirm_title)
            .setMessage(R.string.clear_codes_confirm_message)
            .setPositiveButton(R.string.clear_codes) { _, _ -> clearCodes() }
            .setNegativeButton(R.string.cancel, null)
            .show()
    }

    private fun clearCodes() {
        val session = ObdManager.session ?: return
        setLoading(true)

        viewLifecycleOwner.lifecycleScope.launch {
            val success = session.clearDtcs()
            setLoading(false)
            val message = if (success) R.string.dtcs_cleared else R.string.dtcs_clear_failed
            Toast.makeText(requireContext(), message, Toast.LENGTH_SHORT).show()
            if (success) {
                readCodes()
            }
        }
    }

    private fun setLoading(loading: Boolean) {
        _binding?.progressDtc?.visibility = if (loading) View.VISIBLE else View.GONE
        _binding?.buttonReadCodes?.isEnabled = !loading
        _binding?.buttonClearCodes?.isEnabled = !loading
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
