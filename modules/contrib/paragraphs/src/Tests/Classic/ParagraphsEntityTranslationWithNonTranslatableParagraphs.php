<?php

namespace Drupal\paragraphs\Tests\Classic;

/**
 * Tests the translation of heavily nested / specialized setup.
 *
 * @group paragraphs
 */
class ParagraphsEntityTranslationWithNonTranslatableParagraphs extends ParagraphsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($this->admin_user);

    // Add a languages.
    $edit = array(
      'predefined_langcode' => 'de',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Create article content type with a paragraphs field.
    $this->addParagraphedContentType('article', 'field_paragraphs');
    $this->drupalGet('admin/structure/types/manage/article');
    // Make content type translatable.
    $edit = array(
      'language_configuration[content_translation]' => TRUE,
    );
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));
    $this->drupalGet('admin/structure/types/manage/article');

    // Ensue the paragraphs field itself isn't translatable - this would be a
    // currently not supported configuration otherwise.
    $edit = array(
      'translatable' => FALSE,
    );
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.field_paragraphs', $edit, t('Save settings'));

    // Add Paragraphs type.
    $this->addParagraphsType('test_paragraph_type');
    // Configure paragraphs type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/test_paragraph_type', 'text', 'Text', 'string', [
      'cardinality' => '-1',
    ]);

    // Just for verbose-sake - check the content language settings.
    $this->drupalGet('admin/config/regional/content-language');
  }

  /**
   * Tests the revision of paragraphs.
   */
  public function testParagraphsIEFTranslation() {
    $this->drupalLogin($this->admin_user);

    // Create node with one paragraph.
    $this->drupalGet('node/add/article');

    // Set the values and save.
    $edit = [
      'title[0][value]' => 'Title English',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Add french translation.
    $this->clickLink(t('Translate'));
    $this->clickLink(t('Add'), 1);
    // Make sure that the original paragraph text is displayed.
    $this->assertText('Title English');

    $edit = array(
      'title[0][value]' => 'Title French',
    );
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    $this->assertText('article Title French has been updated.');

    // Add german translation.
    $this->clickLink(t('Translate'));
    $this->clickLink(t('Add'));
    // Make sure that the original paragraph text is displayed.
    $this->assertText('Title English');

    $edit = array(
      'title[0][value]' => 'Title German',
    );
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    $this->assertText('article Title German has been updated.');
  }

}
