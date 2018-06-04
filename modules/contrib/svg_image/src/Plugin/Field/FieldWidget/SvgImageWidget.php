<?php

namespace Drupal\svg_image\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Override plugin of the 'image_image' widget.
 *
 * We have to fully override standard field widget, so we will keep original
 * label and formatter ID.
 *
 * @FieldWidget(
 *   id = "image_image",
 *   label = @Translation("Image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class SvgImageWidget extends FileWidget {

  /**
   * Container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  private $container;

  /**
   * Entity repository service instance.
   *
   * @var \Drupal\Core\Entity\EntityRepository
   */
  protected $entityRepository;

  /**
   * Renderer instance.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * EntityType manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Image style storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($pluginId, $pluginDefinition, FieldDefinitionInterface $fieldDefinition, array $settings, array $thirdPartySettings, ContainerInterface $container) {
    parent::__construct($pluginId, $pluginDefinition, $fieldDefinition, $settings, $thirdPartySettings, $container->get('element_info'));

    $this->container = $container;
    $this->entityRepository = $container->get('entity.repository');
    $this->renderer = $container->get('renderer');
    $this->entityTypeManager = $container->get('entity_type.manager');
    $this->imageStyleStorage = $this->entityTypeManager->getStorage('image_style');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $pluginId,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'progress_indicator' => 'throbber',
      'preview_image_style' => 'thumbnail',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    $element = parent::settingsForm($form, $formState);

    $element['preview_image_style'] = [
      '#title' => $this->t('Preview image style'),
      '#type' => 'select',
      '#options' => image_style_options(FALSE),
      '#empty_option' => '<' . $this->t('no preview') . '>',
      '#default_value' => $this->getSetting('preview_image_style'),
      '#description' => $this->t('The preview image will be shown while editing the content.'),
      '#weight' => 15,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $imageStyles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($imageStyles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $imageStyleSetting = $this->getSetting('preview_image_style');
    if (isset($imageStyles[$imageStyleSetting])) {
      $previewImageStyle = $this->t('Preview image style: @style', ['@style' => $imageStyles[$imageStyleSetting]]);
    }
    else {
      $previewImageStyle = $this->t('No preview');
    }

    array_unshift($summary, $previewImageStyle);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $formState) {
    $elements = parent::formMultipleElements($items, $form, $formState);

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $fileUploadHelp = [
      '#theme' => 'file_upload_help',
      '#description' => '',
      '#upload_validators' => $elements[0]['#upload_validators'],
      '#cardinality' => $cardinality,
    ];
    if ($cardinality == 1) {
      // If there's only one field, return it as delta 0.
      if (empty($elements[0]['#default_value']['fids'])) {
        $fileUploadHelp['#description'] = $this->getFilteredDescription();
        $elements[0]['#description'] = $this->renderer->renderPlain($fileUploadHelp);
      }
    }
    else {
      $elements['#file_upload_description'] = $fileUploadHelp;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $formState) {
    $element = parent::formElement($items, $delta, $element, $form, $formState);

    $fieldSettings = $this->getFieldSettings();

    // Add upload resolution validation.
    if ($fieldSettings['max_resolution'] || $fieldSettings['min_resolution']) {
      $element['#upload_validators']['file_validate_image_resolution'] = [$fieldSettings['max_resolution'], $fieldSettings['min_resolution']];
    }

    // If not using custom extension validation, ensure this is an image.
    $supportedExtensions = ['png', 'gif', 'jpg', 'jpeg', 'svg'];
    $extensions = isset($element['#upload_validators']['file_validate_extensions'][0]) ? $element['#upload_validators']['file_validate_extensions'][0] : implode(' ', $supportedExtensions);
    $extensions = array_intersect(explode(' ', $extensions), $supportedExtensions);
    $element['#upload_validators']['file_validate_extensions'][0] = implode(' ', $extensions);

    // Add mobile device image capture acceptance.
    $element['#accept'] = 'image/*';

    // Add properties needed by process() method.
    $element['#preview_image_style'] = $this->getSetting('preview_image_style');
    $element['#title_field'] = $fieldSettings['title_field'];
    $element['#title_field_required'] = $fieldSettings['title_field_required'];
    $element['#alt_field'] = $fieldSettings['alt_field'];
    $element['#alt_field_required'] = $fieldSettings['alt_field_required'];

    // Default image.
    $defaultImage = $fieldSettings['default_image'];
    if (empty($defaultImage['uuid'])) {
      $defaultImage = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('default_image');
    }
    // Convert the stored UUID into a file ID.
    if (!empty($defaultImage['uuid']) && $entity = $this->entityRepository->loadEntityByUuid('file', $defaultImage['uuid'])) {
      $defaultImage['fid'] = $entity->id();
    }
    $element['#default_image'] = !empty($defaultImage['fid']) ? $defaultImage : [];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $formState, $form) {
    $item = $element['#value'];
    $item['fids'] = $element['fids']['#value'];

    $element['#theme'] = 'image_widget';

    // Add the image preview.
    if (!empty($element['#files']) && $element['#preview_image_style']) {
      $file = reset($element['#files']);

      $variables = svg_image_get_image_file_dimensions($file);

      $variables['style_name'] = $element['#preview_image_style'];
      $variables['uri'] = $file->getFileUri();

      // Add a custom preview for SVG file.
      if (svg_image_is_file_svg($file)) {
        $element['preview'] = [
          '#weight' => -10,
          '#theme' => 'image',
          '#width' => $variables['width'],
          '#height' => $variables['height'],
          '#uri' => $variables['uri'],
        ];
      }
      else {
        $element['preview'] = [
          '#weight' => -10,
          '#theme' => 'image_style',
          '#width' => $variables['width'],
          '#height' => $variables['height'],
          '#style_name' => $variables['style_name'],
          '#uri' => $variables['uri'],
        ];
      }

      // Store the dimensions in the form so the file doesn't have to be
      // accessed again. This is important for remote files.
      $element['width'] = [
        '#type' => 'hidden',
        '#value' => $variables['width'],
      ];
      $element['height'] = [
        '#type' => 'hidden',
        '#value' => $variables['height'],
      ];
    }
    elseif (!empty($element['#default_image'])) {
      $defaultImage = $element['#default_image'];
      $file = File::load($defaultImage['fid']);
      if (!empty($file)) {
        $element['preview'] = [
          '#weight' => -10,
          '#theme' => 'image_style',
          '#width' => $defaultImage['width'],
          '#height' => $defaultImage['height'],
          '#style_name' => $element['#preview_image_style'],
          '#uri' => $file->getFileUri(),
        ];
      }
    }

    // Add the additional alt and title fields.
    $element['alt'] = [
      '#title' => t('Alternative text'),
      '#type' => 'textfield',
      '#default_value' => isset($item['alt']) ? $item['alt'] : '',
      '#description' => t('This text will be used by screen readers, search engines, or when the image cannot be loaded.'),
      // @see https://www.drupal.org/node/465106#alt-text
      '#maxlength' => 512,
      '#weight' => -12,
      '#access' => (bool) $item['fids'] && $element['#alt_field'],
      '#required' => $element['#alt_field_required'],
      '#element_validate' => $element['#alt_field_required'] == 1 ? [[get_called_class(), 'validateRequiredFields']] : [],
    ];
    $element['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => isset($item['title']) ? $item['title'] : '',
      '#description' => t('The title is used as a tool tip when the user hovers the mouse over the image.'),
      '#maxlength' => 1024,
      '#weight' => -11,
      '#access' => (bool) $item['fids'] && $element['#title_field'],
      '#required' => $element['#title_field_required'],
      '#element_validate' => $element['#title_field_required'] == 1 ? [[get_called_class(), 'validateRequiredFields']] : [],
    ];

    return parent::process($element, $formState, $form);
  }

  /**
   * Validate callback for alt and title field, if the user wants them required.
   *
   * This is separated in a validate function instead of a #required flag to
   * avoid being validated on the process callback.
   */
  public static function validateRequiredFields($element, FormStateInterface $formState) {
    // Only do validation if the function is triggered from other places than
    // the image process form.
    $triggeringElement = $formState->getTriggeringElement();
    if (empty($triggeringElement['#submit']) || !in_array('file_managed_file_submit', $triggeringElement['#submit'])) {
      // If the image is not there, we do not check for empty values.
      $parents = $element['#parents'];
      $field = array_pop($parents);
      $imageField = NestedArray::getValue($formState->getUserInput(), $parents);
      // We check for the array key, so that it can be NULL (like if the user
      // submits the form without using the "upload" button).
      if (!array_key_exists($field, $imageField)) {
        return;
      }
    }
    else {
      $formState->setLimitValidationErrors([]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $styleId = $this->getSetting('preview_image_style');

    if ($styleId) {
      /** @var \Drupal\image\ImageStyleStorage $imageStyleStorage */

      $style = $this->imageStyleStorage->load($styleId);
      if ($style) {
        // If this widget uses a valid image style to display the preview of
        // the uploaded image, add that image style configuration entity
        // as dependency of this widget.
        $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    $styleId = $this->getSetting('preview_image_style');

    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($styleId) {
      $style = $this->imageStyleStorage->load($styleId);
      if ($style) {
        if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
          /** @var \Drupal\image\ImageStyleStorageInterface $storage */
          $storage = $this->entityTypeManager->getStorage($style->getEntityTypeId());

          $replacementId = $storage->getReplacementId($styleId);
          // If a valid replacement has been provided in the storage, replace
          // the preview image style with the replacement.
          if ($replacementId && $this->imageStyleStorage->load($replacementId)) {
            $this->setSetting('preview_image_style', $replacementId);
          }

          // If there's no replacement or the replacement is invalid, disable
          // the image preview.
          else {
            $this->setSetting('preview_image_style', '');
          }

          // Signal that the formatter plugin settings were updated.
          $changed = TRUE;
        }
      }
    }

    return $changed;
  }

}
