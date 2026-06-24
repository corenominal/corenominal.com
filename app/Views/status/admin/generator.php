<?= $this->extend('templates/default') ?>
<?= $this->section('content') ?>

<div id="generator" data-master-key="<?= esc(config('ApiKeys')->masterKey) ?>" data-voice-samples="<?= esc(json_encode($voiceSamples)) ?>">

    <div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h1 class="h4 mb-0">Status Generator</h1>
        <div class="d-flex align-items-center gap-2">
            <button id="model-btn" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modelModal">
                <i class="bi bi-cpu"></i> <span id="model-btn-label" class="d-none d-md-inline">Loading…</span>
            </button>
            <button id="restart-btn" class="btn btn-outline-secondary btn-sm d-none">
                <i class="bi bi-arrow-counterclockwise"></i> <span class="d-none d-md-inline">Start Over</span>
            </button>
        </div>
    </div>

    <!-- Progress steps -->
    <div id="progress-bar" class="d-flex align-items-center gap-2 mb-4 text-secondary small">
        <span class="step-indicator active" data-step="0">1. Topic</span>
        <i class="bi bi-chevron-right"></i>
        <span class="step-indicator" data-step="1">2. Interview</span>
        <i class="bi bi-chevron-right"></i>
        <span class="step-indicator" data-step="2">3. Outline</span>
        <i class="bi bi-chevron-right"></i>
        <span class="step-indicator" data-step="3">4. Draft</span>
        <i class="bi bi-chevron-right"></i>
        <span class="step-indicator" data-step="4">5. Polish</span>
    </div>

    <!-- Phase 1: Input -->
    <div id="phase-input" class="phase-panel">
        <div class="card border-0 bg-body-secondary">
            <div class="card-body p-4">
                <h5 class="card-title mb-1">What's on your mind?</h5>
                <p class="text-secondary small mb-3">Describe your topic or raw idea. The AI will then interview you to develop it further.</p>

                <div class="mb-3">
                    <label for="topic-input" class="form-label fw-medium">Topic or idea</label>
                    <textarea id="topic-input" class="form-control" rows="4" placeholder="e.g. Why I think RSS is still the best way to follow the web…" maxlength="2000"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">Questions</label>
                    <div class="d-flex flex-wrap gap-2" id="question-count-selector">
                        <input type="radio" class="btn-check" name="question_count" id="qcount-1" value="1">
                        <label class="btn btn-outline-primary btn-sm" for="qcount-1">1</label>

                        <input type="radio" class="btn-check" name="question_count" id="qcount-2" value="2">
                        <label class="btn btn-outline-primary btn-sm" for="qcount-2">2</label>

                        <input type="radio" class="btn-check" name="question_count" id="qcount-3" value="3" checked>
                        <label class="btn btn-outline-primary btn-sm" for="qcount-3">3</label>

                        <input type="radio" class="btn-check" name="question_count" id="qcount-4" value="4">
                        <label class="btn btn-outline-primary btn-sm" for="qcount-4">4</label>

                        <input type="radio" class="btn-check" name="question_count" id="qcount-5" value="5">
                        <label class="btn btn-outline-primary btn-sm" for="qcount-5">5</label>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">Tone</label>
                    <div class="d-flex flex-wrap gap-2" id="tone-selector">
                        <input type="radio" class="btn-check" name="tone" id="tone-natural" value="Natural" checked>
                        <label class="btn btn-outline-primary btn-sm" for="tone-natural">Natural</label>

                        <input type="radio" class="btn-check" name="tone" id="tone-hot" value="Hot take">
                        <label class="btn btn-outline-primary btn-sm" for="tone-hot">Hot take</label>

                        <input type="radio" class="btn-check" name="tone" id="tone-educational" value="Educational">
                        <label class="btn btn-outline-primary btn-sm" for="tone-educational">Educational</label>

                        <input type="radio" class="btn-check" name="tone" id="tone-storytelling" value="Storytelling">
                        <label class="btn btn-outline-primary btn-sm" for="tone-storytelling">Storytelling</label>

                        <input type="radio" class="btn-check" name="tone" id="tone-announcement" value="Announcement">
                        <label class="btn btn-outline-primary btn-sm" for="tone-announcement">Announcement</label>

                        <input type="radio" class="btn-check" name="tone" id="tone-question" value="Question">
                        <label class="btn btn-outline-primary btn-sm" for="tone-question">Question</label>
                    </div>
                </div>

                <button id="start-btn" class="btn btn-primary">
                    Start Interview <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Phase 2: Interview -->
    <div id="phase-interview" class="phase-panel d-none">
        <div class="card border-0 bg-body-secondary mb-3">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="flex-shrink-0 text-primary"><i class="bi bi-chat-quote fs-4"></i></div>
                    <div class="flex-grow-1">
                        <div class="fw-medium mb-1 text-secondary small" id="question-counter">Question 1</div>
                        <div id="question-text" class="streaming-text">
                            <span class="spinner-border spinner-border-sm text-secondary me-2"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="answer-area" class="d-none">
            <div class="mb-3">
                <label for="answer-input" class="form-label fw-medium">Your answer</label>
                <textarea id="answer-input" class="form-control" rows="3" placeholder="Type your answer…"></textarea>
            </div>
            <button id="answer-btn" class="btn btn-primary">
                Continue <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>

        <div id="generating-summary" class="d-none text-secondary small">
            <span class="spinner-border spinner-border-sm me-2"></span>Generating summary…
        </div>
    </div>

    <!-- Phase 3: Outline -->
    <div id="phase-outline" class="phase-panel d-none">

        <!-- Summary card -->
        <div class="card border-0 bg-body-secondary mb-4">
            <div class="card-body p-4">
                <h6 class="card-title text-secondary small fw-semibold text-uppercase mb-2">Summary</h6>
                <div id="summary-text" class="streaming-text"></div>
            </div>
        </div>

        <!-- Outline editor -->
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h5 class="mb-0">Outline</h5>
                <button id="add-outline-item-btn" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-plus-lg"></i> Add point
                </button>
            </div>
            <p class="text-secondary small mb-3">Edit, reorder, or remove points to shape your narrative.</p>
            <div id="outline-list" class="list-group mb-3"></div>
        </div>

        <div id="generating-outline" class="text-secondary small mb-3 d-none">
            <span class="spinner-border spinner-border-sm me-2"></span>Building outline…
        </div>

        <button id="submit-outline-btn" class="btn btn-primary d-none">
            Generate Drafts <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>

    <!-- Phase 4: Draft -->
    <div id="phase-draft" class="phase-panel d-none">

        <div class="mb-3">
            <button id="back-to-outline-btn" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Outline
            </button>
        </div>

        <div id="generating-draft" class="text-secondary small mb-3">
            <span class="spinner-border spinner-border-sm me-2"></span>Generating drafts…
        </div>

        <div id="draft-variations" class="d-none">
            <div class="row g-3 mb-4">
                <!-- Variation A -->
                <div class="col-md-6">
                    <div class="card border-0 bg-body-secondary h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <h6 class="fw-semibold mb-1">Variation A <span class="badge bg-secondary fw-normal ms-1">Single post</span></h6>
                            <div id="draft-a-text" class="mb-3 flex-grow-1" style="white-space: pre-wrap;"></div>
                            <div id="draft-a-count" class="text-secondary small mb-3"></div>
                            <div class="mb-2">
                                <input type="text" id="feedback-a" class="form-control form-control-sm" placeholder="Feedback (optional)">
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-secondary btn-sm revise-btn" data-variation="a">
                                    <i class="bi bi-arrow-repeat"></i> Revise
                                </button>
                                <button class="btn btn-primary btn-sm select-variation-btn flex-grow-1" data-variation="a">
                                    Use this <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Variation B -->
                <div class="col-md-6">
                    <div class="card border-0 bg-body-secondary h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <h6 class="fw-semibold mb-1">Variation B <span class="badge bg-secondary fw-normal ms-1">Plain English</span></h6>
                            <div id="draft-b-text" class="mb-3 flex-grow-1" style="white-space: pre-wrap;"></div>
                            <div id="draft-b-count" class="text-secondary small mb-3"></div>
                            <div class="mb-2">
                                <input type="text" id="feedback-b" class="form-control form-control-sm" placeholder="Feedback (optional)">
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-secondary btn-sm revise-btn" data-variation="b">
                                    <i class="bi bi-arrow-repeat"></i> Revise
                                </button>
                                <button class="btn btn-primary btn-sm select-variation-btn flex-grow-1" data-variation="b">
                                    Use this <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Phase 5: Polish -->
    <div id="phase-polish" class="phase-panel d-none">
        <div class="mb-3">
            <button id="back-to-draft-btn" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Drafts
            </button>
        </div>
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h5 class="mb-0">Final Text</h5>
                <div class="d-flex gap-2">
                    <button id="save-draft-btn" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-floppy"></i> Save as Draft
                    </button>
                    <button id="copy-btn" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                </div>
            </div>
            <textarea id="final-textarea" class="form-control font-monospace" rows="8"></textarea>
        </div>
        <div class="d-flex align-items-center justify-content-between gap-3">
            <div id="char-counts" class="text-secondary small"></div>
            <div id="draft-save-feedback" class="small"></div>
        </div>
    </div>

</div>

<!-- Model selection modal -->
<div class="modal fade" id="modelModal" tabindex="-1" aria-labelledby="modelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="modelModalLabel"><i class="bi bi-gear-fill me-2"></i>AI Model</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="px-3 pt-2">
                <ul class="nav nav-tabs" id="provider-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-ollama" data-provider="ollama" type="button" role="tab">
                            <i class="bi bi-hdd-fill me-1"></i>Ollama
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-openrouter" data-provider="openrouter" type="button" role="tab">
                            <i class="bi bi-cloud-fill me-1"></i>OpenRouter
                        </button>
                    </li>
                </ul>
            </div>
            <div class="modal-body p-0" id="model-list">
                <p class="text-secondary text-center small py-4 mb-0">Loading models…</p>
            </div>
            <div class="modal-footer border-0 d-block pt-0">
                <p class="text-secondary small mb-0">The selected model will be used for this session.</p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
