const Markdown = {
    async install(Vue, options) {
        function escapeHtml(unsafe) {
			return unsafe
				.replace(/&/g, "&amp;")
				.replace(/</g, "&lt;")
				.replace(/>/g, "&gt;")
				.replace(/"/g, "&quot;")
				.replace(/'/g, "&#039;");
		}

        Vue.prototype.markdown = function (text, escapeHTML = false) {
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

            // _text_
            text = text.replace(/_(.+?)_/gi, '<em>$1</em>');

            // ~~text~~
            text = text.replace(/~{2}(.+?)~{2}/gi, '<del>$1</del>');

            // ![alt](url)
            text = text.replace(/!\[(.+?)\]\((.+?)\)/gi, (match, p1, p2, offset, string) => {
                return `<img src="${p2}" alt="${p1}" class="max-w-xs">`;
            });

            // [text](url)
            text = text.replace(/\[(.+?)\]\((.+?)\)/gi, (match, p1, p2, offset, string) => {
                return `<a href="${p2}" target="_blank" class="text-indigo-600 dark:text-indigo-400" title="${p2}">${p1}</a>`;
            });

            // urls
            text = text.replace(/(?<!")(https?:\/\/[^\s]+)/gi, (match, p1, offset, string) => {
                return `<a href="${p1}" target="_blank" class="text-indigo-600 dark:text-indigo-400">${p1}</a>`;
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
    }
}

export default Markdown;
