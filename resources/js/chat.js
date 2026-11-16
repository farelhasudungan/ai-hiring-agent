const element = (tag, className, text) => {
    const node = document.createElement(tag);
    node.className = className;

    if (text !== undefined && text !== null) {
        node.textContent = text;
    }

    return node;
};

const growTextarea = (textarea) => {
    textarea.style.height = 'auto';
    textarea.style.height = `${Math.min(textarea.scrollHeight, 192)}px`;
};

/**
 * Enter sends, Shift+Enter inserts a new line, and the box grows with its text.
 */
document.querySelectorAll('[data-chat-input]').forEach((textarea) => {
    textarea.addEventListener('input', () => growTextarea(textarea));
    textarea.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            textarea.form?.requestSubmit();
        }
    });
    growTextarea(textarea);
});

document.querySelectorAll('[data-chat-example]').forEach((button) => {
    button.addEventListener('click', () => {
        const textarea = button.closest('form')?.querySelector('[data-chat-input]');

        if (textarea && !textarea.disabled) {
            textarea.value = button.dataset.chatExample;
            growTextarea(textarea);
            textarea.focus();
        }
    });
});

/**
 * Appends inline markdown (**bold**, *italic*, `code`, <br>). Built with DOM
 * nodes only, never innerHTML, so AI output can not inject markup.
 */
const appendInline = (parent, text) => {
    text.split(/<br\s*\/?>/i).forEach((segment, index) => {
        if (index > 0) {
            parent.append(document.createElement('br'));
        }

        segment.split(/(\*\*[^*]+\*\*|`[^`]+`|\*[^*\s][^*]*\*)/g).forEach((part) => {
            if (/^\*\*[^*]+\*\*$/.test(part)) {
                parent.append(element('strong', 'font-semibold', part.slice(2, -2)));
            } else if (/^`[^`]+`$/.test(part)) {
                parent.append(element('code', 'rounded bg-zinc-100 px-1.5 py-0.5 text-sm', part.slice(1, -1)));
            } else if (/^\*[^*\s][^*]*\*$/.test(part)) {
                parent.append(element('em', 'italic', part.slice(1, -1)));
            } else if (part !== '') {
                parent.append(document.createTextNode(part));
            }
        });
    });
};

const tableCells = (line) => line.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map((cell) => cell.trim());

const isTableSeparator = (line) => /^\s*\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)*\|?\s*$/.test(line ?? '') && (line ?? '').includes('-');

const isTableRow = (line) => /^\s*\|.*\|?\s*$/.test(line ?? '') && (line ?? '').includes('|');

const buildTable = (header, rows) => {
    const wrapper = element('div', 'overflow-x-auto rounded-xl border border-line');
    const table = element('table', 'w-full border-collapse text-left text-sm');
    const head = element('tr', 'bg-canvas');

    header.forEach((cell) => {
        const th = element('th', 'px-3 py-2 font-medium whitespace-nowrap');
        appendInline(th, cell);
        head.append(th);
    });

    table.append(element('thead', '')).firstChild.append(head);

    const body = element('tbody', '');

    rows.forEach((cells) => {
        const tr = element('tr', 'border-t border-line align-top');

        header.forEach((_, index) => {
            const td = element('td', 'px-3 py-2');
            appendInline(td, cells[index] ?? '');
            tr.append(td);
        });

        body.append(tr);
    });

    table.append(body);
    wrapper.append(table);

    return wrapper;
};

/**
 * Minimal markdown: headings, paragraphs, bullets, numbered items, tables,
 * horizontal rules and inline formatting.
 */
const renderMarkdown = (target, source) => {
    target.replaceChildren();

    const lines = source.split('\n');
    let list = null;

    for (let index = 0; index < lines.length; index++) {
        const line = lines[index];

        if (isTableRow(line) && isTableSeparator(lines[index + 1])) {
            const rows = [];
            let cursor = index + 2;

            while (cursor < lines.length && isTableRow(lines[cursor])) {
                rows.push(tableCells(lines[cursor]));
                cursor++;
            }

            list = null;
            target.append(buildTable(tableCells(line), rows));
            index = cursor - 1;

            continue;
        }

        const bullet = /^\s*[-*•]\s+(.*)$/.exec(line);
        const numbered = /^\s*\d+[.)]\s+(.*)$/.exec(line);
        const item = bullet ?? numbered;

        if (item) {
            const type = bullet ? 'ul' : 'ol';

            if (!list || list.tagName.toLowerCase() !== type) {
                list = element(type, `${bullet ? 'list-disc' : 'list-decimal'} flex flex-col gap-1 pl-5`);
                target.append(list);
            }

            const li = element('li', '');
            appendInline(li, item[1]);
            list.append(li);

            continue;
        }

        list = null;

        if (/^\s*([-*_])\1{2,}\s*$/.test(line)) {
            target.append(element('hr', 'border-line'));
        } else if (/^#{1,6}\s+/.test(line)) {
            const heading = element('p', 'pt-1 text-lg font-semibold');
            appendInline(heading, line.replace(/^#{1,6}\s+/, ''));
            target.append(heading);
        } else if (line.trim() !== '') {
            const paragraph = element('p', '');
            appendInline(paragraph, line);
            target.append(paragraph);
        }
    }
};

/**
 * Accessible tabs: click or arrow keys switch between the Chat and History panels.
 */
document.querySelectorAll('[data-chat-tabs]').forEach((tabs) => {
    const buttons = [...tabs.querySelectorAll('[data-chat-tab]')];

    const select = (button, focus = false) => {
        buttons.forEach((item) => {
            const active = item === button;

            item.setAttribute('aria-selected', active ? 'true' : 'false');
            item.tabIndex = active ? 0 : -1;
            document.getElementById(item.getAttribute('aria-controls')).hidden = !active;
        });

        if (focus) {
            button.focus();
        }
    };

    buttons.forEach((button, index) => {
        button.addEventListener('click', () => select(button));
        button.addEventListener('keydown', (event) => {
            const step = { ArrowRight: 1, ArrowLeft: -1 }[event.key];

            if (step) {
                event.preventDefault();
                select(buttons[(index + step + buttons.length) % buttons.length], true);
            }
        });
    });
});

/**
 * One shared confirmation dialog for every delete button.
 */
const deleteDialog = document.getElementById('chat-delete-dialog');

if (deleteDialog) {
    const deleteForm = deleteDialog.querySelector('[data-chat-delete-form]');
    const deleteName = deleteDialog.querySelector('[data-chat-delete-name]');

    document.querySelectorAll('[data-chat-delete]').forEach((button) => {
        button.addEventListener('click', () => {
            deleteForm.action = button.dataset.chatDelete;
            deleteName.textContent = `"${button.dataset.chatDeleteTitle}"`;
            deleteDialog.showModal();
        });
    });

    deleteDialog.querySelector('[data-chat-delete-cancel]').addEventListener('click', () => deleteDialog.close());
    deleteDialog.addEventListener('click', (event) => {
        if (event.target === deleteDialog) {
            deleteDialog.close();
        }
    });
}

const root = document.querySelector('[data-chat]');

if (root) {
    const scroller = root.querySelector('[data-chat-scroll]');
    const list = root.querySelector('[data-chat-messages]');
    const form = root.querySelector('[data-chat-form]');
    const input = form.querySelector('[data-chat-input]');
    const submit = form.querySelector('[data-chat-submit]');
    const providerReady = !input.disabled;

    const scrollToEnd = () => scroller.scrollTo({ top: scroller.scrollHeight });

    const addMessage = (role) => {
        if (role === 'user') {
            const row = element('div', 'flex justify-end');
            const bubble = element('div', 'max-w-[85%] rounded-3xl bg-zinc-100 px-5 py-3 text-base whitespace-pre-wrap wrap-anywhere');

            row.append(bubble);
            list.append(row);

            return { bubble, cards: null };
        }

        const row = element('div', 'flex gap-4');
        const avatar = element('span', 'flex size-8 shrink-0 items-center justify-center rounded-full bg-black text-xs font-bold text-white', 'R');
        avatar.setAttribute('aria-hidden', 'true');

        const body = element('div', 'flex min-w-0 flex-1 flex-col gap-4');
        const bubble = element('div', 'flex flex-col gap-3 text-base leading-relaxed wrap-anywhere');
        const cards = element('div', 'flex flex-col gap-2');

        body.append(bubble, cards);
        row.append(avatar, body);
        list.append(row);

        return { bubble, cards };
    };

    const showThinking = (bubble, label = 'Thinking…') => {
        const dots = element('span', 'flex items-center gap-2 py-2 text-sm text-muted');
        dots.setAttribute('role', 'status');

        [0, 150, 300].forEach((delay) => {
            const dot = element('span', 'size-2 animate-bounce rounded-full bg-zinc-400');
            dot.style.animationDelay = `${delay}ms`;
            dots.append(dot);
        });

        dots.append(element('span', '', label));
        bubble.replaceChildren(dots);
    };

    const addCards = (container, candidates) => {
        container.replaceChildren();

        candidates.forEach((candidate) => {
            const card = element('a', 'flex flex-col gap-2 rounded-2xl border border-line bg-paper p-4 transition hover:border-zinc-400');
            card.href = candidate.url;

            const head = element('div', 'flex flex-wrap items-center justify-between gap-2');
            head.append(element('span', 'font-medium wrap-anywhere', candidate.name));
            head.append(element('span', 'rounded-full bg-canvas px-3 py-1 text-xs', candidate.score === null ? 'Not screened' : `Score ${candidate.score}`));
            card.append(head);

            const meta = [candidate.job, candidate.recommendation?.replace('_', ' ')].filter(Boolean).join(' · ');

            if (meta) {
                card.append(element('p', 'text-xs text-muted capitalize', meta));
            }

            if (candidate.matched_skills.length > 0) {
                const skills = element('div', 'flex flex-wrap gap-1.5');
                candidate.matched_skills.slice(0, 6).forEach((skill) => skills.append(element('span', 'rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-muted', skill)));
                card.append(skills);
            }

            container.append(card);
        });
    };

    const parseEvents = (buffer) => {
        const parts = buffer.split('\n\n');
        const rest = parts.pop();
        const events = parts.map((part) => {
            const event = /^event: (.+)$/m.exec(part)?.[1];
            const data = /^data: (.+)$/m.exec(part)?.[1];

            return event && data ? { event, data: JSON.parse(data) } : null;
        });

        return { events: events.filter(Boolean), rest };
    };

    const setBusy = (busy) => {
        submit.disabled = busy || !providerReady;
        input.disabled = busy || !providerReady;
    };

    const send = async (message) => {
        addMessage('user').bubble.textContent = message;
        setBusy(true);

        const answer = addMessage('assistant');
        showThinking(answer.bubble);
        scrollToEnd();

        try {
            const response = await fetch(root.dataset.chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'text/event-stream',
                    'X-CSRF-TOKEN': root.dataset.chatToken,
                },
                body: JSON.stringify({ message }),
            });

            if (!response.ok) {
                const body = await response.json().catch(() => ({}));
                const firstError = body.errors ? Object.values(body.errors)[0][0] : null;

                throw new Error(firstError ?? body.message ?? 'The message could not be sent.');
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            let text = '';

            while (true) {
                const { done, value } = await reader.read();

                if (done) {
                    break;
                }

                buffer += decoder.decode(value, { stream: true });

                const parsed = parseEvents(buffer);
                buffer = parsed.rest;

                parsed.events.forEach(({ event: name, data }) => {
                    if (name === 'tool') {
                        showThinking(answer.bubble, data.label);
                    } else if (name === 'candidates') {
                        addCards(answer.cards, data.candidates);
                    } else if (name === 'chunk') {
                        text += data.text;
                        renderMarkdown(answer.bubble, text);
                    } else if (name === 'error') {
                        throw new Error(data.message);
                    }
                });

                scrollToEnd();
            }
        } catch (error) {
            answer.bubble.closest('.flex.gap-4').remove();
            window.toast?.error(error.message);
        } finally {
            setBusy(false);
            input.focus();
        }
    };

    JSON.parse(document.getElementById('chat-history')?.textContent ?? '[]').forEach((message) => {
        const { bubble, cards } = addMessage(message.role);

        if (message.role === 'user') {
            bubble.textContent = message.content;
        } else {
            renderMarkdown(bubble, message.content);
            addCards(cards, message.candidates ?? []);
        }
    });

    scrollToEnd();

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const message = input.value.trim();

        if (message === '' || input.disabled) {
            return;
        }

        input.value = '';
        growTextarea(input);
        send(message);
    });

    if (root.dataset.chatFirstMessage && providerReady) {
        send(root.dataset.chatFirstMessage);
    } else {
        input.focus();
    }
}
