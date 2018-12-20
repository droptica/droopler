<?php

namespace Drupal\search_api\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\ParseMode\ParseModePluginManager;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a contextual filter for doing fulltext searches.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("search_api_fulltext")
 */
class SearchApiFulltext extends SearchApiStandard {

  /**
   * The parse mode manager.
   *
   * @var \Drupal\search_api\ParseMode\ParseModePluginManager|null
   */
  protected $parseModeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->setParseModeManager($container->get('plugin.manager.search_api.parse_mode'));

    return $plugin;
  }

  /**
   * Retrieves the parse mode manager.
   *
   * @return \Drupal\search_api\ParseMode\ParseModePluginManager
   *   The parse mode manager.
   */
  public function getParseModeManager() {
    return $this->parseModeManager ?: \Drupal::service('plugin.manager.search_api.parse_mode');
  }

  /**
   * Sets the parse mode manager.
   *
   * @param \Drupal\search_api\ParseMode\ParseModePluginManager $parse_mode_manager
   *   The new parse mode manager.
   *
   * @return $this
   */
  public function setParseModeManager(ParseModePluginManager $parse_mode_manager) {
    $this->parseModeManager = $parse_mode_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['parse_mode'] = ['default' => 'terms'];
    $options['fields'] = ['default' => []];
    $options['conjunction'] = ['default' => 'AND'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['parse_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Parse mode'),
      '#description' => $this->t('Choose how the search keys will be parsed.'),
      '#options' => [],
      '#default_value' => $this->options['parse_mode'],
    ];
    foreach ($this->getParseModeManager()->getInstances() as $key => $mode) {
      if ($mode->isHidden()) {
        continue;
      }
      $form['parse_mode']['#options'][$key] = $mode->label();
      if ($mode->getDescription()) {
        $states['visible'][':input[name="options[parse_mode]"]']['value'] = $key;
        $form["parse_mode_{$key}_description"] = [
          '#type' => 'item',
          '#title' => $mode->label(),
          '#description' => $mode->getDescription(),
          '#states' => $states,
        ];
      }
    }

    $fields = $this->getFulltextFields();
    if (!empty($fields)) {
      $form['fields'] = [
        '#type' => 'select',
        '#title' => $this->t('Searched fields'),
        '#description' => $this->t('Select the fields that will be searched. If no fields are selected, all available fulltext fields will be searched.'),
        '#options' => $fields,
        '#size' => min(4, count($fields)),
        '#multiple' => TRUE,
        '#default_value' => $this->options['fields'],
      ];
      $form['conjunction'] = [
        '#title' => $this->t('Operator'),
        '#description' => $this->t('Determines how multiple keywords entered for the search will be combined.'),
        '#type' => 'radios',
        '#options' => [
          'AND' => $this->t('Contains all of these words'),
          'OR' => $this->t('Contains any of these words'),
        ],
        '#default_value' => $this->options['conjunction'],
      ];
    }
    else {
      $form['fields'] = [
        '#type' => 'value',
        '#value' => [],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    if ($this->options['parse_mode']) {
      $parse_mode = $this->getParseModeManager()
        ->createInstance($this->options['parse_mode']);
      $this->query->setParseMode($parse_mode);
    }
    $this->query->getParseMode()->setConjunction($this->options['conjunction']);
    if ($this->options['fields']) {
      $this->query->setFulltextFields($this->options['fields']);
    }

    $old = $this->query->getOriginalKeys();
    $this->query->keys($this->argument);
    if ($old) {
      $keys = &$this->query->getKeys();
      if (is_array($keys)) {
        $keys[] = $old;
      }
      elseif (is_array($old)) {
        // We don't support such nonsense.
      }
      else {
        $keys = "($old) ($keys)";
      }
    }
  }

  /**
   * Retrieves an options list of available fulltext fields.
   *
   * @return string[]
   *   An associative array mapping the identifiers of the index's fulltext
   *   fields to their prefixed labels.
   */
  protected function getFulltextFields() {
    $fields = [];

    if (!empty($this->query)) {
      $index = $this->query->getIndex();
    }
    else {
      $index = SearchApiQuery::getIndexFromTable($this->table);
    }

    if (!$index) {
      return [];
    }

    $fields_info = $index->getFields();
    foreach ($index->getFulltextFields() as $field_id) {
      $fields[$field_id] = $fields_info[$field_id]->getPrefixedLabel();
    }

    return $fields;
  }

}
