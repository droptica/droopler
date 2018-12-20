<?php

namespace Drupal\facets\FacetManager;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\facets\Exception\InvalidProcessorException;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\PostQueryProcessorInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginManager;
use Drupal\facets\QueryType\QueryTypePluginManager;

/**
 * The facet manager.
 *
 * The manager is responsible for interactions with the Search backend, such as
 * altering the query, it is also responsible for executing and building the
 * facet. It is also responsible for running the processors.
 */
class DefaultFacetManager {

  use StringTranslationTrait;

  /**
   * The query type plugin manager.
   *
   * @var \Drupal\facets\QueryType\QueryTypePluginManager
   *   The query type plugin manager.
   */
  protected $queryTypePluginManager;

  /**
   * The facet source plugin manager.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginManager
   */
  protected $facetSourcePluginManager;

  /**
   * The processor plugin manager.
   *
   * @var \Drupal\facets\Processor\ProcessorPluginManager
   */
  protected $processorPluginManager;

  /**
   * An array of facets that are being rendered.
   *
   * @var \Drupal\facets\FacetInterface[]
   *
   * @see \Drupal\facets\FacetInterface
   * @see \Drupal\facets\Entity\Facet
   */
  protected $facets = [];

  /**
   * An array of all entity ids in the active resultset which are a child.
   *
   * @var string[]
   */
  protected $childIds = [];

  /**
   * The entity storage for facets.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|object
   */
  protected $facetStorage;

  /**
   * A static cache of already processed facets.
   *
   * @var \Drupal\facets\FacetInterface[]
   */
  protected $processedFacets;

  /**
   * Constructs a new instance of the DefaultFacetManager.
   *
   * @param \Drupal\facets\QueryType\QueryTypePluginManager $query_type_plugin_manager
   *   The query type plugin manager.
   * @param \Drupal\facets\FacetSource\FacetSourcePluginManager $facet_source_manager
   *   The facet source plugin manager.
   * @param \Drupal\facets\Processor\ProcessorPluginManager $processor_plugin_manager
   *   The processor plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type plugin manager.
   */
  public function __construct(QueryTypePluginManager $query_type_plugin_manager, FacetSourcePluginManager $facet_source_manager, ProcessorPluginManager $processor_plugin_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->queryTypePluginManager = $query_type_plugin_manager;
    $this->facetSourcePluginManager = $facet_source_manager;
    $this->processorPluginManager = $processor_plugin_manager;
    $this->facetStorage = $entity_type_manager->getStorage('facets_facet');
  }

  /**
   * Allows the backend to add facet queries to its native query object.
   *
   * This method is called by the implementing module to initialize the facet
   * display process.
   *
   * @param mixed $query
   *   The backend's native query object.
   * @param string $facetsource_id
   *   The facet source ID to process.
   */
  public function alterQuery(&$query, $facetsource_id) {
    /** @var \Drupal\facets\FacetInterface[] $facets */
    foreach ($this->getFacetsByFacetSourceId($facetsource_id) as $facet) {
      /** @var \Drupal\facets\QueryType\QueryTypeInterface $query_type_plugin */
      $query_type_plugin = $this->queryTypePluginManager->createInstance($facet->getQueryType(), ['query' => $query, 'facet' => $facet]);
      $query_type_plugin->execute();
    }
  }

  /**
   * Returns enabled facets for the searcher associated with this FacetManager.
   *
   * @return \Drupal\facets\FacetInterface[]
   *   An array of enabled facets.
   */
  public function getEnabledFacets() {
    return $this->facetStorage->loadMultiple();
  }

  /**
   * Returns currently rendered facets filtered by facetsource ID.
   *
   * @param string $facetsource_id
   *   The facetsource ID to filter by.
   *
   * @return \Drupal\facets\FacetInterface[]
   *   An array of enabled facets.
   */
  public function getFacetsByFacetSourceId($facetsource_id) {
    // Immediately initialize the facets.
    $this->initFacets();
    $facets = [];
    foreach ($this->facets as $facet) {
      if ($facet->getFacetSourceId() === $facetsource_id) {
        $facets[] = $facet;
      }
    }
    return $facets;
  }

  /**
   * Initializes facet builds, sets the breadcrumb trail.
   *
   * Facets are built via FacetsFacetProcessor objects. Facets only need to be
   * processed, or built, once The FacetsFacetManager::processed semaphore is
   * set when this method is called ensuring that facets are built only once
   * regardless of how many times this method is called.
   *
   * @param string|null $facetsource_id
   *   The facetsource if of the currently processed facet.
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   *   Thrown when one of the defined processors is invalid.
   */
  public function processFacets($facetsource_id = NULL) {
    if ($facetsource_id === NULL) {
      foreach ($this->facets as $facet) {
        $current_facetsource_id = $facet->getFacetSourceId();
        $this->processFacets($current_facetsource_id);
      }
    }

    $unprocessedFacets = array_filter($this->facets, function ($item) use ($facetsource_id) {
      /* @var \Drupal\facets\FacetInterface $item */
      return !isset($this->processedFacets[$facetsource_id][$item->id()]);
    });

    // All facets were already processed on a previous run, so no need to do so
    // again.
    if (count($unprocessedFacets) === 0) {
      return;
    }

    $this->updateResults($facetsource_id);

    foreach ($unprocessedFacets as $facet) {
      foreach ($facet->getProcessorsByStage(ProcessorInterface::STAGE_POST_QUERY) as $processor) {
        /** @var \Drupal\facets\processor\PostQueryProcessorInterface $post_query_processor */
        $post_query_processor = $this->processorPluginManager->createInstance($processor->getPluginDefinition()['id'], ['facet' => $facet]);
        if (!$post_query_processor instanceof PostQueryProcessorInterface) {
          throw new InvalidProcessorException("The processor {$processor->getPluginDefinition()['id']} has a post_query definition but doesn't implement the required PostQueryProcessor interface");
        }
        $post_query_processor->postQuery($facet);
      }
      $this->processedFacets[$facetsource_id][$facet->id()] = $facet;
    }
  }

  /**
   * Initializes enabled facets.
   *
   * In this method all pre-query processors get called and their contents are
   * executed.
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   *   Thrown if one of the pre query processors is invalid.
   */
  protected function initFacets() {
    if (count($this->facets) > 0) {
      return;
    }

    $this->facets = $this->getEnabledFacets();
    foreach ($this->facets as $facet) {
      foreach ($facet->getProcessorsByStage(ProcessorInterface::STAGE_PRE_QUERY) as $processor) {
        /** @var \Drupal\facets\Processor\PreQueryProcessorInterface $pre_query_processor */
        $pre_query_processor = $facet->getProcessors()[$processor->getPluginDefinition()['id']];
        if (!$pre_query_processor instanceof PreQueryProcessorInterface) {
          throw new InvalidProcessorException("The processor {$processor->getPluginDefinition()['id']} has a pre_query definition but doesn't implement the required PreQueryProcessorInterface interface");
        }
        $pre_query_processor->preQuery($facet);
      }
    }
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
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet we should build.
   *
   * @return array
   *   Facet render arrays.
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   *   Throws an exception when an invalid processor is linked to the facet.
   */
  public function build(FacetInterface $facet) {
    // Immediately initialize the facets if they are not initiated yet.
    $this->initFacets();

    // It might be that the facet received here, is not the same as the already
    // loaded facets in the FacetManager.
    // For that reason, get the facet from the already loaded facets in the
    // static cache.
    $facet = $this->facets[$facet->id()];

    if ($facet->getOnlyVisibleWhenFacetSourceIsVisible()) {
      // Block rendering and processing should be stopped when the facet source
      // is not available on the page. Returning an empty array here is enough
      // to halt all further processing.
      $facet_source = $facet->getFacetSource();
      if (is_null($facet_source) || !$facet_source->isRenderedInCurrentRequest()) {
        return [];
      }
    }

    // For clarity, process facets is called each build.
    // The first facet therefor will trigger the processing. Note that
    // processing is done only once, so repeatedly calling this method will not
    // trigger the processing more than once.
    $this->processFacets($facet->getFacetSourceId());

    // Get the current results from the facets and let all processors that
    // trigger on the build step do their build processing.
    // @see \Drupal\facets\Processor\BuildProcessorInterface.
    // @see \Drupal\facets\Processor\SortProcessorInterface.
    $results = $facet->getResults();

    foreach ($facet->getProcessorsByStage(ProcessorInterface::STAGE_BUILD) as $processor) {
      if (!$processor instanceof BuildProcessorInterface) {
        throw new InvalidProcessorException("The processor {$processor->getPluginDefinition()['id']} has a build definition but doesn't implement the required BuildProcessorInterface interface");
      }
      $results = $processor->build($facet, $results);
    }

    // Handle hierarchy.
    if ($results && $facet->getUseHierarchy()) {
      $keyed_results = [];
      foreach ($results as $result) {
        $keyed_results[$result->getRawValue()] = $result;
      }

      $parent_groups = $facet->getHierarchyInstance()->getChildIds(array_keys($keyed_results));
      $keyed_results = $this->buildHierarchicalTree($keyed_results, $parent_groups);

      // Remove children from primary level.
      foreach (array_unique($this->childIds) as $child_id) {
        unset($keyed_results[$child_id]);
      }

      $results = array_values($keyed_results);
    }

    // Trigger sort stage.
    $active_sort_processors = [];
    foreach ($facet->getProcessorsByStage(ProcessorInterface::STAGE_SORT) as $processor) {
      $active_sort_processors[] = $processor;
    }

    // Sort the actual results if we have enabled sort processors.
    if (!empty($active_sort_processors)) {
      $results = $this->sortFacetResults($active_sort_processors, $results);
    }

    $facet->setResults($results);

    // No results behavior handling. Return a custom text or false depending on
    // settings.
    if (empty($facet->getResults())) {
      $empty_behavior = $facet->getEmptyBehavior();
      if ($empty_behavior['behavior'] == 'text') {
        return [
          [
            '#type' => 'container',
            '#attributes' => [
              'data-drupal-facet-id' => $facet->id(),
              'class' => ['facet-empty'],
            ],
            'empty_text' => [
              // @codingStandardsIgnoreStart
              '#markup' => $this->t($empty_behavior['text']),
              // @codingStandardsIgnoreEnd
            ],
          ],
        ];
      }
      else {
        // If the facet has no results, but it is being rendered trough ajax we
        // should render a container (that is empty). This is because the
        // javascript needs to be able to find a div to replace with the new
        // content.
        return [
          [
            '#type' => 'container',
            '#attributes' => [
              'data-drupal-facet-id' => $facet->id(),
              'class' => ['facet-empty'],
            ],
          ],
        ];
      }
    }

    /** @var \Drupal\facets\Widget\WidgetPluginInterface $widget */
    $widget = $facet->getWidgetInstance();

    return [$widget->build($facet)];
  }

  /**
   * Updates all facets of a given facet source with the results.
   *
   * @param string $facetsource_id
   *   The facet source ID of the currently processed facet.
   */
  public function updateResults($facetsource_id) {
    $facets = $this->getFacetsByFacetSourceId($facetsource_id);
    if ($facets) {
      /** @var \drupal\facets\FacetSource\FacetSourcePluginInterface $facet_source_plugin */
      $facet_source_plugin = $this->facetSourcePluginManager->createInstance($facetsource_id);
      $facet_source_plugin->fillFacetsWithResults($facets);
    }
  }

  /**
   * Returns one of the processed facets.
   *
   * Returns one of the processed facets, this is a facet with filled results.
   * Keep in mind that if you want to have the facet's build processor executed,
   * there needs to be an extra call to the FacetManager::build with the facet
   * returned here as argument.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet to process.
   *
   * @return \Drupal\facets\FacetInterface|null
   *   The updated facet if it exists, NULL otherwise.
   */
  public function returnProcessedFacet(FacetInterface $facet) {
    $this->processFacets($facet->getFacetSourceId());
    return !empty($this->facets[$facet->id()]) ? $this->facets[$facet->id()] : NULL;
  }

  /**
   * Builds an hierarchical structure for results.
   *
   * When given an array of results and an array which defines the hierarchical
   * structure, this will build the results structure and set all childs.
   *
   * @param \Drupal\facets\Result\ResultInterface[] $keyed_results
   *   An array of results keyed by id.
   * @param array $parent_groups
   *   An array of 'child id arrays' keyed by their parent id.
   *
   * @return \Drupal\facets\Result\ResultInterface[]
   *   An array of results structured hierarchically.
   */
  protected function buildHierarchicalTree(array $keyed_results, array $parent_groups) {
    foreach ($keyed_results as &$result) {
      $current_id = $result->getRawValue();
      if (isset($parent_groups[$current_id]) && $parent_groups[$current_id]) {
        $child_ids = $parent_groups[$current_id];
        $child_keyed_results = [];
        foreach ($child_ids as $child_id) {
          if (isset($keyed_results[$child_id])) {
            $child_keyed_results[$child_id] = $keyed_results[$child_id];
          }
          else {
            // Children could already be built by Facets Summary manager, if
            // they are, just loading them will suffice.
            $children = $keyed_results[$current_id]->getChildren();
            if (!empty($children[$child_id])) {
              $child_keyed_results[$child_id] = $children[$child_id];
            }
          }
        }
        $result->setChildren($child_keyed_results);
        $this->childIds = array_merge($this->childIds, $child_ids);
      }
    }

    return $keyed_results;
  }

  /**
   * Sort the facet results, and recurse to children to do the same.
   *
   * @param \Drupal\facets\Processor\SortProcessorInterface[] $active_sort_processors
   *   An array of sort processors.
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   An array of results.
   *
   * @return \Drupal\facets\Result\ResultInterface[]
   *   A sorted array of results.
   */
  protected function sortFacetResults(array $active_sort_processors, array $results) {
    uasort($results, function ($a, $b) use ($active_sort_processors) {
      $return = 0;
      foreach ($active_sort_processors as $sort_processor) {
        if ($return = $sort_processor->sortResults($a, $b)) {
          if ($sort_processor->getConfiguration()['sort'] == 'DESC') {
            $return *= -1;
          }
          break;
        }
      }
      return $return;
    });

    // Loop over the results and see if they have any children, if they do, fire
    // a request to this same method again with the children.
    foreach ($results as &$result) {
      if (!empty($result->getChildren())) {
        $children = $this->sortFacetResults($active_sort_processors, $result->getChildren());
        $result->setChildren($children);
      }
    }

    return $results;
  }

}
