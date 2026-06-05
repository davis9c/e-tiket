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
    <script src="<?= base_url('FontAwesome/all.js') ?>" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
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
        const BASE_URL = "<?= base_url() ?>";
        const SOUND_URL = BASE_URL + "assets/audio/bell.mp3";

        let prevNotifCount = 0;
        let audioUnlocked = false;

        // =======================
        // 🔊 ICON UPDATE
        // =======================
        function updateAudioIcon() {
            const icon = document.getElementById('audioIcon');
            if (!icon) {
                console.log("❌ Audio icon element not found");
                return;
            }

            if (audioUnlocked) {
                icon.className = 'fas fa-volume-up text-success';
                icon.style.color = '#28a745';
            } else {
                icon.className = 'fas fa-volume-off text-danger';
                icon.style.color = '#dc3545';
            }

            console.log("🔄 Icon updated to:", audioUnlocked ? "unlocked" : "locked");
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
            unlockAudio();
        });

        window.addEventListener('load', () => {
            updateAudioIcon();
        });

        // =======================
        // 🔊 PLAY SOUND
        // =======================
        function playSound() {
            if (!audioUnlocked) {
                console.log("🔇 Audio belum aktif");
                return;
            }

            const audio = new Audio(SOUND_URL);
            audio.volume = 0.5;
            audio.play();

            console.log("🔊 Bunyi notif");
        }

        // =======================
        // 🔔 UPDATE BADGE
        // =======================
        function updateBadge(count) {
            const badge = document.getElementById('notifCount');

            if (count > 0) {
                badge.innerText = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }

        document.getElementById('notifBtn').addEventListener('click', () => {
            updateBadge(0);
            prevNotifCount = 0;
        });

        // =======================
        // 📡 FETCH NOTIF
        // =======================
        function fetchNotifikasi() {
            fetch(BASE_URL + "notif")
                .then(res => res.json())
                .then(data => {
                    if (!Array.isArray(data)) return;

                    updateBadge(data.length);

                    if (data.length > prevNotifCount) {
                        playSound();
                    }

                    prevNotifCount = data.length;
                })
                .catch(err => console.log("❌ Error:", err));
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
                .catch(error => {
                    console.error(error);
                });
        });
    </script>
</body>

</html>