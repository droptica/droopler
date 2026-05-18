<?php

declare(strict_types=1);

namespace Drupal\d_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\d_media\Service\ProviderManagerInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media\Plugin\media\Source\OEmbedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'd_video_embed' formatter.
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
   * Name for the video settings in the formatter.
   */
  public const string VIDEO_SETTINGS_CONFIG_NAME = 'video_settings';

  /**
   * Name for the player settings in the formatter.
   */
  public const string PLAYER_SETTINGS_CONFIG_NAME = 'player_settings';

  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected readonly ProviderManagerInterface $providerManager,
    protected readonly EntityStorageInterface $imageStyleStorage,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    // @phpstan-ignore-next-line Drupal uses late static binding for plugin factory pattern.
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('d_media.video_provider_manager'),
      $container->get('entity_type.manager')->getStorage('image_style'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    foreach ($items as $delta => $item) {
      $value = $item->value;
      if (empty($value)) {
        continue;
      }

      $provider = $this->providerManager->loadProviderFromInput($value);
      if ($provider === FALSE) {
        continue;
      }

      $provider->setPlayerSettings($this->getSetting(self::PLAYER_SETTINGS_CONFIG_NAME));
      $provider->setVideoSettings($this->getSetting(self::VIDEO_SETTINGS_CONFIG_NAME));
      $element[$delta] = $provider->renderEmbedCode();
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return parent::defaultSettings() + [
      self::PLAYER_SETTINGS_CONFIG_NAME => [
        'autoplay' => 0,
        'loop' => 0,
        'controls' => 0,
        'muted' => 0,
      ],
      self::VIDEO_SETTINGS_CONFIG_NAME => [
        'cover' => 0,
        'image_style' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);

    $form[self::PLAYER_SETTINGS_CONFIG_NAME] = [
      '#type' => 'details',
      '#title' => $this->t('Player settings'),
      '#tree' => TRUE,
    ];
    $this->addFormSettings(self::PLAYER_SETTINGS_CONFIG_NAME, $form);

    $form[self::VIDEO_SETTINGS_CONFIG_NAME] = [
      '#type' => 'details',
      '#title' => $this->t('Video settings'),
      '#tree' => TRUE,
    ];
    $this->addFormSettings(self::VIDEO_SETTINGS_CONFIG_NAME, $form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Player settings');
    $this->addSettingsSummary(self::PLAYER_SETTINGS_CONFIG_NAME, $summary);

    $summary[] = $this->t('Video settings');
    $this->addSettingsSummary(self::VIDEO_SETTINGS_CONFIG_NAME, $summary);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    if ($field_definition->getTargetEntityTypeId() !== 'media') {
      return FALSE;
    }
    if (!parent::isApplicable($field_definition)) {
      return FALSE;
    }
    $media_bundle = $field_definition->getTargetBundle();
    if ($media_bundle === NULL) {
      return FALSE;
    }
    $media_type = MediaType::load($media_bundle);
    return $media_type !== NULL && $media_type->getSource() instanceof OEmbedInterface;
  }

  /**
   * Add fields with formatter settings to the form.
   */
  protected function addFormSettings(string $type, array &$form): void {
    $settings_values = $this->getSetting($type);
    foreach ($this->getSettingsDefinitions($type) as $setting_name => $setting) {
      if (!isset($setting['#type']) || $setting['#type'] === 'checkbox') {
        $form[$type][$setting_name] = [
          '#type' => 'checkbox',
          '#title' => $setting['#title'],
          '#description' => $setting['description'],
          '#default_value' => $settings_values[$setting_name],
        ];
        continue;
      }
      $form[$type][$setting_name] = $setting;
    }
  }

  /**
   * Add summary lines for a settings group.
   */
  protected function addSettingsSummary(string $type, array &$summary): void {
    $settings_values = $this->getSetting($type);
    foreach ($this->getSettingsDefinitions($type) as $setting_name => $setting) {
      $summary[] = $setting['#title'] . ': ' . $this->settingState($settings_values[$setting_name]);
    }
  }

  /**
   * Get settings definitions (name, label, descriptions, …) for a group.
   *
   * @param string $type
   *   Settings group key, or empty string to return all groups.
   *
   * @return array<string, mixed>
   *   Settings definitions for the requested group, or the full structure
   *   when `$type` is empty.
   */
  protected function getSettingsDefinitions(string $type = ''): array {
    $settings = [
      self::PLAYER_SETTINGS_CONFIG_NAME => [
        'autoplay' => [
          '#title' => $this->t('Autoplay'),
          'description' => $this->t('Should video start playing automatically.'),
        ],
        'loop' => [
          '#title' => $this->t('Loop'),
          'description' => $this->t('Should video repeat after it ends.'),
        ],
        'controls' => [
          '#title' => $this->t('Controls'),
          'description' => $this->t('Should video display controls such as play/pause and play bar.'),
        ],
        'muted' => [
          '#title' => $this->t('Muted'),
          'description' => $this->t('Should video be muted.'),
        ],
      ],
      self::VIDEO_SETTINGS_CONFIG_NAME => [
        'cover' => [
          '#title' => $this->t('Cover'),
          'description' => $this->t('Video will cover entire available area and crop to the center.'),
        ],
        'image_style' => [
          '#title' => $this->t('Image style'),
          '#type' => 'select',
          '#default_value' => $this->getSetting(self::VIDEO_SETTINGS_CONFIG_NAME)['image_style'],
          '#empty_option' => $this->t('None (original image)'),
          '#options' => $this->imageStyleOptions(),
        ],
      ],
    ];

    return $settings[$type] ?? $settings;
  }

  /**
   * Human-readable state of a boolean-like setting.
   */
  protected function settingState(mixed $value): TranslatableMarkup|string {
    return match ((string) $value) {
      '0' => $this->t('disabled'),
      '1' => $this->t('enabled'),
      default => (string) $value,
    };
  }

  /**
   * Image style options available for the field.
   *
   * @return array<string, string|\Stringable>
   *   Image style options keyed by style machine name.
   */
  protected function imageStyleOptions(): array {
    $options = [];
    foreach ($this->imageStyleStorage->loadMultiple() as $name => $style) {
      $options[$name] = $style->label();
    }
    if ($options === []) {
      $options[''] = $this->t('No defined styles');
    }
    return $options;
  }

}
