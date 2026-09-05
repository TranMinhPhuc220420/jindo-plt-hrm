self.addEventListener('push', (event) => {
    let data = { title: 'HRM', body: '', url: '/attendance' };

    try {
        if (event.data) {
            data = { ...data, ...event.data.json() };
        }
    } catch {
        // Keep defaults when payload is not JSON.
    }

    event.waitUntil(
        self.registration.showNotification(data.title || 'HRM', {
            body: data.body || '',
            data: { url: data.url || '/attendance' },
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/attendance';

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                for (const client of clientList) {
                    if ('focus' in client && client.url.includes(url)) {
                        return client.focus();
                    }
                }

                if (self.clients.openWindow) {
                    return self.clients.openWindow(url);
                }

                return undefined;
            }),
    );
});
