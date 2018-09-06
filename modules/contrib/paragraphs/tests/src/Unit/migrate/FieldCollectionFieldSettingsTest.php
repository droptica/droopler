<?php

namespace Drupal\Tests\paragraphs\Unit\migrate;

use Drupal\paragraphs\Plugin\migrate\process\FieldCollectionFieldSettings;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Test the FieldCollectionFieldSettings Process Plugin.
 *
 * @group paragraphs
 * @coversDefaultClass \Drupal\paragraphs\Plugin\migrate\process\FieldCollectionFieldSettings
 */
class FieldCollectionFieldSettingsTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->plugin = new FieldCollectionFieldSettings([], 'field_collection_field_settings', []);
    parent::setUp();

  }

  /**
   * Test setting target_type for paragraphs fields.
   */
  public function testParagraphsFieldSettings() {
    $this->row->expects($this->any())
      ->method('getSourceProperty')
      ->with('type')
      ->willReturn('field_collection');
    $value = $this->plugin->transform([], $this->migrateExecutable, $this->row, 'settings');
    $this->assertArrayEquals(['target_type' => 'paragraph'], $value);
  }

  /**
   * Test leaving target_type empty for non-field_collection fields.
   */
  public function testNonParagraphFieldSettings() {
    $this->row->expects($this->any())
      ->method('getSourceProperty')
      ->with('type')
      ->willReturn('something_else');
    $value = $this->plugin->transform([], $this->migrateExecutable, $this->row, 'settings');
    $this->assertEmpty($value);
  }

  /**
   * Test leaving target_type alone for other field types that may have set it.
   */
  public function testTaxonomyParagraphFieldSettings() {
    $this->row->expects($this->any())
      ->method('getSourceProperty')
      ->with('type')
      ->willReturn('taxonomy_term');
    $value = $this->plugin->transform(['target_type' => 'some_preset_vaue'], $this->migrateExecutable, $this->row, 'settings');
    $this->assertArrayEquals(['target_type' => 'some_preset_vaue'], $value);
  }

}
