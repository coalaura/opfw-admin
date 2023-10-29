const fs = require('fs');
const path = require('path');

const directories = ['public/js', 'public/css'];

class WebpackCleanupPlugin {
    apply(compiler) {
        compiler.hooks.beforeRun.tapAsync('WebpackCleanupPlugin', () => {
            cleanupBuild()
        });
    }
}

function cleanupBuild() {
    directories.forEach(dir => {
        const fullPath = path.join(__dirname, dir);

        if (!fs.existsSync(fullPath)) return;

        fs.readdirSync(fullPath).forEach(file => {
            const filePath = path.join(fullPath, file);

            if (file.endsWith('.LICENSE.txt')) {
                fs.unlinkSync(filePath);
            }
        });
    });

    console.log('Cleanup completed!');

    return '';
}

module.exports = WebpackCleanupPlugin;