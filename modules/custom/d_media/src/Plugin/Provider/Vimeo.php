<?php

declare(strict_types=1);

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
  protected string $baseUrl = 'https://player.vimeo.com/video/%s';

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function getIdFromInput(string $input): string|false {
    preg_match('/^https?:\/\/(www\.)?vimeo.com\/(channels\/[a-zA-Z0-9]*\/)?(?<id>[0-9]*)(\/[a-zA-Z0-9]+)?(\#t=(\d+)s)?$/', $input, $matches);
    return $matches['id'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function oEmbedData(): object {
    $response = @file_get_contents('https://vimeo.com/api/oembed.json?url=' . $this->getInput());
    if ($response === FALSE) {
      return new \stdClass();
    }
    $decoded = json_decode($response);
    return is_object($decoded) ? $decoded : new \stdClass();
  }

  /**
   * Time fragment lifted from the user-supplied URL.
   *
   * @return string|false
   *   Fragment in `t=...s` form or FALSE when no time fragment is present.
   */
  protected function getFragmentFromInput(): string|false {
    $time_index = $this->getTimeIndex();
    return $time_index === FALSE ? FALSE : sprintf('t=%s', $time_index);
  }

  /**
   * Time index extracted from the URL.
   *
   * @return string|false
   *   Time-index parameter (e.g. `30s`) or FALSE when none is found.
   */
  protected function getTimeIndex(): string|false {
    preg_match('/\#t=(?<time_index>(\d+)s)$/', $this->getInput(), $matches);
    return $matches['time_index'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function constructQuery(): string {
    $query = $this->playerSettings;

    // Collapse autoplay+loop+muted into Vimeo's `background=1` to allow
    // multiple Vimeo embeds to autoplay on the same page.
    if (!empty($query['autoplay']) && !empty($query['loop']) && !empty($query['muted'])) {
      unset($query['autoplay'], $query['loop'], $query['muted']);
      $query['background'] = 1;
    }

    return http_build_query($query);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function constructSrc(): string {
    $url = parent::constructSrc();
    $fragment = $this->getFragmentFromInput();
    if ($fragment !== FALSE) {
      $url .= '#' . $fragment;
    }
    return $url;
  }

}
