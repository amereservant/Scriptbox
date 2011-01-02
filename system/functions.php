<?php
session_start();
define('CATBASE', realpath('code/') . DIRECTORY_SEPARATOR);
define('BASEURL', 'http://localhost/collection/');
define('CATURL', BASEURL .'code/');
define('SHOW_VIEW_FILE', true); // This should be set to false on a public server since
                                // viewing the file executes the file and could potentially
                                // expose secure information and create a security risk.

/**
 * Sanitize Path
 *
 * This removes any references to './' or '..'|'../' in the given path to prevent users
 * from transcending directories via GET requests.
 *
 * Since all request parameters should be relative to CATBASE, all files and directories
 * should be accessed ONLY from this point on and not below it.
 * Idealy, this shouldn't be possible if the open_basedir directive and/or file permissions
 * are properly set, but this is just an extra precaution in case it isn't.
 *
 * This should be used anytime a GET parameter is being passed to a function as part of a path parameter.
 *
 * @param   string  &$path      The path to sanitize, passed by reference
 * @return  string              Returns the corrected path incase the function is used in-line
 * @since   1.0
 */
function sanitize_path( &$path )
{
    $path = preg_replace("#([\.]{2}\/?)#", '', $path);
    return $path;
}


/**
 * Check URL
 *
 * This is used to check if a requested URL is valid by checking if the requested 
 * script and category exists as directories.
 *
 * If $send_header is set to 'true', it will output a HTTP 404 header.  This is useful when
 * using this function before any script/page output is made so the browser recieves a proper
 * 404 header and NOT a 200 OK header.
 *
 * @param   string  $category    The category being requested (EX: php, ajax, js, ...)
 * @param   string  $script      (optional) The script directory name being requested.
 *                               If this param is left empty, it will only check for the category
 * @param   bool    $send_header Whether or not to output the 404 header.
 * @return  bool                 'true' if directory does exists, 'false' if not
 * @since   1.0
 */
function check_url( $category, $script='', $send_header=false )
{
    $not_missing=true;
    
    if(strlen($script) < 1)
        $not_missing = (bool) realpath(CATBASE . $category);
     
    else
        //var_dump(realpath(CATBASE . $category . DIRECTORY_SEPARATOR . $script));
        $not_missing = (bool) realpath(CATBASE . $category . DIRECTORY_SEPARATOR . $script);
    
    if(!$not_missing) {
        if($send_header && !headers_sent()) header('HTTP/1.1 404 Not Found');
        if($send_header && headers_sent()) echo 'HEADERS ALREADY SENT!  404 headers could not be sent.';
        return false;
    }
    return true;
}


/**
 * Get Script's Info
 *
 * This tries to find the file README.txt in the script's base directory and if it doesn't
 * exist, it will return a default array of data.
 *
 * If the file does exist, then it will return the parsed contents.
 *
 * @param   string  $dir    The directory of the script to retrieve the info for
 * @return  array           An array with the script's info (if available).
 *                          <code>array('title' => '', 'keywords' => array(), 'description' => '')</code>
 * @since   1.0
 */
function get_script_info( $dir )
{
    $path = realpath($dir);
    $file = $path . DIRECTORY_SEPARATOR . 'README.txt';
    
    // Create the default array values
    // The default title is the script's directory name
    $info['title']       = ucfirst(substr($path, strrpos($path, '/')+1));
    $info['keywords']    = array();
    $info['description'] = '';
    
    // Get the category parameter from the directory name
    preg_match('#.*code/([a-zA-Z]+)/.*$#', $path, $match);
    $info['category']    = isset($match[1]) ? strtoupper($match[1]) : '';
    
    // Check if the info file exists, return default info if it doesn't
    if(!file_exists($file))
    {
        make_readme($path, $info['title'], '', '', true);
        return $info;
    }
    
    $contents = file($file);
    
    // Match title
    preg_match('#^TITLE:\s(.*)\n$#', $contents[0], $matches);
    $info['title'] = isset($matches[1]) ? $matches[1] : $info['title'];
    
    // Match Keywords
    preg_match('#^KEYWORDS:\s(.*)\n$#', $contents[1], $matches);
    $info['keywords'] = isset($matches[1]) ? explode(', ', $matches[1]) : array();
    
    // Match Description
    $matched = preg_match('#^DESCRIPTION:\s(.*)\n$#', $contents[2], $matches);
    $info['description'] = isset($matches[1]) ? $matches[1]:'';
    if($matched)
    {
        for($i=3;$i<count($contents);$i++)
        {
            if(strlen(trim($contents[$i])) < 1) break;
            $info['description'] .= $contents[$i];
        }
    }
    $info['description'] .= '&nbsp;'; // Space character for identification
    return $info;
}


// Create blank README.txt file in specified directory
/**
 * Create README.txt file
 *
 * This function is used to create a new README.txt file in the given directory.
 * If the information params are left void, default example information will be added
 * and should be replaced ASAP.
 *
 * @param   string  $dir    The directory to create the README file in.  Can be relative or absolute.
 * @param   string  $title  (optional) The script's title
 * @param   string  $keywords   (optional) Keywords to help searching for this script
 *                              Keywords should be separated by a comma: php, ajax query, forms, remote json, etc.
 * @param   string  $desc       The script's description.  HTML markup may be used and the last line should
 *                              have a blank line after it.  The description CANNOT have 
 *                              any blank lines in it or else it will leave off the rest of the description.
 * @param   bool    $suppress   This suppresses the die() function on error, used for when this
 *                              function is used in an 'auto-create' use.
 * @return  bool                'true' if file was successfully created, 'false' if not
 * @since   1.0
 */
function make_readme( $dir, $title='', $keywords='', $desc='', $suppress=false )
{
    if(!is_writeable(realpath($dir))) {
        if(!$suppress)
            die('Cannot write readme file to the directory `'. $dir .'`.'.
            '  Please assign proper permissions to the folder and try again!.');
        return false;
    }
    
    $dir = realpath($dir);
    
    $title    = strlen($title) > 0 ? $title : ucfirst(substr($dir, strrpos($dir, '/')+1));
    $keywords = strlen($keywords) > 0 ? $keywords : 'example, keywords, replace these';
    $desc     = strlen($desc) > 0 ? $desc : "Type your description here. " .  
                    "You can use <strong>HTML</strong> markup in your description!<br />\n" .
                    "Just be sure to not put a blank line between lines of it and to add a blank line at the end of the description.";

    $file = $dir . DIRECTORY_SEPARATOR . 'README.txt';
    
    if(file_exists($file)) die('README.txt already exists!  Please delete it before creating a new one.');
    
    $template = <<<EOD
TITLE: $title
KEYWORDS: $keywords
DESCRIPTION: $desc

/**
 * Instructions
 *
 * The above details MUST have the detail names all-caps with a space after the ":".
 * The info for that detail parameter MUST remain on the same line and cannot have a new line in it except for the description.
 * The 'DESCRIPTION' must be followed by a blank line.
 * That's it!
 *
 */
VERSION: 1.0
EOD;

    $results = $suppress ? @file_put_contents($file, $template) : file_put_contents($file, $template);
    @chmod($file, 0777); // Make writeable
    return $results;
}


// Get all scripts directories within the given category, if no category given, then
// it will return ALL script directories with categories as array keys

/**
 * Get Script Directories
 *
 * This gets all of the script directories for the give category and if no category is
 * specified, then it will return ALL categories with their scripts (if any).
 *
 * Therefore, this function will return two different multi-dimensional array formats
 * depending on if the $categories parameter is empty or not.
 *
 * @param   string  $category   The name of the category to retrieve the scripts from
 * @return  array               A multi-dimensional array with the script directories
 *                              that are found within the category.
 *                              See descrition for info on varying array output.
 * @since   1.0
 */
function get_script_directories( $category='' )
{
    $directories = array();
    
    // If A Category is specified ...
    if(strlen($category) > 0)
    {
        // Make sure category exists
        if(($dir = realpath(CATBASE . $category)) === false) {
            die('Could not find directory for category `'. $category .'`.');
        }
        
        // Scan the specified category's directory and collect items
        foreach(scandir($dir) as $item) 
        {
            // Filter for directories only
            if(is_dir(CATBASE . $category . DIRECTORY_SEPARATOR . $item) && $item != '.' && $item != '..') {
                $directories[] = $item;
            }
        }
    }
    else
    {
        foreach(get_categories() as $item)
        {
            $current_category = $item;
            $current_path = CATBASE . $item . DIRECTORY_SEPARATOR;
            foreach(scandir($current_path) as $bitem)
            {
                if(is_dir($current_path . $bitem) && $bitem != '.' && $bitem != '..')
                {
                    $directories[$current_category][] = $bitem;
                }
            }
        }
    }
    return $directories;
}


/**
 * Get ALL Category Names
 *
 * Simply scans the CATBASE directory and collects the directory names of all of the categories
 * found and returns them as an array.
 *
 * @param   void
 * @return  array       An array containing all found category names or empty array if none found
 * @since   1.0
 */
function get_categories()
{
    $categories = array();
    
    foreach(scandir(CATBASE) as $item)
    {
        if(is_dir(CATBASE . $item) && $item != '.' && $item != '..') 
        {
            $categories[] = $item;
        }
    }
    return $categories;
}

// Get Current Items
function get_current_items( $category='' )
{
    $dirs = get_script_directories( $category );
    $info = array();
    if(count($dirs) < 1) {
        return array();
    }
    
    // If no category is specified
    if(strlen($category) < 1)
    {
        foreach($dirs as $key => $val)
        {
            // $key will be the category directory, $dr will be the script directory
            foreach($val as $dr) 
            {
                $info[$key][$dr] = get_script_info(CATBASE . $key . DIRECTORY_SEPARATOR . $dr);
            }
        }
        //echo '<pre>'. print_r($info, true) .'</pre>';
    }
    // A category was specified
    else
    {
        foreach($dirs as $dir)
        {
            $info[$dir] = get_script_info( CATBASE . $category . DIRECTORY_SEPARATOR . $dir );
        }
        //echo '<pre>'. print_r($info, true) .'</pre>';
    }
    return $info;
}


/**
 * Get Format String
 *
 * This function is used by other functions in order to retrieve printf/sprintf formatted
 * strings for formatting the output.
 * This creates a 'centralized' place  for all of the formatting strings so they can
 * be used by multiple functions and easily modified if needed.
 *
 * @param   string  $string_name    The name of the string being retrieved
 * @return  string                  The formatted string, if matched, false on failure to match
 * @since   1.0
 */
function get_format( $string )
{
    $index_url = BASEURL .'index.php';
    
    switch( $string ) {
        case 'viewing_category': return '<p class="viewing-category">' .
                                        'You are currently viewing category: "<strong>%s</strong>"</p>';
        
        case 'search_res_title': return '<p class="viewing-category">' .
                                        'Search results for: "<strong>%s</strong>"</p>';
        
        case 'script_title'    : return '<h2 class="category-titles">'.
                                        '<a href="'. $index_url .'?appname=%s&amp;category=%s" title="%s">'.
                                        '%3$s</a></h2>';

        case 'keywords'        : return '<a href="'. $index_url .'?keyword=%s" '.
                                        'title="Search for keyword `%1$s`">%1$s</a>';

        case 'info_summary'    : return '<div class="info-summary">'."\n\t" .
                                        "<h2>%s</h2>\n\t" .
                                        '<span class="meta">KEYWORDS: <span class="meta-items">%s</span>' .                                '&nbsp;&nbsp;&nbsp;CATEGORY: <span class="meta-items">%s</span>' .
                                        "</span>\n\t<p>%s</p>\n</div>";
      
        case 'filelinks'       : return "<li><strong>%s</strong> - &nbsp;" .
                                        (SHOW_VIEW_FILE ? "<a href=\"%s\">VIEW FILE</a>&nbsp;&nbsp;" : '') .
                                        "<a href=\"".BASEURL ."index.php?%s\">VIEW SOURCE</a>&nbsp;&nbsp;" . 
                                        "<a href=\"". BASEURL ."download.php?%s\">DOWNLOAD</a>%s</li>\n";
                                        
        case 'directorylink'   : return '<li><a href="'. BASEURL .'index.php?%s" class="dirlink">' .
                                        "<strong>%s</strong></a></li>\n";
        
        case 'error404'        : return '<div class="error404"><h2>ERROR 404 <span class="sub">' .
                                        'Page doesn\'t exist!</span></h2></div>';
        // Die by default because sprintf won't issue an error if (bool) false is returned
        default: die('Format '. $string .' not found!');
    }
}


/**
 * Format General Output
 *
 * This formats the data for displaying the "general" view, the list of scripts and also
 * used to format the search results.
 * The function format_script_view() is used for formatting a single script's view.
 *
 * @param   array   $info       The array of info to format
 * @param   string  $category   The category name to format the data for
 * @return  string              The HTML output
 * @since   1.0
 */
function format_display_data( $info, $category='' )
{
    $index_url = BASEURL .'index.php';
    
    // Define templates
    $viewing_category = get_format('viewing_category');
    $script_titles    = get_format('script_title');
    $keywords_format  = get_format('keywords');
    $info_summary     = get_format('info_summary');
    
    $output = '';
    
    // If no category is specified, category keys will be present
    if(strlen($category) < 1)
    {
        // $key will be the category name
        foreach($info as $key => $scripts)
        {
            foreach($scripts as $script => $infos)
            {
                // Loop over keywords and add them
                for($i=0;$i<count($infos['keywords']);$i++) {
                    $infos['keywords'][$i] = sprintf($keywords_format, $infos['keywords'][$i]);
                }
                // Create the title
                $title = sprintf($script_titles, $script, $key, $infos['title']);
                
                // Add current information to the output
                $output .= sprintf($info_summary, $title, implode(', ', $infos['keywords']), 
                    $key, $infos['description']);
            }
        }
    }
    // Format output if a category is specified, category keys will NOT be present
    else
    {
        $output .= sprintf($viewing_category, $category);
        
        foreach($info as $script => $data)
        {
            for($i=0;$i<count($data['keywords']);$i++) {
                $data['keywords'][$i] = sprintf($keywords_format, $data['keywords'][$i]);
            }
            $title = sprintf($script_titles, $script, $category, $data['title']);
            
            $output .= sprintf($info_summary, $title, implode(', ', $data['keywords']), 
                $category, $data['description']);
        }
    }
    return $output;
}


// Format script/application view
/**
 * Format Script View (single view)
 *
 * This is used to format a single script's view, where the script's files are shown and
 * full details.
 *
 * @param   string  $script     The script name
 * @param   string  $category   The category name for the script in the first param
 * @param   string  $file       The file name if the view should include given file's source code
 * @return  string              Formatted HTML for the single view
 * @since   1.0
 */
function format_script_view( $script, $category, $file='' )
{
    $directory = CATBASE . $category . DIRECTORY_SEPARATOR . $script . DIRECTORY_SEPARATOR;
    $base      = $directory; // re-assign $directory since it may get changed later
    $index_url = BASEURL .'index.php';
    $sub       = array();
    
    // Parse sub-directory requests
    if(strpos($file, '/') !== false)
    {
        $sub = explode('/', $file);
        $file = array_pop($sub); // Filename will be the last array item, if one is given
        $directory = $directory . implode(DIRECTORY_SEPARATOR, $sub) . DIRECTORY_SEPARATOR;
    }
    
    // Check if directory actually exists
    if(!is_dir($directory)) die('Directory doesn\'t exist!');
    
    // Define output templates
    $title_templ = get_format('script_title');
    $filelink_templ = get_format('filelinks');
    $dirlink_templ  = get_format('directorylink');
    
    // Get the script's information
    $info   = get_script_info( $base );
    
    // Begin formatting the output
    $output = '<div class="single-item">'."\n";
    $output .= sprintf($title_templ, $script, $category, $info['title']);
    $output .= "<h3 class=\"ul\">Description:</h3>\n<p>{$info['description']}</p>\n";
    
    // Create directory/sub-directory links for directory navigation, but do not create
    // a link for the current viewing directory
    for($i=0;count($sub) > $i;$i++)
    {
        $nsub[] = count($sub)-1 != $i ? '<a href="'. BASEURL .'index.php?'.
            http_build_query(array('category' => $category,
                                   'appname'  => $script,
                                   'file'     => implode('/', array_slice($sub, 0, $i+1)).'/')) .
           '">'. $sub[$i] .'</a>' : $sub[$i];
    }
    
    $output .= "<h3><span class=\"ul\">Files:</span>".($sub ? ' <span class="subdir">&gt;&gt; '.implode(' &gt;&gt; ', $nsub).'</span>':'')."</h3>\n";
    
    // Collect all files for the script and add/link them to the output
    $output .= "<ul class=\"filelist\">\n";
    foreach(scandir($directory) as $item)
    {
        // Add file links
        if(!is_dir( $directory . $item ))
        {
            $category_url = CATURL .$category .'/'. $script .'/';
            $query = http_build_query(
                array('category' => $category, 
                      'appname'  => $script,
                      'file'     => implode('/', $sub) . (count($sub) > 0 ? '/':'') . $item)
            );
            
            $dl_query = http_build_query(
                array('category' => $category,
                      'appname'  => $script,
                      'file'     => implode('/', $sub) . (count($sub) > 0 ? '/':'') . $item,
                      'action'   => 'filedownload')
            );
            
            if($item == $file)
                $file_data = '<br />'.highlight_string( file_get_contents($directory . $file), true ).'<br />';
            else
                $file_data = '';
            
            $file_url = $category_url .implode('/', $sub) . (count($sub) > 0 ? '/':'') .$item;
            $output .= SHOW_VIEW_FILE ? sprintf($filelink_templ, $item, $file_url, $query, $dl_query, $file_data) :
                                        sprintf($filelink_templ, $item, $query, $dl_query, $file_data);
        }
        // Add directory links
        elseif( $item != '.' && $item != '..' )
        {
            $query = http_build_query(
                array('category' => $category,
                      'appname'  => $script,
                      'file'     => implode('/', $sub). (count($sub) == 0 ? '':'/') .$item.'/')
            );
            
            $output .= sprintf($dirlink_templ, $query, '/'. $item .'/');
        }
    }
    $output .= '</div><!-- .single-item -->'."\n";
    return $output;
}


/**
 * Detect Mime Type
 *
 * This function is used by the download script to try a few different methods of getting the 
 * file's mimetype before finally falling back to the file's extension.
 *
 * Since mimetype detection is rather unreliable at this point and inconsistent functions/packages
 * are available across different platforms, this function tries to bridge the gap.
 * The mime types by extension were from the Apache mime-types file, located in Ubuntu at
 * /etc/mime.types
 *
 * @param   string  $file       The filename with complete path
 * @param   bool    $debug      If set to 'true', this will display information about missing extensions/etc.
 * @return  string              corresponding mimetype (if detectable) or 'false' if not
 * @since   1.0
 */
function get_mime_type( $file, $debug=false )
{
    // Try Fileinfo extension first (the recommended way, default included 5.3)
    if(!extension_loaded('fileinfo')) {
        if($debug) echo 'Fileinfo extension isn\'t loaded.<br />';
        $mimetype = false;
    }
    else {
        if(($finfo = @finfo_open(FILEINFO_MIME)) === false) {
            $error = error_get_last();
            if($debug) echo $error['message'];
        }
        else {
            $mimetype = finfo_file($finfo, $file);
        }
    }
    
    if($mimetype) return $mimetype;
    
    // Try deprecated function 'mime_content_type()'
    if(!function_exists('mime_content_type')) {
        if($debug) echo 'Function mime_content_type (deprecated) doesn\'t exist.<br />';
        $mimetype = false;
    }
    else
    {
        $mimetype = @mime_content_type($file);
    }
    
    if($mimetype) return $mimetype;
    
    // Lastly, try the file extension
    $ext = substr($file, strrpos($file, '.')+1);
    
    // List of mime types obtained by parsing the /etc/mime.types file, which is used by Apache
    // Probably far more listed than ever needed, but a handy reference...
    switch($ext) {
    
        case 'ez'     : return 'application/andrew-inset';
        case 'anx'    : return 'application/annodex';
        case 'atom'   : return 'application/atom+xml';
        case 'atomcat' : return 'application/atomcat+xml';
        case 'atomsrv' : return 'application/atomserv+xml';
        case 'lin'    : return 'application/bbolin';
        case 'cap'    : return 'application/cap';
        case 'cu'     : return 'application/cu-seeme';
        case 'davmount' : return 'application/davmount+xml';
        case 'tsp'    : return 'application/dsptype';
        case 'es'     : return 'application/ecmascript';
        case 'spl'    : return 'application/futuresplash';
        case 'hta'    : return 'application/hta';
        case 'jar'    : return 'application/java-archive';
        case 'ser'    : return 'application/java-serialized-object';
        case 'class'  : return 'application/java-vm';
        case 'js'     : return 'application/javascript';
        case 'm3g'    : return 'application/m3g';
        case 'hqx'    : return 'application/mac-binhex40';
        case 'cpt'    : return 'application/mac-compactpro';
        case 'nb'     : return 'application/mathematica';
        case 'mdb'    : return 'application/msaccess';
        case 'doc'    : return 'application/msword';
        case 'mxf'    : return 'application/mxf';
        case 'bin'    : return 'application/octet-stream';
        case 'oda'    : return 'application/oda';
        case 'ogx'    : return 'application/ogg';
        case 'pdf'    : return 'application/pdf';
        case 'key'    : return 'application/pgp-keys';
        case 'pgp'    : return 'application/pgp-signature';
        case 'prf'    : return 'application/pics-rules';
        case 'ps'     : return 'application/postscript';
        case 'rar'    : return 'application/rar';
        case 'rdf'    : return 'application/rdf+xml';
        case 'rss'    : return 'application/rss+xml';
        case 'rtf'    : return 'application/rtf';
        case 'smi'    : return 'application/smil';
        case 'xhtml'  : return 'application/xhtml+xml';
        case 'xml'    : return 'application/xml';
        case 'xspf'   : return 'application/xspf+xml';
        case 'zip'    : return 'application/zip';
        case 'apk'    : return 'application/vnd.android.package-archive';
        case 'cdy'    : return 'application/vnd.cinderella';
        case 'kml'    : return 'application/vnd.google-earth.kml+xml';
        case 'kmz'    : return 'application/vnd.google-earth.kmz';
        case 'xul'    : return 'application/vnd.mozilla.xul+xml';
        case 'xls'    : return 'application/vnd.ms-excel';
        case 'cat'    : return 'application/vnd.ms-pki.seccat';
        case 'stl'    : return 'application/vnd.ms-pki.stl';
        case 'ppt'    : return 'application/vnd.ms-powerpoint';
        case 'odc'    : return 'application/vnd.oasis.opendocument.chart';
        case 'odb'    : return 'application/vnd.oasis.opendocument.database';
        case 'odf'    : return 'application/vnd.oasis.opendocument.formula';
        case 'odg'    : return 'application/vnd.oasis.opendocument.graphics';
        case 'otg'    : return 'application/vnd.oasis.opendocument.graphics-template';
        case 'odi'    : return 'application/vnd.oasis.opendocument.image';
        case 'odp'    : return 'application/vnd.oasis.opendocument.presentation';
        case 'otp'    : return 'application/vnd.oasis.opendocument.presentation-template';
        case 'ods'    : return 'application/vnd.oasis.opendocument.spreadsheet';
        case 'ots'    : return 'application/vnd.oasis.opendocument.spreadsheet-template';
        case 'odt'    : return 'application/vnd.oasis.opendocument.text';
        case 'odm'    : return 'application/vnd.oasis.opendocument.text-master';
        case 'ott'    : return 'application/vnd.oasis.opendocument.text-template';
        case 'oth'    : return 'application/vnd.oasis.opendocument.text-web';
        case 'xlsx'   : return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        case 'xltx'   : return 'application/vnd.openxmlformats-officedocument.spreadsheetml.template';
        case 'pptx'   : return 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
        case 'ppsx'   : return 'application/vnd.openxmlformats-officedocument.presentationml.slideshow';
        case 'potx'   : return 'application/vnd.openxmlformats-officedocument.presentationml.template';
        case 'docx'   : return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        case 'dotx'   : return 'application/vnd.openxmlformats-officedocument.wordprocessingml.template';
        case 'cod'    : return 'application/vnd.rim.cod';
        case 'mmf'    : return 'application/vnd.smaf';
        case 'sdc'    : return 'application/vnd.stardivision.calc';
        case 'sds'    : return 'application/vnd.stardivision.chart';
        case 'sda'    : return 'application/vnd.stardivision.draw';
        case 'sdd'    : return 'application/vnd.stardivision.impress';
        case 'sdf'    : return 'application/vnd.stardivision.math';
        case 'sdw'    : return 'application/vnd.stardivision.writer';
        case 'sgl'    : return 'application/vnd.stardivision.writer-global';
        case 'sxc'    : return 'application/vnd.sun.xml.calc';
        case 'stc'    : return 'application/vnd.sun.xml.calc.template';
        case 'sxd'    : return 'application/vnd.sun.xml.draw';
        case 'std'    : return 'application/vnd.sun.xml.draw.template';
        case 'sxi'    : return 'application/vnd.sun.xml.impress';
        case 'sti'    : return 'application/vnd.sun.xml.impress.template';
        case 'sxm'    : return 'application/vnd.sun.xml.math';
        case 'sxw'    : return 'application/vnd.sun.xml.writer';
        case 'sxg'    : return 'application/vnd.sun.xml.writer.global';
        case 'stw'    : return 'application/vnd.sun.xml.writer.template';
        case 'sis'    : return 'application/vnd.symbian.install';
        case 'vsd'    : return 'application/vnd.visio';
        case 'wbxml'  : return 'application/vnd.wap.wbxml';
        case 'wmlc'   : return 'application/vnd.wap.wmlc';
        case 'wmlsc'  : return 'application/vnd.wap.wmlscriptc';
        case 'wpd'    : return 'application/vnd.wordperfect';
        case 'wp5'    : return 'application/vnd.wordperfect5.1';
        case 'wk'     : return 'application/x-123';
        case '7z'     : return 'application/x-7z-compressed';
        case 'abw'    : return 'application/x-abiword';
        case 'dmg'    : return 'application/x-apple-diskimage';
        case 'bcpio'  : return 'application/x-bcpio';
        case 'torrent' : return 'application/x-bittorrent';
        case 'cab'    : return 'application/x-cab';
        case 'cbr'    : return 'application/x-cbr';
        case 'cbz'    : return 'application/x-cbz';
        case 'cdf'    : return 'application/x-cdf';
        case 'vcd'    : return 'application/x-cdlink';
        case 'pgn'    : return 'application/x-chess-pgn';
        case 'cpio'   : return 'application/x-cpio';
        case 'csh'    : return 'application/x-csh';
        case 'deb'    : return 'application/x-debian-package';
        case 'dcr'    : return 'application/x-director';
        case 'dms'    : return 'application/x-dms';
        case 'wad'    : return 'application/x-doom';
        case 'dvi'    : return 'application/x-dvi';
        case 'rhtml'  : return 'application/x-httpd-eruby';
        case 'pfa'    : return 'application/x-font';
        case 'mm'     : return 'application/x-freemind';
        case 'spl'    : return 'application/x-futuresplash';
        case 'gnumeric' : return 'application/x-gnumeric';
        case 'sgf'    : return 'application/x-go-sgf';
        case 'gcf'    : return 'application/x-graphing-calculator';
        case 'gtar'   : return 'application/x-gtar';
        case 'hdf'    : return 'application/x-hdf';
        case 'phtml'  : return 'application/x-httpd-php';
        case 'phps'   : return 'application/x-httpd-php-source';
        case 'php3'   : return 'application/x-httpd-php3';
        case 'php3p'  : return 'application/x-httpd-php3-preprocessed';
        case 'php4'   : return 'application/x-httpd-php4';
        case 'php5'   : return 'application/x-httpd-php5';
        case 'ica'    : return 'application/x-ica';
        case 'info'   : return 'application/x-info';
        case 'ins'    : return 'application/x-internet-signup';
        case 'iii'    : return 'application/x-iphone';
        case 'iso'    : return 'application/x-iso9660-image';
        case 'jam'    : return 'application/x-jam';
        case 'jnlp'   : return 'application/x-java-jnlp-file';
        case 'jmz'    : return 'application/x-jmol';
        case 'chrt'   : return 'application/x-kchart';
        case 'kil'    : return 'application/x-killustrator';
        case 'skp'    : return 'application/x-koan';
        case 'kpr'    : return 'application/x-kpresenter';
        case 'ksp'    : return 'application/x-kspread';
        case 'kwd'    : return 'application/x-kword';
        case 'latex'  : return 'application/x-latex';
        case 'lha'    : return 'application/x-lha';
        case 'lyx'    : return 'application/x-lyx';
        case 'lzh'    : return 'application/x-lzh';
        case 'lzx'    : return 'application/x-lzx';
        case 'frm'    : return 'application/x-maker';
        case 'mif'    : return 'application/x-mif';
        case 'wmd'    : return 'application/x-ms-wmd';
        case 'wmz'    : return 'application/x-ms-wmz';
        case 'com'    : return 'application/x-msdos-program';
        case 'msi'    : return 'application/x-msi';
        case 'nc'     : return 'application/x-netcdf';
        case 'pac'    : return 'application/x-ns-proxy-autoconfig';
        case 'nwc'    : return 'application/x-nwc';
        case 'o'      : return 'application/x-object';
        case 'oza'    : return 'application/x-oz-application';
        case 'p7r'    : return 'application/x-pkcs7-certreqresp';
        case 'crl'    : return 'application/x-pkcs7-crl';
        case 'pyc'    : return 'application/x-python-code';
        case 'qgs'    : return 'application/x-qgis';
        case 'qtl'    : return 'application/x-quicktimeplayer';
        case 'rpm'    : return 'application/x-redhat-package-manager';
        case 'rb'     : return 'application/x-ruby';
        case 'sh'     : return 'application/x-sh';
        case 'shar'   : return 'application/x-shar';
        case 'swf'    : return 'application/x-shockwave-flash';
        case 'scr'    : return 'application/x-silverlight';
        case 'sit'    : return 'application/x-stuffit';
        case 'sv4cpio' : return 'application/x-sv4cpio';
        case 'sv4crc' : return 'application/x-sv4crc';
        case 'tar'    : return 'application/x-tar';
        case 'tcl'    : return 'application/x-tcl';
        case 'gf'     : return 'application/x-tex-gf';
        case 'pk'     : return 'application/x-tex-pk';
        case 'texinfo' : return 'application/x-texinfo';
        case 't'      : return 'application/x-troff';
        case 'man'    : return 'application/x-troff-man';
        case 'me'     : return 'application/x-troff-me';
        case 'ms'     : return 'application/x-troff-ms';
        case 'ustar'  : return 'application/x-ustar';
        case 'src'    : return 'application/x-wais-source';
        case 'wz'     : return 'application/x-wingz';
        case 'crt'    : return 'application/x-x509-ca-cert';
        case 'xcf'    : return 'application/x-xcf';
        case 'fig'    : return 'application/x-xfig';
        case 'xpi'    : return 'application/x-xpinstall';
        case 'amr'    : return 'audio/amr';
        case 'awb'    : return 'audio/amr-wb';
        case 'amr'    : return 'audio/amr';
        case 'awb'    : return 'audio/amr-wb';
        case 'axa'    : return 'audio/annodex';
        case 'au'     : return 'audio/basic';
        case 'flac'   : return 'audio/flac';
        case 'mid'    : return 'audio/midi';
        case 'mpga'   : return 'audio/mpeg';
        case 'm3u'    : return 'audio/mpegurl';
        case 'oga'    : return 'audio/ogg';
        case 'sid'    : return 'audio/prs.sid';
        case 'aif'    : return 'audio/x-aiff';
        case 'gsm'    : return 'audio/x-gsm';
        case 'm3u'    : return 'audio/x-mpegurl';
        case 'wma'    : return 'audio/x-ms-wma';
        case 'wax'    : return 'audio/x-ms-wax';
        case 'ra'     : return 'audio/x-pn-realaudio';
        case 'ra'     : return 'audio/x-realaudio';
        case 'pls'    : return 'audio/x-scpls';
        case 'sd2'    : return 'audio/x-sd2';
        case 'wav'    : return 'audio/x-wav';
        case 'alc'    : return 'chemical/x-alchemy';
        case 'cac'    : return 'chemical/x-cache';
        case 'csf'    : return 'chemical/x-cache-csf';
        case 'cbin'   : return 'chemical/x-cactvs-binary';
        case 'cdx'    : return 'chemical/x-cdx';
        case 'cer'    : return 'chemical/x-cerius';
        case 'c3d'    : return 'chemical/x-chem3d';
        case 'chm'    : return 'chemical/x-chemdraw';
        case 'cif'    : return 'chemical/x-cif';
        case 'cmdf'   : return 'chemical/x-cmdf';
        case 'cml'    : return 'chemical/x-cml';
        case 'cpa'    : return 'chemical/x-compass';
        case 'bsd'    : return 'chemical/x-crossfire';
        case 'csml'   : return 'chemical/x-csml';
        case 'ctx'    : return 'chemical/x-ctx';
        case 'cxf'    : return 'chemical/x-cxf';
        case 'smi'    : return 'chemical/x-daylight-smiles';
        case 'emb'    : return 'chemical/x-embl-dl-nucleotide';
        case 'spc'    : return 'chemical/x-galactic-spc';
        case 'inp'    : return 'chemical/x-gamess-input';
        case 'fch'    : return 'chemical/x-gaussian-checkpoint';
        case 'cub'    : return 'chemical/x-gaussian-cube';
        case 'gau'    : return 'chemical/x-gaussian-input';
        case 'gal'    : return 'chemical/x-gaussian-log';
        case 'gcg'    : return 'chemical/x-gcg8-sequence';
        case 'gen'    : return 'chemical/x-genbank';
        case 'hin'    : return 'chemical/x-hin';
        case 'istr'   : return 'chemical/x-isostar';
        case 'jdx'    : return 'chemical/x-jcamp-dx';
        case 'kin'    : return 'chemical/x-kinemage';
        case 'mcm'    : return 'chemical/x-macmolecule';
        case 'mmd'    : return 'chemical/x-macromodel-input';
        case 'mol'    : return 'chemical/x-mdl-molfile';
        case 'rd'     : return 'chemical/x-mdl-rdfile';
        case 'rxn'    : return 'chemical/x-mdl-rxnfile';
        case 'sd'     : return 'chemical/x-mdl-sdfile';
        case 'tgf'    : return 'chemical/x-mdl-tgf';
        case 'mif'    : return 'chemical/x-mif';
        case 'mcif'   : return 'chemical/x-mmcif';
        case 'mol2'   : return 'chemical/x-mol2';
        case 'b'      : return 'chemical/x-molconn-Z';
        case 'gpt'    : return 'chemical/x-mopac-graph';
        case 'mop'    : return 'chemical/x-mopac-input';
        case 'moo'    : return 'chemical/x-mopac-out';
        case 'mvb'    : return 'chemical/x-mopac-vib';
        case 'asn'    : return 'chemical/x-ncbi-asn1';
        case 'prt'    : return 'chemical/x-ncbi-asn1-ascii';
        case 'val'    : return 'chemical/x-ncbi-asn1-binary';
        case 'asn'    : return 'chemical/x-ncbi-asn1-spec';
        case 'pdb'    : return 'chemical/x-pdb';
        case 'ros'    : return 'chemical/x-rosdal';
        case 'sw'     : return 'chemical/x-swissprot';
        case 'vms'    : return 'chemical/x-vamas-iso14976';
        case 'vmd'    : return 'chemical/x-vmd';
        case 'xtel'   : return 'chemical/x-xtel';
        case 'xyz'    : return 'chemical/x-xyz';
        case 'gif'    : return 'image/gif';
        case 'ief'    : return 'image/ief';
        case 'jpeg'   : return 'image/jpeg';
        case 'pcx'    : return 'image/pcx';
        case 'png'    : return 'image/png';
        case 'svg'    : return 'image/svg+xml';
        case 'tiff'   : return 'image/tiff';
        case 'djvu'   : return 'image/vnd.djvu';
        case 'wbmp'   : return 'image/vnd.wap.wbmp';
        case 'cr2'    : return 'image/x-canon-cr2';
        case 'crw'    : return 'image/x-canon-crw';
        case 'ras'    : return 'image/x-cmu-raster';
        case 'cdr'    : return 'image/x-coreldraw';
        case 'pat'    : return 'image/x-coreldrawpattern';
        case 'cdt'    : return 'image/x-coreldrawtemplate';
        case 'cpt'    : return 'image/x-corelphotopaint';
        case 'erf'    : return 'image/x-epson-erf';
        case 'ico'    : return 'image/x-icon';
        case 'art'    : return 'image/x-jg';
        case 'jng'    : return 'image/x-jng';
        case 'bmp'    : return 'image/x-ms-bmp';
        case 'nef'    : return 'image/x-nikon-nef';
        case 'orf'    : return 'image/x-olympus-orf';
        case 'psd'    : return 'image/x-photoshop';
        case 'pnm'    : return 'image/x-portable-anymap';
        case 'pbm'    : return 'image/x-portable-bitmap';
        case 'pgm'    : return 'image/x-portable-graymap';
        case 'ppm'    : return 'image/x-portable-pixmap';
        case 'rgb'    : return 'image/x-rgb';
        case 'xbm'    : return 'image/x-xbitmap';
        case 'xpm'    : return 'image/x-xpixmap';
        case 'xwd'    : return 'image/x-xwindowdump';
        case 'eml'    : return 'message/rfc822';
        case 'igs'    : return 'model/iges';
        case 'msh'    : return 'model/mesh';
        case 'wrl'    : return 'model/vrml';
        case 'x3dv'   : return 'model/x3d+vrml';
        case 'x3d'    : return 'model/x3d+xml';
        case 'x3db'   : return 'model/x3d+binary';
        case 'manifest' : return 'text/cache-manifest';
        case 'ics'    : return 'text/calendar';
        case 'css'    : return 'text/css';
        case 'csv'    : return 'text/csv';
        case '323'    : return 'text/h323';
        case 'html'   : return 'text/html';
        case 'uls'    : return 'text/iuls';
        case 'mml'    : return 'text/mathml';
        case 'asc'    : return 'text/plain';
        case 'rtx'    : return 'text/richtext';
        case 'sct'    : return 'text/scriptlet';
        case 'tm'     : return 'text/texmacs';
        case 'tsv'    : return 'text/tab-separated-values';
        case 'jad'    : return 'text/vnd.sun.j2me.app-descriptor';
        case 'wml'    : return 'text/vnd.wap.wml';
        case 'wmls'   : return 'text/vnd.wap.wmlscript';
        case 'bib'    : return 'text/x-bibtex';
        case 'boo'    : return 'text/x-boo';
        case 'h'      : return 'text/x-c++hdr';
        case 'c'      : return 'text/x-c++src';
        case 'h'      : return 'text/x-chdr';
        case 'htc'    : return 'text/x-component';
        case 'csh'    : return 'text/x-csh';
        case 'c'      : return 'text/x-csrc';
        case 'd'      : return 'text/x-dsrc';
        case 'diff'   : return 'text/x-diff';
        case 'hs'     : return 'text/x-haskell';
        case 'java'   : return 'text/x-java';
        case 'lhs'    : return 'text/x-literate-haskell';
        case 'moc'    : return 'text/x-moc';
        case 'p'      : return 'text/x-pascal';
        case 'gcd'    : return 'text/x-pcs-gcd';
        case 'pl'     : return 'text/x-perl';
        case 'py'     : return 'text/x-python';
        case 'scala'  : return 'text/x-scala';
        case 'etx'    : return 'text/x-setext';
        case 'sh'     : return 'text/x-sh';
        case 'tcl'    : return 'text/x-tcl';
        case 'tex'    : return 'text/x-tex';
        case 'vcs'    : return 'text/x-vcalendar';
        case 'vcf'    : return 'text/x-vcard';
        case '3gp'    : return 'video/3gpp';
        case 'axv'    : return 'video/annodex';
        case 'dl'     : return 'video/dl';
        case 'dif'    : return 'video/dv';
        case 'fli'    : return 'video/fli';
        case 'gl'     : return 'video/gl';
        case 'mpeg'   : return 'video/mpeg';
        case 'mp4'    : return 'video/mp4';
        case 'qt'     : return 'video/quicktime';
        case 'ogv'    : return 'video/ogg';
        case 'mxu'    : return 'video/vnd.mpegurl';
        case 'flv'    : return 'video/x-flv';
        case 'lsf'    : return 'video/x-la-asf';
        case 'mng'    : return 'video/x-mng';
        case 'asf'    : return 'video/x-ms-asf';
        case 'wm'     : return 'video/x-ms-wm';
        case 'wmv'    : return 'video/x-ms-wmv';
        case 'wmx'    : return 'video/x-ms-wmx';
        case 'wvx'    : return 'video/x-ms-wvx';
        case 'avi'    : return 'video/x-msvideo';
        case 'movie'  : return 'video/x-sgi-movie';
        case 'mpv'    : return 'video/x-matroska';
        case 'ice'    : return 'conference/x-cooltalk';
        case 'sisx'   : return 'epoc/x-sisx-app';
        case 'vrm'    : return 'world/x-vrml';
        default       : return 'application/force-download';
    }
}


function preprint( $array ){ echo '<pre>'. print_r($array, true) .'</pre>'; }


/**
 * Make Script Info JSON
 *
 * This formats ALL of the data from all of the README.txt files into a JSON data array
 * and writes it to a file for caching for faster reading.
 *
 * @param   void
 * @return  array       Returns the array of data that was stored in the file so it can
 *                      be used in-line
 * @since   1.0
 */
function make_info_json()
{
    $categories = get_script_directories();
    $return = array();
    $i=0;
    foreach($categories as $category => $scripts)
    {
        foreach($scripts as $script)
        {
            $dir  = CATBASE . $category . DIRECTORY_SEPARATOR . $script;
            $info = get_script_info( $dir );
            //preprint($info);
            $return[$i]['category'] = $category;
            $return[$i]['script']   = $script;
            $return[$i]['title']    = $info['title'];
            $return[$i]['keywords'] = $info['keywords'];
            $return[$i]['description'] = $info['description'];
            $i++;           
        }
    }
     $return['count']  = count($return);
     $return['cached'] = time();
     file_put_contents(realpath('cache') .DIRECTORY_SEPARATOR. '_datacache.php' , json_encode($return));
     return $return;
}   

/**
 * Get Scipts Cache Data
 *
 * This checks the cache file and if expired, it re-creates a new one.
 * If it is still valid, it will just return the data from the current file.
 *
 * @param   void
 * @return  array       The current data for the scripts.  The data is created originally
 *                      by the make_info_json() function.
 * @since   1.0
 */
function get_cache_data()
{
    $cache_file = realpath('cache') .DIRECTORY_SEPARATOR. '_datacache.php';
    // Figure if cache has expired
    // 1 hour  = 3,600 seconds
    // 3 hours = 10,800 seconds
    // 1 day   = 86,400 seconds
    if(!file_exists($cache_file) || (time() - filemtime($cache_file)) >= 3600) {
        $data = make_info_json();
    }
    else
    {
        if(!is_readable($cache_file)) die('Cache file could not be read!');
        $data = json_decode(file_get_contents($cache_file), true);
    }
    return $data;
}


/**
 * Search For Value
 *
 * Note: If case_sensitive is set to 'true' or matches are coming back that do not appear
 * they were matched, it's very likely it matched the URL in an anchor link in the Description.
 *
 * @param   string  $phrase         The search phrase to search for
 * @param   bool    $case_sensitive Should the search be case sensitive
 * @param   array   $search_params  Defines which parameters should be included or excluded
 *                                  Valid keys are:
 *                                  <code>
                                    $array['title']       = false; // Exclude the title
                                    $array['keywords']    = false; // Exclude the keywords
                                    $array['description'] = false; // Exclude the description
                                    </code>
 * @return  array                   The data that was matched, an empty array if no matches
 * @since   1.0
 */
function search_data( $phrase, $case_sensitive=false, $search_params=array() )
{
    $search_title    = isset($search_params['title']) ? $search_params['title'] : true;
    $search_desc     = isset($search_params['description']) ? $search_params['description'] : true;
    $search_keywords = isset($search_params['keywords']) ? $search_params['keywords'] : true;
    
    $data = get_cache_data();
    $matched = array();
    $phrase = '#.*('. preg_quote($phrase, '#') .').*#' . ($case_sensitive ? '' : 'i');
    
    foreach($data as $key => $val)
    {
        if(is_array($val)) {
            foreach($val as $skey => $sval)
            {
                // Exclude non-search parameters
                if($skey == 'title' && !$search_title) continue;
                if($skey == 'description' && !$search_desc) continue;
                if($skey == 'keywords' && !$search_keywords) continue;
                if($skey == 'script') continue;
                if($skey == 'category') continue;
                
                // Only the keywords are an array here ...
                if(is_array($sval)) {
                    foreach($sval as $bval) {
                        if(preg_match($phrase, $bval))
                            // MUST use the script namme here to prevent duplicate match results since
                            // search may match more than one parameter.
                            $matched[$data[$key]['category']][$data[$key]['script']] = $data[$key];
                    }
                }
                else
                {
                    if(preg_match($phrase, $sval, $matches, PREG_OFFSET_CAPTURE)) { 
                        // This creates a substring of the matching word(s) from the string.
                        // This can be used later for highlighting the matching text and
                        // creating a summary of the matching text.
                        //preprint($matches); 
                        //var_dump(substr($sval, $matches[1][1], strlen($matches[1][0]))); 
                        //var_dump($skey);
                        $matched[$data[$key]['category']][$data[$key]['script']] = $data[$key]; 
                    }
                }
            }
        }
    }
    return $matched;
}


/**
 * Get Formatted Search Results
 *
 * This function returns the formatted search results based on the format_display_data() function.
 *
 * @param   string  $search         The search phrase to search for
 * @param   bool    $case_sensitive Should the search be case-sensitive?
 * @param   array   $search_params  An array specifying which params to exclude from the search.
 *                                  Valid array keys are:<code>
                                        $array['keywords']    = false;
                                        $array['description'] = false;
                                        $array['title']       = false;
                                    </code>
 * @return  string                  The formatted results, formatted according to the
 *                                  format_display_data() function
 * @since   1.0
 */
function get_formatted_search( $search, $case_sensitive=false, $search_params=array() )
{
    $results = search_data( $search, $case_sensitive, $search_params );
    if(count($results) < 1) return '<h2>No Results</h2>';
    return sprintf(get_format('search_res_title'), $search) . format_display_data( $results );
}
