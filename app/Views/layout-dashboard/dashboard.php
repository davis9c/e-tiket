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

        let lastId = localStorage.getItem('lastId') || 0;
        let audioUnlocked = false;

        // =======================
        // 🔊 ICON UPDATE
        // =======================
        function updateAudioIcon() {
            const icon = document.getElementById('audioIcon');

            if (!audioUnlocked) {
                icon.className = 'fas fa-volume-off text-danger'; // belum aktif
            } else {
                icon.className = 'fas fa-volume-up text-success'; // aktif
            }
        }


        // =======================
        // 🔓 UNLOCK AUDIO
        // =======================
        document.addEventListener('click', () => {
            const audio = new Audio(SOUND_URL);

            audio.play().then(() => {
                audio.pause();
                audio.currentTime = 0;

                audioUnlocked = true;
                updateAudioIcon(); // 🔥 WAJIB

                console.log("🔓 Audio unlocked");
            }).catch(err => {
                console.log("❌ Gagal unlock:", err);
            });

        }, {
            once: true
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

        // klik notif → reset badge
        document.getElementById('notifBtn').addEventListener('click', () => {
            updateBadge(0);
        });

        // =======================
        // 📡 FETCH NOTIF
        // =======================
        function fetchNotifikasi() {

            fetch(BASE_URL + "notif")
                .then(res => res.json())
                .then(data => {

                    if (!Array.isArray(data) || data.length === 0) return;

                    let maxId = Math.max(...data.map(d => Number(d.id)));

                    if (maxId > lastId) {

                        let newData = data.filter(d => Number(d.id) > lastId);

                        console.log("🆕 Notif baru:", newData);

                        // 🔊 bunyi
                        playSound();

                        // 🔔 update badge
                        updateBadge(newData.length);

                        lastId = maxId;
                        localStorage.setItem('lastId', lastId);
                    }

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
</body>

</html>