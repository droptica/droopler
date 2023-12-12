<?php

declare(strict_types = 1);

namespace Drupal\d_product\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Reset button' Block.
 *
 * @Block(
 *   id = "reset_button",
 *   admin_label = @Translation("Reset Filters"),
 *   category = @Translation("Facets"),
 * )
 */
class ResetButton extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Current Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * Reset button constructor.
   *
   * @param array $configuration
   *   Configuration options.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $requestStack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button text'),
      '#default_value' => $this->configuration['button_text'] ?? 'Reset Filters',
    ];

    $form['button_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button class'),
      '#default_value' => $this->configuration['button_class'] ?? 'btn btn-outline-primary btn-sm btn-reset',
    ];

    $form['button_target'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button target'),
      '#default_value' => $this->configuration['button_target'] ?? '/products',
    ];

    $form['button_icon_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button icon class'),
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
    if (!$this->request->get('f')) {
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
      '#markup' => $this->t($this->configuration['button_text']), // phpcs:ignore
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
