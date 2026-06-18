# corenominal.com - TODO

## Overview

The TODO system is a personal task manager. Items are written in Markdown, stored as both raw markdown and server-rendered HTML, and organised into categories. All management is handled through the admin panel; data operations are performed client-side via the REST API. Items support soft-deletion (recoverable from the Deleted tab) and hard permanent deletion.

---

## Architecture

The system has two tiers:

- **Admin web** — page rendering only; all data operations are performed client-side via the API
- **REST API** — full CRUD plus status, pin, and lifecycle management; protected by `ApiFilter`

---

## Database Tables

| Table | Purpose |
|---|---|
| `todo_items` | All todo item records, including soft-deleted rows |

### `todo_items`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | Auto-increment |
| `uuid` | VARCHAR(36) | UUID (v4); used as the public item identifier in all API calls |
| `user_uuid` | VARCHAR(36) | UUID of the owning user; sourced from session or `user-uuid` request header |
| `status` | ENUM | `'todo'` or `'complete'`; default `'todo'` |
| `markdown` | TEXT | Raw markdown content |
| `html` | TEXT | Server-rendered HTML, kept in sync with `markdown` on every write |
| `category` | VARCHAR(100) | Lowercase category label; defaults to `'uncategorised'` if blank |
| `is_pinned` | TINYINT | `1` = pinned; pinned items sort first within the todo list |
| `completed_at` | DATETIME | Set when status transitions to `'complete'`; cleared on undo |
| `created_at / updated_at` | DATETIME | Managed by CodeIgniter's model timestamps |
| `deleted_at` | DATETIME | Soft-delete field; `NULL` = active; non-null = in the Deleted tab |

---

## Routes

### Admin (requires `AdminFilter`)

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/admin/todo/` | `Todo\Admin\Home::index` | Main TODO list view |

### API (requires `ApiFilter`)

The `user-uuid` is read from the `user-uuid` request header or from the session on every API request.

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/api/todo/items` | `Todo\Api\TodoItems::index` | Paginated item list with optional status, search, and category filter |
| GET | `/api/todo/counts` | `Todo\Api\TodoItems::counts` | Count of items per status (todo / complete / deleted) |
| GET | `/api/todo/categories` | `Todo\Api\TodoItems::categories` | Distinct categories with per-status counts |
| POST | `/api/todo/items` | `Todo\Api\TodoItems::create` | Create a new item |
| POST | `/api/todo/items/{uuid}` | `Todo\Api\TodoItems::update` | Update markdown and/or category |
| POST | `/api/todo/items/{uuid}/status` | `Todo\Api\TodoItems::updateStatus` | Transition status between `'todo'` and `'complete'` |
| POST | `/api/todo/items/{uuid}/pin` | `Todo\Api\TodoItems::togglePin` | Toggle pin on/off |
| POST | `/api/todo/items/{uuid}/delete` | `Todo\Api\TodoItems::delete` | Soft-delete (moves to Deleted tab) |
| POST | `/api/todo/items/{uuid}/restore` | `Todo\Api\TodoItems::restore` | Restore a soft-deleted item back to todo |
| POST | `/api/todo/items/{uuid}/destroy` | `Todo\Api\TodoItems::destroy` | Permanently delete (hard delete, irreversible) |

---

## Item Lifecycle

```
[create] → status: todo
    ↓
[done]   → status: complete  (is_pinned reset to 0, completed_at set)
    ↓
[undo]   → status: todo      (completed_at cleared)

[delete] → soft-deleted (deleted_at set; item disappears from todo/complete tabs)
    ↓
[restore] → item returns to todo (deleted_at cleared)
[destroy] → permanently removed from database
```

All writes scope to `user_uuid`, so a user can never read or modify another user's items.

---

## Admin View (`/admin/todo/`)

The view renders a static shell; all content is fetched and rendered by JavaScript. It has five tabs:

| Tab | Description |
|---|---|
| **TODO** | Active items; sorted pinned first, then by `created_at DESC` |
| **Completed** | Items with `status = 'complete'`; sorted by `updated_at DESC` |
| **Deleted** | Soft-deleted items; sorted by `updated_at DESC` |
| **Categories** | Category cards with todo/completed counts; clickable to filter |
| **Help** | Keyboard shortcuts reference and GitHub Flavored Markdown cheat sheet |

### Create form

A collapsible card at the top of the page. Fields: markdown textarea and an optional category input backed by a `<datalist>` populated from `GET /api/todo/categories`. Submitting calls `POST /api/todo/items`. If the current tab is not "TODO", the tab is switched automatically after creation.

### Filtering and search

- **Search** (`GET /api/todo/items?search=…`): matches against the `markdown` and `category` columns
- **Category filter** (`GET /api/todo/items?category=…`): applied on top of search; set by clicking a category badge on any item or a card in the Categories tab
- Active filters are shown in a dismissible filter bar above the tabs; "Clear filters" resets both search and category
- Ctrl/Cmd+F focuses the search bar

### Item cards

Each item renders as a Bootstrap card with:

- Rendered HTML content (links open in `_blank`)
- A meta row showing created date, completed date (Completed tab only), pinned badge, and category badge (clickable to filter)
- Per-tab action buttons (see table below)

| Tab | Available actions |
|---|---|
| TODO | Pin/Unpin, Done, Category, Delete |
| Completed | Undo, Delete |
| Deleted | Restore, Delete permanently |

### Inline editing (TODO tab only)

Clicking an item's content area opens an inline edit mode in place: a textarea pre-populated with the raw markdown and a category input. Saving calls `POST /api/todo/items/{uuid}` with `{markdown, category}`. The card content and category badge are updated in place without reloading. Ctrl/Cmd+S saves from any field in the edit area.

### Category change modal

The "Category" button on a todo item opens a Bootstrap modal with a single text input (backed by the same `<datalist>`). Saving calls `POST /api/todo/items/{uuid}` with `{category}`. The badge on the card is updated in place on success.

### Pagination

Items are paginated at 20 per page. The pagination control renders a windowed page list (±2 pages around current). Navigating scrolls the tab content area into view.

### Dirty tab tracking

Only the active tab is live; other tabs are flagged `dirty`. When the user switches to a dirty tab, it reloads automatically. Actions that affect other tabs (e.g. marking done affects Completed; deleting affects Deleted) mark the relevant tabs dirty.

### Confetti

Marking an item as done triggers a radial confetti burst animation originating from the click coordinates, rendered on a full-viewport canvas overlay.

---

## API Response Shapes

### `GET /api/todo/items`

```json
{
  "status":     "success",
  "items":      [...],
  "total":      42,
  "page":       1,
  "perPage":    20,
  "totalPages": 3
}
```

### `GET /api/todo/counts`

```json
{
  "status":   "success",
  "todo":     12,
  "complete": 30,
  "deleted":  5
}
```

### `GET /api/todo/categories`

```json
{
  "status": "success",
  "categories": [
    { "name": "work", "todo": 4, "complete": 10 }
  ]
}
```

---

## Markdown Shortcuts (Textarea)

The create textarea and inline edit textarea both support keyboard shortcuts:

| Trigger | Action |
|---|---|
| Ctrl/Cmd+B | Toggle `**bold**` on selection |
| Ctrl/Cmd+I | Toggle `*italic*` on selection |
| `` ` `` (with selection) | Wrap selection in inline backticks |
| `` ` `` (third backtick in a row) | Expand to fenced code block |
| Enter after unordered list item | Continue with same marker |
| Enter after ordered list item | Continue with next number |
| Enter after blockquote line | Continue with `> ` prefix |
| Enter after empty list item / blockquote | Exit the structure |

---

## Key Files

| Path | Description |
|---|---|
| [app/Controllers/Todo/Admin/Home.php](app/Controllers/Todo/Admin/Home.php) | Admin view controller |
| [app/Controllers/Todo/Api/TodoItems.php](app/Controllers/Todo/Api/TodoItems.php) | Full REST API: CRUD, status, pin, soft-delete, restore, destroy |
| [app/Models/TodoItemModel.php](app/Models/TodoItemModel.php) | TodoItems table model (soft-deletes enabled) |
| [app/Views/todo/admin/home.php](app/Views/todo/admin/home.php) | Admin view template |
| [public/assets/js/todo/admin/home.js](public/assets/js/todo/admin/home.js) | All client-side logic: tabs, create, inline edit, actions, pagination, confetti |
| [public/assets/css/todo/admin/home.css](public/assets/css/todo/admin/home.css) | Admin view styles |
| [app/Database/Migrations/2026-06-17-000009_CreateTodoItemsTable.php](app/Database/Migrations/2026-06-17-000009_CreateTodoItemsTable.php) | Migration creating `todo_items` |
