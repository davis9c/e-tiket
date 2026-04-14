<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>E-TIKET | <?= $title ?></title>
    <link rel="icon" type="image/x-icon" href="<?= base_url('assets/img/logo.ico') ?>">
    <link href="<?= base_url('dataTables/style.min.css') ?>" rel="stylesheet" />
    <link href="<?= base_url('sb/css/styles.css') ?>" rel="stylesheet" />
    <script src="<?= base_url('FontAwesome/all.js') ?>" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <?= $this->include('layout-dashboard/navbar-top') ?>
    <div id="layoutSidenav">
        <?= $this->include('layout-dashboard/sidenav') ?>

        <div id="layoutSidenav_content">
            <?= $this->renderSection('content') ?>
            <?= $this->include('layout-dashboard/footer') ?>
        </div>
    </div>
    <script>
        function openConfirmModal() {
            const selected = document.querySelector('input[name="status_validasi"]:checked');

            if (!selected) {
                alert('Pilih aksi terlebih dahulu!');
                return;
            }

            const modal = new bootstrap.Modal(document.getElementById('modalConfirm'));
            const header = document.getElementById('modalHeader');
            const title = document.getElementById('modalTitle');
            const body = document.getElementById('modalBody');
            const btn = document.getElementById('btnConfirm');

            if (selected.value == '0') {
                // TOLAK
                header.className = 'modal-header bg-danger text-white';
                title.innerHTML = 'Konfirmasi Penolakan';
                body.innerHTML = 'Apakah Anda yakin ingin <strong>menolak</strong> tiket ini?';
                btn.className = 'btn btn-danger btn-sm';
                btn.innerHTML = 'Ya, Tolak';
            } else {
                // SELESAI
                header.className = 'modal-header bg-primary text-white';
                title.innerHTML = 'Konfirmasi Penyelesaian';
                body.innerHTML = 'Apakah Anda yakin ingin <strong>menyelesaikan</strong> tiket ini?';
                btn.className = 'btn btn-primary btn-sm';
                btn.innerHTML = 'Ya, Selesaikan';
            }

            modal.show();
        }
    </script>
    <script src="<?= base_url('BootStrap/bootstrap.bundle.min.js') ?>" crossorigin="anonymous"></script>
    <script src="<?= base_url('sb/js/scripts.js') ?>"></script>
    <script src="<?= base_url('dataTables/simple-datatables.min.js') ?>" crossorigin="anonymous"></script>
    <script src="<?= base_url('sb/js/datatables-simple-demo.js') ?>"></script>
    <script src="<?= base_url('js/dataTables.js') ?>"></script>
</body>

</html>