<script>
import { Bar } from 'vue-chartjs';

export default {
    extends: Bar,
    data() {
        const gridColor = this.isDarkMode() ? "rgba(180, 180, 180, 0.3)" : "rgba(128, 128, 128, 0.3)",
            textColor = this.isDarkMode() ? "rgba(255, 255, 255, 0.7)" : "rgba(0, 0, 0, 0.7)";

        let options = {
            devicePixelRatio: 2,
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    display: true,
                    ticks: {
                        beginAtZero: true,
                        fontColor: textColor
                    },
                    gridLines: {
                        display: true,
                        color: gridColor
                    },
                    stacked: true
                }],
                xAxes: [{
                    display: false,
                    gridLines: {
                        display: false
                    },
                    stacked: true
                }]
            },
            legend: {
                display: false
            },
            title: {
                display: true,
                text: this.title,
                fontSize: 13,
                fontColor: textColor
            },
            tooltips: {
                mode: 'index',
                intersect: false,
                itemSort: function (a, b) {
                    return b.datasetIndex - a.datasetIndex
                }
            },
            hover: {
                mode: 'nearest',
                intersect: true
            },
        };

        if (this.data.tooltips) {
            options.tooltips.callbacks = {
                label: (tooltipItem, data) => {
                    const label = this.data.tooltips[tooltipItem.index];

                    return label[tooltipItem.datasetIndex] || "";
                }
            };
        }

        return {
            options: options
        };
    },
    mounted() {
        const data = this.data.data;

        let datasets = [];

        for (let x = 0; x < data.length; x++) {
            while (data[x].length < data[0].length) {
                data[x].unshift(null);
            }

            let bg, fg;

            if (x >= this.colors.length || !this.colors[x]) {
                bg = 'rgba(0, 0, 0, 0)';
                fg = 'rgba(0, 0, 0, 0)';
            } else {
                bg = 'rgba(' + this.colors[x] + ', 0.3)';
                fg = 'rgba(' + this.colors[x] + ', 1)';
            }

            datasets.push({
                label: this.data.names ? this.t(this.data.names[x]) : null,
                data: data[x],
                backgroundColor: bg,
                fill: true,
                borderColor: fg,
                borderWidth: 1,
                grouped: true
            });
        }

        this.renderChart({
            labels: this.data.labels,
            datasets: datasets
        }, this.options);
    },
    props: {
        title: {
            type: String,
            required: true,
        },
        colors: {
            type: Array,
            required: true,
        },
        data: {
            type: Array,
            required: true,
        }
    }
}
</script>
