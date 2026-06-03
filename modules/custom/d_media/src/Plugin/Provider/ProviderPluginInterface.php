<?php

declare(strict_types=1);

namespace Drupal\d_media\Plugin\Provider;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Provides an interface for video-embed provider plugins.
 *
 * @property string $baseUrl
 */
interface ProviderPluginInterface extends PluginInspectionInterface {

  /**
   * Check if the plugin is applicable to the user input.
   *
   * @param string $input
   *   User input to test against the plugin's URL pattern.
   *
   * @return bool
   *   TRUE when the plugin can handle the URL.
   */
  public static function isApplicable(string $input): bool;

  /**
   * Render the embed code as a render array.
   */
  public function renderEmbedCode(): array;

  /**
   * Get the video id from a free-form user input.
   *
   * @return string|false
   *   The video id, or FALSE when the input doesn't match the pattern.
   */
  public static function getIdFromInput(string $input): string|false;

  /**
   * Setter for player settings.
   */
  public function setPlayerSettings(array $settings): void;

  /**
   * Setter for video settings.
   */
  public function setVideoSettings(array $settings): void;

  /**
   * Get the video oEmbed payload.
   *
   * @return object
   *   Data from the oEmbed endpoint.
   */
  public function oEmbedData(): object;

}
