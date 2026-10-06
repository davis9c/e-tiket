<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Menguji partial halaman per-tiket tanpa lewat HTTP dan tanpa database.
 *
 * Alasan ada tes ini: ketiga partial (header, status, tindakan) dipakai
 * bersama oleh /etiket/{hashid}, /headsection/{hashid}, dan
 * /allticket/{hashid}. Perubahan salah satu akan langsung mengubah
 * ketiganya, dan kesalahan di sana tidak muncul sebagai exception yang
 * mudah dilacak -- misalnya variabel yang tidak pernah terdefinisi hanya
 * menyebabkan halaman kosong satu bagian.
 *
 * Semua partial dirender dengan $data yang meniru hasil
 * ETicket2::renderTicketList(). Yang diuji bukan tampilan, tapi:
 *   - partial tidak melempar error pada kombinasi data yang wajar,
 *   - field yang dipakai view benar-benar diteruskan controller
 *     (regresi sanitizeTicketDetail()),
 *   - kondisi yang dulu terbalik tidak terbalik lagi.
 */
final class TicketDetailViewTest extends CIUnitTestCase
{
    protected $refresh = false;
    protected $migrate = false;

    /**
     * Bentuk $data yang sama dengan renderTicketList() hasilkan, dengan
     * semua field yang mungkin hilang. Sengaja tidak
     * lengkap supaya view wajib tetap aman saat data langka.
     */
    private function payload(array $overrides = []): array
    {
        $detail = array_merge([
            'id'                         => 7,
            'hashid'                     => 'abc123',
            'kode_ticket'                => 'ETK-2026-0007',
            'judul'                      => 'Permintaan perbaikan AC Ruang 3',
            'created_at'                 => '2026-10-01 08:30:00',
            'updated_at'                 => '2026-10-02 09:00:00',
            'kode_kategori'              => 'SAR',
            'nama_kategori'              => 'Sarana dan Prasarana',
            'deskripsi'                  => 'Perbaikan facility',
            'petugas_id_nama'            => 'Budi Santoso',
            'petugas_id'                 => '198001012010011234',
            'nm_jbtn'                    => 'Unit Sarana',
            'headsection'                => 0,
            'valid_nama'                 => 'Ka Unit',
            'status'                     => 'dikerjakan',
            'handler'                    => '198001012010011234',
            'handler_nama'               => 'Budi Santoso',
            'message_catatan'            => '<p>AC ruang 3 tidak Turning.</p><p> urgently </p>',
            'message_lampiran'           => 'permintaan-ac.jpg',
            // Id baris proses untuk pesan ini. Menyamakan id dengan baris
            // rproses di test riwayat.
            'message_id'                 => 1,
            'message_nm_jbtn'            => 'Unit Teknisi',
            'message_kd_jbtn'            => 'J077',
            'message_id_petugas_nama'    => 'Andi',
            'message_created_at'         => '2026-10-01 08:30:00',
            'respon_message_id'          => 99,
            'respon_message_catatan'     => '<p>Sudah diganti.</p>',
            'respon_message_lampiran'    => 'jawaban-ac.png',
            'respon_message_nm_jbtn'     => 'Unit Sarana',
            'respon_message_kd_jbtn'     => 'J001',
            'respon_message_id_petugas_nama' => 'Sari',
            'respon_message_created_at'  => '2026-10-02 09:00:00',
            // Nama unit di sini sengaja dibuat berbeda dari nama unit lain
            // di payload (Unit Sarana = unit pengaju, Unit Teknisi = unit
            // di message_nm_jbtn). Kalau nama ini sama dengan nama di tempat
            // lain, assertion "unit tampil" bisa lulus karena kemunculannya
            // di tempat lain -- persis cara bug empty() yang terbalik lolos
            // tanpa ketahuan.
            'unit_penanggung_jawab'      => [
                ['kd_jbtn' => 'J009', 'nm_jbtn' => 'UPJ-Alpha', 'is_proses' => true],
                ['kd_jbtn' => 'J010', 'nm_jbtn' => 'UPJ-Beta',  'is_proses' => false],
            ],
        ], $overrides['detail'] ?? []);

        $tindakan = $overrides['tindakan'] ?? [
            'validasi'  => null,
            'kerjakan'  => null,
            'teruskan'  => null,
            'kategoric' => null,
            'edittiket' => null,
            'rproses'   => [],
            'pesan'     => 'Tidak ada tindakan',
        ];

        return [
            'title' => 'E-Ticket',
            'data'  => [
                'detailTicket'    => $detail,
                'timeline_status' => $overrides['timeline'] ?? [
                    ['type' => 'created', 'color' => 'primary', 'icon' => 'fa-solid fa-pencil', 'text' => 'Tiket Dibuat 01 Oct 2026'],
                    ['type' => 'queue',   'color' => 'warning', 'icon' => 'fa-solid fa-hourglass-half', 'text' => 'Sedang Dikerjakan Budi Santoso'],
                ],
                'tindakan'      => $tindakan,
                'eticket'       => $overrides['eticket'] ?? [
                    ['id' => 6, 'hashid' => 'bbb111', 'nama_kategori' => 'Lain', 'kategori_id' => 1,
                     'petugas_id_nama' => 'Budi', 'message_catatan' => 'x', 'created_at' => '2026-10-01 07:00:00', 'status' => 'selesai'],
                    ['id' => 7, 'hashid' => 'abc123', 'nama_kategori' => 'Sarana dan Prasarana', 'kategori_id' => 2,
                     'petugas_id_nama' => 'Budi', 'message_catatan' => 'x', 'created_at' => '2026-10-01 08:30:00', 'status' => 'dikerjakan'],
                    ['id' => 8, 'hashid' => 'ccc333', 'nama_kategori' => 'Lain', 'kategori_id' => 1,
                     'petugas_id_nama' => 'Budi', 'message_catatan' => 'x', 'created_at' => '2026-10-01 09:00:00', 'status' => 'dalam_antrian'],
                ],
                'kategori'      => $overrides['kategori'] ?? [],
                'filters'       => $overrides['filters'] ?? ['sumber' => []],
                'sumberFilter'  => false,
                'user'          => [],
            ],
        ];
    }

    private function render(string $view, array $payload): string
    {
        return view($view, $payload);
    }

    /**
     * Link detail dibentuk dari service('uri')->getSegment(1), jadi URI
     * harus disetel -- tanpa ini segment kosong dan tautannya jatuh ke root
     * alih-alih /etiket/{hashid}.
     */
    protected function setUp(): void
    {
        parent::setUp();

        service('uri')->setPath('etiket/abc123');
    }

    /* =====================================================
     | HEADER
     |===================================================== */

    public function testHeaderMenampilkanIdentitasTiket(): void
    {
        $html = $this->render('e-tiket/header', $this->payload());

        $this->assertStringContainsString('Permintaan perbaikan AC Ruang 3', $html);
        $this->assertStringContainsString('ETK-2026-0007', $html);
        $this->assertStringContainsString('Dikerjakan', $html, 'Badge status harus tampil');
    }

    /**
     * Kolom judul (e_ticket.judul) ada di database dan sudah jadi heading.
     * Kalau kosong, judul halaman harus jatuh ke kategori supaya tidak
     * ada heading kosong.
     */
    public function testHeaderJatuhKeKategoriKalauJudulKosong(): void
    {
        $html = $this->render('e-tiket/header', $this->payload([
            'detail' => ['judul' => null],
        ]));

        $this->assertStringContainsString('Sarana dan Prasarana', $html);
    }

    /**
     * Navigasi Before/After: tiket di tengah daftar harus punya keduanya.
     */
    public function testHeaderMenavigasiTetanggaDariPosisiDetail(): void
    {
        $html = $this->render('e-tiket/header', $this->payload());

        $this->assertStringContainsString('/etiket/bbb111', $html, 'Tiket sebelumnya harus tertaut');
        $this->assertStringContainsString('/etiket/ccc333', $html, 'Tiket berikutnya harus tertaut');
    }

    /**
     * Tiket pertama tidak punya "sebelum", tapi harus tetap punya "sesudah".
     * Kalau tidak, ada indeks negatif yang dipakai.
     */
    public function testHeaderTidakMenautkanTetanggaYangTidakAda(): void
    {
        $html = $this->render('e-tiket/header', $this->payload([
            'eticket' => [
                ['id' => 7, 'hashid' => 'abc123', 'nama_kategori' => 'Sarana', 'status' => 'dikerjakan'],
            ],
        ]));

        $this->assertStringNotContainsString('etiket/', $html, 'Tidak ada tetangga, tidak boleh ada link');
    }

    /**
     * Query string aktif ikut dibawa ke Before/After, supaya filter yang
     * sedang dipakai tidak hilang saat pindah tiket.
     */
    public function testNavigasiMembawaQueryStringAktif(): void
    {
        $_SERVER['QUERY_STRING'] = 'status=selesai&kategori=2';

        try {
            $html = $this->render('e-tiket/header', $this->payload());
        } finally {
            unset($_SERVER['QUERY_STRING']);
        }

        $this->assertStringContainsString('?status=selesai', $html);
        $this->assertStringContainsString('kategori=2', $html);
    }

    /* =====================================================
     | STATUS / TIMELINE
     |===================================================== */

    /**
     * Regresi: syaratnya pernah empty() padahal bloknya yang MENCETAK
     * daftar unit. Akibatnya badge unit tidak pernah muncul -- dan
     * postgres/mysql alike, "kartu kategori jadi kosong tanpa alasan".
     */
    public function testUnitPenanggungJawabTampil(): void
    {
        $html = $this->render('e-tiket/e-tiket-status', $this->payload());

        $this->assertStringContainsString('UPJ-Alpha', $html, 'Unit penanggung jawab harus tampil');
        $this->assertStringContainsString('UPJ-Beta', $html);
    }

    public function testUnitPenanggungJawabKosongMemakaiFallback(): void
    {
        $html = $this->render('e-tiket/e-tiket-status', $this->payload([
            'detail' => ['unit_penanggung_jawab' => []],
        ]));

        $this->assertStringContainsString('Tidak ada unit tujuan', $html);
    }

    /**
     * is_proses diteruskan mapUnitWithJabatan(). Kalau hilang, view tidak
     * bisa membedakan unit yang sudah memproses dari yang belum.
     */
    public function testUnitYangSudahProsesDitandai(): void
    {
        $html = $this->render('e-tiket/e-tiket-status', $this->payload());

        $this->assertStringContainsString('sudah memproses', $html);
        $this->assertMatchesRegularExpression(
            '/bg-success[^>]*>\s*(?:<i[^>]*><\/i>)?\s*1\.\s*UPJ-Alpha/',
            $html,
            'Unit yang sudah memproses harus memakai badge hijau'
        );
    }

    /**
     * Kelas .timeline* tidak punya definisi CSS di mana pun sebelum
     * perubahan ini, jadi kartu status merender teks bertumpuk. Partial
     * harus membawa definisinya sendiri.
     */
    public function testTimelineMembawaCssSendiri(): void
    {
        $html = $this->render('e-tiket/e-tiket-status', $this->payload());

        $this->assertStringContainsString('.timeline-tiket', $html);
        $this->assertStringContainsString('.timeline-item::before', $html);
        $this->assertStringContainsString('Sedang Dikerjakan Budi Santoso', $html);
    }

    public function testTimelineKosongTidakMunculkanTimelineKosong(): void
    {
        $html = $this->render('e-tiket/e-tiket-status', $this->payload(['timeline' => []]));

        $this->assertStringContainsString('Status ticket belum tersedia', $html);
    }

    /**
     * Regresi: preview dipotong 3 baris tapi "..." hanya dipasang kalau
     * lebih dari 6 baris, jadi baris ke-4..6 terpotong tanpa penanda.
     */
    public function testPreviewDeskripsiDipotongDenganPenanda(): void
    {
        $html = $this->render('e-tiket/e-tiket-status', $this->payload([
            'detail' => [
                'message_catatan' => '<p>1</p><p>2</p><p>3</p><p>4</p>',
            ],
        ]));

        $this->assertStringContainsString('&hellip;', $html, 'Preview panjang harus ditandai ellipsis');
    }

    public function testPreviewPendekTidakDipotong(): void
    {
        $html = $this->render('e-tiket/e-tiket-status', $this->payload([
            'detail' => ['message_catatan' => '<p>Hanya satu baris.</p>'],
        ]));

        $this->assertStringNotContainsString('&hellip;', $html);
    }

    /**
     * Lampiran pesan awal DAN jawaban keduanya harus tampil. Sebelumnya
     * keduanya tidak pernah muncul karena sanitizeTicketDetail()
     * membuang field lampiran padahal query sudah mengambilnya.
     */
    public function testLampiranPermintaanDanJawabanTampil(): void
    {
        $html = $this->render('e-tiket/e-tiket-status', $this->payload());

        $this->assertStringContainsString('lampiran/view/' . rawurlencode('permintaan-ac.jpg'), $html);
        $this->assertStringContainsString('lampiran/view/' . rawurlencode('jawaban-ac.png'), $html);
    }

    public function testLampiranKosongTidakKirimMarkup(): void
    {
        $html = $this->render('e-tiket/e-tiket-status', $this->payload([
            'detail' => [
                'message_lampiran'        => null,
                'respon_message_lampiran' => null,
            ],
        ]));

        $this->assertStringNotContainsString('lampiran/view/', $html);
    }

    /**
     * Nama pengaju diambil dari baris proses (message_*), bukan dari
     * kolom tiket. Kalau sanitizeTicketDetail() membuangnya, nama yang
     * tampil jatuh ke unit -- bukan orang.
     */
    public function testNamaPengajuDariBarisPesan(): void
    {
        $html = $this->render('e-tiket/e-tiket-status', $this->payload());

        $this->assertStringContainsString('Unit Teknisi', $html);
        $this->assertStringContainsString('Andi', $html);
    }

    /* =====================================================
     | TINDAKAN
     |===================================================== */

    public function testTidakAdaTindakanMenampilkanPesanController(): void
    {
        $html = $this->render('e-tiket/e-tiket-tindakan', $this->payload([
            'tindakan' => [
                'validasi' => null, 'kerjakan' => null, 'teruskan' => null,
                'kategoric' => null, 'edittiket' => null, 'rproses' => [], 'pesan' => 'Tiket selesai',
            ],
        ]));

        $this->assertStringContainsString('Tiket selesai', $html);
    }

    /**
     * Kelima tombol aksi selalu dirender. Yang tidak boleh dipakai diberi
     * disabled, bukan disembunyikan, supaya cakupan tindakan tetap terlihat.
     */
    public function testSemuaTombolAksiSelaluDirender(): void
    {
        $html = $this->render('e-tiket/e-tiket-tindakan', $this->payload([
            'tindakan' => [
                'validasi'  => null,
                'kerjakan'  => null,
                'teruskan'  => null,
                'kategoric' => null,
                'edittiket' => null,
                'rproses'   => [],
                'pesan'     => 'Tidak ada tindakan',
            ],
        ]));

        foreach (['Validasi', 'Kerjakan', 'Teruskan', 'Kategori', 'Edit'] as $label) {
            $this->assertStringContainsString($label, $html, "Tombol $label harus tetap tampil");
        }

        // Lima tombol aksi, semuanya tidak aktif. Dicocokkan dengan pola
        // penutup tag, bukan kata 'disabled' polos: komentar di dalam markup
        // juga menyebut kata itu dan akan ikut terhitung.
        $this->assertSame(5, substr_count($html, '" disabled>'));
    }

    /**
     * Tombol disabled tidak boleh punya data-bs-target. Modalnya memang
     * tidak dirender, jadi targetnya akan menggantung -- Bootstrap
     * mengabaikannya tanpa error, sehingga tombolnya terlihat normal tapi
     * tidak melakukan apa pun kalau diklik.
     */
    public function testTombolDisabledTidakPunyaTargetModal(): void
    {
        $html = $this->render('e-tiket/e-tiket-tindakan', $this->payload([
            'tindakan' => [
                'validasi'  => null,
                'kerjakan'  => null,
                'teruskan'  => null,
                'kategoric' => null,
                'edittiket' => null,
                'rproses'   => [],
                'pesan'     => 'Tidak ada tindakan',
            ],
        ]));

        foreach (['modalValidasi', 'modalKerjakan', 'modalTeruskan', 'modalKategori', 'modalEditTicket'] as $modal) {
            $this->assertStringNotContainsString('#' . $modal, $html, "Tombol disabled tidak boleh menunjuk ke $modal");
            $this->assertStringNotContainsString('id="' . $modal . '"', $html, "Modal $modal tidak boleh dirender");
        }
    }

    public function testTombolKerjakanAktifKalauDiizinkan(): void
    {
        $html = $this->render('e-tiket/e-tiket-tindakan', $this->payload([
            'tindakan' => [
                'validasi' => null,
                'kerjakan' => ['form' => ['url' => base_url('etiket/submit_final'), 'ticket_id' => ['variable' => 'ticket_id', 'value' => 'abc123']]],
                'teruskan' => null,
                'kategoric' => null,
                'edittiket' => null,
                'rproses' => [],
                'pesan' => 'Pelaksana dapat mengerjakan',
            ],
        ]));

        $this->assertStringContainsString('data-bs-target="#modalKerjakan"', $html, 'Tombol aktif harus menunjuk modalnya');
        $this->assertStringContainsString('submit_final', $html);
        $this->assertStringContainsString('id="modalKerjakan"', $html);

        // Empat tombol lain tetap ada, disabled, tanpa target.
        $this->assertSame(4, substr_count($html, '" disabled>'));
        $this->assertStringNotContainsString('#modalValidasi', $html);
    }

    /**
     * Riwayat proses adalah aksi baca, jadi tetap aktif walau semua aksi
     * tulis tidak boleh dipakai.
     */
    public function testRiwayatProsesTetapAdaTanpaAksiTulis(): void
    {
        $html = $this->render('e-tiket/e-tiket-tindakan', $this->payload([
            'tindakan' => [
                'validasi' => null, 'kerjakan' => null, 'teruskan' => null,
                'kategoric' => null, 'edittiket' => null,
                'rproses' => [
                    // id 1 = baris permintaan (sama dengan message_id di payload).
                    ['id' => 1, 'nm_jbtn' => 'Unit Sarana', 'id_petugas_nama' => 'Budi', 'catatan' => 'Butuh AC', 'lampiran' => null, 'created_at' => '2026-10-01 08:30:00'],
                    ['id' => 2, 'nm_jbtn' => 'UPJ-Alpha', 'id_petugas_nama' => 'Andi', 'catatan' => 'Sudah diperbaiki', 'lampiran' => 'bukti.png', 'created_at' => '2026-10-01 10:00:00'],
                ],
                'pesan' => 'Tidak ada tindakan',
            ],
        ]));

        $this->assertStringContainsString('modalRProsess', $html);
        $this->assertStringContainsString('Sudah diperbaiki', $html);
    }

    /**
     * Isi permintaan sudah tampil penuh di kartu Permintaan, jadi baris
     * proses yang sama tidak boleh muncul lagi di Riwayat Proses.
     */
    public function testRiwayatProsesTidakMenampilkanPermintaan(): void
    {
        $html = $this->render('e-tiket/e-tiket-tindakan', $this->payload([
            'tindakan' => [
                'validasi' => null, 'kerjakan' => null, 'teruskan' => null,
                'kategoric' => null, 'edittiket' => null,
                'rproses' => [
                    ['id' => 1, 'nm_jbtn' => 'Unit Sarana', 'id_petugas_nama' => 'Budi', 'catatan' => 'Butuh AC', 'lampiran' => null, 'created_at' => '2026-10-01 08:30:00'],
                    ['id' => 2, 'nm_jbtn' => 'UPJ-Alpha', 'id_petugas_nama' => 'Andi', 'catatan' => 'Sudah diperbaiki', 'lampiran' => null, 'created_at' => '2026-10-01 10:00:00'],
                ],
                'pesan' => 'Tidak ada tindakan',
            ],
        ]));

        $this->assertStringNotContainsString(
            'Butuh AC',
            $html,
            'Baris yang isinya permintaan tidak boleh dirender di riwayat'
        );
        $this->assertStringContainsString('Sudah diperbaiki', $html);
    }

    /**
     * Tiket yang baru dibuat punya tepat satu baris proses, yaitu permintaannya
     * sendiri (ETicket2::submit() menyimpan proses awal lalu menunjuk
     * message_awal ke sana). Setelah baris itu disaring, riwayatnya kosong --
     * jadi tombolnya harus hilang, bukan membuka modal kosong.
     */
    public function testRiwayatKosongSetelahPermintaanDibuang(): void
    {
        $html = $this->render('e-tiket/e-tiket-tindakan', $this->payload([
            'tindakan' => [
                'validasi' => null, 'kerjakan' => null, 'teruskan' => null,
                'kategoric' => null, 'edittiket' => null,
                'rproses' => [
                    ['id' => 1, 'nm_jbtn' => 'Unit Sarana', 'id_petugas_nama' => 'Budi', 'catatan' => 'Butuh AC', 'lampiran' => null, 'created_at' => '2026-10-01 08:30:00'],
                ],
                'pesan' => 'Tidak ada tindakan',
            ],
        ]));

        $this->assertStringNotContainsString('modalRProsess', $html, 'Tombol harus hilang, bukan membuka modal kosong');
        $this->assertStringNotContainsString('Butuh AC', $html);
    }

    /**
     * Kalau message_id tidak diteruskan -- mis. baris proses permintaan sudah
     * dihapus -- tidak ada yang boleh dibuang. Membuang berdasarkan tebakan
     * akan menghapus riwayat yang masih relevan.
     */
    public function testRiwayatUtuhKalauMessageIdTidakAda(): void
    {
        $html = $this->render('e-tiket/e-tiket-tindakan', $this->payload([
            'detail' => ['message_id' => null],
            'tindakan' => [
                'validasi' => null, 'kerjakan' => null, 'teruskan' => null,
                'kategoric' => null, 'edittiket' => null,
                'rproses' => [
                    ['id' => 1, 'nm_jbtn' => 'Unit Sarana', 'id_petugas_nama' => 'Budi', 'catatan' => 'Butuh AC', 'lampiran' => null, 'created_at' => '2026-10-01 08:30:00'],
                ],
                'pesan' => 'Tidak ada tindakan',
            ],
        ]));

        $this->assertStringContainsString('modalRProsess', $html);
        $this->assertStringContainsString('Butuh AC', $html);
    }

    /* =====================================================
     | MODAL PESAN (Permintaan / Keputusan)
     |===================================================== */

    /**
     * Blok identitas harus berlabel. Tanpa itu, nama di modal dibaca sebagai
     * pengaju tiket yang sama dengan di kartu meta -- padahal yang di sini
     * adalah penulis pesan, data yang berbeda.
     */
    public function testModalPermintaanMelolabelkanPenulisPesan(): void
    {
        $html = $this->render('e-tiket/e-tiket-status', $this->payload());

        $this->assertStringContainsString('Diajukan oleh', $html);
        $this->assertStringContainsString('Andi', $html, 'Nama orang harus tampil');
        $this->assertStringContainsString('Unit Teknisi', $html, 'Nama unit harus tampil');
    }

    /**
     * Timestamp sebelumnya tersesat di modal-footer sebagai caption. Sekarang
     * pindah ke blok identitas, dekat dengan siapa yang menulis.
     */
    public function testTimestampPindahKeBlokIdentitas(): void
    {
        $html = $this->render('e-tiket/e-tiket-status', $this->payload());

        preg_match('/id="modalPermintaan".*?<\/div>\s*<\/div>\s*<\/div>/s', $html, $m);
        $modal = $m[0] ?? '';

        $this->assertStringContainsString('01 Oct 2026', $modal);
        // Footer modal tidak lagi memegang timestamp.
        $this->assertStringNotContainsString('justify-content-between', $modal);
    }

    public function testIsiPermintaanMemecahBarisPanjang(): void
    {
        $html = $this->render('e-tiket/e-tiket-status', $this->payload());

        preg_match('/id="modalPermintaan".*?<\/div>\s*<\/div>\s*<\/div>/s', $html, $m);
        $modal = $m[0] ?? '';

        // Isi CKEditor perlu text-break supaya string panjang tanpa spasi
        // (URL, kode) tidak meluber keluar modal.
        $this->assertStringContainsString('text-break', $modal);
    }

    /**
     * Kode unit adalah data yang sudah diambil query. Kalau tidak diteruskan
     * whitelist, !empty() di view selalu salah dan kode diam-diam hilang.
     */
    public function testKodeUnitMunculKalauDiteruskan(): void
    {
        $ada = $this->render('e-tiket/e-tiket-status', $this->payload([
            'detail' => ['message_kd_jbtn' => 'J077'],
        ]));
        $takAda = $this->render('e-tiket/e-tiket-status', $this->payload([
            'detail' => ['message_kd_jbtn' => null],
        ]));

        $this->assertStringContainsString('J077', $ada);
        $this->assertStringNotContainsString('J077', $takAda);
    }

    /**
     * Permintaan dan Keputusan Final memakai partial yang sama, jadi
     * keduanya harus punya bentuk yang sama persis -- termasuk urutan
     * nama orang di atas unit.
     */
    public function testKeduaModalPesanMemakaiBentukYangSama(): void
    {
        $html = $this->render('e-tiket/e-tiket-status', $this->payload());

        $ambil = static function (string $id) use ($html): string {
            preg_match('/id="' . $id . '".*?<\/div>\s*<\/div>\s*<\/div>/s', $html, $m);
            return $m[0] ?? '';
        };

        $permintaan = $ambil('modalPermintaan');
        $keputusan  = $ambil('modalKeputusan');

        // Regex harusnya sudah menangkap keduanya; kalau tidak, seluruh
        // assertion di bawah berarti menguji string kosong.
        $this->assertNotSame('', $permintaan, 'Modal permintaan tidak ditemukan');
        $this->assertNotSame('', $keputusan, 'Modal keputusan tidak ditemukan');

        foreach ([$permintaan, $keputusan] as $modal) {
            $this->assertStringContainsString('text-break', $modal);
            $this->assertStringContainsString('btn-secondary', $modal);
            $this->assertStringNotContainsString('justify-content-between', $modal);
        }

        $this->assertStringContainsString('Diajukan oleh', $permintaan);
        $this->assertStringContainsString('Dijawab oleh', $keputusan);

        // Nama orang harus mendahului unit di keduanya.
        $this->assertLessThan(
            strpos($permintaan, 'Unit Teknisi'),
            strpos($permintaan, 'Andi')
        );
        $this->assertLessThan(
            strpos($keputusan, 'Unit Sarana'),
            strpos($keputusan, 'Sari')
        );
    }

    /* =====================================================
     | PARTIAL LAIN
     |===================================================== */

    public function testModalPilihKategoriMenyesuaikanTarget(): void
    {
        $baru = $this->render('e-tiket/modal-pilih-kategori', $this->payload([
            'kategori' => [['id' => 5, 'kode_kategori' => 'SAR', 'nama_kategori' => 'Sarana', 'deskripsi' => 'desc', 'unit_penanggung_jawab' => [['kd_jbtn' => 'J001', 'nm_jbtn' => 'Unit Sarana']]]],
        ]) + ['targetForm' => 'baru']);

        $manual = $this->render('e-tiket/modal-pilih-kategori', $this->payload([
            'kategori' => [['id' => 5, 'kode_kategori' => 'SAR', 'nama_kategori' => 'Sarana', 'deskripsi' => 'desc', 'unit_penanggung_jawab' => []]],
        ]) + ['targetForm' => 'manual']);

        $this->assertStringContainsString('baru?kategori=5', $baru);
        $this->assertStringContainsString('manual-baru?kategori=5', $manual);
    }

    /* =====================================================
     | HALAMAN UTUH
     |===================================================== */

    /**
     * Partial-nya masing-masing bisa lolos, tapi gabungannya belum tentu.
     * renderTicketList() mengirim $data['timeline_status']; view yang salah
     * membacanya akan lolos saat partial diuji terpisah, dan gagal begitu
     * seluruh halaman dirender.
     *
     * list.php memakai character_limiter() dari helper 'text', yang dalam
     * aplikasi nyata dimuat ETicket2::__construct(). Controller tidak ikut
     * dipakai di sini, jadi helper-nya dimuat manual.
     */
    private function halaman(string $view, bool $adaDetail, string $path = 'etiket/abc123'): string
    {
        helper('text');
        service('uri')->setPath($path);

        $payload = $this->payload();
        if (! $adaDetail) {
            $payload['data']['detailTicket'] = null;
            // Halaman tanpa detail merender tombol Buat Tiket, jadi
            // kategori harus tersedia -- kalau tidak, itu kondisi nyata
            // database kosong dan tetap harus tidak error.
            $payload['data']['kategori'] = [];
        }

        return view($view, $payload);
    }

    public function testHalamanPerTiketTerenderUtuh(): void
    {
        $html = $this->halaman('e-tiket', true);

        $this->assertStringContainsString('Permintaan perbaikan AC Ruang 3', $html);
        $this->assertStringContainsString('Daftar E-Tiket', $html);
        $this->assertStringContainsString('baris-tindakan', $html);
    }

    public function testHalamanDaftarTerenderUtuh(): void
    {
        $html = $this->halaman('e-tiket', false);

        $this->assertStringContainsString('Daftar E-Tiket', $html);
    }

    /**
     * /allticket memakai partial yang sama tapi daftar dan judulnya sendiri.
     */
    public function testHalamanAllTicketTerenderUtuh(): void
    {
        $detail = $this->halaman('allticket', true, 'allticket/abc123');
        $daftar = $this->halaman('allticket', false, 'allticket');

        $this->assertStringContainsString('All E-Ticket', $daftar);
        $this->assertStringContainsString('Permintaan perbaikan AC Ruang 3', $detail);
    }

    /**
     * /headsection merender view 'e-tiket', jadi ikut ternary yang sama --
     * ini yang membuat partial tidak boleh bergantung pada halaman induk.
     */
    public function testHalamanHeadsectionTerenderUtuh(): void
    {
        $html = $this->halaman('e-tiket', true, 'headsection/abc123');

        $this->assertStringContainsString('Permintaan perbaikan AC Ruang 3', $html);
        // Tautan tetangga harus tetap di segment /headsection, bukan ikut
        // terbawa ke /etiket.
        $this->assertStringContainsString('/headsection/bbb111', $html);
        $this->assertStringNotContainsString('/etiket/bbb111', $html);
    }

    /**
     * Tabel daftar harus terbuka di kedua jenis halaman. Di halaman detail
     * justru di situ navigasi antar tiket dilakukan, jadi menyembunyikannya
     * hanya menambah satu klik sebelum hal yang paling sering dipakai.
     *
     * Pemeriksaan dibatasi pada markup kartu daftar, bukan seluruh dokumen:
     * sidenav milik layout sendiri memakai class Bootstrap 'collapse' untuk
     * submenu APP, jadi scrutinizing string itu di seluruh halaman akan
     * salah tangkap meski daftar sudah benar terbuka.
     */
    public function testDaftarSelaluTerbuka(): void
    {
        foreach ([true, false] as $adaDetail) {
            $html = $this->halaman('e-tiket', $adaDetail);

            $this->assertMatchesRegularExpression(
                '/<div class="card shadow-sm mb-4">\s*<div class="card-header">/',
                $html,
                'Kartu daftar tidak boleh memakai collapse'
            );
            $this->assertStringNotContainsString('daftarTiketCollapse', $html);
            $this->assertStringContainsString('<table class="table table-bordered table-striped datatable', $html);
            $this->assertStringContainsString('Daftar E-Tiket', $html);
        }
    }

    /**
     * Tabel yang tidak pernah tersembunyi tidak butuh pemicu resize
     * manual untuk simple-datatables -- itu hanya perlu ketika tabelnya
     * diukur saat display:none. Script-nya harus hilang, bukan jadi
     * listener yang tidak pernah terpicu.
     */
    public function testDaftarTidakPunyaListenerResize(): void
    {
        $this->assertStringNotContainsString(
            'shown.bs.collapse',
            $this->halaman('e-tiket', true)
        );
    }

    /**
     * Modal Buat Tiket hanya berguna di halaman daftar. Di halaman detail
     * tidak ada tombol yang membukanya, jadi ikut merendernya hanya menambah
     * markup besar tanpa kegunaan.
     */
    public function testModalBuatTiketHanyaDiHalamanDaftar(): void
    {
        $this->assertStringNotContainsString('ModalPilihKategori', $this->halaman('e-tiket', true));
        $this->assertStringContainsString('ModalPilihKategori', $this->halaman('e-tiket', false));
        $this->assertStringContainsString('ModalPilihKategori', $this->halaman('allticket', false, 'allticket'));
    }

    /**
     * Every data-bs-target harus menunjuk ke elemen yang benar-benar ada.
     * Target yang menggantung tidak gagal diam-diam: Bootstrap hanya
     * mengabaikannya, jadi tombolnya terlihat tapi tidak melakukan apa pun.
     */
    public function testSemuaTargetModalPunyaPasangannya(): void
    {
        foreach ([['e-tiket', true, 'etiket/abc123'], ['e-tiket', false, 'etiket']] as [$view, $adaDetail, $path]) {
            $html = $this->halaman($view, $adaDetail, $path);

            preg_match_all('/\bid="([^"]+)"/', $html, $mId);
            $ids = array_unique($mId[1]);

            preg_match_all('/data-bs-target="#([^"]+)"/', $html, $mTarget);

            foreach ($mTarget[1] as $target) {
                $this->assertContains($target, $ids, "Target #{$target} tidak punya elemen di $view");
            }
        }
    }

    /* =====================================================
     | WHITELIST CONTROLLER
     |===================================================== */

    /**
     * Regresi untuk kelas bug yang sama: view membaca field yang
     * controller tidak pernah meneruskan. Kalau salah satu hilang lagi,
     * tes ini gagal lebih awal daripada user menemukan lampirannya hilang
     * di halaman.
     *
     * Dipanggil lewat reflection karena sanitizeTicketDetail() private.
     */
    public function testSanitizeDetailMeneruskanFieldYangDibacaView(): void
    {
        $controller = new \App\Controllers\ETicket2();

        $method = (new \ReflectionClass($controller))->getMethod('sanitizeTicketDetail');
        $method->setAccessible(true);

        $out = $method->invoke($controller, [
            'id' => 7, 'judul' => 'Judul', 'status' => 'dikerjakan',
            'message_lampiran' => 'a.jpg', 'message_nm_jbtn' => 'Unit X',
            'message_id' => 1, 'message_kd_jbtn' => 'J077',
            'message_id_petugas_nama' => 'Andi', 'message_created_at' => '2026-10-01 08:00:00',
            'respon_message_id' => 99, 'respon_message_lampiran' => 'b.png',
            'respon_message_nm_jbtn' => 'Unit Y', 'respon_message_kd_jbtn' => 'J001',
            'respon_message_created_at' => '2026-10-02 09:00:00',
            'unit_penanggung_jawab' => [['kd_jbtn' => 'J001', 'nm_jbtn' => 'Unit Y', 'is_proses' => true]],
        ]);

        foreach ([
            'judul', 'status', 'message_lampiran', 'message_nm_jbtn',
            'message_id', 'message_kd_jbtn', 'message_id_petugas_nama', 'message_created_at', 'respon_message_id',
            'respon_message_lampiran', 'respon_message_nm_jbtn', 'respon_message_kd_jbtn',
            'respon_message_created_at', 'unit_penanggung_jawab',
        ] as $key) {
            $this->assertArrayHasKey($key, $out, "sanitizeTicketDetail() harus meneruskan $key");
        }

        $this->assertTrue($out['unit_penanggung_jawab'][0]['is_proses']);
    }

    /**
     * mapUnitWithJabatan() membangun ulang tiap unit dari nol. Kalau
     * is_proses tidak ikut disalin, penanda unit yang sudah memproses hilang
     * sebelum sampai ke view.
     */
    public function testMapUnitWithJabatanMempertahankanIsProses(): void
    {
        $controller = new \App\Controllers\ETicket2();

        $method = (new \ReflectionClass($controller))->getMethod('mapUnitWithJabatan');
        $method->setAccessible(true);

        // getJabatan() memanggil API lewat $this->client yang bertipe
        // CURLRequest, jadi stub harus subclass-nya dan tanda tangannya
        // harus sama persis dengan parent (termasuk return type).
        $client = new class(
            new \Config\App(),
            new \CodeIgniter\HTTP\Uri('http://example.com'),
            null
        ) extends \CodeIgniter\HTTP\CURLRequest {
            public function get(string $url, array $options = []): \CodeIgniter\HTTP\ResponseInterface
            {
                // Body di-set terpisah: constructor Response menerima
                // config, bukan isi body.
                $response = new \CodeIgniter\HTTP\Response(new \Config\App());
                $response->setBody((string) json_encode(['data' => [
                    ['kd_jbtn' => 'J001', 'nm_jbtn' => 'Unit Sarana'],
                    ['kd_jbtn' => 'J002', 'nm_jbtn' => 'Unit Teknisi'],
                ]]));

                return $response;
            }
        };

        $prop = (new \ReflectionClass($controller))->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($controller, $client);

        $out = $method->invoke($controller, [
            'unit_penanggung_jawab' => [
                ['kd_jbtn' => 'J001', 'is_proses' => true],
                ['kd_jbtn' => 'J002', 'is_proses' => false],
            ],
        ]);

        $this->assertTrue($out['unit_penanggung_jawab'][0]['is_proses'], 'is_proses harus ikut');
        $this->assertFalse($out['unit_penanggung_jawab'][1]['is_proses']);
        $this->assertSame('Unit Sarana', $out['unit_penanggung_jawab'][0]['nm_jbtn']);
    }
}
