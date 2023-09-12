/**
 * @file
 * The script that scales facebook video iframes.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.d_p_side_embed = {
    attach: function (context, settings) {

      function resizeVideo($video, $wrapper, aspectRatio) {
        var wrapperDimensions = {
          width: $wrapper.width(),
          height: $wrapper.height()
        };
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

      $('.paragraph--type--d-p-side-embed', context).each(function () {
        var $this = $(this);
        var embedType = '';
        var $embedWrapper = $(this).find('.d-p-side-embed-embed');
        var $embed = $embedWrapper.find('iframe');
        if ($embed.length === 0) {
          return;
        }

        if ($embed.attr('src').indexOf('//www.facebook.com') >= 0) {
          embedType = 'facebook';
          var aspectRatio = {
            horizontal: $embed.attr('width') / $embed.attr('height'),
            vertical: $embed.attr('height') / $embed.attr('width')
          };
          resizeVideo($embed, $embedWrapper, aspectRatio);
          $(window).resize(function () {
            resizeVideo($embed, $embedWrapper, aspectRatio);
          });
        }

        if ($embed.attr('src').indexOf('//www.youtube.com/embed') >= 0) {
          embedType = 'youtube';
        }

        if ($embed.attr('src').indexOf('//player.vimeo.com') >= 0) {
          embedType = 'vimeo';
        }

        if ($embed.attr('src').indexOf('dailymotion.com/embed') > 0) {
          embedType = 'dailymotion';
        }

        if ($embed.attr('src').indexOf('//www.google.com/maps') > 0) {
          embedType = 'google-maps';
        }

        $embed.addClass(embedType + '-embed');
        $embed.closest('.d-p-side-embed-embed').addClass(embedType + '-wrapper');

        // Set proper height for full width embed element.
        $(window).on("resize", function () {
          if ($(window).width() > 767) {
            var $contentH = parseInt($this.find('.embed-side-full .d-p-side-embed-content').innerHeight());
            if (!isNaN($contentH)) {
              var $height = $contentH + 300;
              $this.find('.d-p-side-embed-embed').css('height', $height);
            }
          }
        }).resize();

      });
    }
  };
})(jQuery, Drupal);
