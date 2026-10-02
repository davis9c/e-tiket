<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Login E-Ticket System">
    <meta name="author" content="">
    <title>Login - E-Ticket</title>
    <link rel="icon" type="image/x-icon" href="<?= base_url('assets/img/logo.ico') ?>">
    <link href="<?= base_url('sb/css/styles.css') ?>" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="bg-primary">

    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header text-center">
                                    <h3 class="font-weight-light my-4">Login E-Ticket</h3>
                                </div>
                                <div class="card-body">
                                    <form action="<?= base_url('/auth/attempt') ?>" method="POST" id="loginForm">
                                        <?= csrf_field(); ?>
                                        <!-- Flash Message -->
                                        <?php if (session()->getFlashdata('error')) : ?>
                                            <div class="alert alert-danger text-center">
                                                <?= session()->getFlashdata('error') ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (session()->getFlashdata('success')) : ?>
                                            <div class="alert alert-success text-center">
                                                <?= session()->getFlashdata('success') ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- USER ID -->
                                        <div class="form-floating mb-3">
                                            <?php if (ENVIRONMENT === 'development'): ?>

                                                <?php
                                                // SULIONO - satu-satunya user headsection,
                                                // dipakai default supaya halaman
                                                // persetujuan bisa langsung diuji.
                                                $defaultUser = '197005091995031002';
                                                ?>
                                                <select class="form-select" id="user_id" name="user_id" required>
                                                    <?php foreach (($devUsers ?? []) as $group): ?>
                                                        <optgroup label="<?= esc($group['label']) ?>">
                                                            <?php foreach ($group['users'] as $u): ?>
                                                                <?php
                                                                $selected = old('user_id')
                                                                    ? old('user_id') == $u['nip']
                                                                    : $defaultUser == $u['nip'];
                                                                ?>
                                                                <option value="<?= esc($u['nip']) ?>" <?= $selected ? 'selected' : '' ?>>
                                                                    <?= esc($u['nama']) ?><?= $u['headsection'] ? ' (Headsection)' : '' ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </optgroup>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label for="user_id">Pilih User (Development Mode)</label>
                                            <?php else: ?>
                                                <input type="text"
                                                    class="form-control"
                                                    id="user_id"
                                                    name="user_id"
                                                    value="<?= old('user_id'); ?>"
                                                    placeholder="User ID"
                                                    required>
                                                <label for="user_id">User ID</label>
                                            <?php endif; ?>
                                        </div>
                                        <!-- PASSWORD -->
                                        <div class="form-floating mb-3">
                                            <input type="password"
                                                class="form-control"
                                                id="password"
                                                name="password"
                                                placeholder="Password"
                                                <?php if (ENVIRONMENT === 'development'): ?>
                                                value="123"
                                                <?php endif; ?>
                                                required>
                                            <label for="password">Password</label>
                                        </div>
                                        <!-- BUTTON -->
                                        <div class="d-grid mt-4">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-sign-in-alt me-1"></i>
                                                Login
                                            </button>
                                        </div>
                                    </form>
                                    <!-- <div class="d-grid mt-3">
                                        <a href="<?= base_url('dashboard') ?>"
                                            class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-chart-line me-1"></i>
                                            Dashboard
                                        </a>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?= base_url('sb/js/scripts.js') ?>"></script>

</body>

</html>