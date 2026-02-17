# 📤 Panduan Upload ke GitHub

Panduan lengkap untuk mengupload project E-Tiket ke GitHub.

## ✅ Prasyarat

Sebelum memulai, pastikan Anda telah memiliki:

1. **Akun GitHub** - https://github.com
2. **Git terinstall** - https://git-scm.com/downloads
3. **SSH key atau Personal Access Token** (untuk autentikasi)
4. **Repository kosong di GitHub** (dapat dibuat melalui web GitHub)

## 🔑 Setup Git Configuration (Lakukan sekali)

Jika ini pertama kalinya Anda menggunakan Git, setup konfigurasi global:

```bash
git config --global user.name "Nama Anda"
git config --global user.email "email@gmail.com"
```

Untuk mengecek konfigurasi:

```bash
git config --global --list
```

## 🚀 Langkah-Langkah Upload ke GitHub

### Langkah 1: Inisialisasi Repository Lokal

Jika belum ada repository lokal di project Anda:

```bash
cd d:\AR ROZY\e-tiket
git init
```

### Langkah 2: Tambahkan Remote Repository

Ganti `username` dan `repository-name` dengan data Anda:

```bash
git remote add origin https://github.com/username/e-tiket.git
```

Untuk mengecek remote yang sudah ditambahkan:

```bash
git remote -v
```

### Langkah 3: Tambahkan Files ke Staging

Tambahkan semua files (akan mengikuti `.gitignore`):

```bash
git add .
```

Atau tambahkan file spesifik:

```bash
git add "app/Controllers/Dashboard.php"
```

Untuk mengecek status:

```bash
git status
```

### Langkah 4: Commit

Buat commit dengan pesan yang deskriptif:

```bash
git commit -m "Initial commit: Setup E-Tiket system dengan CodeIgniter 4"
```

Contoh commit messages yang baik:

```bash
git commit -m "feat: Add e-tiket dashboard feature"
git commit -m "fix: Fix database connection issue"
git commit -m "docs: Update README installation guide"
git commit -m "style: Format code according to PSR-12"
git commit -m "refactor: Restructure Models folder"
```

### Langkah 5: Push ke GitHub

Push branch main/master ke GitHub:

```bash
# Untuk branch utama (main atau master)
git push -u origin main
```

Atau jika menggunakan master:

```bash
git push -u origin master
```

Jika Anda diminta login, gunakan salah satu metode:

#### Menggunakan HTTPS (Personal Access Token)

1. Buka https://github.com/settings/tokens
2. Click "Generate new token"
3. Beri nama token (misal: "e-tiket-upload")
4. Pilih scopes: `repo`
5. Click "Generate"
6. Copy token yang dihasilkan
7. Saat diminta password, paste token tersebut

#### Menggunakan SSH (Lebih aman & praktis)

1. Generate SSH key (jika belum ada):

```bash
ssh-keygen -t rsa -b 4096 -C "email@gmail.com"
```

Tekan Enter untuk setiap pertanyaan (gunakan default path).

2. Tambahkan SSH key ke GitHub:

```bash
# Copy SSH key ke clipboard (Windows)
type %USERPROFILE%\.ssh\id_rsa.pub | clip

# Atau untuk Linux/Mac
cat ~/.ssh/id_rsa.pub | pbcopy
```

3. Di GitHub, masuk ke Settings > SSH and GPG keys > New SSH key
4. Paste public key Anda
5. Gunakan SSH URL saat menambah remote:

```bash
git remote set-url origin git@github.com:username/e-tiket.git
```

## 📝 Commit dan Push Berkala

Setelah initial upload, Anda akan melakukan push berkala:

```bash
# Membuat perubahan
# ... edit files ...

# Cek status
git status

# Stage changes
git add .

# Commit
git commit -m "Deskripsi perubahan yang Anda buat"

# Push ke GitHub
git push origin main
```

## 🌿 Bekerja dengan Branches

Untuk fitur baru, sebaiknya gunakan branch terpisah:

```bash
# Buat branch baru
git checkout -b feature/nama-fitur

# Atau
git switch -c feature/nama-fitur

# Lakukan perubahan
# ... edit files ...

# Commit
git add .
git commit -m "Add feature: nama-fitur"

# Push branch
git push origin feature/nama-fitur

# Di GitHub, buat Pull Request dan merge setelah review
```

## 🔄 Pull Changes dari GitHub

Jika ada perubahan di GitHub (dari tim lain atau pull request yang di-merge):

```bash
# Update repository lokal
git pull origin main
```

## 📊 Melihat History

### Log Commits

```bash
# Lihat history commits (shorthand)
git log --oneline

# Lihat history dengan detail
git log --stat

# Lihat history visual
git log --graph --oneline --all
```

### Perbedaan (Diff)

```bash
# Lihat perubahan yang belum di-stage
git diff

# Lihat perubahan yang sudah di-stage
git diff --staged

# Bandingkan 2 branches
git diff main feature/nama-fitur
```

## 🚫 Membatalkan Perubahan

```bash
# Batalkan perubahan di file tertentu (belum commit)
git checkout "file-path"

# Batalkan semua perubahan (belum commit)
git checkout -- .

# Batalkan staging (unstage)
git reset HEAD "file-path"

# Rollback commit terakhir (keep changes)
git reset --soft HEAD~1

# Rollback commit terakhir (hapus changes)
git reset --hard HEAD~1
```

## ⚠️ Troubleshooting

### Error: "fatal: not a git repository"

```bash
# Pastikan sudah berada di folder project
cd d:\AR ROZY\e-tiket

# Inisialisasi git jika belum
git init
```

### Error: "fatal: 'origin' does not appear to be a 'git' repository"

```bash
# Cek remote yang ada
git remote -v

# Tambahkan remote jika belum
git remote add origin https://github.com/username/e-tiket.git
```

### Error: "Please make sure you have the correct access rights"

```bash
# Jika menggunakan SSH
# Pastikan SSH key sudah ditambahkan ke ssh-agent

# Windows (Git Bash)
eval $(ssh-agent -s)
ssh-add ~/.ssh/id_rsa

# Linux/Mac
ssh-add ~/.ssh/id_rsa

# Test koneksi
ssh -T git@github.com
```

### Error: "401 Unauthorized" atau "403 Forbidden"

- Cek username dan password/token yang Anda gunakan
- Pastikan Personal Access Token masih valid dan belum expired
- Pastikan token memiliki permission `repo`

## 📚 Referensi Tambahan

- [GitHub Docs](https://docs.github.com)
- [Git Official Documentation](https://git-scm.com/doc)
- [Git Cheat Sheet](https://github.github.com/training-kit/downloads/github-git-cheat-sheet.pdf)

## 💡 Tips & Best Practices

1. **Commit sering dengan pesan yang jelas** - Jangan tunggu sampai banyak file berubah
2. **Pull sebelum push** - Hindari conflict dengan selalu pull terlebih dahulu
3. **Gunakan branches untuk fitur baru** - Jangan push langsung ke main
4. **Review perubahan sebelum commit** - Gunakan `git diff` untuk cek
5. **Tulis commit message yang bermakna** - Gunakan imperative mood ("Add", "Fix", bukan "Added", "Fixed")

## 🎯 Workflow Rekomendasi untuk Tim

```bash
# 1. Update branch main lokal
git checkout main
git pull origin main

# 2. Buat branch fitur
git checkout -b feature/new-feature

# 3. Develop feature
# ... kerja ...
git add .
git commit -m "Add new feature"

# 4. Push ke GitHub
git push origin feature/new-feature

# 5. Di GitHub, buat Pull Request
# 6. Tim review code
# 7. Merge ke main

# 8. Kembali ke main
git checkout main
git pull origin main

# 9. Hapus branch lokal setelah merge
git branch -d feature/new-feature
```

---

**Selamat! Project E-Tiket Anda sudah berhasil di-upload ke GitHub!** 🎉
