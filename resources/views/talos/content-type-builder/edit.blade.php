@extends('talos.layouts.app')

@section('title', $contentType['info']['displayName'] . ' — Builder')
@section('header', 'Content-Type Builder')

@section('content')
@php
$cats = [
    'Text' => [
        ['type'=>'string',      'label'=>'Short text',   'icon'=>'Aa', 'color'=>'blue',   'desc'=>'Best for titles, names, links'],
        ['type'=>'text',        'label'=>'Long text',    'icon'=>'¶',  'color'=>'blue',   'desc'=>'Unlimited plain text'],
        ['type'=>'richtext',    'label'=>'Rich text',    'icon'=>'RT', 'color'=>'blue',   'desc'=>'HTML editor with formatting'],
    ],
    'Numbers' => [
        ['type'=>'integer',     'label'=>'Integer',      'icon'=>'123','color'=>'emerald','desc'=>'Whole numbers: 0, 42, -5'],
        ['type'=>'biginteger',  'label'=>'Big integer',  'icon'=>'1+', 'color'=>'emerald','desc'=>'Very large whole numbers'],
        ['type'=>'decimal',     'label'=>'Decimal',      'icon'=>'1.2','color'=>'emerald','desc'=>'Fixed precision: 3.14'],
        ['type'=>'float',       'label'=>'Float',        'icon'=>'~1', 'color'=>'emerald','desc'=>'Floating point numbers'],
    ],
    'Date & Time' => [
        ['type'=>'date',        'label'=>'Date',         'icon'=>'📅', 'color'=>'orange', 'desc'=>'A date without time'],
        ['type'=>'datetime',    'label'=>'Date & Time',  'icon'=>'🕐', 'color'=>'orange', 'desc'=>'Full date with time'],
        ['type'=>'time',        'label'=>'Time',         'icon'=>'⏱', 'color'=>'orange', 'desc'=>'Time only, no date'],
    ],
    'Other' => [
        ['type'=>'boolean',     'label'=>'Boolean',      'icon'=>'◎',  'color'=>'violet', 'desc'=>'True or false, yes or no'],
        ['type'=>'email',       'label'=>'Email',        'icon'=>'@',  'color'=>'cyan',   'desc'=>'Validated email address'],
        ['type'=>'url',         'label'=>'URL',          'icon'=>'🔗', 'color'=>'cyan',   'desc'=>'A web address'],
        ['type'=>'uid',         'label'=>'UID / Slug',   'icon'=>'#',  'color'=>'gray',   'desc'=>'Auto-generated unique slug'],
        ['type'=>'json',        'label'=>'JSON',         'icon'=>'{}', 'color'=>'gray',   'desc'=>'Raw JSON data object'],
        ['type'=>'enumeration', 'label'=>'Enumeration',  'icon'=>'≡',  'color'=>'purple', 'desc'=>'A list of predefined options'],
    ],
    'Media' => [
        ['type'=>'media',       'label'=>'Media',        'icon'=>'🖼', 'color'=>'pink',   'desc'=>'Images and files from media library'],
    ],
    'Relations' => [
        ['type'=>'relation',    'label'=>'Relation',     'icon'=>'⟷', 'color'=>'yellow', 'desc'=>'Link to another content type'],
    ],
    'Components' => [
        ['type'=>'component',   'label'=>'Component',    'icon'=>'⬡',  'color'=>'indigo', 'desc'=>'Reusable group of fields'],
        ['type'=>'dynamiczone', 'label'=>'Dynamic Zone', 'icon'=>'⬡+', 'color'=>'indigo', 'desc'=>'Flexible mix of components'],
    ],
    'Advanced' => [
        ['type'=>'repeater',    'label'=>'Repeater',     'icon'=>'≣',  'color'=>'teal',   'desc'=>'Repeatable list of sub-fields'],
    ],
];

$cardClass = [
    'blue'   => 'bg-blue-500/15 text-blue-600',
    'emerald'=> 'bg-emerald-500/15 text-emerald-400',
    'orange' => 'bg-orange-500/15 text-orange-400',
    'violet' => 'bg-violet-500/15 text-violet-400',
    'cyan'   => 'bg-cyan-500/15 text-cyan-400',
    'gray'   => 'bg-gray-500/15 text-slate-500',
    'purple' => 'bg-purple-500/15 text-violet-600',
    'pink'   => 'bg-pink-500/15 text-pink-400',
    'yellow' => 'bg-yellow-500/15 text-amber-600',
    'indigo' => 'bg-indigo-500/15 text-indigo-400',
    'teal'   => 'bg-teal-500/15 text-teal-400',
];
@endphp

<div x-data="fieldBuilder({{ json_encode($contentType['attributes'] ?? []) }}, '{{ $uid }}')"
     @keydown.escape.window="closeModal()">

    {{-- ── Model header ──────────────────────────────────────────── --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-6 flex items-center gap-5">
        <div class="w-12 h-12 bg-blue-600/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2.5 flex-wrap">
                <h2 class="text-slate-800 text-lg font-bold">{{ $contentType['info']['displayName'] }}</h2>
                <span class="px-2 py-0.5 bg-slate-100 text-slate-400 text-xs rounded-lg font-mono">{{ $uid }}</span>
                @if($contentType['options']['i18n'] ?? false)
                    <span class="px-2.5 py-0.5 bg-indigo-500/15 text-indigo-400 text-xs rounded-full font-medium">i18n</span>
                @endif
                @if($contentType['options']['draftAndPublish'] ?? false)
                    <span class="px-2.5 py-0.5 bg-amber-500/15 text-amber-400 text-xs rounded-full font-medium">Draft & Publish</span>
                @endif
                <span class="px-2.5 py-0.5 bg-slate-100 text-slate-400 text-xs rounded-full">
                    {{ $contentType['kind'] === 'singleType' ? 'Single type' : 'Collection type' }}
                </span>
            </div>
            <p class="text-slate-400 text-sm mt-0.5">
                Table: <span class="font-mono text-slate-500 text-xs">{{ $contentType['collectionName'] }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2.5 flex-shrink-0">
            <a href="{{ route('talos.content.index', ['uid' => $uid]) }}"
               class="px-4 py-2 bg-slate-100 hover:bg-slate-100 text-slate-600 rounded-xl text-sm font-medium transition-colors">
                View content
            </a>
            <button @click="save()" :disabled="saving"
                    class="flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white rounded-xl text-sm font-semibold transition-colors">
                <svg x-show="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span x-text="saving ? 'Saving…' : 'Save schema'"></span>
            </button>
        </div>
    </div>

    {{-- ── Field list ─────────────────────────────────────────────── --}}
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <div>
                <h3 class="text-slate-800 font-semibold">Fields</h3>
                <p class="text-xs text-slate-400 mt-0.5"
                   x-text="fields.length + ' field' + (fields.length !== 1 ? 's' : '') + ' configured'"></p>
            </div>
            <button @click="openPicker()"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add field
            </button>
        </div>

        {{-- Empty state --}}
        <template x-if="fields.length === 0">
            <div class="py-20 flex flex-col items-center gap-4 text-slate-400">
                <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 0v10m0-10a2 2 0 012 2h2a2 2 0 012-2"/>
                    </svg>
                </div>
                <div class="text-center">
                    <p class="text-slate-500 font-medium text-base">No fields yet</p>
                    <p class="text-sm mt-1 text-slate-400">Click "Add field" to define your content structure.</p>
                </div>
                <button @click="openPicker()"
                        class="mt-1 flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add your first field
                </button>
            </div>
        </template>

        {{-- Field rows --}}
        <template x-if="fields.length > 0">
            <div>
                <template x-for="(field, index) in fields" :key="field.name">
                    <div class="group flex items-center gap-4 px-6 py-3.5 border-b border-slate-200/60 hover:bg-white/[0.02] transition-colors">

                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 text-sm font-mono font-bold"
                             :class="typeStyle(field.type).bg + ' ' + typeStyle(field.type).text">
                            <span x-text="typeStyle(field.type).icon"></span>
                        </div>

                        <div class="flex-1 min-w-0 flex items-center gap-2.5 flex-wrap">
                            <span class="text-slate-800 font-medium font-mono text-sm" x-text="field.name"></span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                  :class="typeStyle(field.type).badge"
                                  x-text="typeStyle(field.type).label"></span>
                            <template x-if="field.required">
                                <span class="px-2 py-0.5 bg-red-500/10 text-red-600 rounded-full text-xs">Required</span>
                            </template>
                            <template x-if="field.unique">
                                <span class="px-2 py-0.5 bg-slate-200/80 text-slate-500 rounded-full text-xs">Unique</span>
                            </template>
                            <template x-if="field.private">
                                <span class="px-2 py-0.5 bg-slate-200/80 text-slate-400 rounded-full text-xs">Private</span>
                            </template>
                        </div>

                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="openEdit(index)" title="Edit"
                                    class="p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </button>
                            <button @click="removeField(index)" title="Delete"
                                    class="p-2 text-slate-400 hover:text-red-600 hover:bg-slate-100 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>

                <button @click="openPicker()"
                        class="w-full flex items-center gap-3 px-6 py-4 text-slate-400 hover:text-blue-600 hover:bg-white/[0.02] transition-colors text-sm group">
                    <div class="w-8 h-8 rounded-xl border-2 border-dashed border-slate-300 group-hover:border-blue-500/50 flex items-center justify-center transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    Add another field
                </button>
            </div>
        </template>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL SYSTEM
    ═══════════════════════════════════════════════════════════════ --}}

    {{-- Single root: backdrop + card in one x-show container --}}
    <div x-show="modal !== null"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto">

        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/50 backdrop-blur-[2px]" @click="closeModal()"></div>

        <div class="relative flex min-h-full items-start justify-center px-4 pt-16 pb-8 pointer-events-none">

            {{-- Card: width transitions smoothly between picker and config sizes --}}
            <div class="relative pointer-events-auto w-full bg-white border border-slate-200 rounded-2xl shadow-2xl"
                 :class="modal === 'picker' ? 'max-w-2xl' : 'max-w-md'"
                 style="transition: max-width 0.22s cubic-bezier(0.4,0,0.2,1)">

                    {{-- ─────────────────────────────────────────────────────
                         PICKER PANEL
                    ───────────────────────────────────────────────────── --}}
                    <div x-show="modal === 'picker'"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100">

                        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                            <div>
                                <h3 class="text-slate-800 font-bold text-lg">Select a field type</h3>
                                <p class="text-slate-400 text-sm mt-0.5">Choose the kind of content you want to create</p>
                            </div>
                            <button @click="closeModal()"
                                    class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-6 overflow-y-auto" style="max-height:calc(100vh - 12rem)">
                            @foreach($cats as $category => $types)
                            <div>
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-3">{{ $category }}</p>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($types as $ft)
                                    <button @click="selectType('{{ $ft['type'] }}')"
                                            class="group flex items-center gap-3 p-3.5 bg-slate-50 hover:bg-white border border-slate-200 hover:border-blue-300 hover:shadow-sm rounded-xl text-left transition-all duration-150">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-base font-mono font-bold {{ $cardClass[$ft['color']] }}">
                                            {{ $ft['icon'] }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-800">{{ $ft['label'] }}</p>
                                            <p class="text-xs text-slate-400 leading-snug">{{ $ft['desc'] }}</p>
                                        </div>
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>{{-- /picker panel --}}

                    {{-- ─────────────────────────────────────────────────────
                         CONFIG PANEL
                    ───────────────────────────────────────────────────── --}}
                    <div x-show="modal === 'config'"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100">

                        {{-- Config header --}}
                        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                            <div class="flex items-center gap-2.5">
                                <template x-if="editingIndex === null">
                                    <button @click="backToPicker()"
                                            class="p-1.5 -ml-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
                                            title="Back">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                </template>
                                <template x-if="editingField">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-mono font-bold"
                                         :class="typeStyle(editingField.type).bg + ' ' + typeStyle(editingField.type).text">
                                        <span x-text="typeStyle(editingField.type).icon"></span>
                                    </div>
                                </template>
                                <div>
                                    <p class="text-slate-800 font-bold text-sm"
                                       x-text="editingIndex !== null ? 'Edit field' : 'Configure field'"></p>
                                    <p class="text-xs text-slate-400" x-text="editingField ? typeStyle(editingField.type).label : ''"></p>
                                </div>
                            </div>
                            <button @click="closeModal()"
                                    class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Config body --}}
                <div class="overflow-y-auto flex-1 p-6 space-y-5">
                    <template x-if="editingField">
                        <div class="space-y-5">

                            {{-- Field name --}}
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-2">
                                    Name <span class="text-red-600">*</span>
                                </label>
                                <input type="text" x-model="editingField.name" placeholder="e.g. title"
                                       class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm
                                              focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 font-mono transition-all"
                                       @input="editingField.name = $el.value.toLowerCase().replace(/[^a-z0-9_]/g,'')">
                                <p class="text-xs text-slate-400 mt-1.5">Lowercase letters, numbers and underscores only</p>
                            </div>

                            {{-- Max length (string / uid) --}}
                            <template x-if="editingField && ['string','uid'].includes(editingField.type)">
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-2">Max length</label>
                                    <input type="number" x-model.number="editingField.maxLength" min="1" max="65535"
                                           class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-all">
                                </div>
                            </template>

                            {{-- Enumeration --}}
                            <template x-if="editingField && editingField.type === 'enumeration'">
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-2">
                                        Values <span class="text-slate-400 font-normal text-xs">(one per line)</span>
                                    </label>
                                    <textarea x-model="editingField.enumValues" rows="4"
                                              placeholder="draft&#10;published&#10;archived"
                                              class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm
                                                     focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 font-mono resize-none transition-all"></textarea>
                                </div>
                            </template>

                            {{-- Number min/max --}}
                            <template x-if="editingField && ['integer','biginteger','decimal','float'].includes(editingField.type)">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-600 mb-2">Min</label>
                                        <input type="number" x-model.number="editingField.min"
                                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-600 mb-2">Max</label>
                                        <input type="number" x-model.number="editingField.max"
                                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-all">
                                    </div>
                                </div>
                            </template>

                            {{-- Media --}}
                            <template x-if="editingField && editingField.type === 'media'">
                                <div @click="editingField.multiple = !editingField.multiple"
                                     class="flex items-center justify-between p-4 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors">
                                    <div>
                                        <p class="text-sm font-medium text-slate-800">Allow multiple files</p>
                                        <p class="text-xs text-slate-400">Select more than one file at once</p>
                                    </div>
                                    <div class="relative flex-shrink-0">
                                        <div class="w-11 h-6 rounded-full transition-colors" :class="editingField.multiple ? 'bg-blue-600' : 'bg-slate-300'">
                                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all"
                                                 :class="editingField.multiple ? 'left-6' : 'left-1'"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- Relation --}}
                            <template x-if="editingField && editingField.type === 'relation'">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-600 mb-2">Relation type</label>
                                        <select x-model="editingField.relation"
                                                class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-all">
                                            <option value="manyToOne">Many to One</option>
                                            <option value="oneToOne">One to One</option>
                                            <option value="oneToMany">One to Many</option>
                                            <option value="manyToMany">Many to Many</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-600 mb-2">Target content type</label>
                                        <select x-model="editingField.target"
                                                class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-all">
                                            <option value="">— Select —</option>
                                            @foreach($contentTypes as $ct)
                                                <option value="{{ $ct['__uid'] }}">{{ $ct['info']['displayName'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </template>

                            {{-- Component / Dynamic Zone --}}
                            <template x-if="editingField && ['component','dynamiczone'].includes(editingField.type)">
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-2"
                                           x-text="editingField.type === 'dynamiczone' ? 'Allowed components' : 'Component'"></label>
                                    @php $allComponents = $components ?? []; @endphp
                                    @if(count($allComponents) > 0)
                                        <div class="space-y-1 max-h-40 overflow-y-auto">
                                            @foreach($allComponents as $cat => $catComponents)
                                                <p class="text-xs text-slate-400 px-1 pt-2 pb-1">{{ $cat }}</p>
                                                @foreach($catComponents as $comp)
                                                    <label class="flex items-center gap-3 p-3 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors">
                                                        <input type="checkbox"
                                                               :checked="(editingField.components || []).includes('{{ $comp['__uid'] }}')"
                                                               @change="toggleComponent('{{ $comp['__uid'] }}')"
                                                               class="rounded bg-slate-200 border-slate-300 text-blue-600 focus:ring-0">
                                                        <span class="text-sm text-slate-600">{{ $comp['info']['displayName'] }}</span>
                                                    </label>
                                                @endforeach
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-sm text-slate-400 p-3 bg-slate-100 rounded-xl">
                                            No components yet.
                                            <a href="{{ route('talos.components.create') }}" class="text-blue-600 hover:underline">Create one →</a>
                                        </p>
                                    @endif
                                    <template x-if="editingField && editingField.type === 'component'">
                                        <div @click="editingField.repeatable = !editingField.repeatable"
                                             class="flex items-center justify-between p-3 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors mt-2">
                                            <div>
                                                <p class="text-sm font-medium text-slate-800">Repeatable</p>
                                                <p class="text-xs text-slate-400">Allow multiple instances</p>
                                            </div>
                                            <div class="relative flex-shrink-0">
                                                <div class="w-11 h-6 rounded-full transition-colors" :class="editingField.repeatable ? 'bg-blue-600' : 'bg-slate-300'">
                                                    <div class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all"
                                                         :class="editingField.repeatable ? 'left-6' : 'left-1'"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            {{-- Repeater sub-fields --}}
                            <template x-if="editingField && editingField.type === 'repeater'">
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-2">Sub-fields</label>

                                    {{-- Existing sub-fields list --}}
                                    <div class="space-y-1.5 mb-3">
                                        <template x-for="sf in getSubFieldArray()" :key="sf.name">
                                            <div class="flex items-center gap-2 px-3 py-2.5 bg-slate-100 rounded-lg">
                                                <span class="text-xs font-mono text-slate-800 flex-1" x-text="sf.name"></span>
                                                <span class="text-xs text-slate-400 bg-slate-200 px-1.5 py-0.5 rounded" x-text="sf.type"></span>
                                                <template x-if="sf.required">
                                                    <span class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded">req</span>
                                                </template>
                                                <template x-if="sf.unique">
                                                    <span class="text-xs bg-slate-200 text-slate-500 px-1.5 py-0.5 rounded">uniq</span>
                                                </template>
                                                <button type="button" @click="removeSubField(sf.name)"
                                                        class="text-slate-400 hover:text-red-600 transition-colors ml-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="getSubFieldArray().length === 0">
                                            <p class="text-xs text-slate-400 italic px-1">No sub-fields yet.</p>
                                        </template>
                                    </div>

                                    {{-- Add sub-field form --}}
                                    <div class="p-3 bg-slate-100 rounded-xl border border-slate-300 space-y-2.5">

                                        {{-- Name + type row --}}
                                        <div class="flex gap-2">
                                            <input type="text" x-model="newSubFieldName"
                                                   placeholder="field_name"
                                                   @keydown.enter.prevent="addSubField()"
                                                   @input="newSubFieldName = $el.value.toLowerCase().replace(/[^a-z0-9_]/g,'')"
                                                   class="flex-1 px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-800 text-xs font-mono focus:outline-none focus:border-blue-500">
                                            <select x-model="newSubFieldType" @change="newSubFieldConfig = {}"
                                                    class="px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                <option value="string">String</option>
                                                <option value="text">Text</option>
                                                <option value="richtext">Rich text</option>
                                                <option value="integer">Integer</option>
                                                <option value="biginteger">Big integer</option>
                                                <option value="decimal">Decimal</option>
                                                <option value="float">Float</option>
                                                <option value="boolean">Boolean</option>
                                                <option value="email">Email</option>
                                                <option value="url">URL</option>
                                                <option value="date">Date</option>
                                                <option value="datetime">DateTime</option>
                                                <option value="time">Time</option>
                                                <option value="enumeration">Enumeration</option>
                                                <option value="json">JSON</option>
                                            </select>
                                        </div>

                                        {{-- Max length — string --}}
                                        <template x-if="['string'].includes(newSubFieldType)">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-500 mb-1">Max length</label>
                                                <input type="number" x-model.number="newSubFieldConfig.maxLength" min="1" max="65535" placeholder="255"
                                                       class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                            </div>
                                        </template>

                                        {{-- Enum values --}}
                                        <template x-if="newSubFieldType === 'enumeration'">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-500 mb-1">Values <span class="text-slate-400 font-normal">(one per line)</span></label>
                                                <textarea x-model="newSubFieldConfig.enumValues" rows="3"
                                                          placeholder="draft&#10;published&#10;archived"
                                                          class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-800 text-xs font-mono resize-none focus:outline-none focus:border-blue-500"></textarea>
                                            </div>
                                        </template>

                                        {{-- Number min / max --}}
                                        <template x-if="['integer','biginteger','decimal','float'].includes(newSubFieldType)">
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-500 mb-1">Min</label>
                                                    <input type="number" x-model.number="newSubFieldConfig.min"
                                                           class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-500 mb-1">Max</label>
                                                    <input type="number" x-model.number="newSubFieldConfig.max"
                                                           class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                                </div>
                                            </div>
                                        </template>

                                        {{-- Default value — simple types --}}
                                        <template x-if="!['boolean','richtext','json'].includes(newSubFieldType)">
                                            <div>
                                                <label class="block text-xs font-medium text-slate-500 mb-1">Default value</label>
                                                <input type="text" x-model="newSubFieldConfig.default" placeholder="(optional)"
                                                       class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                            </div>
                                        </template>

                                        {{-- Required / Unique toggles --}}
                                        <div class="flex gap-3 pt-0.5">
                                            <label class="flex items-center gap-1.5 cursor-pointer select-none">
                                                <input type="checkbox" x-model="newSubFieldConfig.required"
                                                       class="rounded bg-white border-slate-300 text-blue-600 focus:ring-0 w-3.5 h-3.5">
                                                <span class="text-xs text-slate-600">Required</span>
                                            </label>
                                            <template x-if="['string','text','email','url','integer','biginteger','decimal','float'].includes(newSubFieldType)">
                                                <label class="flex items-center gap-1.5 cursor-pointer select-none">
                                                    <input type="checkbox" x-model="newSubFieldConfig.unique"
                                                           class="rounded bg-white border-slate-300 text-blue-600 focus:ring-0 w-3.5 h-3.5">
                                                    <span class="text-xs text-slate-600">Unique</span>
                                                </label>
                                            </template>
                                        </div>

                                        <button type="button" @click="addSubField()" :disabled="!newSubFieldName"
                                                class="w-full py-1.5 bg-blue-600 hover:bg-blue-500 disabled:opacity-40 text-white rounded-lg text-xs font-medium transition-colors">
                                            Add sub-field
                                        </button>
                                    </div>
                                </div>
                            </template>

                            {{-- Default value --}}
                            <template x-if="editingField && !['component','dynamiczone','media','relation','richtext','repeater','boolean'].includes(editingField.type)">
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-2">Default value</label>
                                    <input type="text" x-model="editingField.default"
                                           class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-all">
                                </div>
                            </template>

                            {{-- Advanced toggles --}}
                            <div class="pt-1 space-y-2">
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Advanced settings</p>

                                <div @click="editingField.required = !editingField.required"
                                     class="flex items-center justify-between p-4 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors">
                                    <div>
                                        <p class="text-sm font-medium text-slate-800">Required</p>
                                        <p class="text-xs text-slate-400">This field must have a value</p>
                                    </div>
                                    <div class="relative flex-shrink-0">
                                        <div class="w-11 h-6 rounded-full transition-colors" :class="editingField.required ? 'bg-blue-600' : 'bg-slate-300'">
                                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all"
                                                 :class="editingField.required ? 'left-6' : 'left-1'"></div>
                                        </div>
                                    </div>
                                </div>

                                <template x-if="editingField && ['string','text','email','url','uid','integer','biginteger','decimal','float'].includes(editingField.type)">
                                    <div @click="editingField.unique = !editingField.unique"
                                         class="flex items-center justify-between p-4 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors">
                                        <div>
                                            <p class="text-sm font-medium text-slate-800">Unique</p>
                                            <p class="text-xs text-slate-400">No duplicate values allowed</p>
                                        </div>
                                        <div class="relative flex-shrink-0">
                                            <div class="w-11 h-6 rounded-full transition-colors" :class="editingField.unique ? 'bg-blue-600' : 'bg-slate-300'">
                                                <div class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all"
                                                     :class="editingField.unique ? 'left-6' : 'left-1'"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div @click="editingField.private = !editingField.private"
                                     class="flex items-center justify-between p-4 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors">
                                    <div>
                                        <p class="text-sm font-medium text-slate-800">Private</p>
                                        <p class="text-xs text-slate-400">Not exposed in the API</p>
                                    </div>
                                    <div class="relative flex-shrink-0">
                                        <div class="w-11 h-6 rounded-full transition-colors" :class="editingField.private ? 'bg-blue-600' : 'bg-slate-300'">
                                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all"
                                                 :class="editingField.private ? 'left-6' : 'left-1'"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="flex gap-3 px-6 py-4 border-t border-slate-200 flex-shrink-0">
                    <button @click="editingIndex === null ? backToPicker() : closeModal()"
                            class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-medium transition-colors"
                            x-text="editingIndex === null ? '← Back' : 'Cancel'">
                    </button>
                    <button @click="addOrUpdateField()"
                            :disabled="!editingField || !editingField.name"
                            class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 disabled:opacity-40 text-white rounded-xl text-sm font-semibold transition-colors"
                            x-text="editingIndex !== null ? 'Update field' : 'Add field'">
                    </button>
                </div>
                    </div>{{-- /config panel --}}
                </div>{{-- /card --}}
            </div>{{-- /centering flex --}}
        </div>{{-- /modal root --}}
</div>

<script>
function fieldBuilder(initialAttributes, uid) {
    const S = {
        string:      { icon: 'Aa',  bg: 'bg-blue-500/20',     text: 'text-blue-600',     badge: 'bg-blue-500/15 text-blue-600',     label: 'Short text' },
        text:        { icon: '¶',   bg: 'bg-blue-500/20',     text: 'text-blue-600',     badge: 'bg-blue-500/15 text-blue-600',     label: 'Long text' },
        richtext:    { icon: 'RT',  bg: 'bg-blue-500/20',     text: 'text-blue-600',     badge: 'bg-blue-500/15 text-blue-600',     label: 'Rich text' },
        integer:     { icon: '123', bg: 'bg-emerald-500/20',  text: 'text-emerald-400',  badge: 'bg-emerald-500/15 text-emerald-400', label: 'Integer' },
        biginteger:  { icon: '1+',  bg: 'bg-emerald-500/20',  text: 'text-emerald-400',  badge: 'bg-emerald-500/15 text-emerald-400', label: 'Big integer' },
        decimal:     { icon: '1.2', bg: 'bg-emerald-500/20',  text: 'text-emerald-400',  badge: 'bg-emerald-500/15 text-emerald-400', label: 'Decimal' },
        float:       { icon: '~1',  bg: 'bg-emerald-500/20',  text: 'text-emerald-400',  badge: 'bg-emerald-500/15 text-emerald-400', label: 'Float' },
        date:        { icon: '📅',  bg: 'bg-orange-500/20',   text: 'text-orange-400',   badge: 'bg-orange-500/15 text-orange-400',   label: 'Date' },
        datetime:    { icon: '🕐',  bg: 'bg-orange-500/20',   text: 'text-orange-400',   badge: 'bg-orange-500/15 text-orange-400',   label: 'Date & Time' },
        time:        { icon: '⏱',  bg: 'bg-orange-500/20',   text: 'text-orange-400',   badge: 'bg-orange-500/15 text-orange-400',   label: 'Time' },
        boolean:     { icon: '◎',   bg: 'bg-violet-500/20',   text: 'text-violet-400',   badge: 'bg-violet-500/15 text-violet-400',   label: 'Boolean' },
        email:       { icon: '@',   bg: 'bg-cyan-500/20',     text: 'text-cyan-400',     badge: 'bg-cyan-500/15 text-cyan-400',     label: 'Email' },
        url:         { icon: '🔗',  bg: 'bg-cyan-500/20',     text: 'text-cyan-400',     badge: 'bg-cyan-500/15 text-cyan-400',     label: 'URL' },
        uid:         { icon: '#',   bg: 'bg-gray-500/20',     text: 'text-slate-500',     badge: 'bg-gray-500/15 text-slate-500',     label: 'UID' },
        json:        { icon: '{}',  bg: 'bg-gray-500/20',     text: 'text-slate-500',     badge: 'bg-gray-500/15 text-slate-500',     label: 'JSON' },
        enumeration: { icon: '≡',   bg: 'bg-purple-500/20',   text: 'text-violet-600',   badge: 'bg-purple-500/15 text-violet-600',   label: 'Enumeration' },
        media:       { icon: '🖼',  bg: 'bg-pink-500/20',     text: 'text-pink-400',     badge: 'bg-pink-500/15 text-pink-400',     label: 'Media' },
        relation:    { icon: '⟷',  bg: 'bg-yellow-500/20',   text: 'text-amber-600',   badge: 'bg-yellow-500/15 text-amber-600',   label: 'Relation' },
        component:   { icon: '⬡',   bg: 'bg-indigo-500/20',   text: 'text-indigo-400',   badge: 'bg-indigo-500/15 text-indigo-400',   label: 'Component' },
        dynamiczone: { icon: '⬡+',  bg: 'bg-indigo-500/20',   text: 'text-indigo-400',   badge: 'bg-indigo-500/15 text-indigo-400',   label: 'Dynamic Zone' },
        repeater:    { icon: '≣',   bg: 'bg-teal-500/20',     text: 'text-teal-400',     badge: 'bg-teal-500/15 text-teal-400',     label: 'Repeater' },
    };

    const toArray  = a => Object.entries(a || {}).map(([name, f]) => ({ name, ...f }));
    const toObject = a => a.reduce((o, f) => { const { name, ...r } = f; o[name] = r; return o; }, {});

    return {
        fields:           toArray(initialAttributes),
        modal:            null,
        editingField:     null,
        editingIndex:     null,
        saving:           false,
        renames:          {},
        newSubFieldName:  '',
        newSubFieldType:  'string',
        newSubFieldConfig: {},

        typeStyle(type) { return S[type] ?? { icon: '?', bg: 'bg-gray-500/20', text: 'text-slate-500', badge: 'bg-gray-500/15 text-slate-500', label: type }; },

        openPicker() { this.editingField = null; this.editingIndex = null; this.modal = 'picker'; },

        openEdit(index) {
            this.editingField = { ...this.fields[index], _originalName: this.fields[index].name };
            this.editingIndex = index;
            this.modal = 'config';
        },

        selectType(type) {
            this.editingField = { type, name: '', required: false, unique: false, private: false };
            if (type === 'repeater') this.editingField.subFields = {};
            if (type === 'relation') { this.editingField.relation = 'manyToOne'; this.editingField.target = ''; }
            this.editingIndex = null;
            this.modal = 'config';
        },

        closeModal() { this.modal = null; this.editingField = null; this.editingIndex = null; },

        backToPicker() { this.modal = 'picker'; this.editingField = null; this.editingIndex = null; },

        async removeField(index) {
            if (await talos.confirm('Remove this field? This cannot be undone.')) {
                this.fields.splice(index, 1);
                talos.markDirty();
            }
        },

        toggleComponent(uid) {
            if (!this.editingField.components) this.editingField.components = [];
            const i = this.editingField.components.indexOf(uid);
            i === -1 ? this.editingField.components.push(uid) : this.editingField.components.splice(i, 1);
        },

        addOrUpdateField() {
            if (!this.editingField?.name) return;
            if (this.fields.some((f, i) => f.name === this.editingField.name && i !== this.editingIndex)) {
                talos.toast('A field with this name already exists.', 'error');
                return;
            }

            if (this.editingIndex !== null) {
                const orig = this.editingField._originalName;
                const next = this.editingField.name;
                if (orig && orig !== next) {
                    const root = Object.keys(this.renames).find(k => this.renames[k] === orig) ?? orig;
                    if (root !== orig) delete this.renames[root];
                    root === next ? delete this.renames[root] : (this.renames[root] = next);
                }
                const saved = { ...this.editingField };
                delete saved._originalName;
                this.fields[this.editingIndex] = saved;
            } else {
                const f = { ...this.editingField };
                delete f._originalName;
                this.fields.push(f);
            }
            this.closeModal();
            talos.markDirty();
        },

        getSubFieldArray() {
            if (!this.editingField?.subFields) return [];
            return Object.entries(this.editingField.subFields).map(([name, f]) => ({ name, ...f }));
        },

        addSubField() {
            const n = this.newSubFieldName.trim();
            if (!n) return;
            if (!this.editingField.subFields) this.editingField.subFields = {};
            if (this.editingField.subFields[n]) { talos.toast('Sub-field "' + n + '" already exists.', 'error'); return; }
            this.editingField.subFields = { ...this.editingField.subFields, [n]: { type: this.newSubFieldType, ...this.newSubFieldConfig } };
            this.newSubFieldName = '';
            this.newSubFieldConfig = {};
        },

        removeSubField(name) {
            const u = { ...this.editingField.subFields };
            delete u[name];
            this.editingField = { ...this.editingField, subFields: u };
        },

        async save() {
            this.saving = true;
            try {
                const r = await fetch(`/{{ config('talos.admin_prefix', 'talos') }}/content-type-builder/${uid}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ attributes: toObject(this.fields), _renames: this.renames }),
                });
                const data = await r.json();
                if (data.success) {
                    this.renames = {};
                    talos.markClean();
                    talos.toast('Schema saved!', 'success');
                } else {
                    talos.toast(data.error || 'Unknown error', 'error');
                }
            } catch {
                talos.toast('Network error — please try again.', 'error');
            } finally {
                this.saving = false;
            }
        },

    };
}
</script>
@endsection
