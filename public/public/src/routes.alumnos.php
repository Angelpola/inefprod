<?php
/**
 *
 * Este archivo incluye todas las funciones que conectan a la base de datos del modulo de alumnos, así como las funciones
 * que devuelven información sobre periodos y carreras a las que estan incritos los alumnos.
 *
 * Lista de acciones vinculadas al modulo de alumnos
 *
 * /alumnos
 * - /listPeriodosInscrito
 * - /listPeriodosParaVincular
 * - /desvincularMateria
 * - /desvincularPeriodo
 * - /bajaPeriodo
 * - /vincularPeriodo
 * - /listaPeriodosAlumno
 * - /listaAlumnoBajas
 * - /getHorario
 * - /calificacionesByAlumno
 * - /calificacionesByAlumnoJSON
 * - /calificacionesByAlumnoJSONEdit
 * - /modificarCalificaciones
 * - /listAlumnosByPeriodo
 * - /listAlumnos
 * - /listCarreras
 * - /listPeriodos
 * - /listPeriodosNombre
 * - /listPeriodosGroups
 * - /list
 * - /listParaMaestros
 * - /listLectura
 * - /listSeguimiento
 * - /deleteServicios
 * - /deletePracticas
 * - /getPracticas
 * - /getServicios
 * - /deleteSeguimiento
 * - /guardarPractica
 * - /guadarServicio
 * - /addSeguimiento
 * - /listPracticas
 * - /listServicio
 * - /listTransversales
 * - /listTalleres
 * - /listAcademias
 * - /grupo
 * - /get
 * - /getFullInfo
 * - /updateRep
 * - /update
 * - /add
 * - /delete
 * - /deleteBaja
 * - /deleteAcademia
 * - /deleteTaller
 * - /docs
 * - /reset
 * - /accesoTutor
 * - /alumnos
 * - /listPeriodos
 * - /listPeriodosAlumno
 * - /listGrados
 * - /listGrupos
 * - /listGradosValue
 * - /listGruposValue
 * - /listCarrerasComplements
 * - /listCarreras
 * - /listCarrerasValue
 * - /listAlumnosValue
 * - /listAlumnosByCarrera
 * - /listAlumnos
 * - /listInscritos
 * - /getAlumnoPeriodo
 * - /listAlumnosByPeriodos
 */


use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/alumnos', function () {

    $this->get('/listPeriodosInscrito', function (Request $request, Response $response, array $args) {



        $query = 'SELECT t.col_status AS estatusAlumno, p.* FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_alumnoid="'.intval($_REQUEST['alumnoid']).'" ORDER BY p.col_grado DESC';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = fixEncode($item['col_nombre'].' ('.$item['col_grado'].$item['col_grupo'].')').($item['estatusAlumno'] == 1?'(Actual)':'');
            $result[$i]['text'] = fixEncode($item['col_nombre'].' ('.$item['col_grado'].$item['col_grupo'].')').($item['estatusAlumno'] == 1?'(Actual)':'');
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listPeriodosParaVincular', function (Request $request, Response $response, array $args) {

        $grados = trim($_REQUEST['grados']);
        $carrera = trim($_REQUEST['carrera']);
        $modalidad = trim($_REQUEST['modalidad']);

        $alumnoid = intval($_REQUEST['alumnoid']);

        if($alumnoid > 0) {
            $alumnoSTH = $this->db->prepare('SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"');
            $alumnoSTH->execute();
            $alumnoData = $alumnoSTH->fetch(PDO::FETCH_OBJ);

            $carreraData = getCarrera($alumnoData->col_carrera, $this->db);

            $grados = '0';
            $carrera = $alumnoData->col_carrera;
            $modalidad = $carreraData['modalidad_numero'];
        }

        $query = "SELECT * FROM tbl_periodos WHERE col_modalidad='".$modalidad."' AND col_carreraid='".$carrera."' AND col_grado NOT IN (".$grados.") ORDER BY col_nombre ASC";
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

    $this->delete('/desvincularMateria', function (Request $request, Response $response, array $args) {
        global $dblog;

        $userType = getCurrentUserType();
        if($userType != 'administrativo'){
            $_response['error'] = 'Acceso Invalido';
            return $this->response->withJson($_response);
        }

        $alumnoid = strtoupper(trim($_REQUEST['alumnoid']));
        $recordid = intval($_REQUEST['recordid']);

        $query = 'DELETE FROM tbl_calificaciones WHERE col_alumnoid="'.$alumnoid.'" AND col_id="'.intval($recordid).'"';

        $dblog = new DBLog($query, 'tbl_calificaciones', '', '', 'Calificaciones Alumnos', $this->db);
        $dblog->where = array('col_alumnoid' => intval($alumnoid), 'col_id' => intval($recordid));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->delete('/desvincularPeriodo', function (Request $request, Response $response, array $args) {
        global $dblog;
        $userType = getCurrentUserType();
        if($userType != 'administrativo'){
            $_response['error'] = 'Acceso Invalido';
            return $this->response->withJson($_response);
        }

        $alumnoid = strtoupper(trim($_REQUEST['alumnoid']));
        $periodoid = intval($_REQUEST['periodoid']);

        $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'" AND col_periodoid="'.intval($periodoid).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        if($sth->rowCount() > 0) {
            return $this->response->withJson(array('status' => 'error', 'message' => 'No puedes desvincular a un alumno de un periodo en el cual esta activo, primero debes activarlo en otro periodo para poder realizar la desvinculación.'));
        }

        $query = 'DELETE FROM tbl_alumnos_taxonomia WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.intval($periodoid).'"';

        $dblog = new DBLog($query, 'tbl_alumnos_taxonomia', '', '', 'Alumnos Taxonomia', $this->db);
        $dblog->where = array('col_alumnoid' => intval($alumnoid), 'col_periodoid' => intval($periodoid));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        /*
        $query = 'DELETE FROM tbl_academias WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.intval($periodoid).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();

        $query = 'DELETE FROM tbl_talleres WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.intval($periodoid).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        */

        /*
        $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'" AND col_periodoid="'.intval($periodoid).'"';
        $sth = $this->db->prepare($query);
        if($sth->rowCount() > 0) {

            $querya = 'SELECT p . * FROM tbl_alumnos_taxonomia t LEFT JOIN tbl_periodos p ON p.col_id = t.col_periodoid WHERE t.col_alumnoid="'.$alumnoid.'" ORDER BY col_grado DESC LIMIT 1';
            $stha = $this->db->prepare($querya);
            $stha->execute();
            $taxData = $stha->fetch(PDO::FETCH_OBJ);

            $query = 'UPDATE tbl_alumnos SET col_periodoid="'.$taxData->col_id.'" WHERE col_id="'.$alumnoid.'"';
            $stha = $this->db->prepare($query);
            $stha->execute();
        }
        */

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->delete('/activarPeriodo', function (Request $request, Response $response, array $args) {

        $userType = getCurrentUserType();
        if($userType != 'administrativo'){
            $_response['error'] = 'Acceso Invalido';
            return $this->response->withJson($_response);
        }

        $alumnoid = strtoupper(trim($_REQUEST['alumnoid']));
        $periodoid = intval($_REQUEST['periodoid']);
        $userid = getCurrentUserID();


        $query = 'UPDATE tbl_alumnos_taxonomia SET col_status=0, col_updated_at="'.date('Y-m-d h:i:s').'", col_updated_by="'.$userid.'"  WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid!="'.intval($periodoid).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();


        $query = 'UPDATE tbl_alumnos_taxonomia SET col_status=1, col_updated_at="'.date('Y-m-d h:i:s').'", col_updated_by="'.$userid.'"  WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.intval($periodoid).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();


        $query = 'UPDATE tbl_alumnos SET col_periodoid="'.intval($periodoid).'", col_updated_at="'.date('Y-m-d h:i:s').'", col_updated_by="'.$userid.'"  WHERE col_id="'.$alumnoid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->delete('/bajaPeriodo', function (Request $request, Response $response, array $args) {
        global $dblog;

        $alumnoid = strtoupper(trim($_REQUEST['alumnoid']));
        $periodoid = intval($_REQUEST['periodoid']);
        $userid = getCurrentUserID();
        $baja = 1;

        $queryAlumno = 'SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.intval($periodoid).'"';
        $alumnoSTH = $this->db->prepare($queryAlumno);
        $alumnoSTH->execute();
        $alumnoData = $alumnoSTH->fetch(PDO::FETCH_OBJ);
        if($alumnoData->col_baja == 1) {
            $baja = 0;
        }

        $query = 'UPDATE tbl_alumnos_taxonomia SET col_baja='.$baja.', col_updated_at="'.date('Y-m-d h:i:s').'", col_updated_by="'.$userid.'"  WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.intval($periodoid).'"';

        $dblog = new DBLog($query, 'tbl_alumnos_taxonomia', '', '', 'Alumnos Taxonomia', $this->db);
        $dblog->where = array('col_alumnoid' => intval($alumnoid), 'col_periodoid' => intval($periodoid));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->post('/vincularPeriodo', function (Request $request, Response $response, array $args) {
        global $dblog;

        $userType = getCurrentUserType();
        if($userType != 'administrativo'){
            $_response['error'] = 'Acceso Invalido';
            return $this->response->withJson($_response);
        }

        $input = $request->getParsedBody();
        $userid = getCurrentUserID();
        $alumnoid = strtoupper(trim($input['alumnoid']));
        $periodoid = intval($input['periodoid']);
        $periodoData = getPeriodo($periodoid, $this->db, false);

        $data = array(
            'col_alumnoid' => $alumnoid,
            'col_periodoid' => $periodoid,
            'col_groupid' => $periodoData->col_groupid,
            'col_status' => 0,
            'col_created_at' => date('Y-m-d h:i:s'),
            'col_created_by' => $userid,
            'col_updated_at' => date('Y-m-d h:i:s'),
            'col_updated_by' => $userid,
        );

        $query = 'INSERT INTO tbl_alumnos_taxonomia ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';

        $dblog = new DBLog($query, 'tbl_alumnos_taxonomia', '', '', 'Alumnos Taxonomia', $this->db);
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
        $dblog->saveLog();

        $result['status'] = 'true';
        return $this->response->withJson($result);
    });

    $this->get('/listaPeriodosAlumno', function (Request $request, Response $response, array $args) {

        $alumnoid = intval($_REQUEST['alumnoid']);

        $queryAlumno = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval($alumnoid).'"';
        $alumnoSTH = $this->db->prepare($queryAlumno);
        $alumnoSTH->execute();
        $alumnoData = $alumnoSTH->fetch(PDO::FETCH_OBJ);
        $periodoData = getPeriodo($alumnoData->col_periodoid, $this->db, false);
        $carreraData = getCarrera($alumnoData->col_carrera, $this->db);
        $_response['modalidad'] = $carreraData['modalidad_numero'];

        $query = 'SELECT t.col_status AS estatusAlumno, t.col_baja AS baja, t.col_created_by AS createdBy, t.col_created_at AS createdAt, t.col_updated_by AS updatedBy, t.col_updated_at AS updatedAt, p.* FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_alumnoid="'.intval($_REQUEST['alumnoid']).'" ORDER BY p.col_grado DESC';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $result = array();
        $i = 0;
        $tipos = array(
            0 => 'Visual',
            1 => 'Auditivo',
            2 => 'Cinestésico'
        );
        foreach($todos as $item){
            $carreraData = getCarrera($item['col_carreraid'], $this->db);
            $result[$i]['periodoID'] = $item['col_id'];
            $result[$i]['periodoNombre'] = fixEncode($item['col_nombre']).($item['estatusAlumno'] == 1?'&nbsp;&nbsp;<span class="badge badge-success">Actual</span>':'');
            $result[$i]['periodoNombre'] .= '&nbsp;&nbsp;<a class="text-secondary" href="#/pages/periodos/editar/'.$item['col_id'].'" target="_blank"><i class="fas fa-pencil-alt"></i></a>';
            $result[$i]['grupo'] = $item['col_grado'].'-'.$item['col_grupo'];
            $result[$i]['modalidad'] = fixEncode($carreraData['modalidad']);
            $result[$i]['estatusAlumno'] = '<a class="text-secondary" target="_blank" href="#/pages/alumnos/calificaciones-actuales/'.$item['col_id'].'/'.intval($_REQUEST['alumnoid']).'"><i class="fas fa-check-square"></i> Ver Calificaciones</a>';
            if(intval($item['col_grado']) > 0 && $item['baja'] == 0) $grados[] = $item['col_grado'];

            if($item['col_carreraid']) $_response['carrera'] = $item['col_carreraid'];
            // if($item['col_modalidad'] != '') $_response['modalidad'] = $item['col_modalidad'];

            if($item['createdBy'] == 0) {
                $result[$i]['inscrito'] = '-';
            } else if($item['createdBy'] == $alumnoid) {
                $result[$i]['inscrito'] = 'Por el Alumno en '.fechaTexto($item['createdAt']);
            } else {
                $userData = getUserData($item['createdBy'], $this->db);
                $result[$i]['inscrito'] = 'Por '.fixEncode($userData->col_firstname, true).' en '.fechaTexto($item['createdAt']);
            }

            if($item['updatedBy'] == 0) {
                $result[$i]['modificado'] = '-';
            } else if($item['updatedBy'] == $alumnoid) {
                $result[$i]['modificado'] = 'Por el Alumno en '.fechaTexto($item['updatedAt']);
            } else {
                $userData = getUserData($item['updatedBy'], $this->db);
                $result[$i]['modificado'] = 'Por '.fixEncode($userData->col_firstname, true).' en '.fechaTexto($item['updatedAt']);
            }
            if($item['baja'] == 1){
                $result[$i]['bajaAlumno'] = '<span class="text-danger"><i class="fas fa-user-slash"></i></span>';
            }else{
                $result[$i]['bajaAlumno'] = '<span class="text-secondary"><i class="fas fa-user"></i></span>';
            }

            $i++;

        }

        $queryAlumno = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval($_REQUEST['alumnoid']).'"';
        $alumno = $this->db->prepare($queryAlumno);
        $alumno->execute();
        $alumnoData = $alumno->fetch(PDO::FETCH_OBJ);

        $_response['nombreAlumno'] = fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos);
        $_response['grados'] = implode(',', array_unique($grados));
        $_response['list'] = $result;

        return $this->response->withJson($_response);
    });

    $this->get('/listaAlumnoBajas', function (Request $request, Response $response, array $args) {

        $alumnoid = intval($_REQUEST['alumnoid']);
        $estatus = Array('activo' => 'Activo', 'baja' => 'Baja', 'bajatemporal' => 'Baja Temporal');
        $estatusNum = Array(0 => 'Baja', 1 => 'Baja Temporal');

        $queryAlumno = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval($alumnoid).'"';
        $alumnoSTH = $this->db->prepare($queryAlumno);
        $alumnoSTH->execute();
        $alumnoData = $alumnoSTH->fetch(PDO::FETCH_OBJ);
        $periodoData = getPeriodo($alumnoData->col_periodoid, $this->db, false);

        $_response['nombreAlumno'] = fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos);
        $_response['estatusActual'] = $estatus[$alumnoData->col_estatus];
        $_response['periodoActual'] = fixEncode($periodoData->col_nombre).' ('.$periodoData->col_grado.'-'.$periodoData->col_grupo.')';


        $query = 'SELECT b.*, p.col_nombre As nombrePeriodo, p.col_grado As gradoPeriodo, p.col_grupo AS grupoPeriodo, p.col_id AS periodoID, CONCAT(u.col_firstname, " ", u.col_lastname) AS nombreAutor FROM tbl_alumnos_bajas b LEFT JOIN tbl_periodos p ON p.col_id=b.col_periodoid LEFT JOIN tbl_users u ON u.col_id=b.col_created_by WHERE b.col_alumnoid="'.intval($_REQUEST['alumnoid']).'" ORDER BY p.col_grado DESC';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $result = array();
        $i = 0;

        foreach($todos as $item){
            $result[$i]['id'] = $item['col_id'];
            if(intval($item['periodoID']) > 0) {
                $result[$i]['periodoID'] = $item['periodoID'];
                $result[$i]['autor'] = fixEncode($item['nombreAutor']);
                $result[$i]['periodoNombre'] = fixEncode($item['nombrePeriodo']);
                $result[$i]['periodoNombre'] .= '&nbsp;&nbsp;<a class="text-secondary" href="#/pages/periodos/editar/'.$item['col_id'].'" target="_blank"><i class="fas fa-pencil-alt"></i></a>';
                $result[$i]['periodoGrupo'] = $item['gradoPeriodo'].'-'.$item['grupoPeriodo'];
                $result[$i]['tipo'] = $estatusNum[$item['col_tipo']];
            }else{
                $result[$i]['periodoID'] = 0;
                $result[$i]['autor'] = '';
                $result[$i]['periodoNombre'] = '-';
                $result[$i]['periodoGrupo'] = '-';
                $result[$i]['tipo'] = $estatusNum[0];
            }

            $result[$i]['fecha'] = fechaTexto($item['col_fecha_baja']).' - '.substr($item['col_fecha_baja'], 10, 18);



            $i++;

        }


        $_response['list'] = $result;

        return $this->response->withJson($_response);
    });

    $this->get('/getHorario', function (Request $request, Response $response, array $args) {
        global $download_url;

        $userID = getCurrentUserID();
        $periodoActivo = getCurrentAlumnoPeriodoID($this->db);
        $carrera = getCarreraByAlumno($userID, $this->db);
        // print_r($carrera);

        $query = 'SELECT p.col_id AS periodoid, p.col_aprobado AS horario_aprobado, p.col_carreraid AS carrera, p.col_plan_estudios AS planEstudios, p.col_grado AS semestre '.
        'FROM tbl_alumnos a '.
        'LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid '.
        'WHERE a.col_id="'.$userID.'"';

        $sth = $this->db->prepare($query);
        $sth->execute();
        $alumno = $sth->fetch(PDO::FETCH_OBJ);

        $queryTalleres = 'SELECT * FROM tbl_talleres WHERE col_alumnoid="'.intval($userID).'" AND col_periodoid="'.intval($periodoActivo).'"';
        $talleres = $this->db->prepare($queryTalleres);
        $talleres->execute();
        $talleresData = $talleres->fetch(PDO::FETCH_OBJ);

        $queryAcademias = 'SELECT * FROM tbl_academias WHERE col_alumnoid="'.intval($userID).'" AND col_periodoid="'.intval($periodoActivo).'"';
        $academias = $this->db->prepare($queryAcademias);
        $academias->execute();
        $academiasData = $academias->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($periodoActivo).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $periodoData = $sth->fetch(PDO::FETCH_OBJ);
        $isPosgrado = 0;
        if($periodoData->col_modalidad == 3 || $periodoData->col_modalidad == 4){
            $isPosgrado = 1;
        }


        $query = "SELECT * FROM tbl_materias ".
        "WHERE (col_clave NOT LIKE 'AC%' AND col_clave NOT LIKE 'TL%') AND col_plan_estudios='".intval($alumno->planEstudios)."' ".
        "AND col_clave IN (SELECT col_materia_clave FROM tbl_maestros_taxonomia WHERE col_periodoid='".intval($periodoActivo)."') ".
        "AND col_carrera='".$alumno->carrera."' AND col_semestre='".$alumno->semestre."' GROUP BY col_clave ORDER BY col_nombre ASC";
        $sth_materias = $this->db->prepare($query);
        $sth_materias->execute();
        $materias1 = $sth_materias->fetchAll();

        $query = "SELECT * FROM tbl_materias WHERE col_id='".$talleresData->col_materiaid."' OR col_id='".$academiasData->col_materiaid."' GROUP BY col_clave ORDER BY col_nombre ASC";
        $sth_materias2 = $this->db->prepare($query);
        $sth_materias2->execute();
        $materias2 = $sth_materias2->fetchAll();

        $materias = (object) array_merge((array) $materias1, (array) $materias2);

        ob_start();
        if($alumno->horario_aprobado == 1){
        ?>
            <table class="table horarioClases">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th class="dia <?php echo (date('N') == 1?'active':''); ?>">Lu</th>
                        <th class="dia <?php echo (date('N') == 2?'active':''); ?>">Ma</th>
                        <th class="dia <?php echo (date('N') == 3?'active':''); ?>">Mi</th>
                        <th class="dia <?php echo (date('N') == 4?'active':''); ?>">Ju</th>
                        <th class="dia <?php echo (date('N') == 5?'active':''); ?>">Vi</th>
                        <th class="dia <?php echo (date('N') == 6?'active':''); ?>">Sa</th>
                        <th class="dia <?php echo (date('N') == 7?'active':''); ?>">Do</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if($isPosgrado == 0){
                        foreach($materias as $item){
                            $queryHorario = 'SELECT * FROM tbl_horarios WHERE col_periodoid="'.intval($alumno->periodoid).'" AND col_materiaid="'.intval($item['col_id']).'"';
                            $sthHorario = $this->db->prepare($queryHorario);
                            $sthHorario->execute();
                            $dataHorario = $sthHorario->fetch(PDO::FETCH_OBJ);
                            ?>
                        <tr>
                            <td><?php echo $item['col_nombre']; ?></td>
                            <td align="center" class="dia <?php echo (date('N') == 1?'active':''); ?>"><?php echo str_replace('-', ' a ', $dataHorario->col_lunes); ?></td>
                            <td align="center" class="dia <?php echo (date('N') == 2?'active':''); ?>"><?php echo str_replace('-', ' a ', $dataHorario->col_martes); ?></td>
                            <td align="center" class="dia <?php echo (date('N') == 3?'active':''); ?>"><?php echo str_replace('-', ' a ', $dataHorario->col_miercoles); ?></td>
                            <td align="center" class="dia <?php echo (date('N') == 4?'active':''); ?>"><?php echo str_replace('-', ' a ', $dataHorario->col_jueves); ?></td>
                            <td align="center" class="dia <?php echo (date('N') == 5?'active':''); ?>"><?php echo str_replace('-', ' a ', $dataHorario->col_viernes); ?></td>
                            <td align="center" class="dia <?php echo (date('N') == 6?'active':''); ?>"><?php echo str_replace('-', ' a ', $dataHorario->col_sabado); ?></td>
                            <td align="center" class="dia <?php echo (date('N') == 7?'active':''); ?>"><?php echo str_replace('-', ' a ', $dataHorario->col_doming); ?></td>
                        </tr>
                            <?php
                        }
                    }else{
                        foreach($materias as $item){
                            $queryHorario = 'SELECT * FROM tbl_horarios_posgrados WHERE col_periodoid="'.intval($alumno->periodoid).'" AND col_materiaid="'.intval($item['col_id']).'"';
                            $sthHorario = $this->db->prepare($queryHorario);
                            $sthHorario->execute();
                            $dataHorarios = $sthHorario->fetchAll();
                            foreach($dataHorarios as $itemHorario) {
                                if($itemHorario['col_dia'] == 0) $horario_lunes[] = '<span class="rowHorarioAlumno">'.$itemHorario['col_hora_inicio'].' a '.$itemHorario['col_hora_fin'].'</span>';
                                if($itemHorario['col_dia'] == 1) $horario_martes[] = '<span class="rowHorarioAlumno">'.$itemHorario['col_hora_inicio'].' a '.$itemHorario['col_hora_fin'].'</span>';
                                if($itemHorario['col_dia'] == 2) $horario_miercoles[] = '<span class="rowHorarioAlumno">'.$itemHorario['col_hora_inicio'].' a '.$itemHorario['col_hora_fin'].'</span>';
                                if($itemHorario['col_dia'] == 3) $horario_jueves[] = '<span class="rowHorarioAlumno">'.$itemHorario['col_hora_inicio'].' a '.$itemHorario['col_hora_fin'].'</span>';
                                if($itemHorario['col_dia'] == 4) $horario_viernes[] = '<span class="rowHorarioAlumno">'.$itemHorario['col_hora_inicio'].' a '.$itemHorario['col_hora_fin'].'</span>';
                                if($itemHorario['col_dia'] == 5) $horario_sabado[] = '<span class="rowHorarioAlumno">'.$itemHorario['col_hora_inicio'].' a '.$itemHorario['col_hora_fin'].'</span>';
                                if($itemHorario['col_dia'] == 6) $horario_doming[] = '<span class="rowHorarioAlumno">'.$itemHorario['col_hora_inicio'].' a '.$itemHorario['col_hora_fin'].'</span>';

                            }
                            ?>
                        <tr>
                            <td><?php echo $item['col_nombre']; ?></td>
                            <td align="center" class="dia <?php echo (date('N') == 1?'active':''); ?>"><?php echo implode('', $horario_lunes); ?></td>
                            <td align="center" class="dia <?php echo (date('N') == 2?'active':''); ?>"><?php echo implode('', $horario_martes); ?></td>
                            <td align="center" class="dia <?php echo (date('N') == 3?'active':''); ?>"><?php echo implode('', $horario_miercoles); ?></td>
                            <td align="center" class="dia <?php echo (date('N') == 4?'active':''); ?>"><?php echo implode('', $horario_jueves); ?></td>
                            <td align="center" class="dia <?php echo (date('N') == 5?'active':''); ?>"><?php echo implode('', $horario_viernes); ?></td>
                            <td align="center" class="dia <?php echo (date('N') == 6?'active':''); ?>"><?php echo implode('', $horario_sabado); ?></td>
                            <td align="center" class="dia <?php echo (date('N') == 7?'active':''); ?>"><?php echo implode('', $horario_doming); ?></td>
                        </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        <?php
        }else{
            ?>
            <table class="table horarioClases">
                <tbody>
                    <tr>
                        <td align="center">Pronto podrás ver tu horario de clases.</td>
                    </tr>
                </tbody>
            </table>
            <?php
        }
        $html = ob_get_contents();
        ob_clean();


        $result['html'] = $html;


        if($carrera['modalidad'] == 'Semestral') {

            $sth = $this->db->prepare('SELECT * FROM tbl_documentos WHERE col_id="49"');
            $sth->execute();
            $data = $sth->fetch(PDO::FETCH_OBJ);
            $result['calendarioEscolar'] = '<a href="'.$download_url.$data->col_filepath.'" target="_blank">'.fixEncode($data->col_nombre).'</a>';


            $sth = $this->db->prepare('SELECT * FROM tbl_documentos WHERE col_id="45"');
            $sth->execute();
            $data = $sth->fetch(PDO::FETCH_OBJ);
            if($alumno->semestre > 6) $result['modeloEducativo'] = '<a href="'.$download_url.$data->col_filepath.'" target="_blank">'.fixEncode($data->col_nombre).'</a>';


            $sth = $this->db->prepare('SELECT * FROM tbl_documentos WHERE col_id="44"');
            $sth->execute();
            $data = $sth->fetch(PDO::FETCH_OBJ);
            if($alumno->semestre < 7) $result['modeloEducativo'] = '<a href="'.$download_url.$data->col_filepath.'" target="_blank">'.fixEncode($data->col_nombre).'</a>';

        }

        if($carrera['modalidad'] == 'Cuatrimestral') {
            $sth = $this->db->prepare('SELECT * FROM tbl_documentos WHERE col_id="48"');
            $sth->execute();
            $data = $sth->fetch(PDO::FETCH_OBJ);
            if($alumno->semestre == 8) $result['horarioGlobal8'] = '<a href="'.$download_url.$data->col_filepath.'" target="_blank">'.fixEncode($data->col_nombre).'</a>';

            $sth = $this->db->prepare('SELECT * FROM tbl_documentos WHERE col_id="47"');
            $sth->execute();
            $data = $sth->fetch(PDO::FETCH_OBJ);
            if($alumno->semestre == 6) $result['horarioGlobal6'] = '<a href="'.$download_url.$data->col_filepath.'" target="_blank">'.fixEncode($data->col_nombre).'</a>';
        }

        return $this->response->withJson($result);

    });

    $this->get('/calificacionesByAlumno', function (Request $request, Response $response, array $args) {
        global $uploaddir, $download_url;
        $fecha = '';
        $userType = getCurrentUserType();
        if(intval($_REQUEST['id']) == 0){
            $alumnoid = getCurrentUserID();
        }else{
            $alumnoid = intval($_REQUEST['id']);
        }

        if(isset($_REQUEST['periodoid']) && intval($_REQUEST['periodoid']) > 0) {
            $periodoid = intval($_REQUEST['periodoid']);
            $periodoNombre = getPeriodo($periodoid, $this->db, true);
        } else {
            $periodoid = getLastPeriodoAlumno(intval($alumnoid), $this->db);
            $periodoNombre = getPeriodo($periodoid, $this->db, true);
        }

        if(!file_exists($uploaddir.'boletas')) @mkdir($uploaddir.'boletas', 0777);
        if(file_exists($uploaddir.'boletas/'.md5($periodoid.$alumnoid).'.pdf')){
            unlink($uploaddir.'boletas/'.md5($periodoid.$alumnoid).'.pdf');
        }
        if(!file_exists($uploaddir.'boletas/'.md5($periodoid.$alumnoid).'.pdf')){
            generarBoleta($alumnoid, $periodoid, $this->db, $uploaddir.'boletas/'.md5($periodoid.$alumnoid), 'F', 'DOCUMENTO NO OFICIAL');
        }
        $downloadURL = $download_url.'boletas/'.md5($periodoid.$alumnoid).'.pdf';

        $query = "SELECT * FROM tbl_alumnos WHERE col_id='".intval($alumnoid)."'";

        ob_start();
        ?>
        <table class="table calificaciones">
        <?php
        $sth = $this->db->prepare($query);
        $sth->execute();
        $alumno = $sth->fetch(PDO::FETCH_OBJ);

        echo '<tr><td>';
        $periodoData = getPeriodo($periodoid, $this->db, false);

        $claveCurriculares = getClavesCurriculares($this->db, $periodoData->col_carreraid);
        // Regulares
        $query = "SELECT c.* FROM tbl_calificaciones c WHERE c.col_periodoid='".$periodoid."' AND (c.col_materia_clave REGEXP '^[a-zA-Z.]+$') IN ('".implode('\',\'', $claveCurriculares)."') AND col_alumnoid='".$alumnoid."' AND col_estatus=1 GROUP BY col_materia_clave";

        if($userType == 'administrativo'){
            $query = "SELECT c.* FROM tbl_calificaciones c WHERE c.col_periodoid='".$periodoid."' AND (c.col_materia_clave REGEXP '^[a-zA-Z.]+$') IN ('".implode('\',\'', $claveCurriculares)."') AND col_alumnoid='".$alumnoid."' GROUP BY col_materia_clave ORDER BY col_materia_clave ASC";
        }
        $cal = $this->db->prepare($query);
        $cal->execute();
        if($cal->rowCount() > 0) {
                $calificacionesLD = $cal->fetchAll();
        }

        // Talleres Academias Club Transversal
        $query = "SELECT c.* FROM tbl_calificaciones c WHERE (c.col_periodoid='".$periodoid."' OR c.col_groupid='".$periodoData->col_groupid."') AND (c.col_materia_clave REGEXP '^[a-zA-Z.]+$') NOT IN ('".implode('\',\'', $claveCurriculares)."') AND col_alumnoid='".$alumnoid."' AND col_estatus=1 GROUP BY col_materia_clave";
        if($userType == 'administrativo'){
            $query = "SELECT c.* FROM tbl_calificaciones c WHERE (c.col_periodoid='".$periodoid."' OR c.col_groupid='".$periodoData->col_groupid."') AND (c.col_materia_clave REGEXP '^[a-zA-Z.]+$') NOT IN ('".implode('\',\'', $claveCurriculares)."') AND col_alumnoid='".$alumnoid."' GROUP BY col_materia_clave ORDER BY col_materia_clave ASC";
        }
        $calNotLD = $this->db->prepare($query);
        $calNotLD->execute();
        if($calNotLD->rowCount() > 0) {
                $calificacionesNotLD = $calNotLD->fetchAll();
        }

        if($calNotLD->rowCount() > 0) {
            $calificaciones = array_merge($calificacionesLD, $calificacionesNotLD);
        }else{
            $calificaciones = $calificacionesLD;
        }

        // if($cal->rowCount() > 0 && $calNotLD->rowCount() > 0) {
        if(count($calificaciones)) {

                $carrera = getCarrera($alumno->col_carrera, $this->db);

                if($carrera['tipo'] == 0 && $carrera['modalidad'] == 'Cuatrimestral'){
                    ?>
                    <table class="table alumno" style="width:100%;">
                        <thead>
                            <tr>
                                <th colspan="3">Alumno: <?php echo fixEncode($alumno->col_nombres.' '.$alumno->col_apellidos); ?><br/>Periodo: <?php echo $periodoNombre;?></th>
                                <th colspan="4">Carrera: <?php echo fixEncode($carrera[nombre]); ?></th>
                            </tr>
                            <tr>
                                <th>Materia</th>
                                <th>Maestro</th>
                                <th>P1</th>
                                <th>EF</th>
                                <th>CF</th>
                                <th>EXT</th>
                                <th>TS</th>
                            </tr>
                        </thead>
                    <?php
                }else if($carrera['tipo'] == 0 && $carrera['modalidad'] == 'Semestral'){
                    ?>
                    <table class="table alumno" style="width:100%;">
                        <thead>
                            <tr>
                                <th colspan="2">Alumno: <?php echo fixEncode($alumno->col_nombres.' '.$alumno->col_apellidos); ?><br/>Periodo: <?php echo $periodoNombre;?></th>
                                <th colspan="6">Carrera: <?php echo fixEncode($carrera[nombre]); ?></th>
                            </tr>
                            <tr>
                                <th>Materia</th>
                                <th>Maestro</th>
                                <th>P1</th>
                                <th>P2</th>
                                <th>EF</th>
                                <th>CF</th>
                                <th>EXT</th>
                                <th>TS</th>
                            </tr>
                        </thead>
                    <?php
                }else{
                    ?>
                    <table class="table alumno"  style="width:100%;">
                        <thead>
                            <tr>
                                <th colspan="2">Alumno: <?php echo fixEncode($alumno->col_nombres.' '.$alumno->col_apellidos); ?><br/>Periodo: <?php echo $periodoNombre;?></th>
                                <th colspan="1">Carrera: <?php echo fixEncode($carrera[nombre]); ?></th>
                            </tr>
                            <tr>
                                <th>Materia</th>
                                <th>Maestro</th>
                                <th>CF</th>
                            </tr>
                        </thead>
                    <?php
                }
                echo '<tbody>';
                $fecha = '';
                foreach($calificaciones as $row){

                    if(in_array(strtoupper(substr($row['col_materia_clave'], 0, 2)), array('TL', 'CL', 'TR'))) {
                        if($row['col_p1'] != '') $row['col_p1'] = (intval($row['col_p1']) >= 7?'A':'NA');
                        if($row['col_p2'] != '') $row['col_p2'] = (intval($row['col_p2']) >= 7?'A':'NA');
                        if($row['col_ef'] != '') $row['col_ef'] = (intval($row['col_ef']) >= 7?'A':'NA');
                        if($row['col_cf'] != '') $row['col_cf'] = (intval($row['col_cf']) >= 7?'-':'-');
                        if($row['col_ext'] != '') $row['col_ext'] = (intval($row['col_ext']) >= 7?'A':'NA');
                        if($row['col_ts'] != '') $row['col_ts'] = (intval($row['col_ts']) >= 7?'A':'NA');
                     }

                     if(in_array(strtoupper(substr($row['col_materia_clave'], 0, 2)), array('AC'))) {
                        if($row['col_p1'] != '') $row['col_p1'] = (intval($row['col_p1']) > 1?'A':'NA');
                        if($row['col_p2'] != '') $row['col_p2'] = (intval($row['col_p2']) > 1?'A':'NA');
                        if($row['col_ef'] != '') $row['col_ef'] = (intval($row['col_ef']) > 1?'A':'NA');
                        if($row['col_cf'] != '') $row['col_cf'] = (intval($row['col_cf']) > 1?'-':'-');
                        if($row['col_ext'] != '') $row['col_ext'] = (intval($row['col_ext']) > 1?'A':'NA');
                        if($row['col_ts'] != '') $row['col_ts'] = (intval($row['col_ts']) > 1?'A':'NA');
                     }

                    $materia = getMateria('col_clave', $row['col_materia_clave'], $this->db, $row['col_periodoid']);
                    if($materia->nombre_maestro == ''){
                        $materia->nombre_maestro = getMaestroByClaveMateria($row['col_materia_clave'], $alumnoid, $this->db);
                    }


                    $queryMaestro = 'SELECT * FROM tbl_users WHERE col_id="'.$row['col_created_by'].'" AND col_maestro=1';
                    $sthMaestro = $this->db->prepare($queryMaestro);
                    $sthMaestro->execute();
                    $dataMaestro = $sthMaestro->fetch(PDO::FETCH_OBJ);
                    if($dataMaestro) {
                        $materia->nombre_maestro = fixEncode($dataMaestro->col_firstname. ' '.$dataMaestro->col_lastname, true);
                    }else{
                        $materia->nombre_maestro = '-';
                    }

                    if($carrera['tipo'] == 0 && $carrera['modalidad'] == 'Cuatrimestral'){
                        ?>
                        <tr>
                            <td><?php echo fixEncode($materia->col_nombre); ?></td>
                            <td><?php echo fixEncode($materia->nombre_maestro); ?></td>
                            <td><?php echo $row['col_p1']; ?></td>
                            <td><?php echo $row['col_ef']; ?></td>
                            <td><?php echo $row['col_cf']; ?></td>
                            <td><?php echo $row['col_ext']; ?></td>
                            <td><?php echo $row['col_ts']; ?></td>
                        </tr>
                        <?php
                    }else if($carrera['tipo'] == 0 && $carrera['modalidad'] == 'Semestral'){
                        ?>
                        <tr>
                            <td><?php echo fixEncode($materia->col_nombre); ?></td>
                            <td><?php echo fixEncode($materia->nombre_maestro); ?></td>
                            <td><?php echo $row['col_p1']; ?></td>
                            <td><?php echo $row['col_p2']; ?></td>
                            <td><?php echo $row['col_ef']; ?></td>
                            <td><?php echo $row['col_cf']; ?></td>
                            <td><?php echo $row['col_ext']; ?></td>
                            <td><?php echo $row['col_ts']; ?></td>
                        </tr>
                        <?php
                    }else{
                        ?>
                        <tr>
                            <td><?php echo fixEncode($materia->col_nombre); ?></td>
                            <td><?php echo fixEncode($materia->nombre_maestro); ?></td>
                            <td><?php echo $row['col_cf']; ?></td>
                        </tr>
                        <?php
                    }





                    $fecha = $row['col_updated_at'];
                }
                echo '</tbody></table>';
                echo '</td></tr>';
        }else {
            ?>
            <tr>
                <td align="center" class="text-danger">No hay información disponible sobre tus calificaciones en este momento, intenta mas tarde!</td>
            </tr>
            <?php
        }
        ?>
        </table>
        <hr/>
        <a class="btn btn-secondary" href="<?php echo $downloadURL; ?>" target="_blank"><i class="fas fa-file-pdf text-danger"></i> Descargar Boleta</a>
        <?php

        $html = ob_get_contents();
        ob_clean();
        $_response['fecha'] = fechaTexto(date('Y-m-d'));
        //if($fecha != '') $_response['fecha'] = fechaTexto($fecha);
        $_response['data'] = $html;
        $_response['type'] = $tipoCarrera;
        return $this->response->withJson($_response);
    });

    $this->get('/calificacionesByAlumnoJSON', function (Request $request, Response $response, array $args) {
        $fecha = '';
        $userType = getCurrentUserType();
        if(intval($_REQUEST['id']) == 0){
            $alumnoid = getCurrentUserID();
        }else{
            $alumnoid = intval($_REQUEST['id']);
        }

        if(isset($_REQUEST['periodoid']) && intval($_REQUEST['periodoid']) > 0) {
            $periodoid = intval($_REQUEST['periodoid']);
            $periodoNombre = getPeriodo($periodoid, $this->db, true);
        } else {
            $periodoid = getLastPeriodoAlumno(intval($alumnoid), $this->db);
            $periodoNombre = getPeriodo($periodoid, $this->db, true);
        }


        $query = "SELECT * FROM tbl_alumnos WHERE col_id='".intval($alumnoid)."'";


        $sth = $this->db->prepare($query);
        $sth->execute();
        $alumno = $sth->fetch(PDO::FETCH_OBJ);

        $periodoData = getPeriodo($periodoid, $this->db, false);
        // Regulares
        $query = "SELECT c.* FROM tbl_calificaciones c WHERE c.col_periodoid='".$periodoid."' AND c.col_materia_clave LIKE 'LD%' AND col_alumnoid='".$alumnoid."' AND col_estatus=1 GROUP BY col_materia_clave";
        if($userType == 'administrativo'){
            $query = "SELECT c.* FROM tbl_calificaciones c WHERE c.col_periodoid='".$periodoid."' AND c.col_materia_clave LIKE 'LD%' AND col_alumnoid='".$alumnoid."' GROUP BY col_materia_clave ORDER BY col_materia_clave ASC";
        }
        $cal = $this->db->prepare($query);
        $cal->execute();
        if($cal->rowCount() > 0) {
                $calificacionesLD = $cal->fetchAll();
        }

        // Talleres Academias Club Transversal
        $query = "SELECT c.* FROM tbl_calificaciones c WHERE (c.col_periodoid='".$periodoid."' OR c.col_groupid='".$periodoData->col_groupid."') AND c.col_materia_clave NOT LIKE 'LD%' AND col_alumnoid='".$alumnoid."' AND col_estatus=1 GROUP BY col_materia_clave";
        if($userType == 'administrativo'){
            $query = "SELECT c.* FROM tbl_calificaciones c WHERE (c.col_periodoid='".$periodoid."' OR c.col_groupid='".$periodoData->col_groupid."') AND c.col_materia_clave NOT LIKE 'LD%' AND col_alumnoid='".$alumnoid."' GROUP BY col_materia_clave ORDER BY col_materia_clave ASC";
        }
        $calNotLD = $this->db->prepare($query);
        $calNotLD->execute();
        if($calNotLD->rowCount() > 0) {
                $calificacionesNotLD = $calNotLD->fetchAll();
        }

        if($calNotLD->rowCount() > 0) {
            $calificaciones = array_merge($calificacionesLD, $calificacionesNotLD);
        }else{
            $calificaciones = $calificacionesLD;
        }

        // if($cal->rowCount() > 0 && $calNotLD->rowCount() > 0) {
        if(count($calificaciones)) {

                $carrera = getCarrera($alumno->col_carrera, $this->db);
                $_response['carreraTipo'] = $carrera['tipo'];
                $_response['carreraModalidad'] = $carrera['modalidad'];
                $_response['carreraNombre'] = fixEncode($carrera['nombre']);
                $_response['nombreAlumno'] = fixEncode($alumno->col_nombres.' '.$alumno->col_apellidos);
                $_response['periodoNombre'] = $periodoNombre;

                $fecha = '';
                foreach($calificaciones as $row){


                    if(in_array(strtoupper(substr($row['col_materia_clave'], 0, 2)), array('TL', 'CL', 'TR'))) {
                        if($row['col_p1'] != '') $row['col_p1'] = (intval($row['col_p1']) >= 7?'A':'NA');
                        if($row['col_p2'] != '') $row['col_p2'] = (intval($row['col_p2']) >= 7?'A':'NA');
                        if($row['col_ef'] != '') $row['col_ef'] = (intval($row['col_ef']) >= 7?'A':'NA');
                        if($row['col_cf'] != '') $row['col_cf'] = (intval($row['col_cf']) >= 7?'-':'-');
                        if($row['col_ext'] != '') $row['col_ext'] = (intval($row['col_ext']) >= 7?'A':'NA');
                        if($row['col_ts'] != '') $row['col_ts'] = (intval($row['col_ts']) >= 7?'A':'NA');
                     }

                     if(in_array(strtoupper(substr($row['col_materia_clave'], 0, 2)), array('AC'))) {
                        if($row['col_p1'] != '') $row['col_p1'] = (intval($row['col_p1']) > 1?'A':'NA');
                        if($row['col_p2'] != '') $row['col_p2'] = (intval($row['col_p2']) > 1?'A':'NA');
                        if($row['col_ef'] != '') $row['col_ef'] = (intval($row['col_ef']) > 1?'A':'NA');
                        if($row['col_cf'] != '') $row['col_cf'] = (intval($row['col_cf']) > 1?'-':'-');
                        if($row['col_ext'] != '') $row['col_ext'] = (intval($row['col_ext']) > 1?'A':'NA');
                        if($row['col_ts'] != '') $row['col_ts'] = (intval($row['col_ts']) > 1?'A':'NA');
                     }

                     $materia = getMateria('col_clave', $row['col_materia_clave'], $this->db, $row['col_periodoid']);
                     if($materia->nombre_maestro == ''){
                        $materia->nombre_maestro = getMaestroByClaveMateria($row['col_materia_clave'], $alumnoid, $this->db);
                    }
                     $row['nombreMateria'] = fixEncode($materia->col_nombre);
                     $row['nombreMaestro'] = fixEncode($materia->nombre_maestro, true);
                     $row['col_observaciones'] = fixEncode(stripslashes($row[col_observaciones]));


                     $queryMaestro = 'SELECT * FROM tbl_users WHERE col_id="'.$row['col_created_by'].'" AND col_maestro=1';
                     $sthMaestro = $this->db->prepare($queryMaestro);
                     $sthMaestro->execute();
                     $dataMaestro = $sthMaestro->fetch(PDO::FETCH_OBJ);
                     if($dataMaestro) {
                         $row['nombreMaestro'] = fixEncode($dataMaestro->col_firstname. ' '.$dataMaestro->col_lastname, true);
                     }else{
                         $row['nombreMaestro'] = '-';
                     }
                     if(trim($row['nombreMaestro']) == '-') {
                        $queryMaestroTax = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_materia_clave="'.$row['col_materia_clave'].'" AND col_periodoid="'.$row['col_periodoid'].'"';
                        $sthMaestroTax = $this->db->prepare($queryMaestroTax);
                        $sthMaestroTax->execute();
                        $dataMaestroTax = $sthMaestroTax->fetch(PDO::FETCH_OBJ);

                        $queryMaestro = 'SELECT * FROM tbl_users WHERE col_id="'.$dataMaestroTax->col_maestroid.'" AND col_maestro=1';
                        $sthMaestro = $this->db->prepare($queryMaestro);
                        $sthMaestro->execute();
                        $dataMaestro = $sthMaestro->fetch(PDO::FETCH_OBJ);
                        if($dataMaestro) {
                            $row['nombreMaestro'] = fixEncode($dataMaestro->col_firstname. ' '.$dataMaestro->col_lastname, true);
                        }else{
                            $row['nombreMaestro'] = '-';
                        }
                    }


                     $items[$row['nombreMateria']] = $row;
                     $fecha = $row['col_updated_at'];
                }

        }
        asort($items);
        $_response['calificaciones'] = $items;

        $_response['fecha'] = fechaTexto(date('Y-m-d'));


        return $this->response->withJson($_response);
    });


    $this->get('/calificacionesByAlumnoJSONEdit', function (Request $request, Response $response, array $args) {
        $fecha = '';
        $userType = getCurrentUserType();
        if($userType != 'administrativo' && $userType != 'maestro'){
            $_response['error'] = 'Acceso Invalido';
            return $this->response->withJson($_response);
        }


        if(intval($_REQUEST['id']) == 0){
            $alumnoid = getCurrentUserID();
        }else{
            $alumnoid = intval($_REQUEST['id']);
        }

        if(isset($_REQUEST['periodoid']) && intval($_REQUEST['periodoid']) > 0) {
            $periodoid = intval($_REQUEST['periodoid']);
            $periodoData = getPeriodo($periodoid, $this->db, false);
        } else {
            $periodoid = getLastPeriodoAlumno(intval($alumnoid), $this->db);
            $periodoData = getPeriodo($periodoid, $this->db, false);
        }

        $periodoNombre = fixEncode($periodoData->col_nombre);


        $query = "SELECT * FROM tbl_alumnos WHERE col_id='".intval($alumnoid)."'";


        $sth = $this->db->prepare($query);
        $sth->execute();
        $alumno = $sth->fetch(PDO::FETCH_OBJ);

        // Regulares
        $query = "SELECT c.* FROM tbl_calificaciones c WHERE c.col_periodoid='".$periodoid."' AND c.col_materia_clave LIKE 'LD%' AND col_alumnoid='".$alumnoid."' AND col_estatus=1 GROUP BY col_materia_clave";
        if($userType == 'administrativo'){
            $query = "SELECT c.* FROM tbl_calificaciones c WHERE c.col_periodoid='".$periodoid."' AND c.col_materia_clave LIKE 'LD%' AND col_alumnoid='".$alumnoid."' GROUP BY col_materia_clave ORDER BY col_materia_clave ASC";
        }
        $cal = $this->db->prepare($query);
        $cal->execute();
        if($cal->rowCount() > 0) {
                $calificacionesLD = $cal->fetchAll();
        }

        // Talleres Academias Club Transversal
        $query = "SELECT c.* FROM tbl_calificaciones c WHERE (c.col_periodoid='".$periodoid."' OR c.col_groupid='".$periodoData->col_groupid."') AND c.col_materia_clave NOT LIKE 'LD%' AND col_alumnoid='".$alumnoid."' AND col_estatus=1 GROUP BY col_materia_clave";
        if($userType == 'administrativo'){
            $query = "SELECT c.* FROM tbl_calificaciones c WHERE (c.col_periodoid='".$periodoid."' OR c.col_groupid='".$periodoData->col_groupid."') AND c.col_materia_clave NOT LIKE 'LD%' AND col_alumnoid='".$alumnoid."' GROUP BY col_materia_clave ORDER BY col_materia_clave ASC";
        }

        $calNotLD = $this->db->prepare($query);
        $calNotLD->execute();
        if($calNotLD->rowCount() > 0) {
                $calificacionesNotLD = $calNotLD->fetchAll();
        }

        if($calNotLD->rowCount() > 0) {
            if($cal->rowCount() > 0) {
                $calificaciones = array_merge($calificacionesLD, $calificacionesNotLD);
            }else{
                $calificaciones = $calificacionesNotLD;
            }
        }else{
            $calificaciones = $calificacionesLD;
        }

        // if($cal->rowCount() > 0 && $calNotLD->rowCount() > 0) {
        if(count($calificaciones)) {

                $carrera = getCarrera($alumno->col_carrera, $this->db);
                $_response['carreraTipo'] = $carrera['tipo'];
                $_response['carreraModalidad'] = $carrera['modalidad'];
                $_response['carreraNombre'] = fixEncode($carrera['nombre']);
                $_response['nombreAlumno'] = fixEncode($alumno->col_nombres.' '.$alumno->col_apellidos);
                $_response['periodoNombre'] = $periodoNombre;

                $fecha = '';
                foreach($calificaciones as $row){


                    if(in_array(strtoupper(substr($row['col_materia_clave'], 0, 2)), array('TL', 'CL', 'TR'))) {
                        if($row['col_p1'] != '') $row['col_p1_a'] = (intval($row['col_p1']) >= 7?'A':'NA');
                        if($row['col_p2'] != '') $row['col_p2_a'] = (intval($row['col_p2']) >= 7?'A':'NA');
                        if($row['col_ef'] != '') $row['col_ef_a'] = (intval($row['col_ef']) >= 7?'A':'NA');
                        if($row['col_cf'] != '') $row['col_cf_a'] = (intval($row['col_cf']) >= 7?'-':'-');
                        if($row['col_ext'] != '') $row['col_ext_a'] = (intval($row['col_ext']) >= 7?'A':'NA');
                        if($row['col_ts'] != '') $row['col_ts_a'] = (intval($row['col_ts']) >= 7?'A':'NA');
                     }

                     if(in_array(strtoupper(substr($row['col_materia_clave'], 0, 2)), array('AC'))) {
                        if($row['col_p1'] != '') $row['col_p1_a'] = (intval($row['col_p1']) >= 1?'A':'NA');
                        if($row['col_p2'] != '') $row['col_p2_a'] = (intval($row['col_p2']) >= 1?'A':'NA');
                        if($row['col_ef'] != '') $row['col_ef_a'] = (intval($row['col_ef']) >= 1?'A':'NA');
                        if($row['col_cf'] != '') $row['col_cf_a'] = (intval($row['col_cf']) >= 1?'-':'-');
                        if($row['col_ext'] != '') $row['col_ext_a'] = (intval($row['col_ext']) >= 1?'A':'NA');
                        if($row['col_ts'] != '') $row['col_ts_a'] = (intval($row['col_ts']) >= 1?'A':'NA');
                     }

                     $materia = getMateria('col_clave', $row['col_materia_clave'], $this->db, $row['col_periodoid'], $alumno->col_carrera);
                     if($materia->nombre_maestro == ''){
                        $materia->nombre_maestro = getMaestroByClaveMateria($row['col_materia_clave'], $alumnoid, $this->db);
                    }
                     $row['nombreMateria'] = fixEncode($materia->col_nombre);
                     $row['posgrado'] = (int) $carrera['posgrado'];
                     $row['nombreMaestro'] = fixEncode($materia->nombre_maestro, true);
                     $row['col_observaciones'] = fixEncode(stripslashes($row[col_observaciones]));


                    $queryMaestro = 'SELECT * FROM tbl_users WHERE col_id="'.$row['col_updated_by'].'" AND col_maestro=1';
                    $sthMaestro = $this->db->prepare($queryMaestro);
                    $sthMaestro->execute();
                    $dataMaestro = $sthMaestro->fetch(PDO::FETCH_OBJ);
                    if($dataMaestro) {
                        $row['nombreMaestro'] = fixEncode($dataMaestro->col_firstname. ' '.$dataMaestro->col_lastname, true);
                    }else{
                        $row['nombreMaestro'] = '-';
                    }

                     $items[] = $row;
                     $fecha = $row['col_updated_at'];
                }

        }

        // asort($items);
        // $i = 0;
        // foreach(_asort($items) as $k => $v){
        //     $v['order'] = $i;
        //     $_itemsData[$k] = $v;
        //     $i++;
        // }

        // $items = array_sort($items, 'nombreMateria', SORT_ASC);

        $_response['calificaciones'] = $items;


        $_response['fecha'] = fechaTexto(date('Y-m-d'));


        return $this->response->withJson($_response);
    });

    $this->post('/modificarCalificaciones', function (Request $request, Response $response, array $args) {
        global $dblog;

        $userType = getCurrentUserType();
        if($userType != 'administrativo' && $userType != 'maestro'){
            $_response['error'] = 'Acceso Invalido';
            return $this->response->withJson($_response);
        }


        $input = $request->getParsedBody();
        $rawData = file_get_contents("php://input");
        $data = json_decode($rawData, true);

        foreach(json_decode($data['p1'], true) as $item) {
            $query = 'UPDATE tbl_calificaciones SET col_p1="'.$item['calificacion'].'" WHERE col_id="'.$item['id'].'"';

            $dblog = new DBLog($query, 'tbl_calificaciones', '', '', 'Calificaciones', $this->db);
            $dblog->where = array('col_id' => intval($item['id']));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->saveLog();
        }

        foreach(json_decode($data['p2'], true) as $item) {
            $query = 'UPDATE tbl_calificaciones SET col_p2="'.$item['calificacion'].'" WHERE col_id="'.$item['id'].'"';

            $dblog = new DBLog($query, 'tbl_calificaciones', '', '', 'Calificaciones', $this->db);
            $dblog->where = array('col_id' => intval($item['id']));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->saveLog();
        }

        foreach(json_decode($data['ef'], true) as $item) {
            $query = 'UPDATE tbl_calificaciones SET col_ef="'.$item['calificacion'].'" WHERE col_id="'.$item['id'].'"';

            $dblog = new DBLog($query, 'tbl_calificaciones', '', '', 'Calificaciones', $this->db);
            $dblog->where = array('col_id' => intval($item['id']));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->saveLog();
        }

        foreach(json_decode($data['cf'], true) as $item) {
            $query = 'UPDATE tbl_calificaciones SET col_cf="'.$item['calificacion'].'" WHERE col_id="'.$item['id'].'"';

            $dblog = new DBLog($query, 'tbl_calificaciones', '', '', 'Calificaciones', $this->db);
            $dblog->where = array('col_id' => intval($item['id']));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->saveLog();
        }

        foreach(json_decode($data['ext'], true) as $item) {
            $query = 'UPDATE tbl_calificaciones SET col_ext="'.$item['calificacion'].'" WHERE col_id="'.$item['id'].'"';

            $dblog = new DBLog($query, 'tbl_calificaciones', '', '', 'Calificaciones', $this->db);
            $dblog->where = array('col_id' => intval($item['id']));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->saveLog();
        }

        foreach(json_decode($data['ts'], true) as $item) {
            $query = 'UPDATE tbl_calificaciones SET col_ts="'.$item['calificacion'].'" WHERE col_id="'.$item['id'].'"';

            $dblog = new DBLog($query, 'tbl_calificaciones', '', '', 'Calificaciones', $this->db);
            $dblog->where = array('col_id' => intval($item['id']));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->saveLog();
        }

        foreach(json_decode($data['observaciones'], true) as $item) {
            $query = 'UPDATE tbl_calificaciones SET col_observaciones="'.addslashes($item['calificacion']).'" WHERE col_id="'.$item['id'].'"';

            $dblog = new DBLog($query, 'tbl_calificaciones', '', '', 'Calificaciones', $this->db);
            $dblog->where = array('col_id' => intval($item['id']));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->saveLog();
        }

        $_result['status'] = 'true';
        return $this->response->withJson($_result);
    });

    $this->get('/listAlumnosByPeriodo', function (Request $request, Response $response, array $args) {

        $sth_taxo = $this->db->prepare("SELECT * FROM tbl_alumnos_taxonomia WHERE col_periodoid='".intval($_REQUEST[periodo])."' ORDER BY col_id ASC");
        $sth_taxo->execute();
        $data_taxo = $sth_taxo->fetchAll();
        foreach($data_taxo as $item_taxo){
            $alumnos[] = $item_taxo['col_alumnoid'];
        }

        $query = "SELECT a.*, IF(c.col_modalidad = 0, 'Semestral', 'Cuatrimestral') as modalidad, CONCAT(p.col_grado, '-', p.col_grupo) as grado FROM tbl_alumnos a ".
        // "LEFT OUTER JOIN tbl_alumnos_taxonomia t ON t.col_alumnoid=a.col_id AND t.col_status=1 ".
        "LEFT OUTER JOIN tbl_carreras c ON c.col_id=a.col_carrera ".
        "LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid ".
        "WHERE a.col_id IN (".implode(',', $alumnos).") ".
        "ORDER BY col_nombres ASC";

        // $result['debug'] = $query;
        // return $this->response->withJson($result);

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;

        foreach($todos as $item){
            $nombre = $item['col_nombres']." ".$item['col_apellidos'];
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['info'] = $item['modalidad'].', '.$item['grado'];
            $result[$i]['label'] = (preg_match('//u', $nombre) == 0?utf8_encode($nombre):$nombre);
            $result[$i]['text'] = (preg_match('//u', $nombre) == 0?utf8_encode($nombre):$nombre);
            $i++;
        }

        return $this->response->withJson($result);

    });


    $this->get('/listAlumnos', function (Request $request, Response $response, array $args) {

        $query = "SELECT a.*, CONCAT(p.col_grado, '-', p.col_grupo) as grado FROM tbl_alumnos a ".
        // "LEFT OUTER JOIN tbl_alumnos_taxonomia t ON t.col_alumnoid=a.col_id AND t.col_status=1 ".
        "LEFT OUTER JOIN tbl_carreras c ON c.col_id=a.col_carrera ".
        "LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid ".
        "ORDER BY col_nombres ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;

        foreach($todos as $item){

            $carreraData = getCarrera($item['col_carrera'], $this->db);

            $nombre = $item['col_nombres']." ".$item['col_apellidos'];
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['info'] = $carreraData['modalidad'].', '.$item['grado'];
            $result[$i]['label'] = (preg_match('//u', $nombre) == 0?utf8_encode($nombre):$nombre);
            $result[$i]['text'] = (preg_match('//u', $nombre) == 0?utf8_encode($nombre):$nombre);
            $i++;
        }

        return $this->response->withJson($result);

    });


    $this->get('/listCarreras', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_carreras ORDER BY col_nombre_largo ASC");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        $estatus = array(0 => '(Inactiva)', 1 => '(Activada)');
        foreach($todos as $item){
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = $item['col_revoe'].' - '.($item['col_nombre_largo']).' '.$estatus[$item['col_estatus']];
            $result[$i]['text'] = $item['col_revoe'].' - '.($item['col_nombre_largo']);
            $result[$i]['nombre'] = fixEncode($item['col_nombre_largo']);
            $result[$i]['clave'] = $item['col_clave'];
            $result[$i]['revoe'] = $item['col_revoe'];
            $result[$i]['estatus'] = $item['col_estatus'];
            $i++;
        }

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

    $this->get('/listPeriodosNombre', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_periodos ORDER BY col_nombre ASC");
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

    $this->get('/listPeriodosGroups', function (Request $request, Response $response, array $args) {

        switch(trim($_REQUEST['tipo'])) {

            case 'semestral';
            $query = "SELECT * FROM tbl_periodos WHERE col_modalidad='1' OR col_modalidad='0' ORDER BY col_nombre ASC";
            break;

            case 'cuatri';
            $query = "SELECT * FROM tbl_periodos WHERE col_modalidad='2' ORDER BY col_nombre ASC";
            break;

            case 'maestria';
            $query = "SELECT * FROM tbl_periodos WHERE col_modalidad='3' ORDER BY col_nombre ASC";
            break;

            case 'doctorado';
            $query = "SELECT * FROM tbl_periodos WHERE col_modalidad='4' ORDER BY col_nombre ASC";
            break;

            default:
            $query = "SELECT * FROM tbl_periodos ORDER BY col_nombre ASC";
            break;
        }

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $carreraData = getCarrera($item['col_carreraid'], $this->db);
            $result[$i]['value'] = fixEncode($item['col_groupid']);
            $result[$i]['label'] = fixEncode($item['col_nombre']).($item['col_aprobado'] == 1?'('.$carreraData['nombre'].') (Aprobado)':' (Periodo NO aprobado)');
            $result[$i]['text'] = fixEncode($item['col_nombre']).' ('.$carreraData['nombre'].')';
            $result[$i]['nombre'] = fixEncode($item['col_nombre']);
            $result[$i]['carrera'] = $carreraData['nombre'];
            $result[$i]['aprobado'] = ($item['col_aprobado'] == 1?'true':'false');
            $i++;
        }

        return $this->response->withJson($result);

    });


    $this->get('/list', function (Request $request, Response $response, array $args) {

        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $currentGroupPeriodo = getCurrentPeriodo($this->db);
        $maestroID = getCurrentUserID();
        $currentPeriodo = getPeriodoTaxoIDSByGroup($currentGroupPeriodo, $this->db);

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $config = $c->fetch(PDO::FETCH_OBJ);

        $periodoData = getPeriodo($currentGroupPeriodo, $this->db, false);


        if(intval($_REQUEST['periodo']) > 0){
            $sth = $this->db->prepare("SELECT pp.col_groupid AS group_periodo_id, a.*, CONCAT(a.col_apellidos, ' ', a.col_nombres) AS col_fullname, CONCAT(pp.col_grado, '-', pp.col_grupo) AS grupo ".
            "FROM tbl_alumnos_taxonomia t ".
            "LEFT OUTER JOIN tbl_periodos pp ON pp.col_id=t.col_periodoid ".
            "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
            // "WHERE t.col_periodoid IN (".implode(',', getPeriodosActivos($_REQUEST['periodo'], $this->db)).") ORDER BY a.col_apellidos ASC");
            "WHERE a.col_periodoid='".intval($_REQUEST['periodo'])."' ORDER BY a.col_apellidos ASC");
        }else{
            $sth = $this->db->prepare("SELECT a.*, CONCAT(a.col_apellidos, ' ', a.col_nombres) AS col_fullname, CONCAT(pp.col_grado, '-', pp.col_grupo) AS grupo FROM tbl_alumnos a LEFT OUTER JOIN tbl_periodos pp ON pp.col_id=a.col_periodoid  ORDER BY a.col_apellidos ASC");
        }
        $sth->execute();
        $todos = $sth->fetchAll();
        $totalAlumnos = $sth->rowCount();

        $result = array();
        $i = 0;
        $tipos = array(
            0 => 'Visual',
            1 => 'Auditivo',
            2 => 'Cinestésico'
        );
        foreach($todos as $item){
            $dataCarrera = getCarrera($item['col_carrera'], $this->db);
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['egresado'] = ($item['col_egresado'] == 1?'Si':'No');
            $result[$i]['col_control'] = fixEncode($item['col_control']);
            $result[$i]['col_fullname'] = fixEncode($item['col_fullname']);
            $result[$i]['col_telefono'] = fixEncode($item['col_telefono']);
            $result[$i]['col_correo'] = fixEncode($item['col_correo']);
            $result[$i]['grupo'] = $item['grupo'];
            $result[$i]['modalidad'] = $dataCarrera['modalidad'];
            $result[$i]['carrera'] = $dataCarrera['nombre'];
            // $result[$i]['periodo'] = getPeriodo($item['col_periodoid'], $this->db);
            $result[$i]['generacion'] = fixEncode($item['col_generacion_start'].'-'.$item['col_generacion_end']);
            $result[$i]['col_fecha_nacimiento'] = fixEncode($item['col_fecha_nacimiento']);
            if($userType == 'maestro') {

                $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_maestroid="'.$maestroID.'" AND col_periodoid="'.$item['periodo_id'].'"';
                $c = $this->db->prepare($query);
                $c->execute();
                $_response['tax_debug'][] = $query;

                $maestro = $c->fetch(PDO::FETCH_OBJ);
                $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.$maestro->col_materia_clave.'" AND col_semestre="'.$item['periodo_semestre'].'" AND col_carrera="'.$item['periodo_carrera'].'" AND col_plan_estudios="'.$item['periodo_plan'].'"';
                $c = $this->db->prepare($query);
                $c->execute();
                $materia = $c->fetch(PDO::FETCH_OBJ);
                $_response['mat_debug'][] = $query;

                $result[$i]['materia'] = fixEncode($materia->col_nombre);

                $queryEva = 'SELECT * FROM tbl_eva_alumnos WHERE col_estatus=2 AND col_group_periodoid="'.$item['group_periodo_id'].'"';
                $c = $this->db->prepare($queryEva);
                $c->execute();
                $eva = $c->fetch(PDO::FETCH_OBJ);


                $evaResult = getResultadoEvaAlumnos($item['col_id'], $this->db, $eva->col_id);
                $result[$i]['tipo'] = $evaResult['resultado'];
            }
            if($userType == 'administrativo') {
                $result[$i]['opciones'] = '<a class="opcion-table" title="Documentos" href="#/pages/alumnos/documentos/'.$item['col_id'].'"><i class="far fa-file-alt"></i></a>&nbsp;&nbsp;';
                // $result[$i]['opciones'] .= '<a class="opcion-table" title="Administrar" href="#/pages/alumnos/administrar/'.$item['col_id'].'"><i class="far fa-address-book"></i></a>&nbsp;&nbsp;';
                $result[$i]['opciones'] .= '<a class="opcion-table" title="Detalles" href="#/pages/alumnos/perfil/'.$item['col_id'].'"><i class="far fa-id-card"></i></a>&nbsp;&nbsp;';
                $result[$i]['opciones'] .= '<a class="opcion-table" title="Calificaciones Periodo Actual" href="#/pages/alumnos/calificaciones-actuales/'.$item['col_periodoid'].'/'.$item['col_id'].'"><i class="fas fa-check-square"></i></a>&nbsp;&nbsp;';
                $result[$i]['opciones'] .= '<a class="opcion-table" title="Administrar Periodos" href="#/pages/alumnos/administrar-periodos/'.$item['col_id'].'"><i class="far fa-list-alt"></i></a>&nbsp;&nbsp;';
                $result[$i]['opciones'] .= '<a class="opcion-table" title="Historico de bajas" href="#/pages/alumnos/bajas/'.$item['col_id'].'"><i class="fas fa-thumbs-down"></i></a>&nbsp;&nbsp;';
            }
            $i++;
        }

        $_response['list'] = $result;
        $_response['total'] = $totalAlumnos;
        $_response['current_periodo'] = $config->col_periodo;
        if(intval($_REQUEST['periodo'])) $_response['current_periodo'] = intval($_REQUEST['periodo']);

        return $response->withStatus(200)
        ->withJson($_response);

    });


    $this->get('/listParaMaestros', function (Request $request, Response $response, array $args) {

        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $currentGroupPeriodo = getCurrentPeriodo($this->db);
        $maestroID = getCurrentUserID();
        $currentPeriodo = getPeriodoTaxoIDSByGroup($currentGroupPeriodo, $this->db);
        $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $config = $c->fetch(PDO::FETCH_OBJ);


            $periodos = getCurrentPeriodos($this->db);
            /*
            $query = "SELECT p.col_carreraid, p.col_plan_estudios, t.col_materia_clave, t.col_id AS ID, t.col_periodoid AS periodoid, p.col_aprobado AS horario_aprobado, p.col_groupid AS periodo_groupid, p.col_grado AS grado, p.col_grupo AS grupo ".
            "FROM tbl_maestros_taxonomia t ".
            "LEFT OUTER JOIN tbl_periodos p ON p.col_id = t.col_periodoid ".
            "WHERE t.col_maestroid='".intval($maestroID)."' ".
            "AND t.col_periodoid IN (".implode(',', $periodos).") ".
            "ORDER BY t.col_id";
            */

            $query = "SELECT p.col_carreraid, p.col_plan_estudios, t.col_materia_clave, t.col_id AS ID, t.col_periodoid AS periodoid, p.col_aprobado AS horario_aprobado, p.col_groupid AS periodo_groupid, p.col_grado AS grado, p.col_grupo AS grupo ".
            "FROM tbl_maestros_taxonomia t ".
            "LEFT OUTER JOIN tbl_periodos p ON p.col_id = t.col_periodoid ".
            "WHERE t.col_maestroid='".intval($maestroID)."' ".
            "AND t.col_periodoid ".
            "ORDER BY t.col_id";

            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();
            $i = 0;

            foreach($todos as $item){
                $periodoData = getPeriodo($item['periodoid'], $this->db, false);
                $queryMateria = 'SELECT * FROM tbl_materias WHERE col_semestre="'.$item['grado'].'" AND col_clave="'.trim($item['col_materia_clave']).'" AND col_plan_estudios="'.$item['col_plan_estudios'].'"';
                //echo '----'.$item['col_materia_clave'].'---';
                $sthm = $this->db->prepare($queryMateria);
                $sthm->execute();
                $materiaData = $sthm->fetch(PDO::FETCH_OBJ);
                if(in_array($item['periodoid'], $periodos) && $materiaData->col_nombre) {

                    if(strtoupper(substr($materiaData->col_clave, 0, 2)) == 'AC'){

                        $queryx = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($materiaData->col_clave).'%" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';

                        $sthx = $this->db->prepare($queryx);
                        $sthx->execute();
                        $dataMateriaMulti = $sthx->fetchAll();
                        unset($multis);
                        foreach($dataMateriaMulti as $mm) {
                            $multis[] = $mm['col_id'];
                        }

                        //echo '---'.$types[$periodoData->col_modalidad].'----';
                        $losPeriodosAC = getCurrentPeriodos($this->db, $types[$periodoData->col_modalidad]);

                        $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
                        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                        "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_academias ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodosAC).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
                        "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                        $todos = $sth->fetchAll();
                        foreach($todos as $item) {
                            $alumno[] = $item['col_id'];
                        }

                    }else if(strtoupper(substr($materiaData->col_clave, 0, 2)) == 'TL'){
                        $queryx = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.substr(trim($materiaData->col_clave), 0, strlen($materiaData->col_clave)-1).'%" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';

                        $sthx = $this->db->prepare($queryx);
                        $sthx->execute();
                        $dataMateriaMulti = $sthx->fetchAll();
                        unset($multis);
                        foreach($dataMateriaMulti as $mm) {
                            $multis[] = $mm['col_id'];
                        }
                        //echo '---'.$types[$periodoData->col_modalidad].'----';
                        $losPeriodosTL = getCurrentPeriodos($this->db, $types[$periodoData->col_modalidad]);

                        $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
                        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                        "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_talleres ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodosTL).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
                        "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                        $todos = $sth->fetchAll();
                        foreach($todos as $item) {
                            $alumno[] = $item['col_id'];
                        }

                    } else {
                        // echo $config->col_periodo;
                        //$types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');


                        //$losPeriodos = getCurrentPeriodos($this->db, $types[$periodoData->col_modalidad]);
                        switch($types[$periodoData->col_modalidad]){
                            case 'ldsem': $grupoPeriodos = $config->col_periodo; break;
                            case 'ldcua': $grupoPeriodos = $config->col_periodo_cuatri; break;
                            case 'docto': $grupoPeriodos = $config->col_periodo_doctorado; break;
                            case 'maester': $grupoPeriodos = $config->col_periodo_maestria; break;
                        }
                        $_losPeriodosRE = getPeriodosActivosMaestroFilter($grupoPeriodos, $maestroID, $this->db);

                        $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
                        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                        "WHERE a.col_estatus='activo' AND t.col_periodoid IN (".implode(',', $_losPeriodosRE).") ORDER BY a.col_apellidos ASC";
                        // "WHERE a.col_estatus='activo' AND t.col_periodoid IN (".implode(',', $losPeriodos).") ORDER BY a.col_apellidos ASC";
                        if(count($losPeriodosRE)){
                            $losPeriodosRE = array_merge($losPeriodosRE, $_losPeriodosRE);
                        }else{
                            $losPeriodosRE = $_losPeriodosRE;
                        }

                        $sth = $this->db->prepare($query);
                        $sth->execute();
                        $todos = $sth->fetchAll();
                        foreach($todos as $item) {
                            $alumno[] = $item['col_id'];
                        }
                    }

                }
            }



        $alumno = array_unique($alumno);

        if(count($losPeriodosAC)) foreach($losPeriodosAC as $_item){ $todosLosPeriodos[] = $_item; }
        if(count($losPeriodosRE)) foreach($losPeriodosRE as $_item){ $todosLosPeriodos[] = $_item; }
        if(count($losPeriodosTL)) foreach($losPeriodosTL as $_item){ $todosLosPeriodos[] = $_item; }
        // $todosLosPeriodos = array_merge($losPeriodosAC, $losPeriodosRE, $losPeriodosTL);
        // print_r($todosLosPeriodos);exit;
        // $todosLosPeriodos = array_unique($todosLosPeriodos);

        $query = "SELECT a.*, CONCAT(a.col_apellidos, ' ', a.col_nombres) AS col_fullname, pp.col_groupid AS group_periodo_id, pp.col_id AS periodo_id, pp.col_grado AS periodo_semestre, pp.col_carreraid AS periodo_carrera, pp.col_plan_estudios AS periodo_plan, CONCAT(pp.col_grado, '-', pp.col_grupo) AS grupo ".
        "FROM tbl_alumnos_taxonomia t ".
        "LEFT OUTER JOIN tbl_periodos pp ON pp.col_id=t.col_periodoid ".
        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
        "WHERE t.col_periodoid IN (".implode(',', $todosLosPeriodos).") AND t.col_alumnoid IN (".implode(',', $alumno).")";
        $sth = $this->db->prepare($query);

        $sth->execute();
        $todos = $sth->fetchAll();
        $result = array();
        $i = 0;
        $tipos = array(
            0 => 'Visual',
            1 => 'Auditivo',
            2 => 'Cinestésico'
        );
        foreach($todos as $item){
            $dataCarrera = getCarrera($item['col_carrera'], $this->db);
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['col_control'] = $item['col_control'];
            $result[$i]['col_fullname'] = ($item['col_fullname']);
            $result[$i]['col_telefono'] = $item['col_telefono'];
            $result[$i]['col_correo'] = $item['col_correo'];
            $result[$i]['grupo'] = $item['grupo'];
            $result[$i]['modalidad'] = $dataCarrera['modalidad'];
            $result[$i]['carrera'] = $dataCarrera['nombre'];
            // $result[$i]['periodo'] = getPeriodo($item['col_periodoid'], $this->db);
            $result[$i]['generacion'] = $item['col_generacion_start'].'-'.$item['col_generacion_end'];
            $result[$i]['col_fecha_nacimiento'] = $item['col_fecha_nacimiento'];
            if($userType == 'maestro') {

                $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_maestroid="'.$maestroID.'" AND col_periodoid="'.$item['periodo_id'].'"';
                $c = $this->db->prepare($query);
                $c->execute();
                // $_response['tax_debug'][] = $query;

                $maestro = $c->fetch(PDO::FETCH_OBJ);
                $query = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.$maestro->col_materia_clave.'%" AND col_semestre="'.$item['periodo_semestre'].'" AND col_carrera="'.$item['periodo_carrera'].'" AND col_plan_estudios="'.$item['periodo_plan'].'"';
                $c = $this->db->prepare($query);
                $c->execute();
                $materia = $c->fetch(PDO::FETCH_OBJ);
                // $_response['mat_debug'][] = $query;
                $result[$i]['materia'] = $materia->col_nombre;


                $queryEva = 'SELECT * FROM tbl_eva_alumnos WHERE col_estatus=2 AND col_group_periodoid="'.$item['group_periodo_id'].'"';
                $c = $this->db->prepare($queryEva);
                $c->execute();
                $eva = $c->fetch(PDO::FETCH_OBJ);


                $evaResult = getResultadoEvaAlumnos($item['col_id'], $this->db, $eva->col_id);
                $result[$i]['tipo'] = $evaResult['resultado'];
            }
            if($userType == 'administrativo') {
                $result[$i]['opciones'] = '<a class="opcion-table" title="Documentos" href="#/pages/alumnos/documentos/'.$item['col_id'].'"><i class="far fa-file-alt"></i></a>&nbsp;&nbsp;';
                // $result[$i]['opciones'] .= '<a class="opcion-table" title="Administrar" href="#/pages/alumnos/administrar/'.$item['col_id'].'"><i class="far fa-address-book"></i></a>&nbsp;&nbsp;';
                $result[$i]['opciones'] .= '<a class="opcion-table" title="Detalles" href="#/pages/alumnos/perfil/'.$item['col_id'].'"><i class="far fa-id-card"></i></a>';
            }
            $i++;
        }

        $_response['list'] = $result;
        $_response['current_periodo'] = $config->col_periodo;
        if(intval($_REQUEST['periodo'])) $_response['current_periodo'] = intval($_REQUEST['periodo']);

        return $this->response->withJson($_response);

    });

    $this->get('/listLectura', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_club_lectura ORDER BY col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        foreach($todos as $item){
            $alumno = getAlumno('col_id', $item['col_alumnoid'], $this->db);
            $materia = getMateria('col_id', $item['col_materiaid'], $this->db);
            $dataCarrera = getCarrera($materia->col_carrera, $this->db);

            $queryc = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($item['col_periodoid']).'"';
            $sthc = $this->db->prepare($queryc);
            $sthc->execute();
            $cl = $sthc->fetch(PDO::FETCH_OBJ);

            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['alumno'] = fixEncode($alumno->col_apellidos." ".$alumno->col_nombres);
            $result[$i]['materia'] = fixEncode(($cl->col_club_lectura != ''?$cl->col_club_lectura:$materia->col_nombre));
            $result[$i]['carrera'] = $dataCarrera['nombre'];
            $result[$i]['periodo'] = getPeriodo($item['col_periodoid'], $this->db);
            $i++;
        }
        return $this->response->withJson($result);

    });

    $this->get('/listSeguimiento', function (Request $request, Response $response, array $args) {

        $queryc = 'SELECT * FROM tbl_practicas WHERE col_id="'.intval($_REQUEST[id]).'"';
        $sthc = $this->db->prepare($queryc);
        $sthc->execute();
        $practica = $sthc->fetch(PDO::FETCH_OBJ);
        $alumno = getAlumno('col_id', $practica->col_alumnoid, $this->db);

        $_response['alumno'] = fixEncode(trim($alumno->col_nombres.' '.$alumno->col_apellidos));

        $sth = $this->db->prepare("SELECT * FROM tbl_practicas_seguimiento WHERE col_practicaid='".intval($_REQUEST[id])."' ORDER BY col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        $formas = array( 0 => 'Telefonica', 1 => 'Visita');
        foreach($todos as $item){
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['forma'] = $formas[$item['col_forma']];
            $result[$i]['observaciones'] = fixEncode($item['col_observaciones']);
            $result[$i]['fecha'] = $item['col_created_at'];
            $i++;
        }

        $_response['historial'] = $result;
        $_response['status'] = 'success';
        return $this->response->withJson($_response);
    });

    $this->delete('/deleteServicios', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_servicio_social WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_servicio_social', '', '', 'Servicio Social', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->delete('/deletePracticas', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_practicas WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_practicas', '', '', 'Practicas', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->post('/getPracticas', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $query = 'SELECT pa.*, CONCAT(a.col_nombres, " ", a.col_apellidos) AS alumno_nombre, pe.col_grado, pe.col_grupo, pe.col_nombre AS periodo_nombre '.
        'FROM tbl_practicas pa '.
        'LEFT OUTER JOIN tbl_alumnos a ON a.col_id=pa.col_alumnoid '.
        'LEFT OUTER JOIN tbl_periodos pe ON pe.col_id=pa.col_periodoid WHERE pa.col_id="'.intval($input['params']['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $dataResponse['id'] = $data->col_id;
        $dataResponse['alumnoid'] = $data->col_alumnoid;
        $dataResponse['alumno_nombre'] = fixEncode($data->alumno_nombre);
        $dataResponse['periodoid'] = $data->col_periodoid;
        $dataResponse['grado'] = $data->col_grado;
        $dataResponse['grupo'] = $data->col_grupo;
        $dataResponse['periodo_nombre'] = fixEncode($data->periodo_nombre);
        $dataResponse['carreraid'] = $data->col_carreraid;
        $dataResponse['convenio'] = fixEncode($data->col_convenio);
        $dataResponse['oficio'] = fixEncode($data->col_oficio);
        $dataResponse['lugar'] = fixEncode($data->col_lugar);
        $dataResponse['titular'] = fixEncode($data->col_titular);
        $dataResponse['cargoTitular'] = fixEncode($data->col_cargo_titular);
        $dataResponse['jefe'] = fixEncode($data->col_jefe);
        $dataResponse['area'] = fixEncode($data->col_area);
        $dataResponse['direccion'] = fixEncode($data->col_direccion);
        $dataResponse['telefono'] = fixEncode($data->col_telefono);

        return $this->response->withJson($dataResponse);

    });

    $this->post('/getServicios', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        //$query = 'SELECT * FROM tbl_servicio_social WHERE col_id="'.intval($input['params']['id']).'"';

        $query = 'SELECT ss.*, CONCAT(a.col_nombres, " ", a.col_apellidos) AS alumno_nombre, pe.col_grado, pe.col_grupo, pe.col_nombre AS periodo_nombre '.
        'FROM tbl_servicio_social ss '.
        'LEFT OUTER JOIN tbl_alumnos a ON a.col_id=ss.col_alumnoid '.
        'LEFT OUTER JOIN tbl_periodos pe ON pe.col_id=ss.col_periodoid WHERE ss.col_id="'.intval($input['params']['id']).'"';

        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);
        // $periodoData = getPeriodo($data->col_periodoid, $this->db, false);
        $dataResponse['id'] = $data->col_id;
        $dataResponse['alumnoid'] = $data->col_alumnoid;
        $dataResponse['alumno_nombre'] = fixEncode($data->alumno_nombre);
        $dataResponse['periodoid'] = $data->col_periodoid;
        $dataResponse['grado'] = $data->col_grado;
        $dataResponse['grupo'] = $data->col_grupo;
        $dataResponse['periodo_nombre'] = fixEncode($data->periodo_nombre);
        $dataResponse['carreraid'] = $data->col_carreraid;
        $dataResponse['convenio'] = fixEncode($data->col_convenio);
        $dataResponse['oficio'] = fixEncode($data->col_oficio);
        $dataResponse['lugar'] = fixEncode($data->col_lugar);
        $dataResponse['titular'] = fixEncode($data->col_titular);
        $dataResponse['cargoTitular'] = fixEncode($data->col_cargo_titular);
        $dataResponse['jefe'] = fixEncode($data->col_jefe);
        $dataResponse['area'] = fixEncode($data->col_area);
        $dataResponse['direccion'] = fixEncode($data->col_direccion);
        $dataResponse['telefono'] = fixEncode($data->col_telefono);

        return $this->response->withJson($dataResponse);

    });

    $this->delete('/deleteSeguimiento', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_practicas_seguimiento WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_practicas_seguimiento', '', '', 'Seguimiento Practicas', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->post('/guardarPractica', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $groupid = getPeriodoTaxoID($input->periodo, $this->db);
        $query = 'SELECT * FROM tbl_periodos WHERE col_grado="'.$input->grado.'" AND col_grupo="'.$input->grupo.'" AND col_groupid="'.$groupid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataPeriodo = $sth->fetch(PDO::FETCH_OBJ);

        if(intval($input->id) == 0){
            $query = 'SELECT * FROM tbl_practicas WHERE col_periodoid="'.$dataPeriodo->col_id.'" AND col_carreraid="'.$input->carreraid.'" AND col_alumnoid="'.$input->alumnoid.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            if($sth->rowCount() > 0) {
                $_response['status'] = 'false';
                $_response['message'] = 'Este alumno ya tiene practica profesional asignada para el periodo seleccionado.';
                return $this->response->withJson($_response);
            }
        }

        $data = array(
            "col_alumnoid" => $input->alumnoid,
            "col_periodoid" => $dataPeriodo->col_id,
            "col_carreraid" => $input->carreraid,
            "col_convenio" => $input->convenio,
            "col_oficio" => $input->oficio,
            "col_lugar" => $input->lugar,
            "col_titular" => $input->titular,
            "col_cargo_titular" => $input->cargoTitular,
            "col_jefe" => $input->jefe,
            "col_area" => $input->area,
            "col_direccion" => $input->direccion,
            "col_telefono" => $input->telefono,
            "col_created_at" => date("Y-m-d H:i:s"),
            "col_created_by" => $input->userid,
            "col_updated_at" => date("Y-m-d H:i:s"),
            "col_updated_by" => $input->userid,
        );

        if(intval($input->id) == 0){

            $query = 'INSERT INTO tbl_practicas ('.implode(",", array_keys($data)).')
            VALUES("'.implode('", "', array_values($data)).'")';

            $dblog = new DBLog($query, 'tbl_practicas', '', '', 'Practicas', $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            if($sth->execute()){
                $_response['status'] = 'true';
                $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                $dblog->saveLog();
            }

        }else{

            $query = 'UPDATE tbl_practicas SET '.prepareUpdate($data).' WHERE col_id="'.$input->id.'"';

            $dblog = new DBLog($query, 'tbl_practicas', '', '', 'Practicas', $this->db);
            $dblog->where = array('col_id' => intval($input->id));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            if($sth->execute()){
                $_response['status'] = 'true';
                $dblog->saveLog();
            }

        }

        // $_response['debug'] = $query;
        return $this->response->withJson($_response);

    });

    $this->post('/guadarServicio', function (Request $request, Response $response, $args) {

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $groupid = getPeriodoTaxoID($input->periodo, $this->db);
        $query = 'SELECT * FROM tbl_periodos WHERE col_grado="'.$input->grado.'" AND col_grupo="'.$input->grupo.'" AND col_groupid="'.$groupid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataPeriodo = $sth->fetch(PDO::FETCH_OBJ);

        if(intval($input->id) == 0){
            $query = 'SELECT * FROM tbl_servicio_social WHERE col_periodoid="'.$dataPeriodo->col_id.'" AND col_carreraid="'.$input->carreraid.'" AND col_alumnoid="'.$input->alumnoid.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            if($sth->rowCount() > 0) {
                $_response['status'] = 'false';
                $_response['message'] = 'Este alumno ya tiene servicio social asignado para el periodo seleccionado.';
                return $this->response->withJson($_response);
            }
        }

        $data = array(
            "col_alumnoid" => $input->alumnoid,
            "col_periodoid" => $dataPeriodo->col_id,
            "col_carreraid" => $input->carreraid,
            "col_oficio" => $input->oficio,
            "col_lugar" => $input->lugar,
            "col_titular" => $input->titular,
            "col_cargo_titular" => $input->cargoTitular,
            "col_jefe" => $input->jefe,
            "col_area" => $input->area,
            "col_direccion" => $input->direccion,
            "col_telefono" => $input->telefono,
            "col_created_at" => date("Y-m-d H:i:s"),
            "col_created_by" => $input->userid,
            "col_updated_at" => date("Y-m-d H:i:s"),
            "col_updated_by" => $input->userid,
        );
        if(intval($input->id) == 0){
            $query = 'INSERT INTO tbl_servicio_social ('.implode(",", array_keys($data)).')
            VALUES("'.implode('", "', array_values($data)).'")';

            $dblog = new DBLog($query, 'tbl_servicio_social', '', '', 'Servicio Social', $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            if($sth->execute()){
                $_response['status'] = 'true';

                $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                $dblog->saveLog();
            }

        }else{
            $query = 'UPDATE tbl_servicio_social SET '.prepareUpdate($data).' WHERE col_id="'.$input->id.'"';

            $dblog = new DBLog($query, 'tbl_servicio_social', '', '', 'Servicio Social', $this->db);
            $dblog->where = array('col_id' => intval($input->id));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            if($sth->execute()){
                $_response['status'] = 'true';
                $dblog->saveLog();
            }
        }

        // $_response['debug'] = $query;
        return $this->response->withJson($_response);

    });

    $this->post('/addSeguimiento', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $data = array(
            "col_practicaid" => intval($input->practicaid),
            "col_forma" => intval($input->forma),
            "col_observaciones" => $input->observaciones,
            "col_created_at" => date("Y-m-d H:i:s"),
            "col_created_by" => $input->userid,
            "col_updated_at" => date("Y-m-d H:i:s"),
            "col_updated_by" => $input->userid,
        );

        $query = 'INSERT INTO tbl_practicas_seguimiento ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';

        $dblog = new DBLog($query, 'tbl_practicas_seguimiento', '', '', 'Practicas', $this->db);
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);

        if($sth->execute()){
            $_response['status'] = 'true';

            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();
        }

        return $this->response->withJson($_response);

    });


    $this->get('/listPracticas', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_practicas ORDER BY col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        foreach($todos as $item){
            $alumno = getAlumno('col_id', $item['col_alumnoid'], $this->db);
            $periodoData = getPeriodo($item['col_periodoid'], $this->db, false);
            $dataCarrera = getCarrera($item['col_carreraid'], $this->db);
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['alumno'] = fixEncode($alumno->col_apellidos." ".$alumno->col_nombres);
            $result[$i]['lugar'] = fixEncode($item['col_lugar']);
            $result[$i]['carrera'] = $dataCarrera['nombre'];
            $result[$i]['periodo'] = $periodoData->col_nombre;
            $result[$i]['grupo'] = $periodoData->col_grado."-".$periodoData->col_grupo;
            $result[$i]['opciones'] = '<a class="opcion-table" title="Seguimiento de Practicas Profesionales" href="#/pages/alumnos/seguimiento-practicas/'.$item['col_id'].'"><i class="far fa-file-alt"></i></a>&nbsp;&nbsp;';

            $query = 'SELECT * FROM tbl_practicas_archivos WHERE col_practicaid="'.intval($item['col_id']).'" AND col_estatus=0';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $css = '';
            if($sth->rowCount()) $css = 'bullet';
            $result[$i]['opciones'] .= '<a class="singleOption opcion-table '.$css.'" title="Reportes del Alumno" href="#/pages/alumnos/reportes-practicas/'.$item['col_id'].'"><i class="fas fa-clipboard"></i></a>';


            $i++;
        }
        return $this->response->withJson($result);

    });

    $this->get('/listServicio', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_servicio_social ORDER BY col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        foreach($todos as $item){
            $alumno = getAlumno('col_id', $item['col_alumnoid'], $this->db);
            $dataCarrera = getCarrera($item['col_carreraid'], $this->db);
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['alumno'] = fixEncode($alumno->col_apellidos." ".$alumno->col_nombres);
            $result[$i]['lugar'] = fixEncode($item['col_lugar']);
            $result[$i]['carrera'] = $dataCarrera['nombre'];
            $result[$i]['periodo'] = getPeriodo($item['col_periodoid'], $this->db);


            $query = 'SELECT * FROM tbl_servicio_social_archivos WHERE col_servicioid="'.intval($item['col_id']).'" AND col_estatus=0';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $css = '';
            if($sth->rowCount()) $css = 'bullet';
            $result[$i]['opciones'] = '<a class="singleOption opcion-table '.$css.'" title="Reportes del Alumno" href="#/pages/alumnos/reportes-servicio/'.$item['col_id'].'"><i class="fas fa-clipboard"></i></a>';
            $i++;
        }
        return $this->response->withJson($result);

    });

    $this->get('/listTransversales', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_transversales ORDER BY col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        foreach($todos as $item){
            $alumno = getAlumno('col_id', $item['col_alumnoid'], $this->db);
            $materia = getMateria('col_id', $item['col_materiaid'], $this->db);
            $dataCarrera = getCarrera($materia->col_carrera, $this->db);
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['alumno'] = fixEncode($alumno->col_apellidos." ".$alumno->col_nombres);
            $result[$i]['materia'] = fixEncode($materia->col_nombre);
            $result[$i]['carrera'] = $dataCarrera['nombre'];
            $result[$i]['periodo'] = getPeriodo($item['col_periodoid'], $this->db);
            $i++;
        }
        return $this->response->withJson($result);

    });

    $this->get('/listTalleres', function (Request $request, Response $response, array $args) {
        $currentPeriodos = getCurrentPeriodos($this->db);
        $sth = $this->db->prepare("SELECT * FROM tbl_talleres ORDER BY col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        foreach($todos as $item){
            $alumno = getAlumno('col_id', $item['col_alumnoid'], $this->db);
            $materia = getMateria('col_id', $item['col_materiaid'], $this->db);
            $dataCarrera = getCarrera($materia->col_carrera, $this->db);
            $periodoData = getPeriodo($item['col_periodoid'], $this->db, false);

            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['col_materiaid'] = $item['col_materiaid'];
            $result[$i]['col_periodoid'] = $item['col_periodoid'];
            $result[$i]['alumno'] = fixEncode($alumno->col_apellidos." ".$alumno->col_nombres);
            $result[$i]['materia'] = fixEncode($materia->col_nombre).' ('.$materia->col_clave.')';
            $result[$i]['carrera'] = $dataCarrera['nombre'];
            $result[$i]['periodo'] = fixEncode($periodoData->col_nombre).(in_array($item['col_periodoid'], $currentPeriodos)?' <span class="text-success"><b>(Activo)</b></span>':'');
            $result[$i]['grupo'] = $periodoData->col_grado.'-'.$periodoData->col_grupo;
            $i++;
        }
        return $this->response->withJson($result);

    });

    $this->get('/listAcademias', function (Request $request, Response $response, array $args) {

        $currentPeriodos = getCurrentPeriodos($this->db);
        $sth = $this->db->prepare("SELECT * FROM tbl_academias ORDER BY col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        foreach($todos as $item){
            $alumno = getAlumno('col_id', $item['col_alumnoid'], $this->db);
            $materia = getMateria('col_id', $item['col_materiaid'], $this->db);
            $dataCarrera = getCarrera($materia->col_carrera, $this->db);
            $periodoData = getPeriodo($item['col_periodoid'], $this->db, false);

            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['col_materiaid'] = $item['col_materiaid'];
            $result[$i]['col_periodoid'] = $item['col_periodoid'];
            $result[$i]['alumno'] = fixEncode($alumno->col_apellidos." ".$alumno->col_nombres);
            if($materia->col_nombre == ''){
                $result[$i]['materia'] = 'Sin Nombre';
            }else{
                $result[$i]['materia'] = fixEncode($materia->col_nombre) .' ('.$materia->col_clave.')';
            }
            $result[$i]['carrera'] = $dataCarrera['nombre'];
            $result[$i]['periodo'] = fixEncode($periodoData->col_nombre).(in_array($item['col_periodoid'], $currentPeriodos)?' <span class="text-success"><b>(Activo)</b></span>':'');
            $result[$i]['grupo'] = $periodoData->col_grado.'-'.$periodoData->col_grupo;
            $i++;
        }
        return $this->response->withJson($result);

    });

    $this->post('/grupo', function (Request $request, Response $response, array $args) {
        $input = json_decode($request->getBody());

        $gth = $this->db->prepare("SELECT * FROM tbl_grupos WHERE col_id='".$input->grupo."'");
        $gth->execute();
        $data = $gth->fetch(PDO::FETCH_OBJ);

        $query = "SELECT *, CONCAT(col_nombres, ' ', col_apellidos) AS col_fullname FROM tbl_alumnos WHERE col_id IN (".implode(',', unserialize(stripslashes($data->col_alumnos))).")";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        // $todos[] = $query;
        return $this->response->withJson($todos);

    });
    $this->post('/get', function (Request $request, Response $response, array $args) {
        global $alertaAsistencias;

        $input = $request->getParsedBody();

        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $alumnoid = $input['params']['id'];
        if($userType == 'alumno') {
            $alumnoid = getCurrentUserID();
        }

        $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval($alumnoid).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $data->col_control = fixEncode($data->col_control);
        $data->col_cedula = fixEncode($data->col_cedula);
        $data->col_nombres = fixEncode($data->col_nombres);
        $data->col_apellidos = fixEncode($data->col_apellidos);
        $data->col_direccion = ucfirst(fixEncode($data->col_direccion));
        $data->col_ciudad = ucfirst(fixEncode($data->col_ciudad));
        $data->col_estado = ucfirst(fixEncode($data->col_estado));
        $data->col_sangre = fixEncode($data->col_sangre);
        $data->col_seguro_folio = fixEncode($data->col_seguro_folio);
        $data->col_enfermedades = fixEncode($data->col_enfermedades);
        $data->col_rep_nombres = fixEncode($data->col_rep_nombres);
        $data->col_rep_apellidos = fixEncode($data->col_rep_apellidos);
        $data->col_rep_empresa = fixEncode($data->col_rep_empresa);
        $data->col_rep_parentesco = fixEncode($data->col_rep_parentesco);
        $data->col_rep_ciudad = fixEncode($data->col_rep_ciudad);
        $data->col_rep_estado = fixEncode($data->col_rep_estado);
        $data->col_genero = strtoupper($data->col_genero);
        $data->is_alumno = 'true';
        $data->isRep = 'false';
        $data->menu = getMenu($this->db);
        if(esRepresentante() == 'true') {
            $data->isRep = 'true';
        }


        if($data->col_periodoid > 0) {
            $data->puedeReinscribirse = puedeReinscribirse($data->col_periodoid, $this->db, $alumnoid);

            if($data->puedeReinscribirse > 0) {
                $periodoParaReinscribirseData = getPeriodo($data->puedeReinscribirse, $this->db, false);
                $data->puedeReinscribirseNombre = fixEncode($periodoParaReinscribirseData->col_nombre);
            }
            $data->materiasReprobadas = alumnoTieneReprobadas($data->col_periodoid, $this->db, $alumnoid);
        }


        $dataCarrera = getCarrera($data->col_carrera, $this->db);

        $data->carreraNombre = $dataCarrera['nombre'];
        $data->carreraModalidad = strtolower($dataCarrera['modalidad']);
        $data->carreraTipoModalidad = $dataCarrera['tipo_modalidad'];

        $dataPeriodo = getPeriodo($data->col_periodoid, $this->db, false);
        $data->periodoActualNombre = fixEncode($dataPeriodo->col_nombre);

        $query = 'SELECT * FROM tbl_eva_maestros WHERE col_estatus="1" AND col_group_periodoid="'.$dataPeriodo->col_groupid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data->evaMaestros = 'closed';
        if($sth->rowCount()) {
            $dataEva = $sth->fetch(PDO::FETCH_OBJ);
            $data->evaMaestros = 'open';
        }


        //$dataPeriodo = getPeriodo($data->col_periodoid, $this->db, false);
        $query = 'SELECT * FROM tbl_eva_alumnos WHERE col_estatus="1" AND col_para="'.($data->carreraModalidad + 1).'" AND col_group_periodoid="'.$dataPeriodo->col_groupid.'" ORDER BY col_id LIMIT 1';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data->evaAlumnos = 'closed';
        if($sth->rowCount()) {
            $dataEvaAlumnos = $sth->fetch(PDO::FETCH_OBJ);
            $data->evaAlumnos = 'open';

            $query = 'SELECT * FROM tbl_eva_alumnos_respuestas WHERE col_evaid="'.$dataEvaAlumnos->col_id.'" AND col_alumnoid="'.$alumnoid.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            if($sth->rowCount()) {
                $data->evaAlumnos = 'closed';
            }


        }


        $data->semestre = $dataPeriodo->col_grado;
        $data->grupo = $dataPeriodo->col_grupo;
        $data->periodoGroup = $dataPeriodo->col_groupid;

        $query = 'SELECT * FROM tbl_pagos WHERE col_alumnoid="'.$data->col_id.'" OR (col_referencia="'.$data->col_referencia.'" AND col_referencia!="")';
        // $query = 'SELECT * FROM tbl_pagos WHERE col_alumnoid="'.$data->col_id.'" OR col_referencia="'.$data->col_referencia.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        if($sth->rowCount()){
            $pagos = $sth->fetch(PDO::FETCH_OBJ);
            if($pagos->col_total_adeudo_vencido > 0){
                if($userType == 'alumno') {
                    $result['alertas'][] = 'Tienes pagos pendientes ($'.number_format($pagos->col_total_adeudo_vencido, 2).'). Fecha de Actualización: '.fechaTexto($pagos->col_updated_at);
                }else{
                    $result['alertas'][] = 'Tiene pagos pendientes. Fecha de Actualización: '.fechaTexto($pagos->col_updated_at);
                }
            }
        }

        $queryBiblioteca = 'SELECT * FROM tbl_biblioteca WHERE col_alumnoid="'.$data->col_id.'" AND col_fecha_devolucion="0000-00-00"';

        $sthBiblioteca = $this->db->prepare($queryBiblioteca);
        $sthBiblioteca->execute();
        if($sthBiblioteca->rowCount()){
            // $bib = $sthBiblioteca->fetch(PDO::FETCH_OBJ);
            $todosBib = $sthBiblioteca->fetchAll();
            foreach($todosBib as $bib){

                $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($bib['col_fecha_prestamo'])), $bib['col_hora_prestamo'], $bib['col_tipo_multa'], $this->db);
                if($bib['col_renovacion'] == 'si'){
                    $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($bib['col_fecha_renovacion'])), $bib['col_hora_renovacion'], $bib['col_tipo_multa'], $this->db);
                }
                if(intval($multa) > 0) {
                    if($userType == 'alumno') {
                        $result['alertas'][] = 'Libro pendiente ('.fixEncode($bib['col_titulo_libro']).') por devolver (Multa: $'.$multa.')';
                    }else{
                        $result['alertas'][] = 'Tiene libro pendiente por devolver ('.fixEncode($bib['col_titulo_libro']).')';
                    }
                }

            }
        }

        // if($data->col_id == 111 || $data->col_id == 93) {
        //     $result['alertas'][] = 'Sin derecho a examen por inasistencias en Derecho Municipal';
        // }

        $query = 'SELECT * FROM tbl_config WHERE col_id=1';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $config = $sth->fetch(PDO::FETCH_OBJ);
        $modalidadAlumno = getModalidadAlumno($data->col_id, $this->db);
        switch($modalidadAlumno) {
            case 'Semestral':
            $primerParcial = explode(',', $config->col_primer_parcial_semestral);
            $segundoParcial = explode(',', $config->col_segundo_parcial_semestral);
            break;

            case 'Cuatrimestral':
            $primerParcial = explode(',', $config->col_primer_parcial_cuatrimestral);
            $segundoParcial = explode(',', $config->col_segundo_parcial_cuatrimestral);
            break;

            case 'Maestria':
            $primerParcial = explode(',', $config->col_primer_parcial_maestria);
            $segundoParcial = explode(',', $config->col_segundo_parcial_maestria);
            break;

            case 'Doctorado':
            $primerParcial = explode(',', $config->col_primer_parcial_doctorado);
            $segundoParcial = explode(',', $config->col_segundo_parcial_doctorado);
            break;
        }
        $rangoFechaInicio = $dataPeriodo->col_fecha_inicio;
        $rangoFechaFin = $primerParcial[0];
        if(strtotime($primerParcial[1]) < strtotime('now')) {
            $rangoFechaInicio = $primerParcial[1];
            $rangoFechaFin = $segundoParcial[0];
        }
        $rangoFechaFin = date('Y-m-d');
        // $asistencias = getAsistenciasByAlumnoAndMateria($data->col_id, $this->db, $rangoFechaInicio, $rangoFechaFin);
        $asistencias = get_AsistenciasByAlumnoAndMateria($data->col_id, $this->db);
        //print_r($asistencias);
        foreach($asistencias as $item){
            if($item[total] > 0 && $item[faltas] > 0){
                if($item['porcentaje'] > 1) $css = 'text-warning';
                if($item['porcentaje'] > $alertaAsistencias) $css = 'text-danger';
                if($item['porcentaje'] < 1) $css = '';
                if($userType == 'alumno') {
                    $result['alertas'][] = '<span class="'.$css.'">Actualmente tienes '.$item['faltas'].' falta(s), en '.$item['materia'].' '.number_format($item['porcentaje_asistencias'], 2).'%.</span>';
                }else{
                    $result['alertas'][] = '<span class="'.$css.'">El alumno tiene '.$item['faltas'].' falta(s), en '.$item['materia'].' '.number_format($item['porcentaje_asistencias'], 2).'%.</span>';
                }
            }
        }




        $data->alertas = $result['alertas'];

        $queryBiblioteca = 'SELECT * FROM tbl_biblioteca WHERE col_alumnoid="'.$data->col_id.'" AND col_fecha_devolucion="0000-00-00"';
        $sthBiblioteca = $this->db->prepare($queryBiblioteca);
        $sthBiblioteca->execute();
        if($sthBiblioteca->rowCount()){
            $bib = $sthBiblioteca->fetch(PDO::FETCH_OBJ);
            if($userType == 'alumno') {
                $result['biblioteca'][] = fixEncode($bib->col_titulo_libro);
            }
            $data->biblioteca = $result['biblioteca'];
        }



        unset($data->col_password);

        return $this->response->withJson($data);

    });

    $this->get('/get', function (Request $request, Response $response, array $args) {
        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $id = intval($_REQUEST['id']);
        if(isset($_REQUEST['session']) && $_REQUEST['session'] == 'true'){
            $id = getCurrentUserID();
        }
        $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$id.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $data->col_control = fixEncode($data->col_control);
        $data->col_cedula = fixEncode($data->col_cedula);
        $data->col_nombres = fixEncode($data->col_nombres);
        $data->col_apellidos = fixEncode($data->col_apellidos);
        $data->col_direccion = ucfirst(fixEncode($data->col_direccion));
        $data->col_ciudad = ucfirst(fixEncode($data->col_ciudad));
        $data->col_estado = ucfirst(fixEncode($data->col_estado));
        $data->col_sangre = fixEncode($data->col_sangre);
        $data->col_seguro_folio = fixEncode($data->col_seguro_folio);
        $data->col_enfermedades = fixEncode($data->col_enfermedades);
        $data->col_rep_nombres = fixEncode($data->col_rep_nombres);
        $data->col_rep_apellidos = fixEncode($data->col_rep_apellidos);
        $data->col_rep_empresa = fixEncode($data->col_rep_empresa);
        $data->col_rep_parentesco = fixEncode($data->col_rep_parentesco);
        $data->col_rep_ciudad = fixEncode($data->col_rep_ciudad);
        $data->col_rep_estado = fixEncode($data->col_rep_estado);
        $data->col_genero = strtoupper($data->col_genero);
        $data->is_alumno = 'true';
        if($data->col_periodoid > 0) {
            $data->puedeReinscribirse = puedeReinscribirse($data->col_periodoid, $this->db, $id);
            if($data->puedeReinscribirse > 0) {
                $periodoParaReinscribirseData = getPeriodo($data->puedeReinscribirse, $this->db, false);
                $data->puedeReinscribirseNombre = fixEncode($periodoParaReinscribirseData->col_nombre);
            }
            $data->materiasReprobadas = alumnoTieneReprobadas($data->col_periodoid, $this->db, $id);
        }
        $data->isRep = 'false';
        if(esRepresentante() == 'true') {
            $data->isRep = 'true';
        }

        $dataCarrera = getCarrera($data->col_carrera, $this->db);

        $data->carreraModalidad = strtolower($dataCarrera['modalidad']);
        $data->carreraTipoModalidad = $dataCarrera['tipo_modalidad'];

        $periodoData = getPeriodo($data->col_periodoid, $this->db, false);
        $data->semestre = $periodoData->col_grado;
        $data->grupo = $periodoData->col_grupo;
        $data->periodo_groupid = $periodoData->col_groupid;

        unset($data->col_password);

        return $this->response->withJson($data);

    });

    $this->get('/getFullInfo', function (Request $request, Response $response, array $args) {
        global $alertaAsistencias;

        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $id = intval($_REQUEST['id']);
        if(intval($_REQUEST['id']) == 0){
            $id = getCurrentUserID();
        }

        $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$id.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_planes_estudios WHERE col_id="'.$data->col_plan_estudios.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $planData = $sth->fetch(PDO::FETCH_OBJ);


        $periodoData = getPeriodo($data->col_periodoid, $this->db, false);
        $carreraData = getCarrera($data->col_carrera, $this->db);

        if(isset($_REQUEST['periodoid']) && intval($_REQUEST['periodoid']) > 0){
            $data->col_periodoid = intval($_REQUEST['periodoid']);
            $periodoData = getPeriodo($data->col_periodoid, $this->db, false);
        }

        $opcionesTitulacion[1] = "PROMEDIO GENERAL DE CALIFICACIONES";
        $opcionesTitulacion[2] = "ESTUDIOS DE POSGRADO (50% DE MAESTRÍA)";
        $opcionesTitulacion[3] = "SUSTENTACIÓN DE EXAMEN POR ÁREAS DE CONOCIMIENTO*";
        $opcionesTitulacion[4] = "CENEVAL";
        $opcionesTitulacion[5] = "TESIS PROFESIONAL";
        $opcionesTitulacion[6] = "CURSO DE TITULACIÓN";
        $opcionesTitulacion[7] = "INFORME O MEMORIA DE PRESTACIÓN DEL SERVICIO SOCIAL OBLIGATORIO";
        $opcionesTitulacion[8] = "MEMORIA DE EXPERIENCIA PROFESIONAL";
        $opcionesTitulacion[9] = "PRODUCCIÓN DE UNA UNIDAD AUDIOVISUAL, ELABORACIÓN DE TEXTOS, PROTOTIPOS DIDÁCTICOS O INSTRUCTIVOS PARA PRESENTACIONES DE UNIDADES TEMÁTICAS O PRÁCTICAS DE LABORATORIO O TALLER";
        $opcionesTitulacion[10] = "TESIS INDIVIDUAL Y RÉPLICA ORAL";
        $opcionesTitulacion[11] = "TESIS COLECTIVA Y RÉPLICA ORAL";
        $opcionesTitulacion[12] = "POR EL 50%  (CINCUENTA POR CIENTO) DE CRÉDITOS DE DOCTORADO";
        $opcionesTitulacion[13] = "EXAMEN GENERAL DE CONOCIMIENTOS";
        $opcionesTitulacion[14] = "POR PROMEDIO";
        $opcionesTitulacion[15] = "TESIS INDIVIDUAL Y REPLICA ORAL";
        $opcionesTitulacion[16] = "PROMEDIO GENERAL DE CALIFICACIONES";

        $result['col_opciones_titulacion'] = $opcionesTitulacion[$data->col_opciones_titulacion];
        $result['col_control'] = fixEncode($data->col_control);
        $result['col_referencia'] = fixEncode($data->col_referencia);
        $result['col_cedula'] = fixEncode($data->col_cedula);
        $result['col_nombres'] = fixEncode($data->col_nombres);
        $result['col_apellidos'] = fixEncode($data->col_apellidos);
        $result['col_direccion'] = ucfirst(fixEncode($data->col_direccion));
        $result['col_ciudad'] = ucfirst(fixEncode($data->col_ciudad));
        $result['col_estado'] = ucfirst(fixEncode($data->col_estado));
        $result['col_pais'] = $data->col_pais;
        $result['col_sangre'] = fixEncode($data->col_sangre);
        $result['col_fecha_nacimiento'] = fechaTexto($data->col_fecha_nacimiento);
        $result['col_telefono'] = phoneFormat($data->col_telefono);
        $result['col_celular'] = phoneFormat($data->col_celular);
        $result['col_correo'] = $data->col_correo;
        $result['col_correoPersonal'] = $data->col_correo_personal;
        $result['col_seguro_folio'] = fixEncode($data->col_seguro_folio);
        $result['col_enfermedades'] = fixEncode($data->col_enfermedades);
        $result['col_rep_nombres'] = fixEncode($data->col_rep_nombres);
        $result['col_rep_apellidos'] = fixEncode($data->col_rep_apellidos);
        $result['col_rep_empresa'] = fixEncode($data->col_rep_empresa);
        $result['col_rep_parentesco'] = fixEncode($data->col_rep_parentesco);
        $result['col_rep_telefono'] = fixEncode($data->col_rep_telefono);
        $result['col_rep_celular'] = fixEncode($data->col_rep_celular);
        $result['col_rep_correo'] = fixEncode($data->col_rep_correo);
        $result['col_rep_empresa'] = fixEncode($data->col_rep_empresa);
        $result['col_rep_empresa_telefono'] = fixEncode($data->col_rep_empresa_telefono);
        $result['col_genero'] = strtoupper($data->col_genero);
        $result['col_periodo'] = fixEncode(ucfirst($periodoData->col_nombre));
        $result['col_carrera'] = fixEncode(ucfirst($carreraData['nombre']));
        $result['col_semestre'] = $periodoData->col_grado;
        $result['col_grupo'] = strtoupper($periodoData->col_grupo);
        $result['planEstudios'] = fixEncode(ucfirst($planData->col_nombre));
        $result['periodo_groupid'] = $periodoData->col_groupid;
        $result['periodo_id'] = $periodoData->col_id;



        $query = 'SELECT * FROM tbl_pagos WHERE col_alumnoid="'.$data->col_id.'" OR (col_referencia="'.$data->col_referencia.'" AND col_referencia!="")';
        // $query = 'SELECT * FROM tbl_pagos WHERE col_alumnoid="'.$data->col_id.'" OR col_referencia="'.$data->col_referencia.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        if($sth->rowCount()){
            $pagos = $sth->fetch(PDO::FETCH_OBJ);
            if($pagos->col_total_adeudo_vencido > 0){
                if($userType == 'alumno') {
                    $result['alertas'][] = 'Tienes pagos pendientes ($'.number_format($pagos->col_total_adeudo_vencido, 2).'). Fecha de Actualización: '.fechaTexto($pagos->col_updated_at);
                }else{
                    $result['alertas'][] = 'Tiene pagos pendientes. Fecha de Actualización: '.fechaTexto($pagos->col_updated_at);
                }
            }
        }

        $queryBiblioteca = 'SELECT * FROM tbl_biblioteca WHERE col_alumnoid="'.$data->col_id.'" AND col_fecha_devolucion="0000-00-00"';
        $sthBiblioteca = $this->db->prepare($queryBiblioteca);
        $sthBiblioteca->execute();
        if($sthBiblioteca->rowCount()){
            // $bib = $sthBiblioteca->fetch(PDO::FETCH_OBJ);
            $todosBib = $sthBiblioteca->fetchAll();
            foreach($todosBib as $bib){

                $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($bib['col_fecha_prestamo'])), $bib['col_hora_prestamo'], $bib['col_tipo_multa'], $this->db);
                if($bib['col_renovacion'] == 'si'){
                    $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($bib['col_fecha_renovacion'])), $bib['col_hora_renovacion'], $bib['col_tipo_multa'], $this->db);
                }

                if($userType == 'alumno') {
                    $result['alertas'][] = 'Libro pendiente ('.fixEncode($bib['col_titulo_libro']).') por devolver (Multa: $'.$multa.')';
                }else{
                    $result['alertas'][] = 'Tiene libro pendiente por devolver ('.fixEncode($bib['col_titulo_libro']).')';
                }
            }
        }

        $query = 'SELECT * FROM tbl_config WHERE col_id=1';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $config = $sth->fetch(PDO::FETCH_OBJ);

        list($rangoFechaInicio, $rangoFechaFin) = getRangoFechas($this->db, $id, $periodoData->col_id);
        $rangoFechaFin = date('Y-m-d');

        $asistencias = get_AsistenciasByAlumnoAndMateria($id, $this->db);
        $result['_asistencias'] = $asistencias;

        foreach($asistencias as $item){
            if($item[total] > 0 && $item[faltas] > 0){
                if($item['porcentaje'] > 1) $css = 'text-warning';
                if($item['porcentaje'] > $alertaAsistencias) $css = 'text-danger';
                if($item['porcentaje'] < 1) $css = '';
                if($userType == 'alumno') {
                    $result['alertas'][] = '<span class="'.$css.'">Actualmente tienes '.$item['faltas'].' falta(s), en '.$item['materia'].' '.number_format($item['porcentaje_asistencias'], 2).'%.</span>';
                }else{
                    $result['alertas'][] = '<span class="'.$css.'">El alumno tiene '.$item['faltas'].' falta(s), en '.$item['materia'].' '.number_format($item['porcentaje_asistencias'], 2).'%.</span>';
                }
            }
        }
        $i = 0;
        if(intval($periodoData->col_transversal) > 0) {
            $queryMaterias = "SELECT * FROM tbl_materias WHERE col_id='".intval($periodoData->col_transversal)."' OR col_plan_estudios='".intval($data->col_plan_estudios)."' AND col_carrera='".intval($data->col_carrera)."' AND col_semestre='".intval($periodoData->col_grado)."' GROUP BY col_clave ORDER BY col_nombre ASC";
            $sth = $this->db->prepare($queryMaterias);
            $sth->execute();
            $__materias = $sth->fetchAll();
            if($sth->rowCount()){


                foreach($__materias as $item){
                    if($item['col_id'] == $periodoData->col_transversal) {
                        $transversal = fixEncode($item['col_nombre']);
                        continue;
                    }else{
                        $result['materias'][$i]['nombre'] = fixEncode($item['col_nombre']);
                        $i++;
                    }
                }
                $result['materias'][$i]['nombre'] = "Transversal: ".$transversal;

            }
        }
        if($periodoData->col_club_lectura != '') {
            $i++;
            $result['materias'][$i]['nombre'] = "Club de Lectura: ".fixEncode($periodoData->col_club_lectura);
        }

        $queryTalleres = 'SELECT m.* FROM tbl_talleres x LEFT OUTER JOIN tbl_materias m ON m.col_id=x.col_materiaid WHERE x.col_alumnoid="'.$data->col_id.'" AND x.col_periodoid="'.$periodoData->col_id.'"';
        $sth = $this->db->prepare($queryTalleres);
        $sth->execute();
        if($sth->rowCount()){
            $taller = $sth->fetch(PDO::FETCH_OBJ);
            $result['talleres'] = fixEncode($taller->col_nombre);
            $result['talleresData']['ID'] = fixEncode($taller->col_id);
            $result['talleresData']['nombre'] = fixEncode($taller->col_nombre);
            $result['talleresData']['clave'] = fixEncode($taller->col_clave);
        }

        $queryAcademias = 'SELECT m.* FROM tbl_academias x LEFT OUTER JOIN tbl_materias m ON m.col_id=x.col_materiaid WHERE x.col_alumnoid="'.$data->col_id.'" AND x.col_periodoid="'.$periodoData->col_id.'"';
        $sth = $this->db->prepare($queryAcademias);
        $sth->execute();
        if($sth->rowCount()){
            $academia = $sth->fetch(PDO::FETCH_OBJ);
            $result['academias'] = fixEncode($academia->col_nombre);
            $result['academiasData']['ID'] = fixEncode($academia->col_id);
            $result['academiasData']['nombre'] = fixEncode($academia->col_nombre);
            $result['academiasData']['clave'] = fixEncode($academia->col_clave);
        }

        $query = 'SELECT * FROM tbl_practicas WHERE col_alumnoid="'.$data->col_id.'" AND col_periodoid="'.$periodoData->col_id.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        if($sth->rowCount()){
            $practicas = $sth->fetch(PDO::FETCH_OBJ);
            $result['practicas']['lugar'] = fixEncode($practicas->col_lugar);
            $result['practicas']['telefono'] = fixEncode($practicas->col_telefono);
            $result['practicas']['direccion'] = fixEncode($practicas->col_direccion);
            $result['practicas']['titular'] = fixEncode($practicas->col_titular);
            $result['practicas']['jefe'] = fixEncode($practicas->col_jefe);
            $result['practicas']['area'] = fixEncode($practicas->col_area);
        }

        $query = 'SELECT * FROM tbl_servicio_social WHERE col_alumnoid="'.$data->col_id.'" AND col_periodoid="'.$periodoData->col_id.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        if($sth->rowCount()){
            $servicio = $sth->fetch(PDO::FETCH_OBJ);
            $result['servicioSocial']['lugar'] = fixEncode($servicio->col_lugar);
            $result['servicioSocial']['telefono'] = fixEncode($servicio->col_telefono);
            $result['servicioSocial']['direccion'] = fixEncode($servicio->col_direccion);
            $result['servicioSocial']['titular'] = fixEncode($servicio->col_titular);
            $result['servicioSocial']['jefe'] = fixEncode($servicio->col_jefe);
            $result['servicioSocial']['area'] = fixEncode($servicio->col_area);
        }

        return $this->response->withJson($result);

    });

    $this->put('/updateRep', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        if(!esRepresentante()){
            $_response['status'] = 'norepresentate';
            return $this->response->withJson($_response);
            exit;
        }

        if($input->passwordRep != '') {
            $_response['strengh'] = checkPassword($input->passwordRep);
            if($_response['strengh'] != 'safe') return $this->response->withJson($_response);
        }

        if(!filter_var(trim($input->repCorreo), FILTER_VALIDATE_EMAIL)){
            $_response['response'] = 'invalid_mail';
            $_response['statusText'] = 'Correo invalido, debes ingresar una cuenta de correo valida.';
            return $this->response->withJson($_response);
            exit;
        }

        $validarCorreo = checarCorreo($input->repCorreo, $this->db);
        if($validarCorreo['status'] !== false && $input->id != $validarCorreo['recordID']) {
            $_response['status'] = 'exists';
            $_response['mensaje'] = $validarCorreo['mensaje'];
            return $this->response->withJson($_response);
        }

        $query = 'UPDATE tbl_alumnos SET col_rep_correo="'.$input->repCorreo.'" WHERE col_id="'.$input->id.'"';

        $dblog = new DBLog($query, 'tbl_alumnos', '', '', 'Alumnos', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();


        if($input->passwordRep == '' && $input->repCorreo != ''){
            $query = 'UPDATE tbl_representantes SET col_correo="'.$input->repCorreo.'" WHERE col_alumnoid="'.$input->id.'"';
        }else if($input->repCorreo != '' && $input->passwordRep != ''){
            $query = 'UPDATE tbl_representantes SET col_correo="'.$input->repCorreo.'", col_password="'.md5($input->passwordRep).'" WHERE col_alumnoid="'.$input->id.'"';
        }

        $dblog = new DBLog($query, 'tbl_representantes', '', '', 'Representate Alumno', $this->db);
        $dblog->where = array('col_alumnoid' => intval($input->id));
        $dblog->prepareLog();


        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        $_response['status'] = 'true';
        return $this->response->withJson($_response);
    });

    $this->put('/update', function (Request $request, Response $response, $args) {
        global $dblog;

        $autorid = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        if(esRepresentante()){
            $_response['status'] = 'representate';
            return $this->response->withJson($_response);
            exit;
        }

        $validarCorreo = checarCorreo($input->correo, $this->db);
        if($validarCorreo['status'] !== false && $input->id != $validarCorreo['recordID']) {
            $_response['status'] = 'exists';
            $_response['mensaje'] = $validarCorreo['mensaje'];
            return $this->response->withJson($_response);
        }

        if($input->password != '') {
            $_response['strengh'] = checkPassword($input->password);
            if($_response['strengh'] != 'safe') return $this->response->withJson($_response);
        }

        $checkBajas = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval($input->id).'"';
        $sth = $this->db->prepare($checkBajas);
        $sth->execute();
        $currentData = $sth->fetch(PDO::FETCH_OBJ);

        if($input->estatus == 'activo') {
            $checkBajas = 'SELECT * FROM tbl_alumnos_bajas WHERE col_alumnoid="'.intval($input->id).'"';
            $sth = $this->db->prepare($checkBajas);
            $sth->execute();
            if($sth->rowCount() >= 2) {
                $_response['status'] = 'blockbajas';
                return $this->response->withJson($_response);
            }
        }

        $fechaNacimiento = substr($input->fechaNacimiento[0], 0, 10);
        if(getCurrentUserType() == 'alumno') {
            $fechaNacimiento = $currentData->col_fecha_nacimiento;
        }

        if($input->estatus !== 'activo' && $currentData->col_estatus !== $input->estatus) {
            $tipoBaja = 0;
            if($input->estatus == 'bajatemporal') $tipoBaja = 1;
                $data_insert = array(
                    "col_alumnoid" => intval($input->id),
                    "col_fecha_baja" => date("Y-m-d H:i:s"),
                    "col_periodoid" => $input->periodo,
                    "col_tipo" => $tipoBaja,
                    "col_created_by" => $autorid
                );

                $query = 'INSERT INTO tbl_alumnos_bajas ('.implode(",", array_keys($data_insert)).')
                VALUES("'.implode('", "', array_values($data_insert)).'")';

                $dblog = new DBLog($query, 'tbl_alumnos_bajas', '', '', 'Alumnos Bajas', $this->db);
                $dblog->prepareLog();

                $sth = $this->db->prepare($query);
                $sth->execute();

                $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                $dblog->saveLog();

        }

        $query = 'UPDATE tbl_alumnos SET
        col_control="'.($input->control).'",
        col_cedula="'.($input->cedula).'",
        col_nombres="'.($input->nombres).'",
        col_apellidos="'.($input->apellidos).'",
        col_fecha_nacimiento="'.$fechaNacimiento.'",
        col_telefono="'.$input->telefono.'",
        col_celular="'.$input->celular.'",
        col_correo="'.$input->correo.'",
        col_correo_personal="'.$input->correoPersonal.'",
        col_genero="'.$input->genero.'",
        col_direccion="'.($input->direccion).'",
        col_ciudad="'.($input->ciudad).'",
        col_estado="'.($input->estado).'",
        col_pais="'.$input->pais.'",
        col_rep_nombres="'.($input->repNombres).'",
        col_rep_apellidos="'.($input->repApellidos).'",
        col_rep_telefono="'.$input->repTelefono.'",
        col_rep_celular="'.$input->repCelular.'",
        col_rep_correo="'.$input->repCorreo.'",
        col_rep_empresa="'.($input->repEmpresa).'",
        col_rep_empresa_telefono="'.$input->repEmpresaTelefono.'",
        col_rep_parentesco="'.($input->repParentesco).'",
        col_rep_ciudad="'.($input->repCiudad).'",
        col_rep_estado="'.($input->repEstado).'",
        col_rep_pais="'.$input->repPais.'",
        col_generacion_start="'.$input->generacionInicio.'",
        col_generacion_end="'.$input->generacionFin.'",
        col_sangre="'.($input->sangre).'",
        col_enfermedades="'.($input->enfermedades).'",
        col_seguro="'.$input->seguro.'",
        col_seguro_folio="'.($input->seguroFolio).'",
        col_carrera="'.$input->carrera.'",
        col_proce_prepa="'.$input->procePrepa.'",
        col_proce_prepa_promedio="'.$input->procePrepaPromedio.'",
        col_proce_universidad_lic="'.$input->proceUniLicenciatura.'",
        col_proce_licenciatura="'.$input->proceLicenciatura.'",
        col_proce_universidad_master="'.$input->proceUniMaestria.'",
        col_proce_maestria="'.$input->proceMaestria.'",
        col_trabajo="'.$input->trabajo.'",
        col_cargo_trabajo="'.$input->cargoTrabajo.'",
        col_credencial="'.$input->credencial.'",
        col_referencia="'.$input->referencia.'",
        col_periodoid="'.$input->periodo.'",
        col_egresado="'.$input->egresado.'",
        col_estatus="'.$input->estatus.'",
        col_opciones_titulacion="'.$input->opcionesTitulacion.'",
        col_fecha_baja="'.substr($input->fechaBaja[0], 0, 10).'",
        col_proce_prepa_estado="'.$input->procePrepaEstado.'",
        col_proce_licenciatura_estado="'.$input->proceLicenciaturaEstado.'",
        col_proce_maestria_estado="'.$input->proceMaestriaEstado.'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$autorid.'"
        WHERE col_id="'.$input->id.'"';

        $dblog = new DBLog($query, 'tbl_alumnos', '', '', 'Alumnos', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);

        if($sth->execute()){
            $dblog->saveLog();

            if($input->password != ''){
                $query = 'UPDATE tbl_alumnos SET
                col_password="'.md5($input->password).'",
                col_password_lastchage="'.date("Y-m-d H:i:s").'",
                col_updated_at="'.date("Y-m-d H:i:s").'",
                col_updated_by="'.$autorid.'" WHERE col_id="'.$input->id.'"';
                $pass = $this->db->prepare($query);
                $pass->execute();
            }

            $query_taxo = 'SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid="'.$input->id.'" AND col_periodoid="'.$input->periodo.'"';
            $sth_taxo = $this->db->prepare($query_taxo);
            $sth_taxo->execute();
            if($sth_taxo->rowCount() > 0){
                $taxo_data = $sth_taxo->fetch(PDO::FETCH_OBJ);
                $taxoid = $taxo_data->col_id;
                $query_taxo = 'UPDATE tbl_alumnos_taxonomia SET col_status=1 WHERE col_id="'.$taxoid.'"';

                $dblog = new DBLog($query, 'tbl_alumnos_taxonomia', '', '', 'Alumnos Taxonomia', $this->db);
                $dblog->where = array('col_id' => intval($input->id));
                $dblog->prepareLog();


                $sth_taxo = $this->db->prepare($query_taxo);
                $sth_taxo->execute();

                $dblog->saveLog();
            }else{
                $query_taxo = 'INSERT INTO tbl_alumnos_taxonomia (col_alumnoid, col_periodoid, col_status)
                VALUES("'.$input->id.'", "'.$input->periodo.'", 1)';

                $dblog = new DBLog($query, 'tbl_alumnos_taxonomia', '', '', 'Alumnos Taxonomia', $this->db);
                $dblog->prepareLog();

                $sth_taxo = $this->db->prepare($query_taxo);
                $sth_taxo->execute();

                $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                $dblog->saveLog();
            }



            $_response['status'] = 'true';
        }

        return $this->response->withJson($_response);

    });


    $this->post('/add', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        // $query = 'SELECT * FROM tbl_alumnos WHERE col_correo="'.$input->correo.'"';
        // $fth = $this->db->prepare($query);
        // $fth->execute();
        // if($fth->rowCount()) {
        //     $_response['status'] = 'exists';
        //     return $this->response->withJson($_response);
        // }

        $validarCorreo = checarCorreo($input->correo, $this->db);
        if($validarCorreo['status'] !== false) {
            $_response['status'] = 'exists';
            $_response['mensaje'] = $validarCorreo['mensaje'];
            return $this->response->withJson($_response);
        }

        // $fechaNacimiento = $input->fechaNacimiento->year.'-'.$input->fechaNacimiento->month.'-'.$input->fechaNacimiento->day;
        if($input->password != '') {
            $_response['strengh'] = checkPassword($input->password);
            if($_response['strengh'] != 'safe') return $this->response->withJson($_response);
        }

        $data = array(
            "col_password" => md5($input->password),
            "col_cedula" => utf8_decode($input->cedula),
            "col_control" => utf8_decode($input->control),
            "col_nombres" => utf8_decode($input->nombres),
            "col_apellidos" => utf8_decode($input->apellidos),
            "col_fecha_nacimiento" => substr($input->fechaNacimiento[0], 0, 10),
            "col_telefono" => $input->telefono,
            "col_celular" => $input->celular,
            "col_correo" => $input->correo,
            "col_correo_personal" => $input->correoPersonal,
            "col_genero" => $input->genero,
            "col_direccion" => utf8_decode($input->direccion),
            "col_ciudad" => utf8_decode($input->ciudad),
            "col_estado" => utf8_decode($input->estado),
            "col_pais" => $input->pais,
            "col_rep_nombres" => utf8_decode($input->repNombres),
            "col_rep_apellidos" => utf8_decode($input->repApellidos),
            "col_rep_telefono" => $input->repTelefono,
            "col_rep_celular" => $input->repCelular,
            "col_rep_correo" => $input->repCorreo,
            "col_rep_empresa" => utf8_decode($input->repEmpresa),
            "col_rep_empresa_telefono" => $input->repEmpresaTelefono,
            "col_rep_parentesco" => utf8_decode($input->repParentesco),
            "col_rep_ciudad" => utf8_decode($input->repCiudad),
            "col_rep_estado" => utf8_decode($input->repEstado),
            "col_rep_pais" => $input->repPais,
            "col_generacion_start" => $input->generacionInicio,
            "col_generacion_end" => $input->generacionFin,
            "col_sangre" => utf8_decode($input->sangre),
            "col_enfermedades" => utf8_decode($input->enfermedades),
            "col_seguro" => $input->seguro,
            "col_seguro_folio" => utf8_decode($input->seguroFolio),
            "col_carrera" => $input->carrera,
            "col_proce_prepa" => $input->procePrepa,
            "col_proce_prepa_promedio" => $input->procePrepaPromedio,
            "col_proce_universidad_lic" => $input->proceUniLicenciatura,
            "col_proce_licenciatura" => $input->proceLicenciatura,
            "col_proce_universidad_master" => $input->proceUniMaestria,
            "col_proce_maestria" => $input->proceMaestria,
            "col_trabajo" => $input->trabajo,
            "col_cargo_trabajo" => $input->cargoTrabajo,
            "col_periodoid" => $input->periodo,
            "col_egresado" => $input->egresado,
            "col_credencial" => $input->credencial,
            "col_referencia" => $input->referencia,
            "col_estatus" => $input->estatus,
            "col_fecha_baja" => substr($input->fechaBaja[0], 0, 10),
            "col_proce_prepa_estado" => $input->procePrepaEstado,
            "col_proce_licenciatura_estado" => $input->proceLicenciaturaEstado,
            "col_proce_maestria_estado" => $input->proceMaestriaEstado,
            "col_created_at" => date("Y-m-d H:i:s"),
            "col_created_by" => $input->userid,
            "col_updated_at" => date("Y-m-d H:i:s"),
            "col_updated_by" => $input->userid,
        );

        $query = 'INSERT INTO tbl_alumnos ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';

        $dblog = new DBLog($query, 'tbl_alumnos', '', '', 'Alumnos', $this->db);
        $dblog->prepareLog();

         $sth = $this->db->prepare($query);

         $query_taxo = 'INSERT INTO tbl_alumnos_taxonomia (col_alumnoid, col_periodoid, col_status)
         VALUES("'.$this->db->lastInsertId().'", "'.$input->periodo.'", 1)';

        $dblogTax = new DBLog($query, 'tbl_alumnos_taxonomia', '', '', 'Alumnos Taxonomia', $this->db);
        $dblogTax->prepareLog();

         $sth_taxo = $this->db->prepare($query_taxo);
         $sth_taxo->execute();

         $dblogTax->where = array('col_id' => intval($this->db->lastInsertId()));
         $dblogTax->saveLog();

        // $sth->execute();
        if($sth->execute()){
            $_response['status'] = 'true';
            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();
        }

        return $this->response->withJson($_response);

    });

    $this->delete('/delete', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_alumnos WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_alumnos', '', '', 'Alumnos', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        // $query = 'DELETE FROM tbl_documentos WHERE col_alumnoid="'.intval($_REQUEST['id']).'"';
        // $docs = $this->db->prepare($query);
        // $docs->execute();
        // $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->delete('/deleteBaja', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_alumnos_bajas WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_alumnos_bajas', '', '', 'Alumnos Bajas', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();
        return $this->response->withJson(array('status' => 'true'));

    });

    $this->delete('/deleteAcademia', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_academias WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_academias', '', '', 'Alumnos Academias', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->delete('/deleteTaller', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_talleres WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_talleres', '', '', 'Alumnos Talleres', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->post('/docs', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        foreach($input->docs as $doc){
            $docsData[$doc] = 'x';
        }

        $query = 'UPDATE tbl_alumnos SET
        col_documentos="'.base64_encode(serialize($docsData)).'",
        col_documentos_otros="'.$input->otros.'",
        col_documentos_observaciones="'.$input->observaciones.'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$input->userid.'"
        WHERE col_id="'.$input->alumnoid.'"';

        $dblog = new DBLog($query, 'tbl_alumnos', '', '', 'Alumnos Documentos', $this->db);
        $dblog->where = array('col_id' => intval($input->params->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        if($sth->execute()){
            $dblog->saveLog();
            $_response['status'] = 'true';
        }
        // $_response['debug'] = $query;
        // $_response['params'] = $input;

        return $this->response->withJson($_response);

    });

    $this->put('/reset', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        if(isAdmin()) {
            $userID = getCurrentUserID();

            $query = 'UPDATE tbl_alumnos SET col_password="'.md5('fldch2019+').'", col_password_lastchage="0000-00-00 00:00:00", col_primer_acceso="0000-00-00", col_updated_by="'.$userID.'", col_updated_at="'.date("Y-m-d H:i:s").'" WHERE col_id="'.$input->id.'"';

            $dblog = new DBLog($query, 'tbl_alumnos', '', '', 'Alumnos', $this->db);
            $dblog->where = array('col_id' => intval($input->params->id));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->saveLog();

            $_response['status'] = 'true';
        }

        return $this->response->withJson($_response);
    });

    $this->put('/accesoTutor', function (Request $request, Response $response, $args) {
        global $nombreInstituto, $claveInstitulo, $inicialesInstituto, $logoInstituto, $dblog;
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        if(isAdmin()) {
            $userID = getCurrentUserID();
            $pass = trim(randomPassword());
            $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval($input->id).'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $dataAlumno = $sth->fetch(PDO::FETCH_OBJ);
            if(filter_var(trim($dataAlumno->col_rep_correo), FILTER_VALIDATE_EMAIL)){

                $nombreTutor = fixEncode($dataAlumno->col_rep_nombres.' '.$dataAlumno->col_rep_apellidos);
                $correoTutor = trim($dataAlumno->col_rep_correo);

                $query = 'SELECT * FROM tbl_representantes WHERE col_alumnoid="'.$input->id.'"';
                $sth = $this->db->prepare($query);
                $sth->execute();
                if($sth->rowCount() == 0) {
                    $query = 'INSERT INTO tbl_representantes (col_alumnoid, col_correo, col_password, col_updated_by, col_updated_at) VALUES("'.$input->id.'", "'.$correoTutor.'", "'.md5($pass).'", "'.$userID.'", "'.date("Y-m-d H:i:s").'")';

                    $dblog = new DBLog($query, 'tbl_representantes', '', '', 'Representate Alumno', $this->db);
                    $dblog->prepareLog();

                    $sth = $this->db->prepare($query);
                    $sth->execute();

                    $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                    $dblog->saveLog();
                }else{
                    $query = 'UPDATE tbl_representantes SET col_correo="'.$correoTutor.'", col_password="'.md5($pass).'", col_updated_by="'.$userID.'", col_updated_at="'.date("Y-m-d H:i:s").'" WHERE col_alumnoid="'.$input->id.'"';

                    $dblog = new DBLog($query, 'tbl_representantes', '', '', 'Representate Alumno', $this->db);
                    $dblog->where = array('col_id' => intval($input->id));
                    $dblog->prepareLog();

                    $sth = $this->db->prepare($query);
                    $sth->execute();

                    $dblog->saveLog();
                }

                ob_start();
                ?>
                <p>Hola Sr.(a) <?php echo $nombreTutor; ?>,</p>
                <p>Este correo es para hacerte llegar su nueva contraseña de acceso, con la cual podrá acceder al portal (<a href="https://plataforma.fldch.edu.mx">https://plataforma.fldch.edu.mx</a>) para dar seguimiento a las actividades de su representado.</p>
                <p>En la plataforma podrá ver las actividades que ha realizado el alumno, su estatus de entrega, las calificaciones de las actividades, de los examenes y también alertas de asistencias, deudas y/o detalles administrativos.</p>
                <p>La información necesaria para acceder a la plataforma es la siguiente:</p>
                    <ul>
                        <li>Correo: <?php echo $correoTutor; ?></li>
                        <li>Nueva contraseña: <?php echo $pass; ?></li>
                    </ul>
                <p>Saludos,<br/><?php echo $nombreInstituto; ?></p>
                <p><img src="<?php echo $logoInstituto; ?>" style="width:auto;height: 50px;" alt="<?php echo $inicialesInstituto; ?> Logo"></p>
                <?php
                $texto = ob_get_contents();
                ob_end_clean();

                //$correoTutor = 'jorge.x3@gmail.com';
                enviarCorreo(array('to' => $correoTutor, 'nombre' => $nombreTutor), 'Nueva Acceso - '.$inicialesInstituto, $texto);
                enviarCorreo(array('to' => 'academicolicenciatura@fldch.edu.mx', 'nombre' => $nombreTutor), 'Copia Nueva Acceso de Tutor - FLDCH', $texto);


                $_response['status'] = 'true';
            }else{
                $_response['status'] = 'error';
            }
        }

        return $this->response->withJson($_response);
    });

});

$app->group('/alumnos-data', function () {

    $this->get('/listPeriodos', function (Request $request, Response $response, array $args) {

        $modalidades = array(0 => "Sin definir", 1 => "Semestral Licenciatura", 2 => "Cuatrimestral Licenciatura", 3 => "Semestral Maestria", 4 => "Semestral Doctorado");

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $config = $c->fetch(PDO::FETCH_OBJ);
        $activos = array(intval($config->col_periodo), intval($config->col_periodo_cuatri), intval($config->col_periodo_maestria), intval($config->col_periodo_doctorado));
        $menor = min(array_values($activos));
        $mayor = max(array_values($activos));

        $query = "SELECT * FROM tbl_periodos GROUP BY col_modalidad ORDER BY col_groupid DESC, col_nombre ASC";
        if($_REQUEST['activos'] == 'true') {
            $query = "SELECT * FROM tbl_periodos WHERE col_groupid IN (".implode(',', $activos).") GROUP BY col_modalidad ORDER BY col_groupid DESC, col_nombre ASC";
        }
        if($_REQUEST['activospasados'] == 'true') {
            $query = "SELECT * FROM tbl_periodos WHERE col_groupid <= '".$mayor."' GROUP BY col_modalidad ORDER BY col_groupid DESC, col_nombre ASC";
        }

        if($_REQUEST['activosfull'] == 'true') {
            $query = "SELECT * FROM tbl_periodos WHERE col_groupid IN (".implode(',', $activos).") ORDER BY col_groupid DESC, col_nombre ASC";
        }

        if($_REQUEST['future'] == 'true') {
            $query = "SELECT * FROM tbl_periodos WHERE col_groupid > '".$menor."' GROUP BY col_groupid ORDER BY col_groupid DESC, col_nombre ASC";
        }

        if($_REQUEST['all'] == 'true') {
            $query = "SELECT DISTINCT col_groupid, col_id, col_groupid, col_carreraid, col_nombre, col_grado, col_grupo, col_modalidad FROM tbl_periodos ORDER BY col_groupid DESC, col_nombre ASC";
        }

        if($_REQUEST['all-expand'] == 'true') {
            $query = "SELECT * FROM tbl_periodos ORDER BY col_groupid DESC, col_nombre ASC";
        }

        // echo $query;exit;

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        // print_r($todos);
        $i = 0;
        if($_REQUEST['any'] == 'true') {
            $result[0]['label'] = 'Todos los periodos';
            $result[0]['text'] = 'Todos los periodos';
            $result[0]['nombre'] = 'Todos los periodos';
            $result[0]['modalidad'] = 'Se mostraran todos los registros sin importar el periodo';
            $result[0]['carrera'] = '';
            $result[0]['selected'] = 'true';
            $result[0]['activo'] = 'false';
            $result[0]['grado'] = '';
            $result[0]['grupo'] = '';
            $result[0]['groupid'] = '-1';
            $result[0]['value'] = '-1';
            $i = 1;
        }

        if($_REQUEST['currentOption'] == 'true') {
            $result[0]['label'] = 'Periodos Actuales';
            $result[0]['text'] = 'Periodos Actuales';
            $result[0]['nombre'] = 'Periodos Actuales';
            $result[0]['modalidad'] = 'Se mostrará información de los periodos actualmente activos.';
            $result[0]['carrera'] = '';
            $result[0]['selected'] = 'false';
            $result[0]['activo'] = 'false';
            $result[0]['grado'] = '';
            $result[0]['grupo'] = '';
            $result[0]['groupid'] = '-1';
            $result[0]['value'] = '-1';
            $i = 1;
        }

        $losGrados = array();

        foreach($todos as $item){

            if($_REQUEST['agrupar'] == 'true') {
                if(in_array(md5($item['col_groupid'].'-'.$item['col_grado'].'-'.$item['col_grupo']), $losGrados)) {
                    continue;
                }else{
                    $losGrados[] = md5($item['col_groupid'].'-'.$item['col_grado'].'-'.$item['col_grupo']);
                }
            }

            if($_REQUEST['agrupar_full'] == 'true') {
                if(in_array(md5($item['col_groupid']), $losGrados)) {
                    continue;
                }else{
                    $losGrados[] = md5($item['col_groupid']);
                }
            }

            $queryPlanesEstudios = "SELECT * FROM tbl_planes_estudios WHERE col_id='".$item['col_plan_estudios']."'";
            $sth = $this->db->prepare($queryPlanesEstudios);
            $sth->execute();
            $dataPlanesEstudios = $sth->fetch(PDO::FETCH_OBJ);



            $carreraData = getCarrera($item['col_carreraid'], $this->db);
            $result[$i]['value'] = $item['col_id'];
            // $result[$i]['debug'] = $query;
            $result[$i]['groupid'] = $item['col_groupid'];
            if(in_array($item['col_groupid'], $activos) && ($_REQUEST['activos'] == 'true' || $_REQUEST['all'] == 'true' || $_REQUEST['all-expand'] == 'true')){
                $result[$i]['label'] = fixEncode($item['col_nombre']).' ('.$modalidades[$item['col_modalidad']].' - '.$carreraData['nombre'].')'.($_REQUEST['activos'] != 'true'?'(Periodo Actual)':'');
                $result[$i]['text'] = fixEncode($item['col_nombre']).' ('.$modalidades[$item['col_modalidad']].' -'.$carreraData['nombre'].')'.($_REQUEST['activos'] != 'true'?'(Periodo Actual)':'');
                $result[$i]['nombre'] = fixEncode($item['col_nombre']);
                $result[$i]['modalidad'] = $modalidades[$item['col_modalidad']];
                $result[$i]['carrera'] = $carreraData['nombre'];
                $result[$i]['selected'] = 'true';
                $result[$i]['activo'] = 'true';
                $result[$i]['grado'] = $item['col_grado'];
                $result[$i]['grupo'] = $item['col_grupo'];
                $result[$i]['plan'] = $dataPlanesEstudios->col_nombre;
            }else{
                $result[$i]['label'] = fixEncode($item['col_nombre']).' ('.$modalidades[$item['col_modalidad']].' - '.$carreraData['nombre'].')'.($_REQUEST['activosfull'] == 'true'?' '.$item['col_grado'].'-'.$item['col_grupo']:'');
                // $result[$i]['text'] = fixEncode($item['col_nombre']).' ('.$modalidades[$item['col_modalidad']].' - '.$carreraData['nombre'].')'.($_REQUEST['activosfull'] == 'true'?' '.$item['col_grado'].'-'.$item['col_grupo']:'');
                $result[$i]['text'] = $result[$i]['label'];
                $result[$i]['nombre'] = fixEncode($item['col_nombre']);
                $result[$i]['modalidad'] = $modalidades[$item['col_modalidad']];
                $result[$i]['carrera'] = $carreraData['nombre'];
                $result[$i]['selected'] = 'false';
                $result[$i]['activo'] = 'false';
                $result[$i]['grado'] = $item['col_grado'];
                $result[$i]['grupo'] = $item['col_grupo'];
                $result[$i]['plan'] = $dataPlanesEstudios->col_nombre;
            }
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listPeriodosAlumno', function (Request $request, Response $response, array $args) {
        $userType = getCurrentUserType();
        $modalidades = array(0 => "Sin definir", 1 => "Semestral Licenciatura", 2 => "Cuatrimestral Licenciatura", 3 => "Semestral Maestria", 4 => "Semestral Doctorado");
        if($userType == 'alumno' && (!isset($_REQUEST['id']) || $_REQUEST['id'] == 0)){
            $_REQUEST['id'] = getCurrentUserID();
        }

        $query = 'SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid="'.$_REQUEST['id'].'"';
        $c = $this->db->prepare($query);
        $c->execute();
        $todos = $c->fetchAll();
        foreach($todos as $item){
            $history[] = $item['col_periodoid'];
        }

        $query = "SELECT * FROM tbl_periodos WHERE col_id IN (".implode(',', $history).") ORDER BY col_grado DESC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();


        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['groupid'] = $item['col_groupid'];
            $result[$i]['label'] = $item['col_grado'].'-'.$item['col_grupo'].' '.fixEncode($item['col_nombre']);
            $result[$i]['text'] = fixEncode($item['col_nombre']);
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listGrados', function (Request $request, Response $response, array $args) {

        $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($_REQUEST['periodo']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $query = "SELECT * FROM tbl_periodos WHERE col_nombre LIKE '%".$data->col_nombre."%' GROUP BY col_grado ORDER BY col_grado ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = fixEncode($item['col_grado']);
            $result[$i]['text'] = fixEncode($item['col_grado']);
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listGrupos', function (Request $request, Response $response, array $args) {

        $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($_REQUEST['grado']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $query = "SELECT * FROM tbl_periodos WHERE col_nombre LIKE '%".$data->col_nombre."%' GROUP BY col_grupo ORDER BY col_grupo ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = fixEncode($item['col_grupo']);
            $result[$i]['text'] = fixEncode($item['col_grupo']);
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listGradosValue', function (Request $request, Response $response, array $args) {

        $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($_REQUEST['periodo']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        // $query = "SELECT * FROM tbl_periodos WHERE col_nombre LIKE '%".$data->col_nombre."%' GROUP BY col_grado ORDER BY col_grado ASC";
        $query = "SELECT * FROM tbl_periodos WHERE col_groupid='".$data->col_groupid."' GROUP BY col_grado ORDER BY col_grado ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = $item['col_grado'];
            $result[$i]['label'] = fixEncode($item['col_grado']);
            $result[$i]['text'] = fixEncode($item['col_grado']);
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listGruposValue', function (Request $request, Response $response, array $args) {

        $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($_REQUEST['periodo']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $query = "SELECT * FROM tbl_periodos WHERE col_grado='".$_REQUEST['grado']."' AND col_groupid='".$data->col_groupid."' GROUP BY col_grupo ORDER BY col_grupo ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $result[0]['value'] = '';
        $result[0]['label'] = 'Seleccionar';
        $result[0]['text'] = 'Seleccionar';

        $i = 1;
        foreach($todos as $item){
            $result[$i]['value'] = $item['col_grupo'];
            $result[$i]['label'] = fixEncode($item['col_grupo']);
            $result[$i]['text'] = fixEncode($item['col_grupo']);
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listCarrerasComplements', function (Request $request, Response $response, array $args) {

        $periodo = intval($_REQUEST['periodo']);
        $periodoid = intval($_REQUEST['periodoid']);
        $groupid = intval($_REQUEST['groupid']);


        if(!isset($_REQUEST['periodoid'])){
            // $periodoid = $periodo;
            $query = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.intval($groupid).'" AND col_grado="'.intval($_REQUEST['grado']).'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $_dataPeriodo = $sth->fetch(PDO::FETCH_OBJ);
            $periodoid = $_dataPeriodo->col_id;
        }

        if(!isset($_REQUEST['periodoid']) && isset($_REQUEST['periodo']) ){
            $periodoid = $periodo;
        }

        $semestre = intval($_REQUEST['grado']);
        if(isset($_REQUEST['alumno'])){
            $alumnoid = intval($_REQUEST['alumno']);
        }else{
            $alumnoid = getCurrentUserID();
        }
        $carreraid = intval($_REQUEST['carrera']);
        $_response['alumnoID'] = $alumnoid;

        $periodoData = getPeriodo($periodoid, $this->db, false);
        // $limiteTaller = 20;
        // $limiteAcademia = 15;
        /*
        $limiteTaller = 100;
        $limiteAcademia = 100;


        if($periodoData->col_grado > 1){
            //$limiteTaller = 12;
            //$limiteAcademia = 11;
            $limiteTaller = 21;
            $limiteAcademia = 21;
        }
        */

        // Empiezan Cambios 2020-02-06
        if($periodoData->col_grado == 1 || $periodoData->col_grado == 2){
            $limiteTaller = 100;
            $limiteAcademia = 100;
        }

        if($periodoData->col_grado > 2){
            // $limiteTaller = 17;
            $limiteTaller = 50;
            // $limiteAcademia = 18;
            $limiteAcademia = 50;
        }
        // Terminan Cambios 2020-02-06

        $queryTalleresTomados = 'SELECT x.col_id AS ID, x.col_periodoid AS periodoID, y.* FROM tbl_talleres x LEFT OUTER JOIN tbl_materias y ON y.col_id=x.col_materiaid WHERE x.col_alumnoid="'.$alumnoid.'" AND x.col_periodoid!="'.intval($periodoid).'"';
        $sthc = $this->db->prepare($queryTalleresTomados);
        $sthc->execute();
        $todosTomadas = $sthc->fetchAll();
        foreach($todosTomadas as $item){
            $_pData = getPeriodo($item['periodoID'], $this->db, false);
            $talleresTomados[] = strtoupper(claveMateria($item['col_clave']));
            $_talleresTomados[] = array('id' => $item['ID'], 'clave' => claveMateria($item['col_clave']), 'nombre' => fixEncode($item['col_nombre']).' ('.$_pData->col_grado.'-'.$_pData->col_grupo.')');
        }

        $queryTalleres = "SELECT t.col_id, t.col_maestroid, t.col_materia_clave AS claveMateria, m.col_id AS materiaID, m.col_nombre AS nombreMateria FROM  tbl_maestros_taxonomia t LEFT OUTER JOIN tbl_materias m ON m.col_clave = t.col_materia_clave WHERE t.col_periodoid ='".intval($periodoid)."' AND t.col_materia_clave LIKE 'TL%' AND m.col_id AND m.col_semestre='".$periodoData->col_grado."' AND m.col_plan_estudios='".$periodoData->col_plan_estudios."' GROUP BY t.col_id";
        $sthc = $this->db->prepare($queryTalleres);
        $sthc->execute();
        $todos = $sthc->fetchAll();
        $i = 0;
        $agregados = array();
        foreach($todos as $item){

            // $queryTalleresInscritos = 'SELECT * FROM tbl_talleres WHERE col_materiaid="'.intval($item['materiaID']).'" AND col_periodoid="'.intval($periodoid).'" GROUP BY col_alumnoid';
            $queryTalleresInscritos = 'SELECT count(a.col_alumnoid) AS totalInscritos, m.col_nombre FROM tbl_talleres a LEFT OUTER JOIN tbl_materias m ON m.col_id=a.col_materiaid WHERE m.col_clave LIKE  "'.substr($item['claveMateria'], 0, 6).'%" AND a.col_periodoid="'.$periodoid.'" GROUP BY SUBSTRING(m.col_clave, 1, 6)';
            $sthc = $this->db->prepare($queryTalleresInscritos);
            $sthc->execute();
            $dataTalleresInscritos = $sthc->fetch(PDO::FETCH_OBJ);
            // if($sthc->rowCount() == 0){
            if($dataTalleresInscritos->totalInscritos <= $limiteTaller && !in_array(strtoupper(substr($item['claveMateria'], 0, 6)), $talleresTomados)){
                $result[$i]['value'] = $item['materiaID'];
                $result[$i]['label'] = fixEncode($item['nombreMateria']).' ('.$item['claveMateria'].')';
                $result[$i]['text'] = fixEncode($item['nombreMateria']).' ('.$item['claveMateria'].')';
                $i++;
            }

            if(!in_array(claveMateria($item['claveMateria']), $agregados)) $_talleresLlenos[] = array('clave' => claveMateria($item['claveMateria']), 'nombre' => fixEncode($item['nombreMateria']), 'inscritos' => intval($dataTalleresInscritos->totalInscritos));
            $agregados[] = claveMateria($item['claveMateria']);
        }

        $_response['talleres'] = $result;
        $_response['talleresTomados'] = $_talleresTomados;
        $_response['talleresLlenos'] = $_talleresLlenos;
        // $_response['debug_talleres'] = $queryTalleres;
        unset($result);


        $queryAcademiasTomados = 'SELECT x.col_id AS ID, x.col_periodoid AS periodoID, y.* FROM tbl_academias x LEFT OUTER JOIN tbl_materias y ON y.col_id=x.col_materiaid WHERE x.col_alumnoid="'.$alumnoid.'" AND x.col_periodoid!="'.intval($periodoid).'"';
        $sthc = $this->db->prepare($queryAcademiasTomados);
        $sthc->execute();
        $todosTomadas = $sthc->fetchAll();
        foreach($todosTomadas as $item){
            $_pData = getPeriodo($item['periodoID'], $this->db, false);
            $academiasTomadas[] = strtoupper(claveMateria($item['col_clave']));
            $_academiasTomadas[] = array('id' => $item['ID'], 'clave' => claveMateria($item['col_clave']), 'nombre' => fixEncode($item['col_nombre']).' ('.$_pData->col_grado.'-'.$_pData->col_grupo.')');
        }


        $queryAcademias = "SELECT t.col_id, t.col_maestroid, t.col_materia_clave AS claveMateria, m.col_id AS materiaID, m.col_nombre AS nombreMateria FROM  tbl_maestros_taxonomia t LEFT OUTER JOIN tbl_materias m ON m.col_clave = t.col_materia_clave WHERE t.col_periodoid ='".intval($periodoid)."' AND t.col_materia_clave LIKE 'AC%' AND m.col_id AND m.col_semestre='".$periodoData->col_grado."' AND m.col_plan_estudios='".$periodoData->col_plan_estudios."' GROUP BY t.col_id";
        // echo $queryAcademias;exit;
        $sthc = $this->db->prepare($queryAcademias);
        $sthc->execute();
        $todos = $sthc->fetchAll();
        $i = 0;
        $agregadas = array();
        foreach($todos as $item){

            // $queryAcademiasInscritos = 'SELECT * FROM tbl_academias WHERE col_materiaid="'.intval($item['materiaID']).'" AND col_periodoid="'.intval($periodoid).'" GROUP BY col_alumnoid';
            $queryAcademiasInscritos = 'SELECT count(a.col_alumnoid) AS totalInscritos, m.col_nombre FROM tbl_academias a LEFT OUTER JOIN tbl_materias m ON m.col_id=a.col_materiaid WHERE m.col_clave LIKE  "'.substr($item['claveMateria'], 0, 6).'%" AND a.col_periodoid="'.$periodoid.'" GROUP BY SUBSTRING(m.col_clave, 1, 6)';
            $sthc = $this->db->prepare($queryAcademiasInscritos);
            $sthc->execute();
            $dataAcademiasInscritos = $sthc->fetch(PDO::FETCH_OBJ);
            // if($sthc->rowCount() == 0){
            if($dataAcademiasInscritos->totalInscritos <= $limiteAcademia && !in_array(strtoupper(substr($item['claveMateria'], 0, 6)), $academiasTomadas)){
                $result[$i]['value'] = $item['materiaID'];
                $result[$i]['label'] = fixEncode($item['nombreMateria']).' ('.$item['claveMateria'].')';
                $result[$i]['text'] = fixEncode($item['nombreMateria']).' ('.$item['claveMateria'].')';
                $i++;
            }


            if(!in_array(claveMateria($item['claveMateria']), $agregadas)) $_academiasLlenas[] = array('clave' => claveMateria($item['claveMateria']), 'nombre' => fixEncode($item['nombreMateria']), 'inscritos' => intval($dataAcademiasInscritos->totalInscritos));
            $agregadas[] = claveMateria($item['claveMateria']);
        }
        $_response['academiasTomadas'] = $_academiasTomadas;
        $_response['academiasLlenas'] = $_academiasLlenas;
        $_response['academias'] = $result;
        // $_response['debug_academias'] = $queryAcademias;

        return $this->response->withJson($_response);

    });

    $this->get('/listCarreras', function (Request $request, Response $response, array $args) {
        if(isset($_REQUEST['grupo'])) {
            $periodo = intval($_REQUEST['grupo']);
        }else if(isset($_REQUEST['periodo'])){
            $periodo = intval($_REQUEST['periodo']);
        }
        $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.$periodo.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $queryc = "SELECT * FROM tbl_carreras WHERE col_id='".$data->col_carreraid."' ORDER BY col_id";
        // $result['debug'] = $queryc;
        $sthc = $this->db->prepare($queryc);
        $sthc->execute();
        $todos = $sthc->fetchAll();

        $i = 0;
        $i = 0;
        foreach($todos as $item){
            $string = $item['col_nombre_largo'].' ('.$item['col_revoe'].')';
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = (preg_match('//u', $string) == 0?utf8_encode($string):$string);
            $result[$i]['text'] = (preg_match('//u', $string) == 0?utf8_encode($string):$string);
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listCarrerasValue', function (Request $request, Response $response, array $args) {

        $queryAskPeriodoSemestre = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.getPeriodoTaxoID($_REQUEST[periodo], $this->db).'" AND TRIM(col_grupo)="'.trim($_REQUEST[grupo]).'" AND col_grado="'.intval($_REQUEST[grado]).'"';
        $objPeriodoSemestre = $this->db->prepare($queryAskPeriodoSemestre);
        $objPeriodoSemestre->execute();
        $data = $objPeriodoSemestre->fetch(PDO::FETCH_OBJ);


        $queryc = "SELECT * FROM tbl_carreras WHERE col_id='".$data->col_carreraid."' ORDER BY col_id";
        $sthc = $this->db->prepare($queryc);
        $sthc->execute();
        $todos = $sthc->fetchAll();

        $i = 0;
        $i = 0;
        foreach($todos as $item){
            $string = $item['col_nombre_largo'].' ('.$item['col_revoe'].')';
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = (preg_match('//u', $string) == 0?utf8_encode($string):$string);
            $result[$i]['text'] = (preg_match('//u', $string) == 0?utf8_encode($string):$string);
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listAlumnosValue', function (Request $request, Response $response, array $args) {

        $queryAskPeriodoSemestre = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.getPeriodoTaxoID($_REQUEST[periodo], $this->db).'" AND TRIM(col_grupo)="'.trim($_REQUEST[grupo]).'" AND col_grado="'.intval($_REQUEST[grado]).'"';
        $objPeriodoSemestre = $this->db->prepare($queryAskPeriodoSemestre);
        $objPeriodoSemestre->execute();
        $data = $objPeriodoSemestre->fetch(PDO::FETCH_OBJ);

        $_REQUEST[periodo] = $data->col_id;

        $queryTaxonomia = "SELECT * FROM tbl_alumnos_taxonomia WHERE col_periodoid='".intval($_REQUEST[periodo])."' ORDER BY col_id ASC";
        $sth_taxo = $this->db->prepare($queryTaxonomia);
        $sth_taxo->execute();
        $data_taxo = $sth_taxo->fetchAll();
        foreach($data_taxo as $item_taxo){
            $alumnos[] = $item_taxo['col_alumnoid'];
        }

        $query = "SELECT a.*, IF(c.col_modalidad = 0, 'Semestral', 'Cuatrimestral') as modalidad, CONCAT(p.col_grado, '-', p.col_grupo) as grado FROM tbl_alumnos a ".
        "LEFT OUTER JOIN tbl_carreras c ON c.col_id=a.col_carrera ".
        "LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid ".
        "WHERE a.col_id IN (".implode(',', $alumnos).") AND a.col_carrera='".intval($_REQUEST[carrera])."' ".
        "ORDER BY col_nombres ASC";

        // $result['debug'] = $queryTaxonomia;
        // return $this->response->withJson($result);

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;

        foreach($todos as $item){
            $nombre = $item['col_nombres']." ".$item['col_apellidos'];
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['info'] = $item['modalidad'].', '.$item['grado'];
            $result[$i]['label'] = (preg_match('//u', $nombre) == 0?utf8_encode($nombre):$nombre);
            $result[$i]['text'] = (preg_match('//u', $nombre) == 0?utf8_encode($nombre):$nombre);
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listAlumnosByCarrera', function (Request $request, Response $response, array $args) {

        if(intval($_REQUEST['periodo']) > 0){
            $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($_REQUEST['periodo']).'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $data = $sth->fetch(PDO::FETCH_OBJ);

            $inscritos = array();
            $sth = $this->db->prepare("SELECT * FROM tbl_alumnos_taxonomia WHERE col_groupid='".$data->col_groupid."'");
            $sth->execute();
            $_inscritos = $sth->fetchAll();
            foreach($_inscritos as $item){
                $inscritos[] = $item['col_alumnoid'];
            }
            if(count($inscritos)){
                $query = "SELECT a.*, IF(c.col_modalidad = 0, 'Semestral', 'Cuatrimestral') as modalidad, CONCAT(p.col_grado, '-', p.col_grupo) as grado FROM tbl_alumnos a ".
                // "LEFT OUTER JOIN tbl_alumnos_taxonomia t ON t.col_alumnoid=a.col_id AND t.col_status=1 ".
                "LEFT OUTER JOIN tbl_carreras c ON c.col_id=a.col_carrera ".
                "LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid ".
                "WHERE a.col_carrera='".intval($_REQUEST[carrera])."' AND a.col_id NOT IN (".implode(',', $inscritos).") ".
                "ORDER BY col_nombres ASC";
            }else{
                $query = "SELECT a.*, IF(c.col_modalidad = 0, 'Semestral', 'Cuatrimestral') as modalidad, CONCAT(p.col_grado, '-', p.col_grupo) as grado FROM tbl_alumnos a ".
                // "LEFT OUTER JOIN tbl_alumnos_taxonomia t ON t.col_alumnoid=a.col_id AND t.col_status=1 ".
                "LEFT OUTER JOIN tbl_carreras c ON c.col_id=a.col_carrera ".
                "LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid ".
                "WHERE a.col_carrera='".intval($_REQUEST[carrera])."' ".
                "ORDER BY col_nombres ASC";
            }


            // exit;

        }else {

            $query = "SELECT a.*, IF(c.col_modalidad = 0, 'Semestral', 'Cuatrimestral') as modalidad, CONCAT(p.col_grado, '-', p.col_grupo) as grado FROM tbl_alumnos a ".
            // "LEFT OUTER JOIN tbl_alumnos_taxonomia t ON t.col_alumnoid=a.col_id AND t.col_status=1 ".
            "LEFT OUTER JOIN tbl_carreras c ON c.col_id=a.col_carrera ".
            "LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid ".
            "WHERE a.col_carrera='".intval($_REQUEST[carrera])."' ".
            "ORDER BY col_nombres ASC";
        }


        // $result['debug'] = $query;
        // return $this->response->withJson($result);

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;

        foreach($todos as $item){
            $nombre = $item['col_nombres']." ".$item['col_apellidos'];
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['info'] = $item['modalidad'].', '.$item['grado'];
            $result[$i]['label'] = (preg_match('//u', $nombre) == 0?utf8_encode($nombre):$nombre);
            $result[$i]['text'] = (preg_match('//u', $nombre) == 0?utf8_encode($nombre):$nombre);
            $i++;
        }

        return $this->response->withJson($result);

    });


    $this->get('/listAlumnos', function (Request $request, Response $response, array $args) {

        $sth_taxo = $this->db->prepare("SELECT * FROM tbl_alumnos_taxonomia WHERE col_periodoid='".intval($_REQUEST[periodo])."' ORDER BY col_id ASC");
        $sth_taxo->execute();
        $data_taxo = $sth_taxo->fetchAll();
        foreach($data_taxo as $item_taxo){
            $alumnos[] = $item_taxo['col_alumnoid'];
        }

        $query = "SELECT a.*, CONCAT(p.col_grado, '-', p.col_grupo) as grado FROM tbl_alumnos a ".
        // "LEFT OUTER JOIN tbl_alumnos_taxonomia t ON t.col_alumnoid=a.col_id AND t.col_status=1 ".
        "LEFT OUTER JOIN tbl_carreras c ON c.col_id=a.col_carrera ".
        "LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid ".
        "WHERE a.col_id IN (".implode(',', $alumnos).") AND a.col_carrera='".intval($_REQUEST[carrera])."' ".
        "ORDER BY col_nombres ASC";

        // $result['debug'] = $query;
        // return $this->response->withJson($result);


        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;

        foreach($todos as $item){
            $carreraData = getCarrera($item['col_carrera'], $this->db);

            $nombre = $item['col_nombres']." ".$item['col_apellidos'];
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['info'] = $carreraData['modalidad'].', '.$item['grado'];
            $result[$i]['label'] = (preg_match('//u', $nombre) == 0?utf8_encode($nombre):$nombre);
            $result[$i]['text'] = (preg_match('//u', $nombre) == 0?utf8_encode($nombre):$nombre);
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listInscritos', function (Request $request, Response $response, array $args) {

        $dataPeriodo = getPeriodo(intval($_REQUEST['periodo']), $this->db, false);

        /*
        $query = "SELECT tax.col_id AS id, a.*, a.col_periodoid AS periodoAlumno, p.col_grado AS semestre, p.col_grupo AS grupo FROM tbl_alumnos_taxonomia tax ".
        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=tax.col_alumnoid ".
        "LEFT OUTER JOIN tbl_periodos p ON p.col_id=tax.col_periodoid ".
        "WHERE tax.col_groupid='".getPeriodoTaxoID(intval($_REQUEST['periodo']), $this->db)."' ORDER BY semestre ASC";
        */

        $query = "SELECT tax.col_id AS id, a.*, a.col_estatus AS estatusAlumno, a.col_periodoid AS periodoAlumno, p.col_grado AS semestre, p.col_grupo AS grupo FROM tbl_alumnos_taxonomia tax ".
        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=tax.col_alumnoid ".
        "LEFT OUTER JOIN tbl_periodos p ON p.col_id=tax.col_periodoid ".
        "WHERE tax.col_periodoid='".intval($_REQUEST['periodo'])."' AND a.col_nombres!='' ORDER BY a.col_apellidos ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;

        foreach($todos as $item){
            $carrera = getCarrera($item['col_carrera'], $this->db);
            $dataPeriodoAlumno = getPeriodo(intval($item['periodoAlumno']), $this->db, false);
            if($carrera['modalidad_numero'] == $dataPeriodo->col_modalidad) {
                $nombre = $item['col_apellidos']." ".$item['col_nombres'];
                $result[$i]['id'] = intval($item['id']);
                if($item['estatusAlumno'] != 'activo'){
                    $result[$i]['alumno'] = fixEncode($nombre).' <span class="badge badge-info">INACTIVO</span>';
                }else{
                    $result[$i]['alumno'] = fixEncode($nombre);
                }
                $result[$i]['semestre'] = fixEncode($dataPeriodoAlumno->col_grado);
                $result[$i]['grupo'] = fixEncode($dataPeriodoAlumno->col_grupo);
                $result[$i]['carrera'] = fixEncode($carrera['nombre']);

                $result[$i]['debug'] = $carrera['modalidad_numero'];
                $result[$i]['debug2'] = $item['modalidadPeriodo'];

                $i++;
            }
        }

        return $this->response->withJson($result);

    });

    $this->get('/getAlumnoPeriodo', function (Request $request, Response $response, array $args) {

        $query = "SELECT ca.col_nombre_largo AS carrera_nombre, pe.col_nombre AS periodo_nombre, tax.*, tl.col_materiaid AS col_tallerid, ac.col_materiaid AS col_academiaid, pe.col_grado, pe.col_grupo, pe.col_carreraid FROM tbl_alumnos_taxonomia tax ".
        "LEFT OUTER JOIN tbl_periodos pe ON pe.col_id=tax.col_periodoid ".
        "LEFT OUTER JOIN tbl_carreras ca ON ca.col_id=pe.col_carreraid ".
        "LEFT OUTER JOIN tbl_academias ac ON ac.col_alumnoid=tax.col_alumnoid AND ac.col_periodoid=tax.col_periodoid ".
        "LEFT OUTER JOIN tbl_talleres tl ON tl.col_alumnoid=tax.col_alumnoid AND tl.col_periodoid=tax.col_periodoid ".
        "WHERE tax.col_id='".intval($_REQUEST['taxonomia'])."'";

        $sth = $this->db->prepare($query);
        $sth->execute();
        //$todos = $sth->fetchAll();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $carreraData = getCarrera($data->col_carreraid, $this->db);

        $data->carrera_nombre = fixEncode($data->carrera_nombre);
        $data->periodo_nombre = fixEncode($data->periodo_nombre);
        $data->grupo = $data->col_grado.'-'.$data->col_grupo;
        $data->modalidad = fixEncode($carreraData['modalidad']);

        return $this->response->withJson($data);

    });

    $this->get('/listAlumnosByPeriodos', function (Request $request, Response $response, array $args) {

        $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.$_REQUEST['periodo'].'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $pe_data = $sth->fetch(PDO::FETCH_OBJ);
        $taxid = $pe_data->col_groupid;

        $query = 'SELECT col_id, col_grupo FROM tbl_periodos WHERE col_groupid="'.$taxid.'" AND col_grado="'.$pe_data->col_grado.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $grupos = $sth->fetchAll();

/*
        $query = "SELECT ca.col_nombre_largo AS col_nombre_carrera, a.col_id AS alumnoid, tax.col_id, a.col_nombres, a.col_apellidos, a.col_periodoid, pe.col_grado AS semestre, pe.col_grupo AS grupo, pe.col_carreraid FROM tbl_alumnos_taxonomia tax  ".
        "LEFT OUTER JOIN tbl_periodos pe ON pe.col_id=tax.col_periodoid ".
        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=tax.col_alumnoid ".
        "LEFT OUTER JOIN tbl_carreras ca ON ca.col_id=a.col_carrera WHERE tax.col_groupid='".intval($taxid)."'";
*/
        $query = "SELECT ca.col_nombre_largo AS col_nombre_carrera, a.col_id AS alumnoid, tax.col_id, a.col_nombres, a.col_apellidos, a.col_periodoid, pe.col_grado AS semestre, pe.col_grupo AS grupo, pe.col_carreraid FROM tbl_alumnos_taxonomia tax  ".
        "LEFT OUTER JOIN tbl_periodos pe ON pe.col_id=tax.col_periodoid ".
        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=tax.col_alumnoid ".
        "LEFT OUTER JOIN tbl_carreras ca ON ca.col_id=a.col_carrera WHERE tax.col_periodoid='".intval($_REQUEST['periodo'])."' AND a.col_estatus='activo'";

        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetchAll();
        $i = 0;
        foreach($data as $item) {
            $_result[$i]['alumno'] = fixEncode($item['col_nombres']." ".$item['col_apellidos']);
            $_result[$i]['carrera'] = fixEncode($item['col_nombre_carrera']);
            $_result[$i]['semestre'] = $item['semestre'];
            $_result[$i]['grupo'] = $item['grupo'];
            $_result[$i]['col_taxid'] = $item['col_id'];
            $_result[$i]['col_alumnoid'] = $item['alumnoid'];

            $i++;
        }
        $_response['periodo_nombre'] = $pe_data->col_nombre;
        $_response['list'] = $_result;
        $_response['grupos'] = $grupos;
        $_response['groupid'] = $taxid;
        return $this->response->withJson($_response);

    });

});
// routes.alumnos.php
