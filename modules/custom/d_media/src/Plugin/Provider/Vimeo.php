<?php

namespace Drupal\d_media\Plugin\Provider;

use Drupal\d_media\ProviderPluginBase;

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
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFragmentFromInput() {
    $time_index = $this->getTimeIndex();
    return $time_index ? sprintf('t=%s', $time_index) : '';
  }

  /**
   * {@inheritdoc}
   */
  public function oEmbedData() {
    return (object) json_decode(file_get_contents('http://vimeo.com/api/oembed.json?url=' . $this->getInput()));
  }

  /**
   * Get the time index from the URL.
   *
   * @return string|false
   *   A time index parameter to pass to the frame or FALSE if none is found.
   */
  protected function getTimeIndex() {
    preg_match('/\#t=(?<time_index>(\d+)s)$/', $this->getInput(), $matches);
    return isset($matches['time_index']) ? $matches['time_index'] : FALSE;
  }

}
