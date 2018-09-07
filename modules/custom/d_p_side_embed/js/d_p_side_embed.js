/**
 * @file
 * The script that scales facebook video iframes.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.d_p_side_embed = {
    attach: function (context, settings) {

      function resizeVideo($video, $wrapper, aspectRatio) {
        var wrapperDimensions = {
          width: $wrapper.width(),
          height: $wrapper.height()
        };
        console.log(wrapperDimensions);
        var videoDimensions = {
          width: $wrapper.height() * aspectRatio.horizontal,
          height: $wrapper.height()
        };
        if (videoDimensions.width > wrapperDimensions.width) {
          videoDimensions.width = wrapperDimensions.width;
          videoDimensions.height = $wrapper.width() * aspectRatio.vertical;
        }
        $video.css(videoDimensions);
      }

      $('.paragraph--type--d-p-side-embed', context).once('d_p_side_embed').each(function () {
        var $videoWrapper = $(this).find('.d-p-side-embed-embed');
        var $video = $videoWrapper.find('iframe');
        if ($video.length === 0 || $video.attr('src').indexOf('https://www.facebook.com') !== 0) {
          return;
        }

        var aspectRatio = {
          horizontal: $video.attr('width') / $video.attr('height'),
          vertical: $video.attr('height') / $video.attr('width')
        };
        $video.addClass('facebook-video');
        resizeVideo($video, $videoWrapper, aspectRatio);
        $(window).resize(function () {
          resizeVideo($video, $videoWrapper, aspectRatio);
        });
      });
    }
  };
})(jQuery);
