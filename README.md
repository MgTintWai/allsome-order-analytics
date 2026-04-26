# New Order Analytics API

Laravel 12 service that **accepts an orders CSV via HTTP upload** and returns **JSON analytics**: total revenue (Σ quantity × price) and the **best-selling SKU** by total units sold. There is **no database** for the analytics feature; data is read from the uploaded file only.

Design:

- **API → `OrderAnalyticsService` → `OrderCsvRepository`:** the controller only talks to the service. The service calls the repository for “data access” (CSV file on disk, same role as a DB repository in a typical stack) and runs domain logic (`summarize`, tie-break) in one place.
- `App\Contracts\BaseInterface` and `App\Repositories\BaseRepository` match the same repository foundation pattern used in **translation-service** (Eloquent CRUD for future or optional persistence; this project’s order flow is CSV-based).
- `OrderCsvRepositoryInterface` → `OrderCsvRepository` implements CSV loading; `OrderAnalyticsService` orchestrates load + analytics.
- **Validation:** `OrderUploadConstraints` holds 4 MB caps, allowed CSV MIME list, and `Content-Length` tuning. **`OrderCsvFile`** enforces exactly one part, **`.csv` only** (client extension and `guessExtension()`), **declared MIME** in an allow-list (extension can be spoofed; MIME can be wrong for renamed binaries—both checks help), and max size. **`OrderCsvRequestBodyHeuristic`** catches Postman adding a second file under the same key when PHP only keeps one part: the **full request body** still includes both parts, so `Content-Length` can exceed the visible file + tolerance.

## Requirements

- PHP **8.2+**
- [Composer](https://getcomposer.org/)

## Install

```bash
cd new-order-analytics-api
composer install
cp .env.example .env
php artisan key:generate
```

Migrations are **optional** for this API; analytics do not query the database. The default app config uses the **filesystem** for cache, sessions, and a **sync** queue, so you do not need MySQL, SQLite, or the `cache` / `sessions` database tables. If you have an old `.env` with `CACHE_STORE=database` or `SESSION_DRIVER=database`, set them to `file` and `QUEUE_CONNECTION=sync` (or copy from `.env.example`), then run `php artisan config:clear`.

You may run the default Laravel migrations if you use other app features (e.g. `users`).

### PHP upload limits vs Laravel (4 MB rule)

| Layer | Responsibility |
|--------|------------------|
| **php.ini** | `post_max_size` and `upload_max_filesize` must be **larger** than your strict app limit (e.g. **20M**), or PHP may reject the body *before* Laravel runs. Set `display_errors=Off` and `log_errors=On` in production so warnings are not printed to the client. |
| **Laravel** | `OrderAnalyticsUploadRequest` uses `App\Validation\Rules\OrderCsvFile` and `App\Validation\Rules\OrderCsvRequestBodyHeuristic` (see `App\Support\OrderUploadConstraints` for sizes). `app/Exceptions/Handler.php`: **413** JSON for `PostTooLargeException` when the framework can handle the request. |

If you see `<b>Warning</b>` before JSON in other setups, that is **PHP output**; use `display_errors=Off` in the active `php.ini` for production. For `php artisan serve`, this repo ships a root `server.php` that turns off `display_errors` before each request (see that file).

## Run the API

```bash
php artisan serve
```

## POST — upload CSV (Postman / curl)

`multipart/form-data` with **exactly one** file in field **`csv`**: **`.csv` only**, **max 4 MB** (4096 KiB), **allowed CSV MIME types** (see `OrderUploadConstraints::CSV_ALLOWED_MIMES`). If two parts are sent under `csv` and Laravel receives them as an **array** of uploads, validation fails with a clear message. If Postman stacks two files on the same key but PHP only exposes one `UploadedFile`, the **body-size** rule may still return **422** (see design note above). Prefer **one file row** for `csv` in Postman.

**Expected header (first line):** `order_id,sku,quantity,price` (lowercase, exact order).  
Each data row: positive integer `order_id`, non-empty `sku`, positive integer `quantity`, non-negative numeric `price`.

**Example (bundled sample file):**

```bash
curl -s -F "csv=@tests/Fixtures/allsome_interview_test_orders.csv" \
  http://127.0.0.1:8000/api/orders/analytics
```

**200** example body (from `tests/Fixtures/allsome_interview_test_orders.csv`; also saved as `example-output.json`):

```json
{
  "total_revenue": 710,
  "best_selling_sku": {
    "sku": "SKU-A123",
    "total_quantity": 5
  }
}
```

**Tie-break:** If two SKUs have the same total quantity, the **lowest** SKU name (string sort) is returned so the result is stable.

### Why PHP can block uploads before Laravel

1. The web server/PHP read the full HTTP **body** first. **`post_max_size`** caps the *entire* POST (all fields + files). If the body is larger, PHP may **empty** `$_POST` / `$_FILES` and emit a **warning**—Laravel’s router and your **FormRequest never run** for that size error.
2. **`upload_max_filesize`** applies per file and must be **≤ `post_max_size`**. A practical setup for this app (4 MB field max + small overhead) is e.g. **`post_max_size=10M`**, **`upload_max_filesize=10M`** in `php.ini`, then the app’s **4 MB** `csv` limit still enforces the product rule in `OrderAnalyticsUploadRequest`.
3. **413** in this app is the JSON from `app/Exceptions/Handler.php` for `PostTooLargeException`, plus `public/index.php` strips accidental PHP warning text before the first `{` for `/api/*`.

**Standard error JSON**

```json
{ "error": { "type": "request_entity_too_large", "message": "Uploaded file exceeds the maximum allowed size (4 MB)." } }
```

**Validation (Form request):** `type`: `validation_error`, `message`: `Invalid input`, `details`: field errors. Implemented in `OrderAnalyticsUploadRequest::failedValidation()`.

### Error handling (reference)

| Situation | HTTP | Notes |
|-----------|------|--------|
| POST over PHP/HTTP size | **413** | `error.type` = `request_entity_too_large` |
| FormRequest (`csv` rules) | 422 | `validation_error` + `details` |
| File unreadable (domain) | 404 | `order_file_unreadable` |
| No valid rows in CSV | 422 | `no_valid_order_rows` + `details.row_errors` |
| Some bad rows, some good | 200 | success + `warnings` |
| `GET /api/health` | 200 | `{ "status": "ok" }` |

**Web vs JSON errors:** `bootstrap/app.php` uses `shouldRenderJsonWhen` for `api/*` (and `Accept: application/json`) so Laravel does not return HTML error pages for those cases. The `AcceptJsonForApi` middleware sets `Accept: application/json` when missing on `/api/*`.

### Assessment PDF sample JSON vs this repository

The official brief (`allsome_IT_test`) shows an example with **`"total_revenue": 610.00`**. The **`allsome_interview_test_orders.csv` committed in this repository** (under `tests/Fixtures/`) sums to **`710.00`** (quantity × price on each row). **`example-output.json` matches that file**, not the PDF’s number. If you use a different copy of the CSV (e.g. from the company) and its total is **610.00**, run the program on **that** file; the same code will return totals for the data you load.

**Note on totals:** The bundled fixture above yields **710.00** total revenue; best-selling SKU and quantities follow from that data.

## Postman

Import the collection and environment (optional) from the `postman/` folder:

- `postman/New-Order-Analytics-API.postman_collection.json`
- `postman/Local.postman_environment.json` (set `baseUrl` as needed, default `http://127.0.0.1:8000`)

Then:

1. Select the **Local** environment in Postman.
2. **GET** `Health` — checks `GET /api/health`.
3. **Orders — Analytics (upload CSV)** — **form-data** key `csv` (type **File**), choose `tests/Fixtures/allsome_interview_test_orders.csv` (or your CSV on disk).
4. **Send**; expect the JSON response above.

## Tests

```bash
composer test
# or
./vendor/bin/phpunit
```

## Project layout (analytics)

- `app/Contracts/BaseInterface.php` — base repository contract (shared pattern)
- `app/Repositories/BaseRepository.php` — Eloquent `BaseInterface` implementation
- `app/Contracts/OrderCsvRepositoryInterface.php` — CSV loading contract
- `app/Repositories/OrderCsvRepository.php` — parsing + row validation
- `app/Services/OrderAnalyticsService.php` — calls repository, revenue + best SKU
- `app/Http/Controllers/Api/OrderAnalyticsController.php` — upload endpoint
- `app/Http/Requests/OrderAnalyticsUploadRequest.php` — composes `OrderCsvFile` + `OrderCsvRequestBodyHeuristic`, `failedValidation` envelope
- `app/Support/OrderUploadConstraints.php` — max upload size, CSV column count, JSON decimals, `Content-Length` tolerance helper
- `app/Validation/Rules/OrderCsvFile.php` — one `.csv` file, extension + MIME + size
- `app/Validation/Rules/OrderCsvRequestBodyHeuristic.php` — duplicate hidden attachments (HTTP size vs visible file)
- `app/Exceptions/Handler.php` — 413, validation, domain errors (JSON)
- `app/Http/Responses/ApiErrorResponse.php` — `error` envelope
- `tests/Fixtures/allsome_interview_test_orders.csv` — sample data
- `example-output.json` — sample output for the sample CSV

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
