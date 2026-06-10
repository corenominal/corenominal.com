# corenominal.com - Metrics

## Overview

The metrics system records a single row to the database for each page view. It is designed to be lightweight and privacy-conscious: IP addresses are anonymised before storage and no persistent identifiers are written to the browser.

---

## Architecture

Collection is split across two layers:

- **Client-side** (`metrics.js`) — runs after page load, gathers browser-side data (path, device type, viewport dimensions, time-to-interactive), and fires a `POST` to `/metrics`
- **Server-side** (`Metrics::receive`) — receives the payload, enriches it with session data and the anonymised IP, and writes one row to the `metrics` table

No third-party services are involved. All data stays on the server.

---

## Database Table

### `metrics`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | Auto-increment |
| `path` | VARCHAR(255) | URL path of the page viewed |
| `user_uuid` | CHAR(36) | Session `user_uuid`; `NULL` for guests |
| `username` | VARCHAR(100) | Session `username`; `'guest'` for unauthenticated visitors |
| `is_admin` | TINYINT | `1` if session `is_admin` is truthy, otherwise `0` |
| `device_type` | VARCHAR(20) | `'mobile'` or `'desktop'` (client-derived) |
| `anonymized_ip` | VARCHAR(45) | Last octet zeroed for IPv4; last 80 bits zeroed for IPv6 |
| `useragent` | TEXT | Full user-agent string |
| `load_time_ms` | INT | `performance.now()` rounded to the nearest millisecond, measured ~500 ms after `load` |
| `window_width` | SMALLINT | Browser viewport width in pixels |
| `window_height` | SMALLINT | Browser viewport height in pixels |
| `created_at` | DATETIME | Server time at insert |

Indexes are present on `created_at`, `path`, and `device_type` to support common reporting queries.

---

## Route

| Method | Path | Handler | Description |
|---|---|---|---|
| POST | `/metrics` | `Metrics::receive` | Receive and store a page-view metric |

The endpoint accepts a plain JSON body and returns `201` on success or `400`/`500` on failure. It requires no authentication — the session is read opportunistically to identify logged-in users, but the request is accepted regardless of authentication state.

---

## Recording Flow

1. The page loads `metrics.js` (included in the `basic-centered` template).
2. After the `load` event fires, a 500 ms `setTimeout` runs to allow `performance.now()` to stabilise.
3. `sendMetrics()` builds the JSON payload:
   - `path` — `window.location.pathname`
   - `deviceType` — `'mobile'` if the user-agent matches `/Mobi|Android/i`, otherwise `'desktop'`
   - `interactiveTime` — `Math.round(performance.now())`
   - `windowWidth` / `windowHeight` — `window.innerWidth` / `window.innerHeight`
4. The payload is sent to `/metrics` via `navigator.sendBeacon` where available, falling back to `fetch` with `keepalive: true`.
5. `Metrics::receive` validates the JSON body; returns `400` if absent.
6. Session values (`user_uuid`, `username`, `is_admin`) are read; guests receive defaults of `null`, `'guest'`, and `0`.
7. The client IP is anonymised (see [IP Anonymisation](#ip-anonymisation)).
8. The CodeIgniter `UserAgent` library parses the `User-Agent` header for the full agent string.
9. A row is inserted via `MetricsModel`. On failure the error is written to the CI log and a `500` is returned.
10. `201 Created` is returned on success.

---

## IP Anonymisation

Performed in `Metrics::anonymizeIp()` before the row is written:

| Protocol | Method |
|---|---|
| IPv4 | Last octet replaced with `0` — e.g. `192.168.1.123` → `192.168.1.0` |
| IPv6 | First three 16-bit segments retained; remainder replaced with `::` — e.g. `2001:db8:85a3::8a2e:370:7334` → `2001:db8:85a3::` |

The raw IP is never persisted.

---

## Key Files

| Path | Description |
|---|---|
| [public/assets/js/common/metrics.js](public/assets/js/common/metrics.js) | Client-side payload collection and dispatch |
| [app/Controllers/Metrics.php](app/Controllers/Metrics.php) | Endpoint handler; IP anonymisation |
| [app/Models/MetricsModel.php](app/Models/MetricsModel.php) | `metrics` table model |
| [app/Database/Migrations/2026-06-10-103052_CreateMetricsTable.php](app/Database/Migrations/2026-06-10-103052_CreateMetricsTable.php) | Table schema and indexes |
| [app/Config/Routes.php](app/Config/Routes.php) | Route registration (`POST /metrics`) |
