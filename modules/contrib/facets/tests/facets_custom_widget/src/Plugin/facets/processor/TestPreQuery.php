<?php

namespace Drupal\facets_custom_widget\Plugin\facets\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * The URL processor handler triggers the actual url processor.
 *
 * @FacetsProcessor(
 *   id = "test_pre_query",
 *   label = @Translation("test pre query"),
 *   description = @Translation("TEST pre query"),
 *   stages = {
 *     "pre_query" = 50
 *   }
 * )
 */
class TestPreQuery extends ProcessorPluginBase implements PreQueryProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    return [
      'test_value' => [
        '#type' => 'textfield',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return 'string';
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet) {
    drupal_set_message($this->getConfiguration()['test_value']);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    return \Drupal::state()->get('facets_test_supports_facet', TRUE);
  }

}
