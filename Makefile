# Makefile

.PHONY: start build clear test migrate seed seed-local seed-admin migrate_and_seed deploy run_xampp format pre-commit ci-check ensure-js-deps ensure-php-deps release release-check release-artifact

# Start the development server
start:
	cp .env.example .env
	composer run dev

# Build the production assets
build:
	# Copy .env.production to .env
	cp .env.production .env
	# Clear the cache
	make clear

	# Run the tests
	php artisan test

	# Run the build
	npm run build

clear:
	php artisan cache:clear
	php artisan route:clear
	php artisan view:clear
	php artisan config:clear
	php artisan optimize:clear

# Run the tests
test:
	php artisan test

# Migrate the database
migrate:
	php artisan migrate

# Seed — default is local full demo
seed: seed-local

# Local / non-production: full demo (DatabaseSeeder)
seed-local:
	php artisan db:seed

seed-admin:
	php artisan db:seed --class=ProductionBootstrapSeeder --force

migrate_and_seed:
	make clear
	php artisan migrate:refresh --seed

# Deploy the application
deploy:
	php artisan deploy

run_xampp:
	sudo /opt/lampp/lampp start

# ---------------------------------------------------------------------------
# Pre-commit / CI helpers
# ---------------------------------------------------------------------------

# Install PHP deps when vendor is incomplete (e.g. missing minishlink/web-push
# after a partial install — that breaks PHPStan class discovery).
ensure-php-deps:
	@test -f vendor/autoload.php -a -d vendor/minishlink/web-push \
		|| composer install --prefer-dist --no-interaction
	@composer dump-autoload -o --quiet

# Install JS deps when Prettier/ESLint are missing (`npm run format` looks for
# binaries on PATH; without node_modules that fails with "prettier: not found").
# Wayfinder route types are gitignored and required by `tsc`.
ensure-js-deps:
	@test -x node_modules/.bin/prettier -a -x node_modules/.bin/eslint || npm install
	php artisan wayfinder:generate --with-form --no-interaction

# Auto-fix Prettier, ESLint, and Pint (fixes the usual format:check CI failure)
format: ensure-js-deps
	npm run format
	npm run lint
	composer lint

# Fix style, then run the same checks GitHub Actions runs via `composer ci:check`
# Usage: make pre-commit
pre-commit: ensure-php-deps format
	composer ci:check

# Run CI checks only (no auto-fix) — same as GitHub Actions
ci-check: ensure-php-deps ensure-js-deps
	composer ci:check

# ---------------------------------------------------------------------------
# Release workflow (see docs/08-development/RELEASE_PROCESS.md)
# ---------------------------------------------------------------------------

# Quality gates before shipping: lint/types/Pest + frontend unit tests.
# Does not mutate .env. Usage: make release-check
release-check: ensure-php-deps ensure-js-deps
	composer ci:check
	npm run test:unit
	php artisan test

# Production Vite build + deploy.zip (mirrors CI artifact; does not upload FTPS).
# Usage: make release-artifact
release-artifact: ensure-php-deps ensure-js-deps
	npm run build
	@mkdir -p deploy-bundle
	@rm -f deploy-bundle/deploy.zip
	@zip -r deploy-bundle/deploy.zip . \
		-x "*.git*" \
		-x "*node_modules*" \
		-x "tests/*" \
		-x "e2e/*" \
		-x "deploy-bundle/*" \
		-x ".env" \
		-x ".env.*" \
		-x "storage/logs/*" \
		-x "test-results/*" \
		-x "playwright-report/*" \
		-x "coverage/*" \
		-x "*.sqlite"
	@echo "Created deploy-bundle/deploy.zip"

# Full release prep: gates → artifact → optional annotated git tag.
# Usage:
#   make release
#   make release VERSION=v0.7.0
release: release-check release-artifact
ifdef VERSION
	@git rev-parse --verify HEAD >/dev/null
	@git tag -a "$(VERSION)" -m "Release $(VERSION)"
	@echo "Tagged $(VERSION). Push with: git push origin $(VERSION) && git push origin HEAD"
else
	@echo "Release artifact ready at deploy-bundle/deploy.zip"
	@echo "Tag when ready: make release VERSION=vX.Y.Z"
endif
