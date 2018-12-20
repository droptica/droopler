<?php

namespace Drupal\facets\Processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;

/**
 * A base class for plugins that implements some boilerplate for a widget order.
 */
abstract class SortProcessorPluginBase extends ProcessorPluginBase implements SortProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $processors = $facet->getProcessors();
    $config = isset($processors[$this->getPluginId()]) ? $processors[$this->getPluginId()] : NULL;

    $build['sort'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sort order'),
      '#options' => [
        'ASC' => $this->t('Ascending'),
        'DESC' => $this->t('Descending'),
      ],
      '#default_value' => !is_null($config) ? $config->getConfiguration()['sort'] : $this->defaultConfiguration()['sort'],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['sort' => 'ASC'];
  }

}
