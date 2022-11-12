<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de periodos.
 *
 * Lista de funciones
 *
 * /periodos
 * - /listPeriodosServicioSocialAlumno
 * - /addMateria
 * - /removeMateria
 * - /listMateriasPeriodos
 * - /listAlumnos
 * - /list
 * - /get
 * - /update
 * - /actualizarHorarios
 * - /aprobarHorarios
 * - /guardarHorario
 * - /removeHorario
 * - /add
 * - /delete
 * - /listMaterias
 * - /getHorarios
 * - /listPlanesEstudios
 * - /getTransversales
 * - /listPeriodos
 * - /listPeriodosCalificaciones
 * - /listPeriodosAlumnos
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/periodos', function () {

    $this->get('/listPeriodosServicioSocialAlumno', function (Request $request, Response $response, array $args) {
        $alumnoid = intval($_REQUEST['alumnoid']);
        $query = "SELECT * FROM tbl_periodos WHERE col_id IN (SELECT col_periodoid FROM tbl_servicio_social WHERE col_alumnoid='".$alumnoid."') ORDER BY col_id ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = fixEncode($item['col_id']);
            $result[$i]['label'] = fixEncode($item['col_nombre']);
            $result[$i]['text'] = fixEncode($item['col_nombre']);
            $i++;
        }

        return $this->response->withJson($result);
    });

    $this->post('/addMateria', function (Request $request, Response $response, array $args) {
        global $dblog;

        $input = $request->getParsedBody();

        $userid = getCurrentUserID();
        $claveMateria = strtoupper(trim($input['clave']));
        $periodoid = intval($input['periodoid']);
        $periodoData = getPeriodo($periodoid, $this->db, false);
        $i = 0;

        $query = 'SELECT a.* FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid WHERE t.col_periodoid="'.$periodoid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $x = 0;
        foreach($todos as $item){
            $data = array(
                'col_alumnoid' => $item['col_id'],
                'col_materia_clave' => $claveMateria,
                'col_periodoid' => $periodoid,
                'col_groupid' => $periodoData->col_groupid,
                'col_observaciones' => '',
                'col_estatus' => 1,
                'col_record_type' => 1,
                'col_created_at' => date('Y-m-d h:i:s'),
                'col_created_by' => $userid,
                'col_updated_at' => date('Y-m-d h:i:s'),
                'col_updated_by' => $userid,
            );

            $query = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave="'.$claveMateria.'" AND col_alumnoid="'.$item['col_id'].'" AND col_periodoid="'.$periodoid.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $hasRecords = $sth->rowCount();
            if($hasRecords == 0){
                $query = 'INSERT INTO tbl_calificaciones ('.implode(",", array_keys($data)).')
                VALUES("'.implode('", "', array_values($data)).'")';

                $dblog = new DBLog($query, 'tbl_calificaciones', '', '', 'Calificaciones', $this->db);
                $dblog->prepareLog();

                $sth = $this->db->prepare($query);
                $sth->execute();

                $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                $dblog->saveLog();
                $x++;
            }
        }

        $result['status'] = 'true';
        if($x == 0) $result['status'] = 'false';
        return $this->response->withJson($result);
    });



    $this->delete('/removeMateria', function (Request $request, Response $response, array $args) {
        global $dblog;

        $claveMateria = strtoupper(trim($_REQUEST['clave']));
        $periodoid = intval($_REQUEST['periodoid']);

        $query = 'DELETE FROM tbl_calificaciones WHERE col_materia_clave="'.$claveMateria.'" AND col_periodoid="'.intval($periodoid).'"';

        $dblog = new DBLog($query, 'tbl_calificaciones', '', '', 'Calificaciones', $this->db);
        $dblog->where = array('col_materia_clave' => $claveMateria, 'col_periodoid' => intval($periodoid));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();
        $dblog->saveLog();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->get('/listMateriasPeriodos', function (Request $request, Response $response, array $args) {
        $periodoid = intval($_REQUEST['periodoid']);
        $periodoData = getPeriodo($periodoid, $this->db, false);

        $m = 0;
        $queryMaterias = 'SELECT * FROM tbl_materias WHERE col_carrera="'.$periodoData->col_carreraid.'" AND col_semestre="'.$periodoData->col_grado.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
        $sth = $this->db->prepare($queryMaterias);
        $sth->execute();
        $todos = $sth->fetchAll();
        foreach($todos as $item){
            $query = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave="'.strtoupper(trim($item['col_clave'])).'" AND col_periodoid="'.$periodoid.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $inCalificaciones = $sth->rowCount();
            $status = '<span class="text-danger"><i class="fas fa-times"></i> No ha sido agregadada</span>';
            if($inCalificaciones>0) $status = '<span class="text-success"><i class="fas fa-check"></i> Ya ha sido agregadada</span>';

            $resultMaterias[$m]['col_id'] = fixEncode($item['col_id']);
            $resultMaterias[$m]['nombre'] = fixEncode($item['col_nombre']);
            $resultMaterias[$m]['clave'] = fixEncode($item['col_clave']);
            $resultMaterias[$m]['status'] = $status;
            $m++;
        }

        $resultData['listMaterias'] = $resultMaterias;

        return $this->response->withJson($resultData);
    });


    $this->get('/listAlumnos', function (Request $request, Response $response, array $args) {
        $periodoid = intval($_REQUEST['periodoid']);
        $periodoData = getPeriodo($periodoid, $this->db, false);
        $i = 0;

        $query = 'SELECT a.*, a.col_estatus AS estatusAlumno FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid WHERE a.col_id AND t.col_periodoid="'.$periodoid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        foreach($todos as $item){
            $carreraData = getCarrera($item['col_carrera'], $this->db);
            $result[$i]['col_id'] = fixEncode($item['col_id']);

            $nombre = $item['col_apellidos']." ".$item['col_nombres'];
            if($item['estatusAlumno'] != 'activo'){
                $result[$i]['nombreAlumno'] = fixEncode($nombre).' <span class="badge badge-info">INACTIVO</span>';
            }else{
                $result[$i]['nombreAlumno'] = fixEncode($nombre);
            }

            $result[$i]['carreraNombre'] = fixEncode($carreraData['nombre']);
            $result[$i]['modalidad'] = fixEncode($carreraData['modalidad']);
            $result[$i]['opciones'] = '<a class="text-secondary" target="_blank" title="Ver Calificaciones" href="#/pages/alumnos/calificaciones-actuales/'.$periodoid.'/'.$item['col_id'].'"><i class="fas fa-clipboard-list text-success"></i> Ver Calificaciones</a>';
            $i++;
        }

        $m = 0;
        $resultMaterias = [];
        $queryMaterias = 'SELECT * FROM tbl_materias WHERE col_carrera="'.$periodoData->col_carreraid.'" AND col_semestre="'.$periodoData->col_grado.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
        $sth = $this->db->prepare($queryMaterias);
        $sth->execute();
        $todos = $sth->fetchAll();
        foreach($todos as $item){

            $query = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave="'.strtoupper(trim($item['col_clave'])).'" AND col_periodoid="'.$periodoid.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $inCalificaciones = $sth->rowCount();
            $status = '<span class="text-danger"><i class="fas fa-times"></i> No ha sido agregadada</span>';
            if($inCalificaciones>0) $status = '<span class="text-success"><i class="fas fa-check"></i> Ya ha sido agregadada</span>';

            $resultMaterias[$m]['col_id'] = fixEncode($item['col_id']);
            $resultMaterias[$m]['nombre'] = fixEncode($item['col_nombre']);
            $resultMaterias[$m]['clave'] = fixEncode($item['col_clave']);
            $resultMaterias[$m]['status'] = $status;
            $m++;
        }

        $resultData['nombrePeriodo'] = $periodoData->col_nombre.' ('.$periodoData->col_grado.'-'.$periodoData->col_grupo.')';
        $resultData['listAlumnos'] = $result;
        $resultData['listMaterias'] = $resultMaterias;
        $resultData['debugMaterias'] = $queryMaterias;

        return $this->response->withJson($resultData);
    });

    $this->get('/list', function (Request $request, Response $response, array $args) {

        $currentPeriodos = getCurrentPeriodos($this->db);

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $r = $c->fetch(PDO::FETCH_OBJ);
        $groupPeriodosActivos[] = $r->col_periodo;
        $groupPeriodosActivos[] = $r->col_periodo_cuatri;
        $groupPeriodosActivos[] = $r->col_periodo_doctorado;
        $groupPeriodosActivos[] = $r->col_periodo_maestria;

        $sth = $this->db->prepare("SELECT * FROM tbl_periodos ORDER BY col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $desface = 0;
        $i = 0;
        $modalidades = array(0 => "", 1 => "Semestral Licenciatura", 2 => "Cuatrimestral Licenciatura", 3 => "Semestral Maestria", 4 => "Semestral Doctorado");
        foreach($todos as $item){

            $query = 'SELECT t.* FROM tbl_alumnos_taxonomia t LEFT JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid WHERE a.col_nombres!="" AND t.col_periodoid="'.$item['col_id'].'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $totalInscritos = $sth->rowCount();
            $css = 'text-info';
            $icon = 'fas fa-user-graduate';
            $alt = 'Todos los alumnos ya tienen calificaciones (Sin Revisión)';

            $query = 'SELECT * FROM tbl_calificaciones WHERE col_periodoid="'.$item['col_id'].'" GROUP BY col_alumnoid';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $totalInscritosConCalificaciones = $sth->rowCount();
            if($totalInscritosConCalificaciones < $totalInscritos){
                $css = 'text-danger';
                $icon = 'fas fa-exclamation-triangle';
                $alt = 'No todos los alumnos tienen califiaciones.';
                if(in_array($item['col_id'], $currentPeriodos)) {
                    $css = 'text-warning';
                    $icon = 'fas fa-exclamation-circle';
                    $alt = 'Las calificaciones se estan capturando.';
                }
            }

            $carrera = getCarrera($item['col_carreraid'], $this->db);
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['col_estatus'] = $item['col_estatus'];

            if($item['col_estatus'] == 1){
                if(in_array($item['col_groupid'], $groupPeriodosActivos)) {
                    $result[$i]['col_nombre'] = fixEncode($item['col_nombre']).' <span class="badge badge-success">Activo</span>';
                }else{
                    $desface++;
                    $result[$i]['col_nombre'] = fixEncode($item['col_nombre']).' <span class="badge badge-warning">Activo</span>';
                }
            }else{
                $result[$i]['col_nombre'] = fixEncode($item['col_nombre']);
            }
            $result[$i]['col_semestre'] = $item['col_grado'];
            $result[$i]['col_grupo'] = $item['col_grupo'];
            $result[$i]['col_carrera'] = $carrera['nombre'];
            $result[$i]['inscritos'] = '<a class="opcion-table iconbold '.$css.'" title="Ver Lista de Alumnos - '.$alt.'" href="#/pages/periodos/alumnos/'.$item['col_id'].'"><i class="'.$icon.'"></i> '.$totalInscritos.' Alumnos</a>';
            $result[$i]['modalidad'] = $modalidades[$item['col_modalidad']];
            if($item['col_aprobado'] == 0){
                $result[$i]['opciones'] = '<a class="opcion-table" title="Horario No Aprobado" href="#/pages/periodos/horario/'.$item['col_id'].'"><i class="fas fa-clock"></i></a>';
            }else{
                $result[$i]['opciones'] = '<a class="opcion-table" title="Horario Aprobado" href="#/pages/periodos/horario/'.$item['col_id'].'"><i class="fas fa-clock text-success"></i></a>';
            }
            $i++;
        }

        $_response['result'] = $result;
        $_response['desface'] = $desface;

        return $this->response->withJson($_response);

    });

    $this->post('/get', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($input['params']['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $item = $sth->fetch(PDO::FETCH_OBJ);

        $result['col_id'] = $item->col_id;
        $result['col_nombre'] = fixEncode($item->col_nombre);
        $result['col_grado'] = $item->col_grado;
        $result['col_grupo'] = $item->col_grupo;
        $result['col_carreraid'] = $item->col_carreraid;
        $result['col_transversal'] = $item->col_transversal;
        $result['col_modalidad'] = $item->col_modalidad;
        $result['col_plan_estudios'] = $item->col_plan_estudios;
        $result['col_club_lectura'] = fixEncode($item->col_club_lectura);
        $result['col_fecha_inicio'] = fixEncode($item->col_fecha_inicio);
        $result['col_fecha_fin'] = fixEncode($item->col_fecha_fin);
        $result['col_fecha_inicio_posgrado'] = fixEncode($item->col_fecha_inicio_posgrado);
        $result['col_fecha_fin_posgrado'] = fixEncode($item->col_fecha_fin_posgrado);
        $result['col_estatus'] = intval($item->col_estatus);


        return $this->response->withJson($result);

    });

    $this->put('/update', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());


        $queryPeriodoActual = "SELECT * FROM tbl_periodos WHERE col_id='".$input->id."'";
        $sth = $this->db->prepare($queryPeriodoActual);
        $sth->execute();
        $periodoData = $sth->fetch(PDO::FETCH_OBJ);

        if(intval($input->estatus) == 1) {

            // Checamos si existe un periodo identico
            $queryPeriodo = "SELECT * FROM tbl_periodos WHERE col_id!=".$input->id." AND col_plan_estudios='".$periodoData->col_plan_estudios."' AND col_modalidad='".$periodoData->col_modalidad."' AND col_aprobado=1 AND col_estatus=1 AND col_carreraid='".$periodoData->col_carreraid."' AND col_grado='".$periodoData->col_grado."' AND col_grupo='".$periodoData->col_grupo."'";
            $sth = $this->db->prepare($queryPeriodo);
            $sth->execute();
            $periodoDataCheck = $sth->fetch(PDO::FETCH_OBJ);
            if($sth->rowCount() > 0){
                $_response['status'] = 'periodo_error';
                $_response['message'] = 'Ya existe un periodo activo con la misma carrera, plan de estudios, modalidad, carrera y grado, el periodo duplicado tiene el ID: '.$periodoDataCheck->col_id;
                return $this->response->withJson($_response);
            }

        }



        $query = 'UPDATE tbl_periodos SET
        col_nombre="'.($input->nombre).'",
        col_grado="'.$input->grado.'",
        col_grupo="'.trim($input->grupo).'",
        col_carreraid="'.$input->carrera.'",
        col_transversal="'.$input->transversal.'",
        col_plan_estudios="'.$input->planEstudios.'",
        col_club_lectura="'.$input->clubLectura.'",
        col_fecha_inicio="'.substr($input->fechaInicio[0], 0, 10).'",
        col_fecha_fin="'.substr($input->fechaFin[0], 0, 10).'",
        col_fecha_inicio_posgrado="'.substr($input->fechaInicioPosgrados[0], 0, 10).'",
        col_fecha_fin_posgrado="'.substr($input->fechaFinPosgrados[0], 0, 10).'",
        col_modalidad="'.$input->modalidad.'",
        col_estatus="'.intval($input->estatus).'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$input->userid.'" WHERE col_id="'.$input->id.'"';

        $dblog = new DBLog($query, 'tbl_periodos', '', '', 'Periodos', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);

        if($sth->execute()){
            $dblog->saveLog();
            $_response['status'] = 'true';
            // $_response['test'] = substr($input->fechaInicio[0], 0, 10);
            if(intval($input->estatus) == 1) {
                // Checamos si tiene alumnos
                $queryTaxAlumnos = "SELECT * FROM tbl_alumnos_taxonomia WHERE col_periodoid='".$input->id."' LIMIT 1";
                $sthTaxalumnos = $this->db->prepare($queryTaxAlumnos);
                $sthTaxalumnos->execute();
                $totalAlumnosEnPeriodo = $sthTaxalumnos->rowCount();
                if($totalAlumnosEnPeriodo > 0){
                    $alumnoTaxData = $sthTaxalumnos->fetch(PDO::FETCH_OBJ);
                    // Pedimos periodo que se desactivara
                    $queryAlumno = "SELECT * FROM tbl_alumnos WHERE col_id='".$alumnoTaxData->col_alumnoid."' LIMIT 1";
                    $sthAlumnoData = $this->db->prepare($queryAlumno);
                    $sthAlumnoData->execute();
                    $alumnoData = $sthAlumnoData->fetch(PDO::FETCH_OBJ);

                    // Desactivamos periodo
                    if($alumnoData->col_periodoid != $input->id){
                        $queryDesactivaPeriodo = 'UPDATE tbl_periodos SET col_estatus=0 WHERE col_id="'.$alumnoData->col_periodoid.'"';
                        $sthPeriodo = $this->db->prepare($queryDesactivaPeriodo);
                        $sthPeriodo->execute();
                    }

                    // Desactivamos periodo activo en la taxonomia de los alumnos
                    $queryDesactivaTax = 'UPDATE tbl_alumnos_taxonomia SET col_status=0 WHERE col_status=1 AND col_periodoid="'.$alumnoData->col_periodoid.'"';
                    $sthDesactivaTax = $this->db->prepare($queryDesactivaTax);
                    $sthDesactivaTax->execute();

                    // Activamos periodo a activar en la taxonomia de los alumnos
                    $queryActivaTax = 'UPDATE tbl_alumnos_taxonomia SET col_status=1 WHERE col_status=0 AND col_periodoid="'.$input->id.'"';
                    $sthActivaTax = $this->db->prepare($queryActivaTax);
                    $sthActivaTax->execute();

                    // Activamos periodo a activar en los alumnos
                    $queryActivaPeriodo = 'UPDATE tbl_alumnos SET col_periodoid="'.$input->id.'" WHERE col_periodoid="'.$alumnoData->col_periodoid.'"';
                    $sthActivaPeriodo = $this->db->prepare($queryActivaPeriodo);
                    $sthActivaPeriodo->execute();

                }

                // $_response['debug'] = $queryTaxAlumnos;
                // $_response['debug2'] = $queryAlumno;
                // $_response['debug3'] = $queryDesactivaPeriodo;
                // $_response['debug4'] = $queryDesactivaTax;
                // $_response['debug5'] = $queryActivaTax;
                // $_response['debug6'] = $queryActivaPeriodo;
            }
        }


        return $this->response->withJson($_response);

    });

    $this->post('/removeHorario', function (Request $request, Response $response, $args) {
        global $dblog;

        $userID = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $query = 'DELETE FROM tbl_horarios_posgrados WHERE col_id="'.intval($input->id).'"';

        $dblog = new DBLog($query, 'tbl_horarios_posgrados', '', '', 'Horarios', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        $_response['status'] = 'true';
        return $this->response->withJson($_response);

    });


    $this->post('/guardarHorario', function (Request $request, Response $response, $args) {
        global $dblog;

        $userID = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $query = "INSERT INTO tbl_horarios_posgrados (col_periodoid, col_materiaid, col_dia, col_hora_inicio, col_hora_fin, col_created_at, col_created_by, col_updated_at, col_updated_by) VALUES('".$input->periodo."', '".$input->materia."', '".$input->dia."', '".$input->inicio."', '".$input->fin."', '".date("Y-m-d H:i:s")."', '".$userID."', '".date("Y-m-d H:i:s")."', '".$userID."')";

        $dblog = new DBLog($query, 'tbl_horarios_posgrados', '', '', 'Horarios', $this->db);
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
        $dblog->saveLog();

        $_response['status'] = 'true';
        return $this->response->withJson($_response);

    });

    $this->post('/guardarPonderacion', function (Request $request, Response $response, $args) {
        global $dblog;

        $userID = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $podenracion['proyecto'] = $input->proyecto;
        $podenracion['participacion'] = $input->participacion;
        $podenracion['examen'] = $input->examen;

        $queryPonderacion = "SELECT * FROM tbl_materias_ponderacion WHERE col_periodoid='".$input->periodoid."' AND col_materiaid='".$input->materiaid."' LIMIT 1";
        $sth = $this->db->prepare($queryPonderacion);
        $sth->execute();
        $existsPonderacion = $sth->rowCount();
        if($existsPonderacion == 0){

            $query = "INSERT INTO tbl_materias_ponderacion (col_periodoid, col_materiaid, col_ponderacion, col_created_at, col_created_by, col_updated_at, col_updated_by) VALUES('".$input->periodoid."', '".$input->materiaid."', '".addslashes(json_encode($podenracion))."', '".date("Y-m-d H:i:s")."', '".$userID."', '".date("Y-m-d H:i:s")."', '".$userID."')";

            $dblog = new DBLog($query, 'tbl_materias_ponderacion', '', '', 'Ponderación Materias', $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();

        }else{

            $query = "UPDATE tbl_materias_ponderacion SET col_ponderacion='".addslashes(json_encode($podenracion))."', col_updated_at='".date("Y-m-d H:i:s")."', col_updated_by='".$userID."' WHERE col_periodoid='".$input->periodoid."' AND col_materiaid='".$input->materiaid."'";

            $dblog = new DBLog($query, 'tbl_materias_ponderacion', '', '', 'Ponderación Materias', $this->db);
            $dblog->where = array('col_periodoid' => intval($input->periodoid), 'col_materiaid' => intval($input->materiaid));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->saveLog();

        }


        $_response['status'] = 'true';
        return $this->response->withJson($_response);

    });

    $this->post('/aprobarHorarios', function (Request $request, Response $response, $args) {
        global $dblog;

        $userID = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        if(intval($input->periodo) > 0) $input->periodoid = $input->periodo;
        $query = "UPDATE tbl_periodos SET col_aprobado='".$input->aprobado."', col_updated_at='".date("Y-m-d H:i:s")."', col_updated_by='".$userID."' WHERE col_id='".intval($input->periodoid)."'";

        $dblog = new DBLog($query, 'tbl_periodos', '', '', 'Horarios', $this->db, 'Aprobación');
        $dblog->where = array('col_id' => intval($input->periodoid));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        $_response['status'] = 'true';
        // $_response['debug'] = $query;
        return $this->response->withJson($_response);

    });

    $this->post('/actualizarHorarios', function (Request $request, Response $response, $args) {
        global $dblog;

        $userID = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        foreach($input->horarios as $k => $v){
            if($v != '-'){
                $data = explode('-', $k);
                $materiaid = $data[0];
                $dia = $data[1];
                $query = 'SELECT * FROM tbl_horarios WHERE col_periodoid="'.intval($input->periodoid).'" AND col_materiaid="'.intval($materiaid).'"';
                $sth = $this->db->prepare($query);
                $sth->execute();
                if($sth->rowCount() == 0){

                    $query = "INSERT INTO tbl_horarios (col_periodoid, col_materiaid, col_".$dia.", col_created_at, col_created_by, col_updated_at, col_updated_by) VALUES('".$input->periodoid."', '".$materiaid."', '".$v."', '".date("Y-m-d H:i:s")."', '".$userID."', '".date("Y-m-d H:i:s")."', '".$userID."')";

                    $dblog = new DBLog($query, 'tbl_horarios', '', '', 'Horarios', $this->db);
                    $dblog->prepareLog();

                    $sth = $this->db->prepare($query);
                    $sth->execute();

                    $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                    $dblog->saveLog();

                }else{

                    $query = "UPDATE tbl_horarios SET col_".$dia."='".$v."', col_updated_at='".date("Y-m-d H:i:s")."', col_updated_by='".$userID."' WHERE col_periodoid='".intval($input->periodoid)."' AND col_materiaid='".intval($materiaid)."'";

                    $dblog = new DBLog($query, 'tbl_horarios', '', '', 'Horarios', $this->db);
                    $dblog->where = array('col_periodoid' => intval($input->periodoid));
                    $dblog->prepareLog();

                    $sth = $this->db->prepare($query);
                    $sth->execute();

                    $dblog->saveLog();
                }

            }
        }

        $query = "UPDATE tbl_periodos SET col_aprobado='".$input->aprobado."', col_updated_at='".date("Y-m-d H:i:s")."', col_updated_by='".$userID."' WHERE col_id='".intval($input->periodoid)."'";

        $dblog = new DBLog($query, 'tbl_periodos', '', '', 'Periodos', $this->db);
        $dblog->where = array('col_id' => intval($input->periodoid));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        $_response['status'] = 'true';
        $_response['query'] = $query;
        return $this->response->withJson($_response);

    });

    $this->post('/add', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $add_periodo_group = 'INSERT INTO tbl_periodos_nombres (col_nombre, col_created_at, col_updated_at)
        VALUES("'.$input->nombre.'", "'.date("Y-m-d H:i:s").'", "'.date("Y-m-d H:i:s").'")';

        $dblog = new DBLog($add_periodo_group, 'tbl_periodos_nombres', '', '', 'Periodos Agrupados', $this->db);
        $dblog->prepareLog();

        $sth_addperiodo_group = $this->db->prepare($add_periodo_group);
        $sth_addperiodo_group->execute();
        $groupid = $this->db->lastInsertId();

        $dblog->where = array('col_id' => intval($groupid));
        $dblog->saveLog();

        foreach(explode("\n", $input->gradosGrupos) as $line){
            $data = explode(":", $line);
            $semestre = $data[0];
            $grupos = explode(",", $data[1]);
            foreach($grupos as $grupo){
                $query = 'INSERT INTO tbl_periodos (col_groupid, col_nombre, col_grado, col_grupo, col_carreraid, col_plan_estudios, col_fecha_inicio, col_fecha_fin, col_modalidad, col_created_at, col_created_by)
                VALUES("'.$groupid.'", "'.($input->nombre).'",  "'.$semestre.'", "'.trim($grupo).'", "'.$input->carrera.'", "'.$input->planEstudios.'", "'.substr($input->fechaInicio[0], 0, 10).'", "'.substr($input->fechaFin[0], 0, 10).'", "'.$input->modalidad.'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'")';

                $dblog = new DBLog($query, 'tbl_periodos', '', '', 'Periodos', $this->db);
                $dblog->prepareLog();

                $sth = $this->db->prepare($query);
                $sth->execute();

                $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                $dblog->saveLog();
            }
        }

        $_response['status'] = 'true';

        return $this->response->withJson($_response);

    });

    $this->delete('/delete', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_periodos WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_periodos', '', '', 'Periodos', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        $query = 'DELETE FROM tbl_alumnos_taxonomia WHERE col_periodoid="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_alumnos_taxonomia', '', '', 'Alumnos Taxonomias', $this->db, 'Se desliga el periodo eliminado de los alumnos');
        $dblog->where = array('col_periodoid' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();
        // $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->get('/listMaterias', function (Request $request, Response $response, array $args) {

        $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($_REQUEST['periodoid']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $periodoData = $sth->fetch(PDO::FETCH_OBJ);

        $sth = $this->db->prepare("SELECT * FROM tbl_materias WHERE col_id='".intval($_REQUEST['transversal'])."' OR col_plan_estudios='".intval($_REQUEST['plan'])."' AND col_carrera='".intval($_REQUEST['carreraid'])."' AND col_semestre='".intval($_REQUEST['semestre'])."'  GROUP BY col_clave ORDER BY col_nombre ASC");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['id'] = $item['col_id'];
            $result[$i]['clave'] = $item['col_clave'];
            if(substr($item['col_clave'], 0, 2) == 'CL') {
                $item['col_nombre'] = $item['col_nombre'].": ".$periodoData->col_club_lectura;
            }
            $result[$i]['nombre'] = fixEncode($item['col_nombre']);
            $horario = unserialize(stripslashes($item['col_horario']));
            // $result['hr'] = $horario;
            foreach($horario as $k => $v) {
                if(($v[0] != '00:00' && $v[1] != '00:00') && ($v[0] != '' && $v[1] != '')){
                    $result[$i][$k] = '<span class="clase_dia" title="'.$k.': '.$v[0].' - '.$v[1].'">'.$v[0].' - '.$v[1].'</span>';
                }
            }
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/reporteAsistencias', function (Request $request, Response $response, array $args) {

        // $TodosLosPeriodos = getCurrentPeriodos($this->db);
        $maestroid = intval($_REQUEST['maestroid']);
        $materiaTaxID = intval($_REQUEST['materiaid']);
        $periodoid = intval($_REQUEST['periodoid']);
        $tipoReporte = intval($_REQUEST['tipo']);

        if($tipoReporte == 1) $subtitulo = '1 PARCIAL';
        if($tipoReporte == 2) $subtitulo = '2 PARCIAL';
        if($tipoReporte == 3) $subtitulo = 'FINAL';
        $isAcademia = false;

        $periodoData = getPeriodo($periodoid, $this->db, false);

        $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_id="'.intval($materiaTaxID).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        if(in_array(substr(trim($data->col_materia_clave), 0, 2), array('AC', 'TL'))) {
            $isAcademia = true;
            $periodosActivos = getPeriodosActivos($periodoData->col_groupid, $this->db);
            $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_materia_clave LIKE "'.claveMateria($data->col_materia_clave).'%" AND col_periodoid IN ('.implode(',', $periodosActivos).')';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $todasTaxonomias = $sth->fetchAll();
            foreach($todasTaxonomias as $_taxonomia) {
                $materiaTaxIDs[] = $_taxonomia['col_id'];
            }
        }

        $query = 'SELECT * FROM tbl_maestros WHERE col_userid="'.$maestroid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMaestro = $sth->fetch(PDO::FETCH_OBJ);


        $carreraData = getCarrera($periodoData->col_carreraid, $this->db);

        $query = 'SELECT * FROM tbl_users WHERE col_id="'.$maestroid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $maestroData = $sth->fetch(PDO::FETCH_OBJ);


        $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.trim($data->col_materia_clave).'" AND col_semestre="'.$periodoData->col_grado.'" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMateria = $sth->fetch(PDO::FETCH_OBJ);

        $materiaid = $dataMateria->col_id;

        if(in_array(substr(trim($data->col_materia_clave), 0, 2), array('AC', 'TL'))) {
            $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.$periodoid.'%" AND col_tipo=5 AND col_created_by="'.$maestroid.'"';
        }else{
            $query = 'SELECT * FROM tbl_actividades WHERE col_materiaid="'.$materiaid.'" AND col_visible_excepto LIKE "%'.$periodoid.'%" AND col_tipo=5 AND col_created_by="'.$maestroid.'"';
        }
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataActividadParcia1 = $sth->fetch(PDO::FETCH_OBJ);

        if($carreraData['posgrado'] == false) {
            if(in_array(substr(trim($data->col_materia_clave), 0, 2), array('AC', 'TL'))) {
                $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.$periodoid.'%" AND col_tipo=6 AND col_created_by="'.$maestroid.'"';
            }else{
                $query = 'SELECT * FROM tbl_actividades WHERE col_materiaid="'.$materiaid.'" AND col_visible_excepto LIKE "%'.$periodoid.'%" AND col_tipo=6 AND col_created_by="'.$maestroid.'"';
            }
            $sth = $this->db->prepare($query);
            $sth->execute();
            $dataActividadParcia2 = $sth->fetch(PDO::FETCH_OBJ);

            if(in_array(substr(trim($data->col_materia_clave), 0, 2), array('AC', 'TL'))) {
                $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.$periodoid.'%" AND col_tipo=7 AND col_created_by="'.$maestroid.'"';
            }else{
                $query = 'SELECT * FROM tbl_actividades WHERE col_materiaid="'.$materiaid.'" AND col_visible_excepto LIKE "%'.$periodoid.'%" AND col_tipo=7 AND col_created_by="'.$maestroid.'"';
            }
            $sth = $this->db->prepare($query);
            $sth->execute();
            $dataActividadFinal = $sth->fetch(PDO::FETCH_OBJ);
        }


        if($tipoReporte == 1) { // Parcial 1
            $fechaInicio = $periodoData->col_fecha_inicio;
            $fechaLimite = $dataActividadParcia1->col_fecha_inicio;
            if(strtotime($fechaInicio) > strtotime($fechaLimite)){
                if($fechaInicio == ''){
                    echo 'No existe registro del primer parcial.';exit;
                }
                if($fechaLimite == ''){
                    echo 'No existe registro del examen final.';exit;
                }
                echo 'Error: La fecha de inicio (Inicio del periodo) '.$fechaInicio.' es posterior a la fecha limite '.$fechaLimite.' (1 Parcial)';exit;
            }
            if($fechaInicio == ''){
                echo 'La fecha de inicio del periodo no esta definida.';exit;
            }
            if($fechaLimite == ''){
                echo 'No existe registro del primer parcial.';exit;
            }
        }

        if($carreraData['posgrado'] == false) {
            if($tipoReporte == 2) { // Parcial 2
                $fechaInicio = $dataActividadParcia1->col_fecha_inicio;
                $fechaLimite = $dataActividadParcia2->col_fecha_inicio;
                if(strtotime($fechaInicio) > strtotime($fechaLimite)){
                    if($fechaInicio == ''){
                        echo 'No existe registro del primer parcial.';exit;
                    }
                    if($fechaLimite == ''){
                        echo 'No existe registro del examen final.';exit;
                    }
                    echo 'Error: La fecha de inicio (1 Parcial) '.$fechaInicio.' es posterior a la fecha limite '.$fechaLimite.' (2 Parcial)';exit;
                }
                if($fechaInicio == ''){
                    echo 'No existe registro del primer parcial.';exit;
                }
                if($fechaLimite == ''){
                    echo 'No existe registro del segundo parcial.';exit;
                }
            }

            if($tipoReporte == 3 && $carreraData['modalidad_periodo'] != 'ldcua') { // Final
                $fechaInicio = $dataActividadParcia2->col_fecha_inicio;
                $fechaLimite = $dataActividadFinal->col_fecha_inicio;
                if(strtotime($fechaInicio) > strtotime($fechaLimite)){
                    if($fechaInicio == ''){
                        echo 'No existe registro del primer parcial.';exit;
                    }
                    if($fechaLimite == ''){
                        echo 'No existe registro del examen final.';exit;
                    }
                    echo 'Error: La fecha de inicio (2 Parcial) '.$fechaInicio.' es posterior a la fecha limite '.$fechaLimite.' (Examen Final)';exit;
                }
                if($fechaInicio == ''){
                    echo 'No existe registro del segundo parcial.';exit;
                }
                if($fechaLimite == ''){
                    echo 'No existe registro del examen final.';exit;
                }
            }else{
                $fechaInicio = $dataActividadParcia1->col_fecha_inicio;
                $fechaLimite = $dataActividadFinal->col_fecha_inicio;
                if(strtotime($fechaInicio) > strtotime($fechaLimite)){
                    if($fechaInicio == ''){
                        echo 'No existe registro del primer parcial.';exit;
                    }
                    if($fechaLimite == ''){
                        echo 'No existe registro del examen final.';exit;
                    }

                    echo 'Error: La fecha de inicio (1 Parcial) '.$fechaInicio.' es posterior a la fecha limite '.$fechaLimite.' (Examen Final)';exit;
                }
                if($fechaInicio == ''){
                    echo 'No existe registro del primer parcial.';exit;
                }
                if($fechaLimite == ''){
                    echo 'No existe registro del examen final.';exit;
                }
            }
        }else{
            if($tipoReporte == 3) { // Final
                $fechaInicio = $dataActividadParcia1->col_fecha_inicio;
                $fechaLimite = $dataActividadFinal->col_fecha_inicio;
                if(strtotime($fechaInicio) > strtotime($fechaLimite)){
                    if($fechaInicio == ''){
                        echo 'No existe registro del primer parcial.';exit;
                    }
                    if($fechaLimite == ''){
                        echo 'No existe registro del examen final.';exit;
                    }
                    echo 'Error: La fecha de inicio (1 Parcial) '.$fechaInicio.' es posterior a la fecha limite '.$fechaLimite.' (Examen Final)';exit;
                }
                if($fechaInicio == ''){
                    echo 'No existe registro del primer parcial.';exit;
                }
                if($fechaLimite == ''){
                    echo 'No existe registro del examen final.';exit;
                }
            }
        }




        if(strtoupper(substr($dataMateria->col_clave, 0, 2)) == 'AC'){
            $_laClave = claveMateria($data->col_materia_clave);
            // if(strlen(trim($data->col_materia_clave)) == 4) $_laClave = trim($data->col_materia_clave);

            $queryx = 'SELECT m.* FROM tbl_materias m LEFT OUTER JOIN tbl_maestros_taxonomia t ON t.col_materia_clave=m.col_clave WHERE t.col_maestroid="'.$dataMaestro->col_userid.'" AND m.col_clave LIKE "'.$_laClave.'%" AND m.col_carrera="'.$periodoData->col_carreraid.'" AND m.col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
            $sthx = $this->db->prepare($queryx);
            $sthx->execute();
            $dataMateriaMulti = $sthx->fetchAll();
            unset($multis);
            foreach($dataMateriaMulti as $mm) {
                $multis[] = $mm['col_id'];
            }
            $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');
            $losPeriodos = getCurrentPeriodos($this->db, $types[$periodoData->col_modalidad]);

            // echo $queryx;exit;

            $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
            "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
            "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_academias ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
            "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";

        } else if(strtoupper(substr($dataMateria->col_clave, 0, 2)) == 'TL'){
                $_laClave = claveMateria($data->col_materia_clave);
                // if(strlen(trim($data->col_materia_clave)) == 4) $_laClave = trim($data->col_materia_clave);

                $queryx = 'SELECT m.* FROM tbl_materias m LEFT OUTER JOIN tbl_maestros_taxonomia t ON t.col_materia_clave=m.col_clave WHERE t.col_maestroid="'.$dataMaestro->col_userid.'" AND m.col_clave LIKE "'.$_laClave.'%" AND m.col_carrera="'.$periodoData->col_carreraid.'" AND m.col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
                $sthx = $this->db->prepare($queryx);
                $sthx->execute();
                $dataMateriaMulti = $sthx->fetchAll();
                unset($multis);
                foreach($dataMateriaMulti as $mm) {
                    $multis[] = $mm['col_id'];
                }
                $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');
                $losPeriodos = getCurrentPeriodos($this->db, $types[$periodoData->col_modalidad]);

                $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
                "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_talleres ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
                "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";


        } else {
            $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
            "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
            "WHERE a.col_estatus='activo' AND t.col_periodoid='".$data->col_periodoid."' ORDER BY a.col_apellidos ASC";
        }


        $sth = $this->db->prepare($query);
        $sth->execute();
        $todosAlumnos = $sth->fetchAll();
        $totalTodosAlumnos = $sth->rowCount();
        $i = 0;

        if($isAcademia == true){
            $query = 'SELECT * FROM tbl_asistencia WHERE col_materiaid IN ('.implode(',', $materiaTaxIDs).') AND col_maestroid="'.$maestroid.'" AND col_fecha BETWEEN "'.$fechaInicio.'" AND "'.$fechaLimite.'"';
        }else{
            $query = 'SELECT * FROM tbl_asistencia WHERE col_materiaid="'.$materiaTaxID.'" AND col_maestroid="'.$maestroid.'" AND col_fecha BETWEEN "'.$fechaInicio.'" AND "'.$fechaLimite.'"';
        }

        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataListas = $sth->fetchAll();


        // 5: Examen Parcial 1
        // 6: Examen Parcial 2
        // 7: Examen Final

        ob_start();
        ?>
        <table width="100%" class="listaCalificaciones">
            <thead>
                <tr>
                    <th style="width: 30px;" align="left">No.</th>
                    <th style="width: 90px;">No. de Control</th>
                    <th align="left">Nombre</th>
                    <?php
                        foreach($dataListas as $lista) {
                            ?>
                            <th style="font-size:10px;text-rotate:90;padding-left:0;padding-right:0;"><?php echo formatoFechaLista($lista['col_fecha']); ?></th>
                            <?php
                        }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                    $i = 1;
                    $a = 1;

                    foreach($todosAlumnos as $item){
                        ?>
                            <tr>
                                <td style="font-size:10px;" align="center"><?php echo $i; ?></td>
                                <td style="font-size:10px;" align="center"><?php echo $item['col_control']; ?></td>
                                <td style="font-size:10px;"><?php echo fixEncode($item['col_apellidos']." ".$item['col_nombres']); ?></td>
                                <?php
                                foreach($dataListas as $lista) {
                                    $queryListaAlumno = "SELECT * FROM tbl_asistencia_alumnos WHERE col_alumnoid='".$item['col_id']."' AND col_listaid='".$lista['col_id']."'";
                                    $sthLA = $this->db->prepare($queryListaAlumno);
                                    $sthLA->execute();
                                    $dataListaAlumno = $sthLA->fetch(PDO::FETCH_OBJ);
                                    $style = '';
                                    $indicador = $dataListaAlumno->col_asistencia;
                                    if($indicador == 'A') $indicador = '&#8226;';
                                    if($indicador == 'F') $indicador = '/';
                                    if($indicador == '') $style = 'style="background: #f2f2f2;"';
                                    ?>
                                    <td align="center" <?php echo $style; ?>><?php echo $indicador; ?></td>
                                    <?php
                                }
                                ?>
                            </tr>
                        <?php
                        $i++;
                        $a++;

                        if($a == 22 && $i < $totalTodosAlumnos) {
                            ?>
                            </tbody>
                        </table>
                        <pagebreak>
                        <table width="100%" class="listaCalificaciones">
                            <thead>
                                <tr>
                                    <th align="left">No.</th>
                                    <th style="width: 90px;">No. de Control</th>
                                    <th align="left">Nombre</th>
                                    <?php
                                        foreach($dataListas as $lista) {
                                            ?>
                                            <th style="font-size:10px;text-rotate:90;padding-left:0;padding-right:0;"><?php echo formatoFechaLista($lista['col_fecha']); ?></th>
                                            <?php
                                        }
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $a = 0;
                        }

                    } ?>
            </tbody>
        </table>
        <br/><br/><br/><br/>
        <table border="0">
            <tr>
                <td width="10%"></td>
                <td width="40%" valign="top" align="center" style="border: 1px solid #222;padding:15px;"><br/><br/><br/><?php echo fixEncode($maestroData->col_firstname.' '.$maestroData->col_lastname); ?><hr/><b>NOMBRE Y FIRMA DEL CATEDRATICO</b></td>
                <td width="20%"></td>
                <td valign="top">
                    <table border="0" cellpadding="0" cellspacing="0" class="explain">
                        <tr>
                            <td>&#8226;</td><td>ASISTENCIA</td><td>R</td><td>RETARDO</td>
                        </tr>
                        <tr>
                            <td>/</td><td>FALTA</td><td>P</td><td>PERMISO</td>
                        </tr>
                    </table>
                </td>
                <td width="10%"></td>
            </tr>
        </table>
        <?php
        $html = ob_get_contents();
        ob_end_clean();

        $tipoGrado = 'SEMESTRE';
        if($carreraData['modalidad_periodo'] != 'ldsem') {
            $tipoGrado = 'CUATRIMESTRE';
        }

        ob_start();
        ?>
        <table border="0" width="100%">
            <tr>
                <td width="25%">
                    <?php
                    if($_SERVER['SERVER_ADDR'] == '37.247.52.225') {
                        echo '<img src="https://plataforma.fldch.edu.mx/assets/images/logo-fldch-impresion.jpg" style="max-width: 180px;height:auto;" alt="FLDCH" border="0"/>';
                        // echo '<img src="/assets/images/logo-fldch-impresion.jpg" style="max-width: 180px;height:auto;" alt="FLDCH" border="0"/>';
                    }else{
                        echo '<img src="http://192.168.12.81/assets/images/logo-fldch-impresion.jpg" style="max-width: 180px;height:auto;" alt="FLDCH" border="0"/>';
                    }
                    ?>
                </td>
                <td width="50%" class="titulo">LISTA DE ASISTENCIAS</td>
                <td width="25%"></td>
            </tr>
        </table>
        <br/>
        <table border="0" width="100%">
            <tr>
                <td width="5%"></td>
                <td style="font-size:11px;" width="40%">PERIODO ESCOLAR: <?php echo $periodoData->col_nombre; ?></td>
                <td width="5%"></td>
                <td style="font-size:11px;" width="40%">FECHA: <?php echo strtoupper(fechaTexto(date('Y-m-d'))); ?></td>
            </tr>
            <tr>
                <td width="5%"></td>
                <td style="font-size:11px;" width="40%"><?php echo mb_strtoupper($carreraData['tipo_modalidad'].' EN: '.$carreraData['nombreLimpio']); ?></td>
                <td width="5%"></td>
                <td style="font-size:11px;" width="40%"><?php echo $periodoData->col_grado; ?> <?php echo $tipoGrado; ?> GRUPO <?php echo $periodoData->col_grupo; ?></td>
            </tr>
            <tr>
                <td width="5%"></td>
                <td style="font-size:11px;" width="40%">MATERIA: <?php echo mb_strtoupper(fixEncode($dataMateria->col_nombre, false, true)); ?></td>
                <td width="5%"></td>
                <td style="font-size:11px;" width="40%"></td>
            </tr>
        </table>
        <table width="100%">
            <tr>
                <td class="subtitulo"><?php echo $subtitulo; ?></td>
            </tr>
        </table>
        <?php
        $header = ob_get_contents();
        ob_end_clean();

        ob_start();
        ?>
        <table border="0" width="100%">
            <tr>
                <td width="30%"></td>
                <td width="40%"></td>
                <td width="30%" align="right">Pag. {PAGENO}</td>
            </tr>
        </table>
        <?php
        $footer = ob_get_contents();
        ob_end_clean();

        include_once(__DIR__ . '/../src/mpdf/mpdf.php');

        $options = array(
            'mode' => 'c',
            'format' => 'A4',
            'margin_header' => 20
        );
        $mpdf=new mPDF('c','A4', '','', '8', '8', 62, 20);
        // $mpdf->showImageErrors = true;
        $mpdf->SetHTMLHeader($header);
        $mpdf->SetHTMLFooter($footer);

        $mpdf->SetDisplayMode('fullpage');
        $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list
        // $stylesheet = file_get_contents(ROOT_PATH."/scss/styles.min.css");
        ob_start();
        ?>
        body {
            font-family: Helvetica, Arial, Tahoma, Sans Serif;
            font-size: 12px;
        }

        table.listaCalificaciones {
            border: 1px solid #222;
            pading: 5px;
        }

        table.listaCalificaciones th {
            background-color: #f2f2f2;
            padding: 12px 5px;
            border: 1px solid #222;
            margin: 0;
        }

        table.listaCalificaciones td {
            padding: 5px;
            border: 1px solid #f2f2f2;
            margin: 0;
        }

        td.titulo {
            color: #222;
            text-align: center;
            font-weight: bold;
        }

        td.subtitulo {
            color: #222;
            text-align: center;
            font-weight: bold;
        }

        table.explain {
            border: 1px solid #222;
            border-collapse: collapsed;
        }
        table.explain td {
            border: 2px solid #222;
            margin: 0;
            padding: 2px 4px;
        }
        <?php
        $stylesheet = ob_get_contents();
        ob_end_clean();

        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->WriteHTML($html, 2);

        $mpdf->Output('Reporte_Asistencias.pdf', 'I');

        die();

    });

    $this->get('/reporteAsistenciasPosgrado', function (Request $request, Response $response, array $args) {

        // $TodosLosPeriodos = getCurrentPeriodos($this->db);
        $maestroid = intval($_REQUEST['maestroid']);
        $materiaTaxID = intval($_REQUEST['materiaid']);
        $periodoid = intval($_REQUEST['periodoid']);
        $tipoReporte = intval($_REQUEST['tipo']);

        if($tipoReporte == 1) $subtitulo = '1 PARCIAL';
        if($tipoReporte == 2) $subtitulo = '2 PARCIAL';
        if($tipoReporte == 3) $subtitulo = 'FINAL';
        $isAcademia = false;

        $periodoData = getPeriodo($periodoid, $this->db, false);

        $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_id="'.intval($materiaTaxID).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_maestros WHERE col_userid="'.$maestroid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMaestro = $sth->fetch(PDO::FETCH_OBJ);

        $carreraData = getCarrera($periodoData->col_carreraid, $this->db);

        $query = 'SELECT * FROM tbl_users WHERE col_id="'.$maestroid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $maestroData = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.trim($data->col_materia_clave).'" AND col_semestre="'.$periodoData->col_grado.'" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMateria = $sth->fetch(PDO::FETCH_OBJ);

        $materiaid = $dataMateria->col_id;


        $query = 'SELECT * FROM tbl_actividades WHERE col_materiaid="'.$materiaid.'" AND col_visible_excepto LIKE "%'.$periodoid.'%" AND col_tipo=12 AND col_created_by="'.$maestroid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataActividad = $sth->fetch(PDO::FETCH_OBJ);

        $fechaInicio = $periodoData->col_fecha_inicio;
        $fechaLimite = $periodoData->col_fecha_fin;

        if(strtotime($fechaInicio) > strtotime($fechaLimite)){
            echo 'Error: La fecha de inicio '.$fechaInicio.' es posterior a la fecha limite '.$fechaLimite.' del periodo';exit;
        }
        if($fechaInicio == ''){
            echo 'No existe registro de la fecha de inicio del periodo.';exit;
        }
        if($fechaLimite == ''){
            echo 'No existe registro de la fecha de fin del periodo.';exit;
        }


        $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
        "WHERE a.col_estatus='activo' AND t.col_periodoid='".$data->col_periodoid."' ORDER BY a.col_apellidos ASC";


        $sth = $this->db->prepare($query);
        $sth->execute();
        $todosAlumnos = $sth->fetchAll();
        $totalTodosAlumnos = $sth->rowCount();
        $i = 0;


        $query = 'SELECT * FROM tbl_asistencia WHERE col_materiaid="'.$materiaTaxID.'" AND col_maestroid="'.$maestroid.'" AND col_fecha BETWEEN "'.$fechaInicio.'" AND "'.$fechaLimite.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataListas = $sth->fetchAll();

        ob_start();
        ?>
        <table width="100%" class="listaCalificaciones">
            <thead>
                <tr>
                    <th rowspan="2" style="font-size:10px;width: 30px;" align="left">No.</th>
                    <th rowspan="2" style="font-size:10px;width: 90px;">NO. DE CONTROL</th>
                    <th rowspan="2" style="font-size:10px;" align="center">NOMBRE DEL ALUMNO</th>
                    <?php
                        foreach($dataListas as $lista) {
                            ?>
                            <th rowspan="2" style="font-size:10px;width:25px;padding:0;"><?php echo formatoFechaListaPosgrado($lista, $this->db); ?></th>
                            <?php
                        }
                    ?>
                    <th style="font-size:10px;" colspan="3" align="center">INFORME DE TRABAJOS</th>
                </tr>
                <tr>
                    <th style="font-size:10px;" align="center">RETROALIMENTACIÓN<BR/>SI O NO</th>
                    <th style="font-size:10px;" align="center">ALUMNO CORRIGIO<br/>OBSERVACIONES<br/>SI O NO</th>
                    <th style="font-size:10px;" align="center">CALIFICACIÓN<br/>FINAL DEL<br/>TRABAJO</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $i = 1;
                    $a = 1;

                    foreach($todosAlumnos as $item){
                        ?>
                            <tr>
                                <td style="font-size:9px;" align="center"><?php echo $i; ?></td>
                                <td style="font-size:9px;" align="center"><?php echo $item['col_control']; ?></td>
                                <td style="font-size:9px;"><?php echo fixEncode($item['col_apellidos']." ".$item['col_nombres']); ?></td>
                                <?php
                                foreach($dataListas as $lista) {
                                    $queryListaAlumno = "SELECT * FROM tbl_asistencia_alumnos WHERE col_alumnoid='".$item['col_id']."' AND col_listaid='".$lista['col_id']."'";
                                    $sthLA = $this->db->prepare($queryListaAlumno);
                                    $sthLA->execute();
                                    $dataListaAlumno = $sthLA->fetch(PDO::FETCH_OBJ);
                                    $style = '';
                                    $indicador = $dataListaAlumno->col_asistencia;
                                    if($indicador == 'A') $indicador = '&#8226;';
                                    if($indicador == 'F') $indicador = '/';
                                    if($indicador == '') $style = 'style="background: #f2f2f2;"';
                                    ?>
                                    <td style="font-size:9px;" align="center" <?php echo $style; ?>><?php echo $indicador; ?></td>
                                    <?php
                                }

                                $queryTareaAlumno = "SELECT * FROM tbl_actividades_tareas WHERE col_actividadid='".$dataActividad->col_id."' AND col_alumnoid='".$item['col_id']."' ORDER BY col_intento DESC LIMIT 1;";
                                $sthTarea = $this->db->prepare($queryTareaAlumno);
                                $sthTarea->execute();
                                $dataTareaAlumno = $sthTarea->fetch(PDO::FETCH_OBJ);
                                ?>
                                <td style="font-size:9px;" align="center"><?php echo ($dataTareaAlumno->col_retroalimentacion != ''?'Si':'No'); ?></td>
                                <td style="font-size:9px;" align="center"><?php echo ($dataTareaAlumno->col_corrigio == 1?'Si':'No'); ?></td>
                                <td style="font-size:9px;" align="center"><?php echo $dataTareaAlumno->col_calificacion; ?></td>
                            </tr>
                        <?php
                        $i++;
                        $a++;

                        if($a == 22 && $i < $totalTodosAlumnos) {
                            ?>
                            </tbody>
                        </table>
                        <pagebreak>
                        <table width="100%" class="listaCalificaciones">
                            <thead>
                                <tr>
                                    <th align="left">No.</th>
                                    <th style="width: 90px;">No. de Control</th>
                                    <th align="left">Nombre</th>
                                    <?php
                                        foreach($dataListas as $lista) {
                                            ?>
                                            <th style="font-size:10px;text-rotate:90;padding-left:0;padding-right:0;"><?php echo formatoFechaLista($lista['col_fecha']); ?></th>
                                            <?php
                                        }
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $a = 0;
                        }

                    } ?>
            </tbody>
        </table>
        <br/><br/><br/><br/>
        <table border="0">
            <tr>
                <td width="10%"></td>
                <td width="40%" valign="top" align="center" style="border: 1px solid #222;padding:15px;"><br/><br/><br/><?php echo fixEncode($maestroData->col_firstname.' '.$maestroData->col_lastname); ?><hr/><b>NOMBRE Y FIRMA DEL CATEDRATICO</b></td>
                <td width="20%"></td>
                <td valign="top">
                    <table border="0" cellpadding="0" cellspacing="0" class="explain">
                        <tr>
                            <td>&#8226;</td><td>ASISTENCIA</td><td>R</td><td>RETARDO</td>
                        </tr>
                        <tr>
                            <td>/</td><td>FALTA</td><td>P</td><td>PERMISO</td>
                        </tr>
                    </table>
                </td>
                <td width="10%"></td>
            </tr>
        </table>
        <?php
        $html = ob_get_contents();
        ob_end_clean();

        $tipoGrado = 'SEMESTRE';
        if($carreraData['modalidad_periodo'] != 'ldsem') {
            $tipoGrado = 'CUATRIMESTRE';
        }

        ob_start();
        ?>
        <table border="0" width="100%">
            <tr>
                <td width="25%">
                    <?php
                        echo '<img src="'.getLogo('big').'" style="max-width: 180px;height:auto;" alt="FLDCH" border="0"/>';
                    ?>
                </td>
                <td width="50%" class="titulo">LISTA DE ASISTENCIAS</td>
                <td width="25%"></td>
            </tr>
        </table>
        <br/>
        <table border="0" width="100%">
            <tr>
                <td style="font-size:11px;" align="center"><b>POSGRADO:</b> <?php echo mb_strtoupper(fixEncode($carreraData['nombre'], false, true)); ?></td>
            </tr>
            <tr>
                <td style="font-size:11px;" align="center"><b>MATERIA:</b> <?php echo mb_strtoupper(fixEncode($dataMateria->col_nombre, false, true)); ?></td>
            </tr>
        </table>
        <table border="0" width="100%">
            <tr>
                <td style="font-size:11px;" width="50%" align="center"><b><?php echo $periodoData->col_grado; ?>&#xB0; SEMESTRE GROUP <?php echo $periodoData->col_grupo; ?></b></td>
                <?php
                $formatoInicio = 'j F';
                if(fechaTexto($periodoData->col_fecha_inicio_posgrado, 'F') == fechaTexto($periodoData->col_fecha_fin_posgrado, 'F')) $formatoInicio = 'j';
                ?>
                <td style="font-size:11px;" width="50%" align="center"><b>FECHA: <?php echo strtoupper(fechaTexto($periodoData->col_fecha_inicio_posgrado, $formatoInicio)); ?> AL <?php echo strtoupper(fechaTexto($periodoData->col_fecha_fin_posgrado, 'j \D\E F \D\E Y')); ?></b></td>
            </tr>
        </table>
        <?php
        $header = ob_get_contents();
        ob_end_clean();

        ob_start();
        ?>
        <table border="0" width="100%">
            <tr>
                <td width="30%"></td>
                <td width="40%"></td>
                <td width="30%" align="right">Pag. {PAGENO}</td>
            </tr>
        </table>
        <?php
        $footer = ob_get_contents();
        ob_end_clean();

        include_once(__DIR__ . '/../src/mpdf/mpdf.php');

        $options = array(
            'mode' => 'c',
            'format' => 'A4',
            'margin_header' => 20
        );
        $mpdf=new mPDF('c','A4', '','', '8', '8', 50, 20);
        // $mpdf->showImageErrors = true;
        $mpdf->SetHTMLHeader($header);
        $mpdf->SetHTMLFooter($footer);

        $mpdf->SetDisplayMode('fullpage');
        $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list
        // $stylesheet = file_get_contents(ROOT_PATH."/scss/styles.min.css");
        ob_start();
        ?>
        body {
            font-family: Helvetica, Arial, Tahoma, Sans Serif;
            font-size: 12px;
        }

        table.listaCalificaciones {
            border: 1px solid #222;
            pading: 3px;
            border-spacing: 0;
        }

        table.listaCalificaciones th {
            background-color: #f2f2f2;
            padding: 12px 3px;
            border: 1px solid #222;
            margin: 0;

        }

        table.listaCalificaciones td {
            padding: 3px;
            border: 1px solid #f2f2f2;
            margin: 0;

        }

        td.titulo {
            color: #222;
            text-align: center;
            font-weight: bold;
        }

        td.subtitulo {
            color: #222;
            text-align: center;
            font-weight: bold;
        }

        table.explain {
            border: 1px solid #222;
            border-collapse: collapsed;
        }
        table.explain td {
            border: 2px solid #222;
            margin: 0;
            padding: 2px 4px;
        }
        <?php
        $stylesheet = ob_get_contents();
        ob_end_clean();

        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->WriteHTML($html, 2);

        $mpdf->Output('Reporte_Asistencias.pdf', 'I');

        die();

    });

    $this->get('/getHorarios', function (Request $request, Response $response, array $args) {
        global $apiURL;

        $horarios = Array();

        $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($_REQUEST['periodoid']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $periodoData = $sth->fetch(PDO::FETCH_OBJ);
        $isPosgrado = 0;
        if($periodoData->col_modalidad == 3 || $periodoData->col_modalidad == 4){
            $isPosgrado = 1;
        }
        $dias[0] = 'LU';
        $dias[1] = 'MA';
        $dias[2] = 'MI';
        $dias[3] = 'JU';
        $dias[4] = 'VI';
        $dias[5] = 'SA';
        $dias[6] = 'DO';

        $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_periodoid="'.intval($_REQUEST['periodoid']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        foreach($todos as $item){
            $claves[] = "'".$item['col_materia_clave']."'";
        }
        $modalidades = array(0 => "Sin definir", 1 => "Semestral Licenciatura", 2 => "Cuatrimestral Licenciatura", 3 => "Semestral Maestria", 4 => "Semestral Doctorado");
        if(count($claves)) {
            $queryMaterias = "SELECT * FROM tbl_materias WHERE col_id='".intval($periodoData->col_transversal)."' OR (col_plan_estudios='".intval($periodoData->col_plan_estudios)."' AND col_carrera='".intval($periodoData->col_carreraid)."' AND col_semestre='".intval($periodoData->col_grado)."' AND col_clave IN (".implode(',', $claves)."))  GROUP BY col_clave ORDER BY col_nombre ASC";
            $sth = $this->db->prepare($queryMaterias);
            $sth->execute();
            $todos = $sth->fetchAll();

            $i = 0;
            foreach($todos as $item){

                $queryMaestroTax = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_materia_clave="'.$item['col_clave'].'" AND col_periodoid="'.$periodoData->col_id.'"';
                $sthMaestroTax = $this->db->prepare($queryMaestroTax);
                $sthMaestroTax->execute();
                $dataMaestroTax = $sthMaestroTax->fetch(PDO::FETCH_OBJ);

                $queryMaestro = 'SELECT * FROM tbl_users WHERE col_id="'.$dataMaestroTax->col_maestroid.'" AND col_maestro=1';
                $sthMaestro = $this->db->prepare($queryMaestro);
                $sthMaestro->execute();
                $dataMaestro = $sthMaestro->fetch(PDO::FETCH_OBJ);
                if($dataMaestro) {
                    $result[$i]['nombre_maestro'] = fixEncode($dataMaestro->col_firstname. ' '.$dataMaestro->col_lastname, true);
                    $result[$i]['maestroid'] = $dataMaestro->col_id;
                }else{
                    $result[$i]['nombre_maestro'] = '-';
                    $result[$i]['maestroid'] = $dataMaestro->col_id;
                }

                if($isPosgrado == 0){
                    $result[$i]['reporte_asistencia'] = $apiURL.'/periodos/reporteAsistencias?maestroid='.$dataMaestroTax->col_maestroid.'&materiaid='.$dataMaestroTax->col_id.'&periodoid='.intval($_REQUEST['periodoid']);
                }else{
                    $result[$i]['reporte_asistencia'] = $apiURL.'/periodos/reporteAsistenciasPosgrado?maestroid='.$dataMaestroTax->col_maestroid.'&materiaid='.$dataMaestroTax->col_id.'&periodoid='.intval($_REQUEST['periodoid']);

                }

                $result[$i]['materiaid'] = $item['col_id'];
                $result[$i]['materia_clave'] = $item['col_clave'];
                if(substr($item['col_clave'], 0, 2) == 'CL') {
                    $item['col_nombre'] = $item['col_nombre'].": ".$periodoData->col_club_lectura;
                }
                $result[$i]['materia_nombre'] = fixEncode($item['col_nombre']);


                if($isPosgrado == 0){

                    $sthh = $this->db->prepare("SELECT * FROM tbl_horarios WHERE col_periodoid='".intval($_REQUEST['periodoid'])."' AND col_materiaid='".$item['col_id']."'");
                    $sthh->execute();
                    $horariosData = $sthh->fetch(PDO::FETCH_OBJ);

                    if($horariosData->col_lunes != '') $horarios[$item['col_id'].'-lunes'] = $horariosData->col_lunes;
                    if($horariosData->col_martes != '') $horarios[$item['col_id'].'-martes'] = $horariosData->col_martes;
                    if($horariosData->col_miercoles != '') $horarios[$item['col_id'].'-miercoles'] = $horariosData->col_miercoles;
                    if($horariosData->col_jueves != '') $horarios[$item['col_id'].'-jueves'] = $horariosData->col_jueves;
                    if($horariosData->col_viernes != '') $horarios[$item['col_id'].'-viernes'] = $horariosData->col_viernes;
                    if($horariosData->col_sabado != '') $horarios[$item['col_id'].'-sabado'] = $horariosData->col_sabado;
                    if($horariosData->col_domingo != '') $horarios[$item['col_id'].'-domingo'] = $horariosData->col_domingo;

                }else{
                    $sthh = $this->db->prepare("SELECT * FROM tbl_horarios_posgrados WHERE col_periodoid='".intval($_REQUEST['periodoid'])."' AND col_materiaid='".$item['col_id']."'");
                    $sthh->execute();
                    $horariosData = $sthh->fetchAll();
                    $xi = 0;
                    foreach($horariosData as $horario){
                        $result[$i]['horarios'][$xi]['data'] = '<b>'.$dias[$horario['col_dia']].':</b> '.$horario['col_hora_inicio'].' - '.$horario['col_hora_fin'];
                        $result[$i]['horarios'][$xi]['id'] = $horario['col_id'];
                        $xi++;
                    }

                    $sthPo = $this->db->prepare("SELECT * FROM tbl_materias_ponderacion WHERE col_periodoid='".intval($_REQUEST['periodoid'])."' AND col_materiaid='".$item['col_id']."'");
                    $sthPo->execute();
                    $ponderacionData = $sthPo->fetch(PDO::FETCH_OBJ);
                    if($ponderacionData) {
                        $result[$i]['ponderacion'] = json_decode($ponderacionData->col_ponderacion, true);
                    }else{
                        $result[$i]['ponderacion'] = '';
                    }
                }
                $result[$i]['periodoid'] = $periodoData->col_id;

                $i++;


            }
        }else{
            $result = $horarios = [];
            $claves = Array('00000');
        }

        $carrera = getCarrera($periodoData->col_carreraid, $this->db);
        $respuesta['periodo'] = $periodoData->col_nombre;
        $respuesta['modalidad'] = $modalidades[$periodoData->col_modalidad];
        $respuesta['modalidad_numero'] = $periodoData->col_modalidad;
        $respuesta['semestre'] = $periodoData->col_grado;
        $respuesta['grupo'] = $periodoData->col_grupo;
        $respuesta['aprobado'] = $periodoData->col_aprobado;
        $respuesta['carrera'] = $carrera['nombre'];
        $respuesta['listMaterias'] = $result;
        $respuesta['clavesMaterias'] = $claves;
        $respuesta['horarios'] = $horarios;
        $respuesta['posgrado'] = $isPosgrado;

        // $respuesta['debug'] = $queryMaterias;
        return $this->response->withJson($respuesta);

    });

    $this->get('/listPlanesEstudios', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_planes_estudios ORDER BY col_id ASC");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = fixEncode($item['col_id']);
            $result[$i]['label'] = fixEncode($item['col_nombre']);
            $result[$i]['text'] = fixEncode($item['col_nombre']);
            $i++;
        }

        return $this->response->withJson($result);
    });

    $this->get('/getTransversales', function (Request $request, Response $response, array $args) {
        $query = "SELECT * FROM tbl_materias WHERE col_clave LIKE 'TR%' AND col_plan_estudios='".intval($_REQUEST['plan'])."' AND col_carrera='".intval($_REQUEST['carreraid'])."' ORDER BY col_id ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = fixEncode($item['col_id']);
            $result[$i]['label'] = fixEncode($item['col_nombre']).' ('.$item['col_clave'].')';
            $result[$i]['text'] = fixEncode($item['col_nombre']).' ('.$item['col_clave'].')';
            $i++;
        }
        // $result['debug'] = $query;
        return $this->response->withJson($result);
    });



    $this->get('/listPeriodos', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_periodos ORDER BY col_nombre ASC");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = fixEncode($item['col_nombre'].' ('.$item['col_grado'].$item['col_grupo'].')');
            $result[$i]['text'] = fixEncode($item['col_nombre'].' ('.$item['col_grado'].$item['col_grupo'].')');
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listPeriodosCalificaciones', function (Request $request, Response $response, array $args) {

        $query = "SELECT p.* FROM tbl_calificaciones c LEFT OUTER JOIN tbl_periodos p ON p.col_id=c.col_periodoid GROUP BY c.col_periodoid ORDER BY p.col_nombre ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = fixEncode($item['col_nombre'].' ('.$item['col_grado'].$item['col_grupo'].')');
            $result[$i]['text'] = fixEncode($item['col_nombre'].' ('.$item['col_grado'].$item['col_grupo'].')');
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listPeriodosAlumnos', function (Request $request, Response $response, array $args) {

        $sth_taxo = $this->db->prepare("SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid='".intval($_REQUEST[alumno])."' ORDER BY col_id ASC");
        $sth_taxo->execute();
        $data_taxo = $sth_taxo->fetchAll();
        foreach($data_taxo as $item_taxo){
            $periodos[] = $item_taxo['col_periodoid'];
        }

        $sth = $this->db->prepare("SELECT * FROM tbl_periodos WHERE col_id IN (".implode(',', $periodos).") ORDER BY col_nombre ASC");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = fixEncode($item['col_nombre'].' ('.$item['col_grado'].$item['col_grupo'].')');
            $result[$i]['text'] = fixEncode($item['col_nombre'].' ('.$item['col_grado'].$item['col_grupo'].')');
            $i++;
        }

        return $this->response->withJson($result);

    });

});
// Termina routes.periodos.php