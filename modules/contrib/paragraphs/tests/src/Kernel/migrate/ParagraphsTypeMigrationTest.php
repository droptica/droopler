<?php

namespace Drupal\Tests\paragraphs\Kernel\migrate;

/**
 * Test Migration of paragraph and field collection bundles.
 *
 * @group paragraphs
 */
class ParagraphsTypeMigrationTest extends ParagraphsMigrationTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'migrate',
    'paragraphs',
  ];

  /**
   * Test if the paragraph/fc types were brought over as a paragraph.
   */
  public function testParagraphsTypeMigration() {
    $this->executeMigration('d7_field_collection_type');
    $this->executeMigration('d7_paragraphs_type');

    $this->assertParagraphBundleExists('field_collection_test', 'Field collection test');
    $this->assertParagraphBundleExists('paragraph_bundle_one', 'Paragraph Bundle One');
    $this->assertParagraphBundleExists('paragraph_bundle_two', 'Paragraph Bundle Two');
  }

}
