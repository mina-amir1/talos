@extends('talos.layouts.app')

@section('title', $component['info']['displayName'] . ' — Component Builder')
@section('header', $component['info']['displayName'])

@section('header-actions')
    <span class="text-xs bg-gray-800 text-gray-400 px-2 py-1 rounded font-mono">{{ $uid }}</span>
@endsection

@section('content')
@php
    $fieldTypes = [
        ['type' => 'string',      'label' => 'Short text',  'icon' => 'T'],
        ['type' => 'text',        'label' => 'Long text',   'icon' => '¶'],
        ['type' => 'richtext',    'label' => 'Rich text',   'icon' => 'RT'],
        ['type' => 'integer',     'label' => 'Integer',     'icon' => '1'],
        ['type' => 'decimal',     'label' => 'Decimal',     'icon' => '1.2'],
        ['type' => 'boolean',     'label' => 'Boolean',     'icon' => '⊙'],
        ['type' => 'email',       'label' => 'Email',       'icon' => '@'],
        ['type' => 'url',         'label' => 'URL',         'icon' => '🔗'],
        ['type' => 'date',        'label' => 'Date',        'icon' => '📅'],
        ['type' => 'datetime',    'label' => 'DateTime',    'icon' => '🕐'],
        ['type' => 'media',       'label' => 'Media',       'icon' => '🖼'],
        ['type' => 'json',        'label' => 'JSON',        'icon' => '{}'],
        ['type' => 'enumeration', 'label' => 'Enumeration', 'icon' => '≡'],
        ['type' => 'repeater',    'label' => 'Repeater',    'icon' => '≣'],
    ];
@endphp

<div x-data="fieldBuilder({{ json_encode($component['attributes'] ?? []) }}, '{{ $uid }}', 'component')"
     class="flex gap-6 h-[calc(100vh-120px)]">

    {{-- Fields list --}}
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

        <div class="flex-1 overflow-y-auto p-4 space-y-2">
            <template x-if="fields.length === 0">
                <div class="h-full flex flex-col items-center justify-center text-gray-600 gap-3">
                    <p class="text-sm">No fields yet. Click "Add field" to start.</p>
                </div>
            </template>

            <template x-for="(field, index) in fields" :key="field.name">
                <div class="flex items-center gap-3 p-3 bg-gray-800 rounded-lg border border-transparent hover:border-gray-700 group">
                    <div class="w-9 h-9 bg-gray-700 rounded-lg flex items-center justify-center text-xs font-mono text-gray-300 flex-shrink-0"
                         x-text="getTypeIcon(field.type)"></div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-white" x-text="field.name"></p>
                        <p class="text-xs text-gray-500" x-text="field.type"></p>
                    </div>
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="editField(index)" class="p-1.5 text-gray-500 hover:text-white hover:bg-gray-700 rounded">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                        <button @click="removeField(index)" class="p-1.5 text-gray-500 hover:text-red-400 hover:bg-gray-700 rounded">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="px-5 py-4 border-t border-gray-800 flex justify-end">
            <button @click="save()" :disabled="saving"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                <svg x-show="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="saving ? 'Saving…' : 'Save component'"></span>
            </button>
        </div>
    </div>

    {{-- Field picker --}}
    <div class="w-80 flex flex-col">
        <div x-show="showPicker && !editingField" class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden flex flex-col h-full">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
                <h2 class="text-white font-semibold">Field type</h2>
                <button @click="showPicker = false" class="text-gray-500 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-3 grid grid-cols-2 gap-2 content-start">
                @foreach($fieldTypes as $ft)
                    <button @click="selectType('{{ $ft['type'] }}')"
                            class="flex items-center gap-2 p-3 bg-gray-800 hover:bg-gray-700 rounded-lg text-left transition-colors">
                        <span class="w-8 h-8 bg-gray-700 rounded flex items-center justify-center text-xs font-mono text-gray-300 flex-shrink-0">
                            {{ $ft['icon'] }}
                        </span>
                        <span class="text-sm text-white">{{ $ft['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div x-show="editingField" class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden flex flex-col h-full">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
                <h2 class="text-white font-semibold" x-text="editingIndex !== null ? 'Edit field' : 'Add field'"></h2>
                <button @click="cancelEdit()" class="text-gray-500 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Field name *</label>
                    <input type="text" x-model="editingField.name" placeholder="e.g. url"
                           class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm font-mono focus:outline-none focus:border-blue-500"
                           @input="editingField.name = $el.value.toLowerCase().replace(/[^a-z0-9_]/g,'')">
                </div>

                {{-- Repeater sub-fields --}}
                <template x-if="editingField.type === 'repeater'">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-400">Sub-fields</label>
                        <div class="space-y-1.5">
                            <template x-for="sf in getSubFieldArray()" :key="sf.name">
                                <div class="flex items-center gap-2 px-3 py-2 bg-gray-800 rounded-lg">
                                    <span class="text-xs font-mono text-white flex-1" x-text="sf.name"></span>
                                    <span class="text-xs text-gray-500 bg-gray-700 px-1.5 py-0.5 rounded" x-text="sf.type"></span>
                                    <button type="button" @click="removeSubField(sf.name)" class="text-gray-600 hover:text-red-400">
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
                                <input type="text" x-model="newSubFieldName" placeholder="field_name"
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
                </template>

                <div class="space-y-2">
                    <label class="flex items-center justify-between p-3 bg-gray-800 rounded-lg cursor-pointer">
                        <p class="text-sm text-white">Required</p>
                        <input type="checkbox" x-model="editingField.required" class="rounded text-blue-600 focus:ring-0 bg-gray-700 border-gray-600">
                    </label>
                    <label class="flex items-center justify-between p-3 bg-gray-800 rounded-lg cursor-pointer">
                        <p class="text-sm text-white">Unique</p>
                        <input type="checkbox" x-model="editingField.unique" class="rounded text-blue-600 focus:ring-0 bg-gray-700 border-gray-600">
                    </label>
                </div>
            </div>
            <div class="px-5 py-4 border-t border-gray-800 flex gap-3">
                <button @click="cancelEdit()" class="flex-1 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium">Cancel</button>
                <button @click="addOrUpdateField()" :disabled="!editingField.name"
                        class="flex-1 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white rounded-lg text-sm font-semibold"
                        x-text="editingIndex !== null ? 'Update' : 'Add'"></button>
            </div>
        </div>

        <div x-show="!showPicker && !editingField"
             class="flex-1 bg-gray-900 border border-gray-800 rounded-xl flex items-center justify-center text-gray-600 p-6">
            <p class="text-sm text-center">Click "Add field" to choose a field type.</p>
        </div>
    </div>
</div>

<script>
function fieldBuilder(initialAttributes, uid, mode = 'contentType') {
    const icons = { string:'T', text:'¶', richtext:'RT', integer:'1', decimal:'1.2', float:'~1',
                    boolean:'⊙', email:'@', url:'🔗', uid:'#', json:'{}', enumeration:'≡',
                    media:'🖼', relation:'⟷', component:'⬡', dynamiczone:'⬡+', date:'📅', datetime:'🕐',
                    repeater:'≣' };

    const toArray = a => Object.entries(a||{}).map(([name, f]) => ({name,...f}));
    const toObj   = a => a.reduce((o,f)=>{const{name,...r}=f;o[name]=r;return o},{});

    return {
        fields: toArray(initialAttributes),
        showPicker: false, editingField: null, editingIndex: null, saving: false,
        newSubFieldName: '', newSubFieldType: 'string', newSubFieldConfig: {},

        getTypeIcon(t){ return icons[t]??'?'; },

        selectType(t){
            this.editingField = { type:t, name:'', required:false, unique:false };
            if (t === 'repeater') this.editingField.subFields = {};
            this.editingIndex = null;
            this.showPicker   = false;
        },

        editField(i){ this.editingField={...this.fields[i]}; this.editingIndex=i; this.showPicker=false; },
        removeField(i){ if(confirm('Remove?'))this.fields.splice(i,1); },
        cancelEdit(){ this.editingField=null; this.editingIndex=null; },

        addOrUpdateField(){
            if(!this.editingField.name)return;
            const dup=this.fields.some((f,i)=>f.name===this.editingField.name&&i!==this.editingIndex);
            if(dup){alert('Name already exists.');return;}
            if(this.editingIndex!==null)this.fields[this.editingIndex]={...this.editingField};
            else this.fields.push({...this.editingField});
            this.cancelEdit();
        },

        getSubFieldArray(){
            if(!this.editingField?.subFields) return [];
            return Object.entries(this.editingField.subFields).map(([name,f])=>({name,...f}));
        },
        addSubField(){
            const n=this.newSubFieldName.trim();
            if(!n)return;
            if(!this.editingField.subFields)this.editingField.subFields={};
            if(this.editingField.subFields[n]){alert('Sub-field "'+n+'" already exists.');return;}
            const fieldDef={type:this.newSubFieldType,...this.newSubFieldConfig};
            this.editingField.subFields={...this.editingField.subFields,[n]:fieldDef};
            this.newSubFieldName='';
            this.newSubFieldConfig={};
        },
        removeSubField(name){
            if(!this.editingField?.subFields)return;
            const updated={...this.editingField.subFields};
            delete updated[name];
            this.editingField={...this.editingField,subFields:updated};
        },

        async save(){
            this.saving=true;
            const prefix='{{ config('talos.admin_prefix','talos') }}';
            const route=mode==='component'?`/${prefix}/components/${uid}`:`/${prefix}/content-type-builder/${uid}`;
            try{
                const r=await fetch(route,{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({attributes:toObj(this.fields)})});
                const d=await r.json();
                if(d.success){
                    const el=document.createElement('div');
                    el.className='fixed bottom-6 right-6 bg-green-700 text-white px-5 py-3 rounded-lg shadow-xl text-sm font-medium z-50';
                    el.textContent='Saved successfully!';
                    document.body.appendChild(el);
                    setTimeout(()=>el.remove(),3000);
                }else alert('Error: '+(d.error||'Unknown'));
            }catch(e){alert('Network error.');}
            this.saving=false;
        }
    };
}
</script>
@endsection
