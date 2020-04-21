/**
 * @file
 * Documentation utilities.
 */
(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.drooplerDocumentation = {
    attach: function (context, settings) {
      var $previewOverlay = $('<div id="documentation-preview-overlay"></div>');
      var $previewItems = $('.documentation-preview', context);
      var groups = {};
      var currentGroup = 0;
      var scale = 0.3;

      // Add preview to the DOM and hide it on click.
      $('body').append($previewOverlay);
      $previewOverlay.click(function () {
        $(this).hide();
      });

      // Group preview items if they are direct siblings.
      $previewItems.each(function(i, item) {
        var $preview = $(item);
        var $parent = $preview.parent();
        var $itemToGroup = $parent.hasClass('geysir-field-paragraph-wrapper') ? $parent : $preview
        var groupItemClass = 'documentation-preview-group-item';
        var prevItemGrouped = i === 0 || $itemToGroup.prev().hasClass(groupItemClass);

        // Change current group index if needed.
        if (!prevItemGrouped) {
          currentGroup++;
        }
        // Define array if its not defined.
        if (groups[currentGroup] === undefined) {
          groups[currentGroup] = [];
        }
        // Add item to current group.
        groups[currentGroup].push($itemToGroup);
        $itemToGroup.addClass(groupItemClass);
      });

      // Wrap items in the group wrappers.
      $.each(groups, function(i, items) {
        var $group = $('<div class="documentation-preview-group">');
        var currentHeight = 10;

        // Place current group before its first item.
        items[0].before($group);

        // Append all items to the group wrapper.
        $.each(items, function (k, $item) {
          $group.append($item);
          $item.css({
            'top': currentHeight + 'px',
          });
          currentHeight += items[k].height() * scale + 10;

          // Show item preview on click.
          $item.click(function () {
            $previewOverlay.html($(this).html()).show();
          });
        })
        // Set calculated height for the entire group.
        $group.css('height', currentHeight);
      });
    }
  };

})(jQuery, Drupal);
