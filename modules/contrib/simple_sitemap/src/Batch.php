<?php

namespace Drupal\simple_sitemap;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Cache\Cache;

/**
 * Class Batch
 * @package Drupal\simple_sitemap\Batch
 *
 * The services of this class are not injected, as this class looses its state
 * on every method call because of how the batch APi works.
 */
class Batch {

  use StringTranslationTrait;

  /**
   * @var array
   */
  protected $batch;

  /**
   * @var array
   */
  protected $batchSettings;

  const BATCH_TITLE = 'Generating XML sitemap';
  const BATCH_INIT_MESSAGE = 'Initializing batch...';
  const BATCH_ERROR_MESSAGE = 'An error has occurred. This may result in an incomplete XML sitemap.';
  const BATCH_PROGRESS_MESSAGE = 'Processing @current out of @total link types.';
  const REGENERATION_FINISHED_MESSAGE = 'The <a href="@url" target="_blank">XML sitemap</a> has been regenerated.';
  const REGENERATION_FINISHED_ERROR_MESSAGE = 'The sitemap generation finished with an error.';

  /**
   * Batch constructor.
   */
  public function __construct() {
    $this->batch = [
      'title' => $this->t(self::BATCH_TITLE),
      'init_message' => $this->t(self::BATCH_INIT_MESSAGE),
      'error_message' => $this->t(self::BATCH_ERROR_MESSAGE),
      'progress_message' => $this->t(self::BATCH_PROGRESS_MESSAGE),
      'operations' => [],
      'finished' => [__CLASS__, 'finishGeneration'],
    ];
  }

  /**
   * @param array $batch_settings
   */
  public function setBatchSettings(array $batch_settings) {
    $this->batchSettings = $batch_settings;
  }

  /**
   * Starts the batch process depending on where it was requested from.
   */
  public function start() {
    switch ($this->batchSettings['from']) {

      case 'form':
        // Start batch process.
        batch_set($this->batch);
        return TRUE;

      case 'drush':
        // Start drush batch process.
        batch_set($this->batch);

        // See https://www.drupal.org/node/638712
        $this->batch =& batch_get();
        $this->batch['progressive'] = FALSE;

        drush_log($this->t(self::BATCH_INIT_MESSAGE), 'status');
        drush_backend_batch_process();
        return TRUE;

      case 'backend':
        // Start backend batch process.
        batch_set($this->batch);

        // See https://www.drupal.org/node/638712
        $this->batch =& batch_get();
        $this->batch['progressive'] = FALSE;

        // todo: Does not take advantage of batch API and eventually runs out of memory on very large sites. Use queue API instead?
        batch_process();
        return TRUE;

      case 'nobatch':
        // Call each batch operation the way the Drupal batch API would do, but
        // within one process (so in fact not using batch API here, just
        // mimicking it to avoid code duplication).
        $context = [];
        foreach ($this->batch['operations'] as $i => $operation) {
          $operation[1][] = &$context;
          call_user_func_array($operation[0], $operation[1]);
        }
        $this->finishGeneration(TRUE, !empty($context['results']) ? $context['results'] : [], []);
        return TRUE;
    }
    return FALSE;
  }

  /**
   * Adds an operation to the batch.
   *
   * @param $plugin_id
   * @param array|null $data_sets
   */
  public function addOperation($plugin_id, $data_sets = NULL) {
    $this->batch['operations'][] = [
      __CLASS__ . '::generate', [$plugin_id, $data_sets, $this->batchSettings],
    ];
  }

  /**
   * Batch callback function which generates URLs.
   *
   * @param $plugin_id
   * @param array|null $data_sets
   * @param array $batch_settings
   * @param $context
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */
  public static function generate($plugin_id, $data_sets, array $batch_settings, &$context) {
    \Drupal::service('plugin.manager.simple_sitemap.url_generator')
      ->createInstance($plugin_id)
      ->setContext($context)
      ->setBatchSettings($batch_settings)
      ->generate($data_sets);
  }

  /**
   * Callback function called by the batch API when all operations are finished.
   *
   * @param $success
   * @param $results
   * @param $operations
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */
  public static function finishGeneration($success, $results, $operations) {
    if ($success) {
      $remove_sitemap = empty($results['chunk_count']);
      if (!empty($results['generate']) || $remove_sitemap) {
        \Drupal::service('simple_sitemap.sitemap_generator')
          ->setSettings(['excluded_languages' => \Drupal::service('simple_sitemap.generator')->getSetting('excluded_languages', [])])
          ->generateSitemap(!empty($results['generate']) ? $results['generate'] : [], $remove_sitemap);
      }
      Cache::invalidateTags(['simple_sitemap']);
      \Drupal::service('simple_sitemap.logger')->m(self::REGENERATION_FINISHED_MESSAGE,
        ['@url' => $GLOBALS['base_url'] . '/sitemap.xml'])
//        ['@url' => $this->sitemapGenerator->getCustomBaseUrl() . '/sitemap.xml']) //todo: Use actual base URL for message.
        ->display('status')
        ->log('info');
    }
    else {
      \Drupal::service('simple_sitemap.logger')->m(self::REGENERATION_FINISHED_ERROR_MESSAGE)
        ->display('error', 'administer sitemap settings')
        ->log('error');
    }
  }

}
