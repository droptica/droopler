<?php

namespace Drupal\search_api\Processor;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValueInterface;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\ConditionInterface;
use Drupal\search_api\Query\QueryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for processors that work on individual fields.
 *
 * A form element to select the fields to run on is provided, as well as easily
 * overridable methods to provide the actual functionality. Subclasses can
 * override any of these methods (or the interface methods themselves, of
 * course) to provide their specific functionality:
 * - processField()
 * - processFieldValue()
 * - processKeys()
 * - processKey()
 * - processConditions()
 * - processConditionValue()
 * - process()
 *
 * The following methods can be used for specific logic regarding the fields to
 * run on:
 * - testField()
 * - testType()
 *
 * Processors extending this class should usually support the following stages:
 * - pre_index_save
 * - preprocess_index
 * - preprocess_query
 */
abstract class FieldsProcessorPluginBase extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * The data type helper.
   *
   * @var \Drupal\search_api\Utility\DataTypeHelperInterface|null
   */
  protected $dataTypeHelper;

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface|null
   */
  protected $elementInfoManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setDataTypeHelper($container->get('search_api.data_type_helper'));
    $processor->setElementInfoManager($container->get('plugin.manager.element_info'));

    return $processor;
  }

  /**
   * Retrieves the data type helper.
   *
   * @return \Drupal\search_api\Utility\DataTypeHelperInterface
   *   The data type helper.
   */
  public function getDataTypeHelper() {
    return $this->dataTypeHelper ?: \Drupal::service('search_api.data_type_helper');
  }

  /**
   * Sets the data type helper.
   *
   * @param \Drupal\search_api\Utility\DataTypeHelperInterface $data_type_helper
   *   The new data type helper.
   *
   * @return $this
   */
  public function setDataTypeHelper(DataTypeHelperInterface $data_type_helper) {
    $this->dataTypeHelper = $data_type_helper;
    return $this;
  }

  /**
   * Retrieves the element info manager.
   *
   * @return \Drupal\Core\Render\ElementInfoManagerInterface
   *   The element info manager.
   */
  public function getElementInfoManager() {
    return $this->elementInfoManager ?: \Drupal::service('plugin.manager.element_info');
  }

  /**
   * Sets the element info manager.
   *
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info_manager
   *   The new element info manager.
   *
   * @return $this
   */
  public function setElementInfoManager(ElementInfoManagerInterface $element_info_manager) {
    $this->elementInfoManager = $element_info_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    parent::preIndexSave();

    // If the "all supported fields" option is checked, we need to reset the
    // fields array and fill it with all fields defined on the index.
    if ($this->configuration['all_fields']) {
      $this->configuration['fields'] = [];
      foreach ($this->index->getFields() as $field_id => $field) {
        if (!$field->isHidden() && $this->testType($field->getType())) {
          $this->configuration['fields'][] = $field_id;
        }
      }
      // No need to explicitly check for field renames.
      return;
    }

    // Otherwise, if no fields were checked, we also have nothing to do here.
    if (empty($this->configuration['fields'])) {
      return;
    }

    // Apply field ID changes to the fields selected for this processor.
    $selected_fields = array_flip($this->configuration['fields']);
    $renames = $this->index->getFieldRenames();
    $renames = array_intersect_key($renames, $selected_fields);
    if ($renames) {
      $new_fields = array_keys(array_diff_key($selected_fields, $renames));
      $new_fields = array_merge($new_fields, array_values($renames));
      $this->configuration['fields'] = $new_fields;
    }

    // Remove fields from the configuration that are no longer compatible with
    // this processor (or no longer present on the index at all).
    foreach ($this->configuration['fields'] as $i => $field_id) {
      $field = $this->index->getField($field_id);
      if ($field === NULL
        || $field->isHidden()
        || !$this->testType($field->getType())) {
        unset($this->configuration['fields'][$i]);
      }
    }
    // Serialization might be problematic if the array indices aren't completely
    // sequential.
    $this->configuration['fields'] = array_values($this->configuration['fields']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();

    // @todo Add "fields" default here, too, and figure out how to replace
    //   current "magic" code dealing with unset option (or whether we even need
    //   to). See #2881665.
    $configuration += [
      'all_fields' => FALSE,
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $fields = $this->index->getFields();
    $field_options = [];
    $default_fields = [];
    $all_fields = $this->configuration['all_fields'];
    $fields_configured = isset($this->configuration['fields']);
    if ($fields_configured && !$all_fields) {
      $default_fields = $this->configuration['fields'];
    }
    foreach ($fields as $name => $field) {
      if (!$field->isHidden() && $this->testType($field->getType())) {
        $field_options[$name] = Html::escape($field->getPrefixedLabel());
        if ($all_fields || (!$fields_configured && $this->testField($name, $field))) {
          $default_fields[] = $name;
        }
      }
    }

    $form['all_fields'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable on all supported fields'),
      '#description' => $this->t('Enable this processor for all supported fields. This will also automatically update the setting when new supported fields are added to the index.'),
      '#default_value' => $all_fields,
    ];

    // Unfortunately, Form API doesn't seem to automatically add the default
    // "#pre_render" callbacks to an element if we set some of our own. We
    // therefore need to explicitly include those, too.
    $pre_render = $this->getElementInfoManager()
      ->getInfoProperty('checkboxes', '#pre_render', []);
    $pre_render[] = [static::class, 'preRenderFieldsCheckboxes'];
    $form['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable this processor on the following fields'),
      '#description' => $this->t("Note: The Search API currently doesn't support per-field keywords processing, so this setting will be ignored when preprocessing search keywords. It is therefore usually recommended that you enable the processor for all fields that you intend to use as fulltext search fields, to avoid undesired consequences."),
      '#options' => $field_options,
      '#default_value' => $default_fields,
      '#pre_render' => $pre_render,
    ];

    return $form;
  }

  /**
   * Preprocesses the "fields" checkboxes before rendering.
   *
   * Adds "#states" settings to disable the checkboxes when "all_fields" is
   * checked.
   *
   * @param array $element
   *   The form element to process.
   *
   * @return array
   *   The processed form element.
   */
  public static function preRenderFieldsCheckboxes(array $element) {
    $parents = $element['#parents'];
    array_pop($parents);
    $parents[] = 'all_fields';
    $name = array_shift($parents);
    if ($parents) {
      $name .= '[' . implode('][', $parents) . ']';
    }
    $selector = ":input[name=\"$name\"]";
    $element['#states'] = [
      'invisible' => [
        $selector => ['checked' => TRUE],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('all_fields')) {
      $fields = array_filter($form_state->getValue('fields', []));
      if ($fields) {
        $fields = array_keys($fields);
      }
    }
    else {
      $fields = array_keys($form['fields']['#options']);
    }
    $form_state->setValue('fields', $fields);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    // Annoyingly, this doc comment is needed for PHPStorm. See
    // http://youtrack.jetbrains.com/issue/WI-23586
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      foreach ($item->getFields() as $name => $field) {
        if ($this->testField($name, $field)) {
          $this->processField($field);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    $keys = &$query->getKeys();
    if (isset($keys)) {
      $this->processKeys($keys);
    }
    $conditions = $query->getConditionGroup();
    $this->processConditions($conditions->getConditions());
  }

  /**
   * Processes a single field's value.
   *
   * Calls process() either for each value, or each token, depending on the
   * type. Also takes care of extracting list values and of fusing returned
   * tokens back into a one-dimensional array.
   *
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   The field to process.
   */
  protected function processField(FieldInterface $field) {
    $values = $field->getValues();
    $type = $field->getType();

    foreach ($values as $i => &$value) {
      // We restore the field's type for each run of the loop since we need the
      // unchanged one as long as the current field value hasn't been updated.
      if ($value instanceof TextValueInterface) {
        $tokens = $value->getTokens();
        if ($tokens !== NULL) {
          $new_tokens = [];
          foreach ($tokens as $token) {
            $token_text = $token->getText();
            $this->processFieldValue($token_text, $type);
            if (is_scalar($token_text)) {
              if ($token_text !== '') {
                $token->setText($token_text);
                $new_tokens[] = $token;
              }
            }
            else {
              $base_boost = $token->getBoost();
              /** @var \Drupal\search_api\Plugin\search_api\data_type\value\TextTokenInterface $new_token */
              foreach ($token_text as $new_token) {
                if ($new_token->getText() !== '') {
                  $new_token->setBoost($new_token->getBoost() * $base_boost);
                  $new_tokens[] = $new_token;
                }
              }
            }
          }
          $value->setTokens($new_tokens);
        }
        else {
          $text = $value->getText();
          if ($text !== '') {
            $this->processFieldValue($text, $type);
            if ($text === '') {
              unset($values[$i]);
            }
            elseif (is_scalar($text)) {
              $value->setText($text);
            }
            else {
              $value->setTokens($text);
            }
          }
        }
      }
      elseif ($value !== '') {
        $this->processFieldValue($value, $type);

        if ($value === '') {
          unset($values[$i]);
        }
      }
    }

    $field->setValues(array_values($values));
  }

  /**
   * Preprocesses the search keywords.
   *
   * Calls processKey() for individual strings.
   *
   * @param array|string $keys
   *   Either a parsed keys array, or a single keywords string.
   */
  protected function processKeys(&$keys) {
    if (is_array($keys)) {
      foreach ($keys as $key => &$v) {
        if (Element::child($key)) {
          $this->processKeys($v);
          if ($v === '') {
            unset($keys[$key]);
          }
        }
      }
    }
    else {
      $this->processKey($keys);
    }
  }

  /**
   * Preprocesses the query conditions.
   *
   * @param \Drupal\search_api\Query\ConditionInterface[]|\Drupal\search_api\Query\ConditionGroupInterface[] $conditions
   *   An array of conditions, as returned by
   *   \Drupal\search_api\Query\ConditionGroupInterface::getConditions(),
   *   passed by reference.
   */
  protected function processConditions(array &$conditions) {
    $fields = $this->index->getFields();
    foreach ($conditions as $key => &$condition) {
      if ($condition instanceof ConditionInterface) {
        $field = $condition->getField();
        if (isset($fields[$field]) && $this->testField($field, $fields[$field])) {
          // We want to allow processors also to easily remove complete
          // conditions. However, we can't use empty() or the like, as that
          // would sort out filters for 0 or NULL. So we specifically check only
          // for the empty string, and we also make sure the condition value was
          // actually changed by storing whether it was empty before.
          $value = $condition->getValue();
          $empty_string = $value === '';
          $this->processConditionValue($value);

          // Conditions with (NOT) BETWEEN operator deserve special attention,
          // as it seems unlikely that it makes sense to completely remove them.
          // Processors that remove values are normally indicating that this
          // value can't be in the index – but that's irrelevant for (NOT)
          // BETWEEN conditions, as any value between the two bounds could still
          // be included. We therefore never remove a (NOT) BETWEEN condition
          // and also ignore it when one of the two values got removed.
          // Processors who need different behavior have to override this
          // method.
          $between_operator = in_array($condition->getOperator(), ['BETWEEN', 'NOT BETWEEN']);
          if ($between_operator && (!is_array($value) || count($value) < 2)) {
            continue;
          }

          if ($value === '' && !$empty_string) {
            unset($conditions[$key]);
          }
          else {
            $condition->setValue($value);
          }
        }
      }
      elseif ($condition instanceof ConditionGroupInterface) {
        $child_conditions = &$condition->getConditions();
        $this->processConditions($child_conditions);
      }
    }
  }

  /**
   * Tests whether a certain field should be processed.
   *
   * @param string $name
   *   The field's ID.
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   The field's information.
   *
   * @return bool
   *   TRUE if the field should be processed, FALSE otherwise.
   */
  protected function testField($name, FieldInterface $field) {
    if (!isset($this->configuration['fields'])) {
      return !$field->isHidden() && $this->testType($field->getType());
    }
    return in_array($name, $this->configuration['fields'], TRUE);
  }

  /**
   * Determines whether a field of a certain type should be preprocessed.
   *
   * The default implementation returns TRUE for "text" and "string".
   *
   * @param string $type
   *   The type of the field (either when preprocessing the field at index time,
   *   or a condition on the field at query time).
   *
   * @return bool
   *   TRUE if fields of that type should be processed, FALSE otherwise.
   */
  protected function testType($type) {
    return $this->getDataTypeHelper()
      ->isTextType($type, ['text', 'string']);
  }

  /**
   * Processes a single text element in a field.
   *
   * The default implementation just calls process().
   *
   * @param string $value
   *   The string value to preprocess, as a reference. Can be manipulated
   *   directly, nothing has to be returned. Can either be left a string, or
   *   changed into an array of
   *   \Drupal\search_api\Plugin\search_api\data_type\value\TextTokenInterface
   *   objects. Returning anything else will result in undefined behavior.
   * @param string $type
   *   The field's data type.
   */
  protected function processFieldValue(&$value, $type) {
    $this->process($value);
  }

  /**
   * Processes a single search keyword.
   *
   * The default implementation just calls process().
   *
   * @param string $value
   *   The string value to preprocess, as a reference. Can be manipulated
   *   directly, nothing has to be returned. Can either be left a string, or be
   *   changed into a nested keys array, as defined by
   *   \Drupal\search_api\ParseMode\ParseModeInterface::parseInput().
   */
  protected function processKey(&$value) {
    $this->process($value);
  }

  /**
   * Processes a single condition value.
   *
   * Called for processing a single condition value. The default implementation
   * just calls process().
   *
   * @param mixed $value
   *   The condition value to preprocess, as a reference. Can be manipulated
   *   directly, nothing has to be returned. Set to an empty string to remove
   *   the condition.
   */
  protected function processConditionValue(&$value) {
    if (is_array($value)) {
      if ($value) {
        foreach ($value as $i => $part) {
          $this->processConditionValue($value[$i]);
          if ($value[$i] !== $part && $value[$i] === '') {
            unset($value[$i]);
          }
        }
        if (!$value) {
          $value = '';
        }
      }
    }
    else {
      $this->process($value);
    }
  }

  /**
   * Processes a single string value.
   *
   * This method is ultimately called for all text by the standard
   * implementation, and does nothing by default.
   *
   * @param string $value
   *   The string value to preprocess, as a reference. Can be manipulated
   *   directly, nothing has to be returned. Since this can be called for all
   *   value types, $value has to remain a string.
   */
  protected function process(&$value) {}

}
