# Figma Specs — US-1: Bootstrap e Ambiente

## Status

No Figma design file is linked to this issue.

The user story explicitly states under "Referencia Visual (Figma)":

> N/A — Este checkpoint e puramente tecnico/infraestrutura.

## Summary

US-1 is entirely a backend/infrastructure story. There are no UI components, screens, or visual designs to implement. The deliverables are:

- Laravel 11 project scaffolding
- Docker Compose configuration (app, nginx, postgres, redis)
- Dockerfile (multistage, PHP 8.2+)
- `.env.example` with all required variables
- `GET /health` endpoint returning JSON
- PHPUnit test for the health check
- README with setup instructions
- `.gitignore` and `.dockerignore`

## Existing UI Patterns (for reference in future issues)

The project currently has a single Blade view at `resources/views/welcome.blade.php`, which is the default Laravel welcome page. It uses:

- **Font:** Instrument Sans (loaded from fonts.bunny.net), weights 400/500/600
- **CSS Framework:** Tailwind CSS v4 (via Vite or inline compiled)
- **Color palette (light mode):**
  - Background: `#FDFDFC`
  - Text primary: `#1b1b18`
  - Text secondary: `#706f6c`
  - Accent/link: `#f53003`
  - Border subtle: `#19140035`
  - Border strong: `#e3e3e0`
  - Card background: `#ffffff`
  - Dot/indicator: `#dbdbd7`
- **Color palette (dark mode):**
  - Background: `#0a0a0a`
  - Card background: `#161615`
  - Text primary: `#EDEDEC`
  - Text secondary: `#A1A09A`
  - Accent/link: `#FF4433`
  - Border: `#3E3E3A`
- **Spacing:** Tailwind spacing scale (p-6, p-8, p-20 for content areas)
- **Border radius:** `rounded-sm` (0.25rem) for buttons and nav items; `rounded-lg` for cards
- **Typography:** `text-sm` (0.875rem) for body, `text-[13px]` for small content
- **Shadows:** Subtle inset box shadows for card containers

## Routes Identified

Based on the codebase (from a reference implementation):

| Method | Path       | Description                          |
|--------|------------|--------------------------------------|
| GET    | `/`        | Welcome/home page (Blade view)       |
| GET    | `/health`  | Health check endpoint (JSON)         |
| GET    | `/{slug}`  | URL redirect (no UI)                 |
| POST   | `/api/links` | Create short link (JSON API)       |

## UI Requirements for US-1

Since this is an infrastructure story, there are no UI requirements. The only endpoint with a visual response is:

- `GET /` — renders `welcome.blade.php` (default Laravel welcome page, already exists)

The health check endpoint (`GET /health`) returns only JSON, no HTML.

## Recommendation

No Figma work is needed for US-1. Implementation should:

1. Preserve the existing `welcome.blade.php` as-is (default Laravel view).
2. Focus all effort on Docker, infrastructure, and the `/health` JSON endpoint.
3. Future issues (URL creation form, dashboard, analytics) will require dedicated Figma designs.
