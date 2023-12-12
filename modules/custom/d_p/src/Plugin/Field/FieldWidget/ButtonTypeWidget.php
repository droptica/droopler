<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\d_p\Enum\ButtonTypeEnum;

/**
 * Plugin implementation of the ButtonType widget.
 *
 * @FieldWidget(
 *   id = "button_type_widget",
 *   label = @Translation("Button Type"),
 *   field_types = {
 *     "button_type"
 *   }
 * )
 */
class ButtonTypeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $button_types = ButtonTypeEnum::getOptions();
    $value = $items[$delta]->button_type ?? ButtonTypeEnum::Primary->value;

    $element += [
      '#type' => 'select',
      '#default_value' => $value,
      '#options' => $button_types,
    ];

    return ['button_type' => $element];
  }

}
