<?php

declare(strict_types=1);

namespace Drupal\d_p\Generators;

use DrupalCodeGenerator\Asset\AssetCollection;
use DrupalCodeGenerator\Attribute\Generator;
use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\GeneratorType;

/**
 * Registers the drush generator for new Droopler paragraph modules.
 */
#[Generator(
  name: 'droopler-paragraph-module',
  description: 'Generates a Droopler Paragraph module.',
  aliases: ['dropar'],
  templatePath: __DIR__,
  type: GeneratorType::OTHER,
)]
final class ParagraphModuleGenerator extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  protected function generate(array &$vars, AssetCollection $assets): void {
    $ir                   = $this->createInterviewer($vars);
    $vars['machine_name'] = $ir->askMachineName();
    $vars['name']         = $ir->askName();
    $vars['preprocess']   = $ir->confirm('Would you like to create a sample preprocess function to read paragraph settings?', TRUE);
    $vars['template']     = 'paragraph--' . str_replace('_', '-', $vars['machine_name']);

    $assets->addFile('src/Hook/Hooks.php')
      ->template('paragraph-module.twig');
    $assets->addFile('{machine_name}.info.yml')
      ->template('paragraph-info.twig');
    $assets->addFile('templates/{template}.html.twig')
      ->template('paragraph-template.twig');
  }

}
