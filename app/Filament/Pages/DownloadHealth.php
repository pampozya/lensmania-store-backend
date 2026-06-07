<?php

namespace App\Filament\Pages;

use App\Models\DownloadToken;
use Filament\Pages\Page;

final class DownloadHealth extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = 'Download Health';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $title = 'Download Health';
    protected static string $view = 'filament.pages.analytics-report';

    public function getCards(): array
    {
        return [
            ['label' => 'Issued tokens', 'value' => (string) DownloadToken::query()->count(), 'hint' => 'All time'],
            ['label' => 'Used tokens', 'value' => (string) DownloadToken::query()->whereNotNull('used_at')->count(), 'hint' => 'Streamed once'],
            ['label' => 'Active tokens', 'value' => (string) DownloadToken::query()->whereNull('used_at')->where('expires_at', '>=', now())->count(), 'hint' => 'Still valid'],
            ['label' => 'Expired unused', 'value' => (string) DownloadToken::query()->whereNull('used_at')->where('expires_at', '<', now())->count(), 'hint' => 'May need resend'],
        ];
    }

    public function getSections(): array
    {
        $rows = DownloadToken::query()
            ->with(['user', 'build.product'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (DownloadToken $token): array => [
                $token->user?->email ?? '-',
                $token->build?->product?->name ?? '-',
                $token->build?->platform ?? '-',
                $token->build?->version ?? '-',
                $token->used_at ? 'used' : ($token->expires_at?->isPast() ? 'expired' : 'active'),
                optional($token->expires_at)->format('M j, Y H:i') ?: '-',
                optional($token->used_at)->format('M j, Y H:i') ?: '-',
            ])
            ->all();

        return [[
            'title' => 'Latest download tokens',
            'description' => 'Single-use token status for delivery monitoring.',
            'columns' => ['Customer', 'Product', 'Platform', 'Version', 'Status', 'Expires', 'Used'],
            'rows' => $rows,
        ]];
    }
}
