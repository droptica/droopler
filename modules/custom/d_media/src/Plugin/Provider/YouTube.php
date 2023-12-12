<?php

declare(strict_types = 1);

namespace Drupal\d_media\Plugin\Provider;

/**
 * A YouTube provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "youtube",
 *   title = @Translation("YouTube")
 * )
 */
class YouTube extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $baseUrl = 'https://www.youtube.com/embed/%s';

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/(www\.)?((?!.*list=)youtube\.com\/watch\?.*v=|youtu\.be\/)(?<id>[0-9A-Za-z_-]*)/', $input, $matches);
    return $matches['id'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function oEmbedData() {
    return (object) json_decode(file_get_contents('https://www.youtube.com/oembed?url=' . $this->getInput()));
  }

  /**
   * Get the time index for when the given video starts.
   *
   * @return int
   *   The time index where the video should start based on the URL.
   */
  protected function getTimeIndex() {
    preg_match('/[&\?]t=((?<hours>\d+)h)?((?<minutes>\d+)m)?(?<seconds>\d+)s?/', $this->getInput(), $matches);

    $hours = !empty($matches['hours']) ? $matches['hours'] : 0;
    $minutes = !empty($matches['minutes']) ? $matches['minutes'] : 0;
    $seconds = !empty($matches['seconds']) ? $matches['seconds'] : 0;

    return $hours * 3600 + $minutes * 60 + $seconds;
  }

  /**
   * Extract the language preference from the URL for use in closed captioning.
   *
   * @return string|false
   *   The language preference if one exists or FALSE if one could not be found.
   */
  protected function getLanguagePreference() {
    preg_match('/[&\?]hl=(?<language>[a-z\-]*)/', $this->getInput(), $matches);
    return $matches['language'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function constructQuery() {
    $query = $this->playerSettings + [
      'start' => $this->getTimeIndex(),
    ];

    // Add language param.
    $language = $this->getLanguagePreference();
    if ($language) {
      $query['cc_lang_pref'] = $language;
    }

    // Youtube accepts mute param, not muted.
    if (isset($query['muted'])) {
      $query['mute'] = $query['muted'];
      unset($query['muted']);
    }

    // If video is supposed to loop then add a playlist param with the video ID.
    if (isset($query['loop']) && $query['loop'] == 1) {
      $query['playlist'] = $this->getVideoId();
    }

    return http_build_query($query);
  }

}
