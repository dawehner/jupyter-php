<?php

namespace dawehner\IPythonPhp;

interface MessageInterface {

  public function execute(array $header, $content);

}
