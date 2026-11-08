/**
 * Builds the frontend assets of the issue tracker with Vite.
 *
 * Usage:
 *   node vite.build.mjs            Build minified production assets
 *   node vite.build.mjs --dev      Build unminified assets
 *   node vite.build.mjs --watch    Build unminified assets and rebuild on changes
 *
 * All files are written to www/media with fixed (unhashed) names, because the templates reference them by path
 * through the Twig asset() function. www/media also contains files which are maintained by hand, so the output
 * directory is never emptied.
 */

import { copyFile, cp, mkdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { transform as transformCss } from 'lightningcss';
import { build, createLogger, minify } from 'vite';

const root   = dirname(fileURLToPath(import.meta.url));
const outDir = resolve(root, 'www/media');
const watch  = process.argv.includes('--watch');
const dev    = watch || process.argv.includes('--dev');

/**
 * Vendor files which are copied from node_modules as they are.
 *
 * Source paths are relative to node_modules, target paths are relative to www/media.
 */
const copies = {
    // jQuery
    'jquery/dist/jquery.min.js': 'js/vendor/jquery.js',

    // Bootstrap
    'bootstrap/dist/js/bootstrap.bundle.min.js':     'js/vendor/bootstrap.min.js',
    'bootstrap/dist/js/bootstrap.bundle.min.js.map': 'js/vendor/bootstrap.min.js.map',

    // Blueimp Canvas to Blob
    'blueimp-canvas-to-blob/js/canvas-to-blob.min.js': 'js/vendor/blueimp-canvas-to-blob.js',

    // Blueimp JavaScript Templates
    'blueimp-tmpl/js/tmpl.min.js': 'js/vendor/blueimp-tmpl.js',

    // jQuery Validation
    'jquery-validation/dist/jquery.validate.min.js': 'js/vendor/jquery-validation.js',

    // markItUp! (whole directory)
    'markItUp!/markitup': 'markitup',

    // twbs-pagination
    'twbs-pagination/jquery.twbsPagination.min.js': 'js/vendor/twbs-pagination.js',

    // Datepicker
    'vanillajs-datepicker/dist/css/datepicker-bs5.min.css': 'css/vendor/datepicker.css',
    'vanillajs-datepicker/dist/js/datepicker.min.js':       'js/vendor/datepicker.js',
    'vanillajs-datepicker/dist/js/locales/en-GB.js':        'js/vendor/datepicker/locales/en-GB.js',

    // Chart.js
    'chart.js/dist/chart.umd.js':     'js/vendor/chart.js',
    'chart.js/dist/chart.umd.js.map': 'js/vendor/chart.js.map',

    // d3
    'd3/d3.min.js': 'js/vendor/d3.js',

    // Octicons font files, referenced by css/vendor/octicons.css
    'octicons/build/font/octicons.eot':   'fonts/vendor/octicons/build/octicons.eot',
    'octicons/build/font/octicons.svg':   'fonts/vendor/octicons/build/octicons.svg',
    'octicons/build/font/octicons.ttf':   'fonts/vendor/octicons/build/octicons.ttf',
    'octicons/build/font/octicons.woff':  'fonts/vendor/octicons/build/octicons.woff',
    'octicons/build/font/octicons.woff2': 'fonts/vendor/octicons/build/octicons.woff2',

    // Font Awesome
    '@fortawesome/fontawesome-free/css/all.min.css':            'css/fontawesome.min.css',
    '@fortawesome/fontawesome-free/webfonts/fa-brands-400.ttf':   'webfonts/fa-brands-400.ttf',
    '@fortawesome/fontawesome-free/webfonts/fa-brands-400.woff2': 'webfonts/fa-brands-400.woff2',
    '@fortawesome/fontawesome-free/webfonts/fa-regular-400.ttf':   'webfonts/fa-regular-400.ttf',
    '@fortawesome/fontawesome-free/webfonts/fa-regular-400.woff2': 'webfonts/fa-regular-400.woff2',
    '@fortawesome/fontawesome-free/webfonts/fa-solid-900.ttf':   'webfonts/fa-solid-900.ttf',
    '@fortawesome/fontawesome-free/webfonts/fa-solid-900.woff2': 'webfonts/fa-solid-900.woff2',

    // Bootstrap Select
    'bootstrap-select/dist/css/bootstrap-select.min.css': 'css/vendor/bootstrap-select.css',
    'bootstrap-select/dist/js/bootstrap-select.min.js':   'js/vendor/bootstrap-select.js',

    // jQuery Caret (Caret.js)
    'jquery.caret/dist/jquery.caret.min.js': 'js/vendor/jquery.caret.js',

    // At.js (jquery.atwho)
    'at.js/dist/css/jquery.atwho.min.css': 'css/vendor/jquery.atwho.css',
    'at.js/dist/js/jquery.atwho.min.js':   'js/vendor/jquery.atwho.js',

    // jQuery Textrange
    'jquery-textrange/jquery-textrange.js': 'js/vendor/jquery-textrange.js',

    // SkipTo
    'skipto/downloads/js/skipto.min.js':     'js/vendor/skipto.min.js',
    'skipto/downloads/js/skipto.min.js.map': 'js/vendor/skipto.min.js.map',
};

/**
 * Vendor files which are concatenated into a single file.
 *
 * Source paths are relative to node_modules, target paths are relative to www/media.
 */
const combines = {
    // Blueimp Load Image
    'js/vendor/blueimp-load-image.js': [
        'blueimp-load-image/js/load-image.js',
        'blueimp-load-image/js/load-image-scale.js',
        'blueimp-load-image/js/load-image-meta.js',
        'blueimp-load-image/js/load-image-fetch.js',
        'blueimp-load-image/js/load-image-exif.js',
        'blueimp-load-image/js/load-image-exif-map.js',
        'blueimp-load-image/js/load-image-iptc.js',
        'blueimp-load-image/js/load-image-iptc-map.js',
        'blueimp-load-image/js/load-image-orientation.js',
    ],

    // Blueimp File Upload
    'css/vendor/blueimp-file-upload.css': [
        'blueimp-file-upload/css/jquery.fileupload.css',
        'blueimp-file-upload/css/jquery.fileupload-ui.css',
    ],
    'js/vendor/blueimp-file-upload.js': [
        'blueimp-file-upload/js/vendor/jquery.ui.widget.js',
        'blueimp-file-upload/js/jquery.fileupload.js',
        'blueimp-file-upload/js/jquery.fileupload-process.js',
        'blueimp-file-upload/js/jquery.fileupload-image.js',
        'blueimp-file-upload/js/jquery.fileupload-ui.js',
        'blueimp-file-upload/js/jquery.fileupload-validate.js',
        'blueimp-file-upload/js/jquery.iframe-transport.js',
    ],
};

/**
 * Stylesheets compiled from SCSS.
 *
 * Keys are the target paths relative to www/media without the .css extension.
 */
const styles = {
    'css/jtracker':                     'assets/scss/jtracker.scss',
    'css/jtracker-rtl':                 'assets/scss/jtracker-rtl.scss',
    'css/markitup/skins/tracker/style': 'assets/scss/markitup.scss',
    'css/vendor/octicons':              'assets/scss/octicons.scss',
};

/**
 * The tracker's own scripts.
 *
 * Every script is built on its own as an IIFE, because the templates load them as classic scripts.
 */
const scripts = {
    'js/color-select.js':                'assets/js/color-select.js',
    'js/jtracker.js':                    'assets/js/jtracker.js',
    'js/jtracker-tmpl.js':               'assets/js/jtracker-tmpl.js',
    'js/uploader-img.js':                'assets/js/uploader-img.js',
    'js/support/documentation-index.js': 'assets/js/support/documentation-index.js',
    'js/text/article-edit.js':           'assets/js/text/article-edit.js',
    'js/text/articles-index.js':         'assets/js/text/articles-index.js',
};

/**
 * Logger which hides the warning about the octicons font urls, they point to the copied font files on purpose.
 */
const customLogger = createLogger('warn');

for (const method of ['warn', 'warnOnce']) {
    const original = customLogger[method];

    customLogger[method] = (message, options) => {
        if (message.includes('/media/fonts/vendor/octicons/') && message.includes("didn't resolve at build time")) {
            return;
        }

        original(message, options);
    };
}

/**
 * Options shared by all Vite builds.
 */
const sharedConfig = {
    root,
    configFile: false,
    publicDir:  false,
    base:       '/media/',
    logLevel:   'warn',
    customLogger,
};

async function copyVendorFiles() {
    for (const [source, target] of Object.entries(copies)) {
        const from = resolve(root, 'node_modules', source);
        const to   = resolve(outDir, target);

        await mkdir(dirname(to), { recursive: true });

        if (source.endsWith('/markitup')) {
            await cp(from, to, { recursive: true });
        } else {
            await copyFile(from, to);
        }
    }

    for (const [target, sources] of Object.entries(combines)) {
        const contents = await Promise.all(sources.map((source) => readFile(resolve(root, 'node_modules', source), 'utf8')));
        const to       = resolve(outDir, target);
        let combined   = contents.join('\n');

        // Production builds minify the combined files, urls inside them are kept as they are
        if (!dev && target.endsWith('.js')) {
            combined = (await minify(target, combined, { compress: true, mangle: true })).code;
        } else if (!dev && target.endsWith('.css')) {
            combined = transformCss({ filename: target, code: Buffer.from(combined), minify: true }).code.toString();
        }

        await mkdir(dirname(to), { recursive: true });
        await writeFile(to, combined);
    }
}

async function buildStyles() {
    await build({
        ...sharedConfig,
        build: {
            outDir,
            emptyOutDir:       false,
            minify:            !dev,
            cssMinify:         !dev,
            assetsInlineLimit: 0,
            watch:             watch ? {} : null,
            rolldownOptions:   {
                input:  styles,
                output: {
                    // Images referenced by the stylesheets, e.g. the markItUp! skin
                    assetFileNames: (asset) => (asset.names[0].endsWith('.css') ? '[name][extname]' : 'images/[name][extname]'),
                    entryFileNames: '[name].js',
                },
            },
        },
        plugins: [
            {
                // A stylesheet entry also produces an empty script chunk, which is not needed
                name: 'jtracker-remove-style-chunks',
                generateBundle(options, bundle) {
                    for (const [fileName, chunk] of Object.entries(bundle)) {
                        if (chunk.type === 'chunk') {
                            delete bundle[fileName];
                        }
                    }
                },
            },
        ],
    });
}

async function buildScript(target, source) {
    await build({
        ...sharedConfig,
        build: {
            outDir,
            emptyOutDir:     false,
            minify:          !dev,
            watch:           watch ? {} : null,
            rolldownOptions: {
                input:  resolve(root, source),
                output: {
                    format:         'iife',
                    entryFileNames: target,
                    // The scripts are not modules, keep them in sloppy mode like the original sources
                    strict:         false,
                },
            },
        },
    });
}

await copyVendorFiles();
await buildStyles();

for (const [target, source] of Object.entries(scripts)) {
    await buildScript(target, source);
}
