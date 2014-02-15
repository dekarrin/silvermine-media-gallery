import sys
import os
import logging

import tf.manga.download
import tf.manga.archive

CONFIG_FILE = "/www/manga_downloader/manga.cfg" # location, relative to this file, of the config file
OUTPUT_FILE = "/www/manga_downloader/status.txt"

class ProgressMonitor(object):

	"""Keep track of progress and write it in a place such that it can be
	read by other programs.

	Output format is as follows:
	msg_type|action|total_captures|current_capture|total_items|current_item|msg|title
	"""

	ACTION_CONFIG = 0
	ACTION_METADATA = 1
	ACTION_ITEM = 2
	ACTION_CLEANUP = 3
	ACTION_COMPLETE = 4

	TYPE_MSG = 1
	TYPE_ERR = 2

	def __init__(self, output_file):
		self.total_captures = 0
		self.current_capture = 0
		self.current_title = ""
		self.total_items = 0
		self.current_item = 0
		self.action = ProgressMonitor.ACTION_CONFIG
		self._output_file = output_file
		self._clear_output()

	def println(self, msg):
		"""Prints a line. It must not contain pipe."""
		line = self._compose_line(ProgressMonitor.TYPE_MSG, msg)
		self._output(line + "\n")

	def printerrln(self, msg):
		"""Prints a line marked as an error. It must not contain pipe."""
		line = self._compose_line(ProgressMonitor.TYPE_ERR, msg)
		self._output(line + "\n")

	def _compose_line(self, t, msg):
		prms = (t, self.action, self.total_captures, self.current_capture, self.total_items, self.current_item, msg, self.current_title)
		line = "%d|%d|%d|%d|%d|%d|%s|%s" % prms
		return line

	def _output(self, output):
		f = open(self._output_file, 'a')
		f.write(output)
		f.close()

	def _clear_output(self):
		f = open(self._output_file, 'w')
		f.close()

PROGRESS = ProgressMonitor(OUTPUT_FILE)

def main():
	global PROGRESS
	outputDir = None
	if len(sys.argv) < 2:
		PROGRESS.println("No output dir given; using '.'")
		outputDir = '.'
	else:
		outputDir = sys.argv[1]
	mangaList = readConfig()
	mangaCount = len(mangaList)
	s = 's' if mangaCount != 1 else ''
	PROGRESS.total_captures = mangaCount
	PROGRESS.println("Found %d comic%s in manga.cfg" % (mangaCount, s))
	createDir(outputDir)
	downloadAllManga(mangaList, outputDir)
	PROGRESS.action = ProgressMonitor.ACTION_COMPLETE
	PROGRESS.println("complete")

def cleanUp(outputDir, title):
	global PROGRESS
	PROGRESS.action = ProgressMonitor.ACTION_CLEANUP
	PROGRESS.println("Cleaning up...")
	mangaDir = outputDir.rstrip('/') + '/' + title + '/'
	try:
		os.rmdir(mangaDir)
	except WindowsError:
		PROGRESS.printerrln("Cannot clean download folder")
	return
	
def downloadGallery(galleryData, outputDir):
	gallery = tf.manga.download.GalleryDownloader(galleryData['url'])
	i = 1
	for data in gallery:
		image = data[0]
		ext = data[1]
		dir = outputDir.rstrip('/') + '/' + galleryData['title']
		createDir(dir)
		path = dir + '/' + str(i).zfill(3) + '.' + str(ext).lstrip('.')
		writeToDisk(image, path)
		i += 1
	return
	
def writeToDisk(data, outputPath):
	f = open(outputPath, 'wb')
	f.write(data)
	f.close()
	return
	
def createDir(dir):
	if not os.path.exists(dir):
		os.makedirs(dir)
	return
	
def readConfig():
	global PROGRESS
	mangaList = list()
	f = None
	try:
		f = open(CONFIG_FILE, 'r')
	except IOError:
		PROGRESS.printerrln("Cannot open manga.cfg")
		sys.exit(1)
	for line in f:
		manga = dict()
		l = line.rstrip()
		parts = l.split(' ')
		manga['title'] = parts[0]
		manga['url'] = parts[1]
		mangaList.append(manga)
	return mangaList

def downloadAllManga(galleryList, outputDir):
	global PROGRESS
	for g in galleryList:
		PROGRESS.action = ProgressMonitor.ACTION_METADATA
		out("Downloading %s" % (g['title']))
		try:
			downloadGallery(g, outputDir)
		except tf.manga.download.DownloadError as e:
			out("Cannot download comic")
		else:
			zipManga(outputDir, g['title'])
			cleanUp(outputDir, g['title'])
	return
	
def zipManga(outputDir, title):
	imagePath = os.path.join(outputDir, title)
	cbzPath = outputDir.rstrip('/') + '/' + title + '.cbz'
	cbz = tf.manga.archive.ComicBookZipFile(cbzPath)
	cbz.delete = True
	cbz.rename = True
	cbz.addAll(imagePath)
	cbz.close()
	
try:
	main()
except KeyboardInterrupt:
	pass
except Exception as e:
	out(e)

