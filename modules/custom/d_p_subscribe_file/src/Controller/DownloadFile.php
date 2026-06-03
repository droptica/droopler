<?php

declare(strict_types=1);

namespace Drupal\d_p_subscribe_file\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\d_p_subscribe_file\Entity\SubscribeFileEntity;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for subscriber file download links.
 */
class DownloadFile extends ControllerBase {

  /**
   * Number of seconds a generated download link remains valid (24h).
   */
  protected const int LINK_LIFETIME_SECONDS = 86400;

  /**
   * Verify the link hash and render the download page.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Render array on success, or a redirect to the front page if the link
   *   has expired.
   */
  public function checkLink(string $link_hash, string $paragraph_id): array|RedirectResponse {
    $entity = $this->getSubscribeFileEntity('link_hash', $link_hash);
    if (!$this->isLinkActive($entity)) {
      return $this->redirectHomeWithMessage($this->t('Link is not active, please add your email again'));
    }

    $paragraph = Paragraph::load($paragraph_id);
    if (!$paragraph instanceof Paragraph) {
      throw new NotFoundHttpException();
    }

    $file_hash = $entity->get('file_hash')->get(0)->getValue();
    $link_options = [
      'absolute' => TRUE,
      'attributes' => ['class' => 'btn btn-primary btn-orange'],
    ];

    $button_text = $paragraph->get('field_d_p_sf_download_button')->getValue();
    $download_link = Link::createFromRoute(
      $button_text[0]['value'],
      'd_p_subscribe_file.downloadfile.getFile',
      ['file_hash' => $file_hash['value']],
      $link_options,
    );
    $rendered_download_link = $download_link->toString()->getGeneratedLink();

    $display_settings = ['label' => 'hidden'];
    $body = $paragraph->get('field_d_p_sf_download_page')->view($display_settings);
    $body[0]['#text'] = str_replace('[download-button]', $rendered_download_link, $body[0]['#text']);

    return [
      '#theme' => 'd_p_subscribe_file_download_page',
      '#body' => $body,
    ];
  }

  /**
   * Stream the file to the browser.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   File response, or a redirect home when the link is no longer active.
   */
  public function getFile(string $file_hash): BinaryFileResponse|RedirectResponse {
    $entity = $this->getSubscribeFileEntity('file_hash', $file_hash);
    if (!$this->isLinkActive($entity)) {
      return $this->redirectHomeWithMessage($this->t('Link is not active, please add your email again'));
    }

    $fid = $entity->get('fid')->getValue()[0]['value'];
    /** @var \Drupal\file\FileInterface|null $file */
    $file = $this->entityTypeManager->getStorage('file')->load($fid);
    if ($file === NULL) {
      throw new NotFoundHttpException();
    }

    $response = new BinaryFileResponse($file->getFileUri());
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    return $response;
  }

  /**
   * Load a SubscribeFileEntity by indexed property.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   When no matching entity exists.
   */
  protected function getSubscribeFileEntity(string $field_name, string $field_value): SubscribeFileEntity {
    $entities = $this->entityTypeManager
      ->getStorage('SubscribeFileEntity')
      ->loadByProperties([$field_name => $field_value]);
    if (empty($entities)) {
      throw new NotFoundHttpException();
    }
    /** @var \Drupal\d_p_subscribe_file\Entity\SubscribeFileEntity $entity */
    $entity = reset($entities);
    return $entity;
  }

  /**
   * TRUE if the link was created within the last 24h.
   */
  protected function isLinkActive(ContentEntityInterface $entity): bool {
    $created = $entity->get('created')->get(0)->getValue();
    return time() <= ((int) $created['value'] + self::LINK_LIFETIME_SECONDS);
  }

  /**
   * Build a redirect to the front page with a status message attached.
   */
  protected function redirectHomeWithMessage(string|\Stringable $message): RedirectResponse {
    $this->messenger()->addStatus($message);
    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

}
