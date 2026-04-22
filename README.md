# Flight Manifest API

Laravel 13 API project for managing flights, passengers, and reservations.

## Tech Stack

- PHP 8.3
- Laravel 13
- PostgreSQL
- PHPUnit 12

## Local Setup

1. Install dependencies:

```bash
composer install
npm install
```

2. Create the environment file if needed:

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure database credentials in `.env`.

Current local defaults are set for PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=flight_manifest_api
DB_USERNAME=sail
DB_PASSWORD=password
```

If you are not using Sail, replace those values with your local PostgreSQL connection details.

## Database Commands

Run migrations:

```bash
php artisan migrate
```

Run seeders:

```bash
php artisan db:seed
```

Run both from a clean state:

```bash
php artisan migrate:fresh --seed
```

## Health Check

Basic API health endpoint:

```text
GET /api/health
```

Example response:

```json
{
  "status": "ok"
}
```

## Domain Model

### Flight

Fields:
- `id`
- `flight_number`
- `origin`
- `destination`
- `departure_at`
- `departed_at`
- `status`

Relationships:
- has many `Reservation`

Statuses:
- `scheduled`
- `boarding`
- `departed`
- `delayed`
- `cancelled`

### Passenger

Fields:
- `id`
- `first_name`
- `last_name`
- `email`
- `birthday`
- `document_number`

Relationships:
- has many `Reservation`

### Reservation

Fields:
- `id`
- `flight_id`
- `passenger_id`
- `seat_number`
- `status`
- `checked_in_at`
- `boarding_pass_code`

Relationships:
- belongs to `Flight`
- belongs to `Passenger`

Statuses:
- `booked`
- `checked_in`
- `cancelled`

## Seed Data

The project includes:
- factories for `Flight`, `Passenger`, and `Reservation`
- seeders for sample flights and passengers
- a reservation seeder that links sample passengers to a sample flight

Seeded data is intended for local development and manual API checks.
