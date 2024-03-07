/**
 * Copyright (C) Baluart.COM - All Rights Reserved
 *
 * @description JavaScript Form Builder for Easy Forms
 * @since 1.0
 * @author Balu
 * @copyright Copyright (c) 2015 - 2019 Baluart.COM
 * @license http://codecanyon.net/licenses/faq Envato marketplace licenses
 * @link http://easyforms.baluart.com/ Easy Forms
 *
 * Based on:
 * Bootstrap 2.3.1 Form Builder
 * Copyright (C) 2012 Adam Moore
 * Licensed under MIT (https://github.com/minikomi/Bootstrap-Form-Builder/blob/gh-pages/LICENSE)
 */

require.config({
    baseUrl: options.libUrl
    , shim: {
        underscoreBase: {
            exports: '_'
        },
        'underscore': {
            deps: ['underscoreBase'],
            exports: '_'
        },
        'backbone': {
            deps: ['underscore', 'jquery'],
            exports: 'Backbone'
        },
        'bootstrap': {
            deps: ['jquery']
        },
        'jquery.fontselect': {
            deps: ['jquery']
        },
        'jquery.flexdatalist': {
            deps: ['jquery']
        },
        'jquery.cookie': {
            deps: ['jquery']
        },
        'jquery.bsAlerts': {
            deps: ['jquery']
        },
        'spectrum': {
            deps: ['jquery']
        },
        'spectrum-kv': {
            deps: ['jquery']
        },
        'polyglot': {
            exports: 'Polyglot'
        },
        'prism': {
            exports: 'Prism'
        },
        'tinyMCE': {
            exports: 'tinyMCE'
        },
        'simplebar': {
            exports: 'SimpleBar'
        },
        'cssjson': {
            exports: 'CSSJSON'
        },
        'grapick': {
            exports: 'Grapick'
        }
    }
    , paths: {
        app           : ".."
        , tinyMCE     : 'tinymce/tinymce.min'
        , collections : "../collections"
        , data        : "../data"
        , models      : "../models"
        , helper      : "../helper"
        , templates   : "../templates"
        , views       : "../views"
    }
});
require([ 'app/app'], function(app){
    app.initialize();
});