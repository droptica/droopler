<?php

namespace Drupal\d_block_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

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
        'content' => $this->processBlockBuild($block_instance),
      ];

      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      $renderer = \Drupal::service('renderer');
      $renderer->addCacheableDependency($elements[$delta], $block_instance);
    }
    return $elements;
  }

  /**
   * Get processed block build.
   *
   * @param BlockPluginInterface $block_instance
   *   The Block Plugin Interface.
   * @return array
   *   The Block Build.
   */
  private function processBlockBuild(BlockPluginInterface $block_instance): array {
    $block_build = $block_instance->build();

    // Get processed block build for entity view node block without recursive node rendering.
    if ($block_instance->getBaseId() === 'entity_view') {
      $node = $block_build['#node'] ?? NULL;

      if ($node instanceof NodeInterface) {
        $sections = $node->hasField('field_page_section') && !$node->get('field_page_section')->isEmpty()
          ? $node->get('field_page_section')->referencedEntities()
          : NULL;

        if (!$sections) {
          return $block_build;
        }

        $block_build['content']['#node'] = $node->set(
          'field_page_section',
          $this->getAllowedSectionsForEntityViewBlock($sections)
        );
      }
    }

    return $block_build;
  }

  /**
   * Get allowed references for page section field.
   *
   * @param array $sections
   *   All referenced entities from field page section.
   * @return array
   *   Allowed referenced entities for field page section.
   */
  private function getAllowedSectionsForEntityViewBlock(array $sections): array {
    foreach ($sections as $key => $paragraph) {
      if ($paragraph instanceof ParagraphInterface && $paragraph->getType() === 'd_p_block') {
        $field_block_value = $paragraph->hasField('field_block') && !$paragraph->get('field_block')->isEmpty()
          ? $paragraph->get('field_block')->getValue()
          : NULL;
        $field_block_value = reset($field_block_value);

        if (isset($field_block_value['plugin_id']) && $field_block_value['plugin_id'] === 'entity_view:node') {
          unset($sections[$key]);
        }
      }
    }

    return $sections;
  }
}
