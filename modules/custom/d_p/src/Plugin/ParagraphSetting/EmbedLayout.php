<?php

declare(strict_types=1);

namespace Drupal\d_p\Plugin\ParagraphSetting;

use Drupal\d_p\ParagraphSettingPluginBase;
use Drupal\d_p\ParagraphSettingSelectInterface;

/**
 * Plugin implementation of the 'embed_layout' setting.
 *
 * @ParagraphSetting(
 *   id = "embed_layout",
 *   label = @Translation("Embed side"),
 * )
 */
class EmbedLayout extends ParagraphSettingPluginBase implements ParagraphSettingSelectInterface {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function formElement(): array {
    return [
      '#type' => 'select',
      '#options' => $this->getOptions(),
    ] + parent::formElement();
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getOptions(): array {
    return [
      'left' => $this->t('Left'),
      'right' => $this->t('Right'),
      'full' => $this->t('Full width'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getDefaultValue(): mixed {
    return 'left';
  }

}
