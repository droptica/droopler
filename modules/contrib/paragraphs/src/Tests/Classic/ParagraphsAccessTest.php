<?php

namespace Drupal\paragraphs\Tests\Classic;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\image\Entity\ImageStyle;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\user\RoleInterface;
use Drupal\user\Entity\Role;

/**
 * Tests the access check of paragraphs.
 *
 * @group paragraphs
 */
class ParagraphsAccessTest extends ParagraphsTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'content_translation',
    'image',
    'field',
    'field_ui',
    'block',
    'language',
    'node'
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    ConfigurableLanguage::create(['id' => 'de', 'label' => '1German'])->save();
    ConfigurableLanguage::create(['id' => 'fr', 'label' => '2French'])->save();
    $this->addParagraphedContentType('paragraphed_content_demo', 'field_paragraphs_demo');
    $this->loginAsAdmin([
      'administer site configuration',
      'administer content translation',
      'administer languages',
    ]);
    $this->addParagraphsType('nested_paragraph');
    $this->addParagraphsField('nested_paragraph', 'field_paragraphs_demo', 'paragraph');
    $this->addParagraphsType('images');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/images', 'images_demo', 'Images', 'image', ['cardinality' => -1], ['settings[alt_field]' => FALSE]);
    $this->addParagraphsType('text_image');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text_image', 'image_demo', 'Images', 'image', ['cardinality' => -1], ['settings[alt_field]' => FALSE]);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text_image', 'text_demo', 'Text', 'text_long', [], []);
    $this->addParagraphsType('text');
    static::fieldUIAddExistingField('admin/structure/paragraphs_type/text', 'field_text_demo', 'Text', []);
    $edit = [
      'entity_types[node]' => TRUE,
      'entity_types[paragraph]' => TRUE,
      'settings[node][paragraphed_content_demo][translatable]' => TRUE,
      'settings[node][paragraphed_content_demo][fields][field_paragraphs_demo]' => FALSE,
      'settings[paragraph][images][translatable]' => TRUE,
      'settings[paragraph][text_image][translatable]' => TRUE,
      'settings[paragraph][text][translatable]' => TRUE,
      'settings[paragraph][nested_paragraph][translatable]' => TRUE,
      'settings[paragraph][nested_paragraph][fields][field_paragraphs_demo]' => FALSE,
      'settings[paragraph][nested_paragraph][settings][language][language_alterable]' => TRUE,
      'settings[paragraph][images][fields][field_images_demo]' => TRUE,
      'settings[paragraph][text_image][fields][field_image_demo]' => TRUE,
      'settings[paragraph][text_image][fields][field_text_demo]' => TRUE,
      'settings[node][paragraphed_content_demo][settings][language][language_alterable]' => TRUE
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save configuration'));

    $view_display = entity_get_display('paragraph', 'images', 'default')
      ->setComponent('field_images_demo', ['settings' => ['image_style' => 'medium']]);
    $view_display->save();
  }

  /**
   * Tests the paragraph translation.
   */
  public function testParagraphAccessCheck() {
    $admin_user = [
      'administer site configuration',
      'administer node display',
      'administer paragraph display',
      'create paragraphed_content_demo content',
      'edit any paragraphed_content_demo content',
    ];
    $this->loginAsAdmin($admin_user);

    // Remove the "access content" for anonymous users. That results in
    // anonymous users not being able to "view" the host entity.
    /* @var Role $role */
    $role = \Drupal::entityTypeManager()
      ->getStorage('user_role')
      ->load(RoleInterface::ANONYMOUS_ID);
    $role->revokePermission('access content');
    $role->save();

    // Set field_images from demo to private file storage.
    $edit = array(
      'settings[uri_scheme]' => 'private',
    );
    $this->drupalPostForm('admin/structure/paragraphs_type/images/fields/paragraph.images.field_images_demo/storage', $edit, t('Save field settings'));

    // Set the form display to classic.
    $form_display = EntityFormDisplay::load('node.paragraphed_content_demo.default')
      ->setComponent('field_paragraphs_demo', ['type' => 'entity_reference_paragraphs']);
    $form_display->save();

    // Create a new demo node.
    $this->drupalGet('node/add/paragraphed_content_demo');

    // Add a new paragraphs images item.
    $this->drupalPostForm(NULL, NULL, t('Add images'));

    $images = $this->drupalGetTestFiles('image');

    // Create a file, upload it.
    file_unmanaged_copy($images[0]->uri, 'temporary://privateImage.jpg');
    $file_path = $this->container->get('file_system')
      ->realpath('temporary://privateImage.jpg');

    // Create a file, upload it.
    file_unmanaged_copy($images[1]->uri, 'temporary://privateImage2.jpg');
    $file_path_2 = $this->container->get('file_system')
      ->realpath('temporary://privateImage2.jpg');

    $edit = array(
      'title[0][value]' => 'Security test node',
      'files[field_paragraphs_demo_0_subform_field_images_demo_0][]' => [$file_path, $file_path_2],
    );

    $this->drupalPostForm(NULL, $edit, t('Upload'));
    $this->drupalPostForm(NULL,  [], t('Preview'));
    $image_style = ImageStyle::load('medium');
    $img1_url = $image_style->buildUrl('private://' . date('Y-m') . '/privateImage.jpg');
    $image_url = file_url_transform_relative($img1_url);
    $this->assertRaw($image_url, 'Image was found in preview');
    $this->clickLink(t('Back to content editing'));
    $this->drupalPostForm(NULL, [], t('Save'));

    $node = $this->drupalGetNodeByTitle('Security test node');

    $this->drupalGet('node/' . $node->id());

    // Check the text and image after publish.
    $this->assertRaw($image_url, 'Image was found in content');

    $this->drupalGet($img1_url);
    $this->assertResponse(200, 'Image could be downloaded');

    // Logout to become anonymous.
    $this->drupalLogout();

    // @todo Requesting the same $img_url again triggers a caching problem on
    // drupal.org test bot, thus we request a different file here.
    $img_url = $image_style->buildUrl('private://' . date('Y-m') . '/privateImage2.jpg');
    $image_url = file_url_transform_relative($img_url);
    // Check the text and image after publish. Anonymous should not see content.
    $this->assertNoRaw($image_url, 'Image was not found in content');

    $this->drupalGet($img_url);
    $this->assertResponse(403, 'Image could not be downloaded');

    // Login as admin with no delete permissions.
    $this->loginAsAdmin($admin_user);
    // Create a new demo node.
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->drupalPostForm(NULL, NULL, t('Add text'));
    $this->assertText('Text');
    $edit = [
      'title[0][value]' => 'delete_permissions',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'Test',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Edit the node.
    $this->clickLink(t('Edit'));
    // Check the remove button is present.
    $this->assertNotNull($this->xpath('//*[@name="field_paragraphs_demo_0_remove"]'));
    // Delete the Paragraph and save.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_0_remove');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_0_confirm_remove');
    $this->drupalPostForm(NULL, [], t('Save'));
    $node = $this->getNodeByTitle('delete_permissions');
    $this->assertUrl('node/' . $node->id());
  }
}
