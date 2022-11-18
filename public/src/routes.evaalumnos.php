<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de evaluación de alumnos.
 *
 * Lista de funciones
 *
 * /evaalumnos
 * - /removeEva
 * - /get
 * - /updateEvaObservaciones
 * - /updateEva
 * - /guardarEva
 * - /guardarRespuestas
 * - /listEvaluaciones
 * - /listEvaluacionesAlumnos
 * - /listPeriodos
 * - /copyEva
 * - /listMaestros
 * - /evaalumnos
 * - /listPreguntasResultados
 * - /listPreguntasAlumnos
 * - /removeEvaPregunta
 * - /getPregunta
 * - /updateEvaPregunta
 * - /guardarEvaPregunta
 * - /listEvaluacionesPreguntas
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

/*
 Al aprobar llenar el campo next
*/

$app->group('/evaalumnos', function () {

    $this->delete('/removeEva', function (Request $request, Response $response, $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_eva_alumnos WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_eva_alumnos', '', '', 'Evaluación de Alumnos', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->get('/get', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $query = 'SELECT * FROM tbl_eva_alumnos WHERE col_id="'.intval($_REQUEST['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $data->col_titulo = fixEncode($data->col_titulo);
        // $data->col_especificos = unserialize(stripslashes($data->col_especificos));

        return $this->response->withJson($data);
    });

    $this->put('/updateEvaObservaciones', function (Request $request, Response $response, $args) {
        global $dblog;

        $userid = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        foreach($input->observaciones as $k => $v) {
            if($v) {
                $data = array(
                    "col_observaciones" => trim($v),
                    "col_estatus" => intval($input->estatus),
                    "col_updated_at" => date("Y-m-d H:i:s"),
                    "col_updated_by" => $userid,
                );

                $query = 'UPDATE tbl_eva_alumnos_observaciones SET '.prepareUpdate($data).' WHERE col_id="'.$k.'"';

                $dblog = new DBLog($query, 'tbl_eva_alumnos_observaciones', '', '', 'Evaluaciones de Alumnos', $this->db);
                $dblog->where = array('col_id' => intval($k));
                $dblog->prepareLog();

                $sth = $this->db->prepare($query);
                $sth->execute();

                $dblog->saveLog();

                $query = 'SELECT * FROM tbl_eva_alumnos_observaciones WHERE col_id="'.intval($k).'"';
                $sth = $this->db->prepare($query);
                $sth->execute();
                $data = $sth->fetch(PDO::FETCH_OBJ);

                $query = 'UPDATE tbl_eva_alumnos_respuestas SET col_aprobado="'.intval($input->estatus).'" WHERE col_materiaid="'.$data->col_materiaid.'" AND col_alumnoid="'.$data->col_alumnoid.'" AND col_maestroid="'.$data->col_maestroid.'" AND col_evaid="'.$data->col_evaid.'"';

                $dblog = new DBLog($query, 'tbl_eva_alumnos_respuestas', '', '', 'Evaluaciones de Alumnos', $this->db);
                $dblog->where = array('col_materiaid' => $data->col_materiaid, 'col_alumnoid' => $data->col_alumnoid, 'col_maestroid' => $data->col_maestroid, 'col_evaid' => $data->col_evaid);
                $dblog->prepareLog();

                $sth = $this->db->prepare($query);
                $sth->execute();

                $dblog->saveLog();

            }

        }

        $_response['debug'] = $query;

        $_response['status'] = 'true';

        return $this->response->withJson($_response);

    });

    $this->put('/updateEva', function (Request $request, Response $response, $args) {
        $dblog->saveLog();

        $userid = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $data = array(
            "col_titulo" => $input->titulo,
            "col_group_periodoid" => intval($input->periodo),
            "col_estatus" => intval($input->estatus),
            "col_para" => intval($input->disponible),
            // "col_especificos" => addslashes(serialize($input->maestros)),
            "col_updated_at" => date("Y-m-d H:i:s"),
            "col_updated_by" => $userid,
        );

        $query = 'UPDATE tbl_eva_alumnos SET '.prepareUpdate($data).' WHERE col_id="'.$input->id.'"';

        $dblog = new DBLog($query, 'tbl_eva_alumnos', '', '', 'Evaluaciones de Alumnos', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);

        if($sth->execute()){
            $dblog->saveLog();
            $_response['status'] = 'true';
        }

        return $this->response->withJson($_response);

    });

    $this->post('/guardarEva', function (Request $request, Response $response, $args) {
        global $dblog;

        $userid = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $data = array(
            "col_titulo" => $input->titulo,
            "col_group_periodoid" => intval($input->periodo),
            "col_estatus" => intval($input->estatus),
            "col_para" => intval($input->disponible),
            // "col_especificos" => addslashes(serialize($input->maestros)),
            "col_created_at" => date("Y-m-d H:i:s"),
            "col_created_by" => $userid,
            "col_updated_at" => date("Y-m-d H:i:s"),
            "col_updated_by" => $userid,
        );

        $query = 'INSERT INTO tbl_eva_alumnos ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';

        $dblog = new DBLog($query, 'tbl_eva_alumnos', '', '', 'Evaluaciones de Alumnos', $this->db);
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);

        if($sth->execute()){
            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();
            $_response['status'] = 'true';
        }

        return $this->response->withJson($_response);

    });

    $this->post('/guardarRespuestas', function (Request $request, Response $response, $args) {
        global $dblog;

        $userid = getCurrentUserID();
        $alumnoPeriodoID = getCurrentAlumnoPeriodoID($this->db);

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        foreach($input->respuestas as $k => $v){
            if($v == '') continue;

            $data = array(
                "col_evaid" => intval($input->evaid),
                "col_preguntaid" => intval($k),
                "col_respuesta" => trim($v),
                "col_alumnoid" => intval($userid),
                "col_aprobado" => 0,
                "col_created_at" => date("Y-m-d H:i:s"),
                "col_created_by" => $userid,
                "col_updated_at" => date("Y-m-d H:i:s"),
                "col_updated_by" => $userid,
            );

            $query = 'INSERT INTO tbl_eva_alumnos_respuestas ('.implode(",", array_keys($data)).')
            VALUES("'.implode('", "', array_values($data)).'")';

            $dblog = new DBLog($query, 'tbl_eva_alumnos_respuestas', '', '', 'Evaluaciones de Alumnos', $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();
        }

        $_response['status'] = 'true';
        $resultadoEva = getResultadoEvaAlumnos($userid, $this->db, $input->evaid);
        $_response['resultado'] = $resultadoEva['resultado'];

        return $this->response->withJson($_response);

    });

    $this->get('/listEvaluaciones', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_eva_alumnos ORDER BY col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        $stO = array(0 => 'no', 1 => 'yes');
        $estatus = array(0 => 'Inactivo <i class="far fa-times-circle text-danger"></i>', 1 => 'Activo <i class="far fa-clock text-info"></i>', 2 => 'Completado <i class="far fa-check-circle text-success"></i>');
        foreach($todos as $item){

            $query = 'SELECT * FROM tbl_periodos_nombres WHERE col_id="'.$item['col_group_periodoid'].'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $dataPeriodo = $sth->fetch(PDO::FETCH_OBJ);

            $query = 'SELECT * FROM tbl_alumnos_taxonomia WHERE col_groupid="'.$item['col_group_periodoid'].'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $totaInscritos = $sth->rowCount();

            $query = 'SELECT * FROM tbl_eva_alumnos_respuestas WHERE col_evaid="'.$item['col_id'].'" GROUP BY col_alumnoid';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $totaRespondieron = $sth->rowCount();

            $tiposPara = array(0 => 'Todos los Alumons', 1 => 'Semestral', 2 => 'Cuatrimestral', 3 => 'Maestria', 4 => 'Doctorado');
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['col_titulo'] = fixEncode($item['col_titulo']);
            $result[$i]['periodo'] = fixEncode($dataPeriodo->col_nombre);
            $result[$i]['estatus'] = $estatus[$item['col_estatus']];
            $result[$i]['info'] = $totaRespondieron.'/'.$totaInscritos;
            $result[$i]['para'] = $tiposPara[$item['col_para']];
            $result[$i]['opciones'] = '<div style="text-align: center;"><a class="opcion-table" title="Administrar Preguntas" href="#/pages/evaalumnos/preguntas/'.$item['col_id'].'"><i class="fas fa-question-circle"></i></a>';
            $result[$i]['opciones'] .= '&nbsp;&nbsp;<a class="opcion-table" title="Aprobar Evaluaciones" href="#/pages/evaalumnos/revisar/'.$item['col_id'].'"><i class="fas fa-check-circle"></i></a>';
            $result[$i]['opciones'] .= '&nbsp;&nbsp;<a class="opcion-table" title="Duplicar Evaluación y Preguntas" href="#/pages/evaalumnos/duplicar/'.$item['col_id'].'"><i class="far fa-copy"></i></a></div>';
            $result[$i]['col_fecha'] = $item['col_created_at'];

            $i++;
        }
        return $this->response->withJson($result);

    });

    $this->get('/listEvaluacionesAlumnos', function (Request $request, Response $response, array $args) {

        $query = "SELECT CONCAT(u.col_nombres, ' ', u.col_apellidos) AS alumno, r.col_alumnoid, r.col_aprobado FROM tbl_eva_alumnos_respuestas r LEFT OUTER JOIN tbl_alumnos u ON u.col_id=r.col_alumnoid WHERE r.col_evaid='".intval($_REQUEST['id'])."' GROUP BY r.col_alumnoid";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;


        $query = 'SELECT * FROM tbl_eva_alumnos WHERE col_id="'.$_REQUEST['id'].'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataEva = $sth->fetch(PDO::FETCH_OBJ);

        $estatus = array(0 => 'En revisión <i class="far fa-clock text-info"></i>', 1 => 'Aprobado <i class="far fa-check-circle text-success"></i>');

        foreach($todos as $item){


            $query = 'SELECT SUM(IF(col_respuesta = "A", 1, 0)) AS visual, SUM(IF(col_respuesta = "B", 1, 0)) AS auditivo, SUM(IF(col_respuesta = "C", 1, 0)) AS cinestesico FROM tbl_eva_alumnos_respuestas WHERE col_alumnoid="'.$item['col_alumnoid'].'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $dataRespuestas = $sth->fetch(PDO::FETCH_OBJ);


            $result[$i]['alumno'] = fixEncode(trim($item['alumno'])).$item['col_alumnoid'];
            $result[$i]['visual'] = $dataRespuestas->visual;
            $result[$i]['auditivo'] = $dataRespuestas->auditivo;
            $result[$i]['cinestesico'] = $dataRespuestas->cinestesico;
            $estilo = "Visual";
            if($dataRespuestas->visual > $dataRespuestas->auditivo && $dataRespuestas->visual > $dataRespuestas->cinestesico) $estilo = 'Visual';
            if($dataRespuestas->auditivo > $dataRespuestas->visual && $dataRespuestas->auditivo > $dataRespuestas->cinestesico) $estilo = 'Auditivo';
            if($dataRespuestas->cinestesico > $dataRespuestas->visual && $dataRespuestas->cinestesico > $dataRespuestas->auditivo) $estilo = 'Cinestésico';
            $result[$i]['resultado'] = $estilo;

            // $result[$i]['estatus'] = $estatus[$item['col_aprobado']];
            // $result[$i]['opciones'] = '<div style="text-align: center;">';
            // $result[$i]['opciones'] .= '<a class="opcion-table" title="Revisar" href="#/pages/evaalumnos/revisar/'.$_REQUEST['id'].'/maestro/'.$item['col_maestroid'].'/materia/'.$item['col_materiaid'].'"><i class="fas fa-file-alt text-info"></i> Revisar</a>';
            // $result[$i]['opciones'] .= '</div>';

            $i++;
        }
        $_result['list'] = $result;
        $_result['tituloEva'] = $dataEva->col_titulo;
        return $this->response->withJson($_result);

    });

    $this->get('/listPeriodos', function (Request $request, Response $response, array $args) {
        $currentPeriodos = getCurrentPeriodos($this->db);
        $modalidades = array(1=>'Semestral', 2=>'Cuatrimestral', 3=>'Maestría', 4=>'Doctorado');

        $query = 'SELECT * FROM tbl_periodos WHERE col_id IN ('.implode(',', $currentPeriodos).') GROUP BY col_groupid';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        foreach($todos as $item){
            $carreraData = getCarrera($item['col_carreraid'], $this->db);
            $result[] = array(
                'nombre' => fixEncode($item['col_nombre']).' ('.$modalidades[$item['col_modalidad']].')',
                'id' => $item['col_groupid']);
        }

        return $this->response->withJson($result);

    });

    $this->post('/copyEva', function (Request $request, Response $response, $args) {
        global $dblog;

        $userid = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $query = 'SELECT * FROM tbl_eva_alumnos WHERE col_id="'.intval($input->id).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $source = $sth->fetch(PDO::FETCH_OBJ);

        $data = array(
            "col_titulo" => 'Copia de: '.$source->col_titulo,
            "col_group_periodoid" => intval($source->col_group_periodoid),
            "col_estatus" => 0,
            "col_para" => $source->col_para,
            // "col_especificos" => $source->col_especificos,
            "col_created_at" => date("Y-m-d H:i:s"),
            "col_created_by" => $userid,
            "col_updated_at" => date("Y-m-d H:i:s"),
            "col_updated_by" => $userid,
        );

        $query = 'INSERT INTO tbl_eva_alumnos ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';

        $dblog = new DBLog($query, 'tbl_eva_alumnos', '', '', 'Evaluaciones de Alumnos', $this->db);
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
        $dblog->saveLog();

        $copiedEvaID = $this->db->lastInsertId();

        $queryx = "SELECT * FROM tbl_eva_alumnos_preguntas WHERE col_evaid='".$input->id."' ORDER BY col_id ASC";
        $sth = $this->db->prepare($queryx);
        $sth->execute();
        $todos = $sth->fetchAll();

        foreach($todos as $item) {

            $data = array(
                "col_evaid" => $copiedEvaID,
                "col_pregunta" => $item['col_pregunta'],
                "col_respuesta_visual" => addslashes($item['col_respuesta_visual']),
                "col_respuesta_auditiva" => addslashes($item['col_respuesta_auditiva']),
                "col_respuesta_cinestesica" => addslashes($item['col_respuesta_cinestesica']),
                "col_created_at" => date("Y-m-d H:i:s"),
                "col_created_by" => $userid,
                "col_updated_at" => date("Y-m-d H:i:s"),
                "col_updated_by" => $userid,
            );

            $query = 'INSERT INTO tbl_eva_alumnos_preguntas ('.implode(",", array_keys($data)).')
            VALUES("'.implode('", "', array_values($data)).'")';

            $dblog = new DBLog($query, 'tbl_eva_alumnos_preguntas', '', '', 'Evaluaciones de Alumnos', $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();

        }


         $_response['status'] = 'true';

        return $this->response->withJson($_response);

    });

    $this->get('/listMaestros', function (Request $request, Response $response, array $args) {
        global $download_url;

        $alumnoid = getCurrentUserID();
        $periodoid = getLastPeriodoAlumno(intval($alumnoid), $this->db);
        // $materias = getMateriasByAlumno($alumnoid, $this->db);
        $maestrosMaterias = getTodosMisMaestros($this->db, $alumnoid, true);
        $periodoData = getPeriodo($periodoid, $this->db, false);
        $i = 0;


        $dataPeriodo = getPeriodo($periodoid, $this->db, false);
        $query = 'SELECT * FROM tbl_eva_alumnos WHERE col_estatus="1" AND col_group_periodoid="'.$dataPeriodo->col_groupid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $evaData = $sth->fetch(PDO::FETCH_OBJ);

        // 0 => 'Todos los Maestros'
        // 1 => 'Solo de Materias Curriculares'
        // 2 => 'Solo Academias'
        // 3 => 'Solo Talleres'
        // 4 => 'Solo Club de Lectura'
        // 5 => 'Maestros Especificos'

        foreach($maestrosMaterias as $mmItem){
            list($maestroid, $materiaid) = explode('|', $mmItem);

            $query_materia = "SELECT * FROM tbl_materias WHERE col_id='".$materiaid."'";
            $sth_materias = $this->db->prepare($query_materia);
            $sth_materias->execute();
            $materia = $sth_materias->fetch(PDO::FETCH_OBJ);

            $query = "SELECT * FROM tbl_eva_alumnos_respuestas WHERE col_maestroid='".$maestroid."' AND col_alumnoid='".$alumnoid."' AND col_evaid='".$evaData->col_id."' ";
            $sth = $this->db->prepare($query);
            $sth->execute();

            if($sth->rowCount() == 0){
                $claveMateria = substr(trim($materia->col_clave), 0, strlen($materia->col_clave)-1);
                if(strlen(trim($materia->col_clave)) == 4) $claveMateria = trim($materia->col_clave);
                // $query_maestro = "SELECT CONCAT(u.col_firstname, ' ', u.col_lastname) AS nombre_maestro FROM tbl_users u WHERE u.col_id='".$maestroid."'";
                $query_maestro = "SELECT t.col_id AS ID, u.col_id AS maestroid, CONCAT(u.col_firstname, ' ', u.col_lastname) AS nombre_maestro FROM tbl_maestros_taxonomia t LEFT OUTER JOIN tbl_users u ON u.col_id=t.col_maestroid WHERE t.col_materia_clave LIKE '".$claveMateria."%' AND t.col_periodoid='".$periodoid."'";
                $sth_tax = $this->db->prepare($query_maestro);
                $sth_tax->execute();
                $maestro = $sth_tax->fetch(PDO::FETCH_OBJ);

                if($evaData->col_para != 0) {
                    if($evaData->col_para == 1 && in_array(strtoupper(substr($materia->col_clave, 0, 2)), array('AC', 'TL', 'CL'))) continue;
                    if($evaData->col_para == 2 && strtoupper(substr($materia->col_clave, 0, 2)) != 'AC') continue;
                    if($evaData->col_para == 3 && strtoupper(substr($materia->col_clave, 0, 2)) != 'TL') continue;
                    if($evaData->col_para == 4 && strtoupper(substr($materia->col_clave, 0, 2)) != 'CL') continue;
                    // if($evaData->col_para == 5 && !in_array($maestro->maestroid, unserialize(stripslashes($evaData->col_especificos)))) continue;
                }

                $result[$i]['materia'] = fixEncode($materia->col_nombre);
                $result[$i]['maestro'] = fixEncode($maestro->nombre_maestro);
                $result[$i]['evaluar'] .= '<a title="Evaluar" href="#/pages/alumnos/evaMaestros/evaluar/'.$maestro->ID.'/'.$materia->col_id.'"><i class="fas fa-check-circle text-info"></i> Evaluar</a>';
                $i++;
            }
        }
        return $this->response->withJson($result);


    });

});

$app->group('/evaalumnos-preguntas', function () {

    $this->get('/listPreguntasResultados', function (Request $request, Response $response, array $args) {

        $alumnoPeriodoID = getCurrentAlumnoPeriodoID($this->db);

        $input = $request->getParsedBody();

        $query = 'SELECT * FROM tbl_users WHERE col_id="'.intval($_REQUEST['maestroid']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMaestro = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_materias WHERE col_id="'.intval($_REQUEST['materiaid']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMateria = $sth->fetch(PDO::FETCH_OBJ);

        $tipoMaestro = 'regular';
        if(strtoupper(substr($dataMateria->col_clave, 0, 2)) == 'TL') $tipoMaestro = 'taller';
        if(strtoupper(substr($dataMateria->col_clave, 0, 2)) == 'AC') $tipoMaestro = 'academia';
        if(strtoupper(substr($dataMateria->col_clave, 0, 2)) == 'CL') $tipoMaestro = 'club';


            $subQuery = 'SELECT col_id FROM tbl_materias WHERE col_carrera="'.$dataMateria->col_carrera.'" AND col_plan_estudios="'.$dataMateria->col_plan_estudios.'" AND UPPER(col_clave) LIKE "'.strtoupper(claveMateria($dataMateria->col_clave)).'%"';
            // $tiposRespuesta = array(1 => 'S,CS,AV,N', 2 => 'E,B,R,M', 3 => 'Si,No');
            $i = 0;
            // $query = 'SELECT p.col_pregunta, SUM(IF(r.col_respuesta = "S", 1, 0)) AS S, SUM(IF(r.col_respuesta = "CS", 1, 0)) AS CS, SUM(IF(r.col_respuesta = "AV", 1, 0)) AS AV, '.
            //         'SUM(IF(r.col_respuesta = "N", 1, 0)) AS N FROM tbl_eva_alumnos_respuestas r LEFT OUTER JOIN tbl_eva_alumnos_preguntas p ON p.col_id=r.col_preguntaid '.
            //         'WHERE p.col_respuesta=1 AND r.col_materiaid IN ('.$subQuery.') AND r.col_maestroid="'.intval($_REQUEST['maestroid']).'" '.
            //         'AND r.col_evaid="'.intval($_REQUEST['evaid']).'" GROUP BY r.col_preguntaid';

            $query = 'SELECT * FROM tbl_eva_alumnos_preguntas WHERE col_evaid="'.intval($_REQUEST['evaid']).'" AND col_tipo_maestro="'.$tipoMaestro.'" AND col_respuesta=1';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach($todos as $item){
                $results = calcularResultados($item['col_id'], intval($_REQUEST['evaid']), intval($_REQUEST['maestroid']), $subQuery, $this->db);

                $result_1[$i]['id'] = $item['col_id'];
                $result_1[$i]['pregunta'] = fixEncode($item['col_pregunta']);
                $result_1[$i]['S'] = intval($results['S']);
                $result_1[$i]['CS'] = intval($results['CS']);
                $result_1[$i]['AV'] = intval($results['AV']);
                $result_1[$i]['N'] = intval($results['N']);
                $i++;
            }

            $i = 0;
            // $query = 'SELECT p.col_pregunta, SUM(IF(r.col_respuesta = "E", 1, 0)) AS E, SUM(IF(r.col_respuesta = "B", 1, 0)) AS B, SUM(IF(r.col_respuesta = "R", 1, 0)) AS R, '.
            //         'SUM(IF(r.col_respuesta = "M", 1, 0)) AS M FROM tbl_eva_alumnos_respuestas r LEFT OUTER JOIN tbl_eva_alumnos_preguntas p ON p.col_id=r.col_preguntaid '.
            //         'WHERE p.col_respuesta=2 AND r.col_materiaid IN ('.$subQuery.') AND r.col_maestroid="'.intval($_REQUEST['maestroid']).'" AND '.
            //         'r.col_evaid="'.intval($_REQUEST['evaid']).'" GROUP BY r.col_preguntaid';

            $query = 'SELECT * FROM tbl_eva_alumnos_preguntas WHERE col_evaid="'.intval($_REQUEST['evaid']).'" AND col_tipo_maestro="'.$tipoMaestro.'" AND col_respuesta=2';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach($todos as $item){
                $results = calcularResultados($item['col_id'], intval($_REQUEST['evaid']), intval($_REQUEST['maestroid']), $subQuery, $this->db);

                $result_2[$i]['id'] = $item['col_id'];
                $result_2[$i]['pregunta'] = fixEncode($item['col_pregunta']);
                $result_2[$i]['E'] = intval($results['E']);
                $result_2[$i]['B'] = intval($results['B']);
                $result_2[$i]['R'] = intval($results['R']);
                $result_2[$i]['M'] = intval($results['M']);
                $i++;
            }

            $i = 0;
            // $query = 'SELECT p.col_pregunta, SUM(IF(r.col_respuesta = "SI", 1, 0)) AS SI, SUM(IF(r.col_respuesta = "NO", 1, 0)) AS NO FROM tbl_eva_alumnos_respuestas r '.
            //         'LEFT OUTER JOIN tbl_eva_alumnos_preguntas p ON p.col_id=r.col_preguntaid WHERE p.col_respuesta=3 AND r.col_materiaid IN ('.$subQuery.') '.
            //         'AND r.col_maestroid="'.intval($_REQUEST['maestroid']).'" AND r.col_evaid="'.intval($_REQUEST['evaid']).'" GROUP BY r.col_preguntaid';

            $query = 'SELECT * FROM tbl_eva_alumnos_preguntas WHERE col_evaid="'.intval($_REQUEST['evaid']).'" AND col_tipo_maestro="'.$tipoMaestro.'" AND col_respuesta=3';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach($todos as $item){
                $results = calcularResultados($item['col_id'], intval($_REQUEST['evaid']), intval($_REQUEST['maestroid']), $subQuery, $this->db);

                $result_3[$i]['id'] = $item['col_id'];
                $result_3[$i]['pregunta'] = fixEncode($item['col_pregunta']);
                $result_3[$i]['SI'] = intval($results['SI']);
                $result_3[$i]['NO'] = intval($results['NO']);
                $i++;
            }

            $i = 0;
            $query = 'SELECT * FROM tbl_eva_alumnos_observaciones WHERE col_observaciones!="" AND col_materiaid IN ('.$subQuery.') AND '.
                'col_maestroid="'.intval($_REQUEST['maestroid']).'" AND col_evaid="'.intval($_REQUEST['evaid']).'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach($todos as $item){
                $observaciones[$i]['id'] = $item['col_id'];
                $observaciones[$i]['observacion'] = fixEncode($item['col_observaciones']);
                $i++;
            }




        // $_response['tituloEva'] = fixEncode($dataEva->col_titulo);
        $_response['maestro'] = fixEncode($dataMaestro->col_firstname.' '.$dataMaestro->col_lastname);
        // $_response['maestroid'] = $dataMaestro->col_id;
        $_response['materia'] = fixEncode($dataMateria->col_nombre);
        $_response['listPreguntas1'] = $result_1;
        $_response['listPreguntas2'] = $result_2;
        $_response['listPreguntas3'] = $result_3;
        $_response['listObservaciones'] = $observaciones;

        return $this->response->withJson($_response);
    });

    $this->get('/listPreguntasAlumnos', function (Request $request, Response $response, array $args) {

        $alumnoPeriodoID = getCurrentAlumnoPeriodoID($this->db);
        $alumnoid = getCurrentUserID();
        $dataPeriodo = getPeriodo($alumnoPeriodoID, $this->db, false);
        $dataCarrera = getCarrera($dataPeriodo->col_carreraid, $this->db);

        $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataAlumno = $sth->fetch(PDO::FETCH_OBJ);
        $carreraModalidad = strtolower($dataCarrera['modalidad']);


        $query = 'SELECT * FROM tbl_eva_alumnos WHERE col_estatus="1" AND col_para="'.($carreraModalidad + 1).'" AND col_group_periodoid="'.$dataPeriodo->col_groupid.'" ORDER BY col_id LIMIT 1';
        $sth = $this->db->prepare($query);
        $sth->execute();
        if($sth->rowCount() == 0) {
            $_response['status'] = 'error';
            return $this->response->withJson($_response);
        } else if($sth->rowCount()) {
            $dataEva = $sth->fetch(PDO::FETCH_OBJ);


            $query = 'SELECT * FROM tbl_eva_alumnos_respuestas WHERE col_evaid="'.$dataEva->col_id.'" AND col_alumnoid="'.$alumnoid.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            if($sth->rowCount()) {
                $_response['status'] = 'already_done';
                return $this->response->withJson($_response);
            }

            $i = 0;
            $query = "SELECT * FROM tbl_eva_alumnos_preguntas WHERE col_evaid='".$dataEva->col_id."' ORDER BY RAND()";
            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach($todos as $item){
                $result_1[$i]['id'] = $item['col_id'];
                $result_1[$i]['pregunta'] = fixEncode($item['col_pregunta']);
                $rand = rand(0, 5);
                $result_1[$i]['rand'] = $rand;
                if($rand == 0) {
                    $result_1[$i]['x'] = array('v'=>'A', 'respuesta'=>fixEncode($item['col_respuesta_visual']));
                    $result_1[$i]['y'] = array('v'=>'B', 'respuesta'=>fixEncode($item['col_respuesta_auditiva']));
                    $result_1[$i]['z'] = array('v'=>'C', 'respuesta'=>fixEncode($item['col_respuesta_cinestesica']));
                }
                if($rand == 1) {
                    $result_1[$i]['x'] = array('v'=>'A', 'respuesta'=>fixEncode($item['col_respuesta_visual']));
                    $result_1[$i]['z'] = array('v'=>'B', 'respuesta'=>fixEncode($item['col_respuesta_auditiva']));
                    $result_1[$i]['y'] = array('v'=>'C', 'respuesta'=>fixEncode($item['col_respuesta_cinestesica']));
                }
                if($rand == 2) {
                    $result_1[$i]['z'] = array('v'=>'A', 'respuesta'=>fixEncode($item['col_respuesta_visual']));
                    $result_1[$i]['x'] = array('v'=>'B', 'respuesta'=>fixEncode($item['col_respuesta_auditiva']));
                    $result_1[$i]['y'] = array('v'=>'C', 'respuesta'=>fixEncode($item['col_respuesta_cinestesica']));
                }
                if($rand == 3) {
                    $result_1[$i]['y'] = array('v'=>'A', 'respuesta'=>fixEncode($item['col_respuesta_visual']));
                    $result_1[$i]['x'] = array('v'=>'B', 'respuesta'=>fixEncode($item['col_respuesta_auditiva']));
                    $result_1[$i]['z'] = array('v'=>'C', 'respuesta'=>fixEncode($item['col_respuesta_cinestesica']));
                }
                if($rand == 4) {
                    $result_1[$i]['z'] = array('v'=>'A', 'respuesta'=>fixEncode($item['col_respuesta_visual']));
                    $result_1[$i]['y'] = array('v'=>'B', 'respuesta'=>fixEncode($item['col_respuesta_auditiva']));
                    $result_1[$i]['x'] = array('v'=>'C', 'respuesta'=>fixEncode($item['col_respuesta_cinestesica']));
                }
                if($rand == 5) {
                    $result_1[$i]['y'] = array('v'=>'A', 'respuesta'=>fixEncode($item['col_respuesta_visual']));
                    $result_1[$i]['z'] = array('v'=>'B', 'respuesta'=>fixEncode($item['col_respuesta_auditiva']));
                    $result_1[$i]['x'] = array('v'=>'C', 'respuesta'=>fixEncode($item['col_respuesta_cinestesica']));
                }
                $i++;
            }




        }

        $_response['tituloEva'] = fixEncode($dataEva->col_titulo);
        $_response['evaid'] = $dataEva->col_id;
        $_response['listPreguntas'] = $result_1;

        return $this->response->withJson($_response);
    });


    $this->delete('/removeEvaPregunta', function (Request $request, Response $response, $args) {
        global $dblog;
        $query = 'DELETE FROM tbl_eva_alumnos_preguntas WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_eva_alumnos_preguntas', '', '', 'Evaluación de Alumnos - Preguntas', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->get('/getPregunta', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $query = 'SELECT * FROM tbl_eva_alumnos_preguntas WHERE col_id="'.intval($_REQUEST['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $data->col_pregunta = fixEncode($data->col_pregunta);
        $data->col_respuesta_visual = fixEncode($data->col_respuesta_visual);
        $data->col_respuesta_auditiva = fixEncode($data->col_respuesta_auditiva);
        $data->col_respuesta_cinestesica = fixEncode($data->col_respuesta_cinestesica);

        return $this->response->withJson($data);
    });

    $this->put('/updateEvaPregunta', function (Request $request, Response $response, $args) {
        global $dblog;

        $userid = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $data = array(
            "col_pregunta" => addslashes($input->pregunta),
            "col_respuesta_visual" => addslashes($input->respuestaVisual),
            "col_respuesta_auditiva" => addslashes($input->respuestaAuditiva),
            "col_respuesta_cinestesica" => addslashes($input->respuestaCinestesica),
            "col_updated_at" => date("Y-m-d H:i:s"),
            "col_updated_by" => $userid,
        );

        $query = 'UPDATE tbl_eva_alumnos_preguntas SET '.prepareUpdate($data).' WHERE col_id="'.$input->id.'"';

        $dblog = new DBLog($query, 'tbl_eva_alumnos_preguntas', '', '', 'Evaluaciones de Alumnos', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);

        if($sth->execute()){
            $dblog->saveLog();
            $_response['status'] = 'true';
        }

        return $this->response->withJson($_response);

    });

    $this->post('/guardarEvaPregunta', function (Request $request, Response $response, $args) {
        global $dblog;

        $userid = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $data = array(
            "col_evaid" => $input->evaid,
            "col_pregunta" => addslashes($input->pregunta),
            "col_respuesta_visual" => addslashes($input->respuestaVisual),
            "col_respuesta_auditiva" => addslashes($input->respuestaAuditiva),
            "col_respuesta_cinestesica" => addslashes($input->respuestaCinestesica),
            "col_created_at" => date("Y-m-d H:i:s"),
            "col_created_by" => $userid,
            "col_updated_at" => date("Y-m-d H:i:s"),
            "col_updated_by" => $userid,
        );

        $query = 'INSERT INTO tbl_eva_alumnos_preguntas ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';

        $dblog = new DBLog($query, 'tbl_eva_alumnos_preguntas', '', '', 'Evaluaciones de Alumnos', $this->db);
        $dblog->prepareLog();

         $sth = $this->db->prepare($query);

        if($sth->execute()){
            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();
            $_response['status'] = 'true';
        }

        return $this->response->withJson($_response);

    });


    $this->get('/listEvaluacionesPreguntas', function (Request $request, Response $response, array $args) {

        $query = 'SELECT * FROM tbl_eva_alumnos WHERE col_id="'.intval($_REQUEST['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);
        $_response['evaluacion'] = fixEncode($data->col_titulo);

        $sth = $this->db->prepare("SELECT * FROM tbl_eva_alumnos_preguntas WHERE col_evaid='".$_REQUEST['id']."' ORDER BY col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        $tiposRespuesta = array(1 => 'S,CS,AV,N', 2 => 'E,B,R,M', 3 => 'Si,No');
        $tiposMaestro = array('regular' => 'Materia Curricular', 'academia' => 'Academia', 'taller' => 'Taller', 'club' => 'Club de Lectura');

        foreach($todos as $item){

            $result[$i]['id'] = $item['col_id'];
            $result[$i]['pregunta'] = fixEncode($item['col_pregunta']);
            $result[$i]['respuestaVisual'] = fixEncode($item['col_respuesta_visual']);
            $result[$i]['respuestaAuditiva'] = fixEncode($item['col_respuesta_auditiva']);
            $result[$i]['respuestaCinestesica'] = fixEncode($item['col_respuesta_cinestesica']);


            $i++;
        }

        $_response['preguntas'] = $result;

        return $this->response->withJson($_response);

    });


});
// Termina routes.evaalumnos.php
