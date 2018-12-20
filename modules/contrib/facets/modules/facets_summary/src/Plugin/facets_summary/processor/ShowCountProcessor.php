<?php

namespace Drupal\facets_summary\Plugin\facets_summary\processor;

use Drupal\facets_summary\FacetsSummaryInterface;
use Drupal\facets_summary\Processor\BuildProcessorInterface;
use Drupal\facets_summary\Processor\ProcessorPluginBase;

/**
 * Provides a processor that shows a summary of how many results were found.
 *
 * @SummaryProcessor(
 *   id = "show_count",
 *   label = @Translation("Show a summary of how many results were found"),
 *   description = @Translation("When checked, this will show the amount of results found."),
 *   stages = {
 *     "build" = 5
 *   }
 * )
 */
class ShowCountProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetsSummaryInterface $facets_summary, array $build, array $facets) {
    if (!isset($build['#items'])) {
      return $build;
    }

    $count = $facets_summary->getFacetSource()->getCount();
    $build_count = [
      '#theme' => 'facets_summary_count',
      '#count' => $count,
    ];
    array_unshift($build['#items'], $build_count);
    return $build;
  }

}
