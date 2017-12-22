/*global Drupal, document, jQuery */

(function ($) {
  "use strict";


  Drupal.behaviors.d_lang_dropdown = {
    attach: function (context, settings) {
      $(document).ready(function () {
        var $body = $('body');
        if ($body.hasClass('d-lang-added')) {
          return;
        }
        var  $languageswitcher = $('#block-languageswitcher:not(dropdown)');
        var  $active_lang = $languageswitcher.find("ul li.is-active");
        var  active_lang_code = $active_lang.attr("hreflang");
        var  $div = $("<div>", {
          "class": "active-lang-code dropdown-toggle",
          "aria-haspopup": "false",
          "data-toggle": "dropdown"
        });
        $div.html(active_lang_code);
        $languageswitcher.find(".content").prepend($div);
        $languageswitcher.attr("aria-labelledby", "dropdownMenuLink");
        $languageswitcher.find(".links").addClass("dropdown-menu");
        $languageswitcher.find(".links")
          .prepend("<li>" + $active_lang.first().text() + "</li>");
        $languageswitcher.addClass("dropdown");
        $body.addClass("d-lang-added")
      });
    }
  };
})(jQuery);
