<?php
require_once("includes/template_header.php");
require_once("includes/template_navbar.php");
require_once 'includes/db_connect.php';
?>

<div class="container">
    <h2>Incident Trends</h2>

    <!-- Chart Mode Buttons -->
    <div style="margin-bottom: 10px;">
        <button type="button" class="btn btn-secondary" onclick="loadIncidentData('date')">By Date</button>
        <button type="button" class="btn btn-secondary" onclick="loadIncidentData('type')">By Type</button>
        <button type="button" class="btn btn-secondary" onclick="loadIncidentData('severity')">By Severity</button>
    </div>

    <canvas id="incidentChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let chart; 

    // Color themes for each chart type
    const colorThemes = {
        date: {
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.4)'
        },
        type: {
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.6)'
        },
        severity: {
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.6)'
        }
    };

    async function loadIncidentData(groupBy = 'date') {
        let url = `get_incidents.php?group_by=${groupBy}`;

        const response = await fetch(url);
        const incidentData = await response.json();

        const labels = incidentData.map(item => item.label);
        const dataCounts = incidentData.map(item => item.total);

        // Destroy old chart if exists
        if (chart) {
            chart.destroy();
        }

        const ctx = document.getElementById('incidentChart').getContext('2d');

        // Chart type switch
        let chartType = groupBy === 'date' ? 'line' : 'bar';

        // Get colors based on groupBy
        let colors = colorThemes[groupBy];

        chart = new Chart(ctx, {
            type: chartType,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Incidents',
                    data: dataCounts,
                    minBarLength: 10,
                    borderColor: colors.borderColor,
                    backgroundColor: colors.backgroundColor,
                    fill: groupBy === 'date' ? false : true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true, // Uses parent div height
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: groupBy.charAt(0).toUpperCase() + groupBy.slice(1)
                        }
                    },
                    y: {
                        beginAtZero: false, 
                        min: 0,
                        suggestedMax: function() {
                        const maxDataValue = Math.max(...dataCounts); // Get the max value from data
                        const maxOverhead = maxDataValue * 0.2; // 20% of the max value as overhead
                        return maxDataValue + maxOverhead; // Add overhead to max value
                        },
                        title: {
                            display: true,
                            text: 'Number of Incidents'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#333' // Darker legend text
                        }
                    }
                }
            }
        });
    }

    // Load by Date on page load
    loadIncidentData('date');
</script>

<?php
require_once("includes/template_footer.php");
?>
