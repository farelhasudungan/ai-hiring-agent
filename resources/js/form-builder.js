const builder = document.querySelector('[data-form-builder]');

if (builder) {
    const sectionsContainer = builder.querySelector('[data-sections]');
    const sectionTemplate = document.querySelector('#section-template');
    const fieldTemplate = document.querySelector('#field-template');
    const choiceTypes = JSON.parse(builder.dataset.choiceTypes || '[]');
    let sectionCounter = 0;

    sectionsContainer.querySelectorAll('[data-section]').forEach((section) => {
        const index = Number(section.dataset.sectionIndex);

        if (Number.isInteger(index) && index >= sectionCounter) {
            sectionCounter = index + 1;
        }
    });

    const firstInvalidField = builder.querySelector('[aria-invalid="true"]');

    if (firstInvalidField) {
        window.requestAnimationFrame(() => {
            firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalidField.focus({ preventScroll: true });
        });
    }

    const initField = (fieldElement) => {
        const typeSelect = fieldElement.querySelector('[data-field-type]');
        const optionsEditor = fieldElement.querySelector('[data-options-editor]');
        const optionsContainer = fieldElement.querySelector('[data-options]');
        const labelInput = fieldElement.querySelector('[data-field-label]');

        const toggleOptions = () => {
            if (optionsEditor && typeSelect) {
                optionsEditor.classList.toggle('hidden', ! choiceTypes.includes(typeSelect.value));
            }
        };

        if (typeSelect) {
            typeSelect.addEventListener('change', toggleOptions);
            toggleOptions();
        }

        fieldElement.querySelector('[data-remove-field]')?.addEventListener('click', () => {
            fieldElement.remove();
        });

        fieldElement.querySelector('[data-move-field-up]')?.addEventListener('click', () => {
            const previous = fieldElement.previousElementSibling;

            if (previous) {
                fieldElement.parentElement.insertBefore(fieldElement, previous);
            }
        });

        fieldElement.querySelector('[data-move-field-down]')?.addEventListener('click', () => {
            const next = fieldElement.nextElementSibling;

            if (next) {
                fieldElement.parentElement.insertBefore(next, fieldElement);
            }
        });

        optionsContainer?.querySelectorAll('[data-option]').forEach((option) => {
            option.querySelector('[data-remove-option]')?.addEventListener('click', () => {
                option.remove();
            });
        });

        fieldElement.querySelector('[data-add-option]')?.addEventListener('click', () => {
            if (! optionsContainer || ! labelInput) {
                return;
            }

            const prefix = labelInput.name.replace(/\[label\]$/, '');
            const index = Number(optionsContainer.dataset.nextIndex || '0');
            optionsContainer.dataset.nextIndex = String(index + 1);

            const row = document.createElement('div');
            row.dataset.option = '';
            row.className = 'flex flex-wrap items-center gap-2';

            const label = document.createElement('input');
            label.name = `${prefix}[options][${index}][label]`;
            label.placeholder = 'Label';
            label.className = 'min-h-11 w-full flex-1 rounded-[16px] border border-line bg-paper px-4 py-2 text-sm placeholder:text-muted/70';

            const value = document.createElement('input');
            value.name = `${prefix}[options][${index}][value]`;
            value.placeholder = 'Stored value (optional)';
            value.className = 'min-h-11 w-full flex-1 rounded-[16px] border border-line bg-paper px-4 py-2 text-sm placeholder:text-muted/70';

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.dataset.removeOption = '';
            remove.className = 'flex size-8 shrink-0 items-center justify-center rounded-full border border-line text-xs transition hover:bg-canvas';
            remove.setAttribute('aria-label', 'Remove option');
            remove.textContent = '×';
            remove.addEventListener('click', () => row.remove());

            row.append(label, value, remove);
            optionsContainer.appendChild(row);
        });
    };

    const initSection = (sectionElement) => {
        const fieldsContainer = sectionElement.querySelector('[data-fields]');

        sectionElement.querySelector('[data-remove-section]')?.addEventListener('click', () => {
            sectionElement.remove();
        });

        sectionElement.querySelector('[data-move-section-up]')?.addEventListener('click', () => {
            const previous = sectionElement.previousElementSibling;

            if (previous) {
                sectionElement.parentElement.insertBefore(sectionElement, previous);
            }
        });

        sectionElement.querySelector('[data-move-section-down]')?.addEventListener('click', () => {
            const next = sectionElement.nextElementSibling;

            if (next) {
                sectionElement.parentElement.insertBefore(next, sectionElement);
            }
        });

        sectionElement.querySelector('[data-add-field]')?.addEventListener('click', () => {
            if (! fieldsContainer || ! fieldTemplate) {
                return;
            }

            const index = Number(fieldsContainer.dataset.nextIndex || '0');
            fieldsContainer.dataset.nextIndex = String(index + 1);

            const wrapper = document.createElement('div');
            wrapper.innerHTML = fieldTemplate.innerHTML
                .replaceAll('__SECTION__', sectionElement.dataset.sectionIndex)
                .replaceAll('__FIELD__', String(index))
                .trim();

            const fieldElement = wrapper.firstElementChild;

            fieldsContainer.appendChild(fieldElement);
            initField(fieldElement);
        });

        fieldsContainer?.querySelectorAll('[data-field]').forEach(initField);
    };

    sectionsContainer?.querySelectorAll('[data-section]').forEach(initSection);

    builder.querySelector('[data-add-section]')?.addEventListener('click', () => {
        if (! sectionTemplate) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = sectionTemplate.innerHTML
            .replaceAll('__SECTION__', String(sectionCounter++))
            .trim();

        const sectionElement = wrapper.firstElementChild;

        sectionsContainer.appendChild(sectionElement);
        initSection(sectionElement);
    });
}
