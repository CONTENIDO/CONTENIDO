<?PHP
 Class uplContent {

     private $_idupl;
     private $_currFilePath;
     private $_type;
     private $_date;
     private $_hierachie;
     private $_folder;
     private $_name;
     private $_dlLink;
     private $_link;
     private $_size;
     private $_navLink;
     private $_description;
     private $_downloadLink;

     public function setDownloadLink($link) {
         $this->_downloadLink = $link;
     }

     public function getDownloadLink() {
         return $this->_downloadLink;
     }

     public function setDescription($des) {
         $this->_description = $des;
     }

     public function getDescription() {
         return $this->_description;
     }

     public function setNavLink($navLink) {
         $this->_navLink = $navLink;
     }

     public function getNaviLink() {
         return $this->_navLink;
     }

     public function setDlLink($link) {

         $this->_dlLink = $link;
     }

     public function getDLink() {
         return $this->_dlLink;
     }

     public function setLink($link) {

         $this->_link = $link;
     }

     public function getLink() {
         return $this->_link;
     }

     function __construct($idupl = 0, $currFilePath = 0, $type = 0, $date = 0) {
         $this->_idupl = $idupl;
         $this->_currFilePath = $currFilePath;
         $this->_type = $type;
         $this->_date = $date;
     }

     public function setName($name) {
         $this->_name = $name;
     }

     public function getName() {
         return $this->_name;
     }

     public function setSize($size) {
         $this->_size = $size;
     }

     public function getSize() {
         return $this->_size;
     }

     public function setHierarchie($pathname) {

         if (substr_count($pathname, '/') == 0) {
             $this->_hierachie = 0;
         } else {
             $this->_hierachie = substr_count($pathname, '/') - 1;
         }
     }

     public function getHierarchie() {
         return $this->_hierachie;
     }

     public function getIdupl() {
         return $this->_idupl;
     }

     public function setIdupl($idupl) {
         $this->_idupl = $idupl;
     }

     public function getCurrFilePath() {
         return $this->_currFilePath;
     }

     public function setCurrFilePath($currFilePath) {
         $this->_currFilePath = $currFilePath;
     }

     public function getType() {
         return $this->_type;
     }

     public function setType($type) {
         $this->_type = $type;
     }

     public function getDate() {
         return $this->_date;
     }

     public function setDate($date) {
 
         $year = substr($date, 0, 4);
         $date = substr($date, 5, strlen($date));
         $month = substr($date, 0, 2);
         $date = substr($date, 3, strlen($date));
         $day = substr($date, 0, 2);
         $this->_date = $day . '.' . $month . '.' . $year;
         $this->_date = trim($this->_date);
     }

     public function setFolder($folder) {
         $this->_folder = $folder;
     }

     public function getFolder() {
         return $this->_folder;
     }

     public function cmpAscName($a, $b) {
         $cmp = strcmp($b->getName(), $a->getName());
         return $cmp;
     }

     public function cmpDesName($a, $b) {
         $cmp = strcmp($b->getName(), $a->getName());
         return ($cmp) * -1;
     }

     public function cmpAscType($a, $b) {
         $cmp = strcmp($b->getType(), $a->getType());
         return $cmp;
     }

     public function cmpDesType($a, $b) {
         $cmp = strcmp($b->getType(), $a->getType());
         return ($cmp) * -1;
     }

     public function cmpAscDescription($a, $b) {
         $cmp = strcmp($b->getDescription(), $a->getDescription());
         return $cmp;
     }

     public function cmpDesDescription($a, $b) {
         $cmp = strcmp($b->getDescription(), $a->getDescription());
         return ($cmp) * -1;
     }

     public function cmpAscSize($a, $b) {
         $cmp = strcmp($b->getSize(), $a->getSize());
         return $cmp;
     }

     public function cmpDesSize($a, $b) {
         $cmp = strcmp($b->getSize(), $a->getSize());
         return ($cmp) * -1;
     }

     public function cmpAscDate($a, $b) {
         $cmp = strcmp($b->getDate(), $a->getDate());
         return $cmp;
     }

     public function cmpDesDate($a, $b) {
         $cmp = strcmp($b->getDate(), $a->getDate());
         return ($cmp) * -1;
     }
 }
 ?>