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

const inheritance = source.match(/\bconst INHERITED_FROM\s*=\s*\[([\s\S]*?)^\s*\];/m),
	permissionsByKey = new Map(permissions.map(permission => [permission.key, permission]));

if (!inheritance) {
	throw new Error("Could not find INHERITED_FROM in PermissionHelper.php.");
}

for (const line of inheritance[1].split("\n").filter(line => line.trim())) {
	const entry = line.match(/^\s*self::(PERM_\w+)\s*=>\s*\[\s*(self::PERM_\w+(?:\s*,\s*self::PERM_\w+)*)\s*\]\s*,?\s*$/);

	if (!entry) {
		throw new Error(`Unrecognized permission inheritance: ${line.trim()}`);
	}

	const baseKey = constants.get(entry[1]);

	if (!keys.has(baseKey)) {
		throw new Error(`Unknown inherited permission: ${entry[1]}`);
	}

	for (const match of entry[2].matchAll(/self::(PERM_\w+)/g)) {
		const permission = permissionsByKey.get(constants.get(match[1]));

		if (!permission || permission.key === baseKey || permission.inherits) {
			throw new Error(`Invalid or multiple inherited permissions for ${match[1]}.`);
		}

		permission.inherits = baseKey;
	}
}

writeFileSync(new URL("permissions.json", root), `${JSON.stringify(permissions, null, "\t")}\n`);

console.log(`Updated permissions.json with ${permissions.length} permissions.`);
