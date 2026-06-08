<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Notifikasi extends Controller
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

            //dd(session()->get('headsection'));
            if (!empty(session()->get('headsection'))) {
                $idPegawai = session()->get('id_pegawai');

            $builder = $this->db->table('notifikasi');
            $data = $builder
                ->where('valid', 0)
                ->where('kd_jbtn', session()->get('kd_jabatan'))
                ->orderBy('created_at', 'DESC')
                ->get()
                ->getResult();

            // Hapus data di database berdasarkan data yang sudah di dapat
            if (!empty($data)) {
                $ids = array_map(fn($item) => $item->id, $data);
                $this->db->table('notifikasi')->whereIn('id', $ids)->delete();
            }

            return $this->response->setJSON($data);
            } else {
                $idPegawai = session()->get('id_pegawai');

                $builder = $this->db->table('notifikasi');
                $data = $builder
                    ->where('valid', 1)
                    ->groupStart() // buka kurung
                    ->where('kd_jbtn', session()->get('kd_jabatan'))
                    ->orWhere('id_pegawai', session()->get('id_pegawai'))
                    ->groupEnd() // tutup kurung
                    ->orderBy('created_at', 'DESC')
                    ->get()
                    ->getResult();

                // Hapus data di database berdasarkan data yang sudah di dapat
                if (!empty($data)) {
                    $ids = array_map(fn($item) => $item->id, $data);
                    $this->db->table('notifikasi')->whereIn('id', $ids)->delete();
                }

                return $this->response->setJSON($data);
            }
        } catch (\Throwable $e) {

            return $this->response->setJSON([
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ])->setStatusCode(500);
        }
    }

    // ➕ Tambah notifikasi
    public function create()
    {
        $data = [
            'id_pegawai' => $this->request->getPost('id_pegawai'),
            'id_eticket' => $this->request->getPost('id_eticket'),
            'pesan'      => $this->request->getPost('pesan'),
            'tipe'       => $this->request->getPost('tipe'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->table('notifikasi')->insert($data);

        return $this->response->setJSON([
            'status' => 'success'
        ]);
    }

    // 🗑️ (opsional) hapus notif
    public function delete($id)
    {
        $this->db->table('notifikasi')->delete(['id' => $id]);

        return $this->response->setJSON([
            'status' => 'deleted'
        ]);
    }
}
