/*************************
  Coppermine Photo Gallery
  ************************
  Copyright (c) 2003-2013 Coppermine Dev Team
  v1.0 originally written by Gregory Demar

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 3
  as published by the Free Software Foundation.

  ********************************************
  Coppermine version: 1.5.22
  $HeadURL: svn://svn.code.sf.net/p/coppermine/code/tags/cpg1.5.22/docs/js/help.js $
  $Revision: 8530 $

  $Date: 2013-01-10 16:07:14 +0000 (Thu, 10 Jan 2013) $
**********************************************/

$(document).ready(function()
{
	$('#toc').replaceWith('');
	$('#docheader').replaceWith('');
	$('#doc_footer').replaceWith('');
});

$(function() {
    $(".cpg_zebra tr:even").addClass("tableb");
	$(".cpg_zebra tr:odd").addClass("tableb_alternate");
});