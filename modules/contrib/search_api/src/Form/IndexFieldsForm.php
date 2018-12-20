<?php

namespace Drupal\search_api\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\search_api\DataType\DataTypePluginManager;
use Drupal\search_api\Processor\ConfigurablePropertyInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\UnsavedConfigurationInterface;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring the fields of a search index.
 */
class IndexFieldsForm extends EntityForm {

  use UnsavedConfigurationFormTrait;

  /**
   * The index for which the fields are configured.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $entity;

  /**
   * The shared temporary storage for unsaved search indexes.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * The data type plugin manager.
   *
   * @var \Drupal\search_api\DataType\DataTypePluginManager
   */
  protected $dataTypePluginManager;

  /**
   * The data type helper.
   *
   * @var \Drupal\search_api\Utility\DataTypeHelperInterface|null
   */
  protected $dataTypeHelper;

  /**
   * The fields helper.
   *
   * @var \Drupal\search_api\Utility\FieldsHelperInterface|null
   */
  protected $fieldsHelper;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs an IndexFieldsForm object.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   *   The factory for shared temporary storages.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\search_api\DataType\DataTypePluginManager $data_type_plugin_manager
   *   The data type plugin manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer to use.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\search_api\Utility\DataTypeHelperInterface $data_type_helper
   *   The data type helper.
   * @param \Drupal\search_api\Utility\FieldsHelperInterface $fields_helper
   *   The fields helper.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(SharedTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, DataTypePluginManager $data_type_plugin_manager, RendererInterface $renderer, DateFormatterInterface $date_formatter, DataTypeHelperInterface $data_type_helper, FieldsHelperInterface $fields_helper, MessengerInterface $messenger) {
    $this->tempStore = $temp_store_factory->get('search_api_index');
    $this->entityTypeManager = $entity_type_manager;
    $this->dataTypePluginManager = $data_type_plugin_manager;
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter;
    $this->dataTypeHelper = $data_type_helper;
    $this->fieldsHelper = $fields_helper;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $temp_store_factory = $container->get('tempstore.shared');
    $entity_type_manager = $container->get('entity_type.manager');
    $data_type_plugin_manager = $container->get('plugin.manager.search_api.data_type');
    $renderer = $container->get('renderer');
    $date_formatter = $container->get('date.formatter');
    $data_type_helper = $container->get('search_api.data_type_helper');
    $fields_helper = $container->get('search_api.fields_helper');
    $messenger = $container->get('messenger');

    return new static($temp_store_factory, $entity_type_manager, $data_type_plugin_manager, $renderer, $date_formatter, $data_type_helper, $fields_helper, $messenger);
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
  public function getFormId() {
    return 'search_api_index_fields';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $index = $this->entity;

    // Do not allow the form to be cached. See
    // \Drupal\views_ui\ViewEditForm::form().
    $form_state->disableCache();

    $this->checkEntityEditable($form, $index, TRUE);

    // Set an appropriate page title.
    $form['#title'] = $this->t('Manage fields for search index %label', ['%label' => $index->label()]);
    $form['#tree'] = TRUE;

    $form['add-field'] = [
      '#type' => 'link',
      '#title' => $this->t('Add fields'),
      '#url' => $this->entity->toUrl('add-fields'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
          'button-action',
          'button--primary',
          'button--small',
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ];

    $form['description']['#markup'] = $this->t('<p>The data type of a field determines how it can be used for searching and filtering. The boost is used to give additional weight to certain fields, for example titles or tags.</p> <p>For information about the data types available for indexing, see the <a href=":url">data types table</a> at the bottom of the page.</p>', [':url' => '#search-api-data-types-table']);

    if ($fields = $index->getFieldsByDatasource(NULL)) {
      $form['_general'] = $this->buildFieldsTable($fields);
      $form['_general']['#title'] = $this->t('General');
    }

    foreach ($index->getDatasources() as $datasource_id => $datasource) {
      $fields = $index->getFieldsByDatasource($datasource_id);
      $form[$datasource_id] = $this->buildFieldsTable($fields);
      $form[$datasource_id]['#title'] = $datasource->label();
    }

    // Build the data type table.
    $instances = $this->dataTypePluginManager->getInstances();
    $fallback_mapping = $this->dataTypeHelper
      ->getDataTypeFallbackMapping($index);

    $data_types = [];
    foreach ($instances as $name => $type) {
      if ($type->isHidden()) {
        continue;
      }
      $data_types[$name] = [
        'label' => $type->label(),
        'description' => $type->getDescription(),
        'fallback' => $type->getFallbackType(),
      ];
    }

    $form['data_type_explanation'] = [
      '#type' => 'details',
      '#id' => 'search-api-data-types-table',
      '#title' => $this->t('Data types'),
      '#description' => $this->t("The data types which can be used for indexing fields in this index. Whether a type is supported depends on the backend of the index's server. If a type is not supported, the fallback type that will be used instead is shown, too."),
      '#theme' => 'search_api_admin_data_type_table',
      '#data_types' => $data_types,
      '#fallback_mapping' => $fallback_mapping,
    ];

    $form['actions'] = $this->actionsElement($form, $form_state);

    return $form;
  }

  /**
   * Builds the form fields for a set of fields.
   *
   * @param \Drupal\search_api\Item\FieldInterface[] $fields
   *   List of fields to display.
   *
   * @return array
   *   The build structure.
   */
  protected function buildFieldsTable(array $fields) {
    $fallback_types = $this->dataTypeHelper
      ->getDataTypeFallbackMapping($this->entity);

    // If one of the unsupported types is actually used by the index, show a
    // warning.
    if ($fallback_types) {
      foreach ($fields as $field) {
        if (isset($fallback_types[$field->getType()])) {
          $args = [':url' => '#search-api-data-types-table'];
          $warning = $this->t("Some of the used data types aren't supported by the server's backend. See the <a href=\":url\">data types table</a> to find out which types are supported.", $args);
          $this->messenger->addWarning($warning);
          break;
        }
      }
    }

    $types = [];
    $fulltext_types = [
      [
        'value' => 'text',
      ],
    ];
    // Add all data types with fallback "text" to fulltext types as well.
    foreach ($this->dataTypePluginManager->getInstances() as $type_id => $type) {
      if (!$type->isHidden()) {
        $types[$type_id] = $type->label();
      }
      if ($type->getFallbackType() == 'text') {
        $fulltext_types[] = [
          'value' => $type_id,
        ];
      }
    }

    $boost_values = [
      '0.0',
      '0.1',
      '0.2',
      '0.3',
      '0.5',
      '0.8',
      '1.0',
      '2.0',
      '3.0',
      '5.0',
      '8.0',
      '13.0',
      '21.0',
    ];
    $boosts = array_combine($boost_values, $boost_values);

    $build = [
      '#type' => 'details',
      '#open' => TRUE,
      '#theme' => 'search_api_admin_fields_table',
      '#parents' => [],
      '#header' => [
        $this->t('Label'),
        $this->t('Machine name'),
        [
          'data' => $this->t('Property path'),
          'class' => [RESPONSIVE_PRIORITY_LOW],
        ],
        $this->t('Type'),
        $this->t('Boost'),
        [
          'data' => $this->t('Operations'),
          'colspan' => 2,
        ],
      ],
    ];

    uasort($fields, [$this->fieldsHelper, 'compareFieldLabels']);
    foreach ($fields as $key => $field) {
      $build['fields'][$key]['#access'] = !$field->isHidden();

      $build['fields'][$key]['title'] = [
        '#type' => 'textfield',
        '#default_value' => $field->getLabel() ? $field->getLabel() : $key,
        '#required' => TRUE,
        '#size' => 40,
      ];
      $build['fields'][$key]['id'] = [
        '#type' => 'textfield',
        '#default_value' => $key,
        '#required' => TRUE,
        '#size' => 35,
      ];
      $build['fields'][$key]['property_path'] = [
        '#markup' => Html::escape($field->getPropertyPath()),
      ];

      if ($field->getDescription()) {
        $build['fields'][$key]['description'] = [
          '#type' => 'value',
          '#value' => $field->getDescription(),
        ];
      }

      $build['fields'][$key]['type'] = [
        '#type' => 'select',
        '#options' => $types,
        '#default_value' => $field->getType(),
      ];
      if ($field->isTypeLocked()) {
        $build['fields'][$key]['type']['#disabled'] = TRUE;
      }

      $build['fields'][$key]['boost'] = [
        '#type' => 'select',
        '#options' => $boosts,
        '#default_value' => sprintf('%.1f', $field->getBoost()),
        '#states' => [
          'visible' => [
            ':input[name="fields[' . $key . '][type]"]' => $fulltext_types,
          ],
        ],
      ];

      $route_parameters = [
        'search_api_index' => $this->entity->id(),
        'field_id' => $key,
      ];
      // Provide some invisible markup as default, if a link is missing, so we
      // don't break the table structure. (theme_search_api_admin_fields_table()
      // does not add empty cells.)
      $build['fields'][$key]['edit']['#markup'] = '<span></span>';
      try {
        if ($field->getDataDefinition() instanceof ConfigurablePropertyInterface) {
          $build['fields'][$key]['edit'] = [
            '#type' => 'link',
            '#title' => $this->t('Edit'),
            '#url' => Url::fromRoute('entity.search_api_index.field_config', $route_parameters),
            '#attributes' => [
              'class' => [
                'use-ajax',
              ],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 700,
              ]),
            ],
          ];
        }
      }
      catch (SearchApiException $e) {
        // Could not retrieve data definition. Since this almost certainly means
        // that the property isn't configurable, we can just ignore it here.
      }
      $build['fields'][$key]['remove']['#markup'] = '<span></span>';
      if (!$field->isIndexedLocked()) {
        $build['fields'][$key]['remove'] = [
          '#type' => 'link',
          '#title' => $this->t('Remove'),
          '#url' => Url::fromRoute('entity.search_api_index.remove_field', $route_parameters),
          '#attributes' => [
            'class' => ['use-ajax'],
          ],
        ];
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save changes'),
        '#button_type' => 'primary',
        '#submit' => ['::submitForm', '::save'],
      ],
    ];
    if ($this->entity instanceof UnsavedConfigurationInterface && $this->entity->hasChanges()) {
      $actions['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#button_type' => 'danger',
        '#submit' => ['::cancel'],
      ];
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $field_values = $form_state->getValues()['fields'];
    $new_ids = [];

    foreach ($field_values as $field_id => $field) {
      $new_id = $field['id'];
      $new_ids[$new_id][] = $field_id;

      // Check for reserved and other illegal field IDs.
      if ($this->fieldsHelper->isFieldIdReserved($new_id)) {
        $args = [
          '%field_id' => $new_id,
        ];
        $error = $this->t('%field_id is a reserved value and cannot be used as the machine name of a normal field.', $args);
        $form_state->setErrorByName('fields][' . $field_id . '][id', $error);
      }
      elseif (preg_match('/^_+$/', $new_id)) {
        $error = $this->t('Field IDs have to contain non-underscore characters.');
        $form_state->setErrorByName('fields][' . $field_id . '][id', $error);
      }
      elseif (preg_match('/[^a-z0-9_]/', $new_id)) {
        $error = $this->t('Field IDs must contain only lowercase letters, numbers and underscores.');
        $form_state->setErrorByName('fields][' . $field_id . '][id', $error);
      }
    }

    // Identify duplicates.
    $has_duplicates = function (array $old_ids) {
      return count($old_ids) > 1;
    };
    foreach (array_filter($new_ids, $has_duplicates) as $new_id => $old_ids) {
      $args['%field_id'] = $new_id;
      $error = $this->t('Field ID %field_id is used multiple times. Field IDs must be unique.', $args);
      foreach ($old_ids as $field_id) {
        $form_state->setErrorByName('fields][' . $field_id . '][id', $error);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $index = $this->entity;

    // Store the fields configuration.
    $fields = $index->getFields();
    $field_values = $form_state->getValue('fields', []);
    $new_fields = [];
    foreach ($field_values as $field_id => $new_settings) {
      if (!isset($fields[$field_id])) {
        $args['%field_id'] = $field_id;
        $this->messenger->addWarning($this->t('The field with ID %field_id does not exist anymore.', $args));
        continue;
      }
      $field = $fields[$field_id];
      $field->setLabel($new_settings['title']);
      $field->setType($new_settings['type']);
      $field->setBoost($new_settings['boost']);
      $field->setFieldIdentifier($new_settings['id']);

      $new_fields[$new_settings['id']] = $field;
    }

    $index->setFields($new_fields);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $index = $this->entity;
    if ($index instanceof UnsavedConfigurationInterface) {
      $index->savePermanent();
    }
    else {
      $index->save();
    }

    $this->messenger->addStatus($this->t('The changes were successfully saved.'));
    if ($this->entity->isReindexing()) {
      $url = $this->entity->toUrl('canonical');
      $this->messenger->addStatus($this->t('All content was scheduled for <a href=":url">reindexing</a> so the new settings can take effect.', [':url' => $url->toString()]));
    }

    return SAVED_UPDATED;
  }

  /**
   * Cancels the editing of the index's fields.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    if ($this->entity instanceof UnsavedConfigurationInterface && $this->entity->hasChanges()) {
      $this->entity->discardChanges();
    }

    $form_state->setRedirectUrl($this->entity->toUrl('canonical'));
  }

}
