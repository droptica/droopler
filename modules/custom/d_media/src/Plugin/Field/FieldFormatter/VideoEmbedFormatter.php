<?php

namespace Drupal\d_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\MediaType;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\media\Source\OEmbedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'video_embed_formatter' formatter.
 *
 * @internal
 *   This is an internal part of the oEmbed system and should only be used by
 *   oEmbed-related code in Drupal core.
 *
 * @FieldFormatter(
 *   id = "d_video_embed",
 *   label = @Translation("Video embed"),
 *   field_types = {
 *     "string",
 *   },
 * )
 */
class VideoEmbedFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $defaults = parent::defaultSettings();

    $video_settings = (new self)->getVideoSettings();
    foreach ($video_settings as $setting_name => $setting) {
      $defaults[$setting_name] = 0;
    }

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $video_settings = $this->getVideoSettings();
    foreach ($video_settings as $setting_name => $setting) {
      $form[$setting_name] = [
        '#type' => 'checkbox',
        '#title' => $setting['label'],
        '#description' => $setting['description'],
        '#default_value' => $this->getSetting($setting_name),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $video_settings = $this->getVideoSettings();
    foreach ($video_settings as $setting_name => $setting) {
      $summary[] = $setting['label'] . ': ' . $this->settingState($this->getSetting($setting_name));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getTargetEntityTypeId() !== 'media') {
      return FALSE;
    }

    if (parent::isApplicable($field_definition)) {
      $media_type = $field_definition->getTargetBundle();

      if ($media_type) {
        $media_type = MediaType::load($media_type);
        return $media_type && $media_type->getSource() instanceof OEmbedInterface;
      }
    }
    return FALSE;
  }

  protected function getVideoSettings() {
    return [
      'autoplay' => [
        'label' => $this->t('Autoplay'),
        'description' => $this->t('Should video start playing automatically.'),
      ],
      'loop' => [
        'label' => $this->t('Loop'),
        'description' => $this->t('Should video repeat after it ends.'),
      ],
      'controls' => [
        'label' => $this->t('Controls'),
        'description' => $this->t('Should video display controls such as play/pause and play bar.'),
      ],
      'muted' => [
        'label' => $this->t('Muted'),
        'description' => $this->t('Should video be muted.'),
      ],
    ];
  }

  protected function settingState($value) {
    return empty($value) ? $this->t('disabled') : $this->t('enabled');
  }

}
