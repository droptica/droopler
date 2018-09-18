<?php

namespace Drupal\Tests\modules_weight\Unit\Utility;

use Drupal\modules_weight\Utility\SortArray;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the SortArray class methods.
 *
 * @group modules_weight
 * @coversDefaultClass \Drupal\modules_weight\Utility\SortArray
 */
class SortArrayTest extends UnitTestCase {

  /**
   * Tests the array sort with SortArray::sortByWeightAndName().
   *
   * @param int $expected
   *   The expected result from calling the function.
   * @param array $a
   *   The first input array to order through SortArray::sortByWeightAndName().
   * @param array $b
   *   The second input array to order through SortArray::sortByWeightAndName().
   *
   * @covers ::sortByWeightAndName
   * @dataProvider providerSortByWeightAndName
   */
  public function testSortByWeightAndName($expected, array $a, array $b) {
    $result = SortArray::sortByWeightAndName($a, $b);
    // Checking that the two values are numerics.
    $this->assertTrue(is_numeric($expected) && is_numeric($result), 'Parameters are numeric.');
    // Checking that both are equals.
    $this->assertTrue(($expected < 0 && $result < 0) || ($expected > 0 && $result > 0) || ($expected === 0 && $result === 0), 'Numbers are either both negative, both positive or both zero.');
  }

  /**
   * Data provider for testSortByWeightAndName().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from SortArray::sortByWeightAndName().
   *   - 'a' - The first input array.
   *   - 'b' - The second input array.
   *
   * @see testSortByWeightAndName()
   */
  public function providerSortByWeightAndName() {

    $tests['same weight different name'] = [
      -1,
      [
        'name' => 'Admin Toolbar',
        'weight' => 1,
      ],
      [
        'name' => 'Asana',
        'weight' => 1,
      ],
    ];

    $tests['same weight different name'] = [
      1,
      [
        'name' => 'Asana',
        'weight' => 1,
      ],
      [
        'name' => 'Admin Toolbar',
        'weight' => 1,
      ],
    ];

    $tests['different weight'] = [
      -1,
      [
        'name' => 'Asana',
        'weight' => -15,
      ],
      [
        'name' => 'Admin Toolbar',
        'weight' => 8,
      ],
    ];

    $tests['different weight'] = [
      1,
      [
        'name' => 'Asana',
        'weight' => 51,
      ],
      [
        'name' => 'Admin Toolbar',
        'weight' => -10,
      ],
    ];

    $tests['with no weight'] = [
      -1,
      [
        'name' => 'Asana',
      ],
      [
        'name' => 'Admin Toolbar',
        'weight' => 8,
      ],
    ];

    $tests['with no weight'] = [
      1,
      [
        'name' => 'Asana',
        'weight' => 51,
      ],
      [
        'name' => 'Admin Toolbar',
      ],
    ];

    return $tests;
  }

}
