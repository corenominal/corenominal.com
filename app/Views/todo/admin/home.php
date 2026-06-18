<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>
<div class="container px-0">
    <div class="row">
        <div class="col-12">

            <div class="mb-4 pb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h1 class="h3 mb-0">TODO List</h1>
                <!-- Search bar -->
                <div class="d-flex gap-2" style="min-width: 280px; max-width: 420px; flex: 1 1 280px;">
                    <div class="input-group input-group-sm">
                        <input type="text" id="search-input" class="form-control" placeholder="Search todos…" autocomplete="off">
                        <button class="btn btn-outline-primary" id="search-btn" type="button" title="Search">
                            <i class="bi bi-search"></i>
                        </button>
                        <button class="btn btn-outline-primary d-none" id="clear-search-btn" type="button" title="Clear search">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Active filters bar -->
            <div id="filter-bar" class="d-none alert border text-body py-2 d-flex flex-wrap align-items-center gap-2 mb-3">
                <i class="bi bi-funnel-fill"></i>
                <span id="filter-bar-text"></span>
                <button class="btn btn-sm btn-primary ms-auto" id="clear-filters-btn">
                    <i class="bi bi-x-lg me-1"></i>Clear filters
                </button>
            </div>

            <!-- Create form -->
            <div class="card mb-4" id="create-form-card">
                <div class="card-header py-2 d-flex align-items-center justify-content-between">
                    <span class="fw-semibold"><i class="bi bi-plus-circle me-1"></i>New TODO item</span>
                    <button class="btn-close" type="button" data-bs-toggle="collapse" data-bs-target="#create-form-body" aria-expanded="true" aria-label="Close"></button>
                </div>
                <div class="collapse show" id="create-form-body">
                <div class="card-body">
                    <form id="create-todo-form" novalidate>
                        <div class="mb-2">
                            <textarea
                                class="form-control font-monospace"
                                id="todo-markdown"
                                rows="4"
                                placeholder="Write your todo in Markdown…"
                                required></textarea>
                        </div>
                        <div class="row g-2 align-items-center">
                            <div class="col">
                                <input
                                    type="text"
                                    class="form-control"
                                    id="todo-category"
                                    placeholder="Category (optional — defaults to uncategorised)"
                                    list="category-datalist"
                                    autocomplete="off">
                                <datalist id="category-datalist"></datalist>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary" id="create-btn">
                                    <i class="bi bi-plus-lg me-1"></i>Create
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs" id="todo-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-todo-btn" data-bs-toggle="tab"
                        data-bs-target="#pane-todo" type="button" role="tab"
                        aria-controls="pane-todo" aria-selected="true" data-tab="todo">
                        <i class="bi bi-list-check me-1"></i><span class="d-none d-md-inline">TODO</span>
                        <span class="badge bg-primary ms-1" id="tab-todo-count">…</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-complete-btn" data-bs-toggle="tab"
                        data-bs-target="#pane-complete" type="button" role="tab"
                        aria-controls="pane-complete" aria-selected="false" data-tab="complete">
                        <i class="bi bi-check-circle me-1"></i><span class="d-none d-md-inline">Completed</span>
                        <span class="badge bg-primary ms-1" id="tab-complete-count">…</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-deleted-btn" data-bs-toggle="tab"
                        data-bs-target="#pane-deleted" type="button" role="tab"
                        aria-controls="pane-deleted" aria-selected="false" data-tab="deleted">
                        <i class="bi bi-trash me-1"></i><span class="d-none d-md-inline">Deleted</span>
                        <span class="badge bg-primary ms-1" id="tab-deleted-count">…</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-categories-btn" data-bs-toggle="tab"
                        data-bs-target="#pane-categories" type="button" role="tab"
                        aria-controls="pane-categories" aria-selected="false" data-tab="categories">
                        <i class="bi bi-tags me-1"></i><span class="d-none d-md-inline">Categories</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-help-btn" data-bs-toggle="tab"
                        data-bs-target="#pane-help" type="button" role="tab"
                        aria-controls="pane-help" aria-selected="false" data-tab="help">
                        <i class="bi bi-question-circle me-1"></i><span class="d-none d-md-inline">Help</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content border border-top-0 rounded-bottom p-3 mb-5" id="todo-tabs-content">
                <div class="tab-pane fade show active" id="pane-todo" role="tabpanel" aria-labelledby="tab-todo-btn">
                    <div id="items-todo"><div class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary" role="status"></div></div></div>
                    <div id="pagination-todo" class="mt-3"></div>
                </div>
                <div class="tab-pane fade" id="pane-complete" role="tabpanel" aria-labelledby="tab-complete-btn">
                    <div id="items-complete"><div class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary" role="status"></div></div></div>
                    <div id="pagination-complete" class="mt-3"></div>
                </div>
                <div class="tab-pane fade" id="pane-deleted" role="tabpanel" aria-labelledby="tab-deleted-btn">
                    <div id="items-deleted"><div class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary" role="status"></div></div></div>
                    <div id="pagination-deleted" class="mt-3"></div>
                </div>
                <div class="tab-pane fade" id="pane-categories" role="tabpanel" aria-labelledby="tab-categories-btn">
                    <div id="items-categories"></div>
                </div>
                <div class="tab-pane fade" id="pane-help" role="tabpanel" aria-labelledby="tab-help-btn">

                    <!-- Keyboard Shortcuts -->
                    <h6 class="text-secondary text-uppercase fw-semibold mb-3 mt-1 help-section-heading">Keyboard Shortcuts</h6>
                    <div class="row g-3 mb-5">
                        <div class="col-12 col-md-6">
                            <div class="card h-100">
                                <div class="card-header py-2 fw-semibold"><i class="bi bi-keyboard me-1"></i>Global</div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="ps-3 pe-2 py-2 text-nowrap"><kbd>Ctrl</kbd> / <kbd>⌘</kbd> + <kbd>F</kbd></td>
                                                <td class="text-secondary pe-3 py-2">Focus the search bar</td>
                                            </tr>
                                            <tr>
                                                <td class="ps-3 pe-2 py-2 text-nowrap"><kbd>Ctrl</kbd> / <kbd>⌘</kbd> + <kbd>S</kbd></td>
                                                <td class="text-secondary pe-3 py-2">Submit the new TODO form</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="card h-100">
                                <div class="card-header py-2 fw-semibold"><i class="bi bi-pencil me-1"></i>Markdown Editor</div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="ps-3 pe-2 py-2 text-nowrap"><kbd>Ctrl</kbd> / <kbd>⌘</kbd> + <kbd>B</kbd></td>
                                                <td class="text-secondary pe-3 py-2">Toggle <strong>bold</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-3 pe-2 py-2 text-nowrap"><kbd>Ctrl</kbd> / <kbd>⌘</kbd> + <kbd>I</kbd></td>
                                                <td class="text-secondary pe-3 py-2">Toggle <em>italic</em></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-3 pe-2 py-2 text-nowrap"><kbd>`</kbd> with selection</td>
                                                <td class="text-secondary pe-3 py-2">Wrap in <code>inline code</code></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-3 pe-2 py-2 text-nowrap"><kbd>`</kbd><kbd>`</kbd><kbd>`</kbd></td>
                                                <td class="text-secondary pe-3 py-2">Insert fenced code block</td>
                                            </tr>
                                            <tr>
                                                <td class="ps-3 pe-2 py-2 text-nowrap"><kbd>Enter</kbd></td>
                                                <td class="text-secondary pe-3 py-2">Continue list or blockquote</td>
                                            </tr>
                                            <tr>
                                                <td class="ps-3 pe-2 py-2 text-nowrap"><kbd>Ctrl</kbd> / <kbd>⌘</kbd> + <kbd>S</kbd></td>
                                                <td class="text-secondary pe-3 py-2">Save edited item</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- GitHub Flavored Markdown Reference -->
                    <h6 class="text-secondary text-uppercase fw-semibold mb-3 help-section-heading">GitHub Flavored Markdown</h6>
                    <div class="row g-3">
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-header py-2 fw-semibold">Headings</div>
                                <div class="card-body py-2">
                                    <table class="table table-sm table-borderless font-monospace mb-0">
                                        <tbody>
                                            <tr><td class="py-1"><code># Heading 1</code></td></tr>
                                            <tr><td class="py-1"><code>## Heading 2</code></td></tr>
                                            <tr><td class="py-1"><code>### Heading 3</code></td></tr>
                                            <tr><td class="py-1"><code>#### Heading 4</code></td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-header py-2 fw-semibold">Emphasis</div>
                                <div class="card-body py-2">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="py-1"><code>**bold**</code></td>
                                                <td class="py-1 text-secondary"><strong>bold</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="py-1"><code>*italic*</code></td>
                                                <td class="py-1 text-secondary"><em>italic</em></td>
                                            </tr>
                                            <tr>
                                                <td class="py-1"><code>~~strikethrough~~</code></td>
                                                <td class="py-1 text-secondary"><del>strikethrough</del></td>
                                            </tr>
                                            <tr>
                                                <td class="py-1"><code>***bold italic***</code></td>
                                                <td class="py-1 text-secondary"><strong><em>bold italic</em></strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-header py-2 fw-semibold">Code</div>
                                <div class="card-body py-2">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="py-1"><code>`inline code`</code></td>
                                                <td class="py-1 text-secondary"><code>inline code</code></td>
                                            </tr>
                                            <tr>
                                                <td class="py-1" colspan="2">
<pre class="mb-0 small"><code>```language
code block
```</code></pre>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-header py-2 fw-semibold">Links &amp; Images</div>
                                <div class="card-body py-2">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="py-1"><code>[text](url)</code></td>
                                                <td class="py-1 text-secondary">Link</td>
                                            </tr>
                                            <tr>
                                                <td class="py-1"><code>[text](url "title")</code></td>
                                                <td class="py-1 text-secondary">Link with title</td>
                                            </tr>
                                            <tr>
                                                <td class="py-1"><code>![alt](url)</code></td>
                                                <td class="py-1 text-secondary">Image</td>
                                            </tr>
                                            <tr>
                                                <td class="py-1"><code>&lt;url&gt;</code></td>
                                                <td class="py-1 text-secondary">Autolink</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-header py-2 fw-semibold">Lists</div>
                                <div class="card-body py-2">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="py-1"><code>- item</code> or <code>* item</code></td>
                                                <td class="py-1 text-secondary">Unordered</td>
                                            </tr>
                                            <tr>
                                                <td class="py-1"><code>1. item</code></td>
                                                <td class="py-1 text-secondary">Ordered</td>
                                            </tr>
                                            <tr>
                                                <td class="py-1"><code>- [ ] task</code></td>
                                                <td class="py-1 text-secondary">Task (open)</td>
                                            </tr>
                                            <tr>
                                                <td class="py-1"><code>- [x] task</code></td>
                                                <td class="py-1 text-secondary">Task (done)</td>
                                            </tr>
                                            <tr>
                                                <td class="py-1"><code>&nbsp;&nbsp;- nested</code></td>
                                                <td class="py-1 text-secondary">Nested (2 spaces)</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-header py-2 fw-semibold">Blockquotes</div>
                                <div class="card-body py-2">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="py-1"><code>&gt; quote</code></td>
                                                <td class="py-1 text-secondary">Single level</td>
                                            </tr>
                                            <tr>
                                                <td class="py-1"><code>&gt;&gt; nested</code></td>
                                                <td class="py-1 text-secondary">Nested quote</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-header py-2 fw-semibold">Tables</div>
                                <div class="card-body py-2">
<pre class="small mb-2"><code>| Col 1  | Col 2  |
|--------|--------|
| cell   | cell   |
| cell   | cell   |</code></pre>
                                    <p class="text-secondary small mb-0">Align columns: <code>:---</code> left, <code>:---:</code> centre, <code>---:</code> right.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-header py-2 fw-semibold">Other</div>
                                <div class="card-body py-2">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="py-1"><code>---</code> or <code>***</code></td>
                                                <td class="py-1 text-secondary">Horizontal rule</td>
                                            </tr>
                                            <tr>
                                                <td class="py-1"><code>\*escaped\*</code></td>
                                                <td class="py-1 text-secondary">Escape special chars</td>
                                            </tr>
                                            <tr>
                                                <td class="py-1"><code>&lt;sub&gt;text&lt;/sub&gt;</code></td>
                                                <td class="py-1 text-secondary">Subscript</td>
                                            </tr>
                                            <tr>
                                                <td class="py-1"><code>&lt;sup&gt;text&lt;/sup&gt;</code></td>
                                                <td class="py-1 text-secondary">Superscript</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div><!-- /.col-12 -->
    </div><!-- /.row -->
</div><!-- /.container-xl -->

<!-- Change Category Modal -->
<div class="modal fade" id="changeCategoryModal" tabindex="-1"
    aria-labelledby="changeCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeCategoryModalLabel">
                    <i class="bi bi-tag me-1"></i>Change Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control" id="modal-category-input"
                    placeholder="Category" list="category-datalist" autocomplete="off">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="save-category-modal-btn">
                    <i class="bi bi-check-lg me-1"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1200;">
    <div id="app-toast" class="toast align-items-center border-0" role="alert"
        aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="app-toast-body"></div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
