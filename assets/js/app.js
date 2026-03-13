/* ========================================
   WFH Attendance Portal - Core JS
   ======================================== */

const BASE_URL = '';

// Toast Notifications
function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const icons = {
        success: '&#10003;',
        error: '&#10007;',
        warning: '&#9888;'
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.success}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Fetch API wrapper
async function fetchAPI(url, data = null, method = 'POST') {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    };

    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(BASE_URL + url, options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'Network error. Please try again.' };
    }
}

// Live Clock
function startLiveClock(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;

    function updateClock() {
        const now = new Date();
        const hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const h12 = hours % 12 || 12;
        el.textContent = `${String(h12).padStart(2, '0')}:${minutes}:${seconds} ${ampm}`;
    }

    updateClock();
    setInterval(updateClock, 1000);
}

// Live Date
function updateDateDisplay(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;

    const now = new Date();
    const days = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];
    const months = ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
                    'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'];

    el.textContent = `${days[now.getDay()]}, ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
}

// Password Toggle
function initPasswordToggle() {
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('input');
            const eyeIcon = btn.getAttribute('data-icon-eye');
            const eyeOffIcon = btn.getAttribute('data-icon-off');
            if (input.type === 'password') {
                input.type = 'text';
                if (eyeIcon) btn.innerHTML = eyeIcon;
            } else {
                input.type = 'password';
                if (eyeOffIcon) btn.innerHTML = eyeOffIcon;
            }
        });
    });
}

// Accomplishments Input Handler
function initAccomplishments() {
    const textarea = document.getElementById('accomplishments-input');
    if (!textarea) return;

    const itemCounter = document.getElementById('item-counter');
    const charCounter = document.getElementById('char-counter');

    textarea.addEventListener('input', () => {
        const lines = textarea.value.split('\n').filter(l => l.trim() !== '');
        const totalChars = lines.reduce((sum, l) => sum + l.trim().length, 0);

        if (lines.length > 4) {
            const trimmed = textarea.value.split('\n').slice(0, 4).join('\n');
            textarea.value = trimmed;
        }

        const currentLines = textarea.value.split('\n').filter(l => l.trim() !== '');
        const currentChars = currentLines.reduce((sum, l) => sum + l.trim().length, 0);

        if (itemCounter) itemCounter.textContent = `${currentLines.length} / 4 items`;
        if (charCounter) charCounter.textContent = `${currentChars} / 300 chars`;

        if (currentChars > 300) {
            textarea.style.borderColor = '#dc2626';
        } else {
            textarea.style.borderColor = '';
        }
    });

    textarea.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const lines = textarea.value.split('\n').filter(l => l.trim() !== '');
            if (lines.length >= 4) {
                e.preventDefault();
            }
        }
    });
}

function getAccomplishments() {
    const textarea = document.getElementById('accomplishments-input');
    if (!textarea) return [];
    return textarea.value.split('\n')
        .map(l => l.trim())
        .filter(l => l !== '')
        .slice(0, 4)
        .map(l => l.substring(0, 300));
}

function getConfirmModalMarkup(title, message, confirmText, cancelText, variant) {
    const iconMap = {
        danger: '&#9888;',
        warning: '&#9888;',
        success: '&#10003;'
    };
    const iconBgMap = {
        danger: 'rgba(220, 38, 38, 0.12)',
        warning: 'rgba(245, 158, 11, 0.14)',
        success: 'rgba(22, 163, 74, 0.12)'
    };
    const iconColorMap = {
        danger: '#dc2626',
        warning: '#d97706',
        success: '#16a34a'
    };

    return `
        <div class="modal" style="max-width:380px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;background:${iconBgMap[variant] || iconBgMap.warning};color:${iconColorMap[variant] || iconColorMap.warning};font-size:1.4rem;font-weight:700;">
                ${iconMap[variant] || iconMap.warning}
            </div>
            <h3 style="margin-bottom:0.75rem;">${title}</h3>
            <p style="margin:0 0 1.25rem;color:var(--text-medium);line-height:1.6;"></p>
            <div style="display:flex;gap:0.75rem;justify-content:center;">
                <button type="button" class="btn btn-secondary" data-confirm-cancel>${cancelText}</button>
                <button type="button" class="btn btn-primary" data-confirm-accept>${confirmText}</button>
            </div>
        </div>
    `;
}

function showConfirmModal({
    title = 'Please Confirm',
    message,
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    variant = 'warning'
} = {}) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.style.zIndex = '10001';
        overlay.innerHTML = getConfirmModalMarkup(title, message, confirmText, cancelText, variant);

        const modal = overlay.querySelector('.modal');
        const messageEl = modal.querySelector('p');
        const acceptBtn = modal.querySelector('[data-confirm-accept]');
        const cancelBtn = modal.querySelector('[data-confirm-cancel]');

        messageEl.textContent = message || '';
        document.body.appendChild(overlay);

        let settled = false;
        const close = (result) => {
            if (settled) return;
            settled = true;
            document.removeEventListener('keydown', onKeyDown);
            overlay.classList.remove('show');
            setTimeout(() => {
                overlay.remove();
                resolve(result);
            }, 180);
        };

        acceptBtn.addEventListener('click', () => close(true));
        cancelBtn.addEventListener('click', () => close(false));
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) close(false);
        });

        const onKeyDown = (event) => {
            if (event.key === 'Escape') close(false);
        };

        document.addEventListener('keydown', onKeyDown);
        requestAnimationFrame(() => overlay.classList.add('show'));
    });
}

// Confirm dialog
function confirmAction(message, options = {}) {
    return showConfirmModal({
        message,
        ...options
    });
}

// Format helpers
function formatTime12(timeStr) {
    if (!timeStr) return '--';
    const [h, m] = timeStr.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const h12 = hour % 12 || 12;
    return `${String(h12).padStart(2, '0')}:${m} ${ampm}`;
}

// Disable right-click context menu
// document.addEventListener('contextmenu', (e) => e.preventDefault());

// Initialize common features
document.addEventListener('DOMContentLoaded', () => {
    initPasswordToggle();
    initAccomplishments();
});

/* ========================================
   Device Fingerprint / MAC-Address Proxy
   Generates a persistent device identifier stored in localStorage.
   Used to detect if different employees are logging from the same device.
   ======================================== */
function getDeviceFingerprint() {
    const STORAGE_KEY = 'wfh_device_fp';
    let stored = localStorage.getItem(STORAGE_KEY);
    if (stored && /^[a-f0-9]{32}$/.test(stored)) {
        return stored;
    }
    // Build a fingerprint from stable browser characteristics
    const parts = [
        navigator.userAgent,
        navigator.language || '',
        screen.colorDepth,
        screen.width + 'x' + screen.height,
        new Date().getTimezoneOffset(),
        navigator.hardwareConcurrency || 0,
        navigator.deviceMemory || 0,
    ];
    // Canvas fingerprint for extra entropy
    try {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.fillStyle = '#f60';
        ctx.fillRect(125, 1, 62, 20);
        ctx.fillStyle = '#069';
        ctx.fillText('WFH-SDOSPC', 2, 15);
        ctx.fillStyle = 'rgba(102,204,0,0.7)';
        ctx.fillText('WFH-SDOSPC', 4, 17);
        parts.push(canvas.toDataURL());
    } catch (_) {}

    // Simple hash (djb2 variant) → 32-char hex string
    let hash1 = 5381, hash2 = 0x811c9dc5;
    const str = parts.join('||');
    for (let i = 0; i < str.length; i++) {
        const c = str.charCodeAt(i);
        hash1 = ((hash1 << 5) + hash1) ^ c;
        hash2 = (hash2 ^ c) * 0x01000193;
    }
    // Add random salt so two identical machines don't share the same ID
    const salt = Math.random().toString(36).slice(2, 10);
    const saltHash = Array.from(salt).reduce((h, c) => ((h << 5) + h) ^ c.charCodeAt(0), 0);
    const combined = (Math.abs(hash1) >>> 0).toString(16).padStart(8, '0')
        + (Math.abs(hash2) >>> 0).toString(16).padStart(8, '0')
        + (Math.abs(saltHash) >>> 0).toString(16).padStart(8, '0')
        + Date.now().toString(16).slice(-8);
    const fp = combined.padEnd(32, '0').slice(0, 32);
    localStorage.setItem(STORAGE_KEY, fp);
    return fp;
}
