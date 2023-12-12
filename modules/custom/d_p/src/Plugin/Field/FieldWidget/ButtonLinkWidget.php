<?php

declare(strict_types = 1);

namespace Drupal\d_p\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\d_p\Enum\ButtonTypeEnum;
use Drupal\link_attributes\Plugin\Field\FieldWidget\LinkWithAttributesWidget;

/**
 * Plugin implementation of the 'Button link widget' widget.
 *
 * @FieldWidget(
 *   id = "button_link_widget",
 *   module = "d_p",
 *   label = @Translation("Button Link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class ButtonLinkWidget extends LinkWithAttributesWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    /** @var \Drupal\link\Plugin\Field\FieldType\LinkItem $item */
    $item = $items[$delta];

    $options = $item->get('options')->getValue();
    $button_types = ButtonTypeEnum::getOptions();
    $element['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => $button_types,
      '#default_value' => $options['type'] ?? ButtonTypeEnum::Primary->value,
    ];

    return $element + parent::formElement($items, $delta, $element, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $values = parent::massageFormValues($values, $form, $form_state);

    foreach ($values as $delta => $value) {
      $values[$delta]['options']['type'] = $value['type'];
    }

    return $values;
  }

}
