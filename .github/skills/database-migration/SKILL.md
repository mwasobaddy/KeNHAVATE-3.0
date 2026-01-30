---
name: laravel-database-migrations
description: >-
  Handles Laravel database schema changes. Activates when creating models, 
  modifying tables, or adding columns via migrations. Includes specific checks 
  for development vs. production workflows.
---

# Laravel Database Migrations

## When to Apply

Activate this skill when:
- Creating new database tables.
- Modifying existing table structures (adding, renaming, or dropping columns).
- Managing migration files in a Laravel environment.
- Setting up database indexes or foreign key constraints.

## Migration Strategy (Critical)

> [!IMPORTANT]
> **Before adding columns to an existing table:**
> If a migration for the table already exists, **always ask the user** if they prefer to:
> 1. **Edit the existing migration file** (Ideal for local development to keep things clean).
> 2. **Create a separate migration file** (Required for production environments to prevent data loss).

---

## Documentation

Use `search-docs` for Laravel-specific migration methods, such as `constrained()`, `onDelete()`, or specific column types like `ulid()` or `json()`.

---

## Usage Patterns

### Creating a New Table
When creating a new migration, use the standard `Schema::create` blueprint.

<code-snippet name="New Table Migration" lang="php">
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
</code-snippet>

### Adding Columns (New Migration File)
If the user chooses to create a separate file (Production safe):

<code-snippet name="Add Columns Migration" lang="php">
Schema::table('users', function (Blueprint $table) {
    $table->string('phone_number')->nullable()->after('email');
    $table->boolean('is_active')->default(true);
});
</code-snippet>

### Modifying Columns
Requires the `doctrine/dbal` package for older Laravel versions, though native in Laravel 10+.

<code-snippet name="Modify Column" lang="php">
Schema::table('users', function (Blueprint $table) {
    $table->string('name', 100)->change();
});
</code-snippet>

---

## Common Pitfalls

- **Production Data Loss:** Never edit an existing migration that has already been run in production. This will lead to schema mismatches when colleagues pull your code or when you deploy.
- **Missing Down Methods:** Always ensure the `down()` method accurately reverses the `up()` method (e.g., `dropColumn` if you used `addColumn`).
- **Foreign Key Order:** Ensure the table you are referencing in a foreign key exists before the migration runs (check the timestamps in filenames).
- **Column Placement:** Use `->after('column_name')` on MySQL to keep the database table organized and readable.

---