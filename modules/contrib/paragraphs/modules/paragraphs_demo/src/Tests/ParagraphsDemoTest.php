<?php

namespace Drupal\paragraphs_demo\Tests;

use Drupal\paragraphs\Tests\Classic\ParagraphsCoreVersionUiTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the demo module for Paragraphs.
 *
 * @group paragraphs
 */
class ParagraphsDemoTest extends WebTestBase {

  use ParagraphsCoreVersionUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = array(
    'paragraphs_demo',
    'block',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Asserts demo paragraphs have been created.
   */
  protected function testConfigurationsAndCreation() {

    // Assert that the demo page is displayed to anymous users.
    $this->drupalGet('');
    $this->assertText('Paragraphs is the new way of content creation!');
    $this->assertText('Apart from the included Paragraph types');
    $this->assertText('A search api example can be found');
    $this->assertText('This is content from the library. We can reuse it multiple times without duplicating it.');

    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'create paragraphed_content_demo content',
      'edit any paragraphed_content_demo content',
      'delete any paragraphed_content_demo content',
      'administer content translation',
      'create content translations',
      'bypass node access',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'use editorial transition archived_published',
      'use editorial transition archived_draft',
      'use editorial transition archive',
      'administer languages',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer paragraphs types',
      'administer paragraph fields',
      'administer paragraph display',
      'administer paragraph form display',
      'administer node form display',
      'administer paragraphs library',
      'use text format basic_html',
    ));

    $this->drupalLogin($admin_user);

    // Set edit mode to open.
    $this->drupalGet('admin/structure/types/manage/paragraphed_content_demo/form-display');
    $this->drupalPostAjaxForm(NULL, [], "field_paragraphs_demo_settings_edit");
    $edit = ['fields[field_paragraphs_demo][settings_edit_form][settings][edit_mode]' => 'open'];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check for all pre-configured paragraphs_types.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->assertText('Image + Text');
    $this->assertText('Images');
    $this->assertText('Text');
    $this->assertText('Text + Image');
    $this->assertText('User');

    // Check for preconfigured languages.
    $this->drupalGet('admin/config/regional/language');
    $this->assertText('English');
    $this->assertText('German');
    $this->assertText('French');

    // Check for Content language translation checks.
    $this->drupalGet('admin/config/regional/content-language');
    $this->assertFieldChecked('edit-entity-types-node');
    $this->assertFieldChecked('edit-entity-types-paragraph');
    $this->assertFieldChecked('edit-settings-node-paragraphed-content-demo-translatable');
    $this->assertNoFieldChecked('edit-settings-node-paragraphed-content-demo-fields-field-paragraphs-demo');
    $this->assertFieldChecked('edit-settings-paragraph-images-translatable');
    $this->assertFieldChecked('edit-settings-paragraph-image-text-translatable');
    $this->assertFieldChecked('edit-settings-paragraph-text-translatable');
    $this->assertFieldChecked('edit-settings-paragraph-text-image-translatable');
    $this->assertFieldChecked('edit-settings-paragraph-user-translatable');

    // Check for paragraph type Image + text that has the correct fields set.
    $this->drupalGet('admin/structure/paragraphs_type/image_text/fields');
    $this->assertText('Text');
    $this->assertText('Image');

    // Check for paragraph type Text that has the correct fields set.
    $this->drupalGet('admin/structure/paragraphs_type/text/fields');
    $this->assertText('Text');
    $this->assertNoText('Image');

    // Make sure we have the paragraphed article listed as a content type.
    $this->drupalGet('admin/structure/types');
    $this->assertText('Paragraphed article');

    // Check that title and the descriptions are set.
    $this->drupalGet('admin/structure/types/manage/paragraphed_content_demo');
    $this->assertText('Paragraphed article');
    $this->assertText('Article with Paragraphs.');

    // Check that the Paragraph field is added.
    $this->clickLink('Manage fields');
    $this->assertText('Paragraphs');

    // Check that all paragraphs types are enabled (disabled).
    $this->clickLink('Edit', 0);
    $this->assertNoFieldChecked('edit-settings-handler-settings-target-bundles-drag-drop-image-text-enabled');
    $this->assertNoFieldChecked('edit-settings-handler-settings-target-bundles-drag-drop-images-enabled');
    $this->assertNoFieldChecked('edit-settings-handler-settings-target-bundles-drag-drop-text-image-enabled');
    $this->assertNoFieldChecked('edit-settings-handler-settings-target-bundles-drag-drop-user-enabled');
    $this->assertNoFieldChecked('edit-settings-handler-settings-target-bundles-drag-drop-text-enabled');

    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->assertRaw('<h4 class="label">Paragraphs</h4>', 'Field name is present on the page.');
    $this->drupalPostForm(NULL, NULL, t('Add Text'));
    $this->assertNoRaw('<strong data-drupal-selector="edit-field-paragraphs-demo-title">Paragraphs</strong>', 'Field name for empty field is not present on the page.');
    $this->assertRaw('<h4 class="label">Paragraphs</h4>', 'Field name appears in the table header.');
    $edit = array(
      'title[0][value]' => 'Paragraph title',
      'moderation_state[0][state]' => 'published',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'Paragraph text',
    );
    $this->drupalPostForm(NULL, $edit, t('Add User'));
    $edit = [
      'field_paragraphs_demo[1][subform][field_user_demo][0][target_id]' => $admin_user->label() . ' (' . $admin_user->id() . ')',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->assertText('Paragraphed article Paragraph title has been created.');
    $this->assertText('Paragraph title');
    $this->assertText('Paragraph text');

    // Search a nested Paragraph text.
    $this->drupalGet('paragraphs_search', ['query' => ['search_api_fulltext' => 'A search api example']]);
    $this->assertRaw('Welcome to the Paragraphs Demo module!');
    // Search a node paragraph field text.
    $this->drupalGet('paragraphs_search', ['query' => ['search_api_fulltext' => 'It allows you']]);
    $this->assertRaw('Welcome to the Paragraphs Demo module!');
    // Search non existent text.
    $this->drupalGet('paragraphs_search', ['query' => ['search_api_fulltext' => 'foo']]);
    $this->assertNoRaw('Welcome to the Paragraphs Demo module!');

    // Check that the dropbutton of Nested Paragraph has the Duplicate function.
    // For now, this indicates that it is using the EXPERIMENTAL widget.
    $this->drupalGet('node/1/edit');
    $this->assertFieldByName('field_paragraphs_demo_3_subform_field_paragraphs_demo_0_duplicate');

    // Check the library paragraph.
    $this->drupalGet('admin/content/paragraphs');
    $this->assertText('Library item');
    $this->assertText('This is content from the library.');

    // Assert anonymous users cannot update library items.
    $this->drupalLogout();
    $this->drupalGet('admin/content/paragraphs/1/edit');
    $this->assertResponse(403);
    $this->drupalGet('admin/content/paragraphs/1/delete');
    $this->assertResponse(403);
  }

}
