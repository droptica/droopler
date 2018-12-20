<?php

namespace Drupal\facets\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\facets\Plugin\facets\facet_source\SearchApiDisplay;
use Drupal\facets\FacetSource\SearchApiFacetSourceInterface;
use Drupal\search_api\Plugin\search_api\display\ViewsRest;
use Drupal\facets\Processor\ProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginManager;
use Drupal\facets\UrlProcessor\UrlProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\facets\Widget\WidgetPluginManager;
use Drupal\facets\Processor\SortProcessorInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Url;

/**
 * Provides a form for configuring the processors of a facet.
 */
class FacetForm extends EntityForm {

  /**
   * The facet being configured.
   *
   * @var \Drupal\facets\FacetInterface
   */
  protected $entity;

  /**
   * The processor manager.
   *
   * @var \Drupal\facets\Processor\ProcessorPluginManager
   */
  protected $processorPluginManager;

  /**
   * The plugin manager for widgets.
   *
   * @var \Drupal\facets\Widget\WidgetPluginManager
   */
  protected $widgetPluginManager;

  /**
   * The plugin manager for facet sources.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginManager
   */
  protected $facetSourcePluginManager;

  /**
   * Constructs an FacetDisplayForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\facets\Processor\ProcessorPluginManager $processor_plugin_manager
   *   The processor plugin manager.
   * @param \Drupal\facets\Widget\WidgetPluginManager $widget_plugin_manager
   *   The plugin manager for widgets.
   * @param \Drupal\facets\FacetSource\FacetSourcePluginManager $facet_source_plugin_manager
   *   The plugin manager for facet sources.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ProcessorPluginManager $processor_plugin_manager, WidgetPluginManager $widget_plugin_manager, FacetSourcePluginManager $facet_source_plugin_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->processorPluginManager = $processor_plugin_manager;
    $this->widgetPluginManager = $widget_plugin_manager;
    $this->facetSourcePluginManager = $facet_source_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.facets.processor'),
      $container->get('plugin.manager.facets.widget'),
      $container->get('plugin.manager.facets.facet_source')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * Builds the configuration forms for all selected widgets.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function buildWidgetConfigForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->getEntity();
    $widget_plugin_id = $form_state->getValue('widget') ?: $facet->getWidget()['type'];
    $widget_config = $form_state->getValue('widget_config') ?: $facet->getWidget()['config'];
    if (empty($widget_plugin_id)) {
      return;
    }

    /** @var \Drupal\facets\Widget\WidgetPluginBase $widget */
    $facet->setWidget($widget_plugin_id, $widget_config);
    $widget = $facet->getWidgetInstance();

    $arguments = ['%widget' => $widget->getPluginDefinition()['label']];
    if (!$config_form = $widget->buildConfigurationForm([], $form_state, $facet)) {
      $type = 'details';
      $config_form = ['#markup' => $this->t('%widget widget needs no configuration.', $arguments)];
    }
    else {
      $type = 'fieldset';
    }
    $form['widget_config'] = [
      '#type' => $type,
      '#tree' => TRUE,
      '#title' => $this->t('%widget settings', $arguments),
      '#attributes' => ['id' => 'facets-widget-config-form'],
    ] + $config_form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'facets/drupal.facets.admin_css';

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->entity;

    $facet_sources = [];
    foreach ($this->facetSourcePluginManager->getDefinitions() as $facet_source_id => $definition) {
      $facet_sources[$definition['id']] = !empty($definition['label']) ? $definition['label'] : $facet_source_id;
    }
    if (isset($facet_sources[$facet->getFacetSourceId()])) {
      $form['facet_source'] = [
        '#type' => 'item',
        '#title' => $this->t('Facet source'),
        '#markup' => $facet_sources[$facet->getFacetSourceId()],
      ];
    }

    $widget_options = [];
    foreach ($this->widgetPluginManager->getDefinitions() as $widget_id => $definition) {
      $widget_options[$widget_id] = !empty($definition['label']) ? $definition['label'] : $widget_id;
    }

    // Filters all the available widgets to make sure that only those that
    // this facet applies for are enabled.
    foreach ($widget_options as $widget_id => $label) {
      $widget = $this->widgetPluginManager->createInstance($widget_id);
      if (!$widget->supportsFacet($facet)) {
        unset($widget_options[$widget_id]);
      }
    }
    unset($widget_id, $label, $widget);

    $widget = $facet->getWidgetInstance();
    $form['widget'] = [
      '#type' => 'radios',
      '#title' => $this->t('Widget'),
      '#description' => $this->t('The widget used for displaying this facet.'),
      '#options' => $widget_options,
      '#default_value' => $facet->getWidget()['type'],
      '#required' => TRUE,
      '#ajax' => [
        'trigger_as' => ['name' => 'widget_configure'],
        'callback' => '::buildAjaxWidgetConfigForm',
        'wrapper' => 'facets-widget-config-form',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];
    $form['widget_config'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'facets-widget-config-form',
      ],
      '#tree' => TRUE,
    ];
    $form['widget_configure_button'] = [
      '#type' => 'submit',
      '#name' => 'widget_configure',
      '#value' => $this->t('Configure widget'),
      '#limit_validation_errors' => [['widget']],
      '#submit' => ['::submitAjaxWidgetConfigForm'],
      '#ajax' => [
        'callback' => '::buildAjaxWidgetConfigForm',
        'wrapper' => 'facets-widget-config-form',
      ],
      '#attributes' => ['class' => ['js-hide']],
    ];
    $this->buildWidgetConfigForm($form, $form_state);

    // Retrieve lists of all processors, and the stages and weights they have.
    if (!$form_state->has('processors')) {
      $all_processors = $facet->getProcessors(FALSE);
      $sort_processors = function (ProcessorInterface $a, ProcessorInterface $b) {
        return strnatcasecmp((string) $a->getPluginDefinition()['label'], (string) $b->getPluginDefinition()['label']);
      };
      uasort($all_processors, $sort_processors);
    }
    else {
      $all_processors = $form_state->get('processors');
    }
    $enabled_processors = $facet->getProcessors(TRUE);

    // Filters all the available processors to make sure that only those that
    // this facet applies for are enabled.
    foreach ($all_processors as $processor_id => $processor) {
      if (!$processor->supportsFacet($facet)) {
        unset($all_processors[$processor_id]);
      }
    }
    unset($processor_id, $processor);

    $stages = $this->processorPluginManager->getProcessingStages();
    $processors_by_stage = [];
    foreach ($stages as $stage => $definition) {
      foreach ($facet->getProcessorsByStage($stage, FALSE) as $processor_id => $processor) {
        if ($processor->supportsFacet($facet)) {
          $processors_by_stage[$stage][$processor_id] = $processor;
        }
      }
      unset($processor_id, $processor);
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'facets/drupal.facets.index-active-formatters';
    $form['#title'] = $this->t('Edit %label facet', ['%label' => $facet->label()]);

    // Add the list of all other processors with checkboxes to enable/disable
    // them.
    $form['facet_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Facet settings'),
      '#attributes' => [
        'class' => [
          'search-api-status-wrapper',
        ],
      ],
    ];

    foreach ($all_processors as $processor_id => $processor) {
      if (!($processor instanceof SortProcessorInterface) && !($processor instanceof UrlProcessorInterface)) {

        $default_value = $processor->isLocked() || $widget->isPropertyRequired($processor_id, 'processors') || !empty($enabled_processors[$processor_id]);
        $clean_css_id = Html::cleanCssIdentifier($processor_id);
        $form['facet_settings'][$processor_id]['status'] = [
          '#type' => 'checkbox',
          '#title' => (string) $processor->getPluginDefinition()['label'],
          '#default_value' => $default_value,
          '#description' => $processor->getDescription(),
          '#attributes' => [
            'class' => [
              'search-api-processor-status-' . $clean_css_id,
            ],
            'data-id' => $clean_css_id,
          ],
          '#disabled' => $processor->isLocked() || $widget->isPropertyRequired($processor_id, 'processors'),
          '#access' => !$processor->isHidden(),
        ];

        $form['facet_settings'][$processor_id]['settings'] = [];
        $processor_form_state = SubformState::createForSubform($form['facet_settings'][$processor_id]['settings'], $form, $form_state);
        $processor_form = $processor->buildConfigurationForm($form, $processor_form_state, $facet);
        if ($processor_form) {
          $form['facet_settings'][$processor_id]['settings'] = [
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
                ':input[name="facet_settings[' . $processor_id . '][status]"]' => ['checked' => TRUE],
              ],
            ],
          ];
          $form['facet_settings'][$processor_id]['settings'] += $processor_form;
        }
      }
    }
    // Add the list of widget sort processors with checkboxes to enable/disable
    // them.
    $form['facet_sorting'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Facet sorting'),
      '#attributes' => [
        'class' => [
          'search-api-status-wrapper',
        ],
      ],
    ];
    foreach ($all_processors as $processor_id => $processor) {
      if ($processor instanceof SortProcessorInterface) {
        $default_value = $processor->isLocked() || $widget->isPropertyRequired($processor_id, 'processors') || !empty($enabled_processors[$processor_id]);
        $clean_css_id = Html::cleanCssIdentifier($processor_id);
        $form['facet_sorting'][$processor_id]['status'] = [
          '#type' => 'checkbox',
          '#title' => (string) $processor->getPluginDefinition()['label'],
          '#default_value' => $default_value,
          '#description' => $processor->getDescription(),
          '#attributes' => [
            'class' => [
              'search-api-processor-status-' . $clean_css_id,
            ],
            'data-id' => $clean_css_id,
          ],
          '#disabled' => $processor->isLocked(),
          '#access' => !$processor->isHidden(),
        ];

        $form['facet_sorting'][$processor_id]['settings'] = [];
        $processor_form_state = SubformState::createForSubform($form['facet_sorting'][$processor_id]['settings'], $form, $form_state);
        $processor_form = $processor->buildConfigurationForm($form, $processor_form_state, $facet);
        if ($processor_form) {
          $form['facet_sorting'][$processor_id]['settings'] = [
            '#type' => 'container',
            '#open' => TRUE,
            '#attributes' => [
              'class' => [
                'facets-processor-settings-' . Html::cleanCssIdentifier($processor_id),
                'facets-processor-settings-sorting',
                'facets-processor-settings',
              ],
            ],
            '#states' => [
              'visible' => [
                ':input[name="facet_sorting[' . $processor_id . '][status]"]' => ['checked' => TRUE],
              ],
            ],
          ];
          $form['facet_sorting'][$processor_id]['settings'] += $processor_form;
        }
      }
    }

    $form['facet_settings']['only_visible_when_facet_source_is_visible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide facet when facet source is not rendered'),
      '#description' => $this->t('Only display the facet if the facet source is rendered. If you want to display the facets on other pages too, you need to uncheck this setting.'),
      '#default_value' => $widget->isPropertyRequired('only_visible_when_facet_source_is_visible', 'settings') ?: $facet->getOnlyVisibleWhenFacetSourceIsVisible(),
      '#disabled' => $widget->isPropertyRequired('only_visible_when_facet_source_is_visible', 'settings') ?: 0,
    ];

    $form['facet_settings']['show_only_one_result'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ensure that only one result can be displayed'),
      '#description' => $this->t('Check this to ensure that only <em>one</em> result at a time can be selected for this facet.'),
      '#default_value' => $widget->isPropertyRequired('show_only_one_result', 'settings') ?: $facet->getShowOnlyOneResult(),
      '#disabled' => $widget->isPropertyRequired('show_only_one_result', 'settings') ?: 0,
    ];

    $form['facet_settings']['url_alias'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL alias'),
      '#description' => $this->t('The alias appears in the URL to identify this facet. It cannot be blank. Allowed are only letters, digits and the following characters: dot ("."), hyphen ("-"), underscore ("_"), and tilde ("~").'),
      '#default_value' => $facet->getUrlAlias(),
      '#maxlength' => 50,
      '#required' => TRUE,
    ];
    $form['facet_settings']['show_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show title of facet'),
      '#description' => $this->t('Show the title of the facet trough a twig template'),
      '#default_value' => $facet->get('show_title'),
    ];

    $empty_behavior_config = $facet->getEmptyBehavior();
    $form['facet_settings']['empty_behavior'] = [
      '#type' => 'radios',
      '#title' => $this->t('Empty facet behavior'),
      '#default_value' => $empty_behavior_config['behavior'] ?: 'none',
      '#options' => ['none' => $this->t('Do not display facet'), 'text' => $this->t('Display text')],
      '#description' => $this->t('Take this action if a facet has no items.'),
      '#required' => TRUE,
    ];
    $form['facet_settings']['empty_behavior_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="facet_settings[empty_behavior]"]' => ['value' => 'text'],
        ],
      ],
    ];
    $form['facet_settings']['empty_behavior_container']['empty_behavior_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Empty text'),
      '#format' => isset($empty_behavior_config['text_format']) ? $empty_behavior_config['text_format'] : 'plain_text',
      '#editor' => TRUE,
      '#default_value' => isset($empty_behavior_config['text_format']) ? $empty_behavior_config['text'] : '',
    ];

    $form['facet_settings']['query_operator'] = [
      '#type' => 'radios',
      '#title' => $this->t('Operator'),
      '#options' => ['or' => $this->t('OR'), 'and' => $this->t('AND')],
      '#description' => $this->t('AND filters are exclusive and narrow the result set. OR filters are inclusive and widen the result set.'),
      '#default_value' => $facet->getQueryOperator(),
    ];

    $hard_limit_options = [3, 5, 10, 15, 20, 30, 40, 50, 75, 100, 250, 500];
    $form['facet_settings']['hard_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Hard limit'),
      '#default_value' => $facet->getHardLimit(),
      '#options' => [0 => $this->t('No limit')] + array_combine($hard_limit_options, $hard_limit_options),
      '#description' => $this->t('Display no more than this number of facet items.'),
    ];
    if (!$facet->getFacetSource() instanceof SearchApiDisplay) {
      $form['facet_settings']['hard_limit']['#disabled'] = TRUE;
      $form['facet_settings']['hard_limit']['#description'] .= '<br />';
      $form['facet_settings']['hard_limit']['#description'] .= $this->t('This setting only works with Search API based facets.');
    }

    $form['facet_settings']['exclude'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude'),
      '#description' => $this->t('Exclude the selected facets from the search result instead of restricting it to them.'),
      '#default_value' => $facet->getExclude(),
    ];

    $form['facet_settings']['use_hierarchy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use hierarchy'),
      '#default_value' => $facet->getUseHierarchy(),
    ];
    if (!$facet->getFacetSource() instanceof SearchApiDisplay) {
      $form['facet_settings']['use_hierarchy']['#disabled'] = TRUE;
      $form['facet_settings']['use_hierarchy']['#description'] = $this->t('This setting only works with Search API based facets.');
    }
    else {
      $processor_url = Url::fromRoute('entity.search_api_index.processors', [
        'search_api_index' => $facet->getFacetSource()->getIndex()->id(),
      ]);
      $description = $this->t('Renders the items using hierarchy. Make sure to enable the hierarchy processor on the <a href=":processor-url">Search api index processor configuration</a> for this to work as expected. If disabled all items will be flatten.', [
        ':processor-url' => $processor_url->toString(),
      ]);
      $form['facet_settings']['use_hierarchy']['#description'] = $description;
      $form['facet_settings']['use_hierarchy']['#description'] .= '<br />';
      $form['facet_settings']['use_hierarchy']['#description'] .= '<strong>At this moment only hierarchical taxonomy terms are supported.</strong>';
    }

    $form['facet_settings']['expand_hierarchy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always expand hierarchy'),
      '#description' => $this->t('Render entire tree, regardless of whether the parents are active or not.'),
      '#default_value' => $facet->getExpandHierarchy(),
      '#states' => [
        'visible' => [
          ':input[name="facet_settings[use_hierarchy]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['facet_settings']['enable_parent_when_child_gets_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable parent when child gets disabled'),
      '#description' => $this->t('Uncheck this if you want to allow de-activating an entire hierarchical trail by clicking an active child.'),
      '#default_value' => $facet->getEnableParentWhenChildGetsDisabled(),
      '#states' => [
        'visible' => [
          ':input[name="facet_settings[use_hierarchy]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['facet_settings']['min_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum count'),
      '#default_value' => $facet->getMinCount(),
      '#description' => $this->t('Only display the results if there is this minimum amount of results.'),
      '#maxlength' => 4,
      '#required' => TRUE,
    ];
    if (!$facet->getFacetSource() instanceof SearchApiDisplay) {
      $form['facet_settings']['min_count']['#disabled'] = TRUE;
      $form['facet_settings']['min_count']['#description'] .= '<br />';
      $form['facet_settings']['min_count']['#description'] .= $this->t('This setting only works with Search API based facets.');
    }

    $form['facet_settings']['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#default_value' => $facet->getWeight(),
      '#description' => $this->t('This weight is used to determine the order of the facets in the URL if pretty paths are used.'),
      '#maxlength' => 4,
      '#required' => TRUE,
    ];

    $form['weights'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['weights']['order'] = [
      '#markup' => $this->t('Processor order'),
      '#prefix' => '<h3>',
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

    $processor_settings = $facet->getProcessorConfigs();

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

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->entity;

    $values = $form_state->getValues();
    /** @var \Drupal\facets\Processor\ProcessorInterface[] $processors */
    $processors = $facet->getProcessors(FALSE);

    // Iterate over all processors that have a form and are enabled.
    foreach ($form['facet_settings'] as $processor_id => $processor_form) {
      if (!empty($values['processors'][$processor_id])) {

        $processor_form_state = SubformState::createForSubform($form['facet_settings'][$processor_id]['settings'], $form, $form_state);
        $processors[$processor_id]->validateConfigurationForm($form['facet_settings'][$processor_id], $processor_form_state, $facet);
      }
    }
    // Iterate over all sorting processors that have a form and are enabled.
    foreach ($form['facet_sorting'] as $processor_id => $processor_form) {
      if (!empty($values['processors'][$processor_id])) {

        $processor_form_state = SubformState::createForSubform($form['facet_sorting'][$processor_id]['settings'], $form, $form_state);
        $processors[$processor_id]->validateConfigurationForm($form['facet_sorting'][$processor_id], $processor_form_state, $facet);
      }
    }

    // Only widgets that return an array can work with rest facet sources, so if
    // the user has selected another widget, we should point them to their
    // misconfiguration.
    if ($facet_source = $facet->getFacetSource()) {
      if ($facet_source instanceof SearchApiFacetSourceInterface) {
        if ($facet_source->getDisplay() instanceof ViewsRest) {
          if (strpos($values['widget'], 'array') === FALSE) {
            $form_state->setErrorByName('widget', $this->t('The Facet source is a Rest export. Please select a raw widget.'));
          }
        }
      }
    }

    // Validate url alias.
    $url_alias = $form_state->getValue(['facet_settings', 'url_alias']);
    if ($url_alias == 'page') {
      $form_state->setErrorByName('url_alias', $this->t('This URL alias is not allowed.'));
    }
    elseif (preg_match('/[^a-zA-Z0-9_~\.\-]/', $url_alias)) {
      $form_state->setErrorByName('url_alias', $this->t('The URL alias contains characters that are not allowed.'));
    }

    $already_enabled_facets_on_same_source = \Drupal::service('facets.manager')->getFacetsByFacetSourceId($facet->getFacetSourceId());
    /** @var \Drupal\facets\FacetInterface $other */
    foreach ($already_enabled_facets_on_same_source as $other) {
      if ($other->getUrlAlias() === $url_alias && $other->id() !== $facet->id()) {
        $form_state->setErrorByName('url_alias', $this->t('This alias is already in use for another facet defined on the same source.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Store processor settings.
    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->entity;

    /** @var \Drupal\facets\Processor\ProcessorInterface $processor */
    $processors = $facet->getProcessors(FALSE);
    foreach ($processors as $processor_id => $processor) {
      $form_container_key = $processor instanceof SortProcessorInterface ? 'facet_sorting' : 'facet_settings';
      if (empty($values[$form_container_key][$processor_id]['status'])) {
        $facet->removeProcessor($processor_id);
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
        $processor->submitConfigurationForm($form[$form_container_key][$processor_id]['settings'], $processor_form_state, $facet);
        $new_settings['settings'] = $processor->getConfiguration();
      }
      $facet->addProcessor($new_settings);
    }

    $facet->setWidget($form_state->getValue('widget'), $form_state->getValue('widget_config'));
    $facet->setUrlAlias($form_state->getValue(['facet_settings', 'url_alias']));
    $facet->setWeight((int) $form_state->getValue(['facet_settings', 'weight']));
    $facet->setMinCount((int) $form_state->getValue(['facet_settings', 'min_count']));
    $facet->setOnlyVisibleWhenFacetSourceIsVisible($form_state->getValue(['facet_settings', 'only_visible_when_facet_source_is_visible']));
    $facet->setShowOnlyOneResult($form_state->getValue(['facet_settings', 'show_only_one_result']));

    $empty_behavior_config = [];
    $empty_behavior = $form_state->getValue(['facet_settings', 'empty_behavior']);
    $empty_behavior_config['behavior'] = $empty_behavior;
    if ($empty_behavior == 'text') {
      $empty_behavior_config['text_format'] = $form_state->getValue([
        'facet_settings',
        'empty_behavior_container',
        'empty_behavior_text',
        'format',
      ]);
      $empty_behavior_config['text'] = $form_state->getValue([
        'facet_settings',
        'empty_behavior_container',
        'empty_behavior_text',
        'value',
      ]);
    }
    $facet->setEmptyBehavior($empty_behavior_config);

    $facet->setQueryOperator($form_state->getValue(['facet_settings', 'query_operator']));

    $facet->setHardLimit($form_state->getValue(['facet_settings', 'hard_limit']));

    $facet->setExclude($form_state->getValue(['facet_settings', 'exclude']));
    $facet->setUseHierarchy($form_state->getValue(['facet_settings', 'use_hierarchy']));
    $facet->setExpandHierarchy($form_state->getValue(['facet_settings', 'expand_hierarchy']));
    $facet->setEnableParentWhenChildGetsDisabled($form_state->getValue(['facet_settings', 'enable_parent_when_child_gets_disabled']));
    $facet->set('show_title', $form_state->getValue(['facet_settings', 'show_title'], FALSE));

    $facet->save();
    drupal_set_message($this->t('Facet %name has been updated.', ['%name' => $facet->getName()]));
  }

  /**
   * Handles form submissions for the widget subform.
   */
  public function submitAjaxWidgetConfigForm($form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Handles changes to the selected widgets.
   */
  public function buildAjaxWidgetConfigForm(array $form, FormStateInterface $form_state) {
    return $form['widget_config'];
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
