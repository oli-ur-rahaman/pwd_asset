document.addEventListener('DOMContentLoaded', () => {
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

    document.querySelectorAll('[data-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (!modal) {
                return;
            }
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    document.querySelectorAll('[data-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-close');
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
        });
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
        const zoneSelect = assetFilters.querySelector('select[name="zone_id"]');
        const circleSelect = assetFilters.querySelector('select[name="circle_id"]');
        const divisionSelect = assetFilters.querySelector('select[name="division_id"]');
        const subdivisionSelect = assetFilters.querySelector('select[name="subdivision_id"]');

        const updateDependentSelect = (select, matcher) => {
            if (!select) {
                return;
            }
            const previous = select.value;
            const visibleOptions = [];
            select.querySelectorAll('option').forEach((option) => {
                if (option.value === '0' || option.value === '') {
                    option.hidden = false;
                    return;
                }
                const matches = matcher(option);
                option.hidden = !matches;
                if (matches) {
                    visibleOptions.push(option);
                }
            });
            const selectedVisible = Array.from(select.selectedOptions).some((option) => !option.hidden);
            if (!selectedVisible) {
                if (visibleOptions.length === 1) {
                    select.value = visibleOptions[0].value;
                } else {
                    select.value = select.querySelector('option')?.value || '0';
                }
            }
            return previous !== select.value;
        };

        const syncOfficeFilters = (source = null) => {
            if (source === circleSelect && circleSelect?.value !== '0') {
                const selected = circleSelect.selectedOptions[0];
                if (zoneSelect && selected) {
                    zoneSelect.value = selected.getAttribute('data-zone') || '0';
                }
            }
            if (source === divisionSelect && divisionSelect?.value !== '0') {
                const selected = divisionSelect.selectedOptions[0];
                if (zoneSelect && selected) {
                    zoneSelect.value = selected.getAttribute('data-zone') || '0';
                }
                if (circleSelect && selected) {
                    circleSelect.value = selected.getAttribute('data-circle') || '0';
                }
            }
            if (source === subdivisionSelect && subdivisionSelect?.value !== '0') {
                const selected = subdivisionSelect.selectedOptions[0];
                if (zoneSelect && selected) {
                    zoneSelect.value = selected.getAttribute('data-zone') || '0';
                }
                if (circleSelect && selected) {
                    circleSelect.value = selected.getAttribute('data-circle') || '0';
                }
                if (divisionSelect && selected) {
                    divisionSelect.value = selected.getAttribute('data-division') || '0';
                }
            }

            const currentZone = zoneSelect?.value || '0';
            const currentCircle = circleSelect?.value || '0';
            const currentDivision = divisionSelect?.value || '0';

            updateDependentSelect(circleSelect, (option) => currentZone === '0' || option.getAttribute('data-zone') === currentZone);
            updateDependentSelect(divisionSelect, (option) => {
                const zoneMatch = currentZone === '0' || option.getAttribute('data-zone') === currentZone;
                const circleMatch = currentCircle === '0' || option.getAttribute('data-circle') === currentCircle;
                return zoneMatch && circleMatch;
            });
            updateDependentSelect(subdivisionSelect, (option) => {
                const zoneMatch = currentZone === '0' || option.getAttribute('data-zone') === currentZone;
                const circleMatch = currentCircle === '0' || option.getAttribute('data-circle') === currentCircle;
                const divisionMatch = currentDivision === '0' || option.getAttribute('data-division') === currentDivision;
                return zoneMatch && circleMatch && divisionMatch;
            });
        };

        [zoneSelect, circleSelect, divisionSelect, subdivisionSelect].forEach((select) => {
            if (!select) {
                return;
            }
            select.addEventListener('change', () => syncOfficeFilters(select));
        });
        syncOfficeFilters();

        const syncConditionalFilter = (primarySelect) => {
            const childKey = primarySelect.getAttribute('data-filter-conditional-child') || '';
            if (!childKey) {
                return;
            }
            const primaryKey = primarySelect.name.replace('field_filter_', '');
            const childSelect = assetFilters.querySelector(`[data-filter-conditional-secondary="${primaryKey}"], select[name="field_filter_${childKey}"]`);
            if (!childSelect) {
                return;
            }
            let map = {};
            try {
                map = JSON.parse(primarySelect.getAttribute('data-filter-conditional-map') || '{}');
            } catch (error) {
                map = {};
            }
            const previous = childSelect.value || '';
            const selectedPrimary = primarySelect.value || '';
            let allowed = [];
            if (selectedPrimary !== '' && Array.isArray(map[selectedPrimary])) {
                allowed = map[selectedPrimary];
            } else {
                Object.values(map).forEach((items) => {
                    (items || []).forEach((item) => {
                        if (!allowed.includes(item)) {
                            allowed.push(item);
                        }
                    });
                });
            }
            childSelect.innerHTML = '<option value="">All</option>';
            allowed.forEach((optionValue) => {
                const option = document.createElement('option');
                option.value = optionValue;
                option.textContent = optionValue;
                childSelect.appendChild(option);
            });
            childSelect.value = allowed.includes(previous) ? previous : '';
        };

        assetFilters.querySelectorAll('[data-filter-conditional-primary="1"]').forEach((primarySelect) => {
            primarySelect.addEventListener('change', () => syncConditionalFilter(primarySelect));
            syncConditionalFilter(primarySelect);
        });
        assetFilters.querySelectorAll('[data-filter-conditional-secondary]').forEach((childSelect) => {
            childSelect.addEventListener('change', () => {
                const primaryKey = childSelect.getAttribute('data-filter-conditional-secondary') || '';
                const primarySelect = assetFilters.querySelector(`[name="field_filter_${primaryKey}"]`);
                if (!primarySelect || childSelect.value === '') {
                    return;
                }
                let map = {};
                try {
                    map = JSON.parse(primarySelect.getAttribute('data-filter-conditional-map') || '{}');
                } catch (error) {
                    map = {};
                }
                const matches = Object.entries(map)
                    .filter(([, options]) => Array.isArray(options) && options.includes(childSelect.value))
                    .map(([primary]) => primary);
                if (matches.length === 1) {
                    primarySelect.value = matches[0];
                    syncConditionalFilter(primarySelect);
                    childSelect.value = childSelect.value;
                }
            });
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
            if (!categoryInput || !subcategoryInput) {
                return;
            }

            const selectedCategory = categoryMap.get(String(categoryInput.value || '').trim().toLowerCase()) || null;
            const previousValue = subcategoryInput.value;
            const allowed = selectedCategory ? (subcategoriesByCategory.get(String(selectedCategory.id)) || []) : [];

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
                const parsed = Number(value);
                if (Number.isNaN(parsed)) {
                    return null;
                }
                return parsed.toFixed(4).replace(/\.?0+$/, '');
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

            if (!category) {
                errors.push('Valid category is required.');
                setCellState(categoryInput, false);
            } else {
                categoryInput.value = category.name;
                setCellState(categoryInput, true);
            }

            let subcategoryValid = !hasSubcategory;
            if (hasSubcategory) {
                if (category) {
                    const allowed = subcategoriesByCategory.get(String(category.id)) || [];
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
                    if (field.data_type === 'number' && Number.isNaN(Number(value))) {
                        message = `${field.label} must be numeric.`;
                    } else if (field.data_type === 'number') {
                        const parsedRule = parseNumberRule(field.number_format_rule || '');
                        if (parsedRule && !matchesNumberRule(value, parsedRule)) {
                            message = numberRuleMessage(field.label, parsedRule);
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
