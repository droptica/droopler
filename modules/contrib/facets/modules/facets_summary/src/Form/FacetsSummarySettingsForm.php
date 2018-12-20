<?php

namespace Drupal\facets_summary\Form;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\facets\FacetSource\SearchApiFacetSourceInterface;
use Drupal\facets_summary\Processor\ProcessorPluginManager;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring the processors of a facet.
 */
class FacetsSummarySettingsForm extends EntityForm {

  /**
   * The facet storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $facetSummaryStorage;

  /**
   * The plugin manager for facet sources.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginManager
   */
  protected $facetSourcePluginManager;

  /**
   * The facet manager service.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetManager;

  /**
   * The facets_summary processor plugin manager service.
   *
   * @var \Drupal\facets_summary\Processor\ProcessorPluginManager
   */
  protected $processorPluginManager;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs an FacetDisplayForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\facets\FacetSource\FacetSourcePluginManager $facet_source_plugin_manager
   *   The plugin manager for facet sources.
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facet_manager
   *   The Default Facet Manager.
   * @param \Drupal\facets_summary\Processor\ProcessorPluginManager $processor_plugin_manager
   *   The Facets Summary Processor Plugin Manager.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FacetSourcePluginManager $facet_source_plugin_manager, DefaultFacetManager $facet_manager, ProcessorPluginManager $processor_plugin_manager, BlockManagerInterface $block_manager, UrlGeneratorInterface $url_generator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->facetSourcePluginManager = $facet_source_plugin_manager;
    $this->facetSummaryStorage = $entity_type_manager->getStorage('facets_summary');
    $this->facetManager = $facet_manager;
    $this->processorPluginManager = $processor_plugin_manager;
    $this->blockManager = $block_manager;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManager $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    /** @var \Drupal\facets\FacetSource\FacetSourcePluginManager $facet_source_plugin_manager */
    $facet_source_plugin_manager = $container->get('plugin.manager.facets.facet_source');

    /** @var \Drupal\facets\FacetManager\DefaultFacetManager $facet_manager */
    $facet_manager = $container->get('facets.manager');

    /** @var \Drupal\facets_summary\Processor\ProcessorPluginManager $processor_plugin_manager */
    $processor_plugin_manager = $container->get('plugin.manager.facets_summary.processor');

    /** @var \Drupal\Core\Block\BlockManager $block_manager */
    $block_manager = $container->get('plugin.manager.block');

    /** @var \Drupal\Core\Routing\UrlGeneratorInterface $url_generator */
    $url_generator = $container->get('url_generator');

    return new static($entity_type_manager, $facet_source_plugin_manager, $facet_manager, $processor_plugin_manager, $block_manager, $url_generator);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\facets_summary\FacetsSummaryInterface $facets_summary */
    $facets_summary = $this->entity;

    $facet_sources = [];

    // If the form is being rebuilt, rebuild the entity with the current form
    // values.
    if ($form_state->isRebuilding()) {
      $this->entity = $this->buildEntity($form, $form_state);
    }

    $form = parent::form($form, $form_state);

    // Set the page title according to whether we are creating or editing the
    // facet.
    if ($this->getEntity()->isNew()) {
      $form['#title'] = $this->t('Add facets summary');
    }
    else {
      $form['#title'] = $this->t('Facets settings for %label', [
        '%label' => $this->getEntity()
          ->label(),
      ]);
    }

    foreach ($this->facetSourcePluginManager->getDefinitions() as $facet_source_id => $definition) {
      $facet_sources[$definition['id']] = !empty($definition['label']) ? $definition['label'] : $facet_source_id;
    }

    if (count($facet_sources) == 0) {
      $form['#markup'] = $this->t('You currently have no facet sources defined. You should start by adding a facet source before creating facets.');
      return TRUE;
    }

    $form['facet_source_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Facet source'),
      '#description' => $this->t('The source where this summary will be built from.'),
      '#options' => $facet_sources,
      '#default_value' => $facets_summary->getFacetSourceId(),
      '#required' => TRUE,
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('The administrative name used for this summary.'),
      '#default_value' => $facets_summary->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $facets_summary->id(),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this->facetSummaryStorage, 'load'],
        'source' => ['name'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\facets_summary\FacetsSummaryInterface $facets_summary */
    $facets_summary = $this->getEntity();
    $is_new = $facets_summary->isNew();
    $facets_summary->save();

    if ($is_new) {
      if ($this->moduleHandler->moduleExists('block')) {
        $message = $this->t('Facet Summary %name has been created. Go to the <a href=":block_overview">Block overview page</a> to place the new block in the desired region.', ['%name' => $facets_summary->getName(), ':block_overview' => $this->urlGenerator->generateFromRoute('block.admin_display')]);
        drupal_set_message($message);
        $form_state->setRedirect('entity.facets_summary.edit_form', ['facets_summary' => $facets_summary->id()]);
      }

      // On facet creation, enable all locked processors by default, using their
      // default settings.
      $stages = $this->processorPluginManager->getProcessingStages();
      $processors_definitions = $this->processorPluginManager->getDefinitions();

      foreach ($processors_definitions as $processor_id => $processor) {
        $is_locked = isset($processor['locked']) && $processor['locked'] == TRUE;
        $is_default_enabled = isset($processor['default_enabled']) && $processor['default_enabled'] == TRUE;
        if ($is_locked || $is_default_enabled) {
          $weights = [];
          foreach ($stages as $stage_id => $stage) {
            if (isset($processor['stages'][$stage_id])) {
              $weights[$stage_id] = $processor['stages'][$stage_id];
            }
          }
          $facets_summary->addProcessor([
            'processor_id' => $processor_id,
            'weights' => $weights,
            'settings' => [],
          ]);
        }
      }
    }
    else {
      drupal_set_message($this->t('Facet %name has been updated.', ['%name' => $facets_summary->getName()]));
    }

    // Clear Drupal cache for blocks to reflect recent changes.
    $this->blockManager->clearCachedDefinitions();
    $facet_source_id = $form_state->getValue('facet_source_id');
    list($type,) = explode(':', $facet_source_id);
    if ($type !== 'search_api') {
      return $facets_summary;
    }

    // Ensure that the caching of the view display is disabled, so the search
    // correctly returns the facets.
    $facet_source = $this->facetSourcePluginManager->createInstance($facet_source_id, ['facet' => $this->getEntity()]);
    if (isset($facet_source) && $facet_source instanceof SearchApiFacetSourceInterface) {
      $view = $facet_source->getViewsDisplay();
      if ($view !== NULL) {
        $view->display_handler->overrideOption('cache', ['type' => 'none']);
        $view->save();
        drupal_set_message($this->t('Caching of view %view has been disabled.', ['%view' => $view->storage->label()]));
      }
    }

    return $facets_summary;
  }

}
