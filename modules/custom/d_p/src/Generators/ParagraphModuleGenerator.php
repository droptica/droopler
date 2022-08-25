<?php

namespace Drupal\d_p\Generators;

use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * The class that adds Droopler Paragraph generator to Drush.
 */
class ParagraphModuleGenerator extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  protected $name = 'droopler-paragraph-module';

  /**
   * {@inheritdoc}
   */
  protected $description = 'Generates a Droopler Paragraph module.';

  /**
   * {@inheritdoc}
   */
  protected $alias = 'dropar';

  /**
   * {@inheritdoc}
   */
  protected $templatePath = __DIR__;

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $questions = Utils::moduleQuestions();
    $questions['preprocess'] = new ConfirmationQuestion('Would you like to create a sample preprocess function to read paragraph settings?', TRUE);

    $vars = &$this->collectVars($input, $output, $questions);
    $vars['template'] = 'paragraph--' . str_replace('_', '-', $vars['machine_name']);

    $this->addFile()
      ->path('{machine_name}.module')
      ->template('paragraph-module.twig');

    $this->addFile()
      ->path('{machine_name}.info.yml')
      ->template('paragraph-info.twig');

    $this->addFile()
      ->path('templates/{template}.html.twig')
      ->template('paragraph-template.twig');
  }

}
