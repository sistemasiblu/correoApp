function recorerBody(divname)
{
    try
    {
        var dataMail = [""];

        var strSelector = "#" + divname.toString() + "  table > tbody > tr";
        var contTablas  = 0;
        var contRows    = 0;
        
        $(function() {
            $(strSelector).each(function(index) {
                contTablas++;
                
                if (contTablas === 2 || contTablas === 9 || contTablas >= 12)
                {
                    contRows++;
                    $(this).children('td').each(function(index2) {
                        var htmlContent = "<tr> <td style='border:1px solid #000000;'> "+ "" + " </td> "  + 
                                          " <td style='border:1px solid #000000;'> " + $(this).text() + "</td> </tr>";
                        $('#tblresults > tbody').append(htmlContent);
                        dataMail.push(htmlContent);

                    });
                }
                
            });
        });
        
        verificarContenido();

    }
    catch (err)
    {
        alert(err.message);
    }
}

function strip_tags(input, allowed) {

  allowed = (((allowed || '') + '')
    .toLowerCase()
    .match(/<[a-z][a-z0-9]*>/g) || [])
    .join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
  var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
    commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
  return input.replace(commentsAndPhpTags, '')
    .replace(tags, function($0, $1) {
      return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
    });
}


// esta función se creó para quitar a las etiquetas Html del correo de lebon algunos 
// caracteres especiales que se le insertan al momento de reenviar un correo
// normalmente por etiquetas adicionales que inserta outlook para hacerlo compatible
// con Word (<:op>)
function quitarSimbolos(dato)
{

    dato = dato.replace("=", "");
    dato = dato.replace("/", "");
    dato = dato.replace("<\no:p>", "");
    dato = dato.replace(/(\r\n|\n|\r)/gm,"");
    dato = dato.replace("<span>","");
    dato = strip_tags(dato, '');
    //console.log(dato);
    return dato;   
}

function corregirFecha(fecha)
{
    mes = fecha.substring(5, 8);
    switch(mes) {
    case 'Ene':
        mes = '01';
        break;
    case 'Feb':
        mes = '02';
        break;
    case 'Mar':
        mes = '03';
        break;
    case 'Abr':
        mes = '04';
        break;
    case 'May':
        mes = '05';
        break;
    case 'Jun':
        mes = '06';
        break;
    case 'Jul':
        mes = '07';
        break;
    case 'Ago':
        mes = '08';
        break;
    case 'Sep':
        mes = '09';
        break;
    case 'Oct':
        mes = '10';
        break;
    case 'Nov':
        mes = '11';
        break;
    case 'Nov':
        mes = '12';
        break;

    default:
        mes = '01';
    }  


    fecha =  fecha.substring(0, 4) +'-'+ mes +'-'+ fecha.substring(9, 11)
    return fecha;
}

function generarInsert(divname)
{
    try
    {

        var strSelector = "#" + divname.toString() + "  table > tbody > tr";
        var contReg  = 0;
        var contRows    = 0;
        var numeroOrden = '';
        var fechaOrden = '';
        var observacionOrden = '';
        var insertEncabezado = '';
        var insertDetalle = '';

        $(function() {
            var swObservacion = false;

            insertEncabezado = 'Insert Into PedidoLeBon(numeroPedido, fechaPedido, observacionPedido) values ';
            insertDetalle = 'Insert Into PedidoLeBonDetalle(numeroPedido, referenciaPedidoDetalle, cantidadPedidoDetalle, precioPedidoDetalle) values ';
        

            // recorre cada uno de los TD del correo
            $(strSelector).each(function(index) {
                contReg++;
                
                if  (contReg === 2 ) 
                {
                    //alert ('registro 2');
                    //console.log($(this).children('td:eq(1)').text());
                    document.getElementById("numeroOrden").value = quitarSimbolos($(this).children('td:eq(1)').text());
                    // console.log('Orden '+document.getElementById("numeroOrden").value);
                }
                else if  (contReg === 9) 
                {
                    //alert ('registro 9');
                    document.getElementById("fechaOrden").value = quitarSimbolos($(this).children('td:eq(1)').text());
                    // console.log(corregirFecha(document.getElementById("fechaOrden").value));
                    document.getElementById("observacionOrden").value = quitarSimbolos($(this).children('td:eq(3)').text());
                    // console.log('Fecha '+document.getElementById("fechaOrden").value);
                    // console.log('Observacion '+document.getElementById("observacionOrden").value);
                }
                else if  ( contReg >= 12) 
                {
                    //alert ('registro 12');
                    contRows++;
                    
                    if(  quitarSimbolos($(this).children('td:eq(0)').text()).trim() == 'OBSERVACIONES')
                    {    
                        swObservacion = true;
                    }
                    else
                    {
                        
                        if(swObservacion === true && $(this).children('td:eq(0)').text().trim() != "ELABORADO")
                        {
                            document.getElementById("observacionOrden").value += " " + quitarSimbolos($(this).children('td:eq(0)').text());
                            // console.log('Observacion Orden '+document.getElementById("observacionOrden").value);

                        }
                        else
                        {
                            if($(this).children('td:eq(1)').text().trim() != "RECIBIDO")
                            {
                                insertDetalle += "('" + document.getElementById("numeroOrden").value +
                                    "','" + quitarSimbolos($(this).children('td:eq(1)').text()) +
                                    "','" + quitarSimbolos($(this).children('td:eq(5)').text()) +
                                    "','"+  quitarSimbolos($(this).children('td:eq(6)').text().replace(',',''))+"')," ;
                                //console.log($(this).children('td:eq(1)').text().trim() +' / '+ swObservacion);
                            }
                        }
                    }
                }

            });

            insertEncabezado += "('" + document.getElementById("numeroOrden").value +"','"+ corregirFecha(document.getElementById("fechaOrden").value) +"','"+ document.getElementById("observacionOrden").value +"');"
            insertDetalle = insertDetalle.substring(0, insertDetalle.length - 1)+';';
            //console.log(insertEncabezado);
            //console.log(insertDetalle);
            //con los campos de encabezado y detalle llenos, ejecutamos la funcion que los guarda en la base de datos
            //realizaProceso(document.getElementById("numeroOrden"),insertEncabezado, insertDetalle)
            realizaProceso(document.getElementById("numeroOrden").value, insertEncabezado, insertDetalle);
            
            document.getElementById("numeroOrden").innerHTML = "";
            document.getElementById("fechaOrden").innerHTML = "";
            document.getElementById("observacionOrden").innerHTML = "";

        });

 
        
        
    }
    catch (err)
    {
        alert(err.message);
    }
}

function verificarContenido()
{
    var _Selector = "#tblresults > tbody > tr";
    $(_Selector).each(function(index) {
        $(this).children('td').each(function(index2) {
            alert("verificarContenido"+ $(this).text() );
        });
    });
    
}




