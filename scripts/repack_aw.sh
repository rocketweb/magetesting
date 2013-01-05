#!/bin/bash

#
# Script prepares encoded and open sourced package
# for any Ahead Works extension package.
# 
# @author wojtek
#  
# Just provide Ahead Works ZIP filename as the only argument:
# ./repack_aw.sh "../data/extensions/CE/aw_affiliate-1.0.1.community_edition.zip"
#
# It will produce two files:
# * cleaned ready-to-paste .tar.gz in data/extensions/CE/open/ directory
# * cleaned, encoded ready-to-paste .tar.gz in data/extensions/CE/encoded/ directory

#locate ioncube binary
IONCUBE=`locate ioncube_encoder53`

# get path to the extension file
FILE=$1

# and filename without extension
FILENAME=$(basename "$FILE")
FILENAME="${FILENAME%.*}"

# packing key
KEY="lets br1ng magent0 t0 the m00n"

BASEDIR=$(dirname $1)

# directory where script is placed
DIR=$(cd "$(dirname "$0")"; pwd)

if [ -z $FILE ]; then
    # return error message if filename is not provided
    echo "You need to provide directory name containing extension to encode."
else
    # unpack extension to temp directory
    unzip -q -d ${BASEDIR}/temp -x $FILE

    # copy files from step2 to step1
    cp -r ${BASEDIR}/temp/step2/ ${BASEDIR}/temp/step1 

    # encode step1 directory
    $IONCUBE --allowed-server magetesting.com,*.magetesting.com --obfuscate all --obfuscation-key "$KEY" --obfuscation-ex ${DIR}/ioncube.blist --ignore .svn/ --ignore .DS_Store  --encode "*.php" --encode "*.phtml" ${BASEDIR}/temp/step1 -o ${BASEDIR}/temp/step1-encoded

    # pack step1 as our open source extension
    tar -czf ${BASEDIR}/open/${FILENAME}.tar.gz -C ${BASEDIR}/temp/step1 .

    # pack step1-encoded as our encoded extension
    tar -czf ${BASEDIR}/encoded/${FILENAME}-encoded.tar.gz -C ${BASEDIR}/temp/step1-encoded .

    # delete temp directory
    rm -r ${BASEDIR}/temp

    echo "AW Extension has been cleaned, obfuscated, encoded and packed again." 
fi
