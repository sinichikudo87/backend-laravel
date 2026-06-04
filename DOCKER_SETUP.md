# Docker + Colima Setup Guide

This guide helps you set up the Wisata Jatim backend using Docker with Colima.

## Prerequisites

### 1. Install Colima (Docker on macOS)

Colima is a lightweight Docker container runtime for macOS that works with the Docker CLI.

```bash
# Install Colima via Homebrew
brew install colima

# Install Docker CLI
brew install docker

# Install Docker Compose
brew install docker-compose
```

### 2. Start Colima

```bash
# Start Colima with default settings
colima start

# Or start with custom resource allocation:
colima start --cpu 4 --memory 8 --disk 50

# Verify it's running
docker ps
```

## Setup Steps

### Step 1: Clone the Repository

```bash
cd /Users/savano/code/wisata-jatim/backend-laravel
```

### Step 2: Configure Environment

The `.env` file is already configured for Docker. Review the database credentials:

```bash
# Database credentials in .env (for Docker)
DB_HOST=mysql           # Service name in docker-compose
DB_PORT=3306
DB_DATABASE=wisata_jatim
DB_USERNAME=root
DB_PASSWORD=secret
```

For local development (without Docker), use `.env.local`:

```bash
DB_HOST=127.0.0.1
DB_PORT=3306
```

### Step 3: Install PHP Dependencies

```bash
# Install Composer dependencies
composer install

# Generate application key
php artisan key:generate
```

### Step 4: Start Docker Containers

```bash
# Start all services
docker-compose up -d

# Or with output logging
docker-compose up
```

Services that will start:

- **app** (Laravel): `http://localhost:8000`
- **mysql** (Primary DB): `localhost:3306`
- **mysql-crm** (Secondary DB): `localhost:3307`
- **phpmyadmin** (DB Manager): `http://localhost:8080`

### Step 5: Initialize Database

```bash
# Run migrations
docker-compose exec app php artisan migrate

# Or seed with data (if seeders exist)
docker-compose exec app php artisan db:seed

# Clear cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
```

### Step 6: Install Node Dependencies (for Vite)

```bash
# Install npm packages
npm install

# Run Vite dev server (if needed)
npm run dev
```

## Useful Commands

### Check Container Status

```bash
# List running containers
docker-compose ps

# View container logs
docker-compose logs -f app
docker-compose logs -f mysql

# View specific service logs
docker-compose logs app
```

### Database Management

```bash
# Access MySQL CLI
docker-compose exec mysql mysql -u root -psecret wisata_jatim

# Run artisan commands
docker-compose exec app php artisan tinker
docker-compose exec app php artisan optimize
docker-compose exec app php artisan cache:clear

# Run migrations
docker-compose exec app php artisan migrate
docker-compose exec app php artisan migrate:rollback
```

### PHPMyAdmin Access

- URL: `http://localhost:8080`
- Username: `root`
- Password: `secret`
- Server: `mysql`

### Stop Containers

```bash
# Stop all services
docker-compose stop

# Stop and remove containers
docker-compose down

# Stop and remove all data
docker-compose down -v
```

### Rebuild Containers

```bash
# Rebuild images
docker-compose build

# Rebuild and start
docker-compose up --build
```

## Local Development Without Docker

If you prefer to run locally without Docker:

### Prerequisites

- PHP 8.3+
- MySQL 8.0+
- Composer
- Node.js

### Setup

```bash
# Update .env for local development
cp .env.example .env

# Set local database host
sed -i '' 's/DB_HOST=mysql/DB_HOST=127.0.0.1/' .env
sed -i '' 's/DB_HOST_SECOND=mysql-crm/DB_HOST_SECOND=127.0.0.1/' .env

# Install dependencies
composer install
npm install

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Start dev server
php artisan serve
```

## Troubleshooting

### Colima not starting

```bash
# Check Colima status
colima status

# Restart Colima
colima stop
colima start

# Check Docker connectivity
docker ps
```

### Database connection refused

```bash
# Ensure containers are running
docker-compose ps

# Restart database service
docker-compose restart mysql
docker-compose restart mysql-crm

# Check MySQL logs
docker-compose logs mysql
```

### Port already in use

```bash
# Find process using the port (e.g., 3306)
lsof -i :3306

# Change port in docker-compose.yml or kill the process
kill <PID>
```

### Permission denied when accessing files

```bash
# Fix file permissions
sudo chown -R $(whoami):staff /Users/savano/code/wisata-jatim/backend-laravel

# Or in container
docker-compose exec app chown -R www-data:www-data /var/www
```

### Application not accessible at localhost:8000

```bash
# Check app container logs
docker-compose logs app

# Verify port binding
docker-compose ps

# Test connection
curl http://localhost:8000
```

## Performance Tips

1. **Enable Docker VirtioFS** (faster file sync):

    ```bash
    colima start --mount-type=virtiofs
    ```

2. **Adjust resource allocation** for large projects:

    ```bash
    colima start --cpu 8 --memory 16 --disk 100
    ```

3. **Use volume mounts efficiently**:
    - Cache PHP/Node modules in volumes
    - Exclude `node_modules` and `vendor` from frequent syncs

## Next Steps

1. Access the application: `http://localhost:8000`
2. Check migrations: `docker-compose exec app php artisan migrate:status`
3. Create test data: `docker-compose exec app php artisan db:seed`
4. Monitor logs: `docker-compose logs -f`

## Support

For issues with specific services or further help, refer to:

- [Laravel Documentation](https://laravel.com/docs)
- [Docker Documentation](https://docs.docker.com)
- [Colima GitHub](https://github.com/abiosoft/colima)
