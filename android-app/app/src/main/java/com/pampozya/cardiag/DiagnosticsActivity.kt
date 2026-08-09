package com.pampozya.cardiag

import android.os.Bundle
import android.view.Menu
import android.view.MenuItem
import androidx.appcompat.app.AppCompatActivity
import androidx.fragment.app.Fragment
import androidx.fragment.app.FragmentActivity
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import androidx.viewpager2.adapter.FragmentStateAdapter
import com.google.android.material.tabs.TabLayoutMediator
import com.pampozya.cardiag.databinding.ActivityDiagnosticsBinding
import com.pampozya.cardiag.obd.ConnState
import com.pampozya.cardiag.obd.ObdManager
import com.pampozya.cardiag.ui.DtcFragment
import com.pampozya.cardiag.ui.LiveDataFragment
import com.pampozya.cardiag.ui.ModulesFragment
import com.pampozya.cardiag.ui.TerminalFragment
import kotlinx.coroutines.launch

class DiagnosticsActivity : AppCompatActivity() {

    private lateinit var binding: ActivityDiagnosticsBinding

    private val tabTitles by lazy {
        listOf(
            getString(R.string.tab_live_data),
            getString(R.string.tab_trouble_codes),
            getString(R.string.tab_modules),
            getString(R.string.tab_terminal)
        )
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityDiagnosticsBinding.inflate(layoutInflater)
        setContentView(binding.root)
        setSupportActionBar(binding.toolbar)

        binding.toolbar.subtitle = ObdManager.deviceName ?: getString(R.string.status_disconnected)

        binding.viewPager.adapter = DiagnosticsPagerAdapter(this)
        TabLayoutMediator(binding.tabLayout, binding.viewPager) { tab, position ->
            tab.text = tabTitles[position]
        }.attach()

        lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                ObdManager.state.collect { state ->
                    updateSubtitle(state)
                    if (state is ConnState.Disconnected) {
                        finish()
                    }
                }
            }
        }
    }

    private fun updateSubtitle(state: ConnState) {
        val deviceName = ObdManager.deviceName ?: getString(R.string.status_disconnected)
        val statusText = when (state) {
            is ConnState.Connected -> getString(R.string.status_connected)
            is ConnState.Connecting -> getString(R.string.status_connecting)
            is ConnState.Disconnected -> getString(R.string.status_disconnected)
            is ConnState.Error -> getString(R.string.status_error, state.message)
        }
        binding.toolbar.subtitle = "$deviceName — $statusText"
    }

    override fun onCreateOptionsMenu(menu: Menu): Boolean {
        menuInflater.inflate(R.menu.diagnostics_menu, menu)
        return true
    }

    override fun onOptionsItemSelected(item: MenuItem): Boolean {
        if (item.itemId == R.id.action_disconnect) {
            lifecycleScope.launch {
                ObdManager.disconnect()
                finish()
            }
            return true
        }
        return super.onOptionsItemSelected(item)
    }

    private class DiagnosticsPagerAdapter(activity: FragmentActivity) : FragmentStateAdapter(activity) {
        override fun getItemCount(): Int = 4

        override fun createFragment(position: Int): Fragment {
            return when (position) {
                0 -> LiveDataFragment()
                1 -> DtcFragment()
                2 -> ModulesFragment()
                else -> TerminalFragment()
            }
        }
    }
}
