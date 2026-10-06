(function ($) {
    'use strict';

    const root = document.querySelector('[data-aab-mb]');
    if (!root) return;

    const toPersianNumber = (val) => {
        const en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        const fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return String(val).replace(/[0-9]/g, (w) => fa[en.indexOf(w)]);
    };

    const uniqueKey = () => 'aab-' + (window.crypto && crypto.randomUUID ? crypto.randomUUID() : Date.now().toString(36) + Math.random().toString(36).slice(2));

    // Toast manager with Undo
    const toast = (function () {
        const el = root.querySelector('[data-aab-toast]');
        let timeout = null;
        let pendingUndo = null;

        const show = (message, undoCallback) => {
            if (!el) return;
            clearTimeout(timeout);
            if (pendingUndo) {
                pendingUndo.finalize();
                pendingUndo = null;
            }

            el.innerHTML = '';
            const msgSpan = document.createElement('span');
            msgSpan.textContent = message;
            el.appendChild(msgSpan);

            if (undoCallback) {
                const undoBtn = document.createElement('button');
                undoBtn.type = 'button';
                undoBtn.className = 'aab-mb-toast__undo';
                undoBtn.textContent = 'لغو (Undo)';
                undoBtn.addEventListener('click', () => {
                    clearTimeout(timeout);
                    el.hidden = true;
                    if (pendingUndo) {
                        pendingUndo.restore();
                        pendingUndo = null;
                    }
                });
                el.appendChild(undoBtn);

                pendingUndo = {
                    restore: undoCallback.restore,
                    finalize: undoCallback.finalize || (() => {})
                };
            }

            el.hidden = false;
            timeout = setTimeout(() => {
                el.hidden = true;
                if (pendingUndo) {
                    pendingUndo.finalize();
                    pendingUndo = null;
                }
            }, 6000);
        };

        return { show };
    })();

    // Tabs navigation & persistence
    (function () {
        const tabs = Array.from(root.querySelectorAll('[data-aab-tab]'));
        const panels = Array.from(root.querySelectorAll('[data-aab-panel]'));
        const storageKey = 'aab_active_tab';

        const activate = (tabId) => {
            tabs.forEach((tab) => {
                const active = tab.dataset.aabTab === tabId;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
                tab.setAttribute('tabindex', active ? '0' : '-1');
            });
            panels.forEach((panel) => {
                panel.hidden = panel.dataset.aabPanel !== tabId;
            });
            try {
                localStorage.setItem(storageKey, tabId);
            } catch (e) {}
        };

        const savedTab = (function () {
            try {
                return localStorage.getItem(storageKey);
            } catch (e) {
                return null;
            }
        })();

        if (savedTab && tabs.some((t) => t.dataset.aabTab === savedTab)) {
            activate(savedTab);
        }

        root.querySelector('.aab-mb__tabs')?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-aab-tab]');
            if (btn) activate(btn.dataset.aabTab);
        });

        root.querySelector('.aab-mb__tabs')?.addEventListener('keydown', (e) => {
            const current = e.target.closest('[data-aab-tab]');
            if (!current) return;
            const idx = tabs.indexOf(current);
            let nextIdx = idx;

            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                nextIdx = (idx + 1) % tabs.length;
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                nextIdx = (idx - 1 + tabs.length) % tabs.length;
            } else if (e.key === 'Home') {
                nextIdx = 0;
            } else if (e.key === 'End') {
                nextIdx = tabs.length - 1;
            } else {
                return;
            }

            e.preventDefault();
            tabs[nextIdx].focus();
            activate(tabs[nextIdx].dataset.aabTab);
        });
    })();

    // Copy shortcodes
    root.addEventListener('click', async (e) => {
        const copyBtn = e.target.closest('[data-aab-copy]');
        if (!copyBtn) return;
        const text = copyBtn.dataset.aabCopy;
        if (!text) return;

        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
            } else {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                ta.remove();
            }
            toast.show('شورت‌کد کپی شد: ' + text);
        } catch (err) {
            toast.show('کپی امکان‌پذیر نبود؛ متن را به‌صورت دستی کپی کنید.');
        }
    });

    // Sync state: counters, numbers, empty states
    const syncPanel = (panel) => {
        if (!panel) return;
        const panelId = panel.dataset.aabPanel;
        const list = panel.querySelector('[data-aab-list]');
        const empty = panel.querySelector('[data-aab-empty]');
        const items = list ? Array.from(list.querySelectorAll(':scope > [data-aab-item]:not([data-aab-deleted])')) : [];

        // Tab count badge
        const tab = root.querySelector(`[data-aab-tab="${panelId}"]`);
        if (tab) {
            const badge = tab.querySelector('[data-aab-count]');
            if (badge) {
                badge.textContent = toPersianNumber(items.length);
                badge.hidden = items.length === 0;
            }
        }

        // Empty state
        if (empty) {
            empty.hidden = items.length > 0;
        }

        // Renumber items
        items.forEach((item, index) => {
            const numEl = item.querySelector('[data-aab-num]');
            if (numEl) numEl.textContent = toPersianNumber(index + 1);

            const upBtn = item.querySelector('[data-aab-move="up"]');
            const downBtn = item.querySelector('[data-aab-move="down"]');
            if (upBtn) upBtn.disabled = index === 0;
            if (downBtn) downBtn.disabled = index === items.length - 1;
        });
    };

    const syncAllPanels = () => {
        root.querySelectorAll('[data-aab-panel]').forEach(syncPanel);
    };

    // Expand / collapse card
    root.addEventListener('click', (e) => {
        const toggle = e.target.closest('[data-aab-toggle]');
        if (!toggle) return;
        const item = toggle.closest('[data-aab-item]');
        const body = item?.querySelector('[data-aab-body]');
        if (!body) return;

        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        body.hidden = isExpanded;
        item.dataset.aabExpanded = isExpanded ? 'false' : 'true';
    });

    // Real-time summary update as user types
    root.addEventListener('input', (e) => {
        const input = e.target.closest('[data-aab-summary-source]');
        if (!input) return;
        const item = input.closest('[data-aab-item]');
        const summary = item?.querySelector('[data-aab-summary]');
        if (summary) {
            summary.textContent = input.value.trim();
        }
    });

    // Up / Down movement
    root.addEventListener('click', (e) => {
        const moveBtn = e.target.closest('[data-aab-move]');
        if (!moveBtn) return;
        const item = moveBtn.closest('[data-aab-item]');
        const list = item?.parentElement;
        if (!item || !list) return;

        const dir = moveBtn.dataset.aabMove;
        if (dir === 'up') {
            const prev = item.previousElementSibling;
            if (prev) {
                list.insertBefore(item, prev);
                syncPanel(item.closest('[data-aab-panel]'));
                moveBtn.focus();
            }
        } else if (dir === 'down') {
            const next = item.nextElementSibling;
            if (next) {
                list.insertBefore(next, item);
                syncPanel(item.closest('[data-aab-panel]'));
                moveBtn.focus();
            }
        }
    });

    // Remove with UNDO support
    root.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('[data-aab-remove]');
        if (!removeBtn) return;
        const removable = removeBtn.closest('[data-aab-removable]');
        if (!removable) return;

        const parent = removable.parentElement;
        const nextSibling = removable.nextElementSibling;
        const panel = removable.closest('[data-aab-panel]');

        // Temporarily mark & hide
        removable.dataset.aabDeleted = 'true';
        removable.style.display = 'none';
        syncPanel(panel);

        toast.show('مورد موردنظر حذف شد.', {
            restore: () => {
                delete removable.dataset.aabDeleted;
                removable.style.display = '';
                if (nextSibling && nextSibling.parentElement === parent) {
                    parent.insertBefore(removable, nextSibling);
                } else {
                    parent.appendChild(removable);
                }
                syncPanel(panel);
            },
            finalize: () => {
                removable.remove();
                syncPanel(panel);
            }
        });
    });

    // Template rendering helper
    const renderTemplate = (panel, templateName, tokens) => {
        const template = panel.querySelector(`[data-aab-template="${templateName}"]`);
        if (!template) return null;
        let html = template.innerHTML;
        Object.keys(tokens).forEach((k) => {
            html = html.replaceAll(k, tokens[k]);
        });
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        return wrap.firstElementChild;
    };

    // Add buttons
    root.addEventListener('click', (e) => {
        const addBtn = e.target.closest('[data-aab-add]');
        if (!addBtn) return;
        const action = addBtn.dataset.aabAdd;
        const panel = addBtn.closest('[data-aab-panel]');
        const list = panel?.querySelector('[data-aab-list]');
        if (!panel || !list) return;

        const max = parseInt(list.dataset.aabMax, 10) || 50;
        if (list.querySelectorAll(':scope > [data-aab-item]:not([data-aab-deleted])').length >= max) {
            toast.show(`حداکثر ${max} مورد می‌توانید اضافه کنید.`);
            return;
        }

        let newRow = null;
        const key = uniqueKey();

        if (action === 'gallery') {
            newRow = renderTemplate(panel, 'gallery', { '__GALLERY_KEY__': key });
        } else if (action === 'link') {
            newRow = renderTemplate(panel, 'link', { '__LINK_KEY__': key });
        } else if (action === 'video') {
            newRow = renderTemplate(panel, 'video', { '__KEY__': String(Date.now()) });
        } else if (action === 'faq') {
            newRow = renderTemplate(panel, 'faq', { '__KEY__': String(Date.now()) });
        }

        if (newRow) {
            list.appendChild(newRow);
            syncPanel(panel);
            const firstInput = newRow.querySelector('input:not([type="hidden"]), textarea');
            firstInput?.focus();
        }
    });

    // Media library: Gallery bulk add
    root.addEventListener('click', (e) => {
        const addImagesBtn = e.target.closest('[data-aab-add-images]');
        if (!addImagesBtn || !window.wp?.media) return;
        const galleryItem = addImagesBtn.closest('[data-aab-item]');
        const thumbsWrap = galleryItem?.querySelector('[data-aab-images]');
        const panel = galleryItem?.closest('[data-aab-panel]');
        const galleryKey = galleryItem?.dataset.key;
        if (!thumbsWrap || !panel || !galleryKey) return;

        const frame = wp.media({
            title: 'انتخاب تصاویر گالری',
            button: { text: 'افزودن به گالری' },
            library: { type: 'image' },
            multiple: true
        });

        frame.on('select', () => {
            const selection = frame.state().get('selection').toJSON();
            if (!selection || !selection.length) return;

            selection.forEach((att) => {
                const imgKey = uniqueKey();
                const tile = renderTemplate(panel, 'gallery-image', {
                    '__GALLERY_KEY__': galleryKey,
                    '__IMAGE_KEY__': imgKey
                });
                if (!tile) return;

                const img = tile.querySelector('[data-aab-image-preview]');
                const idInput = tile.querySelector('[data-aab-image-id]');
                const altInput = tile.querySelector('[data-aab-image-alt]');
                const captionInput = tile.querySelector('[data-aab-image-caption]');

                if (idInput) idInput.value = att.id;
                if (img) {
                    img.src = att.sizes?.medium?.url || att.sizes?.thumbnail?.url || att.url;
                    img.hidden = false;
                }
                if (altInput && !altInput.value) {
                    altInput.value = att.alt || '';
                }
                if (captionInput && !captionInput.value && att.caption) {
                    captionInput.value = att.caption.replace(/<[^>]*>/g, '');
                }

                thumbsWrap.appendChild(tile);
            });

            // update meta count in header
            const metaEl = galleryItem.querySelector('[data-aab-meta]');
            if (metaEl) {
                const count = thumbsWrap.querySelectorAll('[data-aab-image]:not([data-aab-deleted])').length;
                metaEl.textContent = toPersianNumber(count) + ' تصویر';
            }
            const emptyNotice = galleryItem.querySelector('[data-aab-images-empty]');
            if (emptyNotice) emptyNotice.hidden = true;
        });

        frame.open();
    });

    // Media library: replace single image
    root.addEventListener('click', (e) => {
        const replaceBtn = e.target.closest('[data-aab-replace-image]');
        if (!replaceBtn || !window.wp?.media) return;
        const tile = replaceBtn.closest('[data-aab-thumb], [data-aab-image]');
        if (!tile) return;

        const frame = wp.media({
            title: 'تعویض تصویر',
            button: { text: 'انتخاب تصویر' },
            library: { type: 'image' },
            multiple: false
        });

        frame.on('select', () => {
            const att = frame.state().get('selection').first()?.toJSON();
            if (!att) return;

            const img = tile.querySelector('[data-aab-image-preview]');
            const idInput = tile.querySelector('[data-aab-image-id]');
            const altInput = tile.querySelector('[data-aab-image-alt]');

            if (idInput) idInput.value = att.id;
            if (img) {
                img.src = att.sizes?.medium?.url || att.sizes?.thumbnail?.url || att.url;
                img.hidden = false;
            }
            if (altInput && !altInput.value) {
                altInput.value = att.alt || '';
            }
        });

        frame.open();
    });

    // Link autocomplete search & preview
    (function () {
        let debounceTimer = null;
        let activeRequest = 0;

        root.addEventListener('input', (e) => {
            const input = e.target.closest('[data-aab-target-search]');
            if (!input) return;
            const field = input.closest('[data-aab-search]');
            const results = field?.querySelector('[data-aab-target-results]');
            const spinner = field?.querySelector('[data-aab-search-spinner]');
            const idInput = field?.querySelector('[data-aab-target-id]');
            const linkItem = input.closest('[data-aab-item]');
            if (!results) return;

            // Clear id if user edits text
            if (idInput && input.value !== input.dataset.selectedTitle) {
                idInput.value = '';
            }

            clearTimeout(debounceTimer);
            const term = input.value.trim();

            if (term.length < 2) {
                results.hidden = true;
                results.innerHTML = '';
                if (spinner) spinner.classList.remove('is-active');
                return;
            }

            if (spinner) spinner.classList.add('is-active');
            const reqId = ++activeRequest;

            debounceTimer = setTimeout(async () => {
                try {
                    const query = new URLSearchParams({
                        action: 'aab_search_internal_targets',
                        nonce: window.AABAdmin?.searchNonce || '',
                        term
                    });
                    const res = await fetch((window.AABAdmin?.ajaxUrl || '/wp-admin/admin-ajax.php') + '?' + query, { credentials: 'same-origin' });
                    const data = await res.json();

                    if (reqId !== activeRequest || !input.isConnected) return;
                    if (spinner) spinner.classList.remove('is-active');

                    results.innerHTML = '';
                    if (!data.success || !Array.isArray(data.data) || !data.data.length) {
                        results.innerHTML = '<div class="aab-mb-search__empty">موردی یافت نشد.</div>';
                        results.hidden = false;
                        return;
                    }

                    data.data.forEach((item) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'aab-mb-search__item';
                        btn.dataset.itemData = JSON.stringify(item);

                        if (item.thumb) {
                            const thumb = document.createElement('img');
                            thumb.className = 'aab-mb-search__item-thumb';
                            thumb.src = item.thumb;
                            thumb.alt = '';
                            btn.appendChild(thumb);
                        }

                        const titleSpan = document.createElement('span');
                        titleSpan.className = 'aab-mb-search__item-title';
                        titleSpan.textContent = item.title;
                        btn.appendChild(titleSpan);

                        const typeSpan = document.createElement('span');
                        typeSpan.className = 'aab-mb-search__item-type';
                        typeSpan.textContent = item.type_label || item.type;
                        btn.appendChild(typeSpan);

                        results.appendChild(btn);
                    });

                    results.hidden = false;
                } catch (err) {
                    if (spinner) spinner.classList.remove('is-active');
                    results.innerHTML = '<div class="aab-mb-search__empty">خطا در جستجو.</div>';
                    results.hidden = false;
                }
            }, 250);
        });

        // Selecting a search result
        root.addEventListener('click', (e) => {
            const btn = e.target.closest('.aab-mb-search__item');
            if (!btn) return;
            const field = btn.closest('[data-aab-search]');
            const linkItem = btn.closest('[data-aab-item]');
            const results = field?.querySelector('[data-aab-target-results]');
            const searchInput = field?.querySelector('[data-aab-target-search]');
            const idInput = field?.querySelector('[data-aab-target-id]');
            if (!field || !results || !searchInput || !idInput) return;

            let itemData = null;
            try {
                itemData = JSON.parse(btn.dataset.itemData);
            } catch (err) {}
            if (!itemData) return;

            idInput.value = itemData.id;
            searchInput.value = itemData.title;
            searchInput.dataset.selectedTitle = itemData.title;
            results.hidden = true;
            results.innerHTML = '';

            // Update item header
            const summary = linkItem?.querySelector('[data-aab-summary]');
            if (summary) summary.textContent = itemData.title;
            const meta = linkItem?.querySelector('[data-aab-meta]');
            if (meta) meta.textContent = itemData.type_label || itemData.type;

            // Update live preview
            const preview = linkItem?.querySelector('[data-aab-link-preview]');
            if (preview) {
                preview.hidden = false;
                const card = preview.querySelector('[data-aab-preview-card]');
                const img = preview.querySelector('[data-aab-preview-img]');
                const typeEl = preview.querySelector('[data-aab-preview-type]');
                const titleEl = preview.querySelector('[data-aab-preview-title]');
                const urlEl = preview.querySelector('[data-aab-preview-url]');

                if (img) img.src = itemData.thumb || '';
                if (card) card.classList.toggle('is-no-image', !itemData.thumb);
                if (typeEl) typeEl.textContent = itemData.type_label || itemData.type;
                if (titleEl) titleEl.textContent = itemData.title;
                if (urlEl) urlEl.href = itemData.url || '#';
            }
        });

        // Close search results on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[data-aab-search]')) {
                root.querySelectorAll('[data-aab-target-results]').forEach((r) => {
                    r.hidden = true;
                });
            }
        });

        // Live update button text in preview
        root.addEventListener('input', (e) => {
            const btnInput = e.target.closest('[data-aab-button-text]');
            if (!btnInput) return;
            const linkItem = btnInput.closest('[data-aab-item]');
            const previewBtn = linkItem?.querySelector('[data-aab-preview-button]');
            if (previewBtn) {
                previewBtn.textContent = btnInput.value.trim() || 'مشاهده مطلب';
            }
        });
    })();

    // jQuery UI Sortable
    if ($.fn.sortable) {
        $('[data-aab-list]').sortable({
            handle: '.aab-mb-item__handle',
            items: '> [data-aab-item]',
            axis: 'y',
            opacity: 0.85,
            update: function () {
                syncPanel(this.closest('[data-aab-panel]'));
            }
        });

        $('[data-aab-images]').sortable({
            handle: '.aab-mb-thumb__media',
            items: '> [data-aab-thumb], > [data-aab-image]',
            opacity: 0.85,
            update: function () {
                const galleryItem = this.closest('[data-aab-item]');
                const metaEl = galleryItem?.querySelector('[data-aab-meta]');
                if (metaEl) {
                    const count = this.querySelectorAll('[data-aab-image]:not([data-aab-deleted])').length;
                    metaEl.textContent = toPersianNumber(count) + ' تصویر';
                }
            }
        });
    }

    // Initialize all panels
    syncAllPanels();
})(jQuery);
