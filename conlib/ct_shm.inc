<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * PHPLIB Data Storage Container using Shared Memory
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    CONTENIDO Core
 * @subpackage Session
 * @version    1.3
 * @author     Sascha Schumann <sascha@schumann.cx>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <CONTENIDO Version>
 *
 * {@internal
 *   created  2000-01-01
 *   modified 2008-07-03, bilal arslan, added security fix
 *   modified 2011-10-09, Murat Purc, Partly ported to PHP 5 and formatted/documented code
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * @package    CONTENIDO Core
 * @subpackage Session
 */
class CT_Shm
{
    ##
    ## Define these parameters by overwriting or by
    ## deriving your own class from it (recommened)
    ##

    /**
     * Maximum supported sessions
     * @var int
     */
    public $max_sessions = 500;

    /**
     * Key of shared memory segment (unique)
     * @var int
     */
    public $shm_key = 900000;

    /**
     * Size in bytes
     * @var int
     */
    public $shm_size = 64000;

    ## end of configuration

    /**
     * Our shared memory handle
     * @var resource
     */
    public $shmid;

    /**
     * Our semaphore handle
     * @var resource
     */
    public $semid;


    public function extract($id)
    {
        return substr($id, 0, strpos($id, '_'));
    }

    public function ac_start()
    {
        $this->shmid = shm_attach($this->shm_key, $this->shm_size, 0600);
    }

    public function ac_get_lock()
    {
        $this->semid = sem_get($this->shm_key + 1);
        sem_acquire($this->semid);
    }

    public function ac_release_lock()
    {
        shm_detach($this->shmid);
        sem_release($this->semid);
    }

    public function ac_newid($str, $name)
    {
        for ($i = 1; $i <= $this->max_sessions && (@shm_get_var($this->shmid, $i) != false); $i++) {
            $id = $i . '_' . $str;
        }
        $this->ac_store($id, $name, '');
        return $id;
    }

    public function ac_store($id, $name, $str)
    {
        $val = "$id;" . urlencode($name) . ';' . urlencode($str) . ';' . time();
        shm_put_var($this->shmid, $this->extract($id), $val);
        return true;
    }

    public function ac_delete($id, $name)
    {
        shm_remove_var($this->shmid, $this->extract($id));
    }

    public function ac_gc($gc_time, $name)
    {
        $cmp = time() - $gc_time * 60;
        for ($i = 1; $i <= $this->max_sessions; $i++) {
            if (($val = @shm_get_var($this->shmid, $i)) != false) {
                $dat = explode(';', $val);
                if ($name == $dat[1] && intval($dat[3]) < $cmp) {
                    shm_remove_var($this->shmid, $i);
                }
            }
        }
    }

    public function ac_halt($s)
    {
        echo "<b>$s</b>";
        exit;
    }

    public function ac_get_value($id, $name)
    {
        $i = $this->extract($id);
        $var = shm_get_var($this->shmid, $i);
        if ($var == '') {
            return '';
        }
        $dat = explode(';', $var);
        // if classname or md5 id does not match...
        if ($name != urldecode($dat[1]) || $dat[0] != $id) {
            $this->ac_halt('security stop');
        }
        return urldecode($dat[2]);
    }
}

?>