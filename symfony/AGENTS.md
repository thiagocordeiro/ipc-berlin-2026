# AGENTS.md

Directives for AI agents contributing to this Symfony project. Read each layer's `README.md` under `src/` for layer-specific rules before generating code.

## Architecture

Clean architecture, four layers under `src/`:

- `Domain/` — business model, framework-agnostic. ([README](src/Domain/README.md))
- `Application/` — use cases, one operation per class. ([README](src/Application/README.md))
- `Infrastructure/` — Doctrine, DI wiring, external clients. ([README](src/Infrastructure/README.md))
- `Presenter/` — HTTP controllers, CLI commands. ([README](src/Presenter/README.md))

Dependencies point **inward**: `Presenter → Application → Domain`. Infrastructure implements interfaces declared in Domain/Application; nothing imports from Infrastructure except the DI wiring.

## Where to put new code

| What                                    | Where                                        |
|-----------------------------------------|----------------------------------------------|
| Entity / value object                   | `src/Domain/<Aggregate>/`                    |
| Repository interface                    | `src/Domain/<Aggregate>/`                    |
| Use case + Command DTO                  | `src/Application/<UseCase>/`                 |
| Application port (Mailer, Clock, …)     | `src/Application/Ports/`                     |
| Doctrine entity                         | `src/Infrastructure/Doctrine/Models/`        |
| Repository implementation               | `src/Infrastructure/Doctrine/Repositories/`  |
| Port → adapter binding                  | `config/services.yaml`                       |
| Compiler pass / bundle extension        | `src/Infrastructure/Providers/`              |
| HTTP controller + Request/Response DTOs | `src/Presenter/Http/`                        |
| Route registration                      | `#[Route]` attribute on the controller class |

## Conventions

- Controllers, requests, responses, commands, and result DTOs are `readonly` classes.
- One use case per class; method named `execute`, `handle`, or `__invoke`.
- HTTP I/O uses **php-jackson**: `#[JacksonInject]` on the parameter, `#[JacksonResponse]` on the method. Do not return `JsonResponse` manually.
- Use `DateTimeImmutable` everywhere — Domain, Application, and Infrastructure.
- Names: `PlaceOrder` (use case), `PlaceOrderCommand` (input), `PlaceOrderResponse` (output), `PlaceOrderController` (HTTP entry).

## Hard rules — do not violate

- **Never** import `Symfony\*`, Doctrine ORM, or `EntityManagerInterface` inside `Domain/` or `Application/`.
- **Never** return Doctrine entities from a controller or use case — translate to a Domain entity or Response DTO first.
- **Never** add a second public method to an existing use case — create a new use case class.
- **Never** extend `AbstractController` — keep controllers as plain `readonly` classes; inject dependencies through the constructor.
- **Never** register routes in YAML for application controllers — use the `#[Route]` attribute on the class. `config/routes.yaml` only auto-imports them.

## Generation checklist

Before completing a code-gen task, verify:

1. Domain/Application files import only from Domain (and own Application code).
2. Every new port → adapter mapping is present in `config/services.yaml`.
3. Controllers are single-`__invoke`, `readonly`, decorated with `#[Route]` and Jackson attributes.
4. No Doctrine type leaks past the repository implementation.
5. If adding entities: `composer require doctrine/orm doctrine/doctrine-bundle` is run first (not yet installed in this scaffold).

## Local commands

- `symfony serve` (or `php -S localhost:8000 -t public/`) — run the dev server
- `bin/phpunit` — run the test suite
- `bin/console debug:router` — inspect routes
- `bin/console debug:container` — inspect DI bindings
