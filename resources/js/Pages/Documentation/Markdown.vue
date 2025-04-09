<template>
    <div>
        <portal to="title">
            <h1>Markdown Support</h1>
        </portal>

        <v-section class="overflow-x-auto" :noFooter="true" :noHeader="true">
            <div class="w-full flex flex-wrap max-w-screen-md m-auto text-justify">
                <h1 class="dark:text-white mb-5 flex justify-between w-full">
                    Markdown Support

                    <TextToSpeech source="/tts/docs/markdown.mp3" />
                </h1>

                <p class="mb-3">
                    Any input field that has the markdown logo (<i class="fab fa-markdown"></i>) has basic markdown support.
                </p>

                <img :src="'/images/docs/markdown.png'" class="w-full shadow-md mb-3 border border-gray-500" />

                <p class="mb-3">
                    The supported markdown syntax is limited to...
                </p>

                <p class="mb-3">
                    <b>**bold text**</b>
                </p>

                <p class="mb-3">
                    <i>*italic text*</i>
                </p>

                <p class="mb-3">
                    <del>~~strikethrough text~~</del>
                </p>

                <p class="mb-3">
                    <u>__underlined text__</u>
                </p>

                <p class="mb-3">
                    <code class="py-0.5 px-1 bg-gray-200 dark:bg-gray-800 select-all">`single line inline code`</code>
                </p>

                <div class="mb-3">
                    <pre class="max-w-2xl py-1 px-2 bg-gray-200 dark:bg-gray-800 rounded-sm leading-5 whitespace-pre-wrap break-words"><code>```
code block
that can have
multiple lines
```</code></pre>
                </div>

                <p class="mb-3">
                    [link text](https://example.com) or any other valid url.
                </p>

                <p class="mb-3">
                    ![alt text](https://example.com/image.png) embeds an image.
                </p>

                <p class="mb-5">
                    - unordered list item<br>
                    - another unordered list item
                </p>

                <img :src="'/images/docs/markdown_embed.png'" class="w-full shadow-md border border-gray-500 mb-3" />

                <p class="mb-3">
                    In addition to the basic markdown support, the panel also supports embedding images, videos, etc. from certain providers. The following are supported:
                </p>

                <ul class="w-full pl-8 list-disc">
                    <li>Any valid image url (jpg, png, gif and webp)</li>
                    <li>Any valid video url (mp4, mov, avi, mkv and webm)</li>
                    <li>Twitch videos and clips</li>
                    <li>Youtube videos, shortened urls and livestreams</li>
                    <li>TicketTool transcripts</li>
                    <li>Medal.TV clips</li>
                    <li>ClipChamp videos</li>
                </ul>
            </div>
        </v-section>

        <scoped-style>
            header {
                display: none !important;
            }

            p {
                width: 100%;
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
