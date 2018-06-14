<?php

namespace Drupal\paragraphs_library\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Tests paragraphs library multilingual functionality.
 *
 * @package Drupal\paragraphs_library\Tests
 * @group paragraphs_library
 */
class MultilingualBehaviorTest extends ParagraphsExperimentalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'content_translation',
    'paragraphs_library',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->addParagraphedContentType('paragraphed_test');
    $this->addParagraphsType('test_content');
    $this->addParagraphsType('nested_paragraph');

    $user = $this->createUser(array_merge($this->admin_permissions, [
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library',
      'administer languages',
      'administer content translation',
      'create content translations',
      'translate any entity',
    ]));
    $this->drupalLogin($user);

    ConfigurableLanguage::createFromLangcode('de')->save();

    // Enable translation for paragraphed_test content.
    $edit = [
      'language_configuration[content_translation]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/types/manage/paragraphed_test', $edit, 'Save content type');

    $this->fieldUIAddNewField('admin/structure/paragraphs_type/test_content', 'paragraphs_text', 'Test content', 'text_long', [], []);

    // Add nested paragraph field.
    $this->fieldUIAddNewField('admin/structure/paragraphs_type/nested_paragraph', 'err_field', 'Nested', 'field_ui:entity_reference_revisions:paragraph', [
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ], []);

    // Enable translation for paragraphs_content, paragraph type
    // and paragraphs_library_item.
    $edit = [
      'entity_types[paragraph]' => TRUE,
      'entity_types[paragraphs_library_item]' => TRUE,
      'settings[node][paragraphed_test][fields][field_paragraphs]' => FALSE,
      'settings[paragraph][test_content][translatable]' => TRUE,
      'settings[paragraph][nested_paragraph][translatable]' => TRUE,
      'settings[paragraph][test_content][fields][field_paragraphs_text]' => TRUE,
      'settings[paragraphs_library_item][paragraphs_library_item][translatable]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, 'Save configuration');
  }

  /**
   * Tests reusing translated nested paragraph from library.
   */
  public function testReuseTranslationForNestedParagraphFromLibrary() {
    // Add nested paragraph directly in library.
    $this->drupalGet('admin/content/paragraphs/add/default');
    $this->drupalPostAjaxForm(NULL, NULL, 'paragraphs_nested_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'paragraphs_0_subform_field_err_field_test_content_add_more');
    $edit = [
      'label[0][value]' => 'En label Test nested paragraph',
      'paragraphs[0][subform][field_err_field][0][subform][field_paragraphs_text][0][value]' => 'En label Example text for test in nested paragraph.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Paragraph En label Test nested paragraph has been created.');

    // Translate nested paragraphs library item.
    $this->clickLink('Translate');
    $this->clickLink('Add');
    $edit = [
      'label[0][value]' => 'De label Test geschachtelten Absatz',
      'paragraphs[0][subform][field_err_field][0][subform][field_paragraphs_text][0][value]' => 'De label Beispieltext fur den Test in geschachteltem Absatz.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Create test content.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_from_library_add_more');
    $edit = [
      'title[0][value]' => 'En label Test node nested',
      'field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]' => 'En label Test nested paragraph',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Add translation for test node.
    $node = $this->drupalGetNodeByTitle('En label Test node nested');
    $edit = [
      'title[0][value]' => 'De label Test geschachtelten Absatz',
    ];
    $this->drupalPostForm('de/node/' . $node->id() . '/translations/add/en/de', $edit, 'Save (this translation)');

    $this->drupalGet('node/' . $node->id());
    $this->assertText('En label Example text for test in nested paragraph.');

    $this->drupalGet('de/node/' . $node->id());
    $this->assertText('De label Beispieltext fur den Test in geschachteltem Absatz.');

    // Update translation of library item.
    $edit = [
      'paragraphs[0][subform][field_err_field][0][subform][field_paragraphs_text][0][value]' => 'De label Beispieltext fur den Test geander.',
    ];
    $this->drupalPostForm('de/admin/content/paragraphs/1/edit', $edit, 'Save');

    // Check updated content.
    $this->drupalGet('de/node/' . $node->id());
    $this->assertText('De label Beispieltext fur den Test geander.');
  }

  /**
   * Tests converting translated nested paragraph into library.
   */
  public function testMoveTranslatedNestedParagraphToLibrary() {
    $this->enableConvertingParagraphsTypeToLibrary('nested_paragraph');

    // Add node with text paragraph.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_nested_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_0_subform_field_err_field_test_content_add_more');
    $edit = [
      'title[0][value]' => 'En label Test node nested',
      'field_paragraphs[0][subform][field_err_field][0][subform][field_paragraphs_text][0][value]' => 'En label Example text for test in nested paragraph',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Add translation for node.
    $node = $this->drupalGetNodeByTitle('En label Test node nested');
    $this->drupalGet('node/' . $node->id() . '/translations');
    $this->clickLink('Add');
    $edit = [
      'title[0][value]' => 'Testknoten',
      'field_paragraphs[0][subform][field_err_field][0][subform][field_paragraphs_text][0][value]' => 'De label Beispieltext fur den Test in geschachteltem Absatz.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save (this translation)');

    // Convert translated paragraph to library.
    $this->drupalGet($node->toUrl('edit-form'));
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_0_promote_to_library');
    $this->drupalPostForm(NULL, NULL, 'Save (this translation)');

    // Check translation.
    $this->drupalGet($node->toUrl());
    $this->assertText('En label Example text for test in nested paragraph');

    $this->drupalGet('de/node/' . $node->id());
    $this->assertText('De label Beispieltext fur den Test in geschachteltem Absatz.');

    // Check library item after converting translated paragraph.
    $this->drupalGet('de/admin/content/paragraphs/1');
    $this->assertText('De label Beispieltext fur den Test in geschachteltem Absatz.');
  }

  /**
   * Tests converting translated nested paragraph from library.
   */
  public function testDetachTranslatedNestedParagraphItemFromLibrary() {
    $this->enableConvertingParagraphsTypeToLibrary('nested_paragraph');

    // Add paragraph directly in library.
    $this->drupalGet('admin/content/paragraphs/add/default');
    $this->drupalPostAjaxForm(NULL, NULL, 'paragraphs_nested_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'paragraphs_0_subform_field_err_field_test_content_add_more');
    $edit = [
      'label[0][value]' => 'En label Test nested paragraph',
      'paragraphs[0][subform][field_err_field][0][subform][field_paragraphs_text][0][value]' => 'En label Example text for test.'
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Paragraph En label Test nested paragraph has been created.');

    // Translate nested paragraphs library item.
    $this->clickLink('Translate');
    $this->clickLink('Add');
    $edit = [
      'label[0][value]' => 'De label Test geschachtelten Absatz',
      'paragraphs[0][subform][field_err_field][0][subform][field_paragraphs_text][0][value]' => 'De label Beispieltext fur den Test in geschachteltem Absatz.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Create test content.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_from_library_add_more');
    $edit = [
      'title[0][value]' => 'En label Test node nested',
      'field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]' => 'En label Test nested paragraph',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Add translation for test node.
    $node = $this->drupalGetNodeByTitle('En label Test node nested');
    $edit = [
      'title[0][value]' => 'De label Testknoten',
    ];
    $this->drupalPostForm('de/node/' . $node->id() . '/translations/add/en/de', $edit, 'Save (this translation)');

    $this->assertText('De label Beispieltext fur den Test in geschachteltem Absatz.');

    // The detach action is not visible while translating.
    $this->clickLink('Edit');
    $this->assertNoText('Unlink from library');

    // Detach from library an check content.
    $this->drupalGet('node/' . $node->id());
    $this->assertText('En label Example text for test.');
    $this->clickLink('Edit');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_0_unlink_from_library');
    $this->assertText('En label Example text for test.');
  }

  /**
   * Tests detach paragraph before adding a translation for the node.
   */
  public function testDetachBeforeTranslation() {
    $this->enableConvertingParagraphsTypeToLibrary('nested_paragraph');

    // Add paragraph directly in library.
    $this->drupalGet('admin/content/paragraphs/add/default');
    $this->drupalPostAjaxForm(NULL, NULL, 'paragraphs_nested_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'paragraphs_0_subform_field_err_field_test_content_add_more');
    $edit = [
      'label[0][value]' => 'En label Test nested paragraph',
      'paragraphs[0][subform][field_err_field][0][subform][field_paragraphs_text][0][value]' => 'En label Example text for test.'
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Paragraph En label Test nested paragraph has been created.');

    // Translate nested paragraphs library item.
    $this->clickLink('Translate');
    $this->clickLink('Add');
    $edit = [
      'label[0][value]' => 'De label Test geschachtelten Absatz',
      'paragraphs[0][subform][field_err_field][0][subform][field_paragraphs_text][0][value]' => 'De label Beispieltext fur den Test in geschachteltem Absatz.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Create test content.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_from_library_add_more');
    $edit = [
      'title[0][value]' => 'En label Test node nested',
      'field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]' => 'En label Test nested paragraph',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_0_unlink_from_library');
    $edit = [
      'title[0][value]' => 'En label Test node nested',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Add translation for test node.
    $node = $this->drupalGetNodeByTitle('En label Test node nested');
    $this->drupalPostForm('de/node/' . $node->id() . '/translations/add/en/de', NULL, 'Save (this translation)');

    $this->assertText('De label Beispieltext fur den Test in geschachteltem Absatz.');

    $this->drupalGet('node/' . $node->id());
    $this->assertText('En label Example text for test.');
  }

  /**
   * Enables converting paragraphs type into library.
   *
   * @param string $paragraphs_type
   *   Paragraphs type name.
   *
   * @throws \Exception
   *   Throws Exception if ajax path is not specified.
   */
  public function enableConvertingParagraphsTypeToLibrary($paragraphs_type) {
    $edit = [
      'allow_library_conversion' => 1,
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraphs_type, $edit, 'Save');
  }
}
