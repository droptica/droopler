<?php

namespace Drupal\Tests\search_api\Unit\Processor;

use Drupal\search_api\Plugin\search_api\processor\HtmlFilter;
use Drupal\search_api\Utility\Utility;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "HTML filter" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\HtmlFilter
 */
class HtmlFilterTest extends UnitTestCase {

  use ProcessorTestTrait;
  use TestItemsTrait;

  /**
   * Creates a new processor object for use in the tests.
   */
  public function setUp() {
    parent::setUp();

    $this->setUpMockContainer();

    $this->processor = new HtmlFilter([], 'html_filter', []);
  }

  /**
   * Tests preprocessing field values with "title" settings.
   *
   * @param string $passed_value
   *   The value that should be passed into process().
   * @param string $expected_value
   *   The expected processed value.
   * @param bool $title_config
   *   The value to set for the processor's "title" setting.
   *
   * @dataProvider titleConfigurationDataProvider
   */
  public function testTitleConfiguration($passed_value, $expected_value, $title_config) {
    $configuration = [
      'tags' => [],
      'title' => $title_config,
      'alt' => FALSE,
    ];
    $this->processor->setConfiguration($configuration);
    $type = 'text';
    $this->invokeMethod('processFieldValue', [&$passed_value, $type]);
    $this->assertEquals($expected_value, $passed_value);
  }

  /**
   * Data provider for testTitleConfiguration().
   *
   * @return array
   *   An array of argument arrays for testTitleConfiguration().
   */
  public function titleConfigurationDataProvider() {
    return [
      ['word', 'word', FALSE],
      ['word', 'word', TRUE],
      ['<div>word</div>', 'word', TRUE],
      ['<div title="TITLE">word</div>', 'TITLE word', TRUE],
      ['<div title="TITLE">word</div>', 'word', FALSE],
      ['<div data-title="TITLE">word</div>', 'word', TRUE],
      ['<div title="TITLE">word</a>', 'TITLE word', TRUE],
    ];
  }

  /**
   * Tests preprocessing field values with "alt" settings.
   *
   * @param string $passed_value
   *   The value that should be passed into process().
   * @param mixed $expected_value
   *   The expected processed value.
   * @param bool $alt_config
   *   The value to set for the processor's "alt" setting.
   *
   * @dataProvider altConfigurationDataProvider
   */
  public function testAltConfiguration($passed_value, $expected_value, $alt_config) {
    $configuration = [
      'tags' => ['img' => '2'],
      'title' => FALSE,
      'alt' => $alt_config,
    ];
    $this->processor->setConfiguration($configuration);
    $type = 'text';
    $this->invokeMethod('processFieldValue', [&$passed_value, $type]);
    $this->assertEquals($expected_value, $passed_value);
  }

  /**
   * Data provider method for testAltConfiguration().
   *
   * @return array
   *   An array of argument arrays for testAltConfiguration().
   */
  public function altConfigurationDataProvider() {
    return [
      ['word', [Utility::createTextToken('word')], FALSE],
      ['word', [Utility::createTextToken('word')], TRUE],
      [
        '<img src="href" />word',
        [Utility::createTextToken('word')],
        TRUE,
      ],
      [
        '<img alt="ALT"/> word',
        [
          Utility::createTextToken('ALT', 2),
          Utility::createTextToken('word'),
        ],
        TRUE,
      ],
      [
        '<img alt="ALT" /> word',
        [Utility::createTextToken('word')],
        FALSE,
      ],
      [
        '<img data-alt="ALT"/> word',
        [Utility::createTextToken('word')],
        TRUE,
      ],
      [
        '<img src="href" alt="ALT" title="Bar" /> word </a>',
        [
          Utility::createTextToken('ALT', 2),
          Utility::createTextToken('word'),
        ],
        TRUE,
      ],
      // Test fault tolerance.
      [
        'a < b',
        [
          Utility::createTextToken('a < b'),
        ],
        TRUE,
      ],
    ];
  }

  /**
   * Tests preprocessing field values with "alt" settings.
   *
   * @param string $passed_value
   *   The value that should be passed into process().
   * @param mixed $expected_value
   *   The expected processed value.
   * @param float[] $tags_config
   *   The value to set for the processor's "tags" setting.
   *
   * @dataProvider tagConfigurationDataProvider
   */
  public function testTagConfiguration($passed_value, $expected_value, array $tags_config) {
    $configuration = [
      'tags' => $tags_config,
      'title' => TRUE,
      'alt' => TRUE,
    ];
    $this->processor->setConfiguration($configuration);
    $type = 'text';
    $this->invokeMethod('processFieldValue', [&$passed_value, $type]);
    $this->assertEquals($expected_value, $passed_value);
  }

  /**
   * Data provider method for testTagConfiguration().
   *
   * @return array
   *   An array of argument arrays for testTagConfiguration().
   */
  public function tagConfigurationDataProvider() {
    $complex_test = [
      '<h2>Foo Bar <em>Baz</em></h2>

<p>Bla Bla Bla. <strong title="Foobar">Important:</strong> Bla.</p>
<img src="/foo.png" alt="Some picture" />
<span>This is hidden</span>',
      [
        Utility::createTextToken('Foo Bar', 3.0),
        Utility::createTextToken('Baz', 4.5),
        Utility::createTextToken('Bla Bla Bla.', 1.0),
        Utility::createTextToken('Foobar Important:', 2.0),
        Utility::createTextToken('Bla.', 1.0),
        Utility::createTextToken('Some picture', 0.5),
      ],
      [
        'em' => 1.5,
        'strong' => 2.0,
        'h2' => 3.0,
        'img' => 0.5,
        'span' => 0,
      ],
    ];
    $tags_config = ['h2' => '2'];
    return [
      ['h2word', 'h2word', []],
      ['h2word', [Utility::createTextToken('h2word')], $tags_config],
      [
        'foo bar <h2> h2word </h2>',
        [
          Utility::createTextToken('foo bar'),
          Utility::createTextToken('h2word', 2.0),
        ],
        $tags_config,
      ],
      [
        'foo bar <h2>h2word</h2>',
        [
          Utility::createTextToken('foo bar'),
          Utility::createTextToken('h2word', 2.0),
        ],
        $tags_config,
      ],
      [
        '<div>word</div>',
        [Utility::createTextToken('word', 2)],
        ['div' => 2],
      ],
      $complex_test,
    ];
  }

  /**
   * Tests whether strings are correctly handled.
   *
   * String field handling should be completely independent of configuration.
   *
   * @param array $config
   *   The configuration to set on the processor.
   *
   * @dataProvider stringProcessingDataProvider
   */
  public function testStringProcessing(array $config) {
    $this->processor->setConfiguration($config);

    $passed_value = '<h2>Foo Bar <em>Baz</em></h2>

<p>Bla Bla Bla. <strong title="Foobar">Important:</strong> Bla.</p>
<img src="/foo.png" alt="Some picture" />
<span>This is hidden</span>';
    $expected_value = preg_replace('/\s+/', ' ', strip_tags($passed_value));

    $type = 'string';
    $this->invokeMethod('processFieldValue', [&$passed_value, $type]);
    $this->assertEquals($expected_value, $passed_value);
  }

  /**
   * Provides a few sets of HTML filter configuration.
   *
   * @return array
   *   An array of argument arrays for testStringProcessing(), where each array
   *   contains a HTML filter configuration as the only value.
   */
  public function stringProcessingDataProvider() {
    $configs = [];
    $configs[] = [[]];
    $config['tags'] = [
      'h2' => 2.0,
      'span' => 4.0,
      'strong' => 1.5,
      'p' => 0,
    ];
    $configs[] = [$config];
    $config['title'] = TRUE;
    $configs[] = [$config];
    $config['alt'] = TRUE;
    $configs[] = [$config];
    unset($config['tags']);
    $configs[] = [$config];
    return $configs;
  }

}
