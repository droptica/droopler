<?php

declare(strict_types=1);

namespace Drupal\d_documentation\Hook;

use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the d_documentation module.
 */
class Hooks {

  protected const string MODULE_NAME = 'd_documentation';

  public function __construct(
    protected readonly ExtensionPathResolver $extensionPathResolver,
  ) {}

  /**
   * Implements hook_content_structure_alter().
   */
  #[Hook('content_structure_alter')]
  public function contentStructureAlter(array &$structure, string $context): void {
    if ($context !== 'all') {
      return;
    }

    $path = $this->extensionPathResolver->getPath('module', self::MODULE_NAME) . '/pages';
    $structure['documentation'] = [
      'file' => "$path/documentation.yml",
      'link' => 'Documentation',
      'weight' => 30,
      'children' => [
        'shortcodes' => [
          'file' => "$path/shortcodes.yml",
          'link' => 'Shortcodes',
          'weight' => 0,
        ],
        'd_p_banner' => [
          'file' => "$path/d_p_banner.yml",
          'link' => 'Banner Paragraph',
          'weight' => 10,
        ],
        'd_p_block' => [
          'file' => "$path/d_p_block.yml",
          'link' => 'Block Paragraph',
          'weight' => 15,
        ],
        'd_p_form' => [
          'file' => "$path/d_p_form.yml",
          'link' => 'Form Paragraph',
          'weight' => 20,
        ],
        'd_p_text_blocks' => [
          'file' => "$path/d_p_text_blocks.yml",
          'link' => 'Text Blocks Paragraph',
          'weight' => 30,
        ],
        'd_p_sidebar_image' => [
          'file' => "$path/d_p_sidebar_image.yml",
          'link' => 'Sidebar Image Paragraph',
          'weight' => 40,
        ],
        'd_p_subscribe' => [
          'file' => "$path/d_p_subscribe.yml",
          'link' => 'Subscribe File Paragraph',
          'weight' => 50,
        ],
        'd_p_text' => [
          'file' => "$path/d_p_text.yml",
          'link' => 'Text Paragraph',
          'weight' => 60,
        ],
        'd_p_text_bg' => [
          'file' => "$path/d_p_text_bg.yml",
          'link' => 'Text With Background Paragraph',
          'weight' => 70,
        ],
        'd_p_counters' => [
          'file' => "$path/d_p_counters.yml",
          'link' => 'Counters Paragraph',
          'weight' => 80,
        ],
        'd_p_gallery' => [
          'file' => "$path/d_p_gallery.yml",
          'link' => 'Gallery Paragraph',
          'weight' => 90,
        ],
        'd_p_carousel' => [
          'file' => "$path/d_p_carousel.yml",
          'link' => 'Carousel Paragraph',
          'weight' => 90,
        ],
        'd_p_embed' => [
          'file' => "$path/d_p_embed.yml",
          'link' => 'Side Embed Paragraph',
          'weight' => 100,
        ],
        'd_p_tiles' => [
          'file' => "$path/d_p_tiles.yml",
          'link' => 'Tiles Paragraphs',
          'weight' => 110,
        ],
        'd_p_reference_content' => [
          'file' => "$path/d_p_reference_content.yml",
          'link' => 'Reference Content Paragraph',
          'weight' => 120,
        ],
        'd_p_side_by_side' => [
          'file' => "$path/d_p_side_by_side.yml",
          'link' => 'Side By Side Paragraph',
          'weight' => 130,
        ],
      ],
    ];
  }

  /**
   * Implements hook_block_structure_alter().
   */
  #[Hook('block_structure_alter')]
  public function blockStructureAlter(array &$structure, string $context): void {
    if ($context !== 'all') {
      return;
    }

    $path = $this->extensionPathResolver->getPath('module', self::MODULE_NAME) . '/blocks';
    $structure['d_documentation_block_example'] = ['file' => "$path/block_documentation_example.yml"];
  }

}
