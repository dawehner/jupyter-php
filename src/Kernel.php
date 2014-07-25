<?php

/**
 * @file
 * Contains \dawehner\IpythonPhp\Kernel.
 */

namespace dawehner\IPythonPhp;

use DateTime;
use Rhumsaa\Uuid\Uuid;

class Kernel {

  protected $secureKey;

  protected $signatureScheme;

  protected $engineId;

  public function setSecureKey($secure_key) {
    $this->secureKey = $secure_key;
  }

  /**
   * @return mixed
   */
  public function getSecureKey() {
    return $this->secureKey;
  }

  public function setSignatureScheme($signature_scheme) {
    $this->signatureScheme = $signature_scheme;
  }

  public function getSignatureScheme() {
    return $this->signatureScheme;
  }

  /**
   * @return mixed
   */
  public function getEngineId() {
    return $this->engineId;
  }

  /**
   * @param mixed $engineId
   */
  public function setEngineId($engineId) {
    $this->engineId = $engineId;
  }

  public static function getMessageKernelInfo() {
    return [
      'protocol_version' => '4.0.0',
      'implementation' => 'ipython',
      'implementation_version' => '1.0.0',
      'language' => 'php',
      'language_version' => '5.4.0',
      'banner' => 'Hey ho, here is php.',
    ];
  }
  public function newHeader($msg_type) {
    return [
      "date" => (new DateTime('NOW'))->format('c'),
      "msg_id" => static::generateMsgId(),
      "username" => "kernel",
      "session" => $this->getEngineId(),
      "msg_type" => $msg_type,
    ];
  }

  /**
   * Return a new uuid for message id.
   */
  public static function generateMsgId() {
    return UUID::uuid4();
  }

  public static function getSignatureSchemes() {
    return [
      'hmac-sha256' => 'sha256'
    ];
  }

  /**
   * Sign a message with a secure key.
   *
   * @param array $message_list
   *   The message list.
   *
   * @return string
   *   The signature for the message_list.
   */
  public function sign(array $message_list = array()) {
    $hm = hash_init(
      static::getSignatureSchemes()[$this->getSignatureScheme()],
      HASH_HMAC,
      $this->getSecureKey()
    );
    foreach ($message_list as $item) {
      hash_update($hm, $item);
    }
    return hash_final($hm);
  }

  public function send($stream, $msg_type, $content = NULL, $parent_header = NULL, $metadata = NULL, $identifier = NULL) {
    $header = $this->newHeader($msg_type);

    if (!isset($content)) {
      $content = [];
    }
    if (!isset($parent_header)) {
      $parent_header = [];
    }
    if (!isset($metadata)) {
      $metadata = [];
    }

    $msg_list = [
      json_encode($header),
      json_encode($parent_header),
      json_encode($metadata),
      json_encode($content),
    ];

    $delim = '<IDS|MSG>';
    $parts = [];
    $parts[] = $delim;
    $parts[] = $this->sign($msg_list);
    $parts[] = $msg_list[0];
    $parts[] = $msg_list[1];
    $parts[] = $msg_list[2];
    $parts[] = $msg_list[3];

    //  $stream->sendmulti($parts);
    $stream->send($parts);
    // stream.flush()
  }

}
