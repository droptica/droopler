<?php

declare(strict_types=1);

namespace Drupal\d_blog\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

/**
 * Hook implementations for the d_blog module.
 */
class Hooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly RouteMatchInterface $routeMatch,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'node__blog_post' => [
        'base hook' => 'node',
      ],
      'node__blog_post__thumbnail' => [
        'base hook' => 'node',
      ],
      'node__blog_post__teaser' => [
        'base hook' => 'node',
      ],
      'node__blog_post__teaser_small' => [
        'base hook' => 'node',
      ],
      'paragraph__d_p_blog_image' => [
        'base hook' => 'paragraph',
      ],
      'field__node__field_blog_media_main_image__blog_post' => [
        'base hook' => 'field',
      ],
      'page__blog' => [
        'base hook' => 'page',
      ],
      'page__taxonomy__term__blog_posts_category' => [
        'base hook' => 'page',
      ],
      'pager__blog_listing__page' => [
        'base hook' => 'pager',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_links().
   */
  #[Hook('preprocess_links')]
  public function preprocessLinks(array &$variables): void {
    if (isset($variables['links']['node-readmore'])) {
      $variables['links']['node-readmore']['link']['#title'] = $this->t('Read article...');
    }
  }

  /**
   * Implements hook_preprocess_responsive_image().
   *
   * Generates a list of image links for httrack.
   */
  #[Hook('preprocess_responsive_image')]
  public function preprocessResponsiveImage(array &$variables): void {
    if (!Settings::get('httrack_enabled', FALSE)) {
      return;
    }
    if (empty($variables['sources'])) {
      return;
    }

    $id = substr(md5((string) $variables['uri']), 0, 6);
    foreach ($variables['sources'] as $k => $attribute) {
      /** @var \Drupal\Core\Template\Attribute $attribute */
      $source = preg_replace('/\s\d[xX]$/', '', (string) $attribute->offsetGet('srcset'));
      $variables['#attached']['html_head_link'][] = [
        [
          'href' => $source,
          'rel' => "droopler:$id:img$k",
        ],
      ];
    }
  }

  /**
   * Implements hook_preprocess_page().
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(array &$variables): void {
    if ($this->routeMatch->getRouteName() === 'entity.taxonomy_term.canonical') {
      $variables['page']['content']['pagetitle']['#attributes']['class'] = ['blog-listing-main-header'];
    }
  }

  /**
   * Implements hook_node_links_alter().
   */
  #[Hook('node_links_alter')]
  public function nodeLinksAlter(array &$links, NodeInterface $node, array &$context): void {
    foreach ($links as $key => $link) {
      if (str_contains((string) $key, 'comment__field')) {
        unset($links[$key]);
      }
    }
  }

}
