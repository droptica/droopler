<?php

namespace Drupal\paragraphs\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Messenger\Messenger;
use Drupal\field_ui\FieldUI;
use Drupal\paragraphs\ParagraphsBehaviorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for paragraph type forms.
 */
class ParagraphsTypeForm extends EntityForm {

  /**
   * The paragraphs behavior plugin manager service.
   *
   * @var \Drupal\paragraphs\ParagraphsBehaviorManager
   */
  protected $paragraphsBehaviorManager;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\paragraphs\ParagraphsTypeInterface
   */
  protected $entity;

  /**
   * Provides messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * GeneralSettingsForm constructor.
   *
   * @param \Drupal\paragraphs\ParagraphsBehaviorManager $paragraphs_behavior_manager
   *   The paragraphs type feature manager service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(ParagraphsBehaviorManager $paragraphs_behavior_manager, Messenger $messenger) {
    $this->paragraphsBehaviorManager = $paragraphs_behavior_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.paragraphs.behavior'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $paragraphs_type = $this->entity;

    if (!$paragraphs_type->isNew()) {
      $form['#title'] = (t('Edit %title paragraph type', [
        '%title' => $paragraphs_type->label(),
      ]));
    }

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $paragraphs_type->label(),
      '#description' => $this->t("Label for the Paragraphs type."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $paragraphs_type->id(),
      '#machine_name' => array(
        'exists' => 'paragraphs_type_load',
      ),
      '#maxlength' => 32,
      '#disabled' => !$paragraphs_type->isNew(),
    );

    $form['icon_file'] = [
      '#title' => $this->t('Paragraph type icon'),
      '#type' => 'managed_file',
      '#upload_location' => 'public://paragraphs_type_icon/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg svg'],
      ],
    ];

    if ($file = $this->entity->getIconFile()) {
      $form['icon_file']['#default_value'] = ['target_id' => $file->id()];
    }

    $form['description'] = [
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $paragraphs_type->getDescription(),
      '#description' => t('This text will be displayed on the <em>Add new paragraph</em> page.'),
    ];

    // Loop over the plugins that can be applied to this paragraph type.
    if ($behavior_plugin_definitions = $this->paragraphsBehaviorManager->getApplicableDefinitions($paragraphs_type)) {
      $form['message'] = [
        '#type' => 'container',
        '#markup' => $this->t('Behavior plugins are only supported by the EXPERIMENTAL paragraphs widget.'),
        '#attributes' => ['class' => ['messages', 'messages--warning']]
      ];
      $form['behavior_plugins'] = [
        '#type' => 'details',
        '#title' => $this->t('Behaviors'),
        '#tree' => TRUE,
        '#open' => TRUE
      ];
      $config = $paragraphs_type->get('behavior_plugins');
      // Alphabetically sort plugins by plugin label.
      uasort($behavior_plugin_definitions, function ($a, $b) {
        return strcmp($a['label'], $b['label']);
      });
      foreach ($behavior_plugin_definitions as $id => $behavior_plugin_definition) {
        $description = $behavior_plugin_definition['description'];
        $form['behavior_plugins'][$id]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $behavior_plugin_definition['label'],
          '#title_display' => 'after',
          '#description' => $description,
          '#default_value' => !empty($config[$id]['enabled']),
        ];
        $form['behavior_plugins'][$id]['settings'] = [];
        $subform_state = SubformState::createForSubform($form['behavior_plugins'][$id]['settings'], $form, $form_state);
        $behavior_plugin = $paragraphs_type->getBehaviorPlugin($id);
        if ($settings = $behavior_plugin->buildConfigurationForm($form['behavior_plugins'][$id]['settings'], $subform_state)) {
          $form['behavior_plugins'][$id]['settings'] = $settings + [
            '#type' => 'fieldset',
            '#title' => $behavior_plugin_definition['label'],
            '#states' => [
              'visible' => [
                  ':input[name="behavior_plugins[' . $id . '][enabled]"]' => ['checked' => TRUE],
              ]
            ]
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $paragraphs_type = $this->entity;

    $icon_file = $form_state->getValue(['icon_file', '0']);
    // Set the file UUID to the paragraph configuration.
    if (!empty($icon_file) && $file = $this->entityTypeManager->getStorage('file')->load($icon_file)) {
      $paragraphs_type->set('icon_uuid', $file->uuid());
    }
    else {
      $paragraphs_type->set('icon_uuid', NULL);
    }

    if ($behavior_plugin_definitions = $this->paragraphsBehaviorManager->getApplicableDefinitions($paragraphs_type)) {
      foreach ($behavior_plugin_definitions as $id => $behavior_plugin_definition) {
        // Only validate if the plugin is enabled and has settings.
        if (isset($form['behavior_plugins'][$id]['settings']) && $form_state->getValue(['behavior_plugins', $id, 'enabled'])) {
          $subform_state = SubformState::createForSubform($form['behavior_plugins'][$id]['settings'], $form, $form_state);
          $behavior_plugin = $paragraphs_type->getBehaviorPlugin($id);
          $behavior_plugin->validateConfigurationForm($form['behavior_plugins'][$id]['settings'], $subform_state);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $paragraphs_type = $this->entity;

    if ($behavior_plugin_definitions = $this->paragraphsBehaviorManager->getApplicableDefinitions($paragraphs_type)) {
      foreach ($behavior_plugin_definitions as $id => $behavior_plugin_definition) {
        $behavior_plugin = $paragraphs_type->getBehaviorPlugin($id);

        // If the behavior is enabled, initialize the configuration with the
        // enabled key and then let it process the form input.
        if ($form_state->getValue(['behavior_plugins', $id, 'enabled'])) {
          $behavior_plugin->setConfiguration(['enabled' => TRUE]);
          if (isset($form['behavior_plugins'][$id]['settings'])) {
            $subform_state = SubformState::createForSubform($form['behavior_plugins'][$id]['settings'], $form, $form_state);
            $behavior_plugin->submitConfigurationForm($form['behavior_plugins'][$id]['settings'], $subform_state);
          }
        }
        else {
          // The plugin is not enabled, reset to default configuration.
          $behavior_plugin->setConfiguration([]);
        }
      }
    }

    $status = $paragraphs_type->save();
    $this->messenger->addMessage($this->t('Saved the %label Paragraphs type.', array(
      '%label' => $paragraphs_type->label(),
    )));
    if (($status == SAVED_NEW && \Drupal::moduleHandler()->moduleExists('field_ui'))
      && $route_info = FieldUI::getOverviewRouteInfo('paragraph', $paragraphs_type->id())) {
      $form_state->setRedirectUrl($route_info);
    }
    else {
      $form_state->setRedirect('entity.paragraphs_type.collection');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $form = parent::actions($form, $form_state);

    // We want to display the button only on add page.
    if ($this->entity->isNew() && \Drupal::moduleHandler()->moduleExists('field_ui')) {
      $form['submit']['#value'] = $this->t('Save and manage fields');
    }

    return $form;
  }

}
