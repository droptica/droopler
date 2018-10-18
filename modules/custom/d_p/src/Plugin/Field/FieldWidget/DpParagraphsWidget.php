<?php

namespace Drupal\d_p\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
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
    $settings = parent::defaultSettings();
    $settings['show_placeholders'] = 'yes';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['show_placeholders'] = [
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

    return $form;
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
    $element = parent::formElement(
      $items,
      $delta,
      $element,
      $form,
      $form_state
    );
    $elements['#attached']['library'][] = 'd_p/d_p_admin';

    $field_name = $this->fieldDefinition->getName();
    $parents = $element['#field_parents'];

    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraphs_entity */
    $paragraphs_entity = NULL;
    $widget_state = static::getWidgetState($parents, $field_name, $form_state);

    if (isset($widget_state['paragraphs'][$delta]['entity'])) {
      $paragraphs_entity = $widget_state['paragraphs'][$delta]['entity'];
    }
    elseif (isset($items[$delta]->entity)) {
      $paragraphs_entity = $items[$delta]->entity;
    }

    $paragraphs = [];
    \Drupal::moduleHandler()->alter(
      'd_p_placeholder',
      $paragraphs,
      $paragraphs_entity
    );

    if ($this->settings['show_placeholders'] == 'yes') {
      $element['#attached']['library'][] = 'd_p/d_p_admin';
      $id = $paragraphs_entity->id();
      if (isset($paragraphs[$id]['icon'])) {
        $element['top']['icon-placeholder'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['icon-placeholder']],
          '#weight' => -1,
          'icon' => [
            '#markup' => '<img src="' . $paragraphs[$id]['icon'] . '" />',
          ],
        ];
      }
      $bundle = $element['top']['paragraph_type_title']['info']['#markup'];
      unset($element['top']['paragraph_type_title']);

      $title = !empty($paragraphs[$id]['title']) ? '<b>' . $paragraphs[$id]['title'] . '</b> - ' . $bundle : $bundle;

      $element['paragraph_title'] = [
        '#weight' => -1001,
        '#markup' => '<h5>' . $title . '</h5>',
      ];
    }

    return $element;
  }
}