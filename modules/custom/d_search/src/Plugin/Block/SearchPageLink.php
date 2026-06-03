<?php

declare(strict_types=1);

namespace Drupal\d_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configurable search link without the core Search module.
 *
 * @Block(
 *   id = "search_page_link",
 *   admin_label = @Translation("Search page link"),
 *   category = @Translation("Search"),
 * )
 */
class SearchPageLink extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a SearchPageLink block plugin instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    // @phpstan-ignore-next-line Drupal uses late static binding for plugin factory pattern.
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'path' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search page path'),
      '#description' => $this->t(
        'Internal path, e.g. %example. Leave empty for the core Search route if that module exists, otherwise %fallback.',
        [
          '%example' => '/products',
          '%fallback' => '/search',
        ]
      ),
      '#default_value' => $this->configuration['path'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['path'] = trim((string) $form_state->getValue('path'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      [
        '#type' => 'link',
        '#title' => $this->t('Search'),
        '#attributes' => [
          'class' => ['search-page-link'],
          'target' => '_self',
        ],
        '#url' => $this->getSearchDestination(),
      ],
    ];
  }

  /**
   * Resolves where the search link should point.
   *
   * @return \Drupal\Core\Url
   *   The URL object for the link render element.
   */
  protected function getSearchDestination(): Url {
    $path_config = trim($this->configuration['path']);
    if ($path_config !== '') {
      return Url::fromUri('internal:' . (str_starts_with($path_config, '/') ? $path_config : '/' . $path_config));
    }
    if ($this->moduleHandler->moduleExists('search')) {
      return Url::fromRoute('search.view');
    }
    return Url::fromUri('internal:/search');
  }

}
