<?php

namespace Drupal\paragraphs_test\Plugin\paragraphs\Behavior;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Test plugin with field selection.
 *
 * @ParagraphsBehavior(
 *   id = "test_field_selection",
 *   label = @Translation("Test field selection for behavior plugin"),
 *   description = @Translation("Test field selection for behavior plugin"),
 *   weight = 0
 * )
 */
class TestFieldsSelectionBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['field_selection_filter'] = [
      '#type' => 'select',
      '#options' => $this->getFieldNameOptions($form_state->getFormObject()->getEntity(), 'image'),
      '#title' => $this->t('Paragraph fields'),
      '#default_value' => $this->configuration['field_selection_filter'],
      '#description' => $this->t("Choose filtered paragraph field to be applied."),
    ];

    $form['field_selection'] = [
      '#type' => 'select',
      '#options' => $this->getFieldNameOptions($form_state->getFormObject()->getEntity()),
      '#title' => $this->t('Paragraph fields'),
      '#default_value' => $this->configuration['field_selection'],
      '#description' => $this->t("Choose paragraph field to be applied."),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'field_selection' => '',
      'field_selection_filter' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraphs_entity, EntityViewDisplayInterface $display, $view_mode) {}

}
