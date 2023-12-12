<?php

declare(strict_types = 1);

namespace Drupal\d_media\Plugin\Provider;

/**
 * A Vimeo provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "vimeo",
 *   title = @Translation("Vimeo")
 * )
 */
class Vimeo extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $baseUrl = 'https://player.vimeo.com/video/%s';

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/(www\.)?vimeo.com\/(channels\/[a-zA-Z0-9]*\/)?(?<id>[0-9]*)(\/[a-zA-Z0-9]+)?(\#t=(\d+)s)?$/', $input, $matches);
    return $matches['id'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function oEmbedData() {
    return (object) json_decode(file_get_contents('http://vimeo.com/api/oembed.json?url=' . $this->getInput()));
  }

  /**
   * Get the fragment part of the video URL from user input.
   *
   * @return string|false
   *   The fragment part of URL which hold time in seconds.
   */
  protected function getFragmentFromInput() {
    $time_index = $this->getTimeIndex();
    return $time_index ? sprintf('t=%s', $time_index) : FALSE;
  }

  /**
   * Get the time index from the URL.
   *
   * @return string|false
   *   A time index parameter to pass to the frame or FALSE if none is found.
   */
  protected function getTimeIndex() {
    preg_match('/\#t=(?<time_index>(\d+)s)$/', $this->getInput(), $matches);
    return $matches['time_index'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function constructQuery() {
    $query = $this->playerSettings;

    // Add a background param handled by Vimeo.
    // It allows for multiple Vimeo embeds to autoplay.
    if (!empty($query['autoplay']) && !empty($query['loop']) && !empty($query['muted'])) {
      unset($query['autoplay']);
      unset($query['loop']);
      unset($query['muted']);
      $query['background'] = 1;
    }

    return http_build_query($query);
  }

  /**
   * {@inheritdoc}
   */
  protected function constructSrc() {
    $url = parent::constructSrc();

    $fragment = $this->getFragmentFromInput();
    if ($fragment) {
      $url .= '#' . $fragment;
    }

    return $url;
  }

}
