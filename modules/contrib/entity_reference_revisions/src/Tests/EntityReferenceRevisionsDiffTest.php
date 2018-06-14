<?php

namespace Drupal\entity_reference_revisions\Tests;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the entity_reference_revisions diff plugin.
 *
 * @group entity_reference_revisions
 *
 * @dependencies diff
 */
class EntityReferenceRevisionsDiffTest extends WebTestBase {

  use FieldUiTestTrait;
  use EntityReferenceRevisionsCoreVersionUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block_content',
    'node',
    'field',
    'entity_reference_revisions',
    'field_ui',
    'diff',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create article content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Disable visual inline diff.
    $config = $this->config('diff.settings')
      ->set('general_settings.layout_plugins.visual_inline.enabled', FALSE);
    $config->save();

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer nodes',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'view all revisions',
      'edit any article content',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Test for diff plugin of ERR.
   *
   * Tests that the diff is displayed when changes are made in an ERR field.
   */
  public function testEntityReferenceRevisionsDiff() {
    // Add an entity_reference_revisions field.
    static::fieldUIAddNewField('admin/structure/types/manage/article', 'err_field', 'err_field', 'entity_reference_revisions', [
      'settings[target_type]' => 'node',
      'cardinality' => '-1',
    ], [
      'settings[handler_settings][target_bundles][article]' => TRUE,
    ]);

    // Create first referenced node.
    $title_node_1 = 'referenced_node_1';
    $edit = [
      'title[0][value]' => $title_node_1,
      'body[0][value]' => 'body_node_1',
    ];
    $this->drupalPostNodeForm('node/add/article', $edit, t('Save and publish'));

    // Create second referenced node.
    $title_node_2 = 'referenced_node_2';
    $edit = [
      'title[0][value]' => $title_node_2,
      'body[0][value]' => 'body_node_2',
    ];
    $this->drupalPostNodeForm('node/add/article', $edit, t('Save and publish'));

    // Create referencing node.
    $title = 'referencing_node';
    $node = $this->drupalGetNodeByTitle($title_node_1);
    $edit = [
      'title[0][value]' => $title,
      'field_err_field[0][target_id]' => $title_node_1 . ' (' . $node->id() . ')',
    ];
    $this->drupalPostNodeForm('node/add/article', $edit, t('Save and publish'));

    // Check the plugin is set.
    $this->drupalGet('admin/config/content/diff/fields');
    $this->drupalPostForm(NULL, ['fields[node.field_err_field][plugin][type]' => 'entity_reference_revisions_field_diff_builder'], t('Save'));

    // Update the referenced node of the err field and create a new revision.
    $node = $this->drupalGetNodeByTitle($title);
    $referenced_node_new = $this->drupalGetNodeByTitle($title_node_2);
    $edit = [
      'field_err_field[0][target_id]' => $title_node_2 . ' (' . $referenced_node_new->id() . ')',
      'revision' => TRUE,
    ];
    $this->drupalPostNodeForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Compare the revisions of the referencing node.
    $this->drupalPostForm('node/' . $node->id() . '/revisions', [], t('Compare selected revisions'));

    // Assert the field changes.
    $this->assertRaw('class="diff-context diff-deletedline">' . $title_node_1);
    $this->assertRaw('class="diff-context diff-addedline">' . $title_node_2);
  }

}
