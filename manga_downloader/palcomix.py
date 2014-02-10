# format is dest path base ext count zf
# Script for dl'ing from pal
import re
import urllib2
import os
import time

def read_config_file(filename):
	f = open(filename, 'r')
	configs = list()
	for line in f:
		parts = line.split(' ')
		configs.append(parts)
	return configs
	
import sys
	
def download_comic(config):
	dest = config[0]
	path = config[1]
	base = config[2]
	ext = config[3]
	count = int(config[4])
	zf = int(config[5])
	url = 'http://palcomix.com/{0}/images/{1}{2}.{3}'
	ref = 'http://palcomix.com/{0}/imagepages/image{1}.html'
	if not os.path.exists('output/' + dest):
		os.makedirs('output/' + dest)
	for i in range(count):
		num = str(i).zfill(zf)
		trueUrl = url.format(path, base, num, ext)
		opener = urllib2.build_opener()
		opener.addheaders = [('Referer', ref.format(path, i))]
		page = opener.open(trueUrl)
		data = page.read()
		page.close()
		time.sleep(1)
		print "Page " + str(i)
		f = open('output/' + dest + '/' + base + str(num) + '.' + ext, 'wb')
		f.write(data)
		f.close()
		
def main():
	configs = read_config_file('p_manga.cfg')
	for i, c in zip(configs, range(len(configs))):
		print "Comic " + str(c) + "/" + str(len(configs))
		download_comic(i)
		
main()