<?php

namespace Drupal\file_encrypt\StreamFilter;

/**
 * Provides a stream filter for decryption.
 */
class DecryptStreamFilter extends StreamFilterBase {

  /**
   * {@inheritdoc}
   */
  protected function filterData($data) {
    return $this->encryption->decrypt($data, $this->encryptionProfile);
  }

}
