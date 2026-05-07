(function () {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfMeta) {
        console.warn('[FCM] csrf-token meta tag not found — registration skipped');
        return;
    }
    const csrfToken = csrfMeta.getAttribute('content');

    function registerToken(token) {
        if (!token) return;
        const key = 'fcm_sent_' + token.slice(0, 20);
        if (sessionStorage.getItem(key)) return;
        fetch('/fcm/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ token }),
            credentials: 'same-origin',
        }).then(function (r) {
            if (r.ok) {
                sessionStorage.setItem(key, '1');
            } else {
                console.warn('[FCM] register failed:', r.status);
            }
        }).catch(function (e) {
            console.warn('[FCM] register error:', e);
        });
    }

    if (window.__fcmToken) {
        registerToken(window.__fcmToken);
    } else {
        window.__onFcmTokenReady = registerToken;
    }
})();
