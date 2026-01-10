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
});
