<?= $this->extend('layout-dashboard/dashboard') ?>
<?= $this->section('content') ?>
<main>
    <?php
    // Gaya khusus halaman ini, bukan global.
    //
    // Kartu dashboard sengaja pakai CSS grid dengan repeat(5,...) dan
    // BUKAN kolom Bootstrap (col-xl-2 dan friends). Alasannya: dengan
    // breakpoint, begitu lebar jendela turun di bawah 1200px kartu
    // lompat dari 5 per baris ke 3 per baris. Grid 5 kolom memberi 5
    // kartu dalam satu baris di viewport berapa pun, jadi tidak ada
    // breakpoint yang bisa salah.
    //
    // minmax(0,1fr) -- bukan 1fr biasa -- wajib: kalau tidak, label
    // panjang seperti "Dalam Antrian" akan memaksa kolomnya melebar dan
    // menjatuhkan kolom lain ke baris berikutnya.
    //
    // min-width:0 pada tiap kolom supaya teks panjang di dalam kartu
    // ikut ter-truncate, bukan sebaliknya kolom melebar.
    //
    // Layout ini memakai .container-fluid yang lebarnya ikut melebar
    // bersama jendela, jadi kartu ikut kecil otomatis tanpa media
    // query. Dua aturan di bawah hanya untuk layar yang memang tidak
    // cukup lebar untuk 5 kolom.
    //
    // CATATAN: ini BUKAN .row Bootstrap. .row memakai margin negatif
    // kiri/kanan supaya kolom-kolomnya rata dengan konten di
    // sekitarnya -- margin itu justru membuat grid meluber ke luar
    // kartu induknya. Gap dipakai langsung di grid, jadi posisinya
    // sama dengan .row g-2 tanpa efek samping.
    ?>
    <style>
        .kartu-dashboard {
            min-width: 0;
        }

        @media (max-width: 991.98px) {
            .kartu-dashboard-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 575.98px) {
            .kartu-dashboard-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
    </style>

    <div class="container-fluid px-3">

        <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="fw-bold mb-0">Dashboard Saya</h5>
            <small class="text-muted">
                <i class="fas fa-list me-1"></i>My E-Tiket
                <span class="mx-1">|</span>
                <i class="fas fa-clipboard-list me-1"></i>Pelaksana
            </small>
        </div>

        <?php
        // Tiga kelompok, masing-masing satu query dan satu baris kartu.
        //
        // Setiap kartu menuju ke halaman tujuan grupnya, dibedakan lewat
        // query string. Scope-nya ditentukan oleh route tujuan itu sendiri
        // -- lihat DashboardService, yang memakai
        // ETicketModel::getTickets() dengan scope + filter yang sama
        // persis dengan halaman tujuan. Karena itu jumlah baris di
        // halaman selalu sama dengan angka di kartu.
        //
        // Grup 1 dan 2 menuju ke /etiket (scope lewat ?sumber=), grup 3
        // menuju ke /headsection (scope-nya dipaksa di route itu). Itu
        // sebabnya 'base' dan 'sumber' dipisah di bawah: untuk grup 3
        // 'sumber' sengaja TIDAK ikut, karena ?sumber=headsection sudah
        // tidak lagi sah di /etiket.
        $url = static function (array $q = [], string $base = 'etiket'): string {
            $q = array_filter($q, static fn ($v) => $v !== null && $v !== '');

            return base_url($base) . ($q === [] ? '' : '?' . http_build_query($q));
        };

        // Kartu status. Dua nama kunci dipakai, dan itu disengaja:
        //
        //   'k' -> nilai status untuk query string (?status=belum_valid)
        //   'n' -> kunci di array hasil DashboardService
        //
        // Keduanya TIDAK boleh dianggap sama. Service memakai camelCase
        // (belumValid, dalamAntrian) sementara status pakai snake_case.
        // Kalau nama kunci ikut memakai 'k' saja, dua kartu itu selalu 0
        // karena kuncinya tidak ditemukan lalu di-'?? 0'. 'selesai' dan
        // 'dikerjakan' kebetulan sama di kedua sisi, jadi bugnya tidak
        // terlihat di sana.
        $statusKartu = [
            ['k' => 'selesai', 'n' => 'selesai', 't' => 'Selesai', 'c' => 'success'],
            ['k' => 'dikerjakan', 'n' => 'dikerjakan', 't' => 'Dikerjakan', 'c' => 'warning'],
            ['k' => 'dalam_antrian', 'n' => 'dalamAntrian', 't' => 'Dalam Antrian', 'c' => 'info'],
            ['k' => 'belum_valid', 'n' => 'belumValid', 't' => 'Belum Valid', 'c' => 'secondary'],
        ];

        // Kartu Total: tidak punya status, jadi 'n' menunjuk ke total.
        $kartuTotal = [['k' => '', 'n' => 'total', 't' => 'Total', 'c' => 'primary']];

        // Satu kelompok = judul + daftar kartu. 'sumber' dipakai untuk
        // semua link di dalamnya; 'query' ditambahkan per kartu.
        $kelompok = [
            [
                'judul'  => 'Tiket yang dibuat saya',
                'ikon'   => 'fa-user',
                'base'   => 'etiket',
                'sumber' => 'saya',
                'query'  => [],
                'total'  => $milikSaya['total'],
                'angka'  => $milikSaya,
                'kartu'  => array_merge(
                    $kartuTotal,
                    $statusKartu
                ),
            ],
            [
                'judul'  => 'Tiket yang harus saya kerjakan',
                'ikon'   => 'fa-screwdriver-wrench',
                'base'   => 'etiket',
                'sumber' => 'pelaksana',
                'query'  => [],
                'total'  => $executor['total'],
                'angka'  => $executor,
                // Tanpa "Belum Valid": scope 'pelaksana' mensyaratkan
                // valid_nama IS NOT NULL, jadi angkanya selalu 0.
                'kartu'  => [
                    $kartuTotal[0],
                    $statusKartu[0], // selesai
                    $statusKartu[1], // dikerjakan
                    $statusKartu[2], // dalam_antrian
                ],
            ],
        ];

        // Kelompok "Tiket unit saya yang harus saya validasi" hanya untuk
        // headsection + admin. User biasa tidak punya wewenang menyetujui,
        // jadi menampilkan antrean itu hanya menawarkan tombol yang saat
        // diklik tidak melakukan apa-apa.
        if (! empty($isValidasi) && ! empty($validasi)) {
            $kelompok[] = [
                'judul'  => 'Tiket unit saya yang harus saya validasi',
                'ikon'   => 'fa-user-check',
                // Route sendiri, bukan /etiket?sumber=headsection:
                // tiket milik orang lain hanya boleh tampil di sini,
                // dan halamannya sudah digate filter 'roleheadsection'.
                'base'   => 'headsection',
                'sumber' => null,
                // Hanya kategori yang WAJIB approval headsection. Kalau
                // tidak ada kategori seperti itu, DashboardService
                // memakai semua tiket unit (flag 'fallback') -- dan
                // karena itu filter di sini ikut dilepas, supaya isi
                // halaman tujuan sama dengan angka di kartu.
                //
                // Filter ini wajib ada di URL: tanpa itu /headsection
                // menampilkan SEMUA tiket unit, sedangkan angka kartu
                // dihitung dari antrean approval saja.
                'query'  => empty($validasi['fallback']) ? ['headsection' => 1] : [],
                'total'  => $validasi['total'],
                'angka'  => $validasi,
                'kartu'  => array_merge(
                    $kartuTotal,
                    $statusKartu
                ),
            ];
        }

        ?>
        <?php foreach ($kelompok as $g): ?>
            <div class="mb-3">
                <div class="d-flex align-items-center mb-1">
                    <small class="fw-bold text-muted">
                        <i class="fas <?= esc($g['ikon']) ?> me-1"></i><?= esc($g['judul']) ?>
                    </small>
                </div>
                <?php
                // Fallback kelompok 3: tidak ada kategori yang wajib
                // approval headsection, jadi semua tiket unit ditampilkan.
                // Catatan ini WAJIB -- kalau tidak, user mengira angka ini
                // sudah final padahal cakupannya lebih luas dari judul.
                ?>
                <?php if (! empty($g['angka']['fallback'])): ?>
                    <small class="text-muted d-block mb-1 ms-1">
                        <i class="fas fa-info-circle me-1"></i>
                        Belum ada kategori yang wajib persetujuan headsection &mdash;
                        semua tiket unit ditampilkan.
                    </small>
                <?php endif; ?>
                <?php
                // GRID, bukan kolom Bootstrap. Rinciannya ada di blok
                // <style> di atas halaman ini.
                //
                // Kelompok 2 hanya punya 4 kartu, jadi slot kelima
                // sengaja dibiarkan kosong: lebarnya tetap sama dengan
                // kelompok lain sehingga angkanya bisa dibandingkan
                // langsung.
                ?>
                <div class="kartu-dashboard-grid"
                    style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.5rem;">
                    <?php
                    // Progress bar diukur terhadap total KELOMPOK ini, bukan
                    // total global: kalau diukur terhadap total global, kelompok
                    // yang jumlahnya kecil selalu menampilkan bar penuh
                    // dan jadi tidak informatif.
                    $dasar = max((int) $g['total'], 1);
                    ?>
                    <?php foreach ($g['kartu'] as $k): ?>
                        <?php
                        $nilai = (int) ($g['angka'][$k['n']] ?? 0);

                        // Kartu Total menaut ke seluruh kelompok; kartu
                        // status menambah ?status= ke link yang sama.
                        //
                        // Sumber ditulis lebih dulu supaya urutan
                        // parameter di URL konsisten antar kartu --
                        // mudah dibandingkan mata, dan test bisa
                        // menuliskan URL-nya persis.
                        // 'sumber' null untuk grup 3:/?sumber= hanya
                        // sah di /etiket.
                        $link = $url(($g['sumber'] === null ? [] : ['sumber' => $g['sumber']])
                            + $g['query']
                            + ($k['k'] === '' ? [] : ['status' => $k['k']]), $g['base']);

                        $percent = round(($nilai / $dasar) * 100);
                        ?>
                        <div class="kartu-dashboard">
                            <?php
                            // Kartu SENGAJA bukan <a>. Kalau seluruh area
                            // kartu jadi target klik, user mengira ada
                            // elemen di dalamnya yang bisa diklik, padahal
                            // tidak ada. Area klik yang besar dengan
                            // penanda visual kecil juga bikin target klik
                            // meleset.
                            //
                            // Baris paling atas: pengenal kartu di kiri,
                            // tautan di kanan. Angka turun ke baris sendiri
                            // supaya tidak ikut terpotong saat label
                            // panjang harus di-truncate.
                            //
                            // flex-shrink-0 pada link supaya yang terpotong
                            // label, bukan tautannya.
                            ?>
                            <div class="card shadow-sm h-100 p-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted text-truncate"><?= esc($k['t']) ?></small>
                                    <a href="<?= esc($link) ?>"
                                        class="small text-decoration-none flex-shrink-0 ms-2"
                                        title="Lihat daftar <?= esc($k['t']) ?>">
                                        Lihat <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </div>
                                <div class="fw-bold fs-5 lh-1 mt-1"><?= $nilai ?></div>
                                <div class="progress mt-1" style="height:3px;">
                                    <div class="progress-bar bg-<?= esc($k['c']) ?>" style="width: <?= $percent ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- ===================== -->
        <!-- PESAN KONDISI -->
        <!-- ===================== -->
        <?php
        $executorKosong = ($executor['total'] === 0);
        $executorHabis  = (! $executorKosong && $executor['tugas'] === 0);
        ?>
        <?php if ($executorKosong): ?>
            <div class="alert alert-info py-1 px-2 mb-2" role="alert">
                <small>
                    <i class="fas fa-info-circle me-1"></i>
                    Unit Anda belum punya tiket masuk. Section Executor terisi otomatis begitu ada tiket.
                </small>
            </div>
        <?php elseif ($executorHabis): ?>
            <div class="alert alert-success py-1 px-2 mb-2" role="alert">
                <small>
                    <i class="fas fa-check-circle me-1"></i>
                    Semua tiket unit Anda sudah selesai. Tidak ada yang perlu dikerjakan sekarang.
                </small>
            </div>
        <?php endif; ?>

        <!-- ===================== -->
        <!-- PERLU VALIDASI -->
        <!-- Tiket unit yang menunggu disetujui user -->
        <!-- Sama seperti kelompok kartunya: hanya untuk headsection + admin -->
        <!-- ===================== -->
        <?php if (! empty($isValidasi) && ! empty($validasi)): ?>
        <div class="card shadow-sm mb-2">
            <div class="card-header py-1 px-2 d-flex justify-content-between align-items-center">
                <small class="fw-bold">
                    <i class="fas fa-exclamation-triangle me-1 text-danger"></i>
                    Perlu Validasi
                </small>
                <?php
                // Badge memakai key yang sama dengan card "Belum Valid"
                // di kelompok unit, dan loopnya memakai antrianValidasi
                // dari validasiData() -- keduanya satu sumber. Kalau
                // dipisah, angka di header bisa berbeda dari jumlah
                // baris di tabel.
                ?>
                <span class="badge bg-danger"><?= $validasi['belumValid'] ?></span>
            </div>
            <?php if (empty($validasi['antrianValidasi'])): ?>
                <div class="card-body py-2">
                    <small class="text-muted mb-0">
                        Tidak ada tiket yang menunggu validasi.
                    </small>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Pengaju</th>
                                <th>Tanggal</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($validasi['antrianValidasi'] as $t): ?>
                                <tr>
                                    <td class="text-nowrap"><?= esc($t['nama_kategori'] ?? '-') ?></td>
                                    <td class="text-nowrap"><?= esc($t['petugas_id_nama'] ?? '-') ?></td>
                                    <td class="text-nowrap small"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                    <td class="text-end">
                                        <?php
                                        // Selalu "Proses". Isinya murni tiket
                                        // unit yang diajukan orang lain, jadi
                                        // tidak mungkin milik sendiri. Badge
                                        // "Saya" juga karena itu tidak ada
                                        // lagi.
                                        //
                                        // Ke /headsection, bukan /etiket: tiket milik orang lain
                                        // hanya boleh dibuka dari route yang digate 'roleheadsection'.
                                        ?>
                                        <a href="<?= base_url('headsection/' . ($t['hashid'] ?? $t['id'])) ?>"
                                           class="btn btn-sm btn-primary py-0">Proses</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; /* ! $isValidasi */ ?>

        <?php
        // Section "Sudah Disetujui - Menunggu Unit Tujuan" DIHAPUS.
        //
        // Isinya sudah tercakup di kelompok kartu "Tiket unit saya yang
        // harus saya validasi": status Dalam Antrian + Dikerjakan di
        // sana persis berarti "sudah disetujui, menunggu unit tujuan".
        // Daftar yang bisa diklik juga sudah ada di /etiket?sumber=
        // headsection, jadi tabelnya hanya mengulang informasi yang sama
        // dengan bentuk yang lebih panjang.
        //
        // DashboardService::sedangDisetujuiData() sengaja TIDAK dihapus
        // -- hanya pemanggilannya yang dibuang, supaya method itu tetap
        // ada kalau nanti diperlukan.
        ?>
    </div>
</main>

<?= $this->endSection() ?>
