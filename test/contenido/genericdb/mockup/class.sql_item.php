<?php

/**
 * @author marcus.gnass
 */
class SqlItem
{
    /**
     * @param array $tables
     *
     * @return string
     */
    public static function getDeleteStatement(array $tables)
    {
        $tableClause = implode('``, `', $tables);
        return "DROP TABLE IF EXISTS `$tableClause`;";
    }

    /**
     *
     * @return string
     */
    public static function getCreateConTestStatement()
    {
        return (new cSqlTemplate())->parse("
            CREATE TABLE `con_test` (
                `ID` INT(11) NOT NULL AUTO_INCREMENT,
                `Name` CHAR(35) NOT NULL DEFAULT '',
                `CountryCode` CHAR(3) NOT NULL DEFAULT '',
                `District` CHAR(20) NOT NULL DEFAULT '',
                `Population` INT(11) NOT NULL DEFAULT '0',
                PRIMARY KEY (`ID`)
            ) ENGINE=!ENGINE! DEFAULT CHARSET=!CHARSET! AUTO_INCREMENT=0;"
        );
    }

    /**
     *
     * @return string
     */
    public static function getInsertConTestStatement()
    {
        return "
            INSERT INTO `con_test` VALUES
                (1, 'Kabul', 'AFG', 'Kabol', 1780000),
                (2, 'Qandahar', 'AFG', 'Qandahar', 237500),
                (3, 'Herat', 'AFG', 'Herat', 186800)
            ;";
    }

    /**
     *
     * @return string
     */
    public static function getCreateDogStatement()
    {
        return (new cSqlTemplate())->parse("
            CREATE TABLE `con_test_dog` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) DEFAULT NULL,
                `descr` TEXT,
                `size` ENUM('small', 'medium', 'large') DEFAULT NULL,
                `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=!ENGINE! DEFAULT CHARSET=!CHARSET! AUTO_INCREMENT=0;"
        );
    }

    /**
     *
     * @return string
     */
    public static function getInserDogStatement()
    {
        return "
            INSERT INTO `con_test_dog` (`id`, `name`, `descr`, `size`, `date`) VALUES
                (1, 'Max', 'Its distinctive appearance and deep foghorn voice make it stand out in a crowd.', 'medium', '2013-09-26 12:14:28'),
                (2, 'Jake', 'It loves human companionship and being part of the group.', 'medium', '2013-09-26 12:14:28'),
                (3, 'Buster', 'Short-legged but surprisingly strong and agile.', 'small', '2013-09-26 12:14:28')
            ;";
    }

    /**
     *
     * @return string
     */
    public static function getCreateDogRfidStatement()
    {
        return (new cSqlTemplate())->parse("
            CREATE TABLE `con_test_rfid_dog` (
                `dog_id` INT(11) NOT NULL,
                `bar_code` VARCHAR(128) NOT NULL,
                `notes` TEXT,
                `iso_compliant` ENUM('y', 'n') DEFAULT 'n',
                `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`dog_id`)
            ) ENGINE=!ENGINE! DEFAULT CHARSET=!CHARSET!;"
        );
    }

    /**
     *
     * @return string
     */
    public static function getInserDogRfidStatement()
    {
        return "
            INSERT INTO `con_test_rfid_dog` (`dog_id`, `bar_code`, `notes`, `iso_compliant`, `date`) VALUES
                (1, '234k34340ll2342323022', 'This is a RFID tag for the Max', 'y', '2013-09-26 12:14:28'),
                (2, '09383638920290397d829', 'This is a RFID tag for the Jake', 'y', '2013-09-26 12:14:28'),
                (3, '30id8383837210jndal20', 'This is a RFID tag for the Buster', 'y', '2013-09-26 12:14:28')
            ;";
    }
}

// /**
//  *
//  * @author marcus.gnass
//  * @method TITCollection createNewItem
//  * @method TITCollection|bool next
//  */
// class ITCollection extends ItemCollection {

//     /**
//      *
//      * @param string|bool $where
//      */
//     public function __construct($where = false) {
//         parent::__construct('', 'ID');
//         $this->_setItemClass('TestItem');
//         if (false !== $where) {
//             $this->select($where);
//         }
//     }
// }

// /**
//  *
//  * @author marcus.gnass
//  */
// class TITCollection extends ItemCollection {

//     /**
//      *
//      * @param string|bool $where
//      */
//     public function __construct($where = false) {
//         parent::__construct('ID', '');
//         $this->_setItemClass('TestItem');
//         if (false !== $where) {
//             $this->select($where);
//         }
//     }
// }
