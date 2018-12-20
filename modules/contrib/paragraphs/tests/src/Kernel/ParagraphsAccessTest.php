<?php

namespace Drupal\Tests\paragraphs\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\token\Kernel\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\paragraphs\ParagraphAccessControlHandler
 * @group paragraphs
 */
class ParagraphsAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'paragraphs',
  ];

  /**
   * @covers ::checkCreateAccess
   *
   * @dataProvider createAccessTestCases
   */
  public function testCreateAccess($request_format, $expected_result) {
    $request = new Request();
    $request->setRequestFormat($request_format);
    $this->container->get('request_stack')->push($request);
    $result = $this->container->get('entity_type.manager')->getAccessControlHandler('paragraph')->createAccess(NULL, NULL, [], TRUE);
    $this->assertEquals($expected_result, $result);
  }

  /**
   * Test cases for ::testCreateAccess.
   */
  public function createAccessTestCases() {
    $container = new ContainerBuilder();
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens()->willReturn(TRUE);
    $cache_contexts_manager->reveal();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);

    return [
      'Allowed HTML request format' => [
        'html',
        AccessResult::allowed()->addCacheContexts(['request_format']),
      ],
      'Forbidden other formats' => [
        'json',
        AccessResult::neutral()->addCacheContexts(['request_format']),
      ],
    ];
  }

}
