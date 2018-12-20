<?php

namespace Drupal\Tests\facets_range_widget\Unit\Plugin\widget;

use Drupal\facets_range_widget\Plugin\facets\widget\RangeSliderWidget;

/**
 * Unit test for widget.
 *
 * @group facets
 */
class RangeSliderWidgetTest extends SliderWidgetTest {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->widget = new RangeSliderWidget([], 'range_slider_widget', []);
  }

  /**
   * {@inheritdoc}
   */
  public function testGetQueryType() {
    $result = $this->widget->getQueryType($this->queryTypes);
    $this->assertEquals('range', $result);
  }

  /**
   * {@inheritdoc}
   */
  public function testDefaultConfiguration() {
    $default_config = $this->widget->defaultConfiguration();
    $expected = [
      'show_numbers' => FALSE,
      'prefix' => '',
      'suffix' => '',
      'min_type' => 'search_result',
      'min_value' => 0,
      'max_type' => 'search_result',
      'max_value' => 10,
      'step' => 1,
    ];
    $this->assertEquals($expected, $default_config);
  }

  /**
   * {@inheritdoc}
   */
  public function testIsPropertyRequired() {
    $this->assertFalse($this->widget->isPropertyRequired('llama', 'owl'));
    $this->assertTrue($this->widget->isPropertyRequired('range_slider', 'processors'));
    $this->assertTrue($this->widget->isPropertyRequired('show_only_one_result', 'settings'));
  }

  /**
   * {@inheritdoc}
   */
  public function testBuild() {
    $build = parent::testBuild();
    $this->assertTrue($build['range']);
    $this->assertEquals([3, 19999], $build['values']);
  }

}
