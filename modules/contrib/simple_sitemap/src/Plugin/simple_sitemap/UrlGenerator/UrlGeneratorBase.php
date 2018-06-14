<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGeneratorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Url;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\SitemapGenerator;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\Language;

/**
 * Class UrlGeneratorBase
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 */
abstract class UrlGeneratorBase extends UrlGeneratorPluginBase implements UrlGeneratorInterface {

  const ANONYMOUS_USER_ID = 0;
  const PROCESSING_PATH_MESSAGE = 'Processing path #@current out of @max: @path';

  /**
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $generator;

  /**
   * @var \Drupal\simple_sitemap\SitemapGenerator
   */
  protected $sitemapGenerator;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Language\LanguageInterface[]
   */
  protected $languages;

  /**
   * @var string
   */
  protected $defaultLanguageId;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\simple_sitemap\Logger
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $anonUser;

  /**
   * @var array
   */
  protected $context;

  /**
   * @var array
   */
  protected $batchSettings;

  /**
   * @var \Drupal\simple_sitemap\EntityHelper
   */
  protected $entityHelper;

  /**
   * UrlGeneratorBase constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\SitemapGenerator $sitemap_generator
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\simple_sitemap\Logger $logger
   * @param \Drupal\simple_sitemap\EntityHelper $entityHelper
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
    EntityHelper $entityHelper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->generator = $generator;
    $this->sitemapGenerator = $sitemap_generator;
    $this->languageManager = $language_manager;
    $this->languages = $language_manager->getLanguages();
    $this->defaultLanguageId = $language_manager->getDefaultLanguage()->getId();
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->entityHelper = $entityHelper;
    $this->anonUser = $this->entityTypeManager->getStorage('user')
      ->load(self::ANONYMOUS_USER_ID);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.sitemap_generator'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('simple_sitemap.logger'),
      $container->get('simple_sitemap.entity_helper')
    );
  }

  /**
   * @param $context
   * @return $this
   */
  public function setContext(&$context) {
    $this->context = &$context;
    return $this;
  }

  /**
   * @param array $batch_settings
   * @return $this
   */
  public function setBatchSettings(array $batch_settings) {
    $this->batchSettings = $batch_settings;
    return $this;
  }

  /**
   * @return bool
   */
  protected function isBatch() {
    return $this->batchSettings['from'] !== 'nobatch';
  }

  protected function getProcessedElements() {
    return !empty($this->context['results']['processed_paths'])
      ? $this->context['results']['processed_paths']
      : [];
  }

  protected function addProcessedElement($path) {
    $this->context['results']['processed_paths'][] = $path;
  }

  protected function setProcessedElements($elements) {
    $this->context['results']['processed_elements'] = $elements;
  }

  protected function getBatchResults() {
    return !empty($this->context['results']['generate'])
      ? $this->context['results']['generate']
      : [];
  }

  protected function addBatchResult($result) {
    $this->context['results']['generate'][] = $result;
  }

  protected function setBatchResults($results) {
    $this->context['results']['generate'] = $results;
  }

  protected function getChunkCount() {
    return !empty($this->context['results']['chunk_count'])
      ? $this->context['results']['chunk_count']
      : 0;
  }

  protected function setChunkCount($chunk_count) {
    $this->context['results']['chunk_count'] = $chunk_count;
  }

  /**
   * @param string $path
   * @return bool
   */
  protected function pathProcessed($path) {
    if (in_array($path, $this->getProcessedElements())) {
      return TRUE;
    }
    $this->addProcessedElement($path);
    return FALSE;
  }

  /**
   * @param array $path_data
   */
  protected function addUrl(array $path_data) {
    if ($path_data['url'] instanceof Url) {
      $url_object = $path_data['url'];
      unset($path_data['url']);
      $this->addUrlVariants($path_data, $url_object);
    }
    else {
      $this->addBatchResult($path_data);
    }
  }

  /**
   * @param Url $url_object
   * @param array $path_data
   */
  protected function addUrlVariants(array $path_data, Url $url_object) {

    if (!$url_object->isRouted()) {
      // Not a routed URL, including only default variant.
      $alternate_urls = $this->getAlternateUrlsForDefaultLanguage($url_object);
    }
    elseif ($this->batchSettings['skip_untranslated']
      && ($entity = $this->entityHelper->getEntityFromUrlObject($url_object)) instanceof ContentEntityBase) {
      $translation_languages = $entity->getTranslationLanguages();
      if (isset($translation_languages[Language::LANGCODE_NOT_SPECIFIED])
        || isset($translation_languages[Language::LANGCODE_NOT_APPLICABLE])) {
        // Content entity's language is unknown, including only default variant.
        $alternate_urls = $this->getAlternateUrlsForDefaultLanguage($url_object);
      }
      else {
        // Including only translated variants of content entity.
        $alternate_urls = $this->getAlternateUrlsForTranslatedLanguages($entity, $url_object);
      }
    }
    else {
      // Not a content entity or including all untranslated variants.
      $alternate_urls = $this->getAlternateUrlsForAllLanguages($url_object);
    }

    foreach ($alternate_urls as $langcode => $url) {
      $this->addBatchResult(
        $path_data + [
          'langcode' => $langcode, 'url' => $url, 'alternate_urls' => $alternate_urls
        ]
      );
    }
  }

  protected function getAlternateUrlsForDefaultLanguage($url_object) {
    $alternate_urls = [];
    if ($url_object->access($this->anonUser)) {
      $url_object->setOption('language', $this->languages[$this->defaultLanguageId]);
      $alternate_urls[$this->defaultLanguageId] = $this->replaceBaseUrlWithCustom($url_object->toString());
    }
    return $alternate_urls;
  }

  protected function getAlternateUrlsForTranslatedLanguages($entity, $url_object) {
    $alternate_urls = [];
    foreach ($entity->getTranslationLanguages() as $language) {
      if (!isset($this->batchSettings['excluded_languages'][$language->getId()]) || $language->isDefault()) {
        $translation = $entity->getTranslation($language->getId());
        if ($translation->access('view', $this->anonUser)) {
          $url_object->setOption('language', $language);
          $alternate_urls[$language->getId()] = $this->replaceBaseUrlWithCustom($url_object->toString());
        }
      }
    }
    return $alternate_urls;
  }

  protected function getAlternateUrlsForAllLanguages($url_object) {
    $alternate_urls = [];
    if ($url_object->access($this->anonUser)) {
      foreach ($this->languages as $language) {
        if (!isset($this->batchSettings['excluded_languages'][$language->getId()]) || $language->isDefault()) {
          $url_object->setOption('language', $language);
          $alternate_urls[$language->getId()] = $this->replaceBaseUrlWithCustom($url_object->toString());
        }
      }
    }
    return $alternate_urls;
  }

  /**
   * @return bool
   */
  protected function needsInitialization() {
    return empty($this->context['sandbox']);
  }

  /**
   * @param $max
   */
  protected function initializeBatch($max) {
    $this->setBatchResults($this->getBatchResults());
    $this->setChunkCount($this->getChunkCount());
    $this->setProcessedElements($this->getProcessedElements());

    // Initialize sandbox for the batch process.
    if ($this->isBatch()) {
      $this->context['sandbox']['progress'] = 0;
      $this->context['sandbox']['current_id'] = 0;
      $this->context['sandbox']['max'] = $max;
      $this->context['sandbox']['finished'] = 0;
    }
  }

  /**
   * @param $id
   */
  protected function setCurrentId($id) {
    if ($this->isBatch()) {
      $this->context['sandbox']['progress']++;
      $this->context['sandbox']['current_id'] = $id;
    }
  }

  /**
   *
   */
  protected function processSegment() {
    if ($this->isBatch()) {
      $this->setProgressInfo();
    }

    if (!empty($max_links = $this->batchSettings['max_links'])
      && count($this->getBatchResults()) >= $max_links) {

      foreach (array_chunk($this->getBatchResults(), $max_links) as $chunk_links) {

        if (count($chunk_links) == $max_links) {

          // Generate sitemap.
          $this->sitemapGenerator
            ->setSettings(['excluded_languages' => $this->batchSettings['excluded_languages']])
            ->generateSitemap($chunk_links, empty($this->getChunkCount()));

          // Update chunk count info.
          $this->setChunkCount(empty($this->getChunkCount()) ? 1 : ($this->getChunkCount() + 1));

          // Remove links from result array that have been generated.
          $this->setBatchResults(array_slice($this->getBatchResults(), count($chunk_links)));
        }
      }
    }
  }

  protected function setProgressInfo() {
    if ($this->context['sandbox']['progress'] != $this->context['sandbox']['max']) {

      // Provide progress info to the batch API.
      $this->context['finished'] = $this->context['sandbox']['progress'] / $this->context['sandbox']['max'];

      // Add processing message after finishing every batch segment.
      $this->setProcessingBatchMessage();
    }
  }

  protected function setProcessingBatchMessage() {
    $results = $this->getBatchResults();
    end($results);
    if (!empty($path = $results[key($results)]['meta']['path'])) {
      $this->context['message'] = $this->t(self::PROCESSING_PATH_MESSAGE, [
        '@current' => $this->context['sandbox']['progress'],
        '@max' => $this->context['sandbox']['max'],
        '@path' => HTML::escape($path),
      ]);
    }
  }

  /**
   * @param string $url
   * @return string
   */
  protected function replaceBaseUrlWithCustom($url) {
    return !empty($this->batchSettings['base_url'])
      ? str_replace($GLOBALS['base_url'], $this->batchSettings['base_url'], $url)
      : $url;
  }

  /**
   * @param mixed $elements
   * @return array
   */
  protected function getBatchIterationElements($elements) {
    if ($this->needsInitialization()) {
      $this->initializeBatch(count($elements));
    }

    return $this->isBatch()
      ? array_slice($elements, $this->context['sandbox']['progress'], $this->batchSettings['batch_process_limit'])
      : $elements;
  }

  /**
   * @return array
   */
  abstract public function getDataSets();

  /**
   * @param $data_set
   * @return array
   */
  abstract protected function processDataSet($data_set);

  /**
   * Called by batch.
   *
   * @param array|null $data_sets
   */
  public function generate($data_sets = NULL) {
    $data_sets = NULL !== $data_sets ? $data_sets : $this->getDataSets();
    foreach ($this->getBatchIterationElements($data_sets) as $id => $data_set) {
      $this->setCurrentId($id);
      $path_data = $this->processDataSet($data_set);
      if (!$path_data) {
        continue;
      }
      $this->addUrl($path_data);
    }
    $this->processSegment();
  }

  /**
   * @param $entity_type_name
   * @param $entity_id
   * @return array
   */
  protected function getImages($entity_type_name, $entity_id) {
    $images = [];
    foreach ($this->entityHelper->getEntityImageUrls($entity_type_name, $entity_id) as $url) {
      $images[]['path'] = $this->replaceBaseUrlWithCustom($url);
    }
    return $images;
  }
}
