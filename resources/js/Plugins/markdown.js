const Markdown = {
    async install(Vue, options) {
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;");
        }

        function find(rgx, str, index = 1) {
            const match = rgx.exec(str);

            return match && match.length >= index ? match[index] : null;
        }

        function embed(url) {
            // Twitch Videos
            if (url.startsWith("https://www.twitch.tv/videos/")) {
                const id = find(/videos\/(\d+)/gm, url),
                    time = find(/[?&]t=(\w+)/gm, url) || '00h00m00s';

                if (!id) return false;

                return {
                    text: `twitch.tv/${id}`,
                    url: `https://player.twitch.tv/?video=${id}&time=${time}&parent=${window.location.hostname}`
                };
            }

            // Twitch Clips
            if (url.startsWith("https://clips.twitch.tv/")) {
                const id = find(/clips.twitch.tv\/(\w+)/gm, url);

                if (!id) return false;

                return {
                    text: `clips.twitch.tv/${id}`,
                    url: `https://clips.twitch.tv/embed?clip=${id}&parent=${window.location.hostname}`
                };
            }

            // Plain YouTube Videos
            if (url.startsWith("https://www.youtube.com/watch?")) {
                const id = find(/[?&]v=(\w+)/gm, url),
                    time = find(/[?&]t=(\w+)/gm, url) || '0';

                if (!id) return false;

                return {
                    text: `youtube.com/${id}`,
                    url: `https://www.youtube.com/embed/${id}?autoplay=1&start=${time}`
                };
            }

            // YouTube shortened URLs
            if (url.startsWith("https://youtu.be/")) {
                const id = find(/youtu.be\/(\w+)/gm, url),
                    time = find(/[?&]t=(\w+)/gm, url) || '0';

                if (!id) return false;

                return {
                    text: `youtu.be/${id}`,
                    url: `https://www.youtube.com/embed/${id}?autoplay=1&start=${time}`
                };
            }

            // YouTube Live
            if (url.startsWith("https://youtube.com/live/")) {
                const id = find(/youtube.com\/live\/(\w+)/gm, url),
                    time = find(/[?&]t=(\w+)/gm, url) || '0';

                if (!id) return false;

                return {
                    text: `youtube.com/live/${id}`,
                    url: `https://www.youtube.com/embed/${id}?autoplay=1&start=${time}`
                };
            }

            // TicketTool Transcripts
            if (url.startsWith("https://tickettool.xyz/direct?url=")) {
                const ticket = find(/(transcript-.+?)\.html/gm, url);

                if (!ticket) return false;

                return {
                    text: `tickettool.xyz/${ticket}`,
                    url: url
                };
            }

            // Medal.TV clips
            if (url.match(/^https:\/\/medal.tv\/games\/[\w-]+\/clips/gm)) {
                url = url.split('?').pop();

                return {
                    text: url,
                    url: url
                };
            }

            // Generic Image URLs
            if (url.match(/^https:\/\/[^\s?#]+?\.(jpg|jpeg|png|gif|webp)/gm)) {
                const host = find(/^https:\/\/([^\s/]+)/gm, url),
                    base = find(/\/([^/]+)(?=$|[\s#?])/gm, url);

                return {
                    text: host && base ? `${host}/${base}` : url,
                    url: url,
                    image: true
                };
            }

            // Generic Video URLs
            if (url.match(/^https:\/\/[^\s?#]+?\.(mp4|mov|avi|mkv|webm)/gm)) {
                const host = find(/^https:\/\/([^\s/]+)/gm, url),
                    base = find(/\/([^/]+)(?=$|[\s#?])/gm, url);

                return {
                    text: host && base ? `${host}/${base}` : url,
                    url: url,
                    video: true
                };
            }

            return false;
        }

        function link(url) {
            const data = embed(url);

            if (data) {
                return `<a href="${url}" target="_blank" class="text-indigo-600 dark:text-indigo-400 a-link"><i class="fas fa-window-restore"></i> ${data.text}</a>`;
            }

            return `<a href="${url}" target="_blank" class="text-indigo-600 dark:text-indigo-400 a-link">${url}</a>`;
        }

        Vue.prototype.markdown = function (text, escapeHTML = false) {
            if (!text) return false;

            if (escapeHTML) {
                text = escapeHtml(text);
            }

            const codeBlocks = [];

            // ```code```
            text = text.replace(/```(.+?)```\n?/gis, (match, p1, offset, string) => {
                codeBlocks.push(`<pre class="max-w-2xl py-1 px-2 bg-gray-200 dark:bg-gray-800 rounded-sm leading-5 whitespace-pre-wrap break-words"><code>${escapeHtml(p1.trim())}</code></pre>`);

                return `{{${codeBlocks.length - 1}}}`;
            });

            // `code`
            text = text.replace(/`(.+?)`/gi, (match, p1, offset, string) => {
                codeBlocks.push(`<code class="py-0.5 px-1 bg-gray-200 dark:bg-gray-800 select-all">${escapeHtml(p1.trim())}</code>`);

                return `{{${codeBlocks.length - 1}}}`;
            });

            // ***text***
            text = text.replace(/\*{3,}(.+?)\*{3,}/gi, '<strong><em>$1</em></strong>');

            // **text**
            text = text.replace(/\*{2}(.+?)\*{2}/gi, '<strong>$1</strong>');

            // *text*
            text = text.replace(/\*(.+?)\*/gi, '<em>$1</em>');

            // __text__
            text = text.replace(/_{2}(.+?)_{2}/gi, '<u>$1</u>');

            // ~~text~~
            text = text.replace(/~{2}(.+?)~{2}/gi, '<del>$1</del>');

            // --text-- (custom)
            text = text.replace(/-{2}(.+?)-{2}/gi, '<span>$1</span>');

            // ![alt](url)
            text = text.replace(/!\[(.+?)\]\((.+?)\)/gi, (match, p1, p2, offset, string) => {
                return `<img src="${p2}" alt="${p1}" class="max-w-xs">`;
            });

            // [text](url)
            text = text.replace(/\[(.+?)\]\((.+?)\)/gi, (match, p1, p2, offset, string) => {
                return `<a href="${p2}" target="_blank" class="text-indigo-600 dark:text-indigo-400" title="${!p2.startsWith('/') ? p2 : ''}">${p1}</a>`;
            });

            // urls
            text = text.replace(/(?<!["=])(https?:\/\/[^\s]+)/gi, (match, p1, offset, string) => {
                return link(p1);
            });

            // - list
            text = text.replace(/^ ?[-*] (.+)\n?/gmi, (match, p1, offset, string) => {
                return `<li class="ml-4 pl-2 list-dash">${p1.trim()}</li>`;
            });

            codeBlocks.forEach((codeBlock, index) => {
                text = text.replace(`{{${index}}}`, codeBlock);
            });

            return text;
        };

        $(document).on('click', 'a.a-link', (e) => {
            const el = e.target,
                url = el.href,
                data = embed(url);

            if (!data) return;

            e.preventDefault();

            handleEmbedClick(el, url, data);
        });

        $(document).on('click', 'a.a-close', function (e) {
            e.preventDefault();

            const url = $(this).data('url');

            $(this).parent().replaceWith(link(url));
        });

        function handleEmbedClick(el, url, data) {
            if (data.image) {
                return handleImage(el, url, data.text);
            } else if (data.video) {
                return handleVideo(el, url, data.text);
            }

            handleIframe(el, url, data.url);
        }

        function handleImage(el, url) {
            $(el).replaceWith(`<div class="relative whitespace-normal inline-block">
                <a href="#" class="a-close" data-url="${url}">&#10006;</a>
                <img class="block max-h-96 max-w-full" src="${url}" />
            </div>`);
        }

        function handleVideo(el, url) {
            $(el).replaceWith(`<div class="relative whitespace-normal inline-block">
                <a href="#" class="p-2 a-close" data-url="${url}">&#10006;</a>
                <video class="block max-h-96 max-w-full" controls>
                    <source src="${url}" type="video/mp4">
                </video>
            </div>`);
        }

        function handleIframe(el, url, iframe) {
            $(el).replaceWith(`<div class="relative whitespace-normal inline-block">
                <a href="#" class="p-2 a-close" data-url="${url}">&#10006;</a>
                <iframe class="block h-96 w-iframe max-w-full" src="${iframe}" frameborder="0" allowfullscreen></iframe>
            </div>`);
        }
    }
}

export default Markdown;
