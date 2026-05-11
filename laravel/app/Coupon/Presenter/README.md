# Presenter

This is the translation layer between transport and application. Someone fires a `POST /orders`; somewhere down the chain a `PlaceOrder` use case runs; eventually a `201 Created` goes back. Presenter is what stitches those ends together — taking a JSON body, turning it into a `PlaceOrderCommand`, calling the use case, and turning the result into a JSON response with the right status and headers.

The reason it gets its own layer is that HTTP is *one* delivery mechanism. The same `PlaceOrder` use case might also be triggered from an Artisan command, from a queue job that processes uploaded CSVs, from a webhook receiver, from a future GraphQL endpoint. Each transport speaks a different dialect — JSON bodies, command-line flags, message payloads, GraphQL inputs — and each needs its own translator. Putting those translators in a dedicated layer means the use case never has to learn any of those dialects.

## How the rest of the code reaches in

Routes in `routes/api.php` (or `routes/web.php`) map URLs to controller classes via the `Route` facade — `Route::post('/orders', PlaceOrderController::class)`. Controllers are `__invoke`-style: one class, one operation.

How requests get deserialized and responses get serialized is the controller's call. Laravel ships several options — form requests with `$request->validated()`, manual `response()->json(...)`, API resources. This scaffold ships **php-jackson** as the recommended path; see Best practices for the trade-off.

## What lives here

- **HTTP Controllers** — `Http/`. One controller per use case; single `__invoke` method. The controller's job is to call the use case and translate at the boundary.
- **Request DTOs** — the input shape. They describe what the HTTP body or query is *expected* to look like; structural validation happens at deserialization time.
- **Response DTOs** — the output shape. The structure of the JSON response is whatever this DTO looks like; nothing else has a say.
- **CLI commands** — Artisan commands that delegate to a use case. Place them under `Cli/` if added — same shape as a controller, different transport.

## The dependency rule

Presenter imports from **Application** (use cases, commands, results) and may reference **Domain** types where it makes sense — a Response DTO can mention an `OrderId`, for example. It does not import from Infrastructure: no Eloquent models, no repositories, no service providers. Anything Presenter needs from the outside world goes through a use case.

The mental test: could you swap REST for GraphQL — or add a CLI variant of the same operation — by writing new Presenter classes alone, without touching Application? If yes, the layer is doing its job.

## Best practices

- **One controller per use case.** No `OrderController` with `store`, `update`, `destroy`. Instead: `PlaceOrderController`, `CancelOrderController`, `RefundOrderController` — each `readonly`, each `__invoke`. Easier to find, easier to test, easier to evolve independently.
- **Controllers translate, they don't decide.** A controller body should mostly read like "take input, call use case, return output", fit on a screen, and contain zero conditionals about business rules. The moment a controller starts asking "should we?", the question belongs in the use case.
- **Don't reach for the model.** Same rule as everywhere: no `User::find($id)`, no `Eloquent\Builder` in a controller. If the controller needs data, the use case provides it.
- **Don't reach into the container.** No `app(Foo::class)`, no `resolve(...)` from inside a controller. Declare dependencies in the constructor and let autowiring resolve them — the service-locator pattern is the easy way to lose track of what depends on what.
- **Middleware for cross-cutting concerns.** Auth, throttling, CSRF, request logging — those are middleware, applied at the route layer. They don't belong inside controllers.
- **Use php-jackson for HTTP I/O.** `#[JacksonInject]` deserializes the HTTP body or query into a typed DTO; `#[JacksonResponse]` serializes the return value with the status and headers you declare. The win is end-to-end typed contracts — Request DTO in, Response DTO out, no manual `validated()` step in between, no `response()->json(...)` at the end. The trade is giving up Laravel's `validated()`, form requests, and the `response()` helper. The layer's rules don't change if you don't take the trade — controllers can still be plain `Request $request` + manual JSON — but most of the type-safety story disappears.
- **Translate exceptions to HTTP here.** A use case that throws `OrderAlreadyShipped` shouldn't know what HTTP status that maps to. The controller (or a global exception handler) is what turns Domain exceptions into 409s, 422s, 404s.
- **Request DTOs validate structure, not business rules.** "This field is a non-empty string" → DTO. "This email is unique in the system" → use case calling a port. The first is shape; the second is policy.
- **Response DTOs are a public contract.** Once consumers depend on a Response DTO's shape, changing it is a breaking change for them — even when nothing in Domain or Application moved. Add fields, deprecate fields, version the endpoint. Don't reshape silently.
- **API versioning lives at the route + controller layer.** `/v1/orders` and `/v2/orders` may share a use case, or call different ones, or call the same use case with a different mapping into and out of it. That decision belongs here, not in Application — the use case shouldn't know which version is calling it.

## Things that go wrong

- **Business logic in controllers.** "If the cart is empty, return 400" — sounds like HTTP, but "is the cart empty?" is a business question. Push it down: the use case throws `EmptyCart`, the controller maps it to 400.
- **HTTP concerns leaking into the use case.** "If header `X-Tenant` is set, do Y." That's a controller decision, not a use case decision. Anything HTTP-shaped — headers, status codes, request paths, query strings, cookies — stops at the controller boundary. The use case sees a Command, full stop.
- **Multi-method controllers.** `OrderController::store()`, `OrderController::update()`, `OrderController::destroy()` all in one class. Each method is a different use case wearing a fake mustache. Split them.
- **Returning Eloquent models.** It's tempting because it "just works" — Laravel will JSON-serialize the model — but you've now coupled your API contract to your database schema. A Response DTO is the right answer, even if it feels like extra typing.
- **`$request->validated()` carrying business rules.** Laravel's request validation is great for "is this a valid email format?". It's the wrong place for "does this email already exist in the system?" — that one's a use case concern, because it touches state.

## A note on Laravel

Routes in this project live in `routes/*.php` files, registered through the `Route` facade. This is distinct from Symfony's per-controller `#[Route]` attribute approach — Laravel's convention is to keep routes centralized, and we're following that. The trade-off is that the route file becomes the index of the public API; treat it as documentation.
