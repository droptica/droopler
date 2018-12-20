<?php

namespace Drupal\Tests\entity_reference_revisions\Kernel;

use Drupal\entity_composite_relationship_test\Entity\EntityTestCompositeRelationship;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the entity_reference_revisions composite relationship.
 *
 * @group entity_reference_revisions
 */
class EntityReferenceRevisionsCompositeTest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'node',
    'field',
    'entity_reference_revisions',
    'entity_composite_relationship_test',
    'language'
  );

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   *
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_composite');
    $this->installSchema('node', ['node_access']);

    // Create article content type.
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    // Create the reference to the composite entity test.
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => 'composite_reference',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'settings' => array(
        'target_type' => 'entity_test_composite'
      ),
    ));
    $field_storage->save();
    $field = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'translatable' => FALSE,
    ));
    $field->save();

    // Inject database connection and entity type manager for the tests.
    $this->database = \Drupal::database();
    $this->entityTypeManager = \Drupal::entityTypeManager();
  }

  /**
   * Test for maintaining composite relationship.
   *
   * Tests that the referenced entity saves the parent type and id when saving.
   */
  public function testEntityReferenceRevisionsCompositeRelationship() {
    // Create the test composite entity.
    $composite = EntityTestCompositeRelationship::create(array(
      'uuid' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
    ));
    $composite->save();

    // Assert that there is only 1 revision of the composite entity.
    $composite_revisions_count = \Drupal::entityQuery('entity_test_composite')->condition('uuid', $composite->uuid())->allRevisions()->count()->execute();
    $this->assertEquals(1, $composite_revisions_count);

    // Create a node with a reference to the test composite entity.
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create(array(
      'title' => $this->randomMachineName(),
      'type' => 'article',
    ));
    $node->save();
    $node->set('composite_reference', $composite);
    $this->assertTrue($node->hasTranslationChanges());
    $node->save();

    // Assert that there is only 1 revision when creating a node.
    $node_revisions_count = \Drupal::entityQuery('node')->condition('nid', $node->id())->allRevisions()->count()->execute();
    $this->assertEqual($node_revisions_count, 1);
    // Assert there is no new composite revision after creating a host entity.
    $composite_revisions_count = \Drupal::entityQuery('entity_test_composite')->condition('uuid', $composite->uuid())->allRevisions()->count()->execute();
    $this->assertEquals(1, $composite_revisions_count);

    // Verify the value of parent type and id after create a node.
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertEqual($composite->parent_type->value, $node->getEntityTypeId());
    $this->assertEqual($composite->parent_id->value, $node->id());
    $this->assertEqual($composite->parent_field_name->value, 'composite_reference');
    // Create second revision of the node.
    $original_composite_revision = $node->composite_reference[0]->target_revision_id;
    $original_node_revision = $node->getRevisionId();
    $node->setTitle('2nd revision');
    $node->setNewRevision();
    $node->save();
    $node = node_load($node->id(), TRUE);
    // Check the revision of the node.
    $this->assertEqual('2nd revision', $node->getTitle(), 'New node revision has changed data.');
    $this->assertNotEqual($original_composite_revision, $node->composite_reference[0]->target_revision_id, 'Composite entity got new revision when its host did.');

    // Make sure that there are only 2 revisions.
    $node_revisions_count = \Drupal::entityQuery('node')->condition('nid', $node->id())->allRevisions()->count()->execute();
    $this->assertEqual($node_revisions_count, 2);

    // Revert to first revision of the node.
    $node = $this->entityTypeManager->getStorage('node')->loadRevision($original_node_revision);
    $node->setNewRevision();
    $node->isDefaultRevision(TRUE);
    $node->save();
    $node = node_load($node->id(), TRUE);
    // Check the revision of the node.
    $this->assertNotEqual('2nd revision', $node->getTitle(), 'Node did not keep changed title after reversion.');
    $this->assertNotEqual($original_composite_revision, $node->composite_reference[0]->target_revision_id, 'Composite entity got new revision when its host reverted to an old revision.');

    // Test that removing/changing composite references results in translation
    // changes.
    $node->set('composite_reference', []);
    $this->assertTrue($node->hasTranslationChanges());

    // Revert the changes to avoid interfering with the delete test.
    $node->set('composite_reference', $composite);

    // Test that the composite entity is deleted when its parent is deleted.
    $node->delete();
    $this->assertNull(EntityTestCompositeRelationship::load($composite->id()));
  }

  /**
   * Tests composite relationship with translations and an untranslatable field.
   */
  function testCompositeRelationshipWithTranslationNonTranslatableField() {

    ConfigurableLanguage::createFromLangcode('de')->save();

    // Create the test composite entity with a translation.
    $composite = EntityTestCompositeRelationship::create(array(
      'uuid' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
    ));
    $composite->addTranslation('de', $composite->toArray());
    $composite->save();


    // Create a node with a reference to the test composite entity.
    $node = Node::create(array(
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'composite_reference' => $composite,
    ));
    $node->addTranslation('de', $node->toArray());
    $node->save();

    // Verify the value of parent type and id after create a node.
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertEqual($composite->parent_type->value, $node->getEntityTypeId());
    $this->assertEqual($composite->parent_id->value, $node->id());
    $this->assertEqual($composite->parent_field_name->value, 'composite_reference');
    $this->assertTrue($composite->hasTranslation('de'));

    // Test that the composite entity is not deleted when the german translation
    // of the parent is deleted.
    $node->removeTranslation('de');
    $node->save();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);
    $this->assertFalse($composite->hasTranslation('de'));

    // Change the language of the entity, ensure that doesn't try to delete
    // the default translation.
    $node->set('langcode', 'de');
    $node->save();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);

    // Test that the composite entity is deleted when its parent is deleted.
    $node->delete();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNull($composite);
  }

  /**
   * Tests composite relationship with translations and a translatable field.
   */
  function testCompositeRelationshipWithTranslationTranslatableField() {
    $field_config = FieldConfig::loadByName('node', 'article', 'composite_reference');
    $field_config->setTranslatable(TRUE);
    $field_config->save();

    ConfigurableLanguage::createFromLangcode('de')->save();

    // Create the test composite entity with a translation.
    $composite = EntityTestCompositeRelationship::create(array(
      'uuid' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
    ));
    $composite->addTranslation('de', $composite->toArray());
    $composite->save();

    // Create a node with a reference to the test composite entity.
    $node = Node::create(array(
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'composite_reference' => $composite,
    ));
    $node->addTranslation('de', $node->toArray());
    $node->save();

    // Verify the value of parent type and id after create a node.
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertEqual($composite->parent_type->value, $node->getEntityTypeId());
    $this->assertEqual($composite->parent_id->value, $node->id());
    $this->assertEqual($composite->parent_field_name->value, 'composite_reference');

    // Test that the composite entity is not when the german translation of the parent is deleted.
    $node->removeTranslation('de');
    $node->save();
    //$this->entityTypeManager->getStorage('entity_test_composite')->resetCache();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);

    // Test that the composite entity is deleted when its parent is deleted.
    $node->delete();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    // @todo Support deletions for translatable fields.
    //   @see https://www.drupal.org/node/2834374
    // $this->assertNull($composite);
  }

  /**
   * Tests composite relationship with revisions.
   */
  function testCompositeRelationshipWithRevisions() {

    // Create the test composite entity with a translation.
    $composite = EntityTestCompositeRelationship::create(array(
      'uuid' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
    ));
    $composite->save();

    // Create a node with a reference to the test composite entity.
    $node = Node::create(array(
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'composite_reference' => $composite,
    ));
    $node->save();


    // Verify the value of parent type and id after create a node.
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $composite_original_revision_id = $composite->getRevisionId();
    $node_original_revision_id = $node->getRevisionId();
    $this->assertEqual($composite->parent_type->value, $node->getEntityTypeId());
    $this->assertEqual($composite->parent_id->value, $node->id());
    $this->assertEqual($composite->parent_field_name->value, 'composite_reference');

    $node->setNewRevision(TRUE);
    $node->save();
    // Ensure that we saved a new revision ID.
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotEqual($composite->getRevisionId(), $composite_original_revision_id);

    // Test that deleting the first revision does not delete the composite.
    $this->entityTypeManager->getStorage('node')->deleteRevision($node_original_revision_id);
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);

    // Ensure that the composite revision was deleted as well.
    $composite_revision = $this->entityTypeManager->getStorage('entity_test_composite')->loadRevision($composite_original_revision_id);
    $this->assertNull($composite_revision);

    // Test that the composite entity is deleted when its parent is deleted.
    $node->delete();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNull($composite);
  }

  /**
   * Tests that the composite revision is not deleted if it is the default one.
   */
  function testCompositeRelationshipDefaultRevision() {
    // Create a node with a reference to a test composite entity.
    $composite = EntityTestCompositeRelationship::create([
      'uuid' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
    ]);
    $composite->save();
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'composite_reference' => $composite,
    ]);
    $node->save();

    $composite = EntityTestCompositeRelationship::load($composite->id());
    $composite_original_revision_id = $composite->getRevisionId();
    $node_original_revision_id = $node->getRevisionId();

    // Set a new revision, composite entity should have a new revision as well.
    $node->setNewRevision(TRUE);
    $node->save();
    // Ensure that we saved a new revision ID.
    $composite2 = EntityTestCompositeRelationship::load($composite->id());
    $composite2_rev_id = $composite2->getRevisionId();
    $this->assertNotEquals($composite2_rev_id, $composite_original_revision_id);

    // Revert default composite entity revision to the original revision.
    $composite_original = $this->entityTypeManager->getStorage('entity_test_composite')->loadRevision($composite_original_revision_id);
    $composite_original->isDefaultRevision(TRUE);
    $composite_original->save();
    // Check the default composite revision is the original composite revision.
    $this->assertEquals($composite_original_revision_id, $composite_original->getrevisionId());

    // Test deleting the first node revision, referencing to the default
    // composite revision, does not delete the default composite revision.
    $this->entityTypeManager->getStorage('node')->deleteRevision($node_original_revision_id);
    $composite_default = EntityTestCompositeRelationship::load($composite_original->id());
    $this->assertNotNull($composite_default);
    $composite_default_revision = $this->entityTypeManager->getStorage('entity_test_composite')->loadRevision($composite_original->getrevisionId());
    $this->assertNotNull($composite_default_revision);
    // Ensure the second revision still exists.
    $composite2_revision = $this->entityTypeManager->getStorage('entity_test_composite')->loadRevision($composite2_rev_id);
    $this->assertNotNull($composite2_revision);
  }

  /**
   * Tests that the composite revision is not deleted if it is still in use.
   */
  function testCompositeRelationshipDuplicatedRevisions() {
    // Create a node with a reference to a test composite entity.
    $composite = EntityTestCompositeRelationship::create([
      'uuid' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
    ]);
    $composite->save();
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'composite_reference' => $composite,
    ]);
    $node->save();

    $composite = EntityTestCompositeRelationship::load($composite->id());
    $composite_original_revision_id = $composite->getRevisionId();
    $node_original_revision_id = $node->getRevisionId();

    // Set a new revision, composite entity should have a new revision as well.
    $node->setNewRevision(TRUE);
    $node->save();
    // Ensure that we saved a new revision ID.
    $composite2 = EntityTestCompositeRelationship::load($composite->id());
    $composite2_rev_id = $composite2->getRevisionId();
    $this->assertNotEquals($composite2_rev_id, $composite_original_revision_id);

    // Set the new node revision to reference to the original composite
    // revision as well to test this composite revision will not be deleted.
    $this->database->update('node__composite_reference')
      ->fields(['composite_reference_target_revision_id' => $composite_original_revision_id])
      ->condition('revision_id', $node->getRevisionId())
      ->execute();
    $this->database->update('node_revision__composite_reference')
      ->fields(['composite_reference_target_revision_id' => $composite_original_revision_id])
      ->condition('revision_id', $node->getRevisionId())
      ->execute();

    // Test deleting the first revision does not delete the composite.
    $this->entityTypeManager->getStorage('node')->deleteRevision($node_original_revision_id);
    $composite2 = EntityTestCompositeRelationship::load($composite2->id());
    $this->assertNotNull($composite2);

    // Ensure the original composite revision is not deleted because it is
    // still referenced by the second node revision.
    $composite_original_revision = $this->entityTypeManager->getStorage('entity_test_composite')->loadRevision($composite_original_revision_id);
    $this->assertNotNull($composite_original_revision);
    // Ensure the second revision still exists.
    $composite2_revision = $this->entityTypeManager->getStorage('entity_test_composite')->loadRevision($composite2_rev_id);
    $this->assertNotNull($composite2_revision);

    // Test that the composite entity is deleted when its parent is deleted.
    $node->delete();
    $composite = EntityTestCompositeRelationship::load($composite2->id());
    $this->assertNull($composite);
  }

}
