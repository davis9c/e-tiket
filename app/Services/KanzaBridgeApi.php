<?php

namespace App\Services;

use Config\Services;

/**
 * Klien untuk KanzaBridge API V2 (autentikasi API key).
 *
 *
 *
 * V1 memakai `Authorization: Bearer <jwt>` per pengguna, V2 memakai
 * `X-API-Key` yang sama untuk seluruh instalasi. Karena alasan itu kelas
 * ini tidak menyimpan apa pun di session: key dibaca dari .env tiap
 * request, dan hanya ada di memori selama request itu berjalan.
 *
 * Endpoint V1 dan V2 mengembalikan bentuk yang sama persis
 * (`status`/`message`/`data`), jadi tidak ada parser khusus di sini --
 * pemanggil memakai struktur yang sama seperti sebelumnya.
 *
 * Semua panggilan ke bridge -- termasuk login -- sekarang memakai V2
 * lewat kelas ini. V2 tidak menerbitkan token, sehingga respons login
 * tidak lagi menyimpan 'token'/'expires' di session.
 */
class KanzaBridgeApi
{
    /**
     * Pemetaan path V1 -> V2 untuk path yang benar-benar berubah.
     * Sebagian besar endpoint V2 hanya menambah awalan /v2, dan itu sudah
     * ditangani oleh base URL (env api.kanzaBridge). Yang tidak cukup itu
     * hanya tiga di bawah: perubahan huruf besar/kecil dan camelCase ke
     * kebab.
     */
    private const PATH_V2 = [
        'petugas/DanJabatan'    => 'petugas/dan-jabatan',
        'dokter/danSpesialis'   => 'dokter/dan-spesialis',
        'pegawai/dokter'        => 'pegawai/dokter',
    ];

    private ?string $baseUrl;
    private ?string $apiKey;

    public function __construct(?string $baseUrl = null, ?string $apiKey = null)
    {
        // Nama env memakai titik, sama seperti key database.tests.* yang
        // sudah dipakai di phpunit.xml.dist. DotEnv::normaliseVariable()
        // hanya menghapus kutip, jadi titik tetap jadi bagian dari kunci
        // array $_ENV dan env() membacanya seperti biasa.
        $this->baseUrl = $baseUrl ?? env('api.kanzaBridge');
        $this->apiKey  = $apiKey ?? env('api.kanzaBridgeKey');
    }

    /**
     * Key dan base URL sudah lengkap?
     *
     * Penting karena kegagalan di sini tidak selalu kelihatan: pemanggil
     * kebanyakan memakai `?? []` atau `?? ''`, jadi tanpa pengecekan hasilnya
     * halaman kosong tanpa error sama sekali -- semua nm_jbtn berubah jadi
     * '-' dan user mengira itu bug tampilan.
     */
    public function isConfigured(): bool
    {
        return $this->baseUrl !== null
            && $this->baseUrl !== ''
            && $this->apiKey !== null
            && $this->apiKey !== '';
    }

    /**
     * URL penuh untuk sebuah endpoint.
     *
     * $path ditulis relatif terhadap V2 (tanpa /api/v2), misalnya
     * 'jabatan' atau 'pegawai/by-nik'. Path versi V1 tetap diterima dan
     * dipetakan otomatis, supaya pemanggilan yang belum disentuh tidak
     * ikut rusak.
     */
    public function url(string $path): string
    {
        $base = rtrim((string) $this->baseUrl, '/') . '/';

        // Parenthesisasi WAJIB. Operator ?? punya presisi lebih rendah dari
        // titik, jadi tanpa tanda kurung keduanya dihitung sebagai
        // ($base . MAP[$path]) ?? $path -- artinya MAP[$path] diakses lebih
        // dulu dan path yang tidak ada di peta memicu warning, bukan lolos.
        return $base . (self::PATH_V2[$path] ?? $path);
    }

    /**
     * Header untuk request GET.
     */
    public function headers(): array
    {
        return $this->headersWith([]);
    }

    /**
     * Header untuk request POST/PUT, dengan Content-Type.
     */
    public function headersWith(array $extra = []): array
    {
        return array_merge([
            'X-API-Key'    => (string) $this->apiKey,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ], $extra);
    }

    /**
     * GET dengan bentuk respons yang dijanjikan guide (status/message/data).
     *
     * http_errors sengaja false supaya 401/403/429 tidak dilempar sebagai
     * exception. Guide menyatakan 403 itu masalah konfigurasi scope -- bukan
     * request salah -- dan responsnya memuat required_scope, jadi caller
     * butuh bisa membacanya.
     */
    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /**
     * POST. Parameter $asQuery=true dipakai untuk endpoint yang filter-nya
     * lewat query string, seperti petugas/dan-jabatan?jbtn=...
     */
    public function post(string $path, array $payload = [], bool $asQuery = false): array
    {
        return $this->request('POST', $path, $payload, $asQuery);
    }

    /**
     * @return array{status:int, message:string, data:mixed, required_scope?:string}
     */
    private function request(string $method, string $path, array $payload = [], bool $asQuery = false): array
    {
        $gagal = [
            'status'  => 0,
            'message' => 'KanzaBridge belum dikonfigurasi (env api.kanzaBridge / api.kanzaBridgeKey kosong).',
            'data'    => null,
        ];

        if (! $this->isConfigured()) {
            log_message('error', '[KanzaBridge] ' . $gagal['message']);

            return $gagal;
        }

        $url = $this->url($path);
        $opts = ['headers' => $this->headers(), 'timeout' => 10, 'http_errors' => false];

        if ($payload !== []) {
            $asQuery ? $opts['query'] = $payload : $opts['json'] = $payload;
        }

        try {
            $response = Services::curlrequest()->{$method === 'POST' ? 'post' : 'get'}($url, $opts);
        } catch (\Throwable $e) {
            log_message('error', '[KanzaBridge] ' . $method . ' ' . $url . ' -- ' . $e->getMessage());

            return ['status' => 0, 'message' => $e->getMessage(), 'data' => null];
        }

        $status = $response->getStatusCode();
        $result = json_decode($response->getBody(), true);

        // 401 = key salah/dicabut/tidak dikenal. 403 = scope belum dicentang.
        // Keduanya urusan KEY, bukan soal user yang sedang login --
        // jangan logout user karena itu (lihat catatan forceLogout di
        // Admin.php).
        if ($status === 401 || $status === 403) {
            log_message('error', sprintf(
                '[KanzaBridge] %s %s -> HTTP %d (%s). required_scope=%s',
                $method,
                $path,
                $status,
                $result['message'] ?? '-',
                $result['required_scope'] ?? '-'
            ));
        }

        return [
            'status'  => $status,
            'message' => $result['message'] ?? '',
            'data'    => $result['data'] ?? null,
        ];
    }
}
