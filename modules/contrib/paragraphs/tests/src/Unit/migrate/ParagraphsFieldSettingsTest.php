<?php

namespace Drupal\Tests\paragraphs\Unit\migrate;

use Drupal\paragraphs\Plugin\migrate\process\ParagraphsFieldSettings;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Test the ParagraphFieldSettings Process Plugin.
 *
 * @group paragraphs
 * @coversDefaultClass \Drupal\paragraphs\Plugin\migrate\process\ParagraphsFieldSettings
 */
class ParagraphsFieldSettingsTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->plugin = new ParagraphsFieldSettings([], 'paragraphs_field_settings', []);
    parent::setUp();

  }

  /**
   * Test setting target_type for paragraphs fields.
   */
  public function testParagraphsFieldSettings() {
    $this->row->expects($this->any())
      ->method('getSourceProperty')
      ->with('type')
      ->willReturn('paragraphs');
    $value = $this->plugin->transform([], $this->migrateExecutable, $this->row, 'settings');
    $this->assertArrayEquals(['target_type' => 'paragraph'], $value);
  }

  /**
   * Test leaving target_type empty for non-paragraphs fields.
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
