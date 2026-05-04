<?php

declare(strict_types=1);

namespace Drupal\d_p\Generators;

use DrupalCodeGenerator\Asset\AssetCollection;
use DrupalCodeGenerator\Attribute\Generator;
use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\GeneratorType;

/**
 * Registers the drush generator for class-based paragraph setting plugins.
 */
#[Generator(
  name: 'droopler-setting-class-plugin',
  description: 'Generates a Droopler Class Setting plugin.',
  aliases: ['drosec'],
  templatePath: __DIR__,
  type: GeneratorType::MODULE_COMPONENT,
)]
final class SettingClassPluginGenerator extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  protected function generate(array &$vars, AssetCollection $assets): void {
    $ir                   = $this->createInterviewer($vars);
    $vars['machine_name'] = $ir->askMachineName();
    $vars['name']         = $ir->askName();
    $vars['plugin_label'] = $ir->askPluginLabel();
    $vars['plugin_id']    = $ir->askPluginId();
    $vars['class']        = $ir->askPluginClass(suffix: 'ParagraphSetting');

    $assets->addFile('src/Plugin/ParagraphSetting/{class}.php')
      ->template('setting-class-plugin.twig');
  }

}
