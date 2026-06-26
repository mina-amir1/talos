<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContentEntryService
{
    public function __construct(
        private ContentTypeService  $typeService,
        private DynamicModelService $modelService,
    ) {}

    public function processFormData(Request $request, array $attributes): array
    {
        $data = [];

        foreach ($attributes as $name => $field) {
            if ($field['type'] === 'media') {
                $raw         = $request->input($name . '_id');
                $data[$name] = ($field['multiple'] ?? false)
                    ? (is_string($raw) ? json_decode($raw, true) : ($raw ?? []))
                    : $raw;
                continue;
            }

            if ($field['type'] === 'relation') {
                $isMultiple  = in_array($field['relation'] ?? 'manyToOne', ['oneToMany', 'manyToMany']);
                $raw         = $request->input($name);
                $data[$name] = $isMultiple
                    ? array_filter((array) ($raw ?? []), 'is_numeric')
                    : ($raw ?: null);
                continue;
            }

            $value = $request->input($name);

            $data[$name] = match ($field['type']) {
                'boolean'                                      => $request->boolean($name),
                'json', 'component', 'dynamiczone', 'repeater' => (is_string($value) && $value !== '')
                    ? json_decode($value, true)
                    : null,
                default => $value,
            };
        }

        return $data;
    }

    public function loadRelationOptions(array $attributes): array
    {
        $options = [];

        foreach ($attributes as $name => $field) {
            if ($field['type'] !== 'relation' || empty($field['target'])) {
                continue;
            }

            $targetType = $this->typeService->find($field['target']);

            if (! $targetType) {
                continue;
            }

            $targetAttrs = $targetType['attributes'] ?? [];

            $labelField = collect($targetAttrs)
                ->filter(fn($a) => in_array($a['type'] ?? '', ['string', 'text', 'email', 'uid']))
                ->keys()
                ->first()
                ?? collect($targetAttrs)
                    ->filter(fn($a) => in_array($a['type'] ?? '', ['richtext', 'blocks']))
                    ->keys()
                    ->first();

            $options[$name] = [
                'entries'    => $this->modelService->make($field['target'])->newQuery()->latest()->get(),
                'labelField' => $labelField,
                'multiple'   => in_array($field['relation'] ?? 'manyToOne', ['oneToMany', 'manyToMany']),
            ];
        }

        return $options;
    }

    public function makeValidator(array $data, array $attributes): \Illuminate\Contracts\Validation\Validator
    {
        $rules    = [];
        $messages = [];

        foreach ($attributes as $name => $field) {
            $type = $field['type'] ?? '';

            if (in_array($type, ['media', 'relation', 'component', 'dynamiczone', 'richtext', 'json'])) {
                continue;
            }

            if ($type === 'enumeration' && ! empty($field['multiple'])) {
                $rules[$name] = ($field['required'] ?? false) ? 'required|array' : 'nullable|array';

                if (! empty($field['enumValues'])) {
                    $values = array_filter(array_map('trim', explode("\n", $field['enumValues'])));
                    if (! empty($values)) {
                        $rules[$name . '.*']          = 'in:' . implode(',', $values);
                        $messages[$name . '.*.in']    = "The selected {$name} is invalid.";
                    }
                }
                continue;
            }

            if ($type === 'repeater') {
                foreach ($field['subFields'] ?? [] as $subName => $subField) {
                    $key         = "{$name}.*.{$subName}";
                    $subRules    = ($subField['required'] ?? false) ? ['required'] : ['nullable'];
                    array_push($subRules, ...$this->typeRules($subField));
                    $rules[$key] = implode('|', $subRules);

                    if ($subField['required'] ?? false) {
                        $messages["{$key}.required"] = "The \"{$subName}\" field is required in every {$name} row.";
                        $messages["{$key}.in"]       = "The \"{$subName}\" value is not a valid option in every {$name} row.";
                    }
                }
                continue;
            }

            $fieldRules   = ($field['required'] ?? false) ? ['required'] : ['nullable'];
            array_push($fieldRules, ...$this->typeRules($field));
            $rules[$name] = implode('|', $fieldRules);
        }

        return Validator::make($data, $rules, $messages);
    }

    public function findSingleTypeEntry(string $uid, bool $i18n, string $locale): ?object
    {
        $query = $this->modelService->make($uid)->newQuery();

        if ($i18n) {
            $query->where('locale', $locale);
        }

        return $query->first();
    }

    public function uniqueSlug(string $uid, string $base): string
    {
        $model = $this->modelService->make($uid);
        $slug  = $base;
        $n     = 1;

        while ($model->newQuery()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    public function entryCount(array $ids, string $action): string
    {
        $n = count($ids);
        return "{$n} " . ($n === 1 ? 'entry' : 'entries') . " {$action}.";
    }

    public function parseBulkIds(Request $request): array
    {
        $raw = $request->input('ids', '[]');
        $ids = is_array($raw) ? $raw : (json_decode($raw, true) ?? []);

        return array_values(array_filter($ids, 'is_numeric'));
    }

    public function canPublish(?object $user, string $uid): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        $allowed = $user->role?->permissions['content-manager'][$uid] ?? [];

        return in_array('publish', $allowed);
    }

    private function typeRules(array $field): array
    {
        $rules = [];
        $type  = $field['type'] ?? '';

        $typeMap = [
            'email'                       => 'email',
            'url'                         => 'url',
            'boolean'                     => 'boolean',
        ];

        if (isset($typeMap[$type])) {
            $rules[] = $typeMap[$type];
        } elseif (in_array($type, ['integer', 'biginteger'])) {
            $rules[] = 'integer';
        } elseif (in_array($type, ['decimal', 'float'])) {
            $rules[] = 'numeric';
        } elseif (in_array($type, ['date', 'datetime'])) {
            $rules[] = 'date';
        }

        if ($type === 'string' && ! empty($field['maxLength'])) {
            $rules[] = 'max:' . (int) $field['maxLength'];
        }

        if ($type === 'enumeration' && ! empty($field['enumValues'])) {
            $values = array_filter(array_map('trim', explode("\n", $field['enumValues'])));
            if (! empty($values)) {
                $rules[] = 'in:' . implode(',', $values);
            }
        }

        if (in_array($type, ['integer', 'biginteger', 'decimal', 'float'])) {
            if (isset($field['min'])) $rules[] = 'min:' . $field['min'];
            if (isset($field['max'])) $rules[] = 'max:' . $field['max'];
        }

        return $rules;
    }
}
