<template>
    <div v-if="players.length > 0">
        <h3 class="mb-2">{{ title }} <sup>{{ players.length }}</sup></h3>

        <table class="text-sm font-mono font-medium">
            <tr v-for="(player, x) in players" :key="x">
                <td class="pr-2">
                    <a target="_blank" :href="'/players/' + player.license" :class="color">
                        {{ usePlayerName ? player.playerName : player.name }}
                    </a>
                </td>
                <td :class="'pr-2 ' + color">
                    ({{ player.source }})
                </td>
                <td>
                    <a :class="'track-cid ' + color" href="#" data-popup="true" :title="t('map.track')" @click="track($event, player.source)">
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
        track(e, source) {
            e.preventDefault();

            this.$emit('track', source);
        }
    }
}
</script>
