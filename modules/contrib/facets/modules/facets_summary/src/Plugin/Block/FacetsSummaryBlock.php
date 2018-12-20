<?php

namespace Drupal\facets_summary\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\facets_summary\Entity\FacetsSummary;
use Drupal\facets_summary\FacetsSummaryBlockInterface;
use Drupal\facets_summary\FacetsSummaryManager\DefaultFacetsSummaryManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposes a summary based on all the facets as a block.
 *
 * @Block(
 *   id = "facets_summary_block",
 *   deriver = "Drupal\facets_summary\Plugin\Block\FacetsSummaryBlockDeriver"
 * )
 */
class FacetsSummaryBlock extends BlockBase implements FacetsSummaryBlockInterface, ContainerFactoryPluginInterface {

  use UncacheableDependencyTrait;

  /**
   * The facet manager service.
   *
   * @var \Drupal\facets_summary\FacetsSummaryManager\DefaultFacetsSummaryManager
   */
  protected $facetsSummaryManager;

  /**
   * The associated facets_source_summary entity.
   *
   * @var \Drupal\facets_summary\FacetsSummaryInterface
   */
  protected $facetsSummary;

  /**
   * Constructs a source summary block.
   *
   * @param array $configuration
   *   The configuration of the Facets Summary Block.
   * @param string $plugin_id
   *   The block plugin block identifier.
   * @param array $plugin_definition
   *   The block plugin block definition.
   * @param \Drupal\facets_summary\FacetsSummaryManager\DefaultFacetsSummaryManager $facets_summary_manager
   *   The facet manager service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, DefaultFacetsSummaryManager $facets_summary_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->facetsSummaryManager = $facets_summary_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('facets_summary.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    if (!isset($this->facetsSummary)) {
      $source_id = $this->getDerivativeId();
      if (!$this->facetsSummary = FacetsSummary::load($source_id)) {
        $this->facetsSummary = FacetsSummary::create(['id' => $source_id]);
        $this->facetsSummary->save();
      }
    }
    return $this->facetsSummary;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\facets_summary\FacetsSummaryInterface $summary */
    $facets_summary = $this->getEntity();

    // Let the facet_manager build the facets.
    $build = $this->facetsSummaryManager->build($facets_summary);

    // Add contextual links only when we have results.
    if (!empty($build)) {
      $build['#contextual_links']['facets_summary'] = [
        'route_parameters' => ['facets_summary' => $facets_summary->id()],
      ];
    }

    /** @var \Drupal\views\ViewExecutable $view */
    if ($view = $facets_summary->getFacetSource()->getViewsDisplay()) {
      $build['#attached']['drupalSettings']['facets_views_ajax'] = [
        'facets_summary_ajax' => [
          'facets_summary_id' => $facets_summary->id(),
          'view_id' => $view->id(),
          'current_display_id' => $view->current_display,
          'ajax_path' => Url::fromRoute('views.ajax')->toString(),
        ],
      ];
    }

    return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $source_id = $this->getDerivativeId();
    if ($summary = FacetsSummary::load($source_id)) {
      return [$summary->getConfigDependencyKey() => [$summary->getConfigDependencyName()]];
    }
    return [];
  }

}
