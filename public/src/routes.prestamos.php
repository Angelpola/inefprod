<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de renovación de prestamos de libros para alumnos.
 *
 * Lista de funciones
 *
 * /prestamos
 * - /list
 * - /update
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/prestamos', function () {
    $this->get('/list', function (Request $request, Response $response, array $args) {
        $alumnoid = getCurrentUserID();
        $depto = getCurrentUserDepto();

        $query = "SELECT b.*, p.col_modalidad AS modalidad, CONCAT(p.col_grado, '-', p.col_grupo) as grado, CONCAT(a.col_nombres, ' ', a.col_apellidos) AS nombre_alumno FROM tbl_biblioteca b ".
        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=b.col_alumnoid ".
        // "LEFT OUTER JOIN tbl_alumnos_taxonomia t ON t.col_alumnoid=b.col_alumnoid AND t.col_status=1 ".
        "LEFT OUTER JOIN tbl_carreras c ON c.col_id=a.col_carrera ".
        "LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid ".
        "WHERE b.col_alumnoid='".$alumnoid."'".
        "ORDER BY b.col_id DESC";
        // echo $query;
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        $tipos = array(0 => 'Domiciliario', 1 => 'Casa', 2 => 'Nocturno');
        $modalidades = array(1=>'Semestral', 2=>'Cuatrimestral', 3=>'Maestría', 4=>'Doctorado');
        foreach($todos as $item){


            $diasPasados = countDays($item['col_fecha_prestamo']);

            $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($item['col_fecha_prestamo'])), $item['col_hora_prestamo'], $item['col_tipo_multa'], $this->db);
            $_fechaPrestamoInicio = strtotime('+1 day', strtotime($item['col_fecha_prestamo']));
            if($item['col_renovacion'] == 'si'){
                $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($item['col_fecha_renovacion'])), $item['col_hora_renovacion'], $item['col_tipo_multa'], $this->db);
                $_fechaPrestamoInicio = strtotime('+1 day', strtotime($item['col_fecha_renovacion']));
            }

            switch($item['col_tipo_multa']) {
                case 0: //Domiciliario
                //$fechaTentativa = date('Y-m-d', strtotime('+3 day', strtotime($_fechaPrestamoInicio)));
                $fechaTentativa = date('Y-m-d', strtotime('+2 day', $_fechaPrestamoInicio));
                break;

                case 1: //Clase
                $fechaTentativa = date('Y-m-d', strtotime($_fechaPrestamoInicio));
                break;

                case 2: //Nocturno
                $fechaTentativa = date('Y-m-d', strtotime($_fechaPrestamoInicio));
                break;
            }

            $lamulta = 0;
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['alumno'] = fixEncode($item['nombre_alumno']);
            $result[$i]['info'] = $modalidades[$item['modalidad']].', '.$item['grado'];
            if(intval($item['col_multa']) == 0 && $item['col_fecha_devolucion'] == '0000-00-00'){
                $result[$i]['multa'] = "$".$multa;
                $lamulta = $multa;
            }else{
                $result[$i]['multa'] = "$".number_format(floatval($item['col_multa']), 2);
                $lamulta = $item['col_multa'];
            }
            $result[$i]['dias'] = $diasPasados;
            $result[$i]['tipo'] = $tipos[$item['col_tipo_multa']];
            $result[$i]['libro'] = fixEncode($item['col_titulo_libro']);
            if($item['col_renovacion'] == 'si'){
                $result[$i]['fecha_prestamo'] = $item['col_fecha_renovacion'].' '.$item['col_hora_renovacion'].' (R)';
            }else{
                $result[$i]['fecha_prestamo'] = $item['col_fecha_prestamo'].' '.$item['col_hora_prestamo'];
            }
            $result[$i]['fecha_devolucion'] = ($item['col_fecha_devolucion'] == '0000-00-00'?'No ha sido devuelto<br/><small class="text-info">(Para devolver el: '.fechaTexto($fechaTentativa).')</small>':$item['col_fecha_devolucion'].' '.$item['col_hora_devolucion']);
            $result[$i]['retraso'] = (intval($lamulta) > 0?'Si':'No');


            if(in_array($depto, array(10, 14, 15)) && intval($lamulta) == 0){
                unset($result[$i]);
                continue;
            }else{
                $i++;
            }
        }

        return $this->response->withJson($result);

    });

    $this->put('/update', function (Request $request, Response $response, $args) {
        global $dblog;

        $input = json_decode($request->getBody());
        if(esRepresentante()) {
            $_response['status'] = 'false';
            $_response['reason'] = 'Error representante';
            return $this->response->withJson($_response);
        }
        $query = 'SELECT * FROM tbl_biblioteca WHERE col_id="'.intval($input->id).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $item = $sth->fetch(PDO::FETCH_OBJ);

        $diasPasados = countDays($item->col_fecha_prestamo);

        $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($item->col_fecha_prestamo)), $item->col_hora_prestamo, $item->col_tipo_multa, $this->db);
        $_fechaPrestamoInicio = strtotime('+1 day', strtotime($item->col_fecha_prestamo));
        if($item->col_renovacion == 'si'){
            $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($item->col_fecha_renovacion)), $item->col_hora_renovacion, $item->col_tipo_multa, $this->db);
            $_fechaPrestamoInicio = strtotime('+1 day', strtotime($item->col_fecha_renovacion));
        }

        if($item->col_renovacion == 'si' && $item->col_fecha_renovacion == date('Y-m-d')){
            $_response['status'] = 'error';
            $_response['message'] = 'Ya renovaste este prestamo el día de hoy, solo puedes ejecutar una renovación una vez al día.';
            return $this->response->withJson($_response);
        }


        if($item->col_renovacion_count == 2){
            $_response['status'] = 'error';
            $_response['message'] = 'Estas intentando renovar por tercera ocasión, esto no es posible. Solo puedes renovar un libro 2 veces.';
            return $this->response->withJson($_response);
        }

        if($item->col_fecha_devolucion != '0000-00-00'){
            $_response['status'] = 'error';
            $_response['message'] = 'El libro ya fue devuelto, para volver a solicitar un prestamo debes acudir a la biblioteca.';
            return $this->response->withJson($_response);
        }

        if(intval($multa)){
            $_response['status'] = 'error';
            $_response['message'] = 'No se ha renovado el prestamo del libro, debido a que ya cuenta con una multa por retraso.';
            return $this->response->withJson($_response);
        }

        $query = 'UPDATE tbl_biblioteca SET
        col_renovacion="si",
        col_fecha_renovacion="'.date('Y-m-d').'",
        col_hora_renovacion="'.date('H').':00",
        col_renovacion_count=col_renovacion_count+1,
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$input->userid.'" WHERE col_id="'.$input->id.'"';

        $dblog = new DBLog($query, 'tbl_biblioteca', '', '', 'Biblioteca', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);

        $sth->execute();

        $dblog->saveLog();

        $_response['status'] = 'success';
        $_response['message'] = 'Se ha renovado correctamente el prestamo del libro.';
        return $this->response->withJson($_response);

    });


});
// Termina routes.prestamos.php