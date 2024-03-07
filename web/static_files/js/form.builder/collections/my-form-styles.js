/**
 * Copyright (C) Baluart.COM - All Rights Reserved
 *
 * @description JavaScript Form Builder for Easy Forms
 * @since 1.9
 * @author Balu
 * @copyright Copyright (c) 2015 - 2019 Baluart.COM
 * @license http://codecanyon.net/licenses/faq Envato marketplace licenses
 * @link http://easyforms.baluart.com/ Easy Forms
 */

define([
    "jquery" , "underscore" , "backbone"
    , "models/style"
], function(
    $, _, Backbone
    , StyleModel
    ) {
    return Backbone.Collection.extend({
        model: StyleModel
        , initialize: function() {
        }
    });
});