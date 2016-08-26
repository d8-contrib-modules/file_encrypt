<?php

namespace Drupal\file_encrypt\StreamFilter;

/**
 * Provides a base class for stream filters.
 */
abstract class StreamFilterBase extends \php_user_filter {

  /**
   * The encryption service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface
   */
  protected $encryption;

  /**
   * The encryption profile.
   *
   * @var \Drupal\encrypt\EncryptionProfileInterface
   */
  protected $encryptionProfile;

  /**
   * {@inheritdoc}
   */
  public function onCreate() {
    $this->encryption = \Drupal::service('encryption');
    $this->encryptionProfile = $this->params['encryption_profile'];
  }

  /**
   * {@inheritdoc}
   */
  public function filter($in, $out, &$consumed, $closing) {
    while ($bucket = stream_bucket_make_writeable($in)) {
      $bucket->data = $this->filterData($bucket->data);
      $consumed += $bucket->datalen;
      stream_bucket_append($out, $bucket);
    }
    return PSFS_PASS_ON;
  }

  /**
   * Filters data.
   *
   * @param string $data
   *   The data to filter.
   *
   * @return string
   *   The filtered data.
   */
  abstract protected function filterData($data);

}
