document.addEventListener('DOMContentLoaded', () => {
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
        });
    });

    document.querySelectorAll('.modal-backdrop').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target !== modal) {
                return;
            }
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
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

    const filterCategory = document.querySelector('#asset-filters select[name="category_id"]');
    const filterSubcategory = document.querySelector('#asset-filters select[name="subcategory_id"]');
    if (filterCategory && filterSubcategory) {
        filterSubcategories(filterCategory, filterSubcategory);
        filterCategory.addEventListener('change', () => filterSubcategories(filterCategory, filterSubcategory));
    }

    const downloadCategory = document.getElementById('download-category-select');
    const downloadSubcategory = document.getElementById('download-subcategory-select');
    if (downloadCategory && downloadSubcategory) {
        filterSubcategories(downloadCategory, downloadSubcategory);
        downloadCategory.addEventListener('change', () => filterSubcategories(downloadCategory, downloadSubcategory));
    }

    const assetFilters = document.getElementById('asset-filters');
    if (assetFilters) {
        assetFilters.querySelectorAll('select').forEach((select) => {
            select.addEventListener('change', () => {
                const zoneSelect = assetFilters.querySelector('select[name="zone_id"]');
                const circleSelect = assetFilters.querySelector('select[name="circle_id"]');
                const divisionSelect = assetFilters.querySelector('select[name="division_id"]');
                if (select === circleSelect && circleSelect.value !== '0') {
                    const selected = circleSelect.options[circleSelect.selectedIndex];
                    if (zoneSelect) {
                        zoneSelect.value = selected.getAttribute('data-zone') || '0';
                    }
                }
                if (select === divisionSelect && divisionSelect.value !== '0') {
                    const selected = divisionSelect.options[divisionSelect.selectedIndex];
                    if (zoneSelect) {
                        zoneSelect.value = selected.getAttribute('data-zone') || '0';
                    }
                    if (circleSelect) {
                        circleSelect.value = selected.getAttribute('data-circle') || '0';
                    }
                }
                assetFilters.submit();
            });
        });
    }

    const downloadZone = document.getElementById('download-zone-select');
    const downloadCircle = document.getElementById('download-circle-select');
    const downloadDivision = document.getElementById('download-division-select');
    const downloadScopeInput = document.getElementById('download-office-scope');
    const downloadScopeToggle = document.getElementById('download-scope-toggle');
    const downloadResetFilters = document.getElementById('download-reset-filters');
    const downloadLevelFields = document.querySelectorAll('[data-download-level]');
    if (downloadZone && downloadCircle && downloadDivision && downloadScopeInput) {
        const syncDownloadScope = () => {
            const scope = downloadScopeInput.value || 'zone';
            const levels = { zone: 1, circle: 2, division: 3 };
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
            } else if (scope === 'circle') {
                downloadDivision.value = '0';
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
        const fields = meta.fields || [];
        const categoryMap = new Map(categories.map((item) => [String(item.name || '').trim().toLowerCase(), item]));
        const subcategoriesByCategory = new Map();

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

            let subcategoryValid = false;
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
            if (field.data_type === 'dropdown' || field.data_type === 'yes_no') {
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
                input = document.createElement('input');
                input.type = field.data_type === 'number' ? 'number' : (field.data_type === 'date' ? 'date' : 'text');
                if (field.data_type === 'number') {
                    input.step = '0.01';
                }
            }
            input.className = 'review-input';
            input.name = `rows[${index}][fields][${field.field_key}]`;
            input.setAttribute('data-review-role', 'field');
            input.setAttribute('data-field-key', field.field_key);
            input.setAttribute('data-field-type', field.data_type);
            input.setAttribute('data-required', field.required ? '1' : '0');
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

                const subcategoryCell = document.createElement('td');
                subcategoryCell.className = 'cell-valid';
                const subcategorySelect = document.createElement('select');
                subcategorySelect.className = 'review-input';
                subcategorySelect.name = `rows[${rowIndex}][subcategory]`;
                subcategorySelect.setAttribute('data-review-role', 'subcategory');
                subcategorySelect.innerHTML = '<option value="">Select</option>';
                subcategoryCell.appendChild(subcategorySelect);
                tr.appendChild(subcategoryCell);

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
            } else if (kind === 'circle') {
                setHiddenValue('office_name', officeCreateForm.querySelector('[name="office_name_circle"]')?.value || '');
                setHiddenValue('office_address', officeCreateForm.querySelector('[name="office_address_circle"]')?.value || '');
                setHiddenValue('email_id', officeCreateForm.querySelector('[name="email_id_circle"]')?.value || '');
                setHiddenValue('zone_id', officeCreateForm.querySelector('[name="zone_id_circle"]')?.value || '');
                setHiddenValue('circle_id', '');
            } else {
                setHiddenValue('office_name', officeCreateForm.querySelector('[name="office_name_division"]')?.value || '');
                setHiddenValue('office_address', officeCreateForm.querySelector('[name="office_address_division"]')?.value || '');
                setHiddenValue('email_id', officeCreateForm.querySelector('[name="email_id_division"]')?.value || '');
                setHiddenValue('zone_id', officeCreateForm.querySelector('[name="zone_id_division"]')?.value || '');
                setHiddenValue('circle_id', officeCreateForm.querySelector('[name="circle_id_division"]')?.value || '');
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
