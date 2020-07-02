<?php

namespace Drupal\droopler\Installer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleExtensionList;

class AdditionalComponentsForm extends FormBase {

  use StringTranslationTrait;

  /**
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
   * @var string[]
   *   Modules to enable directly from Droopler installator.
   */
  private $modules = [
    'd_blog' => 'This module allows you to create professional blog posts, with all Droopler paragraphs',
    'd_product' => 'This module provides the way to showcase your products without advanced e-commerce features',
    'd_commerce' => 'Out-of-the-box support for Commerce module for Drupal.',
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

    $form['#title'] = $this->t('Install components');
    $form['install'] = [
      '#type' => 'container',
    ];

    foreach ($this->modules as $name => $description) {
      if ($name == 'd_commerce' && !$this->moduleExist('d_commerce')) {
        $description = $this->t('Out-of-the-box support for Commerce module for Drupal. You have to install additional modules to enable this checkbox. <a href="@readme" target="_blank">Read more</a>.',
        ['@readme' => 'https://github.com/droptica/droopler/blob/master/README.md']);
      }

      $form['install']['module_' . $name] = [
        '#type' => 'checkbox',
        '#title' => $name,
        '#description' => $this->t('@description', ['@description' => $description]),
        '#disabled' => !$this->moduleExist($name),
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
      '#type' => 'actions'
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

    $install_modules = $additional_modules = $documentation_module = $methods = [];
    foreach($this->modules as $name => $desc)  {
      if ($values['module_' . $name]) {
        $install_modules[] = $name;
      }
    }

    if ($values['init_content']) {
      $additional_modules = [
        'd_content',
        'd_content_init',
        'd_blog_init',
        'd_product_init',
        'd_demo',
        'd_product',
      ];
      $methods = [
        'd_content_init_create_all'
      ];
    }

    if ($values['documentation']) {
      $additional_modules = array_merge($additional_modules, [
        'd_content',
        'd_content_init',
        'd_documentation',
      ]);
      $methods = array_merge($methods, [
        'd_content_init_create_all',
      ]);
    }

    $install_state[0]['droopler_additional_components'] = array_unique($methods);
    $install_state[0]['droopler_additional_modules'] = array_unique(array_merge($install_modules, $additional_modules), SORT_REGULAR);
    $build_info['args'] = $install_state;
    $form_state->setBuildInfo($build_info);
  }

  /**
   * Check if module (enabled or not) exist in Drupal.
   *
   * @param string $module_name
   *   Module name.
   * @return \Drupal\Core\Extension\Extension|mixed
   *   Return true if module exist.
   */
  private function moduleExist($module_name) {
    $modules_data = $this->moduleExtensionList->reset()->getList();
    return !empty($modules_data[$module_name]);
  }
}
