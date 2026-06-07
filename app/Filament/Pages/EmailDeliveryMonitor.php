<?php

namespace App\Filament\Pages;

use App\Models\EmailDeliveryLog;
use Filament\Pages\Page;

final class EmailDeliveryMonitor extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Email Delivery Monitor';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $title = 'Email Delivery Monitor';
    protected static string $view = 'filament.pages.analytics-report';

    public function getCards(): array
    {
        return [
            ['label' => 'Queued', 'value' => (string) EmailDeliveryLog::query()->where('status', 'queued')->count(), 'hint' => 'Waiting'],
            ['label' => 'Sent', 'value' => (string) EmailDeliveryLog::query()->where('status', 'sent')->count(), 'hint' => 'Provider accepted'],
            ['label' => 'Delivered', 'value' => (string) EmailDeliveryLog::query()->where('status', 'delivered')->count(), 'hint' => 'Provider callback'],
            ['label' => 'Failed', 'value' => (string) EmailDeliveryLog::query()->whereIn('status', ['failed', 'bounced'])->count(), 'hint' => 'Needs review'],
        ];
    }

    public function getSections(): array
    {
        return [[
            'title' => 'Latest email events',
            'description' => 'This table is ready for ESP webhook logging. It will be empty until email events are written.',
            'columns' => ['Email', 'Type', 'Subject', 'Provider', 'Status', 'Sent', 'Error'],
            'rows' => EmailDeliveryLog::query()->latest()->limit(50)->get()->map(fn (EmailDeliveryLog $log): array => [
                $log->email,
                $log->type ?: '-',
                $log->subject ?: '-',
                $log->provider ?: '-',
                $log->status,
                optional($log->sent_at)->format('M j, Y H:i') ?: '-',
                $log->error ? str($log->error)->limit(80) : '-',
            ])->all(),
        ]];
    }
}
