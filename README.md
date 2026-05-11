# ipc-berlin

A pair of PHP scaffolds — one **Laravel**, one **Symfony** — built around **Clean Architecture / Onion Architecture**, used as a live-coding template for a talk on *clean architecture + AI-driven development* at IPC Berlin. The two projects are wired the same way; both exist so the audience can pick the framework, and watch the same architectural patterns hold up underneath. A third folder, [`vanilla/`](vanilla/), exists as a documentation-only reference for the same architecture without any framework at all.

## What it's for

Two goals, in this order:

1. **Show that Clean Architecture is a framework-agnostic idea.** The same layering — Domain, Application, Infrastructure, Presenter — works in Laravel, in Symfony, and in any PHP setup that lets you organize files into folders and inject dependencies through a constructor. The framework choice is a delivery detail, not an architectural one.
2. **Show that AI agents can produce sound code *inside* a strong architecture.** Strict layer rules give an agent (or a junior, or your future self) a stable structure to lean against. The rules are written down in each project's `AGENTS.md` so an agent picking up the repo cold can produce code that fits.

## Architecture in one paragraph

The codebase is organized as concentric layers — **Domain** at the center, **Application** around it, **Infrastructure** and **Presenter** on the outside. Code can only depend on things *closer to the center than itself*: Presenter and Infrastructure both depend on Application; Application depends on Domain; Domain depends on nothing but plain PHP. That single rule — *dependencies point inward* — is what makes the architecture portable, testable, and resistant to framework drift.

The terms **Clean Architecture** (Robert C. Martin) and **Onion Architecture** (Jeffrey Palermo) describe slightly different framings of the same insight: business logic at the center, transport and persistence at the edges, abstraction at the boundaries. We treat them as interchangeable here — the layer names matter, not the brand.

## The layers

| Layer | What it holds | Read more |
|---|---|---|
| **Domain** | Business model — entities, value objects, enums, domain services, repository interfaces, domain exceptions | [Laravel](laravel/app/Domain/README.md) · [Symfony](symfony/src/Domain/README.md) |
| **Application** | Use cases — one class per user-facing operation, plus their commands, queries, results, and ports | [Laravel](laravel/app/Application/README.md) · [Symfony](symfony/src/Application/README.md) |
| **Infrastructure** | Adapters — persistence, providers/DI wiring, external clients | [Laravel](laravel/app/Infrastructure/README.md) · [Symfony](symfony/src/Infrastructure/README.md) |
| **Presenter** | Delivery — HTTP controllers, request/response DTOs, CLI commands | [Laravel](laravel/app/Presenter/README.md) · [Symfony](symfony/src/Presenter/README.md) |

Each layer's README covers its purpose, what belongs in it, the dependency rule, best practices, and the things that go wrong.

## The projects

### [laravel/](laravel/) — Laravel 12

Routes are registered in `routes/api.php` via the `Route::` facade. Persistence uses Eloquent (`app/Infrastructure/Eloquent/`). DI bindings live in `AppServiceProvider::register()`. HTTP I/O uses **[php-jackson-laravel](https://github.com/tcds-io/php-jackson-laravel)**.

```bash
cd laravel
composer install
php artisan serve         # http://localhost:8000
php artisan test
php artisan route:list
```

See [laravel/AGENTS.md](laravel/AGENTS.md) for the project-specific rules.

### [symfony/](symfony/) — Symfony 8

Routes are declared on the controller via `#[Route]` attributes, auto-imported through `config/routes.yaml`. Persistence will use Doctrine ORM (`src/Infrastructure/Doctrine/` — not yet installed). DI bindings live in `config/services.yaml`. HTTP I/O uses **[php-jackson-symfony](https://github.com/tcds-io/php-jackson-symfony)**.

```bash
cd symfony
composer install
symfony serve                  # http://localhost:8000
bin/phpunit
bin/console debug:router
```

See [symfony/AGENTS.md](symfony/AGENTS.md) for the project-specific rules.

### [vanilla/](vanilla/) — reference, not live-coding

A framework-stripped reference for the same architecture, kept as documentation only. Not chosen for live-coding (you'd spend the first ten minutes writing a router and a DI wiring class before the architecture starts to show), but committed to make the point that the architecture is portable all the way down to plain PHP. See [vanilla/README.md](vanilla/README.md) for the file tree and the rationale.

## For agents

Each project has its own `AGENTS.md` with framework-specific rules — start there before generating any code:

- [laravel/AGENTS.md](laravel/AGENTS.md)
- [symfony/AGENTS.md](symfony/AGENTS.md)

The architectural rules are identical between the two; only the framework conventions differ (route declaration, DI configuration, persistence library, etc.).

## Repository & contact

<img src="docs/repo-qr.png" alt="Repository QR code" width="220" />

**Repository:** https://github.com/thiagocordeiro/ipc-berlin-2026

**Author:** Thiago Cordeiro — [LinkedIn](https://www.linkedin.com/in/thiagocordeirooo/)
