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

## Queue Processing

Application jobs use Redis as the queue backend.

Default environment value:

```env
QUEUE_CONNECTION=redis
```

The boarding pass generation job is queued on Redis with a 30-second delay after a successful check-in.

Run a local worker:

```bash
php artisan queue:work redis --queue=boarding_pass --tries=3
```

If you use Sail with the dedicated worker service:

```bash
./vendor/bin/sail up -d
docker compose logs -f queue.worker
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

Validation / business error example:

```json
{
  "message": "Unable to sync flight status from external airline service.",
  "errors": {
    "external_service": [
      "Unable to sync flight status from external airline service."
    ]
  }
}
```

### Flights

| Method | Endpoint                        | Description                                      |
|--------|---------------------------------|--------------------------------------------------|
| `POST` | `/api/flights`                  | Create a flight                                  |
| `GET`  | `/api/flights`                  | List flights                                     |
| `GET`  | `/api/flights/{id}`             | Get a single flight                              |
| `POST` | `/api/flights/{id}/sync-status` | Sync flight status from external airline service |

Flight creation rules:
- `flight_number` is required
- `origin` is required and must be a 3-letter code
- `destination` is required and must be a 3-letter code
- `departure_at` is required, must be a valid datetime, and must be in the future
- `status` is required and must be one of the allowed flight statuses
- `gate` is nullable and may be populated later by external status sync

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

`curl` example for `POST /api/flights`:

```bash
curl -X POST "http://localhost/api/flights" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "flight_number": "PS321",
    "origin": "KBP",
    "destination": "AMS",
    "departure_at": "2026-05-10 10:30:00",
    "status": "scheduled"
  }'
```

Response example for `POST /api/flights`:

```json
{
  "data": {
    "id": 1,
    "flight_number": "PS321",
    "origin": "KBP",
    "destination": "AMS",
    "gate": null,
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
      "gate": "B07",
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
      "gate": null,
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
    "gate": null,
    "departure_at": "2026-04-23T09:15:00+00:00",
    "departed_at": null,
    "status": "scheduled",
    "created_at": "2026-04-22T12:00:00+00:00",
    "updated_at": "2026-04-22T12:00:00+00:00"
  }
}
```

Response example for `POST /api/flights/{id}/sync-status`:

```json
{
  "data": {
    "id": 1,
    "flight_number": "PS101",
    "origin": "KBP",
    "destination": "WAW",
    "gate": "A12",
    "departure_at": "2026-04-24T11:45:00+00:00",
    "departed_at": null,
    "status": "boarding",
    "created_at": "2026-04-22T12:00:00+00:00",
    "updated_at": "2026-04-23T08:50:00+00:00"
  }
}
```

`curl` example for `POST /api/flights/{id}/sync-status`:

```bash
curl -X POST "http://localhost/api/flights/1/sync-status" \
  -H "Accept: application/json"
```

External sync details:
- the app calls a mock airline endpoint via Laravel HTTP client
- synced fields are `status`, `departure_at`, and optional `gate`
- if the external service fails, the API returns `502 Bad Gateway`

Error response example for failed sync:

```json
{
  "message": "Unable to sync flight status from external airline service.",
  "errors": {
    "external_service": [
      "Unable to sync flight status from external airline service."
    ]
  }
}
```

Local mock airline endpoint:
- `GET /mock-airline/flights/{flightNumber}/status`
- supports optional query params: `current_status`, `status`, `departed_at`, `gate`
- valid transitions:
  `scheduled -> delayed|boarding|cancelled`,
  `delayed -> cancelled|boarding`,
  `boarding -> departed`
- returns `departed_at` only when the next status is `departed`
- returns `gate` only for `scheduled`, `delayed`, or `boarding`
- default `.env` points `AIRLINE_STATUS_BASE_URL` to `${APP_URL}/mock-airline`

Example:

```bash
curl "http://localhost/mock-airline/flights/PS321/status?current_status=scheduled&status=boarding&gate=A12"
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

`curl` example for `POST /api/passengers`:

```bash
curl -X POST "http://localhost/api/passengers" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Iryna",
    "last_name": "Melnyk",
    "email": "iryna.melnyk@example.test",
    "birthday": "1995-08-14",
    "document_number": "AB123456"
  }'
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
| `POST` | `/api/reservations/{id}/check-in`| Check in a reservation            |
| `POST` | `/api/reservations/{id}/cancel`  | Cancel a reservation              |
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
- check-in is allowed only for `booked` reservations
- check-in is rejected if the flight has already `departed`
- successful check-in sets `checked_in_at`, changes status to `checked_in`, and queues asynchronous boarding pass generation
- cancellation is allowed for `booked` and `checked_in` reservations and changes the status to `cancelled`

Request example for `POST /api/flights/{id}/reservations`:

```json
{
  "passenger_id": 5,
  "seat_number": "12C"
}
```

`curl` example for `POST /api/flights/{id}/reservations`:

```bash
curl -X POST "http://localhost/api/flights/1/reservations" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "passenger_id": 5,
    "seat_number": "12C"
  }'
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

Request example for `POST /api/reservations/{id}/check-in`:

```json
{}
```

`curl` example for `POST /api/reservations/{id}/check-in`:

```bash
curl -X POST "http://localhost/api/reservations/9/check-in" \
  -H "Accept: application/json"
```

Response example for `POST /api/reservations/{id}/check-in`:

```json
{
  "data": {
    "id": 9,
    "seat_number": "12C",
    "status": "checked_in",
    "checked_in_at": "2026-04-22T12:05:00+00:00",
    "boarding_pass_code": "PS555-12C-A1B2C3",
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
    "updated_at": "2026-04-22T12:05:00+00:00"
  }
}
```

Request example for `POST /api/reservations/{id}/cancel`:

```json
{}
```

Response example for `POST /api/reservations/{id}/cancel`:

```json
{
  "data": {
    "id": 9,
    "seat_number": "12C",
    "status": "cancelled",
    "checked_in_at": "2026-04-22T12:05:00+00:00",
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
    "updated_at": "2026-04-22T12:06:00+00:00"
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
