<template>
    <div v-if="players.length > 0">
        <h3 class="mb-2">{{ title }} <sup>{{ players.length }}</sup></h3>

        <table class="text-sm font-mono font-medium">
            <tr v-for="(player, x) in players" :key="x" :title="player.title">
                <td class="pr-2">
                    <a target="_blank" :href="'/players/' + player.license" :class="finalColor(player)">
                        {{ usePlayerName ? player.playerName : player.name }}
                    </a>
                </td>
                <td :class="'pr-2 ' + finalColor(player)">
                    ({{ player.source }})
                </td>
                <td>
                    <a :class="'track-cid ' + finalColor(player)" href="#" data-popup="true" :title="t('map.track')" @click="track($event, player.source)">
                        {{ t('map.short.track') }}
                    </a>
                </td>
            </tr>
        </table>
    </div>
</template>
<script>
export default {
    name: "SimplePlayerList",
    props: {
        players: {
            type: Array,
            required: true
        },
        title: {
            type: String,
            required: true
        },
        color: {
            type: String,
            required: true
        },
        usePlayerName: {
            type: Boolean,
            default: false
        }
    },
    methods: {
        finalColor(player) {
            if (player.color) {
                return player.color;
            }

            return this.color;
        },
        track(e, source) {
            e.preventDefault();

            this.$emit('track', source);
        }
    }
}
</script>
