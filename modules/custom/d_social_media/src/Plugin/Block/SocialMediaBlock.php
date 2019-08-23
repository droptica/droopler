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
    $body = "<div class='social-media-wrapper'>
        <div class='follow-us'>Follow us</div>
        <div class='icons-wrapper'>
          <a href='#' class='icon icon-facebook-rect'></a><span> | </span>
          <a href='#' class='icon icon-twitter-bird'></a><span> | </span>
          <a href='#' class='icon icon-youtube'></a><span> | </span>
          <a href='#' class='icon icon-instagram'></a><span> | </span>
          <a href='#' class='icon icon-linkedin-rect'></a>
        </div>
        <div class='email'>
            info@droopler.com
        </div>
</div>";
    return [
      '#markup' => $body,
    ];
  }

}
