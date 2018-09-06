<?php

namespace Drupal\paragraphs;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ParagraphsBehaviorBase extends PluginBase implements ParagraphsBehaviorInterface, ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a ParagraphsBehaviorBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration += $this->defaultConfiguration();
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) { }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) { }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(&$variables) { }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsIcon(Paragraph $paragraph) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $filtered_values = $this->filterBehaviorFormSubmitValues($paragraph, $form, $form_state);

    $paragraph->setBehaviorSettings($this->getPluginId(), $filtered_values);
  }

  /**
   * Removes default behavior form values before storing the user-set ones.
   *
   * Default implementation considers a value to be default if and only if it is
   * an empty value. Behavior plugins that do not consider all empty values to
   * be default should override this method or
   * \Drupal\paragraphs\ParagraphsBehaviorBase::submitBehaviorForm.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An associative array of values submitted to the form with all empty
   *   leaves removed. Subarrays that only contain empty leaves are also
   *   removed.
   */
  protected function filterBehaviorFormSubmitValues(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    // Keeps removing empty leaves, until there are none left. So if a subarray
    // only contains empty leaves, that subarray itself will be removed.
    $new_array = $form_state->getValues();

    do {
      $old_array = $new_array;
      $new_array = NestedArray::filter($old_array);
    }
    while ($new_array !== $old_array);

    return $new_array;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNameOptions(ParagraphsType $paragraphs_type, $field_type = NULL) {
    $fields = [];
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('paragraph', $paragraphs_type->id());
    foreach ($field_definitions as $name => $definition) {
      if ($field_definitions[$name] instanceof FieldConfigInterface) {
        if (empty($field_type) || $definition->getType() == $field_type) {
          $fields[$name] = $definition->getLabel();
        }
      }
    }
    return $fields;
  }

}
