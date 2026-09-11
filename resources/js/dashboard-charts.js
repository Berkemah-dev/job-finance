import Chart from 'chart.js/auto';

function formatNumber(val) {
    if (typeof val !== 'number') {
        const parsed = Number(val);
        if (isNaN(parsed)) return val;
        val = parsed;
    }
    if (Math.abs(val) >= 1000000000) {
        return 'Rp ' + (val / 1000000000).toFixed(1).replace('.', ',') + ' M';
    }
    if (Math.abs(val) >= 1000000) {
        return 'Rp ' + (val / 1000000).toFixed(1).replace('.', ',') + ' jt';
    }
    if (Math.abs(val) >= 10000) {
        return 'Rp ' + Math.round(val).toLocaleString('id-ID');
    }
    return Math.round(val).toLocaleString('id-ID');
}

function initDashboardCharts() {
    const mainCanvas = document.getElementById('dashboardMainChart');
    const donutCanvas = document.getElementById('dashboardDonutChart');

    if (mainCanvas && mainCanvas.dataset.chart) {
        try {
            const rawMain = JSON.parse(mainCanvas.dataset.chart);
            const ctx = mainCanvas.getContext('2d');

            if (mainCanvas._chartInstance) {
                mainCanvas._chartInstance.destroy();
            }

            // Create gradient fills for area datasets
            const datasets = rawMain.datasets.map((ds) => {
                const copy = { ...ds };
                if (copy.type === 'line' || rawMain.type === 'line') {
                    if (copy.fill && copy.borderColor) {
                        const gradient = ctx.createLinearGradient(0, 0, 0, 260);
                        if (copy.borderColor.includes('16, 185, 129') || copy.borderColor === '#10b981') {
                            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.28)');
                            gradient.addColorStop(1, 'rgba(16, 185, 129, 0.01)');
                        } else if (copy.borderColor.includes('59, 130, 246') || copy.borderColor === '#3b82f6') {
                            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.28)');
                            gradient.addColorStop(1, 'rgba(59, 130, 246, 0.01)');
                        } else {
                            gradient.addColorStop(0, 'rgba(15, 31, 61, 0.18)');
                            gradient.addColorStop(1, 'rgba(15, 31, 61, 0.01)');
                        }
                        copy.backgroundColor = gradient;
                    }
                }
                return copy;
            });

            mainCanvas._chartInstance = new Chart(ctx, {
                type: rawMain.type || 'line',
                data: {
                    labels: rawMain.labels,
                    datasets: datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 12,
                                borderRadius: 3,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: {
                                    family: "'Poppins', sans-serif",
                                    size: 11,
                                    weight: 500,
                                },
                                color: '#64748b',
                                padding: 14,
                            },
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 31, 61, 0.95)',
                            titleFont: {
                                family: "'Poppins', sans-serif",
                                size: 12,
                                weight: 600,
                            },
                            bodyFont: {
                                family: "'Poppins', sans-serif",
                                size: 11,
                            },
                            padding: 10,
                            cornerRadius: 8,
                            boxPadding: 4,
                            callbacks: {
                                label: function (context) {
                                    const label = context.dataset.label || '';
                                    const value = context.parsed.y !== null ? context.parsed.y : context.raw;
                                    return `${label}: ${formatNumber(value)}`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                font: {
                                    family: "'Poppins', sans-serif",
                                    size: 10,
                                    weight: 500,
                                },
                                color: '#94a3b8',
                            },
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(226, 232, 240, 0.7)',
                                drawBorder: false,
                            },
                            ticks: {
                                font: {
                                    family: "'Poppins', sans-serif",
                                    size: 10,
                                },
                                color: '#94a3b8',
                                callback: function (val) {
                                    if (val >= 1000000) {
                                        return (val / 1000000).toFixed(0) + ' jt';
                                    }
                                    if (val >= 1000) {
                                        return (val / 1000).toFixed(0) + ' k';
                                    }
                                    return val;
                                },
                            },
                        },
                    },
                },
            });
        } catch (e) {
            console.error('Error initializing main dashboard chart:', e);
        }
    }

    if (donutCanvas && donutCanvas.dataset.chart) {
        try {
            const rawDonut = JSON.parse(donutCanvas.dataset.chart);
            const ctx = donutCanvas.getContext('2d');

            if (donutCanvas._chartInstance) {
                donutCanvas._chartInstance.destroy();
            }

            donutCanvas._chartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: rawDonut.labels,
                    datasets: [
                        {
                            data: rawDonut.data,
                            backgroundColor: rawDonut.colors || [
                                '#10b981',
                                '#3b82f6',
                                '#f59e0b',
                                '#8b5cf6',
                                '#ef4444',
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 6,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                borderRadius: 3,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: {
                                    family: "'Poppins', sans-serif",
                                    size: 10,
                                },
                                color: '#64748b',
                                padding: 8,
                            },
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 31, 61, 0.95)',
                            titleFont: {
                                family: "'Poppins', sans-serif",
                                size: 11,
                                weight: 600,
                            },
                            bodyFont: {
                                family: "'Poppins', sans-serif",
                                size: 10,
                            },
                            padding: 8,
                            cornerRadius: 6,
                            callbacks: {
                                label: function (context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    return `${label}: ${formatNumber(value)}`;
                                },
                            },
                        },
                    },
                },
            });
        } catch (e) {
            console.error('Error initializing donut dashboard chart:', e);
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboardCharts);
} else {
    initDashboardCharts();
}
