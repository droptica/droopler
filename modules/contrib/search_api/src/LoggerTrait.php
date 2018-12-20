<?php

namespace Drupal\search_api;

use Drupal\Core\Utility\Error;
use Drupal\Core\Logger\RfcLogLevel;
use Psr\Log\LoggerInterface;

/**
 * Provides helper methods for logging with dependency injection.
 */
trait LoggerTrait {

  /**
   * The logging channel to use.
   *
   * @var \Psr\Log\LoggerInterface|null
   */
  protected $logger;

  /**
   * Retrieves the logger.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger.
   */
  public function getLogger() {
    return $this->logger ?: \Drupal::service('logger.channel.search_api');
  }

  /**
   * Sets the logger.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The new logger.
   *
   * @return $this
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
    return $this;
  }

  /**
   * Logs an exception.
   *
   * @param \Exception $exception
   *   The exception that is going to be logged.
   * @param string|null $message
   *   (optional) The message to display in the log, which can use variables
   *   retrieved from the exception (like "%type" or "@message"). Or NULL to use
   *   the default message.
   * @param array $variables
   *   (optional) Array of variables to replace in the message when it is
   *   displayed, or NULL if the message should not be translated. The normal
   *   patterns for translation variables can be used.
   * @param int $severity
   *   (optional) The severity of the message, as per RFC 3164.
   * @param string|null $link
   *   (optional) A link to associate with the message, if any.
   *
   * @see watchdog_exception()
   * @see \Drupal\Core\Utility\Error::decodeException()
   */
  protected function logException(\Exception $exception, $message = NULL, array $variables = [], $severity = RfcLogLevel::ERROR, $link = NULL) {
    // Use a default value if $message is not set.
    if (empty($message)) {
      $message = '%type: @message in %function (line %line of %file).';
    }

    if ($link) {
      $variables['link'] = $link;
    }

    $variables += Error::decodeException($exception);

    $this->getLogger()->log($severity, $message, $variables);
  }

}
