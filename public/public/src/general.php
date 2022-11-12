<?php

/**
 *
 * Archivo que incluye la función que permite generar las opciones del menu y
 * permisos de acuerdo a cada usuario y sus características
 *
 */

function getMenu($db) {
    $userType = getCurrentUserType();
    $userID = getCurrentUserID();

    if($userType == 'alumno') {
        $periodoid = getCurrentAlumnoPeriodoID($db);
        $periodoData = getPeriodo($periodoid, $db, false);
        $carreraid = getCurrentAlumnoCarreraID();
        $carreraData = getCarrera($carreraid, $db);
        $modalidad = $carreraData['modalidad'];

        $evaMaestros = 'closed';
        $evaAlumnos = 'closed';
        $isRepresentante = false;
        if(esRepresentante() == 'true') {
            $isRepresentante = true;
        }

        $query = 'SELECT * FROM tbl_eva_maestros WHERE col_estatus="1" AND col_group_periodoid="'.$periodoData->col_groupid.'"';
        $sth = $db->prepare($query);
        $sth->execute();
        if($sth->rowCount()) {
            $evaMaestros = 'open';
        }


        $query = 'SELECT * FROM tbl_eva_alumnos WHERE col_estatus="1" AND col_para="'.($modalidad + 1).'" AND col_group_periodoid="'.$periodoData->col_groupid.'" ORDER BY col_id LIMIT 1';
        $sth = $db->prepare($query);
        $sth->execute();

        if($sth->rowCount()) {
            $dataEvaAlumnos = $sth->fetch(PDO::FETCH_OBJ);
            $evaAlumnos = 'open';

            $query = 'SELECT * FROM tbl_eva_alumnos_respuestas WHERE col_evaid="'.$dataEvaAlumnos->col_id.'" AND col_alumnoid="'.$userID.'"';
            $sth = $db->prepare($query);
            $sth->execute();
            if($sth->rowCount()) {
                $evaAlumnos = 'closed';
            }


        }
    }

    if($userType == 'maestro') {
        $hasAcademias = false;
        $hasTalleres = false;
        $hasClubLectura = false;
        $evaMaestrosDisponible = 'no';


        $c = $db->prepare('SELECT * FROM tbl_config WHERE col_id="1"');
        $c->execute();
        $r = $c->fetch(PDO::FETCH_OBJ);
        $grupos_periodos = array($r->col_periodo, $r->col_periodo_cuatri, $r->col_periodo_maestria, $r->col_periodo_doctorado);

        $subquery = 'SELECT col_id FROM tbl_eva_maestros WHERE col_estatus="2" AND col_group_periodoid IN ('.implode(',', array_unique($grupos_periodos)).') ORDER BY col_id DESC';
        $query = 'SELECT * FROM tbl_eva_maestros_observaciones WHERE col_evaid IN ('.$subquery.') AND col_maestroid="'.$userID.'" AND col_estatus=1 ORDER BY col_evaid DESC LIMIT 1';
        $fth = $db->prepare($query);
        $fth->execute();
        if($fth->rowCount()){
            $dataEvaMaestro = $fth->fetch(PDO::FETCH_OBJ);
            $evaMaestrosDisponible = 'si';
            // $data->evaMaestrosID = $dataEvaMaestro->col_evaid;
        }

        $hasAcademias = hasAcademias($userID, $db);
        $hasTalleres = hasTalleres($userID, $db);
        $hasClubLectura = hasClub($userID, $db);
    }


    switch($userType) {
        case 'maestro':
        $query = 'SELECT * FROM tbl_menu WHERE  col_maestros=1 AND col_visible=1 AND col_parent=0';
        break;

        case 'alumno':
        $query = 'SELECT * FROM tbl_menu WHERE  col_alumnos=1 AND col_visible=1 AND col_parent=0';
        break;

        default:
        if(isAdmin()){
            $query = 'SELECT * FROM tbl_menu WHERE col_admins=1 AND col_visible=1 AND col_parent=0';
        }else{
            // $depto = getDepto($userID, $db, false);
            $sth = $db->prepare('SELECT * FROM tbl_users WHERE col_id="'.$userID.'"');
            $sth->execute();
            $userData = $sth->fetch(PDO::FETCH_OBJ);
            $depto = $userData->col_depto;
            $query = 'SELECT * FROM tbl_menu WHERE col_admins=1 AND col_visible=1 AND col_parent=0';
        }
        break;
    }


    $sth = $db->prepare($query);
    $sth->execute();
    $menus = $sth->fetchAll();

    $i = 0;
    foreach($menus as $item) {
        if($userType == 'alumno') {
            if($item['col_semestres'] != '' && !in_array($periodoData->col_grado, explode(',', $item['col_semestres']))) continue;
            if($item['col_modalidad'] != '' && strtolower($item['col_modalidad']) != strtolower($modalidad)) continue;

            if(strpos(strtolower($item['col_link']), 'evaalumnos') !== false && $evaAlumnos == 'closed') continue;
            if(strpos(strtolower($item['col_link']), 'evamaestros') !== false && $evaMaestros == 'closed') continue;

            if($item['col_representante'] == 0 && $isRepresentante == true) continue;
        }

        if($userType == 'maestro') {
            if($item['col_link'] == '/pages/maestros/evaluacion' && $evaMaestrosDisponible == 'no') continue;
            if($item['col_link'] == '/pages/materias/academias' && $hasAcademias === false) continue;
        }

        if($userType == 'administrativo') {
            if(intval($depto) > 0) {
                if($item['col_deptos'] != ''){
                    if(!in_array($depto, explode(',', $item['col_deptos'])) || in_array($depto, explode(',', $item['col_not_deptos']))) continue;
                }
            }
        }

        $menu[$i]['title'] = fixEncode($item['col_titulo']);
        if($item['col_icon'] != '') $menu[$i]['icon'] = $item['col_icon'];
        if($item['col_link'] != '') $menu[$i]['link'] = $item['col_link'];
        if($item['col_home'] == 1) $menu[$i]['home'] = $item['col_home'];

        $s = 0;
        switch($userType) {
            case 'maestro':
            $parent_query = 'SELECT * FROM tbl_menu WHERE  col_maestros=1 AND col_visible=1 AND col_parent='.$item['col_id'];
            break;

            case 'alumno':
            $parent_query = 'SELECT * FROM tbl_menu WHERE  col_alumnos=1 AND col_visible=1 AND col_parent='.$item['col_id'];
            break;

            default:
            if(isAdmin()){
                $parent_query = 'SELECT * FROM tbl_menu WHERE col_admins=1 AND col_visible=1 AND col_parent='.$item['col_id'];
            }else{
                $parent_query = 'SELECT * FROM tbl_menu WHERE col_admins=1 AND col_visible=1 AND col_parent='.$item['col_id'];
            }
            break;
        }
        $sth = $db->prepare($parent_query);
        $sth->execute();
        $submenus = $sth->fetchAll();
        foreach($submenus as $sub_item) {
            if($userType == 'alumno') {
                if($item['col_semestres'] != '' && !in_array($periodoData->col_grado, explode(',', $item['col_semestres']))) continue;
                if($item['col_modalidad'] != '' && strtolower($item['col_modalidad']) != strtolower($modalidad)) continue;
            }

            if($userType == 'administrativo') {
                if(intval($depto) > 0) {
                    if($sub_item['col_deptos'] != ''){
                        if(!in_array($depto, explode(',', $sub_item['col_deptos'])) || in_array($depto, explode(',', $sub_item['col_not_deptos']))) continue;
                    }
                }
            }

            $menu[$i]['children'][$s]['title'] = fixEncode($sub_item['col_titulo']);
            $menu[$i]['children'][$s]['link'] = $sub_item['col_link'];
            $s++;
        }
        // if()
        $i++;
    }
    // return '{"title": "Panel de Control","icon": "fas fa-home","link": "/pages/dashboard","home": "true"}';
    return json_encode($menu);
}

function getMargins($db, $esMembretada = false, $isCerts = false) {
    $query = 'SELECT * FROM tbl_config WHERE col_id="1" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $configData = $sth->fetch(PDO::FETCH_OBJ);

    $regular['top'] = $configData->col_mtop;
    $regular['bottom'] = $configData->col_mbottom;
    $regular['left'] = $configData->col_mleft;
    $regular['right'] = $configData->col_mright;

    $membretadas['top'] = $configData->col_mtop_alt;
    $membretadas['bottom'] = $configData->col_mbottom_alt;
    $membretadas['left'] = $configData->col_mleft_alt;
    $membretadas['right'] = $configData->col_mright_alt;

    $certs['top'] = $configData->col_mtop_cert;
    $certs['bottom'] = $configData->col_mbottom_cert;
    $certs['left'] = $configData->col_mleft_cert;
    $certs['right'] = $configData->col_mright_cert;

    if($isCerts === true) return $certs;
    if($esMembretada === false) return $regular;


    return $membretadas;
}

function getFechaExt($db, $creacion, $autorID, $cal, $alumnoid, $claveMateria, $periodoID){
    if($cal == '') return '';
    if(strtotime(date($creacion)) < strtotime(date('2019-01-18'))) return '';

    $periodoData = getPeriodo($periodoID, $db, false);
    $alumnoData = getAlumnoData($alumnoid, $db);

    $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.$claveMateria.'" AND col_carrera="'.$alumnoData->col_carrera.'" AND col_semestre="'.$periodoData->col_grado.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $materiaData = $sth->fetch(PDO::FETCH_OBJ);
    if($sth->rowCount() == 0) return '';

    $query = 'SELECT a.* FROM tbl_actividades a LEFT JOIN tbl_actividades_tareas t ON t.col_actividadid=a.col_id WHERE t.col_calificacion="'.$cal.'" AND a.col_visible_excepto LIKE "%'.$periodoID.'%" AND a.col_tipo=8 AND a.col_materiaid="'.$materiaData->col_id.'" AND a.col_created_by="'.$autorID.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $actividadData = $sth->fetch(PDO::FETCH_OBJ);
    if($sth->rowCount() == 0) return '';

    return fechaTexto($actividadData->col_fecha_inicio);
}
function getFechaTS($db, $creacion, $autorID, $cal, $alumnoid, $claveMateria, $periodoID){
    if($cal == '') return '';
    if(strtotime(date($creacion)) < strtotime(date('2019-01-18'))) return '';

    $periodoData = getPeriodo($periodoID, $db, false);
    $alumnoData = getAlumnoData($alumnoid, $db);

    $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.$claveMateria.'" AND col_carrera="'.$alumnoData->col_carrera.'" AND col_semestre="'.$periodoData->col_grado.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $materiaData = $sth->fetch(PDO::FETCH_OBJ);
    if($sth->rowCount() == 0) return '';

    $query = 'SELECT a.* FROM tbl_actividades a LEFT JOIN tbl_actividades_tareas t ON t.col_actividadid=a.col_id WHERE t.col_calificacion="'.$cal.'" AND a.col_visible_excepto LIKE "%'.$periodoID.'%" AND a.col_tipo=9 AND a.col_materiaid="'.$materiaData->col_id.'" AND a.col_created_by="'.$autorID.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $actividadData = $sth->fetch(PDO::FETCH_OBJ);
    if($sth->rowCount() == 0) return '';

    return fechaTexto($actividadData->col_fecha_inicio);
}

?>