<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <div class="container-fluid px-4">

        <h1 class="mt-4">
            Persetujuan E-Tiket<?= ($s = service('request')->getGet('status')) ? ' ' . ucfirst($s) : '' ?>
        </h1>
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item active"><a href="<?= base_url('headsection') ?>">Persetujuan E-Ticket</a></li>
                <?php if (!empty($data['detailTicket'])): ?>
                    <li class="breadcrumb-item"><?= esc($data['detailTicket']['hashid']) ?></li>
                <?php endif; ?>
            </ol>
        </nav>
        <div class="row">
            <!-- LIST (KIRI) -->
            <?php if (!empty($data['detailTicket'])): ?>
                <div class="col-md-9">
                    <?= $this->include('e-tiket/form-e') ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="row">
            <div class="d-flex gap-2 mb-4 flex-wrap">
                <form id="formCariKategori" class="d-flex gap-2">
                    <?php
                    $selesaiSelected = service('request')->getGet('selesai');
                    ?>
                    <select name="selesai" id="selectSelesai" class="form-select">
                        <option value=""
                            <?= ($selesaiSelected === null || $selesaiSelected === '') ? 'selected' : '' ?>>
                            Semua
                        </option>
                        <option value="1"
                            <?= ($selesaiSelected === '1') ? 'selected' : '' ?>>
                            Selesai
                        </option>
                        <option value="0"
                            <?= ($selesaiSelected === '0') ? 'selected' : '' ?>>
                            Belum Selesai
                        </option>
                    </select>
                    <?php
                    $validSelected = service('request')->getGet('valid');
                    ?>
                    <select name="valid" id="selectValid" class="form-select">
                        <option value=""
                            <?= ($validSelected === null || $validSelected === '') ? 'selected' : '' ?>>
                            Semua
                        </option>
                        <option value="1"
                            <?= ($validSelected === '1') ? 'selected' : '' ?>>
                            Disetujui
                        </option>
                        <option value="0"
                            <?= ($validSelected === '0') ? 'selected' : '' ?>>
                            Belum Disetujui
                        </option>
                    </select>
                    <select class="form-select" id="selectKategori" name="kategori">
                        <option value="">Pilih Kategori</option>
                        <?php
                        $kategoriSelected = service('request')->getGet('kategori');
                        ?>
                        <?php if (!empty($data['kategori'])): ?>
                            <?php foreach ($data['kategori'] as $p): ?>
                                <option value="<?= esc($p['id']) ?>"
                                    <?= ($kategoriSelected == $p['id']) ? 'selected' : '' ?>>
                                    <?= esc($p['kode_kategori']) ?> - <?= esc($p['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        Cari
                    </button>
                </form>
            </div>
            <!-- FORM DETAIL (KANAN) -->
            <div class="<?= !empty($data['detailTicket']) ? 'col-md-9' : 'col-md-9' ?>">
                <?= $this->include('e-tiket/list') ?>
            </div>
        </div>
    </div>
</main>
<?= $this->endSection() ?>