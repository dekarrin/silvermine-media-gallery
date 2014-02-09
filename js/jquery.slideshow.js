/*************************
  Coppermine Photo Gallery
  ************************
  Copyright (c) 2003-2013 Coppermine Dev Team
  v1.0 originally written by Gregory DEMAR

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 3
  as published by the Free Software Foundation.

  ********************************************
  Coppermine version: 1.5.22
  $HeadURL: svn://svn.code.sf.net/p/coppermine/code/tags/cpg1.5.22/js/jquery.slideshow.js $
  $Revision: 8530 $
**********************************************/

/**
 * This file contains dispalyimge.php specific javascript
 */


$(document).ready(function(){

        /** set variable from php  */
        var Time    =   js_vars.Time;
        var pos     =   js_vars.position;
        var album   =   js_vars.album;
        var PiCount =   js_vars.Pic_count;
        var Pid     =   js_vars.Pid;
        var cat     =   js_vars.cat;
        var run_slideshow = js_vars.run_slideshow;
        var Title   =   "";
	var ShuffleSeed = js_vars.ShuffleSeed;
	var startPos = js_vars.position;
	var ActualWidth = 0;
	var ActualHeight = 0;
	var DisplayWidth = 0;
	var DisplayHeight = 0;

        /** create a Image object */
        var i = new Image();
     
        /** implement ajax call to get pic url and title */
        function loadImage (j){
	var shuffleQuery = '';
	if (ShuffleSeed !== '-1') {
		shuffleQuery = '&shuffle=1&seed=' + ShuffleSeed + '&startpos=' + startPos;
	}
	var requestUri = "displayimage.php?ajax_show=1&pos="+j+"&album="+album+shuffleQuery;
	$.getJSON(requestUri, function(data){
                i.src   = data['url'];
                Title   = data['title'];
                Pid     = data['pid'];
		i.height = DisplayHeight = data['height'];
		i.width = DisplayWidth = data['width'];
		ActualWidth = data['actual_width'];
		ActualHeight = data['actual_height'];
              }); 
        }
        
        /**  next pic view and keeping hold the previous pitcure ID */
        var PidTemp = Pid;
        var timer   = '';
        
        /** start the slideshow */
        if(PiCount>1) {
            runSlideShow();
        }
            
        /** set time to run slideshow */
        function runSlideShow(){
         timer =    setTimeout( showNextSlide,Time);
        }
    
        function showNextSlide(){
            
             /** clear time out */
            clearTimeout(timer);
            
            /** now load a image */         
            pos = parseInt(pos) + 1;
            if (pos  == (PiCount)){ pos=0; }
            loadImage(pos);
                        
            var temp = i.src;
            
            i.onload = function() {
		if (isFullScreen()) {
			var sheight = screen.height;
			var swidth = screen.width;
			var pheight = ActualHeight;
			var pwidth = ActualWidth;
			var pratio = pwidth / pheight;
			var sratio = swidth / sheight;
			var usewidth = (pratio >= sratio); // img is longer or equal
			var scalefactor = (usewidth) ? (swidth / pwidth) : (sheight / pheight);
			var newWidth = Math.round(pwidth * scalefactor);
			var newHeight = Math.round(pheight * scalefactor);
			i.height = newHeight;
			i.width = newWidth;
			$("#showImage").attr({className: ''});
		} else {
			$("#showImage").attr({className: 'image'});
		}

                //if(i.complete){
                $("#showImage").attr({
                    src: i.src,
                    title: Title,
                    alt: "jQuery Logo",
                    style: "display: none;",
		    height: i.height,
		    width: i.width
                }).fadeIn();
                
                $("#title").html(Title);
                /** set Pid to temp */
                PidTemp = Pid; 

            
            //now set time to loaded image.
            runSlideShow();
            //}
        }       
    }

    
    /** close the slide show and will load the current show imags details*/
    $("#back-to").click(function () { 
        if (album != 'lastcom')
        {
            self.document.location = 'displayimage.php?album='+album+'&pid='+PidTemp+'#top_display_media' ;
        }
        else
        {
            self.document.location = 'displayimage.php?album='+album+'&cat='+cat+'&pid='+PidTemp+'&msg_id='+js_vars.msg_id+'&page='+js_vars.page+'#top_display_media' ;
        }
    });
	function isFullScreen() {
		return (document.fullScreen || document.mozFullScreen || document.webkitIsFullScreen);
	}

	function goFullScreen(element) {
		if (element.requestFullScreen) {
			element.requestFullScreen();
		} else if (element.mozRequestFullScreen) {
			element.mozRequestFullScreen();
		} else if (element.webkitRequestFullScreen) {
			element.webkitRequestFullScreen();
		}
	}

/*	$(document).bind('webkitfullscreenchange mozfullscreenchange fullscreenchange', function() {
		if (isFullScreen()) {
			var sheight = screen.height;
			var swidth = screen.width;
			var pheight = ActualHeight;
			var pwidth = ActualWidth;
			var pratio = pwidth / pheight;
			var sratio = swidth / sheight;
			var usewidth = (pratio >= sratio); // img is longer or equal
			var scalefactor = (usewidth) ? (swidth / pwidth) : (sheight / pheight);
			var newWidth = Math.round(pwidth * scalefactor);
			var newHeight = Math.round(pheight * scalefactor);
			$("#showImage").attr({height: newHeight, width: newWidth, className: ''});
		} else {
			$("#showImage").attr({height: DisplayHeight, width: DisplayWidth, className: 'image'});
		}
	});*/

	document.getElementById('fullscreen-ss').onclick = function() {
		goFullScreen(document.getElementById('showImage'));
		document.getElementById('showImage').webkitRequestFullScreen();
	};
});
