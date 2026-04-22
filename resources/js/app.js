import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    setupMobileHeader();
    setupSearchPopover();
    setupConfirmForms();
    setupTableOfContentsTracking();
    setupEditionCodePlaceholders();
    setupLawSlugPreview();
    setupTranslationEditor();
    setupNodeTypeSections();
    setupMediaTypeSections();
    setupImageAssetPicker();
    setupVideoGroupEditor();
    setupDocumentPageEditor();
});

window.addEventListener('load', () => {
    setupDeferredMediaLoading();
});

function setupMobileHeader() {
    const mobileHeader = document.querySelector('[data-mobile-header]');
    const toggleButton = document.querySelector('[data-mobile-menu-toggle]');
    const tray = document.querySelector('[data-mobile-tray]');
    const scrollTopButtons = Array.from(document.querySelectorAll('[data-scroll-top]'));
    const root = document.documentElement;

    if (!mobileHeader || !toggleButton) {
        return;
    }

    let lastScrollY = window.scrollY;

    const setTrayOpen = (open) => {
        mobileHeader.classList.toggle('is-open', open);
        document.body.classList.toggle('menu-open', open);
        toggleButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggleButton.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        toggleButton.innerHTML = open ? '&times;' : '&#9776;';
    };

    toggleButton.addEventListener('click', (event) => {
        event.stopPropagation();
        setTrayOpen(!mobileHeader.classList.contains('is-open'));
    });

    tray?.addEventListener('click', (event) => {
        const target = event.target;

        if (target instanceof HTMLElement && target.closest('a')) {
            setTrayOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobileHeader.classList.contains('is-open')) {
            setTrayOpen(false);
        }
    });

    scrollTopButtons.forEach((button) => {
        button.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    window.addEventListener(
        'resize',
        () => {
            if (window.innerWidth > 700) {
                setTrayOpen(false);
                mobileHeader.classList.remove('is-hidden');
                root.classList.remove('mobile-header-hidden');
            }
        },
        { passive: true },
    );

    window.addEventListener(
        'scroll',
        () => {
            const currentScrollY = window.scrollY;
            const isMobile = window.innerWidth <= 700;

            if (!isMobile) {
                mobileHeader.classList.remove('is-hidden');
                root.classList.remove('mobile-header-hidden');
                return;
            }

            if (mobileHeader.classList.contains('is-open')) {
                mobileHeader.classList.remove('is-hidden');
                root.classList.remove('mobile-header-hidden');
                lastScrollY = currentScrollY;
                return;
            }

            if (currentScrollY <= 12 || currentScrollY + 6 < lastScrollY) {
                mobileHeader.classList.remove('is-hidden');
                root.classList.remove('mobile-header-hidden');
            } else if (currentScrollY > lastScrollY + 6) {
                mobileHeader.classList.add('is-hidden');
                root.classList.add('mobile-header-hidden');
            }

            lastScrollY = currentScrollY;
        },
        { passive: true },
    );
}

function setupTableOfContentsTracking() {
    const tocLinks = Array.from(document.querySelectorAll('.toc-link[data-anchor]'));

    if (!tocLinks.length) {
        return;
    }

    const headings = tocLinks
        .map((link) => document.getElementById(link.dataset.anchor))
        .filter(Boolean);

    if (!headings.length) {
        return;
    }

    const setActive = (id) => {
        tocLinks.forEach((link) => {
            link.classList.toggle('is-active', link.dataset.anchor === id);
        });
    };

    const observer = new IntersectionObserver(
        (entries) => {
            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

            if (visible.length > 0) {
                setActive(visible[0].target.id);
            }
        },
        {
            rootMargin: '-18% 0px -65% 0px',
            threshold: [0, 1],
        },
    );

    headings.forEach((heading) => observer.observe(heading));

    if (headings[0]) {
        setActive(headings[0].id);
    }
}

function setupSearchPopover() {
    const popovers = Array.from(document.querySelectorAll('.search-popover'));

    if (!popovers.length) {
        return;
    }

    document.addEventListener('click', (event) => {
        popovers.forEach((popover) => {
            if (!(popover instanceof HTMLElement)) {
                return;
            }

            if (!popover.contains(event.target)) {
                popover.removeAttribute('open');
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            popovers.forEach((popover) => popover.removeAttribute('open'));
        }
    });
}

function setupConfirmForms() {
    const forms = Array.from(document.querySelectorAll('form[data-confirm-message]'));

    forms.forEach((form) => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        form.addEventListener('submit', (event) => {
            const message = form.dataset.confirmMessage;

            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
}

function setupDeferredMediaLoading() {
    const deferredMedia = Array.from(document.querySelectorAll('[data-deferred-src]'));

    deferredMedia.forEach((element) => {
        if (!(element instanceof HTMLIFrameElement || element instanceof HTMLImageElement)) {
            return;
        }

        const source = element.dataset.src;

        if (!source || element.getAttribute('src')) {
            return;
        }

        element.setAttribute('src', source);
    });
}

function setupEditionCodePlaceholders() {
    const codeInputs = Array.from(document.querySelectorAll('[data-edition-code-input]'));

    codeInputs.forEach((codeInput) => {
        if (!(codeInput instanceof HTMLInputElement)) {
            return;
        }

        const form = codeInput.closest('form');

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const nameInput = form.querySelector('[data-edition-name-input]');

        if (!(nameInput instanceof HTMLInputElement)) {
            return;
        }

        const slugify = (value) =>
            value
                .toString()
                .normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .replace(/-{2,}/g, '-');

        const updatePlaceholder = () => {
            const autoCode = slugify(nameInput.value) || 'edition';
            codeInput.placeholder = `Auto: ${autoCode}`;
        };

        nameInput.addEventListener('input', updatePlaceholder);

        updatePlaceholder();
    });
}

function setupLawSlugPreview() {
    const lawForms = Array.from(document.querySelectorAll('form'));

    lawForms.forEach((form) => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const lawNumberInput = form.querySelector('[data-law-number-input]');
        const slugInput = form.querySelector('[data-law-slug-input]');
        const titleIdInput = form.querySelector('[data-law-title-id-input]');
        const preview = form.querySelector('[data-law-slug-preview]');

        if (!(slugInput instanceof HTMLInputElement) || !(preview instanceof HTMLElement)) {
            return;
        }

        const slugify = (value) =>
            value
                .toString()
                .normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .replace(/-{2,}/g, '-');

        const updatePreview = () => {
            const fallbackTitle = titleIdInput instanceof HTMLInputElement ? titleIdInput.value : '';
            preview.textContent = slugify(slugInput.value || fallbackTitle) || '-';
        };

        slugInput.addEventListener('input', updatePreview);
        if (lawNumberInput instanceof HTMLInputElement) {
            lawNumberInput.addEventListener('input', updatePreview);
        }
        if (titleIdInput instanceof HTMLInputElement) {
            titleIdInput.addEventListener('input', updatePreview);
        }

        updatePreview();
    });
}

function setupTranslationEditor() {
    const editors = Array.from(document.querySelectorAll('[data-translation-editor]'));

    editors.forEach((editor) => {
        if (!(editor instanceof HTMLElement)) {
            return;
        }

        const select = editor.querySelector('[data-translation-select]');
        const panels = Array.from(editor.querySelectorAll('[data-translation-panel]'));
        const fields = Array.from(editor.querySelectorAll('[data-translation-field]'));

        if (!(select instanceof HTMLSelectElement) || !panels.length) {
            return;
        }

        const updateVisiblePanel = () => {
            panels.forEach((panel) => {
                if (!(panel instanceof HTMLElement)) {
                    return;
                }

                panel.hidden = panel.dataset.translationPanel !== select.value;
            });
        };

        const updateOptionLabels = () => {
            Array.from(select.options).forEach((option) => {
                const languageCode = option.value;
                const optionLabel = option.text.replace(/\s*\*$/, '');
                const languageFields = fields.filter(
                    (field) => field instanceof HTMLElement && field.dataset.translationField === languageCode,
                );
                const isDirty = languageFields.some((field) => {
                    if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement)) {
                        return false;
                    }

                    return (field.value || '') !== (field.dataset.initialValue || '');
                });

                option.text = isDirty ? `${optionLabel} *` : optionLabel;
            });
        };

        fields.forEach((field) => {
            field.addEventListener('input', updateOptionLabels);
            field.addEventListener('change', updateOptionLabels);
        });

        select.addEventListener('change', updateVisiblePanel);

        updateVisiblePanel();
        updateOptionLabels();
    });
}

function setupNodeTypeSections() {
    const nodeTypeSelects = Array.from(document.querySelectorAll('[data-node-type-select]'));

    nodeTypeSelects.forEach((select) => {
        if (!(select instanceof HTMLSelectElement)) {
            return;
        }

        const form = select.closest('form');

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const sections = Array.from(form.querySelectorAll('[data-node-type-section]'));

        const updateSections = () => {
            sections.forEach((section) => {
                if (!(section instanceof HTMLElement)) {
                    return;
                }

                section.hidden = section.dataset.nodeTypeSection !== select.value;
            });
        };

        select.addEventListener('change', updateSections);
        updateSections();
    });
}

function setupMediaTypeSections() {
    const selects = Array.from(document.querySelectorAll('[data-media-type-select]'));

    selects.forEach((select) => {
        if (!(select instanceof HTMLSelectElement)) {
            return;
        }

        const form = select.closest('form');

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const sections = Array.from(form.querySelectorAll('[data-media-type-section]'));

        const updateSections = () => {
            sections.forEach((section) => {
                if (!(section instanceof HTMLElement)) {
                    return;
                }

                section.hidden = section.dataset.mediaTypeSection !== select.value;
            });
        };

        select.addEventListener('change', updateSections);
        updateSections();
    });
}

function parsePreviewMap(rawValue) {
    if (!rawValue) {
        return {};
    }

    try {
        return JSON.parse(rawValue);
    } catch {
        return {};
    }
}

function updateMediaSelectionPreview(container, previewItem) {
    if (!(container instanceof HTMLElement)) {
        return;
    }

    if (!previewItem || !previewItem.url) {
        container.hidden = true;
        container.replaceChildren();
        return;
    }

    const frame = document.createElement('div');
    frame.className = 'media-preview-frame media-preview-frame-selection';

    const image = document.createElement('img');
    image.className = 'media-preview-thumb';
    image.loading = 'lazy';
    image.src = previewItem.url;
    image.alt = previewItem.label || 'Selected media preview';
    frame.appendChild(image);

    container.replaceChildren(frame);
    container.hidden = false;
}

function setupImageAssetPicker() {
    const sections = Array.from(document.querySelectorAll('[data-existing-image-select]'));

    sections.forEach((select) => {
        if (!(select instanceof HTMLSelectElement)) {
            return;
        }

        const container = select.closest('[data-node-type-section="image"]');

        if (!(container instanceof HTMLElement)) {
            return;
        }

        const fields = Array.from(container.querySelectorAll('[data-image-new-field]'));
        const preview = container.querySelector('[data-image-selection-preview]');
        const previewMap = parsePreviewMap(select.dataset.previewMap);

        const updateFields = () => {
            const hasExistingSelection = select.value !== '';

            fields.forEach((field) => {
                if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement)) {
                    return;
                }

                field.disabled = hasExistingSelection;
            });

            updateMediaSelectionPreview(preview, previewMap[select.value] ?? null);
        };

        select.addEventListener('change', updateFields);
        updateFields();
    });
}

function setupVideoGroupEditor() {
    const editors = Array.from(document.querySelectorAll('[data-video-group-editor]'));

    editors.forEach((editor) => {
        if (!(editor instanceof HTMLElement)) {
            return;
        }

        const list = editor.querySelector('[data-video-group-list]');
        const addButton = editor.querySelector('[data-video-add]');

        if (!(list instanceof HTMLElement) || !(addButton instanceof HTMLButtonElement)) {
            return;
        }

        const template = editor.querySelector('[data-video-item-template]');
        const previewMap = parsePreviewMap(editor.dataset.videoPreviewMap);

        const buildItem = (index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'card video-item-card';
            wrapper.dataset.videoItem = '';
            const templateHtml = template instanceof HTMLTemplateElement
                ? template.innerHTML
                : `
                    <div class="video-item-header">
                        <h4>Video <span data-video-item-number>__NUMBER__</span></h4>
                        <button type="button" class="button-danger" data-video-remove data-confirm-message="Remove this video row? Unsaved changes in this row will be lost.">Remove video</button>
                    </div>
                    <label>
                        <div class="law-meta">Use existing video</div>
                        <select name="video_items[__INDEX__][existing_media_asset_id]" data-video-existing-select>
                            <option value="">Create a new video from URL below</option>
                        </select>
                    </label>
                    <label>
                        <div class="law-meta">Source URL</div>
                        <input type="url" name="video_items[__INDEX__][url]" value="" placeholder="https://www.youtube.com/watch?v=..." data-video-new-field>
                    </label>
                    <label>
                        <div class="law-meta">Caption</div>
                        <input type="text" name="video_items[__INDEX__][caption]" value="" data-video-new-field>
                    </label>
                    <label>
                        <div class="law-meta">Credit / attribution</div>
                        <input type="text" name="video_items[__INDEX__][credit]" value="" data-video-new-field>
                    </label>
                `;
            wrapper.innerHTML = templateHtml
                .replace(/__INDEX__/g, `${index}`)
                .replace(/__NUMBER__/g, `${index + 1}`);

            return wrapper;
        };

        const updateItemMode = (item) => {
            if (!(item instanceof HTMLElement)) {
                return;
            }

            const existingSelect = item.querySelector('[data-video-existing-select]');
            const newFields = Array.from(item.querySelectorAll('[data-video-new-field]'));
            const hasExistingSelection = existingSelect instanceof HTMLSelectElement && existingSelect.value !== '';
            const preview = item.querySelector('[data-video-selection-preview]');

            newFields.forEach((field) => {
                if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement)) {
                    return;
                }

                field.disabled = hasExistingSelection;
            });

            if (existingSelect instanceof HTMLSelectElement) {
                updateMediaSelectionPreview(preview, previewMap[existingSelect.value] ?? null);
            }
        };

        const renumberItems = () => {
            const items = Array.from(list.querySelectorAll('[data-video-item]'));

            items.forEach((item, index) => {
                if (!(item instanceof HTMLElement)) {
                    return;
                }

                const number = item.querySelector('[data-video-item-number]');
                if (number) {
                    number.textContent = `${index + 1}`;
                }

                Array.from(item.querySelectorAll('input')).forEach((input) => {
                    if (!(input instanceof HTMLInputElement)) {
                        return;
                    }

                    if (input.name.endsWith('[url]')) {
                        input.name = `video_items[${index}][url]`;
                    }

                    if (input.name.endsWith('[caption]')) {
                        input.name = `video_items[${index}][caption]`;
                    }

                    if (input.name.endsWith('[credit]')) {
                        input.name = `video_items[${index}][credit]`;
                    }
                });

                const existingSelect = item.querySelector('[data-video-existing-select]');
                if (existingSelect instanceof HTMLSelectElement) {
                    existingSelect.name = `video_items[${index}][existing_media_asset_id]`;
                }

                updateItemMode(item);
            });
        };

        const ensureAtLeastOneItem = () => {
            if (!list.querySelector('[data-video-item]')) {
                list.appendChild(buildItem(0));
            }

            renumberItems();
        };

        addButton.addEventListener('click', () => {
            const nextIndex = list.querySelectorAll('[data-video-item]').length;
            list.appendChild(buildItem(nextIndex));
            renumberItems();
        });

        list.addEventListener('click', (event) => {
            const target = event.target;

            if (!(target instanceof HTMLElement)) {
                return;
            }

            const removeButton = target.closest('[data-video-remove]');
            if (!removeButton) {
                return;
            }

            if (removeButton instanceof HTMLElement) {
                const confirmMessage = removeButton.dataset.confirmMessage;

                if (confirmMessage && !window.confirm(confirmMessage)) {
                    return;
                }
            }

            const item = removeButton.closest('[data-video-item]');
            if (item instanceof HTMLElement) {
                item.remove();
                ensureAtLeastOneItem();
            }
        });

        list.addEventListener('change', (event) => {
            const target = event.target;

            if (!(target instanceof HTMLElement)) {
                return;
            }

            const existingSelect = target.closest('[data-video-existing-select]');

            if (existingSelect instanceof HTMLSelectElement) {
                const item = existingSelect.closest('[data-video-item]');

                if (item instanceof HTMLElement) {
                    updateItemMode(item);
                }
            }
        });

        ensureAtLeastOneItem();
    });
}

function setupDocumentPageEditor() {
    const editors = Array.from(document.querySelectorAll('[data-document-pages-editor]'));

    editors.forEach((editor) => {
        if (!(editor instanceof HTMLElement)) {
            return;
        }

        const form = editor.closest('form');
        const addButton = form?.querySelector('[data-document-page-add]');
        const typeSelect = form?.querySelector('[data-document-type-select]');
        const removedPagesContainer = form?.querySelector('[data-document-removed-pages]');

        if (!(form instanceof HTMLFormElement) || !(addButton instanceof HTMLButtonElement)) {
            return;
        }

        const slugify = (value) =>
            value
                .toString()
                .normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .replace(/-{2,}/g, '-');

        const updateDocumentMediaPlaceholder = (item) => {
            if (!(item instanceof HTMLElement)) {
                return;
            }

            const keyInput = item.querySelector('[data-document-media-key]');
            const placeholder = item.querySelector('[data-document-media-placeholder]');

            if (!(keyInput instanceof HTMLInputElement) || !(placeholder instanceof HTMLElement)) {
                return;
            }

            const mediaKey = slugify(keyInput.value) || 'example-key';
            placeholder.textContent = `{{media:${mediaKey}}}`;
        };

        const updateDocumentMediaRowMode = (item) => {
            if (!(item instanceof HTMLElement) || item.hidden) {
                return;
            }

            const editorElement = item.closest('[data-document-media-editor]');
            const existingSelect = item.querySelector('[data-document-media-existing-select]');
            const newFields = Array.from(item.querySelectorAll('[data-document-media-new-field]'));
            const preview = item.querySelector('[data-document-media-selection-preview]');
            const previewMap = editorElement instanceof HTMLElement
                ? parsePreviewMap(editorElement.dataset.documentMediaPreviewMap)
                : {};
            const hasExistingSelection = existingSelect instanceof HTMLSelectElement && existingSelect.value !== '';

            newFields.forEach((field) => {
                if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement)) {
                    return;
                }

                field.disabled = hasExistingSelection;
            });

            if (existingSelect instanceof HTMLSelectElement) {
                updateMediaSelectionPreview(preview, previewMap[existingSelect.value] ?? null);
            }
        };

        const renumberMediaItems = (pageItem, pageIndex) => {
            if (!(pageItem instanceof HTMLElement)) {
                return;
            }

            const mediaItems = Array.from(pageItem.querySelectorAll('[data-document-media-item]'));

            mediaItems.forEach((mediaItem, mediaIndex) => {
                if (!(mediaItem instanceof HTMLElement)) {
                    return;
                }

                const number = mediaItem.querySelector('[data-document-media-number]');
                if (number && !mediaItem.hidden) {
                    number.textContent = `${mediaIndex + 1}`;
                }

                Array.from(mediaItem.querySelectorAll('input, select')).forEach((field) => {
                    if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement)) {
                        return;
                    }

                    field.name = field.name
                        .replace(/pages\[\d+\]/, `pages[${pageIndex}]`)
                        .replace(/media\[\d+\]/, `media[${mediaIndex}]`);
                });

                updateDocumentMediaPlaceholder(mediaItem);
                updateDocumentMediaRowMode(mediaItem);
            });
        };

        const renumberItems = () => {
            const items = Array.from(editor.querySelectorAll('[data-document-page-item]'));

            items.forEach((item, index) => {
                if (!(item instanceof HTMLElement)) {
                    return;
                }

                const number = item.querySelector('[data-document-page-number]');
                if (number) {
                    number.textContent = `${index + 1}`;
                }

                Array.from(item.querySelectorAll('input, textarea, select')).forEach((field) => {
                    if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement)) {
                        return;
                    }

                    field.name = field.name.replace(/pages\[\d+\]/, `pages[${index}]`);
                });

                renumberMediaItems(item, index);
            });
        };

        const updateTypeVisibility = () => {
            if (!(typeSelect instanceof HTMLSelectElement)) {
                return;
            }

            const isSingle = typeSelect.value === 'single';

            Array.from(editor.querySelectorAll('[data-document-collection-only]')).forEach((element) => {
                if (element instanceof HTMLElement) {
                    element.hidden = isSingle;
                }
            });
        };

        addButton.addEventListener('click', () => {
            const nextIndex = editor.querySelectorAll('[data-document-page-item]').length;
            const mediaTemplate = editor.querySelector('[data-document-media-template]');
            const mediaTemplateHtml = mediaTemplate instanceof HTMLTemplateElement ? mediaTemplate.outerHTML : '';
            const mediaPreviewMap = editor.querySelector('[data-document-media-editor]')?.getAttribute('data-document-media-preview-map') || '{}';
            const wrapper = document.createElement('div');
            wrapper.className = 'card stack-form document-page-card';
            wrapper.dataset.documentPageItem = '';
            wrapper.innerHTML = `
                <div class="video-item-header">
                    <h2>Page <span data-document-page-number>${nextIndex + 1}</span></h2>
                    <button type="button" class="button-danger" data-document-page-remove data-confirm-message="Remove this page? Unsaved changes in this page will be lost.">Remove page</button>
                </div>
                <input type="hidden" name="pages[${nextIndex}][id]" value="">
                <label>
                    <div class="law-meta">Page slug</div>
                    <input type="text" name="pages[${nextIndex}][slug]" value="">
                </label>
                <label>
                    <div class="law-meta">Page title (ID)</div>
                    <input type="text" name="pages[${nextIndex}][title_id]" value="">
                </label>
                <label>
                    <div class="law-meta">Page title (EN)</div>
                    <input type="text" name="pages[${nextIndex}][title_en]" value="">
                </label>
                <label>
                    <div class="law-meta">Body HTML (ID)</div>
                    <textarea name="pages[${nextIndex}][body_html_id]" rows="10"></textarea>
                </label>
                <label>
                    <div class="law-meta">Body HTML (EN)</div>
                    <textarea name="pages[${nextIndex}][body_html_en]" rows="10"></textarea>
                </label>
                <label data-document-collection-only>
                    <div class="law-meta">Sort order</div>
                    <input type="number" min="1" name="pages[${nextIndex}][sort_order]" value="${nextIndex + 1}">
                </label>
                <label>
                    <div class="law-meta">Page status</div>
                    <select name="pages[${nextIndex}][status]">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </label>
                <div class="document-inline-media-editor" data-document-media-editor data-document-media-preview-map="{}">
                    <div class="video-item-header">
                        <div>
                            <h3>Inline media</h3>
                            <p class="nav-meta">Attach image assets to this page, then place them in the body with placeholders like <code>{{media:example-key}}</code>.</p>
                        </div>
                        <button type="button" data-document-media-add>Add image</button>
                    </div>
                    <div class="stack-form" data-document-media-list></div>
                    ${mediaTemplateHtml}
                </div>
            `;
            const mediaEditor = wrapper.querySelector('[data-document-media-editor]');
            if (mediaEditor instanceof HTMLElement) {
                mediaEditor.setAttribute('data-document-media-preview-map', mediaPreviewMap);
            }
            editor.appendChild(wrapper);
            renumberItems();
            updateTypeVisibility();
        });

        editor.addEventListener('click', (event) => {
            const target = event.target;

            if (!(target instanceof HTMLElement)) {
                return;
            }

            const addMediaButton = target.closest('[data-document-media-add]');
            if (addMediaButton instanceof HTMLElement) {
                const mediaEditor = addMediaButton.closest('[data-document-media-editor]');
                const pageItem = addMediaButton.closest('[data-document-page-item]');
                const list = mediaEditor?.querySelector('[data-document-media-list]');
                const template = mediaEditor?.querySelector('[data-document-media-template]');

                if (!(mediaEditor instanceof HTMLElement) || !(pageItem instanceof HTMLElement) || !(list instanceof HTMLElement) || !(template instanceof HTMLTemplateElement)) {
                    return;
                }

                const pageIndex = Array.from(editor.querySelectorAll('[data-document-page-item]')).indexOf(pageItem);
                const mediaIndex = list.querySelectorAll('[data-document-media-item]').length;
                const wrapper = document.createElement('div');
                wrapper.className = 'card document-media-card';
                wrapper.dataset.documentMediaItem = '';
                wrapper.innerHTML = template.innerHTML
                    .replace(/__PAGE_INDEX__/g, `${pageIndex}`)
                    .replace(/__MEDIA_INDEX__/g, `${mediaIndex}`)
                    .replace(/__NUMBER__/g, `${mediaIndex + 1}`);

                list.appendChild(wrapper);
                renumberItems();
                return;
            }

            const removeMediaButton = target.closest('[data-document-media-remove]');
            if (removeMediaButton instanceof HTMLElement) {
                const confirmMessage = removeMediaButton.dataset.confirmMessage;
                if (confirmMessage && !window.confirm(confirmMessage)) {
                    return;
                }

                const mediaItem = removeMediaButton.closest('[data-document-media-item]');

                if (!(mediaItem instanceof HTMLElement)) {
                    return;
                }

                const removeInput = mediaItem.querySelector('[data-document-media-remove-input]');

                if (mediaItem.dataset.documentMediaPivotId && removeInput instanceof HTMLInputElement) {
                    removeInput.value = '1';
                    mediaItem.hidden = true;
                } else {
                    mediaItem.remove();
                }

                renumberItems();
                return;
            }

            const removeButton = target.closest('[data-document-page-remove]');
            if (!(removeButton instanceof HTMLElement)) {
                return;
            }

            const confirmMessage = removeButton.dataset.confirmMessage;
            if (confirmMessage && !window.confirm(confirmMessage)) {
                return;
            }

            const item = removeButton.closest('[data-document-page-item]');
            if (!(item instanceof HTMLElement)) {
                return;
            }

            const existingPageId = item.dataset.documentPageId;
            if (existingPageId && removedPagesContainer instanceof HTMLElement) {
                const alreadyMarked = removedPagesContainer.querySelector(`input[value="${existingPageId}"]`);

                if (!alreadyMarked) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'remove_page_ids[]';
                    hiddenInput.value = existingPageId;
                    removedPagesContainer.appendChild(hiddenInput);
                }
            }

            item.remove();
            renumberItems();
        });

        editor.addEventListener('input', (event) => {
            const target = event.target;

            if (!(target instanceof HTMLElement)) {
                return;
            }

            const keyInput = target.closest('[data-document-media-key]');

            if (keyInput instanceof HTMLInputElement) {
                updateDocumentMediaPlaceholder(keyInput.closest('[data-document-media-item]'));
            }
        });

        editor.addEventListener('change', (event) => {
            const target = event.target;

            if (!(target instanceof HTMLElement)) {
                return;
            }

            const existingSelect = target.closest('[data-document-media-existing-select]');

            if (existingSelect instanceof HTMLSelectElement) {
                updateDocumentMediaRowMode(existingSelect.closest('[data-document-media-item]'));
            }
        });

        if (typeSelect instanceof HTMLSelectElement) {
            typeSelect.addEventListener('change', updateTypeVisibility);
            updateTypeVisibility();
        }

        renumberItems();
    });
}
