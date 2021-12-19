<?php

if(empty($_SESSION['lang']))
    $_SESSION['lang'] = "fr";
else if($_SESSION['lang'] !== $_GET['lang'])
    try {
        $_SESSION['lang'] = match ($_GET['lang']) {
            'fr' => 'fr',
            'en' => 'en',
        };
    }catch(UnhandledMatchError){
        $_SESSION['lang'] = "fr";
    }
require_once("langs/".$_SESSION['lang'].".php");
