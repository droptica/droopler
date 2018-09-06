<?php

namespace Drupal\Tests\paragraphs\Unit\migrate;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\paragraphs\Plugin\migrate\process\FieldCollectionFieldInstanceSettings;

/**
 * Test the ParagraphFieldInstanceSettings Process Plugin.
 *
 * @group paragraphs
 * @coversDefaultClass \Drupal\paragraphs\Plugin\migrate\process\FieldCollectionFieldInstanceSettings
 */
class FieldCollectionsFieldInstanceSettingsTest extends ProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->plugin = new FieldCollectionFieldInstanceSettings([], 'field_collection_field_instance_settings', [], $this->entityTypeBundleInfo);

  }

  /**
   * Test settings for field_collection field instances.
   *
   * @param array $source
   *   The data source.
   * @param array $expected
   *   The expected result.
   *
   * @dataProvider getData
   */
  public function testFieldCollectionInstanceFieldSettings(array $source, array $expected) {

    $this->row->expects($this->any())
      ->method('getSourceProperty')
      ->willReturnMap([
        ['type', 'field_collection'],
        ['field_name', 'field_field_collection_bundle_one'],
      ]);
    $value = $this->plugin->transform($source, $this->migrateExecutable, $this->row, 'settings');

    $this->assertArrayEquals($expected, $value);
  }

  /**
   * Test that unexpected bundles trigger an exception.
   */
  public function testFieldCollectionBadBundle() {
    $this->row->expects($this->any())
      ->method('getSourceProperty')
      ->willReturnMap([
        ['type', 'field_collection'],
        ['bundle', 'non_existent_bundle'],
      ]);
    $this->setExpectedException(MigrateSkipRowException::class, 'No target paragraph bundle found for field_collection');
    $this->plugin->transform([], $this->migrateExecutable, $this->row, 'settings');
  }

  /**
   * Data provider for unit test.
   *
   * @return array
   *   The source data and expected data.
   */
  public function getData() {
    $data = [
      'With no data' => [
        'source_data' => [],
        'expected_results' => [
          'handler_settings' => [
            'negate' => 0,
            'target_bundles' => [
              'field_collection_bundle_one' => 'field_collection_bundle_one',
            ],
            'target_bundles_drag_drop' => [
              'field_collection_bundle_one' => [
                'enabled' => TRUE,
                'weight' => 1,
              ],
              'paragraph_bundle_one' => [
                'enabled' => FALSE,
                'weight' => 2,
              ],
              'paragraph_bundle_two' => [
                'enabled' => FALSE,
                'weight' => 3,
              ],
              'field_collection_bundle_two' => [
                'enabled' => FALSE,
                'weight' => 4,
              ],
              'prexisting_bundle_one' => [
                'enabled' => FALSE,
                'weight' => 5,
              ],
              'prexisting_bundle_two' => [
                'enabled' => FALSE,
                'weight' => 6,
              ],
            ],
          ],
        ],
      ],
    ];
    return $data;
  }

}
