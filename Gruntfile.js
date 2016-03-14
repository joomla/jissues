//Gruntfile
module.exports = function(grunt) {
    // Initializing the configuration object
    grunt.initConfig({
        bower: {
            dev: {
                dest: 'www/media/js/vendor',
                css_dest: 'www/media/css/vendor',
                options: {
                    expand: true,
                    packageSpecific: {
                        'bootstrap': {
                            files: [
                                'js/bootstrap-affix.js',
                                'js/bootstrap-dropdown.js',
                                'js/bootstrap-tab.js',
                                'js/bootstrap-tooltip.js',
                                'js/bootstrap-collapse.js',
                                'js/bootstrap-popover.js',
                                'js/bootstrap-typeahead.js',
                                'js/bootstrap-modal.js',
                                'js/bootstrap-transition.js'
                            ]
                        },
                        'semantic-ui-transition': {
                            files: [
                                'transition.css',
                                'transition.js'
                            ]
                        },
                        'semantic-ui-dropdown': {
                            files: [
                                'dropdown.css',
                                'dropdown.js'
                            ]
                        },
                        'g11n-js': {
                            files: [
                                'js/g11n.js',
                                'js/methods.js',
                                'js/phpjs.js'
                            ]
                        },
                        'blueimp-file-upload': {
                            images_dest: 'www/media/css/vendor',
                            files: [
                                'css/jquery.fileupload.css',
                                'css/jquery.fileupload-ui.css',
                                'img/loading.gif',
                                'img/progressbar.gif',
                                'js/jquery.iframe-transport.js',
                                'js/jquery.fileupload.js',
                                'js/jquery.fileupload-process.js',
                                'js/jquery.fileupload-image.js',
                                'js/jquery.fileupload-ui.js',
                                'js/jquery.fileupload-validate.js',
                                'js/vendor/jquery.ui.widget.js'
                            ]
                        },
                        'blueimp-load-image': {
                            files: [
                                'js/load-image.js',
                                'js/load-image-exif.js',
                                'js/load-image-exif-map.js',
                                'js/load-image-ios.js',
                                'js/load-image-meta.js',
                                'js/load-image-orientation.js'
                            ]
                        },
                        'bootstrap-switch': {
                            files: [
                                'static/stylesheets/bootstrap-switch.css',
                                'static/js/bootstrap-switch.js'
                            ]
                        },
                        'jquery-simple-color': {
                            files: [
                                'src/jquery.simple-color.js'
                            ]
                        },
                        'jquery-validation': {
                            files: [
                                'dist/jquery.validate.js'
                            ]
                        },
                        'markitup': {
                            files: [
                                'markitup/jquery.markitup.js'
                            ]
                        },
                        'octicons': {
                            fonts_dest: 'www/media/css/vendor',
                            files: [
                                'octicons/octicons.css',
                                'octicons/octicons.eot',
                                'octicons/octicons.svg',
                                'octicons/octicons.ttf',
                                'octicons/octicons.woff'
                            ]
                        }
                    }
                }
            }
        },
        bower_concat: {
            all: {
                dest: 'www/media/js/vendor.js',
                cssDest: 'www/media/css/vendor.css',
                mainFiles: {
                    // v2.3.2 release order: bootstrap-transition.js, bootstrap-alert.js, bootstrap-button.js, bootstrap-carousel.js, bootstrap-collapse.js, bootstrap-dropdown.js, bootstrap-modal.js, bootstrap-tooltip.js, bootstrap-popover.js, bootstrap-scrollspy.js, bootstrap-tab.js, bootstrap-typeahead.js, bootstrap-affix.js
                    'bootstrap': ['js/bootstrap-transition.js', 'js/bootstrap-collapse.js', 'js/bootstrap-dropdown.js', 'js/bootstrap-modal.js', 'js/bootstrap-tooltip.js', 'js/bootstrap-popover.js', 'js/bootstrap-tab.js', 'js/bootstrap-typeahead.js', 'js/bootstrap-affix.js'],
                    'semantic-ui-transition': ['transition.css', 'transition.js'],
                    'semantic-ui-dropdown': ['dropdown.css', 'dropdown.js'],
                    'g11n-js': ['js/g11n.js', 'js/methods.js', 'js/phpjs.js'],
                    'blueimp-file-upload': ['css/jquery.fileupload.css', 'css/jquery.fileupload-ui.css', 'js/vendor/jquery.ui.widget.js', 'js/jquery.fileupload.js', 'js/jquery.fileupload-process.js', 'js/jquery.fileupload-image.js', 'js/jquery.fileupload-ui.js', 'js/jquery.fileupload-validate.js', 'js/jquery.iframe-transport.js'],
                    // Added in same order as v1.13.0 Gruntfile
                    'blueimp-load-image': ['js/load-image.js', 'js/load-image-ios.js', 'js/load-image-orientation.js', 'js/load-image-meta.js', 'js/load-image-exif.js', 'js/load-image-exif-map.js'],
                    'bootstrap-switch': ['static/stylesheets/bootstrap-switch.css', 'static/js/bootstrap-switch.js'],
                    'jquery-simple-color': ['src/jquery.simple-color.js'],
                    'jquery-validation': ['dist/jquery.validate.js'],
                    'markitup': ['markitup/jquery.markitup.js'],
                    'twbs-pagination': ['jquery.twbsPagination.js'],
                    'octicons': ['octicons/octicons.css']
                }
            }
        },
        cssmin: {
            target: {
                files: {
                    'www/media/css/vendor.min.css': 'www/media/css/vendor.css'
                }
            }
        },
        uglify: {
            bower: {
                options: {
                    mangle: true,
                    compress: true,
                    preserveComments: 'all'
                },
                files: {
                    'www/media/js/vendor.min.js': 'www/media/js/vendor.js'
                }
            },
            core: {
                files: {
                    'www/media/js/jtracker.min.js': 'www/media/js/jtracker.js'
                }
            }
        },
        replace: {
            dist: {
                src: 'www/media/css/vendor.css',
                overwrite: true,
                replacements: [
                    {from: 'octicons.', to: '../fonts/octicons.'},
                    {from: 'img/\(.*\).gif', to: '../img/$1.gif'},
                ]
            }
        },
        copy: {
            octicons: {
                expand: true,
                cwd: 'bower_components/octicons/octicons',
                src: ['octicons.eot', 'octicons.svg', 'octicons.ttf', 'octicons.woff'],
                dest: 'www/media/fonts/'
            },
            upload: {
                expand: true,
                cwd: 'bower_components/blueimp-file-upload/img',
                src: '*',
                dest: 'www/media/img/'
            },
            validation: {
                expand: true,
                cwd: 'bower_components/jquery-validation/src/localization',
                src: '*',
                dest: 'www/media/js/validation/'
            }
        },
    });

    // Plugin loading
    grunt.loadNpmTasks('grunt-bower');
    grunt.loadNpmTasks('grunt-bower-concat');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-text-replace');
    grunt.loadNpmTasks('grunt-contrib-copy');

    // Task definition
    grunt.registerTask('default', [
        'bower',
        'bower_concat',
        'uglify:bower',
        'uglify:core',
        'replace',
        'cssmin',
        'copy:octicons',
        'copy:upload',
        'copy:validation'
    ]);
};
