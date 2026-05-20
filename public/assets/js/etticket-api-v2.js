/**
 * E-Ticket API V2 JavaScript Helper
 * Untuk AJAX requests ke API v2
 */

const ETTicketAPI = {
    baseUrl: '/api/v2',

    /**
     * Dashboard - Get Statistics
     */
    getDashboard: async function(range = '7hari') {
        return fetch(`${this.baseUrl}/dashboard?range=${range}`)
            .then(res => res.json());
    },

    /**
     * Tickets - Get List
     */
    getTicketList: async function(status = 'all') {
        return fetch(`${this.baseUrl}/tickets?status=${status}`)
            .then(res => res.json());
    },

    /**
     * Tickets - Get Detail
     */
    getTicketDetail: async function(ticketId) {
        return fetch(`${this.baseUrl}/tickets/${ticketId}`)
            .then(res => res.json());
    },

    /**
     * Tickets - Get Timeline
     */
    getTicketTimeline: async function(ticketId) {
        return fetch(`${this.baseUrl}/tickets/${ticketId}/timeline`)
            .then(res => res.json());
    },

    /**
     * Categories - Get List
     */
    getCategories: async function() {
        return fetch(`${this.baseUrl}/categories`)
            .then(res => res.json());
    },

    /**
     * Petugas - Get List
     */
    getPetugas: async function(kdJbtn = null) {
        let url = `${this.baseUrl}/petugas`;
        if (kdJbtn) {
            url += `?kd_jbtn=${kdJbtn}`;
        }
        return fetch(url)
            .then(res => res.json());
    },

    /**
     * Tickets - Approve
     */
    approveTicket: async function(ticketId, catatan = '') {
        return fetch(`${this.baseUrl}/tickets/${ticketId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ catatan })
        })
            .then(res => res.json());
    },

    /**
     * Tickets - Process (Forward to next unit)
     */
    processTicket: async function(ticketId, unitSelanjutnya, catatan) {
        return fetch(`${this.baseUrl}/tickets/${ticketId}/process`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                unit_selanjutnya: unitSelanjutnya,
                catatan: catatan
            })
        })
            .then(res => res.json());
    },

    /**
     * Tickets - Reject
     */
    rejectTicket: async function(ticketId, catatan) {
        return fetch(`${this.baseUrl}/tickets/${ticketId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ catatan })
        })
            .then(res => res.json());
    },

    /**
     * Tickets - Submit New
     */
    submitTicket: async function(formData) {
        return fetch(`${this.baseUrl}/tickets/submit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(formData)
        })
            .then(res => res.json());
    },

    /**
     * Handle API Error Response
     */
    handleError: function(error) {
        console.error('API Error:', error);
        if (error.errors) {
            // Validation errors
            return Object.values(error.errors).join(', ');
        }
        return error.message || 'Terjadi kesalahan';
    },

    /**
     * Show Toast/Alert
     */
    showMessage: function(message, type = 'info') {
        // Customize ini sesuai dengan library yang digunakan
        console.log(`[${type.toUpperCase()}] ${message}`);

        // Example dengan Bootstrap Toast
        if (window.bootstrap) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.insertBefore(alertDiv, document.body.firstChild);

            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    }
};

/**
 * Utility: Render Dashboard Stats
 */
function renderDashboardStats(data) {
    const statsData = [
        { label: 'Total', value: data.total, color: 'primary' },
        { label: 'Belum Valid', value: data.belumValid, color: 'secondary' },
        { label: 'Proses', value: data.proses, color: 'warning' },
        { label: 'Selesai', value: data.selesai, color: 'success' },
        { label: 'Reject', value: data.reject, color: 'danger' }
    ];

    return statsData.map(stat => {
        const percent = data.total > 0 ? Math.round((stat.value / data.total) * 100) : 0;
        return `
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card shadow-sm h-100 p-3">
                    <small class="text-muted">${stat.label}</small>
                    <h5 class="fw-bold">${stat.value}</h5>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-${stat.color}" style="width: ${percent}%"></div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Utility: Render Category Cards
 */
function renderCategoryCards(kategoriList, total) {
    return kategoriList.map(kat => {
        const percent = total > 0 ? Math.round((kat.jumlah / total) * 100) : 0;
        return `
            <div class="col-md-3 col-6">
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
}

/**
 * Utility: Render Ticket Row
 */
function renderTicketRow(ticket) {
    const statusBadge = getStatusBadge(ticket);
    return `
        <tr>
            <td>${ticket.judul}</td>
            <td>${ticket.nm_jbtn}</td>
            <td>${statusBadge}</td>
            <td>${new Date(ticket.created_at).toLocaleDateString('id-ID')}</td>
            <td>
                <a href="/etiket/${ticket.hashid}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> Detail
                </a>
            </td>
        </tr>
    `;
}

/**
 * Utility: Get Status Badge
 */
function getStatusBadge(ticket) {
    if (ticket.reject_nama) {
        return `<span class="badge bg-danger">Ditolak</span>`;
    }
    if (ticket.selesai_nama) {
        return `<span class="badge bg-success">Selesai</span>`;
    }
    if (ticket.valid_nama) {
        return `<span class="badge bg-info">Diproses</span>`;
    }
    return `<span class="badge bg-warning">Belum Valid</span>`;
}

/**
 * Utility: Format Date
 */
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Utility: Format Time
 */
function formatTime(dateString) {
    return new Date(dateString).toLocaleTimeString('id-ID');
}
