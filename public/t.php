<?php

$data = json_decode($_COOKIE['claimed_lists'], true);
$data[] = rand(2,20);
setcookie('claimed_lists', json_encode($data), time()+(3600), "/", "");

print_r($_COOKIE['claimed_lists']);