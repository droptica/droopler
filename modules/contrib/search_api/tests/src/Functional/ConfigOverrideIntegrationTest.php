<?php

namespace Drupal\Tests\search_api\Functional;

use Drupal\search_api_test\MethodOverrides;
use Drupal\search_api_test\PluginTestTrait;

/**
 * Contains integration tests for config entities with overrides.
 *
 * @group search_api
 */
class ConfigOverrideIntegrationTest extends SearchApiBrowserTestBase {

  use PluginTestTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Set up overrides.
    $settings['config']['search_api.server.test_server']['name'] = (object) [
      'value' => 'Overridden server',
      'required' => TRUE,
    ];
    $settings['config']['search_api.server.test_server']['status'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];
    $settings['config']['search_api.server.test_server']['backend_config']['test'] = (object) [
      'value' => 'foobar',
      'required' => TRUE,
    ];
    $settings['config']['search_api.index.test_index']['name'] = (object) [
      'value' => 'Overridden index',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $permissions = [
      'administer search_api',
      'access administration pages',
      'administer nodes',
      'bypass node access',
      'administer content types',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that the UI works correctly with config entity overrides present.
   */
  public function testConfigOverrideIntegration() {
    $base_path = 'admin/config/search/search-api';
    $new_user = $this->drupalCreateUser();

    // Set up a trap through method overrides to ensure that critical methods
    // are only ever called with the correct, overridden backend configuration.
    $override = [MethodOverrides::class, 'overrideTestBackendMethod'];
    $methods = [
      'postInsert',
      'preUpdate',
      'postUpdate',
      'addIndex',
      'updateIndex',
      'removeIndex',
      'deleteItems',
      'deleteAllIndexItems',
      'search',
      'isAvailable',
      'getDiscouragedProcessors',
    ];
    foreach ($methods as $method) {
      $this->setMethodOverride('backend', $method, $override);
    }
    // indexItems() needs a special override since it needs to return item IDs.
    $override = [MethodOverrides::class, 'overrideTestBackendIndexItems'];
    $this->setMethodOverride('backend', 'indexItems', $override);

    // Add the server.
    $this->drupalGet("$base_path/add-server");
    $edit = [
      'id' => 'test_server',
      'name' => 'Test server',
      'backend' => 'search_api_test',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()
      ->pageTextContains('The server was successfully saved.');
    $this->assertSession()->addressEquals($base_path . '/server/test_server');

    // Add the index.
    $this->drupalGet("$base_path/add-index");
    $edit = [
      'id' => 'test_index',
      'name' => 'Test index',
      'server' => 'test_server',
      'datasources[entity:user]' => TRUE,
      'options[index_directly]' => FALSE,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()
      ->pageTextContains('Please configure the used datasources.');
    $this->submitForm([], 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()
      ->pageTextContains('The index was successfully saved.');
    $this->assertSession()->addressEquals($base_path . '/index/test_index');

    // Overview page displays overridden values.
    $this->drupalGet($base_path);
    $this->assertSession()->pageTextContains('Overridden server');
    $this->assertSession()->responseNotContains('Test server');
    $this->assertSession()->pageTextContains('Overridden index');
    $this->assertSession()->responseNotContains('Test index');

    // Normal server page displays overridden values.
    $this->drupalGet($base_path . '/server/test_server');
    $this->assertSession()->pageTextContains('Overridden server');
    $this->assertSession()->responseNotContains('Test server');
    $this->assertSession()->pageTextContains('Overridden index');
    $this->assertSession()->responseNotContains('Test index');
    $this->assertSession()->pageTextContains('enabled');
    $this->assertSession()->responseNotContains('disabled');

    // Disabling the server isn't possible.
    $this->clickLink('disable');
    $this->assertSession()->pageTextContains('Test server');
    $this->assertSession()->responseNotContains('Overridden server');
    $this->submitForm([], 'Disable');
    $this->drupalGet($base_path . '/server/test_server');
    $this->assertSession()->pageTextContains('enabled');
    $this->assertSession()->responseNotContains('disabled');

    // The "Edit" form shows the override-free server.
    $this->drupalGet($base_path . '/server/test_server/edit');
    $this->assertSession()->pageTextContains('Test server');
    $this->assertSession()->responseNotContains('Overridden server');
    $this->assertSession()->responseNotContains('foobar');
    $edit = [
      'name' => 'New server name',
      'backend_config[test]' => 'nonsense',
    ];
    $this->submitForm($edit, 'Save');

    // "View" tab still shows overrides, "Edit" tab still doesn't.
    $this->assertSession()->addressEquals($base_path . '/server/test_server');
    $this->assertSession()->pageTextContains('Overridden server');
    $this->assertSession()->responseNotContains('New server name');
    $this->drupalGet($base_path . '/server/test_server/edit');
    $this->assertSession()->pageTextContains('New server name');
    $this->assertSession()->responseNotContains('Overridden server');
    $this->assertSession()->responseContains('nonsense');
    $this->assertSession()->responseNotContains('foobar');

    // Server "clear" form and command use overridden server.
    $this->drupalGet($base_path . '/server/test_server/clear');
    $this->assertSession()->pageTextContains('Overridden server');
    $this->assertSession()->responseNotContains('New server name');
    $this->submitForm([], 'Confirm');

    // Index "View" tab uses overridden index and server.
    $this->drupalGet($base_path . '/index/test_index');
    $this->assertSession()->pageTextContains('Overridden index');
    $this->assertSession()->responseNotContains('Test index');
    $this->assertSession()->pageTextContains('Overridden server');
    $this->assertSession()->responseNotContains('New server name');
    $this->assertSession()->pageTextContains('enabled');
    $this->assertSession()->responseNotContains('disabled');

    // Index items, see if that triggers an error.
    $this->submitForm([], 'Index now');
    $this->checkForMetaRefresh();

    // Same for deleting an item.
    $new_user->delete();

    // The "Edit", "Fields", "Processors" and "Disable" tabs/forms should use
    // the override-free index version.
    foreach (['edit', 'fields', 'fields/add/nojs', 'processors', 'disable'] as $tab) {
      $this->drupalGet("$base_path/index/test_index/$tab");
      $this->assertSession()->pageTextContains('Test index');
      $this->assertSession()->responseNotContains('Overridden index');
    }

    // The "Reindex" and "Clear" forms and commands both use the overridden
    // index.
    foreach (['reindex', 'clear'] as $tab) {
      $this->drupalGet("$base_path/index/test_index/$tab");
      $this->assertSession()->pageTextContains('Overridden index');
      $this->assertSession()->responseNotContains('Test index');
    }
    // Clear the index, see if that triggers an error.
    $this->submitForm([], 'Confirm');

    // The server "Delete" form also uses overrides.
    $this->drupalGet($base_path . '/server/test_server/delete');
    $this->assertSession()->pageTextContains('Overridden server');
    $this->assertSession()->responseNotContains('New server name');
    $this->assertSession()->pageTextContains('Overridden index');
    $this->assertSession()->responseNotContains('Test index');

    // Delete the server, see if that triggers any errors.
    $this->submitForm([], 'Delete');
  }

}
