<?php

namespace Drupal\d_commerce_products_list\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'CommerceMobileFilters' Block.
 *
 * @Block(
 *   id = "commerce_mobile_filters",
 *   admin_label = @Translation("Commerce Mobile filters"),
 *   category = @Translation("Facets"),
 * )
 */
class CommerceMobileFilters extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['button_text'] = [
      '#type' => 'textfield',
      '#title' => t('Button text'),
      '#default_value' => $this->getConfiguration()['button_text'] ?? 'Filters',
    ];

    $form['button_class'] = [
      '#type' => 'textfield',
      '#title' => t('Button class'),
      '#default_value' => $this->getConfiguration()['button_class'] ?? 'btn btn-outline-primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $this->setConfigurationValue('button_text', $formState->getValue('button_text'));
    $this->setConfigurationValue('button_class', $formState->getValue('button_class'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'inside' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->t($this->getConfiguration()['button_text']),
        '#attributes' => [
          'class' => [
            'mobile-filter',
            'collapsed',
            $this->getConfiguration()['button_class'],
          ],
          'type' => ['button'],
          'data-target' => ['.region-sidebar-left'],
          'aria-expanded' => ['false'],
          'aria-controls' => ['.region-sidebar-left'],
          'data-closed' => [t('Filters')],
          'data-open' => [t('Close Filters')],
        ],
      ],
    ];
  }

}
