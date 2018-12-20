<?php

namespace Drupal\Tests\paragraphs\Functional;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\paragraphs\Tests\Classic\ParagraphsCoreVersionUiTestTrait;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;
use Drupal\Tests\paragraphs\Traits\ParagraphsLastEntityQueryTrait;

/**
 * Test paragraphs with translations.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalTranslationsTest extends BrowserTestBase {

  use LoginAdminTrait;
  use FieldUiTestTrait;
  use ParagraphsTestBaseTrait;
  use ParagraphsCoreVersionUiTestTrait;
  use ParagraphsLastEntityQueryTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'paragraphs_test',
    'paragraphs',
    'field',
    'field_ui',
    'block',
    'link',
    'content_translation'
  ];

  /**
   * User with admin rights.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $visitorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->addParagraphedContentType('paragraphed_test', 'field_paragraphs', 'paragraphs');

    ConfigurableLanguage::createFromLangcode('de')->save();
    $this->loginAsAdmin([
      'access administration pages',
      'view all revisions',
      'revert all revisions',
      'administer nodes',
      'bypass node access',
      'administer content translation',
      'translate any entity',
      'create content translations',
      'administer languages',
      'administer content types',
      'administer node form display',
      'edit any paragraphed_test content',
      'create paragraphed_test content',
      'edit behavior plugin settings',
    ]);

    $this->visitorUser = $this->drupalCreateUser([
      'access content',
      'view all revisions',
    ]);

    // Add a Paragraph type.
    $this->addParagraphsType('text_translatable');
    $this->addParagraphsType('text_untranslatable');
    $this->addParagraphsType('text_untranslatable_hide');
    $this->addParagraphsType('container');
    $this->addFieldtoParagraphType('text_translatable', 'field_text_translatable', 'text');
    $this->addFieldtoParagraphType('text_untranslatable', 'field_text_untranslatable', 'text');
    $this->addFieldtoParagraphType('text_untranslatable_hide', 'field_text_untranslatable_hide', 'text');
    $this->addParagraphsField('container', 'field_paragraphs', 'paragraph');

    $this->drupalGet('admin/config/regional/content-language');
    $this->assertSession()->pageTextContains('Paragraph types that are used in moderated content requires non-translatable fields to be edited in the original language form and this must be checked.');
    $edit = [
      'entity_types[paragraph]' => TRUE,
      'entity_types[node]' => TRUE,
      'settings[node][paragraphed_test][translatable]' => TRUE,
      'settings[node][paragraphed_test][fields][field_paragraphs]' => FALSE,
      'settings[node][paragraphed_test][settings][language][language_alterable]' => TRUE,
      'settings[paragraph][text_translatable][translatable]' => TRUE,
      'settings[paragraph][text_untranslatable][translatable]' => TRUE,
      'settings[paragraph][text_untranslatable_hide][translatable]' => TRUE,
      'settings[paragraph][container][translatable]' => TRUE,
      'settings[paragraph][text_translatable][settings][content_translation][untranslatable_fields_hide]' => TRUE,
      'settings[paragraph][text_untranslatable][settings][content_translation][untranslatable_fields_hide]' => FALSE,
      'settings[paragraph][text_untranslatable_hide][settings][content_translation][untranslatable_fields_hide]' => TRUE,
      'settings[paragraph][container][settings][content_translation][untranslatable_fields_hide]' => TRUE,
      'settings[paragraph][text_translatable][fields][field_text_translatable]' => TRUE,
      'settings[paragraph][text_untranslatable][fields][field_text_untranslatable]' => FALSE,
      'settings[paragraph][text_untranslatable_hide][fields][field_text_untranslatable_hide]' => FALSE,
      'settings[paragraph][container][fields][field_paragraphs]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
  }

  /**
   * Tests auto collapse when paragraphs do not have translatable fields.
   */
  public function testUntranslatableAutoCollapse() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('node/add/paragraphed_test');
    // Create a text paragraphs, a container paragraph with a text inside.
    $page->fillField('title[0][value]', 'Page 1 EN');
    $page->pressButton('Add text_translatable');
    $page->pressButton('Add text_untranslatable');
    $page->pressButton('Add text_untranslatable_hide');
    $page->pressButton('Add container');
    $page->fillField('field_paragraphs[0][subform][field_text_translatable][0][value]', 'Translate me EN');
    $page->fillField('field_paragraphs[1][subform][field_text_untranslatable][0][value]', 'Do not translate me EN');
    $page->fillField('field_paragraphs[2][subform][field_text_untranslatable_hide][0][value]', 'Do not translate me CLOSED EN');
    $page->pressButton('Save');

    $assert_session->pageTextContains('paragraphed_test Page 1 EN has been created.');

    $host_node = $this->getLastEntityOfType('node', TRUE);
    $host_node_id = $host_node->id();

    // Assert that the paragraph with no translatable fields is closed.
    $this->drupalGet("/de/node/{$host_node_id}/translations/add/en/de");
    $assert_session->fieldExists('field_paragraphs[0][subform][field_text_translatable][0][value]');
    $assert_session->buttonExists('field_paragraphs_0_collapse');
    // Check that the Paragraph with no translatable fields and with setting for
    // hiding the fields is showing the summary and it cannot be opened or
    // closed.
    $assert_session->fieldNotExists('field_paragraphs[2][subform][field_text_untranslatable_hide][0][value]');
    $assert_session->buttonNotExists('field_paragraphs_2_collapse');
    $assert_session->buttonNotExists('field_paragraphs_2_edit');
    $assert_session->responseContains('<div class="paragraphs-collapsed-description">Do not translate me CLOSED EN');

    $assert_session->buttonExists('field_paragraphs_1_collapse');
    $assert_session->fieldExists('field_paragraphs[1][subform][field_text_untranslatable][0][value]');
    $page->pressButton('field_paragraphs_1_collapse');
    $assert_session->fieldNotExists('field_paragraphs[1][subform][field_text_untranslatable][0][value]');
    $assert_session->responseContains('<div class="paragraphs-collapsed-description">Do not translate me EN');
    $assert_session->buttonExists('field_paragraphs_1_edit');

    $assert_session->buttonExists('field_paragraphs_3_collapse');
    $page->pressButton('field_paragraphs_3_collapse');
    $assert_session->buttonExists('field_paragraphs_3_edit');

    // Edit the EN node by adding a Paragraph in the nested container.
    $this->drupalGet('node/' . $host_node_id . '/edit');
    $page->pressButton('field_paragraphs_3_subform_field_paragraphs_text_translatable_add_more');
    $page->fillField('field_paragraphs[3][subform][field_paragraphs][0][subform][field_text_translatable][0][value]', 'Nested translate me EN');
    $page->pressButton('Save');

    // Set the widget edit mode to closed.
    $this->setParagraphsWidgetSettings('paragraphed_test', 'field_paragraphs', ['edit_mode' => 'closed'], 'paragraphs', 'node');
    $this->setParagraphsWidgetSettings('container', 'field_paragraphs', ['edit_mode' => 'closed'], 'paragraphs', 'paragraph');

    // Create a translation and check that all fields are open.
    $this->drupalGet("/de/node/{$host_node_id}/translations/add/en/de");
    $assert_session->fieldExists('field_paragraphs[0][subform][field_text_translatable][0][value]');
    $assert_session->fieldExists('field_paragraphs[1][subform][field_text_untranslatable][0][value]');
    $assert_session->responseContains('<div class="paragraphs-collapsed-description">Do not translate me CLOSED EN');
    $assert_session->fieldExists('field_paragraphs[3][subform][field_paragraphs][0][subform][field_text_translatable][0][value]');
    // Translate the content of the translatable fields.
    $page->fillField('field_paragraphs[0][subform][field_text_translatable][0][value]', 'Translate me DE');
    $page->fillField('field_paragraphs[3][subform][field_paragraphs][0][subform][field_text_translatable][0][value]', 'Nested translate me DE');
    // Close all Paragraphs with the new values.
    $page->pressButton('field_paragraphs_collapse_all');
    $assert_session->responseContains('<div class="paragraphs-collapsed-description">Translate me DE');
    $assert_session->responseContains('<div class="paragraphs-collapsed-description">Do not translate me EN');
    $assert_session->responseContains('<div class="paragraphs-collapsed-description">Do not translate me CLOSED EN');
    $assert_session->responseContains('<div class="paragraphs-collapsed-description">Nested translate me DE');
    // Edit the first Paragraph and update its value.
    $page->pressButton('field_paragraphs_0_edit');
    $page->fillField('field_paragraphs[0][subform][field_text_translatable][0][value]', 'Translate me UPDATE DE');
    // When editing a nested container, all children should be open too when
    // creating the translation.
    $page->pressButton('field_paragraphs_3_edit');
    $assert_session->fieldExists('field_paragraphs[3][subform][field_paragraphs][0][subform][field_text_translatable][0][value]');
    $page->pressButton('Save (this translation)');

    // Edit the translation, assert that the Paragraphs are closed by default.
    $this->drupalGet('de/node/' . $host_node_id . '/edit');
    $assert_session->responseContains('<div class="paragraphs-collapsed-description">Translate me UPDATE DE');
    $assert_session->responseContains('<div class="paragraphs-collapsed-description">Do not translate me EN');
    $assert_session->responseContains('<div class="paragraphs-collapsed-description">Do not translate me CLOSED EN');
    $assert_session->responseContains('<div class="paragraphs-collapsed-description">Nested translate me DE');
    // Open all paragraphs.
    $page->pressButton('field_paragraphs_edit_all');
    $assert_session->fieldExists('field_paragraphs[0][subform][field_text_translatable][0][value]');
    $assert_session->fieldExists('field_paragraphs[1][subform][field_text_untranslatable][0][value]');
    $assert_session->responseContains('<div class="paragraphs-collapsed-description">Do not translate me CLOSED EN');
    $assert_session->responseContains('<div class="paragraphs-collapsed-description">Nested translate me DE');
    $assert_session->fieldNotExists('field_paragraphs[3][subform][field_paragraphs][0][subform][field_text_translatable][0][value]');
    // When editing a nested container, all children should follow the widget
    // settings when editing the translation.
    $page->pressButton('field_paragraphs_3_subform_field_paragraphs_0_edit');
    $assert_session->fieldExists('field_paragraphs[3][subform][field_paragraphs][0][subform][field_text_translatable][0][value]');
  }

}
