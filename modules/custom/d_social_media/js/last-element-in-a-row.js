/**
 * Style all far right elements in horizontal list.
 */
(function (Drupal) {
  Drupal.behaviors.dSocialMedia = {
    attach: function (context) {
      var lastElement = false;
      var els = context.querySelectorAll('.social-media-wrapper ul li');
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
})(Drupal);
