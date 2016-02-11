<?php
/*
* @Projecto: Lector de correos IMAP 
* @Archivo: /clases/EmailReader.php
* @Proposito: Proporciona las funciones para conectarse a un servidor IMAP y leer el INBOX
*           
* @Author: Harvin Adolfo Bejarano
*/


class EmailReader {
 
    //Propiedades
    private $imapServer;
    private $portNumber;
    private $mailUser ;
    private $mailPass;
    private $imap_client;
    
    public $lastError;
    //Constantes
    const EMAILURLDEFAULT = '/imap/novalidate-cert';
    
    
    public function __construct()    
    {
       $this->lastError = ''    ;
    }
    
    public function setProp($propName,$value)
    {
        $this->$propName = $value;
    }
    
    public function getProp($propName)
    {
        return $this->$propName;
    }
    
    //@Proposito: Abre una conecion con el servidor de correo.
    public function Open()
    {
        $resp = true;
        //'{imap.gmail.com:993/imap/ssl}INBOX'
        $imapServ = '{' . $this->imapServer . ':' . $this->portNumber . self::EMAILURLDEFAULT . "}" . 'INBOX';
        
        $this->imap_client = imap_open($imapServ , $this->mailUser, $this->mailPass);
        
        
        if (!$this->imap_client) {
             $resp = false;
             $this->lastError =  imap_last_error();
        }
        
        return $resp;
    }
    
    //@Proposito: Cierra la conecion con el servidor de correo.
    public function Close()
    {
        imap_close($this->imap_client);
    }
    
    //@Proposito: Busca los email de una cuenta dada, debe existir una conexion activa 
    //            con el servidor de correo.  
    public function BuscarMails($from , $readCommand)
    {
        $emails = null;
        if (   $this->Open() )
        {
            $command = $readCommand . " FROM '" . $from . "'";
            $emails = imap_search($this->imap_client, $command);
        }
        
        return $emails;
    }
    
    
    //@Proposito: Recupera el body de los emails
    public function getBody($emails , $part, $subPart) 
    {
        $aBodies = array();
        
               
        foreach ($emails as $email_id) 
        {
            $body = imap_fetchbody($this->imap_client, $email_id, $subPart);
            if ($body == "") {
                $body = imap_fetchbody($this->imap_client, $email_id, $part);
            }
            array_push($aBodies, $body);
        }
        
        return $aBodies;
    }
    
    //@Proposito: Recupera la infomacion de encabezado de un email
    public function getMailInfo($email)
    {
        $header ="";
        $header = imap_headerinfo ($this->imap_client,$email);
        
        return $header;
    }

}
