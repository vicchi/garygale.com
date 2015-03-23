module.exports = function(grunt) {
	require('load-grunt-tasks')(grunt);

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		copy: {
			fonts: {
				files: [
					{
						expand: true,
						flatten: true,
						nonull: true,
						src: ['bower_components/bootstrap-sass/assets/fonts/bootstrap/*'],
						dest: 'assets/fonts/'

					}
				]
			},
			deploy: {
				files: [
					{
						expand: true,
						src: ['index.html', 'robots.txt', 'archive/**/*', 'assets/**/*', 'bio/**/*', 'favicon.ico', 'scripts/**/*'],
						dest: 'deploy'
					}
				]
			}
		},
		sass: {
			dist: {
				files: [
					{
						'assets/css/styles.css': 'src/sass/styles.scss',
						'assets/css/archive.css': 'src/sass/archive.scss',
						'assets/css/bio.css': 'src/sass/bio.scss'
					}
				]
			}
		},
		imagemin: {
			png: {
				options: {
					optimizationLevel: 3
				},
				files: [
					{
						expand: true,
						cwd: 'src/img',
						//src: ['**/*.png'],
						src: ['*.png'],
						dest: 'assets/img/',
						ext: '.png'
					}
				]
			},
			jpg: {
				options: {
					progressive: true
				},
				files: [
					{
						expand: true,
						cwd: 'src/img',
						//src: ['**/*.jpg'],
						src: ['*.jpg'],
						dest: 'assets/img/',
						ext: '.jpg'
					}
				]
			}
		},
		clean: {
			dist: {
				src: ['assets/**/*']
			}
		},
		jshint: {
			options: {
				globals: {
					console: true,
					module: true
				}
			},
			files: ['src/js/*.js']
		},
		uglify: {
			dist: {
				files: [
					{
						expand: true,
						cwd: 'src/js',
						src: '*.js',
						dest: 'assets/js/',
						ext: '.min.js'
					}
				]
			}
		},
		cssmin: {
			minify: {
				expand: true,
				cwd: 'assets/css',
				src: ['*.css', '!**/*.min.css'],
				dest: 'assets/css/',
				ext: '.min.css'
			}
		},
		watch: {
			options: {
				livereload: true
			},
			sass: {
				files: ['src/sass/*'],
				tasks: ['sass']
			}
		}
	});

	grunt.registerTask('default', ['watch']);
	grunt.registerTask('build', [
		'clean',
		'copy:fonts',
		'imagemin',
		'sass',
		'cssmin',
		'jshint',
		'uglify',
		'nodsstore'
	]);
	grunt.registerTask('deploy', ['build', 'copy:deploy']);
	grunt.registerTask('nodsstore', function() {
		grunt.file.expand({
			filter: 'isFile',
			cwd: '.'
		}, ['**/.DS_Store']).forEach(function(file) {
			grunt.file.delete(file);
		})
	});
};
