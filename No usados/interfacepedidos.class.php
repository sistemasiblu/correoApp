<?php

    /**
     * @author Yilmar Jesus Martinez Mosquera
     * @copyright Avance Integral S.A.S - 2015
     * @license software Comercial
     * @version 4.0.1.10
     * @link http://www.avanceintegral.com
     * Fecha última modificacion: 2015-10-27
     * Estado: Cerrado
     */
    class InterfacePedidos 
    {

        function InterfacePedidos() {
           

            /*Incluimos el fichero de la clase Db*/ 
            require_once('../clases/db.class.php'); 
            /*Incluimos el fichero de la clase Conf*/ 
            require_once('confIBLU.class.php');
            
        }

   

        function calcularvencimiento($fecha, $dias) {
            $aFinMes = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

            $anio = substr($fecha, 0, 4);
            $mes = substr($fecha, 5, 2);
            $dia = substr($fecha, 8, 2);

            $diaspendientes = $dias;

            while ($diaspendientes > 0) {
                // verificamos cuantos dias tiene el mes actual
                $ultimo_dia = $aFinMes[$mes - 1];

                // cualculamos cuantos dias faltan para terminar el mes actual
                $resto = $ultimo_dia - $dia;

                // si los dias que faltan (resto) para terminar el mes son mas que los pendientes
                // le restamos al pendiente los dias de resto
                if ($resto < $diaspendientes) {
                    $dia = 0;
                    $diaspendientes = $diaspendientes - $resto;
                    $mes++;

                    if ($mes == 13) {
                        $anio++;
                        $mes = 1;
                    }
                } else {
                    $dia = $dia + $diaspendientes;
                    $diaspendientes = 0;
                }
            }

            // con el ANIO, el MES y el DIA, armamos el formato de la fecha
            $fecha_final = $anio . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-' . str_pad($dia, 2, '0', STR_PAD_LEFT);

            return $fecha_final;
        }


        function llenarPropiedadesMovimiento($encabezado, $detalle, $origen = 'interface', $listaprecio = '', $listapreciotercero = '', $mediopago = array()) 

        {

            $ruta = dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR;

            // instanciamos la clase movimiento y llenamos sus propiedades para que ella se encargue de importar los datos
            require_once '../clases/movimiento.class.php';
            $movimiento = new Movimiento();

            $retorno = array();
            // contamos los registros del encabezado
            $totalreg = (isset($encabezado[0]["numeroMovimiento"]) ? count($encabezado) : 0);

            //print_r($encabezado);
            //echo '<br>';
            //                 print_r($encabezado);
            //print_r($detalle);
            //                 exit();
            //echo '<br>';
            $nuevoserrores = $this->validarMovimiento($encabezado, $detalle, $listaprecio, $listapreciotercero, $origen);
            // print_r($nuevoserrores);
            //exit();
            ////
            if (!isset($nuevoserrores[0]["error"]) or $nuevoserrores[0]["error"] == '') {
                //                    echo "<br>entra1<br>";
                //                    return;

                echo 'entra1';
                for ($i = 0; $i < $totalreg; $i++) {
                    //                    echo "<br> entra for encabezado<br>";
                    //echo " entra if isset ";
                    // para cada registro, ejecutamos el constructor de la clase para que inicialice todas las variables y arrys

                    $movimiento->Movimiento();
                    //echo 'registros de detalle '.count($movimiento->idMovimientoDetalle)."<br><br>";
                    $movimiento->idMovimiento = (isset($encabezado[$i]["idMovimiento"]) ? $encabezado[$i]["idMovimiento"] : 0);

                    $movimiento->Documento_idDocumento = (isset($encabezado[$i]["Documento_idDocumento"]) ? $encabezado[$i]["Documento_idDocumento"] : 0);
                    $movimiento->DocumentoConcepto_idDocumentoConcepto = (isset($encabezado[$i]["DocumentoConcepto_idDocumentoConcepto"]) ? $encabezado[$i]["DocumentoConcepto_idDocumentoConcepto"] : 0);

                    $movimiento->prefijoMovimiento = (isset($encabezado[$i]["prefijoMovimiento"]) ? $encabezado[$i]["prefijoMovimiento"] : '');
                    $movimiento->sufijoMovimiento = (isset($encabezado[$i]["sufijoMovimiento"]) ? $encabezado[$i]["sufijoMovimiento"] : '');
                    $movimiento->fechaElaboracionMovimiento = (isset($encabezado[$i]["fechaElaboracionMovimiento"]) ? $encabezado[$i]["fechaElaboracionMovimiento"] : date("Y-m-d"));
                    $movimiento->horaElaboracionMovimiento = (isset($encabezado[$i]["horaElaboracionMovimiento"]) ? $encabezado[$i]["horaElaboracionMovimiento"] : date("H:i:s"));

                    // obtenemos el período contable segun la fecha de elaboracion del documento
                    $sql = "Select idPeriodo
                            From Periodo
                            Where fechaInicialPeriodo <= '" . $movimiento->fechaElaboracionMovimiento .
                            "' and fechaFinalPeriodo >= '" . $movimiento->fechaElaboracionMovimiento . "'";
                    $bd = Db::getInstance();
                    $dato = $bd->ejecutar($sql); 
                    $movimiento->Periodo_idPeriodo = (isset($dato[0]["idPeriodo"]) ? $dato[0]["idPeriodo"] : 0);


                    $sql= "Select  FormaPago_idFormaPago
                            From    Tercero
                            Where   idTercero = " . $encabezado[$i]["Tercero_idTercero"];
                    $bd = Db::getInstance();
                    $dato = $bd->ejecutar($sql);
                    $movimiento->FormaPago_idFormaPago = (isset($dato[0]["FormaPago_idFormaPago"]) ? $dato[0]["FormaPago_idFormaPago"] : 0 );
                    
                    $sql= "Select  afectaWMSDocumento, estadoWMSDocumento
                            From    Documento
                            Where   idDocumento = " . $movimiento->Documento_idDocumento;
                    $bd = Db::getInstance();
                    $dato = $bd->ejecutar($sql);
                    $movimiento->estadoWMSMovimiento = ($dato[0]["afectaWMSDocumento"] == 'SI' ? $dato[0]["estadoWMSDocumento"] : 'CERRADO');

                    // con el id de la forma de pago, buscamos cuantos días de pago tiene
                    $sql= "Select  diasFormaPago
                            From    FormaPago
                            Where   idFormaPago = " . $movimiento->FormaPago_idFormaPago;
                    $bd = Db::getInstance();
                    $dato = $bd->ejecutar($sql);
                    $dias = (isset($dato[0]["diasFormaPago"]) ? $dato[0]["diasFormaPago"] : 0);
            echo 'ahi va';
                    // calculamos la fecha de vencimiento según la fecha de elaboracion y la forma de pago del documento
                    $movimiento->fechaVencimientoMovimiento = $this->calcularvencimiento($movimiento->fechaElaboracionMovimiento, $dias);
                    $movimiento->fechaMinimaMovimiento = (isset($encabezado[$i]["fechaMinimaMovimiento"]) ? $encabezado[$i]["fechaMinimaMovimiento"] : '');
                    $movimiento->fechaMaximaMovimiento = (isset($encabezado[$i]["fechaMaximaMovimiento"]) ? $encabezado[$i]["fechaMaximaMovimiento"] : '');
                    $movimiento->fechaSolicitudMovimiento = (isset($encabezado[$i]["fechaSolicitudMovimiento"]) ? $encabezado[$i]["fechaSolicitudMovimiento"] : '');

                    $movimiento->numeroMovimiento = (isset($encabezado[$i]["numeroMovimiento"]) ? $encabezado[$i]["numeroMovimiento"] : '');
                    $movimiento->Tercero_idTercero = (isset($encabezado[$i]["Tercero_idTercero"]) ? $encabezado[$i]["Tercero_idTercero"] : 0);
                    $movimiento->Tercero_idPrincipal = (isset($encabezado[$i]["Tercero_idPrincipal"]) ? $encabezado[$i]["Tercero_idPrincipal"] : 0);
                    $movimiento->Tercero_idVendedor = (isset($encabezado[$i]["Tercero_idVendedor"]) ? $encabezado[$i]["Tercero_idVendedor"] : 0);
                    $movimiento->CentroCosto_idCentroCosto = (isset($encabezado[$i]["CentroCosto_idCentroCosto"]) ? $encabezado[$i]["CentroCosto_idCentroCosto"] : 0);

                    $movimiento->Tercero_idEntrega = (isset($encabezado[$i]["Tercero_idEntrega"]) ? $encabezado[$i]["Tercero_idEntrega"] : 0);
                    $movimiento->tipoMovimiento = (isset($encabezado[$i]["tipoMovimiento"]) ? $encabezado[$i]["tipoMovimiento"] : 'NORMAL');

                    $movimiento->tipoReferenciaInternoMovimiento = (isset($encabezado[$i]["tipoReferenciaInternoMovimiento"]) ? $encabezado[$i]["tipoReferenciaInternoMovimiento"] : 0);
                    $movimiento->numeroReferenciaInternoMovimiento = (isset($encabezado[$i]["numeroReferenciaInternoMovimiento"]) ? $encabezado[$i]["numeroReferenciaInternoMovimiento"] : '');
                    $movimiento->tipoReferenciaExternoMovimiento = (isset($encabezado[$i]["tipoReferenciaExternoMovimiento"]) ? $encabezado[$i]["tipoReferenciaExternoMovimiento"] : 0);
                    $movimiento->numeroReferenciaExternoMovimiento = (isset($encabezado[$i]["numeroReferenciaExternoMovimiento"]) ? $encabezado[$i]["numeroReferenciaExternoMovimiento"] : '');
                    $movimiento->Importacion_idImportacion = (isset($encabezado[$i]["Importacion_idImportacion"]) ? $encabezado[$i]["Importacion_idImportacion"] : 0);
                    $movimiento->Embarque_idEmbarque = (isset($encabezado[$i]["Embarque_idEmbarque"]) ? $encabezado[$i]["Embarque_idEmbarque"] : 0);


                    $movimiento->Moneda_idMoneda = (isset($encabezado[$i]["Moneda_idMoneda"]) ? $encabezado[$i]["Moneda_idMoneda"] : 0);
                    $movimiento->tasaCambioMovimiento = (!empty($encabezado[$i]["tasaCambioMovimiento"]) ? $encabezado[$i]["tasaCambioMovimiento"] : 0);
                    $movimiento->factorMovimiento = (!empty($encabezado[$i]["factorMovimiento"]) ? $encabezado[$i]["factorMovimiento"] : 0);

                    $movimiento->Incoterm_idIncoterm = (isset($encabezado[$i]["Incoterm_idIncoterm"]) ? $encabezado[$i]["Incoterm_idIncoterm"] : 0);
                    $movimiento->observacionMovimiento = (isset($encabezado[$i]["observacionMovimiento"]) ? $encabezado[$i]["observacionMovimiento"] : '');

                    $movimiento->totalUnidadesMovimiento = 0;
                    $movimiento->valorFleteMovimiento = (!empty($encabezado[$i]["valorFleteMovimiento"]) ? $encabezado[$i]["valorFleteMovimiento"] : 0);
                    $movimiento->valorSeguroMovimiento = (!empty($encabezado[$i]["valorSeguroMovimiento"]) ? $encabezado[$i]["valorSeguroMovimiento"] : 0);
                    $movimiento->valorAcarreoMovimiento = (!empty($encabezado[$i]["valorAcarreoMovimiento"]) ? $encabezado[$i]["valorAcarreoMovimiento"] : 0);

                    $movimiento->estadoMovimiento = 'ACTIVO';


                    $movimiento->SegLogin_idUsuarioCrea = (isset($encabezado[$i]["SegLogin_idUsuarioCrea"]) ? $encabezado[$i]["SegLogin_idUsuarioCrea"] : '');
                    $movimiento->impresoMovimiento = (isset($encabezado[$i]["impresoMovimiento"]) ? $encabezado[$i]["impresoMovimiento"] : '');
                    $movimiento->SegLogin_idUsuarioAnula = (isset($encabezado[$i]["SegLogin_idUsuarioAnula"]) ? $encabezado[$i]["SegLogin_idUsuarioAnula"] : '');
                    $movimiento->fechaAnuladoMovimiento = (isset($encabezado[$i]["fechaAnuladoMovimiento"]) ? $encabezado[$i]["fechaAnuladoMovimiento"] : '');
                    $movimiento->LiquidacionNomina_idLiquidacionNomina = (isset($encabezado[$i]["LiquidacionNomina_idLiquidacionNomina"]) ? $encabezado[$i]["LiquidacionNomina_idLiquidacionNomina"] : 0);
                    $movimiento->Embarque_idTransito = (isset($encabezado[$i]["Embarque_idTransito"]) ? $encabezado[$i]["Embarque_idTransito"] : 0);
                    $movimiento->MercanciaExtranjera_idMercanciaExtranjera = (isset($encabezado[$i]["MercanciaExtranjera_idMercanciaExtranjera"]) ? $encabezado[$i]["MercanciaExtranjera_idMercanciaExtranjera"] : 0);
                    $movimiento->Nacionalizacion_idNacionalizacion = (isset($encabezado[$i]["Nacionalizacion_idNacionalizacion"]) ? $encabezado[$i]["Nacionalizacion_idNacionalizacion"] : 0);

                    $movimiento->tipoDescuentoMovimiento = (isset($encabezado[$i]["tipoDescuentoMovimiento"]) ? $encabezado[$i]["tipoDescuentoMovimiento"] : 'Porcentaje');
                    $movimiento->nivelDescuentoMovimiento = (isset($encabezado[$i]["nivelDescuentoMovimiento"]) ? $encabezado[$i]["nivelDescuentoMovimiento"] : 'Detalle');
                    $movimiento->CentroProduccion_idCentroProduccion = (isset($encabezado[$i]["CentroProduccion_idCentroProduccion"]) ? $encabezado[$i]["CentroProduccion_idCentroProduccion"] : 0);
                    $movimiento->OrdenProduccion_idOrdenProduccion = (isset($encabezado[$i]["OrdenProduccion_idOrdenProduccion"]) ? $encabezado[$i]["OrdenProduccion_idOrdenProduccion"] : 0);
                    $movimiento->ListaPrecio_idListaPrecio = (isset($encabezado[$i]["ListaPrecio_idListaPrecio"]) ? $encabezado[$i]["ListaPrecio_idListaPrecio"] : (isset($nuevoserrores[0]["ListaPrecio_idListaPrecioDetalle"]) ? $nuevoserrores[0]["ListaPrecio_idListaPrecioDetalle"] : 0));

                    $subtotal = 0;
                    $descuento = 0;
                    $base = 0;
                    $impuesto = 0;
                    $retencion = 0;
                    $reteiva = 0;
                    $totalUnidades = 0;

                    // por cada registro del encabezado, recorremos el detalle para obtener solo los datos del mismo numero de movimiento del encabezado, con estos
                    // llenamos arrays por cada campo
                    $totaldet = (isset($detalle[0]["numeroMovimiento"]) ? count($detalle) : 0);

                    $ids = '';
                    $precios = '';
                    $descuentos = '';
                    $cants = '';
                    $regs = '';
                    $ivas = '';

                    $totalBaseImp = 0;
                    $totalImp = 0;

                    $totalImpoc = 0;
                    $totalIva = 0;
                    $totalImpDep = 0;
                    // llevamos un contador de registros por cada producto del detalle
                    $registroact = 0;

                    for ($j = 0; $j < $totaldet; $j++) {
                        if (isset($encabezado[$i]["Documento_idDocumento"]) and
                                isset($detalle[$j]["Documento_idDocumento"]) and
                                $encabezado[$i]["Documento_idDocumento"] == $detalle[$j]["Documento_idDocumento"]) {

                            if (isset($encabezado[$i]["numeroMovimiento"]) and
                                    isset($detalle[$j]["numeroMovimiento"]) and
                                    $encabezado[$i]["numeroMovimiento"] == $detalle[$j]["numeroMovimiento"]) {


                                $sql= "Select   Impuesto_idImpuesto
                                        From    viewProductoImpuesto
                                        Where   idProducto IN (" . $detalle[$j]["Producto_idProducto"] . ")";
                                $bd = Db::getInstance();
                                $dato = $bd->ejecutar($sql);
                                $idImpuesto = isset($dato[0]['Impuesto_idImpuesto']) ? $dato[0]['Impuesto_idImpuesto'] : 0;

                                $sql= "Select   Retencion_idRetencion
                                        From    viewProductoRetencion
                                        Where   idProducto IN (" . $detalle[$j]["Producto_idProducto"] . ")";
                                $bd = Db::getInstance();
                                $dato = $bd->ejecutar($sql);
                                $idRetencion = isset($dato[0]['Retencion_idRetencion']) ? $dato[0]['Retencion_idRetencion'] : 0;

                                $movimiento->idMovimientoDetalle[$registroact] = 0;
                                $movimiento->Bodega_idBodegaOrigen[$registroact] = (isset($detalle[$j]["Bodega_idBodegaOrigen"]) ? $detalle[$j]["Bodega_idBodegaOrigen"] : 0);
                                $movimiento->Bodega_idBodegaDestino[$registroact] = (isset($detalle[$j]["Bodega_idBodegaDestino"]) ? $detalle[$j]["Bodega_idBodegaDestino"] : 0);
                                $movimiento->ProductoSerie_idProductoSerie[$registroact] = (isset($detalle[$j]["ProductoSerie_idProductoSerie"]) ? $detalle[$j]["ProductoSerie_idProductoSerie"] : 0);
                                $movimiento->numeroProductoSerie[$registroact] = (isset($detalle[$j]["numeroProductoSerie"]) ? $detalle[$j]["numeroProductoSerie"] : 0);
                                $movimiento->numeroLoteMovimientoDetalle[$registroact] = (isset($detalle[$j]["numeroLoteMovimientoDetalle"]) ? $detalle[$j]["numeroLoteMovimientoDetalle"] : '');
                                $movimiento->Movimiento_idDocumentoRef[$registroact] = (isset($detalle[$j]["Movimiento_idDocumentoRef"]) ? $detalle[$j]["Movimiento_idDocumentoRef"] : 0);
                                $movimiento->Poliza_idPoliza[$registroact] = (isset($detalle[$j]["Poliza_idPoliza"]) ? $detalle[$j]["Poliza_idPoliza"] : 0);
                                $movimiento->Producto_idProducto[$registroact] = (isset($detalle[$j]["Producto_idProducto"]) ? $detalle[$j]["Producto_idProducto"] : 0);
                                $movimiento->Producto_idSustitutoPrincipal[$registroact] = (isset($detalle[$j]["Producto_idProducto"]) ? $detalle[$j]["Producto_idProducto"] : 0);
                                $movimiento->Tercero_idAlmacen[$registroact] = (isset($detalle[$j]["Tercero_idAlmacen"]) ? $detalle[$j]["Tercero_idAlmacen"] : 0);
                                $movimiento->cantidadMovimientoDetalle[$registroact] = (isset($detalle[$j]["cantidadMovimientoDetalle"]) ? $detalle[$j]["cantidadMovimientoDetalle"] : 0);
                                $movimiento->ListaPrecio_idListaPrecioDetalle[$registroact] = (isset($encabezado[$i]["ListaPrecio_idListaPrecio"]) ? $encabezado[$i]["ListaPrecio_idListaPrecio"] : (isset($nuevoserrores[$j]["ListaPrecio_idListaPrecioDetalle"]) ? $nuevoserrores[$j]["ListaPrecio_idListaPrecioDetalle"] : 0));
                                $movimiento->precioListaMovimientoDetalle[$registroact] = (isset($nuevoserrores[$j]["precioListaMovimientoDetalle"]) ? $nuevoserrores[$j]["precioListaMovimientoDetalle"] : (isset($detalle[$j]["precioListaMovimientoDetalle"]) ? $detalle[$j]["precioListaMovimientoDetalle"] : 0));
                                $movimiento->valorBrutoMovimientoDetalle[$registroact] = (isset($nuevoserrores[$j]["valorBrutoMovimientoDetalle"]) ? $nuevoserrores[$j]["valorBrutoMovimientoDetalle"] : (isset($detalle[$j]["valorBrutoMovimientoDetalle"]) ? $detalle[$j]["valorBrutoMovimientoDetalle"] : 0));
                                $movimiento->BodegaUbicacion_idBodegaUbicacionOrigen = (isset($detalle[$j]["BodegaUbicacion_idBodegaUbicacionOrigen"]) ? $detalle[$j]["BodegaUbicacion_idBodegaUbicacionOrigen"] : 0);
                                $movimiento->BodegaUbicacion_idBodegaUbicacionDestino[$registroact] = (isset($detalle[$j]["BodegaUbicacion_idBodegaUbicacionDestino"]) ? $detalle[$j]["BodegaUbicacion_idBodegaUbicacionDestino"] : 0);
                                $movimiento->Embalaje_idEmbalaje[$registroact] = (isset($detalle[$j]["Embalaje_idEmbalaje"]) ? $detalle[$j]["Embalaje_idEmbalaje"] : 0);
                                $movimiento->CentroCosto_idCentroCostoDetalle[$registroact] = (isset($detalle[$j]["CentroCosto_idCentroCostoDetalle"]) ? $detalle[$j]["CentroCosto_idCentroCostoDetalle"] : 0);

                                // descuento comercial
                                $movimiento->porcentajeDescuentoMovimientoDetalle[$registroact] = (isset($detalle[$j]["porcentajeDescuentoMovimientoDetalle"]) ? $detalle[$j]["porcentajeDescuentoMovimientoDetalle"] : 0);
                                $movimiento->valorDescuentoMovimientoDetalle[$registroact] = (isset($nuevoserrores[$j]["valorBrutoMovimientoDetalle"]) ? $nuevoserrores[$j]["valorBrutoMovimientoDetalle"] : (isset($detalle[$j]["valorBrutoMovimientoDetalle"]) ? $detalle[$j]["valorBrutoMovimientoDetalle"] : 0)) *
                                        (isset($detalle[$j]["porcentajeDescuentoMovimientoDetalle"]) ? $detalle[$j]["porcentajeDescuentoMovimientoDetalle"] : 0) / 100;
                                $movimiento->valorBaseMovimientoDetalle[$registroact] = (isset($nuevoserrores[$j]["valorBrutoMovimientoDetalle"]) ? $nuevoserrores[$j]["valorBrutoMovimientoDetalle"] : (isset($detalle[$j]["valorBrutoMovimientoDetalle"]) ? $detalle[$j]["valorBrutoMovimientoDetalle"] : 0)) -
                                        (isset($detalle[$j]["valorDescuentoMovimientoDetalle"]) ? $detalle[$j]["valorDescuentoMovimientoDetalle"] : 0);

                                // campos de descuento financiero para las NIIF
                                $movimiento->porcentajeDescuentoFinancieroMovimientoDetalle[$registroact] = (isset($detalle[$j]["porcentajeDescuentoFinancieroMovimientoDetalle"]) ? $detalle[$j]["porcentajeDescuentoFinancieroMovimientoDetalle"] : 0);
                                $movimiento->valorDescuentoFinancieroMovimientoDetalle[$registroact] = (isset($detalle[$j]["valorDescuentoFinancieroMovimientoDetalle"]) ? $detalle[$j]["valorDescuentoFinancieroMovimientoDetalle"] : 0);
                                $movimiento->valorBaseNIIFMovimientoDetalle[$registroact] = $movimiento->valorBrutoMovimientoDetalle[$registroact] -
                                $movimiento->valorDescuentoMovimientoDetalle[$registroact] -
                                $movimiento->valorDescuentoFinancieroMovimientoDetalle[$registroact];

                                // llenamos los id de iva y retencion antes consultados
                                $movimiento->Impuesto_idIva[$registroact] = (isset($detalle[$j]["Impuesto_idIva"]) ? $detalle[$j]["Impuesto_idIva"] : 0);
                                $movimiento->Impuesto_idReteFuente[$registroact] = (isset($detalle[$j]["Impuesto_idReteFuente"]) ? $detalle[$j]["Impuesto_idReteFuente"] : 0);

                                $movimiento->Impuesto_idReteCree[$registroact] = 0;

                                $movimiento->volumenTotalMovimientoDetalle[$registroact] = (isset($detalle[$j]["volumenTotalMovimientoDetalle"]) ? $detalle[$j]["volumenTotalMovimientoDetalle"] : 0);
                                $movimiento->pesoTotalMovimientoDetalle[$registroact] = (isset($detalle[$j]["pesoTotalMovimientoDetalle"]) ? $detalle[$j]["pesoTotalMovimientoDetalle"] : 0);
                                $movimiento->numeroCajasMovimientoDetalle[$registroact] = (isset($detalle[$j]["numeroCajasMovimientoDetalle"]) ? $detalle[$j]["numeroCajasMovimientoDetalle"] : 0);

                                $movimiento->precioVentaPublicoMovimientoDetalle[$registroact] = (isset($detalle[$j]["precioVentaPublicoMovimientoDetalle"]) ? $detalle[$j]["precioVentaPublicoMovimientoDetalle"] : 0);
                                $movimiento->margenUtilidadMovimientoDetalle[$registroact] = (isset($detalle[$j]["margenUtilidadMovimientoDetalle"]) ? $detalle[$j]["margenUtilidadMovimientoDetalle"] : 0);

                                // datos de marcacion de productos
                                $movimiento->EtiquetaProducto_idEtiquetaProducto[$registroact] = (isset($detalle[$j]["EtiquetaProducto_idEtiquetaProducto"]) ? $detalle[$j]["EtiquetaProducto_idEtiquetaProducto"] : 0);
                                $movimiento->etiquetaSeccionMovimientoDetalle[$registroact] = (isset($detalle[$j]["etiquetaSeccionMovimientoDetalle"]) ? $detalle[$j]["etiquetaSeccionMovimientoDetalle"] : '');
                                $movimiento->etiquetaClasificacionMovimientoDetalle[$registroact] = (isset($detalle[$j]["etiquetaClasificacionMovimientoDetalle"]) ? $detalle[$j]["etiquetaClasificacionMovimientoDetalle"] : '');
                                $movimiento->etiquetaFechaMovimientoDetalle[$registroact] = (isset($detalle[$j]["etiquetaFechaMovimientoDetalle"]) ? $detalle[$j]["etiquetaFechaMovimientoDetalle"] : '');
                                $movimiento->etiquetaPrecioVentaNormalMovimientoDetalle[$registroact] = (isset($detalle[$j]["etiquetaPrecioVentaNormalMovimientoDetalle"]) ? $detalle[$j]["etiquetaPrecioVentaNormalMovimientoDetalle"] : '');
                                $movimiento->etiquetaPrecioVentaOfertaMovimientoDetalle[$registroact] = (isset($detalle[$j]["etiquetaPrecioVentaOfertaMovimientoDetalle"]) ? $detalle[$j]["etiquetaPrecioVentaOfertaMovimientoDetalle"] : '');
                                $movimiento->etiquetaLugarExhibicionMovimientoDetalle[$registroact] = (isset($detalle[$j]["etiquetaLugarExhibicionMovimientoDetalle"]) ? $detalle[$j]["etiquetaLugarExhibicionMovimientoDetalle"] : '');
                                $movimiento->etiquetaDescripcion1MovimientoDetalle[$registroact] = (isset($detalle[$j]["etiquetaDescripcion1MovimientoDetalle"]) ? $detalle[$j]["etiquetaDescripcion1MovimientoDetalle"] : '');
                                $movimiento->etiquetaDescripcion2MovimientoDetalle[$registroact] = (isset($detalle[$j]["etiquetaDescripcion2MovimientoDetalle"]) ? $detalle[$j]["etiquetaDescripcion2MovimientoDetalle"] : '');
                                $movimiento->etiquetaDescripcion3MovimientoDetalle[$registroact] = (isset($detalle[$j]["etiquetaDescripcion3MovimientoDetalle"]) ? $detalle[$j]["etiquetaDescripcion3MovimientoDetalle"] : '');
                                $movimiento->etiquetaReferenciaClienteMovimientoDetalle[$registroact] = (isset($detalle[$j]["etiquetaReferenciaClienteMovimientoDetalle"]) ? $detalle[$j]["etiquetaReferenciaClienteMovimientoDetalle"] : '');
                                $movimiento->Lote_idLote[$registroact] = 0;

                                // inicializamos los impuestos en cero
                                $movimiento->valorIvaMovimientoDetalle[$registroact] = 0;
                                $movimiento->valorImpoconsumoMovimientoDetalle[$registroact] = 0;
                                $movimiento->valorImpDeporteMovimientoDetalle[$registroact] = 0;
                                $movimiento->valorReteCreeMovimientoDetalle[$registroact] = 0;

                                // inicializamos las retenciones en cero
                                $movimiento->valorReteIcaMovimientoDetalle[$registroact] = 0;
                                $movimiento->valorReteFuenteMovimientoDetalle[$registroact] = 0;
                                $movimiento->valorReteIvaMovimientoDetalle[$registroact] = 0;
                                //$movimiento->valorReteOtrosMovimientoDetalle[$registroact] = 0;


                                $movimiento->valorNetoMovimientoDetalle[$registroact] = $movimiento->valorBaseMovimientoDetalle[$registroact];
                                $movimiento->valorTotalMovimientoDetalle[$registroact] = $movimiento->valorBaseMovimientoDetalle[$registroact] *
                                $movimiento->cantidadMovimientoDetalle[$registroact];
                                $movimiento->observacionMovimientoDetalle[$registroact] = (isset($detalle[$j]["observacionMovimientoDetalle"]) ? $detalle[$j]["observacionMovimientoDetalle"] : '');

                                // luego de tener llenas las matrices, consultamos los impuestos y retenciones
                                //
                                $impuestos = $movimiento->consultarimpuestos($encabezado[$i]["Tercero_idTercero"], $encabezado[$i]["Documento_idDocumento"], $encabezado[$i]["DocumentoConcepto_idDocumentoConcepto"], $movimiento->Producto_idProducto[$registroact], $movimiento->cantidadMovimientoDetalle[$registroact], $movimiento->precioListaMovimientoDetalle[$registroact], $registroact, $movimiento->porcentajeDescuentoMovimientoDetalle[$registroact], $movimiento->fechaElaboracionMovimiento);

                                //print_r($impuestos);
                                // sumamos los impuestos para enviar al calculo de las retenciones la base de impuestos
                                // para esto recorremos el array de impuestos y aplicamos una suma
                                $totalregimp = (isset($impuestos[0]["Producto_idProducto"]) ? count($impuestos) : 0 );

                                if (isset($impuestos[0]["Producto_idProducto"])) {
                                    //echo " entra if isset 3 ";
                                    $totalBaseImp += $impuestos[0]["valorBaseMovimientoImpuesto"] * $impuestos[0]["cantidadMovimientoDetalle"];
                                    $totalImp += $impuestos[0]["valorUnitarioMovimientoImpuesto"] * $impuestos[0]["cantidadMovimientoDetalle"];

                                    // cada impuesto que recorremos, lo vamos acumulando en el campo correspondiente (segun el tipoImpuesto) y en el producto correspondiente
                                    // (segun el registro del array de impuestos)
                                    switch ($impuestos[0]["tipoImpuesto"]) {
                                        case 'valorImpoconsumoMovimientoDetalle' :
                                            $movimiento->valorImpoconsumoMovimientoDetalle[$registroact] += $impuestos[0]["valorUnitarioMovimientoImpuesto"];
                                            $totalImpoc += $impuestos[0]["valorUnitarioMovimientoImpuesto"] * $impuestos[0]["cantidadMovimientoDetalle"];
                                            break;
                                        case 'valorIvaMovimientoDetalle' :
                                            $movimiento->valorIvaMovimientoDetalle[$registroact] += $impuestos[0]["valorUnitarioMovimientoImpuesto"];
                                            $movimiento->valorBrutoMovimientoDetalle[$registroact] = $impuestos[0]["valorBrutoMovimientoImpuesto"];
                                            $movimiento->valorBaseMovimientoDetalle[$registroact] = $impuestos[0]["valorBaseMovimientoImpuesto"];
                                            $movimiento->Impuesto_idIva[$registroact] += $impuestos[0]["Impuesto_idImpuesto"];
                                            $totalIva += $impuestos[0]["valorUnitarioMovimientoImpuesto"] * $impuestos[0]["cantidadMovimientoDetalle"];
                                            break;
                                        case 'valorImpDeporteMovimientoDetalle' :
                                            $movimiento->valorImpDeporteMovimientoDetalle[$registroact] += $impuestos[0]["valorUnitarioMovimientoImpuesto"];
                                            $totalImpDep += $impuestos[0]["valorUnitarioMovimientoImpuesto"] * $impuestos[0]["cantidadMovimientoDetalle"];
                                            break;
                                    }

                                    $ids .= $movimiento->Producto_idProducto[$registroact] . ',';
                                    $precios .= $movimiento->valorBrutoMovimientoDetalle[$registroact] . ',';
                                    $descuentos .= $movimiento->porcentajeDescuentoMovimientoDetalle[$registroact] . ',';
                                    $cants .= $movimiento->cantidadMovimientoDetalle[$registroact] . ',';
                                    $regs .= $registroact . ',';

                                    $ivas .= ($movimiento->valorIvaMovimientoDetalle[$registroact] * $movimiento->cantidadMovimientoDetalle[$registroact]) . ',';


                                    $movimiento->valorBaseMovimientoDetalle[$registroact] = $movimiento->valorBrutoMovimientoDetalle[$registroact] -
                                    $movimiento->valorDescuentoMovimientoDetalle[$registroact];

                                    $movimiento->valorNetoMovimientoDetalle[$registroact] = $movimiento->valorBaseMovimientoDetalle[$registroact] +
                                    $movimiento->valorIvaMovimientoDetalle[$registroact] +
                                    $movimiento->valorImpoconsumoMovimientoDetalle[$registroact] +
                                    $movimiento->valorImpDeporteMovimientoDetalle[$registroact] -
                                    $movimiento->valorReteFuenteMovimientoDetalle[$registroact] -
                                    $movimiento->valorReteIvaMovimientoDetalle[$registroact] -
                                    $movimiento->valorReteCreeMovimientoDetalle[$registroact] -
                                    $movimiento->valorReteIcaMovimientoDetalle[$registroact];

                                    $movimiento->valorTotalMovimientoDetalle[$registroact] = $movimiento->valorNetoMovimientoDetalle[$registroact] *
                                    $movimiento->cantidadMovimientoDetalle[$registroact];

                                    $movimiento->margenUtilidadMovimientoDetalle[$registroact] = $movimiento->precioVentaPublicoMovimientoDetalle[$registroact] /
                                            ($movimiento->valorNetoMovimientoDetalle[$registroact] == 0 ? 1 : (($movimiento->valorNetoMovimientoDetalle[$registroact]) * 100));

                                }


                                $subtotal += (isset($nuevoserrores[$j]["valorBrutoMovimientoDetalle"]) ? $nuevoserrores[$j]["valorBrutoMovimientoDetalle"] : (isset($movimiento->valorBrutoMovimientoDetalle[$registroact]) ? $movimiento->valorBrutoMovimientoDetalle[$registroact] : 0)) * $detalle[$j]["cantidadMovimientoDetalle"];
                                $descuento += $movimiento->valorDescuentoMovimientoDetalle[$registroact] *
                                        $detalle[$j]["cantidadMovimientoDetalle"];
                                $totalUnidades += $detalle[$j]["cantidadMovimientoDetalle"];

                                $registroact++;
                            }
                        }
                    }

                    $base = $subtotal - $descuento;

                    // luego de calculados los impuestos, calculamos las retenciones ya que estas dependen de la base de impuestos de documento
                    $retenciones = $movimiento->consultarretenciones($encabezado[$i]["Tercero_idTercero"], $encabezado[$i]["Documento_idDocumento"], $encabezado[$i]["DocumentoConcepto_idDocumentoConcepto"], substr($ids, 0, strlen($ids) - 1), substr($cants, 0, strlen($cants) - 1), substr($precios, 0, strlen($precios) - 1), substr($ivas, 0, strlen($ivas) - 1), substr($regs, 0, strlen($regs) - 1), substr($descuentos, 0, strlen($descuentos) - 1), $totalBaseImp, $totalImp, ($movimiento->tasaCambioMovimiento == 0 ? 1 : $movimiento->tasaCambioMovimiento), $movimiento->fechaElaboracionMovimiento);

                    // sumamos las retenciones en los campos correspondientes
                    $totalregret = (isset($retenciones[0]["Producto_idProducto"]) ? count($retenciones) : 0 );

                    $totalReteFte = 0;
                    $totalReteIva = 0;
                    $totalReteIca = 0;
                    $totalReteOtr = 0;
                    $totalReteCree = 0;


                    for ($ret = 0; $ret < $totalregret; $ret++) {
                        //                            echo " entra for 3 ";
                        // cada retencion que recorremos, la vamos acumulando en el campo correspondiente (segun el tipoRetencion) y en el producto correspondiente
                        // (segun el registro del array de retenciones)
                        switch ($retenciones[$ret]["tipoRetencion"]) {
                            case 'valorReteFuenteMovimientoDetalle' :
                                $movimiento->valorReteFuenteMovimientoDetalle[(int) $retenciones[$ret]["registro"]] += $retenciones[$ret]["valorUnitarioMovimientoRetencion"];
                                $totalReteFte += $retenciones[$ret]["valorUnitarioMovimientoRetencion"] * $retenciones[$ret]["cantidadMovimientoDetalle"];
                                break;
                            case 'valorReteIcaMovimientoDetalle' :
                                $movimiento->valorReteIcaMovimientoDetalle[(int) $retenciones[$ret]["registro"]] += $retenciones[$ret]["valorUnitarioMovimientoRetencion"];
                                $totalReteIca += $retenciones[$ret]["valorUnitarioMovimientoRetencion"] * $retenciones[$ret]["cantidadMovimientoDetalle"];
                                break;
                            case 'valorReteIvaMovimientoDetalle' :
                                $movimiento->valorReteIvaMovimientoDetalle[(int) $retenciones[$ret]["registro"]] += $retenciones[$ret]["valorUnitarioMovimientoRetencion"];
                                $totalReteIva += $retenciones[$ret]["valorUnitarioMovimientoRetencion"] * $retenciones[$ret]["cantidadMovimientoDetalle"];
                                $afecReteIva = $retenciones[$ret]["ReteIvaAfectable"];
                                break;
                            case 'valorReteCreeMovimientoDetalle' :
                                $movimiento->valorReteCreeMovimientoDetalle[(int) $retenciones[$ret]["registro"]] += $retenciones[$ret]["valorUnitarioMovimientoRetencion"];
                                $totalReteCree += $retenciones[$ret]["valorUnitarioMovimientoRetencion"] * $retenciones[$ret]["cantidadMovimientoDetalle"];
                                break;
                        }
                        //echo $retenciones[$ret]["tipoRetencion"].' = '.  $retenciones[$ret]["valorUnitarioMovimientoRetencion"]."<br>";
                    }

                    $movimiento->totalUnidadesMovimiento = $totalUnidades;
                    $movimiento->subtotalMovimiento = $subtotal;
                    $movimiento->porcentajeDescuentoMovimiento = (isset($encabezado[$i]["porcentajeDescuentoMovimiento"]) ? $encabezado[$i]["porcentajeDescuentoMovimiento"] : 0);
                    $movimiento->valorDescuentoMovimiento = $descuento;
                    $movimiento->valorBaseMovimiento = $base;
                    $movimiento->valorIvaMovimiento = $totalImp;

                    // Pendiente llenar estos datos automaticamente en la importacion (son para las NIIF)
                    $movimiento->porcentajeDescuentoFinancieroMovimiento = 0;
                    $movimiento->valorDescuentoFinancieroMovimiento = 0;
                    //$movimiento->valorBaseNIIFMovimiento = 0;

                    $movimiento->valorIvaMovimiento = $totalImp;
                    $movimiento->valorRetencionMovimiento = $totalReteFte;
                    $movimiento->valorReteIvaMovimiento = $totalReteIva;
                    $movimiento->valorReteIcaMovimiento = $totalReteIca;


                    $movimiento->valorTotalMovimiento = number_format(($base + $totalImp - $totalReteFte - (($afecReteIva == 'NO') ? 0 : $totalReteIva) - $totalReteIca + $movimiento->valorFleteMovimiento + $movimiento->valorSeguroMovimiento + $movimiento->valorAcarreoMovimiento), $datosDoc[0]['redondeoTotalDocumento'],'.','');
                    $movimiento->valorRecibidoMovimiento = 0;

                    // cada que llenamos un documento, lo cargamos a la base de datos
                    // pero antes de adicionarlo, consultamos que exista del mismo tipo de documento y con el mismo numero para obtener el id
                    // la variable Origen, es para identificar si viene de Excel, EDI, o es de una liquidacion de importacion
                    switch ($origen) {
                        case 'interface':
                            $movimiento->ConsultarMovimiento("Documento_idDocumento = " . $movimiento->Documento_idDocumento . " and numeroMovimiento = '" . $movimiento->numeroMovimiento . "'");
                            break;
                        case 'importacion':
                            $movimiento->ConsultarMovimiento("Documento_idDocumento = " . $movimiento->Documento_idDocumento . " and Importacion_idImportacion  = " . $movimiento->Importacion_idImportacion . " and numeroMovimiento = '" . $movimiento->numeroMovimiento . "'");
                            break;
                        case 'produccion':
                            $movimiento->ConsultarMovimiento("Documento_idDocumento = " . $movimiento->Documento_idDocumento . " and numeroReferenciaInternoMovimiento = '" . $movimiento->numeroReferenciaInternoMovimiento . "'");
                            break;
                        case 'conectividad':
                            //						$movimiento->ConsultarMovimiento("Documento_idDocumento = " . $movimiento->Documento_idDocumento . " and numeroReferenciaInternoMovimiento = '" . $movimiento->numeroReferenciaInternoMovimiento . "' and DocumentoConcepto_idDocumentoConcepto = ".$movimiento->DocumentoConcepto_idDocumentoConcepto);
                            $movimiento->ConsultarMovimiento("Documento_idDocumento = " . $movimiento->Documento_idDocumento . " and numeroReferenciaExternoMovimiento = '" . $movimiento->numeroReferenciaExternoMovimiento . "'");
                            break;
                    }

                     if ($movimiento->idMovimiento == 0) {
                        //                           echo 'entra1';
                        $movimiento->AdicionarMovimiento();
                    } else {
                        //                            echo 'entra2';
                        $movimiento->ModificarMovimiento();
                    }


                }
            }

            $returnuevoserrores = isset($nuevoserrores[0]["error"]) ? $nuevoserrores : array();
            $retorno = array_merge((array) $retorno, (array) $returnuevoserrores);

            return $retorno;
        }

        function validarMovimiento($encabezado, $detalle, $listaprecio, $listapreciotercero, $tipo = '') {


            $ruta = dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR;

            
            $swerror = true;
            $errores = array();

            return $errores;
            
            $linea = 0;
            $totalreg = (isset($encabezado[0]["numeroMovimiento"]) ? count($encabezado) : 0);

            //print_r($detalle);
            for ($x = 0; $x < $totalreg; $x++) {
                //echo " entra for validar ";
                // validamos si el tercero no es cero
                if ($encabezado[$x]["Documento_idDocumento"] == 0) {
                    $errores[$linea]["numeroMovimiento"] = $encabezado[$x]["numeroMovimiento"];
                    $errores[$linea]["error"] = 'El Documento (' . (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '' ) . ') no existe';
                    $errores[$linea]["segmento"] = 'BGM';
                    $errores[$linea]["documento"] = (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '');
                    $errores[$linea]["concepto"] = (isset($encabezado[$x]["codigoConceptoDocumento"]) ? $encabezado[$x]["codigoConceptoDocumento"] : '');
                    $swerror = false;
                    $linea++;
                }
                //                        else if($encabezado[$x]["Documento_idDocumento"] != 0)
                //                        {
                //
                //                        }
                //                        if($encabezado[$x]["Tercero_idTercero"] == 0 || $encabezado[$x]["Tercero_idTercero"] == '')
                //                        {
                //
                //
                //                            echo 'NO EXISTE';
                //                            echo '<br>';
                //                            echo '<br>';
                //                        }
                //


                if (!isset($encabezado[$x]["Tercero_idTercero"]) or $encabezado[$x]["Tercero_idTercero"] == 0 or $encabezado[$x]["Tercero_idTercero"] == '') {
                    $errores[$linea]["numeroMovimiento"] = $encabezado[$x]["numeroMovimiento"];
                    $errores[$linea]["error"] = 'El EAN del Cliente (' . (isset($encabezado[$x]["eanTercero"]) ? $encabezado[$x]["eanTercero"] : '' ) . ') no existe';
                    $errores[$linea]["segmento"] = 'NAD+BY';
                    $errores[$linea]["documento"] = (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '');
                    $errores[$linea]["concepto"] = (isset($encabezado[$x]["codigoConceptoDocumento"]) ? $encabezado[$x]["codigoConceptoDocumento"] : '');
                    $swerror = false;
                    $linea++;
                }
                // validamos si el sitio de entrega esta lleno pero no existe
                if ((isset($encabezado[$x]["eanEntrega"]) and $encabezado[$x]["eanEntrega"] != '') and ( $encabezado[$x]["Tercero_idEntrega"] == 0 or $encabezado[$x]["Tercero_idEntrega"] == '')) {
                    $errores[$linea]["numeroMovimiento"] = $encabezado[$x]["numeroMovimiento"];
                    $errores[$linea]["error"] = 'El EAN del Sitio de Entrega (' . $encabezado[$x]["eanEntrega"] . ') no existe';
                    $errores[$linea]["segmento"] = 'NAD+DP';
                    $errores[$linea]["documento"] = (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '');
                    $errores[$linea]["concepto"] = (isset($encabezado[$x]["codigoConceptoDocumento"]) ? $encabezado[$x]["codigoConceptoDocumento"] : '');
                    $swerror = false;
                    $linea++;
                }
                // Verificamos que el periodo exista
                if (isset($encabezado[$x]["Periodo_idPeriodo"]) and ( $encabezado[$x]["Periodo_idPeriodo"] == 0 or $encabezado[$x]["Periodo_idPeriodo"] == '')) {
                    $errores[$linea]["numeroMovimiento"] = $encabezado[$x]["numeroMovimiento"];
                    $errores[$linea]["error"] = 'La Fecha de elaboracion (' . $encabezado[$x]["fechaElaboracionMovimiento"] .
                            ') no pertenece a un periodo ACTIVO o el periodo no se ha creado';
                    $errores[$linea]["segmento"] = 'DTM+137';
                    $errores[$linea]["documento"] = (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '');
                    $errores[$linea]["concepto"] = (isset($encabezado[$x]["codigoConceptoDocumento"]) ? $encabezado[$x]["codigoConceptoDocumento"] : '');
                    $swerror = false;
                    $linea++;
                }


                $totaldet = (isset($detalle[0]["numeroMovimiento"]) ? count($detalle) : 0);
                for ($y = 0; $y < $totaldet; $y++) {
                    if (isset($encabezado[$x]["numeroMovimiento"]) and isset($detalle[$y]["numeroMovimiento"]) and $encabezado[$x]["numeroMovimiento"] == $detalle[$y]["numeroMovimiento"]) {
                        // Verificamos que el Producto exista
                        if (isset($detalle[$y]["Producto_idProducto"]) and ( $detalle[$y]["Producto_idProducto"] == 0 or $detalle[$y]["Producto_idProducto"] == '')) {
                            $errores[$linea]["numeroMovimiento"] = $detalle[$y]["numeroMovimiento"];

                            $errores[$linea]["error"] = 'El EAN del Producto (' . $detalle[$y]["eanProducto"] . ') no existe';
                            $errores[$linea]["segmento"] = 'LIN';
                            $errores[$linea]["documento"] = (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '');
                            $errores[$linea]["concepto"] = (isset($encabezado[$x]["codigoConceptoDocumento"]) ? $encabezado[$x]["codigoConceptoDocumento"] : '');
                            $swerror = false;
                            $linea++;
                        }
                        // Verificamos que si tiene localizaciones de predistribucion, existan
                        if (isset($encabezado[$x]["tipoMovimiento"]) and $encabezado[$x]["tipoMovimiento"] == 'PREDISTRIBUIDA'
                                and isset($detalle[$y]["Tercero_idAlmacen"]) and ( $detalle[$y]["Tercero_idAlmacen"] == 0 or $detalle[$y]["Tercero_idAlmacen"] == '')) {
                            $errores[$linea]["numeroMovimiento"] = $detalle[$y]["numeroMovimiento"];
                            $errores[$linea]["error"] = 'El Codigo/EAN del Tercero/Almacen del Detalle (' . $detalle[$y]["eanAlmacen"] .
                                    ') no existe';
                            $errores[$linea]["segmento"] = 'LOC+7';
                            $errores[$linea]["documento"] = (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '');
                            $errores[$linea]["concepto"] = (isset($encabezado[$x]["codigoConceptoDocumento"]) ? $encabezado[$x]["codigoConceptoDocumento"] : '');
                            $swerror = false;
                            $linea++;
                        }
                        // verificamos que la cantidad no sea cero
                        if (isset($detalle[$y]["cantidadMovimientoDetalle"]) and ( $detalle[$y]["cantidadMovimientoDetalle"] == 0 or $detalle[$y]["cantidadMovimientoDetalle"] == '')) {
                            $errores[$linea]["numeroMovimiento"] = $detalle[$y]["numeroMovimiento"];
                            $errores[$linea]["error"] = 'La cantidad del Producto con EAN (' . $detalle[$y]["eanProducto"] . ') es cero';
                            $errores[$linea]["segmento"] = 'QTY';
                            $errores[$linea]["documento"] = (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '');
                            $errores[$linea]["concepto"] = (isset($encabezado[$x]["codigoConceptoDocumento"]) ? $encabezado[$x]["codigoConceptoDocumento"] : '');
                            $swerror = false;
                            $linea++;
                        }


                        /*

                          //echo 'precios '.$detalle[$y]["valorBrutoMovimientoDetalle"].' '.$precio;
                          $tercero->ConsultarVistaTercero("idTercero = ".$encabezado[$x]["Tercero_idTercero"], "","idTercero, ListaPrecio_idListaPrecio");
                          $datosProducto = $producto->ConsultarVistaProducto("idProducto = ".$detalle[$y]["Producto_idProducto"], "", "precioProducto","idProducto");

                          if(!empty($tercero->ListaPrecio_idListaPrecio))
                          {
                          $lista->ConsultarPrecio("ListaPrecio_idListaPrecio = ".$tercero->ListaPrecio_idListaPrecio." and idProducto = ".$detalle[$y]["Producto_idProducto"]);

                          if($lista->precioListaPrecioDetalle > 0)
                          {
                          $precio = $lista->precioListaPrecioDetalle;
                          }
                          else
                          {
                          $precio = $datosProducto[0]['precioProducto'];
                          }
                          }
                          else
                          {

                          $precio = $datosProducto[0]['precioProducto'];
                          }

                          if ((!isset($encabezado[$x]["LiquidacionNomina_idLiquidacionNomina"]) or (isset($encabezado[$x]["LiquidacionNomina_idLiquidacionNomina"]) and $encabezado[$x]["LiquidacionNomina_idLiquidacionNomina"] == 0) )  and
                          isset($detalle[$y]["valorBrutoMovimientoDetalle"]) and ($detalle[$y]["valorBrutoMovimientoDetalle"] == 0 or $detalle[$y]["valorBrutoMovimientoDetalle"] == '' or $detalle[$y]["valorBrutoMovimientoDetalle"] != $precio))
                          {
                          $errores[$linea]["numeroMovimiento"] = $detalle[$y]["numeroMovimiento"];
                          $errores[$linea]["error"] = 'REF: '.$detalle[$y]["eanProducto"].', El precio del documento (' . $detalle[$y]["valorBrutoMovimientoDetalle"] . ') no es igual al del producto ('.$precio.')';
                          $errores[$linea]["segmento"] = 'PRI';
                          $swerror = false;
                          $linea++;
                          } */
                        $hoy = date("Y-m-d");
                        if ($listaprecio != '') {
                            $lista->ConsultarIdListaPrecio("codigoAlternoListaPrecio = '" . $listaprecio[$y]["codigoAlternoListaPrecio"] . "' and fechaInicialListaPrecio <= '" . $hoy . "' and fechaFinalListaPrecio >= '" . $hoy . "'");
                            $idListaPrecio = $lista->idListaPrecio;
                        } else {
                            $idListaPrecio = 0;
                        }
                        require_once($ruta.'documentocomercial.class.php');
                        if (!isset($documentocomercial))
                        {
                            $documentocomercial = new Documento();
                        }

                        $datosdocumento = $documentocomercial->ConsultarVistaDocumento("idDocumento = " . $encabezado[$x]["Documento_idDocumento"]);
                        // -----------------------------
                        // Si el documento comercial esta configurado para que no valide precios,
                        // nos devolvemos al inicio del ciclo
                        // -----------------------------
                        if ($datosdocumento[0]["existeDiferenciaPrecioDocumento"] == "NoValidar" and isset($datosdocumento[0]["existeDiferenciaPrecioDocumento"])) {
                            continue;
                        }


                        require_once($ruta.'producto.class.php');
                        $producto = new Producto();
                        $precioproducto = $producto->ConsultarVistaProducto('idProducto = ' . $detalle[$y]["Producto_idProducto"], '', 'precioProducto');
                        //print_r($listapreciotercero);
                        /* $datosLista = $lista->ConsultarVistaListaPrecioTerceroDetalle("Producto_idProducto = " . $detalle[$y]["Producto_idProducto"] . " and idTercero = " . $encabezado[$x]["Tercero_idTercero"] . " and fechaInicialListaPrecio <= '" . $hoy . "' and fechaFinalListaPrecio >= '" . $hoy . "'", "", "idListaPrecio, precioListaPrecioDetalle, Producto_idProducto"); */
                        if (!empty($listaprecio) && !empty($listapreciotercero)) {
                            $datosLista = $lista->BuscarValoresListaPrecio($listaprecio[$y]["Producto_idProducto"], $listapreciotercero[$x]["Tercero_idTercero"], $hoy, '', $idListaPrecio);
                        } else {
                            if ($encabezado[$x]['ListaPrecio_idListaPrecio'] != 0) {
                                if ($datosdocumento[0]['validarTerceroListaPrecioDocumento'] == 0) {
                                    $encabezado[$x]["Tercero_idTercero"] = 0;
                                }
                                $datosLista = $lista->BuscarValoresListaPrecio($detalle[$y]["Producto_idProducto"], $encabezado[$x]["Tercero_idTercero"], $hoy, '', $encabezado[$x]['ListaPrecio_idListaPrecio']);
                            } else {
                                $datoslista = 0;
                            }
                        }
                        /* print_r($datosLista);
                          echo "<br>"; */
                        if ($tipo == 'interface') {
                            if ($datosdocumento[0]["existeDiferenciaPrecioDocumento"] == "ReemplazarPrecio" && ($listaprecio != '' || $encabezado[$x]['ListaPrecio_idListaPrecio'] != 0)) {
                                if (isset($detalle[$y]["Producto_idProducto"])) {
                                    $errores[$linea]["numeroMovimiento"] = "";
                                    $errores[$linea]["error"] = '';
                                    $errores[$linea]["ListaPrecio_idListaPrecioDetalle"] = $datosLista[0]['idListaPrecio'];
                                    $errores[$linea]["precioListaMovimientoDetalle"] = $datosLista[0]['precioListaPrecioDetalle'];
                                    $errores[$linea]["valorBrutoMovimientoDetalle"] = $datosLista[0]['precioListaPrecioDetalle'];
                                    $linea++;
                                } else {
                                    $errores[$linea]["precioListaPrecioDetalle"] = $detalle[$y]["precioListaPrecioDetalle"];
                                    $errores[$linea]["error"] = 'REF: ' . $detalle[$y]["eanProducto"] . ', No existe en la Lista de Precio';
                                    $errores[$linea]["segmento"] = 'PRI';
                                    $errores[$linea]["documento"] = (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '');
                                    $errores[$linea]["concepto"] = (isset($encabezado[$x]["codigoConceptoDocumento"]) ? $encabezado[$x]["codigoConceptoDocumento"] : '');
                                    $swerror = false;
                                    $linea++;
                                }
                            } else {
                                if ($datosdocumento[0]["existeDiferenciaPrecioDocumento"] == "GenerarError") {
                                    if ($detalle[$y]["valorBrutoMovimientoDetalle"] <> $datosLista[0]['precioListaPrecioDetalle']) {
                                        $errores[$linea]["numeroMovimiento"] = $detalle[$y]["numeroMovimiento"];
                                        $errores[$linea]["error"] = 'REF: ' . $detalle[$y]["eanProducto"] . ', El precio del documento (' . $detalle[$y]["valorBrutoMovimientoDetalle"] . ') no es igual al del producto (' . $datosLista[0]['precioListaPrecioDetalle'] . ')';
                                        $errores[$linea]["segmento"] = 'PRI';
                                        $errores[$linea]["documento"] = (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '');
                                        $errores[$linea]["concepto"] = (isset($encabezado[$x]["codigoConceptoDocumento"]) ? $encabezado[$x]["codigoConceptoDocumento"] : '');
                                        $swerror = false;
                                        $linea++;
                                    }
                                } else {
                                    if ($detalle[$y]["valorBrutoMovimientoDetalle"] == '' || $detalle[$y]["valorBrutoMovimientoDetalle"] == 0) {
                                        $errores[$linea]["numeroMovimiento"] = "";
                                        $errores[$linea]["error"] = '';
                                        $errores[$linea]["ListaPrecio_idListaPrecioDetalle"] = 0;
                                        $errores[$linea]["precioListaMovimientoDetalle"] = $precioproducto[0]['precioProducto'];
                                        $errores[$linea]["valorBrutoMovimientoDetalle"] = $precioproducto[0]['precioProducto'];
                                        $errores[$linea]["documento"] = (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '');
                                        $errores[$linea]["concepto"] = (isset($encabezado[$x]["codigoConceptoDocumento"]) ? $encabezado[$x]["codigoConceptoDocumento"] : '');
                                        $linea++;
                                    }
                                }

                                $valserie = true;

                                for ($w = 0; $w < $totaldet; $w++) {
                                    if ($w != $y) {
                                        if (isset($detalle[$w]['numeroSerie']) && isset($detalle[$y]['numeroSerie'])) {
                                            if (($detalle[$w]['numeroSerie'] == $detalle[$y]['numeroSerie']) && ($detalle[$w]['numeroSerie'] != '')) {
                                                $errores[$linea]["numeroMovimiento"] = $detalle[$y]["numeroMovimiento"];
                                                $errores[$linea]["error"] = 'REF: ' . $detalle[$y]["eanProducto"] . ' El numero de producto serie se repite en la lineas ' . ($w + 1) . ' y ' . ($y + 1);
                                                $errores[$linea]["segmento"] = 'PRI';
                                                $errores[$linea]["documento"] = (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '');
                                                $errores[$linea]["concepto"] = (isset($encabezado[$x]["codigoConceptoDocumento"]) ? $encabezado[$x]["codigoConceptoDocumento"] : '');
                                                $swerror = false;
                                                $linea++;
                                                $valserie = false;
                                            }
                                        }
                                    }
                                }

                                if ($valserie == true) {
                                    if (isset($datosdocumento[0]["ModeloContable_idModeloContable"]) && ($datosdocumento[0]['ModeloContable_idModeloContable'] == 6 || $datosdocumento[0]['ModeloContable_idModeloContable'] == 2 || $datosdocumento[0]['ModeloContable_idModeloContable'] == 5 || $datosdocumento[0]['ModeloContable_idModeloContable'] == 7)) {

                                    } else if (isset($datosdocumento[0]["ModeloContable_idModeloContable"]) && ($datosdocumento[0]['ModeloContable_idModeloContable'] == 1 || $datosdocumento[0]['ModeloContable_idModeloContable'] == 12 )) {

                                    }
                                }
                            }
                        } else {
                            if ($datosdocumento[0]["existeDiferenciaPrecioDocumento"] == "ReemplazarPrecio") {
                                if (isset($detalle[$y]["Producto_idProducto"])) {
                                    $errores[$linea]["numeroMovimiento"] = "";
                                    $errores[$linea]["error"] = '';
                                    $errores[$linea]["ListaPrecio_idListaPrecioDetalle"] = 0;
                                    $errores[$linea]["precioListaMovimientoDetalle"] = 0;
                                    $errores[$linea]["valorBrutoMovimientoDetalle"] = $detalle[$y]["valorBrutoMovimientoDetalle"];
                                    $errores[$linea]["documento"] = (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '');
                                    $errores[$linea]["concepto"] = (isset($encabezado[$x]["codigoConceptoDocumento"]) ? $encabezado[$x]["codigoConceptoDocumento"] : '');
                                    $linea++;
                                }
                            } else {
                                if ($datosdocumento[0]["existeDiferenciaPrecioDocumento"] == "GenerarError") {
                                    if ($detalle[$y]["valorBrutoMovimientoDetalle"] <> $detalle[$y]["valorBrutoMovimientoDetalle"]) {
                                        $errores[$linea]["numeroMovimiento"] = $detalle[$y]["numeroMovimiento"];
                                        $errores[$linea]["error"] = 'REF: ' . $detalle[$y]["eanProducto"] . ', El precio del documento (' . $detalle[$y]["valorBrutoMovimientoDetalle"] . ') no es igual al del producto (' . $precioproducto[0]['precioProducto'] . ')';
                                        $errores[$linea]["segmento"] = 'PRI';
                                        $errores[$linea]["documento"] = (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '');
                                        $errores[$linea]["concepto"] = (isset($encabezado[$x]["codigoConceptoDocumento"]) ? $encabezado[$x]["codigoConceptoDocumento"] : '');
                                        $swerror = false;
                                        $linea++;
                                    }
                                } else {
                                    if ($detalle[$y]["valorBrutoMovimientoDetalle"] == '' || $detalle[$y]["valorBrutoMovimientoDetalle"] == 0) {
                                        $errores[$linea]["numeroMovimiento"] = "";
                                        $errores[$linea]["error"] = '';
                                        $errores[$linea]["ListaPrecio_idListaPrecioDetalle"] = 0;
                                        $errores[$linea]["precioListaMovimientoDetalle"] = $precioproducto[0]['precioProducto'];
                                        $errores[$linea]["valorBrutoMovimientoDetalle"] = $precioproducto[0]['precioProducto'];
                                        $errores[$linea]["documento"] = (isset($encabezado[$x]["codigoDocumento"]) ? $encabezado[$x]["codigoDocumento"] : '');
                                        $errores[$linea]["concepto"] = (isset($encabezado[$x]["codigoConceptoDocumento"]) ? $encabezado[$x]["codigoConceptoDocumento"] : '');
                                        $linea++;
                                    }
                                }
                            }
                        }

                        /* se le debe agregar al formato de excel en el encabezado de lista de precio una opcion para reemplazar el precio del producto, y en el detalle agregar una columna de tercero el cual se le insertara a todos los productos
                         */


                        // esta validacion es para mirar que el numero de serial de un producto no se repita en el excel
                        // vamos a validar si el documento pide numero de serie y dependiendo del modelo contable
                        // miramos si hay que adicionar o mirar si el serial existe.
                        //                            var_dump($errores);
                    }
                }
            }
            //print_r($errores);
            return $errores;
        }
    }
?>