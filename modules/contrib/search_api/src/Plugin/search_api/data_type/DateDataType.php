<?php

namespace Drupal\search_api\Plugin\search_api\data_type;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a date data type.
 *
 * @SearchApiDataType(
 *   id = "date",
 *   label = @Translation("Date"),
 *   description = @Translation("Represents points in time."),
 *   default = "true"
 * )
 */
class DateDataType extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
    if ((string) $value === '') {
      return NULL;
    }
    if (is_numeric($value)) {
      return (int) $value;
    }

    $timezone = new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE);
    $date = new DateTimePlus($value, $timezone);
    // Add in time component if this is a date-only field.
    if (strpos($value, ':') === FALSE) {
      $date->setDefaultDateTime();
    }
    return $date->getTimestamp();
  }

}
