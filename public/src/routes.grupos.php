<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el funcionamiento de grupos del modulo de materias.
 *
 * Lista de funciones
 *
 * /grupos
 * - /list
 * - /materia
 * - /get
 * - /update
 * - /add
 * - /delete
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/grupos', function () {
    $this->get('/list', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_grupos ORDER BY col_id");
        $sth->execute();
        $todos = $sth->fetchAll();

        $tipo = array(0 => 'Regular', 1 => 'Recurrente');
        $repetir = array(0 => '', 1 => 'Mensual (1 de cada mes)', 2 => 'Mesual (15 de cada mes)');
        $i = 0;
        foreach($todos as $item){

            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['col_nombre'] = utf8_encode($item['col_nombre']);
            $result[$i]['col_descripcion'] = utf8_encode($item['col_descripcion']);
            $result[$i]['col_tipo'] = $item['col_tipo'];
            $result[$i]['col_tipo_desc'] = $tipo[$item['col_tipo']];
            $result[$i]['col_repetir'] = $item['col_repetir'];
            $result[$i]['col_repetir_desc'] = $repetir[$item['col_repetir']];

            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->post('/materia', function (Request $request, Response $response, array $args) {
        $input = json_decode($request->getBody());

        $sth = $this->db->prepare("SELECT * FROM tbl_grupos ORDER BY col_id");
        $sth->execute();
        $todos = $sth->fetchAll();

        $tipo = array(0 => 'Regular', 1 => 'Recurrente');
        $repetir = array(0 => '', 1 => 'Mensual (1 de cada mes)', 2 => 'Mesual (15 de cada mes)');
        $i = 1;
        if(count($todos)){
            $result[0]['col_id'] = '';
            $result[0]['col_nombre'] = 'Seleccionar';
            foreach($todos as $item){

                if( in_array($input->materia, unserialize(stripslashes($item['col_materias']))) ){
                    $result[$i]['col_id'] = $item['col_id'];
                    $result[$i]['col_nombre'] = utf8_encode($item['col_nombre']);
                    //$result[$i]['col_materias'] = unserialize(stripslashes($item['col_materias']));
                    $i++;
                }


            }
        }else{
            $result[0]['col_id'] = '';
            $result[0]['col_nombre'] = 'Sin Grupos';
        }

        return $this->response->withJson($result);

    });

    $this->post('/get', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $query = 'SELECT * FROM tbl_grupos WHERE col_id="'.intval($input['params']['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson($data);

    });

    $this->put('/update', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $query = 'UPDATE tbl_conceptos SET
        col_nombres="'.$input->nombres.'",
        col_apellidos="'.$input->apellidos.'",
        col_correo="'.$input->correo.'",
        col_fecha_nacimiento="'.$input->fechaNacimiento.'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$input->_userid.'" WHERE col_id="'.$input->id.'"';

        $dblog = new DBLog($query, 'tbl_conceptos', '', '', 'Conceptos', $this->db);
        $dblog->where = array('col_id' => intval($input->params->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);

        if($sth->execute()){

            $dblog->saveLog();
            $_response['status'] = 'true';
        }

        return $this->response->withJson($_response);

    });


    $this->post('/add', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $query = 'INSERT INTO tbl_grupos (col_nombre, col_alumnos, col_materias, col_created_at, col_created_by, col_updated_at, col_updated_by)
        VALUES("'.utf8_decode($input->nombre).'", "'.addslashes(serialize($input->alumnos)).'", "'.addslashes(serialize($input->materias)).'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'")';

        $dblog = new DBLog($query, 'tbl_grupos', '', '', 'Grupos', $this->db);
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

        $query = 'DELETE FROM tbl_conceptos WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_conceptos', '', '', 'Conceptos', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();
        // $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson(array('status' => 'true'));

    });
});
