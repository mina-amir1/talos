@extends('talos.layouts.app')

@section('title', $contentType['info']['displayName'] . ' — Builder')
@section('header', $contentType['info']['displayName'])

@section('header-actions')
    <span class="text-xs bg-gray-800 text-gray-400 px-2 py-1 rounded">{{ $uid }}</span>
    <a href="{{ route('talos.content.index', ['uid' => $uid]) }}"
       class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition-colors">
        View Content
    </a>
@endsection

@section('content')
@php
    $fieldTypes = [
        'Text'     => [
            ['type' => 'string',   'label' => 'Short text',  'icon' => 'T',  'description' => 'Short string up to 255 chars'],
            ['type' => 'text',     'label' => 'Long text',   'icon' => '¶',  'description' => 'Unlimited plain text'],
            ['type' => 'richtext', 'label' => 'Rich text',   'icon' => 'RT', 'description' => 'HTML rich text editor'],
        ],
        'Number'   => [
            ['type' => 'integer',    'label' => 'Integer',  'icon' => '1',    'description' => 'Whole numbers'],
            ['type' => 'biginteger', 'label' => 'Big int',  'icon' => '1+',   'description' => 'Large whole numbers'],
            ['type' => 'decimal',    'label' => 'Decimal',  'icon' => '1.2',  'description' => 'Fixed decimal precision'],
            ['type' => 'float',      'label' => 'Float',    'icon' => '~1',   'description' => 'Floating point numbers'],
        ],
        'Date'     => [
            ['type' => 'date',     'label' => 'Date',      'icon' => '📅', 'description' => 'Date only'],
            ['type' => 'datetime', 'label' => 'DateTime',  'icon' => '🕐', 'description' => 'Date and time'],
            ['type' => 'time',     'label' => 'Time',      'icon' => '⏱', 'description' => 'Time only'],
        ],
        'Other'    => [
            ['type' => 'boolean',     'label' => 'Boolean',     'icon' => '⊙',  'description' => 'True or false'],
            ['type' => 'email',       'label' => 'Email',       'icon' => '@',  'description' => 'Email address'],
            ['type' => 'url',         'label' => 'URL',         'icon' => '🔗', 'description' => 'Web address'],
            ['type' => 'uid',         'label' => 'UID / Slug',  'icon' => '#',  'description' => 'Auto-generated slug'],
            ['type' => 'json',        'label' => 'JSON',        'icon' => '{}', 'description' => 'Raw JSON object'],
            ['type' => 'enumeration', 'label' => 'Enumeration', 'icon' => '≡',  'description' => 'Predefined options list'],
        ],
        'Media'    => [
            ['type' => 'media', 'label' => 'Media', 'icon' => '🖼', 'description' => 'File or image from media library'],
        ],
        'Relational' => [
            ['type' => 'relation',  'label' => 'Relation',    'icon' => '⟷',  'description' => 'Link to another content type'],
        ],
        'Components' => [
            ['type' => 'component',   'label' => 'Component',    'icon' => '⬡',  'description' => 'Embed a reusable component'],
            ['type' => 'dynamiczone', 'label' => 'Dynamic Zone', 'icon' => '⬡+', 'description' => 'List of selectable components'],
        ],
        'Advanced' => [
            ['type' => 'repeater', 'label' => 'Repeater', 'icon' => '≣', 'description' => 'Repeatable list of structured items'],
        ],
    ];
@endphp

<div x-data="fieldBuilder({{ json_encode($contentType['attributes'] ?? []) }}, '{{ $uid }}')"
     class="flex gap-6 h-[calc(100vh-120px)]">

    {{-- ── Left: current fields ────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
            <h2 class="text-white font-semibold">Fields <span class="text-gray-500 text-sm font-normal" x-text="'(' + fields.length + ')'"></span></h2>
            <button @click="showPicker = true"
                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add field
            </button>
        </div>

        {{-- Fields list --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-2">
            <template x-if="fields.length === 0">
                <div class="h-full flex flex-col items-center justify-center text-gray-600 gap-3">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                              d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                    </svg>
                    <p class="text-sm">No fields yet. Click "Add field" to start.</p>
                </div>
            </template>

            <template x-for="(field, index) in fields" :key="field.name">
                <div class="flex items-center gap-3 p-3 bg-gray-800 rounded-lg border border-transparent hover:border-gray-700 group">
                    <div class="w-9 h-9 bg-gray-700 rounded-lg flex items-center justify-center text-xs font-mono text-gray-300 flex-shrink-0"
                         x-text="getTypeIcon(field.type)"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white" x-text="field.name"></p>
                        <p class="text-xs text-gray-500" x-text="field.type + (field.required ? ' · required' : '') + (field.unique ? ' · unique' : '')"></p>
                    </div>
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="editField(index)"
                                class="p-1.5 text-gray-500 hover:text-white hover:bg-gray-700 rounded transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                        <button @click="removeField(index)"
                                class="p-1.5 text-gray-500 hover:text-red-400 hover:bg-gray-700 rounded transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Save --}}
        <div class="px-5 py-4 border-t border-gray-800 flex items-center justify-between">
            <p class="text-xs text-gray-600">Changes are saved to the schema file</p>
            <button @click="save()"
                    :disabled="saving"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                <svg x-show="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="saving ? 'Saving…' : 'Save schema'"></span>
            </button>
        </div>
    </div>

    {{-- ── Right: field picker / config ────────────────────────────── --}}
    <div class="w-96 flex flex-col">

        {{-- Field type picker --}}
        <div x-show="showPicker && !editingField" class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden flex flex-col h-full">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
                <h2 class="text-white font-semibold">Choose a field type</h2>
                <button @click="showPicker = false" class="text-gray-500 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                @foreach($fieldTypes as $category => $types)
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $category }}</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($types as $ft)
                                <button @click="selectType('{{ $ft['type'] }}')"
                                        class="flex items-center gap-2 p-3 bg-gray-800 hover:bg-gray-700 rounded-lg text-left transition-colors group">
                                    <span class="w-8 h-8 bg-gray-700 group-hover:bg-gray-600 rounded flex items-center justify-center text-xs text-gray-300 font-mono flex-shrink-0">
                                        {{ $ft['icon'] }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-white">{{ $ft['label'] }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ $ft['description'] }}</p>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Field configuration form --}}
        <div x-show="editingField" class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden flex flex-col h-full">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
                <div>
                    <h2 class="text-white font-semibold" x-text="editingIndex !== null ? 'Edit field' : 'Configure field'"></h2>
                    <p class="text-xs text-gray-500" x-text="editingField?.type"></p>
                </div>
                <button @click="cancelEdit()" class="text-gray-500 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-5 space-y-4">

                {{-- Name --}}
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Field name <span class="text-red-400">*</span></label>
                    <input type="text" x-model="editingField.name" placeholder="e.g. title"
                           pattern="[a-z0-9_]+"
                           class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm
                                  focus:outline-none focus:border-blue-500 font-mono"
                           @input="editingField.name = $el.value.toLowerCase().replace(/[^a-z0-9_]/g,'')">
                    <p class="text-xs text-gray-600 mt-1">lowercase, underscores allowed</p>
                </div>

                {{-- String-specific --}}
                <template x-if="editingField.type === 'string' || editingField.type === 'uid'">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1.5">Max length</label>
                        <input type="number" x-model.number="editingField.maxLength" min="1" max="65535"
                               class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                    </div>
                </template>

                {{-- Enumeration values --}}
                <template x-if="editingField.type === 'enumeration'">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1.5">Values (one per line)</label>
                        <textarea x-model="editingField.enumValues" rows="4"
                                  placeholder="draft&#10;published&#10;archived"
                                  class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm
                                         focus:outline-none focus:border-blue-500 font-mono resize-none"></textarea>
                    </div>
                </template>

                {{-- Component picker --}}
                <template x-if="editingField.type === 'component' || editingField.type === 'dynamiczone'">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1.5">
                            <span x-text="editingField.type === 'dynamiczone' ? 'Allowed components' : 'Component'"></span>
                        </label>
                        @php $allComponents = $components ?? []; @endphp
                        @if(count($allComponents) > 0)
                            @foreach($allComponents as $cat => $catComponents)
                                <p class="text-xs text-gray-600 mb-1">{{ $cat }}</p>
                                @foreach($catComponents as $comp)
                                    <label class="flex items-center gap-2 py-1.5 cursor-pointer">
                                        <input type="checkbox"
                                               :checked="(editingField.components || []).includes('{{ $comp['__uid'] }}')"
                                               @change="toggleComponent('{{ $comp['__uid'] }}')"
                                               class="rounded bg-gray-800 border-gray-700 text-blue-600 focus:ring-0">
                                        <span class="text-sm text-gray-300">{{ $comp['info']['displayName'] }}</span>
                                    </label>
                                @endforeach
                            @endforeach
                        @else
                            <p class="text-xs text-gray-500">No components created yet.
                                <a href="{{ route('talos.components.create') }}" class="text-blue-400 hover:underline">Create one</a>
                            </p>
                        @endif

                        <template x-if="editingField.type === 'component'">
                            <div class="mt-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="editingField.repeatable"
                                           class="rounded bg-gray-800 border-gray-700 text-blue-600 focus:ring-0">
                                    <span class="text-sm text-gray-300">Repeatable</span>
                                </label>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Relation --}}
                <template x-if="editingField.type === 'relation'">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1.5">Relation type</label>
                            <select x-model="editingField.relation"
                                    class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                <option value="manyToOne">Many to One — pick one entry</option>
                                <option value="oneToOne">One to One — pick one entry</option>
                                <option value="oneToMany">One to Many — pick multiple entries</option>
                                <option value="manyToMany">Many to Many — pick multiple entries</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1.5">Target content type</label>
                            <select x-model="editingField.target"
                                    class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                                <option value="">— Select —</option>
                                @foreach($contentTypes as $ct)
                                    <option value="{{ $ct['__uid'] }}">{{ $ct['info']['displayName'] }} ({{ $ct['__uid'] }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </template>

                {{-- Media --}}
                <template x-if="editingField.type === 'media'">
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="editingField.multiple"
                                   class="rounded bg-gray-800 border-gray-700 text-blue-600 focus:ring-0">
                            <span class="text-sm text-gray-300">Allow multiple files</span>
                        </label>
                    </div>
                </template>

                {{-- Number defaults --}}
                <template x-if="['integer','biginteger','decimal','float'].includes(editingField?.type)">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1.5">Min</label>
                            <input type="number" x-model.number="editingField.min"
                                   class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1.5">Max</label>
                            <input type="number" x-model.number="editingField.max"
                                   class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                </template>

                {{-- Repeater sub-fields --}}
                <template x-if="editingField.type === 'repeater'">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Sub-fields</label>

                            <div class="space-y-1.5 mb-3">
                                <template x-for="sf in getSubFieldArray()" :key="sf.name">
                                    <div class="flex items-center gap-2 px-3 py-2 bg-gray-800 rounded-lg">
                                        <span class="text-xs font-mono text-white flex-1" x-text="sf.name"></span>
                                        <span class="text-xs text-gray-500 bg-gray-700 px-1.5 py-0.5 rounded" x-text="sf.type"></span>
                                        <button type="button" @click="removeSubField(sf.name)"
                                                class="text-gray-600 hover:text-red-400 transition-colors flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <template x-if="getSubFieldArray().length === 0">
                                    <p class="text-xs text-gray-600 italic">No sub-fields yet.</p>
                                </template>
                            </div>

                            <div class="space-y-2 p-3 bg-gray-800 rounded-lg border border-gray-700">
                                <div class="flex gap-2">
                                    <input type="text" x-model="newSubFieldName"
                                           placeholder="field_name"
                                           @keydown.enter.prevent="addSubField()"
                                           @input="newSubFieldName = $el.value.toLowerCase().replace(/[^a-z0-9_]/g,'')"
                                           class="flex-1 px-2.5 py-1.5 bg-gray-900 border border-gray-600 rounded text-white text-xs font-mono focus:outline-none focus:border-blue-500">
                                    <select x-model="newSubFieldType" @change="newSubFieldConfig = {}"
                                            class="px-2 py-1.5 bg-gray-900 border border-gray-600 rounded text-white text-xs focus:outline-none focus:border-blue-500">
                                        <option value="string">String</option>
                                        <option value="text">Text</option>
                                        <option value="integer">Integer</option>
                                        <option value="decimal">Decimal</option>
                                        <option value="float">Float</option>
                                        <option value="boolean">Boolean</option>
                                        <option value="email">Email</option>
                                        <option value="url">URL</option>
                                        <option value="date">Date</option>
                                        <option value="datetime">DateTime</option>
                                        <option value="enumeration">Enumeration</option>
                                    </select>
                                </div>

                                <template x-if="newSubFieldType === 'enumeration'">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Values (one per line)</label>
                                        <textarea x-model="newSubFieldConfig.enumValues" rows="3"
                                                  placeholder="option_a&#10;option_b&#10;option_c"
                                                  class="w-full px-2.5 py-1.5 bg-gray-900 border border-gray-600 rounded text-white text-xs font-mono focus:outline-none focus:border-blue-500 resize-none"></textarea>
                                    </div>
                                </template>

                                <template x-if="['string','email','url'].includes(newSubFieldType)">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Max length</label>
                                        <input type="number" x-model.number="newSubFieldConfig.maxLength" min="1" max="65535" placeholder="255"
                                               class="w-full px-2.5 py-1.5 bg-gray-900 border border-gray-600 rounded text-white text-xs focus:outline-none focus:border-blue-500">
                                    </div>
                                </template>

                                <template x-if="['integer','decimal','float'].includes(newSubFieldType)">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Min</label>
                                            <input type="number" x-model.number="newSubFieldConfig.min"
                                                   class="w-full px-2.5 py-1.5 bg-gray-900 border border-gray-600 rounded text-white text-xs focus:outline-none focus:border-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Max</label>
                                            <input type="number" x-model.number="newSubFieldConfig.max"
                                                   class="w-full px-2.5 py-1.5 bg-gray-900 border border-gray-600 rounded text-white text-xs focus:outline-none focus:border-blue-500">
                                        </div>
                                    </div>
                                </template>

                                <template x-if="newSubFieldType === 'boolean'">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" x-model="newSubFieldConfig.default"
                                               class="rounded bg-gray-900 border-gray-600 text-blue-600 focus:ring-0">
                                        <span class="text-xs text-gray-400">Default value: True</span>
                                    </label>
                                </template>

                                <button type="button" @click="addSubField()" :disabled="!newSubFieldName"
                                        class="w-full py-1.5 bg-blue-600 hover:bg-blue-500 disabled:opacity-40 text-white rounded text-xs font-medium transition-colors">
                                    Add sub-field
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Default value --}}
                <template x-if="!['component','dynamiczone','media','relation','richtext','repeater'].includes(editingField?.type)">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1.5">Default value</label>
                        <input type="text" x-model="editingField.default"
                               class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500">
                    </div>
                </template>

                {{-- Required / Unique / Private --}}
                <div class="space-y-2 pt-2 border-t border-gray-800">
                    <label class="flex items-center justify-between p-3 bg-gray-800 rounded-lg cursor-pointer">
                        <div>
                            <p class="text-sm text-white">Required</p>
                            <p class="text-xs text-gray-500">Field must have a value</p>
                        </div>
                        <input type="checkbox" x-model="editingField.required" class="rounded text-blue-600 focus:ring-0 bg-gray-700 border-gray-600">
                    </label>

                    <template x-if="['string','text','email','url','uid','integer','biginteger','decimal','float'].includes(editingField?.type)">
                        <label class="flex items-center justify-between p-3 bg-gray-800 rounded-lg cursor-pointer">
                            <div>
                                <p class="text-sm text-white">Unique</p>
                                <p class="text-xs text-gray-500">No duplicate values allowed</p>
                            </div>
                            <input type="checkbox" x-model="editingField.unique" class="rounded text-blue-600 focus:ring-0 bg-gray-700 border-gray-600">
                        </label>
                    </template>

                    <label class="flex items-center justify-between p-3 bg-gray-800 rounded-lg cursor-pointer">
                        <div>
                            <p class="text-sm text-white">Private</p>
                            <p class="text-xs text-gray-500">Not exposed in the API</p>
                        </div>
                        <input type="checkbox" x-model="editingField.private" class="rounded text-blue-600 focus:ring-0 bg-gray-700 border-gray-600">
                    </label>
                </div>
            </div>

            <div class="px-5 py-4 border-t border-gray-800 flex gap-3">
                <button @click="cancelEdit()"
                        class="flex-1 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition-colors">
                    Cancel
                </button>
                <button @click="addOrUpdateField()"
                        :disabled="!editingField.name"
                        class="flex-1 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white rounded-lg text-sm font-semibold transition-colors"
                        x-text="editingIndex !== null ? 'Update field' : 'Add field'">
                </button>
            </div>
        </div>

        {{-- Empty state when nothing is open --}}
        <div x-show="!showPicker && !editingField"
             class="flex-1 bg-gray-900 border border-gray-800 rounded-xl flex flex-col items-center justify-center gap-4 text-gray-600 p-8">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                      d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
            </svg>
            <p class="text-sm text-center">Click <strong class="text-gray-400">Add field</strong> to pick a field type and configure it.</p>
        </div>
    </div>
</div>

<script>
function fieldBuilder(initialAttributes, uid) {
    const typeIcons = {
        string: 'T', text: '¶', richtext: 'RT',
        integer: '1', biginteger: '1+', decimal: '1.2', float: '~1',
        date: '📅', datetime: '🕐', time: '⏱',
        boolean: '⊙', email: '@', url: '🔗', uid: '#', json: '{}', enumeration: '≡',
        media: '🖼', relation: '⟷', component: '⬡', dynamiczone: '⬡+', repeater: '≣',
    };

    // Convert the attributes object into an array for easier manipulation
    const toArray = (attrs) => Object.entries(attrs || {}).map(([name, field]) => ({ name, ...field }));
    const toObject = (arr) => arr.reduce((obj, f) => {
        const { name, ...rest } = f;
        obj[name] = rest;
        return obj;
    }, {});

    return {
        fields: toArray(initialAttributes),
        showPicker: false,
        editingField: null,
        editingIndex: null,
        saving: false,
        renames: {},   // { dbColumnName: newColumnName } — built up before each save
        newSubFieldName: '',
        newSubFieldType: 'string',
        newSubFieldConfig: {},

        getTypeIcon(type) { return typeIcons[type] ?? '?'; },

        selectType(type) {
            this.editingField = { type, name: '', required: false, unique: false, private: false };
            if (type === 'repeater') this.editingField.subFields = {};
            if (type === 'relation') { this.editingField.relation = 'manyToOne'; this.editingField.target = ''; }
            this.editingIndex = null;
            this.showPicker   = false;
        },

        editField(index) {
            this.editingField = { ...this.fields[index], _originalName: this.fields[index].name };
            this.editingIndex = index;
            this.showPicker   = false;
        },

        removeField(index) {
            if (confirm('Remove this field?')) {
                this.fields.splice(index, 1);
            }
        },

        cancelEdit() {
            this.editingField = null;
            this.editingIndex = null;
        },

        toggleComponent(compUid) {
            if (!this.editingField.components) this.editingField.components = [];
            const idx = this.editingField.components.indexOf(compUid);
            if (idx === -1) {
                this.editingField.components.push(compUid);
            } else {
                this.editingField.components.splice(idx, 1);
            }
        },

        addOrUpdateField() {
            if (!this.editingField.name) return;

            const duplicate = this.fields.some((f, i) => f.name === this.editingField.name && i !== this.editingIndex);
            if (duplicate) {
                alert('A field with this name already exists.');
                return;
            }

            if (this.editingIndex !== null) {
                const originalName = this.editingField._originalName;
                const newName      = this.editingField.name;

                if (originalName && originalName !== newName) {
                    // If this field was already renamed earlier in this session (A→B now renamed to C),
                    // find the true DB original (A) and update the mapping to A→C.
                    const trueOriginal = Object.keys(this.renames).find(k => this.renames[k] === originalName) ?? originalName;

                    if (trueOriginal !== originalName) {
                        delete this.renames[trueOriginal]; // drop intermediate A→B
                    }

                    if (trueOriginal === newName) {
                        delete this.renames[trueOriginal]; // rename undone — no DB op needed
                    } else {
                        this.renames[trueOriginal] = newName;
                    }
                }

                const saved = { ...this.editingField };
                delete saved._originalName;
                this.fields[this.editingIndex] = saved;
            } else {
                this.fields.push({ ...this.editingField });
            }

            this.cancelEdit();
        },

        // ── Repeater sub-field helpers ──
        getSubFieldArray() {
            if (!this.editingField?.subFields) return [];
            return Object.entries(this.editingField.subFields).map(([name, f]) => ({ name, ...f }));
        },

        addSubField() {
            const n = this.newSubFieldName.trim();
            if (!n) return;
            if (!this.editingField.subFields) this.editingField.subFields = {};
            if (this.editingField.subFields[n]) { alert('Sub-field "' + n + '" already exists.'); return; }
            const fieldDef = { type: this.newSubFieldType, ...this.newSubFieldConfig };
            this.editingField.subFields = { ...this.editingField.subFields, [n]: fieldDef };
            this.newSubFieldName = '';
            this.newSubFieldConfig = {};
        },

        removeSubField(name) {
            if (!this.editingField?.subFields) return;
            const updated = { ...this.editingField.subFields };
            delete updated[name];
            this.editingField = { ...this.editingField, subFields: updated };
        },

        async save() {
            this.saving = true;

            try {
                const response = await fetch(`/{{ config('talos.admin_prefix', 'talos') }}/content-type-builder/${uid}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        attributes: toObject(this.fields),
                        _renames:   this.renames,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.renames = {}; // renames applied — reset for next session
                    this.showSuccess('Schema saved successfully!');
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (e) {
                alert('Network error. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        showSuccess(msg) {
            const el = document.createElement('div');
            el.className = 'fixed bottom-6 right-6 bg-green-700 text-white px-5 py-3 rounded-lg shadow-xl text-sm font-medium z-50 flex items-center gap-2';
            el.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>${msg}`;
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 3000);
        },
    };
}
</script>
@endsection
