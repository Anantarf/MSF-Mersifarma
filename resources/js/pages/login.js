function setDemoModalVisible(visible) {
        const modal = document.getElementById('demoModal');
        if (modal) modal.style.display = visible ? 'block' : 'none';
    }

    function toggleDemoModal() {
        const modal = document.getElementById('demoModal');
        setDemoModalVisible(!modal || modal.style.display === 'none' || modal.style.display === '');
    }

    function fillAccount(account, password) {
        const usernameInput = document.getElementById('username') || document.getElementById('email');
        if (usernameInput) {
            usernameInput.value = account;
            usernameInput.classList.remove('is-invalid');
        }
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.value = password;
            passwordInput.type = 'password';
            passwordInput.classList.remove('is-invalid');
            const eyeIcon = document.getElementById('passwordEyeIcon');
            if (eyeIcon) eyeIcon.className = 'bi bi-eye';
            const btn = document.getElementById('togglePasswordBtn');
            if (btn) {
                btn.setAttribute('title', 'Tampilkan kata sandi');
                btn.setAttribute('aria-label', 'Tampilkan kata sandi');
            }
        }
        setDemoModalVisible(false);
    }

    function togglePasswordVisibility() {
        const pwd = document.getElementById('password');
        const icon = document.getElementById('passwordEyeIcon');
        const btn = document.getElementById('togglePasswordBtn');
        if (!pwd || !icon) return;

        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.className = 'bi bi-eye-slash password-eye-icon';
            if (btn) {
                btn.setAttribute('title', 'Sembunyikan kata sandi');
                btn.setAttribute('aria-label', 'Sembunyikan kata sandi');
            }
        } else {
            pwd.type = 'password';
            icon.className = 'bi bi-eye password-eye-icon';
            if (btn) {
                btn.setAttribute('title', 'Tampilkan kata sandi');
                btn.setAttribute('aria-label', 'Tampilkan kata sandi');
            }
        }
    }

    async function handleLoginSubmit(event) {
        event.preventDefault();
        const btn = document.getElementById('submitBtn');
        const text = document.getElementById('btnText');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const rememberInput = document.getElementById('remember');

        usernameInput.classList.remove('is-invalid');
        passwordInput.classList.remove('is-invalid');

        if (btn && text) {
            btn.disabled = true;
            btn.style.opacity = '0.85';
            btn.style.cursor = 'wait';
            text.textContent = 'Memverifikasi...';
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch(document.getElementById('loginForm')?.action || window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    username: usernameInput.value,
                    password: passwordInput.value,
                    remember: rememberInput.checked
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                showTopNotification('success', 'Login berhasil! Mengalihkan ke sistem...');
                if (btn && text) {
                    text.textContent = 'Mengalihkan...';
                }
                const redirectTarget = (data && data.redirect_url) ? data.redirect_url : document.getElementById('loginForm')?.dataset.redirectUrl || '/call-lists';
                setTimeout(() => {
                    window.location.href = redirectTarget;
                }, 700);
            } else {
                let errorMsg = 'Username atau kata sandi yang Anda masukkan salah.';
                if (data && data.message) {
                    errorMsg = data.message;
                } else if (data && data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    if (firstKey && data.errors[firstKey] && data.errors[firstKey][0]) {
                        errorMsg = data.errors[firstKey][0];
                    }
                }
                showTopNotification('error', errorMsg);
                usernameInput.classList.add('is-invalid');
                passwordInput.classList.add('is-invalid');
                if (btn && text) {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                    text.textContent = 'Masuk ke Aplikasi';
                }
            }
        } catch (error) {
            document.getElementById('loginForm').submit();
        }
    }

    let topToastTimer = null;

    function showTopNotification(type, message) {
        if (!message || typeof message !== 'string' || message.trim() === '') return;
        const banner = document.getElementById('topNotificationBanner');
        const text = document.getElementById('topNotificationText');
        const icon = document.getElementById('topNotificationIcon');
        if (!banner || !text) return;

        if (topToastTimer) {
            clearTimeout(topToastTimer);
            topToastTimer = null;
        }

        banner.className = 'top-toast-banner ' + (type === 'success' ? 'top-toast-success' : 'top-toast-error');
        text.textContent = message;
        banner.style.opacity = '1';
        banner.style.transform = 'translate(-50%, 0)';

        if (type === 'success') {
            icon.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
            topToastTimer = setTimeout(() => {
                closeTopNotification();
            }, 4500);
        } else {
            icon.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>';
        }

        banner.style.display = 'flex';
    }

    function closeTopNotification() {
        const banner = document.getElementById('topNotificationBanner');
        if (banner) {
            banner.style.opacity = '0';
            banner.style.transform = 'translate(-50%, -15px)';
            setTimeout(() => {
                banner.style.display = 'none';
            }, 200);
        }
    }

    // Safety guard, event binding, and auto-dismiss on page load.
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('loginForm')?.addEventListener('submit', handleLoginSubmit);

        document.addEventListener('click', function(event) {
            const accountButton = event.target.closest('[data-account]');
            if (accountButton) {
                fillAccount(accountButton.dataset.account, accountButton.dataset.password || 'password');
                return;
            }

            const actionButton = event.target.closest('[data-action]');
            if (!actionButton) return;

            if (actionButton.dataset.action === 'toggle-demo') toggleDemoModal();
            if (actionButton.dataset.action === 'toggle-password') togglePasswordVisibility();
            if (actionButton.dataset.action === 'close-toast') closeTopNotification();
        });

        const banner = document.getElementById('topNotificationBanner');
        const text = document.getElementById('topNotificationText');
        if (banner) {
            const hasText = text && text.textContent.trim().length > 0;
            if (!hasText) {
                banner.style.display = 'none';
                return;
            }
            if (banner.classList.contains('top-toast-success')) {
                topToastTimer = setTimeout(() => {
                    closeTopNotification();
                }, 4500);
            }
        }
    });

    // Close popover if clicked outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('demoModal');
        const trigger = document.getElementById('demoAccountsTrigger');
        if (modal && trigger && !modal.contains(event.target) && !trigger.contains(event.target)) {
            modal.style.display = 'none';
        }
    });
