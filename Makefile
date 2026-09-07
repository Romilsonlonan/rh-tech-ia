.PHONY: help install up down logs shell migrate seed test lint

# ===========================================
# RH Tech IA - Make Commands
# ===========================================

help:
	@grep -E '^[a-zA-Z_-]+:' Makefile | sed -n 's/^\(.*\):.*#\(.*\)/\1 \2/p' | column -t -s ':'

# Docker Commands
up: # Start all services
	docker-compose up -d

down: # Stop all services
	docker-compose down

logs: # View logs
	docker-compose logs -f

logs-backend: # View backend logs
	docker-compose logs -f backend

build: # Rebuild containers
	docker-compose build --no-cache

shell-backend: # Shell into backend
	docker-compose exec backend sh

shell-postgres: # Shell into PostgreSQL
	docker-compose exec postgres psql -U postgres -d rhtechia

# Laravel Commands
migrate: # Run migrations
	docker-compose exec backend php artisan migrate

migrate-fresh: # Fresh migrations with seed
	docker-compose exec backend php artisan migrate:fresh --seed

seed: # Seed database
	docker-compose exec backend php artisan db:seed

key-generate: # Generate app key
	docker-compose exec backend php artisan key:generate

cache-clear: # Clear all caches
	docker-compose exec backend php artisan config:clear
	docker-compose exec backend php artisan cache:clear
	docker-compose exec backend php artisan view:clear

# Development
test: # Run tests
	docker-compose exec backend php artisan test

test-coverage: # Run tests with coverage
	docker-compose exec backend php artisan test --coverage

lint: # Run linter
	docker-compose exec backend ./vendor/bin/pint

# Frontend Commands
npm-install: # Install frontend dependencies
	docker-compose exec frontend npm install

npm-dev: # Run frontend dev server
	docker-compose exec frontend npm run dev

npm-build: # Build frontend
	docker-compose exec frontend npm run build

npm-lint: # Lint frontend
	docker-compose exec frontend npm run lint

# Database
db-backup: # Backup database
	docker-compose exec postgres pg_dump -U postgres rhtechia > backup_$$(date +%Y%m%d_%H%M%S).sql

db-restore: # Restore database ( Usage: make db-restore FILE=backup.sql )
	docker exec -i $$(docker-compose exec -T postgres psql -U postgres -d rhtechia -c "COPY users FROM STDIN" < $(FILE))

# MinIO
mc-alias: # Configure MinIO alias
	docker-compose exec minio-init sh -c "mc alias set myminio http://minio:9000 minioadmin minioadmin"

mc-ls: # List MinIO buckets
	docker-compose exec minio-init sh -c "mc ls myminio"

# Health Check
health: # Check all services health
	@echo "Backend:" && curl -s http://localhost:8000/health || echo "FAIL"
	@echo "Frontend:" && curl -s http://localhost:3000 | grep -q "root" && echo "OK" || echo "FAIL"
	@echo "Nginx:" && curl -s http://localhost/health || echo "FAIL"
	@echo "MinIO:" && curl -s http://localhost:9000/minio/health/live || echo "FAIL"

# Cleanup
clean: # Remove all containers, volumes, and images
	docker-compose down -v --remove-orphans
	docker system prune -f

# CI/CD
ci: # Run CI locally (tests, lint)
	docker-compose -f docker-compose.yml -f docker-compose.ci.yml up --abort-on-container-exit

# Production
deploy: # Deploy to production
	./scripts/deploy.sh
