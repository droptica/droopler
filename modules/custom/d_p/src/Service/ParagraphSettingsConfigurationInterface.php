<?php

namespace Drupal\d_p\Service;

/**
 * Provides an interface for paragraphs settings configuration manager.
 *
 * @package Drupal\d_p\Service
 */
interface ParagraphSettingsConfigurationInterface {

  const ALL_ALLOWED_BUNDLES = 'all';

  /**
   * Loads setting element for the given id.
   *
   * @param string $id
   *   Paragraph setting id.
   *
   * @return array
   *   Paragraph setting configuration.
   */
  public function load(string $id): array;

  /**
   * Loads all existing paragraph settings configurations.
   *
   * @return array[]
   *   Paragraph settings configuration.
   */
  public function loadMultiple(): array;

}
