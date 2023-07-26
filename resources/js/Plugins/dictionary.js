import { get } from "axios";

const Dictionary = {
    async install(Vue, options) {
        let dictionary,
            badDictionary;

        const cleanWords = words => {
            return words.filter(word => word.length > 3).map(word => word.trim().toLowerCase());
        };

        const loadDictionaryFile = async (file, onProgress, offset, maxPercentage, noMap) => {
            const data = await get(file, {
                onDownloadProgress: progressEvent => {
                    const current = (progressEvent.loaded / progressEvent.total) * 100,
                        percentage = Math.floor((current + offset) / maxPercentage);

                    onProgress(percentage);
                }
            });

            const words = cleanWords(data.data.split("\n"));

            if (noMap) return words;

            return words.reduce((map, word) => {
                map.set(word, true);

                return map;
            }, new Map());
        };

        Vue.prototype.loadDictionaries = async function (onProgress) {
            dictionary = await loadDictionaryFile("/_data/dictionary.txt", onProgress, 0, 200, false);

            badDictionary = await loadDictionaryFile("/_data/bad_words.txt?_=" + Date.now(), onProgress, 100, 200, true);

            onProgress(100);
        };

        Vue.prototype.checkCharacter = function (character) {
            if (!dictionary || !badDictionary) return false;

            const words = character.backstory.toLowerCase().split(/[^\w]+/g).filter(word => word.length > 3);

            const hasBadWord = words.find(word => {
                return badDictionary.find(key => {
                    return key.includes(word) || word.includes(key);
                });
            });

            if (hasBadWord) return "negative";

            const hasEnglish = words.find(word => {
                return dictionary.has(word);
            });

            if (!hasEnglish) return "negative";

            return "positive";
        };
    },
}

export default Dictionary;
