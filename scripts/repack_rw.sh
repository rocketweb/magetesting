#!/bin/bash

#
# Script prepares encoded and open sourced package
# for any Rocket Web extension package.
# 
# @author wojtek
#  
# Just provide filename as the only argument:
# ./repack_rw.sh "../data/extensions/CE/open/rocketweb_search.tar.gz"
#
# Encoded file will be placed in /data/extensions/CE/encoded/ directory.
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
    # create temp directory
    mkdir ${BASEDIR}/temp

    # unpack extension to temp directory
    tar -xzf $FILE -C ${BASEDIR}/temp

    # encode temp directory into temp-encoded
    $IONCUBE --allowed-server *.magetesting.com --obfuscate all --obfuscation-key "$KEY" --ignore .svn/ --ignore .DS_Store  --encode "*.php" --encode "*.phtml" ${BASEDIR}/temp -o ${BASEDIR}/temp-encoded

    # pack temp-encoded as our encoded extension
    tar -czf ${BASEDIR}/../encoded/${FILENAME}-encoded.tar.gz -C ${BASEDIR}/temp-encoded .

    # delete temp directories
    rm -r ${BASEDIR}/temp
    rm -r ${BASEDIR}/temp-encoded

    echo "RW Extension has been obfuscated, encoded and packed." 
fi
