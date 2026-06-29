<?php

namespace App\Services;

use App\Models\TalosSmtpSetting;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class SmtpService
{
    public function settings(): ?TalosSmtpSetting
    {
        return TalosSmtpSetting::first();
    }

    public function configure(): bool
    {
        $cfg = $this->settings();

        if (! $cfg || ! $cfg->is_active || ! $cfg->host || ! $cfg->from_email) {
            return false;
        }

        config([
            'mail.default'                           => 'smtp',
            'mail.mailers.smtp.host'                 => $cfg->host,
            'mail.mailers.smtp.port'                 => $cfg->port,
            'mail.mailers.smtp.encryption'           => $cfg->encryption === 'none' ? null : $cfg->encryption,
            'mail.mailers.smtp.username'             => $cfg->username,
            'mail.mailers.smtp.password'             => $cfg->password,
            'mail.from.address'                      => $cfg->from_email,
            'mail.from.name'                         => $cfg->from_name,
        ]);

        return true;
    }

    public function testConnection(array $params): array
    {
        $host       = $params['host'] ?? '';
        $port       = (int) ($params['port'] ?? 587);
        $encryption = $params['encryption'] ?? 'tls';

        if (! $host) {
            return ['ok' => false, 'error' => 'Host is required.'];
        }

        try {
            // ssl  = implicit TLS from the start (port 465, ssl:// socket)
            // tls  = STARTTLS: plain socket first, TLS negotiated after EHLO (port 587)
            // none = no encryption
            $tls = ($encryption === 'ssl');

            $transport = new EsmtpTransport($host, $port, $tls);
            $transport->setUsername($params['username'] ?? '');
            $transport->setPassword($params['password'] ?? '');

            $transport->start();

            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
