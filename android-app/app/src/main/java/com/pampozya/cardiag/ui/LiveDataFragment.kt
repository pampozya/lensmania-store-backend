package com.pampozya.cardiag.ui

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import com.pampozya.cardiag.R
import com.pampozya.cardiag.databinding.FragmentLiveDataBinding
import com.pampozya.cardiag.obd.ConnState
import com.pampozya.cardiag.obd.Elm327Session
import com.pampozya.cardiag.obd.ObdManager
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

class LiveDataFragment : Fragment(R.layout.fragment_live_data) {

    private var _binding: FragmentLiveDataBinding? = null
    private val binding get() = _binding!!

    private var vinFetched = false

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        _binding = FragmentLiveDataBinding.bind(view)

        binding.cardRpm.textLabel.text = getString(R.string.label_rpm)
        binding.cardSpeed.textLabel.text = getString(R.string.label_speed)
        binding.cardCoolant.textLabel.text = getString(R.string.label_coolant)
        binding.cardIntake.textLabel.text = getString(R.string.label_intake)
        binding.cardThrottle.textLabel.text = getString(R.string.label_throttle)
        binding.cardEngineLoad.textLabel.text = getString(R.string.label_engine_load)
        binding.cardBattery.textLabel.text = getString(R.string.label_battery)

        resetValues()

        viewLifecycleOwner.lifecycleScope.launch {
            viewLifecycleOwner.repeatOnLifecycle(Lifecycle.State.STARTED) {
                pollLoop()
            }
        }
    }

    private fun resetValues() {
        val placeholder = getString(R.string.value_placeholder)
        binding.cardRpm.textValue.text = placeholder
        binding.cardSpeed.textValue.text = placeholder
        binding.cardCoolant.textValue.text = placeholder
        binding.cardIntake.textValue.text = placeholder
        binding.cardThrottle.textValue.text = placeholder
        binding.cardEngineLoad.textValue.text = placeholder
        binding.cardBattery.textValue.text = placeholder
        binding.textVin.text = placeholder
    }

    private suspend fun pollLoop() {
        val placeholder = getString(R.string.value_placeholder)

        while (true) {
            val session = ObdManager.session
            val connected = ObdManager.state.value is ConnState.Connected

            binding.textDisconnectedNotice.visibility = if (connected) View.GONE else View.VISIBLE

            if (session == null || !connected) {
                resetValues()
                delay(700)
                continue
            }

            if (!vinFetched) {
                fetchVin(session)
            }

            binding.cardRpm.textValue.text = session.rpm()?.toString() ?: placeholder
            binding.cardSpeed.textValue.text = session.speedKmh()?.toString() ?: placeholder
            binding.cardCoolant.textValue.text = session.coolantC()?.toString() ?: placeholder
            binding.cardIntake.textValue.text = session.intakeC()?.toString() ?: placeholder
            binding.cardThrottle.textValue.text = session.throttlePct()?.toString() ?: placeholder
            binding.cardEngineLoad.textValue.text = session.engineLoadPct()?.toString() ?: placeholder
            binding.cardBattery.textValue.text = session.batteryVolts()?.let { "%.1f".format(it) } ?: placeholder

            delay(700)
        }
    }

    private suspend fun fetchVin(session: Elm327Session) {
        val vin = session.vin()
        if (vin != null) {
            binding.textVin.text = vin
            vinFetched = true
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
