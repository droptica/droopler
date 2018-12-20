<?php

namespace Drupal\facets\Plugin\facets\widget;

use Drupal\facets\FacetInterface;
use Drupal\facets\Result\ResultInterface;
use Drupal\facets\Widget\WidgetPluginBase;

/**
 * A simple widget class that returns a simple array of the facet results.
 *
 * @FacetsWidget(
 *   id = "array",
 *   label = @Translation("Array with raw results"),
 *   description = @Translation("A widget that builds an array with results. This widget is not supposed to display any results, but it is needed for rest integration."),
 * )
 */
class ArrayWidget extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    /** @var \Drupal\facets\Result\Result[] $results */
    $results = $facet->getResults();
    $items = [];

    $configuration = $facet->getWidget();
    $this->showNumbers = empty($configuration['show_numbers']) ? FALSE : (bool) $configuration['show_numbers'];

    foreach ($results as $result) {
      if (is_null($result->getUrl())) {
        $text = $this->generateValues($result);
        $items[$facet->getFieldIdentifier()][] = $text;
      }
      else {
        $items[$facet->getFieldIdentifier()][] = $this->buildListItems($facet, $result);
      }
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildListItems(FacetInterface $facet, ResultInterface $result) {
    if ($children = $result->getChildren()) {
      $items = $this->prepare($result);

      $children_markup = [];
      foreach ($children as $child) {
        $children_markup[] = $this->buildChildren($child);
      }

      $items['children'] = [$children_markup];

    }
    else {
      $items = $this->prepare($result);
    }

    return $items;
  }

  /**
   * Prepares the URL and values for the facet.
   *
   * @param \Drupal\facets\Result\ResultInterface $result
   *   A result item.
   *
   * @return array
   *   The results.
   */
  protected function prepare(ResultInterface $result) {
    $values = $this->generateValues($result);

    if (is_null($result->getUrl())) {
      $facet_values = $values;
    }
    else {
      $facet_values['url'] = $result->getUrl()->setAbsolute()->toString();
      $facet_values['values'] = $values;
    }

    return $facet_values;
  }

  /**
   * Builds an array for children results.
   *
   * @param \Drupal\facets\Result\ResultInterface $child
   *   A result item.
   *
   * @return array
   *   An array with the results.
   */
  protected function buildChildren(ResultInterface $child) {
    $values = $this->generateValues($child);

    if (!is_null($child->getUrl())) {
      $facet_values['url'] = $child->getUrl()->setAbsolute()->toString();
      $facet_values['values'] = $values;
    }
    else {
      $facet_values = $values;
    }

    return $facet_values;
  }

  /**
   * Generates the value and the url.
   *
   * @param \Drupal\facets\Result\ResultInterface $result
   *   The result to extract the values.
   *
   * @return array
   *   The values.
   */
  protected function generateValues(ResultInterface $result) {
    $values['value'] = $result->getDisplayValue();

    if ($this->getConfiguration()['show_numbers'] && $result->getCount() !== FALSE) {
      $values['count'] = $result->getCount();
    }

    if ($result->isActive()) {
      $values['active'] = 'true';
    }

    return $values;
  }

}
