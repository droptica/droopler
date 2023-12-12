<?php

declare(strict_types = 1);

namespace Drupal\d_frontend_editing\Controller;

use Drupal\frontend_editing\Controller\FrontendEditingController as FrontendEditingControllerBase;

/**
 * Controller class for handling frontend editing of paragraphs.
 */
class FrontendEditingController extends FrontendEditingControllerBase {

  /**
   * {@inheritdoc}
   *
   * Improve markup to display paragraph type icons.
   */
  public function paragraphAddPage($parent_type, $parent, $parent_field_name, $current_paragraph, $before): array {
    $build = parent::paragraphAddPage($parent_type, $parent, $parent_field_name, $current_paragraph, $before);
    $paragraph_type_storage = $this->entityTypeManager()->getStorage('paragraphs_type');

    foreach ($build['#items'] as $key => $item) {
      $paragraph_type_id = str_replace([$parent_field_name . '_', '_add_more'], '', $item['#attributes']['name']);

      if ($paragraph_type = $paragraph_type_storage->load($paragraph_type_id)) {
        /** @var \Drupal\paragraphs\ParagraphsTypeInterface $paragraph_type */
        $icon_url = $paragraph_type->getIconUrl();
      }

      $build['#items'][$key] = [
        'icon' => isset($icon_url) ? [
          '#theme' => 'image',
          '#uri' => $icon_url,
        ] : [],
        'link' => $item,
      ];
    }

    $build['#attached']['library'][] = 'd_frontend_editing/paragraph-add-page';

    return $build;
  }

}
