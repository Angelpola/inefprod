<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de materias.
 *
 * Lista de funciones
 *
 * /materias
 * - /planeacion
 * - /listByMaestroSelect
 * - /listByMaestro
 * - /listByMaestroTable
 * - /listPeriodos
 * - /aprobarPlaneacion
 * - /getMaestros
 * - /listPlaneaciones
 * - /list
 * - /get
 * - /update
 * - /asignarMaestro
 * - /add
 * - /delete
 * - /listMaterias
 * - /getAlumnosAcademias
 * - /getAlumnoAcademia
 * - /guardarAcademiasDetalles
 * - /getListAcademias
 * - /getListClubs
 * - /getAlumnosClubs
 * - /getAlumnoClubs
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/materias', function () {

    $this->post('/planeacion', function (Request $request, Response $response, $args) {
        global $uploaddir, $download_url, $dblog;

        $maestroID = getCurrentUserID();

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());
        // $input->materiaid = TAX
        $query = 'SELECT t.col_materia_clave, p.col_grado, p.col_carreraid, p.col_plan_estudios FROM tbl_maestros_taxonomia t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_id="'.intval($input->materiaid).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $taxData = $sth->fetch(PDO::FETCH_OBJ);
        $materia_clave = $taxData->col_materia_clave;
        $plan_estudios = $taxData->col_plan_estudios;
        $semestre = $taxData->col_grado;
        $carreraid = $taxData->col_carreraid;


        $query = 'SELECT * FROM tbl_materias WHERE col_carrera="'.$carreraid.'" AND col_semestre="'.$semestre.'" AND col_clave="'.$materia_clave.'" AND col_plan_estudios="'.$plan_estudios.'"';
        $sthCurrent = $this->db->prepare($query);
        $sthCurrent->execute();
        $data = $sthCurrent->fetch(PDO::FETCH_OBJ);
        $_materiaid = $data->col_id;

        $query = 'SELECT * FROM tbl_materias_maestros_planeacion WHERE col_materiaid="'.$_materiaid.'" AND col_periodoid="'.$input->periodoid.'" AND col_maestroid="'.$maestroID.'"';
        $sthCurrent = $this->db->prepare($query);
        $sthCurrent->execute();
        if($sthCurrent->rowCount()){
            $current = $sthCurrent->fetch(PDO::FETCH_OBJ);
            if($current->col_archivo != '' && file_exists($uploaddir.'planeacion/'.$current->col_archivo)) @unlink($uploaddir.'planeacion/'.$current->col_archivo);
            $queryRemove = 'DELETE FROM tbl_materias_maestros_planeacion WHERE col_id="'.$current->col_id.'"';

            $dblog = new DBLog($queryRemove, 'tbl_materias_maestros_planeacion', '', '', 'Materias', $this->db);
            $dblog->where = array('col_id' => intval($current->col_id));
            $dblog->prepareLog();

            $sthRemove = $this->db->prepare($queryRemove);
            $sthRemove->execute();

            $dblog->saveLog();

        }

        if($input->archivoPlaneacion->filename != ''){

            $data = array(
                'col_maestroid' => $maestroID,
                'col_materiaid' => $_materiaid,
                'col_periodoid' => $input->periodoid,
                'col_archivo' => '',
                'col_created_at' => date("Y-m-d H:i:s"),
                'col_created_by' => $input->userid,
                'col_updated_at' => date("Y-m-d H:i:s"),
                'col_updated_by' => $input->userid,
            );

            $query = 'INSERT INTO tbl_materias_maestros_planeacion ('.implode(",", array_keys($data)).') VALUES("'.implode('", "', array_values($data)).'")';
            $sth = $this->db->prepare($query);
            $result = $sth->execute();

            if($result){
                $_response['status'] = 'true';
                // $_response['debug'] = $query;
                if($input->archivoPlaneacion->filename){
                    $array_ext = explode('.', $input->archivoPlaneacion->filename);
                    $extension = end($array_ext);
                    $filename = 'planeacion-'.strtotime('now').'.'.$extension;
                    $query = 'UPDATE tbl_materias_maestros_planeacion SET col_archivo="'.$filename.'" WHERE col_id="'.$this->db->lastInsertId().'"';
                    $archivo = $this->db->prepare($query);
                    $archivo->execute();
                    if(!file_exists($uploaddir.'planeacion')) @mkdir($uploaddir.'planeacion', 0777);
                    list($type, $dataFile) = explode(';', $input->archivoPlaneacion->value);
                    list(, $dataFile)      = explode(',', $dataFile);
                    $_response['uploaded'] = file_put_contents($uploaddir.'planeacion/'.$filename, base64_decode($dataFile));
                }
            }else{
                $_response['status'] = 'No se puedo guardar el registro.';
            }
        }else{
            $_response['status'] = 'Debes seleccionar al menos un archivo.';
        }

        return $this->response->withJson($_response);

    });

    $this->get('/listByMaestroSelect', function (Request $request, Response $response, array $args) {
        $maestroID = intval($_REQUEST[maestro]);
        $periodos = getCurrentPeriodos($this->db);
        // $query = "SELECT m.col_id AS materiaid, t.col_id AS ID, t.col_periodoid AS periodoid, p.col_groupid AS periodo_groupid, m.col_nombre AS nombre, p.col_grado AS grado, p.col_grupo AS grupo ".
        // "FROM tbl_maestros_taxonomia t ".
        // "LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid ".
        // "LEFT OUTER JOIN tbl_materias m ON m.col_clave=t.col_materia_clave AND m.col_plan_estudios=p.col_plan_estudios AND m.col_semestre=p.col_grado ".
        // "WHERE t.col_maestroid='".intval(intval($_REQUEST[maestro]))."' GROUP BY col_materia_clave ORDER BY t.col_id";

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
            $queryMateria = 'SELECT * FROM tbl_materias WHERE col_semestre="'.$item['grado'].'" AND col_clave="'.trim($item['col_materia_clave']).'" AND col_plan_estudios="'.$item['col_plan_estudios'].'"';
            $sthm = $this->db->prepare($queryMateria);
            $sthm->execute();
            $materiaData = $sthm->fetch(PDO::FETCH_OBJ);

            $grado = $item['grado'];
            $grupo = $item['grupo'];

            if(in_array($item['periodoid'], $periodos) && $materiaData->col_nombre) {
                if(strpos(strtoupper($item[col_materia_clave]), 'AC') !== false || strpos(strtoupper($item[col_materia_clave]), 'TL') !== false){
                    $grado = 'Multigrupo';
                    $grupo = '';

                    if(strlen($item['col_materia_clave']) > 4){
                        $laClave = claveMateria($item['col_materia_clave']);
                    }else{
                        $laClave = $item['col_materia_clave'];
                    }
                    if(is_array($mata) && in_array($laClave, $mata)) continue;

                    if(strlen($item['col_materia_clave']) > 4){
                        $mata[] = claveMateria($item['col_materia_clave']);
                    }else{
                        $mata[] = $item['col_materia_clave'];
                    }
                    $mata = array_unique($mata);
                }

                // $periodoData = getPeriodo($item['periodoid'], $this->db, false);
                $carreraData = getCarrera($item['col_carreraid'], $this->db);

                // $result[$i]['col_id'] = $item['ID'];
                // $result[$i]['materiaid'] = $materiaData->col_id;
                // $result[$i]['nombre'] = fixEncode($materiaData->col_nombre);
                // $result[$i]['grado'] = $grado;
                // $result[$i]['grupo'] = $grupo;
                // $result[$i]['periodo'] = $item['periodoid'];
                // $result[$i]['groupid'] = $item['periodo_groupid'];

                    $result[$i]['value'] = $item['ID'];
                    $result[$i]['label'] = fixEncode($materiaData->col_nombre).' '.$grado.'-'.$grupo.' ('.fixEncode($carreraData['modalidad']).')';
                    $result[$i]['text'] = fixEncode($materiaData->col_nombre).' '.$grado.'-'.$grupo.' ('.fixEncode($carreraData['modalidad']).')';



                $i++;
            }
        }

        return $this->response->withJson($result);

    });

    $this->get('/listByMaestro', function (Request $request, Response $response, array $args) {
        global $apiURL;

        $maestroID = intval($_REQUEST[maestro]);
        $periodos = getCurrentPeriodos($this->db);
        // $query = "SELECT m.col_id AS materiaid, t.col_id AS ID, t.col_periodoid AS periodoid, p.col_groupid AS periodo_groupid, m.col_nombre AS nombre, p.col_grado AS grado, p.col_grupo AS grupo ".
        // "FROM tbl_maestros_taxonomia t ".
        // "LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid ".
        // "LEFT OUTER JOIN tbl_materias m ON m.col_clave=t.col_materia_clave AND m.col_plan_estudios=p.col_plan_estudios AND m.col_semestre=p.col_grado ".
        // "WHERE t.col_maestroid='".intval(intval($_REQUEST[maestro]))."' GROUP BY col_materia_clave ORDER BY t.col_id";

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
            $queryMateria = 'SELECT * FROM tbl_materias WHERE col_semestre="'.$item['grado'].'" AND col_clave="'.trim($item['col_materia_clave']).'" AND col_plan_estudios="'.$item['col_plan_estudios'].'"';
            $sthm = $this->db->prepare($queryMateria);
            $sthm->execute();
            $materiaData = $sthm->fetch(PDO::FETCH_OBJ);

            $grado = $item['grado'];
            $grupo = $item['grupo'];

            if(in_array($item['periodoid'], $periodos) && $materiaData->col_nombre) {
                if(strpos(strtoupper($item[col_materia_clave]), 'AC') !== false || strpos(strtoupper($item[col_materia_clave]), 'TL') !== false){
                    $grado = 'Multigrupo';
                    $grupo = '';

                    if(strlen($item['col_materia_clave']) > 4){
                        $laClave = claveMateria($item['col_materia_clave']);
                    }else{
                        $laClave = $item['col_materia_clave'];
                    }
                    if(is_array($mata) && in_array($laClave, $mata)) continue;

                    if(strlen($item['col_materia_clave']) > 4){
                        $mata[] = claveMateria($item['col_materia_clave']);
                    }else{
                        $mata[] = $item['col_materia_clave'];
                    }
                    $mata = array_unique($mata);
                }

                $carreraData = getCarrera($item['col_carreraid'], $this->db);

                $result[$i]['col_id'] = $item['ID'];
                $result[$i]['materiaid'] = $materiaData->col_id;
                $result[$i]['nombre'] = fixEncode($materiaData->col_nombre);
                $result[$i]['modalidad'] = fixEncode($carreraData['modalidad']);
                $result[$i]['grado'] = $grado;
                $result[$i]['grupo'] = $grupo;
                $result[$i]['periodo'] = $item['periodoid'];
                $result[$i]['groupid'] = $item['periodo_groupid'];

                $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($item['periodoid']).'"';
                $sth = $this->db->prepare($query);
                $sth->execute();
                $periodoData = $sth->fetch(PDO::FETCH_OBJ);
                $isPosgrado = 0;
                if($periodoData->col_modalidad == 3 || $periodoData->col_modalidad == 4){
                    $isPosgrado = 1;
                }
                $result[$i]['posgrado'] = $isPosgrado;

                $i++;
            }
        }

        return $this->response->withJson($result);

    });

    $this->get('/listByMaestroTable', function (Request $request, Response $response, array $args) {
        global $apiURL;
        $maestroID = getCurrentUserID();

        $periodos = getCurrentPeriodos($this->db);
        // print_r($periodos);

        // $query = "SELECT m.col_carrera AS carrera, m.col_id AS materiaid, m.col_clave AS clave, m.col_plan AS plan, t.col_id AS ID, t.col_periodoid AS periodoid, p.col_aprobado AS horario_aprobado, p.col_groupid AS periodo_groupid, m.col_nombre AS nombre, p.col_grado AS grado, p.col_grupo AS grupo ".
        // "FROM tbl_maestros_taxonomia t ".
        // "LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid ".
        // "LEFT OUTER JOIN tbl_materias m ON m.col_clave=t.col_materia_clave AND m.col_plan_estudios=p.col_plan_estudios AND m.col_semestre=p.col_grado ".
        // "WHERE m.col_id AND t.col_maestroid='".intval(intval($maestroID))."' ORDER BY t.col_id";

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

        $dias[0] = 'LU';
        $dias[1] = 'MA';
        $dias[2] = 'MI';
        $dias[3] = 'JU';
        $dias[4] = 'VI';
        $dias[5] = 'SA';
        $dias[6] = 'DO';

        $modalidad = array(0=>'Semestral', 1=>'Cuatrimestral');
        foreach($todos as $item){
            $queryMateria = 'SELECT * FROM tbl_materias WHERE col_carrera="'.$item['col_carreraid'].'" AND col_semestre="'.$item['grado'].'" AND col_clave="'.trim($item['col_materia_clave']).'" AND col_plan_estudios="'.$item['col_plan_estudios'].'"';
            $sthm = $this->db->prepare($queryMateria);
            $sthm->execute();
            $materiaData = $sthm->fetch(PDO::FETCH_OBJ);

            $grupos = $item['grado']."-".$item['grupo'];


            if(in_array($item['periodoid'], $periodos) && $materiaData->col_nombre) {
                if(strpos(strtoupper($item[col_materia_clave]), 'AC') !== false || strpos(strtoupper($item[col_materia_clave]), 'TL') !== false){
                    $grupos = 'Multigrupo';
                    if(strlen($item['col_materia_clave']) > 4){
                        $laClave = claveMateria($item['col_materia_clave']);
                    }else{
                        $laClave = $item['col_materia_clave'];
                    }
                    if(is_array($mata) && in_array($laClave, $mata)) continue;

                    if(strlen($item['col_materia_clave']) > 4){
                        $mata[] = claveMateria($item['col_materia_clave']);
                    }else{
                        $mata[] = $item['col_materia_clave'];
                    }
                    $mata = array_unique($mata);
                }

                $horario = '-';
                if($item['horario_aprobado'] == 1) {

                    $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($item['periodoid']).'"';
                    $sth = $this->db->prepare($query);
                    $sth->execute();
                    $periodoData = $sth->fetch(PDO::FETCH_OBJ);
                    $isPosgrado = 0;
                    if($periodoData->col_modalidad == 3 || $periodoData->col_modalidad == 4){
                        $isPosgrado = 1;
                    }

                    if($isPosgrado == 0){
                        $queryHorario = 'SELECT * FROM tbl_horarios WHERE col_periodoid="'.intval($item['periodoid']).'" AND col_materiaid="'.intval($materiaData->col_id).'"';
                        $sthHorario = $this->db->prepare($queryHorario);
                        $sthHorario->execute();
                        if($sthHorario->rowCount()){
                            $dataHorario = $sthHorario->fetch(PDO::FETCH_OBJ);
                            $color = "secondary";
                            $horario = '';
                            if($dataHorario->col_lunes != '') $horario .= "<span class='badge badge-".$color."'>Lunes: ".$dataHorario->col_lunes."</span>";
                            if($dataHorario->col_martes != '') $horario .= "<span class='badge badge-".$color."'>Martes: ".$dataHorario->col_martes."</span>";
                            if($dataHorario->col_miercoles != '') $horario .= "<span class='badge badge-".$color."'>Miercoles: ".$dataHorario->col_miercoles."</span>";
                            if($dataHorario->col_jueves != '') $horario .= "<span class='badge badge-".$color."'>Jueves: ".$dataHorario->col_jueves."</span>";
                            if($dataHorario->col_viernes != '') $horario .= "<span class='badge badge-".$color."'>Viernes: ".$dataHorario->col_viernes."</span>";
                            if($dataHorario->col_sabado != '') $horario .= "<span class='badge badge-".$color."'>Sabado: ".$dataHorario->col_sabado."</span>";
                            if($dataHorario->col_domingo != '') $horario .= "<span class='badge badge-".$color."'>Domingo: ".$dataHorario->col_domingo."</span>";
                        }
                    }else{
                        $queryHorario = 'SELECT * FROM tbl_horarios_posgrados WHERE col_periodoid="'.intval($item['periodoid']).'" AND col_materiaid="'.intval($materiaData->col_id).'" ORDER BY col_dia DESC, col_id DESC';
                        $sthHorario = $this->db->prepare($queryHorario);
                        $sthHorario->execute();
                        $todosHorarios = $sthHorario->fetchAll();
                        $horario = '';
                        foreach($todosHorarios as $itemHorario){
                            $horario .= "<span class='badge badge-".$color."'>".$dias[$itemHorario['col_dia']].": ".$itemHorario['col_hora_inicio']."-".$itemHorario['col_hora_fin']."</span>";
                        }
                    }
                }

                $carrera = getCarrera($item['col_carreraid'], $this->db);
                $result[$i]['col_id'] = $item['ID'];
                $result[$i]['nombre'] = fixEncode($materiaData->col_nombre);
                $result[$i]['carrera'] = $carrera['nombre'];
                $result[$i]['clave'] = fixEncode($item['col_materia_clave']);
                $result[$i]['modalidad'] = $carrera['modalidad'];
                $result[$i]['horario'] = $horario;
                $result[$i]['grupo'] = $grupos;
                $result[$i]['opciones'] = '<a class="opcion-table" title="Subir Planeación" href="#/pages/materias/planeacion/'.$item['ID'].'"><i class="fas fa-file"></i></a>';
                if($isPosgrado == 1){
                    $reporteURL = $apiURL.'/periodos/reporteAsistenciasPosgrado?maestroid='.$maestroID.'&materiaid='.$item['ID'].'&periodoid='.intval($item['periodoid']).'&tipo=4';
                    $result[$i]['opciones'] .= '&nbsp;&nbsp;<a class="opcion-table" title="Descargar Reporte" target="_blank" href="'.$reporteURL.'"><i class="fas fa-file-alt"></i></a>';
                }

                // if($carrera['posgrado']) {
                //     $result[$i]['opciones'] .= '&nbsp;&nbsp;<a class="btn btn-primary btn-xs" title="Descargar Reporte" href="#/pages/materias/planeacion/'.$item['ID'].'"><i class="fas fa-file-alt"></i> Reporte</a>';
                // }
                // $result[$i]['opciones'] .= '<a class="opcion-table" title="Horario Planeación" href="#/pages/materias/horario/'.$item['ID'].'"><i class="fas fa-clock"></i></a>';
                $i++;


            }
        }

        return $this->response->withJson($result);

    });

    $this->get('/listPeriodos', function (Request $request, Response $response, array $args) {

        // $periodosActivos = getCurrentPeriodos($this->db);
        $grado = intval($_REQUEST['semestre']);
        $materiaid = intval($_REQUEST['materiaid']);
        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $config = $c->fetch(PDO::FETCH_OBJ);
        $activos = array(intval($config->col_periodo), intval($config->col_periodo_cuatri), intval($config->col_periodo_maestria), intval($config->col_periodo_doctorado));
        $menor = min(array_values($activos));

        $materiaQuery = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaid.'"';
        $materiaObj = $this->db->prepare($materiaQuery);
        $materiaObj->execute();
        $materiaData = $materiaObj->fetch(PDO::FETCH_OBJ);

        //$query = "SELECT * FROM tbl_periodos WHERE col_id IN (".implode(',', $periodosActivos).") ORDER BY col_nombre ASC";
        $query = "SELECT * FROM tbl_periodos WHERE col_groupid > '".$menor."' GROUP BY col_groupid, col_grado, col_grupo ORDER BY col_nombre ASC";
        if($grado > 0){
            // $query = "SELECT * FROM tbl_periodos WHERE col_grado='".$grado."' AND  col_groupid > '".$menor."' GROUP BY col_groupid, col_grado, col_grupo ORDER BY col_nombre ASC";
            $query = "SELECT * FROM tbl_periodos WHERE col_grado='".$materiaData->col_semestre."' AND col_plan_estudios='".$materiaData->col_plan_estudios."' AND col_carreraid='".$materiaData->col_carrera."' AND  col_groupid >= '".$menor."' GROUP BY col_groupid, col_grado, col_grupo ORDER BY col_nombre ASC";

        }
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $modalidades = array(1=>'Semestral', 2=>'Cuatrimestral', 3=>'Maestría', 4=>'Doctorado');


        $i = 0;
        $modalidades = array(1=>'Semestral', 2=>'Cuatrimestral', 3=>'Maestría', 4=>'Doctorado');
        foreach($todos as $item){
            $carreraData = getCarrera($item['col_carreraid'], $this->db);

            if($item['col_modalidad'] == 0) $item['col_modalidad'] = $carreraData['modalidad_numero'];

            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = fixEncode($item['col_nombre'])." (".$item['col_grado']."-".$item['col_grupo'].", ".$modalidades[$item['col_modalidad']]." - ".$carreraData['nombre'].")";
            $result[$i]['text'] = fixEncode($item['col_nombre'])." (".$item['col_grado']."-".$item['col_grupo'].", ".$modalidades[$item['col_modalidad']]." - ".$carreraData['nombre'].")";
            $result[$i]['nombre'] = fixEncode($item['col_nombre']);
            $result[$i]['grupo'] = $item['col_grado']."-".$item['col_grupo'];
            $result[$i]['aprobado'] = ($item['col_aprobado'] == 1?'true':'false');
            $result[$i]['modalidad'] = $modalidades[$item['col_modalidad']];
            $result[$i]['carrera'] = $carreraData['nombre'];
            // $result[$i]['label'] = fixEncode($carredaData['modalidad'], true)." ".$item['col_grado']."-".$item['col_grupo'];
            // $result[$i]['text'] = fixEncode($carredaData['modalidad'], true)." ".$item['col_grado']."-".$item['col_grupo'];
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->post('/aprobarPlaneacion', function (Request $request, Response $response, array $args) {
        global $dblog;

        $input = $request->getParsedBody();
        $planeacion = $input['params']['id'];

        $query = 'UPDATE tbl_materias_maestros_planeacion SET col_estatus=1 WHERE col_id="'.$planeacion.'"';

        $dblog = new DBLog($query, 'tbl_materias_maestros_planeacion', '', '', 'Materias Planeación', $this->db);
        $dblog->where = array('col_id' => intval($planeacion));
        $dblog->prepareLog();

        $sthm = $this->db->prepare($query);
        $sthm->execute();

        $dblog->saveLog();

        $result['status'] = 'true';
        return $this->response->withJson($result);

    });

    $this->post('/getMaestros', function (Request $request, Response $response, array $args) {
        global $download_url;

        $periodosActuales = getCurrentPeriodos($this->db);

        $input = $request->getParsedBody();
        $query = "SELECT p.col_modalidad AS modalidadPeriodo, p.col_carreraid AS carreraid, p.col_aprobado AS periodoAprobado, p.col_id AS periodoid, u.col_id AS maestroid, CONCAT(u.col_firstname, ' ', u.col_lastname) AS maestro, p.col_nombre AS periodo, p.col_grado, col_grupo FROM tbl_maestros_taxonomia t LEFT OUTER JOIN tbl_users u ON u.col_id=t.col_maestroid LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_materia_clave='".trim($input['params']['clave'])."' ORDER BY t.col_id DESC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        $materiaid = $input['params']['id'];
        $modalidades = array(1=>'Semestral', 2=>'Cuatrimestral', 3=>'Maestría', 4=>'Doctorado');
        foreach($todos as $item){
            $query = 'SELECT * FROM tbl_materias_maestros_planeacion WHERE col_maestroid="'.$item['maestroid'].'" AND col_periodoid="'.$item['periodoid'].'" AND col_materiaid="'.$materiaid.'"';
            $sthm = $this->db->prepare($query);
            $sthm->execute();
            $pl = $sthm->fetch(PDO::FETCH_OBJ);


            $carreraData = getCarrera($item['carreraid'], $this->db);
            if($item['modalidadPeriodo'] == 0) $item['modalidadPeriodo'] = $carreraData['modalidad_numero'];

            $result[$i]['nombre_maestro'] = fixEncode($item['maestro'], true);
            $result[$i]['periodo'] = fixEncode($item['periodo']);
            if($item['periodoAprobado'] == 1 && in_array($item['periodoid'], $periodosActuales)) $result[$i]['periodo'] .= ' <span class="badge badge-success">Activo y aprobado </span>';
            if($item['periodoAprobado'] == 0 && in_array($item['periodoid'], $periodosActuales)) $result[$i]['periodo'] .= ' <span class="badge badge-warning">Activo, sin aprobar</span>';
            if(!in_array($item['periodoid'], $periodosActuales)) $result[$i]['periodo'] .= ' <span class="badge badge-secondary">Inactivo</span>';


            $result[$i]['periodoid'] = intval($item['periodoid']);
            $result[$i]['modalidad'] = $modalidades[$item['modalidadPeriodo']];
            $result[$i]['modalidadNumber'] = $item['modalidadPeriodo'];
            $result[$i]['grupo'] = fixEncode($item['col_grupo']);
            $result[$i]['semestre'] = fixEncode($item['col_grado']);
            if($sthm->rowCount()) {
                $result[$i]['planeacion'] = '<a target="_blank" href="'.$download_url.'planeacion/'.$pl->col_archivo.'"><i class="fas fa-file"></i></a>';
                $result[$i]['aprobada'] = $pl->col_estatus;
            }else{
                $result[$i]['planeacion'] = '-';
                $result[$i]['aprobada'] = 2;
            }
            $result[$i]['id'] = $pl->col_id;
            $i++;
        }

        // $result['debug'] = $query;

        return $this->response->withJson($result);

    });

    $this->get('/listPlaneaciones', function (Request $request, Response $response, array $args) {
        global $download_url;

        $alumnoid = getCurrentUserID();
        $periodoid = getLastPeriodoAlumno(intval($alumnoid), $this->db);
        $periodoData = getPeriodo($periodoid, $this->db, false);

        $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');
        $currentPeriodos = getCurrentPeriodos($this->db, $types[$periodoData->col_modalidad]);

        $materias = getMateriasByAlumnoWithPlaneaciones($alumnoid, $this->db);
        // $_materias = getMateriasByAlumno($alumnoid, $this->db);
        // print_r($materias);


        $query = "SELECT * FROM tbl_materias_maestros_planeacion WHERE col_periodoid='".intval($periodoid)."' AND col_materiaid IN (".implode(',', $materias['regulares']).")";
        // $query = "SELECT * FROM tbl_materias_maestros_planeacion WHERE col_periodoid IN (".implode(',', $currentPeriodos).") AND col_materiaid IN (".implode(',', $materias['regulares']).") GROUP BY col_materiaid";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        $addMateria = Array();
        foreach($todos as $item){

            $query_materia = "SELECT * FROM tbl_materias WHERE col_id='".$item['col_materiaid']."'";
            $sth_materias = $this->db->prepare($query_materia);
            $sth_materias->execute();
            $materia = $sth_materias->fetch(PDO::FETCH_OBJ);
            if($materia->col_semestre == $periodoData->col_grado && !in_array($materia->col_id, $addMateria)) {
                $addMateria[] = $materia->col_id;

                $query_maestro = "SELECT CONCAT(u.col_firstname, ' ', u.col_lastname) AS nombre_maestro, u.col_id, t.col_periodoid FROM tbl_maestros_taxonomia t LEFT OUTER JOIN tbl_users u ON u.col_id=t.col_maestroid WHERE t.col_materia_clave='".$materia->col_clave."' AND t.col_periodoid='".$periodoid."'";
                $sth_tax = $this->db->prepare($query_maestro);
                $sth_tax->execute();
                $maestro = $sth_tax->fetch(PDO::FETCH_OBJ);

                $result[$i]['planeacionID'] = $item['col_id'];
                $result[$i]['materiaID'] = fixEncode($materia->col_id);
                $result[$i]['maestroID'] = $maestro->col_id;
                $result[$i]['periodoID'] = $maestro->col_periodoid;
                $result[$i]['materia'] = fixEncode($materia->col_nombre);
                $result[$i]['materiaClave'] = fixEncode($materia->col_clave);
                $result[$i]['maestro'] = fixEncode($maestro->nombre_maestro);
                $result[$i]['descargar'] .= '<a class="text-primary" target="_blank" title="Descargar" href="'.$download_url.'planeacion/'.$item['col_archivo'].'"><i class="fas fa-file"></i> Descargar</a>';
                $result[$i]['descargarArchivo'] = $item['col_archivo'];

                $i++;
            }
        }


        // Academias / Talleres
        if(count($materias['acata'])) {
            // echo $query = "SELECT * FROM tbl_materias_maestros_planeacion WHERE col_periodoid  IN (".implode(',', $currentPeriodos).") AND col_materiaid IN (".implode(',', array_unique($materias['acata'])).")";
            $query = "SELECT * FROM tbl_materias_maestros_planeacion WHERE col_periodoid  = '".$periodoid."' AND col_materiaid IN (".implode(',', array_unique($materias['acata'])).")";

            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();

            foreach($todos as $item){
                if(in_array($item['col_materiaid'], $addMateria)) continue;
                $addMateria[] = $item['col_materiaid'];
                $query_materia = "SELECT * FROM tbl_materias WHERE col_id='".$item['col_materiaid']."'";
                $sth_materias = $this->db->prepare($query_materia);
                $sth_materias->execute();
                $materia = $sth_materias->fetch(PDO::FETCH_OBJ);

                $query_maestro = "SELECT CONCAT(u.col_firstname, ' ', u.col_lastname) AS nombre_maestro, u.col_id FROM tbl_maestros_taxonomia t LEFT OUTER JOIN tbl_users u ON u.col_id=t.col_maestroid WHERE t.col_materia_clave='".$materia->col_clave."' AND t.col_periodoid IN (".implode(',', getCurrentPeriodos($this->db, 'ldsem')).")";
                $sth_tax = $this->db->prepare($query_maestro);
                $sth_tax->execute();
                $maestro = $sth_tax->fetch(PDO::FETCH_OBJ);
                if(intval($maestro->col_id) == 0) continue;

                if(strtoupper(substr($materia->col_clave, 0, 2)) == 'AC') {
                    //$queryCheckAcademias = "SELECT * FROM tbl_academias WHERE col_alumnoid='".$alumnoid."' AND col_materiaid='".$materia->col_id."' AND col_periodoid IN (".implode(',', $currentPeriodos).")";
                    $queryCheckAcademias = "SELECT * FROM tbl_academias WHERE col_alumnoid='".$alumnoid."' AND col_materiaid IN (SELECT col_id FROM tbl_materias WHERE col_clave LIKE '".claveMateria($materia->col_clave)."%') AND col_periodoid IN (".implode(',', $currentPeriodos).")";
                    $sthAcademias = $this->db->prepare($queryCheckAcademias);
                    $sthAcademias->execute();
                    if($sthAcademias->rowCount() == 0) continue;
                }

                if(strtoupper(substr($materia->col_clave, 0, 2)) == 'TL') {
                    // $queryCheckTalleres = "SELECT * FROM tbl_talleres WHERE col_alumnoid='".$alumnoid."' AND col_materiaid='".$materia->col_id."' AND col_periodoid IN (".implode(',', $currentPeriodos).")";
                    $queryCheckTalleres = "SELECT * FROM tbl_talleres WHERE col_alumnoid='".$alumnoid."' AND col_materiaid IN (SELECT col_id FROM tbl_materias WHERE col_clave LIKE '".claveMateria($materia->col_clave)."%') AND col_periodoid IN (".implode(',', $currentPeriodos).")";
                    $sthTalleres = $this->db->prepare($queryCheckTalleres);
                    $sthTalleres->execute();
                    if($sthTalleres->rowCount() == 0) continue;
                }

                $result[$i]['planeacionID'] = $item['col_id'];
                $result[$i]['materiaID'] = intval($materia->col_id);
                $result[$i]['maestroID'] = $maestro->col_id;
                $result[$i]['materia'] = fixEncode($materia->col_nombre);
                $result[$i]['maestro'] = fixEncode($maestro->nombre_maestro);
                $result[$i]['descargar'] .= '<a class="text-primary" target="_blank" title="Descargar" href="'.$download_url.'planeacion/'.$item['col_archivo'].'"><i class="fas fa-file"></i> Descargar</a>';
                $result[$i]['descargarArchivo'] = $item['col_archivo'];
                $i++;

            }
        }

        return $this->response->withJson($result);

    });

    $this->get('/list', function (Request $request, Response $response, array $args) {

        if($_REQUEST['planEstudios'] == ''){
           $query = "SELECT * FROM tbl_materias WHERE col_plan_estudios='".getCurrentPlan($this->db)."' ORDER BY col_id DESC";
        }else if($_REQUEST['planEstudios'] == '0'){
            $query = "SELECT * FROM tbl_materias ORDER BY col_id DESC";
        }else{
            $query = "SELECT * FROM tbl_materias WHERE col_plan_estudios='".intval($_REQUEST['planEstudios'])."' ORDER BY col_id DESC";
        }
        if($_REQUEST['periodo'] != '' && $_REQUEST['periodo'] != '-1'){
            $periodoData = getPeriodo(intval($_REQUEST['periodo']), $this->db, false);
            $query = "SELECT m.* FROM tbl_maestros_taxonomia t LEFT JOIN tbl_materias m ON m.col_clave=t.col_materia_clave WHERE m.col_carrera='".$periodoData->col_carreraid."' AND  m.col_semestre='".$periodoData->col_grado."' AND t.col_periodoid='".intval($_REQUEST['periodo'])."' ORDER BY m.col_nombre DESC";
        }

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        $modalidad = array(0=>'Semestral', 1=>'Cuatrimestral');
        foreach($todos as $item){
            $queryPlan = 'SELECT * FROM tbl_planes_estudios WHERE col_id="'.intval($item['col_plan_estudios']).'"';
            $sthPlan = $this->db->prepare($queryPlan);
            $sthPlan->execute();
            $dataPlan = $sthPlan->fetch(PDO::FETCH_OBJ);

            $carrera = getCarrera($item['col_carrera'], $this->db);
            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['nombre'] = fixEncode($item['col_nombre']);
            $result[$i]['carrera'] = $carrera[nombre];
            $result[$i]['revoe'] = $carrera[revoe];
            $result[$i]['clave'] = fixEncode($item['col_clave']);
            $result[$i]['modalidad'] = $modalidad[$item['col_plan']];
            $result[$i]['plan'] = $dataPlan->col_nombre;
            $result[$i]['semestre'] = $item['col_semestre'];
            $result[$i]['grupo'] = $item['col_semestre'];
            $result[$i]['opciones'] .= '<a class="opcion-table" title="Asignación" href="#/pages/materias/asignacion/'.$item['col_id'].'"><i class="fas fa-user"></i></i></a>';

            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->post('/get', function (Request $request, Response $response, array $args) {
        global $uploaddir, $download_url;

        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $maestroID = getCurrentUserID();
        $input = $request->getParsedBody();
        $materia_id = $input['params']['id'];
        $query = 'SELECT * FROM tbl_materias WHERE col_id="'.intval($materia_id).'"';

        if(intval($input['params']['tax']) > 0) {
            $query = 'SELECT t.col_materia_clave, p.col_grado, p.col_carreraid, p.col_plan_estudios, t.col_periodoid FROM tbl_maestros_taxonomia t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_id="'.intval($input['params']['tax']).'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $taxData = $sth->fetch(PDO::FETCH_OBJ);
            $materia_clave = $taxData->col_materia_clave;
            $plan_estudios = $taxData->col_plan_estudios;
            $semestre = $taxData->col_grado;
            $carreraid = $taxData->col_carreraid;
            $periodoid = $taxData->col_periodoid;


            $query = 'SELECT * FROM tbl_materias WHERE col_carrera="'.$carreraid.'" AND col_semestre="'.$semestre.'" AND col_clave="'.$materia_clave.'" AND col_plan_estudios="'.$plan_estudios.'"';

        }



        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        if($data->col_tipo_materia == 0) {
            $letrasClave = strtoupper(preg_replace("/[^A-Za-z]/", '', trim($data->col_clave)));
            $numerosClave = preg_replace("/[^0-9]/", '', trim($data->col_clave));

            $queryTiposMaterias = "SELECT * FROM tbl_materias_tipos WHERE col_letras='".$letrasClave."'";
            $xsth = $this->db->prepare($queryTiposMaterias);
            $xsth->execute();
            $tipoMateriaData = $xsth->fetch(PDO::FETCH_OBJ);

            $data->col_tipo_materia = $tipoMateriaData->col_id;
            $data->col_numero_clave = $numerosClave;

        }



        $claveGenerada = $tipoMateriaData->col_letras.$input->numeroClave;

        $result['id'] = $data->col_id;
        $result['nombre'] = fixEncode($data->col_nombre);
        $result['clave'] = $data->col_clave;
        $result['grado'] = $data->col_semestre;
        $result['serie'] = $data->col_serie;
        $result['plan'] = $data->col_plan;
        $result['semestre'] = $data->col_semestre;
        $result['carrera'] = $data->col_carrera;
        $result['carreraid'] = $data->col_carrera;
        $result['creditos'] = $data->col_creditos;
        $result['plan_estudios'] = $data->col_plan_estudios;
        $result['plan_estudiosid'] = $data->col_plan_estudios;
        $result['numero_clave'] = $data->col_numero_clave;
        //$result['consecutivo_clave'] = $data->col_consecutivo_clave;
        $result['tipo_materia'] = $data->col_tipo_materia;


        $modalidad = array(0=>'Semestral', 1=>'Cuatrimestral');
        $queryPlan = 'SELECT * FROM tbl_planes_estudios WHERE col_id="'.intval($data->col_plan_estudios).'"';
        $sthPlan = $this->db->prepare($queryPlan);
        $sthPlan->execute();
        $dataPlan = $sthPlan->fetch(PDO::FETCH_OBJ);

        $carrera = getCarrera($data->col_carrera, $this->db);
        $result['carrera'] = $carrera[nombre];
        $result['revoe'] = $carrera[revoe];
        $result['modalidad'] = $modalidad[$data->col_plan];
        $result['plan_estudios'] = $dataPlan->col_nombre;

        $losPeriodos = getCurrentPeriodos($this->db, $carrera['modalidad_periodo']);


        if($userType == 'maestro'){

            $queryTax = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_periodoid="'.$periodoid.'" AND col_maestroid="'.$maestroID.'" AND col_materia_clave="'.trim($result['clave']).'"';
            $tax = $this->db->prepare($queryTax);
            $tax->execute();
            $dataTax = $tax->fetch(PDO::FETCH_OBJ);

            $queryPlan = 'SELECT * FROM tbl_materias_maestros_planeacion WHERE col_periodoid="'.$periodoid.'" AND col_maestroid="'.$maestroID.'" AND col_materiaid="'.intval($result['id']).'"';
            $sth = $this->db->prepare($queryPlan);
            $sth->execute();
            $data = $sth->fetch(PDO::FETCH_OBJ);

            $queryTax = 'SELECT * FROM tbl_periodos WHERE col_id="'.$periodoid.'"';
            $per = $this->db->prepare($queryTax);
            $per->execute();
            $dataPeriodo = $per->fetch(PDO::FETCH_OBJ);

            $queryTax = 'SELECT * FROM tbl_maestros WHERE col_userid="'.$maestroID.'"';
            $per = $this->db->prepare($queryTax);
            $per->execute();
            $maestroData = $per->fetch(PDO::FETCH_OBJ);

            $_response['blocked'] = 'false';
            if(strtotime('now') > strtotime($dataPeriodo->col_fecha_inicio)){
                $_response['blocked'] = 'true';
            }

            if($data->col_archivo != '') {
                $data->col_archivo = $download_url.'planeacion/'.$data->col_archivo;
            }else{
                $data = '';
            }

            if($maestroData->col_edit_planeaciones == 1) {
                $_response['blocked'] = 'false';
            }

            $_response['periodoNombre'] = fixEncode($dataPeriodo->col_nombre);
            $_response['grupo'] = $dataPeriodo->col_grado.'-'.$dataPeriodo->col_grupo;
            $_response['materia'] = $result;
            $_response['planeacion'] = $data;
            $_response['taxonomia'] = $dataTax;
            $_response['debug'] = $queryPlan;
            $_response['fechaLimite'] = fechaTexto($dataPeriodo->col_fecha_inicio);

            return $this->response->withJson($_response);
        }

        return $this->response->withJson($result);
    });

    $this->put('/update', function (Request $request, Response $response, $args) {

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        // $query = 'UPDATE tbl_materias SET
        // col_nombre="'.($input->nombre).'",
        // col_clave="'.$input->clave.'",
        // col_serie="'.$input->serie.'",
        // col_plan="'.$input->plan.'",
        // col_carrera="'.$input->carrera.'",
        // col_semestre="'.$input->semestre.'",
        // col_plan_estudios="'.$input->planEstudios.'",
        // col_creditos="'.$input->creditos.'",
        // col_updated_at="'.date("Y-m-d H:i:s").'",
        // col_updated_by="'.$input->userid.'" WHERE col_id="'.$input->id.'"';

        if($input->numeroClave == '' OR !is_numeric($input->numeroClave)) {
            $_response['status'] = 'false';
            $_response['error'] = 'El número de clave no me puede estar vacio, y solo se permiten números en este campo.';
            return $this->response->withJson($_response);
        }

        if($input->tipoMateria == 0) {
            $_response['status'] = 'false';
            $_response['error'] = 'Debes seleccionar un tipo de clave';
            return $this->response->withJson($_response);
        }


        $xsth = $this->db->prepare("SELECT * FROM tbl_materias_tipos WHERE col_id='".$input->tipoMateria."'");
        $xsth->execute();
        $tipoMateriaData = $xsth->fetch(PDO::FETCH_OBJ);

        $claveGenerada = $tipoMateriaData->col_letras.$input->numeroClave;

        $queryCheck = "SELECT * FROM tbl_materias WHERE col_id!='".$input->id."' AND col_plan='".$input->plan."' AND col_plan_estudios='".$input->planEstudios."' AND col_semestre='".$input->semestre."' AND col_carrera='".$input->carrera."' AND (col_clave='".$claveGenerada."' OR (col_tipo_materia='".$input->tipoMateria."' AND col_numero_clave='".$input->numeroClave."'))";
        $xsth = $this->db->prepare($queryCheck);
        $xsth->execute();
        if($repetida = $xsth->rowCount() > 0){
            $materiaData = $xsth->fetch(PDO::FETCH_OBJ);
            $_response['status'] = 'false';
            $_response['error'] = 'Ya existe una materia con la misma clave y que coincide con la carrera, el semestre, la modalidad y el plan de estudios, favor de verificar. La materia es: <b>'.fixEncode($materiaData->col_nombre).'</b>';
            return $this->response->withJson($_response);
        }

        $query = 'UPDATE tbl_materias SET
        col_nombre="'.($input->nombre).'",
        col_clave="'.$claveGenerada.'",
        col_numero_clave="'.$input->numeroClave.'",
        col_tipo_materia="'.$input->tipoMateria.'",
        col_serie="'.$input->serie.'",
        col_plan="'.$input->plan.'",
        col_carrera="'.$input->carrera.'",
        col_semestre="'.$input->semestre.'",
        col_plan_estudios="'.$input->planEstudios.'",
        col_creditos="'.$input->creditos.'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$input->userid.'" WHERE col_id="'.$input->id.'"';
        $sth = $this->db->prepare($query);

        $dblog = new DBLog($query, 'tbl_materias', '', '', 'Materias', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        if($sth->execute()){
            $dblog->saveLog();
            $_response['status'] = 'true';
        }

        return $this->response->withJson($_response);

    });

    $this->post('/asignarMaestro', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());


        // tbl_maestros_taxonomia
        $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_periodoid="'.$input->periodoid.'" AND col_materia_clave="'.$input->materiaClave.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        if($sth->rowCount() == 0){

            $query = 'INSERT INTO tbl_maestros_taxonomia (col_maestroid, col_materia_clave, col_periodoid, col_created_at, col_created_by, col_updated_at, col_updated_by)
            VALUES("'.$input->maestroid.'", "'.$input->materiaClave.'", "'.$input->periodoid.'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'")';

            $dblog = new DBLog($query, 'tbl_maestros_taxonomia', '', '', 'Maestros', $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);

            if($sth->execute()){
                $_response['status'] = 'true';
                $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                $dblog->saveLog();
            }

        }else{

            $query = 'UPDATE tbl_maestros_taxonomia SET col_maestroid="'.$input->maestroid.'", col_updated_at="'.date("Y-m-d H:i:s").'", col_updated_by="'.$input->userid.'" WHERE col_id="'.$data->col_id.'"';

            $dblog = new DBLog($query, 'tbl_maestros_taxonomia', '', '', 'Maestros', $this->db);
            $dblog->where = array('col_id' => intval($data->col_id));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);

            if($sth->execute()){
                $dblog->saveLog();
                $_response['status'] = 'true';
            }
        }



        return $this->response->withJson($_response);

    });

    $this->post('/add', function (Request $request, Response $response, $args) {

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());
        $userID = getCurrentUserID();



            if($input->numeroClave == '' OR !is_numeric($input->numeroClave)) {
                $_response['status'] = 'false';
                $_response['error'] = 'El número de clave no me puede estar vacio, y solo se permiten números en este campo.';
                return $this->response->withJson($_response);
            }

            if($input->tipoMateria == 0) {
                $_response['status'] = 'false';
                $_response['error'] = 'Debes seleccionar un tipo de clave';
                return $this->response->withJson($_response);
            }

            $xsth = $this->db->prepare("SELECT * FROM tbl_materias_tipos WHERE col_id='".$input->tipoMateria."'");
            $xsth->execute();
            $tipoMateriaData = $xsth->fetch(PDO::FETCH_OBJ);

            $claveGenerada = $tipoMateriaData->col_letras.$input->numeroClave;

            $queryCheck = "SELECT * FROM tbl_materias WHERE col_plan='".$input->plan."' AND col_plan_estudios='".$input->planEstudios."' AND col_semestre='".$input->semestre."' AND col_carrera='".$input->carrera."' AND (col_clave='".$claveGenerada."' OR (col_tipo_materia='".$input->tipoMateria."' AND col_numero_clave='".$input->numeroClave."'))";
            $xsth = $this->db->prepare($queryCheck);
            $xsth->execute();
            if($repetida = $xsth->rowCount() > 0){
                $materiaData = $xsth->fetch(PDO::FETCH_OBJ);
                $_response['status'] = 'false';
                $_response['error'] = 'Ya existe una materia con la misma clave y que coincide con la carrera, el semestre, la modalidad y el plan de estudios, favor de verificar. La materia es: <b>'.fixEncode($materiaData->col_nombre).'</b>';
                return $this->response->withJson($_response);
            }

            // $_response['statusDebug'] = $queryCheck;
            // $_response['status'] = 'false';
            // return $this->response->withJson($_response);
            // $query = 'INSERT INTO tbl_materias (col_nombre, col_carrera, col_clave, col_serie, col_plan, col_semestre, col_plan_estudios, col_creditos, col_created_at, // col_created_by, col_updated_at, col_updated_by)
            // VALUES("'.($input->nombre).'", "'.$input->carrera.'", "'.$input->clave.'", "'.$input->serie.'", "'.$input->plan.'", "'.$input->semestre.'", "'.// $input->planEstudios.'", "'.$input->creditos.'", "'.date("Y-m-d H:i:s").'", "'.$userID.'", "'.date("Y-m-d H:i:s").'", "'.$userID.'")';

            $data = array(
                'col_nombre' => addslashes($input->nombre),
                'col_carrera' => $input->carrera,
                'col_clave' => $claveGenerada,
                'col_numero_clave' => $input->numeroClave,
                //'col_consecutivo_clave' => $input->consecutivoClave,
                'col_tipo_materia' => $input->tipoMateria,
                'col_serie' => $input->serie,
                'col_plan' => $input->plan,
                'col_semestre' => $input->semestre,
                'col_plan_estudios' => $input->planEstudios,
                'col_creditos' => $input->creditos,
                'col_created_at' => date("Y-m-d H:i:s"),
                'col_created_by' => $userID,
                'col_updated_at' => date("Y-m-d H:i:s"),
                'col_updated_by' => $userID,
            );

            $query = 'INSERT INTO tbl_materias ('.implode(",", array_keys($data)).') VALUES("'.implode('", "', array_values($data)).'")';


            $dblog = new DBLog($query, 'tbl_materias', '', '', 'Materias', $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            if($sth->execute()){
                $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                $dblog->saveLog();
                $_response['status'] = 'true';
            }


        return $this->response->withJson($_response);

    });

    $this->delete('/delete', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'DELETE FROM tbl_materias WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_materias', '', '', 'Materias', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        if($sth->execute()){
            $dblog->saveLog();
        }
        // $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->get('/listMaterias', function (Request $request, Response $response, array $args) {

        $query = "SELECT * FROM tbl_materias ORDER BY col_nombre ASC";
        if(intval($_REQUEST['carrera']) > 0 && intval($_REQUEST['plan']) > 0 && isset($_REQUEST['semestre'])){
            $query = "SELECT * FROM tbl_materias WHERE col_semestre='".(intval($_REQUEST['semestre']) - 1)."' AND col_carrera='".intval($_REQUEST['carrera'])."' AND col_plan_estudios='".intval($_REQUEST['plan'])."' ORDER BY col_nombre ASC";
            if(strpos($_REQUEST['semestre'], ',') !== false) { //Multigrupal
                $query = "SELECT * FROM tbl_materias WHERE col_semestre IN (".trim($_REQUEST['semestre']).") AND col_carrera='".intval($_REQUEST['carrera'])."' AND col_plan_estudios='".intval($_REQUEST['plan'])."' ORDER BY col_nombre ASC";
            }
        }
        // echo $query;
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = fixEncode($item['col_id']);
            $result[$i]['label'] = fixEncode($item['col_nombre'])." (".$item['col_clave'].")";
            $result[$i]['text'] = fixEncode($item['col_nombre'])." (".$item['col_clave'].")";
            $i++;
        }

        return $this->response->withJson($result);
    });

    $this->get('/getAlumnosAcademias', function (Request $request, Response $response, array $args) {

        $userID = getCurrentUserID();
        if(intval($_REQUEST['mtax']) > 0){
            $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_id="'.intval($_REQUEST['mtax']).'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $dataTax = $sth->fetch(PDO::FETCH_OBJ);
            $userID = $dataTax->col_maestroid;
        }
        $periodos = getCurrentPeriodos($this->db);

        //$query = 'SELECT col_periodoid, SUBSTRING( col_materia_clave, 1, 4 ) AS materia_clave FROM tbl_maestros_taxonomia WHERE col_materia_clave LIKE "AC%" AND col_maestroid="'.intval($userID).'" AND col_periodoid IN ('.implode(',', $periodos).') GROUP BY materia_clave';
        $query = 'SELECT col_periodoid, col_materia_clave AS materia_clave FROM tbl_maestros_taxonomia WHERE EXISTS (SELECT m.col_id FROM tbl_materias m WHERE m.col_clave=col_materia_clave) AND col_materia_clave LIKE "AC%" AND col_maestroid="'.intval($userID).'" AND col_periodoid IN ('.implode(',', $periodos).') GROUP BY materia_clave';

        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $periodoData = getPeriodo($data->col_periodoid, $this->db, false);
        //$query = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.trim($data->materia_clave).'%" AND col_semestre="'.$periodoData->col_grado.'" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
        $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.trim($data->materia_clave).'" AND col_semestre="'.$periodoData->col_grado.'" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMateria = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_actividades WHERE col_created_by="'.$userID.'" AND col_visible_excepto LIKE "%'.strtoupper($dataMateria->col_clave).'%" ';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $marcadas = $sth->rowCount();



        $queryx = 'SELECT * FROM tbl_materias WHERE col_clave="'.(trim($data->materia_clave)).'" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
        $sthx = $this->db->prepare($queryx);
        $sthx->execute();
        $dataMateriaMulti = $sthx->fetchAll();
        unset($multis);
        foreach($dataMateriaMulti as $mm) {
            $multis[] = $mm['col_id'];
        }
        $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');
        $losPeriodos = getCurrentPeriodos($this->db, $types[$periodoData->col_modalidad]);
        /*
        $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
        "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_academias ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
        "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC"; */

        $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
        "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_academias ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
        "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;

        foreach($todos as $item){

            $_response[$i]['col_id'] = $item['col_id'];
            $_response[$i]['alumno'] = fixEncode(trim($item['col_apellidos']).' '.trim($item['col_nombres']));

            $subQuery = 'SELECT col_id FROM tbl_actividades WHERE col_created_by="'.$userID.'" AND col_visible_excepto LIKE "%'.claveMateria(strtoupper($dataMateria->col_clave)).'%" ';

            $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$item['col_id'].'" AND col_calificacion="10.00" AND col_actividadid IN ('.$subQuery.') ';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $hechas = $sth->rowCount();

            $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$item['col_id'].'" AND col_calificacion="0.00" AND col_actividadid IN ('.$subQuery.') AND col_updated_by="'.$item['col_id'].'" ';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $sincalificar = $sth->rowCount();


            $periodoData = getPeriodo($item['col_periodoid'], $this->db, false);
            $_response[$i]['grupo'] = $periodoData->col_grado.'-'.$periodoData->col_grupo;
            $_response[$i]['actividadesHechas'] = $hechas; //Entregadas
            $_response[$i]['actividadesSinCalificar'] = $sincalificar; //Entregadas
            $_response[$i]['actividadesNoHechas'] = $marcadas - ($hechas + $sincalificar);
            $_response[$i]['actividadesTotales'] = $marcadas;

            $i++;
        }


        $result['list'] = $_response;
        $result['nombreAcademia'] = fixEncode($dataMateria->col_nombre);
        $result['materiaid'] = fixEncode($dataMateria->col_id);

        return $this->response->withJson($result);
    });

    $this->get('/getAlumnoAcademia', function (Request $request, Response $response, array $args) {
        global $download_url;

        $alumnoid = intval($_REQUEST['alumnoid']);
        $materiaid = intval($_REQUEST['materiaid']);


        $userID = getCurrentUserID();
        if(intval($_REQUEST['taxid']) > 0){
            $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_id="'.intval($_REQUEST['taxid']).'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $dataTax = $sth->fetch(PDO::FETCH_OBJ);
            $userID = $dataTax->col_maestroid;
        }
        $periodos = getCurrentPeriodos($this->db);

        $query = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $materiaData = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
        $periodoData = getPeriodo($alumnoData->col_periodoid, $this->db, false);


        $query = 'SELECT * FROM tbl_actividades WHERE col_created_by="'.$userID.'" AND col_visible_excepto LIKE "%'.claveMateria(strtoupper($materiaData->col_clave)).'%" ';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;

        foreach($todos as $item){
            $calificacion = 'empty';
            $archivo = '';
            $query = 'SELECT *, IF(col_calificacion = "10.00", "true", "false") AS calificacion FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid="'.$item['col_id'].'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            if($sth->rowCount()){
                $tarea = $sth->fetch(PDO::FETCH_OBJ);
                $calificacion = $tarea->calificacion;
                $archivo = $download_url.'tareas/'.$tarea->col_archivo;
            }


            $_response[$i]['id'] = intval($item['col_id']);
            $_response[$i]['actividad'] = fixEncode($item['col_titulo']);
            $_response[$i]['calificacion'] = $calificacion;
            $_response[$i]['archivo'] = $archivo;

            $i++;
        }

        $query = 'SELECT * FROM tbl_academias_observaciones WHERE col_maestroid="'.$userID.'" AND col_materiaid="'.$materiaData->col_id.'" AND col_alumnoid="'.$alumnoData->col_id.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $acObser = $sth->fetch(PDO::FETCH_OBJ);

        $result['list'] = $_response;
        $result['nombreAlumno'] = fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos);
        $result['grupo'] = fixEncode($periodoData->col_grado.'-'.$periodoData->col_grupo);
        $result['materiaid'] = intval($materiaData->col_id);
        $result['nombreMateria'] = fixEncode($materiaData->col_nombre);
        $result['observaciones'] = fixEncode($acObser->col_observaciones);

        return $this->response->withJson($result);
    });

    $this->post('/guardarAcademiasDetalles', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $maestroid = getCurrentUserID();
        $materiaid = intval($input->materiaid);
        $alumnoid = intval($input->alumnoid);

        $query = 'SELECT * FROM tbl_academias_observaciones WHERE col_maestroid="'.$maestroid.'" AND col_materiaid="'.$materiaid.'" AND col_alumnoid="'.$alumnoid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        if($sth->rowCount()){
            $acObser = $sth->fetch(PDO::FETCH_OBJ);

            $query = 'UPDATE tbl_academias_observaciones SET col_observaciones="'.addslashes($input->observaciones).'", col_updated_at="'.date("Y-m-d H:i:s").'", col_updated_by="'.$maestroid.'" WHERE col_id="'.$acObser->col_id.'"';

            $dblog = new DBLog($query, 'tbl_academias_observaciones', '', '', 'Academias', $this->db);
            $dblog->where = array('col_id' => intval($acObser->col_id));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->saveLog();

        }else{

            $data = array(
                'col_maestroid' => $maestroid,
                'col_materiaid' => $materiaid,
                'col_alumnoid' => $alumnoid,
                'col_observaciones' => addslashes($input->observaciones),
                'col_created_at' => date("Y-m-d H:i:s"),
                'col_created_by' => $maestroid,
                'col_updated_at' => date("Y-m-d H:i:s"),
                'col_updated_by' => $maestroid,
            );

            $query = 'INSERT INTO tbl_academias_observaciones ('.implode(",", array_keys($data)).')
            VALUES("'.implode('", "', array_values($data)).'")';

            $dblog = new DBLog($query, 'tbl_academias_observaciones', '', '', 'Academias', $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();
        }

        foreach($input->actividades as $k => $v) {

            if($v != ''){
                $actividadid = intval($k);

                $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_actividadid="'.$actividadid.'" AND col_alumnoid="'.$alumnoid.'"';
                $sth = $this->db->prepare($query);
                $sth->execute();
                if($sth->rowCount()){
                    if($v != 'empty') {

                        $query = 'UPDATE tbl_actividades_tareas SET col_calificacion="'.trim($v).'", col_updated_at="'.date("Y-m-d H:i:s").'", col_updated_by="'.$maestroid.'" WHERE col_actividadid="'.$actividadid.'"';

                        $dblog = new DBLog($query, 'tbl_actividades_tareas', '', '', 'Actividades', $this->db);
                        $dblog->where = array('col_actividadid' => intval($actividadid));
                        $dblog->prepareLog();

                        $sth = $this->db->prepare($query);
                        $sth->execute();

                        $dblog->saveLog();
                    }
                    if($v == 'empty') {
                        $query = 'DELETE FROM tbl_actividades_tareas WHERE col_actividadid="'.$actividadid.'"';

                        $dblog = new DBLog($query, 'tbl_actividades_tareas', '', '', 'Actividades', $this->db);
                        $dblog->where = array('col_actividadid' => intval($actividadid));
                        $dblog->prepareLog();

                        $sth = $this->db->prepare($query);
                        $sth->execute();

                        $dblog->saveLog();
                    }

                }else{
                    if($v != 'empty') {
                        $data = array(
                            'col_actividadid' => $actividadid,
                            'col_alumnoid' => $alumnoid,
                            'col_calificacion' => trim($v),
                            'col_archivo' => '',
                            'col_created_at' => date("Y-m-d H:i:s"),
                            'col_created_by' => $maestroid,
                            'col_updated_at' => date("Y-m-d H:i:s"),
                            'col_updated_by' => $maestroid,
                        );

                        $query = 'INSERT INTO tbl_actividades_tareas ('.implode(",", array_keys($data)).')
                        VALUES("'.implode('", "', array_values($data)).'")';

                        $dblog = new DBLog($query, 'tbl_actividades_tareas', '', '', 'Actividades', $this->db);
                        $dblog->prepareLog();

                        $sth = $this->db->prepare($query);
                        $sth->execute();

                        $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
                        $dblog->saveLog();
                    }

                }

            }
        }

        $result['status'] = 'true';
        return $this->response->withJson($result);

    });

    $this->get('/getListAcademias', function (Request $request, Response $response, array $args) {
        global $download_url;

        $alumnoid = intval($_REQUEST['alumnoid']);
        $materiaid = intval($_REQUEST['materiaid']);


        $userID = getCurrentUserID();
        $periodos = getCurrentPeriodos($this->db);
        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $r = $c->fetch(PDO::FETCH_OBJ);
        $grupos_periodos = array($r->col_periodo, $r->col_periodo_cuatri, $r->col_periodo_maestria, $r->col_periodo_doctorado);


        // $query = 'SELECT t.*, CONCAT(u.col_firstname, " ", u.col_lastname) AS nombreMaestro FROM tbl_maestros_taxonomia t LEFT OUTER JOIN tbl_users u ON u.col_id=t.col_maestroid WHERE t.col_materia_clave LIKE "AC%" AND t.col_periodoid IN ('.implode(',', $periodos).') GROUP BY t.col_maestroid';
        $query = 'SELECT t.*, CONCAT(u.col_firstname, " ", u.col_lastname) AS nombreMaestro FROM tbl_maestros_taxonomia t LEFT OUTER JOIN tbl_users u ON u.col_id=t.col_maestroid WHERE t.col_materia_clave LIKE "AC%" AND t.col_periodoid IN ('.implode(',', $periodos).')';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $subquery = 'SELECT col_id FROM tbl_eva_maestros WHERE col_estatus="2" AND col_group_periodoid IN ('.implode(',', array_unique($grupos_periodos)).') ORDER BY col_id DESC';
        $i = 0;

        foreach($todos as $item){
            $periodoData = getPeriodo($item['col_periodoid'], $this->db, false);
            $queryMateria = 'SELECT * FROM tbl_materias WHERE col_carrera="'.$periodoData->col_carreraid.'" AND col_semestre="'.$periodoData->col_grado.'" AND col_clave LIKE "'.trim($item['col_materia_clave']).'%" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
            $sthm = $this->db->prepare($queryMateria);
            $sthm->execute();
            $materiaData = $sthm->fetch(PDO::FETCH_OBJ);
            if(!$materiaData) continue;

            $subQueryMateria = 'SELECT col_id FROM tbl_materias WHERE col_carrera="'.$periodoData->col_carreraid.'" AND col_clave LIKE "'.claveMateria(strtoupper(trim($item['col_materia_clave']))).'%" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';

            $queryPlaneacion = 'SELECT * FROM tbl_materias_maestros_planeacion WHERE col_maestroid="'.$item['col_maestroid'].'" AND col_periodoid IN ('.implode(',', $periodos).') AND col_materiaid IN ('.$subQueryMateria.')';
            $sthm = $this->db->prepare($queryPlaneacion);
            $sthm->execute();
            $pl = $sthm->fetch(PDO::FETCH_OBJ);

            $query = 'SELECT * FROM tbl_eva_maestros_observaciones WHERE col_evaid IN ('.$subquery.') AND col_maestroid="'.$item['col_maestroid'].'" AND col_materiaid IN ('.$subQueryMateria.') AND col_estatus=1 ORDER BY col_evaid DESC LIMIT 1';
            $fth = $this->db->prepare($query);
            $fth->execute();

            $_response[$i]['col_id'] = intval($item['col_id']);
            $_response[$i]['academia'] = fixEncode($materiaData->col_nombre);
            $_response[$i]['maestro'] = fixEncode($item['nombreMaestro']);
            if($pl->col_archivo != ''){
                $_response[$i]['opcion1'] = '<a target="_blank" class="text-secondary" href="'.$download_url.'planeacion/'.$pl->col_archivo.'"><i class="fas fa-download text-info"></i> Descargar</a>';
            }else{
                $_response[$i]['opcion1'] = '';
            }

            if($fth->rowCount()){
                $dataEvaMaestro = $fth->fetch(PDO::FETCH_OBJ);
                $_response[$i]['opcion2'] = '<a class="text-secondary" href="#/pages/academias/evaluacion/'.$dataEvaMaestro->col_evaid.'/maestro/'.$item['col_maestroid'].'/materia/'.$materiaData->col_id.'"><i class="far fa-check-square text-info"></i> Ver Resultados</a>';
            }else{
                $_response[$i]['opcion2'] = '';
            }

            $i++;
        }

        return $this->response->withJson($_response);
    });

    $this->get('/getListClubs', function (Request $request, Response $response, array $args) {
        global $download_url;

        $alumnoid = intval($_REQUEST['alumnoid']);
        $materiaid = intval($_REQUEST['materiaid']);


        $userID = getCurrentUserID();
        $periodos = getCurrentPeriodos($this->db);
        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $r = $c->fetch(PDO::FETCH_OBJ);
        $grupos_periodos = array($r->col_periodo, $r->col_periodo_cuatri, $r->col_periodo_maestria, $r->col_periodo_doctorado);


        $query = 'SELECT t.*, CONCAT(u.col_firstname, " ", u.col_lastname) AS nombreMaestro FROM tbl_maestros_taxonomia t LEFT OUTER JOIN tbl_users u ON u.col_id=t.col_maestroid WHERE t.col_materia_clave LIKE "CL%" AND t.col_periodoid IN ('.implode(',', $periodos).')';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $subquery = 'SELECT col_id FROM tbl_eva_maestros WHERE col_estatus="2" AND col_group_periodoid IN ('.implode(',', array_unique($grupos_periodos)).') ORDER BY col_id DESC';
        $i = 0;

        foreach($todos as $item){
            $periodoData = getPeriodo($item['col_periodoid'], $this->db, false);
            $queryMateria = 'SELECT * FROM tbl_materias WHERE col_carrera="'.$periodoData->col_carreraid.'" AND col_semestre="'.$periodoData->col_grado.'" AND col_clave LIKE "'.trim($item['col_materia_clave']).'%" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
            $sthm = $this->db->prepare($queryMateria);
            $sthm->execute();
            $materiaData = $sthm->fetch(PDO::FETCH_OBJ);

            $subQueryMateria = 'SELECT col_id FROM tbl_materias WHERE col_carrera="'.$periodoData->col_carreraid.'" AND col_clave LIKE "'.claveMateria(strtoupper(trim($item['col_materia_clave']))).'%" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';

            $queryPlaneacion = 'SELECT * FROM tbl_materias_maestros_planeacion WHERE col_maestroid="'.$item['col_maestroid'].'" AND col_periodoid IN ('.implode(',', $periodos).') AND col_materiaid IN ('.$subQueryMateria.')';
            $sthm = $this->db->prepare($queryPlaneacion);
            $sthm->execute();
            $pl = $sthm->fetch(PDO::FETCH_OBJ);

            $query = 'SELECT * FROM tbl_eva_maestros_observaciones WHERE col_evaid IN ('.$subquery.') AND col_maestroid="'.$item['col_maestroid'].'" AND col_materiaid IN ('.$subQueryMateria.') AND col_estatus=1 ORDER BY col_evaid DESC LIMIT 1';
            $fth = $this->db->prepare($query);
            $fth->execute();

            $_response[$i]['col_id'] = intval($item['col_id']);
            if($periodoData->col_club_lectura != '') {
                $_response[$i]['club'] = fixEncode($periodoData->col_club_lectura);
            }else{
                $_response[$i]['club'] = fixEncode($materiaData->col_nombre);
            }
            $_response[$i]['grupo'] = $periodoData->col_grado.'-'.$periodoData->col_grupo;
            $_response[$i]['maestro'] = fixEncode($item['nombreMaestro']);
            if($pl->col_archivo != ''){
                $_response[$i]['opcion1'] = '<a target="_blank" class="text-secondary" href="'.$download_url.'planeacion/'.$pl->col_archivo.'"><i class="fas fa-download text-info"></i> Descargar</a>';
            }else{
                $_response[$i]['opcion1'] = '';
            }

            if($fth->rowCount()){
                $dataEvaMaestro = $fth->fetch(PDO::FETCH_OBJ);
                $_response[$i]['opcion2'] = '<a class="text-secondary" href="#/pages/clublectura/evaluacion/'.$dataEvaMaestro->col_evaid.'/maestro/'.$item['col_maestroid'].'/materia/'.$materiaData->col_id.'"><i class="far fa-check-square text-info"></i> Ver Resultados</a>';
            }else{
                $_response[$i]['opcion2'] = '';
            }

            $i++;
        }

        return $this->response->withJson($_response);
    });

    $this->get('/getAlumnosClubs', function (Request $request, Response $response, array $args) {

        $userID = getCurrentUserID();
        if(intval($_REQUEST['mtax']) > 0){
            $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_id="'.intval($_REQUEST['mtax']).'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $dataTax = $sth->fetch(PDO::FETCH_OBJ);
            $userID = $dataTax->col_maestroid;
        }
        $periodos = getCurrentPeriodos($this->db);

        $query = 'SELECT col_periodoid, SUBSTRING( col_materia_clave, 1, 4 ) AS materia_clave FROM tbl_maestros_taxonomia WHERE col_materia_clave LIKE "CL%" AND col_maestroid="'.intval($userID).'" AND col_periodoid IN ('.implode(',', $periodos).') GROUP BY materia_clave';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $periodoData = getPeriodo($data->col_periodoid, $this->db, false);
        $query = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.trim($data->materia_clave).'%" AND col_semestre="'.$periodoData->col_grado.'" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataMateria = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_actividades WHERE col_created_by="'.$userID.'" AND (col_visible_excepto LIKE "%'.$periodoData->col_id.'%"  AND col_visible_excepto NOT LIKE "%multi%") ';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $marcadas = $sth->rowCount();



        $queryx = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria(trim($data->materia_clave)).'%" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
        $sthx = $this->db->prepare($queryx);
        $sthx->execute();
        $dataMateriaMulti = $sthx->fetchAll();
        unset($multis);
        foreach($dataMateriaMulti as $mm) {
            $multis[] = $mm['col_id'];
        }
        $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');

        $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
        "WHERE a.col_estatus='activo' AND t.col_periodoid='".$periodoData->col_id."' ".
        "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;

        foreach($todos as $item){

            $_response[$i]['col_id'] = $item['col_id'];
            $_response[$i]['alumno'] = fixEncode(trim($item['col_apellidos']).' '.trim($item['col_nombres']));

            $subQuery = 'SELECT col_id FROM tbl_actividades WHERE col_created_by="'.$userID.'" AND col_visible="200" AND (col_visible_excepto LIKE "%'.$periodoData->col_id.'%"  AND col_visible_excepto NOT LIKE "%multi%") ';

            $queryHechas = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$item['col_id'].'" AND col_calificacion>0 AND col_actividadid IN ('.$subQuery.') ';
            $sth = $this->db->prepare($queryHechas);
            $sth->execute();
            $hechas = $sth->rowCount();

            $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$item['col_id'].'" AND col_calificacion="0.00" AND col_actividadid IN ('.$subQuery.') AND col_updated_by="'.$item['col_id'].'" ';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $sincalificar = $sth->rowCount();


            $periodoData = getPeriodo($item['col_periodoid'], $this->db, false);
            $_response[$i]['grupo'] = $periodoData->col_grado.'-'.$periodoData->col_grupo;
            $_response[$i]['actividadesHechas'] = $hechas; //Entregadas
            $_response[$i]['actividadesSinCalificar'] = $sincalificar; //Entregadas
            $_response[$i]['actividadesNoHechas'] = $marcadas - ($hechas + $sincalificar);
            $_response[$i]['actividadesTotales'] = $marcadas;

            $i++;
        }


        $result['list'] = $_response;
        $result['nombreAcademia'] = fixEncode($dataMateria->col_nombre);
        $result['materiaid'] = fixEncode($dataMateria->col_id);

        return $this->response->withJson($result);
    });

    $this->get('/getAlumnoClubs', function (Request $request, Response $response, array $args) {
        global $download_url;

        $alumnoid = intval($_REQUEST['alumnoid']);
        $materiaid = intval($_REQUEST['materiaid']);


        $userID = getCurrentUserID();
        if(intval($_REQUEST['taxid']) > 0){
            $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_id="'.intval($_REQUEST['taxid']).'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $dataTax = $sth->fetch(PDO::FETCH_OBJ);
            $userID = $dataTax->col_maestroid;
        }
        $periodos = getCurrentPeriodos($this->db);

        $query = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $materiaData = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
        $periodoData = getPeriodo($alumnoData->col_periodoid, $this->db, false);


        $query = 'SELECT * FROM tbl_actividades WHERE col_created_by="'.$userID.'" AND (col_visible_excepto LIKE "%'.$periodoData->col_id.'%"  AND col_visible_excepto NOT LIKE "%multi%") ';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;

        foreach($todos as $item){
            $calificacion = 'empty';
            $archivo = '';
            $query = 'SELECT * FROM tbl_actividades_tareas WHERE col_alumnoid="'.$alumnoid.'" AND col_actividadid="'.$item['col_id'].'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            if($sth->rowCount()){
                $tarea = $sth->fetch(PDO::FETCH_OBJ);
                $calificacion = $tarea->col_calificacion;
                $archivo = $download_url.'tareas/'.$tarea->col_archivo;
            }


            $_response[$i]['id'] = intval($item['col_id']);
            $_response[$i]['actividad'] = fixEncode($item['col_titulo']);
            $_response[$i]['calificacion'] = $calificacion;
            $_response[$i]['archivo'] = $archivo;

            $i++;
        }

        $result['list'] = $_response;
        $result['nombreAlumno'] = fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos);
        $result['grupo'] = fixEncode($periodoData->col_grado.'-'.$periodoData->col_grupo);
        $result['materiaid'] = intval($materiaData->col_id);
        $result['nombreMateria'] = fixEncode($materiaData->col_nombre);

        return $this->response->withJson($result);
    });

    $this->get('/tiposMaterias', function (Request $request, Response $response, array $args) {

        $query = "SELECT * FROM tbl_categorias_materias ORDER BY col_id ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        foreach($todos as $item){
            $tipos[$item['col_id']] = fixEncode($item['col_nombre']);
        }

        $query = "SELECT * FROM tbl_materias_tipos WHERE col_estatus=1 ORDER BY col_id DESC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;

        foreach($todos as $item){

            $carrera = getCarrera($item['col_carrera'], $this->db);

            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['nombre'] = fixEncode($item['col_nombre']);
            $result[$i]['clave'] = fixEncode($item['col_letras']);
            $result[$i]['carrera'] = $carrera['nombre'];
            $result[$i]['categoria'] = $tipos[$item['col_tipo']];

            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/conflictosMaterias', function (Request $request, Response $response, array $args) {


        $query = "SELECT *, count(*) AS repetido FROM tbl_materias GROUP BY col_clave";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;

        foreach($todos as $item){
            if($item['repetido'] <= 1) continue;
            $carrera = getCarrera($item['col_carrera'], $this->db);
            $planEstudios = getPlanEstudios('col_id', $item['col_plan_estudios'], $this->db, 'col_nombre');
            $estatus = 'Repetido '.$item['repetido'].' veces';

            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['nombre'] = fixEncode($item['col_nombre']);
            $result[$i]['clave'] = fixEncode($item['col_clave']);
            $result[$i]['grupo'] = fixEncode($item['col_clave']).$item['col_semestre'].$item['plan_de_estudios'].$item['carrera'];
            $result[$i]['semestre'] = $item['col_semestre'];
            $result[$i]['modalidad'] = $carrera['modalidad'];
            $result[$i]['carrera'] = $carrera['nombre'];
            $result[$i]['plan_de_estudios'] = $planEstudios;
            $result[$i]['estatus'] = $estatus;

            $i++;
        }

        $query = "SELECT col_periodoid, col_materia_clave AS materia_clave FROM tbl_maestros_taxonomia WHERE NOT EXISTS (SELECT m.col_id FROM tbl_materias m WHERE m.col_clave=col_materia_clave)";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $resultAll['limbo'] = intval($sth->rowCount());

        $resultAll['listas'] = $result;

        return $this->response->withJson($resultAll);

    });

    $this->get('/conflictosMateriasDuplicados', function (Request $request, Response $response, array $args) {
        $input = json_decode($request->getBody());
        $result = Array();
        $query = "SELECT * FROM tbl_materias WHERE col_clave='".$_REQUEST['clave']."'";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;

        foreach($todos as $item){
            $carrera = getCarrera($item['col_carrera'], $this->db);
            $planEstudios = getPlanEstudios('col_id', $item['col_plan_estudios'], $this->db, 'col_nombre');
            $estatus = 'Repetido '.$item['repetido'].' veces';

            $result[$i]['col_id'] = $item['col_id'];
            $result[$i]['nombre'] = fixEncode($item['col_nombre']);
            $result[$i]['clave'] = fixEncode($item['col_clave']);
            $result[$i]['grupo'] = fixEncode($item['col_clave']).$item['col_semestre'].$item['plan_de_estudios'].$item['carrera'];
            $result[$i]['semestre'] = $item['col_semestre'];
            $result[$i]['modalidad'] = $carrera['modalidad'];
            $result[$i]['carrera'] = $carrera['revoe'] .' - '.$carrera['nombre'];
            $result[$i]['plan_de_estudios'] = $planEstudios;

            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listaTiposMaterias', function (Request $request, Response $response, array $args) {

        $query = "SELECT * FROM tbl_materias_tipos ORDER BY col_id ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){

            if($item['col_letras'] == '') continue;
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['text'] = fixEncode($item['col_nombre']);
            $result[$i]['label'] = fixEncode($item['col_letras']);

            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listaCategoriaMaterias', function (Request $request, Response $response, array $args) {

        $query = "SELECT * FROM tbl_categorias_materias ORDER BY col_id ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){

            $carrera = getCarrera($item['col_carrera'], $this->db);

            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = fixEncode($item['col_nombre']);
            $result[$i]['text'] = fixEncode($item['col_nombre']);

            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->post('/limpiarTaxonomias', function (Request $request, Response $response, $args) {
        global $dblog;
        $_response['status'] = 'true';

        $query = "SELECT col_id, col_periodoid, col_materia_clave AS materia_clave FROM tbl_maestros_taxonomia WHERE NOT EXISTS (SELECT m.col_id FROM tbl_materias m WHERE m.col_clave=col_materia_clave)";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        foreach($todos as $item){

            $query = 'DELETE FROM tbl_maestros_taxonomia WHERE col_id="'.intval($item['col_id']).'"';

            $dblog = new DBLog($query, 'tbl_maestros_taxonomia', '', '', 'Taxonomia Maestros', $this->db, 'Se elimino utilizando las herramientas para solucionar conflictos de materias/claves.');
            $dblog->where = array('col_id' => intval($item['col_id']));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->saveLog();

        }

        return $this->response->withJson($_response);

    });

    $this->post('/guardarTipo', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $_response['error'] = '';
        $input = json_decode($request->getBody());
        $userID = getCurrentUserID();

        if(trim($input->nombre) == '') {
            $_response['status'] = 'false';
            $_response['error'] = 'Debes especificar un nombre que describa el tipo de materia';
            return $this->response->withJson($_response);
        }

        if(trim($input->letras) == '') {
            $_response['status'] = 'false';
            $_response['error'] = 'Debes especificar las letras que formaran el sufijo de la clave de la materia';
            return $this->response->withJson($_response);
        }

        if(intval($input->tipo) == 0) {
            $_response['status'] = 'false';
            $_response['error'] = 'Debes especificar una categoría para el tipo de materia';
            return $this->response->withJson($_response);
        }

        if(intval($input->carrera) == 0) {
            $_response['status'] = 'false';
            $_response['error'] = 'Debes especificar una carrera para el tipo de materia';
            return $this->response->withJson($_response);
        }

        if($input->id == 0) {
            // add

            $data = array(
                "col_nombre" => addslashes($input->nombre),
                "col_carrera" => $input->carrera,
                "col_letras" => preg_replace('/[^A-Z]/', '', strtoupper($input->letras)),
                "col_numero" => $input->numero,
                "col_tipo" => $input->tipo,
                "col_estatus" => 1,
                "col_created_at" => date("Y-m-d H:i:s"),
                "col_created_by" => $userID,
                "col_updated_at" => date("Y-m-d H:i:s"),
                "col_updated_by" => $userID,
            );

            $query = 'INSERT INTO tbl_materias_tipos ('.implode(",", array_keys($data)).')
            VALUES("'.implode('", "', array_values($data)).'")';

            $dblog = new DBLog($query, 'tbl_materias_tipos', '', '', 'Materias', $this->db);
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->where = array('col_id' => intval($this->db->lastInsertId()));
            $dblog->saveLog();
        }else{
            // edit

            $query = 'UPDATE tbl_materias_tipos SET
            col_nombre="'.addslashes($input->nombre).'",
            col_carrera="'.$input->carrera.'",
            col_letras="'.preg_replace('/[^A-Z]/', '', strtoupper($input->letras)).'",
            col_numero="'.$input->numero.'",
            col_tipo="'.$input->tipo.'",
            col_updated_at="'.date("Y-m-d H:i:s").'",
            col_updated_by="'.$userID.'"
            WHERE col_id="'.$input->id.'"';

            $dblog = new DBLog($query, 'tbl_materias_tipos', '', '', 'Materias', $this->db);
            $dblog->where = array('col_id' => intval($input->id));
            $dblog->prepareLog();

            $sth = $this->db->prepare($query);
            $sth->execute();

            $dblog->saveLog();
        }



        $_response['status'] = 'true';

        return $this->response->withJson($_response);

    });

    $this->delete('/deleteTipo', function (Request $request, Response $response, array $args) {
        global $dblog;

        $query = 'UPDATE tbl_materias_tipos SET col_estatus=0 WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_materias_tipos', '', '', 'Materias', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->post('/getDataTipoMateria', function (Request $request, Response $response, array $args) {
        global $uploaddir, $download_url;

        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $maestroID = getCurrentUserID();
        $input = $request->getParsedBody();
        $recordid = $input['params']['id'];

        $query = 'SELECT * FROM tbl_materias_tipos WHERE col_id="'.intval($recordid).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $result['nombre'] = fixEncode($data->col_nombre);
        $result['carrera'] = $data->col_carrera;
        $result['letras'] = $data->col_letras;
        $result['numero'] = $data->col_numero;
        $result['tipo'] = $data->col_tipo;

        return $this->response->withJson($result);

    });

});
// Termina routes.materias.php