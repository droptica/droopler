/**
 * Style all far right elements in horizontal list.
 */
(function ($, Drupal) {
  Drupal.behaviors.socialMedia = {
    attach: function (context, settings) {
      var lastElement = false;
      var els = document.querySelectorAll('.social-media-wrapper ul li');
      for (var i = 0; i < els.length; i++) {
        var el = els[i]; // current element
        if (lastElement && lastElement.offsetTop !== el.offsetTop) {
          lastElement.className += " last-element";
        }
        lastElement = el;
      }
      els[els.length - 1].className += " last-element";
    }
  };
})(jQuery, Drupal);
