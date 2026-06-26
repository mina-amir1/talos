<input type="hidden" name="{{ $name }}"
       value="{{ is_array($value) ? json_encode($value) : $value }}">
<div class="p-4 bg-slate-100 rounded-lg border border-dashed border-slate-300 text-center">
    <p class="text-sm text-slate-400">Dynamic Zone — populate via API.</p>
</div>
