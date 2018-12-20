<?php

namespace Drupal\facets_summary\Plugin\facets_summary\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets_summary\FacetsSummaryInterface;
use Drupal\facets_summary\Processor\BuildProcessorInterface;
use Drupal\facets_summary\Processor\ProcessorPluginBase;

/**
 * Provides a processor that shows a text when there are no results.
 *
 * @SummaryProcessor(
 *   id = "show_text_when_empty",
 *   label = @Translation("Show a text when there are no results"),
 *   description = @Translation("Show a text when there are no results, otherwise it will hide the block."),
 *   default_enabled = TRUE,
 *   stages = {
 *     "build" = 10
 *   }
 * )
 */
class ShowTextWhenEmptyProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetsSummaryInterface $facets_summary, array $build, array $facets) {
    $config = $this->getConfiguration();

    $results_count = array_sum(array_map(function ($it) {
      /** @var \Drupal\facets\FacetInterface $it */
      return count($it->getResults());
    }, $facets));

    // No items are found, so we should return the empty summary.
    if (!isset($build['#items']) || $results_count === 0) {
      return [
        '#theme' => 'facets_summary_empty',
        '#message' => [
          '#type' => 'processed_text',
          '#text' => $config['text']['value'],
          '#format' => $config['text']['format'],
        ],
      ];
    }

    // Return the actual items.
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetsSummaryInterface $facets_summary) {
    // By default, there should be no config form.
    $config = $this->getConfiguration();

    $build['text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Empty text'),
      '#format' => $config['text']['format'],
      '#editor' => TRUE,
      '#default_value' => $config['text']['value'],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'text' => [
        'format' => 'plain_text',
        'value' => $this->t('No results found.'),
      ],
    ];
  }

}
