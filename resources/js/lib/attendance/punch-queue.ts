const DB_NAME = 'jindo-plt-hrm-attendance';
const DB_VERSION = 1;
const STORE = 'pending_punches';

export type PendingPunchMode = 'check_in' | 'check_out';

export type PendingPunchStatus = 'pending' | 'syncing' | 'failed';

export type PendingPunch = {
    id: string;
    mode: PendingPunchMode;
    idempotencyKey: string;
    latitude: number;
    longitude: number;
    accuracy_meters: number | null;
    address: string;
    captured_at: string;
    photoBlob: Blob;
    photoName: string;
    photoType: string;
    createdAt: string;
    status: PendingPunchStatus;
    lastError?: string;
};

export type EnqueuePunchInput = {
    mode: PendingPunchMode;
    idempotencyKey: string;
    latitude: number;
    longitude: number;
    accuracy_meters: number | null;
    address: string;
    captured_at: string;
    photo: File;
};

function openDb(): Promise<IDBDatabase> {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;

            if (!db.objectStoreNames.contains(STORE)) {
                const store = db.createObjectStore(STORE, { keyPath: 'id' });
                store.createIndex('mode', 'mode', { unique: false });
                store.createIndex('idempotencyKey', 'idempotencyKey', {
                    unique: true,
                });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () =>
            reject(request.error ?? new Error('Failed to open punch queue.'));
    });
}

function reqToPromise<T>(request: IDBRequest<T>): Promise<T> {
    return new Promise((resolve, reject) => {
        request.onsuccess = () => resolve(request.result);
        request.onerror = () =>
            reject(request.error ?? new Error('IndexedDB request failed.'));
    });
}

export async function listPending(): Promise<PendingPunch[]> {
    const db = await openDb();

    try {
        const tx = db.transaction(STORE, 'readonly');
        const store = tx.objectStore(STORE);
        const rows = await reqToPromise(store.getAll());

        return (rows as PendingPunch[]).sort((a, b) =>
            a.createdAt.localeCompare(b.createdAt),
        );
    } finally {
        db.close();
    }
}

export async function enqueue(input: EnqueuePunchInput): Promise<PendingPunch> {
    const existing = await listPending();
    const sameMode = existing.find((row) => row.mode === input.mode);

    if (sameMode) {
        if (sameMode.idempotencyKey === input.idempotencyKey) {
            return sameMode;
        }

        throw new Error('A pending punch of this type is already queued.');
    }

    if (existing.length >= 2) {
        throw new Error('Punch queue is full.');
    }

    const row: PendingPunch = {
        id: crypto.randomUUID(),
        mode: input.mode,
        idempotencyKey: input.idempotencyKey,
        latitude: input.latitude,
        longitude: input.longitude,
        accuracy_meters: input.accuracy_meters,
        address: input.address,
        captured_at: input.captured_at,
        photoBlob: input.photo,
        photoName: input.photo.name || 'punch.jpg',
        photoType: input.photo.type || 'image/jpeg',
        createdAt: new Date().toISOString(),
        status: 'pending',
    };

    const db = await openDb();

    try {
        const tx = db.transaction(STORE, 'readwrite');
        await reqToPromise(tx.objectStore(STORE).put(row));
    } finally {
        db.close();
    }

    return row;
}

export async function remove(id: string): Promise<void> {
    const db = await openDb();

    try {
        const tx = db.transaction(STORE, 'readwrite');
        await reqToPromise(tx.objectStore(STORE).delete(id));
    } finally {
        db.close();
    }
}

export async function updatePending(
    id: string,
    patch: Partial<Pick<PendingPunch, 'status' | 'lastError'>>,
): Promise<void> {
    const db = await openDb();

    try {
        const tx = db.transaction(STORE, 'readwrite');
        const store = tx.objectStore(STORE);
        const current = (await reqToPromise(store.get(id))) as
            PendingPunch | undefined;

        if (!current) {
            return;
        }

        await reqToPromise(
            store.put({
                ...current,
                ...patch,
            }),
        );
    } finally {
        db.close();
    }
}

export function toPunchFile(row: PendingPunch): File {
    return new File([row.photoBlob], row.photoName, {
        type: row.photoType,
        lastModified: Date.parse(row.createdAt) || Date.now(),
    });
}

export type DrainResult = {
    synced: number;
    remaining: number;
    lastError?: string;
};

/**
 * Attempt to sync pending punches with exponential backoff (1s → 2s → 5s), max 3 attempts.
 */
export async function drain(
    syncOne: (row: PendingPunch) => Promise<void>,
): Promise<DrainResult> {
    const backoffsMs = [1000, 2000, 5000];
    let synced = 0;
    let lastError: string | undefined;

    for (let attempt = 0; attempt < 3; attempt++) {
        if (attempt > 0) {
            await new Promise((r) => setTimeout(r, backoffsMs[attempt - 1]));
        }

        const pending = await listPending();

        if (pending.length === 0) {
            break;
        }

        for (const row of pending) {
            await updatePending(row.id, {
                status: 'syncing',
                lastError: undefined,
            });

            try {
                await syncOne(row);
                await remove(row.id);
                synced += 1;
            } catch (err) {
                const message =
                    err instanceof Error ? err.message : 'Sync failed';
                lastError = message;
                await updatePending(row.id, {
                    status: 'failed',
                    lastError: message,
                });
            }
        }

        if ((await listPending()).length === 0) {
            break;
        }
    }

    const remaining = (await listPending()).length;

    return { synced, remaining, lastError };
}
