<?php

namespace Drupal\paragraphs\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Element;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\Plugin\EntityReferenceSelection\ParagraphSelection;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Plugin implementation of the 'entity_reference_revisions paragraphs' widget.
 *
 * @FieldWidget(
 *   id = "paragraphs",
 *   label = @Translation("Paragraphs EXPERIMENTAL"),
 *   description = @Translation("An experimental paragraphs inline form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsWidget extends WidgetBase {

  /**
   * Action position is in the add paragraphs place.
   */
  const ACTION_POSITION_BASE = 1;

  /**
   * Action position is in the table header section.
   */
  const ACTION_POSITION_HEADER = 2;

  /**
   * Action position is in the actions section of the widget.
   */
  const ACTION_POSITION_ACTIONS = 3;

  /**
   * Indicates whether the current widget instance is in translation.
   *
   * @var bool
   */
  protected $isTranslating;

  /**
   * Id to name ajax buttons that includes field parents and field name.
   *
   * @var string
   */
  protected $fieldIdPrefix;

  /**
   * Wrapper id to identify the paragraphs.
   *
   * @var string
   */
  protected $fieldWrapperId;

  /**
   * Number of paragraphs item on form.
   *
   * @var int
   */
  protected $realItemCount;

  /**
   * Parents for the current paragraph.
   *
   * @var array
   */
  protected $fieldParents;

  /**
   * Accessible paragraphs types.
   *
   * @var array
   */
  protected $accessOptions = NULL;

  /**
   * Constructs a ParagraphsWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    // Modify settings that were set before https://www.drupal.org/node/2896115.
    if(isset($settings['edit_mode']) && $settings['edit_mode'] === 'preview') {
      $settings['edit_mode'] = 'closed';
      $settings['closed_mode'] = 'preview';
    }

    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'title' => t('Paragraph'),
      'title_plural' => t('Paragraphs'),
      'edit_mode' => 'open',
      'closed_mode' => 'summary',
      'autocollapse' => 'none',
      'add_mode' => 'dropdown',
      'form_display_mode' => 'default',
      'default_paragraph_type' => '',
      'features' => ['duplicate' => 'duplicate', 'collapse_edit_all' => 'collapse_edit_all'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = array();

    $elements['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Paragraph Title'),
      '#description' => $this->t('Label to appear as title on the button as "Add new [title]", this label is translatable'),
      '#default_value' => $this->getSetting('title'),
      '#required' => TRUE,
    );

    $elements['title_plural'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Plural Paragraph Title'),
      '#description' => $this->t('Title in its plural form.'),
      '#default_value' => $this->getSetting('title_plural'),
      '#required' => TRUE,
    );

    $elements['edit_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Edit mode'),
      '#description' => $this->t('The mode the paragraph is in by default.'),
      '#options' => $this->getSettingOptions('edit_mode'),
      '#default_value' => $this->getSetting('edit_mode'),
      '#required' => TRUE,
    );

    $elements['closed_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Closed mode'),
      '#description' => $this->t('How to display the paragraphs, when the widget is closed. Preview will render the paragraph in the preview view mode and typically needs a custom admin theme.'),
      '#options' => $this->getSettingOptions('closed_mode'),
      '#default_value' => $this->getSetting('closed_mode'),
      '#required' => TRUE,
    ];

    $elements['autocollapse'] = [
      '#type' => 'select',
      '#title' => $this->t('Autocollapse'),
      '#description' => $this->t('When a paragraph is opened for editing, close others.'),
      '#options' => $this->getSettingOptions('autocollapse'),
      '#default_value' => $this->getSetting('autocollapse'),
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          'select[name="fields[' . $this->fieldDefinition->getName() .  '][settings_edit_form][settings][edit_mode]"]' => ['value' => 'closed'],
        ],
      ],
    ];

    $elements['add_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Add mode'),
      '#description' => $this->t('The way to add new Paragraphs.'),
      '#options' => $this->getSettingOptions('add_mode'),
      '#default_value' => $this->getSetting('add_mode'),
      '#required' => TRUE,
    );

    $elements['form_display_mode'] = array(
      '#type' => 'select',
      '#options' => \Drupal::service('entity_display.repository')->getFormModeOptions($this->getFieldSetting('target_type')),
      '#description' => $this->t('The form display mode to use when rendering the paragraph form.'),
      '#title' => $this->t('Form display mode'),
      '#default_value' => $this->getSetting('form_display_mode'),
      '#required' => TRUE,
    );

    $options  = [];
    foreach ($this->getAllowedTypes() as $key => $bundle) {
      $options[$key] = $bundle['label'];
    }

    $elements['default_paragraph_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default paragraph type'),
      '#empty_value' => '_none',
      '#default_value' => $this->getDefaultParagraphTypeMachineName(),
      '#options' => $options,
      '#description' => $this->t('When creating a new host entity, a paragraph of this type is added.'),
    ];

    $elements['features'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable widget features'),
      '#options' => $this->getSettingOptions('features'),
      '#default_value' => $this->getSetting('features'),
      '#description' => $this->t('When editing, available as action. "Add above" only works in add mode "Modal form"'),
      '#multiple' => TRUE,
    ];

    return $elements;
  }

  /**
   * Returns select options for a plugin setting.
   *
   * This is done to allow
   * \Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget::settingsSummary()
   * to access option labels. Not all plugin setting are available.
   *
   * @param string $setting_name
   *   The name of the widget setting. Supported settings:
   *   - "edit_mode"
   *   - "closed_mode"
   *   - "autocollapse"
   *   - "add_mode"
   *
   * @return array|null
   *   An array of setting option usable as a value for a "#options" key.
   */
  protected function getSettingOptions($setting_name) {
    switch($setting_name) {
      case 'edit_mode':
        $options = [
          'open' => $this->t('Open'),
          'closed' => $this->t('Closed'),
        ];
        break;
      case 'closed_mode':
        $options = [
          'summary' => $this->t('Summary'),
          'preview' => $this->t('Preview'),
        ];
        break;
      case 'autocollapse':
        $options = [
          'none' => $this->t('None'),
          'all' => $this->t('All'),
        ];
        break;
      case 'add_mode':
        $options = [
          'select' => $this->t('Select list'),
          'button' => $this->t('Buttons'),
          'dropdown' => $this->t('Dropdown button'),
          'modal' => $this->t('Modal form'),
        ];
        break;
      case 'features':
        $options = [
          'duplicate' => $this->t('Duplicate'),
          'collapse_edit_all' => $this->t('Collapse / Edit all'),
          // The "Add above" feature will be completely injected clientside,
          // whenever this option is enabled in the widget configuration.
          // @see Drupal.behaviors.paragraphsAddAboveButton
          'add_above' => $this->t('Add above'),
        ];
        break;
    }

    return isset($options) ? $options : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = $this->t('Title: @title', ['@title' => $this->getSetting('title')]);
    $summary[] = $this->t('Plural title: @title_plural', [
      '@title_plural' => $this->getSetting('title_plural')
    ]);

    $edit_mode = $this->getSettingOptions('edit_mode')[$this->getSetting('edit_mode')];
    $closed_mode = $this->getSettingOptions('closed_mode')[$this->getSetting('closed_mode')];
    $add_mode = $this->getSettingOptions('add_mode')[$this->getSetting('add_mode')];

    $summary[] = $this->t('Edit mode: @edit_mode', ['@edit_mode' => $edit_mode]);
    $summary[] = $this->t('Closed mode: @closed_mode', ['@closed_mode' => $closed_mode]);
    if ($this->getSetting('edit_mode') == 'closed') {
      $autocollapse = $this->getSettingOptions('autocollapse')[$this->getSetting('autocollapse')];
      $summary[] = $this->t('Autocollapse: @autocollapse', ['@autocollapse' => $autocollapse]);
    }
    $summary[] = $this->t('Add mode: @add_mode', ['@add_mode' => $add_mode]);

    $summary[] = $this->t('Form display mode: @form_display_mode', [
      '@form_display_mode' => $this->getSetting('form_display_mode')
    ]);
    if ($this->getDefaultParagraphTypeLabelName() !== NULL) {
      $summary[] = $this->t('Default paragraph type: @default_paragraph_type', [
        '@default_paragraph_type' => $this->getDefaultParagraphTypeLabelName()
      ]);
    }
    $features_labels = array_intersect_key($this->getSettingOptions('features'), array_filter($this->getSetting('features')));
    if (!empty($features_labels)) {
      $summary[] = $this->t('Features: @features', ['@features' => implode($features_labels, ', ')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\content_translation\Controller\ContentTranslationController::prepareTranslation()
   *   Uses a similar approach to populate a new translation.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $element['#field_parents'];

    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraphs_entity */
    $paragraphs_entity = NULL;
    $host = $items->getEntity();
    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    $entity_type_manager = \Drupal::entityTypeManager();
    $target_type = $this->getFieldSetting('target_type');

    $item_mode = isset($widget_state['paragraphs'][$delta]['mode']) ? $widget_state['paragraphs'][$delta]['mode'] : 'edit';
    $default_edit_mode = $this->getSetting('edit_mode');

    $closed_mode_setting = isset($widget_state['closed_mode']) ? $widget_state['closed_mode'] : $this->getSetting('closed_mode');
    $autocollapse_setting = isset($widget_state['autocollapse']) ? $widget_state['autocollapse'] : $this->getSetting('autocollapse');

    $show_must_be_saved_warning = !empty($widget_state['paragraphs'][$delta]['show_warning']);

    if (isset($widget_state['paragraphs'][$delta]['entity'])) {
      $paragraphs_entity = $widget_state['paragraphs'][$delta]['entity'];
    }
    elseif (isset($items[$delta]->entity)) {
      $paragraphs_entity = $items[$delta]->entity;

      // We don't have a widget state yet, get from selector settings.
      if (!isset($widget_state['paragraphs'][$delta]['mode'])) {

        if ($default_edit_mode == 'open') {
          $item_mode = 'edit';
        }
        elseif ($default_edit_mode == 'closed') {
          $item_mode = 'closed';
        }
      }
    }
    elseif (isset($widget_state['selected_bundle'])) {

      $entity_type = $entity_type_manager->getDefinition($target_type);
      $bundle_key = $entity_type->getKey('bundle');

      $paragraphs_entity = $entity_type_manager->getStorage($target_type)->create(array(
        $bundle_key => $widget_state['selected_bundle'],
      ));
      $paragraphs_entity->setParentEntity($host, $field_name);

      $item_mode = 'edit';
    }

    if ($paragraphs_entity) {
      // Detect if we are translating.
      $this->initIsTranslating($form_state, $host);
      $langcode = $form_state->get('langcode');

      if (!$this->isTranslating) {
        // Set the langcode if we are not translating.
        $langcode_key = $paragraphs_entity->getEntityType()->getKey('langcode');
        if ($paragraphs_entity->get($langcode_key)->value != $langcode) {
          // If a translation in the given language already exists, switch to
          // that. If there is none yet, update the language.
          if ($paragraphs_entity->hasTranslation($langcode)) {
            $paragraphs_entity = $paragraphs_entity->getTranslation($langcode);
          }
          else {
            $paragraphs_entity->set($langcode_key, $langcode);
          }
        }
      }
      else {
        // Add translation if missing for the target language.
        if (!$paragraphs_entity->hasTranslation($langcode)) {
          // Get the selected translation of the paragraph entity.
          $entity_langcode = $paragraphs_entity->language()->getId();
          $source = $form_state->get(['content_translation', 'source']);
          $source_langcode = $source ? $source->getId() : $entity_langcode;
          // Make sure the source language version is used if available. It is a
          // the host and fetching the translation without this check could lead
          // valid scenario to have no paragraphs items in the source version of
          // to an exception.
          if ($paragraphs_entity->hasTranslation($source_langcode)) {
            $paragraphs_entity = $paragraphs_entity->getTranslation($source_langcode);
          }
          // The paragraphs entity has no content translation source field if
          // no paragraph entity field is translatable, even if the host is.
          if ($paragraphs_entity->hasField('content_translation_source')) {
            // Initialise the translation with source language values.
            $paragraphs_entity->addTranslation($langcode, $paragraphs_entity->toArray());
            $translation = $paragraphs_entity->getTranslation($langcode);
            $manager = \Drupal::service('content_translation.manager');
            $manager->getTranslationMetadata($translation)->setSource($paragraphs_entity->language()->getId());
          }
        }
        // If any paragraphs type is translatable do not switch.
        if ($paragraphs_entity->hasField('content_translation_source')) {
          // Switch the paragraph to the translation.
          $paragraphs_entity = $paragraphs_entity->getTranslation($langcode);
        }
      }

      $element_parents = $parents;
      $element_parents[] = $field_name;
      $element_parents[] = $delta;
      $element_parents[] = 'subform';

      $id_prefix = implode('-', array_merge($parents, array($field_name, $delta)));
      $wrapper_id = Html::getUniqueId($id_prefix . '-item-wrapper');

      $element += array(
        '#type' => 'container',
        '#element_validate' => array(array($this, 'elementValidate')),
        '#paragraph_type' => $paragraphs_entity->bundle(),
        'subform' => array(
          '#type' => 'container',
          '#parents' => $element_parents,
        ),
      );

      $element['#prefix'] = '<div id="' . $wrapper_id . '">';
      $element['#suffix'] = '</div>';

      // Create top section structure with all needed subsections.
      $element['top'] = [
        '#type' => 'container',
        '#weight' => -1000,
        '#attributes' => [
          'class' => [
            'paragraph-top',
            // Add a flag to indicate if the add_above feature is enabled and
            // should be injected client-side.
            $this->isFeatureEnabled('add_above') ? 'add-above-on' : 'add-above-off',
          ],
        ],
        // Section for paragraph type information.
        'type' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['paragraph-type']],
        ],
        // Section for info icons.
        'icons' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['paragraph-info']],
        ],
        'summary' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['paragraph-summary']],
        ],
        // Paragraphs actions element for actions and dropdown actions.
        'actions' => [
          '#type' => 'paragraphs_actions',
        ],
      ];
      // Holds information items.
      $info = [];

      $item_bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($target_type);
      if (isset($item_bundles[$paragraphs_entity->bundle()])) {
        $bundle_info = $item_bundles[$paragraphs_entity->bundle()];

        $element['top']['type']['label'] = ['#markup' => $bundle_info['label']];

        // Type icon and label bundle.
        if ($icon_url = $paragraphs_entity->type->entity->getIconUrl()) {
          $element['top']['type']['icon'] = [
            '#theme' => 'image',
            '#uri' => $icon_url,
            '#attributes' => [
              'class' => ['paragraph-type-icon'],
              'title' => $bundle_info['label'],
            ],
            '#weight' => 0,
            // We set inline height and width so icon don't resize on first load
            // while CSS is still not loaded.
            '#height' => 16,
            '#width' => 16,
          ];
        }
        $element['top']['type']['label'] = [
          '#markup' => '<span class="paragraph-type-label">' . $bundle_info['label'] . '</span>',
          '#weight' => 1,
        ];

        // Widget actions.
        $widget_actions = [
          'actions' => [],
          'dropdown_actions' => [],
        ];

        $widget_actions['dropdown_actions']['duplicate_button'] = [
          '#type' => 'submit',
          '#value' => $this->t('Duplicate'),
          '#name' => $id_prefix . '_duplicate',
          '#weight' => 502,
          '#submit' => [[get_class($this), 'duplicateSubmit']],
          '#limit_validation_errors' => [array_merge($parents, [$field_name, $delta])],
          '#delta' => $delta,
          '#ajax' => [
            'callback' => [get_class($this), 'itemAjax'],
            'wrapper' => $widget_state['ajax_wrapper_id'],
          ],
          '#access' => $this->duplicateButtonAccess($paragraphs_entity),
        ];

        if ($item_mode != 'remove') {
          $widget_actions['dropdown_actions']['remove_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => $id_prefix . '_remove',
            '#weight' => 501,
            '#submit' => [[get_class($this), 'paragraphsItemSubmit']],
            // Ignore all validation errors because deleting invalid paragraphs
            // is allowed.
            '#limit_validation_errors' => [],
            '#delta' => $delta,
            '#ajax' => [
              'callback' => array(get_class($this), 'itemAjax'),
              'wrapper' => $widget_state['ajax_wrapper_id'],
            ],
            '#access' => $this->removeButtonAccess($paragraphs_entity),
            '#paragraphs_mode' => 'remove',
          ];
        }

        if ($item_mode == 'edit') {
          if (isset($paragraphs_entity)) {
            $widget_actions['actions']['collapse_button'] = [
              '#value' => $this->t('Collapse'),
              '#name' => $id_prefix . '_collapse',
              '#weight' => 1,
              '#submit' => [[get_class($this), 'paragraphsItemSubmit']],
              '#limit_validation_errors' => [array_merge($parents, [$field_name, $delta])],
              '#delta' => $delta,
              '#ajax' => [
                'callback' => [get_class($this), 'itemAjax'],
                'wrapper' => $widget_state['ajax_wrapper_id'],
              ],
              '#access' => $paragraphs_entity->access('update'),
              '#paragraphs_mode' => 'closed',
              '#paragraphs_show_warning' => TRUE,
              '#attributes' => [
                'class' => ['paragraphs-icon-button', 'paragraphs-icon-button-collapse'],
                'title' => $this->t('Collapse'),
              ],
            ];
          }
        }
        else {
          $widget_actions['actions']['edit_button'] = $this->expandButton([
            '#type' => 'submit',
            '#value' => $this->t('Edit'),
            '#name' => $id_prefix . '_edit',
            '#weight' => 1,
            '#submit' => [[get_class($this), 'paragraphsItemSubmit']],
            '#limit_validation_errors' => [
              array_merge($parents, [$field_name, $delta]),
            ],
            '#delta' => $delta,
            '#ajax' => [
              'callback' => [get_class($this), 'itemAjax'],
              'wrapper' => $widget_state['ajax_wrapper_id'],
            ],
            '#access' => $paragraphs_entity->access('update'),
            '#paragraphs_mode' => 'edit',
            '#attributes' => [
              'class' => ['paragraphs-icon-button', 'paragraphs-icon-button-edit'],
              'title' => $this->t('Edit'),
            ],
          ]);

          if ($show_must_be_saved_warning && $paragraphs_entity->isChanged()) {
            $info['changed'] = [
              '#theme' => 'paragraphs_info_icon',
              '#message' => $this->t('You have unsaved changes on this @title item.', ['@title' => $this->getSetting('title')]),
              '#icon' => 'changed',
            ];
          }

          if (!$paragraphs_entity->access('view')) {
            $info['preview'] = [
              '#theme' => 'paragraphs_info_icon',
              '#message' => $this->t('You are not allowed to view this @title.', array('@title' => $this->getSetting('title'))),
              '#icon' => 'view',
            ];
          }
        }

        // If update is disabled we will show lock icon in actions section.
        if (!$paragraphs_entity->access('update')) {
          $widget_actions['actions']['edit_disabled'] = [
            '#theme' => 'paragraphs_info_icon',
            '#message' => $this->t('You are not allowed to edit or remove this @title.', ['@title' => $this->getSetting('title')]),
            '#icon' => 'lock',
            '#weight' => 1,
          ];
        }

        if (!$paragraphs_entity->access('update') && $paragraphs_entity->access('delete')) {
          $info['edit'] = [
            '#theme' => 'paragraphs_info_icon',
            '#message' => $this->t('You are not allowed to edit this @title.', ['@title' => $this->getSetting('title')]),
            '#icon' => 'edit-disabled',
          ];
        }
        elseif (!$paragraphs_entity->access('delete') && $paragraphs_entity->access('update')) {
          $info['remove'] = [
            '#theme' => 'paragraphs_info_icon',
            '#message' => $this->t('You are not allowed to remove this @title.', ['@title' => $this->getSetting('title')]),
            '#icon' => 'delete-disabled',
          ];
        }

        $context = [
          'form' => $form,
          'widget' => self::getWidgetState($parents, $field_name, $form_state, $widget_state),
          'items' => $items,
          'delta' => $delta,
          'element' => $element,
          'form_state' => $form_state,
          'paragraphs_entity' => $paragraphs_entity,
          'is_translating' => $this->isTranslating,
          'allow_reference_changes' => $this->allowReferenceChanges(),
        ];

        // Allow modules to alter widget actions.
        \Drupal::moduleHandler()->alter('paragraphs_widget_actions', $widget_actions, $context);

        if (count($widget_actions['actions'])) {
          // Expand all actions to proper submit elements and add it to top
          // actions sub component.
          $element['top']['actions']['actions'] = array_map([$this, 'expandButton'], $widget_actions['actions']);
        }

        if (count($widget_actions['dropdown_actions'])) {
          // Expand all dropdown actions to proper submit elements and add
          // them to top dropdown actions sub component.
          $element['top']['actions']['dropdown_actions'] = array_map([$this, 'expandButton'], $widget_actions['dropdown_actions']);
        }
      }

      $display = EntityFormDisplay::collectRenderDisplay($paragraphs_entity, $this->getSetting('form_display_mode'));

      // @todo Remove as part of https://www.drupal.org/node/2640056
      if (\Drupal::moduleHandler()->moduleExists('field_group')) {
        $context = [
          'entity_type' => $paragraphs_entity->getEntityTypeId(),
          'bundle' => $paragraphs_entity->bundle(),
          'entity' => $paragraphs_entity,
          'context' => 'form',
          'display_context' => 'form',
          'mode' => $display->getMode(),
        ];

        field_group_attach_groups($element['subform'], $context);
        $element['subform']['#pre_render'][] = 'field_group_form_pre_render';
      }

      if ($item_mode == 'edit') {
        $display->buildForm($paragraphs_entity, $element['subform'], $form_state);
        // Get the field definitions of the paragraphs_entity.
        // We need them to filter out entity reference revisions fields that
        // reference paragraphs, cause otherwise we have problems with showing
        // and hiding the right fields in nested paragraphs.
        $field_definitions = $paragraphs_entity->getFieldDefinitions();

        foreach (Element::children($element['subform']) as $field) {
          // Do a check if we have to add a class to the form element. We need
          // those classes (paragraphs-content and paragraphs-behavior) to show
          // and hide elements, depending of the active perspective.
          $omit_class = FALSE;
          if (isset($field_definitions[$field])) {
            $type = $field_definitions[$field]->getType();
            if ($type == 'entity_reference_revisions') {
              // Check if we are referencing paragraphs.
              $target_entity_type = $field_definitions[$field]->get('entity_type');
              if ($target_entity_type && $target_entity_type == 'paragraph') {
                $omit_class = TRUE;
              }
            }
          }

          if ($paragraphs_entity->hasField($field)) {
            if (!$omit_class) {
              $element['subform'][$field]['#attributes']['class'][] = 'paragraphs-content';
            }
            $translatable = $paragraphs_entity->{$field}->getFieldDefinition()->isTranslatable();
            if ($translatable) {
              $element['subform'][$field]['widget']['#after_build'][] = [
                static::class,
                'removeTranslatabilityClue',
              ];
            }
          }
        }

        // Build the behavior plugins fields.
        $paragraphs_type = $paragraphs_entity->getParagraphType();
        if ($paragraphs_type && \Drupal::currentUser()->hasPermission('edit behavior plugin settings')) {
          $element['behavior_plugins']['#weight'] = -99;
          foreach ($paragraphs_type->getEnabledBehaviorPlugins() as $plugin_id => $plugin) {
            $element['behavior_plugins'][$plugin_id] = [
              '#type' => 'container',
              '#group' => implode('][', array_merge($element_parents, ['paragraph_behavior'])),
            ];
            $subform_state = SubformState::createForSubform($element['behavior_plugins'][$plugin_id], $form, $form_state);
            if ($plugin_form = $plugin->buildBehaviorForm($paragraphs_entity, $element['behavior_plugins'][$plugin_id], $subform_state)) {
              $element['behavior_plugins'][$plugin_id] = $plugin_form;
              // Add the paragraphs-behavior class, so that we are able to show
              // and hide behavior fields, depending on the active perspective.
              $element['behavior_plugins'][$plugin_id]['#attributes']['class'][] = 'paragraphs-behavior';
            }
          }
        }
      }
      elseif ($item_mode == 'closed') {
        $element['subform'] = [];
        $element['behavior_plugins'] = [];
        if ($closed_mode_setting === 'preview') {
          // The closed paragraph is displayed as a rendered preview.
          $view_builder = $entity_type_manager->getViewBuilder('paragraph');

          $element['preview'] = $view_builder->view($paragraphs_entity, 'preview', $paragraphs_entity->language()->getId());
          $element['preview']['#access'] = $paragraphs_entity->access('view');
        }
        else {
          // The closed paragraph is displayed as a summary.
          if ($paragraphs_entity) {
            $summary = $paragraphs_entity->getSummary();
            if (!empty($summary)) {
              $element['top']['summary']['fields_info'] = [
                '#markup' => $summary,
                '#prefix' => '<div class="paragraphs-collapsed-description">',
                '#suffix' => '</div>',
                '#access' => $paragraphs_entity->access('update') || $paragraphs_entity->access('view'),
              ];
            }

            $info = array_merge($info, $paragraphs_entity->getIcons());
          }
        }
      }
      else {
        $element['subform'] = array();
      }

      // If we have any info items lets add them to the top section.
      if (count($info)) {
        foreach ($info as $info_item) {
          if (!isset($info_item['#access']) || $info_item['#access']) {
            $element['top']['icons']['items'] = $info;
            break;
          }
        }
      }

      $element['subform']['#attributes']['class'][] = 'paragraphs-subform';
      $element['subform']['#access'] = $paragraphs_entity->access('update');

      if ($item_mode == 'remove') {
        $element['#access'] = FALSE;
      }

      $widget_state['paragraphs'][$delta]['entity'] = $paragraphs_entity;
      $widget_state['paragraphs'][$delta]['display'] = $display;
      $widget_state['paragraphs'][$delta]['mode'] = $item_mode;
      $widget_state['closed_mode'] = $closed_mode_setting;
      $widget_state['autocollapse'] = $autocollapse_setting;
      $widget_state['autocollapse_default'] = $this->getSetting('autocollapse');

      static::setWidgetState($parents, $field_name, $form_state, $widget_state);
    }
    else {
      $element['#access'] = FALSE;
    }

    return $element;
  }

  /**
   * Builds an add paragraph button for opening of modal form.
   *
   * @param array $element
   *   Render element.
   */
  protected function buildModalAddForm(array &$element) {
    // Attach the theme for the dialog template.
    $element['#theme'] = 'paragraphs_add_dialog';

    $element['add_modal_form_area'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'paragraph-type-add-modal',
          'first-button',
        ],
      ],
      '#access' => $this->allowReferenceChanges(),
      '#weight' => -2000,
    ];

    $element['add_modal_form_area']['add_more'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add @title', ['@title' => $this->getSetting('title')]),
      '#name' => 'button_add_modal',
      '#attributes' => [
        'class' => [
          'paragraph-type-add-modal-button',
          'js-show',
        ],
      ],
    ];

    // Hidden field provided by "Modal" mode. Field is provided for additional
    // integrations, where also position of addition can be specified. It should
    // be used by sub-modules or other paragraphs integration. CSS class is used
    // to support easier element selecting in JavaScript.
    $element['add_modal_form_area']['add_more_delta'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => [
          'paragraph-type-add-modal-delta',
        ],
      ],
    ];

    $element['#attached']['library'][] = 'paragraphs/drupal.paragraphs.modal';
    if ($this->isFeatureEnabled('add_above')) {
      $element['#attached']['library'][] = 'paragraphs/drupal.paragraphs.add_above_button';
    }

  }

  /**
   * Returns the sorted allowed types for a entity reference field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *  (optional) The field definition forwhich the allowed types should be
   *  returned, defaults to the current field.
   *
   * @return array
   *   A list of arrays keyed by the paragraph type machine name with the following properties.
   *     - label: The label of the paragraph type.
   *     - weight: The weight of the paragraph type.
   */
  public function getAllowedTypes(FieldDefinitionInterface $field_definition = NULL) {

    $return_bundles = array();
    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager */
    $selection_manager = \Drupal::service('plugin.manager.entity_reference_selection');
    $handler = $selection_manager->getSelectionHandler($field_definition ?: $this->fieldDefinition);
    if ($handler instanceof ParagraphSelection) {
      $return_bundles = $handler->getSortedAllowedTypes();
    }
    // Support for other reference types.
    else {
      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($field_definition ? $field_definition->getSetting('target_type') : $this->fieldDefinition->getSetting('target_type'));
      $weight = 0;
      foreach ($bundles as $machine_name => $bundle) {
        if (!count($this->getSelectionHandlerSetting('target_bundles'))
          || in_array($machine_name, $this->getSelectionHandlerSetting('target_bundles'))) {

          $return_bundles[$machine_name] = array(
            'label' => $bundle['label'],
            'weight' => $weight,
          );

          $weight++;
        }
      }
    }


    return $return_bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $this->fieldParents = $form['#parents'];
    $field_state = static::getWidgetState($this->fieldParents, $field_name, $form_state);

    $max = $field_state['items_count'];
    $entity_type_manager = \Drupal::entityTypeManager();

    // Consider adding a default paragraph for new host entities.
    if ($max == 0 && $items->getEntity()->isNew()) {
      $default_type = $this->getDefaultParagraphTypeMachineName();

      // Checking if default_type is not none and if is allowed.
      if ($default_type) {
        // Place the default paragraph.
        $target_type = $this->getFieldSetting('target_type');

        /** @var \Drupal\paragraphs\ParagraphInterface $paragraphs_entity */
        $paragraphs_entity = $entity_type_manager->getStorage($target_type)->create([
          'type' => $default_type,
        ]);
        $paragraphs_entity->setParentEntity($items->getEntity(), $field_name);
        $field_state['selected_bundle'] = $default_type;
        $display = EntityFormDisplay::collectRenderDisplay($paragraphs_entity, $this->getSetting('form_display_mode'));
        $field_state['paragraphs'][0] = [
          'entity' => $paragraphs_entity,
          'display' => $display,
          'mode' => 'edit',
          'original_delta' => 1
        ];
        $max = 1;
        $field_state['items_count'] = $max;
      }
    }

    $this->realItemCount = $max;
    $is_multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    $field_title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    $elements = array();
    $tabs = '';
    $this->fieldIdPrefix = implode('-', array_merge($this->fieldParents, array($field_name)));
    $this->fieldWrapperId = Html::getId($this->fieldIdPrefix . '-add-more-wrapper');

    // If the parent entity is paragraph add the nested class if not then add
    // the perspective tabs.
    $field_prefix = strtr($this->fieldIdPrefix, '_', '-');
    if (count($this->fieldParents) == 0) {
      if ($items->getEntity()->getEntityTypeId() != 'paragraph') {
        $tabs = '<ul class="paragraphs-tabs tabs primary clearfix"><li id="content" class="tabs__tab"><a href="#' . $field_prefix . '-values">Content</a></li><li id="behavior" class="tabs__tab"><a href="#' . $field_prefix . '-values">Behavior</a></li></ul>';
      }
    }
    if (count($this->fieldParents) > 0) {
      if ($items->getEntity()->getEntityTypeId() === 'paragraph') {
        $form['#attributes']['class'][] = 'paragraphs-nested';
      }
    }
    $elements['#prefix'] = '<div class="is-horizontal paragraphs-tabs-wrapper" id="' . $this->fieldWrapperId . '">' . $tabs;
    $elements['#suffix'] = '</div>';

    $field_state['ajax_wrapper_id'] = $this->fieldWrapperId;
    // Persist the widget state so formElement() can access it.
    static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);

    if (!empty($field_state['dragdrop'])) {
      $elements['header_actions']['actions']['complete_button'] = $this->expandButton([
        '#type' => 'submit',
        '#name' => $this->fieldIdPrefix . '_dragdrop_mode',
        '#value' => $this->t('Complete drag & drop'),
        '#attributes' => ['class' => ['field-dragdrop-mode-submit']],
        '#submit' => [[get_class($this), 'dragDropModeSubmit']],
        '#ajax' => [
          'callback' => [get_class($this), 'dragDropModeAjax'],
          'wrapper' => $this->fieldWrapperId,
        ],
        '#button_type' => 'primary',
      ]);

      $elements['#attached']['library'][] = 'paragraphs/paragraphs-dragdrop';
      //$elements['dragdrop_mode']['#button_type'] = 'primary';
      $elements['dragdrop'] = $this->buildNestedParagraphsFoDragDrop($form_state, NULL, []);
      return $elements;
    }

    if ($max > 0) {
      for ($delta = 0; $delta < $max; $delta++) {

        // Add a new empty item if it doesn't exist yet at this delta.
        if (!isset($items[$delta])) {
          $items->appendItem();
        }

        // For multiple fields, title and description are handled by the wrapping
        // table.
        $element = array(
          '#title' => $is_multiple ? '' : $field_title,
          '#description' => $is_multiple ? '' : $description,
        );
        $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

        if ($element) {
          // Input field for the delta (drag-n-drop reordering).
          if ($is_multiple) {
            // We name the element '_weight' to avoid clashing with elements
            // defined by widget.
            $element['_weight'] = array(
              '#type' => 'weight',
              '#title' => $this->t('Weight for row @number', array('@number' => $delta + 1)),
              '#title_display' => 'invisible',
              // Note: this 'delta' is the FAPI #type 'weight' element's property.
              '#delta' => $max,
              '#default_value' => $items[$delta]->_weight ?: $delta,
              '#weight' => 100,
            );
          }

          // Access for the top element is set to FALSE only when the paragraph
          // was removed. A paragraphs that a user can not edit has access on
          // lower level.
          if (isset($element['#access']) && !$element['#access']) {
            $this->realItemCount--;
          }
          else {
            $elements[$delta] = $element;
          }
        }
      }
    }

    $field_state = static::getWidgetState($this->fieldParents, $field_name, $form_state);
    $field_state['real_item_count'] = $this->realItemCount;
    $field_state['add_mode'] = $this->getSetting('add_mode');
    static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);

    $elements += [
      '#element_validate' => [[$this, 'multipleElementValidate']],
      '#required' => $this->fieldDefinition->isRequired(),
      '#field_name' => $field_name,
      '#cardinality' => $cardinality,
      '#max_delta' => $max - 1,
    ];

    $elements += [
      '#theme' => 'field_multiple_value_form',
      '#field_name' => $field_name,
      '#cardinality' => $cardinality,
      '#cardinality_multiple' => TRUE,
      '#required' => $this->fieldDefinition->isRequired(),
      '#title' => $field_title,
      '#description' => $description,
      '#max_delta' => $max - 1,
    ];

    $host = $items->getEntity();
    $this->initIsTranslating($form_state, $host);

    $header_actions = $this->buildHeaderActions($field_state, $form_state);
    if ($header_actions) {
      $elements['header_actions'] = $header_actions;
      // Add a weight element so we guaranty that header actions will stay in
      // first row. We will use this later in
      // paragraphs_preprocess_field_multiple_value_form().
      $elements['header_actions']['_weight'] = [
        '#type' => 'weight',
        '#default_value' => -100,
      ];
    }

    if (($this->realItemCount < $cardinality || $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) && !$form_state->isProgrammed() && $this->allowReferenceChanges()) {
      $elements['add_more'] = $this->buildAddActions();
    }

    $elements['#allow_reference_changes'] = $this->allowReferenceChanges();
    $elements['#attached']['library'][] = 'paragraphs/drupal.paragraphs.widget';

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $parents = $form['#parents'];

    // Identify the manage field settings default value form.
    if (in_array('default_value_input', $parents, TRUE)) {
      // Since the entity is not reusable neither cloneable, having a default
      // value is not supported.
      return ['#markup' => $this->t('No widget available for: %label.', ['%label' => $items->getFieldDefinition()->getLabel()])];
    }

    return parent::form($items, $form, $form_state, $get_delta);
  }

  /**
   * Returns a list of child paragraphs for a given field to loop over.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $field_name
   *   The field name for which to find child paragraphs.
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The current paragraph.
   * @param array $array_parents
   *   The current field parent structure.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph[]
   *   Child paragraphs.
   */
  protected function getChildParagraphs(FormStateInterface $form_state, $field_name, ParagraphInterface $paragraph = NULL, array $array_parents = []) {

    // Convert the parents structure which only includes field names and delta
    // to the full storage array key which includes a prefix and a subform.
    $full_parents_key = ['field_storage', '#parents'];
    foreach ($array_parents as $i => $parent) {
      $full_parents_key[] = $parent;
      if ($i % 2) {
        $full_parents_key[] = 'subform';
      }
    }

    $current_parents = array_merge($full_parents_key, ['#fields', $field_name]);
    $child_field_state = NestedArray::getValue($form_state->getStorage(), $current_parents);
    $entities = [];
    if ($child_field_state && isset($child_field_state['paragraphs'])) {
      // Fetch the paragraphs from the field state. Use the original delta
      // to get the right position. Also reorder the paragraphs in the widget
      // state accordingly.
      $new_widget_paragraphs = [];
      foreach ($child_field_state['paragraphs'] as $child_delta => $child_field_item_state) {
        $entities[array_search($child_delta, $child_field_state['original_deltas'])] = $child_field_item_state['entity'];
        $new_widget_paragraphs[array_search($child_delta, $child_field_state['original_deltas'])] = $child_field_item_state;
      }
      ksort($entities);

      // Set the orderd paragraphs into the widget state and reset original
      // deltas.
      ksort($new_widget_paragraphs);
      $child_field_state['paragraphs'] = $new_widget_paragraphs;
      $child_field_state['original_deltas'] = range(0, count($child_field_state['paragraphs']) - 1);
      NestedArray::setValue($form_state->getStorage(), $current_parents, $child_field_state);
    }
    elseif ($paragraph) {
      // If there is no field state, return the paragraphs directly from the
      // entity.
      foreach ($paragraph->get($field_name) as $child_delta => $item) {
        if ($item->entity) {
          $entities[$child_delta] = $item->entity;
        }
      }
    }

    return $entities;
  }

  /**
   * Builds the nested drag and drop structure.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\paragraphs\ParagraphInterface|null $paragraph
   *   The parent paragraph, NULL for the initial call.
   * @param string[] $array_parents
   *   The array parents for nested paragraphs.
   *
   * @return array
   *   The built form structure.
   */
  protected function buildNestedParagraphsFoDragDrop(FormStateInterface $form_state, ParagraphInterface $paragraph = NULL, array $array_parents = []) {
    // Look for nested elements.
    $elements = [];
    $field_definitions = [];
    if ($paragraph) {
      foreach ($paragraph->getFieldDefinitions() as $child_field_name => $field_definition) {
        /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
        if ($field_definition->getType() == 'entity_reference_revisions' && $field_definition->getSetting('target_type') == 'paragraph') {
          $field_definitions[$child_field_name] = $field_definition;
        }
      }
    }
    else {
      $field_definitions = [$this->fieldDefinition->getName() => $this->fieldDefinition];
    }

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    foreach ($field_definitions as $child_field_name => $field_definition) {
      $child_path = implode('][', array_merge($array_parents, [$child_field_name]));
      $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();
      $allowed_types = implode(array_keys($this->getAllowedTypes($field_definition)), ',');
      $elements[$child_field_name] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['paragraphs-dragdrop-wrapper']],
      ];

      // Only show a field label if there is more than one paragraph field.
      $label = count($field_definitions) > 1 || !$paragraph ? '<label><strong class="paragraphs-dragdrop__label paragraphs-dragdrop__label--field">' . $field_definition->getLabel() . '</strong></label>' : '';

      $elements[$child_field_name]['list'] = [
        '#type' => 'markup',
        '#prefix' => $label . '<ul class="paragraphs-dragdrop__list" data-paragraphs-dragdrop-cardinality="' . $cardinality . '" data-paragraphs-dragdrop-allowed-types="' . $allowed_types . '" data-paragraphs-dragdrop-path="' . $child_path . '">',
        '#suffix' => '</ul>',
      ];

      /** @var \Drupal\paragraphs\Entity\Paragraph $child_paragraph */
      foreach ($this->getChildParagraphs($form_state, $child_field_name, $paragraph, $array_parents) as $child_delta => $child_paragraph) {
        $element = [];
        $element['top'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['paragraphs-summary-wrapper']],
        ];
        $element['top']['paragraph_summary']['type'] = [
          '#markup' => '<strong class="paragraphs-dragdrop__label paragraphs-dragdrop__label--bundle">' . $child_paragraph->getParagraphType()->label() . '</strong>',
        ];

        // We name the element '_weight' to avoid clashing with elements
        // defined by widget.
        $element['_weight'] = array(
          '#type' => 'hidden',
          '#default_value' => $child_delta,
          '#attributes' => [
            'class' => ['paragraphs-dragdrop__weight'],
          ]
        );

        $element['_path'] = [
          '#type' => 'hidden',
          '#title' => $this->t('Current path for @number', ['@number' => $delta = 1]),
          '#title_display' => 'invisible',
          '#default_value' => $child_path,
          '#attributes' => [
            'class' => ['paragraphs-dragdrop__path'],
          ]
        ];

        $summary_options = [];

        $element['#prefix'] = '<li class="paragraphs-dragdrop__item" data-paragraphs-dragdrop-bundle="' . $child_paragraph->bundle() . '"><a href="#" class="paragraphs-dragdrop__handle"><span class="paragraphs-dragdrop__icon"></span></a>';
        $element['#suffix'] = '</li>';
        $child_array_parents = array_merge($array_parents,  [$child_field_name, $child_delta]);

        if ($child_elements = $this->buildNestedParagraphsFoDragDrop($form_state, $child_paragraph, $child_array_parents)) {
          $element['dragdrop'] = $child_elements;

          // Set the depth limit to 0 to avoid displaying a summary for the
          // children.
          $summary_options['depth_limit'] = 1;
        }

        $element['top']['summary']['fields_info'] = [
          '#markup' => $child_paragraph->getSummary($summary_options),
          '#prefix' => '<div class="paragraphs-collapsed-description">',
          '#suffix' => '</div>',
          '#access' => $child_paragraph->access('update') || $child_paragraph->access('view'),
        ];

        $info = $child_paragraph->getIcons();
        if (isset($info['count'])) {
          $element['top']['icons']['count'] = $info['count'];
        }

        $elements[$child_field_name]['list'][$child_delta] = $element;
      }
    }
    return $elements;
  }

  /**
   * Add 'add more' button, if not working with a programmed form.
   *
   * @return array
   *    The form element array.
   */
  protected function buildAddActions() {
    if (count($this->getAccessibleOptions()) === 0) {
      if (count($this->getAllowedTypes()) === 0) {
        $add_more_elements['icons'] = $this->createMessage($this->t('You are not allowed to add any of the @title types.', ['@title' => $this->getSetting('title')]));
      }
      else {
        $add_more_elements['icons'] = $this->createMessage($this->t('You did not add any @title types yet.', ['@title' => $this->getSetting('title')]));
      }

      return $add_more_elements;
    }

    if (in_array($this->getSetting('add_mode'), ['button', 'dropdown', 'modal'])) {
      return $this->buildButtonsAddMode();
    }

    return $this->buildSelectAddMode();
  }

  /**
   * Returns the available paragraphs type.
   *
   * @return array
   *   Available paragraphs types.
   */
  protected function getAccessibleOptions() {
    if ($this->accessOptions !== NULL) {
      return $this->accessOptions;
    }

    $entity_type_manager = \Drupal::entityTypeManager();
    $target_type = $this->getFieldSetting('target_type');
    $bundles = $this->getAllowedTypes();
    $access_control_handler = $entity_type_manager->getAccessControlHandler($target_type);
    $dragdrop_settings = $this->getSelectionHandlerSetting('target_bundles_drag_drop');

    foreach ($bundles as $machine_name => $bundle) {
      if ($dragdrop_settings || (!count($this->getSelectionHandlerSetting('target_bundles'))
          || in_array($machine_name, $this->getSelectionHandlerSetting('target_bundles')))) {
        if ($access_control_handler->createAccess($machine_name)) {
          $this->accessOptions[$machine_name] = $bundle['label'];
        }
      }
    }

    return $this->accessOptions;
  }

  /**
   * Helper to create a paragraph UI message.
   *
   * @param string $message
   *   Message text.
   * @param string $type
   *   Message type.
   *
   * @return array
   *   Render array of message.
   */
  public function createMessage($message, $type = 'warning') {
    return [
      '#type' => 'container',
      '#markup' => $message,
      '#attributes' => ['class' => ['messages', 'messages--' . $type]],
    ];
  }

  /**
   * Expand button base array into a paragraph widget action button.
   *
   * @param array $button_base
   *   Button base render array.
   *
   * @return array
   *   Button render array.
   */
  public static function expandButton(array $button_base) {
    // Do not expand elements that do not have submit handler.
    if (empty($button_base['#submit'])) {
      return $button_base;
    }

    $button = $button_base + [
      '#type' => 'submit',
      '#theme_wrappers' => ['input__submit__paragraph_action'],
    ];

    // Html::getId will give us '-' char in name but we want '_' for now so
    // we use strtr to search&replace '-' to '_'.
    $button['#name'] = strtr(Html::getId($button_base['#name']), '-', '_');
    $button['#id'] = Html::getUniqueId($button['#name']);

    if (isset($button['#ajax'])) {
      $button['#ajax'] += [
        'effect' => 'fade',
        // Since a normal throbber is added inline, this has the potential to
        // break a layout if the button is located in dropbuttons. Instead,
        // it's safer to just show the fullscreen progress element instead.
        'progress' => ['type' => 'fullscreen'],
      ];
    }

    return $button;
  }

  /**
   * Get common submit element information for processing ajax submit handlers.
   *
   * @param array $form
   *   Form array.
   * @param FormStateInterface $form_state
   *   Form state object.
   * @param int $position
   *   Position of triggering element.
   *
   * @return array
   *   Submit element information.
   */
  public static function getSubmitElementInfo(array $form, FormStateInterface $form_state, $position = ParagraphsWidget::ACTION_POSITION_BASE) {
    $submit['button'] = $form_state->getTriggeringElement();

    // Go up in the form, to the widgets container.
    if ($position == ParagraphsWidget::ACTION_POSITION_BASE) {
      $submit['element'] = NestedArray::getValue($form, array_slice($submit['button']['#array_parents'], 0, -2));
    }
    if ($position == ParagraphsWidget::ACTION_POSITION_HEADER) {
      $submit['element'] = NestedArray::getValue($form, array_slice($submit['button']['#array_parents'], 0, -3));
    }
    elseif ($position == ParagraphsWidget::ACTION_POSITION_ACTIONS) {
      $submit['element'] = NestedArray::getValue($form, array_slice($submit['button']['#array_parents'], 0, -5));
      $delta = array_slice($submit['button']['#array_parents'], -5, -4);
      $submit['delta'] = $delta[0];
    }

    $submit['field_name'] = $submit['element']['#field_name'];
    $submit['parents'] = $submit['element']['#field_parents'];

    // Get widget state.
    $submit['widget_state'] = static::getWidgetState($submit['parents'], $submit['field_name'], $form_state);

    return $submit;
  }

  /**
   * Build drop button.
   *
   * @param array $elements
   *   Elements for drop button.
   *
   * @return array
   *   Drop button array.
   */
  protected function buildDropbutton(array $elements = []) {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['paragraphs-dropbutton-wrapper']],
    ];

    $operations = [];
    // Because we are cloning the elements into title sub element we need to
    // sort children first.
    foreach (Element::children($elements, TRUE) as $child) {
      // Clone the element as an operation.
      $operations[$child] = ['title' => $elements[$child]];

      // Flag the original element as printed so it doesn't render twice.
      $elements[$child]['#printed'] = TRUE;
    }

    $build['operations'] = [
      '#type' => 'paragraph_operations',
      // Even though operations are run through the "links" element type, the
      // theme system will render any render array passed as a link "title".
      '#links' => $operations,
    ];

    return $build + $elements;
  }

  /**
   * Builds dropdown button for adding new paragraph.
   *
   * @return array
   *   The form element array.
   */
  protected function buildButtonsAddMode() {
    $options = $this->getAccessibleOptions();
    $add_mode = $this->getSetting('add_mode');
    $paragraphs_type_storage = \Drupal::entityTypeManager()->getStorage('paragraphs_type');

    // Build the buttons.
    $add_more_elements = [];
    foreach ($options as $machine_name => $label) {
      $button_key = 'add_more_button_' . $machine_name;
      $add_more_elements[$button_key] = $this->expandButton([
        '#type' => 'submit',
        '#name' => $this->fieldIdPrefix . '_' . $machine_name . '_add_more',
        '#value' => $add_mode == 'modal' ? $label : $this->t('Add @type', ['@type' => $label]),
        '#attributes' => ['class' => ['field-add-more-submit']],
        '#limit_validation_errors' => [array_merge($this->fieldParents, [$this->fieldDefinition->getName(), 'add_more'])],
        '#submit' => [[get_class($this), 'addMoreSubmit']],
        '#ajax' => [
          'callback' => [get_class($this), 'addMoreAjax'],
          'wrapper' => $this->fieldWrapperId,
        ],
        '#bundle_machine_name' => $machine_name,
      ]);

      if ($add_mode === 'modal' && $icon_url = $paragraphs_type_storage->load($machine_name)->getIconUrl()) {
        $add_more_elements[$button_key]['#attributes']['style'] = 'background-image: url(' . $icon_url . ');';
      }
    }

    // Determine if buttons should be rendered as dropbuttons.
    if (count($options) > 1 && $add_mode == 'dropdown') {
      $add_more_elements = $this->buildDropbutton($add_more_elements);
      $add_more_elements['#suffix'] = $this->t('to %type', ['%type' => $this->fieldDefinition->getLabel()]);
    }
    elseif ($add_mode == 'modal') {
      $this->buildModalAddForm($add_more_elements);
      $add_more_elements['add_modal_form_area']['#suffix'] = $this->t('to %type', ['%type' => $this->fieldDefinition->getLabel()]);
    }
    $add_more_elements['#weight'] = 1;

    return $add_more_elements;
  }

  /**
   * Builds list of actions based on paragraphs type.
   *
   * @return array
   *   The form element array.
   */
  protected function buildSelectAddMode() {
    $field_name = $this->fieldDefinition->getName();
    $field_title = $this->fieldDefinition->getLabel();
    $setting_title = $this->getSetting('title');
    $add_more_elements['add_more_select'] = [
      '#type' => 'select',
      '#options' => $this->getAccessibleOptions(),
      '#title' => $this->t('@title type', ['@title' => $setting_title]),
      '#label_display' => 'hidden',
    ];

    $text = $this->t('Add @title', ['@title' => $setting_title]);

    if ($this->realItemCount > 0) {
      $text = $this->t('Add another @title', ['@title' => $setting_title]);
    }

    $add_more_elements['add_more_button'] = [
      '#type' => 'submit',
      '#name' => strtr($this->fieldIdPrefix, '-', '_') . '_add_more',
      '#value' => $text,
      '#attributes' => ['class' => ['field-add-more-submit']],
      '#limit_validation_errors' => [array_merge($this->fieldParents, [$field_name, 'add_more'])],
      '#submit' => [[get_class($this), 'addMoreSubmit']],
      '#ajax' => [
        'callback' => [get_class($this), 'addMoreAjax'],
        'wrapper' => $this->fieldWrapperId,
        'effect' => 'fade',
      ],
    ];

    $add_more_elements['add_more_button']['#suffix'] = $this->t(' to %type', ['%type' => $field_title]);
    return $add_more_elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $submit = ParagraphsWidget::getSubmitElementInfo($form, $form_state);
    $element = $submit['element'];

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $submit['element']['#max_delta'];
    $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
    $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';

    return $element;
  }

  /**
   * Ajax callback for all actions.
   */
  public static function allActionsAjax(array $form, FormStateInterface $form_state) {
    $submit = ParagraphsWidget::getSubmitElementInfo($form, $form_state, ParagraphsWidget::ACTION_POSITION_HEADER);
    $element = $submit['element'];

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $submit['element']['#max_delta'];
    $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
    $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';

    return $element;
  }

  /**
   * Prepares the widget state to add a new paragraph at a specific position.
   *
   * In addition to the widget state change, also user input could be modified
   * to handle adding of a new paragraph at a specific position between existing
   * paragraphs.
   *
   * @param array $widget_state
   *   Widget state as reference, so that it can be updated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $field_path
   *   Path to paragraph field.
   * @param int|mixed $new_delta
   *   Delta position in list of paragraphs, where new paragraph will be added.
   */
  protected static function prepareDeltaPosition(array &$widget_state, FormStateInterface $form_state, array $field_path, $new_delta) {
    // Increase number of items to create place for new paragraph.
    $widget_state['items_count']++;

    // Default behavior is adding to end of list and in case delta is not
    // provided or already at end, we can skip all other steps.
    if (!is_numeric($new_delta) || intval($new_delta) >= $widget_state['real_item_count']) {
      return;
    }

    $widget_state['real_item_count']++;

    // Limit delta between 0 and "number of items" in paragraphs widget.
    $new_delta = max(intval($new_delta), 0);

    // Change user input in order to create new delta position.
    $user_input = NestedArray::getValue($form_state->getUserInput(), $field_path);

    // Rearrange all original deltas to make one place for the new element.
    $new_original_deltas = [];
    foreach ($widget_state['original_deltas'] as $current_delta => $original_delta) {
      $new_current_delta = $current_delta >= $new_delta ? $current_delta + 1 : $current_delta;

      $new_original_deltas[$new_current_delta] = $original_delta;
      $user_input[$original_delta]['_weight'] = $new_current_delta;
    }

    // Add information into delta mapping for the new element.
    $original_deltas_size = count($widget_state['original_deltas']);
    $new_original_deltas[$new_delta] = $original_deltas_size;
    $user_input[$original_deltas_size]['_weight'] = $new_delta;

    $widget_state['original_deltas'] = $new_original_deltas;
    NestedArray::setValue($form_state->getUserInput(), $field_path, $user_input);
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $submit = ParagraphsWidget::getSubmitElementInfo($form, $form_state);

    if ($submit['widget_state']['real_item_count'] < $submit['element']['#cardinality'] || $submit['element']['#cardinality'] == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $field_path = array_merge($submit['element']['#field_parents'], [$submit['element']['#field_name']]);
      $add_more_delta = NestedArray::getValue(
        $submit['element'],
        ['add_more', 'add_modal_form_area', 'add_more_delta', '#value']
      );

      static::prepareDeltaPosition($submit['widget_state'], $form_state, $field_path, $add_more_delta);
    }

    if (isset($submit['button']['#bundle_machine_name'])) {
      $submit['widget_state']['selected_bundle'] = $submit['button']['#bundle_machine_name'];
    }
    else {
      $submit['widget_state']['selected_bundle'] = $submit['element']['add_more']['add_more_select']['#value'];
    }

    $submit['widget_state'] = static::autocollapse($submit['widget_state']);

    static::setWidgetState($submit['parents'], $submit['field_name'], $form_state, $submit['widget_state']);

    $form_state->setRebuild();
  }

  /**
   * Creates a duplicate of the paragraph entity.
   */
  public static function duplicateSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -5));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];
    $filed_path = array_slice($button['#parents'], 0, -5);

    // Inserting new element in the array.
    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    // Map the button delta to the actual delta.
    $original_button_delta = $button['#delta'];
    $position = array_search($button['#delta'], $widget_state['original_deltas']) + 1;
    static::prepareDeltaPosition($widget_state, $form_state, $filed_path, $position);

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $widget_state['paragraphs'][$original_button_delta]['entity'];

    $widget_state = static::autocollapse($widget_state);

    // Check if the replicate module is enabled.
    if (\Drupal::hasService('replicate.replicator')) {
      $duplicate_entity = \Drupal::getContainer()->get('replicate.replicator')->replicateEntity($entity);
    }
    else {
      $duplicate_entity = $entity->createDuplicate();
    }
    // Create the duplicated paragraph and insert it below the original.
    $widget_state['paragraphs'][] = [
      'entity' => $duplicate_entity,
      'display' => $widget_state['paragraphs'][$original_button_delta]['display'],
      'mode' => 'edit',
    ];

    static::setWidgetState($parents, $field_name, $form_state, $widget_state);
    $form_state->setRebuild();
  }

  public static function paragraphsItemSubmit(array $form, FormStateInterface $form_state) {
    $submit = ParagraphsWidget::getSubmitElementInfo($form, $form_state, ParagraphsWidget::ACTION_POSITION_ACTIONS);

    $new_mode = $submit['button']['#paragraphs_mode'];

    if ($new_mode === 'edit') {
      $submit['widget_state'] = static::autocollapse($submit['widget_state']);
    }

    $submit['widget_state']['paragraphs'][$submit['delta']]['mode'] = $new_mode;

    if (!empty($submit['button']['#paragraphs_show_warning'])) {
      $submit['widget_state']['paragraphs'][$submit['delta']]['show_warning'] = $submit['button']['#paragraphs_show_warning'];
    }

    static::setWidgetState($submit['parents'], $submit['field_name'], $form_state, $submit['widget_state']);

    $form_state->setRebuild();
  }

  public static function itemAjax(array $form, FormStateInterface $form_state) {
    $submit = ParagraphsWidget::getSubmitElementInfo($form, $form_state, ParagraphsWidget::ACTION_POSITION_ACTIONS);

    $submit['element']['#prefix'] = '<div class="ajax-new-content">' . (isset($submit['element']['#prefix']) ? $submit['element']['#prefix'] : '');
    $submit['element']['#suffix'] = (isset($submit['element']['#suffix']) ? $submit['element']['#suffix'] : '') . '</div>';

    return $submit['element'];
  }

  /**
   * Sets the form mode accordingly.
   *
   * @param array $form
   *   An associate array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function dragDropModeSubmit(array $form, FormStateInterface $form_state) {
    $submit = ParagraphsWidget::getSubmitElementInfo($form, $form_state, ParagraphsWidget::ACTION_POSITION_HEADER);

    if (empty($submit['widget_state']['dragdrop'])) {
      $submit['widget_state']['dragdrop'] = TRUE;
    }
    else {
      $submit['widget_state']['dragdrop'] = FALSE;
    }

    // Make sure that flag that we already reordered is unset when the mode is
    // switched.
    unset($submit['widget_state']['reordered']);

    // Switch the form mode accordingly.
    static::setWidgetState($submit['parents'], $submit['field_name'], $form_state, $submit['widget_state']);

    $form_state->setRebuild();
  }


  /**
   * Reorder paragraphs.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param $field_values_parents
   *   The field value parents.
   */
  protected static function reorderParagraphs(FormStateInterface $form_state, $field_values_parents) {
    $field_name = end($field_values_parents);
    $field_values = NestedArray::getValue($form_state->getValues(), $field_values_parents);
    $complete_field_storage = NestedArray::getValue(
      $form_state->getStorage(), [
        'field_storage',
        '#parents'
      ]
    );
    $new_field_storage = $complete_field_storage;

    // Set a flag to prevent this from running twice, as the entity is built
    // for validation as well as saving and would fail the second time as we
    // already altered the field storage.
    if (!empty($new_field_storage['#fields'][$field_name]['reordered'])) {
      return;
    }
    $new_field_storage['#fields'][$field_name]['reordered'] = TRUE;

    // Clear out all current paragraphs keys in all nested paragraph widgets
    // as there might be fewer than before or none in a certain widget.
    $clear_paragraphs = function ($field_storage) use (&$clear_paragraphs) {
      foreach ($field_storage as $key => $value) {
        if ($key === '#fields') {
          foreach ($value as $field_name => $widget_state) {
            if (isset($widget_state['paragraphs'])) {
              $field_storage['#fields'][$field_name]['paragraphs'] = [];
            }
          }
        }
        else {
          $field_storage[$key] = $clear_paragraphs($field_storage[$key]);
        }
      }
      return $field_storage;
    };

    // Only clear the current field and its children to avoid deleting
    // paragraph references in other fields.
    $new_field_storage['#fields'][$field_name]['paragraphs'] = [];
    if (isset($new_field_storage[$field_name])) {
      $new_field_storage[$field_name] = $clear_paragraphs($new_field_storage[$field_name]);
    }

    $reorder_paragraphs = function ($reorder_values, $parents = [], FieldableEntityInterface $parent_entity = NULL) use ($complete_field_storage, &$new_field_storage, &$reorder_paragraphs) {
      foreach ($reorder_values as $field_name => $values) {
        foreach ($values['list'] as $delta => $item_values) {
          $old_keys = array_merge(
            $parents, [
              '#fields',
              $field_name,
              'paragraphs',
              $delta
            ]
          );
          $path = explode('][', $item_values['_path']);
          $new_field_name = array_pop($path);
          $key_parents = [];
          foreach ($path as $i => $key) {
            $key_parents[] = $key;
            if ($i % 2 == 1) {
              $key_parents[] = 'subform';
            }
          }
          $new_keys = array_merge(
            $key_parents, [
              '#fields',
              $new_field_name,
              'paragraphs',
              $item_values['_weight']
            ]
          );
          $key_exists = NULL;
          $item_state = NestedArray::getValue($complete_field_storage, $old_keys, $key_exists);
          if (!$key_exists && $parent_entity) {
            // If key does not exist, then this parent widget was previously
            // not expanded. This can only happen on nested levels. In that
            // case, initialize a new item state and set the widget state to
            // an empty array if it is not already set from an earlier item.
            // If something else is placed there, it will be put in there,
            // otherwise the widget will know that nothing is there anymore.
            $item_state = [
              'entity' => $parent_entity->get($field_name)->get($delta)->entity,
              'mode' => 'closed',
            ];
            $widget_state_keys = array_slice($old_keys, 0, count($old_keys) - 2);
            if (!NestedArray::getValue($new_field_storage, $widget_state_keys)) {
              NestedArray::setValue($new_field_storage, $widget_state_keys, ['paragraphs' => []]);
            }
          }

          // Ensure the referenced paragraph will be saved.
          $item_state['entity']->setNeedsSave(TRUE);

          NestedArray::setValue($new_field_storage, $new_keys, $item_state);
          if (isset($item_values['dragdrop'])) {

            // If there is no field storage yet for the new position, initialize
            // it to an empty array in case all paragraphs have been moved away
            // from it.
            foreach (array_keys($item_values['dragdrop']) as $sub_field_name) {
              $new_widget_state_keys = array_merge($parents, [$field_name, $item_values['_weight'] ,'subform', '#fields', $sub_field_name]);
              if (!NestedArray::getValue($new_field_storage, $new_widget_state_keys)) {
                NestedArray::setValue($new_field_storage, $new_widget_state_keys, ['paragraphs' => []]);
              }
            }

            $reorder_paragraphs($item_values['dragdrop'], array_merge($parents, [$field_name, $delta, 'subform']), $item_state['entity']);
          }
        }
      }
    };
    $reorder_paragraphs($field_values['dragdrop']);

    // Recalculate original deltas.
    $recalculate_original_deltas = function ($field_storage, ContentEntityInterface $parent_entity) use (&$recalculate_original_deltas) {
      if (isset($field_storage['#fields'])) {
        foreach ($field_storage['#fields'] as $field_name => $widget_state) {
          if (isset($widget_state['paragraphs'])) {

            // If the parent field does not exist but we have paragraphs in
            // widget state, something went wrong and we have a mismatch.
            // Throw an exception.
            if (!$parent_entity->hasField($field_name) && !empty($widget_state['paragraphs'])) {
              throw new \LogicException('Reordering paragraphs resulted in paragraphs on non-existing field ' . $field_name . ' on parent entity ' . $parent_entity->getEntityTypeId() . '/' . $parent_entity->id());
            }

            // Sort the paragraphs by key so that they will be assigned to
            // the entity in the right order. Reset the deltas.
            ksort($widget_state['paragraphs']);
            $widget_state['paragraphs'] = array_values($widget_state['paragraphs']);

            $original_deltas = range(0, count($widget_state['paragraphs']) - 1);
            $field_storage['#fields'][$field_name]['original_deltas'] = $original_deltas;
            $field_storage['#fields'][$field_name]['items_count'] = count($widget_state['paragraphs']);
            $field_storage['#fields'][$field_name]['real_item_count'] = count($widget_state['paragraphs']);

            // Update the parent entity and point to the new children, if the
            // parent field does not exist, we also have no paragraphs, so
            // we can just skip this, this is a dead leaf after re-ordering.
            // @todo Clean this up somehow?
            if ($parent_entity->hasField($field_name)) {
              $parent_entity->set($field_name, array_column($widget_state['paragraphs'], 'entity'));

              // Next process that field recursively.
              foreach (array_keys($widget_state['paragraphs']) as $delta) {
                if (isset($field_storage[$field_name][$delta]['subform'])) {
                  $field_storage[$field_name][$delta]['subform'] = $recalculate_original_deltas($field_storage[$field_name][$delta]['subform'], $parent_entity->get($field_name)->get($delta)->entity);
                }
              }
            }

          }
        }
      }
      return $field_storage;
    };

    $parent_entity = $form_state->getFormObject()->getEntity();
    $new_field_storage = $recalculate_original_deltas($new_field_storage, $parent_entity);

    $form_state->set(['field_storage', '#parents'], $new_field_storage);
  }

  /**
   * Ajax callback for the dragdrop mode.
   *
   * @param array $form
   *   An associate array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The container form element.
   */
  public static function dragDropModeAjax(array $form, FormStateInterface $form_state) {
    $submit = ParagraphsWidget::getSubmitElementInfo($form, $form_state, ParagraphsWidget::ACTION_POSITION_HEADER);

    $submit['element']['#prefix'] = '<div class="ajax-new-content">' . (isset($submit['element']['#prefix']) ? $submit['element']['#prefix'] : '');
    $submit['element']['#suffix'] = (isset($submit['element']['#suffix']) ? $submit['element']['#suffix'] : '') . '</div>';

    return $submit['element'];
  }

  /**
   * Returns the value of a setting for the entity reference selection handler.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getSelectionHandlerSetting($setting_name) {
    $settings = $this->getFieldSetting('handler_settings');
    return isset($settings[$setting_name]) ? $settings[$setting_name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function elementValidate($element, FormStateInterface $form_state, $form) {
    $field_name = $this->fieldDefinition->getName();
    $widget_state = static::getWidgetState($element['#field_parents'], $field_name, $form_state);
    $delta = $element['#delta'];

    if (isset($widget_state['paragraphs'][$delta]['entity'])) {
      /** @var \Drupal\paragraphs\ParagraphInterface $paragraphs_entity */
      $entity = $widget_state['paragraphs'][$delta]['entity'];

      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
      $display = $widget_state['paragraphs'][$delta]['display'];

      if ($widget_state['paragraphs'][$delta]['mode'] == 'edit') {
        // Extract the form values on submit for getting the current paragraph.
        $display->extractFormValues($entity, $element['subform'], $form_state);

        // Validate all enabled behavior plugins.
        $paragraphs_type = $entity->getParagraphType();
        if (\Drupal::currentUser()->hasPermission('edit behavior plugin settings')) {
          foreach ($paragraphs_type->getEnabledBehaviorPlugins() as $plugin_id => $plugin_values) {
            $subform_state = SubformState::createForSubform($element['behavior_plugins'][$plugin_id], $form_state->getCompleteForm(), $form_state);
            $plugin_values->validateBehaviorForm($entity, $element['behavior_plugins'][$plugin_id], $subform_state);
          }
        }
      }
    }

    static::setWidgetState($element['#field_parents'], $field_name, $form_state, $widget_state);
  }

  /**
   * {@inheritdoc}
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);

    // In dragdrop mode, validation errors can not be mapped to form elements,
    // add them on the top level widget element.
    if (!empty($field_state['dragdrop'])) {
      if ($violations->count()) {
        $element = NestedArray::getValue($form_state->getCompleteForm(), $field_state['array_parents']);
        foreach ($violations as $violation) {
          $form_state->setError($element, $violation->getMessage());
        }
      }
    }
    else {
      return parent::flagErrors($items, $violations, $form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    // Validation errors might be a about a specific (behavior) form element
    // attempt to find a matching element.
    if (!empty($error->arrayPropertyPath) && $sub_element = NestedArray::getValue($element, $error->arrayPropertyPath)) {
      return $sub_element;
    }
    return $element;
  }


  /**
   * Special handling to validate form elements with multiple values.
   *
   * @param array $elements
   *   An associative array containing the substructure of the form to be
   *   validated in this call.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $form
   *   The complete form array.
   */
  public function multipleElementValidate(array $elements, FormStateInterface $form_state, array $form) {
    $field_name = $this->fieldDefinition->getName();
    $widget_state = static::getWidgetState($elements['#field_parents'], $field_name, $form_state);

    if ($elements['#required'] && $widget_state['real_item_count'] < 1) {
      $form_state->setError($elements, t('@name field is required.', ['@name' => $this->fieldDefinition->getLabel()]));
    }

    static::setWidgetState($elements['#field_parents'], $field_name, $form_state, $widget_state);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $widget_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
    $element = NestedArray::getValue($form_state->getCompleteForm(), $widget_state['array_parents']);

    if (!empty($widget_state['dragdrop'])) {
      $path = array_merge($form['#parents'], array($field_name));
      static::reorderParagraphs($form_state, $path);

      // After re-ordering, get the updated widget state.
      $widget_state = static::getWidgetState($form['#parents'], $field_name, $form_state);

      // Re-create values based on current widget state.
      $values = [];
      foreach ($widget_state['paragraphs'] as $delta => $paragraph_state) {
        $values[$delta]['entity'] = $paragraph_state['entity'];
      }
      return $values;
    }

    foreach ($values as $delta => &$item) {
      if (isset($widget_state['paragraphs'][$item['_original_delta']]['entity'])
        && $widget_state['paragraphs'][$item['_original_delta']]['mode'] != 'remove') {
        /** @var \Drupal\paragraphs\ParagraphInterface $paragraphs_entity */
        $paragraphs_entity = $widget_state['paragraphs'][$item['_original_delta']]['entity'];

        /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
        $display = $widget_state['paragraphs'][$item['_original_delta']]['display'];
        if ($widget_state['paragraphs'][$item['_original_delta']]['mode'] == 'edit') {
          $display->extractFormValues($paragraphs_entity, $element[$item['_original_delta']]['subform'], $form_state);
        }
        // A content entity form saves without any rebuild. It needs to set the
        // language to update it in case of language change.
        $langcode_key = $paragraphs_entity->getEntityType()->getKey('langcode');
        if ($paragraphs_entity->get($langcode_key)->value != $form_state->get('langcode')) {
          // If a translation in the given language already exists, switch to
          // that. If there is none yet, update the language.
          if ($paragraphs_entity->hasTranslation($form_state->get('langcode'))) {
            $paragraphs_entity = $paragraphs_entity->getTranslation($form_state->get('langcode'));
          }
          else {
            $paragraphs_entity->set($langcode_key, $form_state->get('langcode'));
          }
        }
        if (isset($item['behavior_plugins'])) {
          // Submit all enabled behavior plugins.
          $paragraphs_type = $paragraphs_entity->getParagraphType();
          foreach ($paragraphs_type->getEnabledBehaviorPlugins() as $plugin_id => $plugin_values) {
            if (!isset($item['behavior_plugins'][$plugin_id])) {
              $item['behavior_plugins'][$plugin_id] = [];
            }
            $original_delta = $item['_original_delta'];
            if (isset($element[$original_delta]) && isset($element[$original_delta]['behavior_plugins'][$plugin_id]) && $form_state->getCompleteForm() && \Drupal::currentUser()->hasPermission('edit behavior plugin settings')) {
              $subform_state = SubformState::createForSubform($element[$original_delta]['behavior_plugins'][$plugin_id], $form_state->getCompleteForm(), $form_state);
              if (isset($item['behavior_plugins'][$plugin_id])) {
                $plugin_values->submitBehaviorForm($paragraphs_entity, $item['behavior_plugins'][$plugin_id], $subform_state);
              }
            }
          }
        }

        // We can only use the entity form display to display validation errors
        // if it is in edit mode.
        if ($widget_state['paragraphs'][$item['_original_delta']]['mode'] === 'edit') {
          $display->validateFormValues($paragraphs_entity, $element[$item['_original_delta']]['subform'], $form_state);
        }
        // Assume that the entity is being saved/previewed, in this case,
        // validate even the closed paragraphs. If there are validation errors,
        // add them on the parent level. Validation errors do not rebuild the
        // form so it's not possible to auto-uncollapse the form at this point.
        elseif ($form_state->getLimitValidationErrors() === NULL) {
          $violations = $paragraphs_entity->validate();
          $violations->filterByFieldAccess();
          if (count($violations)) {
            foreach ($violations as $violation) {
              /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
              $form_state->setError($element[$item['_original_delta']], $violation->getMessage());
            }
          }
        }

        $paragraphs_entity->setNeedsSave(TRUE);
        $item['entity'] = $paragraphs_entity;
        $item['target_id'] = $paragraphs_entity->id();
        $item['target_revision_id'] = $paragraphs_entity->getRevisionId();
      }
      // If our mode is remove don't save or reference this entity.
      // @todo: Maybe we should actually delete it here?
      elseif (isset($widget_state['paragraphs'][$item['_original_delta']]['mode']) && $widget_state['paragraphs'][$item['_original_delta']]['mode'] == 'remove') {
        $item['target_id'] = NULL;
        $item['target_revision_id'] = NULL;
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    // Filter possible empty items.
    $items->filterEmptyItems();

    // Remove buttons from header actions.
    $field_name = $this->fieldDefinition->getName();
    $path = array_merge($form['#parents'], array($field_name));
    $form_state_variables = $form_state->getValues();
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state_variables, $path, $key_exists);

    if ($key_exists) {
      unset($values['header_actions']);

      NestedArray::setValue($form_state_variables, $path, $values);
      $form_state->setValues($form_state_variables);
    }

    return parent::extractFormValues($items, $form, $form_state);
  }

  /**
   * Determine if widget is in translation.
   *
   * Initializes $this->isTranslating.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\Core\Entity\ContentEntityInterface $host
   */
  protected function initIsTranslating(FormStateInterface $form_state, ContentEntityInterface $host) {
    if ($this->isTranslating != NULL) {
      return;
    }
    $this->isTranslating = FALSE;
    if (!$host->isTranslatable()) {
      return;
    }
    if (!$host->getEntityType()->hasKey('default_langcode')) {
      return;
    }
    $default_langcode_key = $host->getEntityType()->getKey('default_langcode');
    if (!$host->hasField($default_langcode_key)) {
      return;
    }

    // Supporting \Drupal\content_translation\Controller\ContentTranslationController.
    if (!empty($form_state->get('content_translation'))) {
      // Adding a translation.
      $this->isTranslating = TRUE;
    }
    $langcode = $form_state->get('langcode');
    if ($host->hasTranslation($langcode) && $host->getTranslation($langcode)->get($default_langcode_key)->value == 0) {
      // Editing a translation.
      $this->isTranslating = TRUE;
    }
  }

  /**
   * After-build callback for removing the translatability clue from the widget.
   *
   * If the fields on the paragraph type are translatable,
   * ContentTranslationHandler::addTranslatabilityClue()adds an
   * "(all languages)" suffix to the widget title. That suffix is incorrect and
   * is being removed by this method using a #after_build on the field widget.
   *
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public static function removeTranslatabilityClue(array $element, FormStateInterface $form_state) {
    // Widgets could have multiple elements with their own titles, so remove the
    // suffix if it exists, do not recurse lower than this to avoid going into
    // nested paragraphs or similar nested field types.
    $suffix = ' <span class="translation-entity-all-languages">(' . t('all languages') . ')</span>';
    if (isset($element['#title']) && strpos($element['#title'], $suffix)) {
      $element['#title'] = str_replace($suffix, '', $element['#title']);
    }
    // Loop over all widget deltas.
    foreach (Element::children($element) as $delta) {
      if (isset($element[$delta]['#title']) && strpos($element[$delta]['#title'], $suffix)) {
        $element[$delta]['#title'] = str_replace($suffix, '', $element[$delta]['#title']);
      }
      // Loop over all form elements within the current delta.
      foreach (Element::children($element[$delta]) as $field) {
        if (isset($element[$delta][$field]['#title']) && strpos($element[$delta][$field]['#title'], $suffix)) {
          $element[$delta][$field]['#title'] = str_replace($suffix, '', $element[$delta][$field]['#title']);
        }
      }
    }
    return $element;
  }

  /**
   * Returns the default paragraph type.
   *
   * @return string
   *   Label name for default paragraph type.
   */
  protected function getDefaultParagraphTypeLabelName() {
    if ($this->getDefaultParagraphTypeMachineName() !== NULL) {
      $allowed_types = $this->getAllowedTypes();
      return $allowed_types[$this->getDefaultParagraphTypeMachineName()]['label'];
    }

    return NULL;
  }

  /**
   * Returns the machine name for default paragraph type.
   *
   * @return string
   *   Machine name for default paragraph type.
   */
  protected function getDefaultParagraphTypeMachineName() {
    $default_type = $this->getSetting('default_paragraph_type');
    $allowed_types = $this->getAllowedTypes();
    if ($default_type && isset($allowed_types[$default_type])) {
      return $default_type;
    }
    // Check if the user explicitly selected not to have any default Paragraph
    // type. Othewise, if there is only one type available, that one is the
    // default.
    if ($default_type === '_none') {
      return NULL;
    }
    if (count($allowed_types) === 1) {
      return key($allowed_types);
    }

    return NULL;
  }

  /**
   * Counts the number of paragraphs in a certain mode in a form substructure.
   *
   * @param array $widget_state
   *   The widget state for the form substructure containing information about
   *   the paragraphs within.
   * @param string $mode
   *   The mode to look for.
   *
   * @return int
   *   The number of paragraphs is the given mode.
   */
  protected function getNumberOfParagraphsInMode(array $widget_state, $mode) {
    if (!isset($widget_state['paragraphs'])) {
      return 0;
    }

    $paragraphs_count = 0;
    foreach ($widget_state['paragraphs'] as $paragraph) {
      if ($paragraph['mode'] == $mode) {
        $paragraphs_count++;
      }
    }

    return $paragraphs_count;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_type = $field_definition->getSetting('target_type');
    $paragraph_type = \Drupal::entityTypeManager()->getDefinition($target_type);
    if ($paragraph_type) {
      return $paragraph_type->entityClassImplements(ParagraphInterface::class);
    }

    return FALSE;
  }

  /**
   * Builds header actions.
   *
   * @param array[] $field_state
   *   Field widget state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array[]
   *   The form element array.
   */
  public function buildHeaderActions(array $field_state, FormStateInterface $form_state) {
    $actions = [];
    $field_name = $this->fieldDefinition->getName();
    $id_prefix = implode('-', array_merge($this->fieldParents, [$field_name]));

    if (empty($this->fieldParents)) {
      // Only show the drag&drop mode if we have some items to actually drag
      // around and can find the sortable library.
      $library_discovery = \Drupal::service('library.discovery');
      $library = $library_discovery->getLibraryByName('paragraphs', 'paragraphs-dragdrop');
      if ($this->realItemCount > 0 && ($library || \Drupal::state()->get('paragraphs_test_dragdrop_force_show', FALSE))) {
        $actions['dropdown_actions']['dragdrop_mode'] = $this->expandButton([
          '#type' => 'submit',
          '#name' => $this->fieldIdPrefix . '_dragdrop_mode',
          '#value' => $this->t('Drag & drop'),
          '#attributes' => ['class' => ['field-dragdrop-mode-submit']],
          '#submit' => [[get_class($this), 'dragDropModeSubmit']],
          '#weight' => 8,
          '#ajax' => [
            'callback' => [get_class($this), 'dragDropModeAjax'],
            'wrapper' => $this->fieldWrapperId,
          ],
          '#limit_validation_errors' => [
            array_merge($this->fieldParents, [$field_name, 'dragdrop_mode']),
          ],
          '#access' => $this->allowReferenceChanges()
        ]);
      }
    }

    // Collapse & expand all.
    if ($this->fieldDefinition->getType() == 'entity_reference_revisions' &&  $this->realItemCount > 1 && $this->isFeatureEnabled('collapse_edit_all')) {
      $collapse_all = $this->expandButton([
        '#type' => 'submit',
        '#value' => $this->t('Collapse all'),
        '#submit' => [[get_class($this), 'changeAllEditModeSubmit']],
        '#name' => $id_prefix . '_collapse_all',
        '#paragraphs_mode' => 'closed',
        '#limit_validation_errors' => [
          array_merge($this->fieldParents, [$field_name, 'collapse_all']),
        ],
        '#ajax' => [
          'callback' => [get_class($this), 'allActionsAjax'],
          'wrapper' => $this->fieldWrapperId,
        ],
        '#weight' => -1,
        '#paragraphs_show_warning' => TRUE,
      ]);

      $edit_all = $this->expandButton([
        '#type' => 'submit',
        '#value' => $this->t('Edit all'),
        '#submit' => [[get_class($this), 'changeAllEditModeSubmit']],
        '#name' => $id_prefix . '_edit-all',
        '#paragraphs_mode' => 'edit',
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [get_class($this), 'allActionsAjax'],
          'wrapper' => $this->fieldWrapperId,
        ],
      ]);

      // Take the default edit mode if we don't have anything in state.
      $mode = isset($field_state['paragraphs'][0]['mode']) ? $field_state['paragraphs'][0]['mode'] : $this->settings['edit_mode'];

      // Depending on the state of the widget output close/edit all in the right
      // order and with the right settings.
      if ($mode === 'closed') {
        $edit_all['#attributes'] = [
          'class' => ['paragraphs-icon-button', 'paragraphs-icon-button-edit'],
          'title' => $this->t('Edit all'),
        ];
        $edit_all['#title'] = $this->t('Edit All');
        $actions['actions']['edit_all'] = $edit_all;
        $actions['dropdown_actions']['collapse_all'] = $collapse_all;
      }
      else {
        $collapse_all['#attributes'] = [
          'class' => ['paragraphs-icon-button', 'paragraphs-icon-button-collapse'],
          'title' => $this->t('Collapse all'),
        ];
        $actions['actions']['collapse_all'] = $collapse_all;
        $actions['dropdown_actions']['edit_all'] = $edit_all;
      }
    }

    // Add paragraphs_header flag which we use later in preprocessor to move
    // header actions to table header.
    if ($actions) {
      // Set actions.
      $actions['#type'] = 'paragraphs_actions';
      $actions['#paragraphs_header'] = TRUE;
    }

    return $actions;
  }

  /**
   * Loops through all paragraphs and change mode for each paragraph instance.
   *
   * @param array $form
   *   Current form state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public static function changeAllEditModeSubmit(array $form, FormStateInterface $form_state) {
    $submit = ParagraphsWidget::getSubmitElementInfo($form, $form_state, ParagraphsWidget::ACTION_POSITION_HEADER);

    // Change edit mode for each paragraph.
    foreach ($submit['widget_state']['paragraphs'] as $delta => &$paragraph) {
      if ($submit['widget_state']['paragraphs'][$delta]['mode'] !== 'remove') {
        $submit['widget_state']['paragraphs'][$delta]['mode'] = $submit['button']['#paragraphs_mode'];
        if (!empty($submit['button']['#paragraphs_show_warning'])) {
          $submit['widget_state']['paragraphs'][$delta]['show_warning'] = $submit['button']['#paragraphs_show_warning'];
        }
      }
    }

    if ($submit['widget_state']['autocollapse_default'] == 'all') {
      if ($submit['button']['#paragraphs_mode'] === 'edit') {
        $submit['widget_state']['autocollapse'] = 'none';
      }
      elseif ($submit['button']['#paragraphs_mode'] === 'closed') {
        $submit['widget_state']['autocollapse'] = 'all';
      }
    }

    static::setWidgetState($submit['parents'], $submit['field_name'], $form_state, $submit['widget_state']);
    $form_state->setRebuild();
  }

  /**
   * Returns a state with all paragraphs closed, if autocollapse is enabled.
   *
   * @param array $widget_state
   *   The current widget state.
   *
   * @return array
   *   The widget state altered by closing all paragraphs.
   */
  public static function autocollapse(array $widget_state) {
    if ($widget_state['real_item_count'] > 0 && $widget_state['autocollapse'] !== 'none') {
      foreach ($widget_state['paragraphs'] as $delta => $value) {
        if ($widget_state['paragraphs'][$delta]['mode'] === 'edit') {
          $widget_state['paragraphs'][$delta]['mode'] = 'closed';
        }
      }
    }

    return $widget_state;
  }

  /**
   * Checks if we can allow reference changes.
   *
   * @return bool
   *   TRUE if we can allow reference changes, otherwise FALSE.
   */
  protected function allowReferenceChanges() {
    return !$this->isTranslating;
  }

  /**
   * Check remove button access.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   Paragraphs entity to check.
   *
   * @return bool
   *   TRUE if we can remove paragraph, otherwise FALSE.
   */
  protected function removeButtonAccess(ParagraphInterface $paragraph) {
    if (!$paragraph->access('delete')) {
      return FALSE;
    }

    if (!$this->allowReferenceChanges()) {
      return FALSE;
    }

    $field_required = $this->fieldDefinition->isRequired();
    $allowed_types = $this->getAllowedTypes();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    // Hide the button if field is required, cardinality is one and just one
    // paragraph type is allowed.
    if ($field_required && $cardinality == 1 && (count($allowed_types) == 1)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check duplicate button access.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   Paragraphs entity to check.
   *
   * @return bool
   *   TRUE if we can duplicate the paragraph, otherwise FALSE.
   */
  protected function duplicateButtonAccess(ParagraphInterface $paragraph) {
    if (!$this->isFeatureEnabled('duplicate')) {
      return FALSE;
    }

    if (!$paragraph->access('update')) {
      return FALSE;
    }

    if (!$this->allowReferenceChanges()) {
      return FALSE;
    }

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    // Hide the button if field cardinality is one.
    if ($cardinality == 1) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Checks if a widget feature is enabled or not.
   *
   * @param string $feature
   *   Feature name to check.
   *
   * @return bool
   *   TRUE if the feature is enabled, otherwise FALSE.
   */
  protected function isFeatureEnabled($feature) {
    $features = $this->getSetting('features');
    if (!empty($features[$feature])) {
      return TRUE;
    }
    return FALSE;
  }

}
