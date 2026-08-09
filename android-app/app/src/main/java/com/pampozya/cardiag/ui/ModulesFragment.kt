package com.pampozya.cardiag.ui

import android.os.Bundle
import android.view.View
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.pampozya.cardiag.R
import com.pampozya.cardiag.databinding.FragmentModulesBinding
import com.pampozya.cardiag.obd.ModuleScanner
import com.pampozya.cardiag.obd.ObdManager
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class ModulesFragment : Fragment(R.layout.fragment_modules) {

    private var _binding: FragmentModulesBinding? = null
    private val binding get() = _binding!!

    private val adapter = ModuleAdapter { row -> onModuleClicked(row) }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        _binding = FragmentModulesBinding.bind(view)

        binding.recyclerModules.layoutManager = LinearLayoutManager(requireContext())
        binding.recyclerModules.adapter = adapter

        binding.buttonScanModules.setOnClickListener { scanModules() }
    }

    private fun scanModules() {
        val connection = ObdManager.connection ?: run {
            Toast.makeText(requireContext(), R.string.modules_not_connected, Toast.LENGTH_SHORT).show()
            return
        }
        val scanner = ModuleScanner(connection)

        setScanning(true)
        adapter.submitList(emptyList())

        viewLifecycleOwner.lifecycleScope.launch {
            withContext(Dispatchers.IO) {
                scanner.scanAll { probe ->
                    lifecycleScope.launch {
                        _binding?.let {
                            it.textScanStatus.text = getString(
                                R.string.modules_scanning,
                                String.format("%03X", probe.candidate.requestId)
                            )
                            adapter.upsert(ModuleRow(probe))
                        }
                    }
                }
            }
            _binding?.let {
                setScanning(false)
                it.textScanStatus.text = getString(R.string.modules_scan_done)
            }
        }
    }

    private fun onModuleClicked(row: ModuleRow) {
        if (!row.probe.responded) {
            Toast.makeText(requireContext(), R.string.modules_tap_offline, Toast.LENGTH_SHORT).show()
            return
        }
        val connection = ObdManager.connection ?: return
        val scanner = ModuleScanner(connection)

        binding.textScanStatus.text = getString(
            R.string.modules_reading_dtcs,
            row.probe.candidate.name
        )

        viewLifecycleOwner.lifecycleScope.launch {
            val result = withContext(Dispatchers.IO) {
                scanner.readModuleDtcs(row.probe.candidate)
            }
            val detail = if (result.codes.isEmpty()) {
                getString(R.string.modules_no_codes)
            } else {
                result.codes.joinToString("\n")
            }
            _binding?.let {
                adapter.upsert(ModuleRow(row.probe, detail))
                it.textScanStatus.text = getString(R.string.modules_scan_done)
            }
        }
    }

    private fun setScanning(scanning: Boolean) {
        _binding?.let {
            it.progressModules.visibility = if (scanning) View.VISIBLE else View.GONE
            it.buttonScanModules.isEnabled = !scanning
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
