<?php

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\views\Plugin\views\filter\Date;

/**
 * Defines a filter for filtering on dates.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_date")
 */
class SearchApiDate extends Date {

  use SearchApiFilterTrait;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|null
   */
  protected $timeService;

  /**
   * Retrieves the time service.
   *
   * @return \Drupal\Component\Datetime\TimeInterface
   *   The time service.
   */
  public function getTimeService() {
    return $this->timeService ?: \Drupal::time();
  }

  /**
   * Sets the time service.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time_service
   *   The new time service.
   *
   * @return $this
   */
  public function setTimeService(TimeInterface $time_service) {
    $this->timeService = $time_service;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = parent::operators();
    unset($operators['regular_expression']);
    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    // Unfortunately, this is necessary due to a bug in our parent filter. See
    // #2704077.
    if (!empty($this->options['expose']['identifier'])) {
      $value = &$input[$this->options['expose']['identifier']];
      if (!is_array($value)) {
        $value = [
          'value' => $value,
        ];
      }
      $value += [
        'min' => '',
        'max' => '',
      ];
    }

    $return = parent::acceptExposedInput($input);

    if (!$return) {
      // Override for the "(not) empty" operators.
      $operators = $this->operators();
      if ($operators[$this->operator]['values'] == 0) {
        return TRUE;
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field) {
    if ($this->value['type'] == 'offset') {
      $time = $this->getTimeService()->getRequestTime();
      $a = strtotime($this->value['min'], $time);
      $b = strtotime($this->value['max'], $time);
    }
    else {
      $a = intval(strtotime($this->value['min'], 0));
      $b = intval(strtotime($this->value['max'], 0));
    }
    $real_field = $this->realField;
    $operator = strtoupper($this->operator);
    $group = $this->options['group'];
    $this->getQuery()->addCondition($real_field, [$a, $b], $operator, $group);
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    $value = intval(strtotime($this->value['value'], 0));
    if (!empty($this->value['type']) && $this->value['type'] == 'offset') {
      $time = $this->getTimeService()->getRequestTime();
      $value = strtotime($this->value['value'], $time);
    }

    $this->getQuery()->addCondition($this->realField, $value, $this->operator, $this->options['group']);
  }

}
