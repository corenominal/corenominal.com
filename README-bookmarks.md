# corenominal.com - Bookmarks System

## Overview

The bookmarks system is a curated link collection — saved URLs enriched with titles, notes (Markdown), tags, and images, displayed on a public page with infinite scroll. Bookmarks can be pinned to a dashboard/startpage view. The system is split into public web views, an admin panel, and a REST API.

---

## Architecture

The system has three distinct tiers:

- **Public web** — the listing page and individual bookmark pages, accessible to all visitors (private bookmarks are hidden)
- **Admin web** — full CRUD management with bulk operations and a live-preview form
- **REST API** — all write operations plus tag listing, markdown preview, and screenshot services; protected by the `ApiFilter`

---

## Database Tables

| Table | Purpose |
|---|---|
| `bookmarks` | Saved bookmark records |
| `bookmarks_tags` | Tag junction table |

### `bookmarks`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | |
| `uuid` | VARCHAR(36) | Public permalink identifier |
| `title` | VARCHAR(255) | Plain-text title |
| `title_html` | TEXT | Rendered HTML title |
| `url` | TEXT | The bookmarked URL |
| `favicon` | VARCHAR(512) | URL to the site's favicon (Google S2 proxy) |
| `notes` | TEXT | Raw Markdown notes; nullable |
| `notes_html` | TEXT | Rendered HTML notes |
| `tags` | TEXT | Comma-separated tag string (denormalised copy) |
| `image` | VARCHAR(512) | Filename of the associated image; nullable |
| `private` | TINYINT(1) | 1 = hidden from public listing |
| `dashboard` | TINYINT(1) | 1 = pinned to dashboard/startpage |
| `hitcounter` | INT | Public view count (admin reads excluded) |
| `created_at / updated_at / deleted_at` | DATETIME | Soft deletes enabled |

### `bookmarks_tags`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | |
| `bookmark_id` | INT | FK to `bookmarks.id`; indexed |
| `tag` | VARCHAR(100) | Display name of the tag |
| `slug` | VARCHAR(110) | URL-safe slug; indexed |
| `created_at / updated_at` | DATETIME | No soft deletes |

Tags are stored both in the `bookmarks_tags` junction table (for querying) and as a denormalised comma-separated string in `bookmarks.tags` (for display without a join).

Images are stored on disk at `public/uploads/bookmarks/media/`.

---

## Routes

### Public

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/bookmarks` | `Bookmarks\Home::index` | Bookmark listing (paginated) |
| GET | `/bookmarks/load` | `Bookmarks\Home::loadMore` | Infinite-scroll batch |
| GET | `/bookmarks/feed/rss` | `Bookmarks\Feed::rss` | RSS feed (latest 20 public) |
| GET | `/bookmarks/(:segment)` | `Bookmarks\Home::show/$1` | Single bookmark by UUID |

### Admin (requires `AdminFilter`)

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/admin/bookmarks` | `Bookmarks\Admin\Home::index` | Management table with stats |
| POST | `/admin/bookmarks/delete` | `Bookmarks\Admin\Home::delete` | Bulk delete |
| GET | `/admin/bookmarks/create` | `Bookmarks\Admin\BookmarkForm::create` | New bookmark form |
| GET | `/admin/bookmarks/(:segment)/edit` | `Bookmarks\Admin\BookmarkForm::edit/$1` | Edit form by UUID |

### API (requires `ApiFilter`)

| Method | Path | Handler | Description |
|---|---|---|---|
| POST | `/api/bookmarks` | `Bookmarks\Api\Bookmarks::create` | Create bookmark |
| PUT | `/api/bookmarks/(:segment)` | `Bookmarks\Api\Bookmarks::update/$1` | Update bookmark by UUID |
| GET | `/api/bookmarks/latest` | `Bookmarks\Api\Bookmarks::latest` | Paginated public bookmark list |
| GET | `/api/bookmarks/check-url` | `Bookmarks\Api\Bookmarks::checkUrl` | Check if a URL already exists |
| GET | `/api/bookmarks/tags` | `Bookmarks\Api\Tags::index` | All distinct tags alphabetically |
| POST | `/api/bookmarks/markdown/preview` | `Bookmarks\Api\MarkdownPreview::convert` | Convert Markdown to HTML |
| GET | `/api/bookmarks/screenshot/preview` | `Bookmarks\Api\ScreenshotPreview::url` | Signed ScreenshotOne preview URL |
| POST | `/api/bookmarks/screenshot/capture` | `Bookmarks\Api\ScreenshotPreview::capture` | Capture and save screenshot |

---

## Public Listing

`Bookmarks\Home::index` fetches the first 20 public bookmarks, newest first, and renders `bookmarks/home`. Infinite scroll is driven by `Bookmarks\Home::loadMore`, which returns a JSON payload containing pre-rendered HTML (via the `bookmarks/partials/bookmark_items` partial), the next offset, and a `hasMore` flag. The client JS (`bookmarks/home.js`) appends the HTML fragment directly.

A search filter is supported via the `q` query parameter, which performs a `LIKE` match against `title`, `notes`, and `tags`.

---

## Single Bookmark Page

`Bookmarks\Home::show` looks up a bookmark by UUID. A 404 is thrown if the UUID is not found or the bookmark is private and the visitor is not an administrator. The hit counter is incremented on each visit unless the current session user is an administrator.

---

## RSS Feed

`Bookmarks\Feed::rss` returns the 20 most recent public bookmarks as an RSS 2.0 feed (`application/rss+xml`). Each item includes the title, URL, and `notes_html` as description.

---

## Admin Dashboard

`Bookmarks\Admin\Home::index` renders a paginated table (25 per page) of all bookmarks with summary statistics — total count, public count, private count, and total views. A search field filters across title, URL, and notes. Rows are selectable for bulk delete. Per-row edit and delete actions are also available.

---

## Bulk Delete

`Bookmarks\Admin\Home::delete` accepts a `POST` request with a JSON body containing an `ids` array of bookmark IDs. Each bookmark is soft-deleted. The operation is admin-only; access is enforced at the route level via `AdminFilter`. The admin JS triggers a confirmation modal before submitting.

---

## API: Creating a Bookmark

1. Client POSTs `title`, `url`, and optional fields (`notes`, `tags[]`, `private`, `dashboard`, `image_file`) to `/api/bookmarks`.
2. The URL is validated as a well-formed URL.
3. The favicon is fetched via the Google S2 favicon proxy using the URL hostname.
4. `notes` is converted to HTML by the `Markdown` library if provided.
5. Tags are normalised: each tag is slugified and written to `bookmarks_tags`; the comma-separated string is stored in `bookmarks.tags`.
6. If `image_file` is provided, it is saved as `{uuid}.jpg` in `public/uploads/bookmarks/media/`.
7. If no image is provided and the URL is a YouTube video, the YouTube thumbnail is downloaded and saved automatically.
8. If no image is provided and the bookmark has an `inspiration` tag, the ScreenshotOne API is called to capture and save a screenshot.
9. Returns `201` with the created record.

---

## API: Updating a Bookmark

1. Client sends `PUT` with any combination of `title`, `url`, `notes`, `tags[]`, `private`, `dashboard`, `image_file`.
2. If tags are included, all existing `bookmarks_tags` rows for the bookmark are deleted and replaced.
3. Image, favicon, and Markdown processing follow the same logic as create.
4. Returns the updated record.

---

## API: Checking a URL

`Bookmarks\Api\Bookmarks::checkUrl` accepts a `url` query parameter and returns `{"exists": true}` or `{"exists": false}`. Used by the admin form to warn before saving a duplicate URL.

---

## Tag Listing

`Bookmarks\Api\Tags::index` queries `bookmarks_tags` for all distinct `tag` values and returns them as a JSON array sorted alphabetically. Used by the admin form to populate the tag autocomplete datalist.

---

## Screenshot Service

Screenshot capture is handled via the ScreenshotOne API, configured in [app/Config/ScreenshotOne.php](app/Config/ScreenshotOne.php).

- `Bookmarks\Api\ScreenshotPreview::url` — returns a signed preview URL for live preview in the admin form (HMAC-signed if a secret key is configured).
- `Bookmarks\Api\ScreenshotPreview::capture` — calls the ScreenshotOne API, downloads the resulting image, and saves it as `{uuid}.jpg` in `public/uploads/bookmarks/media/`. Returns the filename on success.

Screenshots are captured automatically on create/update when the bookmark carries an `inspiration` tag and no image has been supplied.

---

## Admin Bookmark Form

The create and edit views (`bookmarks/admin/bookmark_form.php`) use a two-column layout: the form on the left, a live preview panel on the right. All preview updates are driven by `public/assets/js/bookmarks/admin/bookmark-form.js`.

Key behaviours:

- **Favicon** — fetched debounced from Google S2 as the URL field changes; displayed in the preview.
- **Screenshot preview** — shown debounced in the preview panel when the bookmark has an `inspiration` tag (or the URL is a YouTube video), using the signed ScreenshotOne preview URL.
- **YouTube thumbnails** — if the URL is a YouTube video, the video thumbnail is displayed in the preview instead of a screenshot.
- **Notes preview** — Markdown is converted to HTML via `POST /api/bookmarks/markdown/preview` as the notes field changes.
- **Tags** — added via Enter or comma key, with autocomplete from the tag datalist; displayed as badge pills; removed by clicking the badge.
- **Submit** — POSTs to the create endpoint or PUTs to the update endpoint with the API key header. On successful create, redirects to the edit form with a `?created=1` success banner.

---

## Auto-Enrichment Summary

| Trigger | Action |
|---|---|
| URL is a YouTube video | Thumbnail downloaded and saved as the bookmark image |
| Bookmark has `inspiration` tag and no image | ScreenshotOne screenshot captured and saved |
| URL provided | Favicon fetched from Google S2 and stored |
| Notes provided | Markdown converted to HTML on save |

---

## Privacy

Bookmarks with `private = 1` are excluded from the public listing, search results, RSS feed, and the `/api/bookmarks/latest` response. Administrators can see private bookmarks in the admin panel. Visiting the permalink of a private bookmark while not logged in as an administrator returns a 404.

---

## Dashboard Pinning

Bookmarks with `dashboard = 1` are intended to be surfaced on a separate startpage or dashboard view. The flag is toggled via the admin form.

---

## Hit Counter

`bookmarks.hitcounter` is incremented each time `Bookmarks\Home::show` is called by a non-admin visitor. The count is visible to administrators in the admin listing. It is not displayed publicly.

---

## Key Files

| Path | Description |
|---|---|
| [app/Controllers/Bookmarks/Home.php](app/Controllers/Bookmarks/Home.php) | Public listing, single bookmark, infinite-scroll endpoint |
| [app/Controllers/Bookmarks/Feed.php](app/Controllers/Bookmarks/Feed.php) | RSS feed |
| [app/Controllers/Bookmarks/Admin/Home.php](app/Controllers/Bookmarks/Admin/Home.php) | Admin management table and bulk delete |
| [app/Controllers/Bookmarks/Admin/BookmarkForm.php](app/Controllers/Bookmarks/Admin/BookmarkForm.php) | Create and edit form views |
| [app/Controllers/Bookmarks/Api/Bookmarks.php](app/Controllers/Bookmarks/Api/Bookmarks.php) | CRUD API, URL check, auto-enrichment logic |
| [app/Controllers/Bookmarks/Api/Tags.php](app/Controllers/Bookmarks/Api/Tags.php) | Tag listing API |
| [app/Controllers/Bookmarks/Api/MarkdownPreview.php](app/Controllers/Bookmarks/Api/MarkdownPreview.php) | Markdown-to-HTML preview API |
| [app/Controllers/Bookmarks/Api/ScreenshotPreview.php](app/Controllers/Bookmarks/Api/ScreenshotPreview.php) | Screenshot preview URL and capture API |
| [app/Models/BookmarkModel.php](app/Models/BookmarkModel.php) | Bookmark records with soft deletes |
| [app/Models/BookmarkTagModel.php](app/Models/BookmarkTagModel.php) | Tag junction table records |
| [app/Helpers/bookmark_helper.php](app/Helpers/bookmark_helper.php) | `bookmark_with_tags()` and `bookmark_tag_row_to_array()` helpers |
| [app/Config/ScreenshotOne.php](app/Config/ScreenshotOne.php) | ScreenshotOne API key and secret |
| [app/Views/bookmarks/home.php](app/Views/bookmarks/home.php) | Public listing view |
| [app/Views/bookmarks/bookmark.php](app/Views/bookmarks/bookmark.php) | Single bookmark detail view |
| [app/Views/bookmarks/partials/bookmark_items.php](app/Views/bookmarks/partials/bookmark_items.php) | Reusable bookmark card partial |
| [app/Views/bookmarks/admin/home.php](app/Views/bookmarks/admin/home.php) | Admin management table view |
| [app/Views/bookmarks/admin/bookmark_form.php](app/Views/bookmarks/admin/bookmark_form.php) | Create/edit form with live preview |
| [public/assets/js/bookmarks/home.js](public/assets/js/bookmarks/home.js) | Public listing JS (infinite scroll, search shortcut) |
| [public/assets/js/bookmarks/admin/home.js](public/assets/js/bookmarks/admin/home.js) | Admin listing JS (selection, bulk delete) |
| [public/assets/js/bookmarks/admin/bookmark-form.js](public/assets/js/bookmarks/admin/bookmark-form.js) | Form JS (tags, live preview, favicon, screenshots, submit) |
| [public/uploads/bookmarks/media/](public/uploads/bookmarks/media/) | Uploaded and captured bookmark images |
