/* =========================================================
   Interaksi halaman login E-Tiket
   - Toggle tampilkan/sembunyikan password
   - Peringatan Caps Lock
   - Loading state saat form dikirim
   ========================================================= */

(() => {
    "use strict";

    const form = document.getElementById("loginForm");
    const password = document.getElementById("password");
    const toggle = document.getElementById("togglePassword");
    const capsWarning = document.getElementById("capsWarning");
    const submitButton = document.getElementById("loginButton");

    /* -----------------------------------------------------
       Tampilkan / sembunyikan password
       ----------------------------------------------------- */
    if (toggle && password) {
        toggle.addEventListener("click", () => {
            const willShow = password.type === "password";
            const caretPosition = password.selectionStart;

            password.type = willShow ? "text" : "password";
            toggle.classList.toggle("is-visible", willShow);
            toggle.setAttribute("aria-pressed", String(willShow));
            toggle.setAttribute(
                "aria-label",
                willShow ? "Sembunyikan password" : "Tampilkan password"
            );
            toggle.setAttribute(
                "title",
                willShow ? "Sembunyikan password" : "Tampilkan password"
            );

            // Kembalikan fokus dan posisi kursor agar pengguna bisa lanjut mengetik.
            password.focus({ preventScroll: true });

            if (caretPosition !== null) {
                password.setSelectionRange(caretPosition, caretPosition);
            }
        });
    }

    /* -----------------------------------------------------
       Peringatan Caps Lock
       ----------------------------------------------------- */
    if (password && capsWarning) {
        const updateCapsState = (event) => {
            if (typeof event.getModifierState !== "function") {
                return;
            }

            capsWarning.hidden = !event.getModifierState("CapsLock");
        };

        const hideCapsState = () => {
            capsWarning.hidden = true;
        };

        password.addEventListener("keydown", updateCapsState);
        password.addEventListener("keyup", updateCapsState);
        password.addEventListener("blur", hideCapsState);
    }

    /* -----------------------------------------------------
       Loading state
       ----------------------------------------------------- */
    if (form && submitButton) {
        form.addEventListener("submit", () => {
            // Submit event hanya berjalan setelah validasi native lolos.
            // Tetap cek sekali lagi untuk form yang di-submit lewat keyboard.
            if (!form.checkValidity()) {
                return;
            }

            submitButton.classList.add("is-loading");
            submitButton.setAttribute("aria-busy", "true");
            submitButton.disabled = true;
        });

        // Jika halaman dipulihkan dari bfcache (tombol Back), jangan biarkan
        // tombol tertinggal dalam keadaan loading dan tidak bisa diklik.
        window.addEventListener("pageshow", () => {
            submitButton.classList.remove("is-loading");
            submitButton.removeAttribute("aria-busy");
            submitButton.disabled = false;
        });
    }
})();
