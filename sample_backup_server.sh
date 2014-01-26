#!/bin/bash

################################################################################
# backup_server.sh                                                             #
# ================                                                             #
# Backs up the entire server and sends it to a shared folder.                  #
#                                                                              #
# DESCRIPTION                                                                  #
# -----------                                                                  #
# First, the shared folder is mounted on the system. The server application    #
# files, the user-uploaded files, and the database are then each compressed    #
# into their own archive located in a subdirectory of the shared folder. The   #
# host operating system should then be used to maintain data redundancy for    #
# the backup archives                                                          #
#                                                                              #
# ASSUMPTIONS                                                                  #
# -----------                                                                  #
# This script assumes that the server is being run in a virtual machine that   #
# provides access to folders that can be shared between the host and guest     #
# operating systems. It was originally designed to be used with ArchLinux      #
# running as a guest on Windows 8 through Oracle VM VirtualBox. This script    #
# may work on other systems as well, but such usage is untested.               #
#                                                                              #
# RETURN VALUES                                                                #
# -------------                                                                #
# A return value of 0 indicates successful completion.                         #
# A return value of 1 indicates failure caused by executing the script as a    #
# user other than root                                                         #
# A return value of 2 indicates failure caused by providing invalid options    #
# and/or arguments to the script.                                              #
################################################################################


# SCRIPT VARIABLES
# Edit these as apporirate for the current system

# Set this to 1 once you have finished setting the other variables
config_complete=

# The directory containing server files
gallery_root=

# The directory containing gallery images, relative to $gallery_root
gallery_image_dir=albums

# The home directory of server backups, relative to the root of the backup
# storage device
backup_home=

# Controls mounting of the backup storage device
mount_fs_type=
mount_device=
mount_point=

# Used in the file names of archives
datestring=$(date +%F_%T | sed s/:/-/g)

# Added to the end of the archive type (application, database, etc) to make the
# file name of an archive. Does not include extension.
archive_suffix=_bu

# Credentials to access mysqldump with
# TODO: Require ~/.my.cnf file instead of plaintext pass
db_user=
db_pass=

# END SCRIPT VARIABLES
# Editing below this line is NOT recommended unless you know what you're doing!


# COMPOSITE SCRIPT VARIABLES

backup_dir=$mount_point/$backup_home/$datestring

archive_db=$backup_dir/database$archive_suffix.gz
archive_gallery=$backup_dir/gallery$archive_suffix.tar.gz
archive_images=$backup_dir/images$archive_suffix.tar.gz

gallery_image_home=$gallery_root/$gallery_image_dir

# END COMPOSITE SCRIPT VARS

mode_none=-1
mode_all=0
mode_images=1
mode_gallery=2
mode_database=3

script_mode=$mode_all

varg=
marg=

# don't run if not yet configured
if [ -z "$config_complete" ]
then
	echo "Error: you must edit this script and set the variables!"
	exit 1
fi

# only root can mount the storage device
if [ $(whoami) != "root" ]
then
	echo "Error: script must be run as root"
	exit 1
fi

while [ -n "$1" ]
do
	case "$1" in
		-h)
			echo "-h for help"
			echo "-i for images only"
			echo "-g for gallery application files only"
			echo "-d for database backup only"
			echo "-v for verbose (may be used with -i, -g, or -d)"
			echo "-m for auto-mount of backup fs"
			script_mode=$mode_none
			;;
		-i)
			script_mode=$mode_images
			;;

		-g)
			script_mode=$mode_gallery
			;;

		-d)
			script_mode=$mode_database
			;;

		-v)
			varg=-v
			;;

		-m)
			marg=-m
			;;

		*)
			echo "unknown argument/option '$1'. Use -h for help"
			exit 2
			;;
	esac
	shift
done

if [ "$script_mode" -ne "$mode_none" ]
then
	mkdir -p $mount_point
	if [ -n "$marg" ]
	then
		mount -t $mount_fs_type $mount_device $mount_point
	fi
	mkdir -p $backup_dir
fi

if [ "$script_mode" -eq "$mode_images" -o "$script_mode" -eq "$mode_all" ]
then
	if [ -n "$varg" ]
	then
		echo "Creating images archive..."
	fi
	tar $varg -cz -C $gallery_image_home/.. \
	$(basename $gallery_image_home) | split -d -b 2048m - ${archive_images}.part
	if [ -n "$varg" ]
	then
		echo
	fi
fi

if [ "$script_mode" -eq "$mode_gallery" -o "$script_mode" -eq "$mode_all" ]
then
	if [ -n "$varg" ]
	then
		echo "Creating gallery files archive..."
	fi
	tar $varg -czf $archive_gallery -C $gallery_root/.. \
	$(basename $gallery_root) \
	--exclude="$(basename $gallery_root)/$gallery_image_dir"
	if [ -n "$varg" ]
	then
		echo
	fi
fi

if [ "$script_mode" -eq "$mode_database" -o "$script_mode" -eq "$mode_all" ]
then
	if [ -n "$varg" ]
	then
		echo "Creating database dump archive..."
	fi
	mysqldump $varg --single-transaction --flush-logs --master-data=2 \
	--all-databases -u $db_user --password=$db_pass | gzip > $archive_db

	echo 'PURGE MASTER LOGS BEFORE DATE_SUB(NOW(), INTERVAL 7 DAY);' \
	| mysql $varg -u $db_user --password=$db_pass
	if [ -n "$varg" ]
	then
		echo
	fi
fi

if [ "$script_mode" -ne "$mode_none" -a -n "$marg" ]
then
	umount $mount_point
fi

