# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SeQura Checkout Demo — a full-stack e-commerce checkout integrating SeQura's payment platform. PHP 8.4 backend with `sequra/integration-core` library, Lit 3.1 web components frontend bundled by Vite 5.0 into a single IIFE file.

## Development Commands

### Docker (full stack)
```bash
cd src/backend
docker compose up                  # Start app on http://localhost:8081
docker compose down                # Stop
```

### Frontend development (HMR)
```bash
cd src/frontend
npm install
npm run dev                        # Vite dev server on http://localhost:3000
npm run build                      # Production build → src/backend/public/dist/
```

### PHP code style (only linter — no PHPUnit)
```bash
docker compose exec sequra_demo_shop vendor/bin/phpcs --standard=PSR12 src/
```

### Reinitialize backend data
```bash
docker compose exec sequra_demo_shop php bin/init-data.php
```
Fetches deployments from SeQura API, writes encrypted JSON to `backend/data/`, logs to `/tmp/sequra-demo.log`.

### Production image
The backend image is built from `src/backend/Dockerfile` (PHP 8.4 + Apache); the local stack uses it via `docker compose up`. No Makefile or registry-push automation is checked into this repo.

No automated test suite. Testing is manual through the browser checkout flow.

### Git hooks (optional, developer-local)
```bash
./setup.sh                         # git config core.hooksPath .githooks
```
Enables the shared hooks in `.githooks/`: `pre-commit` runs `php -l` + `phpcbf`/`phpcs` (PSR-12) on staged PHP, and `pre-push` runs the full `phpcs src/` sweep on PHP-touching pushes. Both run inside the app container if it is up, otherwise a throwaway `php:<ver>-cli` image, and skip with a notice only when Docker is unavailable. Bypass a hook once with `--no-verify`.

### Continuous integration
The `Code style` GitHub Actions workflow (`.github/workflows/code-style.yml`) runs the `php -l` syntax check and the full `phpcs --standard=PSR12 src/` sweep on every push and pull request, so the gate holds even when a developer hasn't installed the hooks.

## Architecture

### Two-part monorepo
- `src/frontend/` — Lit web components, Vite, Sass. Builds to single IIFE (`sequra-checkout.js`) output into `src/backend/public/dist/`.
- `src/backend/` — PHP 8.4, Apache, custom router. No framework (not Laravel/Symfony). Uses `sequra/integration-core` for SeQura API integration.

### Frontend structure (`src/frontend/src/`)
- **Entries**: `index.js` exports components/models/services and sets `window.SeQura` global. `checkout-entry.js` registers the `<sequra-checkout>` custom element — this is the IIFE bundle entry point used in the checkout page.
- **Components**: Lit web components in `components/`. Root orchestrator is `SeQuraCheckout.js` (~560 lines) managing a 4-step checkout flow. Sub-components in `components/molecules/`.
- **Services**: `services/` — `SeQuraService` (API client), `I18nService` (4 languages, 4 currencies), `OrderBuilderService` (state→API payload), `ProductService`, `DiscountService`, `StorageService`.
- **Data**: `data/products.js` — hardcoded mock catalog. `i18n/translations.js` — translation strings for en/es/fr/de.
- **Styles**: SCSS in `styles/` with `_variables.scss` for shared tokens.
- **State**: `SeQuraCheckout` uses Lit reactive properties (`static properties = { ... }`). I18n changes propagate via `I18nService.addListener()` callbacks that call `requestUpdate()`.
- **Vite plugin**: `vite.config.js` includes a custom `copyPublicAssets()` plugin that copies `src/frontend/public/` (images, etc.) into `backend/public/dist/` after each build.

### Backend structure (`src/backend/`)
- **Entry**: `public/index.php` → `Bootstrap.php` (service registration) → `Router.php` (dispatch).
- **Bootstrap three stages**: (1) register platform adapters (`DemoConfiguration`, `DemoEncryptor`, `DemoLoggerAdapter`, etc.) before core init, (2) call `BootstrapComponent::init()`, (3) override core repositories with demo implementations. New services must be registered in the correct stage.
- **Router**: Uses PHP Reflection to auto-inject controller dependencies from `ServiceRegister`. `Request` wraps superglobals; `Response` has static factories `::json()`, `::html()`, `::view()`.
- **Controllers**: `PageController` (GET /), `CheckoutController` (solicitation + form), `OrderController` (status polling), `WebhookController` (IPN).
- **Platform adapters**: `Platform/` — demo implementations of integration-core interfaces (config, encryption, logging, merchant data).
- **Repositories**: `Repository/` — encrypted JSON file storage (AES-256-CBC). `DemoBaseRepository` keeps a static in-memory cache keyed by class name so multiple instances share data. Storage path is controlled by `SEQURA_DATA_DIR` env var (default: `backend/data/`).
- **Security**: `SecurityMiddleware` validates CSRF tokens (AES-256-CBC encrypted, 4-hour TTL) and Origin/Referer headers on `/api/*` routes. IPN endpoint is exempt. Allowed hosts derived from `localhost`, `127.0.0.1`, `VITE_ALLOWED_HOSTS` (comma-separated env var), and the hostname in `SEQURA_WEBHOOK_BASE_URL`.
- **CSRF flow**: `CsrfTokenManager` generates the token; `PageController` embeds it in `<meta name="csrf-token">`. Frontend reads the meta tag and sends it as `X-CSRF-Token` header on every API request.
- **Logging**: `DemoLoggerAdapter` writes all log output to `/tmp/sequra-demo.log`.

### Checkout flow
1. Address entry → 2. Shipping selection (triggers solicitation POST) → 3. Payment method selection (SeQura widgets from CDN) → 4. Order completion (SeQura identification iFrame) → IPN webhook confirms order → thank-you page.

### Key integration points
- Frontend calls backend API at `/api/checkout/solicitation` and `/api/checkout/form`
- Backend delegates to `sequra/integration-core` which calls SeQura's external API
- SeQura sends IPN webhooks to `/api/ipn`; frontend polls `/api/orders/{id}/status`
- `WebhookController` prefixes incoming payload keys with `m_` and remaps `event` → `sq_state` before passing to `WebhookAPI::webhookHandler()` — this bridges SeQura's webhook format to the core's expected format
- Vite build output goes to `src/backend/public/dist/` — the backend serves it

### Webhook tunneling
IPN webhooks require a publicly accessible URL. The repo ships a `docker-compose.override.yml` that starts a **Cloudflare Tunnel** sidecar (`cloudflared`) — set `CLOUDFLARED_TUNNEL_TOKEN` in `.env` and `SEQURA_WEBHOOK_BASE_URL` to the tunnel's public hostname. Alternatively, delete the override file and use ngrok or similar manually.

### Environment
Backend `.env` (from `.env.example`) requires: `SEQURA_ACCOUNT_KEY`, `SEQURA_ACCOUNT_SECRET`, `SEQURA_ENCRYPTION_KEY`, `SEQURA_WEBHOOK_BASE_URL`, `APP_ENV`, `CLOUDFLARED_TUNNEL_TOKEN`. When `APP_ENV=development`, the checkout page loads JS from Vite dev server instead of the built bundle.

Optional: `SEQURA_DATA_DIR` (encrypted file storage path), `VITE_ALLOWED_HOSTS` (additional CORS-allowed hosts).

### PHP debugging
`.vscode/launch.json` configures XDebug listening on port 9003 with path mappings for remote debugging inside Docker.

## Adding Products

1. Add image to `src/frontend/public/images/products/`
2. Add entry in `src/frontend/src/data/products.js`
3. Add translation keys for all 4 languages in `src/frontend/src/i18n/translations.js`

## Working Principles

Behavioral guidelines (adapted from Andrej Karpathy's CLAUDE.md) to reduce common LLM coding mistakes. They bias toward caution over speed — for trivial tasks, use judgment.

### 1. Think Before Coding
Don't assume. Don't hide confusion. Surface tradeoffs.
- State assumptions explicitly. If uncertain, ask.
- If multiple interpretations exist, present them — don't pick silently.
- If a simpler approach exists, say so. Push back when warranted.
- If something is unclear, stop, name what's confusing, and ask.

### 2. Simplicity First
Minimum code that solves the problem. Nothing speculative.
- No features beyond what was asked; no abstractions for single-use code.
- No "flexibility"/"configurability" that wasn't requested; no error handling for impossible scenarios.
- If you write 200 lines and it could be 50, rewrite it. Ask: "Would a senior engineer say this is overcomplicated?"

### 3. Surgical Changes
Touch only what you must. Clean up only your own mess.
- Don't "improve" adjacent code, comments, or formatting; don't refactor what isn't broken.
- Match existing style even if you'd do it differently.
- Remove imports/variables/functions that *your* changes orphaned; don't delete pre-existing dead code unless asked — mention it instead.
- Every changed line should trace directly to the request.

### 4. Goal-Driven Execution
Define success criteria. Loop until verified.
- "Add validation" → "write the failing case, then make it pass"; "Fix the bug" → "reproduce it, then make it pass".
- For multi-step tasks, state a brief plan with a verify check per step.
- This repo has no automated suite — the verify gate is `phpcs --standard=PSR12` plus the manual browser checkout flow (address → shipping → payment → completion → IPN → thank-you). Confirm both before declaring a change done.
