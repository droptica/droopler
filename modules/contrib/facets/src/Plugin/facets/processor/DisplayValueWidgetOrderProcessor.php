<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\Processor\SortProcessorInterface;
use Drupal\facets\Processor\SortProcessorPluginBase;
use Drupal\facets\Result\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A processor that orders the results by display value.
 *
 * @FacetsProcessor(
 *   id = "display_value_widget_order",
 *   label = @Translation("Sort by display value"),
 *   description = @Translation("Sorts the widget results by display value."),
 *   default_enabled = TRUE,
 *   stages = {
 *     "sort" = 40
 *   }
 * )
 */
class DisplayValueWidgetOrderProcessor extends SortProcessorPluginBase implements SortProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Constructs a DisplayValueWidgetOrderProcessor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TransliterationInterface $transliteration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->transliteration = $transliteration;
  }

  /**
   * Creates an instance of the plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('transliteration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function sortResults(Result $a, Result $b) {
    // Get the transliterate values only once.
    if (!isset($a->transliterateDisplayValue)) {
      $a->transliterateDisplayValue = $this->transliteration->removeDiacritics($a->getDisplayValue());
    }
    if (!isset($b->transliterateDisplayValue)) {
      $b->transliterateDisplayValue = $this->transliteration->removeDiacritics($b->getDisplayValue());
    }

    // Return the sort value.
    if ($a->transliterateDisplayValue == $b->transliterateDisplayValue) {
      return 0;
    }
    return strnatcasecmp($a->transliterateDisplayValue, $b->transliterateDisplayValue);
  }

}
