<?php
$required_keys = array('category', 'appname', 'file', 'action');
foreach($required_keys as $k => $key) {
    if(!isset($_GET[$key]) || strlen(trim($_GET[$key])) < 1 || $_GET['action'] != 'filedownload') {
        header('HTTP/1.1 500 Internal Error');
        exit();
    }
    if($key == 'action') unset($required_keys[$k]);
}
require_once 'system/functions.php';

$file = CATBASE;
foreach($required_keys as $key)
{
    if($key == 'file') 
        $file .= sanitize_path($_GET[$key]);
    else 
        $file .= sanitize_path($_GET[$key]) . DIRECTORY_SEPARATOR;
}

if(!file_exists($file)) {
    header('HTTP/1.1 400 Not Found');
    echo '<h1>File Not Found</h1>';
    exit();
}

$mime = get_mime_type($file, true);
$name = substr($file, strrpos($file, DIRECTORY_SEPARATOR)+1);
$length = filesize($file);

// More extensive file download script at http://w-shadow.com/blog/2007/08/12/how-to-force-file-download-with-php/
// required for IE, otherwise Content-Disposition may be ignored
if(ini_get('zlib.output_compression'))
    ini_set('zlib.output_compression', 'Off');

if(headers_sent()) die('Invalid output before content!  Cannot proceed with download.');
header('Content-Type: '. $mime);
header('Content-Disposition: attachment; filename="'. $name .'"');
header('Content-Transfer-Encoding: binary');

// These three lines basically make the download non-cacheable
header("Cache-control: private");
header('Pragma: private');
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Content-Length: $length");
readfile($file);
exit();
