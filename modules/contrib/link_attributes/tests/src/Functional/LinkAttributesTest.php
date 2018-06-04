<?php

namespace Drupal\Tests\link_attributes\Functional;

use Drupal\simpletest\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests link attributes functionality.
 *
 * @group link_attributes
 */
class LinkAttributesTest extends BrowserTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'link',
    'link_attributes',
    'menu_ui',
    'menu_link_content',
    'system',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->placeBlock('system_menu_block:footer');
  }

  /**
   * Test attributes.
   */
  public function testMenuLinkAdmin() {
    // Login as a super-admin.
    $this->drupalLogin($this->drupalCreateUser(array_keys(\Drupal::service('user.permissions')->getPermissions())));

    $this->drupalGet('admin/structure/menu/manage/footer/add');
    $this->submitForm([
      'title[0][value]' => 'A menu link',
      'link[0][uri]' => '<front>',
      // This is enough to check the fields are there.
      'link[0][options][attributes][target]' => '_blank',
      'link[0][options][attributes][class]' => 'menu__link--really_special',
    ], t('Save'));
    $this->drupalGet('user');
    $page = $this->getSession()->getPage();
    // The link should exist and contain the required attributes.
    $link = $page->findLink('A menu link');
    $this->assertNotNull($link);
    $this->assertEquals('_blank', $link->getAttribute('target'));
    $this->assertEquals('menu__link--really_special', $link->getAttribute('class'));
    // No rel attribute was added, so none should be present.
    $this->assertFalse($link->hasAttribute('rel'));
  }

}
