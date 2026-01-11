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

    const graphModal = document.getElementById('graph-modal');
    const graphChartCanvas = document.getElementById('board-chart');
    const graphFy = document.getElementById('graph-fy');
    const graphDivision = document.getElementById('graph-division');
    const graphMetricName = document.getElementById('graph-metric-name');
    const graphDivisionName = document.getElementById('graph-division-name');
    const graphOfficeName = document.getElementById('graph-office-name');
    const graphDate = document.getElementById('graph-date');
    const graphDownload = document.getElementById('graph-download');
    let boardChart = null;
    let currentMetric = 'pkg';
    let currentTable = 'revenue';

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

    const loadBoardChart = async () => {
        if (!graphChartCanvas || !graphFy) {
            return;
        }
        const params = new URLSearchParams();
        params.set('table', currentTable);
        params.set('metric', currentMetric);
        params.set('fy_id', graphFy.value);
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
            currentTable = button.getAttribute('data-table') || 'revenue';
            if (graphModal) {
                const title = document.getElementById('graph-title');
                if (title) {
                    title.textContent = currentTable === 'revenue' ? 'Revenue Budget' : 'Development Budget';
                }
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
    if (graphDivision) {
        graphDivision.addEventListener('change', loadBoardChart);
    }

    if (graphDownload && graphChartCanvas) {
        graphDownload.addEventListener('click', () => {
            const meta = [
                `Office: ${graphOfficeName ? graphOfficeName.textContent : ''}`,
                `Division: ${graphDivisionName ? graphDivisionName.textContent : ''}`,
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
            const title = currentTable === 'revenue' ? 'Revenue Budget' : 'Development Budget';
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
            const padding = 8;
            let y = headerHeight + margin + padding + 12;
            meta.forEach((line) => {
                const width = ctx.measureText(line).width;
                ctx.fillText(line, exportCanvas.width - width - padding, y);
                y += 14;
            });
            const link = document.createElement('a');
            link.href = exportCanvas.toDataURL('image/jpeg', 0.9);
            link.download = 'graph.jpg';
            link.click();
        });
    }
});
