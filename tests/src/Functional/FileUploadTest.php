<?php

namespace Drupal\Tests\file_encrypt\Functional;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests uploading files as well as viewing files on the rendered entity.
 *
 * @group file_encrypt
 */
class FileUploadTest extends FunctionalTestBase {

  /**
   * @var boolean
   */
  protected $generatedTestFiles;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    NodeType::create([
      'type' => 'page',
    ])->save();

    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test_file',
      'type' => 'file',
      'settings' => [
        'uri_scheme' => 'encrypt',
      ],
      'third_party_settings' => [
        'file_encrypt' => [
          'encryption_profile' => 'encryption_profile_1',
        ],
      ],
    ])->save();

    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test_file',
      'bundle' => 'page',
      'settings' => [
        'file_directory' => 'encryption_profile_1',
        'file_extensions' => 'txt',
      ],
    ])->save();

    $this->drupalGetTestFiles('text');

    $form_display = entity_get_form_display('node', 'page', 'default');
    $form_display->setComponent('field_test_file', [
      'type' => 'file_generic',
    ]);
    $form_display->save();

    $view_display = entity_get_display('node', 'page', 'default');
    $view_display->setComponent('field_test_file', [
      'type' => 'file_url_plain',
    ]);
    $view_display->save();
  }

  /**
   * Tests uploading an actual file.
   */
  public function testFileUpload() {
    $account = $this->drupalCreateUser(['create page content']);
    $this->drupalLogin($account);

    $text_files = $this->drupalGetTestFiles('text');
    $text_file = File::create((array) current($text_files));
    $text_file->getFileUri();

    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');
    $assert->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => 'Test title',
      'files[field_test_file_0]' => drupal_realpath($text_file->getFileUri()),
    ];
    $this->submitForm($edit, 'Save');

    // Ensure the file was saved.
    $nodes = Node::loadMultiple();
    $this->assertCount(1, $nodes);
    $last_node = end($nodes);
    $this->assertEquals('encrypt://encryption_profile_1/text-0_0.txt', $last_node->field_test_file->entity->getFileUri());
  
    // Ensure the file was visible.
    file_put_contents('/tmp/foo.html', $this->getSession()->getPage()->getHtml());
    $assert->pageTextContains('/encrypt/files?file=encryption_profile_1/text-0_0.txt');
  }

  /**
   * Gets a list of files that can be used in tests.
   *
   * The first time this method is called, it will call
   * simpletest_generate_file() to generate binary and ASCII text files in the
   * public:// directory. It will also copy all files in
   * core/modules/simpletest/files to public://. These contain image, SQL, PHP,
   * JavaScript, and HTML files.
   *
   * All filenames are prefixed with their type and have appropriate extensions:
   * - text-*.txt
   * - binary-*.txt
   * - html-*.html and html-*.txt
   * - image-*.png, image-*.jpg, and image-*.gif
   * - javascript-*.txt and javascript-*.script
   * - php-*.txt and php-*.php
   * - sql-*.txt and sql-*.sql
   *
   * Any subsequent calls will not generate any new files, or copy the files
   * over again. However, if a test class adds a new file to public:// that
   * is prefixed with one of the above types, it will get returned as well, even
   * on subsequent calls.
   *
   * @param $type
   *   File type, possible values: 'binary', 'html', 'image', 'javascript',
   *   'php', 'sql', 'text'.
   * @param $size
   *   (optional) File size in bytes to match. Defaults to NULL, which will not
   *   filter the returned list by size.
   *
   * @return
   *   List of files in public:// that match the filter(s).
   */
  protected function drupalGetTestFiles($type, $size = NULL) {
    if (empty($this->generatedTestFiles)) {
      // Generate binary test files.
      $lines = array(64, 1024);
      $count = 0;
      foreach ($lines as $line) {
        simpletest_generate_file('binary-' . $count++, 64, $line, 'binary');
      }

      // Generate ASCII text test files.
      $lines = array(16, 256, 1024, 2048, 20480);
      $count = 0;
      foreach ($lines as $line) {
        simpletest_generate_file('text-' . $count++, 64, $line, 'text');
      }

      // Copy other test files from simpletest.
      $original = drupal_get_path('module', 'simpletest') . '/files';
      $files = file_scan_directory($original, '/(html|image|javascript|php|sql)-.*/');
      foreach ($files as $file) {
        file_unmanaged_copy($file->uri, PublicStream::basePath());
      }

      $this->generatedTestFiles = TRUE;
    }

    $files = array();
    // Make sure type is valid.
    if (in_array($type, array('binary', 'html', 'image', 'javascript', 'php', 'sql', 'text'))) {
      $files = file_scan_directory('public://', '/' . $type . '\-.*/');

      // If size is set then remove any files that are not of that size.
      if ($size !== NULL) {
        foreach ($files as $file) {
          $stats = stat($file->uri);
          if ($stats['size'] != $size) {
            unset($files[$file->uri]);
          }
        }
      }
    }
    usort($files, array($this, 'drupalCompareFiles'));
    return $files;
  }

  /**
   * Compare two files based on size and file name.
   */
  protected function drupalCompareFiles($file1, $file2) {
    $compare_size = filesize($file1->uri) - filesize($file2->uri);
    if ($compare_size) {
      // Sort by file size.
      return $compare_size;
    }
    else {
      // The files were the same size, so sort alphabetically.
      return strnatcmp($file1->name, $file2->name);
    }
  }

}
