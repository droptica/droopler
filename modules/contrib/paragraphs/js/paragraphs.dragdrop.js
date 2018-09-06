/**
 * @file
 * Paragraphs drag and drop handling and integration with the Sortable library.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * jQuery plugin for Sortable
   *
   * Registers Sortable under a custom name to prevent a collision with jQuery
   * UI.
   *
   * @param   {Object|String} options
   * @param   {..*}           [args]
   * @returns {jQuery|*}
   */
  $.fn.paragraphsSortable = function (options) {
    var retVal,
      args = arguments;

    this.each(function () {
      var $el = $(this),
        sortable = $el.data('sortable');

      if (!sortable && (options instanceof Object || !options)) {
        sortable = new Sortable(this, options);
        $el.data('sortable', sortable);
      }

      if (sortable) {
        if (options === 'widget') {
          return sortable;
        }
        else if (options === 'destroy') {
          sortable.destroy();
          $el.removeData('sortable');
        }
        else if (typeof sortable[options] === 'function') {
          retVal = sortable[options].apply(sortable, [].slice.call(args, 1));
        }
        else if (options in sortable.options) {
          retVal = sortable.option.apply(sortable, args);
        }
      }
    });

    return (retVal === void 0) ? this : retVal;
  };


  Drupal.behaviors.paragraphsDraggable = {
    attach: function (context) {
      // Prevent default click handling on the drag handle.
      $('.paragraphs-dragdrop__handle', context).on('click', function (event) {
        event.preventDefault();
      });

      // Initialize drag and drop.
      $('ul.paragraphs-dragdrop__list', context).each(function (i, item) {
        $(item).paragraphsSortable({
          group: "paragraphs",
          sort: true,
          handle: ".paragraphs-dragdrop__handle",
          onMove: isAllowed,
          onEnd: function(evt) {
            handleReorder(evt);
            endDragClasses();
          },
          onStart: startDragClasses,
        });
      });

      /**
       * Callback to update weight and path information.
       *
       * @param evt
       *   The Sortable event.
       */
      function handleReorder(evt) {
        var $item = $(evt.item);
        var $parent = $item.closest('.paragraphs-dragdrop__list');
        var $children = $parent.children('li');
        var $srcParent = $(evt.to);
        var $srcChildren = $srcParent.children('li');

        // Update both the source and target children.
        updateWeightsAndPath($srcChildren);
        updateWeightsAndPath($children);
        endDragClasses();
      }


      /**
       * Update weight and recursively update path of the provided paragraphs.
       *
       * @param $items
       *   Drag and drop items.
       */
      function updateWeightsAndPath($items) {
        $items.each(function (index, value) {

          // Update the weight in the weight of the current element, avoid
          // matching child weights by selecting the first.
          var $currentItem = $(value);
          var $weight = $currentItem.find('.paragraphs-dragdrop__weight:first');
          $weight.val(index);

          // Update the path of the current element and then update all nested
          // elements.
          updatePaths($currentItem, $currentItem.parent());
          $currentItem.find('> div > ul').each(function () {
            updateNestedPath(this, index, $currentItem);
          });
        })
      }

      /**
       * Update the path field based on the parent.
       *
       * @param $item
       *   A list item.
       * @param $parent
       *   The parent of the list item.
       */
      function updatePaths($item, $parent) {
        // Select the first path field which is the one from the current
        // element.
        var $pathField = $item.find('.paragraphs-dragdrop__path:first');
        var newPath = $parent.attr('data-paragraphs-dragdrop-path');
        $pathField.val(newPath);
      }

      /**
       * Update nested paragraphs for a field/list.
       *
       * @param childList
       *   The paragraph field/list, parent of the children to be updated.
       * @param parentIndex
       *   The index of the parent list item.
       * @param $parentListItem
       *   The parent list item.
       */
      function updateNestedPath(childList, parentIndex, $parentListItem) {

        var sortablePath = childList.getAttribute('data-paragraphs-dragdrop-path');
        var newParent = $parentListItem.parent().attr('data-paragraphs-dragdrop-path');

        // Update the data attribute of the list based on the parent index and
        // list item.
        sortablePath = newParent + "][" + parentIndex + sortablePath.substr(sortablePath.lastIndexOf("]"));
        childList.setAttribute('data-paragraphs-dragdrop-path', sortablePath);

        // Now update the children.
        $(childList).children().each(function (childIndex) {
          var $childListItem = $(this);
          updatePaths($childListItem, $(childList), childIndex);
          $(this).find('> div > ul').each(function () {
            var nestedChildList = this;
            updateNestedPath(nestedChildList, childIndex, $childListItem);
          });
        });
      }


      /**
       * Callback to check if a paragraph item can be dropped into a position.
       *
       * @param evt
       *   The Sortable event.
       * @param originalEvent
       *   The original Sortable event.
       *
       * @returns {boolean|*}
       *   True if the type is allowed and there is enough room.
       */
      function isAllowed(evt, originalEvent) {
        var dragee = evt.dragged;
        var target = evt.to;
        var drageeType = dragee.dataset.paragraphsDragdropBundle;
        var allowedTypes = target.dataset.paragraphsDragdropAllowedTypes;
        var hasSameContainer = evt.to === evt.from;
        var allowed = hasSameContainer || (contains(drageeType, allowedTypes) && hasRoom(target));
        targetAllowedClasses(target, allowed);

        return allowed;
      }

      /**
       * Checks if the target has room.
       *
       * @param target
       *   The target list/paragraph field.
       *
       * @returns {boolean}
       *   True if the field is unlimited or limit is not reached yet.
       */
      function hasRoom(target) {

        var cardinality = target.dataset.paragraphsDragdropCardinality;
        var occupants = target.childNodes.length;
        var isLimited = parseInt(cardinality, 10) !== -1;
        var hasRoom = cardinality > occupants;

        return hasRoom || !isLimited;
      }

      /**
       * Checks if the paragraph type is allowed in the target type list.
       *
       * @param candidate
       *   The paragraph type.
       * @param set
       *   Comma separated list of target types.
       *
       * @returns {boolean}
       *   TRUE if the target type is allowed.
       */
      function contains(candidate, set) {
        set = set.split(',');
        var l = set.length;

        for(var i = 0; i < l; i++) {
          if(set[i] === candidate) {
            return true;
          }
        }
        return false;
      }

      /**
       * Provides a helper class indicating drag status on <html> element when
       * dragging starts.
       */
      function startDragClasses() {
        $('html').addClass('is-dragging-paragraphs');
      }

      /**
       * Provides a helper class indicating a valid drop target via isAllowed().
       *
       * @param target
       *   The target list/paragraph field.
       *
       * @param {boolean} allowed
       *   TRUE if the target type is allowed.
       */
      function targetAllowedClasses(target, allowed) {
        $('.is-droppable-target').removeClass('is-droppable-target');
        if (allowed) {
          $(target).addClass('is-droppable-target');
        }
      }

      /**
       * Removes helper classes when dragging ends.
       */
      function endDragClasses() {
        $('html').removeClass('is-dragging-paragraphs');
        $('.is-droppable-target').removeClass('is-droppable-target');
      }

      // Fix for an iOS 10 bug. Binding empty event handler on the touchmove
      // event.
      window.addEventListener('touchmove', function () {
      })
    }
  }

})(jQuery, Drupal);
