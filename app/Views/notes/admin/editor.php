<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div id="note-editor-container" data-master-key="<?= esc((string)$masterKey) ?>">

    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="<?= site_url('admin/notes') ?>" class="btn btn-outline-primary btn-sm" aria-label="Back to notes">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1 class="h4 mb-0" id="editor-title"><?= esc((string) $title) ?></h1>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-pin" aria-label="Pin note" aria-pressed="false">
                <i class="bi bi-pin-fill"></i>
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-download" aria-label="Download note">
                <i class="bi bi-download"></i>
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-save">
                <i class="bi bi-floppy-fill"></i><span class="d-none d-lg-inline"> Save</span>
            </button>
        </div>
    </div>

    <ul class="nav nav-tabs note-editor__tabs mb-3" id="editor-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-edit" data-bs-toggle="tab" data-bs-target="#panel-edit" type="button" role="tab" aria-controls="panel-edit" aria-selected="true">
                <i class="bi bi-pencil-fill"></i><span class="d-none d-lg-inline"> Edit</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-preview" data-bs-toggle="tab" data-bs-target="#panel-preview" type="button" role="tab" aria-controls="panel-preview" aria-selected="false">
                <i class="bi bi-eye-fill"></i><span class="d-none d-lg-inline"> Preview</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-help" data-bs-toggle="tab" data-bs-target="#panel-help" type="button" role="tab" aria-controls="panel-help" aria-selected="false">
                <i class="bi bi-question-circle-fill"></i><span class="d-none d-lg-inline"> Help</span>
            </button>
        </li>
        <?php if (isset($note_id) && $note_id): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-revisions" data-bs-toggle="tab" data-bs-target="#panel-revisions" type="button" role="tab" aria-controls="panel-revisions" aria-selected="false">
                <i class="bi bi-clock-history"></i><span class="d-none d-lg-inline"> Revisions</span>
            </button>
        </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content" id="editor-tab-content">
        <div class="tab-pane fade show active" id="panel-edit" role="tabpanel" aria-labelledby="tab-edit">
            <textarea id="note-body" class="form-control font-monospace note-editor__textarea" rows="28" placeholder="Start typing your note&hellip;" spellcheck="false" autocorrect="off" autocapitalize="off"></textarea>
        </div>
        <div class="tab-pane fade" id="panel-preview" role="tabpanel" aria-labelledby="tab-preview">
            <div id="note-preview" class="note-editor__preview"></div>
        </div>
        <div class="tab-pane fade" id="panel-help" role="tabpanel" aria-labelledby="tab-help">
            <div class="py-4" id="syntax-guide">

                <h2 class="h5 mb-4 text-secondary">Editor Usage</h2>
                <div class="row g-4 mb-5">

                    <div class="col-lg-6">

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Keyboard Shortcuts</div>
                            <div class="card-body pb-2">
                                <table class="table table-sm table-borderless mb-0 small">
                                    <tbody>
                                        <tr><td class="text-nowrap pe-3"><kbd>Tab</kbd></td><td>Insert 4 spaces</td></tr>
                                        <tr><td class="text-nowrap pe-3"><kbd>Tab</kbd> <span class="text-secondary">(selection)</span></td><td>Indent selected lines by 4 spaces</td></tr>
                                        <tr><td class="text-nowrap pe-3"><kbd>Shift</kbd>+<kbd>Tab</kbd> <span class="text-secondary">(selection)</span></td><td>Outdent selected lines</td></tr>
                                        <tr><td class="text-nowrap pe-3"><kbd>Ctrl</kbd>/<kbd>⌘</kbd>+<kbd>B</kbd></td><td>Toggle <strong>bold</strong> on selection</td></tr>
                                        <tr><td class="text-nowrap pe-3"><kbd>Ctrl</kbd>/<kbd>⌘</kbd>+<kbd>I</kbd></td><td>Toggle <em>italic</em> on selection</td></tr>
                                        <tr><td class="text-nowrap pe-3"><kbd>Ctrl</kbd>/<kbd>⌘</kbd>+<kbd>S</kbd></td><td>Save note</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Backtick Shortcuts</div>
                            <div class="card-body pb-2">
                                <p class="small text-secondary mb-2">Select text and press <kbd>`</kbd> to wrap it in inline code backticks.</p>
                                <p class="small text-secondary mb-0">Type <code>``<wbr>`</code> (three backticks) to insert a fenced code block with the cursor placed inside, ready to type.</p>
                            </div>
                        </div>

                    </div><!-- /.col-lg-6 -->

                    <div class="col-lg-6">

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Smart Lists &amp; Blockquotes</div>
                            <div class="card-body pb-2">
                                <p class="small text-secondary mb-2">Press <kbd>Enter</kbd> at the end of a list item to automatically start the next item. Ordered lists auto-increment the number.</p>
                                <p class="small text-secondary mb-2">Press <kbd>Enter</kbd> on a blockquote line to continue the <code>&gt;</code> prefix on the next line.</p>
                                <p class="small text-secondary mb-0">Press <kbd>Enter</kbd> on an empty list item or blockquote line to exit the structure.</p>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Word Expanders</div>
                            <div class="card-body pb-2">
                                <p class="small text-secondary mb-2">Type a trigger word then press <kbd>Space</kbd> or <kbd>Tab</kbd> to expand it. Triggers are case-insensitive.</p>
                                <table class="table table-sm table-borderless mb-0 small">
                                    <tbody>
                                        <tr><td class="text-nowrap pe-3"><code>lorem</code></td><td>Expands to a Lorem Ipsum paragraph</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div><!-- /.col-lg-6 -->

                </div><!-- /.row -->

                <h2 class="h5 mb-4 text-secondary">GitHub Flavored Markdown Reference</h2>
                <div class="row g-4">

                    <div class="col-lg-6">

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Headings</div>
                            <pre class="p-3 mb-0 rounded-bottom syntax-block"><code># Heading 1
## Heading 2
### Heading 3
#### Heading 4
##### Heading 5
###### Heading 6</code></pre>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Emphasis</div>
                            <pre class="p-3 mb-0 rounded-bottom syntax-block"><code>**Bold text**  or  __Bold text__
*Italic text*  or  _Italic text_
***Bold and italic***
~~Strikethrough~~</code></pre>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Blockquotes</div>
                            <pre class="p-3 mb-0 rounded-bottom syntax-block"><code>&gt; This is a blockquote.
&gt;
&gt; It can span multiple paragraphs.
&gt;
&gt; &gt; Nested blockquote.</code></pre>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Unordered Lists</div>
                            <pre class="p-3 mb-0 rounded-bottom syntax-block"><code>- Item one
- Item two
  - Nested item
  - Nested item
- Item three

* Asterisk also works
+ Plus sign also works</code></pre>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Ordered Lists</div>
                            <pre class="p-3 mb-0 rounded-bottom syntax-block"><code>1. First item
2. Second item
3. Third item
   1. Nested ordered
   2. Nested ordered</code></pre>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Task Lists <span class="badge text-bg-secondary fw-normal ms-1">GFM</span></div>
                            <pre class="p-3 mb-0 rounded-bottom syntax-block"><code>- [x] Completed task
- [ ] Incomplete task
- [x] Another completed task</code></pre>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Horizontal Rule</div>
                            <pre class="p-3 mb-0 rounded-bottom syntax-block"><code>---
***
___</code></pre>
                        </div>

                    </div><!-- /.col-lg-6 -->

                    <div class="col-lg-6">

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Links</div>
                            <pre class="p-3 mb-0 rounded-bottom syntax-block"><code>[Link text](https://example.com)
[With title](https://example.com "Title")

[Reference link][my-ref]

[my-ref]: https://example.com</code></pre>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Images</div>
                            <pre class="p-3 mb-0 rounded-bottom syntax-block"><code>![Alt text](image.jpg)
![Alt text](image.jpg "Optional title")

![Reference image][img-id]

[img-id]: image.jpg</code></pre>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Inline Code &amp; Code Blocks</div>
                            <pre class="p-3 mb-0 rounded-bottom syntax-block"><code>Use `backticks` for inline code.

```javascript
const greet = (name) => {
  console.log(`Hello, ${name}!`);
};
```

```python
def hello(name):
    print(f"Hello, {name}!")
```</code></pre>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Tables <span class="badge text-bg-secondary fw-normal ms-1">GFM</span></div>
                            <pre class="p-3 mb-0 rounded-bottom syntax-block"><code>| Header   | Header   | Header   |
|----------|----------|----------|
| Cell     | Cell     | Cell     |
| Cell     | Cell     | Cell     |

Alignment:

| Left     | Center   | Right    |
|:---------|:--------:|---------:|
| text     | text     | text     |</code></pre>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Autolinks <span class="badge text-bg-secondary fw-normal ms-1">GFM</span></div>
                            <pre class="p-3 mb-0 rounded-bottom syntax-block"><code>https://example.com becomes a link.
user@example.com becomes a mailto link.</code></pre>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Escaping Special Characters</div>
                            <pre class="p-3 mb-0 rounded-bottom syntax-block"><code>\# Not a heading
\*Not italic\*
\`Not inline code\`
\[Not a link\](url)</code></pre>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header fw-semibold">Paragraphs &amp; Line Breaks</div>
                            <div class="card-body pb-2">
                                <p class="small text-secondary mb-2">Separate text with a blank line to create a new paragraph.</p>
                                <p class="small text-secondary mb-0">End a line with two or more spaces, then press enter for a line break within a paragraph.</p>
                            </div>
                        </div>

                    </div><!-- /.col-lg-6 -->

                </div><!-- /.row -->
            </div><!-- /#syntax-guide -->
        </div>
        <?php if (isset($note_id) && $note_id): ?>
        <div class="tab-pane fade" id="panel-revisions" role="tabpanel" aria-labelledby="tab-revisions">
            <div id="revisions-list" class="py-3">
                <p class="text-muted fst-italic">Loading revisions&hellip;</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (isset($note_id) && $note_id): ?>
    <input type="hidden" id="note-id" value="<?= esc((string) $note_id) ?>">
    <?php endif; ?>

</div>

<?php if (isset($note_id) && $note_id): ?>
<div class="modal fade" id="revision-modal" tabindex="-1" aria-labelledby="revision-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="revision-modal-label">Revision &mdash; <span id="revision-modal-date"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs px-3 pt-3" id="revision-modal-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="revision-tab-text" data-bs-toggle="tab" data-bs-target="#revision-panel-text" type="button" role="tab" aria-controls="revision-panel-text" aria-selected="true">Revision</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="revision-tab-diff" data-bs-toggle="tab" data-bs-target="#revision-panel-diff" type="button" role="tab" aria-controls="revision-panel-diff" aria-selected="false">Diff</button>
                    </li>
                </ul>
                <div class="tab-content p-3" id="revision-modal-tab-content">
                    <div class="tab-pane fade show active" id="revision-panel-text" role="tabpanel" aria-labelledby="revision-tab-text">
                        <textarea id="revision-modal-body" class="form-control font-monospace" rows="20" readonly></textarea>
                    </div>
                    <div class="tab-pane fade" id="revision-panel-diff" role="tabpanel" aria-labelledby="revision-tab-diff">
                        <div id="revision-modal-diff" class="revision-diff"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn-restore-revision">Restore this revision</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="revision-delete-modal" tabindex="-1" aria-labelledby="revision-delete-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="revision-delete-modal-label">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="revision-delete-modal-body">
                Are you sure you want to delete this revision?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-delete-revision">Delete</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="editor-toast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
