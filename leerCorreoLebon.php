<?php 
header("Content-Type: text/html;charset=utf-8");
/*
* @Projecto: Lector de correos
* @Archivo: index.php
* @Proposito: Script Principal App   
*           
* @Author: Harvin Adolfo Bejarano
*/

$ruta = dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR;
   
require $ruta.'EmailReader.php';

$oMail = new EmailReader();
$oMail->setProp('imapServer', 'mail.ciiblu.com');
$oMail->setProp('portNumber', '143');
$oMail->setProp('mailUser', 'asierra@ciiblu.com');
$oMail->setProp('mailPass', 'Iblu20$');

$fecha = '2016-01-01';
//$from        = "procesoslebon@lebon.com.co";
$from        = "sistemas@ciiblu.com";
$mails       = $oMail->BuscarMails($from, 'SUBJECT "orden de compra" SINCE "'.$fecha.'"');
$ntotalMails = count($mails);

if($mails == false )
{
  echo "<h1>No hay correos para mostrar. </h1>";
  return ;
}

$aBodys = $oMail->getBody($mails,'2','2.1');


// Jquery Pluig
echo '<script>
function realizaProceso(numOrden, enc, det)
{
    var parametros = {
        "numOrden" : numOrden,
        "enc" : enc,
        "det" : det
    };
    $.ajax(
    {
        data:  parametros,
        url:   "guardarPedidoLeBon.php",
        type:  "post",
        beforeSend: function () {
                $("#resultado").html("Procesando, espere por favor...");
        },
        success:  function (response) {
                //$("#resultado").innerHTML();
                alert("success");
                document.getElementById("resultado").innerHTML = "";
                document.getElementById("resultado2").innerHTML += response;
        },
        error:  function (response) {
            alert("error");
                $("#resultado").html(response);
        }
    });

    

}


</script>';

echo "<script src='jquery-1.9.0.js' type='text/javascript'></script>";
echo "<script src='jquery-ui-1.10.0.custom.min.js' type='text/javascript'></script>";
// JS file
echo "<script src='leerHTMLCorreoLebon.js' type='text/javascript'></script>";

echo "<input type='hidden' id='numeroOrden' value=''>";
echo "<input type='hidden' id='fechaOrden' value=''>";
echo "<input type='hidden' id='observacionOrden' value=''>";
echo "<span id='resultado'></span>";


for($i = 0; $i<$ntotalMails;$i++) 
{
    $mInfo   = $oMail->getMailInfo( $mails[ $i ] ); 
    
    // if( strpos( strtoupper (trim($mInfo->subject)) , strtoupper(trim('orden de compra'))) )
    // {
        $idMail = $mails[$i];
        
        $divName = "divMail" . strval($idMail);
        
        //Procesamos el Email
        addBody( $aBodys[$i], $idMail , $divName);
        
    // }
}
$oMail->Close();

//echo 'INDEX';
//exit();
function addBody($body , $idMail, $divName)
{
    echo "<div id='result'>";
    echo "<table id = 'tblresults' style='border:1px solid #000000;'> <tbody></tbody> </table>" ;
    echo "</div>";
    
    echo "<div id='" . $divName .   "' style='display:none;'><br> CORREO ID ".$idMail."<br>" ;
    echo $body ;
    echo "<script >";
    echo "  generarInsert('".$divName."'); ";
    echo "</script >"; 
    echo  "</div>";
    echo "<span id='resultado2'></span>";
}


?>