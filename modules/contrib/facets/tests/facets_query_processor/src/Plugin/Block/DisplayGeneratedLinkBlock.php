<?php

namespace Drupal\facets_query_processor\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;

/**
 * Class DisplayGeneratedLinkBlock.
 *
 * @Block(
 *   id = "display_generated_link",
 *   admin_label = @Translation("Display Generated Link"),
 * )
 */
class DisplayGeneratedLinkBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\facets\Utility\FacetsUrlGenerator $urlGeneratorService */
    $urlGeneratorService = \Drupal::service('facets.utility.url_generator');
    $url = $urlGeneratorService->getUrl(['owl' => ['item']], \Drupal::state()->get('facets_url_generator_keep_active', FALSE));

    $link = new Link('Link to owl item', $url);
    return $link->toRenderable() + ['#cache' => ['max-age' => 0]];
  }

}
