const pageEl = document.querySelector('[data-call-actuals-page]');
const currentUserRole = pageEl?.dataset.currentUserRole || "";
const callPlansUrl = pageEl?.dataset.callPlansUrl || "/call-plans";

let todayPlans = [];
let todayPlansLoaded = false;
let productsLoadFailed = false;
let actuals = [];
let doctors = [];
let products = [];
let activeSession = null;
let isDrawing = false;
let sigCanvas, sigCtx;

const planLookup = new Map();
let currentActualStep = 1;
let isPreparingActualForm = false;
let actualFormDirty = false;
let isSubmittingActual = false;
let isStartingSession = false;
let lockedPlanDate = '';

const EXPIRED_LOOKBACK_DAYS = 14;
const EXPIRED_MAX_ITEMS = 5;

// Mapping label + warna dipisah per entitas agar status Plan, Session, dan Actual tidak tertukar.
const PLAN_STATUS = {
    scheduled: ['Terjadwal', 'status-scheduled'],
    completed: ['Selesai', 'status-approved'],
    cancelled: ['Dibatalkan', 'status-draft'],
    expired: ['Kedaluwarsa', 'status-warning'],
};
const ACTUAL_TYPE = {
    planned: ['Terencana', 'status-approved'],
    unplanned: ['Di Luar Rencana', 'status-scheduled'],
    non_target: ['Non-Target', 'status-draft'],
};
const SESSION_STATUS = {
    in_progress: ['Sedang Berlangsung', 'status-approved'],
    checked_out: ['Menunggu Laporan', 'status-warning'],
};

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}

function statusBadge(map, key, kindTitle) {
    const [label, cls] = map[key] || [String(key || '-'), 'status-draft'];
    return `<span class="status-badge ${cls}" title="${escapeHtml(kindTitle)}">${escapeHtml(label)}</span>`;
}

function byId(id) {
    return document.getElementById(id);
}

function isVisible(id) {
    const el = byId(id);
    return !!el && el.style.display !== 'none';
}

document.addEventListener('DOMContentLoaded', async () => {
    bindStaticEvents();

    if (currentUserRole === 'mr') {
        renderTodayLabel();
        initSignaturePad();
        await Promise.all([loadDoctors(), loadProducts()]);
        await loadActiveSession();
        await loadTodayPlans();
        loadExpiredPlans();

        const urlParams = new URLSearchParams(window.location.search);
        const autoPlanId = parseInt(urlParams.get('plan_id'), 10);
        const autoDocId = urlParams.get('doctor_id');
        if (Number.isInteger(autoPlanId) && !activeSession) {
            const plan = await ensurePlanForReport(autoPlanId);
            if (plan) {
                openActualModal(autoPlanId, autoDocId ? parseInt(autoDocId, 10) : null);
            } else {
                showActualAlert('Jadwal tidak ditemukan atau sudah tidak dapat diisi sebagai laporan terencana.', 'warning');
            }
        }
    }

    loadActuals();
});

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (isVisible('leaveConfirmModal')) {
        hideLeaveConfirm();
        return;
    }
    if (isVisible('actualModal')) {
        requestCloseActualModal();
        return;
    }
    closeCancelSessionModal();
    closeEvidenceModal();
});

window.addEventListener('beforeunload', (e) => {
    if (isVisible('actualModal') && actualFormDirty) {
        e.preventDefault();
        e.returnValue = '';
    }
});

function bindStaticEvents() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-action]');
        if (!trigger) return;

        const action = trigger.dataset.action;
        switch (action) {
            case 'open-unplanned-actual':
                openActualModal(null, trigger.dataset.doctorId ? parseInt(trigger.dataset.doctorId, 10) : null);
                break;
            case 'session-checkout':
                handleSessionCheckout();
                break;
            case 'open-session-actual':
                openActualModalFromSession();
                break;
            case 'open-cancel-session':
                openCancelSessionModal();
                break;
            case 'reset-manager-filters':
                resetManagerFilters();
                break;
            case 'close-actual-modal':
                requestCloseActualModal();
                break;
            case 'stay-in-actual':
                hideLeaveConfirm();
                break;
            case 'leave-actual':
                hideLeaveConfirm();
                closeActualModal();
                break;
            case 'actual-step':
                goToActualStep(parseInt(trigger.dataset.step, 10));
                break;
            case 'actual-prev':
                if (currentActualStep === 1) requestCloseActualModal();
                else goToActualStep(currentActualStep - 1);
                break;
            case 'actual-next':
                goToActualStep(currentActualStep + 1);
                break;
            case 'open-date-picker':
                openActualDatePicker();
                break;
            case 'reset-actual-form':
                resetActualForm();
                break;
            case 'clear-photo':
                clearPhoto();
                break;
            case 'verification-mode':
                switchVerificationMode(trigger.dataset.mode);
                break;
            case 'clear-signature':
                clearSignature();
                break;
            case 'clear-stamp-file':
                clearStampFile();
                break;
            case 'close-cancel-session':
                closeCancelSessionModal();
                break;
            case 'close-evidence-modal':
                closeEvidenceModal();
                break;
            case 'start-visit-session':
                startVisitSession(parseInt(trigger.dataset.planId, 10), parseInt(trigger.dataset.doctorId, 10));
                break;
            case 'open-planned-actual':
                openActualModal(parseInt(trigger.dataset.planId, 10));
                break;
            case 'show-evidence':
                openEvidenceModalFromActualId(trigger.dataset.actualId);
                break;
            case 'retry-load':
                retryLoad(trigger.dataset.target);
                break;
            default:
                break;
        }
    });

    const actualForm = byId('actualForm');
    actualForm?.addEventListener('submit', submitActual);
    actualForm?.addEventListener('input', markActualDirty);
    actualForm?.addEventListener('change', markActualDirty);
    byId('cancelSessionForm')?.addEventListener('submit', handleCancelSessionSubmit);
    byId('productSearchInput')?.addEventListener('input', (event) => filterProductPicker(event.target.value));
    byId('photoFileInput')?.addEventListener('change', (event) => handlePhotoFile(event.target));
    byId('stampFileInput')?.addEventListener('change', (event) => handleStampFile(event.target));
    byId('productPicker')?.addEventListener('change', (event) => {
        if (event.target.matches('input[name="product_ids"]')) {
            saveActualDraft();
        }
    });

    ['filterManagerMr', 'filterManagerMonth', 'filterManagerVisitType'].forEach((id) => {
        byId(id)?.addEventListener('change', loadActuals);
    });

    byId('actualDoctorSelect')?.addEventListener('change', saveActualDraft);
    byId('actualDateInput')?.addEventListener('change', () => {
        syncActualDateDisplay();
        saveActualDraft();
    });
    ['checkInInput', 'checkOutInput'].forEach((id) => {
        const input = byId(id);
        input?.addEventListener('input', () => window.autoFormatTimeInput(input));
        input?.addEventListener('blur', () => window.normalizeTime24(input));
        input?.addEventListener('change', saveActualDraft);
    });
    byId('visitReasonInput')?.addEventListener('input', saveActualDraft);
}

/* ------------------------------------------------------------------ */
/* API helper: satu pintu agar 401/403/409/419/422/5xx/jaringan konsisten */
/* ------------------------------------------------------------------ */

async function callApi(url, options = {}) {
    let res;
    try {
        res = await window.apiFetch(url, options);
    } catch (err) {
        return { networkError: true, ok: false, status: 0, body: null };
    }

    let body = null;
    try {
        body = await res.json();
    } catch (err) {
        body = null;
    }

    return {
        networkError: false,
        ok: res.ok && !!body && body.success !== false,
        status: res.status,
        body,
    };
}

const FIELD_BY_ERROR_KEY = {
    doctor_id: ['doctor', 1],
    visit_date: ['date', 1],
    check_in: ['time', 1],
    check_out: ['time', 1],
    photo_url: ['evidence', 2],
    signature_url: ['evidence', 2],
    products: ['products', 2],
};
const FIELD_BY_ERROR_CODE = {
    PHOTO_REQUIRED: ['evidence', 2],
    SIGNATURE_REQUIRED: ['evidence', 2],
    PRODUCT_REQUIRED: ['products', 2],
    DOCTOR_PLAN_MISMATCH: ['doctor', 1],
    VISIT_TIME_IN_FUTURE: ['time', 1],
};

function describeApiError(result, fallbackMessage) {
    const { status, body, networkError } = result;
    const info = { message: fallbackMessage, fields: {}, step: null };

    if (networkError) {
        info.message = 'Koneksi ke server terputus. Periksa jaringan Anda lalu coba lagi.';
        return info;
    }
    if (status === 401) {
        info.message = 'Sesi login Anda berakhir. Muat ulang halaman dan masuk kembali.';
        return info;
    }
    if (status === 419) {
        info.message = 'Sesi halaman kedaluwarsa. Muat ulang halaman lalu coba lagi.';
        return info;
    }
    if (status >= 500 || !body) {
        info.message = 'Server sedang bermasalah. Coba lagi beberapa saat.';
        return info;
    }

    if (status === 403) {
        info.message = body.message || 'Anda tidak memiliki akses untuk tindakan ini.';
        return info;
    }

    if (body.error_code === 'VISIT_ALREADY_EXISTS') {
        info.message = body.data?.owned_by_me
            ? 'Kunjungan ini sudah tercatat atas nama Anda. Periksa Riwayat Kunjungan.'
            : 'Dokter sudah memiliki kunjungan pada tanggal ini.';
        return info;
    }

    info.message = body.message || fallbackMessage;

    const mappedByCode = FIELD_BY_ERROR_CODE[body.error_code];
    if (mappedByCode) {
        info.fields[mappedByCode[0]] = body.message;
        info.step = mappedByCode[1];
    }

    if (body.errors && typeof body.errors === 'object') {
        Object.entries(body.errors).forEach(([key, messages]) => {
            const mapped = FIELD_BY_ERROR_KEY[key.split('.')[0]];
            if (!mapped) return;
            info.fields[mapped[0]] = Array.isArray(messages) ? messages[0] : String(messages);
            info.step = info.step ? Math.min(info.step, mapped[1]) : mapped[1];
        });
    }

    return info;
}

/* ------------------------------------------------------------------ */
/* Bukti (modal lihat bukti)                                           */
/* ------------------------------------------------------------------ */

function openEvidenceModalFromActualId(actualId) {
    const actual = actuals.find((item) => String(item.id) === String(actualId));
    if (!actual) return;

    showEvidenceModal(
        actual.doctor_name || 'Dokter',
        actual.photo_url || '',
        actual.signature_url || '',
        actual.mr_name || ''
    );
}

function showEvidenceModal(doctorName, photoUrl, sigUrl, mrName = '') {
    byId('evidenceDoctorName').innerText = doctorName || 'Dokter';
    byId('evidencePhotoImg').src = photoUrl || '';
    byId('evidenceSignatureImg').src = sigUrl || '';
    const mrWrap = byId('evidenceMrWrap');
    const mrNameEl = byId('evidenceMrName');
    if (mrWrap && mrNameEl) {
        if (mrName) {
            mrNameEl.innerText = mrName;
            mrWrap.style.display = 'block';
        } else {
            mrWrap.style.display = 'none';
        }
    }
    byId('evidenceModal').style.display = 'flex';
}

function closeEvidenceModal() {
    const modal = byId('evidenceModal');
    if (modal) modal.style.display = 'none';
}

/* ------------------------------------------------------------------ */
/* Format tanggal / waktu                                              */
/* ------------------------------------------------------------------ */

function formatVisitDateIndonesian(dateStr) {
    if (!dateStr) return '-';
    const parts = String(dateStr).split('T')[0].split('-');
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
}

function localDateString(date) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

// Tanggal lokal perangkat (bukan UTC) agar jadwal hari ini tidak bergeser sebelum pukul 07.00 WIB.
function todayString() {
    return localDateString(new Date());
}

function renderTodayLabel() {
    const label = byId('actualTodayLabel');
    if (!label) return;
    label.innerText = 'Hari ini - ' + formatVisitDateIndonesian(todayString());
}

function openActualDatePicker() {
    const picker = byId('actualDateInput');
    if (!picker || picker.disabled) return;
    if (typeof picker.showPicker === 'function') {
        picker.showPicker();
    } else {
        picker.focus();
        picker.click();
    }
}

function syncActualDateDisplay() {
    const input = byId('actualDateInput');
    const display = byId('actualDateDisplay');
    if (input && display) {
        display.value = input.value ? formatVisitDateIndonesian(input.value) : '';
    }
}

function formatWaktuIndonesia(timeOrDate) {
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
}

/* ------------------------------------------------------------------ */
/* Tanda tangan, foto, stempel                                         */
/* ------------------------------------------------------------------ */

let cameraStream = null;
let verificationMode = 'touch';
let touchSignatureDataUrl = '';
let stampSignatureDataUrl = '';

function initSignaturePad() {
    sigCanvas = byId('signatureCanvas');
    if (!sigCanvas) return;
    sigCtx = sigCanvas.getContext('2d');
    sigCtx.strokeStyle = '#0f172a';
    sigCtx.lineWidth = 2.5;
    sigCtx.lineCap = 'round';
    sigCtx.lineJoin = 'round';

    function getPos(e) {
        const rect = sigCanvas.getBoundingClientRect();
        const touch = (e.touches && e.touches[0]) ? e.touches[0] : ((e.changedTouches && e.changedTouches[0]) ? e.changedTouches[0] : e);
        const clientX = touch.clientX;
        const clientY = touch.clientY;
        const scaleX = rect.width > 0 ? (sigCanvas.width / rect.width) : 1;
        const scaleY = rect.height > 0 ? (sigCanvas.height / rect.height) : 1;
        return {
            x: (clientX - rect.left) * scaleX,
            y: (clientY - rect.top) * scaleY
        };
    }

    function finishStroke() {
        if (!isDrawing) return;
        isDrawing = false;
        if (verificationMode === 'touch') {
            touchSignatureDataUrl = sigCanvas.toDataURL('image/png');
            byId('signatureUrlInput').value = touchSignatureDataUrl;
        }
        saveActualDraft();
    }

    sigCanvas.addEventListener('mousedown', (e) => {
        isDrawing = true;
        setSigPlaceholderVisible(false);
        const pos = getPos(e);
        sigCtx.beginPath();
        sigCtx.moveTo(pos.x, pos.y);
    });

    sigCanvas.addEventListener('mousemove', (e) => {
        if (!isDrawing) return;
        const pos = getPos(e);
        sigCtx.lineTo(pos.x, pos.y);
        sigCtx.stroke();
    });

    window.addEventListener('mouseup', finishStroke);

    sigCanvas.addEventListener('touchstart', (e) => {
        e.preventDefault();
        isDrawing = true;
        setSigPlaceholderVisible(false);
        const pos = getPos(e);
        sigCtx.beginPath();
        sigCtx.moveTo(pos.x, pos.y);
    }, { passive: false });

    sigCanvas.addEventListener('touchmove', (e) => {
        e.preventDefault();
        if (!isDrawing) return;
        const pos = getPos(e);
        sigCtx.lineTo(pos.x, pos.y);
        sigCtx.stroke();
    }, { passive: false });

    sigCanvas.addEventListener('touchend', (e) => {
        e.preventDefault();
        finishStroke();
    }, { passive: false });
}

function resizeSignatureCanvas() {
    if (!sigCanvas || !sigCtx) return;
    const rect = sigCanvas.getBoundingClientRect();
    if (rect.width > 0 && Math.abs(sigCanvas.width - Math.round(rect.width)) > 2) {
        const currentData = touchSignatureDataUrl || byId('signatureUrlInput')?.value;
        sigCanvas.width = Math.round(rect.width);
        sigCanvas.height = 165;
        sigCtx.strokeStyle = '#0f172a';
        sigCtx.lineWidth = 2.5;
        sigCtx.lineCap = 'round';
        sigCtx.lineJoin = 'round';
        if (currentData && currentData.startsWith('data:')) {
            const img = new Image();
            img.onload = () => {
                sigCtx.drawImage(img, 0, 0, sigCanvas.width, sigCanvas.height);
                setSigPlaceholderVisible(false);
            };
            img.src = currentData;
        }
    }
}

function switchVerificationMode(mode) {
    verificationMode = mode;
    const isTouch = mode === 'touch';

    byId('tabSignTouch').classList.toggle('is-active', isTouch);
    byId('tabSignStamp').classList.toggle('is-active', !isTouch);
    byId('tabSignTouch').setAttribute('aria-selected', String(isTouch));
    byId('tabSignStamp').setAttribute('aria-selected', String(!isTouch));
    byId('panelSignTouch').style.display = isTouch ? 'block' : 'none';
    byId('panelSignStamp').style.display = isTouch ? 'none' : 'block';

    byId('signatureUrlInput').value = (isTouch ? touchSignatureDataUrl : stampSignatureDataUrl) || '';
    if (isTouch) setTimeout(resizeSignatureCanvas, 50);
    saveActualDraft();
}

function setSigPlaceholderVisible(visible) {
    const el = byId('sigPlaceholder');
    if (el) el.style.opacity = visible ? '1' : '0';
}

function clearSignature() {
    if (!sigCanvas || !sigCtx) return;
    sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
    touchSignatureDataUrl = '';
    if (verificationMode === 'touch') {
        byId('signatureUrlInput').value = '';
    }
    setSigPlaceholderVisible(true);
    saveActualDraft();
}

function clearPhoto() {
    byId('photoUrlInput').value = '';
    byId('photoPreviewContainer').style.display = 'none';
    byId('photoPreviewImg').src = '';
    byId('photoFileName').innerText = '';
    const fileInput = byId('photoFileInput');
    if (fileInput) fileInput.value = '';
    saveActualDraft();
}

async function handleStampFile(input) {
    if (!input.files || !input.files[0]) return;

    try {
        const file = input.files[0];
        const dataUrl = await fileToEvidenceDataUrl(file, 700);
        stampSignatureDataUrl = dataUrl;
        byId('signatureUrlInput').value = dataUrl;
        byId('stampPreviewImg').src = dataUrl;
        byId('stampFileName').innerText = file.name;
        byId('stampPreviewContainer').style.display = 'flex';
        saveActualDraft();
        window.showToast('Stempel disimpan.', 'success');
    } catch (error) {
        input.value = '';
        window.showToast(error.message || 'Gagal memproses foto stempel/paraf.', 'danger');
    }
}

function clearStampFile() {
    const fileInput = byId('stampFileInput');
    if (fileInput) fileInput.value = '';
    byId('stampPreviewImg').src = '';
    byId('stampFileName').innerText = '';
    byId('stampPreviewContainer').style.display = 'none';
    stampSignatureDataUrl = '';
    if (verificationMode === 'stamp') {
        byId('signatureUrlInput').value = '';
    }
    saveActualDraft();
}

async function readFileAsDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (event) => resolve(event.target.result);
        reader.onerror = () => reject(new Error('Gagal membaca file gambar.'));
        reader.readAsDataURL(file);
    });
}

function loadImage(dataUrl) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error('File gambar tidak valid.'));
        image.src = dataUrl;
    });
}

function drawImageToCanvas(image, maxDimension) {
    const ratio = Math.min(1, maxDimension / Math.max(image.width, image.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(image.width * ratio));
    canvas.height = Math.max(1, Math.round(image.height * ratio));
    canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
    return canvas;
}

function canvasToEvidenceDataUrl(canvas, initialQuality = 0.72) {
    let quality = initialQuality;
    let dataUrl = canvas.toDataURL('image/jpeg', quality);

    while (dataUrl.length > 64000 && quality > 0.38) {
        quality -= 0.1;
        dataUrl = canvas.toDataURL('image/jpeg', quality);
    }

    while (dataUrl.length > 64000 && canvas.width > 360 && canvas.height > 360) {
        const nextCanvas = document.createElement('canvas');
        nextCanvas.width = Math.round(canvas.width * 0.8);
        nextCanvas.height = Math.round(canvas.height * 0.8);
        nextCanvas.getContext('2d').drawImage(canvas, 0, 0, nextCanvas.width, nextCanvas.height);
        canvas = nextCanvas;
        dataUrl = canvas.toDataURL('image/jpeg', 0.45);
    }

    if (dataUrl.length > 64000) {
        throw new Error('Ukuran gambar masih terlalu besar. Gunakan foto yang lebih kecil atau crop lebih dekat.');
    }

    return dataUrl;
}

async function fileToEvidenceDataUrl(file, maxDimension = 900) {
    if (!file || !file.type.startsWith('image/')) {
        throw new Error('File harus berupa gambar.');
    }

    const rawDataUrl = await readFileAsDataUrl(file);
    const image = await loadImage(rawDataUrl);
    const canvas = drawImageToCanvas(image, maxDimension);

    return canvasToEvidenceDataUrl(canvas);
}

async function toggleLiveCamera() {
    if (cameraStream) {
        stopLiveCamera();
        return;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        window.showToast('Browser Anda tidak mendukung akses kamera langsung.', 'warning');
        return;
    }

    const wrap = byId('cameraViewfinderWrap');
    const btnText = byId('liveCameraBtnText');
    try {
        btnText.innerText = 'Menghubungkan...';
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } }
        });
        const video = byId('cameraVideo');
        video.srcObject = cameraStream;
        wrap.style.display = 'block';
        btnText.innerText = 'Tutup Kamera';
    } catch (err) {
        console.warn('Camera access denied or unavailable:', err);
        window.showToast('Kamera tidak dapat diakses atau izin ditolak. Silakan gunakan berkas foto.', 'warning');
        stopLiveCamera();
    }
}

function stopLiveCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    const wrap = byId('cameraViewfinderWrap');
    if (wrap) wrap.style.display = 'none';
    const btnText = byId('liveCameraBtnText');
    if (btnText) btnText.innerText = 'Buka Kamera';
    const video = byId('cameraVideo');
    if (video) video.srcObject = null;
}

async function capturePhotoFromCamera() {
    const video = byId('cameraVideo');
    if (!video || !cameraStream) return;

    const sourceCanvas = document.createElement('canvas');
    sourceCanvas.width = video.videoWidth || 640;
    sourceCanvas.height = video.videoHeight || 480;
    sourceCanvas.getContext('2d').drawImage(video, 0, 0, sourceCanvas.width, sourceCanvas.height);

    try {
        const canvas = drawImageToCanvas(sourceCanvas, 900);
        const dataUrl = canvasToEvidenceDataUrl(canvas);
        byId('photoUrlInput').value = dataUrl;
        byId('photoPreviewImg').src = dataUrl;
        byId('photoFileName').innerText = 'kamera-langsung-' + Date.now() + '.jpg';
        byId('photoPreviewContainer').style.display = 'flex';

        stopLiveCamera();
        saveActualDraft();
        window.showToast('Foto disimpan.', 'success');
    } catch (error) {
        window.showToast(error.message || 'Gagal memproses foto kamera.', 'danger');
    }
}

async function handlePhotoFile(input) {
    if (!input.files || !input.files[0]) return;

    try {
        const file = input.files[0];
        const dataUrl = await fileToEvidenceDataUrl(file);
        byId('photoUrlInput').value = dataUrl;
        byId('photoPreviewImg').src = dataUrl;
        byId('photoFileName').innerText = file.name;
        byId('photoPreviewContainer').style.display = 'flex';
        saveActualDraft();
    } catch (error) {
        input.value = '';
        window.showToast(error.message || 'Gagal memproses file foto.', 'danger');
    }
}

// Menyinkronkan teks status bukti dengan nilai input tersembunyi yang menjadi sumber payload.
function refreshEvidenceStatus() {
    const hasPhoto = !!byId('photoUrlInput')?.value;
    const hasSignature = !!byId('signatureUrlInput')?.value;
    const photoText = byId('photoStatusText');
    if (photoText) photoText.innerText = hasPhoto ? 'Foto terpilih' : 'Belum ada foto';
    const badge = byId('sigBadgeRecorded');
    const idle = byId('sigStatusText');
    if (badge) badge.style.display = hasSignature ? 'inline' : 'none';
    if (idle) idle.style.display = hasSignature ? 'none' : 'inline';
    if (badge && hasSignature) {
        badge.innerText = verificationMode === 'stamp' ? 'Stempel / resep terpilih' : 'Tanda tangan tersimpan';
    }
}

function updateProductCount() {
    const el = byId('productCount');
    if (el) el.innerText = `${selectedProductIds().length} produk dipilih`;
}

/* ------------------------------------------------------------------ */
/* Draft lokal browser (perilaku existing, tidak diklaim sebagai simpan) */
/* ------------------------------------------------------------------ */

function markActualDirty(event) {
    if (isPreparingActualForm) return;
    if (event?.target?.id === 'productSearchInput') return;
    actualFormDirty = true;
}

function saveActualDraft() {
    refreshEvidenceStatus();
    updateProductCount();
    if (isPreparingActualForm) return;
    actualFormDirty = true;

    const planId = byId('actualPlanId')?.value;
    const sessionId = byId('actualSessionId')?.value;
    if (sessionId) return;

    const docId = byId('actualDoctorSelect')?.value || '';
    const checkIn = byId('checkInInput')?.value || '';
    const checkOut = byId('checkOutInput')?.value || '';
    const reason = byId('visitReasonInput')?.value || '';
    const productIds = selectedProductIds();
    const photoUrl = byId('photoUrlInput')?.value || '';
    const signatureUrl = byId('signatureUrlInput')?.value || '';

    if (!docId && !photoUrl && !signatureUrl && productIds.length === 0 && !reason) {
        return;
    }

    const draft = {
        plan_id: planId || null,
        doctor_id: docId,
        visit_date: byId('actualDateInput')?.value || todayString(),
        check_in: checkIn,
        check_out: checkOut,
        visit_reason: reason,
        product_ids: productIds,
        photo_url: photoUrl,
        signature_url: signatureUrl
    };

    try {
        localStorage.setItem(getDraftStorageKey(), JSON.stringify(draft));
    } catch (e) {
        // Penyimpanan lokal penuh/diblokir: laporan tetap bisa dikirim, hanya draf lokal yang tidak ada.
    }
}

function getDraftStorageKey() {
    const userId = pageEl?.dataset.currentUserId || "guest";
    return `msf_actual_draft_${userId}`;
}

function clearActualDraft(showNotice = false) {
    localStorage.removeItem(getDraftStorageKey());
    if (showNotice) {
        window.showToast('Isian formulir telah dikosongkan.', 'info');
    }
}

function resetActualForm() {
    isPreparingActualForm = true;
    clearActualDraft(false);
    clearFieldErrors();
    hideActualModalError();

    const hasLockedDoctor = !!(byId('actualPlanId').value || byId('actualSessionId').value);
    if (!hasLockedDoctor) {
        const docSel = byId('actualDoctorSelect');
        if (docSel) docSel.selectedIndex = 0;
    }

    const dateInput = byId('actualDateInput');
    if (dateInput) {
        dateInput.value = lockedPlanDate || todayString();
        syncActualDateDisplay();
    }

    byId('checkInInput').value = '08.30';
    byId('checkOutInput').value = '09.00';
    byId('visitReasonInput').value = '';
    document.querySelectorAll('#productPicker input[name="product_ids"]').forEach(input => {
        input.checked = false;
    });
    byId('photoUrlInput').value = '';
    byId('photoPreviewContainer').style.display = 'none';
    byId('photoPreviewImg').src = '';
    byId('photoFileName').innerText = '';
    const photoFile = byId('photoFileInput');
    if (photoFile) photoFile.value = '';
    clearSignature();
    clearStampFile();
    currentActualStep = 1;
    renderStepView();
    refreshEvidenceStatus();
    updateProductCount();
    isPreparingActualForm = false;
    actualFormDirty = false;

    window.showToast('Form dikosongkan.', 'info');
}

function restoreActualDraft(planId) {
    const raw = localStorage.getItem(getDraftStorageKey());
    if (!raw) return;
    try {
        const draft = JSON.parse(raw);
        if (planId && draft.plan_id && String(draft.plan_id) !== String(planId)) return;
        if (!planId && draft.plan_id) return;

        if (draft.doctor_id && !planId) {
            const docSel = byId('actualDoctorSelect');
            if (docSel) docSel.value = draft.doctor_id;
        }
        if (draft.visit_date && !lockedPlanDate) {
            byId('actualDateInput').value = draft.visit_date;
        }
        syncActualDateDisplay();
        if (draft.check_in) byId('checkInInput').value = draft.check_in;
        if (draft.check_out) byId('checkOutInput').value = draft.check_out;
        if (draft.visit_reason) byId('visitReasonInput').value = draft.visit_reason;

        if (draft.product_ids && Array.isArray(draft.product_ids)) {
            document.querySelectorAll('#productPicker input[name="product_ids"]').forEach(input => {
                input.checked = draft.product_ids.includes(parseInt(input.value));
            });
        }

        if (draft.photo_url) {
            byId('photoUrlInput').value = draft.photo_url;
            byId('photoPreviewImg').src = draft.photo_url;
            byId('photoFileName').innerText = 'foto-terpilih.jpg';
            byId('photoPreviewContainer').style.display = 'flex';
        }

        if (draft.signature_url) {
            byId('signatureUrlInput').value = draft.signature_url;
            touchSignatureDataUrl = draft.signature_url;
            const img = new Image();
            img.onload = () => {
                if (sigCtx && sigCanvas) {
                    sigCtx.drawImage(img, 0, 0, sigCanvas.width, sigCanvas.height);
                    setSigPlaceholderVisible(false);
                }
            };
            img.src = draft.signature_url;
        }
    } catch (e) {
        console.warn('Failed to parse draft:', e);
    }
}

function showActualAlert(message, type = 'success') {
    window.showToast(message, type);
}

/* ------------------------------------------------------------------ */
/* Loader data                                                         */
/* ------------------------------------------------------------------ */

async function loadDoctors() {
    const result = await callApi('/api/doctors');
    if (result.ok && Array.isArray(result.body.data)) {
        doctors = result.body.data;
    } else {
        doctors = [];
        showActualAlert('Daftar dokter gagal dimuat. Muat ulang halaman untuk mencoba lagi.', 'danger');
    }
    renderDoctorSelect();
}

async function loadProducts() {
    const result = await callApi('/api/products');
    productsLoadFailed = !(result.ok && Array.isArray(result.body.data));
    products = productsLoadFailed ? [] : result.body.data;
    renderProductPicker();
}

function renderLoadError(emptyId, message, target) {
    const el = byId(emptyId);
    if (!el) return;
    if (el.dataset.defaultHtml === undefined) el.dataset.defaultHtml = el.innerHTML;
    el.innerHTML = `
        <h4>Data gagal dimuat</h4>
        <p>${escapeHtml(message)}</p>
        <button type="button" class="ca-btn ca-btn-soft ca-btn-sm ca-retry-btn" data-action="retry-load" data-target="${escapeHtml(target)}">Coba Lagi</button>
    `;
    el.style.display = 'block';
}

function restoreEmptyState(emptyId) {
    const el = byId(emptyId);
    if (el && el.dataset.defaultHtml !== undefined) el.innerHTML = el.dataset.defaultHtml;
}

function retryLoad(target) {
    if (target === 'plans') loadTodayPlans();
    else if (target === 'actuals') loadActuals();
    else if (target === 'products') loadProducts();
}

function rememberPlans(list) {
    list.forEach((plan) => planLookup.set(Number(plan.id), plan));
}

async function loadTodayPlans() {
    const loading = byId('todayPlansLoading');
    if (loading) {
        loading.innerHTML = window.renderTableSkeleton(2);
        loading.style.display = 'block';
    }

    const result = await callApi(`/api/call-plans?date=${todayString()}&status=scheduled`);
    if (loading) loading.style.display = 'none';

    restoreEmptyState('todayPlansEmpty');
    if (result.ok && Array.isArray(result.body.data)) {
        todayPlans = result.body.data;
        rememberPlans(todayPlans);
        todayPlansLoaded = true;
        renderTodayPlans();
    } else {
        todayPlans = [];
        byId('todayPlansList').style.display = 'none';
        renderLoadError('todayPlansEmpty', describeApiError(result, 'Jadwal hari ini belum dapat dimuat.').message, 'plans');
    }
}

async function ensurePlanForReport(planId) {
    if (planLookup.has(planId)) return planLookup.get(planId);
    const result = await callApi('/api/call-plans?status=scheduled');
    if (result.ok && Array.isArray(result.body.data)) rememberPlans(result.body.data);
    return planLookup.get(planId) || null;
}

async function loadExpiredPlans() {
    const section = byId('expiredPlansSection');
    const list = byId('expiredPlansList');
    if (!section || !list) return;

    const result = await callApi('/api/call-plans?status=expired');
    if (!result.ok || !Array.isArray(result.body.data)) {
        section.style.display = 'none';
        return;
    }

    const cutoff = new Date();
    cutoff.setDate(cutoff.getDate() - EXPIRED_LOOKBACK_DAYS);
    const cutoffString = localDateString(cutoff);
    const recent = result.body.data
        .filter((plan) => plan.plan_date >= cutoffString)
        .sort((a, b) => String(b.plan_date).localeCompare(String(a.plan_date)))
        .slice(0, EXPIRED_MAX_ITEMS);

    if (!recent.length) {
        section.style.display = 'none';
        return;
    }

    list.innerHTML = recent.map(expiredCardHtml).join('');
    section.style.display = 'flex';
}

async function loadActuals() {
    const loading = byId('actualsLoading');
    if (loading) {
        loading.innerHTML = window.renderTableSkeleton(3);
        loading.style.display = 'block';
    }

    let url = '/api/call-actuals';
    if (currentUserRole !== 'mr') {
        const params = new URLSearchParams();
        const mrId = byId('filterManagerMr')?.value;
        const month = byId('filterManagerMonth')?.value;
        const visitType = byId('filterManagerVisitType')?.value;
        if (mrId) params.set('mr_id', mrId);
        if (month) params.set('month', month);
        if (visitType) params.set('visit_type', visitType);
        const queryStr = params.toString();
        if (queryStr) url += '?' + queryStr;
    }

    const result = await callApi(url);
    if (loading) loading.style.display = 'none';
    restoreEmptyState('actualsEmpty');

    if (result.ok && Array.isArray(result.body.data)) {
        actuals = result.body.data;
        if (currentUserRole !== 'mr') updateManagerMetrics();
        renderActuals();
        return;
    }

    actuals = [];
    if (currentUserRole !== 'mr') updateManagerMetrics();
    ['actualsTableWrap', 'actualsList'].forEach((id) => {
        const el = byId(id);
        if (el) el.style.display = 'none';
    });
    renderLoadError('actualsEmpty', describeApiError(result, 'Riwayat kunjungan belum dapat dimuat.').message, 'actuals');
}

function updateManagerMetrics() {
    const total = actuals.length;
    const planned = actuals.filter(a => a.visit_type === 'planned').length;
    const unplanned = total - planned;

    const totalEl = byId('metricTotalActuals');
    const plannedEl = byId('metricPlannedActuals');
    const unplannedEl = byId('metricUnplannedActuals');
    const plannedPctEl = byId('metricPlannedPercent');
    const unplannedPctEl = byId('metricUnplannedPercent');

    if (totalEl) totalEl.innerText = total;
    if (plannedEl) plannedEl.innerText = planned;
    if (unplannedEl) unplannedEl.innerText = unplanned;

    const plannedPct = total > 0 ? Math.round((planned / total) * 100) : 0;
    const unplannedPct = total > 0 ? Math.round((unplanned / total) * 100) : 0;

    if (plannedPctEl) plannedPctEl.innerText = plannedPct + '%';
    if (unplannedPctEl) unplannedPctEl.innerText = unplannedPct + '%';
}

function resetManagerFilters() {
    const mrSelect = byId('filterManagerMr');
    const monthInput = byId('filterManagerMonth');
    const visitTypeSelect = byId('filterManagerVisitType');
    if (mrSelect) mrSelect.value = '';
    if (monthInput) monthInput.value = todayString().slice(0, 7);
    if (visitTypeSelect) visitTypeSelect.value = '';
    loadActuals();
}

/* ------------------------------------------------------------------ */
/* Render: dokter, produk, jadwal, riwayat                              */
/* ------------------------------------------------------------------ */

function renderDoctorSelect(selectedDoctorId = '') {
    const select = byId('actualDoctorSelect');
    if (!select) return;
    select.innerHTML = '<option value="">-- Pilih Dokter --</option>' + doctors.map((doctor) => `
        <option value="${doctor.id}" ${String(doctor.id) === String(selectedDoctorId) ? 'selected' : ''}>
            ${escapeHtml(doctor.name)} (${escapeHtml(doctor.specialization || 'Umum')})
        </option>
    `).join('');
}

function getProductDosageForm(product) {
    if (product.dosage_form && !product.dosage_form.includes('Sediaan')) {
        return product.dosage_form;
    }
    const cat = (product.category || '').toLowerCase();
    const name = (product.name || '').toLowerCase();

    if (cat.includes('sirup') || name.includes('expectorant') || name.includes('syrup') || name.includes('60ml') || name.includes('ml')) return 'Sirup';
    if (cat.includes('krim') || name.includes('krim') || name.includes('cream') || name.includes('salep') || name.includes('10g')) return 'Krim';
    if (cat.includes('kapsul') || name.includes('kapsul') || name.includes('capsule')) return 'Kapsul';
    if (cat.includes('kaplet') || name.includes('kaplet') || name.includes('clav')) return 'Kaplet';
    if (cat.includes('tablet') || name.includes('tropic') || name.includes('tablet')) return 'Tablet';
    if (cat.includes('injeksi') || name.includes('inj')) return 'Injeksi';
    return 'Tablet';
}

function getProductDescription(product) {
    const rawCat = (product.category || '').trim();
    if (!rawCat) return 'Obat Farmasi Mersifarma';

    let desc = rawCat.replace(/^(Tablet|Kapsul|Sirup|Krim|Kaplet|Sediaan)\s+/i, '');

    if (product.name && product.name.includes('Mersicort')) {
        return 'Krim Anti-inflamasi Topikal';
    }
    if (product.name && product.name.includes('Mersiclav')) {
        return 'Antibiotik Amoxicillin-Clavulanate';
    }
    if (product.name && product.name.includes('Mersidryl')) {
        return 'Ekspektoran Batuk & Flu';
    }
    if (product.name && product.name.includes('Mersitropic')) {
        return 'Neurotropik & Vitamin Saraf';
    }
    if (product.name && product.name.includes('Mersizol')) {
        return 'Anti-tukak Lambung (Omeprazole PPI)';
    }
    return desc || rawCat;
}

function renderProductPicker() {
    const picker = byId('productPicker');
    if (!picker) return;
    if (productsLoadFailed) {
        picker.innerHTML = `
            <p class="ca-field-note">Produk gagal dimuat.</p>
            <button type="button" class="ca-btn ca-btn-soft ca-btn-sm" data-action="retry-load" data-target="products">Muat Ulang Produk</button>
        `;
        return;
    }
    if (!products.length) {
        picker.innerHTML = '<p class="ca-field-note">Produk belum tersedia.</p>';
        return;
    }
    picker.innerHTML = products.map((product) => `
        <label class="ca-product">
            <input type="checkbox" name="product_ids" value="${product.id}">
            <div class="ca-product-body">
                <div class="ca-product-name">
                    <strong>${escapeHtml(product.name)}</strong>
                    <span class="ca-product-form">${escapeHtml(getProductDosageForm(product))}</span>
                </div>
                <div class="ca-product-desc">${escapeHtml(getProductDescription(product))}</div>
            </div>
        </label>
    `).join('');
}

function filterProductPicker(keyword) {
    const term = String(keyword || '').toLowerCase().trim();
    document.querySelectorAll('#productPicker label').forEach(label => {
        label.style.display = label.innerText.toLowerCase().includes(term) ? '' : 'none';
    });
}

function planCardHtml(plan, index) {
    const isThisPlanActive = !!activeSession && Number(activeSession.plan_id) === Number(plan.id);
    const hasOtherSession = !!activeSession && !isThisPlanActive;

    const badge = isThisPlanActive
        ? statusBadge(SESSION_STATUS, activeSession.check_out_at ? 'checked_out' : 'in_progress', 'Status sesi kunjungan')
        : statusBadge(PLAN_STATUS, plan.status || 'scheduled', 'Status jadwal');

    let actionsHtml;
    if (isThisPlanActive) {
        actionsHtml = '<p class="ca-note">Sesi untuk jadwal ini sedang berjalan. Lanjutkan dari kartu sesi di bagian atas halaman.</p>';
    } else {
        const startDisabled = hasOtherSession || isStartingSession ? 'disabled' : '';
        actionsHtml = `
            ${hasOtherSession ? '<p class="ca-note">Selesaikan atau batalkan sesi yang sedang berlangsung untuk memulai kunjungan lain.</p>' : ''}
            <div class="ca-actions">
                <button type="button" class="ca-btn ca-btn-primary" data-action="start-visit-session" data-plan-id="${plan.id}" data-doctor-id="${plan.doctor_id}" ${startDisabled}>
                    Mulai Kunjungan
                </button>
                <button type="button" class="ca-btn ca-btn-outline" data-action="open-planned-actual" data-plan-id="${plan.id}">
                    Isi Laporan Manual
                </button>
            </div>
        `;
    }

    return `
        <article class="ca-card ${index === 0 ? 'ca-card-priority' : ''}">
            <div class="ca-card-top">
                <div>
                    <h3 class="ca-card-title">${escapeHtml(plan.doctor_name || 'Target Dokter')}</h3>
                    <p class="ca-card-sub">${escapeHtml(plan.doctor_specialization || 'Spesialis Umum')}</p>
                </div>
                ${badge}
            </div>
            <div class="ca-meta">
                <span><i class="bi bi-clock" aria-hidden="true"></i>${escapeHtml(formatWaktuIndonesia(plan.planned_time))}</span>
            </div>
            <p class="ca-address"><i class="bi bi-geo-alt" aria-hidden="true"></i><span>${escapeHtml(plan.doctor_address || '-')}</span></p>
            ${actionsHtml}
        </article>
    `;
}

function expiredCardHtml(plan) {
    return `
        <article class="ca-card">
            <div class="ca-card-top">
                <div>
                    <h3 class="ca-card-title">${escapeHtml(plan.doctor_name || 'Target Dokter')}</h3>
                    <p class="ca-card-sub">Rencana ${escapeHtml(formatVisitDateIndonesian(plan.plan_date))}</p>
                </div>
                ${statusBadge(PLAN_STATUS, 'expired', 'Status jadwal')}
            </div>
            <p class="ca-note">Jadwal ini sudah terlewat dan tidak dapat diisi sebagai laporan terencana. Buat jadwal baru, atau catat sebagai kunjungan di luar rencana bila memang terjadi.</p>
            <div class="ca-actions">
                <a class="ca-btn ca-btn-soft" href="${escapeHtml(callPlansUrl)}">Buat Jadwal Baru</a>
                <button type="button" class="ca-btn ca-btn-outline" data-action="open-unplanned-actual" data-doctor-id="${plan.doctor_id}">
                    Catat di Luar Rencana
                </button>
            </div>
        </article>
    `;
}

function renderTodayPlans() {
    const list = byId('todayPlansList');
    const empty = byId('todayPlansEmpty');
    if (!list || !empty) return;

    if (!todayPlans.length) {
        list.innerHTML = '';
        list.style.display = 'none';
        empty.style.display = 'block';
        return;
    }
    empty.style.display = 'none';
    list.style.display = 'flex';
    list.innerHTML = todayPlans.map(planCardHtml).join('');
}

function renderActuals() {
    const empty = byId('actualsEmpty');
    const isMr = currentUserRole === 'mr';
    const container = byId(isMr ? 'actualsList' : 'actualsTableWrap');
    if (!container) return;

    if (!actuals.length) {
        container.style.display = 'none';
        if (empty) empty.style.display = 'block';
        return;
    }
    if (empty) empty.style.display = 'none';

    if (isMr) {
        container.style.display = 'flex';
        container.innerHTML = actuals.map(actualCardHtml).join('');
        return;
    }

    container.style.display = 'block';
    byId('actualsTableBody').innerHTML = actuals.map(actualRowHtml).join('');
}

function actualTimeRange(actual) {
    const checkInVal = actual.check_in || actual.check_in_at;
    const checkOutVal = actual.check_out || actual.check_out_at;
    if (checkInVal && checkOutVal) {
        return `${formatWaktuIndonesia(checkInVal).replace(' WIB', '')} - ${formatWaktuIndonesia(checkOutVal)}`;
    }
    return checkInVal ? formatWaktuIndonesia(checkInVal) : '';
}

function actualCardHtml(actual) {
    const timeRange = actualTimeRange(actual);
    const products = (actual.products || []).map((p) => `<span class="ca-product-chip">${escapeHtml(p.name)}</span>`).join('');

    return `
        <article class="ca-card">
            <div class="ca-card-top">
                <div>
                    <h3 class="ca-card-title">${escapeHtml(actual.doctor_name || 'Dokter')}</h3>
                    <p class="ca-card-sub">${escapeHtml(actual.doctor_specialization || 'Spesialis Umum')}</p>
                </div>
                ${statusBadge(ACTUAL_TYPE, actual.visit_type, 'Kategori kunjungan')}
            </div>
            <div class="ca-meta">
                <span><i class="bi bi-calendar3" aria-hidden="true"></i>${escapeHtml(formatVisitDateIndonesian(actual.visit_date))}</span>
                ${timeRange ? `<span><i class="bi bi-clock" aria-hidden="true"></i>${escapeHtml(timeRange)}</span>` : ''}
            </div>
            ${actual.doctor_address ? `<p class="ca-address"><i class="bi bi-geo-alt" aria-hidden="true"></i><span>${escapeHtml(actual.doctor_address)}</span></p>` : ''}
            ${products ? `<div class="ca-products">${products}</div>` : ''}
            ${actual.visit_reason ? `<p class="ca-reason">"${escapeHtml(actual.visit_reason)}"</p>` : ''}
            <div class="ca-actions">
                <button type="button" class="ca-btn ca-btn-soft" data-action="show-evidence" data-actual-id="${actual.id}">Lihat Bukti</button>
            </div>
        </article>
    `;
}

function actualRowHtml(actual) {
    const timeRange = actualTimeRange(actual);
    const timeHtml = timeRange
        ? `<div class="ca-table-subtext">${escapeHtml(timeRange)}</div>`
        : '';
    const doctorAddress = actual.doctor_address
        ? `<div class="ca-table-address">${escapeHtml(actual.doctor_address)}</div>`
        : '';
    const visitReason = actual.visit_reason
        ? `<div class="ca-table-reason">"${escapeHtml(actual.visit_reason)}"</div>`
        : '';
    const products = (actual.products || []).map((p) => escapeHtml(p.name)).join(', ') || '-';

    return `
        <tr>
            <td class="ca-table-nowrap">
                <strong class="ca-table-date">${escapeHtml(formatVisitDateIndonesian(actual.visit_date))}</strong>
                ${timeHtml}
            </td>
            <td class="ca-table-nowrap">
                <div class="ca-table-name">${escapeHtml(actual.mr_name || 'MR')}</div>
                <span class="ca-table-role">Medical Rep</span>
            </td>
            <td>
                <strong class="ca-table-doctor">${escapeHtml(actual.doctor_name || 'Dokter')}</strong>
                <div class="ca-table-specialty">${escapeHtml(actual.doctor_specialization || 'Spesialis Umum')}</div>
                ${doctorAddress}
            </td>
            <td>
                ${statusBadge(ACTUAL_TYPE, actual.visit_type, 'Kategori kunjungan')}
                ${visitReason}
            </td>
            <td>
                <div class="ca-table-products">${products}</div>
            </td>
            <td class="ca-table-action">
                <button type="button" class="btn btn-outline btn-sm ca-table-evidence" data-action="show-evidence" data-actual-id="${actual.id}">
                    Lihat Bukti
                </button>
            </td>
        </tr>
    `;
}

/* ------------------------------------------------------------------ */
/* Form Progressive Steps (3 langkah)                                  */
/* ------------------------------------------------------------------ */

const STEP_NAMES = ['Informasi & Waktu', 'Bukti & Produk', 'Konfirmasi'];
const NEXT_LABELS = { 1: 'Lanjut ke Bukti & Produk', 2: 'Lanjut ke Konfirmasi' };

function actualMode() {
    if (byId('actualSessionId').value) return 'session';
    if (byId('actualPlanId').value) return 'plan';
    return 'direct';
}

function clearFieldErrors() {
    document.querySelectorAll('[data-error-for]').forEach((el) => { el.innerText = ''; });
}

function setFieldError(name, message) {
    const el = document.querySelector(`[data-error-for="${name}"]`);
    if (el) el.innerText = message || '';
}

function showActualModalError(message) {
    const errorEl = byId('actualModalError');
    errorEl.innerText = message;
    errorEl.style.display = 'block';
    document.querySelector('.ca-sheet-body')?.scrollTo({ top: 0 });
}

function hideActualModalError() {
    const errorEl = byId('actualModalError');
    if (errorEl) errorEl.style.display = 'none';
}

function normalizeTimeInput(value) {
    let str = String(value || "").trim().replace(".", ":");
    if (/^\d:\d{2}/.test(str)) str = "0" + str;
    return str;
}

// Frontend hanya membantu pengguna; aturan final (mis. waktu di masa depan) tetap di backend.
function validateStep(step) {
    clearFieldErrors();
    hideActualModalError();

    if (step === 1) {
        let valid = true;
        if (!byId('actualDoctorSelect').value) {
            setFieldError('doctor', 'Pilih dokter terlebih dahulu.');
            valid = false;
        }
        if (actualMode() === 'session') return valid;

        if (!byId('actualDateInput').value) {
            setFieldError('date', 'Tanggal kunjungan wajib diisi.');
            valid = false;
        }
        const checkIn = normalizeTimeInput(byId('checkInInput').value);
        const checkOut = normalizeTimeInput(byId('checkOutInput').value);
        const timePattern = /^([01]\d|2[0-3]):[0-5]\d$/;
        if (!timePattern.test(checkIn) || !timePattern.test(checkOut)) {
            setFieldError('time', 'Isi waktu mulai dan selesai dengan format 24 jam, contoh 08.30.');
            valid = false;
        } else if (checkOut <= checkIn) {
            setFieldError('time', 'Waktu selesai harus setelah waktu mulai.');
            valid = false;
        }
        return valid;
    }

    if (step === 2) {
        let valid = true;
        if (!byId('photoUrlInput').value.trim()) {
            setFieldError('evidence', 'Foto kunjungan wajib diisi.');
            valid = false;
        } else if (!byId('signatureUrlInput').value.trim()) {
            setFieldError('evidence', 'Tanda tangan atau stempel dokter wajib diisi.');
            valid = false;
        }
        if (!selectedProductIds().length) {
            setFieldError('products', 'Pilih minimal satu produk detailing.');
            valid = false;
        }
        return valid;
    }

    return true;
}

function goToActualStep(target) {
    if (!Number.isInteger(target) || target < 1 || target > 3) return;
    hideActualModalError();

    if (target > currentActualStep) {
        for (let step = currentActualStep; step < target; step++) {
            if (!validateStep(step)) {
                currentActualStep = step;
                renderStepView();
                return;
            }
        }
    }

    currentActualStep = target;
    if (target === 3) renderSummary();
    renderStepView();
}

function renderStepView() {
    [1, 2, 3].forEach((step) => {
        const section = byId(`stepSection${step}`);
        if (section) section.style.display = currentActualStep === step ? 'block' : 'none';

        const seg = byId(`stepIndicator${step}`);
        if (seg) {
            seg.classList.toggle('is-active', step === currentActualStep);
            seg.classList.toggle('is-done', step < currentActualStep);
            if (step === currentActualStep) seg.setAttribute('aria-current', 'step');
            else seg.removeAttribute('aria-current');
        }
    });

    byId('actualStepName').innerText = `${currentActualStep}. ${STEP_NAMES[currentActualStep - 1]}`;
    byId('actualStepCount').innerText = `Langkah ${currentActualStep} dari 3`;

    byId('actualPrevButton').innerText = currentActualStep === 1 ? 'Batal' : 'Kembali';
    const nextButton = byId('actualNextButton');
    nextButton.style.display = currentActualStep < 3 ? '' : 'none';
    nextButton.innerText = NEXT_LABELS[currentActualStep] || '';
    byId('actualSubmitButton').style.display = currentActualStep === 3 ? '' : 'none';

    document.querySelector('.ca-sheet-body')?.scrollTo({ top: 0 });
    if (currentActualStep === 2) {
        setTimeout(resizeSignatureCanvas, 60);
    }
}

function renderSummary() {
    const mode = actualMode();
    const doctorId = byId('actualDoctorSelect').value;
    const doctor = doctors.find((item) => String(item.id) === String(doctorId));
    const productNames = selectedProductIds()
        .map((id) => productNameById(id))
        .filter(Boolean)
        .join(', ');
    const reason = byId('visitReasonInput').value.trim();

    let when;
    if (mode === 'session') {
        when = byId('sessionTimeText').innerText + ' (server)';
    } else {
        const date = formatVisitDateIndonesian(byId('actualDateInput').value);
        when = `${date} · ${byId('checkInInput').value} – ${byId('checkOutInput').value} WIB (manual)`;
    }

    const methodLabel = { session: 'Visit Session', plan: 'Manual dari jadwal', direct: 'Kunjungan di luar rencana' }[mode];
    const rows = [
        ['Dokter', doctor ? doctor.name : '-'],
        ['Metode', methodLabel],
        ['Tanggal & waktu', when],
        ['Foto kunjungan', byId('photoUrlInput').value ? 'Terlampir' : 'Belum ada'],
        [verificationMode === 'stamp' ? 'Stempel / resep' : 'Tanda tangan', byId('signatureUrlInput').value ? 'Terlampir' : 'Belum ada'],
        ['Produk detailing', productNames || '-'],
    ];
    if (mode !== 'plan' && reason) rows.push(['Alasan', reason]);

    byId('actualSummary').innerHTML = rows.map(([label, value]) => `
        <div class="ca-summary-row"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value)}</strong></div>
    `).join('');
}

function productNameById(id) {
    const product = products.find((item) => Number(item.id) === Number(id));
    return product ? product.name : null;
}

function setLockedDoctorNote(text) {
    const note = byId('doctorLockNote');
    note.innerText = text || '';
    note.style.display = text ? 'block' : 'none';
}

function openActualModal(planId = null, fallbackDoctorId = null, sessionId = null) {
    isPreparingActualForm = true;
    actualFormDirty = false;
    clearFieldErrors();

    const plan = planId ? (planLookup.get(Number(planId)) || null) : null;
    const isSessionBacked = !!sessionId;
    lockedPlanDate = plan && !isSessionBacked ? String(plan.plan_date) : '';

    byId('actualSessionId').value = sessionId || '';
    byId('actualPlanId').value = planId || '';
    byId('actualDoctorSelect').disabled = !!(planId || isSessionBacked);

    const mode = actualMode();
    byId('actualModalTitle').innerText = 'Laporan Kunjungan';
    byId('actualModeLabel').innerText = {
        session: 'Visit Session · waktu dari server',
        plan: 'Dari jadwal · waktu diisi manual',
        direct: 'Kunjungan di luar rencana · waktu diisi manual',
    }[mode];
    setLockedDoctorNote({ session: 'Dokter mengikuti sesi kunjungan.', plan: 'Dokter mengikuti jadwal.', direct: '' }[mode]);

    byId('visitReasonWrap').style.display = planId ? 'none' : 'block';
    byId('manualTimeFields').style.display = isSessionBacked ? 'none' : 'block';
    byId('sessionTimeBox').style.display = isSessionBacked ? 'block' : 'none';
    byId('dateLockNote').style.display = lockedPlanDate ? 'block' : 'none';
    byId('actualDateInput').disabled = !!lockedPlanDate;
    byId('actualDateDisplay').classList.toggle('ca-input-clickable', !lockedPlanDate);
    byId('actualDateDisplay').style.background = lockedPlanDate ? '#f4f7fb' : '';

    byId('actualDateInput').value = lockedPlanDate || todayString();
    syncActualDateDisplay();
    byId('checkInInput').value = '08.30';
    byId('checkOutInput').value = '09.00';

    const docIdToSelect = (plan && plan.doctor_id) || fallbackDoctorId || '';
    renderDoctorSelect(docIdToSelect);
    renderProductPicker();
    byId('productSearchInput').value = '';

    byId('photoUrlInput').value = '';
    byId('photoPreviewContainer').style.display = 'none';
    byId('photoPreviewImg').src = '';
    byId('photoFileName').innerText = '';
    byId('photoFileInput').value = '';

    clearSignature();
    clearStampFile();
    switchVerificationMode('touch');

    byId('visitReasonInput').value = '';
    hideActualModalError();

    if (isSessionBacked && activeSession) {
        fillSessionTime();
    }

    currentActualStep = 1;
    renderStepView();

    byId('actualModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';

    if (!isSessionBacked) {
        restoreActualDraft(planId);
    }
    refreshEvidenceStatus();
    updateProductCount();
    isPreparingActualForm = false;
    document.querySelector('#actualModal .ca-back')?.focus();
}

function fillSessionTime() {
    const checkIn = new Date(activeSession.check_in_at);
    const checkOut = activeSession.check_out_at ? new Date(activeSession.check_out_at) : null;
    const dateLabel = formatVisitDateIndonesian(localDateString(checkIn));
    const range = checkOut
        ? `${formatWaktuIndonesia(checkIn).replace(' WIB', '')} – ${formatWaktuIndonesia(checkOut)}`
        : formatWaktuIndonesia(checkIn);
    byId('sessionTimeText').innerText = `${dateLabel} · ${range}`;
}

function requestCloseActualModal() {
    if (isSubmittingActual) return;
    if (actualFormDirty) {
        byId('leaveConfirmModal').style.display = 'flex';
        byId('leaveStayButton')?.focus();
        return;
    }
    closeActualModal();
}

function hideLeaveConfirm() {
    const modal = byId('leaveConfirmModal');
    if (modal) modal.style.display = 'none';
}

function closeActualModal() {
    stopLiveCamera();
    currentActualStep = 1;
    actualFormDirty = false;
    document.body.style.overflow = '';
    const modal = byId('actualModal');
    if (modal) modal.style.display = 'none';
}

function selectedProductIds() {
    return Array.from(document.querySelectorAll('#productPicker input[name="product_ids"]:checked'))
        .map((input) => parseInt(input.value))
        .filter((id) => Number.isInteger(id));
}

function buildActualPayload() {
    const planId = byId('actualPlanId').value;
    const sessionId = byId('actualSessionId').value;
    const payload = {
        photo_url: byId('photoUrlInput').value.trim(),
        signature_url: byId('signatureUrlInput').value.trim(),
        products: selectedProductIds(),
        visit_reason: planId ? null : (byId('visitReasonInput').value.trim() || null)
    };

    if (sessionId) {
        payload.visit_session_id = parseInt(sessionId);
        return payload;
    }

    payload.plan_id = planId ? parseInt(planId) : null;
    payload.doctor_id = parseInt(byId('actualDoctorSelect').value);
    payload.visit_date = byId('actualDateInput').value;
    payload.check_in = normalizeTimeInput(byId('checkInInput').value);
    payload.check_out = normalizeTimeInput(byId('checkOutInput').value);

    return payload;
}

function setSubmitting(isSubmitting) {
    const button = byId('actualSubmitButton');
    button.disabled = isSubmitting;
    button.innerText = isSubmitting ? 'Menyimpan...' : 'Simpan Laporan';
    ['actualPrevButton', 'actualNextButton'].forEach((id) => { byId(id).disabled = isSubmitting; });
}

// Setelah jaringan putus / 5xx hasil simpan belum pasti: cek riwayat milik sendiri sebelum menyarankan ulang.
async function findSavedActual(payload) {
    let doctorId = payload.doctor_id;
    let visitDate = payload.visit_date;
    if (payload.visit_session_id && activeSession) {
        doctorId = activeSession.doctor_id;
        visitDate = localDateString(new Date(activeSession.check_in_at));
    }
    if (!doctorId || !visitDate) return null;

    const result = await callApi('/api/call-actuals');
    if (!result.ok || !Array.isArray(result.body.data)) return null;
    return result.body.data.some((item) => Number(item.doctor_id) === Number(doctorId) && item.visit_date === visitDate);
}

async function onActualSaved(message) {
    clearActualDraft(false);
    actualFormDirty = false;
    closeActualModal();
    showActualAlert(message);
    await loadActiveSession();
    loadTodayPlans();
    loadExpiredPlans();
    loadActuals();
}

async function submitActual(event) {
    event.preventDefault();
    if (isSubmittingActual) return;

    if (currentActualStep < 3) {
        goToActualStep(currentActualStep + 1);
        return;
    }

    for (const step of [1, 2]) {
        if (!validateStep(step)) {
            currentActualStep = step;
            renderStepView();
            return;
        }
    }

    isSubmittingActual = true;
    setSubmitting(true);
    hideActualModalError();

    const payload = buildActualPayload();
    try {
        const result = await callApi('/api/call-actuals', { method: 'POST', body: JSON.stringify(payload) });

        if (result.ok) {
            await onActualSaved('Laporan disimpan.');
            return;
        }

        const unconfirmed = result.networkError || result.status >= 500 || !result.body;
        if (unconfirmed) {
            const saved = await findSavedActual(payload);
            if (saved === true) {
                await onActualSaved('Laporan sudah tercatat di server.');
                return;
            }
            showActualModalError(saved === false
                ? `${describeApiError(result, '').message} Laporan belum tercatat. Isian Anda masih ada, silakan coba lagi.`
                : `${describeApiError(result, '').message} Status penyimpanan belum dapat dikonfirmasi. Periksa Riwayat Kunjungan sebelum menyimpan ulang; isian Anda masih ada.`);
            return;
        }

        const info = describeApiError(result, 'Gagal menyimpan kunjungan.');
        Object.entries(info.fields).forEach(([name, message]) => setFieldError(name, message));
        if (info.step) {
            currentActualStep = info.step;
            renderStepView();
        }
        showActualModalError(info.message);
    } finally {
        isSubmittingActual = false;
        setSubmitting(false);
    }
}

/* ------------------------------------------------------------------ */
/* Visit Session                                                       */
/* ------------------------------------------------------------------ */

async function loadActiveSession() {
    const result = await callApi('/api/visit-sessions/active');
    activeSession = result.ok ? result.body.data : null;
    renderActiveSession();
}

function renderActiveSession() {
    const section = byId('activeSessionSection');
    if (!section) return;
    if (!activeSession) {
        section.style.display = 'none';
        if (todayPlansLoaded) renderTodayPlans();
        return;
    }

    section.style.display = 'block';
    byId('activeSessionDoctorName').innerText = activeSession.doctor_name || 'Target Dokter';
    byId('activeSessionDoctorDetail').innerText = `${activeSession.doctor_specialization || 'Spesialis Umum'} - ${activeSession.doctor_address || '-'}`;
    byId('activeSessionCheckInTime').innerText = formatWaktuIndonesia(new Date(activeSession.check_in_at));

    const checkoutWrap = byId('activeSessionCheckOutWrap');
    const btnCheckout = byId('btnSessionCheckout');
    const btnContinue = byId('btnSessionContinue');
    const hasCheckout = !!activeSession.check_out_at;

    const [label, cls] = SESSION_STATUS[hasCheckout ? 'checked_out' : 'in_progress'];
    const badge = byId('activeSessionBadge');
    badge.innerText = label;
    badge.className = `status-badge ${cls}`;

    if (hasCheckout) {
        byId('activeSessionCheckOutTime').innerText = formatWaktuIndonesia(new Date(activeSession.check_out_at));
    }
    checkoutWrap.style.display = hasCheckout ? 'inline' : 'none';
    btnCheckout.style.display = hasCheckout ? 'none' : '';
    btnContinue.style.display = hasCheckout ? '' : 'none';

    if (todayPlansLoaded) renderTodayPlans();
}

async function startVisitSession(planId, doctorId) {
    if (isStartingSession) return;
    isStartingSession = true;
    renderTodayPlans();

    try {
        const payload = planId ? { plan_id: parseInt(planId, 10) } : { doctor_id: parseInt(doctorId, 10) };
        const result = await callApi('/api/visit-sessions/start', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        if (result.ok) {
            showActualAlert('Kunjungan dimulai. Waktu check-in dicatat server.');
            await loadActiveSession();
        } else {
            showActualAlert(describeApiError(result, 'Gagal memulai sesi kunjungan.').message, 'danger');
        }
    } finally {
        isStartingSession = false;
        renderTodayPlans();
    }
}

async function handleSessionCheckout() {
    if (!activeSession) return;
    const btn = byId('btnSessionCheckout');
    if (btn.disabled) return;
    btn.disabled = true;
    btn.innerText = 'Mencatat...';

    try {
        const result = await callApi(`/api/visit-sessions/${activeSession.id}/checkout`, { method: 'POST' });
        if (result.ok) {
            showActualAlert('Kunjungan diakhiri. Lengkapi laporan.');
            await loadActiveSession();
            openActualModalFromSession();
        } else {
            showActualAlert(describeApiError(result, 'Gagal mencatat check-out.').message, 'danger');
        }
    } finally {
        btn.disabled = false;
        btn.innerText = 'Akhiri Kunjungan';
    }
}

function openActualModalFromSession() {
    if (!activeSession) return;
    openActualModal(activeSession.plan_id, activeSession.doctor_id, activeSession.id);
}

function openCancelSessionModal() {
    if (!activeSession) return;
    byId('cancelSessionId').value = activeSession.id;
    byId('cancelSessionDoctorName').innerText = activeSession.doctor_name || 'Target Dokter';
    byId('cancelSessionReasonInput').value = '';
    const errEl = byId('cancelSessionModalError');
    errEl.style.display = 'none';
    errEl.innerText = '';
    byId('cancelSessionModal').style.display = 'flex';
}

function closeCancelSessionModal() {
    const modal = byId('cancelSessionModal');
    if (modal) modal.style.display = 'none';
}

async function handleCancelSessionSubmit(e) {
    e.preventDefault();
    const sessionId = byId('cancelSessionId').value;
    const reason = byId('cancelSessionReasonInput').value.trim();
    const errEl = byId('cancelSessionModalError');
    const btn = byId('btnConfirmCancelSession');
    if (btn.disabled) return;

    errEl.style.display = 'none';

    if (reason.length < 3) {
        errEl.innerText = 'Alasan pembatalan kunjungan wajib diisi minimal 3 karakter.';
        errEl.style.display = 'block';
        return;
    }

    btn.disabled = true;
    btn.innerText = 'Membatalkan...';

    try {
        const result = await callApi(`/api/visit-sessions/${sessionId}/cancel`, {
            method: 'POST',
            body: JSON.stringify({ reason })
        });

        if (result.ok) {
            closeCancelSessionModal();
            showActualAlert('Sesi dibatalkan. Jadwal tetap tersimpan.');
            await loadActiveSession();
        } else {
            errEl.innerText = describeApiError(result, 'Gagal membatalkan sesi kunjungan.').message;
            errEl.style.display = 'block';
        }
    } finally {
        btn.disabled = false;
        btn.innerText = 'Batalkan Sesi';
    }
}
