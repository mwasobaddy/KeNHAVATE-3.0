---
name: typescript-type-safety
description: >-
  Enforces strict typing and eliminates the use of 'any' in TypeScript projects. 
  Activates when writing TypeScript/TSX, defining interfaces, handling API responses, 
  managing React/Inertia state, or refactoring legacy code to resolve type-related bugs.
---

# TypeScript Type Safety

## When to Apply

Activate this skill when:

- Defining component props or application state.
- Handling data from external APIs or dynamic JSON payloads.
- Refactoring code to resolve `no-explicit-any` ESLint warnings.
- Implementing type guards or narrowing logic for safer data handling.
- Working with generic object structures where the shape is not immediately known.

## Documentation

Use `search-docs` for detailed TypeScript utility types, narrowing strategies, and advanced generics.

## Basic Usage

### Avoiding `any`

Using `any` effectively disables the TypeScript compiler for that variable. It hides potential bugs, reduces editor IntelliSense, and leads to runtime errors that could have been caught during development.

### Preferred Type Alternatives

- **Specific Interfaces:** Always the first choice for known data shapes.
- **Record<string, unknown>:** Use for generic objects (e.g., form data) where keys are strings but values vary.
- **unknown:** Use for truly unknown values that require narrowing before use.

### Refactoring Props

Replace unconstrained `any` types in component definitions with structured interfaces.

<code-snippet name="Refactoring Component Props" lang="tsx">

// Good: Specific or Record-based
interface Props {
  value: Record<string, unknown>; 
  onBack: () => void;
}

</code-snippet>

## Practical Patterns

### Managing Form State

When working with form data in React components (e.g., Inertia.js forms), avoid initializing state with `any`.

<code-snippet name="Safer Form State" lang="tsx">

// Avoid: useState<any>({})
const [formData, setFormData] = useState<Record<string, unknown>>({});

// Accessing values safely via casting
const displayName = String(formData.name || '');

</code-snippet>

## Type Strategy Comparison

Use specific narrowing and utility types to maintain safety:

| Use | Instead of |
|-----|------------|
| `Record<string, unknown>` | `any` (for generic objects) |
| `unknown[]` | `any[]` (for generic arrays) |
| `'active' \| 'inactive'` | `string` (for specific sets) |
| `String(value)` | `value as any` (for string operations) |

## Advanced Narrowing

### Using `unknown` and Type Guards

Use `unknown` when the type is dynamic. Narrow it using type guards to ensure type safety.



<code-snippet name="Type Guard Pattern" lang="typescript">

function isString(value: unknown): value is string {
  return typeof value === 'string';
}

if (isString(someValue)) {
  // TypeScript safely allows string methods here
  console.log(someValue.toUpperCase());
}

</code-snippet>

## Better Approach: Specific Interfaces

For the highest level of safety, always prefer a dedicated interface over generic Records when the structure is predictable.

<code-snippet name="Defining Form Interfaces" lang="tsx">

interface UserFormData {
  firstName: string;
  lastName: string;
  email: string;
}

const [formData, setFormData] = useState<UserFormData>({
  firstName: '',
  lastName: '',
  email: '',
});

</code-snippet>

## Common Pitfalls

- **Accessing Properties on `unknown`:** TypeScript will block direct access to properties on `unknown`. You must narrow the type first using `typeof` or a custom guard.
- **Type Errors with `Record`:** `Record<string, unknown>` can cause errors when assigning to strictly typed string variables. **Fix:** Use `String()` casting for string contexts.
- **Overusing `Record`:** Do not use generic records for everything; if an API returns a consistent shape, document it with an `interface`.
- **Casting vs. Narrowing:** Avoid `value as TargetType` (assertion) when you can use `if (check)` (narrowing). Narrowing is inherently safer.

## References
- [TypeScript no-explicit-any rule](https://typescript-eslint.io/rules/no-explicit-any/)
- [TypeScript Handbook: Any](https://www.typescriptlang.org/docs/handbook/basic-types.html#any)
- [TypeScript Handbook: Unknown](https://www.typescriptlang.org/docs/handbook/release-notes/typescript-3-0.html#new-unknown-top-type)
