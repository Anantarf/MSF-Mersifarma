let approvedCallLists = [];
let currentCallListDoctors = [];
let currentPlans = [];
let plansLoadError = null;
let callListLoadRequest = 0;
let callListDoctorsRequest = 0;

const escapeHtml = window.MSF.escapeHtml;
const formatPeriodMonth = window.MSF.formatPeriodIndonesian;
const formatDateLabel = window.MSF.formatTanggalIndonesia;
const formatDateTimeLabel = window.MSF.formatDateTimeIndonesia;
const formatWaktuIndonesia = window.MSF.formatWaktuIndonesia;
document.addEventListener('DOMContentLoaded', () => {
    bindPlanEvents();
    loadPlans();
    loadApprovedCallLists();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeCustomPlanDoctorDropdown();
        closeCreatePlanModal();
        closeReschedulePlanModal();
        closeCancelPlanModal();
        closePlanDetailModal();
    }
});

function showAlert(message, type = 'success') {
    window.showToast(message, type);
}

function bindPlanEvents() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-action]');
        if (!trigger) return;

        switch (trigger.dataset.action) {
            case 'open-create-plan':
                openCreatePlanModal();
                break;
            case 'close-create-plan':
                closeCreatePlanModal();
                break;
            case 'select-plan-doctor':
                handleCustomPlanDoctorSelect(trigger.dataset.doctorId);
                break;
            case 'quick-date':
                setQuickDate(trigger.dataset.date);
                break;
            case 'open-reschedule-plan':
                openReschedulePlanModal(parseInt(trigger.dataset.planId, 10));
                break;
            case 'close-reschedule-plan':
                closeReschedulePlanModal();
                break;
            case 'open-cancel-plan':
                openCancelPlanModal(parseInt(trigger.dataset.planId, 10));
                break;
            case 'close-cancel-plan':
                closeCancelPlanModal();
                break;
            case 'open-plan-detail':
                openPlanDetailModal(parseInt(trigger.dataset.planId, 10));
                break;
            case 'close-plan-detail':
                closePlanDetailModal();
                break;
            case 'close-conflict-modal':
                closeConflictModal();
                break;
            case 'modify-conflict-time':
                modifyConflictTime();
                break;
            case 'retry-load-plans':
                loadPlans();
                break;
            case 'retry-load-call-lists':
                loadApprovedCallLists();
                break;
            case 'retry-load-doctors':
                onCallListChange();
                break;
            default:
                break;
        }
    });

    document.getElementById('filterDateInput')?.addEventListener('change', applyFilters);
    document.getElementById('filterStatusSelect')?.addEventListener('change', applyFilters);
    document.getElementById('callListSelect')?.addEventListener('change', onCallListChange);
    document.getElementById('doctorSelect')?.addEventListener('change', onDoctorChange);
    document.getElementById('planDateInput')?.addEventListener('change', onPlanDateChange);
    document.getElementById('reschedulePlanDateInput')?.addEventListener('change', onRescheduleDateChange);
    document.getElementById('createPlanForm')?.addEventListener('submit', handleCreatePlanSubmit);
    document.getElementById('reschedulePlanForm')?.addEventListener('submit', handleReschedulePlanSubmit);
    document.getElementById('cancelPlanForm')?.addEventListener('submit', handleCancelPlanSubmit);
    document.getElementById('btnConfirmConflictSubmit')?.addEventListener('click', () => {
        const cb = conflictCallback;
        closeConflictModal();
        if (typeof cb === 'function') cb();
    });

    document.getElementById('customPlanDoctorTrigger')?.addEventListener('click', toggleCustomPlanDoctorDropdown);
    document.getElementById('customPlanDoctorSearch')?.addEventListener('input', (e) => renderCustomPlanDoctorList(e.target.value));
    document.addEventListener('click', (e) => {
        const wrap = document.getElementById('customPlanDoctorWrap');
        if (wrap && !wrap.contains(e.target)) {
            closeCustomPlanDoctorDropdown();
        }
    });

    const plannedTimeInput = document.getElementById('plannedTimeInput');
    plannedTimeInput?.addEventListener('input', () => window.autoFormatTimeInput(plannedTimeInput));
    plannedTimeInput?.addEventListener('blur', () => window.normalizeTime24(plannedTimeInput));

    const reschedulePlannedTimeInput = document.getElementById('reschedulePlannedTimeInput');
    reschedulePlannedTimeInput?.addEventListener('input', () => window.autoFormatTimeInput(reschedulePlannedTimeInput));
    reschedulePlannedTimeInput?.addEventListener('blur', () => window.normalizeTime24(reschedulePlannedTimeInput));
}

function applyFilters() {
    loadPlans();
}

function setQuickDate(type) {
    const input = document.getElementById('filterDateInput');
    if (type === 'all') {
        input.value = '';
    } else if (type === 'today') {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        input.value = `${yyyy}-${mm}-${dd}`;
    } else if (type === 'tomorrow') {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const yyyy = tomorrow.getFullYear();
        const mm = String(tomorrow.getMonth() + 1).padStart(2, '0');
        const dd = String(tomorrow.getDate()).padStart(2, '0');
        input.value = `${yyyy}-${mm}-${dd}`;
    }
    applyFilters();
}

async function loadPlans() {
    const loading = document.getElementById('loadingPlans');
    const errorEl = document.getElementById('errorPlans');
    if (errorEl) errorEl.style.display = 'none';

    if (loading) {
        loading.innerHTML = window.renderTableSkeleton(3);
        loading.style.display = 'block';
    }

    const filterDate = document.getElementById('filterDateInput').value;
    const filterStatus = document.getElementById('filterStatusSelect').value;

    let query = [];
    if (filterDate) query.push(`date=${encodeURIComponent(filterDate)}`);
    if (filterStatus) query.push(`status=${encodeURIComponent(filterStatus)}`);
    const queryString = query.length ? `?${query.join('&')}` : '';

    try {
        const res = await window.apiFetch(`/api/call-plans${queryString}`);
        const result = await res.json();
        if (res.ok && result && result.success && Array.isArray(result.data)) {
            currentPlans = result.data;
            plansLoadError = null;
        } else {
            currentPlans = [];
            plansLoadError = window.describeApiError(result || res, 'Gagal memuat jadwal kunjungan dari server.', res.status);
        }
    } catch (err) {
        currentPlans = [];
        plansLoadError = window.describeApiError(err, 'Terjadi gangguan jaringan saat memuat jadwal.');
        console.error('Gagal memuat jadwal', err);
    } finally {
        if (loading) loading.style.display = 'none';
        renderPlansGrid();
    }
}

function renderPlansGrid() {
    const tableWrap = document.getElementById('planTableWrap');
    const tbody = document.getElementById('planTableBody');
    const empty = document.getElementById('emptyPlans');
    const errorEl = document.getElementById('errorPlans');
    const errorText = document.getElementById('errorPlansText');

    if (plansLoadError) {
        if (tbody) tbody.innerHTML = '';
        if (tableWrap) tableWrap.style.display = 'none';
        if (empty) empty.style.display = 'none';
        if (errorEl) {
            if (errorText) errorText.innerText = plansLoadError;
            errorEl.style.display = 'block';
        }
        return;
    }

    if (errorEl) errorEl.style.display = 'none';

    if (!currentPlans.length) {
        if (tbody) tbody.innerHTML = '';
        if (tableWrap) tableWrap.style.display = 'none';
        empty.style.display = 'block';
        return;
    }

    empty.style.display = 'none';
    if (tableWrap) tableWrap.style.display = 'block';
    tbody.innerHTML = currentPlans.map(plan => {
        const isCompleted = plan.status === 'completed';
        const isCancelled = plan.status === 'cancelled';
        const isExpired = plan.status === 'expired';
        let statusLabel = 'Terjadwal';
        if (isCompleted) statusLabel = 'Selesai';
        if (isCancelled) statusLabel = 'Dibatalkan';
        if (isExpired) statusLabel = 'Tidak Terlaksana';

        const safeStatus = ['scheduled', 'completed', 'cancelled', 'expired'].includes(plan.status) ? plan.status : 'scheduled';
        const statusClass = `status-${safeStatus}`;

        const dateObj = new Date(plan.plan_date + 'T' + plan.planned_time);
        const dateStr = formatDateLabel(plan.plan_date);
        const timeStr = formatWaktuIndonesia(plan.planned_time);

        return `
            <tr>
                <td class="plan-nowrap-cell">
                    <strong class="plan-table-date">${dateStr}</strong>
                    <div class="plan-table-time">${timeStr}</div>
                </td>
                <td class="plan-nowrap-cell">
                    <strong class="plan-table-doctor">${escapeHtml(plan.doctor_name || 'Dokter')}</strong>
                </td>
                <td>
                    <div class="plan-table-specialty">${escapeHtml(plan.doctor_specialization || 'Spesialis Umum')}</div>
                    <small class="plan-help-text">${escapeHtml(plan.doctor_address || '-')}</small>
                </td>
                <td>
                    <div class="plan-status-wrap">
                        <span class="status-badge ${statusClass}">
                            <span class="status-dot"></span>
                            ${escapeHtml(statusLabel)}
                        </span>
                        ${isCancelled && plan.cancellation_reason ? `
                            <div class="plan-meta-note plan-meta-cancelled" title="${escapeHtml(plan.cancellation_reason)}">
                                <span class="plan-meta-label">Alasan:</span> ${escapeHtml(plan.cancellation_reason)}
                            </div>
                        ` : (plan.status === 'scheduled' && plan.rescheduled_at && plan.reschedule_reason ? `
                            <div class="plan-meta-note plan-meta-rescheduled" title="${escapeHtml(plan.reschedule_reason)}">
                                <i class="bi bi-clock-history"></i> Reschedule: ${escapeHtml(plan.reschedule_reason)}
                            </div>
                        ` : '')}
                    </div>
                </td>
                <td class="plan-action-cell">
                    ${plan.status === 'scheduled' ? `
                        <div class="plan-inline-actions">
                            <a href="/call-actuals?plan_id=${plan.id}&doctor_id=${plan.doctor_id}" class="btn btn-primary btn-sm plan-link-button">
                                Isi Laporan
                            </a>
                            <button type="button" class="btn btn-outline btn-sm" data-action="open-reschedule-plan" data-plan-id="${plan.id}">
                                Reschedule
                            </button>
                            <button type="button" class="btn btn-subtle-danger btn-sm" data-action="open-cancel-plan" data-plan-id="${plan.id}">
                                Batalkan
                            </button>
                        </div>
                    ` : (isCompleted ? `
                        <a href="/call-actuals" class="btn btn-outline btn-sm plan-link-button">
                            Lihat Laporan
                        </a>
                    ` : (isExpired ? `
                        <button type="button" class="btn btn-outline btn-sm" data-action="open-create-plan">
                            Jadwalkan Ulang
                        </button>
                    ` : `
                        <button type="button" class="btn btn-outline btn-sm" data-action="open-plan-detail" data-plan-id="${plan.id}">
                            Detail
                        </button>
                    `))}
                </td>
            </tr>
        `;
    }).join('');
}

async function loadApprovedCallLists() {
    const select = document.getElementById('callListSelect');
    const errorBox = document.getElementById('callListLoadError');
    const errorText = document.getElementById('callListLoadErrorText');
    const requestId = ++callListLoadRequest;
    if (!select) return;

    if (errorBox) errorBox.style.display = 'none';
    select.disabled = true;
    select.innerHTML = '<option value="">-- Memuat target dokter... --</option>';

    try {
        const res = await window.apiFetch('/api/call-lists?status=approved');
        const result = await res.json();
        if (requestId !== callListLoadRequest) return;
        if (res.ok && result.success && Array.isArray(result.data)) {
            approvedCallLists = result.data;
            if (!approvedCallLists.length) {
                select.innerHTML = '<option value="">-- Belum ada target dokter yang disetujui --</option>';
                select.disabled = true;
                return;
            }
            select.innerHTML = '<option value="">-- Pilih target bulanan --</option>' +
                approvedCallLists.map(cl => `
                    <option value="${cl.id}" data-period="${escapeHtml(cl.period_month)}">
                        ${escapeHtml(formatPeriodMonth(cl.period_month))}
                    </option>
                `).join('');
            select.disabled = false;
        } else {
            throw new Error(window.describeApiError(result || res, 'Gagal memuat target', res.status));
        }
    } catch (err) {
        if (requestId !== callListLoadRequest) return;
        const message = window.describeApiError(err, 'Gagal memuat target dokter yang disetujui.');
        select.innerHTML = '<option value="">Target dokter gagal dimuat</option>';
        select.disabled = true;
        if (errorText) errorText.innerText = message;
        if (errorBox) errorBox.style.display = 'block';
    }
}

async function onCallListChange() {
    const select = document.getElementById('callListSelect');
    const doctorSelect = document.getElementById('doctorSelect');
    const planDateInput = document.getElementById('planDateInput');
    const dateHelp = document.getElementById('dateRangeHelp');
    const errorBox = document.getElementById('doctorLoadError');
    const errorText = document.getElementById('doctorLoadErrorText');
    const requestId = ++callListDoctorsRequest;

    const selectedOption = select.options[select.selectedIndex];
    const callListId = select.value;
    const periodMonth = selectedOption.getAttribute('data-period'); // Format YYYY-MM
    currentCallListDoctors = [];
    if (errorBox) errorBox.style.display = 'none';
    const recNotice = document.getElementById('doctorRecNotice');
    if (recNotice) recNotice.style.display = 'none';

    if (!callListId) {
        doctorSelect.innerHTML = '<option value="">-- Pilih target bulanan terlebih dahulu --</option>';
        doctorSelect.disabled = true;
        setCustomPlanDoctorDisabled(true, '-- Pilih target bulanan terlebih dahulu --');
        planDateInput.removeAttribute('min');
        planDateInput.removeAttribute('max');
        planDateInput.disabled = true;
        dateHelp.innerText = 'Tanggal mengikuti periode target dokter.';
        const previewEl = document.getElementById('planSelectedDateText');
        if (previewEl) previewEl.style.display = 'none';
        return;
    }

    // Set batasan tanggal sesuai periode target
    if (periodMonth) {
        const [yyyy, mm] = periodMonth.split('-');
        const lastDay = new Date(yyyy, mm, 0).getDate();
        const minDate = `${periodMonth}-01`;
        const maxDate = `${periodMonth}-${String(lastDay).padStart(2, '0')}`;
        const today = new Date();
        const yyyyNow = today.getFullYear();
        const mmNow = String(today.getMonth() + 1).padStart(2, '0');
        const ddNow = String(today.getDate()).padStart(2, '0');
        const todayStr = `${yyyyNow}-${mmNow}-${ddNow}`;

        const effectiveMin = todayStr > minDate ? todayStr : minDate;
        planDateInput.min = effectiveMin;
        planDateInput.max = maxDate;

        if (todayStr >= effectiveMin && todayStr <= maxDate) {
            planDateInput.value = todayStr;
        } else {
            planDateInput.value = effectiveMin;
        }

        planDateInput.disabled = false;
        const monthName = formatPeriodMonth(periodMonth);
        const startDay = parseInt(effectiveMin.split('-')[2], 10);
        dateHelp.innerText = `Pilih tanggal antara ${startDay} s.d. ${lastDay} ${monthName}.`;
        onPlanDateChange();
    }

    doctorSelect.disabled = true;
    doctorSelect.innerHTML = '<option value="">Memuat dokter target...</option>';
    setCustomPlanDoctorDisabled(true, 'Memuat dokter target...');

    try {
        const res = await window.apiFetch(`/api/call-lists/${callListId}`);
        const result = await res.json();
        if (requestId !== callListDoctorsRequest || select.value !== callListId) return;
        if (res.ok && result.success && Array.isArray(result.data?.doctors)) {
            const callListDoctors = result.data.doctors;
            if (!callListDoctors.length) {
                doctorSelect.innerHTML = '<option value="">Tidak ada dokter di target ini</option>';
                setCustomPlanDoctorDisabled(true, 'Tidak ada dokter di target ini');
                return;
            }
            let recMap = {};
            try {
                const recRes = await window.apiFetch('/api/managerial-campaigns/recommendations');
                const recData = await recRes.json();
                if (recRes.ok && recData.success && Array.isArray(recData.data)) {
                    recData.data.forEach((item) => {
                        recMap[item.doctor_id] = item;
                    });
                } else {
                    console.warn('Rekomendasi produk tidak tersedia untuk target dokter ini.', recData.message || recRes.status);
                }
            } catch (err) {
                console.warn('Gagal memuat rekomendasi produk untuk target dokter.', err);
            }
            if (requestId !== callListDoctorsRequest || select.value !== callListId) return;

            const recommendedDocs = [];
            const regularDocs = [];

            callListDoctors.forEach(doc => {
                const rec = recMap[doc.id];
                if (rec) {
                    recommendedDocs.push({ doc, rec });
                } else {
                    regularDocs.push({ doc });
                }
            });

            let optionsHtml = '<option value="">-- Pilih Target Dokter --</option>';

            if (recommendedDocs.length > 0) {
                optionsHtml += `<optgroup label="⭐ Rekomendasi Manajer">` +
                    recommendedDocs.map(({ doc, rec }) => `
                        <option value="${doc.id}" data-rec-product="${escapeHtml(rec.focus_products || '')}">
                            ⭐ ${escapeHtml(doc.name)} (${escapeHtml(doc.specialization || 'Umum')})
                        </option>
                    `).join('') +
                    `</optgroup>`;
            }

            if (regularDocs.length > 0) {
                const groupLabel = recommendedDocs.length > 0 ? 'Target Dokter Lainnya' : '';
                const openGroup = groupLabel ? `<optgroup label="${groupLabel}">` : '';
                const closeGroup = groupLabel ? `</optgroup>` : '';
                optionsHtml += openGroup +
                    regularDocs.map(({ doc }) => `
                        <option value="${doc.id}">
                            ${escapeHtml(doc.name)} (${escapeHtml(doc.specialization || 'Umum')})
                        </option>
                    `).join('') +
                    closeGroup;
            }

            doctorSelect.innerHTML = optionsHtml;
            doctorSelect.disabled = false;
            currentCallListDoctors = callListDoctors.map(doc => {
                const rec = recMap[doc.id];
                return {
                    ...doc,
                    is_recommended: !!rec,
                    rec_product: rec ? rec.focus_products : null
                };
            });
            setCustomPlanDoctorDisabled(false, '-- Pilih Target Dokter --');
            renderCustomPlanDoctorList('');
            syncCustomPlanDoctorTrigger('');
        } else {
            throw new Error(window.describeApiError(result || res, 'Gagal memuat dokter pada target ini.', res.status));
        }
    } catch (err) {
        if (requestId !== callListDoctorsRequest || select.value !== callListId) return;
        const message = window.describeApiError(err, 'Gagal memuat dokter pada target ini.');
        doctorSelect.innerHTML = '<option value="">Dokter gagal dimuat</option>';
        setCustomPlanDoctorDisabled(true, 'Dokter gagal dimuat');
        if (errorText) errorText.innerText = message;
        if (errorBox) errorBox.style.display = 'block';
    }
}

function renderCustomPlanDoctorList(filterKeyword = '') {
    const list = document.getElementById('customPlanDoctorList');
    if (!list) return;

    const term = String(filterKeyword || '').toLowerCase().trim();
    const doctorSelect = document.getElementById('doctorSelect');
    const selectedId = doctorSelect ? String(doctorSelect.value || '') : '';

    const filtered = currentCallListDoctors.filter((doc) => {
        if (!term) return true;
        const name = (doc.name || '').toLowerCase();
        const spec = (doc.specialization || '').toLowerCase();
        const addr = (doc.address || '').toLowerCase();
        return name.includes(term) || spec.includes(term) || addr.includes(term);
    });

    if (!filtered.length) {
        list.innerHTML = '<div class="ca-doctor-opt-empty">Tidak ada dokter yang cocok dengan pencarian.</div>';
        return;
    }

    const recs = filtered.filter(d => d.is_recommended);
    const regs = filtered.filter(d => !d.is_recommended);

    let html = '';
    if (recs.length > 0) {
        html += '<div class="ca-doctor-opt-group">⭐ Rekomendasi Manajer</div>';
        html += recs.map(doc => renderCustomPlanDoctorItem(doc, selectedId)).join('');
    }
    if (regs.length > 0) {
        if (recs.length > 0) {
            html += '<div class="ca-doctor-opt-group">Target Dokter Lainnya</div>';
        }
        html += regs.map(doc => renderCustomPlanDoctorItem(doc, selectedId)).join('');
    }

    list.innerHTML = html;
}

function renderCustomPlanDoctorItem(doc, selectedId) {
    const isSelected = String(doc.id) === selectedId;
    const addr = doc.address || '-';
    return `
        <div class="ca-doctor-opt ${isSelected ? 'is-selected' : ''}" data-action="select-plan-doctor" data-doctor-id="${doc.id}">
            <div class="ca-doctor-opt-head">
                <strong class="ca-doctor-opt-name">${escapeHtml(doc.name)}</strong>
                <span class="ca-doctor-opt-spec">${escapeHtml(doc.specialization || 'Umum')}</span>
            </div>
            <div class="ca-doctor-opt-sub">
                <i class="bi bi-building" aria-hidden="true"></i>
                <span>${escapeHtml(addr)}</span>
            </div>
        </div>
    `;
}

function syncCustomPlanDoctorTrigger(doctorId = null) {
    const select = document.getElementById('doctorSelect');
    const trigger = document.getElementById('customPlanDoctorTrigger');
    const label = document.getElementById('customPlanDoctorLabel');
    if (!trigger || !label) return;

    const currentId = doctorId !== null && doctorId !== undefined && doctorId !== ''
        ? String(doctorId)
        : (select ? String(select.value || '') : '');

    trigger.disabled = !!(select && select.disabled);

    if (!currentId) {
        label.innerHTML = '<span style="color: #64748b;">-- Pilih Target Dokter --</span>';
        return;
    }

    const doc = currentCallListDoctors.find((d) => String(d.id) === currentId);
    if (!doc) {
        label.innerHTML = '<span style="color: #64748b;">-- Pilih Target Dokter --</span>';
        return;
    }

    const addr = doc.address || '';
    label.innerHTML = `
        <div class="ca-doctor-trigger-body">
            <div class="ca-doctor-trigger-head">
                <span>${escapeHtml(doc.name)}</span>
                <span class="ca-doctor-spec-pill">${escapeHtml(doc.specialization || 'Umum')}</span>
            </div>
            ${addr ? `
                <div class="ca-doctor-trigger-sub">
                    <i class="bi bi-building" aria-hidden="true"></i>
                    <span>${escapeHtml(addr)}</span>
                </div>
            ` : ''}
        </div>
    `;
}

function setCustomPlanDoctorDisabled(disabled, labelText) {
    const trigger = document.getElementById('customPlanDoctorTrigger');
    const label = document.getElementById('customPlanDoctorLabel');
    if (trigger) trigger.disabled = !!disabled;
    if (label && labelText) {
        label.innerHTML = `<span style="color: #64748b;">${escapeHtml(labelText)}</span>`;
    }
}

function openCustomPlanDoctorDropdown() {
    const trigger = document.getElementById('customPlanDoctorTrigger');
    if (trigger && trigger.disabled) return;
    const wrap = document.getElementById('customPlanDoctorWrap');
    const dropdown = document.getElementById('customPlanDoctorDropdown');
    const search = document.getElementById('customPlanDoctorSearch');
    if (!wrap || !dropdown) return;

    wrap.classList.add('is-open');
    dropdown.style.display = 'flex';
    if (search) {
        search.value = '';
        renderCustomPlanDoctorList('');
        setTimeout(() => search.focus(), 50);
    }
}

function closeCustomPlanDoctorDropdown() {
    const wrap = document.getElementById('customPlanDoctorWrap');
    const dropdown = document.getElementById('customPlanDoctorDropdown');
    if (!wrap || !dropdown) return;
    wrap.classList.remove('is-open');
    dropdown.style.display = 'none';
}

function toggleCustomPlanDoctorDropdown() {
    const dropdown = document.getElementById('customPlanDoctorDropdown');
    if (dropdown && dropdown.style.display !== 'none') {
        closeCustomPlanDoctorDropdown();
    } else {
        openCustomPlanDoctorDropdown();
    }
}

function handleCustomPlanDoctorSelect(doctorId) {
    const select = document.getElementById('doctorSelect');
    if (!select) return;
    select.value = doctorId;
    syncCustomPlanDoctorTrigger(doctorId);
    closeCustomPlanDoctorDropdown();
    onDoctorChange();
}

let currentDoctorBusyDates = [];

async function onDoctorChange() {
    const doctorSelect = document.getElementById('doctorSelect');
    const doctorId = doctorSelect.value;
    const callListSelect = document.getElementById('callListSelect');
    const selectedOption = callListSelect.options[callListSelect.selectedIndex];
    const periodMonth = selectedOption ? selectedOption.getAttribute('data-period') : '';
    const noticeEl = document.getElementById('availabilityNotice');
    const recNotice = document.getElementById('doctorRecNotice');
    const recProductEl = document.getElementById('doctorRecProduct');

    const selectedDocOption = doctorSelect.options[doctorSelect.selectedIndex];
    const recProduct = selectedDocOption ? selectedDocOption.getAttribute('data-rec-product') : null;

    if (recNotice) {
        if (recProduct) {
            recNotice.style.display = 'flex';
            if (recProductEl) recProductEl.textContent = recProduct;
        } else {
            recNotice.style.display = 'none';
        }
    }

    currentDoctorBusyDates = [];
    if (!doctorId || !periodMonth) {
        noticeEl.style.display = 'none';
        return;
    }

    noticeEl.style.display = 'block';
    noticeEl.className = 'plan-availability is-loading';
    noticeEl.innerHTML = 'Memeriksa ketersediaan jadwal dokter...';

    try {
        const res = await window.apiFetch(`/api/doctors/${doctorId}/availability?month=${periodMonth}`);
        const result = await res.json();
        if (result.success && result.data) {
            currentDoctorBusyDates = result.data.busy_dates || [];
            renderAvailabilityNotice();
            onPlanDateChange();
        } else {
            noticeEl.style.display = 'none';
        }
    } catch (err) {
        noticeEl.style.display = 'none';
    }
}

function renderAvailabilityNotice() {
    const noticeEl = document.getElementById('availabilityNotice');
    if (!currentDoctorBusyDates.length) {
        noticeEl.style.display = 'block';
        noticeEl.className = 'plan-availability is-available';
        noticeEl.innerHTML = 'Tersedia. Seluruh tanggal pada bulan ini masih tersedia untuk target dokter ini.';
        return;
    }

    const formattedDays = currentDoctorBusyDates.map(d => parseInt(d.split('-')[2])).join(', ');
    noticeEl.style.display = 'block';
    noticeEl.className = 'plan-availability is-busy';
    noticeEl.innerHTML = `<strong>Tanggal terisi:</strong> Tanggal ${formattedDays} sudah ada jadwal/kunjungan dokter ini. Pilih tanggal lain agar tidak bentrok.`;
}

function onPlanDateChange() {
    const planDateInput = document.getElementById('planDateInput');
    const errEl = document.getElementById('createPlanModalError');
    const btn = document.getElementById('btnSubmitPlan');
    const previewEl = document.getElementById('planSelectedDateText');
    const selectedDate = planDateInput.value;

    if (previewEl) {
        if (selectedDate) {
            previewEl.style.display = 'inline-flex';
            previewEl.innerHTML = `<i class="bi bi-calendar-check plan-date-preview-icon"></i> Tanggal terpilih: <strong>${formatDateLabel(selectedDate)}</strong>`;
        } else {
            previewEl.style.display = 'none';
        }
    }

    if (!selectedDate || !currentDoctorBusyDates.length) {
        if (errEl.innerText.includes('sudah memiliki jadwal')) {
            errEl.style.display = 'none';
        }
        btn.disabled = false;
        return;
    }

    if (currentDoctorBusyDates.includes(selectedDate)) {
        errEl.innerText = `Dokter sudah memiliki jadwal/kunjungan pada tanggal ${formatDateLabel(selectedDate)}. Silakan pilih tanggal lain.`;
        errEl.style.display = 'block';
        btn.disabled = true;
    } else {
        if (errEl.innerText.includes('sudah memiliki jadwal')) {
            errEl.style.display = 'none';
        }
        btn.disabled = false;
    }
}

let conflictCallback = null;
let conflictSourceInput = null;
let bypassCreateTimeConflict = false;
let bypassRescheduleTimeConflict = false;

function showConflictModal(conflictingPlan, timeStr, onConfirm, sourceInput) {
    conflictCallback = onConfirm;
    conflictSourceInput = sourceInput;

    const timeEl = document.getElementById('conflictModalTime');
    const docEl = document.getElementById('conflictModalDoctor');
    const facEl = document.getElementById('conflictModalFacility');
    const modal = document.getElementById('planTimeConflictModal');

    if (timeEl) timeEl.innerText = formatWaktuIndonesia(timeStr);
    if (docEl) docEl.innerText = conflictingPlan.doctor_name || 'Dokter';
    if (facEl) facEl.innerText = conflictingPlan.doctor_address || '-';
    if (modal) modal.style.display = 'flex';
}

function closeConflictModal() {
    const modal = document.getElementById('planTimeConflictModal');
    if (modal) modal.style.display = 'none';
    conflictCallback = null;
    conflictSourceInput = null;
}

function modifyConflictTime() {
    const input = conflictSourceInput;
    closeConflictModal();
    if (input) {
        input.focus();
        if (typeof input.select === 'function') input.select();
    }
}

function openCreatePlanModal() {
    const errEl = document.getElementById('createPlanModalError');
    if (errEl) {
        errEl.style.display = 'none';
        errEl.innerText = '';
    }
    const noticeEl = document.getElementById('availabilityNotice');
    if (noticeEl) {
        noticeEl.style.display = 'none';
        noticeEl.innerHTML = '';
    }
    const recNotice = document.getElementById('doctorRecNotice');
    if (recNotice) {
        recNotice.style.display = 'none';
    }
    currentDoctorBusyDates = [];
    bypassCreateTimeConflict = false;
    document.getElementById('createPlanModal').style.display = 'flex';
    loadApprovedCallLists();
}

function closeCreatePlanModal() {
    closeCustomPlanDoctorDropdown();
    const recNotice = document.getElementById('doctorRecNotice');
    if (recNotice) {
        recNotice.style.display = 'none';
    }
    bypassCreateTimeConflict = false;
    document.getElementById('createPlanModal').style.display = 'none';
}

async function handleCreatePlanSubmit(e) {
    e.preventDefault();
    const errEl = document.getElementById('createPlanModalError');
    errEl.style.display = 'none';

    const callListId = document.getElementById('callListSelect').value;
    const doctorId = document.getElementById('doctorSelect').value;
    let plannedTime = (document.getElementById('plannedTimeInput').value || '').trim().replace('.', ':');
    if (/^\d:\d{2}/.test(plannedTime)) {
        plannedTime = '0' + plannedTime;
    }

    if (!callListId) {
        errEl.innerText = 'Pilih target dokter bulanan yang sudah disetujui.';
        errEl.style.display = 'block';
        return;
    }

    if (!doctorId) {
        errEl.innerText = 'Pilih target dokter.';
        errEl.style.display = 'block';
        return;
    }

    const planDate = (document.getElementById('planDateInput')?.value || '').trim();

    if (!planDate) {
        errEl.innerText = 'Tanggal wajib diisi.';
        errEl.style.display = 'block';
        return;
    }

    // Cek potensi bentrok jam kunjungan dengan dokter lain pada tanggal yang sama
    if (!bypassCreateTimeConflict) {
        try {
            const checkRes = await window.apiFetch(`/api/call-plans?date=${encodeURIComponent(planDate)}&status=scheduled`);
            const checkData = await checkRes.json();
            if (checkRes.ok && checkData.success && Array.isArray(checkData.data)) {
                const normTargetTime = plannedTime.substring(0, 5);
                const conflictingPlan = checkData.data.find(p => {
                    const normPTime = (p.planned_time || '').substring(0, 5).replace('.', ':');
                    return normPTime === normTargetTime && String(p.doctor_id) !== String(doctorId);
                });

                if (conflictingPlan) {
                    showConflictModal(conflictingPlan, plannedTime, () => {
                        bypassCreateTimeConflict = true;
                        handleCreatePlanSubmit(e);
                    }, document.getElementById('plannedTimeInput'));
                    return;
                }
            }
        } catch (checkErr) {
            console.warn('Gagal memeriksa jadwal bersamaan:', checkErr);
        }
    }

    bypassCreateTimeConflict = false;
    const btn = document.getElementById('btnSubmitPlan');
    btn.disabled = true;
    btn.innerText = 'Menyimpan...';

    try {
        const res = await window.apiFetch('/api/call-plans', {
            method: 'POST',
            body: JSON.stringify({
                call_list_id: parseInt(callListId),
                doctor_id: parseInt(doctorId),
                plan_date: planDate,
                planned_time: plannedTime
            })
        });
        const data = await res.json();
        btn.disabled = false;
        btn.innerText = 'Simpan';

        if (res.ok && data.success) {
            closeCreatePlanModal();
            showAlert('Jadwal disimpan.');
            loadPlans();
        } else {
            errEl.innerText = window.describeApiError(data || res, 'Gagal menyimpan jadwal.', res.status);
            errEl.style.display = 'block';
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerText = 'Simpan';
        errEl.innerText = window.describeApiError(err, 'Terjadi kesalahan jaringan.');
        errEl.style.display = 'block';
    }
}

function openCancelPlanModal(planId) {
    const plan = currentPlans.find(p => p.id === planId);
    if (!plan) return;

    document.getElementById('cancelPlanId').value = plan.id;
    document.getElementById('cancelPlanDoctorName').innerText = plan.doctor_name || 'Dokter';
    document.getElementById('cancelPlanDate').innerText = plan.plan_date || '-';
    document.getElementById('cancelPlanReasonInput').value = '';
    const errEl = document.getElementById('cancelPlanModalError');
    errEl.style.display = 'none';
    errEl.innerText = '';
    document.getElementById('cancelPlanModal').style.display = 'flex';
}

function closeCancelPlanModal() {
    document.getElementById('cancelPlanModal').style.display = 'none';
}

async function handleCancelPlanSubmit(e) {
    e.preventDefault();
    const planId = document.getElementById('cancelPlanId').value;
    const reason = document.getElementById('cancelPlanReasonInput').value.trim();
    const errEl = document.getElementById('cancelPlanModalError');
    const btn = document.getElementById('btnConfirmCancelPlan');

    errEl.style.display = 'none';

    if (reason.length < 3) {
        errEl.innerText = 'Alasan pembatalan wajib diisi minimal 3 karakter.';
        errEl.style.display = 'block';
        return;
    }

    btn.disabled = true;
    btn.innerText = 'Membatalkan...';

    try {
        const res = await window.apiFetch(`/api/call-plans/${planId}/cancel`, {
            method: 'PATCH',
            body: JSON.stringify({ reason: reason })
        });
        const result = await res.json();
        btn.disabled = false;
        btn.innerText = 'Batalkan Jadwal';

        if (res.ok && result.success) {
            closeCancelPlanModal();
            showAlert('Jadwal dibatalkan.');
            loadPlans();
        } else {
            errEl.innerText = window.describeApiError(result || res, 'Gagal membatalkan jadwal.', res.status);
            errEl.style.display = 'block';
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerText = 'Batalkan Jadwal';
        errEl.innerText = window.describeApiError(err, 'Gagal membatalkan jadwal. Coba lagi.');
        errEl.style.display = 'block';
    }
}

function openPlanDetailModal(planId) {
    const plan = currentPlans.find(p => p.id === planId);
    if (!plan) return;

    document.getElementById('planDetailDoctorName').innerText = plan.doctor_name || 'Dokter';
    document.getElementById('planDetailDoctorSub').innerText = `${plan.doctor_specialization || 'Spesialis Umum'} - ${plan.doctor_address || '-'}`;

    const dateObj = new Date(plan.plan_date + 'T' + plan.planned_time);
    const dateStr = formatDateLabel(plan.plan_date);
    const timeStr = formatWaktuIndonesia(plan.planned_time);
    document.getElementById('planDetailSchedule').innerText = `${dateStr} pukul ${timeStr}`;

    document.getElementById('planDetailReason').innerText = plan.cancellation_reason || '-';

    const cancelledAtEl = document.getElementById('planDetailCancelledAt');
    const cancelledAtWrap = document.getElementById('planDetailCancelledAtWrap');
    if (plan.cancelled_at) {
        const cDate = new Date(plan.cancelled_at);
        cancelledAtEl.innerText = formatDateTimeLabel(plan.cancelled_at);
        cancelledAtWrap.style.display = 'block';
    } else {
        cancelledAtWrap.style.display = 'none';
    }

    document.getElementById('planDetailModal').style.display = 'flex';
}

function closePlanDetailModal() {
    document.getElementById('planDetailModal').style.display = 'none';
}

let currentRescheduleDoctorBusyDates = [];

async function openReschedulePlanModal(planId) {
    const plan = currentPlans.find(p => p.id === planId);
    if (!plan) return;

    document.getElementById('reschedulePlanId').value = plan.id;
    document.getElementById('rescheduleDoctorId').value = plan.doctor_id;
    document.getElementById('reschedulePlanDoctorName').innerText = plan.doctor_name || 'Dokter';

    const timeStr = formatWaktuIndonesia(plan.planned_time);
    const dateStr = formatDateLabel(plan.plan_date);
    document.getElementById('reschedulePlanCurrentSchedule').innerText = `${dateStr} pukul ${timeStr}`;

    const dateInput = document.getElementById('reschedulePlanDateInput');
    const [year, mm] = (plan.plan_date || '').substring(0, 7).split('-');
    const lastDay = new Date(parseInt(year, 10), parseInt(mm, 10), 0).getDate();

    const today = new Date();
    const yyyyNow = today.getFullYear();
    const mmNow = String(today.getMonth() + 1).padStart(2, '0');
    const ddNow = String(today.getDate()).padStart(2, '0');
    const todayStr = `${yyyyNow}-${mmNow}-${ddNow}`;
    const monthMin = `${year}-${mm}-01`;
    const effectiveMin = todayStr > monthMin ? todayStr : monthMin;

    dateInput.min = effectiveMin;
    dateInput.max = `${year}-${mm}-${String(lastDay).padStart(2, '0')}`;
    dateInput.value = plan.plan_date >= effectiveMin ? plan.plan_date : effectiveMin;

    const timeInput = document.getElementById('reschedulePlannedTimeInput');
    timeInput.value = plan.planned_time ? plan.planned_time.substring(0, 5).replace(':', '.') : '09.00';

    document.getElementById('reschedulePlanReasonInput').value = '';
    const errEl = document.getElementById('reschedulePlanModalError');
    errEl.style.display = 'none';
    errEl.innerText = '';

    document.getElementById('reschedulePlanModal').style.display = 'flex';

    const noticeEl = document.getElementById('rescheduleAvailabilityNotice');
    noticeEl.style.display = 'block';
    noticeEl.className = 'plan-availability';
    noticeEl.innerHTML = 'Memeriksa ketersediaan jadwal dokter...';
    try {
        const periodMonth = `${year}-${mm}`;
        const res = await window.apiFetch(`/api/doctors/${plan.doctor_id}/availability?month=${periodMonth}`);
        const result = await res.json();
        if (result.success && result.data) {
            currentRescheduleDoctorBusyDates = (result.data.busy_dates || []).filter(d => d !== plan.plan_date);
            renderRescheduleAvailabilityNotice();
            onRescheduleDateChange();
        } else {
            noticeEl.style.display = 'none';
        }
    } catch (err) {
        noticeEl.style.display = 'none';
    }
}

function renderRescheduleAvailabilityNotice() {
    const noticeEl = document.getElementById('rescheduleAvailabilityNotice');
    if (!currentRescheduleDoctorBusyDates.length) {
        noticeEl.style.display = 'block';
        noticeEl.className = 'plan-availability is-available';
        noticeEl.innerHTML = 'Tersedia. Tanggal lain pada bulan ini masih belum terisi.';
        return;
    }

    const formattedDays = currentRescheduleDoctorBusyDates.map(d => parseInt(d.split('-')[2])).join(', ');
    noticeEl.style.display = 'block';
    noticeEl.className = 'plan-availability is-busy';
    noticeEl.innerHTML = `<strong>Tanggal terisi:</strong> Tanggal ${formattedDays} sudah ada jadwal/kunjungan dokter ini. Pilih tanggal lain agar tidak bentrok.`;
}

function onRescheduleDateChange() {
    const dateInput = document.getElementById('reschedulePlanDateInput');
    const previewEl = document.getElementById('rescheduleSelectedDateText');
    const selectedDate = dateInput.value;
    const btn = document.getElementById('btnConfirmReschedulePlan');
    const errEl = document.getElementById('reschedulePlanModalError');

    if (previewEl) {
        if (selectedDate) {
            previewEl.style.display = 'inline-flex';
            previewEl.innerHTML = `<i class="bi bi-calendar-check plan-date-preview-icon"></i> Tanggal baru: <strong>${formatDateLabel(selectedDate)}</strong>`;
        } else {
            previewEl.style.display = 'none';
        }
    }

    if (currentRescheduleDoctorBusyDates.includes(selectedDate)) {
        errEl.innerText = `Dokter ini sudah memiliki jadwal pada tanggal ${formatDateLabel(selectedDate)}. Pilih tanggal lain.`;
        errEl.style.display = 'block';
        btn.disabled = true;
    } else {
        errEl.style.display = 'none';
        btn.disabled = false;
    }
}

function closeReschedulePlanModal() {
    bypassRescheduleTimeConflict = false;
    document.getElementById('reschedulePlanModal').style.display = 'none';
}

async function handleReschedulePlanSubmit(e) {
    e.preventDefault();
    const planId = document.getElementById('reschedulePlanId').value;
    const planDate = document.getElementById('reschedulePlanDateInput').value;
    const plannedTime = document.getElementById('reschedulePlannedTimeInput').value.replace('.', ':');
    const reason = document.getElementById('reschedulePlanReasonInput').value.trim();
    const errEl = document.getElementById('reschedulePlanModalError');
    const btn = document.getElementById('btnConfirmReschedulePlan');

    errEl.style.display = 'none';

    if (reason.length < 3) {
        errEl.innerText = 'Alasan reschedule wajib diisi minimal 3 karakter.';
        errEl.style.display = 'block';
        return;
    }

    // Cek potensi bentrok jam kunjungan dengan jadwal dokter lain
    if (!bypassRescheduleTimeConflict) {
        try {
            const checkRes = await window.apiFetch(`/api/call-plans?date=${encodeURIComponent(planDate)}&status=scheduled`);
            const checkData = await checkRes.json();
            if (checkRes.ok && checkData.success && Array.isArray(checkData.data)) {
                const normTargetTime = plannedTime.substring(0, 5);
                const conflictingPlan = checkData.data.find(p => {
                    const normPTime = (p.planned_time || '').substring(0, 5).replace('.', ':');
                    return normPTime === normTargetTime && String(p.id) !== String(planId);
                });

                if (conflictingPlan) {
                    showConflictModal(conflictingPlan, plannedTime, () => {
                        bypassRescheduleTimeConflict = true;
                        handleReschedulePlanSubmit(e);
                    }, document.getElementById('reschedulePlannedTimeInput'));
                    return;
                }
            }
        } catch (checkErr) {
            console.warn('Gagal memeriksa jadwal bersamaan saat reschedule:', checkErr);
        }
    }

    bypassRescheduleTimeConflict = false;
    btn.disabled = true;
    btn.innerText = 'Menyimpan...';

    try {
        const res = await window.apiFetch(`/api/call-plans/${planId}/reschedule`, {
            method: 'PATCH',
            body: JSON.stringify({
                plan_date: planDate,
                planned_time: plannedTime,
                reason: reason
            })
        });
        const result = await res.json();
        btn.disabled = false;
        btn.innerText = 'Simpan Perubahan Jadwal';

        if (res.ok && result.success) {
            closeReschedulePlanModal();
            showAlert('Jadwal berhasil di-reschedule.');
            loadPlans();
        } else {
            errEl.innerText = window.describeApiError(result || res, 'Gagal reschedule jadwal.', res.status);
            errEl.style.display = 'block';
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerText = 'Simpan Perubahan Jadwal';
        errEl.innerText = window.describeApiError(err, 'Terjadi kesalahan jaringan.');
        errEl.style.display = 'block';
    }
}
