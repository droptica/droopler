<?php

declare(strict_types = 1);

namespace Drupal\d_media\Service;

use Drupal\d_media\Plugin\Provider\ProviderPluginInterface;

/**
 * Interface for the class that gathers the provider plugins.
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
   * @return \Drupal\d_media\Plugin\Provider\ProviderPluginInterface|false
   *   The relevant plugin or FALSE on failure.
   */
  public function filterApplicableDefinitions(array $definitions, $user_input): ProviderPluginInterface|false;

  /**
   * Load a provider from user input.
   *
   * @param string $input
   *   Input provided from a field.
   *
   * @return \Drupal\d_media\Plugin\Provider\ProviderPluginInterface|false
   *   The loaded plugin or FALSE on failure.
   */
  public function loadProviderFromInput($input): ProviderPluginInterface|false;

  /**
   * Load a plugin definition from an input.
   *
   * @param string $input
   *   An input string.
   *
   * @return \Drupal\d_media\Plugin\Provider\ProviderPluginInterface|false
   *   The relevant plugin or FALSE on failure.
   */
  public function loadDefinitionFromInput($input): ProviderPluginInterface|false;

}
