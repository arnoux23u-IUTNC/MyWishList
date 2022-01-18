<?php

$lines = 0;

function getDirContents($dir) {

    global $lines;

    $files = scandir($dir);
    
    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        //exclude images and css
        if (!is_dir($path) && !preg_match('/^\./', $value)) {
            if(!str_contains($path, 'css') && !str_contains($path, 'assets') && !str_contains($path, 'composer'))
                $lines += count(file($path));
        } else if ($value != "." && $value != ".." && !str_starts_with($value, ".")&& !str_starts_with($value, "vendor")) {
            getDirContents($path);
        }
    }
    return $lines;
}

var_dump(getDirContents(__DIR__.'\..\\'));