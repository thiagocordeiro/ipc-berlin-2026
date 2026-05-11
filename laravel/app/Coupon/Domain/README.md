# Domain

This is where your business lives — not your *application*, your *business*. On a shopping system, the pieces here describe what an Order actually is, when it's allowed to ship, what makes an Email well-formed. They'd still be true if you threw away Laravel tomorrow and rebuilt the whole thing in Symfony, or in another language entirely.

That's the point of putting them at the center: business rules outlive their surroundings. Payment providers get swapped out, shipment carriers change, the CRM you integrate with this year won't be the same one in three years — but "an order can't ship without payment" stays true. Regardless of which provider processed the charge, what matters to the business is that the money was received.

So we keep that knowledge in plain PHP, away from anything that might rot.

## How the rest of the code reaches in

It doesn't, directly. Domain types are constructed and used by the **Application** layer; nothing else gets to talk to them. A controller never news up an `Order` — a use case does. Eloquent never stores an `Order` — a repository implementation translates between the Domain entity and the persistence row.

If you find yourself importing something from `Domain/` into a controller, that's a smell. The use case is missing.

## What lives here

- **Entities** — carry identity and a lifecycle. Two `Order`s with the same data are still two different orders.
- **Value Objects** — defined entirely by their data. Two `Money(10, EUR)` are interchangeable. Immutable, equality-by-value.
- **Enums** — closed sets of values that the business actually cares about: `OrderStatus`, `PaymentMethod`, `Currency`. If a string column has more than three valid values, it probably wants to be an enum.
- **Domain Services** — logic that doesn't naturally belong to one entity. The classic example: a `PriceCalculator` that needs an order *and* a discount policy *and* a tax table to do its job.
- **Domain Events** — facts that already happened. `OrderPlaced`, `PaymentRefunded`. Past tense, no behavior.
- **Repository interfaces** — declare *what* persistence promises ("give me the order with this id"), not *how* it's done. The how lives in Infrastructure.
- **Domain exceptions** — business-rule violations like `InsufficientStock` or `OrderAlreadyShipped`. Distinct from infrastructure failures like "database timeout".

## The dependency rule

Nothing in here imports from Application, Infrastructure, or Presenter. No `Illuminate\*`, no `Carbon`, no Eloquent — just pure PHP, `DateTimeImmutable`, and other Domain types.

The mental test: could you copy this folder into a script with no Laravel installed and have it still compile? If yes, you're on track.

## Best practices

- **Methods speak the business's language.** `$order->cancel($reason)`, not `$order->setStatus(OrderStatus::Cancelled)`. The name should be something a non-developer in the same room would recognize. If product and engineering use different words for the same thing, the Domain is where you settle on one and use it everywhere — class names, method names, events, exceptions.
- **An entity cannot exist in an invalid state.** The constructor (or factory method) is responsible for enforcing invariants up front. If you can `new Order(customerId: 0, total: -5)` and only learn it's wrong three method calls later, the design has already lost. Validate at construction; throw immediately on bad input.
- **Prefer named constructors over a public `__construct`.** `Order::place($cart, $customer)` carries intent that `new Order(...)` doesn't. It also lets you have multiple creation paths — `Order::place()` for new orders, `Order::reconstituteFrom($row)` for rehydration from storage — without overloading one constructor with optional everything.
- **Wrap primitives in Value Objects when the type carries meaning beyond its raw value.** `Email` is a value object because not every string is a valid email — once an `Email` exists, you know it parses, and you stop asking "is this checked yet?" at every boundary. `Money(10, EUR)` is one because the amount and currency must travel together, and arithmetic has to respect both. The bar is: there's an invariant to protect, a unit to carry, or behavior to attach. A `Counter(1)` clears none of those; it's just `int $count` with extra steps. Reach for a VO when wrapping the primitive *prevents bugs*, not when it just gives the primitive a nicer name.
- **Entity state is either fully `readonly` or sealed behind business methods.** Public getters paired with private setters are fine; raw public mutability isn't. State changes through methods named after what's happening — `cancel`, `ship`, `refund` — not generic setters.
- **Throw on rule violations; don't return `null` or `false`.** `Order::cancel()` should throw `OrderAlreadyShipped` if the order's already in transit. Boolean returns invite callers to forget to check; named exceptions force the conversation, and they show up in stack traces with a name that means something.
- **Domain Services are stateless.** They orchestrate logic across entities; they don't remember anything between calls. The same rule applies to use cases and controllers — we'll come back to it in those layers.
- **Unit tests run on plain PHPUnit.** No `TestCase` from Laravel, no database, no `bootstrap/app.php`. If a Domain test needs the framework to start up, you're testing the wrong thing.

## Things that go wrong

- **Anemic entities.** Classes with only getters and setters, where all the actual logic ends up in services. The behavior *wants* to live on the entity — let it.
- **Eloquent in disguise.** Extending `Model`, using `$casts`, reaching for `find()`. That's not a Domain entity, that's a database row with a fancier name.
- **Time leaks.** An entity that calls `sleep()` to throttle, or reads `time()` directly, drags the runtime clock into your tests. Carbon offers `setTestNow()` to paper over this — but that's a test-time workaround for a design-time mistake. Take the time as a parameter, or inject a clock.
- **Database-aware validation.** "Is this email unique?" is a query, not a rule. The rule ("email must be present and well-formed") goes here; the uniqueness check belongs in Application.

## A note on Laravel

Laravel offers nothing in this layer, and it shouldn't. No facades, no `app()`, no service container — Domain is the one place in the codebase where you don't get any framework magic. If a Domain unit test starts failing because `bootstrap/app.php` didn't load, something has leaked. Find it and put it back where it belongs.

Use `DateTimeImmutable` here, not `Carbon`. Carbon is great — expressive, ergonomic, fluent — but it's a Laravel-flavored concession, and Domain doesn't make those.
