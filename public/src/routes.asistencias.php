<?php

/**
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de asistencias para maestros y administrativos.
 *
 * Lista de funciones
 *
 * /asistencias
 *  - /delete
 *  - /listReport
 *  - /listAlumnos
 *  - /listAlumnosEdit
 *  - /listFechas
 *  - /addAsistencia
 *  - /get
 *  - /update
 *  - /updateLista
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/asistencias', function () {

    $this->delete('/delete', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_asistencia WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_asistencia', '', '', 'Asistencias', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        $query = 'DELETE FROM tbl_asistencia_alumnos WHERE col_listaid="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_asistencia_alumnos', '', '', 'Asistencias', $this->db);
        $dblog->where = array('col_listaid' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->get('/listReport', function (Request $request, Response $response, array $args) {
        global $uploaddir, $download_url;

         $userDepto = getCurrentUserDepto();
         $userType = getCurrentUserType(); // maestro - administrativo - alumno
         $userID = getCurrentUserID();
         $periodoAlumnoID = getCurrentAlumnoPeriodoID($this->db);



        $start = (isset($_REQUEST['_page'])?intval($_REQUEST['_page']):1);
        $limit = (isset($_REQUEST['_limit'])?intval($_REQUEST['_limit']):20);
        if($start == 1) $start = 0;
        if($start > 1) $start = ($start * $limit) - $limit;

        $sort = 'ID';
        $order = 'DESC';
        if(isset($_REQUEST['_order'])) $order = (trim($_REQUEST['_order']) != 'ASC'?'DESC':'ASC');

        if(isset($_REQUEST['_sort'])){
            switch(trim($_REQUEST['_sort'])) {
                case 'maestro': $sort = 'u.col_lastname'; break;
                case 'fecha': $sort = 'a.col_fecha'; break;
                case 'grupo': $sort = 'p.col_grado, p.col_grupo'; break;
                case 'materia': $sort = 'm.col_nombre'; break;
            }
        }

        if(isset($_REQUEST['maestro_like'])) $where[] = "CONCAT(u.col_firstname, ' ', u.col_lastname) LIKE '%".addslashes(trim($_REQUEST['maestro_like']))."%'";
        if(isset($_REQUEST['fecha_like'])) {
            foreach(explode(' ', trim($_REQUEST['fecha_like'])) as $word){
                $whereFecha[] = "DATE_FORMAT(a.col_fecha, '%Y %M %d') LIKE '%".addslashes(strtolower($word))."%'";
            }
            if(count($whereFecha) > 0){
                $where[] = "(".implode(' AND ', $whereFecha).")";
            }
        }
        $where[] = " a.col_fecha BETWEEN '".$_REQUEST['dateFrom']."' AND '".$_REQUEST['dateTo']."' ";
        if(isset($_REQUEST['grupo_like'])) $where[] = "CONCAT(p.col_grado, '-', p.col_grupo) LIKE '%".addslashes(trim($_REQUEST['grupo_like']))."%'";
        if(isset($_REQUEST['materia_like'])) $where[] = "m.col_nombre LIKE '%".addslashes(trim($_REQUEST['materia_like']))."%'";


        $sth = $this->db->prepare("SET lc_time_names = 'es_ES'");
        $sth->execute();

        $queryAsistencias = "SELECT SQL_CALC_FOUND_ROWS mt.col_materia_clave AS materiaClave, a.col_id AS ID, CONCAT(u.col_firstname, ' ', u.col_lastname) AS nombreMaestro, "
        ."a.col_fecha AS fecha, m.col_nombre AS materiaNombre, CONCAT(p.col_grado, '-', p.col_grupo) AS grupo, DATE_FORMAT(a.col_fecha, '%Y-%M-%d') AS fechaTexto, a.col_sesion AS sesion FROM tbl_asistencia a "
        ."LEFT OUTER JOIN tbl_maestros_taxonomia mt ON mt.col_id=a.col_materiaid "
        ."LEFT OUTER JOIN tbl_periodos p ON p.col_id=mt.col_periodoid "
        ."LEFT OUTER JOIN tbl_materias m ON m.col_clave=mt.col_materia_clave AND m.col_semestre=p.col_grado AND m.col_plan_estudios=p.col_plan_estudios "
        ."LEFT OUTER JOIN tbl_users u ON u.col_id=a.col_maestroid ".(count($where) > 0?'WHERE '.implode(' AND ', $where):'')." GROUP BY a.col_id ORDER BY ".$sort." ".$order;

        $sth = $this->db->prepare($queryAsistencias.' LIMIT '.$start.','.$limit);
        $sth->execute();
        $asistencias = $sth->fetchAll();

        $sth = $this->db->prepare("SELECT FOUND_ROWS() as total");
        $sth->execute();
        $totalAsistencias = $sth->fetch(PDO::FETCH_OBJ);
        $totalAsistencias = $totalAsistencias->total;




        // $sth = $this->db->prepare("SELECT mt.col_materia_clave AS materiaClave, a.col_id AS ID, CONCAT(u.col_firstname, ' ', u.col_lastname) AS nombreMaestro, a.col_fecha AS fecha, m.col_nombre AS materiaNombre, CONCAT(p.col_grado, '-', p.col_grupo) AS grupo FROM tbl_asistencia a
        // LEFT OUTER JOIN tbl_maestros_taxonomia mt ON mt.col_id=a.col_materiaid
        // LEFT OUTER JOIN tbl_periodos p ON p.col_id=mt.col_periodoid
        // LEFT OUTER JOIN tbl_materias m ON m.col_clave=mt.col_materia_clave AND m.col_semestre=p.col_grado AND m.col_plan_estudios=p.col_plan_estudios
        // LEFT OUTER JOIN tbl_users u ON u.col_id=a.col_maestroid
        // GROUP BY a.col_id ORDER BY ID DESC LIMIT 0,1000");
        // $sth->execute();
        // $asistencias = $sth->fetchAll();
        // $totalAsistencias = $sth->rowCount();

        $i = 0;
        foreach($asistencias as $item) {
            $result[$i]['id'] = $item['ID'];
            if($item['materiaNombre'] == ''){
                $query = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($item['materiaClave']).'%" LIMIT 1';
                $sth = $this->db->prepare($query);
                $sth->execute();
                $data = $sth->fetch(PDO::FETCH_OBJ);
                $item['materiaNombre'] = $data->col_nombre;
            }

            $result[$i]['maestro'] = fixEncode($item['nombreMaestro']);
            $result[$i]['fecha'] = fechaTexto($item['fecha']);
            $result[$i]['fechaTexto'] = ($item['fechaTexto']);
            $result[$i]['sesion'] = ($item['sesion']);
            $result[$i]['materia'] =  fixEncode($item['materiaNombre']);
            $result[$i]['grupo'] = $item['grupo'];
            $result[$i]['opciones'] = '<div style="text-align: center;"><a class="btn btn-xs btn-primary" href="#/pages/reporte-asistencias/lista/'.$item['ID'].'"><i class="fas fa-check-double"></i> Ver Lista</a></div>';
            $i++;
        }


        $_response['list'] = $result;
        $_response['total'] = $totalAsistencias;
        // $_response['debug'] = $queryAsistencias;
        // $_response['debug'] = $_REQUEST['dateFrom'];

        // return $response->withStatus(200)->withJson($_response);
        return $this->response->withJson($_response);

    });

    $this->get('/listAlumnos', function (Request $request, Response $response, array $args) {
        global $alertaAsistencias;

        $sesion = intval($_REQUEST['sesion']);
        $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_id="'.intval($_REQUEST['taxonomiaMateria']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_maestros WHERE col_userid="'.$data->col_maestroid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMaestro = $sth->fetch(PDO::FETCH_OBJ);

        $periodoData = getPeriodo($data->col_periodoid, $this->db, false);
        $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.trim($data->col_materia_clave).'" AND col_semestre="'.$periodoData->col_grado.'" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMateria = $sth->fetch(PDO::FETCH_OBJ);
        $materiaid = $dataMateria->col_id;


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

        // echo strtoupper(strpos($dataMateria->col_clave, 0, 2))."<br/>";
        // echo $query;
        // exit;

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;

        $query = 'SELECT * FROM tbl_asistencia WHERE col_materiaid="'.intval($_REQUEST['taxonomiaMateria']).'" AND col_maestroid="'.intval($_REQUEST['maestro']).'" AND col_fecha="'.date('Y-m-d').'" AND col_sesion="'.$sesion.'"';
        // $_respuesta['lafecha_query'] = $query;
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataCheck = $sth->fetch(PDO::FETCH_OBJ);
        if($_REQUEST['fecha'] == 'today' && $sth->rowCount() > 0){
            $_REQUEST['fecha'] = $dataCheck->col_id;
        }

        $observaciones = '';
        if($_REQUEST['fecha'] != 'today'){
            $query_obsrvaciones = 'SELECT * FROM tbl_asistencia WHERE col_id="'.intval($_REQUEST['fecha']).'"';
            $sth_ob = $this->db->prepare($query_obsrvaciones);
            $sth_ob->execute();
            $data_obs = $sth_ob->fetch(PDO::FETCH_OBJ);
            $observaciones = $data_obs->col_observaciones;
        }

        // $query = 'SELECT * FROM tbl_config WHERE col_id=1';
        // $sth = $this->db->prepare($query);
        // $sth->execute();
        // $config = $sth->fetch(PDO::FETCH_OBJ);



        foreach($todos as $item){
            // echo $dataMateria->col_id;
            usleep(1000);

            if($_REQUEST['fecha'] != 'today'){
                $acredita = acreditaPresentarByActividad($this->db, $item['col_id'], 0, $dataMateria->col_id, $data_obs->col_fecha);

                // list($rangoFechaInicio, $rangoFechaFin) = getRangoFechas($this->db, $item['col_id'], $data->col_periodoid, $dataMateria->col_id, $data_obs->col_fecha);
                // $_asistenciasAlumno = getAsistenciasByAlumnoAndMateria($item['col_id'], $this->db, $rangoFechaInicio, $data_obs->col_fecha, $dataMateria->col_id);
                $_asistenciasAlumno = get_AsistenciasByAlumnoAndMateria($item['col_id'], $this->db, $data_obs->col_fecha, intval($_REQUEST['taxonomiaMateria']));
            }else{
                $acredita = acreditaPresentarByActividad($this->db, $item['col_id'], 0, $dataMateria->col_id, date('Y-m-d', strtotime('now')));
                // list($rangoFechaInicio, $rangoFechaFin) = getRangoFechas($this->db, $item['col_id'], $data->col_periodoid, $dataMateria->col_id, date('Y-m-d', strtotime('now')));
                // $_asistenciasAlumno = getAsistenciasByAlumnoAndMateria($item['col_id'], $this->db, $rangoFechaInicio, date('Y-m-d', strtotime('now')), $dataMateria->col_id);
                $_asistenciasAlumno = get_AsistenciasByAlumnoAndMateria($item['col_id'], $this->db, '', intval($_REQUEST['taxonomiaMateria']));
            }

            // echo $dataMateria->col_id;exit;
            // print_r($_asistenciasAlumno);exit;


            // $_asistenciasAlumno = getAsistenciasByAlumnoAndMateria($item['col_id'], $this->db, $rangoFechaInicio, $rangoFechaFin, $dataMateria->col_id);
            $asistenciasAlumno = $_asistenciasAlumno[$dataMateria->col_id];
            // print_r($asistencias);
            $_response[$i]['debugAsistenciasMatID'] = $dataMateria->col_id;
            $_response[$i]['debugAsistenciasFull'] = $_asistenciasAlumno;
            $_response[$i]['debugAsistencias'] = $_asistenciasAlumno[intval($dataMateria->col_id)];
            // $_respuesta['debugFecha'] = $rangoFechaInicio.'----'.$rangoFechaFin;
            // $_response[$i]['debugAsistencias'] = $_asistenciasAlumno;
            if($asistenciasAlumno['porcentaje'] > 1) $css = 'text-warning';
            if($asistenciasAlumno['porcentaje'] > $alertaAsistencias) $css = 'text-danger';
            if($asistenciasAlumno['porcentaje'] < 1) $css = '';



            $carrera = getCarrera($item['col_carrera'], $this->db);
            $_response[$i]['col_id'] = $item['col_id'];
            $_response[$i]['col_nombre'] = fixEncode(trim($item['col_apellidos']).' '.trim($item['col_nombres']));
            $_response[$i]['carrera'] = $carrera['nombre'];
            $_response[$i]['alertaCSS'] = $css;
            $_response[$i]['alertaTexto'] = ($acredita['status'] == 'sin-derecho'?'<b>Sin derecho a examen por:</b> '.$acredita['reason'].'<br/>':'').'El alumno tiene '.intval($asistenciasAlumno['faltas']).' falta(s) ('.number_format($asistenciasAlumno['porcentaje_asistencias'], 2).'%)';
            $_response[$i]['lasAsistencias'] = $asistenciasAlumno;
            $_response[$i]['isAllowed'] = puedeCursarMateria($item['col_id'], intval($_REQUEST['taxonomiaMateria']), $dataMateria->col_id, $this->db);

            if($_REQUEST['fecha'] != 'today'){
                $_respuesta['listID'] = intval($_REQUEST['fecha']);
                $query_history = 'SELECT * FROM tbl_asistencia_alumnos WHERE col_listaid="'.intval($_REQUEST['fecha']).'" AND col_alumnoid="'.$item['col_id'].'"';

                $sth_hi = $this->db->prepare($query_history);
                $sth_hi->execute();
                $history = $sth_hi->fetch(PDO::FETCH_OBJ);

                $_response[$i]['asistencia'] = $history->col_asistencia;
                $_response[$i]['participacion'] = $history->col_participacion;
                $_response[$i]['segunda'] = $history->col_segunda;
                $_response[$i]['comportamiento'] = $history->col_comportamiento;
                $_response[$i]['justificacion'] = $history->col_justificacion;


            }
            $i++;
        }

        $allowed = 'false';
        $allowAsistencia = 'true';
        $horasClase = 0;

        $diaActual = date('N');
        if($_REQUEST['fecha'] != 'today'){
            $diaActual = date('N', strtotime($data_obs->col_fecha));
        }
        // $diaActual = 2;
        $diasCols = array(1 => 'col_lunes', 2 => 'col_martes', 3 => 'col_miercoles', 4 => 'col_jueves', 5 => 'col_viernes', 6 => 'col_sabado', 7 => 'col_domingo');
        $query = 'SELECT '.$diasCols[$diaActual].' AS dia FROM tbl_horarios WHERE col_periodoid="'.$data->col_periodoid.'" AND col_materiaid="'.intval($dataMateria->col_id).'"';
        $sth_ob = $this->db->prepare($query);
        $sth_ob->execute();
        $horario = $sth_ob->fetch(PDO::FETCH_OBJ);
        if(trim($horario->dia) != '' && strlen($horario->dia) > 0) {
            $allowed = 'true';
            $_h1 = explode('-', $horario->dia);
            $horaInicio = $_h1[0];
            $horaFin = $_h1[1];
            $_h2 = intval(substr($_h1[0], 0, 2)); // Inicio
            $_h3 = intval(substr($_h1[1], 0, 2)); // Fin
            $horasClase = ($_h3 - $_h2);

            $horaActual = date('G');
            // $horaActual = 7;
            if($horaActual < $_h2 || $horaActual > $_h3){
                $allowed = 'false';
            }

            if(strtotime('now') > strtotime(date('Y-m-d').' '.$_h2.':15:00')){
                $_respuesta['allowAsistencia'] = 'false';
                // $_respuesta['xdebug'] = 1;
            }

        }


        $_respuesta['listAlumnos'] = $_response;
        $_respuesta['totalAlumnos'] = count($_response);
        $_respuesta['observaciones'] = $observaciones;
        $_respuesta['horas'] = $horasClase;
        $_respuesta['allow'] = $allowed;
        $_respuesta['horario'] = $horario->dia;
        $_respuesta['periodo'] = $data->col_periodoid;
        $_respuesta['materia'] = $dataMateria->col_id;
        // $_respuesta['allowAsistencia'] = 'false';
        // $_respuesta['debug'] = strtotime(date('Y-m-d').' '.$_h2.':15:00');
        $_respuesta['debug'] = date('Y-m-d H:i:s');

        $_respuesta['_allow'] = $_respuesta['allow'];
        $_respuesta['_allowAsistencia'] = $_respuesta['allowAsistencia'];
        $_respuesta['lafecha'] = $_REQUEST['fecha'];

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $r = $c->fetch(PDO::FETCH_OBJ);
        $diasTranscurridos = 0;
        if($data_obs->col_fecha) {
            $diasTranscurridos = countDays(strtotime($data_obs->col_fecha));
        }

        $_respuesta['diasTranscurridos'] = $diasTranscurridos;
        if($diasTranscurridos > 3){
            $_respuesta['allow'] = 'false';
            $_respuesta['allowAsistencia'] = 'false';
            //$_respuesta['xdebug'] = 2;

            $queryCheck = 'SELECT * FROM tbl_asistencia WHERE col_fecha>"'.$data_obs->col_fecha.'" AND col_materiaid="'.intval($data_obs->col_materiaid).'" AND col_maestroid="'.intval($data_obs->col_maestroid).'"';
            $sth_ch = $this->db->prepare($queryCheck);
            $sth_ch->execute();
            if($sth_ch->rowCount() <= 1){
                $_respuesta['allow'] = 'true';
            }

        }else{
            $_respuesta['allow'] = 'true';
            $_respuesta['allowAsistencia'] = 'false';
           // $_respuesta['xdebug'] = 3;
        }

        if($r->col_candados_asistencias == 0 || $dataMaestro->col_edit_asistencias == 1) {
            $_respuesta['allow'] = 'true';
            //$_respuesta['xdebug'] = 4;
            $_respuesta['allowAsistencia'] = 'true';
        }


        return $this->response->withJson($_respuesta);

    });

    $this->get('/listAlumnosEdit', function (Request $request, Response $response, array $args) {


        $query = 'SELECT * FROM tbl_asistencia WHERE col_id="'.intval($_REQUEST['lista']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);
        $observaciones = $data->col_observaciones;
        $fechaLista = fechaTexto($data->col_fecha);
        $fechaLista_str = $data->col_fecha;


        $sesion = $data->col_sesion;

        $query = 'SELECT * FROM tbl_users WHERE col_id="'.intval($data->col_maestroid).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMaestro = $sth->fetch(PDO::FETCH_OBJ);
        $maestroID = $dataMaestro->col_id;
        $nombreMaestro = fixEncode($dataMaestro->col_firstname.' '.$dataMaestro->col_lastname);


       $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_id="'.intval($data->col_materiaid).'"';
       //exit;
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $periodoData = getPeriodo($data->col_periodoid, $this->db, false);
        $grupo = fixEncode($periodoData->col_grado.'-'.$periodoData->col_grupo);

        $posgrado = 0;
        if($periodoData->col_modalidad == 3 || $periodoData->col_modalidad == 4){
            $posgrado = 1;
        }

        $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.trim($data->col_materia_clave).'" AND col_semestre="'.$periodoData->col_grado.'" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMateria = $sth->fetch(PDO::FETCH_OBJ);
        $nombreMateria = fixEncode($dataMateria->col_nombre);

        if(strtoupper(substr($dataMateria->col_clave, 0, 2)) == 'AC'){
            $_laClave = claveMateria($dataMateria->col_clave);
            // $queryx = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($data->col_materia_clave).'%" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
            $queryx = 'SELECT m.* FROM tbl_materias m LEFT OUTER JOIN tbl_maestros_taxonomia t ON t.col_materia_clave=m.col_clave WHERE t.col_maestroid="'.$maestroID.'" AND m.col_clave LIKE "'.$_laClave.'%" AND m.col_carrera="'.$periodoData->col_carreraid.'" AND m.col_plan_estudios="'.$periodoData->col_plan_estudios.'"';

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
            "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_academias ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
            "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";

        }else if(strtoupper(substr($dataMateria->col_clave, 0, 2)) == 'TL'){
            $_laClave = claveMateria($dataMateria->col_clave);
            $queryx = 'SELECT m.* FROM tbl_materias m LEFT OUTER JOIN tbl_maestros_taxonomia t ON t.col_materia_clave=m.col_clave WHERE t.col_maestroid="'.$maestroID.'" AND m.col_clave LIKE "'.$_laClave.'%" AND m.col_carrera="'.$periodoData->col_carreraid.'" AND m.col_plan_estudios="'.$periodoData->col_plan_estudios.'"';

            // $queryx = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($data->col_materia_clave).'%" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
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


        // echo strtoupper(strpos($dataMateria->col_clave, 0, 2))."<br/>";
        // echo $query;
        // exit;
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;

        foreach($todos as $item){
            $carrera = getCarrera($item['col_carrera'], $this->db);
            $_response[$i]['col_id'] = $item['col_id'];
            $_response[$i]['col_nombre'] = fixEncode(trim($item['col_apellidos']).' '.trim($item['col_nombres']));
            $_response[$i]['carrera'] = $carrera['nombre'];

            $query_history = 'SELECT * FROM tbl_asistencia_alumnos WHERE col_listaid="'.intval($_REQUEST['lista']).'" AND col_alumnoid="'.$item['col_id'].'"';
            $sth_hi = $this->db->prepare($query_history);
            $sth_hi->execute();
            $history = $sth_hi->fetch(PDO::FETCH_OBJ);

            $_response[$i]['asistencia'] = $history->col_asistencia;
            $_response[$i]['participacion'] = $history->col_participacion;
            $_response[$i]['segunda'] = $history->col_segunda;
            $_response[$i]['comportamiento'] = $history->col_comportamiento;
            $_response[$i]['justificacion'] = $history->col_justificacion;

            $i++;
        }

        $allowed = 'false';
        $allowAsistencia = 'true';
        $horasClase = 0;
        $diaActual = date('N', strtotime($fechaLista_str));
        if($posgrado == 0){
            // $diaActual = 2;
            $diasCols = array(1 => 'col_lunes', 2 => 'col_martes', 3 => 'col_miercoles', 4 => 'col_jueves', 5 => 'col_viernes', 6 => 'col_sabado', 7 => 'col_domingo');
            $query = 'SELECT '.$diasCols[$diaActual].' AS dia FROM tbl_horarios WHERE col_periodoid="'.$data->col_periodoid.'" AND col_materiaid="'.intval($dataMateria->col_id).'"';
            $sth_ob = $this->db->prepare($query);
            $sth_ob->execute();
            $horario = $sth_ob->fetch(PDO::FETCH_OBJ);
            if(trim($horario->dia) != '' && strlen($horario->dia) > 0) {
                $allowed = 'true';
                $_h1 = explode('-', $horario->dia);
                $horaInicio = $_h1[0];
                $horaFin = $_h1[1];
                $_h2 = intval(substr($_h1[0], 0, 2)); // Inicio
                $_h3 = intval(substr($_h1[1], 0, 2)); // Fin
                $horasClase = ($_h3 - $_h2);

                $horaActual = date('G');
                // $horaActual = 19;
                if($horaActual < $_h2 || $horaActual > $_h3){
                    $allowed = 'false';
                }

                if(strtotime('now') > strtotime(date('Y-m-d').' '.$_h2.':15:00')){
                    $allowAsistencia = 'false';
                }

            }
            $elHorario = $horario->dia;
        }

        if($posgrado == 1) {
            $elHorario = '';
            $_elHorario = Array();
            $diasshort = array(0 => 'LU', 1 => 'MA', 2 => 'MI', 3 => 'JU', 4 => 'VI', 5 => 'SA', 6 => 'DO');
            if($sesion == 1){
                $query = 'SELECT * FROM tbl_horarios_posgrados WHERE col_dia="'.($diaActual - 1).'" AND col_periodoid="'.$data->col_periodoid.'" AND col_materiaid="'.intval($dataMateria->col_id).'" ORDER BY col_hora_inicio ASC LIMIT 1';
            }else{
                $query = 'SELECT * FROM tbl_horarios_posgrados WHERE col_dia="'.($diaActual - 1).'" AND col_periodoid="'.$data->col_periodoid.'" AND col_materiaid="'.intval($dataMateria->col_id).'" ORDER BY col_hora_inicio DESC LIMIT 1';
            }

            $sth_ob = $this->db->prepare($query);
            $sth_ob->execute();
            $todosDias = $sth_ob->fetchAll();
            foreach($todosDias as $item){
                $_elHorario[] = $item['col_hora_inicio'].'-'.$item['col_hora_fin'];
            }

            $elHorario = implode(', ', $_elHorario);
        }

        $dias = array(1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo');
        //$_respuesta['debug'] = $query;
        $_respuesta['listAlumnos'] = $_response;
        $_respuesta['observaciones'] = $observaciones;
        $_respuesta['horas'] = $horasClase;
        $_respuesta['allow'] = $allowed;
        $_respuesta['horario'] = $elHorario;
        $_respuesta['periodo'] = $data->col_periodoid;
        $_respuesta['materia'] = $dataMateria->col_id;
        $_respuesta['maestro'] = $maestroID;
        $_respuesta['posgrado'] = $posgrado;
        $_respuesta['sesion'] = $sesion;

        $_respuesta['nombreMateria'] = $nombreMateria;
        $_respuesta['grupo'] = $grupo;
        $_respuesta['nombreMaestro'] = $nombreMaestro;
        $_respuesta['fechaLista'] = $fechaLista;
        $_respuesta['diaClase'] = $dias[date('N', strtotime($fechaLista_str))];
        $_respuesta['horarioClase'] = $elHorario;



        return $this->response->withJson($_respuesta);

    });


    $this->get('/listFechas', function (Request $request, Response $response, array $args) {


        $query = 'SELECT * FROM tbl_asistencia WHERE col_materiaid="'.intval($_REQUEST['taxonomiaMateria']).'" AND col_maestroid="'.intval($_REQUEST['maestro']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        foreach($todos as $item){

            $_response[$i]['col_id'] = $item['col_id'];
            $_response[$i]['fecha'] = fechaTexto($item['col_fecha']);
            $_response[$i]['fecharaw'] = $item['col_fecha'];
            $_response[$i]['sesion'] = $item['col_sesion'];
            if($item['col_fecha'] == date('Y-m-d')) {
                $_response[$i]['hoy'] = 1;
            }else{
                $_response[$i]['hoy'] = 0;
            }
            $i++;
        }
        return $this->response->withJson($_response);

    });

    $this->post('/addAsistencia', function (Request $request, Response $response, array $args) {
        global $dblog;

        $input = json_decode($request->getBody());
        $_response['status'] = 'true';
        $fecha = substr($input->fecha[0], 0, 10);

        $query = 'SELECT * FROM tbl_asistencia WHERE col_materiaid="'.intval($input->taxonomiaMateria).'" AND col_maestroid="'.intval($input->maestroid).'" AND col_fecha="'.$fecha.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();

        if($sth->rowCount() == 0){

            $data_insert = array(
                "col_materiaid" => intval($input->taxonomiaMateria), // col_id tbl_maestros_taxonomia
                "col_maestroid" => intval($input->maestroid),
                "col_fecha"     => $fecha,
                "col_observaciones" => '',
                "col_created_at" => date("Y-m-d H:i:s"),
                "col_created_by" => $input->maestroid,
                "col_updated_at" => date("Y-m-d H:i:s"),
                "col_updated_by" => $input->maestroid,
            );

            $query = 'INSERT INTO tbl_asistencia ('.implode(",", array_keys($data_insert)).')
            VALUES("'.implode('", "', array_values($data_insert)).'")';

            $dblog = new DBLog($query, 'tbl_asistencia', '', '', 'Asistencias', $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();
        }else{
            $_response['status'] = 'false';
        }


        return $this->response->withJson($_response);

    });

    $this->post('/get', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval($input['params']['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson($data);

    });

    $this->put('/update', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());
        $asistenciaid = 0;
        // $input = $request->getParsedBody();

        $query = 'SELECT * FROM tbl_asistencia WHERE col_materiaid="'.intval($input->taxonomiaMateria).'" AND col_maestroid="'.intval($input->userid).'" AND col_sesion="'.intval($input->sesion).'" AND col_fecha="'.date('Y-m-d').'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);
        $asistenciaid = $data->col_id;
        if(intval($input->fecha) == 0 && $sth->rowCount() > 0){
            $input->fecha = $data->col_id;
        }
        if(intval($input->fecha) == 0){

            $data_insert = array(
                "col_materiaid" => intval($input->taxonomiaMateria), // col_id tbl_maestros_taxonomia
                "col_maestroid" => intval($input->userid),
                "col_fecha"     => date('Y-m-d'),
                "col_sesion"    => intval($input->sesion),
                "col_observaciones" => $input->observaciones,
                "col_created_at" => date("Y-m-d H:i:s"),
                "col_created_by" => $input->userid,
                "col_updated_at" => date("Y-m-d H:i:s"),
                "col_updated_by" => $input->userid,
            );

            $query = 'INSERT INTO tbl_asistencia ('.implode(",", array_keys($data_insert)).')
            VALUES("'.implode('", "', array_values($data_insert)).'")';

            $dblog = new DBLog($query, 'tbl_asistencia', '', '', 'Asistencias', $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();

            $asistenciaid = $this->db->lastInsertId();

            $alumnos = explode(',', $input->alumnos);
            foreach($alumnos as $alumno){

                $data_insert = array(
                    "col_listaid"        => intval($asistenciaid),
                    "col_alumnoid"       => intval($alumno),
                    "col_asistencia"     => trim($input->asistencias[$alumno]),
                    "col_participacion"  => intval($input->participacion[$alumno]),
                    "col_segunda"  => intval($input->segunda[$alumno]),
                    //"col_comportamiento" => trim($input->comportamiento[$alumno]),
                    // "col_justificacion"  => trim($input->justificacion[$alumno]),
                    "col_created_at"     => date("Y-m-d H:i:s"),
                    "col_created_by"     => $input->userid,
                    "col_updated_at"     => date("Y-m-d H:i:s"),
                    "col_updated_by"     => $input->userid,
                );

                $query = 'INSERT INTO tbl_asistencia_alumnos ('.implode(",", array_keys($data_insert)).')
                VALUES("'.implode('", "', array_values($data_insert)).'")';

                $dblog = new DBLog($query, 'tbl_asistencia_alumnos', '', '', 'Asistencias', $this->db);
                $dblog->prepareLog();

                $sth = $this->db->prepare($query);
                $sth->execute();

                $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                $dblog->saveLog();
            }

        }else{
            $asistenciaid = $input->fecha;

            $query = 'UPDATE tbl_asistencia SET '.
            'col_observaciones="'.$input->observaciones.'", '.
            'col_updated_at="'.date("Y-m-d H:i:s").'", '.
            'col_updated_by="'.$input->userid.'" '.
            'WHERE col_id="'.$asistenciaid.'"';

            $dblog = new DBLog($query, 'tbl_asistencia', '', '', 'Asistencias', $this->db);
            $dblog->where = array('col_id' => intval($asistenciaid));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->saveLog();

            $alumnos = explode(',', $input->alumnos);
            foreach($alumnos as $alumno){
                if($alumno > 0) {
                    $query = 'SELECT * FROM tbl_asistencia_alumnos WHERE col_alumnoid="'.$alumno.'" AND col_listaid="'.intval($asistenciaid).'"';
                    $sth = $this->db->prepare($query);
                    $sth->execute();
                    if($sth->rowCount() > 0) {

                        // $alumnos = explode(',', $input->alumnos);
                        // foreach($alumnos as $alumno){
                           $query = 'UPDATE tbl_asistencia_alumnos SET '.
                            'col_asistencia="'.trim($input->asistencias[$alumno]).'", '.
                            'col_participacion="'.intval($input->participacion[$alumno]).'", '.
                            'col_segunda="'.intval($input->segunda[$alumno]).'", '.
                            'col_comportamiento="'.trim($input->comportamiento[$alumno]).'", '.
                            'col_justificacion="'.trim($input->justificacion[$alumno]).'", '.
                            'col_updated_at="'.date("Y-m-d H:i:s").'", '.
                            'col_updated_by="'.$input->userid.'" '.
                            'WHERE col_alumnoid="'.$alumno.'" AND col_listaid="'.$asistenciaid.'"';

                            $dblog = new DBLog($query, 'tbl_asistencia_alumnos', '', '', 'Asistencias', $this->db);
                            $dblog->where = array('col_alumnoid' => intval($alumno), 'col_listaid' => intval($asistenciaid));
                            $dblog->prepareLog();

                            $sth = $this->db->prepare($query);
                            $sth->execute();

                            $dblog->saveLog();
                        //}

                    }else{
                        //$alumnos = explode(',', $input->alumnos);
                        //foreach($alumnos as $alumno){

                            $data_insert = array(
                                "col_listaid"        => intval($asistenciaid),
                                "col_alumnoid"       => intval($alumno),
                                "col_asistencia"     => trim($input->asistencias[$alumno]),
                                "col_participacion"  => intval($input->participacion[$alumno]),
                                "col_segunda"  => intval($input->segunda[$alumno]),
                                //"col_comportamiento" => trim($input->comportamiento[$alumno]),
                                // "col_justificacion"  => trim($input->justificacion[$alumno]),
                                "col_created_at"     => date("Y-m-d H:i:s"),
                                "col_created_by"     => $input->userid,
                                "col_updated_at"     => date("Y-m-d H:i:s"),
                                "col_updated_by"     => $input->userid,
                            );

                            $query = 'INSERT INTO tbl_asistencia_alumnos ('.implode(",", array_keys($data_insert)).')
                            VALUES("'.implode('", "', array_values($data_insert)).'")';

                            $dblog = new DBLog($query, 'tbl_asistencia_alumnos', '', '', 'Asistencias', $this->db);
                            $dblog->prepareLog();

                            $sth = $this->db->prepare($query);
                            $sth->execute();

                            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                            $dblog->saveLog();
                        //}
                    }
                }
            }
        }


        $_response['status'] = 'true';
        return $this->response->withJson($_response);

    });

    $this->put('/updateLista', function (Request $request, Response $response, $args) {

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());
        // $input = $request->getParsedBody();
        $query = 'SELECT * FROM tbl_asistencia WHERE col_id="'.intval($input->id).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataAsistencia = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'UPDATE tbl_asistencia SET '.
        'col_observaciones="'.$input->observaciones.'", '.
        'col_updated_at="'.date("Y-m-d H:i:s").'", '.
        'col_updated_by="'.$input->userid.'" '.
        'WHERE col_id="'.$dataAsistencia->col_id.'"';

        $dblog = new DBLog($query, 'tbl_asistencia', '', '', 'Asistencias', $this->db);
        $dblog->where = array('col_id' => intval($dataAsistencia->col_id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        $alumnos = explode(',', $input->alumnos);
        foreach($alumnos as $alumno){
            if($alumno > 0){
                $query = 'SELECT * FROM tbl_asistencia_alumnos WHERE col_alumnoid="'.$alumno.'" AND col_listaid="'.intval($input->id).'"';
                $sth = $this->db->prepare($query);
                $sth->execute();
                if($sth->rowCount() > 0) {

                    //$alumnos = explode(',', $input->alumnos);
                    //foreach($alumnos as $alumno){
                        $query = 'UPDATE tbl_asistencia_alumnos SET '.
                        'col_asistencia="'.trim($input->asistencias[$alumno]).'", '.
                        'col_participacion="'.intval($input->participacion[$alumno]).'", '.
                        'col_segunda="'.intval($input->segunda[$alumno]).'", '.
                        'col_comportamiento="'.trim($input->comportamiento[$alumno]).'", '.
                        'col_justificacion="'.trim($input->justificacion[$alumno]).'", '.
                        'col_updated_at="'.date("Y-m-d H:i:s").'", '.
                        'col_updated_by="'.$input->userid.'" '.
                        'WHERE col_alumnoid="'.$alumno.'" AND col_listaid="'.$dataAsistencia->col_id.'"';

                        $dblog = new DBLog($query, 'tbl_asistencia_alumnos', '', '', 'Asistencias', $this->db);
                        $dblog->where = array('col_alumnoid' => intval($alumno), 'col_listaid' => $dataAsistencia->col_id);
                        $dblog->prepareLog();

                        $sth = $this->db->prepare($query);
                        $sth->execute();
                        $dblog->saveLog();
                    //}

                }else{
                    // $alumnos = explode(',', $input->alumnos);
                    // foreach($alumnos as $alumno){

                        $data_insert = array(
                            "col_listaid"        => intval($input->id),
                            "col_alumnoid"       => intval($alumno),
                            "col_asistencia"     => trim($input->asistencias[$alumno]),
                            "col_participacion"  => intval($input->participacion[$alumno]),
                            "col_segunda"  => intval($input->segunda[$alumno]),
                            //"col_comportamiento" => trim($input->comportamiento[$alumno]),
                            // "col_justificacion"  => trim($input->justificacion[$alumno]),
                            "col_created_at"     => date("Y-m-d H:i:s"),
                            "col_created_by"     => $input->userid,
                            "col_updated_at"     => date("Y-m-d H:i:s"),
                            "col_updated_by"     => $input->userid,
                        );

                        $query = 'INSERT INTO tbl_asistencia_alumnos ('.implode(",", array_keys($data_insert)).')
                        VALUES("'.implode('", "', array_values($data_insert)).'")';

                        $dblog = new DBLog($query, 'tbl_asistencia_alumnos', '', '', 'Asistencias', $this->db);
                        $dblog->prepareLog();

                        $sth = $this->db->prepare($query);
                        $sth->execute();

                        $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                        $dblog->saveLog();
                    //}
                }
            }
        }


        $_response['status'] = 'true';
        return $this->response->withJson($_response);

    });



});
// Termina routes.asistencias.php
