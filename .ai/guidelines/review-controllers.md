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