# Architecture and Structure

This document explains the current backend architecture, folder structure, request flow, and development conventions for the Hotel Management Backend.

## Overview

The application is a Laravel API backend built with a layered, domain-oriented structure. The goal is to keep HTTP concerns, application workflows, domain rules, and infrastructure details separated.

High-level flow:

```text
API Route
    -> Controller
        -> Form Request
        -> Application Use Case
            -> Domain DTO
            -> Domain Action
            -> Repository Contract
                -> Infrastructure Repository
                    -> Eloquent Model
```

## Main Layers

### HTTP Layer

Path: `app/Http`

Responsible for:

- API controllers
- Request validation
- Middleware
- HTTP response formatting

Controllers should stay thin. They should validate input, call a use case, and return a response.

Example folders:

```text
app/Http/
├── Controllers/
│   ├── Auth/
│   └── Tenant/
├── Middleware/
└── Requests/
```

### Application Layer

Path: `app/Application`

Responsible for:

- Coordinating full feature workflows
- Managing transactions
- Calling domain actions
- Preparing final data for controllers

Use cases are named by intent, for example:

```text
app/Application/
├── Auth/
├── Booking/
├── Room/
└── Tenant/
```

Example:

```text
CreateBookingUseCase
    -> resolves guest user
    -> assigns customer role
    -> creates booking
    -> creates invoice
    -> creates down payment and receipt when needed
```

### Domain Layer

Path: `app/Domain`

Responsible for:

- Business rules
- Domain actions
- Data transfer objects
- Repository contracts
- Domain services

Current domain areas:

```text
app/Domain/
├── Auth/
├── Billing/
├── Booking/
├── Room/
└── Tenant/
```

Common subfolders:

```text
Action/
Actions/
DTO/
Repositories/
Services/
```

### Infrastructure Layer

Path: `app/Infrastructure`

Responsible for:

- Repository implementations
- External service implementations
- Tenancy infrastructure
- Persistence-specific logic

Example:

```text
app/Infrastructure/
├── Repositories/
├── Services/
└── Tenancy/
```

Domain contracts should not depend on infrastructure classes. Infrastructure classes implement domain contracts.

### Model Layer

Path: `app/Models`

Responsible for:

- Eloquent models
- Relationships
- Casts
- Model constants
- Model scopes when needed

Current important models include:

```text
Booking
File
Invoice
Payment
Receipt
Room
Tenant
User
```

### Support Layer

Path: `app/Support`

Responsible for shared utilities that do not belong to a single domain.

Examples:

```text
app/Support/
├── Enums/
├── File/
├── Helpers/
└── Traits/
```

## Route Structure

API routes are split by feature:

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

Keep new routes inside the closest existing feature route file unless a new domain area is introduced.

## Feature Modules

### Auth

Handles:

- Login
- Token refresh
- Authenticated user data
- Roles and permissions

Main paths:

```text
app/Application/Auth
app/Domain/Auth
app/Http/Controllers/Auth
routes/api/auth.php
```

### Tenant

Handles:

- Tenant creation
- Tenant users
- Tenant user login
- Tenant-scoped access

Main paths:

```text
app/Application/Tenant
app/Domain/Tenant
app/Http/Controllers/Tenant
routes/api/tenants.php
```

### Room

Handles:

- Room creation
- Room update
- Room deletion
- Room listing

Main paths:

```text
app/Application/Room
app/Domain/Room
app/Http/Controllers/Tenant/RoomController.php
routes/api/rooms.php
```

### Booking

Handles:

- Booking creation
- Booking listing
- Booking details
- Guest user creation
- NID image storage

Main paths:

```text
app/Application/Booking
app/Domain/Booking
app/Http/Controllers/Tenant/BookingController.php
routes/api/bookings.php
```

### Billing

Handles:

- Booking invoice creation
- Down payment creation
- Receipt creation

Main paths:

```text
app/Domain/Billing
app/Models/Invoice.php
app/Models/Payment.php
app/Models/Receipt.php
```

## Design Patterns

### Thin Controller

Controllers should avoid business logic. They should:

- Accept a validated request
- Call a use case
- Return a response

### Use Case Pattern

Use cases live in `app/Application`. They coordinate feature workflows and may use database transactions.

Naming:

```text
CreateBookingUseCase
GetBookingsUseCase
CreateRoomUseCase
LoginUseCase
```

### Action Pattern

Actions live in `app/Domain/*/Action`. Each action should do one focused business operation.

Naming:

```text
CreateBookingAction
CreateInvoiceForBookingAction
CreateBookingGuestUserAction
```

### DTO Pattern

DTOs live in `app/Domain/*/DTO` and use Spatie Laravel Data.

DTOs are used for:

- Structured request data
- Structured response data
- Type-safe data movement between layers

### Repository Pattern

Repository contracts live inside domain folders. Implementations live in infrastructure.

Example:

```text
app/Domain/Booking/Repositories/BookingRepositoryInterface.php
app/Infrastructure/Repositories/BookingRepository.php
```

Bindings are registered through service providers.

## Multi-Tenancy

The application uses tenant-aware logic. Tenant context is initialized through middleware and route/auth context.

Important paths:

```text
app/Http/Middleware/InitializeTenantFromAuthenticatedUser.php
app/Http/Middleware/InitializeTenantByRouteParameter.php
app/Infrastructure/Tenancy
app/Providers/TenancyServiceProvider.php
```

Tenant-scoped features should make tenant boundaries explicit in requests, use cases, and queries.

## Testing Structure

Tests are written with Pest.

```text
tests/
├── Feature/
└── Unit/
```

Use feature tests for API workflows and unit tests for focused domain actions.

Common commands:

```bash
php artisan test --compact
php artisan test --compact tests/Feature/TenantManagementTest.php
php artisan test --compact --filter=testName
```

## Development Rules

- Keep controllers thin.
- Keep business rules inside domain actions.
- Keep workflow orchestration inside application use cases.
- Keep persistence implementations inside infrastructure.
- Use DTOs for structured request and response data.
- Use explicit parameter and return types.
- Use database transactions for multi-step writes.
- Add or update tests for behavior changes.
- Run focused tests for changed areas.
- Run Pint before finalizing PHP changes.

## Adding a New Feature

Recommended flow:

1. Add or update API routes in `routes/api`.
2. Create a form request in `app/Http/Requests` when validation is needed.
3. Create or update a controller in `app/Http/Controllers`.
4. Add a use case in `app/Application/{Feature}`.
5. Add DTOs in `app/Domain/{Feature}/DTO`.
6. Add domain actions in `app/Domain/{Feature}/Action`.
7. Add repository contracts in `app/Domain/{Feature}/Repositories` only when persistence abstraction is needed.
8. Add repository implementations in `app/Infrastructure/Repositories`.
9. Register bindings in a service provider.
10. Add feature or unit tests.
