<?php

namespace Drupal\simple_sitemap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Cache\CacheableResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;

/**
 * Class SimplesitemapController
 * @package Drupal\simple_sitemap\Controller
 */
class SimplesitemapController extends ControllerBase {

  /**
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $generator;

  /**
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $cacheKillSwitch;

  /**
   * SimplesitemapController constructor.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $cache_kill_switch
   */
  public function __construct(Simplesitemap $generator, KillSwitch $cache_kill_switch) {
    $this->generator = $generator;
    $this->cacheKillSwitch = $cache_kill_switch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_sitemap.generator'),
      $container->get('page_cache_kill_switch')
    );
  }

  /**
   * Returns the whole sitemap, a requested sitemap chunk, or the sitemap index file.
   * Caches the response in case of expected output, prevents caching otherwise.
   *
   * @param int $chunk_id
   *   Optional ID of the sitemap chunk. If none provided, the first chunk or
   *   the sitemap index is fetched.
   *
   * @throws NotFoundHttpException
   *
   * @return object
   *   Returns an XML response.
   */
  public function getSitemap($chunk_id = NULL) {
    $output = $this->generator->getSitemap($chunk_id);
    if (!$output) {
      $this->cacheKillSwitch->trigger();
      throw new NotFoundHttpException();
    }

    // Display sitemap with correct XML header.
    $response = new CacheableResponse($output, Response::HTTP_OK, [
      'content-type' => 'application/xml',
      'X-Robots-Tag' => 'noindex', // Do not index the sitemap itself.
    ]);

    // Cache output.
    $meta_data = $response->getCacheableMetadata();
    $meta_data->addCacheTags(['simple_sitemap']);

    return $response;
  }
}
