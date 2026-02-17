<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        <?= $title ?>
    </div>
    <div class="card-body">
        <div class="card-body">
            <!-- Flash Message -->
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <form action="<?= base_url('kategori/store') ?>" method="post">
                <?= csrf_field() ?>

                <!-- Kode Kategori -->
                <div class="mb-3">
                    <label class="form-label">Kode Kategori</label>
                    <input type="text" name="kode_kategori" class="form-control"
                        placeholder="Contoh: SIMRS, IT-INF, OPEN-BILL" required>
                </div>

                <!-- Nama Kategori -->
                <div class="mb-3">
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" name="nama_kategori" class="form-control"
                        placeholder="Contoh: SIMRS" required>
                </div>

                <!-- Deskripsi -->
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="3"
                        placeholder="Jelaskan layanan kategori ini..."></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Unit Penanggung Jawab</label>
                    <div class="row">
                        <div class="col-md-7">
                            <select id="pjSelect" class="form-select">
                                <option value="">-- Pilih Jabatan --</option>
                                <?php foreach ($jabatan as $j): ?>
                                    <option value="<?= $j['kd_jbtn'] ?>">
                                        (<?= $j['kd_jbtn'] ?>) | <?= $j['nm_jbtn'] ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                            <small class="text-muted">Bisa memilih lebih dari satu</small>
                        </div>

                        <div class="col-md-5">
                            <div class="border rounded p-2 bg-light">
                                <small class="fw-bold text-muted">Unit Terpilih</small>
                                <div id="pjContainer" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="unit_penanggung_jawab" id="unit_penanggung_jawab">


                <!-- Unit Unit Pengajuan -->
                <div class="mb-3">
                    <label class="form-label">Unit Pengajuan</label>
                    <div class="row">
                        <div class="col-md-7">
                            <select id="pengajuanSelect" class="form-select">
                                <option value="">-- Pilih Jabatan --</option>
                                <?php foreach ($jabatan as $j): ?>
                                    <option value="<?= $j['kd_jbtn'] ?>">
                                        (<?= $j['kd_jbtn'] ?>) | <?= $j['nm_jbtn'] ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                            <small class="text-muted">Bisa memilih lebih dari satu</small>
                        </div>

                        <div class="col-md-5">
                            <div class="border rounded p-2 bg-light">
                                <small class="fw-bold text-muted">Unit Terpilih</small>
                                <div id="pengajuanContainer" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="unit_pengajuan" id="unit_pengajuan">



                <!-- Hidden hasil -->
                <input type="hidden" name="unit_penanggung_jawab" id="unit_penanggung_jawab">

                <!-- Status -->
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="aktif" class="form-select">
                        <option value="1" selected>Aktif</option>
                        <option value="0">Non Aktif</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Simpan Kategori
                </button>
            </form>
        </div>


        <input type="hidden" name="unit_penanggung_jawab" id="unit_penanggung_jawab">

        <script>
            function multiSelect(selectId, containerId, hiddenId) {
                const data = [];
                const select = document.getElementById(selectId);
                const container = document.getElementById(containerId);
                const hidden = document.getElementById(hiddenId);

                select.addEventListener('change', function() {
                    const kode = this.value;
                    const nama = this.options[this.selectedIndex].text;

                    if (!kode) return;

                    if (data.includes(kode)) {
                        this.value = '';
                        return;
                    }

                    data.push(kode);
                    render();
                    this.value = '';
                });

                function render() {
                    container.innerHTML = '';
                    data.forEach((kode, index) => {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-primary me-1 mb-1 d-inline-flex align-items-center';
                        badge.innerHTML = `
                ${index + 1}. ${kode}
                <button type="button"
                    class="btn-close btn-close-white ms-2"
                    style="font-size: .6em"
                    onclick="remove('${kode}')"></button>`;
                        container.appendChild(badge);
                    });
                    hidden.value = JSON.stringify(data);
                }

                window.remove = function(kode) {
                    const i = data.indexOf(kode);
                    if (i > -1) data.splice(i, 1);
                    render();
                };
            }

            // init
            multiSelect('pjSelect', 'pjContainer', 'unit_penanggung_jawab');
            multiSelect('pengajuanSelect', 'pengajuanContainer', 'unit_pengajuan');
        </script>



    </div>

</div>