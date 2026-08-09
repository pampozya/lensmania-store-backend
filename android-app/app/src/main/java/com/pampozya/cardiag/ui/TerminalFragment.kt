package com.pampozya.cardiag.ui

import android.os.Bundle
import android.view.View
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import com.pampozya.cardiag.R
import com.pampozya.cardiag.databinding.FragmentTerminalBinding
import com.pampozya.cardiag.obd.ObdManager
import kotlinx.coroutines.launch

class TerminalFragment : Fragment(R.layout.fragment_terminal) {

    private var _binding: FragmentTerminalBinding? = null
    private val binding get() = _binding!!

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        _binding = FragmentTerminalBinding.bind(view)

        binding.textInitWarning.visibility =
            if (ObdManager.state.value is com.pampozya.cardiag.obd.ConnState.Connected) View.GONE else View.VISIBLE

        binding.buttonSend.setOnClickListener { sendCurrentCommand() }

        binding.chipAtz.setOnClickListener { sendCommand("ATZ") }
        binding.chipAti.setOnClickListener { sendCommand("ATI") }
        binding.chipAtrv.setOnClickListener { sendCommand("ATRV") }
        binding.chip0100.setOnClickListener { sendCommand("0100") }
        binding.chip03.setOnClickListener { sendCommand("03") }
        binding.chip0902.setOnClickListener { sendCommand("0902") }
    }

    private fun sendCurrentCommand() {
        val cmd = binding.editCommand.text?.toString()?.trim().orEmpty()
        if (cmd.isEmpty()) return
        binding.editCommand.setText("")
        sendCommand(cmd)
    }

    private fun sendCommand(cmd: String) {
        val connection = ObdManager.connection
        if (connection == null) {
            appendLog("> $cmd\n(not connected)\n")
            return
        }

        appendLog("> $cmd\n")
        viewLifecycleOwner.lifecycleScope.launch {
            val response = try {
                connection.sendCommand(cmd)
            } catch (e: Exception) {
                "ERROR: ${e.message}"
            }
            appendLog("$response\n\n")
        }
    }

    private fun appendLog(text: String) {
        val binding = _binding ?: return
        binding.textLog.append(text)
        binding.scrollLog.post {
            binding.scrollLog.fullScroll(View.FOCUS_DOWN)
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
