<?php

declare(strict_types=1);

namespace Drupal\d_media\Plugin\Provider;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for video-embed provider plugins.
 */
abstract class ProviderPluginBase extends PluginBase implements ProviderPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Image-style effect ids that expose width/height as data, used for spacers.
   *
   * @var string[]
   */
  protected const array SCALE_AND_CROP_EFFECTS = [
    'image_scale_and_crop',
    'focal_point_scale_and_crop',
    'image_scale',
  ];

  /**
   * Player settings provided by the formatter.
   *
   * @var array<string, mixed>
   */
  protected array $playerSettings = [];

  /**
   * Video settings provided by the formatter.
   *
   * @var array<string, mixed>
   */
  protected array $videoSettings = [];

  /**
   * Base URL of the provider, with a `%s` placeholder for the video id.
   */
  protected string $baseUrl;

  /**
   * The id of the video extracted from the user input.
   */
  protected string $videoId;

  /**
   * The original input that caused the embed provider to be selected.
   */
  protected string $input;

  /**
   * Constructs a new provider plugin instance.
   *
   * @throws \InvalidArgumentException
   *   When the configured input doesn't match the plugin's URL pattern.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!static::isApplicable($configuration['input'])) {
      throw new \InvalidArgumentException('Tried to create a video provider plugin with invalid input.');
    }

    $this->input = (string) $configuration['input'];
    $id = static::getIdFromInput($this->input);
    $this->videoId = $id === FALSE ? '' : $id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    // @phpstan-ignore-next-line Drupal uses late static binding for plugin factory.
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(string $input): bool {
    return !empty(static::getIdFromInput($input));
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode(): array {
    $output = [
      '#theme' => 'd_media_video_embed',
      '#attributes' => new Attribute([
        'src' => $this->constructSrc(),
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'data-provider' => $this->getPluginDefinition()['id'],
        'data-aspect-ratio' => $this->calculateAspectRatio(),
        'class' => ['video-embed'],
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
  public function setPlayerSettings(array $settings): void {
    $this->playerSettings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setVideoSettings(array $settings): void {
    $this->videoSettings = $settings;
  }

  /**
   * Get the id of the video.
   */
  protected function getVideoId(): string {
    return $this->videoId;
  }

  /**
   * Get the original user input that caused the plugin to be selected.
   */
  protected function getInput(): string {
    return $this->input;
  }

  /**
   * Build the query string for the embed URL.
   */
  protected function constructQuery(): string {
    return http_build_query($this->playerSettings);
  }

  /**
   * Build the `src` attribute for the embed iframe.
   */
  protected function constructSrc(): string {
    $url = sprintf($this->baseUrl, $this->getVideoId());

    $query = $this->constructQuery();
    if ($query !== '') {
      $url .= '?' . $query;
    }

    return $url;
  }

  /**
   * Aspect ratio (height ÷ width) for the embed; defaults to 1 when unknown.
   */
  protected function calculateAspectRatio(): float|int {
    $video_data = $this->oEmbedData();
    if (!isset($video_data->height, $video_data->width)) {
      return 1;
    }
    if (!is_numeric($video_data->height) || !is_numeric($video_data->width) || (float) $video_data->width === 0.0) {
      return 1;
    }
    return $video_data->height / $video_data->width;
  }

  /**
   * Attach spacer attributes (width / height) sourced from the image style.
   */
  protected function getSpacerAttributes(array &$output): void {
    $imageStyleSetting = $this->videoSettings['image_style'];

    /** @var \Drupal\image\ImageStyleInterface|null $image_style */
    $image_style = $this->entityTypeManager
      ->getStorage('image_style')
      ->load($imageStyleSetting);
    if ($image_style === NULL) {
      return;
    }

    foreach ($image_style->getEffects()->getConfiguration() as $effect) {
      if (in_array($effect['id'], self::SCALE_AND_CROP_EFFECTS, TRUE) && !empty($effect['data'])) {
        $output['#spacer_attributes'] = new Attribute([
          'width' => $effect['data']['width'],
          'height' => $effect['data']['height'],
        ]);
      }
    }
  }

}
