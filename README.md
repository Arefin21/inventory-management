# Product Inventory Management System

A modern Laravel 12 application with Inertia.js and Vue 3 for managing product inventory with full CRUD operations, image uploads, and a polished UI using ShadCN Vue components.

## Features

- ✅ Product CRUD operations (Create, Read, Update, Delete)
- ✅ Image upload with validation
- ✅ Search functionality (by name or SKU)
- ✅ Pagination (10 items per page)
- ✅ Soft delete with confirmation modal
- ✅ Modern UI with ShadCN Vue components
- ✅ Form validation using Laravel Form Requests
- ✅ Clean architecture with Service classes
- ✅ Inertia.js for seamless SPA experience

## Installation Steps

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm
- SQLite (or MySQL/PostgreSQL)

### Step 1: Clone the Repository

```bash
git clone https://github.com/Arefin21/inventory-management.git
cd inventory-management
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

### Step 3: Install JavaScript Dependencies

```bash
npm install
```

### Step 4: Environment Configuration

Copy the `.env.example` file to `.env` (if not already present):

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

### Step 5: Database Setup

The project uses SQLite by default. Ensure the database file exists:

```bash
touch database/database.sqlite
```

Or update `.env` to use MySQL/PostgreSQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_management
DB_USERNAME=root
DB_PASSWORD=
```

### Step 6: Run Migrations

```bash
php artisan migrate
```

### Step 7: Seed the Database (Optional)

```bash
php artisan db:seed
```

This will create sample products using the ProductSeeder.

### Step 8: Create Storage Link

Create a symbolic link for public storage (required for image uploads):

```bash
php artisan storage:link
```

### Step 9: Build Assets

For development:

```bash
npm run dev
```

For production:

```bash
npm run build
```

### Step 10: Start the Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.


## Technical Questions & Answers

### 1. Laravel 12 & Modern PHP: Dependency Injection vs Facades

**What is the difference between Dependency Injection and using a Facade in Laravel?**

**Dependency Injection (DI):**
- Explicitly inject dependencies through constructor or method parameters
- Makes dependencies clear and testable
- Example: `public function __construct(ProductService $productService)`
- Type-hinted, IDE-friendly, and allows for easy mocking in tests

**Facades:**
- Static interface to classes in the service container
- Provides convenient access to services without explicit injection
- Example: `Storage::disk('public')->put(...)`
- Less explicit, but more convenient for quick access

**When to prefer Dependency Injection:**

1. **Testability**: DI makes it easier to mock dependencies in unit tests
2. **Explicit Dependencies**: Makes it clear what a class depends on
3. **Service Classes**: When building reusable service classes (like our `ProductService`)
4. **Type Safety**: Better IDE autocomplete and type checking
5. **Loose Coupling**: Easier to swap implementations

**Example from this project:**

```php
// Using Dependency Injection (Preferred)
class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}
    
    public function store(StoreProductRequest $request)
    {
        $validated['image'] = $this->productService->handleImageUpload(
            $request->file('image')
        );
    }
}

// Using Facade (Less preferred for services)
public function store(StoreProductRequest $request)
{
    $validated['image'] = Storage::disk('public')
        ->put('products', $request->file('image'));
}
```

In this project, we use DI for `ProductService` because it encapsulates file upload logic, making the controller cleaner and the service easily testable.

---

### 2. Performance: Optimizing Search with 10,000 Products

**How would you optimize the search query to ensure the page loads quickly with 10,000 products?**

**Current Implementation:**
```php
$query->where(function ($q) use ($search) {
    $q->where('name', 'like', "%{$search}%")
      ->orWhere('sku', 'like', "%{$search}%");
});
```

**Optimization Strategies:**

1. **Database Indexing:**
   ```php
   // In migration
   $table->index('name');
   $table->index('sku');
   // Or composite index
   $table->index(['name', 'sku']);
   ```

2. **Full-Text Search (MySQL/PostgreSQL):**
   ```php
   // For MySQL
   $query->whereRaw("MATCH(name, sku) AGAINST(? IN BOOLEAN MODE)", [$search]);
   
   // For PostgreSQL
   $query->whereRaw("to_tsvector('english', name || ' ' || sku) @@ plainto_tsquery('english', ?)", [$search]);
   ```

3. **Limit Search Length:**
   ```php
   if (strlen($search) >= 3) {
       // Only search if query is 3+ characters
       $query->where(function ($q) use ($search) {
           $q->where('name', 'like', "%{$search}%")
             ->orWhere('sku', 'like', "%{$search}%");
       });
   }
   ```

4. **Caching Results:**
   ```php
   $cacheKey = "products.search.{$search}";
   $products = Cache::remember($cacheKey, 300, function () use ($query) {
       return $query->paginate(10);
   });
   ```

5. **Eager Loading (if relationships exist):**
   ```php
   $query->with('category'); // Prevent N+1 queries
   ```

6. **Pagination Optimization:**
   ```php
   // Use cursor pagination for large datasets
   $products = $query->cursorPaginate(10);
   ```

**Recommended Implementation:**
```php
public function index(Request $request): Response
{
    $query = Product::query();
    
    if ($request->has('search') && strlen($request->search) >= 3) {
        $search = $request->search;
        // Use full-text search if available, fallback to LIKE
        if (config('database.default') === 'mysql') {
            $query->whereRaw("MATCH(name, sku) AGAINST(? IN BOOLEAN MODE)", [$search]);
        } else {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "{$search}%")  // Prefix search is faster
                  ->orWhere('sku', 'like', "{$search}%");
            });
        }
    }
    
    $products = $query->latest()->paginate(10)->withQueryString();
    // ... rest of code
}
```

---

### 3. Security: CSRF Protection with Inertia.js

**How does Laravel's CSRF protection work with Inertia.js, and why is it important for form submissions?**

**How CSRF Works with Inertia.js:**

1. **CSRF Token Generation:**
   - Laravel generates a CSRF token on each request
   - Inertia automatically includes this token in the page props via the `HandleInertiaRequests` middleware

2. **Token Sharing:**
   ```php
   // In HandleInertiaRequests middleware
   public function share(Request $request): array
   {
       return [
           'csrf_token' => csrf_token(),
           // ... other shared data
       ];
   }
   ```

3. **Automatic Token Inclusion:**
   - Inertia.js automatically includes the CSRF token in all POST/PUT/DELETE requests
   - The token is sent via the `X-CSRF-TOKEN` header or `_token` field

4. **Laravel Verification:**
   - Laravel's `VerifyCsrfToken` middleware validates the token on each state-changing request
   - If invalid, Laravel returns a 419 error

**Why It's Important:**

1. **Prevents Cross-Site Request Forgery (CSRF) Attacks:**
   - Without CSRF protection, malicious sites could make requests on behalf of authenticated users
   - Example: A malicious site could delete products if the user is logged in

2. **Maintains Security Standards:**
   - CSRF tokens ensure requests originate from your application
   - Protects against unauthorized state changes

3. **Seamless Integration:**
   - Inertia.js handles CSRF tokens automatically, so developers don't need to manually add them to forms
   - Works transparently with Laravel's built-in CSRF protection

**Example in this project:**

```javascript
// In Create.vue - Inertia automatically includes CSRF token
form.post(route('products.store'), {
    forceFormData: true,
    // CSRF token is automatically included
});
```

Laravel verifies the token server-side:
```php
// Automatically handled by VerifyCsrfToken middleware
// No additional code needed in controllers
```

---

### 4. Code Structure: N+1 Query Problem

**Explain the N+1 query problem. If each Product belonged to a Category, how would you prevent N+1 issues when loading the list?**

**What is the N+1 Query Problem?**

The N+1 query problem occurs when:
1. You execute 1 query to fetch a collection (e.g., 10 products)
2. Then execute N additional queries (one per item) to fetch related data (e.g., categories)
3. Result: 1 + N queries instead of 2 queries (1 for products, 1 for all categories)

**Example of N+1 Problem:**

```php
// BAD: N+1 Query Problem
$products = Product::all(); // 1 query

foreach ($products as $product) {
    echo $product->category->name; // N queries (one per product)
}
// Total: 1 + N queries (e.g., 1 + 10 = 11 queries)
```

**Solution: Eager Loading**

```php
// GOOD: Eager Loading
$products = Product::with('category')->get(); // 2 queries total
// 1 query for products
// 1 query for all categories (WHERE id IN (...))

foreach ($products as $product) {
    echo $product->category->name; // No additional queries
}
// Total: 2 queries
```

**Implementation in this Project:**

If we had a Category relationship:

```php
// In Product Model
public function category()
{
    return $this->belongsTo(Category::class);
}

// In ProductController
public function index(Request $request): Response
{
    $query = Product::with('category'); // Eager load category
    
    // ... search logic
    
    $products = $query->latest()->paginate(10);
    
    return Inertia::render('Products/Index', [
        'products' => $products,
    ]);
}
```

**Additional Optimizations:**

1. **Select Specific Columns:**
   ```php
   Product::with('category:id,name')->select('id', 'name', 'category_id')->get();
   ```

2. **Nested Eager Loading:**
   ```php
   Product::with('category.subcategory')->get();
   ```

3. **Conditional Eager Loading:**
   ```php
   $query->when($request->has('with_category'), function ($q) {
       $q->with('category');
   });
   ```

4. **Lazy Eager Loading (if needed later):**
   ```php
   $products->load('category');
   ```

**Best Practice:**
Always use eager loading when you know you'll access relationships in your views. Laravel's query builder makes it easy with the `with()` method. This prevents the N+1 query problem and significantly improves performance, especially when dealing with large datasets.

---

## Technologies Used

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Vue 3, Inertia.js
- **UI Components:** ShadCN Vue
- **Styling:** Tailwind CSS 4
- **Database:** SQLite (configurable to MySQL/PostgreSQL)

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

