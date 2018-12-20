<?php

namespace Drupal\search_api\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\Core\Url;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ConfigurablePropertyInterface;
use Drupal\search_api\Processor\ProcessorPropertyInterface;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding fields to a search index.
 */
class IndexAddFieldsForm extends EntityForm {

  use UnsavedConfigurationFormTrait;

  /**
   * The fields helper.
   *
   * @var \Drupal\search_api\Utility\FieldsHelperInterface
   */
  protected $fieldsHelper;

  /**
   * The data type helper.
   *
   * @var \Drupal\search_api\Utility\DataTypeHelperInterface|null
   */
  protected $dataTypeHelper;

  /**
   * The index for which the fields are configured.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $entity;

  /**
   * The parameters of the current page request.
   *
   * @var array
   */
  protected $parameters;

  /**
   * List of types that failed to map to a Search API type.
   *
   * The unknown types are the keys and map to arrays of fields that were
   * ignored because they are of this type.
   *
   * @var string[][]
   */
  protected $unmappedFields = [];

  /**
   * The "id" attribute of the generated form.
   *
   * @var string
   */
  protected $formIdAttribute;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs an IndexAddFieldsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\search_api\Utility\FieldsHelperInterface $fields_helper
   *   The fields helper.
   * @param \Drupal\search_api\Utility\DataTypeHelperInterface $data_type_helper
   *   The data type helper.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer to use.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param array $parameters
   *   The parameters for this page request.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FieldsHelperInterface $fields_helper, DataTypeHelperInterface $data_type_helper, RendererInterface $renderer, DateFormatterInterface $date_formatter, MessengerInterface $messenger, array $parameters) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldsHelper = $fields_helper;
    $this->dataTypeHelper = $data_type_helper;
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter;
    $this->messenger = $messenger;
    $this->parameters = $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    $fields_helper = $container->get('search_api.fields_helper');
    $data_type_helper = $container->get('search_api.data_type_helper');
    $renderer = $container->get('renderer');
    $date_formatter = $container->get('date.formatter');
    $request_stack = $container->get('request_stack');
    $messenger = $container->get('messenger');
    $parameters = $request_stack->getCurrentRequest()->query->all();

    return new static($entity_type_manager, $fields_helper, $data_type_helper, $renderer, $date_formatter, $messenger, $parameters);
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
    return 'search_api_index_add_fields';
  }

  /**
   * Retrieves a single page request parameter.
   *
   * @param string $name
   *   The name of the parameter.
   * @param string|null $default
   *   The value to return if the parameter isn't present.
   *
   * @return string|null
   *   The parameter value.
   */
  public function getParameter($name, $default = NULL) {
    return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $index = $this->entity;

    // Do not allow the form to be cached. See
    // \Drupal\views_ui\ViewEditForm::form().
    $form_state->disableCache();

    $this->checkEntityEditable($form, $index);

    $args['%index'] = $index->label();
    $form['#title'] = $this->t('Add fields to index %index', $args);

    $this->formIdAttribute = Html::getUniqueId($this->getFormId());
    $form['#id'] = $this->formIdAttribute;

    $form['messages'] = [
      '#type' => 'status_messages',
    ];

    $datasources = [
      '' => NULL,
    ];
    $datasources += $this->entity->getDatasources();
    foreach ($datasources as $datasource_id => $datasource) {
      $item = $this->getDatasourceListItem($datasource);
      if ($item) {
        $form['datasources']['datasource_' . $datasource_id] = $item;
      }
    }

    $form['actions'] = $this->actionsElement($form, $form_state);

    // Log any unmapped types that were encountered.
    if ($this->unmappedFields) {
      $unmapped_fields = [];
      foreach ($this->unmappedFields as $type => $fields) {
        foreach ($fields as $field) {
          $unmapped_fields[] = new FormattableMarkup('@field (type "@type")', [
            '@field' => $field,
            '@type' => $type,
          ]);
        }
      }
      $form['unmapped_types'] = [
        '#type' => 'details',
        '#title' => $this->t('Skipped fields'),
        'fields' => [
          '#theme' => 'item_list',
          '#items' => $unmapped_fields,
          '#prefix' => $this->t('The following fields cannot be indexed since there is no type mapping for them:'),
          '#suffix' => $this->t("If you think one of these fields should be available for indexing, please report this in the module's <a href=':url'>issue queue</a>. (Make sure to first search for an existing issue for this field.) Please note that entity-valued fields generally can be indexed by either indexing their parent reference field, or their child entity ID field.", [':url' => Url::fromUri('https://www.drupal.org/project/issues/search_api')->toString()]),
        ],
      ];
    }

    return $form;
  }

  /**
   * Creates a list item for one datasource.
   *
   * @param \Drupal\search_api\Datasource\DatasourceInterface|null $datasource
   *   The datasource, or NULL for general properties.
   *
   * @return array
   *   A render array representing the given datasource and, possibly, its
   *   attached properties.
   */
  protected function getDatasourceListItem(DatasourceInterface $datasource = NULL) {
    $datasource_id = $datasource ? $datasource->getPluginId() : NULL;
    $datasource_id_param = $datasource_id ?: '';
    $properties = $this->entity->getPropertyDefinitions($datasource_id);
    if ($properties) {
      $active_property_path = '';
      $active_datasource = $this->getParameter('datasource');
      if ($active_datasource !== NULL && $active_datasource == $datasource_id_param) {
        $active_property_path = $this->getParameter('property_path', '');
      }

      $base_url = $this->entity->toUrl('add-fields');
      $base_url->setOption('query', ['datasource' => $datasource_id_param]);

      $item = $this->getPropertiesList($properties, $active_property_path, $base_url, $datasource_id);
      $item['#title'] = $datasource ? $datasource->label() : $this->t('General');
      return $item;
    }

    return NULL;
  }

  /**
   * Creates an items list for the given properties.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface[] $properties
   *   The property definitions, keyed by their property names.
   * @param string $active_property_path
   *   The relative property path to the active property.
   * @param \Drupal\Core\Url $base_url
   *   The base URL to which property path parameters should be added for
   *   the navigation links.
   * @param string|null $datasource_id
   *   The datasource ID of the listed properties, or NULL for
   *   datasource-independent properties.
   * @param string $parent_path
   *   (optional) The common property path prefix of the given properties.
   * @param string $label_prefix
   *   (optional) The prefix to use for the labels of created fields.
   *
   * @return array
   *   A render array representing the given properties and, possibly, nested
   *   properties.
   */
  protected function getPropertiesList(array $properties, $active_property_path, Url $base_url, $datasource_id, $parent_path = '', $label_prefix = '') {
    $list = [];

    $active_item = '';
    if ($active_property_path) {
      list($active_item, $active_property_path) = explode(':', $active_property_path, 2) + [1 => ''];
    }

    $type_mapping = $this->dataTypeHelper->getFieldTypeMapping();

    $query_base = $base_url->getOption('query');
    foreach ($properties as $key => $property) {
      if ($property instanceof ProcessorPropertyInterface && $property->isHidden()) {
        continue;
      }
      $this_path = $parent_path ? $parent_path . ':' : '';
      $this_path .= $key;

      $label = $property->getLabel();
      $property = $this->fieldsHelper->getInnerProperty($property);

      $can_be_indexed = TRUE;
      $nested_properties = [];
      $parent_child_type = NULL;
      if ($property instanceof ComplexDataDefinitionInterface) {
        $can_be_indexed = FALSE;
        $nested_properties = $this->fieldsHelper->getNestedProperties($property);
        $main_property = $property->getMainPropertyName();
        if ($main_property && isset($nested_properties[$main_property])) {
          $parent_child_type = $property->getDataType() . '.';
          $property = $nested_properties[$main_property];
          $parent_child_type .= $property->getDataType();
          unset($nested_properties[$main_property]);
          $can_be_indexed = TRUE;
        }

        // Don't add the additional "entity" property for entity reference
        // fields which don't target a content entity type.
        if (isset($nested_properties['entity'])) {
          $entity_property = $nested_properties['entity'];
          if ($entity_property instanceof DataReferenceDefinitionInterface) {
            $target = $entity_property->getTargetDefinition();
            if ($target instanceof EntityDataDefinitionInterface) {
              if (!$this->fieldsHelper->isContentEntityType($target->getEntityTypeId())) {
                unset($nested_properties['entity']);
              }
            }
          }
        }
      }

      // Don't allow indexing of properties with unmapped types. Also, prefer
      // a "parent.child" type mapping (taking into account the parent property
      // for, for example, text fields).
      $type = $property->getDataType();
      if ($parent_child_type && !empty($type_mapping[$parent_child_type])) {
        $type = $parent_child_type;
      }
      elseif (empty($type_mapping[$type])) {
        // Remember the type only if it was not explicitly mapped to FALSE.
        if (!isset($type_mapping[$type])) {
          $this->unmappedFields[$type][] = $label_prefix . $label;
        }
        $can_be_indexed = FALSE;
      }

      // If the property can neither be expanded nor indexed, just skip it.
      if (!($nested_properties || $can_be_indexed)) {
        continue;
      }

      $item = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['container-inline'],
        ],
      ];

      $nested_list = [];
      if ($nested_properties) {
        if ($key == $active_item) {
          $link_url = clone $base_url;
          $query_base['property_path'] = $parent_path;
          $link_url->setOption('query', $query_base);
          $item['expand_link'] = [
            '#type' => 'link',
            '#title' => '(-) ',
            '#url' => $link_url,
            '#ajax' => [
              'wrapper' => $this->formIdAttribute,
            ],
          ];

          $nested_list = $this->getPropertiesList($nested_properties, $active_property_path, $base_url, $datasource_id, $this_path, $label_prefix . $label . ' » ');
        }
        else {
          $link_url = clone $base_url;
          $query_base['property_path'] = $this_path;
          $link_url->setOption('query', $query_base);
          $item['expand_link'] = [
            '#type' => 'link',
            '#title' => '(+) ',
            '#url' => $link_url,
            '#ajax' => [
              'wrapper' => $this->formIdAttribute,
            ],
          ];
        }
      }

      $label_markup = Html::escape($label);
      $escaped_path = Html::escape($this_path);
      $label_markup = "$label_markup <small>(<code>$escaped_path</code>)</small>";
      $item['label']['#markup'] = $label_markup . ' ';

      if ($can_be_indexed) {
        $item['add'] = [
          '#type' => 'submit',
          '#name' => Utility::createCombinedId($datasource_id, $this_path),
          '#value' => $this->t('Add'),
          '#submit' => ['::addField', '::save'],
          '#property' => $property,
          '#prefixed_label' => $label_prefix . $label,
          '#data_type' => $type_mapping[$type],
          '#ajax' => [
            'wrapper' => $this->formIdAttribute,
          ],
        ];
      }

      if ($nested_list) {
        $item['properties'] = $nested_list;
      }

      $list[$key] = $item;
    }

    if ($list) {
      uasort($list, [static::class, 'compareFieldLabels']);
      $list['#theme'] = 'search_api_form_item_list';
    }

    return $list;
  }

  /**
   * Compares labels of property render arrays.
   *
   * Used as an uasort() callback in
   * \Drupal\search_api\Form\IndexAddFieldsForm::getPropertiesList().
   *
   * @param array $a
   *   First property render array.
   * @param array $b
   *   Second property render array.
   *
   * @return int
   *   -1, 0 or 1 if the first array should be considered, respectively, less
   *   than, equal to or greater than the second.
   */
  public static function compareFieldLabels(array $a, array $b) {
    $a_title = (string) $a['label']['#markup'];
    $b_title = (string) $b['label']['#markup'];

    return strnatcasecmp($a_title, $b_title);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [
      'done' => [
        '#type' => 'link',
        '#title' => $this->t('Done'),
        '#url' => $this->entity->toUrl('fields'),
        '#attributes' => [
          'class' => ['button'],
        ],
      ],
    ];
  }

  /**
   * Form submission handler for adding a new field to the index.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addField(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    if (!$button) {
      return;
    }

    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $property */
    $property = $button['#property'];

    list($datasource_id, $property_path) = Utility::splitCombinedId($button['#name']);
    $field = $this->fieldsHelper->createFieldFromProperty($this->entity, $property, $datasource_id, $property_path, NULL, $button['#data_type']);
    $field->setLabel($button['#prefixed_label']);
    $this->entity->addField($field);

    if ($property instanceof ConfigurablePropertyInterface) {
      $parameters = [
        'search_api_index' => $this->entity->id(),
        'field_id' => $field->getFieldIdentifier(),
      ];
      $options = [];
      $route = $this->getRequest()->attributes->get('_route');
      if ($route === 'entity.search_api_index.add_fields_ajax') {
        $options['query'] = [
          MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax',
          'modal_redirect' => 1,
        ];
      }
      $form_state->setRedirect('entity.search_api_index.field_config', $parameters, $options);
    }

    $args['%label'] = $field->getLabel();
    $this->messenger->addStatus($this->t('Field %label was added to the index.', $args));
  }

}
