<?php

namespace Drupal\facets_range_widget\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Widget\WidgetPluginBase;

/**
 * The slider widget.
 *
 * @FacetsWidget(
 *   id = "slider",
 *   label = @Translation("Slider"),
 *   description = @Translation("A widget that shows a slider."),
 * )
 */
class SliderWidget extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'prefix' => '',
      'suffix' => '',
      'min_type' => 'search_result',
      'min_value' => 0,
      'max_type' => 'search_result',
      'max_value' => 10,
      'step' => 1,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $build = parent::build($facet);

    $results = $facet->getResults();
    ksort($results);

    $show_numbers = $facet->getWidgetInstance()->getConfiguration()['show_numbers'];
    $urls = [];
    $labels = [];

    foreach ($results as $result) {
      $urls['f_' . $result->getRawValue()] = $result->getUrl()->toString();
      $labels[] = $result->getDisplayValue() . ($show_numbers ? ' (' . $result->getCount() . ')' : '');
    }
    // The results set on the facet are sorted where the minimum is the first
    // item and the last one is the one with the highest results, so it's safe
    // to use min/max.
    $min = (float) reset($results)->getRawValue();
    $max = (float) end($results)->getRawValue();

    $build['#items'] = [[
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['facet-slider'],
        'id' => $facet->id(),
      ],
    ]];

    $active = $facet->getActiveItems();

    $build['#attached']['library'][] = 'facets_range_widget/slider';
    $build['#attached']['drupalSettings']['facets']['sliders'][$facet->id()] = [
      'min' => $min,
      'max' => $max,
      'value' => isset($active[0]) ? (float) $active[0] : '',
      'urls' => $urls,
      'prefix' => $this->getConfiguration()['prefix'],
      'suffix' => $this->getConfiguration()['suffix'],
      'step' => $this->getConfiguration()['step'],
      'labels' => $labels,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $config = $this->getConfiguration();
    $form = parent::buildConfigurationForm($form, $form_state, $facet);

    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value prefix'),
      '#size' => 5,
      '#default_value' => $config['prefix'],
    ];

    $form['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value suffix'),
      '#size' => 5,
      '#default_value' => $config['suffix'],
    ];

    $form['min_type'] = [
      '#type' => 'radios',
      '#options' => [
        'fixed' => $this->t('Fixed value'),
        'search_result' => $this->t('Based on search result'),
      ],
      '#title' => $this->t('Minimum value type'),
      '#default_value' => $config['min_type'],
    ];

    $form['min_value'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum value'),
      '#default_value' => $config['min_value'],
      '#size' => 10,
      '#states' => [
        'visible' => [
          'input[name="widget_config[min_type]"]' => ['value' => 'fixed'],
        ],
      ],
    ];

    $form['max_type'] = [
      '#type' => 'radios',
      '#options' => [
        'fixed' => $this->t('Fixed value'),
        'search_result' => $this->t('Based on search result'),
      ],
      '#title' => $this->t('Maximum value type'),
      '#default_value' => $config['max_type'],
    ];

    $form['max_value'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum value'),
      '#default_value' => $config['max_value'],
      '#size' => 5,
      '#states' => [
        'visible' => [
          'input[name="widget_config[max_type]"]' => ['value' => 'fixed'],
        ],
      ],
    ];

    $form['step'] = [
      '#type' => 'number',
      '#step' => 0.001,
      '#title' => $this->t('slider step'),
      '#default_value' => $config['step'],
      '#size' => 2,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function isPropertyRequired($name, $type) {
    if ($name === 'slider' && $type === 'processors') {
      return TRUE;
    }
    if ($name === 'show_only_one_result' && $type === 'settings') {
      return TRUE;
    }

    return FALSE;
  }

}
