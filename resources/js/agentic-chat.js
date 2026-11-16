/**
 * Progressive SSE transport for the Agentic Chat workspace.
 *
 * The Livewire component owns persisted state; this module only streams the
 * answer into the DOM and then asks Livewire to re-render from the database.
 */

const state = {
    controller: null,
    streaming: false,
    lastPrompt: '',
};

const selectors = {
    root: '[data-agentic-chat]',
    panel: '[data-chat-panel]',
    messages: '[data-chat-messages]',
    form: '[data-chat-form]',
    input: '[data-chat-input]',
    send: '[data-chat-send]',
    sendLabel: '[data-chat-send-label]',
    status: '[data-chat-status]',
    error: '[data-chat-error]',
    errorMessage: '[data-chat-error-message]',
    retry: '[data-chat-retry]',
    session: '[data-chat-session]',
    newChat: '[data-chat-new]',
    optimistic: '[data-chat-optimistic]',
    meta: {
        model: '[data-meta-model]',
        provider: '[data-meta-provider]',
        promptTokens: '[data-meta-prompt-tokens]',
        completionTokens: '[data-meta-completion-tokens]',
        totalTokens: '[data-meta-total-tokens]',
    },
};

const root = () => document.querySelector(selectors.root);
const messages = () => root()?.querySelector(selectors.messages);

function setText(selector, value) {
    const element = root()?.querySelector(selector);
    if (element) {
        element.textContent = value === null || value === undefined || value === '' ? '—' : String(value);
    }
}

function setStatus(message) {
    const element = root()?.querySelector(selectors.status);
    if (element) {
        element.textContent = message;
    }
}

function setBusy(busy) {
    const panel = root()?.querySelector(selectors.panel);
    const button = root()?.querySelector(selectors.send);
    const label = root()?.querySelector(selectors.sendLabel);

    panel?.setAttribute('aria-busy', String(busy));

    if (button) {
        button.disabled = busy;
    }

    if (label) {
        label.textContent = busy ? 'Mengirim…' : 'Kirim';
    }
}

function showError(message) {
    const box = root()?.querySelector(selectors.error);
    const text = root()?.querySelector(selectors.errorMessage);

    if (!box || !text) {
        return;
    }

    text.textContent = message;
    box.classList.remove('hidden');
    box.classList.add('flex');
}

function clearError() {
    const box = root()?.querySelector(selectors.error);

    if (box) {
        box.classList.add('hidden');
        box.classList.remove('flex');
    }
}

function scrollToLatest() {
    const container = messages();

    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

function appendUserBubble(prompt) {
    const template = root()?.querySelector('[data-chat-user-template]');
    const node = template?.content?.firstElementChild?.cloneNode(true);

    if (!node) {
        return;
    }

    node.querySelector('[data-chat-user-text]').textContent = prompt;
    messages()?.append(node);
    scrollToLatest();
}

function appendAssistantBubble() {
    const template = root()?.querySelector('[data-chat-assistant-template]');
    const node = template?.content?.firstElementChild?.cloneNode(true);

    if (!node) {
        return null;
    }

    messages()?.append(node);
    scrollToLatest();

    return {
        node,
        text: node.querySelector('[data-chat-stream-text]'),
        typing: node.querySelector('[data-chat-typing]'),
    };
}

function removeOptimisticBubbles() {
    root()?.querySelectorAll(selectors.optimistic).forEach((node) => node.remove());
}

function updateMeta(metadata) {
    const tokens = metadata?.tokens ?? {};

    setText(selectors.meta.model, metadata?.model_used);
    setText(selectors.meta.provider, metadata?.provider);
    setText(selectors.meta.promptTokens, tokens.prompt);
    setText(selectors.meta.completionTokens, tokens.completion);
    setText(selectors.meta.totalTokens, tokens.total);
}

function parseEventBlock(block) {
    let event = 'message';
    const data = [];

    block.split('\n').forEach((line) => {
        if (line.startsWith('event:')) {
            event = line.slice(6).trim();
        } else if (line.startsWith('data:')) {
            data.push(line.slice(5).trim());
        }
    });

    return { event, data: data.join('\n') };
}

async function syncWithServer(sessionId) {
    const element = root();
    const componentId = element?.getAttribute('wire:id');
    const component = componentId ? window.Livewire?.find(componentId) : null;

    if (!sessionId || !component?.conversationCompleted) {
        window.location.reload();

        return;
    }

    try {
        await component.conversationCompleted(sessionId);
    } catch {
        window.location.reload();
    }
}

async function finish(metadata, live) {
    live?.typing?.remove();

    if (metadata) {
        updateMeta(metadata);
    }

    setStatus('Jawaban selesai diterima.');
    await syncWithServer(metadata?.session_id);
    removeOptimisticBubbles();
    scrollToLatest();
    root()?.querySelector(selectors.input)?.focus();
}

async function readStream(body, live) {
    const reader = body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';
    let metadata = null;

    const handleBlock = (block) => {
        const { event, data } = parseEventBlock(block);

        if (data === '') {
            return false;
        }

        if (data === '[DONE]') {
            return true;
        }

        let payload;

        try {
            payload = JSON.parse(data);
        } catch {
            return false;
        }

        if (event === 'error' || payload.status === 'error') {
            throw new Error(payload.message || 'Streaming gagal diproses.');
        }

        if (typeof payload.chunk === 'string') {
            live?.typing?.remove();
            live?.text?.append(document.createTextNode(payload.chunk));
            scrollToLatest();

            return false;
        }

        if (payload.status === '[DONE]') {
            metadata = payload;

            return true;
        }

        return false;
    };

    while (true) {
        const { value, done } = await reader.read();

        if (done) {
            break;
        }

        buffer += decoder.decode(value, { stream: true });

        let boundary = buffer.indexOf('\n\n');

        while (boundary !== -1) {
            const block = buffer.slice(0, boundary);
            buffer = buffer.slice(boundary + 2);

            if (handleBlock(block)) {
                await finish(metadata, live);

                return;
            }

            boundary = buffer.indexOf('\n\n');
        }
    }

    if (buffer.trim() !== '' && handleBlock(buffer)) {
        await finish(metadata, live);

        return;
    }

    throw new Error('Streaming berakhir tanpa penanda selesai. Silakan ulangi permintaan.');
}

async function sendPrompt(rawPrompt) {
    const element = root();

    if (!element || state.streaming) {
        return;
    }

    const prompt = (rawPrompt ?? '').trim();

    if (prompt === '') {
        showError('Prompt tidak boleh kosong.');

        return;
    }

    if (prompt.length > 1000) {
        showError('Prompt tidak boleh lebih dari 1000 karakter.');

        return;
    }

    state.streaming = true;
    state.lastPrompt = prompt;
    clearError();
    setBusy(true);

    const input = element.querySelector(selectors.input);

    if (input) {
        input.value = '';
        autoGrow(input);
    }
    setStatus('Menunggu jawaban dari penyedia LLM…');

    appendUserBubble(prompt);
    const live = appendAssistantBubble();

    const controller = new AbortController();
    state.controller = controller;

    const sessionId = element.dataset.sessionId;

    try {
        const response = await fetch(element.dataset.streamUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'text/event-stream',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({
                prompt,
                session_id: sessionId ? Number(sessionId) : null,
            }),
            signal: controller.signal,
        });

        if (!response.ok || !response.body) {
            const payload = await response.json().catch(() => ({}));

            throw new Error(payload.message || 'Permintaan tidak dapat diproses.');
        }

        await readStream(response.body, live);
    } catch (error) {
        if (error.name === 'AbortError') {
            live?.node?.remove();
            removeOptimisticBubbles();

            return;
        }

        controller.abort();
        live?.node?.remove();
        setStatus('Permintaan gagal.');
        showError(error.message || 'Koneksi ke layanan LLM terputus.');
    } finally {
        state.streaming = false;
        state.controller = null;
        setBusy(false);
    }
}

function autoGrow(input) {
    input.style.height = 'auto';
    input.style.height = `${Math.min(input.scrollHeight, 200)}px`;
}

export function initAgenticChat() {
    document.addEventListener('submit', (event) => {
        const form = event.target.closest?.(selectors.form);

        if (!form) {
            return;
        }

        event.preventDefault();
        sendPrompt(form.querySelector(selectors.input)?.value);
    });

    document.addEventListener('keydown', (event) => {
        const input = event.target.closest?.(selectors.input);

        if (!input || event.key !== 'Enter' || event.shiftKey || event.isComposing) {
            return;
        }

        event.preventDefault();
        input.form?.requestSubmit();
    });

    document.addEventListener('input', (event) => {
        const input = event.target.closest?.(selectors.input);

        if (input) {
            autoGrow(input);
        }
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest?.(selectors.retry)) {
            event.preventDefault();
            clearError();
            sendPrompt(state.lastPrompt);

            return;
        }

        if (event.target.closest?.(selectors.newChat) || event.target.closest?.(selectors.session)) {
            state.controller?.abort();
        }
    });
}
