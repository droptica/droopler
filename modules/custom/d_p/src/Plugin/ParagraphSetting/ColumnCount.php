<?php

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\Validation\ParagraphSettingsValidation;

/**
 * Plugin implementation of the 'column_count' setting.
 *
 * @ParagraphSetting(
 *   id = "column_count",
 *   label = @Translation("Column count (desktop)"),
 *   settings = {
 *      "weight" = 0,
 *   }
 * )
 */
class ColumnCount extends ParagraphSettingPluginBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(): array {
    $element = parent::formElement();

    return [
      '#description' => $this->t('Select the number of items in one row.'),
      '#type' => 'number',
      '#min' => '1',
      '#max' => '12',
      '#element_validate' => [
        [ParagraphSettingsValidation::class, 'validateColumnCount'],
      ],
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 4;
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
