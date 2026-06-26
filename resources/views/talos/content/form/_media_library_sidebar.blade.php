<div class="w-48 border-r border-slate-200 flex-shrink-0 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto py-2">
        <button type="button"
                @click="$store._mlib.folder = null"
                :class="$store._mlib.folder === null ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'"
                class="w-full text-left px-4 py-2.5 text-sm transition-colors">
            All media
        </button>
        <template x-for="_mf in $store._mlib.folders" :key="_mf">
            <button type="button"
                    @click="$store._mlib.folder = _mf"
                    :class="_mf === $store._mlib.folder ? 'bg-blue-50 text-blue-600 font-medium' : 'text-slate-600 hover:bg-slate-50'"
                    class="w-full text-left px-4 py-2.5 text-sm truncate transition-colors flex items-center gap-2">
                <svg class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                </svg>
                <span x-text="_mf.split('/').pop()"></span>
            </button>
        </template>
    </div>

    <div class="border-t border-slate-200 p-2" x-data="{ _nf: '', _nfOpen: false }">
        <div x-show="!_nfOpen">
            <button type="button" @click="_nfOpen = true"
                    class="w-full flex items-center gap-1 px-2 py-1.5 text-xs text-slate-400 hover:text-blue-600 hover:bg-slate-50 rounded transition-colors">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New folder
            </button>
        </div>
        <div x-show="_nfOpen" class="flex gap-1">
            <input type="text" x-model="_nf"
                   @keydown.enter.prevent="if(_nf.trim()) $store._mlib.createFolder(_nf.trim()).then(()=>{ _nf=''; _nfOpen=false; })"
                   @keydown.escape="_nfOpen=false; _nf=''"
                   placeholder="folder-name"
                   class="flex-1 min-w-0 px-2 py-1 text-xs border border-slate-300 rounded bg-white focus:outline-none focus:border-blue-400">
            <button type="button"
                    @click="if(_nf.trim()) $store._mlib.createFolder(_nf.trim()).then(()=>{ _nf=''; _nfOpen=false; })"
                    class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
            <button type="button" @click="_nfOpen=false; _nf=''"
                    class="px-2 py-1 text-slate-400 hover:text-slate-600 text-xs rounded hover:bg-slate-100">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
</div>
