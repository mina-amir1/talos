<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class DynamicModelService
{
    /**
     * Return a fully-configured Eloquent Model instance for the given content type UID.
     *
     * We generate a uniquely-named class at runtime so that Eloquent's
     * internal mechanisms (cloning, new instances without arguments) work correctly.
     */
    public function make(string $uid): Model
    {
        $className = $this->className($uid);

        if (! class_exists($className, false)) {
            $schema = app(ContentTypeService::class)->find($uid);

            if (! $schema) {
                abort(404, "Content type [{$uid}] not found.");
            }

            $table  = addslashes($schema['collectionName']);
            $casts  = $this->buildCasts($schema['attributes'] ?? []);
            if ($schema['options']['draftAndPublish'] ?? false) {
                $casts['published_at'] = 'datetime';
            }
            $castsExpr = var_export($casts, true);
            $draftable = ($schema['options']['draftAndPublish'] ?? false) ? 'true' : 'false';

            // phpcs:ignore
            eval("
                class {$className} extends \\Illuminate\\Database\\Eloquent\\Model {
                    protected \$table    = '{$table}';
                    protected \$guarded  = ['id'];
                    protected \$casts    = {$castsExpr};

                    public function scopeDraft(\$q)     { return \$q->whereNull('published_at'); }
                    public function scopePublished(\$q) { return \$q->whereNotNull('published_at'); }
                }
            ");
        }

        return new $className;
    }

    private function className(string $uid): string
    {
        return 'TalosModel_' . preg_replace('/[^A-Za-z0-9]/', '_', $uid);
    }

    private function buildCasts(array $attributes): array
    {
        $casts = [];

        foreach ($attributes as $name => $field) {
            $casts[$name] = match ($field['type'] ?? '') {
                'boolean'                                    => 'boolean',
                'integer', 'biginteger'                     => 'integer',
                'float'                                      => 'float',
                'decimal'                                    => 'decimal:2',
                'json', 'component', 'dynamiczone', 'media', 'repeater' => 'array',
                'relation' => in_array($field['relation'] ?? 'manyToOne', ['oneToMany', 'manyToMany']) ? 'array' : 'integer',
                'date'                                       => 'date',
                'datetime'                                   => 'datetime',
                default                                      => 'string',
            };

            if (($field['type'] ?? '') === 'enumeration' && !empty($field['multiple'])) {
                $casts[$name] = 'array';
            }
        }

        return $casts;
    }
}
