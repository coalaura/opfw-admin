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
        }
    },
    methods: {
        calculateCeiling(data) {
            const max = Math.max(...data);

            if (max === 0) return 0;

            const log = Math.pow(10, Math.floor(Math.log10(max)));

            return Math.ceil(max / log) * log;
        },
        calculateFloor(data) {
            const min = Math.min(...data);

            if (min >= 0) return 0;

            const log = Math.pow(10, Math.floor(Math.log10(Math.abs(min))));

            return Math.floor(Math.abs(min) / log) * log * -1;
        },
        async render() {
            await this.$nextTick();

            const canvas = this.$refs.chart,
                wrapper = this.$refs.wrapper,
                ctx = canvas.getContext('2d');

            canvas.width = wrapper.offsetWidth;
            canvas.height = wrapper.offsetHeight;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const data = this.data.map(entry => entry.amount);

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
            ctx.beginPath();
            ctx.moveTo(1, y(data[0]));

            for (let i = 1; i < data.length; i++) {
                const x = 1 + i * step,
                    yi = y(data[i]);

                ctx.lineTo(x, yi);
            }

            ctx.lineWidth = 2;
            ctx.strokeStyle = this.themeColor('gray-400');

            ctx.stroke();
            ctx.closePath();

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
