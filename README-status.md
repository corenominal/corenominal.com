# corenominal.com - Status System

## Overview

The status system is a microblogging feature — short posts (statuses) displayed on a public timeline, optionally accompanied by media attachments. Posts can be cross-posted to Mastodon. An internal drafts system allows posts to be saved before publishing. The system is split into public web views, an admin panel, and a REST API.

---

## Architecture

The system has three distinct tiers:

- **Public web** — the timeline and individual status pages, accessible to all visitors
- **Admin web** — statistics dashboard and data export, restricted to administrators
- **REST API** — all write operations (create, update, delete) plus drafts and media management; protected by the `ApiFilter`

---

## Database Tables

| Table | Purpose |
|---|---|
| `statuses` | Published status posts |
| `status_media` | Uploaded media attachments |
| `status_drafts` | Unpublished draft posts |

### `statuses`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | |
| `uuid` | VARCHAR(36) | Indexed; used as the public permalink identifier |
| `content` | TEXT | Raw source text (Markdown) |
| `content_html` | TEXT | Rendered HTML |
| `media_ids` | TEXT | JSON-encoded array of `status_media.id` values |
| `mastodon_id` | VARCHAR(255) | Mastodon status ID; null if not cross-posted |
| `in_reply_to_id` | VARCHAR(255) | Mastodon reply-to ID; null if not a reply |
| `mastodon_url` | VARCHAR(512) | Public URL of the Mastodon post |
| `created_at / updated_at / deleted_at` | DATETIME | Soft deletes enabled |

`media_ids` is stored as a JSON string (e.g. `[1, 4, 7]`) and decoded automatically by `StatusModel` callbacks on read.

### `status_media`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | |
| `uuid` | VARCHAR(36) | Indexed |
| `file_name` | VARCHAR(255) | UUID-prefixed filename, e.g. `{uuid}.jpg` |
| `description` | TEXT | Alt text / caption |
| `file_ext` | VARCHAR(20) | Extension derived from MIME type |
| `mime_type` | VARCHAR(100) | `image/jpeg`, `image/png`, `image/gif`, `image/webp`, `video/mp4` |
| `width` | INT | Pixels; 0 for video |
| `height` | INT | Pixels; 0 for video |
| `filesize` | INT UNSIGNED | Bytes (post-processing) |
| `created_at / updated_at` | DATETIME | No soft deletes |

Files are stored on disk at `public/uploads/status/media/`.

### `status_drafts`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | |
| `uuid` | VARCHAR(36) | Indexed |
| `content` | TEXT | Raw Markdown; nullable |
| `media_ids` | TEXT | JSON-encoded array of `status_media.id` values |
| `created_at / updated_at` | DATETIME | No soft deletes |

Drafts are not published and have no `content_html` — HTML conversion happens only at publish time.

---

## Routes

### Public

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/status` | `Status\Home::index` | Timeline (paginated) |
| GET | `/status/feed/rss` | `Status\Feed::rss` | RSS feed (latest 20) |
| GET | `/status/timeline/load` | `Status\Home::loadMoreStatuses` | Infinite-scroll batch |
| GET | `/status/(:segment)` | `Status\Home::show/$1` | Single status by UUID |

### Admin (requires `AdminFilter`)

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/admin/status` | `Status\Admin\Home::index` | Stats dashboard |
| GET | `/admin/status/export` | `Status\Admin\Export::index` | Export UI |
| GET | `/admin/status/export/(:segment)` | `Status\Admin\Export::download/$1` | Download export file |

### API (requires `ApiFilter`)

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/api/status/ping` | `Status\Api\Test::ping` | Health check |
| GET | `/api/status/statuses/latest` | `Status\Api\Statuses::latest` | Paginated status list (public data) |
| GET | `/api/status/statuses/(:num)` | `Status\Api\Statuses::get/$1` | Single status by ID (admin only) |
| POST | `/api/status/statuses` | `Status\Api\Statuses::create` | Create status (admin only) |
| PATCH | `/api/status/statuses/(:num)` | `Status\Api\Statuses::update/$1` | Update status (admin only) |
| DELETE | `/api/status/statuses/(:num)` | `Status\Api\Statuses::delete/$1` | Delete status (admin only) |
| POST | `/api/status/media` | `Status\Api\Media::upload` | Upload media (admin only) |
| DELETE | `/api/status/media/(:num)` | `Status\Api\Media::delete/$1` | Delete media (admin only) |
| GET | `/api/status/drafts` | `Status\Api\Drafts::index` | List drafts (admin only) |
| POST | `/api/status/drafts` | `Status\Api\Drafts::create` | Create draft (admin only) |
| PATCH | `/api/status/drafts/(:num)` | `Status\Api\Drafts::update/$1` | Update draft (admin only) |
| DELETE | `/api/status/drafts/(:num)` | `Status\Api\Drafts::delete/$1` | Delete draft (admin only) |

There is also a separate AI route at `/api/status/rewrite` (`Api\Ai\Status::rewrite`) documented below.

---

## Public Timeline

`Status\Home::index` fetches the first 20 statuses, newest first, and renders `status/home`. Infinite scroll is driven by `Status\Home::loadMoreStatuses`, which returns a JSON payload containing pre-rendered HTML (via the `status/partials/timeline_items` partial), the next offset, and a `hasMore` flag. The client JS (`status/home.js`) appends the HTML fragment directly.

A search filter is supported via the `q` query parameter, which performs a `LIKE` match against both `content` and `content_html`.

If the current session user is an administrator, a draft count badge is shown in the UI.

---

## Single Status Page

`Status\Home::show` looks up a status by UUID. A 404 is thrown if the UUID is not found. Media attachments are hydrated inline before the view is rendered.

---

## RSS Feed

`Status\Feed::rss` returns the 20 most recent statuses as an RSS 2.0 feed (`application/rss+xml`). Media attachments are included in each item. The feed URL, site name, and base URL are pulled from `App` config.

---

## Admin Dashboard

`Status\Admin\Home::index` computes summary statistics and a 12-month activity chart, then renders `status/admin/home`. The monthly activity query runs directly against the database (not through the model) to use `DATE_FORMAT`. No write operations occur here.

---

## Data Export

`Status\Admin\Export::download` accepts a format segment and streams a file download. Three formats are supported:

| Format | Filename | Content |
|---|---|---|
| `json` | `statuses-YYYY-MM-DD.json` | Full records as pretty-printed JSON |
| `sql` | `statuses-YYYY-MM-DD.sql` | `CREATE TABLE` + `INSERT INTO` dump |
| `ai` | `statuses-ai-YYYY-MM-DD.txt` | Plain text prompt for LLM writing-style analysis |

All formats export every published status ordered oldest-first. No pagination — the full dataset is loaded into memory.

---

## API: Creating a Status

1. Client POSTs `content` (Markdown text) and optional `media_ids[]` and `post_to_mastodon=1` to `/api/status/statuses`.
2. `$GLOBALS['is_admin']` must be truthy (set by `ApiFilter`); otherwise `403` is returned.
3. `content` is converted to HTML by the `Markdown` library. On failure it falls back to escaped paragraphs with `nl2br`.
4. A UUID v4 is generated and the row is inserted into `statuses`.
5. If `post_to_mastodon=1` and `MastodonPoster::isEnabled()` returns true, the post is sent to Mastodon. On success, `mastodon_id` and `mastodon_url` are written back to the record. Mastodon failures are logged but do not fail the request.
6. Returns `201` with the created record.

---

## API: Updating a Status

1. Client sends PATCH with `content` and/or `media_ids` (POST fields or JSON body; both are merged).
2. If the status has a `mastodon_id` and either `content` or `media_ids` changed, a Mastodon update is attempted. Failures are logged but do not fail the request.
3. Returns the updated record.

---

## API: Deleting a Status

1. Any media files attached to the status are removed from disk and soft-deleted from `status_media`.
2. If the status has a `mastodon_id`, the Mastodon post is deleted. Failures are logged but do not fail the request.
3. The status row is soft-deleted in `statuses`.
4. Returns `{"status": "success"}`.

---

## Media Upload

`Status\Api\Media::upload` handles `multipart/form-data` uploads.

- Allowed MIME types: `image/jpeg`, `image/png`, `image/gif`, `image/webp`, `video/mp4`
- Maximum file size: 500 MB
- Maximum image width: 1920 px (images wider than this are resized proportionally)
- JPEG EXIF orientation is corrected automatically before saving
- The file is stored as `{uuid}.{ext}` in `public/uploads/status/media/`
- Width, height, and filesize are recorded in `status_media`
- Videos skip image processing; width and height are stored as 0

Returns `201` with the new `status_media` record on success.

---

## Drafts

Drafts are stored in `status_drafts` and managed entirely through the API. They hold raw Markdown content and media IDs but are never rendered to HTML — that conversion happens when a draft is published as a status. Deleting a draft does not delete any attached media.

---

## Mastodon Integration

Cross-posting is handled by the `MastodonPoster` library. Integration is optional: `MastodonPoster::isEnabled()` returns false when the credentials are not configured, and all Mastodon calls are skipped silently.

Configuration is in [app/Config/Mastodon.php](app/Config/Mastodon.php) (values set via `.env`):

| Setting | Description |
|---|---|
| `$url` | Mastodon instance base URL |
| `$apiv1` / `$apiv2` | API endpoint paths |
| `$access_token` | OAuth access token |
| `$account` | `@handle@instance` string displayed in the UI |
| `$profile` | Profile URL displayed in the UI |
| `$displayname` | Display name shown in the UI |

---

## AI Rewrite

`Api\Ai\Status::rewrite` at `POST /api/status/rewrite` accepts a `text` string and an optional `expand` flag, then proxies a request to a local Ollama instance to generate 5 alternative rewrites. The response is `{"suggestions": [...]}`. The Ollama host is configured in `app/Config/Ollama.php` (set via `.env`). This route sits under the standard `ApiFilter`.

---

## `media_ids` Serialisation

`media_ids` is stored as a JSON string in the database. Both `StatusModel` and `StatusDraftModel` handle the conversion transparently via model callbacks:

- `encodeMediaIds` (before insert/update) — converts a PHP array to a JSON string
- `decodeMediaIds` (after find) — converts the JSON string back to a PHP array

Controllers always pass and receive `media_ids` as a PHP array.

---

## Key Files

| Path | Description |
|---|---|
| [app/Controllers/Status/Home.php](app/Controllers/Status/Home.php) | Public timeline, single status, infinite-scroll endpoint |
| [app/Controllers/Status/Feed.php](app/Controllers/Status/Feed.php) | RSS feed |
| [app/Controllers/Status/Admin/Home.php](app/Controllers/Status/Admin/Home.php) | Admin stats dashboard |
| [app/Controllers/Status/Admin/Export.php](app/Controllers/Status/Admin/Export.php) | Data export (JSON, SQL, AI) |
| [app/Controllers/Status/Api/Statuses.php](app/Controllers/Status/Api/Statuses.php) | CRUD API for statuses |
| [app/Controllers/Status/Api/Media.php](app/Controllers/Status/Api/Media.php) | Media upload and delete |
| [app/Controllers/Status/Api/Drafts.php](app/Controllers/Status/Api/Drafts.php) | Draft CRUD |
| [app/Controllers/Status/Api/Test.php](app/Controllers/Status/Api/Test.php) | Health check ping |
| [app/Controllers/Api/Ai/Status.php](app/Controllers/Api/Ai/Status.php) | AI rewrite via Ollama |
| [app/Models/StatusModel.php](app/Models/StatusModel.php) | Status records with soft deletes and media_ids callbacks |
| [app/Models/StatusMediaModel.php](app/Models/StatusMediaModel.php) | Media attachment records |
| [app/Models/StatusDraftModel.php](app/Models/StatusDraftModel.php) | Draft records with media_ids callbacks |
| [app/Libraries/MastodonPoster.php](app/Libraries/MastodonPoster.php) | Mastodon cross-posting |
| [app/Libraries/Markdown.php](app/Libraries/Markdown.php) | Markdown-to-HTML conversion |
| [app/Config/Mastodon.php](app/Config/Mastodon.php) | Mastodon credentials and display config |
| [public/assets/js/status/home.js](public/assets/js/status/home.js) | Client-side timeline (infinite scroll, search) |
| [public/assets/js/status/admin/home.js](public/assets/js/status/admin/home.js) | Admin dashboard JS |
| [public/assets/js/status/admin/export.js](public/assets/js/status/admin/export.js) | Export page JS |
| [public/assets/css/status/timeline.css](public/assets/css/status/timeline.css) | Timeline styles |
| [public/uploads/status/media/](public/uploads/status/media/) | Uploaded media files |
