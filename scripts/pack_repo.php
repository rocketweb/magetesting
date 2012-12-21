<?php

/**
 *  WARNING: this script has to be changed before it is ready to use again, 
 *  due to removal of DevExtension classes, it is no longer working
 */

die ('Sorry, this script is currently under mainteance');

error_reporting(0);
ini_set('display_errors',0);
include 'init.console.php';
/**
 * script usage:
 * 
 * to create file in data/extensions/<edition>/<package>.tgz
 * php pack_repo --package=RocketWeb_Notifications --edition=CE
 * 
 * to also add to allowed extensions table (admin only)
 * 
 * php pack_repo --package=RocketWeb_Notifications --edition=CE --add_as=dev
 * 
 * or without admin restriction
 * php pack_repo --package=RocketWeb_Notifications --edition=CE --add_as=stable
 */

try {
    $opts = new Zend_Console_Getopt(
        array(
            'package|p=s'      => '(required) use format: Namespace_Module',
            'edition|e=s'       => "(required) possible values are: 'CE', 'EE' and 'PE'",
            'add_as|a-s'      => "(optional) if given, adds entry to allowed extensions table.Possible values are 'dev' and 'stable', only admins have access to dev, anyone has access to stable",
            'help'       => 'this list',
            'list'	=> 'prints list of available extensions',
        )
    );

    $opts->parse();

} catch (Zend_Console_Getopt_Exception $e) {
    exit($e->getMessage() ."\n\n". $e->getUsageMessage());
}

if(isset($opts->help)) {
    echo $opts->getUsageMessage();
    exit;
}

/**
 * Action : add
 */
if(isset($opts->package)) {
    // do something
    
    if (!isset($opts->edition)){
        die('edition not specified, aborting...');
    }
    
    if (isset($opts->add_as) && !in_array($opts->getOption('add_as'),array('dev','stable'))){
        die('add_as specified, but has unsupported value, aborting...');
    }
    
    if (isset($opts->edition) && !in_array($opts->getOption('edition'),array('CE','EE','PE'))){
        die('edition specified, but has unsupported value, aborting...');
    }
    
    //check if we actually have such package in our extensions
    $devExtensionModel = new Application_Model_DevExtension();
    $devExtension = $devExtensionModel->findByFilters(
	array(
	  'name'=> $opts->getOption('package'),
	  'edition' => $opts->getOption('edition'),
	)
    );
    if (!$devExtension){
        die('we dont have such extension listed, aborting...');
    }
          
    //now, let's begin!
    
    //stworz temp directory
    $tempPackageDir = 'temp_package_dir';
    mkdir('temp_package_dir',0777);
    
    $fileToParse = pathinfo($devExtension->getExtensionConfigFile(), PATHINFO_BASENAME);
    
    //sciagnij do niego package
    exec('svn export --force ' .
                $devExtension->getRepoUrl() . '' . $devExtension->getExtensionConfigFile() .
                ' --username ' . $devExtension->getRepoUser() .
                ' --password ' . $devExtension->getRepoPassword() .
                ' ' . $tempPackageDir . '/' . $fileToParse);
    
    //
    $doc = new DOMDocument();
    $doc->load($tempPackageDir. '/package.xml' );
    
    $version  = (string)$doc->getElementsByTagName( "version" )->item(0)->nodeValue;
    $contents  = $doc->getElementsByTagName( "contents" );

    for($i=0; $i < $contents->length; $i++ )
    {
	  //create temp folder
	  //mkdir($_POST['packageTempFolder'],null,true);

	  $targets = $contents->item($i)->getElementsByTagName("target");
	  for($targetIterator=0; $targetIterator < $targets->length; $targetIterator++ )
	  {
		  walktree($targets->item($targetIterator),0,'',$targets->item($targetIterator)->getAttribute("name"));
	  }  
    }
    
    //create archive in this folder
    exec('cd '.$tempPackageDir.' && tar -zcf ../'.$devExtension->getName().'-'.$version.'.tgz . && cd .. ');	
    
   
    if (isset($opts->add_as)){
    echo 'moving...';  
      exec('mv '.$devExtension->getName().'-'.$version.'.tgz ../data/extensions/'.$devExtension->getEdition().'/'.$devExtension->getName().'-'.$version.'.tgz');
           
	try {
	$extensionModel = new Application_Model_Extension();
	
	if ($newExt = $extensionModel->findByFilters(array('namespace_module'=>$devExtension->getName(),'edition'=>$devExtension->getEdition()))){
	
	} else {
	  $newExt = $extensionModel;
	}
      	
	$newExt->setName($devExtension->getName());
        $newExt->setVersion($version);
	$newExt->setFileName($devExtension->getName().'-'.$version.'.tgz');
	$newExt->setNamespaceModule($devExtension->getName());
	$newExt->setFromVersion($devExtension->getFromVersion());
	$newExt->setToVersion($devExtension->getToVersion());
	$newExt->setEdition($devExtension->getEdition());
           
      if ($opts->getOption('add_as')=='stable'){
	$newExt->setIsDev(0);
      } elseif ($opts->getOption('add_as')=='dev'){
	$newExt->setIsDev(1);
      }
      
      $newExt->save();
      }catch(Exception $e)
      {
      var_dump($e);
      }
      
      
    }
    rrmdir($tempPackageDir);  
    
    echo PHP_EOL;
    exit;
}

if (isset($opts->list)) {
    $devExtensionModel = new Application_Model_DevExtension();
    $devExtensions = $devExtensionModel->fetchAll();

    if (count($devExtensions) > 0) {
        echo 'Available extensions:';
        foreach ($devExtensions as $de) {
            echo PHP_EOL . ' - ' . $de->getName() . '(' . $de->getVersion() . ')';
        }

    } else {
        echo 'No available extensions' . PHP_EOL;
    }
    
    echo PHP_EOL;
    exit;
}

//bam rest should work


function walktree(DomNode $node,$level=0,$dir='',$target){

	//do things
	$dir .= do_things($node,$level,$dir,$target).'/';
	

	//go through kids
	if ($node->hasChildNodes()){
		$children = $node->childNodes;
		foreach($children as $child){
			//echo $dir;
			if ($child->nodeType == XML_ELEMENT_NODE){
				walktree($child,$level+1,$dir,$target);
			}
		}
	}


}

function do_things(DomNode $node, $nesting,$dir,$target){

//so sorry for using this
global $tempPackageDir,$devExtension;

$magePaths = array(
'magelocal' 	=> 'app/code/local',
'magecommunity' => 'app/code/community',
'magecore' 	=> 'app/code/core',
'magedesign' 	=> 'app/design',
'mageetc'	 => 'app/etc',
'magelib' 	=> 'lib',
'magelocale' 	=> 'app/locale',
'magemedia' 	=> 'media',
'mageskin' 	=> 'skin',
'mageweb' 	=> '',
'magetest' 	=> 'tests',
'mage' 		=> '',
);

if (!isset($magePaths[$target])){
die('Please enable support for '.$target.' path!!!!');
}

  if ( $node->nodeType == XML_ELEMENT_NODE ) {
      if($node->tagName == 'dir'){
	return $node->getAttribute('name');
      }elseif($node->tagName == 'file'){

	//mkdir from pathinfo
	$pathInfo = pathinfo($magePaths[$target].$dir.$node->getAttribute('name'));

	$tempdir = sys_get_temp_dir();
	mkdir($tempPackageDir.'/'.$pathInfo['dirname'],0777,true);

	//copy file there
	echo $magePaths[$target].$dir.$node->getAttribute('name').PHP_EOL;
	exec('sudo svn export ' .
                    $devExtension->getRepoUrl() . '' . $magePaths[$target] . $dir . $node->getAttribute('name') . ' ' .
                    ' ' . $tempPackageDir . '/' . $magePaths[$target] . $dir . $node->getAttribute('name') .
                    ' --username ' . $devExtension->getRepoUser() .
                    ' --password \'' . $devExtension->getRepoPassword() . '\'' .
                    ' --force');

      }
  }
}

function rrmdir($dir) {
    foreach(glob($dir . '/*') as $file) {
        if(is_dir($file))
            rrmdir($file);
        else
            unlink($file);
    }
    rmdir($dir);
}