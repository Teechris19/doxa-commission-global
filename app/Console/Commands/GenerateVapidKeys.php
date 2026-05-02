<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\Utils;

class GenerateVapidKeys extends Command
{
    protected $signature = 'webpush:vapid';
    protected $description = 'Generate VAPID keys for push notifications';

    public function handle(): int
    {
        $keys = $this->generateVapidKeys();

        $this->info('VAPID Keys Generated');
        $this->line('');
        $this->comment('Add these to your .env file:');
        $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->line('VAPID_SUBJECT="mailto:admin@doxa.org"');

        return Command::SUCCESS;
    }

    private function generateVapidKeys(): array
    {
        $privateKey = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_bits' => 256,
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        openssl_pkey_export($privateKey, $privateKeyPem);
        $details = openssl_pkey_get_details($privateKey);

        $publicKey = $this->base64Encode($details['key']);
        $privateKey = $this->base64Encode($details['ec']['d']);

        return [
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
        ];
    }

    private function base64Encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
