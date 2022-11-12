<?php

/**
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de atención de alumnos, mismo que es visible para administrativos.
 *
 * Lista de funciones
 *
 *  /atencion
 *  - /listAlumnos
 *  - /listUsuarios
 *  - /list
 *  - /get
 *  - /update
 *  - /add
 *  - /delete
 *  - /amonestacion
 *  - /psicopedagogica
 *  - /coordinacion
 *  - /inasistencias
 *  - /modelo
 *  - /historial
 *  - /boleta
 *  - /kardex
 *  - /constancia
 *  - /carta
 *  - /constancia
 *  - /diploma1
 *  - /certificado
 *  - /certificado
 *  - /constancia
 *  - /constancia
 *  - /registro
 *  - /formato
 *  - /constancia
 *  - /constancia
 *  - /formato
 *  - /formato
 *  - /constancia
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

set_time_limit(0);


$tiposDocumentosNombres = array(
    0 => 'Sin Especificar',
    1 => 'Psicopedagogía',
    2 => 'Coordinación Academica',
    3 => 'Incumplimiento Modelo Educativo',
    4 => 'Inasistencias',
    5 => 'Medida Disciplinaria',
    6 => 'Historial Academico',
    7 => 'Kardex',
    8 => 'Boleta de Calificaciones',
    9 => 'Constancia de Servicio Social',
    10 => 'Carta Pasante',
    11 => 'Constancia de Terminación de Estudios (Antiguo)',
    12 => 'Diploma (Licenciatura y Posgrados)',
    13 => 'Certificado Parcial',
    14 => 'Certificado Total y Duplicado',
    16 => 'Constancia con Calificaciones',
    17 => 'Registro de Escolaridad',
    18 => 'Formato de Buena Conducta',
    19 => 'Constancia de Sustentante',
    20 => 'Constancia de Aprobación de Sinodales',
    21 => 'Formato de Toma de Protesta',
    22 => 'Constancia de Examen General de Conocimientos',
    23 => 'Formato de Toma de Protesta para Posgrados',
    24 => 'Dictamen de Titulación',
    // 25 => 'constancia-estudios',
    15 => 'Constancia de Estudios Sencilla',
    28 => 'Constancia de Estudios Termino de Semestre',
    26 => 'Constancia de Estudios Termino de Carrera',
    27 => 'Constancia de Estudios Trámite de Titulación',
    29 => 'Acta Examen Posgrado',
    30 => 'Acta Examen Licenciatura',
    31 => 'Constancia de Desempeño'
);
$tiposDocumentosURL = array(
    0 => 'Sin Especificar',
    1 => 'psicopedagogica',
    2 => 'coordinacion',
    3 => 'modelo-educativo',
    4 => 'inasistencias',
    5 => 'amonestacion',
    6 => 'historial-academico',
    7 => 'kardex',
    8 => 'boleta',
    9 => 'constancia-servicio',
    10 => 'carta-pasante',
    11 => 'constancia-terminacion',
    12 => 'diploma1',
    13 => 'certificado-parcial',
    14 => 'certificado-total',
    16 => 'constancia-calificaciones',
    17 => 'registro-escolaridad',
    18 => 'formato-buena-conducta',
    19 => 'constancia-sustentante',
    20 => 'constancia-aprobacion-sinodales',
    21 => 'formato-toma-protesta',
    22 => 'constancia-examen-conocimientos',
    23 => 'formato-toma-protesta-posgrado',
    24 => 'dictamen-titulacion',
    //25 => 'constancia-estudios',
    15 => 'constancia-sencilla',
    28 => 'constancia-estudios-termino-semestre',
    26 => 'constancia-termino-estudios',
    27 => 'constancia-tramite-titulacion',
    29 => 'acta-examen-posgrado',
    30 => 'acta-examen-licenciatura',
    31 => 'constancia-desempeno',
);


$app->group('/atencion', function () {

    $this->get('/listAlumnos', function (Request $request, Response $response, array $args) {

        $periodosActivos = getCurrentPeriodos($this->db);
        // $sth = $this->db->prepare("SELECT *, CONCAT(col_nombres, ' ', col_apellidos) AS col_fullname FROM tbl_alumnos WHERE col_estatus='activo' AND col_periodoid IN (".implode(',', $periodosActivos).") ORDER BY col_id DESC");
        $sth = $this->db->prepare("SELECT a.*, CONCAT(a.col_nombres, ' ', a.col_apellidos) AS col_fullname, p.col_grado AS gradoPeriodo, p.col_nombre AS nombrePeriodo, p.col_grupo AS grupoPeriodo FROM tbl_alumnos a LEFT JOIN tbl_periodos p ON p.col_id=a.col_periodoid ORDER BY a.col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        foreach($todos as $item){
            // $periodoData = getPeriodo($item['col_periodoid'], $this->db, false);
            $carreraData = getCarrera($item['col_carrera'], $this->db);
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = fixEncode($item['col_fullname'], true).' ('.($item['col_estatus'] != 'activo'?'Baja':'Activo').') '.($item['col_egresado'] == 1?'(Egresado)':'');
            $result[$i]['text'] =  fixEncode($item['col_fullname'], true).' ('.$carreraData['nombre'].', '.$carreraData['modalidad'].')';
            $result[$i]['estatus'] = $item['col_estatus'] != 'activo'?'BAJA':'ACTIVO';
            $result[$i]['nombre'] = fixEncode($item['col_fullname'], true);
            $result[$i]['carrera'] = $carreraData['nombre'];
            $result[$i]['modalidad'] = $carreraData['modalidad'];
            $result[$i]['grado'] = $item['gradoPeriodo'];
            $result[$i]['grupo'] = $item['grupoPeriodo'];
            $result[$i]['periodoNombre'] = $item['nombrePeriodo'];
            $result[$i]['periodoID'] = $item['col_periodoid'];
            $result[$i]['periodoCompleto'] = $item['gradoPeriodo'].'-'.$item['grupoPeriodo'].' ('.$item['nombrePeriodo'].')';
            $i++;
        }

        return $this->response->withJson($result);
    });

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
        global $apiURL;
        global $tiposDocumentosNombres;
        global $tiposDocumentosURL;

        $sth = $this->db->prepare("SELECT t.*, CONCAT(a.col_nombres, ' ', a.col_apellidos) AS nombreAlumno FROM tbl_atencion t LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ORDER BY t.col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;

        $estatus = array(
            0 => '<span class="badge badge-warning">Pendiente</span>',
            1 => '<span class="badge badge-info">En Proceso (Revisión)</span>',
            2 => '<span class="badge badge-success">Aprobado (Completado)</span>',
            3 => '<span class="badge badge-danger">Rechazado (Completado)</span>'
        );
        $tipos = $tiposDocumentosNombres;
        $tiposURL = $tiposDocumentosURL;

        foreach($todos as $item){

            $result[$i]['id'] = $item['col_id'];
            $result[$i]['asunto'] = fixEncode($item['col_asunto'], true);
            $result[$i]['nombreAlumno'] = fixEncode($item['nombreAlumno'], true);
            $result[$i]['fecha'] = fechaTexto($item['col_created_at']);
            $result[$i]['estatus'] = $estatus[$item['col_estatus']];
            $result[$i]['tipo'] = $tipos[$item['col_tipo']];
            if(intval($item['col_tipo']) > 0) {
                $result[$i]['archivo'] = '<a class="text-secondary" target="_blank" href="'.$apiURL.'/atencion/'.$tiposURL[$item['col_tipo']].'?id='.$item['col_id'].'"><i class="fas fa-file-pdf text-danger"></i> Descargar</a>';
            }else{
                $result[$i]['archivo'] = '';
            }
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/get', function (Request $request, Response $response, array $args) {
        global $apiURL;
        global $tiposDocumentosNombres;
        global $tiposDocumentosURL;

        $sth = $this->db->prepare("SELECT * FROM tbl_atencion WHERE col_id='".intval($_REQUEST['id'])."'");
        $sth->execute();
        $item = $sth->fetch(PDO::FETCH_OBJ);

        $item->col_asunto = fixEncode(stripslashes($item->col_asunto));
        $item->col_folio = fixEncode(stripslashes($item->col_folio));
        $item->col_observaciones = fixEncode(stripslashes($item->col_observaciones));
        $item->col_articulos = fixEncode(stripslashes($item->col_articulos));
        $item->col_extra = unserialize($item->col_extra);
        if(intval($item->col_tipo) > 0) {
            $tiposURL = $tiposDocumentosURL;

            $item->descargarURL = $apiURL.'/atencion/'.$tiposURL[$item->col_tipo].'?id='.$item->col_id;
        }
        return $this->response->withJson($item);

    });


    $this->put('/update', function (Request $request, Response $response, $args) {
        global $apiURL;
        global $tiposDocumentosNombres;
        global $tiposDocumentosURL;
        global $nombreInstituto, $inicialesInstituto;

        $_response['status'] = 'false';
        $userid = getCurrentUserID();
        $input = json_decode($request->getBody());

        $extraData = (array) $input->extraData;


        $query = 'UPDATE tbl_atencion SET
        col_folio="'.addslashes($input->folio).'",
        col_asunto="'.addslashes($input->asunto).'",
        col_alumnoid="'.intval($input->alumnoid).'",
        col_observaciones="'.addslashes($input->observaciones).'",
        col_articulos="'.addslashes($input->articulos).'",
        col_estatus="'.intval($input->estatus).'",
        col_tipo="'.intval($input->tipo).'",
        col_fecha="'.$input->fecha.'",
        col_hora_entrada="'.$input->horaEntrada.'",
        col_hora_salida="'.$input->horaSalida.'",
        col_fecha_cita="'.$input->cita.'",
        col_hora_cita="'.$input->horaCita.'",
        col_firma_userid="'.intval($input->firma).'",
        col_registro_ss="'.addslashes($input->registroSS).'",
        col_programa_ss="'.addslashes($input->programaSS).'",
        col_duracion_ss="'.addslashes($input->duracionSS).'",
        col_vigente="'.addslashes($input->vigente).'",
        col_turno="'.addslashes($input->turno).'",
        col_ampara="'.addslashes($input->ampara).'",
        col_jefe_servicios_escolares="'.addslashes($input->jefeServiciosEscolares).'",
        col_director_educacion="'.addslashes($input->directorEducacion).'",
        col_jefe_oficina="'.addslashes($input->jefeOficina).'",
        col_subsecretario="'.addslashes($input->subsecretario).'",
        col_numero="'.addslashes($input->numero).'",
        col_libro="'.addslashes($input->libro).'",
        col_foja="'.addslashes($input->foja).'",
        col_duplicado="'.intval($input->duplicado).'",
        col_fecha_depto_escolares="'.addslashes($input->fechaDeptoEscolares).'",
        col_regimen="'.addslashes($input->regimen).'",
        col_fecha_vigencia="'.addslashes($input->fechaVigencia).'",
        col_sinodal_presidente="'.addslashes($input->sinodalPresidente).'",
        col_sinodal_secretario="'.addslashes($input->sinodalSecretario).'",
        col_sinodal_vocal="'.addslashes($input->sinodalVocal).'",
        col_vacaciones_inicio="'.addslashes($input->vacacionesInicio).'",
        col_vacaciones_fin="'.addslashes($input->vacacionesFin).'",
        col_calificacion_jurado="'.addslashes($input->calificacionJurado).'",
        col_extra="'.addslashes(serialize($extraData)).'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$userid.'" WHERE col_id="'.$input->id.'"';

        $dblog = new DBLog($query, 'tbl_atencion', 'col_id', $input->id, 'Servicios de Alumnos', $this->db);
        $dblog->prepareLog();
        $sth = $this->db->prepare($query);

        if($sth->execute()){
            $dblog->saveLog();
            $_response['status'] = 'true';
            if(intval($input->tipo) > 0) {
                $tiposURL = $tiposDocumentosURL;
                $_response['descargarURL'] = $apiURL.'/atencion/'.$tiposURL[$input->tipo].'?id='.$input->id;
            }
            if($input->send == 1){
                $sth = $this->db->prepare("SELECT * FROM tbl_alumnos WHERE col_id='".intval($input->alumnoid)."'");
                $sth->execute();
                $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
                $nombre = fixEncode($alumnoData->col_rep_nombres.' '.$alumnoData->col_rep_apellidos, true);
                $nombreAlumno = fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos, true);
                $email = $alumnoData->col_rep_correo;
                // $email = 'jorge.x3@gmail.com';
                if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $texto = '<table style="width:100%;max-width:500px;" boder="0"><tr><td>';
                    $texto .= 'Buen día C.'.$nombre.',<br/>A continuación adjuntamos una copia del reporte de atención del alumno(a) <b>'.$nombreAlumno.'</b>:<br/><br/>';
                    $texto .= '<b>Asunto:</b> '.$input->asunto.'<br/>';
                    $texto .= $input->observaciones.'<br/>';
                    $texto .= '<b>Fecha:</b> '.fechaTexto(date("Y-m-d")).'<br/><br/>';
                    $texto .= '<br/><br/>Saludos,<br/>'.$nombreInstituto.'<br/><br/>';
                    $texto .= '<img src="'.getLogo().'" style="max-width: 150px;height:auto;" alt="'.$inicialesInstituto.'" border="0"/>';
                    $texto .= '</td></tr></table>';
                    switch($input->tipo){
                        case 1:
                            $fileData = atencionPsicopedagogica($input->id, $this->db, 'S');
                            $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                        break;
                        case 2:
                            $fileData = atencionCoordinacion($input->id, $this->db, 'S');
                            $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                        break;
                        case 3:
                            $fileData = atencionModeloEducativo($input->id, $this->db, 'S');
                            $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                        break;
                        case 4:
                            $fileData = atencionInasistencias($input->id, $this->db, 'S');
                            $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                        break;
                        case 5:
                            $fileData = atencionAmonestacion($input->id, $this->db, 'S');
                            $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                        break;
                        case 6:
                            $fileData = generarKardex($input->id, $this->db, 'S');
                            $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                        break;
                        case 7:
                            $fileData = generarHistorialAcademico($input->id, $this->db, 'S');
                            $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                        break;
                        case 8:
                            $sth = $this->db->prepare("SELECT * FROM tbl_atencion WHERE col_id='".intval($input->id)."'");
                            $sth->execute();
                            $atencionData = $sth->fetch(PDO::FETCH_OBJ);

                            $sth = $this->db->prepare("SELECT * FROM tbl_alumnos WHERE col_id='".intval($atencionData->col_alumnoid)."'");
                            $sth->execute();
                            $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
                            // generarKardex($atencionid, $this->db);
                            $fileData = generarBoleta($alumnoData->col_id, $alumnoData->col_periodoid, $this->db, 'descarga-fldch', 'S');
                            $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                        break;
                    }

                    $_response['sent'] = $email;
                    enviarCorreo(array('to' => $email, 'nombre' => $nombre), 'Reporte de Atención - FLDCH', $texto, '', $fileData, $fileName);
                }
            }
        }

        return $this->response->withJson($_response);

    });

    $this->post('/add', function (Request $request, Response $response, $args) {
        global $nombreInstituto, $inicialesInstituto;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());
        $userid = getCurrentUserID();
        $extraData = (array) $input->extraData;

        $sth = $this->db->prepare("SELECT * FROM tbl_alumnos WHERE col_id='".intval($input->alumnoid)."'");
        $sth->execute();
        $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

        $data = array(
            "col_folio" => addslashes($input->folio),
            "col_asunto" => addslashes($input->asunto),
            "col_alumnoid" => intval($input->alumnoid),
            "col_observaciones" => addslashes($input->observaciones),
            "col_articulos" => addslashes($input->articulos),
            "col_estatus" => intval($input->estatus),
            "col_tipo" => intval($input->tipo),
            "col_fecha" => $input->fecha,
            "col_hora_entrada" => $input->horaEntrada,
            "col_hora_salida" => $input->horaSalida,
            "col_fecha_cita" => $input->cita,
            "col_hora_cita" => $input->horaCita,
            "col_firma_userid" => intval($input->firma),
            "col_registro_ss" => addslashes($input->registroSS),
            "col_programa_ss" => addslashes($input->programaSS),
            "col_duracion_ss" => addslashes($input->duracionSS),
            "col_regimen" => addslashes($input->regimen),
            "col_fecha_vigencia" => addslashes($input->fechaVigencia),
            "col_vigente" => addslashes($input->vigente),
            "col_turno" => addslashes($input->turno),
            "col_ampara" => addslashes($input->ampara),
            "col_jefe_servicios_escolares" => addslashes($input->jefeServiciosEscolares),
            "col_director_educacion" => addslashes($input->directorEducacion),
            "col_jefe_oficina" => addslashes($input->jefeOficina),
            "col_subsecretario" => addslashes($input->subsecretario),
            "col_numero" => addslashes($input->numero),
            "col_libro" => addslashes($input->libro),
            "col_foja" => addslashes($input->foja),
            "col_duplicado" => intval($input->duplicado),
            "col_fecha_depto_escolares" => addslashes($input->fechaDeptoEscolares),
            "col_sinodal_presidente" => addslashes($input->sinodalPresidente),
            "col_sinodal_secretario" => addslashes($input->sinodalSecretario),
            "col_sinodal_vocal" => addslashes($input->sinodalVocal),
            "col_vacaciones_inicio" => addslashes($input->vacacionesInicio),
            "col_vacaciones_fin" => addslashes($input->vacacionesFin),
            "col_calificacion_jurado" => addslashes($input->calificacionJurado),
            "col_extra" => addslashes(serialize($extraData)),
            "col_periodoid" => $alumnoData->col_periodoid,
            "col_created_at" => date("Y-m-d H:i:s"),
            "col_created_by" => $userid,
            "col_updated_at" => date("Y-m-d H:i:s"),
            "col_updated_by" => $userid,
        );

        $query = 'INSERT INTO tbl_atencion ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';
        $sth = $this->db->prepare($query);

        $sth->execute();
        $lastID = $this->db->lastInsertId();

        $dblog = new DBLog($query, 'tbl_atencion', 'col_id', $lastID, 'Servicios de Alumnos', $this->db);
        $dblog->saveLog();

        if($input->send == 1){
            $nombre = fixEncode($alumnoData->col_rep_nombres.' '.$alumnoData->col_rep_apellidos, true);
            $nombreAlumno = fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos, true);
            $email = $alumnoData->col_rep_correo;
            // $email = 'jorge.x3@gmail.com';
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $texto = '<table style="width:100%;max-width:500px;" boder="0"><tr><td>';
                $texto .= 'Buen día C.'.$nombre.',<br/>A continuación adjuntamos una copia del reporte de atención del alumno(a) <b>'.$nombreAlumno.'</b>:<br/><br/>';
                $texto .= '<b>Asunto:</b> '.$input->asunto.'<br/>';
                $texto .= $input->observaciones.'<br/>';
                $texto .= '<b>Fecha:</b> '.fechaTexto(date("Y-m-d")).'<br/><br/>';
                $texto .= '<br/><br/>Saludos,<br/>'.$nombreInstituto.'<br/><br/>';
                $texto .= '<img src="'.getLogo().'" style="max-width: 150px;height:auto;" alt="'.$inicialesInstituto.'" border="0"/>';
                $texto .= '</td></tr></table>';

                switch($input->tipo){
                    case 1:
                        $fileData = atencionPsicopedagogica($lastID, $this->db, 'S');
                        $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                    break;
                    case 2:
                        $fileData = atencionCoordinacion($lastID, $this->db, 'S');
                        $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                    break;
                    case 3:
                        $fileData = atencionModeloEducativo($lastID, $this->db, 'S');
                        $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                    break;
                    case 4:
                        $fileData = atencionInasistencias($lastID, $this->db, 'S');
                        $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                    break;
                    case 5:
                        $fileData = atencionAmonestacion($lastID, $this->db, 'S');
                        $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                    break;
                    case 6:
                        $fileData = generarKardex($lastID, $this->db, 'S');
                        $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                    break;
                    case 7:
                        $fileData = generarHistorialAcademico($lastID, $this->db, 'S');
                        $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                    break;
                    case 8:
                        $sth = $this->db->prepare("SELECT * FROM tbl_atencion WHERE col_id='".intval($lastID)."'");
                        $sth->execute();
                        $atencionData = $sth->fetch(PDO::FETCH_OBJ);

                        $sth = $this->db->prepare("SELECT * FROM tbl_alumnos WHERE col_id='".intval($atencionData->col_alumnoid)."'");
                        $sth->execute();
                        $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
                        // generarKardex($atencionid, $this->db);
                        $fileData = generarBoleta($alumnoData->col_id, $alumnoData->col_periodoid, $this->db, 'descarga-fldch', 'S');
                        $fileName = 'reporteAtencion-'.date('Y-m-d').'.pdf';
                    break;
                }
                // $email = 'jorge.x3@gmail.com';
                enviarCorreo(array('to' => $email, 'nombre' => $nombre), 'Reporte de Atención - FLDCH', $texto, '', $fileData, $fileName);
            }
        }

        $_response['status'] = 'true';

        return $this->response->withJson($_response);

    });

    $this->delete('/delete', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_atencion WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_atencion', '', '', 'Atención de Alumnos', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();
        // $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->get('/amonestacion', function () {

        $atencionid = intval($_REQUEST['id']);
        atencionAmonestacion($atencionid, $this->db);
        die();

    });

    $this->get('/psicopedagogica', function () {
        //
        $atencionid = intval($_REQUEST['id']);
        atencionPsicopedagogica($atencionid, $this->db);
        die();

    });

    $this->get('/coordinacion', function () {
        //
        $atencionid = intval($_REQUEST['id']);
        atencionCoordinacion($atencionid, $this->db);
        die();

    });

    $this->get('/inasistencias', function () {
        //
        $atencionid = intval($_REQUEST['id']);
        atencionInasistencias($atencionid, $this->db);
        die();

    });

    $this->get('/modelo-educativo', function () {

        $atencionid = intval($_REQUEST['id']);
        atencionModeloEducativo($atencionid, $this->db);
        die();

    });

    $this->get('/historial-academico', function () {
        //
        $atencionid = intval($_REQUEST['id']);
        // generarKardex($atencionid, $this->db);
        generarHistorialAcademico($atencionid, $this->db);
        die();

    });

    $this->get('/boleta', function () {
        //
        $atencionid = intval($_REQUEST['id']);

        $sth = $this->db->prepare("SELECT * FROM tbl_atencion WHERE col_id='".intval($atencionid)."'");
        $sth->execute();
        $atencionData = $sth->fetch(PDO::FETCH_OBJ);

        $sth = $this->db->prepare("SELECT * FROM tbl_alumnos WHERE col_id='".intval($atencionData->col_alumnoid)."'");
        $sth->execute();
        $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

        generarBoleta($alumnoData->col_id, $alumnoData->col_periodoid, $this->db, 'descarga-fldch');
        die();

    });

    $this->get('/kardex', function () {
        //
        $atencionid = intval($_REQUEST['id']);
        // generarHistorialAcademico($atencionid, $this->db);
        generarKardex($atencionid, $this->db);
        die();

    });

    $this->get('/constancia-servicio', function() {
      $atencionid = intval($_REQUEST['id']);

      generarConstanciaServicio($atencionid, $this->db);
      die();
    });
    $this->get('/carta-pasante', function() {
      $atencionid = intval($_REQUEST['id']);

      generarCartaPasante($atencionid, $this->db);
      die();
    });
    $this->get('/constancia-terminacion', function() {
      $atencionid = intval($_REQUEST['id']);

      generarConstanciaTerminacion($atencionid, $this->db);
      die();
    });
    $this->get('/diploma1', function() {
      $atencionid = intval($_REQUEST['id']);

      generarDiploma($atencionid, $this->db);
      die();
    });
    $this->get('/certificado-parcial', function() {
      $atencionid = intval($_REQUEST['id']);

      generarCertificadoParcial($atencionid, $this->db);
      die();
    });
    $this->get('/certificado-total', function() {
      $atencionid = intval($_REQUEST['id']);

      generarCertificadoTotal($atencionid, $this->db);
      die();
    });

    $this->get('/constancia-calificaciones', function() {
      $atencionid = intval($_REQUEST['id']);

      generarConstanciaCalificaciones($atencionid, $this->db);
      die();
    });
    $this->get('/registro-escolaridad', function() {
      $atencionid = intval($_REQUEST['id']);

      generarRegistroEscolaridad($atencionid, $this->db);
      die();
    });
    $this->get('/formato-buena-conducta', function() {
      $atencionid = intval($_REQUEST['id']);

      generarFormatoBuenaConducta($atencionid, $this->db);
      die();
    });
    $this->get('/constancia-sustentante', function() {
      $atencionid = intval($_REQUEST['id']);

      generarConstanciaSustentante($atencionid, $this->db);
      die();
    });
    $this->get('/constancia-aprobacion-sinodales', function() {
      $atencionid = intval($_REQUEST['id']);
      generarConstanciaSinodales($atencionid, $this->db);
      die();
    });
    $this->get('/formato-toma-protesta', function() {
      $atencionid = intval($_REQUEST['id']);

      generarTomaProtesta($atencionid, $this->db);
      die();
    });
    $this->get('/formato-toma-protesta-posgrado', function() {
        $atencionid = intval($_REQUEST['id']);

        generarTomaProtestaPosgrado($atencionid, $this->db);
        die();
      });

    $this->get('/constancia-examen-conocimientos', function() {
      $atencionid = intval($_REQUEST['id']);

      generarConstanciaExamenConocimientos($atencionid, $this->db);
      die();
    });


    $this->get('/dictamen-titulacion', function() {
        $atencionid = intval($_REQUEST['id']);

        generarDictamenTitulacion($atencionid, $this->db);
        die();
      });

      //$this->get('/constancia-estudios', function() {
      //  $atencionid = intval($_REQUEST['id']);
      //  generarConstanciaEstudios($atencionid, $this->db);
      //  die();
      //});

      $this->get('/constancia-sencilla', function() {
        $atencionid = intval($_REQUEST['id']);

        generarConstanciaSencilla($atencionid, $this->db);
        die();
      });

      $this->get('/constancia-estudios-termino-semestre', function() {
        $atencionid = intval($_REQUEST['id']);

        generarConstanciaTerminoSemestre($atencionid, $this->db);
        die();
      });

      $this->get('/constancia-termino-estudios', function() {
        $atencionid = intval($_REQUEST['id']);

        generarConstanciaTerminoEstudios($atencionid, $this->db);
        die();
      });

      $this->get('/constancia-tramite-titulacion', function() {
        $atencionid = intval($_REQUEST['id']);

        generarConstanciaTramiteTitulacion($atencionid, $this->db);
        die();
      });


      $this->get('/acta-examen-posgrado', function() {
        $atencionid = intval($_REQUEST['id']);

        generarActaExamenPosgrado($atencionid, $this->db);
        die();
      });

      $this->get('/acta-examen-licenciatura', function() {
        $atencionid = intval($_REQUEST['id']);

        generarActaExamenLicenciatura($atencionid, $this->db);
        die();
      });

      $this->get('/constancia-desempeno', function() {
        $atencionid = intval($_REQUEST['id']);

        generarConstanciaDesempeno($atencionid, $this->db);
        die();
      });

});
// Termina routes.atencion.php
