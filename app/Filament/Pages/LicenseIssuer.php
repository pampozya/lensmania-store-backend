<?php

namespace App\Filament\Pages;

use App\Services\LicenseIssuerService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

final class LicenseIssuer extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Issue License';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 35;

    protected static ?string $title = 'Issue License';

    protected static string $view = 'filament.pages.license-issuer';

    public string $email = '';
    public string $kind = 'trial';
    public bool $createUser = true;
    public string $name = '';
    public string $platform = 'mac-arm64';
    public string $app = 'premiere';
    public array $result = [];

    public function submit(LicenseIssuerService $issuer): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'kind' => ['required', 'in:trial,paid,full'],
            'name' => ['nullable', 'string', 'max:255'],
            'platform' => ['required', 'string', 'max:100'],
            'app' => ['required', 'string', 'max:100'],
        ]);

        try {
            $this->result = $issuer->issue([
                'email' => $this->email,
                'kind' => $this->kind,
                'create_user' => $this->createUser,
                'name' => $this->name,
                'product' => 'cinecut',
                'platform' => $this->platform,
                'app' => $this->app,
            ]);

            Notification::make()
                ->title($this->result['created'] ? 'License issued' : 'Existing license found')
                ->body(($this->result['kind'] ?? 'license') . ' license for ' . ($this->result['email'] ?? $this->email))
                ->success()
                ->send();
        } catch (ValidationException $exception) {
            $this->result = [];
            $this->setErrorBag($exception->validator->errors());

            Notification::make()
                ->title('License could not be issued')
                ->body($this->firstValidationMessage($exception))
                ->danger()
                ->send();
        }
    }

    private function firstValidationMessage(ValidationException $exception): string
    {
        foreach ($exception->errors() as $messages) {
            if (is_array($messages) && isset($messages[0])) {
                return (string) $messages[0];
            }
        }

        return $exception->getMessage();
    }
}
