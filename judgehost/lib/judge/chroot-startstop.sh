#!/bin/sh

# Generated from 'chroot-startstop.sh.in' on Sun Jun 11 09:03:49 ICT 2017.

# Script to setup chroot environment extras needed for non-statically
# compiled languages, such as Java, Python and other interpreted languages.
#
# The basic chroot environment contains nothing but a POSIX shell.
# This script will (bind) mount a minimal set of subdirectories from a
# pre-built chroot tree into the actual chroot environment that is
# created for each judging (as well as the proc FS and a few device
# files). Afterwards it is also called to cleanup.
#
# You can adapt this script to your environment, e.g. if you need to make
# more/other subdirectories available in the chroot environment.
#
# See also bin/dj_make*_chroot.sh for scripts to generate a minimal
# chroot environment with Java included. Note that if you modify paths
# in this script, then the associated sudo rules (see
# etc/sudoers-domjudge) must also be updated.
#
# This script will be called from judgedaemon.main.php in the root
# directory of the chroot environment with one parameter: either
# 'start' to setup, or 'stop' to destroy the chroot environment.
#
# We always use 'sudo -n <command> < /dev/null' to make sure that sudo
# doesn't try to ask for a password, but just fails.

# Exit on error:
set -e

# Chroot subdirs needed: (optional lib64 only needed for amd64 architecture)
SUBDIRMOUNTS="etc usr lib lib64"

# Location of the pre-built chroot tree and where to bind mount from:
CHROOTORIGINAL="/chroot/domjudge"

case "$1" in
	start)

		# Mount (bind) the proc filesystem (needed by Java for /proc/self/stat):
		mkdir -p proc
		sudo -n mount -n --bind /proc proc < /dev/null

		for i in $SUBDIRMOUNTS ; do

			# Some dirs may be links to others, e.g. /lib64 -> /lib.
			# Preserve those; bind mount the others.
			if [ -L "$CHROOTORIGINAL/$i" ]; then
				ln -s "$(readlink "$CHROOTORIGINAL/$i")" $i
			elif [ -d "$CHROOTORIGINAL/$i" ]; then
				mkdir -p $i
				sudo -n mount --bind "$CHROOTORIGINAL/$i" $i < /dev/null
				# Mount read-only for extra security. Note that this
				# must be executed separately from the bind mount.
				sudo -n mount -o remount,ro,bind "$PWD/$i" < /dev/null
			fi
		done

		# copy dev/random and /dev/urandom as a random source
		mkdir -p dev
		sudo -n cp -pR /dev/random  dev < /dev/null
		sudo -n cp -pR /dev/urandom dev < /dev/null
		;;

	stop)

		sudo -n umount "$PWD/proc" < /dev/null

		rm dev/urandom
		rm dev/random
		rmdir dev || true

		for i in $SUBDIRMOUNTS ; do
			if [ -L "$CHROOTORIGINAL/$i" ]; then
				rm -f $i
			elif [ -d "$CHROOTORIGINAL/$i" ]; then
				sudo -n umount "$PWD/$i" < /dev/null
			fi
		done
# KLUDGE: We don't rmdir the empty mountpoint directories, since after
# unmounting, we sometimes still get error messages "Device or
# resource busy" when trying to. This seems only to occur when
# multiple judgedaemons are run on a single host...
		;;

	*)
		echo "Unknown argument '$1' given."
		exit 1
esac

exit 0
