(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.d_lang_dropdown = {
    attach: function (context, settings) {
      let $body = $("body");
      if ($body.hasClass("d-lang-added")) {
        return;
      }

      let $languageSwitcher = $(".block.language-switcher-language-url .block__content:not(.dropdown)", $body);
      let $links = $(".links", $languageSwitcher);
      let $activeLang = $("ul li.is-active", $languageSwitcher);
      let activeLangCode = $activeLang.attr("hreflang");

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
