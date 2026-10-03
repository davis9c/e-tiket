<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Buat E-Tiket Baru</h1>
        <!--UPDATE 2 END-->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-4">
                <!--
                    Kembali ke /allticket, bukan /baru: /baru adalah form
                    pengajuan milik user sendiri, sedangkan halaman ini form
                    milik admin yang membuatkan tiket atas nama orang lain.
                -->
                <li class="breadcrumb-item active"><a href="<?= base_url('allticket') ?>">Semua Tiket</a></li>
                <?php if (!empty($data['kategoriData'])): ?>
                    <li class="breadcrumb-item"><?= esc($data['kategoriData']['nama_kategori']) ?> (Baru)</li>
                <?php endif; ?>
            </ol>
        </nav>
        <?php
        //dd($data);
        ?>
        <?php if ($msg = session()->getFlashdata('success')): ?>
            <div class="modal fade" id="exampleModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-body">
                            <?= esc($msg) ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var myModal = new bootstrap.Modal(document.getElementById('exampleModal'));
                    myModal.show();
                });
            </script>
        <?php endif; ?>

        <div class="row">
            <!-- FORM/DETAIL (ATAS) -->
            <?php if (!empty($data['kategoriData'])): ?>
                <div class="col-md-9">
                    <?= $this->include('manual/form') ?>
                </div>
            <?php else: ?>
                <div class="col-md-9">
                    <?= $this->include('manual/card') ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</main>
<?= $this->endSection() ?>