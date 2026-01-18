document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('chart-filter');
    const canvas = document.getElementById('data-chart');
    let chart = null;

    const loadChart = async () => {
        if (!form || !canvas) {
            return;
        }
        const params = new URLSearchParams(new FormData(form));
        const response = await fetch('chart_data.php?' + params.toString());
        if (!response.ok) {
            return;
        }
        const data = await response.json();
        const labels = data.map(item => item.label);
        const values = data.map(item => item.value);

        if (chart) {
            chart.data.labels = labels;
            chart.data.datasets[0].data = values;
            chart.update();
            return;
        }

        chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Monthly Values',
                    data: values,
                    borderColor: '#0f3d5e',
                    backgroundColor: 'rgba(15, 61, 94, 0.15)',
                    fill: true,
                    tension: 0.25,
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    };

    if (form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            loadChart();
        });
        loadChart();
    }

    const jpegBtn = document.getElementById('download-jpeg');
    if (jpegBtn && canvas) {
        jpegBtn.addEventListener('click', () => {
            const link = document.createElement('a');
            link.href = canvas.toDataURL('image/jpeg', 0.9);
            link.download = 'chart.jpg';
            link.click();
        });
    }

    const pdfBtn = document.getElementById('download-pdf');
    const imgInput = document.getElementById('chart-image-data');
    const pdfForm = document.getElementById('chart-pdf-form');
    if (pdfBtn && canvas && imgInput && pdfForm) {
        pdfBtn.addEventListener('click', () => {
            imgInput.value = canvas.toDataURL('image/png');
            pdfForm.submit();
        });
    }

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
        forgotModal.addEventListener('click', (event) => {
            if (event.target === forgotModal) {
                forgotModal.classList.remove('open');
                forgotModal.setAttribute('aria-hidden', 'true');
            }
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
        });
    });

    document.querySelectorAll('.modal-backdrop').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            }
        });
    });

    const boardFilters = document.getElementById('board-filters');
    if (boardFilters) {
        const zoneSelect = boardFilters.querySelector('select[name="zone_id"]');
        const circleSelect = boardFilters.querySelector('select[name="circle_id"]');
        const divisionSelect = boardFilters.querySelector('select[name="division_id"]');
        const fySelect = boardFilters.querySelector('select[name="fy_id"]');
        const budgetSelect = boardFilters.querySelector('select[name="budget_type"]');
        const ministrySelect = boardFilters.querySelector('select[name="ministry_id"]');
        const viewModeInput = boardFilters.querySelector('input[name="view_mode"]');

        const setSelectValue = (select, value) => {
            if (!select) {
                return;
            }
            const option = select.querySelector(`option[value="${value}"]`);
            if (option) {
                select.value = value;
            }
        };

        if (zoneSelect) {
            zoneSelect.addEventListener('change', () => {
                setSelectValue(circleSelect, 'all');
                setSelectValue(divisionSelect, 'all');
                boardFilters.submit();
            });
        }

        if (circleSelect) {
            circleSelect.addEventListener('change', () => {
                const selected = circleSelect.options[circleSelect.selectedIndex];
                const zoneId = selected ? selected.getAttribute('data-zone') : null;
                if (zoneId && zoneSelect && !zoneSelect.disabled) {
                    setSelectValue(zoneSelect, zoneId);
                }
                setSelectValue(divisionSelect, 'all');
                boardFilters.submit();
            });
        }

        if (divisionSelect) {
            divisionSelect.addEventListener('change', () => {
                const selected = divisionSelect.options[divisionSelect.selectedIndex];
                const zoneId = selected ? selected.getAttribute('data-zone') : null;
                const circleId = selected ? selected.getAttribute('data-circle') : null;
                if (zoneId && zoneSelect && !zoneSelect.disabled) {
                    setSelectValue(zoneSelect, zoneId);
                }
                if (circleId && circleSelect && !circleSelect.disabled) {
                    setSelectValue(circleSelect, circleId);
                }
                boardFilters.submit();
            });
        }

        if (fySelect) {
            fySelect.addEventListener('change', () => {
                boardFilters.submit();
            });
        }

        if (budgetSelect) {
            budgetSelect.addEventListener('change', () => {
                boardFilters.submit();
            });
        }

        if (ministrySelect) {
            ministrySelect.addEventListener('change', () => {
                boardFilters.submit();
            });
        }

        document.querySelectorAll('.view-toggle .toggle-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.view-toggle .toggle-btn').forEach((b) => b.classList.remove('active'));
                btn.classList.add('active');
                const view = btn.getAttribute('data-view') || 'ministry';
                if (viewModeInput) {
                    viewModeInput.value = view;
                }
                boardFilters.submit();
            });
        });

        const resetBtn = document.getElementById('filters-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                const params = new URLSearchParams();
                params.set('page', 'board');
                params.set('reset', '1');
                window.location.href = 'index.php?' + params.toString();
            });
        }

        const filterBar = document.querySelector('.sticky-filters');
        const spacer = document.getElementById('filter-bar-spacer');
        if (filterBar && spacer) {
            let stickyStart = filterBar.getBoundingClientRect().top + window.scrollY;
            const updateStickyBar = () => {
                if (window.scrollY >= stickyStart) {
                    if (!filterBar.classList.contains('is-sticky')) {
                        filterBar.classList.add('is-sticky');
                        spacer.style.height = filterBar.offsetHeight + 'px';
                    }
                } else {
                    filterBar.classList.remove('is-sticky');
                    spacer.style.height = '0px';
                }
            };
            const recalcStickyStart = () => {
                if (!filterBar.classList.contains('is-sticky')) {
                    stickyStart = filterBar.getBoundingClientRect().top + window.scrollY;
                }
                updateStickyBar();
            };
            updateStickyBar();
            window.addEventListener('scroll', updateStickyBar);
            window.addEventListener('resize', recalcStickyStart);
        }
    }

    const usersFilters = document.getElementById('users-filters');
    if (usersFilters) {
        const zoneSelect = usersFilters.querySelector('select[name="zone_id"]');
        const circleSelect = usersFilters.querySelector('select[name="circle_id"]');
        const roleSelect = usersFilters.querySelector('select[name="role"]');
        const divisionSelect = usersFilters.querySelector('select[name="division_id"]');

        const filterUserCircles = () => {
            if (!circleSelect) {
                return;
            }
            const zoneId = zoneSelect ? zoneSelect.value : 'all';
            circleSelect.querySelectorAll('option').forEach((opt) => {
                if (opt.value === 'all') {
                    opt.hidden = false;
                    return;
                }
                const match = zoneId === 'all' || opt.getAttribute('data-zone') === zoneId;
                opt.hidden = !match;
            });
        };

        const filterUserDivisions = () => {
            if (!divisionSelect) {
                return;
            }
            const zoneId = zoneSelect ? zoneSelect.value : 'all';
            const circleId = circleSelect ? circleSelect.value : 'all';
            const visibleCircles = new Set();
            if (circleSelect) {
                circleSelect.querySelectorAll('option').forEach((opt) => {
                    if (opt.value !== 'all' && !opt.hidden) {
                        visibleCircles.add(opt.value);
                    }
                });
            }
            divisionSelect.querySelectorAll('option').forEach((opt) => {
                if (opt.value === 'all') {
                    opt.hidden = false;
                    return;
                }
                const optZone = opt.getAttribute('data-zone') || '';
                const optCircle = opt.getAttribute('data-circle') || '';
                if (circleId !== 'all') {
                    opt.hidden = optCircle !== circleId;
                    return;
                }
                if (zoneId === 'all') {
                    opt.hidden = visibleCircles.size > 0 && !visibleCircles.has(optCircle);
                    return;
                }
                opt.hidden = optZone !== zoneId;
            });
        };

        if (zoneSelect) {
            zoneSelect.addEventListener('change', () => {
                if (circleSelect) {
                    circleSelect.value = 'all';
                }
                if (divisionSelect) {
                    divisionSelect.value = 'all';
                }
                filterUserCircles();
                filterUserDivisions();
                usersFilters.submit();
            });
        }

        if (circleSelect) {
            circleSelect.addEventListener('change', () => {
                if (circleSelect.value !== 'all') {
                    const selected = circleSelect.options[circleSelect.selectedIndex];
                    const zoneId = selected ? selected.getAttribute('data-zone') : null;
                    if (zoneSelect && zoneId) {
                        zoneSelect.value = zoneId;
                    }
                }
                if (divisionSelect) {
                    divisionSelect.value = 'all';
                }
                filterUserCircles();
                filterUserDivisions();
                usersFilters.submit();
            });
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', () => {
                usersFilters.submit();
            });
        }

        if (divisionSelect) {
            divisionSelect.addEventListener('change', () => {
                if (divisionSelect.value !== 'all') {
                    const selected = divisionSelect.options[divisionSelect.selectedIndex];
                    const zoneId = selected ? selected.getAttribute('data-zone') : null;
                    const circleId = selected ? selected.getAttribute('data-circle') : null;
                    if (zoneSelect && zoneId) {
                        zoneSelect.value = zoneId;
                    }
                    if (circleSelect && circleId) {
                        circleSelect.value = circleId;
                    }
                    filterUserCircles();
                    filterUserDivisions();
                }
                usersFilters.submit();
            });
        }

        filterUserCircles();
        filterUserDivisions();
    }

    const graphModal = document.getElementById('graph-modal');
    const graphChartCanvas = document.getElementById('board-chart');
    const graphFy = document.getElementById('graph-fy');
    const graphMinistry = document.getElementById('graph-ministry');
    const graphDivision = document.getElementById('graph-division');
    const graphMetricName = document.getElementById('graph-metric-name');
    const graphDivisionName = document.getElementById('graph-division-name');
    const graphMinistryName = document.getElementById('graph-ministry-name');
    const graphOfficeName = document.getElementById('graph-office-name');
    const graphDate = document.getElementById('graph-date');
    const graphDownload = document.getElementById('graph-download');
    let boardChart = null;
    let currentMetric = 'pkg';
    let currentTable = 'opr_repair';

    const metricLabels = {
        pkg: 'Total no. of packages',
        est: 'Total Value',
        pkg_live: 'In live',
        pkg_eval: 'Evaluation/Appr.',
        pkg_cont: 'Contract Awarded',
        cont: 'Contract Value',
    };

    const getDivisionName = () => {
        if (!graphDivision) {
            return graphOfficeName ? graphOfficeName.textContent : '';
        }
        const value = graphDivision.value;
        if (value === 'all') {
            return 'All';
        }
        const option = graphDivision.querySelector(`option[value="${value}"]`);
        return option ? option.textContent : '';
    };

    const getMinistryName = () => {
        if (!graphMinistry) {
            return 'All';
        }
        const value = graphMinistry.value;
        if (value === 'all') {
            return 'All';
        }
        const option = graphMinistry.querySelector(`option[value="${value}"]`);
        return option ? option.textContent : '';
    };

    const loadBoardChart = async () => {
        if (!graphChartCanvas || !graphFy) {
            return;
        }
        const params = new URLSearchParams();
        params.set('table', currentTable);
        params.set('metric', currentMetric);
        params.set('fy_id', graphFy.value);
        if (graphMinistry) {
            params.set('ministry_id', graphMinistry.value);
        }
        if (graphDivision) {
            params.set('division_id', graphDivision.value);
        } else {
            const divisionId = graphModal ? graphModal.getAttribute('data-division-id') : '';
            params.set('division_id', divisionId || 'all');
        }
        const response = await fetch('chart_data.php?' + params.toString());
        if (!response.ok) {
            return;
        }
        const data = await response.json();
        const labels = data.map(item => item.label);
        const values = data.map(item => item.value);

        if (graphMetricName) {
            graphMetricName.textContent = metricLabels[currentMetric] || currentMetric;
        }
        if (graphDivisionName) {
            graphDivisionName.textContent = getDivisionName();
        }
        if (graphMinistryName) {
            graphMinistryName.textContent = getMinistryName();
        }
        if (graphDate) {
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = now.getFullYear();
            graphDate.textContent = `${day}-${month}-${year}`;
        }

        if (boardChart) {
            boardChart.data.labels = labels;
            boardChart.data.datasets[0].data = values;
            boardChart.update();
            return;
        }

        boardChart = new Chart(graphChartCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Monthly Values',
                    data: values,
                    borderColor: '#0f3d5e',
                    backgroundColor: 'rgba(15, 61, 94, 0.6)',
                }]
            },
            options: {
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    };

    document.querySelectorAll('[data-modal="graph-modal"]').forEach((button) => {
        button.addEventListener('click', () => {
            currentTable = button.getAttribute('data-table') || 'opr_repair';
            const cardViewMode = button.getAttribute('data-view-mode') || '';
            const fixedMinistryId = button.getAttribute('data-ministry-id') || '';
            const fixedDivisionId = button.getAttribute('data-division-id') || '';
            const fixedDivisionName = button.getAttribute('data-division-name') || '';
            const fixedMinistryName = button.getAttribute('data-ministry-name') || '';
            if (graphModal) {
                const title = document.getElementById('graph-title');
                if (title) {
                    const titles = {
                        operational: 'Operational Budget',
                        development: 'Development Budget',
                        opr_repair: 'Operational Budget (Repair Works)',
                        opr_other: 'Operational Budget (Other than Repair)',
                        dev_pw: 'Development Budget (MoHPW)',
                        opr_other_min: 'Operational Budget (Other Ministry)',
                        dev_other_min: 'Development Budget (Other Ministry)',
                    };
                    title.textContent = titles[currentTable] || 'Budget';
                }
            }
            if (graphMinistry) {
                graphMinistry.disabled = false;
                graphMinistry.querySelectorAll('option').forEach((opt) => {
                    if (opt.value === 'all') {
                        opt.hidden = false;
                        return;
                    }
                    if (currentTable === 'operational') {
                        opt.hidden = opt.getAttribute('data-opr') !== '1';
                    } else if (currentTable === 'development') {
                        opt.hidden = opt.getAttribute('data-dev') !== '1';
                    } else {
                        opt.hidden = false;
                    }
                });
                if (cardViewMode === 'ministry' && fixedMinistryId) {
                    graphMinistry.value = fixedMinistryId;
                    graphMinistry.disabled = true;
                } else if (graphMinistry.selectedOptions.length && graphMinistry.selectedOptions[0].hidden) {
                    graphMinistry.value = 'all';
                }
            }
            if (graphDivision) {
                graphDivision.disabled = false;
                if (cardViewMode === 'division' && fixedDivisionId) {
                    graphDivision.value = fixedDivisionId;
                    graphDivision.disabled = true;
                } else if (graphModal) {
                    const zoneId = graphModal.getAttribute('data-zone-id') || 'all';
                    graphDivision.querySelectorAll('option').forEach((opt) => {
                        if (opt.value === 'all') {
                            opt.hidden = false;
                            return;
                        }
                        const optZone = opt.getAttribute('data-zone') || '';
                        opt.hidden = zoneId !== 'all' && optZone !== zoneId;
                    });
                    if (graphDivision.selectedOptions.length && graphDivision.selectedOptions[0].hidden) {
                        graphDivision.value = 'all';
                    }
                }
            }
            if (graphDivisionName && cardViewMode === 'division' && fixedDivisionName) {
                graphDivisionName.textContent = fixedDivisionName;
            }
            if (graphDivisionName && cardViewMode === 'ministry') {
                graphDivisionName.textContent = graphDivision ? graphDivision.options[graphDivision.selectedIndex].textContent : 'All';
            }
            if (graphMinistryName) {
                graphMinistryName.textContent = getMinistryName();
            }
            loadBoardChart();
        });
    });

    document.querySelectorAll('.metric-buttons button').forEach((btn) => {
        btn.addEventListener('click', () => {
            currentMetric = btn.getAttribute('data-metric') || 'pkg';
            document.querySelectorAll('.metric-buttons button').forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');
            loadBoardChart();
        });
    });

    const metricButtons = document.querySelectorAll('.metric-buttons button');
    if (metricButtons.length > 0) {
        metricButtons[0].classList.add('active');
    }

    if (graphFy) {
        graphFy.addEventListener('change', loadBoardChart);
    }
    if (graphMinistry) {
        graphMinistry.addEventListener('change', loadBoardChart);
    }
    if (graphDivision) {
        graphDivision.addEventListener('change', loadBoardChart);
    }

    if (graphDownload && graphChartCanvas) {
        graphDownload.addEventListener('click', () => {
            const meta = [
                `Office: ${graphOfficeName ? graphOfficeName.textContent : ''}`,
                `Division: ${graphDivisionName ? graphDivisionName.textContent : ''}`,
                `Ministry: ${graphMinistryName ? graphMinistryName.textContent : ''}`,
                `Metric: ${graphMetricName ? graphMetricName.textContent : ''}`,
                `Date: ${graphDate ? graphDate.textContent : ''}`,
            ];
            const exportCanvas = document.createElement('canvas');
            const margin = 40;
            const headerHeight = 70;
            exportCanvas.width = graphChartCanvas.width + margin * 2;
            exportCanvas.height = graphChartCanvas.height + headerHeight + margin * 2;
            const ctx = exportCanvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, exportCanvas.width, exportCanvas.height);
            ctx.fillStyle = '#1f2a44';
            ctx.font = '32px Arial';
            const titles = {
                opr_repair: 'Operational Budget (Repair Works)',
                opr_other: 'Operational Budget (Other than Repair)',
                dev_pw: 'Development Budget (MoHPW)',
                opr_other_min: 'Operational Budget (Other Ministry)',
                dev_other_min: 'Development Budget (Other Ministry)',
            };
            const title = titles[currentTable] || 'Budget';
            const titleWidth = ctx.measureText(title).width;
            ctx.fillText(title, (exportCanvas.width - titleWidth) / 2, margin + 28);
            const fyLabel = graphFy ? graphFy.options[graphFy.selectedIndex].textContent : '';
            const fyText = fyLabel ? `Fiscal Year: ${fyLabel}` : '';
            ctx.font = '14px Arial';
            const fyWidth = ctx.measureText(fyText).width;
            ctx.fillText(fyText, (exportCanvas.width - fyWidth) / 2, margin + 50);
            ctx.drawImage(graphChartCanvas, margin, headerHeight + margin);
            ctx.fillStyle = '#1f2a44';
            ctx.font = '12px Arial';
            const padding = 12;
            const blockWidth = 200;
            const lineHeight = 14;
            let y = headerHeight + margin + padding + lineHeight;
            const xRight = exportCanvas.width - padding;
            const xLeft = xRight - blockWidth;
            meta.forEach((line) => {
                const parts = line.split(':');
                if (parts.length > 1) {
                    const label = parts.shift().trim() + ':';
                    const value = parts.join(':').trim();
                    ctx.textAlign = 'left';
                    ctx.fillText(label, xLeft, y);
                    ctx.textAlign = 'right';
                    ctx.fillText(value, xRight, y);
                } else {
                    ctx.textAlign = 'left';
                    ctx.fillText(line, xLeft, y);
                }
                y += lineHeight;
            });
            ctx.textAlign = 'left';
            const link = document.createElement('a');
            link.href = exportCanvas.toDataURL('image/jpeg', 0.9);
            link.download = 'graph.jpg';
            link.click();
        });
    }

    document.querySelectorAll('[id^="edit-user-"]').forEach((modal) => {
        const zoneSelect = modal.querySelector('.zone-select');
        const circleSelect = modal.querySelector('.circle-select');
        const divisionSelect = modal.querySelector('.division-select');
        const roleSelect = modal.querySelector('select[name="office_role"]');
        const officeTypeInput = modal.querySelector('input[name="office_type"]');
        const officeTypeDisplay = modal.querySelector('.office-type-display');

        const officeTypeLabels = {
            1: "Chief Engineer's Office",
            2: 'Zone',
            3: 'Circle',
            4: 'Division',
        };

        const setOfficeType = (type) => {
            const value = String(type);
            if (officeTypeInput) {
                officeTypeInput.value = value;
            }
            if (officeTypeDisplay) {
                officeTypeDisplay.value = officeTypeLabels[type] || '-';
            }
        };

        const updateOfficeTypeFromSelection = () => {
            let type = 1;
            if (divisionSelect && divisionSelect.value !== '0') {
                type = 4;
            } else if (circleSelect && circleSelect.value !== '0') {
                type = 3;
            } else if (zoneSelect && zoneSelect.value !== '0') {
                type = 2;
            }
            setOfficeType(type);
        };

        const filterCircles = () => {
            if (!circleSelect) {
                return;
            }
            const zoneId = zoneSelect ? zoneSelect.value : '0';
            circleSelect.querySelectorAll('option').forEach((opt) => {
                if (opt.value === '0') {
                    opt.hidden = false;
                    return;
                }
                const match = zoneId === '0' || opt.getAttribute('data-zone') === zoneId;
                opt.hidden = !match;
            });
        };

        const filterDivisions = () => {
            if (!divisionSelect) {
                return;
            }
            const zoneId = zoneSelect ? zoneSelect.value : '0';
            const circleId = circleSelect ? circleSelect.value : '0';
            divisionSelect.querySelectorAll('option').forEach((opt) => {
                if (opt.value === '0') {
                    opt.hidden = false;
                    return;
                }
                const matchZone = zoneId === '0' || opt.getAttribute('data-zone') === zoneId;
                const matchCircle = circleId === '0' || opt.getAttribute('data-circle') === circleId;
                opt.hidden = !(matchZone && matchCircle);
            });
        };

        if (roleSelect) {
            roleSelect.addEventListener('change', () => {
                if (roleSelect.value === '2' || roleSelect.value === '3') {
                    if (zoneSelect) {
                        zoneSelect.value = '0';
                    }
                    if (circleSelect) {
                        circleSelect.value = '0';
                    }
                    if (divisionSelect) {
                        divisionSelect.value = '0';
                    }
                    filterCircles();
                    filterDivisions();
                    setOfficeType(1);
                    return;
                }
                updateOfficeTypeFromSelection();
            });
        }

        if (zoneSelect) {
            zoneSelect.addEventListener('change', () => {
                if (circleSelect) {
                    circleSelect.value = '0';
                }
                if (divisionSelect) {
                    divisionSelect.value = '0';
                }
                if (roleSelect && zoneSelect.value !== '0') {
                    roleSelect.value = '1';
                }
                filterCircles();
                filterDivisions();
                updateOfficeTypeFromSelection();
            });
        }

        if (circleSelect) {
            circleSelect.addEventListener('change', () => {
                if (divisionSelect) {
                    divisionSelect.value = '0';
                }
                if (zoneSelect && circleSelect.value !== '0') {
                    const selected = circleSelect.options[circleSelect.selectedIndex];
                    const zoneId = selected.getAttribute('data-zone');
                    if (zoneId) {
                        zoneSelect.value = zoneId;
                    }
                }
                if (roleSelect && circleSelect.value !== '0') {
                    roleSelect.value = '1';
                }
                filterDivisions();
                updateOfficeTypeFromSelection();
            });
        }

        if (divisionSelect) {
            divisionSelect.addEventListener('change', () => {
                if (divisionSelect.value === '0') {
                    updateOfficeTypeFromSelection();
                    return;
                }
                const selected = divisionSelect.options[divisionSelect.selectedIndex];
                const zoneId = selected.getAttribute('data-zone');
                const circleId = selected.getAttribute('data-circle');
                if (zoneSelect && zoneId) {
                    zoneSelect.value = zoneId;
                }
                if (circleSelect && circleId) {
                    circleSelect.value = circleId;
                }
                if (roleSelect) {
                    roleSelect.value = '1';
                }
                filterCircles();
                filterDivisions();
                updateOfficeTypeFromSelection();
            });
        }

        filterCircles();
        filterDivisions();
        if (roleSelect && (roleSelect.value === '2' || roleSelect.value === '3')) {
            if (zoneSelect) {
                zoneSelect.value = '0';
            }
            if (circleSelect) {
                circleSelect.value = '0';
            }
            if (divisionSelect) {
                divisionSelect.value = '0';
            }
            filterCircles();
            filterDivisions();
            setOfficeType(1);
        } else {
            updateOfficeTypeFromSelection();
        }
    });

    const nameModal = document.getElementById('name-modal');
    const nameForm = document.getElementById('name-form');
    const nameInput = document.getElementById('officer-name-input');
    const nameCancel = document.getElementById('name-cancel');
    if (nameModal && nameForm && nameInput && nameCancel) {
        nameCancel.addEventListener('click', () => {
            nameModal.classList.remove('open');
            nameModal.setAttribute('aria-hidden', 'true');
        });
        nameForm.addEventListener('submit', (event) => {
            if (nameInput.value.trim() === '') {
                event.preventDefault();
                alert('Please enter your name and press Save.');
            }
        });
    }

    const rowModalMap = {
        operational: {
            modalId: 'revenue-modal',
            nameId: 'operational-ministry-name',
        },
        development: {
            modalId: 'development-modal',
            nameId: 'development-ministry-name',
        },
    };

    const fillBudgetModal = (modal, data) => {
        const setValue = (selector, value) => {
            const input = modal.querySelector(selector);
            if (!input) {
                return;
            }
            input.value = value;
        };
        if (data.month_val) {
            setValue('select[name="month_val"]', data.month_val);
        }
        setValue('input[name="pkg"]', data.pkg ?? 0);
        setValue('input[name="est"]', data.est ?? 0);
        setValue('input[name="pkg_live"]', data.pkg_live ?? 0);
        setValue('input[name="pkg_eval"]', data.pkg_eval ?? 0);
        setValue('input[name="pkg_cont"]', data.pkg_cont ?? 0);
        setValue('input[name="cont"]', data.cont ?? 0);
        const note = modal.querySelector('textarea[name="note"]');
        if (note) {
            note.value = data.note ?? '';
        }
    };

    const openBudgetModal = async ({ table, ministryId, ministryName, fyId, source }) => {
        const config = rowModalMap[table];
        if (!config) {
            return;
        }
        const modal = document.getElementById(config.modalId);
        if (!modal) {
            return;
        }
        modal.setAttribute('data-open-source', source || '');
        const nameEl = document.getElementById(config.nameId);
        if (nameEl) {
            nameEl.textContent = ministryName || '';
        }
        const hiddenMinistry = modal.querySelector('input[name="ministry_id"]');
        if (hiddenMinistry) {
            hiddenMinistry.value = ministryId;
        }

        try {
            const params = new URLSearchParams({
                table,
                ministry_id: ministryId,
                fy_id: fyId || '',
            });
            const response = await fetch('ministry_row.php?' + params.toString());
            if (response.ok) {
                const data = await response.json();
                fillBudgetModal(modal, data);
                if (nameEl && data.ministry_name) {
                    nameEl.textContent = data.ministry_name;
                }
            }
        } catch (err) {
            // Ignore network errors; modal still opens with existing values.
        }

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    };

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.row-clickable[data-ministry-id]');
        if (!row) {
            return;
        }
        const table = row.getAttribute('data-table');
        const ministryId = row.getAttribute('data-ministry-id');
        if (!table || !ministryId) {
            return;
        }
        const ministryName = row.getAttribute('data-ministry-name') || '';
        const fyWrapper = row.closest('.operational-budget-card');
        const fyId = fyWrapper ? fyWrapper.getAttribute('data-fy-id') : '';
        openBudgetModal({
            table,
            ministryId,
            ministryName,
            fyId,
            source: 'row',
        });
    });

    document.querySelectorAll('[data-ministry-list]').forEach((button) => {
        button.addEventListener('click', () => {
            const table = button.getAttribute('data-ministry-list');
            const modalId = table === 'development' ? 'development-ministry-modal' : 'operational-ministry-modal';
            const modal = document.getElementById(modalId);
            if (!modal) {
                return;
            }
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    document.querySelectorAll('.ministry-pill').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.getAttribute('data-disabled') === '1') {
                return;
            }
            const table = button.getAttribute('data-table');
            const ministryId = button.getAttribute('data-ministry-id');
            const ministryName = button.getAttribute('data-ministry-name') || '';
            if (!table || !ministryId) {
                return;
            }
            const modalId = table === 'development' ? 'development-ministry-modal' : 'operational-ministry-modal';
            const listModal = document.getElementById(modalId);
            if (listModal) {
                listModal.classList.remove('open');
                listModal.setAttribute('aria-hidden', 'true');
            }
            const card = document.querySelector(`.operational-budget-card[data-table="${table}"]`);
            const fyId = card ? card.getAttribute('data-fy-id') : '';
            openBudgetModal({
                table,
                ministryId,
                ministryName,
                fyId,
                source: 'list',
            });
        });
    });

    document.querySelectorAll('.ministry-search').forEach((input) => {
        input.addEventListener('input', () => {
            const target = input.getAttribute('data-target');
            if (!target) {
                return;
            }
            const query = input.value.trim().toLowerCase();
            const group = input.closest('.ministry-group');
            if (!group) {
                return;
            }
            group.querySelectorAll('.ministry-pill').forEach((btn) => {
                if (btn.getAttribute('data-disabled') === '1') {
                    return;
                }
                const name = (btn.getAttribute('data-ministry-name') || '').toLowerCase();
                btn.style.display = name.includes(query) ? '' : 'none';
            });
        });
    });

    const modalFormMap = {
        operational: 'revenue-modal',
        development: 'development-modal',
    };

    const createRowElement = (table, data) => {
        const row = document.createElement('tr');
        row.className = 'row-clickable';
        row.setAttribute('data-table', table);
        row.setAttribute('data-ministry-id', String(data.ministry_id || '0'));
        row.setAttribute('data-ministry-name', data.ministry_name || '');
        row.setAttribute('data-default', '0');
        const values = [
            data.ministry_name || '-',
            data.pkg ?? 0,
            data.est ?? 0,
            data.pkg_live ?? 0,
            data.pkg_eval ?? 0,
            data.pkg_cont ?? 0,
            data.cont ?? 0,
        ];
        values.forEach((val) => {
            const td = document.createElement('td');
            td.textContent = String(val);
            row.appendChild(td);
        });
        return row;
    };

    const syncMinistryLists = (table, data) => {
        const modalId = table === 'development' ? 'development-ministry-modal' : 'operational-ministry-modal';
        const listModal = document.getElementById(modalId);
        if (!listModal) {
            return;
        }
        const presentList = listModal.querySelector('.ministry-group:first-of-type .ministry-list');
        const availableList = listModal.querySelector('.ministry-group:last-of-type .ministry-list');
        if (!presentList || !availableList) {
            return;
        }
        const selector = `.ministry-pill[data-ministry-id="${data.ministry_id}"]`;
        let pill = listModal.querySelector(selector);
        if (!pill) {
            return;
        }
        if (!pill.classList.contains('disabled')) {
            pill.classList.add('disabled');
            pill.setAttribute('data-disabled', '1');
        }
        availableList.querySelectorAll(selector).forEach((node) => {
            if (node !== pill) {
                node.remove();
            }
        });
        if (!presentList.contains(pill)) {
            presentList.appendChild(pill);
        }
    };

    const insertOrUpdateRow = (table, data) => {
        const tbody = document.querySelector(`.operational-budget-card[data-table="${table}"] tbody`);
        if (!tbody) {
            return;
        }
        const existing = tbody.querySelector(`tr[data-table="${table}"][data-ministry-id="${data.ministry_id}"]`);
        const newRow = createRowElement(table, data);
        if (existing) {
            existing.replaceWith(newRow);
            return;
        }
        const rows = Array.from(tbody.querySelectorAll(`tr[data-table="${table}"]`));
        const lastDefault = rows.filter((row) => row.getAttribute('data-default') === '1').pop();
        if (lastDefault && lastDefault.nextSibling) {
            lastDefault.parentNode.insertBefore(newRow, lastDefault.nextSibling);
        } else if (lastDefault) {
            lastDefault.parentNode.appendChild(newRow);
        } else {
            tbody.appendChild(newRow);
        }
    };

    Object.keys(modalFormMap).forEach((table) => {
        const modal = document.getElementById(modalFormMap[table]);
        if (!modal) {
            return;
        }
        const form = modal.querySelector('form');
        if (!form) {
            return;
        }
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const openSource = modal.getAttribute('data-open-source');
            if (openSource === 'list') {
                const pkg = Number(form.querySelector('input[name="pkg"]')?.value || 0);
                const est = Number(form.querySelector('input[name="est"]')?.value || 0);
                const pkgLive = Number(form.querySelector('input[name="pkg_live"]')?.value || 0);
                const pkgEval = Number(form.querySelector('input[name="pkg_eval"]')?.value || 0);
                const pkgCont = Number(form.querySelector('input[name="pkg_cont"]')?.value || 0);
                const cont = Number(form.querySelector('input[name="cont"]')?.value || 0);
                if (pkg === 0 && est === 0 && pkgLive === 0 && pkgEval === 0 && pkgCont === 0 && cont === 0) {
                    modal.classList.remove('open');
                    modal.setAttribute('aria-hidden', 'true');
                    return;
                }
            }
            const formData = new FormData(form);
            formData.set('table', table);
            try {
                const response = await fetch('add_record_ajax.php', {
                    method: 'POST',
                    body: formData,
                });
                const data = await response.json();
                if (!response.ok || !data.ok) {
                    alert(data.error || 'Unable to save entry.');
                    return;
                }
                insertOrUpdateRow(table, data);
                syncMinistryLists(table, data);
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
                window.location.reload();
            } catch (err) {
                alert('Unable to save entry.');
            }
        });
    });
});
