<?php

declare(strict_types=1);

namespace Drupal\d_media\Service;

use Drupal\d_media\Plugin\Provider\ProviderPluginInterface;

/**
 * Interface for the class that gathers the video-embed provider plugins.
 */
interface ProviderManagerInterface {

  /**
   * Get the provider applicable to the given user input.
   *
   * @param array $definitions
   *   A list of definitions to test against.
   * @param string $user_input
   *   The user input to test against the plugins.
   *
   * @return array<string, mixed>|false
   *   The matching plugin definition or FALSE if none applies.
   */
  public function filterApplicableDefinitions(array $definitions, string $user_input): array|false;

  /**
   * Load a provider from a free-form user input.
   *
   * @return \Drupal\d_media\Plugin\Provider\ProviderPluginInterface|false
   *   The loaded plugin or FALSE if no provider matched.
   */
  public function loadProviderFromInput(string $input): ProviderPluginInterface|false;

  /**
   * Load a plugin definition from a free-form input.
   *
   * @return array<string, mixed>|false
   *   The plugin definition or FALSE if none matched.
   */
  public function loadDefinitionFromInput(string $input): array|false;

}
