<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

final class SystemHealth extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'System Health';

    protected static ?string $navigationGroup = 'System & Security';

    protected static ?int $navigationSort = 40;

    protected static ?string $title = 'System Health';

    protected static string $view = 'filament.pages.system-health';

    public function getRows(): array
    {
        return [
            $this->check('App environment', app()->environment(), app()->environment('production')),
            $this->check('Debug mode', config('app.debug') ? 'enabled' : 'disabled', ! config('app.debug')),
            $this->check('Database', $this->databaseStatus(), $this->databaseStatus() === 'connected'),
            $this->check('Queue connection', config('queue.default'), config('queue.default') !== null),
            $this->check('Session driver', config('session.driver'), config('session.driver') !== null),
            $this->check('Cache store', config('cache.default'), config('cache.default') !== null),
            $this->check('Mail mailer', config('mail.default'), config('mail.default') !== null),
            $this->check('Storage path', storage_path(), is_writable(storage_path())),
            $this->check('Framework cache dir', storage_path('framework/cache'), is_writable(storage_path('framework/cache'))),
            $this->check('Bootstrap cache dir', base_path('bootstrap/cache'), is_writable(base_path('bootstrap/cache'))),
        ];
    }

    private function databaseStatus(): string
    {
        try {
            DB::select('select 1');

            return 'connected';
        } catch (\Throwable $exception) {
            return 'failed: ' . $exception->getMessage();
        }
    }

    private function check(string $name, mixed $value, bool $ok): array
    {
        return [
            'name' => $name,
            'value' => (string) $value,
            'ok' => $ok,
        ];
    }
}
