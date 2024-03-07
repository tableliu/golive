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
    'jquery', 'underscore', 'backbone'
], function($, _, Backbone) {
    return Backbone.Model.extend({
        setAttribute: function(name, value) {
            var attributes = this.get("attributes");
            attributes[name] = value;
            this.set("attributes", attributes);
        }
        , getAttributes: function(name) {
            var styles = this.get("styles");
            return styles["attributes"];
        }
        , getAttribute: function(name) {
            var styles = this.get("styles");
            return styles["attributes"][name];
        }
    });
});