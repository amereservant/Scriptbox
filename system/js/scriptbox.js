/**
 * Supporting Javascript for ScriptBox
 * version 1.0
 */
(function($) {
    // Prevent Javascript errors if console isn't defined
    if(!window.console)
        window.console = { log: $.noop, group: $.noop, groupEnd: $.noop };

    // Default options
    var opts = { searchID   : '#search', // search field id
                 contentID  : '#content', // content element id
                 ajaxUrl    : 'ajax.php', // AJAX url to send requests to
                 viewSourceEl : '.viewsource', // View source anchor element class
                 codeDiv    : 'codeblock', // DIV used only by js to add slideDown/slideUp effects for source code
    };
    
    // Live Search AJAX
    $.fn.liveSearch = function() {
        var searchVal = '';
        
        $(this).bind('keyup', function() {
            searchVal = $(this).val();
            
            $.ajax({
                url     : opts.ajaxUrl,
                dataType : 'html',
                type     : 'POST',
                data     : { searchphrase : searchVal },
                success  : function(data) {
                    $(opts.contentID).html(data);
                },
                error    : function(xhr, status, error) {
                    alert(error);
                }
            });
        });
    }
    
    // Parses a URL for GET parameters and values
    // Used for AJAX requests which will re-assign the variables and values
    // Code from http://jquery-howto.blogspot.com/2009/09/get-url-parameters-values-with-jquery.html
    $.extend({
        getUrlVars: function( url ){
            var vars = [], hash;
            var hashes = url.slice(url.indexOf('?') + 1).split('&');
            for(var i = 0; i < hashes.length; i++)
            {
              hash = hashes[i].split('=');
              vars.push(hash[0]);
              vars[hash[0]] = hash[1];
            }
            return vars;
        },
        getUrlVar: function(url, name){
            return $.getUrlVars(url)[name];
        }
    });
    
    // View Source Functionality
    $.fn.viewSource = function() {
        $(this).bind('click', function(e){
            e.preventDefault();
            var element = $(this).parent();
            
            // If element already exists, just toggle it
            if(element.children('.'+opts.codeDiv).size() > 0) {
                var el = element.children('.'+opts.codeDiv);
                if(el.is(':hidden')) 
                    el.slideDown('slow');
                else
                    el.slideUp('slow');
            }
            // If it doesn't already exist, let's add it, then have it slideDown into view.
            else
            { 
                var vars = $.getUrlVars($(this).attr('href'));
                $.ajax({
                    url      : opts.ajaxUrl,
                    dataType : 'html',
                    async    : false,
                    type     : 'POST',
                    data     : { file     : vars.file,
                                 appname  : vars.appname,
                                 category : vars.category },
                    success  : function(data) {
                        element.append('<div class="'+ opts.codeDiv +'" style="display:none">'+data+'</div>');
                        element.children('.'+opts.codeDiv).slideDown();
                    },
                    error    : function(xhr, status, error) {
                        alert(error);
                    }
                });
            }
        });
    }
    
    // Initialize functionality on document ready
    $(document).ready(function(){
        $(opts.searchID).liveSearch();
        $(opts.viewSourceEl).viewSource();
    });
})(jQuery);
