<?php

class RocketWeb_Cli_Kit_Compression_Gzip
    extends RocketWeb_Cli_Query
{
    public function unpack($path)
    {
        return $this->append('gunzip ?', $path);
    }

    /**
     * Output will be array('filename') on success
     * @param string $path
     * @return RocketWeb_Cli_Kit_Compression_Gzip
     */
    public function getPackedFilename($path)
    {
        return $this->append(
            'gunzip -l ? | awk \'{ if($3 ~ /%$/) {print $4} }\'',
            /*
            gunzip -l index.php.gz
               compressed        uncompressed  ratio uncompressed_name
                448                 869  51.7% index.php
            |
            awk
            {
                if($3 ~ /%$/)
                {
                    print $4
                }
            }
            */
            array($path, $path.':')
        );
    }
    /**
     * Output will be array('Ok') on success
     * @param string $path
     * @return RocketWeb_Cli_Kit_Compression_Gzip
     */
    public function test($path)
    {
        return $this->append(
            'gunzip -tv ? | awk \'{ if($1 == ?) {print $2} }\'',
            // gunzip -tv test.gz
            // test.gz:     Ok
            /*
             
                awk 
                {
                    if($1 == "test.gz:")
                    {
                        print $2
                    }
                }
             */
            array($path, $path.':')
        );
    }
}