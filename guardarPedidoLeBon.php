<?php 
header("Content-Type: text/html;charset=utf-8");
// con los 2 parametros enviados desde la funcion de AJAX, simplemente los ejecutamos para 
// insertar los pedidos en la  base de datos 
$ruta = dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR;
//$ruta = str_replace("correoApp/", '', $ruta);

require_once('db.class.php'); 
require_once('conf.class.php'); 

$bd = Db::getInstance();
require_once('interfacedatos.class.php'); 
$interface = new InterfaceDatos();


 //echo '<br>'.$_POST["numOrden"].'<br>';
 //echo '<br>'.$_POST["enc"].'<br>';
 //echo '<br>'.$_POST["det"].'<br>';

    mysql_query("SET NAMES 'utf8'");

    $sql = "SELECT numeroPedido FROM PedidoLeBon WHERE numeroPedido = '".$_POST["numOrden"]."'";
    $resultado = $bd->ConsultarVista($sql);

    if($resultado[0]['numeroPedido'])
    {
    	$sql = "DELETE t1, t2 
    				FROM  PedidoLeBon t1
    					left join PedidoLeBonDetalle t2 
    					ON t1.numeroPedido = t2.numeroPedido
    				WHERE t1.numeroPedido = '".$_POST["numOrden"]."'";
    	$resultado = $bd->ejecutar($sql);
    }

    $sql = $_POST["enc"];
    $resultado = $bd->ejecutar($sql);
    //echo $sql;

    if($resultado) 
    {
        $sql = $_POST["det"];
        $resultado = $bd->ejecutar($sql);
        //echo $sql;

        if($resultado) 
        {
            $encabezado = array();
            $detalle = array();
            
            echo "Orden de Compra No. " . $_POST["numOrden"] .", guardada con exito.<br>";
            
            $sql = "SELECT numeroPedido,fechaPedido,observacionPedido
                    FROM PedidoLeBon 
                    WHERE numeroPedido = '".$_POST["numOrden"]."'";
            $datosEncabezado = $bd->ConsultarVista($sql);

            if(!empty($datosEncabezado))
            {
                $encabezado[0]["Documento_idDocumento"] = 137;
                $encabezado[0]["DocumentoConcepto_idDocumentoConcepto"] = 7;
                $encabezado[0]["fechaElaboracionMovimiento"] = $datosEncabezado[0]["fechaPedido"];
                $encabezado[0]["numeroMovimiento"] = $datosEncabezado[0]["numeroPedido"];
                $encabezado[0]["Tercero_idTercero"] = 980;
                $encabezado[0]["Tercero_idPrincipal"] = 980;
                $encabezado[0]["Periodo_idPeriodo"] = 117;
                $encabezado[0]["tipoMovimiento"] = 'NORMAL';
                $encabezado[0]["observacionMovimiento"] = $datosEncabezado[0]["observacionPedido"];

                $sql = "SELECT numeroPedido, Producto_idProducto, referenciaProducto, referenciaPedidoDetalle, codigoBarrasProducto, cantidadPedidoDetalle, precioPedidoDetalle 
                        FROM PedidoLeBonDetalle PLD
                        LEFT JOIN ProductoTercero PT
                        ON PLD.referenciaPedidoDetalle = PT.pluProductoTercero
                        left join Producto P
                        on PT.Producto_idProducto = P.idProducto
                        WHERE numeroPedido = '".$_POST["numOrden"]."'";
                $datosDetalle = $bd->ConsultarVista($sql);
                //print_r($datosDetalle);
                
                if(!empty($datosDetalle))
                {
                    $cont = count($datosDetalle);


                    for($i = 0; $i < $cont; $i++)
                    {
                        $detalle[$i]["numeroMovimiento"] = $_POST["numOrden"];
                        $detalle[$i]["Producto_idProducto"] = $datosDetalle[$i]["Producto_idProducto"];
                        $detalle[$i]["referenciaProducto"] = $datosDetalle[$i]["referenciaProducto"];
                        $detalle[$i]["codigoBarrasProducto"] = $datosDetalle[$i]["codigoBarrasProducto"];
                        $detalle[$i]["cantidadMovimientoDetalle"] = $datosDetalle[$i]["cantidadPedidoDetalle"];
                        $detalle[$i]["precioListaMovimientoDetalle"] = $datosDetalle[$i]["precioPedidoDetalle"];
                        $detalle[$i]["valorBrutoMovimientoDetalle"] = $datosDetalle[$i]["precioPedidoDetalle"];
                        $detalle[$i]["Documento_idDocumento"] = 137;
                    }

                   // print_r($detalle);
            
                    $resolved = $interface->llenarPropiedadesMovimiento($encabezado, $detalle);
                    if(empty($resolved))
                    {
                        $sql = "UPDATE PedidoLeBon SET estadoPedido = 'EXPORTADO' WHERE numeroPedido = '".$_POST["numOrden"]."'";
                    }
                    else
                    {
                        $errores = "";
                        for($i = 0; $i < count($resolved); $i++)
                        {
                            $errores .= $resolved[$i]['error'].","; 
                        }
                        
                        $sql = "UPDATE PedidoLeBon SET estadoPedido = 'ERROR', erroresPedido = '$errores' WHERE numeroPedido = '".$_POST["numOrden"]."'";
                    }
                    $bd->ejecutar($sql);
                    
                    //echo $proceso;
                    //print_r($proceso);
                }
                else
                {
                    //cambiar estado del pedido lebon a error y agregarle a las observaciones que las referencias no existen
                    echo '<br>Error, no hay un detalle del pedido<br>';
                    $sql = "UPDATE PedidoLeBon SET estadoPedido = 'ERROR', erroresPedido = 'No se encontro el detalle del pedido' WHERE numeroPedido = '".$_POST["numOrden"]."'";
                    $bd->ejecutar($sql);
                }
            }
            else
            {
                $sql = "UPDATE PedidoLeBon SET estadoPedido = 'ERROR', erroresPedido = 'No se encontro el encabezado del pedido' WHERE numeroPedido = '".$_POST["numOrden"]."'";
                $bd->ejecutar($sql);
                echo '<br>Error, no se encontro el encabezado del pedido<br>';
            }
        }
        else
        {
            $sql = "UPDATE PedidoLeBon SET estadoPedido = 'ERROR', erroresPedido = 'No se puedo grabar el detalle' WHERE numeroPedido = '".$_POST["numOrden"]."'";
            $bd->ejecutar($sql);
            echo '<br>'."Error de BD, no se pudo insertar la base de datos\n ". " Error MySQL: " . mysql_error();
        }  
    }
    else
    {
        echo '<br>'."Error de BD, no se pudo insertar la base de datos\n ". " Error MySQL: " . mysql_error();  
    }
    return true;
?>