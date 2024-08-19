# Goshne Prro

Goshne Prro is a project to find discounts on Snapp Food and Snap Market and more in the future.
It can find discounts based on the user's location and the user's favorite vendors/products and discount percentage.

## Installation

### Development Environment (Laravel Sail)

You can read the [Laravel Sail documentation](https://laravel.com/docs/11.x/sail) for more information.

```bash
# clone the repository
git clone https://github.com/ahbanavi/goshneprro.git
cd goshneprro

# write your .env file (don't forget to put app key)
cp .env.example .env

# install the dependencies
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs

# build and run the containers
./vendor/bin/sail up -d

# run the migrations
./vendor/bin/sail artisan migrate

# Create admin user
./vendor/bin/sail artisan make:admin-user

# run schedule in the background 
./vendor/bin/sail artisan schedule:work -n &

# run queue worker in the background
./vendor/bin/sail artisan queue:work -v -n &
```

### Production Environment (Docker)

```bash
# create a directory for the app to hold .env and docker-compose.yml
mkdir goshneprro-docker
cd goshneprro-docker

# download .env file and write your configurations
curl -o .env https://raw.githubusercontent.com/ahbanavi/goshneprro/main/.env.example

# download docker-compose.yml file
curl -o docker-compose.yml https://raw.githubusercontent.com/ahbanavi/goshneprro/main/docker-compose-production.yml

# run the containers
docker compose up -d

# run the migrations
docker compose exec app php artisan migrate

# Create admin user
docker compose exec app php artisan make:admin-user
```

## Configuration

Besides the default Laravel environment variables, you can set the following variables specifically for Goshne Prro:

| Var                       | Description                                                    | Required | Default                     |
|---------------------------|----------------------------------------------------------------|----------|-----------------------------|
| TELEGRAM_BOT_TOKEN        | Telegram bot token                                             | **Yes**  | -                           |
| TELEGRAM_BOT_BASE_URI     | Base URI (Bridge) for the Telegram Bot API                     | No       | https://api.telegram.org    |
| MARKET_PARTY_PRODUCTS_TTL | Time-to-live (TTL) for market party products caches in seconds | No       | 900                         |
| MARKET_PARTY_NOTIFY_TTL   | TTL for market party notifications cache in seconds            | No       | 43200                       |
| FOOD_PARTY_NOTIFY_TTL     | TTL for food party notifications cache in seconds              | No       | 43200                       |
| FOOD_PARTY_SCHEDULE       | Cron schedule for food party runs                              | No       | "*/15 * * * *"              |
| MARKET_PARTY_SCHEDULE     | Cron schedule for market party runs                            | No       | "*/15 * * * *"              |
| DEFAULT_LATITUDE          | Default latitude for UI map                                    | No       | 36.32112700482277 (Mashhad) |
| DEFAULT_LONGITUDE         | Default longitude for UI map                                   | No       | 59.53740119934083 (Mashhad) |

## License
This project is licensed under the [Creative Commons Attribution-NonCommercial 4.0 International License](./LICENSE.md). You are free to share, copy and adapt the material in any medium or format for non-commercial purposes with proper attribution, providing a link to the license, and indicating if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
