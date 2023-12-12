<?php

declare(strict_types = 1);

namespace Drupal\d_p_subscribe_file\Controller;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\d_p\Enum\ButtonTypeEnum;
use Drupal\d_p_subscribe_file\Entity\SubscribeFileEntity;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class DownloadFile controller.
 */
class DownloadFile extends ControllerBase {

  /**
   * Download file constructor.
   *
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer object.
   */
  public function __construct(protected Renderer $renderer) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
    );
  }

  /**
   * Check link to download file.
   *
   * @param string $link_hash
   *   Link Hash.
   * @param string $paragraph_id
   *   Paragraph id.
   *
   * @return array
   *   Render download page.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function checkLink(string $link_hash, string $paragraph_id): array {
    // Load file and paragraph.
    $entity = $this->getSubscribeFileEntity('link_hash', $link_hash);
    $this->checkLinkActive($entity);
    $paragraph = Paragraph::load($paragraph_id);
    $file_hash = $entity->get('file_hash')->get(0)->getValue();

    // Generate download button.
    $button_text = $paragraph->get('field_d_p_sf_download_button')->getValue();
    $button_url = Url::fromRoute(
      'd_p_subscribe_file.downloadfile.getFile',
      ['file_hash' => $file_hash['value']],
      ['absolute' => TRUE]
    )->toString();

    $button = [
      '#theme' => 'd_button_link',
      '#title' => $button_text[0]['value'],
      '#options' => [
        'type' => ButtonTypeEnum::Primary->value,
      ],
      '#url' => $button_url,
    ];

    $rendered_button = $this->renderer->render($button)->__toString();

    // Generate download page with link.
    $display_settings = ['label' => 'hidden'];
    $body = $paragraph->get('field_d_p_sf_download_page')
      ->view($display_settings);
    $body[0]['#text'] = str_replace("[download-button]", $rendered_button, $body[0]['#text']);

    return [
      '#theme' => 'd_p_subscribe_file_download_page',
      '#body' => $body,
    ];
  }

  /**
   * Get SubscribeFileEntity.
   *
   * @param string $field_name
   *   Field name.
   * @param string $field_value
   *   Field value.
   *
   * @return \Drupal\d_p_subscribe_file\Entity\SubscribeFileEntity
   *   Return file entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getSubscribeFileEntity(string $field_name, $field_value): SubscribeFileEntity {
    /** @var \Drupal\d_p_subscribe_file\Entity\SubscribeFileEntity[] $subscribe_file_entity */
    $subscribe_file_entity = $this->entityTypeManager()
      ->getStorage('SubscribeFileEntity')
      ->loadByProperties([$field_name => $field_value]);

    if (empty($subscribe_file_entity)) {
      throw new NotFoundHttpException();
    }

    return reset($subscribe_file_entity);
  }

  /**
   * Checking link was created before 24h.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function checkLinkActive(ContentEntityInterface $entity) {
    $created = $entity->get('created')->get(0)->getValue();
    if (time() > $created['value'] + 86400) {
      $this->goHomeWithMessage($this->t('Link is not active, please add your email again'));
    }
  }

  /**
   * Redirect to home page and show drupal message.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $message
   *   Message.
   */
  private function goHomeWithMessage(string|MarkupInterface $message) {
    $this->messenger()->addStatus($message);
    $url = Url::fromRoute('<front>');
    $response = new RedirectResponse($url->toString());
    $response->send();
  }

  /**
   * Send file to browsers.
   *
   * @param string $file_hash
   *   File link hash.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   Return file.
   */
  public function getFile(string $file_hash) {
    $entity = $this->getSubscribeFileEntity('file_hash', $file_hash);
    $this->checkLinkActive($entity);
    $file = $this->entityTypeManager->getStorage('file')->load($entity->get('fid')->getValue()[0]['value']);
    $uri = $file->getFileUri();
    $response = new BinaryFileResponse($uri);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    return $response;
  }

}
