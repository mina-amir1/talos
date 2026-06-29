@php
    $selectedEvents = old('events',           $rule?->events ?? []);
    $selectedUid    = old('content_type_uid', $rule?->content_type_uid ?? '');
    $existingFields = old('fields',           $rule?->fields ?? []);
    $recipientStr   = old('recipients',       $rule ? implode("\n", $rule->recipients) : '');
    $collOpts       = collect($contentTypes)->map(fn($l, $v) => ['v' => $v, 'l' => $l])->values()->all();

    $eventColors = [
        'entry.create'    => 'emerald',
        'entry.update'    => 'blue',
        'entry.delete'    => 'red',
        'entry.publish'   => 'violet',
        'entry.unpublish' => 'slate',
    ];
@endphp

<div class="space-y-4">

    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Rule name</label>
        <input type="text" name="name"
               value="{{ old('name', $rule?->name) }}"
               placeholder="e.g. Notify editor on new article"
               required
               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    {{-- Events: multi-select checkboxes --}}
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-2">
            Trigger on
            @error('events')<span class="text-red-500 font-normal ml-1">{{ $message }}</span>@enderror
        </label>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            @foreach($events as $val => $label)
            @php $color = $eventColors[$val] ?? 'slate'; @endphp
            <label class="flex items-center gap-2 border border-slate-200 rounded-lg px-3 py-2 cursor-pointer hover:bg-slate-50 transition-colors has-[:checked]:border-{{ $color }}-400 has-[:checked]:bg-{{ $color }}-50">
                <input type="checkbox" name="events[]" value="{{ $val }}"
                       {{ in_array($val, $selectedEvents) ? 'checked' : '' }}
                       class="w-3.5 h-3.5 rounded border-slate-300 text-{{ $color }}-600 focus:ring-{{ $color }}-500 focus:ring-offset-0">
                <span class="text-xs font-medium text-slate-700">{{ $label }}</span>
            </label>
            @endforeach
        </div>
    </div>

    {{-- Collection --}}
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Collection <span class="text-slate-400">(or All)</span></label>
            <div class="relative"
                 x-data='notifCollDrop(@json($selectedUid), @json($collOpts))'
                 @click.outside="open = false">
                <input type="hidden" name="content_type_uid" :value="value">
                <button type="button" @click="open = !open"
                        class="w-full flex items-center justify-between gap-2 border rounded-lg px-3 py-2 text-sm bg-white text-left focus:outline-none transition-colors"
                        :class="open ? 'border-blue-500 ring-2 ring-blue-500' : 'border-slate-200 hover:border-slate-300'">
                    <span class="truncate"
                          :class="value ? 'text-slate-800' : 'text-slate-400'"
                          x-text="label || 'All collections'"></span>
                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 transition-transform duration-150"
                         :class="open ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" x-cloak
                     class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg py-1 max-h-52 overflow-y-auto">
                    <button type="button" @click="pick('')"
                            class="w-full text-left px-3 py-2 text-sm flex items-center gap-2 transition-colors"
                            :class="value === '' ? 'text-blue-600 bg-blue-50 font-medium' : 'text-slate-700 hover:bg-slate-50'">
                        <svg x-show="value === ''" class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-show="value !== ''" class="w-3.5 flex-shrink-0"></span>
                        All collections
                    </button>
                    <template x-for="o in opts" :key="o.v">
                        <button type="button" @click="pick(o.v)"
                                class="w-full text-left px-3 py-2 text-sm flex items-center gap-2 transition-colors"
                                :class="value === o.v ? 'text-blue-600 bg-blue-50 font-medium' : 'text-slate-700 hover:bg-slate-50'">
                            <svg x-show="value === o.v" class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span x-show="value !== o.v" class="w-3.5 flex-shrink-0"></span>
                            <span x-text="o.l"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">
            Recipients
            <span class="font-normal text-slate-400">(one email per line)</span>
        </label>
        <textarea name="recipients" rows="3" required
                  placeholder="editor@example.com&#10;manager@example.com"
                  class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono resize-y">{{ $recipientStr }}</textarea>
    </div>

    {{-- Field picker --}}
    <div x-data='notifFieldPicker(@json($existingFields), @json($selectedUid))'
         x-init="init()"
         @notif-coll-pick.window="onPick($event)">

        <label class="block text-xs font-medium text-slate-600 mb-1">
            Fields to include in email
            <span class="font-normal text-slate-400">(leave empty for all)</span>
        </label>

        <div x-show="fields.length > 0" class="border border-slate-200 rounded-lg p-3 space-y-1.5 max-h-48 overflow-y-auto">
            <template x-for="f in fields" :key="f">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" :name="'fields[]'" :value="f"
                           :checked="selected.includes(f)"
                           @change="selected.includes(f) ? selected.splice(selected.indexOf(f), 1) : selected.push(f)"
                           class="w-3.5 h-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-slate-700 font-mono" x-text="f"></span>
                </label>
            </template>
        </div>

        <p x-show="fields.length === 0" class="text-xs text-slate-400 mt-1">Select a specific collection to pick fields.</p>
    </div>

</div>

{{-- Schema data written once to window so it doesn't go through Blade's HTML escaping --}}
<script>
if (!window.__talosSchemas) {
    window.__talosSchemas = {!! json_encode(app(\App\Services\ContentTypeService::class)->all()) !!};
}

function notifCollDrop(initial, opts) {
    return {
        open:  false,
        value: initial,
        opts:  opts,
        get label() { return (this.opts.find(o => o.v === this.value) || {}).l || ''; },
        pick(uid) {
            this.value = uid;
            this.open  = false;
            this.$el.dispatchEvent(new CustomEvent('notif-coll-pick', {
                bubbles: true,
                detail:  { uid, srcEl: this.$el },
            }));
        },
    };
}

function notifFieldPicker(initialSelected, initialUid) {
    return {
        fields:   [],
        selected: initialSelected || [],

        init() {
            if (initialUid) this.load(initialUid);
        },

        onPick(e) {
            if (!this.$el.closest('form')?.contains(e.detail.srcEl)) return;
            this.selected = [];
            if (e.detail.uid) this.load(e.detail.uid);
            else              this.fields = [];
        },

        load(uid) {
            const schema = (window.__talosSchemas || []).find(s => s['__uid'] === uid);
            this.fields  = schema ? Object.keys(schema.attributes || {}) : [];
        },
    };
}
</script>
