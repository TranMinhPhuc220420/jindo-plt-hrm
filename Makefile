# Makefile

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
