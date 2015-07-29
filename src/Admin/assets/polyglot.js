(function($) {

    function polyglot() {
        registerEvents();
    }

    var spinner = "<div class='spinner' style='float:none; visibility:visible;'></div>";

    function registerEvents(scope)
    {
        if (scope) {
            scope.find('*[data-polyglot-ajax="click-popup"]').click(triggerPopupClickEventAjaxCall);
            scope.find('*[data-polyglot-ajax="click"]').click(triggerClickEventAjaxCall);
            scope.find('*[data-ajax="autoload"]').each(triggerAjaxCall);
            //scope.find('*[data-polyglot-ajax="submit"]').submit(triggerSubmitEventAjaxCall);
        } else {
            $('*[data-polyglot-ajax="click-popup"]').click(triggerPopupClickEventAjaxCall);
            $('*[data-polyglot-ajax="click"]').click(triggerClickEventAjaxCall);
            //$('*[data-ajax="submit"]').submit(triggerSubmitEventAjaxCall);
            $('*[data-polyglot-ajax="autoload"]').each(triggerAjaxCall);
        }
    }

    function createDialogFromHtml(html)
    {
        var node = $(html);
        node.dialog({
            'dialogClass'   : 'wp-dialog',
            'modal'         : true,
            'closeOnEscape' : true,
            'width'         : 500,
            'height'        : 500,
            'buttons'       : {
                "Save": function() {
                    showSpinner($(this).find(".ui-dialog-buttonset button:last"));
                    $(this).find('form').submit();
                }
            }
        });

        return node;
    }

    function showSpinner(el)
    {
        el.after($(spinner));
    }

    function hideSpinner(el)
    {
        el.parent().find(".spinner").remove();
    }

    function triggerPopupClickEventAjaxCall()
    {
        var el = $(this),
            action = el.attr("data-polyglot-ajax-action");

        showSpinner(el);
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: "polyglot_ajax",
                polyglot_ajax_action: action,
                param: el.data("ajax-param")
            }
        }).done(function(html) {
            hideSpinner(el);
            var node = $("<div class=\"polyglot-template\">" +html + "</div>");
            createDialogFromHtml(node);
            registerEvents(node);
        });
    }

    function triggerClickEventAjaxCall(evt)
    {
        evt.preventDefault();
        triggerAjaxCall.apply(this);
    }

    function triggerSubmitEventAjaxCall(evt)
    {
        evt.preventDefault();

        var el = $(this),
            action = el.attr("data-polyglot-ajax-action"),
            target = el.is("*[data-polyglot-ajax-target]") ? $(el.attr("data-polyglot-ajax-target")) : el;

        var formData = el.serialize();
        if (formData != "") {
            formData += "&";
        }
        formData += "action=polyglot_ajax&&polyglot_ajax_action=" + action;

        target.html('<div class="loading">Loading...</div>');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: formData,
        }).done(function(data) {
            target.html(data);
        });
    }

    function triggerAjaxCall()
    {
        var el = $(this),
            action = el.data("polyglot-ajax-action"),
            target = el.is("*[data-polyglot-ajax-target]") ? $(el.data("polyglot-ajax-target")) : el;

        target.html('<div class="loading">Loading...</div>');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: "polyglot_ajax",
                polyglot_ajax_action: action,
                param: el.data("ajax-param")
            },
        }).done(function(data) {
            target.html(data);
            registerEvents(target);
        });
    };

    $(document).ready(polyglot);

})(jQuery);
