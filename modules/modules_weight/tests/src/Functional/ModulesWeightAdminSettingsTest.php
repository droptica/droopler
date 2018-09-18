<?php

namespace Drupal\Tests\modules_weight\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test form the module configurations.
 *
 * @group modules_weight
 */
class ModulesWeightAdminSettingsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['modules_weight'];

  /**
   * Tests the configuration form, the permission and the link.
   */
  public function testConfigurationForm() {
    // Going to the config page.
    $this->drupalGet('/admin/config/system/modules-weight/configuration');

    // Checking that the page is not accesible for anonymous users.
    $this->assertSession()->statusCodeEquals(403);

    // Creating a user with the module permission.
    $account = $this->drupalCreateUser(['administer modules weight', 'access administration pages']);
    // Log in.
    $this->drupalLogin($account);

    // Checking the module link.
    $this->drupalGet('/admin/config/system');
    $this->assertSession()->linkByHrefExists('/admin/config/system/modules-weight');

    // Going to the config page.
    $this->drupalGet('/admin/config/system/modules-weight/configuration');
    // Checking that the request has succeeded.
    $this->assertSession()->statusCodeEquals(200);

    // Checking the page title.
    $this->assertSession()->elementTextContains('css', 'h1', 'Modules Weight Settings');
    // Check that the checkbox is unchecked.
    $this->assertSession()->checkboxNotChecked('show_system_modules');

    // Form values to send (checking check checkbox).
    $edit = [
      'show_system_modules' => 1,
    ];
    // Sending the form.
    $this->drupalPostForm(NULL, $edit, 'op');
    // Verifiying the save message.
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Getting the config factory service.
    $config_factory = $this->container->get('config.factory');

    // Getting variables.
    $show_system_modules = $config_factory->get('modules_weight.settings')->get('show_system_modules');

    // Verifiying that the config values are stored.
    $this->assertTrue($show_system_modules, 'The configuration value for show_system_modules should be TRUE.');

    // Form values to send (checking uncheck checkbox).
    $edit = [
      'show_system_modules' => 0,
    ];
    // Sending the form.
    $this->drupalPostForm(NULL, $edit, 'op');

    // Getting variables.
    $show_system_modules = $config_factory->get('modules_weight.settings')->get('show_system_modules');
    // Verifiying that the config values are stored.
    $this->assertFalse($show_system_modules, 'The configuration value for show_system_modules should be FALSE.');
  }

}
