<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de biblioteca para todos los tipos de usuarios.
 *
 * Lista de funciones
 *
 * /biblioteca
 * - /list
 * - /get
 * - /update
 * - /pagar
 * - /add
 * - /delete
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/biblioteca', function () {
    $this->get('/list', function (Request $request, Response $response, array $args) {

        $depto = getCurrentUserDepto();

        $query = "SELECT b.*, p.col_modalidad AS modalidad, CONCAT(p.col_grado, '-', p.col_grupo) as grado, CONCAT(a.col_nombres, ' ', a.col_apellidos) AS nombre_alumno FROM tbl_biblioteca b ".
        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=b.col_alumnoid ".
        // "LEFT OUTER JOIN tbl_alumnos_taxonomia t ON t.col_alumnoid=b.col_alumnoid AND t.col_status=1 ".
        "LEFT OUTER JOIN tbl_carreras c ON c.col_id=a.col_carrera ".
        "LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid ".
        "ORDER BY b.col_fecha_entrega ASC";
        // echo $query;
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        $tipos = array(0 => 'Domiciliario', 1 => 'Casa', 2 => 'Nocturno');
        $modalidades = array(1=>'Semestral', 2=>'Cuatrimestral', 3=>'Maestría', 4=>'Doctorado');
        foreach($todos as $item){


            // $diasPasados = countDays($item['col_fecha_prestamo']);

            $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($item['col_fecha_prestamo'])), $item['col_hora_prestamo'], $item['col_tipo_multa'], $this->db);
            if($item['col_renovacion'] == 'si'){
                $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($item['col_fecha_renovacion'])), $item['col_hora_renovacion'], $item['col_tipo_multa'], $this->db);
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
            // $result[$i]['dias'] = $diasPasados;
            $result[$i]['tipo'] = $tipos[$item['col_tipo_multa']];
            $result[$i]['libro'] = fixEncode($item['col_titulo_libro']);
            if($item['col_renovacion'] == 'si'){
                $result[$i]['fecha_prestamo'] = $item['col_fecha_renovacion'].' '.$item['col_hora_renovacion'].' (R)';
            }else{
                $result[$i]['fecha_prestamo'] = $item['col_fecha_prestamo'].' '.$item['col_hora_prestamo'];
            }
            $result[$i]['fecha_devolucion'] = ($item['col_fecha_devolucion'] == '0000-00-00'?'No ha sido devuelto':$item['col_fecha_devolucion'].' '.$item['col_hora_devolucion']);
            $result[$i]['retraso'] = (intval($lamulta) > 0?'Si':'No');

            if(in_array($depto, array(10, 14, 15)) || isAdmin()) {
                if(intval($lamulta) > 0) {
                    if($item['col_multa_pagada'] == 1){
                        $result[$i]['opciones'] = '<div style="text-align: center;"><i title="Multa Pagada" class="fas fa-check-circle text-success"></i></div>';
                    }else{
                        $result[$i]['opciones'] = '<div style="text-align: center;"><a class="opcion-table" title="Marcar como pago realizado" href="#/pages/biblioteca/pago/'.$item['col_id'].'"><i class="fas fa-check-circle"></i></a></div>';
                    }
                }
            }


            if(in_array($depto, array(10, 14, 15)) && intval($lamulta) == 0){
                unset($result[$i]);
                continue;
            }else{
                $i++;
            }
        }

        return $this->response->withJson($result);

    });

    $this->post('/get', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $query = 'SELECT * FROM tbl_biblioteca WHERE col_id="'.intval($input['params']['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $item = $sth->fetch(PDO::FETCH_OBJ);

        $result['col_id'] = $item->col_id;
        $result['col_titulo_libro'] = fixEncode($item->col_titulo_libro);
        $result['col_alumnoid'] = $item->col_alumnoid;
        $result['col_clasificacion'] = $item->col_clasificacion;
        $result['col_adquisicion'] = $item->col_adquisicion;
        $result['col_fecha_prestamo'] = ($item->col_fecha_prestamo == '0000-00-00'?'':$item->col_fecha_prestamo);
        $result['col_hora_prestamo'] = ($item->col_hora_prestamo == '0000-00-00'?'':$item->col_hora_prestamo);
        $result['col_fecha_devolucion'] = ($item->col_fecha_devolucion == '0000-00-00'?'':$item->col_fecha_devolucion);
        $result['col_hora_devolucion'] = ($item->col_hora_devolucion == '0000-00-00'?'':$item->col_hora_devolucion);
        $result['col_identificacion'] = $item->col_identificacion;
        $result['col_renovacion'] = $item->col_renovacion;
        $result['col_fecha_renovacion'] = ($item->col_fecha_renovacion == '0000-00-00'?'':$item->col_fecha_renovacion);
        $result['col_hora_renovacion'] = ($item->col_hora_renovacion == '0000-00-00'?'':$item->col_hora_renovacion);
        $result['col_fecha_entrega'] = ($item->col_fecha_entrega == '0000-00-00'?'':$item->col_fecha_entrega);
        $result['col_multa'] = $item->col_multa;
        $result['col_tipo_multa'] = $item->col_tipo_multa;
        $result['col_multa_estimada'] = '';

        if(intval($item->col_multa) == 0 && $item->col_fecha_devolucion == '0000-00-00'){
            $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($item->col_fecha_prestamo)), $item->col_hora_prestamo, $item->col_tipo_multa, $this->db);
            if($item->col_renovacion == 'si'){
                $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($item->col_fecha_renovacion)), $item->col_hora_renovacion, $item->col_tipo_multa, $this->db);
            }
            $result['col_multa_estimada'] = $multa;
        }

        return $this->response->withJson($result);

    });

    $this->put('/update', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $query = 'UPDATE tbl_biblioteca SET
        col_alumnoid="'.$input->alumnoid.'",
        col_titulo_libro="'.$input->tituloLibro.'",
        col_clasificacion="'.$input->clasificacion.'",
        col_adquisicion="'.$input->adquisicion.'",
        col_fecha_prestamo="'.substr($input->fechaPrestamo[0], 0, 10).'",
        col_hora_prestamo="'.$input->horaPrestamo.'",
        col_fecha_devolucion="'.substr($input->fechaDevolucion[0], 0, 10).'",
        col_hora_devolucion="'.$input->horaDevolucion.'",
        col_identificacion="'.$input->identificacion.'",
        col_renovacion="'.$input->renovacion.'",
        col_fecha_renovacion="'.substr($input->fechaRenovacion[0], 0, 10).'",
        col_hora_renovacion="'.$input->horaRenovacion.'",
        col_fecha_entrega="'.substr($input->fechaEntrega[0], 0, 10).'",
        col_hora_entrega="'.$input->horaEntrega.'",
        col_multa="'.$input->multa.'",
        col_tipo_multa="'.$input->tipoMulta.'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$input->userid.'" WHERE col_id="'.$input->id.'"';
        $sth = $this->db->prepare($query);

        $dblog = new DBLog($query, 'tbl_biblioteca', '', '', 'Biblioteca', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        if($sth->execute()){
            $_response['status'] = 'true';
            $dblog->saveLog();
        }

        return $this->response->withJson($_response);

    });


    $this->post('/pagar', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $query = 'UPDATE tbl_biblioteca SET col_multa_pagada=1 WHERE col_id="'.$input->params->id.'" ';
        $sth = $this->db->prepare($query);

        $dblog = new DBLog($query, 'tbl_biblioteca', '', '', 'Biblioteca', $this->db);
        $dblog->where = array('col_id' => intval($input->params->id));
        $dblog->prepareLog();

        if($sth->execute()){
            $_response['status'] = 'true';
            $dblog->saveLog();
        }

        return $this->response->withJson($_response);

    });

    $this->post('/add', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $data = array(
            "col_alumnoid" => $input->alumnoid,
            "col_titulo_libro" => utf8_decode($input->tituloLibro),
            "col_clasificacion" => $input->clasificacion,
            "col_adquisicion" => $input->adquisicion,
            "col_fecha_prestamo" => substr($input->fechaPrestamo[0], 0, 10),
            "col_hora_prestamo" => $input->horaPrestamo,
            "col_fecha_devolucion" => substr($input->fechaDevolucion[0], 0, 10),
            "col_hora_devolucion" => $input->horaDevolucion,
            "col_identificacion" => $input->identificacion,
            "col_renovacion" => $input->renovacion,
            "col_fecha_renovacion" => substr($input->fechaRenovacion[0], 0, 10),
            "col_hora_renovacion" => $input->horaRenovacion,
            "col_fecha_entrega" => substr($input->fechaEntrega[0], 0, 10),
            "col_hora_entrega" => $input->horaEntrega,
            "col_tipo_multa" => $input->tipoMulta,
            "col_created_at" => date("Y-m-d H:i:s"),
            "col_created_by" => $input->userid,
            "col_updated_at" => date("Y-m-d H:i:s"),
            "col_updated_by" => $input->userid,
        );

        $query = 'INSERT INTO tbl_biblioteca ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';

        $dblog = new DBLog($query, 'tbl_biblioteca', '', '', 'Biblioteca', $this->db);
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);

        if($sth->execute()){
            $_response['status'] = 'true';
            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();
        }

        return $this->response->withJson($_response);

    });

    $this->delete('/delete', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_biblioteca WHERE col_id="'.intval($_REQUEST['id']).'"';
        $sth = $this->db->prepare($query);

        $dblog = new DBLog($query, 'tbl_biblioteca', '', '', 'Biblioteca', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth->execute();
        $dblog->saveLog();
        // $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson(array('status' => 'true'));

    });
});
// Termina routes.biblioteca.php