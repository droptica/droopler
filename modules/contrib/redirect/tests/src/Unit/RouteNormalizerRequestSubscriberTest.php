<?php

namespace Drupal\Tests\redirect\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\redirect\EventSubscriber\RouteNormalizerRequestSubscriber;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Tests the route normalizer.
 *
 * @group redirect
 *
 * @coversDefaultClass \Drupal\redirect\EventSubscriber\RouteNormalizerRequestSubscriber
 */
class RouteNormalizerRequestSubscriberTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $kill_switch = $this->getMock('\Drupal\Core\PageCache\ResponsePolicy\KillSwitch');
    $kill_switch->expects($this->any())
      ->method('trigger')
      ->withAnyParameters()
      ->will($this->returnValue(NULL));
    $container = new ContainerBuilder();
    $container->set('page_cache_kill_switch', $kill_switch);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::onKernelRequestRedirect
   */
  public function testSkipIfFlagNotEnabled() {
    $request_uri = 'https://example.com/route-to-normalize';
    $request_query = [];

    $event = $this->getGetResponseEventStub($request_uri, http_build_query($request_query));
    // We set 'route_normalizer_enabled' config to FALSE and expect to leave onKernelRequestRedirect at the beginning,
    // i.e. $this->redirectChecker->canRedirect($request) should never be called.
    $subscriber = $this->getSubscriber($request_uri, FALSE, FALSE);
    $subscriber->onKernelRequestRedirect($event);
  }

  /**
   * @covers ::onKernelRequestRedirect
   */
  public function testSkipIfSubRequest() {
    $request_uri = 'https://example.com/route-to-normalize';
    $request_query = [];

    $event = $this->getGetResponseEventStub($request_uri, http_build_query($request_query), HttpKernelInterface::SUB_REQUEST);
    // We are using SUB_REQUEST as the request type and expect to leave onKernelRequestRedirect at the beginning,
    // i.e. $this->redirectChecker->canRedirect($request) should never be called.
    $subscriber = $this->getSubscriber($request_uri, TRUE, FALSE);
    $subscriber->onKernelRequestRedirect($event);
  }

  /**
   * @covers ::onKernelRequestRedirect
   */
  public function testSkipIfRequestAttribute() {
    $request_uri = 'https://example.com/route-to-normalize';
    $request_query = [];

    $event = $this->getGetResponseEventStub($request_uri, http_build_query($request_query), HttpKernelInterface::MASTER_REQUEST, TRUE);
    // We set '_disable_route_normalizer' as a request attribute and expect to leave onKernelRequestRedirect at the beginning,
    // i.e. $this->redirectChecker->canRedirect($request) should never be called.
    $subscriber = $this->getSubscriber($request_uri, TRUE, FALSE);
    $subscriber->onKernelRequestRedirect($event);
  }

  /**
   * @covers ::onKernelRequestRedirect
   * @dataProvider getTestUrls
   */
  public function testOnKernelRequestRedirect($request_uri, $request_query, $expected, $expect_normalization) {
    $event = $this->getGetResponseEventStub($request_uri, http_build_query($request_query));
    $subscriber = $this->getSubscriber($request_uri);
    $subscriber->onKernelRequestRedirect($event);

    if ($expect_normalization) {
      $response = $event->getResponse();
      $this->assertEquals($expected, $response->getTargetUrl());
    }
  }

  /**
   * Data provider for testOnKernelRequestRedirect().
   */
  public function getTestUrls() {
    return [
      ['https://example.com/route-to-normalize', [], 'https://example.com/route-to-normalize', FALSE],
      ['https://example.com/route-to-normalize', ['key' => 'value'], 'https://example.com/route-to-normalize?key=value', FALSE],
      ['https://example.com/index.php/', ['q' => 'node/1'], 'https://example.com/?q=node/1', TRUE],
    ];
  }

  /**
   * Create a RouteNormalizerRequestSubscriber object.
   *
   * @param string $request_uri
   *   The return value for the generateFromRoute method.
   * @param bool $enabled
   *   Flag indicating if the normalizer shoud be enabled.
   * @param bool $call_expected
   *   If true, canRedirect() and other methods should be called once.
   *
   * @return \Drupal\redirect\EventSubscriber\RouteNormalizerRequestSubscriber
   */
  protected function getSubscriber($request_uri, $enabled = TRUE, $call_expected = TRUE) {
    return new RouteNormalizerRequestSubscriber(
      $this->getUrlGeneratorStub($request_uri, $call_expected),
      $this->getPathMatcherStub($call_expected),
      $this->getConfigFactoryStub([
        'redirect.settings' => [
          'route_normalizer_enabled' => $enabled,
          'default_status_code' => 301,
        ],
      ]),
      $this->getRedirectCheckerStub($call_expected)
    );
  }

  /**
   * Gets the UrlGenerator mock object.
   *
   * @param string $request_uri
   *   The return value for the generateFromRoute method.
   * @param bool $call_expected
   *   If true, we expect generateFromRoute() to be called once.
   *
   * @return \Drupal\Core\Routing\UrlGeneratorInterface|PHPUnit_Framework_MockObject_MockObject
   */
  protected function getUrlGeneratorStub($request_uri, $call_expected = TRUE) {
    $url_generator = $this->getMockBuilder('\Drupal\Core\Routing\UrlGeneratorInterface')
      ->getMock();

    $options = ['absolute' => TRUE];

    $expectation = $call_expected ? $this->once() : $this->never();

    $url_generator->expects($expectation)
      ->method('generateFromRoute')
      ->with('<current>', [], $options)
      ->willReturn($request_uri);
    return $url_generator;
  }

  /**
   * Gets the PathMatcher mock object.
   *
   * @param bool $call_expected
   *   If true, we expect isFrontPage() to be called once.
   *
   * @return \Drupal\Core\Path\PathMatcherInterface|PHPUnit_Framework_MockObject_MockObject
   */
  protected function getPathMatcherStub($call_expected = TRUE) {
    $path_matcher = $this->getMockBuilder('\Drupal\Core\Path\PathMatcherInterface')
      ->getMock();

    $expectation = $call_expected ? $this->once() : $this->never();

    $path_matcher->expects($expectation)
      ->method('isFrontPage')
      ->withAnyParameters()
      ->willReturn(FALSE);
    return $path_matcher;
  }

  /**
   * Gets the RedirectChecker mock object.
   *
   * @param bool $call_expected
   *   If true, we expect canRedirect() to be called once.
   *
   * @return \Drupal\redirect\RedirectChecker|PHPUnit_Framework_MockObject_MockObject
   */
  protected function getRedirectCheckerStub($call_expected = TRUE) {
    $redirect_checker = $this->getMockBuilder('\Drupal\redirect\RedirectChecker')
      ->disableOriginalConstructor()
      ->getMock();

    $expectation = $call_expected ? $this->once() : $this->never();

    $redirect_checker->expects($expectation)
      ->method('canRedirect')
      ->withAnyParameters()
      ->willReturn(TRUE);
    return $redirect_checker;
  }

  /**
   * Returns a GET response event object.
   *
   * @param string $path_info
   *   The path of the request.
   * @param array $query_string
   *   The query string of the request.
   * @param int $request_type
   *   The request type of the request.
   * @param bool $set_request_attribute
   *   If true, the request attribute '_disable_route_normalizer' will be set.
   *
   * @return \Symfony\Component\HttpKernel\Event\GetResponseEvent
   */
  protected function getGetResponseEventStub($path_info, $query_string, $request_type = HttpKernelInterface::MASTER_REQUEST, $set_request_attribute = FALSE) {
    $request = Request::create($path_info . '?' . $query_string, 'GET', [], [], [], ['SCRIPT_NAME' => 'index.php', 'SCRIPT_FILENAME' => 'index.php']);

    if ($set_request_attribute === TRUE) {
      $request->attributes->add(['_disable_route_normalizer' => TRUE]);
    }

    $http_kernel = $this->getMockBuilder('\Symfony\Component\HttpKernel\HttpKernelInterface')
      ->getMock();
    return new GetResponseEvent($http_kernel, $request, $request_type);
  }

}
