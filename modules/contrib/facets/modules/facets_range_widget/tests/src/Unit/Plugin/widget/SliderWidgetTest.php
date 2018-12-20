<?php

namespace Drupal\Tests\facets_range_widget\Unit\Plugin\widget;

use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Result\Result;
use Drupal\facets\Widget\WidgetPluginManager;
use Drupal\facets_range_widget\Plugin\facets\widget\SliderWidget;
use Drupal\Tests\facets\Unit\Plugin\widget\WidgetTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit test for widget.
 *
 * @group facets
 */
class SliderWidgetTest extends WidgetTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->widget = new SliderWidget([], 'slider_widget', []);
  }

  /**
   * {@inheritdoc}
   */
  public function testGetQueryType() {
    $result = $this->widget->getQueryType($this->queryTypes);
    $this->assertEquals(NULL, $result);
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
    $this->assertTrue($this->widget->isPropertyRequired('slider', 'processors'));
    $this->assertTrue($this->widget->isPropertyRequired('show_only_one_result', 'settings'));
  }

  /**
   * Tests building of the widget.
   */
  public function testBuild() {
    $widget = $this->prophesize(SliderWidget::class);
    $widget->getConfiguration()->willReturn(['show_numbers' => FALSE]);
    $pluginManager = $this->prophesize(WidgetPluginManager::class);
    $pluginManager->createInstance('slider', [])
      ->willReturn($widget->reveal());

    $url_generator = $this->prophesize(UrlGeneratorInterface::class);

    $container = new ContainerBuilder();
    $container->set('plugin.manager.facets.widget', $pluginManager->reveal());
    $container->set('url_generator', $url_generator->reveal());
    \Drupal::setContainer($container);

    $facet = new Facet(['id' => 'barn_owl'], 'facets_facet');
    $originalResults = [];
    foreach (range(3, 20000, 2) as $rv) {
      $res = new Result($facet, $rv, 'Value: ' . $rv, ceil($rv / 2));
      $res->setUrl(new Url('test'));
      $originalResults[] = $res;
    }

    $this->originalResults = $originalResults;

    $facet->setResults($this->originalResults);
    $facet->setFieldIdentifier('owl');
    $facet->setWidget('slider', []);

    $startTime = microtime(TRUE);
    $build = $this->widget->build($facet);
    $stopTime = microtime(TRUE);

    if (($stopTime - $startTime) > 1) {
      $this->fail('Test is too slow');
    }

    $this->assertInternalType('array', $build);
    $build = $build['#attached']['drupalSettings']['facets']['sliders']['barn_owl'];
    $this->assertEquals(3, $build['min']);
    $this->assertEquals(19999, $build['max']);
    return $build;
  }

}
