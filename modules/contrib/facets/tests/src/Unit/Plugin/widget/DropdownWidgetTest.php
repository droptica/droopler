<?php

namespace Drupal\Tests\facets\Unit\Plugin\widget;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\widget\DropdownWidget;

/**
 * Unit test for widget.
 *
 * @group facets
 */
class DropdownWidgetTest extends WidgetTestBase {

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->widget = new DropdownWidget(['show_numbers' => TRUE], 'dropdown_widget', []);
  }

  /**
   * Tests widget without filters.
   */
  public function testNoFilterResults() {
    $facet = $this->facet;
    $facet->setResults($this->originalResults);

    $output = $this->widget->build($facet);

    $this->assertInternalType('array', $output);
    $this->assertCount(4, $output['#items']);

    $this->assertEquals(['js-facets-dropdown-links'], $output['#attributes']['class']);

    $expected_links = [
      $this->buildLinkAssertion('Llama', 'llama', $facet, 10),
      $this->buildLinkAssertion('Badger', 'badger', $facet, 20),
      $this->buildLinkAssertion('Duck', 'duck', $facet, 15),
      $this->buildLinkAssertion('Alpaca', 'alpaca', $facet, 9),
    ];
    foreach ($expected_links as $index => $value) {
      $this->assertInternalType('array', $output['#items'][$index]);
      $this->assertEquals($value, $output['#items'][$index]['#title']);
      $this->assertInternalType('array', $output['#items'][$index]['#title']);
      $this->assertEquals('link', $output['#items'][$index]['#type']);
      $this->assertEquals(['facet-item'], $output['#items'][$index]['#wrapper_attributes']['class']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testDefaultConfiguration() {
    $default_config = $this->widget->defaultConfiguration();

    // We can't use $this->assertEquals() because that makes mocking here too
    // hard, that way we'd need to also mock the translation interface. That's
    // not needed.
    $this->assertArrayHasKey('show_numbers', $default_config);
    $this->assertArrayHasKey('default_option_label', $default_config);
  }

}
