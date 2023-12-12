<?php

declare(strict_types = 1);

namespace Drupal\d_media\Plugin\Provider;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Template\Attribute;

/**
 * A base for the provider plugins.
 */
abstract class ProviderPluginBase extends PluginBase implements ProviderPluginInterface {

  /**
   * An array of settings from formatter for player.
   *
   * @var array
   */
  protected $playerSettings = [];

  /**
   * An array of settings from formatter for video.
   *
   * @var array
   */
  protected $videoSettings = [];

  /**
   * Base URL of the provider, with a placeholder for video ID.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * The ID of the video.
   *
   * @var string
   */
  protected $videoId;

  /**
   * The input that caused the embed provider to be selected.
   *
   * @var string
   */
  protected $input;

  /**
   * Array of available image style effects for the spacer element.
   */
  const SCALE_AND_CROP_EFFECTS = [
    'image_scale_and_crop',
    'focal_point_scale_and_crop',
    'image_scale',
  ];

  /**
   * Create a plugin with the given input.
   *
   * @param array $configuration
   *   The configuration of the plugin.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   *
   * @throws \Exception
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!static::isApplicable($configuration['input'])) {
      throw new \Exception('Tried to create a video provider plugin with invalid input.');
    }

    $this->input = $configuration['input'];
    $this->videoId = $this->getIdFromInput($configuration['input']);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input) {
    $id = static::getIdFromInput($input);

    return !empty($id);
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode() {
    $output = [
      '#theme' => 'd_media_video_embed',
      '#attributes' => new Attribute([
        'src' => $this->constructSrc(),
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'data-provider' => $this->getPluginDefinition()['id'],
        'data-aspect-ratio' => $this->calculateAspectRatio(),
        'class' => [
          'video-embed',
        ],
      ]),
    ];

    if (!empty($this->videoSettings['image_style'])) {

      $this->getSpacerAttributes($output);

    }
    if (!empty($this->videoSettings['cover'])) {
      $output['#attributes']->addClass('video-embed--cover');
      $output['#attached']['library'][] = 'd_media/responsive-video';
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function setPlayerSettings(array $settings) {
    $this->playerSettings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setVideoSettings(array $settings) {
    $this->videoSettings = $settings;
  }

  /**
   * Get the ID of the video.
   *
   * @return string
   *   The video ID.
   */
  protected function getVideoId() {
    return $this->videoId;
  }

  /**
   * Get the input which caused this plugin to be selected.
   *
   * @return string
   *   The raw input from the user.
   */
  protected function getInput() {
    return $this->input;
  }

  /**
   * Create query string.
   *
   * @return string
   *   Query string to be added to url.
   */
  protected function constructQuery() {
    return http_build_query($this->playerSettings);
  }

  /**
   * Construct source attribute.
   *
   * @return string
   *   Src attribute.
   */
  protected function constructSrc() {
    $url = sprintf($this->baseUrl, $this->getVideoId());

    $query = $this->constructQuery();
    if (!empty($query)) {
      $url .= "?$query";
    }

    return $url;
  }

  /**
   * Calculate aspect ratio of the video.
   *
   * @return float|int
   *   Aspect ratio.
   */
  protected function calculateAspectRatio() {
    $video_data = $this->oEmbedData();
    return (isset($video_data->height) && isset($video_data->width)) ? $video_data->height / $video_data->width : 1;
  }

  /**
   * Adds spacer attributes to the output based on selected image style.
   *
   * @param array $output
   *   Contains theme and attributes data.
   */
  protected function getSpacerAttributes(array &$output) {
    $imageStyleSetting = $this->videoSettings['image_style'];

    $effects = \Drupal::service('entity_type.manager')
      ->getStorage('image_style')
      ->load($imageStyleSetting)->getEffects()->getConfiguration();
    foreach ($effects as $effect) {
      if (in_array($effect['id'], self::SCALE_AND_CROP_EFFECTS) && !empty($effect['data'])) {
        $output['#spacer_attributes'] = new Attribute([
          'width' => $effect['data']['width'],
          'height' => $effect['data']['height'],
        ]);
      }
    }
  }

}
