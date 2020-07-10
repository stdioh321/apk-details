<?php
$protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === 0 ? 'https://' : 'http://';

print_r($protocol . $_SERVER['HTTP_HOST'] . '/downloads/decompile/');
