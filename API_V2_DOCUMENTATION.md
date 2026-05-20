# E-Ticket API V2 Documentation

API endpoints untuk V2 dashboard yang support AJAX (tanpa reload halaman).

## Base URL
```
/api/v2
```

## Authentication
Semua endpoint memerlukan user login (session).

---

## 📊 Dashboard Endpoints

### 1. Get Dashboard Statistics
```
GET /api/v2/dashboard?range=7hari
```

**Query Parameters:**
- `range` (optional): `7hari`, `2minggu`, `1bulan`, `3bulan`, `6bulan`, atau kosong untuk semua

**Response:**
```json
{
  "status": "success",
  "data": {
    "total": 45,
    "belumValid": 10,
    "proses": 20,
    "selesai": 12,
    "reject": 3,
    "kategoriList": [
      {
        "kategori_id": 1,
        "nama_kategori": "Pengajuan",
        "jumlah": 15
      }
    ],
    "chartLabels": ["01 May", "02 May", ...],
    "chartTotal": [5, 8, 3, ...],
    "chartSelesai": [2, 5, 1, ...],
    "chartProses": [3, 3, 2, ...]
  }
}
```

---

## 📋 Ticket List Endpoints

### 2. Get Ticket List
```
GET /api/v2/tickets?status=all
```

**Query Parameters:**
- `status` (optional): `all`, `belum_valid`, `proses`, `selesai`, `reject`

**Response:**
```json
{
  "status": "success",
  "count": 45,
  "data": [
    {
      "id": 123,
      "hashid": "encode123",
      "judul": "Pengajuan Cuti",
      "kategori_id": 1,
      "kd_jbtn": "001",
      "nm_jbtn": "Kepala Unit",
      "valid_nama": "Budi Santoso",
      "selesai_nama": null,
      "reject_nama": null,
      "proses_unit": "002",
      "proses_unit_nama": "Unit Verifikasi",
      "created_at": "2026-05-20 10:30:00"
    }
  ]
}
```

---

## 🔍 Ticket Detail Endpoints

### 3. Get Ticket Detail
```
GET /api/v2/tickets/123
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 123,
    "judul": "Pengajuan Cuti",
    "message": "Permohonan cuti 5 hari",
    "kategori_id": 1,
    "kd_pegawai": "PEG001",
    "kd_jbtn": "001",
    "nm_jbtn": "Kepala Unit",
    "valid_nama": "Budi Santoso",
    "selesai_nama": null,
    "reject_nama": null,
    "proses": [
      {
        "id": 1,
        "id_petugas": "123456",
        "nm_petugas": "Adi",
        "nm_jbtn": "Verifikator",
        "catatan": "Proses verifikasi...",
        "created_at": "2026-05-20 11:00:00"
      }
    ],
    "unit_penanggung_jawab": [
      {
        "kd_jbtn": "002",
        "nm_jbtn": "Unit Verifikasi"
      }
    ],
    "created_at": "2026-05-20 10:30:00"
  }
}
```

### 4. Get Ticket Timeline
```
GET /api/v2/tickets/123/timeline
```

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "type": "created",
      "color": "primary",
      "icon": "fa-solid fa-pencil",
      "text": "Tiket Dibuat 20 May 2026"
    },
    {
      "type": "waiting_approval",
      "color": "warning",
      "icon": "fa-solid fa-clock",
      "text": "Menunggu Persetujuan"
    }
  ]
}
```

---

## 📂 Category & Petugas Endpoints

### 5. Get Categories
```
GET /api/v2/categories
```

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "nama_kategori": "Pengajuan",
      "headsection": 1,
      "unit_penanggung_jawab": [...],
      "unit_pengajuan": [...]
    }
  ]
}
```

### 6. Get Petugas
```
GET /api/v2/petugas?kd_jbtn=001
```

**Query Parameters:**
- `kd_jbtn` (optional): Filter by position code

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "nip": "123456",
      "nama": "Budi Santoso",
      "nm_jbtn": "Kepala Unit"
    }
  ]
}
```

---

## ✏️ Ticket Action Endpoints

### 7. Approve Ticket
```
POST /api/v2/tickets/123/approve
```

**Body:**
```json
{
  "catatan": "Disetujui sesuai prosedur" (optional)
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Ticket berhasil di-approve"
}
```

### 8. Process Ticket (Forward to next unit)
```
POST /api/v2/tickets/123/process
```

**Body:**
```json
{
  "unit_selanjutnya": "002",
  "catatan": "Diteruskan ke unit verifikasi"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Ticket berhasil diproses"
}
```

### 9. Reject Ticket
```
POST /api/v2/tickets/123/reject
```

**Body:**
```json
{
  "catatan": "Data tidak lengkap, mohon diperbaiki"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Ticket berhasil ditolak"
}
```

### 10. Submit New Ticket
```
POST /api/v2/tickets/submit
```

**Body:**
```json
{
  "kategori_id": 1,
  "petugas_id": 123,
  "petugas_id_nama": "Budi Santoso",
  "judul": "Pengajuan Cuti",
  "message": "Permohonan cuti 5 hari"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "E-Ticket berhasil dibuat",
  "data": {
    "id": 124,
    "hashid": "encode124"
  }
}
```

---

## ❌ Error Responses

### 400 Bad Request
```json
{
  "status": "error",
  "message": "Validasi gagal",
  "errors": {
    "kategori_id": "kategori_id field is required"
  }
}
```

### 404 Not Found
```json
{
  "status": "error",
  "message": "Ticket tidak ditemukan"
}
```

### 405 Method Not Allowed
```json
{
  "status": "error",
  "message": "Method not allowed"
}
```

### 500 Internal Server Error
```json
{
  "status": "error",
  "message": "Error message here"
}
```

---

## 🎯 Usage Example (JavaScript)

### Fetch Dashboard Data
```javascript
fetch('/api/v2/dashboard?range=7hari')
  .then(res => res.json())
  .then(data => {
    console.log('Total tickets:', data.data.total);
    console.log('Statistics:', data.data);
    // Update UI dengan data
  })
  .catch(err => console.error('Error:', err));
```

### Approve a Ticket
```javascript
fetch('/api/v2/tickets/123/approve', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    catatan: 'Disetujui'
  })
})
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      alert('Ticket berhasil di-approve');
      // Refresh UI
    }
  })
  .catch(err => console.error('Error:', err));
```

---

## 📝 Notes
- Semua request otomatis mengecek session/login
- Response selalu dalam format JSON
- Semua error ditangani dengan error handling yang proper
- Database transaction digunakan untuk action-based endpoints
