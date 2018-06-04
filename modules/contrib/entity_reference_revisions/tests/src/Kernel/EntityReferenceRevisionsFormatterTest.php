<?php

namespace Drupal\Tests\entity_reference_revisions\Kernel;

use Drupal\entity_composite_relationship_test\Entity\EntityTestCompositeRelationship;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\UserCreationTrait;

/**
 * @coversDefaultClass \Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter
 * @group entity_reference_revisions
 */
class EntityReferenceRevisionsFormatterTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'user',
    'system',
    'field',
    'entity_reference_revisions',
    'entity_composite_relationship_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create article content type.
    $values = ['type' => 'article', 'name' => 'Article'];
    $node_type = NodeType::create($values);
    $node_type->save();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('entity_test_composite');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);

    // Add the entity_reference_revisions field to article.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'composite_reference',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'settings' => [
        'target_type' => 'entity_test_composite'
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ]);
    $field->save();

    $user = $this->createUser(['administer entity_test composite relationship']);
    \Drupal::currentUser()->setAccount($user);
  }

  public function testFormatterWithDeletedReference() {
    // Create the test composite entity.
    $text = 'Dummy text';
    $entity_test = EntityTestCompositeRelationship::create([
      'uuid' => $text,
      'name' => $text,
    ]);
    $entity_test->save();

    $text = 'Clever text';
    // Set the name to a new text.
    /** @var \Drupal\entity_composite_relationship_test\Entity\EntityTestCompositeRelationship $entity_test */
    $entity_test->name = $text;
    $entity_test->setNeedsSave(TRUE);

    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'composite_reference' => $entity_test,
    ]);
    $node->save();

    // entity_reference_revisions_entity_view
    $result = $node->composite_reference->view(['type' => 'entity_reference_revisions_entity_view']);
    $this->setRawContent($this->render($result));
    $this->assertText('Clever text');

    // Remove the referenced entity.
    $entity_test->delete();

    $node = Node::load($node->id());
    $result = $node->composite_reference->view(['type' => 'entity_reference_revisions_entity_view']);
    $this->render($result);
    $this->assertNoText('Clever text');
  }

}
