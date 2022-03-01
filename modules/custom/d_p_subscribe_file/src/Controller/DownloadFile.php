<?php

namespace Drupal\d_p_subscribe_file\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class DownloadFile controller.
 */
class DownloadFile extends ControllerBase {

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
   */
  public function checkLink($link_hash, $paragraph_id) {
    // Load file and paragraph.
    $entity = $this->getSubscribeFileEntity('link_hash', $link_hash);
    $this->checkLinkActive($entity);
    $paragraph = Paragraph::load($paragraph_id);
    $file_hash = $entity->get('file_hash')->get(0)->getValue();
    $link_options = [
      'absolute' => TRUE,
      'attributes' => ['class' => 'btn btn-primary btn-orange'],
    ];

    // Generate download link.
    $button_text = $paragraph->get('field_d_p_sf_download_button')->getValue();
    $download_link = Link::createFromRoute($button_text[0]['value'], 'd_p_subscribe_file.downloadfile.getFile', ['file_hash' => $file_hash['value']], $link_options);
    $rendered_download_link = $download_link->toString()->getGeneratedLink();

    // Generate download page with link.
    $display_settings = ['label' => 'hidden'];
    $body = $paragraph->get('field_d_p_sf_download_page')
      ->view($display_settings);
    $body[0]['#text'] = str_replace("[download-button]", $rendered_download_link, $body[0]['#text']);
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
   * @return \Drupal\Core\Entity\EntityInterface|mixed
   *   Return file entity.
   */
  private function getSubscribeFileEntity($field_name, $field_value) {
    $subscribe_file_entity = \Drupal::entityTypeManager()
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
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   */
  private function checkLinkActive(EntityInterface $entity) {
    $created = $entity->get('created')->get(0)->getValue();
    if (time() > $created['value'] + 86400) {
      $this->goHomeWithMessage(t('Link is not active, please add your email again'));
    }
  }

  /**
   * Redirect to home page and show drupal message.
   *
   * @param string $message
   *   Message.
   */
  private function goHomeWithMessage(string $message) {
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
    $file = File::load($entity->get('fid')->getValue()[0]['value']);
    $uri = $file->getFileUri();
    $response = new BinaryFileResponse($uri);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    return $response;
  }

}
