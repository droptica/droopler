<?php

declare(strict_types=1);

namespace Drupal\d_p_reference_content\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Template\Attribute;
use Drupal\d_p_reference_content\Helpers\ContentHelper;

/**
 * Hook implementations for the d_p_reference_content module.
 */
class Hooks {

  public function __construct(
    protected readonly ContentHelper $contentHelper,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__d_p_reference_content' => [
        'base hook' => 'paragraph',
      ],
      'field__paragraph__field_d_p_reference_content' => [
        'base hook' => 'field',
      ],
      'field__paragraph__field_d_main_title__d_p_reference_content' => [
        'base hook' => 'field',
      ],
      'field__paragraph__field_d_cta_link__d_p_reference_content' => [
        'base hook' => 'field',
      ],
      'field__paragraph__field_d_media_icon__d_p_reference_content' => [
        'base hook' => 'field',
      ],
      'field__paragraph__field_d_long_text__d_p_reference_content' => [
        'base hook' => 'field',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK() for paragraph__d_p_reference_content.
   */
  #[Hook('preprocess_paragraph__d_p_reference_content')]
  public function preprocessParagraphDpReferenceContent(array &$variables): void {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];

    $variables['d_p_reference_content_wrapper_attributes'] = new Attribute();

    $reference_content = $paragraph->get('field_d_p_reference_content');
    $values = $reference_content->getValue();

    // Auto-fill with the latest blog posts, excluding those already picked.
    $auto_values = $this->contentHelper->getSortedContentByType('blog_post', 'created', 'DESC', $values);
    $merged_values = array_merge($values, $auto_values);

    // Strip unpublished items.
    $merged_values = $this->contentHelper->getPublishedContent($merged_values);

    /** @var \Drupal\field\Entity\FieldConfig $definition */
    $definition = $reference_content->getDataDefinition();
    $cardinality = $definition->getFieldStorageDefinition()->getCardinality();
    $limit = min(count($merged_values), $cardinality);

    $new_values = array_slice($merged_values, 0, $limit);

    $this->contentHelper->replaceContent($variables, 'node', 'teaser_small', 'field_d_p_reference_content', $new_values);

    $variables['#cache']['tags'][] = 'node_list';
  }

}
