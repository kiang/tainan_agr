<?php

$filePath = __DIR__ . '/files';
if(!file_exists($filePath)) {
  mkdir($filePath, 0777, true);
}
$fh = fopen(__DIR__ . '/list.csv', 'r');
/*
Array
(
    [0] => file
    [1] => source
)
*/
fgetcsv($fh, 2048);
while($line = fgetcsv($fh, 2048)) {
  $parts = explode('/', $line[0]);
  $fileName = pathinfo(array_pop($parts));
  $targetFile = $filePath . '/' . $fileName['basename'];
  $newUrl = implode('/', $parts) . '/' . urlencode($fileName['filename']) . '.' . $fileName['extension'];
  file_put_contents($targetFile, file_get_contents($newUrl));
}
