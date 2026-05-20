<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class SchemaGeneratorService
{
    /**
     * Generate (create) a table from a content-type schema.
     */
    public function generate(array $schema, string $uid): void
    {
        $table = $schema['collectionName'];

        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $bp) use ($schema) {
            $bp->id();
            // Slug: collection types only — single types don't have list URLs
            if (($schema['kind'] ?? 'collectionType') === 'collectionType') {
                $bp->string('slug', 255)->nullable()->index();
            }
            $this->addAttributes($bp, $schema['attributes'] ?? []);
            $bp->unsignedBigInteger('created_by')->nullable();
            $bp->unsignedBigInteger('updated_by')->nullable();

            if ($schema['options']['draftAndPublish'] ?? false) {
                $bp->timestamp('published_at')->nullable();
            }

            if ($schema['options']['i18n'] ?? false) {
                $bp->string('locale', 10)->default(config('talos.default_locale'))->index();
                $bp->unsignedBigInteger('localizations_id')->nullable()->index();
            }

            $bp->timestamps();
        });
    }

    /**
     * Sync schema changes (rename / add columns) without recreating the table.
     *
     * @param array $renames  Map of [oldColumnName => newColumnName] detected by ContentTypeService.
     */
    public function sync(array $schema, string $uid, array $renames = []): void
    {
        $table      = $schema['collectionName'];
        $attributes = $schema['attributes'] ?? [];

        if (! Schema::hasTable($table)) {
            $this->generate($schema, $uid);
            return;
        }

        // Step 1: Apply renames first (each in its own call — SQLite requires this).
        foreach ($renames as $oldName => $newName) {
            if (Schema::hasColumn($table, $oldName) && ! Schema::hasColumn($table, $newName)) {
                Schema::table($table, fn (Blueprint $bp) => $bp->renameColumn($oldName, $newName));
            }
        }

        // Step 2: Add genuinely new columns (skip rename targets already handled above).
        $renamedTargets = array_values($renames);

        Schema::table($table, function (Blueprint $bp) use ($table, $attributes, $schema, $renamedTargets) {
            // Add slug for collection types that don't have it yet
            if (($schema['kind'] ?? 'collectionType') === 'collectionType' && ! Schema::hasColumn($table, 'slug')) {
                $bp->string('slug', 255)->nullable()->index();
            }

            foreach ($attributes as $name => $field) {
                if (! Schema::hasColumn($table, $name) && ! in_array($name, $renamedTargets)) {
                    $this->addColumn($bp, $name, array_merge($field, ['required' => false]));
                }
            }

            if (($schema['options']['i18n'] ?? false) && ! Schema::hasColumn($table, 'locale')) {
                $bp->string('locale', 10)->default(config('talos.default_locale'))->index();
                $bp->unsignedBigInteger('localizations_id')->nullable()->index();
            }
        });
    }

    /**
     * Drop the table for a deleted content type.
     */
    public function drop(string $uid): void
    {
        $service = app(ContentTypeService::class);
        $schema  = $service->find($uid);

        if ($schema) {
            Schema::dropIfExists($schema['collectionName']);
        }
    }

    private function addAttributes(Blueprint $bp, array $attributes): void
    {
        foreach ($attributes as $name => $field) {
            $this->addColumn($bp, $name, $field);
        }
    }

    private function addColumn(Blueprint $bp, string $name, array $field): void
    {
        $required = $field['required'] ?? false;
        $type     = $field['type'];

        $col = match ($type) {
            'string', 'email', 'password', 'url', 'uid', 'enumeration'
                => $bp->string($name, $field['maxLength'] ?? 255),

            'text'
                => $bp->text($name),

            'richtext'
                => $bp->longText($name),

            'integer'
                => $bp->integer($name),

            'biginteger'
                => $bp->bigInteger($name),

            'decimal'
                => $bp->decimal($name, $field['precision'] ?? 10, $field['scale'] ?? 2),

            'float'
                => $bp->float($name),

            'boolean'
                => $bp->boolean($name)->default($field['default'] ?? false),

            'date'
                => $bp->date($name),

            'datetime'
                => $bp->dateTime($name),

            'time'
                => $bp->time($name),

            'json', 'component', 'dynamiczone', 'media', 'repeater'
                => $bp->json($name),

            'relation' => in_array($field['relation'] ?? 'manyToOne', ['oneToMany', 'manyToMany'])
                ? $bp->json($name)
                : $bp->unsignedBigInteger($name),

            default
                => $bp->string($name, 255),
        };

        if (! $required && $type !== 'boolean') {
            $col->nullable();
        }

        if (isset($field['unique']) && $field['unique']) {
            $col->unique();
        }

        if (isset($field['default']) && $type !== 'boolean') {
            $col->default($field['default']);
        }
    }
}
