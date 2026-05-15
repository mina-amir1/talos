<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ComponentService
{
    protected string $basePath;

    public function __construct()
    {
        $this->basePath = config('talos.schema_path') . '/components';
    }

    public function all(): array
    {
        $components = [];

        if (! File::isDirectory($this->basePath)) {
            return $components;
        }

        foreach (File::directories($this->basePath) as $categoryDir) {
            $category = basename($categoryDir);
            foreach (File::directories($categoryDir) as $compDir) {
                $schemaFile = $compDir . '/schema.json';
                if (File::exists($schemaFile)) {
                    $schema = json_decode(File::get($schemaFile), true);
                    $schema['__uid'] = $category . '.' . basename($compDir);
                    $schema['__category'] = $category;
                    $components[] = $schema;
                }
            }
        }

        return $components;
    }

    public function find(string $uid): ?array
    {
        [$category, $name] = $this->parseUid($uid);
        $schemaFile = $this->basePath . '/' . $category . '/' . $name . '/schema.json';

        if (! File::exists($schemaFile)) {
            return null;
        }

        $schema = json_decode(File::get($schemaFile), true);
        $schema['__uid']      = $uid;
        $schema['__category'] = $category;

        return $schema;
    }

    public function create(array $data): array
    {
        $category = Str::slug($data['category'] ?? 'shared', '_');
        $name     = Str::slug($data['info']['displayName'], '_');
        $uid      = $category . '.' . $name;

        $schema = [
            'collectionName' => 'components_' . $category . '_' . $name . 's',
            'info' => [
                'displayName' => $data['info']['displayName'],
                'description' => $data['info']['description'] ?? '',
                'icon'        => $data['info']['icon'] ?? 'puzzle-piece',
            ],
            'options'    => [],
            'attributes' => $data['attributes'] ?? [],
        ];

        $dir = $this->basePath . '/' . $category . '/' . $name;
        File::ensureDirectoryExists($dir);
        File::put($dir . '/schema.json', json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $schema['__uid']      = $uid;
        $schema['__category'] = $category;

        return $schema;
    }

    public function update(string $uid, array $data): array
    {
        [$category, $name] = $this->parseUid($uid);
        $dir       = $this->basePath . '/' . $category . '/' . $name;
        $schemaFile = $dir . '/schema.json';

        $schema = json_decode(File::get($schemaFile), true);
        $schema['attributes'] = $data['attributes'] ?? $schema['attributes'];
        $schema['info']       = array_merge($schema['info'], $data['info'] ?? []);

        File::put($schemaFile, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $schema['__uid']      = $uid;
        $schema['__category'] = $category;

        return $schema;
    }

    public function delete(string $uid): void
    {
        [$category, $name] = $this->parseUid($uid);
        $dir = $this->basePath . '/' . $category . '/' . $name;

        if (File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }
    }

    public function grouped(): array
    {
        $grouped = [];

        foreach ($this->all() as $component) {
            $grouped[$component['__category']][] = $component;
        }

        return $grouped;
    }

    private function parseUid(string $uid): array
    {
        $parts = explode('.', $uid, 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}
