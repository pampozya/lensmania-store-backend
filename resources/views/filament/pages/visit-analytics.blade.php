<x-filament-panels::page>
    @if ($this->lastUpdated)
        <p class="text-sm text-gray-500 dark:text-gray-400">Last updated: {{ $this->lastUpdated }}</p>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Top locations</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-2">Country</th>
                            <th class="py-2">City</th>
                            <th class="py-2 text-right">Visits</th>
                            <th class="py-2 text-right">Visitors</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @forelse ($this->locations as $row)
                            <tr>
                                <td class="py-2 text-gray-950 dark:text-white">{{ $row['country'] }}</td>
                                <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['city'] }}</td>
                                <td class="py-2 text-right text-gray-950 dark:text-white">{{ $row['visits'] }}</td>
                                <td class="py-2 text-right text-gray-700 dark:text-gray-300">{{ $row['visitors'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-500">No visits tracked yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Device breakdown</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="py-2">Device</th>
                            <th class="py-2">OS</th>
                            <th class="py-2">Browser</th>
                            <th class="py-2 text-right">Visits</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @forelse ($this->devices as $row)
                            <tr>
                                <td class="py-2 text-gray-950 dark:text-white">{{ $row['device'] }}</td>
                                <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['os'] }}</td>
                                <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['browser'] }}</td>
                                <td class="py-2 text-right text-gray-950 dark:text-white">{{ $row['visits'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-gray-500">No visits tracked yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h2 class="text-base font-semibold text-gray-950 dark:text-white">Recent visits</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="py-2">When</th>
                        <th class="py-2">Path</th>
                        <th class="py-2">Location</th>
                        <th class="py-2">Device</th>
                        <th class="py-2">Promo</th>
                        <th class="py-2">Referrer</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($this->recentVisits as $row)
                        <tr>
                            <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['when'] }}</td>
                            <td class="py-2 text-gray-950 dark:text-white">{{ $row['path'] }}</td>
                            <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['location'] }}</td>
                            <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['device'] }}</td>
                            <td class="py-2 text-gray-700 dark:text-gray-300">{{ $row['promo'] }}</td>
                            <td class="py-2 text-gray-500 dark:text-gray-400">{{ $row['referrer'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-center text-gray-500">No visits tracked yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
