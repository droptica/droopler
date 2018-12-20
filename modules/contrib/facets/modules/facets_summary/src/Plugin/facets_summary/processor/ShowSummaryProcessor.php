<?php

namespace Drupal\facets_summary\Plugin\facets_summary\processor;

use Drupal\facets_summary\FacetsSummaryInterface;
use Drupal\facets_summary\Processor\BuildProcessorInterface;
use Drupal\facets_summary\Processor\ProcessorPluginBase;

/**
 * Provides a processor that shows a summary of all selected facets.
 *
 * @SummaryProcessor(
 *   id = "show_summary",
 *   label = @Translation("Show a summary of all selected facets"),
 *   description = @Translation("When checked, this facet will show an imploded list of all selected facets."),
 *   stages = {
 *     "build" = 20
 *   }
 * )
 */
class ShowSummaryProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetsSummaryInterface $facets_summary, array $build, array $facets) {
    $facets_config = $facets_summary->getFacets();

    if (!isset($build['#items'])) {
      return $build;
    }

    /** @var \Drupal\facets\Entity\Facet $facet */
    foreach ($facets as $facet) {
      if (empty($facet->getActiveItems())) {
        continue;
      }
      $items = $this->getActiveDisplayValues($facet->getResults());
      $facet_summary = [
        '#theme' => 'facets_summary_facet',
        '#label' => $facets_config[$facet->id()]['label'],
        '#separator' => $facets_config[$facet->id()]['separator'],
        '#items' => $items,
        '#facet_id' => $facet->id(),
        '#facet_admin_label' => $facet->getName(),
      ];
      array_unshift($build['#items'], $facet_summary);
    }
    return $build;
  }

  /**
   * Get all active results' display values from hierarchy.
   *
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   The results to check for active children.
   *
   * @return \Drupal\facets\Result\ResultInterface[]
   *   The active results found.
   */
  protected function getActiveDisplayValues(array $results) {
    $items = [];
    foreach ($results as $result) {
      if ($result->isActive()) {
        $items[] = $result->getDisplayValue();
      }
      if ($result->hasActiveChildren()) {
        $items = array_merge($items, $this->getActiveDisplayValues($result->getChildren()));
      }
    }
    return $items;
  }

}
