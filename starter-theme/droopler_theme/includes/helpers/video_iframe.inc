<?php

/**
 * @file
 * Helper functions for embedding responsive video with different view modes and settings.
 */

declare(strict_types = 1);

use Drupal\media\Entity\Media;

/**
 * Calculates the aspect ratio of a video based on its dimensions.
 *
 * @param int $height
 *   The height of the video.
 * @param int $width
 *   The width of the video.
 *
 * @return float
 *   The calculated aspect ratio as a float.
 */
function _calculate_aspect_ratio(int $height, int $width): float {
  return $height / $width;
}

/**
 * Generates the full video source URL with player settings based on view mode.
 *
 * @param Media $media
 *   The media entity representing the video.
 * @param string $view_mode
 *   The view mode to determine player settings.
 *
 * @return string
 *   The full URL of the embedded video or an empty string if parameters are missing.
 */
function _get_video_src(Media $media, string $view_mode): string {
  $provider = _get_video_provider($media);
  if (!$provider) {
    return '';
  }

  $video_base_url = _get_video_base_url($provider);
  $video_id = _get_video_id($media);
  if (!$video_base_url || !$video_id) {
    return '';
  }

  $player_settings = http_build_query(_get_player_settings($view_mode, $video_id, $provider));
  if (!$player_settings) {
    return '';
  }

  return $video_base_url . $video_id . '?' . $player_settings;
}

/**
 * Extracts the video ID from the oEmbed URL for YouTube or Vimeo.
 *
 * @param Media $media
 *   The media entity representing the video.
 *
 * @return string|null
 *   The extracted video ID, or NULL if not found or if the media type isn't a video.
 */
function _get_video_id(Media $media): string|null {
  if ($media->bundle() !== 'd_video') {
    return NULL;
  }

  $oembed_url = $media->hasField('field_media_oembed_video') && !$media->get('field_media_oembed_video')->isEmpty()
    ? $media->get('field_media_oembed_video')->value
    : NULL;

  if (!$oembed_url) {
    return NULL;
  }

  // Match and return the video ID based on the video provider (YouTube or Vimeo).
  return match (true) {
    str_contains($oembed_url, 'youtube.com') =>
      (preg_match('/[\\?\\&]v=([^\\?\\&]+)/', $oembed_url, $matches) && !empty($matches[1]))
        ? $matches[1]
        : NULL,
    str_contains($oembed_url, 'youtu.be') =>
      (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $oembed_url, $matches) && !empty($matches[1]))
        ? $matches[1]
        : NULL,
    str_contains($oembed_url, 'vimeo.com') =>
      (preg_match('/vimeo\.com\/(\d+)/', $oembed_url, $matches) && !empty($matches[1]))
        ? $matches[1]
        : NULL,
    default => NULL,
  };
}

/**
 * Determines the video provider (YouTube or Vimeo) based on the oEmbed URL.
 *
 * @param Media $media
 *   The media entity representing the video.
 *
 * @return string|null
 *   The video provider ('youtube' or 'vimeo') or NULL if not recognized.
 */
function _get_video_provider(Media $media): string|null {
  $oembed_url = $media->hasField('field_media_oembed_video') && !$media->get('field_media_oembed_video')->isEmpty()
    ? $media->get('field_media_oembed_video')->value
    : NULL;

  if (!$oembed_url) {
    return NULL;
  }

  // Determine provider based on URL structure.
  return match (true) {
    str_contains($oembed_url, 'youtube.com'), str_contains($oembed_url, 'youtu.be') => 'youtube',
    str_contains($oembed_url, 'vimeo.com') => 'vimeo',
    default => NULL,
  };
}

/**
 * Provides the base URL for embedding based on the video provider.
 *
 * @param string $provider
 *   The video provider ('youtube' or 'vimeo').
 *
 * @return string|null
 *   The base URL for embedding the video or NULL if provider is unrecognized.
 */
function _get_video_base_url(string $provider): string|null {
  return match($provider) {
    'youtube' => 'https://www.youtube.com/embed/',
    'vimeo' => 'https://player.vimeo.com/video/',
    default => NULL,
  };
}

/**
 * Configures player settings based on the view mode and video provider.
 *
 * @param string $view_mode
 *   The view mode used for display.
 * @param string $video_id
 *   The ID of the video.
 * @param string $provider
 *   The video provider ('youtube' or 'vimeo').
 *
 * @return array
 *   An associative array of player settings.
 */
function _get_player_settings(string $view_mode, string $video_id, string $provider): array {
  $settings = [
    'autoplay' => 0,
    'loop' => 0,
    'controls' => 1,
    'start' => 0,
    'mute' => 0,
  ];

  // Adjust settings based on the view mode.
  switch ($view_mode) {
    case 'gallery_item':
    case 'd_tiles_gallery_fullscreen':
    case 'tiles_gallery_fullscreen_featured':
      break;
    case 'counter':
      $settings['autoplay'] = 1;
      $settings['mute'] = 1;
      break;
    case 'd_banner':
    case 'd_responsive_background':
    case 'd_responsive_background_overlay':
      $settings['autoplay'] = 1;
      $settings['loop'] = 1;
      $settings['controls'] = 0;
      $settings['mute'] = 1;
      $settings['playlist'] = $video_id;
      break;
  }

  // Specific settings for Vimeo videos.
  if ($provider === 'vimeo') {
    unset(
      $settings['autoplay'],
      $settings['loop'],
      $settings['muted']
    );
    $settings['background'] = 1;
  }

  return $settings;
}

/**
 * Assigns CSS classes to the video based on the view mode.
 *
 * @param string $view_mode
 *   The view mode for the video display.
 *
 * @return array
 *   An array of CSS class names.
 */
function _get_video_classes(string $view_mode): array {
  $classes = ['video-embed'];

  // Add specific classes based on the view mode.
  switch ($view_mode) {
    case 'd_tiles_gallery_fullscreen':
    case 'tiles_gallery_fullscreen_featured':
      break;
    case 'd_blog_image':
      $classes[] = 'video-embed--blog';
      break;
    case 'side_image':
      $classes[] = 'img-fluid';
      break;
    default:
      $classes[] = 'video-embed--cover';
      break;
  }

  return $classes;
}
