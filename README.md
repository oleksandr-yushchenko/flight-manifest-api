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

## Running Tests

Run the full test suite:

```bash
php artisan test --compact
```

Run specific feature tests:

```bash
php artisan test --compact tests/Feature/FlightsApiTest.php
php artisan test --compact tests/Feature/PassengersApiTest.php
```

The test suite is configured to use SQLite in memory.

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

## API Endpoints

### Health

| Method | Endpoint      | Description            |
|--------|---------------|------------------------|
| `GET`  | `/api/health` | Basic API health check |

Response example:

```json
{
  "status": "ok"
}
```

### Flights

| Method | Endpoint            | Description         |
|--------|---------------------|---------------------|
| `POST` | `/api/flights`      | Create a flight     |
| `GET`  | `/api/flights`      | List flights        |
| `GET`  | `/api/flights/{id}` | Get a single flight |

Flight creation rules:
- `flight_number` is required
- `origin` is required and must be a 3-letter code
- `destination` is required and must be a 3-letter code
- `departure_at` is required, must be a valid datetime, and must be in the future
- `status` is required and must be one of the allowed flight statuses

Request example for `POST /api/flights`:

```json
{
  "flight_number": "PS321",
  "origin": "KBP",
  "destination": "AMS",
  "departure_at": "2026-05-10 10:30:00",
  "status": "scheduled"
}
```

Response example for `POST /api/flights`:

```json
{
  "data": {
    "id": 1,
    "flight_number": "PS321",
    "origin": "KBP",
    "destination": "AMS",
    "departure_at": "2026-05-10T10:30:00+00:00",
    "departed_at": null,
    "status": "scheduled",
    "created_at": "2026-04-22T12:00:00+00:00",
    "updated_at": "2026-04-22T12:00:00+00:00"
  }
}
```

Response example for `GET /api/flights`:

```json
{
  "data": [
    {
      "id": 2,
      "flight_number": "LH202",
      "origin": "BER",
      "destination": "FRA",
      "departure_at": "2026-04-24T14:40:00+00:00",
      "departed_at": null,
      "status": "boarding",
      "created_at": "2026-04-22T12:00:00+00:00",
      "updated_at": "2026-04-22T12:00:00+00:00"
    },
    {
      "id": 1,
      "flight_number": "PS101",
      "origin": "KBP",
      "destination": "WAW",
      "departure_at": "2026-04-23T09:15:00+00:00",
      "departed_at": null,
      "status": "scheduled",
      "created_at": "2026-04-22T12:00:00+00:00",
      "updated_at": "2026-04-22T12:00:00+00:00"
    }
  ]
}
```

Response example for `GET /api/flights/{id}`:

```json
{
  "data": {
    "id": 1,
    "flight_number": "PS101",
    "origin": "KBP",
    "destination": "WAW",
    "departure_at": "2026-04-23T09:15:00+00:00",
    "departed_at": null,
    "status": "scheduled",
    "created_at": "2026-04-22T12:00:00+00:00",
    "updated_at": "2026-04-22T12:00:00+00:00"
  }
}
```

### Passengers

| Method | Endpoint               | Description            |
|--------|------------------------|------------------------|
| `POST` | `/api/passengers`      | Create a passenger     |
| `GET`  | `/api/passengers/{id}` | Get a single passenger |

Passenger creation rules:
- `first_name` is required
- `last_name` is required
- `email` is required and must be valid
- `birthday` is required and must be a valid date
- `document_number` is required and must be unique

Request example for `POST /api/passengers`:

```json
{
  "first_name": "Iryna",
  "last_name": "Melnyk",
  "email": "iryna.melnyk@example.test",
  "birthday": "1995-08-14",
  "document_number": "AB123456"
}
```

Response example for `POST /api/passengers`:

```json
{
  "data": {
    "id": 1,
    "first_name": "Iryna",
    "last_name": "Melnyk",
    "email": "iryna.melnyk@example.test",
    "birthday": "1995-08-14",
    "document_number": "AB123456",
    "created_at": "2026-04-22T12:00:00+00:00",
    "updated_at": "2026-04-22T12:00:00+00:00"
  }
}
```

### Reservations

| Method | Endpoint                         | Description                       |
|--------|----------------------------------|-----------------------------------|
| `POST` | `/api/flights/{id}/reservations` | Create a reservation for a flight |
| `GET`  | `/api/reservations/{id}`         | Get a single reservation          |
| `GET`  | `/api/flights/{id}/manifest`     | Get flight manifest               |

Reservation creation rules:
- `passenger_id` is required and must reference an existing passenger
- `seat_number` is required and must match rows `1-36` with seat letters `A-F`
- an active reservation cannot reuse the same seat on the same flight
- an active reservation cannot book the same passenger twice on the same flight
- cancelled reservations do not block rebooking the same seat
- cancelled reservations do not block rebooking the same passenger on the same flight
- reservations are rejected for `cancelled` and `departed` flights
- new reservations default to `booked`

Request example for `POST /api/flights/{id}/reservations`:

```json
{
  "passenger_id": 5,
  "seat_number": "12C"
}
```

Response example for `POST /api/flights/{id}/reservations`:

```json
{
  "data": {
    "id": 9,
    "seat_number": "12C",
    "status": "booked",
    "checked_in_at": null,
    "boarding_pass_code": null,
    "flight": {
      "id": 1,
      "flight_number": "PS555",
      "status": "scheduled",
      "departure_at": "2026-05-10T10:30:00+00:00"
    },
    "passenger": {
      "id": 5,
      "first_name": "Iryna",
      "last_name": "Melnyk",
      "email": "iryna.melnyk@example.test",
      "document_number": "AB123456"
    },
    "created_at": "2026-04-22T12:00:00+00:00",
    "updated_at": "2026-04-22T12:00:00+00:00"
  }
}
```

Response example for `GET /api/reservations/{id}`:

```json
{
  "data": {
    "id": 9,
    "seat_number": "12C",
    "status": "booked",
    "checked_in_at": null,
    "boarding_pass_code": null,
    "flight": {
      "id": 1,
      "flight_number": "PS555",
      "status": "scheduled",
      "departure_at": "2026-05-10T10:30:00+00:00"
    },
    "passenger": {
      "id": 5,
      "first_name": "Iryna",
      "last_name": "Melnyk",
      "email": "iryna.melnyk@example.test",
      "document_number": "AB123456"
    },
    "created_at": "2026-04-22T12:00:00+00:00",
    "updated_at": "2026-04-22T12:00:00+00:00"
  }
}
```

Response example for `GET /api/flights/{id}/manifest`:

```json
{
  "data": {
    "flight": {
      "id": 1,
      "flight_number": "PS555",
      "origin": "KBP",
      "destination": "AMS",
      "departure_at": "2026-05-10T10:30:00+00:00",
      "departed_at": null,
      "status": "scheduled"
    },
    "reservations": [
      {
        "id": 9,
        "seat_number": "12A",
        "status": "booked",
        "passenger": {
          "id": 3,
          "first_name": "Anna",
          "last_name": "Shevchenko",
          "email": "anna.shevchenko@example.test",
          "document_number": "ER123456"
        }
      },
      {
        "id": 10,
        "seat_number": "14B",
        "status": "booked",
        "passenger": {
          "id": 4,
          "first_name": "Bohdan",
          "last_name": "Koval",
          "email": "bohdan.koval@example.test",
          "document_number": "FF654321"
        }
      }
    ]
  }
}
```

Response example for `GET /api/passengers/{id}`:

```json
{
  "data": {
    "id": 1,
    "first_name": "Iryna",
    "last_name": "Melnyk",
    "email": "iryna.melnyk@example.test",
    "birthday": "1995-08-14",
    "document_number": "AB123456",
    "created_at": "2026-04-22T12:00:00+00:00",
    "updated_at": "2026-04-22T12:00:00+00:00"
  }
}
```
