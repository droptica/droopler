<?php

namespace Drupal\Tests\config_update\Unit;

use Drupal\config_update\ConfigDiffer;

/**
 * Tests the \Drupal\config_update\ConfigDiffer class.
 *
 * @group config_update
 *
 * @coversDefaultClass \Drupal\config_update\ConfigDiffer
 */
class ConfigDifferTest extends ConfigUpdateUnitTestBase {

  /**
   * The config differ to test.
   *
   * @var \Drupal\config_update\ConfigDiffer
   */
  protected $configDiffer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->configDiffer = new ConfigDiffer($this->getTranslationMock());
  }

  /**
   * @covers \Drupal\config_update\ConfigDiffer::same
   * @dataProvider sameProvider
   */
  public function testSame($a, $b, $expected) {
    $this->assertEquals($expected, $this->configDiffer->same($a, $b));
  }

  /**
   * Data provider for self:testSame().
   */
  public function sameProvider() {
    $base = [
      'uuid' => 'bar',
      'a' => 'a',
      'b' => 0,
      'c' => [
        'd' => TRUE,
        'e' => FALSE,
        'empty' => [],
      ],
    ];
    return [
      [$base, $base, TRUE],

      // Add _core, omit uuid at top level. Should match, as both are removed
      // in normalization process.
      [
        $base,
        [
          '_core' => 'foo',
          'a' => 'a',
          'b' => 0,
          'c' => [
            'd' => TRUE,
            'e' => FALSE,
            'empty' => [],
          ],
        ],
        TRUE,
      ],

      // Change order in top and deep level. Should match.
      [
        $base,
        [
          'uuid' => 'bar',
          'b' => 0,
          'a' => 'a',
          'c' => [
            'e' => FALSE,
            'empty' => [],
            'd' => TRUE,
          ],
        ],
        TRUE,
      ],

      // Add _core in deeper level. Should not match, as this is removed
      // only at the top level during normalization.
      [
        $base,
        [
          'uuid' => 'bar',
          'a' => 'a',
          'b' => 0,
          'c' => [
            '_core' => 'do-not-use-this-key',
            'd' => TRUE,
            'e' => FALSE,
            'empty' => [],
          ],
        ],
        FALSE,
      ],

      // Add uuid in deeper level. Should not match, as this is removed
      // only at the top level during normalization.
      [
        $base,
        [
          'uuid' => 'bar',
          'a' => 'a',
          'b' => 0,
          'c' => [
            'd' => TRUE,
            'e' => FALSE,
            'uuid' => 'important',
            'empty' => [],
          ],
        ],
        FALSE,
      ],

      // Omit a component. Should not match.
      [
        $base,
        [
          'uuid' => 'bar',
          'a' => 'a',
          'c' => [
            'd' => TRUE,
            'e' => FALSE,
            'empty' => [],
          ],
        ],
        FALSE,
      ],

      // Add a component. Should not match.
      [
        $base,
        [
          'uuid' => 'bar',
          'a' => 'a',
          'b' => 0,
          'c' => [
            'd' => TRUE,
            'e' => FALSE,
            'empty' => [],
          ],
          'f' => 'f',
        ],
        FALSE,
      ],

      // 0 should not match a string.
      [
        $base,
        [
          '_core' => 'foo',
          'uuid' => 'bar',
          'a' => 'a',
          'b' => 'b',
          'c' => [
            'd' => TRUE,
            'e' => FALSE,
            'empty' => [],
          ],
        ],
        FALSE,
      ],

      // 0 should not match NULL.
      [
        $base,
        [
          '_core' => 'foo',
          'uuid' => 'bar',
          'a' => 'a',
          'b' => NULL,
          'c' => [
            'd' => TRUE,
            'e' => FALSE,
            'empty' => [],
          ],
        ],
        FALSE,
      ],

      // FALSE should not match a string.
      [
        $base,
        [
          '_core' => 'foo',
          'uuid' => 'bar',
          'a' => 'a',
          'b' => 0,
          'c' => [
            'd' => TRUE,
            'e' => 'e',
            'empty' => [],
          ],
        ],
        FALSE,
      ],

      // TRUE should not match a string.
      [
        $base,
        [
          '_core' => 'foo',
          'uuid' => 'bar',
          'a' => 'a',
          'b' => 0,
          'c' => [
            'd' => 'd',
            'e' => FALSE,
            'empty' => [],
          ],
        ],
        FALSE,
      ],

      // Add an empty array at top, and remove at lower level. Should still
      // match.
      [
        $base,
        [
          '_core' => 'foo',
          'uuid' => 'bar',
          'a' => 'a',
          'b' => 0,
          'c' => [
            'd' => TRUE,
            'e' => FALSE,
          ],
          'empty_two' => [],
        ],
        TRUE,
      ],
    ];
  }

  /**
   * @covers \Drupal\config_update\ConfigDiffer::diff
   */
  public function testDiff() {
    $configOne = [
      'uuid' => '1234-5678-90',
      'id' => 'test.config.id',
      'id_to_remove' => 'test.remove.id',
      'type' => 'old_type',
      'true_value' => TRUE,
      'null_value' => NULL,
      'nested_array' => [
        'flat_array' => [
          'value2',
          'value1',
          'value3',
        ],
        'custom_key' => 'value',
      ],
    ];

    $configTwo = [
      'uuid' => '09-8765-4321',
      'id' => 'test.config.id',
      'type' => 'new_type',
      'true_value' => FALSE,
      'null_value' => FALSE,
      'nested_array' => [
        'flat_array' => [
          'value2',
          'value3',
        ],
        'custom_key' => 'value',
        'custom_key_2' => 'value2',
      ],
    ];

    $edits = $this->configDiffer->diff($configOne, $configTwo)->getEdits();

    $expectedEdits = [
      [
        'copy' => [
          'orig' => [
            'id : test.config.id',
          ],
          'closing' => [
            'id : test.config.id',
          ],
        ],
      ],
      [
        'delete' => [
          'orig' => [
            'id_to_remove : test.remove.id',
          ],
          'closing' => FALSE,
        ],
      ],
      [
        'copy' => [
          'orig' => [
            'nested_array',
            'nested_array::custom_key : value',
          ],
          'closing' => [
            'nested_array',
            'nested_array::custom_key : value',
          ],
        ],
      ],
      [
        'add' => [
          'orig' => FALSE,
          'closing' => [
            'nested_array::custom_key_2 : value2',
          ],
        ],
      ],
      [
        'copy' => [
          'orig' => [
            'nested_array::flat_array',
            'nested_array::flat_array::0 : value2',
          ],
          'closing' => [
            'nested_array::flat_array',
            'nested_array::flat_array::0 : value2',
          ],
        ],
      ],
      [
        'change' => [
          'orig' => [
            'nested_array::flat_array::1 : value1',
            'nested_array::flat_array::2 : value3',
            'null_value : null',
            'true_value : true',
            'type : old_type',
          ],
          'closing' => [
            'nested_array::flat_array::1 : value3',
            'null_value : false',
            'true_value : false',
            'type : new_type',
          ],
        ],
      ],
    ];

    $this->assertEquals(count($expectedEdits), count($edits));

    /** @var \Drupal\Component\Diff\Engine\DiffOp $diffOp */
    foreach ($edits as $index => $diffOp) {
      $this->assertEquals($expectedEdits[$index][$diffOp->type]['orig'], $diffOp->orig);
      $this->assertEquals($expectedEdits[$index][$diffOp->type]['closing'], $diffOp->closing);
    }
  }

}
