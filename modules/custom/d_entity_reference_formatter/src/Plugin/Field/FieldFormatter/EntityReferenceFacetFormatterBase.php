<?php

declare(strict_types = 1);

namespace Drupal\d_entity_reference_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\Result\Result;
use Drupal\facets\UrlProcessor\UrlProcessorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for Entity Reference Facet Formatters.
 */
abstract class EntityReferenceFacetFormatterBase extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * A facet entity.
   *
   * @var \Drupal\facets\FacetInterface
   */
  protected FacetInterface $facet;

  /**
   * The facet entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected ConfigEntityStorageInterface $facetStorage;

  /**
   * A URL processor plugin manager.
   *
   * @var \Drupal\facets\UrlProcessor\UrlProcessorPluginManager
   */
  protected UrlProcessorPluginManager $urlProcessorPluginManager;

  /**
   * Constructs a EntityReferenceFacetLink object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $facet_storage
   *   An entity type manager.
   * @param \Drupal\facets\UrlProcessor\UrlProcessorPluginManager $url_processor_plugin_manager
   *   A URL processor plugin manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigEntityStorageInterface $facet_storage, UrlProcessorPluginManager $url_processor_plugin_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->facetStorage = $facet_storage;
    $this->urlProcessorPluginManager = $url_processor_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')->getStorage('facets_facet'),
      $container->get('plugin.manager.facets.url_processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();

    if ($facet = $this->getFacet()) {
      $dependencies[$facet->getConfigDependencyKey()][] = $facet->getConfigDependencyName();
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'facet' => '',
      'raw_value_label' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\facets\FacetInterface[] $facets */
    $facets = $this->facetStorage->loadMultiple();
    $options = [];
    foreach ($facets as $facet) {
      $options[$facet->id()] = $facet->label();
    }

    $elements['facet'] = [
      '#title' => $this->t('Select the facet to which the labels should be linked.'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('facet'),
      '#options' => $options,
    ];

    $elements['raw_value_label'] = [
      '#title' => $this->t("Use the entity's label instead of the entity's id as value in the facet query string."),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('raw_value_label'),
      '#description' => $this->t("Check this if you have facets based on a referenced entity's field values."),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    if ($facet = $this->getFacet()) {
      $summary[] = $this->t('Selected facet: @facet', ['@facet' => $facet->label()])->render();
    }
    else {
      $summary[] = $this->t('No facet selected')->render();
    }

    return $summary;
  }

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $facet = $this->getFacet();
    if (empty($facet)) {
      return [];
    }

    // Instead of trying to guess how the facet URLs should be formatted, let
    // the facet's own URL processor do the work of building them. Then the
    // URLs will be formatted correctly no matter what processor is being used,
    // for instance Facets Pretty Paths.
    $url_processor_id = $facet->getFacetSourceConfig()->getUrlProcessorName();
    $configuration = ['facet' => $facet];
    /** @var \Drupal\facets\UrlProcessor\UrlProcessorInterface $url_processor */
    $url_processor = $this->urlProcessorPluginManager->createInstance($url_processor_id, $configuration);

    $elements = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // Create a fake Result object from the field item so that we can pass
      // it to the URL processor.
      $display_value = $entity->label();
      if ($this->getSetting('raw_value_label')) {
        $raw_value = strtolower($entity->label());
      }
      else {
        $raw_value = $entity->id();
      }
      $result = new Result($facet, $raw_value, $display_value, 0);
      $result = $url_processor->buildUrls($facet, [$result])[0];

      // Invalidate the cache when the referenced entity or the facet source
      // config changes.  The source display config, for instance a view, should
      // be added here too, but there really isn't any way to access that config
      // entity through the API.
      $cache_tags = Cache::mergeTags($entity->getCacheTags(), $facet->getFacetSourceConfig()->getCacheTags());

      $elements[$delta] = $this->buildElement($result->getUrl(), $entity) + [
        '#cache' => [
          'tags' => $cache_tags,
        ],
      ];
    }

    return $elements;
  }

  /**
   * Builds a single element's render array.
   *
   * @param \Drupal\Core\Url $url
   *   The processed facet URL.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being displayed.
   *
   * @return array
   *   A render array.
   */
  abstract protected function buildElement(Url $url, EntityInterface $entity): array;

  /**
   * Gets the configured facet entity.
   *
   * @return \Drupal\facets\FacetInterface|null
   *   The configured facet or null if not set.
   */
  protected function getFacet(): ?FacetInterface {
    if (
      !isset($this->facet) &&
      ($facet_id = $this->getSetting('facet')) &&
      ($facet = $this->facetStorage->load($facet_id))
    ) {
      /** @var \Drupal\facets\FacetInterface $facet */
      $this->facet = $facet;
    }

    return $this->facet ?? NULL;
  }

}
