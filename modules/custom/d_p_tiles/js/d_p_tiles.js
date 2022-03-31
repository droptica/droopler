/**
 * @file
 * A script that enables Masonry.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.d_p_tiles = {
    attach: function (context, settings) {

      // Enable Masonry.
      $(document).ready(function () {
        let $wrapper = $('.d-tiles, .d-side-tiles', context);
        let videoElements = $wrapper.find('.video-embed');
        let timer;

        function scaleVideo(){
          if (videoElements) {
            let resizedImage = $('.image-style-tiles-thumbnail').first();
            let imageHeight = resizedImage.height() + 4;

            videoElements.each(function () {
              $(this).css('max-height', imageHeight + 'px');
            });
          }
        }

        window.onresize = function () {
          clearTimeout(timer);
          timer = setTimeout(scaleVideo, 100);
        };
        videoElements.each(function () {
          $(this).addClass('d-tiles-item');
        });

        let $grid = $wrapper.masonry({
          itemSelector: '.d-tiles-item',
          columnWidth: '.d-tiles-sizer',
          percentPosition: true,
        });

        $(window).on('lazyloaded', function () {
          $grid.masonry('layout');
        });

        // Add titles to items.
        $wrapper.find('.d-tiles-item').each(function () {
          let alt = $(this).find('img').attr('alt');
          let subalt = '';
          if (alt) {
            let parts = alt.split('/', 2);
            if (parts.length > 1) {
              alt = parts[0];
              subalt = parts[1];
            }
            let $caption_wrapper = $('<div class="d-tiles-caption"></div>').appendTo($(this));
            let $caption = $('<div></div>').appendTo($caption_wrapper).text(alt);
            if (subalt !== '') {
              $('<small></small>').appendTo($caption).text(subalt);
            }
          }
        });
      });
    }
  };

})(jQuery, Drupal);
