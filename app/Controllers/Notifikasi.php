<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Notifikasi extends BaseController
{
    protected $db;

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
