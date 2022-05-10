<?php

namespace Drupal\droopler\Installer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleExtensionList;

/**
 * A form class to customize Droopler installation process.
 */
class AdditionalComponentsForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  private $moduleExtensionList;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   */
  public function __construct(ModuleExtensionList $module_extension_list) {
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.list.module')
    );
  }

  /**
   * Modules to enable directly from Droopler installator.
   *
   * @var string[]
   */
  private $modules = [
    'd_blog' => 'This module allows you to create professional blog posts, with all Droopler paragraphs',
    'd_product' => 'This module provides the way to showcase your products without advanced e-commerce features',
    'd_commerce' => 'Out-of-the-box support for Commerce module for Drupal.',
  ];

  /**
   * List of all d_commerce dependencies.
   *
   * @var string[]
   */
  private $commerceModules = [
    'commerce',
    'commerce_cart',
    'commerce_checkout',
    'commerce_payment',
    'commerce_price',
    'commerce_product',
    'commerce_promotion',
    'commerce_tax',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'droopler_additional_modules_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->messenger()->deleteAll();

    $form['#title'] = $this->t('Install & configure Droopler components');

    foreach ($this->modules as $name => $description) {
      $disabled = !$this->moduleExist($name);
      if ($name == 'd_commerce' && !$this->modulesExists($this->commerceModules)) {
        $description = $this->t('Out-of-the-box support for Commerce module for Drupal. You have to install additional modules to enable this checkbox. <a href="@readme" target="_blank">Read more</a>.',
        ['@readme' => 'https://github.com/droptica/droopler/blob/master/README.md']);
        $disabled = TRUE;
      }

      $form['install']['module_' . $name] = [
        '#type' => 'checkbox',
        '#title' => $name,
        '#description' => $this->t('@description', ['@description' => $description]),
        '#disabled' => $disabled,
      ];
    }

    $form['install']['init_content'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Install example content'),
      '#description' => $this->t('Install the profile along with some example nodes and blocks, demonstrating the usage of Droopler.'),
    ];

    $form['install']['documentation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Install documentation'),
      '#description' => $this->t('Include some nodes with documentation of all paragraphs used in Droopler.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $build_info = $form_state->getBuildInfo();
    $install_state = $build_info['args'];

    $install_modules = $additional_modules = $documentation_module = [];
    foreach ($this->modules as $name => $desc) {
      if ($values['module_' . $name]) {
        $install_modules[] = $name;
      }
    }

    $install_state[0]['droopler_init_content'] = 0;
    if ($values['init_content']) {
      $install_state[0]['droopler_init_content'] = 1;
      $additional_modules = [
        'd_demo',
      ];
      if ($values['module_d_blog']) {
        $additional_modules[] = 'd_blog_init';
      }
      if ($values['module_d_product']) {
        $additional_modules[] = 'd_product_init';
      }
    }

    if ($values['documentation']) {
      $install_state[0]['droopler_init_content'] = 1;
      $additional_modules = array_merge($additional_modules, [
        'd_documentation',
      ]);
    }

    $install_state[0]['droopler_additional_modules'] = array_unique(array_merge($install_modules, $additional_modules), SORT_REGULAR);
    $build_info['args'] = $install_state;
    $form_state->setBuildInfo($build_info);
  }

  /**
   * Check if module (enabled or not) exist in Drupal.
   *
   * @param string $module_name
   *   Module name.
   *
   * @return \Drupal\Core\Extension\Extension|mixed
   *   Return TRUE if module exist.
   */
  private function moduleExist($module_name) {
    $modules_data = $this->moduleExtensionList->getList();
    return !empty($modules_data[$module_name]);
  }

  /**
   * Check if all modules exists.
   *
   * @param array $modules
   *   List of modules.
   *
   * @return bool
   *   Return TRUE if all modules exist.
   */
  private function modulesExists(array $modules) {
    foreach ($modules as $module) {
      if (!$this->moduleExist($module)) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
