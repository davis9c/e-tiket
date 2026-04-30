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

        <!-- 🔔 NOTIF -->
        <li class="nav-item me-3">
            <a href="#" class="nav-link position-relative" id="notifBtn">
                <i class="fas fa-bell"></i>

                <!-- badge -->
                <span id="notifCount"
                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                    style="font-size:10px; display:none;">
                    0
                </span>
            </a>
        </li>

        <!-- 🔊 AUDIO notif status -->
        <li class="nav-item me-3">
            <a href="#" class="nav-link" id="audioToggle">
                <i id="audioIcon" class="fas fa-volume-up"></i>
            </a>

        </li>

        <!-- 👤 USER -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle"
                id="navbarDropdown"
                href="#"
                role="button"
                data-bs-toggle="dropdown">
                <i class="fas fa-user fa-fw"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= base_url('logout') ?>">Logout</a></li>
            </ul>
        </li>

    </ul>
</nav>