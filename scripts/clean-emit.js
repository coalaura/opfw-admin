const { extend } = require('laravel-mix');
const { sources } = require('webpack');

class CleanEmitPlugin {
    constructor(options = {}) {
        this.options = options;
    }

    register(options = {}) {
        this.options = options;
    }

    _cleanAsset(compilation, name) {
        const remove = this.options?.remove || [],
            stripComments = this.options?.stripComments || false;

        // Remove files.
        for (const rgx of remove) {
            if (name.match(rgx)) {
                delete compilation.assets[name];

                return;
            }
        }

        // Strip comments.
        if (stripComments && name.match(/\.(js|css)$/)) {
            const asset = compilation.assets[name];

            const source = asset.source().toString();

            compilation.updateAsset(
                name,
                new sources.RawSource(source.replace(/^\/\*.+?\*\/\s*/gms, '')),
            );
        }
    }

    apply(compiler) {
        compiler.hooks.emit.tap("StopEmitPlugin", (compilation) => {
            for (const name in compilation.assets) {
                this._cleanAsset(compilation, name);
            }
        });
    }

    webpackPlugins() {
        return new CleanEmitPlugin(this.options);
    }
}

extend('cleanEmit', new CleanEmitPlugin());