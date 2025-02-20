# todo-backend-php

## Environment Setup

This application supports two environment configurations:

### 1. Development Environment (Local)

1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
2. Modify the `.env` file with your local database credentials
3. Run the application using your local PHP and MySQL setup

### 2. Docker Environment

When running with Docker, environment variables are managed through `docker-compose.yml`. No `.env` file is needed as the environment variables are passed directly to the containers.

```bash
# Start the application with Docker
docker compose up --build
```

The application will be available at `http://localhost:8081`

## Environment Variables

The following environment variables are required:

- `DB_HOST`: Database host (use 'db' for Docker, 'localhost' for local development)
- `DB_NAME`: Database name
- `DB_USER`: Database user
- `DB_PASSWORD`: Database password
