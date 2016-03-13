//Gruntfile
module.exports = function(grunt) {
    //Initializing the configuration object
    grunt.initConfig({
        bower: {
            dev: {
                dest: 'www/media/js/vendor',
                css_dest: 'www/media/css/vendor',
                fonts_dest: 'www/media/fonts',
                images_dest: 'www/media/images',
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
                                'js/bootstrap-popover.js'
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
                            files: [
                                'css/jquery.fileupload.css',
                                'css/jquery.fileupload-ui.css',
                                'js/jquery.iframe-transport.js',
                                'js/jquery.fileupload.js',
                                'js/jquery.fileupload-process.js',
                                'js/jquery.fileupload-image.js',
                                'js/jquery.fileupload-ui.js',
                                'js/jquery.fileupload-validate.js',
                                'js/vendor/jquery.ui.widget.js'
                            ]
                        },
                        'bootstrap-switch': {
                            files: [
                                'static/stylesheets/bootstrap-switch.css',
                                'static/js/bootstrap-switch.js'
                            ]
                        },
                        'jquery-validation': {
                            files: [
                                'dist/jquery.validate.js',
                                'src/localization/*.js'
                            ]
                        },
                        'markitup': {
                            files: [
                                'markitup/jquery.markitup.js'
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
                    'bootstrap': ['js/bootstrap-affix.js', 'js/bootstrap-dropdown.js', 'js/bootstrap-tab.js', 'js/bootstrap-tooltip.js', 'js/bootstrap-collapse.js', 'js/bootstrap-popover.js'],
                    'semantic-ui-transition': ['transition.css', 'transition.js'],
                    'semantic-ui-dropdown': ['dropdown.css', 'dropdown.js'],
                    'g11n-js': ['js/g11n.js', 'js/methods.js', 'js/phpjs.js'],
                    'blueimp-file-upload': ['css/jquery.fileupload.css', 'css/jquery.fileupload-ui.css', 'js/vendor/jquery.ui.widget.js', 'js/jquery.fileupload.js', 'js/jquery.fileupload-process.js', 'js/jquery.fileupload-image.js', 'js/jquery.fileupload-ui.js', 'js/jquery.fileupload-validate.js', 'js/jquery.iframe-transport.js'],
                    'bootstrap-switch': ['static/stylesheets/bootstrap-switch.css', 'static/js/bootstrap-switch.js'],
                    'jquery-validation': ['dist/jquery.validate.js', 'src/localization/*.js'],
                    'markitup': ['markitup/jquery.markitup.js'],
                    'twbs-pagination': ['jquery.twbsPagination.js']
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
            }
        }
    });

    // Plugin loading
    grunt.loadNpmTasks('grunt-bower');
    grunt.loadNpmTasks('grunt-bower-concat');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-uglify');

    // Task definition
    grunt.registerTask('buildbower', [
        'bower_concat',
        'uglify:bower',
        'cssmin'
    ]);
};
