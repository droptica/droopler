<?php

namespace Drupal\search_api\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Defines a contextual filter for applying Search API conditions.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("search_api")
 */
class SearchApiStandard extends ArgumentPluginBase {

  /**
   * The Views query object used by this contextual filter.
   *
   * @var \Drupal\search_api\Plugin\views\query\SearchApiQuery
   */
  public $query;

  /**
   * The operator to use for multiple arguments.
   *
   * Either "and" or "or".
   *
   * @var string
   *
   * @see \Drupal\views\Plugin\views\argument\ArgumentPluginBase::unpackArgumentValue()
   */
  public $operator;

  /**
   * {@inheritdoc}
   */
  public function defaultActions($which = NULL) {
    $defaults = parent::defaultActions();
    unset($defaults['summary']);

    if ($which) {
      return isset($defaults[$which]) ? $defaults[$which] : NULL;
    }
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['break_phrase'] = ['default' => FALSE];
    $options['not'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    if (empty($this->definition['disable_break_phrase'])) {
      // Allow passing multiple values.
      $form['break_phrase'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow multiple values'),
        '#description' => $this->t('If selected, users can enter multiple values in the form of 1+2+3 (for OR) or 1,2,3 (for AND).'),
        '#default_value' => !empty($this->options['break_phrase']),
        '#group' => 'options][more',
      ];
    }

    $form['not'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude'),
      '#description' => $this->t('If selected, the values entered for the filter will be excluded rather than limiting the view to those values.'),
      '#default_value' => !empty($this->options['not']),
      '#group' => 'options][more',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $option_values = &$form_state->getValue('options');
    if (empty($option_values)) {
      return;
    }

    // Let the plugins do validation.
    if (!empty($option_values['default_argument_type'])) {
      $default_id = $option_values['default_argument_type'];
      $plugin = $this->getPlugin('argument_default', $default_id);
      if ($plugin) {
        $plugin->validateOptionsForm($form['argument_default'][$default_id], $form_state, $option_values['argument_default'][$default_id]);
      }
    }

    if (!empty($option_values['validate']['type'])) {
      $sanitized_id = $option_values['validate']['type'];
      // Correct ID for js sanitized version.
      $validate_id = static::decodeValidatorId($sanitized_id);
      $plugin = $this->getPlugin('argument_validator', $validate_id);
      if ($plugin) {
        $plugin->validateOptionsForm($form['validate']['options'][$sanitized_id], $form_state, $option_values['validate']['options'][$sanitized_id]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $option_values = &$form_state->getValue('options');
    if (empty($option_values)) {
      return;
    }

    // Let the plugins make submit modifications if necessary.
    if (!empty($option_values['default_argument_type'])) {
      $default_id = $option_values['default_argument_type'];
      $plugin = $this->getPlugin('argument_default', $default_id);
      if ($plugin) {
        $options = &$option_values['argument_default'][$default_id];
        $plugin->submitOptionsForm($form['argument_default'][$default_id], $form_state, $options);
        // Copy the now submitted options to their final resting place so they get saved.
        $option_values['default_argument_options'] = $options;
      }
    }

    // If the 'Specify validation criteria' checkbox is not checked, reset the
    // validation options.
    if (empty($option_values['specify_validation'])) {
      $option_values['validate']['type'] = 'none';
      // We need to keep the empty array of options for the 'None' plugin as
      // it will be needed later.
      $option_values['validate']['options'] = ['none' => []];
      $option_values['validate']['fail'] = 'not found';
    }

    if (!empty($option_values['validate']['type'])) {
      $sanitized_id = $option_values['validate']['type'];
      // Correct ID for js sanitized version.
      $option_values['validate']['type'] = $validate_id = static::decodeValidatorId($sanitized_id);
      $plugin = $this->getPlugin('argument_validator', $validate_id);
      if ($plugin) {
        $options = &$option_values['validate']['options'][$sanitized_id];
        $plugin->submitOptionsForm($form['validate']['options'][$sanitized_id], $form_state, $options);
        // Copy the now submitted options to their final resting place so they get saved.
        $option_values['validate_options'] = $options;
      }
    }

    // Clear out the content of title if it's not enabled.
    if (empty($option_values['title_enable'])) {
      $option_values['title'] = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->fillValue();

    // If there are multiple arguments, add query conditions accordingly.
    // E.g 1+2+3 (for OR) or 1,2,3 (for AND).
    if (count($this->value) > 1) {
      if ($this->operator === 'or') {
        $operator = empty($this->options['not']) ? 'IN' : 'NOT IN';
        $this->query->addCondition($this->realField, $this->value, $operator);
      }
      else {
        foreach ($this->value as $value) {
          $operator = empty($this->options['not']) ? '=' : '<>';
          $this->query->addCondition($this->realField, $value, $operator);
        }
      }
    }
    elseif ($this->value) {
      $operator = empty($this->options['not']) ? '=' : '<>';
      $this->query->addCondition($this->realField, reset($this->value), $operator);
    }
  }

  /**
   * Fills $this->value and $this->operator with data from the argument.
   */
  protected function fillValue() {
    if (isset($this->value)) {
      return;
    }

    $filter = '';
    if (!empty($this->definition['filter'])) {
      $filter = $this->definition['filter'];
    }

    if (!empty($this->options['break_phrase']) && empty($this->definition['disable_break_phrase'])) {
      $force_int = FALSE;
      if ($filter == 'intval') {
        $force_int = TRUE;
        $filter = '';
      }
      $this->unpackArgumentValue($force_int);
    }
    else {
      $this->value = [$this->argument];
      $this->operator = 'and';
    }

    if (is_callable($filter)) {
      $this->value = array_map($filter, $this->value);
    }
  }

}
