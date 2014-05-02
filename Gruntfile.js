module.exports = function(grunt) {
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-compass');
    grunt.loadNpmTasks('grunt-contrib-imagemin');

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		compass: {
			dist: {
				options: {
					sassDir: 'src/sass',
					cssDir: 'src/css'
				}
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
						dest: 'img/',
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
						dest: 'img/',
						ext: '.jpg'
					}
				]
			}
		},
		clean: {
			dist: {
				src: ['img/*', 'css/*', 'js/*', 'src/css/*']
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
			my_target: {
				files: [
					{
						expand: true,
						cwd: 'src/js',
						src: '*.js',
						dest: 'js/',
						ext: '.min.js'
					}
				]
			}
		},
		cssmin: {
			minify: {
				expand: true,
				cwd: 'src/css',
				src: '*.css',
				dest: 'css/',
				ext: '.min.css'
			}
		},
		watch: {
			dist: {
				options: {
					livereload: true
				},
				files: ['src/**/*'],
				tasks: ['compass', 'cssmin', 'jshint', 'uglify']
			}
		}
	});

	grunt.registerTask('default', [
		'clean',
		'imagemin',
		'compass',
		'cssmin',
		'jshint',
		'uglify'
	]);

	grunt.registerTask('nodsstore', function() {
		grunt.file.expand({
			filter: 'isFile',
			cwd: '.'
		}, ['**/.DS_Store']).forEach(function(file) {
			grunt.file.delete(file);
		})
	});
};
