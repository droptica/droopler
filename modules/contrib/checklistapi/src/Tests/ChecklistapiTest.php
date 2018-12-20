<?php

namespace Drupal\checklistapi\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Functionally tests Checklist API.
 *
 * @group checklistapi
 *
 * @todo Add tests for vertical tabs progress indicators.
 * @todo Add tests for saving and retrieving checklist progress.
 * @todo Add tests for clearing saved progress.
 */
class ChecklistapiTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'checklistapi',
    'checklistapiexample',
    'help',
    'block',
  ];

  /**
   * A user object with permission to edit any checklist.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $privilegedUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a privileged user.
    $permissions = ['edit any checklistapi checklist'];
    $this->privilegedUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->privilegedUser);

    // Place help block.
    $this->drupalPlaceBlock('help_block', ['region' => 'help']);
  }

  /**
   * Tests checklist access.
   */
  public function testChecklistAccess() {
    $this->drupalGet('admin/config/development/checklistapi-example');
    $this->assertResponse(200, 'Granted access to user with "edit any checklistapi checklist" permission.');

    $permissions = ['edit example_checklist checklistapi checklist'];
    $semi_privileged_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($semi_privileged_user);
    $this->drupalGet('admin/config/development/checklistapi-example');
    $this->assertResponse(200, 'Granted access to user with checklist-specific permission.');

    $this->drupalLogout();
    $this->drupalGet('admin/config/development/checklistapi-example');
    $this->assertResponse(403, 'Denied access to non-privileged user.');
  }

  /**
   * Tests checklist composition.
   */
  public function testChecklistComposition() {
    $this->drupalGet('admin/config/development/checklistapi-example');
    $this->assertRaw('This checklist based on', 'Created per-checklist help block.');
  }

  /**
   * Tests permissions.
   */
  public function testPermissions() {
    $this->assertTrue($this->checkPermissions([
      'view checklistapi checklists report',
      'view any checklistapi checklist',
      'edit any checklistapi checklist',
    ]), 'Created universal permissions.');
    $this->assertTrue($this->checkPermissions([
      'view example_checklist checklistapi checklist',
      'edit example_checklist checklistapi checklist',
    ]), 'Created per-checklist permissions.');
  }

}
