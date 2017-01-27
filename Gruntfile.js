module.exports = function(grunt) {
    require('load-grunt-tasks')(grunt);

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        clean: {
            rebuild: ['public/*']
        },
        concat: {
            main: {
                src: [
                    'bower_components/jquery/dist/jquery.js',
                    'bower_components/jquery-backstretch-2/jquery.backstretch.js',
                    'src/js/site.js'
                ],
                dest: 'public/assets/js/site.js'
            },
            preload: {
                src: [
                    'src/js/google-tracking.js'
                ],
                dest: 'public/assets/js/preload.js'
            }
        },
        copy: {
            html: {
                files: [{
                    expand: true,
                    cwd: 'src/html',
                    src: ['**/*.html'],
                    dest: 'public/'
                }]
            },
            icons: {
                files: [{
                    src: ['favicon.ico'],
                    dest: 'public/'
                }]
            }
        },
        cssmin: {
            dist: {
                files: [{
                    expand: true,
                    cwd: 'public/assets/css',
                    src: ['*.css', '!*.min.css'],
                    dest: 'public/assets/css',
                    ext: '.min.css'
                }]
            }
        },
        imagemin: {
            images: {
                files: [{
                    expand: true,
                    cwd: 'src/',
                    src: ['images/**/*.png', 'images/**/*.jpg'],
                    dest: 'public/assets/'
                }]
            }
        },
        sass: {
            site: {
                options: {
                    style: 'nested',
                    sourcemap: 'none'
                },
                files: {
                    'public/assets/css/site.css': 'src/sass/site.scss'
                }
            }
        },
        uglify: {
            dist: {
                files: {
                    'public/assets/js/preload.min.js': 'public/assets/js/preload.js',
                    'public/assets/js/site.min.js': 'public/assets/js/site.js'
                }
            }
        },
        watch: {
            options: {
                livereload: true
            },
            grunt: {
                files: ['Gruntfile.js'],
                tasks: ['build']
            },
            js: {
                files: ['src/js/*.js'],
                tasks: ['concat:main', 'concat:preload']
            },
            uglify: {
                files: ['public/assets/js/*.css', '!public/assets/js/*.min.js'],
                tasks: ['uglify']
            },
            sass: {
                files: ['src/sass/*.scss'],
                tasks: ['sass']
            },
            cssmin: {
                files: ['public/assets/css/*.css', '!public/assets/css/*.min.css'],
                tasks: ['cssmin']
            },
            html: {
                files: ['src/html/**/*.html'],
                tasks: ['copy:html']
            }
        }
    });

    grunt.registerTask('default', ['watch']);

    grunt.registerTask('nodsstore', function() {
        grunt.file.expand({
            filter: 'isFile',
            cwd: '.'
        }, ['**/.DS_Store']).forEach(function(file) {
            grunt.file.delete(file);
        });
    });

    grunt.registerTask('update', function() {
        var async = require('async');
        var exec = require('child_process').exec;
        var done = this.async();

        var runCmd = function(item, callback) {
            process.stdout.write('Running "' + item + '" ...\n');
            var cmd = exec(item);
            cmd.stdout.on('data', function(data) {
                grunt.log.writeln(data);
            });
            cmd.stderr.on('data', function(data) {
                grunt.log.errorlns(data);
            });
            cmd.on('exit', function(code) {
                if (code !== 0) throw new Error(item + ' failed');
                grunt.log.writeln('Done\n');
                callback();
            });
        };

        async.series({
                npm: function(callback) {
                    runCmd('npm update', callback);
                },
                bower: function(callback) {
                    runCmd('bower update', callback);
                }
            },
            function(err, results) {
                if (err) done(false);
                done();
            });
    });

    grunt.registerTask('build', ['concat', 'copy', 'imagemin', 'sass', 'cssmin', 'uglify']);
    grunt.registerTask('rebuild', ['clean', 'update', 'build']);
};
