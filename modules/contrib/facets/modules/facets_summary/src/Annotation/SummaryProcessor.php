<?php

namespace Drupal\facets_summary\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a facets summary display processor.
 *
 * @see \Drupal\facets\Processor\ProcessorPluginManager
 * @see plugin_api
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class SummaryProcessor extends Plugin {

  /**
   * The plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The plugin description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The stages this processor will run in, along with their default weights.
   *
   * This is represented as an associative array, mapping one or more of the
   * stage identifiers to the default weight for that stage. For the available
   * stages, see
   * \Drupal\facets_summary\Processor\ProcessorPluginManager::getProcessingStages().
   *
   * @var int[]
   */
  public $stages;

  /**
   * Whether or not this processor is default enabled for new facets.
   *
   * @var bool
   */
  // @codingStandardsIgnoreStart
  public $default_enabled;
  // @codingStandardsIgnoreEnd

}
