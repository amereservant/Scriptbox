<?php
/**
 * Scriptbox - Primary View File
 *
 * The index file handles all of the views and processes the requests accordingly.
 *
 * You are free to use this script as long as you abide by the terms of the
 * <b>Creative Commons Attribution-ShareAlike 3.0 License</b>, which in summary means
 * you must give credit to the author of Scriptbox in any derivative works and
 * any derivative works must have a like license.
 *
 * Visit the license URL if you are not clear what the license means and need clearer details.
 * Please enjoy and share!
 *
 * @category    Organization
 * @package     Scripbox
 * @version     1.0
 * @author      David Miles <david@amereservant.com>
 * @link        http://github.com/amereservant
 * @license     http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons Attribution-ShareAlike 3.0 Unported
 */
require_once 'system/functions.php';
$error404 = false;

if(isset($_GET['category']))
{
    // Get an application view if one is being requested
    if(!check_url(sanitize_path($_GET['category']), (isset($_GET['appname']) ? sanitize_path($_GET['appname']) : ''), true))
    {
        $error404 = get_format('error404');
    }
    // Try getting file contents request
    elseif(isset($_GET['file']) && isset($_GET['appname']))
    {
        $req_file = CATBASE . sanitize_path($_GET['category']) . DIRECTORY_SEPARATOR . 
            sanitize_path($_GET['appname']) . DIRECTORY_SEPARATOR . sanitize_path($_GET['file']);
        
        if(!file_exists($req_file))
        {
            $error404 = get_format('error404');
        }
    }
    else
    {
        $items = get_current_items( sanitize_path($_GET['category']) );
    }
}
// No category/app view requested, check if it is a search view
elseif(isset($_POST['search']) && strlen($_POST['search']) > 0)
{
    $search = get_formatted_search(trim($_POST['search']));
}
// Search via keywords
elseif(isset($_GET['keyword']) && strlen($_GET['keyword']) > 0)
{
    $search = get_formatted_search(trim($_GET['keyword']), false, array('description' => false, 'title' => false));
}
else
{
    $items = get_current_items();
}
//var_dump($items);
?>
<!DOCTYPE html>
<html>
<head>
<title>Collection</title>
<link rel="stylesheet" href="system/style.css" type="text/css" media="screen" charset="utf-8" />
<script src="system/js/jquery-1.4.4.min.js" type="text/javascript" language="javascript" charset="utf-8"></script>
<script src="system/js/scriptbox.js" type="text/javascript" language="javascript" charset="utf-8"></script>
<script type="text/javascript">
// Script from http://m5url.me/placeholder - Pseudo placeholder support
$(document).ready(function() {
	if(!$.support.placeholder) { 
		var active = document.activeElement;
		$(':text').focus(function () {
			if ($(this).attr('placeholder') != '' && $(this).val() == $(this).attr('placeholder')) {
				$(this).val('').removeClass('hasPlaceholder');
			}
		}).blur(function () {
			if ($(this).attr('placeholder') != '' && ($(this).val() == '' || $(this).val() == $(this).attr('placeholder'))) {
				$(this).val($(this).attr('placeholder')).addClass('hasPlaceholder');
			}
		});
		$(':text').blur();
		$(active).focus();
		$('form').submit(function () {
			$(this).find('.hasPlaceholder').each(function() { $(this).val(''); });
		});
	}
});
</script>
</head>
<body>
<div id="container">
    <div id="top-border"></div>
    <div id="header">
        <h1><a href="<?php echo BASEURL; ?>"><span class="upr">S</span>cripts <span class="sub">Archive</span></a></h1>
        <ul id="nav">
        <?php foreach(get_categories() as $category) { ?>
            <li>
                <a href="<?php echo BASEURL .'index.php?category='. $category; ?>" title="<?php echo $category; ?>"><?php echo strtoupper($category); ?></a>
            </li>
        <?php } ?>
        </ul>
        <form method="post" action="index.php" class="searchform">
            <fieldset>
                <input type="text" name="search" id="search" placeholder="Search" />
                <input class="submit" type="image" alt="Go" src="system/images/search-btn.png" />
            </fieldset>
        </form>
        <div class="clear"></div>
    </div><!-- #header -->
    <div id="content">
        <?php 
            if($error404)
                echo $error404;
            elseif(!isset($_GET['appname']) && !isset($_POST['search']) && !isset($_GET['keyword']))
                echo format_display_data($items, (isset($_GET['category']) ? sanitize_path($_GET['category']) : '')); 
            elseif(isset($_GET['category']))
                echo format_script_view(sanitize_path($_GET['appname']), sanitize_path($_GET['category']), 
                    (isset($_GET['file']) ? sanitize_path($_GET['file']):''));
            elseif(isset($_POST['search']) || isset($_GET['keyword']))
                echo $search;
        ?>
    </div><!-- #content -->
    <div id="footer">
        <span id="powered-by">Powered by ScriptBox</span>
        <span id="created-by" class="em">Created by <a href="http://amereservant.com" title="Amereservant">Amereservant</a></span>
    </div><!-- #footer -->
</div><!-- #container -->
</body>
</html>
