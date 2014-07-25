<?php

use Rhumsaa\Uuid\Uuid;

/**
 * Return a new uuid for message id.
 */
function msg_id() {
  return UUID::uuid4();
}

/**
 * Sign a message with a secure key.
 */
function sign($message_list) {
  global $signature_schemes;
  global $signature_scheme;
  global $secure_key;

  $hm = hash_init($signature_schemes[$signature_scheme], HASH_HMAC, $secure_key);
  foreach ($message_list as $item) {
    hash_update($hm, $item);
  }
  return hash_final($hm);
}

function new_header($msg_type) {
  return [
    "date" => (new DateTime('NOW'))->format('c'),
    "msg_id" => msg_id(),
    "username" => "kernel",
    "session" => $GLOBALS['engine_id'],
    "msg_type" => $msg_type,
  ];
}

/**
 * @param ZmqSocket $stream
 * @param $msg_type
 * @param null $content
 * @param null $parent_header
 * @param null $metadata
 * @param null $identifie
 */
function send($stream, $msg_type, $content = NULL, $parent_header = NULL, $metadata = NULL, $identifier = NULL) {
  $header = new_header($msg_type);

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
  $parts[] = sign($msg_list);
  $parts[] = $msg_list[0];
  $parts[] = $msg_list[1];
  $parts[] = $msg_list[2];
  $parts[] = $msg_list[3];

  //  $stream->sendmulti($parts);
  $stream->send($parts);
  // stream.flush()
}

function message_kernel_info_reply() {
  $content = [
    'protocol_version' => '4.0.0',
    'implementation' => 'ipython',
    'implementation_version' => '1.0.0',
    'language' => 'php',
    'language_version' => '5.4.0',
    'banner' => 'Hey ho, here is php.',
  ];
  return $content;
}

function message_deserialze($message) {
}
