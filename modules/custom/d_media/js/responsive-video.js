/**
 * @file
 * Behaviors for embed video.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * DMediaResponsiveVideo 'class'.
   *
   * @param $video
   *   Video element.
   * @param settings
   *   Object with settings to override default with.
   *
   * @constructor
   */
  function DMediaResponsiveVideo($video, settings) {

    /**
     * Video object.
     *
     * @type {jQuery}
     */
    this.$video = $video;

    /**
     * Video object wrapper.
     *
     * @type {jQuery}
     */
    this.$wrapper = $video.parent();

    /**
     * The aspect ratio of this video.
     *
     * @type {number}
     */
    this.aspectRatio = parseFloat($video.data('aspect-ratio'));

    // Init.
    this.cover();
  }

  /**
   * Set video size bases on parent and video aspect ratio.
   */
  DMediaResponsiveVideo.prototype.setVideoSize = function () {
    var wrapperDimensions = {
      width: this.$wrapper.outerWidth(),
      height: this.$wrapper.outerHeight(),
    };

    var videoDimensions = {
      width:  wrapperDimensions.width + 'px',
      height: wrapperDimensions.width * this.aspectRatio + 'px',
    };

    // The case where the wrapper is higher than video.
    if (parseInt(videoDimensions.height) < wrapperDimensions.height) {
      videoDimensions = {
        width:  wrapperDimensions.height / this.aspectRatio + 'px',
        height: wrapperDimensions.height + 'px',
      };
    }

    this.$video.css(videoDimensions);
  };

  /**
   * Make video cover the wrapper.
   */
  DMediaResponsiveVideo.prototype.cover = function () {
    var self = this;

    this.setVideoSize();
    $(window).resize(function () {
      self.setVideoSize();
    });
  };

  /**
   * A jQuery interface.
   *
   * @param settings
   *
   * @returns {jQuery}
   */
  DMediaResponsiveVideo.jQueryInterface = function () {
    return new DMediaResponsiveVideo($(this));
  };

  $.fn.dMediaResponsiveVideo = DMediaResponsiveVideo.jQueryInterface;

  /**
   * Drupal behaviors for cover embed video..
   *
   * @type {{attach: Drupal.behaviors.d_media_responsive_video.attach}}
   */
  Drupal.behaviors.d_media_responsive_video = {
    attach: function (context) {
      $('iframe.video-embed--cover', context).each(function () {
        $(this).dMediaResponsiveVideo();
      });
    }
  };

})(jQuery, Drupal);
