<?php

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'heading_type' setting.
 *
 * @ParagraphSetting(
 *   id = "heading_type",
 *   label = @Translation("Heading type"),
 * )
 */
class HeadingType extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(): array {
    $element = parent::formElement();

    return [
      '#description' => $this->t('Select the type of heading to use with this paragraph.'),
      '#type' => 'select',
      '#options' => $this->getOptions(),
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    return [
      'h1' => $this->t('H1'),
      'h2' => $this->t('H2'),
      'h3' => $this->t('H3'),
      'h4' => $this->t('H4'),
      'h5' => $this->t('H5'),
      'div' => $this->t('Normal text'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return 'h2';
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedBundles(): array {
    return [
      'd_p_banner',
      'd_p_carousel',
      'd_p_carousel_item',
      'd_p_form',
      'd_p_gallery',
      'd_p_group_of_counters',
      'd_p_group_of_text_blocks',
      'd_p_node',
      'd_p_reference_content',
      'd_p_side_embed',
      'd_p_side_image',
      'd_p_side_tiles',
      'd_p_single_text_block',
      'd_p_subscribe_file',
      'd_p_text_paged',
      'd_p_text_with_bckg',
      'd_p_tiles',
    ];
  }

}
