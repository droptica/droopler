<?php

namespace Drupal\Tests\config_update_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify the config revert report and its links.
 *
 * @group config_update
 */
class ConfigUpdateTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * Use the Search module because it has two included config items in its
   * config/install, assuming node and user are also enabled.
   *
   * @var array
   */
  public static $modules = [
    'config',
    'config_update',
    'config_update_ui',
    'search',
    'node',
    'user',
    'block',
    'text',
    'field',
    'filter',
  ];

  /**
   * The admin user that will be created.
   *
   * @var object
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user and log in.
    $this->adminUser = $this->createUser([
      'access administration pages',
      'administer search',
      'view config updates report',
      'synchronize configuration',
      'export configuration',
      'import configuration',
      'revert configuration',
      'delete configuration',
      'administer filters',
    ]);
    $this->drupalLogin($this->adminUser);

    // Make sure local tasks and page title are showing.
    $this->placeBlock('local_tasks_block');
    $this->placeBlock('page_title_block');

    // Load the Drush include file so that its functions can be tested, plus
    // the Drush testing include file.
    module_load_include('inc', 'config_update_ui', 'config_update_ui.drush_testing');
    module_load_include('inc', 'config_update_ui', 'config_update_ui.drush');
  }

  /**
   * Tests the config report and its linked pages.
   */
  public function testConfigReport() {
    // Test links to report page.
    $this->drupalGet('admin/config/development/configuration');
    $this->clickLink('Updates report');
    $this->assertNoReport();

    // Verify the Drush list types command.
    $output = implode("\n", drush_config_update_ui_config_list_types());
    $this->assertTrue(strpos($output, 'search_page') !== FALSE);
    $this->assertTrue(strpos($output, 'node_type') !== FALSE);
    $this->assertTrue(strpos($output, 'user_role') !== FALSE);
    $this->assertTrue(strpos($output, 'block') !== FALSE);

    // Verify some empty reports.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);
    $this->assertDrushReports('type', 'search_page', [], [], [], []);

    // Module, theme, and profile reports have no 'added' section.
    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->assertReport('Search module', [], [], [], [], ['added']);
    $this->assertDrushReports('module', 'search', [], [], [], []);
    $this->drupalGet('admin/config/development/configuration/report/theme/classy');
    $this->assertReport('Classy theme', [], [], [], [], ['added']);
    $this->assertDrushReports('theme', 'classy', [], [], [], []);

    $inactive = ['locale.settings' => 'Simple configuration'];
    $this->drupalGet('admin/config/development/configuration/report/profile');
    $this->assertReport('Testing profile', [], [], [], $inactive, ['added']);
    // The locale.settings line should show that the Testing profile is the
    // provider.
    $session = $this->assertSession();
    $session->pageTextContains('Testing profile');
    $this->assertDrushReports('profile', '', [], [], [], array_keys($inactive));

    // Verify that the user search page cannot be imported (because it already
    // exists).
    $this->drupalGet('admin/config/development/configuration/report/import/search_page/user_search');
    $session = $this->assertSession();
    $session->statusCodeEquals(404);

    // Delete the user search page from the search UI and verify report for
    // both the search page config type and user module.
    $this->drupalGet('admin/config/search/pages');
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    $inactive = ['search.page.user_search' => 'Users'];
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], $inactive);
    // The search.page.user_search line should show that the User module is the
    // provider.
    $session = $this->assertSession();
    $session->pageTextContains('User module');
    $this->assertDrushReports('type', 'search_page', [], [], [], array_keys($inactive));

    $this->drupalGet('admin/config/development/configuration/report/module/user');
    $this->assertReport('User module', [], [], [], $inactive, ['added', 'changed']);
    $this->assertDrushReports('module', 'user', [], [], [],
      [
        'rdf.mapping.user.user',
        'search.page.user_search',
        'views.view.user_admin_people',
        'views.view.who_s_new',
        'views.view.who_s_online',
      ], ['changed']);

    // Verify that the user search page cannot be reverted (because it does
    // not already exist).
    $this->drupalGet('admin/config/development/configuration/report/revert/search_page/user_search');
    $session = $this->assertSession();
    $session->statusCodeEquals(404);
    // Verify that the delete URL doesn't work either.
    $this->drupalGet('admin/config/development/configuration/report/delete/search_page/user_search');
    $session = $this->assertSession();
    $session->statusCodeEquals(404);

    // Use the import link to get it back. Do this from the search page
    // report to make sure we are importing the right config.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->clickLink('Import from source');
    $this->drupalPostForm(NULL, [], 'Import');
    $session = $this->assertSession();
    $session->pageTextContains('has been imported');
    $this->assertNoReport();
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);
    $this->assertDrushReports('type', 'search_page', [], [], [], []);

    // Verify that after import, there is no config hash generated.
    $this->drupalGet('admin/config/development/configuration/single/export/search_page/user_search');
    $session = $this->assertSession();
    $session->pageTextContains('id: user_search');
    $session->pageTextNotContains('default_config_hash:');

    // Test importing again, this time using the Drush import command.
    $this->drupalGet('admin/config/search/pages');
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    $inactive = ['search.page.user_search' => 'Users'];
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], $inactive);
    drush_config_update_ui_config_import_missing('search.page.user_search');
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);

    // Edit the node search page from the search UI and verify report.
    $this->drupalGet('admin/config/search/pages');
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, [
      'label' => 'New label',
      'path'  => 'new_path',
    ], 'Save search page');
    $changed = ['search.page.node_search' => 'New label'];
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], $changed, []);
    $this->assertDrushReports('type', 'search_page', [], [], array_keys($changed), []);

    // Test the show differences link.
    $this->clickLink('Show differences');
    $session = $this->assertSession();
    $session->pageTextContains('Content');
    $session->pageTextContains('New label');
    $session->pageTextContains('node');
    $session->pageTextContains('new_path');

    // Test the show differences Drush command.
    $output = drush_config_update_ui_config_diff('search.page.node_search');
    $this->assertTrue(strpos($output, 'Content') !== FALSE);
    $this->assertTrue(strpos($output, 'New label') !== FALSE);
    $this->assertTrue(strpos($output, 'node') !== FALSE);
    $this->assertTrue(strpos($output, 'new_path') !== FALSE);

    // Test the Back link.
    $this->clickLink("Back to 'Updates report' page.");
    $this->assertNoReport();

    // Test the export link.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->clickLink('Export');
    $session = $this->assertSession();
    $session->pageTextContains('Here is your configuration:');
    $session->pageTextContains('id: node_search');
    $session->pageTextContains('New label');
    $session->pageTextContains('path: new_path');
    $session->pageTextContains('search.page.node_search.yml');

    // Grab the uuid and hash lines from the exported config for the next test.
    $text = strip_tags($this->getSession()->getPage()->find('css', 'textarea')->getHtml());
    $matches = [];
    preg_match('|^.*uuid:.*$|m', $text, $matches);
    $uuid_line = trim($matches[0]);
    preg_match('|^.*default_config_hash:.*$|m', $text, $matches);
    $hash_line = trim($matches[0]);

    // Test reverting.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->clickLink('Revert to source');
    $session = $this->assertSession();
    $session->pageTextContains('Are you sure you want to revert');
    $session->pageTextContains('Search page');
    $session->pageTextContains('node_search');
    $session->pageTextContains('Customizations will be lost. This action cannot be undone');
    $this->drupalPostForm(NULL, [], 'Revert');
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);

    // Verify that the uuid and hash keys were retained in the revert.
    $this->drupalGet('admin/config/development/configuration/single/export/search_page/node_search');
    $session = $this->assertSession();
    $session->pageTextContains('id: node_search');
    $session->pageTextContains($uuid_line);
    $session->pageTextContains($hash_line);

    // Test reverting again, this time using Drush single revert command.
    $this->drupalGet('admin/config/search/pages');
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, [
      'label' => 'New label',
      'path'  => 'new_path',
    ], 'Save search page');
    $changed = ['search.page.node_search' => 'New label'];
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], $changed, []);
    drush_config_update_ui_config_revert('search.page.node_search');
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);

    // Test reverting again, this time using Drush multiple revert command.
    $this->drupalGet('admin/config/search/pages');
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, [
      'label' => 'New label',
      'path'  => 'new_path',
    ], 'Save search page');
    $changed = ['search.page.node_search' => 'New label'];
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], $changed, []);
    drush_config_update_ui_config_revert_multiple('type', 'search_page');
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);

    // Add a new search page from the search UI and verify report.
    $this->drupalPostForm('admin/config/search/pages', [
      'search_type' => 'node_search',
    ], 'Add search page');
    $this->drupalPostForm(NULL, [
      'label' => 'test',
      'id'    => 'test',
      'path'  => 'test',
    ], 'Save');
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $added = ['search.page.test' => 'test'];
    $this->assertReport('Search page', [], $added, [], []);
    $this->assertDrushReports('type', 'search_page', [], array_keys($added), [], []);

    // Test the export link.
    $this->clickLink('Export');
    $session = $this->assertSession();
    $session->pageTextContains('Here is your configuration:');
    $session->pageTextContains('id: test');
    $session->pageTextContains('label: test');
    $session->pageTextContains('path: test');
    $session->pageTextContains('search.page.test.yml');

    // Test the delete link.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->clickLink('Delete');
    $session = $this->assertSession();
    $session->pageTextContains('Are you sure');
    $session->pageTextContains('cannot be undone');
    $this->drupalPostForm(NULL, [], 'Delete');
    $session = $this->assertSession();
    $session->pageTextContains('has been deleted');

    // And verify the report again.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);

    // Change the search module config and verify the actions work for
    // simple config.
    $this->drupalPostForm('admin/config/search/pages', [
      'minimum_word_size' => 4,
    ], 'Save configuration');
    $changed = ['search.settings' => 'search.settings'];
    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->assertReport('Search module', [], [], $changed, [], ['added']);

    $this->clickLink('Show differences');
    $session = $this->assertSession();
    $session->pageTextContains('Config difference for Simple configuration search.settings');
    $session->pageTextContains('index::minimum_word_size');
    $session->pageTextContains('4');

    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->clickLink('Export');
    $session = $this->assertSession();
    $session->pageTextContains('minimum_word_size: 4');
    // Grab the hash line for the next test.
    $text = strip_tags($this->getSession()->getPage()->find('css', 'textarea')->getHtml());
    $matches = [];
    preg_match('|^.*default_config_hash:.*$|m', $text, $matches);
    $hash_line = trim($matches[0]);

    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->clickLink('Revert to source');
    $this->drupalPostForm(NULL, [], 'Revert');

    // Verify that the hash was retained in the revert.
    $this->drupalGet('admin/config/development/configuration/single/export/system.simple/search.settings');
    $session = $this->assertSession();
    $session->pageTextContains($hash_line);

    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->assertReport('Search module', [], [], [], [], ['added']);

    // Edit the plain_text filter from the filter UI and verify report.
    // The filter_format config type uses a label key other than 'label'.
    $this->drupalPostForm('admin/config/content/formats/manage/plain_text', [
      'name' => 'New label',
    ], 'Save configuration');
    $changed = ['filter.format.plain_text' => 'New label'];
    $this->drupalGet('admin/config/development/configuration/report/type/filter_format');
    $this->assertReport('Text format', [], [], $changed, []);

    // Verify that we can revert non-entity configuration in Drush. Issue:
    // https://www.drupal.org/project/config_update/issues/2935395
    drush_config_update_ui_config_revert('system.date');
  }

  /**
   * Asserts that the report page has the correct content.
   *
   * Assumes you are already on the report page.
   *
   * @param string $title
   *   Report title to check for.
   * @param string[] $missing
   *   Array of items that should be listed as missing, name => label.
   * @param string[] $added
   *   Array of items that should be listed as added, name => label.
   * @param string[] $changed
   *   Array of items that should be listed as changed, name => label.
   * @param string[] $inactive
   *   Array of items that should be listed as inactive, name => label.
   * @param string[] $skip
   *   Array of report sections to skip checking.
   */
  protected function assertReport($title, array $missing, array $added, array $changed, array $inactive, array $skip = []) {
    $session = $this->assertSession();
    $session->pageTextContains('Configuration updates report for ' . $title);
    $session->pageTextContains('Generate new report');

    if (!in_array('missing', $skip)) {
      $session->pageTextContains('Missing configuration items');
      if (count($missing)) {
        foreach ($missing as $name => $label) {
          $session->pageTextContains($name);
          $session->pageTextContains($label);
        }
        $session->pageTextNotContains('None: all provided configuration items are in your active configuration.');
      }
      else {
        $session->pageTextContains('None: all provided configuration items are in your active configuration.');
      }
    }

    if (!in_array('inactive', $skip)) {
      $session->pageTextContains('Inactive optional items');
      if (count($inactive)) {
        foreach ($inactive as $name => $label) {
          $session->pageTextContains($name);
          $session->pageTextContains($label);
        }
        $session->pageTextNotContains('None: all optional configuration items are in your active configuration.');
      }
      else {
        $session->pageTextContains('None: all optional configuration items are in your active configuration.');
      }
    }

    if (!in_array('added', $skip)) {
      $session->pageTextContains('Added configuration items');
      if (count($added)) {
        foreach ($added as $name => $label) {
          $session->pageTextContains($name);
          $session->pageTextContains($label);
        }
        $session->pageTextNotContains('None: all active configuration items of this type were provided by modules, themes, or install profile.');
      }
      else {
        $session->pageTextContains('None: all active configuration items of this type were provided by modules, themes, or install profile.');
      }
    }

    if (!in_array('changed', $skip)) {
      $session->pageTextContains('Changed configuration items');
      if (count($changed)) {
        foreach ($changed as $name => $label) {
          $session->pageTextContains($name);
          $session->pageTextContains($label);
        }
        $session->pageTextNotContains('None: no active configuration items differ from their current provided versions.');
      }
      else {
        $session->pageTextContains('None: no active configuration items differ from their current provided versions.');
      }
    }
  }

  /**
   * Asserts that the Drush reports have the correct content.
   *
   * @param string $type
   *   Type of report to run (type, module, theme, etc.).
   * @param string $name
   *   Name of that type to run (e.g., module machine name).
   * @param string[] $missing
   *   Array of config items that should be listed as missing.
   * @param string[] $added
   *   Array of config items that should be listed as added.
   * @param string[] $changed
   *   Array of config items that should be listed as changed.
   * @param string[] $inactive
   *   Array of config items that should be listed as inactive.
   * @param string[] $skip
   *   Array of report sections to skip checking.
   */
  protected function assertDrushReports($type, $name, array $missing, array $added, array $changed, array $inactive, array $skip = []) {
    if (!in_array('missing', $skip)) {
      $output = drush_config_update_ui_config_missing_report($type, $name);
      $this->assertEquals(count($output), count($missing), 'Drush missing report has correct number of items');
      if (count($missing)) {
        foreach ($missing as $item) {
          $this->assertTrue(in_array($item, $output), "Item $item is in the Drush missing report");
        }
      }
    }

    if (!in_array('added', $skip) && $type == 'type') {
      $output = drush_config_update_ui_config_added_report($name);
      $this->assertEquals(count($output), count($added), 'Drush added report has correct number of items');
      if (count($added)) {
        foreach ($added as $item) {
          $this->assertTrue(in_array($item, $output), "Item $item is in the Drush added report");
        }
      }
    }

    if (!in_array('changed', $skip)) {
      $output = drush_config_update_ui_config_different_report($type, $name);
      $this->assertEquals(count($output), count($changed), 'Drush changed report has correct number of items');
      if (count($changed)) {
        foreach ($changed as $item) {
          $this->assertTrue(in_array($item, $output), "Item $item is in the Drush changed report");
        }
      }
    }

    if (!in_array('inactive', $skip)) {
      $output = drush_config_update_ui_config_inactive_report($type, $name);
      $this->assertEquals(count($output), count($inactive), 'Drush inactive report has correct number of items');
      if (count($inactive)) {
        foreach ($inactive as $item) {
          $this->assertTrue(in_array($item, $output), "Item $item is in the Drush inactive report");
        }
      }
    }
  }

  /**
   * Asserts that the report is not shown.
   *
   * Assumes you are already on the report form page.
   */
  protected function assertNoReport() {
    $session = $this->assertSession();
    $session->pageTextContains('Report type');
    $session->pageTextContains('Full report');
    $session->pageTextContains('Single configuration type');
    $session->pageTextContains('Single module');
    $session->pageTextContains('Single theme');
    $session->pageTextContains('Installation profile');
    $session->pageTextContains('Updates report');
    $session->pageTextNotContains('Missing configuration items');
    $session->pageTextNotContains('Added configuration items');
    $session->pageTextNotContains('Changed configuration items');
    $session->pageTextNotContains('Unchanged configuration items');

    // Verify that certain report links are shown or not shown. For extensions,
    // only extensions that have configuration should be shown.
    // Modules.
    $session->linkExists('Search');
    $session->linkExists('Field');
    $session->linkNotExists('Configuration Update Base');
    $session->linkNotExists('Configuration Update Reports');

    // Themes.
    $session->linkNotExists('Stark');
    $session->linkNotExists('Classy');

    // Profiles.
    $session->linkExists('Testing');

    // Configuration types.
    $session->linkExists('Everything');
    $session->linkExists('Simple configuration');
    $session->linkExists('Search page');
  }

}
