<?php

namespace Drupal\Tests\paragraphs\Kernel;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Site\Settings;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the replication functionality provided by Replicate module.
 *
 * @group paragraphs
 */
class ParagraphsReplicateTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'paragraphs',
    'replicate',
    'node',
    'user',
    'system',
    'field',
    'entity_reference_revisions',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create paragraphs and article content types.
    $values = ['type' => 'article', 'name' => 'Article'];
    $node_type = NodeType::create($values);
    $node_type->save();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');
  }

  /**
   * Tests the replication of the parent entity.
   */
  public function testReplication() {
    // Create the paragraph type.
    $paragraph_type = ParagraphsType::create([
      'label' => 'test_text',
      'id' => 'test_text',
    ]);
    $paragraph_type->save();

    $paragraph_type_nested = ParagraphsType::create([
      'label' => 'test_nested',
      'id' => 'test_nested',
    ]);
    $paragraph_type_nested->save();

    // Add a title field to both paragraph bundles.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'title',
      'entity_type' => 'paragraph',
      'type' => 'string',
      'cardinality' => '1',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_text',
    ]);
    $field->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_nested',
    ]);
    $field->save();

    // Add a paragraph field to the nested paragraph.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'nested_paragraph_field',
      'entity_type' => 'paragraph',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_nested',
    ]);
    $field->save();

    // Add a paragraph field to the article.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'node_paragraph_field',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ]);
    $field->save();

    // Create a paragraph.
    $paragraph = Paragraph::create([
      'title' => 'Simple paragraph',
      'type' => 'test_text',
    ]);
    $paragraph->save();

    // Create nested paragraph.
    $paragraph_nested = Paragraph::create([
      'title' => 'Nested paragraph',
      'type' => 'test_text',
    ]);
    $paragraph_nested->save();

    // Create another paragraph.
    $paragraph_nested_parent = Paragraph::create([
      'title' => 'Parent paragraph',
      'type' => 'test_nested',
      'nested_paragraph_field' => [$paragraph_nested],
    ]);
    $paragraph_nested_parent->save();

    // Create a node with two paragraphs.
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'node_paragraph_field' => array($paragraph, $paragraph_nested_parent),
      ]);
    $node->save();

    $replicated_node = $this->container->get('replicate.replicator')
      ->replicateEntity($node);

    // Check that all paragraphs on the replicated node were replicated too.
    $this->assertNotEquals($replicated_node->id(), $node->id(), 'We have two different nodes.');
    $this->assertNotEquals($replicated_node->node_paragraph_field[0]->target_id, $node->node_paragraph_field[0]->target_id, 'Simple paragraph was duplicated.');
    $this->assertEquals('Simple paragraph', $replicated_node->node_paragraph_field[0]->entity->title->value, "Simple paragraph inherited title from it's original.");
    $this->assertNotEquals($replicated_node->node_paragraph_field[1]->target_id, $node->node_paragraph_field[1]->target_id, 'Parent paragraph was duplicated.');
    $this->assertEquals('Parent paragraph', $replicated_node->node_paragraph_field[1]->entity->title->value, "Parent paragraph inherited title from it's original.");
    $this->assertNotEquals($replicated_node->node_paragraph_field[1]->entity->nested_paragraph_field[0]->target_id, $node->node_paragraph_field[1]->entity->nested_paragraph_field[0]->target_id, 'Nested paragraph was duplicated.');
    $this->assertEquals('Nested paragraph', $replicated_node->node_paragraph_field[1]->entity->nested_paragraph_field[0]->entity->title->value, "Nested paragraph inherited title from it's original.");

    // Try to edit replicated paragraphs and check that changes do not propagate.
    /** @var \Drupal\paragraphs\ParagraphInterface $simple_paragraph */
    $simple_paragraph = $replicated_node->node_paragraph_field[0]->entity;
    $simple_paragraph->title->value = 'Simple paragraph - replicated';
    $simple_paragraph->save();
    /** @var \Drupal\paragraphs\ParagraphInterface $parent_paragraph */
    $parent_paragraph = $replicated_node->node_paragraph_field[1]->entity;
    $parent_paragraph->title->value = 'Parent paragraph - replicated';
    $parent_paragraph->save();
    /** @var \Drupal\paragraphs\ParagraphInterface $nested_paragraph */
    $nested_paragraph = $replicated_node->node_paragraph_field[1]->entity->nested_paragraph_field[0]->entity;
    $nested_paragraph->title->value = 'Nested paragraph - replicated';
    $nested_paragraph->save();

    $this->assertEquals('Simple paragraph', $node->node_paragraph_field[0]->entity->title->value, 'Field value on the original simple paragraph are unchanged.');
    $this->assertEquals('Parent paragraph', $node->node_paragraph_field[1]->entity->title->value, 'Field value on the original parent paragraph are unchanged.');
    $this->assertEquals('Nested paragraph', $node->node_paragraph_field[1]->entity->nested_paragraph_field[0]->entity->title->value, 'Field value on the original nested paragraph are unchanged.');

    $this->assertEquals('Simple paragraph - replicated', $replicated_node->node_paragraph_field[0]->entity->title->value, 'Field value on the replicated simple paragraph are updated.');
    $this->assertEquals('Parent paragraph - replicated', $replicated_node->node_paragraph_field[1]->entity->title->value, 'Field value on the replicated parent paragraph are updated.');
    $this->assertEquals('Nested paragraph - replicated', $replicated_node->node_paragraph_field[1]->entity->nested_paragraph_field[0]->entity->title->value, 'Field value on the replicated nested paragraph are updated.');
  }
}
