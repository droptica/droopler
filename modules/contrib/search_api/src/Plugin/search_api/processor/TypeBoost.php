<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Adds a boost to indexed items based on their datasource and/or bundle.
 *
 * @SearchApiProcessor(
 *   id = "type_boost",
 *   label = @Translation("Type-specific boosting"),
 *   description = @Translation("Adds a boost to indexed items based on their datasource and/or bundle."),
 *   stages = {
 *     "preprocess_index" = 0,
 *   }
 * )
 */
class TypeBoost extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * The available boost factors.
   *
   * @var string[]
   */
  protected static $boost_factors = [
    '0.0' => '0.0',
    '0.1' => '0.1',
    '0.2' => '0.2',
    '0.3' => '0.3',
    '0.5' => '0.5',
    '0.8' => '0.8',
    '1.0' => '1.0',
    '2.0' => '2.0',
    '3.0' => '3.0',
    '5.0' => '5.0',
    '8.0' => '8.0',
    '13.0' => '13.0',
    '21.0' => '21.0',
  ];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'boosts' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $bundle_boost_options = [
      '' => $this->t('Use datasource default'),
    ] + static::$boost_factors;

    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $datasource_configuration = [];
      if (isset($this->configuration['boosts'][$datasource_id])) {
        $datasource_configuration = $this->configuration['boosts'][$datasource_id];
      }
      $datasource_configuration += [
        'datasource_boost' => 1.0,
        'bundle_boosts' => [],
      ];
      $datasource_boost = $datasource_configuration['datasource_boost'];
      $bundle_boosts = $datasource_configuration['bundle_boosts'];

      $form['boosts'][$datasource_id] = [
        '#type' => 'details',
        '#title' => $this->t('Boost settings for %datasource', ['%datasource' => $datasource->label()]),
        '#open' => TRUE,
        'datasource_boost' => [
          '#type' => 'select',
          '#title' => $this->t('Default boost for items from this datasource'),
          '#options' => static::$boost_factors,
          '#default_value' => sprintf('%.1f', $datasource_boost),
        ],
      ];

      // Add a boost for every available bundle. Drop the "pseudo-bundle" that
      // is added when the datasource does not contain any bundles.
      $bundles = $datasource->getBundles();
      if (count($bundles) === 1) {
        // Depending on the datasource, the pseudo-bundle might use the
        // datasource ID or the entity type ID.
        unset($bundles[$datasource_id], $bundles[$datasource->getEntityTypeId()]);
      }

      foreach ($bundles as $bundle => $bundle_label) {
        $has_value = isset($bundle_boosts[$bundle]);
        $bundle_boost = $has_value ? $bundle_boosts[$bundle] : '';
        $form['boosts'][$datasource_id]['bundle_boosts'][$bundle] = [
          '#type' => 'select',
          '#title' => $this->t('Boost for the %bundle bundle', ['%bundle' => $bundle_label]),
          '#options' => $bundle_boost_options,
          '#default_value' => sprintf('%.1f', $bundle_boost),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($this->index->getDatasourceIds() as $datasource_id) {
      if (!empty($values['boosts'][$datasource_id]['bundle_boosts'])) {
        foreach ($values['boosts'][$datasource_id]['bundle_boosts'] as $bundle => $boost) {
          if ($boost === '') {
            unset($values['boosts'][$datasource_id]['bundle_boosts'][$bundle]);
          }
        }
        if (!$values['boosts'][$datasource_id]['bundle_boosts']) {
          unset($values['boosts'][$datasource_id]['bundle_boosts']);
        }
      }
    }
    $form_state->setValues($values);
    $this->setConfiguration($values);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    $boosts = $this->configuration['boosts'];

    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      $datasource_id = $item->getDatasourceId();
      $bundle = $item->getDatasource()->getItemBundle($item->getOriginalObject());

      $item_boost = (double) isset($boosts[$datasource_id]['datasource_boost']) ? $boosts[$datasource_id]['datasource_boost'] : 1.0;
      if ($bundle && isset($boosts[$datasource_id]['bundle_boosts'][$bundle])) {
        $item_boost = (double) $boosts[$datasource_id]['bundle_boosts'][$bundle];
      }

      $item->setBoost($item_boost);
    }
  }

}
