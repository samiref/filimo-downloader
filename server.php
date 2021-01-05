<?php

$host = "127.0.0.1";
$port = "7667";
$public = __DIR__ . DIRECTORY_SEPARATOR . "public";

echo "Video Downloader server started on http://{$host}:{$port}/\n";
passthru(PHP_BINARY . " -S {$host}:{$port} -t \"{$public}\" 2>&1");

