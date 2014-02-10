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
  $HeadURL: https://coppermine.svn.sourceforge.net/svnroot/coppermine/branches/cpg1.5.x/plugins/keyboard_navigation/keydown_thumbnails.js $
  $Revision: 8308 $
  $LastChangedBy: eenemeenemuu $
  $Date: 2012-01-17 16:08:27 +0100 (Di, 17 Jan 2012) $
  **************************************************/

$(document).ready(function() {
    $(document).keydown(function(e) {
        if (!e) {
            e = window.event;
        }
        if (e.which) {
            kcode = e.which;
        } else if (e.keyCode) {
            kcode = e.keyCode;
        }
        if ($("#jquery-lightbox").length != 1) {
            if(kcode == 37 && $('.navmenu img[src*=tab_left]').attr('src')) {
                window.location = $('.navmenu img[src*=tab_left]').parent().attr('href');
            }
            if(kcode == 39 && $('.navmenu img[src*=tab_right]').attr('src')) {
                window.location = $('.navmenu img[src*=tab_right]').parent().attr('href');
            }
        }
    });
});