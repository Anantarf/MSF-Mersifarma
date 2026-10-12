// Global CSRF helper for API Fetch requests
        window.apiFetch = async function(url, options = {}) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            options.headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...(options.headers || {})
            };
            return fetch(url, options);
        };

        // Global Toast Notification Helper
        window.showToast = function(message, type = 'success', duration = 3500) {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            const safeType = ['success', 'danger', 'error', 'warning', 'info'].includes(type) ? type : 'info';
            toast.className = `toast toast-${safeType}`;

            let icon = 'bi-check-circle-fill';
            if (safeType === 'danger' || safeType === 'error') icon = 'bi-x-circle-fill';
            else if (safeType === 'warning') icon = 'bi-exclamation-triangle-fill';
            else if (safeType === 'info') icon = 'bi-info-circle-fill';

            toast.innerHTML = `
                <i class="bi ${icon} toast-icon"></i>
                <div class="toast-message">${escapeHtmlToast(message)}</div>
                <button type="button" class="toast-close" aria-label="Tutup">&times;</button>
            `;

            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.add('toast-show');
            });

            const closeBtn = toast.querySelector('.toast-close');
            let timeoutId;

            const removeToast = () => {
                clearTimeout(timeoutId);
                toast.classList.remove('toast-show');
                toast.classList.add('toast-hide');
                setTimeout(() => toast.remove(), 250);
            };

            closeBtn.addEventListener('click', removeToast);
            timeoutId = setTimeout(removeToast, duration);
        };

        window.escapeHtml = function(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };
        const escapeHtmlToast = window.escapeHtml;

        // Global Table Skeleton Generator Helper
        window.renderTableSkeleton = function(rows = 3) {
            let html = '<div class="skeleton-wrap">';
            for (let i = 0; i < rows; i++) {
                html += `
                    <div class="skeleton-row">
                        <div class="skeleton-box skeleton-w-25"></div>
                        <div class="skeleton-box skeleton-w-35"></div>
                        <div class="skeleton-box skeleton-w-20"></div>
                        <div class="skeleton-box skeleton-w-20"></div>
                    </div>
                `;
            }
            html += '</div>';
            return html;
        };

        // Standar Format Waktu Indonesia 24 Jam: 00.00 - 23.59 WIB (No AM/PM)
        window.formatWaktuIndonesia = function(timeOrDate) {
            if (!timeOrDate) return '-';
            if (timeOrDate instanceof Date) {
                const h = String(timeOrDate.getHours()).padStart(2, '0');
                const m = String(timeOrDate.getMinutes()).padStart(2, '0');
                return `${h}.${m} WIB`;
            }
            if (typeof timeOrDate === 'string') {
                const trimmed = timeOrDate.trim();
                const timeMatch = trimmed.match(/^(\d{1,2}):(\d{2})/);
                if (timeMatch) {
                    const h = timeMatch[1].padStart(2, '0');
                    const m = timeMatch[2];
                    return `${h}.${m} WIB`;
                }
                const d = new Date(trimmed);
                if (!isNaN(d.getTime())) {
                    const h = String(d.getHours()).padStart(2, '0');
                    const m = String(d.getMinutes()).padStart(2, '0');
                    return `${h}.${m} WIB`;
                }
            }
            return String(timeOrDate);
        };

        window.formatPeriodIndonesian = function(periodStr) {
            if (!periodStr) return '-';
            const parts = String(periodStr).split('-');
            if (parts.length < 2) return periodStr;
            const year = parts[0];
            const monthNum = parseInt(parts[1], 10);
            const monthNames = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            return `${monthNames[monthNum - 1] || parts[1]} ${year}`;
        };

        window.formatTanggalIndonesia = function(dateStr) {
            if (!dateStr) return '-';
            const parts = String(dateStr).split('T')[0].split(' ')[0].split('-');
            if (parts.length !== 3) return dateStr;
            const year = parts[0];
            const monthNum = parseInt(parts[1], 10);
            const day = parseInt(parts[2], 10);
            const monthNames = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            const monthName = monthNames[monthNum - 1] || parts[1];
            return `${day} ${monthName} ${year}`;
        };

        window.formatDateTimeIndonesia = function(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            const day = d.getDate();
            const monthNames = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            const month = monthNames[d.getMonth()];
            const year = d.getFullYear();
            const h = String(d.getHours()).padStart(2, '0');
            const m = String(d.getMinutes()).padStart(2, '0');
            return `${day} ${month} ${year}, ${h}.${m} WIB`;
        };

        window.normalizeTime24 = function(el) {
            if (!el || !el.value) return;
            let val = el.value.trim().replace(':', '.');
            if (/^\d{1,2}$/.test(val)) {
                val = val.padStart(2, '0') + '.00';
            } else if (/^\d{3}$/.test(val)) {
                val = '0' + val[0] + '.' + val.substring(1);
            } else if (/^\d{4}$/.test(val)) {
                val = val.substring(0, 2) + '.' + val.substring(2);
            } else if (/^(\d{1,2})\.(\d{1,2})$/.test(val)) {
                const parts = val.split('.');
                val = parts[0].padStart(2, '0') + '.' + parts[1].padEnd(2, '0');
            }
            const match = val.match(/^([0-1][0-9]|2[0-3])\.([0-5][0-9])$/);
            if (match) {
                el.value = val;
            }
        };

        window.autoFormatTimeInput = function(el) {
            if (!el) return;
            let digits = el.value.replace(/\D/g, '');
            if (digits.length > 4) digits = digits.substring(0, 4);
            if (digits.length >= 3) {
                el.value = digits.substring(0, 2) + '.' + digits.substring(2);
            } else {
                el.value = digits;
            }
        };

        // Native Network Status Listeners (Efficient & Friendly)
        window.addEventListener('online', () => {
            window.showToast('Koneksi internet terhubung kembali.', 'info', 3000);
        });
        window.addEventListener('offline', () => {
            window.showToast('Koneksi internet terputus. Mode offline aktif, draf tersimpan lokal.', 'warning', 4500);
        });
