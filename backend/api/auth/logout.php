<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers.php';

api_require_method('POST');

logout();

json_response(200, [
  'status' => 'ok'
]);
