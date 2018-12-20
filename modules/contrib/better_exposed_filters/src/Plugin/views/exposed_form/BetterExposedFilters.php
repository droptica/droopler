<?php

namespace Drupal\better_exposed_filters\Plugin\views\exposed_form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\exposed_form\InputRequired;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\views\Plugin\views\filter\StringFilter;

/**
 * Exposed form plugin that provides a basic exposed form.
 *
 * @ingroup views_exposed_form_plugins
 *
 * @ViewsExposedForm(
 *   id = "bef",
 *   title = @Translation("Better Exposed Filters"),
 *   help = @Translation("Provides additional options for exposed form elements.")
 * )
 */
class BetterExposedFilters extends InputRequired {

  /**
   * @inheritdoc
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $bef_options = array();

    // Get current settings and default values for new filters/
    $existing = $this->getSettings();

    // Insert a checkbox to make the input required optional just before the
    // input required text field. Only show the text field if the input required
    // option is selected.
    $textInputRequired = $form['text_input_required'];
    unset($form['text_input_required']);
    $form['input_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Input required'),
      '#description' => $this->t('Only display results after the user has selected a filter option.'),
      '#default_value' => !empty($this->options['input_required']),
    ];
    $form['text_input_required'] = $textInputRequired += [
      '#states' => [
        'visible' => [
          'input[name="exposed_form_options[input_required]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    /*
     * Add general options for exposed form items.
     */
    $bef_options['general']['allow_secondary'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable secondary exposed form options'),
      '#default_value' => $existing['general']['allow_secondary'],
      '#description' => $this->t('Allows you to specify some exposed form elements as being secondary options and places those elements in a collapsible "details" element. Use this option to place some exposed filters in an "Advanced Search" area of the form, for example.'),
    );
    $bef_options['general']['secondary_label'] = array(
      '#type' => 'textfield',
      '#default_value' => $existing['general']['secondary_label'],
      '#title' => $this->t('Secondary options label'),
      '#description' => $this->t(
        'The name of the details element to hold secondary options. This cannot be left blank or there will be no way to show/hide these options.'
      ),
      '#states' => array(
        'required' => array(
          ':input[name="exposed_form_options[bef][general][allow_secondary]"]' => array('checked' => TRUE),
        ),
        'visible' => array(
          ':input[name="exposed_form_options[bef][general][allow_secondary]"]' => array('checked' => TRUE),
        ),
      ),
    );

    // Add the 'autosbumit' functionality from Views 7.x.
    $bef_options['general']['autosubmit'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Autosubmit'),
      '#description' => $this->t('Automatically submit the form once an element is changed.'),
      '#default_value' => $existing['general']['autosubmit'],
    );

    $bef_options['general']['autosubmit_exclude_textfield'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude Textfield'),
      '#description' => $this->t('Exclude Textfield from autosubmit. User will have to press enter key or click submit.'),
      '#default_value' => $existing['general']['autosubmit_exclude_textfield'],
      '#states' => array(
        'visible' => array(
          ':input[name="exposed_form_options[bef][general][autosubmit]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $bef_options['general']['autosubmit_hide'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Hide submit button'),
      '#description' => $this->t('Hide submit button if javascript is enabled.'),
      '#default_value' => $existing['general']['autosubmit_hide'],
      '#states' => array(
        'visible' => array(
          ':input[name="exposed_form_options[bef][general][autosubmit]"]' => array('checked' => TRUE),
        ),
      ),
    );

    /*
     * Add options for exposed sorts.
     */
    $exposed = FALSE;
    /* @var \Drupal\views\Plugin\views\HandlerBase $sort */
    foreach ($this->view->display_handler->getHandlers('sort') as $label => $sort) {
      if ($sort->isExposed()) {
        $exposed = TRUE;
        break;
      }
    }
    if ($exposed) {
      $bef_options['sort']['bef_format'] = array(
        '#type' => 'select',
        '#title' => $this->t('Display exposed sort options as'),
        '#default_value' => $existing['sort']['bef_format'],
        '#options' => array(
          'default' => $this->t('Default Views element'),
          'bef' => $this->t('Radio Buttons'),
          'bef_links' => $this->t('Links'),
        ),
        '#description' => $this->t('Select a format for the exposed sort options.'),
      );
      $bef_options['sort']['advanced'] = array(
        '#type' => 'details',
        '#title' => $this->t('Advanced sort options'),
      );
      $bef_options['sort']['advanced']['collapsible'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Make sort options collapsible'),
        '#default_value' => $existing['sort']['advanced']['collapsible'],
        '#description' => $this->t(
          'Puts the sort options in a collapsible details element.'
        ),
      );
      $bef_options['sort']['advanced']['collapsible_label'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Collapsible details element title'),
        '#default_value' => empty($existing['sort']['advanced']['collapsible_label']) ? $this->t('Sort options') : $existing['sort']['advanced']['collapsible_label'],
        '#description' => $this->t('This cannot be left blank or there will be no way to show/hide sort options.'),
        '#states' => array(
          'visible' => array(
            ':input[name="exposed_form_options[bef][sort][advanced][collapsible]"]' => array('checked' => TRUE),
          ),
          'required' => array(
            ':input[name="exposed_form_options[bef][sort][advanced][collapsible]"]' => array('checked' => TRUE),
          ),
        ),
      );

      // We can only combine sort order and sort by if both options are exposed.
      $bef_options['sort']['advanced']['combine'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Combine sort order with sort by'),
        '#default_value' => $existing['sort']['advanced']['combine'],
        '#description' => $this->t('Combines the sort by options and order (ascending or decending) into a single list.  Use this to display "Option1 (ascending)", "Option1 (descending)", "Option2 (ascending)", "Option2 (descending)" in a single form element. Sort order should first be exposed by selecting <em>Allow people to choose the sort order</em>.'),
        '#states' => [
          'enabled' => [
            ':input[name="exposed_form_options[expose_sort_order]"]' => ['checked' => TRUE],
          ],
        ],
      );
      $bef_options['sort']['advanced']['combine_rewrite'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Rewrite the text displayed'),
        '#default_value' => $existing['sort']['advanced']['combine_rewrite'],
        '#description' => $this->t('Use this field to rewrite the text displayed for combined sort options and sort order. Use the format of current_text|replacement_text, one replacement per line. For example: <pre>
Post date Asc|Oldest first
Post date Desc|Newest first
Title Asc|A -> Z
Title Desc|Z -> A</pre> Leave the replacement text blank to remove an option altogether. The order the options appear will be changed to match the order of options in this field.'),
        '#states' => array(
          'visible' => array(
            ':input[name="exposed_form_options[bef][sort][advanced][combine]"]' => array('checked' => TRUE),
          ),
        ),
      );
      $bef_options['sort']['advanced']['reset'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Include a "Reset sort" option'),
        '#default_value' => $existing['sort']['advanced']['reset'],
        '#description' => $this->t('Adds a "Reset sort" link; Views will use the default sort order.'),
      );
      $bef_options['sort']['advanced']['reset_label'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('"Reset sort" label'),
        '#default_value' => $existing['sort']['advanced']['reset_label'],
        '#description' => $this->t('This cannot be left blank if the above option is checked'),
        '#states' => array(
          'visible' => array(
            ':input[name="exposed_form_options[bef][sort][advanced][reset]"]' => array('checked' => TRUE),
          ),
          'required' => array(
            ':input[name="exposed_form_options[bef][sort][advanced][reset]"]' => array('checked' => TRUE),
          ),
        ),
      );
      $bef_options['sort']['advanced']['is_secondary'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('This is a secondary option'),
        '#default_value' => $existing['sort']['advanced']['is_secondary'],
        '#states' => array(
          'visible' => array(
            ':input[name="exposed_form_options[bef][general][allow_secondary]"]' => array('checked' => TRUE),
          ),
        ),
        '#description' => $this->t('Places this element in the secondary options portion of the exposed form.'),
      );
    }

    /*
     * Add options for exposed pager.
     */
    if (isset($this->display->display_options['pager']) && $this->display->display_options['pager']['options']['expose']['items_per_page']) {
      $bef_options['pager']['bef_format'] = array(
        '#type' => 'select',
        '#title' => $this->t('Display exposed pager options as'),
        '#default_value' => $existing['pager']['bef_format'],
        '#options' => array(
          'default' => $this->t('Default (Views render element)'),
          'bef' => $this->t('Radio Buttons'),
          'bef_links' => $this->t('Links'),
        ),
        '#description' => $this->t('Select a format for the exposed pager options.'),
      );
      $bef_options['pager']['is_secondary'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('This is a secondary option'),
        '#default_value' => $existing['pager']['is_secondary'],
        '#states' => array(
          'visible' => array(
            ':input[name="exposed_form_options[bef][general][allow_secondary]"]' => array('checked' => TRUE),
          ),
        ),
        '#description' => $this->t('Places this element in the secondary options portion of the exposed form.'),
      );
    }

    // Only add the description text once -- it was getting a little long to be
    // added to each filter.
    $bef_filter_intro = FALSE;

    // Go through each filter and add BEF options.
    /* @var \Drupal\views\Plugin\views\HandlerBase $filter */
    foreach ($this->view->display_handler->getHandlers('filter') as $label => $filter) {
      if (!$filter->isExposed()) {
        continue;
      }

      // If we're adding BEF filter options, add an intro to explain what's
      // going on.
      if (!$bef_filter_intro) {
        $bef_options['bef_intro'] = array(
          '#markup' => '<h3>'
            . $this->t('Exposed Filter Settings')
            . '</h3><p>'
            . $this->t('This section lets you select additional options for exposed filters. Some options are only available in certain situations. If you do not see the options you expect, please see the <a href=":link">BEF settings documentation page</a> for more details.',
              array(
                ':link' => Url::fromUri('http://drupal.org/node/1701012')
                  ->toString()
              ))
            . '</p>',
        );
        $bef_filter_intro = TRUE;
      }

      // These filter operators get our standard options: select, radio or
      // checkboxes, links, etc.
      $bef_standard = FALSE;

      // These filters get a single on/off checkbox option for boolean
      // operators.
      $bef_single = FALSE;

      // Used for taxonomy filters with hierarchy.
      $bef_nested = FALSE;

      // Used for taxonomy filters with hierarchy rendered as links.
      $bef_nested_links = FALSE;

      // Used for date-based filters.
      $bef_datepicker = FALSE;

      // Used for numeric, non-date filters.
      $bef_slider = FALSE;

      // Check various filter types and determine what options are available.
      if (is_a($filter, 'Drupal\views\Plugin\views\filter\String') || is_a($filter, 'Drupal\views\Plugin\views\filter\InOperator')) {
        if (in_array($filter->operator, array('in', 'or', 'and'))) {
          $bef_standard = TRUE;
        }
        if (in_array($filter->operator, array('empty', 'not empty'))) {
          $bef_standard = TRUE;
          if (!$filter->options['expose']['multiple']) {
            $bef_single = TRUE;
          }
        }
      }

      if (is_a($filter, 'Drupal\views\Plugin\views\filter\BooleanOperator')) {
        $bef_standard = TRUE;
        if (!$filter->options['expose']['multiple']) {
          $bef_single = TRUE;
        }
      }

      if (is_a($filter, 'Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid')) {
        // Autocomplete and dropdown taxonomy filter are both instances of
        // TaxonomyIndexTid, but we can't show BEF options for the autocomplete
        // widget.
        if ($this->displayHandler->handlers['filter'][$label]->options['type'] != 'select') {
          $bef_standard = FALSE;
        }
        elseif (!empty($this->displayHandler->handlers['filter'][$label]->options['hierarchy'])) {
          $bef_nested = TRUE;
          $bef_nested_links = TRUE;
        }
      }

      if (is_a($filter, 'Drupal\views\Plugin\views\filter\Date') || !empty($filter->date_handler)) {
        $bef_datepicker = TRUE;
      }

      // The date filter handler extends the numeric filter handler so we have
      // to exclude it specifically.
      if (is_a($filter, 'Drupal\views\Plugin\views\filter\NumericFilter') && !is_a($filter, 'Drupal\views\Plugin\views\filter\Date')) {
        $bef_slider = TRUE;
      }

      // All filters can use the default filter exposed by Views.
      $display_options = array('default' => $this->t('Default (Views render element)'));

      if ($bef_standard) {
        // Main BEF option: radios/checkboxes.
        $display_options['bef'] = $this->t('Checkboxes/Radio Buttons');
      }

      if ($bef_nested) {
        $display_options['bef_ul'] = $this->t('Nested Checkboxes/Radio Buttons');
      }

      if ($bef_single) {
        $display_options['bef_single'] = $this->t('Single on/off checkbox');
      }

      if ($bef_datepicker) {
        $display_options['bef_datepicker'] = $this->t('jQuery UI Datepicker');
      }

      if ($bef_slider) {
        $display_options['bef_slider'] = $this->t('jQuery UI slider');
      }

      if ($bef_standard) {
        // Less used BEF options, so put them last.
        $display_options['bef_links'] = $this->t('Links');
        if ($bef_nested_links) {
          $display_options['bef_ul_links'] = $this->t('Nested Links');
        }
        $display_options['bef_hidden'] = $this->t('Hidden');
      }

      // Alter the list of available display options for this filter.
      \Drupal::moduleHandler()->alter('better_exposed_filters_display_options', $display_options, $filter);

      $identifier = '"' . $filter->options['expose']['identifier'] . '"';
      if (!empty($filter->options['expose']['label'])) {
        $identifier .= $this->t(' (Filter label: "@fl")', array('@fl' => $filter->options['expose']['label']));
      }
      $bef_options[$label]['bef_format'] = array(
        '#type' => 'select',
        '#title' => $this->t('Display @identifier exposed filter as', array('@identifier' => $identifier)),
        '#default_value' => $existing[$label]['bef_format'],
        '#options' => $display_options,
      );

      if ($bef_slider) {
        // Details element for jQuery slider options.
        $bef_options[$label]['slider_options'] = array(
          '#type' => 'details',
          '#title' => $this->t('Slider options for @identifier', array('@identifier' => $identifier)),
          '#open' => TRUE,
          '#states' => array(
            'visible' => array(
              ':input[name="exposed_form_options[bef][' . $label . '][bef_format]"]' => array('value' => 'bef_slider'),
            ),
          ),
        );

        $bef_options[$label]['slider_options']['bef_slider_min'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Range minimum'),
          '#default_value' => $this->options['bef'][$label]['slider_options']['bef_slider_min'],
          '#bef_filter_id' => $label,
          '#states' => array(
            'required' => array(
              ':input[name="exposed_form_options[bef][' . $label . '][bef_format]"]' => array('value' => 'bef_slider'),
            ),
          ),
          '#description' => $this->t('The minimum allowed value for the jQuery range slider. It can be positive, negative, or zero and have up to 11 decimal places.'),
        );
        $bef_options[$label]['slider_options']['bef_slider_max'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Range maximum'),
          '#default_value' => $this->options['bef'][$label]['slider_options']['bef_slider_max'],
          '#bef_filter_id' => $label,
          '#states' => array(
            'required' => array(
              ':input[name="exposed_form_options[bef][' . $label . '][bef_format]"]' => array('value' => 'bef_slider'),
            ),
          ),
          '#description' => $this->t('The maximum allowed value for the jQuery range slider. It can be positive, negative, or zero and have up to 11 decimal places.'),
        );
        $bef_options[$label]['slider_options']['bef_slider_step'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Step'),
          '#default_value' => empty($this->options['bef'][$label]['slider_options']['bef_slider_step']) ? 1 : $this->options['bef'][$label]['slider_options']['bef_slider_step'],
          '#bef_filter_id' => $label,
          '#states' => array(
            'required' => array(
              ':input[name="exposed_form_options[bef][' . $label . '][bef_format]"]' => array('value' => 'bef_slider'),
            ),
          ),
          '#description' => $this->t('Determines the size or amount of each interval or step the slider takes between the min and max.') . '<br />' .
            $this->t('The full specified value range of the slider (Range maximum - Range minimum) must be evenly divisible by the step.') . '<br />' .
            $this->t('The step must be a positive number of up to 5 decimal places.'),
        );
        $bef_options[$label]['slider_options']['bef_slider_animate'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Animate'),
          '#default_value' => $this->options['bef'][$label]['slider_options']['bef_slider_animate'],
          '#bef_filter_id' => $label,
          '#description' => $this->t('Whether to slide handle smoothly when user click outside handle on the bar. Allowed values are "slow", "normal", "fast" or the number of milliseconds to run the animation (e.g. 1000). If left blank, there will be no animation, the slider will just jump to the new value instantly.'),
        );
        $bef_options[$label]['slider_options']['bef_slider_orientation'] = array(
          '#type' => 'select',
          '#title' => $this->t('Orientation'),
          '#options' => array(
            'horizontal' => $this->t('Horizontal'),
            'vertical' => $this->t('Vertical'),
          ),
          '#default_value' => $this->options['bef'][$label]['slider_options']['bef_slider_orientation'],
          '#bef_filter_id' => $label,
          '#states' => array(
            'required' => array(
              ':input[name="exposed_form_options[bef][' . $label . '][bef_format]"]' => array('value' => 'bef_slider'),
            ),
          ),
          '#description' => $this->t('The orientation of the jQuery range slider.'),
        );
      }

      // Details element to keep the UI from getting out of hand.
      $bef_options[$label]['more_options'] = array(
        '#type' => 'details',
        '#title' => $this->t('More options for @identifier', array('@identifier' => $identifier)),
      );

      // Select all checkbox.
      if ($bef_standard) {
        $bef_options[$label]['more_options']['bef_select_all_none'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Add select all/none links'),
          '#default_value' => $existing[$label]['more_options']['bef_select_all_none'],
          '#disabled' => !$filter->options['expose']['multiple'],
          '#description' => $this->t(
            'Add a "Select All/None" link when rendering the exposed filter using
              checkboxes. If this option is disabled, edit the filter and check the
              "Allow multiple selections".'
          ),
        );

        if ($bef_nested) {
          $bef_options[$label]['more_options']['bef_select_all_none_nested'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Add nested all/none selection'),
            '#default_value' => $this->options['bef'][$label]['more_options']['bef_select_all_none_nested'],
            '#disabled' => !$filter->options['expose']['multiple'] || !$filter->options['hierarchy'],
            '#description' => $this->t(
              'When a parent checkbox is checked, check all its children. If this option
                is disabled, edit the filter and check "Allow multiple selections" and
                edit the filter settings and check "Show hierarchy in dropdown".'
            ),
          );
        }

        // Put filter in details element option.
        // TODO: expand to all exposed filters.
        $bef_options[$label]['more_options']['bef_collapsible'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Make this filter collapsible'),
          '#default_value' => $existing[$label]['more_options']['bef_collapsible'],
          '#description' => $this->t(
            'Puts this filter in a collapsible details element'
          ),
        );
      }

      // Allow any filter to be moved into the secondary options element.
      $bef_options[$label]['more_options']['is_secondary'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('This is a secondary option'),
        '#default_value' => $existing[$label]['more_options']['is_secondary'],
        '#states' => array(
          'visible' => array(
            ':input[name="exposed_form_options[bef][general][allow_secondary]"]' => array('checked' => TRUE),
          ),
        ),
        '#description' => $this->t('Places this element in the secondary options portion of the exposed form.'),
      );

      $filter_form = array();
      $form_state = new FormState();
      /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
      $filter->buildExposedForm($filter_form, $form_state);

      $supported_types = array('entity_autocomplete', 'textfield');

      $filter_id = $filter->options['expose']['identifier'];
      if (in_array($filter_form[$filter_id]['#type'], $supported_types) || in_array($filter_form[$filter_id]['value']['#type'], $supported_types)) {
        // Allow users to specify placeholder text.
        $bef_options[$label]['more_options']['placeholder_text'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Placeholder text'),
          '#description' => $this->t('Text to be shown in the text field until it is edited. Leave blank for no placeholder to be set.'),
          '#default_value' => $existing[$label]['more_options']['placeholder_text'],
        ];
      }

      // Allow rewriting of filter options for any filter. String and numeric
      // filters allow unlimited filter options via textfields, so we can't
      // offer rewriting for those.
      // @TODO: check other core filter types to see if there are others that
      // should be added to this list.
      if (!$filter instanceof StringFilter && !$filter instanceof NumericFilter) {
        $bef_options[$label]['more_options']['rewrite'] = array(
          '#title' => $this->t('Rewrite filter options'),
          '#type' => 'details',
        );
        $bef_options[$label]['more_options']['rewrite']['filter_rewrite_values'] = array(
          '#type' => 'textarea',
          '#title' => $this->t('Rewrite the text displayed'),
          '#default_value' => $existing[$label]['more_options']['rewrite']['filter_rewrite_values'],
          '#description' => $this->t('Use this field to rewrite the filter options displayed. Use the format of current_text|replacement_text, one replacement per line. For example: <pre>
  Current|Replacement
  On|Yes
  Off|No
  </pre> Leave the replacement text blank to remove an option altogether. If using hierarchical taxonomy filters, do not including leading hyphens in the current text.
          '),
        );
      }
    }
    /* Ends: foreach ($filters as $filter) { */

    // Add BEF form elements to the exposed form options form.
    $form['bef'] = $bef_options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $bef = $form_state->getValue(['exposed_form_options', 'bef']);

    // Validate slider settings.
    foreach ($bef as $id => $settings) {
      if ($id == 'general' || $settings['bef_format'] != 'bef_slider') {
        continue;
      }

      // Max must be > min.
      $min = $settings['slider_options']['bef_slider_min'];
      $max = $settings['slider_options']['bef_slider_max'];
      if ($max <= $min) {
        $form_state->setError($form['bef'][$id], $this->t('The slider max value must be greater than the slider min value.'));
      }

      // Validate the slider animation settings.
      $animate = $settings['slider_options']['bef_slider_animate'];
      if ($animate != '') {
        if ((!is_numeric($animate) || intval($animate) != $animate) && !in_array($animate, ['slow', 'normal', 'fast'])) {
          $form_state->setError($form['bef'][$id], $this->t('The slider animation option for %name must be "slow", "normal", or "fast", the number of milliseconds to run the animation (e.g. 1000), or blank.', ['%name' => $id]));
        }
      }

      // Validate the step setting:
      //   - Must be positive
      //   - No more than 5 decimal places
      //   - Slider range must be evenly divisible by step
      $step = $settings['slider_options']['bef_slider_step'];
      if ($step <= 0) {
        $form_state->setError($form['bef'][$id], $this->t('The slider step option for %name must be a positive number.', ['%name' => $id]));
      }
      if (strlen(substr(strrchr((string) $step, '.'), 1)) > 5) {
        $form_state->setError($form['bef'][$id], $this->t('The slider step option for %name cannot have more than 5 decimal places.', ['%name' => $id]));
      }
      // Very small step and a vary large range can go beyond the max value of
      // an int in PHP. Thus we look for a decimal point when casting the result
      // to a string.
      if (strpos((string) ($max - $min) / $step, '.')) {
        $form_state->setError($form['bef'][$id], $this->t('The slider range for %name must be evenly divisible by the step option.', ['%name' => $id]));
      }
    }

    parent::validateOptionsForm($form, $form_state);
  }



  /**
   * @inheritdoc
   */
  public function exposedFormAlter(&$form, FormStateInterface $form_state) {
    parent::exposedFormAlter($form, $form_state);

    // If we have no visible elements, we don't show the Apply button.
    $show_apply = FALSE;

    // These styles are used on all exposed forms.
    $form['#attached']['library'][] = 'better_exposed_filters/general';

    // Add the bef-exposed-form class at the form level so we can limit some
    // styling changes to just BEF forms.
    $form['#attributes']['class'][] = 'bef-exposed-form';

    // Collect BEF's Javascript settings, add to Drupal.settings at the end.
    $bef_add_js = FALSE;
    $bef_js = array(
      'datepicker' => FALSE,
      'slider' => FALSE,
      'settings' => array(),
    );

    // Grab BEF settings and allow modules/theme to modify them before
    // processing.
    $settings = $this->getSettings();
    \Drupal::moduleHandler()->alter('better_exposed_filters_settings', $settings, $this->view, $this->displayHandler);

    // Some elements may be placed in a secondary details element (eg: "Advanced
    // search options"). Place this after the exposed filters and before the
    // rest of the items in the exposed form.
    if ($allow_secondary = $settings['general']['allow_secondary']) {
      $secondary = array(
        '#type' => 'details',
        '#title' => $settings['general']['secondary_label'],
      );
    }

    // Apply autosubmit values.
    if (!empty($settings['general']['autosubmit'])) {
      $form = array_merge_recursive($form, array('#attributes' => array('data-bef-auto-submit-full-form' => '')));
      $form['actions']['submit']['#attributes']['data-bef-auto-submit-click'] = '';
      $form['#attached']['library'][] = 'better_exposed_filters/auto_submit';

      if (!empty($settings['general']['autosubmit_exclude_textfield'])) {
        foreach ($form as &$element) {
          if (isset($element['#type']) && $element['#type'] == 'textfield') {
            $element['#attributes'] = ['data-bef-auto-submit-exclude' => ''];
          }
        }
      }

      if (!empty($settings['general']['autosubmit_hide'])) {
        $form['actions']['submit']['#attributes']['class'][] = 'js-hide';
      }
    }

    /*
     * Handle exposed sort elements.
     */
    if (isset($settings['sort']) && !empty($form['sort_by'])) {
      $show_apply = TRUE;

      // If selected, collect all sort-related form elements and put them
      // in a details element.
      $collapse = $settings['sort']['advanced']['collapsible']
        && !empty($settings['sort']['advanced']['collapsible_label']);
      $sort_elems = array();

      // Check for combined sort_by and sort_order.
      if ($settings['sort']['advanced']['combine'] && !empty($form['sort_order'])) {
        $options = [];

        // Add reset sort option at the top of the list.
        if ($settings['sort']['advanced']['reset']) {
          $options[' '] = $this->t($settings['sort']['advanced']['reset_label']);
        }
        else {
          $form['sort_bef_combine']['#default_value'] = '';
        }

        $selected = '';
        foreach ($form['sort_by']['#options'] as $by_key => $by_val) {
          foreach ($form['sort_order']['#options'] as $order_key => $order_val) {
            // Use a space to separate the two keys, we'll unpack them in our
            // submit handler.
            $options["$by_key $order_key"] = "$by_val $order_val";

            if ($form['sort_order']['#default_value'] == $order_key && empty($selected)) {
              // Respect default sort order set in Views. The default sort field
              // will be the first one if there are multiple sort criteria.
              $selected = "$by_key $order_key";
            }
          }
        }

        // Rewrite the option values if any were specified.
        if (!empty($settings['sort']['advanced']['combine_rewrite'])) {
          $options = $this->rewriteOptions($options, $settings['sort']['advanced']['combine_rewrite'], TRUE);
          if (!isset($options[$selected])) {
            // Avoid "illegal choice" errors if the selected option is
            // eliminated by the rewrite.
            $selected = NULL;
          }
        }

        $form['sort_bef_combine'] = array(
          '#options' => $options,
          '#default_value' => $selected,
          // Already sanitized by Views.
          '#title' => $form['sort_by']['#title'],
        );

        // Handle display-specific details.
        switch ($settings['sort']['bef_format']) {
          case 'bef':
            $form['sort_bef_combine']['#type'] = 'radios';
            $form['sort_bef_combine']['#theme'] = 'bef_radios';
            break;

          case 'bef_links':
            $form['sort_bef_combine']['#theme'] = 'bef_links';

            // Exposed form displayed as blocks can appear on pages other than
            // the view results appear on. This can cause problems with
            // select_as_links options as they will use the wrong path. We
            // provide a hint for theme functions to correct this.
            $form['sort_bef_combine']['#bef_path'] = $this->getExposedFormActionUrl();
            break;

          case 'default':
            $form['sort_bef_combine']['#type'] = 'select';
            break;
        }

        // Add our submit routine to process.
        $form['#submit'][] = 'bef_sort_combine_submit';

        // Pretend we're another exposed form widget.
        $form['#info']['sort-sort_bef_combine'] = array(
          'value' => 'sort_bef_combine',
        );

        // Remove the existing sort_by and sort_order elements.
        unset($form['sort_by']);
        unset($form['sort_order']);

        if ($collapse) {
          $sort_elems[] = 'sort_bef_combine';
        }
      }
      else {
        // Leave sort_by and sort_order as separate elements.
        if ('bef' == $settings['sort']['bef_format']) {
          foreach (['sort_by', 'sort_order'] as $field) {
            if (!empty($form[$field])) {
              $form[$field]['#theme'] = 'bef_radios';
              $form[$field]['#type'] = 'radios';
              if (empty($form[$field]['#process'])) {
                $form[$field]['#process'] = array();
              }
              $form[$field]['#process'][] = ['\Drupal\Core\Render\Element\Radios', 'processRadios'];
            }
          }
        }
        elseif ('bef_links' == $settings['sort']['bef_format']) {
          $form['sort_by']['#theme'] = 'bef_links';
          if(!empty($form['sort_order'])) {
            $form['sort_order']['#theme'] = 'bef_links';
          }

          // Exposed form displayed as blocks can appear on pages other than the
          // view results appear on. This can cause problems with
          // select_as_links options as they will use the wrong path. We provide
          // a hint for theme functions to correct this.
          $form['sort_by']['#bef_path'] = $this->getExposedFormActionUrl();
          if(!empty($form['sort_order'])) {
            $form['sort_order']['#bef_path'] = $this->getExposedFormActionUrl();
          }
        }

        if ($collapse) {
          $sort_elems[] = 'sort_by';
          if(!empty($form['sort_order'])) {
            $sort_elems[] = 'sort_order';
          }
        }

        // Add reset sort option if selected.
        if ($settings['sort']['advanced']['reset']) {
          array_unshift($form['sort_by']['#options'], $settings['sort']['advanced']['reset_label']);
        }
      }
      /* Ends: if ($settings['sort']['advanced']['combine']) { ... } else { */

      if ($collapse) {
        $form['bef_sort_options'] = array(
          '#type' => 'details',
          '#title' => $settings['sort']['advanced']['collapsible_label'],
        );
        foreach ($sort_elems as $elem) {
          $form['bef_sort_options'][$elem] = $form[$elem];
          unset($form[$elem]);
        }
      }

      // Check if this is a secondary form element.
      if ($allow_secondary && $settings['sort']['advanced']['is_secondary']) {
        foreach (array('sort_bef_combine', 'sort_by', 'sort_order') as $elem) {
          if (!empty($form[$elem])) {
            $secondary[$elem] = $form[$elem];
            unset($form[$elem]);
          }
        }
      }
    }
    /* Ends: if (isset($settings['sort'])) { */

    /*
     * Handle exposed pager elements.
     */
    if (isset($settings['pager'])) {
      $show_apply = TRUE;

      switch ($settings['pager']['bef_format']) {
        case 'bef':
          $form['items_per_page']['#type'] = 'radios';
          if (empty($form['items_per_page']['#process'])) {
            $form['items_per_page']['#process'] = array();
          }
          array_unshift($form['items_per_page']['#process'], 'form_process_radios');
          $form['items_per_page']['#prefix'] = '<div class="bef-sortby bef-select-as-radios">';
          $form['items_per_page']['#suffix'] = '</div>';
          break;

        case 'bef_links':
          if (count($form['items_per_page']['#options']) > 1) {
            $form['items_per_page']['#theme'] = 'select_as_links';
            $form['items_per_page']['#items_per_page'] = max($form['items_per_page']['#default_value'], key($form['items_per_page']['#options']));

            // Exposed form displayed as blocks can appear on pages other than
            // the view results appear on. This can cause problems with
            // select_as_links options as they will use the wrong path. We
            // provide a hint for theme functions to correct this.
            $form['items_per_page']['#bef_path'] = $this->getExposedFormActionUrl();
          }
          break;
      }

      // Check if this is a secondary form element.
      if ($allow_secondary && $settings['pager']['is_secondary']) {
        foreach (array('items_per_page', 'offset') as $elem) {
          if (!empty($form[$elem])) {
            $secondary[$elem] = $form[$elem];
            unset($form[$elem]);
          }
        }
      }
    }

    // Shorthand for all filters in this view.
    /* @var \Drupal\views\Plugin\views\HandlerBase[] $filters */
    $filters = $form_state->get('view')->display_handler->handlers['filter'];

    // Go through each saved option looking for Better Exposed Filter settings.
    foreach ($settings as $label => $options) {

      // Sanity check: Ensure this filter is an exposed filter.
      if (empty($filters[$label]) || !$filters[$label]->isExposed()) {
        continue;
      }

      // Form element is designated by the element ID which is user-
      // configurable.
      $field_id = $filters[$label]->options['expose']['identifier'];

      // Check for placeholder text.
      if (!empty($settings[$label]['more_options']['placeholder_text'])) {
        // @todo: Add token replacement for placeholder text.
        $form[$field_id]['#placeholder'] = $settings[$label]['more_options']['placeholder_text'];
      }

      // Handle filter value rewrites.
      if (!empty($options['more_options']['rewrite']['filter_rewrite_values'])) {
        $form[$field_id]['#options'] = $this->rewriteOptions($form[$field_id]['#options'] , $options['more_options']['rewrite']['filter_rewrite_values']);
        if (isset($selected) && !isset($form[$field_id]['#options'][$selected])) {
          // Avoid "Illegal choice" errors.
          $form[$field_id]['#default_value'] = NULL;
        }
      }

      // @TODO: Is this conditional needed anymore after the existing settings
      // array default values were added?
      if (!isset($options['bef_format'])) {
        $options['bef_format'] = '';
      }

      // These BEF options require a set of given options to work (namely,
      // $form[$field_id]['#options'] needs to set). But it is possible to
      // adjust settings elsewhere in the view that removes these options from
      // the form (eg: changing a taxonomy term filter from dropdown to
      // autocomplete). Check for that here and revert to Views' default filter
      // in those cases.
      $requires_options = array('bef', 'bef_ul', 'bef_links', 'bef_ul_links', 'bef_hidden');
      if (in_array($options['bef_format'], $requires_options)) {
        if (empty($form[$field_id]['#options'])) {
          $options['bef_format'] = 'default';
        }
        else {
          // Clean up filters that pass objects as options instead of strings.
          $form[$field_id]['#options'] = $this->cleanOptions($form[$field_id]['#options']);
        }
      }

      switch ($options['bef_format']) {
        case 'bef_datepicker':
          $show_apply = TRUE;
          $bef_add_js = TRUE;
          $bef_js['datepicker'] = TRUE;
          $bef_js['datepicker_options'] = array();

          if ((
            // Single Date API-based input element.
            isset($form[$field_id]['value']['#type'])
              && 'date_text' == $form[$field_id]['value']['#type']
          )
          // Double Date-API-based input elements such as "in-between".
          || (isset($form[$field_id]['min']) && isset($form[$field_id]['max'])
            && 'date_text' == $form[$field_id]['min']['#type']
            && 'date_text' == $form[$field_id]['max']['#type']
          )) {
            /*
             * Convert Date API formatting to jQuery formatDate formatting.
             *
             * @TODO: To be honest, I'm not sure this is needed.  Can you set a
             * Date API field to accept anything other than Y-m-d? Well, better
             * safe than sorry...
             *
             * @see http://us3.php.net/manual/en/function.date.php
             * @see http://docs.jquery.com/UI/Datepicker/formatDate
             *
             * Array format: PHP date format => jQuery formatDate format
             * (comments are for the PHP format, lines that are commented out do
             * not have a jQuery formatDate equivalent, but maybe someday they
             * will...)
             */
            $convert = array(
              /* Day */

              // Day of the month, 2 digits with leading zeros 01 to 31.
              'd' => 'dd',
              // A textual representation of a day, three letters  Mon through
              // Sun.
              'D' => 'D',
              // Day of the month without leading zeros  1 to 31.
              'j' => 'd',
              // (lowercase 'L') A full textual representation of the day of the
              // week Sunday through Saturday.
              'l' => 'DD',
              // ISO-8601 numeric representation of the day of the week (added
              // in PHP 5.1.0) 1 (for Monday) through 7 (for Sunday).
              // 'N' => ' ',
              // English ordinal suffix for the day of the month, 2 characters
              // st, nd, rd or th. Works well with j.
              // 'S' => ' ',
              // Numeric representation of the day of the week 0 (for Sunday)
              // through 6 (for Saturday).
              // 'w' => ' ',
              // The day of the year (starting from 0) 0 through 365.
              'z' => 'o',

              /* Week */
              // ISO-8601 week number of year, weeks starting on Monday (added
              // in PHP 4.1.0) Example: 42 (the 42nd week in the year).
              // 'W' => ' ',
              //
              /* Month */
              // A full textual representation of a month, such as January or
              // March  January through December.
              'F' => 'MM',
              // Numeric representation of a month, with leading zeros 01
              // through 12.
              'm' => 'mm',
              // A short textual representation of a month, three letters  Jan
              // through Dec.
              'M' => 'M',
              // Numeric representation of a month, without leading zeros  1
              // through 12.
              'n' => 'm',
              // Number of days in the given month 28 through 31.
              // 't' => ' ',
              //
              /* Year */
              // Whether it's a leap year  1 if it is a leap year, 0 otherwise.
              // 'L' => ' ',
              // ISO-8601 year number. This has the same value as Y, except that
              // if the ISO week number (W) belongs to the previous or next
              // year, that year is used instead. (added in PHP 5.1.0).
              // Examples: 1999 or 2003.
              // 'o' => ' ',
              // A full numeric representation of a year, 4 digits Examples:
              // 1999 or 2003.
              'Y' => 'yy',
              // A two digit representation of a year  Examples: 99 or 03.
              'y' => 'y',

              /* Time */
              // Lowercase Ante meridiem and Post meridiem am or pm.
              // 'a' => ' ',
              // Uppercase Ante meridiem and Post meridiem AM or PM.
              // 'A' => ' ',
              // Swatch Internet time  000 through 999.
              // 'B' => ' ',
              // 12-hour format of an hour without leading zeros 1 through 12.
              // 'g' => ' ',
              // 24-hour format of an hour without leading zeros 0 through 23.
              // 'G' => ' ',
              // 12-hour format of an hour with leading zeros  01 through 12.
              // 'h' => ' ',
              // 24-hour format of an hour with leading zeros  00 through 23.
              // 'H' => ' ',
              // Minutes with leading zeros  00 to 59.
              // 'i' => ' ',
              // Seconds, with leading zeros 00 through 59.
              // 's' => ' ',
              // Microseconds (added in PHP 5.2.2) Example: 654321.
              // 'u' => ' ',
            );

            $format = '';
            if (isset($form[$field_id]['value'])) {
              $format = $form[$field_id]['value']['#date_format'];
              $form[$field_id]['value']['#attributes']['class'][] = 'bef-datepicker';
            }
            else {
              // Both min and max share the same format.
              $format = $form[$field_id]['min']['#date_format'];
              $form[$field_id]['min']['#attributes']['class'][] = 'bef-datepicker';
              $form[$field_id]['max']['#attributes']['class'][] = 'bef-datepicker';
            }
            $bef_js['datepicker_options']['dateformat'] = str_replace(array_keys($convert), array_values($convert), $format);
          }
          else {
            /*
             * Standard Drupal date field.  Depending on the settings, the field
             * can be at $form[$field_id] (single field) or
             * $form[$field_id][subfield] for two-value date fields or filters
             * with exposed operators.
             */
            $fields = array('min', 'max', 'value');
            if (count(array_intersect($fields, array_keys($form[$field_id])))) {
              foreach ($fields as $field) {
                if (isset($form[$field_id][$field])) {
                  $form[$field_id][$field]['#attributes']['class'][] = 'bef-datepicker';
                }
              }
            }
            else {
              $form[$field_id]['#attributes']['class'][] = 'bef-datepicker';
            }
          }
          break;

        case 'bef_slider':
          $show_apply = TRUE;
          $form[$field_id]['#attached']['library'][] = 'core/jquery.ui.slider';
          $form[$field_id]['#attached']['library'][] = 'better_exposed_filters/sliders';

          $form[$field_id]['#attached']['drupalSettings']['better_exposed_filters']['slider'] = TRUE;
            $form[$field_id]['#attached']['drupalSettings']['better_exposed_filters']['slider_options'][$field_id] = [
            'min' => $options['slider_options']['bef_slider_min'],
            'max' => $options['slider_options']['bef_slider_max'],
            'step' => $options['slider_options']['bef_slider_step'],
            'animate' => $options['slider_options']['bef_slider_animate'],
            'orientation' => $options['slider_options']['bef_slider_orientation'],
            'id' => Html::getUniqueId($field_id),
            'viewId' => $form['#id'],
          ];
          break;

        case 'bef_links':
        case 'bef_ul_links':
          if ($options['bef_format'] == 'bef_ul_links') {
            // Let the templates know this is a nested option.
            $form[$field_id]['#bef_nested'] = TRUE;
          }

          $form[$field_id]['#theme'] = 'bef_links';

          // Exposed form displayed as blocks can appear on pages other than
          // the view results appear on. This can cause problems with
          // select_as_links options as they will use the wrong path. We provide
          // a hint for theme functions to correct this.
          $form[$field_id]['#bef_path'] = $this->getExposedFormActionUrl();
          break;

        case 'bef_single':
          $show_apply = TRUE;

          // Use filter label as checkbox label.
          $form[$field_id]['#title'] = $filters[$label]->options['expose']['label'];
          $form[$field_id]['#type'] = 'checkbox';
          // Views populates missing values in $form_state['input'] with the
          // defaults and a checkbox does not appear in $_GET (or $_POST) so it
          // will appear to be missing when a user submits a form. Because of
          // this, instead of unchecking the checkbox value will revert to the
          // default. More, the default value for select values is reused which
          // results in the checkbox always checked. So we need to add a form
          // element to see whether the form is submitted or not and then we
          // need to look at $_GET directly to see whether the checkbox is
          // there. For security reasons, we must not copy the $_GET value.

          // First, let's figure out a short name for the signal element and
          // then add it.
          if (empty($signal)) {
            for ($signal = 'a'; isset($form[$signal]); $signal++);
            // This is all the signal element needs.
            $form[$signal]['#type'] = 'hidden';
          }
          $input = $form_state->getUserInput();
          $value = \Drupal::request()->query->get($field_id);
          $checked = isset($input[$signal]) ? isset($value) : $form[$field_id]['#default_value'];
          // For security, we check if value is valid and exist.
          if ($checked) {
            if (!in_array($value, array_keys($form[$field_id]['#options']))) {
              $checked = FALSE;
            }
          }
          // Now we know whether the checkbox is checked or not, set #value
          // accordingly.
          $form[$field_id]['#value'] = $checked ? $value : 0;
          break;

        case 'bef':
        case 'bef_ul':
          if ($options['bef_format'] == 'bef_ul') {
            $form[$field_id]['#bef_nested'] = TRUE;
          }
          $show_apply = TRUE;

          // Clean up objects from the options array (happens for taxonomy-
          // based filters).
          $form[$field_id]['#options'] = $this->cleanOptions($form[$field_id]['#options']);

          // Render as either radio buttons or checkboxes.
          if (empty($form[$field_id]['#multiple'])) {
            // Single-select -- display as radio buttons.
            $form[$field_id]['#type'] = 'radios';
            $form[$field_id]['#theme'] = 'bef_radios';
            if (empty($form[$field_id]['#process'])) {
              $form[$field_id]['#process'] = array();
            }
            $form[$field_id]['#process'][] = ['\Drupal\Core\Render\Element\Radios', 'processRadios'];
          }
          else {
            $form[$field_id]['#type'] = 'checkboxes';
            $form[$field_id]['#theme'] = 'bef_checkboxes';

            if ($options['more_options']['bef_select_all_none'] || $options['more_options']['bef_select_all_none_nested']) {
              $form[$field_id]['#attached']['library'][] = 'better_exposed_filters/select_all_none';

              $form[$field_id]['#bef_select_all_none'] = $options['more_options']['bef_select_all_none'];
              $form[$field_id]['#bef_select_all_none_nested'] = $options['more_options']['bef_select_all_none_nested'];
            }
          }
          break;

        case 'bef_hidden':
          if (empty($form[$field_id]['#multiple'])) {
            // Single entry filters can simple be changed to a different element
            // type.
            $form[$field_id]['#type'] = 'hidden';
          }
          else {
            // Hide the label.
            $form['#info']["filter-$label"]['label'] = '';

            // Use BEF's preprocess and template to output the hidden elements.
            $form[$field_id]['#theme'] = 'bef_hidden';
          }
          break;

        default:
          $show_apply = TRUE;
          break;
      }
      /* Ends switch ($options['bef_format']) */

      // Collapsible filters are an option for all exposed filters.
      if (!empty($options['more_options']['bef_collapsible'])) {
        // Pass the description and title along in a way such that it does
        // not get rendered as part of the exposed form widget.  We render
        // them as part of the details element instead.
        $form[$field_id]['#theme_wrappers'] = [
          'details' => [
            '#title' => $form['#info']["filter-$label"]['label'] ?: '',
            '#description' => $form['#info']["filter-$label"]['description'] ?: '',
            // Needed to keep styling consistent with other exposed options.
            '#attributes' => array('class' => 'form-item'),
            '#value' => NULL,
          ],
        ];
        $form['#info']["filter-$label"]['label'] = '';
        $form['#info']["filter-$label"]['description'] = '';

        // Check if the operator is exposed for this filter.
        if (isset($this->view->getHandlers('filter')[$label])
          && $this->view->getHandlers('filter')[$label]['expose']['use_operator']
        ) {
          // Include the exposed operator with the filter.
          $operator_id = $this->view->getHandlers('filter')[$label]['expose']['operator_id'];
          $form[$field_id][$operator_id] = $form[$operator_id];
          unset($form[$operator_id]);
        }
      }

      // Check if this is a secondary form element.
      if ($allow_secondary && $settings[$label]['more_options']['is_secondary']) {
        if ($filters[$label]->options['is_grouped']) {
          $identifier = $filters[$label]->options['group_info']['identifier'];
          $filter_info_name = "filter-$identifier";
        }
        else {
          $identifier = $filters[$label]->options['expose']['identifier'];
          $filter_info_name = "filter-$label";
        }
        if (!empty($form[$identifier])) {
          // Move exposed operators with exposed filters
          if (!empty($this->view->getHandlers('filter')[$label]['expose']['use_operator'])) {
            $op_id = $this->view->getHandlers('filter')[$label]['expose']['operator_id'];
            $secondary[$op_id] = $form[$op_id];
            unset($form[$op_id]);
          }
          $secondary[$identifier] = $form[$identifier];
          unset($form[$identifier]);
          $secondary[$identifier]['#title'] = $form['#info'][$filter_info_name]['label'];
          $secondary[$identifier]['#description'] = $form['#info'][$filter_info_name]['description'];
          unset($form['#info'][$filter_info_name]);
        }
      }
    }

    // If our form has no visible filters, hide the submit button.
    $form['submit']['#access'] = $show_apply;
    $form['reset']['#access'] = $show_apply;

    // Add Javascript as needed.
    if ($bef_add_js) {
      // Add jQuery UI library code as needed.
      if ($bef_js['datepicker']) {
        $form['#attached']['library'][] = 'core/jquery.ui.datepicker';
        $form['#attached']['library'][] = 'better_exposed_filters/datepickers';
      }

      $form['#attached']['drupalSettings']['better_exposed_filters'] = $bef_js;
    }

    // Check for secondary elements.
    if ($allow_secondary && !empty($secondary)) {
      // Use weights to place the secondary elements just ahead of the
      // submit/reset buttons, but after all the other expsoed elements.
      foreach(Element::children($form) as $index => $child) {
        $form[$child]['#weight'] = $index;
      }
      $form['secondary'] = $secondary;
      $form['secondary']['#weight'] = count($form['#info']) -1;
      $form['#info']['filter-secondary']['value'] = 'secondary';
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function exposedFilterApplied() {
    // If the input required option is set, check to see if a filter option has
    // been set.
    if (!empty($this->options['input_required'])) {
      return parent::exposedFilterApplied();
    }
    else {
      return TRUE;
    }
  }

  /**
   * Rewrites a set of options given a string from the config form.
   *
   * Rewrites should be specified, one per line, using the format
   * old_string|new_string. If new_string is empty, the option will be removed.
   *
   * @param array $options
   *   An array of key => value pairs that may be rewritten.
   * @param string $rewriteSettings
   *   String representing the entry in the settings form.
   * @param bool $reorder
   *   Reorder $options based on the rewrite settings.
   *
   * @return array
   *   Rewritten $options.
   */
  protected function rewriteOptions(array $options, $rewriteSettings, $reorder = FALSE) {
    if (empty($rewriteSettings) || !is_string($rewriteSettings)) {
      return $options;
    }
    $rewrites = [];
    $order = [];
    if (!$reorder) {
      $order = array_keys($options);
    }
    $lines = explode("\n", trim($rewriteSettings));
    foreach ($lines as $line) {
      list($search, $replace) = explode('|', $line);
      if (!empty($search)) {
        $rewrites[$search] = $replace;
        if ($reorder) {
          // Reorder options in the order they are specified in rewrites.
          // Collect the keys to use later.
          // @TODO: need to handle taxonomy terms before exposing this option.
          $key = array_search($search, $options);
          if ($key !== FALSE) {
            $order[] = $key;
          }
        }
      }
    }

    $return = [];
    if ($reorder && !empty($order)) {
      // Start with the items that were listed in the rewrite settings.
      foreach ($order as $key) {
        $return[$key] = $options[$key];
        unset($options[$key]);
      }

      // Tack remaaining options on the end.
      $return += $options;
    }
    else {
      $return = $options;
    }

    // Rewrite the option text.
    foreach ($return as $index => $option) {
      // Handle taxonomy terms which use a stdClass option object that doesn't
      // implement __toString().
      if ($option instanceof \stdClass && isset($option->option) && is_array($option->option)) {
        $option = array_values($option->option)[0];
      }
      else if (!is_string($option)) {
        // Some options, such as "- Any -", are passed as TranslatableMarkup
        // objects. Convert them to strings for the comparison.
        $option = (string) $option;
      }
      if (!is_string($option)) {
        // We give up...
        continue;
      }

      if (isset($rewrites[$option])) {
        if ('' == $rewrites[$option]) {
          unset($return[$index]);
        }
        else {
          if ($return[$index] instanceof \stdClass) {
            $tid = key($return[$index]->option);
            $text = current($return[$index]->option);
            $return[$index]->option[$tid] = $rewrites[$text];
          }
          else {
            $return[$index] = $rewrites[$option];
          }
        }
      }
    }
    return $return;
  }

  /**
   * Cleans up options being sent to radio button or checkbox renderers.
   *
   * @param array $options
   *   The options array to clean.
   *
   * @return array
   *   Cleaned options array.
   */
  protected function cleanOptions(array $options) {
    // Check the first element to see if we need to clean this array. If it is
    // a scalar, then just return the array as-is.
    if (is_scalar(reset($options))) {
      return $options;
    }

    $clean = [];
    foreach ($options as $index => $value) {
      // Some options, such as the "any" text, are really just plain
      // text so we keep those as they are. Others, like taxonomy terms
      // need to be converted to text.
      if (is_object($value) && !is_a($value, 'Drupal\Core\StringTranslation\TranslatableMarkup')) {
        reset($value->option);
        $key = key($value->option);
        $val = current($value->option);
        $clean[$key] = $val;
      }
      else {
        $clean[$index] = $value;
      }
    }
    return $clean;
  }

  /**
   * Fills in missing settings with default values.
   *
   * Similar to array_merge_recursive, but later numeric keys overwrites earlier
   * values.  Use this to set defaults for missing values in a multi-dimensional
   * array.  Eg:
   *
   *  $existing = $this->setDefaults($defaults, $existing);
   *
   * @return array
   *   The resulting settings array
   */
  protected function setDefaults() {
    $count = func_num_args();
    if (!$count) {
      return;
    }
    elseif (1 == $count) {
      return (func_get_arg(0));
    }

    // First array is the default values.
    $params = func_get_args();
    $return = array_shift($params);

    // Merge the rest of the arrays onto the default array.
    foreach ($params as $array) {
      foreach ($array as $key => $value) {
        // Numeric keyed values are added (unless already there).
        if (is_numeric($key) && !in_array($value, $return)) {
          if (is_array($value)) {
            $return[] = $this->setDefaults($return[$key], $value);
          }
          else {
            $return[] = $value;
          }
        }
        // String keyed values are replaced.
        else {
          if (isset($return[$key]) && is_array($value) && is_array($return[$key])) {
            $return[$key] = $this->setDefaults($return[$key], $value);
          }
          else {
            $return[$key] = $value;
          }
        }
      }
    }
    return $return;
  }

  /**
   * Updates legacy settings to their current location.
   *
   * @param array $settings
   *   Array of BEF settings.
   */
  protected function updateLegacySettings($settings) {
    // There has got to be a better way... But for now, this works.
    if (isset($settings['sort']['collapsible'])) {
      $settings['sort']['advanced']['collapsible'] = $settings['sort']['collapsible'];
      unset($settings['sort']['collapsible']);
    }
    if (isset($settings['sort']['collapsible_label'])) {
      $settings['sort']['advanced']['collapsible_label'] = $settings['sort']['collapsible_label'];
      unset($settings['sort']['collapsible_label']);
    }
    if (isset($settings['sort']['combine'])) {
      $settings['sort']['advanced']['combine'] = $settings['sort']['combine'];
      unset($settings['sort']['combine']);
    }
    if (isset($settings['sort']['reset'])) {
      $settings['sort']['advanced']['reset'] = $settings['sort']['reset'];
      unset($settings['sort']['reset']);
    }
    if (isset($settings['sort']['reset_label'])) {
      $settings['sort']['advanced']['reset_label'] = $settings['sort']['reset_label'];
      unset($settings['sort']['reset_label']);
    }

    return $settings;
  }

  /**
   * Returns an array of default or current existing values for BEF settings.
   *
   * This helps us as we add new options and prevents a lot of
   * @code
   *    if (isset($settings['new_settings'])) { ... }
   * @endcode
   * as there will be a default value at all positions in the settings array.
   * Also updates legacy settings to their new locations via
   * updateLegacySettings().
   *
   * @return array
   *   Multi-dimensional settings array.
   */
  protected function getSettings() {
    // General, sort, pagers, etc.
    $defaults = array(
      'general' => array(
        'allow_secondary' => FALSE,
        'secondary_label' => $this->t('Advanced options'),
        'autosubmit' => FALSE,
        'autosubmit_exclude_textfield' => FALSE,
        'autosubmit_hide' => FALSE,
      ),
      'sort' => array(
        'bef_format' => 'default',
        'advanced' => array(
          'collapsible' => FALSE,
          'collapsible_label' => '',
          'combine' => FALSE,
          'combine_rewrite' => '',
          'reset' => FALSE,
          'reset_label' => '',
          'is_secondary' => FALSE,
        ),
      ),
      'pager' => array(
        'bef_format' => 'default',
        'is_secondary' => FALSE,
      ),
    );

    // Update legacy settings in the exposed form settings form. This
    // keep us from losing settings when an option is put into an
    // 'advanced options' details element.
    $current = [];
    if (isset($this->options['bef'])) {
      $current = $this->updateLegacySettings($this->options['bef']);
    }

    // Collect existing values or use defaults.
    $settings = $this->setDefaults($defaults, $current);

    // Filter default values.
    $filter_defaults = array(
      'bef_format' => 'default',
      'more_options' => array(
        'placeholder_text' => '',
        'bef_select_all_none' => FALSE,
        'bef_select_all_none_nested' => FALSE,
        'bef_collapsible' => FALSE,
        'is_secondary' => FALSE,
        'rewrite' => array(
          'filter_rewrite_values' => '',
        ),
      ),
      'slider_options' => array(
        'bef_slider_min' => 0,
        'bef_slider_max' => 99999,
        'bef_slider_step' => 1,
        'bef_slider_animate' => '',
        'bef_slider_orientation' => 'horizontal',
      ),
    );

    // Go through each exposed filter and collect settings.
    /* @var \Drupal\views\Plugin\views\HandlerBase $filter */
    foreach ($this->view->display_handler->getHandlers('filter') as $label => $filter) {
      if (!$filter->isExposed()) {
        continue;
      }

      // Get existing values or use defaults.
      if (!isset($this->options['bef'][$label])) {
        // First time opening the settings form with a new filter.
        $settings[$label] = $filter_defaults;
      }
      else {
        $settings[$label] = $this->setDefaults($filter_defaults, $this->options['bef'][$label]);
      }
    }
    return $settings;
  }

  /**
   * Returns exposed form action URL object.
   *
   * @return \Drupal\Core\Url
   *   Url object.
   */
  protected function getExposedFormActionUrl() {
    if ($this->displayHandler->getRoutedDisplay()) {
      return $this->displayHandler->getUrl();
    }

    $request = \Drupal::request();
    $url = Url::createFromRequest($request);
    $url->setAbsolute();

    return $url;
  }

}
