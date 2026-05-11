# src/

Two questions decide where a class goes, and they're independent:

1. **Which part of the business is this about?** — the *bounded context* (the module: `Coupon/`, `Campaign/`, `Shared/`).
2. **What kind of job does it do?** — the *layer* (`Domain`, `Application`, `Infrastructure`, `Presenter`).

The first picks the top-level folder; the second picks the folder inside it. So `src/Coupon/Domain/Coupon/Coupon.php` reads left to right as *the Coupon context's Domain model of a Coupon*. The path tells you the business area first, then the architectural role.

```
src/
├── Coupon/            ← bounded context (module)
│   ├── Domain/        ← layer
│   ├── Application/   ← layer
│   ├── Infrastructure/← layer
│   └── Presenter/     ← layer
├── Campaign/          ← bounded context (module)
│   └── …same four layers
└── Shared/            ← cross-cutting kernel (small on purpose)
```

The four-layer story — *dependencies point inward* — is told once, in each layer's README, and it holds identically inside every module. This file is about the **other** axis: how the modules relate to each other.

## Why modules first

The common alternative is to split by layer at the top — one big `src/Domain/`, one big `src/Application/` — and drop every context's files into the shared pile. It looks tidy on day one and rots by month three: to understand "coupons" you open four distant folders and mentally filter out everything about campaigns, payments, and users that happens to share them.

Splitting by **context** first keeps a feature's whole vertical slice in one place. Everything about coupons — the entity, the use cases, the repository, the controller — lives under `src/Coupon/`. You can read a context without loading the rest of the app into your head, hand one context to one person, and *delete* a context by deleting its folder. Cohesion goes up; the blast radius of a change goes down.

## Bounded contexts

A **bounded context** is a boundary around a model inside which every word means exactly one thing. "Coupon" inside `Coupon/` is the full entity with its identity and lifecycle. "Coupon" as far as `Campaign/` is concerned is just *a code to put in an email* — a different, smaller idea wearing the same word. That's not duplication to stamp out; it's two contexts each modelling only what they need. Forcing them to share one `Coupon` class would couple a marketing concern to an issuance concern, and every change to one would ripple into the other.

Each context is autonomous: its own four layers, its own wiring, its own tests. It does not reach into another context's internals.

## The rule between contexts

Inside a context, layers depend inward. **Between** contexts the rule is stricter:

> A context may depend only on another context's **published contract** — never on its internals.

The single cross-context link in this codebase is an event, and it shows the rule in practice:

```
Coupon                                   Shared bus            Campaign
──────                                   ──────────            ────────
CouponCreated  ──publish──▶  MemoryPubSub  ──rehydrate──▶  ShareCouponWithCustomersCommand
  ::name() = 'coupon.created'   (serialize)   (deserialize)   └─▶ …EventListener ─▶ …UseCase
```

- **Coupon** publishes `CouponCreated`, a past-tense fact named by a topic string: `coupon.created`.
- The **Shared** bus serializes it on the way out (via php-jackson's `ObjectMapper`) and rehydrates the payload into whatever type the subscriber asked for on the way in.
- **Campaign** subscribes to the string `'coupon.created'` and receives its *own* `ShareCouponWithCustomersCommand` — built by the bus from the wire payload.

Look at what Campaign imports: nothing from `App\Coupon`. The only shared knowledge between the two contexts is the topic string `coupon.created`. That string is the published contract — exactly like a topic name on a real message broker. Swap the in-memory bus for a real broker tomorrow and neither context changes.

If you ever write `use App\Coupon\…` from inside `src/Campaign/` (or vice-versa), that's the smell: you've reached past the boundary into another context's model. The fix is a published contract — an event, or a call through a port — not an import.

## The Shared module

`Shared/` holds the genuinely cross-cutting primitives every context builds on:

- `Domain/Entity` — the identity base class entities extend.
- `Domain/Event/{Event,Publisher,Subscriber}` — the messaging ports.
- `Infrastructure/MemoryPubSub` — the in-memory adapter implementing both ports.

Keep it small. `Shared/` is a *shared kernel*, and shared kernels are where coupling quietly accumulates — every context depends on it, so anything you put here is something every context is now married to. The bar for adding something: two or more contexts genuinely need the same thing, and that thing has **no business meaning of its own** (a `Coupon` never belongs here; a `Publisher` does).

## Wiring

Each module owns its container wiring in its `Infrastructure/Providers/services.yaml`, the Symfony counterpart to Laravel's per-module service providers. `config/services.yaml` imports them after the PSR-4 auto-registration so a context's explicit bindings, aliases, and tags take precedence:

- `Shared/.../services.yaml` — aliases `Publisher` and `Subscriber` to a single shared `MemoryPubSub`.
- `Coupon/.../services.yaml` — binds `CouponRepository` to its implementation.
- `Campaign/.../services.yaml` — tags its listener `app.event_subscriber` for the `coupon.created` topic.

The cross-context subscription itself is wired by `Shared\…\RegisterEventSubscribersPass`, a compiler pass (registered in `Kernel::build()`) that collects every `app.event_subscriber`-tagged service and registers it on the bus. A context declares *what* it listens to; Shared does the plumbing without knowing the contexts exist.

To remove a context, you delete its folder and drop one line from `config/services.yaml`.

## Best practices

- **New capability in an existing context → a new feature slice inside it.** Adding "reserve a coupon" means `src/Coupon/Application/ReserveCoupon/`, not a new top-level folder.
- **New business area → a new module** with its own four layers and its own `services.yaml`.
- **Contexts talk through published contracts** — events or ports — never direct calls into each other's Domain or Application.
- **Keep `Shared/` lean.** When in doubt, a thing belongs to a context, not to Shared.
- **Each module wires itself.** A context's bindings live with the context, not in one global file.

## Things that go wrong

- **Cross-context imports.** `use App\Coupon\Domain\…` inside `Campaign/`. The boundary is gone the moment this compiles; publish a contract instead.
- **A God `Shared/` module.** Half the codebase slides into `Shared/` "because two things use it". Now every context is coupled to every other through the middle. Push things back out to the context that owns them.
- **One entity shared by two contexts.** A single `Coupon` class imported by both Coupon and Campaign feels DRY and becomes a liability — the two contexts have different reasons to change it, and they fight. Let each model what it needs.
- **Layer-first inside the app.** Collapsing back to one `src/Domain/` for every context trades cohesion for a tidy-looking tree and loses the ability to reason about (or delete) a context in isolation.
- **Wrong boundaries.** If two "contexts" can't stop importing each other, they're probably one context drawn in the wrong place. Merge them, or find the real seam.

## A note on Symfony

Symfony is indifferent to how you fold `src/`. PSR-4 maps `src/` → `App\`, so `src/Coupon/Domain/Coupon/Coupon.php` is just `App\Coupon\Domain\Coupon\Coupon` — the module layout *is* the namespace. The `App\` resource block registers every class as a service regardless of folder depth.

The one place the framework shows up is wiring, and per-module `services.yaml` files keep that local: each context's bindings and tags live with the context rather than piling into a single file. Cross-context subscriptions go through a compiler pass instead of a runtime `boot()` — different mechanism from Laravel, same outcome: a context names what it needs, and the wiring stays out of the business code.
