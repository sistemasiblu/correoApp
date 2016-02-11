<?php


Class Conf{ 
   private $_domain; 
   private $_userdb; 
   private $_passdb; 
   private $_hostdb; 
   private $_db; 
   private $_dbType; 
   private $_dir;
   
   private static $_instance; 
 
   private function __construct(){ 
      //Datos de configuraci&#243;n de la conexi&#243;n a la base de datos 

      //Dominio
      $domain=''; 

      //Servidor 
      $host='localhost'; 
       
      //Usuario 
      $user='root';

      //Password 
      $password='C11blu01'; 
       
      //Base de datos a utilizar 
      $db='Iblu';

      //Qu&#233; sistema gestor de base de datos utilizamos, mysql, oracle, ... 
      $dbType='mysql' ;

      $carpeta='iblu';
      
     
      
      $this->_domain=$domain; 
      $this->_userdb=$user; 
      $this->_passdb=$password; 
      $this->_hostdb=$host; 
      $this->_db=$db; 
      $this->_dbType=$dbType; 
      $this->_folder=$carpeta;
      
//      if(isset($_SESSION["mysqlUser"]))
//      {
//          echo 'EXISTE UNA SESION ABIERTA';
//      }
//      else
//      {
//          echo 'NO EXISTE UNA SESION';
//      }
//      
   } 
 
   private function __clone(){ } 
 
   private function __wakeup(){ } 
 
   public static function getInstance(){ 
      if (!(self::$_instance instanceof self)){ 
         self::$_instance=new self(); 
      } 
      return self::$_instance; 
   } 
 
   public function getUserDB(){ 
      $var=$this->_userdb; 
      return $var; 
   } 
 
   public function getHostDB(){ 
      $var=$this->_hostdb; 
      return $var; 
   } 
 
   public function getPassDB(){ 
       
      
      $var=$this->_passdb; 
      return $var; 
   } 
 
   public function getDB(){ 
      $var=$this->_db; 
      return $var; 
   } 
 
   public function getDBType(){ 
     $var=$this->_dbType; 
     return $var; 
   } 

   public function getfolder(){ 
     $var=$this->_folder; 
     return $var; 
   } 
}
?>