import * as gulp from 'gulp';
import * as gutil from 'gulp-util';
import * as env from 'gulp-env';
import * as electron from 'gulp-atom-electron';
import * as symdest from 'gulp-symdest';
import * as ts from 'gulp-typescript';
import * as debug from 'gulp-debug';
import * as webpack from 'webpack';
import * as WebpackDevServer from 'webpack-dev-server';
import * as del from 'del';
import * as gulpSequence from 'gulp-sequence';

import {parameterList as webpackDevConfig}  from './config/webpack.dev';
import {parameterList as webpackProdConfig} from './config/webpack.prod';
import {environment} from './config/environment';
import {path} from './config/path.common';

let compiler = null;

gulp.task('init:env:dev', function(callback) {
    env.set({ENVIRONMENT: environment.development});
    callback();
});

gulp.task('init:env:prod', function(callback) {
    env.set({ENVIRONMENT: environment.production});
    callback();
});

gulp.task('clean:artifact', function(callback) {
    del(path.artifactDir + '/**/*', { force: true });
    callback();
});

gulp.task('clean:dist', function(callback) {
    del(path.distDir + '/**/*', { force: true });
    callback();
});

gulp.task('clean:package', function(callback) {
    del(path.packageDir + '/**/*', { force: true });
    callback();
});

gulp.task('clean', gulpSequence(
    'clean:artifact',
    'clean:dist',
    'clean:package'
));

gulp.task('build:dist:webpack:dev', ['init:env:dev'], function(callback) {
    buildWebpack(webpackDevConfig, callback);
});

gulp.task('build:dist:webpack:prod', ['init:env:prod'], function(callback) {
    buildWebpack(webpackProdConfig, callback);
});

gulp.task('build:dist:electron:dev', ['build:dist:webpack:dev'], function() {
    return buildElectron();
});

gulp.task('build:dist:electron:prod', ['build:dist:webpack:prod'], function() {
    return buildElectron();
});

gulp.task('run:web:dev', ['build:dist:webpack:dev'], function(callback) {
    runWebpackServer(webpackDevConfig, callback);
});

gulp.task('run:web:prod', ['build:dist:webpack:prod'], function(callback) {
    runWebpackServer(webpackProdConfig, callback);
});

gulp.task('build:package:windows', function() {
    return electronPackage('win32', path.winPackageDir);
});

gulp.task('build:package:linux', function() {
    return electronPackage('linux', path.linuxPackageDir);
});

gulp.task('build:package:osx', function() {
    return electronPackage('darwin', path.osxPackageDir);
});

gulp.task('build:package:web', function() {
    return webpackPackage();
});

gulp.task('build:package:windows:dev', gulpSequence(
    'build:dist:electron:dev',
    'build:package:windows'
));

gulp.task('build:package:windows:prod', gulpSequence(
    'build:dist:electron:prod',
    'build:package:windows'
));

gulp.task('build:package:linux:dev', gulpSequence(
    'build:dist:electron:dev',
    'build:package:linux'
));

gulp.task('build:package:linux:prod', gulpSequence(
    'build:dist:electron:prod',
    'build:package:linux'
));

gulp.task('build:package:osx:dev', gulpSequence(
    'build:dist:electron:dev',
    'build:package:osx'
));

gulp.task('build:package:osx:prod', gulpSequence(
    'build:dist:electron:prod',
    'build:package:osx'
));

gulp.task('build:package:web:dev', gulpSequence(
    'build:dist:webpack:dev',
    'build:package:web'
));

gulp.task('build:package:web:prod', gulpSequence(
    'build:dist:webpack:prod',
    'build:package:web'
));

gulp.task('build:packages:dev', gulpSequence(
    'build:dist:webpack:dev',
    'build:dist:electron:dev',
    [
        'build:package:web',
        'build:package:windows',
        'build:package:linux',
        'build:package:osx',
    ]
));

gulp.task('build:packages:prod', gulpSequence(
    'build:dist:webpack:prod',
    'build:dist:electron:prod',
    [
        'build:package:web',
        'build:package:windows',
        'build:package:linux',
        'build:package:osx',
    ]
));

function buildWebpack(config, callback) {
    compiler = webpack(config, function(error, stats) {
        if (error) {
            throw new gutil.PluginError('webpack', error);
        }
        gutil.log('Webpack stats: ', stats.toString({colors: true}));
        callback();
    });
}

function buildElectron() {
    return gulp.src([
        path.artifactDir + '/src/electron/main.js',
        path.sourceDir + '/electron/package.json',
        path.webpackDistDir + '/**/*',
    ]).pipe(gulp.dest(path.electronDistDir));
}

function runWebpackServer(config, callback) {
    new WebpackDevServer(compiler, config.devServer).listen(8080, 'localhost', function (error) {
        if (error) {
            throw new gutil.PluginError('webpack-dev-server', error);
        }
        gutil.log('Server will run at:', gutil.colors.bgGreen('http://localhost:8080/'));
        callback();
    });
}

function electronPackage(platform, packageDir) {
    return gulp.src(path.electronDistDir + '/**/*')
        .pipe(electron({
            version: '1.4.14',
            platform: platform
        })).pipe(symdest(packageDir));
}

function webpackPackage() {
    return gulp.src(path.webpackDistDir + '/**/*')
        .pipe(gulp.dest(path.webPackageDir));
}