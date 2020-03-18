<?php

namespace Drupal\d_block_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'd_block_field_label' formatter.
 *
 * @FieldFormatter(
 *   id = "d_block_field_label",
 *   label = @Translation("Block field label"),
 *   field_types = {
 *     "d_block_field"
 *   }
 * )
 */
class BlockFieldLabelFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      /** @var \Drupal\d_block_field\BlockFieldItemInterface $item */
      $block_instance = $item->getBlock();
      // Make sure the block exists and is accessible.
      if (!$block_instance || !$block_instance->access(\Drupal::currentUser())) {
        continue;
      }

      $elements[$delta] = [
        '#markup' => $block_instance->label(),
      ];

      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      $renderer = \Drupal::service('renderer');
      $renderer->addCacheableDependency($elements[$delta], $block_instance);
    }
    return $elements;
  }

}
