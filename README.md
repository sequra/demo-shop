# SeQura Checkout Demo

A demo application showcasing SeQura's payment integration. It implements a full checkout flow - from address entry to payment completion - using SeQura's [Integration CORE](https://github.com/sequra/integration-core) PHP library on the backend and [Lit web components](https://lit.dev/) on the frontend.

## Table of Contents

- [📖 Overview 📖](#-overview-)
- [⚙️ Components summary ⚙️](#%EF%B8%8F-components-summary-%EF%B8%8F)
    - [Backend](#backend)
    - [Frontend](#frontend)
    - [Checkout Flow](#checkout-flow)
- [🔌 System dependencies 🔌](#-system-dependencies-)
- [👩‍💻 Development environment instructions 👩‍💻](#-development-environment-instructions-)
    - [Environment variables](#environment-variables)
    - [Setup](#setup)
    - [Reinitializing data](#reinitializing-data)
    - [Frontend development](#frontend-development)
    - [Available npm scripts](#available-npm-scripts)
- [Adding products](#adding-products)
- [URLs list](#urls-list)

## 📖 Overview 📖

A demo checkout application that integrates with [SeQura's payment platform](https://sequra.com). The app simulates an e-commerce checkout where a customer enters their address, selects shipping, chooses a SeQura payment method (e.g. "buy now, pay later"), completes identification via SeQura's iFrame, and receives order confirmation via IPN webhook.

**Tech Stack:**
- **Backend**: PHP 8.4, Apache 2.4, `sequra/integration-core` ^4.1
- **Frontend**: Lit 3.1 (web components), Vite 5.0, Sass
- **Infrastructure**: Docker, ngrok (for webhook tunneling)
- **Storage**: Encrypted JSON files (no database)

### ⚙️ Components summary ⚙️

#### Backend

The backend is a PHP API layer that integrates with SeQura's `integration-core` library. It handles solicitation requests, serves the checkout page, processes IPN webhooks, and exposes order status endpoints.

**Entry Point:** `public/index.php` loads the service container via `Bootstrap.php`, then dispatches requests through `Router.php`. `SecurityMiddleware` validates CSRF tokens and request origins before controllers are invoked.

**Controllers:**

| Controller | Routes | Purpose |
|---|---|---|
| **PageController** | `GET /` | Renders the checkout page with the `<sequra-checkout>` web component and injects the merchant asset key. |
| **CheckoutController** | `POST /api/checkout/solicitation`, `GET /api/checkout/form` | Core checkout logic. `solicitation()` sends cart/address/shipping data to SeQura's API and returns available payment methods. `getForm()` fetches the identification form HTML for a selected payment product. |
| **OrderController** | `GET /api/orders/{id}/status` | Returns order status (pending, confirmed, on_hold, error) from the repository. Used by frontend polling. |
| **WebhookController** | `POST /api/ipn` | Receives Instant Payment Notification callbacks from SeQura. Transforms the payload and delegates to the core webhook handler to update order status. |

**Platform Adapters** - demo implementations of `integration-core` interfaces:

| Class | Purpose |
|---|---|
| **DemoConfiguration** | In-memory configuration store. |
| **DemoEncryptor** | AES encryption for stored data using the key from `.env`. |
| **DemoLoggerAdapter** | Logs to `/tmp/sequra-demo.log`. |
| **DemoMerchantDataProvider** | Provides callback URLs and webhook endpoints for SeQura's form. |
| **DemoOrderCreation** | No-op order creation (demo has no real shop backend). |
| **DemoShopOrderService** | Wraps the order repository for status updates on webhook events. |
| **DemoShopOrderStatuses** | Maps SeQura states to shop order states. |
| **DemoStoreIntegration** | Provides store metadata (country, currency, language). |

**Repositories** - all use encrypted JSON files (AES-256-CBC, key from `.env`) instead of a database:

| Repository | Storage | Purpose |
|---|---|---|
| **DemoSeQuraOrderRepository** | `/tmp/sequra-demo-checkout-orders.enc` | Stores order data keyed by cart ID. Tracks status, IPN receipt, and timestamps. |
| **DemoCredentialsRepository** | `data/credentials.enc` | Merchant credentials indexed by country code. |
| **DemoConnectionDataRepository** | `data/connections.enc` | API connection settings. |
| **DemoCountryConfigRepository** | `data/countryConfig.enc` | Per-country configuration. |
| **DemoDeploymentsRepository** | `data/deployments.enc` | Deployment records. |

**Security:**
- **SecurityMiddleware** validates CSRF tokens (via `X-CSRF-Token` header) and request Origin/Referer for all `/api/*` routes. The `/api/ipn` webhook endpoint is exempt (SeQura validates its own signatures).
- **CsrfTokenManager** generates session-based tokens injected into the checkout page via a `<meta>` tag.

#### Frontend

The frontend is a set of Lit web components that render the checkout UI. Everything is bundled into a single IIFE file (`sequra-checkout.js`) by Vite and served from `backend/public/dist/`.

**Main Component:** **SeQuraCheckout** (`components/SeQuraCheckout.js`) is the root orchestrator. It manages all checkout state (cart items, addresses, shipping, payment methods, loading states) and renders child components based on the current step (1-4). It also handles SeQura script loading, solicitation API calls, identification form injection, order status polling, and currency/language change reactions.

**Step Components:**

| Step | Active Component | Summary Component | Purpose |
|---|---|---|---|
| 1 | **SeQuraAddressForm** | **SeQuraAddressSummary** | Shipping address input with country selector and validation. |
| 2 | **SeQuraShippingOptions** | **SeQuraShippingSummary** | Shipping method selection (standard, express, overnight). Confirming triggers solicitation. |
| 3 | **SeQuraPaymentMethods** | **SeQuraPaymentSummary** | Displays payment methods returned by solicitation. Renders SeQura promotion widgets per method. |
| 4 | **SeQuraOrderSummary** (sidebar) | / | Order review with discount codes. "Complete Order" opens the SeQura identification form. |

**Page Components:**

| Component | Purpose |
|---|---|
| **SeQuraOrderCompleted** | Thank-you page showing order number, item summary, and shipping details. |
| **SeQuraOrderPending** | Intermediate page when the IPN hasn't arrived yet. Offers manual status check. |

**Utility Components:**

| Component | Purpose |
|---|---|
| **SeQuraStepIndicator** | Visual progress bar (steps 1-4). |
| **SeQuraNotification** | Toast notification system (success, error, warning). |
| **SeQuraSettingsPanel** | Floating panel to switch language and currency. |
| **SeQuraDiscountInput** | Discount code input with apply/remove functionality. |

**Molecule Components:**

| Component | Purpose |
|---|---|
| **SeQuraItemList** | Renders cart items in grid or compact layout. |
| **SeQuraTotals** | Displays subtotal, discount, shipping, and total. |
| **SeQuraOrderDetails** | Shows address, shipping, and payment info on the completed page. |

**Services:**

| Service | Purpose |
|---|---|
| **SeQuraService** | API client. Handles solicitation (`POST /api/checkout/solicitation`), form fetching, order status polling, and dynamic loading of SeQura's CDN script for widgets. |
| **I18nService** | Internationalization. Supports 4 languages (en, es, fr, de) and 4 currencies (EUR, GBP, USD, CHF). Formats prices with locale-appropriate separators. Persists preferences in localStorage. |
| **OrderBuilderService** | Transforms frontend state into the SeQura API payload format (snake_case, country codes, currency, cart items). |
| **ProductService** | Loads the mock product catalog and applies translations. |
| **DiscountService** | Validates discount codes and calculates discount amounts. Accepts the dynamic pattern `SEQURA-XX` (e.g. `SEQURA-20` for 20% off, integer 1–99, case-insensitive) plus two special codes: `freeship` (free shipping) and `welcome` ($15 off). |
| **StorageService** | localStorage wrapper with namespaced keys. |

**Models:**

| Model | Purpose |
|---|---|
| **Product** | Represents a shop product (id, name, price, image, description, quantity). |
| **CartItem** | Wraps a Product with quantity tracking. Computes `total = price * quantity`. |
| **Order** | Aggregates cart items, shipping, discount, and address into a single order object. |

#### Checkout Flow

1. **Page Load** - Browser loads checkout.php, `<sequra-checkout>` component initializes with mock products and default settings (EUR, English).
2. **Address (Step 1)** - User fills shipping address, validates, moves to Step 2.
3. **Shipping (Step 2)** - User picks shipping method, clicks "Continue", triggers solicitation API call to backend.
4. **Solicitation** - Backend sends order data to SeQura API, receives available payment methods + order reference, frontend loads SeQura widget script from CDN.
5. **Payment (Step 3)** - User selects a payment method, moves to Step 4.
6. **Complete Order (Step 4)** - User clicks "Complete Order", backend fetches identification form, SeQura iFrame opens for KYC/payment approval.
7. **IPN Webhook** - SeQura sends IPN to `/api/ipn`, backend updates order status, frontend polls `/api/orders/{id}/status` until confirmed.
8. **Done** - Order confirmed: shows thank-you page. Timeout: shows pending page with manual check.

### 📊 Observability and dashboards 📊

- Application logs: `/tmp/sequra-demo.log` (via `DemoLoggerAdapter`)
- No external dashboards (demo application)

### 🔌 System dependencies 🔌

| Dependency | Purpose |
|---|---|
| **Docker & Docker Compose** | Containerized runtime. Handles PHP, Apache, Node.js, Composer — no local installs needed. |
| **ngrok** (or similar tunneling tool) | Exposes local port 8081 to the internet so SeQura can send IPN webhooks during development. |
| **SeQura API** | External payment platform. The app calls SeQura's API for solicitations, payment forms, and credential validation. |
| **Encrypted JSON files** | Used instead of a database. All persistent data (orders, credentials, configs) is stored as AES-256-CBC encrypted files in `backend/data/` and `/tmp/`. |

## 👩‍💻 Development environment instructions 👩‍💻

### Environment variables

Configure `.env` in the project root:

```
SEQURA_ACCOUNT_KEY=your_account
SEQURA_ACCOUNT_SECRET=your_secret
SEQURA_ENCRYPTION_KEY=your_secret_passphrase
SEQURA_WEBHOOK_BASE_URL=https://your-ngrok-url.ngrok.app
APP_ENV=development
```

| Variable | Description |
|---|---|
| `SEQURA_ACCOUNT_KEY` | Your SeQura merchant account key. Provided by SeQura during onboarding. Used to authenticate API requests. |
| `SEQURA_ACCOUNT_SECRET` | Your SeQura merchant account secret. Provided together with the account key. Used to sign API requests. |
| `SEQURA_ENCRYPTION_KEY` | A secret passphrase used to encrypt stored data (credentials, orders, configs) at rest using AES-256-CBC. Can be any string — it is hashed with SHA-256 internally to derive the actual encryption key. Generate one with: `openssl rand -hex 32` |
| `SEQURA_WEBHOOK_BASE_URL` | The publicly accessible base URL where SeQura will send IPN (Instant Payment Notification) webhooks. In development, this is your ngrok URL (e.g. `https://abc123.ngrok.app`). In production, your actual domain. |
| `APP_ENV` | Application environment. Set to `development` for Vite dev server support and verbose error output, or `production` to serve the pre-built frontend bundle from `backend/public/dist/`. |

### Setup

1. Copy and configure `.env` (see [Environment variables](#environment-variables)).

2. Start the application:
   ```bash
   cd backend
   docker compose up
   ```
   This builds the Docker image and starts the container. The `entrypoint.sh` script then runs automatically on each container start:
    - **`composer install`** — installs the `sequra/integration-core` library and other PHP dependencies into `backend/vendor/`.
    - **`npm install && npm run build`** — installs frontend dependencies (Lit, Vite, Sass) and bundles the web components into a single IIFE file output to `backend/public/dist/`.
    - **`php bin/init-data.php`** — connects to the SeQura API using your `.env` credentials, fetches available deployments and merchant data, validates credentials, and writes the results as encrypted JSON files to `backend/data/`.
    - **`apache2-foreground`** — starts Apache serving the app on `http://localhost:8081`.

3. Start ngrok for webhooks:
   ```bash
   ngrok http 8081
   ```
   Update `SEQURA_WEBHOOK_BASE_URL` in `.env` with the ngrok URL, then restart the backend (`docker compose down && docker compose up`).

### Reinitializing data

`php bin/init-data.php` can be run independently at any time to refresh the encrypted data files in `backend/data/`. This is useful when:

- You change `SEQURA_ACCOUNT_KEY` or `SEQURA_ACCOUNT_SECRET` in `.env` and need to re-validate credentials.
- SeQura has updated your merchant configuration (new countries, payment methods, etc.).
- The encrypted data files are corrupted or deleted.

To run it inside the running container:
```bash
docker compose exec sequra_demo_shop php bin/init-data.php
```

No restart is needed — the application reads the updated data files on each request.

### Frontend development

For frontend development with hot reload (instead of rebuilding via Docker):
```bash
cd frontend
npm install
npm run dev
```
Vite dev server runs on `http://localhost:3000` with HMR.

### Available npm scripts

| Script | Command | Purpose |
|---|---|---|
| `npm run dev` | `vite` | Starts Vite dev server with HMR on port 3000 |
| `npm run build` | `vite build` | Production build, outputs to `backend/public/dist/` |
| `npm run preview` | `vite preview` | Preview the production build locally |
| `npm run serve` | `vite preview --port 3000` | Same as preview but on port 3000 |

### 🚧 How to run the test suite 🚧

No automated test suite is included in this demo application. Manual testing can be done through the checkout flow in the browser.

For PHP code style checks:
```bash
docker compose exec sequra_demo_shop vendor/bin/phpcs --standard=PSR12 src/
```

## Adding products

Products are hardcoded in the frontend - there is no admin interface or backend API for managing them. To add a new product, modify three things:

### 1. Add the product image(not required)

Place a `.jpg` image in:

- `frontend/public/images/products/<product-slug>.jpg`


### 2. Add the product entry

Edit `frontend/src/data/products.js` and append a new object to the array:

```js
{
    id: 9,                                    // unique numeric ID
        nameKey: 'product.tablet.name',           // i18n key for the product name
        price: 45.00,                             // price in the base currency (EUR)
        image: '/dist/images/products/tablet.jpg',// path to the product image
        descKey: 'product.tablet.desc',           // i18n key for the description
        quantity: 1                               // default quantity in cart
}
```

### 3. Add translations

Edit `frontend/src/i18n/translations.js` and add translation keys for **all four languages** (`en`, `es`, `fr`, `de`). Add the entries inside each language block alongside the existing product keys:

```js
// en:
'product.tablet.name': 'Tablet Pro 11-inch',
    'product.tablet.desc': 'Lightweight tablet for everyday use',

// es:
    'product.tablet.name': 'Tablet Pro 11 pulgadas',
    'product.tablet.desc': 'Tablet ligera para uso diario',

// fr:
    'product.tablet.name': 'Tablette Pro 11 pouces',
    'product.tablet.desc': 'Tablette légère pour un usage quotidien',

// de:
    'product.tablet.name': 'Tablet Pro 11 Zoll',
    'product.tablet.desc': 'Leichtes Tablet für den täglichen Gebrauch',
```

After making the changes, rebuild the frontend (`npm run build`) or let the Vite dev server pick them up automatically if running `npm run dev`.

## URLs list

| What | URL |
|---|---|
| App home | `http://localhost:8081` |
| Vite dev server (frontend HMR) | `http://localhost:3000` |
| Solicitation API | `POST http://localhost:8081/api/checkout/solicitation` |
| Payment form API | `GET http://localhost:8081/api/checkout/form` |
| Order status API | `GET http://localhost:8081/api/orders/{id}/status` |
| IPN Webhook | `POST http://localhost:8081/api/ipn` |
