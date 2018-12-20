<?php

namespace Drupal\Tests\checklistapi\Unit;

use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;

require_once __DIR__ . '/../../../checklistapi.module';

/**
 * Tests the functions in checklistapi.module.
 *
 * @group checklistapi
 */
class ChecklistapiModuleTest extends UnitTestCase {

  /**
   * Tests checklistapi_sort_array().
   */
  public function testChecklistapiSortArray() {
    $input = [
      '#title' => 'Checklist API test',
      '#path' => 'admin/config/development/checklistapi-test',
      // @codingStandardsIgnoreLine
      '#description' => 'A test checklist.',
      '#help' => '<p>This is a test checklist.</p>',
      'group_two' => [
        '#title' => 'Group two',
      ],
      'group_one' => [
        '#title' => 'Group one',
        // @codingStandardsIgnoreLine
        '#description' => '<p>Group one description.</p>',
        '#weight' => -1,
        'item_three' => [
          '#title' => 'Item three',
          '#weight' => 1,
        ],
        'item_one' => [
          '#title' => 'Item one',
          // @codingStandardsIgnoreLine
          '#description' => 'Item one description',
          '#weight' => -1,
          'link_three' => [
            '#text' => 'Link three',
            '#url' => Url::fromUri('http://example.com/three'),
            '#weight' => 3,
          ],
          'link_two' => [
            '#text' => 'Link two',
            '#url' => Url::fromUri('http://example.com/two'),
            '#weight' => 2,
          ],
          'link_one' => [
            '#text' => 'Link one',
            '#url' => Url::fromUri('http://example.com/one'),
            '#weight' => 1,
          ],
        ],
        'item_two' => [
          '#title' => 'Item two',
        ],
      ],
      'group_four' => [
        '#title' => 'Group four',
        '#weight' => 1,
      ],
      'group_three' => [
        '#title' => 'Group three',
      ],
    ];

    $output = checklistapi_sort_array($input);

    $this->assertEquals(0, $output['group_two']['#weight'], 'Failed to supply a default for omitted element weight.');
    $this->assertEquals(0, $output['group_three']['#weight'], 'Failed to supply a default in place of invalid element weight.');
    $this->assertEquals(-1, $output['group_one']['#weight'], 'Failed to retain a valid element weight.');
    $this->assertEquals(
      ['group_one', 'group_two', 'group_three', 'group_four'],
      Element::children($output),
      'Failed to sort elements by weight.'
    );
    $this->assertEquals(
      ['link_one', 'link_two', 'link_three'],
      Element::children($output['group_one']['item_one']),
      'Failed to recurse through element descendants.'
    );
  }

  /**
   * Tests checklistapi_strtolowercamel().
   */
  public function testChecklistapiStrtolowercamel() {
    $this->assertEquals('abcDefGhi', checklistapi_strtolowercamel('Abc def_ghi'), 'Failed to convert string to lowerCamel case.');
  }

  /**
   * Tests that checklistapi_checklist_access() rejects an invalid mode.
   */
  public function testChecklistapiChecklistAccessInvalidMode() {
    $this->setExpectedException(\InvalidArgumentException::class, 'No such operation "invalid operation');
    checklistapi_checklist_access(NULL, 'invalid operation');
  }

}
