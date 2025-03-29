<script>
import { Line } from 'vue-chartjs'

export default {
    extends: Line,
    props: {
        chartData: {
            type: Object,
            required: true
        },
        currency: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            colors: []
        };
    },
    methods: {
        resolveOptions() {
            const formatter = new Intl.NumberFormat('en-US', this.currency ? {
                style: 'currency',
                currency: 'USD',
                maximumFractionDigits: 0
            } : {
                maximumFractionDigits: 0
            }),
                _this = this;

            const gridColor = this.isDarkMode() ? "rgba(180, 180, 180, 0.3)" : "rgba(128, 128, 128, 0.3)",
                textColor = this.isDarkMode() ? "rgba(255, 255, 255, 0.7)" : "rgba(0, 0, 0, 0.7)";

            return {
                showScale: true,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: false,
                            callback: (value, index, values) => {
                                return formatter.format(value);
                            },
                            fontColor: textColor
                        },
                        gridLines: {
                            display: true,
                            color: gridColor
                        }
                    }],
                    xAxes: [{
                        ticks: {
                            display: false
                        },
                        gridLines: {
                            display: false
                        }
                    }]
                },
                tooltips: {
                    enabled: false, // Disable the default tooltips
                    mode: 'index',
                    intersect: false,
                    custom: function (tooltipModel) {
                        let tooltipEl = document.getElementById('chartjs-tooltip');

                        if (!tooltipEl) {
                            tooltipEl = document.createElement('div');

                            tooltipEl.id = 'chartjs-tooltip';
                            tooltipEl.className = 'border-gray-500 bg-gray-300 dark:bg-gray-700 rounded-sm px-2 py-0.5 dark:text-white';

                            document.body.appendChild(tooltipEl);
                        }

                        if (tooltipModel.opacity === 0) {
                            tooltipEl.style.opacity = 0;

                            return;
                        }

                        tooltipEl.classList.remove('above', 'below', 'no-transform');

                        if (tooltipModel.yAlign) {
                            tooltipEl.classList.add(tooltipModel.yAlign);
                        } else {
                            tooltipEl.classList.add('no-transform');
                        }

                        const title = tooltipModel.title.shift(),
                            data = tooltipModel.dataPoints;

                        let html = `<div class="font-semibold">${title}</div><table>`;

                        for (const entry of data) {
                            const dataset = _this.chartData.datasets[entry.datasetIndex],
                                value = dataset.data[entry.index];

                            html += `<tr class="${entry.datasetIndex > 0 ? 'border-t border-gray-500' : ''}">
                                <td class="font-semibold p-0.5">${dataset.label}:</td>
                                <td class="pl-0.5 py-0.5">${formatter.format(value)}</td>
                            </tr>`;
                        }

                        html += '</table>';

                        tooltipEl.innerHTML = html;

                        // Display, position, and set styles for font
                        const position = this._chart.canvas.getBoundingClientRect();

                        tooltipEl.style.opacity = 1;
                        tooltipEl.style.position = 'absolute';
                        tooltipEl.style.left = `${position.left + window.pageXOffset + tooltipModel.caretX}px`;
                        tooltipEl.style.top = `${position.top + window.pageYOffset + tooltipModel.caretY}px`;
                        tooltipEl.style.fontFamily = tooltipModel._bodyFontFamily;
                        tooltipEl.style.fontSize = `${tooltipModel.bodyFontSize}px`;
                        tooltipEl.style.fontStyle = tooltipModel._bodyFontStyle;
                        tooltipEl.style.padding = `${tooltipModel.yPadding}px ${tooltipModel.xPadding}px`;
                        tooltipEl.style.pointerEvents = 'none';
                    }
                },
                legend: {
                    display: false
                },
                responsive: true,
                maintainAspectRatio: false
            };
        },
        chartDataUpdated() {
            this.renderChart(this.chartData, this.resolveOptions());
        }
    },
    mounted() {
        this.chartDataUpdated();

        this.$bus.$on('themeChanged', () => {
            this.chartDataUpdated();
        });
    },
    beforeDestroy() {
        this.$bus.$off('themeChanged');
    },
    watch: {
        chartData() {
            this.chartDataUpdated();
        }
    }
}
</script>