<?php

namespace Tests\Support\Helper;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Codeception\Util\Drupal\FormField;
use Codeception\Util\Drupal\ParagraphFormField;
use Codeception\Util\IdentifiableFormFieldInterface;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use GuzzleHttp\Client;

/**
 * Class DrupalAcceptance.
 *
 * ### Example
 * #### Example (DrupalAcceptance)
 *     modules:
 *        - DrupalAcceptance.
 *
 * @package Codeception\Module
 */
class TestDrupalAcceptance extends Module
{
    /**
     * Web-driver.
     *
     * @var \Codeception\Module
     */
    protected $webdriver;

    /**
     * DrupalAcceptance constructor.
     *
     * @param \Codeception\Lib\ModuleContainer $moduleContainer
     *   Module container.
     * @param null|mixed $config
     *   Configurations.
     *
     * @throws \Codeception\Exception\ModuleException
     */
    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        parent::__construct($moduleContainer, $config);
        $this->webdriver = $this->getModule("WebDriver");
    }

    /**
     * Fill link field.
     *
     * @param \Codeception\Util\IdentifiableFormFieldInterface $field
     *   Text form field.
     * @param string $uri
     *   Uri.
      */
    public function fillSingleLinkField(IdentifiableFormFieldInterface $field, $uri)
    {
        $this->webdriver->fillField($field->uri, $uri);
    }

    /**
     * Fill CTA link field.
     *
     * @param \Codeception\Util\IdentifiableFormFieldInterface $field
     *   Text form field.
     * @param string $uri
     *   Uri.
     * @param string|null $title
     *   Title.
     * @param string $target
     *   Target.
     */
    public function fillCTAField(
        IdentifiableFormFieldInterface $field,
        string $uri,
        string $title = null,
        string $target = null
    ) {
        $this->webdriver->fillField($field->uri, $uri);
        if (isset($title)) {
            $this->webdriver->fillField($field->title, $title);
        }
        if (isset($target)) {
            $this->webdriver->selectOption($field->__get('options-attributes-target'), $target);
        }
    }

    /**
     * Fill date field.
     *
     * @param \Codeception\Util\IdentifiableFormFieldInterface $field
     *   Text form field.
     * @param string $startDate
     *   start Date.
     * @param string $startTime
     *   start Time.
     * @param string $endDate
     *   end Date.
     * @param string $endTime
     *   end Time.
     */
    public function fillDateField(
        IdentifiableFormFieldInterface $field,
        string $startDate,
        string $startTime,
        string $endDate,
        string $endTime
    ) {
        $this->webdriver->fillField($field->__get('date-wrapper-date'), $startDate);
        $this->webdriver->fillField($field->__get('date-wrapper-time'), $startTime);
        $this->webdriver->fillField($field->__get('date-end-wrapper-date'), $endDate);
        $this->webdriver->fillField($field->__get('date-end-wrapper-time'), $endTime);
    }

    /**
     * Fill date field.
     *
     * @param \Codeception\Util\IdentifiableFormFieldInterface $field
     *   Text form field.
     * @param string $startDate
     *   start Date.
     * @param string $startTime
     *   start Time.
     */
    public function fillStartDateField(IdentifiableFormFieldInterface $field, string $startDate, string $startTime)
    {
        $this->webdriver->fillField($field->__get('date-wrapper-date'), $startDate);
        $this->webdriver->fillField($field->__get('date-wrapper-time'), $startTime);
    }

    /**
     * Edit paragraph element.
     *
     * @param \Codeception\Util\Drupal\ParagraphFormField $paragraph
     *   Paragraph field.
     */
    public function editParagraph(ParagraphFormField $paragraph)
    {
        $this->webdriver->click($paragraph->get($paragraph->position . ' top links edit button'));
        $this->webdriver->waitForElementClickable($paragraph->getCurrent('Subform'));
    }

    /**
     * Add paragraph element.
     *
     * @param string $type
     *   Paragraph type.
     * @param \Codeception\Util\Drupal\ParagraphFormField $paragraph
     *   Paragraph field.
     */
    public function addNewParagraph(string $type, ParagraphFormField $field)
    {
        $this->webdriver->click('[name=field_page_section_' . $type . '_add_more]');
        $this->webdriver->waitForElementClickable($field->getCurrent());
    }

    /**
     * Fill text field.
     *
     * @param \Codeception\Util\IdentifiableFormFieldInterface $field
     *   Element xpath.
     * @param string $text
     *   Text to insert in CkEditor5.
     */
    public function fillCk5WysiwygEditor(IdentifiableFormFieldInterface $field, $content): void
    {
        $selector = $this->webdriver->grabAttributeFrom($field->value, 'id');
        $script = "
         window.Drupal.CKEditor5Instances.forEach((instance) => {
           if (instance.sourceElement.id === '$selector') {
             instance.setData('$content');
           }
         });";
        $this->webdriver->executeInSelenium(function (RemoteWebDriver $webDriver) use ($script) {
            $webDriver->executeScript($script);
        });
        $this->webdriver->wait(1);
    }
}
