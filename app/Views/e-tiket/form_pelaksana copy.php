<div class="card shadow-sm mb-4">
    <div class="card-header">
        <i class="fas fa-ticket-alt me-1"></i>
        Detail E-Ticket
    </div>

    <div class="card-body">

        <!-- ================= FLASH MESSAGE ================= -->
        <?php if ($msg = session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= esc($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($msg = session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= esc($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>


        <!-- ================= HEADER KATEGORI ================= -->
        <div class="card mb-4 border-start border-primary border-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">

                        <h5 class="fw-bold text-uppercase mb-1">
                            <?= esc($data['detailTicket']['nama_kategori']) ?>
                        </h5>

                        <div class="text-muted small mb-2">
                            Kode Kategori: <?= esc($data['detailTicket']['kode_kategori']) ?>
                        </div>

                        <?php if (!empty($data['detailTicket']['unit_penanggung_jawab'])): ?>
                            <div class="mb-2">
                                <div class="small text-muted">Unit Penanggung Jawab</div>
                                <?php foreach ($data['detailTicket']['unit_penanggung_jawab'] as $unit): ?>
                                    <span class="badge bg-primary me-1 mb-1">
                                        <?= esc($unit['nm_jbtn']) ?>
                                    </span><br>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <p class="fst-italic mb-0">
                            <?= esc($data['detailTicket']['deskripsi']) ?>
                        </p>

                    </div>

                    <div class="col-md-4 text-md-end">
                        <div class="small text-muted">Tanggal Pengajuan</div>
                        <div class="fw-semibold">
                            <?= esc($data['detailTicket']['created_at']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- ================= INFORMASI PETUGAS ================= -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Petugas</label>
            <div class="form-control bg-light">
                <?= esc($data['detailTicket']['petugas_nama']) ?>
                <div class="small text-muted">
                    <?= esc($data['detailTicket']['nm_jbtn']) ?>
                    — NIP: <?= esc($data['detailTicket']['petugas_id']) ?>
                </div>
            </div>
        </div>
        <!-- ================= DESKRIPSI ================= -->
        <div class="mb-0">
            <label class="form-label fw-semibold">Deskripsi Pengajuan</label>
            <textarea class="form-control bg-light" rows="4" readonly><?= esc($data['detailTicket']['message']) ?></textarea>
        </div>
        <?php if ($data['detailTicket']['valid'] != null) : ?>
            <?php if ($data['detailTicket']['valid'] != $data['detailTicket']['petugas_id']) : ?>
                validated by <?= esc($data['detailTicket']['valid']) ?>
            <?php else : ?>
                VALID
            <?php endif; ?>
            <br>
            <!-- UNIT PROSES -->
            <?php

            $units  = $data['detailTicket']['unit_penanggung_jawab'] ?? [];
            $proses = $data['detailTicket']['proses'] ?? [];

            $prosesKd = array_column($proses, 'kd_jbtn');
            $formDitampilkan = false;

            foreach ($units as $unit) :

                $kd = $unit['kd_jbtn'];

                echo "<div>" . esc($kd) . " - " . esc($unit['nm_jbtn']) . "</div>";

                if (!in_array($kd, $prosesKd)) :

                    if (!$formDitampilkan) :

                        $formDitampilkan = true;
                        $errors = session('errors') ?? [];
            ?>

                        <form action="<?= base_url('proses/store') ?>" method="post" class="mt-3">
                            <?= csrf_field() ?>

                            <input type="hidden" name="ticket_id"
                                value="<?= esc($data['detailTicket']['id']) ?>">

                            <input type="hidden" name="kd_jbtn"
                                value="<?= esc($kd) ?>">

                            <?php
                            $selectedStatus = old('status_proses') ?? '';
                            $errors = session('errors') ?? [];
                            ?>

                            <!-- STATUS PROSES -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block">
                                    Status Proses (<?= esc($kd) ?>)
                                </label>

                                <div class="btn-group w-100" role="group">

                                    <!-- REJECT -->
                                    <input type="radio" class="btn-check"
                                        name="status_proses"
                                        id="reject"
                                        value="reject"
                                        <?= $selectedStatus === 'reject' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-danger" for="reject">
                                        ❌ Reject
                                    </label>

                                    <!-- TERUSKAN -->
                                    <input type="radio" class="btn-check"
                                        name="status_proses"
                                        id="teruskan"
                                        value="teruskan"
                                        <?= $selectedStatus === 'teruskan' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-warning" for="teruskan">
                                        ➡ Teruskan ke Berikutnya
                                    </label>

                                    <!-- SELESAI -->
                                    <input type="radio" class="btn-check"
                                        name="status_proses"
                                        id="selesai"
                                        value="selesai"
                                        <?= $selectedStatus === 'selesai' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success" for="selesai">
                                        ✅ Selesai
                                    </label>

                                </div>

                                <?php if (isset($errors['status_proses'])): ?>
                                    <div class="text-danger small mt-2">
                                        <?= esc($errors['status_proses']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- KETERANGAN -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Keterangan
                                </label>

                                <textarea
                                    name="keterangan_proses"
                                    rows="3"
                                    class="form-control <?= isset($errors['keterangan_proses']) ? 'is-invalid' : '' ?>"
                                    placeholder="Masukkan keterangan..."><?= old('keterangan_proses') ?></textarea>

                                <?php if (isset($errors['keterangan_proses'])): ?>
                                    <div class="invalid-feedback">
                                        <?= esc($errors['keterangan_proses']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- TOMBOL -->
                            <div class="d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Simpan
                                </button>
                            </div>
                        </form>
            <?php
                    endif;

                    break;

                endif;

            endforeach;

            if (!$formDitampilkan) :
                echo "<div class='alert alert-info mt-3'>Tidak ada lanjutan</div>";
            endif;

            ?>



        <?php else : ?>

            <!-- Form Approve / Validasi -->
            <div class="alert alert-warning">
                Ticket belum divalidasi.
            </div>
        <?php endif; ?>
        <!-- ================= FORM APPROVAL ================= -->
        <form action="<?= base_url('headsection/approve') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= esc($data['detailTicket']['id']) ?>">
            <?php
            //dd($data['detailTicket']);
            ?>
            <?php
            $selectedStatus = old('status_validasi') ?? '0';
            $errors = session('errors') ?? [];
            ?>



            <!-- STATUS VALIDASI -->
            <div class="mb-3">
                <label class="form-label fw-semibold d-block">Status Validasi</label>

                <div class="btn-group w-100" role="group">

                    <input type="radio" class="btn-check"
                        name="status_validasi"
                        id="reject"
                        value="0"
                        <?= $selectedStatus == '0' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-danger" for="reject">
                        ❌ Reject
                    </label>

                    <input type="radio" class="btn-check"
                        name="status_validasi"
                        id="accept"
                        value="1"
                        <?= $selectedStatus == '1' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-success" for="accept">
                        ✔ Accept
                    </label>

                    <input type="radio" class="btn-check"
                        name="status_validasi"
                        id="selesai"
                        value="2"
                        <?= $selectedStatus == '2' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-primary" for="selesai">
                        ✅ Diselesaikan
                    </label>
                </div>

                <?php if (isset($errors['status_validasi'])): ?>
                    <div class="text-danger small mt-2">
                        <?= esc($errors['status_validasi']) ?>
                    </div>
                <?php endif; ?>
            </div>


            <!-- CATATAN -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Pesan / Catatan Headsection</label>
                <textarea
                    name="catatan_headsection"
                    rows="3"
                    class="form-control <?= isset($errors['catatan_headsection']) ? 'is-invalid' : '' ?>"
                    placeholder="Masukkan catatan jika diperlukan..."><?= old('catatan_headsection') ?></textarea>

                <?php if (isset($errors['catatan_headsection'])): ?>
                    <div class="invalid-feedback">
                        <?= esc($errors['catatan_headsection']) ?>
                    </div>
                <?php endif; ?>
            </div>


            <!-- TOMBOL -->
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= base_url('etiket') ?>" class="btn btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check me-1"></i>
                    Simpan
                </button>
            </div>
        </form>
        <hr>



    </div>

</div>