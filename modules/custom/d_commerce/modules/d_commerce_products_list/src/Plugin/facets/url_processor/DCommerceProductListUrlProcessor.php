<?php

namespace Drupal\d_commerce_products_list\Plugin\facets\url_processor;

use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets_pretty_paths\Plugin\facets\url_processor\FacetsPrettyPathsUrlProcessor;
use Drupal\views\Views;

/**
 * Droopler commerce product list pretty paths URL processor.
 *
 * @FacetsUrlProcessor(
 *   id = "d_commerce_product_list_url_processor",
 *   label = @Translation("Droopler product list url processor"),
 *   description = @Translation("Use internally a Pretty paths processor"),
 * )
 */
class DCommerceProductListUrlProcessor extends FacetsPrettyPathsUrlProcessor {

  /**
   * {@inheritdoc}
   */
  public function buildUrls(FacetInterface $facet, array $results) {
    $results = parent::buildUrls($facet, $results);
    $attributes = $this->request->attributes;

    foreach ($results as &$result) {
      if ($result->getUrl()->toString() === $this->request->getRequestUri()) {
        $base_url = new Url($result->getUrl()->getRouteName());
        $result->setUrl($base_url);
        $result->setActiveState(TRUE);
      }
      else {
        // Because we are limiting the results in a view by contextual filters
        // we need to load view with arguments from Result Url object
        // and get the number of total rows.
        $view = Views::getView($attributes->get('view_id'));
        $view->setDisplay($attributes->get('display_id'));
        $view->setArguments($result->getUrl()->getRouteParameters());
        $view->execute();

        if ($view->total_rows) {
          $result->setCount($view->total_rows);
        }
      }
    }

    return $results;
  }

}
