<template>
    <div>
        <portal to="title">
            <h1>Info: What Are Hashes?</h1>
        </portal>

        <v-section class="overflow-x-auto" :noFooter="true" :noHeader="true">
            <div class="w-full flex flex-wrap max-w-screen-md m-auto text-justify">
                <h1 class="dark:text-white mb-5 flex justify-between w-full">
                    Info: What Are Hashes?

                    <TextToSpeech source="/tts/docs/hashes.mp3" />
                </h1>

                <p class="mb-3">
                    In the game, many objects, weapons, vehicles, and other entities are identified by unique names, such as "weapon_pistol"
                    for a pistol or "adder" for a specific car model. However, instead of using these names directly, the game often converts
                    them into a special type of number called a "hash." A hash is essentially a unique identifier generated from the name of
                    the object. Think of it like a barcode for items in the game, it's a way for the game to quickly recognize and work with
                    objects without needing to store or process their full names.
                </p>

                <p class="mb-3">
                    The type of hash used in the game is called a "JOAAT hash" (short for Jenkins One At A Time). While the name might sound
                    technical, all you need to know is that it's a mathematical way to turn a name like "weapon_pistol" into a number, such
                    as <code>453432689</code>. This number is unique to the name it represents, meaning no two names will have the same hash.
                </p>

                <p class="mb-3">
                    Hashes are used in many places in the game because they are faster for the game to process than names. For example, when
                    a player fires a weapon, the game uses the hash to identify which weapon was used. Similarly, when a vehicle or prop is
                    spawned, the game uses the hash to identify the model of the spawned entity.
                </p>

                <img :src="'/images/docs/hash_example.png'" class="w-full shadow-md border border-gray-500 mb-1" />
                <caption class="mb-3 text-gray-700 dark:text-gray-200 text-xs">
                    Example of a damage log showing a hash instead of a weapon name
                </caption>

                <p class="mb-3">
                    Hashes may appear in damage logs or anti-cheat events. In damage logs, hashes are often used to represent weapon names,
                    such as "weapon_pistol" or "weapon_explosion." If the system cannot find the name that matches a hash, it will display
                    the hash number instead. This does not mean anything suspicious is happening, it simply means the hash is not in the list
                    of known names. For example, if a player uses a weapon or item that is not yet added to the list, the hash will show up
                    instead of the name.
                </p>

                <p class="mb-3">
                    In anti-cheat events, hashes can appear when the anti-cheat detects something unusual, such as a player spawning a car
                    or prop. In these cases, the hash represents the model of the spawned entity. If the anti-cheat logs show a hash instead
                    of a name, it simply means the model is not in the list of known names, not necessarily that the player is cheating.
                </p>

                <p class="mb-3">
                    To summarize, hashes are just unique numbers that represent names of objects in the game. They are used because they are
                    faster for the game to process. If a hash shows up instead of a name, it usually means the name is not in the list of
                    known names, and it does not automatically mean anything suspicious is happening.
                </p>

                <p class="mb-3">
                    If you're curious about hashes and want to experiment with them, you can use <a href="https://joaat.sh" target="_blank"
                    class="text-blue-500 hover:underline">joaat.sh</a>. This tool allows you to input a hash and see what name
                    it represents, or input a name to see its hash. It's a great way to better understand how hashes work in the game.
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
import Layout from './../../Layouts/App.vue';
import VSection from './../../Components/Section.vue';
import TextToSpeech from './../../Components/TextToSpeech.vue';

export default {
    layout: Layout,
    components: {
        VSection,
        TextToSpeech,
    },
}
</script>
