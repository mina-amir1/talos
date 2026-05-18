<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class LocaleService
{
    protected string $path;

    public function __construct()
    {
        $this->path = config('talos.schema_path') . '/settings.json';
    }

    public function all(): array
    {
        return $this->read()['locales'] ?? [config('talos.default_locale', 'en')];
    }

    public function default(): string
    {
        return config('talos.default_locale', 'en');
    }

    public function add(string $locale): void
    {
        $settings = $this->read();
        $locales  = $settings['locales'] ?? [$this->default()];

        if (! in_array($locale, $locales)) {
            $locales[] = $locale;
            // Default locale always first
            usort($locales, fn($a, $b) => $a === $this->default() ? -1 : ($b === $this->default() ? 1 : strcmp($a, $b)));
            $settings['locales'] = $locales;
            $this->write($settings);
        }
    }

    public function remove(string $locale): void
    {
        if ($locale === $this->default()) {
            return; // Cannot remove the default locale
        }

        $settings            = $this->read();
        $settings['locales'] = array_values(array_filter(
            $settings['locales'] ?? [$this->default()],
            fn($l) => $l !== $locale
        ));

        $this->write($settings);
    }

    private function read(): array
    {
        if (! File::exists($this->path)) {
            return ['locales' => [config('talos.default_locale', 'en')]];
        }

        return json_decode(File::get($this->path), true) ?? [];
    }

    private function write(array $settings): void
    {
        File::ensureDirectoryExists(dirname($this->path));
        File::put($this->path, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
