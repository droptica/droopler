<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'paragraph_theme' setting.
 *
 * @ParagraphSetting(
 *   id = "paragraph_theme",
 *   label = @Translation("Paragraph Theme"),
 *   settings = {
 *      "weight" = -90,
 *   }
 * )
 */
class ParagraphTheme extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
    $element = parent::formElement();
    $options = $this->getOptions();

    return [
      '#description' => $this->t('Choose a theme for this paragraph. You can manage this list in the form display settings for this paragraph type.'),
      '#type' => 'select',
      '#options' => isset($settings['allowed_themes'])
        ? array_intersect_key($options, array_flip($settings['allowed_themes']))
        : $options,
      '#default_value' => $settings['default_theme'] ?? '',
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    return [
      'theme-light' => $this->t('Light'),
      'theme-dark' => $this->t('Dark'),
      'theme-primary' => $this->t('Primary'),
      'theme-secondary' => $this->t('Secondary'),
      'theme-custom' => $this->t('Custom'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginSettingsForm(array $values = []): array {
    $options = $this->getOptions();

    $form['allowed_themes'] = [
      '#type' => 'select',
      '#title' => $this->t('Allowed Themes'),
      '#options' => $options,
      '#default_value' => $values['allowed_themes'] ?? array_keys($options),
      '#multiple' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'refreshDefaultThemeField'],
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => 'default-theme-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Updating the default theme field...'),
        ],
      ],
    ];

    $form['default_theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Theme'),
      '#options' => isset($values['allowed_themes'])
        ? array_intersect_key($options, array_flip($values['allowed_themes']))
        : $options,
      '#default_value' => $values['default_theme'] ?? '',
      '#prefix' => '<div id="default-theme-wrapper">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * The AJAX callback for the settings form to refresh the default theme list.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The default theme field with the refreshed options.
   */
  public function refreshDefaultThemeField(array &$form, FormStateInterface $form_state): array {
    $triggered_element = $form_state->getTriggeringElement();
    $parents = $triggered_element['#array_parents'];
    $parents[array_key_last($parents)] = 'default_theme';

    $default_theme = NestedArray::getValue($form, $parents);
    $default_theme['#options'] = array_intersect_key(
      $this->getOptions(),
      array_flip($form_state->getValue($triggered_element['#parents']))
    );

    return $default_theme;
  }

}
