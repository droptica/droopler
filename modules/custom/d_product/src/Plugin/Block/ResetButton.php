<?php

namespace Drupal\d_product\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a 'Reset button' Block.
 *
 * @Block(
 *   id = "reset_button",
 *   admin_label = @Translation("Reset Filters"),
 *   category = @Translation("Facets"),
 * )
 */
class ResetButton extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['button_text'] = [
      '#type' => 'textfield',
      '#title' => t('Button text'),
      '#default_value' => $this->configuration['button_text'] ?? 'Reset Filters',
    ];

    $form['button_class'] = [
      '#type' => 'textfield',
      '#title' => t('Button class'),
      '#default_value' => $this->configuration['button_class'] ?? 'btn btn-outline-primary btn-sm btn-reset',
    ];

    $form['button_target'] = [
      '#type' => 'textfield',
      '#title' => t('Button target'),
      '#default_value' => $this->configuration['button_target'] ?? '/products',
    ];

    $form['button_icon_class'] = [
      '#type' => 'textfield',
      '#title' => t('Button icon class'),
      '#default_value' => $this->configuration['button_icon_class'] ?? 'fas fa-times',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $this->configuration['button_text'] = $formState->getValue('button_text');
    $this->configuration['button_class'] = $formState->getValue('button_class');
    $this->configuration['button_target'] = '/' . ltrim(
        $formState->getValue('button_target'),
        '/'
      );
    $this->configuration['button_icon_class'] = $formState->getValue(
      'button_icon_class'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!isset($_REQUEST['f'])) {
      return [
        '#markup' => '',
        '#cache' => [
          'contexts' => ['url.query_args:f'],
        ],
      ];
    }

    $link_content_markups = [];
    if (!empty($this->configuration['button_icon_class'])) {
      $link_content_markups[] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => $this->configuration['button_icon_class'],
        ],
      ];
    }

    $link_content_markups[] = [
      '#markup' => $this->t($this->configuration['button_text']),
    ];

    return [
      [
        '#type' => 'link',
        '#title' => $link_content_markups,
        '#attributes' => [
          'class' => $this->configuration['button_class'],
          'target' => '_self',
        ],
        '#url' => URL::fromUserInput($this->configuration['button_target']),
        '#cache' => [
          'contexts' => ['url.query_args:f'],
        ],
      ],
    ];
  }
}
