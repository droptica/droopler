<?php

namespace Drupal\Tests\modules_weight\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the modules list form.
 *
 * @group modules_weight
 */
class ModulesListFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['modules_weight'];

  /**
   * Tests the modules list form.
   */
  public function testModulesListForm() {
    // Going to the modules list page.
    $this->drupalGet('/admin/config/system/modules-weight');

    // Checking that the page is not accesible for anonymous users.
    $this->assertSession()->statusCodeEquals(403);

    // Creating a user with the module permission.
    $account = $this->drupalCreateUser(['administer modules weight', 'access administration pages']);
    // Log in.
    $this->drupalLogin($account);

    // Going to the modules list page.
    $this->drupalGet('/admin/config/system/modules-weight');
    // Checking that the request has succeeded.
    $this->assertSession()->statusCodeEquals(200);

    // Checking the page title.
    $this->assertSession()->elementTextContains('css', 'h1', 'Modules Weight');

    // Checking Modules Weight information.
    $this->assertSession()->elementTextContains('css', '#edit-modules > tbody > tr > td:nth-child(1)', 'Modules Weight');
    $this->assertSession()->elementTextContains('css', '#edit-modules > tbody > tr > td:nth-child(2)', 'Allows to change the modules execution order.');
    $this->assertSession()->elementTextContains('css', '#edit-modules > tbody > tr > td:nth-child(4)', 'Development');
    // Checking the value in the select.
    $this->assertEquals(0, $this->getSession()->getPage()->findField('modules[modules_weight][weight]')->getValue());

    // Checking that Core modules are not displayed.
    $this->assertSession()->elementTextNotContains('css', '#edit-modules > tbody > tr > td:nth-child(4)', 'Core');

    // Going to the config page.
    $this->drupalGet('/admin/config/system/modules-weight/configuration');

    // Form values to send (checking check checkbox).
    $edit = [
      'show_system_modules' => 1,
    ];
    // Sending the form.
    $this->drupalPostForm(NULL, $edit, 'op');

    // Going to the modules list page.
    $this->drupalGet('/admin/config/system/modules-weight');

    // Checking that the Core modules are displayed.
    $this->assertSession()->elementTextContains('css', '#edit-modules > tbody > tr > td:nth-child(4)', 'Core');

    // Sending the form without changes.
    $this->drupalPostForm(NULL, [], 'op');

    // Checking the message.
    $this->assertSession()->elementTextContains('css', '.messages', 'You don\'t have changed the weight for any module.');

    // The new modules weight values to send.
    $edit = [
      'modules[dynamic_page_cache][weight]' => 15,
      'modules[modules_weight][weight]' => -3,
    ];
    // Sending the form.
    $this->drupalPostForm(NULL, $edit, 'op');

    // Verifiying the save message.
    $this->assertSession()->pageTextContains('The modules weight was updated.');
    $this->assertSession()->pageTextContains('Internal Dynamic Page Cache have now as weight: 15');
    $this->assertSession()->pageTextContains('Modules Weight have now as weight: -3');

    // Checking the value in the selects.
    $this->assertEquals(15, $this->getSession()->getPage()->findField('modules[dynamic_page_cache][weight]')->getValue());
    $this->assertEquals(0, $this->getSession()->getPage()->findField('modules[page_cache][weight]')->getValue());
    $this->assertEquals(-3, $this->getSession()->getPage()->findField('modules[modules_weight][weight]')->getValue());
    $this->assertEquals(0, $this->getSession()->getPage()->findField('modules[system][weight]')->getValue());
    $this->assertEquals(0, $this->getSession()->getPage()->findField('modules[user][weight]')->getValue());

    // Getting the modules weight values.
    $modules_weight = $this->container->get('config.factory')->get('core.extension')->get('module');
    // Verifying the weight values.
    $this->assertEquals(15, $modules_weight['dynamic_page_cache']);
    $this->assertEquals(0, $modules_weight['page_cache']);
    $this->assertEquals(-3, $modules_weight['modules_weight']);
    $this->assertEquals(0, $modules_weight['system']);
    $this->assertEquals(0, $modules_weight['user']);
  }

}
