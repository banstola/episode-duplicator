# Guide 

The following preconditions must be met before proceeding further.
1. Linux Environment needed for running containerized applications eg. Rancher Desktop or Docker Desktop
2. Verify using `docker version` and `docker-compose` that the environment is running Docker Desktop or Rancher Desktop
3. Ensure port 8000 is free


# How to run the application locally
1. Clone the repository
2. Prepare .env file with required environment variables.
   1. Use `postgres` for `DATABASE_HOST` - Check [Docker Compose Local](docker-compose.yml) 
   2. User `redis` for `REDIS_HOST` - Check [Docker Compose Local](docker-compose.yml)
3. Run `docker-compose up -d`
4. Verify the application is running `curl http://localhost:8000/api/health`

```
GET /api/health
{"status":"healthy","app_version":"dev"}
```

# Seeding the database
1. `docker exec episode-duplicator php artisan db:seed --class=EpisodeSeeder`
2. For the sake of simplicity, in this project - the rows limits are set at [EpisodeSeeder](database/seeders/EpisodeSeeder.php) using const

# Testing the application
1. Import the [OpenAPI Spec](api_spec/api_endpoint_collection.yaml) to your favorite API client e.g. Bruno, Postman
2. Check the available episodes for duplication using 
```
docker exec -it postgres-db psql -U db_user -d episode_duplicator -c "SELECT episode_uuid FROM episodes;"
```
3. Pick a uuid from the list and request `POST /api/episode/duplicate/{episode_uuid}` 
4. Make sure to use same `X-API-KEY` header as defined in `.env` file
5. Monitor the progress of the duplication using `GET /api/episode/duplicate/{episode_uuid}`
6. Laravel Horizon Dashboard can be accessed at `http://localhost:8000/horizon`

