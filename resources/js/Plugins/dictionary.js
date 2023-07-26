import { get } from "axios";

const Dictionary = {
    async install(Vue, options) {
        let dictionary,
            badDictionary;

        async function loadDictionaryFile(file, onProgress, offset, maxPercentage, noMap) {
            const data = await get(file, {
                onDownloadProgress: progressEvent => {
                    const current = (progressEvent.loaded / progressEvent.total) * 100,
                        percentage = Math.floor((current + offset) / maxPercentage);

                    onProgress(percentage);
                }
            });

            const words = data.data.split("\n").filter(word => word.length > 3).map(word => word.trim().toLowerCase());

            if (noMap) return words;

            return words.reduce((map, word) => {
                map.set(word, true);

                return map;
            }, new Map());
        }

        Vue.prototype.loadDictionaries = async function (onProgress) {
            dictionary = await loadDictionaryFile("/_data/dictionary.txt", onProgress, 0, 200, false);

            badDictionary = await loadDictionaryFile("/_data/bad_words.txt?_=" + Date.now(), onProgress, 100, 200, true);

            onProgress(100);
        };

        function isWordEnglish(word) {
            return dictionary.has(word.toLowerCase());
        }

        function isWordBad(word) {
            word = word.toLowerCase();

            return badDictionary.find(key => {
                return key.includes(word) || word.includes(key);
            });
        }

        function highlight(text, color) {
            return `<span class="text-${color}-800 dark:text-${color}-200">${text}</span>`;
        }

        Vue.prototype.highlightText = function (text) {
            if (!dictionary || !badDictionary) return false;

            const highlighted = text.replace(/[\w']+/gi, word => {
                if (isWordBad(word)) highlight(word, "red");

                if (!isWordEnglish(word)) highlight(word, "yellow");

                return word;
            });

            return {
                text: text,
                color: text !== highlighted ? "red" : "green"
            };
        };
    },
}

export default Dictionary;
