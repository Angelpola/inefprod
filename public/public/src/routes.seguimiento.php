<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de seguimiento de alumnos (tickets).
 *
 * Lista de funciones
 *
 * /seguimiento
 * - /listUsuarios
 * - /list
 * - /get
 * - /update
 * - /add
 * - /deleteObservacion
 * - /delete
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/seguimiento', function () {

    $this->get('/listUsuarios', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT u.*, d.col_nombre AS departamento, CONCAT(u.col_firstname, ' ', u.col_lastname) AS col_fullname FROM tbl_users u LEFT OUTER JOIN tbl_departamentos d ON d.col_id=u.col_depto WHERE u.col_maestro=0 ORDER BY u.col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        foreach($todos as $item){
            if($item[col_id] == 1) continue;
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = fixEncode($item['col_fullname'], true).' ('.fixEncode($item['departamento'], true).')';
            $result[$i]['text'] =  fixEncode($item['col_fullname'], true).' ('.fixEncode($item['departamento'], true).')';
            $i++;
        }

        return $this->response->withJson($result);
    });

    $this->get('/list', function (Request $request, Response $response, array $args) {
        $depto = getCurrentUserDepto();
        $userid = getCurrentUserID();
        if(in_array($depto, array(11, 2)) OR $userid == 1) {
            $query = "SELECT t.*, CONCAT(a.col_nombres, ' ', a.col_apellidos) AS nombreAlumno, CONCAT(u.col_firstname, ' ', u.col_lastname) AS nombreAdmin FROM tbl_seguimiento t LEFT OUTER JOIN tbl_users u ON u.col_id=t.col_asignado LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ORDER BY t.col_id DESC LIMIT 100";
        }else{
            $query = "SELECT t.*, CONCAT(a.col_nombres, ' ', a.col_apellidos) AS nombreAlumno, CONCAT(u.col_firstname, ' ', u.col_lastname) AS nombreAdmin FROM tbl_seguimiento t LEFT OUTER JOIN tbl_users u ON u.col_id=t.col_asignado LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid WHERE t.col_asignado='".$userid."' ORDER BY t.col_id DESC LIMIT 100";
        }

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;

        $estatus = array(
            0 => '<span class="badge badge-warning">Pendiente</span>',
            1 => '<span class="badge badge-info">Revisión</span>',
            2 => '<span class="badge badge-success">Completado</span>'
        );
        foreach($todos as $item){

            $result[$i]['id'] = $item['col_id'];
            $result[$i]['tipo'] = fixEncode($item['col_tipo'], true);
            $result[$i]['nombreAlumno'] = fixEncode($item['nombreAlumno'], true);
            $result[$i]['nombreAdmin'] = fixEncode($item['nombreAdmin'], true);
            $result[$i]['fecha'] = fechaTexto($item['col_created_at']).' '.substr($item['col_created_at'], -8);
            $result[$i]['estatus'] = $estatus[$item['col_estatus']];
            $diasPasados = countDays(strtotime($item['col_created_at']));
            if($diasPasados >= 3) {
                $dias = '<span class="text-danger">'.$diasPasados.' días</span>';
            }else if($diasPasados == 2){
                $dias = '<span class="text-warning">'.$diasPasados.' días</span>';
            }else{
                $dias = '<span class="text-info">'.$diasPasados.' día</span>';
            }
            $result[$i]['dias'] = $dias;
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/get', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_seguimiento WHERE col_id='".intval($_REQUEST['id'])."'");
        $sth->execute();
        $item = $sth->fetch(PDO::FETCH_OBJ);

        $sth = $this->db->prepare("SELECT * FROM tbl_seguimiento_observaciones WHERE col_seguimientoid='".intval($_REQUEST['id'])."' ORDER BY col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $o = 0;
        foreach($todos as $row){
            $sth = $this->db->prepare("SELECT * FROM tbl_users WHERE col_id='".intval($row['col_created_by'])."'");
            $sth->execute();
            $autor = $sth->fetch(PDO::FETCH_OBJ);
            $observaciones[$o]['id'] = $row['col_id'];
            $observaciones[$o]['texto'] = nl2br($row['col_observaciones']);
            $observaciones[$o]['autor'] = fixEncode($autor->col_firstname.' '.$autor->col_lastname, true);
            $observaciones[$o]['fecha'] = fechaTexto($row['col_created_at']).' '.substr($row['col_created_at'], -8);
            $o++;
        }

        $sth = $this->db->prepare("SELECT * FROM tbl_alumnos WHERE col_id='".intval($item->col_alumnoid)."'");
        $sth->execute();
        $alumno = $sth->fetch(PDO::FETCH_OBJ);

        $sth = $this->db->prepare("SELECT * FROM tbl_users WHERE col_id='".intval($item->col_asignado)."'");
        $sth->execute();
        $admin = $sth->fetch(PDO::FETCH_OBJ);

        $dataCarrera = getCarrera($alumno->col_carrera, $this->db);
        $dataPeriodo = getPeriodo($alumno->col_periodoid, $this->db, false);

        $_response['id'] = $item->col_id;
        $_response['alumnoid'] = $item->col_alumnoid;
        $_response['alumno'] = fixEncode($alumno->col_nombres.' '.$alumno->col_apellidos, true);
        $_response['carreraNombre'] = fixEncode($dataCarrera['nombre'], true);
        $_response['modalidad'] = fixEncode($dataCarrera['modalidad'], true);
        $_response['grupo'] = $dataPeriodo->col_grado.'-'.$dataPeriodo->col_grupo;
        $_response['tipo'] = ucfirst($item->col_tipo);

        $razones = json_decode($item->col_razones);
        $_response['razones'] = $razones;
        $estatus = array(
            0 => '<span class="badge badge-warning">Pendiente</span>',
            1 => '<span class="badge badge-info">Revisión</span>',
            2 => '<span class="badge badge-success">Completado</span>'
        );

        $_response['estatusTexto'] = $estatus[$item->col_estatus];
        $_response['estatus'] = $item->col_estatus;

        $_response['asignado'] = 0;
        $_response['asignadoNombre'] = 'Asignación Pendiente';
        if(intval($item->col_asignado) > 0){
            $_response['asignado'] = intval($item->col_asignado);
            $_response['asignadoNombre'] = fixEncode($admin->col_firstname.' '.$admin->col_lastname, true);
        }
        $_response['observaciones'] = $observaciones;
        $_response['fecha'] = fechaTexto($item->col_created_at).' '.substr($item->col_created_at, -8);

        return $this->response->withJson($_response);

    });


    $this->put('/update', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $userid = getCurrentUserID();
        $input = json_decode($request->getBody());

        $query = 'UPDATE tbl_seguimiento SET
        col_asignado="'.intval($input->asignacion).'",
        col_estatus="'.intval($input->estatus).'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$userid.'" WHERE col_id="'.$input->id.'"';

        $dblog = new DBLog($query, 'tbl_seguimiento', '', '', 'Seguimiento', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        $_response['status'] = 'true';

        return $this->response->withJson($_response);

    });

    $this->post('/add', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());
        $userid = getCurrentUserID();

        $data = array(
            "col_seguimientoid" => intval($input->id),
            "col_observaciones" => addslashes($input->observaciones),
            "col_created_at" => date("Y-m-d H:i:s"),
            "col_created_by" => $userid,
            "col_updated_at" => date("Y-m-d H:i:s"),
            "col_updated_by" => $userid,
        );

        $query = 'INSERT INTO tbl_seguimiento_observaciones ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';

        $dblog = new DBLog($query, 'tbl_seguimiento_observaciones', '', '', 'Seguimiento', $this->db);
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
        $dblog->saveLog();

        $_response['status'] = 'true';

        return $this->response->withJson($_response);

    });

    $this->delete('/deleteObservacion', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_seguimiento_observaciones WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_seguimiento_observaciones', '', '', 'Seguimiento', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->delete('/delete', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_seguimiento WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_seguimiento', '', '', 'Seguimiento', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        $query = 'DELETE FROM tbl_seguimiento_observaciones WHERE col_seguimientoid="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_seguimiento_observaciones', '', '', 'Seguimiento', $this->db);
        $dblog->where = array('col_seguimientoid' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();


        return $this->response->withJson(array('status' => 'true'));

    });

});
// Termina routes.seguimiento.php
