<?php

namespace Drupal\d_p\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Element;
use Drupal\paragraphs;


/**
 * Droopler implementation of the 'entity_reference paragraphs' widget.
 *
 * We hide add mockup paragraph.
 *
 * @FieldWidget(
 *   id = "droopler_paragraphs",
 *   label = @Translation("Droopler Paragraphs"),
 *   description = @Translation("A paragraphs inline form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class DpParagraphsWidget extends paragraphs\Plugin\Field\FieldWidget\InlineParagraphsWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'title' => t('Paragraph'),
      'title_plural' => t('Paragraphs'),
      'edit_mode' => 'open',
      'add_mode' => 'dropdown',
      'form_display_mode' => 'default',
      'default_paragraph_type' => '',
      'show_placeholders' => 'yes',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Paragraph Title'),
      '#description' => $this->t(
        'Label to appear as title on the button as "Add new [title]", this label is translatable'
      ),
      '#default_value' => $this->getSetting('title'),
      '#required' => TRUE,
    ];

    $elements['title_plural'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plural Paragraph Title'),
      '#description' => $this->t('Title in its plural form.'),
      '#default_value' => $this->getSetting('title_plural'),
      '#required' => TRUE,
    ];

    $elements['edit_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Edit mode'),
      '#description' => $this->t(
        'The mode the paragraph is in by default. Preview will render the paragraph in the preview view mode.'
      ),
      '#options' => [
        'open' => $this->t('Open'),
        'closed' => $this->t('Closed'),
        'preview' => $this->t('Preview'),
      ],
      '#default_value' => $this->getSetting('edit_mode'),
      '#required' => TRUE,
    ];

    $elements['show_placeholders'] = [
      '#type' => 'select',
      '#title' => $this->t('Show placeholders'),
      '#description' => $this->t('Show mock-up paragraph'),
      '#options' => [
        'yes' => $this->t('Yes'),
        'no' => $this->t('No'),
      ],
      '#default_value' => $this->getSetting('show_placeholders'),
      '#required' => TRUE,
    ];

    $elements['add_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Add mode'),
      '#description' => $this->t('The way to add new Paragraphs.'),
      '#options' => [
        'select' => $this->t('Select list'),
        'button' => $this->t('Buttons'),
        'dropdown' => $this->t('Dropdown button')
      ],
      '#default_value' => $this->getSetting('add_mode'),
      '#required' => TRUE,
    ];

    $elements['form_display_mode'] = [
      '#type' => 'select',
      '#options' => \Drupal::service('entity_display.repository')
        ->getFormModeOptions($this->getFieldSetting('target_type')),
      '#description' => $this->t(
        'The form display mode to use when rendering the paragraph form.'
      ),
      '#title' => $this->t('Form display mode'),
      '#default_value' => $this->getSetting('form_display_mode'),
      '#required' => TRUE,
    ];

    $options = [];
    foreach ($this->getAllowedTypes() as $key => $bundle) {
      $options[$key] = $bundle['label'];
    }

    $elements['default_paragraph_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default paragraph type'),
      '#empty_value' => '_none',
      '#default_value' => $this->getDefaultParagraphTypeMachineName(),
      '#options' => $options,
      '#description' => $this->t(
        'When creating a new host entity, a paragraph of this type is added.'
      ),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\content_translation\Controller\ContentTranslationController::prepareTranslation()
   *   Uses a similar approach to populate a new translation.
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $element['#field_parents'];
    $info = [];

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

      $paragraphs_entity = $entity_manager->getStorage($target_type)->create(
        [
          $bundle_key => $widget_state['selected_bundle'],
        ]
      );
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

      $id_prefix = implode('-', array_merge($parents, [$field_name, $delta]));
      $wrapper_id = Html::getUniqueId($id_prefix . '-item-wrapper');

      $element += [
        '#type' => 'container',
        '#element_validate' => [[$this, 'elementValidate']],
        '#paragraph_type' => $paragraphs_entity->bundle(),
        'subform' => [
          '#type' => 'container',
          '#parents' => $element_parents,
        ],
      ];

      $element['#prefix'] = '<div id="' . $wrapper_id . '">';
      $element['#suffix'] = '</div>';

      $item_bundles = \Drupal::service('entity_type.bundle.info')
        ->getBundleInfo($target_type);
      if (isset($item_bundles[$paragraphs_entity->bundle()])) {
        $bundle_info = $item_bundles[$paragraphs_entity->bundle()];

        $element['top'] = [
          '#type' => 'container',
          '#weight' => -1000,
          '#attributes' => [
            'class' => [
              'paragraph-type-top',
            ],
          ],
        ];

        if ($this->settings['show_placeholders'] == 'yes') {
          $place_holder_link = drupal_get_path(
              'module',
              $paragraphs_entity->bundle()
            ) . '/placeholder.jpg';
          $element['placeholder'] = [
            '#type' => 'container',
            '#weight' => -2000,
            '#attributes' => [
              'class' => [
                'float-left',
              ],
            ],
          ];

          $title = '';
          if ($paragraphs_entity->hasField('field_d_main_title')) {
            $title = $paragraphs_entity->get('field_d_main_title');
            $title = $title->getValue()[0]['value'];
          }
          $img = '';
          if (file_exists($place_holder_link)) {
            $img = "<img class='paragraph-placeholder' src='/" . $place_holder_link . "'>";
          }
          $element['#attached']['library'][] = 'd_p/d_p_admin';
          $element['top']['placeholder']['#prefix'] = '<div class="paragraph-info-wrapper">' . $img . '<h5>' . $title . '</h5>';
          $element['top']['placeholder']['#prefix'] .= '<p>' . $this->t('Type:') . ' ' . $bundle_info['label'] . '</p>';
          $element['top']['placeholder']['#suffix'] = '</div>';
        }

        $actions = [];
        $links = [];

        // Hide the button when translating.
        $button_access = $paragraphs_entity->access(
            'delete'
          ) && !$this->isTranslating;
        if ($item_mode != 'remove') {
          $links['remove_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => strtr($id_prefix, '-', '_') . '_remove',
            '#weight' => 501,
            '#submit' => [[get_class($this), 'paragraphsItemSubmit']],
            '#limit_validation_errors' => [
              array_merge(
                $parents,
                [$field_name, 'add_more']
              )
            ],
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
            $links['collapse_button'] = [
              '#type' => 'submit',
              '#value' => $this->t('Collapse'),
              '#name' => strtr($id_prefix, '-', '_') . '_collapse',
              '#weight' => 499,
              '#submit' => [[get_class($this), 'paragraphsItemSubmit']],
              '#delta' => $delta,
              '#limit_validation_errors' => [
                array_merge(
                  $parents,
                  [$field_name, 'add_more']
                )
              ],
              '#ajax' => [
                'callback' => [get_class($this), 'itemAjax'],
                'wrapper' => $widget_state['ajax_wrapper_id'],
                'effect' => 'fade',
              ],
              '#access' => $paragraphs_entity->access('update'),
              '#prefix' => '<li class="collapse">',
              '#suffix' => '</li>',
              '#paragraphs_mode' => 'collapsed',
              '#paragraphs_show_warning' => TRUE,
            ];
          }

          $info['edit_button_info'] = [
            '#type' => 'container',
            '#markup' => $this->t(
              'You are not allowed to edit this @title.',
              ['@title' => $this->getSetting('title')]
            ),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$paragraphs_entity->access(
                'update'
              ) && $paragraphs_entity->access('delete'),
          ];

          $info['remove_button_info'] = [
            '#type' => 'container',
            '#markup' => $this->t(
              'You are not allowed to remove this @title.',
              ['@title' => $this->getSetting('title')]
            ),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$paragraphs_entity->access(
                'delete'
              ) && $paragraphs_entity->access('update'),
          ];

          $info['edit_remove_button_info'] = [
            '#type' => 'container',
            '#markup' => $this->t(
              'You are not allowed to edit or remove this @title.',
              ['@title' => $this->getSetting('title')]
            ),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$paragraphs_entity->access(
                'update'
              ) && !$paragraphs_entity->access('delete'),
          ];
        }
        elseif ($item_mode == 'preview' || $item_mode == 'closed') {
          $links['edit_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Edit'),
            '#name' => strtr($id_prefix, '-', '_') . '_edit',
            '#weight' => 500,
            '#submit' => [[get_class($this), 'paragraphsItemSubmit']],
            '#limit_validation_errors' => [
              array_merge(
                $parents,
                [$field_name, 'add_more']
              )
            ],
            '#delta' => $delta,
            '#ajax' => [
              'callback' => [get_class($this), 'itemAjax'],
              'wrapper' => $widget_state['ajax_wrapper_id'],
              'effect' => 'fade',
            ],
            '#access' => $paragraphs_entity->access('update'),
            '#prefix' => '<li class="edit">',
            '#suffix' => '</li>',
            '#paragraphs_mode' => 'edit',
          ];

          if ($show_must_be_saved_warning) {
            $info['must_be_saved_info'] = [
              '#type' => 'container',
              '#markup' => $this->t(
                'You have unsaved changes on this @title item.',
                ['@title' => $this->getSetting('title')]
              ),
              '#attributes' => ['class' => ['messages', 'messages--warning']],
            ];
          }

          $info['preview_info'] = [
            '#type' => 'container',
            '#markup' => $this->t(
              'You are not allowed to view this @title.',
              ['@title' => $this->getSetting('title')]
            ),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$paragraphs_entity->access('view'),
          ];

          $info['edit_button_info'] = [
            '#type' => 'container',
            '#markup' => $this->t(
              'You are not allowed to edit this @title.',
              ['@title' => $this->getSetting('title')]
            ),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$paragraphs_entity->access(
                'update'
              ) && $paragraphs_entity->access('delete'),
          ];

          $info['remove_button_info'] = [
            '#type' => 'container',
            '#markup' => $this->t(
              'You are not allowed to remove this @title.',
              ['@title' => $this->getSetting('title')]
            ),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$paragraphs_entity->access(
                'delete'
              ) && $paragraphs_entity->access('update'),
          ];

          $info['edit_remove_button_info'] = [
            '#type' => 'container',
            '#markup' => $this->t(
              'You are not allowed to edit or remove this @title.',
              ['@title' => $this->getSetting('title')]
            ),
            '#attributes' => ['class' => ['messages', 'messages--warning']],
            '#access' => !$paragraphs_entity->access(
                'update'
              ) && !$paragraphs_entity->access('delete'),
          ];
        }
        elseif ($item_mode == 'remove') {

          $element['top']['paragraph_type_title']['info'] = [
            '#markup' => $this->t(
              'Deleted @title: %type',
              [
                '@title' => $this->getSetting('title'),
                '%type' => $bundle_info['label']
              ]
            ),
          ];

          $links['confirm_remove_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Confirm removal'),
            '#name' => strtr($id_prefix, '-', '_') . '_confirm_remove',
            '#weight' => 503,
            '#submit' => [[get_class($this), 'paragraphsItemSubmit']],
            '#limit_validation_errors' => [
              array_merge(
                $parents,
                [$field_name, 'add_more']
              )
            ],
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
            '#limit_validation_errors' => [
              array_merge(
                $parents,
                [$field_name, 'add_more']
              )
            ],
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
          foreach ($links as $link_item) {
            if (!isset($link_item['#access']) || $link_item['#access']) {
              $show_links++;
            }
          }

          if ($show_links > 0) {

            $element['top']['links'] = $links;
            if ($show_links > 1) {
              $element['top']['links']['#theme_wrappers'] = [
                'dropbutton_wrapper',
                'paragraphs_dropbutton_wrapper'
              ];
              $element['top']['links']['prefix'] = [
                '#markup' => '<ul class="dropbutton">',
                '#weight' => -999,
              ];
              $element['top']['links']['suffix'] = [
                '#markup' => '</li>',
                '#weight' => 999,
              ];
            }
            else {
              $element['top']['links']['#theme_wrappers'] = ['paragraphs_dropbutton_wrapper'];
              foreach ($links as $key => $link_item) {
                unset($element['top']['links'][$key]['#prefix']);
                unset($element['top']['links'][$key]['#suffix']);
              }
            }
            $element['top']['links']['#weight'] = 2;
          }
        }

        if (count($info)) {
          $show_info = FALSE;
          foreach ($info as $info_item) {
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
          foreach ($actions as $action_item) {
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

      $display = EntityFormDisplay::collectRenderDisplay(
        $paragraphs_entity,
        $this->getSetting('form_display_mode')
      );

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
        $display->buildForm(
          $paragraphs_entity,
          $element['subform'],
          $form_state
        );
        foreach (Element::children($element['subform']) as $field) {
          if ($paragraphs_entity->hasField($field)) {
            $translatable = $paragraphs_entity->{$field}->getFieldDefinition()
              ->isTranslatable();
            if ($translatable) {
              $element['subform'][$field]['widget']['#after_build'][] = [
                static::class,
                'removeTranslatabilityClue'
              ];
            }
          }
        }
      }
      elseif ($item_mode == 'preview') {
        $element['subform'] = [];
        $element['behavior_plugins'] = [];
        $element['preview'] = entity_view(
          $paragraphs_entity,
          'preview',
          $paragraphs_entity->language()->getId()
        );
        $element['preview']['#access'] = $paragraphs_entity->access('view');
      }
      elseif ($item_mode == 'closed') {
        $element['subform'] = [];
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
        $element['subform'] = [];
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

}
