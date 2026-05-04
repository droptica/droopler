<?php

declare(strict_types=1);

namespace Drupal\d_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a link to the site's search page without requiring core Search module.
 *
 * @Block(
 *   id = "search_page_link",
 *   admin_label = @Translation("Search page link"),
 *   category = @Translation("Search"),
 * )
 */
class SearchPageLink extends BlockBase {

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
      '#description' => $this->t('Internal path, e.g. %example. Leave empty to use the default core Search route when the Search module is enabled, or %fallback when it is not.', [
        '%example' => '/products',
        '%fallback' => '/search',
      ]),
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
   */
  protected function getSearchDestination(): Url {
    $path_config = trim($this->configuration['path']);
    if ($path_config !== '') {
      return Url::fromUri('internal:' . (str_starts_with($path_config, '/') ? $path_config : '/' . $path_config));
    }
    $module_handler = \Drupal::moduleHandler();
    if ($module_handler->moduleExists('search')) {
      return Url::fromRoute('search.view');
    }
    return Url::fromUri('internal:/search');
  }

}
