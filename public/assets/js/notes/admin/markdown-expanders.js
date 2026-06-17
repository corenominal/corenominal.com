(function () {
    'use strict';

    const LOREM_TEXT = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

    const EXPANDERS = [
        { trigger: 'lorem', replacement: LOREM_TEXT },
    ];

    const markdownInput = document.getElementById('note-body');
    if (!markdownInput) return;

    // ── Helpers ────────────────────────────────────────────────────────────────

    function getLineStart(value, pos) {
        const idx = value.lastIndexOf('\n', pos - 1);
        return idx === -1 ? 0 : idx + 1;
    }

    function commit(el, newValue, cursorPos) {
        el.value = newValue;
        el.setSelectionRange(cursorPos, cursorPos);
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    // ── Word expanders (Space / Tab) ───────────────────────────────────────────

    function handleExpanders(e, el) {
        if (e.key !== ' ' && e.key !== 'Tab') return false;

        const pos       = el.selectionStart;
        const value     = el.value;
        const wordStart = value.slice(0, pos).search(/\S+$/);
        if (wordStart === -1) return false;

        const word     = value.slice(wordStart, pos);
        const expander = EXPANDERS.find(exp => word.toLowerCase() === exp.trigger);
        if (!expander) return false;

        e.preventDefault();
        const result = value.slice(0, wordStart) + expander.replacement;
        commit(el, result + value.slice(pos), result.length);
        return true;
    }

    // ── Tab: 4 spaces, or indent / Shift+Tab outdent selected lines ───────────

    function handleTab(e, el) {
        if (e.key !== 'Tab') return false;
        e.preventDefault();

        const { selectionStart: ss, selectionEnd: se, value } = el;
        const INDENT = '    ';

        if (ss === se) {
            commit(el, value.slice(0, ss) + INDENT + value.slice(ss), ss + INDENT.length);
            return true;
        }

        const blockStart = getLineStart(value, ss);
        const blockEnd   = value.indexOf('\n', se - 1);
        const block      = value.slice(blockStart, blockEnd === -1 ? value.length : blockEnd);
        const modified   = e.shiftKey
            ? block.replace(/^ {1,4}/gm, '')
            : block.replace(/^/gm, INDENT);
        const tail = blockEnd === -1 ? '' : value.slice(blockEnd);

        el.value = value.slice(0, blockStart) + modified + tail;
        el.setSelectionRange(blockStart, blockStart + modified.length);
        el.dispatchEvent(new Event('input', { bubbles: true }));
        return true;
    }

    // ── Enter: smart list / blockquote continuation ───────────────────────────

    function handleEnter(e, el) {
        if (e.key !== 'Enter') return false;

        const { selectionStart: ss, selectionEnd: se, value } = el;
        const lineStart = getLineStart(value, ss);
        const line      = value.slice(lineStart, ss);

        // Empty list item or blockquote → exit the structure
        if (/^(\s*)([-*+]|\d+\.)\s*$/.test(line) || /^>\s*$/.test(line)) {
            e.preventDefault();
            commit(el, value.slice(0, lineStart) + value.slice(ss), lineStart);
            return true;
        }

        // Ordered list continuation: "  1. text"
        const orderedMatch = line.match(/^(\s*)(\d+)\.\s\S/);
        if (orderedMatch) {
            e.preventDefault();
            const next = '\n' + orderedMatch[1] + (parseInt(orderedMatch[2], 10) + 1) + '. ';
            commit(el, value.slice(0, ss) + next + value.slice(se), ss + next.length);
            return true;
        }

        // Unordered list continuation: "- text", "* text", "+ text"
        const unorderedMatch = line.match(/^(\s*)([-*+])\s\S/);
        if (unorderedMatch) {
            e.preventDefault();
            const next = '\n' + unorderedMatch[1] + unorderedMatch[2] + ' ';
            commit(el, value.slice(0, ss) + next + value.slice(se), ss + next.length);
            return true;
        }

        // Blockquote continuation: "> text"
        const bqMatch = line.match(/^(>\s?)\S/);
        if (bqMatch) {
            e.preventDefault();
            const next = '\n' + bqMatch[1];
            commit(el, value.slice(0, ss) + next + value.slice(se), ss + next.length);
            return true;
        }

        return false;
    }

    // ── Ctrl/Cmd+B / Ctrl/Cmd+I: toggle bold & italic ────────────────────────

    function handleInlineFormat(e, el) {
        if (!(e.ctrlKey || e.metaKey) || e.altKey) return false;

        let marker;
        if      (e.key === 'b' || e.key === 'B') marker = '**';
        else if (e.key === 'i' || e.key === 'I') marker = '*';
        else return false;

        e.preventDefault();
        const { selectionStart: ss, selectionEnd: se, value } = el;
        const selected = value.slice(ss, se);
        const ml       = marker.length;

        if (selected.startsWith(marker) && selected.endsWith(marker) && selected.length >= ml * 2) {
            // Unwrap
            const inner = selected.slice(ml, selected.length - ml);
            el.value = value.slice(0, ss) + inner + value.slice(se);
            el.setSelectionRange(ss, ss + inner.length);
        } else {
            // Wrap
            const wrapped = marker + selected + marker;
            el.value = value.slice(0, ss) + wrapped + value.slice(se);
            el.setSelectionRange(
                selected.length === 0 ? ss + ml : ss,
                selected.length === 0 ? ss + ml : ss + wrapped.length
            );
        }
        el.dispatchEvent(new Event('input', { bubbles: true }));
        return true;
    }

    // ── Backtick: ``` → fenced code block; selection → inline code ───────────

    function handleBacktick(e, el) {
        if (e.key !== '`') return false;

        const { selectionStart: ss, selectionEnd: se, value } = el;
        const selected = value.slice(ss, se);
        const before   = value.slice(0, ss);

        // Third backtick typed → expand to fenced code block
        if (ss === se && before.endsWith('``')) {
            e.preventDefault();
            const fence = '```\n\n```';
            commit(el, before.slice(0, -2) + fence + value.slice(se), before.length - 2 + 4);
            return true;
        }

        // Wrap selection in inline code backticks
        if (selected.length > 0) {
            e.preventDefault();
            el.value = value.slice(0, ss) + '`' + selected + '`' + value.slice(se);
            el.setSelectionRange(ss + 1, ss + 1 + selected.length);
            el.dispatchEvent(new Event('input', { bubbles: true }));
            return true;
        }

        return false;
    }

    // ── Main listener ─────────────────────────────────────────────────────────

    markdownInput.addEventListener('keydown', function (e) {
        if (handleExpanders(e, this))    return;
        if (handleTab(e, this))          return;
        if (handleEnter(e, this))        return;
        if (handleInlineFormat(e, this)) return;
        if (handleBacktick(e, this))     return;
    });

}());
