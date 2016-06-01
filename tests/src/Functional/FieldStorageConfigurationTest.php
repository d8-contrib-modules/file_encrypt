<?php

namespace Drupal\Tests\file_encrypt\Functional;

use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\key\Entity\Key;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the field storage configuration UI.
 *
 * @group file_encrypt
 */
class FieldStorageConfigurationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file_encrypt', 'encrypt', 'key', 'encrypt_test', 'node', 'file', 'field_ui'];

  /**
   * A list of testkeys.
   *
   * @var \Drupal\key\Entity\Key[]
   */
  protected $testKeys;

  /**
   * A list of test encryption profiles.
   *
   * @var \Drupal\encrypt\Entity\EncryptionProfile[]
   */
  protected $encryptionProfiles;

  /**
   * Creates test keys for usage in tests.
   */
  protected function createTestKeys() {
    // Create a 128bit testkey.
    $key_128 = Key::create([
      'id' => 'testing_key_128',
      'label' => 'Testing Key 128 bit',
      'key_type' => "encryption",
      'key_type_settings' => ['key_size' => '128'],
      'key_provider' => 'config',
      'key_provider_settings' => ['key_value' => 'mustbesixteenbit'],
    ]);
    $key_128->save();
    $this->testKeys['testing_key_128'] = $key_128;

    // Create a 256bit testkey.
    $key_256 = Key::create([
      'id' => 'testing_key_256',
      'label' => 'Testing Key 256 bit',
      'key_type' => "encryption",
      'key_type_settings' => ['key_size' => '256'],
      'key_provider' => 'config',
      'key_provider_settings' => ['key_value' => 'mustbesixteenbitmustbesixteenbit'],
    ]);
    $key_256->save();
    $this->testKeys['testing_key_256'] = $key_256;
  }

  /**
   * Creates test encryption profiles for usage in tests.
   */
  protected function createTestEncryptionProfiles() {
    // Create test encryption profiles.
    $encryption_profile_1 = EncryptionProfile::create([
      'id' => 'encryption_profile_1',
      'label' => 'Encryption profile 1',
      'encryption_method' => 'test_encryption_method',
      'encryption_key' => $this->testKeys['testing_key_128']->id(),
    ]);
    $encryption_profile_1->save();
    $this->encryptionProfiles['encryption_profile_1'] = $encryption_profile_1;

    $encryption_profile_2 = EncryptionProfile::create([
      'id' => 'encryption_profile_2',
      'label' => 'Encryption profile 2',
      'encryption_method' => 'config_test_encryption_method',
      'encryption_method_configuration' => ['mode' => 'CFB'],
      'encryption_key' => $this->testKeys['testing_key_256']->id(),
    ]);
    $encryption_profile_2->save();
    $this->encryptionProfiles['encryption_profile_2'] = $encryption_profile_2;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createTestKeys();
    $this->createTestEncryptionProfiles();
  }

  public function testFieldStorageSettingsForm() {
    $account = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node display',
    ]);
    $this->drupalLogin($account);

    NodeType::create([
      'type' => 'page',
    ])->save();

    $assert = $this->assertSession();
    $this->drupalGet('admin/structure/types/manage/page/fields/add-field');
    $assert->statusCodeEquals(200);

    $this->submitForm([
      'new_storage_type' => 'file',
      'field_name' => 'test_file',
      'label' => 'New file field',
    ], 'Save and continue');

    file_put_contents('/tmp/foo.html', $this->getSession()->getPage()->getHtml());

    $this->submitForm([
      'settings[uri_scheme]' => 'encrypt',
      'settings[encryption_profile]' => 'encryption_profile_1',
    ], 'Save field settings');

    $field_storage_config = FieldStorageConfig::load('node.field_test_file');
    $this->assertEquals('encryption_profile_1', $field_storage_config->getThirdPartySetting('file_encrypt', 'encryption_profile'));
  }

}
