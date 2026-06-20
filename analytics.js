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
            maintainAspectRatio: false,
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
});