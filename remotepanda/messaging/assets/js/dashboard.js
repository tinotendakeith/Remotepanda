(function ($) {
    'use strict';
    $(async () => {

        if ($('#history-chart').length) {
            const DateTime = window.luxon.DateTime;

            const response = await fetch(window.hillpaul.base_url + "/api/history", {
                headers: {
                    'X-Requested-With': 'xmlhttprequest'
                },
            }).then(response => response.json());

            const historyData = window._.countBy(response.data, (item) => {
                return DateTime.fromSeconds(item.date).startOf("day").toSeconds()
            });

            const weekHistoryCanvas = $("#history-chart").get(0).getContext("2d");
            const data = {
                labels: Object.keys(historyData).map((item) => {
                    return DateTime.fromSeconds(parseInt(item)).toFormat("LLL dd")
                }),
                datasets: [
                    {
                        label: 'Sent Messages',
                        data: Object.values(historyData),
                        borderColor: [
                            '#ff4747'
                        ],
                        borderWidth: 2,
                        fill: false,
                        pointBackgroundColor: "#fff"
                    }
                ]
            };

            const graphMin = Math.floor(Math.min(...Object.values(historyData)) * 0.9);
            const graphMax = Math.ceil(Math.max(...Object.values(historyData)) * 1.1);

            const options = {
                scales: {
                    yAxes: [{
                        display: true,
                        gridLines: {
                            drawBorder: false,
                            lineWidth: 1,
                            color: "#e9e9e9",
                            zeroLineColor: "#e9e9e9",
                        },
                        ticks: {
                            min: graphMin,
                            max: graphMax,
                            stepSize: 20,
                            fontColor: "#6c7383",
                            fontSize: 16,
                            fontStyle: 300,
                            padding: 15
                        }
                    }],
                    xAxes: [{
                        display: true,
                        gridLines: {
                            drawBorder: false,
                            lineWidth: 1,
                            color: "#e9e9e9",
                        },
                        ticks: {
                            fontColor: "#6c7383",
                            fontSize: 16,
                            fontStyle: 300,
                            padding: 15
                        }
                    }]
                },
                legend: {
                    display: false
                },
                legendCallback: function (chart) {
                    const text = [];
                    text.push('<ul class="dashboard-chart-legend">');
                    for (let i = 0; i < chart.data.datasets.length; i++) {
                        text.push('<li><span style="background-color: ' + chart.data.datasets[i].borderColor[0] + ' "></span>');
                        if (chart.data.datasets[i].label) {
                            text.push(chart.data.datasets[i].label);
                        }
                    }
                    text.push('</ul>');
                    return text.join("");
                },
                elements: {
                    point: {
                        radius: 3
                    },
                    line: {
                        tension: 0
                    }
                },
                stepsize: 1,
                layout: {
                    padding: {
                        top: 0,
                        bottom: -10,
                        left: -10,
                        right: 0
                    }
                }
            };
            const cashDeposits = new Chart(weekHistoryCanvas, {
                type: 'line',
                data: data,
                options: options
            });
            document.getElementById('history-chart-legend').innerHTML = cashDeposits.generateLegend();
        }

    });
})(jQuery);