<?php

declare(strict_types=1);

namespace Drupal\d_update;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Config-comparison service: fingerprints configuration objects.
 *
 * MD5 is intentionally used here as a non-cryptographic fingerprint —
 * hashes are stored verbatim in update hooks and `updates.yml` across many
 * sites. Switching the hash algorithm would invalidate every existing
 * recorded hash, so MD5 stays. Collision-resistance is irrelevant for this
 * use-case.
 */
class ConfigCompare implements ConfigCompareInterface {

  /**
   * Volatile keys excluded from the fingerprint computation.
   *
   * @var string[]
   */
  protected const array IGNORED_KEYS = [
    'uuid',
    'lang',
    'langcode',
    'icon_default',
  ];

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function generateHashFromDatabase(string $config_name): string|false {
    $config_storage = $this->getConfig($config_name);
    if ($config_storage->isNew()) {
      return FALSE;
    }

    $config = $config_storage->getRawData();
    foreach (self::IGNORED_KEYS as $key) {
      unset($config[$key]);
    }

    return md5(serialize($config));
  }

  /**
   * {@inheritdoc}
   */
  public function configExists(string $config_name): bool {
    return !$this->getConfig($config_name)->isNew();
  }

  /**
   * {@inheritdoc}
   */
  public function compare(string $config_name, ?string $hash = NULL): bool {
    if ($hash === NULL || $hash === '') {
      return TRUE;
    }
    return $this->generateHashFromDatabase($config_name) === $hash;
  }

  /**
   * Resolve a config object for the given config name.
   */
  protected function getConfig(string $config_name): ImmutableConfig {
    return $this->configFactory->get($config_name);
  }

}
