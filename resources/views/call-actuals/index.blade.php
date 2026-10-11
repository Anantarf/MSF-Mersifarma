@extends('layouts.app')

@section('title', 'Call Actual')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; color: var(--secondary); letter-spacing: -0.02em; margin-bottom: 0.15rem;">Pelaksanaan Kunjungan</h1>
            <p style="color: var(--text-muted); font-size: 0.8125rem;">Call Actual : Pelaporan kunjungan lapangan dengan bukti foto dan tanda tangan digital dokter.</p>
        </div>
        <button type="button" class="btn btn-outline" style="border-color: var(--primary); color: var(--primary); font-weight: 600;" onclick="openActualModal(null)">
            + Kunjungan di Luar Rencana
        </button>
    </div>

    <div id="actualStatusAlert" style="display: none;" class="alert"></div>

    <!-- Section 0: Kunjungan Sedang Berlangsung (Active Session P1) -->
    <section id="activeSessionSection" style="display: none; background: #ffffff; border: 1px solid var(--border); border-left: 4px solid var(--success); border-radius: 6px; padding: 1.15rem 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.7rem; font-weight: 700; color: var(--success); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">
                    <span style="width: 7px; height: 7px; border-radius: 50%; background: var(--success); display: inline-block;"></span>
                    Kunjungan Sedang Berlangsung (Server Check-in)
                </div>
                <h3 id="activeSessionDoctorName" style="font-size: 1.1rem; font-weight: 700; color: var(--secondary); margin: 0 0 0.15rem 0;">-</h3>
                <p id="activeSessionDoctorDetail" style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">-</p>
                <div style="margin-top: 0.4rem; font-size: 0.775rem; color: var(--text-main);">
                    <span style="color: var(--text-muted);">Check-in:</span> <strong id="activeSessionCheckInTime" class="tabular-nums">-</strong>
                    <span id="activeSessionCheckOutWrap" style="display: none; margin-left: 0.65rem;">
                        | <span style="color: var(--text-muted);">Check-out:</span> <strong id="activeSessionCheckOutTime" class="tabular-nums">-</strong>
                    </span>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <button type="button" id="btnSessionCheckout" class="btn btn-primary" onclick="handleSessionCheckout()">
                    Akhiri Kunjungan (Check-out)
                </button>
                <button type="button" id="btnSessionContinue" class="btn btn-primary" style="display: none;" onclick="openActualModalFromSession()">
                    Lanjutkan Pengisian Laporan
                </button>
                <button type="button" class="btn btn-outline" style="color: var(--danger); border-color: #fecdd3;" onclick="openCancelSessionModal()">
                    Batalkan Kunjungan
                </button>
            </div>
        </div>
    </section>

    <!-- Section 1: Today Plans (Planned First UX) -->
    <section style="display: flex; flex-direction: column; gap: 0.85rem;">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--secondary);">Jadwal Kunjungan Hari Ini</h2>
            <p style="font-size: 0.8rem; color: var(--text-muted);">Jadwal terencana (Planned Visit) diprioritaskan utama untuk efisiensi MR.</p>
        </div>
        <div id="todayPlansLoading" style="padding: 0.25rem 0;"></div>
        <div id="todayPlansTableWrap" class="table-responsive" style="display: none;">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>Jam Rencana</th>
                        <th>Target Dokter</th>
                        <th>Spesialisasi & Alamat</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="todayPlansTableBody"></tbody>
            </table>
        </div>
        <div id="todayPlansEmpty" style="display: none; padding: 2.5rem 1.5rem; color: var(--text-muted); text-align: center; background: white; border: 1px solid var(--border); border-radius: 8px;">
            <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--secondary); margin-bottom: 0.25rem;">Tidak Ada Jadwal Kunjungan Hari Ini</h4>
            <p style="font-size: 0.825rem; color: var(--text-muted); max-width: 400px; margin: 0 auto;">
                Gunakan tombol <strong>+ Kunjungan di Luar Rencana</strong> di atas jika Anda melakukan kunjungan di luar jadwal terencana.
            </p>
        </div>
    </section>

    <!-- Section 2: Completed Actuals History -->
    <section style="display: flex; flex-direction: column; gap: 0.85rem;">
        <div>
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--secondary);">Riwayat Kunjungan Selesai</h2>
            <p style="font-size: 0.8rem; color: var(--text-muted);">Daftar kunjungan yang telah berhasil dilaporkan dan terverifikasi.</p>
        </div>
        <div id="actualsLoading" style="padding: 0.25rem 0;"></div>
        <div id="actualsTableWrap" class="table-responsive" style="display: none;">
            <table class="enterprise-table">
                <thead>
                    <tr>
                        <th>Tanggal & Jam</th>
                        <th>Target Dokter</th>
                        <th>Kategori Kunjungan</th>
                        <th>Detailing Produk</th>
                        <th>Verifikasi Bukti</th>
                    </tr>
                </thead>
                <tbody id="actualsTableBody"></tbody>
            </table>
        </div>
        <div id="actualsEmpty" style="display: none; padding: 2.5rem 1.5rem; color: var(--text-muted); text-align: center; background: white; border: 1px solid var(--border); border-radius: 8px;">
            <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--secondary); margin-bottom: 0.25rem;">Belum Ada Riwayat Kunjungan</h4>
            <p style="font-size: 0.825rem; color: var(--text-muted); max-width: 380px; margin: 0 auto;">
                Kunjungan yang telah berhasil diselesaikan akan tercatat rapi di tabel ini sebagai bukti audit operasional.
            </p>
        </div>
    </section>
</div>

<!-- MODAL: Form Pelaporan Kunjungan (Call Actual) -->
<div id="actualModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card" style="max-width: 580px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 id="actualModalTitle" style="font-size: 1.2rem; font-weight: 700; color: var(--secondary);">Catat Kunjungan Lapangan</h3>
            <button type="button" class="close-btn" onclick="closeActualModal()">&times;</button>
        </div>

        <div id="actualModalError" style="display: none; margin-bottom: 0.85rem;" class="alert alert-danger"></div>

        <!-- 3-STEP PROGRESS STEPPER -->
        <div class="stepper-wrap" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; padding-bottom: 0.85rem; border-bottom: 1px solid var(--border);">
            <!-- Step 1 -->
            <div id="stepIndicator1" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;" onclick="goToActualStep(1)">
                <span class="step-num" style="width: 26px; height: 26px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700;">1</span>
                <span class="step-title" style="font-size: 0.825rem; font-weight: 700; color: var(--primary);">Dokter & Waktu</span>
            </div>
            <div style="flex: 1; height: 2px; background: var(--border); margin: 0 0.65rem;" id="stepLine1"></div>
            <!-- Step 2 -->
            <div id="stepIndicator2" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;" onclick="goToActualStep(2)">
                <span class="step-num" style="width: 26px; height: 26px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700;">2</span>
                <span class="step-title" style="font-size: 0.825rem; font-weight: 500; color: var(--text-muted);">Produk</span>
            </div>
            <div style="flex: 1; height: 2px; background: var(--border); margin: 0 0.65rem;" id="stepLine2"></div>
            <!-- Step 3 -->
            <div id="stepIndicator3" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;" onclick="goToActualStep(3)">
                <span class="step-num" style="width: 26px; height: 26px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700;">3</span>
                <span class="step-title" style="font-size: 0.825rem; font-weight: 500; color: var(--text-muted);">Bukti Kunjungan</span>
            </div>
        </div>

        <form id="actualForm" onsubmit="submitActual(event)">
            <input type="hidden" id="actualPlanId">
            <input type="hidden" id="actualSessionId">

            <!-- ================= STEP 1: DOKTER & WAKTU ================= -->
            <div id="stepSection1">
                <!-- 1. Pilihan Dokter -->
                <div style="margin-bottom: 1rem;">
                    <label for="actualDoctorSelect" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">
                        <i class="bi bi-person-circle" style="color: var(--primary); margin-right: 0.25rem;"></i>Dokter
                    </label>
                    <select id="actualDoctorSelect" required style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem;"></select>
                </div>

                <!-- 2. Tanggal Kunjungan -->
                <div style="margin-bottom: 1rem;">
                    <label for="actualDateDisplay" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">
                        <i class="bi bi-calendar-event" style="color: var(--primary); margin-right: 0.25rem;"></i>Tanggal Kunjungan
                    </label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="text" id="actualDateDisplay" readonly placeholder="Pilih tanggal kunjungan"
                               onclick="openActualDatePicker()"
                               style="width: 100%; padding: 0.55rem 2.5rem 0.55rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem; background: var(--surface); cursor: pointer; font-weight: 500; color: var(--secondary);"
                               title="Klik untuk memilih tanggal">
                        <button type="button" onclick="openActualDatePicker()" 
                                style="position: absolute; right: 0.5rem; background: none; border: none; color: var(--primary); cursor: pointer; padding: 0.25rem 0.4rem; font-size: 1.1rem; display: flex; align-items: center;"
                                title="Buka Kalender" aria-label="Buka Kalender">
                            <i class="bi bi-calendar3"></i>
                        </button>
                        <input type="date" id="actualDateInput" required
                               style="position: absolute; opacity: 0; pointer-events: none; width: 1px; height: 1px; bottom: 0; left: 0;"
                               onchange="syncActualDateDisplay()">
                    </div>
                </div>

                <!-- 3. Jam Kunjungan (Pas & Proporsional 50:50) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; margin-bottom: 1.15rem;">
                    <div>
                        <label for="checkInInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">
                            <i class="bi bi-clock" style="color: var(--primary); margin-right: 0.25rem;"></i>Jam Check-in
                        </label>
                        <input type="text" id="checkInInput" required value="08.30" maxlength="5" inputmode="numeric" placeholder="08.30"
                               style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem; font-variant-numeric: tabular-nums;"
                               oninput="window.autoFormatTimeInput(this)" onblur="window.normalizeTime24(this)">
                    </div>
                    <div>
                        <label for="checkOutInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">
                            <i class="bi bi-clock-history" style="color: var(--primary); margin-right: 0.25rem;"></i>Jam Check-out
                        </label>
                        <input type="text" id="checkOutInput" required value="09.00" maxlength="5" inputmode="numeric" placeholder="09.00"
                               style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.9rem; font-variant-numeric: tabular-nums;"
                               oninput="window.autoFormatTimeInput(this)" onblur="window.normalizeTime24(this)">
                    </div>
                </div>

                <!-- 3. Alasan Kunjungan (jika di luar rencana) -->
                <div id="visitReasonWrap" style="margin-bottom: 1.15rem; display: none;">
                    <label for="visitReasonInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">
                        Alasan Kunjungan (Opsional)
                    </label>
                    <textarea id="visitReasonInput" rows="5" style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.85rem; line-height: 1.5; resize: vertical;" placeholder="Contoh: Kunjungan mendadak atas permintaan dokter"></textarea>
                </div>

                <!-- Step 1 Footer Navigation -->
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 0.85rem; margin-top: 1rem;">
                    <div>
                        <button type="button" onclick="resetActualForm()" class="btn btn-outline btn-sm" style="color: var(--text-muted); border-color: var(--border); font-size: 0.8rem; display: inline-flex; align-items: center; gap: 0.35rem;" title="Kosongkan seluruh isian formulir">
                            <i class="bi bi-eraser"></i> Hapus Isian
                        </button>
                    </div>
                    <div style="display: flex; gap: 0.5rem; margin-left: auto;">
                        <button type="button" class="btn btn-outline" onclick="closeActualModal()">Batal</button>
                        <button type="button" class="btn btn-primary" onclick="goToActualStep(2)">
                            Lanjut <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ================= STEP 2: PRODUK ================= -->
            <div id="stepSection2" style="display: none;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--secondary);">
                        Pilih Produk
                    </label>
                    <p style="font-size: 0.775rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                        Centang produk obat yang dipresentasikan ke dokter.
                    </p>
                    <div class="modal-filter-wrap" style="margin-bottom: 0.5rem;">
                        <i class="bi bi-search"></i>
                        <input type="text" id="productSearchInput" class="modal-filter-input" placeholder="Cari produk obat..." oninput="filterProductPicker(this.value)">
                    </div>
                    <div id="productPicker" style="display: flex; flex-direction: column; gap: 0.45rem; max-height: 220px; overflow-y: auto; border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.5rem; background: #fafafa;">
                        Memuat produk...
                    </div>
                </div>

                <!-- Step 2 Footer Navigation -->
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 0.85rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="goToActualStep(1)">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </button>
                    <button type="button" class="btn btn-primary" onclick="goToActualStep(3)">
                        Lanjut <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ================= STEP 3: BUKTI & VERIFIKASI ================= -->
            <div id="stepSection3" style="display: none;">
                <!-- 1. Foto Kunjungan -->
                <div style="margin-bottom: 1.15rem; background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <label style="font-size: 0.825rem; font-weight: 700; color: var(--secondary);">Foto Bukti Kunjungan</label>
                        <button type="button" class="btn btn-outline" style="min-height: 26px; padding: 0.1rem 0.45rem; font-size: 0.725rem;" onclick="useSamplePhoto()">
                            + Gunakan Foto Sampel
                        </button>
                    </div>
                    <input type="hidden" id="photoUrlInput" required>

                    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                        <button type="button" class="btn btn-outline" style="min-height: 36px; font-size: 0.775rem; gap: 0.35rem;" onclick="toggleLiveCamera()">
                            <i class="bi bi-camera-video" style="font-size: 0.95rem; color: var(--primary);"></i>
                            <span id="liveCameraBtnText">Buka Kamera Langsung</span>
                        </button>
                        <label class="btn btn-outline" style="min-height: 36px; font-size: 0.775rem; cursor: pointer; gap: 0.35rem;">
                            <i class="bi bi-image" style="font-size: 0.95rem; color: var(--primary);"></i>
                            <span>Pilih Berkas Foto</span>
                            <input type="file" id="photoFileInput" accept="image/*" style="display: none;" onchange="handlePhotoFile(this)">
                        </label>
                        <div id="photoPreviewContainer" style="display: none; align-items: center; gap: 0.5rem; background: white; border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.2rem 0.5rem;">
                            <img id="photoPreviewImg" src="" alt="Pratinjau Foto" style="width: 32px; height: 32px; object-fit: cover; border-radius: 0.25rem;">
                            <span id="photoFileName" style="font-size: 0.725rem; color: var(--text-muted); max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                        </div>
                    </div>

                    <!-- Live Camera Viewfinder -->
                    <div id="cameraViewfinderWrap" style="display: none; margin-top: 0.65rem; border: 1px solid var(--border); border-radius: 0.5rem; overflow: hidden; background: #0f172a; position: relative;">
                        <video id="cameraVideo" autoplay playsinline style="width: 100%; max-height: 200px; object-fit: cover; display: block;"></video>
                        <div style="position: absolute; bottom: 8px; left: 0; right: 0; display: flex; justify-content: center; gap: 0.5rem; padding: 0.4rem; background: linear-gradient(transparent, rgba(15,23,42,0.85));">
                            <button type="button" class="btn btn-primary" style="font-size: 0.775rem; padding: 0.3rem 0.75rem;" onclick="capturePhotoFromCamera()">
                                <i class="bi bi-camera"></i> Ambil Foto Sekarang
                            </button>
                            <button type="button" class="btn btn-outline" style="font-size: 0.775rem; padding: 0.3rem 0.65rem; background: #ffffff; color: var(--secondary);" onclick="stopLiveCamera()">
                                Tutup Kamera
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 2. Verifikasi Bukti Dokter -->
                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.75rem; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.35rem;">
                        <label style="font-size: 0.825rem; font-weight: 700; color: var(--secondary);">Verifikasi Dokter</label>
                        <div style="display: flex; gap: 0.25rem; background: #e2e8f0; padding: 0.15rem; border-radius: 0.375rem;">
                            <button type="button" id="tabSignTouch" class="btn btn-sm" style="font-size: 0.725rem; padding: 0.2rem 0.55rem; border-radius: 0.25rem; background: white; color: var(--primary); font-weight: 600;" onclick="switchVerificationMode('touch')">
                                <i class="bi bi-pen"></i> Tanda Tangan Layar
                            </button>
                            <button type="button" id="tabSignStamp" class="btn btn-sm" style="font-size: 0.725rem; padding: 0.2rem 0.55rem; border-radius: 0.25rem; background: transparent; color: var(--text-muted);" onclick="switchVerificationMode('stamp')">
                                <i class="bi bi-patch-check"></i> Foto Stempel / Resep
                            </button>
                        </div>
                    </div>

                    <input type="hidden" id="signatureUrlInput" required>

                    <!-- Panel 1: Tanda Tangan Layar Sentuh -->
                    <div id="panelSignTouch">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                            <div id="sigBadgeRecorded" style="display: none; align-items: center; gap: 0.35rem; font-size: 0.725rem; font-weight: 600; color: var(--success); background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.1rem 0.45rem; border-radius: 0.25rem;">
                                <i class="bi bi-check-circle-fill"></i> Tanda Tangan Terisi
                            </div>
                            <button type="button" class="btn btn-outline" style="min-height: 26px; padding: 0.1rem 0.45rem; font-size: 0.725rem; margin-left: auto;" onclick="clearSignature()">
                                <i class="bi bi-eraser"></i> Bersihkan Kanvas
                            </button>
                        </div>
                        <div style="border: 1px solid var(--border); border-radius: 0.375rem; background: #ffffff; padding: 0.2rem; touch-action: none; position: relative;">
                            <canvas id="signatureCanvas" width="500" height="110" style="width: 100%; height: 110px; display: block; border-radius: 0.25rem; cursor: crosshair; background: #fafafa;"></canvas>
                            <div id="sigPlaceholder" style="position: absolute; bottom: 14px; left: 14px; right: 14px; pointer-events: none; border-bottom: 1px dashed #cbd5e1; display: flex; justify-content: space-between; align-items: flex-end; padding-bottom: 2px; transition: opacity 0.2s ease;">
                                <span style="font-size: 0.7rem; color: #94a3b8; font-weight: 500;">Dokter menandatangani langsung di atas garis ini</span>
                                <span style="font-size: 0.7rem; color: #cbd5e1; font-weight: 700;">✕</span>
                            </div>
                        </div>
                    </div>

                    <!-- Panel 2: Foto Stempel Basah / Paraf Resep Fisik -->
                    <div id="panelSignStamp" style="display: none; text-align: center; border: 1px dashed var(--border-strong); border-radius: 0.375rem; padding: 0.75rem; background: white;">
                        <p style="font-size: 0.775rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                            Unggah foto stempel basah klinik atau paraf fisik dokter pada lembar resep/kunjungan.
                        </p>
                        <div style="display: flex; justify-content: center; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <label class="btn btn-outline" style="min-height: 36px; font-size: 0.775rem; cursor: pointer; gap: 0.35rem;">
                                <i class="bi bi-camera" style="font-size: 0.95rem; color: var(--primary);"></i>
                                <span>Pilih / Foto Stempel Fisik</span>
                                <input type="file" id="stampFileInput" accept="image/*" style="display: none;" onchange="handleStampFile(this)">
                            </label>
                            <div id="stampPreviewContainer" style="display: none; align-items: center; gap: 0.5rem; background: white; border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.2rem 0.5rem;">
                                <img id="stampPreviewImg" src="" alt="Pratinjau Stempel" style="width: 30px; height: 30px; object-fit: cover; border-radius: 0.25rem;">
                                <span id="stampFileName" style="font-size: 0.725rem; color: var(--text-muted); max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                                <button type="button" onclick="clearStampFile()" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 0.85rem;" title="Hapus foto stempel">&times;</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3 Footer Navigation -->
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 0.85rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="goToActualStep(2)">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </button>
                    <button type="submit" id="actualSubmitButton" class="btn btn-primary" style="font-weight: 700;">
                        <i class="bi bi-check2-circle"></i> Simpan Laporan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Batalkan Sesi Kunjungan (P1 Feature) -->
<div id="cancelSessionModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--danger); margin: 0;">Batalkan Sesi Kunjungan</h3>
            <button type="button" class="close-btn" onclick="closeCancelSessionModal()">&times;</button>
        </div>

        <div id="cancelSessionModalError" style="display: none;" class="alert alert-danger"></div>

        <p style="font-size: 0.875rem; color: var(--secondary); margin-bottom: 1rem; line-height: 1.5;">
            Apakah Anda yakin ingin membatalkan sesi kunjungan ke <strong id="cancelSessionDoctorName">-</strong>? Jadwal terkait akan tetap tersimpan agar dapat dikunjungi kembali nanti.
        </p>

        <form id="cancelSessionForm" onsubmit="handleCancelSessionSubmit(event)">
            <input type="hidden" id="cancelSessionId">
            <div style="margin-bottom: 1.25rem;">
                <label for="cancelSessionReasonInput" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--secondary);">
                    Alasan Pembatalan Kunjungan (Wajib diisi):
                </label>
                <textarea id="cancelSessionReasonInput" required minlength="3" rows="3"
                          style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; font-size: 0.875rem;"
                          placeholder="Contoh: Dokter dipanggil operasi darurat sebelum detailing obat"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeCancelSessionModal()">Kembali</button>
                <button type="submit" id="btnConfirmCancelSession" class="btn btn-danger" style="background: var(--danger); color: white; border: none; font-weight: 600;">
                    Konfirmasi Batalkan Kunjungan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Lihat Bukti Kunjungan (Foto & TTD) -->
<div id="evidenceModal" class="modal-backdrop" style="display: none;">
    <div class="modal-card" style="max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--secondary); margin: 0;">
                Bukti Kunjungan: <span id="evidenceDoctorName">-</span>
            </h3>
            <button type="button" class="close-btn" onclick="closeEvidenceModal()">&times;</button>
        </div>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div>
                <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">
                    Foto Kunjungan di Lokasi:
                </label>
                <div style="border: 1px solid var(--border); border-radius: 6px; overflow: hidden; background: #f8fafc; text-align: center; max-height: 220px; display: flex; align-items: center; justify-content: center;">
                    <img id="evidencePhotoImg" src="" alt="Foto Lokasi" style="max-width: 100%; max-height: 220px; object-fit: contain;">
                </div>
            </div>
            <div>
                <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">
                    Tanda Tangan Digital Dokter:
                </label>
                <div style="border: 1px solid var(--border); border-radius: 6px; overflow: hidden; background: #ffffff; text-align: center; height: 110px; display: flex; align-items: center; justify-content: center; padding: 0.5rem;">
                    <img id="evidenceSignatureImg" src="" alt="Tanda Tangan Dokter" style="max-width: 100%; max-height: 100px; object-fit: contain;">
                </div>
            </div>
            <div style="text-align: right; border-top: 1px solid var(--border); padding-top: 0.75rem;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeEvidenceModal()">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
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
        initSignaturePad();
        await Promise.all([loadDoctors(), loadProducts()]);
        await loadActiveSession();
        await loadTodayPlans();
        loadActuals();

        // Listeners for draft auto-save
        document.getElementById('actualDoctorSelect')?.addEventListener('change', saveActualDraft);
        document.getElementById('actualDateInput')?.addEventListener('change', () => {
            syncActualDateDisplay();
            saveActualDraft();
        });
        document.getElementById('checkInInput')?.addEventListener('change', saveActualDraft);
        document.getElementById('checkOutInput')?.addEventListener('change', saveActualDraft);
        document.getElementById('visitReasonInput')?.addEventListener('input', saveActualDraft);

        const urlParams = new URLSearchParams(window.location.search);
        const autoPlanId = urlParams.get('plan_id');
        const autoDocId = urlParams.get('doctor_id');
        if (autoPlanId && !activeSession) {
            openActualModal(parseInt(autoPlanId), autoDocId ? parseInt(autoDocId) : null);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeActualModal();
            closeCancelSessionModal();
            closeEvidenceModal();
        }
    });

    function showEvidenceModal(doctorName, photoUrl, sigUrl) {
        document.getElementById('evidenceDoctorName').innerText = doctorName || 'Dokter';
        document.getElementById('evidencePhotoImg').src = photoUrl || '';
        document.getElementById('evidenceSignatureImg').src = sigUrl || '';
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
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            const scaleX = sigCanvas.width / rect.width;
            const scaleY = sigCanvas.height / rect.height;
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
                    document.getElementById('signatureUrlInput').value = sigCanvas.toDataURL('image/png');
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
                    document.getElementById('signatureUrlInput').value = sigCanvas.toDataURL('image/png');
                    updateSigBadge(true);
                }
                saveActualDraft();
            }
        }, { passive: false });
    }

    let verificationMode = 'touch';

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
            tabStamp.style.fontWeight = '400';
            tabStamp.style.boxShadow = 'none';

            panelTouch.style.display = 'block';
            panelStamp.style.display = 'none';

            // Sync touch signature if canvas has content
            const badge = document.getElementById('sigBadgeRecorded');
            if (badge && badge.style.display === 'inline-flex' && sigCanvas) {
                document.getElementById('signatureUrlInput').value = sigCanvas.toDataURL('image/png');
            } else {
                document.getElementById('signatureUrlInput').value = '';
            }
        } else {
            tabStamp.style.background = 'white';
            tabStamp.style.color = 'var(--primary)';
            tabStamp.style.fontWeight = '600';
            tabStamp.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';

            tabTouch.style.background = 'transparent';
            tabTouch.style.color = 'var(--text-muted)';
            tabTouch.style.fontWeight = '400';
            tabTouch.style.boxShadow = 'none';

            panelTouch.style.display = 'none';
            panelStamp.style.display = 'block';

            // Sync stamp preview if available
            const stampImg = document.getElementById('stampPreviewImg');
            if (stampImg && stampImg.src && stampImg.src.startsWith('data:')) {
                document.getElementById('signatureUrlInput').value = stampImg.src;
            } else {
                document.getElementById('signatureUrlInput').value = '';
            }
        }
        saveActualDraft();
    }

    function setSigPlaceholderVisible(visible) {
        const el = document.getElementById('sigPlaceholder');
        if (el) el.style.opacity = visible ? '1' : '0';
    }

    function updateSigBadge(hasContent) {
        const badge = document.getElementById('sigBadgeRecorded');
        if (badge) badge.style.display = hasContent ? 'inline-flex' : 'none';
    }

    function clearSignature() {
        if (!sigCanvas || !sigCtx) return;
        sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        if (verificationMode === 'touch') {
            document.getElementById('signatureUrlInput').value = '';
        }
        setSigPlaceholderVisible(true);
        updateSigBadge(false);
        saveActualDraft();
    }

    function handleStampFile(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                const dataUrl = e.target.result;
                document.getElementById('signatureUrlInput').value = dataUrl;
                document.getElementById('stampPreviewImg').src = dataUrl;
                document.getElementById('stampFileName').innerText = file.name;
                document.getElementById('stampPreviewContainer').style.display = 'inline-flex';
                saveActualDraft();
                window.showToast('Foto stempel/paraf fisik berhasil disimpan.', 'success');
            };
            reader.readAsDataURL(file);
        }
    }

    function clearStampFile() {
        const fileInput = document.getElementById('stampFileInput');
        if (fileInput) fileInput.value = '';
        document.getElementById('stampPreviewImg').src = '';
        document.getElementById('stampFileName').innerText = '';
        document.getElementById('stampPreviewContainer').style.display = 'none';
        if (verificationMode === 'stamp') {
            document.getElementById('signatureUrlInput').value = '';
        }
        saveActualDraft();
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
        if (btnText) btnText.innerText = 'Buka Kamera Langsung';
        const video = document.getElementById('cameraVideo');
        if (video) video.srcObject = null;
    }

    function capturePhotoFromCamera() {
        const video = document.getElementById('cameraVideo');
        if (!video || !cameraStream) return;

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        document.getElementById('photoUrlInput').value = dataUrl;
        document.getElementById('photoPreviewImg').src = dataUrl;
        document.getElementById('photoFileName').innerText = 'kamera-langsung-' + Date.now() + '.jpg';
        document.getElementById('photoPreviewContainer').style.display = 'flex';

        stopLiveCamera();
        saveActualDraft();
        window.showToast('Foto berhasil diambil dari kamera langsung.', 'success');
    }

    function handlePhotoFile(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                const dataUrl = e.target.result;
                document.getElementById('photoUrlInput').value = dataUrl;
                document.getElementById('photoPreviewImg').src = dataUrl;
                document.getElementById('photoFileName').innerText = file.name;
                document.getElementById('photoPreviewContainer').style.display = 'flex';
                saveActualDraft();
            };
            reader.readAsDataURL(file);
        }
    }

    function useSamplePhoto() {
        const svgData = `<svg xmlns="http://www.w3.org/2000/svg" width="300" height="200" viewBox="0 0 300 200"><rect width="300" height="200" fill="#e0f2fe"/><rect x="20" y="20" width="260" height="160" rx="8" fill="#ffffff" stroke="#0284c7" stroke-width="2"/><circle cx="150" cy="85" r="35" fill="#f0f9ff" stroke="#0ea5e9" stroke-width="2"/><path d="M150 65v40M130 85h40" stroke="#0284c7" stroke-width="4" stroke-linecap="round"/><text x="150" y="145" font-family="sans-serif" font-size="12" font-weight="bold" fill="#0f172a" text-anchor="middle">KLINIK MITRA MERSIFARMA</text><text x="150" y="162" font-family="sans-serif" font-size="10" fill="#64748b" text-anchor="middle">Bukti Kunjungan Lapangan Terverifikasi</text></svg>`;
        const dataUrl = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svgData);
        document.getElementById('photoUrlInput').value = dataUrl;
        document.getElementById('photoPreviewImg').src = dataUrl;
        document.getElementById('photoFileName').innerText = 'klinik-mitra-verified.svg';
        document.getElementById('photoPreviewContainer').style.display = 'flex';
        saveActualDraft();
    }

    function saveActualDraft() {
        const planId = document.getElementById('actualPlanId')?.value;
        const sessionId = document.getElementById('actualSessionId')?.value;
        if (sessionId) return; // sesi berjalan menggunakan pencatatan server

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
            localStorage.setItem('msf_actual_draft', JSON.stringify(draft));
            updateDraftBadge();
        } catch (e) {
            // Storage quota fallback
        }
    }

    function updateDraftBadge() {
        // Indikator draf ditiadakan sesuai preferensi clean UI
    }

    function clearActualDraft(showNotice = false) {
        localStorage.removeItem('msf_actual_draft');
        if (showNotice) {
            window.showToast('Isian formulir telah dikosongkan.', 'info');
        }
    }

    function resetActualForm() {
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

        // Reset pilihan produk di Step 2
        document.querySelectorAll('#productPicker input[name="product_ids"]').forEach(input => {
            input.checked = false;
        });

        // Reset foto di Step 3
        document.getElementById('photoUrlInput').value = '';
        document.getElementById('photoPreviewContainer').style.display = 'none';
        document.getElementById('photoPreviewImg').src = '';
        document.getElementById('photoFileName').innerText = '';
        const photoFile = document.getElementById('photoFileInput');
        if (photoFile) photoFile.value = '';

        // Reset tanda tangan / stempel di Step 3
        clearSignature();
        clearStampFile();

        // Kembali ke Langkah 1
        currentActualStep = 1;
        renderStepView();

        window.showToast('Seluruh isian formulir berhasil dikosongkan.', 'info');
    }

    function restoreActualDraft(planId) {
        const raw = localStorage.getItem('msf_actual_draft');
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
            todayPlans = result.success ? result.data : [];
            renderTodayPlans();
        } finally {
            loading.style.display = 'none';
        }
    }

    async function loadActuals() {
        const loading = document.getElementById('actualsLoading');
        if (loading) {
            loading.innerHTML = window.renderTableSkeleton(3);
            loading.style.display = 'block';
        }
        try {
            const res = await window.apiFetch('/api/call-actuals');
            const result = await res.json();
            actuals = result.success ? result.data : [];
            renderActuals();
        } finally {
            loading.style.display = 'none';
        }
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

    function renderProductPicker() {
        const picker = document.getElementById('productPicker');
        if (!picker) return;
        if (!products.length) {
            picker.innerHTML = '<span style="color: var(--text-muted); font-size: 0.85rem;">Produk belum tersedia.</span>';
            return;
        }
        picker.innerHTML = products.map((product) => `
            <label style="display: flex; gap: 0.5rem; align-items: flex-start; background: white; border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.5rem; font-size: 0.85rem; cursor: pointer; transition: background 0.15s ease;">
                <input type="checkbox" name="product_ids" value="${product.id}" onchange="saveActualDraft()" style="margin-top: 0.2rem;">
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong>${escapeHtml(product.name)}</strong>
                        <span style="font-size: 0.7rem; font-weight: 600; color: var(--primary); background: var(--primary-light); padding: 0.1rem 0.4rem; border-radius: 0.25rem;">${escapeHtml(product.category || 'Farmasi')}</span>
                    </div>
                    <small style="color: var(--text-muted); font-size: 0.75rem;">${escapeHtml(product.dosage_form || 'Sediaan Tablet/Kapsul')}</small>
                </div>
            </label>
        `).join('');
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
                        ● Sedang Berlangsung
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
                        <button type="button" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;" onclick="startVisitSession(${plan.id}, ${plan.doctor_id})">
                            Mulai Kunjungan
                        </button>
                        <button type="button" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.35rem 0.6rem; color: var(--text-muted);" onclick="openActualModal(${plan.id})" title="Atau isi laporan langsung secara manual">
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
            empty.style.display = 'block';
            return;
        }
        empty.style.display = 'none';
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
            if (actual.check_in && actual.check_out) {
                const inTime = formatWaktuIndonesia(actual.check_in).replace(' WIB', '');
                const outTime = formatWaktuIndonesia(actual.check_out);
                timeHtml = `<div style="font-size: 0.75rem; color: var(--text-muted);">${inTime} - ${outTime}</div>`;
            } else if (actual.check_in) {
                timeHtml = `<div style="font-size: 0.75rem; color: var(--text-muted);">${formatWaktuIndonesia(actual.check_in)}</div>`;
            }

            return `
                <tr>
                    <td style="white-space: nowrap;">
                        <strong style="color: var(--secondary); font-size: 0.9rem;">${formatVisitDateIndonesian(actual.visit_date)}</strong>
                        ${timeHtml}
                    </td>
                    <td style="white-space: nowrap;">
                        <strong style="font-size: 0.95rem; color: var(--secondary);">${escapeHtml(actual.doctor_name)}</strong>
                        <div style="font-size: 0.775rem; color: var(--text-muted);">${escapeHtml(actual.doctor_specialization || 'Spesialis Umum')}</div>
                    </td>
                    <td>
                        <span class="status-badge ${visitBadgeClass}">${escapeHtml(visitTypeLabel)}</span>
                        ${actual.visit_reason ? `
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; font-style: italic;">
                                "${escapeHtml(actual.visit_reason)}"
                            </div>
                        ` : ''}
                    </td>
                    <td>
                        <div style="font-size: 0.825rem; font-weight: 600; color: var(--secondary);">
                            ${actual.products.map((p) => escapeHtml(p.name)).join(', ')}
                        </div>
                    </td>
                    <td style="white-space: nowrap;">
                        <button type="button" class="btn btn-outline btn-sm" onclick="showEvidenceModal('${escapeHtml(actual.doctor_name)}', '${actual.photo_url}', '${actual.signature_url}')" style="font-size: 0.775rem; color: var(--success); border-color: #a7f3d0; background: #ecfdf5;">
                            <i class="bi bi-file-earmark-check"></i> Foto & TTD
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    let currentActualStep = 1;

    function goToActualStep(step) {
        const errEl = document.getElementById('actualModalError');
        errEl.style.display = 'none';

        // Validasi saat ingin maju dari Step 1 ke Step 2 atau 3
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

        // Validasi saat ingin maju dari Step 2 ke Step 3
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
    }

    function openActualModal(planId, fallbackDoctorId = null) {
        const plan = planId ? todayPlans.find((item) => item.id === planId) : null;
        document.getElementById('actualPlanId').value = plan ? plan.id : (planId || '');
        document.getElementById('actualModalTitle').innerText = (plan || planId) ? 'Laporan Kunjungan Terencana' : 'Laporan Kunjungan di Luar Rencana';
        document.getElementById('actualDateInput').value = todayString();
        syncActualDateDisplay();
        document.getElementById('visitReasonWrap').style.display = (plan || planId) ? 'none' : 'block';
        document.getElementById('actualDoctorSelect').disabled = !!(plan || planId);
        
        const docIdToSelect = plan ? plan.doctor_id : (fallbackDoctorId || '');
        renderDoctorSelect(docIdToSelect);
        renderProductPicker();

        // Reset foto & tanda tangan
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
        restoreActualDraft(planId);
    }

    function closeActualModal() {
        stopLiveCamera();
        currentActualStep = 1;
        document.getElementById('actualModal').style.display = 'none';
    }

    async function submitActual(event) {
        event.preventDefault();
        const errorEl = document.getElementById('actualModalError');
        const button = document.getElementById('actualSubmitButton');
        errorEl.style.display = 'none';

        const photoUrl = document.getElementById('photoUrlInput').value.trim();
        if (!photoUrl) {
            errorEl.innerText = 'Foto kunjungan di lokasi klinik/praktik dokter wajib diambil atau diunggah.';
            errorEl.style.display = 'block';
            return;
        }

        const signatureUrl = document.getElementById('signatureUrlInput').value.trim();
        if (!signatureUrl) {
            errorEl.innerText = 'Bukti verifikasi dokter (tanda tangan atau stempel fisik) wajib diisi.';
            errorEl.style.display = 'block';
            return;
        }

        const productIds = Array.from(document.querySelectorAll('#productPicker input[name="product_ids"]:checked')).map((input) => parseInt(input.value));
        if (!productIds.length) {
            errorEl.innerText = 'Pilih minimal satu produk obat.';
            errorEl.style.display = 'block';
            return;
        }

        button.disabled = true;
        button.innerText = 'Menyimpan...';

        try {
            const planId = document.getElementById('actualPlanId').value;
            const sessionId = document.getElementById('actualSessionId').value;
            const payload = {
                photo_url: photoUrl,
                signature_url: signatureUrl,
                products: productIds,
                visit_reason: planId ? null : (document.getElementById('visitReasonInput').value.trim() || null)
            };

            if (sessionId) {
                payload.visit_session_id = parseInt(sessionId);
            } else {
                payload.plan_id = planId ? parseInt(planId) : null;
                payload.doctor_id = parseInt(document.getElementById('actualDoctorSelect').value);
                payload.visit_date = document.getElementById('actualDateInput').value;
                payload.check_in = document.getElementById('checkInInput').value.trim().replace('.', ':');
                payload.check_out = document.getElementById('checkOutInput').value.trim().replace('.', ':');
            }

            const res = await window.apiFetch('/api/call-actuals', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.success) {
                clearActualDraft(false);
                closeActualModal();
                showActualAlert('Laporan kunjungan dokter berhasil disimpan.');
                await loadActiveSession();
                loadTodayPlans();
                loadActuals();
            } else {
                errorEl.innerText = result.message || 'Gagal menyimpan kunjungan.';
                errorEl.style.display = 'block';
            }
        } catch (error) {
            errorEl.innerText = 'Terjadi kesalahan jaringan atau server saat menyimpan.';
            errorEl.style.display = 'block';
        } finally {
            button.disabled = false;
            button.innerText = 'Simpan Laporan';
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
                showActualAlert('Sesi kunjungan berhasil dimulai. Check-in tercatat otomatis oleh server.');
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
                showActualAlert('Waktu check-out berhasil dicatat server. Silakan lengkapi bukti foto dan tanda tangan.');
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

        openActualModal(activeSession.plan_id, activeSession.doctor_id);
        document.getElementById('actualSessionId').value = activeSession.id;
        document.getElementById('actualModalTitle').innerText = 'Lengkapi Laporan Kunjungan Sesi Ini';

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
            btn.innerText = 'Konfirmasi Batalkan Kunjungan';

            if (result.success) {
                closeCancelSessionModal();
                showActualAlert('Sesi kunjungan berhasil dibatalkan. Jadwal terkait tetap tersedia untuk dikunjungi kembali.');
                await loadActiveSession();
                renderTodayPlans();
            } else {
                errEl.innerText = result.message || 'Gagal membatalkan sesi kunjungan.';
                errEl.style.display = 'block';
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerText = 'Konfirmasi Batalkan Kunjungan';
            errEl.innerText = 'Terjadi kesalahan jaringan atau server saat membatalkan sesi.';
            errEl.style.display = 'block';
        }
    }
</script>
@endpush

@push('styles')
<style>
    .call-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 0.85rem;
        box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.04), 0 1px 2px -1px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        transition: transform 0.2s var(--ease-spring), box-shadow 0.2s var(--ease-spring), border-color 0.2s ease;
    }
    .call-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1;
        box-shadow: 0 8px 16px -4px rgba(15, 23, 42, 0.08), 0 4px 6px -2px rgba(15, 23, 42, 0.03);
    }
    .status-badge {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-variant-numeric: tabular-nums;
        border: 1px solid transparent;
    }
    .status-draft { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .status-scheduled { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }
    .status-approved { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }

    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 50;
        padding: 1rem;
    }
    .modal-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 8px;
        width: 100%;
        max-width: 580px;
        padding: 1.5rem;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.2);
    }
    .close-btn {
        background: transparent;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--text-muted);
        line-height: 1;
    }
</style>
@endpush
@endsection