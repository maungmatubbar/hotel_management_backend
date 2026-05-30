# Hotel Management Backend

Hotel Management Backend is a Laravel API application for managing tenants, tenant users, rooms, bookings, billing, payments, receipts, file uploads, and authentication.

The codebase follows a layered, domain-oriented structure. Controllers stay thin, application use cases coordinate workflows, domain actions hold business operations, and infrastructure classes handle persistence concerns.

## Tech Stack

- PHP 8.3
- Laravel 13
- Laravel Sanctum for API authentication
- Laravel Horizon for queue monitoring
- Predis for Redis connectivity
- Stancl Tenancy for multi-tenant support
- Spatie Laravel Data for DTOs
- Spatie Permission for roles and permissions
- Spatie Media Library for file/media handling
- Spatie Activity Log for audit logs
- Spatie Query Builder for API filtering and sorting
- Spatie TypeScript Transformer for generated frontend types
- Pest for automated tests
- Laravel Pint for PHP formatting
- Vite 8 and Tailwind CSS 4 for frontend asset tooling

## Architecture

The project uses a layered architecture with DDD-inspired boundaries:

```text
routes/api/*
    -> app/Http/Controllers/*
        -> app/Application/*UseCase.php
            -> app/Domain/*/Action/*
            -> app/Domain/*/DTO/*
            -> app/Domain/*/Repositories/*
                -> app/Infrastructure/*
                    -> app/Models/*
```

### Main Layers

- `app/Http` contains controllers, form requests, and middleware.
- `app/Application` contains use cases that orchestrate feature workflows.
- `app/Domain` contains feature business logic, DTOs, actions, repository contracts, services, and enums.
- `app/Infrastructure` contains repository implementations and infrastructure services.
- `app/Models` contains Eloquent models.
- `app/Support` contains shared helpers, traits, enums, and file utilities.
- `routes/api` contains API route files split by feature.
- `tests` contains Pest feature and unit tests.

## Feature Areas

Current domain areas include:

- `Auth` - login, refresh token, authenticated user, roles, and permissions.
- `Tenant` - tenant creation, tenant users, and tenant authentication.
- `Room` - room creation, update, delete, and listing.
- `Booking` - booking creation, booking listing, guest user handling, and NID image storage.
- `Billing` - invoice, payment, receipt, and down payment workflows.

## Design Patterns Used

- **Use Case Pattern**: application services such as `CreateBookingUseCase` coordinate full workflows.
- **Action Pattern**: focused business operations live in `app/Domain/*/Action`.
- **Repository Pattern**: domain repository interfaces are implemented in `app/Infrastructure/Repositories`.
- **DTO Pattern**: request and response data objects are built with Spatie Laravel Data.
- **Thin Controllers**: controllers validate requests, call use cases, and return API responses.
- **Multi-Tenancy**: tenant context is initialized through middleware and route/auth context.

## Directory Structure

```text
app/
├── Application/
│   ├── Auth/
│   ├── Booking/
│   ├── Room/
│   └── Tenant/
├── Domain/
│   ├── Auth/
│   ├── Billing/
│   ├── Booking/
│   ├── Room/
│   └── Tenant/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Infrastructure/
│   ├── Repositories/
│   ├── Services/
│   └── Tenancy/
├── Models/
├── Observers/
├── Providers/
└── Support/
```

```text
routes/
├── api/
│   ├── auth.php
│   ├── bookings.php
│   ├── files.php
│   ├── rooms.php
│   └── tenants.php
├── console.php
└── web.php
```

## Local Setup

Install PHP dependencies:

```bash
composer install
```

Create the environment file and application key:

```bash
cp .env.example .env
php artisan key:generate
```

Install JavaScript dependencies:

```bash
npm install
```

Run migrations:

```bash
php artisan migrate
```

Build frontend assets:

```bash
npm run build
```

You can also run the project setup script:

```bash
composer run setup
```

## Development

Start the full local development stack:

```bash
composer run dev
```

This starts:

- Laravel development server
- Queue listener
- Laravel Pail logs
- Vite development server

Run only Vite:

```bash
npm run dev
```

## Testing

Run the full test suite:

```bash
php artisan test --compact
```

Run a specific test file:

```bash
php artisan test --compact tests/Feature/TenantManagementTest.php
```

Run tests through Composer:

```bash
composer test
```

## Code Style

Format PHP changes with Laravel Pint:

```bash
vendor/bin/pint --dirty --format agent
```

## API Route Organization

API routes are split by feature under `routes/api`. Keep new endpoints in the closest existing route file unless a new bounded feature area is introduced.

Controllers should delegate business work to application use cases. Use form requests for validation and DTOs for structured input/output data.

## Development Conventions

- Keep controllers thin.
- Put workflow orchestration in `app/Application`.
- Put business rules in `app/Domain`.
- Put persistence implementations in `app/Infrastructure`.
- Use explicit method parameter and return types.
- Use factories and Pest tests for new behavior.
- Run the focused test file or filter for the area you changed.
- Run Pint before finalizing PHP changes.
