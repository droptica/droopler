<?php

declare(strict_types = 1);

namespace Drupal\d_product\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the d_product_gallery_formatter.
 *
 * @FieldFormatter(
 *   id = "d_product_gallery_formatter",
 *   label = @Translation("Gallery"),
 *   description = @Translation("Droopler formatter with extra carousel settings"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class GalleryFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form['enable_navigation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable pager'),
      '#default_value' => $this->getSetting('enable_navigation'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'enable_navigation' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    return [
      'summary' => $this->t('Rendered as Gallery items and Gallery navigation items.')->render(),
    ];
  }


  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $single_items = [];
    $nav_items = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $single_items[$delta] = $view_builder->view($entity, 'd_product_gallery', $entity->language()->getId());
      $nav_items[$delta] = $view_builder->view($entity, 'd_product_gallery_navigation_item', $entity->language()->getId());
    }

    $element = [
      '#theme' => 'd_product_gallery',
      '#single_items' => [
        'settings' => $this->getGalleryForSettings(),
        'items' => $single_items,
      ],
    ];

    if ($this->getSetting('enable_navigation')) {
      $element['#nav_items'] = [
        'settings' => $this->getGalleryNavSettings(),
        'items' => $nav_items,
      ];
    }

    return $element;
  }

  /**
   * Get settings for one-item gallery.
   *
   * @return string
   *   Json encoded settings.
   */
  protected function getGalleryForSettings(): string {
    return Json::encode([
      'slidesToShow' => 1,
      'slidesToScroll' => 1,
      'arrows' => TRUE,
      'fade' => TRUE,
      'asNavFor' => '.product-gallery__small-images',
      'centerPadding' => '100px',
    ]);
  }

  /**
   * Get settings for gallery nav pager.
   *
   * @return string
   *   Json encoded settings.
   */
  protected function getGalleryNavSettings(): string {
    return Json::encode([
      'slidesToShow' => 4,
      'slidesToScroll' => 1,
      'arrows' => FALSE,
      'focusOnSelect' => TRUE,
      'asNavFor' => '.product-gallery__main-image',
    ]);
  }

}
