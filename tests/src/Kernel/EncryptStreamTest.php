<?php

namespace Drupal\Tests\file_encrypt\Kernel;

use Drupal\file_encrypt\EncryptStreamWrapper;

/**
 * @group file_encrypt
 */
class EncryptStreamTest extends FileEncryptTestBase {

  public function testDecrypt() {
    $this->assertFalse(file_exists('vfs://root/encrypt_test/example.txt'));
    $this->assertFalse(file_exists(EncryptStreamWrapper::SCHEME . '://encryption_profile_1/example.txt'));

    file_put_contents(EncryptStreamWrapper::SCHEME . '://encryption_profile_1/example.txt', 'test-data');
    $this->assertNotEquals('test-data', file_get_contents('vfs://root/encrypt_test/example.txt'));

    $content = file_get_contents(EncryptStreamWrapper::SCHEME . '://encryption_profile_1/example.txt');
    $this->assertEquals('test-data', $content);
  }

}
