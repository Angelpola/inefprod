<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el apartado de documentos de alumnos y el modulo de documentos de administrativos.
 *
 * Lista de funciones
 *
 * /documentos
 * - /listAlumnos
 * - /list
 * - /imprimirVale
 * - /get
 * - /get
 * - /update
 * - /add
 * - /delete
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$ext_icons = array('xls' => 'far fa-file-excel', 'xlsx' => 'far fa-file-excel', 'doc' => 'far fa-file-word', 'docx' => 'far fa-file-word', 'pdf' => 'far fa-file-pdf', 'jpg' => 'far fa-image', 'png' => 'far fa-image', 'ppt' => 'far fa-file-powerpoint', 'pptx' => 'far fa-file-powerpoint', 'zip' => 'far fa-file-archive', 'rar' => 'far fa-file-archive');

$app->group('/documentos', function () {

    $this->get('/getDataValeDocumentos', function (Request $request, Response $response, array $args) {

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $configData = $c->fetch(PDO::FETCH_OBJ);

        $sth = $this->db->prepare("SELECT * FROM tbl_documentos WHERE col_alumnos='1' ORDER BY col_orden ASC");
        $sth->execute();
        $todos = $sth->fetchAll();

        foreach($todos as $item){
            $result['id'] = $item['col_id'];
            $result['nombre'] = fixEncode($item['col_nombre']);
            $result['orden'] = intval($item['col_orden']);
            $result['obligatorio'] = $item['col_obligatorio'];
            if($item['col_copia'] == 1) {
                $resultDocumentosConCopia[] = $result;
            }else{
                $resultDocumentosOriginales[] = $result;
            }


        }


        $_response['listDocumentosOriginales'] = $resultDocumentosOriginales;
        $_response['listDocumentosConCopia'] = $resultDocumentosConCopia;
        $_response['folio'] = str_pad($configData->col_folio_vale_documentos + 1, 5, "0", STR_PAD_LEFT);

        return $this->response->withJson($_response);
    });

    $this->get('/listDocumentos', function (Request $request, Response $response, array $args) {
        global $apiURL;

        $alumnoid = intval($_REQUEST['alumno']);
        $query_alumno = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
        $obj_alumno = $this->db->prepare($query_alumno);
        $obj_alumno->execute();
        $alumno = $obj_alumno->fetch(PDO::FETCH_OBJ);
        $alumno_nombre = fixEncode($alumno->col_nombres.' '.$alumno->col_apellidos);
        $docs = unserialize(base64_decode($alumno->col_documentos));

        $sth = $this->db->prepare("SELECT * FROM tbl_documentos WHERE col_alumnos='1' ORDER BY col_orden ASC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        foreach($todos as $item){
            if(trim(fixEncode($item['col_nombre'])) == '') continue;
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['col_nombre'] = fixEncode($item['col_nombre']);
            $result[$i]['col_orden'] = intval($item['col_orden']);
            $result[$i]['col_obligatorio'] = $item['col_obligatorio'];

            $losDocs[$item['col_id']] = fixEncode($item['col_nombre']);
            $losDocsOrden[$item['col_id']] = intval($item['col_orden']);
            $i++;
        }


        $sth = $this->db->prepare("SELECT * FROM tbl_alumnos_documentos WHERE col_alumnoid='".$alumnoid."' AND (col_original=1 OR col_copia=1)");
        $sth->execute();
        $todosAlumnos = $sth->fetchAll();
        $a = 0;
        foreach($todosAlumnos as $itemA){
            //if(trim($losDocs[$itemA['col_documentoid']]) == '') continue;
            $resultAlumnos[$a]['id'] = $itemA['col_id'];
            $resultAlumnos[$a]['documentoId'] = $itemA['col_documentoid'];
            $resultAlumnos[$a]['documentoOrden'] = $losDocsOrden[$itemA['col_documentoid']];
            $resultAlumnos[$a]['documentoNombre'] = $losDocs[$itemA['col_documentoid']];
            $resultAlumnos[$a]['original'] = $itemA['col_original'];
            $resultAlumnos[$a]['copia'] = $itemA['col_copia'];
            $a++;
        }

        $_response['alumnoID'] = $alumnoid;
        $_response['nombre'] = fixEncode($alumno_nombre);
        $_response['docs'] = $losDocs;
        $_response['listDocumentos'] = $result;
        $_response['listDocumentosAlumnos'] = $resultAlumnos;
        $_response['otros'] = fixEncode($alumno->col_documentos_otros);
        if($_response['otros'] == '') $_response['otros'] = '-';
        $_response['observaciones'] = fixEncode($alumno->col_documentos_observaciones);
        $sth = $this->db->prepare("SELECT * FROM tbl_vale_documentos WHERE col_alumnoid='".$alumnoid."' ORDER BY col_id DESC");
        $sth->execute();
        $valesDocumentos = $sth->fetchAll();
        foreach($valesDocumentos as $vale) {
            if(trim($vale['col_observaciones']) != ''){
                $url = $apiURL.'/documentos/generarValeDocumentos?id='.$vale['col_id'];
                $_response['observaciones'] .= '<span class="folio">Vale Folio <a target="_blank" href="'.$url.'">#'.str_pad($vale['col_folio'], 5, "0", STR_PAD_LEFT).'</a></span><br/>';
                $_response['observaciones'] .= $vale['col_observaciones'].'<hr/>';
            }
        }


        if($_response['observaciones'] == '') $_response['observaciones'] = '-';
        return $this->response->withJson($_response);

    });

    $this->get('/listAlumnos', function (Request $request, Response $response, array $args) {

        $query_alumno = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$_REQUEST['alumno'].'"';
        $obj_alumno = $this->db->prepare($query_alumno);
        $obj_alumno->execute();
        $alumno = $obj_alumno->fetch(PDO::FETCH_OBJ);
        $alumno_nombre = fixEncode($alumno->col_nombres.' '.$alumno->col_apellidos);
        $docs = unserialize(base64_decode($alumno->col_documentos));

        $sth = $this->db->prepare("SELECT * FROM tbl_documentos WHERE col_alumnos='1' ORDER BY TRIM(col_nombre) ASC");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        $stI = array(0 => '', 1 => 'obligatorio');
        foreach($todos as $item){
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['col_nombre'] = utf8_encode($item['col_nombre']);
            $result[$i]['col_obligatorio'] = $stI[$item['col_obligatorio']];
            $result[$i]['col_checked'] = ($docs[$item['col_id']] != ''?'true':'');

            $i++;
        }

        $_response['nombre'] = (preg_match('//u', $alumno_nombre) == 0?utf8_encode($alumno_nombre):$alumno_nombre);
        $_response['docs'] = json_encode($docs);
        $_response['list'] = $result;
        $_response['otros'] = fixEncode($alumno->col_documentos_otros);
        $_response['observaciones'] = fixEncode($alumno->col_documentos_observaciones);
        return $this->response->withJson($_response);

    });

    $this->get('/list', function (Request $request, Response $response, array $args) {
        global $download_url, $uploaddir, $ext_icons;

        $tipoDocumentos = intval($_REQUEST['tipoDocumentos']);
        switch($tipoDocumentos){
            case 1: $query = "SELECT * FROM tbl_documentos WHERE col_alumnos!='1' ORDER BY col_orden ASC"; break;
            case 2: $query = "SELECT * FROM tbl_documentos WHERE col_alumnos='1' ORDER BY col_orden ASC"; break;
            case 3: $query = "SELECT * FROM tbl_documentos ORDER BY col_orden ASC"; break;
        }

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        $stO = array(0 => 'no', 1 => 'yes');
        $stI = array(0 => 'text-secondary far fa-times-circle fa-1-5x', 1 => 'text-success fas fa-check-circle fa-1-5x');
        foreach($todos as $item){
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['col_nombre'] = fixEncode($item['col_nombre']);
            $result[$i]['orden'] = intval($item['col_orden']);
            $result[$i]['col_obligatorio'] = '<center><span class="obligatorio-'.$stO[$item['col_obligatorio']].'"><i class="'.$stI[$item['col_obligatorio']].'"></i></span></center>';
            $result[$i]['col_alumnos'] = '<center><span class="obligatorio-'.$stO[$item['col_alumnos']].'"><i class="'.$stI[$item['col_alumnos']].'"></i></span></center>';
            $result[$i]['col_file'] = '<i class="'.$ext_icons[$item['col_filetype']].'"></i> <a target="_blank" class="text-secondary" href="'.$download_url.$item['col_filepath'].'">'.$item['col_filename'].'</a>';

            $i++;
        }
        return $this->response->withJson($result);

    });

    $this->get('/generarValeDocumentos', function (Request $request, Response $response, array $args) {
        // global $download_url;

        // if(isset($_REQUEST['a']) && intval($_REQUEST['a']) > 0){
        //     $id = intval($_REQUEST['a']);
        // }

        nuevoValeDocumentos(intval($_REQUEST['id']), $this->db);
        exit;

    });

    $this->get('/get', function (Request $request, Response $response, array $args) {
        global $download_url;

        if(isset($_REQUEST['id']) && intval($_REQUEST['id']) > 0) $id = intval($_REQUEST['id']);

        $query = 'SELECT * FROM tbl_documentos WHERE col_id="'.$id.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $data->col_nombre = fixEncode($data->col_nombre);
        $data->col_filepath = $download_url.$data->col_filepath;

        return $this->response->withJson($data);

    });


    $this->post('/get', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $id = intval($input['params']['id']);

        $query = 'SELECT * FROM tbl_documentos WHERE col_id="'.$id.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);
        $data->col_nombre = fixEncode($data->col_nombre);
        return $this->response->withJson($data);

    });

    $this->put('/update', function (Request $request, Response $response, $args) {
        global $download_url, $uploaddir;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        // Primero borramos el archivo actual si existe
        $query = 'SELECT * FROM tbl_documentos WHERE col_id="'.$input->id.'"';
        $fth = $this->db->prepare($query);
        $fth->execute();
        $file = $fth->fetch(PDO::FETCH_OBJ);
        $archivo_viejo = trim($file->col_filepath);
        if($archivo_viejo){
            $_response['viejo'] = $archivo_viejo;
            if(@file_exists($uploaddir.$archivo_viejo)){
                $_response['existe_viejo'] = $archivo_viejo;
                @unlink($uploaddir.$archivo_viejo);
            }
        }

        $extension = $input->file->extension;
        $filename = 'doc-'.strtotime('now').'.'.$extension;
        //$file_data = explode(',', $input->file->value);
        list($type, $dataFile) = explode(';', $input->file->value);
        list(, $dataFile)      = explode(',', $dataFile);
        $_response['uploaded'] = file_put_contents($uploaddir.$filename, base64_decode($dataFile));




        $query = 'UPDATE tbl_documentos SET
        col_nombre="'.utf8_decode($input->nombre).'",
        col_obligatorio="'.intval($input->obligatorio).'",
        col_alumnos="'.intval($input->alumnos).'",
        col_descargable="'.$input->descargable.'",
        col_filepath="'.$filename.'",
        col_filename="'.$input->file->filename.'",
        col_filetype="'.$extension.'",
        col_orden="'.intval($input->orden).'",
        col_copia="'.intval($input->copia).'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$input->_userid.'" WHERE col_id="'.$input->id.'"';
        $sth = $this->db->prepare($query);

        if($sth->execute()){
            $_response['status'] = 'true';
        }

        return $this->response->withJson($_response);

    });

    $this->post('/addValeDocumentos', function (Request $request, Response $response, $args) {
        global $apiURL;

        $userid = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        if(intval($input->alumnoid) == 0) {
            $_response['status'] = 'false';
            $_response['errorMessage'] = 'Debes seleccionar un alumno para poder generar un vale de documentos.';
            return $this->response->withJson($_response);
        }

        if(trim($input->fecha) == '0000-00-00' || trim($input->fecha) == '') {
            $_response['status'] = 'false';
            $_response['errorMessage'] = 'Debes seleccionar una fecha valida para poder generar un vale de documentos.';
            return $this->response->withJson($_response);
        }

        if(count($input->documentosOriginales) == 0 && count($input->documentosCopias) == 0) {
            $_response['status'] = 'false';
            $_response['errorMessage'] = 'Debes seleccionar al menos un documento para poder generar un vale de documentos.';
            return $this->response->withJson($_response);
        }


        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $configData = $c->fetch(PDO::FETCH_OBJ);
        $folio = $configData->col_folio_vale_documentos + 1;

        $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval($input->alumnoid).'"';
        $a = $this->db->prepare($query);
        $a->execute();
        $alumnoData = $a->fetch(PDO::FETCH_OBJ);

        $accion = 1; // Recepción (alumno entrega a escuela)
        if($input->tipo == 1) $accion = 0; // Entrega (escuela entrega al alumno)

        $data = array(
            "col_folio" => $folio,
            "col_fecha" => $input->fecha,
            "col_tipo" => $input->tipo,
            "col_tramite" => $input->tramite,
            "col_alumnoid" => intval($input->alumnoid),
            "col_periodoid" => $alumnoData->col_periodoid,
            "col_documentos_originales" => addslashes(json_encode($input->documentosOriginales)),
            "col_documentos_copia" => addslashes(json_encode($input->documentosCopias)),
            "col_observaciones" => $input->observaciones,
            "col_hacienda" => $input->hacienda,
            "col_fecha_creacion" => date("Y-m-d H:i:s"),
            "col_autor_creacion" => $userid
        );

        $query = 'INSERT INTO tbl_vale_documentos ('.implode(",", array_keys($data)).') VALUES("'.implode('", "', array_values($data)).'")';
        $sth = $this->db->prepare($query);
        if($sth->execute()){
            $lastID = $this->db->lastInsertId();

            $query = 'UPDATE tbl_config SET col_folio_vale_documentos="'.$folio.'" WHERE col_id=1';
            $sth = $this->db->prepare($query);
            $sth->execute();


            foreach($input->documentosOriginales as $k => $docID) {

                $query = 'SELECT * FROM tbl_alumnos_documentos WHERE col_alumnoid="'.intval($input->alumnoid).'" AND col_documentoid="'.$docID.'"';
                $sth = $this->db->prepare($query);
                $sth->execute();
                if($sth->rowCount() > 0 ) {
                    // Do Update
                    $queryProcess = 'UPDATE tbl_alumnos_documentos SET col_original="'.$accion.'", col_fecha_cambio="'.date("Y-m-d H:i:s").'", col_autor_cambio="'.$userid.'" WHERE  col_alumnoid="'.intval($input->alumnoid).'" AND col_documentoid="'.$docID.'"';
                }else{
                    // Do Insert
                    $queryProcess = 'INSERT INTO tbl_alumnos_documentos (col_alumnoid, col_documentoid, col_original, col_fecha_creacion, col_autor_creacion) VALUES("'.intval($input->alumnoid).'", "'.$docID.'", "'.$accion.'", "'.date("Y-m-d H:i:s").'", "'.$userid.'")';
                }
                $sthp = $this->db->prepare($queryProcess);
                $sthp->execute();
            }

            foreach($input->documentosCopias as $k => $docID) {

                $query = 'SELECT * FROM tbl_alumnos_documentos WHERE col_alumnoid="'.intval($input->alumnoid).'" AND col_documentoid="'.$docID.'"';
                $sth = $this->db->prepare($query);
                $sth->execute();
                if($sth->rowCount() > 0 ) {
                    // Do Update
                    $queryProcess = 'UPDATE tbl_alumnos_documentos SET col_copia="'.$accion.'", col_fecha_cambio="'.date("Y-m-d H:i:s").'", col_autor_cambio="'.$userid.'" WHERE  col_alumnoid="'.intval($input->alumnoid).'" AND col_documentoid="'.$docID.'"';
                }else{
                    // Do Insert
                    $queryProcess = 'INSERT INTO tbl_alumnos_documentos (col_alumnoid, col_documentoid, col_copia, col_fecha_creacion, col_autor_creacion) VALUES("'.intval($input->alumnoid).'", "'.$docID.'", "'.$accion.'", "'.date("Y-m-d H:i:s").'", "'.$userid.'")';
                }
                $sthp = $this->db->prepare($queryProcess);
                $sthp->execute();
            }


            $_response['status'] = 'true';
            $_response['url'] = $apiURL.'/documentos/generarValeDocumentos?id='.$lastID;
        }

        return $this->response->withJson($_response);

    });

    $this->post('/add', function (Request $request, Response $response, $args) {
        global $download_url, $uploaddir;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());


        // Ahora guardamos el nuevo
        $extension = $input->file->extension;
        $filename = 'doc-'.strtotime('now').'.'.$extension;

        // $file_data = explode(',', $input->file->value);
        list($type, $dataFile) = explode(';', $input->file->value);
        list(, $dataFile)      = explode(',', $dataFile);
        $_response['uploaded'] = file_put_contents($uploaddir.$filename, base64_decode($dataFile));

        $query = 'INSERT INTO tbl_documentos (col_nombre, col_obligatorio, col_alumnos, col_descargable, col_filepath, col_filename, col_filetype, col_orden, col_copia, col_created_at, col_created_by, col_updated_at, col_updated_by)
        VALUES(
            "'.utf8_decode($input->nombre).'",
            "'.intval($input->obligatorio).'",
            "'.intval($input->alumnos).'",
            "'.intval($input->descargable).'",
            "'.$filename.'",
            "'.$input->file->filename.'",
            "'.$extension.'",
            "'.intval($input->orden).'",
            "'.intval($input->copia).'",
            "'.date("Y-m-d H:i:s").'",
            "'.$input->userid.'",
            "'.date("Y-m-d H:i:s").'",
            "'.$input->userid.'")';
        $sth = $this->db->prepare($query);

        if($sth->execute()){
            $_response['status'] = 'true';
        }

        return $this->response->withJson($_response);

    });

    $this->delete('/delete', function (Request $request, Response $response, array $args) {
        global $download_url, $uploaddir;

        // Primero borramos el archivo actual si existe
        $query = 'SELECT * FROM tbl_documentos WHERE col_id="'.intval($_REQUEST['id']).'"';
        $fth = $this->db->prepare($query);
        $fth->execute();
        $file = $fth->fetch(PDO::FETCH_OBJ);
        $archivo_viejo = trim($file->col_filepath);
        if($archivo_viejo){
            $_response['viejo'] = $archivo_viejo;
            if(@file_exists($uploaddir.$archivo_viejo)){
                $_response['existe_viejo'] = $archivo_viejo;
                @unlink($uploaddir.$archivo_viejo);
            }
        }

        $query = 'DELETE FROM tbl_documentos WHERE col_id="'.intval($_REQUEST['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        // $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->get('/listHistorialVales', function (Request $request, Response $response, array $args) {
        global $apiURL, $ext_icons;

        $query = "SELECT * FROM tbl_vale_documentos ORDER BY col_id DESC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        $tipo = array(0 => 'Recepción', 1 => 'Entrega');
        foreach($todos as $item){
            $queryAlumno = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval($item['col_alumnoid']).'"';
            $a = $this->db->prepare($queryAlumno);
            $a->execute();
            $alumnoData = $a->fetch(PDO::FETCH_OBJ);
            $url = $apiURL.'/documentos/generarValeDocumentos?id='.$item['col_id'];

            $result[$i]['id'] = $item['col_id'];
            $result[$i]['folio'] = '<span class="text-danger">'.str_pad($item['col_folio'], 5, "0", STR_PAD_LEFT).'</span>';
            $result[$i]['alumno'] = fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos);
            $result[$i]['tipo'] = $tipo[$item['col_tipo']];
            $result[$i]['fecha'] = fechaTexto($item['col_fecha']);
            $result[$i]['opciones'] = '<i class="fa fa-download"></i> <a target="_blank" class="text-secondary" href="'.$url.'">Descargar</a>';
            $i++;
        }
        return $this->response->withJson($result);

    });

    $this->delete('/deleteVale', function (Request $request, Response $response, array $args) {

        $query = 'DELETE FROM tbl_vale_documentos WHERE col_id="'.intval($_REQUEST['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();

        return $this->response->withJson(array('status' => 'true'));

    });
});
