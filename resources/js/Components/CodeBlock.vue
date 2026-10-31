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
	--syntax-background: light-dark(theme('colors.gray.100'), theme('colors.gray.800'));
	--syntax-foreground: light-dark(theme('colors.gray.800'), theme('colors.gray.200'));
	--syntax-comment: light-dark(theme('colors.gray.600'), theme('colors.gray.400'));
	--syntax-keyword: light-dark(theme('colors.primary'), theme('colors.dark-primary'));
	--syntax-operator: light-dark(theme('colors.gray.700'), theme('colors.gray.300'));
	--syntax-punctuation: var(--syntax-comment);
	--syntax-string: light-dark(theme('colors.green.700'), theme('colors.green.300'));
	--syntax-numeric: light-dark(theme('colors.orange.700'), theme('colors.orange.300'));
	--syntax-boolean: light-dark(theme('colors.purple.700'), theme('colors.purple.300'));
	--syntax-constant: light-dark(theme('colors.violet.700'), theme('colors.violet.300'));
	--syntax-function: var(--syntax-keyword);
	--syntax-type: light-dark(theme('colors.teal.700'), theme('colors.teal.300'));
	--syntax-variable: var(--syntax-foreground);
	--syntax-property: light-dark(theme('colors.blue.700'), theme('colors.blue.300'));
	--syntax-tag: light-dark(theme('colors.rose.700'), theme('colors.rose.300'));
	--syntax-selector: var(--syntax-type);
	--syntax-inserted: var(--syntax-string);
	--syntax-deleted: light-dark(theme('colors.red.700'), theme('colors.red.300'));
	--syntax-border: light-dark(theme('colors.gray.300'), theme('colors.gray.600'));
	--syntax-control-background: light-dark(theme('colors.gray.200'), theme('colors.gray.700'));
	background-color: var(--syntax-background);
	color: var(--syntax-foreground);
	color-scheme: light;
}

[data-syntax-theme="opfw-admin"] pre:has(code) {
	background-color: var(--syntax-background);
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

[data-syntax-theme="opfw-admin"] ::highlight(boolean) {
	color: var(--syntax-boolean);
}

[data-syntax-theme="opfw-admin"] ::highlight(constant),
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
[data-syntax-theme="opfw-admin"] ::highlight(key),
[data-syntax-theme="opfw-admin"] ::highlight(attribute-name) {
	color: var(--syntax-property);
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
	border-right: 1px solid var(--syntax-border);
	font-size: 0.75rem;
}

.code-block::part(copy-button) {
	background-color: var(--syntax-control-background);
	border-color: var(--syntax-border);
	color: var(--syntax-foreground);
}

.code-block::part(copy-button):hover {
	border-color: var(--syntax-keyword);
	color: var(--syntax-keyword);
}

.code-block::part(copy-button):focus-visible {
	outline-color: var(--syntax-keyword);
}

.dark [data-syntax-theme="opfw-admin"] {
	color-scheme: dark;
}
</style>
