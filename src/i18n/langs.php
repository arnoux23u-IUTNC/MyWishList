<?php

if(empty($_SESSION['lang']))
    $_SESSION['lang'] = "fr";
else if(!empty($_GET['lang']) && $_SESSION['lang'] !== $_GET['lang'])
    try {
        $_SESSION['lang'] = match ($_GET['lang']) {
            'fr' => 'fr'
        };
    }catch(UnhandledMatchError){
        $_SESSION['lang'] = "fr";
    }
require_once(__DIR__.DIRECTORY_SEPARATOR."langs".DIRECTORY_SEPARATOR.$_SESSION['lang'].".php");
