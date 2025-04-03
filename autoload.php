<?php

spl_autoload_register(function ($class) {
    $pathClass = str_replace("\\", "/", $class);
    $path =  APP_PATH . '/' . $pathClass . '.php';

    if (file_exists($path)) {
        require_once $path;
    }
});