<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de carreras.
 *
 * Lista de funciones
 *
 * /carreras
 * - /listSelect
 * - /listPlanes
 * - /list
 * - /get
 * - /update
 * - /add
 * - /delete
 * - /getPlan
 * - /addPlan
 * - /updatePlan
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/carreras', function () {
    $this->get('/listSelect', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_carreras ORDER BY col_id");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        $i = 0;
        foreach($todos as $item){
            $string = $item['col_nombre_largo'].' ('.$item['col_revoe'].')';
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = fixEncode($string);
            $result[$i]['text'] = fixEncode($string);
            $result[$i]['nombre'] = fixEncode($item['col_nombre_largo']);
            $result[$i]['clave'] = $item['col_clave'];
            $result[$i]['revoe'] = $item['col_revoe'];
            $result[$i]['estatus'] = $item['col_estatus'];
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listPlanes', function (Request $request, Response $response, array $args) {
        global $download_url;

        $sth = $this->db->prepare("SELECT * FROM tbl_planes_estudios ORDER BY col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){

            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['nombre'] = fixEncode($item['col_nombre']);
            $result[$i]['descripcion'] = fixEncode($item['col_descripcion']);
            if($item['col_archivo']) {
                $result[$i]['descargar'] = '<a target="_blank" class="text-primary" href="'.$download_url.'/'.$item['col_archivo'].'"><i class="fas fa-file-download"></i> Descargar</a>';
            }else{
                $result[$i]['descargar'] = '';
            }
            $result[$i]['actualizacion'] = fechaTexto($item['col_actualizacion']);

            $i++;
        }

        return $this->response->withJson($result);

    });
    $this->get('/list', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_carreras ORDER BY col_id");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        $campus = array(0 => 'Tuxtla', 1 => 'Tapachula');
        $estatus = array(0 => '<i class="fa fa-minus-circle text-secondary"></i> No', 1 => '<i class="fa fa-check-circle text-success"></i> Si');
        foreach($todos as $item){

            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['nombrelargo'] = fixEncode($item['col_nombre_largo']);
            $result[$i]['nombrecorto'] = fixEncode($item['col_nombre_corto']);
            $result[$i]['revoe'] = $item['col_revoe'];
            $result[$i]['campus'] = $campus[$item['col_campus']];
            $result[$i]['estatus'] = $estatus[$item['col_estatus']];
            $result[$i]['fechainicio'] = $item['col_fecha_inicio'];
            $result[$i]['duracion'] = $item['col_duracion'];
            $result[$i]['modalidad'] = $item['col_modalidad'];

            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->post('/get', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $query = 'SELECT * FROM tbl_carreras WHERE col_id="'.intval($input['params']['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $result['id'] = $data->col_id;
        $result['nombrelargo'] = fixEncode($data->col_nombre_largo);
        $result['nombrecorto'] = fixEncode($data->col_nombre_corto);
        $result['revoe'] = $data->col_revoe;
        $result['fechainicio'] = $data->col_fecha_inicio;
        $result['duracion'] = $data->col_duracion;
        $result['modalidad'] = $data->col_modalidad;
        $result['campus'] = $data->col_campus;
        $result['actualizacion'] = $data->col_actualizacion;
        $result['estatus'] = $data->col_estatus;
        $result['tipo'] = $data->col_tipo;


        return $this->response->withJson($result);

    });

    $this->put('/update', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $query = "SELECT * FROM tbl_carreras where col_revoe='".$input->revoe."' and col_modalidad='".$input->modalidad."' and col_id <> '".$input->id."'";

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        if(count($todos)==0){

            $query = 'UPDATE tbl_carreras SET
            col_nombre_largo="'.($input->nombrelargo).'",
            col_nombre_corto="'.($input->nombrecorto).'",
            col_revoe="'.$input->revoe.'",
            col_fecha_inicio="'.substr($input->fechainicio[0], 0, 10).'",
            col_duracion="'.$input->duracion.'",
            col_modalidad="'.$input->modalidad.'",
            col_campus="'.$input->campus.'",
            col_tipo="'.$input->tipo.'",
            col_estatus="'.$input->estatus.'",
            col_actualizacion="'.$input->actualizacion.'",
            col_updated_at="'.date("Y-m-d H:i:s").'",
            col_updated_by="'.$input->userid.'" WHERE col_id="'.$input->id.'"';

            $dblog = new DBLog($query, 'tbl_carreras', '', '', 'Carreras', $this->db);
            $dblog->where = array('col_id' => intval($input->id));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);

            if($sth->execute()){

                $dblog->saveLog();
                $_response['status'] = 'true';
            }
        }else{
            $_response['status'] = 'exists';
        }

        return $this->response->withJson($_response);

    });


    $this->post('/add', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $query = "SELECT * FROM tbl_carreras where col_revoe='".$input->revoe."' and col_modalidad='".$input->modalidad."'";

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        if(count($todos)==0){

            $query = 'INSERT INTO tbl_carreras (col_nombre_largo, col_nombre_corto, col_revoe, col_fecha_inicio, col_duracion, col_modalidad, col_actualizacion, col_campus, col_tipo, col_estatus, col_created_at, col_created_by, col_updated_at, col_updated_by)
            VALUES("'.($input->nombrelargo).'", "'.($input->nombrecorto).'", "'.$input->revoe.'", "'.substr($input->fechainicio[0], 0, 10).'", "'.$input->duracion.'", "'.$input->modalidad.'", "'.$input->actualizacion.'", "'.$input->campus.'", "'.$input->tipo.'", "'.$input->estatus.'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'")';

            $dblog = new DBLog($query, 'tbl_carreras', '', '', 'Carreras', $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);

            if($sth->execute()){
                $_response['status'] = 'true';
                $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                $dblog->saveLog();
            }

        }else{
            $_response['status'] = 'exists';
        }

        return $this->response->withJson($_response);

    });

    $this->delete('/delete', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_carreras WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_carreras', '', '', 'Carreras', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();
        // $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson(array('status' => 'true'));

    });


    $this->post('/getPlan', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $query = 'SELECT * FROM tbl_planes_estudios WHERE col_id="'.intval($input['params']['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $result['id'] = $data->col_id;
        $result['nombre'] = fixEncode($data->col_nombre);
        $result['descripcion'] = fixEncode($data->col_descripcion);
        $result['actualizacion'] = $data->col_actualizacion;
        $result['archivo'] = $data->col_archivo;

        return $this->response->withJson($result);

    });

    $this->post('/addPlan', function (Request $request, Response $response, $args) {
        global $uploaddir;
        global $dblog;

        $userid = getCurrentUserID();

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $query = 'SELECT * FROM tbl_planes_estudios WHERE col_nombre="'.trim($input->nombre).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        if($sth->rowCount() == 0) {

            // Ahora guardamos el nuevo
            $extension = $input->archivo->extension;
            $filename = 'plan-estudios-'.strtotime('now').'.'.$extension;
            list($type, $dataFile) = explode(';', $input->archivo->value);
            list(, $dataFile)      = explode(',', $dataFile);
            $_response['uploaded'] = file_put_contents($uploaddir.$filename, base64_decode($dataFile));

            $query = 'INSERT INTO tbl_planes_estudios (col_nombre, col_descripcion, col_actualizacion, col_archivo, col_updated_by, col_updated_at) VALUES("'.$input->nombre.'", "'.$input->descripcion.'", "'.substr($input->actualizacion[0], 0, 10).'", "'.$filename.'", "'.$userid.'", "'.date("Y-m-d H:i:s").'")';

            $dblog = new DBLog($query, 'tbl_planes_estudios', '', '', 'Plan de Estudios', $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);

            if($sth->execute()){


                $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                $dblog->saveLog();
                $_response['status'] = 'true';
            }
        }else{
            $_response['status'] = 'duplicated';
        }


        return $this->response->withJson($_response);

    });

    $this->post('/updatePlan', function (Request $request, Response $response, $args) {
        global $uploaddir;
        global $dblog;

        $userid = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

            $query = 'SELECT * FROM tbl_planes_estudios WHERE col_id="'.trim($input->id).'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $currentFile = $sth->fetch(PDO::FETCH_OBJ);
            $archivo_viejo = trim($currentFile->col_archivo);
            if($archivo_viejo){
                $_response['viejo'] = $archivo_viejo;
                if(@file_exists($uploaddir.$archivo_viejo)){
                    $_response['existe_viejo'] = $archivo_viejo;
                    @unlink($uploaddir.$archivo_viejo);
                }
            }

            // Ahora guardamos el nuevo
            $extension = $input->archivo->extension;
            $filename = 'plan-estudios-'.strtotime('now').'.'.$extension;
            list($type, $dataFile) = explode(';', $input->archivo->value);
            list(, $dataFile)      = explode(',', $dataFile);
            $_response['uploaded'] = file_put_contents($uploaddir.$filename, base64_decode($dataFile));

            $query = 'UPDATE tbl_planes_estudios SET
            col_nombre="'.$input->nombre.'",
            col_descripcion="'.$input->descripcion.'",
            col_actualizacion="'.substr($input->actualizacion[0], 0, 10).'",
            col_archivo="'.$filename.'",
            col_updated_by="'.$userid.'",
            col_updated_at="'.date('Y-m-d H:i:s').'"
            WHERE col_id="'.$input->id.'"';

            $dblog = new DBLog($query, 'tbl_planes_estudios', '', '', 'Plan de Estudios', $this->db);
            $dblog->where = array('col_id' => intval($input->params->id));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);

            if($sth->execute()){
                $dblog->saveLog();
                $_response['status'] = 'true';
            }


        return $this->response->withJson($_response);

    });

});
