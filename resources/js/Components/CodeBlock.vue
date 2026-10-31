<template>
	<micro-lighter :language="language" controls="copy" line-numbers class="code-block overflow-hidden rounded border border-gray-300 dark:border-gray-600" data-syntax-theme="opfw-admin">
		<pre class="m-0 overflow-x-auto px-3 py-3 text-xs leading-relaxed"><code>{{ code }}</code></pre>
	</micro-lighter>
</template>

<script>
import 'microlighter/micro-lighter-element.js';

export default {
	name: 'CodeBlock',
	props: {
		code: {
			type: String,
			default: ''
		},
		language: {
			type: String,
			required: true
		}
	}
};
</script>

<style>
[data-syntax-theme="opfw-admin"] {
	--syntax-background: light-dark(var(--panel-background-gray-200, theme('colors.gray.200')), var(--panel-background-gray-800, theme('colors.gray.800')));
	/* GitHub-inspired tokens with darker equivalents for light mode. */
	--syntax-foreground: light-dark(#24292e, #adbac7);
	--syntax-comment: light-dark(#6a737d, #768390);
	--syntax-keyword: light-dark(#d73a49, #f47067);
	--syntax-constant: light-dark(#005cc5, #6cb6ff);
	--syntax-operator: var(--syntax-constant);
	--syntax-punctuation: var(--syntax-foreground);
	--syntax-string: light-dark(#032f62, #96d0ff);
	--syntax-numeric: light-dark(theme('colors.blue.700'), theme('colors.blue.400'));
	--syntax-boolean: var(--syntax-keyword);
	--syntax-function: light-dark(#6f42c1, #dcbdfb);
	--syntax-type: var(--syntax-function);
	--syntax-variable: var(--syntax-constant);
	--syntax-property: var(--syntax-constant);
	--syntax-key: light-dark(#52677d, #b0c2d4);
	--syntax-tag: light-dark(#22863a, #8ddb8c);
	--syntax-selector: var(--syntax-tag);
	--syntax-inserted: light-dark(#22863a, #b4f1b4);
	--syntax-deleted: light-dark(#b31d28, #ffd8d3);
	--syntax-border: light-dark(var(--panel-color-gray-300, theme('colors.gray.300')), var(--panel-color-gray-600, theme('colors.gray.600')));
	--syntax-control-background: light-dark(var(--panel-color-gray-200, theme('colors.gray.200')), var(--panel-color-gray-700, theme('colors.gray.700')));
	background-color: var(--syntax-background);
	color: var(--syntax-foreground);
	color-scheme: light;
}

[data-syntax-theme="opfw-admin"] pre:has(code) {
	background-color: transparent;
	color: var(--syntax-foreground);
}

[data-syntax-theme="opfw-admin"] ::highlight(comment),
[data-syntax-theme="opfw-admin"] ::highlight(quote) {
	color: var(--syntax-comment);
}

[data-syntax-theme="opfw-admin"] ::highlight(keyword),
[data-syntax-theme="opfw-admin"] ::highlight(storage),
[data-syntax-theme="opfw-admin"] ::highlight(at-rule),
[data-syntax-theme="opfw-admin"] ::highlight(doctype),
[data-syntax-theme="opfw-admin"] ::highlight(important),
[data-syntax-theme="opfw-admin"] ::highlight(section) {
	color: var(--syntax-keyword);
}

[data-syntax-theme="opfw-admin"] ::highlight(operator) {
	color: var(--syntax-operator);
}

[data-syntax-theme="opfw-admin"] ::highlight(punctuation) {
	color: var(--syntax-punctuation);
}

[data-syntax-theme="opfw-admin"] ::highlight(string),
[data-syntax-theme="opfw-admin"] ::highlight(regexp),
[data-syntax-theme="opfw-admin"] ::highlight(attribute-value),
[data-syntax-theme="opfw-admin"] ::highlight(link),
[data-syntax-theme="opfw-admin"] ::highlight(raw) {
	color: var(--syntax-string);
}

[data-syntax-theme="opfw-admin"] ::highlight(numeric) {
	color: var(--syntax-numeric);
}

[data-syntax-theme="opfw-admin"] ::highlight(boolean),
[data-syntax-theme="opfw-admin"] ::highlight(constant) {
	color: var(--syntax-boolean);
}

[data-syntax-theme="opfw-admin"] ::highlight(symbol),
[data-syntax-theme="opfw-admin"] ::highlight(character-entity),
[data-syntax-theme="opfw-admin"] ::highlight(anchor),
[data-syntax-theme="opfw-admin"] ::highlight(entity) {
	color: var(--syntax-constant);
}

[data-syntax-theme="opfw-admin"] ::highlight(function),
[data-syntax-theme="opfw-admin"] ::highlight(decorator),
[data-syntax-theme="opfw-admin"] ::highlight(animation) {
	color: var(--syntax-function);
}

[data-syntax-theme="opfw-admin"] ::highlight(type),
[data-syntax-theme="opfw-admin"] ::highlight(support) {
	color: var(--syntax-type);
}

[data-syntax-theme="opfw-admin"] ::highlight(variable),
[data-syntax-theme="opfw-admin"] ::highlight(interpolation) {
	color: var(--syntax-variable);
}

[data-syntax-theme="opfw-admin"] ::highlight(property),
[data-syntax-theme="opfw-admin"] ::highlight(attribute-name) {
	color: var(--syntax-property);
}

[data-syntax-theme="opfw-admin"] ::highlight(key) {
	color: var(--syntax-key);
}

[data-syntax-theme="opfw-admin"] ::highlight(tag) {
	color: var(--syntax-tag);
}

[data-syntax-theme="opfw-admin"] ::highlight(selector) {
	color: var(--syntax-selector);
}

[data-syntax-theme="opfw-admin"] ::highlight(inserted) {
	color: var(--syntax-inserted);
}

[data-syntax-theme="opfw-admin"] ::highlight(deleted) {
	color: var(--syntax-deleted);
}

.code-block code {
	font-family: inherit;
	tab-size: 4;
}

.code-block::part(line-numbers) {
	background-color: transparent;
	border-right: 1px solid var(--syntax-border);
	font-size: 0.75rem;
}

.code-block::part(copy-button) {
	background-color: var(--syntax-control-background);
	border-color: var(--syntax-border);
	color: var(--syntax-foreground);
	transition: background-color 120ms ease, border-color 120ms ease;
}

.code-block::part(copy-button):hover {
	background-color: color-mix(in srgb, var(--syntax-control-background) 94%, var(--syntax-foreground));
	border-color: color-mix(in srgb, var(--syntax-border) 80%, var(--syntax-foreground));
}

.code-block::part(copy-button):focus-visible {
	outline-color: var(--syntax-keyword);
}

.dark [data-syntax-theme="opfw-admin"] {
	color-scheme: dark;
}
</style>
