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

	$(document).keydown(function(e) {
		if (!e) {
			e = window.event;
		}
		var kcode = 0;
		if (e.which) {
			kcode = e.which;
		} else if (e.keyCode) {
			kcode = e.keyCode;
		}
		var propogate = false;
		if (PiCount > 1) {
			switch (kcode) {
				case 37: // left arrow
					pauseSlideShow();
					showPrevSlide();
					break;
				case 39: // right arrow
					showNextSlide();
					break;
				case 32: // space bar
					if (Paused) {
						runSlideShow();
						showNextSlide();
					} else {
						pauseSlideShow();
					}
					break;
				default:
					propogate = true;
					break;
			}
		}
		return propogate;
	});

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
	var ActualWidth = js_vars.Pic_width;
	var ActualHeight = js_vars.Pic_height;
	var DisplayWidth = $('#showImage').width();
	var DisplayHeight = $('#showImage').height();
	var Paused = false;
	var SwitchingSlide = false;

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
		Paused = false;
         timer =    setTimeout( showNextSlide,Time);
        }

	function pauseSlideShow() {
		Paused = true;
		clearTimeout(timer);
	}

	function showNextSlide() {
		if (!SwitchingSlide) {
			SwitchingSlide = true;
			pos = parseInt(pos) + 1;
			if (pos == (PiCount)) {
				pos = 0;
			}
			showSlide(pos);
		}
	}

	function showPrevSlide() {
		if (!SwitchingSlide) {
			SwitchingSlide = true;
			pos = parseInt(pos) - 1;
			if (pos == -1) {
				pos = PiCount - 1;
			}
			showSlide(pos);
		}
	}

	function switchImageRez() {
		if (isFullScreen()) {
			var dims = calcFsImageSize();
			$('#showImage').width(dims.width);
			$('#showImage').height(dims.height);
			$('#showImage').attr({className: ''});
		} else {
			$('#showImage').width(DisplayWidth);
			$('#showImage').height(DisplayHeight);
			$('#showImage').attr({className: 'image'});
		}
	}

	function calcFsImageSize() {
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
		return {width: newWidth, height: newHeight};
	}
    
        function showSlide(slide_pos){
            
             /** clear time out */
            clearTimeout(timer);
            
            /** now load a image */         
            loadImage(slide_pos);
                        
            var temp = i.src;
            
            i.onload = function() {
		if (isFullScreen()) {
			var dims = calcFsImageSize();
			i.height = dims.height;
			i.width = dims.width;
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
		SwitchingSlide = false;

            if (!Paused) {
            	//now set time to loaded image.
            	runSlideShow();
            	//}
	    }
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
		return (document.fullScreen || document.mozFullScreen || document.webkitIsFullScreen || document.msFullScreen);
	}

	function goFullScreen(element) {
		if (element.requestFullScreen) {
			element.requestFullScreen();
		} else if (element.mozRequestFullScreen) {
			var container = document.createElement("div");
			$(container).attr("id", "mozFsImgContainer");
			$(element).before(container);
			$(element).remove();
			$(container).append(element);
			container.mozRequestFullScreen();
		} else if (element.webkitRequestFullScreen) {
			element.webkitRequestFullScreen();
		} else if (element.msRequestFullScreen) {
			element.msRequestFullScreen();
		}
	}

	$(document).bind('webkitfullscreenchange fullscreenchange msfullscreenchange', function() {
		switchImageRez();
	});
	$(document).bind('mozfullscreenchange', function() {
		if (!isFullScreen()) {
			var img = $('#mozFsImgContainer img')[0];
			$(img).remove();
			$('#mozFsImgContainer').before(img);
			$('#mozFsImgContainer').remove();
		}
		switchImageRez();
	});

	document.getElementById('fullscreen-ss').onclick = function() {
		goFullScreen(document.getElementById('showImage'));
	};
});
