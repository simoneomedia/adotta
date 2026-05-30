self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json().catch(() => ({})) : Promise.resolve({});
    event.waitUntil(
        data.then((payload) =>
            self.registration.showNotification(payload.title || 'Adotta', {
                body: payload.body || 'Nuovo aggiornamento dal campo!',
                icon: payload.icon || '/wp-content/themes/adotta/assets/img/icon-192.png',
                badge: '/wp-content/themes/adotta/assets/img/icon-72.png',
                data: { url: payload.url || '/' },
            })
        )
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            return clients.openWindow(url);
        })
    );
});
