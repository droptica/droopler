(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.lang_dropdown = {
    attach: function (context, settings) {
      let $body = $("body");
      let $html = $("html");
      if ($body.hasClass("d-lang-added")) {
        return;
      }

      let $languageSwitcher = $(".block.language-switcher-language-url .block__content:not(.dropdown)", $body);
      let $links = $(".links", $languageSwitcher);
      let activeLangCode = $html.attr("lang");

      let $div = $("<a>", {
        "href": "#",
        "class": "dropdown-toggle",
        "role": "button",
        "id": "dropdownMenuLink",
        "data-bs-toggle": "dropdown",
        "aria-expanded": "false"
      });
      $div.html(activeLangCode);

      $languageSwitcher
        .prepend($div)
        .addClass("dropdown");

      $links
        .removeClass("nav")
        .addClass("dropdown-menu")
        .attr("aria-labelledby", "dropdownMenuLink");

      $("li", $links).addClass("dropdown-item");

      $body.addClass("d-lang-added");
    }
  };

})(jQuery, Drupal);
