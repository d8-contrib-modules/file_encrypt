<?php

namespace Drupal\file_encrypt;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

class EncryptBinaryFileResponse extends BinaryFileResponse  {

  /**
   * {@inheritdoc}
   */
  public function prepare(Request $request) {
    parent::prepare($request);
    $this->fixContentLengthHeader();
    $this->setPrivate();
    return $this;
  }

  /**
   * Fixes the Content-Length response header.
   *
   * This works around a bug in symfony/http-foundation that causes
   * BinaryFileResponse to send the wrong Content-Length header value for files
   * modified by stream filters as encrypted files are. See
   * https://github.com/symfony/symfony/issues/19738.
   */
  protected function fixContentLengthHeader() {
    $file = fopen($this->getFile()->getPathname(), 'rb');
    $this->headers->set('Content-Length', fstat($file)['size']);
    fclose($file);
  }

}
