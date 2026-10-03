<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Menguji ETicketModel::getTickets() langsung, tanpa lewat HTTP.
 *
 * Fokus: apakah tiap scope benar-benar menyaring seperti seharusnya,
 * apakah gabungannya (default /etiket) benar-benar union, dan apakah
 * query ke tabel unit sudah di-batch (bukan N+1).
 *
 * Semua penulisan dibungkus transaksi yang di-rollback di tearDown.
 */
final class TicketScopeTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $refresh = false;
    protected $migrate = false;
    protected $db;
    private bool $inTransaction = false;

    private const NIP_LOGIN = '199004232019022005';
    private const NIP_LAIN  = '198001012010011234';

    private ?string $kdJbtn = null;
    private ?string $kdJbtnLain = null;
    private ?int $kategoriMilikLogin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = \Config\Database::connect();
        $this->inTransaction = $this->db->transBegin();
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            $this->db->transRollback();
        }

        parent::tearDown();
    }

    /* =====================================================
     | SEED
     | ===================================================== */

    /**
     * Dua unit: unit milik user login, dan unit lain.
     *
     * Dipakai untuk membedakan scope 'pelaksana' (unit saya sebagai UPJ)
     * dari scope 'headsection' (unit saya sebagai pengaju).
     */
    private function seedDuaUnit(): void
    {
        $this->kdJbtn     = (string) env('ROLE_ADMIN');
        $this->kdJbtnLain = 'J003';

        $kode = 'ZSC' . random_int(1000, 9999);

        $this->db->table('tb_e_ticket_kategori_eticket')->insert([
            'kode_kategori' => $kode,
            'nama_kategori' => 'Kategori Uji Scope',
            'deskripsi'     => '',
            'template'      => '',
            'aktif'         => 1,
            'headsection'   => 0,
            'teruskan'      => 0,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->kategoriMilikLogin = (int) $this->db->insertID();

        // Unit milik user login = unit pengaju + penanggung jawab.
        $this->db->table('tb_e_ticket_kategori_unit_jabatan')->insert([
            'kategori_id'         => $this->kategoriMilikLogin,
            'kd_jbtn'             => $this->kdJbtn,
            'is_penanggung_jawab' => 1,
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        $this->db->table('tb_e_ticket_kategori_unit_jabatan')->insert([
            'kategori_id'         => $this->kategoriMilikLogin,
            'kd_jbtn'             => $this->kdJbtnLain,
            'is_penanggung_jawab' => 1,
            'created_at'          => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param string $pengajuId NIP pengaju
     * @param string|null $upjKd unit yang ditugaskan, null = tidak ada UPJ
     */
    private function seedTiket(
        string $pengajuId,
        string $pengajuKdJbtn,
        ?string $upjKd = null,
        bool $sudahValid = true,
        bool $sudahSelesai = false
    ): int {
        $now = date('Y-m-d H:i:s');

        $this->db->table('tb_e_ticket')->insert([
            'judul'           => 'Tiket ' . $pengajuId . ' upj=' . ($upjKd ?? '-'),
            'message_awal'    => 'Pesan uji',
            'kategori_id'     => $this->kategoriMilikLogin,
            'kd_pegawai'      => $pengajuId,
            'petugas_id'      => $pengajuId,
            'petugas_id_nama' => $pengajuId,
            'kd_jbtn'         => $pengajuKdJbtn,
            'headsection'     => 0,
            'valid_nama'      => $sudahValid ? 'Atasan' : null,
            'message_akhir'   => $sudahSelesai ? 'Selesai oleh unit' : null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $id = (int) $this->db->insertID();

        if ($upjKd !== null) {
            $this->db->table('tb_e_ticket_upj')->insert([
                'etiket_id' => $id,
                'kd_jbtn'   => $upjKd,
            ]);
        }

        return $id;
    }

    private function model(): \App\Models\ETicketModel
    {
        return new \App\Models\ETicketModel();
    }

    /**
     * @param string[] $scope
     */
    private function ids(
        array $scope,
        ?string $kd = null,
        ?string $nip = self::NIP_LOGIN,
        ?int $valid = null,
        ?int $selesai = null
    ): array {
        $kd = $kd ?? $this->kdJbtn;

        $rows = $this->model()->getTickets($scope, $kd, $nip, $valid, $selesai, $this->kategoriMilikLogin);

        return array_map(static fn ($r) => (int) $r['id'], $rows);
    }

    /* =====================================================
     | SCOPE 'saya'
     | ===================================================== */

    public function testSayaReturnsOnlyOwnTicketsRegardlessOfValid(): void
    {
        $this->seedDuaUnit();

        $milikSayaBelumValid = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, $this->kdJbtn, false);
        $milikSayaValid      = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, $this->kdJbtn, true);
        $milikOrangLain      = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, $this->kdJbtn, true);

        $ids = $this->ids(['saya']);

        sort($ids);

        $harus = [$milikSayaBelumValid, $milikSayaValid];
        sort($harus);

        // Tiket orang lain tidak boleh muncul.
        $this->assertSame($harus, $ids);
        $this->assertNotContains($milikOrangLain, $ids);
    }

    public function testSayaNeedsNipOtherwiseReturnsNothing(): void
    {
        $this->seedDuaUnit();

        $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, $this->kdJbtn, true);

        // Tanpa NIP, filter dilewati -> risks seluruh tabel terekspos.
        // Karena itu model harus mengembalikan nol baris, bukan semuanya.
        $rows = $this->model()->getTickets(['saya'], $this->kdJbtn, null, null, null, $this->kategoriMilikLogin);

        $this->assertSame([], $rows, 'Scope saya tanpa NIP harus kosong');
    }

    /* =====================================================
     | SCOPE 'pelaksana'
     | ===================================================== */

    public function testPelaksanaReturnsOnlyTicketsAssignedToMyUnit(): void
    {
        $this->seedDuaUnit();

        $untukSaya      = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, $this->kdJbtn, true);
        $untukUnitLain  = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, $this->kdJbtnLain, true);
        $tanpaUpj       = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true);

        $ids = $this->ids(['pelaksana']);

        $this->assertContains($untukSaya, $ids, 'Tiket yang ditugaskan ke unit saya harus muncul');
        $this->assertNotContains($untukUnitLain, $ids, 'Tiket untuk unit lain tidak boleh muncul');
        $this->assertNotContains($tanpaUpj, $ids, 'Tiket tanpa UPJ tidak boleh masuk daftar tugas');
    }

    public function testPelaksanaExcludesUnvalidatedTickets(): void
    {
        $this->seedDuaUnit();

        $belumValid = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, $this->kdJbtn, false);
        $sudahValid = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, $this->kdJbtn, true);

        $ids = $this->ids(['pelaksana']);

        $this->assertContains($sudahValid, $ids);
        $this->assertNotContains(
            $belumValid,
            $ids,
            'Scope pelaksana mensyaratkan valid_nama IS NOT NULL'
        );
    }

    public function testPelaksanaNeedsKdJbtnOtherwiseReturnsNothing(): void
    {
        $this->seedDuaUnit();

        $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, $this->kdJbtn, true);

        // Tanpa kd_jabatan filter dilewati -> harus nol baris.
        $rows = $this->model()->getTickets(['pelaksana'], null, self::NIP_LOGIN, null, null, $this->kategoriMilikLogin);

        $this->assertSame([], $rows, 'Scope pelaksana tanpa kd_jabatan harus kosong');
    }

    /* =====================================================
     | SCOPE 'headsection'
     |===================================================== */

    public function testHeadsectionReturnsTicketsRequestedByMyUnitNotMine(): void
    {
        $this->seedDuaUnit();

        $diajukanRekan = $this->seedTiket(self::NIP_LAIN, $this->kdJbtn, null);
        $milikSendiri  = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null);
        $unitLain      = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, null);

        $ids = $this->ids(['headsection']);

        $this->assertContains(
            $diajukanRekan,
            $ids,
            'Tiket yang diajukan unit saya harus muncul'
        );
        $this->assertNotContains(
            $milikSendiri,
            $ids,
            'Headsection tidak boleh menyetujui tiket miliknya sendiri'
        );
        $this->assertNotContains(
            $unitLain,
            $ids,
            'Tiket yang diajukan unit lain tidak boleh muncul'
        );
    }

    public function testHeadsectionIncludesTicketsWithNullPetugas(): void
    {
        $this->seedDuaUnit();

        $nullPetugas = $this->seedTiket('0', $this->kdJbtn, null);

        // Tiket tanpa petugas_id tetap masuk daftar: tidak jelas milik siapa,
        // jadi lebih baik terlihat daripada hilang.
        $this->db->table('tb_e_ticket')
            ->where('id', $nullPetugas)
            ->update(['petugas_id' => null]);

        $this->assertContains(
            $nullPetugas,
            $this->ids(['headsection']),
            'petugas_id NULL harus lolos (kondisi NULL-safe)'
        );
    }

    public function testHeadsectionIgnoresValidFilterByDefault(): void
    {
        $this->seedDuaUnit();

        $belumValid = $this->seedTiket(self::NIP_LAIN, $this->kdJbtn, null, false);
        $sudahValid = $this->seedTiket(self::NIP_LAIN, $this->kdJbtn, null, true);

        $ids = $this->ids(['headsection']);

        $this->assertContains($belumValid, $ids);
        $this->assertContains($sudahValid, $ids);
    }

    /* =====================================================
     | GABUNGAN
     | ===================================================== */

    public function testDefaultIsUnionOfAllThreeScopes(): void
    {
        $this->seedDuaUnit();

        $saya      = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, false);
        $pelaksana = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, $this->kdJbtn, true);
        $headsect  = $this->seedTiket(self::NIP_LAIN, $this->kdJbtn, null, true);

        $default = $this->ids(\App\Models\ETicketModel::SUMBER_DEFAULT);

        $this->assertContains($saya, $default);
        $this->assertContains($pelaksana, $default);
        $this->assertContains($headsect, $default);
    }

    public function testDefaultHasNoDuplicateRows(): void
    {
        $this->seedDuaUnit();

        // Tiket ini memenuhi syarat 'saya' DAN 'pelaksana' sekaligus:
        // dibuat oleh user login, dan unit login ada di UPJ-nya.
        $ganda = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, $this->kdJbtn, true);

        $ids = $this->ids(\App\Models\ETicketModel::SUMBER_DEFAULT);

        $this->assertSame(
            1,
            count(array_keys($ids, $ganda, true)),
            'Tiket yang cocok di dua scope tidak boleh terduplikasi'
        );
    }

    public function testPerluValidasiScopeCombinesSayaAndHeadsection(): void
    {
        $this->seedDuaUnit();

        $milikSaya   = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, false);
        $diajukanUnit = $this->seedTiket(self::NIP_LAIN, $this->kdJbtn, null, false);
        $sudahValid  = $this->seedTiket(self::NIP_LAIN, $this->kdJbtn, null, true);

        // Perlu Validasi = valid 0 (valid_nama IS NULL)
        $ids = $this->ids(['saya', 'headsection'], null, self::NIP_LOGIN, 0);

        $this->assertContains($milikSaya, $ids);
        $this->assertContains($diajukanUnit, $ids);
        $this->assertNotContains(
            $sudahValid,
            $ids,
            'Perlu Validasi hanya memuat tiket yang valid_nama-nya kosong'
        );
    }

    public function testEmptyScopeReturnsNothing(): void
    {
        $this->seedDuaUnit();

        $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, $this->kdJbtn, true);

        // Scope kosong harus nol baris (bukan "semua tiket").
        // Kalau tidak,Nfmn бош kosong berarti filter diabaikan.
        $this->assertSame([], $this->ids([]));
    }

    /* =====================================================
     | SCOPE 'all' (admin)
     | ===================================================== */

    public function testAllScopeReturnsEveryTicketRegardlessOfUnit(): void
    {
        $this->seedDuaUnit();

        $a = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null);
        $b = $this->seedTiket(self::NIP_LAIN, $this->kdJbtnLain, null);

        $rows = $this->model()->getTickets(['all'], null, null, null, null, $this->kategoriMilikLogin);

        $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
        sort($ids);

        $harus = [$a, $b];
        sort($harus);

        $this->assertSame($harus, $ids);
    }

    /* =====================================================
     | FILTER TAMBAHAN
     | ===================================================== */

    public function testValidAndSelesaiFilters(): void
    {
        $this->seedDuaUnit();

        $belumValid  = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, false);
        $validAktif  = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true, false);
        $validSelesai = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, true, true);

        $m = $this->model();

        $ids = static fn (array $rows) => array_map(static fn ($r) => (int) $r['id'], $rows);

        // ?valid=0 -> valid_nama IS NULL
        $this->assertSame(
            [$belumValid],
            $ids($m->getTickets(['saya'], $this->kdJbtn, self::NIP_LOGIN, 0, null, $this->kategoriMilikLogin))
        );

        // ?selesai=0 -> message_akhir IS NULL
        $this->assertSame(
            [$belumValid, $validAktif],
            $ids($m->getTickets(['saya'], $this->kdJbtn, self::NIP_LOGIN, null, 0, $this->kategoriMilikLogin))
        );

        // ?selesai=1 -> message_akhir IS NOT NULL
        $this->assertSame(
            [$validSelesai],
            $ids($m->getTickets(['saya'], $this->kdJbtn, self::NIP_LOGIN, null, 1, $this->kategoriMilikLogin))
        );
    }

    public function testStatusFilterIsComputedInPhp(): void
    {
        $this->seedDuaUnit();

        // belum_valid: valid_nama kosong
        $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, false);
        // belum disetujui tapi sudah ditolak
        $ditolak = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null, false);
        $this->db->table('tb_e_ticket')->where('id', $ditolak)->update(['reject_nama' => 'Atasan']);

        $rows = $this->model()->getTickets(['saya'], $this->kdJbtn, self::NIP_LOGIN, null, null, $this->kategoriMilikLogin);

        $status = [];
        foreach ($rows as $r) {
            $status[(int) $r['id']] = $r['status'];
        }

        $this->assertSame('belum_valid', $status[$ditolak - 1] ?? $status[$ditolak] ?? null, 'sanity: ada status belum_valid');
        $this->assertSame('reject', $status[$ditolak], 'reject_nama harus menang dari valid_nama kosong');
    }

    public function testOrderIsNewestFirst(): void
    {
        $this->seedDuaUnit();

        $lama = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null);
        $this->db->table('tb_e_ticket')->where('id', $lama)->update([
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        ]);

        $baru = $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null);

        $this->assertSame(
            [$baru, $lama],
            $this->ids(['saya']),
            'Urutan harus created_at DESC'
        );
    }

    /**
     * Menghitung query ke tb_e_ticket_kategori_unit_jabatan yang
     * dijalankan getTickets().
     *
     * $kategoriId null = tanpa filter kategori, jadi semua kategori
     * yang muncul di hasil query ikut diproses. Inilah kondisi tempat
     * N+1 lama muncul.
     *
     * MySQLi tidak punya listen()/getEventManager(), jadi yang dipakai
     * event global 'DBQuery'. Tabel yang dipilih adalah sumber N+1 yang
     * dulu ada di attachProsesToRows().
     */
    private function countUnitQueriesFor(?int $kategoriId = null): int
    {
        $jumlah = 0;

        \CodeIgniter\Events\Events::on('DBQuery', static function ($query) use (&$jumlah): void {
            if (str_contains((string) $query, 'tb_e_ticket_kategori_unit_jabatan')) {
                $jumlah++;
            }
        });

        // Model baru setiap pemanggilan: cache unit ada di dalam instance,
        // jadi dua pengukuran tidak saling memengaruhi.
        (new \App\Models\ETicketModel())->getTickets(
            ['saya'],
            $this->kdJbtn,
            self::NIP_LOGIN,
            null,
            null,
            $kategoriId
        );

        return $jumlah;
    }

    /**
     * Menahan diri dari N+1.
     *
     * Sebelum refactor, attachProsesToRows() memanggil
     * getUnitByKategori() di dalam loop -- satu query per baris tiket.
     * Sekarang primeUnitCache() mengambilnya sekaligus dalam 1 query.
     *
     * Yang diuji: 5 tiket dari 5 kategori berbeda harus tetap memakai
     * 1 query ke tabel unit, bukan 5.
     */
    public function testUnitQueryIsBatchedAcrossCategories(): void
    {
        $this->seedDuaUnit();

        // Tiket di kategori milik login (kategori pertama).
        $this->seedTiket(self::NIP_LOGIN, $this->kdJbtn, null);

        // 4 kategori tambahan, masing-masing dengan 1 tiket.
        for ($i = 0; $i < 4; $i++) {
            $this->db->table('tb_e_ticket_kategori_eticket')->insert([
                'kode_kategori' => 'ZN' . random_int(10000, 99999),
                'nama_kategori' => 'Kategori Uji N+1',
                'deskripsi'     => '',
                'template'      => '',
                'aktif'         => 1,
                'headsection'   => 0,
                'teruskan'      => 0,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            $katId = (int) $this->db->insertID();

            $this->db->table('tb_e_ticket_kategori_unit_jabatan')->insert([
                'kategori_id'         => $katId,
                'kd_jbtn'             => $this->kdJbtn,
                'is_penanggung_jawab' => 1,
                'created_at'          => date('Y-m-d H:i:s'),
            ]);

            $this->db->table('tb_e_ticket')->insert([
                'judul'           => 'Uji N+1 #' . $i,
                'message_awal'    => 'x',
                'kategori_id'     => $katId,
                'kd_pegawai'      => self::NIP_LOGIN,
                'petugas_id'      => self::NIP_LOGIN,
                'petugas_id_nama' => 'Uji',
                'kd_jbtn'         => $this->kdJbtn,
                'headsection'     => 0,
                'valid_nama'      => 'Atasan',
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        }

        $satu = $this->countUnitQueriesFor($this->kategoriMilikLogin);
        $lima = $this->countUnitQueriesFor(null);

        $this->assertSame(
            1,
            $satu,
            'Satu kategori seharusnya cukup 1 query unit'
        );

        $this->assertSame(
            $satu,
            $lima,
            sprintf(
                'Query unit harus tetap 1 walau hasil berisi 5 kategori. '
                . 'Ternyata %d vs %d -- berarti masih N+1.',
                $satu,
                $lima
            )
        );
    }
}
