import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

/**
 * Prepares a file-backed SQLite database and seeds E2eSeeder before Playwright starts.
 */
async function globalSetup(): Promise<void> {
    const root = process.cwd();
    const dbPath = path.join(root, 'database', 'e2e.sqlite');

    if (fs.existsSync(dbPath)) {
        fs.unlinkSync(dbPath);
    }

    fs.writeFileSync(dbPath, '');

    const env = {
        ...process.env,
        APP_ENV: 'local',
        APP_URL: 'http://127.0.0.1:8000',
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: dbPath,
        DB_URL: '',
        SESSION_DRIVER: 'file',
        CACHE_STORE: 'file',
        QUEUE_CONNECTION: 'sync',
        MAIL_MAILER: 'array',
        BROADCAST_CONNECTION: 'null',
        SANCTUM_STATEFUL_DOMAINS:
            '127.0.0.1,127.0.0.1:8000,localhost,localhost:8000',
    };

    execSync('php artisan config:clear', { stdio: 'inherit', env, cwd: root });
    execSync('php artisan migrate:fresh --force --seeder=E2eSeeder', {
        stdio: 'inherit',
        env,
        cwd: root,
    });
}

export default globalSetup;
