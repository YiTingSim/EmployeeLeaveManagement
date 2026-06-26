document.addEventListener("DOMContentLoaded", function () {
    //locate the canvas element container
    const chartCanvas = document.getElementById('leaveStatusChart');
    if (!chartCanvas) return; // Prevent script crash if page elements haven't loaded

    //safely read dataset values stored inside HTML data-attributes
    const approved = parseInt(chartCanvas.getAttribute('data-approved')) || 0;
    const pending  = parseInt(chartCanvas.getAttribute('data-pending')) || 0;
    const rejected = parseInt(chartCanvas.getAttribute('data-rejected')) || 0;

    // 3. Initialize Chart configuration
    const ctx = chartCanvas.getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Approved', 'Pending', 'Rejected'],
            datasets: [{
                data: [approved, pending, rejected],
                backgroundColor: [
                    '#10b981', // Emerald green
                    '#f59e0b', // Amber yellow
                    '#ef4444'  // Rose red
                ],
                borderWidth: 2,
                borderColor: '#12141c', // Matches dark card surface layout
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,		//Make it flexible and resizable
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#9ca3af',
                        font: {
                            family: "'Inter', sans-serif",
                            size: 12
                        },
                        padding: 16
                    }
                },
                tooltip: {
                    boxPadding: 6,
                    usePointStyle: true
                }
            },
            cutout: '75%'
        }
    });

    // 2. Initialize Type Chart (Bar Chart)
    const typeCanvas = document.getElementById('leaveTypeChart');
    if (typeCanvas) {
        const annual    = parseInt(typeCanvas.getAttribute('data-annual')) || 0;
        const medical   = parseInt(typeCanvas.getAttribute('data-medical')) || 0;
        const maternity = parseInt(typeCanvas.getAttribute('data-maternity')) || 0;
        const casual    = parseInt(typeCanvas.getAttribute('data-casual')) || 0;

        const ctx2 = typeCanvas.getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['Annual', 'Medical', 'Maternity', 'Casual'],
                datasets: [{
                    label: 'Requests',
                    data: [annual, medical, maternity, casual],
                    backgroundColor: [
                        '#0f43ff', // Annual
                        '#06b6d4', // Medical
                        '#f43f5e', // Maternity
                        '#d630ff'  // Casual
                    ],
                    borderRadius: 4,
                    barPercentage: 0.5,       // Makes bars slim
                    categoryPercentage: 0.5   // Makes bars slim
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { 
                    y: { beginAtZero: true, grid: { color: '#374151' }, ticks: { color: '#9ca3af' } },
                    x: { grid: { display: false }, ticks: { color: '#9ca3af' } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }
});