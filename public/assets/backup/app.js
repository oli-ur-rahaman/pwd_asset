document.addEventListener('DOMContentLoaded', () => {
    const escapeHtml = (value) => String(value).replace(/[&<>"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char]));
    const normalizeHelpHref = (href) => {
        const value = String(href || '').trim();
        if (!value) {
            return '';
        }
        const lower = value.toLowerCase();
        if (lower.startsWith('javascript:') || lower.startsWith('data:') || lower.startsWith('vbscript:')) {
            return '';
        }
        return value;
    };
    const renderHelpInformationHtml = (information) => {
        const lines = information.trim() === '' ? ['No additional information provided.'] : information.split(/\r\n|\r|\n/);
        const linkPattern = /<a\b([^>]*)href=(["'])(.*?)\2([^>]*)>(.*?)<\/a>/gi;
        return lines.map((line) => {
            linkPattern.lastIndex = 0;
            let html = '';
            let lastIndex = 0;
            let match;
            while ((match = linkPattern.exec(line)) !== null) {
                html += escapeHtml(line.slice(lastIndex, match.index));
                const href = normalizeHelpHref(match[3]);
                const attrsText = `${match[1] || ''} ${match[4] || ''}`.toLowerCase();
                const linkText = escapeHtml(match[5] || href || 'Open link');
                if (href) {
                    const extraAttrs = [];
                    if (/\bdownload\b/.test(attrsText)) {
                        extraAttrs.push('download');
                    }
                    const targetBlank = /\btarget\s*=\s*["']?_blank["']?/.test(attrsText);
                    extraAttrs.push(targetBlank ? 'target="_blank"' : 'target="_self"');
                    extraAttrs.push('rel="noopener"');
                    html += `<a href="${escapeHtml(href)}" ${extraAttrs.join(' ')}>${linkText}</a>`;
                } else {
                    html += linkText;
                }
                lastIndex = match.index + match[0].length;
            }
            html += escapeHtml(line.slice(lastIndex));
            return `<p>${html}</p>`;
        }).join('');
    };
    const setupStickyTableHeaders = () => {
        const candidateTables = Array.from(document.querySelectorAll('table')).filter((table) => table.tHead && table.tHead.querySelector('th'));
        if (!candidateTables.length) {
            return;
        }
        const layer = document.createElement('div');
        layer.className = 'sticky-table-header-layer';
        layer.setAttribute('aria-hidden', 'true');
        document.body.appendChild(layer);

        let activeTable = null;
        let activeWrapper = null;

        const isTableVisible = (table) => {
            if (!table.isConnected) {
                return false;
            }
            const modal = table.closest('.modal-backdrop');
            if (modal && !modal.classList.contains('open')) {
                return false;
            }
            const rect = table.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0;
        };

        const hideLayer = () => {
            activeTable = null;
            activeWrapper = null;
            layer.classList.remove('is-visible');
            layer.innerHTML = '';
            layer.style.width = '';
            layer.style.left = '';
        };

        const syncLayer = (table) => {
            if (!table || !table.tHead) {
                hideLayer();
                return;
            }
            const wrapper = table.closest('.table-wrap') || table.parentElement;
            if (!wrapper) {
                hideLayer();
                return;
            }
            const tableRect = table.getBoundingClientRect();
            const headRect = table.tHead.getBoundingClientRect();
            const wrapperRect = wrapper.getBoundingClientRect();
            const headerHeight = Math.max(1, Math.round(headRect.height || table.tHead.offsetHeight || 0));
            if (
                wrapperRect.width <= 0
                || tableRect.bottom <= headerHeight
                || headRect.bottom > 0
                || wrapperRect.bottom <= 0
                || wrapperRect.top >= window.innerHeight
            ) {
                hideLayer();
                return;
            }

            const cloneTable = table.cloneNode(false);
            const cloneHead = table.tHead.cloneNode(true);
            cloneTable.appendChild(cloneHead);
            cloneTable.style.width = `${Math.round(table.offsetWidth)}px`;
            cloneTable.style.transform = `translateX(${-wrapper.scrollLeft}px)`;

            const originalRows = Array.from(table.tHead.rows);
            const cloneRows = Array.from(cloneHead.rows);
            originalRows.forEach((row, rowIndex) => {
                const cloneRow = cloneRows[rowIndex];
                if (!cloneRow) {
                    return;
                }
                Array.from(row.cells).forEach((cell, cellIndex) => {
                    const cloneCell = cloneRow.cells[cellIndex];
                    if (!cloneCell) {
                        return;
                    }
                    const width = cell.getBoundingClientRect().width;
                    cloneCell.style.width = `${Math.round(width)}px`;
                    cloneCell.style.minWidth = `${Math.round(width)}px`;
                    cloneCell.style.maxWidth = `${Math.round(width)}px`;
                });
            });

            layer.innerHTML = '';
            layer.appendChild(cloneTable);
            layer.style.left = `${Math.round(wrapperRect.left)}px`;
            layer.style.width = `${Math.round(wrapperRect.width)}px`;
            layer.classList.add('is-visible');
            activeTable = table;
            activeWrapper = wrapper;
        };

        const pickActiveTable = () => {
            let bestTable = null;
            let bestTop = -Infinity;
            candidateTables.forEach((table) => {
                if (!isTableVisible(table)) {
                    return;
                }
                const wrapper = table.closest('.table-wrap') || table.parentElement;
                if (!wrapper) {
                    return;
                }
                const rect = table.getBoundingClientRect();
                const headRect = table.tHead.getBoundingClientRect();
                const headerHeight = Math.max(1, Math.round(headRect.height || table.tHead.offsetHeight || 0));
                const shouldStick = headRect.bottom <= 0 && rect.bottom > headerHeight;
                if (!shouldStick) {
                    return;
                }
                if (rect.top > bestTop) {
                    bestTop = rect.top;
                    bestTable = table;
                }
            });
            if (!bestTable) {
                hideLayer();
                return;
            }
            syncLayer(bestTable);
        };

        const scheduleUpdate = () => {
            window.requestAnimationFrame(pickActiveTable);
        };

        window.addEventListener('scroll', scheduleUpdate, { passive: true });
        window.addEventListener('resize', scheduleUpdate);
        candidateTables.forEach((table) => {
            const wrapper = table.closest('.table-wrap') || table.parentElement;
            if (wrapper) {
                wrapper.addEventListener('scroll', () => {
                    if (table === activeTable || wrapper === activeWrapper) {
                        scheduleUpdate();
                    }
                }, { passive: true });
            }
        });
        document.querySelectorAll('[data-modal], .modal-close').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                window.setTimeout(scheduleUpdate, 50);
            });
        });
        scheduleUpdate();
    };

    const pageKey = new URLSearchParams(window.location.search).get('page') || 'board';
    const scrollKey = `pwd-asset-scroll:${window.location.pathname}:${pageKey}`;
    const restoreScroll = () => {
        const stored = sessionStorage.getItem(scrollKey);
        if (!stored) {
            return;
        }
        const target = Number.parseInt(stored, 10);
        if (Number.isNaN(target) || target < 0) {
            return;
        }
        window.requestAnimationFrame(() => {
            window.scrollTo(0, target);
        });
    };
    setupStickyTableHeaders();
    restoreScroll();
    let scrollSaveTimer = null;
    const persistScroll = () => {
        sessionStorage.setItem(scrollKey, String(Math.max(0, Math.round(window.scrollY || window.pageYOffset || 0))));
    };
    window.addEventListener('scroll', () => {
        if (scrollSaveTimer) {
            window.clearTimeout(scrollSaveTimer);
        }
        scrollSaveTimer = window.setTimeout(persistScroll, 80);
    }, { passive: true });
    window.addEventListener('beforeunload', persistScroll);

    const loginMessage = document.getElementById('login-message');
    const loginPreview = document.getElementById('login-preview');
    if (loginMessage && loginPreview) {
        const updatePreview = () => {
            loginPreview.innerHTML = loginMessage.value;
        };
        loginMessage.addEventListener('input', updatePreview);
        updatePreview();
    }

    const welcomeMessage = document.getElementById('welcome-message');
    const welcomePreview = document.getElementById('welcome-preview');
    if (welcomeMessage && welcomePreview) {
        const updateWelcomePreview = () => {
            welcomePreview.innerHTML = welcomeMessage.value;
        };
        welcomeMessage.addEventListener('input', updateWelcomePreview);
        updateWelcomePreview();
    }

    const forgotLink = document.getElementById('forgot-link');
    const forgotModal = document.getElementById('forgot-modal');
    const closeForgot = document.getElementById('close-forgot');
    if (forgotLink && forgotModal && closeForgot) {
        forgotLink.addEventListener('click', (event) => {
            event.preventDefault();
            forgotModal.classList.add('open');
            forgotModal.setAttribute('aria-hidden', 'false');
        });
        closeForgot.addEventListener('click', () => {
            forgotModal.classList.remove('open');
            forgotModal.setAttribute('aria-hidden', 'true');
        });
    }

    const syncDownloadChip = (chip) => {
        const input = chip.querySelector('[data-download-chip-input]');
        if (!input) {
            return;
        }
        chip.classList.toggle('is-active', input.checked);
        chip.classList.toggle('is-removed', !input.checked);
    };

    document.querySelectorAll('[data-download-chip]').forEach((chip) => {
        syncDownloadChip(chip);
        const removeButton = chip.querySelector('[data-download-chip-remove]');
        const input = chip.querySelector('[data-download-chip-input]');
        if (removeButton && input) {
            removeButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                input.checked = false;
                syncDownloadChip(chip);
            });
        }
    });

    document.querySelectorAll('[data-download-cell-refresh]').forEach((button) => {
        button.addEventListener('click', () => {
            const cell = button.closest('[data-download-cell]');
            if (!cell) {
                return;
            }
            cell.querySelectorAll('[data-download-chip-input]').forEach((input) => {
                input.checked = true;
                syncDownloadChip(input.closest('[data-download-chip]'));
            });
        });
    });

    document.querySelectorAll('[data-download-cell-disable-all]').forEach((button) => {
        button.addEventListener('click', () => {
            const cell = button.closest('[data-download-cell]');
            if (!cell) {
                return;
            }
            cell.querySelectorAll('[data-download-chip-input]').forEach((input) => {
                input.checked = false;
                syncDownloadChip(input.closest('[data-download-chip]'));
            });
        });
    });

    const closeModalById = (modalId) => {
        const modal = document.getElementById(modalId);
        if (!modal) {
            return;
        }
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        if (modalId === 'asset-modal' && window.location.search.includes('edit_asset=')) {
            window.location.href = 'index.php?page=board';
        }
        if (modalId === 'asset-history-modal' && window.location.search.includes('asset_history=')) {
            window.location.href = 'index.php?page=board';
        }
        if (modalId === 'field-help-modal') {
            const iframe = document.getElementById('field-help-iframe');
            if (iframe) {
                iframe.src = '';
            }
        }
    };

    const loadDownloadModalContent = (modal) => {
        const body = modal ? modal.querySelector('.download-modal-async-body[data-download-modal-url]') : null;
        if (!body || body.getAttribute('data-download-loaded') === '1' || body.getAttribute('data-download-loading') === '1') {
            return;
        }
        const url = body.getAttribute('data-download-modal-url') || '';
        if (!url) {
            return;
        }
        body.setAttribute('data-download-loading', '1');
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.text();
            })
            .then((html) => {
                const temp = document.createElement('div');
                temp.innerHTML = html;
                const bodyFragment = temp.querySelector('[data-download-fragment-body]');
                const commonFragment = temp.querySelector('[data-download-fragment-common]');
                if (bodyFragment) {
                    body.innerHTML = bodyFragment.innerHTML;
                    body.setAttribute('data-download-loaded', '1');
                } else {
                    body.innerHTML = '<p class="error-text">Failed to load download options.</p>';
                }
                if (typeof window.initializeDownloadModalUi === 'function') {
                    window.initializeDownloadModalUi();
                }
            })
            .catch(() => {
                body.innerHTML = '<p class="error-text">Failed to load download options.</p>';
            })
            .finally(() => {
                body.removeAttribute('data-download-loading');
            });
    };

    document.querySelectorAll('[data-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (!modal) {
                return;
            }
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            if (modalId === 'download-data-modal') {
                loadDownloadModalContent(modal);
            }
        });
    });

    document.querySelectorAll('[data-close]').forEach((button) => {
        button.addEventListener('click', () => {
            closeModalById(button.getAttribute('data-close'));
        });
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-close]');
        if (!button) {
            return;
        }
        event.preventDefault();
        closeModalById(button.getAttribute('data-close'));
    });

    document.querySelectorAll('[data-field-help]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const modal = document.getElementById('field-help-modal');
            if (!modal) {
                return;
            }
            const title = document.getElementById('field-help-title');
            const label = document.getElementById('field-help-label');
            const body = document.getElementById('field-help-body');
            const videoBlock = document.getElementById('field-help-video');
            const iframe = document.getElementById('field-help-iframe');
            const link = document.getElementById('field-help-link');
            const helpLabel = button.getAttribute('data-help-label') || 'Field';
            const information = button.getAttribute('data-help-information') || '';
            const tutorialUrl = button.getAttribute('data-help-url') || '';
            const embedUrl = button.getAttribute('data-help-embed-url') || '';
            if (title) {
                title.textContent = 'Field Information';
            }
            if (label) {
                label.textContent = helpLabel;
            }
            if (body) {
                body.innerHTML = renderHelpInformationHtml(information);
            }
            if (videoBlock && iframe && link) {
                if (tutorialUrl) {
                    videoBlock.classList.remove('hidden');
                    iframe.src = embedUrl || '';
                    link.href = tutorialUrl;
                } else {
                    videoBlock.classList.add('hidden');
                    iframe.src = '';
                    link.href = '#';
                }
            }
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    document.querySelectorAll('[data-number-rule-help]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const modal = document.getElementById('number-rule-help-modal');
            if (!modal) {
                return;
            }
            const title = document.getElementById('number-rule-help-title');
            const body = document.getElementById('number-rule-help-body');
            let lines = [];
            try {
                lines = JSON.parse(button.getAttribute('data-help-lines') || '[]');
            } catch (error) {
                lines = [];
            }
            if (title) {
                title.textContent = button.getAttribute('data-help-title') || 'Number Format Rules';
            }
            if (body) {
                const safeLines = Array.isArray(lines) ? lines : [];
                body.innerHTML = safeLines.length
                    ? safeLines.map((line) => `<p>${String(line).replace(/[&<>"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char]))}</p>`).join('')
                    : '<p>No rules available.</p>';
            }
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    const bindCharCounter = (input) => {
        const limit = Number.parseInt(input.getAttribute('data-char-limit') || '0', 10);
        if (!limit || Number.isNaN(limit) || limit <= 0) {
            return;
        }
        const target = input.parentElement ? input.parentElement.querySelector('[data-char-count-target]') : null;
        if (!target) {
            return;
        }
        const update = () => {
            const length = String(input.value || '').length;
            target.textContent = `${length}/${limit} characters`;
        };
        input.addEventListener('input', update);
        update();
    };

    document.querySelectorAll('[data-char-limit]').forEach((input) => {
        bindCharCounter(input);
    });

    document.querySelectorAll('[data-show-all-columns]').forEach((button) => {
        button.addEventListener('click', () => {
            const formId = button.getAttribute('data-show-all-columns');
            const form = formId ? document.getElementById(formId) : null;
            if (!form) {
                return;
            }
            form.querySelectorAll('input[type="checkbox"][name="visible_columns[]"]').forEach((input) => {
                input.checked = true;
            });
        });
    });

    const assetDeleteModal = document.getElementById('asset-delete-confirm-modal');
    const assetDeleteForm = document.getElementById('asset-delete-form');
    const assetDeleteProceed = document.getElementById('asset-delete-confirm-proceed');
    if (assetDeleteModal && assetDeleteForm && assetDeleteProceed) {
        assetDeleteProceed.addEventListener('click', () => {
            const selectedRows = Array.from(document.getElementsByName('asset_ids[]')).filter((input) => {
                if (!(input instanceof HTMLInputElement)) {
                    return false;
                }
                const linkedForm = input.form;
                return !!linkedForm && linkedForm.id === assetDeleteForm.id && input.checked;
            });
            if (!selectedRows.length) {
                window.alert('Please select at least one row before deletion.');
                return;
            }
            assetDeleteModal.classList.remove('open');
            assetDeleteModal.setAttribute('aria-hidden', 'true');
            assetDeleteForm.submit();
        });
    }

    const renumberManagedUserRows = (container) => {
        const body = container?.querySelector('[data-managed-user-body]');
        if (!body) {
            return;
        }
        body.querySelectorAll('tr').forEach((row, index) => {
            const slCell = row.querySelector('[data-managed-user-sl]');
            if (slCell) {
                slCell.textContent = String(index + 1);
            }
        });
    };

    document.querySelectorAll('[data-add-managed-user-row]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-add-managed-user-row');
            const container = document.getElementById(targetId) || button.closest('.modal-card') || button.closest('.card');
            if (!container) {
                return;
            }
            const template = container.querySelector('template[data-managed-user-template]');
            const body = container.querySelector('[data-managed-user-body]');
            if (!template || !body) {
                return;
            }
            const formId = `managed-user-${Date.now()}-${Math.random().toString(16).slice(2)}`;
            const html = template.innerHTML.replaceAll('__FORM_ID__', formId);
            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = html.trim();
            const row = wrapper.firstElementChild;
            if (!row) {
                return;
            }
            body.appendChild(row);
            renumberManagedUserRows(container);
        });
    });

    document.querySelectorAll('.select-all').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            const table = checkbox.closest('table');
            if (!table) {
                return;
            }
            table.querySelectorAll('tbody input[type="checkbox"]').forEach((input) => {
                input.checked = checkbox.checked;
            });
        });
    });

    document.querySelectorAll('[data-inline-file-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const files = Array.from(input.files || []);
            if (!files.length) {
                return;
            }
            const label = input.getAttribute('data-label') || 'File';
            const allowed = (input.getAttribute('data-allowed-extensions') || '')
                .split(',')
                .map((item) => item.trim().toLowerCase())
                .filter(Boolean);
            const maxFiles = Number.parseInt(input.getAttribute('data-max-files') || '0', 10);
            const maxFileSize = Number.parseInt(input.getAttribute('data-max-file-size') || '0', 10);
            const maxTotalSize = Number.parseInt(input.getAttribute('data-max-total-size') || '0', 10);
            const isMultiple = input.getAttribute('data-is-multiple') === '1';
            const errors = [];

            if (!isMultiple && files.length > 1) {
                errors.push(`${label} allows only one file.`);
            }
            if (maxFiles > 0 && files.length > maxFiles) {
                errors.push(`${label} exceeds the maximum number of files.`);
            }

            let totalSize = 0;
            files.forEach((file) => {
                totalSize += Number(file.size || 0);
                const ext = (file.name.split('.').pop() || '').trim().toLowerCase();
                if (!ext || (allowed.length && !allowed.includes(ext))) {
                    errors.push(`${file.name}: unsupported file type.`);
                }
                if (maxFileSize > 0 && Number(file.size || 0) > maxFileSize) {
                    errors.push(`${file.name}: exceeds the per-file size limit.`);
                }
            });

            if (maxTotalSize > 0 && totalSize > maxTotalSize) {
                errors.push(`${label} exceeds the total upload size limit.`);
            }

            if (errors.length) {
                window.alert(errors.join('\n'));
                input.value = '';
                return;
            }

            const form = input.closest('form');
            if (form) {
                form.submit();
            }
        });
    });

    const syncFieldTypeSections = (container) => {
        const typeSelect = container?.querySelector('[data-field-type-select]');
        if (!typeSelect) {
            return;
        }
        const currentType = typeSelect.value;
        container.querySelectorAll('[data-field-config]').forEach((section) => {
            section.classList.toggle('hidden', section.getAttribute('data-field-config') !== currentType);
        });
    };

    document.querySelectorAll('[data-asset-field-form], tr').forEach((container) => {
        if (!container.querySelector('[data-field-type-select]')) {
            return;
        }
        syncFieldTypeSections(container);
        container.querySelector('[data-field-type-select]').addEventListener('change', () => {
            syncFieldTypeSections(container);
        });
    });

    const filterSubcategories = (categorySelect, subcategorySelect) => {
        if (!categorySelect || !subcategorySelect) {
            return;
        }
        const categoryId = categorySelect.value;
        let hasSelectedVisible = false;
        subcategorySelect.querySelectorAll('option').forEach((option) => {
            if (option.value === '') {
                option.hidden = false;
                return;
            }
            const matches = categoryId === '' || option.getAttribute('data-category') === categoryId;
            option.hidden = !matches;
            if (matches && option.selected) {
                hasSelectedVisible = true;
            }
        });
        if (!hasSelectedVisible) {
            subcategorySelect.value = '';
        }
    };

    const assetCategorySelect = document.getElementById('asset-category-select');
    const assetSubcategorySelect = document.getElementById('asset-subcategory-select');
    if (assetCategorySelect && assetSubcategorySelect) {
        filterSubcategories(assetCategorySelect, assetSubcategorySelect);
        assetCategorySelect.addEventListener('change', () => filterSubcategories(assetCategorySelect, assetSubcategorySelect));
    }

    const syncConditionalSelects = (container) => {
        container.querySelectorAll('[data-conditional-primary="1"]').forEach((primarySelect) => {
            const childKey = primarySelect.getAttribute('data-conditional-child') || '';
            if (!childKey) {
                return;
            }
            const childSelect = container.querySelector(`[data-field-key="${childKey}"][data-conditional-secondary], [data-field-name="fields[${childKey}]"][data-conditional-secondary]`);
            if (!childSelect) {
                return;
            }
            let map = {};
            try {
                map = JSON.parse(primarySelect.getAttribute('data-conditional-map') || '{}');
            } catch (error) {
                map = {};
            }
            const previousValue = childSelect.value || '';
            const allowed = Array.isArray(map[primarySelect.value]) ? map[primarySelect.value] : [];
            childSelect.innerHTML = '<option value="">Select</option>';
            allowed.forEach((optionValue) => {
                const option = document.createElement('option');
                option.value = optionValue;
                option.textContent = optionValue;
                childSelect.appendChild(option);
            });
            childSelect.value = allowed.includes(previousValue) ? previousValue : '';
        });
    };

    const assetModal = document.getElementById('asset-modal');
    if (assetModal) {
        syncConditionalSelects(assetModal);
        assetModal.querySelectorAll('[data-conditional-primary="1"]').forEach((primarySelect) => {
            primarySelect.addEventListener('change', () => syncConditionalSelects(assetModal));
        });
    }

    const bimhMetaNode = document.getElementById('bimh-picker-meta');
    let bimhMeta = { lookup_url: 'index.php?page=bimh_lookup', scope: {}, rows: [] };
    if (bimhMetaNode) {
        try {
            bimhMeta = JSON.parse(bimhMetaNode.textContent || '{}');
        } catch (error) {
            bimhMeta = { lookup_url: 'index.php?page=bimh_lookup', scope: {}, rows: [] };
        }
    }
    const bimhLookupCache = new Map();
    const bimhPickerModal = document.getElementById('bimh-picker-modal');
    const bimhPickerSearch = document.getElementById('bimh-picker-search');
    const bimhPickerCircle = document.getElementById('bimh-picker-circle');
    const bimhPickerDivision = document.getElementById('bimh-picker-division');
    const bimhPickerBody = document.getElementById('bimh-picker-body');
    const bimhPickerRows = Array.isArray(bimhMeta.rows) ? bimhMeta.rows : [];
    let activeBimhField = null;

    const normalizeBimhSearchText = (value) => String(value || '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();
    const normalizeBimhCompactText = (value) => String(value || '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '');

    const bimhFieldSuggestions = (query) => {
        const normalized = normalizeBimhSearchText(query);
        const normalizedCompact = normalizeBimhCompactText(query);
        const rows = !normalized
            ? bimhPickerRows
            : bimhPickerRows.filter((row) => {
                const idText = normalizeBimhSearchText(row.bimh_id || '');
                const estText = normalizeBimhSearchText(row.est_name || '');
                const idCompact = normalizeBimhCompactText(row.bimh_id || '');
                const estCompact = normalizeBimhCompactText(row.est_name || '');
                return idText.includes(normalized)
                    || estText.includes(normalized)
                    || (normalizedCompact !== '' && (idCompact.includes(normalizedCompact) || estCompact.includes(normalizedCompact)));
            });
        return rows;
    };

    const setBimhEstName = (fieldBox, value) => {
        const target = fieldBox?.querySelector('[data-bimh-est-name]');
        if (target) {
            const text = String(value || '').trim();
            target.textContent = text;
            const isNotFound = text === 'BIMH ID is not in the Database.';
            target.classList.toggle('is-not-found', isNotFound);
        }
    };

    const positionBimhSuggestions = (fieldBox) => {
        const menu = fieldBox?.querySelector('[data-bimh-suggestions]');
        const input = fieldBox?.querySelector('[data-bimh-id-input]');
        if (!menu || !input || menu.hidden) {
            return;
        }
        menu.style.top = '';
        menu.style.bottom = '';
        menu.style.left = '';
        menu.style.width = '';
        const inputRect = input.getBoundingClientRect();
        const fieldRect = fieldBox.getBoundingClientRect();
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
        const estimatedHeight = Math.min(8, Math.max(1, menu.childElementCount)) * 44 + 16;
        const spaceBelow = viewportHeight - inputRect.bottom;
        const openUpward = spaceBelow < Math.min(estimatedHeight, 320) && inputRect.top > spaceBelow;
        menu.style.left = `${Math.round(inputRect.left - fieldRect.left)}px`;
        menu.style.width = `${Math.round(inputRect.width)}px`;
        if (openUpward) {
            menu.style.bottom = `${Math.round(fieldRect.bottom - inputRect.top + 6)}px`;
        } else {
            menu.style.top = `${Math.round(inputRect.bottom - fieldRect.top + 6)}px`;
        }
    };

    const hideBimhSuggestions = (fieldBox) => {
        const menu = fieldBox?.querySelector('[data-bimh-suggestions]');
        if (!menu) {
            return;
        }
        menu.hidden = true;
        menu.innerHTML = '';
        fieldBox.classList.remove('is-suggestion-open');
    };

    const applyBimhSuggestion = (fieldBox, row) => {
        const input = fieldBox?.querySelector('[data-bimh-id-input]');
        if (!input || !row) {
            return;
        }
        input.value = String(row.bimh_id || '');
        setBimhEstName(fieldBox, row.est_name || '');
        hideBimhSuggestions(fieldBox);
    };

    const showBimhSuggestions = (fieldBox, query = '') => {
        const menu = fieldBox?.querySelector('[data-bimh-suggestions]');
        if (!menu) {
            return;
        }
        const rows = bimhFieldSuggestions(query);
        if (!rows.length) {
            hideBimhSuggestions(fieldBox);
            return;
        }
        menu.innerHTML = rows.map((row, index) => `
            <button type="button" class="bimh-suggestion-option" data-bimh-suggestion-index="${index}">
                <span class="bimh-suggestion-id">${escapeHtml(row.bimh_id || '')}</span>
                <span class="bimh-suggestion-name">${escapeHtml(row.est_name || '')}</span>
            </button>
        `).join('');
        menu.hidden = false;
        fieldBox.classList.add('is-suggestion-open');
        positionBimhSuggestions(fieldBox);
        Array.from(menu.querySelectorAll('[data-bimh-suggestion-index]')).forEach((button) => {
            button.addEventListener('mousedown', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const row = rows[Number.parseInt(button.getAttribute('data-bimh-suggestion-index') || '-1', 10)];
                applyBimhSuggestion(fieldBox, row);
                const input = fieldBox.querySelector('[data-bimh-id-input]');
                if (input) {
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        });
    };

    const fetchBimhLookup = async (bimhId) => {
        const normalized = String(bimhId || '').trim();
        if (!normalized) {
            return { est_name: '' };
        }
        if (bimhLookupCache.has(normalized)) {
            return bimhLookupCache.get(normalized);
        }
        const response = await fetch(`${bimhMeta.lookup_url || 'index.php?page=bimh_lookup'}&bimh_id=${encodeURIComponent(normalized)}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) {
            return { est_name: 'Connection error.' };
        }
        const payload = await response.json();
        bimhLookupCache.set(normalized, payload || { est_name: 'BIMH ID is not in the Database.' });
        return payload || { est_name: 'BIMH ID is not in the Database.' };
    };

    const runBimhLookup = async (fieldBox) => {
        const input = fieldBox?.querySelector('[data-bimh-id-input]');
        if (!input) {
            return;
        }
        const requestedValue = String(input.value || '').trim();
        if (!requestedValue) {
            setBimhEstName(fieldBox, '');
            return;
        }
        setBimhEstName(fieldBox, 'Checking...');
        const result = await fetchBimhLookup(requestedValue);
        if (String(input.value || '').trim() !== requestedValue) {
            return;
        }
        setBimhEstName(fieldBox, result.est_name || 'BIMH ID is not in the Database.');
    };

    const bindBimhField = (fieldBox, autoLookup = true) => {
        if (!fieldBox || fieldBox.dataset.bimhBound === '1') {
            if (autoLookup && fieldBox) {
                const input = fieldBox.querySelector('[data-bimh-id-input]');
                const estTarget = fieldBox.querySelector('[data-bimh-est-name]');
                if (input && estTarget && String(input.value || '').trim() !== '' && String(estTarget.textContent || '').trim() === '') {
                    runBimhLookup(fieldBox);
                }
            }
            return;
        }
        fieldBox.dataset.bimhBound = '1';
        const input = fieldBox.querySelector('[data-bimh-id-input]');
        const pickerButton = fieldBox.querySelector('[data-bimh-picker-open]');
        if (pickerButton && bimhPickerModal) {
            pickerButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                activeBimhField = fieldBox;
                hideBimhSuggestions(fieldBox);
                if (bimhPickerSearch) {
                    bimhPickerSearch.value = '';
                }
                if (bimhPickerCircle) {
                    bimhPickerCircle.value = '';
                }
                syncBimhDivisionOptions();
                if (bimhPickerDivision) {
                    bimhPickerDivision.value = '';
                }
                bimhPickerModal.classList.add('open');
                bimhPickerModal.setAttribute('aria-hidden', 'false');
                if (bimhPickerSearch) {
                    bimhPickerSearch.focus();
                }
                renderBimhPickerRows();
            });
        }
        if (input) {
            input.addEventListener('input', () => {
                const currentValue = String(input.value || '');
                if (currentValue.trim() === '') {
                    hideBimhSuggestions(fieldBox);
                    return;
                }
                showBimhSuggestions(fieldBox, currentValue);
            });
            input.addEventListener('change', () => runBimhLookup(fieldBox));
            input.addEventListener('blur', () => {
                window.setTimeout(() => hideBimhSuggestions(fieldBox), 120);
                runBimhLookup(fieldBox);
            });
        }
        if (autoLookup && input && String(input.value || '').trim() !== '') {
            runBimhLookup(fieldBox);
        }
    };

    const fillSelectOptions = (select, options, placeholder = 'All') => {
        if (!select) {
            return;
        }
        const previous = select.value;
        select.innerHTML = '';
        const blank = document.createElement('option');
        blank.value = '';
        blank.textContent = placeholder;
        select.appendChild(blank);
        options.forEach((item) => {
            const option = document.createElement('option');
            option.value = String(item.value || '');
            option.textContent = String(item.label || '');
            select.appendChild(option);
        });
        select.value = options.some((item) => String(item.value || '') === previous) ? previous : '';
    };

    const buildBimhFilterOptions = () => {
        const circleMap = new Map();
        const divisionMap = new Map();
        bimhPickerRows.forEach((row) => {
            const circleId = String(row.circle_id || '');
            const divisionId = String(row.division_id || '');
            if (circleId && !circleMap.has(circleId)) {
                circleMap.set(circleId, { value: circleId, label: row.circle_name || circleId });
            }
            if (divisionId && !divisionMap.has(divisionId)) {
                divisionMap.set(divisionId, { value: divisionId, label: row.division_name || divisionId, circle_id: circleId });
            }
        });
        fillSelectOptions(bimhPickerCircle, Array.from(circleMap.values()).sort((a, b) => String(a.label).localeCompare(String(b.label))));
        fillSelectOptions(bimhPickerDivision, Array.from(divisionMap.values()).sort((a, b) => String(a.label).localeCompare(String(b.label))));
    };

    const syncBimhDivisionOptions = () => {
        if (!bimhPickerDivision) {
            return;
        }
        const selectedCircle = String(bimhPickerCircle?.value || '');
        const divisionMap = new Map();
        bimhPickerRows.forEach((row) => {
            const divisionId = String(row.division_id || '');
            const circleId = String(row.circle_id || '');
            if (!divisionId) {
                return;
            }
            if (selectedCircle && circleId !== selectedCircle) {
                return;
            }
            if (!divisionMap.has(divisionId)) {
                divisionMap.set(divisionId, { value: divisionId, label: row.division_name || divisionId });
            }
        });
        fillSelectOptions(bimhPickerDivision, Array.from(divisionMap.values()).sort((a, b) => String(a.label).localeCompare(String(b.label))));
    };

    const getFilteredBimhRows = () => {
        const search = String(bimhPickerSearch?.value || '').trim().toLowerCase();
        const selectedCircle = String(bimhPickerCircle?.value || '');
        const selectedDivision = String(bimhPickerDivision?.value || '');
        return bimhPickerRows.filter((row) => {
            if (selectedCircle && String(row.circle_id || '') !== selectedCircle) {
                return false;
            }
            if (selectedDivision && String(row.division_id || '') !== selectedDivision) {
                return false;
            }
            if (!search) {
                return true;
            }
            const haystack = `${row.bimh_id || ''} ${row.est_name || ''} ${row.upazila_thana || ''} ${row.district || ''} ${row.circle_name || ''} ${row.division_name || ''}`.toLowerCase();
            return haystack.includes(search);
        });
    };

    function renderBimhPickerRows() {
        if (!bimhPickerBody) {
            return;
        }
        const filteredRows = getFilteredBimhRows();
        const showCircle = !!bimhMeta.scope?.show_circle_filter;
        const showDivision = !!bimhMeta.scope?.show_division_filter;
        const colspan = 5 + (showCircle ? 1 : 0) + (showDivision ? 1 : 0);
        if (!filteredRows.length) {
            bimhPickerBody.innerHTML = `<tr><td colspan="${colspan}" class="muted">No establishment found.</td></tr>`;
            return;
        }
        bimhPickerBody.innerHTML = filteredRows.map((row, index) => {
            const cells = [
                `<td>${escapeHtml(row.bimh_id || '')}</td>`,
                `<td>${escapeHtml(row.est_name || '')}</td>`,
                `<td>${escapeHtml(row.upazila_thana || '')}</td>`,
                `<td>${escapeHtml(row.district || '')}</td>`,
            ];
            if (showCircle) {
                cells.push(`<td>${escapeHtml(row.circle_name || '')}</td>`);
            }
            if (showDivision) {
                cells.push(`<td>${escapeHtml(row.division_name || '')}</td>`);
            }
            cells.push(`<td><button type="button" class="btn-small" data-bimh-select-row="${index}">Select</button></td>`);
            return `<tr>${cells.join('')}</tr>`;
        }).join('');
        Array.from(bimhPickerBody.querySelectorAll('[data-bimh-select-row]')).forEach((button) => {
            button.addEventListener('click', () => {
                const row = filteredRows[Number.parseInt(button.getAttribute('data-bimh-select-row') || '-1', 10)];
                if (!row || !activeBimhField) {
                    return;
                }
                const input = activeBimhField.querySelector('[data-bimh-id-input]');
                if (input) {
                    input.value = String(row.bimh_id || '');
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
                setBimhEstName(activeBimhField, row.est_name || '');
                if (bimhPickerModal) {
                    bimhPickerModal.classList.remove('open');
                    bimhPickerModal.setAttribute('aria-hidden', 'true');
                }
            });
        });
    }

    buildBimhFilterOptions();
    syncBimhDivisionOptions();
    if (bimhPickerSearch) {
        bimhPickerSearch.addEventListener('input', renderBimhPickerRows);
    }
    if (bimhPickerCircle) {
        bimhPickerCircle.addEventListener('change', () => {
            syncBimhDivisionOptions();
            renderBimhPickerRows();
        });
    }
    if (bimhPickerDivision) {
        bimhPickerDivision.addEventListener('change', renderBimhPickerRows);
    }
    document.querySelectorAll('[data-bimh-field]').forEach((fieldBox) => bindBimhField(fieldBox, true));
    window.addEventListener('resize', () => {
        document.querySelectorAll('.bimh-field.is-suggestion-open').forEach((fieldBox) => positionBimhSuggestions(fieldBox));
    });
    window.addEventListener('scroll', () => {
        document.querySelectorAll('.bimh-field.is-suggestion-open').forEach((fieldBox) => positionBimhSuggestions(fieldBox));
    }, { passive: true });

    const filterCategory = document.querySelector('#asset-filters select[name="category_id"]');
    const filterSubcategory = document.querySelector('#asset-filters select[name="subcategory_id"]');
    if (filterCategory && filterSubcategory) {
        filterSubcategories(filterCategory, filterSubcategory);
        filterCategory.addEventListener('change', () => filterSubcategories(filterCategory, filterSubcategory));
        filterSubcategory.addEventListener('change', () => {
            if (filterSubcategory.value !== '0' && filterSubcategory.selectedOptions[0]) {
                const categoryId = filterSubcategory.selectedOptions[0].getAttribute('data-category') || '0';
                if (categoryId !== '0') {
                    filterCategory.value = categoryId;
                    filterSubcategories(filterCategory, filterSubcategory);
                }
            }
        });
    }

    const downloadCategory = document.getElementById('download-category-select');
    const downloadSubcategory = document.getElementById('download-subcategory-select');
    if (downloadCategory && downloadSubcategory) {
        filterSubcategories(downloadCategory, downloadSubcategory);
        downloadCategory.addEventListener('change', () => filterSubcategories(downloadCategory, downloadSubcategory));
    }

    const assetFilters = document.getElementById('asset-filters');
    if (assetFilters) {
        const filterPickers = Array.from(assetFilters.querySelectorAll('[data-filter-picker]'));
        const getPickerValueInput = (picker) => picker?.querySelector('[data-filter-picker-value]');
        const getPickerTextInput = (picker) => picker?.querySelector('[data-filter-picker-input]');
        const getPickerMenu = (picker) => picker?.querySelector('[data-filter-picker-menu]');
        const findPickerByName = (name) => assetFilters.querySelector(`[data-filter-picker] [name="${name}"]`)?.closest('[data-filter-picker]');
        const pickerOptionSources = new Map();
        const getPickerSourceOptions = (picker) => pickerOptionSources.get(picker) || [];
        const getPickerOptions = (picker) => Array.from(picker?.querySelectorAll('.filter-picker-option') || []);
        const buildOptionSourceFromDom = (picker) => {
            const source = [];
            getPickerOptions(picker).forEach((option) => {
                const value = option.getAttribute('data-option-value') || '';
                if (value === '') {
                    return;
                }
                const meta = {};
                Array.from(option.attributes).forEach((attribute) => {
                    if (!attribute.name.startsWith('data-')) {
                        return;
                    }
                    if (attribute.name === 'data-option-value' || attribute.name === 'data-option-label') {
                        return;
                    }
                    meta[attribute.name.slice(5)] = attribute.value;
                });
                source.push({
                    value,
                    label: option.getAttribute('data-option-label') || value,
                    meta,
                    disabled: option.getAttribute('data-option-disabled') === '1',
                });
            });
            pickerOptionSources.set(picker, source);
            return source;
        };
        const setPickerSourceOptions = (picker, options) => {
            pickerOptionSources.set(picker, options);
        };
        const setPickerActiveState = (picker) => {
            if (!picker) {
                return;
            }
            const value = getPickerValueInput(picker)?.value || '';
            picker.classList.toggle('is-selected', value !== '' && value !== '0');
        };
        const setPickerSelection = (picker, value, label = null) => {
            const valueInput = getPickerValueInput(picker);
            const textInput = getPickerTextInput(picker);
            if (!valueInput || !textInput) {
                return;
            }
            const sourceMatch = getPickerSourceOptions(picker).find((option) => option.value === String(value));
            valueInput.value = String(value ?? '');
            textInput.value = label ?? (sourceMatch ? sourceMatch.label : '');
            setPickerActiveState(picker);
        };
        const getPickerValue = (picker) => getPickerValueInput(picker)?.value || '';
        const renderPickerMenu = (picker, query = '', matcher = null) => {
            const menu = getPickerMenu(picker);
            if (!menu) {
                return [];
            }
            const normalizedQuery = String(query || '').trim().toLowerCase();
            const visible = [];
            menu.innerHTML = '';
            const allOption = document.createElement('button');
            allOption.type = 'button';
            allOption.className = 'filter-picker-option';
            allOption.setAttribute('data-option-value', '');
            allOption.setAttribute('data-option-label', 'All');
            allOption.textContent = 'All';
            allOption.addEventListener('click', () => {
                setPickerSelection(picker, '', '');
                closePicker(picker);
                getPickerTextInput(picker)?.dispatchEvent(new Event('change', { bubbles: true }));
            });
            menu.appendChild(allOption);
            getPickerSourceOptions(picker).forEach((option) => {
                const label = option.label.toLowerCase();
                const queryMatch = normalizedQuery === '' || label.includes(normalizedQuery);
                const dependencyMatch = matcher ? matcher(option) : true;
                if (queryMatch && dependencyMatch) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = `filter-picker-option${option.disabled ? ' is-disabled' : ''}`;
                    button.setAttribute('data-option-value', option.value);
                    button.setAttribute('data-option-label', option.label);
                    if (option.disabled) {
                        button.setAttribute('data-option-disabled', '1');
                        button.setAttribute('title', 'Not present in the table.');
                        button.setAttribute('aria-disabled', 'true');
                    }
                    Object.entries(option.meta || {}).forEach(([metaKey, metaValue]) => {
                        button.setAttribute(`data-${metaKey}`, metaValue);
                    });
                    button.textContent = option.label;
                    button.addEventListener('click', () => {
                        if (option.disabled) {
                            return;
                        }
                        setPickerSelection(picker, option.value, option.label);
                        closePicker(picker);
                        getPickerTextInput(picker)?.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                    menu.appendChild(button);
                    visible.push(button);
                }
            });
            return visible;
        };
        const openPicker = (picker) => picker?.classList.add('is-open');
        const closePicker = (picker) => picker?.classList.remove('is-open');

        filterPickers.forEach((picker) => {
            buildOptionSourceFromDom(picker);
            const textInput = getPickerTextInput(picker);
            const valueInput = getPickerValueInput(picker);
            const menu = getPickerMenu(picker);
            if (!textInput || !valueInput || !menu) {
                return;
            }
            textInput.addEventListener('focus', () => {
                renderPickerMenu(picker, '', getPickerMatcher(picker));
                openPicker(picker);
            });
            textInput.addEventListener('input', () => {
                valueInput.value = '';
                setPickerActiveState(picker);
                renderPickerMenu(picker, textInput.value, getPickerMatcher(picker));
                openPicker(picker);
            });
            setPickerActiveState(picker);
        });

        const categoryPicker = findPickerByName('category_id');
        const subcategoryPicker = findPickerByName('subcategory_id');
        const zonePicker = findPickerByName('zone_id');
        const circlePicker = findPickerByName('circle_id');
        const divisionPicker = findPickerByName('division_id');
        const subdivisionPicker = findPickerByName('subdivision_id');

        const getPickerMatcher = (picker) => {
            if (!picker) {
                return null;
            }
            if (picker === subcategoryPicker) {
                const selectedCategory = getPickerValue(categoryPicker);
                return (option) => selectedCategory === '' || selectedCategory === '0' || (option.meta?.category || '') === selectedCategory;
            }
            if (picker === circlePicker) {
                const currentZone = getPickerValue(zonePicker);
                return (option) => currentZone === '' || currentZone === '0' || (option.meta?.zone || '') === currentZone;
            }
            if (picker === divisionPicker) {
                const currentZone = getPickerValue(zonePicker);
                const currentCircle = getPickerValue(circlePicker);
                return (option) => {
                    const zoneMatch = currentZone === '' || currentZone === '0' || (option.meta?.zone || '') === currentZone;
                    const circleMatch = currentCircle === '' || currentCircle === '0' || (option.meta?.circle || '') === currentCircle;
                    return zoneMatch && circleMatch;
                };
            }
            if (picker === subdivisionPicker) {
                const currentZone = getPickerValue(zonePicker);
                const currentCircle = getPickerValue(circlePicker);
                const currentDivision = getPickerValue(divisionPicker);
                return (option) => {
                    const zoneMatch = currentZone === '' || currentZone === '0' || (option.meta?.zone || '') === currentZone;
                    const circleMatch = currentCircle === '' || currentCircle === '0' || (option.meta?.circle || '') === currentCircle;
                    const divisionMatch = currentDivision === '' || currentDivision === '0' || (option.meta?.division || '') === currentDivision;
                    return zoneMatch && circleMatch && divisionMatch;
                };
            }
            if (picker?.getAttribute('data-filter-conditional-secondary')) {
                const primaryKey = picker.getAttribute('data-filter-conditional-secondary') || '';
                const primaryPicker = findPickerByName(`field_filter_${primaryKey}`);
                const selectedPrimary = getPickerValue(primaryPicker);
                if (selectedPrimary === '' || selectedPrimary === '0') {
                    return null;
                }
                return (option) => (option.meta?.primary || '') === selectedPrimary;
            }
            return null;
        };

        document.addEventListener('click', (event) => {
            filterPickers.forEach((picker) => {
                if (!picker.contains(event.target)) {
                    closePicker(picker);
                }
            });
        });
        const syncSubcategoryPicker = (source = null) => {
            if (!subcategoryPicker) {
                return;
            }
            if (source === subcategoryPicker && getPickerValue(subcategoryPicker) !== '') {
                const selected = getPickerSourceOptions(subcategoryPicker).find((option) => option.value === getPickerValue(subcategoryPicker));
                if (selected && categoryPicker) {
                    const selectedCategory = selected.meta?.category || '';
                    const categoryOption = getPickerSourceOptions(categoryPicker).find((option) => option.value === selectedCategory);
                    setPickerSelection(categoryPicker, selectedCategory, categoryOption?.label || '');
                }
            }
            const visible = renderPickerMenu(subcategoryPicker, '', getPickerMatcher(subcategoryPicker));
            const currentValue = getPickerValue(subcategoryPicker);
            const stillVisible = visible.some((option) => option.getAttribute('data-option-value') === currentValue);
            if (!stillVisible) {
                const enabledVisible = visible.filter((option) => option.getAttribute('data-option-disabled') !== '1');
                if (enabledVisible.length === 1) {
                    setPickerSelection(subcategoryPicker, enabledVisible[0].getAttribute('data-option-value') || '', enabledVisible[0].getAttribute('data-option-label') || '');
                } else {
                    setPickerSelection(subcategoryPicker, '', '');
                }
            }
        };

        const syncOfficeFilters = (source = null) => {
            if (source === circlePicker && getPickerValue(circlePicker) !== '') {
                const selected = getPickerSourceOptions(circlePicker).find((option) => option.value === getPickerValue(circlePicker));
                if (zonePicker && selected) {
                    const zoneValue = selected.meta?.zone || '';
                    const zoneOption = getPickerSourceOptions(zonePicker).find((option) => option.value === zoneValue);
                    setPickerSelection(zonePicker, zoneValue, zoneOption?.label || '');
                }
            }
            if (source === divisionPicker && getPickerValue(divisionPicker) !== '') {
                const selected = getPickerSourceOptions(divisionPicker).find((option) => option.value === getPickerValue(divisionPicker));
                if (zonePicker && selected) {
                    const zoneValue = selected.meta?.zone || '';
                    const zoneOption = getPickerSourceOptions(zonePicker).find((option) => option.value === zoneValue);
                    setPickerSelection(zonePicker, zoneValue, zoneOption?.label || '');
                }
                if (circlePicker && selected) {
                    const circleValue = selected.meta?.circle || '';
                    const circleOption = getPickerSourceOptions(circlePicker).find((option) => option.value === circleValue);
                    setPickerSelection(circlePicker, circleValue, circleOption?.label || '');
                }
            }
            if (source === subdivisionPicker && getPickerValue(subdivisionPicker) !== '') {
                const selected = getPickerSourceOptions(subdivisionPicker).find((option) => option.value === getPickerValue(subdivisionPicker));
                if (zonePicker && selected) {
                    const zoneValue = selected.meta?.zone || '';
                    const zoneOption = getPickerSourceOptions(zonePicker).find((option) => option.value === zoneValue);
                    setPickerSelection(zonePicker, zoneValue, zoneOption?.label || '');
                }
                if (circlePicker && selected) {
                    const circleValue = selected.meta?.circle || '';
                    const circleOption = getPickerSourceOptions(circlePicker).find((option) => option.value === circleValue);
                    setPickerSelection(circlePicker, circleValue, circleOption?.label || '');
                }
                if (divisionPicker && selected) {
                    const divisionValue = selected.meta?.division || '';
                    const divisionOption = getPickerSourceOptions(divisionPicker).find((option) => option.value === divisionValue);
                    setPickerSelection(divisionPicker, divisionValue, divisionOption?.label || '');
                }
            }

            const syncPicker = (picker, matcher) => {
                if (!picker) {
                    return;
                }
                const visible = renderPickerMenu(picker, '', matcher);
                const currentValue = getPickerValue(picker);
                const stillVisible = visible.some((option) => option.getAttribute('data-option-value') === currentValue);
                if (!stillVisible) {
                    const enabledVisible = visible.filter((option) => option.getAttribute('data-option-disabled') !== '1');
                    if (enabledVisible.length === 1) {
                        setPickerSelection(picker, enabledVisible[0].getAttribute('data-option-value') || '', enabledVisible[0].getAttribute('data-option-label') || '');
                    } else {
                        setPickerSelection(picker, '', '');
                    }
                }
            };

            syncPicker(circlePicker, getPickerMatcher(circlePicker));
            syncPicker(divisionPicker, getPickerMatcher(divisionPicker));
            syncPicker(subdivisionPicker, getPickerMatcher(subdivisionPicker));
        };

        [zonePicker, circlePicker, divisionPicker, subdivisionPicker].forEach((picker) => {
            const textInput = getPickerTextInput(picker);
            if (!textInput) {
                return;
            }
            textInput.addEventListener('change', () => syncOfficeFilters(picker));
        });
        syncOfficeFilters();
        if (categoryPicker) {
            getPickerTextInput(categoryPicker)?.addEventListener('change', () => syncSubcategoryPicker(categoryPicker));
        }
        if (subcategoryPicker) {
            getPickerTextInput(subcategoryPicker)?.addEventListener('change', () => syncSubcategoryPicker(subcategoryPicker));
            syncSubcategoryPicker();
        }

        const syncConditionalFilter = (primarySelect) => {
            const primaryPicker = primarySelect.closest('[data-filter-picker]');
            const childKey = primaryPicker?.getAttribute('data-filter-conditional-child') || '';
            if (!childKey) {
                return;
            }
            const primaryKey = primarySelect.name.replace('field_filter_', '');
            const childPicker = assetFilters.querySelector(`[data-filter-picker][data-filter-conditional-secondary="${primaryKey}"]`);
            if (!childPicker) {
                return;
            }
            const previous = getPickerValue(childPicker) || '';
            const visible = renderPickerMenu(childPicker, '', getPickerMatcher(childPicker));
            const stillVisible = visible.some((option) => option.getAttribute('data-option-value') === previous);
            if (stillVisible) {
                setPickerSelection(childPicker, previous, previous);
            } else {
                const enabledVisible = visible.filter((option) => option.getAttribute('data-option-disabled') !== '1');
                if (enabledVisible.length === 1) {
                    setPickerSelection(childPicker, enabledVisible[0].getAttribute('data-option-value') || '', enabledVisible[0].getAttribute('data-option-label') || '');
                } else if (getPickerValue(primaryPicker) === '' || getPickerValue(primaryPicker) === '0') {
                    setPickerSelection(childPicker, '', '');
                } else {
                    setPickerSelection(childPicker, '', '');
                }
            }
        };

        assetFilters.querySelectorAll('[data-filter-picker][data-filter-conditional-primary="1"]').forEach((primaryPicker) => {
            const primaryInput = getPickerTextInput(primaryPicker);
            const primaryValueInput = getPickerValueInput(primaryPicker);
            if (!primaryInput || !primaryValueInput) {
                return;
            }
            primaryInput.addEventListener('change', () => syncConditionalFilter(primaryValueInput));
            syncConditionalFilter(primaryValueInput);
        });
        assetFilters.querySelectorAll('[data-filter-picker][data-filter-conditional-secondary]').forEach((childPicker) => {
            const childInput = getPickerTextInput(childPicker);
            if (!childInput) {
                return;
            }
            childInput.addEventListener('change', () => {
                const primaryKey = childPicker.getAttribute('data-filter-conditional-secondary') || '';
                const primaryPicker = findPickerByName(`field_filter_${primaryKey}`);
                const primaryValueInput = getPickerValueInput(primaryPicker);
                if (!primaryPicker || !primaryValueInput || getPickerValue(childPicker) === '') {
                    return;
                }
                const selectedChild = getPickerSourceOptions(childPicker).find((option) => option.value === getPickerValue(childPicker));
                if (selectedChild && selectedChild.meta?.primary) {
                    const primaryOption = getPickerSourceOptions(primaryPicker).find((option) => option.value === selectedChild.meta.primary);
                    setPickerSelection(primaryPicker, selectedChild.meta.primary, primaryOption?.label || selectedChild.meta.primary);
                    syncConditionalFilter(primaryValueInput);
                    setPickerSelection(childPicker, getPickerValue(childPicker), getPickerTextInput(childPicker)?.value || getPickerValue(childPicker));
                }
            });
        });

        filterPickers.forEach((picker) => {
            renderPickerMenu(picker, '', getPickerMatcher(picker));
            const currentValue = getPickerValue(picker);
            if (currentValue !== '' && currentValue !== '0') {
                const selected = getPickerSourceOptions(picker).find((option) => option.value === currentValue);
                if (selected) {
                    setPickerSelection(picker, selected.value, selected.label);
                }
            } else {
                setPickerActiveState(picker);
            }
        });
    }

    const downloadZone = document.getElementById('download-zone-select');
    const downloadCircle = document.getElementById('download-circle-select');
    const downloadDivision = document.getElementById('download-division-select');
    const downloadSubdivision = document.getElementById('download-subdivision-select');
    const downloadScopeInput = document.getElementById('download-office-scope');
    const downloadScopeToggle = document.getElementById('download-scope-toggle');
    const downloadResetFilters = document.getElementById('download-reset-filters');
    const downloadLevelFields = document.querySelectorAll('[data-download-level]');
    if (downloadZone && downloadCircle && downloadDivision && downloadScopeInput) {
        const syncDownloadScope = () => {
            const scope = downloadScopeInput.value || 'zone';
            const levels = { zone: 1, circle: 2, division: 3, subdivision: 4 };
            const maxLevel = levels[scope] || 1;

            if (downloadScopeToggle) {
                downloadScopeToggle.querySelectorAll('[data-download-scope]').forEach((button) => {
                    button.classList.toggle('is-active', button.getAttribute('data-download-scope') === scope);
                });
            }

            downloadLevelFields.forEach((field) => {
                const level = field.getAttribute('data-download-level');
                const rank = levels[level] || 1;
                field.classList.toggle('hidden', rank > maxLevel);
            });

            if (scope === 'zone') {
                downloadCircle.value = '0';
                downloadDivision.value = '0';
                if (downloadSubdivision) {
                    downloadSubdivision.value = '0';
                }
            } else if (scope === 'circle') {
                downloadDivision.value = '0';
                if (downloadSubdivision) {
                    downloadSubdivision.value = '0';
                }
            } else if (scope === 'division' && downloadSubdivision) {
                downloadSubdivision.value = '0';
            }
        };

        const filterByOffice = () => {
            const zoneId = downloadZone.value;
            const circleId = downloadCircle.value;

            downloadCircle.querySelectorAll('option').forEach((option) => {
                if (option.value === '0') {
                    option.hidden = false;
                    return;
                }
                option.hidden = !(zoneId === '0' || option.getAttribute('data-zone') === zoneId);
            });

            if (downloadCircle.selectedOptions[0]?.hidden) {
                downloadCircle.value = '0';
            }

            downloadDivision.querySelectorAll('option').forEach((option) => {
                if (option.value === '0') {
                    option.hidden = false;
                    return;
                }
                const zoneMatch = zoneId === '0' || option.getAttribute('data-zone') === zoneId;
                const circleMatch = circleId === '0' || option.getAttribute('data-circle') === circleId;
                option.hidden = !(zoneMatch && circleMatch);
            });

            if (downloadDivision.selectedOptions[0]?.hidden) {
                downloadDivision.value = '0';
            }

            if (downloadSubdivision) {
                const divisionId = downloadDivision.value;
                downloadSubdivision.querySelectorAll('option').forEach((option) => {
                    if (option.value === '0') {
                        option.hidden = false;
                        return;
                    }
                    const zoneMatch = zoneId === '0' || option.getAttribute('data-zone') === zoneId;
                    const circleMatch = circleId === '0' || option.getAttribute('data-circle') === circleId;
                    const divisionMatch = divisionId === '0' || option.getAttribute('data-division') === divisionId;
                    option.hidden = !(zoneMatch && circleMatch && divisionMatch);
                });
                if (downloadSubdivision.selectedOptions[0]?.hidden) {
                    downloadSubdivision.value = '0';
                }
            }
        };

        if (downloadScopeToggle) {
            downloadScopeToggle.querySelectorAll('[data-download-scope]').forEach((button) => {
                button.addEventListener('click', () => {
                    downloadScopeInput.value = button.getAttribute('data-download-scope') || 'zone';
                    syncDownloadScope();
                    filterByOffice();
                });
            });
        }

        if (downloadResetFilters) {
            downloadResetFilters.addEventListener('click', () => {
                downloadScopeInput.value = 'zone';
                downloadZone.value = '0';
                downloadCircle.value = '0';
                downloadDivision.value = '0';
                if (downloadSubdivision) {
                    downloadSubdivision.value = '0';
                }
                if (downloadCategory) {
                    downloadCategory.value = '0';
                }
                if (downloadSubcategory) {
                    downloadSubcategory.value = '0';
                }
                const downloadCondition = document.getElementById('download-condition-select');
                if (downloadCondition) {
                    downloadCondition.value = '';
                }
                syncDownloadScope();
                filterByOffice();
                if (downloadCategory && downloadSubcategory) {
                    filterSubcategories(downloadCategory, downloadSubcategory);
                }
            });
        }

        downloadZone.addEventListener('change', filterByOffice);
        downloadCircle.addEventListener('change', filterByOffice);
        downloadDivision.addEventListener('change', filterByOffice);
        syncDownloadScope();
        filterByOffice();
    }

    const declarationToggleButtons = Array.from(document.querySelectorAll('[data-declaration-toggle]'));
    const declarationSections = Array.from(document.querySelectorAll('[data-declaration-section]'));
    if (declarationToggleButtons.length && declarationSections.length) {
        const declarationToggleStorageKey = `pwd-asset-declarations-toggle:${window.location.pathname}`;
        let storedDeclarationState = {};
        try {
            storedDeclarationState = JSON.parse(sessionStorage.getItem(declarationToggleStorageKey) || '{}') || {};
        } catch (error) {
            storedDeclarationState = {};
        }
        const saveDeclarationState = () => {
            const state = {};
            declarationToggleButtons.forEach((button) => {
                const key = button.getAttribute('data-declaration-toggle') || '';
                if (!key) {
                    return;
                }
                state[key] = button.classList.contains('is-active');
            });
            sessionStorage.setItem(declarationToggleStorageKey, JSON.stringify(state));
        };
        declarationToggleButtons.forEach((button) => {
            const key = button.getAttribute('data-declaration-toggle') || '';
            if (!key || !Object.prototype.hasOwnProperty.call(storedDeclarationState, key)) {
                return;
            }
            const shouldShow = !!storedDeclarationState[key];
            button.classList.toggle('is-active', shouldShow);
            const section = document.querySelector(`[data-declaration-section="${key}"]`);
            if (section) {
                section.toggleAttribute('hidden', !shouldShow);
            }
        });
        declarationToggleButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const key = button.getAttribute('data-declaration-toggle') || '';
                const section = document.querySelector(`[data-declaration-section="${key}"]`);
                if (!section) {
                    return;
                }
                const isHidden = section.hasAttribute('hidden');
                section.toggleAttribute('hidden', !isHidden);
                button.classList.toggle('is-active', isHidden);
                saveDeclarationState();
            });
        });
    }

    const usersFilters = document.getElementById('users-filters');
    if (usersFilters) {
        const zoneSelect = usersFilters.querySelector('select[name="zone_id"]');
        const circleSelect = usersFilters.querySelector('select[name="circle_id"]');
        const roleSelect = usersFilters.querySelector('select[name="role"]');
        const divisionSelect = usersFilters.querySelector('select[name="division_id"]');

        const filterCircles = () => {
            if (!circleSelect) {
                return;
            }
            const zoneId = zoneSelect ? zoneSelect.value : 'all';
            circleSelect.querySelectorAll('option').forEach((opt) => {
                if (opt.value === 'all') {
                    opt.hidden = false;
                    return;
                }
                opt.hidden = !(zoneId === 'all' || opt.getAttribute('data-zone') === zoneId);
            });
        };

        const filterDivisions = () => {
            if (!divisionSelect) {
                return;
            }
            const zoneId = zoneSelect ? zoneSelect.value : 'all';
            const circleId = circleSelect ? circleSelect.value : 'all';
            divisionSelect.querySelectorAll('option').forEach((opt) => {
                if (opt.value === 'all') {
                    opt.hidden = false;
                    return;
                }
                const matchZone = zoneId === 'all' || opt.getAttribute('data-zone') === zoneId;
                const matchCircle = circleId === 'all' || opt.getAttribute('data-circle') === circleId;
                opt.hidden = !(matchZone && matchCircle);
            });
        };

        [zoneSelect, circleSelect, roleSelect, divisionSelect].forEach((select) => {
            if (!select) {
                return;
            }
            select.addEventListener('change', () => {
                filterCircles();
                filterDivisions();
                usersFilters.submit();
            });
        });
        filterCircles();
        filterDivisions();
    }

    const importReviewModal = document.getElementById('import-review-modal');
    const importReviewMeta = document.getElementById('import-review-meta');
    const importReviewBody = document.getElementById('import-review-body');
    const importReviewAddRow = document.getElementById('import-review-add-row');
    const importReviewSummary = document.getElementById('import-review-summary');
    if (importReviewModal && importReviewMeta && importReviewBody) {
        const meta = JSON.parse(importReviewMeta.textContent || '{}');
        const categories = meta.categories || [];
        const subcategories = meta.subcategories || [];
        const hasCategory = !!importReviewBody.querySelector('[data-review-role="category"]');
        const hasSubcategory = subcategories.length > 0;
        const fields = meta.fields || [];
        const categoryMap = new Map(categories.map((item) => [String(item.name || '').trim().toLowerCase(), item]));
        const subcategoriesByCategory = new Map();
        const fieldMap = new Map(fields.map((field) => [field.field_key, field]));

        const updateReviewSummary = () => {
            if (!importReviewSummary) {
                return;
            }
            const needsAttention = importReviewBody.querySelectorAll('tr.review-row.has-errors').length;
            importReviewSummary.textContent = `Number of Rows need attention - ${needsAttention}`;
        };

        subcategories.forEach((item) => {
            const key = String(item.category_id || 0);
            if (!subcategoriesByCategory.has(key)) {
                subcategoriesByCategory.set(key, []);
            }
            subcategoriesByCategory.get(key).push(item);
        });

        const syncSubcategoryOptions = (row) => {
            const categoryInput = row.querySelector('[data-review-role="category"]');
            const subcategoryInput = row.querySelector('[data-review-role="subcategory"]');
            if (!subcategoryInput) {
                return;
            }

            const selectedCategory = categoryInput
                ? (categoryMap.get(String(categoryInput.value || '').trim().toLowerCase()) || null)
                : null;
            const previousValue = subcategoryInput.value;
            const allowed = selectedCategory
                ? (subcategoriesByCategory.get(String(selectedCategory.id)) || [])
                : (hasCategory ? [] : subcategories);

            subcategoryInput.innerHTML = '<option value="">Select</option>';
            allowed.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.name;
                option.textContent = item.name;
                subcategoryInput.appendChild(option);
            });

            const stillAllowed = allowed.some((item) => String(item.name).trim().toLowerCase() === String(previousValue).trim().toLowerCase());
            subcategoryInput.value = stillAllowed ? previousValue : '';
        };

        const normalizeReviewFieldValue = (field, rawValue) => {
            const value = String(rawValue || '').trim();
            if (value === '') {
                return '';
            }
            if (field.data_type === 'number') {
                if (!/^-?\d+(?:\.\d+)?$/.test(value)) {
                    return null;
                }
                return value.includes('.') ? value.replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '') : value;
            }
            if (field.data_type === 'date') {
                const parsed = Date.parse(value);
                if (Number.isNaN(parsed)) {
                    return null;
                }
                return new Date(parsed).toISOString().slice(0, 10);
            }
            if (field.data_type === 'yes_no') {
                const normalized = value.toLowerCase();
                if (['yes', 'y', 'true', '1'].includes(normalized)) {
                    return '1';
                }
                if (['no', 'n', 'false', '0'].includes(normalized)) {
                    return '0';
                }
                return null;
            }
            if (field.data_type === 'dropdown') {
                const option = (field.options || []).find((item) => String(item).trim().toLowerCase() === value.toLowerCase());
                return option ? String(option) : null;
            }
            return value;
        };

        const parseNumberRule = (rule) => {
            const value = String(rule || '').trim();
            const match = value.match(/^(-)?(\*)?(\d+)\.(\*)?(\d+)$/);
            if (!match) {
                return null;
            }
            return {
                allowNegative: match[1] === '-',
                beforeExact: match[2] === '*',
                beforeDigits: Number.parseInt(match[3], 10),
                afterExact: match[4] === '*',
                afterDigits: Number.parseInt(match[5], 10),
            };
        };

        const matchesNumberRule = (value, rule) => {
            if (!/^-?\d+(?:\.\d+)?$/.test(value)) {
                return false;
            }
            if (!rule.allowNegative && value.startsWith('-')) {
                return false;
            }
            const unsigned = value.replace(/^-/, '');
            const [before, after = ''] = unsigned.split('.');
            if (rule.beforeExact) {
                if (before.length !== rule.beforeDigits) {
                    return false;
                }
            } else if (before.length > rule.beforeDigits) {
                return false;
            }
            if (rule.afterExact) {
                if (after.length !== rule.afterDigits) {
                    return false;
                }
            } else if (after.length > rule.afterDigits) {
                return false;
            }
            return true;
        };

        const numberRuleMessage = (label, rule) => {
            const beforeText = `${rule.beforeExact ? 'exactly' : 'at most'} ${rule.beforeDigits} digit${rule.beforeDigits === 1 ? '' : 's'} before decimal`;
            const afterText = `${rule.afterExact ? 'exactly' : 'at most'} ${rule.afterDigits} digit${rule.afterDigits === 1 ? '' : 's'} after decimal`;
            return `${label} must follow ${beforeText} and ${afterText}${rule.allowNegative ? ' (negative allowed).' : ' (no negative allowed).'}`;
        };

        const syncReviewConditionalSelects = (row) => {
            row.querySelectorAll('[data-conditional-primary="1"]').forEach((primarySelect) => {
                const childKey = primarySelect.getAttribute('data-conditional-child') || '';
                if (!childKey) {
                    return;
                }
                const childSelect = row.querySelector(`[data-field-key="${childKey}"]`);
                if (!childSelect) {
                    return;
                }
                let map = {};
                try {
                    map = JSON.parse(primarySelect.getAttribute('data-conditional-map') || '{}');
                } catch (error) {
                    map = {};
                }
                const previousValue = childSelect.value || '';
                const allowed = Array.isArray(map[primarySelect.value]) ? map[primarySelect.value] : [];
                childSelect.innerHTML = '<option value="">Select</option>';
                allowed.forEach((optionValue) => {
                    const option = document.createElement('option');
                    option.value = optionValue;
                    option.textContent = optionValue;
                    childSelect.appendChild(option);
                });
                childSelect.value = allowed.includes(previousValue) ? previousValue : '';
            });
        };

        const validateRow = (row) => {
            const errors = [];
            const categoryInput = row.querySelector('[data-review-role="category"]');
            const subcategoryInput = row.querySelector('[data-review-role="subcategory"]');
            const categoryValue = (categoryInput?.value || '').trim();
            const subcategoryValue = (subcategoryInput?.value || '').trim();
            const category = categoryMap.get(categoryValue.toLowerCase()) || null;

            const setCellState = (input, isValid) => {
                const cell = input?.closest('td');
                if (!cell) {
                    return;
                }
                cell.classList.toggle('cell-error', !isValid);
                cell.classList.toggle('cell-valid', isValid);
            };

            if (hasCategory) {
                if (!category) {
                    errors.push('Valid category is required.');
                    setCellState(categoryInput, false);
                } else {
                    categoryInput.value = category.name;
                    setCellState(categoryInput, true);
                }
            }

            let subcategoryValid = !hasSubcategory;
            if (hasSubcategory) {
                const allowed = category ? (subcategoriesByCategory.get(String(category.id)) || []) : (hasCategory ? [] : subcategories);
                if (allowed.length > 0) {
                    const match = allowed.find((item) => String(item.name || '').trim().toLowerCase() === subcategoryValue.toLowerCase());
                    if (match) {
                        subcategoryInput.value = match.name;
                        subcategoryValid = true;
                    }
                }
                if (!subcategoryValid) {
                    errors.push('Valid sub-category is required.');
                }
                setCellState(subcategoryInput, subcategoryValid);
            }

            fields.forEach((field) => {
                const input = row.querySelector(`[data-field-key="${field.field_key}"]`);
                if (!input) {
                    return;
                }
                const value = (input.value || '').trim();
                let message = '';

                if (field.required && value === '') {
                    message = `${field.label} is required.`;
                } else if (value !== '') {
                    if (field.data_type === 'number' && !/^-?\d+(?:\.\d+)?$/.test(value)) {
                        message = `${field.label} must be numeric.`;
                    } else if (field.data_type === 'number') {
                        const parsedRule = parseNumberRule(field.number_format_rule || '');
                        if (parsedRule && !matchesNumberRule(value, parsedRule)) {
                            message = numberRuleMessage(field.label, parsedRule);
                        }
                    } else if (field.data_type === 'text') {
                        const textMaxLength = Number.parseInt(String(field.text_max_length || '0'), 10);
                        if (textMaxLength > 0 && value.length > textMaxLength) {
                            message = `${field.label} must not exceed ${textMaxLength} characters.`;
                        }
                    } else if (field.data_type === 'date' && Number.isNaN(Date.parse(value))) {
                        message = `${field.label} must be a valid date.`;
                    } else if (field.data_type === 'yes_no') {
                        const normalized = value.toLowerCase();
                        if (!['yes', 'no', '1', '0', 'true', 'false', 'y', 'n'].includes(normalized)) {
                            message = `${field.label} must be Yes or No.`;
                        }
                    } else if (field.data_type === 'dropdown') {
                        const options = (field.options || []).map((item) => String(item).trim().toLowerCase());
                        if (!options.includes(value.toLowerCase())) {
                            message = `${field.label} has an invalid option.`;
                        }
                    } else if (field.data_type === 'conditional') {
                        const options = (field.options || []).map((item) => String(item).trim().toLowerCase());
                        if (!options.includes(value.toLowerCase())) {
                            message = `${field.label} has an invalid option.`;
                        }
                    } else if (field.secondary_of_field_key) {
                        const primaryInput = row.querySelector(`[data-field-key="${field.secondary_of_field_key}"]`);
                        const primaryField = fieldMap.get(field.secondary_of_field_key);
                        const conditionalMap = primaryField?.conditional_map || {};
                        const allowed = Array.isArray(conditionalMap[primaryInput?.value || '']) ? conditionalMap[primaryInput.value] : [];
                        if (!allowed.map((item) => String(item).trim().toLowerCase()).includes(value.toLowerCase())) {
                            message = `${field.label} has an invalid option for the selected ${primaryField?.label || 'primary value'}.`;
                        }
                    }
                }

                if (!message) {
                    if (field.is_unique) {
                        const normalizedValue = normalizeReviewFieldValue(field, value);
                        if (normalizedValue !== null && normalizedValue !== '') {
                            const existingValues = field.existing_values || [];
                            const duplicateInDb = existingValues.includes(normalizedValue);
                            const duplicateInRows = Array.from(importReviewBody.querySelectorAll(`[data-field-key="${field.field_key}"]`))
                                .filter((otherInput) => otherInput !== input)
                                .some((otherInput) => normalizeReviewFieldValue(field, otherInput.value) === normalizedValue);
                            if (duplicateInDb || duplicateInRows) {
                                message = `${field.label} already exists.`;
                            }
                        }
                    }
                }

                if (message) {
                    errors.push(message);
                    setCellState(input, false);
                } else {
                    setCellState(input, true);
                }
            });

            const auditCell = row.querySelector('.audit-cell');
            if (auditCell) {
                auditCell.innerHTML = errors.length
                    ? errors.map((message) => `<div class="error-text">${message}</div>`).join('')
                    : '<span class="success-text">OK</span>';
            }
            row.classList.toggle('has-errors', errors.length > 0);
            row.classList.toggle('is-valid', errors.length === 0);
            updateReviewSummary();
        };

        const attachRowHandlers = (row) => {
            row.querySelectorAll('.review-input').forEach((input) => {
                const rerun = () => {
                    if (input.getAttribute('data-review-role') === 'category') {
                        syncSubcategoryOptions(row);
                    }
                    if (input.getAttribute('data-conditional-primary') === '1') {
                        syncReviewConditionalSelects(row);
                    }
                    validateRow(row);
                };
                input.addEventListener('input', rerun);
                input.addEventListener('change', rerun);
            });

            const deleteButton = row.querySelector('.review-delete-row');
            if (deleteButton) {
                deleteButton.addEventListener('click', () => {
                    row.remove();
                    updateReviewSummary();
                });
            }

            row.querySelectorAll('[data-bimh-field]').forEach((fieldBox) => {
                bindBimhField(fieldBox, true);
            });
            syncSubcategoryOptions(row);
            syncReviewConditionalSelects(row);
            validateRow(row);
        };

        const nextRowNumber = () => {
            const values = Array.from(importReviewBody.querySelectorAll('input[name$="[row_number]"]'))
                .map((input) => Number.parseInt(input.value || '0', 10))
                .filter((value) => !Number.isNaN(value));
            return values.length ? Math.max(...values) + 1 : 2;
        };

        const nextRowIndex = () => {
            const values = Array.from(importReviewBody.querySelectorAll('input[name$="[row_number]"]'))
                .map((input) => {
                    const match = (input.getAttribute('name') || '').match(/^rows\[(\d+)\]/);
                    return match ? Number.parseInt(match[1], 10) : null;
                })
                .filter((value) => value !== null && !Number.isNaN(value));
            return values.length ? Math.max(...values) + 1 : 0;
        };

        const createFieldCell = (index, field) => {
            const td = document.createElement('td');
            td.className = 'cell-valid';
            let input;
            if (field.data_type === 'bimh') {
                const wrapper = document.createElement('div');
                wrapper.className = 'bimh-field';
                wrapper.setAttribute('data-bimh-field', '');
                wrapper.innerHTML = `
                    <div class="bimh-input-row">
                        <input
                            type="text"
                            class="review-input bimh-id-input"
                            name="rows[${index}][fields][${field.field_key}]"
                            data-review-role="field"
                            data-field-key="${field.field_key}"
                            data-field-type="${field.data_type}"
                            data-required="${field.required ? '1' : '0'}"
                            data-number-format-rule="${field.number_format_rule || ''}"
                            data-text-max-length="${field.text_max_length || '0'}"
                            data-bimh-id-input
                            data-bimh-field-key="${field.field_key}">
                        <button type="button" class="icon-only-button bimh-picker-button" data-bimh-picker-open title="Pick establishment" aria-label="Pick establishment">&#x1F50D;</button>
                    </div>
                    <div class="bimh-suggestion-menu" data-bimh-suggestions hidden></div>
                    <div class="bimh-est-name-box" data-bimh-est-name></div>
                `;
                td.appendChild(wrapper);
                return td;
            }
            if (field.data_type === 'dropdown' || field.data_type === 'yes_no' || field.data_type === 'conditional') {
                input = document.createElement('select');
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Select';
                input.appendChild(placeholder);
                let options = field.options || [];
                if (field.data_type === 'yes_no' && !options.length) {
                    options = ['Yes', 'No'];
                }
                options.forEach((optionValue) => {
                    const option = document.createElement('option');
                    option.value = optionValue;
                    option.textContent = optionValue;
                    input.appendChild(option);
                });
            } else {
                if (field.data_type === 'text') {
                    input = document.createElement('textarea');
                    input.rows = 3;
                    input.className = 'review-input review-textarea';
                } else {
                    input = document.createElement('input');
                    input.type = field.data_type === 'number' ? 'number' : (field.data_type === 'date' ? 'date' : 'text');
                    if (field.data_type === 'number') {
                        input.step = '0.01';
                    }
                }
            }
            if (!input.className) {
                input.className = 'review-input';
            }
            input.name = `rows[${index}][fields][${field.field_key}]`;
            input.setAttribute('data-review-role', 'field');
            input.setAttribute('data-field-key', field.field_key);
            input.setAttribute('data-field-type', field.data_type);
            input.setAttribute('data-required', field.required ? '1' : '0');
            input.setAttribute('data-number-format-rule', field.number_format_rule || '');
            input.setAttribute('data-text-max-length', field.text_max_length || '0');
            if (field.data_type === 'text' && Number.parseInt(String(field.text_max_length || '0'), 10) > 0) {
                input.maxLength = Number.parseInt(String(field.text_max_length || '0'), 10);
            }
            if (field.data_type === 'conditional') {
                input.setAttribute('data-conditional-primary', '1');
                input.setAttribute('data-conditional-map', JSON.stringify(field.conditional_map || {}));
                const childField = fields.find((item) => item.secondary_of_field_key === field.field_key);
                if (childField) {
                    input.setAttribute('data-conditional-child', childField.field_key);
                }
            } else if (field.secondary_of_field_key) {
                input.setAttribute('data-conditional-secondary', field.secondary_of_field_key);
            }
            td.appendChild(input);
            return td;
        };

        if (importReviewAddRow) {
            importReviewAddRow.addEventListener('click', () => {
                const rowIndex = nextRowIndex();
                const rowNumber = nextRowNumber();
                const tr = document.createElement('tr');
                tr.className = 'review-row has-errors';

                const rowCell = document.createElement('td');
                rowCell.innerHTML = `${rowNumber}<input type="hidden" name="rows[${rowIndex}][row_number]" value="${rowNumber}">`;
                tr.appendChild(rowCell);

                if (hasCategory) {
                    const categoryCell = document.createElement('td');
                    categoryCell.className = 'cell-valid';
                    const categorySelect = document.createElement('select');
                    categorySelect.className = 'review-input';
                    categorySelect.name = `rows[${rowIndex}][category]`;
                    categorySelect.setAttribute('data-review-role', 'category');
                    categorySelect.innerHTML = '<option value="">Select</option>';
                    categories.forEach((category) => {
                        const option = document.createElement('option');
                        option.value = category.name;
                        option.textContent = category.name;
                        categorySelect.appendChild(option);
                    });
                    categoryCell.appendChild(categorySelect);
                    tr.appendChild(categoryCell);
                }

                if (hasSubcategory) {
                    const subcategoryCell = document.createElement('td');
                    subcategoryCell.className = 'cell-valid';
                    const subcategorySelect = document.createElement('select');
                    subcategorySelect.className = 'review-input';
                    subcategorySelect.name = `rows[${rowIndex}][subcategory]`;
                    subcategorySelect.setAttribute('data-review-role', 'subcategory');
                    subcategorySelect.innerHTML = '<option value="">Select</option>';
                    subcategoryCell.appendChild(subcategorySelect);
                    tr.appendChild(subcategoryCell);
                }

                fields.forEach((field) => {
                    tr.appendChild(createFieldCell(rowIndex, field));
                });

                const auditCell = document.createElement('td');
                auditCell.className = 'audit-cell';
                tr.appendChild(auditCell);

                const actionCell = document.createElement('td');
                actionCell.innerHTML = '<button type="button" class="icon-only-button icon-delete-button review-delete-row" title="Delete Row" aria-label="Delete Row">&#x1f5d1;</button>';
                tr.appendChild(actionCell);

                importReviewBody.appendChild(tr);
                attachRowHandlers(tr);
            });
        }

        importReviewBody.querySelectorAll('tr.review-row').forEach(attachRowHandlers);
        updateReviewSummary();
    }

    const officeCreateForm = document.getElementById('office-create-form');
    const officeKindInput = document.getElementById('office-kind-input');
    const officeKindButtons = document.querySelectorAll('[data-office-kind]');
    const officeKindPanels = document.querySelectorAll('[data-office-kind-panel]');
    const createCircleSelect = document.getElementById('office-create-circle-select');
    const createZoneDisplay = document.getElementById('office-create-zone-display');
    const createZoneId = document.getElementById('office-create-zone-id');
    const createDivisionSelect = document.getElementById('office-create-division-select');
    const createDivisionCircleDisplay = document.getElementById('office-create-division-circle-display');
    const createDivisionZoneDisplay = document.getElementById('office-create-division-zone-display');

    const syncCreateDivisionZone = () => {
        if (!createCircleSelect || !createZoneDisplay || !createZoneId) {
            return;
        }
        const selected = createCircleSelect.options[createCircleSelect.selectedIndex];
        createZoneDisplay.value = selected ? (selected.getAttribute('data-zone-name') || '') : '';
        createZoneId.value = selected ? (selected.getAttribute('data-zone-id') || '') : '';
    };

    const switchOfficePanel = (kind) => {
        if (!officeKindInput) {
            return;
        }
        officeKindInput.value = kind;
        officeKindButtons.forEach((button) => {
            button.classList.toggle('is-active', button.getAttribute('data-office-kind') === kind);
        });
        officeKindPanels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.getAttribute('data-office-kind-panel') !== kind);
        });
    };

    officeKindButtons.forEach((button) => {
        button.addEventListener('click', () => switchOfficePanel(button.getAttribute('data-office-kind') || 'zone'));
    });
    if (createCircleSelect) {
        createCircleSelect.addEventListener('change', syncCreateDivisionZone);
        syncCreateDivisionZone();
    }
    const syncCreateSubdivisionHierarchy = () => {
        if (!createDivisionSelect || !createDivisionCircleDisplay || !createDivisionZoneDisplay) {
            return;
        }
        const selected = createDivisionSelect.options[createDivisionSelect.selectedIndex];
        createDivisionCircleDisplay.value = selected ? (selected.getAttribute('data-circle-name') || '') : '';
        createDivisionZoneDisplay.value = selected ? (selected.getAttribute('data-zone-name') || '') : '';
    };
    if (createDivisionSelect) {
        createDivisionSelect.addEventListener('change', syncCreateSubdivisionHierarchy);
        syncCreateSubdivisionHierarchy();
    }
    if (officeCreateForm) {
        const setHiddenValue = (name, value) => {
            let input = officeCreateForm.querySelector(`input[type="hidden"][name="${name}"]`);
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                officeCreateForm.appendChild(input);
            }
            input.value = value;
        };

        officeCreateForm.addEventListener('submit', () => {
            const kind = officeKindInput ? officeKindInput.value : 'zone';
            if (kind === 'zone') {
                setHiddenValue('office_name', officeCreateForm.querySelector('[name="office_name_zone"]')?.value || '');
                setHiddenValue('office_address', officeCreateForm.querySelector('[name="office_address_zone"]')?.value || '');
                setHiddenValue('email_id', officeCreateForm.querySelector('[name="email_id_zone"]')?.value || '');
                setHiddenValue('zone_id', '');
                setHiddenValue('circle_id', '');
                setHiddenValue('division_id', '');
            } else if (kind === 'circle') {
                setHiddenValue('office_name', officeCreateForm.querySelector('[name="office_name_circle"]')?.value || '');
                setHiddenValue('office_address', officeCreateForm.querySelector('[name="office_address_circle"]')?.value || '');
                setHiddenValue('email_id', officeCreateForm.querySelector('[name="email_id_circle"]')?.value || '');
                setHiddenValue('zone_id', officeCreateForm.querySelector('[name="zone_id_circle"]')?.value || '');
                setHiddenValue('circle_id', '');
                setHiddenValue('division_id', '');
            } else if (kind === 'division') {
                setHiddenValue('office_name', officeCreateForm.querySelector('[name="office_name_division"]')?.value || '');
                setHiddenValue('office_address', officeCreateForm.querySelector('[name="office_address_division"]')?.value || '');
                setHiddenValue('email_id', officeCreateForm.querySelector('[name="email_id_division"]')?.value || '');
                setHiddenValue('zone_id', officeCreateForm.querySelector('[name="zone_id_division"]')?.value || '');
                setHiddenValue('circle_id', officeCreateForm.querySelector('[name="circle_id_division"]')?.value || '');
                setHiddenValue('division_id', '');
            } else {
                setHiddenValue('office_name', officeCreateForm.querySelector('[name="office_name_subdivision"]')?.value || '');
                setHiddenValue('office_address', officeCreateForm.querySelector('[name="office_address_subdivision"]')?.value || '');
                setHiddenValue('email_id', officeCreateForm.querySelector('[name="email_id_subdivision"]')?.value || '');
                setHiddenValue('zone_id', '');
                setHiddenValue('circle_id', '');
                setHiddenValue('division_id', officeCreateForm.querySelector('[name="division_id_subdivision"]')?.value || '');
            }
        });
    }

    document.querySelectorAll('.office-circle-select').forEach((select) => {
        const syncZoneDisplay = () => {
            const targetId = select.getAttribute('data-target-zone');
            if (!targetId) {
                return;
            }
            const target = document.getElementById(targetId);
            if (!target) {
                return;
            }
            const selected = select.options[select.selectedIndex];
            target.value = selected ? (selected.getAttribute('data-zone-name') || '') : '';
        };
        select.addEventListener('change', syncZoneDisplay);
        syncZoneDisplay();
    });

    document.querySelectorAll('.office-division-select').forEach((select) => {
        const syncHierarchyDisplay = () => {
            const circleTarget = document.getElementById(select.getAttribute('data-target-circle') || '');
            const zoneTarget = document.getElementById(select.getAttribute('data-target-zone') || '');
            const selected = select.options[select.selectedIndex];
            if (circleTarget) {
                circleTarget.value = selected ? (selected.getAttribute('data-circle-name') || '') : '';
            }
            if (zoneTarget) {
                zoneTarget.value = selected ? (selected.getAttribute('data-zone-name') || '') : '';
            }
        };
        select.addEventListener('change', syncHierarchyDisplay);
        syncHierarchyDisplay();
    });

    document.querySelectorAll('.office-inline-form').forEach((form) => {
        const formId = form.id;
        const fields = document.querySelectorAll(`[form="${formId}"]`);
        const saveButton = form.querySelector('.office-save-button');
        if (!fields.length || !saveButton) {
            return;
        }

        fields.forEach((field) => {
            field.dataset.initialValue = field.value;
            field.addEventListener('focus', () => field.classList.add('is-editing'));
            field.addEventListener('blur', () => field.classList.remove('is-editing'));
        });

        const updateDirtyState = () => {
            const dirty = Array.from(fields).some((field) => field.value !== field.dataset.initialValue);
            saveButton.classList.toggle('btn-danger', dirty);
            form.classList.toggle('is-dirty', dirty);
        };

        fields.forEach((field) => {
            field.addEventListener('input', updateDirtyState);
            field.addEventListener('change', updateDirtyState);
        });
    });
});
