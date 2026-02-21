<div class="col-md-6 col-lg-5">
    <div class="card shadow mb-4">
        <div class="card-header py-2">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Jabatan</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($jabatan as $j): ?>
                    <?php if ($j['kd_jbtn'] === '-') continue; ?>
                    <?php
                    $isActive = ($jbtn ?? null) === $j['kd_jbtn'];
                    $cardClass = $isActive ? 'bg-success text-white shadow-lg border-0' : 'border-left-primary shadow';
                    $textColor = $isActive ? 'text-white' : 'text-gray-800';
                    ?>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                        <a href="<?= base_url('admin/petugas/' . esc($j['kd_jbtn'])) ?>" class="text-decoration-none">
                            <div class="card <?= $cardClass ?> h-100 py-2">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="small font-weight-bold <?= $textColor ?>">
                                            <?= esc($j['nm_jbtn']) ?>
                                        </div>
                                        <?php if ($isActive): ?>
                                            <i class="fas fa-check-circle text-white fa-lg"></i>
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