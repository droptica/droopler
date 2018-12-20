<?php

namespace Drupal\facets\Plugin\facets\query_type;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\facets\QueryType\QueryTypeRangeBase;

/**
 * Support for date facets within the Search API scope.
 *
 * This query type supports dates for all possible backends. This specific
 * implementation of the query type supports a generic solution of adding facets
 * for dates.
 *
 * If you want to have a specific solution for your backend / module to
 * implement dates, you can alter the ::getQueryTypesForDataType method on the
 * backendPlugin to return a different class.
 *
 * @FacetsQueryType(
 *   id = "search_api_date",
 *   label = @Translation("Date"),
 * )
 */
class SearchApiDate extends QueryTypeRangeBase {

  /**
   * Constant for grouping on year.
   */
  const FACETAPI_DATE_YEAR = 6;

  /**
   * Constant for grouping on month.
   */
  const FACETAPI_DATE_MONTH = 5;

  /**
   * Constant for grouping on day.
   */
  const FACETAPI_DATE_DAY = 4;

  /**
   * Constant for grouping on hour.
   */
  const FACETAPI_DATE_HOUR = 3;

  /**
   * Constant for grouping on minute.
   */
  const FACETAPI_DATE_MINUTE = 2;

  /**
   * Constant for grouping on second.
   */
  const FACETAPI_DATE_SECOND = 1;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $facet = $configuration['facet'];
    $processors = $facet->getProcessors();
    $dateProcessorConfig = $processors['date_item']->getConfiguration();

    $configuration = $this->getConfiguration();
    $configuration['granularity'] = $dateProcessorConfig['granularity'];
    $configuration['date_display'] = $dateProcessorConfig['date_display'];
    $configuration['date_format'] = $dateProcessorConfig['date_format'];
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateRange($value) {
    if ($this->getDateDisplay() === 'relative_date') {
      return $this->calculateRangeRelative($value);
    }
    else {
      return $this->calculateRangeAbsolute($value);
    }
  }

  /**
   * Returns a start and end date based on a unix timestamp.
   *
   * This method returns a start and end date with an absolute interval, based
   * on the granularity set in the widget.
   *
   * @param int $value
   *   Unix timestamp.
   *
   * @return array
   *   An array with a start and end date as unix timestamps.
   *
   * @throws \Exception
   *   Thrown when creating a date fails.
   */
  protected function calculateRangeAbsolute($value) {
    $dateTime = new DrupalDateTime();

    switch ($this->getGranularity()) {
      case static::FACETAPI_DATE_YEAR:
        $startDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value . '-01-01T00:00:00');
        $stopDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value . '-12-31T23:59:59');
        break;

      case static::FACETAPI_DATE_MONTH:
        $startDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value . '-01T00:00:00');
        $stopDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value . '-' . $startDate->format('t') . 'T23:59:59');
        break;

      case static::FACETAPI_DATE_DAY:
        $startDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value . 'T00:00:00');
        $stopDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value . 'T23:59:59');
        break;

      case static::FACETAPI_DATE_HOUR:
        $startDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value . ':00:00');
        $stopDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value . ':59:59');
        break;

      case static::FACETAPI_DATE_MINUTE:
        $startDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value . ':00');
        $stopDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value . ':59');
        break;

      default:
        $startDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value);
        $stopDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value);
        break;
    }

    return [
      'start' => $startDate->format('U'),
      'stop' => $stopDate->format('U'),
    ];
  }

  /**
   * Returns a start and end date based on a unix timestamp.
   *
   * This method returns a start and end date with an relative interval, based
   * on the granularity set in the widget.
   *
   * @param int $value
   *   Unix timestamp.
   *
   * @return array
   *   An array with a start and end date as unix timestamps.
   *
   * @throws \Exception
   *   Thrown when creating a date fails.
   */
  protected function calculateRangeRelative($value) {
    $dateTime = new DrupalDateTime();

    switch ($this->getGranularity()) {
      case static::FACETAPI_DATE_YEAR:
        $startDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value . '-1T00:00:00');
        $stopDate = clone $startDate;
        $stopDate->add(new \DateInterval('P1Y'));
        $stopDate->sub(new \DateInterval('PT1S'));
        break;

      case static::FACETAPI_DATE_MONTH:
        $startDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value . 'T00:00:00');
        $stopDate = clone $startDate;
        $stopDate->add(new \DateInterval('P1M'));
        $stopDate->sub(new \DateInterval('PT1S'));
        break;

      case static::FACETAPI_DATE_DAY:
        $startDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value . ':00:00');
        $stopDate = clone $startDate;
        $stopDate->add(new \DateInterval('P1D'));
        $stopDate->sub(new \DateInterval('PT1S'));
        break;

      case static::FACETAPI_DATE_HOUR:
        $startDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value . ':00');
        $stopDate = clone $startDate;
        $stopDate->add(new \DateInterval('PT1H'));
        $stopDate->sub(new \DateInterval('PT1S'));
        break;

      case static::FACETAPI_DATE_MINUTE:
        $startDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value);
        $stopDate = clone $startDate;
        $stopDate->add(new \DateInterval('PT1M'));
        $stopDate->sub(new \DateInterval('PT1S'));
        break;

      default:
        $startDate = $dateTime::createFromFormat('Y-m-d\TH:i:s', $value);
        $stopDate = clone $startDate;
        break;
    }

    return [
      'start' => $startDate->format('U'),
      'stop' => $stopDate->format('U'),
    ];
  }

  /**
   * Calculates the result of the filter.
   *
   * @param int $value
   *   A unix timestamp.
   *
   * @return array
   *   An array with a start and end date as unix timestamps.
   */
  public function calculateResultFilter($value) {
    if ($this->getDateDisplay() === 'relative_date') {
      return $this->calculateResultFilterRelative($value);
    }
    else {
      return $this->calculateResultFilterAbsolute($value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateResultFilterAbsolute($value) {
    $date = new DrupalDateTime();
    $date->setTimestamp($value);
    $date_format = $this->getDateFormat();

    switch ($this->getGranularity()) {
      case static::FACETAPI_DATE_YEAR:
        $format = 'Y';
        $raw = $date->format('Y');
        break;

      case static::FACETAPI_DATE_MONTH:
        $format = 'F Y';
        $raw = $date->format('Y-m');
        break;

      case static::FACETAPI_DATE_DAY:
        $format = 'd F Y';
        $raw = $date->format('Y-m-d');
        break;

      case static::FACETAPI_DATE_HOUR:
        $format = 'd/m/Y H\h';
        $raw = $date->format('Y-m-d\TH');
        break;

      case static::FACETAPI_DATE_MINUTE:
        $format = 'd/m/Y H:i';
        $raw = $date->format('Y-m-d\TH:i');
        break;

      default:
        $format = 'd/m/Y H:i:s';
        $raw = $date->format('Y-m-d\TH:i:s');
        break;
    }

    $format = $date_format ? $date_format : $format;
    return [
      'display' => $date->format($format),
      'raw' => $raw,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateResultFilterRelative($value) {
    $date = new DrupalDateTime();
    $date->setTimestamp($value);
    $now = new DrupalDateTime();
    $now->setTimestamp(REQUEST_TIME);
    $interval = $date->diff($now);
    $future = $date > $now;

    switch ($this->getGranularity()) {
      case static::FACETAPI_DATE_YEAR:
        $rounded = new \DateInterval('P' . $interval->y . 'Y');
        if ($future) {
          $display = $interval->y ? $this->formatPlural($interval->y, '1 year hence', '@count years hence') : $this->t('In the next year');
          $now->add($rounded);
        }
        else {
          $display = $interval->y ? $this->formatPlural($interval->y, '1 year ago', '@count years ago') : $this->t('In the last year');
          $now->sub($rounded);
          $now->sub(new \DateInterval('P1Y'));
        }
        $raw = $now->format('Y-m');
        break;

      case static::FACETAPI_DATE_MONTH:
        $rounded = new \DateInterval('P' . $interval->y . 'Y' . $interval->m . 'M');
        $display = $interval->y ? $this->formatPlural($interval->y, '1 year', '@count years') . ' ' : '';
        if ($future) {
          $display .= $interval->m ?
            $this->formatPlural($interval->m, '1 month hence', '@count months hence') :
            (empty($display) ? $this->t('In the next month') : $this->t('0 months hence'));
          $now->add($rounded);
        }
        else {
          $display .= $interval->m ?
            $this->formatPlural($interval->m, '1 month ago', '@count months ago') :
            (empty($display) ? $this->t('In the last month') : $this->t('0 months ago'));
          $now->sub($rounded);
          $now->sub(new \DateInterval('P1M'));
        }
        $raw = $now->format('Y-m-d');
        break;

      case static::FACETAPI_DATE_DAY:
        $rounded = new \DateInterval('P' . $interval->y . 'Y' . $interval->m . 'M' . $interval->d . 'D');
        $display = $interval->y ? $this->formatPlural($interval->y, '1 year', '@count years') . ' ' : '';
        $display .= $interval->m ? $this->formatPlural($interval->m, '1 month', '@count months') . ' ' : '';
        if ($future) {
          $display .= $interval->d ?
            $this->formatPlural($interval->d, '1 day hence', '@count days hence') :
            (empty($display) ? $this->t('In the next day') : $this->t('0 days hence'));
          $now->add($rounded);
        }
        else {
          $display .= $interval->d ?
            $this->formatPlural($interval->d, '1 day ago', '@count days ago') :
            (empty($display) ? $this->t('In the last day') : $this->t('0 days ago'));
          $now->sub($rounded);
          $now->sub(new \DateInterval('P1D'));
        }
        $raw = $now->format('Y-m-d\TH');
        break;

      case static::FACETAPI_DATE_HOUR:
        $rounded = new \DateInterval('P' . $interval->y . 'Y' . $interval->m . 'M' . $interval->d . 'DT' . $interval->h . 'H');
        $display = $interval->y ? $this->formatPlural($interval->y, '1 year', '@count years') . ' ' : '';
        $display .= $interval->m ? $this->formatPlural($interval->m, '1 month', '@count months') . ' ' : '';
        $display .= $interval->d ? $this->formatPlural($interval->d, '1 day', '@count days') . ' ' : '';
        if ($future) {
          $display .= $interval->h ?
            $this->formatPlural($interval->h, '1 hour hence', '@count hours hence') :
            (empty($display) ? $this->t('In the next hour') : $this->t('0 hours hence'));
          $now->add($rounded);
        }
        else {
          $display .= $interval->h ?
            $this->formatPlural($interval->h, '1 hour ago', '@count hours ago') :
            (empty($display) ? $this->t('In the last hour') : $this->t('0 hours ago'));
          $now->sub($rounded);
          $now->sub(new \DateInterval('PT1H'));
        }
        $raw = $now->format('Y-m-d\TH:i');
        break;

      case static::FACETAPI_DATE_MINUTE:
        $rounded = new \DateInterval('P' . $interval->y . 'Y' . $interval->m . 'M' . $interval->d . 'DT' . $interval->h . 'H' . $interval->i);
        $display = $interval->y ? $this->formatPlural($interval->y, '1 year', '@count years') . ' ' : '';
        $display .= $interval->m ? $this->formatPlural($interval->m, '1 month', '@count months') . ' ' : '';
        $display .= $interval->d ? $this->formatPlural($interval->d, '1 day', '@count days') . ' ' : '';
        $display .= $interval->h ? $this->formatPlural($interval->h, '1 hour', '@count hours') . ' ' : '';
        if ($future) {
          $display .= $interval->i ?
            $this->formatPlural($interval->i, '1 minute hence', '@count minutes hence') :
            (empty($display) ? $this->t('In the next minute') : $this->t('0 minutes hence'));
          $now->add($rounded);
        }
        else {
          $display .= $interval->i ?
            $this->formatPlural($interval->i, '1 minute ago', '@count minutes ago') :
            (empty($display) ? $this->t('In the last minute') : $this->t('0 minutes ago'));
          $now->sub($rounded);
          $now->sub(new \DateInterval('PT1M'));
        }
        $raw = $date->format('Y-m-d\TH:i:s');
        break;

      default:
        $rounded = new \DateInterval('P' . $interval->y . 'Y' . $interval->m . 'M' . $interval->d . 'DT' . $interval->h . 'H' . $interval->i . $interval->s . 'S');
        $display = $interval->y ? $this->formatPlural($interval->y, '1 year', '@count years') . ' ' : '';
        $display .= $interval->m ? $this->formatPlural($interval->m, '1 month', '@count months') . ' ' : '';
        $display .= $interval->d ? $this->formatPlural($interval->d, '1 day', '@count days') . ' ' : '';
        $display .= $interval->h ? $this->formatPlural($interval->h, '1 hour', '@count hours') . ' ' : '';
        $display .= $interval->i ? $this->formatPlural($interval->i, '1 minute', '@count minutes') . ' ' : '';
        if ($future) {
          $display .= $interval->s ?
            $this->formatPlural($interval->s, '1 second hence', '@count seconds hence') :
            (empty($display) ? $this->t('In the next second') : $this->t('0 secondss hence'));
          $now->add($rounded);
        }
        else {
          $display .= $interval->s ?
            $this->formatPlural($interval->s, '1 second ago', '@count seconds ago') :
            (empty($display) ? $this->t('In the last second') : $this->t('0 seconds ago'));
          $now->sub($rounded);
          $now->sub(new \DateInterval('PT1S'));
        }
        $raw = $date->format('Y-m-d\TH:i:s');
        break;
    }

    return [
      'display' => $display,
      'raw' => $raw,
    ];
  }

  /**
   * Retrieve configuration: Granularity to use.
   *
   * Default behaviour an integer for the steps that the facet works in.
   *
   * @return int
   *   The granularity for this config.
   */
  protected function getGranularity() {
    return $this->getConfiguration()['granularity'];
  }

  /**
   * Retrieve configuration: Date Display type.
   *
   * @return string
   *   Returns the display mode..
   */
  protected function getDateDisplay() {
    return $this->getConfiguration()['date_display'];
  }

  /**
   * Retrieve configuration: Date display format.
   *
   * @return string
   *   Returns the format.
   */
  protected function getDateFormat() {
    return $this->getConfiguration()['date_format'];
  }

}
