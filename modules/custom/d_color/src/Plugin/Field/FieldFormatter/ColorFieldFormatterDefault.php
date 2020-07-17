<?php

namespace Drupal\d_color\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the color_field css declaration formatter.
 *
 * @FieldFormatter(
 *   id = "d_color_field_formatter_default",
 *   label = @Translation("Color default"),
 *   field_types = {
 *     "d_color_field_type"
 *   }
 * )
 */
class ColorFieldFormatterDefault extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'selector' => '.custom-background-color',
        'property' => 'background-color',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'selector' => [
        '#title' => $this->t('Selector'),
        '#description' => $this->t('Inner selector for a entity.'),
        '#type' => 'textarea',
        '#default_value' => $this->getSetting('selector'),
        '#required' => FALSE,
        '#placeholder' => '.custom-background-color',
      ],
      'property' => [
        '#title' => $this->t('Property'),
        '#type' => 'select',
        '#default_value' => $this->getSetting('property'),
        '#required' => TRUE,
        '#options' => [
          'background-color' => $this->t('Background color'),
          'color' => $this->t('Text color'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();

    return [
      $this->t('Selector: @selector', [
        '@selector' => $settings['selector'] ?: $this->t('main entity wrapper'),
      ]),
      $this->t('Property: @property', [
        '@property' => $settings['property'],
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();
    $elements = [];
    $entity = $items->getEntity();
    $id_selector = "#{$entity->getEntityType()->id()}-{$entity->bundle()}-{$entity->id()}";

    foreach ($items as $item) {
      // Example: #paragraph-d_p_carousel-405 .field { background-color: #ff0000 !important; }
      $styles = "$id_selector {$settings['selector']} { {$settings['property']}: {$item->color} !important; }";

      $elements['#attached']['html_head'][] = [
        [
          '#tag' => 'style',
          '#value' => $styles,
        ],
        sha1($styles),
      ];
    }

    return $elements;
  }

}
