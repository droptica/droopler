<?php

namespace Drupal\facets_range_widget\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;

/**
 * Provides a processor that adds all range values between an min and max range.
 *
 * @FacetsProcessor(
 *   id = "range_slider",
 *   label = @Translation("Range slider"),
 *   description = @Translation("Add range results for all the steps between min and max range."),
 *   stages = {
 *     "pre_query" =60,
 *     "post_query" = 60,
 *     "build" = 20
 *   }
 * )
 */
class RangeSliderProcessor extends SliderProcessor implements PreQueryProcessorInterface, BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet) {
    $active_items = $facet->getActiveItems();

    array_walk($active_items, function (&$item) {
      if (preg_match('/\(min:((?:-)?[\d\.]+),max:((?:-)?[\d\.]+)\)/i', $item, $matches)) {
        $item = [$matches[1], $matches[2]];
      }
      else {
        $item = NULL;
      }
    });
    $facet->setActiveItems($active_items);
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    /** @var \Drupal\facets\Plugin\facets\processor\UrlProcessorHandler $url_processor_handler */
    $url_processor_handler = $facet->getProcessors()['url_processor_handler'];
    $url_processor = $url_processor_handler->getProcessor();
    $active_filters = $url_processor->getActiveFilters();

    if (isset($active_filters[''])) {
      unset($active_filters['']);
    }

    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    foreach ($results as &$result) {
      $new_active_filters = $active_filters;
      unset($new_active_filters[$facet->getUrlAlias()]);
      // Add one generic query filter with the min and max placeholder.
      $new_active_filters[$facet->id()][] = '(min:__range_slider_min__,max:__range_slider_max__)';
      $url = \Drupal::service('facets.utility.url_generator')->getUrl($new_active_filters, FALSE);
      $result->setUrl($url);
    }

    return $results;
  }

}
