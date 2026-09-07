# ==========================================
# RH Tech IA - Docker Environment
# ==========================================

# Usage:
# 1. cp .env.docker backend/.env
# 2. cd docker && docker-compose up -d
# 3. docker exec -it rhtechia-backend php artisan key:generate
# 4. docker exec -it rhtechia-backend php artisan migrate

# Services:
# - Backend: http://localhost:8000
# - Frontend: http://localhost:3000
# - Nginx: http://localhost:80
# - MinIO Console: http://localhost:9001 (minioadmin/minioadmin)
# - PostgreSQL: localhost:5432 (postgres/postgres123)
# - Redis: localhost:6379

# Commands:
# docker-compose up -d          # Start all services
# docker-compose down           # Stop all services
# docker-compose logs -f        # View logs
# docker-compose exec backend sh # Shell into backend
