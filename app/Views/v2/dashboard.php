<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard E-Ticket</title>
    <link rel="icon" type="image/x-icon" href="<?= base_url('assets/img/logo.ico') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* =======================
        LIGHT MODE DEFAULT
        ======================= */
        body {
            background-color: #f8f9fa;
        }

        /* =======================
        DARK MODE GLOBAL
        ======================= */
        body.dark-mode {
            background-color: #0f172a;
            color: #e2e8f0;
            transition: 0.3s ease;
        }

        /* =======================
        CARD DARK MODE
        ======================= */
        .dark-mode .card,
        .dark-mode .clock-card {
            background: #1e293b;
            color: #f1f5f9;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        .dark-mode .card-header {
            background: #1e293b;
            color: #f1f5f9;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .dark-mode .text-muted {
            color: #94a3b8 !important;
        }

        .dark-mode .progress {
            background-color: #334155;
        }

        .dark-mode .form-select {
            background-color: #1e293b;
            color: #f1f5f9;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .dark-mode .btn-outline-secondary {
            color: #cbd5e1;
            border-color: #475569;
        }

        .dark-mode .btn-outline-secondary:hover {
            background: #334155;
            color: #fff;
        }

        /* LOADING SPINNER */
        .spinner-mini {
            width: 1rem;
            height: 1rem;
            display: inline-block;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid px-4 py-4">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <!-- JAM -->
            <div class="clock-card p-3 rounded shadow-sm">
                <div id="currentDate" class="fw-semibold"></div>
                <div id="currentTime" class="clock-time"></div>
            </div>
            <!-- CONTROL -->
            <div class="d-flex gap-2 align-items-center">
                <!-- DROPDOWN RANGE -->
                <select id="rangeSelector" class="form-select form-select-sm" style="width: 150px;">
                    <option value="">Semua Waktu</option>
                    <option value="7hari" selected>7 Hari</option>
                    <option value="2minggu">2 Minggu</option>
                    <option value="1bulan">1 Bulan</option>
                    <option value="3bulan">3 Bulan</option>
                    <option value="6bulan">6 Bulan</option>
                </select>
                <!-- DARK MODE -->
                <button class="btn btn-sm btn-outline-secondary" onclick="toggleDarkMode()">
                    <i class="bi bi-moon-fill"></i>
                </button>
                <!-- FULLSCREEN -->
                <button class="btn btn-sm btn-outline-primary" onclick="toggleFullScreen()">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Dashboard E-Ticket</h4>
            <div id="loadingIndicator" class="d-none">
                <div class="spinner-border spinner-mini text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>

        <!-- STAT CARD -->
        <div class="row g-3 mb-4" id="statsContainer">
            <div class="col-12 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>

        <!-- KATEGORI -->
        <div class="row g-3 mb-4" id="kategoriContainer">
            <div class="col-12 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>

        <!-- CHART -->
        <div class="card shadow-sm p-3 mb-4">
            <h6 class="fw-bold mb-3">Grafik Tiket Dibuat</h6>
            <div style="height:300px;">
                <canvas id="ticketChart"></canvas>
            </div>
        </div>
    </div>

    <!-- LOAD API HELPER -->
    <script src="<?= base_url('assets/js/etticket-api-v2.js') ?>"></script>
    <!-- LOAD CHART.JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        let chartInstance = null;
        let currentRange = '7hari';

        // =====================================================
        // JAM REALTIME
        // =====================================================
        function updateClock() {
            const now = new Date();
            const optionsDate = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const optionsTime = {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };

            document.getElementById('currentDate').innerHTML =
                now.toLocaleDateString('id-ID', optionsDate);

            document.getElementById('currentTime').innerHTML =
                now.toLocaleTimeString('id-ID', optionsTime);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // =====================================================
        // LOAD DASHBOARD DATA
        // =====================================================
        async function loadDashboard(range = '') {
            try {
                showLoading(true);
                const data = await ETTicketAPI.getDashboard(range);

                if (data.status !== 'success') {
                    throw new Error(data.message || 'Gagal memuat data dashboard');
                }

                renderStats(data.data);
                renderCategories(data.data);
                renderChart(data.data);

                showLoading(false);
            } catch (error) {
                console.error('Error loading dashboard:', error);
                document.getElementById('statsContainer').innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> Gagal memuat data: ${error.message}
                        </div>
                    </div>
                `;
                showLoading(false);
            }
        }

        // =====================================================
        // RENDER STATS CARDS
        // =====================================================
        function renderStats(dashboardData) {
            const {
                total,
                belumValid,
                proses,
                selesai,
                reject
            } = dashboardData;
            const totalData = Math.max(total, 1);

            const stats = [{
                    title: 'Total',
                    value: total,
                    color: 'primary'
                },
                {
                    title: 'Belum Valid',
                    value: belumValid,
                    color: 'secondary'
                },
                {
                    title: 'Proses',
                    value: proses,
                    color: 'warning'
                },
                {
                    title: 'Selesai',
                    value: selesai,
                    color: 'success'
                },
                {
                    title: 'Reject',
                    value: reject,
                    color: 'danger'
                },
            ];

            const html = stats.map(stat => {
                const percent = Math.round((stat.value / totalData) * 100);
                return `
                    <div class="col-xl-2 col-md-4 col-6 fade-in">
                        <div class="card shadow-sm h-100 p-3">
                            <small class="text-muted">${stat.title}</small>
                            <h5 class="fw-bold">${stat.value}</h5>
                            <div class="progress mt-2">
                                <div class="progress-bar bg-${stat.color}" style="width: ${percent}%"></div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            document.getElementById('statsContainer').innerHTML = html;
        }

        // =====================================================
        // RENDER KATEGORI CARDS
        // =====================================================
        function renderCategories(dashboardData) {
            const {
                kategoriList,
                total
            } = dashboardData;
            const totalData = Math.max(total, 1);

            if (!kategoriList || kategoriList.length === 0) {
                document.getElementById('kategoriContainer').innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-info">Tidak ada data kategori</div>
                    </div>
                `;
                return;
            }

            const html = kategoriList.map(kat => {
                const percent = Math.round((kat.jumlah / totalData) * 100);
                return `
                    <div class="col-md-3 col-6 fade-in">
                        <div class="card shadow-sm h-100 p-3">
                            <small class="text-muted">${kat.nama_kategori}</small>
                            <h6 class="fw-bold">${kat.jumlah}</h6>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: ${percent}%"></div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            document.getElementById('kategoriContainer').innerHTML = html;
        }

        // =====================================================
        // RENDER CHART
        // =====================================================
        function renderChart(dashboardData) {
            const ctx = document.getElementById('ticketChart').getContext('2d');

            // Destroy existing chart if any
            if (chartInstance) {
                chartInstance.destroy();
            }

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dashboardData.chartLabels || [],
                    datasets: [{
                            label: 'Total Tiket',
                            data: dashboardData.chartTotal || [],
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.05)',
                            tension: 0.3,
                            borderWidth: 2,
                            fill: true
                        },
                        {
                            label: 'Tiket Selesai',
                            data: dashboardData.chartSelesai || [],
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.05)',
                            tension: 0.3,
                            borderWidth: 2,
                            fill: true
                        },
                        {
                            label: 'Tiket Diproses',
                            data: dashboardData.chartProses || [],
                            borderColor: '#ffc107',
                            backgroundColor: 'rgba(255, 193, 7, 0.05)',
                            tension: 0.3,
                            borderWidth: 2,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // =====================================================
        // DARK MODE
        // =====================================================
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }

        // Load saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }

        // =====================================================
        // FULLSCREEN
        // =====================================================
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.error('Error requesting fullscreen:', err);
                });
            } else {
                document.exitFullscreen();
            }
        }

        // =====================================================
        // LOADING INDICATOR
        // =====================================================
        function showLoading(show) {
            const loader = document.getElementById('loadingIndicator');
            if (show) {
                loader.classList.remove('d-none');
            } else {
                loader.classList.add('d-none');
            }
        }

        // =====================================================
        // RANGE SELECTOR CHANGE EVENT
        // =====================================================
        document.getElementById('rangeSelector').addEventListener('change', async function(e) {
            currentRange = this.value || '';
            await loadDashboard(currentRange);
        });

        // =====================================================
        // INITIAL LOAD
        // =====================================================
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboard(currentRange);
        });

        // =====================================================
        // AUTO REFRESH (OPTIONAL - disabled by default)
        // =====================================================
        // Uncomment line below to enable auto-refresh every 5 minutes
        // setInterval(() => loadDashboard(currentRange), 5 * 60 * 1000);
    </script>
</body>

</html>