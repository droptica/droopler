<?php

namespace Drupal\d_media\Service;

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
   * @return \Drupal\d_media\Plugin\Provider\ProviderPluginInterface|bool
   *   The relevant plugin or FALSE on failure.
   */
  public function filterApplicableDefinitions(array $definitions, $user_input);

  /**
   * Load a provider from user input.
   *
   * @param string $input
   *   Input provided from a field.
   *
   * @return \Drupal\d_media\Plugin\Provider\ProviderPluginInterface|bool
   *   The loaded plugin.
   */
  public function loadProviderFromInput($input);

  /**
   * Load a plugin definition from an input.
   *
   * @param string $input
   *   An input string.
   *
   * @return array
   *   A plugin definition.
   */
  public function loadDefinitionFromInput($input);

}
