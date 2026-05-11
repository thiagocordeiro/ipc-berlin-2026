# Application

If Domain knows what an `Order` *is*, Application knows what happens when a customer actually clicks "Place Order". Domain holds the rules; Application choreographs them. One use case per user-facing operation: `PlaceOrder`, `RegisterUser`, `RefundPayment` — each its own class, each doing exactly one thing.

The reason this layer exists at all is that fat controllers and god-services are easy to write and miserable to navigate. Once "place an order" lives inside `OrderController::store()`, it's tangled with HTTP request parsing, with `JsonResponse`, with security firewalls. You can't reuse it from a Console command, you can't trigger it from a Messenger handler, and you can't unit-test it without booting the kernel. Pulling the operation out into `PlaceOrder::execute()` makes the system's capabilities visible at a glance — `ls src/Application/` becomes a roadmap of what the app actually *does*.

## How the rest of the code reaches in

Use cases are called from **Presenter** (controllers) and from any other entrypoint that triggers them — Console commands, Messenger handlers, scheduled tasks, event subscribers. Symfony's DI container resolves them through autowiring, so the caller just type-hints the use case class in the constructor and the container wires it up.

The use case takes a Command DTO in, returns a Result DTO out (or throws). It never sees a `Symfony\Component\HttpFoundation\Request`, and it never returns a `JsonResponse` — that's Presenter's job.

## What lives here

- **Use cases / Interactors** — one class, one public method: `execute`, `handle`, or `__invoke`. The class name is a verb in the imperative: `PlaceOrder`, `CancelSubscription`, `ImportInvoice`.
- **Commands & Queries** — immutable `readonly` DTOs that describe what the caller wants done. `PlaceOrderCommand`, `FindOrderByIdQuery`. They're the use case's input contract.
- **Results / Output DTOs** — when the return shape isn't a Domain entity. Both are valid return types — pick the one that says the most about the intent.
- **Port interfaces** — for things the use case needs but Domain shouldn't define: `Mailer`, `Clock`, `EventBus`, `PaymentGateway`. Domain defines repositories (because they're about Domain types); Application defines everything else the use case needs from the outside world.

## The dependency rule

Application imports from **Domain** and from **its own ports**. It does not import `Symfony\*`, Doctrine, HTTP request/response classes, or anything from Presenter. If a use case needs persistence, it depends on the repository *interface* declared in Domain — never on `Doctrine\ORM\QueryBuilder`.

The mental test: could you call the same use case from a controller over HTTP *and* from a Console command in the terminal, without changing a line of the use case? If yes, you're on track.

## Best practices

- **One use case = one class = one public method.** If you're tempted to add `executeAlternative()` or `handleEdgeCase()`, what you actually have is a second use case. Make a new class with its own name.
- **Define a generic `UseCase<In, Out>` interface.** PHP doesn't ship generics, but PHPStan and Psalm understand them through `@template` docblocks. Every use case implements the same `handle(In): Out` shape, with concrete types declared per class. The payoff is twofold: a single, recognizable contract across the whole layer, and decorators (logging, transactions, retries) that can wrap *any* `UseCase<In, Out>` without caring about the specific types.
- **Slice by feature, not by type.** Put `PlaceOrder`, `PlaceOrderCommand`, `PlaceOrderResult`, and any feature-specific port in the same folder — `src/Application/PlaceOrder/`. Browsing the folder shows the slice in full; browsing `src/Application/` is the index of what the system can do. Resist the urge to make a `Commands/` folder and a `UseCases/` folder side by side — that organizes by kind, not by capability, and forces readers to chase across folders to follow one feature.
- **Use cases are stateless.** Constructor takes ports and repositories; the public method takes a command and returns a result. Nothing else. No fields that change between calls, no caching state, no "remember the last thing we did".
- **Depend on interfaces, not concrete implementations.** A use case that imports `DoctrineOrderRepository` directly is hard-coupled to whatever ORM is underneath it. One that imports `OrderRepository` (the Domain interface) doesn't care.
- **Wrap transactions behind a `UnitOfWork` interface.** When a use case has to commit several writes atomically — place the order *and* decrement stock *and* enqueue the receipt — don't reach for `$em->wrapInTransaction(...)` from inside the use case. Define a `UnitOfWork` port (a single `inTransaction(callable)` method works fine) and let Infrastructure implement it. The use case stays framework-agnostic, the boundaries of "what's atomic together" become explicit, and the same UoW shape works against Doctrine, against raw PDO, or against an in-memory fake in tests.
- **Commands, Queries, and Results are immutable.** `readonly` classes with everything in the constructor. No setters, no mutable arrays, no surprises after construction.
- **Translate, don't leak.** A use case shouldn't return a Doctrine entity just because that's what the repository's implementation handed it. The translation to a Domain entity happens at the repo boundary; the translation to a Result DTO happens here, if needed.
- **Unit tests use in-memory ports.** `InMemoryOrderRepository` implements the same interface as the Doctrine one and lives next to your tests. Use cases get tested without touching the framework, the database, or the network.

## Things that go wrong

- **Use cases that return `JsonResponse` or `RedirectResponse`.** That's HTTP, and HTTP is Presenter's problem.
- **Use cases that take `Symfony\Component\HttpFoundation\Request` as input.** The moment you do, the use case can only be called from an HTTP controller — never from Messenger, never from the CLI, never from a test without faking a request. Accept a Command DTO; let Presenter unpack the request into it.
- **God use cases** — five public methods and a switch on the input. That's three or four use cases that ran into each other in the dark. Separate them.
- **`$em->wrapInTransaction(...)` directly.** Couples Application to Doctrine. Wrap it behind a `UnitOfWork` port (see Best practices); many cases don't need transactional control at all.

## A note on Symfony

Application classes mostly resolve themselves — Symfony's autowiring + autoconfigure scans the constructor, walks the type hints, and assembles them. The one place you do need to write code is when binding a port (interface) to its adapter (implementation): that goes in `config/services.yaml`, with a line like `App\Domain\Order\OrderRepository: '@App\Infrastructure\Doctrine\Repositories\DoctrineOrderRepository'`.

If you find yourself injecting `ContainerInterface` into a use case to call `$container->get(...)`, stop. Inject what you need explicitly; the service-locator pattern is the easy way to lose track of what depends on what — and it defeats Symfony's compile-time container checks at the same time.
