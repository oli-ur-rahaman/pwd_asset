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
