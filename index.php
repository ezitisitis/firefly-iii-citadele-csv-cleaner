<?php
include_once 'packages/Statement.php';


$cliHeader = <<<TXT
  _____ _ _            _      _        _        
 / ____(_) |          | |    | |      | |       
| |     _| |_ __ _  __| | ___| | ___  | |_ ___  
| |    | | __/ _` |/ _` |/ _ \ |/ _ \ | __/ _ \ 
| |____| | || (_| | (_| |  __/ |  __/ | || (_) |
 \_____|_|\__\__,_|\__,_|\___|_|\___|  \__\___/ 
                                                
                                                
 ______ _           __ _       _____ _____ _____ 
|  ____(_)         / _| |     |_   _|_   _|_   _|
| |__   _ _ __ ___| |_| |_   _  | |   | |   | |  
|  __| | | '__/ _ \  _| | | | | | |   | |   | |  
| |    | | | |  __/ | | | |_| |_| |_ _| |_ _| |_ 
|_|    |_|_|  \___|_| |_|\__, |_____|_____|_____|
                          __/ |                  
                         |___/                   
TXT;

echo $cliHeader . "\n\n\n";

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
fputcsv($newFile, $statement->getHeaders(), ',', '"');
foreach ($data as $line) {
    fputcsv($newFile, $line, ',', '"');
}

echo "CSV cleaning is finished\n";
echo "You can find your file located at: " . $filePath.'-generated.csv';