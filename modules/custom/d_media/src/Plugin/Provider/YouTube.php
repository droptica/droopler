<?php

declare(strict_types=1);

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
  protected string $baseUrl = 'https://www.youtube.com/embed/%s';

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function getIdFromInput(string $input): string|false {
    preg_match('/^https?:\/\/(www\.)?((?!.*list=)youtube\.com\/watch\?.*v=|youtu\.be\/)(?<id>[0-9A-Za-z_-]*)/', $input, $matches);
    return $matches['id'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function oEmbedData(): object {
    $response = @file_get_contents('https://www.youtube.com/oembed?url=' . $this->getInput());
    if ($response === FALSE) {
      return new \stdClass();
    }
    $decoded = json_decode($response);
    return is_object($decoded) ? $decoded : new \stdClass();
  }

  /**
   * Time index (in seconds) parsed from the YouTube URL `t=` parameter.
   */
  protected function getTimeIndex(): int {
    preg_match('/[&\?]t=((?<hours>\d+)h)?((?<minutes>\d+)m)?(?<seconds>\d+)s?/', $this->getInput(), $matches);
    $hours = !empty($matches['hours']) ? (int) $matches['hours'] : 0;
    $minutes = !empty($matches['minutes']) ? (int) $matches['minutes'] : 0;
    $seconds = !empty($matches['seconds']) ? (int) $matches['seconds'] : 0;
    return $hours * 3600 + $minutes * 60 + $seconds;
  }

  /**
   * Closed-caption language preference parsed from the URL.
   *
   * @return string|false
   *   Language code or FALSE when no preference is present.
   */
  protected function getLanguagePreference(): string|false {
    preg_match('/[&\?]hl=(?<language>[a-z\-]*)/', $this->getInput(), $matches);
    return $matches['language'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function constructQuery(): string {
    $query = $this->playerSettings + [
      'start' => $this->getTimeIndex(),
    ];

    $language = $this->getLanguagePreference();
    if ($language !== FALSE) {
      $query['cc_lang_pref'] = $language;
    }

    // YouTube uses `mute`, not `muted`.
    if (isset($query['muted'])) {
      $query['mute'] = $query['muted'];
      unset($query['muted']);
    }

    // Looping requires the playlist param to point at the same video.
    if (isset($query['loop']) && (int) $query['loop'] === 1) {
      $query['playlist'] = $this->getVideoId();
    }

    return http_build_query($query);
  }

}
