(function($) {

    function polyglot() {
        registerEvents();
    }

    var spinner = "<div class='spinner' style='float:none; visibility:visible;'></div>";

    function registerEvents(scope)
    {
        if (scope) {
            scope.on("click", '*[data-polyglot-ajax="click-popup"]', triggerPopupClickEventAjaxCall);
            scope.on("click", '*[data-polyglot-ajax="click"]', triggerClickEventAjaxCall);
            scope.find('*[data-ajax="autoload"]').each(triggerAjaxCall);
        } else {
            $('.wrap').on("click", '*[data-polyglot-ajax="click-popup"]', triggerPopupClickEventAjaxCall);
            $('.wrap').on("click", '*[data-polyglot-ajax="click"]', triggerClickEventAjaxCall);
            $('*[data-polyglot-ajax="autoload"]').each(triggerAjaxCall);
        }
    }

    function createDialogFromHtml(html)
    {
        var node = $(html);

        var buttons = null;
        if (node.find('form').length > 0) {
            buttons = {
                "Save": function() {
                    showSpinner($(this).find(".ui-dialog-buttonset button:last"));
                    $(this).find('form').submit();
                }
            };
        } else {
            buttons = {
                "Close": function() {
                    $(this).dialog("close");
                }
            };
        }

        node.dialog({
            'dialogClass'   : 'wp-dialog',
            'modal'         : true,
            'closeOnEscape' : true,
            'width'         : 500,
            'height'        : 500,
            'title'         : 'Localization',
            'buttons'       : buttons,
            'zIndex'        : 1010
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

    function triggerPopupClickEventAjaxCall(evt)
    {
        evt.preventDefault();
        var el = $(this),
            action = el.attr("data-polyglot-ajax-action");

        showSpinner(el);
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: "polyglot_ajax",
                polyglot_ajax_action: action,
                param: el.data("polyglot-ajax-param")
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

    function triggerClickEventCall(evt)
    {
        evt.preventDefault();
        var el = $(this);

        switch (el.data("polyglot-click")) {
            case "switchTranslation" : return switchTranslation(el);

        }
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
                param: el.data("polyglot-ajax-param")
            },
        }).done(function(data) {
            target.html(data);
            registerEvents(target);
        });
    };

    function switchTranslation(el)
    {
        showSpinner(el);
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: "polyglot_ajax",
                polyglot_ajax_action: "switchTranslation",
                param: {
                    locale : el.prev("select").val(),
                    postId : el.data("polyglot-pid")
                }
            }
        }).done(function(html) {
            hideSpinner(el);
            var node = $("<div class=\"polyglot-template\">" +html + "</div>");
            createDialogFromHtml(node);
            registerEvents(node);
        });

    }

    $(document).ready(polyglot);

})(jQuery);
