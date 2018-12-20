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
 * Test paragraphs and content moderation with translations.
 *
 * @group paragraphs
 */
class ParagraphsClassicContentModerationTranslationsTest extends BrowserTestBase {

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
    'content_moderation',
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

    $this->addParagraphedContentType('paragraphed_test', 'field_paragraphs', 'entity_reference_paragraphs');

    ConfigurableLanguage::createFromLangcode('de')->save();

    $this->createEditorialWorkflow('paragraphed_test');

    $this->loginAsAdmin([
      'access administration pages',
      'view any unpublished content',
      'view all revisions',
      'revert all revisions',
      'view latest version',
      'view any unpublished content',
      'use ' . $this->workflow->id() . ' transition create_new_draft',
      'use ' . $this->workflow->id() . ' transition publish',
      'use ' . $this->workflow->id() . ' transition archived_published',
      'use ' . $this->workflow->id() . ' transition archived_draft',
      'use ' . $this->workflow->id() . ' transition archive',
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
    $this->addParagraphsType('text');
    $this->addParagraphsType('container');

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text', 'text', 'Text', 'text_long', [], []);
    // Add an untranslatable string field.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text', 'untranslatable', 'Text', 'string', [], []);

    $this->addParagraphsField('container', 'field_paragraphs', 'paragraph', 'entity_reference_paragraphs');

    $this->drupalGet('admin/config/regional/content-language');
    $this->assertSession()->pageTextContains('Paragraph types that are used in moderated content requires non-translatable fields to be edited in the original language form and this must be checked.');
    $edit = [
      'entity_types[paragraph]' => TRUE,
      'entity_types[node]' => TRUE,
      'settings[node][paragraphed_test][translatable]' => TRUE,
      'settings[node][paragraphed_test][fields][field_paragraphs]' => FALSE,
      'settings[node][paragraphed_test][settings][language][language_alterable]' => TRUE,
      'settings[paragraph][text][translatable]' => TRUE,
      'settings[paragraph][container][translatable]' => TRUE,
      // Because the paragraph entity itself is not directly enabled in the
      // workflow, these options must be enabled manually.
      'settings[paragraph][text][settings][content_translation][untranslatable_fields_hide]' => TRUE,
      'settings[paragraph][container][settings][content_translation][untranslatable_fields_hide]' => TRUE,
      'settings[paragraph][text][fields][field_text]' => TRUE,
      'settings[paragraph][text][fields][field_untranslatable]' => FALSE,
      'settings[paragraph][container][fields][field_paragraphs]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
  }

  /**
   * Tests content moderation with translatable content entities.
   */
  public function testTranslatableContentEntities() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('node/add/paragraphed_test');

    // Translation selection should be available on content creation page.
    $option = $assert_session->optionExists('langcode[0][value]', 'en');
    $this->assertTrue($option->hasAttribute('selected'));
    $assert_session->optionExists('langcode[0][value]', 'de');

    // Create a text paragraphs, a container paragraph with a text inside.
    $page->fillField('title[0][value]', 'Page 1 EN');
    $page->pressButton('Add text');
    $page->pressButton('Add container');
    $page->pressButton('field_paragraphs_1_subform_field_paragraphs_text_add_more');
    $page->fillField('field_paragraphs[0][subform][field_text][0][value]', 'Initial paragraph text EN');
    $page->fillField('field_paragraphs[0][subform][field_untranslatable][0][value]', 'Untranslatable text');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][0][subform][field_text][0][value]', 'Initial paragraph container text EN');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][0][subform][field_untranslatable][0][value]', 'Untranslatable container text');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->fillField('revision_log[0][value]', 'Node revision #1 EN');
    $page->pressButton('Save');

    $assert_session->pageTextContains('paragraphed_test Page 1 EN has been created.');
    $assert_session->pageTextContains('Initial paragraph text EN');
    $assert_session->pageTextContains('Untranslatable text');
    $assert_session->pageTextContains('Initial paragraph container text EN');
    $assert_session->pageTextContains('Untranslatable container text');

    $host_node = $this->getLastEntityOfType('node', TRUE);
    $host_node_id = $host_node->id();

    // Create a translation.
    $this->drupalGet("/de/node/{$host_node_id}/translations/add/en/de");
    $assert_session->pageTextNotContains('Fields that apply to all languages are hidden to avoid conflicting changes');
    $page->fillField('title[0][value]', 'Page 1 DE');
    $page->fillField('field_paragraphs[0][subform][field_text][0][value]', 'Initial paragraph text DE');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][0][subform][field_text][0][value]', 'Initial paragraph container text DE');
    $assert_session->fieldNotExists('field_paragraphs_1_subform_field_paragraphs_container_add_more');
    $assert_session->fieldNotExists('field_paragraphs[0][subform][field_untranslatable][0][value]');
    $assert_session->fieldNotExists('field_paragraphs[1][subform][field_paragraphs][0][subform][field_untranslatable][0][value]');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->pressButton('Save (this translation)');

    $assert_session->pageTextContains('paragraphed_test Page 1 DE has been updated.');
    $assert_session->pageTextContains('Initial paragraph text DE');
    $assert_session->pageTextContains('Untranslatable text');
    $assert_session->pageTextContains('Initial paragraph container text DE');
    $assert_session->pageTextContains('Untranslatable container text');

    // Test the original translation.
    $this->drupalGet("/node/$host_node_id");
    $assert_session->pageTextContains('Page 1 EN');
    $assert_session->pageTextContains('Initial paragraph text EN');
    $assert_session->pageTextContains('Untranslatable text');
    $assert_session->pageTextContains('Initial paragraph container text EN');
    $assert_session->pageTextContains('Untranslatable container text');

    // Create revision.
    $this->drupalGet("/de/node/{$host_node_id}/edit");
    $page->fillField('title[0][value]', 'Changed Page 1 DE');
    $page->fillField('field_paragraphs[0][subform][field_text][0][value]', 'Changed paragraph text DE');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][0][subform][field_text][0][value]', 'Changed paragraph container text DE');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->pressButton('Save (this translation)');
    $assert_session->pageTextContains('paragraphed_test Changed Page 1 DE has been updated.');
    $assert_session->pageTextNotContains('Initial paragraph text DE');
    $assert_session->pageTextContains('Changed paragraph container text DE');

    // Create revision draft for DE.
    $this->drupalGet("/de/node/{$host_node_id}/edit");
    $page->fillField('title[0][value]', 'Draft Page 1 DE');
    $page->fillField('field_paragraphs[0][subform][field_text][0][value]', 'Draft paragraph text DE');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][0][subform][field_text][0][value]', 'Draft paragraph container text DE');
    $page->selectFieldOption('moderation_state[0][state]', 'draft');
    $page->pressButton('Save (this translation)');
    $assert_session->pageTextContains('paragraphed_test Draft Page 1 DE has been updated.');
    $assert_session->pageTextContains('Draft paragraph text DE');
    $assert_session->pageTextContains('Draft paragraph container text DE');

    $this->drupalGet("/de/node/{$host_node_id}");
    $assert_session->pageTextContains('Changed paragraph text DE');
    $this->drupalGet("/de/node/{$host_node_id}/latest");
    $assert_session->pageTextContains('Draft paragraph text DE');

    // Create revision draft for EN.
    $this->drupalGet("node/{$host_node_id}/edit");
    $page->fillField('title[0][value]', 'Draft Page 1 EN');
    $page->fillField('field_paragraphs[0][subform][field_text][0][value]', 'Draft paragraph text EN');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][0][subform][field_text][0][value]', 'Draft paragraph container text EN');
    $page->fillField('field_paragraphs[0][subform][field_untranslatable][0][value]', 'Untranslatable draft text');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][0][subform][field_untranslatable][0][value]', 'Untranslatable container draft text');
    $page->selectFieldOption('moderation_state[0][state]', 'draft');
    $page->pressButton('Save (this translation)');
    $assert_session->pageTextContains('paragraphed_test Draft Page 1 EN has been updated.');
    $assert_session->pageTextContains('Draft paragraph text EN');
    $assert_session->pageTextContains('Untranslatable draft text');
    $assert_session->pageTextContains('Draft paragraph container text EN');
    $assert_session->pageTextContains('Untranslatable container draft text');

    $this->drupalGet("/node/{$host_node_id}");
    $assert_session->pageTextContains('Initial paragraph text EN');

    $this->drupalGet("/node/{$host_node_id}/latest");
    $assert_session->pageTextContains('Draft paragraph text EN');

    // Assert the DE draft is still accessible.
    $this->drupalGet("/de/node/{$host_node_id}");
    $assert_session->pageTextContains('Changed paragraph text DE');
    $this->drupalGet("/de/node/{$host_node_id}/latest");
    $assert_session->pageTextContains('Draft paragraph text DE');

    // Publish the EN draft.
    $this->drupalGet("/node/{$host_node_id}/latest");
    $assert_session->pageTextContains('Draft paragraph text EN');
    $assert_session->pageTextContains('Untranslatable draft text');
    $page->pressButton('Apply');

    $assert_session->pageTextContains('Draft paragraph text EN');
    $assert_session->pageTextContains('Untranslatable draft text');

    // The untranslatable fields are really stored per translation revision too
    // so the DE draft still has the old values for them.
    $this->drupalGet("/de/node/{$host_node_id}/latest");
    $assert_session->pageTextContains('Draft paragraph text DE');
    $assert_session->pageTextContains('Untranslatable text');
    $assert_session->pageTextContains('Draft paragraph container text DE');
    $assert_session->pageTextContains('Untranslatable container text');

    // Publish the DE draft through the edit form.
    $this->drupalGet("/de/node/{$host_node_id}/edit");
    $assert_session->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', 'Draft paragraph text DE');
    $assert_session->fieldValueEquals('field_paragraphs[1][subform][field_paragraphs][0][subform][field_text][0][value]', 'Draft paragraph container text DE');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->pressButton('Save (this translation)');

    $this->drupalGet("/de/node/{$host_node_id}");
    $assert_session->pageTextContains('Draft paragraph text DE');
    $assert_session->pageTextContains('Untranslatable draft text');
    $assert_session->pageTextContains('Draft paragraph container text DE');
    $assert_session->pageTextContains('Untranslatable container draft text');

    // Assert that the EN translation as not affected.
    $this->drupalGet("/node/{$host_node_id}");
    $assert_session->pageTextContains('Draft paragraph text EN');
    $assert_session->pageTextContains('Untranslatable draft text');
    $assert_session->pageTextContains('Draft paragraph container text EN');
    $assert_session->pageTextContains('Untranslatable container draft text');

    // Revert the DE translation to the previous state, ensure that the EN
    // translation does not get reverted with it.
    $this->drupalGet("/node/{$host_node_id}/revisions");
    $this->drupalGet("/de/node/{$host_node_id}/revisions");

    // The revision lists 4 german revisions, the current, the draft, the
    // changed and the original revision. Revert to the changed revision, which
    // is the second Revert link on the page.
    $this->clickLink('Revert', 1);
    $page->pressButton('Revert');

    $this->drupalGet("/de/node/{$host_node_id}");
    $assert_session->pageTextContains('Changed paragraph text DE');
    $assert_session->pageTextContains('Untranslatable draft text');
    $assert_session->pageTextContains('Changed paragraph container text DE');
    $assert_session->pageTextContains('Untranslatable container draft text');

    // Assert that the EN translation as not affected.
    $this->drupalGet("/node/{$host_node_id}");
    $assert_session->pageTextContains('Draft paragraph text EN');
    $assert_session->pageTextContains('Untranslatable draft text');
    $assert_session->pageTextContains('Draft paragraph container text EN');
    $assert_session->pageTextContains('Untranslatable container draft text');

    entity_get_form_display('node', 'paragraphed_test', 'default')
      ->setComponent('field_paragraphs', [
        'type' => 'entity_reference_paragraphs',
        'settings' => [
          'edit_mode' => 'open',
          'add_mode' => 'dropdown',
          'form_display_mode' => 'default',
          'default_paragraph_type' => '_none',
        ],
      ])
      ->save();
    entity_get_form_display('paragraph', 'container', 'default')
      ->setComponent('field_paragraphs', [
        'type' => 'entity_reference_paragraphs',
        'settings' => [
          'edit_mode' => 'open',
          'add_mode' => 'dropdown',
          'form_display_mode' => 'default',
          'default_paragraph_type' => '_none',
        ],
      ])
      ->save();

    // @TODO when https://www.drupal.org/project/paragraphs/issues/3004099 gets
    // committed, update the following two scenarios.
    // When an EN node is published, we add a draft translation and we edit the
    // original EN adding a new Paragraph and keeping it published, the new
    // created Paragraph should appear in the translation draft too.
    // Create a new published EN node with a paragraph.
    $this->drupalGet('node/add/paragraphed_test');
    $page->fillField('title[0][value]', 'Moderation test 1 EN');
    $page->pressButton('Add text');
    $page->pressButton('Add container');
    $page->pressButton('field_paragraphs_1_subform_field_paragraphs_text_add_more');
    $page->fillField('field_paragraphs[0][subform][field_text][0][value]', 'EN First level text');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][0][subform][field_text][0][value]', 'EN Second level text');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->fillField('revision_log[0][value]', 'Revision 1 EN');
    $page->pressButton('Save');
    $assert_session->pageTextContains('paragraphed_test Moderation test 1 EN has been created.');
    $assert_session->pageTextContains('EN First level text');
    $assert_session->pageTextContains('EN Second level text');

    $node = $this->getLastEntityOfType('node', TRUE);
    $node = $node->id();

    // Create a draft translation.
    $this->drupalGet("/de/node/{$node}/translations/add/en/de");
    $page->fillField('title[0][value]', 'Moderation test 1 DE');
    $page->fillField('field_paragraphs[0][subform][field_text][0][value]', 'DE First level text');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][0][subform][field_text][0][value]', 'DE Second level text');
    $page->selectFieldOption('moderation_state[0][state]', 'draft');
    $page->pressButton('Save (this translation)');
    $assert_session->pageTextContains('paragraphed_test Moderation test 1 DE has been updated.');
    $assert_session->pageTextContains('DE First level text');
    $assert_session->pageTextContains('DE Second level text');

    // Change the structure of Paragraphs on the new published EN node.
    $this->drupalGet("/node/$node/edit");
    $assert_session->pageTextContains('Moderation test 1 EN');
    $page->pressButton('field_paragraphs_0_remove');
    $page->pressButton('field_paragraphs_1_subform_field_paragraphs_text_add_more');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][0][subform][field_text][0][value]', 'EN Draft second level text 1');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][1][subform][field_text][0][value]', 'EN Draft second level text 2');
    $page->fillField('revision_log[0][value]', 'Revision 1 EN');
    $page->pressButton('Save');
    $assert_session->pageTextContains('paragraphed_test Moderation test 1 EN has been updated.');
    $assert_session->pageTextContains('EN Draft second level text 1');
    $assert_session->pageTextContains('EN Draft second level text 2');

    // Assert that the draft DE translation has the new paragraph structure.
    $this->drupalGet("/de/node/{$node}/edit");
    $assert_session->fieldExists('field_paragraphs[0][subform][field_paragraphs][0][subform][field_text][0][value]');
    $assert_session->fieldExists('field_paragraphs[0][subform][field_paragraphs][1][subform][field_text][0][value]');
    $page->fillField('field_paragraphs[0][subform][field_paragraphs][0][subform][field_text][0][value]', 'DE Draft second level text 1');
    $page->fillField('field_paragraphs[0][subform][field_paragraphs][1][subform][field_text][0][value]', 'DE Draft second level text 2');
    $page->selectFieldOption('moderation_state[0][state]', 'draft');
    $page->pressButton('Save (this translation)');
    $assert_session->pageTextContains('paragraphed_test Moderation test 1 DE has been updated.');
    $assert_session->pageTextContains('DE Draft second level text 1');
    $assert_session->pageTextContains('DE Draft second level text 2');

    // Create a published EN node, add a draft with a different Paragraphs
    // structure. When translating, the translation should have the same
    // Paragraphs structure as the last published EN node.
    // Create a new published EN node with a paragraph.
    $this->drupalGet('node/add/paragraphed_test');
    $page->fillField('title[0][value]', 'EN Moderation');
    $page->pressButton('Add text');
    $page->pressButton('Add container');
    $page->pressButton('field_paragraphs_1_subform_field_paragraphs_text_add_more');
    $page->fillField('field_paragraphs[0][subform][field_text][0][value]', 'EN First level text');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][0][subform][field_text][0][value]', 'EN Second level text');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->fillField('revision_log[0][value]', 'Revision 1 EN');
    $page->pressButton('Save');
    $assert_session->pageTextContains('paragraphed_test EN Moderation has been created.');
    $assert_session->pageTextContains('EN First level text');
    $assert_session->pageTextContains('EN Second level text');

    $node = $this->getLastEntityOfType('node', TRUE);
    $node = $node->id();

    // Create an EN draft of the node.
    $this->drupalGet("node/{$node}/edit");
    $page->pressButton('field_paragraphs_0_remove');
    $page->pressButton('field_paragraphs_1_subform_field_paragraphs_text_add_more');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][0][subform][field_text][0][value]', 'EN Draft second level first text');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][1][subform][field_text][0][value]', 'EN Draft second level second text');
    $page->selectFieldOption('moderation_state[0][state]', 'draft');
    $page->fillField('revision_log[0][value]', 'Revision 2 EN');
    $page->pressButton('Save');
    $assert_session->pageTextContains('paragraphed_test EN Moderation has been updated.');
    $assert_session->pageTextContains('EN Draft second level first text');
    $assert_session->pageTextContains('EN Draft second level second text');

    $node = $this->getLastEntityOfType('node', TRUE);
    $node = $node->id();

    // Create a translation and save, it should have the same structure as the
    // published EN node.
    $this->drupalGet("/de/node/{$node}/translations/add/en/de");
    $page->fillField('title[0][value]', 'DE Moderation');
    $page->fillField('field_paragraphs[0][subform][field_text][0][value]', 'DE First level text');
    $page->fillField('field_paragraphs[1][subform][field_paragraphs][0][subform][field_text][0][value]', 'DE Second level text');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->pressButton('Save (this translation)');
    $assert_session->pageTextNotContains('Non-translatable fields can only be changed when updating the original language.');
    $assert_session->pageTextContains('paragraphed_test DE Moderation has been updated.');
    $assert_session->pageTextContains('DE First level text');
    $assert_session->pageTextContains('DE Second level text');

    // Publish the EN draft.
    $this->drupalGet("node/{$node}/edit");
    $page->fillField('field_paragraphs[0][subform][field_paragraphs][0][subform][field_text][0][value]', 'EN Second level first text');
    $page->fillField('field_paragraphs[0][subform][field_paragraphs][1][subform][field_text][0][value]', 'EN Second level second text');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->pressButton('Save');
    $assert_session->pageTextNotContains('Non-translatable fields can only be changed when updating the original language.');
    $assert_session->pageTextContains('paragraphed_test EN Moderation has been updated.');
    $assert_session->pageTextContains('EN Second level first text');
    $assert_session->pageTextContains('EN Second level second text');

    // Assert that the translation node has the same structure as the new
    // published node.
    $this->drupalGet("de/node/{$node}/edit");
    $page->fillField('field_paragraphs[0][subform][field_paragraphs][0][subform][field_text][0][value]', 'DE Second level first text');
    $page->fillField('field_paragraphs[0][subform][field_paragraphs][1][subform][field_text][0][value]', 'DE Second level second text');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->pressButton('Save');
    $assert_session->pageTextNotContains('Non-translatable fields can only be changed when updating the original language.');
    $assert_session->pageTextContains('paragraphed_test DE Moderation has been updated.');
    $assert_session->pageTextContains('DE Second level first text');
    $assert_session->pageTextContains('DE Second level second text');
  }
}
