<?php

namespace Drupal\d_p\Generators;

use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The class that adds Droopler Setting Plugin generator to Drush.
 */
class SettingPluginGenerator extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  protected $name = 'droopler-setting-plugin';

  /**
   * {@inheritdoc}
   */
  protected $description = 'Generates a Droopler Setting plugin.';

  /**
   * {@inheritdoc}
   */
  protected $alias = 'drosep';

  /**
   * {@inheritdoc}
   */
  protected $templatePath = __DIR__;

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $questions = Utils::moduleQuestions() + Utils::pluginQuestions();
    $vars = &$this->collectVars($input, $output, $questions);

    $this->addFile()
      ->path('src/Plugin/ParagraphSetting/{class}.php')
      ->template('setting-plugin.twig');
  }

}
