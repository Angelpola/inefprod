<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de maestros.
 *
 * Lista de funciones
 *
 * /maestros
 * - /evaluacionListMaterias
 * - /evaluacion
 * - /getListExamenes
 * - /list
 * - /get
 * - /update
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/maestros', function () {

    $this->get('/evaluacionListMaterias', function (Request $request, Response $response, array $args) {

        $maestroID = getCurrentUserID();

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $r = $c->fetch(PDO::FETCH_OBJ);
        $grupos_periodos = array($r->col_periodo, $r->col_periodo_cuatri, $r->col_periodo_maestria, $r->col_periodo_doctorado);

        $query = 'SELECT * FROM tbl_eva_maestros WHERE col_estatus="2" AND col_group_periodoid IN ('.implode(',', array_unique($grupos_periodos)).') ORDER BY col_id DESC';
        $data->evaMaestrosDisponible = $query;
        $fth = $this->db->prepare($query);
        $fth->execute();
        $dataEvaMaestro = $fth->fetch(PDO::FETCH_OBJ);


        $i = 0;
        $query = 'SELECT m.* FROM tbl_eva_maestros_observaciones o '.
                 'LEFT OUTER JOIN tbl_materias m ON m.col_id=o.col_materiaid '.
                 // 'WHERE o.col_maestroid="'.intval($maestroID).'" AND o.col_evaid="'.intval($dataEvaMaestro->col_id).'" GROUP BY o.col_materiaid';
                 'WHERE o.col_maestroid="'.intval($maestroID).'" AND o.col_evaid IN (SELECT col_id FROM tbl_eva_maestros WHERE col_estatus="2" AND col_group_periodoid IN ('.implode(',', array_unique($grupos_periodos)).')) GROUP BY o.col_materiaid';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $in = array();
        foreach($todos as $item){
            $index = strtoupper(claveMateria($item['col_clave'])).'-'.$item['col_carrera'].$item['col_semestre'];
            if(in_array($index,  $in)) continue;
            $in[] = $index;
            $carreraData = getCarrera($item['col_carrera'], $this->db);
            $materias[$i]['id'] = $item['col_id'];
            $materias[$i]['materia'] = fixEncode($item['col_nombre']).' ('.fixEncode($carreraData['modalidad']).')';
            $i++;
        }

        return $this->response->withJson($materias);

    });

    $this->get('/evaluacion', function (Request $request, Response $response, array $args) {
        $maestroID = getCurrentUserID();
        $materiaid = intval($_REQUEST['materiaid']);

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $r = $c->fetch(PDO::FETCH_OBJ);
        $grupos_periodos = array($r->col_periodo, $r->col_periodo_cuatri, $r->col_periodo_maestria, $r->col_periodo_doctorado);


        $query = 'SELECT * FROM tbl_eva_maestros_observaciones WHERE col_estatus="1" AND col_materiaid="'.$materiaid.'" AND col_maestroid="'.$maestroID.'" AND col_evaid IN (SELECT col_id FROM tbl_eva_maestros WHERE col_estatus="2" AND col_group_periodoid IN ('.implode(',', array_unique($grupos_periodos)).') ORDER BY col_id DESC) GROUP BY col_evaid ORDER BY col_evaid DESC LIMIT 1';
        $fth = $this->db->prepare($query);
        $fth->execute();
        $dataEvaMaestro = $fth->fetch(PDO::FETCH_OBJ);
        $evaid = $dataEvaMaestro->col_evaid;
        $fecha = fechaTexto($dataEvaMaestro->col_updated_at);


        $query = 'SELECT * FROM tbl_users WHERE col_id="'.intval($maestroID).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMaestro = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_materias WHERE col_id="'.intval($materiaid).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMateria = $sth->fetch(PDO::FETCH_OBJ);

        $tipoMaestro = 'regular';
        if(strtoupper(substr($dataMateria->col_clave, 0, 2)) == 'TL') $tipoMaestro = 'taller';
        if(strtoupper(substr($dataMateria->col_clave, 0, 2)) == 'AC') $tipoMaestro = 'academia';
        if(strtoupper(substr($dataMateria->col_clave, 0, 2)) == 'CL') $tipoMaestro = 'club';


            $subQuery = 'SELECT col_id FROM tbl_materias WHERE col_carrera="'.$dataMateria->col_carrera.'" AND col_plan_estudios="'.$dataMateria->col_plan_estudios.'" AND UPPER(col_clave) LIKE "'.strtoupper(claveMateria($dataMateria->col_clave)).'%"';
            $i = 0;
            $query = 'SELECT * FROM tbl_eva_maestros_preguntas WHERE col_evaid="'.intval($evaid).'" AND col_tipo_maestro="'.$tipoMaestro.'" AND col_respuesta=1';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach($todos as $item){
                $results = calcularResultados($item['col_id'], intval($evaid), intval($maestroID), $subQuery, $this->db);

                $result_1[$i]['id'] = $item['col_id'];
                $result_1[$i]['pregunta'] = fixEncode($item['col_pregunta']);
                $result_1[$i]['S'] = intval($results['S']);
                $result_1[$i]['CS'] = intval($results['CS']);
                $result_1[$i]['AV'] = intval($results['AV']);
                $result_1[$i]['N'] = intval($results['N']);
                $i++;
            }

            $i = 0;

            $query = 'SELECT * FROM tbl_eva_maestros_preguntas WHERE col_evaid="'.intval($evaid).'" AND col_tipo_maestro="'.$tipoMaestro.'" AND col_respuesta=2';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach($todos as $item){
                $results = calcularResultados($item['col_id'], intval($evaid), intval($maestroID), $subQuery, $this->db);

                $result_2[$i]['id'] = $item['col_id'];
                $result_2[$i]['pregunta'] = fixEncode($item['col_pregunta']);
                $result_2[$i]['E'] = intval($results['E']);
                $result_2[$i]['B'] = intval($results['B']);
                $result_2[$i]['R'] = intval($results['R']);
                $result_2[$i]['M'] = intval($results['M']);
                $i++;
            }

            $i = 0;
            $query = 'SELECT * FROM tbl_eva_maestros_preguntas WHERE col_evaid="'.intval($evaid).'" AND col_tipo_maestro="'.$tipoMaestro.'" AND col_respuesta=3';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach($todos as $item){
                $results = calcularResultados($item['col_id'], intval($evaid), intval($maestroID), $subQuery, $this->db);

                $result_3[$i]['id'] = $item['col_id'];
                $result_3[$i]['pregunta'] = fixEncode($item['col_pregunta']);
                $result_3[$i]['SI'] = intval($results['SI']);
                $result_3[$i]['NO'] = intval($results['NO']);
                $i++;
            }

            $i = 0;
            $query = 'SELECT * FROM tbl_eva_maestros_observaciones WHERE col_observaciones!="" AND col_materiaid IN ('.$subQuery.') AND '.
                'col_maestroid="'.intval($maestroID).'" AND col_evaid="'.intval($evaid).'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach($todos as $item){
                if(strlen(trim($item['col_observaciones'])) > 1) {
                    $observaciones[$i]['id'] = $item['col_id'];
                    $observaciones[$i]['observacion'] = fixEncode($item['col_observaciones']);
                    $i++;
                }

            }



        // $_response['tituloEva'] = fixEncode($dataEva->col_titulo);
        $_response['maestro'] = fixEncode($dataMaestro->col_firstname.' '.$dataMaestro->col_lastname);
        // $_response['maestroid'] = $dataMaestro->col_id;
        $_response['materia'] = fixEncode($dataMateria->col_nombre);
        $_response['fecha'] = $fecha;
        $_response['evaid'] = $evaid;
        $_response['listPreguntas1'] = $result_1;
        $_response['listPreguntas2'] = $result_2;
        $_response['listPreguntas3'] = $result_3;
        $_response['listObservaciones'] = $observaciones;

        return $this->response->withJson($_response);
    });

    $this->get('/getListExamenes', function (Request $request, Response $response, array $args) {
        global $apiURL;
        $subQuery = 'SELECT col_maestroid FROM tbl_maestros_taxonomia WHERE col_periodoid IN ('.implode(',', getCurrentPeriodos($this->db)).')';
        $query = "SELECT * FROM tbl_actividades WHERE col_tipo IN (5,6,7,8,9) AND col_created_by IN (".$subQuery.") ORDER BY col_id DESC";
        $query = "SELECT * FROM tbl_actividades WHERE col_tipo IN (5,6,7,8,9) ORDER BY col_id DESC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();


        $i = 0;
        $tiposActividades = array(
            1 => "Tarea",
            2 => "Trabajos de Investigación",
            3 => "Lectura",
            4 => "Debates",
            5 => "Examen Parcial 1",
            6 => "Examen Parcial 2",
            7 => "Examen Final",
            8 => "Examen Extraordinario",
            9 => "Examen a Titulo de Suficiencia",
            10 => "Actividad Extra (No calificable)",
            11 => "Actividad en Clase");

        foreach($todos as $item) {

            $sth = $this->db->prepare("SELECT * FROM tbl_users WHERE col_id='".$item['col_created_by']."'");
            $sth->execute();
            $maestroData = $sth->fetch(PDO::FETCH_OBJ);

            $materiaID = getMateriaByActividadID($item[col_visible_excepto], $this->db, intval($item['col_created_by']), intval($item['col_id']));
            $query = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaID.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $materiaData = $sth->fetch(PDO::FETCH_OBJ);


            $sth = $this->db->prepare("SELECT * FROM tbl_carreras WHERE col_id='".$materiaData->col_carrera."'");
            $sth->execute();
            $carreraData = $sth->fetch(PDO::FETCH_OBJ);

            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['maestro'] = fixEncode($maestroData->col_firstname.' '.$maestroData->col_lastname);
            $result[$i]['materia'] = fixEncode($materiaData->col_nombre);
            $result[$i]['carrera'] = fixEncode($carreraData->col_nombre_largo);
            $result[$i]['tipo'] = $tiposActividades[$item['col_tipo']];
            $result[$i]['fecha'] = fechaTexto($item['col_fecha_inicio']);
            $query = "SELECT * FROM tbl_actividades_tareas WHERE col_actividadid='".$item['col_id']."'";
            $sth = $this->db->prepare($query);
            $sth->execute();
            if($sth->rowCount()){
                $result[$i]['acta'] = '<a href="'.$apiURL.'/actividades/getActa?id='.$item['col_id'].'" class="text-secondary" target="_blank"><i class="fas fa-file-download"></i> Descargar Acta</a>';
            }else{
                $result[$i]['acta'] = '<i class="text-danger fas fa-ban"></i> Sin Calificar';
            }



            if($item['col_visible_excepto'] == ''){
                $result[$i]['grupo'] = 'Todos';
            }else{
                $elID = unserialize($item['col_visible_excepto']);
                if(intval($elID) > 0) {
                    $queryPeriodo = 'SELECT * FROM tbl_periodos WHERE col_id="'.$elID.'"';
                    $sthPeriodo = $this->db->prepare($queryPeriodo);
                    $sthPeriodo->execute();
                    $periodoData = $sthPeriodo->fetch(PDO::FETCH_OBJ);
                    $result[$i]['grupo'] =  $periodoData->col_grado."-".$periodoData->col_grupo;
                    $_modalidades = array(1=>'Semestral', 2=>'Cuatrimestral', 3=>'Maestría', 4=>'Doctorado');
                    $result[$i]['grupo'] .= ' ('.$_modalidades[$periodoData->col_modalidad].')';
                }else{
                    $result[$i]['grupo'] =  'Multigrupo';
                }
            }

            $i++;
        }


        return $this->response->withJson($result);

    });

    $this->get('/list', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_users WHERE col_type='4' ORDER BY col_id");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item) {
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['col_fullname'] = utf8_encode($item['col_firstname'].' '.$item['col_lastname']);
            $result[$i]['col_email'] = $item['col_email'];
            $result[$i]['col_cedula'] = utf8_encode($item['col_cedula']);
            $result[$i]['col_fecha_nacimiento'] = $item['col_fecha_nacimiento'];

            $i++;
        }


        return $this->response->withJson($result);

    });

    $this->post('/get', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $query = 'SELECT * FROM tbl_maestros WHERE col_id="'.intval($input['params']['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data_ma = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_users WHERE col_id="'.intval($data_ma->col_userid).'"';
        $sth_us = $this->db->prepare($query);
        $sth_us->execute();
        $data_us = $sth_us->fetch(PDO::FETCH_OBJ);

        // Tbl Maestros
        $result['id'] = $data_ma->col_id;
        $result['cedula'] = $data_ma->col_cedula;
        $result['fecha_nacimiento'] = $data_ma->col_fecha_nacimiento;
        // Tbl Users
        $result['telefono'] = $data_us->col_phone;
        $result['nombres'] = $data_us->col_firstname;
        $result['apellidos'] = $data_us->col_lastname;
        $result['correo'] = $data_us->col_email;
        $result['estatus'] = $data_us->col_status;


        return $this->response->withJson($result);

    });

    $this->put('/update', function (Request $request, Response $response, $args) {

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());


        $query = 'SELECT * FROM tbl_maestros WHERE col_id="'.intval($input->id).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data_ma = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'UPDATE tbl_maestros SET
        col_cedula="'.$input->cedula.'",
        col_fecha_nacimiento="'.substr($input->fechaNacimiento[0], 0, 10).'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$input->userid.'" WHERE col_id="'.$input->id.'"';
        $sth_maestro = $this->db->prepare($query);

        $query = 'UPDATE tbl_users SET
        col_firstname="'.$input->nombres.'",
        col_lastname="'.$input->apellidos.'",
        col_phone="'.$input->telefono.'",
        col_email="'.$input->correo.'",
        col_status="'.$input->estatus.'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$input->userid.'" WHERE col_id="'.$data_ma->col_userid.'"';
        $sth_user = $this->db->prepare($query);

        if($input->password != ''){
            $query = 'UPDATE tbl_users SET
            col_pass="'.md5($input->password).'",
            col_updated_at="'.date("Y-m-d H:i:s").'",
            col_updated_by="'.$input->userid.'" WHERE col_id="'.$data_ma->col_userid.'"';
            $sth_pass = $this->db->prepare($query);
            $sth_pass->execute();
        }

        if($sth_user->execute() && $sth_maestro->execute()){
            $_response['status'] = 'true';
        }

        return $this->response->withJson($_response);

    });



});
// Termina routes.maestros.php
