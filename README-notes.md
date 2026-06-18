# corenominal.com - Notes

## Overview

The notes system is a personal encrypted notepad. All note content (title and body) is stored in the database using MySQL's `AES_ENCRYPT` / `AES_DECRYPT` functions with a user-supplied passphrase. The key is never sent to the server as a standalone credential — it travels as a browser cookie on admin page requests and as a custom request header on API calls. The server never stores the key; it only decrypts on the fly per request. All management is handled through the admin panel and a REST API consumed by the admin JS.

---

## Architecture

The system has two tiers:

- **Admin web** — page rendering only; all data operations are performed client-side via the API
- **REST API** — full CRUD plus revision management; protected by `ApiFilter` and the per-request note key header

---

## Database Tables

| Table | Purpose |
|---|---|
| `notes` | Encrypted note records |
| `notes_revisions` | Point-in-time snapshots created when a note's body changes |

### `notes`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | Auto-increment |
| `note_id` | VARCHAR | UUID (v4); shared key between `notes` and `notes_revisions` |
| `hash` | VARCHAR | `sha1(notekey)`; used to detect key changes, not for auth |
| `title` | LONGBLOB | AES-encrypted; auto-derived from first line of body |
| `body` | LONGBLOB | AES-encrypted markdown |
| `pinned` | TINYINT | `1` = pinned; pinned notes sort first in the list |
| `created_at / updated_at` | DATETIME | No soft deletes |

### `notes_revisions`

Identical schema to `notes`. Rows share `note_id` with their parent note. Revisions are created automatically when a note's body changes on update; they are deleted in bulk when the parent note is deleted.

---

## Routes

### Admin (requires `AdminFilter`)

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/admin/notes/` | `Notes\Admin\Home::index` | Notes list view |
| GET | `/admin/notes/key` | `Notes\Admin\Key::index` | Set or clear encryption key |
| GET | `/admin/notes/new` | `Notes\Admin\Editor::new` | New note editor |
| GET | `/admin/notes/{id}/edit` | `Notes\Admin\Editor::edit` | Edit existing note |

### API (requires `ApiFilter`)

All API endpoints also require the decryption key to be passed in the `notekey` request header (except `delete` and `preview`, which do not need to decrypt).

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/api/notes/list` | `Notes\Api\Notes::list` | Paginated note list with optional search |
| GET | `/api/notes/{id}` | `Notes\Api\Notes::find` | Fetch a single decrypted note |
| POST | `/api/notes/` | `Notes\Api\Notes::create` | Create a new note |
| POST | `/api/notes/preview` | `Notes\Api\Notes::preview` | Convert markdown to HTML (server-side) |
| PATCH | `/api/notes/{id}` | `Notes\Api\Notes::update` | Update note content or pin status |
| DELETE | `/api/notes/{id}` | `Notes\Api\Notes::delete` | Delete note and all its revisions |
| GET | `/api/notes/{id}/revisions` | `Notes\Api\Notes::listRevisions` | List revisions for a note |
| GET | `/api/notes/{id}/revision/{rid}` | `Notes\Api\Notes::findRevision` | Fetch a single decrypted revision |
| DELETE | `/api/notes/{id}/revision/{rid}` | `Notes\Api\Notes::deleteRevision` | Delete a single revision |
| DELETE | `/api/notes/{id}/revisions` | `Notes\Api\Notes::deleteRevisions` | Delete all or selected revisions |

---

## Encryption

Notes are encrypted with `AES_ENCRYPT(value, key)` and decrypted with `AES_DECRYPT(value, key)` inline in every SQL query. The key is:

- Stored in the browser cookie `noteskey` (set on the Key page; never expires)
- Read from the `notekey` HTTP request header for all API calls

The `hash` column stores `sha1(notekey)` to make it possible to detect when a note was saved with a different key, though no access control is enforced on it.

Title is not entered by the user — it is derived server-side from the first line of the body: leading `#` characters and surrounding whitespace are stripped. If the body is empty, the title defaults to `Untitled Note`.

---

## Key Management (`/admin/notes/key`)

The Key page provides a form to set or clear the `noteskey` cookie.

- **Set key**: submitting a non-empty value writes it to `noteskey` with a far-future expiry and redirects to `/admin/notes/`
- **Clear key**: clicking "Clear key" deletes the cookie client-side

Both `Home` and `Editor` controllers check for the cookie on every request and redirect to `/admin/notes/key` if it is absent.

---

## Notes List (`/admin/notes/`)

The list view renders an empty shell; the JS fetches and renders all content via the API.

`GET /api/notes/list` returns a paginated, decrypted list (20 per page). The list is first fetched entirely, filtered in PHP when a search term `q` is present (case-insensitive match against title or body), then sliced for the requested page.

Response shape:

```json
{
  "notes":       [...],
  "total":       42,
  "page":        1,
  "per_page":    20,
  "total_pages": 3
}
```

Body is excluded from list results; only `id`, `title`, `pinned`, and `updated_at` are returned.

**Client behaviour:**

- Search input debounces at 300 ms
- Pagination and search state are reflected in the URL via the History API (`pushState` / `popState`)
- Pinned notes appear first (sorted by `pinned DESC, updated_at DESC`)
- Pinned notes receive a `note-pinned` CSS class on their table row
- Delete is confirmed via a Bootstrap modal; the row is removed immediately on success without reloading

---

## Note Editor (`/admin/notes/new`, `/admin/notes/{id}/edit`)

The editor page is a plain markdown textarea with three Bootstrap tabs: **Edit**, **Preview**, and **Revisions**.

### Edit tab

- On load for an existing note, the body is fetched from `GET /api/notes/{id}` and populated into the textarea
- New notes start with `# ` pre-filled and the cursor placed after it
- The page title gains a `*` suffix while there are unsaved changes (`isDirty` flag)
- `beforeunload` fires a browser warning if the note is dirty

### Saving

Saving calls `POST /api/notes/` (new) or `PATCH /api/notes/{id}` (existing) with `{"body": "..."}`. After the first successful save of a new note, the URL is updated to `/admin/notes/{id}/edit` via `history.replaceState`. Ctrl+S / Cmd+S triggers save.

### Pin toggle

The pin button sends `PATCH /api/notes/{id}` with `{"pinned": 0 | 1}`. A pin-only PATCH is detected by the API when the payload contains exactly one key (`pinned`) and skips the body-change detection and revision logic.

### Download

The download button creates a `Blob` from the current textarea content and triggers a browser download as `note-{id}.md` (or `note.md` for unsaved notes).

### Preview tab

When the Preview tab is activated, the current markdown is rendered via the client-side `marked.js` library. The preview is only re-rendered when `previewDirty` is true (set on every textarea `input` event). Code blocks in the preview get a copy-to-clipboard button injected alongside them.

The `POST /api/notes/preview` endpoint also exists for server-side rendering via the `Markdown` library, but the editor uses the client-side `marked.js` path.

### Revisions tab

Revisions are loaded on first tab activation via `GET /api/notes/{id}/revisions`, returning a list of `{id, title, created_at}` objects.

**Viewing a revision** fetches the full decrypted body (`GET /api/notes/{id}/revision/{rid}`) and displays it in a modal with two sub-tabs:

- **Text** — a read-only textarea showing the revision's raw markdown
- **Diff** — a line-by-line LCS diff between the revision body and the current textarea content; deleted lines prefixed with `-`, added lines with `+`

A **Restore** button copies the revision body into the editor textarea (marks as dirty) and switches back to the Edit tab.

**Deleting revisions:**

| Action | API call |
|---|---|
| Delete single | `DELETE /api/notes/{id}/revision/{rid}` |
| Delete selected | `DELETE /api/notes/{id}/revisions` with `{"ids": [...]}` |
| Delete all | `DELETE /api/notes/{id}/revisions` (no body) |

All deletion paths are confirmed via a Bootstrap modal before the API call is made.

---

## Markdown Expanders (`markdown-expanders.js`)

The editor textarea has a set of keyboard shortcuts and word expanders:

| Trigger | Action |
|---|---|
| Tab (no selection) | Insert 4 spaces |
| Tab (with selection) | Indent selected lines by 4 spaces |
| Shift+Tab (with selection) | Outdent selected lines by up to 4 spaces |
| Enter after empty list item / blockquote | Exit the structure (delete the trailing marker) |
| Enter after ordered list item | Continue with next number |
| Enter after unordered list item | Continue with same marker |
| Enter after blockquote line | Continue with `> ` prefix |
| Ctrl/Cmd+B | Toggle `**bold**` on selection |
| Ctrl/Cmd+I | Toggle `*italic*` on selection |
| `` ` `` (third backtick in a row) | Expand to fenced code block ` ``` `` ` |
| `` ` `` (with selection) | Wrap selection in inline backticks |
| `lorem` + Space/Tab | Replace the word with a lorem ipsum paragraph |

---

## Key Files

| Path | Description |
|---|---|
| [app/Controllers/Notes/Admin/Home.php](app/Controllers/Notes/Admin/Home.php) | Notes list view |
| [app/Controllers/Notes/Admin/Editor.php](app/Controllers/Notes/Admin/Editor.php) | New and edit note views |
| [app/Controllers/Notes/Admin/Key.php](app/Controllers/Notes/Admin/Key.php) | Set/clear encryption key |
| [app/Controllers/Notes/Api/Notes.php](app/Controllers/Notes/Api/Notes.php) | Full REST API: CRUD, preview, revisions |
| [app/Models/NoteModel.php](app/Models/NoteModel.php) | Notes table model |
| [app/Models/NoteRevisionModel.php](app/Models/NoteRevisionModel.php) | Notes revisions table model |
| [public/assets/js/notes/admin/home.js](public/assets/js/notes/admin/home.js) | List view JS: fetch, search, pagination, pin, delete |
| [public/assets/js/notes/admin/editor.js](public/assets/js/notes/admin/editor.js) | Editor JS: load, save, pin, download, preview, revisions diff |
| [public/assets/js/notes/admin/key.js](public/assets/js/notes/admin/key.js) | Key page JS: set and clear cookie |
| [public/assets/js/notes/admin/markdown-expanders.js](public/assets/js/notes/admin/markdown-expanders.js) | Textarea keyboard expanders and smart list continuation |
| [public/assets/css/notes/admin/editor.css](public/assets/css/notes/admin/editor.css) | Editor styles |
| [public/assets/css/notes/admin/home.css](public/assets/css/notes/admin/home.css) | List view styles |
