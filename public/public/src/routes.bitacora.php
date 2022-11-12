<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de pagos.
 *
 * Lista de funciones
 *
 * /pagos
 * - /download
 * - /list
 * - /get
 * - /limpiar
 * - /actualizar
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/bitacora', function () {

    $this->get('/list', function (Request $request, Response $response, array $args) {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $_response = [];

        $start = (isset($_REQUEST['_page'])?intval($_REQUEST['_page']):1);
        $limit = (isset($_REQUEST['_limit'])?intval($_REQUEST['_limit']):20);
        if($start == 1) $start = 0;
        if($start > 1) $start = ($start * $limit) - $limit;

        $sort = 'col_id';
        $order = 'DESC';
        if(isset($_REQUEST['_order'])) $order = (trim($_REQUEST['_order']) != 'ASC'?'DESC':'ASC');


        // col_id
        // modulo
        // accion
        // tipo_autor
        // autor
        // fecha

        if(isset($_REQUEST['_sort'])){
            switch(trim($_REQUEST['_sort'])) {
                case 'col_id': $sort = 'l.col_id'; break;
                case 'modulo': $sort = 'l.col_modulo'; break;
                case 'accion': $sort = 'l.col_type'; break;
                case 'tipo_autor': $sort = 'col_user_type'; break;
                case 'autor': $sort = 'nombreAutor'; break;
                case 'fecha': $sort = 'l.col_datetime'; break;
            }
        }
        $where = [];



        if(isset($_REQUEST['col_id_like'])) $where[] = "l.col_id LIKE '%".addslashes(intval($_REQUEST['col_id_like']))."%'";
        if(isset($_REQUEST['modulo_like'])) $where[] = "l.col_modulo LIKE '%".addslashes(trim($_REQUEST['modulo_like']))."%'";
        if(isset($_REQUEST['accion_like'])) $where[] = "l.col_type LIKE '%".addslashes(trim($_REQUEST['accion_like']))."%'";
        if(isset($_REQUEST['tipo_autor_like'])) $where[] = "l.col_user_type LIKE '%".addslashes(trim($_REQUEST['tipo_autor_like']))."%'";
        if(isset($_REQUEST['fecha_like'])) $where[] = "l.col_datetime LIKE '%".addslashes(trim($_REQUEST['fecha_like']))."%'";
        if(isset($_REQUEST['autor_like'])) $where[] = "(CONCAT(a.col_nombres, ' ', a.col_apellidos) LIKE '%".addslashes(trim($_REQUEST['autor_like']))."%' OR CONCAT(u.col_firstname, ' ', u.col_lastname) LIKE '%".addslashes(trim($_REQUEST['autor_like']))."%')";


        $query = "SELECT l.*, IF(col_user_type = 'alumno', CONCAT(a.col_nombres, ' ', a.col_apellidos), CONCAT(u.col_firstname, ' ', u.col_lastname)) AS nombreAutor FROM tbl_log l ";
        $query .= "LEFT JOIN tbl_alumnos a ON a.col_id=l.col_userid ";
        $query .= "LEFT JOIN tbl_users u ON u.col_id=l.col_userid ";
        $query .= (count($where) > 0?'WHERE '.implode(' AND ', $where):'')." ORDER BY ".$sort." ".$order;

        $sth = $this->db->prepare($query);
        $sth->execute();
        $total = $sth->rowCount();

        $query = $query." LIMIT ".$start.",".$limit;
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        $stO = array(0 => 'no', 1 => 'yes');
        $stI = array(0 => 'far fa-square', 1 => 'far fa-check-square');
        $tipos = array('INSERT' => 'INSERT', 'UPDATE' => 'UPDATE', 'DELETE' => 'DELETE');
        foreach($todos as $item){


            $fecha = explode(' ', $item['col_datetime']);

            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['modulo'] = $item['col_modulo'];
            $result[$i]['accion'] = $tipos[$item['col_type']];
            //$result[$i]['autor'] = fixEncode($autorData->col_fullname);
            $result[$i]['autor'] = fixEncode($item['nombreAutor']);
            $result[$i]['tipo_autor'] = $item['col_user_type'];
            $result[$i]['fecha'] = fechaTexto($item['col_datetime']).' a las '.$fecha[1];

            $result[$i]['type'] = $item['col_type'];
            $result[$i]['old_data'] = json_encode(json_decode($item['col_old_data']), JSON_PRETTY_PRINT);
            $result[$i]['new_data'] = json_encode(json_decode($item['col_new_data']), JSON_PRETTY_PRINT);
            $result[$i]['ip'] = $item['col_ip'];
            $result[$i]['source'] = $item['col_source'];
            $result[$i]['device'] = $item['col_device'];
            $result[$i]['info'] = $item['col_info'];
            // array_map('stripslashes', $result[$i]);

            $i++;
        }

        $_response['list'] = $result;
        $_response['total'] = intval($total);

        return $this->response->withJson($_response);

    });

    $this->post('/get', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $query = 'SELECT * FROM tbl_log WHERE col_id="'.intval($input['params']['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson($data);

    });

    $this->post('/limpiar', function (Request $request, Response $response, $args) {
            $sthr = $this->db->prepare('TRUNCATE TABLE tbl_log');
            $sthr->execute();

            $_response['status'] = 'true';

            $currentUser = getCurrentUserData('id');
            $query = 'SELECT * FROM tbl_users WHERE col_id="'.intval($currentUser).'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $dataUser = $sth->fetch(PDO::FETCH_OBJ);

            $texto = 'Notificación de acción en Bitacora<br/><br/>';
            $texto .= 'Acción realizada por: '.$dataUser->col_firstname.' '.$dataUser->col_lastname.'<br/>';
            $texto .= 'Tipo de acción: Purgar Bitacora<br/>';
            $texto .= 'Fecha: '.date('Y-m-d h:i:s').'<br/>';

            enviarCorreo(array('to' => 'academicolicenciatura@fldch.edu.mx', 'nombre' => ''), 'Notificación de Bitacora: Purgar', $texto);

            return $this->response->withJson($_response);
    });

    $this->post('/revertir', function (Request $request, Response $response, $args) {
        $input = $request->getParsedBody();
        $_response['status'] = 'false';

        $currentUser = getCurrentUserData('id');
        $query = 'SELECT * FROM tbl_users WHERE col_id="'.intval($currentUser).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataUser = $sth->fetch(PDO::FETCH_OBJ);


        $query = 'SELECT * FROM tbl_log WHERE col_id="'.intval($input['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $oldDataDB = json_decode(stripslashes($data->col_old_data), true);
        $whereDB = json_decode(stripslashes($data->col_tbl_id), true);
        $_where = Array();
        foreach($whereDB as $k => $v) {
            $_where[] = $k.'='.$v;
        }
        $where = implode(' AND ', $_where);

        if($data->col_type == 'DELETE'){


            $query = 'INSERT INTO '.$data->col_tbl.' ('.implode(",", array_keys($oldDataDB)).') VALUES("'.implode('", "', array_values($oldDataDB)).'")';


            $dblog = new DBLog($query, $data->col_tbl, '', '', 'Revertir Bitacora > '.$data->col_modulo, $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            if($sth->execute()){
                $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                $dblog->saveLog();
                $_response['status'] = 'true';
            }


        }


        if($data->col_type == 'UPDATE'){
            $_update = Array();
            foreach($oldDataDB as $k => $v) {
                if($k == 'col_id') continue;
                if($v == '') continue;
                $_update[] = $k."='".trim(fixEncode($v))."'";
            }
            $update = implode(', ', $_update);

            $query = "UPDATE ".$data->col_tbl." SET ".$update." WHERE ".$where;

            $dblog = new DBLog($query, $data->col_tbl, '', '', 'Revertir Bitacora > '.$data->col_modulo, $this->db);
            $dblog->where = $whereDB;
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            if($sth->execute()) {
                $_response['status'] = 'true';
                $dblog->saveLog();
            }

        }

        if($data->col_type == 'INSERT'){


            $query = "DELETE FROM ".$data->col_tbl." WHERE ".$where;


            $dblog = new DBLog($query, $data->col_tbl, '', '', 'Revertir Bitacora > '.$data->col_modulo, $this->db);
            $dblog->where = $whereDB;
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            if($sth->execute()){
                $dblog->saveLog();
                $_response['status'] = 'true';
            }


        }

        $texto = 'Notificación de acción en Bitacora<br/><br/>';
        $texto .= 'Acción realizada por: '.$dataUser->col_firstname.' '.$dataUser->col_lastname.'<br/>';
        $texto .= 'Tipo de acción revertida: '.$data->col_type.'<br/>';
        $texto .= 'Fecha: '.date('Y-m-d h:i:s').'<br/>';
        if($data->col_type == 'INSERT'){
            $texto .= 'Acción tomanda: Eliminar<br/><br/>';
            $texto .= '<b>Información Eliminada:</b><br/><br/>';
            $texto .= '<pre>'.$data->col_new_data.'</pre>';
        }

        if($data->col_type == 'DELETE'){
            $texto .= 'Acción tomanda: Se reestablece<br/><br/>';
            $texto .= '<b>Información reestablecida:</b><br/><br/>';
            $texto .= '<pre>'.$data->col_old_data.'</pre>';
        }

        if($data->col_type == 'UPDATE'){
            $texto .= 'Acción tomanda: Se reempleza con la información antigua<br/><br/>';
            $texto .= '<b>Información que se elimina:</b><br/><br/>';
            $texto .= '<pre>'.$data->col_new_data.'</pre><br/><hr/>';
            $texto .= '<b>Información por la que se reemplaza:</b><br/><br/>';
            $texto .= '<pre>'.$data->col_old_data.'</pre>';
        }


        enviarCorreo(array('to' => 'academicolicenciatura@fldch.edu.mx', 'nombre' => ''), 'Notificación de Bitacora', $texto);

        return $this->response->withJson($_response);
});

});
// Termina routes.pagos.php
