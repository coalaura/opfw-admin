import { readdir, lstat, readFile } from "node:fs/promises";
import { join } from "node:path";

const exclude = ["node_modules", ".git"],
	rgx = /canBeEnabled = "([^"\s]+)"/g;

async function checkFile(file, list) {
	const content = (await readFile(file)).toString();

	let m;

	while ((m = rgx.exec(content)) !== null) {
		if (m.index === rgx.lastIndex) {
			rgx.lastIndex++;
		}

		list.push(m[1]);
	}
}

async function walkDirectory(dir, list) {
	const files = await readdir(dir),
		promises = [];

	for (const file of files) {
		const path = join(dir, file),
			stat = await lstat(path);

		if (stat.isFile() && path.endsWith("_client.lua")) {
			promises.push(checkFile(path, list));
		} else if (stat.isDirectory() && !exclude.find(part => path.includes(part))) {
			promises.push(walkDirectory(path, list));
		}
	}

	await Promise.all(promises);
}

console.log("Walking source...");

const commands = [];

await walkDirectory("D:\\op-fw\\FXServer\\server-data\\resources\\op-framework", commands);

commands.sort();

console.log(commands);
