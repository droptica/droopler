<?php

namespace Drupal\paragraphs\Tests\Experimental;

use Drupal\contact\Entity\ContactForm;

/**
 * Tests paragraphs with contact forms.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalContactTest extends ParagraphsExperimentalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'contact',
  );

  /**
   * Tests adding paragraphs in contact forms.
   */
  public function testContactForm() {
    $this->loginAsAdmin([
      'administer contact forms',
      'access site-wide contact form'
    ]);
    // Add a paragraph type.
    $this->addParagraphsType('paragraphs_contact');
    $this->addParagraphsType('text');

    // Create a contact form.
    $contact_form = ContactForm::create(['id' => 'test_contact_form']);
    $contact_form->save();
    // Add a paragraphs field to the contact form.
    $this->addParagraphsField($contact_form->id(), 'paragraphs', 'contact_message');
    // Add a paragraph to the contact form.
    $this->drupalGet('contact/test_contact_form');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_paragraphs_contact_add_more');
    // Check that the paragraph is displayed.
    $this->assertText('paragraphs_contact');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_0_remove');
    $elements = $this->xpath('//table[starts-with(@id, :id)]/tbody', [':id' => 'paragraphs-values']);
    $header = $this->xpath('//table[starts-with(@id, :id)]/thead', [':id' => 'paragraphs-values']);
    $this->assertEqual($elements, []);
    $this->assertNotEqual($header, []);
  }
}
