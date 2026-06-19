<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ContentTypeService
{
    protected string $basePath;

    public function __construct()
    {
        $this->basePath = config('talos.schema_path') . '/content-types';
    }

    public function all(): array
    {
        $types = [];

        if (! File::isDirectory($this->basePath)) {
            return $types;
        }

        foreach (File::directories($this->basePath) as $dir) {
            $schemaFile = $dir . '/schema.json';
            if (File::exists($schemaFile)) {
                $schema = json_decode(File::get($schemaFile), true);
                $schema['__uid'] = basename($dir);
                $types[] = $schema;
            }
        }

        return $types;
    }

    public function find(string $uid): ?array
    {
        $schemaFile = $this->basePath . '/' . $uid . '/schema.json';

        if (! File::exists($schemaFile)) {
            return null;
        }

        $schema = json_decode(File::get($schemaFile), true);
        $schema['__uid'] = $uid;

        return $schema;
    }

    public function create(array $data): array
    {
        $singularName = Str::slug($data['info']['singularName'], '_');
        $uid          = 'api.' . $singularName;

        $schema = $this->buildSchema($data, $uid);

        $this->save($uid, $schema);
        app(SchemaGeneratorService::class)->generate($schema, $uid);

        return $schema;
    }

    public function update(string $uid, array $data): array
    {
        $existing = $this->find($uid);

        if (! $existing) {
            abort(404, "Content type [{$uid}] not found.");
        }

        // Explicit rename map sent by the UI — strip before saving to schema file
        $renames = $data['_renames'] ?? [];
        unset($data['_renames']);

        // Merge option flags without clobbering sibling flags
        if (array_key_exists('manualOrder', $data)) {
            $data['options'] = array_merge(
                $existing['options'] ?? [],
                ['manualOrder' => (bool) $data['manualOrder']]
            );
            unset($data['manualOrder']);
        }

        $schema = array_merge($existing, $data);
        unset($schema['__uid']);

        $this->save($uid, $schema);
        app(SchemaGeneratorService::class)->sync($schema, $uid, $renames);

        return $schema;
    }

    public function delete(string $uid): void
    {
        $dir = $this->basePath . '/' . $uid;

        if (File::isDirectory($dir)) {
            app(SchemaGeneratorService::class)->drop($uid);
            File::deleteDirectory($dir);
        }
    }

    public function getTableName(string $uid): string
    {
        $schema = $this->find($uid);

        return $schema['collectionName'] ?? Str::snake(Str::plural(str_replace('api.', '', $uid)));
    }

    private function buildSchema(array $data, string $uid): array
    {
        $singular = Str::slug($data['info']['singularName'], '_');
        $plural   = Str::slug($data['info']['pluralName'] ?? Str::plural($singular), '_');

        return [
            'kind'           => $data['kind'] ?? 'collectionType',
            'collectionName' => $plural,
            'info'           => [
                'singularName' => $singular,
                'pluralName'   => $plural,
                'displayName'  => $data['info']['displayName'],
                'description'  => $data['info']['description'] ?? '',
            ],
            'options' => [
                'draftAndPublish' => $data['options']['draftAndPublish'] ?? false,
                'i18n'            => $data['options']['i18n'] ?? false,
            ],
            'attributes' => $data['attributes'] ?? [],
        ];
    }

    private function save(string $uid, array $schema): void
    {
        $dir = $this->basePath . '/' . $uid;
        File::ensureDirectoryExists($dir);
        File::put($dir . '/schema.json', json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
