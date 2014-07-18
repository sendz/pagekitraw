<?php

ini_set('display_errors','On'); ini_set('error_reporting','E_ALL | E_STRICT'); error_reporting(E_ALL);

if (version_compare($ver = PHP_VERSION, $req = '5.4.0', '<')) {
	exit(sprintf('You are running PHP %s, but Pagekit needs at least <strong>PHP %s</strong> to run.', $ver, $req));
}

$app = require_once __DIR__.'/app/app.php';
$app->run();