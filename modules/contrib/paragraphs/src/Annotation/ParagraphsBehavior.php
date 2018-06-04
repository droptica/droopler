<?php

namespace Drupal\paragraphs\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ParagraphsBehavior annotation object.
 *
 * Paragraphs behavior builders handle extra settings for the paragraph
 * entity.
 *
 * @Annotation
 *
 */
class ParagraphsBehavior extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the paragraphs behavior plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The plugin description.
   *
   * @ingroup plugin_translatable
   *
   * @var string
   */
  public $description;

  /**
   * The plugin weight.
   *
   * @var int
   */
  public $weight;

}
