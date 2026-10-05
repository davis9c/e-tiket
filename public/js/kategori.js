/**
 * CRUD Kategori E-Ticket tanpa reload.
 *
 * Halaman /kategori (dan alias /kategori/edit/:id) selalu menampilkan
 * daftar. Semua aksi lain lewat modal:
 *   - ktFormModal : tambah & ubah kategori
 *   - ktUnitModal : kelola unit penanggung jawab & pengajuan
 *
 * Tidak ada modal konfirmasi. Aksi berjalan langsung dan hasilnya
 * dilaporkan lewat toast, jadi user tidak perlu klik dua kali untuk
 * setiap tambah/hapus unit atau aktivasi kategori.
 *
 * Bergaya sama dengan public/js/dataTables.js (vanilla JS, tanpa jQuery).
 */
(function () {
    'use strict';

    const PAGE = window.KT_PAGE || { kategoriId: 0, csrfName: 'csrf_test_name', csrfToken: '' };
    const BASE = window.BASE_URL || '/';

    let modals = {};

    /* =====================================================
     * UTIL
     * ===================================================== */

    function esc(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    /**
     * Untuk nilai atribut. textContent -> innerHTML tidak escaped tanda kutip,
     * jadi quotes harus ditangani sendiri.
     */
    function escAttr(value) {
        return esc(value).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    /**
     * Semua request memakai header ini agar controller membalas JSON.
     */
    function headers(extra) {
        return Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': PAGE.csrfToken || '',
            'Accept': 'application/json'
        }, extra || {});
    }

    /**
     * Wrapper fetch. Kalau server membalas redirect / HTML (mis. sesi habis
     * atau filter menolak), fallback: reload supaya user tidak bingung.
     */
    async function request(url, options) {
        const res = await fetch(url, Object.assign({
            credentials: 'same-origin',
            headers: headers()
        }, options || {}));

        const type = res.headers.get('content-type') || '';

        if (res.redirected || !type.includes('json')) {
            window.location.reload();
            return new Promise(function () {}); // hentikan alur
        }

        const body = await res.json().catch(function () {
            return {};
        });

        if (!res.ok) {
            const err = new Error(body.message || 'Permintaan gagal (' + res.status + ')');
            err.status = res.status;
            err.errors = body.errors || {};
            throw err;
        }

        return body;
    }

    function getModal(id) {
        const el = document.getElementById(id);
        return el ? modals[id] || (modals[id] = new bootstrap.Modal(el)) : null;
    }

    /* =====================================================
     * TOAST
     * ===================================================== */

    const ICONS = {
        success: 'fa-circle-check',
        error: 'fa-circle-exclamation',
        info: 'fa-circle-info'
    };

    function toast(message, type) {
        const container = document.getElementById('ktToast');
        if (!container || !message) return;

        const kind = ICONS[type] ? type : 'info';

        const el = document.createElement('div');
        el.className = 'toast align-items-center text-bg-' + kind + ' border-0';
        el.setAttribute('role', 'alert');
        el.setAttribute('aria-live', 'assertive');
        el.setAttribute('aria-atomic', 'true');

        el.innerHTML =
            '<div class="d-flex">' +
            '<div class="toast-body"><i class="fas ' + ICONS[kind] + ' me-2"></i>' +
            '<span class="kt-toast-text"></span></div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" ' +
            'data-bs-dismiss="toast" aria-label="Tutup"></button>' +
            '</div>';

        el.querySelector('.kt-toast-text').textContent = message;
        container.appendChild(el);

        const instance = new bootstrap.Toast(el, { delay: 4000 });
        el.addEventListener('hidden.bs.toast', function () {
            el.remove();
        });
        instance.show();
    }

    /* =====================================================
     * FORM HELPERS
     * ===================================================== */

    function setBusy(button, busy, busyLabel) {
        if (!button) return;

        if (busy) {
            const label = button.querySelector('.kt-btn-label');
            button.dataset.label = label ? label.textContent : button.textContent.trim();
            button.disabled = true;
            button.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1" role="status" ' +
                'aria-hidden="true"></span>' + (busyLabel || 'Memproses...');
        } else {
            button.disabled = false;

            if (button.dataset.label) {
                button.innerHTML =
                    '<i class="fas fa-save me-1"></i>' +
                    '<span class="kt-btn-label">' + button.dataset.label + '</span>';
            }
        }
    }

    function formToObject(form) {
        const data = {};

        new FormData(form).forEach(function (value, key) {
            data[key] = value;
        });

        return data;
    }

    /* =====================================================
     * PANEL UNIT
     * ===================================================== */

    /**
     * Panel unit (jabatan tersedia + PJ + pengajuan).
     * Satu implementasi dipakai modal unit di halaman daftar.
     */
    function createUnitPanel() {
        const jabatanList = document.getElementById('ktJabatanList');
        const pjList = document.getElementById('ktPJList');
        const pengajuanList = document.getElementById('ktPengajuanList');
        const search = document.getElementById('ktJabatanSearch');

        let kategoriId = 0;

        function renderUnitList(el, units, type, emptyText) {
            if (!el) return;

            if (!units || !units.length) {
                el.innerHTML = '<li class="list-group-item text-muted">' + emptyText + '</li>';
                return;
            }

            el.innerHTML = units.map(function (u) {
                return '' +
                    '<li class="list-group-item d-flex justify-content-between align-items-center">' +
                    '<span>' + esc(u.nm_jbtn) + '</span>' +
                    '<button type="button" class="btn btn-sm btn-danger" data-kt-action="remove" ' +
                    'data-kt-type="' + type + '" data-kt-kd="' + escAttr(u.kd_jbtn) + '" ' +
                    'data-kt-nama="' + escAttr(u.nm_jbtn) + '" title="Hapus dari daftar">' +
                    '<i class="fas fa-trash"></i></button></li>';
            }).join('');
        }

        function renderJabatan(units) {
            if (!jabatanList) return;

            if (!units || !units.length) {
                jabatanList.innerHTML =
                    '<tr><td colspan="2" class="text-center text-muted py-4">' +
                    'Tidak ada jabatan tersedia</td></tr>';
                return;
            }

            jabatanList.innerHTML = units.map(function (j) {
                const kd = escAttr(j.kd_jbtn);
                const nama = escAttr(j.nm_jbtn);

                return '' +
                    '<tr data-kt-nama-row="' +
                    escAttr(String(j.nm_jbtn || '').toLowerCase()) + '">' +
                    '<td>' + esc(j.nm_jbtn) + '</td>' +
                    '<td class="text-center"><div class="d-flex justify-content-center gap-1">' +
                    '<button type="button" class="btn btn-sm btn-success" data-kt-action="add" ' +
                    'data-kt-type="1" data-kt-kd="' + kd + '" data-kt-nama="' + nama + '" ' +
                    'title="Tambah sebagai Unit Penanggung Jawab">' +
                    '<i class="fas fa-user-check"></i></button> ' +
                    '<button type="button" class="btn btn-sm btn-primary" data-kt-action="add" ' +
                    'data-kt-type="0" data-kt-kd="' + kd + '" data-kt-nama="' + nama + '" ' +
                    'title="Tambah sebagai Unit Pengajuan">' +
                    '<i class="fas fa-paper-plane"></i></button>' +
                    '</div></td></tr>';
            }).join('');

            filterJabatan();
        }

        function filterJabatan() {
            const keyword = search && search.value ? search.value.toLowerCase().trim() : '';

            if (!jabatanList) return;

            jabatanList.querySelectorAll('[data-kt-nama-row]').forEach(function (tr) {
                tr.style.display = !keyword || tr.dataset.ktNamaRow.includes(keyword) ? '' : 'none';
            });
        }

        function render(payload) {
            renderJabatan(payload.jabatan);
            renderUnitList(pjList, payload.unit_penanggung_jawab, 1, 'Belum ada Unit Penanggung Jawab');
            renderUnitList(pengajuanList, payload.unit_pengajuan, 0, 'Kategori ini umum: bisa dipakai semua unit');
        }

        function loading() {
            if (!jabatanList) return;

            jabatanList.innerHTML =
                '<tr><td colspan="2" class="text-center text-muted py-4">' +
                '<span class="spinner-border spinner-border-sm me-1"></span>Memuat jabatan...</td></tr>';

            if (pjList) {
                pjList.innerHTML = '<li class="list-group-item text-muted">Memuat...</li>';
            }
            if (pengajuanList) {
                pengajuanList.innerHTML = '<li class="list-group-item text-muted">Memuat...</li>';
            }
        }

        if (search) {
            search.addEventListener('input', filterJabatan);
        }

        // Delegasi aksi add/remove unit.
        document.addEventListener('click', async function (event) {
            const btn = event.target.closest('#ktUnitModal [data-kt-action]');
            if (!btn) return;

            const kd = btn.dataset.ktKd;
            const nama = btn.dataset.ktNama;
            const isPJ = btn.dataset.ktType === '1';
            const action = btn.dataset.ktAction;
            const daftar = isPJ ? 'Unit Penanggung Jawab' : 'Unit Pengajuan';

            btn.disabled = true;

            try {
                const res = await request(BASE + 'kategori/updateUnit', {
                    method: 'POST',
                    headers: headers({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({
                        kategori_id: kategoriId,
                        kd_jbtn: kd,
                        is_penanggung_jawab: isPJ ? 1 : 0,
                        action: action
                    })
                });

                render(res);
                refreshTable();

                // Toast menyebut nama jabatan yang disentuh. Pesan dari
                // server ("Unit berhasil ditambahkan.") tidak menyebut apa
                // pun yang berubah, padahal daftar unit yang baru saja
                // dirender ulang adalah tempat user melihat hasilnya.
                toast(
                    nama + (action === 'add'
                        ? ' ditambahkan sebagai ' + daftar + '.'
                        : ' dihapus dari ' + daftar + '.'),
                    'success'
                );
            } catch (err) {
                toast(err.message, 'error');
            } finally {
                btn.disabled = false;
            }
        });

        return {
            setKategori: function (id) {
                kategoriId = Number(id) || 0;
            },
            loading: loading,
            render: render
        };
    }

    /* =====================================================
     * TABEL DAFTAR
     * ===================================================== */

    let refreshTable = function () {};

    function initTable() {
        const tbody = document.getElementById('ktTableBody');
        if (!tbody) {
            return { refresh: refreshTable };
        }

        const search = document.getElementById('ktSearch');
        const statusFilter = document.getElementById('ktStatusFilter');
        const rowInfo = document.getElementById('ktRowInfo');

        let rows = [];

        // emptyHtml bisa customized karena unit pengajuan kosong berarti
        // kategori umum, bukan berarti datanya belum lengkap.
        function unitBadges(units, className, emptyHtml) {
            if (!units || !units.length) {
                return emptyHtml || '<span class="text-muted">-</span>';
            }

            const shown = units.slice(0, 2).map(function (u) {
                return '<span class="badge ' + className + ' mb-1">' + esc(u.nm_jbtn) + '</span>';
            });

            if (units.length > 2) {
                shown.push('<span class="badge bg-secondary mb-1">+' +
                    (units.length - 2) + ' lainnya</span>');
            }

            return shown.join(' ');
        }

        function render() {
            const keyword = search && search.value ? search.value.toLowerCase().trim() : '';
            const status = statusFilter ? statusFilter.value : '';

            const filtered = rows.filter(function (row) {
                if (status !== '' && String(row.aktif) !== status) return false;
                if (!keyword) return true;

                return (
                    String(row.kode_kategori).toLowerCase().includes(keyword) ||
                    String(row.nama_kategori).toLowerCase().includes(keyword) ||
                    String(row.deskripsi || '').toLowerCase().includes(keyword)
                );
            });

            if (!filtered.length) {
                tbody.innerHTML =
                    '<tr><td colspan="7" class="text-center text-muted py-4">' +
                    (rows.length ? 'Tidak ada kategori yang cocok' : 'Belum ada kategori') +
                    '</td></tr>';

                if (rowInfo) rowInfo.textContent = '';
                return;
            }

            tbody.innerHTML = filtered.map(function (row, i) {
                const aktif = Number(row.aktif) === 1;
                const nama = escAttr(row.nama_kategori);

                return '' +
                    '<tr>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td>' + esc(row.kode_kategori) + '</td>' +
                    '<td>' + esc(row.nama_kategori) +
                    (row.deskripsi
                        ? '<small class="d-block text-muted">' + esc(row.deskripsi) + '</small>'
                        : '') +
                    '</td>' +
                    '<td>' + unitBadges(row.unit_penanggung_jawab, 'bg-primary') + '</td>' +
                    '<td>' + unitBadges(
                        row.unit_pengajuan,
                        'bg-info text-dark',
                        '<span class="badge bg-light text-dark">' +
                            '<i class="fas fa-globe me-1"></i>Umum</span>'
                    ) + '</td>' +
                    '<td class="text-center">' +
                    (aktif
                        ? '<span class="badge bg-success">Aktif</span>'
                        : '<span class="badge bg-secondary">Non Aktif</span>') +
                    '</td>' +
                    '<td class="text-center text-nowrap">' +
                    '<button type="button" class="btn btn-sm btn-outline-primary" ' +
                    'data-kt-edit="' + row.id + '" title="Ubah kategori">' +
                    '<i class="fas fa-edit"></i></button> ' +
                    '<button type="button" class="btn btn-sm btn-outline-info" ' +
                    'data-kt-unit="' + row.id + '" data-kt-nama="' + nama + '" ' +
                    'title="Kelola unit penanggung jawab & pengajuan">' +
                    '<i class="fas fa-diagram-project"></i></button> ' +
                    '<button type="button" class="btn btn-sm btn-outline-warning" ' +
                    'data-kt-toggle="' + row.id + '" data-kt-aktif="' + (aktif ? 1 : 0) + '" ' +
                    'data-kt-nama="' + nama + '" ' +
                    'title="' + (aktif ? 'Nonaktifkan' : 'Aktifkan') + ' kategori">' +
                    '<i class="fas ' + (aktif ? 'fa-toggle-on' : 'fa-toggle-off') + '"></i>' +
                    '</button>' +
                    '</td></tr>';
            }).join('');

            if (rowInfo) {
                rowInfo.textContent = filtered.length === rows.length
                    ? 'Menampilkan ' + rows.length + ' kategori'
                    : 'Menampilkan ' + filtered.length + ' dari ' + rows.length + ' kategori';
            }
        }

        async function refresh() {
            try {
                const res = await request(BASE + 'kategori/list', { method: 'GET' });
                rows = Array.isArray(res.data) ? res.data : [];
                render();
            } catch (err) {
                toast(err.message, 'error');
            }
        }

        let timer = null;
        if (search) {
            search.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(render, 150);
            });
        }
        if (statusFilter) {
            statusFilter.addEventListener('change', render);
        }

        return { refresh: refresh };
    }

    /* =====================================================
     * MODAL FORM (tambah & edit)
     * ===================================================== */

    function initFormModal() {
        const modalEl = document.getElementById('ktFormModal');
        const form = document.getElementById('ktForm');
        if (!modalEl || !form) return null;

        const titleEl = document.getElementById('ktFormModalTitle');
        const headerEl = document.getElementById('ktFormModalHeader');
        const submitBtn = document.getElementById('ktFormSubmit');
        const kodeInput = document.getElementById('ktKode');
        const kodeHint = document.getElementById('ktKodeHint');

        const templateEl = document.getElementById('ktTemplate');

        let mode = 'create';
        let kategoriId = 0;

        function setMode(next) {
            mode = next;

            if (mode === 'edit') {
                titleEl.textContent = 'Edit Kategori E-Ticket';
                headerEl.className = 'modal-header bg-primary text-white';
                submitBtn.className = 'btn btn-primary';
                submitBtn.querySelector('.kt-btn-label').textContent = 'Simpan Perubahan';
                kodeInput.readOnly = true;
                kodeInput.classList.add('bg-light');
                kodeHint.classList.remove('d-none');
            } else {
                titleEl.textContent = 'Tambah Kategori E-Ticket';
                headerEl.className = 'modal-header bg-success text-white';
                submitBtn.className = 'btn btn-success';
                submitBtn.querySelector('.kt-btn-label').textContent = 'Simpan Kategori';
                kodeInput.readOnly = false;
                kodeInput.classList.remove('bg-light');
                kodeHint.classList.add('d-none');
            }
        }

        function fill(data) {
            form.reset();

            kodeInput.value = data.kode_kategori || '';
            document.getElementById('ktNama').value = data.nama_kategori || '';
            document.getElementById('ktDeskripsi').value = data.deskripsi || '';

            // Template: textarea.value adalah sumber kebenaran. ClassicEditor.create()
            // membaca isi textarea saat dibuat, jadi HARUS diisi sebelum modal show.
            templateEl.value = data.template || '';

            document.getElementById('ktAktif').value = String(Number(data.aktif) === 1 ? 1 : 0);
            document.getElementById('ktHeadsection').value = String(Number(data.headsection) === 1 ? 1 : 0);
            document.getElementById('ktTeruskan').value = String(Number(data.teruskan) === 1 ? 1 : 0);
        }

        /**
         * CKEditor hanya boleh dibuat saat modal terlihat; kalau dibuat saat
         * modal masih display:none lebarnya 0 dan tampilannya rusak.
         * Instance dihapus lagi setiap modal ditutup.
         */
        function destroyEditor() {
            if (!templateEl.ckEditorInstance) return;

            // Kembalikan isi editor ke textarea supaya nilainya tidak hilang
            // saat modal dibuka lagi untuk kategori lain.
            templateEl.value = templateEl.ckEditorInstance.getData();
            templateEl.ckEditorInstance.destroy();
            delete templateEl.ckEditorInstance;
        }

        function syncTemplate() {
            if (templateEl.ckEditorInstance) {
                templateEl.value = templateEl.ckEditorInstance.getData();
            }
        }

        function createEditor() {
            if (templateEl.ckEditorInstance || typeof ClassicEditor === 'undefined') {
                return;
            }

            ClassicEditor
                .create(templateEl, {
                    toolbar: [
                        'heading', '|',
                        'bold', 'italic', 'underline', '|',
                        'bulletedList', 'numberedList', '|',
                        'undo', 'redo'
                    ]
                })
                .then(function (editor) {
                    templateEl.ckEditorInstance = editor;
                })
                .catch(function (error) {
                    console.error(error);
                });
        }

        modalEl.addEventListener('show.bs.modal', createEditor);
        modalEl.addEventListener('hidden.bs.modal', destroyEditor);

        kodeInput.addEventListener('blur', function () {
            kodeInput.value = kodeInput.value.toUpperCase().trim();
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            syncTemplate();

            const payload = formToObject(form);

            if (!payload.nama_kategori || (mode === 'create' && !payload.kode_kategori)) {
                toast(mode === 'create'
                    ? 'Kode dan nama kategori wajib diisi.'
                    : 'Nama kategori wajib diisi.', 'error');
                return;
            }

            setBusy(submitBtn, true, 'Menyimpan...');

            try {
                const res = mode === 'edit'
                    ? await request(BASE + 'kategori/update/' + kategoriId, {
                        method: 'PUT',
                        headers: headers({ 'Content-Type': 'application/json' }),
                        body: JSON.stringify(payload)
                    })
                    : await request(BASE + 'kategori/store', {
                        method: 'POST',
                        headers: headers({ 'Content-Type': 'application/json' }),
                        body: JSON.stringify(payload)
                    });

                toast(res.message || 'Kategori berhasil disimpan.', 'success');

                getModal('ktFormModal').hide();
                await refreshTable();
            } catch (err) {
                const first = Object.values(err.errors || {})[0];
                toast(first || err.message, 'error');
            } finally {
                setBusy(submitBtn, false);
            }
        });

        return {
            openCreate: function () {
                kategoriId = 0;
                setMode('create');
                fill({ aktif: 1, headsection: 1, teruskan: 1 });
                getModal('ktFormModal').show();
            },
            openEdit: function (data) {
                kategoriId = Number(data.id) || 0;
                setMode('edit');
                fill(data);
                getModal('ktFormModal').show();
            }
        };
    }

    /* =====================================================
     * BOOT
     * ===================================================== */

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.getElementById('ktToast')) return;

        // refreshTable dipakai panel unit & modal form setelah mutasi.
        refreshTable = initTable().refresh;

        const formModal = initFormModal();
        const unitPanel = createUnitPanel();

        /* ---- tombol tambah di header ---- */
        const openCreate = document.getElementById('ktOpenCreate');
        if (openCreate && formModal) {
            openCreate.addEventListener('click', function () {
                formModal.openCreate();
            });
        }

        /* ---- klik tombol di kolom Aksi ---- */
        document.addEventListener('click', async function (event) {
            const editBtn = event.target.closest('[data-kt-edit]');
            if (editBtn && formModal) {
                try {
                    const res = await request(
                        BASE + 'kategori/detail/' + editBtn.dataset.ktEdit,
                        { method: 'GET' }
                    );
                    formModal.openEdit(res.data);
                } catch (err) {
                    toast(err.message, 'error');
                }
                return;
            }

            const unitBtn = event.target.closest('[data-kt-unit]');
            if (unitBtn) {
                const unitTitle = document.getElementById('ktUnitTitle');
                const unitModal = getModal('ktUnitModal');

                if (!unitModal) return;

                unitPanel.setKategori(unitBtn.dataset.ktUnit);

                if (unitTitle) {
                    unitTitle.textContent = 'Edit Unit — ' + (unitBtn.dataset.ktNama || '');
                }

                unitPanel.loading();
                unitModal.show();

                try {
                    const res = await request(
                        BASE + 'kategori/detail/' + unitBtn.dataset.ktUnit,
                        { method: 'GET' }
                    );
                    unitPanel.render(res.data);
                } catch (err) {
                    toast(err.message, 'error');
                    unitModal.hide();
                }
                return;
            }

            const toggleBtn = event.target.closest('[data-kt-toggle]');
            if (toggleBtn) {
                const id = toggleBtn.dataset.ktToggle;
                const aktif = Number(toggleBtn.dataset.ktAktif) === 1;
                const nama = toggleBtn.dataset.ktNama || 'Kategori ini';

                toggleBtn.disabled = true;

                try {
                    await request(BASE + 'kategori/toggle-status/' + id, {
                        method: 'POST'
                    });

                    // Nama kategori ikut disebut supaya toast tidak cuma
                    // "berhasil" tanpa konteks: dari daftar beberapa
                    // kategori, user perlu tahu yang berubah yang mana.
                    // Nonaktifkan diberi 'info' karena biasanya itu
                    // keputusan sementara, bukan pencapaian.
                    toast(
                        nama + (aktif ? ' dinonaktifkan.' : ' diaktifkan kembali.'),
                        aktif ? 'info' : 'success'
                    );

                    await refreshTable();
                } catch (err) {
                    toggleBtn.disabled = false;
                    toast(err.message, 'error');
                }
            }
        });

        /* ---- /kategori/edit/{id} : buka modal edit otomatis ---- */
        if (PAGE.kategoriId && formModal) {
            request(BASE + 'kategori/detail/' + PAGE.kategoriId, { method: 'GET' })
                .then(function (res) {
                    formModal.openEdit(res.data);
                })
                .catch(function (err) {
                    toast(err.message, 'error');
                });
        }
    });
})();