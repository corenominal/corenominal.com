# corenominal.com - AI Chat

## Overview

The AI Chat system is a personal chat interface that supports two backends: a local [Ollama](https://ollama.com) instance and [OpenRouter](https://openrouter.ai) (cloud models). Conversations are stored in the database (sessions and messages), streamed in real time over Server-Sent Events, and rendered client-side with `marked.js` and `highlight.js`. The system supports images (base64), documents (PDF and plain text), and models that emit a separate thinking stream.

---

## Architecture

The system has two tiers:

- **Admin web** — page rendering only; all data operations are performed client-side via the API
- **REST API** — session and message management plus the Ollama/OpenRouter proxy; protected by `ApiFilter`

---

## Database Tables

| Table | Purpose |
|---|---|
| `chat_sessions` | One row per conversation |
| `chat_messages` | All messages for every session |
| `system_prompts` | Revision history of the default system prompt |
| `openrouter_models` | Whitelist of OpenRouter model IDs enabled for use in chat |

### `chat_sessions`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | Auto-increment |
| `uuid` | VARCHAR | V4 UUID; used in URLs and API paths |
| `title` | VARCHAR | Auto-derived from the first message (up to 60 chars) or set manually via rename |
| `model` | VARCHAR | Model name active for this session (e.g. `llama3.2`, `anthropic/claude-opus-4`) |
| `provider` | VARCHAR | `ollama` (default) or `openrouter` |
| `pinned` | TINYINT | `1` = pinned; pinned sessions sort first |
| `created_at / updated_at / deleted_at` | DATETIME | Soft deletes via `deleted_at` |

### `chat_messages`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | Auto-increment |
| `session_id` | INT FK | References `chat_sessions.id` |
| `role` | VARCHAR | `user` or `assistant` |
| `model` | VARCHAR | Set only on `assistant` rows; records which model produced the reply |
| `content` | TEXT | Message text (markdown for assistant messages) |
| `thinking` | TEXT | Separated reasoning content if the model emits it; NULL otherwise |
| `images` | JSON | Base64 data-URLs of attached images (`null` when empty) |
| `documents` | JSON | `[{name, type, content}]` objects of attached files (`null` when empty) |
| `created_at` | DATETIME | No `updated_at`; messages are never edited |

### `system_prompts`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | Auto-increment |
| `content` | TEXT | Prompt text injected as `role: system` on every request |
| `created_at` | DATETIME | Each save is a new row; the highest `id` is the active prompt |

### `openrouter_models`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK | Auto-increment |
| `model_id` | VARCHAR | OpenRouter model ID (e.g. `anthropic/claude-opus-4`); unique |

---

## Routes

### Admin (requires `AdminFilter`)

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/admin/ai/` | `Ai\Admin\Home::index` | Dashboard with stats and recent sessions |
| GET | `/admin/ai/chat` | `Ai\Admin\Chat::index` | Chat UI (new chat) |
| GET | `/admin/ai/chat/{uuid}` | `Ai\Admin\Chat::session` | Chat UI (load existing session) |
| GET | `/admin/ai/prompt` | `Ai\Admin\Prompt::index` | Default system prompt editor |
| POST | `/admin/ai/prompt/update` | `Ai\Admin\Prompt::update` | Save new prompt revision |
| POST | `/admin/ai/prompt/revert/{id}` | `Ai\Admin\Prompt::revert` | Promote an old revision to active |
| GET | `/admin/ai/openrouter-models` | `Ai\Admin\OpenrouterModels::index` | Manage enabled OpenRouter models |
| POST | `/admin/ai/openrouter-models/save` | `Ai\Admin\OpenrouterModels::save` | Save enabled model selection |

### API (requires `ApiFilter`)

| Method | Path | Handler | Description |
|---|---|---|---|
| GET | `/api/ai/chat/models` | `Ai\Api\Chat::models` | List models; `?provider=ollama` (default) or `?provider=openrouter` |
| GET | `/api/ai/chat/sessions` | `Ai\Api\Chat::sessions` | All sessions ordered by pinned then updated |
| POST | `/api/ai/chat/session` | `Ai\Api\Chat::createSession` | Create a new session with a chosen model and provider |
| PATCH | `/api/ai/chat/session/{uuid}` | `Ai\Api\Chat::updateSession` | Update `title`, `pinned`, `model`, or `provider` |
| DELETE | `/api/ai/chat/session/{uuid}` | `Ai\Api\Chat::deleteSession` | Delete session and all its messages |
| GET | `/api/ai/chat/messages/{uuid}` | `Ai\Api\Chat::messages` | Return session + ordered messages |
| POST | `/api/ai/chat/stream` | `Ai\Api\Chat::stream` | Stream a chat turn via SSE |
| POST | `/api/ai/chat/extract-pdf` | `Ai\Api\Chat::extractPdf` | Extract text from an uploaded PDF |
| GET | `/api/ai/chat/search` | `Ai\Api\Chat::search` | Full-text search across sessions and messages |

---

## Streaming (`POST /api/ai/chat/stream`)

The stream endpoint routes to either the Ollama or OpenRouter backend based on the session's `provider` field, then re-emits the response as SSE. All headers (Content-Type, Cache-Control, X-Accel-Buffering) are set to prevent buffering. `set_time_limit(300)` is applied for long responses.

**Request body:**

```json
{
  "session_uuid": "optional-existing-uuid",
  "message":      "user text",
  "model":        "llama3.2",
  "provider":     "ollama",
  "images":       ["data:image/png;base64,..."],
  "documents":    [{"name": "file.txt", "type": "text/plain", "content": "..."}]
}
```

**Session creation on first message:** If `session_uuid` is absent or the UUID is not found, a new session is inserted automatically with the given `model` and `provider`. The title is taken from the first 60 characters of the message, or from the document/image name if the message is empty.

**Document injection:** Attached document content is prepended to the user message before being sent to the backend, wrapped in `--- filename ---` / `--- end of filename ---` delimiters. This is applied identically for both Ollama and OpenRouter.

**SSE event types emitted:**

| Event | Payload | When |
|---|---|---|
| `session` | `{uuid, title}` | First event; allows client to update the URL |
| `data` | `{content: "chunk"}` | Each response token |
| `data` | `{thinking: "chunk"}` | Reasoning tokens (when the model emits them) |
| `done` | `{done: true, model, created_at}` | After all tokens; `assistant` message is persisted here |
| `error` | `{error: "..."}` | Connection failure, HTTP error, or empty payload |

### Ollama backend

Sends a POST to `http://{ollamaIp}:11434/api/chat` with newline-delimited JSON streaming. Image data-URLs are stripped to bare base64 and passed as the `images` array in the Ollama message payload. Reasoning tokens are read from `message.thinking`.

**Thinking extraction fallback:** After the stream completes, if `fullThinking` is empty but the assembled content begins with `<think>…</think>`, the tag is parsed out and stored in the `thinking` column. This handles models that embed reasoning in `content` rather than the dedicated `thinking` field.

### OpenRouter backend

Sends a POST to `https://openrouter.ai/api/v1/chat/completions` using the OpenAI-compatible streaming format (SSE lines prefixed with `data: `, terminated with `[DONE]`). The API key is read from `Config\Openrouter::$apikey`.

Key differences from Ollama:

- Images are sent as multimodal `content` arrays with `{type: "image_url", image_url: {url: "<data-url>"}}` parts rather than a bare base64 `images` array.
- Reasoning tokens are read from `choices[0].delta.reasoning` rather than `message.thinking`.
- HTTP errors are detected by inspecting the stream's wrapper metadata (the stream is opened with `ignore_errors: true` so 4xx/5xx responses still return a body to parse).

---

## OpenRouter Model Management (`/admin/ai/openrouter-models`)

This page fetches the full list of available models from the OpenRouter API and displays them with checkboxes. Checking a model and saving adds it to the `openrouter_models` table; unchecking removes it. Only models present in this table appear in the chat model selector when the OpenRouter provider is active. The page shows modality badges (vision, audio) and the model creation date.

The API key must be set in `OPENROUTER_APIKEY` (or directly in `app/Config/Openrouter.php`) for the model list to load.

---

## Chat UI (`/admin/ai/chat`)

The chat view renders an empty shell; all content is loaded and managed by `chat.js`.

### Provider and model selector

The UI exposes both a provider toggle (Ollama / OpenRouter) and a model dropdown. Changing the provider re-fetches the model list from `GET /api/ai/chat/models?provider=<provider>`. The selected provider and model are persisted to `localStorage` (`chat_provider`, `chat_model`).

### Session sidebar

Sessions are loaded via `GET /api/ai/chat/sessions` and grouped into date buckets: **Pinned**, **Today**, **Yesterday**, **Last 7 Days**, **Last 30 Days**, **Older**. Each item has a three-dot context menu with:

- **Pin / Unpin** — `PATCH session/{uuid}` with `{pinned: 0|1}`
- **Rename** — prompts via a Bootstrap modal, then `PATCH session/{uuid}` with `{title}`
- **Delete** — confirmed via a Bootstrap modal, then `DELETE session/{uuid}`

### Sending a message

`Enter` (without Shift) submits. Shift+Enter inserts a newline. While streaming, the send button shows a spinner and is disabled. A typing indicator (`...`) is shown in the assistant bubble until the first token arrives.

### Thinking blocks

Models that emit reasoning emit a collapsible `<details>` block above their response:

- While thinking tokens are arriving: label is **Thinking…**, `details` is `open`
- Once response tokens begin or `done` fires: label is **Thinking**, `details` closes

Two rendering paths exist:
- **Split** (`renderBubbleHtmlSplit`): used when thinking and content arrive in separate fields
- **Embedded** (`renderBubbleHtml`): used for `<think>…</think>` prefixes inside `content`

### Attachments

The attach button (`paperclip`) opens a hidden `<input type="file" multiple>` that accepts images and documents.

| File type | Handling |
|---|---|
| `image/*` | Read as data-URL via `FileReader`; stored in `pendingImages`; sent as base64 to Ollama or as `image_url` parts to OpenRouter |
| `application/pdf` | Uploaded to `POST /api/ai/chat/extract-pdf` (smalot/pdfparser); extracted text stored as a document |
| Text extensions (`.txt`, `.md`, `.csv`, `.js`, `.php`, etc.) | Read as text via `FileReader`; stored in `pendingDocuments` |

Pending attachments are shown as thumbnail previews (images) or chips (documents) above the message input. Each item has a remove button.

### Retry

If the backend connection fails during a stream, the assistant bubble shows an error and a **Retry** button. On retry, the last user message, images, and documents are restored and `sendMessage()` is called again.

If a session is loaded whose last message is from the `user` role (i.e. a previous stream that never completed), a **Retry** prompt is appended to the thread automatically.

### Image lightbox

Clicking any `message-image` opens a full-screen lightbox overlay. Clicking the overlay or pressing Escape dismisses it.

### Search

Cmd/Ctrl+K opens the search modal. Queries are debounced at 300 ms and sent to `GET /api/ai/chat/search?q=…`.

The API searches across session titles and message content for all terms simultaneously (AND logic). Results are limited to 25 and return a `snippet` extracted from the first matching message, centred around the first match position (±60 chars, capped at 180 chars). Results are grouped into **Conversations** (title match) and **Messages** (content-only match) when both types are present.

---

## System Prompt (`/admin/ai/prompt`)

Every save creates a new row in `system_prompts` rather than overwriting. The row with the highest `id` is considered active (`SystemPromptModel::getActive()`). The system prompt is injected as `role: system` for both Ollama and OpenRouter requests.

- **Update**: submitting the form calls `POST /admin/ai/prompt/update`. If the text is unchanged a flash info message is shown and no row is inserted.
- **Revert**: clicking a revision's Revert button shows a Bootstrap confirmation modal, then posts to `POST /admin/ai/prompt/revert/{id}`. If the revision content matches the current active prompt a flash info message is shown and no row is inserted; otherwise a new row is inserted with the revision's content.

The page JS tracks unsaved changes (dirty flag), shows a character count, and fires `beforeunload` if there are unsaved edits.

---

## Dashboard (`/admin/ai/`)

Shows four stat cards (total sessions, total messages, pinned sessions, average messages per session) and a table of the 8 most recently created sessions.

---

## Key Files

| Path | Description |
|---|---|
| [app/Controllers/Ai/Admin/Home.php](app/Controllers/Ai/Admin/Home.php) | Dashboard stats and recent sessions |
| [app/Controllers/Ai/Admin/Chat.php](app/Controllers/Ai/Admin/Chat.php) | Chat view (new and existing session) |
| [app/Controllers/Ai/Admin/Prompt.php](app/Controllers/Ai/Admin/Prompt.php) | System prompt editor: update and revert |
| [app/Controllers/Ai/Admin/OpenrouterModels.php](app/Controllers/Ai/Admin/OpenrouterModels.php) | OpenRouter model whitelist management |
| [app/Controllers/Ai/Api/Chat.php](app/Controllers/Ai/Api/Chat.php) | REST API: sessions, messages, stream (Ollama + OpenRouter), search, PDF extraction |
| [app/Models/ChatSessionModel.php](app/Models/ChatSessionModel.php) | Sessions table model (soft deletes) |
| [app/Models/ChatMessageModel.php](app/Models/ChatMessageModel.php) | Messages table model |
| [app/Models/SystemPromptModel.php](app/Models/SystemPromptModel.php) | System prompts model with `getActive()` |
| [app/Models/OpenrouterModelModel.php](app/Models/OpenrouterModelModel.php) | OpenRouter enabled models table model |
| [app/Config/Openrouter.php](app/Config/Openrouter.php) | OpenRouter configuration (API key) |
| [public/assets/js/ai/admin/chat.js](public/assets/js/ai/admin/chat.js) | Chat UI: sessions, streaming, attachments, search, thinking, retry |
| [public/assets/js/ai/admin/prompt.js](public/assets/js/ai/admin/prompt.js) | Prompt page: dirty tracking, char count, revert modal |
| [public/assets/css/ai/admin/chat.css](public/assets/css/ai/admin/chat.css) | Chat UI styles |
| [public/assets/css/ai/admin/prompt.css](public/assets/css/ai/admin/prompt.css) | Prompt page styles |
