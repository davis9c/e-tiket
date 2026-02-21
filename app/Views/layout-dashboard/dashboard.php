<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Dashboard - SB Admin</title>
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
    <script src="<?= base_url('BootStrap/bootstrap.bundle.min.js') ?>" crossorigin="anonymous"></script>
    <script src="<?= base_url('sb/js/scripts.js') ?>"></script>
    <script src="<?= base_url('dataTables/simple-datatables.min.js') ?>" crossorigin="anonymous"></script>
    <script src="<?= base_url('sb/js/datatables-simple-demo.js') ?>"></script>
    <script src="<?= base_url('js/dataTables.js') ?>"></script>
</body>

</html>