const Wait = {
    async install(Vue, options) {
        Vue.prototype.waitTick = function() {
            return new Promise(resolve => this.$nextTick(resolve));
        };

        Vue.prototype.wait = function(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        };

        Vue.prototype.waitTicks = async function(ticks) {
            for (let i = 0; i < ticks; i++) {
                await this.waitTick();
            }
        };
    },
}

export default Wait;
