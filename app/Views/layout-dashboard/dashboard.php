<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>E-TIKET | <?= $title ?></title>
    <link rel="icon" type="image/x-icon" href="<?= base_url('assets/img/logo.ico') ?>">
    <link href="<?= base_url('dataTables/style.min.css') ?>" rel="stylesheet" />
    <link href="<?= base_url('sb/css/styles.css') ?>" rel="stylesheet" />
    <!-- pencarian -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="<?= base_url('FontAwesome/all.js') ?>" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <?php
    /*
     * Toast container, pojok kanan atas.
     *
     * Dipakai oleh toastNotifikasi() untuk menampilkan notifikasi baru.
     * Dulu notifikasi ditampilkan lewat lonceng + badge jumlah di navbar;
     * sekarang cukup toast, dan badge-nya dihapus -- badge butuh interaksi
     * (klik) untuk "dibaca", padahal notifikasi adalah informasi yang harus
     * sampai tanpa user melakukan apa-apa.
     *
     * Toast bisa ditutup manual, dan diberi penanda aria-label supaya
     * tombol penutupnya terbaca sebagai "Tutup notifikasi", bukan sekadar
     * "Tutup". Histori lengkapnya ada di /notifikasi.
     *
     * Ditaruh DI AWAL body, bukan di dekat script-nya: container ini harus
     * ada sebelum toastNotifikasi() dipanggil. Kalau dipindah ke bawah,
     // pemanggilan pertama bisa mendarat sebelum elemennya ada -- dan toast
     * yang hilang tanpa error adalah kelas bug yang paling sulit dicari.
     */
    ?>
    <div class="toast-container position-fixed top-0 end-0 p-3" id="notifToast"></div>

    <?= $this->include('layout-dashboard/navbar-top') ?>
    <div id="layoutSidenav">
        <?= $this->include('layout-dashboard/sidenav') ?>

        <div id="layoutSidenav_content">
            <?= $this->renderSection('content') ?>
            <?= $this->include('layout-dashboard/footer') ?>
        </div>
    </div>
    <script>
        function openConfirmModal() {
            const selected = document.querySelector('input[name="status_validasi"]:checked');

            if (!selected) {
                alert('Pilih aksi terlebih dahulu!');
                return;
            }

            const modal = new bootstrap.Modal(document.getElementById('modalConfirm'));
            const header = document.getElementById('modalHeader');
            const title = document.getElementById('modalTitle');
            const body = document.getElementById('modalBody');
            const btn = document.getElementById('btnConfirm');

            if (selected.value == '0') {
                // TOLAK
                header.className = 'modal-header bg-danger text-white';
                title.innerHTML = 'Konfirmasi Penolakan';
                body.innerHTML = 'Apakah Anda yakin ingin <strong>menolak</strong> tiket ini?';
                btn.className = 'btn btn-danger btn-sm';
                btn.innerHTML = 'Ya, Tolak';
            } else {
                // SELESAI
                header.className = 'modal-header bg-primary text-white';
                title.innerHTML = 'Konfirmasi Penyelesaian';
                body.innerHTML = 'Apakah Anda yakin ingin <strong>menyelesaikan</strong> tiket ini?';
                btn.className = 'btn btn-primary btn-sm';
                btn.innerHTML = 'Ya, Selesaikan';
            }
            modal.show();
        }
    </script>
    <script>
        //-window.BASE_URL dipakai oleh halaman yang melakukan request AJAX
        window.BASE_URL = "<?= base_url() ?>";
        const BASE_URL = window.BASE_URL;
        const SOUND_URL = BASE_URL + "assets/audio/bell.mp3";

        let previousNotifIds = new Set();

        // Kuota toast notifikasi per muat halaman.
        //
        // Toast menandai notifikasi yang ditampilkan sebagai terbaca, jadi
        // tanpa batas, user yang Away beberapa hari akan mendapat seluruh
        // notifikasi lama menumpuk di layar setiap kali halaman dibuka.
        // Sisanya tidak hilang: tetap dihitung di badge lonceng dan muncul di
        // muat halaman berikutnya atau setelah lonceng diklik.
        const BATAS_TOAST = 3;
        let toastDitampilkan = 0;

        // Audio belum boleh berbunyi sebelum ada interaksi user -- itu aturan
        // browser, bukan pilihan kita. audioUnlocked berubah setelah interaksi
        // pertama, jadi "terbuka" dan "user sudah mengizinkan" adalah satu
        // hal yang sama.
        let audioUnlocked = false;

        // Keinginan user, disimpan supaya tidak perlu menyalakan ulang tiap
        // halaman. Tanpa ini, muted akan hilang begitu halaman di-reload --
        // dan user yang sengaja mematikan suara akan mendapatkannya kembali
        // setiap kali membuka tab baru.
        //
        // Default: AKTIF. Tidak ada setting yang tersimpan berarti user belum pernah
        // memilih apa-apa, dan mayoritas user justru ingin diberi tahu.
        const AUDIO_KEY = 'eticket.audio';
        let audioMuted = localStorage.getItem(AUDIO_KEY) === '0';

        // =======================
        // 🔊 ICON UPDATE
        // =======================
        function updateAudioIcon() {
            const icon = document.getElementById('audioIcon');
            const tombol = document.getElementById('audioToggle');
            if (!icon) {
                console.log("❌ Audio icon element not found");
                return;
            }

            // Ikon dan aria-pressed ikut localStorage, bukan audioUnlocked:
            // audioUnlocked baru berubah setelah klik pertama, sehingga kalau
            // keduanya dipakai bersama, pilihan "dimatikan" akan tampak aktif
            // selama halaman ini belum pernah diklik -- dan user akan mengira
            // pilihannya hilang.
            const bunyi = ! audioMuted;

            icon.className = bunyi ? 'fas fa-volume-up text-success' : 'fas fa-volume-off text-danger';
            icon.style.color = bunyi ? '#28a745' : '#dc3545';

            if (tombol) {
                tombol.setAttribute('aria-pressed', bunyi ? 'true' : 'false');
                tombol.setAttribute(
                    'title',
                    bunyi ? 'Bunyikan notifikasi' : 'Matikan bunyi notifikasi'
                );
            }

            console.log("🔄 Icon updated to:", bunyi ? "un" : "muted");
        }

        // =======================
        // 🔓 UNLOCK AUDIO
        // =======================
        function unlockAudio() {
            if (audioUnlocked) return;

            console.log("🔓 Attempting to unlock audio...");

            const audio = new Audio(SOUND_URL);
            audio.play().then(() => {
                audio.pause();
                audio.currentTime = 0;
                audioUnlocked = true;
                updateAudioIcon();
                console.log("✅ Audio unlocked successfully");
            }).catch(err => {
                console.log("❌ Gagal unlock:", err);
                audioUnlocked = true;
                updateAudioIcon();
                console.log("🔄 Fallback: Icon set to unlocked despite error");
            });
        }

        document.addEventListener('click', unlockAudio, {
            once: true
        });

        document.getElementById('audioToggle').addEventListener('click', (e) => {
            e.preventDefault();

            audioMuted = ! audioMuted;
            localStorage.setItem(AUDIO_KEY, audioMuted ? '0' : '1');
            updateAudioIcon();

            // Menyalakan kembali butuh izin browser. Kalau user baru menyalakan
            // dan halaman ini belum pernah diklik, audioUnlocked masih false
            // dan bunyi berikutnya tidak akan terdengar tanpa interaksi lagi --
            // jadi biarkan unlockAudio() yang mengurus perizinannya.

            unlockAudio();

            if (audioMuted) {
                return;
            }

            // Konfirmasi singkat kalau yang baru dinyalakan: tanpa ini user
            // tidak tahu bunyinya benar-benar jalan atau tidak, dan testnya
            // jadi "dengarkan sendiri".
            const coba = new Audio(SOUND_URL);
            coba.volume = 0.5;
            coba.play().then(() => {
                coba.pause();
                coba.currentTime = 0;
            }).catch(() => {
                // Browser menolak (belum ada interaksi) -- unlockAudio()
                // sudah menangani pemanggilannya sendiri.
            });
        });

        window.addEventListener('load', () => {
            updateAudioIcon();
        });

        // =======================
        // 🔊 PLAY SOUND
        // =======================
        function playSound() {
            // Dua syarat terpisah, dan keduanya wajib:
            //
            //   audioUnlocked - browser belum mengizinkan audio sama sekali
            //                  sampai ada interaksi user. Tidak ada cara
            //                 memintanya di Javascript.
            //   audioMuted    - pilihan user lewat tombol di navbar.
            //
            // Dulu hanya yang pertama dicek, jadi ikon "🔊 aktif" yang
            // ditampilkan navbar sebenarnya hanya berarti "browser mengizinkan
            // audio" -- dan user tidak pernah bisa mematikan bunyinya sama
            // sekali.
            if (!audioUnlocked) {
                console.log("🔇 Audio belum diizinkan browser");
                return;
            }

            if (audioMuted) {
                console.log("🔇 Bunyi dimatikan user");
                return;
            }

            const audio = new Audio(SOUND_URL);
            audio.volume = 0.5;
            audio.play();

            console.log("🔊 Bunyi notif");
        }

        // =======================
        // 🔔 TOAST NOTIFIKASI
        // =======================
        // Satu toast per notifikasi baru. delay 4000ms dan pembuangan
        // elemen saat hidden, mengikuti public/js/kategori.js supaya
        // tampilannya sama dengan toast yang sudah dipakai di aplikasi.
        function toastNotifikasi(pesan) {
            const container = document.getElementById('notifToast');
            if (!container || !pesan) return;

            const el = document.createElement('div');
            el.className = 'toast align-items-center text-bg-info border-0';
            el.setAttribute('role', 'alert');
            el.setAttribute('aria-live', 'assertive');
            el.setAttribute('aria-atomic', 'true');

            // Ikon ditulis sebagai markup statis, pesan lewat textContent.
            // Pesan berasal dari isinya, bukan dari kode, jadi tidak boleh
            // masuk lewat innerHTML -- nama orang atau isi tiket bisa
            // memuat karakter yang punya arti sebagai HTML.
            el.innerHTML =
                '<div class="d-flex">' +
                '<div class="toast-body">' +
                '<i class="fas fa-bell me-2"></i>' +
                '<span class="notif-toast-teks"></span>' +
                '</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" ' +
                'data-bs-dismiss="toast" aria-label="Tutup notifikasi"></button>' +
                '</div>';

            el.querySelector('.notif-toast-teks').textContent = pesan;
            container.appendChild(el);

            const instance = new bootstrap.Toast(el, { delay: 4000 });
            el.addEventListener('hidden.bs.toast', function () {
                el.remove();
            });
            instance.show();
        }

        // =======================
        // 🔖 TANDAI TERBACA
        // =======================
        // Dipanggil setelah toast ditampilkan, dan dari klik lonceng. Yang
        // ditandai HANYA notifikasi yang benar-benar sudah terlihat: kalau
        // sisanya ikut ditandai, badge akan ikut kosong dan lonceng tidak
        // akan pernah punya isi lagi.
        //
        // Promise-nya dikembalikan supaya pemanggil bisa menunggu. Klik
        // lonceng butuh itu: badge baru dikosongkan setelah server mengonfirmasi,
        // kalau tidak badge akan terlihat 0 padahal belum ada yang berubah.
        function tandaiTerbaca(ids) {
            if (! ids.length) return Promise.resolve();

            return fetch(BASE_URL + "notif/read", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: Array.from(ids) })
            }).catch(error => {
                console.error("❌ Error marking notifications read:", error);
            });
        }

        // =======================
        // 🔔 UPDATE BADGE
        // =======================
        function updateBadge(count) {
            const badge = document.getElementById('notifCount');
            if (! badge) return;

            if (count > 0) {
                badge.innerText = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }

        // Klik lonceng = tandai semua terbaca. Endpoint JSON hanya mengembalikan
        // yang read_at-nya NULL, jadi setelah POST ini badge langsung kosong.
        document.getElementById('notifBtn').addEventListener('click', (e) => {
            e.preventDefault();

            const ids = Array.from(previousNotifIds);
            if (! ids.length) return;

            tandaiTerbaca(ids).then(() => {
                previousNotifIds.clear();
                updateBadge(0);
            });
        });

        // =======================
        // 📡 FETCH NOTIF
        // =======================

        function fetchNotifikasi() {
            fetch(BASE_URL + "notif")
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (!Array.isArray(data)) {
                        return;
                    }

                    const ids = data.map(item => Number(item.id)).filter(Boolean);
                    const newIds = ids.filter(id => ! previousNotifIds.has(id));
                    previousNotifIds = new Set(ids);

                    // Badge dihitung dari SEMUA yang belum terbaca, bukan dari
                    // yang baru. Itu yang membuatnya tetap jujur: notifikasi
                    // yang tidak sempat jadi toast (karena kuota habis) masih
                    // terlihat di sini sampai user menekan lonceng.
                    updateBadge(ids.length);

                    if (newIds.length === 0) {
                        // Tidak ada yang baru: tidak ada toast dan tidak ada
                        // bunyi. Polling 3 detik tetap berjalan untuk badge.
                        return;
                    }

                    playSound();

                    // Kuota toast per MUAT HALAMAN, bukan per polling.
                    //
                    // Kalau batasnya dihitung tiap poll, 7 notifikasi akan
                    // jadi 3 toast sekarang, 3 toast lagi 3 detik kemudian,
                    // dan seterusnya -- lebih membanjir daripada tanpa batas
                    // sama sekali. Dengan kuota sekali, paling banyak 3 toast
                    // muncul sampai halaman dimuat ulang.
                    const sisa = BATAS_TOAST - toastDitampilkan;
                    if (sisa <= 0) {
                        return;
                    }

                    // data sudah urut terbaru dulu (orderBy created_at DESC di
                    // Notifikasi::index), jadi slice mengambil yang terbaru.
                    const untukToast = newIds.slice(0, sisa);
                    const peta = new Map(data.map(item => [Number(item.id), item]));

                    untukToast.forEach(id => {
                        const item = peta.get(id);
                        if (item) {
                            toastNotifikasi(item.pesan);
                        }
                    });

                    toastDitampilkan += untukToast.length;

                    // Hanya yang ditampilkan yang ditandai terbaca. Sisanya
                    // sengaja dibiarkan: itu yang membuat badge tidak kosong
                    // dan yang memberi alasan lonceng masih berguna.
                    tandaiTerbaca(untukToast);
                })
                .catch(error => {
                    console.error("❌ Error:", error);
                });
        }

        // =======================
        // LOOP
        // =======================
        setInterval(fetchNotifikasi, 3000);

        // first load
        fetchNotifikasi();
    </script>

    <script src="<?= base_url('BootStrap/bootstrap.bundle.min.js') ?>" crossorigin="anonymous"></script>
    <script src="<?= base_url('sb/js/scripts.js') ?>"></script>
    <script src="<?= base_url('dataTables/simple-datatables.min.js') ?>" crossorigin="anonymous"></script>
    <script src="<?= base_url('sb/js/datatables-simple-demo.js') ?>"></script>
    <script src="<?= base_url('js/dataTables.js') ?>"></script>
    <!-- UPDATE BARU 25 05 2026 -->
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script>
        document.querySelectorAll('.editor').forEach((element) => {
            ClassicEditor
                .create(element, {
                    toolbar: [
                        'heading',
                        '|',
                        'bold',
                        'italic',
                        'underline',
                        '|',
                        'bulletedList',
                        'numberedList',
                        '|',
                        'undo',
                        'redo'
                    ]
                })
                .then((editor) => {
                    // Simpan instance supaya form AJAX bisa mengambil
                    // isi HTML lewat editor.getData() sebelum submit.
                    element.ckEditorInstance = editor;
                })
                .catch(error => {
                    console.error(error);
                });
        });
    </script>
    <?= $this->renderSection('pageScripts') ?>
</body>

</html>