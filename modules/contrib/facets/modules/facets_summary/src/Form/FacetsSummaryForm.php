<?php

namespace Drupal\facets_summary\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\SubformState;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\facets_summary\Processor\ProcessorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring the processors of a facet.
 */
class FacetsSummaryForm extends EntityForm {

  /**
   * The facet being configured.
   *
   * @var \Drupal\facets\FacetInterface
   */
  protected $facet;

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
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FacetSourcePluginManager $facet_source_plugin_manager, DefaultFacetManager $facet_manager, ProcessorPluginManager $processor_plugin_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->facetSourcePluginManager = $facet_source_plugin_manager;
    $this->facetSummaryStorage = $entity_type_manager->getStorage('facets_summary');
    $this->facetManager = $facet_manager;
    $this->processorPluginManager = $processor_plugin_manager;

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

    return new static($entity_type_manager, $facet_source_plugin_manager, $facet_manager, $processor_plugin_manager);
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
    $form['#attached']['library'][] = 'facets/drupal.facets.admin_css';

    /** @var \Drupal\facets_summary\FacetsSummaryInterface $facets_summary */
    $facets_summary = $this->entity;

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'facets/drupal.facets.index-active-formatters';
    $form['#title'] = $this->t('Edit %label facets summary', ['%label' => $facets_summary->label()]);

    $form['facets'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Enabled facets'),
        $this->t('Label'),
        $this->t('Separator'),
        $this->t('Show counts'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'facets-order-weight',
        ],
      ],
      '#caption' => $this->t('Select the facets to be shown in the summary block. You can reorder them.'),
    ];
    $facets = $facets_summary->getFacets();
    $default_facets = array_keys($facets);

    $all_facets = $this->facetManager->getFacetsByFacetSourceId($facets_summary->getFacetSourceId());
    if (!empty($all_facets)) {
      foreach ($all_facets as $facet) {
        if (!in_array($facet->id(), $default_facets)) {
          $facets[$facet->id()] = [
            'label' => $facet->getName(),
            'separator' => ', ',
            'show_count' => FALSE,
          ];
        }
        $facets[$facet->id()]['name'] = $facet->getName();
      }

      foreach ($facets as $id => $facet) {
        $form['facets'][$id] = [
          'checked' => [
            '#type' => 'checkbox',
            '#title' => $facet['name'],
            '#default_value' => in_array($id, $default_facets),
          ],
          'label' => [
            '#type' => 'textfield',
            '#title' => $this->t('Label'),
            '#default_value' => $facet['label'],
            '#size' => 25,
          ],
          'separator' => [
            '#type' => 'textfield',
            '#title' => $this->t('Separator'),
            '#default_value' => $facet['separator'],
            '#size' => 8,
          ],
          'show_count' => [
            '#type' => 'checkbox',
            '#default_value' => $facet['show_count'],
          ],
          'weight' => [
            '#type' => 'weight',
            '#title' => $this->t('Weight for @title', ['@title' => $facet['name']]),
            '#title_display' => 'invisible',
            '#attributes' => ['class' => ['facets-order-weight']],
          ],
          '#attributes' => ['class' => ['draggable']],
        ];
      }
    }
    else {
      $form['facets'] = ['#markup' => $this->t('No facets found.')];
    }

    // Retrieve lists of all processors, and the stages and weights they have.
    if (!$form_state->has('processors')) {
      $all_processors = $facets_summary->getProcessors(FALSE);
    }
    else {
      $all_processors = $form_state->get('processors');
    }
    $enabled_processors = $facets_summary->getProcessors(TRUE);

    $stages = $this->processorPluginManager->getProcessingStages();
    $processors_by_stage = [];
    foreach ($stages as $stage => $definition) {
      $processors_by_stage[$stage] = $facets_summary->getProcessorsByStage($stage, FALSE);
    }

    // Add the list of all other processors with checkboxes to enable/disable
    // them.
    $form['facets_summary_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Facets Summary settings'),
      '#attributes' => [
        'class' => [
          'search-api-status-wrapper',
        ],
      ],
    ];
    foreach ($all_processors as $processor_id => $processor) {
      $clean_css_id = Html::cleanCssIdentifier($processor_id);
      $form['facets_summary_settings'][$processor_id]['status'] = [
        '#type' => 'checkbox',
        '#title' => (string) $processor->getPluginDefinition()['label'],
        '#default_value' => !empty($enabled_processors[$processor_id]),
        '#description' => $processor->getDescription(),
        '#attributes' => [
          'class' => [
            'search-api-processor-status-' . $clean_css_id,
          ],
          'data-id' => $clean_css_id,
        ],
      ];

      $form['facets_summary_settings'][$processor_id]['settings'] = [];
      $processor_form_state = SubformState::createForSubform($form['facets_summary_settings'][$processor_id]['settings'], $form, $form_state);
      $processor_form = $processor->buildConfigurationForm($form, $processor_form_state, $facets_summary);
      if ($processor_form) {
        $form['facets_summary_settings'][$processor_id]['settings'] = [
          '#type' => 'details',
          '#title' => $this->t('%processor settings', ['%processor' => (string) $processor->getPluginDefinition()['label']]),
          '#open' => TRUE,
          '#attributes' => [
            'class' => [
              'facets-processor-settings-' . Html::cleanCssIdentifier($processor_id),
              'facets-processor-settings-facet',
              'facets-processor-settings',
            ],
          ],
          '#states' => [
            'visible' => [
              ':input[name="facets_summary_settings[' . $processor_id . '][status]"]' => ['checked' => TRUE],
            ],
          ],
        ];
        $form['facets_summary_settings'][$processor_id]['settings'] += $processor_form;
      }
    }

    $form['weights'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['weights']['order'] = [
      '#prefix' => '<h3>',
      '#markup' => $this->t('Processor order'),
      '#suffix' => '</h3>',
    ];

    // Order enabled processors per stage, create all the containers for the
    // different stages.
    foreach ($stages as $stage => $description) {
      $form['weights'][$stage] = [
        '#type' => 'fieldset',
        '#title' => $description['label'],
        '#attributes' => [
          'class' => [
            'search-api-stage-wrapper',
            'search-api-stage-wrapper-' . Html::cleanCssIdentifier($stage),
          ],
        ],
      ];
      $form['weights'][$stage]['order'] = [
        '#type' => 'table',
      ];
      $form['weights'][$stage]['order']['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'search-api-processor-weight-' . Html::cleanCssIdentifier($stage),
      ];
    }

    $processor_settings = $facets_summary->getProcessorConfigs();

    // Fill in the containers previously created with the processors that are
    // enabled on the facet.
    foreach ($processors_by_stage as $stage => $processors) {
      /** @var \Drupal\facets\Processor\ProcessorInterface $processor */
      foreach ($processors as $processor_id => $processor) {
        $weight = isset($processor_settings[$processor_id]['weights'][$stage])
          ? $processor_settings[$processor_id]['weights'][$stage]
          : $processor->getDefaultWeight($stage);
        if ($processor->isHidden()) {
          $form['processors'][$processor_id]['weights'][$stage] = [
            '#type' => 'value',
            '#value' => $weight,
          ];
          continue;
        }
        $form['weights'][$stage]['order'][$processor_id]['#attributes']['class'][] = 'draggable';
        $form['weights'][$stage]['order'][$processor_id]['#attributes']['class'][] = 'search-api-processor-weight--' . Html::cleanCssIdentifier($processor_id);
        $form['weights'][$stage]['order'][$processor_id]['#weight'] = $weight;
        $form['weights'][$stage]['order'][$processor_id]['label']['#plain_text'] = (string) $processor->getPluginDefinition()['label'];
        $form['weights'][$stage]['order'][$processor_id]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight for processor %title', ['%title' => (string) $processor->getPluginDefinition()['label']]),
          '#title_display' => 'invisible',
          '#default_value' => $weight,
          '#parents' => ['processors', $processor_id, 'weights', $stage],
          '#attributes' => [
            'class' => [
              'search-api-processor-weight-' . Html::cleanCssIdentifier($stage),
            ],
          ],
        ];
      }
    }

    // Add vertical tabs containing the settings for the processors. Tabs for
    // disabled processors are hidden with JS magic, but need to be included in
    // case the processor is enabled.
    $form['processor_settings'] = [
      '#title' => $this->t('Processor settings'),
      '#type' => 'vertical_tabs',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\facets_summary\FacetsSummaryInterface $facets_summary */
    $facets_summary = $this->entity;

    $values = $form_state->getValues();
    /** @var \Drupal\facets_summary\Processor\ProcessorInterface[] $processors */
    $processors = $facets_summary->getProcessors(FALSE);

    // Iterate over all processors that have a form and are enabled.
    foreach ($form['facets_summary_settings'] as $processor_id => $processor_form) {
      if (!empty($values['processors'][$processor_id])) {

        $processor_form_state = SubformState::createForSubform($form['facets_summary_settings'][$processor_id]['settings'], $form, $form_state);
        $processors[$processor_id]->validateConfigurationForm($form['facets_summary_settings'][$processor_id], $processor_form_state, $facets_summary);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Store processor settings.
    /** @var \Drupal\facets_summary\FacetsSummaryInterface $facets_summary */
    $facets_summary = $this->entity;

    /** @var \Drupal\facets_summary\Processor\ProcessorInterface $processor */
    $processors = $facets_summary->getProcessors(FALSE);
    foreach ($processors as $processor_id => $processor) {
      $form_container_key = 'facets_summary_settings';
      if (empty($values[$form_container_key][$processor_id]['status'])) {
        $facets_summary->removeProcessor($processor_id);
        continue;
      }

      $new_settings = [
        'processor_id' => $processor_id,
        'weights' => [],
        'settings' => [],
      ];

      if (!empty($values['processors'][$processor_id]['weights'])) {
        $new_settings['weights'] = $values['processors'][$processor_id]['weights'];
      }

      if (isset($form[$form_container_key][$processor_id]['settings'])) {
        $processor_form_state = SubformState::createForSubform($form[$form_container_key][$processor_id]['settings'], $form, $form_state);
        $processor->submitConfigurationForm($form[$form_container_key][$processor_id]['settings'], $processor_form_state, $facets_summary);
        $new_settings['settings'] = $processor->getConfiguration();
      }
      $facets_summary->addProcessor($new_settings);
    }

    $value = $form_state->getValue('facets') ?: [];
    $enabled_facets = array_filter($value, function ($item) {
      return isset($item['checked']) && $item['checked'] == 1;
    });

    $facets_summary->setFacets((array) $enabled_facets);
    $facets_summary->save();

    drupal_set_message($this->t('Facets Summary %name has been updated.', ['%name' => $facets_summary->getName()]));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // We don't have a "delete" action here.
    unset($actions['delete']);

    return $actions;
  }

}
