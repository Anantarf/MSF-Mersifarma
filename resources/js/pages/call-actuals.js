const currentUserRole = window.mersiCallActual?.currentUserRole || "";
let todayPlans = [];
let actuals = [];
let doctors = [];
let products = [];
let activeSession = null;
let isDrawing = false;
let sigCanvas, sigCtx;

function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

document.addEventListener('DOMContentLoaded', async () => {
        bindStaticEvents();

        if (currentUserRole === 'mr') {
            initSignaturePad();
            await Promise.all([loadDoctors(), loadProducts()]);
            await loadActiveSession();
            await loadTodayPlans();

            const urlParams = new URLSearchParams(window.location.search);
            const autoPlanId = urlParams.get('plan_id');
            const autoDocId = urlParams.get('doctor_id');
            if (autoPlanId && !activeSession) {
                openActualModal(parseInt(autoPlanId), autoDocId ? parseInt(autoDocId) : null);
            }
        }

        loadActuals();
    });

document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeActualModal();
            closeCancelSessionModal();
            closeEvidenceModal();
        }
    });

function bindStaticEvents() {
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-action]');
            if (!trigger) return;

            const action = trigger.dataset.action;
            switch (action) {
                case 'open-unplanned-actual':
                    openActualModal(null);
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
                    closeActualModal();
                    break;
                case 'actual-step':
                    goToActualStep(parseInt(trigger.dataset.step, 10));
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
                default:
                    break;
            }
        });

        document.getElementById('actualForm')?.addEventListener('submit', submitActual);
        document.getElementById('cancelSessionForm')?.addEventListener('submit', handleCancelSessionSubmit);
        document.getElementById('productSearchInput')?.addEventListener('input', (event) => filterProductPicker(event.target.value));
        document.getElementById('photoFileInput')?.addEventListener('change', (event) => handlePhotoFile(event.target));
        document.getElementById('stampFileInput')?.addEventListener('change', (event) => handleStampFile(event.target));
        document.getElementById('productPicker')?.addEventListener('change', (event) => {
            if (event.target.matches('input[name="product_ids"]')) {
                saveActualDraft();
            }
        });

        ['filterManagerMr', 'filterManagerMonth', 'filterManagerVisitType'].forEach((id) => {
            document.getElementById(id)?.addEventListener('change', loadActuals);
        });

        document.getElementById('actualDoctorSelect')?.addEventListener('change', saveActualDraft);
        document.getElementById('actualDateInput')?.addEventListener('change', () => {
            syncActualDateDisplay();
            saveActualDraft();
        });
        ['checkInInput', 'checkOutInput'].forEach((id) => {
            const input = document.getElementById(id);
            input?.addEventListener('input', () => window.autoFormatTimeInput(input));
            input?.addEventListener('blur', () => window.normalizeTime24(input));
            input?.addEventListener('change', saveActualDraft);
        });
        document.getElementById('visitReasonInput')?.addEventListener('input', saveActualDraft);
    }

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
        document.getElementById('evidenceDoctorName').innerText = doctorName || 'Dokter';
        document.getElementById('evidencePhotoImg').src = photoUrl || '';
        document.getElementById('evidenceSignatureImg').src = sigUrl || '';
        const mrWrap = document.getElementById('evidenceMrWrap');
        const mrNameEl = document.getElementById('evidenceMrName');
        if (mrWrap && mrNameEl) {
            if (mrName) {
                mrNameEl.innerText = mrName;
                mrWrap.style.display = 'block';
            } else {
                mrWrap.style.display = 'none';
            }
        }
        document.getElementById('evidenceModal').style.display = 'flex';
    }

function closeEvidenceModal() {
        document.getElementById('evidenceModal').style.display = 'none';
    }

function formatVisitDateIndonesian(dateStr) {
        if (!dateStr) return '-';
        const parts = String(dateStr).split('-');
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

function openActualDatePicker() {
        const picker = document.getElementById('actualDateInput');
        if (picker) {
            if (typeof picker.showPicker === 'function') {
                picker.showPicker();
            } else {
                picker.focus();
                picker.click();
            }
        }
    }

function syncActualDateDisplay() {
        const input = document.getElementById('actualDateInput');
        const display = document.getElementById('actualDateDisplay');
        if (input && display) {
            display.value = formatVisitDateIndonesian(input.value);
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

let cameraStream = null;
let verificationMode = 'touch';
let touchSignatureDataUrl = '';
let stampSignatureDataUrl = '';

function initSignaturePad() {
        sigCanvas = document.getElementById('signatureCanvas');
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

        window.addEventListener('mouseup', () => {
            if (isDrawing) {
                isDrawing = false;
                if (verificationMode === 'touch') {
                    touchSignatureDataUrl = sigCanvas.toDataURL('image/png');
                    document.getElementById('signatureUrlInput').value = touchSignatureDataUrl;
                    updateSigBadge(true);
                }
                saveActualDraft();
            }
        });

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
            if (isDrawing) {
                isDrawing = false;
                if (verificationMode === 'touch') {
                    touchSignatureDataUrl = sigCanvas.toDataURL('image/png');
                    document.getElementById('signatureUrlInput').value = touchSignatureDataUrl;
                    updateSigBadge(true);
                }
                saveActualDraft();
            }
        }, { passive: false });
    }

function resizeSignatureCanvas() {
        if (!sigCanvas || !sigCtx) return;
        const rect = sigCanvas.getBoundingClientRect();
        if (rect.width > 0 && Math.abs(sigCanvas.width - Math.round(rect.width)) > 2) {
            const currentData = touchSignatureDataUrl || document.getElementById('signatureUrlInput')?.value;
            sigCanvas.width = Math.round(rect.width);
            sigCanvas.height = 165;
            sigCtx.strokeStyle = '#0f172a';
            sigCtx.lineWidth = 2.5;
            sigCtx.lineCap = 'round';
            sigCtx.lineJoin = 'round';
            if (currentData && currentData.startsWith('data:')) {
                const img = new Image();
                img.onload = () => {
                    sigCtx.drawImage(img, 0, 0);
                    setSigPlaceholderVisible(false);
                };
                img.src = currentData;
            }
        }
    }

function switchVerificationMode(mode) {
        verificationMode = mode;
        const tabTouch = document.getElementById('tabSignTouch');
        const tabStamp = document.getElementById('tabSignStamp');
        const panelTouch = document.getElementById('panelSignTouch');
        const panelStamp = document.getElementById('panelSignStamp');

        if (mode === 'touch') {
            tabTouch.style.background = 'white';
            tabTouch.style.color = 'var(--primary)';
            tabTouch.style.fontWeight = '600';
            tabTouch.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';

            tabStamp.style.background = 'transparent';
            tabStamp.style.color = 'var(--text-muted)';
            tabStamp.style.fontWeight = '500';
            tabStamp.style.boxShadow = 'none';

            panelTouch.style.display = 'block';
            panelStamp.style.display = 'none';

            document.getElementById('signatureUrlInput').value = touchSignatureDataUrl || '';
            setTimeout(resizeSignatureCanvas, 50);
        } else {
            tabStamp.style.background = 'white';
            tabStamp.style.color = 'var(--primary)';
            tabStamp.style.fontWeight = '600';
            tabStamp.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';

            tabTouch.style.background = 'transparent';
            tabTouch.style.color = 'var(--text-muted)';
            tabTouch.style.fontWeight = '500';
            tabTouch.style.boxShadow = 'none';

            panelTouch.style.display = 'none';
            panelStamp.style.display = 'block';

            document.getElementById('signatureUrlInput').value = stampSignatureDataUrl || '';
        }
        saveActualDraft();
    }

function setSigPlaceholderVisible(visible) {
        const el = document.getElementById('sigPlaceholder');
        if (el) el.style.opacity = visible ? '1' : '0';
    }

function updateSigBadge(hasContent) {
        const badge = document.getElementById('sigBadgeRecorded');
        if (badge) badge.style.display = hasContent ? 'inline' : 'none';
    }

function clearSignature() {
        if (!sigCanvas || !sigCtx) return;
        sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        touchSignatureDataUrl = '';
        if (verificationMode === 'touch') {
            document.getElementById('signatureUrlInput').value = '';
        }
        setSigPlaceholderVisible(true);
        updateSigBadge(false);
        saveActualDraft();
    }

function clearPhoto() {
        document.getElementById('photoUrlInput').value = '';
        document.getElementById('photoPreviewContainer').style.display = 'none';
        document.getElementById('photoPreviewImg').src = '';
        document.getElementById('photoFileName').innerText = '';
        const fileInput = document.getElementById('photoFileInput');
        if (fileInput) fileInput.value = '';
        saveActualDraft();
    }

async function handleStampFile(input) {
        if (!input.files || !input.files[0]) return;

        try {
            const file = input.files[0];
            const dataUrl = await fileToEvidenceDataUrl(file, 700);
            stampSignatureDataUrl = dataUrl;
            document.getElementById('signatureUrlInput').value = dataUrl;
            document.getElementById('stampPreviewImg').src = dataUrl;
            document.getElementById('stampFileName').innerText = file.name;
            document.getElementById('stampPreviewContainer').style.display = 'flex';
            saveActualDraft();
            window.showToast('Stempel disimpan.', 'success');
        } catch (error) {
            input.value = '';
            window.showToast(error.message || 'Gagal memproses foto stempel/paraf.', 'danger');
        }
    }

function clearStampFile() {
        const fileInput = document.getElementById('stampFileInput');
        if (fileInput) fileInput.value = '';
        document.getElementById('stampPreviewImg').src = '';
        document.getElementById('stampFileName').innerText = '';
        document.getElementById('stampPreviewContainer').style.display = 'none';
        stampSignatureDataUrl = '';
        if (verificationMode === 'stamp') {
            document.getElementById('signatureUrlInput').value = '';
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

        const wrap = document.getElementById('cameraViewfinderWrap');
        const btnText = document.getElementById('liveCameraBtnText');
        try {
            btnText.innerText = 'Menghubungkan...';
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' } }
            });
            const video = document.getElementById('cameraVideo');
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
        const wrap = document.getElementById('cameraViewfinderWrap');
        if (wrap) wrap.style.display = 'none';
        const btnText = document.getElementById('liveCameraBtnText');
        if (btnText) btnText.innerText = 'Buka Kamera';
        const video = document.getElementById('cameraVideo');
        if (video) video.srcObject = null;
    }

async function capturePhotoFromCamera() {
        const video = document.getElementById('cameraVideo');
        if (!video || !cameraStream) return;

        const sourceCanvas = document.createElement('canvas');
        sourceCanvas.width = video.videoWidth || 640;
        sourceCanvas.height = video.videoHeight || 480;
        sourceCanvas.getContext('2d').drawImage(video, 0, 0, sourceCanvas.width, sourceCanvas.height);

        try {
            const canvas = drawImageToCanvas(sourceCanvas, 900);
            const dataUrl = canvasToEvidenceDataUrl(canvas);
            document.getElementById('photoUrlInput').value = dataUrl;
            document.getElementById('photoPreviewImg').src = dataUrl;
            document.getElementById('photoFileName').innerText = 'kamera-langsung-' + Date.now() + '.jpg';
            document.getElementById('photoPreviewContainer').style.display = 'flex';

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
            document.getElementById('photoUrlInput').value = dataUrl;
            document.getElementById('photoPreviewImg').src = dataUrl;
            document.getElementById('photoFileName').innerText = file.name;
            document.getElementById('photoPreviewContainer').style.display = 'flex';
            saveActualDraft();
        } catch (error) {
            input.value = '';
            window.showToast(error.message || 'Gagal memproses file foto.', 'danger');
        }
    }

function saveActualDraft() {
        if (isPreparingActualForm) return;

        const planId = document.getElementById('actualPlanId')?.value;
        const sessionId = document.getElementById('actualSessionId')?.value;
        if (sessionId) return;

        const docId = document.getElementById('actualDoctorSelect')?.value || '';
        const checkIn = document.getElementById('checkInInput')?.value || '';
        const checkOut = document.getElementById('checkOutInput')?.value || '';
        const reason = document.getElementById('visitReasonInput')?.value || '';
        const productIds = Array.from(document.querySelectorAll('#productPicker input[name="product_ids"]:checked')).map(i => parseInt(i.value));
        const photoUrl = document.getElementById('photoUrlInput')?.value || '';
        const signatureUrl = document.getElementById('signatureUrlInput')?.value || '';

        if (!docId && !photoUrl && !signatureUrl && productIds.length === 0 && !reason) {
            return;
        }

        const draft = {
            plan_id: planId || null,
            doctor_id: docId,
            visit_date: document.getElementById('actualDateInput')?.value || todayString(),
            check_in: checkIn,
            check_out: checkOut,
            visit_reason: reason,
            product_ids: productIds,
            photo_url: photoUrl,
            signature_url: signatureUrl
        };

        try {
            localStorage.setItem(getDraftStorageKey(), JSON.stringify(draft));
            updateDraftBadge();
        } catch (e) {
        }
    }

function getDraftStorageKey() {
    const userId = window.mersiCallActual?.currentUserId || "guest";
    return `msf_actual_draft_${userId}`;
}

function updateDraftBadge() {
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

        const planId = document.getElementById('actualPlanId').value;
        if (!planId) {
            const docSel = document.getElementById('actualDoctorSelect');
            if (docSel) docSel.selectedIndex = 0;
        }

        const dateInput = document.getElementById('actualDateInput');
        if (dateInput) {
            dateInput.value = todayString();
            syncActualDateDisplay();
        }

        const checkIn = document.getElementById('checkInInput');
        if (checkIn) checkIn.value = '08.30';

        const checkOut = document.getElementById('checkOutInput');
        if (checkOut) checkOut.value = '09.00';

        const reason = document.getElementById('visitReasonInput');
        if (reason) reason.value = '';
        document.querySelectorAll('#productPicker input[name="product_ids"]').forEach(input => {
            input.checked = false;
        });
        document.getElementById('photoUrlInput').value = '';
        document.getElementById('photoPreviewContainer').style.display = 'none';
        document.getElementById('photoPreviewImg').src = '';
        document.getElementById('photoFileName').innerText = '';
        const photoFile = document.getElementById('photoFileInput');
        if (photoFile) photoFile.value = '';
        clearSignature();
        clearStampFile();
        currentActualStep = 1;
        renderStepView();
        isPreparingActualForm = false;

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
                const docSel = document.getElementById('actualDoctorSelect');
                if (docSel) docSel.value = draft.doctor_id;
            }
            if (draft.visit_date) {
                document.getElementById('actualDateInput').value = draft.visit_date;
            }
            syncActualDateDisplay();
            if (draft.check_in) document.getElementById('checkInInput').value = draft.check_in;
            if (draft.check_out) document.getElementById('checkOutInput').value = draft.check_out;
            if (draft.visit_reason) document.getElementById('visitReasonInput').value = draft.visit_reason;

            if (draft.product_ids && Array.isArray(draft.product_ids)) {
                document.querySelectorAll('#productPicker input[name="product_ids"]').forEach(input => {
                    input.checked = draft.product_ids.includes(parseInt(input.value));
                });
            }

            if (draft.photo_url) {
                document.getElementById('photoUrlInput').value = draft.photo_url;
                document.getElementById('photoPreviewImg').src = draft.photo_url;
                document.getElementById('photoFileName').innerText = 'draf-foto-tersimpan.jpg';
                document.getElementById('photoPreviewContainer').style.display = 'flex';
            }

            if (draft.signature_url) {
                document.getElementById('signatureUrlInput').value = draft.signature_url;
                touchSignatureDataUrl = draft.signature_url;
                const img = new Image();
                img.onload = () => {
                    if (sigCtx && sigCanvas) {
                        sigCtx.drawImage(img, 0, 0);
                        setSigPlaceholderVisible(false);
                    }
                };
                img.src = draft.signature_url;
            }
        } catch (e) {
            console.warn('Failed to parse draft:', e);
        }
    }

function todayString() {
        return new Date().toISOString().split('T')[0];
    }

function showActualAlert(message, type = 'success') {
        window.showToast(message, type);
    }

async function loadDoctors() {
        const res = await window.apiFetch('/api/doctors');
        const result = await res.json();
        doctors = result.success ? result.data : [];
        renderDoctorSelect();
    }

async function loadProducts() {
        const res = await window.apiFetch('/api/products');
        const result = await res.json();
        products = result.success ? result.data : [];
        renderProductPicker();
    }

async function loadTodayPlans() {
        const loading = document.getElementById('todayPlansLoading');
        if (loading) {
            loading.innerHTML = window.renderTableSkeleton(2);
            loading.style.display = 'block';
        }
        try {
            const res = await window.apiFetch(`/api/call-plans?date=${todayString()}&status=scheduled`);
            const result = await res.json();
            todayPlans = (result && result.success && Array.isArray(result.data)) ? result.data : [];
        } catch (err) {
            todayPlans = [];
        } finally {
            if (loading) loading.style.display = 'none';
            renderTodayPlans();
        }
    }

async function loadActuals() {
        const loading = document.getElementById('actualsLoading');
        if (loading) {
            loading.innerHTML = window.renderTableSkeleton(3);
            loading.style.display = 'block';
        }

        let url = '/api/call-actuals';
        if (currentUserRole !== 'mr') {
            const params = new URLSearchParams();
            const mrId = document.getElementById('filterManagerMr')?.value;
            const month = document.getElementById('filterManagerMonth')?.value;
            const visitType = document.getElementById('filterManagerVisitType')?.value;
            if (mrId) params.set('mr_id', mrId);
            if (month) params.set('month', month);
            if (visitType) params.set('visit_type', visitType);
            const queryStr = params.toString();
            if (queryStr) url += '?' + queryStr;
        }

        try {
            const res = await window.apiFetch(url);
            const result = await res.json();
            actuals = (result && result.success && Array.isArray(result.data)) ? result.data : [];
        } catch (err) {
            actuals = [];
        } finally {
            if (loading) loading.style.display = 'none';
            if (currentUserRole !== 'mr') {
                updateManagerMetrics();
            }
            renderActuals();
        }
    }

function updateManagerMetrics() {
        const total = actuals.length;
        const planned = actuals.filter(a => a.visit_type === 'planned').length;
        const unplanned = total - planned;

        const totalEl = document.getElementById('metricTotalActuals');
        const plannedEl = document.getElementById('metricPlannedActuals');
        const unplannedEl = document.getElementById('metricUnplannedActuals');
        const plannedPctEl = document.getElementById('metricPlannedPercent');
        const unplannedPctEl = document.getElementById('metricUnplannedPercent');

        if (totalEl) totalEl.innerText = total;
        if (plannedEl) plannedEl.innerText = planned;
        if (unplannedEl) unplannedEl.innerText = unplanned;

        const plannedPct = total > 0 ? Math.round((planned / total) * 100) : 0;
        const unplannedPct = total > 0 ? Math.round((unplanned / total) * 100) : 0;

        if (plannedPctEl) plannedPctEl.innerText = plannedPct + '%';
        if (unplannedPctEl) unplannedPctEl.innerText = unplannedPct + '%';
    }

function resetManagerFilters() {
        const mrSelect = document.getElementById('filterManagerMr');
        const monthInput = document.getElementById('filterManagerMonth');
        const visitTypeSelect = document.getElementById('filterManagerVisitType');
        if (mrSelect) mrSelect.value = '';
        if (monthInput) {
            const now = new Date();
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            monthInput.value = `${yyyy}-${mm}`;
        }
        if (visitTypeSelect) visitTypeSelect.value = '';
        loadActuals();
    }

function renderDoctorSelect(selectedDoctorId = '') {
        const select = document.getElementById('actualDoctorSelect');
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
        const picker = document.getElementById('productPicker');
        if (!picker) return;
        if (!products.length) {
            picker.innerHTML = '<span style="color: var(--text-muted); font-size: 0.85rem;">Produk belum tersedia.</span>';
            return;
        }
        picker.innerHTML = products.map((product) => {
            const form = getProductDosageForm(product);
            const desc = getProductDescription(product);

            return `
                <label style="display: flex; gap: 0.65rem; align-items: flex-start; background: #ffffff; border: 1px solid var(--border); border-radius: 0.45rem; padding: 0.6rem 0.75rem; font-size: 0.85rem; cursor: pointer; transition: all 0.15s ease; position: relative;">
                    <input type="checkbox" name="product_ids" value="${product.id}" style="margin-top: 0.25rem; accent-color: var(--primary);">
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                            <strong style="color: var(--secondary); font-size: 0.875rem; font-weight: 600;">${escapeHtml(product.name)}</strong>
                            <span style="font-size: 0.7rem; font-weight: 700; color: #0284c7; background: #e0f2fe; border: 1px solid #bae6fd; padding: 0.12rem 0.5rem; border-radius: 9999px; white-space: nowrap;">
                                ${escapeHtml(form)}
                            </span>
                        </div>
                        <div style="color: #64748b; font-size: 0.775rem; margin-top: 0.25rem; line-height: 1.35;">
                            ${escapeHtml(desc)}
                        </div>
                    </div>
                </label>
            `;
        }).join('');
    }

function filterProductPicker(keyword) {
        const term = String(keyword || '').toLowerCase().trim();
        const labels = document.querySelectorAll('#productPicker label');
        labels.forEach(label => {
            const text = label.innerText.toLowerCase();
            label.style.display = text.includes(term) ? 'flex' : 'none';
        });
    }

function renderTodayPlans() {
        const tableWrap = document.getElementById('todayPlansTableWrap');
        const tbody = document.getElementById('todayPlansTableBody');
        const empty = document.getElementById('todayPlansEmpty');
        if (!todayPlans.length) {
            if (tbody) tbody.innerHTML = '';
            if (tableWrap) tableWrap.style.display = 'none';
            empty.style.display = 'block';
            return;
        }
        empty.style.display = 'none';
        if (tableWrap) tableWrap.style.display = 'block';
        tbody.innerHTML = todayPlans.map((plan) => {
            const isThisPlanActive = activeSession && activeSession.plan_id === plan.id;
            const hasOtherSession = activeSession && !isThisPlanActive;

            let actionHtml = '';
            if (isThisPlanActive) {
                actionHtml = `
                    <span class="status-badge status-scheduled" style="padding: 0.35rem 0.65rem;">
                        Sedang Berlangsung
                    </span>
                `;
            } else if (hasOtherSession) {
                actionHtml = `
                    <button type="button" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; opacity: 0.6; cursor: not-allowed;" disabled title="Selesaikan sesi kunjungan aktif terlebih dahulu">
                        Mulai Kunjungan
                    </button>
                `;
            } else {
                actionHtml = `
                    <div style="display: inline-flex; gap: 0.4rem; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                        <button type="button" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;" data-action="start-visit-session" data-plan-id="${plan.id}" data-doctor-id="${plan.doctor_id}">
                            Mulai Kunjungan
                        </button>
                        <button type="button" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.35rem 0.6rem; color: var(--text-muted);" data-action="open-planned-actual" data-plan-id="${plan.id}" title="Atau isi laporan langsung secara manual">
                            Manual
                        </button>
                    </div>
                `;
            }

            return `
                <tr>
                    <td style="white-space: nowrap;">
                        <strong style="color: var(--primary); font-size: 0.95rem;">${formatWaktuIndonesia(plan.planned_time)}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Hari Ini</div>
                    </td>
                    <td style="white-space: nowrap;">
                        <strong style="font-size: 0.95rem; color: var(--secondary);">${escapeHtml(plan.doctor_name)}</strong>
                    </td>
                    <td>
                        <div style="font-weight: 500;">${escapeHtml(plan.doctor_specialization || 'Spesialis Umum')}</div>
                        <small style="color: var(--text-muted); font-size: 0.75rem;">${escapeHtml(plan.doctor_address || '-')}</small>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        ${actionHtml}
                    </td>
                </tr>
            `;
        }).join('');
    }

function renderActuals() {
        const tableWrap = document.getElementById('actualsTableWrap');
        const tbody = document.getElementById('actualsTableBody');
        const empty = document.getElementById('actualsEmpty');
        if (!actuals.length) {
            if (tbody) tbody.innerHTML = '';
            if (tableWrap) tableWrap.style.display = 'none';
            if (empty) empty.style.display = 'block';
            return;
        }
        if (empty) empty.style.display = 'none';
        if (tableWrap) tableWrap.style.display = 'block';

        tbody.innerHTML = actuals.map((actual) => {
            let visitTypeLabel = 'Terencana';
            let visitBadgeClass = 'status-approved';
            if (actual.visit_type === 'unplanned') {
                visitTypeLabel = 'Di Luar Rencana';
                visitBadgeClass = 'status-scheduled';
            } else if (actual.visit_type === 'non_target') {
                visitTypeLabel = 'Non-Target';
                visitBadgeClass = 'status-draft';
            }

            let timeHtml = '';
            const checkInVal = actual.check_in || actual.check_in_at;
            const checkOutVal = actual.check_out || actual.check_out_at;
            if (checkInVal && checkOutVal) {
                const inTime = formatWaktuIndonesia(checkInVal).replace(' WIB', '');
                const outTime = formatWaktuIndonesia(checkOutVal);
                timeHtml = `<div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">${inTime} - ${outTime}</div>`;
            } else if (checkInVal) {
                timeHtml = `<div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">${formatWaktuIndonesia(checkInVal)}</div>`;
            }

            const safeDoctorName = escapeHtml(actual.doctor_name || 'Dokter');
            const safeMrName = escapeHtml(actual.mr_name || 'MR');
            const doctorSub = escapeHtml(actual.doctor_specialization || 'Spesialis Umum');
            const doctorAddress = actual.doctor_address ? `<div style="font-size: 0.725rem; color: var(--text-muted); max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(actual.doctor_address)}</div>` : '';

            const mrColHtml = currentUserRole !== 'mr' ? `
                <td style="white-space: nowrap;">
                    <div style="font-weight: 600; color: var(--secondary); font-size: 0.85rem;">
                        ${safeMrName}
                    </div>
                    <span style="font-size: 0.7rem; font-weight: 700; color: #475569; background: #f1f5f9; padding: 0.1rem 0.4rem; border-radius: 4px; display: inline-block; margin-top: 0.15rem;">
                        Medical Rep
                    </span>
                </td>
            ` : '';

            const btnText = 'Lihat Bukti';
            const btnClass = 'btn btn-outline btn-sm';

            return `
                <tr>
                    <td style="white-space: nowrap;">
                        <strong style="color: var(--secondary); font-size: 0.875rem;">${formatVisitDateIndonesian(actual.visit_date)}</strong>
                        ${timeHtml}
                    </td>
                    ${mrColHtml}
                    <td>
                        <strong style="font-size: 0.9rem; color: var(--secondary); display: block;">${safeDoctorName}</strong>
                        <div style="font-size: 0.75rem; color: var(--primary); font-weight: 500;">${doctorSub}</div>
                        ${doctorAddress}
                    </td>
                    <td>
                        <span class="status-badge ${visitBadgeClass}">${escapeHtml(visitTypeLabel)}</span>
                        ${actual.visit_reason ? `
                            <div style="font-size: 0.725rem; color: var(--text-muted); margin-top: 0.25rem; font-style: italic;">
                                "${escapeHtml(actual.visit_reason)}"
                            </div>
                        ` : ''}
                    </td>
                    <td>
                        <div style="font-size: 0.8rem; font-weight: 600; color: var(--secondary);">
                            ${(actual.products || []).map((p) => escapeHtml(p.name)).join(', ') || '-'}
                        </div>
                    </td>
                    <td style="white-space: nowrap; text-align: center;">
                        <button type="button" class="${btnClass}" data-action="show-evidence" data-actual-id="${actual.id}" style="font-size: 0.75rem; color: var(--primary); border-color: #cbd5e1; background: #f8fafc; font-weight: 600;">
                            ${btnText}
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

let currentActualStep = 1;
let isPreparingActualForm = false;

function goToActualStep(step) {
        const errEl = document.getElementById('actualModalError');
        errEl.style.display = 'none';
        if (step > 1 && currentActualStep === 1) {
            const docId = document.getElementById('actualDoctorSelect')?.value;
            const date = document.getElementById('actualDateInput')?.value;
            const checkIn = document.getElementById('checkInInput')?.value;
            const checkOut = document.getElementById('checkOutInput')?.value;

            if (!docId) {
                errEl.innerText = 'Silakan pilih dokter terlebih dahulu.';
                errEl.style.display = 'block';
                return;
            }
            if (!date || !checkIn || !checkOut) {
                errEl.innerText = 'Tanggal, Jam Check-in, dan Jam Check-out wajib diisi.';
                errEl.style.display = 'block';
                return;
            }
        }
        if (step > 2) {
            const productIds = Array.from(document.querySelectorAll('#productPicker input[name="product_ids"]:checked'));
            if (!productIds.length) {
                errEl.innerText = 'Pilih minimal satu produk obat yang dipresentasikan.';
                errEl.style.display = 'block';
                return;
            }
        }

        currentActualStep = step;
        renderStepView();
    }

function renderStepView() {
        const s1 = document.getElementById('stepSection1');
        const s2 = document.getElementById('stepSection2');
        const s3 = document.getElementById('stepSection3');
        if (!s1 || !s2 || !s3) return;

        s1.style.display = currentActualStep === 1 ? 'block' : 'none';
        s2.style.display = currentActualStep === 2 ? 'block' : 'none';
        s3.style.display = currentActualStep === 3 ? 'block' : 'none';

        for (let i = 1; i <= 3; i++) {
            const item = document.getElementById(`stepIndicator${i}`);
            if (!item) continue;
            const num = item.querySelector('.step-num');
            const title = item.querySelector('.step-title');

            if (i < currentActualStep) {
                num.style.background = 'var(--success)';
                num.style.color = 'white';
                num.innerHTML = '<i class="bi bi-check-lg"></i>';
                if (title) {
                    title.style.color = 'var(--secondary)';
                    title.style.fontWeight = '600';
                }
            } else if (i === currentActualStep) {
                num.style.background = 'var(--primary)';
                num.style.color = 'white';
                num.innerHTML = i;
                if (title) {
                    title.style.color = 'var(--primary)';
                    title.style.fontWeight = '700';
                }
            } else {
                num.style.background = '#e2e8f0';
                num.style.color = '#64748b';
                num.innerHTML = i;
                if (title) {
                    title.style.color = 'var(--text-muted)';
                    title.style.fontWeight = '500';
                }
            }
        }

        const l1 = document.getElementById('stepLine1');
        const l2 = document.getElementById('stepLine2');
        if (l1) l1.style.background = currentActualStep > 1 ? 'var(--success)' : 'var(--border)';
        if (l2) l2.style.background = currentActualStep > 2 ? 'var(--success)' : 'var(--border)';

        if (currentActualStep === 3) {
            setTimeout(resizeSignatureCanvas, 60);
        }
    }

function openActualModal(planId, fallbackDoctorId = null, sessionId = null) {
        isPreparingActualForm = true;
        const plan = planId ? todayPlans.find((item) => item.id === planId) : null;
        const isSessionBacked = !!sessionId;
        document.getElementById('actualSessionId').value = sessionId || '';
        document.getElementById('actualPlanId').value = plan ? plan.id : (planId || '');
        document.getElementById('actualModalTitle').innerText = (plan || planId) ? 'Laporan Jadwal' : 'Laporan Luar Rencana';
        document.getElementById('actualDateInput').value = todayString();
        syncActualDateDisplay();
        document.getElementById('visitReasonWrap').style.display = (plan || planId) ? 'none' : 'block';
        document.getElementById('actualDoctorSelect').disabled = !!(plan || planId);

        const docIdToSelect = plan ? plan.doctor_id : (fallbackDoctorId || '');
        renderDoctorSelect(docIdToSelect);
        renderProductPicker();
        document.getElementById('photoUrlInput').value = '';
        document.getElementById('photoPreviewContainer').style.display = 'none';
        document.getElementById('photoPreviewImg').src = '';
        document.getElementById('photoFileName').innerText = '';
        document.getElementById('photoFileInput').value = '';

        clearSignature();
        clearStampFile();
        switchVerificationMode('touch');

        document.getElementById('visitReasonInput').value = '';
        document.getElementById('actualModalError').style.display = 'none';

        currentActualStep = 1;
        renderStepView();

        document.getElementById('actualModal').style.display = 'flex';

        updateDraftBadge();
        isPreparingActualForm = false;
        if (!isSessionBacked) {
            restoreActualDraft(planId);
        }
    }

function closeActualModal() {
        stopLiveCamera();
        currentActualStep = 1;
        document.getElementById('actualModal').style.display = 'none';
    }

function showActualModalError(message) {
        const errorEl = document.getElementById('actualModalError');
        errorEl.innerText = message;
        errorEl.style.display = 'block';
    }

function selectedProductIds() {
        return Array.from(document.querySelectorAll('#productPicker input[name="product_ids"]:checked'))
            .map((input) => parseInt(input.value))
            .filter((id) => Number.isInteger(id));
    }

function normalizeTimeInput(value) {
    let str = String(value || "").trim().replace(".", ":");
    if (/^\d:\d{2}/.test(str)) str = "0" + str;
    return str;
}

function validateActualForm() {
        const photoUrl = document.getElementById('photoUrlInput').value.trim();
        if (!photoUrl) return 'Foto kunjungan di lokasi klinik/praktik dokter wajib diambil atau diunggah.';

        const signatureUrl = document.getElementById('signatureUrlInput').value.trim();
        if (!signatureUrl) return 'Bukti verifikasi dokter (tanda tangan atau stempel fisik) wajib diisi.';

        if (!selectedProductIds().length) return 'Pilih minimal satu produk obat.';

        const sessionId = document.getElementById('actualSessionId').value;
        if (sessionId) return null;

        if (!document.getElementById('actualDoctorSelect').value) return 'Silakan pilih dokter terlebih dahulu.';
        if (!document.getElementById('actualDateInput').value) return 'Tanggal kunjungan wajib diisi.';
        if (!normalizeTimeInput(document.getElementById('checkInInput').value)) return 'Jam check-in wajib diisi.';
        if (!normalizeTimeInput(document.getElementById('checkOutInput').value)) return 'Jam check-out wajib diisi.';

        return null;
    }

function buildActualPayload() {
        const planId = document.getElementById('actualPlanId').value;
        const sessionId = document.getElementById('actualSessionId').value;
        const payload = {
            photo_url: document.getElementById('photoUrlInput').value.trim(),
            signature_url: document.getElementById('signatureUrlInput').value.trim(),
            products: selectedProductIds(),
            visit_reason: planId ? null : (document.getElementById('visitReasonInput').value.trim() || null)
        };

        if (sessionId) {
            payload.visit_session_id = parseInt(sessionId);
            return payload;
        }

        payload.plan_id = planId ? parseInt(planId) : null;
        payload.doctor_id = parseInt(document.getElementById('actualDoctorSelect').value);
        payload.visit_date = document.getElementById('actualDateInput').value;
        payload.check_in = normalizeTimeInput(document.getElementById('checkInInput').value);
        payload.check_out = normalizeTimeInput(document.getElementById('checkOutInput').value);

        return payload;
    }

async function submitActual(event) {
        event.preventDefault();
        const errorEl = document.getElementById('actualModalError');
        const button = document.getElementById('actualSubmitButton');
        errorEl.style.display = 'none';

        const validationMessage = validateActualForm();
        if (validationMessage) {
            showActualModalError(validationMessage);
            return;
        }

        button.disabled = true;
        button.innerText = 'Menyimpan...';

        try {
            const res = await window.apiFetch('/api/call-actuals', {
                method: 'POST',
                body: JSON.stringify(buildActualPayload())
            });
            const result = await res.json();
            if (result.success) {
                clearActualDraft(false);
                closeActualModal();
                showActualAlert('Laporan disimpan.');
                await loadActiveSession();
                loadTodayPlans();
                loadActuals();
            } else {
                showActualModalError(result.message || 'Gagal menyimpan kunjungan.');
            }
        } catch (error) {
            showActualModalError('Gagal menyimpan laporan. Coba lagi.');
        } finally {
            button.disabled = false;
            button.innerText = 'Simpan';
        }
    }

async function loadActiveSession() {
        try {
            const res = await window.apiFetch('/api/visit-sessions/active');
            const result = await res.json();
            activeSession = result.success ? result.data : null;
            renderActiveSession();
        } catch (err) {
            activeSession = null;
            renderActiveSession();
        }
    }

function renderActiveSession() {
        const section = document.getElementById('activeSessionSection');
        if (!section) return;
        if (!activeSession) {
            section.style.display = 'none';
            return;
        }

        section.style.display = 'block';
        document.getElementById('activeSessionDoctorName').innerText = activeSession.doctor_name || 'Target Dokter';
        document.getElementById('activeSessionDoctorDetail').innerText = `${activeSession.doctor_specialization || 'Spesialis Umum'} - ${activeSession.doctor_address || '-'}`;

        const checkInDate = new Date(activeSession.check_in_at);
        document.getElementById('activeSessionCheckInTime').innerText = formatWaktuIndonesia(checkInDate);

        const checkoutWrap = document.getElementById('activeSessionCheckOutWrap');
        const btnCheckout = document.getElementById('btnSessionCheckout');
        const btnContinue = document.getElementById('btnSessionContinue');

        if (activeSession.check_out_at) {
            const checkOutDate = new Date(activeSession.check_out_at);
            document.getElementById('activeSessionCheckOutTime').innerText = formatWaktuIndonesia(checkOutDate);
            checkoutWrap.style.display = 'inline';
            btnCheckout.style.display = 'none';
            btnContinue.style.display = 'inline-block';
        } else {
            checkoutWrap.style.display = 'none';
            btnCheckout.style.display = 'inline-block';
            btnContinue.style.display = 'none';
        }
    }

async function startVisitSession(planId, doctorId) {
        try {
            const res = await window.apiFetch('/api/visit-sessions/start', {
                method: 'POST',
                body: JSON.stringify({
                    plan_id: planId ? parseInt(planId) : null,
                    doctor_id: doctorId ? parseInt(doctorId) : null
                })
            });
            const result = await res.json();
            if (result.success) {
                showActualAlert('Sesi dimulai.');
                await loadActiveSession();
                renderTodayPlans();
            } else {
                showActualAlert(result.message || 'Gagal memulai sesi kunjungan.', 'danger');
            }
        } catch (err) {
            showActualAlert('Terjadi kesalahan jaringan saat memulai kunjungan.', 'danger');
        }
    }

async function handleSessionCheckout() {
        if (!activeSession) return;
        const btn = document.getElementById('btnSessionCheckout');
        btn.disabled = true;
        btn.innerText = 'Mencatat Check-out...';

        try {
            const res = await window.apiFetch(`/api/visit-sessions/${activeSession.id}/checkout`, {
                method: 'POST'
            });
            const result = await res.json();
            btn.disabled = false;
            btn.innerText = 'Akhiri Kunjungan (Check-out)';

            if (result.success) {
                showActualAlert('Check-out tersimpan. Lengkapi bukti.');
                await loadActiveSession();
                openActualModalFromSession();
            } else {
                showActualAlert(result.message || 'Gagal mencatat check-out.', 'danger');
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerText = 'Akhiri Kunjungan (Check-out)';
            showActualAlert('Terjadi kesalahan jaringan saat check-out.', 'danger');
        }
    }

function openActualModalFromSession() {
        if (!activeSession) return;

        openActualModal(activeSession.plan_id, activeSession.doctor_id, activeSession.id);
        document.getElementById('actualModalTitle').innerText = 'Lengkapi Laporan';

        if (activeSession.check_in_at) {
            const cin = new Date(activeSession.check_in_at);
            const hh = String(cin.getHours()).padStart(2, '0');
            const mm = String(cin.getMinutes()).padStart(2, '0');
            document.getElementById('checkInInput').value = `${hh}:${mm}`;
        }

        if (activeSession.check_out_at) {
            const cout = new Date(activeSession.check_out_at);
            const hh = String(cout.getHours()).padStart(2, '0');
            const mm = String(cout.getMinutes()).padStart(2, '0');
            document.getElementById('checkOutInput').value = `${hh}:${mm}`;
        }
    }

function openCancelSessionModal() {
        if (!activeSession) return;
        document.getElementById('cancelSessionId').value = activeSession.id;
        document.getElementById('cancelSessionDoctorName').innerText = activeSession.doctor_name || 'Target Dokter';
        document.getElementById('cancelSessionReasonInput').value = '';
        const errEl = document.getElementById('cancelSessionModalError');
        errEl.style.display = 'none';
        errEl.innerText = '';
        document.getElementById('cancelSessionModal').style.display = 'flex';
    }

function closeCancelSessionModal() {
        document.getElementById('cancelSessionModal').style.display = 'none';
    }

async function handleCancelSessionSubmit(e) {
        e.preventDefault();
        const sessionId = document.getElementById('cancelSessionId').value;
        const reason = document.getElementById('cancelSessionReasonInput').value.trim();
        const errEl = document.getElementById('cancelSessionModalError');
        const btn = document.getElementById('btnConfirmCancelSession');

        errEl.style.display = 'none';

        if (reason.length < 3) {
            errEl.innerText = 'Alasan pembatalan kunjungan wajib diisi minimal 3 karakter.';
            errEl.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.innerText = 'Membatalkan...';

        try {
            const res = await window.apiFetch(`/api/visit-sessions/${sessionId}/cancel`, {
                method: 'POST',
                body: JSON.stringify({ reason: reason })
            });
            const result = await res.json();
            btn.disabled = false;
            btn.innerText = 'Batalkan Sesi';

            if (result.success) {
                closeCancelSessionModal();
                showActualAlert('Sesi dibatalkan. Jadwal tetap tersimpan.');
                await loadActiveSession();
                renderTodayPlans();
            } else {
                errEl.innerText = result.message || 'Gagal membatalkan sesi kunjungan.';
                errEl.style.display = 'block';
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerText = 'Batalkan Sesi';
            errEl.innerText = 'Gagal membatalkan sesi. Coba lagi.';
            errEl.style.display = 'block';
        }
    }
