<?php

namespace Drupal\facets_summary\Plugin\facets_summary\processor;

use Drupal\facets_summary\FacetsSummaryInterface;
use Drupal\facets_summary\Processor\BuildProcessorInterface;
use Drupal\facets_summary\Processor\ProcessorPluginBase;

/**
 * Provides a processor that hides the summary when the source was not rendered.
 *
 * @SummaryProcessor(
 *   id = "hide_when_not_rendered",
 *   label = @Translation("Hide Summary when Facet Source is not rendered"),
 *   description = @Translation("When checked, this facet will only be rendered when the facet source is rendered. If you want to show facets on other pages too, you need to uncheck this setting."),
 *   default_enabled = TRUE,
 *   stages = {
 *     "build" = 45
 *   }
 * )
 */
class HideWhenNotRenderedProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetsSummaryInterface $facets_summary, array $build, array $facets) {
    $facet_source = $facets_summary->getFacetSource();
    if (!$facet_source->isRenderedInCurrentRequest()) {
      return [];
    }
    return $build;
  }

}
