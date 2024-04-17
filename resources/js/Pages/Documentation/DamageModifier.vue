<template>
    <div>
        <portal to="title">
            <h1>Anti-Cheat: Invalid Damage Modifier</h1>
        </portal>

        <v-section class="overflow-x-auto" :noFooter="true" :noHeader="true">
            <div class="w-full flex flex-wrap max-w-screen-md m-auto text-justify">
                <h1 class="dark:text-white mb-5">
                    Anti-Cheat: Invalid Damage Modifier
                </h1>

                <p class="mb-3">
                    Every weapon has its damage hardcoded in its respective .meta file. This value defines the base damage of each weapon.
                </p>

                <img src="/images/docs/damage_meta.png" class="w-full shadow-md border border-gray-500 mb-1" />
                <caption class="mb-3 text-gray-700 dark:text-gray-200 text-xs">
                    Example of the base damage being set in a .meta file
                </caption>

                <p class="mb-3">
                    To calculate the damage of a weapon, the game multiplies the base damage with the damage modifier. In the OP-Framework
                    each weapon has a different damage modifier which is used to make balancing easier.
                </p>
                <p class="mb-3">
                    If a player switches their weapon, the framework will apply the preset damage modifier of the new weapon. Every time a
                    shot is fired, the anti-cheat will check if the damage modifier is the same as the one that was set when the player
                    switched their weapon. If the damage modifier is different, the anti-cheat will trigger a detection, create a
                    screencapture and notify online staff members.
                </p>

                <img src="/images/docs/damage_modifier.png" class="w-full shadow-md border border-gray-500 mb-1" />
                <caption class="mb-3 text-gray-700 dark:text-gray-200 text-xs">
                    Example anti-cheat log. (Modifier is supposed to be 0.5 but was set to 10.0)
                </caption>

                <p>
                    In addition to that the anti-cheat will also reset the modifier to its supposed value. This means that if it is triggered
                    multiple times in a row, the player is most likely using some form of mod menu that is continuously overriding the damage
                    modifier.
                </p>
            </div>
        </v-section>

        <scoped-style>
            header {
                display: none !important;
            }
        </scoped-style>
    </div>
</template>

<script>
import Layout from './../../Layouts/App';
import VSection from './../../Components/Section';

export default {
    layout: Layout,
    components: {
        VSection,
    },
}
</script>
