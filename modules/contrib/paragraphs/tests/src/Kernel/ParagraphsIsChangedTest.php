<?php

namespace Drupal\Tests\paragraphs\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * Tests \Drupal\Paragraphs\Entity\Paragraph::isChanged().
 *
 * @group paragraphs
 */
class ParagraphsIsChangedTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'paragraphs',
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
    $this->installEntitySchema('user');
    $this->installEntitySchema('paragraph');
    $this->installSchema('system', ['sequences']);
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');

    // Create a text paragraph type.
    $paragraph_type = ParagraphsType::create([
      'label' => 'text_paragraph',
      'id' => 'text_paragraph',
    ]);
    $paragraph_type->save();
    $this->addParagraphsField('text_paragraph', 'text', 'string');
  }

  /**
   * Tests the functionality of the isChanged() function.
   */
  public function testIsChanged() {
    // Create a paragraph.
    $paragraph = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'text_paragraph',
      'text' => 'Text Paragraph',
    ]);
    $this->assertTrue($paragraph->isChanged(), 'The paragraph is a new entity.');

    // Save the paragraph and assert no changes.
    $paragraph->save();
    $this->assertFalse($paragraph->isChanged(), 'Paragraph::isChanged() found no changes after the entity has been saved.');

    // Update the revision author field, which should be skipped from checking
    // for changes in Paragraph::isChanged().
    $paragraph->setRevisionAuthorId(3);
    $this->assertFalse($paragraph->isChanged(), 'Paragraph::isChanged() found no changes after updating revision_uid field.');

    $paragraph->set('text', 'New text');
    $this->assertTrue($paragraph->isChanged(), 'Paragraph::isChanged() found changes after updating text field.');
  }

  /**
   * Adds a field to a given paragraph type.
   *
   * @param string $paragraph_type_name
   *   Paragraph type name to be used.
   * @param string $field_name
   *   Paragraphs field name to be used.
   * @param string $field_type
   *   Type of the field.
   * @param array $field_edit
   *   Edit settings for the field.
   */
  protected function addParagraphsField($paragraph_type_name, $field_name, $field_type, array $field_edit = []) {
    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'paragraph',
      'type' => $field_type,
      'cardinality' => '-1',
      'settings' => $field_edit,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $paragraph_type_name,
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();
  }

}
