<?php

/**
 * @file
 */

declare(strict_types = 1);

use Drupal\media\Entity\Media;

function _calculate_aspect_ratio(int $height, int $width): float {
  return $height / $width;
}

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

function _get_video_provider(Media $media): string|null {
  $oembed_url = $media->hasField('field_media_oembed_video') && !$media->get('field_media_oembed_video')->isEmpty()
    ? $media->get('field_media_oembed_video')->value
    : NULL;

  if (!$oembed_url) {
    return NULL;
  }

  return match (true) {
    str_contains($oembed_url, 'youtube.com'), str_contains($oembed_url, 'youtu.be') => 'youtube',
    str_contains($oembed_url, 'vimeo.com') => 'vimeo',
    default => NULL,
  };
}

function _get_video_base_url(string $provider): string|null {
  return match($provider) {
    'youtube' => 'https://www.youtube.com/embed/',
    'vimeo' => 'https://player.vimeo.com/video/',
    default => NULL,
  };
}

function _get_player_settings(string $view_mode, string $video_id, string $provider): array {
  $settings = [
    'autoplay' => 0,
    'loop' => 0,
    'controls' => 1,
    'start' => 0,
    'mute' => 0,
  ];

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

function _get_video_classes(string $view_mode): array {
 $classes = ['video-embed'];

  switch ($view_mode) {
    case 'd_tiles_gallery_fullscreen':
    case 'tiles_gallery_fullscreen_featured':
      break;
    default:
      $classes[] = 'video-embed--cover';
      break;
  }

  return $classes;
}
