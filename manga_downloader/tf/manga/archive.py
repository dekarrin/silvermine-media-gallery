"""Convert directories of images to comic book archive format."""

import zipfile
import tarfile
import os

class ComicBookArchiveFile:
	
	"""Put pages of a comic into a single archive. Do not preserve the
	directory structure; comic archives are created with a flat filesystem.
	
	.rename specifies whether files are to be renamed to sequentially numbers
	when they are added to the archive. This defaults to False.
	
	.digitWidth specifies the length of the file name for when sequential
	renaming is used. If a number is less than this many digits long, it will
	be padded with zeros to ensure that it appears in the proper place in
	directory listings. This defaults to 3.
	
	.delete specifies whether the original files are to be deleted once they
	have been added to the archive. This defaults to False.
	
	Since derived classes implement opening, closing, and writing to their
	specific archive format, they must keep track of the archive handle
	themselves.
	"""
	
	def __init__(self, archivePath):
		"""Create a new ComicBookArchiveFile for the given file."""
		self.rename = False
		self.delete = False
		self.digitWidth = 3
		self.__imageCount = 0
		self._openArchive(archivePath)
		
	def _openArchive(self, archivePath):
		"""Open a handle to the archive at the given path.
		
		ABSTRACT. Derived classes must implement.
		"""
		raise NotImplementedError("Method is abstract.")
		
	def _writeToArchive(self, originalPath, pathInArchive):
		"""Add the file at originalPath to the path in the archive.
		
		ABSTRACT. Derived classes must implement.
		"""
		raise NotImplementedError("Method is abstract.")
		
	def _closeArchive(self):
		"""Close the archive handle.
		
		ABSTRACT. Derived classes must implement.
		"""
		raise NotImplementedError("Method is abstract.")
		
	def __renameFile(self, original):
		"""Return the file renamed as the next sequential number."""
		ext = os.path.splitext(original)[1]
		numericName = str(self.__imageCount).zfill(self.digitWidth)
		sequentialName = numericName + '.' + ext.lstrip('.')
		return sequentialName
		
	def __deleteOriginal(self, original):
		"""Delete the original file."""
		os.remove(original)
	
	def addAll(self, directory):
		"""Add all files in directory to the archive."""
		inputFiles = sorted(os.listdir(directory))
		for f in inputFiles:
			fullPath = os.path.join(directory, f)
			self.add(fullPath)
			
	def add(self, file):
		"""Add an image to the archive."""
		basename = os.path.basename(file)
		if self.rename:
			basename = self.__renameFile(basename)
		self._writeToArchive(file, basename)
		self.__imageCount += 1
		if self.delete:
			self.__deleteOriginal(file)
			
	def close(self):
		"""Close the archive handle."""
		self._closeArchive()
		

class ComicBookZipFile(ComicBookArchiveFile):
	
	"""Zip image files into a CBZ archive."""
	
	def __init__(self, cbzPath):
		"""Allocate a new ComicBookZipFile."""
		self.__archiveHandle = None
		super().__init__(cbzPath)
		
	def _openArchive(self, archivePath):
		"""Open the archive handle."""
		self.__archiveHandle = zipfile.ZipFile(archivePath, 'w')
		
	def _writeToArchive(self, originalPath, pathInArchive):
		"""Write to the archive handle."""
		self.__archiveHandle.write(originalPath, pathInArchive)
		
	def _closeArchive(self):
		"""Close the archive handle."""
		self.__archiveHandle.close()
		
		
class ComicBookTarFile(ComicBookArchiveFile):

	"""Tar image files into a CBT archive."""
	
	def __init__(self, tarPath):
		"""Allocate a new ComicBookTarFile."""
		super().__init__(tarPath)
		self.__archiveHandle = None
		
	def _openArchive(self, archivePath):
		"""Open the archive handle."""
		self.__archiveHandle = tarfile.TarFile(archivePath, 'w')
		
	def _writeToArchive(self, originalPath, pathInArchive):
		"""Write to the archive handle."""
		self.__archiveHandle.write(originalPath, pathInArchive)
		
	def _closeArchive(self):
		"""Close the archive handle."""
		self.__archiveHandle.close()
		
