<?php

namespace Drupal\d_demo\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Footer menu example block' Block.
 *
 * @Block(
 *   id = "footer_menu_example_block",
 *   admin_label = @Translation("Footer menu example block"),
 *   category = @Translation("Example content"),
 * )
 */
class FooterMenuExampleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => "<h2>Example block h2</h2><p>Example menu</p><ul><li>First</li><li>Menu item 1</li><li>Menu item 2</li><li>Menu item 3</li><li>Last menu item</li></ul>",
    ];
  }

}
