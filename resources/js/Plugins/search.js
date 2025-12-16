const operatorRgx = /^(!=|!~|[=<>~])?(.*)$/;

const Search = {
	install: (Vue, options) => {
		Vue.prototype.previewQuery = input => {
			if (!input) {
				return "";
			}

			const orGroups = input.split("|");
			const results = [];

			for (let groupStr of orGroups) {
				groupStr = groupStr.trim();

				if (!groupStr) {
					continue;
				}

				const andParts = groupStr.split("&"),
					currentGroupDesc = [];

				for (let part of andParts) {
					part = part.trim();

					if (!part) {
						continue;
					}

					const match = part.match(operatorRgx);

					if (!match) {
						continue;
					}

					const operator = match[1] || "",
						value = match[2].trim();

					if (!value) {
						continue;
					}

					let text = "";

					switch (operator) {
						case "=":
							text = `is exactly "${value}"`;

							break;
						case "!=":
							text = `is not "${value}"`;

							break;
						case ">":
							// PHP is_numeric check
							if (Number.isNaN(value)) {
								continue;
							}

							text = `greater than ${value}`;

							break;
						case "<":
							// PHP is_numeric check
							if (Number.isNaN(value)) {
								continue;
							}

							text = `less than ${value}`;

							break;
						case "!~":
							text = `does not contain "${value}"`;

							break;
						default:
							text = `contains "${value}"`;

							break;
					}

					if (text) {
						currentGroupDesc.push(text);
					}
				}

				if (currentGroupDesc.length > 0) {
					results.push(currentGroupDesc.join(" and "));
				}
			}

			if (results.length === 0) {
				return "";
			}

			if (results.length > 1) {
				return results.map(r => `(${r})`).join(" or ");
			}

			return results[0];
		};
	},
};

export default Search;
