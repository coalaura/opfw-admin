import axios from "axios";
import BadWords from "../data/bad_words.json";

const Dictionary = {
    async install(Vue, options) {
        const badLongDictionary = BadWords.filter(word => word.includes(" "));
        const badDictionary = BadWords.filter(word => !word.includes(" "));

        function skipWord(text, word) {
            word = word.toLowerCase();

            if (word.endsWith("n't")) return true;
            if (word.endsWith("i'm")) return true;
            if (word.endsWith("'s")) return true;
            if (word.endsWith("'d")) return true;

            if (text.endsWith(word + "...")) return true;

            return false;
        }

        function isWordBad(word, nextText) {
            word = word.toLowerCase();

            return badDictionary.find(key => {
                if (key.startsWith("=")) {
                    key = key.substring(1);

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
            danny = (danny || 0) * 100;

            let hasBad = 0,
                otherIssues = false;

            let text = original.replace(/[a-z']+/gi, (word, index) => {
                const testAgainst = word.toLowerCase().replace(/^'|'$/g, "");

                if (testAgainst.length <= 3 || testAgainst.match(/^\d+$/)) {
                    return word;
                }

                if (isWordBad(testAgainst, original.substr(index))) {
                    hasBad++;

                    return highlight(word, "red", "possibly bad word (commonly used by trolls)");
                }

                return word;
            });

            for (const word of badLongDictionary) {
                text = text.replace(new RegExp(word, "gmi"), word => {
                    hasBad++;

                    return highlight(word, "red", "possibly bad words (commonly used by trolls)")
                });
            }

            text = text.replace(/(\w{2,})(\s*\1){2,}/gmi, word => {
                otherIssues = "spamming the same word/letters";

                return highlight(word, "red", "repeated words/letters");
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
            } else {
                if (danny >= 90) {
                    color = "red";
                    prediction = "negative";
                    reason = "high danny percentage";
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
