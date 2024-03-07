/**
 * Copyright (C) Baluart.COM - All Rights Reserved
 *
 * @since 1.9.1
 * @author Balu
 * @copyright Copyright (c) 2015 - 2019 Baluart.COM
 * @license http://codecanyon.net/licenses/faq Envato marketplace licenses
 * @link http://easyforms.baluart.com/ Easy Forms
 */
(function($) {
    $.fn.conditionsWidget = function(options) {

        var settings = $.extend({
            field: '',
            initDepends: '',
            depends: '',
            url: '',
            data: '',
            loading: true,
            loadingClass: 'data-loading',
            ruleBuilderSelector: '.rule-builder',
            idParam: 'id',
            nameParam: 'name',
            initialize: false,
            operators: {
                // Text component
                "text": textOperators,
                "tel": textOperators,
                "color": colorOperators,
                "url": textOperators,
                "password": textOperators,
                // Number component
                "number": numberOperators,
                "range": numberOperators,
                // Date component
                "date": dateOperators,
                "datetime-local": dateOperators,
                "time": dateOperators,
                "month": dateOperators,
                "week": dateOperators,
                // Email component
                "email": emailOperators,
                // TextArea component
                "textarea": textOperators,
                // Select List component
                "select": selectOperators,
                // Checkbox component
                "checkbox": checkboxOperators,
                // Radio component
                "radio": radioOperators,
                // Hidden component
                "hidden": hiddenOperators,
                // File component
                "file": fileOperators,
                // Button component
                "button": buttonOperators,
                // Form
                "form": formOperators
            },
            listen: function (i, depends, len) {
                $('#' + depends[i]).on('conditions:change change', function (e) {
                    //
                });
            },
            process: function ($el) {
                var $rb = $el.closest(settings.ruleBuilderSelector);
                var $field = $(settings.field);
                $.ajax({
                    data: {},
                    url: settings.url,
                    beforeSend: function() {
                        $rb.removeClass(settings.loadingClass).addClass(settings.loadingClass);
                    }
                }).done(function(resp) {
                    if (typeof resp.variables !== 'undefined' && resp.variables.length > 0) {
                        var opts = {
                            "variables": resp.variables,
                            "variable_type_operators": settings.operators
                        };

                        var data = $field.val();

                        if (typeof data !== 'undefined' && data.length > 0) {
                            opts = $.extend({}, opts, {
                                data: JSON.parse(data)
                            });
                        }

                        $el.conditionsBuilder(opts);

                        $el.on('keyup change', ':input', function(e) {
                            $field.val(JSON.stringify($el.conditionsBuilder('data')));
                        });

                        if ('MutationObserver' in window) {
                            $el.on('DOMNodeInserted DOMNodeRemoved', function(e) {
                                setTimeout(function() {
                                    $field.val(JSON.stringify($el.conditionsBuilder('data')));
                                }, 200);
                            });
                        } else {
                            $el.on('remove', '.remove', function(e) {
                                setTimeout(function() {
                                    $field.val(JSON.stringify($el.conditionsBuilder('data')));
                                }, 200);
                            });
                        }
                    } else {
                        $el.html('');
                    }
                }).always(function() {
                    $rb.removeClass(settings.loadingClass);
                });
            },
        }, options);
        this.each(function() {
            var self = this;
            self.$element = $(this);
            var $el = self.$element;
            var depends = settings.depends, len = depends.length;
            var initDepends = settings.initDepends || settings.depends;
            var i;
            for (i = 0; i < len; i++) {
                settings.listen(i, depends, len);
            }
            if (self.initialize === true) {
                for (i = 0; i < initDepends.length; i++) {
                    $('#' + initDepends[i]).trigger('conditions:change');
                }
            }
            settings.process($el);
        });
        return this;
    };

}(jQuery));