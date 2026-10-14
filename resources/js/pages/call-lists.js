const pageEl = document.querySelector('[data-call-lists-page]');
const currentUserRole = pageEl?.dataset.currentUserRole || '';
const currentUserDistrict = pageEl?.dataset.currentUserDistrict || '';
let allDoctors = [];
let myCallLists = [];
let myCallListsLoaded = false;
let reviewCallLists = [];
let allCampaigns = [];
let doctorRecommendationsMap = {};
let masterProducts = [];
let campaignSelectedDoctorIds = new Set();
let currentSelectedDoctorIds = new Set();
let pickerCurrentPage = 1;
let isFilterOnlySelected = false;
let pendingConfirmResolve = null;
const escapeHtml = window.MSF.escapeHtml;
const formatPeriodIndonesian = window.MSF.formatPeriodIndonesian;

document.addEventListener('DOMContentLoaded', () => {
        bindCallListEvents();
        loadDoctors();
        if (currentUserRole === 'mr') {
            loadMyCallLists();
        } else if (currentUserRole === 'dm') {
            loadReviewCallLists();
            loadManagerSummary();
        } else {
            // RSM dan MM: langsung muat program rekomendasi & executive summary
            loadCampaigns();
        }
    });

document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeTargetModal();
            closeDetailModal();
            closeRejectModal();
            closeConfirmModal(false);
        }
    });

function showAlert(message, type = 'success') {
        window.showToast(message, type);
    }

function requestConfirmation(message, title = 'Konfirmasi Aksi') {
        const modal = document.getElementById('confirmModal');
        const titleEl = document.getElementById('confirmModalTitle');
        const messageEl = document.getElementById('confirmModalMessage');

        if (!modal || !titleEl || !messageEl) {
            showAlert('Dialog konfirmasi tidak tersedia. Muat ulang halaman lalu coba lagi.', 'danger');
            return Promise.resolve(false);
        }

        if (pendingConfirmResolve) {
            pendingConfirmResolve(false);
        }

        titleEl.innerText = title;
        messageEl.innerText = message;
        modal.style.display = 'flex';

        return new Promise((resolve) => {
            pendingConfirmResolve = resolve;
        });
    }

function closeConfirmModal(confirmed = false) {
        const modal = document.getElementById('confirmModal');
        if (modal) modal.style.display = 'none';

        if (pendingConfirmResolve) {
            pendingConfirmResolve(confirmed);
            pendingConfirmResolve = null;
        }
    }

function bindCallListEvents() {
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-action]');
            if (!trigger) return;

            const callListId = trigger.dataset.callListId ? parseInt(trigger.dataset.callListId, 10) : null;

            switch (trigger.dataset.action) {
                case 'open-create':
                    openCreateModal();
                    break;
                case 'switch-tab':
                    switchTab(trigger.dataset.tab);
                    break;
                case 'close-target':
                    closeTargetModal();
                    break;
                case 'toggle-selected-filter':
                    toggleShowOnlySelected();
                    break;
                case 'search-doctor':
                    onDoctorSearchClick();
                    break;
                case 'save-and-submit':
                    handleSaveAndSubmit();
                    break;
                case 'close-detail':
                    closeDetailModal();
                    break;
                case 'close-reject':
                    closeRejectModal();
                    break;
                case 'close-confirm':
                    closeConfirmModal(false);
                    break;
                case 'confirm-action':
                    closeConfirmModal(true);
                    break;
                case 'open-detail':
                    if (callListId) openDetailModal(callListId);
                    break;
                case 'open-edit':
                    if (callListId) openEditModal(callListId);
                    break;
                case 'submit-call-list':
                    if (callListId) submitCallList(callListId);
                    break;
                case 'approve-call-list':
                    if (callListId) approveCallList(callListId);
                    break;
                case 'open-reject':
                    if (callListId) openRejectModal(callListId);
                    break;
                case 'toggle-doctor-selection':
                    event.preventDefault();
                    event.stopPropagation();
                    toggleDoctorSelection(parseInt(trigger.dataset.doctorId, 10));
                    break;
                case 'open-create-campaign':
                    openCreateCampaignModal();
                    break;
                case 'close-create-campaign':
                    closeCreateCampaignModal();
                    break;
                case 'show-unvisited-doctors':
                    if (trigger.dataset.campaignId) showUnvisitedDoctors(parseInt(trigger.dataset.campaignId, 10));
                    break;
                case 'close-unvisited-modal':
                    closeUnvisitedModal();
                    break;
                case 'approve-campaign':
                    if (trigger.dataset.campaignId) handleApproveCampaign(parseInt(trigger.dataset.campaignId, 10));
                    break;
                case 'open-reject-campaign':
                    if (trigger.dataset.campaignId) openRejectCampaignModal(parseInt(trigger.dataset.campaignId, 10));
                    break;
                case 'select-all-campaign-docs':
                    event.preventDefault();
                    selectAllCampaignDoctors();
                    break;
                case 'clear-all-campaign-docs':
                    event.preventDefault();
                    clearAllCampaignDoctors();
                    break;
                case 'retry-load-my-lists':
                    loadMyCallLists();
                    break;
                case 'retry-load-review':
                    loadReviewCallLists();
                    break;
                case 'retry-manager-summary':
                    loadExecutiveSummary();
                    break;
                default:
                    break;
            }
        });

        // Global delegated change listener for campaign checkboxes
        document.addEventListener('change', (event) => {
            if (event.target && event.target.name === 'campaign_product') {
                onCampaignProductToggle(event.target);
            }
            if (event.target && event.target.name === 'campaign_doctor') {
                onCampaignDoctorToggle(event.target);
            }
        });

        // Global delegated input listener for doctor search
        document.addEventListener('input', (event) => {
            if (event.target && event.target.id === 'campaignDoctorSearchInput') {
                renderCampaignDoctors();
            }
        });

        document.getElementById('targetForm')?.addEventListener('submit', handleTargetFormSubmit);
        document.getElementById('rejectForm')?.addEventListener('submit', handleRejectSubmit);
        document.getElementById('createCampaignForm')?.addEventListener('submit', handleCreateCampaignSubmit);
        document.getElementById('rejectCampaignForm')?.addEventListener('submit', handleRejectCampaignSubmit);
        document.getElementById('campaignSpecSelect')?.addEventListener('change', renderCampaignDoctors);
        document.getElementById('campaignRsmGuidanceSelect')?.addEventListener('change', handleRsmGuidanceChange);
        document.getElementById('campaignDoctorSearchInput')?.addEventListener('input', renderCampaignDoctors);
        document.getElementById('campaignDoctorSearchInput')?.addEventListener('keyup', renderCampaignDoctors);
        document.getElementById('btnSelectAllCampaignDocs')?.addEventListener('click', (e) => {
            e.preventDefault();
            selectAllCampaignDoctors();
        });
        document.getElementById('btnClearAllCampaignDocs')?.addEventListener('click', (e) => {
            e.preventDefault();
            clearAllCampaignDoctors();
        });
        document.getElementById('doctorSearchInput')?.addEventListener('input', (event) => onDoctorSearchInput(event.target.value));
        document.getElementById('doctorSearchInput')?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                onDoctorSearchClick();
            }
        });
        document.getElementById('summaryMonthSelect')?.addEventListener('change', loadManagerSummary);
        document.getElementById('periodMonthSelect')?.addEventListener('change', syncPeriodToHiddenInput);
        document.getElementById('periodYearSelect')?.addEventListener('change', syncPeriodToHiddenInput);
    }

function syncPeriodToHiddenInput() {
        const monthSelect = document.getElementById('periodMonthSelect');
        const yearSelect = document.getElementById('periodYearSelect');
        const hidden = document.getElementById('periodMonthInput');
        if (monthSelect && yearSelect && hidden) {
            hidden.value = `${yearSelect.value}-${monthSelect.value}`;
            showPeriodConflict(hidden.value);
        }
    }

function showPeriodConflict(period) {
    const editingId = document.getElementById('editingCallListId')?.value;
    const exists = !editingId && myCallLists.some((list) => list.period_month.slice(0, 7) === period);
    const errorEl = document.getElementById('targetModalError');

    if (!errorEl) return exists;

    errorEl.innerText = exists
        ? 'Target Dokter untuk periode ini sudah ada. Pilih periode lain atau ubah daftar yang sudah tersedia.'
        : '';
    errorEl.style.display = exists ? 'block' : 'none';

    return exists;
}

function switchTab(tab, isManual = true) {
        if (isManual) {
            sessionStorage.setItem('call_list_tab_manual', '1');
        }
        if (document.getElementById('tabMyLists')) document.getElementById('tabMyLists').classList.remove('active');
        if (document.getElementById('tabReview')) document.getElementById('tabReview').classList.remove('active');
        if (document.getElementById('tabCampaigns')) document.getElementById('tabCampaigns').classList.remove('active');

        const myListsEl = document.getElementById('myListsContainer');
        const reviewEl = document.getElementById('reviewContainer');
        const campEl = document.getElementById('campaignsContainer');

        if (tab === 'myLists') {
            if (document.getElementById('tabMyLists')) document.getElementById('tabMyLists').classList.add('active');
            if (myListsEl) myListsEl.style.display = 'flex';
            if (reviewEl) reviewEl.style.display = 'none';
            if (campEl) campEl.style.display = 'none';
        } else if (tab === 'campaigns') {
            if (document.getElementById('tabCampaigns')) document.getElementById('tabCampaigns').classList.add('active');
            if (myListsEl) myListsEl.style.display = 'none';
            if (reviewEl) reviewEl.style.display = 'none';
            if (campEl) {
                campEl.style.display = 'flex';
                loadCampaigns();
            }
        } else {
            if (document.getElementById('tabReview')) document.getElementById('tabReview').classList.add('active');
            if (myListsEl) myListsEl.style.display = 'none';
            if (reviewEl) {
                reviewEl.style.display = 'flex';
                loadReviewCallLists();
                loadManagerSummary();
            }
            if (campEl) campEl.style.display = 'none';
        }
    }

async function loadDoctors() {
        try {
            const res = await window.apiFetch('/api/doctors');
            const data = await res.json();
            if (data.success) {
                allDoctors = data.data;
            }

            // Muat rekomendasi dokter aktif untuk distrik ini
            try {
                const recRes = await window.apiFetch('/api/managerial-campaigns/recommendations');
                const recData = await recRes.json();
                if (recData.success && Array.isArray(recData.data)) {
                    doctorRecommendationsMap = {};
                    recData.data.forEach((item) => {
                        doctorRecommendationsMap[item.doctor_id] = item;
                    });
                }
            } catch (recErr) {
                console.warn('Gagal memuat data rekomendasi dokter', recErr);
            }
        } catch (err) {
            console.error('Gagal memuat dokter', err);
        }
    }

async function loadMyCallLists() {
    if (currentUserRole !== 'mr') return;

    const loadingEl = document.getElementById('loadingMyLists');
        const errorEl = document.getElementById('errorMyLists');
        if (errorEl) errorEl.style.display = 'none';

        if (loadingEl) {
            loadingEl.innerHTML = window.renderTableSkeleton(3);
            loadingEl.style.display = 'block';
        }
        try {
            const res = await window.apiFetch('/api/call-lists');
            const result = await res.json();
            if (loadingEl) loadingEl.style.display = 'none';

            if (res.ok && result.success && Array.isArray(result.data)) {
                myCallLists = result.data;
                myCallListsLoaded = true;
                const countEl = document.getElementById('myListCount');
                if (countEl) countEl.innerText = myCallLists.length;
                renderMyListsView();
            } else {
                const msg = window.describeApiError(result || res, 'Gagal memuat target dokter dari server.', res.status);
                showMyListsError(msg);
            }
        } catch (err) {
            if (loadingEl) loadingEl.style.display = 'none';
            const msg = window.describeApiError(err, 'Terjadi gangguan jaringan saat memuat target dokter.');
            showMyListsError(msg);
        }
    }

    function showMyListsError(message) {
        const spotlight = document.getElementById('currentMonthSpotlight');
        const tableWrap = document.getElementById('myListsTableWrap');
        const empty = document.getElementById('emptyMyLists');
        const errorEl = document.getElementById('errorMyLists');
        const errorText = document.getElementById('errorMyListsText');

        if (spotlight) spotlight.style.display = 'none';
        if (tableWrap) tableWrap.style.display = 'none';
        if (empty) empty.style.display = 'none';

        if (errorEl) {
            if (errorText) errorText.innerText = message;
            errorEl.style.display = 'block';
        } else {
            showAlert(message, 'danger');
        }
    }

function renderMyListsView() {
        const spotlight = document.getElementById('currentMonthSpotlight');
        const tableWrap = document.getElementById('myListsTableWrap');
        const tbody = document.getElementById('myListsTableBody');
        const empty = document.getElementById('emptyMyLists');
        const errorEl = document.getElementById('errorMyLists');

        if (errorEl) errorEl.style.display = 'none';

        if (!myCallLists.length) {
            if (spotlight) spotlight.style.display = 'none';
            if (tableWrap) tableWrap.style.display = 'none';
            if (empty) empty.style.display = 'block';
            return;
        }

        if (empty) empty.style.display = 'none';

        // 1. Render Kartu Target Periode Berjalan / Utama
        const activeList = myCallLists[0];
        if (spotlight && activeList) {
            spotlight.style.display = 'block';
            document.getElementById('spotlightPeriod').innerText = formatPeriodIndonesian(activeList.period_month);
            document.getElementById('spotlightDoctorCount').innerText = activeList.doctor_count;
            document.getElementById('spotlightApprover').innerText = activeList.assigned_approver_name || 'Atasan Langsung';

            const badgeEl = document.getElementById('spotlightStatusBadge');
            const btnsEl = document.getElementById('spotlightActionBtns');
            const rejBox = document.getElementById('spotlightRejectionBox');
            const quickAddWrap = document.getElementById('spotlightQuickAddBtnWrap');
            const doctorsTableBody = document.getElementById('spotlightDoctorsTableBody');

            let badgeHtml = '';
            let btnsHtml = '';

            if (activeList.status === 'approved') {
                badgeHtml = `<span class="target-title-tag target-tag-approved">Disetujui</span>`;
                btnsHtml = `
                    <button class="btn btn-outline btn-sm" data-action="open-detail" data-call-list-id="${activeList.id}">Lihat Detail</button>
                    <a href="/call-plans" class="btn btn-primary btn-sm target-link-button">Jadwalkan Kunjungan</a>
                `;
                if (rejBox) rejBox.style.display = 'none';
                if (quickAddWrap) quickAddWrap.innerHTML = '';
            } else if (activeList.status === 'pending_approval') {
                badgeHtml = `<span class="target-title-tag target-tag-pending">Menunggu Persetujuan</span>`;
                btnsHtml = `
                    <button class="btn btn-outline btn-sm" data-action="open-detail" data-call-list-id="${activeList.id}">Lihat Detail</button>
                `;
                if (rejBox) rejBox.style.display = 'none';
                if (quickAddWrap) quickAddWrap.innerHTML = '';
            } else if (activeList.status === 'draft' && activeList.is_rejected_revision) {
                badgeHtml = `<span class="target-title-tag target-tag-rejected">Perlu Revisi</span>`;
                btnsHtml = `
                    <button class="btn btn-outline btn-sm" data-action="open-detail" data-call-list-id="${activeList.id}">Lihat Detail</button>
                    <button class="btn btn-primary btn-sm" data-action="submit-call-list" data-call-list-id="${activeList.id}">Ajukan Ulang ke DM</button>
                `;
                if (rejBox) {
                    rejBox.style.display = 'block';
                    document.getElementById('spotlightRejectionReason').innerText = activeList.latest_rejection_reason || 'Perlu penyesuaian daftar dokter target.';
                }
                if (quickAddWrap) {
                    quickAddWrap.innerHTML = `
                        <button type="button" class="btn btn-outline btn-sm" data-action="open-edit" data-call-list-id="${activeList.id}">
                            Kelola Target Dokter
                        </button>
                    `;
                }
            } else {
                badgeHtml = `<span class="target-title-tag target-tag-draft">Draft</span>`;
                btnsHtml = `
                    <button class="btn btn-outline btn-sm" data-action="open-detail" data-call-list-id="${activeList.id}">Detail</button>
                    <button class="btn btn-primary btn-sm" data-action="submit-call-list" data-call-list-id="${activeList.id}">Ajukan ke DM</button>
                `;
                if (rejBox) rejBox.style.display = 'none';
                if (quickAddWrap) {
                    quickAddWrap.innerHTML = `
                        <button type="button" class="btn btn-outline btn-sm" data-action="open-edit" data-call-list-id="${activeList.id}">
                            Kelola Target Dokter
                        </button>
                    `;
                }
            }

            badgeEl.innerHTML = badgeHtml;
            btnsEl.innerHTML = btnsHtml;

            // Render Daftar Target Dokter pada Kartu
            const docs = activeList.doctors || [];
            if (doctorsTableBody) {
                if (docs.length === 0) {
                    doctorsTableBody.innerHTML = `
                        <tr>
                            <td colspan="4" class="target-empty-cell">
                                Belum ada dokter yang ditargetkan untuk periode ini.
                                ${activeList.status === 'draft' ? `<div class="target-row-action-space"><button type="button" class="btn btn-primary btn-sm" data-action="open-edit" data-call-list-id="${activeList.id}">Pilih Target Dokter</button></div>` : ''}
                            </td>
                        </tr>
                    `;
                } else {
                    doctorsTableBody.innerHTML = docs.map((doc, idx) => `
                        <tr>
                            <td class="target-number-cell">${idx + 1}</td>
                            <td>
                                <strong class="target-doctor-name">${escapeHtml(doc.name)}</strong>
                            </td>
                            <td>
                                <span class="badge target-specialty-badge">
                                    ${escapeHtml(doc.specialization || 'Umum')}
                                </span>
                            </td>
                            <td class="target-muted-cell">
                                ${escapeHtml(doc.address || '-')}
                            </td>
                        </tr>
                    `).join('');
                }
            }
        }

        // 2. Render Tabel Riwayat Periode Sebelumnya (Hanya jika ada periode selain yang aktif)
        if (myCallLists.length > 1) {
            if (tableWrap) tableWrap.style.display = 'block';
            if (tbody) {
                const previousLists = myCallLists.slice(1);
                tbody.innerHTML = previousLists.map(cl => {
                    let statusClass = `status-${cl.status === 'pending_approval' ? 'pending' : cl.status}`;
                    let statusLabel = cl.status === 'pending_approval' ? 'Menunggu Persetujuan' : (cl.status === 'approved' ? 'Disetujui' : 'Draft');
                    if (cl.status === 'draft' && cl.is_rejected_revision) {
                        statusClass = 'status-rejected';
                        statusLabel = 'Perlu Revisi';
                    }

                    return `
                        <tr>
                            <td class="target-nowrap-cell target-cell-left">
                                <strong class="target-doctor-name">${formatPeriodIndonesian(cl.period_month)}</strong>
                            </td>
                            <td class="target-nowrap-cell">
                                <strong class="target-table-strong">${cl.doctor_count} Dokter</strong>
                            </td>
                            <td>
                                <span class="status-badge ${statusClass}">${escapeHtml(statusLabel)}</span>
                            </td>
                            <td class="target-nowrap-cell target-main-cell">
                                <span>${escapeHtml(cl.assigned_approver_name || '-')}</span>
                            </td>
                            <td class="target-action-cell">
                                <div class="target-inline-actions">
                                    <button class="btn btn-outline btn-sm" data-action="open-detail" data-call-list-id="${cl.id}">Detail</button>
                                    ${cl.status === 'draft' ? `
                                        <button class="btn btn-outline btn-sm" data-action="open-edit" data-call-list-id="${cl.id}">Ubah</button>
                                        <button class="btn btn-primary btn-sm" data-action="submit-call-list" data-call-list-id="${cl.id}">Ajukan</button>
                                    ` : ''}
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        } else {
            if (tableWrap) tableWrap.style.display = 'none';
            if (tbody) tbody.innerHTML = '';
        }
    }

    // Modal Builder: Pemilihan Dokter yang Lega & Nyaman
    async function openCreateModal() {
        if (!myCallListsLoaded) {
            await loadMyCallLists();
        }

        if (!myCallListsLoaded) {
            showAlert('Daftar target belum berhasil dimuat. Muat ulang daftar sebelum membuat target baru.', 'danger');
            return;
        }

        document.getElementById('editingCallListId').value = '';
        document.getElementById('targetModalTitle').innerText = 'Buat Target Dokter';
        document.getElementById('targetModalSubtitle').innerText = 'Pilih target dokter dari Master Customer List';

        const periodInput = document.getElementById('periodMonthInput');
        if (periodInput) periodInput.disabled = false;

        // Start at the first period without an existing list.
        const now = new Date();
        const usedPeriods = new Set(myCallLists.map((list) => list.period_month.slice(0, 7)));
        let availableDate = new Date(now.getFullYear(), now.getMonth(), 1);
        for (let offset = 0; offset < 27; offset += 1) {
            const candidate = new Date(now.getFullYear(), now.getMonth() + offset, 1);
            const candidatePeriod = `${candidate.getFullYear()}-${String(candidate.getMonth() + 1).padStart(2, '0')}`;
            if (!usedPeriods.has(candidatePeriod)) {
                availableDate = candidate;
                break;
            }
        }
        const yyyy = String(availableDate.getFullYear());
        const mm = String(availableDate.getMonth() + 1).padStart(2, '0');
        const period = `${yyyy}-${mm}`;
        if (periodInput) periodInput.value = period;

        const monthSelect = document.getElementById('periodMonthSelect');
        const yearSelect = document.getElementById('periodYearSelect');
        if (monthSelect) { monthSelect.value = mm; monthSelect.disabled = false; }
        if (yearSelect) { yearSelect.value = yyyy; yearSelect.disabled = false; }

        currentSelectedDoctorIds = new Set();
        isFilterOnlySelected = false;
        pickerCurrentPage = 1;
        document.getElementById('doctorSearchInput').value = '';
        const btnFilter = document.getElementById('btnFilterToggleSelected');
        if (btnFilter) btnFilter.innerText = 'Tampilkan Terpilih';

        const errEl = document.getElementById('targetModalError');
        if (errEl) {
            errEl.style.display = 'none';
            errEl.innerText = '';
        }
        showPeriodConflict(period);

        if (!allDoctors.length) {
            await loadDoctors();
        }

        renderDoctorPickerList();
        updateSelectedCounter();
        document.getElementById('targetModal').style.display = 'flex';
    }

    async function openEditModal(callListId) {
        document.getElementById('editingCallListId').value = callListId;
        document.getElementById('targetModalTitle').innerText = 'Ubah Target Dokter';
        document.getElementById('targetModalSubtitle').innerText = 'Perbarui daftar target dokter untuk periode ini';

        const errEl = document.getElementById('targetModalError');
        if (errEl) errEl.style.display = 'none';

        if (!allDoctors.length) {
            await loadDoctors();
        }

        try {
            const res = await window.apiFetch(`/api/call-lists/${callListId}`);
            const result = await res.json();
            if (result.success) {
                const cl = result.data;
                const periodInput = document.getElementById('periodMonthInput');
                if (periodInput) {
                    periodInput.value = cl.period_month;
                    periodInput.disabled = true; // Periode tidak boleh diubah saat edit
                }

                if (cl.period_month) {
                    const [y, m] = cl.period_month.split('-');
                    const monthSelect = document.getElementById('periodMonthSelect');
                    const yearSelect = document.getElementById('periodYearSelect');
                    if (monthSelect) { monthSelect.value = m; monthSelect.disabled = true; }
                    if (yearSelect) { yearSelect.value = y; yearSelect.disabled = true; }
                }

                currentSelectedDoctorIds = new Set(cl.doctors.map(d => d.id));
                isFilterOnlySelected = false;
                pickerCurrentPage = 1;
                document.getElementById('doctorSearchInput').value = '';
                const btnFilterEdit = document.getElementById('btnFilterToggleSelected');
                if (btnFilterEdit) btnFilterEdit.innerText = 'Tampilkan Terpilih';

                renderDoctorPickerList();
                updateSelectedCounter();
                document.getElementById('targetModal').style.display = 'flex';
            }
        } catch (err) {
            showAlert('Gagal memuat data target untuk diedit', 'danger');
        }
    }

function closeTargetModal() {
        document.getElementById('targetModal').style.display = 'none';
    }

function renderDoctorPickerList() {
        const container = document.getElementById('doctorPickerList');
        const searchTerm = (document.getElementById('doctorSearchInput').value || '').toLowerCase().trim();

        if (!allDoctors.length) {
            container.innerHTML = '<span class="target-picker-loading">Memuat dokter...</span>';
            return;
        }

        let filtered = allDoctors.filter(doc => {
            const matchesSearch = doc.name.toLowerCase().includes(searchTerm) ||
                (doc.specialization || '').toLowerCase().includes(searchTerm) ||
                (doc.address || '').toLowerCase().includes(searchTerm);

            if (isFilterOnlySelected) {
                return matchesSearch && currentSelectedDoctorIds.has(doc.id);
            }
            return matchesSearch;
        });

        if (!filtered.length) {
            container.innerHTML = '<div class="target-picker-empty">Tidak ada dokter yang cocok dengan pencarian.</div>';
            return;
        }

        container.innerHTML = filtered.map(doc => {
            const isChecked = currentSelectedDoctorIds.has(doc.id);
            const rec = doctorRecommendationsMap[doc.id];
            const recBadge = rec ? `
                <span class="badge-priority"><i class="bi bi-stars"></i> Rekomendasi Manajer</span>
            ` : '';

            return `
                <div class="doctor-selectable-row ${isChecked ? 'selected' : ''}" data-action="toggle-doctor-selection" data-doctor-id="${doc.id}">
                    <input type="checkbox" ${isChecked ? 'checked' : ''} data-action="toggle-doctor-selection" data-doctor-id="${doc.id}">
                    <div class="target-picker-main">
                        <div class="target-picker-name-line">
                            <strong class="target-doctor-name">${escapeHtml(doc.name)}</strong>
                            <span class="target-specialty-badge target-specialty-compact">
                                ${escapeHtml(doc.specialization || 'Umum')}
                            </span>
                            ${recBadge}
                        </div>
                        <div class="target-picker-address">
                            <i class="bi bi-building" aria-hidden="true"></i>
                            <span>${escapeHtml(doc.address || '-')}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

function toggleDoctorSelection(doctorId, event) {
        if (currentSelectedDoctorIds.has(doctorId)) {
            currentSelectedDoctorIds.delete(doctorId);
        } else {
            currentSelectedDoctorIds.add(doctorId);
        }
        updateSelectedCounter();
        renderDoctorPickerList();
    }

function updateSelectedCounter() {
        const count = currentSelectedDoctorIds.size;
        document.getElementById('selectedDoctorCount').innerText = count;
        document.getElementById('footerCountText').innerText = count;
    }

function onDoctorSearchInput(val) {
        pickerCurrentPage = 1;
        renderDoctorPickerList();
    }

function onDoctorSearchClick(e) {
        if (e) e.preventDefault();
        pickerCurrentPage = 1;
        renderDoctorPickerList();
    }

function toggleShowOnlySelected() {
        isFilterOnlySelected = !isFilterOnlySelected;
        pickerCurrentPage = 1;
        const btn = document.getElementById('btnFilterToggleSelected');
        if (btn) btn.innerText = isFilterOnlySelected ? 'Tampilkan Semua' : 'Tampilkan Terpilih';
        renderDoctorPickerList();
    }

async function handleTargetFormSubmit(e) {
        e.preventDefault();
        await saveCallList(false);
    }

async function handleSaveAndSubmit() {
        await saveCallList(true);
    }

async function saveCallList(autoSubmit = false) {
        const errEl = document.getElementById('targetModalError');
        errEl.style.display = 'none';

        const editingId = document.getElementById('editingCallListId').value;
        const period = document.getElementById('periodMonthInput').value;
        const selectedDoctors = Array.from(currentSelectedDoctorIds);

        if (showPeriodConflict(period)) {
            return;
        }

        if (!selectedDoctors.length) {
            errEl.innerText = 'Pilih minimal 1 target dokter.';
            errEl.style.display = 'block';
            return;
        }

        const btnDraft = document.getElementById('btnSubmitDraft');
        const btnSend = document.getElementById('btnSubmitAndSend');
        btnDraft.disabled = true;
        btnSend.disabled = true;

        try {
            let res, data;
            if (editingId) {
                // Update draft yang sudah ada
                res = await window.apiFetch(`/api/call-lists/${editingId}`, {
                    method: 'PUT',
                    body: JSON.stringify({ doctor_ids: selectedDoctors })
                });
                data = await res.json();
            } else {
                // Buat draft baru
                res = await window.apiFetch('/api/call-lists', {
                    method: 'POST',
                    body: JSON.stringify({
                        period_month: period,
                        doctor_ids: selectedDoctors
                    })
                });
                data = await res.json();
            }

            if (!res.ok || !data.success) {
                btnDraft.disabled = false;
                btnSend.disabled = false;
                errEl.innerText = window.describeApiError(data || res, 'Gagal menyimpan target dokter.', res.status);
                errEl.style.display = 'block';
                return;
            }

            const savedId = editingId || data.data.id;

            if (autoSubmit) {
                let subRes = null;
                let subData = null;
                let isNetworkError = false;
                try {
                    subRes = await window.apiFetch(`/api/call-lists/${savedId}/submit`, { method: 'POST' });
                    subData = await subRes.json();
                } catch (subErr) {
                    isNetworkError = true;
                }

                btnDraft.disabled = false;
                btnSend.disabled = false;
                closeTargetModal();

                if (!isNetworkError && subRes && subRes.ok && subData && subData.success) {
                    showAlert('Target dokter berhasil disimpan dan diajukan ke atasan!');
                } else {
                    // A failed response can arrive after the server already changed the state.
                    let reconciled = false;
                    try {
                        const checkRes = await window.apiFetch(`/api/call-lists/${savedId}`);
                        const checkData = await checkRes.json();
                        if (checkRes.ok && checkData.success && checkData.data) {
                            if (checkData.data.status === 'pending_approval') {
                                reconciled = true;
                                showAlert('Target dokter berhasil disimpan dan diajukan ke atasan (terverifikasi setelah rekonsiliasi koneksi).');
                            } else if (checkData.data.status === 'draft') {
                                reconciled = true;
                                const isClientError = subRes && subRes.status >= 400 && subRes.status < 500;
                                if (!isNetworkError && isClientError && subRes.status !== 409) {
                                    const message = window.describeApiError(subData || subRes, 'Pengajuan belum diterima.', subRes.status);
                                    showAlert(`Draft tersimpan, tetapi pengajuan belum diterima: ${message} Periksa lalu ajukan kembali dari kartu draf.`, 'warning');
                                } else {
                                    showAlert('Draft tersimpan dan masih berstatus Draft. Pengajuan belum terkonfirmasi; Anda dapat memeriksa daftar sebelum mencoba lagi.', 'warning');
                                }
                            }
                        }
                    } catch (reconErr) {
                        // Keep the outcome unconfirmed if reconciliation also fails.
                    }

                    if (!reconciled) {
                        const reason = isNetworkError
                            ? 'Koneksi terputus.'
                            : `Server merespons dengan status ${subRes?.status || 'tidak diketahui'}.`;
                        showAlert(`Draft target dokter tersimpan, tetapi status pengajuan belum dapat dipastikan. ${reason} Muat ulang daftar sebelum mencoba lagi.`, 'warning');
                    }
                }
                loadMyCallLists();
                if (currentUserRole === 'dm') loadReviewCallLists();
            } else {
                btnDraft.disabled = false;
                btnSend.disabled = false;
                closeTargetModal();
                showAlert('Draft target dokter berhasil disimpan!');
                loadMyCallLists();
            }
        } catch (err) {
            btnDraft.disabled = false;
            btnSend.disabled = false;
            errEl.innerText = window.describeApiError(err, 'Terjadi kesalahan jaringan atau server saat menyimpan target dokter.');
            errEl.style.display = 'block';
        }
    }

async function submitCallList(id) {
        const confirmed = await requestConfirmation(
            'Ajukan Target Dokter ini ke atasan langsung?',
            'Ajukan Target Dokter'
        );
        if (!confirmed) return;

        try {
            const res = await window.apiFetch(`/api/call-lists/${id}/submit`, { method: 'POST' });
            const data = await res.json();
            if (res.ok && data.success) {
                showAlert('Target dokter berhasil diajukan untuk persetujuan.');
                loadMyCallLists();
                if (currentUserRole === 'dm') loadReviewCallLists();
            } else {
                showAlert(window.describeApiError(data || res, 'Gagal mengajukan target dokter.', res.status), 'danger');
            }
        } catch (err) {
            // Rekonsiliasi status ke server jika koneksi sempat terputus saat request submit
            let reconciled = false;
            try {
                const checkRes = await window.apiFetch(`/api/call-lists/${id}`);
                const checkData = await checkRes.json();
                if (checkRes.ok && checkData.success && checkData.data) {
                    if (checkData.data.status === 'pending_approval') {
                        reconciled = true;
                        showAlert('Target dokter berhasil diajukan untuk persetujuan (terverifikasi setelah rekonsiliasi koneksi).');
                        loadMyCallLists();
                        if (currentUserRole === 'dm') loadReviewCallLists();
                        return;
                    }
                }
            } catch (reconErr) {
                console.warn('Gagal memverifikasi status pengajuan target setelah gangguan koneksi.', reconErr);
            }

            showAlert('Koneksi jaringan terputus saat menunggu konfirmasi pengajuan. Status belum dapat dipastikan; silakan muat ulang daftar sebelum mencoba lagi.', 'warning');
        }
    }

function formatApprovalEvent(event) {
        const labels = {
            created_draft: 'Draft dibuat',
            submitted: 'Diajukan',
            approved: 'Disetujui',
            rejected: 'Ditolak',
            revised: 'Direvisi',
        };

        return labels[event] || String(event || '-').replaceAll('_', ' ');
    }

async function openDetailModal(id) {
        document.getElementById('detailContent').innerHTML = '<div class="target-detail-loading">Memuat detail...</div>';
        document.getElementById('detailModal').style.display = 'flex';

        try {
            const res = await window.apiFetch(`/api/call-lists/${id}`);
            const result = await res.json();
            if (result.success) {
                const cl = result.data;
                document.getElementById('detailPeriodSubtitle').innerText = formatPeriodIndonesian(cl.period_month);

                const statusClass = `status-${cl.status === 'pending_approval' ? 'pending' : cl.status}`;
                const statusLabel = cl.status === 'pending_approval' ? 'Menunggu Persetujuan' : (cl.status === 'approved' ? 'Disetujui' : 'Draft');
                const doctorCount = cl.doctors.length;

                const defaultReasons = [
                    'Draft Call List dibuat',
                    'Pengajuan Call List ke atasan',
                    'Pengajuan ulang setelah revisi draft',
                    'Disetujui'
                ];
                const historyHtml = cl.approval_history.length
                    ? cl.approval_history.map(h => {
                        const eventTitle = formatApprovalEvent(h.event);
                        const actor = h.actor_name || 'Sistem';
                        const timeStr = window.formatDateTimeIndonesia ? window.formatDateTimeIndonesia(h.created_at) : h.created_at;
                        const isRedundantReason = !h.reason || defaultReasons.includes(h.reason.trim());

                        return `
                            <div class="target-detail-history-row">
                                <div class="target-history-main" style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
                                    <strong>${escapeHtml(eventTitle)}</strong>
                                    <span>${escapeHtml(actor)}</span>
                                    <span class="tabular-nums" style="font-size: 0.775rem;">${escapeHtml(timeStr)}</span>
                                </div>
                                ${!isRedundantReason ? `<div class="target-history-note">Catatan: "${escapeHtml(h.reason)}"</div>` : ''}
                            </div>
                        `;
                    }).join('')
                    : '<div class="target-detail-history-row">Belum ada riwayat.</div>';

                const html = `
                    <div class="target-detail-meta">
                        <div>
                            Status
                            <strong><span class="status-badge ${statusClass}">${escapeHtml(statusLabel)}</span></strong>
                        </div>
                        <div>
                            Peninjau
                            <strong>${escapeHtml(cl.assigned_approver_name || (cl.assigned_approver && cl.assigned_approver.name) || '-')}</strong>
                        </div>
                        <div>
                            Pengaju
                            <strong>${escapeHtml(cl.owner_name || (cl.owner && cl.owner.name) || '-')}</strong>
                        </div>
                        <div>
                            Jumlah dokter
                            <strong>${doctorCount} dokter</strong>
                        </div>
                    </div>

                    <section>
                        <h4 class="target-detail-section-title">Daftar dokter</h4>
                        <div class="target-detail-list">
                            ${cl.doctors.map(d => `
                                <div class="target-detail-doctor">
                                    <strong>${escapeHtml(d.name)}</strong>
                                    <span>${escapeHtml(d.specialization || 'Umum')} - ${escapeHtml(d.address || '-')}</span>
                                </div>
                            `).join('')}
                        </div>
                    </section>

                    <section>
                        <h4 class="target-detail-section-title">Riwayat</h4>
                        <div class="target-detail-history">
                            ${historyHtml}
                        </div>
                    </section>

                    <div class="target-detail-actions">
                        <button type="button" class="btn btn-outline btn-sm" data-action="close-detail">Tutup</button>
                    </div>
                `;
                document.getElementById('detailContent').innerHTML = html;
            } else {
                const errMsg = window.describeApiError(result || res, 'Gagal memuat detail.', res.status);
                document.getElementById('detailContent').innerHTML = `<div class="target-detail-error">${escapeHtml(errMsg)}</div>`;
            }
        } catch (err) {
            const errMsg = window.describeApiError(err, 'Gagal memuat detail.');
            document.getElementById('detailContent').innerHTML = `<div class="target-detail-error">${escapeHtml(errMsg)}</div>`;
        }
    }

function closeDetailModal() {
        document.getElementById('detailModal').style.display = 'none';
    }

    // Manager Actions
    async function loadReviewCallLists() {
        if (currentUserRole !== 'dm') return;

        const loadingEl = document.getElementById('loadingReview');
        const errorEl = document.getElementById('errorReviewQueue');
        if (errorEl) errorEl.style.display = 'none';

        if (!loadingEl) return;
        loadingEl.innerHTML = window.renderTableSkeleton(3);
        loadingEl.style.display = 'block';
        try {
            const res = await window.apiFetch('/api/call-lists?mode=review');
            const result = await res.json();
            if (loadingEl) loadingEl.style.display = 'none';

            if (res.ok && result.success && Array.isArray(result.data)) {
                reviewCallLists = result.data;
                const countEl = document.getElementById('reviewCount');
                if (countEl) countEl.innerText = reviewCallLists.length;
                renderReviewGrid();
            } else {
                const errMsg = window.describeApiError(result || res, 'Gagal memuat antrean persetujuan.', res.status);
                showReviewQueueError(errMsg);
            }
        } catch (err) {
            if (loadingEl) loadingEl.style.display = 'none';
            const errMsg = window.describeApiError(err, 'Terjadi gangguan jaringan saat memuat antrean persetujuan.');
            showReviewQueueError(errMsg);
        }
    }

    function showReviewQueueError(message) {
        const tableWrap = document.getElementById('reviewTableWrap');
        const empty = document.getElementById('emptyReview');
        const errorEl = document.getElementById('errorReviewQueue');
        const errorText = document.getElementById('errorReviewQueueText');

        if (tableWrap) tableWrap.style.display = 'none';
        if (empty) empty.style.display = 'none';

        if (errorEl) {
            if (errorText) errorText.innerText = message;
            errorEl.style.display = 'block';
        } else {
            showAlert(message, 'danger');
        }
    }

function renderReviewGrid() {
        const tableWrap = document.getElementById('reviewTableWrap');
        const tbody = document.getElementById('reviewTableBody');
        const empty = document.getElementById('emptyReview');
        const errorEl = document.getElementById('errorReviewQueue');

        if (errorEl) errorEl.style.display = 'none';

        if (!reviewCallLists.length) {
            if (tbody) tbody.innerHTML = '';
            if (tableWrap) tableWrap.style.display = 'none';
            if (empty) empty.style.display = 'block';
            return;
        }
        if (empty) empty.style.display = 'none';
        if (tableWrap) tableWrap.style.display = 'block';
        tbody.innerHTML = reviewCallLists.map(cl => `
            <tr>
                <td class="target-nowrap-cell">
                    <strong class="target-review-period">${formatPeriodIndonesian(cl.period_month)}</strong>
                </td>
                <td class="target-nowrap-cell">
                    <strong class="target-table-strong">${escapeHtml(cl.owner_name || '-')}</strong>
                </td>
                <td>
                    <strong class="target-table-strong">${cl.doctor_count} Dokter</strong>
                </td>
                <td>
                    <span class="status-badge status-pending">Menunggu Persetujuan</span>
                </td>
                <td class="target-action-cell">
                    <div class="target-inline-actions">
                        <button class="btn btn-outline btn-sm" data-action="open-detail" data-call-list-id="${cl.id}">Detail</button>
                        <button class="btn btn-primary btn-sm" data-action="approve-call-list" data-call-list-id="${cl.id}">Setujui</button>
                        <button class="btn btn-outline btn-sm target-danger-outline" data-action="open-reject" data-call-list-id="${cl.id}">Tolak</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

async function approveCallList(id) {
        const confirmed = await requestConfirmation(
            'Setujui Target Dokter ini?',
            'Setujui Target Dokter'
        );
        if (!confirmed) return;

        try {
            const res = await window.apiFetch(`/api/call-lists/${id}/approve`, {
                method: 'PATCH',
                body: JSON.stringify({ status: 'approved', reason: 'Disetujui' })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                showAlert('Target dokter berhasil disetujui!');
                loadReviewCallLists();
                loadManagerSummary();
            } else {
                showAlert(window.describeApiError(data || res, 'Gagal menyetujui target dokter.', res.status), 'danger');
            }
        } catch (err) {
            showAlert(window.describeApiError(err, 'Terjadi kesalahan jaringan.'), 'danger');
        }
    }

function openRejectModal(id) {
        document.getElementById('rejectCallListId').value = id;
        document.getElementById('rejectReasonInput').value = '';
        const errEl = document.getElementById('rejectModalError');
        if (errEl) errEl.style.display = 'none';
        document.getElementById('rejectModal').style.display = 'flex';
    }

function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
    }

async function handleRejectSubmit(e) {
        e.preventDefault();
        const id = document.getElementById('rejectCallListId').value;
        const reason = document.getElementById('rejectReasonInput').value.trim();
        const errEl = document.getElementById('rejectModalError');

        if (!reason) {
            errEl.innerText = 'Alasan penolakan wajib diisi.';
            errEl.style.display = 'block';
            return;
        }

        try {
            const res = await window.apiFetch(`/api/call-lists/${id}/approve`, {
                method: 'PATCH',
                body: JSON.stringify({ status: 'rejected', reason: reason })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                closeRejectModal();
                showAlert('Target dokter ditolak dan dikembalikan untuk direvisi.');
                loadReviewCallLists();
                loadManagerSummary();
            } else {
                errEl.innerText = window.describeApiError(data || res, 'Gagal menolak target.', res.status);
                errEl.style.display = 'block';
            }
        } catch (err) {
            errEl.innerText = window.describeApiError(err, 'Terjadi kesalahan jaringan.');
            errEl.style.display = 'block';
        }
    }

async function loadManagerSummary() {
    if (currentUserRole !== 'dm') return;

    const monthInput = document.getElementById('summaryMonthSelect');
        const card = document.getElementById('monitoringSummaryCard');
        if (!card) return;

        let selectedMonth = monthInput.value;
        if (!selectedMonth) {
            const now = new Date();
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            selectedMonth = `${yyyy}-${mm}`;
            monthInput.value = selectedMonth;
        }

        try {
            const res = await window.apiFetch(`/api/call-lists/summary?period_month=${selectedMonth}`);
            const result = await res.json();
            if (result.success) {
                card.style.display = 'block';
                const data = result.data;
                document.getElementById('statTotalSubordinates').innerText = data.total_subordinates;
                document.getElementById('statTotalDoctors').innerText = data.total_target_doctors;
                document.getElementById('statApproved').innerText = `${data.approved_count} / ${data.total_subordinates}`;
                document.getElementById('statPending').innerText = data.pending_count;

                const listContainer = document.getElementById('subordinatesListContainer');
                if (data.subordinates_detail && data.subordinates_detail.length) {
                    listContainer.innerHTML = data.subordinates_detail.map(sub => {
                        let statusTheme = 'status-none';
                        let statusText = 'Belum Mengajukan';

                        if (sub.status === 'approved') {
                            statusTheme = 'status-approved';
                            statusText = 'Disetujui';
                        } else if (sub.status === 'pending_approval') {
                            statusTheme = 'status-pending';
                            statusText = 'Menunggu Persetujuan';
                        } else if (sub.status === 'draft') {
                            statusTheme = 'status-draft';
                            statusText = 'Perlu Revisi';
                        }

                        const actionBtn = sub.call_list_id ? `
                            <button type="button" class="btn btn-outline btn-sm target-roster-button" data-action="open-detail" data-call-list-id="${sub.call_list_id}">
                                Lihat Target
                            </button>
                        ` : `
                            <span class="target-roster-empty">Belum Ada Target</span>
                        `;

                        return `
                            <div class="team-roster-item">
                                <div class="target-roster-person">
                                    <strong class="target-roster-name">${escapeHtml(sub.name)}</strong>
                                    <span class="role-pill role-${sub.role}">${sub.role.toUpperCase()}</span>
                                </div>
                                <div class="target-roster-status">
                                    <div class="target-roster-count">
                                        <strong class="tabular-nums">${sub.doctor_count}</strong> Dokter
                                    </div>
                                    <span class="status-badge ${statusTheme}">${statusText}</span>
                                    ${actionBtn}
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    listContainer.innerHTML = '<div class="target-roster-none">Tidak ada anggota tim terdaftar.</div>';
                }
            }
        } catch (err) {
            console.error('Gagal mengambil ringkasan monitoring manajerial', err);
        }
    }

// =========================================================================
// FITUR P1: PROGRAM REKOMENDASI MANAJERIAL & PEMANTAUAN PROGRES
// =========================================================================

async function loadCampaigns() {
    const loadingEl = document.getElementById('loadingCampaigns');
    const cardsStack = document.getElementById('campaignCardsStack');
    const emptyEl = document.getElementById('emptyCampaigns');

    if (loadingEl) loadingEl.style.display = 'block';
    if (cardsStack) cardsStack.innerHTML = '';
    if (emptyEl) emptyEl.style.display = 'none';

    try {
        if (currentUserRole === 'rsm' || currentUserRole === 'mm') {
            loadExecutiveSummary();
        }

        const res = await window.apiFetch('/api/managerial-campaigns');
        const result = await res.json();
        if (loadingEl) loadingEl.style.display = 'none';

        if (res.ok && result.success && Array.isArray(result.data)) {
            allCampaigns = result.data;
            if (!allCampaigns.length) {
                if (emptyEl) emptyEl.style.display = 'block';
                return;
            }
            renderCampaignCards(allCampaigns);
        } else {
            showAlert(window.describeApiError(result || res, 'Gagal memuat program rekomendasi.', res.status), 'danger');
        }
    } catch (err) {
        if (loadingEl) loadingEl.style.display = 'none';
        console.error('Gagal memuat program kampanye', err);
        showAlert(window.describeApiError(err, 'Gagal memuat data program rekomendasi.'), 'danger');
    }
}

async function renderCampaignCards(campaigns) {
    const cardsStack = document.getElementById('campaignCardsStack');
    if (!cardsStack) return;

    cardsStack.innerHTML = campaigns.map(camp => {
        const isRegionalDirective = camp.district_name === 'Regional';
        const isNationalDirective = camp.district_name === 'Nasional';
        const isPolicyDirective = isRegionalDirective || isNationalDirective;

        let statusBadge = '<span class="status-badge status-draft">Draft</span>';
        if (isPolicyDirective) {
            statusBadge = '<span class="status-badge" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-weight: 600;"><i class="bi bi-check-circle-fill"></i> Arahan Aktif</span>';
        } else if (camp.status === 'approved') {
            statusBadge = '<span class="status-badge status-approved">Disetujui</span>';
        } else if (camp.status === 'pending_approval') {
            statusBadge = '<span class="status-badge status-pending">Menunggu Persetujuan</span>';
        } else if (camp.status === 'rejected') {
            statusBadge = '<span class="status-badge status-rejected">Perlu Revisi</span>';
        }

        const productNames = camp.products && camp.products.length
            ? camp.products.map(p => p.name).join(', ')
            : '-';
        const districtInfo = isPolicyDirective
            ? ''
            : `<span>Distrik: ${escapeHtml(camp.district_name || '-')}</span>`;

        // Approval hanya untuk atasan langsung (RSM), MM tidak perlu approval (hanya monitoring)
        let actionButtons = '';
        if (camp.status === 'pending_approval' && currentUserRole === 'rsm') {
            actionButtons = `
                <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                    <button type="button" class="btn btn-primary btn-sm" data-action="approve-campaign" data-campaign-id="${camp.id}">
                        Setujui Program
                    </button>
                    <button type="button" class="btn btn-outline btn-sm target-danger-button" data-action="open-reject-campaign" data-campaign-id="${camp.id}">
                        Tolak
                    </button>
                </div>
            `;
        }

        const rejectionInfo = camp.rejection_reason ? `
            <div style="margin-top: 0.5rem; padding: 0.5rem 0.75rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); font-size: 0.8rem; color: var(--danger);">
                <strong>Catatan Revisi Atasan:</strong> ${escapeHtml(camp.rejection_reason)}
            </div>
        ` : '';

        const cardClass = isPolicyDirective ? 'target-card regional-directive-card' : 'target-card district-proposal-card';
        const typeHeader = isPolicyDirective
            ? `<div style="margin-bottom: 0.5rem;">
                   <span class="regional-directive-pill">
                       <i class="bi bi-info-circle-fill"></i>
                       <span>${isNationalDirective ? 'Arahan Nasional' : 'Arahan Regional'}</span>
                   </span>
               </div>`
            : `<div style="margin-bottom: 0.5rem;">
                   <span class="district-proposal-pill">
                       <i class="bi bi-file-earmark-text"></i>
                       <span>Usulan Rekomendasi Distrik</span>
                   </span>
               </div>`;

        const creatorPrefix = isPolicyDirective ? 'Diterbitkan oleh:' : 'Diajukan oleh:';
        const targetBadge = !isPolicyDirective
            ? `<span class="badge-priority">★ ${camp.doctor_count} Dokter Sasaran</span>`
            : '';

        const rawTitle = camp.title || 'Program Rekomendasi';
        // Clean out generic AI em-dashes and redundant "Rekomendasi <Distrik> – " or "Rekomendasi <Distrik> - " prefixes
        const cleanTitle = rawTitle.replace(/^Rekomendasi\s+[^–—\-]+[–—\-]\s*/i, '').replace(/[–—]/g, '-').trim() || rawTitle;

        return `
            <div class="${cardClass}" id="campaignCard_${camp.id}" style="padding: 1rem 1.25rem;">
                ${typeHeader}
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.65rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                            <h4 style="font-size: 1.05rem; font-weight: 700; color: var(--secondary); margin: 0;">${escapeHtml(cleanTitle)}</h4>
                            ${statusBadge}
                        </div>
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.85rem; font-size: 0.8125rem; color: var(--text-muted); margin-top: 0.2rem;">
                            ${districtInfo}
                            <span>Durasi: ${camp.duration_months} Bulan <span style="font-size: 0.75rem;">(${camp.start_date || '-'} s.d. ${camp.end_date || '-'})</span></span>
                            <span>${creatorPrefix} ${escapeHtml(camp.creator_name || '-')}</span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; margin-bottom: 0.75rem; font-size: 0.8rem;">
                    ${targetBadge}
                    <span class="badge-focus-product" style="${isPolicyDirective ? 'background: #ffffff; border-color: #bae6fd;' : ''}">Produk prioritas: ${escapeHtml(productNames)}</span>
                    ${camp.specialization ? `<span class="target-specialty-badge">${escapeHtml(camp.specialization)}</span>` : ''}
                </div>

                <div id="campaignProgress_${camp.id}">
                    ${camp.status === 'approved' ? '<div style="font-size: 0.775rem; color: var(--text-muted);">Memuat kalkulasi progres aktual...</div>' : ''}
                </div>

                ${rejectionInfo}
                ${actionButtons}
            </div>
        `;
    }).join('');

    campaigns.filter(c => c.status === 'approved').forEach(c => {
        loadSingleCampaignProgress(c.id);
    });
}

async function loadSingleCampaignProgress(campaignId) {
    const wrap = document.getElementById(`campaignProgress_${campaignId}`);
    if (!wrap) return;

    try {
        const res = await window.apiFetch(`/api/managerial-campaigns/${campaignId}`);
        const result = await res.json();
        if (result.success && result.data) {
            const p = result.data;

            // Jika kebijakan arahan regional tanpa daftar dokter sasaran, tampilkan status kebijakan regional
            if (p.total_doctors === 0 && ['Regional', 'Nasional'].includes(p.district_name)) {
                const scopeLabel = p.district_name === 'Nasional'
                    ? 'Berlaku secara nasional'
                    : 'Berlaku untuk seluruh distrik di bawah koordinasi RSM';
                wrap.innerHTML = `
                    <div style="margin-top: 0.75rem; padding-top: 0.65rem; border-top: 1px solid #e0f2fe; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; font-size: 0.8125rem;">
                        <span style="color: var(--text-muted);">${scopeLabel}</span>
                        <span style="color: var(--secondary);">Kunjungan dengan produk prioritas: <strong style="color: #0284c7;">${p.focus_detailing_count || 0} kali</strong></span>
                    </div>
                `;
                return;
            }

            // Untuk program rekomendasi distrik dengan dokter sasaran
            const completionIndicator = (p.unvisited_doctors_count === 0 && p.total_doctors > 0)
                ? '<span style="font-size: 0.75rem; color: var(--success); font-weight: 600;">✓ Seluruh dokter target telah dikunjungi</span>'
                : (p.unvisited_doctors_count > 0
                    ? `<button type="button" class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 0.2rem 0.6rem;" data-action="show-unvisited-doctors" data-campaign-id="${campaignId}">
                          Lihat ${p.unvisited_doctors_count} Dokter Belum Dikunjungi
                       </button>`
                    : '');

            wrap.innerHTML = `
                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 4px; padding: 0.75rem; margin-top: 0.65rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem; font-size: 0.8rem;">
                        <span>Progres Kunjungan Dokter: <strong>${p.visited_doctors_count} dari ${p.total_doctors} Dokter (${p.reach_percentage}%)</strong></span>
                        <span>Kunjungan dengan Produk Prioritas: <strong style="color: var(--primary);">${p.focus_detailing_count}</strong></span>
                    </div>
                    <div class="progress-bar-container" style="margin-bottom: 0.5rem;">
                        <div class="progress-bar-fill" style="width: ${p.reach_percentage}%;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.75rem; color: var(--text-muted);">Total Kunjungan Lapangan: ${p.total_calls} kali</span>
                        ${completionIndicator}
                    </div>
                </div>
            `;
        }
    } catch (e) {
        console.warn(`Gagal memuat progres kampanye ${campaignId}`, e);
    }
}

async function loadExecutiveSummary() {
    const section = document.getElementById('executiveSummarySection');
    const metricGrid = document.getElementById('executiveMetricGrid');
    const tableBody = document.getElementById('districtBreakdownTableBody');
    const errorBox = document.getElementById('managerSummaryError');
    if (!section || !metricGrid || !tableBody) return;
    if (errorBox) errorBox.style.display = 'none';
    section.style.display = 'none';

    try {
        const res = await window.apiFetch('/api/managerial-campaigns/summary');
        const result = await res.json();
        if (res.ok && result.success && result.data) {
            const sum = result.data;
            section.style.display = 'block';

            metricGrid.innerHTML = `
                <div class="metric-card">
                    <span class="metric-card-label">Total Dokter Target</span>
                    <strong class="metric-card-value">${sum.total_target_doctors}</strong>
                </div>
                <div class="metric-card">
                    <span class="metric-card-label">Dokter target dikunjungi</span>
                    <strong class="metric-card-value" style="color: var(--primary);">${Number(sum.total_target_doctors) > 0 ? `${sum.overall_reach_percentage}%` : '-'}</strong>
                    <span class="metric-card-sub">${sum.total_visited_doctors} dokter unik telah dikunjungi</span>
                </div>
                <div class="metric-card">
                    <span class="metric-card-label">Kunjungan dengan Produk Prioritas</span>
                    <strong class="metric-card-value" style="color: var(--success);">${sum.total_focus_detailing}</strong>
                    <span class="metric-card-sub">Laporan kunjungan unik lintas program</span>
                </div>
            `;

            if (sum.district_breakdown && sum.district_breakdown.length) {
                const operationalDistricts = sum.district_breakdown.filter(d => (d.district_name || '').toLowerCase() !== 'regional');
                tableBody.innerHTML = operationalDistricts.map(d => {
                    const reachPct = Number(d.reach_percentage) || 0;
                    const hasTarget = Number(d.total_doctors) > 0;
                    const badgeClass = reachPct >= 80 ? 'badge-success' : (reachPct >= 50 ? 'badge-info' : 'badge-warning');
                    const fillBg = reachPct >= 80 ? 'background: linear-gradient(90deg, #16a34a, #22c55e);' : '';
                    const roleLabel = d.creator_role === 'rsm' ? 'Regional Sales Manager' : 'District Manager';

                    return `
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--secondary); font-size: 0.9rem;">${escapeHtml(d.district_name)}</div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--text-main); font-size: 0.85rem;">${escapeHtml(d.dm_name)}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">${roleLabel}</div>
                            </td>
                            <td style="text-align: right;">
                                <span class="tabular-nums font-bold" style="font-size: 0.9rem; color: var(--secondary);">${d.total_doctors}</span>
                                <span style="font-size: 0.78rem; color: var(--text-muted); margin-left: 2px;">Dokter</span>
                            </td>
                            <td>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                    <strong style="color: var(--secondary); font-size: 0.82rem;">${hasTarget ? `${d.visited_doctors_count}/${d.total_doctors}` : '-'}</strong>
                                    ${hasTarget ? `<span class="badge ${badgeClass}" style="font-size: 0.72rem; padding: 0.15rem 0.45rem;">${reachPct}%</span>` : ''}
                                </div>
                                ${hasTarget ? `<div class="progress-bar-container" style="height: 6px;"><div class="progress-bar-fill" style="width: ${reachPct}%; ${fillBg}"></div></div>` : ''}
                            </td>
                            <td style="text-align: right;">
                                <span class="tabular-nums font-bold" style="font-size: 0.92rem; color: var(--success);">${d.focus_detailing_count}x</span>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">kunjungan dengan produk prioritas</div>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2.75rem 1.5rem; background: #fafcff;">
                            <i class="bi bi-inbox" style="font-size: 2.25rem; color: #94a3b8; display: block; margin-bottom: 0.65rem;"></i>
                            <div style="font-weight: 700; color: var(--secondary); font-size: 0.95rem; margin-bottom: 0.35rem;">Belum Ada Program Distrik yang Disetujui</div>
                            <div style="font-size: 0.825rem; color: var(--text-muted); max-width: 460px; margin: 0 auto; line-height: 1.5;">
                                Data kunjungan yang membahas produk prioritas akan terakumulasi setelah program distrik disetujui.
                            </div>
                        </td>
                    </tr>
                `;
            }
        } else {
            throw new Error(window.describeApiError(result || res, 'Ringkasan belum dapat dimuat.', res.status));
        }
    } catch (e) {
        console.warn('Gagal memuat ringkasan program', e);
        if (errorBox) errorBox.style.display = 'block';
    }
}

async function openCreateCampaignModal() {
    const modal = document.getElementById('createCampaignModal');
    const errBox = document.getElementById('createCampaignError');
    const prodBox = document.getElementById('campaignProductCheckboxes');
    const headingEl = document.getElementById('campaignModalHeading');
    const subheadingEl = document.getElementById('campaignModalSubheading');
    const districtLabelEl = document.getElementById('campaignDistrictLabel');
    const districtLabelPrefixEl = document.getElementById('campaignDistrictLabelPrefix');
    const rsmGuidanceField = document.getElementById('campaignRsmGuidanceField');
    const rsmGuidanceSelect = document.getElementById('campaignRsmGuidanceSelect');
    const doctorSection = document.getElementById('campaignDoctorSection');
    const submitBtn = document.getElementById('btnSubmitCampaign');
    const durationSelect = document.getElementById('campaignDurationSelect');

    if (!modal) return;
    if (errBox) errBox.style.display = 'none';

    // Reset formulir & status disabled
    const form = document.getElementById('createCampaignForm');
    if (form) form.reset();
    if (durationSelect) durationSelect.disabled = false;

    // Pastikan master produk telah dimuat
    if (!masterProducts.length) {
        try {
            const res = await window.apiFetch('/api/products');
            const pData = await res.json();
            if (pData.success) {
                masterProducts = pData.data;
            }
        } catch (e) {
            console.warn('Gagal memuat master produk', e);
        }
    }

    // Pastikan master dokter telah dimuat
    if (!allDoctors.length) {
        await loadDoctors();
    }

    // Pastikan daftar kampanye telah dimuat (agar DM bisa memilih arahan RSM)
    if (!allCampaigns.length) {
        try {
            const cRes = await window.apiFetch('/api/managerial-campaigns');
            const cData = await cRes.json();
            if (cData.success && Array.isArray(cData.data)) {
                allCampaigns = cData.data;
            }
        } catch (e) {
            console.warn('Gagal memuat kampanye untuk modal', e);
        }
    }

    // Render checkbox produk master sebagai chip interaktif
    renderCampaignProducts();

    // Reset pilihan dokter
    campaignSelectedDoctorIds.clear();

    // Reset kolom pencarian dokter
    const docSearchInput = document.getElementById('campaignDoctorSearchInput');
    if (docSearchInput) docSearchInput.value = '';

    // Penyesuaian antarmuka berdasarkan peran pengguna (RSM vs DM)
    if (currentUserRole === 'rsm' || currentUserRole === 'mm') {
        // RSM/MM: arahan kebijakan berlaku di seluruh distrik dalam cakupannya.
        if (headingEl) headingEl.innerText = 'Buat Arahan Kampanye Regional (Produk Prioritas & Periode)';
        if (subheadingEl) subheadingEl.innerText = 'Tentukan produk prioritas dan periode program untuk seluruh distrik';
        if (districtLabelPrefixEl) districtLabelPrefixEl.innerText = 'Wilayah:';
        if (districtLabelEl) districtLabelEl.innerText = 'Seluruh distrik di bawah RSM';
        if (rsmGuidanceField) rsmGuidanceField.style.display = 'none';
        if (doctorSection) doctorSection.style.display = 'none';
        if (submitBtn) submitBtn.innerText = 'Terbitkan Arahan Regional';

        populateCampaignSpecializations();
    } else {
        // DM: Pengajuan rekomendasi dokter distrik ke RSM
        if (headingEl) headingEl.innerText = 'Ajukan Rekomendasi Dokter Distrik ke RSM';
        if (subheadingEl) subheadingEl.innerText = 'Tentukan dokter sasaran dan spesialisasi sesuai arahan RSM';
        if (districtLabelPrefixEl) districtLabelPrefixEl.innerText = 'Distrik:';
        if (districtLabelEl) districtLabelEl.innerText = currentUserDistrict ? `Distrik ${currentUserDistrict}` : 'Wilayah DM';
        if (doctorSection) doctorSection.style.display = 'block';
        if (submitBtn) submitBtn.innerText = 'Simpan & Ajukan ke RSM';

        // Tampilkan pilihan arahan RSM jika tersedia
        if (rsmGuidanceField && rsmGuidanceSelect) {
            rsmGuidanceField.style.display = 'block';
            const rsmCampaigns = allCampaigns.filter(c => c.status === 'approved' && (c.district_name === 'Regional' || c.created_by !== window.MSF?.currentUserId));

            rsmGuidanceSelect.innerHTML = '<option value="">-- Buat Usulan Distrik Mandiri --</option>' +
                rsmCampaigns.map(c => {
                    const prodNames = (c.products || []).map(p => p.name).join(', ') || `${c.product_count || 0} Produk Prioritas`;
                    return `<option value="${c.id}">[Arahan RSM] ${escapeHtml(c.title)} (${c.duration_months} Bln - Fokus: ${escapeHtml(prodNames)})</option>`;
                }).join('');
        }

        populateCampaignSpecializations();
        renderCampaignDoctors();
    }

    modal.style.display = 'flex';
}

function renderCampaignProducts() {
    const prodBox = document.getElementById('campaignProductCheckboxes');
    if (!prodBox) return;

    if (!masterProducts.length) {
        prodBox.innerHTML = `
            <span style="font-size: 0.8rem; color: var(--text-muted); grid-column: 1 / -1; padding: 0.5rem; text-align: center;">
                Memuat produk master...
            </span>
        `;
        return;
    }

    prodBox.innerHTML = masterProducts.map(p => `
        <label class="product-chip-item" id="prodChip_${p.id}">
            <input type="checkbox" name="campaign_product" value="${p.id}">
            <span style="flex: 1;">${escapeHtml(p.name)}</span>
        </label>
    `).join('');

    updateCampaignSelectedProdCount();
}

function onCampaignProductToggle(input) {
    if (!input) return;
    const chip = input.closest('.product-chip-item');
    const checked = Array.from(document.querySelectorAll('#campaignProductCheckboxes input[name="campaign_product"]:checked'));

    if (input.checked && checked.length > 5) {
        input.checked = false;
        if (chip) chip.classList.remove('is-selected');
        showAlert('Maksimal 5 produk prioritas.', 'danger');
        updateCampaignSelectedProdCount();
        return;
    }

    if (chip) {
        chip.classList.toggle('is-selected', input.checked);
    }
    updateCampaignSelectedProdCount();
}
window.onCampaignProductToggle = onCampaignProductToggle;

function updateCampaignSelectedProdCount() {
    const countEl = document.getElementById('campaignSelectedProdCount');
    const checked = document.querySelectorAll('#campaignProductCheckboxes input[name="campaign_product"]:checked');
    if (countEl) {
        countEl.innerText = String(checked.length);
    }
    document.querySelectorAll('.product-chip-item').forEach(chip => {
        const input = chip.querySelector('input[name="campaign_product"]');
        if (input) {
            chip.classList.toggle('is-selected', input.checked);
        }
    });
}
window.updateCampaignSelectedProdCount = updateCampaignSelectedProdCount;

function populateCampaignSpecializations() {
    const specSelect = document.getElementById('campaignSpecSelect');
    if (!specSelect) return;

    // Untuk DM: hanya ambil spesialisasi dokter yang benar-benar ada di distrik DM
    let targetDocs = allDoctors;
    if (currentUserRole === 'dm' && currentUserDistrict) {
        targetDocs = targetDocs.filter(d => d.district === currentUserDistrict);
    }

    const uniqueSpecs = Array.from(new Set(targetDocs.map(d => d.specialization).filter(Boolean))).sort();

    specSelect.innerHTML = '<option value="">-- Semua Spesialisasi --</option>' +
        uniqueSpecs.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('');
}

function renderCampaignDoctors() {
    const docBox = document.getElementById('campaignDoctorCheckboxes');
    if (!docBox) return;

    // Filter dokter strictly hanya dari distrik DM yang sedang aktif
    let filtered = allDoctors;
    if (currentUserRole === 'dm' && currentUserDistrict) {
        filtered = filtered.filter(d => d.district === currentUserDistrict);
    }

    // Filter dinamis berdasarkan spesialisasi yang dipilih
    const specSelect = document.getElementById('campaignSpecSelect');
    const selectedSpec = specSelect ? specSelect.value : '';
    if (selectedSpec) {
        filtered = filtered.filter(d => (d.specialization || '') === selectedSpec);
    }

    // Filter pencarian nama / spesialisasi dokter secara real-time
    const searchInput = document.getElementById('campaignDoctorSearchInput');
    const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
    if (query) {
        filtered = filtered.filter(d => {
            const name = (d.name || '').toLowerCase();
            const spec = (d.specialization || '').toLowerCase();
            return name.includes(query) || spec.includes(query);
        });
    }

    if (!filtered.length) {
        docBox.innerHTML = `
            <div style="font-size: 0.8rem; color: var(--text-muted); text-align: center; padding: 1.25rem 0.5rem;">
                <i class="bi bi-search" style="font-size: 1.25rem; display: block; opacity: 0.4; margin-bottom: 0.35rem;"></i>
                Tidak ada dokter ${query ? `yang cocok dengan "${escapeHtml(query)}" ` : ''}${selectedSpec ? `spesialisasi "${escapeHtml(selectedSpec)}" ` : ''}di distrik ${escapeHtml(currentUserDistrict || '-')}.
            </div>
        `;
        updateCampaignSelectedDocCount();
        return;
    }

    docBox.innerHTML = filtered.map(d => {
        const isChecked = campaignSelectedDoctorIds.has(d.id);
        const addr = d.address || '-';
        return `
            <label class="doctor-chip-item ${isChecked ? 'is-selected' : ''}" id="docChip_${d.id}" style="padding: 0.6rem 0.75rem;">
                <div style="display: flex; align-items: flex-start; gap: 0.65rem; flex: 1;">
                    <input type="checkbox" name="campaign_doctor" value="${d.id}" ${isChecked ? 'checked' : ''} style="margin-top: 0.2rem;">
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.2rem;">
                            <strong style="color: var(--secondary); font-size: 0.85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(d.name)}</strong>
                            <span class="target-specialty-badge" style="font-size: 0.675rem; padding: 0.1rem 0.35rem; flex-shrink: 0;">${escapeHtml(d.specialization || 'Umum')}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: #64748b; line-height: 1.35;">
                            <i class="bi bi-building" style="color: #94a3b8; font-size: 0.775rem; flex-shrink: 0;" aria-hidden="true"></i>
                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(addr)}</span>
                        </div>
                    </div>
                </div>
            </label>
        `;
    }).join('');

    updateCampaignSelectedDocCount();
}

function onCampaignDoctorToggle(input) {
    if (!input) return;
    const docId = parseInt(input.value, 10);
    if (input.checked) {
        campaignSelectedDoctorIds.add(docId);
    } else {
        campaignSelectedDoctorIds.delete(docId);
    }
    const chip = input.closest('.doctor-chip-item');
    if (chip) {
        chip.classList.toggle('is-selected', input.checked);
    }
    updateCampaignSelectedDocCount();
}
window.onCampaignDoctorToggle = onCampaignDoctorToggle;

function selectAllCampaignDoctors() {
    const checkboxes = document.querySelectorAll('#campaignDoctorCheckboxes input[name="campaign_doctor"]');
    checkboxes.forEach(cb => {
        cb.checked = true;
        campaignSelectedDoctorIds.add(parseInt(cb.value, 10));
        const chip = cb.closest('.doctor-chip-item');
        if (chip) chip.classList.add('is-selected');
    });
    updateCampaignSelectedDocCount();
}
window.selectAllCampaignDoctors = selectAllCampaignDoctors;

function clearAllCampaignDoctors() {
    const checkboxes = document.querySelectorAll('#campaignDoctorCheckboxes input[name="campaign_doctor"]');
    checkboxes.forEach(cb => {
        cb.checked = false;
        campaignSelectedDoctorIds.delete(parseInt(cb.value, 10));
        const chip = cb.closest('.doctor-chip-item');
        if (chip) chip.classList.remove('is-selected');
    });
    updateCampaignSelectedDocCount();
}
window.clearAllCampaignDoctors = clearAllCampaignDoctors;

function updateCampaignSelectedDocCount() {
    const countEl = document.getElementById('campaignSelectedDocCount');
    if (countEl) {
        countEl.innerText = String(campaignSelectedDoctorIds.size);
    }
    document.querySelectorAll('.doctor-chip-item').forEach(chip => {
        const input = chip.querySelector('input[name="campaign_doctor"]');
        if (input) {
            const isChecked = campaignSelectedDoctorIds.has(parseInt(input.value, 10));
            input.checked = isChecked;
            chip.classList.toggle('is-selected', isChecked);
        }
    });
}
window.updateCampaignSelectedDocCount = updateCampaignSelectedDocCount;

function handleRsmGuidanceChange(e) {
    const selectedCampId = parseInt(e.target.value, 10);
    const durationSelect = document.getElementById('campaignDurationSelect');
    const titleInput = document.getElementById('campaignTitleInput');
    const specSelect = document.getElementById('campaignSpecSelect');
    const lockBanner = document.getElementById('campaignGuidanceLockBanner');
    const lockDesc = document.getElementById('campaignGuidanceLockDesc');

    // Reset status produk
    document.querySelectorAll('#campaignProductCheckboxes input[name="campaign_product"]').forEach(cb => {
        cb.checked = false;
        cb.disabled = false;
        const chip = cb.closest('.product-chip-item');
        if (chip) {
            chip.classList.remove('is-selected', 'is-disabled');
        }
    });

    if (!selectedCampId) {
        if (durationSelect) durationSelect.disabled = false;
        if (lockBanner) lockBanner.style.display = 'none';
        updateCampaignSelectedProdCount();
        return;
    }

    const camp = allCampaigns.find(c => c.id === selectedCampId);
    if (!camp) return;

    // Otomatis isi judul rekomendasi distrik
    if (titleInput) {
        titleInput.value = camp.title ? camp.title.replace(/[–—]/g, '-').trim() : '';
    }

    // Kunci durasi waktu sesuai arahan RSM
    if (durationSelect) {
        durationSelect.value = String(camp.duration_months);
        durationSelect.disabled = true;
    }

    // Tampilkan lock banner informatif
    if (lockBanner) {
        lockBanner.style.display = 'flex';
        if (lockDesc) {
            const prodList = (camp.products || []).map(p => p.name).join(', ') || `${camp.product_count || 0} Produk Prioritas`;
            lockDesc.innerText = `Program "${camp.title}" (${camp.duration_months} Bulan). Produk Prioritas: ${prodList}.`;
        }
    }

    // Kunci produk prioritas sesuai arahan RSM
    if (camp.products && camp.products.length) {
        const targetProductIds = new Set(camp.products.map(p => p.id));
        document.querySelectorAll('#campaignProductCheckboxes input[name="campaign_product"]').forEach(cb => {
            const pid = parseInt(cb.value, 10);
            const chip = cb.closest('.product-chip-item');
            if (targetProductIds.has(pid)) {
                cb.checked = true;
                if (chip) chip.classList.add('is-selected');
            }
            cb.disabled = true;
            if (chip) chip.classList.add('is-disabled');
        });
    }

    // Pilih spesialisasi jika ada arahan khusus dari RSM
    if (camp.specialization && specSelect) {
        specSelect.value = camp.specialization;
    }
    renderCampaignDoctors();
    updateCampaignSelectedProdCount();
}

function closeCreateCampaignModal() {
    const modal = document.getElementById('createCampaignModal');
    if (modal) modal.style.display = 'none';

    // Kembalikan input yang sempat di-disable ke status normal
    const durationSelect = document.getElementById('campaignDurationSelect');
    if (durationSelect) durationSelect.disabled = false;
    const lockBanner = document.getElementById('campaignGuidanceLockBanner');
    if (lockBanner) lockBanner.style.display = 'none';

    document.querySelectorAll('#campaignProductCheckboxes input[name="campaign_product"]').forEach(cb => {
        cb.disabled = false;
        const chip = cb.closest('.product-chip-item');
        if (chip) chip.classList.remove('is-selected', 'is-disabled');
    });
}

async function handleCreateCampaignSubmit(e) {
    e.preventDefault();
    const errBox = document.getElementById('createCampaignError');
    if (errBox) errBox.style.display = 'none';

    const title = document.getElementById('campaignTitleInput')?.value?.trim();
    const durationSelect = document.getElementById('campaignDurationSelect');
    const duration = parseInt(durationSelect?.value, 10);
    const spec = document.getElementById('campaignSpecSelect')?.value?.trim();
    const notes = document.getElementById('campaignNotesInput')?.value?.trim();

    const selectedProducts = Array.from(document.querySelectorAll('#campaignProductCheckboxes input[name="campaign_product"]:checked')).map(el => parseInt(el.value, 10));
    const selectedDoctors = Array.from(campaignSelectedDoctorIds);

    if (!selectedProducts.length) {
        if (errBox) {
            errBox.innerText = 'Pilih minimal 1 produk prioritas.';
            errBox.style.display = 'block';
        }
        return;
    }
    if (selectedProducts.length > 5) {
        if (errBox) {
            errBox.innerText = 'Maksimal 5 produk prioritas.';
            errBox.style.display = 'block';
        }
        return;
    }

    // DM wajib memilih dokter target di distriknya
    if (currentUserRole === 'dm' && !selectedDoctors.length) {
        if (errBox) {
            errBox.innerText = `Pilih minimal 1 dokter target di distrik ${currentUserDistrict || ''}.`;
            errBox.style.display = 'block';
        }
        return;
    }

    const payload = {
        title,
        duration_months: duration,
        specialization: spec || null,
        product_ids: selectedProducts,
        doctor_ids: currentUserRole === 'dm' ? selectedDoctors : [],
        notes: notes || null,
    };

    const submitBtn = document.getElementById('btnSubmitCampaign');
    if (submitBtn) submitBtn.disabled = true;

    try {
        const res = await window.apiFetch('/api/managerial-campaigns', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        const result = await res.json();
        if (submitBtn) submitBtn.disabled = false;

        if (res.ok && result.success) {
            showAlert(currentUserRole === 'dm'
                ? 'Rekomendasi dokter distrik berhasil diajukan ke RSM untuk ditinjau.'
                : 'Arahan program kampanye regional berhasil diterbitkan.'
            );
            closeCreateCampaignModal();
            loadCampaigns();
        } else {
            if (errBox) {
                errBox.innerText = window.describeApiError(result || res, 'Gagal mengajukan program.', res.status);
                errBox.style.display = 'block';
            }
        }
    } catch (err) {
        if (submitBtn) submitBtn.disabled = false;
        if (errBox) {
            errBox.innerText = window.describeApiError(err, 'Terjadi kesalahan sistem saat mengirim pengajuan.');
            errBox.style.display = 'block';
        }
    }
}

async function handleApproveCampaign(campaignId) {
    const ok = await requestConfirmation('Setujui program rekomendasi distrik ini? Rekomendasi dokter dan produk prioritas akan otomatis aktif untuk seluruh MR di distrik terkait.', 'Konfirmasi Persetujuan Program');
    if (!ok) return;

        try {
        const res = await window.apiFetch(`/api/managerial-campaigns/${campaignId}/review`, {
            method: 'PATCH',
            body: JSON.stringify({ decision: 'approved' }),
        });
        const result = await res.json();
        if (res.ok && result.success) {
            showAlert('Program rekomendasi distrik berhasil disetujui.');
            loadCampaigns();
        } else {
            showAlert(window.describeApiError(result || res, 'Gagal menyetujui program.', res.status), 'danger');
        }
    } catch (err) {
        showAlert(window.describeApiError(err, 'Terjadi kesalahan saat menyetujui program.'), 'danger');
    }
}

function openRejectCampaignModal(campaignId) {
    const modal = document.getElementById('rejectCampaignModal');
    const idInput = document.getElementById('rejectCampaignId');
    const reasonInput = document.getElementById('rejectCampaignReasonInput');
    if (modal && idInput && reasonInput) {
        idInput.value = campaignId;
        reasonInput.value = '';
        modal.style.display = 'flex';
    }
}

function closeRejectCampaignModal() {
    const modal = document.getElementById('rejectCampaignModal');
    if (modal) modal.style.display = 'none';
}

async function handleRejectCampaignSubmit(e) {
    e.preventDefault();
    const campaignId = document.getElementById('rejectCampaignId')?.value;
    const reason = document.getElementById('rejectCampaignReasonInput')?.value?.trim();

    if (!reason) {
        showAlert('Alasan penolakan wajib diisi.', 'danger');
        return;
    }

    try {
        const res = await window.apiFetch(`/api/managerial-campaigns/${campaignId}/review`, {
            method: 'PATCH',
            body: JSON.stringify({ decision: 'rejected', reason }),
        });
        const result = await res.json();
        if (res.ok && result.success) {
            showAlert('Program rekomendasi berhasil ditolak dengan catatan.');
            closeRejectCampaignModal();
            loadCampaigns();
        } else {
            showAlert(window.describeApiError(result || res, 'Gagal menolak program.', res.status), 'danger');
        }
    } catch (err) {
        showAlert(window.describeApiError(err, 'Terjadi kesalahan saat memproses penolakan.'), 'danger');
    }
}

async function showUnvisitedDoctors(campaignId) {
    const modal = document.getElementById('unvisitedDoctorsModal');
    const listEl = document.getElementById('unvisitedDoctorsList');
    if (!modal || !listEl) return;

    listEl.innerHTML = '<li style="font-size: 0.8rem; color: var(--text-muted);">Memuat daftar dokter...</li>';
    modal.style.display = 'flex';

    try {
        const res = await window.apiFetch(`/api/managerial-campaigns/${campaignId}`);
        const result = await res.json();
        if (result.success && result.data) {
            const doctors = result.data.unvisited_doctors || [];
            if (!doctors.length) {
                listEl.innerHTML = '<li style="font-size: 0.8rem; color: var(--success); font-weight: 600;">Semua dokter telah berhasil dikunjungi!</li>';
                return;
            }
            listEl.innerHTML = doctors.map(d => {
                const addr = d.address || (d.district ? `Distrik ${d.district}` : '-');
                return `
                    <li style="padding: 0.6rem 0.75rem; background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.825rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.25rem;">
                            <strong style="color: var(--secondary); font-size: 0.85rem;">${escapeHtml(d.name)}</strong>
                            <span class="target-specialty-badge" style="font-size: 0.675rem; padding: 0.1rem 0.35rem; flex-shrink: 0;">${escapeHtml(d.specialization || 'Umum')}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: #64748b; line-height: 1.35;">
                            <i class="bi bi-building" style="color: #94a3b8; font-size: 0.775rem; flex-shrink: 0;" aria-hidden="true"></i>
                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(addr)}</span>
                        </div>
                    </li>
                `;
            }).join('');
        }
    } catch (e) {
        listEl.innerHTML = '<li style="color: var(--danger); font-size: 0.8rem;">Gagal memuat dokter belum dikunjungi.</li>';
    }
}

function closeUnvisitedModal() {
    const modal = document.getElementById('unvisitedDoctorsModal');
    if (modal) modal.style.display = 'none';
}
