<?php

namespace Drupal\paragraphs_test\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Provides a test feature plugin.
 *
 * @ParagraphsBehavior(
 *   id = "test_text_color",
 *   label = @Translation("Test text color behavior plugin"),
 *   description = @Translation("Test text color behavior plugin"),
 *   weight = 1
 * )
 */
class TestTextColorBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['default_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Color'),
      '#maxlength' => 255,
      '#default_value' => $this->configuration['default_color'],
      '#description' => $this->t("Text color for the paragraph."),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('default_color') == 'red') {
      $form_state->setErrorByName('default_color', $this->t('Red can not be used as the default color.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['default_color'] = $form_state->getValue('default_color');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'default_color' => 'blue',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Color'),
      '#maxlength' => 255,
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'text_color', $this->configuration['default_color']),
      '#description' => $this->t("Text color for the paragraph."),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('text_color') != 'blue' && $form_state->getValue('text_color') != 'red') {
      $form_state->setError($form, 'The only allowed values are blue and red.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraphs_entity, EntityViewDisplayInterface $display, $view_mode) {
    if ($color = $paragraphs_entity->getBehaviorSetting($this->getPluginId(), 'text_color')) {
      $build['#attributes']['class'][] = $color . '_plugin_text';
      $build['#attached']['library'][] = 'paragraphs_test/drupal.paragraphs_test.color_text';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    $text_color = $paragraph->getBehaviorSetting($this->pluginId, 'text_color');
    return [$this->t('Text color: @color', ['@color' => $text_color])];
  }
}
