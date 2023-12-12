<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;
use Drupal\d_p\Validation\ParagraphSettingsValidation;

/**
 * The abstract class for the column count setting.
 */
abstract class ColumnCount extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

  const DEFAULT_DESCRIPTION = 'Select the number of items in one row.';

  /**
   * {@inheritdoc}
   */
  public function formElement(array $settings = []): array {
    $element = parent::formElement();

    return [
      '#description' => $this->t($settings['description'] ?? static::DEFAULT_DESCRIPTION), // phpcs:ignore
      '#type' => 'select',
      '#options' => $this->getOptions(),
      '#default_value' => $settings['default_value'] ?? $this->getDefaultValue(),
      '#element_validate' => [
        [ParagraphSettingsValidation::class, 'validateColumnCount'],
      ],
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    $range = range(1, 12);

    return array_combine($range, $range);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginSettingsForm(array $values = []): array {
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $values['description'] ?? static::DEFAULT_DESCRIPTION,
    ];

    $form['default_value'] = [
      '#type' => 'select',
      '#title' => $this->t('Default value'),
      '#options' => $this->getOptions(),
      '#default_value' => $values['default_value'] ?? $this->getDefaultValue(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidationRulesDefinition(): array {
    return [
      'column_count' => [
        'allowed_values' => range(1, 12),
        'bundle_allowed_values' => [
          'd_p_group_of_counters' => [1, 2, 3, 4, 6, 12],
          'd_p_group_of_text_blocks' => [1, 2, 3, 4, 6, 12],
        ],
      ],
    ];
  }

}
