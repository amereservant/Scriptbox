<?php
if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') die('Invalid request!');

if(!@include 'system/functions.php') { 
    header('HTTP/1.1 500 Internal Server Error');
    exit();
}

if(isset($_POST['searchphrase'])) {
    echo strlen($_POST['searchphrase']) > 0 ? get_formatted_search($_POST['searchphrase']) : '<h2 class="noresults">No Results</h2>';
    exit();
}

if(isset($_POST['file']) && isset($_POST['appname']) && isset($_POST['category'])) {
    $req_file = CATBASE . sanitize_path($_POST['category']) . DIRECTORY_SEPARATOR . 
        sanitize_path($_POST['appname']) . DIRECTORY_SEPARATOR . sanitize_path($_POST['file']);
    
    if(!file_exists($req_file))
    {
        header('HTTP/1.1 404 Not Found');
        exit();
    }
    echo get_file_syntax($req_file);
    exit();
}
