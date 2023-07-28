import { get } from "axios";

const Dictionary = {
    async install(Vue, options) {
        let dictionary,
            badDictionary,
            badLongDictionary;

        function skipWord(text, word) {
            word = word.toLowerCase();

            if (word.endsWith("n't")) return true;
            if (word.endsWith("i'm")) return true;
            if (word.endsWith("'s")) return true;
            if (word.endsWith("'d")) return true;

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

            const words = data.data.split("\n").map(word => word.trim());

            if (noMap) return words;

            return words.reduce((map, word) => {
                map.set(word, true);

                return map;
            }, new Map());
        }

        Vue.prototype.loadDictionaries = async function (onProgress) {
            dictionary = await loadDictionaryFile("/_data/dictionary.txt", onProgress, 0, 200, false);

            badDictionary = await loadDictionaryFile("/_data/bad_words.txt?_=" + Date.now(), onProgress, 100, 200, true);

            badLongDictionary = badDictionary.filter(word => word.includes(" "));
            badDictionary = badDictionary.filter(word => !word.includes(" "));

            onProgress(100);
        };

        function isWordEnglish(word) {
            return dictionary.has(word.toLowerCase());
        }

        function isWordBad(word, nextText) {
            word = word.toLowerCase();

            return badDictionary.find(key => {
                if (key.startsWith("=")) {
                    key = key.substr(1);

                    return word === key;
                }

                if (key.includes("+")) {
                    const parts = key.split("+");

                    return word === parts[0] && nextText.toLowerCase().startsWith(parts[1]);
                }

                return word === key || word.includes(key);
            });
        }

        // text-red-700 dark:text-red-300 text-yellow-700 dark:text-yellow-300 text-green-700 dark:text-green-300 text-blue-700 dark:text-blue-300
        // hover:text-red-600 dark:hover:text-red-400 hover:text-yellow-600 dark:hover:text-yellow-400 hover:text-green-600 dark:hover:text-green-400 hover:text-blue-600 dark:hover:text-blue-400
        function highlight(text, color, title) {
            return `<span class="font-semibold text-${color}-700 dark:text-${color}-300 hover:text-${color}-600 dark:hover:text-${color}-400" title="${title}">${text}</span>`;
        }

        Vue.prototype.highlightText = function (original, danny) {
            if (!dictionary || !badDictionary) return false;

            danny = (danny || 0) * 100;

            let hasBad = 0,
                noEnglish = 0,
                hasAnyEnglish = 0,
                otherIssues = false;

            let text = original.replace(/[a-z']+/gi, (word, index) => {
                const testAgainst = word.toLowerCase().replace(/^'|'$/g, "");

                if (testAgainst.match(/^\d+$/)) return word;

                if (testAgainst.length <= 3) return word;

                if (isWordBad(testAgainst, original.substr(index))) {
                    hasBad++;

                    return highlight(word, "red", "possibly bad word");
                }

                if (skipWord(original, testAgainst)) return word;

                if (!isWordEnglish(testAgainst)) {
                    noEnglish++;

                    return highlight(word, "yellow", "not english");
                }

                hasAnyEnglish++;

                return word;
            });

            for (const word of badLongDictionary) {
                text = text.replace(new RegExp(word, "gmi"), word => {
                    hasBad++;

                    return highlight(word, "red", "possibly bad word")
                });
            }

            text = text.replace(/(\w{2,})(\s*\1)+/gmi, word => {
                otherIssues = "spamming the same word";

                return highlight(word, "red", "repeated word");
            });

            let color = "green",
                prediction = "positive",
                reason = "seems fine";

            if (otherIssues) {
                color = "red";
                prediction = "negative";
                reason = otherIssues;
            } if (hasBad > 0) {
                color = "red";
                prediction = "negative";
                reason = "contains " + hasBad + " bad word(s)";
            } else if (hasAnyEnglish === 0) {
                color = "red";
                prediction = "negative";
                reason = "not a single english word";
            } else {
                if (noEnglish > 0) {
                    color = "yellow";
                    prediction = "neutral";
                    reason = "contains non-english words";
                }

                if (hasAnyEnglish <= 2 && danny >= 85) {
                    color = "red";
                    prediction = "negative";
                    reason = "has barely any english words and high danny percentage";
                } else if (original === original.toUpperCase()) {
                    color = "red";
                    prediction = "negative";
                    reason = "all caps";
                }
            }

            return {
                text: text,
                color: color,
                prediction: prediction,
                reason: reason
            };
        };
    },
}

export default Dictionary;
