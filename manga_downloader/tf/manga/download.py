"""Provide functions for downloading manga from e-hentai style websites.

Classes:
Downloader -- Downloads URLs as bytes.
TextDownloader -- Downloads URLs as text.
GalleryDownloader -- Downloads entire galleries of manga.
DownloadError -- raised when a download fails.
	msg -- a reason for the raising.
	url -- the url that failed downloading.
"""

import re
import urllib.request
import urllib.error
import socket
import http
import os
import time
import random
import logging
import json


ANTI_FLOOD = 3
ANTI_OVERLOAD = 2
MAX_ERRORS = 10
TIMEOUT = 15

SITE_ROOT = '/srv/http/'

def out(stuff):
	f = open(SITE_ROOT + 'manga_downloader/status.txt', 'a')
	f.write(stuff + "\n")
	f.close()

class DownloadError(Exception):
	def __init__(self, url, msg, code):
		self.url = url
		self.msg = msg
		self.code = code

class Downloader:
	
	"""Download data through an HTTP request.
	
	Handles flooding control and common HTTP errors.
	"""
	
	def __init__(self, timeout, antiFlood, antiOverload, maxErrors):
		"""Allocate a new Downloader.
		
		Arguments:
		timeout -- the number of seconds to wait before the request times out.
		antiFlood -- the time to wait in between each request.
		antiOverload -- additional time to wait if the server is overloaded.
		maxErrors -- the maximum number of repeated non-handled errors that
		can occur before the download fails.
		"""
		self.__sameErrorCount = 0
		self.__timeout = timeout
		self.__maxErrorCount = maxErrors
		self.__antiFloodWaitTime = antiFlood
		self.__antiOverloadWaitTime = antiOverload
		self.__url = None
		self.__finalWait = False
		
	def download(self, url):
		"""Download the resource at the URL and return it."""
		self.__url = url
		data = None
		self.__resetErrorTracking()
		while data is None:
			try:
				request = urllib.request.Request(self.__url, None, {'User-Agent':'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.7) Gecko/2009021910 Firefox/3.0.7'})
				response = urllib.request.urlopen(request, None, self.__timeout)
				data = response.read()
			except socket.timeout:
				self.__resetErrorTracking()
				self.__handleTimeout()
			except urllib.error.HTTPError as e:
				self.__resetErrorTracking()
				self.__handleHTTPError(e)
			except urllib.error.URLError as e:
				self.__handleURLError(e)
			except http.client.IncompleteRead:
				self.__resetErrorTracking()
				self.__handleServerOverload()
		self.__controlFlooding()
		return data
		
	def __resetErrorTracking(self):
		"""Reset error markers and state."""
		self.__finalWait = False
		self.__sameErrorCount = 0
		
	def __controlFlooding(self):
		"""Control flooding behavior by temporarily halting execution."""
		time.sleep(self.__antiFloodWaitTime + random.randint(0,4))
		
	def __handleTimeout(self):
		"""Display timeout status to user. Do not halt execuation."""
		out("Timed out")
		out("Retrying...")
		
	def __handleHTTPError(self, e):
		"""Raise a DownloadError if the status code is 404; pass it on
		otherwise."""
		if e.code >= 400:
			raise DownloadError(self.__url, "Request returned HTTP " +
								str(e.code), e.code)
		else:
			raise
			
	def __handleURLError(self, e):
		"""Assume the error is one-time, but fail if the same error happens
		too many times."""
		self.__sameErrorCount += 1
		if self.__sameErrorCount == self.__maxErrorCount:
			if self.__finalWait:
				raise DownloadError(self.__url, "Too many of the same error.", 0)
			else:
				self.__finalWait = True
				self.__waitTwoMinutes()
		self.__handleServerError(e)
		
	def __waitTwoMinutes(self):
		"""Wait for two minutes."""
		out("Waiting 2 minutes before final attempt...")
		time.sleep(120)
		
	def __handleServerOverload(self):
		"""Wait for the server to stop being overloaded."""
		out("Server overloaded.")
		self.__waitForAntiOverload()
		
	def __handleServerError(self, e):
		"""Display the error and wait as if the server were overloaded."""
		out(str(e))
		self.__waitForAntiOverload()
		
	def __waitForAntiOverload(self):
		waitTime = self.__antiOverloadWaitTime + random.randint(0, 5)
		out("Trying again in %d seconds..." % (waitTime))
		time.sleep(waitTime)

class TextDownloader(Downloader):
	
	"""Download text data through an HTTP request."""
	
	def __init__(self, encoding, timeout, antiFlood, antiOverload, maxErrors):
		"""Allocate a new TextDownloader.
		
		Arguments:
		encoding -- the byte encoding to use for the document.
		timeout -- the number of seconds to wait before the request times out.
		antiFlood -- the time to wait in between each request.
		antiOverload -- additional time to wait if the server is overloaded.
		maxErrors -- the maximum number of repeated non-handled errors that
		can occur before the download fails.
		"""
		super().__init__(timeout, antiFlood, antiOverload, maxErrors)
		self.__encoding = encoding
		
	def download(self, url):
		"""Download the data and return the decoded text."""
		data = super().download(url)
		text = data.decode(self.__encoding)
		return text

def get_site(url):
	global SITE_DATA
	for site in SITE_DATA:
		data = SITE_DATA[site]
		if re.match(data['url_regex'], url) is not None:
			return data
	return None

class GalleryDownloader:
	
	"""Download a single gallery one page at a time."""
	
	def __init__(self, url):
		"""Allocate a new GalleryDownloader for a gallery.
		
		Arguments:
		url -- the url of the first page of the gallery.
		"""
		self.__jsonGetter = None
		self.__pageUrl = url
		self.__currentPage = 1
		self.__pageCount = None
		self.__imageUrl = None
		self.__lastDownloaded = None
		self.__pageContent = None
		site = get_site(url)
		if site is not None:
			self.__nextPageUrlRegex = site['next_page_url_regex']
			self.__imageUrlRegex = site['image_url_regex']
			self.__pageCountRegex = site['page_count_regex']
			self.__nextLinkPrefix = site['next_link_prefix']
			if site['json'] is not None:
				self.__jsonGetter = site['json']
				self.__jsonGetter.setBaseURL(url)
		else:
			raise StandardError("Invalid URL")
	
	def __iter__(self):
		"""Return self as an iterator for image data"""
		self.__downloadMetadata()
		return self
		
	def __next__(self):
		"""Download the current comic page and return the image data.
		
		Note that this will result in up to two downloads; one for the page
		and one for the image.
		
		The information for the next page download is extracted from the
		downloaded page so that subsequent calls to __next__() return the next
		image.
		"""
		if self.__lastDownloaded == self.__pageCount:
			raise StopIteration
		out("Downloading page %d of %d..." % (self.__currentPage, self.__pageCount))
		imageData = None
		errors = 0
		while True:  # try it five times at most.
			try:
				if self.__jsonGetter is not None:
					self.__imageUrl = self.__jsonGetter.getPageImageURL(self.__currentPage)
					self.__lastDownloaded = self.__currentPage
				else:
					self.__downloadPage()
					self.__extractNextPageUrl()
					self.__extractImageUrl()
				imageData = self.__downloadImage()
			except DownloadError as e:
				errors += 1
				if errors > 5:
					out("HTTP %d when accessing %s" % (e.code, e.url))
				#	out(self.__pageContent)
					raise e
				else:
					out("Download failed.")
					out("Retrying in 60 seconds...")
					time.sleep(60)
			if imageData is not None:
				break
		self.__currentPage += 1
		return (imageData, '.' + self.__imageUrl.rsplit('.', 1)[1])
		
	def __downloadMetadata(self):
		"""Download the current comic page and extract metadata from it."""
		out("Downloading metadata...")
		if self.__jsonGetter is not None:
			self.__pageCount = self.__jsonGetter.getPageCount()
		else:
			self.__downloadPage()
			self.__extractPageCount()
		
	def __downloadPage(self):
		"""Download the page specified by the current url and cache it.
		
		The page's contents are cached. If another call is made to download
		the same page, it is ignored. The cache can still be safely accessed
		as if the download had gone through.
		"""
		if self.__lastDownloaded != self.__currentPage:
			td = TextDownloader("utf-8", TIMEOUT, ANTI_FLOOD, ANTI_OVERLOAD, MAX_ERRORS)
			text = td.download(self.__pageUrl)
			self.__pageContent = text
			self.__lastDownloaded = self.__currentPage
			
	def __downloadImage(self):
		"""Download and return the image pointed to by the state variable."""
		d = Downloader(TIMEOUT, ANTI_FLOOD, ANTI_OVERLOAD, MAX_ERRORS)
		data = d.download(self.__imageUrl)
		return data
		
	def __extractNextPageUrl(self):
		"""Extract the URL for the next page from the page data and set the
		URL to it.
		"""
		m = re.search(self.__nextPageUrlRegex, self.__pageContent)
		self.__pageUrl = self.__nextLinkPrefix + m.group(REGEX_TARGET_GROUP)
		
	def __extractImageUrl(self):
		"""Extract the URL for the current page's image from the page data and
		set the state to hold it."""
		m = re.search(self.__imageUrlRegex, self.__pageContent)
		unreplaced = m.group(REGEX_TARGET_GROUP)
		self.__imageUrl = unreplaced.replace('&amp;', '&')
		
	def __extractPageCount(self):
		"""Extract the page count from the page data and set the state to
		reflect it."""
		m = re.search(self.__pageCountRegex, self.__pageContent)
		self.__pageCount = int(m.group(REGEX_TARGET_GROUP))

class JSONGetter:

	def __init__(self):
		pass

	def getPageImageURL(self, pageNum):
		pass

	def getPageCount(self):
		pass

	def setBaseURL(self, baseURL):
		self.__baseURL = baseURL

	def getBaseURL(self):
		return self.__baseURL

class FakkuJSON(JSONGetter):

	def __init__(self):
		super().__init__()
		self.__metaCache = None

	def setBaseURL(self, baseURL):
		url = baseURL.replace('http://www.fakku.net', 'https://www.fakku.net')
		url = url.replace('https://www.fakku.net', 'https://api.fakku.net')
		while url[-1] in map(str, range(10)):
			url = url[:-1]
		url = url.replace('#page=', '')
		self.__metaCache = None
		super().setBaseURL(url)

	def getPageImageURL(self, pageNum):
		self.__getMeta()
		return self.__metaCache['pages'][str(pageNum)]['image']

	def getPageCount(self):
		self.__getMeta()
		return self.__metaCache['content']['content_pages']

	def __getMeta(self):
		if self.__metaCache is None:
			td = TextDownloader("utf-8", TIMEOUT, ANTI_FLOOD, ANTI_OVERLOAD, MAX_ERRORS)
			self.__metaCache = json.loads(td.download(self.getBaseURL()))

REGEX_TARGET_GROUP = 1

"""Patterns use groups. The actual target is in the predefined target group."""
SITE_DATA = {
  'e_hentai': {
    'url_regex': '^https?://g\.e-hentai\.org/',
    'next_page_url_regex': ' / <span>\d+</span></div><a href="([^"]+)"',
    'image_url_regex': '<img src="([^"]+)" style',
    'page_count_regex': ' / <span>(\d+)',
    'next_link_prefix': '',
    'json': None,
  },
  'the_doujin': {
    'url_regex': '^https?://thedoujin\.com/',
    'next_page_url_regex': '<a href="([^"]+)" id="image-link">',
    'image_url_regex': '<img src="([^"]+)" id="image"',
    'page_count_regex': '(\d+)">&raquo;',
    'next_link_prefix': 'http://thedoujin.com',
    'json': None,
  },
  'fakku': {
    'url_regex': '^https?://www\.fakku\.net/',
    'next_page_url_regex': None,
    'image_url_regex': None,
    'page_count_regex': None,
    'next_link_prefix': None,
    'json': FakkuJSON(),
  },
}
