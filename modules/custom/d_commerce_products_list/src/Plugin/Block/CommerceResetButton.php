<?php

namespace Drupal\d_commerce_products_list\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Commerce Reset button' Block.
 *
 * @Block(
 *   id = "commerce_reset_button",
 *   admin_label = @Translation("Commerce Reset Filters"),
 *   category = @Translation("Facets"),
 * )
 */
class CommerceResetButton extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Current Request.
   *
   * @var Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * CommerceResetButton constructor.
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
   * Create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   * @param array $configuration
   *   Configuration options.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   *
   * @return CommerceResetButton|static
   *   Static.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
      '#title' => t('Button text'),
      '#default_value' => $this->getConfiguration()['button_text'] ?? 'Reset Filters',
    ];

    $form['button_class'] = [
      '#type' => 'textfield',
      '#title' => t('Button class'),
      '#default_value' => $this->getConfiguration()['button_class'] ?? 'btn btn-outline-primary btn-sm btn-reset',
    ];

    $form['button_target'] = [
      '#type' => 'textfield',
      '#title' => t('Button target'),
      '#default_value' => $this->getConfiguration()['button_target'] ?? '/shop',
    ];

    $form['button_icon_class'] = [
      '#type' => 'textfield',
      '#title' => t('Button icon class'),
      '#default_value' => $this->getConfiguration()['button_icon_class'] ?? 'fas fa-times',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $this->setConfigurationValue('button_text', $formState->getValue('button_text'));
    $this->setConfigurationValue('button_class', $formState->getValue('button_class'));
    $this->setConfigurationValue('button_target', '/' . ltrim(
      $formState->getValue('button_target'),
      '/'
    ));
    $this->setConfigurationValue('button_icon_class', $formState->getValue('button_icon_class'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    if ($this->request->getRequestUri() == '/shop') {
      return [
        '#markup' => '',
        '#cache' => [
          'contexts' => ['url'],
        ],
      ];
    }

    $link_content_markups[] = [
      '#markup' => $this->t($this->getConfiguration()['button_text']),
    ];
    if (!empty($this->getConfiguration()['button_icon_class'])) {
      $link_content_markups[] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => $this->getConfiguration()['button_icon_class'],
        ],
      ];
    }

    return [
      [
        '#type' => 'link',
        '#title' => $link_content_markups,
        '#attributes' => [
          'class' => $this->getConfiguration()['button_class'],
          'target' => '_self',
        ],
        '#url' => URL::fromUserInput($this->getConfiguration()['button_target']),
        '#cache' => [
          'contexts' => ['url'],
        ],
      ],
    ];
  }

}
