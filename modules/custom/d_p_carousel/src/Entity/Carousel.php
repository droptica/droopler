<?php

declare(strict_types = 1);

namespace Drupal\d_p_carousel\Entity;

use Drupal\Component\Serialization\Json;
use Drupal\d_p\AddDividersInterface;
use Drupal\d_p\AddDividersTrait;
use Drupal\d_p\ColumnCountInterface;
use Drupal\d_p\ColumnCountTrait;
use Drupal\d_p\Enum\ParagraphSettingTypes;
use Drupal\d_p\FullWidthInterface;
use Drupal\d_p\FullWidthTrait;
use Drupal\d_p\WithPriceInterface;
use Drupal\d_p\WithPriceTrait;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides additional functionality for carousel paragraphs.
 */
class Carousel extends Paragraph implements FullWidthInterface, ColumnCountInterface, WithPriceInterface, AddDividersInterface {

  use FullWidthTrait;
  use ColumnCountTrait;
  use WithPriceTrait;
  use AddDividersTrait;

  /**
   * Get settings for carousel.
   *
   * @return string
   *   Json encoded settings.
   */
  public function getCarouselSettings(): string {
    [
      ParagraphSettingTypes::COLUMN_COUNT_DESKTOP->value => $desktop_columns,
      ParagraphSettingTypes::COLUMN_COUNT_TABLET->value => $tablet_columns,
      ParagraphSettingTypes::COLUMN_COUNT_MOBILE->value => $mobile_columns,
    ] = $this->getColumnSettings();

    return Json::encode([
      'infinite' => TRUE,
      'slidesToShow' => $desktop_columns,
      'slidesToScroll' => 1,
      'swipeToSlide' => TRUE,
      'touchMove' => TRUE,
      'autoplay' => TRUE,
      'autoplaySpeed' => 3000,
      'responsive' => [
        [
          'breakpoint' => $this->getMobileBreakpointSize(),
          'settings' => [
            'arrows' => TRUE,
            'slidesToShow' => $mobile_columns,
          ],
        ],
        [
          'breakpoint' => $this->getTabletBreakpointSize(),
          'settings' => [
            'arrows' => TRUE,
            'slidesToShow' => $tablet_columns,
          ],
        ],
      ],
    ]);
  }

}
