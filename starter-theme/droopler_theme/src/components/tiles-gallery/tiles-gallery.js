(function ($, Drupal) {

  'use strict'

  /**
   * TilesGallery 'class'.
   *
   * @param {jQuery} $wrapper
   *   Main wrapper of tiles gallery..
   * @param {object} settings
   *   Object with settings to override default with.
   *
   * @constructor
   */
  function TilesGallery($wrapper, settings) {
    /**
     * Default settings.
     */
    this.defaultSettings = {
      itemSelector: '.tiles-gallery-item',
      sizerSelector: '.tiles-gallery__sizer',
      parentSelector: '.tiles-gallery-parent',
      captionSelector: '.tiles-gallery-item__caption',
      captionTitleSelector: '.tiles-gallery-item__caption-title',
      captionSubtitleSelector: '.tiles-gallery-item__caption-subtitle',
      videoSelector: '.video-embed',
      standardImageSelector: '.tiles-gallery-item--standard img',
    }

    /**
     * Apply custom settings.
     *
     * @type {object}
     */
    this.settings = $.extend(true, this.defaultSettings, settings || {});

    /**
     * Main wrapper.
     *
     * @type {jQuery}
     */
    this.$wrapper = $wrapper;

    /**
     * Parent item.
     *
     * @type {jQuery}
     */
    this.$parent  = this.$wrapper.closest(this.settings.parentSelector);

    /**
     * Item with masonry initialized.
     *
     * @type {jQuery}
     */
    this.$masonry = null;

    this.initMasonry();
    this.prepareTilesCaptions();
    this.handleIframes();
  }

  TilesGallery.prototype.handleIframes = function () {
    const self = this;
    const $iframes = this.$wrapper.find('.video-embed:not(.video-embed--cover)');
    
    $iframes.each(function() {
      $(this).css('pointer-events', 'none');
      
      if (this.complete) {
        self.$masonry.masonry('layout');
      } else {
        this.onload = function() {
          self.$masonry.masonry('layout');
        };
      }
    });

    $(document).on('cbox_complete', function() {
      $('.video-embed--cover').css('pointer-events', 'auto');
    });
  };

  /**
   * Init masonry.
   */
  TilesGallery.prototype.initMasonry = function () {
    const self = this;
    
    this.$wrapper.masonry({
      itemSelector: self.settings.itemSelector,
      columnWidth: self.settings.sizerSelector,
      percentPosition: true,
      initLayout: false
    });
    
    this.$masonry = this.$wrapper;
    
    this.$wrapper.imagesLoaded(function () {
      self.resizeVideos();
      self.$masonry.masonry('layout');
      self.bindMasonryEvents();
    }).done(function () {
      $(window).trigger('resize');
    });

    $(window).on('resize', function() {
      self.resizeVideos();
      self.$masonry.masonry('layout');
    });
  };

  /**
   * Bind masonry events.
   */
  TilesGallery.prototype.bindMasonryEvents = function () {
    const self = this;

    this.$masonry.on('layoutComplete', function () {
      self.$parent.css('min-height', self.$wrapper.height());
    });
  };

  /**
   * Resize videos.
   */
  TilesGallery.prototype.resizeVideos = function () {
    const DESIRED_RATIO = 0.65848452508;
    const $videos = this.$wrapper.find(this.settings.videoSelector);
    
    $videos.each(function () {
      const $video = $(this);
      const width = $video.width();
      const finalHeight = width * DESIRED_RATIO + 1;
      
      $video.css('height', finalHeight + 'px');
    });
  };

  /**
   * Prepare tiles captions.
   */
  TilesGallery.prototype.prepareTilesCaptions = function () {
    const self = this;

    this.$wrapper.find(this.settings.itemSelector).each(function () {
      const $image = $(this).find('img');

      if ($image.length == 0) {
        $(this).find(self.settings.captionSelector).remove();

        return;
      }

      let [title, subtitle] = $image.attr('alt').split('/', 2);

      if (title !== undefined) {
        $(this).find(self.settings.captionTitleSelector).text(title);
      }

      if (subtitle !== undefined) {
        $(this).find(self.settings.captionSubtitleSelector).text(subtitle);
      }
    });
  };

  /**
   * A jQuery interface.
   *
   * @param {object} settings
   *   Object with settings to override defaults with.
   *
   * @returns {jQuery}
   */
  TilesGallery.jQueryInterface = function (settings) {
    return this.each(function () {
      new TilesGallery($(this), settings);
    });
  };

  $.fn.tilesGallery = TilesGallery.jQueryInterface;

  /**
   * Main behavior for tiles gallery.
   */
  Drupal.behaviors.tilesGallery = {
    attach: function (context) {
      $(once('tiles-gallery', '.tiles-gallery', context)).tilesGallery();
    }
  };

})(jQuery, Drupal);
