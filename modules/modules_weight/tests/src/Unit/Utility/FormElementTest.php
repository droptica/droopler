<?php

namespace Drupal\Tests\modules_weight\Unit\Utility;

use Drupal\modules_weight\Utility\FormElement;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the FormElement class methods.
 *
 * @group modules_weight
 * @coversDefaultClass \Drupal\modules_weight\Utility\FormElement
 */
class FormElementTest extends UnitTestCase {

  /**
   * Tests the max delta with FormElement::getMaxDelta().
   *
   * @param int $expected
   *   The expected result from calling the function.
   * @param int $weight
   *   The element weight to run through FormElement::getMaxDelta().
   *
   * @covers ::getMaxDelta
   * @dataProvider providerGetMaxDelta
   */
  public function testGetMaxDelta($expected, $weight) {
    $this->assertEquals($expected, FormElement::getMaxDelta($weight));
  }

  /**
   * Data provider for testGetMaxDelta().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from FormElement::getMaxDelta().
   *   - 'weight' - The element weight.
   *
   * @see testGetMaxDelta()
   */
  public function providerGetMaxDelta() {
    $tests['value higher than 100'] = [101, 101];
    $tests['value higher than 100'] = [234, 234];
    $tests['value lower than -100'] = [101, -101];
    $tests['value lower than -100'] = [234, -234];
    $tests['value lower than 100 and higher than -100'] = [100, 35];
    $tests['value lower than 100 and higher than -100'] = [100, 33];
    $tests['value lower than 100 and higher than -100'] = [100, -33];
    $tests['value lower than 100 and higher than -100'] = [100, -35];
    $tests['value equal to 100'] = [100, 100];
    $tests['value equal to -100'] = [100, -100];

    return $tests;
  }

}
