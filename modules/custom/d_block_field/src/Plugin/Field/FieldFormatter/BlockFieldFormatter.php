<?php

namespace Drupal\d_block_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

/**
 * Plugin implementation of the 'd_block_field' formatter.
 *
 * @FieldFormatter(
 *   id = "d_block_field",
 *   label = @Translation("Block field"),
 *   field_types = {
 *     "d_block_field"
 *   }
 * )
 */
class BlockFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      /** @var \Drupal\d_block_field\BlockFieldItemInterface $item */
      $block_instance = $item->getBlock();

      // Inject runtime contexts.
      if ($block_instance instanceof ContextAwarePluginInterface) {
        try {
          $contexts = \Drupal::service('context.repository')->getRuntimeContexts($block_instance->getContextMapping());
          \Drupal::service('context.handler')->applyContextMapping($block_instance, $contexts);
        }
        catch (ContextException $e) {
          continue;
        }
      }

      // Make sure the block exists and is accessible.
      if (!$block_instance || !$block_instance->access(\Drupal::currentUser())) {
        continue;
      }

      // See \Drupal\block\BlockViewBuilder::buildPreRenderableBlock
      // See template_preprocess_block()
      $elements[$delta] = [
        '#theme' => 'block',
        '#attributes' => [],
        '#configuration' => $block_instance->getConfiguration(),
        '#plugin_id' => $block_instance->getPluginId(),
        '#base_plugin_id' => $block_instance->getBaseId(),
        '#derivative_plugin_id' => $block_instance->getDerivativeId(),
        'content' => $block_instance->build(),
      ];

      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      $renderer = \Drupal::service('renderer');
      $renderer->addCacheableDependency($elements[$delta], $block_instance);
    }
    return $elements;
  }

}
