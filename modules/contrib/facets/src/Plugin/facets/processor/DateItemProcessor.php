<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\facets\Plugin\facets\query_type\SearchApiDate;

/**
 * Provides a processor for dates.
 *
 * @FacetsProcessor(
 *   id = "date_item",
 *   label = @Translation("Date item processor"),
 *   description = @Translation("Display dates with granularity options for date fields."),
 *   stages = {
 *     "build" = 35
 *   }
 * )
 */
class DateItemProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    return $results;
  }

  /**
   * Human readable array of granularity options.
   *
   * @return array
   *   An array of granularity options.
   */
  private function granularityOptions() {
    return [
      SearchApiDate::FACETAPI_DATE_YEAR => $this->t('Year'),
      SearchApiDate::FACETAPI_DATE_MONTH => $this->t('Month'),
      SearchApiDate::FACETAPI_DATE_DAY => $this->t('Day'),
      SearchApiDate::FACETAPI_DATE_HOUR => $this->t('Hour'),
      SearchApiDate::FACETAPI_DATE_MINUTE => $this->t('Minute'),
      SearchApiDate::FACETAPI_DATE_SECOND => $this->t('Second'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $this->getConfiguration();

    $build['date_display'] = [
      '#type' => 'radios',
      '#title' => $this->t('Date display'),
      '#default_value' => $this->getConfiguration()['date_display'],
      '#options' => [
        'actual_date' => $this->t('Actual date with granularity'),
        'relative_date' => $this->t('Relative date'),
      ],
    ];

    $build['granularity'] = [
      '#type' => 'radios',
      '#title' => $this->t('Granularity'),
      '#default_value' => $this->getConfiguration()['granularity'],
      '#options' => $this->granularityOptions(),
    ];
    $build['date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date format'),
      '#default_value' => $this->getConfiguration()['date_format'],
      '#description' => $this->t('Override default date format used for the displayed filter format. See the <a href="http://php.net/manual/function.date.php">PHP manual</a> for available options.'),
      '#states' => [
        'visible' => [':input[name="facet_settings[date_item][settings][date_display]"]' => ['value' => 'actual_date']],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return 'date';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'date_display' => 'actual_date',
      'granularity' => SearchApiDate::FACETAPI_DATE_MONTH,
      'date_format' => '',
    ];
  }

}
