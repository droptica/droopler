<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

declare(strict_types = 1);

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Url;

/**
 * Implements hook_preprocess_block().
 */
function droopler_theme_preprocess_block(array &$variables): void {
  $block_content = $variables['content']['#block_content'] ?? NULL;
  if (!$block_content instanceof BlockContentInterface) {
    return;
  }

  $block_bundle = $variables['bundle'] ?? NULL;

  if ($block_bundle === 'social_media') {
    $social_media_names = _get_social_media_names();
    $links = [];

    foreach ($social_media_names as $name) {
      $social_media_link = theme_get_setting("link_$name");
      if (!empty($social_media_link)) {
        $links[] = [
          'name' => $name,
          'link' => $social_media_link,
        ];
      }
    }

    $variables['social_media_links'] = $links;
  }

  if ($block_bundle === 'search_page_link') {
    $search_page_link = theme_get_setting('search_page_link');
    if ($search_page_link) {
      $variables['search_page_link'] = [
        '#type' => 'link',
        '#title' => 'Search',
        '#attributes' => [
          'class' => ['search-page-link'],
          'target' => '_self',
        ],
        '#url' => Url::fromUserInput($search_page_link),
      ];
    }
  }

  if ($block_bundle === 'mobile_filters') {
    $button_text = $block_content->get('field_button_text')->value;
    $button_class = $block_content->get('field_button_class')->value;
    $variables['content']['inside'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $button_text, // phpcs:ignore
      '#attributes' => [
        'class' => [
          'mobile-filter',
          'collapsed',
          $button_class,
        ],
        'type' => ['button'],
        'data-closed' => [t('Filters')],
        'data-open' => [t('Close Filters')],
      ],
    ];
  }

  if ($block_bundle === 'mobile_filters_submit') {
    $button_text = $block_content->get('field_button_text')->value;
    $button_class = $block_content->get('field_button_class')->value;
    $variables['content']['inside'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $button_text, // phpcs:ignore
      '#attributes' => [
        'class' => [
          'mobile-filter-close',
          $button_class,
        ],
        'type' => ['button'],
        'data-target' => ['.region-sidebar-left'],
        'aria-expanded' => ['false'],
        'aria-controls' => ['.region-sidebar-left'],
      ],
    ];
    $variables['content']['closeme'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => t('x'),
      '#attributes' => [
        'class' => ['mobile-filter-close-top', 'd-none'],
        'type' => ['button'],
        'data-target' => ['.region-sidebar-left'],
        'aria-expanded' => ['false'],
        'aria-controls' => ['.region-sidebar-left'],
      ],
    ];
  }

  if ($block_bundle === 'reset_button') {
    $button_text = $block_content->get('field_button_text')->value;
    $button_class = $block_content->get('field_button_class')->value;
    $button_target = $block_content->get('field_button_target')->value;
    $button_icon_class = $block_content->get('field_button_icon_class')->value;

    if (!\Drupal::request()->get('f')) {
      $variables['content']['inside'] = [
        '#markup' => '',
        '#cache' => [
          'contexts' => ['url.query_args:f'],
        ],
      ];
    }
    else {
      $link_content_markups = [];
      if (!empty($button_class)) {
        $link_content_markups[] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => [$button_icon_class],
          ],
        ];
      }

      $link_content_markups[] = [
        '#markup' => $button_text, // phpcs:ignore
      ];

      $variables['content']['inside'] =
        [
          '#type' => 'link',
          '#title' => $link_content_markups,
          '#attributes' => [
            'class' => [$button_class],
            'target' => '_self',
          ],
          '#url' => URL::fromUserInput($button_target),
          '#cache' => [
            'contexts' => ['url.query_args:f'],
          ],

      ];
    }
  }
}
