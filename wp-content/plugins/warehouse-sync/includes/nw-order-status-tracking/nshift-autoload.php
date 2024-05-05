<?php

function fqcnToPath(string $fqcn)
{
    $fqcn = str_replace("Crakter\\nShift\\", '', $fqcn);
    $fqcn = str_replace("GuzzleHttp\\", '', $fqcn);
    return str_replace('\\', '/', $fqcn) . '.php';
}

spl_autoload_register(function (string $class) {
    $path = fqcnToPath($class);

    if (file_exists(__DIR__ . '/nshift-php-client/src/' . $path)) {
        require __DIR__ . '/nshift-php-client/src/' . $path;
    }
    if (file_exists(__DIR__ . '/nshift-php-client/vendor/guzzlehttp/guzzle/src/' . $path)) {
        require __DIR__ . '/nshift-php-client/vendor/guzzlehttp/guzzle/src/' . $path;
    }
});
