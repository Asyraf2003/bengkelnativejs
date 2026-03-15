<?php

declare(strict_types=1);

$directory = new RecursiveDirectoryIterator(__DIR__ . '/../app');
$iterator = new RecursiveIteratorIterator($directory);
$limit = 100;
$bypassToken = '@audit-skip: line-limit';
$errors = [];

foreach ($iterator as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') {
        continue;
    }

    $filePath = (string) $file->getRealPath();
    $lines = file($filePath);
    $lineCount = count($lines);

    if ($lineCount > $limit) {
        $content = implode('', array_slice($lines, 0, 15)); // Cek 15 baris pertama
        if (!str_contains($content, $bypassToken)) {
            // Dapatkan path relatif terhadap root project
            $relativePath = str_replace(realpath(__DIR__ . '/../') . '/', '', $filePath);
            $errors[] = sprintf("[%d lines] %s", $lineCount, $relativePath);
        }
    }
}

if (count($errors) > 0) {
    echo "\e[31mERROR: File berikut melebihi limit $limit baris tanpa label bypass:\e[0m\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
    exit(1);
}

echo "\e[32mSUCCESS: Semua file memenuhi standar limit baris (atau memiliki label bypass).\e[0m\n";
exit(0);
