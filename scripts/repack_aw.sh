#!/bin/bash

#
# Script prepares encoded and open sourced package
# for any Ahead Works extension package.
# 
# @author wojtek
#  
# Just provide filename as the only argument:
# ./repack_aw.sh "../data/aw_affiliate-1.0.1.community_edition.zip"
#

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

if [ -z $FILE ]; then
    # return error message if filename is not provided
    echo "You need to provide directory name containing extension to encode."
else
    # unpack extension to temp directory
    unzip -q -d ${BASEDIR}/temp -x $FILE

    # copy files from step2 to step1
    cp -r ${BASEDIR}/temp/step2/ ${BASEDIR}/temp/step1 

    # encode step1 directory
    $IONCUBE --obfuscate all --obfuscation-key "$KEY" --ignore .svn/ --ignore .DS_Store  --encode "*.php" --encode "*.phtml" ${BASEDIR}/temp/step1 -o ${BASEDIR}/temp/step1-encoded

    # pack step1 as our open source extension
    tar -czf ${BASEDIR}/${FILENAME}.tar.gz -C ${BASEDIR}/temp/step1 .

    # pack step1-encoded as our encoded extension
    tar -czf ${BASEDIR}/${FILENAME}-encoded.tar.gz -C ${BASEDIR}/temp/step1-encoded .

    # delete temp directory
    rm -r ${BASEDIR}/temp

    echo "AW Extension has been cleaned, obfuscated, encoded and packed again." 
fi
