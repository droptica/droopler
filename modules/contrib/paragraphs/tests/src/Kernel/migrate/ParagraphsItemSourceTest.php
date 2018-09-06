<?php

namespace Drupal\Tests\paragraphs\Kernel\migrate;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use Drupal\Tests\paragraphs\Traits\ParagraphsSourceData;

/**
 * Test the paragraphs_item source plugin.
 *
 * @covers \Drupal\paragraphs\Plugin\migrate\source\d7\ParagraphsItem
 * @group paragraphs
 */
class ParagraphsItemSourceTest extends MigrateSqlSourceTestBase {
  use ParagraphsSourceData;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'paragraphs'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $data = $this->getSourceData();
    $data[0]['expected_results'] = [
      [
        'item_id' => '1',
        'revision_id' => '1',
        'field_name' => 'field_paragraphs_field',
        'bundle' => 'paragraphs_field',
        'archived' => '0',
        'field_text' => [
          0 => [
            'value' => 'PID1R1 text',
          ],
        ],
      ],
      [
        'item_id' => '2',
        'revision_id' => '3',
        'field_name' => 'field_paragraphs_field',
        'bundle' => 'paragraphs_field',
        'archived' => '0',
        'field_text' => [
          0 => [
            'value' => 'PID2R3 text',
          ],
        ],
      ],

    ];
    return $data;
  }

}
