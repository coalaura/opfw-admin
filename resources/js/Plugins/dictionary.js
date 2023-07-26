import { get } from "axios";

const Dictionary = {
    async install(Vue, options) {
        let dictionary,
            badDictionary;

        function skipWord(text, word) {
            word = word.toLowerCase();

            if (word.endsWith("n't")) return true;
            if (word.endsWith("i'm")) return true;
            if (word.endsWith("'s")) return true;

            if (text.endsWith(word + "...")) return true;

            return false;
        }

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

        function highlight(text, color, title) {
            return `<span class="font-semibold text-${color}-800 dark:text-${color}-200" title="${title}">${text}</span>`;
        }

        Vue.prototype.highlightText = function (text) {
            if (!dictionary || !badDictionary) return false;

            let hasBad, noEnglish;

            text = text.replace(/[\w']+/gi, word => {
                if (word.length <= 3) return word;

                if (isWordBad(word)) {
                    hasBad = true;

                    return highlight(word, "red", "possibly bad word");
                }

                if (skipWord(text, word)) return word;

                if (!isWordEnglish(word)) {
                    noEnglish = true;

                    return highlight(word, "yellow", "not english");
                }

                return word;
            });

            let color = "green",
                prediction = "positive";

            if (hasBad) {
                color = "red";
                prediction = "negative";
            } else if (noEnglish) {
                color = "yellow";
                prediction = "neutral";
            }

            return {
                text: text,
                color: color,
                prediction: prediction
            };
        };
    },
}

export default Dictionary;