<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Login - SB Admin</title>
    <link href="<?= base_url('sb/css/styles.css') ?>" rel="stylesheet" />
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
                                <div class="card-header">
                                    <h3 class="text-center font-weight-light my-4">Login</h3>
                                </div>
                                <div class="card-body">
                                    <form action="<?= base_url('/auth/attempt') ?>" class="user" method="POST" id="loginForm">
                                        <?php echo csrf_field(); ?>
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
                                        <div class="form-floating mb-3">
                                            <?php if (ENVIRONMENT == 'development'): ?>
                                                <?php
                                                $defaultUser = '198511072009031002'; // <-- DEFAULT LOGIN DEV
                                                $devUsers = [
                                                    'J002 - Pranata Komputer' => [
                                                        '198511072009031002' => 'IRFAN FAUZI, A.Md.',
                                                    ],
                                                    'J036 - Arofah / Mina' => [
                                                        '197005091995031002' => 'SULIONO, S.Kep.Ns.',
                                                        '199004232019022005' => 'YULIANI, A.Md.Kep.',
                                                        '199102102019022006' => 'SEJUK KARISMA, A.Md.Kep.',
                                                    ],
                                                    'J039 - Aqsha / VIP' => [
                                                        '196807161989022002' => 'SUSI KRISTIANI, S.Kep.Ns.',
                                                        '199304282019022004' => 'CATHARINA TRY MAYA SOVA, A.Md.Kep.',
                                                        '198904012011012011' => 'APRILIA SISKARIYANTI, A.Md.Kep.',
                                                    ],
                                                    'J013 - Perekam Medis' => [
                                                        '198509192010011015' => 'ARIF RAKHMAD ANDRIANTO, A.Md.',
                                                    ],
                                                    'J014 - Bendahara' => [
                                                        '198310292010011001' => 'BAGUS IRAWAN OKTORIYANTO',
                                                    ],
                                                ];
                                                ?>
                                                <select class="form-select" id="user_id" name="user_id">
                                                    <?php foreach ($devUsers as $jabatan => $users): ?>
                                                        <optgroup label="<?= $jabatan ?>">
                                                            <?php foreach ($users as $id => $nama): ?>
                                                                <?php
                                                                $selected = old('user_id')
                                                                    ? old('user_id') == $id
                                                                    : $defaultUser == $id;
                                                                ?>
                                                                <option value="<?= $id ?>" <?= $selected ? 'selected' : '' ?>>
                                                                    <?= $nama ?>
                                                                </option>
                                                            <?php endforeach ?>
                                                        </optgroup>
                                                    <?php endforeach ?>
                                                </select>
                                                <label for="user_id">Pilih User (Development Mode)</label>
                                            <?php else: ?>
                                                <input type="text"
                                                    class="form-control form-control-user"
                                                    id="user_id"
                                                    name="user_id"
                                                    value="<?= old('user_id'); ?>"
                                                    placeholder="User ID">
                                                <label for="user_id">User ID</label>
                                            <?php endif ?>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input type="password"
                                                class="form-control form-control-user"
                                                placeholder="Password" id="password"
                                                name="password" value="123">
                                            <label for="password">Password</label>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                                            <button class="btn btn-primary btn-user btn-block">Login</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
        </main>
    </div>
    <div id="layoutAuthentication_footer">
        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">Copyright &copy; Your Website 2023</div>
                    <div>
                        <a href="#">Privacy Policy</a>
                        &middot;
                        <a href="#">Terms &amp; Conditions</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?= base_url('sb/js/scripts.js') ?>"></script>
</body>

</html>