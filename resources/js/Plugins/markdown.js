const Markdown = {
    async install(Vue, options) {
        function find(rgx, str, index = 1) {
            const match = rgx.exec(str);

            return match && match.length >= index ? match[index] : null;
        }

        function embed(url) {
            // Twitch Videos
            if (url.startsWith("https://www.twitch.tv/videos/")) {
                const id = find(/videos\/(\d+)/m, url),
                    time = find(/[?&]t=(\w+)/m, url) || '00h00m00s';

                if (!id) return false;

                return {
                    text: `twitch.tv/${id}`,
                    url: `https://player.twitch.tv/?video=${id}&time=${time}&parent=${window.location.hostname}`
                };
            }

            // Twitch Clips
            if (url.startsWith("https://clips.twitch.tv/")) {
                const id = find(/clips.twitch.tv\/(\w+)/m, url);

                if (!id) return false;

                return {
                    text: `clips.twitch.tv/${id}`,
                    url: `https://clips.twitch.tv/embed?clip=${id}&parent=${window.location.hostname}`
                };
            }

            // Plain YouTube Videos
            if (url.startsWith("https://www.youtube.com/watch?")) {
                const id = find(/[?&]v=(\w+)/m, url),
                    time = find(/[?&]t=(\w+)/m, url) || '0';

                if (!id) return false;

                return {
                    text: `youtube.com/${id}`,
                    url: `https://www.youtube.com/embed/${id}?autoplay=1&start=${time}`
                };
            }

            // YouTube shortened URLs
            if (url.startsWith("https://youtu.be/")) {
                const id = find(/youtu.be\/(\w+)/m, url),
                    time = find(/[?&]t=(\w+)/m, url) || '0';

                if (!id) return false;

                return {
                    text: `youtu.be/${id}`,
                    url: `https://www.youtube.com/embed/${id}?autoplay=1&start=${time}`
                };
            }

            // YouTube Live
            if (url.startsWith("https://youtube.com/live/")) {
                const id = find(/youtube.com\/live\/(\w+)/m, url),
                    time = find(/[?&]t=(\w+)/m, url) || '0';

                if (!id) return false;

                return {
                    text: `youtube.com/live/${id}`,
                    url: `https://www.youtube.com/embed/${id}?autoplay=1&start=${time}`
                };
            }

            // ClipChamp
            if (url.startsWith("https://clipchamp.com/watch/")) {
                const id = find(/clipchamp.com\/watch\/(\w+)/m, url);

                if (!id) return false;

                return {
                    text: `clipchamp.com/${id}`,
                    url: `https://clipchamp.com/watch/${id}/embed`
                };
            }

            // TicketTool Transcripts
            if (url.startsWith("https://tickettool.xyz/direct?url=")) {
                const ticket = find(/(transcript-.+?)\.html/m, url);

                if (!ticket) return false;

                return {
                    text: `tickettool.xyz/${ticket}`,
                    url: url
                };
            }

            // Stored Transcript URLs (deprecated)
            const hostTr = `${window.location.origin}/_transcripts/`;

            if (url.startsWith(hostTr)) {
                const ticket = find(/_transcripts\/(\d+(-[a-f0-9]+)?)\.html/m, url);

                if (!ticket) return false;

                return {
                    text: `local/ticket-${ticket}`,
                    url: url
                };
            }

            // Stored Attachments URLs (deprecated)
            const host = `${window.location.origin}/_discord_attachments/`;

            if (url.startsWith(host)) {
                const name = find(/_discord_attachments\/\d+-([\w._-]+)/m, url);

                if (!name) return false;

                const image = name.match(/\.(jpg|jpeg|png|gif|webp)$/m),
                    video = !image && name.match(/\.(mp4|mov|avi|mkv|webm)$/m);

                return {
                    text: `local/attachment/${name}`,
                    url: url,
                    image: image,
                    video: video,
                    noEmbed: !image && !video
                };
            }

            // Medal.TV clips
            if (url.match(/^https:\/\/medal.tv\/games\/[\w-]+\/clips/m)) {
                url = url.split('?').shift();

                const id = find(/clips\/(.+?)\/(.+?)$/m, url);

                if (!id) return false;

                return {
                    text: url,
                    url: `https://medal.tv/clip/${id}`
                };
            }

            // Discord attachments
            if (url.match(/^https:\/\/(cdn\.discordapp\.com|media\.discordapp\.net)\/attachments\/\d+\/\d+\/(.+?)(\?(.+?)?)?$/m)) {
                const ex = find(/[?&]ex=([a-f0-9]+)/, url);

                url = url.split('?').shift();

                const filename = find(/\d+\/\d+\/(.+?)$/m, url);

                if (!filename) return false;

                const host = find(/^https:\/\/([^\s/]+)/m, url),
                    extension = find(/\.(\w+)$/gm, filename),
                    isVideo = extension && ['mp4', 'mov', 'avi', 'mkv', 'webm'].includes(extension);

                let expired = true;

                if (ex) {
                    const ts = parseInt(ex, 16),
                        now = Math.round(Date.now() / 1000);

                    expired = ts < now;
                }

                return {
                    text: `https://${host}/${filename}`,
                    url: url,
                    image: !isVideo,
                    video: isVideo,
                    classes: "!text-discord",
                    icon: `fab fa-discord ${expired ? "text-red-500" : "text-green-500"}`
                };
            }

            // Generic Image URLs
            if (url.match(/^https:\/\/[^\s?#]+?\.(jpg|jpeg|png|gif|webp)/m)) {
                const host = find(/^https:\/\/([^\s/]+)/m, url),
                    base = find(/\/([^/]+)(?=$|[\s#?])/m, url);

                return {
                    text: host && base ? `${host}/${base}` : url,
                    url: url,
                    image: true
                };
            }

            // Generic Video URLs
            if (url.match(/^https:\/\/[^\s?#]+?\.(mp4|mov|avi|mkv|webm)/m)) {
                const host = find(/^https:\/\/([^\s/]+)/m, url),
                    base = find(/\/([^/]+)(?=$|[\s#?])/m, url);

                return {
                    text: host && base ? `${host}/${base}` : url,
                    url: url,
                    video: true
                };
            }

            return false;
        }

        function special(url) {
            // Discord channel/message links
            if (url.match(/^https:\/\/discord\.com\/channels\/\d+\/\d+(\/\d+)?$/m)) {
                const channel = find(/channels\/\d+\/(\d+)$/m, url),
                    message = find(/channels\/\d+\/\d+\/(\d+)$/m, url);

                const text = message ? `discord.com/msg/${message}` : `discord.com/chn/${channel}`;

                return {
                    text: text,
                    url: url,
                    classes: "!text-discord",
                    icon: "fab fa-discord"
                };
            }

            return false;
        }

        function link(url) {
            const data = embed(url) || special(url);

            if (data) {
                const icon = data.image ? "fas fa-image" : data.video ? "fas fa-video" : "fas fa-window-restore";

                return `<a href="${url}" target="_blank" class="text-indigo-600 dark:text-indigo-400 a-link ${data.classes || ""}"><i class="${data.icon || icon}"></i> ${data.text}</a>`;
            }

            return `<a href="${url}" target="_blank" class="text-indigo-600 dark:text-indigo-400 a-link">${url}</a>`;
        }

        Vue.prototype.markdown = (text, escapeHTML = false) => {
            if (!text) return false;

            if (escapeHTML) {
                text = Vue.escapeHtml(text);
            }

            const codeBlocks = [];

            // ```code```
            text = text.replace(/```(.+?)```\n?/gis, (match, p1, offset, string) => {
                codeBlocks.push(`<pre class="max-w-2xl py-1 px-2 bg-gray-200 dark:bg-gray-800 rounded-sm leading-5 whitespace-pre-wrap break-words"><code>${Vue.escapeHtml(p1.trim())}</code></pre>`);

                return `{{${codeBlocks.length - 1}}}`;
            });

            // `code`
            text = text.replace(/`(.+?)`/gi, (match, p1, offset, string) => {
                codeBlocks.push(`<code class="py-0.5 px-1 bg-gray-200 dark:bg-gray-800 select-all">${Vue.escapeHtml(p1.trim())}</code>`);

                return `{{${codeBlocks.length - 1}}}`;
            });

            // ***text***
            text = text.replace(/\*{3,}(.+?)\*{3,}/gi, (match, content) => {
                if (content.match(/^[*_~-]*$/m)) return match;

                return `<strong><em>${content}</em></strong>`;
            });

            // **text**
            text = text.replace(/\*{2}(.+?)\*{2}/gi, (match, content) => {
                if (content.match(/^[*_~-]*$/m)) return match;

                return `<strong>${content}</strong>`;
            });

            // *text*
            text = text.replace(/\*(.+?)\*/gi, (match, content) => {
                if (content.match(/^[*_~-]*$/m)) return match;

                return `<em>${content}</em>`;
            });

            // __text__
            text = text.replace(/_{2}(.+?)_{2}/gi, (match, content) => {
                if (content.match(/^[*_~-]*$/m)) return match;

                return `<u>${content}</u>`;
            });

            // ~~text~~
            text = text.replace(/~{2}(.+?)~{2}/gi, (match, content) => {
                if (content.match(/^[*_~-]*$/m)) return match;

                return `<del>${content}</del>`;
            });

            // --text-- (custom)
            text = text.replace(/-{2}(.+?)-{2}/gi, (match, content) => {
                if (content.match(/^[*_~-]*$/m)) return match;

                return `<span>${content}</span>`;
            });

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
            $(el).replaceWith(`<div class="relative whitespace-normal block w-max max-w-full">
                <a href="#" class="a-close" data-url="${url}">&#10006;</a>
                <img class="block max-h-96 max-w-full" src="${url}" />
            </div>`);
        }

        function handleVideo(el, url) {
            $(el).replaceWith(`<div class="relative whitespace-normal block w-max max-w-full">
                <a href="#" class="p-2 a-close" data-url="${url}">&#10006;</a>
                <video class="block max-h-96 max-w-full" controls>
                    <source src="${url}" type="video/mp4">
                </video>
            </div>`);
        }

        function handleIframe(el, url, iframe) {
            $(el).replaceWith(`<div class="relative whitespace-normal block w-max max-w-full">
                <a href="#" class="p-2 a-close" data-url="${url}">&#10006;</a>
                <iframe class="block h-96 w-iframe max-w-full" src="${iframe}" frameborder="0" allow="autoplay" allowfullscreen></iframe>
            </div>`);
        }
    }
}

export default Markdown;
