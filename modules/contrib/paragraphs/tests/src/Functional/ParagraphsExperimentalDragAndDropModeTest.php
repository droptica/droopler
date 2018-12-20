<?php

namespace Drupal\Tests\paragraphs\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests the drag and drop mode of paragraphs.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalDragAndDropModeTest extends BrowserTestBase {

  use ParagraphsTestBaseTrait;

  /**
   * Modules to be enabled.
   */
  public static $modules = [
    'node',
    'paragraphs',
    'field'
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->addParagraphedContentType('paragraphed_test', 'field_paragraphs');
    $this->addParagraphsType('paragraphs_container');
    $this->addParagraphsType('text');
    $this->addFieldtoParagraphType('text', 'field_text', 'text');

    $this->addParagraphsField('paragraphs_container', 'paragraphs_container_paragraphs', 'paragraph');

    // Make sure the paragraph fields use closed edit mode by default.
    $component = [
      'type' => 'paragraphs',
      'region' => 'content',
      'settings' => [
        'edit_mode' => 'closed',
        'add_mode' => 'modal',
        'form_display_mode' => 'default',
      ],
    ];

    EntityFormDisplay::load('paragraph.paragraphs_container.default')
      ->setComponent('paragraphs_container_paragraphs', $component)
      ->save();

    EntityFormDisplay::load('node.paragraphed_test.default')
      ->setComponent('field_paragraphs', $component)
      ->save();

    $admin = $this->drupalCreateUser([
      'create paragraphed_test content',
      'edit any paragraphed_test content'
    ]);
    $this->drupalLogin($admin);

    // By default, paragraphs does not show the Drag & drop button if the
    // library is not present. Override this for tests, as they don't need the
    // JS.
    \Drupal::state()->set('paragraphs_test_dragdrop_force_show', TRUE);
  }

  /**
   * Tests moving a paragraph from a container to top-level.
   */
  public function testChangeParagraphParentWeight() {
    // Create text paragraph.
    $text_paragraph_1 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 1',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_1->save();

    // Create a second text paragraph.
    $text_paragraph_2 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 2.',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_2->save();

    // Create container that contains the first two text paragraphs.
    $paragraph = Paragraph::create([
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [$text_paragraph_1, $text_paragraph_2],
    ]);
    $paragraph->save();

    // Add test content with paragraph container.
    $node = Node::create([
      'type' => 'paragraphed_test',
      'title' => 'Paragraphs Test',
      'field_paragraphs' => [$paragraph],
    ]);
    $node->save();

    // Check that the parent of the second text paragraph is the paragraph
    // container.
    $text_paragraph_2 = Paragraph::load($text_paragraph_2->id());
    $this->assertEquals($text_paragraph_2->get('parent_id')->value, $paragraph->id());
    $this->assertEquals($text_paragraph_2->get('parent_type')->value, 'paragraph');

    $this->drupalGet('/node/' . $node->id() . '/edit');
    $this->drupalPostForm(NULL, [], 'Drag & drop');

    $assert_session = $this->assertSession();
    $assert_session->hiddenFieldValueEquals('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][_path]', 'field_paragraphs][0][paragraphs_container_paragraphs');
    $assert_session->hiddenFieldValueEquals('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][1][_path]', 'field_paragraphs][0][paragraphs_container_paragraphs');

    // Change the path of the first text paragraph to the node as its parent.
    // This also requires an update of the path of the second paragraph in the
    // container as that moves down as well as the weight to prevent multiple
    // identical weights.
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][_path]')
      ->setValue('field_paragraphs');
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][1][_path]')
      ->setValue('field_paragraphs][1][paragraphs_container_paragraphs');
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][_weight]')
      ->setValue(1);
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][1][_weight]')
      ->setValue(0);

    $this->drupalPostForm(NULL, [], 'Complete drag & drop');
    $this->drupalPostForm(NULL, [], 'Save');

    // Check the new structure of the node and its paragraphs.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    \Drupal::entityTypeManager()->getStorage('paragraph')->resetCache();
    $node = Node::load($node->id());
    $this->assertEquals(count($node->get('field_paragraphs')), 2);

    $this->assertEquals($node->get('field_paragraphs')->get(0)->target_id, $text_paragraph_1->id());
    $text_paragraph_1 = $node->get('field_paragraphs')->get(0)->entity;
    $this->assertEquals($text_paragraph_1->get('parent_id')->value, $node->id());
    $this->assertEquals($text_paragraph_1->get('parent_type')->value, 'node');

    $this->assertEquals($node->get('field_paragraphs')->get(1)->target_id, $paragraph->id());
    $paragraph = $node->get('field_paragraphs')->get(1)->entity;
    $this->assertEquals($paragraph->get('parent_id')->value, $node->id());
    $this->assertEquals($paragraph->get('parent_type')->value, 'node');

    $this->assertEquals(count($paragraph->get('paragraphs_container_paragraphs')), 1);
    $this->assertEquals($paragraph->get('paragraphs_container_paragraphs')->target_id, $text_paragraph_2->id());

    $text_paragraph_2 = $paragraph->get('paragraphs_container_paragraphs')->entity;
    $this->assertEquals($text_paragraph_2->get('parent_id')->value, $paragraph->id());
    $this->assertEquals($text_paragraph_2->get('parent_type')->value, 'paragraph');

    // If the library does not exist, test that the button is not visible
    // without forcing it. This can not be tested if the library exists.
    // @todo: Implement a library alter in a test module to do this?
    $library_discovery = \Drupal::service('library.discovery');
    $library = $library_discovery->getLibraryByName('paragraphs', 'paragraphs-dragdrop');
    if (!$library) {
      \Drupal::state()->set('paragraphs_test_dragdrop_force_show', FALSE);
      $this->drupalGet('/node/' . $node->id() . '/edit');
      $this->assertSession()->buttonNotExists('Drag & drop');
    }
  }

  /**
   * Tests moving a paragraph from one container to another.
   */
  public function testChangeParagraphContainerMove() {
    // Create text paragraph.
    $text_paragraph_1 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 1',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_1->save();

    // Create container that contains the first two text paragraphs.
    $paragraph = Paragraph::create([
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [$text_paragraph_1],
    ]);
    $paragraph->save();

    // Create an empty container paragraph.
    $paragraph_1 = Paragraph::create([
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [],
    ]);
    $paragraph_1->save();

    // Add test content with paragraph container and the third text paragraph.
    $node = Node::create([
      'type' => 'paragraphed_test',
      'title' => 'Paragraphs Test',
      'field_paragraphs' => [$paragraph, $paragraph_1],
    ]);
    $node->save();

    // Change the path of the text paragraph to the empty container as its
    // parent.
    $this->drupalGet('/node/' . $node->id() . '/edit');
    $this->drupalPostForm(NULL, [], 'Drag & drop');

    // Ensure that the summary is displayed correctly.
    $this->assertSession()->elementTextContains('css', '.paragraphs-dragdrop-wrapper li:nth-of-type(1)', 'Test text 1');
    $this->assertSession()->elementTextNotContains('css', '.paragraphs-dragdrop-wrapper li:nth-of-type(2)', 'Test text 1');

    $this->assertSession()
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][_path]')
      ->setValue('field_paragraphs][1][paragraphs_container_paragraphs');
    $this->drupalPostForm(NULL, [], 'Complete drag & drop');

    // Ensure the summary is displayed correctly for the collapsed paragraphs.
    $this->assertSession()->elementTextNotContains('css', '.field--name-field-paragraphs tbody tr:nth-of-type(1) .paragraph-summary', 'Test text 1');
    $this->assertSession()->elementTextContains('css', '.field--name-field-paragraphs tbody tr:nth-of-type(2) .paragraph-summary', 'Test text 1');

    // Ensure that the summary was updated correctly when going back to drag and
    // drop mode.
    $this->drupalPostForm(NULL, [], 'Drag & drop');
    $this->assertSession()->elementTextNotContains('css', '.paragraphs-dragdrop-wrapper li:nth-of-type(1)', 'Test text 1');
    $this->assertSession()->elementTextContains('css', '.paragraphs-dragdrop-wrapper li:nth-of-type(2)', 'Test text 1');
    $this->drupalPostForm(NULL, [], 'Complete drag & drop');

    $this->drupalPostForm(NULL, [], 'Save');

    // Check that the parent of the text paragraph is the second paragraph
    // container.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    \Drupal::entityTypeManager()->getStorage('paragraph')->resetCache();
    $node = Node::load($node->id());
    $this->assertEquals(count($node->get('field_paragraphs')), 2);

    $this->assertEquals($node->get('field_paragraphs')->get(0)->target_id, $paragraph->id());
    $this->assertEquals($node->get('field_paragraphs')->get(1)->target_id, $paragraph_1->id());
    $paragraph = $node->get('field_paragraphs')->get(0)->entity;

    $this->assertEquals(count($paragraph->get('paragraphs_container_paragraphs')), 0);

    $paragraph_1 = $node->get('field_paragraphs')->get(1)->entity;
    $this->assertEquals(count($paragraph_1->get('paragraphs_container_paragraphs')), 1);
    $this->assertEquals($paragraph_1->get('paragraphs_container_paragraphs')->get(0)->target_id, $text_paragraph_1->id());

    $text_paragraph_1 = $paragraph_1->get('paragraphs_container_paragraphs')->entity;
    $this->assertEquals($text_paragraph_1->get('parent_id')->value, $paragraph_1->id());
    $this->assertEquals($text_paragraph_1->get('parent_type')->value, 'paragraph');
  }

  /**
   * Tests drag and drop mode with multiple changes on the paragraphs.
   */
  public function testMultipleChangesParagraphs() {
    // Create text paragraph.
    $text_paragraph_1 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 1',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_1->save();

    // Create a second text paragraph.
    $text_paragraph_2 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 2.',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_2->save();

    // Create container that contains the first two text paragraphs.
    $paragraph_1 = Paragraph::create([
      'title' => 'Test Paragraph 1',
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [$text_paragraph_1, $text_paragraph_2],
    ]);
    $paragraph_1->save();

    // Create another text paragraph.
    $text_paragraph_3 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 3.',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_3->save();

    // Create a container that contains the third text paragraph.
    $paragraph_2 = Paragraph::create([
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [$text_paragraph_3],
    ]);
    $paragraph_2->save();

    // Create a container that contains the second paragraph.
    $paragraph_3 = Paragraph::create([
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [$paragraph_2],
    ]);
    $paragraph_3->save();

    // Create an empty container paragraph.
    $paragraph_4 = Paragraph::create([
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [],
    ]);
    $paragraph_4->save();

    // Create a node with the structure of three nested paragraphs, first
    // paragraph with two text paragraphs, second paragraph with a nested
    // paragraph containing a text paragraph and the third empty paragraph.
    $node = Node::create([
      'type' => 'paragraphed_test',
      'title' => 'Paragraphs Test',
      'field_paragraphs' => [$paragraph_1, $paragraph_3, $paragraph_4],
    ]);
    $node->save();

    // Edit the node.
    $this->drupalGet('/node/' . $node->id() . '/edit');
    $this->getSession()->getPage()->findButton('field_paragraphs_2_edit')->press();
    $this->getSession()->getPage()->findButton('field_paragraphs_2_subform_paragraphs_container_paragraphs_text_add_more')->press();

    $edit = [
      'field_paragraphs[2][subform][paragraphs_container_paragraphs][0][subform][field_text][0][value]' => 'new paragraph',
    ];
    $this->drupalPostForm(NULL, $edit, 'Drag & drop');

    // Change the structure of the node, third text paragraph goes to first
    // container, the first text paragraph goes to the second container (child
    // of third container) and the third container goes to the fourth container.
    // This also affects weights and paths of child and related paragraphs.
    $assert_session = $this->assertSession();
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][1][dragdrop][paragraphs_container_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][_path]')
      ->setValue('field_paragraphs][0][paragraphs_container_paragraphs');
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][_path]')
      ->setValue('field_paragraphs][1][paragraphs_container_paragraphs][0][paragraphs_container_paragraphs][0][paragraphs_container_paragraphs');
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][1][dragdrop][paragraphs_container_paragraphs][list][0][_path]')
      ->setValue('field_paragraphs][1][paragraphs_container_paragraphs][0][paragraphs_container_paragraphs');
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][1][_path]')
      ->setValue('field_paragraphs][1][paragraphs_container_paragraphs');
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][2][dragdrop][paragraphs_container_paragraphs][list][0][_path]')
      ->setValue('field_paragraphs][1][paragraphs_container_paragraphs');
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][1][_weight]')
      ->setValue(0);
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][2][dragdrop][paragraphs_container_paragraphs][list][0][_weight]')
      ->setValue(1);
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][2][_weight]')
      ->setValue(1);

    // Save immediately, without separately confirming the widget changes.
    $this->drupalPostForm(NULL, [], 'Save');

    // Reset the cache to make sure that the loaded parents are the new ones.
    \Drupal::entityTypeManager()->getStorage('paragraph')->resetCache();
    // Assert the new parents of the text paragraphs.
    $text_paragraph_1 = Paragraph::load($text_paragraph_1->id());
    $this->assertEquals($text_paragraph_1->get('parent_id')->value, $paragraph_2->id());
    $this->assertEquals($text_paragraph_1->get('parent_type')->value, 'paragraph');

    $text_paragraph_3 = Paragraph::load($text_paragraph_3->id());
    $this->assertEquals($text_paragraph_3->get('parent_id')->value, $paragraph_1->id());
    $this->assertEquals($text_paragraph_3->get('parent_type')->value, 'paragraph');

    // Assert the new parent of the container.
    $paragraph_3 =Paragraph::load($paragraph_3->id());
    $this->assertEquals($paragraph_3->get('parent_id')->value, $paragraph_4->id());
    $this->assertEquals($paragraph_3->get('parent_type')->value, 'paragraph');
  }

  /**
   * Tests that a separate field is not affected by reordering one field.
   */
  public function testChangeParagraphContainerMultipleFields() {
    $this->addParagraphsField('paragraphed_test', 'field_paragraphs_second', 'node');

    // Create text paragraph.
    $text_paragraph_1 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 1',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_1->save();

    // Create second text paragraph.
    $text_paragraph_2 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 2',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_2->save();

    // Create container that contains the first two text paragraphs.
    $paragraph = Paragraph::create([
      'title' => 'Test Paragraph',
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [$text_paragraph_1],
    ]);
    $paragraph->save();

    // Create an empty container paragraph.
    $paragraph_1 = Paragraph::create([
      'title' => 'Test Paragraph 1',
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [],
    ]);
    $paragraph_1->save();

    // Create a container paragraph for the second field.
    $paragraph_second = Paragraph::create([
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [$text_paragraph_2],
    ]);
    $paragraph_second->save();

    // Add test content with paragraph container and the third text paragraph.
    $node = Node::create([
      'type' => 'paragraphed_test',
      'title' => 'Paragraphs Test',
      'field_paragraphs' => [$paragraph, $paragraph_1],
      'field_paragraphs_second' => [$paragraph_second],
    ]);
    $node->save();

    // Change the path of the text paragraph to the empty container as its
    // parent.
    $this->drupalGet('/node/' . $node->id() . '/edit');
    $this->drupalPostForm(NULL, [], 'Drag & drop');

    // Make sure that the second paragraph field is still displayed normally by
    // checking that it displays the edit button, as it is closed by default.
    // @todo: Introduce a global drag and drop mode?
    $this->assertSession()->buttonExists('field_paragraphs_second_0_subform_paragraphs_container_paragraphs_0_edit');

    // Change the path of the text paragraph to the empty container as its
    // parent.
    $this->assertSession()
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][_path]')
      ->setValue('field_paragraphs][1][paragraphs_container_paragraphs');
    $this->drupalPostForm(NULL, [], 'Save');

    // Check that the parent of the text paragraph is the second paragraph
    // container.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    \Drupal::entityTypeManager()->getStorage('paragraph')->resetCache();
    $node = Node::load($node->id());
    $this->assertEquals(count($node->get('field_paragraphs')), 2);

    $this->assertEquals($node->get('field_paragraphs')->get(0)->target_id, $paragraph->id());
    $this->assertEquals($node->get('field_paragraphs')->get(1)->target_id, $paragraph_1->id());
    $paragraph = $node->get('field_paragraphs')->get(0)->entity;

    $this->assertEquals(count($paragraph->get('paragraphs_container_paragraphs')), 0);

    $paragraph_1 = $node->get('field_paragraphs')->get(1)->entity;
    $this->assertEquals(count($paragraph_1->get('paragraphs_container_paragraphs')), 1);
    $this->assertEquals($paragraph_1->get('paragraphs_container_paragraphs')->get(0)->target_id, $text_paragraph_1->id());

    $text_paragraph_1 = $paragraph_1->get('paragraphs_container_paragraphs')->entity;
    $this->assertEquals($text_paragraph_1->get('parent_id')->value, $paragraph_1->id());
    $this->assertEquals($text_paragraph_1->get('parent_type')->value, 'paragraph');

    // Assert the second field.
    $this->assertEquals(count($node->get('field_paragraphs_second')), 1);

    $this->assertEquals($node->get('field_paragraphs_second')->get(0)->target_id, $paragraph_second->id());
    $paragraph_second = $node->get('field_paragraphs_second')->get(0)->entity;

    $this->assertEquals(count($paragraph_second->get('paragraphs_container_paragraphs')), 1);
    $this->assertEquals($paragraph_second->get('paragraphs_container_paragraphs')->get(0)->target_id, $text_paragraph_2->id());
  }

  /**
   * Tests moving a paragraph and after that enable the drag and drop mode.
   */
  public function testChangeParagraphMoveBeforeReorder() {
    // Create text paragraph.
    $text_paragraph_1 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 1',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_1->save();

    // Create text paragraph.
    $text_paragraph_2 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 2',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_2->save();

    // Create container that contains the first text paragraphs.
    $paragraph = Paragraph::create([
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [$text_paragraph_1],
    ]);
    $paragraph->save();

    // Create an empty container paragraph.
    $paragraph_1 = Paragraph::create([
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [],
    ]);
    $paragraph_1->save();

    // Create the node with two containers and the second text in the middle.
    $node = Node::create([
      'type' => 'paragraphed_test',
      'title' => 'Paragraphs Test',
      'field_paragraphs' => [$paragraph, $text_paragraph_2, $paragraph_1],
    ]);
    $node->save();

    $this->drupalGet('/node/' . $node->id() . '/edit');

    // Move the second text below the container.
    $edit = [
      'field_paragraphs[1][_weight]' => 2,
      'field_paragraphs[2][_weight]' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, 'Drag & drop');

    // Change the path of the text paragraph to the empty container as its
    // parent.
    $this->assertSession()
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][_path]')
      ->setValue('field_paragraphs][1][paragraphs_container_paragraphs');
    $this->drupalPostForm(NULL, [], 'Complete drag & drop');

    $this->drupalPostForm(NULL, [], 'Save');

    // Check that the parent of the text paragraph is the second paragraph
    // container.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    \Drupal::entityTypeManager()->getStorage('paragraph')->resetCache();
    $node = Node::load($node->id());
    $this->assertEquals(count($node->get('field_paragraphs')), 3);

    $this->assertEquals($node->get('field_paragraphs')->get(0)->target_id, $paragraph->id());
    $this->assertEquals($node->get('field_paragraphs')->get(1)->target_id, $paragraph_1->id());
    $this->assertEquals($node->get('field_paragraphs')->get(2)->target_id, $text_paragraph_2->id());
    $paragraph = $node->get('field_paragraphs')->get(0)->entity;

    $this->assertEquals(count($paragraph->get('paragraphs_container_paragraphs')), 0);

    $paragraph_1 = $node->get('field_paragraphs')->get(1)->entity;
    $this->assertEquals(count($paragraph_1->get('paragraphs_container_paragraphs')), 1);
    $this->assertEquals($paragraph_1->get('paragraphs_container_paragraphs')->get(0)->target_id, $text_paragraph_1->id());

    $text_paragraph_1 = $paragraph_1->get('paragraphs_container_paragraphs')->entity;
    $this->assertEquals($text_paragraph_1->get('parent_id')->value, $paragraph_1->id());
    $this->assertEquals($text_paragraph_1->get('parent_type')->value, 'paragraph');
  }

  /**
   * Tests deleting a paragraph and after that enable the drag and drop mode.
   */
  public function testChangeParagraphMoveAfterDelete() {
    // Create text paragraph.
    $text_paragraph_1 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 1',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_1->save();

    // Create text paragraph.
    $text_paragraph_2 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 2',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_2->save();

    // Create container that contains the first text paragraphs.
    $paragraph = Paragraph::create([
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [$text_paragraph_1],
    ]);
    $paragraph->save();

    // Create an empty container paragraph.
    $paragraph_1 = Paragraph::create([
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [],
    ]);
    $paragraph_1->save();

    // Create the node with two containers and the second text in the middle.
    $node = Node::create([
      'type' => 'paragraphed_test',
      'title' => 'Paragraphs Test',
      'field_paragraphs' => [$paragraph, $text_paragraph_2, $paragraph_1],
    ]);
    $node->save();

    $this->drupalGet('/node/' . $node->id() . '/edit');

    // Delete the first container, move the text 2 paragraph into the second
    // container.
    $this->getSession()->getPage()->pressButton('field_paragraphs_0_remove');
    $this->drupalPostForm(NULL, [], 'Drag & drop');

    $assert_session = $this->assertSession();

    $assert_session->pageTextNotContains('Test text 1');
    $assert_session->pageTextContains('Test text 2');

    // Change the path of the text 2 paragraph to the empty container as its
    // parent.
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][_path]')
      ->setValue('field_paragraphs][0][paragraphs_container_paragraphs');
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][1][_weight]')
      ->setValue(0);

    $this->drupalPostForm(NULL, [], 'Complete drag & drop');
    $this->drupalPostForm(NULL, [], 'Save');

    // Check that the parent of the text paragraph is the second paragraph
    // container.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    \Drupal::entityTypeManager()->getStorage('paragraph')->resetCache();
    $node = Node::load($node->id());
    $this->assertEquals(count($node->get('field_paragraphs')), 1);

    $this->assertEquals($node->get('field_paragraphs')->get(0)->target_id, $paragraph_1->id());
    $paragraph_1 = $node->get('field_paragraphs')->get(0)->entity;
    $this->assertEquals(count($paragraph_1->get('paragraphs_container_paragraphs')), 1);
    $this->assertEquals($paragraph_1->get('paragraphs_container_paragraphs')->get(0)->target_id, $text_paragraph_2->id());

    $text_paragraph_2 = $paragraph_1->get('paragraphs_container_paragraphs')->entity;
    $this->assertEquals($text_paragraph_2->get('parent_id')->value, $paragraph_1->id());
    $this->assertEquals($text_paragraph_2->get('parent_type')->value, 'paragraph');
  }

  /**
   * Tests emptying a top level container.
   */
  public function testChangeParagraphMoveAllFromTopLevelContainer() {
    // Create text paragraph.
    $text_paragraph_1 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 1',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_1->save();

    // Create a second text paragraph.
    $text_paragraph_2 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 2.',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_2->save();

    // Create container that contains the two text paragraphs.
    $paragraph = Paragraph::create([
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [$text_paragraph_1, $text_paragraph_2],
    ]);
    $paragraph->save();

    // Add test node with paragraph container.
    $node = Node::create([
      'type' => 'paragraphed_test',
      'title' => 'Paragraphs Test',
      'field_paragraphs' => [$paragraph],
    ]);
    $node->save();

    // Check that the parent of the second text paragraph is the paragraph
    // container.
    $text_paragraph_2 = Paragraph::load($text_paragraph_2->id());
    $this->assertEquals($text_paragraph_2->get('parent_id')->value, $paragraph->id());
    $this->assertEquals($text_paragraph_2->get('parent_type')->value, 'paragraph');

    $this->drupalGet('/node/' . $node->id() . '/edit');
    $this->drupalPostForm(NULL, [], 'Drag & drop');

    $assert_session = $this->assertSession();
    $assert_session->hiddenFieldValueEquals('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][_path]', 'field_paragraphs][0][paragraphs_container_paragraphs');
    $assert_session->hiddenFieldValueEquals('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][1][_path]', 'field_paragraphs][0][paragraphs_container_paragraphs');

    // Change the path of both text paragraphs to the node as their parent.
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][_path]')
      ->setValue('field_paragraphs');
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][1][_path]')
      ->setValue('field_paragraphs');
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][_weight]')
      ->setValue(0);
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][1][_weight]')
      ->setValue(1);
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][_weight]')
      ->setValue(2);

    $this->drupalPostForm(NULL, [], 'Complete drag & drop');
    $this->drupalPostForm(NULL, [], 'Save');

    // Check the new structure of the node and its paragraphs.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    \Drupal::entityTypeManager()->getStorage('paragraph')->resetCache();
    $node = Node::load($node->id());
    $this->assertEquals(3, count($node->get('field_paragraphs')));

    $this->assertEquals($node->get('field_paragraphs')->get(0)->target_id, $text_paragraph_1->id());
    $text_paragraph_1 = $node->get('field_paragraphs')->get(0)->entity;
    $this->assertEquals('node', $text_paragraph_1->get('parent_type')->value);
    $this->assertEquals($node->id(), $text_paragraph_1->get('parent_id')->value);

    $this->assertEquals($node->get('field_paragraphs')->get(1)->target_id, $text_paragraph_2->id());
    $text_paragraph_2 = $node->get('field_paragraphs')->get(1)->entity;
    $this->assertEquals('node', $text_paragraph_2->get('parent_type')->value);
    $this->assertEquals($node->id(), $text_paragraph_2->get('parent_id')->value);

    $this->assertEquals($node->get('field_paragraphs')->get(2)->target_id, $paragraph->id());
    $paragraph = $node->get('field_paragraphs')->get(2)->entity;
    $this->assertEquals('node', $paragraph->get('parent_type')->value);
    $this->assertEquals($node->id(), $paragraph->get('parent_id')->value);

    $this->assertEquals(0, count($paragraph->get('paragraphs_container_paragraphs')));
  }

  /**
   * Tests emptying a nested container.
   */
  public function testChangeParagraphMoveAllFromNestedContainer() {
    // Create text paragraph.
    $text_paragraph_1 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 1',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_1->save();

    // Create a second text paragraph.
    $text_paragraph_2 = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => 'Test text 2.',
        'format' => 'plain_text',
      ],
    ]);
    $text_paragraph_2->save();

    // Create a nested container that contains the two text paragraphs.
    $nested_container = Paragraph::create([
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [$text_paragraph_1, $text_paragraph_2],
    ]);
    $nested_container->save();

    // Create a container that contains the first two text paragraphs.
    $container = Paragraph::create([
      'type' => 'paragraphs_container',
      'paragraphs_container_paragraphs' => [$nested_container],
    ]);
    $container->save();

    // Add test node with paragraph container.
    $node = Node::create([
      'type' => 'paragraphed_test',
      'title' => 'Paragraphs Test',
      'field_paragraphs' => [$container],
    ]);
    $node->save();

    // Check that the parent of the second text paragraph is the nested
    // container.
    $text_paragraph_2 = Paragraph::load($text_paragraph_2->id());
    $this->assertEquals($text_paragraph_2->get('parent_id')->value, $nested_container->id());
    $this->assertEquals($text_paragraph_2->get('parent_type')->value, 'paragraph');

    $this->drupalGet('/node/' . $node->id() . '/edit');
    $this->drupalPostForm(NULL, [], 'Drag & drop');

    $assert_session = $this->assertSession();
    $assert_session->hiddenFieldValueEquals('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][_path]', 'field_paragraphs][0][paragraphs_container_paragraphs][0][paragraphs_container_paragraphs');
    $assert_session->hiddenFieldValueEquals('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][1][_path]', 'field_paragraphs][0][paragraphs_container_paragraphs][0][paragraphs_container_paragraphs');

    // Change the path of both text paragraphs to the top container as their
    // parent with the nested container in the middle.
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][_path]')
      ->setValue('field_paragraphs][0][paragraphs_container_paragraphs');
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][1][_path]')
      ->setValue('field_paragraphs][0][paragraphs_container_paragraphs');
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][_weight]')
      ->setValue(0);
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][_weight]')
      ->setValue(1);
    $assert_session
      ->hiddenFieldExists('field_paragraphs[dragdrop][field_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][0][dragdrop][paragraphs_container_paragraphs][list][1][_weight]')
      ->setValue(2);

    $this->drupalPostForm(NULL, [], 'Complete drag & drop');
    $this->drupalPostForm(NULL, [], 'Save');

    // Check the new structure of the node and its paragraphs.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    \Drupal::entityTypeManager()->getStorage('paragraph')->resetCache();
    $node = Node::load($node->id());
    $this->assertEquals(1, count($node->get('field_paragraphs')));

    $this->assertEquals($container->id(), $node->get('field_paragraphs')->get(0)->target_id);
    $container = $node->get('field_paragraphs')->get(0)->entity;
    $this->assertEquals('node', $container->get('parent_type')->value);
    $this->assertEquals($node->id(), $container->get('parent_id')->value);

    $this->assertEquals(3, count($container->get('paragraphs_container_paragraphs')));
    $this->assertEquals($text_paragraph_1->id(), $container->get('paragraphs_container_paragraphs')->get(0)->target_id);
    $text_paragraph_1 = $container->get('paragraphs_container_paragraphs')->get(0)->entity;
    $this->assertEquals('paragraph', $text_paragraph_1->get('parent_type')->value);
    $this->assertEquals($container->id(), $text_paragraph_1->get('parent_id')->value);

    $this->assertEquals($nested_container->id(), $container->get('paragraphs_container_paragraphs')->get(1)->target_id);
    $nested_container = $container->get('paragraphs_container_paragraphs')->get(1)->entity;
    $this->assertEquals('paragraph', $nested_container->get('parent_type')->value);
    $this->assertEquals($container->id(), $nested_container->get('parent_id')->value);
    $this->assertEquals(count($nested_container->get('paragraphs_container_paragraphs')), 0);

    $this->assertEquals($text_paragraph_2->id(), $container->get('paragraphs_container_paragraphs')->get(2)->target_id);
    $text_paragraph_2 = $container->get('paragraphs_container_paragraphs')->get(2)->entity;
    $this->assertEquals('paragraph', $text_paragraph_2->get('parent_type')->value);
    $this->assertEquals($container->id(), $text_paragraph_2->get('parent_id')->value);
  }

}
