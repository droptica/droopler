<?php

namespace Drupal\facets_summary\Processor;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\facets_summary\Annotation\SummaryProcessor;

/**
 * Manages processor plugins.
 *
 * @see \Drupal\facets_summary\Annotation\FacetsSummaryProcessor
 * @see \Drupal\facets_summary\Processor\ProcessorInterface
 * @see \Drupal\facets_summary\Processor\ProcessorPluginBase
 * @see plugin_api
 */
class ProcessorPluginManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, TranslationInterface $translation) {
    parent::__construct('Plugin/facets_summary/processor', $namespaces, $module_handler, ProcessorInterface::class, SummaryProcessor::class);
    $this->setCacheBackend($cache_backend, 'facets_summary_processors');
    $this->setStringTranslation($translation);
  }

  /**
   * Retrieves information about the available processing stages.
   *
   * These are then used by processors in their "stages" definition to specify
   * in which stages they will run.
   *
   * @return array
   *   An associative array mapping stage identifiers to information about that
   *   stage. The information itself is an associative array with the following
   *   keys:
   *   - label: The translated label for this stage.
   */
  public function getProcessingStages() {
    return [
      ProcessorInterface::STAGE_BUILD => [
        'label' => $this->t('Build stage'),
      ],
    ];
  }

}
