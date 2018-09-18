<?php

namespace Drupal\modules_weight\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\modules_weight\Utility\FormElement;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\modules_weight\ModulesWeightInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Builds the form to configure the Modules weight.
 */
class ModulesListForm extends FormBase {

  /**
   * Drupal\modules_weight\ModulesWeightInterface definition.
   *
   * @var Drupal\modules_weight\ModulesWeightInterface
   */
  protected $modulesWeight;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ModulesWeight object.
   *
   * @param Drupal\modules_weight\ModulesWeightInterface $modules_weight
   *   The modules weight.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ModulesWeightInterface $modules_weight, ConfigFactoryInterface $config_factory) {
    $this->modulesWeight = $modules_weight;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('modules_weight'), $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'modules_weight_modules_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // The table header.
    $header = [
      $this->t('Name'),
      [
        'data' => $this->t('Description'),
        // Hidding the description on narrow width devices.
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      $this->t('Weight'),
      [
        'data' => $this->t('Package'),
        // Hidding the description on narrow width devices.
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
    ];

    // The table.
    $form['modules'] = [
      '#type' => 'table',
      '#header' => $header,
      '#sticky' => TRUE,
    ];

    // Getting the config to know if we should show or not the core modules.
    $show_core_modules = $this->configFactory->get('modules_weight.settings')->get('show_system_modules');
    // Getting the module list.
    $modules = $this->modulesWeight->getModulesList($show_core_modules);
    // Iterate over each module.
    foreach ($modules as $filename => $module) {
      // The rows info.
      // Module name.
      $form['modules'][$filename]['name'] = [
        '#markup' => $module['name'],
      ];
      // Module description.
      $form['modules'][$filename]['description'] = [
        '#markup' => $module['description'],
      ];
      // Module weight.
      $form['modules'][$filename]['weight'] = [
        '#type' => 'weight',
        '#default_value' => $module['weight'],
        '#delta' => FormElement::getMaxDelta($module['weight']),
      ];
      // Module old weight value, used to see if we need to update the weight.
      $form['old_weight_value'][$filename] = [
        '#type' => 'hidden',
        '#value' => $module['weight'],
      ];
      // Module package.
      $form['modules'][$filename]['package'] = [
        '#markup' => $module['package'],
      ];
    }
    // Making $form['old_weight_value'] non flat.
    // Read more: https://www.drupal.org/docs/7/api/form-api/tree-and-parents .
    $form['old_weight_value']['#tree'] = TRUE;

    // The form button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Changes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Variable to see if we have uptaded some module weight.
    $printed = FALSE;
    // The modules information.
    $modules_info = $form_state->getValue('modules');
    // The old values information.
    $old_weight_value = $form_state->getValue('old_weight_value');

    foreach ($modules_info as $module => $values) {
      // Checking if a value has changed.
      if ($modules_info[$module]['weight'] != $old_weight_value[$module]) {
        // Setting the new weight.
        module_set_weight($module, $values['weight']);

        // Printing the update message.
        if (!$printed) {
          drupal_set_message($this->t('The modules weight was updated.'));
          $printed = TRUE;
        }

        $variables = [
          '@module_name' => system_get_info('module', $module)['name'],
          '@weight' => $values['weight'],
        ];
        // Printing information about the modules weight.
        drupal_set_message($this->t('@module_name have now as weight: @weight', $variables));
      }
    }
    // Printing message if there are not module that has changed.
    if (!$printed) {
      drupal_set_message($this->t("You don't have changed the weight for any module."), 'warning');
    }
  }

}
