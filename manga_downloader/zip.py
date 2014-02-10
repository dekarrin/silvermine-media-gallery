import sys
import os

import tf.manga.archive

def main():
	outputDir = None
	if len(sys.argv) < 2:
		print("ERROR: No output dir given!")
		sys.exit(1)
	outputDir = sys.argv[1]
	zipAll(outputDir)
	
def zipAll(outputDir):
	manga = os.listdir(outputDir)
	for m in manga:
		if m == "Thumbs.db":
			os.unlink(outputDir.strip('/') + "/Thumbs.db")
		else:
			zipManga(outputDir, m)
			cleanUp(outputDir, m)
	
def cleanUp(outputDir, title):
	mangaDir = outputDir.strip('/') + '/' + title + '/'
	try:
		os.rmdir(mangaDir)
	except WindowsError:
		print("ERROR: Cannot clean download folder")
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
