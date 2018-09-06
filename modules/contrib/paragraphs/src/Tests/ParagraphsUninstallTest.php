<?php

namespace Drupal\paragraphs\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that Paragraphs module can be uninstalled.
 *
 * @group paragraphs
 */
class ParagraphsUninstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('paragraphs_demo');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(array(
      'administer paragraphs types',
      'administer modules',
    ));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that Paragraphs module can be uninstalled.
   */
  public function testUninstall() {

    // Uninstall the module paragraphs_demo.
    $this->drupalPostForm('admin/modules/uninstall', ['uninstall[paragraphs_demo]' => TRUE], t('Uninstall'));
    $this->drupalPostForm(NULL, [], t('Uninstall'));

    // Delete library data.
    $this->clickLink('Remove paragraphs library item entities');
    $this->drupalPostForm(NULL, [], t('Delete all paragraphs library item entities'));

    // Uninstall the library module.
    $this->drupalPostForm('admin/modules/uninstall', ['uninstall[paragraphs_library]' => TRUE], t('Uninstall'));
    $this->drupalPostForm(NULL, [], t('Uninstall'));

    // Delete paragraphs data.
    $this->clickLink('Remove paragraph entities');
    $this->drupalPostForm(NULL, [], t('Delete all paragraph entities'));

    // Uninstall the module paragraphs.
    $this->drupalPostForm('admin/modules/uninstall', ['uninstall[paragraphs]' => TRUE], t('Uninstall'));
    $this->drupalPostForm(NULL, [], t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'));
    $this->assertNoText(t('Paragraphs demo'));
    $this->assertNoText(t('Paragraphs library'));
    $this->assertNoText(t('Paragraphs'));
  }

}
