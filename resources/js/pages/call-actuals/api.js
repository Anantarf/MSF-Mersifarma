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
    RESCHEDULE_REQUIRED: ['date', 1],
};

export async function callApi(url, options = {}) {
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

export function describeApiError(result, fallbackMessage) {
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