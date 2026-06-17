# corenominal.com - Startpage System

## Overview

The startpage system is a personal browser new-tab page with a command bar. Typing a query routes it to a matching redirect, a configured search engine, or falls back to Google. Slash-prefixed queries run server-side network diagnostics (ping, whois, dig, etc.) and return inline HTML. The page also displays configurable shortcut tiles organized into categories. All management — redirects, search engines, shortcuts, history, and import/export — is handled through the admin panel.

---

## Architecture

The system has two tiers:

- **Admin web** — all pages and management endpoints, restricted to administrators via `AdminFilter`
- **REST API** — a single endpoint to create redirects externally, protected by `ApiFilter`

---

## Database Tables

| Table | Purpose |
|---|---|
| `start_history` | Every query submitted, with a use count |
| `start_redirects` | Exact-phrase → URL redirect rules |
| `start_search` | Prefix-based search engine rules |
| `start_shortcut_categories` | Groups for shortcut tiles |
| `start_shortcuts` | Individual shortcut tiles |

### `start_history`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | |
| `q` | VARCHAR | The query string |
| `count` | INT | Incremented on each repeated use |
| `created_at / updated_at` | DATETIME | No soft deletes |

### `start_redirects`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | |
| `phrase` | VARCHAR | Exact match trigger; unique |
| `url` | VARCHAR | Destination URL |
| `comments` | TEXT | Optional notes |
| `created_at / updated_at / deleted_at` | DATETIME | Soft deletes enabled |

### `start_search`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | |
| `phrase` | VARCHAR | Prefix keyword (first word of query) |
| `url` | VARCHAR | Search URL with `%s` placeholder |
| `comments` | TEXT | Optional notes |
| `created_at / updated_at / deleted_at` | DATETIME | Soft deletes enabled |

### `start_shortcut_categories`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | |
| `name` | VARCHAR | Display name |
| `sort_order` | INT | Display order |
| `created_at / updated_at / deleted_at` | DATETIME | Soft deletes enabled |

### `start_shortcuts`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | |
| `category_id` | INT | Foreign key to `start_shortcut_categories` |
| `name` | VARCHAR | Display label |
| `url` | VARCHAR | Link destination |
| `icon_filename` | VARCHAR | Filename in `public/uploads/startpage/icons/`; empty string if no icon |
| `icon_invert` | TINYINT | `1` = CSS `invert()` filter applied in dark mode |
| `icon_invert_light` | TINYINT | `1` = CSS `invert()` filter applied in light mode |
| `sort_order` | INT | Display order within the category |
| `created_at / updated_at / deleted_at` | DATETIME | Soft deletes enabled |

---

## Routes

### Admin (requires `AdminFilter`)

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/admin/startpage/` | `Startpage\Admin\Home::index` | Main startpage |
| POST | `/admin/startpage/command` | `Startpage\Admin\Home::command` | AJAX command dispatcher |
| GET | `/admin/startpage/history/suggestions` | `Startpage\Admin\Home::historySuggestions` | Typeahead suggestions |
| GET | `/admin/startpage/history` | `Startpage\Admin\History::index` | History list view |
| POST | `/admin/startpage/history/delete` | `Startpage\Admin\History::delete` | Bulk delete history entries |
| GET | `/admin/startpage/redirects` | `Startpage\Admin\Redirects::index` | Redirects list view |
| POST | `/admin/startpage/redirects/add` | `Startpage\Admin\Redirects::add` | Add redirect |
| POST | `/admin/startpage/redirects/edit` | `Startpage\Admin\Redirects::edit` | Update redirect |
| POST | `/admin/startpage/redirects/delete` | `Startpage\Admin\Redirects::delete` | Bulk delete redirects |
| GET | `/admin/startpage/search` | `Startpage\Admin\Search::index` | Search engines list view |
| POST | `/admin/startpage/search/add` | `Startpage\Admin\Search::add` | Add search engine |
| POST | `/admin/startpage/search/edit` | `Startpage\Admin\Search::edit` | Update search engine |
| POST | `/admin/startpage/search/delete` | `Startpage\Admin\Search::delete` | Bulk delete search engines |
| GET | `/admin/startpage/shortcuts` | `Startpage\Admin\Shortcuts::index` | Shortcuts management view |
| POST | `/admin/startpage/shortcuts/category/add` | `Startpage\Admin\Shortcuts::categoryAdd` | Add category |
| POST | `/admin/startpage/shortcuts/category/edit` | `Startpage\Admin\Shortcuts::categoryEdit` | Rename category |
| POST | `/admin/startpage/shortcuts/category/delete` | `Startpage\Admin\Shortcuts::categoryDelete` | Delete category and its shortcuts |
| POST | `/admin/startpage/shortcuts/category/reorder` | `Startpage\Admin\Shortcuts::categoryReorder` | Reorder categories |
| POST | `/admin/startpage/shortcuts/add` | `Startpage\Admin\Shortcuts::shortcutAdd` | Add shortcut |
| POST | `/admin/startpage/shortcuts/edit` | `Startpage\Admin\Shortcuts::shortcutEdit` | Update shortcut |
| POST | `/admin/startpage/shortcuts/delete` | `Startpage\Admin\Shortcuts::shortcutDelete` | Delete shortcut |
| POST | `/admin/startpage/shortcuts/reorder` | `Startpage\Admin\Shortcuts::shortcutReorder` | Reorder shortcuts within a category |
| GET | `/admin/startpage/import-export` | `Startpage\Admin\ImportExport::index` | Import/export UI |
| GET | `/admin/startpage/export/history` | `Startpage\Admin\ImportExport::exportHistory` | Download history JSON |
| GET | `/admin/startpage/export/redirects` | `Startpage\Admin\ImportExport::exportRedirects` | Download redirects JSON |
| GET | `/admin/startpage/export/search` | `Startpage\Admin\ImportExport::exportSearch` | Download search engines JSON |
| POST | `/admin/startpage/import/history` | `Startpage\Admin\ImportExport::importHistory` | Import history from JSON |
| POST | `/admin/startpage/import/redirects` | `Startpage\Admin\ImportExport::importRedirects` | Import redirects from JSON |
| POST | `/admin/startpage/import/search` | `Startpage\Admin\ImportExport::importSearch` | Import search engines from JSON |

### API (requires `ApiFilter`)

| Method | Path | Handler | Description |
|---|---|---|---|
| POST | `/api/startpage/redirects` | `Startpage\Api\Redirects::create` | Create redirect (admin only) |

---

## Main Startpage

`Startpage\Admin\Home::index` loads all data needed to render the page in one request: the 50 most recent history entries, all redirects, all search engines, and all shortcut categories with their shortcuts (sorted by `sort_order`). The page is designed to be set as a browser new-tab URL.

The GET handler also supports being used as a browser search bar URL. If a `?q=` parameter is present, it runs the same redirect/search-engine lookup logic described below and issues a PHP redirect response instead of rendering the page.

---

## Command Dispatch (AJAX)

`POST /admin/startpage/command` accepts `{"q": "..."}` as JSON, logs the query to history, and returns one of three response shapes:

| Key | Effect |
|---|---|
| `url` | Client navigates to the URL |
| `html` | Client renders the HTML below the search bar |
| `notification` | Client shows a Bootstrap toast |

The query is always echoed back as `q` in the response.

### Query Resolution Order (`processQuery`)

1. If `q` starts with `https://` or `http://` and passes URL validation → return the URL directly.
2. If `q` starts with `/` → dispatch to `processCommand()` (see below).
3. Look up `q` as an exact match in `start_redirects` → return the redirect URL.
4. If `q` contains a space, take the first word and look it up in `start_search` → replace `%s` in the engine URL with `urlencode` of the remaining text.
5. Fallback → Google search (`https://www.google.com/search?q=...`).

### Commands (`processCommand`)

Slash-prefixed commands run server-side tools via `shell_exec`. All host/domain arguments are passed through `escapeshellarg()`.

| Input | Action |
|---|---|
| `/ping` | Returns `pong!` |
| `/hello` | Returns `Hello, World!` |
| `/ping <host>` | `ping -c 5 <host>` |
| `/whois <domain>` | `whois <domain>` |
| `/dig <domain>` | `dig <domain>` |
| `/headers <url>` | `curl --head <url>` |
| `/traceroute <host>` | `traceroute -w 2 -q 1 -m 20 <host>` (15 s timeout) |
| `/mx <domain>` | `dig MX <domain>` |
| `/ns <domain>` | `dig NS <domain>` |
| `/rdns <ip>` | `dig -x <ip>` |
| `/ssl <host>` | `openssl s_client -connect <host>:443` |

Output is returned as `<pre><code>` HTML. Unrecognised commands return `Unrecognised command.`

---

## Typeahead Suggestions

`GET /admin/startpage/history/suggestions?q=...` returns up to 10 history entries whose `q` field contains the search term, ordered by `count` DESC then `updated_at` DESC, as a plain JSON array of strings.

The client JS debounces input by 200 ms, fetches suggestions, and renders a `#q-typeahead` dropdown (`.q-typeahead__item` elements). Arrow keys navigate the list; Enter selects; Escape dismisses.

When the typeahead dropdown is hidden, ArrowUp/ArrowDown on the input cycles through the 50 most recent history entries loaded on page load.

---

## History

Every submitted query is upserted into `start_history`: new queries are inserted with `count = 1`; repeated queries increment the existing `count` and update `updated_at` (via the model's timestamp handling).

The dedicated history page (`/admin/startpage/history`) shows all entries ordered by `updated_at` DESC. The main page shows the 50 most recent. Both support multi-row selection (checkboxes, "select all" header) and bulk delete via a confirmation modal.

---

## Redirects

Redirects map an exact phrase to a destination URL. Phrases must be unique — a duplicate phrase returns `409`. Soft-deleted rows are excluded from lookup during query resolution but are included in exports.

---

## Search Engines

Search engine rules use a keyword prefix. When a query contains a space, the first word is matched against `start_search.phrase`. On a match, the rest of the query replaces the `%s` placeholder in `start_search.url` after URL-encoding.

Example: phrase `gh`, url `https://github.com/search?q=%s`, query `gh codeigniter` → `https://github.com/search?q=codeigniter`.

---

## Shortcuts

Shortcuts are link tiles displayed on the main startpage, grouped into categories.

**Categories** support add, rename, delete (cascade-deletes all shortcuts in the category), and drag-and-drop reorder. `sort_order` is updated as 1-based positions on each reorder.

**Shortcuts** support add, edit, delete, and drag-and-drop reorder within their category. Fields:

- `name` — display label
- `url` — link destination
- `icon_filename` — optional icon image
- `icon_invert` / `icon_invert_light` — CSS invert filter flags for dark/light mode icon adjustments
- `sort_order` — position within the category

**Icon handling:**

- Accepted types: PNG, JPEG, GIF, WebP, SVG, ICO; maximum 512 KB
- Icons are stored as `<random_name>.<ext>` in `public/uploads/startpage/icons/`
- When adding or editing a shortcut, an icon already used by another shortcut can be selected from a picker instead of uploading a new file
- When a shortcut is deleted (or its icon replaced), the icon file is deleted from disk only if no other shortcut references it (reference-counted by filename)

A "open shortcuts in new tab" toggle on the main page persists its state in `localStorage` under the key `shortcuts_new_tab`.

---

## Import / Export

History, redirects, and search engines can each be exported as a JSON file and re-imported. Imports are upserts:

| Dataset | Match key | On match | On new |
|---|---|---|---|
| History | `q` | Updates `count` and `updated_at` | Inserts with original timestamps |
| Redirects | `phrase` | Updates `url`, `comments`, `updated_at`, `deleted_at` | Inserts with original timestamps |
| Search engines | `phrase` | Updates `url`, `comments`, `updated_at`, `deleted_at` | Inserts with original timestamps |

Upload constraints: `.json` extension only, maximum 10 MB. The redirects and search export uses `withDeleted()` to include soft-deleted rows.

---

## Key Files

| Path | Description |
|---|---|
| [app/Controllers/Startpage/Admin/Home.php](app/Controllers/Startpage/Admin/Home.php) | Main startpage, command dispatch, typeahead |
| [app/Controllers/Startpage/Admin/History.php](app/Controllers/Startpage/Admin/History.php) | History list view and bulk delete |
| [app/Controllers/Startpage/Admin/Redirects.php](app/Controllers/Startpage/Admin/Redirects.php) | Redirects CRUD |
| [app/Controllers/Startpage/Admin/Search.php](app/Controllers/Startpage/Admin/Search.php) | Search engines CRUD |
| [app/Controllers/Startpage/Admin/Shortcuts.php](app/Controllers/Startpage/Admin/Shortcuts.php) | Shortcuts and categories CRUD, icon upload/reference-counting |
| [app/Controllers/Startpage/Admin/ImportExport.php](app/Controllers/Startpage/Admin/ImportExport.php) | Import/export for history, redirects, and search engines |
| [app/Controllers/Startpage/Api/Redirects.php](app/Controllers/Startpage/Api/Redirects.php) | External API: create redirect |
| [app/Models/StartHistoryModel.php](app/Models/StartHistoryModel.php) | History records |
| [app/Models/StartRedirectsModel.php](app/Models/StartRedirectsModel.php) | Redirect records (soft deletes) |
| [app/Models/StartSearchModel.php](app/Models/StartSearchModel.php) | Search engine records (soft deletes) |
| [app/Models/StartShortcutCategoryModel.php](app/Models/StartShortcutCategoryModel.php) | Shortcut category records (soft deletes) |
| [app/Models/StartShortcutModel.php](app/Models/StartShortcutModel.php) | Shortcut records (soft deletes) |
| [public/assets/js/startpage/admin/home.js](public/assets/js/startpage/admin/home.js) | Main page JS: command dispatch, history navigation, typeahead, new-tab toggle |
| [public/assets/js/startpage/admin/history.js](public/assets/js/startpage/admin/history.js) | History page JS |
| [public/assets/js/startpage/admin/redirects.js](public/assets/js/startpage/admin/redirects.js) | Redirects page JS |
| [public/assets/js/startpage/admin/search.js](public/assets/js/startpage/admin/search.js) | Search engines page JS |
| [public/assets/js/startpage/admin/shortcuts.js](public/assets/js/startpage/admin/shortcuts.js) | Shortcuts page JS (drag-and-drop reorder, icon picker) |
| [public/assets/js/startpage/admin/import_export.js](public/assets/js/startpage/admin/import_export.js) | Import/export page JS |
| [public/assets/css/startpage/admin/home.css](public/assets/css/startpage/admin/home.css) | Main startpage styles (includes typeahead dropdown) |
| [public/uploads/startpage/icons/](public/uploads/startpage/icons/) | Uploaded shortcut icon files |
