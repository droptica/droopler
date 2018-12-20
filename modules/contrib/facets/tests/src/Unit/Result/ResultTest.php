<?php

namespace Drupal\Tests\facets\Unit\Result;

use Drupal\Core\Url;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;

/**
 * Class ResultTest.
 *
 * @group facets
 */
class ResultTest extends UnitTestCase {

  /**
   * Test facet creation.
   */
  public function testCreation() {
    $facet = new Facet(['id' => 'foo'], 'facets_facet');

    $result = new Result($facet, 11, 'Eleven', '3.11');
    $this->assertInstanceOf(Result::class, $result);
    $this->assertSame(11, $result->getRawValue());
    $this->assertSame('Eleven', $result->getDisplayValue());
    $this->assertSame(3, $result->getCount());
    $this->assertSame($facet, $result->getFacet());
  }

  /**
   * Tests getters.
   */
  public function testGetters() {
    $facet = new Facet(['id' => 'foo'], 'facets_facet');

    $result = new Result($facet, 11, 'Eleven', 3);
    $result->setCount(11.2);
    $this->assertSame(11, $result->getCount());
    $result->setDisplayValue('Foo');
    $this->assertSame('Foo', $result->getDisplayValue());

    $url = new Url('foo');
    $result->setUrl($url);
    $this->assertSame($url, $result->getUrl());
  }

}
