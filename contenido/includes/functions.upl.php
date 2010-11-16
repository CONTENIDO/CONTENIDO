<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Upload functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.3.3
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-12-28
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2008-11-27, Andreas Lindner, add possibility to define additional chars as allowed in file / dir names  
 *   modified 2009-03-16, Ingo van Peeren, fixed some sql-statements and a missing parameter in uplRenameDirectory()
 *   modified 2009-10-22, OliverL, fixed uplHasFiles is only one file in directory you can delete Directory
 *   modified 2009-10-29, Murat Purc, replaced deprecated functions (PHP 5.3 ready) and usage of is_dbfs()
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.upload.php");
cInclude("classes", "class.properties.php");
cInclude("classes", "class.dbfs.php");

/**
 * Function reduces long path names and creates a dynamic tooltipp which shows
 * the full path name on mouseover
 *
 * @author Timo Trautmann (4fb)
 * @param string $sDisplayPath - original filepath
 * @param int $iLimit - limit of chars which were displayed directly. If the path 
 *                      string is shorter there will be no tooltipp
 * @return string - string, which contains short path name and tooltipp if neccessary
 */
function generateDisplayFilePath ($sDisplayPath, $iLimit) {
    $sDisplayPath = (string) $sDisplayPath;
    $iLimit = (int) $iLimit;
    
    if (strlen($sDisplayPath) > $iLimit) {
        $sDisplayPathShort = capiStrTrimHard($sDisplayPath, $iLimit);
        
        $sTooltippString = '';
        $iCharcount = 0;
        
        $aPathFragments = explode('/', $sDisplayPath);
            
        foreach ($aPathFragments as $sFragment) {
            if ($sFragment != '') {
                if (strlen($sFragment) > ($iLimit-5)) {
                    $sFragment = capiStrTrimHard($sFragment, $iLimit);
                }
            
                if($iCharcount+strlen($sFragment)+1 > $iLimit) {
                    $sTooltippString .= '<br>'.$sFragment.'/';
                    $iCharcount = strlen($sFragment);
                } else {
                    $iCharcount = $iCharcount+1+strlen($sFragment);
                    $sTooltippString .= $sFragment.'/';
                }
            }
        }
        
        $sDisplayPath = '<span onmouseover="Tip(\''.$sTooltippString.'\', BALLOON, true, ABOVE, true);">'.$sDisplayPathShort.'</span>';
    }
    return $sDisplayPath;
}

function uplDirectoryListRecursive ($currentdir, $startdir=NULL, $files=array(), $depth=-1, $pathstring="") {
    $depth++;

    $unsorted_files = array();

    if (chdir ($currentdir) == false)
    {
    	return;
    }

    // remember where we started from
    if (!$startdir) {
        $startdir = $currentdir;
    }
    $d = opendir (".");

    //list the files in the dir
    while ($file = readdir ($d)) {
        if ($file != ".." && $file != ".") {
            if (is_dir ($file)) {
                $unsorted_files[] = $file;
            } else {
            }
        }
    }
    if (is_array($unsorted_files)) sort($unsorted_files);
    $sorted_files = $unsorted_files;

    if(is_array($sorted_files)) {
        foreach ($sorted_files as $file) {
            if ($file != ".." && $file != ".") {

                if ((filetype(getcwd()."/".$file) == "dir") &&
                    (opendir(getcwd()."/".$file) !== false)) { 
                    $a_file['name']  = $file;
                    $a_file['depth'] = $depth;
                    $a_file['pathstring']  = $pathstring.$file.'/';;

                    $files[] = $a_file;
                    // If $file is a directory take a look inside
                    $files = uplDirectoryListRecursive (getcwd().'/'.$file, getcwd(), $files, $depth, $a_file['pathstring']);
                } else {
                    // If $ file is not a directory then do nothing
                }
            }
        }
    }

    closedir ($d);
    chdir ($startdir);
    return $files;
}

function upldelete($path, $files) {
        global $cfgClient, $client, $con_cfg, $db, $cfg;

        $path = $cfgClient[$client]['upl']['path'].$path;

        if (!is_array($files)) {
            $tmp[] = $files;
            unset($files);
            $files = $tmp;
        }

        $ArrayCount = count($files);
        for ($i=0; $i<$ArrayCount; $i++) {
                if (is_dir($path.urldecode($files[$i]))) {
                        uplRecursiveRmDirIfEmpty($path.urldecode($files[$i]));

                        $sql = "DELETE FROM ".$cfg["tab"]["upl"]." WHERE dirname='".Contenido_Security::escapeDB($files[$i], $db)."/'";
                        $db->query($sql);
                } else {
                        if (file_exists ($cfgClient[$client]["path"]["frontend"].$con_cfg['PathFrontendTmp'].urldecode($files[$i]))) {
                                unlink($cfgClient[$client]["path"]["frontend"].$con_cfg['PathFrontendTmp'].urldecode($files[$i]));
                        }

                        $file_name = urldecode($files[$i]);
                        $sql_dirname = str_replace($cfgClient[$client]['upl']['path'], '', $path);

                        unlink($path.$file_name);

                        $sql = "SELECT idupl
                                          FROM ".$cfg["tab"]["upl"]."
                                          WHERE
                                          idclient='".Contenido_Security::toInteger($client)."'
                                          AND
                                          filename='".Contenido_Security::toInteger($file_name)."'
                                          AND
                                          dirname='".Contenido_Security::escapeDB($sql_dirname)."'";
                        $db->query($sql);
                        if ($db->next_record()) {
                                $sql = "DELETE FROM ".$cfg["tab"]["upl"]." WHERE idupl='".Contenido_Security::toInteger($db->f("idupl"))."'";
                                $db->query($sql);
                        }

                }
        }
}

function uplRecursiveRmDirIfEmpty($dir) {

    global $notification;

    if(!is_dir($dir)) {
            return 0;
    }
    $directory = @opendir($dir);
    
    if (!$directory)
    {
    	return false;	
    }
    readdir($directory);

    while(false !== ($dir_entry = readdir($directory))) {
            if($dir_entry != "." && $dir_entry != "..") {
                    if (is_dir($dir."/".$dir_entry)) {
                            uplrecursivermdir($dir."/".$dir_entry);
                    } else {
                            $notification->displayNotification("warning", "Im Verzeichnis $dir sind noch Dateien vorhanden. L&ouml;schen nicht m&ouml;glich.");
                    }
            }
    }
    closedir($directory);
    unset($directory);
    if (@rmdir($dir)) {
            return 1;
    } else {
            return 0;
    }
}

function uplHasFiles($dir)
{
	global $client, $cfgClient;

    $directory = @opendir($cfgClient[$client]["upl"]["path"].$dir);

    if (!$directory) {
    	return true;	
    }
	
    while(false !== ($dir_entry = readdir($directory))) {
            if($dir_entry != "." && $dir_entry != "..") {
            				closedir($directory);
                            return (true);
            }
    }
    closedir($directory);
    unset($directory);

    return false;
}

function uplHasSubdirs($dir)
{
	global $client, $cfgClient;

    $directory = @opendir($cfgClient[$client]["upl"]["path"].$dir);
    
    if (!$directory)
    {
    	return true;	
    }
    
    readdir($directory);

    $ret = false;

    while(false !== ($dir_entry = readdir($directory))) {
    		if ($dir_entry != "." && $dir_entry != "..")
    		{
        		if (is_dir($cfgClient[$client]["upl"]["path"].$dir.$dir_entry))
        		{
        			closedir($directory);
        			return true;
        		}
    		}
    }
    
    return ($ret);
}


/**
 * uplSyncDirectory ($path)
 * Sync database contents with directory
 *
 * @param string $path Specifies the path to scan 
 */
function uplSyncDirectory ($path)
{
	global $cfgClient, $client, $cfg, $db;

	if (is_dbfs($path))
	{
		return uplSyncDirectoryDBFS($path);	
	}
	
	$uploads = new UploadCollection;
    $properties = new PropertyCollection;
    	
	/* Read all files in a specific directory */
	$dir = $cfgClient[$client]['upl']['path'].$path;
	
	$olddir = getcwd();
	
	@chdir($dir);
	$dirhandle = @opendir($dir);
	
	/* Whoops, probably failed to open. Return to the caller, but clean up stuff first. */
	if (!$dirhandle)
	{
		$uploads->select("dirname = '$path' AND idclient = '$client'");

        while ($upload = $uploads->next())
        {
        	if (!file_exists($cfgClient[$client]["upl"]["path"].$upload->get("dirname").$upload->get("filename")))
        	{
        		$uploads->delete($upload->get("idupl"));
        	}	
        }
        
        // A click on "Upload" (root) would result in path = "" and this will result in LIKE '%' = everything
        // So, we have to exclude dbfs-files, as they "don't exist" (-> file_exists)
        $properties->select("idclient = '$client' AND itemtype='upload' AND type='file' AND itemid LIKE '".$path."%' AND itemid NOT LIKE 'dbfs%'");
        
       	while ($property = $properties->next())
       	{
       		if (!file_exists($cfgClient[$client]["upl"]["path"].$property->get("itemid")))
    		{
    			$properties->delete($property->get("idproperty"));	
    		}
       	}        
        
		chdir($olddir);
		return;
	}
	
	/* Put all the files into the $files array */
	while ($file = readdir ($dirhandle))
    {
    	if ($file != "." && $file != "..")
    	{
    		if (is_file($file))
    		{
    			$uploads->sync($path, $file);
    		}
    	}
    }
    
    $uploads->select("dirname = '$path' AND idclient = '$client'");

    while ($upload = $uploads->next())
    {
    	if (!file_exists($cfgClient[$client]["upl"]["path"].$upload->get("dirname").$upload->get("filename")))
    	{
    		$uploads->delete($upload->get("idupl"));
    	}	
    }

    // A click on "Upload" (root) would result in path = "" and this will result in LIKE '%' = everything
    // So, we have to exclude dbfs-files, as they "don't exist" (-> file_exists)
    $properties->select("idclient = '$client' AND itemtype='upload' AND type='file' AND itemid LIKE '".$path."%' AND itemid NOT LIKE 'dbfs%'");
        
   	while ($property = $properties->next())
   	{
   		if (!file_exists($cfgClient[$client]["upl"]["path"].$property->get("itemid")))
		{
			$properties->delete($property->get("idproperty"));	
		}
   	}
    
    chdir($olddir);
}

/**
 * uplSyncDirectoryDBFS ($path)
 * Sync database contents with DBFS
 *
 * @param string $path Specifies the path to scan 
 */
function uplSyncDirectoryDBFS ($path)
{
	global $cfgClient, $client, $cfg, $db;
	
	$uploads = new UploadCollection;
    $properties = new PropertyCollection;
    $dbfs = new DBFSCollection;
    
    if ($dbfs->dir_exists($path))
    {
    	$strippath = $dbfs->strip_path($path);
    	
    	$dbfs->select("dirname = '$strippath'");
    	
    	while ($file = $dbfs->next())
        {
        	if ($file->get("filename") != ".")
        	{
        		$uploads->sync($path."/", $file->get("filename"));
        	}
        }
	}

	$uploads->select("dirname = '$path/' AND idclient = '$client'");

    while ($upload = $uploads->next())
    {
    	if (!$dbfs->file_exists($upload->get("dirname").$upload->get("filename")))
    	{
    		$uploads->delete($upload->get("idupl"));
    	}	
    }
    
    $properties->select("idclient = '$client' AND itemtype='upload' AND type='file' AND itemid LIKE '".$path."%'");
    
   	while ($property = $properties->next())
   	{
   		if (!$dbfs->file_exists($property->get("itemid")))
		{
			$properties->delete($property->get("idproperty"));	
		}
   	}        
    
	return;
}


function uplmkdir($path,$name) {
	
        global $cfgClient, $client, $action;
       
        if (is_dbfs($path))
        {
        	$path = str_replace("dbfs:","", $path);
        	
        	$fullpath = $path."/".$name."/.";
        	
        	$dbfs = new DBFSCollection;
        	$dbfs->create($fullpath);
        	return;	
        }
        
        $name = uplCreateFriendlyName($name);
        $name = strtr($name, "'", ".");
        if(file_exists($cfgClient[$client]['upl']['path'].$path.$name)) {
                $action = "upl_mkdir";
                return "0702";
        } else {
                $oldumask = umask(0);
                @mkdir($cfgClient[$client]['upl']['path'].$path.$name,0775);
                umask($oldumask);
        }
}

function uplRenameDirectory ($oldpath, $newpath, $parent)
{
	global $cfgClient, $client, $cfg, $db;
	
	$db2 = new DB_Contenido;
	
	rename($cfgClient[$client]['upl']['path'].$parent.$oldpath, $cfgClient[$client]['upl']['path'].$parent.$newpath."/");
	
	/* Fetch all directory strings starting with the old path, and replace them
       with the new path */
	$sql = "SELECT dirname, idupl FROM ".$cfg["tab"]["upl"]." WHERE idclient='".Contenido_Security::toInteger($client)."' AND dirname LIKE '".Contenido_Security::escapeDB($parent, $db).Contenido_Security::escapeDB($oldpath, $db)."%'";
	$db->query($sql);

	while ($db->next_record())
	{
		$moldpath = $db->f("dirname");
		$junk = substr($moldpath, strlen($parent) + strlen($oldpath));
		
		$newpath2 = $parent . $newpath . $junk; 
 
		$idupl = $db->f("idupl");
		$sql = "UPDATE ".$cfg["tab"]["upl"]." SET dirname='".Contenido_Security::escapeDB($newpath2, $db)."' WHERE idupl = '".Contenido_Security::toInteger($idupl)."'";
		$db2->query($sql);
		
	}
	
	$sql = "SELECT itemid, idproperty FROM ".$cfg["tab"]["properties"]." WHERE itemid LIKE '".Contenido_Security::escapeDB($parent, $db).Contenido_Security::escapeDB($oldpath, $db)."%'";
	$db->query($sql);
	
	while ($db->next_record())
	{
		$moldpath = $db->f("itemid");
		$junk = substr($moldpath, strlen($parent) + strlen($oldpath));
		
		$newpath2 = $parent . $newpath . $junk; 
		$idproperty = $db->f("idproperty");
		$sql = "UPDATE ".$cfg["tab"]["properties"]." SET itemid = '$newpath2' WHERE idproperty='$idproperty'";
		$db2->query($sql);
	}
	
}


function uplRecursiveDirectoryList ($directory, &$rootitem, $level, $sParent = '', $iRenameLevel = null)
{
	$dirhandle = @opendir($directory);
	
	if (!$dirhandle)
	{
	} 
	else 
	{
        $aInvalidDirectories = array();
        
		unset($files);
		
        //list the files in the dir
        while ($file = readdir ($dirhandle))
        {
        	if ($file != "." && $file != "..")
        	{
        		if (@chdir($directory.$file."/"))
            	{
                    if (uplCreateFriendlyName($file) == $file) {
                        $files[] = $file;
                    } else {
                        if ($_GET['force_rename'] == 'true') {
                            if ($iRenameLevel == 0 || $iRenameLevel == $level) {
                                uplRenameDirectory($file, uplCreateFriendlyName($file), $sParent);
                                $iRenameLevel = $level;
                                $files[] = uplCreateFriendlyName($file);
                            } else {
                                array_push($aInvalidDirectories, $file);
                            }
                        } else {
                            array_push($aInvalidDirectories, $file);
                        }
                    }
            	}
        	}
        }
        
        if (is_array($files))
        {
        	sort($files);
        	foreach ($files as $key => $file)
        	{
                /* We aren't using is_dir anymore as that function is buggy */
            	$olddir = getcwd();
            	if ($file != "." && $file != "..")
            	{
            		if (@chdir($directory.$file."/"))
            		{
                		unset($item);
                		$item = new TreeItem($file, $directory.$file."/",true);
                		$item->custom["level"] = $level;
                		
                		if ($key == count($files)-1)
                		{
                			$item->custom["lastitem"] = true;
                		} else {
                			$item->custom["lastitem"] = false;
                		}
                		
                		$item->custom["parent"] = $directory;
                		
                		$rootitem->addItem($item);
                		$old = $rootitem;
                		$aArrayTemp = uplRecursiveDirectoryList($directory.$file."/", $item, $level + 1, $sParent.$file.'/', $iRenameLevel);
                		$aInvalidDirectories = array_merge($aInvalidDirectories, $aArrayTemp);
                        $rootitem = $old;
                		chdir($olddir);
                	}
            	}
        	}	
        }
	}
	
    @closedir ($dirhandle);
    return $aInvalidDirectories;
}


function uplRecursiveDBDirectoryList ($directory, &$rootitem, $level)
{
	$dbfs = new DBFSCollection;
	$dbfs->select("filename = '.'","dirname", "dirname ASC");
	$count = 0;
	$lastlevel = 0;
	$item["."] = &$rootitem;
		
	while ($dbitem = $dbfs->next())
	{
		$dirname = $dbitem->get("dirname");
		$level = substr_count($dirname, "/")+2;
		$file = basename($dbitem->get("dirname"));
		$parent = dirname($dbitem->get("dirname"));

		if ($dirname != "." && $file != ".")
		{
    		$item[$dirname] = new TreeItem($file, "dbfs:/".$dirname,true);
    		$item[$dirname]->custom["level"] = $level;
    		$item[$dirname]->custom["parent"] = $parent;
    		$item[$dirname]->custom["lastitem"] = true;

    		if ($prevobj[$level]->custom["level"] == $level)
    		{
    			if (is_object($prevobj[$level]))
    			{
    				$prevobj[$level]->custom["lastitem"] = false;
    			}
    		}

    		if ($lastlevel > $level)
    		{
    			unset($prevobj[$lastlevel]);
    			$lprevobj->custom["lastitem"] = true;
    		}
    		
    		$prevobj[$level] = &$item[$dirname];
    		$lprevobj = &$item[$dirname];
    		
    		$lastlevel = $level;
    
    		if (is_object($item[$parent]))
    		{
    			$item[$parent]->addItem($item[$dirname]);		
    		}
    		
    		$count++;
		}
	}
}


function uplGetThumbnail ($file, $maxsize)
{
	global $client, $cfgClient, $cfg;

	if ($maxsize == -1)
	{
		return uplGetFileIcon ($file);
	}
	
	switch (getFileExtension($file))
	{
		case "png":
		case "gif":
		case "tiff":
		case "tif":
		case "bmp":
		case "jpeg":
		case "jpg":
		case "bmp":
		case "iff":
		case "xbm":
		case "wbmp":
				$img = capiImgScale($cfgClient[$client]["upl"]["path"].$file, $maxsize, $maxsize, false, false, 50);

				if ($img !== false)
				{
					return $img;
				} else {
					$value = capiImgScale($cfg["path"]["contenido"]."images/unknown.jpg", $maxsize, $maxsize, false, false, 50);	
					if ($value !== false)
					{
						return $value;
					} else {
						return uplGetFileIcon($file);
					}
				}
				break;
		default:
				return uplGetFileIcon ($file);
				break;
		
	}
}

/**
 * Returns the icon for a file type
 *
 * @param $file		Filename to retrieve the extension for
 *
 * @return Icon for the file type
 *
 */
function uplGetFileIcon ($file)
{
	global $cfg;
	
	switch (getFileExtension($file)) {
		case "sxi":
		case "sti":
		case "pps":
		case "pot":
		case "kpr":
        case "pptx":
        case "potx":
        case "pptm":
        case "potm":
		case "ppt":	$icon = "ppt.gif";
					break;
		case "doc":
		case "dot":
		case "sxw":
		case "stw":
		case "sdw":
        case "docx":
        case "dotx":
        case "docm":
        case "dotm":
		case "kwd":	$icon = "word.gif";
					break;
		case "xls":
		case "sxc":
		case "stc":
		case "xlw":
		case "xlt":
		case "csv":
		case "ksp":
        case "xlsx":
        case "xltx":
        case "xlsm":
        case "xlsb":
        case "xltm":
		case "sdc":	$icon = "excel.gif";
					break;
		case "txt":
		case "rtf": $icon = "txt.gif";
					break;					
		case "gif": $icon = "gif.gif";
					break;
		case "png": $icon = "png.gif";
					break;
		case "jpeg":
		case "jpg": $icon = "jpg.gif";
					break;
		case "html":
		case "htm": $icon = "html.gif";
					break;
		case "lha":
		case "rar":
		case "arj":
		case "bz2":
		case "bz":
		case "gz":
		case "tar":
		case "tbz2":
		case "tbz":
		case "tgz":
		case "zip": $icon = "zip.gif";
					break;
		case "pdf": $icon = "pdf.gif";
					break;
		case "mov":
		case "avi":
		case "mpg":
		case "mpeg":
		case "wmv": $icon = "movie.gif";
					break;
        case "swf": $icon = "swf.gif";
					break;
        case "js": $icon = "js.gif";
					break;
        case "vcf": $icon = "vcf.gif";
                    break;
        case "odf": $icon = "odf.gif";
                    break;
        case "php": $icon = "php.gif";
                    break;
        case "mp3":
        case "wma":
        case "ogg":
        case "mp4": $icon = "sound.gif";
                    break;
        case "psd":
        case "ai":
        case "eps":
        case "cdr":
        case "qxp":
        case "ps": $icon = "design.gif";
                   break;
        case "css": $icon = "css.gif";
					
		default: 
            if (file_exists($cfg['path']['contenido_fullhtml'] . $cfg["path"]["images"]. "filetypes/".getFileExtension($file).".gif")) {
                $icon = getFileExtension($file).".gif";
            } else {
                $icon = "unknown.gif";
            }
            break;
    }
	
	return $cfg['path']['contenido_fullhtml'] . $cfg["path"]["images"]. "filetypes/".$icon;
}


/**
 * Returns the description for a file type
 *
 * @param $extension	Extension to use
 *
 * @return Text for the file type
 *
 */
function uplGetFileTypeDescription ($extension)
{
	global $cfg;
	
	switch ($extension)
	{
		/* Presentation files */
		case "sxi": return (i18n("OpenOffice.org Presentation"));
		case "sti": return (i18n("OpenOffice.org Presentation Template"));
		case "pps": return (i18n("Microsoft PowerPoint Screen Presentation"));
		case "pot": return (i18n("Microsoft PowerPoint Presentation Template"));
		case "kpr": return (i18n("KDE KPresenter Document"));
		case "ppt":	return (i18n("Microsoft PowerPoint Presentation Template"));

		/* Document files */
		case "doc": return (i18n("Microsoft Word Document or regular text file"));
		case "dot": return (i18n("Microsoft Word Template"));
		case "sxw": return (i18n("OpenOffice.org Text Document"));
		case "stw": return (i18n("OpenOffice.org Text Document Template"));
		case "sdw": return (i18n("StarOffice 5.0 Text Document"));
		case "kwd":	return (i18n("KDE KWord Document"));

		/* Spreadsheet files */
		case "xls": return (i18n("Microsoft Excel Worksheet"));
		case "sxc": return (i18n("OpenOffice.org Table"));
		case "stc": return (i18n("OpenOffice.org Table Template"));
		case "xlw": return (i18n("Microsoft Excel File"));
		case "xlt": return (i18n("Microsoft Excel Template"));
		case "csv": return (i18n("Comma Seperated Value File"));
		case "ksp": return (i18n("KDE KSpread Document"));
		case "sdc": return (i18n("StarOffice 5.0 Table"));

		/* Text types */
		case "txt": return (i18n("Plain Text"));
		case "rtf": return (i18n("Rich Text Format"));

		/* Images */					
		case "gif": return (i18n("GIF Image"));
		case "png": return (i18n("PNG Image"));
		case "jpeg": return (i18n("JPEG Image"));
		case "jpg": return (i18n("JPEG Image"));
		case "tif": return (i18n("TIFF Image"));
		case "psd": return (i18n("Adobe Photoshop Image"));		

		/* HTML */
		case "html": return (i18n("Hypertext Markup Language Document"));
		case "htm": return (i18n("Hypertext Markup Language Document"));
        case "css": return (i18n("Cascading Style Sheets"));

		/* Archives */
		case "lha": return (i18n("LHA Archive"));
		case "rar": return (i18n("RAR Archive"));
		case "arj": return (i18n("ARJ Archive"));
		case "bz2": return (i18n("bz2-compressed File"));
		case "bz": return (i18n("bzip-compressed File"));
		case "zip": return (i18n("ZIP Archive"));
		case "tar": return (i18n("TAR Archive"));
		case "gz": return (i18n("GZ Compressed File"));

		/* Source files */
		case "c": return (i18n("C Program Code"));
		case "c++":
		case "cc":
		case "cpp": return (i18n("C++ Program Code"));
		case "hpp":
		case "h": return (i18n("C or C++ Program Header"));
		case "php":
		case "php3":
		case "php4": return (i18n("PHP Program Code"));
		case "phps": return (i18n("PHP Source File"));
		
		case "pdf": return (i18n("Adobe Acrobat Portable Document"));
		
		/* Movies */ 
		case "mov": return (i18n("QuickTime Movie"));
		case "avi": return (i18n("avi Movie"));
		case "mpg":
		case "mpeg": return (i18n("MPEG Movie"));
		case "wmv": return (i18n("Windows Media Video"));
		
		default: return (i18n($extension."-File"));
	}
}

function uplCreateFriendlyName ($filename)
{
	global $cfg;
	
	$newfilename = "";
	
	if (!is_array($cfg['upl']['allow_additional_chars'])) {
		$filename = str_replace(" ", "_", $filename);
	} elseif (in_array(' ', $cfg['upl']['allow_additional_chars']) === FALSE) {
		$filename = str_replace(" ", "_", $filename);
	}
	
	for ($i=0;$i<strlen($filename);$i++)
	{
		$atom = substr($filename, $i,1);
		$bFound = false;
		
		if (preg_match("/[0-9a-zA-Z]/i", $atom ))
		{
			$newfilename .= $atom;
			$bFound = true;		
		}
		
		if (($atom == "-" || $atom == "_" || $atom == ".") && !$bFound)
		{
			$newfilename .= $atom;
			$bFound = true;
		}
		
		#Check for additionally allowed charcaters in $cfg['upl']['allow_additional_chars'] (must be array of chars allowed) 
		if (is_array($cfg['upl']['allow_additional_chars']) && !$bFound) {
			if (in_array($atom, $cfg['upl']['allow_additional_chars'])) {
				$newfilename .= $atom;
			}
		}
	}
	
	return $newfilename;
}

function uplSearch ($searchfor)
{
	global $client;
	
    $properties = new PropertyCollection;
    $uploads = new UploadCollection;
    
    $mysearch = urlencode($searchfor);
    
    /* Search for keywords first, ranking +5 */
    $properties->select("idclient='".Contenido_Security::toInteger($client)."' AND itemtype = 'upload' AND type='file' AND name='keywords' AND value LIKE '%".Contenido_Security::escapeDB($mysearch, $db)."%'","itemid");

    while ($item = $properties->next())
    {
    	$items[$item->get("itemid")] += (substr_count(strtolower($item->get("value")), strtolower($searchfor)) * 5);
    }

    /* Search for medianame , ranking +4 */
    $properties->select("idclient='".Contenido_Security::toInteger($client)."' AND itemtype = 'upload' AND type='file' AND name='medianame' AND value LIKE '%".Contenido_Security::escapeDB($mysearch, $db)."%'","itemid");

    while ($item = $properties->next())
    {
    	$items[$item->get("itemid")] += (substr_count(strtolower($item->get("value")), strtolower($searchfor)) * 4);
    }
    
    /* Search for media notes, ranking +3 */
    $properties->select("idclient='".Contenido_Security::toInteger($client)."' AND itemtype = 'upload' AND type='file' AND name='medianotes' AND value LIKE '%".Contenido_Security::escapeDB($mysearch, $db)."%'","itemid");

    while ($item = $properties->next())
    {
    	$items[$item->get("itemid")] += (substr_count(strtolower($item->get("value")), strtolower($searchfor)) * 3);
    }

    /* Search for description, ranking +2 */
    $uploads->select("idclient='".Contenido_Security::toInteger($client)."' AND description LIKE '%".Contenido_Security::escapeDB($mysearch, $db)."%'", "idupl");

    while ($item = $uploads->next())
    {
    	$items[$item->get("dirname").$item->get("filename")] += (substr_count(strtolower($item->get("description")), strtolower($searchfor)) * 2);
    }
    
    /* Search for file name, ranking +1 */
    $uploads->select("idclient='".Contenido_Security::toInteger($client)."' AND filename LIKE '%".Contenido_Security::escapeDB($mysearch, $db)."%'", "idupl");

    while ($item = $uploads->next())
    {
    	$items[$item->get("dirname").$item->get("filename")] += 1;
    }    
    
    return ($items);	
}

function uplGetFileExtension ($sFile)
{
	/* Fetch the dot position */
	$iDotPosition = strrpos($sFile, ".");
	
	$sExtension = substr($sFile, $iDotPosition + 1);
	if (strpos($sExtension, "/") !== false)
	{
		return false;	
	} else {
		return $sExtension;	
	}
}
?>
