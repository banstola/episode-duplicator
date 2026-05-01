# Guide 

_note_: This guide is applicable for macOS local environment.

The following preconditions must be met before proceeding further.
1. Linux Environment needed for running containerized applications eg. Rancher Desktop or Docker Desktop
2. Verify using `docker version` and `docker-compose` that the environment is running Docker Desktop or Rancher Desktop
3. Ensure port 8000 is free


# How to run the application locally
1. Clone the repository
2. Prepare .env file with required environment variables.
   1. Use `postgres` for `DATABASE_HOST` - Check [Docker Compose Local](docker-compose.yml) 
   2. User `redis` for `REDIS_HOST` - Check [Docker Compose Local](docker-compose.yml)
3. Run `docker-compose up`
4. Verify the application is using `curl http://localhost:8000/health`

```
GET /health
{"status":"healthy","app_version":"dev"}
```

# Seeding the database
1. `docker exec episode-duplicator php artisan db:seed --class=EpisodeSeeder`
2. For the sake of simplicity, in this project - the rows limits are set at [EpisodeSeeder](database/seeders/EpisodeSeeder.php) using const



