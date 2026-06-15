# corenominal.com - Blog System

## Overview

The blog system is a full-featured publishing platform for long-form posts. Posts are written in Markdown, stored with pre-rendered HTML, and organised with tags. The system is split into public web views, an admin panel, a REST API, and a set of AI-assisted writing tools powered by a local Ollama instance.

---

## Architecture

The system has four distinct tiers:

- **Public web** — post listing, individual post pages, tag pages, full-text search, and RSS feed
- **Admin web** — post management dashboard with create/edit/delete, media uploads, and post statistics
- **REST API** — read-only public endpoint for fetching latest posts; protected by `ApiFilter`
- **AI API** — Ollama-backed endpoints for writing assistance; protected by `ApiFilter`

---

## Database Tables

| Table | Purpose |
|---|---|
| `posts` | Published and draft blog posts |
| `posts_tags` | Tag records linked to posts |
| `posts_meta` | Key-value metadata per post (e.g. video filename) |

### `posts`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | |
| `uuid` | VARCHAR(36) | Unique; not used as the public identifier (slug is) |
| `title` | VARCHAR(255) | Raw source title |
| `title_html` | TEXT | HTML-rendered title (entity-decoded, tags stripped) |
| `slug` | VARCHAR(255) | Unique URL-safe identifier |
| `body` | LONGTEXT | Raw Markdown source |
| `body_html` | LONGTEXT | Rendered HTML |
| `excerpt` | TEXT | Short plain-text summary |
| `tags` | TEXT | Comma-separated tag string (denormalised copy) |
| `featured_image` | VARCHAR(255) | Filename of the OG image (e.g. `og-{uuid}.png`) |
| `visibility` | TINYINT | `0` = public, other values reserved |
| `status` | ENUM | `draft`, `published`, `revision`, `trashed` |
| `comment_status` | ENUM | `open` or `closed` |
| `comment_count` | INT | Reserved; not currently used |
| `hitcounter` | INT UNSIGNED | Incremented on each non-admin page view |
| `published_at` | DATETIME | Set to now when first published; never overwritten after that |
| `created_at / updated_at / deleted_at` | DATETIME | Soft deletes enabled |

### `posts_tags`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | |
| `post_id` | INT | Foreign key to `posts.id` |
| `tag` | VARCHAR(100) | Display name (e.g. `Linux`) |
| `slug` | VARCHAR(100) | URL-safe slug (e.g. `linux`) |
| `created_at / updated_at` | DATETIME | |

Tags are fully denormalised — each row is a `(post_id, tag, slug)` triple. There is no separate tags table; the set of known tags is derived by querying `posts_tags` with `SELECT DISTINCT tag`.

### `posts_meta`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | |
| `post_id` | INT | Foreign key to `posts.id` |
| `meta_key` | VARCHAR(255) | Currently only `post_video` is used |
| `meta_value` | TEXT | Filename of the uploaded video |
| `created_at / updated_at` | DATETIME | |

Currently only used to store a post's associated video filename (`post_video`).

---

## Routes

### Public

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/blog` | `Blog\Home::index` | Post listing (featured + recent) |
| GET | `/blog/posts` | `Blog\Home::morePosts` | Infinite-scroll batch (JSON) |
| GET | `/blog/posts/(:segment)` | `Blog\Post::show/$1` | Single post by slug |
| GET | `/blog/posts/(:segment)/json` | `Blog\Post::showJson/$1` | Post as JSON |
| GET | `/blog/posts/(:segment)/markdown` | `Blog\Post::showMarkdown/$1` | Post as Markdown download |
| GET | `/blog/tags/(:segment)` | `Blog\Tag::show/$1` | Posts filtered by tag slug |
| GET | `/blog/search` | `Blog\Search::index` | Full-text search results |
| GET | `/blog/feed/rss` | `Blog\Feed::rss` | RSS feed (latest 20) |

### Admin (requires `AdminFilter`)

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/admin/blog` | `Blog\Admin\Home::index` | Post list with stats, search, and status filter |
| POST | `/admin/blog/posts/delete` | `Blog\Admin\Home::deletePosts` | Trash or permanently delete posts |
| GET | `/admin/blog/posts/create` | `Blog\Admin\Posts::create` | New post editor |
| POST | `/admin/blog/posts/store` | `Blog\Admin\Posts::store` | Save new post |
| GET | `/admin/blog/posts/(:num)/edit` | `Blog\Admin\Posts::edit/$1` | Edit existing post |
| POST | `/admin/blog/posts/(:num)/update` | `Blog\Admin\Posts::update/$1` | Save post edits |
| POST | `/admin/blog/posts/preview` | `Blog\Admin\Posts::preview` | Render Markdown to HTML (live preview) |
| POST | `/admin/blog/posts/upload_featured_image` | `Blog\Admin\Posts::upload_featured_image` | Upload OG image |
| POST | `/admin/blog/posts/remove_featured_image` | `Blog\Admin\Posts::remove_featured_image` | Disassociate OG image |
| GET | `/admin/blog/posts/list_featured_images` | `Blog\Admin\Posts::list_featured_images` | List available OG images |
| POST | `/admin/blog/posts/upload_body_image` | `Blog\Admin\Posts::upload_body_image` | Upload inline image for post body |
| POST | `/admin/blog/posts/upload_video` | `Blog\Admin\Posts::upload_video` | Upload post video |
| POST | `/admin/blog/posts/remove_video` | `Blog\Admin\Posts::remove_video` | Delete post video |

### API (requires `ApiFilter`)

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/api/blog/ping` | `Blog\Api\Test::ping` | Health check |
| GET | `/api/blog/posts/latest` | `Blog\Api\Posts::latest` | Latest published posts (JSON) |

### AI API (requires `ApiFilter`)

| Method | Path | Handler | Description |
|---|---|---|---|
| POST | `/api/blog/analyse` | `Api\Ai\Blog::analyse` | Summarise post and suggest improvements |
| POST | `/api/blog/rewrite` | `Api\Ai\Blog::rewrite` | Proofread and improve post |
| POST | `/api/blog/excerpt` | `Api\Ai\Blog::excerpt` | Generate an excerpt |
| POST | `/api/blog/creative` | `Api\Ai\Blog::creative` | Rewrite in a more engaging style |
| POST | `/api/blog/outline` | `Api\Ai\Blog::outline` | Generate a section outline for a topic |

---

## Public Post Listing

`Blog\Home::index` fetches the most recent published public post separately as `$latestPost`, then fetches the next 10 (`POSTS_PER_PAGE`) as `$otherPosts`. Tags and video metadata are hydrated for the featured post; tags are hydrated for the other posts. `$hasMorePosts` signals the client whether infinite scroll should be enabled.

`Blog\Home::morePosts` handles infinite-scroll pagination. It accepts an `offset` query parameter and returns JSON with `data` (post array with pre-formatted date fields), `hasMore` (boolean), and `status`. The featured post is always excluded from paginated results.

---

## Single Post Page

`Blog\Post::show` looks up a post by slug. A 404 is thrown if not found or not published/public. For non-admin visitors the `hitcounter` is incremented. Tags and video metadata are loaded, then similar posts are resolved: posts sharing one or more tags are preferred; if none exist, the 5 most recent other posts are used instead. The heading `$similarHeading` is set to `'Similar posts'` or `'Latest posts'` accordingly.

OG meta tags (`type`, `title`, `url`, `description`, `image`) are passed as `$og` for the layout to render. The featured image URL is built from `public/uploads/blog/media/` and expected to be 1200×630 px.

`Blog\Post::showJson` and `Blog\Post::showMarkdown` return the same post in machine-readable formats without incrementing the hit counter.

---

## Tag Pages

`Blog\Tag::show` looks up all `posts_tags` rows for the given slug to find the canonical tag name and the set of post IDs. Those IDs are then queried against `posts` filtered to published/public. A 404 is thrown if no matching tag or no visible posts exist.

---

## Search

`Blog\Search::index` reads a `q` query parameter, splits it into individual terms, and performs an `OR LIKE` match against `title`, `excerpt`, and `body` for each term. Results are ordered by `published_at` descending. Tags are hydrated for all results.

---

## RSS Feed

`Blog\Feed::rss` returns the 20 most recent published public posts as an RSS 2.0 feed (`application/rss+xml`). Site name and base URL are pulled from `App` config.

---

## Admin Dashboard

`Blog\Admin\Home::index` renders a post list with summary statistics (`total_posts`, `published_posts`, `draft_posts`, `trashed_posts`, `total_tags`, `total_views`). The list is paginated at 25 per page and supports a text search (`q`) against `title`, `slug`, and `tags`, as well as a `status` filter. Posts are ordered by `created_at` descending.

---

## Two-Stage Delete

`Blog\Admin\Home::deletePosts` accepts a JSON array of `ids`. Posts not yet trashed are moved to `status = 'trashed'`; posts already trashed are hard-deleted. The response reports how many were trashed vs. permanently deleted.

---

## Creating and Updating Posts

Both `store` and `update` share the same flow:

1. Markdown is converted to HTML for `title` and `body` via the `Markdown` library. Title HTML is entity-decoded and stripped of tags.
2. The slug is resolved: if an explicit slug is provided it is used as the base; otherwise it is derived from the title via `url_title`. A numeric suffix is appended if the candidate slug is already taken. For updates, the slug is frozen once a post is published.
3. If `_save_action` is `publish`, `status` is forced to `published` and `published_at` is set to now (if not already set).
4. Publishing requires `title`, `body`, `tags`, and `excerpt` to be non-empty; validation errors are returned as a 422 JSON response (or a redirect with flash data for non-AJAX requests).
5. Tags are re-saved by deleting all existing `posts_tags` rows for the post and inserting fresh ones.
6. Video metadata is upserted into `posts_meta` if a `video_filename` is present, or deleted if the field is empty.

Both endpoints detect AJAX / `Accept: application/json` requests and return JSON (including a refreshed CSRF token) rather than redirecting.

---

## Media Uploads

All media is stored in `public/uploads/blog/media/` with UUID-prefixed filenames.

### Featured (OG) Images

- Route: `POST /admin/blog/posts/upload_featured_image`
- Must be exactly **1200×630 px**
- Allowed MIME types: `image/png`, `image/jpeg`, `image/webp`, `image/gif`
- Filename format: `og-{uuid}.{ext}`
- `remove_featured_image` disassociates the image from a post (does not delete the file from disk)
- `list_featured_images` scans `public/uploads/blog/media/` for files prefixed with `og-`

### Inline Body Images

- Route: `POST /admin/blog/posts/upload_body_image`
- Allowed MIME types: `image/png`, `image/jpeg`, `image/webp`, `image/gif`
- Images wider than **1920 px** are resized proportionally using GD; transparency is preserved for PNG/WebP/GIF
- Filename format: `{uuid}.{ext}`

### Videos

- Route: `POST /admin/blog/posts/upload_video`
- Allowed MIME types: `video/mp4`, `video/webm`, `video/ogg`, `video/quicktime`
- Filename format: `{uuid}.{ext}` (ext derived from MIME: `mp4`, `webm`, `ogg`, `mov`)
- On upload, if a `post_id` is provided, the `post_video` meta key is upserted immediately
- `remove_video` deletes the file from disk and removes the `posts_meta` row

---

## AI Writing Tools

All AI endpoints proxy requests to a local Ollama instance. The host IP is configured in `app/Config/Ollama.php` (set via `.env`). All endpoints accept a JSON body and return JSON.

| Endpoint | Input | Output |
|---|---|---|
| `analyse` | `content`, optional `title`, optional `model` | `{"summary": "...", "suggestions": [{"area": "...", "suggestion": "..."}, ...]}` |
| `rewrite` | `content`, optional `title`, optional `model` | `{"content": "..."}` (plus `"title"` if title was provided) |
| `excerpt` | `content`, optional `title`, optional `model`, optional `length` (`short`/`medium`/`long`) | `{"excerpt": "..."}` |
| `creative` | `content`, optional `title`, optional `model` | `{"content": "..."}` (plus `"title"` if title was provided) |
| `outline` | `topic`, optional `model` | `{"outline": [{"heading": "...", "subheadings": [...]}, ...]}` |

All prompts instruct the model to respond with structured JSON only (no Markdown in field values, no em dashes). Timeouts range from 60 s (`excerpt`, `outline`) to 120 s (`rewrite`, `creative`).

---

## Key Files

| Path | Description |
|---|---|
| [app/Controllers/Blog/Home.php](app/Controllers/Blog/Home.php) | Public post listing and infinite-scroll endpoint |
| [app/Controllers/Blog/Post.php](app/Controllers/Blog/Post.php) | Single post page, JSON and Markdown views |
| [app/Controllers/Blog/Tag.php](app/Controllers/Blog/Tag.php) | Tag archive page |
| [app/Controllers/Blog/Search.php](app/Controllers/Blog/Search.php) | Full-text search |
| [app/Controllers/Blog/Feed.php](app/Controllers/Blog/Feed.php) | RSS feed |
| [app/Controllers/Blog/Admin/Home.php](app/Controllers/Blog/Admin/Home.php) | Admin post list, stats, and delete |
| [app/Controllers/Blog/Admin/Posts.php](app/Controllers/Blog/Admin/Posts.php) | Post editor, store/update, and media uploads |
| [app/Controllers/Blog/Api/Posts.php](app/Controllers/Blog/Api/Posts.php) | Public API: latest posts |
| [app/Controllers/Blog/Api/Test.php](app/Controllers/Blog/Api/Test.php) | Health check ping |
| [app/Controllers/Api/Ai/Blog.php](app/Controllers/Api/Ai/Blog.php) | AI writing tools (analyse, rewrite, excerpt, creative, outline) |
| [app/Models/PostModel.php](app/Models/PostModel.php) | Post records with soft deletes |
| [app/Models/PostsTagModel.php](app/Models/PostsTagModel.php) | Tag records |
| [app/Models/PostsMetaModel.php](app/Models/PostsMetaModel.php) | Post metadata (video filenames) |
| [app/Libraries/Markdown.php](app/Libraries/Markdown.php) | Markdown-to-HTML conversion |
| [app/Config/Ollama.php](app/Config/Ollama.php) | Ollama host IP and default model |
| [app/Database/Migrations/2026-06-14-100000_CreateBlogTables.php](app/Database/Migrations/2026-06-14-100000_CreateBlogTables.php) | Migration for `posts`, `posts_tags`, `posts_meta` |
| [public/assets/js/blog/admin/home.js](public/assets/js/blog/admin/home.js) | Admin post list JS |
| [public/assets/js/blog/admin/post_editor.js](public/assets/js/blog/admin/post_editor.js) | Post editor JS (live preview, media uploads, AI tools) |
| [public/assets/js/blog/common.js](public/assets/js/blog/common.js) | Shared blog JS utilities |
| [public/assets/js/blog/home.js](public/assets/js/blog/home.js) | Infinite scroll for post listing |
| [public/assets/js/blog/post.js](public/assets/js/blog/post.js) | Single post page JS |
| [public/uploads/blog/media/](public/uploads/blog/media/) | Uploaded images and videos |
