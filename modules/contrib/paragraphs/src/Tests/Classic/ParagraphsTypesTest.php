<?php

namespace Drupal\paragraphs\Tests\Classic;

use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * Tests Paragraphs types.
 *
 * @group paragraphs
 */
class ParagraphsTypesTest extends ParagraphsTestBase {

  /**
   * Tests the deletion of Paragraphs types.
   */
  public function testRemoveTypesWithContent() {
    $this->loginAsAdmin();

    // Add a Paragraphed test content.
    $this->addParagraphedContentType('paragraphed_test', 'paragraphs', 'entity_reference_paragraphs');
    $this->addParagraphsType('paragraph_type_test');
    $this->addParagraphsType('text');

    // Attempt to delete the content type not used yet.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->clickLink(t('Delete'));
    $this->assertText('This action cannot be undone.');
    $this->clickLink(t('Cancel'));

    // Add a test node with a Paragraph.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_paragraph_type_test_add_more');
    $edit = ['title[0][value]' => 'test_node'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('paragraphed_test test_node has been created.');

    // Attempt to delete the paragraph type already used.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->clickLink(t('Delete'));
    $this->assertText('paragraph_type_test Paragraphs type is used by 1 piece of content on your site. You can not remove this paragraph_type_test Paragraphs type until you have removed all from the content.');

  }

  /**
   * Tests the paragraph type icon settings.
   */
  public function testParagraphTypeIcon() {

    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = \Drupal::service('file.usage');

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');

    $admin_user = $this->drupalCreateUser([
      'administer paragraphs types',
      'access files overview',
    ]);
    $this->drupalLogin($admin_user);
    // Add the paragraph type with icon.
    $this->drupalGet('admin/structure/paragraphs_type/add');
    $this->assertText('Paragraph type icon');
    $test_files = $this->drupalGetTestFiles('image');
    $fileSystem = \Drupal::service('file_system');
    $edit = [
      'label' => 'Test paragraph type',
      'id' => 'test_paragraph_type_icon',
      'files[icon_file]' => $fileSystem->realpath($test_files[0]->uri),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and manage fields'));
    $this->assertText('Saved the Test paragraph type Paragraphs type.');

    // Check if the icon has been saved.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->assertRaw('image-test.png');
    $this->clickLink('Edit');
    $this->assertText('image-test.png');

    // Check that the icon file usage has been registered.
    $paragraph_type = ParagraphsType::load('test_paragraph_type_icon');
    $file = $entity_repository->loadEntityByUuid('file', $paragraph_type->get('icon_uuid'));
    $usages = $file_usage->listUsage($file);
    $this->assertEqual($usages['paragraphs']['paragraphs_type']['test_paragraph_type_icon'], 1);

    // Tests calculateDependencies method.
    $dependencies = $paragraph_type->getDependencies();
    $dependencies_uuid[] = explode(':', $dependencies['content'][0]);
    $this->assertEqual($paragraph_type->get('icon_uuid'), $dependencies_uuid[0][2]);

    // Delete the icon.
    $this->drupalGet('admin/structure/paragraphs_type/test_paragraph_type_icon');
    $this->drupalPostAjaxForm(NULL, [], 'icon_file_remove_button');
    $this->drupalPostForm(NULL, [], t('Save'));

    // Check that the icon file usage has been deregistered.
    $usages = $file_usage->listUsage($file);
    $this->assertEqual($usages, []);
  }

  /**
   * Test the paragraph type description settings.
   */
  public function testParagraphTypeDescription() {
    $admin_user = $this->drupalCreateUser(['administer paragraphs types']);
    $this->drupalLogin($admin_user);
    // Add the paragraph type with description.
    $this->drupalGet('admin/structure/paragraphs_type/add');
    $this->assertText('Description');
    $label = 'Test paragraph type';
    $description_markup = 'Use <em>Test paragraph type</em> to test the functionality of descriptions.';
    $description_text = 'Use Test paragraph type to test the functionality of descriptions.';
    $edit = [
      'label' => $label,
      'id' => 'test_paragraph_type_description',
      'description' => $description_markup,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and manage fields'));
    $this->assertText("Saved the $label Paragraphs type.");

    // Check if the description has been saved.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->assertText('Description');
    $this->assertText($description_text);
    $this->assertRaw($description_markup);
    //Check if description is at Description column
    $header_position = count($this->xpath('//table/thead/tr/th[.="Description"]/preceding-sibling::th'));
    $row_position = count($this->xpath('//table/tbody/tr/td[.="' . $description_text . '"]/preceding-sibling::td'));
    $this->assertEqual($header_position, $row_position);
    $this->clickLink('Edit');
    $this->assertText('Use &lt;em&gt;Test paragraph type&lt;/em&gt; to test the functionality of descriptions.');
  }

}
