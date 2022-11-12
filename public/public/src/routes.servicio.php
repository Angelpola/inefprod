<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de servicio social.
 *
 * Lista de funciones
 *
 * /servicio
 * - /set
 * - /agregar
 * - /getLastData
 * - /getInfo
 * - /updateComments
 * - /listReportes
 * - /listHistorial
 * - /borrar
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/servicio', function () {

    $this->get('/set', function (Request $request, Response $response, array $args) {
        global $dblog;
        $query = "SELECT * FROM tbl_servicio_social_archivos WHERE col_id='".intval($_REQUEST['idreporte'])."' AND col_servicioid='".intval($_REQUEST['id'])."'";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'UPDATE tbl_servicio_social_archivos SET col_estatus="'.intval($_REQUEST['status']).'" WHERE col_id="'.$data->col_id.'"';

        $dblog = new DBLog($query, 'tbl_servicio_social_archivos', '', '', 'Servicio social', $this->db);
        $dblog->where = array('col_id' => intval($data->col_id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        $_response['status'] = 'true';

        return $this->response->withJson($_response);
    });

    $this->post('/agregar', function (Request $request, Response $response, $args) {
        global $uploaddir, $download_url, $allowExtensions, $limitFileSize;
        global $dblog;

        $userID = getCurrentUserID();
        $periodo = getCurrentAlumnoPeriodoID($this->db);

        if (!file_exists($uploaddir.'serviciosocial')) mkdir($uploaddir.'serviciosocial', 0777, true);

        if(esRepresentante()) {
            $_response['status'] = 'No tienes permisos suficientes para subir este archivo.';
            $_response['reason'] = 'Error representante';
            return $this->response->withJson($_response);
        }

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        // if($input->adjunto->filename == ''){
        //     $_response['status'] = 'false';
        //     return $this->response->withJson($_response);
        // }

        if($input->adjunto->filename == ''){
            $_response['status'] = 'No se puede guardar el registro, debido a que no estas enviando ningun archivo, debes enviar un archivo como evidencia de tu reporte.';
            return $this->response->withJson($_response);
        }

        list($type, $dataFile) = explode(';', $input->adjunto->value);
        list(, $dataFile)      = explode(',', $dataFile);

        if ( base64_encode(base64_decode($dataFile, true)) !== $dataFile){
            $_response['status'] = 'El archivo que estas intentando enviar parece ser invaliddo, puedes probar abriendolo con un editor y guardar como un nuevo archivo antes de volver a intentar subirlo.';
            return $this->response->withJson($_response);
        }

        $array_ext = explode('.', $input->adjunto->filename);
        $extension = end($array_ext);

        if (!in_array(strtoupper($extension), $allowExtensions)) {
            $_response['status'] = 'No se permite el tipo de archivo que deseas agregar, solo se permite: '.implode(', ', $allowExtensions);
            return $this->response->withJson($_response);
        }

        $fileSizeUploaded = getBase64Size($dataFile);

        $max_upload = (int)(ini_get('upload_max_filesize'));
        $max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);
        $_response['mus'] = $upload_mb;

        if($fileSizeUploaded > $limitFileSize) {
            $_response['status'] = 'El archivo que deseas agregar supera el limite permitido de: '.($limitFileSize / 1e+6).' Megas';
            return $this->response->withJson($_response);
        }

        $query = 'SELECT * FROM tbl_servicio_social WHERE col_alumnoid="'.$userID.'" AND col_periodoid="'.$periodo.'"';
        $sthCurrent = $this->db->prepare($query);
        $sthCurrent->execute();
        $servicios = $sthCurrent->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_servicio_social_archivos WHERE col_alumnoid="'.$userID.'" AND col_servicioid="'.$servicios->col_id.'" AND col_estatus=0';
        $sthCurrent = $this->db->prepare($query);
        $sthCurrent->execute();
        if($sthCurrent->rowCount() == 0) {

            $data = array(
                'col_alumnoid' => $userID,
                'col_servicioid' => $servicios->col_id,
                'col_archivo' => '',
                'col_created_at' => date("Y-m-d H:i:s"),
                'col_created_by' => $userID
            );

            $query = 'INSERT INTO tbl_servicio_social_archivos ('.implode(",", array_keys($data)).')
            VALUES("'.implode('", "', array_values($data)).'")';
            $sth = $this->db->prepare($query);

            if($sth->execute()){
                $_response['status'] = 'true';
                if($input->adjunto->filename){
                    $lastID = $this->db->lastInsertId();
                    $filename = 'servicio-'.strtotime('now').'.'.$extension;
                    $query = 'UPDATE tbl_servicio_social_archivos SET col_archivo="'.$filename.'" WHERE col_id="'.$lastID.'"';

                    $dblog = new DBLog($query, 'tbl_servicio_social_archivos', '', '', 'Servicio Social', $this->db);
                    $dblog->where = array('col_id' => intval($lastID));
                    $dblog->prepareLog();

                    $archivo = $this->db->prepare($query);
                    $archivo->execute();

                    $dblog->saveLog();

                    if(!file_exists($uploaddir.'serviciosocial')) @mkdir($uploaddir.'serviciosocial', 0777);
                    $_response['uploaded'] = file_put_contents($uploaddir.'serviciosocial/'.$filename, base64_decode($dataFile));
                }
            }else{
                $_response['status'] = 'No se puedo guardar el registro.';
            }

        }else{
            // $_response['status'] = 'Solo se permite un archivo';
            $_response['status'] = 'Actualmente tienes un reporte en revisión, no puedes subir mas archivos mientras tengas un reporte con estatus "en revisión."';
        }

        return $this->response->withJson($_response);

    });

    $this->get('/getLastData', function (Request $request, Response $response, array $args) {

        $query = 'SELECT * FROM tbl_servicio_social WHERE col_alumnoid="'.intval($_REQUEST['id']).'" ORDER BY col_id DESC LIMIT 1';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson($data);
    });

    $this->get('/getInfo', function (Request $request, Response $response, array $args) {
        global $download_url;

        $userID = getCurrentUserID();
        $periodo = getCurrentAlumnoPeriodoID($this->db);

        $periodoData = getPeriodo($periodo, $this->db, false);

        $sth = $this->db->prepare('SELECT * FROM tbl_documentos WHERE col_id="42"');
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);
        $_response['formato'] = $download_url.$data->col_filepath;

        if($periodoData->col_grado > 6){
            $sth = $this->db->prepare('SELECT * FROM tbl_documentos WHERE col_id="46"');
            $sth->execute();
            $data = $sth->fetch(PDO::FETCH_OBJ);
            $_response['formatoLiberacion'] = $download_url.$data->col_filepath;
        }


        $query = 'SELECT * FROM tbl_servicio_social WHERE col_alumnoid="'.$userID.'" AND col_periodoid="'.$periodo.'"';
        $sthCurrent = $this->db->prepare($query);
        $sthCurrent->execute();
        if($sthCurrent->rowCount() == 0){
            $_response['allow'] = 'nada';
            return $this->response->withJson($_response);
        }else{
            $servicioData = $sthCurrent->fetch(PDO::FETCH_OBJ);


            $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
            $c = $this->db->prepare($query);
            $c->execute();
            $r = $c->fetch(PDO::FETCH_OBJ);
            $estatus_ss = $r->col_reportes_servicio_social;

            // $sth = $this->db->prepare("SELECT * FROM tbl_servicio_social_archivos ORDER BY col_id DESC LIMIT 1");
            $sth = $this->db->prepare("SELECT * FROM tbl_servicio_social_archivos WHERE  col_alumnoid='".$userID."' AND col_servicioid='".$servicioData->col_id."' ORDER BY col_id DESC LIMIT 1");
            $sth->execute();
            $item = $sth->fetch(PDO::FETCH_OBJ);
            if($sth->rowCount()){
            $i = 0;

                if($item->col_estatus == 0) { //Subido sin revision
                    $_response['allow'] = 'wait';
                    $_response['currentArchivo'] = $download_url.'serviciosocial/'.$item->col_archivo;
                }

                if($item->col_estatus == 1) { //Revisado Denegado
                    $_response['allow'] = 'true';
                }

                if($item->col_estatus == 2 || $item->col_estatus == 3 || $item->col_estatus == 4 || $item->col_estatus == 5) { //Revisado Aprobado
                    $_response['allow'] = 'aprobado';
                    if($estatus_ss == 1) {
                        $_response['allow'] = 'true';
                    }else{
                        $_response['allow'] = 'false';
                    }
                }
            }else{

                if($estatus_ss == 1) {
                    $_response['allow'] = 'true';
                }else{
                    $_response['allow'] = 'false';
                }
            }

        }

        return $this->response->withJson($_response);

    });

    $this->post('/updateComments', function (Request $request, Response $response, $args) {
        global $dblog;
        $input = json_decode($request->getBody());

        $query = 'UPDATE tbl_servicio_social_archivos SET col_comentarios="'.addslashes($input->comentario).'" WHERE col_id="'.intval($input->id).'"';

        $dblog = new DBLog($query, 'tbl_servicio_social_archivos', '', '', 'Servicio Social', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        $_response['status'] = 'true';
        return $this->response->withJson($_response);
    });

    $this->get('/listReportes', function (Request $request, Response $response, array $args) {
        global $download_url;

        // $alumnoid = getCurrentUserID();
        // $periodoid = getLastPeriodoAlumno(intval($alumnoid), $this->db);
        $periodosActivos = getCurrentPeriodos($this->db);
        $query = "SELECT a.*, s.col_lugar AS lugar, s.col_periodoid AS periodoID FROM tbl_servicio_social s LEFT OUTER JOIN tbl_alumnos a ON a.col_id=s.col_alumnoid WHERE s.col_id='".intval($_REQUEST['id'])."'";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
        $periodoData = getPeriodo($alumnoData->periodoID, $this->db, false);

        $query = "SELECT * FROM tbl_servicio_social_archivos WHERE col_servicioid='".intval($_REQUEST['id'])."' ORDER BY col_id DESC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){


            $carrera = getCarrera($item['col_carrera'], $this->db);
            $result[$i]['id'] = $item['col_id'];
            $result[$i]['comentarios'] = $item['col_comentarios'];
            $result[$i]['fecha'] = fechaTexto($item['col_created_at']).' '.substr($item['col_created_at'], 11, strlen($item['col_created_at']));
            $result[$i]['descargar'] = '<a class="text-primary" href="'.$download_url.'serviciosocial/'.$item['col_archivo'].'" target="_blank"><i class="fas fa-file"></i> Descargar</a>';

            $result[$i]['aprobar'] =  '<span class="'.($item['col_estatus'] == 0?'text-white badge badge-warning':'text-secondary').'" title="En Revisión"><i class="fas fa-check-circle"></i> En Revisión</span>';
            $result[$i]['aprobar'] .= '<br/><a class="'.($item['col_estatus'] == 2?'text-white badge badge-success':'text-secondary').'" title="Aprobado" href="#/pages/alumnos/reportes-servicio/'.intval($_REQUEST['id']).'/aprobado/'.intval($item['col_id']).'"><i class="fas fa-check-circle"></i> Aprobado</a>';
            $result[$i]['aprobar'] .= '<br/><a class="'.($item['col_estatus'] == 1?'text-white badge badge-danger':'text-secondary').'" title="Rechazado" href="#/pages/alumnos/reportes-servicio/'.intval($_REQUEST['id']).'/rechazado/'.intval($item['col_id']).'"><i class="fas fa-times-circle"></i> Rechazado</a>';
            $result[$i]['aprobar'] .= '<br/><a class="'.($item['col_estatus'] == 3?'text-white badge badge-info':'text-secondary').'" title="Extemporaneo" href="#/pages/alumnos/reportes-servicio/'.intval($_REQUEST['id']).'/extemporaneo/'.intval($item['col_id']).'"><i class="fas fa-check-square"></i> Extemporaneo</a>';
            $result[$i]['aprobar'] .= '<br/><a class="'.($item['col_estatus'] == 4?'text-white badge badge-danger':'text-secondary').'" title="Falsificación" href="#/pages/alumnos/reportes-servicio/'.intval($_REQUEST['id']).'/falsificacion/'.intval($item['col_id']).'"><i class="fas fa-exclamation-triangle"></i> Falsificación</a>';
            $result[$i]['aprobar'] .= '<br/><a class="'.($item['col_estatus'] == 5?'text-white badge badge-success':'text-secondary').'" title="Carta de Liberación" href="#/pages/alumnos/reportes-servicio/'.intval($_REQUEST['id']).'/liberacion/'.intval($item['col_id']).'"><i class="fas fa-check-double"></i> Carta de Liberación Entregada</a>';

            // if($item['col_estatus'] == 0) $result[$i]['aprobar'] .= '<br/><br/>Estatus Actual: <span class="badge badge-warning"><i class="fas fa-clock"></i> Esperando Revisión</span>';
            // if($item['col_estatus'] == 1) $result[$i]['aprobar'] .= '<br/><br/>Estatus Actual: <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Rechazado</span>';
            // if($item['col_estatus'] == 2) $result[$i]['aprobar'] .= '<br/><br/>Estatus Actual: <span class="badge badge-success"><i class="fas fa-check-circle"></i> Aprobado</span>';
            // if($item['col_estatus'] == 3) $result[$i]['aprobar'] .= '<br/><br/>Estatus Actual: <span class="badge badge-info"><i class="fas fa-check-square"></i> Extemporaneo</span>';
            // if($item['col_estatus'] == 4) $result[$i]['aprobar'] .= '<br/><br/>Estatus Actual: <span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> Falsificación</span>';
            // if($item['col_estatus'] == 5) $result[$i]['aprobar'] .= '<br/><br/>Estatus Actual: <span class="badge badge-success"><i class="fas fa-check-double"></i> Carta de Liberación Entregada</span>';
            $i++;
        }

        $_response['periodoActivo'] = 'false';
        $_response['nombreAlumno'] = fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos);
        $_response['periodo'] = fixEncode($periodoData->col_nombre). ' ('.$periodoData->col_grado.'-'.$periodoData->col_grupo.')';
        if(in_array($periodoData->col_id, $periodosActivos)) {
            $_response['periodoActivo'] = 'true';
        }
        $_response['lugar'] = fixEncode($alumnoData->lugar);
        $_response['result'] = $result;
        return $this->response->withJson($_response);
    });

    $this->get('/listHistorial', function (Request $request, Response $response, array $args) {
        global $download_url;

        $alumnoid = getCurrentUserID();

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $r = $c->fetch(PDO::FETCH_OBJ);
        $estatus_ss = $r->col_reportes_servicio_social;

        // $periodoid = getLastPeriodoAlumno(intval($alumnoid), $this->db);
        if(date('N') != 3 && strtotime('now') > strtotime($item->col_next)){
            $allow = 'true';
            if(date('N') == 2 && date('G') >= 19){
                $allow = 'false';
            }
        }else{
            $allow = 'false';
        }
        $query = "SELECT * FROM tbl_servicio_social_archivos WHERE col_alumnoid='".intval($alumnoid)."' ORDER BY col_id DESC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){

            $result[$i]['id'] = $item['col_id'];
            $result[$i]['comentarios'] = $item['col_comentarios'];
            $fecha = fechaTexto($item['col_created_at']).' '.substr($item['col_created_at'], 11, strlen($item['col_created_at']));
            $result[$i]['fecha'] = '<a class="text-primary" href="'.$download_url.'serviciosocial/'.$item['col_archivo'].'" target="_blank">'.$fecha.'</a>';

            if($item['col_estatus'] == 0) {
                $result[$i]['estatus'] = '<span class="text-info"><i class="fas fa-clock"></i> En Revisión</span>';
                if($allow == true || $estatus_ss == 1) $result[$i]['estatus'] .= '&nbsp;&nbsp;<a class="text-danger" href="#/pages/servicio-social/borrar/'.$item['col_id'].'"><i class="fas fa-trash"></i> Borrar Archivo</a>';
            }
            if($item['col_estatus'] == 1) $result[$i]['estatus'] = '<span class="text-danger"><i class="fas fa-times-circle"></i> Rechazado</span>';
            if($item['col_estatus'] == 2) $result[$i]['estatus'] = '<span class="text-success"><i class="fas fa-check-circle"></i> Aprobado</span>';
            if($item['col_estatus'] == 3) $result[$i]['estatus'] = '<span class="text-info"><i class="fas fa-check-square"></i> Extemporaneo</span>';
            if($item['col_estatus'] == 4) $result[$i]['estatus'] = '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Falsificación</span>';
            if($item['col_estatus'] == 5) $result[$i]['estatus'] = '<span class="text-success"><i class="fas fa-check-double"></i> Cara de Liberación Aprobada</span>';

            $i++;
        }

        return $this->response->withJson($result);
    });

    $this->post('/borrar', function (Request $request, Response $response, $args) {
        global $dblog;
        $input = json_decode($request->getBody());
        $_response['status'] = 'false';
        $alumnoid = getCurrentUserID();

        $query = 'DELETE FROM tbl_servicio_social_archivos WHERE col_id="'.intval($input->id).'" AND col_alumnoid="'.$alumnoid.'"';

        $dblog = new DBLog($query, 'tbl_servicio_social_archivos', '', '', 'Servicio Social - Archivos', $this->db);
        $dblog->where = array('col_id' => intval($input->id), 'col_alumnoid' => intval($alumnoid));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        $query = 'SELECT * FROM tbl_servicio_social_archivos WHERE col_id="'.intval($input->id).'" AND col_alumnoid="'.$alumnoid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        if($sth->rowCount() == 0) {
            $_response['status'] = 'true';
        }
        return $this->response->withJson($_response);
    });


});
// Termina routes.servicio.php