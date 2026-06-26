<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
// ── Repeater Alpine component ─────────────────────────────────────────────────
function repeaterField(initialRows, emptyRow) {
    return {
        rows:     Array.isArray(initialRows) ? initialRows : [],
        emptyRow: emptyRow || {},
        open:     [],

        init() {
            if (this.rows.length > 0) this.open = [0];
        },

        addRow() {
            const idx = this.rows.length;
            this.rows.push(Object.assign({}, this.emptyRow));
            this.open.push(idx);
        },

        removeRow(idx) {
            this.rows.splice(idx, 1);
            this.open = this.open
                .filter(i => i !== idx)
                .map(i => i > idx ? i - 1 : i);
        },

        toggle(idx) {
            const pos = this.open.indexOf(idx);
            if (pos === -1) { this.open.push(idx); }
            else            { this.open.splice(pos, 1); }
        },

        isOpen(idx) { return this.open.includes(idx); },

        preview(row) {
            const val = Object.values(row).find(v => v !== null && v !== '' && typeof v === 'string');
            return val ? String(val).substring(0, 60) : '—';
        },

        getBool(row, field)    { return !!row[field]; },
        toggleBool(row, field) { row[field] = !row[field]; },

        moveUp(idx) {
            if (idx === 0) return;
            const temp = this.rows.splice(idx, 1)[0];
            this.rows.splice(idx - 1, 0, temp);
            this.open = this.open.map(i => i === idx ? idx - 1 : i === idx - 1 ? idx : i);
        },
        moveDown(idx) {
            if (idx === this.rows.length - 1) return;
            const temp = this.rows.splice(idx, 1)[0];
            this.rows.splice(idx + 1, 0, temp);
            this.open = this.open.map(i => i === idx ? idx + 1 : i === idx + 1 ? idx : i);
        },
    };
}

// ── Quill toolbar config ──────────────────────────────────────────────────────
window._talosQuillToolbar = [
    [{ header: [1, 2, 3, 4, 5, 6, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ color: [] }, { background: [] }],
    [{ list: 'ordered' }, { list: 'bullet' }],
    [{ indent: '-1' }, { indent: '+1' }],
    [{ align: [] }],
    ['blockquote', 'code-block'],
    ['link', 'image'],
    ['clean'],
];

// ── Quill top-level rich-text editors ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-quill-for]').forEach(function (container) {
        var fieldName   = container.dataset.quillFor;
        var hiddenInput = document.getElementById('quill-input-' + fieldName);

        var quill = new Quill(container, {
            theme:   'snow',
            modules: { toolbar: window._talosQuillToolbar },
            placeholder: 'Write something…',
        });

        if (hiddenInput && hiddenInput.value) {
            quill.clipboard.dangerouslyPasteHTML(hiddenInput.value);
            quill.history.clear();
        }

        quill.on('text-change', function () {
            if (hiddenInput) {
                var html = quill.root.innerHTML;
                hiddenInput.value = html === '<p><br></p>' ? '' : html;
            }
        });
    });

    var form = document.getElementById('content-form');
    if (form) {
        form.addEventListener('submit', function () {
            document.querySelectorAll('[data-quill-for]').forEach(function (container) {
                var fieldName   = container.dataset.quillFor;
                var hiddenInput = document.getElementById('quill-input-' + fieldName);
                var quill       = Quill.find(container);
                if (quill && hiddenInput) {
                    var html = quill.root.innerHTML;
                    hiddenInput.value = html === '<p><br></p>' ? '' : html;
                }
            });
        });
    }
});
</script>

<script>
// ── Slug auto-generation ──────────────────────────────────────────────────────
(function () {
    const slugInput = document.getElementById('talos-slug-input');
    if (! slugInput) return;

    function slugify(str) {
        return str.toLowerCase().trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    const form        = document.getElementById('content-form');
    const sourceField = form
        ? [...form.querySelectorAll('input[type=text], input[type=email], input[type=url], textarea')]
              .find(el => el !== slugInput && el.name && ! el.name.startsWith('_'))
        : null;

    let manuallyEdited = slugInput.value.length > 0;

    if (sourceField) {
        sourceField.addEventListener('input', function () {
            if (! manuallyEdited) {
                slugInput.value = slugify(this.value);
            }
        });
    }

    slugInput.addEventListener('input', function () {
        manuallyEdited = true;
    });

    slugInput.addEventListener('blur', function () {
        this.value = slugify(this.value);
    });
})();
</script>

<script>
// ── Alpine media library store ────────────────────────────────────────────────
document.addEventListener('alpine:init', () => {
    Alpine.store('_mlib', {
        folder:         null,
        items:          @json($mediaItemsForJs),
        folders:        @json($mediaFolders),
        loading:        false,
        uploading:      false,
        uploadProgress: 0,
        uploadUrl:      '{{ route("talos.media.upload") }}',

        async refresh() {
            if (this.loading) return;
            this.loading = true;
            try {
                const r = await fetch('{{ route("talos.media.items") }}');
                const j = await r.json();
                this.items   = j.data;
                this.folders = j.folders ?? [...new Set(j.data.map(i => i.folder).filter(Boolean))].sort();
            } finally { this.loading = false; }
        },

        async createFolder(name) {
            const slug = name.replace(/[^a-z0-9]/gi, '-').replace(/-+/g, '-').replace(/^-|-$/g, '').toLowerCase();
            if (!slug) return;
            const parent  = this.folder || '';
            const newPath = parent ? parent + '/' + slug : slug;
            const r = await fetch('{{ route("talos.media.folders.store") }}', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ name: slug, parent }),
            });
            if (r.ok) {
                if (!this.folders.includes(newPath))
                    this.folders = [...this.folders, newPath].sort();
                this.folder = newPath;
            }
        },

        upload(file) {
            const store = this;
            return new Promise((resolve, reject) => {
                store.uploading      = true;
                store.uploadProgress = 0;
                const fd = new FormData();
                fd.append('file',   file);
                fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
                fd.append('folder', store.folder || '');
                const xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', e => {
                    if (e.lengthComputable)
                        store.uploadProgress = Math.round(e.loaded / e.total * 100);
                });
                xhr.addEventListener('load', async () => {
                    store.uploading      = false;
                    store.uploadProgress = 0;
                    if (xhr.status >= 200 && xhr.status < 300) {
                        await store.refresh();
                        const res = JSON.parse(xhr.responseText);
                        if (res.data && res.data.status === 'converting')
                            setTimeout(() => store.refresh(), 5000);
                        resolve(res);
                    } else {
                        reject(new Error('Upload failed (' + xhr.status + ')'));
                    }
                });
                xhr.addEventListener('error', () => {
                    store.uploading      = false;
                    store.uploadProgress = 0;
                    reject(new Error('Upload failed'));
                });
                xhr.open('POST', store.uploadUrl);
                xhr.send(fd);
            });
        },
    });
});

// ── Alpine helper functions ───────────────────────────────────────────────────
function enumPicker(opts, initialSelected) {
    return {
        opts:     opts || [],
        selected: initialSelected || [],
        toggle(v) {
            const i = this.selected.indexOf(v);
            i === -1 ? this.selected.push(v) : this.selected.splice(i, 1);
        },
    };
}

function enumArr(v)         { return Array.isArray(v) ? v : (v ? [v] : []); }
function enumToggle(cur, v) { const a = enumArr(cur).slice(); const i = a.indexOf(v); i === -1 ? a.push(v) : a.splice(i, 1); return a; }

function relPicker(initialEntries, initialSelected) {
    return {
        entries:  initialEntries  || [],
        selected: initialSelected || [],
        search:   '',
        filtered() {
            const q = this.search.trim().toLowerCase();
            return q ? this.entries.filter(e => e.label.toLowerCase().includes(q)) : this.entries;
        },
        toggle(id) {
            const i = this.selected.indexOf(id);
            i === -1 ? this.selected.push(id) : this.selected.splice(i, 1);
        },
        labelFor(id) {
            const e = this.entries.find(e => e.id === id);
            return e ? e.label : '#' + id;
        },
    };
}
</script>

@if($isEdit)
<script>
(function () {
    const KEY  = 'talos_scroll_{{ $uid }}_{{ $entry->id }}';
    const main = document.getElementById('talos-main');
    const form = document.getElementById('content-form');

    if (form && main) {
        form.addEventListener('submit', () => sessionStorage.setItem(KEY, main.scrollTop));
    }

    const saved = sessionStorage.getItem(KEY);
    if (saved !== null && main) {
        sessionStorage.removeItem(KEY);
        document.addEventListener('DOMContentLoaded', () => {
            main.scrollTop = parseInt(saved, 10);
        });
    }
})();
</script>
@endif
