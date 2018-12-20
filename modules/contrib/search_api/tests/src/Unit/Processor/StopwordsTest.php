<?php

namespace Drupal\Tests\search_api\Unit\Processor;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\search_api\processor\Stopwords;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "Stopwords" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\Stopwords
 */
class StopwordsTest extends UnitTestCase {

  use ProcessorTestTrait, TestItemsTrait;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpMockContainer();
    $this->processor = new Stopwords([], 'stopwords', []);;
  }

  /**
   * Tests the process() method of the Stopwords processor.
   *
   * @param string $passed_value
   *   The string that should be passed to process().
   * @param string $expected_value
   *   The expected altered string.
   * @param string[] $stopwords
   *   The stopwords with which to configure the test processor.
   *
   * @dataProvider processDataProvider
   */
  public function testProcess($passed_value, $expected_value, array $stopwords) {
    $this->processor->setConfiguration(['stopwords' => $stopwords]);
    $this->invokeMethod('process', [&$passed_value]);
    $this->assertEquals($expected_value, $passed_value);
  }

  /**
   * Data provider for testStopwords().
   *
   * Processor checks for exact case, and tokenized content.
   */
  public function processDataProvider() {
    return [
      [
        'or',
        '',
        ['or'],
      ],
      [
        'orb',
        'orb',
        ['or'],
      ],
      [
        'for',
        'for',
        ['or'],
      ],
      [
        'ordor',
        'ordor',
        ['or'],
      ],
      [
        'ÄÖÜÀÁ<>»«û',
        'ÄÖÜÀÁ<>»«û',
        ['stopword1', 'ÄÖÜÀÁ<>»«', 'stopword3'],
      ],
      [
        'ÄÖÜÀÁ',
        '',
        ['stopword1', 'ÄÖÜÀÁ', 'stopword3'],
      ],
      [
        'ÄÖÜÀÁ stopword1',
        'ÄÖÜÀÁ stopword1',
        ['stopword1', 'ÄÖÜÀÁ', 'stopword3'],
      ],
    ];
  }

  /**
   * Tests the processor's preprocessSearchQuery() method.
   */
  public function testPreprocessSearchQuery() {
    $index = $this->createMock(IndexInterface::class);
    $index->expects($this->any())
      ->method('status')
      ->will($this->returnValue(TRUE));
    /** @var \Drupal\search_api\IndexInterface $index */

    $this->processor->setIndex($index);
    $query = \Drupal::getContainer()
      ->get('search_api.query_helper')
      ->createQuery($index);
    $keys = ['#conjunction' => 'AND', 'foo', 'bar', 'bar foo'];
    $query->keys($keys);

    $configuration = ['stopwords' => ['foobar', 'bar', 'barfoo']];
    $this->processor->setConfiguration($configuration);
    $this->processor->preprocessSearchQuery($query);
    unset($keys[1]);
    $this->assertEquals($keys, $query->getKeys());

    $this->assertEquals(['bar'], $query->getResults()->getIgnoredSearchKeys());
  }

}
