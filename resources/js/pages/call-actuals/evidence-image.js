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

export function drawImageToCanvas(image, maxDimension) {
    const ratio = Math.min(1, maxDimension / Math.max(image.width, image.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(image.width * ratio));
    canvas.height = Math.max(1, Math.round(image.height * ratio));
    canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
    return canvas;
}

export function canvasToEvidenceDataUrl(canvas, initialQuality = 0.72) {
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

export async function fileToEvidenceDataUrl(file, maxDimension = 900) {
    if (!file || !file.type.startsWith('image/')) {
        throw new Error('File harus berupa gambar.');
    }

    const rawDataUrl = await readFileAsDataUrl(file);
    const image = await loadImage(rawDataUrl);
    const canvas = drawImageToCanvas(image, maxDimension);

    return canvasToEvidenceDataUrl(canvas);
}