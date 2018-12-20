<?php

namespace Drupal\paragraphs\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Element;
use Drupal\node\Entity\Node;
use Drupal\paragraphs;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Drupal\paragraphs\Plugin\EntityReferenceSelection\ParagraphSelection;


/**
 * Plugin implementation of the 'entity_reference paragraphs' widget.
 *
 * We hide add / remove buttons when translating to avoid accidental loss of
 * data because these actions effect all languages.
 *
 * @FieldWidget(
 *   id = "entity_reference_paragraphs",
 *   label = @Translation("Paragraphs Classic"),
 *   description = @Translation("A paragraphs inline form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class InlineParagraphsWidget extends WidgetBase {

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
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'title' => t('Paragraph'),
      'title_plural' => t('Paragraphs'),
      'edit_mode' => 'open',
      'add_mode' => 'dropdown',
      'form_display_mode' => 'default',
      'default_paragraph_type' => '',
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
      '#description' => $this->t('The mode the paragraph is in by default. Preview will render the paragraph in the preview view mode.'),
      '#options' => array(
        'open' => $this->t('Open'),
        'closed' => $this->t('Closed'),
        'preview' => $this->t('Preview'),
      ),
      '#default_value' => $this->getSetting('edit_mode'),
      '#required' => TRUE,
    );

    $elements['add_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Add mode'),
      '#description' => $this->t('The way to add new Paragraphs.'),
      '#options' => array(
        'select' => $this->t('Select list'),
        'button' => $this->t('Buttons'),
        'dropdown' => $this->t('Dropdown button')
      ),
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

    return $elements;
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

    switch($this->getSetting('edit_mode')) {
      case 'open':
      default:
        $edit_mode = $this->t('Open');
        break;
      case 'closed':
        $edit_mode = $this->t('Closed');
        break;
      case 'preview':
        $edit_mode = $this->t('Preview');
        break;
    }

    switch($this->getSetting('add_mode')) {
      case 'select':
      default:
        $add_mode = $this->t('Select list');
        break;
      case 'button':
        $add_mode = $this->t('Buttons');
        break;
      case 'dropdown':
        $add_mode = $this->t('Dropdown button');
        break;
    }

    $summary[] = $this->t('Edit mode: @edit_mode', ['@edit_mode' => $edit_mode]);
    $summary[] = $this->t('Add mode: @add_mode', ['@add_mode' => $add_mode]);
    $summary[] = $this->t('Form display mode: @form_display_mode', [
      '@form_display_mode' => $this->getSetting('form_display_mode')
    ]);
    if ($this->getDefaultParagraphTypeLabelName() !== NULL) {
      $summary[] = $this->t('Default paragraph type: @default_paragraph_type', [
        '@default_paragraph_type' => $this->getDefaultParagraphTypeLabelName()
      ]);
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
    $info = [];

    /** @var \Drupal\paragraphs\ParagraphInterface $paragraphs_entity */
    $paragraphs_entity = NULL;
    $host = $items->getEntity();
    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    $entity_manager = \Drupal::entityTypeManager();
    $target_type = $this->getFieldSetting('target_type');

    $item_mode = isset($widget_state['paragraphs'][$delta]['mode']) ? $widget_state['paragraphs'][$delta]['mode'] : 'edit';
    $default_edit_mode = $this->getSetting('edit_mode');

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
        elseif ($default_edit_mode == 'preview') {
          $item_mode = 'preview';
        }
      }
    }
    elseif (isset($widget_state['selected_bundle'])) {

      $entity_type = $entity_manager->getDefinition($target_type);
      $bundle_key = $entity_type->getKey('bundle');

      $paragraphs_entity = $entity_manager->getStorage($target_type)->create(array(
        $bundle_key => $widget_state['selected_bundle'],
      ));
      $paragraphs_entity->setParentEntity($items->getEntity(), $field_name);

      $item_mode = 'edit';
    }

    if ($item_mode == 'collapsed') {
      $item_mode = $default_edit_mode;
    }

    if ($item_mode == 'closed') {
      // Validate closed paragraphs and expand if needed.
      // @todo Consider recursion.
      $violations = $paragraphs_entity->validate();
      $violations->filterByFieldAccess();
      if (count($violations) > 0) {
        $item_mode = 'edit';
        $messages = [];
        foreach ($violations as $violation) {
          $messages[] = $violation->getMessage();
        }
        $info['validation_error'] = array(
          '#type' => 'container',
          '#markup' => $this->t('@messages', ['@messages' => strip_tags(implode('\n', $messages))]),
          '#attributes' => ['class' => ['messages', 'messages--warning']],
        );
      }
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
          // valid scenario to have no paragraphs items in the source version of
          // the host and fetching the translation without this check could lead
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

      $item_bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($target_type);
      if (isset($item_bundles[$paragraphs_entity->bundle()])) {
        $bundle_info = $item_bundles[$paragraphs_entity->bundle()];

        $element['top'] = array(
          '#type' => 'container',
          '#weight' => -1000,
          '#attributes' => array(
            'class' => array(
              'paragraph-type-top',
            ),
          ),
        );

        $element['top']['paragraph_type_title'] = array(
          '#type' => 'container',
          '#weight' => 0,
          '#attributes' => array(
            'class' => array(
              'paragraph-type-title',
            ),
          ),
        );

        $element['top']['paragraph_type_title']['info'] = array(
          '#markup' => $bundle_info['label'],
        );

        $actions = array();
        $links = array();

        // Hide the button when translating.
        $button_access = $paragraphs_entity->access('delete') && !$this->isTranslating;
        if ($item_mode != 'remove') {
          $links['remove_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => strtr($id_prefix, '-', '_') . '_remove',
            '#weight' => 501,
            '#submit' => [[get_class($this), 'paragraphsItemSubmit']],
            '#limit_validation_errors' => [array_merge($parents, [$field_name, 'add_more'])],
            '#delta' => $delta,
            '#ajax' => [
              'callback' => [get_class($this), 'itemAjax'],
              'wrapper' => $widget_state['ajax_wrapper_id'],
              'effect' => 'fade',
            ],
            '#access' => $button_access,
            '#prefix' => '<li class="remove">',
            '#suffix' => '</li>',
            '#paragraphs_mode' => 'remove',
          ];

        }

        if ($item_mode == 'edit') {

          if (isset($items[$delta]->entity) && ($default_edit_mode == 'preview' || $default_edit_mode == 'closed')) {
            $links['collapse_button'] = array(
              '#type' => 'submit',
              '#value' => $this->t('Collapse'),
              '#name' => strtr($id_prefix, '-', '_') . '_collapse',
              '#weight' => 499,
              '#submit' => array(array(get_class($this), 'paragraphsItemSubmit')),
              '#delta' => $delta,
              '#limit_validation_errors' => [array_merge($parents, [$field_name, 'add_more'])],
              '#ajax' => array(
                'callback' => array(get_class($this), 'itemAjax'),
                'wrapper' => $widget_state['ajax_wrapper_id'],
                'effect' => 'fade',
              ),
              '#access' => $paragraphs_entity->access('update'),
              '#prefix' => '<li class="collapse">',
              '#suffix' => '</li>',
              '#paragraphs_mode' => 'collapsed',
              '#paragraphs_show_warning' => TRUE,
            );
          }

          $info['edit_button_info'] = array(
            '#type' => 'container',
            '#markup' => $this->t('You are not allowed to edit this @title.', array('@title' => $this->getSetting('title'))),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$paragraphs_entity->access('update') && $paragraphs_entity->access('delete'),
          );

          $info['remove_button_info'] = array(
            '#type' => 'container',
            '#markup' => $this->t('You are not allowed to remove this @title.', array('@title' => $this->getSetting('title'))),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$paragraphs_entity->access('delete') && $paragraphs_entity->access('update'),
          );

          $info['edit_remove_button_info'] = array(
            '#type' => 'container',
            '#markup' => $this->t('You are not allowed to edit or remove this @title.', array('@title' => $this->getSetting('title'))),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$paragraphs_entity->access('update') && !$paragraphs_entity->access('delete'),
          );
        }
        elseif ($item_mode == 'preview' || $item_mode == 'closed') {
          $links['edit_button'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Edit'),
            '#name' => strtr($id_prefix, '-', '_') . '_edit',
            '#weight' => 500,
            '#submit' => array(array(get_class($this), 'paragraphsItemSubmit')),
            '#limit_validation_errors' => array(array_merge($parents, array($field_name, 'add_more'))),
            '#delta' => $delta,
            '#ajax' => array(
              'callback' => array(get_class($this), 'itemAjax'),
              'wrapper' => $widget_state['ajax_wrapper_id'],
              'effect' => 'fade',
            ),
            '#access' => $paragraphs_entity->access('update'),
            '#prefix' => '<li class="edit">',
            '#suffix' => '</li>',
            '#paragraphs_mode' => 'edit',
          );

          if ($show_must_be_saved_warning) {
            $info['must_be_saved_info'] = array(
              '#type' => 'container',
              '#markup' => $this->t('You have unsaved changes on this @title item.', array('@title' => $this->getSetting('title'))),
              '#attributes' => ['class' => ['messages', 'messages--warning']],
            );
          }

          $info['preview_info'] = array(
            '#type' => 'container',
            '#markup' => $this->t('You are not allowed to view this @title.', array('@title' => $this->getSetting('title'))),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$paragraphs_entity->access('view'),
          );

          $info['edit_button_info'] = array(
            '#type' => 'container',
            '#markup' => $this->t('You are not allowed to edit this @title.', array('@title' => $this->getSetting('title'))),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$paragraphs_entity->access('update') && $paragraphs_entity->access('delete'),
          );

          $info['remove_button_info'] = array(
            '#type' => 'container',
            '#markup' => $this->t('You are not allowed to remove this @title.', array('@title' => $this->getSetting('title'))),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$paragraphs_entity->access('delete') && $paragraphs_entity->access('update'),
          );

          $info['edit_remove_button_info'] = array(
            '#type' => 'container',
            '#markup' => $this->t('You are not allowed to edit or remove this @title.', array('@title' => $this->getSetting('title'))),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$paragraphs_entity->access('update') && !$paragraphs_entity->access('delete'),
          );
        }
        elseif ($item_mode == 'remove') {

          $element['top']['paragraph_type_title']['info'] = [
            '#markup' => $this->t('Deleted @title: %type', ['@title' => $this->getSetting('title'), '%type' => $bundle_info['label']]),
          ];

          $links['confirm_remove_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Confirm removal'),
            '#name' => strtr($id_prefix, '-', '_') . '_confirm_remove',
            '#weight' => 503,
            '#submit' => [[get_class($this), 'paragraphsItemSubmit']],
            '#limit_validation_errors' => [array_merge($parents, [$field_name, 'add_more'])],
            '#delta' => $delta,
            '#ajax' => [
              'callback' => [get_class($this), 'itemAjax'],
              'wrapper' => $widget_state['ajax_wrapper_id'],
              'effect' => 'fade',
            ],
            '#prefix' => '<li class="confirm-remove">',
            '#suffix' => '</li>',
            '#paragraphs_mode' => 'removed',
          ];

          $links['restore_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Restore'),
            '#name' => strtr($id_prefix, '-', '_') . '_restore',
            '#weight' => 504,
            '#submit' => [[get_class($this), 'paragraphsItemSubmit']],
            '#limit_validation_errors' => [array_merge($parents, [$field_name, 'add_more'])],
            '#delta' => $delta,
            '#ajax' => [
              'callback' => [get_class($this), 'itemAjax'],
              'wrapper' => $widget_state['ajax_wrapper_id'],
              'effect' => 'fade',
            ],
            '#prefix' => '<li class="restore">',
            '#suffix' => '</li>',
            '#paragraphs_mode' => 'edit',
          ];
        }

        if (count($links)) {
          $show_links = 0;
          foreach($links as $link_item) {
            if (!isset($link_item['#access']) || $link_item['#access']) {
              $show_links++;
            }
          }

          if ($show_links > 0) {

            $element['top']['links'] = $links;
            if ($show_links > 1) {
              $element['top']['links']['#theme_wrappers'] = array('dropbutton_wrapper', 'paragraphs_dropbutton_wrapper');
              $element['top']['links']['prefix'] = array(
                '#markup' => '<ul class="dropbutton">',
                '#weight' => -999,
              );
              $element['top']['links']['suffix'] = array(
                '#markup' => '</li>',
                '#weight' => 999,
              );
            }
            else {
              $element['top']['links']['#theme_wrappers'] = array('paragraphs_dropbutton_wrapper');
              foreach($links as $key => $link_item) {
                unset($element['top']['links'][$key]['#prefix']);
                unset($element['top']['links'][$key]['#suffix']);
              }
            }
            $element['top']['links']['#weight'] = 2;
          }
        }

        if (count($info)) {
          $show_info = FALSE;
          foreach($info as $info_item) {
            if (!isset($info_item['#access']) || $info_item['#access']) {
              $show_info = TRUE;
              break;
            }
          }

          if ($show_info) {
            $element['info'] = $info;
            $element['info']['#weight'] = 998;
          }
        }

        if (count($actions)) {
          $show_actions = FALSE;
          foreach($actions as $action_item) {
            if (!isset($action_item['#access']) || $action_item['#access']) {
              $show_actions = TRUE;
              break;
            }
          }

          if ($show_actions) {
            $element['actions'] = $actions;
            $element['actions']['#type'] = 'actions';
            $element['actions']['#weight'] = 999;
          }
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
        $hide_untranslatable_fields = $paragraphs_entity->isDefaultTranslationAffectedOnly();
        foreach (Element::children($element['subform']) as $field) {
          if ($paragraphs_entity->hasField($field)) {
            $field_definition = $paragraphs_entity->getFieldDefinition($field);
            $translatable = $paragraphs_entity->{$field}->getFieldDefinition()->isTranslatable();

            // Do a check if we have to add a class to the form element. We need
            // those classes (paragraphs-content and paragraphs-behavior) to show
            // and hide elements, depending of the active perspective.
            // We need them to filter out entity reference revisions fields that
            // reference paragraphs, cause otherwise we have problems with showing
            // and hiding the right fields in nested paragraphs.
            $is_paragraph_field = FALSE;
            if ($field_definition->getType() == 'entity_reference_revisions') {
              // Check if we are referencing paragraphs.
              if ($field_definition->getSetting('target_type') == 'paragraph') {
                $is_paragraph_field = TRUE;
              }
            }

            // Hide untranslatable fields when configured to do so except
            // paragraph fields.
            if (!$translatable && $this->isTranslating && !$is_paragraph_field) {
              if ($hide_untranslatable_fields) {
                $element['subform'][$field]['#access'] = FALSE;
              }
              else {
                $element['subform'][$field]['widget']['#after_build'][] = [
                  static::class,
                  'addTranslatabilityClue'
                ];
              }
            }
          }
        }
      }
      elseif ($item_mode == 'preview') {
        $element['subform'] = array();
        $element['behavior_plugins'] = [];
        $element['preview'] = entity_view($paragraphs_entity, 'preview', $paragraphs_entity->language()->getId());
        $element['preview']['#access'] = $paragraphs_entity->access('view');
      }
      elseif ($item_mode == 'closed') {
        $element['subform'] = array();
        $element['behavior_plugins'] = [];
        if ($paragraphs_entity) {
          $summary = $paragraphs_entity->getSummary();
          $element['top']['paragraph_summary']['fields_info'] = [
            '#markup' => $summary,
            '#prefix' => '<div class="paragraphs-collapsed-description">',
            '#suffix' => '</div>',
          ];
        }
      }
      else {
        $element['subform'] = array();
      }

      $element['subform']['#attributes']['class'][] = 'paragraphs-subform';
      $element['subform']['#access'] = $paragraphs_entity->access('update');

      if ($item_mode == 'removed') {
        $element['#access'] = FALSE;
      }

      $widget_state['paragraphs'][$delta]['entity'] = $paragraphs_entity;
      $widget_state['paragraphs'][$delta]['display'] = $display;
      $widget_state['paragraphs'][$delta]['mode'] = $item_mode;

      static::setWidgetState($parents, $field_name, $form_state, $widget_state);
    }
    else {
      $element['#access'] = FALSE;
    }

    return $element;
  }

  /**
   * Returns the sorted allowed types for a entity reference field.
   *
   * @return array
   *   A list of arrays keyed by the paragraph type machine name with the following properties.
   *     - label: The label of the paragraph type.
   *     - weight: The weight of the paragraph type.
   */
  public function getAllowedTypes() {

    $return_bundles = array();
    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager */
    $selection_manager = \Drupal::service('plugin.manager.entity_reference_selection');
    $handler = $selection_manager->getSelectionHandler($this->fieldDefinition);
    if ($handler instanceof ParagraphSelection) {
      $return_bundles = $handler->getSortedAllowedTypes();
    }
    // Support for other reference types.
    else {
      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($this->getFieldSetting('target_type'));
      $weight = 0;
      foreach ($bundles as $machine_name => $bundle) {
        if (empty($this->getSelectionHandlerSetting('target_bundles'))
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

    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    $elements = array();
    $this->fieldIdPrefix = implode('-', array_merge($this->fieldParents, array($field_name)));
    $this->fieldWrapperId = Html::getUniqueId($this->fieldIdPrefix . '-add-more-wrapper');
    $elements['#prefix'] = '<div id="' . $this->fieldWrapperId . '">';
    $elements['#suffix'] = '</div>';

    $field_state['ajax_wrapper_id'] = $this->fieldWrapperId;
    // Persist the widget state so formElement() can access it.
    static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);

    if ($max > 0) {
      for ($delta = 0; $delta < $max; $delta++) {

        // Add a new empty item if it doesn't exist yet at this delta.
        if (!isset($items[$delta])) {
          $items->appendItem();
        }

        // For multiple fields, title and description are handled by the wrapping
        // table.
        $element = array(
          '#title' => $is_multiple ? '' : $title,
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
    static::setWidgetState($this->fieldParents, $field_name, $form_state, $field_state);

    $elements += [
      '#element_validate' => [[$this, 'multipleElementValidate']],
      '#required' => $this->fieldDefinition->isRequired(),
      '#field_name' => $field_name,
      '#cardinality' => $cardinality,
      '#max_delta' => $max - 1,
    ];

    if ($this->realItemCount > 0) {
      $elements += array(
        '#theme' => 'field_multiple_value_form',
        '#cardinality_multiple' => $is_multiple,
        '#title' => $title,
        '#description' => $description,
      );
    }
    else {
      $classes = $this->fieldDefinition->isRequired() ? ['form-required'] : [];
      $elements += [
        '#type' => 'container',
        '#theme_wrappers' => ['container'],
        '#cardinality_multiple' => TRUE,
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => $title,
          '#attributes' => ['class' => $classes],
        ],
        'text' => [
          '#type' => 'container',
          'value' => [
            '#markup' => $this->t('No @title added yet.', ['@title' => $this->getSetting('title')]),
            '#prefix' => '<em>',
            '#suffix' => '</em>',
          ]
        ],
      ];

      if ($this->fieldDefinition->isRequired()) {
        $elements['title']['#attributes']['class'][] = 'form-required';
      }

      if ($description) {
        $elements['description'] = [
          '#type' => 'container',
          'value' => ['#markup' => $description],
          '#attributes' => ['class' => ['description']],
        ];
      }
    }

    $host = $items->getEntity();
    $this->initIsTranslating($form_state, $host);

    if (($this->realItemCount < $cardinality || $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) && !$form_state->isProgrammed() && !$this->isTranslating) {
      $elements['add_more'] = $this->buildAddActions();
    }

    $elements['#attached']['library'][] = 'paragraphs/drupal.paragraphs.admin';

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

    $elements = parent::form($items, $form, $form_state, $get_delta);

    // Signal to content_translation that this field should be treated as
    // multilingual and not be hidden, see
    // \Drupal\content_translation\ContentTranslationHandler::entityFormSharedElements().
    $elements['#multilingual'] = TRUE;
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
        $add_more_elements['info'] = [
          '#type' => 'container',
          '#markup' => $this->t('You are not allowed to add any of the @title types.', ['@title' => $this->getSetting('title')]),
          '#attributes' => ['class' => ['messages', 'messages--warning']],
        ];
      }
      else {
        $add_more_elements['info'] = [
          '#type' => 'container',
          '#markup' => $this->t('You did not add any @title types yet.', ['@title' => $this->getSetting('title')]),
          '#attributes' => ['class' => ['messages', 'messages--warning']],
        ];
      }

      return $add_more_elements ;
    }

    if ($this->getSetting('add_mode') == 'button' || $this->getSetting('add_mode') == 'dropdown') {
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
      if ($dragdrop_settings || (empty($this->getSelectionHandlerSetting('target_bundles'))
          || in_array($machine_name, $this->getSelectionHandlerSetting('target_bundles')))) {
        if ($access_control_handler->createAccess($machine_name)) {
          $this->accessOptions[$machine_name] = $bundle['label'];
        }
      }
    }

    return $this->accessOptions;
  }

  /**
   * Builds dropdown button for adding new paragraph.
   *
   * @return array
   *   The form element array.
   */
  protected function buildButtonsAddMode() {
    // Hide the button when translating.
    $add_more_elements = [
      '#type' => 'container',
      '#theme_wrappers' => ['paragraphs_dropbutton_wrapper'],
    ];
    $field_name = $this->fieldDefinition->getName();
    $title = $this->fieldDefinition->getLabel();

    $drop_button = FALSE;
    if (count($this->getAccessibleOptions()) > 1 && $this->getSetting('add_mode') == 'dropdown') {
      $drop_button = TRUE;
      $add_more_elements['#theme_wrappers'] = ['dropbutton_wrapper'];
      $add_more_elements['prefix'] = [
        '#markup' => '<ul class="dropbutton">',
        '#weight' => -999,
      ];
      $add_more_elements['suffix'] = [
        '#markup' => '</ul>',
        '#weight' => 999,
      ];
      $add_more_elements['#suffix'] = $this->t(' to %type', ['%type' => $title]);
    }

    foreach ($this->getAccessibleOptions() as $machine_name => $label) {
      $add_more_elements['add_more_button_' . $machine_name] = [
        '#type' => 'submit',
        '#name' => strtr($this->fieldIdPrefix, '-', '_') . '_' . $machine_name . '_add_more',
        '#value' => $this->t('Add @type', ['@type' => $label]),
        '#attributes' => ['class' => ['field-add-more-submit']],
        '#limit_validation_errors' => [array_merge($this->fieldParents, [$field_name, 'add_more'])],
        '#submit' => [[get_class($this), 'addMoreSubmit']],
        '#ajax' => [
          'callback' => [get_class($this), 'addMoreAjax'],
          'wrapper' => $this->fieldWrapperId,
          'effect' => 'fade',
        ],
        '#bundle_machine_name' => $machine_name,
      ];

      if ($drop_button) {
        $add_more_elements['add_more_button_' . $machine_name]['#prefix'] = '<li>';
        $add_more_elements['add_more_button_' . $machine_name]['#suffix'] = '</li>';
      }
    }

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
    $title = $this->fieldDefinition->getLabel();
    $add_more_elements['add_more_select'] = [
      '#type' => 'select',
      '#options' => $this->getAccessibleOptions(),
      '#title' => $this->t('@title type', ['@title' => $this->getSetting('title')]),
      '#label_display' => 'hidden',
    ];

    $text = $this->t('Add @title', ['@title' => $this->getSetting('title')]);

    if ($this->realItemCount > 0) {
      $text = $this->t('Add another @title', ['@title' => $this->getSetting('title')]);
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

    $add_more_elements['add_more_button']['#suffix'] = $this->t(' to %type', ['%type' => $title]);
    return $add_more_elements;
  }

  /**
   * Gets current language code from the form state or item.
   *
   * Since the paragraph field is not set as translatable, the item language
   * code is set to the source language. The intended translation language
   * is only accessibly through the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   * @return string
   */
  protected function getCurrentLangcode(FormStateInterface $form_state, FieldItemListInterface $items) {
    return $form_state->get('langcode') ?: $items->getEntity()->language()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $element['#max_delta'];
    $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
    $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Increment the items count.
    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    if ($widget_state['real_item_count'] < $element['#cardinality'] || $element['#cardinality'] == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $widget_state['items_count']++;
    }

    if (isset($button['#bundle_machine_name'])) {
      $widget_state['selected_bundle'] = $button['#bundle_machine_name'];
    }
    else {
      $widget_state['selected_bundle'] = $element['add_more']['add_more_select']['#value'];
    }

    static::setWidgetState($parents, $field_name, $form_state, $widget_state);

    $form_state->setRebuild();
  }

  public static function paragraphsItemSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));

    $delta = array_slice($button['#array_parents'], -4, -3);
    $delta = $delta[0];

    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    $widget_state['paragraphs'][$delta]['mode'] = $button['#paragraphs_mode'];

    if (!empty($button['#paragraphs_show_warning'])) {
      $widget_state['paragraphs'][$delta]['show_warning'] = $button['#paragraphs_show_warning'];
    }

    static::setWidgetState($parents, $field_name, $form_state, $widget_state);

    $form_state->setRebuild();
  }

  public static function itemAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));

    $element['#prefix'] = '<div class="ajax-new-content">' . (isset($element['#prefix']) ? $element['#prefix'] : '');
    $element['#suffix'] = (isset($element['#suffix']) ? $element['#suffix'] : '') . '</div>';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element;
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
   * Checks whether a content entity is referenced.
   *
   * @return bool
   */
  protected function isContentReferenced() {
    $target_type = $this->getFieldSetting('target_type');
    $target_type_info = \Drupal::entityTypeManager()->getDefinition($target_type);
    return $target_type_info->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface');
  }

  /**
   * {@inheritdoc}
   */
  public function elementValidate($element, FormStateInterface $form_state, $form) {
    $field_name = $this->fieldDefinition->getName();
    $widget_state = static::getWidgetState($element['#field_parents'], $field_name, $form_state);
    $delta = $element['#delta'];

    if (isset($widget_state['paragraphs'][$delta]['entity'])) {
      $entity = $widget_state['paragraphs'][$delta]['entity'];

      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
      $display = $widget_state['paragraphs'][$delta]['display'];

      if ($widget_state['paragraphs'][$delta]['mode'] == 'edit') {
        // Extract the form values on submit for getting the current paragraph.
        $display->extractFormValues($entity, $element['subform'], $form_state);
        $display->validateFormValues($entity, $element['subform'], $form_state);
      }
    }

    static::setWidgetState($element['#field_parents'], $field_name, $form_state, $widget_state);
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

    $remove_mode_item_count = $this->getNumberOfParagraphsInMode($widget_state, 'remove');
    $non_remove_mode_item_count = $widget_state['real_item_count'] - $remove_mode_item_count;

    if ($elements['#required'] && $non_remove_mode_item_count < 1) {
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

    foreach ($values as $delta => &$item) {
      if (isset($widget_state['paragraphs'][$item['_original_delta']]['entity'])
        && $widget_state['paragraphs'][$item['_original_delta']]['mode'] != 'remove') {
        $paragraphs_entity = $widget_state['paragraphs'][$item['_original_delta']]['entity'];

        /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
        $display =  $widget_state['paragraphs'][$item['_original_delta']]['display'];
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

        $paragraphs_entity->setNeedsSave(TRUE);
        $item['entity'] = $paragraphs_entity;
        $item['target_id'] = $paragraphs_entity->id();
        $item['target_revision_id'] = $paragraphs_entity->getRevisionId();
      }
      // If our mode is remove don't save or reference this entity.
      // @todo: Maybe we should actually delete it here?
      elseif($widget_state['paragraphs'][$item['_original_delta']]['mode'] == 'remove' || $widget_state['paragraphs'][$item['_original_delta']]['mode'] == 'removed') {
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
    return parent::extractFormValues($items, $form, $form_state);
  }

  /**
   * Initializes the translation form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\Core\Entity\EntityInterface $host
   */
  protected function initIsTranslating(FormStateInterface $form_state, EntityInterface $host) {
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

    if (!empty($form_state->get('content_translation'))) {
      // Adding a language through the ContentTranslationController.
      $this->isTranslating = TRUE;
    }
    if ($host->hasTranslation($form_state->get('langcode')) && $host->getTranslation($form_state->get('langcode'))->get($default_langcode_key)->value == 0) {
      // Editing a translation.
      $this->isTranslating = TRUE;
    }
  }

  /**
   * After-build callback for adding the translatability clue from the widget.
   *
   * ContentTranslationHandler::addTranslatabilityClue() adds an
   * "(all languages)" suffix to the widget title, replicate that here.
   *
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public static function addTranslatabilityClue(array $element, FormStateInterface $form_state) {
    static $suffix, $fapi_title_elements;

    // Widgets could have multiple elements with their own titles, so remove the
    // suffix if it exists, do not recurse lower than this to avoid going into
    // nested paragraphs or similar nested field types.
    // Elements which can have a #title attribute according to FAPI Reference.
    if (!isset($suffix)) {
      $suffix = ' <span class="translation-entity-all-languages">(' . t('all languages') . ')</span>';
      $fapi_title_elements = array_flip(['checkbox', 'checkboxes', 'date', 'details', 'fieldset', 'file', 'item', 'password', 'password_confirm', 'radio', 'radios', 'select', 'textarea', 'textfield', 'weight']);
    }

    // Update #title attribute for all elements that are allowed to have a
    // #title attribute according to the Form API Reference. The reason for this
    // check is because some elements have a #title attribute even though it is
    // not rendered; for instance, field containers.
    if (isset($element['#type']) && isset($fapi_title_elements[$element['#type']]) && isset($element['#title'])) {
      $element['#title'] .= $suffix;
    }
    // If the current element does not have a (valid) title, try child elements.
    elseif ($children = Element::children($element)) {
      foreach ($children as $delta) {
        $element[$delta] = static::addTranslatabilityClue($element[$delta], $form_state);
      }
    }
    // If there are no children, fall back to the current #title attribute if it
    // exists.
    elseif (isset($element['#title'])) {
      $element['#title'] .= $suffix;
    }
    return $element;
  }

  /**
   * Returns the default paragraph type.
   *
   * @return string $default_paragraph_type
   *   Label name for default paragraph type.
   */
  protected function getDefaultParagraphTypeLabelName(){
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
      return $paragraph_type->isSubclassOf(ParagraphInterface::class);
    }

    return FALSE;
  }

}
