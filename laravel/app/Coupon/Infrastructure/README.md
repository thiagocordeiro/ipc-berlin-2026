# Infrastructure

This is where the rest of the world plugs in. Domain says "give me the order with id 47"; Infrastructure says "okay — here's a `SELECT * FROM orders WHERE id = 47`, and here's how I turn the row I get back into a Domain `Order`". Domain says "send the customer a receipt"; Infrastructure says "here's the SDK call to Postmark, here's the retry logic, here's what I do when their service is down".

Concentrating all that vendor- and framework-specific knowledge in one layer is what keeps the rest of the codebase agnostic. Swap MySQL for Postgres? It's a configuration change plus maybe a couple of repository tweaks; nothing in Domain or Application moves. Swap Postmark for SES? Edit one adapter and update one binding; the rest of the system doesn't notice.

## How the rest of the code reaches in

Through interfaces only. A use case asks for an `OrderRepository`; the container hands it `EloquentOrderRepository`. The use case doesn't know — and doesn't care — which implementation it got, and that's exactly the point.

Bindings live in `app/Infrastructure/Providers/AppServiceProvider::register()`. That file is the seam between "what the application wants" and "how it's actually done". Most ports are one or two lines: `$this->app->bind(OrderRepository::class, EloquentOrderRepository::class)`.

## What lives here

- **Eloquent models** — `Eloquent/Models/`. They describe the database row, not the Domain entity. They're allowed to use `#[Fillable]`, `#[Hidden]`, casts, observers — all the Eloquent ergonomics. They just don't leak past this layer.
- **Repository implementations** — `Eloquent/Repositories/`. They implement the Domain repository interface and translate in both directions: Domain entity → Eloquent model on the way in, Eloquent row → Domain entity on the way out.
- **Service Providers** — `Providers/`. Bindings, macros, middleware registrations, event listeners — anything that wires the framework up.
- **External clients** — wrappers around third-party SDKs and HTTP APIs. The Stripe client, the S3 client, the Postmark client. Usually one class per provider, sometimes hidden behind an Application port.
- **Adapters** — implementations of Application ports: `SystemClock implements Clock`, `LaravelEventBus implements EventBus`. Thin glue between an Application interface and a concrete tool.

## The dependency rule

Infrastructure imports from **Domain** and **Application** — it has to, because it implements their interfaces. Domain and Application do not import from Infrastructure, ever. Presenter generally shouldn't either; it goes through Application.

The mental test: could you delete every file in `Infrastructure/` tomorrow and rebuild it on a different stack — raw PDO instead of Eloquent, Symfony Mailer instead of Postmark — and have Domain and Application keep compiling without a single edit? If yes, the layer is doing its job.

## Best practices

- **Repositories return Domain entities, not Eloquent models.** The translation happens at the repository boundary. If `findById` returns an `Eloquent\Model`, the abstraction has leaked, and every caller now has to know about it.
- **One adapter per port.** Don't write a `MultiPurposeService` that implements three unrelated interfaces. One port, one adapter; readers and the container both thank you.
- **Adapter names carry `<tool><port>`.** `EloquentOrderRepository`, `StripePaymentGateway`, `PostmarkMailer`, `RedisCache`. The binding line reads like English — *this port is provided by this tool* — and a reader scanning the folder can tell what's wrapping what without opening files.
- **Adapters are thin.** They translate and delegate. They don't decide. If `EloquentOrderRepository::findOpen()` is making business judgments about what counts as "open", that judgment belongs in Domain.
- **External clients hide behind Application ports.** A use case never depends on `StripeClient` directly; it depends on `PaymentGateway`, and `StripePaymentGateway` wraps the SDK. The raw client is an implementation detail of the adapter, not a contract anyone outside Infrastructure should see.
- **Caching is a decorator, not a use-case concern.** Don't sprinkle `Cache::remember(...)` across use cases. If reading orders benefits from caching, write `CachingOrderRepository implements OrderRepository` that wraps the Eloquent version. Both implement the same interface; the use case doesn't know caching exists, and you can swap the cache off in tests by binding the unwrapped version.
- **Implement Application ports here, including `UnitOfWork`.** When Application defines a `UnitOfWork`, this is where it gets a body — wrapping `DB::transaction(...)` in Eloquent, `EntityManager::wrapInTransaction(...)` in Doctrine, or whatever the underlying tool offers. The implementation is the *only* place transactional facade calls are allowed to appear in this codebase.
- **Bindings live in providers, not scattered.** Every `bind`/`singleton` call goes in `AppServiceProvider` (or a dedicated provider for that area). When a port has no binding and the container blows up at runtime, the answer is always "go look in the provider".
- **Integration tests live here.** Unit tests cover Domain and Application with in-memory ports. The tests that actually hit MySQL, that send a real request to Stripe's sandbox, that spin up Redis — those test the *adapters*, and they belong with the adapters.

## Things that go wrong

- **Eloquent models leaking out.** A controller does `User::find($id)` and the repository abstraction is bypassed. Every such shortcut is a future refactor blocker — the day you decide to swap Eloquent for something else, you have to chase down every caller.
- **Leaking infrastructure exceptions upward.** A use case catching `QueryException` or `PDOException` to detect "not found" — those are Eloquent and PDO types, and the use case shouldn't know they exist. Repositories return `?Entity` (and let the caller decide what `null` means) or throw a Domain exception like `OrderNotFound`. The database driver's exceptions stop in this layer.
- **Business logic creeping into repositories.** A *named filter* like `findOpenOrders` or `findEligibleForRefund` is fine — that's selection criteria with a good name. What's not fine is the repo pulling rows out and *evaluating* business decisions in PHP ("is this customer a VIP?", "is this order refundable?") before deciding what to return. The repository's job is to query; the rule lives in Domain. If a query happens to express the same rule a Domain method does, both should be obvious from reading the code — not split such that the repo's WHERE clause is the only place the rule survives.
- **Repository methods that take raw SQL (or query fragments) as parameters.** `findWhere(string $sql)`, `find(['where' => 'status = ?'])`, `query(string $dsl)`. The whole point of the repository is to turn SQL into named operations the rest of the code can speak in; once the SQL leaks back up through the parameters, callers can shape any query they want and the abstraction is gone. If you find yourself wanting that, what you actually want is a few more named methods.
- **N+1 hidden behind repository methods.** A `findById` that lazy-loads relations, called in a loop somewhere upstream. The repository should expose explicit eager-loading variants (`findByIdWithItems`) when callers need them, rather than silently firing dozens of queries that nobody can see at the call site.
- **Eloquent column names dictating Domain field names.** The database column is `usr_eml_addr`; the Domain field is `email`. The translation happens here, not in Domain.
- **Service providers doing real work in `boot()`.** Booting providers should be cheap and idempotent. If `AppServiceProvider::boot()` is hitting the database or making HTTP calls, every artisan command starts to feel slow for no obvious reason.
- **Eloquent observers and global scopes.** They mutate behavior at a distance — `Order::find($id)` quietly runs an observer that fires events, or applies a global scope that filters rows, and none of that is visible at the call site. Use sparingly; prefer explicit calls in the repository.

## A note on Laravel

Eloquent is one of the most popular ORMs out there, but it's also opinionated and reaches into a lot of corners of your code if you let it. Keeping it bottled up in this layer is what makes the rest of the architecture portable — and it's what makes Domain unit tests run in milliseconds instead of seconds.

Prefer `bind` over `singleton` for most things. `singleton` is right for genuinely stateless, expensive-to-build dependencies (an HTTP client with a connection pool, for example); for everything else, a fresh instance per resolution avoids surprising cross-request state.

Be skeptical of Eloquent observers and global scopes. They're easy to add and very hard to find later — three months in, a `User::find($id)` mysteriously runs a notification, or `Order::all()` quietly excludes soft-deleted rows because a scope was registered in some `boot()` method. If you do reach for them, document them at the model and consider whether the same effect could be achieved with an explicit method call in the repository instead.
