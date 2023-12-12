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
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   */
  public function __construct(
    private readonly ModuleExtensionList $moduleExtensionList
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.list.module')
    );
  }

  /**
   * Modules to enable directly from Droopler installer.
   *
   * @var string[]
   */
  private array $modules = [
    'd_blog' => [
      'description' => 'This module allows you to create professional blog posts, with all Droopler paragraphs',
      'dependencies' => [
        'tvi',
      ],
    ],
    'd_product' => [
      'description' => 'This module provides the way to showcase your products without advanced e-commerce features',
      'dependencies' => [
        'better_exposed_filters',
        'facets',
        'search_api',
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'droopler_additional_modules_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $this->messenger()->deleteAll();

    $form['#title'] = $this->t('Install & configure Droopler components');

    foreach ($this->modules as $name => $data) {
      $disabled = !$this->moduleExist($name);

      if (!empty($data['dependencies']) && !$this->modulesExists($data['dependencies'])) {
        $description = $this->t('Out-of-the-box support for @name module for Drupal. You have to install additional modules to enable this checkbox. <a href="@readme" target="_blank">Read more</a>.', [
          '@name' => $name,
          '@readme' => 'https://github.com/droptica/droopler/blob/master/README.md',
        ]);
        $disabled = TRUE;
      }

      $form['install']['module_' . $name] = [
        '#type' => 'checkbox',
        '#title' => $name,
        '#description' => $this->t('@description', ['@description' => $description ?? $data['description']]),
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

    $install_modules = $additional_modules = [];
    foreach ($this->modules as $name => $data) {
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
   * @return bool
   *   Return TRUE if module exist.
   */
  private function moduleExist(string $module_name): bool {
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
  private function modulesExists(array $modules): bool {
    foreach ($modules as $module) {
      if (!$this->moduleExist($module)) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
