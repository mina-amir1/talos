<label class="cursor-pointer" :class="$store._mlib.uploading && 'pointer-events-none'">
    <div class="flex items-center gap-1.5 text-sm"
         :class="$store._mlib.uploading ? 'text-blue-400' : 'text-blue-600 hover:text-blue-700'">
        <template x-if="!$store._mlib.uploading">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
        </template>
        <template x-if="$store._mlib.uploading">
            <svg class="w-4 h-4 flex-shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
        </template>
        <span x-text="$store._mlib.uploading ? $store._mlib.uploadProgress + '%' : 'Upload'"></span>
    </div>
    <div x-show="$store._mlib.uploading" class="mt-1 h-1 w-24 bg-slate-200 rounded-full overflow-hidden">
        <div class="h-full bg-blue-500 rounded-full transition-all duration-150"
             :style="'width:'+$store._mlib.uploadProgress+'%'"></div>
    </div>
    <input type="file" accept="image/*,video/*,application/pdf" class="sr-only"
           :disabled="$store._mlib.uploading"
           @change="if($store._mlib.uploading||!$event.target.files[0])return;$store._mlib.upload($event.target.files[0]).then(()=>{$event.target.value=''}).catch(()=>{$event.target.value=''})">
</label>
