<?php

namespace Drupal\facets\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Widget\WidgetPluginBase;

/**
 * The links widget.
 *
 * @FacetsWidget(
 *   id = "links",
 *   label = @Translation("List of links"),
 *   description = @Translation("A simple widget that shows a list of links"),
 * )
 */
class LinksWidget extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'soft_limit' => 0,
      'soft_limit_settings' => [
        'show_less_label' => 'Show less',
        'show_more_label' => 'Show more',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $build = parent::build($facet);
    $soft_limit = (int) $this->getConfiguration()['soft_limit'];
    if ($soft_limit !== 0) {
      $show_less_label = $this->getConfiguration()['soft_limit_settings']['show_less_label'];
      $show_more_label = $this->getConfiguration()['soft_limit_settings']['show_more_label'];
      $build['#attached']['library'][] = 'facets/soft-limit';
      $build['#attached']['drupalSettings']['facets']['softLimit'][$facet->id()] = $soft_limit;
      $build['#attached']['drupalSettings']['facets']['softLimitSettings'][$facet->id()]['showLessLabel'] = $show_less_label;
      $build['#attached']['drupalSettings']['facets']['softLimitSettings'][$facet->id()]['showMoreLabel'] = $show_more_label;
    }
    if ($facet->getUseHierarchy()) {
      $build['#attached']['library'][] = 'facets/drupal.facets.hierarchical';
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form = parent::buildConfigurationForm($form, $form_state, $facet);

    $options = [50, 40, 30, 20, 15, 10, 5, 3];
    $form['soft_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Soft limit'),
      '#default_value' => $this->getConfiguration()['soft_limit'],
      '#options' => [0 => $this->t('No limit')] + array_combine($options, $options),
      '#description' => $this->t('Limit the number of displayed facets via JavaScript.'),
    ];
    $form['soft_limit_settings'] = [
      '#type' => 'container',
      '#title' => $this->t('Soft limit settings'),
      '#states' => [
        'invisible' => [':input[name="widget_config[soft_limit]"]' => ['value' => 0]],
      ],
    ];
    $form['soft_limit_settings']['show_less_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Show less label'),
      '#description' => $this->t('This text will be used for "Show less" link.'),
      '#default_value' => $this->getConfiguration()['soft_limit_settings']['show_less_label'],
      '#states' => [
        'optional' => [':input[name="widget_config[soft_limit]"]' => ['value' => 0]],
      ],
    ];
    $form['soft_limit_settings']['show_more_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Show more label'),
      '#description' => $this->t('This text will be used for "Show more" link.'),
      '#default_value' => $this->getConfiguration()['soft_limit_settings']['show_more_label'],
      '#states' => [
        'optional' => [':input[name="widget_config[soft_limit]"]' => ['value' => 0]],
      ],
    ];
    return $form;
  }

}
