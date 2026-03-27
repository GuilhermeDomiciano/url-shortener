# Figma Specs — US-1: Bootstrap e Ambiente

## Status

No Figma design file is linked to this issue.

The user story explicitly states under "Referencia Visual (Figma)":

> N/A — Este checkpoint e puramente tecnico/infraestrutura.

---

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

---

## UI Components

No UI components are required for this story. This is a pure backend/infrastructure checkpoint.

The only endpoints with visual considerations:

| Endpoint         | Response Type | UI Required |
|------------------|---------------|-------------|
| `GET /health`    | JSON          | No (optional simple HTML status page for developer convenience) |
| `GET /`          | HTML (Blade)  | Pre-existing `welcome.blade.php` — no changes needed |

---

## API Response Format

### GET /health — Success (HTTP 200)

```json
{
  "status": "ok",
  "timestamp": "2026-03-27T00:00:00.000000Z",
  "services": {
    "database": "ok",
    "redis": "ok"
  }
}
```

### GET /health — Failure (HTTP 503)

```json
{
  "status": "error",
  "timestamp": "2026-03-27T00:00:00.000000Z",
  "services": {
    "database": "error",
    "redis": "ok"
  },
  "message": "One or more services are unavailable"
}
```

**Headers for both responses:**
- `Content-Type: application/json`
- `Cache-Control: no-store, no-cache`

---

## Docker Infrastructure Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         HOST MACHINE                            │
│                                                                 │
│  Browser / curl                                                 │
│       │                                                         │
│       │  http://localhost:8080                                  │
│       ▼                                                         │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                   Docker Network (bridge)                │   │
│  │                                                          │   │
│  │  ┌───────────────┐      ┌───────────────────────────┐   │   │
│  │  │     nginx     │      │      app (PHP-FPM)        │   │   │
│  │  │  nginx:latest │─────▶│     php:8.2-fpm           │   │   │
│  │  │  port 80→8080 │      │     port 9000 (internal)  │   │   │
│  │  └───────────────┘      └─────────────┬─────────────┘   │   │
│  │                                       │                  │   │
│  │                         ┌─────────────┼──────────────┐   │   │
│  │                         ▼             ▼              │   │   │
│  │                ┌────────────┐  ┌──────────────┐      │   │   │
│  │                │ postgres   │  │    redis     │      │   │   │
│  │                │ pg:15-alp. │  │ redis:7-alp. │      │   │   │
│  │                │ port 5432  │  │  port 6379   │      │   │   │
│  │                └────────────┘  └──────────────┘      │   │   │
│  │                     │                                 │   │   │
│  │             ┌────────────────┐                        │   │   │
│  │             │  pgbouncer     │ (optional, port 6432)  │   │   │
│  │             │  port 6432     │                        │   │   │
│  │             └────────────────┘                        │   │   │
│  └──────────────────────────────────────────────────────-┘   │   │
│                                                                 │
│  Named Volumes:                                                 │
│    pgdata   → /var/lib/postgresql/data                         │
│    redisdata → /data                                           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Service Summary

| Service    | Image              | Internal Port | Host Port  | Purpose                     |
|------------|--------------------|---------------|------------|-----------------------------|
| nginx      | nginx:latest       | 80            | 8080       | Reverse proxy to PHP-FPM    |
| app        | php:8.2-fpm        | 9000          | (none)     | Laravel application         |
| postgres   | postgres:15-alpine | 5432          | (none)     | Primary relational database |
| redis      | redis:7-alpine     | 6379          | (none)     | Cache, sessions, queue      |
| pgbouncer  | pgbouncer image    | 6432          | (none)     | Connection pooling (opt.)   |

---

## Health Check Page — Visual Design Spec

Although the `/health` endpoint returns JSON for programmatic consumers, a simple HTML status page (`resources/views/health.blade.php`) is provided for developer convenience. It is rendered when accessed from a browser with `Accept: text/html`, or can be linked from the Nginx dashboard.

### Design Principles

- Minimal and functional — no decorative elements
- Consistent with the existing project color palette (see below)
- Displays service status clearly using color-coded indicators
- Responsive (readable on mobile and desktop)
- No JavaScript frameworks — pure HTML + inline/embedded CSS

### Color Palette (from existing `welcome.blade.php`)

| Token           | Light Mode  | Dark Mode   | Usage                      |
|-----------------|-------------|-------------|----------------------------|
| Background      | `#FDFDFC`   | `#0a0a0a`   | Page background            |
| Card background | `#ffffff`   | `#161615`   | Status card container      |
| Text primary    | `#1b1b18`   | `#EDEDEC`   | Headings, service names    |
| Text secondary  | `#706f6c`   | `#A1A09A`   | Timestamps, subtitles      |
| Status OK       | `#16a34a`   | `#22c55e`   | Green dot + "ok" label     |
| Status ERROR    | `#dc2626`   | `#f87171`   | Red dot + "error" label    |
| Border subtle   | `#e3e3e0`   | `#3E3E3A`   | Card borders               |
| Accent          | `#f53003`   | `#FF4433`   | Brand color (header bar)   |

### Layout Structure

```
┌─────────────────────────────────────────────────────────┐
│  ■ URL Shortener           [brand accent bar — 4px top] │
│─────────────────────────────────────────────────────────│
│                                                         │
│  System Health                   2026-03-27T12:00:00Z  │
│  ─────────────────────────────────────────────────      │
│                                                         │
│  ┌───────────────────────────────────────────────────┐  │
│  │  Overall Status                                   │  │
│  │  ● OK  /  ● ERROR                                 │  │
│  └───────────────────────────────────────────────────┘  │
│                                                         │
│  ┌───────────────────────────────────────────────────┐  │
│  │  Services                                         │  │
│  │  ────────────────────────────────────────         │  │
│  │  PostgreSQL        ●  ok                          │  │
│  │  Redis             ●  ok                          │  │
│  └───────────────────────────────────────────────────┘  │
│                                                         │
│  GET /health  ·  HTTP 200  ·  application/json          │
└─────────────────────────────────────────────────────────┘
```

### Typography

- **Heading:** 1.5rem, font-weight 600, color: text-primary
- **Service name:** 0.875rem, font-weight 500, color: text-primary
- **Status label:** 0.875rem, font-weight 400, color: status-ok or status-error
- **Timestamp/subtitle:** 0.75rem, font-weight 400, color: text-secondary
- **Font family:** `'Instrument Sans', ui-sans-serif, system-ui, sans-serif`

### Status Indicator

A filled circle (●, 10px diameter) with background color matching the status:
- Green (`#16a34a`) for `"ok"`
- Red (`#dc2626`) for `"error"`

---

## Existing UI Patterns (reference for future issues)

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

---

## Routes Identified

Based on the codebase (from a reference implementation):

| Method | Path         | Description                          |
|--------|--------------|--------------------------------------|
| GET    | `/`          | Welcome/home page (Blade view)       |
| GET    | `/health`    | Health check endpoint (JSON + HTML)  |
| GET    | `/{slug}`    | URL redirect (no UI)                 |
| POST   | `/api/links` | Create short link (JSON API)         |

---

## Recommendation

No Figma work is needed for US-1. Implementation should:

1. Preserve the existing `welcome.blade.php` as-is (default Laravel view).
2. Focus all effort on Docker, infrastructure, and the `/health` JSON endpoint.
3. Optionally render `health.blade.php` when the `/health` route is accessed from a browser.
4. Future issues (URL creation form, dashboard, analytics) will require dedicated Figma designs.
