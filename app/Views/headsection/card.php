<div class="row">
    <?php if (!empty($kategoriEticket)): ?>
        <?php foreach ($kategoriEticket as $p): ?>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card bg-primary text-white h-100">

                    <!-- Header -->
                    <div class="card-header text-white fw-bold">
                        <?= esc($p['kode_kategori']) ?> | <?= esc($p['nama_kategori']) ?>
                    </div>

                    <!-- Body -->
                    <div class="card-body">

                        <p class="mb-1">
                            <?php foreach ($p['unit_penanggung_jawab_nama'] as $unit): ?>
                                <span class="badge bg-light text-dark me-1">
                                    <?= esc($unit) ?>
                                </span>
                            <?php endforeach ?>
                        </p>


                        <p class="small mb-0">
                            <?= esc($p['deskripsi']) ?>
                        </p>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link"
                            href="<?= base_url('etiket/create?kategori=' . $p['id']) ?>">
                            Buat Tiket
                        </a>
                        <div class="small text-white">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                    </div>

                </div>
            </div>

        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-warning text-center">
                Data kategori belum tersedia
            </div>
        </div>

    <?php endif; ?>

</div>