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

@php
$flatComponents = [];
foreach(($components ?? []) as $cat => $comps) {
    foreach($comps as $comp) {
        $flatComponents[] = ['uid' => $comp['__uid'], 'displayName' => $comp['info']['displayName'], 'category' => $cat];
    }
}
@endphp
<div x-data="fieldBuilder({{ json_encode($contentType['attributes'] ?? []) }}, '{{ $uid }}', {{ json_encode($flatComponents) }})"
     @keydown.escape.window="compBuilderOpen ? (cc()?.cModal ? compCloseModal() : (compStack.length > 1 ? compPopFrame() : closeCompBuilder())) : closeModal()">

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
        <div x-show="fields.length > 0">
            <div x-ref="fieldsList">
                <template x-for="(field, index) in fields" :key="field.name">
                    <div class="group flex items-center gap-4 px-6 py-3.5 border-b border-slate-200/60 hover:bg-slate-50/50 transition-colors">

                        <div class="field-drag-handle cursor-grab active:cursor-grabbing text-slate-300 hover:text-slate-500 flex-shrink-0 transition-colors" title="Drag to reorder">
                            <svg class="w-4 h-4" viewBox="0 0 16 16" fill="currentColor">
                                <circle cx="5" cy="4" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="5" cy="12" r="1.5"/>
                                <circle cx="11" cy="4" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="11" cy="12" r="1.5"/>
                            </svg>
                        </div>

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
            </div>

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
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-600 mb-2">
                                            Values <span class="text-slate-400 font-normal text-xs">(one per line)</span>
                                        </label>
                                        <textarea x-model="editingField.enumValues" rows="4"
                                                  placeholder="draft&#10;published&#10;archived"
                                                  class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm
                                                         focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 font-mono resize-none transition-all"></textarea>
                                    </div>
                                    <div @click="editingField.multiple = !editingField.multiple"
                                         class="flex items-center justify-between p-4 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors">
                                        <div>
                                            <p class="text-sm font-medium text-slate-800">Allow multiple selection</p>
                                            <p class="text-xs text-slate-400">Users can pick more than one option</p>
                                        </div>
                                        <div class="relative flex-shrink-0">
                                            <div class="w-11 h-6 rounded-full transition-colors" :class="editingField.multiple ? 'bg-blue-600' : 'bg-slate-300'">
                                                <div class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all"
                                                     :class="editingField.multiple ? 'left-6' : 'left-1'"></div>
                                            </div>
                                        </div>
                                    </div>
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
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-sm font-medium text-slate-600"
                                               x-text="editingField.type === 'dynamiczone' ? 'Allowed components' : 'Component'"></label>
                                        <button type="button" @click="openCompBuilder()"
                                                class="flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 font-medium transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            Add new component
                                        </button>
                                    </div>

                                    {{-- Component list (Alpine-reactive) --}}
                                    <template x-if="allComponents.length > 0">
                                        <div class="space-y-1 max-h-40 overflow-y-auto">
                                            <template x-for="cat in uniqueCategories()" :key="cat">
                                                <div>
                                                    <p class="text-xs text-slate-400 px-1 pt-2 pb-1" x-text="cat"></p>
                                                    <template x-for="comp in componentsByCategory(cat)" :key="comp.uid">
                                                        <label class="flex items-center gap-3 p-3 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors">
                                                            <input type="checkbox"
                                                                   :checked="(editingField.components || []).includes(comp.uid)"
                                                                   @change="toggleComponent(comp.uid)"
                                                                   class="rounded bg-slate-200 border-slate-300 text-blue-600 focus:ring-0">
                                                            <span class="text-sm text-slate-600" x-text="comp.displayName"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="allComponents.length === 0">
                                        <p class="text-sm text-slate-400 p-3 bg-slate-100 rounded-xl">
                                            No components yet. Use "Add new component" above.
                                        </p>
                                    </template>

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

    {{-- ═══════════════════════════════════════════════════════════════
         COMPONENT BUILDER MODAL
    ═══════════════════════════════════════════════════════════════ --}}
    <div x-show="compBuilderOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[60] overflow-y-auto">

        <div class="fixed inset-0 bg-black/60 backdrop-blur-[2px]" @click="closeCompBuilder()"></div>

        <div class="relative flex min-h-full items-start justify-center px-4 pt-16 pb-8 pointer-events-none">

            <div class="relative pointer-events-auto w-full bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden"
                 :class="cc() && cc().cModal === 'config' ? 'max-w-md' : 'max-w-2xl'"
                 style="transition: max-width 0.22s cubic-bezier(0.4,0,0.2,1)">

                {{-- ── Header / breadcrumb ─────────────────────────────── --}}
                <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        {{-- Back button --}}
                        <template x-if="cc() && (cc().cModal !== null || compStack.length > 1)">
                            <button @click="cc().cModal === 'config' ? compBackToPicker() : (cc().cModal === 'picker' ? compCloseModal() : compPopFrame())"
                                    class="p-1.5 -ml-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors flex-shrink-0" title="Back">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                        </template>
                        {{-- Breadcrumb --}}
                        <div class="flex items-center gap-1 min-w-0 flex-1 flex-wrap">
                            <template x-for="(frame, fi) in compStack" :key="fi">
                                <div class="flex items-center gap-1">
                                    <template x-if="fi > 0">
                                        <svg class="w-3 h-3 text-slate-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </template>
                                    <span class="text-sm font-medium"
                                          :class="fi === compStack.length - 1 && cc()?.cModal === null ? 'text-slate-800' : 'text-slate-400'"
                                          x-text="frame.displayName || (fi === 0 ? 'New Component' : 'Sub-component')"></span>
                                </div>
                            </template>
                            <template x-if="cc() && cc().cModal !== null">
                                <div class="flex items-center gap-1">
                                    <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span class="text-sm text-slate-800 font-medium"
                                          x-text="cc().cModal === 'picker' ? 'Select field type' : (cc().cEditingField ? typeStyle(cc().cEditingField.type).label : 'Configure')"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                    <button @click="closeCompBuilder()"
                            class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- ── Field list view (cModal === null) ──────────────── --}}
                <div x-show="cc() && cc().cModal === null">

                    {{-- Name + category --}}
                    <div class="px-6 py-4 border-b border-slate-200 grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1.5">Display name <span class="text-red-500">*</span></label>
                            <input type="text" x-model="cc().displayName"
                                   placeholder="e.g. Hero Section"
                                   class="w-full px-3.5 py-2 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1.5">Category</label>
                            <input type="text" x-model="cc().category"
                                   placeholder="shared"
                                   @input="cc().category = $el.value.toLowerCase().replace(/[^a-z0-9_]/g, '_')"
                                   class="w-full px-3.5 py-2 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 font-mono transition-all">
                        </div>
                    </div>

                    {{-- Fields header --}}
                    <div class="flex items-center justify-between px-6 py-3 border-b border-slate-200">
                        <p class="text-xs text-slate-400"
                           x-text="cc().fields.length + ' field' + (cc().fields.length !== 1 ? 's' : '')"></p>
                        <button @click="compOpenPicker()"
                                class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-medium transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add field
                        </button>
                    </div>

                    {{-- Field rows --}}
                    <div class="overflow-y-auto" style="max-height: calc(100vh - 22rem)">
                        <template x-if="cc().fields.length === 0">
                            <div class="py-12 flex flex-col items-center gap-3 text-slate-400">
                                <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 0v10"/>
                                    </svg>
                                </div>
                                <div class="text-center">
                                    <p class="text-slate-500 font-medium text-sm">No fields yet</p>
                                    <p class="text-xs mt-0.5 text-slate-400">Click "Add field" to define the component structure.</p>
                                </div>
                            </div>
                        </template>
                        <template x-if="cc().fields.length > 0">
                            <div>
                                <template x-for="(field, index) in cc().fields" :key="field.name">
                                    <div class="group flex items-center gap-3 px-6 py-3 border-b border-slate-200/60 hover:bg-slate-50/50 transition-colors">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 text-xs font-mono font-bold"
                                             :class="typeStyle(field.type).bg + ' ' + typeStyle(field.type).text">
                                            <span x-text="typeStyle(field.type).icon"></span>
                                        </div>
                                        <div class="flex-1 min-w-0 flex items-center gap-2 flex-wrap">
                                            <span class="text-slate-800 font-medium font-mono text-sm" x-text="field.name"></span>
                                            <span class="px-1.5 py-0.5 rounded-full text-xs font-medium"
                                                  :class="typeStyle(field.type).badge"
                                                  x-text="typeStyle(field.type).label"></span>
                                            <template x-if="field.required">
                                                <span class="px-1.5 py-0.5 bg-red-500/10 text-red-600 rounded-full text-xs">Required</span>
                                            </template>
                                        </div>
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click="compOpenEdit(index)" title="Edit"
                                                    class="p-1.5 text-slate-400 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                </svg>
                                            </button>
                                            <button @click="compRemoveField(index)" title="Delete"
                                                    class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-slate-100 rounded-lg transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center gap-3 px-6 py-4 border-t border-slate-200">
                        <button @click="closeCompBuilder()"
                                class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-medium transition-colors">
                            Cancel
                        </button>
                        <button @click="saveCompFrame()" :disabled="compSaving || !cc()?.displayName?.trim()"
                                class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 text-white rounded-xl text-sm font-semibold transition-colors flex items-center justify-center gap-2">
                            <svg x-show="compSaving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <span x-text="compSaving ? 'Saving…' : (compStack.length > 1 ? 'Save sub-component' : 'Save & select component')"></span>
                        </button>
                    </div>
                </div>

                {{-- ── Picker panel ────────────────────────────────────── --}}
                <div x-show="cc() && cc().cModal === 'picker'"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100">
                    <div class="p-6 space-y-6 overflow-y-auto" style="max-height:calc(100vh - 14rem)">
                        @foreach($cats as $category => $types)
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-3">{{ $category }}</p>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($types as $ft)
                                <button @click="compSelectType('{{ $ft['type'] }}')"
                                        class="group flex items-center gap-3 p-3.5 bg-slate-50 hover:bg-white border border-slate-200 hover:border-indigo-300 hover:shadow-sm rounded-xl text-left transition-all duration-150">
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
                </div>

                {{-- ── Config panel ────────────────────────────────────── --}}
                <div x-show="cc() && cc().cModal === 'config'"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100">

                    {{-- Config sub-header --}}
                    <div class="flex items-center gap-2.5 px-6 py-4 border-b border-slate-200">
                        <template x-if="cc() && cc().cEditingField">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-mono font-bold flex-shrink-0"
                                 :class="typeStyle(cc().cEditingField.type).bg + ' ' + typeStyle(cc().cEditingField.type).text">
                                <span x-text="typeStyle(cc().cEditingField.type).icon"></span>
                            </div>
                        </template>
                        <div>
                            <p class="text-slate-800 font-bold text-sm"
                               x-text="cc()?.cEditingIndex !== null ? 'Edit field' : 'Configure field'"></p>
                            <p class="text-xs text-slate-400"
                               x-text="cc()?.cEditingField ? typeStyle(cc().cEditingField.type).label : ''"></p>
                        </div>
                    </div>

                    {{-- Config body --}}
                    <div class="overflow-y-auto p-6 space-y-5" style="max-height: calc(100vh - 24rem)">
                        <template x-if="cc() && cc().cEditingField">
                            <div class="space-y-5">

                                {{-- Field name --}}
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-2">
                                        Name <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" x-model="cc().cEditingField.name" placeholder="e.g. title"
                                           class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 font-mono transition-all"
                                           @input="cc().cEditingField.name = $el.value.toLowerCase().replace(/[^a-z0-9_]/g,'')">
                                    <p class="text-xs text-slate-400 mt-1.5">Lowercase letters, numbers and underscores only</p>
                                </div>

                                {{-- Max length --}}
                                <template x-if="cc() && cc().cEditingField && ['string','uid'].includes(cc().cEditingField.type)">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-600 mb-2">Max length</label>
                                        <input type="number" x-model.number="cc().cEditingField.maxLength" min="1" max="65535"
                                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all">
                                    </div>
                                </template>

                                {{-- Enumeration --}}
                                <template x-if="cc() && cc().cEditingField && cc().cEditingField.type === 'enumeration'">
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-600 mb-2">
                                                Values <span class="text-slate-400 font-normal text-xs">(one per line)</span>
                                            </label>
                                            <textarea x-model="cc().cEditingField.enumValues" rows="4"
                                                      placeholder="draft&#10;published&#10;archived"
                                                      class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 font-mono resize-none transition-all"></textarea>
                                        </div>
                                        <div @click="cc().cEditingField.multiple = !cc().cEditingField.multiple"
                                             class="flex items-center justify-between p-4 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors">
                                            <div>
                                                <p class="text-sm font-medium text-slate-800">Allow multiple selection</p>
                                                <p class="text-xs text-slate-400">Users can pick more than one option</p>
                                            </div>
                                            <div class="relative flex-shrink-0">
                                                <div class="w-11 h-6 rounded-full transition-colors" :class="cc().cEditingField.multiple ? 'bg-blue-600' : 'bg-slate-300'">
                                                    <div class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all"
                                                         :class="cc().cEditingField.multiple ? 'left-6' : 'left-1'"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                {{-- Number min/max --}}
                                <template x-if="cc() && cc().cEditingField && ['integer','biginteger','decimal','float'].includes(cc().cEditingField.type)">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-600 mb-2">Min</label>
                                            <input type="number" x-model.number="cc().cEditingField.min"
                                                   class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-600 mb-2">Max</label>
                                            <input type="number" x-model.number="cc().cEditingField.max"
                                                   class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all">
                                        </div>
                                    </div>
                                </template>

                                {{-- Media --}}
                                <template x-if="cc() && cc().cEditingField && cc().cEditingField.type === 'media'">
                                    <div @click="cc().cEditingField.multiple = !cc().cEditingField.multiple"
                                         class="flex items-center justify-between p-4 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors">
                                        <div>
                                            <p class="text-sm font-medium text-slate-800">Allow multiple files</p>
                                            <p class="text-xs text-slate-400">Select more than one file at once</p>
                                        </div>
                                        <div class="relative flex-shrink-0">
                                            <div class="w-11 h-6 rounded-full transition-colors" :class="cc().cEditingField.multiple ? 'bg-blue-600' : 'bg-slate-300'">
                                                <div class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all"
                                                     :class="cc().cEditingField.multiple ? 'left-6' : 'left-1'"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                {{-- Relation --}}
                                <template x-if="cc() && cc().cEditingField && cc().cEditingField.type === 'relation'">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-600 mb-2">Relation type</label>
                                            <select x-model="cc().cEditingField.relation"
                                                    class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all">
                                                <option value="manyToOne">Many to One</option>
                                                <option value="oneToOne">One to One</option>
                                                <option value="oneToMany">One to Many</option>
                                                <option value="manyToMany">Many to Many</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-600 mb-2">Target content type</label>
                                            <select x-model="cc().cEditingField.target"
                                                    class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all">
                                                <option value="">— Select —</option>
                                                @foreach($contentTypes as $ct)
                                                    <option value="{{ $ct['__uid'] }}">{{ $ct['info']['displayName'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </template>

                                {{-- Component / Dynamic Zone --}}
                                <template x-if="cc() && cc().cEditingField && ['component','dynamiczone'].includes(cc().cEditingField.type)">
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-sm font-medium text-slate-600"
                                                   x-text="cc().cEditingField.type === 'dynamiczone' ? 'Allowed components' : 'Component'"></label>
                                            <button type="button" @click="compPushFrame()"
                                                    class="flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-700 font-medium transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Add new component
                                            </button>
                                        </div>
                                        <template x-if="allComponents.length > 0">
                                            <div class="space-y-1 max-h-40 overflow-y-auto">
                                                <template x-for="cat in uniqueCategories()" :key="cat">
                                                    <div>
                                                        <p class="text-xs text-slate-400 px-1 pt-2 pb-1" x-text="cat"></p>
                                                        <template x-for="comp in componentsByCategory(cat)" :key="comp.uid">
                                                            <label class="flex items-center gap-3 p-3 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors">
                                                                <input type="checkbox"
                                                                       :checked="(cc().cEditingField.components || []).includes(comp.uid)"
                                                                       @change="compToggleComponent(comp.uid)"
                                                                       class="rounded bg-slate-200 border-slate-300 text-indigo-600 focus:ring-0">
                                                                <span class="text-sm text-slate-600" x-text="comp.displayName"></span>
                                                            </label>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="allComponents.length === 0">
                                            <p class="text-sm text-slate-400 p-3 bg-slate-100 rounded-xl">
                                                No components yet. Use "Add new component" above.
                                            </p>
                                        </template>
                                        <template x-if="cc() && cc().cEditingField && cc().cEditingField.type === 'component'">
                                            <div @click="cc().cEditingField.repeatable = !cc().cEditingField.repeatable"
                                                 class="flex items-center justify-between p-3 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors mt-2">
                                                <div>
                                                    <p class="text-sm font-medium text-slate-800">Repeatable</p>
                                                    <p class="text-xs text-slate-400">Allow multiple instances</p>
                                                </div>
                                                <div class="relative flex-shrink-0">
                                                    <div class="w-11 h-6 rounded-full transition-colors" :class="cc().cEditingField.repeatable ? 'bg-blue-600' : 'bg-slate-300'">
                                                        <div class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all"
                                                             :class="cc().cEditingField.repeatable ? 'left-6' : 'left-1'"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- Repeater sub-fields --}}
                                <template x-if="cc() && cc().cEditingField && cc().cEditingField.type === 'repeater'">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-600 mb-2">Sub-fields</label>
                                        <div class="space-y-1.5 mb-3">
                                            <template x-for="sf in compGetSubFieldArray()" :key="sf.name">
                                                <div class="flex items-center gap-2 px-3 py-2.5 bg-slate-100 rounded-lg">
                                                    <span class="text-xs font-mono text-slate-800 flex-1" x-text="sf.name"></span>
                                                    <span class="text-xs text-slate-400 bg-slate-200 px-1.5 py-0.5 rounded" x-text="sf.type"></span>
                                                    <button type="button" @click="compRemoveSubField(sf.name)"
                                                            class="text-slate-400 hover:text-red-600 transition-colors ml-1">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="compGetSubFieldArray().length === 0">
                                                <p class="text-xs text-slate-400 italic px-1">No sub-fields yet.</p>
                                            </template>
                                        </div>
                                        <div class="p-3 bg-slate-100 rounded-xl border border-slate-300 space-y-2.5">
                                            <div class="flex gap-2">
                                                <input type="text" x-model="cc().cNewSubFieldName"
                                                       placeholder="field_name"
                                                       @input="cc().cNewSubFieldName = $el.value.toLowerCase().replace(/[^a-z0-9_]/g,'')"
                                                       @keydown.enter.prevent="compAddSubField()"
                                                       class="flex-1 px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-800 text-xs font-mono focus:outline-none focus:border-indigo-500">
                                                <select x-model="cc().cNewSubFieldType"
                                                        class="px-2 py-1.5 bg-white border border-slate-300 rounded-lg text-slate-800 text-xs focus:outline-none focus:border-indigo-500">
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
                                            <button type="button" @click="compAddSubField()" :disabled="!cc().cNewSubFieldName"
                                                    class="w-full py-1.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 text-white rounded-lg text-xs font-medium transition-colors">
                                                Add sub-field
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                {{-- Default value --}}
                                <template x-if="cc() && cc().cEditingField && !['component','dynamiczone','media','relation','richtext','repeater','boolean'].includes(cc().cEditingField.type)">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-600 mb-2">Default value</label>
                                        <input type="text" x-model="cc().cEditingField.default"
                                               class="w-full px-4 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all">
                                    </div>
                                </template>

                                {{-- Advanced toggles --}}
                                <div class="pt-1 space-y-2">
                                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Advanced settings</p>
                                    <div @click="cc().cEditingField.required = !cc().cEditingField.required"
                                         class="flex items-center justify-between p-4 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors">
                                        <div>
                                            <p class="text-sm font-medium text-slate-800">Required</p>
                                            <p class="text-xs text-slate-400">This field must have a value</p>
                                        </div>
                                        <div class="relative flex-shrink-0">
                                            <div class="w-11 h-6 rounded-full transition-colors" :class="cc().cEditingField.required ? 'bg-blue-600' : 'bg-slate-300'">
                                                <div class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all"
                                                     :class="cc().cEditingField.required ? 'left-6' : 'left-1'"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <template x-if="cc() && cc().cEditingField && ['string','text','email','url','uid','integer','biginteger','decimal','float'].includes(cc().cEditingField.type)">
                                        <div @click="cc().cEditingField.unique = !cc().cEditingField.unique"
                                             class="flex items-center justify-between p-4 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors">
                                            <div>
                                                <p class="text-sm font-medium text-slate-800">Unique</p>
                                                <p class="text-xs text-slate-400">No duplicate values allowed</p>
                                            </div>
                                            <div class="relative flex-shrink-0">
                                                <div class="w-11 h-6 rounded-full transition-colors" :class="cc().cEditingField.unique ? 'bg-blue-600' : 'bg-slate-300'">
                                                    <div class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all"
                                                         :class="cc().cEditingField.unique ? 'left-6' : 'left-1'"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <div @click="cc().cEditingField.private = !cc().cEditingField.private"
                                         class="flex items-center justify-between p-4 bg-slate-100 rounded-xl cursor-pointer hover:bg-slate-100/50 transition-colors">
                                        <div>
                                            <p class="text-sm font-medium text-slate-800">Private</p>
                                            <p class="text-xs text-slate-400">Not exposed in the API</p>
                                        </div>
                                        <div class="relative flex-shrink-0">
                                            <div class="w-11 h-6 rounded-full transition-colors" :class="cc().cEditingField.private ? 'bg-blue-600' : 'bg-slate-300'">
                                                <div class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all"
                                                     :class="cc().cEditingField.private ? 'left-6' : 'left-1'"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </template>
                    </div>

                    {{-- Config footer --}}
                    <div class="flex gap-3 px-6 py-4 border-t border-slate-200">
                        <button @click="compBackToPicker()"
                                class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-medium transition-colors">
                            ← Back
                        </button>
                        <button @click="compAddOrUpdateField()"
                                :disabled="!(cc()?.cEditingField?.name)"
                                class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 text-white rounded-xl text-sm font-semibold transition-colors"
                                x-text="cc()?.cEditingIndex !== null ? 'Update field' : 'Add field'">
                        </button>
                    </div>
                </div>

            </div>{{-- /card --}}
        </div>{{-- /centering flex --}}
    </div>{{-- /comp builder modal --}}

</div>

<script>
function fieldBuilder(initialAttributes, uid, initialComponents) {
    const S = {
        string:      { icon: 'Aa',  bg: 'bg-blue-500/20',    text: 'text-blue-600',   badge: 'bg-blue-500/15 text-blue-600',     label: 'Short text' },
        text:        { icon: '¶',   bg: 'bg-blue-500/20',    text: 'text-blue-600',   badge: 'bg-blue-500/15 text-blue-600',     label: 'Long text' },
        richtext:    { icon: 'RT',  bg: 'bg-blue-500/20',    text: 'text-blue-600',   badge: 'bg-blue-500/15 text-blue-600',     label: 'Rich text' },
        integer:     { icon: '123', bg: 'bg-emerald-500/20', text: 'text-emerald-400',badge: 'bg-emerald-500/15 text-emerald-400',label: 'Integer' },
        biginteger:  { icon: '1+',  bg: 'bg-emerald-500/20', text: 'text-emerald-400',badge: 'bg-emerald-500/15 text-emerald-400',label: 'Big integer' },
        decimal:     { icon: '1.2', bg: 'bg-emerald-500/20', text: 'text-emerald-400',badge: 'bg-emerald-500/15 text-emerald-400',label: 'Decimal' },
        float:       { icon: '~1',  bg: 'bg-emerald-500/20', text: 'text-emerald-400',badge: 'bg-emerald-500/15 text-emerald-400',label: 'Float' },
        date:        { icon: '📅',  bg: 'bg-orange-500/20',  text: 'text-orange-400', badge: 'bg-orange-500/15 text-orange-400',  label: 'Date' },
        datetime:    { icon: '🕐',  bg: 'bg-orange-500/20',  text: 'text-orange-400', badge: 'bg-orange-500/15 text-orange-400',  label: 'Date & Time' },
        time:        { icon: '⏱',  bg: 'bg-orange-500/20',  text: 'text-orange-400', badge: 'bg-orange-500/15 text-orange-400',  label: 'Time' },
        boolean:     { icon: '◎',   bg: 'bg-violet-500/20',  text: 'text-violet-400', badge: 'bg-violet-500/15 text-violet-400',  label: 'Boolean' },
        email:       { icon: '@',   bg: 'bg-cyan-500/20',    text: 'text-cyan-400',   badge: 'bg-cyan-500/15 text-cyan-400',     label: 'Email' },
        url:         { icon: '🔗',  bg: 'bg-cyan-500/20',    text: 'text-cyan-400',   badge: 'bg-cyan-500/15 text-cyan-400',     label: 'URL' },
        uid:         { icon: '#',   bg: 'bg-gray-500/20',    text: 'text-slate-500',  badge: 'bg-gray-500/15 text-slate-500',    label: 'UID' },
        json:        { icon: '{}',  bg: 'bg-gray-500/20',    text: 'text-slate-500',  badge: 'bg-gray-500/15 text-slate-500',    label: 'JSON' },
        enumeration: { icon: '≡',   bg: 'bg-purple-500/20',  text: 'text-violet-600', badge: 'bg-purple-500/15 text-violet-600', label: 'Enumeration' },
        media:       { icon: '🖼',  bg: 'bg-pink-500/20',    text: 'text-pink-400',   badge: 'bg-pink-500/15 text-pink-400',     label: 'Media' },
        relation:    { icon: '⟷',  bg: 'bg-yellow-500/20',  text: 'text-amber-600',  badge: 'bg-yellow-500/15 text-amber-600',  label: 'Relation' },
        component:   { icon: '⬡',   bg: 'bg-indigo-500/20',  text: 'text-indigo-400', badge: 'bg-indigo-500/15 text-indigo-400', label: 'Component' },
        dynamiczone: { icon: '⬡+',  bg: 'bg-indigo-500/20',  text: 'text-indigo-400', badge: 'bg-indigo-500/15 text-indigo-400', label: 'Dynamic Zone' },
        repeater:    { icon: '≣',   bg: 'bg-teal-500/20',    text: 'text-teal-400',   badge: 'bg-teal-500/15 text-teal-400',     label: 'Repeater' },
    };

    const toArray  = a => Object.entries(a || {}).map(([name, f]) => ({ name, ...f }));
    const toObject = a => a.reduce((o, f) => { const { name, ...r } = f; o[name] = r; return o; }, {});

    const newFrame = (category = 'shared') => ({
        displayName:      '',
        category,
        fields:           [],
        cModal:           null,
        cEditingField:    null,
        cEditingIndex:    null,
        cNewSubFieldName: '',
        cNewSubFieldType: 'string',
        cNewSubFieldConfig: {},
    });

    return {
        // ── Main modal state ───────────────────────────────────────────
        fields:            toArray(initialAttributes),

        init() {
            this.$nextTick(() => {
                if (typeof Sortable === 'undefined' || !this.$refs.fieldsList) return;
                Sortable.create(this.$refs.fieldsList, {
                    handle:    '.field-drag-handle',
                    animation: 150,
                    onEnd: (evt) => {
                        if (evt.oldIndex === evt.newIndex) return;
                        const moved = this.fields.splice(evt.oldIndex, 1)[0];
                        this.fields.splice(evt.newIndex, 0, moved);
                        talos.markDirty();
                    },
                });
            });
        },
        modal:             null,
        editingField:      null,
        editingIndex:      null,
        saving:            false,
        renames:           {},
        newSubFieldName:   '',
        newSubFieldType:   'string',
        newSubFieldConfig: {},
        allComponents:     Array.isArray(initialComponents) ? initialComponents : [],

        // ── Component builder state ────────────────────────────────────
        compStack:        [],
        compBuilderOpen:  false,
        compSaving:       false,

        // ── Helpers ───────────────────────────────────────────────────
        typeStyle(type) { return S[type] ?? { icon: '?', bg: 'bg-gray-500/20', text: 'text-slate-500', badge: 'bg-gray-500/15 text-slate-500', label: type }; },
        uniqueCategories() { return [...new Set(this.allComponents.map(c => c.category))]; },
        componentsByCategory(cat) { return this.allComponents.filter(c => c.category === cat); },

        // ── Main modal methods ────────────────────────────────────────
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

        // ── Component builder methods ─────────────────────────────────
        cc() { return this.compStack[this.compStack.length - 1] ?? null; },

        openCompBuilder() {
            this.compStack = [newFrame('shared')];
            this.compBuilderOpen = true;
        },

        closeCompBuilder() {
            this.compStack = [];
            this.compBuilderOpen = false;
        },

        compOpenPicker() {
            const c = this.cc();
            if (!c) return;
            c.cEditingField = null;
            c.cEditingIndex = null;
            c.cModal = 'picker';
        },

        compCloseModal() {
            const c = this.cc();
            if (!c) return;
            c.cModal = null;
            c.cEditingField = null;
            c.cEditingIndex = null;
        },

        compBackToPicker() {
            const c = this.cc();
            if (!c) return;
            c.cModal = 'picker';
            c.cEditingField = null;
            c.cEditingIndex = null;
        },

        compSelectType(type) {
            const c = this.cc();
            if (!c) return;
            c.cEditingField = { type, name: '', required: false, unique: false, private: false };
            if (type === 'repeater') c.cEditingField.subFields = {};
            if (type === 'relation') { c.cEditingField.relation = 'manyToOne'; c.cEditingField.target = ''; }
            c.cEditingIndex = null;
            c.cModal = 'config';
        },

        compOpenEdit(index) {
            const c = this.cc();
            if (!c) return;
            c.cEditingField = { ...c.fields[index], _originalName: c.fields[index].name };
            c.cEditingIndex = index;
            c.cModal = 'config';
        },

        compRemoveField(index) {
            const c = this.cc();
            if (c) c.fields.splice(index, 1);
        },

        compToggleComponent(uid) {
            const c = this.cc();
            if (!c?.cEditingField) return;
            if (!c.cEditingField.components) c.cEditingField.components = [];
            const i = c.cEditingField.components.indexOf(uid);
            i === -1 ? c.cEditingField.components.push(uid) : c.cEditingField.components.splice(i, 1);
        },

        compAddOrUpdateField() {
            const c = this.cc();
            if (!c?.cEditingField?.name) return;
            if (c.fields.some((f, i) => f.name === c.cEditingField.name && i !== c.cEditingIndex)) {
                talos.toast('A field with this name already exists.', 'error');
                return;
            }
            if (c.cEditingIndex !== null) {
                const saved = { ...c.cEditingField };
                delete saved._originalName;
                c.fields[c.cEditingIndex] = saved;
            } else {
                const f = { ...c.cEditingField };
                delete f._originalName;
                c.fields.push(f);
            }
            c.cModal = null;
            c.cEditingField = null;
            c.cEditingIndex = null;
        },

        compGetSubFieldArray() {
            const c = this.cc();
            if (!c?.cEditingField?.subFields) return [];
            return Object.entries(c.cEditingField.subFields).map(([name, f]) => ({ name, ...f }));
        },

        compAddSubField() {
            const c = this.cc();
            if (!c) return;
            const n = c.cNewSubFieldName.trim();
            if (!n) return;
            if (!c.cEditingField.subFields) c.cEditingField.subFields = {};
            if (c.cEditingField.subFields[n]) { talos.toast('Sub-field "' + n + '" already exists.', 'error'); return; }
            c.cEditingField.subFields = { ...c.cEditingField.subFields, [n]: { type: c.cNewSubFieldType, ...c.cNewSubFieldConfig } };
            c.cNewSubFieldName = '';
            c.cNewSubFieldConfig = {};
        },

        compRemoveSubField(name) {
            const c = this.cc();
            if (!c) return;
            const u = { ...c.cEditingField.subFields };
            delete u[name];
            c.cEditingField = { ...c.cEditingField, subFields: u };
        },

        compPushFrame() {
            const parentCategory = this.cc()?.category ?? 'shared';
            this.compStack.push(newFrame(parentCategory));
        },

        compPopFrame() {
            if (this.compStack.length > 1) this.compStack.pop();
            else this.closeCompBuilder();
        },

        async saveCompFrame() {
            const c = this.cc();
            if (!c) return;
            const displayName = c.displayName.trim();
            const category = (c.category.trim() || 'shared').toLowerCase().replace(/[^a-z0-9_]/g, '_');
            if (!displayName) { talos.toast('Component name is required.', 'error'); return; }

            this.compSaving = true;
            try {
                const res = await fetch('{{ route('talos.components.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ info: { displayName }, category, attributes: toObject(c.fields) }),
                });
                const data = await res.json();
                if (!res.ok) { talos.toast(data.message || 'Failed to create component.', 'error'); return; }

                this.allComponents.push({ uid: data.uid, displayName: data.displayName, category: data.category });

                if (this.compStack.length === 1) {
                    // Top-level: auto-select in the parent field (main modal)
                    if (this.editingField) {
                        if (!this.editingField.components) this.editingField.components = [];
                        this.editingField.components.push(data.uid);
                    }
                    this.closeCompBuilder();
                    talos.toast('Component "' + data.displayName + '" created and selected.', 'success');
                } else {
                    // Sub-component: pop frame and select in parent frame's editing field
                    this.compStack.pop();
                    const parent = this.cc();
                    if (parent?.cEditingField) {
                        if (!parent.cEditingField.components) parent.cEditingField.components = [];
                        parent.cEditingField.components.push(data.uid);
                    }
                    talos.toast('Sub-component "' + data.displayName + '" created and selected.', 'success');
                }
            } catch (e) {
                talos.toast('Network error. Please try again.', 'error');
            } finally {
                this.compSaving = false;
            }
        },

        // ── Schema save ───────────────────────────────────────────────
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
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endsection
