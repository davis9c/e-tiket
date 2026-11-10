<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('app_name')) {
    /**
     * Nama aplikasi untuk brand di navbar, judul tab, footer, dan sidenav.
     *
     * Dibaca dari APP_NAME di .env. Helper ini sengaja tidak memakai
     * konstanta di Config/Constants.php: Boot::bootWeb() memuat Constants.php
     * SEBELUM .env dibaca, jadi env() di sana tidak akan melihat nilai dari
     * .env saat aplikasi dijalankan tanpa Docker. Common.php dimuat setelah
     * .env, jadi aman untuk kedua mode.
     */
    function app_name(): string
    {
        $nama = trim((string) env('APP_NAME', 'E-Tiket'));

        return $nama !== '' ? $nama : 'E-Tiket';
    }
}
