<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Notifikasi extends BaseController
{
    protected $db;

    /**
     * Batas baris histori notifikasi per halaman.
     *
     * Tidak ada paging di halaman ini: notifikasi dibaca sekali untuk isi
     * tabel dan badge sidenav. Kalau someday tabelnya perlu jauh lebih besar
     * lagi, angka ini harus naik bersama paging-nya -- bukan sendirian.
     *
     * Badge sidenav TIDAK memakai angka ini sebagai sumber: badge dihitung
     * dari kolom read_at di layout, bukan dari sini, supaya membuka halaman
     * tidak ikut menambah query dashboard.
     */
    private const BATAS_HISTORI = 100;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // 🔔 Ambil notifikasi
    public function index()
    {
        try {
            $userData = $this->userData;
            $data = $this->db->table('tb_e_ticket_notifikasi')
                ->where('id_pegawai', $userData['id_pegawai'])
                ->where('read_at IS NULL', null, false)
                ->orderBy('created_at', 'DESC')
                ->get()
                ->getResult();

            return $this->response->setJSON($data);
        } catch (\Throwable $e) {
            log_message('error', 'Notification fetch failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'message' => 'Gagal mengambil notifikasi.',
            ])->setStatusCode(500);
        }
    }

    public function read()
    {
        $userData = $this->userData;
        $ids = $this->request->getJSON(true)['ids'] ?? [];
        $ids = array_values(array_filter(array_map('intval', (array) $ids)));

        if (!empty($ids)) {
            $this->db->table('tb_e_ticket_notifikasi')
                ->whereIn('id', $ids)
                ->where('id_pegawai', $userData['id_pegawai'])
                ->where('read_at IS NULL', null, false)
                ->update(['read_at' => date('Y-m-d H:i:s')]);
        }

        return $this->response->setJSON(['status' => 'read']);
    }

    /**
     * Tandai semua notifikasi milik user ini sebagai terbaca.
     *
     * Debtornya halaman /notifikasi: dulu tombol "tandai terbaca" ada di
     * lonceng di navbar, dan sekarang tidak ada ikon itu lagi, jadi satu-
     * klik ini hilang bersamanya. Halaman histori butuh jalan yang tidak
     * bergantung pada satu ikon.
     *
     * WHERE-nya tetap 'read_at IS NULL' supaya tidak menulis ulang baris yang
     * sudah terbaca -- kalau tidak, read_at jadi waktu klik terakhir, bukan
     * waktu benar-benar dibaca, dan urutan "kapan dibaca" jadi tidak berguna.
     */
    public function readAll()
    {
        $userData = $this->userData;

        $this->db->table('tb_e_ticket_notifikasi')
            ->where('id_pegawai', $userData['id_pegawai'])
            ->where('read_at IS NULL', null, false)
            ->update(['read_at' => date('Y-m-d H:i:s')]);

        return redirect()->to(base_url('notifikasi'));
    }

    /**
     * Histori notifikasi milik user yang sedang login.
     *
     * Sengaja TIDAK memakai filter 'read_at IS NULL' seperti index(): halaman
     * ini soal notifikasi yang sudah lewat, jadi membacanya justru akan
     * membuat daftar kosong setiap kali user membukanya.
     *
     * Batas 100 baris: notifikasi tidak pernah dihapus dari tabel, jadi tanpa
     * batas query ini akan tumbuh terus di sisi server. Badge di sidenav
     * memakai hitungan yang sama supaya angka di menu dan isi halaman tidak
     * berbeda.
     */
    public function histori()
    {
        $userData = $this->userData;

        $notif = $this->db->table('tb_e_ticket_notifikasi')
            ->where('id_pegawai', $userData['id_pegawai'])
            ->orderBy('created_at', 'DESC')
            ->limit(self::BATAS_HISTORI)
            ->get()
            ->getResultArray();

        // Dipisah supaya view tidak perlu menghitung ulang, dan supaya
        // "baru" didefinisikan sekali di satu tempat.
        $belumTerbaca = 0;

        foreach ($notif as $i => $baris) {
            $baru = empty($baris['read_at']);
            $notif[$i]['adalah_baru'] = $baru;

            if ($baru) {
                $belumTerbaca++;
            }
        }

        return view('notifikasi/histori', [
            'title'        => 'Histori Notifikasi',
            'notif'        => $notif,
            'jumlah'       => count($notif),
            'belumTerbaca' => $belumTerbaca,
        ]);
    }

    // ➕ Tambah notifikasi
    public function create()
    {
        $data = [
            'id_pegawai' => $this->request->getPost('id_pegawai'),
            'id_eticket' => $this->request->getPost('id_eticket'),
            'valid'      => $this->request->getPost('valid') ?? 1,
            'kd_jbtn'    => $this->request->getPost('kd_jbtn'),
            'pesan'      => $this->request->getPost('pesan'),
            'tipe'       => $this->request->getPost('tipe'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->table('tb_e_ticket_notifikasi')->insert($data);

        return $this->response->setJSON([
            'status' => 'success'
        ]);
    }

    // 🗑️ (opsional) hapus notif
    public function delete($id)
    {
        $this->db->table('tb_e_ticket_notifikasi')
            ->where('id', $id)
            ->where('id_pegawai', $this->userData['id_pegawai'])
            ->delete();

        return $this->response->setJSON([
            'status' => 'deleted'
        ]);
    }
}
