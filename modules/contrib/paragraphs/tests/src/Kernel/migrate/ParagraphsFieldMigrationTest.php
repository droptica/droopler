<?php

namespace Drupal\Tests\paragraphs\Kernel\migrate;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\field\Entity\FieldConfig;
use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * Test the migration of paragraphs and field collection fields.
 *
 * @group paragraphs
 */
class ParagraphsFieldMigrationTest extends ParagraphsMigrationTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'datetime',
    'datetime_range',
    'entity_reference_revisions',
    'field',
    'file',
    'image',
    'link',
    'menu_ui',
    'migrate_drupal',
    'node',
    'options',
    'paragraphs',
    'system',
    'taxonomy',
    'telephone',
    'text',
    'user',
  ];

  /**
   * Test that the paragraph and field collection field storage was migrated.
   */
  public function testParagraphsFieldMigration() {
    $this->executeMigration('d7_field');

    $this->assertParagraphEntityFieldExists('field_email', 'email');
    $this->assertParagraphEntityFieldExists('field_text', 'string');
    $this->assertParagraphEntityFieldExists('field_text_list', 'list_string');
    $this->assertParagraphEntityFieldExists('field_integer_list', 'list_integer');

    $this->assertParagraphFieldExists('node', 'field_any_paragraph');
    $this->assertParagraphFieldExists('node', 'field_paragraph_one_only');
    $this->assertParagraphFieldExists('node', 'field_field_collection_test');
  }

  /**
   * Test if the paragraph field instances were migrated.
   */
  public function testParagrahsFieldInstanceMigration() {

    $this->executeMigrationWithDependencies('d7_field_instance');

    $total_bundles = count(ParagraphsType::loadMultiple());

    $bundles_from_field_collections = count($this->getMigration('d7_field_collection_type')->getSourcePlugin());

    $this->assertFieldInstanceExists('node', 'paragraphs_test', 'field_field_collection_test');
    $field = FieldConfig::loadByName('node', 'paragraphs_test', 'field_field_collection_test');

    $handler_settings = $field->getSetting('handler_settings');
    $this->assertEquals(0, $handler_settings['negate']);
    $this->assertCount(1, $handler_settings['target_bundles']);
    $this->assertEquals('field_collection_test', $handler_settings['target_bundles']['field_collection_test']);
    $this->assertCount($total_bundles, $handler_settings['target_bundles_drag_drop']);

    $this->assertFieldInstanceExists('node', 'paragraphs_test', 'field_any_paragraph');
    $field = FieldConfig::loadByName('node', 'paragraphs_test', 'field_any_paragraph');
    $handler_settings = $field->getSetting('handler_settings');
    $this->assertEquals(0, $handler_settings['negate']);
    $this->assertNULL($handler_settings['target_bundles']);
    $this->assertCount($total_bundles, $handler_settings['target_bundles_drag_drop']);

    $this->assertFieldInstanceExists('node', 'paragraphs_test', 'field_paragraph_one_only');
    $field = FieldConfig::loadByName('node', 'paragraphs_test', 'field_paragraph_one_only');
    $handler_settings = $field->getSetting('handler_settings');
    $this->assertEquals(0, $handler_settings['negate']);
    $this->assertCount(1, $handler_settings['target_bundles']);
    $this->assertEquals('paragraph_bundle_one', $handler_settings['target_bundles']['paragraph_bundle_one']);
    $this->assertCount($total_bundles, $handler_settings['target_bundles_drag_drop']);

    $this->assertFieldInstanceExists('paragraph', 'paragraph_bundle_one', 'field_text', 'string');
    $this->assertFieldInstanceExists('paragraph', 'paragraph_bundle_one', 'field_text_list', 'list_string');
    $this->assertFieldInstanceExists('paragraph', 'paragraph_bundle_two', 'field_text', 'string');
    $this->assertFieldInstanceExists('paragraph', 'paragraph_bundle_two', 'field_email', 'email');
    $this->assertFieldInstanceExists('paragraph', 'field_collection_test', 'field_text', 'string');
    $this->assertFieldInstanceExists('paragraph', 'field_collection_test', 'field_integer_list', 'list_integer');

  }

  /**
   * Test Paragraph widget Migration.
   */
  public function testParagraphsWidgets() {

    $this->executeMigrationWithDependencies('d7_field_instance_widget_settings');

    $formDisplay = EntityFormDisplay::load('node.paragraphs_test.default');
    $this->assertNotNull($formDisplay);
    $field_any_paragraph = $formDisplay->getComponent('field_any_paragraph');
    $field_collection_test = $formDisplay->getComponent('field_field_collection_test');
    $field_paragraph_one_only = $formDisplay->getComponent('field_paragraph_one_only');
    $this->assertNotNull($field_any_paragraph);
    $this->assertNotNull($field_collection_test);
    $this->assertNotNull($field_paragraph_one_only);
    $this->assertEquals('button', $field_any_paragraph['settings']['add_mode']);
    $this->assertEquals('Any Paragraph', $field_any_paragraph['settings']['title']);
    $this->assertEquals('Any Paragraphs', $field_any_paragraph['settings']['title_plural']);
    $this->assertEquals('closed', $field_any_paragraph['settings']['edit_mode']);
  }

  /**
   * Test Paragraph Formatter Migration.
   */
  public function testParagraphFormatters() {

    $this->executeMigrationWithDependencies('d7_field_formatter_settings');

    $full = EntityViewMode::load('paragraph.full');
    $this->assertNotNull($full);

    $editor_preview = EntityViewMode::load('paragraph.paragraphs_editor_preview');
    $this->assertNotNull($editor_preview);

    $viewDisplay = EntityViewDisplay::load('node.paragraphs_test.default');
    $this->assertNotNull($viewDisplay);
    $field_any_paragraph = $viewDisplay->getComponent('field_any_paragraph');
    $field_collection_test = $viewDisplay->getComponent('field_field_collection_test');
    $field_paragraph_one_only = $viewDisplay->getComponent('field_paragraph_one_only');
    $this->assertNotNull($field_any_paragraph);
    $this->assertNotNull($field_collection_test);
    $this->assertNull($field_paragraph_one_only);
    $this->assertEquals('paragraphs_editor_preview', $field_any_paragraph['settings']['view_mode']);
  }

}
