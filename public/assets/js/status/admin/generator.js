'use strict';

// ===== CONSTANTS =====
const PHASES = { INPUT: 0, INTERVIEW: 1, OUTLINE: 2, DRAFT: 3, POLISH: 4 };
const CHAR_LIMIT        = 500;
const MODEL_KEY         = 'generator_model';
const PROVIDER_KEY      = 'generator_provider';
const INTERVIEW_COMPLETE = 'INTERVIEW_COMPLETE';

// ===== STATE =====
let state = {
    phase:      PHASES.INPUT,
    topic:      '',
    tone:       '',
    maxQuestions: 3,
    messages:   [],   // [{role, content}] full interview history
    qCount:     0,
    summary:    '',
    outline:    [],   // [{label, text}]
    draftA:     '',
    draftB:     '',
};

let selectedModel    = '';
let selectedProvider = 'ollama';
const modelCache     = {};
let masterKey        = '';
let voiceSamples     = [];
let isStreaming      = false;

// ===== DOM REFS =====
let phaseInput, phaseInterview, phaseOutline, phaseDraft, phasePolish;
let topicInput, startBtn, restartBtn;
let questionCounter, questionText, answerArea, answerInput, answerBtn, generatingSummary;
let summaryText, outlineList, addOutlineItemBtn, generatingOutline, submitOutlineBtn;
let generatingDraft, draftVariations, draftAText, draftBText, draftACount, draftBCount;
let feedbackA, feedbackB;
let finalTextarea, charCounts, copyBtn, saveDraftBtn, draftSaveFeedback;
let modelBtn, modelBtnLabel, modelList;

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('generator');
    masterKey    = container?.dataset.masterKey || '';
    voiceSamples = JSON.parse(container?.dataset.voiceSamples || '[]');

    phaseInput     = document.getElementById('phase-input');
    phaseInterview = document.getElementById('phase-interview');
    phaseOutline   = document.getElementById('phase-outline');
    phaseDraft     = document.getElementById('phase-draft');
    phasePolish    = document.getElementById('phase-polish');

    topicInput  = document.getElementById('topic-input');
    startBtn    = document.getElementById('start-btn');
    restartBtn  = document.getElementById('restart-btn');

    questionCounter   = document.getElementById('question-counter');
    questionText      = document.getElementById('question-text');
    answerArea        = document.getElementById('answer-area');
    answerInput       = document.getElementById('answer-input');
    answerBtn         = document.getElementById('answer-btn');
    generatingSummary = document.getElementById('generating-summary');

    summaryText        = document.getElementById('summary-text');
    outlineList        = document.getElementById('outline-list');
    addOutlineItemBtn  = document.getElementById('add-outline-item-btn');
    generatingOutline  = document.getElementById('generating-outline');
    submitOutlineBtn   = document.getElementById('submit-outline-btn');

    generatingDraft  = document.getElementById('generating-draft');
    draftVariations  = document.getElementById('draft-variations');
    draftAText       = document.getElementById('draft-a-text');
    draftBText       = document.getElementById('draft-b-text');
    draftACount      = document.getElementById('draft-a-count');
    draftBCount      = document.getElementById('draft-b-count');
    feedbackA        = document.getElementById('feedback-a');
    feedbackB        = document.getElementById('feedback-b');

    finalTextarea      = document.getElementById('final-textarea');
    charCounts         = document.getElementById('char-counts');
    copyBtn            = document.getElementById('copy-btn');
    saveDraftBtn       = document.getElementById('save-draft-btn');
    draftSaveFeedback  = document.getElementById('draft-save-feedback');
    modelBtn      = document.getElementById('model-btn');
    modelBtnLabel = document.getElementById('model-btn-label');
    modelList     = document.getElementById('model-list');

    startBtn.addEventListener('click', startInterview);
    restartBtn.addEventListener('click', restart);
    answerBtn.addEventListener('click', submitAnswer);
    answerInput.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); submitAnswer(); }
    });
    addOutlineItemBtn.addEventListener('click', addOutlineItem);
    submitOutlineBtn.addEventListener('click', submitOutline);
    finalTextarea.addEventListener('input', () => { updateCharCounts(); draftSaveFeedback.textContent = ''; });
    copyBtn.addEventListener('click', copyToClipboard);
    saveDraftBtn.addEventListener('click', saveDraft);

    document.querySelectorAll('.revise-btn').forEach(btn => {
        btn.addEventListener('click', () => reviseDraft(btn.dataset.variation));
    });
    document.querySelectorAll('.select-variation-btn').forEach(btn => {
        btn.addEventListener('click', () => selectVariation(btn.dataset.variation));
    });

    selectedProvider = localStorage.getItem(PROVIDER_KEY) || 'ollama';
    setActiveProviderTab(selectedProvider, false);

    document.querySelectorAll('#provider-tabs [data-provider]').forEach(btn => {
        btn.addEventListener('click', () => setActiveProviderTab(btn.dataset.provider, true));
    });
    document.getElementById('modelModal').addEventListener('show.bs.modal', () => {
        setActiveProviderTab(selectedProvider, true);
    });

    loadModels(selectedProvider);
});

// ===== PHASE NAVIGATION =====
function showPhase(phase) {
    state.phase = phase;
    phaseInput.classList.toggle('d-none', phase !== PHASES.INPUT);
    phaseInterview.classList.toggle('d-none', phase !== PHASES.INTERVIEW);
    phaseOutline.classList.toggle('d-none', phase !== PHASES.OUTLINE);
    phaseDraft.classList.toggle('d-none', phase !== PHASES.DRAFT);
    phasePolish.classList.toggle('d-none', phase !== PHASES.POLISH);

    document.querySelectorAll('.step-indicator').forEach(el => {
        const step = parseInt(el.dataset.step, 10);
        el.classList.toggle('active', step === phase);
        el.classList.toggle('done', step < phase);
    });

    restartBtn.classList.toggle('d-none', phase === PHASES.INPUT);
}

function restart() {
    state = {
        phase: PHASES.INPUT, topic: '', tone: '', maxQuestions: 3,
        messages: [], qCount: 0, summary: '', outline: [], draftA: '', draftB: '',
    };
    topicInput.value = '';
    answerInput.value = '';
    finalTextarea.value = '';
    showPhase(PHASES.INPUT);
}

// ===== PHASE 1: START INTERVIEW =====
async function startInterview() {
    const topic        = topicInput.value.trim();
    const tone         = document.querySelector('input[name="tone"]:checked')?.value || 'Chill';
    const maxQuestions = parseInt(document.querySelector('input[name="question_count"]:checked')?.value || '3', 10);

    if (!topic) {
        topicInput.focus();
        topicInput.classList.add('is-invalid');
        topicInput.addEventListener('input', () => topicInput.classList.remove('is-invalid'), { once: true });
        return;
    }
    if (!selectedModel) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modelModal')).show();
        return;
    }

    state.topic        = topic;
    state.tone         = tone;
    state.maxQuestions = maxQuestions;
    state.messages     = [];
    state.qCount       = 0;

    showPhase(PHASES.INTERVIEW);
    questionText.innerHTML = '<span class="spinner-border spinner-border-sm text-secondary me-2"></span>';
    answerArea.classList.add('d-none');

    const systemPrompt = buildInterviewSystemPrompt(topic, tone);
    const messages = [
        { role: 'system', content: systemPrompt },
        { role: 'user', content: `My topic: ${topic}\nDesired tone: ${tone}` },
    ];

    await streamQuestion(messages);
}

function buildVoiceContext() {
    if (!voiceSamples.length) return '';
    const samples = voiceSamples.map(s => `- ${s.trim()}`).join('\n');
    return `\n\nThe user's writing style — study these real examples of their previous posts and match their voice, vocabulary, sentence length, and tone:\n${samples}`;
}

function buildInterviewSystemPrompt(topic, tone) {
    const n = state.maxQuestions;
    return `You are a sharp interviewer helping the user develop their thoughts before posting on Mastodon.

Topic: "${topic}"
Tone: ${tone}

Your job is to ask exactly ${n} targeted question${n === 1 ? '' : 's'}, ONE AT A TIME, to explore the user's perspective and surface unique angles. Do not ask all questions at once.

Rules:
- Ask only one question per response. Keep it concise (1–2 sentences).
- After you have asked exactly ${n} question${n === 1 ? '' : 's'} and received answer${n === 1 ? '' : 's'}, output the exact text: ${INTERVIEW_COMPLETE}
- Never output ${INTERVIEW_COMPLETE} before asking all ${n} question${n === 1 ? '' : 's'}.
- Do not include any other text when outputting ${INTERVIEW_COMPLETE}.`;
}

// ===== PHASE 2: INTERVIEW LOOP =====
async function streamQuestion(messages) {
    state.qCount++;
    questionCounter.textContent = `Question ${state.qCount}`;
    questionText.innerHTML = '<span class="spinner-border spinner-border-sm text-secondary me-2"></span>';
    answerArea.classList.add('d-none');
    answerInput.value = '';

    let accumulated = '';

    await streamSSE(messages, (chunk) => {
        accumulated += chunk;

        if (accumulated.includes(INTERVIEW_COMPLETE)) {
            return;
        }

        questionText.textContent = accumulated;
    }, () => {
        if (accumulated.includes(INTERVIEW_COMPLETE)) {
            moveToSummary();
        } else {
            const assistantMsg = { role: 'assistant', content: accumulated };
            state.messages.push(assistantMsg);
            answerArea.classList.remove('d-none');
            answerInput.focus();
        }
    });
}

async function submitAnswer() {
    const answer = answerInput.value.trim();
    if (!answer || isStreaming) return;

    state.messages.push({ role: 'user', content: answer });
    answerArea.classList.add('d-none');

    const systemPrompt = buildInterviewSystemPrompt(state.topic, state.tone);
    const messages = [
        { role: 'system', content: systemPrompt },
        { role: 'user', content: `My topic: ${state.topic}\nDesired tone: ${state.tone}` },
        ...state.messages,
    ];

    await streamQuestion(messages);
}

async function moveToSummary() {
    answerArea.classList.add('d-none');
    generatingSummary.classList.remove('d-none');

    const summaryMessages = [
        {
            role: 'system',
            content: `You are summarising a topic exploration interview. Based on the conversation, write a clear and concise summary of the user's core arguments and main takeaways. Use 3–5 bullet points. Plain text only — no markdown headers or bold. Each bullet on its own line starting with •`,
        },
        ...state.messages,
        { role: 'user', content: 'Please summarise the key points from our conversation.' },
    ];

    showPhase(PHASES.OUTLINE);
    summaryText.innerHTML = '<span class="spinner-border spinner-border-sm text-secondary me-2"></span>';
    generatingOutline.classList.remove('d-none');
    submitOutlineBtn.classList.add('d-none');
    outlineList.innerHTML = '';

    let summaryAccumulated = '';
    await streamSSE(summaryMessages, (chunk) => {
        summaryAccumulated += chunk;
        summaryText.textContent = summaryAccumulated;
    }, async () => {
        state.summary = summaryAccumulated;
        generatingSummary.classList.add('d-none');
        await generateOutline();
    });
}

// ===== PHASE 3: OUTLINE =====
async function generateOutline() {
    const outlineMessages = [
        {
            role: 'system',
            content: `You convert a topic summary into a structured Mastodon post outline. Respond with ONLY valid JSON, no other text, using this exact format:
{"outline":[{"label":"Hook","text":"..."},{"label":"Main Point","text":"..."},{"label":"Supporting Detail","text":"..."},{"label":"Conclusion","text":"..."}]}
Each text value should be a concise single sentence or short phrase. Do not add extra fields.`,
        },
        { role: 'user', content: `Tone: ${state.tone}\n\nSummary:\n${state.summary}` },
    ];

    let accumulated = '';

    await streamSSE(outlineMessages, (chunk) => {
        accumulated += chunk;
    }, () => {
        generatingOutline.classList.add('d-none');

        let parsed = null;
        try {
            const jsonStr = accumulated.replace(/^```(?:json)?\s*/i, '').replace(/```\s*$/i, '').trim();
            parsed = JSON.parse(jsonStr);
        } catch (e) {
            outlineList.innerHTML = `<div class="alert alert-danger">Failed to parse outline. Please try again.</div>`;
            return;
        }

        state.outline = parsed.outline || [];
        renderOutlineList();
        submitOutlineBtn.classList.remove('d-none');
    });
}

function renderOutlineList() {
    outlineList.innerHTML = '';
    state.outline.forEach((item, index) => {
        outlineList.appendChild(renderOutlineItem(item, index));
    });
}

function renderOutlineItem(item, index) {
    const li = document.createElement('div');
    li.className = 'list-group-item list-group-item-action outline-item d-flex align-items-start gap-2 py-3';
    li.dataset.index = index;

    const reorderBtns = document.createElement('div');
    reorderBtns.className = 'd-flex flex-column gap-1 flex-shrink-0 pt-1';
    reorderBtns.innerHTML = `
        <button class="btn btn-sm outline-move-btn p-0 lh-1 border-0 bg-transparent text-secondary" data-dir="up" title="Move up" ${index === 0 ? 'disabled' : ''}>
            <i class="bi bi-chevron-up"></i>
        </button>
        <button class="btn btn-sm outline-move-btn p-0 lh-1 border-0 bg-transparent text-secondary" data-dir="down" title="Move down" ${index === state.outline.length - 1 ? 'disabled' : ''}>
            <i class="bi bi-chevron-down"></i>
        </button>`;

    const body = document.createElement('div');
    body.className = 'flex-grow-1';

    const label = document.createElement('div');
    label.className = 'text-secondary small fw-semibold mb-1';
    label.contentEditable = 'true';
    label.textContent = item.label;
    label.addEventListener('blur', () => { state.outline[index].label = label.textContent.trim(); });

    const text = document.createElement('div');
    text.contentEditable = 'true';
    text.textContent = item.text;
    text.addEventListener('blur', () => { state.outline[index].text = text.textContent.trim(); });

    body.appendChild(label);
    body.appendChild(text);

    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'btn btn-sm p-0 lh-1 border-0 bg-transparent text-secondary flex-shrink-0 pt-1';
    deleteBtn.title = 'Remove';
    deleteBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
    deleteBtn.addEventListener('click', () => {
        state.outline.splice(index, 1);
        renderOutlineList();
    });

    reorderBtns.querySelectorAll('.outline-move-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const dir = btn.dataset.dir;
            const newIndex = dir === 'up' ? index - 1 : index + 1;
            if (newIndex < 0 || newIndex >= state.outline.length) return;
            [state.outline[index], state.outline[newIndex]] = [state.outline[newIndex], state.outline[index]];
            renderOutlineList();
        });
    });

    li.appendChild(reorderBtns);
    li.appendChild(body);
    li.appendChild(deleteBtn);

    return li;
}

function addOutlineItem() {
    state.outline.push({ label: 'New point', text: 'Add your content here' });
    renderOutlineList();
    const items = outlineList.querySelectorAll('.outline-item');
    const last  = items[items.length - 1];
    if (last) {
        const editableText = last.querySelectorAll('[contenteditable]')[1];
        if (editableText) {
            editableText.focus();
            document.execCommand('selectAll');
        }
    }
}

function submitOutline() {
    // Sync any in-focus edits
    state.outline.forEach((item, i) => {
        const li    = outlineList.querySelector(`[data-index="${i}"]`);
        const parts = li ? li.querySelectorAll('[contenteditable]') : [];
        if (parts[0]) item.label = parts[0].textContent.trim();
        if (parts[1]) item.text  = parts[1].textContent.trim();
    });

    showPhase(PHASES.DRAFT);
    generateDraft();
}

// ===== PHASE 4: DRAFT =====
async function generateDraft() {
    generatingDraft.classList.remove('d-none');
    draftVariations.classList.add('d-none');
    draftAText.textContent = '';
    draftBText.textContent = '';

    const outlineStr = state.outline.map(i => `${i.label}: ${i.text}`).join('\n');

    const draftMessages = [
        {
            role: 'system',
            content: `You write Mastodon post drafts. Respond with ONLY valid JSON using this exact format:
{"variation_a":"...","variation_b":"tweet1 text\\n---\\ntweet2 text\\n---\\ntweet3 text"}

Rules:
- variation_a: a single punchy post, under ${CHAR_LIMIT} characters. No hashtags unless critical.
- variation_b: rewrite the same idea in plain, simple English — short words, short sentences, as if written by a child. Under ${CHAR_LIMIT} characters. No hashtags.
- Tone: ${state.tone}. British English. Friendly and direct.
- Never use: emoji, em dashes, colons, semi-colons, ellipses, exclamation marks, or markdown.
- Never use fluffy, vague, or advanced vocabulary in either variation.
- Output raw JSON only — no code blocks, no commentary.${buildVoiceContext()}`,
        },
        {
            role: 'user',
            content: `Outline:\n${outlineStr}`,
        },
    ];

    let accumulated = '';

    await streamSSE(draftMessages, (chunk) => {
        accumulated += chunk;
    }, () => {
        generatingDraft.classList.add('d-none');

        let parsed = null;
        try {
            const jsonStr = accumulated.replace(/^```(?:json)?\s*/i, '').replace(/```\s*$/i, '').trim();
            parsed = JSON.parse(jsonStr);
        } catch (e) {
            generatingDraft.textContent = 'Failed to parse drafts. Please go back and try again.';
            generatingDraft.classList.remove('d-none');
            return;
        }

        state.draftA = parsed.variation_a || '';
        state.draftB = parsed.variation_b || '';

        renderDraftVariations();
        draftVariations.classList.remove('d-none');
    });
}

function renderDraftVariations() {
    draftAText.textContent = state.draftA;
    draftBText.textContent = state.draftB;
    updateDraftCharCount('a');
    updateDraftCharCount('b');
}

function updateDraftCharCount(variation) {
    const text = variation === 'a' ? state.draftA : state.draftB;
    const el   = variation === 'a' ? draftACount : draftBCount;
    el.textContent = `${text.length} / ${CHAR_LIMIT} chars`;
}

async function reviseDraft(variation) {
    const feedbackEl = variation === 'a' ? feedbackA : feedbackB;
    const feedback   = feedbackEl.value.trim();
    const current    = variation === 'a' ? state.draftA : state.draftB;
    const textEl     = variation === 'a' ? draftAText : draftBText;

    if (!current) return;

    const formatNote = variation === 'b'
        ? `This is a plain English version — keep the simple, child-like language. Single post under ${CHAR_LIMIT} characters.`
        : `This is a single post under ${CHAR_LIMIT} characters.`;

    const reviseMessages = [
        {
            role: 'system',
            content: `You revise Mastodon post drafts. ${formatNote} Return only the revised text — no commentary, no JSON, no code blocks. Never use emoji, em dashes, colons, semi-colons, ellipses, exclamation marks, or markdown.${buildVoiceContext()}`,
        },
        {
            role: 'user',
            content: `Current draft:\n${current}\n\n${feedback ? `Feedback: ${feedback}` : 'Please polish this draft.'}`,
        },
    ];

    textEl.textContent = '';
    feedbackEl.value   = '';

    let accumulated = '';

    await streamSSE(reviseMessages, (chunk) => {
        accumulated += chunk;
        textEl.textContent = accumulated;
    }, () => {
        if (variation === 'a') {
            state.draftA = accumulated;
            updateDraftCharCount('a');
        } else {
            state.draftB = accumulated;
            updateDraftCharCount('b');
        }
    });
}

function selectVariation(variation) {
    const text = variation === 'a' ? state.draftA : state.draftB;
    showPhase(PHASES.POLISH);
    finalTextarea.value = text;
    updateCharCounts();
    finalTextarea.focus();
}

// ===== PHASE 5: POLISH =====
function updateCharCounts() {
    const text = finalTextarea.value;
    const over = text.length > CHAR_LIMIT;
    charCounts.innerHTML = `<span class="${over ? 'text-danger' : ''}">${text.length} / ${CHAR_LIMIT} chars</span>`;
}

async function saveDraft() {
    const content = finalTextarea.value.trim();
    if (!content) return;

    saveDraftBtn.disabled = true;
    saveDraftBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';
    draftSaveFeedback.textContent = '';

    try {
        const body = new URLSearchParams({ content });
        const res  = await fetch('/api/status/drafts', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', apikey: masterKey },
            body:    body.toString(),
        });

        if (res.status === 201) {
            draftSaveFeedback.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Saved as draft.</span>';
            saveDraftBtn.innerHTML = '<i class="bi bi-floppy"></i> Save as Draft';
        } else {
            const data = await res.json().catch(() => ({}));
            draftSaveFeedback.innerHTML = `<span class="text-danger">${escHtml(data.error || 'Failed to save draft.')}</span>`;
            saveDraftBtn.innerHTML = '<i class="bi bi-floppy"></i> Save as Draft';
        }
    } catch {
        draftSaveFeedback.innerHTML = '<span class="text-danger">Network error. Please try again.</span>';
        saveDraftBtn.innerHTML = '<i class="bi bi-floppy"></i> Save as Draft';
    } finally {
        saveDraftBtn.disabled = false;
    }
}

async function copyToClipboard() {
    try {
        await navigator.clipboard.writeText(finalTextarea.value);
        copyBtn.innerHTML = '<i class="bi bi-clipboard-check"></i> Copied!';
        setTimeout(() => { copyBtn.innerHTML = '<i class="bi bi-clipboard"></i> Copy'; }, 2000);
    } catch {
        copyBtn.innerHTML = '<i class="bi bi-clipboard-x"></i> Failed';
        setTimeout(() => { copyBtn.innerHTML = '<i class="bi bi-clipboard"></i> Copy'; }, 2000);
    }
}

// ===== STREAMING =====
async function streamSSE(messages, onChunk, onDone) {
    if (isStreaming) return;
    isStreaming = true;

    let streamError = null;

    try {
        const res = await fetch('/api/status/generator/stream', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', apikey: masterKey },
            body:    JSON.stringify({ messages, model: selectedModel, provider: selectedProvider }),
        });

        if (!res.ok || !res.body) {
            throw new Error(`HTTP ${res.status}`);
        }

        const reader  = res.body.getReader();
        const decoder = new TextDecoder();
        let buffer    = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop();

            for (const line of lines) {
                const trimmed = line.trim();
                if (!trimmed) continue;

                if (trimmed.startsWith('event: error')) continue;
                if (trimmed.startsWith('event: done'))  continue;

                if (trimmed.startsWith('data: ')) {
                    const raw = trimmed.slice(6);
                    let data  = null;
                    try { data = JSON.parse(raw); } catch { /* skip malformed */ }
                    if (!data) continue;
                    if (data.content) {
                        onChunk(data.content);
                    } else if (data.error) {
                        throw new Error(data.error);
                    } else if (data.done) {
                        break;
                    }
                }
            }
        }
    } catch (err) {
        console.error('Stream error:', err);
        streamError = err;
    } finally {
        isStreaming = false;
    }

    // onDone is called after isStreaming is reset so that nested streamSSE
    // calls (e.g. outline triggered from summary's onDone) are not blocked.
    await onDone(streamError);
}

// ===== MODEL SELECTOR =====
function setActiveProviderTab(provider, loadIfNeeded = true) {
    selectedProvider = provider;
    localStorage.setItem(PROVIDER_KEY, provider);

    document.querySelectorAll('#provider-tabs [data-provider]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.provider === provider);
    });

    if (loadIfNeeded) {
        if (modelCache[provider]) {
            renderModelList(modelCache[provider], provider);
        } else {
            loadModels(provider);
        }
    }
}

async function loadModels(provider = 'ollama') {
    modelList.innerHTML = '<p class="text-secondary text-center small py-4 mb-0">Loading models…</p>';

    try {
        const res    = await fetch('/api/ai/chat/models?provider=' + encodeURIComponent(provider), {
            headers: { apikey: masterKey },
        });
        const data   = await res.json();
        const models = data.models || [];
        modelCache[provider] = models;
        renderModelList(models, provider);

        const savedProvider = localStorage.getItem(PROVIDER_KEY) || 'ollama';
        if (provider === savedProvider && !selectedModel) {
            const saved = localStorage.getItem(MODEL_KEY);
            setSelectedModel(saved && models.includes(saved) ? saved : (models[0] || null), provider);
        }
    } catch {
        modelList.innerHTML = '<p class="text-danger text-center small py-4 mb-0">Failed to load models.</p>';
        modelBtnLabel.textContent = 'Unavailable';
    }
}

function renderModelList(models, provider) {
    if (models.length === 0) {
        const msg = provider === 'openrouter'
            ? 'No models enabled. <a href="/admin/ai/openrouter-models">Configure OpenRouter models</a>.'
            : 'No models found.';
        modelList.innerHTML = `<p class="text-secondary text-center small py-4 mb-0">${msg}</p>`;
        return;
    }

    const frag = document.createDocumentFragment();
    models.forEach(name => {
        const item = document.createElement('button');
        item.type      = 'button';
        item.className = 'model-list-item d-flex align-items-center justify-content-between w-100 px-3 py-3 border-bottom bg-transparent border-start-0 border-end-0 border-top-0 text-start';
        item.dataset.model = name;
        const isActive = name === selectedModel && provider === selectedProvider;
        item.innerHTML = `<span>${escHtml(name)}</span><i class="bi bi-check-circle-fill"${isActive ? '' : ' hidden'}></i>`;
        item.addEventListener('click', () => {
            setSelectedModel(name, provider);
            bootstrap.Modal.getInstance(document.getElementById('modelModal')).hide();
        });
        frag.appendChild(item);
    });

    modelList.innerHTML = '';
    modelList.appendChild(frag);
}

function setSelectedModel(name, provider = selectedProvider) {
    selectedModel    = name;
    selectedProvider = provider;
    localStorage.setItem(MODEL_KEY, name || '');
    localStorage.setItem(PROVIDER_KEY, provider);

    const providerLabel = provider === 'openrouter' ? 'OR' : 'Ollama';
    modelBtnLabel.textContent = name ? `${providerLabel} / ${name}` : 'Select model…';

    document.querySelectorAll('#model-list [data-model]').forEach(item => {
        const check = item.querySelector('.bi-check-circle-fill');
        if (check) check.hidden = item.dataset.model !== name;
    });
}

function escHtml(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
