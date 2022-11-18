<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de usuarios.
 *
 * Lista de funciones
 *
 * /usuarios
 * - /desbloquear
 * - /listBloqueados
 * - /listMaestros
 * - /listDepartamentos
 * - /list
 * - /get
 * - /accept
 * - /reset
 * - /update
 * - /updatePassword
 * - /add
 * - /delete
 * - /perfil
 * - /update
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/usuarios', function () {

    $this->delete('/desbloquear', function (Request $request, Response $response, array $args) {
        global $dblog;

        $_REQUEST['correo'] = filter_var($_REQUEST['correo'], FILTER_SANITIZE_EMAIL);

        $query = 'DELETE FROM tbl_bitacora_ingresos WHERE col_correo="'.$_REQUEST['correo'].'"';

        $dblog = new DBLog($query, 'tbl_bitacora_ingresos', '', '', 'Bitacora Acceso', $this->db);
        $dblog->where = array('col_correo' => intval($_REQUEST['correo']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();


        return $this->response->withJson(array('status' => 'true'));

    });

    $this->get('/listBloqueados', function (Request $request, Response $response, array $args) {
        $sth = $this->db->prepare("SELECT * FROM tbl_bitacora_ingresos WHERE col_estatus=0 ORDER BY col_id");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['correo'] = fixEncode($item['col_correo']);
            $result[$i]['pass'] = fixEncode($item['col_pass']);
            $result[$i]['ip'] = $item['col_ip'];
            $result[$i]['fecha'] = fechaTexto($item['col_fecha']);
            $i++;
        }

        return $this->response->withJson($result);
    });

    $this->get('/listMaestros', function (Request $request, Response $response, array $args) {


        $sth = $this->db->prepare("SELECT u.*, m.col_contratado, CASE u.col_type ".
        "WHEN '1' THEN 'Administrador' ".
        "WHEN '2' THEN 'Visor' ".
        "WHEN '3'  THEN 'Editor' ".
        "WHEN '4'  THEN 'Maestro' ".
        "END AS col_name_type, CASE u.col_status ".
        "WHEN '0' THEN 'Inactivo' ".
        "WHEN '1' THEN 'Activo' ".
        "END AS col_estatus, CONCAT(u.col_firstname, ' ', u.col_lastname) AS col_fullname ".
        "FROM tbl_users u ".
        "LEFT OUTER JOIN tbl_maestros m ON m.col_userid=u.col_id WHERE u.col_maestro='1' AND m.col_contratado='1' AND u.col_id>'1' ORDER BY u.col_id");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = fixEncode($item['col_firstname']." ".$item['col_lastname']);
            $result[$i]['text'] = fixEncode($item['col_firstname']." ".$item['col_lastname']);
            $i++;
        }

        return $this->response->withJson($result);

    });


    $this->get('/listDepartamentos', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_departamentos ORDER BY col_nombre ASC");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = fixEncode($item['col_nombre']);
            $result[$i]['text'] = fixEncode($item['col_nombre']);
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/list', function (Request $request, Response $response, array $args) {

        switch($_REQUEST['type']){
            case 1:
            $where = ' WHERE u.col_maestro="0" AND u.col_id>1';
            break;
            case 2:
            $where = ' WHERE u.col_maestro="1" AND u.col_id>1';
            break;
            case 3:
            $where = ' WHERE u.col_maestro="1" AND m.col_contratado="1" AND u.col_id>1';
            break;
            case 4:
            $where = ' WHERE u.col_maestro="1" AND m.col_contratado="0"';
            break;

            case 0:
            $where = ' WHERE u.col_id>1';
            break;

            default:
            $where = ' WHERE u.col_id>1';
            break;
        }

        $sth = $this->db->prepare("SELECT u.*, d.col_nombre AS departamento, m.col_contratado, CASE u.col_status ".
        "WHEN '0' THEN 'Inactivo' ".
        "WHEN '1' THEN 'Activo' ".
        "END AS col_estatus, CONCAT(u.col_firstname, ' ', u.col_lastname) AS col_fullname ".
        "FROM tbl_users u ".
        "LEFT OUTER JOIN tbl_maestros m ON m.col_userid=u.col_id ".
        "LEFT OUTER JOIN tbl_departamentos d ON d.col_id=u.col_depto ".
        $where." ORDER BY u.col_id");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        foreach($todos as $item){
            if($item[col_id] == 1) continue;
            $result[$i]['col_id'] = $item[col_id];
            $result[$i]['col_fullname'] = fixEncode($item[col_fullname]);
            if($item['col_maestro'] == 1){
                if($item['col_contratado'] == 0){
                    $result[$i]['col_fullname'] = $result[$i]['col_fullname'].' <small><i class="fas fa-user-times text-danger"></i></small>';
                }else{
                    $result[$i]['col_fullname'] = $result[$i]['col_fullname'].' <small><i class="fas fa-user-check text-success"></i></small>';
                }
            }

            $result[$i]['col_email'] = $item[col_email];
            $result[$i]['col_phone'] = $item[col_phone];
            if($item[col_maestro] == 1){
                $result[$i]['depto'] = 'Maestro';
            }else{
                $result[$i]['depto'] = fixEncode($item[departamento]);
            }
            $result[$i]['col_estatus'] = $item[col_estatus];
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->post('/get', function (Request $request, Response $response, array $args) {
        global $download_url;

        $input = $request->getParsedBody();
        // $userID = getCurrentUserID();
        $userID = intval($input['params']['id']);

        $query = 'SELECT u.*, m.col_edit_planeaciones, m.col_edit_asistencias, m.col_edit_calificaciones, m.col_contratado, m.fileCV, m.fileActaNacimiento, m.fileINE, m.fileTituloLicenciatura, m.fileCedulaLicenciatura, m.fileGradoMaestria, m.fileCedulaMaestria, m.fileGradoDoctorado, m.fileCedulaDoctorado, m.fileContratoColaboracion, m.col_costo_clase, m.col_costo_clase_academia, m.col_costo_clase_postgrado'
        .' FROM tbl_users u LEFT OUTER JOIN tbl_maestros m ON m.col_userid=u.col_id WHERE u.col_id="'.intval($userID).'"';

        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);
        $data->col_cedula = fixEncode($data->col_cedula);
        $data->col_firstname = fixEncode($data->col_firstname);
        $data->col_lastname = fixEncode($data->col_lastname);
        $data->col_estado = fixEncode($data->col_estado);
        $data->col_ciudad = fixEncode($data->col_ciudad);
        $data->col_direccion = fixEncode($data->col_direccion);
        $data->col_titulo = fixEncode($data->col_titulo);
        $data->col_estudios = fixEncode($data->col_estudios);
        $data->is_alumno = 'false';
        unset($data->col_pass);

        $data->fileCV = ($data->fileCV != ''?$download_url.$data->fileCV:'');
        $data->fileActaNacimiento = ($data->fileActaNacimiento != ''?$download_url.$data->fileActaNacimiento:'');
        $data->fileINE = ($data->fileINE != ''?$download_url.$data->fileINE:'');
        $data->fileTituloLicenciatura = ($data->fileTituloLicenciatura != ''?$download_url.$data->fileTituloLicenciatura:'');
        $data->fileCedulaLicenciatura = ($data->fileCedulaLicenciatura != ''?$download_url.$data->fileCedulaLicenciatura:'');
        $data->fileGradoMaestria = ($data->fileGradoMaestria != ''?$download_url.$data->fileGradoMaestria:'');
        $data->fileCedulaMaestria = ($data->fileCedulaMaestria != ''?$download_url.$data->fileCedulaMaestria:'');
        $data->fileGradoDoctorado = ($data->fileGradoDoctorado != ''?$download_url.$data->fileGradoDoctorado:'');
        $data->fileCedulaDoctorado = ($data->fileCedulaDoctorado != ''?$download_url.$data->fileCedulaDoctorado:'');
        $data->fileContratoColaboracion = ($data->fileContratoColaboracion != ''?$download_url.$data->fileContratoColaboracion:'');

        $userType = getCurrentUserType();
        if($userType == 'maestro') {
            $data->evaMaestrosDisponible = "no";
            $data->evaMaestrosID = 0;
            $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
            $c = $this->db->prepare($query);
            $c->execute();
            $r = $c->fetch(PDO::FETCH_OBJ);
            $grupos_periodos = array($r->col_periodo, $r->col_periodo_cuatri, $r->col_periodo_maestria, $r->col_periodo_doctorado);

            // $query = 'SELECT * FROM tbl_eva_maestros WHERE col_estatus="2" AND col_group_periodoid IN ('.implode(',', array_unique($grupos_periodos)).') ORDER BY col_id DESC';
            $subquery = 'SELECT col_id FROM tbl_eva_maestros WHERE col_estatus="2" AND col_group_periodoid IN ('.implode(',', array_unique($grupos_periodos)).') ORDER BY col_id DESC';
            // $fth = $this->db->prepare($query);
            // $fth->execute();
            // if($fth->rowCount()){
                // $dataEvaMaestro = $fth->fetch(PDO::FETCH_OBJ);
                $query = 'SELECT * FROM tbl_eva_maestros_observaciones WHERE col_evaid IN ('.$subquery.') AND col_maestroid="'.$userID.'" AND col_estatus=1 ORDER BY col_evaid DESC LIMIT 1';
                $fth = $this->db->prepare($query);
                $fth->execute();
                if($fth->rowCount()){
                    $dataEvaMaestro = $fth->fetch(PDO::FETCH_OBJ);
                    $data->evaMaestrosDisponible = "si";
                    $data->evaMaestrosID = $dataEvaMaestro->col_evaid;
                }
            //}
            $data->hasAcademias = hasAcademias($userID, $this->db);
            $data->hasTalleres = hasTalleres($userID, $this->db);
            $data->hasClubLectura = hasClub($userID, $this->db);
        }



        $data->debug = $_SERVER['HTTP_HOST'];
        // if($userID == 1) {
        //     $data->debug = $_SERVER;
        // }

        $data->menu = getMenu($this->db);

        return $this->response->withJson($data);

    });

    $this->post('/accept', function (Request $request, Response $response, array $args) {
        global $dblog;

        $input = $request->getParsedBody();

        $tipo = getCurrentUserType();
        if($tipo === 'alumno'){

            $query = 'UPDATE tbl_alumnos SET
            col_primer_acceso="'.date("Y-m-d").'",
            col_updated_at="'.date("Y-m-d H:i:s").'",
            col_updated_by="'.intval($input['params']['id']).'" WHERE col_id="'.intval($input['params']['id']).'"';

        }else{
            $query = 'UPDATE tbl_users SET
            col_primer_acceso="'.date("Y-m-d").'",
            col_updated_at="'.date("Y-m-d H:i:s").'",
            col_updated_by="'.intval($input['params']['id']).'" WHERE col_id="'.intval($input['params']['id']).'"';
        }

        $dblog = new DBLog($query, 'tbl_users', '', '', 'Usuarios', $this->db);
        $dblog->where = array('col_id' => intval($input['params']['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        if($sth->execute()){
            $dblog->saveLog();
            $_response['status'] = 'success';
            // $_response['query'] = $query;
        }

        return $this->response->withJson($_response);

    });

    $this->put('/reset', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        if(isAdmin()) {
            $userID = getCurrentUserID();

            $query = 'UPDATE tbl_users SET col_pass="'.md5('fldch2019+').'", col_password_lastchage="0000-00-00 00:00:00", col_primer_acceso="0000-00-00", col_updated_by="'.$userID.'", col_updated_at="'.date("Y-m-d H:i:s").'"  WHERE col_id="'.$input->id.'"';

            $dblog = new DBLog($query, 'tbl_users', '', '', 'Usuarios', $this->db);
            $dblog->where = array('col_id' => intval($input->id));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->saveLog();

            $_response['status'] = 'true';
        }

        return $this->response->withJson($_response);
    });

    $this->put('/update', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        if($input->password != '') {
            $_response['strengh'] = checkPassword($input->password);
            if($_response['strengh'] != 'safe') return $this->response->withJson($_response);
        }
        $query = 'SELECT * FROM tbl_users WHERE col_id="'.$input->id.'"';
        $fth = $this->db->prepare($query);
        $fth->execute();
        $currentUserData = $fth->fetch(PDO::FETCH_OBJ);

        $validarCorreo = checarCorreo($input->correo, $this->db);
        if($validarCorreo['status'] !== false && $input->id != $validarCorreo['recordID']) {
            $_response['status'] = 'exists';
            $_response['mensaje'] = $validarCorreo['mensaje'];
            return $this->response->withJson($_response);
        }

        if($input->esMaestro == 1){

            $query = 'SELECT * FROM tbl_maestros WHERE col_userid="'.$input->id.'"';
            $fth = $this->db->prepare($query);
            $fth->execute();
            $file = $fth->fetch(PDO::FETCH_OBJ);
            if(!$file) {
                $queryInsertMaestro = 'INSERT INTO tbl_maestros (col_userid) VALUES('.$input->id.')';
                $insertMaestro = $this->db->prepare($queryInsertMaestro);
                $insertMaestro->execute();
            }
            $currentMaestro = $file;

            $source_fileCV = uploadFile($input->fileCV, $currentMaestro->fileCV, 'docmaestro');
            $source_fileActaNacimiento = uploadFile($input->fileActaNacimiento, $currentMaestro->fileActaNacimiento, 'docmaestro');
            $source_fileINE = uploadFile($input->fileINE, $currentMaestro->fileINE, 'docmaestro');
            $source_fileTituloLicenciatura = uploadFile($input->fileTituloLicenciatura, $currentMaestro->fileTituloLicenciatura, 'docmaestro');
            $source_fileCedulaLicenciatura = uploadFile($input->fileCedulaLicenciatura, $currentMaestro->fileCedulaLicenciatura, 'docmaestro');
            $source_fileGradoMaestria = uploadFile($input->fileGradoMaestria, $currentMaestro->fileGradoMaestria, 'docmaestro');
            $source_fileCedulaMaestria = uploadFile($input->fileCedulaMaestria, $currentMaestro->fileCedulaMaestria, 'docmaestro');
            $source_fileGradoDoctorado = uploadFile($input->fileGradoDoctorado, $currentMaestro->fileGradoDoctorado, 'docmaestro');
            $source_fileCedulaDoctorado = uploadFile($input->fileCedulaDoctorado, $currentMaestro->fileCedulaDoctorado, 'docmaestro');
            $source_fileContratoColaboracion = uploadFile($input->fileContratoColaboracion, $currentMaestro->fileContratoColaboracion, 'docmaestro');
            if(!isset($input->editCalificaciones)){
                $editCalificaciones = $currentMaestro->col_edit_calificaciones;
            }else{
                $editCalificaciones = intval($input->editCalificaciones);
            }
            if(!isset($input->editAsistencias)){
                $editAsistencias = $currentMaestro->col_edit_asistencias;
            }else{
                $editAsistencias = intval($input->editAsistencias);
            }

            if(!isset($input->editPlaneaciones)){
                $editPlaneaciones = $currentMaestro->col_edit_planeaciones;
            }else{
                $editPlaneaciones = intval($input->editPlaneaciones);
            }

        }
        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        if($userType == 'administrativo') {
            if(isset($input->depto)) {
                $query = 'UPDATE tbl_users SET
                col_cedula="'.utf8_decode($input->cedula).'",
                col_titulo="'.utf8_decode($input->titulo).'",
                col_firstname="'.utf8_decode($input->nombres).'",
                col_lastname="'.utf8_decode($input->apellidos).'",
                col_email="'.$input->correo.'",
                col_phone="'.$input->telefono.'",
                col_phone="'.$input->telefono.'",
                col_ext="'.$input->ext.'",
                col_type="'.$input->tipo.'",
                col_genero="'.$input->genero.'",
                col_rfc="'.$input->rfc.'",
                col_nss="'.$input->nss.'",
                col_sangre="'.$input->sangre.'",
                col_nomina="'.$input->nomina.'",
                col_pais="'.$input->pais.'",
                col_estado="'.utf8_decode(($input->otroestado?$input->otroestado:$input->estado)).'",
                col_ciudad="'.utf8_decode($input->ciudad).'",
                col_direccion="'.utf8_decode($input->direccion).'",
                col_celular="'.$input->celular.'",
                col_911_nombre="'.$input->nombre911.'",
                col_911_telefono="'.$input->telefono911.'",
                col_911_celular="'.$input->celular911.'",
                col_patronal="'.$input->patronal.'",
                col_fecha_ingreso="'.substr($input->fechaIngreso[0], 0, 10).'",
                col_fecha_termino="'.substr($input->fechaTermino[0], 0, 10).'",
                col_fecha_ingreso_semestral="'.substr($input->fechaIngresoSemestral[0], 0, 10).'",
                col_fecha_termino_semestral="'.substr($input->fechaTerminoSemestral[0], 0, 10).'",
                col_fecha_ingreso_cuatri="'.substr($input->fechaIngresoCuatrimestral[0], 0, 10).'",
                col_fecha_termino_cuatri="'.substr($input->fechaTerminoCuatrimestral[0], 0, 10).'",
                col_fecha_nacimiento="'.substr($input->fechaNacimiento[0], 0, 10).'",
                col_estudios="'.$input->estudios.'",
                col_perfil_profesional="'.$input->perfilProfesional.'",
                col_dependencia="'.$input->dependencia.'",
                col_status="'.$input->estatus.'",
                col_depto="'.$input->depto.'",
                col_maestro="'.$input->esMaestro.'",
                col_curp="'.$input->curp.'",
                col_updated_at="'.date("Y-m-d H:i:s").'",
                col_updated_by="'.$input->userid.'" WHERE col_id="'.$input->id.'"';
            }else{
                $query = 'UPDATE tbl_users SET
                col_cedula="'.utf8_decode($input->cedula).'",
                col_firstname="'.utf8_decode($input->nombres).'",
                col_lastname="'.utf8_decode($input->apellidos).'",
                col_email="'.$input->correo.'",
                col_phone="'.$input->telefono.'",
                col_phone="'.$input->telefono.'",
                col_ext="'.$input->ext.'",
                col_type="'.$input->tipo.'",
                col_genero="'.$input->genero.'",
                col_rfc="'.$input->rfc.'",
                col_nss="'.$input->nss.'",
                col_sangre="'.$input->sangre.'",
                col_nomina="'.$input->nomina.'",
                col_pais="'.$input->pais.'",
                col_estado="'.utf8_decode(($input->otroestado?$input->otroestado:$input->estado)).'",
                col_ciudad="'.utf8_decode($input->ciudad).'",
                col_direccion="'.utf8_decode($input->direccion).'",
                col_celular="'.$input->celular.'",
                col_911_nombre="'.$input->nombre911.'",
                col_911_telefono="'.$input->telefono911.'",
                col_911_celular="'.$input->celular911.'",
                col_patronal="'.$input->patronal.'",
                col_fecha_ingreso="'.substr($input->fechaIngreso[0], 0, 10).'",
                col_fecha_termino="'.substr($input->fechaTermino[0], 0, 10).'",
                col_fecha_ingreso_semestral="'.substr($input->fechaIngresoSemestral[0], 0, 10).'",
                col_fecha_termino_semestral="'.substr($input->fechaTerminoSemestral[0], 0, 10).'",
                col_fecha_ingreso_cuatri="'.substr($input->fechaIngresoCuatrimestral[0], 0, 10).'",
                col_fecha_termino_cuatri="'.substr($input->fechaTerminoCuatrimestral[0], 0, 10).'",
                col_fecha_nacimiento="'.substr($input->fechaNacimiento[0], 0, 10).'",
                col_estudios="'.$input->estudios.'",
                col_perfil_profesional="'.$input->perfilProfesional.'",
                col_dependencia="'.$input->dependencia.'",
                col_status="'.$input->estatus.'",
                col_maestro="'.$input->esMaestro.'",
                col_curp="'.$input->curp.'",
                col_updated_at="'.date("Y-m-d H:i:s").'",
                col_updated_by="'.$input->userid.'" WHERE col_id="'.$input->id.'"';
            }

        }
        if($userType == 'maestro') {
            $query = 'UPDATE tbl_users SET
            col_cedula="'.utf8_decode($input->cedula).'",
            col_firstname="'.utf8_decode($input->nombres).'",
            col_lastname="'.utf8_decode($input->apellidos).'",
            col_email="'.$input->correo.'",
            col_phone="'.$input->telefono.'",
            col_phone="'.$input->telefono.'",
            col_ext="'.$input->ext.'",
            col_type="'.$input->tipo.'",
            col_genero="'.$input->genero.'",
            col_rfc="'.$input->rfc.'",
            col_nss="'.$input->nss.'",
            col_sangre="'.$input->sangre.'",
            col_nomina="'.$input->nomina.'",
            col_pais="'.$input->pais.'",
            col_estado="'.utf8_decode(($input->otroestado?$input->otroestado:$input->estado)).'",
            col_ciudad="'.utf8_decode($input->ciudad).'",
            col_direccion="'.utf8_decode($input->direccion).'",
            col_celular="'.$input->celular.'",
            col_911_nombre="'.$input->nombre911.'",
            col_911_telefono="'.$input->telefono911.'",
            col_911_celular="'.$input->celular911.'",
            col_patronal="'.$input->patronal.'",
            col_fecha_ingreso="'.substr($input->fechaIngreso[0], 0, 10).'",
            col_fecha_termino="'.substr($input->fechaTermino[0], 0, 10).'",
            col_fecha_ingreso_semestral="'.substr($input->fechaIngresoSemestral[0], 0, 10).'",
            col_fecha_termino_semestral="'.substr($input->fechaTerminoSemestral[0], 0, 10).'",
            col_fecha_ingreso_cuatri="'.substr($input->fechaIngresoCuatrimestral[0], 0, 10).'",
            col_fecha_termino_cuatri="'.substr($input->fechaTerminoCuatrimestral[0], 0, 10).'",
            col_fecha_nacimiento="'.substr($input->fechaNacimiento[0], 0, 10).'",
            col_estudios="'.$input->estudios.'",
            col_perfil_profesional="'.$input->perfilProfesional.'",
            col_dependencia="'.$input->dependencia.'",
            col_curp="'.$input->curp.'",
            col_updated_at="'.date("Y-m-d H:i:s").'",
            col_updated_by="'.$input->userid.'" WHERE col_id="'.$input->id.'"';
        }

        $lastUserid = $input->id;

        $dblog = new DBLog($query, 'tbl_users', '', '', 'Usuarios', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);


        if($input->fotoPerfil->value) {
            $fotoPerfil = uploadFile($input->fotoPerfil, $currentUserData->col_image, 'avatar', 'avatars');
            $query = 'UPDATE tbl_users SET
            col_image="avatars/'.$fotoPerfil.'",
            col_updated_at="'.date("Y-m-d H:i:s").'",
            col_updated_by="'.$input->userid.'" WHERE col_id="'.$input->id.'"';
            $sthFotoPerfil = $this->db->prepare($query);
            $sthFotoPerfil->execute();
        }

        if($input->password != ''){
            $query = 'UPDATE tbl_users SET
            col_pass="'.md5($input->password).'",
            col_password_lastchage="'.date("Y-m-d H:i:s").'",
            col_updated_at="'.date("Y-m-d H:i:s").'",
            col_updated_by="'.$input->_userid.'" WHERE col_id="'.$input->id.'"';
            $pass = $this->db->prepare($query);
            $pass->execute();
        }

        if($input->foto){

            $query = 'UPDATE tbl_users SET
            col_image="avatars/'.$input->foto->filename.'" WHERE col_id="'.$input->id.'"';
            $avatar = $this->db->prepare($query);
            $avatar->execute();
            list($type, $dataFile) = explode(';', $input->foto->value);
            list(, $dataFile)      = explode(',', $dataFile);
            $_response['uploaded'] = file_put_contents($_SERVER['DOCUMENT_ROOT'].'/universidad/dist/avatars/'.$input->foto->filename, base64_decode($dataFile));

        }

        if($input->esMaestro == 1){
            $query = 'UPDATE tbl_maestros SET '.
            'col_costo_clase="'.$input->costoClase.'", '.
            'col_contratado="'.$input->contratado.'", '.
            'col_costo_clase_academia="'.$input->costoClaseAcademia.'", '.
            'col_costo_clase_postgrado="'.$input->costoClasePostgrado.'", '.
            'fileCV="'.$source_fileCV.'", '.
            'fileActaNacimiento="'.$source_fileActaNacimiento.'", '.
            'fileINE="'.$source_fileINE.'", '.
            'fileTituloLicenciatura="'.$source_fileTituloLicenciatura.'", '.
            'fileCedulaLicenciatura="'.$source_fileCedulaLicenciatura.'", '.
            'fileGradoMaestria="'.$source_fileGradoMaestria.'", '.
            'fileCedulaMaestria="'.$source_fileCedulaMaestria.'", '.
            'fileGradoDoctorado="'.$source_fileGradoDoctorado.'", '.
            'fileCedulaDoctorado="'.$source_fileCedulaDoctorado.'", '.
            'fileContratoColaboracion="'.$source_fileContratoColaboracion.'", '.
            'col_edit_calificaciones="'.$editCalificaciones.'", '.
            'col_edit_asistencias="'.$editAsistencias.'", '.
            'col_edit_planeaciones="'.$editPlaneaciones.'" '.
            'WHERE col_userid="'.$input->id.'"';

            $dblogMaestros = new DBLog($query, 'tbl_maestros', '', '', 'Maestros', $this->db);
            $dblogMaestros->where = array('col_userid' => intval($input->id));
            $dblogMaestros->prepareLog();

            $q_maestro = $this->db->prepare($query);
            $q_maestro->execute();

            $dblogMaestros->saveLog();
        }

        if($sth->execute()){
            $_response['status'] = 'true';
            $dblog->saveLog();
        }

        return $this->response->withJson($_response);

    });

    $this->post('/updatePassword', function (Request $request, Response $response, $args) {
        global $dblog;

        $input = json_decode($request->getBody());
        $tipo = getCurrentUserType();
        $id = getCurrentUserID();

        $_response['strengh'] = checkPassword($input->params->pass);
        if($_response['strengh'] != 'safe') return $this->response->withJson($_response);

        $pass = md5($input->params->pass);
        if($tipo == 'alumno') {
            $query = 'UPDATE tbl_alumnos SET
            col_password="'.$pass.'",
            col_password_lastchage="'.date("Y-m-d H:i:s").'",
            col_updated_at="'.date("Y-m-d H:i:s").'",
            col_updated_by="'.$id.'" WHERE col_id="'.$id.'"';

            $dblog = new DBLog($query, 'tbl_alumnos', '', '', 'Alumnos', $this->db);
            $dblog->where = array('col_id' => intval($id));
            $dblog->prepareLog();

        }else{
            $query = 'UPDATE tbl_users SET
            col_pass="'.$pass.'",
            col_password_lastchage="'.date("Y-m-d H:i:s").'",
            col_updated_at="'.date("Y-m-d H:i:s").'",
            col_updated_by="'.$id.'" WHERE col_id="'.$id.'"';

            $dblog = new DBLog($query, 'tbl_users', '', '', 'Usuarios', $this->db);
            $dblog->where = array('col_id' => intval($id));
            $dblog->prepareLog();
        }



        $pass = $this->db->prepare($query);
        $pass->execute();
        $dblog->saveLog();

        //$_response['status'] = 'success';


        // Refresh Token
        $key = 'e-N6RfEM*@!QAOT&eMF$5*DqDvpk4)SR';
        $token = getBearerToken();
        $data = JWT::decode($token, $key, array('HS256'));

        $data->secret = $input->params->pass;

            $data->exp = strtotime('+2 hour');
            // $_response['response'] = 'valid';
            $_response['new_token'] = $data;
            $_response['token'] = JWT::encode($data, $key);
            $_response['status'] = 'success';



        return $this->response->withJson($_response);
    });

    $this->post('/add', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        /*
        $query = 'SELECT * FROM tbl_users WHERE col_email="'.$input->correo.'"';
        $fth = $this->db->prepare($query);
        $fth->execute();
        if($fth->rowCount()) {
            $_response['status'] = 'exists';
            return $this->response->withJson($_response);
        }
        */

        $validarCorreo = checarCorreo($input->correo, $this->db);
        if($validarCorreo['status'] !== false) {
            $_response['status'] = 'exists';
            $_response['mensaje'] = $validarCorreo['mensaje'];
            return $this->response->withJson($_response);
        }


        if($input->password != '') {
            $_response['strengh'] = checkPassword($input->password);
            if($_response['strengh'] != 'safe') return $this->response->withJson($_response);
        }

        $data = array(
            "col_cedula" => utf8_decode($input->cedula),
            "col_titulo" => utf8_decode($input->titulo),
            "col_firstname" => utf8_decode($input->nombres),
            "col_lastname" => utf8_decode($input->apellidos),
            "col_pass" => md5($input->password),
            "col_email" => $input->correo,
            "col_phone" => $input->telefono,
            "col_ext" => $input->ext,
            "col_type" => 0,
            "col_genero" => $input->genero,
            "col_rfc" => $input->rfc,
            "col_nss" => $input->nss,
            "col_sangre" => $input->sangre,
            "col_nomina" => $input->nomina,
            "col_pais" => $input->pais,
            "col_estado" => utf8_decode(($input->otroestado?$input->otroestado:$input->estado)),
            "col_ciudad" => utf8_decode($input->ciudad),
            "col_direccion" => utf8_decode($input->direccion),
            "col_celular" => $input->celular,
            "col_911_nombre" => utf8_encode($input->nombre911),
            "col_911_telefono" => $input->telefono911,
            "col_911_celular" => $input->celular911,
            "col_patronal" => $input->patronal,
            "col_fecha_ingreso" => substr($input->fechaIngreso[0], 0, 10),
            "col_fecha_termino" => substr($input->fechaTermino[0], 0, 10),
            "col_fecha_ingreso_semestral" => substr($input->fechaIngresoSemestral[0], 0, 10),
            "col_fecha_termino_semestral" => substr($input->fechaTerminoSemestral[0], 0, 10),
            "col_fecha_ingreso_cuatri" => substr($input->fechaIngresoCuatrimestral[0], 0, 10),
            "col_fecha_termino_cuatri" => substr($input->fechaTerminoCuatrimestral[0], 0, 10),
            "col_fecha_nacimiento" => substr($input->fechaNacimiento[0], 0, 10),
            "col_estudios" => $input->estudios,
            "col_perfil_profesional" => $input->perfilProfesional,
            "col_dependencia" => $input->dependencia,
            "col_status" => $input->estatus,
            "col_depto" => $input->depto,
            "col_maestro" => $input->esMaestro,
            "col_curp" => $input->curp,
            "col_created_at" => date("Y-m-d H:i:s"),
            "col_created_by" => $input->userid,
            "col_updated_at" => date("Y-m-d H:i:s"),
            "col_updated_by" => $input->userid,
        );

        $query = 'INSERT INTO tbl_users ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';

        $dblog = new DBLog($query, 'tbl_users', '', '', 'Usuarios', $this->db);
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);


        if($sth->execute()){
            $lastInsertId = $this->db->lastInsertId();
            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();

            if($input->esMaestro == 1){
                $query = 'INSERT INTO tbl_maestros (col_userid, col_costo_clase, col_costo_clase_academia, col_costo_clase_postgrado, col_contratado, col_created_at, col_created_by, col_updated_at, col_updated_by)
                VALUES("'.$lastInsertId.'", "'.$input->costoClase.'", "'.$input->costoClaseAcademia.'", "'.$input->costoClasePostrado.'", "'.$input->contratado.'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'")';

                $dblog = new DBLog($query, 'tbl_maestros', '', '', 'Maestros', $this->db);
                $dblog->prepareLog();

                $ma = $this->db->prepare($query);
                $ma->execute();

                $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                $dblog->saveLog();
                $maestros++;
            }
            $_response['status'] = 'true';
        }

        $_response['query'] = $query;
        // $_response['data_query'] = $data;

        return $this->response->withJson($_response);

    });

    $this->delete('/delete', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_users WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_users', '', '', 'Usuarios', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();


        $query = 'DELETE FROM tbl_maestros WHERE col_userid="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_maestros', '', '', 'Maestros', $this->db);
        $dblog->where = array('col_userid' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();
        // $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson(array('status' => 'true'));

    });
});

$app->group('/perfil', function () {

    $this->put('/update', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $validarCorreo = checarCorreo($input->correo, $this->db);
        if($validarCorreo['status'] !== false && $input->id != $validarCorreo['recordID']) {
            $_response['status'] = 'exists';
            $_response['mensaje'] = $validarCorreo['mensaje'];
            return $this->response->withJson($_response);
        }

        $query = 'UPDATE tbl_users SET
        col_firstname="'.$input->nombres.'",
        col_lastname="'.$input->apellidos.'",
        col_email="'.$input->correo.'",
        col_phone="'.$input->telefono.'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$input->id.'" WHERE col_id="'.$input->id.'"';

        $dblog = new DBLog($query, 'tbl_users', '', '', 'Usuarios', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);

        if($input->password != ''){
            $query = 'UPDATE tbl_users SET
            col_pass="'.md5($input->password).'",
            col_updated_at="'.date("Y-m-d H:i:s").'",
            col_updated_by="'.$input->id.'" WHERE col_id="'.$input->id.'"';

            $dblogPass = new DBLog($query, 'tbl_users', '', '', 'Usuarios', $this->db);
            $dblogPass->where = array('col_id' => intval($input->id));
            $dblogPass->prepareLog();

            $pass = $this->db->prepare($query);
            $pass->execute();

            $dblogPass->saveLog();
        }

        if($input->foto){

            $query = 'UPDATE tbl_users SET
            col_image="avatars/'.$input->foto->filename.'" WHERE col_id="'.$input->id.'"';

            $dblogAvatar = new DBLog($query, 'tbl_users', '', '', 'Usuarios', $this->db);
            $dblogAvatar->where = array('col_id' => intval($input->id));
            $dblogAvatar->prepareLog();

            $avatar = $this->db->prepare($query);
            $avatar->execute();
            list($type, $dataFile) = explode(';', $input->foto->value);
            list(, $dataFile)      = explode(',', $dataFile);
            $_response['uploaded'] = file_put_contents($_SERVER['DOCUMENT_ROOT'].'/universidad/dist/avatars/'.$input->foto->filename, base64_decode($dataFile));

            $dblogAvatar->saveLog();

        }

        if($sth->execute()){
            $dblog->saveLog();
            $_response['status'] = 'true';
        }

        return $this->response->withJson($_response);

    });

});
//Termina routes.usuarios.php
