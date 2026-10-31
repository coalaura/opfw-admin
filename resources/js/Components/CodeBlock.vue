<template>
	<micro-lighter :language="language" controls="copy" line-numbers class="code-block overflow-hidden rounded border border-gray-300 dark:border-gray-600" data-syntax-theme="tokyo-night">
		<pre class="m-0 overflow-x-auto px-3 py-3 text-xs leading-relaxed"><code>{{ code }}</code></pre>
	</micro-lighter>
</template>

<script>
import 'microlighter/micro-lighter-element.js';
import 'microlighter/themes/tokyo-night.css';

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
.code-block[data-syntax-theme] {
	color-scheme: light;
}

.code-block code {
	font-family: inherit;
	tab-size: 4;
}

.code-block::part(line-numbers) {
	font-size: 0.75rem;
}

.dark .code-block[data-syntax-theme] {
	color-scheme: dark;
}
</style>
