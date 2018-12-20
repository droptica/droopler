<?php

namespace Drupal\Tests\search_api_db\FunctionalJavascript;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests that using the DB backend via the UI works as expected.
 *
 * @group search_api
 */
class IntegrationTest extends JavascriptTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'search_api',
    'search_api_db',
  ];

  /**
   * Tests that adding a server works.
   */
  public function testAddingServer() {
    $admin_user = $this->drupalCreateUser(['administer search_api', 'access administration pages']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/config/search/search-api/add-server');
    $this->assertSession()->statusCodeEquals(200);

    $edit = ['name' => ' ~`Test Server', 'id' => '_test'];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->addressEquals('admin/config/search/search-api/server/_test');
  }

}
