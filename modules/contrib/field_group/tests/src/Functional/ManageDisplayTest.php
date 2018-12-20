<?php

namespace Drupal\Tests\field_group\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for managing display of entities.
 *
 * @group field_group
 */
class ManageDisplayTest extends BrowserTestBase {

  use FieldGroupTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = array('node', 'field_ui', 'field_group');

  /**
   * Content type id.
   *
   * @var string
   */
  protected $type;

  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = 'll4ma_test';
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);
    $this->type = $type->id();

  }

  /**
   * Test the creation a group on the article content type.
   */
  public function testCreateGroup() {
    // Create random group name.
    $group_label = $this->randomString(8);
    $group_name_input = Unicode::strtolower($this->randomMachineName());
    $group_name = 'group_' . $group_name_input;
    $group_formatter = 'details';

    // Setup new group.
    $group = array(
      'group_formatter' => $group_formatter,
      'label' => $group_label,
    );

    $add_form_display = 'admin/structure/types/manage/' . $this->type . '/form-display/add-group';
    $this->drupalPostForm($add_form_display, $group, 'Save and continue');
    $this->assertSession()->pageTextContains('Machine-readable name field is required.');

    // Add required field to form.
    $group['group_name'] = $group_name_input;

    // Add new group on the 'Manage form display' page.
    $this->drupalPostForm($add_form_display, $group, 'Save and continue');
    $this->drupalPostForm(NULL, [], 'Create group');

    $this->assertSession()->responseContains(t('New group %label successfully created.', array('%label' => $group_label)));

    // Test if group is in the $groups array.
    $this->group = field_group_load_field_group($group_name, 'node', $this->type, 'form', 'default');
    $this->assertNotNull($group, 'Group was loaded');

    // Add new group on the 'Manage display' page.
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/display/add-group', $group, 'Save and continue');
    $this->drupalPostForm(NULL, [], 'Create group');

    $this->assertSession()->responseContains(t('New group %label successfully created.', array('%label' => $group_label)));

    // Test if group is in the $groups array.
    $loaded_group = field_group_load_field_group($group_name, 'node', $this->type, 'view', 'default');
    $this->assertNotNull($loaded_group, 'Group was loaded');
  }

  /**
   * Delete a group.
   */
  public function testDeleteGroup() {
    $data = array(
      'format_type' => 'fieldset',
      'label' => 'testing',
    );

    $group = $this->createGroup('node', $this->type, 'form', 'default', $data);

    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/form-display/' . $group->group_name . '/delete', array(), 'Delete');
    $this->assertSession()->responseContains(t('The group %label has been deleted from the %type content type.', array('%label' => $group->label, '%type' => $this->type)));

    // Test that group is not in the $groups array.
    \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->resetCache();
    $loaded_group = field_group_load_field_group($group->group_name, 'node', $this->type, 'form', 'default');
    $this->assertNull($loaded_group, 'Group not found after deleting');

    $data = array(
      'format_type' => 'fieldset',
      'label' => 'testing',
    );

    $group = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/display/' . $group->group_name . '/delete', array(), t('Delete'));
    $this->assertRaw(t('The group %label has been deleted from the %type content type.', array('%label' => $group->label, '%type' => $this->type)));

    // Test that group is not in the $groups array.
    \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->resetCache();
    $loaded_group = field_group_load_field_group($group->group_name, 'node', $this->type, 'view', 'default');
    $this->assertNull($loaded_group, 'Group not found after deleting');
  }

  /**
   * Nest a field underneath a group.
   */
  public function testNestField() {
    $data = array(
      'format_type' => 'fieldset',
    );

    $group = $this->createGroup('node', $this->type, 'form', 'default', $data);

    $edit = array(
      'fields[body][parent]' => $group->group_name,
    );
    $this->drupalPostForm('admin/structure/types/manage/' . $this->type . '/form-display', $edit, 'Save');
    $this->assertRaw('Your settings have been saved.');

    $group = field_group_load_field_group($group->group_name, 'node', $this->type, 'form', 'default');
    $this->assertTrue(in_array('body', $group->children), t('Body is a child of %group', array('%group' => $group->group_name)));
  }

}
