<laravel-boost-guidelines>
=== .ai/review-controllers rules ===

# Controller Refactoring Guide

## Overview

This guide provides a systematic approach to refactoring Laravel controllers following best practices and SOLID principles. Use this as a checklist when reviewing and refactoring each controller in the application.

---

## Core Principles

### Controllers Should ONLY:

1. ✅ Handle HTTP requests and responses
2. ✅ Validate incoming data
3. ✅ Delegate business logic to services
4. ✅ Return views/JSON responses
5. ✅ Handle redirects with appropriate messages

### Controllers Should NEVER:

1. ❌ Contain complex business logic
2. ❌ Directly manipulate multiple models
3. ❌ Handle database transactions
4. ❌ Contain validation logic in closures
5. ❌ Have methods longer than 20-30 lines
6. ❌ Directly send emails/notifications (delegate to services)

---

## Refactoring Checklist

### Step 1: Identify Code Smells

Review each controller method for these red flags:

- [ ] **Fat Methods**: Methods with 30+ lines of code
- [ ] **Database Transactions**: `DB::transaction()` or `DB::beginTransaction()` in controller
- [ ] **Multiple Model Operations**: Creating/updating 3+ models in one method
- [ ] **Complex Validation**: Validation rules with closures or custom logic
- [ ] **Business Logic**: Calculations, data transformations, complex conditionals
- [ ] **Direct Email/Notifications**: Sending emails directly in controller
- [ ] **Query Builder Usage**: Raw queries or complex Eloquent operations
- [ ] **Error Handling**: Try-catch blocks with complex error handling logic

### Step 2: Extract to Appropriate Layers

Based on what you find, extract code to:

#### A. Service Classes (`app/Services/`)

**When to use:**
- Complex business operations involving multiple steps
- Operations that coordinate multiple models
- Database transactions
- Third-party API interactions
- Complex calculations or data transformations

**Example:**
```php
// app/Services/TenantCreationService.php
namespace App\Services;

class TenantCreationService
{
    public function createTenant(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            // Complex tenant creation logic
        });
    }
}
```

#### B. Actions (`app/Actions/`)

**When to use:**
- Single, focused operations (Single Responsibility Principle)
- Reusable operations across multiple contexts
- Simple, atomic business operations

**Example:**
```php
// app/Actions/SendWelcomeEmail.php
namespace App\Actions;

class SendWelcomeEmail
{
    public function execute(User $user, string $password): void
    {
        $user->notify(new WelcomeCredentials($password));
    }
}
```

#### C. Custom Request Classes (`app/Http/Requests/`)

**When to use:**
- Complex validation rules
- Validation with closures
- Authorization logic
- Conditional validation

**Example:**
```php
// app/Http/Requests/StoreSubscriptionRequest.php
namespace App\Http\Requests;

class StoreSubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', new UniqueTenantDomain()],
            // ... other rules
        ];
    }
    
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Complex validation logic
        });
    }
}
```

#### D. Custom Validation Rules (`app/Rules/`)

**When to use:**
- Reusable validation logic
- Complex validation that doesn't fit in a rule string
- Database-dependent validation

**Example:**
```php
// app/Rules/UniqueTenantDomain.php
namespace App\Rules;

class UniqueTenantDomain implements Rule
{
    public function passes($attribute, $value): bool
    {
        // Validation logic
    }
}
```

#### E. Model Methods

**When to use:**
- Data manipulation specific to that model
- Accessors/Mutators
- Query scopes
- Relationships
- Simple helper methods about the model's state

**Example:**
```php
// In Tenant.php model
public function isActive(): bool
{
    return $this->subscription_status === 'active';
}

public function scopeActive($query)
{
    return $query->where('subscription_status', 'active');
}
```

#### F. Traits (`app/Traits/`)

**When to use:**
- Shared functionality across multiple models
- Reusable behavior patterns
- Cross-cutting concerns

**Example:**
```php
// app/Traits/HasSubscription.php
namespace App\Traits;

trait HasSubscription
{
    public function isSubscriptionActive(): bool { }
    public function renewSubscription(): void { }
    public function cancelSubscription(): void { }
}
```

---

## Step-by-Step Refactoring Process

### For Each Controller:

#### 1. **Analyze Current State**

```bash

# Review the controller

- Count lines per method
- Identify dependencies
- List all operations performed
- Note any external service calls
```

#### 2. **Plan the Refactoring**

```markdown
Create a refactoring plan:
- [ ] What needs to move to services?
- [ ] What validation needs extraction?
- [ ] What can move to model methods?
- [ ] What traits could be created?
- [ ] What actions are needed?
```

#### 3. **Create New Files**

```bash

# Generate necessary files

php artisan make:service TenantCreationService
php artisan make:request StoreSubscriptionRequest
php artisan make:rule UniqueTenantDomain
```

#### 4. **Move Code Systematically**

**Priority Order:**
1. Extract validation → Request classes or Rules
2. Extract business logic → Services or Actions
3. Extract model operations → Model methods
4. Extract shared behavior → Traits
5. Clean up controller → Keep only HTTP concerns

#### 5. **Update Controller**

**Before:**
```php
public function store(Request $request)
{
    $validated = $request->validate([...]);
    
    DB::beginTransaction();
    try {
        $tenant = Tenant::create([...]);
        $user = User::create([...]);
        $user->notify(new WelcomeEmail());
        DB::commit();
        return redirect('/success');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => $e->getMessage()]);
    }
}
```

**After:**
```php
public function store(StoreSubscriptionRequest $request)
{
    try {
        $tenant = $this->tenantService->createTenant(
            $request->validated()
        );
        
        return redirect('/success')
            ->with('success', 'Account created!');
    } catch (\Exception $e) {
        Log::error('Tenant creation failed', [
            'error' => $e->getMessage()
        ]);
        
        return back()
            ->withInput()
            ->withErrors(['error' => 'Failed to create account.']);
    }
}
```

---

## Controller Method Templates

### Index Method

```php
public function index(Request $request)
{
    $items = $this->service->getPaginated(
        $request->query('filter'),
        $request->query('sort')
    );
    
    return view('items.index', compact('items'));
}
```

### Show Method

```php
public function show(Model $model)
{
    $this->authorize('view', $model);
    
    return view('items.show', compact('model'));
}
```

### Create Method

```php
public function create()
{
    $options = $this->service->getFormOptions();
    
    return view('items.create', compact('options'));
}
```

### Store Method

```php
public function store(StoreModelRequest $request)
{
    try {
        $model = $this->service->create($request->validated());
        
        return redirect()
            ->route('items.show', $model)
            ->with('success', 'Created successfully!');
    } catch (\Exception $e) {
        Log::error('Creation failed', ['error' => $e->getMessage()]);
        
        return back()
            ->withInput()
            ->withErrors(['error' => 'Creation failed.']);
    }
}
```

### Update Method

```php
public function update(UpdateModelRequest $request, Model $model)
{
    $this->authorize('update', $model);
    
    try {
        $this->service->update($model, $request->validated());
        
        return redirect()
            ->route('items.show', $model)
            ->with('success', 'Updated successfully!');
    } catch (\Exception $e) {
        Log::error('Update failed', ['error' => $e->getMessage()]);
        
        return back()
            ->withInput()
            ->withErrors(['error' => 'Update failed.']);
    }
}
```

### Destroy Method

```php
public function destroy(Model $model)
{
    $this->authorize('delete', $model);
    
    try {
        $this->service->delete($model);
        
        return redirect()
            ->route('items.index')
            ->with('success', 'Deleted successfully!');
    } catch (\Exception $e) {
        Log::error('Deletion failed', ['error' => $e->getMessage()]);
        
        return back()
            ->withErrors(['error' => 'Deletion failed.']);
    }
}
```

---

## Common Refactoring Patterns

### Pattern 1: Multi-Model Creation

**Before:**
```php
public function store(Request $request)
{
    $user = User::create($request->only('name', 'email'));
    $profile = Profile::create(['user_id' => $user->id, ...]);
    $settings = Settings::create(['user_id' => $user->id, ...]);
    // More operations...
}
```

**After:**
```php
// Controller
public function store(StoreUserRequest $request)
{
    $user = $this->userService->createWithProfile($request->validated());
    return redirect()->route('users.show', $user);
}

// Service
class UserCreationService
{
    public function createWithProfile(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create($data);
            $this->createProfile($user, $data);
            $this->createSettings($user);
            return $user;
        });
    }
}
```

### Pattern 2: Complex Validation

**Before:**
```php
$request->validate([
    'domain' => [
        'required',
        function ($attribute, $value, $fail) {
            if (Domain::where('name', $value)->exists()) {
                $fail('Domain taken.');
            }
        },
    ],
]);
```

**After:**
```php
// Controller
$request->validate([
    'domain' => ['required', new UniqueDomain()],
]);

// Rule
class UniqueDomain implements Rule
{
    public function passes($attribute, $value): bool
    {
        return !Domain::where('name', $value)->exists();
    }
}
```

### Pattern 3: API Interactions

**Before:**
```php
public function process(Request $request)
{
    $response = Http::post('https://api.example.com/endpoint', [...]);
    $data = $response->json();
    // Process data...
}
```

**After:**
```php
// Controller
public function process(Request $request)
{
    $result = $this->apiService->processData($request->validated());
    return response()->json($result);
}

// Service
class ExternalApiService
{
    public function processData(array $data): array
    {
        $response = Http::post($this->endpoint, $data);
        return $this->transformResponse($response->json());
    }
}
```

---

## Testing Strategy

After refactoring, ensure you have tests for:

### Unit Tests

- [ ] Service classes
- [ ] Action classes
- [ ] Custom validation rules
- [ ] Model methods

### Feature Tests

- [ ] Controller endpoints
- [ ] End-to-end workflows
- [ ] Authentication/Authorization

### Example Service Test

```php
class TenantCreationServiceTest extends TestCase
{
    /** @test */
    public function it_creates_tenant_with_all_resources()
    {
        $service = new TenantCreationService();
        
        $tenant = $service->createTenant([
            'company_name' => 'Test Company',
            'domain' => 'test',
            // ...
        ]);
        
        $this->assertDatabaseHas('tenants', [
            'company_name' => 'Test Company'
        ]);
        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id
        ]);
    }
}
```

---

## Quality Metrics

After refactoring each controller, verify:

- [ ] **Method Length**: No method exceeds 25 lines
- [ ] **Cyclomatic Complexity**: Each method has complexity < 10
- [ ] **Dependencies**: Controller has 3 or fewer injected dependencies
- [ ] **Single Responsibility**: Each method does ONE thing
- [ ] **Testability**: Each method can be easily unit tested
- [ ] **Readability**: Code is self-documenting

---

## File Organization

Maintain this structure:

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   └── V1/
│   │   ├── Auth/
│   │   └── [FeatureControllers].php
│   └── Requests/
│       ├── [Feature]/
│       │   ├── Store[Feature]Request.php
│       │   └── Update[Feature]Request.php
│       └── [OtherRequests].php
├── Services/
│   ├── [Feature]/
│   │   └── [Feature]Service.php
│   └── [OtherServices].php
├── Actions/
│   └── [Feature]/
│       └── [Action].php
├── Rules/
│   └── [ValidationRule].php
├── Traits/
│   └── [BehaviorTrait].php
└── Models/
    └── [Model].php
```

---

## Common Mistakes to Avoid

1. ❌ **Over-engineering**: Don't create services for simple CRUD
2. ❌ **Service Layer Bloat**: Keep services focused on specific domains
3. ❌ **Circular Dependencies**: Services shouldn't depend on controllers
4. ❌ **Inconsistent Patterns**: Use the same pattern across similar features
5. ❌ **Premature Optimization**: Refactor when you see patterns, not before
6. ❌ **Ignoring Type Hints**: Always use return types and parameter types
7. ❌ **Poor Naming**: Use descriptive names that reveal intent

---

## Refactoring Priority

Prioritize controllers in this order:

1. **High Priority**: Controllers with security implications (Auth, Payment, User Management)
2. **Medium Priority**: Core business logic controllers (Orders, Subscriptions, Tenant Management)
3. **Low Priority**: Simple CRUD controllers with minimal logic
4. **Last**: Admin/Dashboard controllers with mostly read operations

---

## Review Checklist

Before marking a controller as "refactored," verify:

- [ ] All business logic moved to appropriate services/actions
- [ ] Complex validation extracted to Request classes or Rules
- [ ] Model-specific logic moved to models
- [ ] Shared behavior extracted to traits
- [ ] Controller methods are thin and readable
- [ ] Proper error handling and logging implemented
- [ ] Type hints added to all methods
- [ ] Tests updated or created
- [ ] Documentation updated
- [ ] Code review completed by peer

---

## Example: Complete Refactoring

### Original Controller (Bad)

```php
class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:card,paypal',
        ]);

        DB::beginTransaction();
        try {
            $total = 0;
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if ($product->stock < $item['quantity']) {
                    throw new \Exception('Insufficient stock');
                }
                $total += $product->price * $item['quantity'];
            }

            $order = Order::create([
                'user_id' => auth()->id(),
                'total' => $total,
                'status' => 'pending',
            ]);

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);
                $product->decrement('stock', $item['quantity']);
            }

            if ($request->payment_method === 'card') {
                $payment = Stripe::charge([
                    'amount' => $total * 100,
                    'currency' => 'usd',
                    'source' => $request->token,
                ]);
            }

            Mail::to(auth()->user())->send(new OrderConfirmation($order));

            DB::commit();
            return redirect()->route('orders.show', $order);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
```

### Refactored Version (Good)

**Controller:**
```php
class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function store(StoreOrderRequest $request)
    {
        try {
            $order = $this->orderService->createOrder(
                auth()->user(),
                $request->validated()
            );

            return redirect()
                ->route('orders.show', $order)
                ->with('success', 'Order placed successfully!');
        } catch (InsufficientStockException $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Some items are out of stock.']);
        } catch (PaymentFailedException $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Payment failed. Please try again.']);
        } catch (\Exception $e) {
            Log::error('Order creation failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create order.']);
        }
    }
}
```

**Request:**
```php
class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => ['required', 'integer', 'min:1', new SufficientStock()],
            'payment_method' => 'required|in:card,paypal',
            'token' => 'required_if:payment_method,card',
        ];
    }
}
```

**Service:**
```php
class OrderService
{
    public function __construct(
        private PaymentService $paymentService,
        private NotificationService $notificationService
    ) {}

    public function createOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $total = $this->calculateTotal($data['items']);

            $order = Order::create([
                'user_id' => $user->id,
                'total' => $total,
                'status' => 'pending',
            ]);

            $this->createOrderItems($order, $data['items']);
            $this->updateProductStock($data['items']);

            $this->paymentService->processPayment(
                $order,
                $data['payment_method'],
                $data['token'] ?? null
            );

            $this->notificationService->sendOrderConfirmation($order);

            return $order;
        });
    }

    protected function calculateTotal(array $items): float
    {
        return collect($items)->sum(function ($item) {
            $product = Product::find($item['product_id']);
            return $product->price * $item['quantity'];
        });
    }

    protected function createOrderItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $product->price,
            ]);
        }
    }

    protected function updateProductStock(array $items): void
    {
        foreach ($items as $item) {
            Product::find($item['product_id'])
                ->decrement('stock', $item['quantity']);
        }
    }
}
```

---

## Conclusion

Use this guide as your refactoring blueprint. Work through controllers systematically, applying these patterns consistently. The goal is clean, maintainable code that follows Laravel best practices and SOLID principles.

Remember: **Refactor incrementally, test thoroughly, and commit frequently.**

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.7
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/socialite (SOCIALITE) - v5
- laravel/wayfinder (WAYFINDER) - v0
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/react (INERTIA) - v2
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4
- @laravel/vite-plugin-wayfinder (WAYFINDER) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `wayfinder-development` — Activates whenever referencing backend routes in frontend components. Use when importing from @/actions or @/routes, calling Laravel routes from TypeScript, or working with Wayfinder route functions.
- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `inertia-react-development` — Develops Inertia.js v2 React client-side applications. Activates when creating React pages, forms, or navigation; using &lt;Link&gt;, &lt;Form&gt;, useForm, or router; working with deferred props, prefetching, or polling; or when user mentions React with Inertia, React pages, React forms, or React navigation.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.
- `developing-with-fortify` — Laravel Fortify headless authentication backend development. Activate when implementing authentication features including login, registration, password reset, email verification, two-factor authentication (2FA/TOTP), profile updates, headless auth, authentication scaffolding, or auth guards in Laravel applications.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

=== inertia-laravel/v2 rules ===

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scrolling (merging props + `WhenVisible`), lazy loading on scroll, polling, prefetching.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== wayfinder/core rules ===

# Laravel Wayfinder

Wayfinder generates TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

- IMPORTANT: Activate `wayfinder-development` skill whenever referencing backend routes in frontend components.
- Invokable Controllers: `import StorePost from '@/actions/.../StorePostController'; StorePost()`.
- Parameter Binding: Detects route keys (`{post:slug}`) — `show({ slug: "my-post" })`.
- Query Merging: `show(1, { mergeQuery: { page: 2, sort: null } })` merges with current URL, `null` removes params.
- Inertia: Use `.form()` with `<Form>` component or `form.submit(store())` with useForm.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.

=== laravel/fortify rules ===

# Laravel Fortify

- Fortify is a headless authentication backend that provides authentication routes and controllers for Laravel applications.
- IMPORTANT: Always use the `search-docs` tool for detailed Laravel Fortify patterns and documentation.
- IMPORTANT: Activate `developing-with-fortify` skill when working with Fortify authentication features.

=== spatie/boost-spatie-guidelines rules ===

# Laravel & PHP Guidelines for AI Code Assistants

This file contains Laravel and PHP coding standards optimized for AI code assistants like Claude Code, GitHub Copilot, and Cursor. These guidelines are derived from Spatie's comprehensive Laravel & PHP standards.

## Core Laravel Principle

**Follow Laravel conventions first.** If Laravel has a documented way to do something, use it. Only deviate when you have a clear justification.

## PHP Standards

- Follow PSR-1, PSR-2, and PSR-12
- Use camelCase for non-public-facing strings
- Use short nullable notation: `?string` not `string|null`
- Always specify `void` return types when methods return nothing

## Class Structure

- Use typed properties, not docblocks:
- Constructor property promotion when all properties can be promoted:
- One trait per line:

## Type Declarations & Docblocks

- Use typed properties over docblocks
- Specify return types including `void`
- Use short nullable syntax: `?Type` not `Type|null`
- Document iterables with generics:

  ```php
  /** @return Collection<int, User> */
  public function getUsers(): Collection
  ```

### Docblock Rules

- Don't use docblocks for fully type-hinted methods (unless description needed)
- **Always import classnames in docblocks** - never use fully qualified names:

  ```php
  use \Spatie\Url\Url;
  /** @return Url */
  ```

- Use one-line docblocks when possible: `/** @var string */`
- Most common type should be first in multi-type docblocks:

  ```php
  /** @var Collection|SomeWeirdVendor\Collection */
  ```

- If one parameter needs docblock, add docblocks for all parameters
- For iterables, always specify key and value types:

  ```php
  /**
   * @param array<int, MyObject> $myArray
   * @param int $typedArgument
   */
  function someFunction(array $myArray, int $typedArgument) {}
  ```

- Use array shape notation for fixed keys, put each key on it's own line:

  ```php
  /** @return array{
     first: SomeClass,
     second: SomeClass
  } */
  ```

## Control Flow

- **Happy path last**: Handle error conditions first, success case last
- **Avoid else**: Use early returns instead of nested conditions
- **Separate conditions**: Prefer multiple if statements over compound conditions
- **Always use curly brackets** even for single statements
- **Ternary operators**: Each part on own line unless very short

```php
// Happy path last
if (! $user) {
    return null;
}

if (! $user->isActive()) {
    return null;
}

// Process active user...

// Short ternary
$name = $isFoo ? 'foo' : 'bar';

// Multi-line ternary
$result = $object instanceof Model ?
    $object->name :
    'A default value';

// Ternary instead of else
$condition
    ? $this->doSomething()
    : $this->doSomethingElse();
```

## Laravel Conventions

### Routes

- URLs: kebab-case (`/open-source`)
- Route names: camelCase (`->name('openSource')`)
- Parameters: camelCase (`{userId}`)
- Use tuple notation: `[Controller::class, 'method']`

### Controllers

- Plural resource names (`PostsController`)
- Stick to CRUD methods (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`)
- Extract new controllers for non-CRUD actions

### Configuration

- Files: kebab-case (`pdf-generator.php`)
- Keys: snake_case (`chrome_path`)
- Add service configs to `config/services.php`, don't create new files
- Use `config()` helper, avoid `env()` outside config files

### Artisan Commands

- Names: kebab-case (`delete-old-records`)
- Always provide feedback (`$this->comment('All ok!')`)
- Show progress for loops, summary at end
- Put output BEFORE processing item (easier debugging):

  ```php
  $items->each(function(Item $item) {
      $this->info("Processing item id `{$item->id}`...");
      $this->processItem($item);
  });

  $this->comment("Processed {$items->count()} items.");
  ```

## Strings & Formatting

- **String interpolation** over concatenation:

## Enums

- Use PascalCase for enum values:

## Comments

- **Avoid comments** - write expressive code instead
- When needed, use proper formatting:

  ```php
  // Single line with space after //

  /*
   * Multi-line blocks start with single *
   */
  ```

- Refactor comments into descriptive function names

## Whitespace

- Add blank lines between statements for readability
- Exception: sequences of equivalent single-line operations
- No extra empty lines between `{}` brackets
- Let code "breathe" - avoid cramped formatting

## Validation

- Use array notation for multiple rules (easier for custom rule classes):

  ```php
  public function rules() {
      return [
          'email' => ['required', 'email'],
      ];
  }
  ```

- Custom validation rules use snake_case:

  ```php
  Validator::extend('organisation_type', function ($attribute, $value) {
      return OrganisationType::isValid($value);
  });
  ```

## Blade Templates

- Indent with 4 spaces
- No spaces after control structures:

  ```blade
  @if($condition)
      Something
  @endif
  ```

## Authorization

- Policies use camelCase: `Gate::define('editPost', ...)`
- Use CRUD words, but `view` instead of `show`

## Translations

- Use `__()` function over `@lang`:

## API Routing

- Use plural resource names: `/errors`
- Use kebab-case: `/error-occurrences`
- Limit deep nesting for simplicity:
  ```
  /error-occurrences/1
  /errors/1/occurrences
  ```

## Testing

- Keep test classes in same file when possible
- Use descriptive test method names
- Follow the arrange-act-assert pattern

## Quick Reference

### Naming Conventions

- **Classes**: PascalCase (`UserController`, `OrderStatus`)
- **Methods/Variables**: camelCase (`getUserName`, `$firstName`)
- **Routes**: kebab-case (`/open-source`, `/user-profile`)
- **Config files**: kebab-case (`pdf-generator.php`)
- **Config keys**: snake_case (`chrome_path`)
- **Artisan commands**: kebab-case (`php artisan delete-old-records`)

### File Structure

- Controllers: plural resource name + `Controller` (`PostsController`)
- Views: camelCase (`openSource.blade.php`)
- Jobs: action-based (`CreateUser`, `SendEmailNotification`)
- Events: tense-based (`UserRegistering`, `UserRegistered`)
- Listeners: action + `Listener` suffix (`SendInvitationMailListener`)
- Commands: action + `Command` suffix (`PublishScheduledPostsCommand`)
- Mailables: purpose + `Mail` suffix (`AccountActivatedMail`)
- Resources/Transformers: plural + `Resource`/`Transformer` (`UsersResource`)
- Enums: descriptive name, no prefix (`OrderStatus`, `BookingType`)

### Migrations

- do not write down methods in migrations, only up methods

### Code Quality Reminders

#### PHP

- Use typed properties over docblocks
- Prefer early returns over nested if/else
- Use constructor property promotion when all properties can be promoted
- Avoid `else` statements when possible
- Use string interpolation over concatenation
- Always use curly braces for control structures

---

*These guidelines are maintained by [Spatie](https://spatie.be/guidelines) and optimized for AI code assistants.*
</laravel-boost-guidelines>
