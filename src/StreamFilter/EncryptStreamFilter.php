<?php

namespace Drupal\file_encrypt\StreamFilter;

/**
 * Provides a stream filter for encryption.
 */
class EncryptStreamFilter extends StreamFilterBase {

  /**
   * {@inheritdoc}
   */
  protected function filterData($data) {
    return $this->encryption->encrypt($data, $this->encryptionProfile);
  }

}
