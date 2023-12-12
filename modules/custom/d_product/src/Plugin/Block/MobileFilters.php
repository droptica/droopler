<?php

declare(strict_types = 1);

namespace Drupal\d_product\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'MobileFilters' Block.
 *
 * @Block(
 *   id = "mobile_filters",
 *   admin_label = @Translation("Mobile filters"),
 *   category = @Translation("Facets"),
 * )
 */
class MobileFilters extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button text'),
      '#default_value' => $this->configuration['button_text'] ?? 'Filters',
    ];

    $form['button_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button class'),
      '#default_value' => $this->configuration['button_class'] ?? 'btn btn-outline-primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $this->configuration['button_text'] = $formState->getValue('button_text');
    $this->configuration['button_class'] = $formState->getValue('button_class');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'inside' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->t($this->configuration['button_text']), // phpcs:ignore
        '#attributes' => [
          'class' => [
            'mobile-filter',
            'collapsed',
            $this->configuration['button_class'],
          ],
          'type' => ['button'],
          'data-closed' => [$this->t('Filters')],
          'data-open' => [$this->t('Close Filters')],
        ],
      ],
    ];
  }

}
