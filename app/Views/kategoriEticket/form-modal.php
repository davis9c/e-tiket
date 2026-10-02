<?php // Form bersama untuk modal Tambah & Edit. Diisi/dikosongkan oleh JS. ?>
<form id="ktForm" novalidate>
    <!-- Informasi Utama -->
    <div class="row g-3 mb-3">
        <div class="col-md-4" id="ktFormKodeWrap">
            <label class="form-label fw-semibold" for="ktKode">Kode Kategori</label>
            <input type="text"
                id="ktKode"
                name="kode_kategori"
                class="form-control"
                placeholder="IT, SIMRS, BILL"
                maxlength="20">
            <small class="text-muted d-none" id="ktKodeHint">Kode tidak dapat diubah</small>
        </div>

        <div class="col-md-8" id="ktFormNamaWrap">
            <label class="form-label fw-semibold" for="ktNama">Nama Kategori</label>
            <input type="text"
                id="ktNama"
                name="nama_kategori"
                class="form-control"
                placeholder="Contoh: Tim Teknologi Informasi"
                maxlength="100">
        </div>
    </div>

    <!-- Deskripsi dan Template -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold" for="ktDeskripsi">Deskripsi</label>
            <textarea
                id="ktDeskripsi"
                name="deskripsi"
                class="form-control"
                rows="5"
                placeholder="Deskripsi kategori tiket..."></textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold" for="ktTemplate">Template Tiket</label>
            <textarea
                id="ktTemplate"
                name="template"
                class="form-control kt-editor"
                rows="5"
                placeholder="Template atau format tiket..."></textarea>
            <small class="text-muted">Boleh diisi markup HTML.</small>
        </div>
    </div>

    <!-- Pengaturan -->
    <h6 class="border-bottom pb-2 mb-3">Pengaturan Kategori</h6>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label fw-semibold" for="ktAktif">Status</label>
            <select id="ktAktif" name="aktif" class="form-select">
                <option value="1">Aktif</option>
                <option value="0">Non Aktif</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold" for="ktHeadsection">Head Section</label>
            <select id="ktHeadsection" name="headsection" class="form-select">
                <option value="1">Aktif</option>
                <option value="0">Non Aktif</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold" for="ktTeruskan">Teruskan Tiket</label>
            <select id="ktTeruskan" name="teruskan" class="form-select">
                <option value="1">Ya</option>
                <option value="0">Tidak</option>
            </select>
        </div>
    </div>
</form>