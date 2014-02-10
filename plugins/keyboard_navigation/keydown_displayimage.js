/**************************************************
  Coppermine 1.5.x Plugin - keyboard_navigation
  *************************************************
  Copyright (c) 2009-2012 eenemeenemuu
  *************************************************
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 3 of the License, or
  (at your option) any later version.
  ********************************************
  $HeadURL: https://coppermine.svn.sourceforge.net/svnroot/coppermine/branches/cpg1.5.x/plugins/keyboard_navigation/keydown_displayimage.js $
  $Revision: 8492 $
  $LastChangedBy: eenemeenemuu $
  $Date: 2012-09-05 16:59:12 +0200 (Mi, 05 Sep 2012) $
  **************************************************/

$(document).ready(function() {
    jQuery.expr[':'].focus = function( elem ) {
        return elem === document.activeElement && ( elem.type || elem.href );
    };
    $(document).keydown(function(e) {
        if (!e) {
            e = window.event;
        }
        if (e.which) {
            kcode = e.which;
        } else if (e.keyCode) {
            kcode = e.keyCode;
        }
        if (!$(':input').is(':focus') && $("#jquery-lightbox").length != 1) {
            if(kcode == 37) { // 33 for pg
                window.location = $('.navmenu_pic img[src*=prev]').parent().attr('href');
            }
            if(kcode == 39) { // 34 for pg
                window.location = $('.navmenu_pic img[src*=next]').parent().attr('href');
            }
/*            if(kcode == 38) {
                window.location = $('.navmenu_pic img[src*=thumb]').parent().attr('href');
            }
            if(kcode == 40) {
                blocking('picinfo', 'yes', 'block');
                return false;
            }*/
        }
    });
});