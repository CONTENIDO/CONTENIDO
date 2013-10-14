<?php

class SqlItem {

    public static function getDeleteStatement(array $tables) {
        $sql = 'DROP TABLE IF EXISTS';

        foreach ($tables as $key => $table) {
            $sql = $sql . ' ' . $table . ',';
        }
        $sql = substr($sql, 0, strlen($sql) - 1);
        return $sql . ';';
    }

    public static function getCreateConTestStatement() {
        return "
            CREATE TABLE `con_test` (
                `ID` int(11) NOT NULL auto_increment,
                `Name` char(35) NOT NULL default '',
                `CountryCode` char(3) NOT NULL default '',
                `District` char(20) NOT NULL default '',
                `Population` int(11) NOT NULL default '0',
                PRIMARY KEY  (`ID`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;";
    }

    public static function getInsertConTestStatement() {
        return "
            INSERT INTO `con_test` VALUES
                (1, 'Kabul', 'AFG', 'Kabol', 1780000),
                (2, 'Qandahar', 'AFG', 'Qandahar', 237500),
                (3, 'Herat', 'AFG', 'Herat', 186800)
            ;";
    }

    public static function getCreateDogStatement() {
        return "
            CREATE TABLE `con_test_dog` (
                `id` int(11) NOT NULL auto_increment,
                `name` varchar(255) default NULL,
                `descr` text,
                `size` enum('small', 'medium', 'large') default NULL,
                `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
                PRIMARY KEY  (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;";
    }

    public static function getInserDogStatement() {
        return "
            INSERT INTO `con_test_dog` (`id`, `name`, `descr`, `size`, `date`) VALUES
                (1, 'Max', 'Its distinctive appearance and deep foghorn voice make it stand out in a crowd.', 'medium', '2013-09-26 12:14:28'),
                (2, 'Jake', 'It loves human companionship and being part of the group.', 'medium', '2013-09-26 12:14:28'),
                (3, 'Buster', 'Short-legged but surprisingly strong and agile.', 'small', '2013-09-26 12:14:28')
            ;";
    }

    public static function getCreateDogRfidStatement() {
        return "
            CREATE TABLE `con_test_rfid_dog` (
                `dog_id` int(11) NOT NULL,
                `bar_code` varchar(128) NOT NULL,
                `notes` text,
                `iso_compliant` enum('y', 'n') default 'n',
                `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
                PRIMARY KEY  (`dog_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    }

    public static function getInserDogRfidStatement() {
        return "
            INSERT INTO `con_test_rfid_dog` (`dog_id`, `bar_code`, `notes`, `iso_compliant`, `date`) VALUES
                (1, '234k34340ll2342323022', 'This is a RFID tag for the Max', 'y', '2013-09-26 12:14:28'),
                (2, '09383638920290397d829', 'This is a RFID tag for the Jake', 'y', '2013-09-26 12:14:28'),
                (3, '30id8383837210jndal20', 'This is a RFID tag for the Buster', 'y', '2013-09-26 12:14:28')
            ;";
    }

}

// class ITCollection extends ItemCollection {
// public function __construct($where = false) {
// parent::__construct('', 'ID');
// $this->_setItemClass('TestItem');
// if (false !== $where) {
// $this->select($where);
// }
// }
// }
// class TITCollection extends ItemCollection {
// public function __construct($where = false) {
// parent::__construct('ID', '');
// $this->_setItemClass('TestItem');
// if (false !== $where) {
// $this->select($where);
// }
// }
// }
