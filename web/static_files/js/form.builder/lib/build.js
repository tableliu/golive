({
    name: "../main",
    out: "../main-built.js"
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
        'gradpick': {
            exports: 'Gradpick'
        }
    }
    , paths: {
        app         : ".."
        , tinyMCE     : "tinymce/tinymce.min"
        , collections : "../collections"
        , data        : "../data"
        , models      : "../models"
        , helper      : "../helper"
        , templates   : "../templates"
        , views       : "../views"
    }
})
