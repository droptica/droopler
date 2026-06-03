<?php

declare(strict_types=1);

namespace Drupal\d_p_subscribe_file\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\d_p_subscribe_file\Forms\SubscribeFileForm;

/**
 * Hook implementations for the d_p_subscribe_file module.
 */
class Hooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ClassResolverInterface $classResolver,
    protected readonly FormBuilderInterface $formBuilder,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly RendererInterface $renderer,
  ) {}

  /**
   * Implements hook_preprocess_HOOK() for paragraph__d_p_subscribe_file.
   */
  #[Hook('preprocess_paragraph__d_p_subscribe_file')]
  public function preprocessParagraphDpSubscribeFile(array &$variables): void {
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];

    /** @var \Drupal\d_p_subscribe_file\Forms\SubscribeFileForm $form_object */
    $form_object = $this->classResolver->getInstanceFromDefinition(SubscribeFileForm::class);
    $form_object->setParagraph($paragraph);

    $variables['subscribe_file_form'] = $this->formBuilder->getForm($form_object);
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'paragraph__d_p_subscribe_file' => [
        'base hook' => 'paragraph',
      ],
      'd_p_subscribe_file_download_page' => [
        'variables' => ['body' => NULL],
      ],
      'd_p_subscribe_file_mail' => [
        'variables' => ['body' => NULL],
      ],
    ];
  }

  /**
   * Implements hook_mail().
   */
  #[Hook('mail')]
  public function mail(string $key, array &$message, array $params): void {
    if ($key !== 'subscribe_form') {
      return;
    }

    $config = $this->configFactory->get('system.site');
    $message['from'] = $config->get('mail');
    $message['subject'] = $this->t('Hi, @name, Your @site Download Link', [
      '@name' => $params['name'],
      '@site' => $config->get('name'),
    ]);
    $message['headers']['Content-Type'] = 'text/html; charset=UTF-8; format=flowed; delsp=yes';
    $rendered = $this->renderer->render($params['body']);
    $message['body'][0] = Markup::create((string) $rendered);
  }

  /**
   * Implements hook_d_p_centered_ckeditor_widget_paragraphs().
   */
  #[Hook('d_p_centered_ckeditor_widget_paragraphs')]
  public function dpCenteredCkeditorWidgetParagraphs(array &$paragraph_types): void {
    $paragraph_types[] = 'd_p_subscribe_file';
  }

}
