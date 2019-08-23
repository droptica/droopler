<?php

namespace Drupal\d_social_media\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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
    $mediaName = ['facebook', 'twitter', 'youtube', 'instagram', 'linkedin'];
    $links = [];
    $config = \Drupal::config('d_social_media.settings');
    foreach ($mediaName as $name) {
      if (!empty($config->get("link_$name"))) {
        $links[] = [
          'name' => $name,
          'link' => $config->get("link_$name"),
        ];
      }
    }

    return [
      '#theme' => 'social_media_theme',
      '#show' => !empty($links),
      '#links' => $links,
    ];
  }

}
