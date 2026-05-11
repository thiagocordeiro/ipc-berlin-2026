# AGENTS.md

Directives for AI agents contributing to this Laravel project. **Read this file in full before generating any code.** Layer-specific rules live in each layer's `README.md`, linked below — consult the relevant one before touching that folder.

## Architecture

This project follows **Clean Architecture / Onion Architecture**: four concentric layers under `app/`, with a single overarching rule — **dependencies point inward**. Business logic sits at the center; framework, persistence, and transport live at the edges.

```
                ┌───────────────────────────────────┐
                │            Presenter              │
                │  ┌─────────────────────────────┐  │
                │  │         Application         │  │
                │  │   ┌─────────────────────┐   │  │
                │  │   │       Domain        │   │  │
                │  │   └─────────────────────┘   │  │
                │  └─────────────────────────────┘  │
                └───────────────────────────────────┘
                          ▲
                          │ implements interfaces from
                          │
                ┌───────────────────────────────────┐
                │          Infrastructure           │
                └───────────────────────────────────┘
```

| Layer | Folder | What it holds |
|---|---|---|
| **Domain** | [app/Domain/](app/Domain/README.md) | Entities, value objects, enums, domain services, repository interfaces, domain exceptions |
| **Application** | [app/Application/](app/Application/README.md) | Use cases (one per operation), commands, queries, results, ports |
| **Infrastructure** | [app/Infrastructure/](app/Infrastructure/README.md) | Eloquent models, repository implementations, providers, external clients, adapters |
| **Presenter** | [app/Presenter/](app/Presenter/README.md) | HTTP controllers, request/response DTOs, CLI commands |

## The dependency rule

Read each row as: *"Code in this layer may import from these layers."*

| From ↓ \ Imports → | Domain | Application | Infrastructure | Presenter |
|---|:---:|:---:|:---:|:---:|
| **Domain** | ✅ | ❌ | ❌ | ❌ |
| **Application** | ✅ | ✅ | ❌ | ❌ |
| **Infrastructure** | ✅ | ✅ | ✅ | ❌ |
| **Presenter** | ✅ | ✅ | ❌ | ✅ |

Repositories are the special case: their *interfaces* live in **Domain** (they're typed by Domain entities and are part of the business vocabulary), but their *implementations* live in **Infrastructure**.

Application ports (Mailer, Clock, EventBus, PaymentGateway, …) are declared in **Application** — they describe what use cases need from the outside world — and implemented in **Infrastructure**.

## Where to put new code

| What | Where |
|---|---|
| Entity / value object / enum | `app/Domain/<Aggregate>/` |
| Repository interface | `app/Domain/<Aggregate>/` |
| Use case + Command + Result | `app/Application/<UseCase>/` (feature slice) |
| Application port (feature-specific) | `app/Application/<UseCase>/` |
| Application port (cross-cutting, e.g. `Clock`) | `app/Application/Shared/` |
| Eloquent model | `app/Infrastructure/Eloquent/Models/` |
| Repository implementation | `app/Infrastructure/Eloquent/Repositories/` |
| External-client adapter | `app/Infrastructure/<Vendor>/` |
| Container binding | `app/Infrastructure/Providers/AppServiceProvider::register()` |
| HTTP controller + Request/Response DTOs | `app/Presenter/Http/` |
| Route registration | `routes/api.php` (or `routes/web.php`) via `Route::` facade |

## Conventions

- Controllers, requests, responses, commands, queries, and result DTOs are `readonly` classes.
- One use case per class. Method named `execute`, `handle`, or `__invoke`. Never add a second public method to an existing use case.
- Use cases implement a generic `UseCase<In, Out>` interface (typed via PHPStan/Psalm `@template`).
- Application is **sliced by feature**: `app/Application/PlaceOrder/` contains `PlaceOrder.php`, `PlaceOrderCommand.php`, `PlaceOrderResult.php`, and any feature-specific ports — together in one folder.
- HTTP I/O uses **php-jackson**: `#[JacksonInject]` on the parameter, `#[JacksonResponse]` on the method. No manual `JsonResponse` returns.
- Use `DateTimeImmutable` in Domain. `Carbon` is allowed only at the edges (Infrastructure / Presenter).
- Naming:
  - **Use case**: `PlaceOrder` (verb in imperative)
  - **Input**: `PlaceOrderCommand` / `PlaceOrderQuery`
  - **Output**: `PlaceOrderResult` (or a Domain entity)
  - **Controller**: `PlaceOrderController`
  - **Adapter**: `<tool><port>` — `EloquentOrderRepository`, `StripePaymentGateway`, `PostmarkMailer`
- Domain methods speak the business's language: `$order->cancel($reason)`, not `$order->setStatus(OrderStatus::Cancelled)`.
- Transactions go through a `UnitOfWork` port, never `DB::transaction(...)` directly from a use case.
- Entities cannot exist in an invalid state — the constructor (or factory method) enforces invariants up front.

## Hard rules — do not violate

- **Never** import `Illuminate\*`, `Carbon`, or Eloquent inside `Domain/` or `Application/`.
- **Never** return Eloquent models from a controller or use case — translate to a Domain entity (at the repository boundary) or a Response DTO (at the controller boundary).
- **Never** call `app(Foo::class)`, `resolve(...)`, or any service-locator pattern from inside a use case or controller. Inject dependencies through the constructor.
- **Never** call any Laravel facade (`DB::`, `Auth::`, `Cache::`, `Mail::`) from inside a use case — depend on a port instead.
- **Never** put `#[Route]` attributes on controllers in this project — routes belong in `routes/*.php`.
- **Never** put business logic in a controller, in a request validator, or in a repository implementation.
- **Never** add a second public method to an existing use case — create a new use case class.
- **Never** let infrastructure exceptions (`QueryException`, `PDOException`, vendor SDK exceptions) escape Infrastructure — return `?Entity` or throw a Domain exception instead.

## Generation checklist

Before completing a code-gen task, verify:

1. Each new file is in the correct layer per the **Where to put new code** table.
2. Domain and Application files import only from Domain (and own Application code) — no `Illuminate\*`, no Eloquent, no `Carbon`.
3. Every new port has a binding in `AppServiceProvider::register()`.
4. Controllers are single-`__invoke`, `readonly`, and use Jackson attributes.
5. Each new use case has a route entry in `routes/api.php`.
6. Eloquent types do not appear anywhere outside `app/Infrastructure/Eloquent/`.
7. No `app()`, `resolve()`, or facade calls inside use cases or controllers (other than `Route::` in route files).
8. Each new use case has a test under `tests/` that uses an in-memory port (no DB, no framework boot for Domain tests).

## Local commands

- `php artisan serve` — dev server at http://localhost:8000
- `php artisan test` — run the test suite
- `php artisan route:list` — inspect the public API
- `php artisan tinker` — REPL (boots the framework — not for Domain testing)
- `composer install` — install dependencies
