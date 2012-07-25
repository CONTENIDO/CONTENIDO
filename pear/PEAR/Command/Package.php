<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Stig Bakken <ssb@php.net>                                   |
// |          Martin Jansen <mj@php.net>                                  |
// +----------------------------------------------------------------------+
//
// $Id: Package.php,v 1.1 2003/08/21 08:15:24 timo.hummel Exp $

require_once 'PEAR/Common.php';
require_once 'PEAR/Command/Common.php';

class PEAR_Command_Package extends PEAR_Command_Common
{
    // {{{ properties

    var $commands = array(
        'package' => array(
            'summary' => 'Build Package',
            'function' => 'doPackage',
            'shortcut' => 'p',
            'options' => array(
                'nocompress' => array(
                    'shortopt' => 'Z',
                    'doc' => 'Do not gzip the package file'
                    ),
                'showname' => array(
                    'shortopt' => 'n',
                    'doc' => 'Print the name of the packaged file.',
                    ),
                ),
            'doc' => '[descfile]
Creates a PEAR package from its description file (usually called
package.xml).
'
            ),
        'package-validate' => array(
            'summary' => 'Validate Package Consistency',
            'function' => 'doPackageValidate',
            'shortcut' => 'pv',
            'options' => array(),
            'doc' => '
',
            ),
        'cvsdiff' => array(
            'summary' => 'Run a "cvs diff" for all files in a package',
            'function' => 'doCvsDiff',
            'shortcut' => 'cd',
            'options' => array(
                'quiet' => array(
                    'shortopt' => 'q',
                    'doc' => 'Be quiet',
                    ),
                'reallyquiet' => array(
                    'shortopt' => 'Q',
                    'doc' => 'Be really quiet',
                    ),
                'date' => array(
                    'shortopt' => 'D',
                    'doc' => 'Diff against revision of DATE',
                    'arg' => 'DATE',
                    ),
                'release' => array(
                    'shortopt' => 'R',
                    'doc' => 'Diff against tag for package release REL',
                    'arg' => 'REL',
                    ),
                'revision' => array(
                    'shortopt' => 'r',
                    'doc' => 'Diff against revision REV',
                    'arg' => 'REV',
                    ),
                'context' => array(
                    'shortopt' => 'c',
                    'doc' => 'Generate context diff',
                    ),
                'unified' => array(
                    'shortopt' => 'u',
                    'doc' => 'Generate unified diff',
                    ),
                'ignore-case' => array(
                    'shortopt' => 'i',
                    'doc' => 'Ignore case, consider upper- and lower-case letters equivalent',
                    ),
                'ignore-whitespace' => array(
                    'shortopt' => 'b',
                    'doc' => 'Ignore changes in amount of white space',
                    ),
                'ignore-blank-lines' => array(
                    'shortopt' => 'B',
                    'doc' => 'Ignore changes that insert or delete blank lines',
                    ),
                'brief' => array(
                    'doc' => 'Report only whether the files differ, no details',
                    ),
                'dry-run' => array(
                    'shortopt' => 'n',
                    'doc' => 'Don\'t do anything, just pretend',
                    ),
                ),
            'doc' => '<package.xml>
Compares all the files in a package.  Without any options, this
command will compare the current code with the last checked-in code.
Using the -r or -R option you may compare the current code with that
of a specific release.
',
            ),
        'cvstag' => array(
            'summary' => 'Set CVS Release Tag',
            'function' => 'doCvsTag',
            'shortcut' => 'ct',
            'options' => array(
                'quiet' => array(
                    'shortopt' => 'q',
                    'doc' => 'Be quiet',
                    ),
                'reallyquiet' => array(
                    'shortopt' => 'Q',
                    'doc' => 'Be really quiet',
                    ),
                'slide' => array(
                    'shortopt' => 'F',
                    'doc' => 'Move (slide) tag if it exists',
                    ),
                'delete' => array(
                    'shortopt' => 'd',
                    'doc' => 'Remove tag',
                    ),
                'dry-run' => array(
                    'shortopt' => 'n',
                    'doc' => 'Don\'t do anything, just pretend',
                    ),
                ),
            'doc' => '<package.xml>
Sets a CVS tag on all files in a package.  Use this command after you have
packaged a distribution tarball with the "package" command to tag what
revisions of what files were in that release.  If need to fix something
after running cvstag once, but before the tarball is released to the public,
use the "slide" option to move the release tag.
',
            ),
        'run-tests' => array(
            'summary' => 'Run Regression Tests',
            'function' => 'doRunTests',
            'shortcut' => 'rt',
            'options' => array(),
            'doc' => '[testfile|dir ...]
Run regression tests with PHP\'s regression testing script (run-tests.php).',
            ),
        'package-dependencies' => array(
            'summary' => 'Show package dependencies',
            'function' => 'doPackageDependencies',
            'shortcut' => 'pd',
            'options' => array(),
            'doc' => '
List all depencies the package has.'
            ),
        'sign' => array(
            'summary' => 'Sign a package distribution file',
            'function' => 'doSign',
            'shortcut' => 'si',
            'options' => array(),
            'doc' => '<package-file>
Signs a package distribution (.tar or .tgz) file with GnuPG.',
            ),
        'makerpm' => array(
            'summary' => 'Builds an RPM spec file from a PEAR package',
            'function' => 'doMakeRPM',
            'shortcut' => 'rpm',
            'options' => array(
                'spec-template' => array(
                    'shortopt' => 't',
                    'arg' => 'FILE',
                    'doc' => 'Use FILE as RPM spec file template'
                    ),
                'rpm-pkgname' => array(
                    'shortopt' => 'p',
                    'arg' => 'FORMAT',
                    'doc' => 'Use FORMAT as format string for RPM package name, %s is replaced
by the PEAR package name, defaults to "PEAR::%s".',
                    ),
                ),
            'doc' => '<package-file>

Creates an RPM .spec file for wrapping a PEAR package inside an RPM
package.  Intended to be used from the SPECS directory, with the PEAR
package tarball in the SOURCES directory:

$ pear makerpm ../SOURCES/Net_Socket-1.0.tgz
Wrote RPM spec file PEAR::Net_Geo-1.0.spec
$ rpm -bb PEAR::Net_Socket-1.0.spec
...
Wrote: /usr/src/redhat/RPMS/i386/PEAR::Net_Socket-1.0-1.i386.rpm
',
            ),
        );

    var $output;

    // }}}
    // {{{ constructor

    /**
     * PEAR_Command_Package constructor.
     *
     * @access public
     */
    function PEAR_Command_Package(&$ui, &$config)
    {
        parent::PEAR_Command_Common($ui, $config);
    }

    // }}}

    // {{{ _displayValidationResults()

    function _displayValidationResults($err, $warn, $strict = false)
    {
        foreach ($err as $e) {
            $this->output .= "Error: $e\n";
        }
        foreach ($warn as $w) {
            $this->output .= "Warning: $w\n";
        }
        $this->output .= sprintf('Validation: %d error(s), %d warning(s)'."\n",
                                       sizeof($err), sizeof($warn));
        if ($strict && sizeof($err) > 0) {
            $this->output .= "Fix these errors and try again.";
            return false;
        }
        return true;
    }

    // }}}
    // {{{ doPackage()

    function doPackage($command, $options, $params)
    {
        $this->output = '';
        include_once 'PEAR/Packager.php';
        $pkginfofile = isset($params[0]) ? $params[0] : 'package.xml';
        $packager =& new PEAR_Packager($this->config->get('php_dir'),
                                       $this->config->get('ext_dir'),
                                       $this->config->get('doc_dir'));
        $packager->debug = $this->config->get('verbose');
        $err = $warn = array();
        $dir = dirname($pkginfofile);
        $compress = empty($options['nocompress']) ? true : false;
        $result = $packager->package($pkginfofile, $compress);
        if (PEAR::isError($result)) {
            $this->ui->outputData($this->output, $command);
            return $this->raiseError($result);
        }
        // Don't want output, only the package file name just created
        if (isset($options['showname'])) {
            $this->output = $result;
        }
        /* (cox) What is supposed to do that code?
        $lines = explode("\n", $this->output);
        foreach ($lines as $line) {
            $this->output .= $line."n";
        }
        */
        if (PEAR::isError($result)) {
            $this->output .= "Package failed: ".$result->getMessage();
        }
        $this->ui->outputData($this->output, $command);
        return true;
    }

    // }}}
    // {{{ doPackageValidate()

    function doPackageValidate($command, $options, $params)
    {
        $this->output = '';
        if (sizeof($params) < 1) {
            $params[0] = "package.xml";
        }
        $obj = new PEAR_Common;
        $info = null;
        if ($fp = @fopen($params[0], "r")) {
            $test = fread($fp, 5);
            fclose($fp);
            if ($test == "<?xml") {
                $info = $obj->infoFromDescriptionFile($params[0]);
            }
        }
        if (empty($info)) {
            $info = $obj->infoFromTgzFile($params[0]);
        }
        if (PEAR::isError($info)) {
            return $this->raiseError($info);
        }
        $obj->validatePackageInfo($info, $err, $warn);
        $this->_displayValidationResults($err, $warn);
        $this->ui->outputData($this->output, $command);
        return true;
    }

    // }}}
    // {{{ doCvsTag()

    function doCvsTag($command, $options, $params)
    {
        $this->output = '';
        $_cmd = $command;
        if (sizeof($params) < 1) {
            $help = $this->getHelp($command);
            return $this->raiseError("$command: missing parameter: $help[0]");
        }
        $obj = new PEAR_Common;
        $info = $obj->infoFromDescriptionFile($params[0]);
        if (PEAR::isError($info)) {
            return $this->raiseError($info);
        }
        $err = $warn = array();
        $obj->validatePackageInfo($info, $err, $warn);
        if (!$this->_displayValidationResults($err, $warn, true)) {
            $this->ui->outputData($this->output, $command);
            break;
        }
        $version = $info['version'];
        $cvsversion = preg_replace('/[^a-z0-9]/i', '_', $version);
        $cvstag = "RELEASE_$cvsversion";
        $files = array_keys($info['filelist']);
        $command = "cvs";
        if (isset($options['quiet'])) {
            $command .= ' -q';
        }
        if (isset($options['reallyquiet'])) {
            $command .= ' -Q';
        }
        $command .= ' tag';
        if (isset($options['slide'])) {
            $command .= ' -F';
        }
        if (isset($options['delete'])) {
            $command .= ' -d';
        }
        $command .= ' ' . $cvstag . ' ' . escapeshellarg($params[0]);
        foreach ($files as $file) {
            $command .= ' ' . escapeshellarg($file);
        }
        if ($this->config->get('verbose') > 1) {
            $this->output .= "+ $command\n";
        }
        $this->output .= "+ $command\n";
        if (empty($options['dry-run'])) {
            $fp = popen($command, "r");
            while ($line = fgets($fp, 1024)) {
                $this->output .= rtrim($line)."\n";
            }
            pclose($fp);
        }
        $this->ui->outputData($this->output, $_cmd);
        return true;
    }

    // }}}
    // {{{ doCvsDiff()

    function doCvsDiff($command, $options, $params)
    {
        $this->output = '';
        if (sizeof($params) < 1) {
            $help = $this->getHelp($command);
            return $this->raiseError("$command: missing parameter: $help[0]");
        }
        $obj = new PEAR_Common;
        $info = $obj->infoFromDescriptionFile($params[0]);
        if (PEAR::isError($info)) {
            return $this->raiseError($info);
        }
        $files = array_keys($info['filelist']);
        $cmd = "cvs";
        if (isset($options['quiet'])) {
            $cmd .= ' -q';
            unset($options['quiet']);
        }
        if (isset($options['reallyquiet'])) {
            $cmd .= ' -Q';
            unset($options['reallyquiet']);
        }
        if (isset($options['release'])) {
            $cvsversion = preg_replace('/[^a-z0-9]/i', '_', $options['release']);
            $cvstag = "RELEASE_$cvsversion";
            $options['revision'] = $cvstag;
            unset($options['release']);
        }
        $execute = true;
        if (isset($options['dry-run'])) {
            $execute = false;
            unset($options['dry-run']);
        }
        $cmd .= ' diff';
        // the rest of the options are passed right on to "cvs diff"
        foreach ($options as $option => $optarg) {
            $arg = @$this->commands[$command]['options'][$option]['arg'];
            $short = @$this->commands[$command]['options'][$option]['shortopt'];
            $cmd .= $short ? " -$short" : " --$option";
            if ($arg && $optarg) {
                $cmd .= ($short ? '' : '=') . escapeshellarg($optarg);
            }
        }
        foreach ($files as $file) {
            $cmd .= ' ' . escapeshellarg($file);
        }
        if ($this->config->get('verbose') > 1) {
            $this->output .= "+ $cmd\n";
        }
        if ($execute) {
            $fp = popen($cmd, "r");
            while ($line = fgets($fp, 1024)) {
                $this->output .= rtrim($line)."\n";
            }
            pclose($fp);
        }
        $this->ui->outputData($this->output, $command);
        return true;
    }

    // }}}
    // {{{ doRunTests()

    function doRunTests($command, $options, $params)
    {
        $cwd = getcwd();
        $php = PHP_BINDIR . '/php' . (OS_WINDOWS ? '.exe' : '');
        putenv("TEST_PHP_EXECUTABLE=$php");
        $ip = ini_get("include_path");
        $ps = OS_WINDOWS ? ';' : ':';
        $run_tests = $rtsts = $this->config->get('php_dir') . DIRECTORY_SEPARATOR . 'run-tests.php';
        if (!file_exists($run_tests)) {
            $run_tests = PEAR_INSTALL_DIR . DIRECTORY_SEPARATOR . 'run-tests.php';
            if (!file_exists($run_tests)) {
                return $this->raiseError("No run-tests.php file found. Please copy this ".
                                                "file from the sources of your PHP distribution to $rtsts");
            }
        }
        $plist = implode(" ", $params);
        $cmd = "$php -C -d include_path=$cwd$ps$ip -f $run_tests -- $plist";
        system($cmd);
        return true;
    }

    // }}}
    // {{{ doPackageDependencies()

    function doPackageDependencies($command, $options, $params)
    {
        // $params[0] -> the PEAR package to list its information
        if (sizeof($params) != 1) {
            return $this->raiseError("bad parameter(s), try \"help $command\"");
        }

        $obj = new PEAR_Common();
        if (PEAR::isError($info = $obj->infoFromAny($params[0]))) {
            return $this->raiseError($info);
        }

        if (is_array($info['release_deps'])) {
            $data = array(
                'caption' => 'Dependencies for ' . $info['package'],
                'border' => true,
                'headline' => array("Type", "Name", "Relation", "Version"),
                );

            foreach ($info['release_deps'] as $d) {

                if (isset($this->_deps_rel_trans[$d['rel']])) {
                    $rel = $this->_deps_rel_trans[$d['rel']];
                } else {
                    $rel = $d['rel'];
                }

                if (isset($this->_deps_type_trans[$d['type']])) {
                    $type = ucfirst($this->_deps_type_trans[$d['type']]);
                } else {
                    $type = $d['type'];
                }

                if (isset($d['name'])) {
                    $name = $d['name'];
                } else {
                    $name = '';
                }

                if (isset($d['version'])) {
                    $version = $d['version'];
                } else {
                    $version = '';
                }

                $data['data'][] = array($type, $name, $rel, $version);
            }

            $this->ui->outputData($data, $command);
            return true;
        }

        // Fallback
        $this->ui->outputData("This package does not have any dependencies.", $command);
    }

    // }}}
    // {{{ doSign()

    function doSign($command, $options, $params)
    {
        // should move most of this code into PEAR_Packager
        // so it'll be easy to implement "pear package --sign"
        if (sizeof($params) != 1) {
            return $this->raiseError("bad parameter(s), try \"help $command\"");
        }
        if (!file_exists($params[0])) {
            return $this->raiseError("file does not exist: $params[0]");
        }
        $obj = new PEAR_Common;
        $info = $obj->infoFromTgzFile($params[0]);
        if (PEAR::isError($info)) {
            return $this->raiseError($info);
        }
        include_once "Archive/Tar.php";
        include_once "System.php";
        $tar = new Archive_Tar($params[0]);
        $tmpdir = System::mktemp('-d pearsign');
        if (!$tar->extractList('package.xml package.sig', $tmpdir)) {
            return $this->raiseError("failed to extract tar file");
        }
        if (file_exists("$tmpdir/package.sig")) {
            return $this->raiseError("package already signed");
        }
        @unlink("$tmpdir/package.sig");
        $input = $this->ui->userDialog($command,
                                       array('GnuPG Passphrase'),
                                       array('password'));
        $gpg = popen("gpg --batch --passphrase-fd 0 --armor --detach-sign --output $tmpdir/package.sig $tmpdir/package.xml 2>/dev/null", "w");
        if (!$gpg) {
            return $this->raiseError("gpg command failed");
        }
        fwrite($gpg, "$input[0]\r");
        if (pclose($gpg) || !file_exists("$tmpdir/package.sig")) {
            return $this->raiseError("gpg sign failed");
        }
        $tar->addModify("$tmpdir/package.sig", '', $tmpdir);
        return true;
    }

    // }}}
    // {{{ doMakeRPM()
    /*
    (cox)
    TODO:
        - Fill the rpm dependencies in the template file.
    IDEAS:
        - Instead of mapping the role to rpm vars, perhaps it's better
          to use directly the pear cmd to install the files by itself
          in %postrun so:
          pear -d php_dir=%{_libdir}/php/pear -d test_dir=.. <package>
    */

    function doMakeRPM($command, $options, $params)
    {
        if (sizeof($params) != 1) {
            return $this->raiseError("bad parameter(s), try \"help $command\"");
        }
        if (!file_exists($params[0])) {
            return $this->raiseError("file does not exist: $params[0]");
        }
        include_once "Archive/Tar.php";
        include_once "PEAR/Installer.php";
        include_once "System.php";
        $tar = new Archive_Tar($params[0]);
        $tmpdir = System::mktemp('-d pear2rpm');
        $instroot = System::mktemp('-d pear2rpm');
        $tmp = $this->config->get('verbose');
        $this->config->set('verbose', 0);
        $installer = new PEAR_Installer($this->ui);
        $info = $installer->install($params[0],
                                    array('installroot' => $instroot,
                                          'nodeps' => true));
        $pkgdir = "$info[package]-$info[version]";
        $info['rpm_xml_dir'] = '/var/lib/pear';
        $this->config->set('verbose', $tmp);
        if (!$tar->extractList("package.xml", $tmpdir, $pkgdir)) {
            return $this->raiseError("failed to extract $params[0]");
        }
        if (!file_exists("$tmpdir/package.xml")) {
            return $this->raiseError("no package.xml found in $params[0]");
        }
        if (isset($options['spec-template'])) {
            $spec_template = $options['spec-template'];
        } else {
            $spec_template = $this->config->get('data_dir') .
                '/PEAR/template.spec';
        }
        if (isset($options['rpm-pkgname'])) {
            $rpm_pkgname_format = $options['rpm-pkgname'];
        } else {
            $rpm_pkgname_format = "PEAR::%s";
        }

        $info['extra_headers'] = '';
        $info['doc_files'] = '';
        $info['files'] = '';
        $info['rpm_package'] = sprintf($rpm_pkgname_format, $info['package']);
        $srcfiles = 0;
        foreach ($info['filelist'] as $name => $attr) {
            if ($attr['role'] == 'doc') {
                $info['doc_files'] .= " $name";
            // Map role to the rpm vars
            } else {
                $c_prefix = '%{_libdir}/php/pear';
                switch ($attr['role']) {
                    case 'php':
                        $prefix = $c_prefix; break;
                    case 'ext':
                        $prefix = '%{_libdir}/php'; break; // XXX good place?
                    case 'src':
                        $srcfiles++;
                        $prefix = '%{_includedir}/php'; break; // XXX good place?
                    case 'test':
                        $prefix = "$c_prefix/tests/" . $info['package']; break;
                    case 'data':
                        $prefix = "$c_prefix/data/" . $info['package']; break;
                    case 'script':
                        $prefix = '%{_bindir}'; break;
                }
                $info['files'] .= "$prefix/$name\n";
            }
        }
        if ($srcfiles > 0) {
            include_once "OS/Guess.php";
            $os = new OS_Guess;
            $arch = $os->getCpu();
        } else {
            $arch = 'noarch';
        }
        $cfg = array('master_server', 'php_dir', 'ext_dir', 'doc_dir',
                     'bin_dir', 'data_dir', 'test_dir');
        foreach ($cfg as $k) {
            $info[$k] = $this->config->get($k);
        }
        $info['arch'] = $arch;
        $fp = @fopen($spec_template, "r");
        if (!$fp) {
            return $this->raiseError("could not open RPM spec file template $spec_template: $php_errormsg");
        }
        $spec_contents = preg_replace('/@([a-z0-9_-]+)@/e', '$info["\1"]', fread($fp, filesize($spec_template)));
        fclose($fp);
        $spec_file = "$info[rpm_package]-$info[version].spec";
        $wp = fopen($spec_file, "wb");
        if (!$wp) {
            return $this->raiseError("could not write RPM spec file $spec_file: $php_errormsg");
        }
        fwrite($wp, $spec_contents);
        fclose($wp);
        $this->ui->outputData("Wrote RPM spec file $spec_file", $command);

        return true;
    }

    // }}}
}

?>
