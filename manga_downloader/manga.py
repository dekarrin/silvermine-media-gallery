#!/usr/bin/python

import sys
import os
import logging

import tf.manga.download
import tf.manga.archive

SITE_ROOT = '/srv/http/'

CONFIG_FILE = SITE_ROOT + "manga_downloader/manga.cfg" # location, relative to this file, of the config file

def out(stuff):
	f = open(SITE_ROOT + 'manga_downloader/status.txt', 'a')
	f.write(str(stuff) + "\n")
	f.close()

def main():
	outputDir = None
	if len(sys.argv) < 2:
		out("No output dir given; using '.'")
		outputDir = '.'
	else:
		outputDir = sys.argv[1]
	mangaList = readConfig()
	mangaCount = len(mangaList)
	s = 's' if mangaCount != 1 else ''
	out("Found %d comic%s in manga.cfg" % (mangaCount, s))
	createDir(outputDir)
	downloadAllManga(mangaList, outputDir)
	out("complete")

def cleanUp(outputDir, title):
	out("Cleaning up...")
	mangaDir = outputDir.rstrip('/') + '/' + title + '/'
	try:
		os.rmdir(mangaDir)
	except WindowsError:
		out("Cannot clean download folder")
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
	mangaList = list()
	f = None
	try:
		f = open(CONFIG_FILE, 'r')
	except IOError:
		out("Cannot open manga.cfg")
		sys.exit(1)
	for line in f:
		manga = dict()
		l = line.rstrip()
		parts = l.split(' ')
		manga['title'] = parts[0]
		manga['url'] = parts[1]
		mangaList.append(manga)
	return mangaList
import traceback
def downloadAllManga(galleryList, outputDir):
	for g in galleryList:
		out("Downloading %s" % (g['title']))
		try:
			downloadGallery(g, outputDir)
		except tf.manga.download.DownloadError as e:
			tr = traceback.format_exc()
			out("Cannot download comic: " + str(e) + tr )
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

if __name__ == "__main__":
	try:
		f = open(SITE_ROOT + 'manga_downloader/status.txt', 'w')
		f.close()
		main()
	except KeyboardInterrupt:
		pass
	except Exception as e:
		out(e)

