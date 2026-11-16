@props(['maxLength', 'disabled' => false, 'placeholder' => 'Describe the candidate you are looking for…'])

<div class="flex flex-col gap-2">
    <div class="flex items-end gap-2 rounded-3xl border border-line bg-paper p-2 shadow-sm focus-within:border-zinc-400">
        <label for="chat-message" class="sr-only">Your message</label>
        <textarea id="chat-message" name="message" rows="1" maxlength="{{ $maxLength }}" required data-chat-input @disabled($disabled) placeholder="{{ $placeholder }}"
            class="max-h-48 min-h-11 flex-1 resize-none bg-transparent px-4 py-2.5 text-base outline-none placeholder:text-zinc-400 disabled:cursor-not-allowed">{{ $slot }}</textarea>
        <button type="submit" data-chat-submit @disabled($disabled) aria-label="Send message"
            class="flex size-11 shrink-0 items-center justify-center rounded-full bg-ink text-paper transition hover:bg-zinc-700 disabled:cursor-not-allowed disabled:bg-zinc-300">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>
    <p class="text-center text-xs text-zinc-400">Enter to send, Shift+Enter for a new line. AI answers are based on your stored resumes; always review candidates yourself.</p>
</div>
