<?php

namespace Drupal\better_exposed_filters\Tests;

use Drupal\better_exposed_filters\Plugin\views\exposed_form\BetterExposedFilters;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\better_exposed_filters\Plugin\views\exposed_form\BetterExposedFilters
 * @group bef
 */
class BetterExposedFiltersTest extends UnitTestCase {

  /**
   * Order in the returned array matters for BEF rewrites. But assertEquals
   * don't take array order into account.
   *
   * @param $expected
   *   Expectedd value.
   * @param $actual
   *   Actual value.
   * @param $message
   *   Optionsal error message on failure.
   */
  protected function assertRewrite($expected, $actual, $message = NULL) {
    // Order matters.
    foreach ($actual as $actual_item) {
      $expected_item = array_shift($expected);
      $this->assertEquals($expected_item, $actual_item, $message);
    }
  }

  public function providerTestRewriteOptions() {
    $data = [];

    // Super basic rewrite.
    $data[] = [
      ['foo' => 'bar'],
      "bar|baz",
      ['foo' => 'baz'],
    ];

    // Removes an option.
    $data[] = [
      ['foo' => 'bar'],
      "bar|",
      [],
    ];

    // An option in the middle is removed -- preserves order.
    $data[] = [
      ['foo' => '1', 'bar' => '2', 'baz' => '3'],
      "2|",
      ['foo' => '1', 'baz' => '3'],
    ];

    // Ensure order is preserved.
    $data[] = [
      ['foo' => '1', 'bar' => '2', 'baz' => '3'],
      "2|Two",
      ['foo' => '1', 'bar' => 'Two', 'baz' => '3'],
    ];

    // No options are replaced.
    $data[] = [
      ['foo' => '1', 'bar' => '2', 'baz' => '3'],
      "4|Two",
      ['foo' => '1', 'bar' => '2', 'baz' => '3'],
    ];

    // All options are replaced.
    $data[] = [
      ['foo' => '1', 'bar' => '2', 'baz' => '3'],
      "1|One\n2|Two\n3|Three",
      ['foo' => 'One', 'bar' => 'Two', 'baz' => 'Three'],
    ];

    return $data;
  }

  /**
   * Tests options are rewritten correctly.
   *
   * @dataProvider providerTestRewriteOptions
   * @covers ::rewriteOptions
   */
  public function testRewriteOptions($options, $rewriteSettings, $expected) {
    $bef = new TestBEF([], 'default', []);
    $actual = $bef->testRewriteOptions($options, $rewriteSettings);
    $this->assertRewrite($expected, $actual);
  }

  public function providerTestRewriteReorderOptions() {
    $data = [];

    // Basic use case.
    $data[] = [
      ['foo' => '1', 'bar' => '2', 'baz' => '3'],
      '2|Two',
      ['bar' => 'Two', 'foo' => '1', 'baz' => '3'],
    ];

    // No option replaced should not change the order
    $data[] = [
      ['foo' => '1', 'bar' => '2', 'baz' => '3'],
      '4|Four',
      ['foo' => '1', 'bar' => '2', 'baz' => '3'],
    ];

    // Completely reorder options
    $data[] = [
      ['foo' => '1', 'bar' => '2', 'baz' => '3'],
      "3|Three\n2|Two\n1|One",
      ['baz' => 'Three', 'bar' => 'Two', 'foo' => 'One'],
    ];

    return $data;
  }

  /**
   * Tests options are rewritten correctly.
   *
   * @dataProvider providerTestRewriteReorderOptions
   * @covers ::rewriteOptions
   */
  public function testRewriteReorderOptions($options, $rewriteSettings, $expected) {
    $bef = new TestBEF([], 'default', []);
    $actual = $bef->testRewriteOptions($options, $rewriteSettings, TRUE);
    $this->assertRewrite($expected, $actual);
  }

  public function providerTestRewriteTaxonomy() {
    $data = [];

    // Replace a single item, no change in order.
    $data[] = [
      [
        (object)['option' => [123 => 'term1']],
        (object)['option' => [456 => 'term2']],
        (object)['option' => [789 => 'term3']],
      ],
      "term2|Two",
      [
        (object)['option' => [123 => 'term1']],
        (object)['option' => [456 => 'Two']],
        (object)['option' => [789 => 'term3']],
      ],
    ];

    // Replace all items, no change in order.
    $data[] = [
      [
        (object)['option' => [123 => 'term1']],
        (object)['option' => [456 => 'term2']],
        (object)['option' => [789 => 'term3']],
      ],
      "term2|Two\nterm3|Three\nterm1|One",
      [
        (object)['option' => [123 => 'One']],
        (object)['option' => [456 => 'Two']],
        (object)['option' => [789 => 'Three']],
      ],
    ];

    // @TODO:
    // Replace a single item, with change in order.
    //$data[] = [
    //  [
    //    (object)['option' => [123 => 'term1']],
    //    (object)['option' => [456 => 'term2']],
    //    (object)['option' => [789 => 'term3']],
    //  ],
    //  "term2|Two",
    //  [
    //    (object)['option' => [456 => 'Two']],
    //    (object)['option' => [123 => 'term1']],
    //    (object)['option' => [789 => 'term3']],
    //  ],
    //  TRUE,
    //];

    //// Replace all items, with change in order.
    //$data[] = [
    //  [
    //    (object)['option' => [123 => 'term1']],
    //    (object)['option' => [456 => 'term2']],
    //    (object)['option' => [789 => 'term3']],
    //  ],
    //  "term2|Two\nterm3|Three\nterm1|One",
    //  [
    //    (object)['option' => [456 => 'Two']],
    //    (object)['option' => [789 => 'Three']],
    //    (object)['option' => [123 => 'One']],
    //  ],
    //  TRUE,
    //];

    return $data;
  }

  /**
   * Tests options are rewritten correctly.
   *
   * @dataProvider providerTestRewriteTaxonomy
   * @covers ::rewriteOptions
   */
  public function testRewriteTaxonomy($options, $rewriteSettings, $expected, $reorder = FALSE) {
    $bef = new TestBEF([], 'default', []);
    $actual = $bef->testRewriteOptions($options, $rewriteSettings, $reorder);
    $this->assertRewrite($expected, $actual);
  }

}

// Allows access to otherwise protected methods in BEF.
class TestBEF extends BetterExposedFilters {

  public function testRewriteOptions($options, $rewriteSettings, $reorder = FALSE) {
    return $this->rewriteOptions($options, $rewriteSettings, $reorder);
  }

}
