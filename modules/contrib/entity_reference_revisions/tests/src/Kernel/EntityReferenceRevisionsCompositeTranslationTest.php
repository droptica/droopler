<?php

namespace Drupal\Tests\entity_reference_revisions\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
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
class EntityReferenceRevisionsCompositeTranslationTest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'field',
    'entity_reference_revisions',
    'entity_composite_relationship_test',
    'language',
    'content_translation'
  ];

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

    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();

    $this->installEntitySchema('entity_test_composite');
    $this->installSchema('node', ['node_access']);

    // Create article content type.
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    // Create the reference to the composite entity test.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'composite_reference',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'entity_test_composite'
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'translatable' => FALSE,
    ]);
    $field->save();

    // Create an untranslatable field on the composite entity.
    $text_field_storage = FieldStorageConfig::create([
      'field_name' => 'field_untranslatable',
      'entity_type' => 'entity_test_composite',
      'type' => 'string',
    ]);
    $text_field_storage->save();
    $text_field = FieldConfig::create([
      'field_storage' => $text_field_storage,
      'bundle' => 'entity_test_composite',
      'translatable' => FALSE,
    ]);
    $text_field->save();

    // Add a nested composite field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'composite_reference',
      'entity_type' => 'entity_test_composite',
      'type' => 'entity_reference_revisions',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'entity_test_composite'
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test_composite',
      'translatable' => FALSE,
    ]);
    $field->save();

    // Inject database connection and entity type manager for the tests.
    $this->database = \Drupal::database();
    $this->entityTypeManager = \Drupal::entityTypeManager();

    // @todo content_translation should not be needed for a storage test, but
    //   \Drupal\Core\Entity\ContentEntityBase::isTranslatable() only returns
    //   TRUE if the bundle is explicitly translatable.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'article', TRUE);
    \Drupal::service('content_translation.manager')->setEnabled('entity_test_composite', 'entity_test_composite', TRUE);
    \Drupal::service('content_translation.manager')->setBundleTranslationSettings('node', 'article', [
      'untranslatable_fields_hide' => TRUE,
    ]);
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();
  }

  /**
   * Test the storage for handling pending revisions with translations.
   */
  public function testCompositePendingRevisionTranslation() {
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    // Create a nested composite entity.
    $nested_composite = EntityTestCompositeRelationship::create([
      'langcode' => 'en',
      'name' => 'Initial Nested Source Composite',
    ]);
    $nested_composite->save();

    // Create a composite entity.
    $composite = EntityTestCompositeRelationship::create([
      'langcode' => 'en',
      'name' => 'Initial Source Composite',
      'field_untranslatable' => 'Initial untranslatable field',
      'composite_reference' => $nested_composite,
    ]);
    $composite->save();

    // Create a node with a reference to the test composite entity.
    $node = Node::create([
      'langcode' => 'en',
      'title' => 'Initial Source Node',
      'type' => 'article',
      'composite_reference' => $composite,
    ]);
    $node->save();
    $initial_revision_id = $node->getRevisionId();

    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($node->id());

    // Assert that there is only 1 revision when creating a node.
    $this->assertRevisionCount(1, $node);
    // Assert there is no new composite revision after creating a host entity.
    $this->assertRevisionCount(1, $composite);
    // Assert there is no new composite revision after creating a host entity.
    $this->assertRevisionCount(1, $nested_composite);

    // Create a second nested composite entity.
    $second_nested_composite = EntityTestCompositeRelationship::create([
      'langcode' => 'en',
      'name' => 'Initial Nested Composite #2',
    ]);

    // Add a pending revision.
    $node = $node_storage->createRevision($node, FALSE);
    $node->get('composite_reference')->entity->get('composite_reference')->appendItem($second_nested_composite);
    $node->save();
    $pending_en_revision_id = $node->getRevisionId();

    $this->assertRevisionCount(2, $node);
    $this->assertRevisionCount(2, $composite);
    $this->assertRevisionCount(2, $nested_composite);
    $this->assertRevisionCount(1, $second_nested_composite);

    // Create a DE translation, start as a draft to replicate the behavior of
    // the UI.
    $node_de = $node->addTranslation('de', ['title' => 'New Node #1 DE'] + $node->toArray());
    $node_de = $node_storage->createRevision($node_de, FALSE);

    // Despite starting of the draft revision, creating draft of the translation
    // uses the paragraphs of the default revision.
    $this->assertCount(1, $node_de->get('composite_reference')->entity->get('composite_reference'));

    $node_de->get('composite_reference')->entity->getTranslation('de')->set('name', 'New Composite #1 DE');
    $node_de->get('composite_reference')->entity->get('composite_reference')->entity->getTranslation('de')->set('name', 'New Nested Composite #1 DE');
    $node_de->isDefaultRevision(TRUE);
    $violations = $node_de->validate();
    foreach ($violations as $violation) {
      $this->fail($violation->getPropertyPath() . ': ' . $violation->getMessage());
    }
    $this->assertEquals(0, count($violations));
    $node_de->save();

    $this->assertRevisionCount(3, $node);
    $this->assertRevisionCount(3, $composite);
    $this->assertRevisionCount(3, $nested_composite);
    $this->assertRevisionCount(1, $second_nested_composite);

    // Update the translation as a pending revision for both the composite and
    // the node.
    $node_de->get('composite_reference')->entity->getTranslation('de')->set('name', 'Pending Revision Composite #1 DE');
    $node_de->get('composite_reference')->entity->get('composite_reference')->entity->getTranslation('de')->set('name', 'Pending Nested Composite #1 DE');
    $node_de->set('title', 'Pending Revision Node #1 DE');
    $node_de->setNewRevision(TRUE);
    $node_de->isDefaultRevision(FALSE);
    $violations = $node_de->validate();
    foreach ($violations as $violation) {
      $this->fail($violation->getMessage());
    }
    $this->assertEquals(0, count($violations));
    $node_de->save();

    $this->assertRevisionCount(4, $node);
    $this->assertRevisionCount(4, $composite);
    $this->assertRevisionCount(4, $nested_composite);
    $this->assertRevisionCount(1, $second_nested_composite);

    /** @var \Drupal\node\NodeInterface $node_de */
    $node_de = $node_storage->loadRevision($node_de->getRevisionId());
    $this->assertFalse($node_de->isDefaultRevision());
    $this->assertFalse((bool) $node_de->isRevisionTranslationAffected());
    $this->assertTrue((bool) $node_de->getTranslation('de')->isRevisionTranslationAffected());
    $this->assertEquals('Pending Revision Node #1 DE', $node_de->getTranslation('de')->label());
    $this->assertEquals('Initial Source Node', $node_de->label());
    $this->assertFalse($node_de->get('composite_reference')->entity->isDefaultRevision());
    $this->assertEquals('Pending Revision Composite #1 DE', $node_de->get('composite_reference')->entity->getTranslation('de')->label());
    $this->assertEquals('Pending Nested Composite #1 DE', $node_de->get('composite_reference')->entity->get('composite_reference')->entity->getTranslation('de')->label());
    $this->assertEquals('Initial untranslatable field', $node_de->get('composite_reference')->entity->getTranslation('de')->get('field_untranslatable')->value);
    $this->assertEquals('Initial Source Composite', $node_de->get('composite_reference')->entity->label());

    // Reload the default revision of the node, make sure that the composite
    // there is unchanged.
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->hasTranslation('de'));
    $this->assertEquals('Initial Source Node', $node->label());
    $this->assertTrue($node->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertEquals('Initial Source Composite', $node->get('composite_reference')->entity->label());

    // Create a FR translation, start as a draft to replicate the behavior of
    // the UI.
    $node_fr = $node->addTranslation('fr', ['title' => 'Pending Revision Node #1 FR'] + $node->toArray());
    $node_fr = $node_storage->createRevision($node_fr, FALSE);
    $node_fr->get('composite_reference')->entity->getTranslation('fr')->set('name', 'Pending Revision Composite #1 FR');
    $node_fr->get('composite_reference')->entity->get('composite_reference')->entity->getTranslation('fr')->set('name', 'Pending Nested Composite #1 FR');
    $violations = $node_fr->validate();
    $this->assertEquals(0, count($violations));
    $node_fr->save();

    // Now assert that all 3 revisions exist as expected. Two translation
    // pending revisions, each composite has the original revision as parent
    // without any existing translation.
    /** @var \Drupal\node\NodeInterface $node_fr */
    $node_fr = $node_storage->loadRevision($node_fr->getRevisionId());
    $this->assertFalse($node_fr->isDefaultRevision());
    $this->assertTrue($node_fr->hasTranslation('de'));
    $this->assertFalse((bool) $node_fr->isRevisionTranslationAffected());
    $this->assertTrue((bool) $node_fr->getTranslation('fr')->isRevisionTranslationAffected());
    $this->assertEquals('Pending Revision Node #1 FR', $node_fr->getTranslation('fr')->label());
    $this->assertEquals('Initial Source Node', $node_fr->label());
    $this->assertFalse($node_fr->get('composite_reference')->entity->isDefaultRevision());
    $this->assertTrue($node_fr->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertEquals('Pending Revision Composite #1 FR', $node_fr->get('composite_reference')->entity->getTranslation('fr')->label());
    $this->assertEquals('Pending Nested Composite #1 FR', $node_fr->get('composite_reference')->entity->get('composite_reference')->entity->getTranslation('fr')->label());
    $this->assertEquals('Initial untranslatable field', $node_fr->get('composite_reference')->entity->getTranslation('fr')->get('field_untranslatable')->value);
    $this->assertEquals('Initial Source Composite', $node_fr->get('composite_reference')->entity->label());

    $node_de = $node_storage->loadRevision($node_de->getRevisionId());
    $this->assertFalse($node_de->isDefaultRevision());
    $this->assertFalse($node_de->hasTranslation('fr'));
    $this->assertEquals('Pending Revision Node #1 DE', $node_de->getTranslation('de')->label());
    $this->assertEquals('Initial Source Node', $node_de->label());
    $this->assertFalse($node_de->get('composite_reference')->entity->isDefaultRevision());
    $this->assertFalse($node_de->get('composite_reference')->entity->hasTranslation('fr'));
    $this->assertEquals('Pending Revision Composite #1 DE', $node_de->get('composite_reference')->entity->getTranslation('de')->label());
    $this->assertEquals('Pending Nested Composite #1 DE', $node_de->get('composite_reference')->entity->get('composite_reference')->entity->getTranslation('de')->label());
    $this->assertEquals('Initial untranslatable field', $node_de->get('composite_reference')->entity->getTranslation('de')->get('field_untranslatable')->value);
    $this->assertEquals('Initial Source Composite', $node_de->get('composite_reference')->entity->label());

    // Reload the default revision of the node, make sure that the composite
    // there is unchanged.
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->hasTranslation('de'));
    $this->assertEquals('Initial Source Node', $node->label());
    $this->assertTrue($node->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertEquals('Initial Source Composite', $node->get('composite_reference')->entity->label());

    // Create another pending EN revision and make that the default.
    $node = $node_storage->loadRevision($pending_en_revision_id);
    $new_revision = $node_storage->createRevision($node);
    $new_revision->get('composite_reference')->entity->set('name', 'Updated Source Composite');
    $new_revision->get('composite_reference')->entity->set('field_untranslatable', 'Updated untranslatable field');
    $new_revision->setTitle('Updated Source Node');
    $new_revision->get('composite_reference')->entity->get('composite_reference')[1]->entity->set('name', 'Draft Nested Source Composite #2');
    $violations = $new_revision->validate();
    $this->assertEquals(0, count($violations));
    $new_revision->save();

    // Assert the two english revisions.
    // Reload the default revision of the node, make sure that the composite
    // there is unchanged.
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->isDefaultRevision());
    $this->assertTrue($node->hasTranslation('de'));
    $this->assertFalse($node->hasTranslation('fr'));
    $this->assertTrue((bool) $node->isRevisionTranslationAffected());
    $this->assertEquals('Updated Source Node', $node->label());
    $this->assertTrue($node->get('composite_reference')->entity->isDefaultRevision());
    $this->assertTrue($node->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertFalse($node->get('composite_reference')->entity->hasTranslation('fr'));
    $this->assertEquals('Updated Source Composite', $node->get('composite_reference')->entity->label());
    $this->assertEquals('Initial Nested Source Composite', $node->get('composite_reference')->entity->get('composite_reference')->entity->label());
    $this->assertEquals('Draft Nested Source Composite #2', $node->get('composite_reference')->entity->get('composite_reference')[1]->entity->label());
    $this->assertEquals('Updated untranslatable field', $node->get('composite_reference')->entity->get('field_untranslatable')->value);

    $node_initial = $node_storage->loadRevision($initial_revision_id);
    $this->assertFalse($node_initial->isDefaultRevision());
    $this->assertFalse($node_initial->hasTranslation('de'));
    $this->assertFalse($node_initial->hasTranslation('fr'));
    $this->assertEquals('Initial Source Node', $node_initial->label());
    $this->assertFalse($node_initial->get('composite_reference')->entity->isDefaultRevision());
    $this->assertFalse($node_initial->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertEquals('Initial Source Composite', $node_initial->get('composite_reference')->entity->label());
    $this->assertEquals('Initial Nested Source Composite', $node_initial->get('composite_reference')->entity->get('composite_reference')->entity->label());
    $this->assertEquals('Initial untranslatable field', $node_initial->get('composite_reference')->entity->get('field_untranslatable')->value);
    $this->assertCount(1, $node_initial->get('composite_reference')->entity->get('composite_reference'));

    // The current node_fr pending revision still has the initial value before
    // "merging" it, but it will get the new value for the untranslatable field
    // in the new revision.
    $node_fr = $node_storage->loadRevision($node_fr->getRevisionId());
    $this->assertEquals('Initial untranslatable field', $node_fr->get('composite_reference')->entity->get('field_untranslatable')->value);
    $this->assertCount(1, $node_fr->get('composite_reference')->entity->get('composite_reference'));

    // Now publish the FR pending revision and also add a translation for
    // the second composite that it now has.
    $new_revision = $node_storage->createRevision($node_fr->getTranslation('fr'));
    $this->assertCount(2, $new_revision->get('composite_reference')->entity->get('composite_reference'));
    $new_revision->get('composite_reference')->entity->get('composite_reference')[1]->entity->getTranslation('fr')->set('name', 'FR Nested Composite #2');

    $violations = $new_revision->validate();
    $this->assertEquals(0, count($violations));
    $new_revision->save();

    $this->assertRevisionCount(7, $node);
    $this->assertRevisionCount(7, $composite);
    $this->assertRevisionCount(7, $nested_composite);
    $this->assertRevisionCount(3, $second_nested_composite);

    // The new default revision should now have the updated english source,
    // original german translation and the french pending revision.
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->isDefaultRevision());
    $this->assertTrue($node->hasTranslation('de'));
    $this->assertTrue($node->hasTranslation('fr'));
    $this->assertFalse((bool) $node->isRevisionTranslationAffected());
    $this->assertTrue((bool) $node->getTranslation('fr')->isRevisionTranslationAffected());
    $this->assertEquals('Updated Source Node', $node->label());
    $this->assertTrue($node->get('composite_reference')->entity->isDefaultRevision());
    $this->assertTrue($node->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertTrue($node->get('composite_reference')->entity->hasTranslation('fr'));
    $this->assertEquals('Pending Revision Node #1 FR', $node->getTranslation('fr')->label());
    $this->assertEquals('Pending Revision Composite #1 FR', $node->get('composite_reference')->entity->getTranslation('fr')->label());
    $this->assertEquals('Pending Nested Composite #1 FR', $node->get('composite_reference')->entity->get('composite_reference')->entity->getTranslation('fr')->label());
    $this->assertEquals('New Node #1 DE', $node->getTranslation('de')->label());
    $this->assertEquals('New Composite #1 DE', $node->get('composite_reference')->entity->getTranslation('de')->label());
    $this->assertEquals('New Nested Composite #1 DE', $node->get('composite_reference')->entity->get('composite_reference')->entity->getTranslation('de')->label());
    $this->assertEquals('Updated Source Composite', $node->get('composite_reference')->entity->label());
    $this->assertEquals('Updated untranslatable field', $node->get('composite_reference')->entity->get('field_untranslatable')->value);
    $this->assertEquals('Draft Nested Source Composite #2', $node->get('composite_reference')->entity->get('composite_reference')[1]->entity->label());
    $this->assertEquals('FR Nested Composite #2', $node->get('composite_reference')->entity->get('composite_reference')[1]->entity->getTranslation('fr')->label());

    // Now publish the DE pending revision as well.
    $new_revision = $node_storage->createRevision($node_de->getTranslation('de'));
    $violations = $new_revision->validate();
    $this->assertCount(2, $new_revision->get('composite_reference')->entity->get('composite_reference'));
    $this->assertEquals(0, count($violations));
    $new_revision->save();

    $this->assertRevisionCount(8, $node);
    $this->assertRevisionCount(8, $composite);
    $this->assertRevisionCount(8, $nested_composite);
    $this->assertRevisionCount(4, $second_nested_composite);

    // The new default revision should now have the updated source and both
    // translations.
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->isDefaultRevision());
    $this->assertTrue($node->hasTranslation('de'));
    $this->assertTrue($node->hasTranslation('fr'));
    $this->assertFalse((bool) $node->isRevisionTranslationAffected());
    $this->assertFalse((bool) $node->getTranslation('fr')->isRevisionTranslationAffected());
    $this->assertTrue((bool) $node->getTranslation('de')->isRevisionTranslationAffected());
    $this->assertEquals('Updated Source Node', $node->label());
    $this->assertTrue($node->get('composite_reference')->entity->isDefaultRevision());
    $this->assertTrue($node->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertTrue($node->get('composite_reference')->entity->hasTranslation('fr'));
    $this->assertEquals('Pending Revision Node #1 FR', $node->getTranslation('fr')->label());
    $this->assertEquals('Pending Revision Composite #1 FR', $node->get('composite_reference')->entity->getTranslation('fr')->label());
    $this->assertEquals('Pending Revision Node #1 DE', $node->getTranslation('de')->label());
    $this->assertEquals('Pending Revision Composite #1 DE', $node->get('composite_reference')->entity->getTranslation('de')->label());
    $this->assertEquals('Pending Nested Composite #1 DE', $node->get('composite_reference')->entity->get('composite_reference')->entity->getTranslation('de')->label());
    $this->assertEquals('Updated Source Composite', $node->get('composite_reference')->entity->label());
    $this->assertEquals('Updated untranslatable field', $node->get('composite_reference')->entity->get('field_untranslatable')->value);
    $this->assertEquals('Draft Nested Source Composite #2', $node->get('composite_reference')->entity->get('composite_reference')[1]->entity->label());
    $this->assertEquals('FR Nested Composite #2', $node->get('composite_reference')->entity->get('composite_reference')[1]->entity->getTranslation('fr')->label());

    // The second nested composite of DE inherited the default values for its
    // translation.
    $this->assertEquals('Draft Nested Source Composite #2', $node->get('composite_reference')->entity->get('composite_reference')[1]->entity->getTranslation('de')->label());

    // Simulate creating a new pending revision like
    // \Drupal\content_moderation\EntityTypeInfo::entityPrepareForm().
    $new_revision = $node_storage->createRevision($node);
    $revision_key = $new_revision->getEntityType()->getKey('revision');
    $new_revision->set($revision_key, $new_revision->getLoadedRevisionId());
    $new_revision->save();
    $this->assertEquals('Pending Nested Composite #1 DE', $new_revision->get('composite_reference')->entity->get('composite_reference')->entity->getTranslation('de')->label());

  }

  /**
   * Asserts the revision count of an entity.
   *
   * @param int $expected
   *   The expected amount of revisions.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  protected function assertRevisionCount($expected, EntityInterface $entity) {
    $node_revisions_count = \Drupal::entityQuery($entity->getEntityTypeId())
      ->condition($entity->getEntityType()->getKey('id'), $entity->id())
      ->allRevisions()
      ->count()
      ->execute();
    $this->assertEquals($expected, $node_revisions_count);
  }

}
