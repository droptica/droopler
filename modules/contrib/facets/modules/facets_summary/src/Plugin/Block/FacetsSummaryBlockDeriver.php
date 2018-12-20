<?php

namespace Drupal\facets_summary\Plugin\Block;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This deriver creates a block for every facet source.
 */
class FacetsSummaryBlockDeriver implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * The entity storage used for facets summaries.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $facetsSummaryStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $deriver = new static($container, $base_plugin_id);
    $deriver->facetsSummaryStorage = $container->get('entity_type.manager')->getStorage('facets_summary');

    return $deriver;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    $derivatives = $this->getDerivativeDefinitions($base_plugin_definition);
    return isset($derivatives[$derivative_id]) ? $derivatives[$derivative_id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $base_plugin_id = $base_plugin_definition['id'];
    if (!isset($this->derivatives[$base_plugin_id])) {
      $plugin_derivatives = [];
      /** @var \Drupal\facets_summary\FacetsSummaryInterface[] $all_facets_summaries */
      $all_facets_summaries = $this->facetsSummaryStorage->loadMultiple();
      foreach ($all_facets_summaries as $facets_summary) {
        $machine_name = $facets_summary->id();

        $plugin_derivatives[$machine_name] = [
          'id' => $base_plugin_id . PluginBase::DERIVATIVE_SEPARATOR . $machine_name,
          'label' => $this->t('Facet Summary: :facet_summary', [':facet_summary' => $facets_summary->getName()]),
          'admin_label' => $facets_summary->getName(),
          'description' => $this->t('Facets Summary'),
        ] + $base_plugin_definition;
      }
      $this->derivatives[$base_plugin_id] = $plugin_derivatives;
    }
    return $this->derivatives[$base_plugin_id];
  }

}
