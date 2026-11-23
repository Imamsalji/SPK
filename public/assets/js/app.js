(function () {
    'use strict';

    var root = document.documentElement;
    var body = document.body;

    /* ---------- Sidebar (mobile) ---------- */
    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            body.classList.toggle('sidebar-open');
        });
    });
    document.querySelectorAll('[data-sidebar-close]').forEach(function (el) {
        el.addEventListener('click', function () {
            body.classList.remove('sidebar-open');
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            body.classList.remove('sidebar-open');
        }
    });

    /* ---------- Tema terang/gelap ---------- */
    function syncThemeIcon() {
        var dark = root.getAttribute('data-bs-theme') === 'dark';
        document.querySelectorAll('[data-theme-icon]').forEach(function (icon) {
            icon.className = 'bi ' + (dark ? 'bi-sun' : 'bi-moon-stars');
        });
    }
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-bs-theme', next);
            try { localStorage.setItem('theme', next); } catch (e) {}
            syncThemeIcon();
        });
    });
    syncThemeIcon();

    /* ---------- Tampilkan/sembunyikan password ---------- */
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-toggle-password'));
            if (!input) { return; }
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('i').className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
        });
    });

    /* ---------- Loading state pada form ---------- */
    var loadingButtons = [];

    document.querySelectorAll('form[data-loading]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('[type="submit"]');
            if (!btn || btn.classList.contains('is-loading')) { return; }
            loadingButtons.push({ el: btn, html: btn.innerHTML });
            btn.classList.add('is-loading');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
                (form.getAttribute('data-loading-text') || 'Memproses...');
        });
    });

    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) { return; }
        loadingButtons.forEach(function (item) {
            item.el.classList.remove('is-loading');
            item.el.innerHTML = item.html;
        });
        loadingButtons = [];
    });

    /* =====================================================
       TAHAP 4
       ===================================================== */

    /* ---------- Modal tambah/ubah ---------- */
    function setModalMode(modal, mode, id) {
        var form = modal.querySelector('form');
        var edit = mode === 'edit';

        form.action = edit
            ? form.getAttribute('data-update-url').replace('__ID__', id)
            : form.getAttribute('data-create-url');

        var title = modal.querySelector('[data-modal-title]');
        if (title) {
            title.textContent = form.getAttribute(edit ? 'data-title-edit' : 'data-title-create');
        }

        modal.querySelectorAll('[data-mode-only]').forEach(function (el) {
            el.classList.toggle('d-none', el.getAttribute('data-mode-only') !== mode);
        });
        modal.querySelectorAll('[data-required-create]').forEach(function (el) {
            el.required = !edit;
        });
    }

    function fillForm(form, values) {
        Object.keys(values).forEach(function (name) {
            var els = form.querySelectorAll('[name="' + name + '"]');
            els.forEach(function (el) {
                if (el.type === 'checkbox') {
                    el.checked = String(values[name]) === '1';
                } else if (el.type === 'hidden' && els.length > 1) {
                    // pasangan hidden untuk checkbox: dibiarkan
                } else if (el.type !== 'file') {
                    el.value = values[name] === null ? '' : values[name];
                }
            });
        });
    }

    function setImagePreview(modal, url) {
        var box = modal.querySelector('[data-image-box]');
        var img = modal.querySelector('[data-image-preview]');
        if (!box || !img) { return; }
        if (url) {
            img.src = url;
            box.classList.remove('d-none');
        } else {
            img.removeAttribute('src');
            box.classList.add('d-none');
        }
        var remove = box.querySelector('input[type="checkbox"]');
        if (remove) { remove.checked = false; }
    }

    document.querySelectorAll('.modal[data-crud-modal]').forEach(function (modal) {
        modal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) { return; } // dibuka dari skrip (reopen): jangan reset

            var form = modal.querySelector('form');
            form.reset();
            modal.querySelectorAll('.is-invalid').forEach(function (el) {
                el.classList.remove('is-invalid');
            });

            var mode = btn.getAttribute('data-mode') || 'create';
            var values = {};
            try { values = JSON.parse(btn.getAttribute('data-values') || '{}'); } catch (e) {}

            setModalMode(modal, mode, values.id);
            if (mode === 'edit') { fillForm(form, values); }
            setImagePreview(modal, mode === 'edit' ? (values.image_url || '') : '');
        });
    });

    /* ---------- Buka kembali modal setelah validasi gagal ---------- */
    document.querySelectorAll('.modal[data-reopen]').forEach(function (modal) {
        var state = {};
        try { state = JSON.parse(modal.getAttribute('data-reopen') || '{}'); } catch (e) {}

        setModalMode(modal, state.mode === 'edit' ? 'edit' : 'create', state.id);
        fillForm(modal.querySelector('form'), state.old || {});
        setImagePreview(modal, (state.old && state.old.image_url) || '');

        bootstrap.Modal.getOrCreateInstance(modal).show();
    });

    /* ---------- Konfirmasi hapus ---------- */
    var deleteModalEl = document.getElementById('confirmDeleteModal');
    if (deleteModalEl) {
        var deleteModal = bootstrap.Modal.getOrCreateInstance(deleteModalEl);
        var deleteForm = document.getElementById('confirmDeleteForm');

        document.querySelectorAll('[data-confirm-delete]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                deleteForm.action = btn.getAttribute('data-action');
                deleteModalEl.querySelector('[data-delete-name]').textContent = btn.getAttribute('data-name') || '';
                deleteModalEl.querySelector('[data-delete-note]').textContent = btn.getAttribute('data-note') || '';
                deleteModal.show();
            });
        });
    }

    /* ---------- Total bobot kriteria (harus 100) ---------- */
    document.querySelectorAll('form[data-weight-form]').forEach(function (form) {
        var inputs = form.querySelectorAll('[data-weight-input]');
        var totalEl = form.querySelector('[data-weight-total]');
        var badge = form.querySelector('[data-weight-badge]');
        var submit = form.querySelector('[data-weight-submit]');

        function refresh() {
            var cents = 0;
            inputs.forEach(function (input) {
                var v = parseFloat(input.value);
                if (!isNaN(v)) { cents += Math.round(v * 100); }
            });
            var ok = cents === 10000;
            totalEl.textContent = (cents / 100).toFixed(2).replace('.', ',');
            badge.textContent = ok ? 'OK' : 'Harus 100%';
            badge.className = 'badge ms-1 text-bg-' + (ok ? 'success' : 'danger');
            if (submit) { submit.disabled = !ok; }
        }

        inputs.forEach(function (input) { input.addEventListener('input', refresh); });
        refresh();
    });
})();