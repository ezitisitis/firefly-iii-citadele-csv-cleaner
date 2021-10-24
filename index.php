<?php
include_once 'packages/Statement.php';

// @todo define regexes

$options = getopt('f:', ['file:']);

if (empty($options)) {
    exit('"file" option should be specified');
}

if (strpos($options['file'], '~') === 0) {
    $filePath = str_replace('~', $_SERVER['HOME'],$options['file']);
} else {
    $filePath = $options['file'];
}

if (!file_exists($filePath)) {
    exit('File "' . $filePath . '" doesn\'t exist');
}

$file = fopen($filePath, 'r');
while(($line = fgetcsv($file, 0, '|', '"')) !== false) {
    $data[] = $line;
}
fclose($file);

$statement = (new Statement($data));
$statement->clearData();
$statement->transformData();

$newFile = fopen($filePath.'-generated.csv', 'w');
$data = $statement->getNewData();
foreach ($data as $line) {
    fputcsv($newFile, $line, ',', '"');
}

echo "Danonki";
