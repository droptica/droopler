<?php

namespace Drupal\d_p\Generators;

use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The class that adds Droopler Class Setting Plugin generator to Drush.
 */
class SettingClassPluginGenerator extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  protected $name = 'droopler-setting-class-plugin';

  /**
   * {@inheritdoc}
   */
  protected $description = 'Generates a Droopler Class Setting plugin.';

  /**
   * {@inheritdoc}
   */
  protected $alias = 'drosec';

  /**
   * {@inheritdoc}
   */
  protected $templatePath = __DIR__;

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $questions = Utils::moduleQuestions() + Utils::pluginQuestions();

    $this->collectVars($input, $output, $questions);

    $this->addFile()
      ->path('src/Plugin/ParagraphSetting/{class}.php')
      ->template('setting-class-plugin.twig');
  }

}
