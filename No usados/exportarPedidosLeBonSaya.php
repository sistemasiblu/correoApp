<?php 
$totalDet = (isset($datos[0]["idProduccionEntrega"]) ? count($datos) : 0);

// si no se encuentra, mostramos un mensaje de error y retornamos
if ($totalDet == 0 or $datos[0]["Producto_idMaterial"] == 0)
{
    //echo '<script> alert("Ocurrio un error al generar el movimiento para la carga del inventario de la importacion, no se encontro la liquidacion de importacion id: '.$idImportacion.' "); 
    return;
}

// consultamos los par�metros de importacion para tener los datos necesarios para el documento
require_once('../clases/parametrosproduccion.class.php');
$parametros = new ParametrosProduccion();

require_once('../clases/interfacedatos.class.php');
$interfacedatos = new InterfaceDatos();

$posDet = 0;
$errores = '';

// debemos llenar 2 array con los datos de encabezado y de detalle  para enviarlos a otro proceso que los completa y los 
// carga al modulo ocmercial y posteriormente a los demas modulos que este afecte   

$reg = 0;
$encabezado = array();
$detalle = array();
$posEnc = 0;
$posDet = 0;
 
while ($reg < $totalDet)
{
    $DocumentoAnterior = $datos[$posDet]["Documento_idDescargueMaterialesRemision"];
    $ConceptoAnterior = $datos[$posDet]["DocumentoConcepto_idDescargueMaterialesRemision"];
    $NumeroMovimientoAnterior = $datos[0]["numeroProduccionEntrega"].$DocumentoAnterior;
     
    $encabezado[$posEnc]["Documento_idDocumento"] = $datos[$posDet]["Documento_idDescargueMaterialesRemision"];
    $encabezado[$posEnc]["DocumentoConcepto_idDocumentoConcepto"] = $datos[$posDet]["DocumentoConcepto_idDescargueMaterialesRemision"];
    $encabezado[$posEnc]["tipoMovimiento"] = 'NORMAL';
    $encabezado[$posEnc]["numeroMovimiento"] = $datos[0]["numeroProduccionEntrega"].$DocumentoAnterior;
    $encabezado[$posEnc]["tipoReferenciaInternoMovimiento"] = 7;  // Remision
    $encabezado[$posEnc]["numeroReferenciaInternoMovimiento"] = $datos[0]["numeroProduccionEntrega"];
    $encabezado[$posEnc]["tipoReferenciaExternoMovimiento"] = '';
    $encabezado[$posEnc]["numeroReferenciaExternoMovimiento"] = $datos[0]["observacionProduccionEntrega"];
    $encabezado[$posEnc]["fechaElaboracionMovimiento"] = $datos[0]["fechaElaboracionProduccionEntrega"];
    $encabezado[$posEnc]["ProduccionEntrega_idProduccionEntrega"] = $datos[0]["idProduccionEntrega"];
    $encabezado[$posEnc]["Tercero_idTercero"] = $datos[0]["Tercero_idTercero"];
    $encabezado[$posEnc]["idMovimiento"] = 0;
    $encabezado[$posEnc]["eanTercero"] = '';
    $encabezado[$posEnc]["SegLogin_idUsuarioCrea"] = $_SESSION['SesionUsuario'];
    $encabezado[$posEnc]["codigoDocumento"] = '';
    $encabezado[$posEnc]["codigoConceptoDocumento"] = '';
    $encabezado[$posEnc]["CentroProduccion_idCentroProduccion"] = $datos[0]["CentroProduccion_idCentroProduccion"];
    $encabezado[$posEnc]["OrdenProduccion_idOrdenProduccion"] = $datos[0]["OrdenProduccion_idOrdenProduccion"];
     
    while ($reg < $totalDet and $DocumentoAnterior == $datos[$posDet]["Documento_idDescargueMaterialesRemision"] 
            and  $ConceptoAnterior == $datos[$posDet]["DocumentoConcepto_idDescargueMaterialesRemision"])
    {
        $detalle[$posDet]["numeroMovimiento"] =$encabezado[$posEnc]["numeroMovimiento"];
        $detalle[$posDet]["Documento_idDocumento"] =$encabezado[$posEnc]["Documento_idDocumento"];
        // llenamos la bodega de los parametros de importaciones
        $detalle[$posDet]["Bodega_idBodegaOrigen"] = $datos[$posDet]["Bodega_idDescargueMaterialesRemision"];

        // llenamos los datos del producto y cantidades de la Remision (Produccion Entrega)
        $detalle[$posDet]["Producto_idProducto"] = $datos[$posDet]["Producto_idMaterial"];
        $detalle[$posDet]["cantidadMovimientoDetalle"] = $datos[$posDet]["cantidadProduccionEntregaMaterial"];
        $detalle[$posDet]["precioListaMovimientoDetalle"] = $datos[$posDet]["costoUnitarioProduccionEntregaMaterial"];
        $detalle[$posDet]["valorBrutoMovimientoDetalle"] = $datos[$posDet]["costoUnitarioProduccionEntregaMaterial"];
        $detalle[$posDet]["valorNetoMovimientoDetalle"] = $datos[$posDet]["costoUnitarioProduccionEntregaMaterial"];
        $detalle[$posDet]["porcentajeDescuentoMovimientoDetalle"] = 0;
        $detalle[$posDet]["valorDescuentoMovimientoDetalle"] = 0;
        $detalle[$posDet]["eanProducto"] = '';

//                echo "<script> console.log('Este es el numero de movimiento (".$detalle[$posDet]["numeroMovimiento"].")'); </script>";
        $posDet++;               
        $reg++;
    }        
     
    $posEnc++;
}

// luego de que tenemos la matriz de encabezado y detalle llenos, las enviamos al proceso de importacion de movimientos comerciales
// para que las valide e importe al sistema, para esto recorremos cada orden de compra importada para llenar el encabezado en variables
// normales y el detalle correspondiente en un array
$retorno = $interfacedatos->llenarPropiedadesMovimiento($encabezado, $detalle, 'interface');
 

 
return $retorno;
?>