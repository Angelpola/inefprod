<?php


/**
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de Labor Altruista.
 *
 * Lista de funciones
 *
 * /altruista
 * - /getPaseLista
 * - /getActividades
 * - /grupos
 * - /cambiarGrupo
 * - /guardarPaseLista
 * - /eliminarActividad
 * - /guardarActividad
 * - /generar
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/altruista', function () {

    $this->get('/listPeriodos', function (Request $request, Response $response, array $args) {

        $modalidades = array(0 => "Sin definir", 1 => "Semestral Licenciatura", 2 => "Cuatrimestral Licenciatura", 3 => "Semestral Maestria", 4 => "Semestral Doctorado");

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $config = $c->fetch(PDO::FETCH_OBJ);
        $activos = array(intval($config->col_periodo), intval($config->col_periodo_cuatri), intval($config->col_periodo_maestria), intval($config->col_periodo_doctorado));
        $menor = min(array_values($activos));
        $mayor = max(array_values($activos));


        $query = "SELECT * FROM tbl_periodos WHERE col_groupid IN (".implode(',', $activos).") GROUP BY col_modalidad ORDER BY col_groupid DESC, col_nombre ASC";
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
                if(in_array(md5($item['col_groupid'].'-'.$item['col_grado']), $losGrados)) {
                    continue;
                }else{
                    $losGrados[] = md5($item['col_groupid'].'-'.$item['col_grado']);
                }
            }

            if($_REQUEST['agrupar_full'] == 'true') {
                if(in_array(md5($item['col_groupid']), $losGrados)) {
                    continue;
                }else{
                    $losGrados[] = md5($item['col_groupid']);
                }
            }

            $carreraData = getCarrera($item['col_carreraid'], $this->db);
            $result[$i]['value'] = $item['col_id'];
            // $result[$i]['debug'] = $query;
            $result[$i]['groupid'] = $item['col_groupid'];
            if(in_array($item['col_groupid'], $activos) && ($_REQUEST['activos'] == 'true' || $_REQUEST['all'] == 'true' || $_REQUEST['all-expand'] == 'true')){
                $result[$i]['label'] = fixEncode($item['col_nombre']).' ('.$modalidades[$item['col_modalidad']].' - '.$carreraData['nombre'].')'.($_REQUEST['activos'] != 'true'?'(Periodo Actual)':'');
                $result[$i]['text'] = fixEncode($item['col_nombre']).' ('.$modalidades[$item['col_modalidad']].' - '.$carreraData['nombre'].')'.($_REQUEST['activos'] != 'true'?'(Periodo Actual)':'');
                $result[$i]['nombre'] = fixEncode($item['col_nombre']);
                $result[$i]['modalidad'] = $modalidades[$item['col_modalidad']];
                $result[$i]['carrera'] = $carreraData['nombre'];
                $result[$i]['selected'] = 'true';
                $result[$i]['activo'] = 'true';
                $result[$i]['grado'] = $item['col_grado'];
                $result[$i]['grupo'] = $item['col_grupo'];
            }else{
                $result[$i]['label'] = fixEncode($item['col_nombre']).' ('.$modalidades[$item['col_modalidad']].' - '.$carreraData['nombre'].')'.($_REQUEST['activosfull'] == 'true'?' '.$item['col_grado'].'-'.$item['col_grupo']:'');
                $result[$i]['text'] = fixEncode($item['col_nombre']).' ('.$modalidades[$item['col_modalidad']].' - '.$carreraData['nombre'].')'.($_REQUEST['activosfull'] == 'true'?' '.$item['col_grado'].'-'.$item['col_grupo']:'');
                $result[$i]['nombre'] = fixEncode($item['col_nombre']);
                $result[$i]['modalidad'] = $modalidades[$item['col_modalidad']];
                $result[$i]['carrera'] = $carreraData['nombre'];
                $result[$i]['selected'] = 'false';
                $result[$i]['activo'] = 'false';
                $result[$i]['grado'] = $item['col_grado'];
                $result[$i]['grupo'] = $item['col_grupo'];
            }
            $i++;
        }

        return $this->response->withJson($result);

    });


    $this->get('/getPaseLista', function (Request $request, Response $response, array $args) {

        $input = $request->getParsedBody();


        $sth = $this->db->prepare("SELECT * FROM tbl_altruista_actividades WHERE col_id='".intval($_REQUEST[actividad])."'");
        $sth->execute();
        $actividad = $sth->fetch(PDO::FETCH_OBJ);

        $query = "SELECT a.* FROM tbl_altruista_integrantes i LEFT OUTER JOIN tbl_alumnos a ON a.col_id=i.col_alumnoid WHERE a.col_estatus='activo' AND i.col_grupo='".intval($actividad->col_grupo)."' AND i.col_group_periodoid='".intval($actividad->col_group_periodoid)."' ORDER BY a.col_apellidos ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $integrantes = $sth->fetchAll();

        $i = 0;
        foreach($integrantes as $item) {
            $result[$i]['alumnoNombre'] = fixEncode($item['col_apellidos']." ".$item['col_nombres']);
            $result[$i]['alumnoid'] = $item['col_id'];
            $i++;
        }

        $sth = $this->db->prepare("SELECT * FROM tbl_altruista_asistencia WHERE col_asistencia=1 AND col_actividad='".intval($_REQUEST[actividad])."'");
        $sth->execute();
        $asistencias = $sth->fetchAll();
        foreach($asistencias as $item) {
            $resultAsistencia[] = $item['col_alumnoid'];
        }

        $_response['listaIntegrantes'] = $result;
        $_response['listaAsistencias'] = $resultAsistencia;
        $_response['nombreActividad'] = $actividad->col_titulo;
        $_response['fechaActividad'] = fechaTexto($actividad->col_fecha);

        return $this->response->withJson($_response);

    });

    $this->get('/getActividades', function (Request $request, Response $response, array $args) {

        $input = $request->getParsedBody();

        $userDepto = getCurrentUserDepto();
        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $userID = getCurrentUserID();
        $periodoAlumnoID = getCurrentAlumnoPeriodoID($this->db);


        $sth = $this->db->prepare("SELECT * FROM tbl_altruista_actividades WHERE col_grupo='".intval($_REQUEST[grupo])."' AND col_group_periodoid='".intval($_REQUEST[periodo])."' ORDER BY col_fecha DESC");
        $sth->execute();
        $actividades = $sth->fetchAll();

        $i = 0;
        foreach($actividades as $item) {
            $result[$i]['col_titulo'] = $item['col_titulo'];
            $result[$i]['col_fecha'] = fechaTexto($item['col_fecha']);
            $result[$i]['col_id'] = $item['col_id'];
            $i++;
        }


        return $this->response->withJson($result);

    });

    $this->get('/grupos', function (Request $request, Response $response, array $args) {

        $input = $request->getParsedBody();

        $userDepto = getCurrentUserDepto();
        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $userID = getCurrentUserID();
        $periodoAlumnoID = getCurrentAlumnoPeriodoID($this->db);


        $sth = $this->db->prepare("SELECT * FROM tbl_altruista WHERE col_group_periodoid='".intval($_REQUEST[periodo])."' ORDER BY col_nombre DESC");
        $sth->execute();
        if($sth->rowCount() == 0) {
            $sth = $this->db->prepare("SELECT * FROM tbl_periodos WHERE col_id='".intval($_REQUEST[periodo])."'");
            $sth->execute();
            $periodo = $sth->fetch(PDO::FETCH_OBJ);

            $wherePeriodos = Array();
            $query = "SELECT * FROM tbl_periodos WHERE col_groupid='".intval($periodo->col_groupid)."' AND col_modalidad='".$periodo->col_modalidad."'";
            $sth = $this->db->prepare($query);
            $sth->execute();
            $periodos = $sth->fetchAll();
            foreach($periodos as $_pe) {
                $wherePeriodos[] = $_pe['col_id'];
            }

            $result['totalAlumnos'] = 0;
            if(count($wherePeriodos) > 0) {
                $query = "SELECT * FROM tbl_alumnos_taxonomia WHERE col_groupid='".intval($periodo->col_groupid)."' AND col_periodoid IN (".implode(',', $wherePeriodos).")";
                $sthA = $this->db->prepare($query);
                $sthA->execute();
                $result['totalAlumnos'] = $sthA->rowCount();
            }


            $result['totalGrupos'] = 0;
            $result['status'] = 'empty';
        } else {
            $_grupos = $sth->fetchAll();
            $total = 0;

            foreach($_grupos as $grupo) {
                $sthG = $this->db->prepare("SELECT i.*, CONCAT(a.col_nombres, ' ', a.col_apellidos) AS nombre_alumno FROM tbl_altruista_integrantes i LEFT OUTER JOIN tbl_alumnos a ON a.col_id=i.col_alumnoid WHERE a.col_estatus='activo' AND i.col_grupo='".$grupo['col_grupo']."' AND i.col_group_periodoid='".intval($_REQUEST[periodo])."'");
                $sthG->execute();
                $integrantes = $sthG->fetchAll();
                $x = 0;
                foreach($integrantes as $integrante) {
                    $grupos[$grupo['col_grupo']][$x]['id'] = $integrante['col_alumnoid'];
                    $grupos[$grupo['col_grupo']][$x]['nombre'] = $integrante['nombre_alumno'];
                    $x++;
                }
                $total++;
            }
            $result['status'] = 'true';
            $result['grupos'] = $grupos;
            $result['totalGrupos'] = $total;
        }
        return $this->response->withJson($result);

    });

    $this->post('/cambiarGrupo', function (Request $request, Response $response, $args) {
        global $dblog;

        $userID = getCurrentUserID();
        $input = json_decode($request->getBody());

        $sth = $this->db->prepare("SELECT * FROM tbl_altruista_integrantes WHERE col_grupo='".intval($input->grupoActual)."' AND col_alumnoid='".intval($input->alumnoid)."' AND col_group_periodoid='".intval($input->grupoPeriodo)."'");
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $query = "UPDATE tbl_altruista_integrantes SET col_grupo='".intval($input->grupoNuevo)."' WHERE col_id='".$data->col_id."'";

        $dblog = new DBLog($query, 'tbl_altruista_integrantes', '', '', 'Altruista', $this->db);
        $dblog->where = array('col_id' => intval($data->col_id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        $_response['status'] = 'true';


        return $this->response->withJson($_response);

    });

    $this->post('/guardarPaseLista', function (Request $request, Response $response, $args) {
        global $dblog;

        $userID = getCurrentUserID();
        $input = json_decode($request->getBody());
        $_response['status'] = 'false';

        $sth = $this->db->prepare("SELECT * FROM tbl_altruista_actividades WHERE col_id='".intval($input->actividad)."'");
        $sth->execute();
        $actividad = $sth->fetch(PDO::FETCH_OBJ);

        $query = "SELECT a.* FROM tbl_altruista_integrantes i LEFT OUTER JOIN tbl_alumnos a ON a.col_id=i.col_alumnoid WHERE a.col_estatus='activo' AND i.col_grupo='".intval($actividad->col_grupo)."' AND i.col_group_periodoid='".intval($actividad->col_group_periodoid)."'";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $integrantes = $sth->fetchAll();

        $i = 0;
        foreach($integrantes as $item) {
            if(in_array($item['col_id'], $input->participacion) && $input->participacion[$item['col_id']] === true) {
                $asistio = 1;
            }else{
                $asistio = 0;
            }
            $sth = $this->db->prepare("SELECT * FROM tbl_altruista_asistencia WHERE col_actividad='".intval($input->actividad)."' AND col_alumnoid='".$item['col_id']."'");
            $sth->execute();
            if($sth->rowCount()){
                $asistencia = $sth->fetch(PDO::FETCH_OBJ);
                $query = "UPDATE tbl_altruista_asistencia SET col_asistencia='".$asistio."' WHERE col_id='".$asistencia->col_id."'";

                $dblog = new DBLog($query, 'tbl_altruista_integrantes', '', '', 'Altruista', $this->db);
                $dblog->where = array('col_id' => intval($asistencia->col_id));
                $dblog->prepareLog();

                $sth = $this->db->prepare($query);
                $sth->execute();

                $dblog->saveLog();

            }else{
                $query = "INSERT INTO tbl_altruista_asistencia (col_actividad, col_alumnoid, col_asistencia) VALUES('".intval($input->actividad)."', '".$item['col_id']."', '".$asistio."')";

                $dblog = new DBLog($query, 'tbl_altruista_asistencia', '', '', 'Altruista', $this->db);
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

    $this->post('/eliminarActividad', function (Request $request, Response $response, $args) {
        global $dblog;

        $userID = getCurrentUserID();
        $input = json_decode($request->getBody());
        $_response['status'] = 'false';

        $query = 'DELETE FROM tbl_altruista_actividades WHERE col_id="'.$input->id.'"';

        $dblog = new DBLog($query, 'tbl_altruista_actividades', '', '', 'Altruista', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        if($sth->execute()){
            $_response['status'] = 'true';
            $dblog->saveLog();
        }

        return $this->response->withJson($_response);

    });

    $this->post('/guardarActividad', function (Request $request, Response $response, $args) {
        global $dblog;

        $userID = getCurrentUserID();
        $input = json_decode($request->getBody());
        $_response['status'] = 'false';

        $data = array(
            "col_grupo" => intval($input->grupo),
            "col_group_periodoid" => ($input->periodo),
            "col_titulo" => ($input->nombre),
            "col_descripcion" => ($input->descripcion),
            "col_fecha" => substr($input->fecha[0], 0, 10),
            "col_created_at" => date("Y-m-d H:i:s"),
            "col_created_by" => $userID,
            "col_updated_at" => date("Y-m-d H:i:s"),
            "col_updated_by" => $userID,
        );

        $query = 'INSERT INTO tbl_altruista_actividades ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';

        $dblog = new DBLog($query, 'tbl_altruista_actividades', '', '', 'Altruista', $this->db);
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        if($sth->execute()){
            $_response['status'] = 'true';

            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();
        }

        return $this->response->withJson($_response);

    });

    $this->post('/generar', function (Request $request, Response $response, $args) {
        global $dblog;

        $userID = getCurrentUserID();
        // $_response['status'] = 'false';
        // $sth = $this->db->prepare('TRUNCATE tbl_altruista');
        // $sth->execute();
        // $sth = $this->db->prepare('TRUNCATE tbl_altruista_integrantes');
        // $sth->execute();

        $input = json_decode($request->getBody());

        $sth = $this->db->prepare("SELECT * FROM tbl_periodos WHERE col_id='".intval($input->periodo)."'");
        $sth->execute();
        $periodo = $sth->fetch(PDO::FETCH_OBJ);

        $query = "SELECT * FROM tbl_periodos WHERE col_groupid='".intval($periodo->col_groupid)."' AND col_modalidad='".$periodo->col_modalidad."'";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $periodos = $sth->fetchAll();
        foreach($periodos as $_pe) {
            $wherePeriodos[] = $_pe['col_id'];
        }



        $query = "SELECT a.* FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid WHERE a.col_estatus='activo' AND t.col_periodoid IN (".implode(',', $wherePeriodos).") AND t.col_groupid='".intval($periodo->col_groupid)."'";
        $sthA = $this->db->prepare($query);
        $sthA->execute();
        $totalAlumnos = $sthA->rowCount();
        $alumnos = $sthA->fetchAll();

        $diff_byGroup = 0; // Just in case
        $pre_byGroup = ($totalAlumnos / intval($input->qty));
        $floor_byGroup = floor($pre_byGroup);
        if($pre_byGroup > $floor_byGroup) {
            $floorBaseTotal = ($floor_byGroup * intval($input->qty));
            $diffFloor = $totalAlumnos - $floorBaseTotal;
            $diff_byGroup = $floor_byGroup + $diffFloor;
        }
        $_response['base'] = $floor_byGroup;
        $_response['diff'] = $diff_byGroup;

        $group = 1;
        $a = 0;
        foreach($alumnos as $alumno) {
            // $_alumnos[] = trim(fixEncode($alumno[col_nombres])); // Debug line

            if($diff_byGroup > 0 && $group == $input->qty) {
                $grupos[$group][$a]['id'] = $alumno[col_id];
                $grupos[$group][$a]['nombre'] = fixEncode(trim($alumno['col_nombres']." ".trim($alumno['col_apellidos'])));

                $query = 'INSERT INTO tbl_altruista_integrantes (col_group_periodoid, col_grupo, col_alumnoid) VALUES("'.$input->periodo.'", "'.$group.'", "'.$alumno[col_id].'")';

                $dblog = new DBLog($query, 'tbl_altruista_integrantes', '', '', 'Altruista', $this->db);
                $dblog->prepareLog();

                $sth = $this->db->prepare($query);
                $sth->execute();
                $lastID = intval($this->db->lastInsertId());

                $dblog->where = array('col_id' => $lastID);
                $dblog->saveLog();

                $a++;
            }else{

                if($a < $floor_byGroup) {
                    $grupos[$group][$a]['id'] = $alumno[col_id];
                    $grupos[$group][$a]['nombre'] = fixEncode(trim($alumno['col_nombres']." ".trim($alumno['col_apellidos'])));

                    $query = 'INSERT INTO tbl_altruista_integrantes (col_group_periodoid, col_grupo, col_alumnoid) VALUES("'.$input->periodo.'", "'.$group.'", "'.$alumno[col_id].'")';

                    $dblog = new DBLog($query, 'tbl_altruista_integrantes', '', '', 'Altruista', $this->db);
                    $dblog->prepareLog();

                    $sth = $this->db->prepare($query);
                    $sth->execute();
                    $lastID = intval($this->db->lastInsertId());

                    $dblog->where = array('col_id' => $lastID);
                    $dblog->saveLog();

                    $a++;
                    if($a == $floor_byGroup){
                        $group++;
                        $a = 0;
                    }
                }

            }
        }


        for($i = 0; $i < intval($input->qty); $i++){
            $query = 'INSERT INTO tbl_altruista (col_group_periodoid, col_grupo, col_created_by, col_created_at, col_updated_by, col_updated_at) VALUES("'.$input->periodo.'", "'.($i + 1).'", "'.$userID.'", "'.date("Y-m-d H:i:s").'", "'.$userID.'", "'.date("Y-m-d H:i:s").'")';

            $dblog = new DBLog($query, 'tbl_altruista', '', '', 'Altruista', $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $lastID = intval($this->db->lastInsertId());
            $dblog->where = array('col_id' => $lastID);
            $dblog->saveLog();
        }

        $_response['grupos'] = $grupos;
        $_response['alumnos'] = $alumnos;

        return $this->response->withJson($_response);

    });


});
