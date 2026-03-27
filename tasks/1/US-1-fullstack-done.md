# Fullstack — US-1

## Frontend components

- `resources/views/health.blade.php` — Already existed and is complete. Renders a styled HTML status page with overall status card, per-service status indicators (PostgreSQL, Redis), timestamp, and footer. Supports light/dark mode. Uses the project color palette and Instrument Sans font. Variables expected: `$status`, `$timestamp`, `$services`.
- `resources/views/welcome.blade.php` — Modified to add a "Health" navigation link pointing to `url('/health')`. The header is now always visible (removed `not-has-[nav]:hidden` guard) since the `nav` is always rendered with at minimum the Health link.

## Routes

| Method | Path      | Handler                      | Description                        |
|--------|-----------|------------------------------|------------------------------------|
| GET    | `/`       | closure → `welcome` view     | Welcome/home page (Blade)          |
| GET    | `/health` | `HealthController@check`     | Health check — JSON or HTML        |
| GET    | `/{slug}` | `RedirectController`         | URL redirect                       |

## Files created/modified

### Modified
- `app/Http/Controllers/HealthController.php` — Updated `check()` method signature to accept `Request $request` and return `JsonResponse|Response`. Added content-negotiation: when the request accepts `text/html` and does not explicitly want JSON, the controller returns the `health` Blade view with `$status`, `$timestamp`, and `$services` variables, along with the correct HTTP status code (200 or 503) and `Cache-Control: no-store, no-cache` header. JSON responses also received the `Cache-Control` header. The services field is now always passed to the view but only included in JSON for 503 responses (per api-contract spec).
- `resources/views/welcome.blade.php` — Added a "Health" link in the header nav that always renders. Removed the `not-has-[nav]:hidden` CSS class from the `<header>` since the nav is now unconditionally present. Login/Register/Dashboard links are preserved inside a `@if (Route::has('login'))` conditional.

### Already existed and verified complete
- `resources/views/health.blade.php` — No changes needed; already implements the full design spec from figma-specs.md.
- `routes/web.php` — Already has `Route::get('/health', [HealthController::class, 'check'])` registered.

## Notes

- The HealthController already had correct DB and Redis checks with logging and 503 handling. The only gap was the lack of HTML response support and the missing `Cache-Control` header.
- Content negotiation logic: `$request->accepts(['text/html']) && !$request->wantsJson()` — returns HTML for browser requests, JSON for API/curl requests.
- The health Blade view always receives `$services` (both `database` and `redis` statuses) regardless of overall status, which allows the HTML page to always show per-service detail. The JSON 200 response omits `services` per the api-contract spec.
- The welcome page Health link uses `url('/health')` (absolute URL helper) consistent with other nav links in the file.
