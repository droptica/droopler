<?php

namespace Drupal\d_social_media\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\d_social_media\Form\ConfigurationForm;

/**
 * Provides a block contains links to social media.
 *
 * @Block(
 *   id = "social_media_block",
 *   admin_label = @Translation("Social Media Block"),
 * )
 */
class SocialMediaBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $links = [];
    $config = \Drupal::config('d_social_media.settings');
    foreach (ConfigurationForm::getMediaNames() as $name) {
      if (!empty($config->get("link_$name"))) {
        $links[] = [
          'name' => $name,
          'link' => $config->get("link_$name"),
        ];
      }
    }

    // Not render block if links are empty.
    if (empty($links)) {
      return [];
    }

    return [
      '#theme' => 'd_social_media',
      '#attached' => [
        'library' => [
          'd_social_media/last-element-in-a-row',
        ],
      ],
      '#links' => $links,
    ];
  }

}
