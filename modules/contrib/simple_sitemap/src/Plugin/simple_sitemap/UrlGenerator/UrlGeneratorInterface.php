<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

/**
 * Interface UrlGeneratorInterface
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 */
interface UrlGeneratorInterface {

  public function generate();

  /**
   * @return mixed
   */
  public function getDataSets();
}
