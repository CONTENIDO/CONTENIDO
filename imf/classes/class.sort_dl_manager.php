<?php
Class SortDlManager{
    
public static function sortName($style, $conti) {
     if ($style === 'asc') {
         usort($conti, array(
          'uplContent',
          'cmpAscName'
         ));
     }

     if ($style === 'des') {
         usort($conti, array(
          'uplContent',
          'cmpDesName'
         ));
     }
     return $conti;
 }

// sort public static public static functions for postal codes
 public static function sortSize($style, $conti) {
     if ($style === 'asc') {
         usort($conti, array(
          'uplContent',
          'cmpAscSize'
         ));
     }

     if ($style === 'des') {
         usort($conti, array(
          'uplContent',
          'cmpDesSize'
         ));
     }
     return $conti;
 }

// sort public static public static functions for countries
 public static function sortDate($style, $conti) {
     if ($style === 'asc') {
         usort($conti, array(
          'uplContent',
          'cmpAscDate'
         ));
     }

     if ($style === 'des') {
         usort($conti, array(
          'uplContent',
          'cmpDesDate'
         ));
     }
     return $conti;
 }

 public static function sortType($style, $conti) {
     if ($style === 'asc') {
         usort($conti, array(
          'uplContent',
          'cmpAscType'
         ));
     }

     if ($style === 'des') {
         usort($conti, array(
          'uplContent',
          'cmpDesType'
         ));
     }
     return $conti;
 }

 public static function sortDescription($style, $conti) {
     if ($style === 'asc') {
         usort($conti, array(
          'uplContent',
          'cmpAscDescription'
         ));
     }

     if ($style === 'des') {
         usort($conti, array(
          'uplContent',
          'cmpDesDescription'
         ));
     }
     return $conti;
 }
    
}
?>
