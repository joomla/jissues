const mix = require('laravel-mix');

// Configure base path for mix stuff going to web
mix.setPublicPath('www/media/');

// Configure base path for media assets
mix.setResourceRoot('/media/');

// Documented change for mix v6 upgrade - we are copying files out node_modules so require the watching of node_modules
mix.override((config) => {
  delete config.watchOptions;
});

/*
 * Copy and build vendor packages
 */

// jQuery
mix.copy('node_modules/jquery/dist/jquery.min.js', 'www/media/js/vendor/jquery.js');

// Bootstrap
mix.copy('node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', 'www/media/js/vendor/bootstrap.min.js');
mix.copy('node_modules/bootstrap/dist/js/bootstrap.bundle.min.js.map', 'www/media/js/vendor/bootstrap.min.js.map');

// Blueimp Canvas to Blob
mix.copy('node_modules/blueimp-canvas-to-blob/js/canvas-to-blob.min.js', 'www/media/js/vendor/blueimp-canvas-to-blob.js');

// Blueimp Load Image
mix.combine(
    [
        'node_modules/blueimp-load-image/js/load-image.js',
        'node_modules/blueimp-load-image/js/load-image-scale.js',
        'node_modules/blueimp-load-image/js/load-image-meta.js',
        'node_modules/blueimp-load-image/js/load-image-fetch.js',
        'node_modules/blueimp-load-image/js/load-image-exif.js',
        'node_modules/blueimp-load-image/js/load-image-exif-map.js',
        'node_modules/blueimp-load-image/js/load-image-iptc.js',
        'node_modules/blueimp-load-image/js/load-image-iptc-map.js',
        'node_modules/blueimp-load-image/js/load-image-orientation.js',
    ],
    'www/media/js/vendor/blueimp-load-image.js'
);

// Blueimp JavaScript Templates
mix.copy('node_modules/blueimp-tmpl/js/tmpl.min.js', 'www/media/js/vendor/blueimp-tmpl.js');

// Blueimp File Upload
mix.combine(
    [
        'node_modules/blueimp-file-upload/css/jquery.fileupload.css',
        'node_modules/blueimp-file-upload/css/jquery.fileupload-ui.css',
    ],
    'www/media/css/vendor/blueimp-file-upload.css'
);

mix.combine(
    [
        'node_modules/blueimp-file-upload/js/vendor/jquery.ui.widget.js',
        'node_modules/blueimp-file-upload/js/jquery.fileupload.js',
        'node_modules/blueimp-file-upload/js/jquery.fileupload-process.js',
        'node_modules/blueimp-file-upload/js/jquery.fileupload-image.js',
        'node_modules/blueimp-file-upload/js/jquery.fileupload-ui.js',
        'node_modules/blueimp-file-upload/js/jquery.fileupload-validate.js',
        'node_modules/blueimp-file-upload/js/jquery.iframe-transport.js',
    ],
    'www/media/js/vendor/blueimp-file-upload.js'
);

// jQuery Validation
mix.copy('node_modules/jquery-validation/dist/jquery.validate.min.js', 'www/media/js/vendor/jquery-validation.js');

// markItUp!
mix.copy('node_modules/markItUp!/markitup', 'www/media/markitup');

// twbs-pagination
mix.copy('node_modules/twbs-pagination/jquery.twbsPagination.min.js', 'www/media/js/vendor/twbs-pagination.js');

// Datepicker
mix.copy('node_modules/vanillajs-datepicker/dist/css/datepicker-bs5.min.css', 'www/media/css/vendor/datepicker.css');
mix.copy('node_modules/vanillajs-datepicker/dist/js/datepicker.min.js', 'www/media/js/vendor/datepicker.js');
mix.copy('node_modules/vanillajs-datepicker/dist/js/locales/en-GB.js', 'www/media/js/vendor/datepicker/locales/en-GB.js');

// Chart.js
mix.copy('node_modules/chart.js/dist/chart.umd.js', 'www/media/js/vendor/chart.js');
mix.copy('node_modules/chart.js/dist/chart.umd.js.map', 'www/media/js/vendor/chart.js.map');

// d3
mix.copy('node_modules/d3/d3.min.js', 'www/media/js/vendor/d3.js');

// octicons
mix.sass('node_modules/octicons/build/font/_octicons.scss', 'css/vendor/octicons.css');

// Font Awesome
mix.copy('node_modules/@fortawesome/fontawesome-free/css/all.min.css', 'www/media/css/fontawesome.min.css');
mix.copy('node_modules/@fortawesome/fontawesome-free/webfonts/fa-brands-400.ttf', 'www/media/webfonts/fa-brands-400.ttf');
mix.copy('node_modules/@fortawesome/fontawesome-free/webfonts/fa-brands-400.woff2', 'www/media/webfonts/fa-brands-400.woff2');
mix.copy('node_modules/@fortawesome/fontawesome-free/webfonts/fa-regular-400.ttf', 'www/media/webfonts/fa-regular-400.ttf');
mix.copy('node_modules/@fortawesome/fontawesome-free/webfonts/fa-regular-400.woff2', 'www/media/webfonts/fa-regular-400.woff2');
mix.copy('node_modules/@fortawesome/fontawesome-free/webfonts/fa-solid-900.ttf', 'www/media/webfonts/fa-solid-900.ttf');
mix.copy('node_modules/@fortawesome/fontawesome-free/webfonts/fa-solid-900.woff2', 'www/media/webfonts/fa-solid-900.woff2');

// Bootstrap Select
mix.copy('node_modules/bootstrap-select/dist/css/bootstrap-select.min.css', 'www/media/css/vendor/bootstrap-select.css');
mix.copy('node_modules/bootstrap-select/dist/js/bootstrap-select.min.js', 'www/media/js/vendor/bootstrap-select.js');

// jQuery Caret (Caret.js)
mix.copy('node_modules/jquery.caret/dist/jquery.caret.min.js', 'www/media/js/vendor/jquery.caret.js');

// At.js (jquery.atwho)
mix.copy('node_modules/at.js/dist/css/jquery.atwho.min.css', 'www/media/css/vendor/jquery.atwho.css');
mix.copy('node_modules/at.js/dist/js/jquery.atwho.min.js', 'www/media/js/vendor/jquery.atwho.js');

// jQuery Textrange
mix.copy('node_modules/jquery-textrange/jquery-textrange.js', 'www/media/js/vendor/jquery-textrange.js');

// SkipTo
mix.copy('node_modules/skipto/downloads/js/skipto.min.js', 'www/media/js/vendor/skipto.min.js');
mix.copy('node_modules/skipto/downloads/js/skipto.min.js.map', 'www/media/js/vendor/skipto.min.js.map');

// Build site resources
mix.sass('assets/scss/jtracker.scss', 'css/jtracker.css');
mix.sass('assets/scss/jtracker-rtl.scss', 'css/jtracker-rtl.css');
mix.sass('assets/scss/markitup.scss', 'css/markitup/skins/tracker/style.css');
mix.js('assets/js/color-select.js', 'js/color-select.js');
mix.js('assets/js/jtracker.js', 'js/jtracker.js');
mix.js('assets/js/jtracker-tmpl.js', 'js/jtracker-tmpl.js');
mix.js('assets/js/uploader-img.js', 'js/uploader-img.js');
mix.js('assets/js/support/documentation-index.js', 'js/support/documentation-index.js');
mix.js('assets/js/text/article-edit.js', 'js/text/article-edit.js');
mix.js('assets/js/text/articles-index.js', 'js/text/articles-index.js');
