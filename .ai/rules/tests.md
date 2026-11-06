---
paths:
  - 'tests/**'
---

# Tests

## Deferred Inertia props require loadDeferredProps() in tests
Controllers pass slow public/shared data via Inertia::defer() (v3) so the page can stream with <Deferred> skeletons (Welcome.tsx, Pricing.tsx). Feature tests must not assert deferred props directly on the initial GET: assertInertia returns an AssertableInertia whose props miss them. Chain ->loadDeferredProps(fn ($loaded) => $loaded->where(...)) to resolve the deferred group first (vendor/inertiajs/inertia-laravel/src/Testing/AssertableInertia.php). Symptom when skipped: "Property [plans] does not exist" in Fluent assertions.
