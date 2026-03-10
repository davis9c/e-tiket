window.addEventListener("DOMContentLoaded", (event) => {
    // Ambil semua elemen dengan class datatable
    const datatablesSimple = document.querySelectorAll(".datatable");

    if (datatablesSimple.length > 0) {
        datatablesSimple.forEach((table) => {
            new simpleDatatables.DataTable(table, {
                perPageSelect: [5, 10, 15],
                perPage: 10,
                searchable: true,
                sortable: true,
            });
        });
    }
});
