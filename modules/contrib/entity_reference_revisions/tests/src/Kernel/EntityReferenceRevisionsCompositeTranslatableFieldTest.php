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
 * Tests entity_reference_revisions composites with a translatable field.
 *
 * @group entity_reference_revisions
 */
class EntityReferenceRevisionsCompositeTranslatableFieldTest extends EntityKernelTestBase {

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
    'language',
    'content_translation'
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

    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();

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
      'translatable' => TRUE,
    ));
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

    // Create the test composite entity.
    $composite = EntityTestCompositeRelationship::create([
      'langcode' => 'en',
      'name' => 'Initial Source Composite',
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

    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($node->id());

    // Assert the revision count.
    $this->assertRevisionCount(1, 'node', $node->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite->id());

    // Create a translation as a pending revision for both the composite and the
    // node. While technically, the referenced composite could be the same
    // entity, for translatable fields, it makes more sense if each translation
    // points to a separate entity, each only with a single language.
    $composite_de = $node->get('composite_reference')->entity->createDuplicate();
    $composite_de->set('langcode', 'de');
    $composite_de->set('name', 'Pending Revision Composite #1 DE');
    /** @var \Drupal\node\NodeInterface $node_de */
    $node_de = $node->addTranslation('de', ['title' => 'Pending Revision Node #1 DE', 'composite_reference' => $composite_de] + $node->toArray());
    $node_de->setNewRevision(TRUE);
    $node_de->isDefaultRevision(FALSE);
    $node_de->save();

    // Assert the revision count.
    $this->assertRevisionCount(2, 'node', $node->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite_de->id());

    // The DE translation will now reference to a pending revision of the
    // composite entity but the en translation will reference the existing,
    // unchanged revision.
    /** @var \Drupal\node\NodeInterface $node_revision */
    $node_revision = $node_storage->loadRevision($node_de->getRevisionId());
    $this->assertFalse($node_revision->isDefaultRevision());
    $this->assertFalse((bool) $node_revision->isRevisionTranslationAffected());
    $this->assertEquals('Initial Source Node', $node_revision->label());
    $this->assertTrue($node_revision->get('composite_reference')->entity->isDefaultRevision());
    $this->assertEquals('Initial Source Composite', $node_revision->get('composite_reference')->entity->label());
    $this->assertFalse($node_revision->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertEquals($node->get('composite_reference')->target_revision_id, $node_revision->get('composite_reference')->target_revision_id);

    $node_de = $node_revision->getTranslation('de');
    $this->assertTrue((bool) $node_de->isRevisionTranslationAffected());
    $this->assertEquals('Pending Revision Node #1 DE', $node_de->label());
    // The composite is the default revision because it is a new entity.
    $this->assertTrue($node_de->get('composite_reference')->entity->isDefaultRevision());
    $this->assertEquals('Pending Revision Composite #1 DE', $node_de->get('composite_reference')->entity->label());
    $this->assertNotEquals($node->get('composite_reference')->target_revision_id, $node_de->get('composite_reference')->target_revision_id);

    // Reload the default revision of the node, make sure that the composite
    // there is unchanged.
    $node = $node_storage->load($node->id());
    $this->assertFalse($node->hasTranslation('de'));
    $this->assertEquals('Initial Source Node', $node->label());
    $this->assertFalse($node->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertEquals('Initial Source Composite', $node->get('composite_reference')->entity->label());

    // Create a second translation revision for FR.
    $composite_fr = $node->get('composite_reference')->entity->createDuplicate();
    $composite_fr->set('langcode', 'fr');
    $composite_fr->set('name', 'Pending Revision Composite #1 FR');
    $node_fr = $node->addTranslation('fr', ['title' => 'Pending Revision Node #1 FR', 'composite_reference' => $composite_fr] + $node->toArray());
    $node_fr->setNewRevision(TRUE);
    $node_fr->isDefaultRevision(FALSE);
    $node_fr->save();

    // Assert the revision count.
    $this->assertRevisionCount(3, 'node', $node->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite_de->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite_fr->id());

    // Now assert that all 3 revisions exist as expected. Two translation
    // pending revisions, each has the original revision as parent without
    // any existing translation.
    /** @var \Drupal\node\NodeInterface $node_fr */
    $node_revision = $node_storage->loadRevision($node_fr->getRevisionId());
    $this->assertFalse($node_revision->isDefaultRevision());
    $this->assertFalse((bool) $node_revision->isRevisionTranslationAffected());
    $this->assertEquals('Initial Source Node', $node_revision->label());
    $this->assertTrue($node_revision->get('composite_reference')->entity->isDefaultRevision());
    $this->assertEquals('Initial Source Composite', $node_revision->get('composite_reference')->entity->label());
    $this->assertFalse($node_revision->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertEquals($node->get('composite_reference')->target_revision_id, $node_revision->get('composite_reference')->target_revision_id);

    $node_fr = $node_revision->getTranslation('fr');
    $this->assertTrue((bool) $node_fr->isRevisionTranslationAffected());
    $this->assertEquals('Pending Revision Node #1 FR', $node_fr->label());
    $this->assertTrue($node_fr->get('composite_reference')->entity->isDefaultRevision());
    $this->assertEquals('Pending Revision Composite #1 FR', $node_fr->get('composite_reference')->entity->label());
    $this->assertNotEquals($node->get('composite_reference')->target_revision_id, $node_fr->get('composite_reference')->target_revision_id);

    $node_de = $node_storage->loadRevision($node_de->getRevisionId())->getTranslation('de');
    $this->assertTrue((bool) $node_de->isRevisionTranslationAffected());
    $this->assertEquals('Pending Revision Node #1 DE', $node_de->label());
    $this->assertTrue($node_de->get('composite_reference')->entity->isDefaultRevision());
    $this->assertEquals('Pending Revision Composite #1 DE', $node_de->get('composite_reference')->entity->label());
    $this->assertNotEquals($node->get('composite_reference')->target_revision_id, $node_de->get('composite_reference')->target_revision_id);

    // Reload the default revision of the node, make sure that the composite
    // there is unchanged.
    $node = $node_storage->load($node->id());
    $this->assertFalse($node->hasTranslation('de'));
    $this->assertEquals('Initial Source Node', $node->label());
    $this->assertFalse($node->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertEquals('Initial Source Composite', $node->get('composite_reference')->entity->label());

    // Now make a change to the initial source revision, save as a new default
    // revision.
    $initial_revision_id = $node->getRevisionId();
    $node->get('composite_reference')->entity->set('name', 'Updated Source Composite');
    $node->setTitle('Updated Source Node');
    $node->setNewRevision(TRUE);
    $node->save();

    // Assert the revision count.
    $this->assertRevisionCount(4, 'node', $node->id());
    $this->assertRevisionCount(2, 'entity_test_composite', $composite->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite_de->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite_fr->id());

    // Assert the two english revisions.
    // Reload the default revision of the node, make sure that the composite
    // there is unchanged.
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->isDefaultRevision());
    $this->assertFalse($node->hasTranslation('de'));
    $this->assertFalse($node->hasTranslation('fr'));
    $this->assertTrue((bool) $node->isRevisionTranslationAffected());
    $this->assertEquals('Updated Source Node', $node->label());
    $this->assertTrue($node->get('composite_reference')->entity->isDefaultRevision());
    $this->assertFalse($node->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertEquals('Updated Source Composite', $node->get('composite_reference')->entity->label());

    $node_initial = $node_storage->loadRevision($initial_revision_id);
    $this->assertFalse($node_initial->isDefaultRevision());
    $this->assertFalse($node_initial->hasTranslation('de'));
    $this->assertFalse($node_initial->hasTranslation('fr'));
    $this->assertEquals('Initial Source Node', $node_initial->label());
    $this->assertFalse($node_initial->get('composite_reference')->entity->isDefaultRevision());
    $this->assertFalse($node_initial->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertEquals('Initial Source Composite', $node_initial->get('composite_reference')->entity->label());

    // Now publish the FR pending revision.
    $node_storage->createRevision($node_fr->getTranslation('fr'))->save();

    // Assert the revision count.
    $this->assertRevisionCount(5, 'node', $node->id());
    $this->assertRevisionCount(2, 'entity_test_composite', $composite->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite_de->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite_fr->id());

    // The new default revision should now have the updated english source and
    // the french pending revision.
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->isDefaultRevision());
    $this->assertFalse($node->hasTranslation('de'));
    $this->assertTrue($node->hasTranslation('fr'));
    $node_fr = $node->getTranslation('fr');
    $this->assertFalse((bool) $node->isRevisionTranslationAffected());
    $this->assertTrue((bool) $node->getTranslation('fr')->isRevisionTranslationAffected());
    $this->assertEquals('Updated Source Node', $node->label());
    $this->assertTrue($node->get('composite_reference')->entity->isDefaultRevision());
    $this->assertFalse($node->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertTrue($node_fr->get('composite_reference')->entity->hasTranslation('fr'));
    $this->assertEquals('Pending Revision Node #1 FR', $node_fr->label());
    $this->assertEquals('Pending Revision Composite #1 FR', $node_fr->get('composite_reference')->entity->getTranslation('fr')->label());
    $this->assertEquals('Updated Source Composite', $node->get('composite_reference')->entity->label());

    // Now publish the DE pending revision as well.
    $node_storage->createRevision($node_de->getTranslation('de'))->save();

    // Assert the revision count.
    $this->assertRevisionCount(6, 'node', $node->id());
    $this->assertRevisionCount(2, 'entity_test_composite', $composite->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite_de->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite_fr->id());

    // The new default revision should now have the updated source and both
    // translations.
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->isDefaultRevision());
    $this->assertTrue($node->hasTranslation('de'));
    $this->assertTrue($node->hasTranslation('fr'));
    $node_fr = $node->getTranslation('fr');
    $node_de = $node->getTranslation('de');
    $this->assertFalse((bool) $node->isRevisionTranslationAffected());
    $this->assertFalse((bool) $node->getTranslation('fr')->isRevisionTranslationAffected());
    $this->assertTrue((bool) $node->getTranslation('de')->isRevisionTranslationAffected());
    $this->assertEquals('Updated Source Node', $node->label());

    // Each translation only has the composite in its translation.
    $this->assertTrue($node->get('composite_reference')->entity->hasTranslation('en'));
    $this->assertFalse($node->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertFalse($node->get('composite_reference')->entity->hasTranslation('fr'));
    $this->assertFalse($node_fr->get('composite_reference')->entity->hasTranslation('en'));
    $this->assertTrue($node_fr->get('composite_reference')->entity->hasTranslation('fr'));
    $this->assertFalse($node_fr->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertFalse($node_de->get('composite_reference')->entity->hasTranslation('en'));
    $this->assertTrue($node_de->get('composite_reference')->entity->hasTranslation('de'));
    $this->assertFalse($node_de->get('composite_reference')->entity->hasTranslation('fr'));

    $this->assertEquals('Pending Revision Node #1 FR', $node_fr->label());
    $this->assertEquals('Pending Revision Composite #1 FR', $node_fr->get('composite_reference')->entity->getTranslation('fr')->label());
    $this->assertEquals('Pending Revision Node #1 DE', $node_de->label());
    $this->assertEquals('Pending Revision Composite #1 DE', $node_de->get('composite_reference')->entity->getTranslation('de')->label());
    $this->assertEquals('Updated Source Composite', $node->get('composite_reference')->entity->label());
  }

  /**
   * Asserts the revision count of a certain entity.
   *
   * @param int $expected
   *   The expected count.
   * @param string $entity_type_id
   *   The entity type ID, e.g. node.
   * @param int $entity_id
   *   The entity ID.
   */
  protected function assertRevisionCount($expected, $entity_type_id, $entity_id) {
    $id_field = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getKey('id');

    $revision_count = \Drupal::entityQuery($entity_type_id)
      ->condition($id_field, $entity_id)
      ->allRevisions()
      ->count()
      ->execute();
    $this->assertEquals($expected, $revision_count);
  }

}
