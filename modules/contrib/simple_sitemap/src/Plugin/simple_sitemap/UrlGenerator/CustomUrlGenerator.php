<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Url;
use Drupal\simple_sitemap\Annotation\UrlGenerator;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\SitemapGenerator;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CustomUrlGenerator
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 *
 * @UrlGenerator(
 *   id = "custom",
 *   title = @Translation("Custom URL generator"),
 *   description = @Translation("Generates URLs set in admin/config/search/simplesitemap/custom."),
 *   weight = 0,
 * )
 *
 */
class CustomUrlGenerator extends UrlGeneratorBase {

  const PATH_DOES_NOT_EXIST_OR_NO_ACCESS_MESSAGE = 'The custom path @path has been omitted from the XML sitemap as it either does not exist, or it is not accessible to anonymous users. You can review custom paths <a href="@custom_paths_url">here</a>.';


  /**
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * @var bool
   */
  protected $includeImages;

  /**
   * CustomUrlGenerator constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\SitemapGenerator $sitemap_generator
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\simple_sitemap\Logger $logger
   * @param \Drupal\simple_sitemap\EntityHelper $entityHelper
   * @param \Drupal\Core\Path\PathValidator $path_validator
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Simplesitemap $generator,
    SitemapGenerator $sitemap_generator,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    Logger $logger,
    EntityHelper $entityHelper,
    PathValidator $path_validator) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $generator,
      $sitemap_generator,
      $language_manager,
      $entity_type_manager,
      $logger,
      $entityHelper
    );
    $this->pathValidator = $path_validator;
  }

  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.sitemap_generator'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('simple_sitemap.logger'),
      $container->get('simple_sitemap.entity_helper'),
      $container->get('path.validator')
    );
  }

  /**
   * @inheritdoc
   */
  public function getDataSets() {
    $this->includeImages = $this->generator->getSetting('custom_links_include_images', FALSE);

    return array_values($this->generator->getCustomLinks());
  }

  /**
   * @inheritdoc
   */
  protected function processDataSet($data_set) {

      // todo: Change to different function, as this also checks if current user has access. The user however varies depending if process was started from the web interface or via cron/drush. Use getUrlIfValidWithoutAccessCheck()?
      if (!$this->pathValidator->isValid($data_set['path'])) {
//        if (!(bool) $this->pathValidator->getUrlIfValidWithoutAccessCheck($data['path'])) {
        $this->logger->m(self::PATH_DOES_NOT_EXIST_OR_NO_ACCESS_MESSAGE,
          ['@path' => $data_set['path'], '@custom_paths_url' => $GLOBALS['base_url'] . '/admin/config/search/simplesitemap/custom'])
          ->display('warning', 'administer sitemap settings')
          ->log('warning');
        return FALSE;
      }

      $url_object = Url::fromUserInput($data_set['path'], ['absolute' => TRUE]);
      $path = $url_object->getInternalPath();

      if ($this->batchSettings['remove_duplicates'] && $this->pathProcessed($path)) {
        return FALSE;
      }

      $entity = $this->entityHelper->getEntityFromUrlObject($url_object);

      $path_data = [
        'url' => $url_object,
        'lastmod' => method_exists($entity, 'getChangedTime')
          ? date_iso8601($entity->getChangedTime()) : NULL,
        'priority' => isset($data_set['priority']) ? $data_set['priority'] : NULL,
        'changefreq' => !empty($data_set['changefreq']) ? $data_set['changefreq'] : NULL,
        'images' => $this->includeImages && method_exists($entity, 'getEntityTypeId')
          ? $this->getImages($entity->getEntityTypeId(), $entity->id())
          : [],
        'meta' => [
          'path' => $path,
        ]
      ];

      // Additional info useful in hooks.
      if (NULL !== $entity) {
        $path_data['meta']['entity_info'] = [
          'entity_type' => $entity->getEntityTypeId(),
          'id' => $entity->id(),
        ];
      }

      return $path_data;
  }
}
