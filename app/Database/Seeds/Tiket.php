<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Tiket extends Seeder
{
    public function run()
    {
        /*
        |--------------------------------------------------------------------------
        | e_ticket
        |--------------------------------------------------------------------------
        */

        $tickets = [
            ['id' => 17, 'kode_ticket' => null, 'judul' => '', 'message' => 'No RM: RM-2026-001', 'headsection' => 1, 'kategori_id' => 1, 'petugas_id' => 199004232019022005, 'kd_jbtn' => 'J036', 'valid' => null, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 09:59:34', 'updated_at' => '2026-02-16 09:59:34'],
            ['id' => 18, 'kode_ticket' => null, 'judul' => '', 'message' => 'No RM: RM-2026-002', 'headsection' => 1, 'kategori_id' => 1, 'petugas_id' => 199102102019022006, 'kd_jbtn' => 'J036', 'valid' => null, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 09:59:37', 'updated_at' => '2026-02-16 09:59:37'],
            ['id' => 19, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: Monitor LCD, Detail Kendala: Layar tidak menyala', 'headsection' => 0, 'kategori_id' => 4, 'petugas_id' => 199004232019022005, 'kd_jbtn' => 'J036', 'valid' => 199004232019022005, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 10:00:37', 'updated_at' => '2026-02-16 10:00:37'],
            ['id' => 20, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: Keyboard, Detail Kendala: Beberapa tombol tidak responsif', 'headsection' => 0, 'kategori_id' => 4, 'petugas_id' => 199102102019022006, 'kd_jbtn' => 'J036', 'valid' => 199102102019022006, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 10:00:41', 'updated_at' => '2026-02-16 10:00:41'],
            ['id' => 21, 'kode_ticket' => null, 'judul' => '', 'message' => 'No RM: RM-2026-003, catatan: Pasien baru', 'headsection' => 1, 'kategori_id' => 2, 'petugas_id' => 199004232019022005, 'kd_jbtn' => 'J036', 'valid' => null, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 10:01:50', 'updated_at' => '2026-02-16 10:01:50'],
            ['id' => 22, 'kode_ticket' => null, 'judul' => '', 'message' => 'No RM: RM-2026-004', 'headsection' => 1, 'kategori_id' => 2, 'petugas_id' => 199102102019022006, 'kd_jbtn' => 'J036', 'valid' => null, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 10:01:54', 'updated_at' => '2026-02-16 10:01:54'],
            ['id' => 23, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: CPU Tower, Detail Kendala: Sering restart otomatis', 'headsection' => 1, 'kategori_id' => 3, 'petugas_id' => 199004232019022005, 'kd_jbtn' => 'J036', 'valid' => null, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 10:03:30', 'updated_at' => '2026-02-16 10:03:30'],
            ['id' => 24, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: Scanner Dokumen, Detail Kendala: Driver tidak terdeteksi', 'headsection' => 1, 'kategori_id' => 3, 'petugas_id' => 199102102019022006, 'kd_jbtn' => 'J036', 'valid' => null, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 10:03:34', 'updated_at' => '2026-02-16 10:03:34'],
            ['id' => 25, 'kode_ticket' => null, 'judul' => '', 'message' => 'No RM: RM-2026-005, prioritas: Tinggi', 'headsection' => 1, 'kategori_id' => 1, 'petugas_id' => 199004232019022005, 'kd_jbtn' => 'J036', 'valid' => null, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 10:05:40', 'updated_at' => '2026-02-16 10:05:40'],
            ['id' => 26, 'kode_ticket' => null, 'judul' => '', 'message' => 'No RM: RM-2026-006', 'headsection' => 1, 'kategori_id' => 1, 'petugas_id' => 199102102019022006, 'kd_jbtn' => 'J036', 'valid' => null, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 10:05:44', 'updated_at' => '2026-02-16 10:05:44'],
            ['id' => 27, 'kode_ticket' => null, 'judul' => '', 'message' => 'No RM: RM-2026-007, lokasi: Ruang Administrasi', 'headsection' => 1, 'kategori_id' => 2, 'petugas_id' => 199004232019022005, 'kd_jbtn' => 'J036', 'valid' => null, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 10:06:55', 'updated_at' => '2026-02-16 10:06:55'],
            ['id' => 28, 'kode_ticket' => null, 'judul' => '', 'message' => 'No RM: RM-2026-008', 'headsection' => 1, 'kategori_id' => 2, 'petugas_id' => 199102102019022006, 'kd_jbtn' => 'J036', 'valid' => null, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 10:06:59', 'updated_at' => '2026-02-16 10:06:59'],
            ['id' => 29, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: Mouse Nirkabel, Detail Kendala: Koneksi sering terputus', 'headsection' => 0, 'kategori_id' => 4, 'petugas_id' => 199004232019022005, 'kd_jbtn' => 'J036', 'valid' => 199004232019022005, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 10:07:56', 'updated_at' => '2026-02-16 10:07:56'],
            ['id' => 30, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: Printer HP LaserJet, Detail Kendala: Paper jam', 'headsection' => 0, 'kategori_id' => 4, 'petugas_id' => 199102102019022006, 'kd_jbtn' => 'J036', 'valid' => 199102102019022006, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 10:07:59', 'updated_at' => '2026-02-16 10:07:59'],
            ['id' => 31, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: Headset USB, Detail Kendala: Mikrofon tidak berfungsi', 'headsection' => 0, 'kategori_id' => 4, 'petugas_id' => 199004232019022005, 'kd_jbtn' => 'J036', 'valid' => 199004232019022005, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 17:10:45', 'updated_at' => '2026-02-16 17:10:45'],
            ['id' => 32, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: Web Camera, Detail Kendala: Gambar gelap dan blur', 'headsection' => 0, 'kategori_id' => 4, 'petugas_id' => 199102102019022006, 'kd_jbtn' => 'J036', 'valid' => 199102102019022006, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 17:10:49', 'updated_at' => '2026-02-16 17:10:49'],
            ['id' => 33, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: Monitor Dual Screen, Detail Kendala: Port HDMI 1 tidak berfungsi', 'headsection' => 1, 'kategori_id' => 3, 'petugas_id' => 199004232019022005, 'kd_jbtn' => 'J036', 'valid' => null, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 17:12:38', 'updated_at' => '2026-02-16 17:12:38'],
            ['id' => 34, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: Hard Disk Eksternal, Detail Kendala: Tidak terdeteksi komputer', 'headsection' => 1, 'kategori_id' => 3, 'petugas_id' => 199102102019022006, 'kd_jbtn' => 'J036', 'valid' => null, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 17:12:42', 'updated_at' => '2026-02-16 17:12:42'],
            ['id' => 35, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: Laptop Asus, Detail Kendala: Baterai cepat habis', 'headsection' => 1, 'kategori_id' => 3, 'petugas_id' => 199004232019022005, 'kd_jbtn' => 'J036', 'valid' => null, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 17:14:14', 'updated_at' => '2026-02-16 17:14:14'],
            ['id' => 36, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: Router Wifi, Detail Kendala: Signal tidak stabil', 'headsection' => 1, 'kategori_id' => 3, 'petugas_id' => 199102102019022006, 'kd_jbtn' => 'J036', 'valid' => null, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 17:14:18', 'updated_at' => '2026-02-16 17:14:18'],
            ['id' => 37, 'kode_ticket' => null, 'judul' => '', 'message' => 'No RM: RM-2026-009, status urgent', 'headsection' => 1, 'kategori_id' => 1, 'petugas_id' => 197005091995031002, 'kd_jbtn' => 'J036', 'valid' => 197005091995031002, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 17:17:42', 'updated_at' => '2026-02-16 17:17:42'],
            ['id' => 38, 'kode_ticket' => null, 'judul' => '', 'message' => 'No RM: RM-2026-010, lokasi: IGD', 'headsection' => 1, 'kategori_id' => 2, 'petugas_id' => 197005091995031002, 'kd_jbtn' => 'J036', 'valid' => 197005091995031002, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 17:22:40', 'updated_at' => '2026-02-16 17:22:40'],
            ['id' => 39, 'kode_ticket' => null, 'judul' => '', 'message' => 'No RM: RM-2026-011, follow up data', 'headsection' => 1, 'kategori_id' => 1, 'petugas_id' => 197005091995031002, 'kd_jbtn' => 'J036', 'valid' => 197005091995031002, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 17:23:16', 'updated_at' => '2026-02-16 17:23:16'],
            ['id' => 40, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: Docking Station, Detail Kendala: Port USB tidak berfungsi semua', 'headsection' => 0, 'kategori_id' => 4, 'petugas_id' => 197005091995031002, 'kd_jbtn' => 'J036', 'valid' => 197005091995031002, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 17:25:12', 'updated_at' => '2026-02-16 17:25:12'],
            ['id' => 41, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: Monitor Stand, Detail Kendala: Posisi tidak stabil, mudah goyang', 'headsection' => 1, 'kategori_id' => 3, 'petugas_id' => 197005091995031002, 'kd_jbtn' => 'J036', 'valid' => 197005091995031002, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 17:25:52', 'updated_at' => '2026-02-16 17:25:52'],
            ['id' => 42, 'kode_ticket' => null, 'judul' => '', 'message' => 'Nama Alat: Stabilizer UPS, Detail Kendala: Tidak aktif saat listrik mati', 'headsection' => 1, 'kategori_id' => 3, 'petugas_id' => 197005091995031002, 'kd_jbtn' => 'J036', 'valid' => 197005091995031002, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 17:27:05', 'updated_at' => '2026-02-16 17:27:05'],
            ['id' => 43, 'kode_ticket' => null, 'judul' => '', 'message' => 'No RM: RM-2026-012, catatan penting: data sensitif', 'headsection' => 1, 'kategori_id' => 1, 'petugas_id' => 197005091995031002, 'kd_jbtn' => 'J036', 'valid' => 197005091995031002, 'selesai' => null, 'reject' => null, 'respon_message' => null, 'created_at' => '2026-02-16 17:28:39', 'updated_at' => '2026-02-16 17:28:39'],
        ];

        $this->db->table('e_ticket')->insertBatch($tickets);


        /*
        |--------------------------------------------------------------------------
        | eticket_proses
        |--------------------------------------------------------------------------
        */

        $proses = [
            ['id' => 15, 'id_eticket' => 19, 'kd_jbtn' => 'J002', 'id_petugas' => null, 'catatan' => null, 'created_at' => '2026-02-16 10:00:37', 'updated_at' => '2026-02-16 10:00:37'],
            ['id' => 16, 'id_eticket' => 20, 'kd_jbtn' => 'J002', 'id_petugas' => null, 'catatan' => null, 'created_at' => '2026-02-16 10:00:41', 'updated_at' => '2026-02-16 10:00:41'],
            ['id' => 17, 'id_eticket' => 29, 'kd_jbtn' => 'J002', 'id_petugas' => null, 'catatan' => null, 'created_at' => '2026-02-16 10:07:56', 'updated_at' => '2026-02-16 10:07:56'],
            ['id' => 18, 'id_eticket' => 30, 'kd_jbtn' => 'J002', 'id_petugas' => null, 'catatan' => null, 'created_at' => '2026-02-16 10:07:59', 'updated_at' => '2026-02-16 10:07:59'],
            ['id' => 19, 'id_eticket' => 31, 'kd_jbtn' => 'J002', 'id_petugas' => null, 'catatan' => null, 'created_at' => '2026-02-16 17:10:45', 'updated_at' => '2026-02-16 17:10:45'],
            ['id' => 20, 'id_eticket' => 32, 'kd_jbtn' => 'J002', 'id_petugas' => null, 'catatan' => null, 'created_at' => '2026-02-16 17:10:49', 'updated_at' => '2026-02-16 17:10:49'],
            ['id' => 21, 'id_eticket' => 37, 'kd_jbtn' => 'J013', 'id_petugas' => null, 'catatan' => null, 'created_at' => '2026-02-16 17:17:42', 'updated_at' => '2026-02-16 17:17:42'],
            ['id' => 22, 'id_eticket' => 38, 'kd_jbtn' => 'J014', 'id_petugas' => null, 'catatan' => null, 'created_at' => '2026-02-16 17:22:40', 'updated_at' => '2026-02-16 17:22:40'],
            ['id' => 23, 'id_eticket' => 39, 'kd_jbtn' => 'J013', 'id_petugas' => null, 'catatan' => null, 'created_at' => '2026-02-16 17:23:16', 'updated_at' => '2026-02-16 17:23:16'],
            ['id' => 24, 'id_eticket' => 40, 'kd_jbtn' => 'J002', 'id_petugas' => null, 'catatan' => null, 'created_at' => '2026-02-16 17:25:12', 'updated_at' => '2026-02-16 17:25:12'],
            ['id' => 25, 'id_eticket' => 41, 'kd_jbtn' => 'J002', 'id_petugas' => null, 'catatan' => null, 'created_at' => '2026-02-16 17:25:52', 'updated_at' => '2026-02-16 17:25:52'],
            ['id' => 26, 'id_eticket' => 42, 'kd_jbtn' => 'J002', 'id_petugas' => null, 'catatan' => null, 'created_at' => '2026-02-16 17:27:05', 'updated_at' => '2026-02-16 17:27:05'],
            ['id' => 27, 'id_eticket' => 43, 'kd_jbtn' => 'J013', 'id_petugas' => null, 'catatan' => null, 'created_at' => '2026-02-16 17:28:39', 'updated_at' => '2026-02-16 17:28:39'],
        ];

        $this->db->table('eticket_proses')->insertBatch($proses);
    }
}
