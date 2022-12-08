<?php
/**
 *
 * Este archivo incluye todas las funciones que conectan a la base de datos del modulo de actividades y califiaciones de actividades,
 * incluye las funciones que generan el acta de calificaciones de examenes, y las funciones que permiten a los alumnos enviar sus
 * evidencias (tareas) de las actividades
 *
 * Lista de acciones vinculadas al modulo de actividades
 *
 * /actividades
 * - /listHistorialAlumno
 * - /listUsuarios
 * - /listOpcionesVisible
 * - /listOpcionesVisibleDepartamentos
 * - /listTable
 * - /list
 * - /filtro
 * - /getAlumnos
 * - /guardarCalificaciones
 * - /guardarCalificacionesIndividual
 * - /getActividad
 * - /get
 * - /update
 * - /tarea
 * - /add
 * - /delete
 * - /getActa
 * - /getBoleta
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

set_time_limit(0);

$app->group('/actividades', function () {


    $this->get('/listHistorialAlumno', function (Request $request, Response $response, array $args) {
        global $uploaddir, $download_url;

        $userDepto = getCurrentUserDepto();
        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $alumnoid = getCurrentUserID();
        $periodoAlumnoID = getCurrentAlumnoPeriodoID($this->db);

        $query = "SELECT a.col_tipo AS tipoActiviad, a.col_materiaid AS actividadMateriaID, t.col_actividadid AS actividadID, t.col_archivo AS archivo, t.col_calificacion AS calificacion, t.col_updated_by AS actualizo, ".
        "t.col_created_at AS fechaEnvio, a.col_titulo AS tituloActividad, a.col_descripcion AS descripcionActividad, a.col_fecha_inicio AS fechaInicio, a.col_fecha_fin AS fechaFin, a.col_created_by AS maestroid, t.col_created_by AS createdby, t.col_fecha_subida AS fechaEnvioReal, ".
        "a.col_visible_excepto AS data FROM tbl_actividades_tareas t LEFT OUTER JOIN tbl_actividades a ON a.col_id=t.col_actividadid WHERE a.col_visible_excepto LIKE '%".$periodoAlumnoID."%' AND t.col_alumnoid='".$alumnoid."' ".
        "GROUP BY t.col_actividadid ORDER BY t.col_id DESC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            if($item['tituloActividad'] == '') continue;
            $archivos = '';
            $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid="'.intval($item['actividadID']).'"';
            $sth_tt = $this->db->prepare($query);
            $sth_tt->execute();
            $tareas = $sth_tt->fetchAll();
            $totalTareas = 0;
            $x = 1;
            foreach($tareas as $tarea){
                if($tarea['col_archivo']) {

                    switch(substr(pathinfo($tarea['col_archivo'], PATHINFO_EXTENSION), 0, 3)){
                        case 'doc':$icon = 'fa-file-word';break;
                        case 'pdf':$icon = 'fa-file-pdf';break;
                        default:$icon = 'fa-file';break;
                    }

                    $archivos .= '<a target="_blank" class="tarea-file" href="'.$download_url.'tareas/'.$tarea['col_archivo'].'"><i class="fas '.$icon.'"></i> #'.$x.'</a>';
                    $totalTareas++;
                    $x++;
                }
            }


            $queryIntentos = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid="'.intval($item['actividadID']).'" GROUP BY col_intento ORDER BY col_intento ASC';
            $sth = $this->db->prepare($queryIntentos);
            $sth->execute();
            $todosIntentos = $sth->fetchAll();
            $int = 0;
            foreach($todosIntentos as $intentos) {
                $intentoNumero = $intentos['col_intento'];
                $queryTareas = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid="'.intval($item['actividadID']).'" AND col_intento="'.$intentos['col_intento'].'"';
                $sth = $this->db->prepare($queryTareas);
                $sth->execute();
                $todosTareas = $sth->fetchAll();
                $a = 0;

                foreach($todosTareas as $itemTarea) {
                    if($itemTarea['col_archivo']){
                        $tareasDetails[$int]['intento'] = $intentoNumero;
                        $tareasDetails[$int]['estatus'] = $itemTarea['col_estatus'];
                        $tareasDetails[$int]['retroalimentacion'] = $itemTarea['col_retroalimentacion'];
                        $tareasDetails[$int]['fecha_retro'] = $itemTarea['col_fecha_retro'];
                        $tareasDetails[$int]['falsificacion'] = $itemTarea['col_falsificacion'];
                        $tareasDetails[$int]['calificacion'] = $itemTarea['col_calificacion'];
                        $tareasDetails[$int]['calificacionCorregida'] = corregirCalificacion($itemTarea['col_calificacion']);
                        $tareasDetails[$int]['tareas'][$a]['fechaTarea'] = fechaTexto($itemTarea['col_created_at'])." ".substr($itemTarea['col_created_at'], 11, 17);
                        $tareasDetails[$int]['tareas'][$a]['url'] = $download_url.'tareas/'.$itemTarea['col_archivo'];
                        $tareasDetails[$int]['tareas'][$a]['archivo'] = $itemTarea['col_archivo'];
                        $tareasDetails[$int]['tareas'][$a]['intento'] = $itemTarea['col_intento'];

                        if($itemTarea['col_estatus'] == 2 && $item['tipoActiviad'] == 12) {
                            $item['calificacion'] = $itemTarea['col_calificacion'];
                        }
                        $a++;

                    }
                }
                $int++;
            }


            if($totalTareas == 0){
                $archivos .= '<span class="sin-tareas">Sin archivos</span>';
            }
            //echo $item['data'].'---'.$item['maestroid'].'--'.$item['actividadID'];exit;
            $maestroid = $item['maestroid'];
            if(intval($item['actividadMateriaID']) > 0) {
                $materiaID = $item['actividadMateriaID'];
            }else{
                $materiaID = getMateriaByActividadID($item['data'], $this->db, $maestroid, $item['actividadID']);
            }

            $query = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaID.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $materiaData = $sth->fetch(PDO::FETCH_OBJ);

            $_fechaEnvio = explode(' ', $item['fechaEnvio']);
            $result[$i]['materia'] = fixEncode($materiaData->col_nombre);
            $result[$i]['actividad'] = $item['tituloActividad'];
            $result[$i]['descripcion'] = $item['descripcionActividad'];
            $result[$i]['actividadid'] = $item['actividadID'];
            $result[$i]['fechaLimite'] = fechaTexto(($item['fechaFin'] == '0000-00-00'?$item['fechaInicio']:$item['fechaFin']));
            $result[$i]['archivo'] =  $archivos;
            $result[$i]['tareasDetails'] =  $tareasDetails;
            $result[$i]['tipoActividad'] = $item['tipoActiviad'];
            $result[$i]['totalTareas'] = $totalTareas;
            if(in_array($item['tipoActiviad'], array(5,6,7))){

                $_fechaEnvio = explode(' ', $item['fechaEnvio']);
                $result[$i]['fechaEnvio'] = fechaTexto($item['fechaEnvio']).' a las '.$_fechaEnvio[1];

            }else{
                if($totalTareas > 0) {
                    if(strtotime('now') > strtotime('2021-02-24')) {
                        $_fechaEnvio = explode(' ', $item['fechaEnvioReal']);
                        $result[$i]['fechaEnvio'] = fechaTexto($item['fechaEnvioReal']).' a las '.$_fechaEnvio[1];
                    }else{
                        $_fechaEnvio = explode(' ', $item['fechaEnvio']);
                        $result[$i]['fechaEnvio'] = fechaTexto($item['fechaEnvio']).' a las '.$_fechaEnvio[1];
                    }
                }else{
                    $result[$i]['fechaEnvio'] = 'No entregado';
                }
            }


            if(in_array(substr(strtoupper($materiaData->col_clave), 0, 2), array('AC'))){

                $calificacion = 'No Acredito';
                if(intval($item['calificacion']) == 1){
                    $calificacion = 'Acredito';
                }
                $result[$i]['calificacion'] = ($item['actualizo'] != $alumnoid?$calificacion:'<span class="sin-tareas">Calificación<br/>Pendiente</span>');

            }else if(in_array(substr(strtoupper($materiaData->col_clave), 0, 2), array('TL', 'CL', 'TR'))){

                $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_calificacion>1 AND col_actividadid="'.$item['actividadID'].'" AND col_alumnoid!="'.$alumnoid.'"';
                $sth = $this->db->prepare($query);
                $sth->execute();
                // if($sth->rowCount() == 0) if($item['calificacion'] == '1.00') $item['calificacion'] = '10.00';

                $result[$i]['calificacion'] = ($item['actualizo'] != $alumnoid?$item['calificacion']:'<span class="sin-tareas">Calificación<br/>Pendiente</span>');

            } else {
                $result[$i]['calificacion'] = ($item['actualizo'] != $alumnoid?$item['calificacion']:'<span class="sin-tareas">Calificación<br/>Pendiente</span>');
            }

            $i++;
        }
        return $this->response->withJson($result);
    });

    $this->get('/listUsuarios', function (Request $request, Response $response, array $args) {
        $userDepto = getCurrentUserDepto();
        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $userID = getCurrentUserID();
        $periodoAlumnoID = getCurrentAlumnoPeriodoID($this->db);
        $currentPeriodos = getCurrentPeriodos($this->db);

        if($userType == 'administrativo') {
            $sth = $this->db->prepare("SELECT * FROM tbl_users ORDER BY col_firstname ASC");
            $sth->execute();
            $todos = $sth->fetchAll();

            $i = 0;
            foreach($todos as $item){
                if($item['col_id'] == 1 && $userID != 1) continue;
                $result[$i]['value'] = 'u'.$item['col_id'];
                if($item['col_maestro'] == 1){
                    $result[$i]['label'] = fixEncode($item['col_firstname'].' '.$item['col_lastname'], true).' (Academico)';
                }else{
                    $result[$i]['label'] = fixEncode($item['col_firstname'].' '.$item['col_lastname'], true).' ('.getDepto($item['col_depto'], $this->db).')';
                }
                $i++;
            }

            if($currentPeriodos) {
                $query = "SELECT * FROM tbl_alumnos WHERE col_estatus='activo' AND col_periodoid IN (".implode(',', $currentPeriodos).") ORDER BY col_nombres ASC";
                //echo $query;exit;
                $sth = $this->db->prepare($query);
                $sth->execute();
                $todosAlumnos = $sth->fetchAll();

                //$i = 0;
                foreach($todosAlumnos as $item){
                    $periodoData = getPeriodo($item['col_periodoid'], $this->db, false);
                    $carreraData = getCarrera($item['col_carrera'], $this->db);
                    $result[$i]['value'] = $item['col_id'];
                    $result[$i]['label'] = fixEncode($item['col_nombres'].' '.$item['col_apellidos'], true).' ('.$periodoData->col_grado.'-'.$periodoData->col_grupo.' '.$carreraData['modalidad'].')';
                    //$result[$i]['label'] = fixEncode($item['col_nombres'].' '.$item['col_apellidos']);
                    $i++;
                }
            }
        }
        if($userType == 'maestro') {
            $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $data = $sth->fetch(PDO::FETCH_OBJ);

            $query = "SELECT a.* FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid WHERE t.col_periodoid IN (".implode(',', getPeriodosActivos($data->col_periodo, $this->db)).")";

            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();

            $i = 0;
            foreach($todos as $item){
                $result[$i]['value'] = $item['col_id'];
                $result[$i]['label'] = fixEncode($item['col_nombres'].' '.$item['col_apellidos']);
                $i++;
            }
        }

        return $this->response->withJson($result);

    });


    $this->get('/listOpcionesVisible', function (Request $request, Response $response, array $args) {

        $userDepto = getCurrentUserDepto();
        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $userID = getCurrentUserID();
        $periodoAlumnoID = getCurrentAlumnoPeriodoID($this->db);

        if($userType == 'administrativo') {

            $currentPeriodos = getCurrentPeriodos($this->db);
            if($currentPeriodos) {

                $sth = $this->db->prepare("SELECT * FROM tbl_periodos WHERE col_id IN (".implode(',', $currentPeriodos).") ORDER BY col_modalidad, col_grado, col_grupo ASC");
                $sth->execute();
                $todos = $sth->fetchAll();

                $i = 0;
                foreach($todos as $item){
                    $carreraData = getCarrera($item['col_carreraid'], $this->db);

                    $result[$i]['value'] = $item['col_id'];
                    $result[$i]['label'] = fixEncode($item['col_grado'].' '.$item['col_grupo']).' ('.$carreraData['modalidad'].')';
                    $i++;
                }

            }

        }

        if($userType == 'maestro') {
            $periodos = getCurrentPeriodos($this->db);
            $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $data = $sth->fetch(PDO::FETCH_OBJ);

           $query = "SELECT p.col_carreraid, p.col_plan_estudios, t.col_materia_clave, t.col_id AS ID, t.col_periodoid AS periodoid, p.col_aprobado AS horario_aprobado, p.col_groupid AS periodo_groupid, p.col_grado AS grado, p.col_grupo AS grupo ".
            "FROM tbl_maestros_taxonomia t ".
            "LEFT OUTER JOIN tbl_periodos p ON p.col_id = t.col_periodoid ".
            "WHERE  t.col_maestroid='".intval($userID)."' ".
            "AND t.col_periodoid IN (".implode(',', $periodos).") AND p.col_aprobado=1 ".
            "ORDER BY t.col_id";

            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();

            $i = 0;
            foreach($todos as $item){
                $carreraData = getCarrera($item['col_carreraid'], $this->db);

                $planMateria = 0; // Semestral
                if($carreraData['modalidad_numero'] == 2) $planMateria = 1; // Cuatrimestral
                // $queryMateria = 'SELECT * FROM tbl_materias WHERE col_carrera="'.$item['col_carreraid'].'" AND col_plan="'.$planMateria.'" AND col_semestre="'.$item['grado'].'" AND (col_clave = "'.claveMateria($item['col_materia_clave']).'" OR col_clave = "'.claveMateria($item['col_materia_clave']).$item['grado'].'") AND col_plan_estudios="'.$item['col_plan_estudios'].'"';
                $queryMateria = 'SELECT * FROM tbl_materias WHERE col_carrera="'.$item['col_carreraid'].'" AND col_plan="'.$planMateria.'" AND col_semestre="'.$item['grado'].'" AND col_clave = "'.$item['col_materia_clave'].'" AND col_plan_estudios="'.$item['col_plan_estudios'].'"';

                // echo $queryMateria.'<br/>';

                $sthm = $this->db->prepare($queryMateria);
                $sthm->execute();
                $materiaData = $sthm->fetch(PDO::FETCH_OBJ);


                $grupos = $item['grado'].'-'.$item['grupo'];
                $valor = $item['periodoid'].'|m:'.$materiaData->col_id;

                if(in_array($item['periodoid'], $periodos) && $materiaData->col_nombre) {
                    if(strpos(strtoupper($item[col_materia_clave]), 'AC') !== false || strpos(strtoupper($item[col_materia_clave]), 'TL') !== false){
                        $item['col_materia_clave'] = strtoupper($item['col_materia_clave']);
                        $periodosSemestral = getCurrentPeriodos($this->db, 'ldsem');
                        $grupos = 'Multigrupo';
                        $valor = "multi|".$item[col_materia_clave]."|".implode(',', $periodosSemestral);

                        if(strlen($item['col_materia_clave']) > 4){
                            $laClave = substr($item['col_materia_clave'], 0, -1);
                        }else{
                            $laClave = $item['col_materia_clave'];
                        }
                        if(is_array($mata) && in_array($laClave, $mata)) continue;

                        if(strlen($item['col_materia_clave']) > 4){
                            $mata[] = substr($item['col_materia_clave'], 0, -1);
                        }else{
                            $mata[] = $item['col_materia_clave'];
                        }
                        $mata = array_unique($mata);
                    }


                    if($grupos != 'Multigrupo') {
                        $_periodoData = getPeriodo($item['periodoid'], $this->db, false);
                        $modalidades = array(1=>'Semestral', 2=>'Cuatrimestral', 3=>'Maestría', 4=>'Doctorado');
                        $grupos = $grupos.' ('.$modalidades[$_periodoData->col_modalidad].')';
                    }


                    $result[$i]['value'] = $valor;
                    $result[$i]['label'] = fixEncode($materiaData->col_nombre.', '.$grupos);
                    $i++;
                }
            }
             // print_r($mata);
             // exit;

        }

        return $this->response->withJson($result);

    });

    $this->get('/listOpcionesVisibleDepartamentos', function (Request $request, Response $response, array $args) {

        $userDepto = getCurrentUserDepto();
        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $userID = getCurrentUserID();

        if($userType == 'administrativo') {


            $sth = $this->db->prepare("SELECT * FROM tbl_departamentos ORDER BY col_nombre ASC");
            $sth->execute();
            $todos = $sth->fetchAll();

            $i = 0;
            foreach($todos as $item){

                $result[$i]['value'] = $item['col_id'];
                $result[$i]['label'] = fixEncode($item['col_nombre']);
                $i++;
            }

        }


        return $this->response->withJson($result);

    });

    $this->get('/listTable', function (Request $request, Response $response, array $args) {
        global $uploaddir, $download_url;



        $result = Array();
        $userDepto = '';
        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        if($userType != 'alumno') $userDepto = getCurrentUserDepto();

        $userID = getCurrentUserID();
        $periodoAlumnoID = getCurrentAlumnoPeriodoID($this->db);
        $periodos_Alumno = getAlumnoPeriodos($this->db);
        //
        if($userType == 'alumno') {
            $query = 'SELECT m.* FROM tbl_academias a LEFT OUTER JOIN tbl_materias m ON m.col_id=a.col_materiaid WHERE a.col_alumnoid="'.$userID.'" AND a.col_periodoid="'.$periodoAlumnoID.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $materia = $sth->fetch(PDO::FETCH_OBJ);

            //$alumnoAcademia = substr(trim($materia->col_clave), 0, strlen($materia->col_clave)-1);
            //if(strlen(trim($materia->col_clave)) == 4) $alumnoAcademia = trim($materia->col_clave);
            $alumnoAcademia = $materia->col_clave;

            $query = 'SELECT m.* FROM tbl_talleres a LEFT OUTER JOIN tbl_materias m ON m.col_id=a.col_materiaid WHERE a.col_alumnoid="'.$userID.'" AND a.col_periodoid="'.$periodoAlumnoID.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $materia = $sth->fetch(PDO::FETCH_OBJ);
            // $alumnoTaller = substr(trim($materia->col_clave), 0, strlen($materia->col_clave)-1);
            // if(strlen(trim($materia->col_clave)) == 4) $alumnoTaller = trim($materia->col_clave);
            $alumnoTaller = $materia->col_clave;
        }

       $query = "SELECT * FROM tbl_actividades WHERE (col_fecha_inicio >= '".date('Y-m-d')."' OR col_fecha_fin >= '".date('Y-m-d')."') AND (col_fecha_inicio <= '".date('Y-m-d', strtotime("+25 day"))."' OR col_fecha_fin <= '".date('Y-m-d', strtotime("+25 day"))."') ORDER BY col_fecha_inicio ASC";
       $sth = $this->db->prepare($query);
       $sth->execute();
       $todos = $sth->fetchAll();

       $i = 0;
       foreach($todos as $item){
            $autorID = $item['col_created_by'];
            $query = 'SELECT u.col_maestro, u.col_firstname, u.col_lastname, d.col_nombre FROM tbl_users u LEFT OUTER JOIN tbl_departamentos d ON d.col_id=u.col_depto WHERE u.col_id="'.$autorID.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $autorData = $sth->fetch(PDO::FETCH_OBJ);

           $allowed = false;
           $visiblePara = unserialize($item['col_visible_excepto']);

           // $valor = "multi|".$item[col_materia_clave]."|".implode(',', $periodos);
           // $userType = 'alumno';
           // $userID = 564545454;
           //if($userType != 'administrativo'){
            $xxuserID = 'u'.$userID;
            if($userType == 'alumno') $xxuserID = $userID;


                   if(intval($item['col_visible']) == 100 && in_array($xxuserID, $visiblePara)) {
                       // Visible para persoinas especificadas en $visiblePara y para los creadores del evento
                       $allowed = true;

                    }else if(intval($item['col_visible']) == 200  && $userType == 'alumno' && strpos(strtoupper($visiblePara), 'MULTI|AC') !== false && $alumnoAcademia != '') {
                        $dataVisiblePara = explode('|', $visiblePara);

                        $claveVisiblePara = trim($dataVisiblePara[1]);

                        $periodosVisiblePara = explode(',', $dataVisiblePara[2]);
                        if(strtoupper($alumnoAcademia) == strtoupper($claveVisiblePara) && in_array($periodoAlumnoID, $periodosVisiblePara)){
                         $allowed = true;
                        }

                    }else if(intval($item['col_visible']) == 200  && $userType == 'alumno' && strpos(strtoupper($visiblePara), 'MULTI|TL') !== false && $alumnoTaller != '') {
                        $dataVisiblePara = explode('|', $visiblePara);

                        $claveVisiblePara = trim($dataVisiblePara[1]);

                        $periodosVisiblePara = explode(',', $dataVisiblePara[2]);
                        if(strtoupper($alumnoTaller) == strtoupper($claveVisiblePara) && in_array($periodoAlumnoID, $periodosVisiblePara)){
                         $allowed = true;
                        }
                   }else if(intval($item['col_visible']) == 200  && $userType == 'alumno' && (($visiblePara == $periodoAlumnoID) ||  (is_array($visiblePara) && inPeriodo($periodos_Alumno, $visiblePara)))) {
                       // Visible para los alumnos de los grupos especificadas en $visiblePara
                       $allowed = true;
                   }else {
                       if(intval($item['col_visible']) == 99 && $userType != 'alumno') {
                           // Visible para todos excepto para los alumnos
                           $allowed = true;
                       }else if(intval($item['col_visible']) == 98 && $userType == 'maestro') {
                           // Visible para todos los maestros
                           $allowed = true;
                       }else if(intval($item['col_visible']) == 97 && $userType == 'alumno') {
                           // Visible para todos los alumnos
                           $allowed = true;
                       }else if(intval($item['col_visible']) == 971 && $userType == 'alumno') {
                           // Visible para todos los alumnos LD semestral
                            $la_dataPeriodo = getPeriodo($periodoAlumnoID, $this->db, false);
                            if($la_dataPeriodo->col_modalidad == 1) $allowed = true;

                        }else if(intval($item['col_visible']) == 972 && $userType == 'alumno') {
                            // Visible para todos los alumnos LD cuatrimaestral
                             $la_dataPeriodo = getPeriodo($periodoAlumnoID, $this->db, false);
                             if($la_dataPeriodo->col_modalidad == 2) $allowed = true;

                        }else if(intval($item['col_visible']) == 973 && $userType == 'alumno') {
                            // Visible para todos los alumnos maestrias
                             $la_dataPeriodo = getPeriodo($periodoAlumnoID, $this->db, false);
                             if($la_dataPeriodo->col_modalidad == 3) $allowed = true;

                        }else if(intval($item['col_visible']) == 974 && $userType == 'alumno') {
                            // Visible para todos los alumnos doctorado
                             $la_dataPeriodo = getPeriodo($periodoAlumnoID, $this->db, false);
                             if($la_dataPeriodo->col_modalidad == 4) $allowed = true;

                       }else if(intval($item['col_visible']) == 96 && $userType == 'administrativo') {
                           // Visible para todos los maestros
                           $allowed = true;
                       }else if(intval($item['col_visible']) == $userDepto){
                           // Visible para administradores segun departamentos
                           $allowed = true;
                       }else{
                           $allowed = false;
                       }
                   }

                   if($userID == $item['col_created_by']) $allowed = true; // Siempre mostrar las actividades si el usuario es su autor


           //}else{
               //$allowed = true;
           //}



           if($allowed == true){

               $result[$i]['debug'] = $item;
               $result[$i]['periodoAlumnoID'] = $periodoAlumnoID;
               $result[$i]['alumnoAcademia'] = $alumnoAcademia;
               $result[$i]['alumnoTaller'] = $alumnoTaller;
               $result[$i]['id'] = $item['col_id'];
               $result[$i]['title'] = fixEncode($item['col_titulo']);

                if($autorData->col_maestro == 1) {
                    $result[$i]['materia'] = getMiMateriaPorMaestro($item['col_created_by'], $this->db, $item['col_id']);
                }else{
                    $result[$i]['materia'] = fixEncode($autorData->col_nombre);
                }


               $result[$i]['description'] = '<p>'.nl2br(makeClickeable(fixEncode($item['col_descripcion']))).'</p>';
               $result[$i]['fecha'] = (date('Y-m-d') == $item['col_fecha_inicio']?'<span class="badge badge-info">Hoy</span>':$item['col_fecha_inicio']);
               $nombre_archivo = htmlentities(fixEncode($item['col_archivo_nombre']));
               if(strlen($nombre_archivo) > 15){
                   $nombre_archivo = substr($nombre_archivo, 0, 15).'...';
               }
               $result[$i]['archivo'] =  ($item['col_archivo']?$nombre_archivo.' <a target="_blank" href="'.$download_url.$item['col_archivo'].'" class="pull-right"><i class="fas fa-download text-primary"></i></a>':'');
               if($userType == 'alumno' && esMaestro($item['col_created_by'], $this->db) && in_array($item['col_tipo'], array(1,2,3,4,10,11,12))){
                $result[$i]['opciones'] = '<a class="opcion-table" title="Subir Archivo" href="#/pages/perfil/subir/'.$item['col_id'].'"><i class="fas fa-file text-info"></i> Subir Tarea</a>';
               }
               $i++;
           }

       }
//exit;

       if($userType == 'alumno') {
        $sth = $this->db->prepare("SELECT * FROM tbl_altruista_actividades WHERE col_fecha >= NOW() ORDER BY col_id DESC");
        $sth->execute();
        $actividadesAltruistas = $sth->fetchAll();
        foreach($actividadesAltruistas as $item) {
            $sth = $this->db->prepare("SELECT * FROM tbl_altruista_integrantes WHERE col_alumnoid='".$userID."' AND col_grupo='".intval($item[col_grupo])."' AND col_group_periodoid='".intval($item[col_group_periodoid])."'");
            $sth->execute();
            if($sth->rowCount()) {
                $result[$i]['id'] = $item['col_id'];
                $result[$i]['title'] = fixEncode($item['col_titulo']);
                $result[$i]['materia'] = 'Actividad Altruista';
                $result[$i]['description'] = '<p>'.nl2br(makeClickeable(fixEncode($item['col_descripcion']))).'</p>';
                $result[$i]['fecha'] = (date('Y-m-d') == $item['col_fecha']?'<span class="badge badge-info">Hoy</span>':$item['col_fecha']);
                $result[$i]['archivo'] =  '';
                $i++;
            }
        }
       }

       return $this->response->withJson($result);

   });

    $this->get('/list', function (Request $request, Response $response, array $args) {
        global $uploaddir, $download_url;

         $userDepto = getCurrentUserDepto();
         $userType = getCurrentUserType(); // maestro - administrativo - alumno
         $userID = getCurrentUserID();
         $periodoAlumnoID = getCurrentAlumnoPeriodoID($this->db);
         $periodos_Alumno = getAlumnoPeriodos($this->db);

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
            11 => "Actividad en Clase",
            12 => "Proyecto posgrado");

         if($userType == 'alumno') {
            $query = 'SELECT m.* FROM tbl_academias a LEFT OUTER JOIN tbl_materias m ON m.col_id=a.col_materiaid WHERE a.col_alumnoid="'.$userID.'" AND a.col_periodoid="'.$periodoAlumnoID.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $materia = $sth->fetch(PDO::FETCH_OBJ);

            $alumnoAcademiaReal = $materia->col_clave;
            $alumnoAcademia = trim($materia->col_clave);

            $query = 'SELECT m.* FROM tbl_talleres a LEFT OUTER JOIN tbl_materias m ON m.col_id=a.col_materiaid WHERE a.col_alumnoid="'.$userID.'" AND a.col_periodoid="'.$periodoAlumnoID.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $materia = $sth->fetch(PDO::FETCH_OBJ);

            $alumnoTallerReal = $materia->col_clave;
            $alumnoTaller = trim($materia->col_clave);

        }


        if(isset($_REQUEST['own']) && $_REQUEST['own'] == 'true') {
            $__periodosActivos = getCurrentPeriodos($this->db);
            if($__periodosActivos) {
                foreach($__periodosActivos as $iperiodo){
                    $checkQuery = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_maestroid="'.$userID.'" AND col_periodoid="'.$iperiodo.'"';
                    $sthCheckPeriodo = $this->db->prepare($checkQuery);
                    $sthCheckPeriodo->execute();
                    if($sthCheckPeriodo->rowCount() > 0){
                        $periodosWhereArray[] = "col_visible_excepto LIKE '%".$iperiodo."%'";
                    }else if($userType == 'administrativo') {
                        $periodosWhereArray[] = "col_visible_excepto LIKE '%".$iperiodo."%'";
                    }
                }
            }
            if($periodosWhereArray) {
                $periodosWhere = implode(' OR ', $periodosWhereArray);
                $query = "SELECT * FROM tbl_actividades WHERE col_created_by='".$userID."' AND col_visible!='200' OR (col_visible='200' AND ".$periodosWhere.") ORDER BY col_fecha_inicio DESC";
            }else{
                $query = "SELECT * FROM tbl_actividades WHERE col_created_by='".$userID."' AND col_visible!='200' OR col_visible='200' ORDER BY col_fecha_inicio DESC";
            }
            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();

        } else {


            $__periodosActivos = getCurrentPeriodos($this->db);
            if($__periodosActivos) {
                foreach($__periodosActivos as $iperiodo){
                    $periodosWhereArray[] = "col_visible_excepto LIKE '%".$iperiodo."%'";
                }
            }

                if(count($periodosWhereArray) > 0) {
                    $periodosWhere = implode(' OR ', $periodosWhereArray);
                    $sth = $this->db->prepare("SELECT * FROM tbl_actividades WHERE col_visible!='200' OR (col_visible='200' AND ".$periodosWhere.") ORDER BY col_id DESC");
                }else{
                    $sth = $this->db->prepare("SELECT * FROM tbl_actividades WHERE col_visible!='200' OR col_visible='200' ORDER BY col_id DESC");
                }
                $sth->execute();
                $todos = $sth->fetchAll();

        }
        // $sth->execute();
        // $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){

            if(isset($_REQUEST['filtrar'])){
                if($_REQUEST['filtrar'] == 'semana' && strtotime($item['col_fecha_inicio']) > strtotime('+5 days')) continue;
                if($_REQUEST['filtrar'] == 'proximas' && strtotime($item['col_fecha_inicio']) <= strtotime('+5 days')) continue;
                // echo date('Y-m-d', strtotime('+7 days'));exit;
            }

            $allowed = false;
            $autorID = $item['col_created_by'];
            $query = 'SELECT u.col_maestro, u.col_firstname, u.col_lastname, d.col_nombre FROM tbl_users u LEFT OUTER JOIN tbl_departamentos d ON d.col_id=u.col_depto WHERE u.col_id="'.$autorID.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $autorData = $sth->fetch(PDO::FETCH_OBJ);

            $visiblePara = unserialize($item['col_visible_excepto']);
            // $userType = 'alumno';
            // $userID = 564545454;
            //if($userType != 'administrativo'){
                $xxuserID = 'u'.$userID;
                if($userType == 'alumno') $xxuserID = $userID;

                    if(intval($item['col_visible']) == 100 && in_array($xxuserID, $visiblePara)) {
                        // Visible para persoinas especificadas en $visiblePara y para los creadores del evento
                        $allowed = true;
                    }else if(intval($item['col_visible']) == 200  && $userType == 'alumno' && strpos(strtoupper($visiblePara), 'MULTI|AC') !== false && $alumnoAcademia != '') {
                        $dataVisiblePara = explode('|', $visiblePara);

                        $claveVisiblePara = trim($dataVisiblePara[1]);

                        $periodosVisiblePara = explode(',', $dataVisiblePara[2]);
                        if(strtoupper($alumnoAcademia) == strtoupper($claveVisiblePara) && in_array($periodoAlumnoID, $periodosVisiblePara)){
                         $allowed = true;
                        }
                     }else if(intval($item['col_visible']) == 200  && $userType == 'alumno' && strpos(strtoupper($visiblePara), 'MULTI|TL') !== false && $alumnoTaller != '') {
                         $dataVisiblePara = explode('|', $visiblePara);

                        $claveVisiblePara = trim($dataVisiblePara[1]);

                         $periodosVisiblePara = explode(',', $dataVisiblePara[2]);
                         if(strtoupper($alumnoTaller) == strtoupper($claveVisiblePara) && in_array($periodoAlumnoID, $periodosVisiblePara)){
                          $allowed = true;
                         }
                    }else if(intval($item['col_visible']) == 200  && $userType == 'alumno' && (($visiblePara == $periodoAlumnoID) ||  (is_array($visiblePara) && inPeriodo($periodos_Alumno, $visiblePara)))) {
                        // Visible para los alumnos de los grupos especificadas en $visiblePara
                        $allowed = true;
                    }else {
                        if(intval($item['col_visible']) == 99 && $userType != 'alumno') {
                            // Visible para todos excepto para los alumnos
                            $allowed = true;
                        }else if(intval($item['col_visible']) == 98 && $userType == 'maestro') {
                            // Visible para todos los maestros
                            $allowed = true;
                        }else if(intval($item['col_visible']) == 97 && $userType == 'alumno') {
                            // Visible para todos los alumnos
                            $allowed = true;
                        }else if(intval($item['col_visible']) == 971 && $userType == 'alumno') {
                            // Visible para todos los alumnos LD semestral
                             $la_dataPeriodo = getPeriodo($periodoAlumnoID, $this->db, false);
                             if($la_dataPeriodo->col_modalidad == 1) $allowed = true;

                         }else if(intval($item['col_visible']) == 972 && $userType == 'alumno') {
                             // Visible para todos los alumnos LD cuatrimaestral
                              $la_dataPeriodo = getPeriodo($periodoAlumnoID, $this->db, false);
                              if($la_dataPeriodo->col_modalidad == 2) $allowed = true;

                         }else if(intval($item['col_visible']) == 973 && $userType == 'alumno') {
                             // Visible para todos los alumnos maestrias
                              $la_dataPeriodo = getPeriodo($periodoAlumnoID, $this->db, false);
                              if($la_dataPeriodo->col_modalidad == 3) $allowed = true;

                         }else if(intval($item['col_visible']) == 974 && $userType == 'alumno') {
                             // Visible para todos los alumnos doctorado
                              $la_dataPeriodo = getPeriodo($periodoAlumnoID, $this->db, false);
                              if($la_dataPeriodo->col_modalidad == 4) $allowed = true;

                        }else if(intval($item['col_visible']) == 96 && $userType == 'administrativo') {
                            // Visible para todos los maestros
                            $allowed = true;
                        }else if(intval($item['col_visible']) == $userDepto){
                            // Visible para administradores segun departamentos
                            $allowed = true;
                        }else{
                            $allowed = false;
                        }
                    }

                    if($userID == $item['col_created_by']) $allowed = true; // Siempre mostrar las actividades si el usuario es su autor


            //}else{
                //$allowed = true;
            //}

            if($allowed == true){
                $result[$i]['col_id'] = $item['col_id'];
                $result[$i]['id'] = $item['col_id'];
                $result[$i]['created_by'] = $item['col_created_by'];
                $result[$i]['permissions'] = intval($item['col_visible']);
                $result[$i]['permissions_specific'] = unserialize($item['col_visible_excepto']);
                if(($userID == $item['col_created_by']) && (isset($_REQUEST['own']) && $_REQUEST['own'] != 'true')){
                    $result[$i]['title'] = "(*) ".fixEncode($item['col_titulo']);
                }else{
                    $result[$i]['title'] = fixEncode($item['col_titulo']);
                }
                $materia = getMiMateriaPorMaestro($item['col_created_by'], $this->db, $item['col_id']);
                if($materia != ''){
                    $result[$i]['description'] = '<p>'.nl2br(makeClickeable(fixEncode($item['col_descripcion']))).'</p><br/><p><small>Materia: '.$materia.'</small></p>';
                }else{
                    $result[$i]['description'] = '<p>'.nl2br(makeClickeable(fixEncode($item['col_descripcion']))).'</p>';
                }
                $result[$i]['description'] .= '<p><b>Fecha:</b> '.fechaTexto($item['col_fecha_inicio']);
                if(strtotime($item['col_fecha_fin']) > strtotime($item['col_fecha_inicio'])) $result[$i]['description'] .= ' a '.fechaTexto($item['col_fecha_fin']);
                $result[$i]['description'] .= '</p>';

                $result[$i]['archivo_url'] = $download_url.$item['col_archivo'];
                $result[$i]['archivo_name'] = fixEncode($item['col_archivo_nombre']);

                $result[$i]['start'] = $item['col_fecha_inicio'];
                if(strtotime($item['col_fecha_fin']) > strtotime($item['col_fecha_inicio'])) {
                    $result[$i]['end'] = date('Y-m-d', strtotime($item['col_fecha_fin'].' +1 day'));
                }else{
                    $result[$i]['end'] = $item['col_fecha_fin'];
                }
                $result[$i]['backgroundColor'] =  $item['col_color_fondo'];
                $result[$i]['borderColor'] =  $item['col_color_fondo'];
                $result[$i]['textColor'] =  $item['col_color_letra'];
                // if($userID == 1){
                //     $result[$i]['debug'] =  $_SERVER;
                // }
                $nombre_archivo = htmlentities(fixEncode($item['col_archivo_nombre']));
                if(strlen($nombre_archivo) > 15){
                    $nombre_archivo = substr($nombre_archivo, 0, 15).'...';
                }

                $result[$i]['archivo'] =  ($item['col_archivo']?$nombre_archivo.' <a target="_blank" href="'.$download_url.$item['col_archivo'].'" class="pull-right"><i class="fas fa-download text-primary"></i></a>':'');
                $result[$i]['grupo'] =  '';
                if($userType == 'maestro' && $item['col_tipo'] > 0){
                    if($item['col_visible_excepto'] == ''){
                        $result[$i]['grupo'] = 'Todos';
                    }else{
                        $elID = unserialize($item['col_visible_excepto']);
                        if(intval($elID) > 0) {
                            // $queryPeriodo = 'SELECT * FROM tbl_periodos WHERE col_id="'.$elID.'"';
                            // $sthPeriodo = $this->db->prepare($queryPeriodo);
                            // $sthPeriodo->execute();
                            // $periodoData = $sthPeriodo->fetch(PDO::FETCH_OBJ);
                            $periodoData = getPeriodo($elID, $this->db, false);

                            $result[$i]['grupo'] =  $periodoData->col_grado."-".$periodoData->col_grupo;
                            $_modalidades = array(1=>'Semestral', 2=>'Cuatrimestral', 3=>'Maestría', 4=>'Doctorado');
                            $result[$i]['grupo'] .= ' ('.$_modalidades[$periodoData->col_modalidad].')';

                            if($periodoData->isPosgrado == 1) {
                                $tiposActividades[7] = 'Acta de calificación posgrado';
                            }
                        }else{
                            $result[$i]['grupo'] =  'Multigrupo';
                        }
                    }
                    if($autorData->col_maestro == 1) {
                        if(unserialize($item['col_visible_excepto']) == ''){
                            $result[$i]['materia'] = 'Todos mis grupos';
                        }else{
                            $result[$i]['materia'] = getMateriaByActividad($item['col_visible_excepto'], $this->db, 0, $item['col_id']);

                        }
                    }else{
                        $result[$i]['materia'] = fixEncode($autorData->col_nombre);
                    }
                }
                if($autorData->col_maestro == 1) {
                    $result[$i]['tipo'] = $tiposActividades[$item['col_tipo']];
                } else {
                    $result[$i]['tipo'] = 'Actividad';
                }

                // if(isset($_REQUEST['own']) && $_REQUEST['own'] == 'true' && (strtotime('now') > strtotime($item['col_fecha_inicio'])) && (strtotime('now') > strtotime($item['col_fecha_fin']))){
                if(isset($_REQUEST['own']) && $_REQUEST['own'] == 'true'){
                    $css = '';
                    $queryTareas = 'SELECT * FROM tbl_actividades_tareas WHERE col_actividadid="'.intval($item['col_id']).'" AND col_calificacion="0.00"';
                    $sthareas = $this->db->prepare($queryTareas);
                    $sthareas->execute();
                    if($sthareas->rowCount()) $css = 'bullet';
                    $result[$i]['opciones'] = '<a class="singleOption opcion-table '.$css.'" title="Asignar Calificaciones" href="#/pages/actividades/calificar/'.$item['col_id'].'"><i class="far fa-check-square"></i></a>';
                }

                $i++;
            }
        }

        if($userType == 'alumno') {
            $sth = $this->db->prepare("SELECT * FROM tbl_altruista_actividades WHERE col_fecha >= NOW() ORDER BY col_id DESC");
            $sth->execute();
            $actividadesAltruistas = $sth->fetchAll();
            foreach($actividadesAltruistas as $item) {
                $sth = $this->db->prepare("SELECT * FROM tbl_altruista_integrantes WHERE col_alumnoid='".$userID."' AND col_grupo='".intval($item[col_grupo])."' AND col_group_periodoid='".intval($item[col_group_periodoid])."'");
                $sth->execute();
                if($sth->rowCount()) {
                    $result[$i]['id'] = $item['col_id'];
                    $result[$i]['title'] = 'Actividad Altruista: '.fixEncode($item['col_titulo']);
                    $result[$i]['description'] = '<p>'.nl2br(makeClickeable(fixEncode($item['col_descripcion']))).'</p>';
                    $result[$i]['archivo'] =  '';
                    $result[$i]['archivo_url'] = '';
                    $result[$i]['archivo_name'] = '';
                    $result[$i]['start'] = $item['col_fecha'];
                    $result[$i]['end'] = $item['col_fecha'];
                    $result[$i]['backgroundColor'] =  '#d3158a';
                    $result[$i]['borderColor'] =  '#d3158a';
                    $result[$i]['textColor'] =  '#ffffff';
                    $i++;
                }
            }
           }

        if(count($result) == 0) return;
        return $this->response->withJson($result);

    });

    $this->post('/filtro', function (Request $request, Response $response, array $args) {
        $input = json_decode($request->getBody());
        $sth = $this->db->prepare("SELECT ac.*, ma.col_nombre AS materia, gr.col_nombre AS grupo FROM tbl_actividades ac "
                            ."LEFT OUTER JOIN tbl_materias ma ON ma.col_id=ac.col_materiaid "
                            ."LEFT OUTER JOIN tbl_grupos gr ON gr.col_id=ac.col_grupoid "
                            ."WHERE ac.col_grupoid='".$input->grupo."' AND ac.col_materiaid='".$input->materia."' ORDER BY ac.col_id");
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['col_nombre'] = fixEncode($item['col_nombre']);
            $result[$i]['col_descripcion'] = fixEncode($item['col_descripcion']);
            $result[$i]['col_fecha_entrega'] = $item['col_fecha_entrega'];
            $result[$i]['materia'] = fixEncode($item['materia']);
            $result[$i]['col_materiaid'] = $item['col_materiaid'];
            $result[$i]['grupo'] = fixEncode($item['grupo']);
            $result[$i]['col_grupoid'] = $item['col_grupoid'];
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/getAlumnos', function (Request $request, Response $response, array $args) {
        global $download_url;
        $maestroID = getCurrentUserID();

        $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.intval($_REQUEST['id']).'" AND col_created_by="'.$maestroID.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        if($sth->rowCount() == 0) {
            $result['error'] = 'Esta actividad de te pertenece';
            return $this->response->withJson($result);
        }
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $visibleData = unserialize(stripslashes($data->col_visible_excepto));
        if(intval($visibleData) > 0) {
            $periodoData = getPeriodo(intval($visibleData), $this->db, false);
        }else{
            $_arrayVD = explode('|', $visibleData);
            $_periodosVD = explode(',', $_arrayVD[2]);
            $periodoData = getPeriodo(intval($_periodosVD[0]), $this->db, false);
        }


        $_reponse['tipoActiviad'] = $data->col_tipo;


        $materiaID = getMateriaByActividadID($data->col_visible_excepto, $this->db, intval($data->col_created_by), intval($_REQUEST['id']));
        $query = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaID.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $materiaData = $sth->fetch(PDO::FETCH_OBJ);
        $_reponse['materiaActividad'] = fixEncode($materiaData->col_nombre);

        $ponderacionExamen = 80;
        if(($data->col_tipo == 12 || $data->col_tipo == 7) && $periodoData->isPosgrado == 1) {
            $ponderacion = getPonderacion($materiaID, $periodoData->col_id, $this->db);
            $ponderacionExamen = removePorcentaje($ponderacion['examen']);
        }else{
            $ponderacion = unserialize(stripslashes($data->col_ponderacion));
        }
        $_reponse['ponderacionDebug'] = $ponderacion;
        $_reponse['ponderacionExamenDebug'] = $ponderacionExamen;
        $_reponse['ponderacionMateriaDebug'] = getPonderacion($materiaID, $periodoData->col_id, $this->db);
        $_fechaActividad = $data->col_fecha_inicio;



        $curricular = 'si';
        if(in_array(strtoupper(substr($materiaData->col_clave, 0, 2)), array('TL', 'AC', 'CL', 'TR')) || $periodoData->col_modalidad > 1) {
            $curricular = 'no';
        }

        $ignorarParticipaciones = false;
        if(in_array(strtoupper(substr($materiaData->col_clave, 0, 2)), array('AC'))) {
            $ignorarParticipaciones = true;
        }

        $_reponse['curricular'] = $curricular;

        if($_reponse['tipoActiviad'] >=5 AND $_reponse['tipoActiviad'] <=7 AND $curricular == 'si') {
            $ordenExamen = getExamenOrden($data->col_id, $this->db);
            $_reponse['ordenExamen'] = $ordenExamen;
            // $_reponse['ordenExamenID'] = $data->col_materiaid;
        }

        if(removePorcentaje($ponderacion['tarea']) > 0) $_ponderacion[] .= 'Tareas: '.removePorcentaje($ponderacion['tarea']).'%';
        if(removePorcentaje($ponderacion['investigacion']) > 0) $_ponderacion[] .= 'Investigaciones: '.removePorcentaje($ponderacion['investigacion']).'%';
        if(removePorcentaje($ponderacion['lecturas']) > 0) $_ponderacion[] .= 'Lecturas: '.removePorcentaje($ponderacion['lecturas']).'%';
        if(removePorcentaje($ponderacion['debates']) > 0) $_ponderacion[] .= 'Debates: '.removePorcentaje($ponderacion['debates']).'%';

        if(removePorcentaje($ponderacion['proyecto']) > 0) $_ponderacion[] .= 'Proyecto: '.removePorcentaje($ponderacion['proyecto']).'%';
        if(removePorcentaje($ponderacion['participacion']) > 0) $_ponderacion[] .= 'Participaciones: '.removePorcentaje($ponderacion['participacion']).'%';
        if(removePorcentaje($ponderacion['examen']) > 0) $_ponderacion[] .= 'Exámen: '.removePorcentaje($ponderacion['examen']).'%';
        $_reponse['ponderacion'] = implode(',', $_ponderacion);


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
            11 => "Actividad en Clase",
            12 => "Proyecto posgrado");

        if($periodoData->isPosgrado == 1) {
            $tiposActividades[7] = 'Acta de calificación posgrado';
        }

        $_reponse['tipoActividadNombre'] = $tiposActividades[$data->col_tipo];
        $_reponse['fechaActividad'] = fechaTexto($data->col_fecha_inicio);
        $todosLosPeriodos = getCurrentPeriodos($this->db);
        if($data->col_visible == 200) {
            $grupo =  unserialize(stripslashes($data->col_visible_excepto));
            // print_r($grupo);
            if(strpos(strtoupper($grupo), 'MULTI|AC') !== false) {
                $_reponse['tipoMateria'] = 'AC';
                $dataVisiblePara = explode('|', $grupo);

                //$claveVisiblePara = claveMateria($dataVisiblePara[1]);
                $claveVisiblePara = $dataVisiblePara[1];

                $periodosVisiblePara = explode(',', $dataVisiblePara[2]);

                $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE  col_periodoid IN ('.implode(',', $todosLosPeriodos).') AND col_materia_clave="'.$dataVisiblePara[1].'" AND col_maestroid="'.intval($data->col_created_by).'"';
                $sth = $this->db->prepare($query);
                $sth->execute();
                $preDataTaxMaestro = $sth->fetch(PDO::FETCH_OBJ);

                $preDataPeriodo = getPeriodo($preDataTaxMaestro->col_periodoid, $this->db, false);


                $queryMateria = 'SELECT * FROM tbl_materias WHERE col_clave="'.trim($preDataTaxMaestro->col_materia_clave).'" AND col_carrera="'.$preDataPeriodo->col_carreraid.'" AND col_plan_estudios="'.$preDataPeriodo->col_plan_estudios.'"';
                $sth = $this->db->prepare($queryMateria);
                $sth->execute();
                $laMateria = $sth->fetch(PDO::FETCH_OBJ);

                //$queryx = 'SELECT * FROM tbl_materias WHERE col_nombre LIKE "%'.trim($laMateria->col_nombre).'%" AND col_clave LIKE "'.trim($claveVisiblePara).'%" AND col_carrera="'.$preDataPeriodo->col_carreraid.'" AND col_plan_estudios="'.$preDataPeriodo->col_plan_estudios.'"';
                $queryx = 'SELECT * FROM tbl_materias WHERE col_clave="'.trim($claveVisiblePara).'" AND col_carrera="'.$preDataPeriodo->col_carreraid.'" AND col_plan_estudios="'.$preDataPeriodo->col_plan_estudios.'"';
                $sthx = $this->db->prepare($queryx);
                $sthx->execute();
                $dataMateriaMulti = $sthx->fetchAll();
                unset($multis);
                foreach($dataMateriaMulti as $mm) {
                    $multis[] = $mm['col_id'];
                }
                $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');
                $losPeriodos = getCurrentPeriodos($this->db, $types[$preDataPeriodo->col_modalidad]);

                $query = "SELECT p.col_id AS periodoAlumno, t.col_id, t.col_alumnoid, t.col_groupid, a.col_nombres, a.col_apellidos, a.col_control, a.col_correo, p.col_grado AS semestre, p.col_grupo As grupo ".
                "FROM tbl_alumnos_taxonomia t ".
                "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                "LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid ".
                "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_academias ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
                "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";

            } else if(strpos(strtoupper($grupo), 'MULTI|TL') !== false) {
                $_reponse['tipoMateria'] = 'TL';
                $dataVisiblePara = explode('|', $grupo);

                // $claveVisiblePara = claveMateria($dataVisiblePara[1]);
                $claveVisiblePara = $dataVisiblePara[1];
                $periodosVisiblePara = explode(',', $dataVisiblePara[2]);

                $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_periodoid IN ('.implode(',', $todosLosPeriodos).') AND col_materia_clave="'.$dataVisiblePara[1].'" AND col_maestroid="'.intval($data->col_created_by).'"';
                $sth = $this->db->prepare($query);
                $sth->execute();
                $preDataTaxMaestro = $sth->fetch(PDO::FETCH_OBJ);

                $preDataPeriodo = getPeriodo($preDataTaxMaestro->col_periodoid, $this->db, false);

                $queryMateria = 'SELECT * FROM tbl_materias WHERE col_clave="'.trim($preDataTaxMaestro->col_materia_clave).'" AND col_carrera="'.$preDataPeriodo->col_carreraid.'" AND col_plan_estudios="'.$preDataPeriodo->col_plan_estudios.'"';
                $sth = $this->db->prepare($queryMateria);
                $sth->execute();
                $laMateria = $sth->fetch(PDO::FETCH_OBJ);

                // $queryx = 'SELECT * FROM tbl_materias WHERE col_nombre LIKE "%'.trim($laMateria->col_nombre).'%" AND col_clave LIKE "'.trim($claveVisiblePara).'%" AND col_carrera="'.$preDataPeriodo->col_carreraid.'" AND col_plan_estudios="'.$preDataPeriodo->col_plan_estudios.'"';
                $queryx = 'SELECT * FROM tbl_materias WHERE col_clave="'.trim($claveVisiblePara).'" AND col_carrera="'.$preDataPeriodo->col_carreraid.'" AND col_plan_estudios="'.$preDataPeriodo->col_plan_estudios.'"';
                $sthx = $this->db->prepare($queryx);
                $sthx->execute();
                $dataMateriaMulti = $sthx->fetchAll();
                unset($multis);
                foreach($dataMateriaMulti as $mm) {
                    $multis[] = $mm['col_id'];
                }
                $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');
                // echo $preDataTaxMaestro->col_periodoid;
                // echo $preDataPeriodo->col_modalidad;
                // echo $types[$preDataPeriodo->col_modalidad];exit;
                $losPeriodos = getCurrentPeriodos($this->db, $types[$preDataPeriodo->col_modalidad]);

                $query = "SELECT p.col_id AS periodoAlumno, t.col_id, t.col_alumnoid, t.col_groupid, a.col_nombres, a.col_apellidos, a.col_control, a.col_correo, p.col_grado AS semestre, p.col_grupo As grupo ".
                "FROM tbl_alumnos_taxonomia t ".
                "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                "LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid ".
                "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_talleres ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid ".
                "WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
                "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";

            }else{
                $_reponse['tipoMateria'] = 'R';
                $result['grupo'] = $grupo;
                $query = 'SELECT p.col_id AS periodoAlumno, t.col_id, t.col_alumnoid, t.col_groupid, a.col_nombres, a.col_apellidos, a.col_control, a.col_correo, p.col_grado AS semestre, p.col_grupo As grupo '.
                'FROM tbl_alumnos_taxonomia t '.
                'LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid '.
                'LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid '.
                'WHERE a.col_estatus="activo" AND t.col_periodoid="'.$grupo.'" '.
                'GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC';
            }
        }

        if($data->col_visible == 89) {
            $result['grupo'] = '-1'; // Todos los grupos
        }

        $result['periodoData->col_modalidad'] = $periodoData->col_id;


        // $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_actividadid="'.intval($_REQUEST['id']).'"';
        // $sth = $this->db->prepare($query);
        // $sth->execute();

        if($_reponse['tipoActiviad'] == 8) {
            $alumnosReprobados = getAlumnosReprobadosPorActividad(intval($_REQUEST['id']), $this->db);
            $_reponse['reprobados'] = $alumnosReprobados;
        }

        if($_reponse['tipoActiviad'] == 9) {
            $alumnosReprobadosExt = getAlumnosReprobadosExtPorActividad(intval($_REQUEST['id']), $this->db);
            $_reponse['reprobados'] = $alumnosReprobadosExt;
        }

        $sth = $this->db->prepare($query);
        $sth->execute();

        $todos = $sth->fetchAll();
        $a = 0;

        foreach($todos as $item){
            $finalDebugArr = '';
            // if($maestroID == 18 && $item['col_alumnoid'] == 230)continue;
            // if($maestroID == 46 && $item['col_alumnoid'] == 211)continue;

            // if($item['col_alumnoid'] != 831)continue;

            if($_reponse['tipoActiviad'] == 8 && !in_array($item['col_alumnoid'], $alumnosReprobados)) continue;
            if($_reponse['tipoActiviad'] == 9 && !in_array($item['col_alumnoid'], $alumnosReprobadosExt)) continue;

            $calificacion = '0.00';
            $calificacionIntegra = '';
            $fechaCreo = '';
            $fechaActualizo = '';
            $hasSD = 0;
            $sdRazon = '';
            $hasSDME = '';
            $sdMERazon = '';
            $falsificacion = 0;
            $sinCalificar = 0;

            unset($archivos);
            $archivos = [];
            $archivosDetails = [];
            if($_reponse['tipoActiviad'] == 12){
                $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$item['col_alumnoid'].'" AND col_actividadid="'.intval($_REQUEST['id']).'" GROUP BY col_intento';
            }else{
                $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$item['col_alumnoid'].'" AND col_actividadid="'.intval($_REQUEST['id']).'"';
            }

            $sth_tt = $this->db->prepare($query);
            $sth_tt->execute();
            $tareas = $sth_tt->fetchAll();
            $i = 1;
            $ix = 0;
            foreach($tareas as $tarea){
                if($_reponse['tipoActiviad'] == 12){
                    $queryArchivos = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$item['col_alumnoid'].'" AND col_actividadid="'.intval($_REQUEST['id']).'" AND col_intento="'.$tarea['col_intento'].'"';
                    $sth_archivos = $this->db->prepare($queryArchivos);
                    $sth_archivos->execute();
                    $tareasArchivos = $sth_archivos->fetchAll();
                    $ar = 0;
                    foreach($tareasArchivos as $_archivo){
                        if($tarea['col_archivo'] != '') {
                            $archivos[$i] = '<a target="_blank" class="tarea-file" href="'.$download_url.'tareas/'.$_archivo['col_archivo'].'">Archivo #'.$i.'</a>';
                        }else{
                            $archivos[$i] = 'Sin Archivo';
                        }
                        $archivosDetails[$ix]['archivos'][$ar]['link'] = '<a target="_blank" class="tarea-file" href="'.$download_url.'tareas/'.$_archivo['col_archivo'].'">Archivo #'.($ar + 1).'</a>';
                        $archivosDetails[$ix]['archivos'][$ar]['nombre_archivo'] = $_archivo['col_archivo'];
                        $archivosDetails[$ix]['archivos'][$ar]['url'] = $download_url.'tareas/'.$_archivo['col_archivo'];
                        $archivosDetails[$ix]['archivos'][$ar]['number'] = ($ar + 1);
                        // $archivosDetails[$ix]['archivos'][$a]['xxx'] = $queryArchivos;
                        $i++;
                        $ar++;
                    }
                }else{
                    if($tarea['col_archivo'] != '') {
                        $archivos[$i] = '<a target="_blank" class="tarea-file" href="'.$download_url.'tareas/'.$tarea['col_archivo'].'">Archivo #'.$i.'</a>';
                        $archivosDetails[$ix]['link'] = '<a target="_blank" class="tarea-file" href="'.$download_url.'tareas/'.$tarea['col_archivo'].'">Archivo #'.$i.'</a>';
                        $archivosDetails[$ix]['nombre_archivo'] = $tarea['col_archivo'];
                        $archivosDetails[$ix]['url'] = $download_url.'tareas/'.$tarea['col_archivo'];
                        $i++;
                    }
                }

                $calificacion = corregirCalificacion($tarea['col_calificacion']);
                $calificacionIntegra = $tarea['col_calificacion'];
                $hasSD = $tarea['col_sd'];
                $sdRazon = $tarea['col_sd_razon'];
                $hasSDME = $tarea['col_sdme'];
                $sdMERazon = $tarea['col_sdme_razon'];

                $falsificacion = $tarea['col_falsificacion'];
                $fechaCreo = $tarea['col_created_at'];
                $fechaActualizo = $tarea['col_updated_at'];


                $archivosDetails[$ix]['intento'] = $tarea['col_intento'];
                $archivosDetails[$ix]['id'] = $tarea['col_id'];
                $archivosDetails[$ix]['calificacion'] = $calificacion;
                $archivosDetails[$ix]['calificacionIntegra'] = $calificacionIntegra;
                $archivosDetails[$ix]['hasSD'] = $hasSD;
                $archivosDetails[$ix]['sdRazon'] = $sdRazon;
                $archivosDetails[$ix]['hasSDME'] = $hasSDME;
                $archivosDetails[$ix]['sdMERazon'] = $sdMERazon;
                $archivosDetails[$ix]['falsificacion'] = $falsificacion;
                $archivosDetails[$ix]['fechaCreo'] = $fechaCreo;
                $archivosDetails[$ix]['fechaActualizo'] = $fechaActualizo;

                $archivosDetails[$ix]['retro'] = $tarea['col_retroalimentacion'];
                $archivosDetails[$ix]['fecha_retro'] = $tarea['col_fecha_retro'];
                $archivosDetails[$ix]['corrigio'] = $tarea['col_corrigio'];
                $archivosDetails[$ix]['fecha_corrigio'] = $tarea['col_fecha_correcion'];
                $archivosDetails[$ix]['fecha_subida'] = $tarea['col_fecha_subida'];
                $archivosDetails[$ix]['fecha_subida_nice'] = fechaTexto($tarea['col_fecha_subida'], 'F j, Y H:i');
                $archivosDetails[$ix]['estatus'] = $tarea['col_estatus'];
                if($tarea['col_estatus'] <= 1 && $_reponse['tipoActiviad'] == 12) {
                    $calificacion = '0.00';
                    $calificacionIntegra = '';
                    $sinCalificar++;
                }

                $ix++;
            }
            // Calculamos el 80%
            if(in_array(strtoupper(substr($materiaData->col_clave, 0, 2)), array('TL', 'AC', 'CL'))) {
                if($calificacion == '1.00') $calificacion = 10;
            }

            $finalIntegro = $calificacion;
            $result['alumnos'][$a]['finalIntegro'] = $finalIntegro;

            $final = ($ponderacionExamen*$calificacion)/10;
            $result['alumnos'][$a]['finalPreIntegroDebug'] = '('.$ponderacionExamen.' * '.$calificacion.') / 10';

            if($_reponse['tipoActiviad'] >=5 AND $_reponse['tipoActiviad'] <=7) {

                $totalParticipaciones = getTotalParticipaciones($item['col_alumnoid'], $materiaData->col_id, intval($_REQUEST['id']), $this->db, $_fechaActividad);
                if($periodoData->isPosgrado == 0){

                    $totalesActividades = getTotalesActividades($item['col_alumnoid'], intval($_REQUEST['id']), $this->db, $_fechaActividad);

                }else{

                    $totalesActividades = getTotalesActividadesPosgrados($item['col_alumnoid'], intval($_REQUEST['id']), $this->db);
                }

                if($curricular == 'si'){
                    if($_reponse['tipoActiviad'] == 5 OR $_reponse['tipoActiviad'] == 6){
                        if($ordenExamen == 1) $me = acreditaMEAcademias($item['col_alumnoid'], intval($_REQUEST['id']), $this->db);
                        if($ordenExamen == 2) $me = acreditaMETalleres($item['col_alumnoid'], intval($_REQUEST['id']), $this->db);
                        if($ordenExamen == 3) $me = acreditaMEClubLectura($item['col_alumnoid'], intval($_REQUEST['id']), $this->db);
                        if($ordenExamen == 4) $me = acreditaMETransversales($item['col_alumnoid'], intval($_REQUEST['id']), $this->db);
                        if($ordenExamen == 5) $me = acreditaMEPracticas($item['col_alumnoid'], intval($_REQUEST['id']), $_reponse['tipoActiviad'], $this->db);
                        if($ordenExamen == 7) $me = acreditaMEServicio($item['col_alumnoid'], intval($_REQUEST['id']), $_reponse['tipoActiviad'], $this->db);
                    }else if($_reponse['tipoActiviad'] == 7){

                        if($periodoData->col_grado < 7) {
                            if($ordenExamen == 1) $me = acreditaMEAcademias($item['col_alumnoid'], intval($_REQUEST['id']), $this->db);
                            if($ordenExamen == 2) $me = acreditaMEAltruista($item['col_alumnoid'], $this->db, $periodoData->col_id);
                            if($ordenExamen == 7) $me = acreditaMEPracticas($item['col_alumnoid'], intval($_REQUEST['id']), $_reponse['tipoActiviad'], $this->db);
                        }
                        if($periodoData->col_grado > 6) {
                            if($ordenExamen == 1) $me = acreditaMEAcademias($item['col_alumnoid'], intval($_REQUEST['id']), $this->db);
                            if($ordenExamen == 2) $me = acreditaMEAltruista($item['col_alumnoid'], $this->db, $periodoData->col_id);
                        }
                    }

                }


                $tareas = 0;
                $investigacion = 0;
                $lecturas = 0;
                $debates = 0;
                $proyectos = 0;
                $participacion = 0;
                $tareasDebug = $totalesActividades;

                if(is_array($totalesActividades)) {
                    if(removePorcentaje($ponderacion['tarea']) > 0) {
                        if($totalesActividades['enclase']['calificacion'] > 0) $totalesActividades['enclase']['calificacion'] = $totalesActividades['enclase']['calificacion'] * 10;
                        $division = (($totalesActividades['tarea']['calificacion'] + $totalesActividades['enclase']['calificacion']) / ($totalesActividades['tarea']['total'] + $totalesActividades['enclase']['total'])) * 10;

                        $tareas = (removePorcentaje($ponderacion['tarea'])*$division)/100;
                        // if($item['col_alumnoid'] == '583') {
                        //     echo '----'.$totalesActividades['enclase']['calificacion'].' - '.$totalesActividades['enclase']['total'].'<br/>';
                        //     echo '----'.$totalesActividades['tarea']['calificacion'].' - '.$totalesActividades['tarea']['total'].'<br/>';
                        //     echo $ponderacion['tarea'].'-'.removePorcentaje($ponderacion['tarea']).'-'.$division.'-100<br/>';
                        //     echo $tareas;
                        //     exit;
                        // }

                    }
                    if(removePorcentaje($ponderacion['investigacion']) > 0) {
                        $result['alumnos'][$a]['las_tareas'] = $totalesActividades['investigacion'];
                        $division = ($totalesActividades['investigacion']['calificacion'] / $totalesActividades['investigacion']['total']) * 10;
                        $investigacion = (removePorcentaje($ponderacion['investigacion'])*$division)/100;
                    }
                    if(removePorcentaje($ponderacion['lecturas']) > 0) {
                        $division = ($totalesActividades['lectura']['calificacion'] / $totalesActividades['lectura']['total']) * 10;
                        $lecturas = (removePorcentaje($ponderacion['lecturas'])*$division)/100;
                    }
                    if(removePorcentaje($ponderacion['debates']) > 0) {
                        $division = ($totalesActividades['debates']['calificacion'] / $totalesActividades['debates']['total']) * 10;
                        $debates = (removePorcentaje($ponderacion['debates'])*$division)/100;
                    }
                    }

                if(removePorcentaje($ponderacion['participacion']) > 0) {
                    $division = ($totalParticipaciones['suma'] / $totalParticipaciones['max']) * 10;
                    // $participacionDebug = $totalParticipaciones['suma'].' - '.$totalParticipaciones['max'].' - '.$totalParticipaciones['fechaInicio'].' - '.$totalParticipaciones['fechaFin'];
                    $participacionDebug = $totalParticipaciones['fechaInicio'].' - '.$totalParticipaciones['fechaFin'];
                    $participacion = (removePorcentaje($ponderacion['participacion'])*$division)/10;

                }

                if(is_array($totalesActividades)) {
                    if(removePorcentaje($ponderacion['proyecto']) > 0) {
                        $division = ($totalesActividades['proyecto']['calificacion'] / $totalesActividades['proyecto']['total']) * 10;
                        $proyectos = (removePorcentaje($ponderacion['proyecto'])*$division)/100;
                    }

                    if(removePorcentaje($ponderacion['examen']) > 0) {
                        $division = ($totalesActividades['examen_final']['calificacion'] / $totalesActividades['examen_final']['total']) * 10;
                        $examen = (removePorcentaje($ponderacion['examen'])*$division)/100;
                    }
                }


                if($me['reduccion'] == 50) $final = ($final / 2);
                if($me['reduccion'] == 100) $final = 0;

                if($ignorarParticipaciones == false) {
                    $finalDebugArr = $final .'-'. $tareas .'-'. $investigacion .'-'. $lecturas .'-'. $debates .'-'. $participacion.'-'.$proyectos;
                    $final = formatoPromedio(($final + floatval($tareas) + floatval($investigacion) + floatval($lecturas) + floatval($debates) + floatval($proyectos) + floatval($participacion)) / 10);
                }else{
                    // $final = $finalIntegro;
                    $final = ($final / 10);
                }


            }

            if(in_array(strtoupper(substr($materiaData->col_clave, 0, 2)), array('TL', 'AC', 'CL', 'TR'))) {
                $result['alumnos'][$a]['pre_final'] = $final;
                if($final >= 7) {
                    $final = '1.00';
                }else{
                    $final = 0;
                }
            }else{
                $result['alumnos'][$a]['pre_final'] = $final;
                if($periodoData->isPosgrado == 1){
                    if($final <= 7) {
                        $final = 7;
                    }else{
                        $final = round($final, 0, PHP_ROUND_HALF_ODD);
                    }
                }else{
                    if($final < 7) {
                        $final = intval($final);
                        if($final > 5 && $_reponse['tipoActiviad'] == 7) $final = 5;

                    }else{
                        $final = round($final, 0, PHP_ROUND_HALF_ODD);
                    }
                }
            }
            if($_reponse['tipoActiviad'] == 7) {
                $ClaveMateria = $materiaData->col_clave;
                $PeriodoID = $item['periodoAlumno'];
                $AlumnoID = $item['col_alumnoid'];
                $queryCalificaciones = 'SELECT * FROM tbl_calificaciones WHERE col_alumnoid="'.$AlumnoID.'" AND col_periodoid="'.$PeriodoID.'" AND col_materia_clave="'.$ClaveMateria.'" ';
                $sth = $this->db->prepare($queryCalificaciones);
                $sth->execute();
                $dataCalificaciones = $sth->fetch(PDO::FETCH_OBJ);
                if($dataCalificaciones->col_cf){
                    $final = $dataCalificaciones->col_cf;
                    $result['alumnos'][$a]['tipoFinal'] = 'DB';
                    //$result['alumnos'][$a]['tipoFinalQuery'] = $queryCalificaciones;
                } else if(in_array($data->col_tipo, array(7,8,9))){
                    $final = $finalIntegro;
                    $result['alumnos'][$a]['tipoFinal'] = 'Integro';
                }
            }

            unset($acredita);
            //if($calificacionIntegra == '' || (floatval($calificacionIntegra) == 0 && $fechaCreo == $fechaActualizo)) {
                $acredita = acreditaPresentarByActividad($this->db, $item['col_alumnoid'], intval($_REQUEST['id']), 0, $_fechaActividad);
            //}
            if ($hasSD == 1){
                $db_acredita['status'] = 'sin-derecho';
                $db_acredita['reason'] = $sdRazon;
            }

            if ($hasSDME == 1){
                $db_acreditaME['status'] = 'sin-derecho';
                $db_acreditaME['reason'] = $sdMERazon;
            }


            // if($acredita['status'] == 'sin-derecho' || $me['reduccion'] == 100){
            //     $calificacion = $final = 'SD';
            // }

            // $result['alumnos'][$a]['debugCalificaciones'] = $queryCalificaciones;
            $result['alumnos'][$a]['ordenExamen'] = $ordenExamen;
            $result['alumnos'][$a]['debugPonderacion'] = $ponderacion;
            $result['alumnos'][$a]['id'] = $item['col_id'];
            $result['alumnos'][$a]['acredita'] = $acredita['status'];
            $result['alumnos'][$a]['acreditaReason'] = $acredita['reason'];

            $result['alumnos'][$a]['db_acredita'] = $db_acredita['status'];
            $result['alumnos'][$a]['db_acreditaReason'] = $db_acredita['reason'];

            $result['alumnos'][$a]['db_acreditaME'] = $db_acreditaME['status'];
            $result['alumnos'][$a]['db_acreditaReasonME'] = $db_acreditaME['reason'];

            $result['alumnos'][$a]['acreditaDebug'] = $acredita['debug'];
            $result['alumnos'][$a]['porcentaje'] = ((80*$calificacion) / 10).'%';
            $result['alumnos'][$a]['porcentajeTareas'] = formatPonderacionPercent($tareas).'%';
            $result['alumnos'][$a]['porcentajeTareasDebug'] = $tareasDebug;
            // $result['alumnos'][$a]['porcentajeTareasDebug'] = $debugPorcentajeTareas;
            $result['alumnos'][$a]['porcentajeInvestigacion'] = formatPonderacionPercent($investigacion).'%';
            $result['alumnos'][$a]['porcentajeLecturas'] = formatPonderacionPercent($lecturas).'%';
            $result['alumnos'][$a]['porcentajeDebates'] = formatPonderacionPercent($debates).'%';
            $result['alumnos'][$a]['porcentajeProyectos'] = formatPonderacionPercent($proyectos).'%';
            $result['alumnos'][$a]['porcentajeExamen'] = formatPonderacionPercent($examen).'%';
            $result['alumnos'][$a]['porcentajeParticipacion'] = formatPonderacionPercent($participacion).'%';
            $result['alumnos'][$a]['porcentajeParticipacionDebug'] = $participacionDebug;
            $result['alumnos'][$a]['alumnoid'] = $item['col_alumnoid'];
            $result['alumnos'][$a]['nombre'] = fixEncode($item['col_apellidos']." ".$item['col_nombres']);
            $result['alumnos'][$a]['grupo'] = fixEncode($item['semestre']."-".$item['grupo']);
            $result['alumnos'][$a]['archivos'] = (count($archivos)?implode('', $archivos):'-');
            $result['alumnos'][$a]['totalTareas'] = count($archivos);
            $result['alumnos'][$a]['listaArchivos'] = $archivosDetails;
            $result['alumnos'][$a]['calificacion'] = $calificacion;
            $result['alumnos'][$a]['calificacionIntegra'] = $calificacionIntegra;

            $result['alumnos'][$a]['falsificacion'] = $falsificacion;
            $result['alumnos'][$a]['me'] = $me;
            $result['alumnos'][$a]['meAcredita'] = $me['acredita'];
            $result['alumnos'][$a]['meReduccion'] = $me['reduccion'];
            $result['alumnos'][$a]['meTipo'] = $me['tipo'];
            $result['alumnos'][$a]['final'] = $final;
            $result['alumnos'][$a]['finalDebugArr'] = $finalDebugArr;
            $result['alumnos'][$a]['sinCalificar'] = $sinCalificar;

            $a++;
            // if($a == 8)break;
            // sleep(1);
        }

        $_reponse['listAlumnos'] = $result;


        return $this->response->withJson($_reponse);

    });

    $this->post('/guardarCalificacionesIndividual', function (Request $request, Response $response, $args) {
        global $uploaddir, $dblog;
        $userID = getCurrentUserID();

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());
        // $input->id;
        // $input->alumnoid;
        // $input->intento;
        // $input->actividadid;
        // $input->retro;
        // $input->calificacion;
        // $input->falsificacion;
        // $input->estatus;
        //$_response['debug'] = $input;

        $query = 'UPDATE tbl_actividades_tareas SET col_calificacion="'.$input->calificacion.'", col_falsificacion="'.$input->falsificacion.'", '
        .'col_sd="'.$input->sd.'", col_sd_razon="'.$input->razon.'", col_sdme="'.$input->sdme.'", col_sdme_razon="'.$input->merazon.'", '
        .'col_retroalimentacion="'.$input->retro.'", '
        .'col_fecha_retro="'.date("Y-m-d H:i:s").'", '
        .'col_estatus="'.$input->estatus.'", '
        .'col_updated_by="'.$userID.'", col_updated_at="'.date("Y-m-d H:i:s").'" WHERE col_alumnoid="'.intval($input->alumnoid).'" AND col_intento="'.intval($input->intento).'" AND col_actividadid="'.intval($input->actividadid).'"';

        $sth = $this->db->prepare($query);
        $dblog = new DBLog($query, 'tbl_actividades_tareas', '', '', 'Actividades', $this->db, 'Cambio en calificaciones');
        $dblog->where = array('col_alumnoid' => intval($input->alumnoid), 'col_intento' => intval($input->intento), 'col_actividadid' => intval($input->actividadid));
        $dblog->prepareLog();
        if($sth->execute()){
            $dblog->saveLog();
            guardarCalificacionesFinales($input->alumnoid, $input->actividadid, $this->db);
            // $total++;
        }

        $_response['status'] = 'true';
        return $this->response->withJson($_response);
    });


    $this->post('/guardarCalificacionesBase', function (Request $request, Response $response, $args) {
        global $uploaddir, $dblog;
        $userID = getCurrentUserID();

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $total = 0;

        $idsAlumnos = explode(',', $input->idsAlumnos);
        foreach($idsAlumnos as $alumnoid) {
                $check = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid="'.intval($input->id).'"';
                $sthCheck = $this->db->prepare($check);
                $sthCheck->execute();
                if($sthCheck->rowCount() == 0){
                    $query = 'INSERT INTO tbl_actividades_tareas (col_alumnoid, col_actividadid, col_calificacion, col_falsificacion, col_sd, col_sd_razon, col_sdme, col_sdme_razon, col_created_by, col_created_at, col_updated_by, col_updated_at) VALUES("'.$alumnoid.'", "'.$input->id.'", "0", "0", "0", "", "0", "", "'.$userID.'", "'.date("Y-m-d H:i:s").'", "'.$userID.'", "'.date("Y-m-d H:i:s").'")';

                    $dblog = new DBLog($query, 'tbl_actividades_tareas', '', '', 'Actividades', $this->db, 'Cambio en calificaciones');
                    $dblog->prepareLog();

                    $sth = $this->db->prepare($query);
                    $sth->execute();

                    $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                    $dblog->saveLog();
                }
                $alumnosIDS[] = $alumnoid;
        }

        $_response['status'] = 'true';
        return $this->response->withJson($_response);
    });

    $this->post('/guardarCalificaciones', function (Request $request, Response $response, $args) {
        global $uploaddir, $dblog;
        $userID = getCurrentUserID();

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $total = 0;

        $idsAlumnos = explode(',', $input->idsAlumnos);
        foreach($idsAlumnos as $alumnoid) {
                $check = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid="'.intval($input->id).'"';
                $sthCheck = $this->db->prepare($check);
                $sthCheck->execute();
                if($sthCheck->rowCount() == 0){
                    $query = 'INSERT INTO tbl_actividades_tareas (col_alumnoid, col_actividadid, col_calificacion, col_falsificacion, col_sd, col_sd_razon, col_sdme, col_sdme_razon, col_created_by, col_created_at, col_updated_by, col_updated_at) VALUES("'.$alumnoid.'", "'.$input->id.'", "0", "0", "0", "", "0", "", "'.$userID.'", "'.date("Y-m-d H:i:s").'", "'.$userID.'", "'.date("Y-m-d H:i:s").'")';

                    $dblog = new DBLog($query, 'tbl_actividades_tareas', '', '', 'Actividades', $this->db, 'Cambio en calificaciones');
                    $dblog->prepareLog();

                    $sth = $this->db->prepare($query);
                    $sth->execute();

                    $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                    $dblog->saveLog();
                }
                $alumnosIDS[] = $alumnoid;
        }

        foreach($idsAlumnos as $alumnoid) {
        // foreach($input->calificacion as $k => $v) {
            $k = $alumnoid;
            $v = ($input->calificacion[$alumnoid] == ''?'0.00':$input->calificacion[$alumnoid]);
            // if($v){
                if(floatval($v) > 10) {
                    $v = floatval($v / 10);
                }
                $check = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$k.'" AND col_actividadid="'.intval($input->id).'"';
                $sthCheck = $this->db->prepare($check);
                $sthCheck->execute();


                if($sthCheck->rowCount() > 0){
                    $query = 'UPDATE tbl_actividades_tareas SET col_calificacion="'.$v.'", col_falsificacion="'.$input->falsificacion[$k].'", col_sd="'.$input->sd[$k].'", col_sd_razon="'.$input->razon[$k].'", col_sdme="'.$input->sdme[$k].'", col_sdme_razon="'.$input->merazon[$k].'", col_updated_by="'.$userID.'", col_updated_at="'.date("Y-m-d H:i:s").'" WHERE col_alumnoid="'.$k.'" AND col_actividadid="'.intval($input->id).'"';
                    $dblog = new DBLog($query, 'tbl_actividades_tareas', '', '', 'Actividades', $this->db, 'Cambio en calificaciones');
                    $dblog->where = array('col_alumnoid' => intval($k), 'col_actividadid' => intval($input->id));
                }else{
                    $query = 'INSERT INTO tbl_actividades_tareas (col_alumnoid, col_actividadid, col_calificacion, col_falsificacion, col_sd, col_sd_razon, col_created_by, col_created_at, col_updated_by, col_updated_at) VALUES("'.$k.'", "'.$input->id.'", "'.$v.'", "'.$input->falsificacion[$k].'", "'.$input->sd[$k].'", "'.$input->razon[$k].'", "'.$userID.'", "'.date("Y-m-d H:i:s").'", "'.$userID.'", "'.date("Y-m-d H:i:s").'")';
                    $dblog = new DBLog($query, 'tbl_actividades_tareas', '', '', 'Actividades', $this->db, 'Cambio en calificaciones');
                }
                $dblog->prepareLog();

                $sth = $this->db->prepare($query);

                if($sth->execute()){
                    $dblog->saveLog();
                    guardarCalificacionesFinales($k, $input->id, $this->db);
                    $total++;
                }
            // }
        }



        // 5: Examen Parcial 1
        // 6: Examen Parcial 2
        // 7: Examen Final

        // Calificables
        // 1: Tarea
        // 2: Trabajos de Investigación
        // 3: Lectura
        // 4: Debates
        // 11: Actividad en Clase
        // 12: Proyecto posgrado
        $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.intval($input->id).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataActividad = $sth->fetch(PDO::FETCH_OBJ);
        if($dataActividad->col_tipo == 5 || $dataActividad->col_tipo == 6) {
            $query = 'SELECT * FROM tbl_actividades WHERE col_fecha_inicio<="'.$dataActividad->col_fecha_inicio.'" AND col_materiaid="'.$dataActividad->col_materiaid.'" AND col_tipo IN (1,2,3,4,11) AND col_visible="200" AND col_visible_excepto="'.addslashes($dataActividad->col_visible_excepto).'" AND col_id!="'.intval($input->id).'"';
            // $_response['debug1'] = $query;
            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach($todos as $item){
                switch($item['col_tipo']) {

                    case 1:$xdata['tareas'][] = $item['col_id'];break;
                    case 2:$xdata['investigaciones'][] = $item['col_id'];break;
                    case 3:$xdata['lecturas'][] = $item['col_id'];break;
                    case 4:$xdata['debates'][] = $item['col_id'];break;
                    case 11:$xdata['enclase'][] = $item['col_id'];break;

                }
            }

            //Tareas
            foreach($alumnosIDS as $alumnoid) {

                foreach($xdata['tareas'] as $itemid) {

                    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid="'.intval($itemid).'"';
                    $sth = $this->db->prepare($query);
                    $sth->execute();
                    $dataTarea = $sth->fetch(PDO::FETCH_OBJ);
                    $calificacion = 0;
                    if($sth->rowCount() > 0) $calificacion = corregirCalificacion($dataTarea->col_calificacion);
                    $xdata[$alumnoid]['calificaciones']['tareas'] = $xdata[$alumnoid]['calificaciones']['tareas'] + $calificacion;

                }

                foreach($xdata['investigaciones'] as $itemid) {

                    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid="'.intval($itemid).'"';
                    $sth = $this->db->prepare($query);
                    $sth->execute();
                    $dataTarea = $sth->fetch(PDO::FETCH_OBJ);
                    $calificacion = 0;
                    if($sth->rowCount() > 0) $calificacion = corregirCalificacion($dataTarea->col_calificacion);
                    $xdata[$alumnoid]['calificaciones']['investigaciones'] = $xdata[$alumnoid]['calificaciones']['investigaciones'] + $calificacion;

                }

                foreach($xdata['lecturas'] as $itemid) {

                    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid="'.intval($itemid).'"';
                    $sth = $this->db->prepare($query);
                    $sth->execute();
                    $dataTarea = $sth->fetch(PDO::FETCH_OBJ);
                    $calificacion = 0;
                    if($sth->rowCount() > 0) $calificacion = corregirCalificacion($dataTarea->col_calificacion);
                    $xdata[$alumnoid]['calificaciones']['lecturas'] = $xdata[$alumnoid]['calificaciones']['lecturas'] + $calificacion;

                }

                foreach($xdata['debates'] as $itemid) {

                    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid="'.intval($itemid).'"';
                    $sth = $this->db->prepare($query);
                    $sth->execute();
                    $dataTarea = $sth->fetch(PDO::FETCH_OBJ);
                    $calificacion = 0;
                    if($sth->rowCount() > 0) $calificacion = corregirCalificacion($dataTarea->col_calificacion);
                    $xdata[$alumnoid]['calificaciones']['debates'] = $xdata[$alumnoid]['calificaciones']['debates'] + $calificacion;

                }

                foreach($xdata['enclase'] as $itemid) {

                    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid="'.intval($itemid).'"';
                    $sth = $this->db->prepare($query);
                    $sth->execute();
                    $dataTarea = $sth->fetch(PDO::FETCH_OBJ);
                    $calificacion = 0;
                    if($sth->rowCount() > 0) corregirCalificacion($calificacion = $dataTarea->col_calificacion);
                    $xdata[$alumnoid]['calificaciones']['enclase'] = $xdata[$alumnoid]['calificaciones']['enclase'] + $calificacion;

                }

            }

            //$_response['debug'] = $xdata;

        }

        $_response['guardado'] = $total;
        $_response['status'] = 'true';
        return $this->response->withJson($_response);

    });

    $this->get('/getActividad', function (Request $request, Response $response, array $args) {
        global $download_url;

        $userID = getCurrentUserID();
        $userType = getCurrentUserType();
        $actividadid = intval($_REQUEST['id']);

        // $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'" AND col_created_by="'.$userID.'"';
        $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        if($sth->rowCount() == 0 && $userType == 'maestro') {
            $result['error'] = 'Esta actividad no te pertenece';
            return $this->response->withJson($result);
        }
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_maestros WHERE col_userid="'.$data->col_created_by.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMaestro = $sth->fetch(PDO::FETCH_OBJ);

        $materiaid = getMateriaByActividadID($data->col_visible_excepto, $this->db, $data->col_created_by, $actividadid);
        $query = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMateria = $sth->fetch(PDO::FETCH_OBJ);


        $data->col_titulo = fixEncode($data->col_titulo);
        $data->col_descripcion = fixEncode($data->col_descripcion);
        $data->col_visible_excepto =  unserialize(stripslashes($data->col_visible_excepto));

        if(intval($data->col_visible_excepto) > 0) $periodoData = getPeriodo(intval($data->col_visible_excepto), $this->db, false);

        if(($data->col_tipo == 12 || $data->col_tipo == 7) && $periodoData->isPosgrado == 1 && intval($data->col_visible_excepto) > 0) {
            $ponderacion = getPonderacion($materiaid, $periodoData->col_id, $this->db);
        }else{
            $ponderacion =  unserialize(stripslashes($data->col_ponderacion));
        }
        $data->fechaLimite =  fechaTexto($data->col_fecha_inicio);
        $result['blocked'] = 'false';
        if(strtotime('now') > strtotime($data->col_fecha_inicio." 23:59:59")){
             $result['blocked'] = 'true';
        }





        $result['actividad'] = $data;
        $diasTranscurridos = 0;
        if(strtotime('now') > strtotime($data->col_fecha_inicio)) $diasTranscurridos = countDays(strtotime($data->col_fecha_inicio));

        $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$userID.'" AND col_actividadid="'.intval($_REQUEST['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $nuevaRevision = false;
        $cerrado = false;
        $i = 0;
        foreach($todos as $item){
            if($item['col_archivo']){
                $result['tareas'][$i]['fechaTarea'] = fechaTexto($item['col_created_at'])." ".substr($item['col_created_at'], 11, 17);
                $result['tareas'][$i]['archivo'] = $download_url.'tareas/'.$item['col_archivo'];
                $result['tareas'][$i]['estatus'] = $item['col_estatus'];
                if($item['col_estatus'] == 1) $nuevaRevision = true;
                if($item['col_estatus'] == 2) $cerrado = true;
                $result['tareas'][$i]['retroalimentacion'] = $item['col_retroalimentacion'];
                $result['tareas'][$i]['fecha_retro'] = $item['col_fecha_retro'];
                $result['tareas'][$i]['intento'] = $item['col_intento'];
                $result['calificacion'] = corregirCalificacion($item['col_calificacion']);
                $i++;
            }
        }

        $queryIntentos = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$userID.'" AND col_actividadid="'.intval($_REQUEST['id']).'" GROUP BY col_intento ORDER BY col_intento ASC';

        $sth = $this->db->prepare($queryIntentos);
        $sth->execute();
        $todosIntentos = $sth->fetchAll();
        $int = 0;
        foreach($todosIntentos as $intentos) {
            $intentoNumero = $intentos['col_intento'];
            $queryTareas = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$userID.'" AND col_actividadid="'.intval($_REQUEST['id']).'" AND col_intento="'.$intentos['col_intento'].'"';
            $sth = $this->db->prepare($queryTareas);
            $sth->execute();
            $todosTareas = $sth->fetchAll();
            $a = 0;
            //echo "1"; return;
            foreach($todosTareas as $item) {
                if($item['col_archivo']){
                    $result['tareasDetails'][$int]['intento'] = $intentoNumero;
                    $result['tareasDetails'][$int]['estatus'] = $item['col_estatus'];
                    $result['tareasDetails'][$int]['retroalimentacion'] = $item['col_retroalimentacion'];
                    $result['tareasDetails'][$int]['fecha_retro'] = $item['col_fecha_retro'];
                    $result['tareasDetails'][$int]['falsificacion'] = $item['col_falsificacion'];
                    $result['tareasDetails'][$int]['calificacion'] = $item['col_calificacion'];
                    $result['tareasDetails'][$int]['calificacionCorregida'] = corregirCalificacion($item['col_calificacion']);

                    $result['tareasDetails'][$int]['tareas'][$a]['fechaTarea'] = fechaTexto($item['col_created_at'])." ".substr($item['col_created_at'], 11, 17);
                    $result['tareasDetails'][$int]['tareas'][$a]['url'] = $download_url.'tareas/'.$item['col_archivo'];
                    $result['tareasDetails'][$int]['tareas'][$a]['archivo'] = $item['col_archivo'];
                    $result['tareasDetails'][$int]['tareas'][$a]['intento'] = $item['col_intento'];


                    if($item['col_estatus'] == 1) $nuevaRevision = true;
                    if($item['col_estatus'] == 2) $cerrado = true;
                    $a++;
                }
            }
            $int++;
        }

        $intento = 1;
        $intentoTexto = 'Primer Intento';
        if($nuevaRevision) {
            $intento = 2;
            $intentoTexto = 'Segundo Intento';
        }

        $result['totalTareas'] = isset($result['tareas'])?count($result['tareas']):0;
        $result['diasPasados'] = $diasTranscurridos;
        $estatus = 'open';

        // $tiposActividades = array(
        //     1 => "Tarea",
        //     2 => "Trabajos de Investigación",
        //     3 => "Lectura",
        //     4 => "Debates",
        //     5 => "Examen Parcial 1",
        //     6 => "Examen Parcial 2",
        //     7 => "Examen Final",
        //     8 => "Examen Extraordinario",
        //     9 => "Examen a Titulo de Suficiencia",
        //     10 => "Actividad Extra (No calificable)",
        //     11 => "Actividad en Clase");
        //     12 => "Proyecto posgrado"

        if($diasTranscurridos > 7 && ($data->col_tipo >= 5 && $data->col_tipo <=9)) {
            $estatus = 'blocked';
            $estatusRazon = 'Excedio limite de días: 7';
            $result['blocked_reason'] = 'fecha';
        }
        if($diasTranscurridos > 2 && ($data->col_tipo == 7)) {
            $estatus = 'blocked';
            $estatusRazon = 'Excedio limite de días: 2 (Examen final)';
            $result['blocked_reason'] = 'fecha';
        }
        if($diasTranscurridos > 7 && ($data->col_tipo == 7)) {
            $estatus = 'blocked';
            $estatusRazon = 'Excedio limite de días: 7';
            $result['blocked_reason'] = 'fecha';
        }

        if($cerrado == true && $data->col_tipo == 12) {
            $estatus = 'blocked';
            $estatusRazon = 'Actividad cerrada';
            $result['blocked_reason'] = 'estatus';
        }
        if($dataMaestro->col_edit_calificaciones == 1) $estatus = 'open';

        $result['nombreMateria'] = fixEncode($dataMateria->col_nombre, true);
        $result['maestroEstatus'] = $estatus;
        $result['maestroEstatusRazon'] = $estatusRazon;
        $result['fechaLimite'] = $data->fechaLimite;
        $result['actividadTipo'] = $data->col_tipo;
        $result['debug'] = $ponderacion;
        $result['hasPonderacionTareas'] = removePorcentaje($ponderacion['tarea']);
        $result['hasPonderacionInvestigacion'] = removePorcentaje($ponderacion['investigacion']);
        $result['hasPonderacionLecturas'] = removePorcentaje($ponderacion['lecturas']);
        $result['hasPonderacionDebates'] = removePorcentaje($ponderacion['debates']);
        $result['hasPonderacionParticipacion'] = removePorcentaje($ponderacion['participacion']);
        $result['hasPonderacionProyecto'] = removePorcentaje($ponderacion['proyecto']);
        $result['hasPonderacionExamen'] = removePorcentaje($ponderacion['examen']);
        $result['tipoMateria'] = strtoupper(substr($dataMateria->col_clave, 0, 2));
        $result['posgrado'] = 'no';
        if(($data->col_tipo == 12 || $data->col_tipo == 7) && $periodoData->isPosgrado == 1) $result['posgrado'] = 'si';
        $result['intento'] = $intento;
        $result['intentoTexto'] = $intentoTexto;
        $result['bloqueado'] = $cerrado;
        if($cerrado || $estatus == 'blocked') $result['blocked'] = 'true';

        return $this->response->withJson($result);

    });

    $this->post('/get', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.intval($input['params']['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $data->col_titulo = fixEncode($data->col_titulo);
        $data->col_descripcion = fixEncode($data->col_descripcion);
        $data->col_visible_excepto =  unserialize(stripslashes($data->col_visible_excepto));
        $data->col_ponderacion =  unserialize(stripslashes($data->col_ponderacion));
        if(intval($data->col_visible_excepto) > 0 && intval($data->col_materiaid) > 0) {
            $data->col_visible_excepto = $data->col_visible_excepto.'|m:'.$data->col_materiaid;
        }

        if(intval($data->col_visible_excepto) > 0) {
            $periodoData = getPeriodo(intval($data->col_visible_excepto), $this->db, false);
            if( $periodoData->isPosgrado == 1){
                if(intval($data->col_tipo) == 7) $data->col_tipo = 13;
            }
        }

        return $this->response->withJson($data);

    });

    $this->put('/update', function (Request $request, Response $response, $args) {
        global $uploaddir, $download_url, $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $visibleExcepto = '';
        $materiaid = 0;
        if(intval($input->visible) == 100) $visibleExcepto = $input->visibleExcepto;
        if(intval($input->visible) == 200){
            $visibleExcepto = $input->visibleExceptoGrupo;
            if(strpos($visibleExcepto, '|m:') !== false){
                $__visibleExcepto = explode('|m:', $visibleExcepto);
                $visibleExcepto = $__visibleExcepto[0];
                $materiaid = $__visibleExcepto[1];
            }
        }

        if(intval($visibleExcepto) > 0) {
            $periodoData = getPeriodo(intval($visibleExcepto), $this->db, false);
            if((intval($input->tipo) == 12 || intval($input->tipo) == 13) && $periodoData->isPosgrado == 0) {
                $_response['status'] = 'No se puedo guardar el registro. El tipo de actividad seleccionado, solo se puede elegir para posgrados.';
                return $this->response->withJson($_response);
            }else if( $periodoData->isPosgrado == 1){
                if(intval($input->tipo) == 13) $input->tipo = 7;
            }
        }

        if(isset($input->tareaPond) && $input->tareaPond != '') $ponderacion['tarea'] = $input->tareaPond;
        if(isset($input->investigacionPond) && $input->investigacionPond != '') $ponderacion['investigacion'] = $input->investigacionPond;
        if(isset($input->lecturasPond) && $input->lecturasPond != '') $ponderacion['lecturas'] = $input->lecturasPond;
        if(isset($input->debatesPond) && $input->debatesPond != '') $ponderacion['debates'] = $input->debatesPond;
        if(isset($input->participacionPond) && $input->participacionPond != '') $ponderacion['participacion'] = $input->participacionPond;


        $query = 'UPDATE tbl_actividades SET '
            .'col_titulo="'.addslashes($input->titulo).'", '
            .'col_descripcion="'.addslashes($input->descripcion).'", '
            .'col_fecha_inicio="'.substr($input->fecha[0], 0, 10).'", '
            .'col_fecha_fin="'.substr($input->fecha[1], 0, 10).'", '
            .'col_visible="'.intval($input->visible).'", '
            .'col_visible_excepto="'.addslashes(serialize($visibleExcepto)).'", '
            .'col_color_letra="'.$input->colorLetra.'", '
            .'col_color_fondo="'.$input->colorFondo.'", '
            .'col_estatus="'.intval($input->estatus).'", '
            .'col_tipo="'.intval($input->tipo).'", '
            .'col_materiaid="'.intval($materiaid).'", '
            .'col_ponderacion="'.addslashes(serialize($ponderacion)).'", '
            .'col_updated_at="'.date("Y-m-d H:i:s").'", '
            .'col_updated_by="'.$input->userid.'" WHERE col_id="'.$input->id.'"';

        $sth = $this->db->prepare($query);

        $dblog = new DBLog($query, 'tbl_actividades', '', '', 'Actividades', $this->db, 'Se modifico una actividad');
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        if($sth->execute()){
            $dblog->saveLog();

            $_response['status'] = 'true';
            if($input->archivo->filename){

                // Primero borramos el archivo actual si existe
                $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$input->id.'"';
                $fth = $this->db->prepare($query);
                $fth->execute();
                $file = $fth->fetch(PDO::FETCH_OBJ);
                $archivo_viejo = trim($file->col_archivo);
                if($archivo_viejo){
                    if(@file_exists($uploaddir.$archivo_viejo)){
                        @unlink($uploaddir.$archivo_viejo);
                    }
                }

                // Ahora guardamos el nuevo
                $array_ext = explode('.', $input->archivo->filename);
                $extension = end($array_ext);
                $filename = 'attach-'.strtotime('now').'.'.$extension;
                $query = 'UPDATE tbl_actividades SET col_archivo_nombre="'.$input->archivo->filename.'", col_archivo="'.$filename.'" WHERE col_id="'.$input->id.'"';
                $archivo = $this->db->prepare($query);
                $archivo->execute();

                list($type, $dataFile) = explode(';', $input->archivo->value);
                list(, $dataFile)      = explode(',', $dataFile);
                $_response['uploaded'] = file_put_contents($uploaddir.$filename, base64_decode($input->archivo->value));

            }else{
                if($input->markDeleteFile == 'true'){
                    // $_response['y'] = $query;
                    $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$input->id.'"';
                    $fth = $this->db->prepare($query);
                    $fth->execute();
                    $file = $fth->fetch(PDO::FETCH_OBJ);
                    $archivo_viejo = trim($file->col_archivo);
                    if($archivo_viejo){
                        $_response['unlink'] = 'fail';
                        if(@file_exists($uploaddir.$archivo_viejo)){
                            if(@unlink($uploaddir.$archivo_viejo)){
                                $query = 'UPDATE tbl_actividades SET col_archivo_nombre="", col_archivo="" WHERE col_id="'.$input->id.'"';
                                $archivo = $this->db->prepare($query);
                                $archivo->execute();

                                $_response['unlink'] = 'deleted';
                            }
                        }
                    }


                }
            }
        }

        return $this->response->withJson($_response);

    });

    $this->post('/tarea', function (Request $request, Response $response, $args) {
        global $uploaddir, $download_url, $allowExtensions, $limitFileSize, $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $sourceDevice = 'desktop';

        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            $sourceDevice = 'tablet';
        }

        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            $sourceDevice = 'phone';
        }

        $userID = getCurrentUserID();
        $userType = getCurrentUserType();


        $log_msg = "\n\r=================================================\n\r";
        $log_msg .= "Alumno ID: ".$input->userid."\n\r";
        $log_msg .= "User ID: ".$userID."\n\r";
        $log_msg .= "Actividad ID: ".$input->actividadid."\n\r";
        $log_msg .= "User Type: ".$userType."\n\r";
        $log_msg .= "Time: ".date('Y-m-d H:i:s')."\n\r";
        $log_msg .= "Device: ".$sourceDevice."\n\r";
        $log_msg .= "Device Info: ".$_SERVER['HTTP_USER_AGENT']."\n\r";
        if($input->archivoTarea->filename == ''){
            $log_msg .= "Error: No se envia archivo\n\r";
        }else{
            $log_msg .= "Archivo: ".$input->archivoTarea->filename."\n\r";
        }

        $log_filename = $_SERVER['DOCUMENT_ROOT']."/logsuploads";
        if (!file_exists($log_filename)) {
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename.'/log_' . date('Y-m-d').'.log';

        if (!file_exists($uploaddir.'tareas')) mkdir($uploaddir.'tareas', 0777, true);


        if($input->archivoTarea->filename == ''){
            file_put_contents($log_file_data, $log_msg, FILE_APPEND);
            $_response['status'] = 'No se puede guardar el registro, debido a que no estas enviando ningun archivo, debes enviar un archivo como evidencia de tu actividad.';
            return $this->response->withJson($_response);
        }

        list($type, $dataFile) = explode(';', $input->archivoTarea->value);
        list(, $dataFile)      = explode(',', $dataFile);

        if ( base64_encode(base64_decode($dataFile, true)) !== $dataFile){
            $log_msg .= "Archivo Invalido\n\r";
            file_put_contents($log_file_data, $log_msg, FILE_APPEND);
            $_response['status'] = 'El archivo que estas intentando enviar parece ser invaliddo, puedes probar abriendolo con un editor y guardar como un nuevo archivo antes de volver a intentar subirlo.';
            return $this->response->withJson($_response);
        }

        $array_ext = explode('.', $input->archivoTarea->filename);
        $extension = end($array_ext);

        if (!in_array(strtoupper($extension), $allowExtensions)) {
            $log_msg .= "Extension Invalida: ".$extension."\n\r";
            file_put_contents($log_file_data, $log_msg, FILE_APPEND);
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
            $log_msg .= "Archivo supera limite: ".$fileSizeUploaded." Bytes (Limite: ".$limitFileSize.")\n\r";
            file_put_contents($log_file_data, $log_msg, FILE_APPEND);
            $_response['status'] = 'El archivo que deseas agregar supera el limite permitido de: '.($limitFileSize / 1e+6).' Megas';
            return $this->response->withJson($_response);
        }



        $data = array(
            'col_alumnoid' => $input->userid,
            'col_actividadid' => $input->actividadid,
            'col_calificacion' => '0.00',
            'col_archivo' => '',
            'col_created_at' => date("Y-m-d H:i:s"),
            'col_created_by' => $input->userid,
            'col_updated_at' => date("Y-m-d H:i:s"),
            'col_updated_by' => $input->userid,
        );

        $query = 'INSERT INTO tbl_actividades_tareas ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';

        $dblog = new DBLog($query, 'tbl_actividades_tareas', '', '', 'Actividades', $this->db, 'Se guardo una tarea');
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);

        if($sth->execute()){
            $ID = $this->db->lastInsertId();

            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();

            $_response['status'] = 'true';
            if($input->archivoTarea->filename){

                $filename = 'tarea-'.$input->userid.strtotime('now').'.'.$extension;
                $query = 'UPDATE tbl_actividades_tareas SET col_archivo="'.$filename.'", col_intento="'.$input->intento.'", col_fecha_subida="'.date('Y-m-d H:i:s').'" WHERE col_id="'.$ID.'"';
                $archivo = $this->db->prepare($query);
                $archivo->execute();

                $_response['debug'] = $uploaddir.'tareas/'.$filename;

                $estatus = file_put_contents($uploaddir.'tareas/'.$filename, base64_decode($dataFile));

                if($estatus === false){
                    $log_msg .= "Estatus Upload: No se subio\n\r";

                    $query = 'DELETE FROM tbl_actividades_tareas WHERE col_id="'.$ID.'"';
                    $removeFail = $this->db->prepare($query);
                    $removeFail->execute();

                    $_response['status'] = 'No se pudo guardar el archivo, intenta nuevamente, prueba cambiando el nombre del archivo o enviando un archivo mas pequeño.';
                }else{
                    $log_msg .= "Estatus Upload: SI se subio\n\r";
                }

                if(file_exists($uploaddir.'tareas/'.$filename)){
                    $log_msg .= "Estatus Archivo: Si existe el archivo\n\r";
                }else{
                    $log_msg .= "Estatus Archivo: No existe el archivo\n\r";
                }
                // $_response['uploaded'] = file_put_contents($uploaddir.'tareas/'.$filename, base64_decode($input->archivo->value));
                //$_response['uploaded'] = file_put_contents($uploaddir.$filename, base64_decode($input->archivo->value));
                $_response['uploaded'] = $estatus;
                file_put_contents($log_file_data, $log_msg, FILE_APPEND);
            }
        }else{
            file_put_contents($log_file_data, $log_msg, FILE_APPEND);
            $_response['status'] = 'No se puedo guardar el registro.';
        }

        return $this->response->withJson($_response);

    });


    $this->post('/add', function (Request $request, Response $response, $args) {
        global $uploaddir, $dblog;
        $userType = getCurrentUserType();
        $userID = getCurrentUserID();

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $visibleExcepto = '';
        if(intval($input->visible) == 100) $visibleExcepto = $input->visibleExcepto;
        if(intval($input->visible) == 200){
            $visibleExcepto = $input->visibleExceptoGrupo;
            if(strpos($visibleExcepto, '|m:') !== false){
                $__visibleExcepto = explode('|m:', $visibleExcepto);
                $visibleExcepto = $__visibleExcepto[0];
                $materiaid = $__visibleExcepto[1];
            }
        }


        if(intval($input->visible) == 0) {
            $_response['status'] = 'No se puedo guardar el registro. Debes especificar para quien será visible la actividad.';
            return $this->response->withJson($_response);
        }


        if($userType == 'maestro' && (intval($input->visible) == 0 || serialize($visibleExcepto) == 's:0:"";')) {
            $_response['status'] = 'No se puedo guardar el registro. Debes seleccionar un grupo antes de guardar.';
            return $this->response->withJson($_response);
        }

        if(intval($visibleExcepto) > 0) {
            $periodoData = getPeriodo(intval($visibleExcepto), $this->db, false);
            if((intval($input->tipo) == 12 || intval($input->tipo) == 13) && $periodoData->isPosgrado == 0) {
                $_response['status'] = 'No se puedo guardar el registro. El tipo de actividad seleccionado, solo se puede elegir para posgrados.';
                return $this->response->withJson($_response);
            }else if( $periodoData->isPosgrado == 1){
                if(intval($input->tipo) == 13) $input->tipo = 7;
            }
        }

        if(isset($input->tareaPond) && $input->tareaPond != '') $ponderacion['tarea'] = $input->tareaPond;
        if(isset($input->investigacionPond) && $input->investigacionPond != '') $ponderacion['investigacion'] = $input->investigacionPond;
        if(isset($input->lecturasPond) && $input->lecturasPond != '') $ponderacion['lecturas'] = $input->lecturasPond;
        if(isset($input->debatesPond) && $input->debatesPond != '') $ponderacion['debates'] = $input->debatesPond;
        if(isset($input->participacionPond) && $input->participacionPond != '') $ponderacion['participacion'] = $input->participacionPond;

        $data = array(
            'col_titulo' => addslashes($input->titulo),
            'col_descripcion' => addslashes($input->descripcion),
            'col_fecha_inicio' => substr($input->fecha[0], 0, 10),
            'col_fecha_fin' => substr($input->fecha[1], 0, 10),
            'col_visible' => intval($input->visible),
            'col_visible_excepto' => addslashes(serialize($visibleExcepto)),
            'col_color_letra' => $input->colorLetra,
            'col_color_fondo' => $input->colorFondo,
            'col_estatus' => intval($input->estatus),
            'col_tipo' => intval($input->tipo),
            'col_materiaid' => intval($materiaid),
            'col_ponderacion' => addslashes(serialize($ponderacion)),
            'col_created_at' => date("Y-m-d H:i:s"),
            'col_created_by' => $input->userid,
            'col_updated_at' => date("Y-m-d H:i:s"),
            'col_updated_by' => $input->userid,
        );


        $query = 'INSERT INTO tbl_actividades ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';

        $dblog = new DBLog($query, 'tbl_actividades', '', '', 'Actividades', $this->db, 'Se creo uno actividad');
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);

        if($sth->execute()){
            $_response['status'] = 'true';

            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();

            if($input->archivo->filename){
                $array_ext = explode('.', $input->archivo->filename);
                $extension = end($array_ext);
                $filename = 'attach-'.strtotime('now').'.'.$extension;
                $query = 'UPDATE tbl_actividades SET col_archivo_nombre="'.$input->archivo->filename.'", col_archivo="'.$filename.'" WHERE col_id="'.$this->db->lastInsertId().'"';
                $archivo = $this->db->prepare($query);
                $archivo->execute();

                list($type, $dataFile) = explode(';', $input->archivo->value);
                list(, $dataFile)      = explode(',', $dataFile);
                $_response['uploaded'] = file_put_contents($uploaddir.$filename, base64_decode($input->archivo->value));

            }

        }else{
            $_response['status'] = 'No se puedo guardar el registro.';
        }

        return $this->response->withJson($_response);

    });

    $this->delete('/delete', function (Request $request, Response $response, array $args) {
        global $uploaddir, $dblog;

        $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.intval($_REQUEST['id']).'"';
        $fth = $this->db->prepare($query);
        $fth->execute();
        $file = $fth->fetch(PDO::FETCH_OBJ);
        $archivo = trim($file->col_archivo);

        $query = 'DELETE FROM tbl_actividades WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_actividades', '', '', 'Actividades', $this->db, 'Se borro una actividad');
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        if($sth->execute()){
            $dblog->saveLog();
            if($archivo) {
            if(@file_exists($uploaddir.$archivo)){
                @unlink($uploaddir.$archivo);
            }
            }
        }

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->get('/getActa', function (Request $request, Response $response, array $args) {

        // $TodosLosPeriodos = getCurrentPeriodos($this->db);
        $actividadID = intval($_REQUEST['id']);
        $detalles = intval($_REQUEST['detalles']);
        $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.intval($_REQUEST['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataActividad = $sth->fetch(PDO::FETCH_OBJ);
        $maestroID = $dataActividad->col_created_by;
        switch($dataActividad->col_tipo){
            case 5:$col = 'col_p1';break;
            case 6:$col = 'col_p2';break;
            case 7:$col = 'col_ef';break;
            case 8:$col = 'col_ext';break;
            case 9:$col = 'col_ts';break;
        }

        $visibleData = unserialize(stripslashes($dataActividad->col_visible_excepto));
        if(intval($visibleData) > 0) {
            $periodoData = getPeriodo(intval($visibleData), $this->db, false);
            $periodoNombre = fixEncode($periodoData->col_nombre);
            $periodoGrupo = $periodoData->col_grado.'-'.$periodoData->col_grupo;
            $TodosLosPeriodos[] = intval($visibleData);
        }else{
            $_arrayVD = explode('|', $visibleData);
            $_periodosVD = explode(',', $_arrayVD[2]);
            $periodoData = getPeriodo(intval($_periodosVD[0]), $this->db, false);
            $periodoGrupo = 'Multigrupo';
            $periodoNombre = fixEncode($periodoData->col_nombre);
            $TodosLosPeriodos = $_periodosVD;
        }
        $periodoGroupActividad = $periodoGroup = $periodoData->col_groupid;
        $ordenExamen = getExamenOrden($dataActividad->col_id, $this->db);


        $materiaID = getMateriaByActividadID($dataActividad->col_visible_excepto, $this->db, intval($dataActividad->col_created_by), intval($_REQUEST['id']), $periodoData->col_carreraid);
        $query = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaID.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $materiaData = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_users WHERE col_id="'.$dataActividad->col_created_by.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $maestroData = $sth->fetch(PDO::FETCH_OBJ);

        $curricular = 'si';
        $materiaData->col_carrera = $periodoData->col_carreraid;
        if(in_array(strtoupper(substr($materiaData->col_clave, 0, 2)), array('TL', 'AC', 'CL', 'TR')) || $periodoData->col_modalidad > 1) {
            $curricular = 'no';
        }

        $carreraID = 0;
        switch($dataActividad->col_tipo){
            case 5: $subtitulo = '1º Parcial'; break;
            case 6: $subtitulo = '2º Parcial'; break;
            case 7: $subtitulo = 'Final'; break;
            case 8: $subtitulo = 'Extraordinario'; break;
            case 9: $subtitulo = 'Titulo de Suficiencia'; break;
        }

        $_reponse['tipoActiviad'] = $dataActividad->col_tipo;
        $_reponse['tipoActividad'] = $dataActividad->col_tipo;

        if($dataActividad->col_tipo == 7 && $periodoData->isPosgrado == 1) {
            $subtitulo = 'Acta de calificación posgrado';
        }

        if($dataActividad->col_visible == 200) {
            $grupo =  unserialize(stripslashes($dataActividad->col_visible_excepto));
            // print_r($grupo);
            if(strpos(strtoupper($grupo), 'MULTI|AC') !== false) {
                $_reponse['tipoMateria'] = 'AC';
                $dataVisiblePara = explode('|', $grupo);

                //$claveVisiblePara = claveMateria($dataVisiblePara[1]);
                $claveVisiblePara = $dataVisiblePara[1];
                $periodosVisiblePara = explode(',', $dataVisiblePara[2]);

                $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_materia_clave="'.$dataVisiblePara[1].'" AND col_maestroid="'.intval($dataActividad->col_created_by).'"';
                $sth = $this->db->prepare($query);
                $sth->execute();
                $preDataTaxMaestro = $sth->fetch(PDO::FETCH_OBJ);

                $preDataPeriodo = getPeriodo($preDataTaxMaestro->col_periodoid, $this->db, false);

                $queryMateria = 'SELECT * FROM tbl_materias WHERE col_clave="'.trim($preDataTaxMaestro->col_materia_clave).'" AND col_carrera="'.$preDataPeriodo->col_carreraid.'" AND col_plan_estudios="'.$preDataPeriodo->col_plan_estudios.'"';
                $sth = $this->db->prepare($queryMateria);
                $sth->execute();
                $laMateria = $sth->fetch(PDO::FETCH_OBJ);


                // $queryx = 'SELECT * FROM tbl_materias WHERE col_periodoid IN ('.implode(',', $TodosLosPeriodos).') AND col_clave LIKE "'.trim($claveVisiblePara).'%" AND col_carrera="'.$preDataPeriodo->col_carreraid.'" AND col_plan_estudios="'.$preDataPeriodo->col_plan_estudios.'"';
                $queryx = 'SELECT * FROM tbl_materias WHERE col_clave = "'.trim($claveVisiblePara).'" AND col_carrera="'.$preDataPeriodo->col_carreraid.'" AND col_plan_estudios="'.$preDataPeriodo->col_plan_estudios.'"';
                $sthx = $this->db->prepare($queryx);
                $sthx->execute();
                $dataMateriaMulti = $sthx->fetchAll();
                unset($multis);
                foreach($dataMateriaMulti as $mm) {
                    $multis[] = $mm['col_id'];
                }
                $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');
                $losPeriodos = getCurrentPeriodos($this->db, $types[$preDataPeriodo->col_modalidad]);

                $query = "SELECT a.col_carrera AS carreraID, a.col_periodoid AS periodoAlumno, t.col_id, t.col_alumnoid, t.col_groupid, a.col_nombres, a.col_apellidos, a.col_control, a.col_correo, p.col_grado AS semestre, p.col_grupo As grupo ".
                "FROM tbl_alumnos_taxonomia t ".
                "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                "LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid ".
                "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_academias ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
                // "GROUP BY t.col_alumnoid ORDER BY SUBSTRING(a.col_apellidos, 1, 5) ASC, SUBSTR(LTRIM(a.col_apellidos), LOCATE(' ',LTRIM(a.col_apellidos)))";
                "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";

            } else if(strpos(strtoupper($grupo), 'MULTI|TL') !== false) {
                $_reponse['tipoMateria'] = 'TL';
                $dataVisiblePara = explode('|', $grupo);

                //$claveVisiblePara = claveMateria($dataVisiblePara[1]);
                $claveVisiblePara = $dataVisiblePara[1];
                $periodosVisiblePara = explode(',', $dataVisiblePara[2]);

                $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_periodoid IN ('.implode(',', $TodosLosPeriodos).') AND col_materia_clave="'.$dataVisiblePara[1].'" AND col_maestroid="'.intval($dataActividad->col_created_by).'"';
                $sth = $this->db->prepare($query);
                $sth->execute();
                $preDataTaxMaestro = $sth->fetch(PDO::FETCH_OBJ);
                $preDataTaxMaestro->col_periodoid;

                $preDataPeriodo = getPeriodo($preDataTaxMaestro->col_periodoid, $this->db, false);

                $queryMateria = 'SELECT * FROM tbl_materias WHERE col_clave="'.trim($preDataTaxMaestro->col_materia_clave).'" AND col_carrera="'.$preDataPeriodo->col_carreraid.'" AND col_plan_estudios="'.$preDataPeriodo->col_plan_estudios.'"';
                $sth = $this->db->prepare($queryMateria);
                $sth->execute();
                $laMateria = $sth->fetch(PDO::FETCH_OBJ);

                $queryx = 'SELECT * FROM tbl_materias WHERE col_clave = "'.trim($claveVisiblePara).'" AND col_carrera="'.$preDataPeriodo->col_carreraid.'" AND col_plan_estudios="'.$preDataPeriodo->col_plan_estudios.'"';
                $sthx = $this->db->prepare($queryx);
                $sthx->execute();
                $dataMateriaMulti = $sthx->fetchAll();
                unset($multis);
                foreach($dataMateriaMulti as $mm) {
                    $multis[] = $mm['col_id'];
                }
                $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');
                $losPeriodos = getCurrentPeriodos($this->db, $types[$preDataPeriodo->col_modalidad]);

                $query = "SELECT a.col_carrera AS carreraID, a.col_periodoid AS periodoAlumno, t.col_id, t.col_alumnoid, t.col_groupid, a.col_nombres, a.col_apellidos, a.col_control, a.col_correo, p.col_grado AS semestre, p.col_grupo As grupo ".
                "FROM tbl_alumnos_taxonomia t ".
                "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                "LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid ".
                "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_talleres ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
                // "GROUP BY t.col_alumnoid ORDER BY SUBSTRING(a.col_apellidos, 1, 5) ASC, SUBSTR(LTRIM(a.col_apellidos), LOCATE(' ',LTRIM(a.col_apellidos)))";
                "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";

            }else{
                $_reponse['tipoMateria'] = 'R';
                $result['grupo'] = $grupo;
                $query = "SELECT a.col_carrera AS carreraID, a.col_periodoid AS periodoAlumno, t.col_id, t.col_alumnoid, t.col_groupid, a.col_nombres, a.col_apellidos, a.col_control, a.col_correo, p.col_grado AS semestre, p.col_grupo As grupo ".
                "FROM tbl_alumnos_taxonomia t ".
                "LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid ".
                "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                "WHERE a.col_estatus='activo' AND t.col_periodoid='".$grupo."' ".
                // "GROUP BY t.col_alumnoid ORDER BY SUBSTRING(a.col_apellidos, 1, 4) DESC, substring_index(a.col_apellidos, ' ', -1) ASC";
                // "GROUP BY t.col_alumnoid ORDER BY SUBSTRING(a.col_apellidos, 1, 5) ASC, SUBSTR(LTRIM(a.col_apellidos), LOCATE(' ',LTRIM(a.col_apellidos)))";
                // "GROUP BY t.col_alumnoid ORDER BY SUBSTR(RTRIM(a.col_apellidos), LOCATE(' ',RTRIM(a.col_apellidos))) ASC";
                "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";

            }
        }

        if($dataActividad->col_visible == 89) {
            $result['grupo'] = '-1'; // Todos los grupos
        }
//echo $queryx;exit;
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todosAlumnos = $sth->fetchAll();
        $totalTodosAlumnos = $sth->rowCount();
        $a = 0;

        // 5: Examen Parcial 1
        // 6: Examen Parcial 2
        // 7: Examen Final

        ob_start();
        ?>
        <table width="100%" class="listaCalificaciones">
            <thead>
                <tr>
                    <th align="left">No.</th>
                    <th>No. de Control</th>
                    <th align="left">Nombre</th>
                    <?php if(in_array(substr(strtoupper($materiaData->col_clave), 0, 2), array('AC', 'TL', 'CL', 'TR'))) { ?>
                        <?php if($dataActividad->col_tipo > 6) {?>
                            <!-- <th>EXAMEN</th> -->
                            <th>Calificación</th>
                            <th>Calificación<br/>con letra</th>
                        <?php }else{ ?>
                            <th>Calificación</th>
                            <th>Calificación<br/>con letra</th>
                        <?php } ?>

                    <?php }else{ ?>
                        <?php if($dataActividad->col_tipo > 6) {?>
                            <th>EXAMEN</th>
                            <th>Calificación<br/>con número</th>
                            <th>Calificación<br/>con letra</th>
                        <?php }else{ ?>
                            <th>Calificación<br/>con número</th>
                            <th>Calificación<br/>con letra</th>
                        <?php } ?>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php
                    $i = 1;
                    $a = 1;
                    $alumnosReprobados = getAlumnosReprobadosPorActividad(intval($_REQUEST['id']), $this->db);
                    $alumnosReprobadosExt = getAlumnosReprobadosExtPorActividad(intval($_REQUEST['id']), $this->db);
                    foreach($todosAlumnos as $item){
                        // if($maestroID == 18 && $item['col_alumnoid'] == 230)continue;
                        // if($maestroID == 46 && $item['col_alumnoid'] == 211)continue;

                        if($_reponse['tipoActiviad'] == 8 && !in_array($item['col_alumnoid'], $alumnosReprobados)) continue;
                        if($_reponse['tipoActiviad'] == 9 && !in_array($item['col_alumnoid'], $alumnosReprobadosExt)) continue;

                        $carreraID = $item['carreraID'];

                        $calificacion = '0.00';
                        unset($archivos);
                        $totalesActividades = getTotalesActividades($item['col_alumnoid'], $actividadID, $this->db);
                        if($curricular == 'si'){

                            if($_reponse['tipoActiviad'] == 5 OR $_reponse['tipoActiviad'] == 6){
                                if($ordenExamen == 1) $me = acreditaMEAcademias($item['col_alumnoid'], $actividadID, $this->db);
                                if($ordenExamen == 2) $me = acreditaMETalleres($item['col_alumnoid'], $actividadID, $this->db);
                                if($ordenExamen == 3) $me = acreditaMEClubLectura($item['col_alumnoid'], $actividadID, $this->db);
                                if($ordenExamen == 4) $me = acreditaMETransversales($item['col_alumnoid'], $actividadID, $this->db);
                                if($ordenExamen == 5) $me = acreditaMEPracticas($item['col_alumnoid'], intval($_REQUEST['id']), $_reponse['tipoActiviad'], $this->db);
                                if($ordenExamen == 7) $me = acreditaMEServicio($item['col_alumnoid'], intval($_REQUEST['id']), $_reponse['tipoActiviad'], $this->db);
                            }else if($_reponse['tipoActiviad'] == 7){
                                if($ordenExamen == 1) $me = acreditaMEAcademias($item['col_alumnoid'], $actividadID, $this->db);
                                if($ordenExamen == 2) $me = acreditaMEAltruista($item['col_alumnoid'], $this->db);
                            }

                        }

                        $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$item['col_alumnoid'].'" AND col_actividadid="'.intval($_REQUEST['id']).'"';
                        $sth_tt = $this->db->prepare($query);
                        $sth_tt->execute();
                        $tareas = $sth_tt->fetchAll();
                        foreach($tareas as $tarea){
                            $calificacion = corregirCalificacion($tarea['col_calificacion']);
                            $sd = $tarea['col_sd'];
                            $sdRazon = $tarea['col_sd_razon'];
                            $sdME = $tarea['col_sdme'];
                            $sdRazonME = $tarea['col_sdme_razon'];
                        }

                        $periodoDataAlumno = getPeriodo(intval($item['periodoAlumno']), $this->db, false);
                        $periodoGroup = $periodoDataAlumno->col_groupid;

                        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
                        $c = $this->db->prepare($query);
                        $c->execute();
                        $r = $c->fetch(PDO::FETCH_OBJ);
                        $grupos_periodos = array($r->col_periodo, $r->col_periodo_cuatri, $r->col_periodo_maestria, $r->col_periodo_doctorado);

                        $periodoGroupAlumno = $periodoGroup;
                        if(!in_array($periodoGroup, $grupos_periodos)) $periodoGroup = $periodoGroupActividad;

                        $query = 'SELECT '.$col.' As calificacion, col_cf AS CalificacionFinal FROM tbl_calificaciones WHERE col_alumnoid="'.$item['col_alumnoid'].'" AND col_materia_clave="'.$materiaData->col_clave.'" AND col_groupid="'.$periodoGroup.'"';
            			$sth_tt = $this->db->prepare($query);
                        $sth_tt->execute();
                        $calificaciones = $sth_tt->fetchAll();
			            if(!$calificaciones) {
				            $query = 'SELECT '.$col.' As calificacion, col_cf AS CalificacionFinal FROM tbl_calificaciones WHERE col_alumnoid="'.$item['col_alumnoid'].'" AND col_materia_clave="'.$materiaData->col_clave.'" AND col_groupid="'.$periodoGroupAlumno.'"';
                        	$sth_tt = $this->db->prepare($query);
                        	$sth_tt->execute();
                        	$calificaciones = $sth_tt->fetchAll();
			            }

                        foreach($calificaciones as $row){
                            $final = corregirCalificacion($row['calificacion']);
                            $CalificacionFinal = $row['CalificacionFinal'];
                        }


                        if(in_array(strtoupper(substr($materiaData->col_clave, 0, 2)), array('TL', 'AC', 'CL', 'TR'))) {
                            if($final >= 7) {
                                $final = '1.00';
                            }else{
                                $final = 0;
                            }
                        }else{
                            if($final < 7) {
                                $final = floatval($final);
                                // if($final > 5 && $_reponse['tipoActiviad'] == 7) $final = 5;
                            }else{
                                $final = round($final, 0, PHP_ROUND_HALF_ODD);
                            }
                        }

                        ?>
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td align="center"><?php echo $item['col_control']; ?></td>
                        <td>
                            <?php echo fixEncode($item['col_apellidos']." ".$item['col_nombres']); ?>
                            <?php if($me['acredita'] == 'NA') { ?>
                                <br/><small style="color: #cc0000;">SD por <?php echo $me['tipo']; ?> valor de examen <?php echo $me['reduccion']; ?>%</small>
                            <?php } ?>

                            <?php if($sd == 1) { ?>
                                <br/><small style="color: #cc0000;">SD por <?php echo $sdRazon; ?></small>
                            <?php } ?>
                            <?php if($sdME == 1) { ?>
                                <br/><small style="color: #cc0000;">SD por <?php echo $sdRazonME; ?></small>
                            <?php } ?>
                        </td>
                        <?php if(in_array(substr(strtoupper($materiaData->col_clave), 0, 2), array('AC', 'TL', 'CL', 'TR'))) { ?>
                            <?php if($dataActividad->col_tipo > 6) {?>
                                <!-- <td align="center"><?php echo ($final >= 1?'A':'NA'); ?></td> -->
                                <td align="center"><?php echo ($final >= 1?'A':'NA'); ?></td>
                                <td align="center"><?php echo ($final >= 1?'Acredito':'No Acredito'); ?></td>
                            <?php }else{ ?>
                                <td align="center"><?php echo ($final >= 1?'A':'NA'); ?></td>
                                <td align="center"><?php echo ($final >= 1?'Acredito':'No Acredito'); ?></td>
                            <?php } ?>
                        <?php }else{ ?>
                            <?php if($dataActividad->col_tipo > 6) {?>
                                <td align="center"><?php echo $final; ?></td>
                                <?php
                                    if($dataActividad->col_tipo == 8 || $dataActividad->col_tipo == 9) {
                                        $CalificacionFinal = $final;
                                    }
                                ?>
                                <td align="center"><?php echo $CalificacionFinal; ?></td>
                                <td align="center"><?php echo numerosaletras(intval($CalificacionFinal)); ?></td>
                            <?php }else{ ?>
                                <td align="center"><?php echo $final; ?></td>
                                <td align="center"><?php echo numerosaletras(intval($final)); ?></td>
                            <?php } ?>
                        <?php } ?>
                    </tr>
                <?php
                    $i++;
                    $a++;

                    if($a == 122 && $i < $totalTodosAlumnos) {
                        ?>
                        </tbody>
                    </table>
                    <pagebreak>
                    <table width="100%" class="listaCalificaciones">
                        <thead>
                            <tr>
                                <th align="left">No.</th>
                                <th>No. de Control</th>
                                <th align="left">Nombre</th>
                                <?php if(in_array($_reponse['tipoMateria'], array('AC', 'TL', 'CL', 'TR'))) { ?>
                                    <?php if($dataActividad->col_tipo > 6) {?>
                                        <th>EXAMEN</th>
                                        <th>Calificación</th>
                                        <th>Calificación<br/>con letra</th>
                                    <?php }else{ ?>
                                        <th>Calificación</th>
                                        <th>Calificación<br/>con letra</th>
                                    <?php } ?>

                                <?php }else{ ?>
                                    <?php if($dataActividad->col_tipo > 6) {?>
                                        <th>EXAMEN</th>
                                        <th>Calificación<br/>con número</th>
                                        <th>Calificación<br/>con letra</th>
                                    <?php }else{ ?>
                                        <th>Calificación<br/>con número</th>
                                        <th>Calificación<br/>con letra</th>
                                    <?php } ?>
                                <?php } ?>
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
                <td width="36%"></td>
                <td width="40%" align="center" style="border: 1px solid #222;padding:15px;"><br/><br/><br/><br/><br/><?php echo fixEncode($maestroData->col_firstname.' '.$maestroData->col_lastname); ?><hr/><b>NOMBRE Y FIRMA DEL CATEDRATICO</b></td>
                <td width="20%"></td>
            </tr>
        </table>
        <?php
        $html = ob_get_contents();
        ob_end_clean();

        $carreraData = getCarrera($carreraID, $this->db);
        ob_start();
        ?>
        <table border="0" width="100%">
            <tr>
                <td width="25%">
                    <img src="<?php echo getLogo(); ?>" style="max-width: 180px;height:auto;" alt="FLDCH" border="0"/>
                </td>
                <td width="50%" class="titulo">ACTA DE CALIFICACIONES</td>
                <td width="25%"></td>
            </tr>
        </table>
        <table border="0" width="100%">
            <tr>
                <td width="10%"></td>
                <td width="40%">CARRERA: <?php echo fixEncode($carreraData['nombre']); ?></td>
                <td width="40%"><?php echo $periodoGrupo; ?></td>
                <td width="10%"></td>
            </tr>
            <tr>
                <td width="10%"></td>
                <td width="40%">PERIODO ESCOLAR: <?php echo $periodoNombre; ?></td>
                <td width="40%">FECHA: <?php echo strtoupper(fechaTexto($dataActividad->col_fecha_inicio)); ?></td>
                <td width="10%"></td>
            </tr>
            <tr>
                <td width="10%"></td>
                <td width="40%" valign="top">MODALIDAD: <?php echo strtoupper(fixEncode($carreraData['modalidad'])); ?></td>
                <td width="40%">MATERIA: <?php echo fixEncode($materiaData->col_nombre); ?></td>
                <td width="10%"></td>
            </tr>
        </table><br/>
        <table width="100%">
            <tr>
                <td class="subtitulo"><?php echo $subtitulo; ?></td>
            </tr>
        </table><br/>
        <?php
        $header = ob_get_contents();
        ob_end_clean();

        ob_start();
        ?>
        <table border="0" width="100%">
            <tr>
                <td width="30%"></td>
                <td width="40%">Calificación Final minima aprobatoría 7<br/>Una vez entregada el acta no hay modificaciones.</td>
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
        $mpdf=new mPDF('c','A4', '','', '8', '8', 60, 40);
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
        <?php
        $stylesheet = ob_get_contents();
        ob_end_clean();

        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->WriteHTML($html, 2);

        $mpdf->Output('Acta.pdf', 'I');

        die();

    });


    $this->get('/getBoleta', function (Request $request, Response $response, array $args) {

        // $TodosLosPeriodos = getCurrentPeriodos($this->db);
        $alumnoid = intval($_REQUEST['id']);
        $periodoid = intval($_REQUEST['pid']);

        generarBoleta($alumnoid, $periodoid, $this->db, 'descarga-fldch');

        die();

    });

});
// routes.actividades.php
