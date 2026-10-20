import { readFileSync, writeFileSync } from "node:fs";

const root = new URL("../", import.meta.url);

const source = readFileSync(new URL("app/Helpers/PermissionHelper.php", root), "utf8"),
	translations = JSON.parse(readFileSync(new URL("resources/js/locales/en-us.json", root), "utf8")).roles,
	constants = new Map([...source.matchAll(/\bconst (PERM_\w+)\s*=\s*['"]([^'"]+)['"]\s*;/g)].map(match => [match[1], match[2]])),
	registry = source.match(/\bconst PERMISSIONS\s*=\s*\[([\s\S]*?)^\s*\];/m),
	defaults = new Set(["staff", "senior", "superadmin", "root", "disabled"]),
	keys = new Set();

if (!registry) {
	throw new Error("Could not find the PERMISSIONS registry in PermissionHelper.php.");
}

const permissions = registry[1].split("\n").filter(line => line.trim()).map(line => {
	const entry = line.match(/^\s*self::(PERM_\w+)\s*=>\s*\[\s*['"]([^'"]+)['"]\s*,\s*self::LEVEL_(\w+)\s*\]\s*,?\s*$/);

	if (!entry) {
		throw new Error(`Unrecognized permission entry: ${line.trim()}`);
	}

	const key = constants.get(entry[1]),
		title = translations.permissions?.[entry[2]],
		description = translations.permission_descriptions?.[entry[2]],
		defaultLevel = entry[3].toLowerCase();

	if (!key || !defaults.has(defaultLevel) || typeof title !== "string" || !title.trim() || typeof description !== "string" || !description.trim()) {
		throw new Error(`Missing or invalid key, default, title, or description for ${entry[1]}.`);
	}

	if (keys.has(key)) {
		throw new Error(`Duplicate permission key: ${key}`);
	}

	keys.add(key);

	return {
		key: key,
		title: title,
		description: description,
		default: defaultLevel
	};
});

if (!permissions.length) {
	throw new Error("The PERMISSIONS registry is empty.");
}

writeFileSync(new URL("permissions.json", root), `${JSON.stringify(permissions, null, "\t")}\n`);

console.log(`Updated permissions.json with ${permissions.length} permissions.`);
