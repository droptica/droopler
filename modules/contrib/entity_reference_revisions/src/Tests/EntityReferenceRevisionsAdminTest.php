<?php

namespace Drupal\entity_reference_revisions\Tests;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the entity_reference_revisions configuration.
 *
 * @group entity_reference_revisions
 */
class EntityReferenceRevisionsAdminTest extends WebTestBase {

  use FieldUiTestTrait;
  use EntityReferenceRevisionsCoreVersionUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'node',
    'field',
    'entity_reference_revisions',
    'field_ui',
    'block',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create paragraphs and article content types.
    $this->drupalCreateContentType(array('type' => 'entity_revisions', 'name' => 'Entity revisions'));
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer nodes',
      'create article content',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'edit any article content',
    ));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the entity reference revisions configuration.
   */
  public function testEntityReferenceRevisions() {
    // Create a test target node used as entity reference by another test node.
    $node_target = Node::create([
      'title' => 'Target node',
      'type' => 'article',
      'body' => 'Target body text',
      'uuid' => '2d04c2b4-9c3d-4fa6-869e-ecb6fa5c9410',
    ]);
    $node_target->save();

    // Add an entity reference revisions field to entity_revisions content type
    // with $node_target as default value.
    $storage_edit = ['settings[target_type]' => 'node', 'cardinality' => '-1'];
    $field_edit = [
      'settings[handler_settings][target_bundles][article]' => TRUE,
      'default_value_input[field_entity_reference_revisions][0][target_id]' => $node_target->label() . ' (' . $node_target->id() . ')',
    ];
    static::fieldUIAddNewField('admin/structure/types/manage/entity_revisions', 'entity_reference_revisions', 'Entity reference revisions', 'entity_reference_revisions', $storage_edit, $field_edit);
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $this->assertText('Saved Entity reference revisions configuration.');

    // Resave the target node, so that the default revision is not the one we
    // want to use.
    $revision_id = $node_target->getRevisionId();
    $node_target_after = Node::load($node_target->id());
    $node_target_after->setNewRevision();
    $node_target_after->save();
    $this->assertTrue($node_target_after->getRevisionId() != $revision_id);

    // Create an article.
    $title = $this->randomMachineName();
    $edit = array(
      'title[0][value]' => $title,
      'body[0][value]' => 'Revision 1',
    );
    $this->drupalPostNodeForm('node/add/article', $edit, t('Save and publish'));
    $this->assertText($title);
    $this->assertText('Revision 1');
    $node = $this->drupalGetNodeByTitle($title);

    // Check if when creating an entity revisions content the default entity
    // reference is set, add also the above article as a new reference.
    $this->drupalGet('node/add/entity_revisions');
    $this->assertFieldByName('field_entity_reference_revisions[0][target_id]', $node_target->label() . ' (' . $node_target->id() . ')');
    $edit = [
      'title[0][value]' => 'Entity reference revision content',
      'field_entity_reference_revisions[1][target_id]' => $node->label() . ' (' . $node->id() . ')',
    ];
    $this->drupalPostNodeForm(NULL, $edit, t('Save and publish'));
    $this->assertLinkByHref('node/' . $node_target->id());
    $this->assertText('Entity revisions Entity reference revision content has been created.');
    $this->assertText('Entity reference revision content');
    $this->assertText($title);
    $this->assertText('Revision 1');

    // Create 2nd revision of the article.
    $edit = array(
      'body[0][value]' => 'Revision 2',
      'revision' => TRUE,
    );
    $this->drupalPostNodeForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $this->assertText($title);
    $this->assertText('Revision 2');

    // View the Entity reference content and make sure it still has revision 1.
    $node = $this->drupalGetNodeByTitle('Entity reference revision content');
    $this->drupalGet('node/' . $node->id());
    $this->assertText($title);
    $this->assertText('Revision 1');
    $this->assertNoText('Revision 2');

    // Make sure the non-revisionable entities are not selectable as referenced
    // entities.
    $edit = array(
      'new_storage_type' => 'entity_reference_revisions',
      'label' => 'Entity reference revisions field',
      'field_name' => 'entity_ref_revisions_field',
    );
    $this->drupalPostForm('admin/structure/types/manage/entity_revisions/fields/add-field', $edit, t('Save and continue'));
    $this->assertNoOption('edit-settings-target-type', 'user');
    $this->assertOption('edit-settings-target-type', 'node');

    // Check ERR default value and property definitions label are set properly.
    $field_definition = $node->getFieldDefinition('field_entity_reference_revisions');
    $default_value = $field_definition->toArray()['default_value'];
    $this->assertEqual($default_value[0]['target_uuid'], $node_target->uuid());
    $this->assertEqual($default_value[0]['target_revision_id'], $revision_id);
    $properties = $field_definition->getFieldStorageDefinition()->getPropertyDefinitions();
    $this->assertEqual((string) $properties['target_revision_id']->getLabel(), 'Content revision ID');
    $this->assertEqual((string) $properties['target_id']->getLabel(), 'Content ID');
    $this->assertEqual((string) $properties['entity']->getLabel(), 'Content');
  }

  /**
   * Tests target bundle settings for an entity reference revisions field.
   */
  public function testMultipleTargetBundles() {
    // Create a couple of content types for the ERR field to point to.
    $target_types = [];
    for ($i = 0; $i < 2; $i++) {
      $target_types[$i] = $this->drupalCreateContentType([
        'type' => strtolower($this->randomMachineName()),
        'name' => 'Test type ' . $i
      ]);
    }

    // Create a new field that can point to either target content type.
    $node_type_path = 'admin/structure/types/manage/entity_revisions';

    // Generate a random field name, must be only lowercase characters.
    $field_name = strtolower($this->randomMachineName());

    $field_edit = [];
    $storage_edit = ['settings[target_type]' => 'node', 'cardinality' => '-1'];
    $field_edit['settings[handler_settings][target_bundles][' . $target_types[0]->id() . ']'] = TRUE;
    $field_edit['settings[handler_settings][target_bundles][' . $target_types[1]->id() . ']'] = TRUE;

    $this->fieldUIAddNewField($node_type_path, $field_name, 'Entity reference revisions', 'entity_reference_revisions', $storage_edit, $field_edit);

    // Deleting one of these content bundles at this point should only delete
    // that bundle's body field. Test that there is no second field that will
    // be deleted.
    $this->drupalGet('/admin/structure/types/manage/' . $target_types[0]->id() . '/delete');
    $this->assertNoFieldByXPath('(//details[@id="edit-entity-deletes"]//ul[@data-drupal-selector="edit-field-config"]/li)[2]');
  }

}
