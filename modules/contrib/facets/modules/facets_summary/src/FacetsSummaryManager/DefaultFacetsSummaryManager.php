<?php

namespace Drupal\facets_summary\FacetsSummaryManager;

use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\facets\Exception\InvalidProcessorException;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\facets_summary\Processor\BuildProcessorInterface;
use Drupal\facets_summary\Processor\ProcessorInterface;
use Drupal\facets_summary\Processor\ProcessorPluginManager;
use Drupal\facets_summary\FacetsSummaryInterface;

/**
 * The facet summary manager.
 *
 * The manager wires everything together, it's responsible for gather the
 * results and creating the summary.
 * It also runs the processors and returns a renderable array from the build
 * method.
 */
class DefaultFacetsSummaryManager {

  use StringTranslationTrait;

  /**
   * The facet source plugin manager.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginManager
   */
  protected $facetSourcePluginManager;

  /**
   * The processor plugin manager.
   *
   * @var \Drupal\facets_summary\Processor\ProcessorPluginManager
   */
  protected $processorPluginManager;

  /**
   * The Facet Manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetManager;

  /**
   * Constructs a new instance of the DefaultFacetManager.
   *
   * @param \Drupal\facets\FacetSource\FacetSourcePluginManager $facet_source_manager
   *   The facet source plugin manager.
   * @param \Drupal\facets_summary\Processor\ProcessorPluginManager $processor_plugin_manager
   *   The facets summary processor plugin manager.
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facet_manager
   *   The facet manager service.
   */
  public function __construct(FacetSourcePluginManager $facet_source_manager, ProcessorPluginManager $processor_plugin_manager, DefaultFacetManager $facet_manager) {
    $this->facetSourcePluginManager = $facet_source_manager;
    $this->processorPluginManager = $processor_plugin_manager;
    $this->facetManager = $facet_manager;
  }

  /**
   * Builds a facet and returns it as a renderable array.
   *
   * This method delegates to the relevant plugins to render a facet, it calls
   * out to a widget plugin to do the actual rendering when results are found.
   * When no results are found it calls out to the correct empty result plugin
   * to build a render array.
   *
   * Before doing any rendering, the processors that implement the
   * BuildProcessorInterface enabled on this facet will run.
   *
   * @param \Drupal\facets_summary\FacetsSummaryInterface $facets_summary
   *   The facet we should build.
   *
   * @return array
   *   Facet render arrays.
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   *   Throws an exception when an invalid processor is linked to the facet.
   */
  public function build(FacetsSummaryInterface $facets_summary) {
    // Let the facet_manager build the facets.
    $facetsource_id = $facets_summary->getFacetSourceId();

    /** @var \Drupal\facets\Entity\Facet[] $facets */
    $facets = $this->facetManager->getFacetsByFacetSourceId($facetsource_id);
    // Get the current results from the facets and let all processors that
    // trigger on the build step do their build processing.
    // @see \Drupal\facets\Processor\BuildProcessorInterface.
    // @see \Drupal\facets\Processor\SortProcessorInterface.
    $this->facetManager->updateResults($facetsource_id);

    $facets_config = $facets_summary->getFacets();
    // Exclude facets which were not selected for this summary.
    $facets = array_filter($facets,
      function ($item) use ($facets_config) {
        return (isset($facets_config[$item->id()]));
      }
    );

    foreach ($facets as $facet) {
      // Do not build the facet in summary if facet is not rendered.
      if (!$facet->getActiveItems()) {
        continue;
      }
      // For clarity, process facets is called each build.
      // The first facet therefor will trigger the processing. Note that
      // processing is done only once, so repeatedly calling this method will
      // not trigger the processing more than once.
      $this->facetManager->build($facet);
    }

    $build = [
      '#theme' => 'facets_summary_item_list',
      '#attributes' => [
        'data-drupal-facets-summary-id' => $facets_summary->id(),
      ],
    ];

    $results = [];
    foreach ($facets as $facet) {
      $show_count = $facets_config[$facet->id()]['show_count'];
      $results = array_merge($results, $this->buildResultTree($show_count, $facet->getResults()));
    }
    $build['#items'] = $results;

    // Allow our Facets Summary processors to alter the build array in a
    // configured order.
    foreach ($facets_summary->getProcessorsByStage(ProcessorInterface::STAGE_BUILD) as $processor) {
      if (!$processor instanceof BuildProcessorInterface) {
        throw new InvalidProcessorException("The processor {$processor->getPluginDefinition()['id']} has a build definition but doesn't implement the required BuildProcessorInterface interface");
      }
      $build = $processor->build($facets_summary, $build, $facets);
    }

    return $build;
  }

  /**
   * Build result tree, taking possible children into account.
   *
   * @param bool $show_count
   *   Show the count next to the facet.
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   Facet results array.
   *
   * @return array
   *   The rendered links to the active facets.
   */
  protected function buildResultTree($show_count, array $results) {
    $items = [];
    foreach ($results as $result) {
      if ($result->isActive()) {
        $item = [
          '#theme' => 'facets_result_item__summary',
          '#value' => $result->getDisplayValue(),
          '#show_count' => $show_count,
          '#count' => $result->getCount(),
          '#is_active' => TRUE,
          '#facet' => $result->getFacet(),
          '#raw_value' => $result->getRawValue(),
        ];
        $item = (new Link($item, $result->getUrl()))->toRenderable();
        $item['#wrapper_attributes'] = [
          'class' => [
            'facet-summary-item--facet',
          ],
        ];
        $items[] = $item;
      }
      if ($children = $result->getChildren()) {
        $items = array_merge($items, $this->buildResultTree($show_count, $children));
      }
    }
    return $items;
  }

}
