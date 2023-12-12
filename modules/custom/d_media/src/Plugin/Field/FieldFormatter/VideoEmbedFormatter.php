<?php

declare(strict_types = 1);

namespace Drupal\d_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
  const VIDEO_SETTINGS_CONFIG_NAME = 'video_settings';

  /**
   * Name for the player settings in the formatter.
   */
  const PLAYER_SETTINGS_CONFIG_NAME = 'player_settings';

  /**
   * The embed provider plugin manager.
   *
   * @var \Drupal\d_media\Service\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * Image style storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * Constructs an VideoEmbedFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\d_media\Service\ProviderManagerInterface $provider_manager
   *   The video embed provider manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The entity storage class.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    ProviderManagerInterface $provider_manager,
    EntityStorageInterface $image_style_storage
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->providerManager = $provider_manager;
    $this->imageStyleStorage = $image_style_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('d_media.video_provider_manager'),
      $container->get('entity_type.manager')->getStorage('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $value = $item->value;
      if (empty($value)) {
        continue;
      }

      $provider = $this->providerManager->loadProviderFromInput($value);
      if (!$provider) {
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
  public static function defaultSettings() {
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
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    // Player settings.
    $form[self::PLAYER_SETTINGS_CONFIG_NAME] = [
      '#type' => 'details',
      '#title' => $this->t('Player settings'),
      '#tree' => TRUE,
    ];
    $this->addFormSettings(self::PLAYER_SETTINGS_CONFIG_NAME, $form);

    // Video settings.
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
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    // Player settings.
    $summary[] = $this->t('Player settings');
    $this->addSettingsSummary(self::PLAYER_SETTINGS_CONFIG_NAME, $summary);

    // Video settings.
    $summary[] = $this->t('Video settings');
    $this->addSettingsSummary(self::VIDEO_SETTINGS_CONFIG_NAME, $summary);

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

  /**
   * Add fields with formatter settings to the form.
   *
   * @param string $type
   *   Type of the settings.
   * @param array $form
   *   Form array.
   */
  protected function addFormSettings($type, array &$form) {
    $settings_values = $this->getSetting($type);
    foreach ($this->getSettingsDefinitions($type) as $setting_name => $setting) {
      if (!isset($setting['#type']) || $setting['#type'] === 'checkbox') {
        $form[$type][$setting_name] = [
          '#type' => 'checkbox',
          '#title' => $setting['#title'],
          '#description' => $setting['description'],
          '#default_value' => $settings_values[$setting_name],
        ];
      }
      else {
        $form[$type][$setting_name] = $setting;
      }
    }
  }

  /**
   * Add summary section for a type of config.
   *
   * @param string $type
   *   Type of the settings.
   * @param array $summary
   *   The summary for formatter settings.
   */
  protected function addSettingsSummary($type, array &$summary) {
    $settings_values = $this->getSetting($type);
    foreach ($this->getSettingsDefinitions($type) as $setting_name => $setting) {
      $summary[] = $setting['#title'] . ': ' . $this->settingState($settings_values[$setting_name]);
    }

  }

  /**
   * Get settings definitions including name, label, descriptions, etc.
   *
   * @param string $type
   *   Type of the settings.
   *
   * @return array
   *   Settings definition, such as name, label and description.
   */
  protected function getSettingsDefinitions($type = '') {
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

    if (!empty($settings[$type])) {
      return $settings[$type];
    }

    return $settings;
  }

  /**
   * Human readable state of the setting.
   *
   * @param int $value
   *   Value of the setting.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The word describing setting state.
   */
  protected function settingState($value) {
    switch ((string) $value) {
      case '0':
        $state = $this->t('disabled');
        break;

      case '1':
        $state = $this->t('enabled');
        break;

      default:
        $state = $value;
        break;
    }

    return $state;
  }

  /**
   * Gets image styles option available for the field.
   *
   * @return array
   *   Array of image style options.
   */
  private function imageStyleOptions() {
    $styles = $this->imageStyleStorage->loadMultiple();
    $options = [];
    foreach ($styles as $name => $style) {
      $options[$name] = $style
        ->label();
    }
    if (empty($options)) {
      $options[''] = $this->t('No defined styles');
    }

    return $options;
  }

}
