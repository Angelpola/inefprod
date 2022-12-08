<?php

/**
 *
 * Archivo que incluye todas las funciones que permiten revisar si los alumnos acreditan el modelo educativo, dichas funciones
 * tienen relación con diversos modulos, los cuales se vinculan de forma global para la revisión del modelo educativo de cada alumno.
 *
 * getTotalesActividades
 * getExamenOrden
 * acreditaMEAcademias
 * acreditaMETalleres
 * acreditaMEClubLectura
 * acreditaMETransversales
 * acreditaMEPracticas
 * acreditaMEServicio
 * acreditaMEAltruista
 * corregirCalificacion
 * guardarCalificacionesFinales
 * fechaTextoBoleta
 * addSeguimiento
 *
 */

function getTotalesActividadesPosgrados($alumnoid, $actividadid, $db) {

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataActividad = $sth->fetch(PDO::FETCH_OBJ);

    $visibleData = unserialize(stripslashes($dataActividad->col_visible_excepto));
    $periodoData = getPeriodo(intval($visibleData), $db, false);


    $fechaInicio = $periodoData->col_fecha_inicio;
    $fechaFin = $dataActividad->col_fecha_inicio;
    // 7: Final


    $tiposActividades = array(
        12 => "proyecto",
        7 => "examen_final");

    $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto="'.addslashes($dataActividad->col_visible_excepto).'" AND (col_fecha_inicio>"'.$fechaInicio.'" AND col_fecha_inicio<="'.$fechaFin.'") AND (col_tipo=12 OR col_tipo=7) AND col_created_by="'.$dataActividad->col_created_by.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $actividades = $sth->fetchAll();
    foreach($actividades as $item){

        $totales[$tiposActividades[$item['col_tipo']]]['total'] = intval($totales[$tiposActividades[$item['col_tipo']]]['total']) + 1;
        if($item['col_tipo'] == 12) {
            $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_actividadid="'.$item['col_id'].'" AND col_alumnoid="'.$alumnoid.'" AND col_estatus=2';
        }else{
            $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_actividadid="'.$item['col_id'].'" AND col_alumnoid="'.$alumnoid.'" ';
        }

        // $totales[$tiposActividades[$item['col_tipo']]]['debug'] = $query;
        $sth = $db->prepare($query);
        $sth->execute();
        if($sth->rowCount()) {
            $act = $sth->fetch(PDO::FETCH_OBJ);
            $totales[$tiposActividades[$item['col_tipo']]]['calificacion'] = floatval($totales[$tiposActividades[$item['col_tipo']]]['calificacion']) + corregirCalificacion($act->col_calificacion);
        }
    }


    return $totales;
}

function getTotalesActividades($alumnoid, $actividadid, $db, $fechaLimite = '') {

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataActividad = $sth->fetch(PDO::FETCH_OBJ);

    $visibleData = unserialize(stripslashes($dataActividad->col_visible_excepto));
    if(intval($visibleData) > 0) {
        $periodoData = getPeriodo(intval($visibleData), $db, false);
    }else{
        $_arrayVD = explode('|', $visibleData);
        $_periodosVD = explode(',', $_arrayVD[2]);
        $periodoData = getPeriodo(intval($_periodosVD[0]), $db, false);
    }

    // 5: 1 Parcial
    // 6: 2 Parcial
    // 7: Final
    if($dataActividad->col_tipo == 5) {
        // Extraida de configuracion
        $fechaInicio = $periodoData->col_fecha_inicio;
        $fechaFin = $dataActividad->col_fecha_inicio;
    }
    if($dataActividad->col_tipo == 6) {
        // Extraida primer parcial
        $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto="'.addslashes($dataActividad->col_visible_excepto).'" AND col_tipo="5" AND col_created_by="'.$dataActividad->col_created_by.'" ORDER BY col_fecha_inicio DESC LIMIT 1 ';
        $sth = $db->prepare($query);
        $sth->execute();
        $dataActividadPrimerParcial = $sth->fetch(PDO::FETCH_OBJ);
        $fechaInicio = $dataActividadPrimerParcial->col_fecha_inicio;
        $fechaFin = $dataActividad->col_fecha_inicio;

    }
    if($dataActividad->col_tipo == 7) return 0;


    $tiposActividades = array(
        1 => "tarea",
        2 => "investigacion",
        3 => "lectura",
        4 => "debates",
        11 => "enclase",
        12 => "proyecto",
        7 => "examen_final");

    $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto="'.addslashes($dataActividad->col_visible_excepto).'" AND (col_fecha_inicio>"'.$fechaInicio.'" AND col_fecha_inicio<="'.$fechaFin.'") AND (col_tipo<5 OR col_tipo>10) AND col_created_by="'.$dataActividad->col_created_by.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $actividades = $sth->fetchAll();

    foreach($actividades as $item){
        $totales[$tiposActividades[$item['col_tipo']]]['total'] = intval($totales[$tiposActividades[$item['col_tipo']]]['total']) + 1;

        $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_actividadid="'.$item['col_id'].'" AND col_alumnoid="'.$alumnoid.'" ';
        $sth = $db->prepare($query);
        $sth->execute();
        if($sth->rowCount()) {
            $act = $sth->fetch(PDO::FETCH_OBJ);
            $totales[$tiposActividades[$item['col_tipo']]]['calificacion'] = floatval($totales[$tiposActividades[$item['col_tipo']]]['calificacion']) + corregirCalificacion($act->col_calificacion);
        }
    }


    return $totales;
}


function getExamenOrden($actividadid, $db){

    $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataActividad = $sth->fetch(PDO::FETCH_OBJ);


    $periodoid = intval(unserialize(stripslashes($dataActividad->col_visible_excepto)));
    $tipo = $dataActividad->col_tipo;
    if($tipo < 5 || $tipo > 7) return 'invalid';
    // 5: Examen Parcial 1
    // 6: Examen Parcial 2
    // 7: Examen Final

    $query = "SELECT a.col_id, a.col_titulo, a.col_fecha_inicio, m.col_id AS materiaid, m.col_nombre AS materia, m.col_clave AS clave FROM tbl_actividades a ".
    "LEFT OUTER JOIN tbl_materias m ON m.col_id=a.col_materiaid WHERE a.col_visible_excepto LIKE '%".$periodoid."%' AND a.col_tipo='".$tipo."' AND a.col_materiaid ".
    "AND (m.col_clave NOT LIKE 'TR%' AND m.col_clave NOT LIKE 'CL%') ORDER BY a.col_fecha_inicio ASC";
    // echo $query;exit;
    $sth = $db->prepare($query);
    $sth->execute();
    $actividades = $sth->fetchAll();
    $i = 0;
    foreach($actividades as $item){
        $order[$i] = $item['materiaid'];

        $i++;
    }

    return (intval(array_search($dataActividad->col_materiaid, $order)) + 1);
}

function acreditaMEAcademias($alumnoid, $actividadid, $db) {

    $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataActividad = $sth->fetch(PDO::FETCH_OBJ);
    $periodoid = intval(unserialize(stripslashes($dataActividad->col_visible_excepto)));
    if($periodoid == 0) {
        $periodoid = explode(',', array_pop(explode('|', (unserialize(stripslashes($dataActividad->col_visible_excepto))))));
        $periodoid = $periodoid[0];
    }
    // list($rangoFechaInicio, $rangoFechaFin) = getRangoFechas($db, $alumnoid, $periodoid);
    $periodoData = getPeriodo($periodoid, $db, false);
    if($dataActividad->col_tipo == 5){
        $rangoFechaInicio =  $periodoData->col_fecha_inicio;
    }else{
        list($rangoFechaInicio, $rangoFechaFin) = getRangoFechas($db, $alumnoid, $periodoid);
    }
    $queryFechaInicio = $rangoFechaFin = $dataActividad->col_fecha_inicio;

    // Pedimos Academia del alumno
    $queryAcacemia = "SELECT m.* FROM tbl_academias a LEFT JOIN tbl_materias m ON m.col_id=a.col_materiaid WHERE a.col_alumnoid='".$alumnoid."'";
    $sth = $db->prepare($queryAcacemia);
    $sth->execute();
    $dataAcademia = $sth->fetch(PDO::FETCH_OBJ);
    $claveAC = $dataAcademia->col_clave;


    $subQuery = 'SELECT col_id FROM tbl_actividades WHERE col_fecha_inicio<="'.$queryFechaInicio.'" AND (col_visible_excepto LIKE "%'.$periodoid.'%" AND col_visible_excepto LIKE "%'.$claveAC.'%") AND col_tipo="'.$dataActividad->col_tipo.'"';
    // $subQuery = 'SELECT col_id FROM tbl_actividades WHERE col_fecha_inicio<="'.$queryFechaInicio.'" AND col_visible_excepto ="'.addslashes($dataActividad->col_visible_excepto).'" AND col_tipo="'.$dataActividad->col_tipo.'"';
    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid IN ('.$subQuery.')';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() == 0) return;
    $dataActividad = $sth->fetch(PDO::FETCH_OBJ);

    $calificacion = corregirCalificacion($dataActividad->col_calificacion);
    $result['examenAcademia'] = $dataActividad->col_actividadid;
    $result['examenAcademiaDebug'] = $dataActividad;


    $sth = $db->prepare('SELECT * FROM tbl_actividades WHERE col_id="'.$dataActividad->col_actividadid.'"');
    $sth->execute();
    if($sth->rowCount() == 0) return;
    $la__dataActividad = $sth->fetch(PDO::FETCH_OBJ);
    $rangoFechaFin = $la__dataActividad->col_fecha_inicio;

    $extraData = explode('|', unserialize(stripslashes($la__dataActividad->col_visible_excepto)));
    $clavesMateria = getClavesPosibles($extraData[1], $db);
    $result['claveMateria'] = implode(',', $clavesMateria);

    switch($la__dataActividad->col_tipo){
        case 6:
        $sth = $db->prepare('SELECT * FROM tbl_actividades WHERE col_visible_excepto="'.addslashes($la__dataActividad->col_visible_excepto).'" AND col_tipo=5');
        $sth->execute();
        $tbt__dataActividad = $sth->fetch(PDO::FETCH_OBJ);
        $rangoFechaInicio = date('Y-m-d', strtotime('+1 day'. $tbt__dataActividad->col_fecha_inicio));
        break;

        case 7:
            $sth = $db->prepare('SELECT * FROM tbl_actividades WHERE col_visible_excepto="'.addslashes($la__dataActividad->col_visible_excepto).'" AND col_tipo=6');
            $sth->execute();
            $tbt__dataActividad = $sth->fetch(PDO::FETCH_OBJ);
            $rangoFechaInicio = date('Y-m-d', strtotime('+1 day'. $tbt__dataActividad->col_fecha_inicio));
        break;
    }


    $falsifico = 'no';
    $subQuery = 'SELECT col_id FROM tbl_actividades WHERE col_fecha_inicio<"'.$queryFechaInicio.'" AND col_visible_excepto LIKE "%'.$claveAC.'%"';
    $queryFalsifico = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid IN ('.$subQuery.') AND col_falsificacion=1';
    $sth = $db->prepare($queryFalsifico);
    $sth->execute();
    if($sth->rowCount() > 0) $falsifico = 'si';
    $la__dataActividad = $sth->fetch(PDO::FETCH_OBJ);

    // if($alumnoid == 682){
    //      echo $rangoFechaInicio.'   -     '.$rangoFechaFin.' - '.date('Y-m-d', strtotime('-1 day'. $rangoFechaFin));
    //     exit;
    // }
    $result['asistenciasFechaInicio'] = $rangoFechaInicio;
    $result['asistenciasFechaFinal'] = $rangoFechaFin;
    $asistencias = getAsistenciasByAlumnoAndMateria($alumnoid, $db, $rangoFechaInicio, $rangoFechaFin, 0, true);

    $hasAsistencias = 0;
    //$result['debugX'] = $asistencias;
    foreach($asistencias as $item){

        if(strtoupper(substr($item['clave'], 0, 2)) == 'AC' && in_array(trim($item['clave']), $clavesMateria) ){
            $hasAsistencias = 1;
            // if($alumnoid == 682){
            //     echo '<pre>';
            //     print_r($item);
            //     echo '</pre>';
            //     exit;
            // }

            $result['debug'] = $item;
            $porcentajeAsistencias = $item['porcentaje_asistencias'];
            $faltasAsistencias = $item['faltas'];
            break;
        }
    }
    if($hasAsistencias == 0) {
        $result['debug'] = 'No hay listas';
        $porcentajeAsistencias = 100;
        $faltasAsistencias = 0;
    }

    $deudas = tieneDeudas($db, $alumnoid, '', $queryFechaInicio);

    $result['reduccion'] = 0;
    $result['tipo'] = 'Academia';
    $result['faltas'] = $faltasAsistencias;
    $result['asistenciasPorcentaje'] = number_format($porcentajeAsistencias, 2);
    $result['acredita'] = ($calificacion == '1.00' || intval($calificacion) == 10?'A':'NA');

    if($result['acredita'] == 'NA' && intval($result['asistenciasPorcentaje']) >= 80){
        $result['reduccion'] = 50;
    }
    if($falsifico == 'si' || $deudas['status'] == 'true' || intval($result['asistenciasPorcentaje']) < 80){
        $result['acredita'] = 'NA';
        $result['reduccion'] = 100;
        $result['deudas'] = $deudas['status'];
        $result['falsifico'] = $falsifico;
        //$result['falsificoQuery'] = $queryFalsifico;
        $result['razon'] = 'asistencias';
        // $result['razonDebug'] = 'Porcentaje de asistencias menor a 80 ('.intval($result['asistenciasPorcentaje']).')';
    }

    if($dataActividad->col_sd == 1 && $dataActividad->col_sd_razon != '') {
        $result['acredita'] = 'NA';
        $result['reduccion'] = 100;
        $result['deudas'] = $deudas['status'];
        $result['falsifico'] = $falsifico;
        $result['razon'] = 'db';
        // $result['razonDebug'] = 'Tiene SD en DB';
    }
    return $result;
}
function acreditaMETalleres($alumnoid, $actividadid, $db) {
    $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataActividad = $sth->fetch(PDO::FETCH_OBJ);
    $periodoid = intval(unserialize(stripslashes($dataActividad->col_visible_excepto)));
    if($periodoid == 0) {
        $periodoid = explode(',', array_pop(explode('|', (unserialize(stripslashes($dataActividad->col_visible_excepto))))));
        $periodoid = $periodoid[0];
    }
    // list($rangoFechaInicio, $rangoFechaFin) = getRangoFechas($db, $alumnoid, $periodoid);
    $periodoData = getPeriodo($periodoid, $db, false);
    if($dataActividad->col_tipo == 5){
        $rangoFechaInicio =  $periodoData->col_fecha_inicio;
    }else{
        list($rangoFechaInicio, $rangoFechaFin) = getRangoFechas($db, $alumnoid, $periodoid);
    }
    $queryFechaInicio = $rangoFechaFin = $dataActividad->col_fecha_inicio;

    // Pedimos Taller del alumno
    $queryTaller = "SELECT m.* FROM tbl_talleres t LEFT JOIN tbl_materias m ON m.col_id=t.col_materiaid WHERE t.col_alumnoid='".$alumnoid."'";
    $sth = $db->prepare($queryTaller);
    $sth->execute();
    $dataTaller = $sth->fetch(PDO::FETCH_OBJ);
    $claveTL = $dataTaller->col_clave;


    if($periodoid == 0) {
        $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid ="'.$actividadid.'"';
    }else{
        $subQuery = 'SELECT col_id FROM tbl_actividades WHERE col_fecha_inicio<="'.$queryFechaInicio.'" AND (col_visible_excepto LIKE "%'.$claveTL.'%" AND col_visible_excepto LIKE "%'.$periodoid.'%") AND col_tipo="'.$dataActividad->col_tipo.'"';
        $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid IN ('.$subQuery.')';
    }

    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() == 0) return;
    $dataActividad = $sth->fetch(PDO::FETCH_OBJ);
    $calificacion = corregirCalificacion($dataActividad->col_calificacion);
    // $result['queryDebug'] = $query;
    $result['examenTaller'] = $dataActividad->col_actividadid;

    // $rangoFechaFin = $dataActividad->col_fecha_inicio;
    $sth = $db->prepare('SELECT * FROM tbl_actividades WHERE col_id="'.$dataActividad->col_actividadid.'"');
    $sth->execute();
    if($sth->rowCount() == 0) return;
    $la__dataActividad = $sth->fetch(PDO::FETCH_OBJ);
    $rangoFechaFin = $la__dataActividad->col_fecha_inicio;

    $extraData = explode('|', unserialize(stripslashes($la__dataActividad->col_visible_excepto)));
    $clavesMateria = getClavesPosibles($extraData[1], $db);
    $result['claveMateria'] = implode(',', $clavesMateria);

    switch($la__dataActividad->col_tipo){
        case 6:
        $sth = $db->prepare('SELECT * FROM tbl_actividades WHERE col_visible_excepto="'.addslashes($la__dataActividad->col_visible_excepto).'" AND col_tipo=5');
        $sth->execute();
        $tbt__dataActividad = $sth->fetch(PDO::FETCH_OBJ);
        $rangoFechaInicio = date('Y-m-d', strtotime('+1 day'. $tbt__dataActividad->col_fecha_inicio));
        break;

        case 7:
            $sth = $db->prepare('SELECT * FROM tbl_actividades WHERE col_visible_excepto="'.addslashes($la__dataActividad->col_visible_excepto).'" AND col_tipo=6');
            $sth->execute();
            $tbt__dataActividad = $sth->fetch(PDO::FETCH_OBJ);
            $rangoFechaInicio = date('Y-m-d', strtotime('+1 day'. $tbt__dataActividad->col_fecha_inicio));
        break;
    }

    $falsifico = 'no';
    $subQuery = 'SELECT col_id FROM tbl_actividades WHERE col_fecha_inicio<"'.$queryFechaInicio.'" AND col_visible_excepto LIKE "%'.$claveTL.'%"';
    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid IN ('.$subQuery.') AND col_falsificacion=1';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount()) $falsifico = 'si';

    $result['asistenciasFechaInicio'] = $rangoFechaInicio;
    $result['asistenciasFechaFinal'] = $rangoFechaFin;

    $asistencias = getAsistenciasByAlumnoAndMateria($alumnoid, $db, $rangoFechaInicio, $rangoFechaFin, 0, true);
    // $asistencias = getAsistenciasByAlumnoAndMateria($alumnoid, $db, $rangoFechaInicio, $rangoFechaFin, $materiaid);
    // $result['debug'] = $asistencias;
    // $result['debug_alumnoid'] = $alumnoid;
    // $result['debug_inicio'] = $rangoFechaInicio;
    // $result['debug_fin'] = $rangoFechaFin;
    // $result['debug_periodo'] = $periodoid;

    $hasAsistencias = 0;

    foreach($asistencias as $item){
        // $result['debug'][] = strtoupper(substr($item['clave'], 0, 2));
        if(strtoupper(substr($item['clave'], 0, 2)) == 'TL' && in_array(trim($item['clave']), $clavesMateria) ){
            $hasAsistencias = 1;
            $porcentajeAsistencias = $item['porcentaje_asistencias'];
            $faltasAsistencias = $item['faltas'];
            $result['debug0'] = $item;
            if(intval($item['participaciones']) > 0) {
                $division = ($item['participaciones'] / $item['clasesTotal']) * 10;
                // $result['debug1'] = $item['participaciones'];
                // $result['debug2'] = $item['clasesTotal'];
                $participacion = (20*$division)/10;
                $participacion = 20;
                // $participacion = $ponderacion['participacion'].'*'.$division;
            }

            break;
        }
    }

    if($hasAsistencias == 0) {
        $result['debug'] = 'No hay listas';
        $porcentajeAsistencias = 100;
        $faltasAsistencias = 0;
    }

    $result['calificacionLimpia'] = $calificacion;
    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_calificacion>1 AND col_actividadid="'.$dataActividad->col_actividadid.'" AND col_alumnoid!="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() == 0) if($calificacion == '1.00') $calificacion = 10;
    $final = (100*$calificacion)/10;
    $result['calificacionPorcentaje'] = $final;
    // $final = (floatval($final) + floatval($participacion)) / 10;
    $result['calificacionParticipaciones'] = $participacion;
    $result['calificacionPorcentajeSuma'] = $final;
    $deudas = tieneDeudas($db, $alumnoid, '', $queryFechaInicio);

    $result['reduccion'] = 0;
    $result['tipo'] = 'Taller';
    $result['faltas'] = $faltasAsistencias;
    $result['asistenciasPorcentaje'] = number_format($porcentajeAsistencias, 2);
    if($final > 10) $final = $final / 10;
    $result['acredita'] = ($final >= 7?'A':'NA');

    if($result['acredita'] == 'NA' && intval($result['asistenciasPorcentaje']) >= 80){
        $result['reduccion'] = 50;
    }
    if($falsifico == 'si' || $deudas['status'] == 'true' || intval($result['asistenciasPorcentaje']) < 80){
        $result['acredita'] = 'NA';
        $result['reduccion'] = 100;
        $result['deudas'] = $deudas['status'];
        $result['falsifico'] = $falsifico;
        $result['db'] = 'no';
    }

    if($dataActividad->col_sd == 1 && $dataActividad->col_sd_razon != '') {
        $result['acredita'] = 'NA';
        $result['reduccion'] = 100;
        $result['deudas'] = $deudas['status'];
        $result['falsifico'] = $falsifico;
        $result['db'] = 'si';
    }

    return $result;
}
function acreditaMEClubLectura($alumnoid, $actividadid, $db) {

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataActividad = $sth->fetch(PDO::FETCH_OBJ);
    $periodoid = intval(unserialize(stripslashes($dataActividad->col_visible_excepto)));
    $periodoData = getPeriodo($periodoid, $db, false);


    // Pedimos CL activos en el periodo del alumno
    $queryCL = "SELECT * FROM tbl_maestros_taxonomia WHERE col_materia_clave LIKE 'CL%' AND col_periodoid='".$alumnoData->col_periodoid."'";
    $sth = $db->prepare($queryCL);
    $sth->execute();
    $dataCL = $sth->fetch(PDO::FETCH_OBJ);
    $claveCL = $dataCL->col_materia_clave;

    $query = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.$claveCL.'%" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'" AND col_semestre="'.$periodoData->col_grado.'" AND col_carrera="'.$periodoData->col_carreraid.'"';

    $sth = $db->prepare($query);
    $sth->execute();
    $dataMateria = $sth->fetch(PDO::FETCH_OBJ);
    if(!$dataMateria) {
        $result['acredita'] = 'A';
        $result['reduccion'] = 0;
        return $result;
    }



    if($dataActividad->col_tipo == 5){
        $rangoFechaInicio =  $periodoData->col_fecha_inicio;
    }else{
        list($rangoFechaInicio, $rangoFechaFin) = getRangoFechas($db, $alumnoid, $periodoid);
    }
    $queryFechaInicio = $rangoFechaFin = $dataActividad->col_fecha_inicio;

    switch($dataActividad->col_tipo){
        case 6:
        $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.$alumnoData->col_periodoid.'%" AND col_fecha_inicio<="'.$queryFechaInicio.'" AND col_materiaid="'.$dataMateria->col_id.'" AND col_tipo="5"';
        // $sth = $db->prepare('SELECT * FROM tbl_actividades WHERE col_visible_excepto="'.addslashes($dataActividad->col_visible_excepto).'" AND col_materiaid="'.$dataActividad->col_materiaid.'" AND col_tipo=5');
        $sth = $db->prepare($query);
        $sth->execute();
        $tbt__dataActividad = $sth->fetch(PDO::FETCH_OBJ);
        $rangoFechaInicio = date('Y-m-d', strtotime('+1 day'. $tbt__dataActividad->col_fecha_inicio));
        break;

    }

    $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.$alumnoData->col_periodoid.'%" AND col_fecha_inicio<="'.$queryFechaInicio.'" AND col_materiaid="'.$dataMateria->col_id.'" AND col_tipo="'.$dataActividad->col_tipo.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $predataActividad = $sth->fetch(PDO::FETCH_OBJ);
    $laActividadid = $predataActividad->col_id;
    $_fechaActividad = $rangoFechaFin = $predataActividad->col_fecha_inicio;

    $subQuery = 'SELECT col_id FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.$alumnoData->col_periodoid.'%" AND col_fecha_inicio<="'.$queryFechaInicio.'" AND col_materiaid="'.$dataMateria->col_id.'" AND col_tipo="'.$dataActividad->col_tipo.'"';
    // $subQuery = 'SELECT col_id FROM tbl_actividades WHERE col_fecha_inicio<="'.$queryFechaInicio.'" AND col_visible_excepto ="'.addslashes($dataActividad->col_visible_excepto).'" AND col_tipo="'.$dataActividad->col_tipo.'"';
    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid IN ('.$subQuery.')';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() == 0) return;
    $dataActividad = $sth->fetch(PDO::FETCH_OBJ);

    $calificacion = corregirCalificacion($dataActividad->col_calificacion);

    $falsifico = 'no';
    $subQuery = 'SELECT col_id FROM tbl_actividades WHERE col_fecha_inicio<"'.$queryFechaInicio.'" AND col_materiaid="'.$dataMateria->col_id.'"';
    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid IN ('.$subQuery.') AND col_falsificacion=1';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount()) $falsifico = 'si';

    $result['asistenciasFechaInicio'] = $rangoFechaInicio;
    $result['asistenciasFechaFinal'] = $rangoFechaFin;
    $asistencias = getAsistenciasByAlumnoAndMateria($alumnoid, $db, $rangoFechaInicio, $rangoFechaFin, 0, true);
    // $result['rangoFechaInicio'] = $rangoFechaInicio;
    // $result['rangoFechaFin'] = $rangoFechaFin;


    $hasAsistencias = 0;

    foreach($asistencias as $item){
        if(strtoupper(substr($item['clave'], 0, 2)) == 'CL'){
            $hasAsistencias = 1;
            $porcentajeAsistencias = $item['porcentaje_asistencias'];
            $faltasAsistencias = $item['faltas'];
            if(intval($item['participaciones']) > 0) {
                $division = ($item['participaciones'] / $item['clasesTotal']) * 10;
                // $result['debug1'] = $item['participaciones'];
                // $result['debug2'] = $item['clasesTotal'];
                $participacion = (20*$division)/10;
                $participacion = 20;
                // $participacion = $ponderacion['participacion'].'*'.$division;
            }
            break;
        }
    }

    if($hasAsistencias == 0) {
        $result['debug'] = 'No hay listas';
        $porcentajeAsistencias = 100;
        $faltasAsistencias = 0;
    }

    // Calculamos el 80%
    $result['calificacionExamenLimpia'] = $calificacion;
    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_calificacion>1 AND col_actividadid="'.$dataActividad->col_actividadid.'" AND col_alumnoid!="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() == 0) if($calificacion == '1.00') $calificacion = 10;
    $result['hasCalificacion'] = $sth->rowCount();
    $final = (80*$calificacion)/10;
    $result['calificacionExamen'] = $final;
    $result['examenClubLectura'] = $laActividadid;

    $totalParticipaciones = getTotalParticipaciones($alumnoid, $dataMateria->col_id, $laActividadid, $db, $_fechaActividad);
    $totalesActividades = getTotalesActividades($alumnoid, $laActividadid, $db, $_fechaActividad);

    $ponderacion = unserialize(stripslashes($predataActividad->col_ponderacion));
    $tareas = 0;
    $investigacion = 0;
    $lecturas = 0;
    $debates = 0;
    $participacion = 0;

    if(removePorcentaje($ponderacion['tarea']) > 0) {
        if($totalesActividades['enclase']['calificacion'] > 0) $totalesActividades['enclase']['calificacion'] = $totalesActividades['enclase']['calificacion'] * 10;
        $division = (($totalesActividades['tarea']['calificacion'] + $totalesActividades['enclase']['calificacion']) / ($totalesActividades['tarea']['total'] + $totalesActividades['enclase']['total'])) * 10;
        $tareas = (removePorcentaje($ponderacion['tarea'])*$division)/100;
        $result['calificacionTareas'] = $tareas;
    }
    if(removePorcentaje($ponderacion['investigacion']) > 0) {
        $division = ($totalesActividades['investigacion']['calificacion'] / $totalesActividades['investigacion']['total']) * 10;
        $investigacion = (removePorcentaje($ponderacion['investigacion'])*$division)/100;
        $result['calificacionInvestigacion'] = $investigacion;
    }
    if(removePorcentaje($ponderacion['lecturas']) > 0) {
        $division = ($totalesActividades['lectura']['calificacion'] / $totalesActividades['lectura']['total']) * 10;
        $lecturas = (removePorcentaje($ponderacion['lecturas'])*$division)/100;
        $result['calificacionLecturas'] = $lecturas;
    }
    if(removePorcentaje($ponderacion['debates']) > 0) {
        $division = ($totalesActividades['debates']['calificacion'] / $totalesActividades['debates']['total']) * 10;
        $debates = (removePorcentaje($ponderacion['debates'])*$division)/100;
        $result['calificacionDebates'] = $debates;
    }

    if(removePorcentaje($ponderacion['participacion']) > 0) {
        $division = ($totalParticipaciones['suma'] / $totalParticipaciones['max']) * 10;
        $participacion = (removePorcentaje($ponderacion['participacion'])*$division)/10;
        $result['calificacionParticipacion'] = $participacion;
    }

    $final = ($final + floatval($tareas) + floatval($investigacion) + floatval($lecturas) + floatval($debates) + floatval($participacion)) / 10;

    $result['calificacionFinalLimpia'] = $final;

    if($final >= 7) {
        $final = '1.00';
    }else{
        $final = 0;
    }
    $result['calificacionFinal'] = $final;

    $deudas = tieneDeudas($db, $alumnoid, '', $queryFechaInicio);

    $result['reduccion'] = 0;
    $result['tipo'] = 'Club de Lectura';
    $result['faltas'] = $faltasAsistencias;
    $result['asistenciasPorcentaje'] = number_format($porcentajeAsistencias, 2);
    $result['acredita'] = (intval($final) >= 1?'A':'NA');

    if($result['acredita'] == 'NA' && intval($result['asistenciasPorcentaje']) >= 80){
        $result['reduccion'] = 50;
    }
    if($falsifico == 'si' || $deudas['status'] == 'true' || intval($result['asistenciasPorcentaje']) < 80){
        $result['acredita'] = 'NA';
        $result['reduccion'] = 100;
        $result['deudas'] = $deudas['status'];
        $result['falsifico'] = $falsifico;
        $result['debugAsistencias'] = $asistencias;
    }

    return $result;
}
function acreditaMETransversales($alumnoid, $actividadid, $db) {

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataActividad = $sth->fetch(PDO::FETCH_OBJ);
    $periodoid = intval(unserialize(stripslashes($dataActividad->col_visible_excepto)));
    $periodoData = getPeriodo($periodoid, $db, false);

    $materiaID = $periodoData->col_transversal;
    // Verificamos la materia ID


    $query = "SELECT c.col_materia_clave FROM tbl_calificaciones c WHERE c.col_periodoid='".$periodoData->col_id."' AND c.col_materia_clave=(SELECT col_clave FROM tbl_materias WHERE col_id='".$materiaID."') AND col_alumnoid='".$alumnoid."' AND col_estatus=1 GROUP BY col_materia_clave LIMIT 1";
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() == 0) {

        $query = "SELECT * FROM tbl_materias WHERE col_clave=(SELECT c.col_materia_clave FROM tbl_calificaciones c WHERE c.col_periodoid='".$periodoData->col_id."' AND c.col_materia_clave LIKE 'TR%' AND col_alumnoid='".$alumnoid."' AND col_estatus=1 GROUP BY col_materia_clave LIMIT 1) AND col_carrera='".$periodoData->col_carreraid."' AND col_semestre='".$periodoData->col_grado."' AND col_plan_estudios='".$periodoData->col_plan_estudios."'";
        $sth = $db->prepare($query);
        $sth->execute();
        if($sth->rowCount() > 0) {
            $dataMateriaCalificaciones = $sth->fetch(PDO::FETCH_OBJ);
            if($dataMateriaCalificaciones->col_id != $materiaID) {
                $materiaID = $dataMateriaCalificaciones->col_id;
            }
        }

    }

    // list($rangoFechaInicio, $rangoFechaFin) = getRangoFechas($db, $alumnoid, $periodoid);
    $periodoData = getPeriodo($periodoid, $db, false);
    if($dataActividad->col_tipo == 5){
        $rangoFechaInicio =  $periodoData->col_fecha_inicio;
    }else{
        list($rangoFechaInicio, $rangoFechaFin) = getRangoFechas($db, $alumnoid, $periodoid);
    }
    $queryFechaInicio = $rangoFechaFin = $dataActividad->col_fecha_inicio;

    switch($dataActividad->col_tipo){
        case 6:
        $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.$alumnoData->col_periodoid.'%" AND col_fecha_inicio<="'.$queryFechaInicio.'" AND col_materiaid="'.$materiaID.'" AND col_tipo="5"';
        // $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto="'.addslashes($dataActividad->col_visible_excepto).'" AND col_tipo=5 AND col_materiaid="'.$materiaID.'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $tbt__dataActividad = $sth->fetch(PDO::FETCH_OBJ);
        $rangoFechaInicio = date('Y-m-d', strtotime('+1 day'. $tbt__dataActividad->col_fecha_inicio));
        // $result['_asistenciasFechaInicio'] = $query;
        // $result['__asistenciasFechaInicio'] = $rangoFechaInicio;
        break;
    }

    $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.$alumnoData->col_periodoid.'%" AND col_fecha_inicio<="'.$queryFechaInicio.'" AND col_materiaid="'.$materiaID.'" AND col_tipo="'.$dataActividad->col_tipo.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() == 0) return;
    $predataActividad = $sth->fetch(PDO::FETCH_OBJ);
    $laActividadid = $predataActividad->col_id;
    $_fechaActividad = $predataActividad->col_fecha_inicio;

    $rangoFechaFin = $predataActividad->col_fecha_inicio;

    $subQuery = 'SELECT col_id FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.$alumnoData->col_periodoid.'%" AND col_fecha_inicio<="'.$queryFechaInicio.'" AND col_materiaid="'.$materiaID.'" AND col_tipo="'.$dataActividad->col_tipo.'"';
    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid IN ('.$subQuery.')';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() == 0) return;
    $dataActividad = $sth->fetch(PDO::FETCH_OBJ);

    $calificacion = corregirCalificacion($dataActividad->col_calificacion);

    $falsifico = 'no';
    $subQuery = 'SELECT col_id FROM tbl_actividades WHERE col_fecha_inicio<"'.$queryFechaInicio.'" AND col_materiaid="'.$materiaID.'"';
    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid IN ('.$subQuery.') AND col_falsificacion=1';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount()) $falsifico = 'si';
    $result['asistenciasFechaInicio'] = $rangoFechaInicio;
    $result['asistenciasFechaFinal'] = $rangoFechaFin;

    if($alumnoid != 1225) return;
    $asistencias = getAsistenciasByAlumnoAndMateria($alumnoid, $db, $rangoFechaInicio, $rangoFechaFin, 0, true);

    foreach($asistencias as $item){
        if(trim(strtoupper(substr($item['clave'], 0, 2))) == 'TR'){
            $dataAsistencias = $item;
            $porcentajeAsistencias = $item['porcentaje_asistencias'];
            $faltasAsistencias = $item['faltas'];
            break;
        }
    }

    $totalParticipaciones = getTotalParticipaciones($alumnoid, $materiaID, $laActividadid, $db, $_fechaActividad);
    $totalesActividades = getTotalesActividades($alumnoid, $laActividadid, $db, $_fechaActividad);
    // Calculamos el 80%

    $result['calificacionExamenLimpia'] = $calificacion;
    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_calificacion>1 AND col_actividadid="'.$dataActividad->col_actividadid.'" AND col_alumnoid!="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() == 0) if($calificacion == '1.00') $calificacion = 10;
    $final = (80*$calificacion)/10;
    $result['hasCalificacion'] = $sth->rowCount();
    $result['calificacionExamen'] = $final;
    $result['examenTransversal'] = $laActividadid;
    $result['examenFechaTransversal'] = $_fechaActividad;
    $ponderacion = unserialize(stripslashes($predataActividad->col_ponderacion));
    $tareas = 0;
    $investigacion = 0;
    $lecturas = 0;
    $debates = 0;
    $participacion = 0;
    if(removePorcentaje($ponderacion['tarea']) > 0) {
        if($totalesActividades['enclase']['calificacion'] > 0) $totalesActividades['enclase']['calificacion'] = $totalesActividades['enclase']['calificacion'] * 10;
        $division = (($totalesActividades['tarea']['calificacion'] + $totalesActividades['enclase']['calificacion']) / ($totalesActividades['tarea']['total'] + $totalesActividades['enclase']['total'])) * 10;
        $tareas = (removePorcentaje($ponderacion['tarea'])*$division)/100;
        $result['calificacionTareas'] = $tareas;
    }
    //print_r($totalesActividades);exit;
    if(removePorcentaje($ponderacion['investigacion']) > 0) {
        // if($totalesActividades['investigacion']['total'] == 0){
        //     $ponderacion['participacion'] = intval($ponderacion['participacion']) + intval($ponderacion['investigacion']);
        // }
        $division = ($totalesActividades['investigacion']['calificacion'] / $totalesActividades['investigacion']['total']) * 10;
        $investigacion = (removePorcentaje($ponderacion['investigacion'])*$division)/100;
        $result['calificacionInvestigacion'] = $investigacion;
    }
    if(removePorcentaje($ponderacion['lecturas']) > 0) {
        // if($totalesActividades['lectura']['total'] == 0){
        //     $ponderacion['participacion'] = intval($ponderacion['participacion']) + intval($ponderacion['lecturas']);
        // }
        $division = ($totalesActividades['lectura']['calificacion'] / $totalesActividades['lectura']['total']) * 10;
        $lecturas = (removePorcentaje($ponderacion['lecturas'])*$division)/100;
        $result['calificacionLecturas'] = $lecturas;
    }
    if(removePorcentaje($ponderacion['debates']) > 0) {
        // if($totalesActividades['debates']['total'] == 0){
        //     $ponderacion['participacion'] = intval($ponderacion['debates']) + intval($ponderacion['debates']);
        // }
        $division = ($totalesActividades['debates']['calificacion'] / $totalesActividades['debates']['total']) * 10;
        $debates = (removePorcentaje($ponderacion['debates'])*$division)/100;
        $result['calificacionDebates'] = $debates;
    }
    if(removePorcentaje($ponderacion['participacion']) > 0) {
        $division = ($totalParticipaciones['suma'] / $totalParticipaciones['max']) * 10;
        $participacion = (removePorcentaje($ponderacion['participacion'])*$division)/10;
        $result['calificacionParticipacion'] = $participacion;
        // $participacion = $ponderacion['participacion'].'*'.$division;
    }

    $final = ($final + floatval($tareas) + floatval($investigacion) + floatval($lecturas) + floatval($debates) + floatval($participacion)) / 10;
    $result['calificacionFinalLimpia'] = $final;
    //$result['xxx'] = $final;
    if($final >= 7) {
        $final = '1.00';
    }else{
        $final = 0;
    }

    $result['calificacionFinal'] = $final;

    $deudas = tieneDeudas($db, $alumnoid, '', $queryFechaInicio);

    $result['reduccion'] = 0;
    $result['periodoData'] = $periodoData;
    $result['tipo'] = 'Transversal';
    $result['faltas'] = $faltasAsistencias;
    $result['asistenciasDebug'] = $asistencias;
    $result['asistenciasDataDebug'] = $dataAsistencias;
    $result['asistenciasPorcentaje'] = number_format($porcentajeAsistencias, 2);
    $result['acredita'] = (intval($final) >= 1?'A':'NA');

    if($result['acredita'] == 'NA' && intval($result['asistenciasPorcentaje']) >= 80){
        $result['reduccion'] = 50;
    }
    if($falsifico == 'si' || $deudas['status'] == 'true' || intval($result['asistenciasPorcentaje']) < 80){
        $result['acredita'] = 'NA';
        $result['reduccion'] = 100;
        $result['deudas'] = $deudas['status'];
        $result['falsifico'] = $falsifico;
        //$result['asistenciasPorcentaje'] = intval($result['asistenciasPorcentaje']);
    }

    return $result;

}

function acreditaMEPracticas($alumnoid, $actividadid = 0, $tipo, $db) {

    $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
    $c = $db->prepare($query);
    $c->execute();
    $config = $c->fetch(PDO::FETCH_OBJ);
    if($config->col_practicas == 0){
        // Todos acreditan por COVID-19 29 de Abril de 2020
        $result['acredita'] = 'A';
        $result['reduccion'] = 0;

        return $result;
    }

    if(intval($actividadid) == 0) {
        $result['acredita'] = 'NA';
        $result['reduccion'] = 100;

        return $result;
    }


    // if($tipo == 5) $reportes = 6;
    if($tipo == 5) $reportes = 3; // Se definio el numero de practicas a 3 como apoyo extraordinario por la contingencia del COVID-19, pasando esto deberia reestablecerse a 6
    if($tipo == 6) $reportes = 4;
    if($tipo == 7) $reportes = 0;
    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $actividadData = $sth->fetch(PDO::FETCH_OBJ);

    $periodoData = getPeriodo($alumnoData->col_periodoid, $db, false);
    if($periodoData->col_grado > 6) return;
    // $periodoid = intval(unserialize(stripslashes($alumnoData->col_periodoid)));

    $query = 'SELECT * FROM tbl_practicas WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$alumnoData->col_periodoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $practicasData = $sth->fetch(PDO::FETCH_OBJ);

    // 0: En Revision
    // 1: Rechazado
    // 2; Aprobado
    // 3: Extemporaneo
    // 4: Falsificado
    // 5: Liberación

    if($tipo == 5) {
        $fechaQuery = '(col_created_at > "'.substr($periodoData->col_fecha_inicio, 0 ,10).' 00:00:00" AND col_created_at < "'.substr($actividadData->col_fecha_inicio, 0 ,10).' 23:59:59")';
    }

    if($tipo == 6) {
        // $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto="'.addslashes($actividadData->col_visible_excepto).'" AND col_materiaid="'.$actividadData->col_materiaid.'" AND col_created_by="'.$actividadData->col_created_by.'" AND col_tipo="5"';
        // $sth = $db->prepare($query);
        // $sth->execute();
        // $actividadPrimerParcial = $sth->fetch(PDO::FETCH_OBJ);
        $actividadPrimerParcial = '';
        $queryFechaPrimerParcial = "SELECT a.col_id, a.col_titulo, a.col_fecha_inicio, m.col_id AS materiaid, m.col_nombre AS materia, m.col_clave AS clave FROM tbl_actividades a ".
        "LEFT OUTER JOIN tbl_materias m ON m.col_id=a.col_materiaid WHERE a.col_visible_excepto LIKE '%".$alumnoData->col_periodoid."%' AND a.col_tipo='5' AND a.col_materiaid ".
        "AND (m.col_clave NOT LIKE 'TR%' AND m.col_clave NOT LIKE 'CL%') ORDER BY a.col_fecha_inicio ASC";
        $sth = $db->prepare($queryFechaPrimerParcial);
        $sth->execute();
        $todosParciales1 = $sth->fetchAll();
        $iep = 1;
        foreach($todosParciales1 as $ep){
            if($iep == 5){
                $actividadPrimerParcial = $ep['col_fecha_inicio'];
            }
            $iep++;
        }
        $fechaQuery = '(col_created_at > "'.substr($actividadPrimerParcial, 0, 10).' 00:00:00" AND col_created_at < "'.substr($actividadData->col_fecha_inicio, 0, 10).' 23:59:59")';
        // $fechaQuery = '(col_created_at > "'.substr($actividadPrimerParcial->col_fecha_inicio, 0, 10).' 23:59:59" AND col_created_at < "'.substr($actividadData->col_fecha_inicio, 0, 10).' 23:59:59")';
    }

    if($tipo == 7) {
        $fechaQuery = 'col_created_at < "'.substr($actividadData->col_fecha_inicio, 0, 10).' 23:59:59"';
    }

    $query = 'SELECT * FROM tbl_practicas_archivos WHERE '.$fechaQuery.' AND col_alumnoid="'.$alumnoid.'" AND col_practicaid="'.$practicasData->col_id.'" AND col_estatus>1';
    $sth = $db->prepare($query);
    $sth->execute();
    $entregados = $sth->rowCount();

    $query = 'SELECT * FROM tbl_practicas_archivos WHERE '.$fechaQuery.' AND col_alumnoid="'.$alumnoid.'" AND col_practicaid="'.$practicasData->col_id.'" AND col_estatus=2';
    $sth = $db->prepare($query);
    $sth->execute();
    $aprobados = $sth->rowCount();

    $falsifico = 'no';
    $query = 'SELECT * FROM tbl_practicas_archivos WHERE '.$fechaQuery.' AND col_alumnoid="'.$alumnoid.'" AND col_practicaid="'.$practicasData->col_id.'" AND col_estatus="4"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() > 0) $falsifico = 'si';

    $liberacion = 'no';
    $query = 'SELECT * FROM tbl_practicas_archivos WHERE col_alumnoid="'.$alumnoid.'" AND col_practicaid="'.$practicasData->col_id.'" AND col_estatus="5"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() > 0) $liberacion = 'si';

    $extemporaneo = 'no';
    $query = 'SELECT * FROM tbl_practicas_archivos WHERE '.$fechaQuery.' AND col_alumnoid="'.$alumnoid.'" AND col_practicaid="'.$practicasData->col_id.'" AND col_estatus="3"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() > 0) $extemporaneo = 'si';


    $result['reduccion'] = 0;
    $result['tipo'] = 'Practicas';

    // $result['acredita'] = ($liberacion == 'si'?'A':'NA');
    $result['fechasDebug'] = $fechaQuery;
    $result['practicaID'] = $practicasData->col_id;
    $result['entregados'] = $entregados;
    $result['reportes'] = $reportes;

    if($extemporaneo == 'si' || ($entregados > 0 && $entregados < $reportes)){
        $result['reduccion'] = 50;
        $result['acredita'] = 'NA';
    }
    if($falsifico == 'si' || $entregados == 0){
        $result['acredita'] = 'NA';
        $result['reduccion'] = 100;
        //$result['deudas'] = $deudas['status'];
        $result['falsifico'] = $falsifico;
    }

    if($extemporaneo == 'no' && $falsifico == 'no' && ($tipo == 5 || $tipo ==6) && $aprobados >= $reportes){
        $result['acredita'] = 'A';
    }

    if($extemporaneo == 'no' && $falsifico == 'no' && $tipo == 7 && $liberacion == 'si'){
        $result['acredita'] = 'A';
    }

    if($tipo == 7 && $liberacion == 'no'){
        $result['acredita'] = 'NA';
        $result['reduccion'] = 100;
    }

    if($tipo == 7 && $liberacion !== 'no'){
        $result['acredita'] = 'A';
        $result['reduccion'] = 0;
    }

    // Todos acreditan por COVID-19 29 de Abril de 2020
    // $result['acredita'] = 'A';
    // $result['reduccion'] = 0;

    return $result;
}

function acreditaMEServicio($alumnoid, $actividadid, $tipo, $db) {

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
    $periodoData = getPeriodo($alumnoData->col_periodoid, $db, false);
    // echo $periodoData->col_grado;exit;
    if($periodoData->col_grado < 7) return;
    // $periodoid = intval(unserialize(stripslashes($alumnoData->col_periodoid)));

    $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $actividadData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_servicio_social WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$alumnoData->col_periodoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $practicasData = $sth->fetch(PDO::FETCH_OBJ);

    // 0: En Revision
    // 1: Rechazado
    // 2; Aprobado
    // 3: Extemporaneo
    // 4: Falsificado
    // 5: Liberación

    $query = 'SELECT * FROM tbl_servicio_social_archivos WHERE col_alumnoid="'.$alumnoid.'" AND col_servicioid="'.$practicasData->col_id.'" AND col_estatus>1';
    $sth = $db->prepare($query);
    $sth->execute();
    $entregados = $sth->rowCount();

    $query = 'SELECT * FROM tbl_servicio_social_archivos WHERE col_alumnoid="'.$alumnoid.'" AND col_servicioid="'.$practicasData->col_id.'" AND col_estatus=2';
    $sth = $db->prepare($query);
    $sth->execute();
    $aprobados = $sth->rowCount();

    $falsifico = 'no';
    $query = 'SELECT * FROM tbl_servicio_social_archivos WHERE col_alumnoid="'.$alumnoid.'" AND col_servicioid="'.$practicasData->col_id.'" AND col_estatus="4"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() > 0) $falsifico = 'si';

    $liberacion = 'no';
    $query = 'SELECT * FROM tbl_servicio_social_archivos WHERE col_alumnoid="'.$alumnoid.'" AND col_servicioid="'.$practicasData->col_id.'" AND col_estatus="5"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() > 0) $liberacion = 'si';

    $extemporaneo = 'no';
    $query = 'SELECT * FROM tbl_servicio_social_archivos WHERE col_alumnoid="'.$alumnoid.'" AND col_servicioid="'.$practicasData->col_id.'" AND col_estatus="3"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() > 0) $extemporaneo = 'si';


    $result['reduccion'] = 0;
    $result['reportesEntregados'] = $entregados;
    $result['reportesAprobados'] = $aprobados;
    $result['tipo'] = 'Servicio Social';

    // $result['acredita'] = ($liberacion == 'si'?'A':'NA');

    if($extemporaneo == 'si' && $entregados > 0){
        $result['reduccion'] = 50;
        $result['acredita'] = 'NA';
    }

    if($entregados == 0){
        $result['acredita'] = 'NA';
        $result['reduccion'] = 50;
    }

    if($falsifico == 'si' || $deudas['status'] == 'true'){
        $result['acredita'] = 'NA';
        $result['reduccion'] = 100;
        $result['deudas'] = $deudas['status'];
        $result['falsifico'] = $falsifico;
    }

    if($extemporaneo == 'no' && $falsifico == 'no' && $tipo != 7 && $aprobados > 0){
        $result['acredita'] = 'A';
        $result['reduccion'] = 0;
    }

    if($extemporaneo == 'no' && $falsifico == 'no' && $tipo == 7 && $aprobados >= 3 && $liberacion == 'si'){
        $result['acredita'] = 'A';
        $result['reduccion'] = 0;
    }

    // Todos acreditan por COVID-19 29 de Abril de 2020
    $result['acredita'] = 'A';
    $result['reduccion'] = 0;

    return $result;
}

function acreditaMEAltruista($alumnoid, $db, $periodo = 0) {

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
    if(intval($periodo) > 0) {
        $alumnoData->col_periodoid = intval($periodo);
    }
    $periodoData = getPeriodo($alumnoData->col_periodoid, $db, false);

    $query = 'SELECT * FROM tbl_altruista_integrantes WHERE col_alumnoid="'.$alumnoid.'" AND col_group_periodoid="'.$alumnoData->col_periodoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $integrantesData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_altruista_actividades WHERE col_group_periodoid="'.$alumnoData->col_periodoid.'" AND col_grupo="'.$integrantesData->col_grupo.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    // $actividadesData = $sth->fetch(PDO::FETCH_OBJ);
    $totalActividades = $sth->rowCount();
    $actividadesData = $sth->fetchAll();
    $asistencias = 0;
    foreach($actividadesData as $item) {
        $query = 'SELECT * FROM tbl_altruista_asistencia WHERE col_alumnoid="'.$alumnoid.'" AND col_actividad="'.$item['col_id'].'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $asistencia = $sth->fetch(PDO::FETCH_OBJ);
        if($asistencia->col_asistencia == 1) {
            $asistencias++;
        }
    }

    $result['totalActividades'] = $totalActividades;
    $result['asistencias'] = $asistencias;

    if($asistencias < $totalActividades){
        $result['reduccion'] = 100;
        $result['acredita'] = 'NA';
    }else{
        $result['acredita'] = 'A';
        $result['reduccion'] = 0;
    }

    return $result;
}

function corregirCalificacion($value){
    $value = explode('.', $value);
    $enteros = intval($value[0]);
    if($enteros > 10) {
        if(substr($enteros, 0, 1) == 1 && strlen($enteros) > 1){
            $enteros = 10;
        }else{
            $enteros = substr($enteros, 0, 1);
        }
    }
    return $enteros.'.'.$value[1];
}

function guardarCalificacionesFinales($alumnoid, $actividadid, $db) {
    global $redondearCalculosCalificacionesFinales;
    $maestroID = getCurrentUserID();


    $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
    $c = $db->prepare($query);
    $c->execute();
    $config = $c->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.intval($actividadid).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataActividad = $sth->fetch(PDO::FETCH_OBJ);
    if($dataActividad->col_tipo < 5 || $dataActividad->col_tipo > 10) return;

    $_fechaActividad = $dataActividad->col_fecha_inicio;
    $ponderacion = unserialize(stripslashes($dataActividad->col_ponderacion));
    $ordenExamen = getExamenOrden($dataActividad->col_id, $db);
    $curricular = 'si';



    if($dataActividad->col_materiaid > 0){
        $materiaID = $dataActividad->col_materiaid;
    } else {
        $materiaID = getMateriaByActividadID($dataActividad->col_visible_excepto, $db, 0, $actividadid);
    }

    $query = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaID.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $materiaData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);


    $visibleData = unserialize(stripslashes($dataActividad->col_visible_excepto));
    if(intval($visibleData) > 0) {
        $periodoData = getPeriodo(intval($visibleData), $db, false);
    }else{
        $_arrayVD = explode('|', $visibleData);
        $_periodosVD = explode(',', $_arrayVD[2]);
        $periodoData = getPeriodo(intval($_periodosVD[0]), $db, false);
    }


    $periodoDataAlumno = getPeriodo($alumnoData->col_periodoid, $db, false);
    // $periodoData = getPeriodo($alumnoData->col_periodoid, $db, false);
    // $carreraData = getCarrera($alumnoData->col_carrera, $db);

    $ponderacionExamen = 80;

    if($periodoDataAlumno->isPosgrado == 1) {
        $ponderacion = getPonderacion($materiaID, $periodoDataAlumno->col_id, $db);
        $ponderacionExamen = removePorcentaje($ponderacion['examen']);
    }else{
        $ponderacion = unserialize(stripslashes($dataActividad->col_ponderacion));
    }


    if(in_array(strtoupper(substr($materiaData->col_clave, 0, 2)), array('TL', 'AC', 'CL', 'TR')) || $periodoData->col_modalidad > 1) {
        $curricular = 'no';
    }

    switch($dataActividad->col_tipo){
        case 5:$col = 'col_p1';break;
        case 6:$col = 'col_p2';break;
        case 7:$col = 'col_ef';break;
        case 8:$col = 'col_ext';break;
        case 9:$col = 'col_ts';break;
    }

    $hasSD = false;
    // Calculamos calificacion final
    $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid="'.$dataActividad->col_id.'"';
    $sth_tt = $db->prepare($query);
    $sth_tt->execute();
    $tareas = $sth_tt->fetchAll();
    $i = 1;
    foreach($tareas as $tarea){
        $calificacion = corregirCalificacion($tarea['col_calificacion']);
        // $calificacionDebug = corregirCalificacion($tarea['col_calificacion']);
    }

    $ignorarParticipaciones = false;
    if(in_array(strtoupper(substr($materiaData->col_clave, 0, 2)), array('AC'))) {
        $ignorarParticipaciones = true;
    }
    if(in_array(strtoupper(substr($materiaData->col_clave, 0, 2)), array('TL', 'AC', 'CL'))) {

        $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_calificacion>1 AND col_actividadid="'.$dataActividad->col_id.'" AND col_alumnoid!="'.$alumnoid.'"';
        $sth = $db->prepare($query);
        $sth->execute();
        if($sth->rowCount() == 0) {
            if($calificacion == '1.00') $calificacion = 10;
        }
    }

    $finalIntegro = $calificacion;
    //echo $finalIntegro;exit;
    $final = ($ponderacionExamen*$calificacion)/10;
    //echo $final;exit;
    $finalDebug = $final;

    if($dataActividad->col_tipo >=5 AND $dataActividad->col_tipo <=7) {
        $totalParticipaciones = getTotalParticipaciones($alumnoid, $materiaData->col_id, $dataActividad->col_id, $db, $_fechaActividad);
        // $totalesActividades = getTotalesActividades($alumnoid, $dataActividad->col_id, $db, $_fechaActividad);
        if($periodoData->isPosgrado == 0){
            $totalesActividades = getTotalesActividades($alumnoid, $dataActividad->col_id, $db, $_fechaActividad);
        }else{
            $totalesActividades = getTotalesActividadesPosgrados($alumnoid, $dataActividad->col_id, $db);
        }

        if($curricular == 'si'){

            if($dataActividad->col_tipo == 5 OR $dataActividad->col_tipo == 6){
                if($ordenExamen == 1) $me = acreditaMEAcademias($alumnoid, $dataActividad->col_id, $db);
                if($ordenExamen == 2) $me = acreditaMETalleres($alumnoid, $dataActividad->col_id, $db);
                if($ordenExamen == 3) $me = acreditaMEClubLectura($alumnoid, $dataActividad->col_id, $db);
                if($ordenExamen == 4) $me = acreditaMETransversales($alumnoid, $dataActividad->col_id, $db);
                if($ordenExamen == 5) $me = acreditaMEPracticas($alumnoid, $dataActividad->col_id, $dataActividad->col_tipo, $db);
                if($ordenExamen == 7) $me = acreditaMEServicio($alumnoid, $dataActividad->col_id, $dataActividad->col_tipo, $db);
            }else if($dataActividad->col_tipo == 7){
                if($ordenExamen == 1) $me = acreditaMEAcademias($alumnoid, $dataActividad->col_id, $db);
                if($ordenExamen == 2) $me = acreditaMEAltruista($alumnoid, $db, $periodoData->col_id);
            }
        }

        $tareas = 0;
        $investigacion = 0;
        $lecturas = 0;
        $debates = 0;
        $participacion = 0;
        if(removePorcentaje($ponderacion['tarea']) > 0) {
            if($totalesActividades['enclase']['calificacion'] > 0) $totalesActividades['enclase']['calificacion'] = $totalesActividades['enclase']['calificacion'] * 10;
            $division = (($totalesActividades['tarea']['calificacion'] + $totalesActividades['enclase']['calificacion']) / ($totalesActividades['tarea']['total'] + $totalesActividades['enclase']['total'])) * 10;
            $tareas = (removePorcentaje($ponderacion['tarea'])*$division)/100;
        }
        if(removePorcentaje($ponderacion['investigacion']) > 0) {
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
        if(removePorcentaje($ponderacion['participacion']) > 0) {
            $division = ($totalParticipaciones['suma'] / $totalParticipaciones['max']) * 10;
            $participacion = (removePorcentaje($ponderacion['participacion'])*$division)/10;
        }


        if(removePorcentaje($ponderacion['proyecto']) > 0) {
            $division = ($totalesActividades['proyecto']['calificacion'] / $totalesActividades['proyecto']['total']) * 10;
            $proyectos = (removePorcentaje($ponderacion['proyecto'])*$division)/100;
        }

        if(removePorcentaje($ponderacion['examen']) > 0) {
            $division = ($totalesActividades['examen_final']['calificacion'] / $totalesActividades['examen_final']['total']) * 10;
            $examen = (removePorcentaje($ponderacion['examen'])*$division)/100;
        }

        if($me['reduccion'] == 50) $final = ($final / 2);
        if($me['reduccion'] == 100) $final = 0;
        if($tarea['col_sd'] == 1) $final = 0;
        if($ignorarParticipaciones == false) {
            // $final = ($final + floatval($tareas) + floatval($investigacion) + floatval($lecturas) + floatval($debates) + floatval($participacion)) / 10;
            if($periodoData->isPosgrado == 1) {
                $final = formatoPromedio(($examen + floatval($proyectos)) / 10);
                // echo '<br/>final / ponderaciones: '.$final;
            }else{
                $final = formatoPromedio(($final + floatval($tareas) + floatval($investigacion) + floatval($lecturas) + floatval($debates) + floatval($proyectos) + floatval($participacion)) / 10);
            }

        }else{
            // $final = $finalIntegro;
            $final = ($final / 10);
            // echo '<br/>final / 10: '.$final;
        }

    }


    if($final < 7) {
        $final = intval($final);
    }else{
        $final = round($final, 0, PHP_ROUND_HALF_ODD);
    }
    $calificacion = floatval($final);
    // echo '<br/>calificacion: '.$calificacion;

    if(in_array($dataActividad->col_tipo, array(7,8,9)) && $periodoData->isPosgrado == 0){
        $calificacion = $finalIntegro;
        // echo '<br/>calificacion integro: '.$calificacion;
    }

    if($dataActividad->col_tipo == 7) {
        if($me['reduccion'] == 50) $calificacion = ($calificacion / 2);
        if($me['reduccion'] == 100) $calificacion = 0;
        if($tarea['col_sd'] == 1) $calificacion = 0;
        $calificacionEF = $calificacion;
    }

    // echo '<br/>calificacionEF: '.$calificacionEF;
    // exit;

    // if($alumnoid == 577){
    //     echo $finalIntegro;
    //     echo '--';
    //     echo $final;
    //     echo '--';
    //     echo $calificacion;exit;
    // }


    $query = 'DELETE FROM tbl_calificaciones WHERE col_alumnoid="'.$alumnoid.'" AND col_materia_clave="'.$materiaData->col_clave.'" AND col_periodoid!="'.$periodoDataAlumno->col_id.'" AND col_groupid="'.$periodoDataAlumno->col_groupid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();

    $query = 'SELECT * FROM tbl_calificaciones WHERE col_alumnoid="'.$alumnoid.'" AND col_materia_clave="'.$materiaData->col_clave.'" AND col_periodoid="'.$periodoDataAlumno->col_id.'" AND col_groupid="'.$periodoDataAlumno->col_groupid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();

    if($sth->rowCount()) {
        $calificacionesData = $sth->fetch(PDO::FETCH_OBJ);
        $query = 'UPDATE tbl_calificaciones SET '.$col.'="'.$calificacion.'", col_updated_at="'.date('Y-m-d h:i:s').'", col_updated_by="'.$maestroID.'" WHERE col_id="'.$calificacionesData->col_id.'"';
    }else{

        $data = array(
            'col_alumnoid' => $alumnoid,
            'col_materia_clave' => $materiaData->col_clave,
            'col_periodoid' => $periodoDataAlumno->col_id,
            'col_groupid' => $periodoDataAlumno->col_groupid,
            'col_observaciones' => '',
            'col_estatus' => $config->col_calificaciones_estatus,
            'col_created_at' => date('Y-m-d h:i:s'),
            'col_created_by' => $maestroID,
            'col_updated_at' => date('Y-m-d h:i:s'),
            'col_updated_by' => $maestroID,
        );
        $data[$col] = $calificacion;

        $query = 'INSERT INTO tbl_calificaciones ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';
    }



    $sth = $db->prepare($query);
    $sth->execute();


    if($dataActividad->col_tipo == 7) {

        if($periodoDataAlumno->isPosgrado == 1) {

            $calificacionEF = floatval($calificacionEF);
            if(intval($calificacionEF) > 10) $calificacionEF = (($calificacionEF) / 10);

            $laCalificacionFinal = $calificacionEF;
            if($laCalificacionFinal <= 7) {
                $laCalificacionFinal = 7;
            }else{
                $laCalificacionFinal = number_format($laCalificacionFinal, 1);
                $spit_laCalificacionFinal = explode('.', $laCalificacionFinal);
                if($spit_laCalificacionFinal[1] > 5) {
                    $laCalificacionFinal = ceil($laCalificacionFinal);
                }else{
                    $laCalificacionFinal = floor($laCalificacionFinal);
                }
            }
        }else{
            $query = 'SELECT * FROM tbl_calificaciones WHERE col_alumnoid="'.$alumnoid.'" AND col_materia_clave="'.$materiaData->col_clave.'" AND col_periodoid="'.$periodoDataAlumno->col_id.'" AND col_groupid="'.$periodoDataAlumno->col_groupid.'" ';
            $sth = $db->prepare($query);
            $sth->execute();
            $calificacionesData = $sth->fetch(PDO::FETCH_OBJ);

            if($calificacionesData->col_p2 != '') {
                if($redondearCalculosCalificacionesFinales) {
                    $calificacionesData->col_p1 = round($calificacionesData->col_p1, 0, PHP_ROUND_HALF_ODD);
                    $calificacionesData->col_p2 = round($calificacionesData->col_p2, 0, PHP_ROUND_HALF_ODD);
                }
                $parciales = floatval(((20 * $calificacionesData->col_p1) / 10) + ((20 * $calificacionesData->col_p2) / 10));
            }else{
                if($redondearCalculosCalificacionesFinales) $calificacionesData->col_p1 = round($calificacionesData->col_p1, 0, PHP_ROUND_HALF_ODD);
                $parciales = floatval(((40 * $calificacionesData->col_p1) / 10));
                //if($alumnoid == 1001) echo "\n".$parciales;
            }

            if($redondearCalculosCalificacionesFinales) $calificacionEF = round($calificacionEF, 0, PHP_ROUND_HALF_ODD);
            $calificacionEF = ((60 * $calificacionEF) / 10);
            //if($alumnoid == 1001) echo "\n".$calificacionEF;
            $laCalificacionFinal = (($parciales + $calificacionEF) / 10);
            if($laCalificacionFinal < 7) {
                $laCalificacionFinal = 5;
            }else{
                $laCalificacionFinal = number_format($laCalificacionFinal, 1);
                $spit_laCalificacionFinal = explode('.', $laCalificacionFinal);
                if($spit_laCalificacionFinal[1] > 5) {
                    $laCalificacionFinal = ceil($laCalificacionFinal);
                }else{
                    $laCalificacionFinal = floor($laCalificacionFinal);
                }
            }
        }



        if($sth->rowCount()) {
            $query = 'UPDATE tbl_calificaciones SET col_cf="'.$laCalificacionFinal.'", col_updated_at="'.date('Y-m-d h:i:s').'", col_updated_by="'.$maestroID.'" WHERE col_id="'.$calificacionesData->col_id.'"';
            //if($alumnoid == 1001) echo "\n".$query;
        }else{

            $data = array(
                'col_cf' => $laCalificacionFinal,
                'col_alumnoid' => $alumnoid,
                'col_materia_clave' => $materiaData->col_clave,
                'col_periodoid' => $periodoDataAlumno->col_id,
                'col_groupid' => $periodoDataAlumno->col_groupid,
                'col_observaciones' => '',
                'col_estatus' => $config->col_calificaciones_estatus,
                'col_created_at' => date('Y-m-d h:i:s'),
                'col_created_by' => $maestroID,
                'col_updated_at' => date('Y-m-d h:i:s'),
                'col_updated_by' => $maestroID,
            );

            $query = 'INSERT INTO tbl_calificaciones ('.implode(",", array_keys($data)).')
            VALUES("'.implode('", "', array_values($data)).'")';
        }
        $sth = $db->prepare($query);
        $sth->execute();
    }

    return;
}

function fechaTextoBoleta($fecha = '', $formato = 'j F Y'){
    if($fecha == '0000-00-00' || $fecha == '0000-00-00 00:00:00' || $fecha == '') return 'Sin Definir';
    $en = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $es = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    return strtoupper(str_replace($en, $es, date($formato, strtotime($fecha))));
}

function addSeguimiento($alumnoid, $razones, $tipo, $db) {

    $query = 'SELECT * FROM tbl_alumnos WHERE col_estatus="activo" AND col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
    if($alumnoData->rowCount() == 0) return;

    $query = 'SELECT * FROM tbl_users WHERE col_depto="16" AND col_status=1 LIMIT 1';
    $sth = $db->prepare($query);
    $sth->execute();
    $user = $sth->fetch(PDO::FETCH_OBJ);

    $data = array(
        "col_alumnoid" => intval($alumnoid),
        "col_razones" => addslashes($razones),
        "col_tipo" => trim($tipo),
        "col_estatus" => 0,
        "col_asignado" => intval($user->col_id),
        "col_created_at" => date("Y-m-d H:i:s"),
        "col_created_by" => 99999,
        "col_updated_at" => date("Y-m-d H:i:s"),
        "col_updated_by" => 99999,
    );

    $query = 'INSERT INTO tbl_seguimiento ('.implode(",", array_keys($data)).')
    VALUES("'.implode('", "', array_values($data)).'")';
    $sth = $db->prepare($query);
    $sth->execute();

    return;
}

// Termina me.php
