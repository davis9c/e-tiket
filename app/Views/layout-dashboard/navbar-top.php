<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <!-- Navbar Brand -->
    <a class="navbar-brand ps-3" href="<?= base_url() ?>">E-Tiket</a>

    <!-- Sidebar Toggle -->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" 
            id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Right Navbar -->
    <ul class="navbar-nav ms-auto me-3 me-lg-4 align-items-center">

        <!-- Menu Dropdown (Pengganti Search) -->
        <li class="nav-item dropdown me-3">
            <a class="nav-link dropdown-toggle" href="#" 
               id="menuDropdown" 
               role="button" 
               data-bs-toggle="dropdown" 
               aria-expanded="false">
                Menu
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuDropdown">
                <li><a class="dropdown-item" href="#!">Dashboard</a></li>
                <li><a class="dropdown-item" href="#!">Data Tiket</a></li>
                <li><a class="dropdown-item" href="#!">Laporan</a></li>
            </ul>
        </li>

        <!-- User Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" 
               id="navbarDropdown" 
               href="#" 
               role="button" 
               data-bs-toggle="dropdown" 
               aria-expanded="false">
                <i class="fas fa-user fa-fw"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" 
                aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item" href="#!">Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= base_url('logout') ?>">Logout</a></li>
            </ul>
        </li>
    </ul>
</nav>