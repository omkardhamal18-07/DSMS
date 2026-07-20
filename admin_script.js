// admin_script.js
document.addEventListener("DOMContentLoaded", function () {

    // Sidebar toggle functionality
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');

    if (sidebarCollapse && sidebar) {
        sidebarCollapse.addEventListener('click', function () {
            sidebar.classList.toggle('active');
        });
    }

    // Chart.js Default Configurations
    Chart.defaults.font.family = 'Nunito, -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
    Chart.defaults.color = '#858796';

    // 1. Monthly Usage Bar Chart
    const ctxMonthly = document.getElementById('monthlyUsageChart');
    if (ctxMonthly) {
        new Chart(ctxMonthly, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Stationery Issued',
                    backgroundColor: '#4e73df',
                    hoverBackgroundColor: '#2e59d9',
                    borderColor: '#4e73df',
                    data: [4215, 5312, 6251, 7841, 9821, 14984, 11025, 8541, 10542, 12514, 11542, 10245],
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { grid: { display: false, drawBorder: false } },
                    y: {
                        grid: { color: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2] },
                        ticks: { maxTicksLimit: 5, padding: 10 }
                    }
                }
            }
        });
    }

    // 2. Inventory Pie Chart
    const ctxPie = document.getElementById('inventoryPieChart');
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Paper', 'Writing Tools', 'Office Supplies', 'Electronics'],
                datasets: [{
                    data: [45, 25, 20, 10],
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
                    hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } }
                },
                cutout: '70%',
            }
        });
    }

    // 3. Weekly Requests Line Chart
    const ctxWeekly = document.getElementById('weeklyRequestsChart');
    if (ctxWeekly) {
        new Chart(ctxWeekly, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Requests',
                    lineTension: 0.3,
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "rgba(78, 115, 223, 1)",
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: [15, 25, 10, 30, 22, 5, 2],
                    fill: true,
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { grid: { display: false, drawBorder: false } },
                    y: {
                        grid: { color: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2] },
                        ticks: { maxTicksLimit: 5, padding: 10 }
                    }
                }
            }
        });
    }
});
