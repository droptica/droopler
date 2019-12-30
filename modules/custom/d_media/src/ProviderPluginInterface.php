<?php

namespace Drupal\d_media;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Providers an interface for embed providers.
 *
 * @property string $baseUrl
 */
interface ProviderPluginInterface extends PluginInspectionInterface {

  /**
   * Check if the plugin is applicable to the user input.
   *
   * @param string $input
   *   User input to check if it's a URL for the given provider.
   *
   * @return bool
   *   If the plugin works for the given URL.
   */
  public static function isApplicable($input);

  /**
   * Render embed code.
   *
   * @return mixed
   *   A renderable array of the embed code.
   */
  public function renderEmbedCode();

  /**
   * Get the ID of the video from user input.
   *
   * @param string $input
   *   Input a user would enter into a video field.
   *
   * @return string
   *   The ID in whatever format makes sense for the provider.
   */
  public static function getIdFromInput($input);

  /**
   * Get the fragment part of the video URL from user input.
   *
   * @return string
   *   The fragment in whatever format makes sense for the provider.
   */
  public function getFragmentFromInput();

  /**
   * Get the name of the video.
   *
   * @return string
   *   A name to represent the video for the given plugin.
   */
  public function getName();

  /**
   * Setter for player settings.
   *
   * @param array $settings
   *   An array of settings from formatter for player.
   */
  public function setPlayerSettings(array $settings);

  /**
   * Setter for video settings.
   *
   * @param array $settings
   *   An array of settings from formatter for video.
   */
  public function setVideoSettings(array $settings);

  /**
   * Get the video oembed data.
   *
   * @return \stdClass
   *   Data from the oembed endpoint.
   */
  public function oEmbedData();

}
