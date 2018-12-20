<?php

namespace Drupal\facets\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\Entity\FacetSource;
use Drupal\facets\UrlProcessor\UrlProcessorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Uuid\UuidInterface;

/**
 * Provides a form for editing facet sources.
 *
 * Configuration saved trough this form is specific for a facet source and can
 * be used by all facets on this facet source.
 */
class FacetSourceEditForm extends EntityForm {

  /**
   * The plugin manager for URL Processors.
   *
   * @var \Drupal\facets\UrlProcessor\UrlProcessorPluginManager
   */
  protected $urlProcessorPluginManager;

  /**
   * The UUID generator interface.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.facets.url_processor'),
      $container->get('module_handler'),
      $container->get('uuid')
    );
  }

  /**
   * Constructs a FacetSourceEditForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\facets\UrlProcessor\UrlProcessorPluginManager $url_processor_plugin_manager
   *   The url processor plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Drupal's module handler.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   Drupal's uuid generator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, UrlProcessorPluginManager $url_processor_plugin_manager, ModuleHandlerInterface $moduleHandler, UuidInterface $uuid) {
    $facet_source_storage = $entity_type_manager->getStorage('facets_facet_source');

    $this->urlProcessorPluginManager = $url_processor_plugin_manager;
    $this->uuid = $uuid;

    // Make sure we remove colons from the source id, those are disallowed in
    // the entity id.
    $source_id = $this->getRequest()->get('facets_facet_source');
    $source_id = str_replace(':', '__', $source_id);

    $facet_source = $facet_source_storage->load($source_id);

    if ($facet_source instanceof FacetSource) {
      $this->setEntity($facet_source);
    }
    else {
      // We didn't have a facet source config entity yet for this facet source
      // plugin, so we create it on the fly.
      // Generate and set an uuid for config export and import to work.
      $facet_source = new FacetSource(
        [
          'id' => $source_id,
          'name' => $this->getRequest()->get('facets_facet_source'),
          'is_new' => TRUE,
          'uuid' => $this->uuid->generate(),
        ],
        'facets_facet_source'
      );
      $facet_source->save();
      $this->setEntity($facet_source);
    }

    $this->setModuleHandler($moduleHandler);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'facet_source_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /** @var \Drupal\facets\FacetSourceInterface $facet_source */
    $facet_source = $this->getEntity();

    $form['#tree'] = TRUE;
    $form['filter_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter key'),
      '#size' => 20,
      '#maxlength' => 255,
      '#default_value' => $facet_source->getFilterKey(),
      '#description' => $this->t(
        'The key used in the url to identify the facet source.
        When using multiple facet sources you should make sure each facet source has a different filter key.'
      ),
    ];

    $url_processors = [];
    $url_processors_description = [];
    foreach ($this->urlProcessorPluginManager->getDefinitions() as $definition) {
      $url_processors[$definition['id']] = $definition['label'];
      $url_processors_description[] = $definition['description'];
    }
    $form['url_processor'] = [
      '#type' => 'radios',
      '#title' => $this->t('URL Processor'),
      '#options' => $url_processors,
      '#default_value' => $facet_source->getUrlProcessorName(),
      '#description' => $this->t(
        'The URL Processor defines the url structure used for this facet source.') . '<br />- ' . implode('<br>- ', $url_processors_description),
    ];

    $breadcrumb_settings = $facet_source->getBreadcrumbSettings();
    $form['breadcrumb'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Breadcrumb'),
    ];
    $form['breadcrumb']['active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Append active facets to breadcrumb'),
      '#default_value' => isset($breadcrumb_settings['active']) ? $breadcrumb_settings['active'] : FALSE,
    ];
    $form['breadcrumb']['before'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show facet label before active facet'),
      '#default_value' => isset($breadcrumb_settings['before']) ? $breadcrumb_settings['before'] : TRUE,
      '#states' => [
        'visible' => [
          ':input[name="breadcrumb[active]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['breadcrumb']['group'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Group active items under same crumb (not implemented yet - now always grouping)'),
      '#default_value' => isset($breadcrumb_settings['group']) ? $breadcrumb_settings['group'] : FALSE,
      '#states' => [
        'visible' => [
          ':input[name="breadcrumb[active]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // The parent's form build method will add a save button.
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $facet_source = $this->getEntity();
    drupal_set_message($this->t('Facet source %name has been saved.', ['%name' => $facet_source->label()]));
    $form_state->setRedirect('entity.facets_facet.collection');
  }

}
