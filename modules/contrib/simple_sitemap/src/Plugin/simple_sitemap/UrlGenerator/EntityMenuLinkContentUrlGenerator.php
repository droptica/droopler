<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\SitemapGenerator;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Menu\MenuLinkTree;

/**
 * Class EntityMenuLinkContentUrlGenerator
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 *
 * @UrlGenerator(
 *   id = "entity_menu_link_content",
 *   title = @Translation("Menu link URL generator"),
 *   description = @Translation("Generates menu link URLs by overriding the 'entity' URL generator."),
 *   weight = 5,
 *   settings = {
 *     "instantiate_for_each_data_set" = true,
 *     "overrides_entity_type" = "menu_link_content",
 *   },
 * )
 */
class EntityMenuLinkContentUrlGenerator extends UrlGeneratorBase {

  /**
   * @var \Drupal\Core\Menu\MenuLinkTree
   */
  protected $menuLinkTree;

  /**
   * EntityMenuLinkContentUrlGenerator constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\SitemapGenerator $sitemap_generator
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\simple_sitemap\Logger $logger
   * @param \Drupal\simple_sitemap\EntityHelper $entityHelper
   * @param \Drupal\Core\Menu\MenuLinkTree $menu_link_tree
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
    MenuLinkTree $menu_link_tree
  ) {
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
    $this->menuLinkTree = $menu_link_tree;
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
      $container->get('menu.link_tree')
    );
  }

  /**
   * @inheritdoc
   */
  public function getDataSets() {
    $menu_names = [];
    $bundle_settings = $this->generator->getBundleSettings();
    if (!empty($bundle_settings['menu_link_content'])) {
      foreach ($bundle_settings['menu_link_content'] as $bundle_name => $settings) {
        if ($settings['index']) {
          $menu_names[] = $bundle_name;
        }
      }
    }

    return $menu_names;
  }

  /**
   * @inheritdoc
   */
  protected function processDataSet($link) {

    if (!$link->isEnabled()) {
      return FALSE;
    }

    $url_object = $link->getUrlObject();

    // Do not include external paths.
    if ($url_object->isExternal()) {
      return FALSE;
    }

    // If not a menu_link_content link, use bundle settings.
    $meta_data = $link->getMetaData();
    if (empty($meta_data['entity_id'])) {
      $entity_settings = $this->generator->getBundleSettings('menu_link_content', $link->getMenuName());
    }

    // If menu link is of entity type menu_link_content, take under account its entity override.
    else {
      $entity_settings = $this->generator->getEntityInstanceSettings('menu_link_content', $meta_data['entity_id']);

      if (empty($entity_settings['index'])) {
        return FALSE;
      }
    }

    // There can be internal paths that are not rooted, like 'base:/path'.
    if ($url_object->isRouted()) {
     $path = $url_object->getInternalPath();
    }
    else { // Handle base scheme.
      if (strpos($uri = $url_object->toUriString(), 'base:/') === 0 ) {
        $path = $uri[6] === '/' ? substr($uri, 7) : substr($uri, 6);
      }
      else { // Handle unforeseen schemes.
        $path = $uri;
      }
    }

    // Do not include paths that have been already indexed.
    if ($this->batchSettings['remove_duplicates'] && $this->pathProcessed($path)) {
      return FALSE;
    }

    $url_object->setOption('absolute', TRUE);

    $entity = $this->entityHelper->getEntityFromUrlObject($url_object);

    $path_data = [
      'url' => $url_object,
      'lastmod' => !empty($entity) && method_exists($entity, 'getChangedTime')
        ? date_iso8601($entity->getChangedTime())
        : NULL,
      'priority' => isset($entity_settings['priority']) ? $entity_settings['priority'] : NULL,
      'changefreq' => !empty($entity_settings['changefreq']) ? $entity_settings['changefreq'] : NULL,
      'images' => !empty($entity_settings['include_images']) && !empty($entity)
        ? $this->getImages($entity->getEntityTypeId(), $entity->id())
        : [],

      // Additional info useful in hooks.
      'meta' => [
        'path' => $path,
      ]
    ];
    if (!empty($entity)) {
      $path_data['meta']['entity_info'] = [
        'entity_type' => $entity->getEntityTypeId(),
        'id' => $entity->id(),
      ];
    }

    return $path_data;
  }

  /**
   * @inheritdoc
   */
  protected function getBatchIterationElements($menu_name) {

    // Retrieve the expanded tree.
    $tree = $this->menuLinkTree->load($menu_name, new MenuTreeParameters());
    $tree = $this->menuLinkTree->transform($tree, [['callable' => 'menu.default_tree_manipulators:generateIndexAndSort']]);

    $elements = [];
    foreach ($tree as $i => $item) {
      $elements[] = $item->link;
    }
    $elements = array_values($elements);

    if ($this->needsInitialization()) {
      $this->initializeBatch(count($elements));
    }

    return $this->isBatch()
      ? array_slice($elements, $this->context['sandbox']['progress'], $this->batchSettings['batch_process_limit'])
      : $elements;
  }

}
