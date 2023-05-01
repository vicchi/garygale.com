sass = require("sass");
module.exports = function (grunt) {
    require("load-grunt-tasks")(grunt);

    grunt.initConfig({
        pkg: grunt.file.readJSON("package.json"),
        clean: {
            rebuild: ["public/"],
        },
        concat: {
            preload: {
                src: ["src/js/google-tracking.js"],
                dest: "public/assets/js/site.js",
            },
        },
        copy: {
            html: {
                files: [
                    {
                        expand: true,
                        cwd: "src/html",
                        src: ["**/*.html"],
                        dest: "public/",
                    },
                ],
            },
            icons: {
                files: [
                    {
                        expand: true,
                        cwd: "src/images",
                        src: ["favicon.ico"],
                        dest: "public/",
                    },
                ],
            },
            misc: {
                files: [
                    {
                        expand: true,
                        cwd: "src/misc",
                        src: ["**/*", "**/.*"],
                        dest: "public/",
                    },
                ],
            },
        },
        cssmin: {
            dist: {
                files: [
                    {
                        expand: true,
                        cwd: "public/assets/css",
                        src: ["*.css", "!*.min.css"],
                        dest: "public/assets/css",
                        ext: ".min.css",
                    },
                ],
            },
        },
        imagemin: {
            images: {
                files: [
                    {
                        expand: true,
                        cwd: "src/",
                        src: ["images/**/*.png", "images/**/*.jpg"],
                        dest: "public/assets/",
                    },
                ],
            },
        },
        sass: {
            options: {
                implementation: sass,
                outputStyle: "expanded",
                indentType: "tab",
                indentWidth: 1,
            },
            dist: {
                files: {
                    "public/assets/css/site.css": "src/sass/site.scss",
                },
            },
        },
        uglify: {
            dist: {
                files: {
                    "public/assets/js/site.min.js": "public/assets/js/site.js",
                },
            },
        },
        watch: {
            options: {
                livereload: true,
            },
            grunt: {
                files: ["Gruntfile.js"],
                tasks: ["build"],
            },
            js: {
                files: ["src/js/*.js"],
                tasks: ["concat", "uglify"],
            },
            uglify: {
                files: ["!public/assets/js/*.min.js"],
                tasks: ["uglify"],
            },
            sass: {
                files: ["src/sass/*.scss"],
                tasks: ["sass"],
            },
            cssmin: {
                files: [
                    "public/assets/css/*.css",
                    "!public/assets/css/*.min.css",
                ],
                tasks: ["cssmin"],
            },
            html: {
                files: ["src/html/**/*.html"],
                tasks: ["copy:html"],
            },
            robots: {
                files: ["src/misc/**"],
                tasks: ["copy:robots"],
            },
        },
    });

    grunt.registerTask("default", ["watch"]);

    grunt.registerTask("nodsstore", function () {
        grunt.file
            .expand(
                {
                    filter: "isFile",
                    cwd: ".",
                },
                ["**/.DS_Store"]
            )
            .forEach(function (file) {
                grunt.file.delete(file);
            });
    });

    grunt.registerTask("build", [
        "concat",
        "copy",
        "imagemin",
        "sass",
        "cssmin",
        "uglify",
    ]);
    grunt.registerTask("rebuild", ["clean", "build"]);
};
