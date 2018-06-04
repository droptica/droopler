<?php

namespace Drupal\Tests\link_attributes\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Component\Utility\Unicode;
use Drupal\field_ui\Tests\FieldUiTestTrait;

/**
 * Tests link attributes functionality.
 *
 * @group link_attributes
 */
class LinkAttributesFieldTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'link_attributes',
    'field_ui',
    'block',
  ];

  /**
   * A user that can edit content types.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer content types', 'administer node fields', 'administer node display']);
    $this->drupalLogin($this->adminUser);
    // Breadcrumb is required for FieldUiTestTrait::fieldUIAddNewField.
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests the display of attributes in the widget.
   */
  public function testWidget() {
    // Add a content type.
    $type = $this->drupalCreateContentType();
    $type_path = 'admin/structure/types/manage/' . $type->id();
    $add_path = 'node/add/' . $type->id();

    // Add a link field to the newly-created type.
    $label = $this->randomMachineName();
    $field_name = Unicode::strtolower($label);
    $this->fieldUIAddNewField($type_path, $field_name, $label, 'link');

    // Change the link widget and enable some attributes.
    \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.' . $type->id() . '.default')
      ->setComponent('field_' . $field_name, [
        'type' => 'link_attributes',
        'settings' => [
          'enabled_attributes' => [
            'rel' => TRUE,
            'class' => TRUE,
          ],
        ],
      ])
      ->save();

    // Check if the link field have the attributes displayed on node add page.
    $this->drupalGet($add_path);
    $web_assert = $this->assertSession();
    // Link attributes.
    $web_assert->elementExists('css', '.field--widget-link-attributes');

    // Rel attribute.
    $attribute_rel = 'field_' . $field_name . '[0][options][attributes][rel]';
    $web_assert->fieldExists($attribute_rel);

    // Class attribute.
    $attribute_class = 'field_' . $field_name . '[0][options][attributes][class]';
    $web_assert->fieldExists($attribute_class);
  }
}
