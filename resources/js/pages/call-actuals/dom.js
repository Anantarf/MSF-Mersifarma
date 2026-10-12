export function byId(id) {
    return document.getElementById(id);
}

export function isVisible(id) {
    const el = byId(id);
    return !!el && el.style.display !== 'none';
}