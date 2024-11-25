<template>
    <div class="w-full overflow-hidden" :class="height" ref="wrapper">
        <canvas ref="chart" width="0" height="0"></canvas>
    </div>
</template>

<script>
export default {
    name: 'SimpleChart',
    props: {
        data: {
            type: Array,
            required: true,
        },
        height: {
            type: String,
        },
        lines: {
            type: Boolean,
            default: true
        },
        amounts: {
            type: Array,
            default: () => ['amount']
        },
        labels: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            colors: {}
        };
    },
    methods: {
        calculateCeiling(data) {
            const max = Math.ceil(Math.max(...data.map(entry => {
                return Math.max(...this.amounts.map(amount => entry[amount]));
            })) * 1.2);

            if (max === 0) return 0;

            const log = Math.pow(10, Math.floor(Math.log10(max)));

            return Math.ceil(max / log) * log;
        },
        calculateFloor(data) {
            const min = Math.min(...data.map(entry => {
                return Math.min(...this.amounts.map(amount => entry[amount]));
            }));

            if (min >= 0) return 0;

            const log = Math.pow(10, Math.floor(Math.log10(Math.abs(min))));

            return Math.floor(Math.abs(min) / log) * log * -1;
        },
        color(amount) {
            if (this.amounts.length === 1) {
                return this.themeColor('gray-400');
            }

            if (!this.colors[amount]) {
                const index = this.amounts.indexOf(amount),
                    steps = 360 / this.amounts.length;

                return `hsl(${steps * index}, 35%, 65%)`;
            }

            return this.colors[amount];
        },
        async render() {
            await this.$nextTick();

            const canvas = this.$refs.chart,
                wrapper = this.$refs.wrapper,
                ctx = canvas.getContext('2d');

            canvas.width = wrapper.offsetWidth;
            canvas.height = wrapper.offsetHeight;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const data = JSON.parse(JSON.stringify(this.data)); // Copy the data

            data.reverse();

            const width = canvas.width - 2,
                height = canvas.height - 2,
                step = width / (data.length - 1),
                ceiling = this.calculateCeiling(data),
                floor = this.calculateFloor(data);

            // Map floor<->ceiling to 0<->1
            function map(value) {
                return (value - floor) / (ceiling - floor);
            }

            function y(value) {
                if (ceiling === 0 && floor === 0) return 1 + height;

                return 1 + (height - (map(value) * height));
            }

            // Draw the line graph
            for (let index = 0; index < this.amounts.length; index++) {
                const amount = this.amounts[index],
                    label = index < this.labels.length ? this.labels[index] : false;

                if (label) {
                    ctx.textAlign = 'left';
                    ctx.textBaseline = 'bottom';
                    ctx.font = '600 12px "Montserrat", sans-serif';
                    ctx.fillStyle = this.color(amount);
                    ctx.fillText(label, 2, (12 * (index + 1)) + (2 * (index + 1)));
                }

                ctx.beginPath();
                ctx.moveTo(1, y(data[0][amount]));

                for (let i = 1; i < data.length; i++) {
                    const x = 1 + i * step,
                        yi = y(data[i][amount]);

                    ctx.lineTo(x, yi);
                }

                ctx.lineWidth = 2;
                ctx.strokeStyle = this.color(amount);

                ctx.stroke();
                ctx.closePath();
            }

            // Draw the x axis lines
            if (this.lines) {
                ctx.beginPath();

                for (let i = 0; i <= data.length; i++) {
                    const x = 1 + i * step;

                    ctx.moveTo(x, 0);
                    ctx.lineTo(x, canvas.height);
                }

                ctx.lineWidth = 1;
                ctx.strokeStyle = this.themeColor('gray-400', 0.2);

                ctx.stroke();
                ctx.closePath();

                // Draw the y axis line
                const y0 = y(0);

                ctx.beginPath();

                ctx.moveTo(0, y0);
                ctx.lineTo(canvas.width, y0);

                ctx.lineWidth = 1;
                ctx.strokeStyle = this.themeColor('gray-400', 0.4);

                ctx.stroke();
                ctx.closePath();
            }
        }
    },
    mounted() {
        this.render();
    }
}
</script>
