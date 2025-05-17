<?php
    require_once("includes/session_start.php");
    if (empty($_SESSION["user_id"])) {
        header("Location: login.php");
    }
    require_once("includes/template_header.php");
    require_once("includes/template_navbar.php");
    require_once 'includes/db_connect.php';
    ?>

    <div class="container py-3">
    <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 5px;">
    <h1 class="text-center">Analytics</h1>
</div>

        <!-- Chart Mode Buttons -->
        <div style="margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
        <div id="chartModeButtons">
            <button type="button" class="btn btn-secondary" data-group="date" onclick="loadIncidentData('date')">Date</button>
            <button type="button" class="btn btn-secondary" data-group="type" onclick="loadIncidentData('type')">Type</button>
            <button type="button" class="btn btn-secondary" data-group="severity" onclick="loadIncidentData('severity')">Severity</button>
            <button type="button" class="btn btn-secondary" data-group="asset" onclick="loadIncidentData('asset')">Assets</button>
        </div>
        <div id="dateGroupSelect" style="display: none;">
            <select class="form-select" onchange="changeDateGroup(this.value)">
                <option value="date">Daily</option>
                <option value="month">Monthly</option>
            </select>
        </div>
    </div>

        
        <canvas id="incidentChart"></canvas>
        <!-- Mini Pie Chart -->
        <canvas id="miniPieChart" 
        style="position: absolute; top: 27%; right: 13%; max-width:200px; max-height:200px; display: none;"></canvas>
        <!-- Incident Data Table -->
        <div style="margin-top: 20px;">
            <h4>Incident Details</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm table-hover" id="incidentTable">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Total Incidents</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be injected here -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        let chart; 
        let miniPieChart;
        // Customisation options for each chart
        const colorThemes = {
            date: {
                borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.6)'
            },
            type: {
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.6)'
            },
            severity: {
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.6)'
            },
            asset: {
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.6)'
            },
            month: { 
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.6)'
        }
        };
        function setActiveButton(selectedGroup) {
            const buttonsContainer = document.getElementById('chartModeButtons');
            // Select only buttons that are meant to show an active state for data grouping
            const buttons = buttonsContainer.querySelectorAll('button[data-group]'); 
            
            buttons.forEach(button => {
                // Handle 'month' being a sub-state of 'date' conceptually for the 'Date' button
                if (button.dataset.group === selectedGroup || (selectedGroup === 'month' && button.dataset.group === 'date')) {
                    button.classList.add('btn-custom-active'); // Add your custom active class
                    button.classList.remove('btn-secondary'); // Remove default styling if needed
                } else {
                    button.classList.remove('btn-custom-active');
                    // Ensure it reverts to btn-secondary if not active (and not the print button)
                    if (!button.classList.contains('btn-primary')) { // Don't change print button
                         button.classList.add('btn-secondary');
                    }
                }
            });
        }

        async function loadIncidentData(groupBy = 'date') {

            setActiveButton(groupBy);
            let url = `get_incidents.php?group_by=${groupBy}`;

            // Show or hide the date grouping select
            const dateGroupSelect = document.getElementById('dateGroupSelect');

            if (groupBy === 'date' || groupBy === 'month') {
                dateGroupSelect.style.display = 'block';

                // Set select value
                dateGroupSelect.querySelector('select').value = groupBy;
            } else {
                dateGroupSelect.style.display = 'none';
            }


            const response = await fetch(url);
            const incidentData = await response.json();

            let labels = incidentData.map(item => item.label);

            let formattedLabels = labels;

            // Format YYYY-MM to "Month YYYY" only if groupBy === 'month'
            if (groupBy === 'month') {
                formattedLabels = labels.map(label => {
                    const [year, month] = label.split('-');
                    if (year && month) {
                        const date = new Date(year, month - 1);
                        return date.toLocaleString('default', { month: 'long', year: 'numeric' });
                    } else {
                        return label; // fallback
                    }
                });
            }


            const dataCounts = incidentData.map(item => item.total);

            // Populate Table
            const tableBody = document.querySelector('#incidentTable tbody');
            tableBody.innerHTML = ''; // Clear old rows

            incidentData.forEach(item => {
                let row = `<tr>
                    <td>${item.label}</td>
                    <td>${item.total}</td>
                </tr>`;
                tableBody.innerHTML += row;
            });
            
            // Destroy old chart if exists
            if (chart) {
                chart.destroy();
            }

            const ctx = document.getElementById('incidentChart').getContext('2d');

            // Chart type switch
            let chartType = (groupBy === 'date' || groupBy === 'month') ? 'line' : 'bar';

            // Get colors based on groupBy
            let colors = colorThemes[groupBy];

            chart = new Chart(ctx, {
                type: chartType,
                data: {
                    labels: formattedLabels,
                datasets: [{
                    label: 'Total Incidents',
                    data: dataCounts,
                    minBarLength: 10,
                    borderColor: colors.borderColor,
                    backgroundColor: colors.backgroundColor,
                    fill: (groupBy === 'date' || groupBy === 'month') ? false : true,
                    tension: 0.3
                    }]
                },
                options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: groupBy.charAt(0).toUpperCase() + groupBy.slice(1),
                        font: {
                            size: 16,  // Font size for x-axis title
                            family: 'Arial, sans-serif', // Font family
                            weight: 'bold', // Bold text
                            color: '#4a4a4a'  // Font color
                        }
                    },
                    ticks: {
                        font: {
                            size: 12,  // Font size for x-axis labels
                            family: 'Arial, sans-serif', // Font family
                            color: '#4a4a4a'  // Font color for ticks
                        }
                    },
                    grid: {
                    color: 'rgba(255, 255, 255, 0.2)', // Lighten X-axis grid lines
                    // You can also control other grid properties here, like:
                    // drawBorder: false, // If you don't want the border line of the axis
                    // display: true, // Ensure grid lines are displayed (true by default)
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
                        text: 'Number of Incidents',
                        font: {
                            size: 16,  // Font size for y-axis title
                            family: 'Arial, sans-serif', // Font family
                            weight: 'bold', // Bold text
                            color: '#4a4a4a'  // Font color
                        }
                    },
                    ticks: {
                        autoSkip: true, // Skip ticks automatically if they get crowded
                        maxTicksLimit: 10, // Show up to 10 ticks max (keeps axis clean)
                        precision: 0, // Whole numbers only
                        font: {
                            size: 12,  // Font size for y-axis labels
                            family: 'Arial, sans-serif', // Font family
                            color: '#4a4a4a'  // Font color for ticks
                        },
                        // Add format for y-axis ticks if needed (e.g., add thousand separator)
                        callback: function(value) {
                            return value.toLocaleString();  // Adds thousands separator
                        }
                    },
                    grid: {
                    color: 'rgba(255, 255, 255, 0.2)', // Lighten X-axis grid lines
                    // You can also control other grid properties here, like:
                    // drawBorder: false, // If you don't want the border line of the axis
                    // display: true, // Ensure grid lines are displayed (true by default)
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#999',  // Darker legend text color
                        font: {
                            size: 14,  // Font size for legend labels
                            family: 'Arial, sans-serif',  // Font family
                            weight: 'bold'  // Font weight
                        }
                    }
                },
                tooltip: {
                    bodyFont: {
                        size: 14,  // Font size for tooltip body
                        family: 'Arial, sans-serif',  // Font family for tooltip
                        weight: 'normal',  // Normal weight for tooltip body
                        color: '#333'  // Tooltip text color
                    },
                    titleFont: {
                        size: 16,  // Font size for tooltip title
                        family: 'Arial, sans-serif',  // Font family for tooltip title
                        weight: 'bold',  // Bold weight for tooltip title
                        color: '#333'  // Tooltip title text color
                    },
                    backgroundColor: 'rgba(0, 0, 0, 0.7)',  // Tooltip background color
                    titleColor: '#fff',  // Tooltip title text color
                    bodyColor: '#fff'   // Tooltip body text color
                        }
                    }
                }
            });

                // Show mini pie only for severity mode
                const miniPieCanvas = document.getElementById('miniPieChart');
                if (groupBy === 'severity') {
                    miniPieCanvas.style.display = 'block';
                    renderMiniPie(labels, dataCounts);
                } else {
                    miniPieCanvas.style.display = 'none';
                    if (miniPieChart) miniPieChart.destroy();
                }


        }   
        
        function renderMiniPie(labels, dataCounts) {
            const ctxPie = document.getElementById('miniPieChart').getContext('2d');

            // Destroy old mini chart if exists
            if (miniPieChart) {
                miniPieChart.destroy();
            }

            miniPieChart = new Chart(ctxPie, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: dataCounts,
                        backgroundColor: [
                            'rgba(0, 200, 83, 0.7)',   // green
                            'rgba(255, 143, 0, 0.7)',  // orange
                            'rgba(229, 57, 53, 0.7)',  // red
                            'rgba(103, 58, 183, 0.7)'  // purple
                        ],
                        borderWidth: 1
                    }]
                },
                    options: {
                      responsive: true,
                        maintainAspectRatio: true,  // ✅ Keep it a circle
                        aspectRatio: 1,             // ✅ Force 1:1 ratio (circle)
                        plugins: {
                            legend: { // Legend for the mini pie chart
                            display: true, // <<<<------ IMPORTANT: Enable the legend
                            position: 'bottom', // Position it below the pie
                            labels: {
                                font: {
                                    size: 10, // Smaller font for a mini chart legend
                                    family: 'Arial, sans-serif'
                                },
                                color: '#999', // Good contrast for print
                                boxWidth: 15, // Width of the color box
                                padding: 8     // Padding between legend items
                            }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        let value = context.parsed || 0;
                                        return `${label}: ${value}`;
                                }
                            }
                        }
                    }
                }

            });
        }
                


            function changeDateGroup(value) {
            loadIncidentData(value);
            }
            
            // Load by Date on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadIncidentData('date');   
        });
    </script>

    <?php
    require_once("includes/template_footer.php");
    ?>
