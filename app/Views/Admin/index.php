<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4"><?= esc($title) ?></h1>

        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Admin</li>
                <li class="breadcrumb-item"><?= esc(ucfirst($tab)) ?></li>
            </ol>
        </nav>

        <?php
        // Satu controller, satu view, tiga tab.
        //
        // Isi tiap tab tetap memakai partial yang sudah ada
        // (Admin/users/list, Admin/pegawai/list, Admin/petugas/*), jadi
        // markup tabelnya TIDAK diduplikasi di file ini.
        //
        // Tab aktif dibaca dari ?tab=, bukan dari state JS, supaya URL bisa
        // disalin/dibagikan dan tetap membuka tab yang sama.
        $labelTab = [
            'users'    => 'User E-Tiket',
            'pegawai'  => 'Pegawai',
            'petugas'  => 'Petugas',
        ];
        ?>

        <ul class="nav nav-tabs mb-4" role="tablist">
            <?php foreach ($tabs as $t): ?>
                <li class="nav-item" role="presentation">
                    <a class="nav-link<?= $tab === $t ? ' active' : '' ?>"
                        href="<?= base_url('admin?tab=' . esc($t)) ?>"
                        role="tab"
                        aria-selected="<?= $tab === $t ? 'true' : 'false' ?>">
                        <?= esc($labelTab[$t]) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (! empty($error)): ?>
            <div class="alert alert-warning py-2" role="alert">
                <small><?= esc($error) ?></small>
            </div>
        <?php endif; ?>

        <?php
        // Partial di bawah memakai $title sebagai judul kartu
        // (lihat Admin/users/list.php baris 4). Jadi $title harus
        // diganti sesuai tab SEBELUM partial di-include -- kalau tidak,
        // semua kartu akan menuliskan judul halaman "Admin".
        //
        // $judulTab disimpan terpisah supaya nilai aslinya tidak hilang
        // untuk <h1> di atas.
        ?>
        <?php if ($tab === 'users'): ?>
            <?php $title = $labelTab['users']; ?>
            <?= $this->include('Admin/users/list') ?>

        <?php elseif ($tab === 'pegawai'): ?>
            <?php $title = $labelTab['pegawai']; ?>
            <?= $this->include('Admin/pegawai/list') ?>

        <?php else: ?>
            <?php
            // Tab Petugas punya dua tahap: pilih jabatan dulu, baru
            // daftar petugasnya muncul. Sulusinya lewat link biasa
            // (?jbtn=), bukan AJAX, supaya tetap jalan tanpa JS dan
            // tetap bisa di-back.
            $title = $labelTab['petugas'];
            $petugasAda = ! empty($petugas);
            ?>
            <div class="row">
                <?= $this->include('Admin/petugas/card-datatable') ?>
                <?php if ($petugasAda): ?>
                    <?= $this->include('Admin/petugas/list2') ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<?= $this->endSection() ?>