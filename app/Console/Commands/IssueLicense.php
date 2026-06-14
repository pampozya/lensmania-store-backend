<?php

namespace App\Console\Commands;

use App\Services\LicenseIssuerService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class IssueLicense extends Command
{
    protected $signature = 'license:issue
        {email : Customer email}
        {kind : License kind: trial, full, or paid}
        {--create-user : Create the customer account if it does not exist}
        {--name= : Customer name when creating a new account}
        {--product=cinecut : Product slug}
        {--platform=mac-arm64 : Platform metadata}
        {--app=premiere : App metadata}
        {--json : Output machine-readable JSON}';

    protected $description = 'Issue a CineCut trial or full paid license to a customer';

    public function __construct(private LicenseIssuerService $issuer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $result = $this->issuer->issue([
                'email' => (string) $this->argument('email'),
                'kind' => (string) $this->argument('kind'),
                'create_user' => (bool) $this->option('create-user'),
                'name' => (string) ($this->option('name') ?? ''),
                'product' => (string) $this->option('product'),
                'platform' => (string) $this->option('platform'),
                'app' => (string) $this->option('app'),
            ]);
        } catch (ValidationException $exception) {
            return $this->failWith($this->firstValidationMessage($exception));
        }

        $this->printResult($result);

        return self::SUCCESS;
    }
    private function printResult(array $result): void
    {
        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_UNESCAPED_SLASHES));
            return;
        }

        $this->info(($result['created'] ? 'License issued' : 'Existing license found') . ': ' . $result['license_key']);
        $this->line('Email: ' . $result['email']);
        $this->line('Kind: ' . $result['kind']);
        $this->line('Status: ' . $result['status']);
        $this->line('Expires: ' . ($result['expires_at'] ?? 'never'));
        if (array_key_exists('order_id', $result) && $result['order_id']) {
            $this->line('Order ID: ' . $result['order_id']);
        }
    }

    private function failWith(string $message): int
    {
        if ($this->option('json')) {
            $this->line(json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_SLASHES));
        } else {
            $this->error($message);
        }

        return self::FAILURE;
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
