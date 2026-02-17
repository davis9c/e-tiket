<div class="col-md-6 col-lg-5">
    <div class="card-body">
        <div class="card shadow mb-4">
            <div class="card-header py-2">
                <h6 class="m-0 font-weight-bold text-primary">
                    Daftar Jabatan
                </h6>
            </div>

            <div class="card-body">
                <div class="row">

                    <?php foreach ($jabatan as $j): ?>
                        <?php if ($j['kd_jbtn'] === '-') continue; ?>

                        <?php
                        $isActive = ($jbtn ?? null) === $j['kd_jbtn'];

                        $cardClass = $isActive
                            ? 'bg-success text-white shadow-lg border-0'
                            : 'border-left-primary shadow';

                        $textColorKode = $isActive
                            ? 'text-white'
                            : 'text-primary';

                        $textColorNama = $isActive
                            ? 'text-white'
                            : 'text-gray-800';
                        ?>

                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                            <a href="<?= base_url('admin/petugas/' . esc($j['kd_jbtn'])) ?>"
                                class="text-decoration-none">

                                <div class="card <?= $cardClass ?> h-100 py-2">

                                    <div class="card-body">

                                        <div class="row align-items-center no-gutters">

                                            <div class="col mr-2">

                                                <div class="text-xs font-weight-bold <?= $textColorKode ?> text-uppercase mb-1">
                                                    <?= esc($j['kd_jbtn']) ?>
                                                </div>

                                                <div class="small font-weight-bold <?= $textColorNama ?>">
                                                    <?= esc($j['nm_jbtn']) ?>
                                                </div>

                                            </div>

                                            <?php if ($isActive): ?>
                                                <div class="col-auto">
                                                    <i class="fas fa-check-circle text-white fa-lg"></i>
                                                </div>
                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </div>

                            </a>
                        </div>

                    <?php endforeach; ?>

                </div>

            </div>
        </div>

    </div>
</div>