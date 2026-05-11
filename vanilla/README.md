# vanilla — reference implementation (planned)

> **Status:** documentation-only. This folder reserves the namespace; the implementation is not (yet) committed.

A framework-stripped reference for the same Clean Architecture used by the [Laravel](../laravel) and [Symfony](../symfony) scaffolds. It exists to answer one question: *"sure, this works with a framework — but does it work without one?"* The architecture is identical; the wiring is by hand.

## Why this folder exists, given that nothing's in it

For the IPC Berlin talk, the live-coding portion happens in **Laravel or Symfony** — the audience picks. This folder is not a third option. It's a reference the slides and Q&A can point at: *"yes, the architecture is the same in vanilla PHP — here's what it would look like."*

Vanilla isn't chosen for live-coding because the demo cost outweighs the demo value: you spend the first ten minutes writing a router, a tiny DI wiring class, and PSR-7 plumbing before the architecture starts to show. With a framework, you get to the architecture immediately.

## What it would contain

The same four layers as the other projects, plus the minimum plumbing a framework would otherwise provide:

```
vanilla/
├── composer.json              # PSR-7 (nyholm/psr7 or similar), a tiny PSR-15-ish runner
├── public/
│   └── index.php              # front controller (~30 lines: bootstrap → route → respond)
└── src/
    ├── Domain/                # ← identical structure to laravel/symfony
    ├── Application/           # ← identical structure
    ├── Infrastructure/
    │   ├── Persistence/       # raw PDO repository implementations
    │   └── Container/         # tiny manual DI container (or a Wiring class)
    └── Presenter/
        └── Http/              # controllers + tiny dispatcher/router
```

The layer READMEs would mirror Laravel's and Symfony's almost word-for-word — the architectural rules don't change. The only differences are framework adaptations:

- **Routing:** plain `match` in `public/index.php` (no library), or `nikic/fast-route` if the route table grows.
- **DI:** manual wiring in a `Container` class — every adapter constructed once, every dependency passed in by reference. No autowiring.
- **HTTP I/O:** PSR-7 request/response objects; `json_encode` / `json_decode` for serialization, or php-jackson's standalone core if it ships one. No `JsonResponse`, no `#[JacksonResponse]`.
- **Persistence:** raw PDO. No Eloquent, no Doctrine. Repository implementations write SQL directly.

## What stays the same

- The **four layers** and their responsibilities.
- The **dependency rule** — inward-pointing, no exceptions.
- The **best practices** in each layer's README — naming, statelessness, immutable DTOs, entities-can't-exist-invalid, named constructors, value objects with invariants, `UnitOfWork` for transactions, repositories returning Domain entities.
- The **hard rules** that would land in `AGENTS.md` — no business logic in repositories, no framework imports in Domain, one use case per class.

That's the point: the framework moves; the architecture doesn't.

## If you're reading this hoping it's runnable

It isn't, on purpose. If you want a working Clean Architecture PHP project, pick [Laravel](../laravel) or [Symfony](../symfony). Vanilla is here to make the case that the architecture is portable — not to be a third deployment target.
