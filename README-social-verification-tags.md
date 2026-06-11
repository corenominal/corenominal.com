# corenominal.com - Social Verification Tags

## Overview

The social verification tags system provides a simple CRUD admin for managing `rel="me"` identity verification links. These links are embedded in every page's `<head>` and allow social platforms (such as Mastodon) to verify that the site and a given social profile belong to the same person.

---

## Architecture

Each tag stores a human-readable name (for admin display only) and a URL (written into the HTML). Tags are soft-deleted, paginated, and managed entirely through an AJAX-driven admin interface at `/admin/social/tags`.

---

## Database Table

### `social_verification_tags`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | Auto-increment |
| `uuid` | VARCHAR(36) | Stable external identifier (Ramsey UUID v4) |
| `name` | VARCHAR(255) | Label used in the admin UI only |
| `url` | VARCHAR(500) | The URL written into `<link rel="me" href="...">` |
| `created_at / updated_at / deleted_at` | DATETIME | Soft deletes enabled |

---

## Routes

All admin routes are grouped under `/admin/social` and are protected by the global `AdminFilter`.

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/admin/social` | — | Redirects to `/admin/social/tags` |
| GET | `/admin/social/tags` | `Social\Admin\Tags::index` | Admin page (HTML shell) |
| GET | `/admin/social/tags/data` | `Social\Admin\Tags::getData` | Paginated tag list (JSON) |
| GET | `/admin/social/tags/:id` | `Social\Admin\Tags::getTag` | Single tag (JSON) |
| POST | `/admin/social/tags/create` | `Social\Admin\Tags::createTag` | Create tag |
| POST | `/admin/social/tags/:id` | `Social\Admin\Tags::updateTag` | Update tag |
| DELETE | `/admin/social/tags/:id` | `Social\Admin\Tags::deleteTag` | Soft-delete tag |
| POST | `/admin/social/tags/bulk-delete` | `Social\Admin\Tags::bulkDelete` | Soft-delete multiple tags |

---

## Admin UI

The admin page at `/admin/social/tags` is an AJAX-driven table. The HTML shell is rendered server-side; all data is fetched and rendered client-side via `tags.js`.

**Features:**

- Paginated table with configurable rows per page (10 / 20 / 50 / 100)
- Column sorting (ID, Name, URL, Created)
- Debounced search across name and URL fields
- Create and edit via a Bootstrap modal; URL field validated before submission
- Single-row delete with Bootstrap confirmation modal
- Bulk delete via checkbox selection

**"New Tag" sidebar link** — the sidebar includes a direct "New Tag" link that navigates to `/admin/social/tags?new=1`. The JS detects this parameter on load, strips it from the address bar via `history.replaceState`, and opens the create modal automatically.

---

## URL Validation

URLs are validated at two layers:

- **Client-side** — the JS submit handler uses `new URL()` before sending the request; an inline error is shown immediately if the URL is invalid
- **Server-side** — `filter_var($url, FILTER_VALIDATE_URL)` is applied in both `createTag()` and `updateTag()`; returns `400` with an error message if invalid

---

## Helper Function

**File:** [app/Helpers/social_helper.php](app/Helpers/social_helper.php)

**Autoloaded via:** `$helpers = ['log', 'auth', 'social']` in [app/Config/Autoload.php](app/Config/Autoload.php)

### `social_verification_tags(): string`

Queries all non-deleted rows from `social_verification_tags` and returns an HTML string of `<link rel="me">` tags, one per record. Returns an empty string if there are no tags or if a database error occurs.

**Output example:**

```html
<link rel="me" href="https://mastodon.social/@username">
<link rel="me" href="https://github.com/username">
```

The URL attribute is escaped with CodeIgniter's `esc($value, 'attr')` to prevent attribute injection.

**Usage in templates:**

```php
<?= social_verification_tags() ?>
```

Called inside `<head>` in both template files:

- [app/Views/templates/default.php](app/Views/templates/default.php) — main sidebar template
- [app/Views/templates/basic-centered.php](app/Views/templates/basic-centered.php) — centred single-column template

---

## Key Files

| Path | Description |
|---|---|
| [app/Controllers/Social/Admin/BaseController.php](app/Controllers/Social/Admin/BaseController.php) | Base controller for the Social admin area |
| [app/Controllers/Social/Admin/Tags.php](app/Controllers/Social/Admin/Tags.php) | CRUD controller for verification tags |
| [app/Models/SocialVerificationTagModel.php](app/Models/SocialVerificationTagModel.php) | Model with soft deletes and timestamps |
| [app/Helpers/social_helper.php](app/Helpers/social_helper.php) | `social_verification_tags()` helper function |
| [app/Views/social/admin/tags.php](app/Views/social/admin/tags.php) | Admin page view |
| [app/Views/social/admin/sidebar-menu.php](app/Views/social/admin/sidebar-menu.php) | Sidebar navigation for the Social admin area |
| [public/assets/js/social/admin/tags.js](public/assets/js/social/admin/tags.js) | Client-side table, modals, and AJAX logic |
| [app/Config/Routes.php](app/Config/Routes.php) | Route definitions (`admin/social` group) |
| [app/Database/Migrations/2026-06-11-120000_CreateSocialVerificationTagsTable.php](app/Database/Migrations/2026-06-11-120000_CreateSocialVerificationTagsTable.php) | Database migration |
