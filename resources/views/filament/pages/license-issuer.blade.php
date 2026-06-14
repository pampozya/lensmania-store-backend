<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <form wire:submit="submit" class="space-y-5">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Issue a CineCut license</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Use this page to create a trial or full paid license without using the terminal.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-gray-950 dark:text-white" for="issuer-email">Customer email</label>
                        <input
                            id="issuer-email"
                            type="email"
                            wire:model.defer="email"
                            class="mt-2 block w-full rounded-lg border-gray-300 bg-white text-gray-950 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-950 dark:text-white"
                            placeholder="customer@email.com"
                        />
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-950 dark:text-white" for="issuer-name">Customer name</label>
                        <input
                            id="issuer-name"
                            type="text"
                            wire:model.defer="name"
                            class="mt-2 block w-full rounded-lg border-gray-300 bg-white text-gray-950 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-950 dark:text-white"
                            placeholder="Optional when user already exists"
                        />
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="text-sm font-medium text-gray-950 dark:text-white" for="issuer-kind">License type</label>
                        <select
                            id="issuer-kind"
                            wire:model.defer="kind"
                            class="mt-2 block w-full rounded-lg border-gray-300 bg-white text-gray-950 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-950 dark:text-white"
                        >
                            <option value="trial">Trial</option>
                            <option value="paid">Paid</option>
                        </select>
                        @error('kind')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-950 dark:text-white" for="issuer-platform">Platform</label>
                        <select
                            id="issuer-platform"
                            wire:model.defer="platform"
                            class="mt-2 block w-full rounded-lg border-gray-300 bg-white text-gray-950 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-950 dark:text-white"
                        >
                            <option value="mac-arm64">Mac Apple Silicon</option>
                            <option value="mac-x64">Mac Intel</option>
                            <option value="windows-x64">Windows</option>
                        </select>
                        @error('platform')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-950 dark:text-white" for="issuer-app">App</label>
                        <select
                            id="issuer-app"
                            wire:model.defer="app"
                            class="mt-2 block w-full rounded-lg border-gray-300 bg-white text-gray-950 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-950 dark:text-white"
                        >
                            <option value="premiere">Premiere Pro</option>
                            <option value="resolve">DaVinci Resolve</option>
                        </select>
                        @error('app')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <label class="flex items-center gap-3 rounded-lg bg-gray-50 px-3 py-3 text-sm text-gray-700 dark:bg-white/5 dark:text-gray-200">
                    <input
                        type="checkbox"
                        wire:model.defer="createUser"
                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-white/10 dark:bg-gray-950"
                    />
                    Create the customer account if it does not already exist
                </label>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-primary-500"
                    >
                        Generate license
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Latest result</h2>

            @if ($result !== [])
                <div class="mt-4 space-y-3">
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Customer</p>
                        <p class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $result['email'] ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Kind</p>
                        <p class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ strtoupper($result['kind'] ?? '-') }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">License key</p>
                        <p class="mt-1 break-all font-mono text-sm text-gray-950 dark:text-white">{{ $result['license_key'] ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</p>
                        <p class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $result['status'] ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Expires</p>
                        <p class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $result['expires_at'] ?? 'Never' }}</p>
                    </div>
                    @if (! empty($result['order_id']))
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Order ID</p>
                            <p class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $result['order_id'] }}</p>
                        </div>
                    @endif
                </div>
            @else
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">The generated trial or paid license will appear here after submission.</p>
            @endif
        </div>
    </div>
</x-filament-panels::page>
