<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de calificaciones.
 *
 * Lista de funciones
 *
 * /calificaciones
 * - /list
 * - /listOld
 * - /get
 * - /update
 * - /add
 * - /delete
 * - /listPeriodos
 * - /listGrados
 * - /listGrupos
 * - /listCarreras
 * - /listAlumnos
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/calificaciones', function () {

    $this->get('/list', function (Request $request, Response $response, array $args) {
        $userType = getCurrentUserType(); // maestro - administrativo - alumno

        if(intval($_REQUEST['periodo']) == 0 && $userType == 'administrativo'){
            $_REQUEST['periodo'] = getLastPeriodoAlumno(intval($_REQUEST['alumno']), $this->db);
        }

        $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($_REQUEST['periodo']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $dataPeriodo = $sth->fetch(PDO::FETCH_OBJ);

        $periodoid = $_REQUEST['periodo'];
        $groupid = $dataPeriodo->col_groupid;

        if(intval($_REQUEST['grado']) > 0) {
            $query = 'SELECT * FROM tbl_periodos WHERE col_grado="'.intval($_REQUEST['grado']).'" AND col_groupid="'.intval($groupid).'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $dataPeriodo = $sth->fetch(PDO::FETCH_OBJ);
            $periodoid = $dataPeriodo->col_id;
        }

        if(intval($_REQUEST['alumno']) > 0){
            if(!isset($_REQUEST['periodo']) || intval($_REQUEST['periodo']) == 0){
                $periodoid = getLastPeriodoAlumno(intval($_REQUEST['alumno']), $this->db);

            }
            $carrera = getCarreraByAlumno(intval($_REQUEST['alumno']), $this->db);
            $query = "SELECT a.* FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid WHERE t.col_alumnoid='".intval($_REQUEST[alumno])."' AND t.col_periodoid='".intval($periodoid)."'";
        }else if(intval($_REQUEST['carrera']) > 0 && intval($_REQUEST['alumno']) == 0){
            $carrera = getCarrera(intval($_REQUEST['carrera']), $this->db);
            $query = "SELECT a.* FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid WHERE a.col_carrera='".intval($_REQUEST[carrera])."' AND t.col_periodoid='".intval($periodoid)."'";
        }else{
            $query = "SELECT a.* FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid WHERE t.col_periodoid='".intval($periodoid)."'";
        }


        ob_start();
        ?>
        <table class="table calificaciones">
        <?php
        $sth = $this->db->prepare($query);
        $sth->execute();
        $alumnos = $sth->fetchAll();
        if($sth->rowCount() > 0){

            $i = 0;

            foreach($alumnos as $item){
                echo '<tr><td>';
                if(!$carrera) $carrera = getCarreraByMateria($item['col_carrera'], $this->db);
                $periodoData = getPeriodo($periodoid, $this->db, false);
                $periodoDataAlumno = getPeriodo($item['col_periodoid'], $this->db, false);

                $modalidades_alt = array(1=>'ldsem', 2=>'ldcua', 3=>'master', 4=>'docto');
                $modalidadPeriodo = $modalidades_alt[$carrera['modalidad_numero']];
                //$currentPeriodos = getCurrentPeriodos($this->db, $modalidadPeriodo);
/*
                $query = "SELECT c.*, m.col_nombre AS nombre_materia, m.col_clave AS clave_materia, mt.col_maestroid, CONCAT(u.col_firstname, ' ', u.col_lastname) AS nombre_maestro FROM tbl_calificaciones c ".
                "LEFT OUTER JOIN tbl_materias m ON m.col_clave=c.col_materia_clave AND m.col_carrera='".$carrera['carreraid']."' AND m.col_plan_estudios='".$periodoData->col_plan_estudios."' ".
                "LEFT OUTER JOIN tbl_maestros_taxonomia mt ON mt.col_periodoid IN (".implode(',', $currentPeriodos).") AND mt.col_materia_clave=c.col_materia_clave ".
                "LEFT OUTER JOIN tbl_users u ON u.col_id=mt.col_maestroid ".
                "WHERE c.col_periodoid IN (".implode(',', $currentPeriodos).") AND c.col_alumnoid='".intval($item['col_id'])."' GROUP BY c.col_materia_clave";

                $cal = $this->db->prepare($query);
                $cal->execute();
                $calificaciones = $cal->fetchAll();
*/
                // Regulares
                $query = "SELECT c.* FROM tbl_calificaciones c WHERE c.col_periodoid='".$periodoData->col_id."' AND c.col_materia_clave LIKE 'LD%' AND col_alumnoid='".intval($item['col_id'])."' AND col_estatus=1 GROUP BY col_materia_clave";
                if($userType == 'administrativo'){
                    $query = "SELECT c.* FROM tbl_calificaciones c WHERE c.col_periodoid='".$periodoData->col_id."' AND c.col_materia_clave LIKE 'LD%' AND col_alumnoid='".intval($item['col_id'])."' GROUP BY col_materia_clave ORDER BY col_materia_clave ASC";
                }
                // echo $query;exit;
                $cal = $this->db->prepare($query);
                $cal->execute();
                if($cal->rowCount() > 0) {
                        $calificacionesLD = $cal->fetchAll();
                }

                // Talleres Academias Club Transversal
                $query = "SELECT c.* FROM tbl_calificaciones c WHERE (c.col_periodoid='".$periodoData->col_id."' OR c.col_groupid='".$periodoData->col_groupid."') AND c.col_materia_clave NOT LIKE 'LD%' AND col_alumnoid='".intval($item['col_id'])."' AND col_estatus=1 GROUP BY col_materia_clave";
                if($userType == 'administrativo'){
                    $query = "SELECT c.* FROM tbl_calificaciones c WHERE (c.col_periodoid='".$periodoData->col_id."' OR c.col_groupid='".$periodoData->col_groupid."') AND c.col_materia_clave NOT LIKE 'LD%' AND col_alumnoid='".intval($item['col_id'])."' GROUP BY col_materia_clave ORDER BY col_materia_clave ASC";
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

                if($cal->rowCount() == 0 && $calNotLD->rowCount() > 0) $calificaciones = $calificacionesNotLD;



                $alumno = getAlumno('col_id', $item['col_id'], $this->db);
                if($carrera['tipo'] == 0 && $carrera['modalidad_numero'] == '2'){
                    ?>
                    <table class="alumno">
                        <thead>
                            <tr>
                                <th colspan="3">Alumno: <?php echo fixEncode($item['col_nombres'].' '.$item['col_apellidos']); ?></th>
                                <th colspan="4">Carrera: <?php echo fixEncode($carrera['nombre']); ?></th>
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
                }else if($carrera['tipo'] == 0 && $carrera['modalidad_numero'] == '1'){
                    ?>
                    <table class="table alumno">
                        <thead>
                            <tr>
                                <th colspan="4">Alumno: <?php echo fixEncode($item['col_nombres'].' '.$item['col_apellidos']); ?></th>
                                <th colspan="4">Carrera: <?php echo fixEncode($carrera['nombre']); ?></th>
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
                    <table class="alumno">
                        <thead>
                            <tr>
                                <th colspan="2">Alumno: <?php echo fixEncode($item['col_nombres'].' '.$item['col_apellidos']); ?></th>
                                <th colspan="1">Carrera: <?php echo fixEncode($carrera['nombre']); ?></th>
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

                foreach($calificaciones as $row){

                    if(in_array(strtoupper(substr($row['col_materia_clave'], 0, 2)), array('TL', 'CL', 'TR'))) {
                        if($row['col_p1'] != '') $row['col_p1'] = (intval($row['col_p1']) >= 7?'A':'NA');
                        if($row['col_p2'] != '') $row['col_p2'] = (intval($row['col_p2']) >= 7?'A':'NA');
                        if($row['col_ef'] != '') $row['col_ef'] = (intval($row['col_ef']) >= 7?'A':'NA');
                        if($row['col_cf'] != '') $row['col_cf'] = (intval($row['col_cf']) >= 7?'A':'NA');
                        if($row['col_ext'] != '') $row['col_ext'] = (intval($row['col_ext']) >= 7?'A':'NA');
                        if($row['col_ts'] != '') $row['col_ts'] = (intval($row['col_ts']) >= 7?'A':'NA');
                     }

                     if(in_array(strtoupper(substr($row['col_materia_clave'], 0, 2)), array('AC'))) {
                        if($row['col_p1'] != '') $row['col_p1'] = (intval($row['col_p1']) > 1?'A':'NA');
                        if($row['col_p2'] != '') $row['col_p2'] = (intval($row['col_p2']) > 1?'A':'NA');
                        if($row['col_ef'] != '') $row['col_ef'] = (intval($row['col_ef']) > 1?'A':'NA');
                        if($row['col_cf'] != '') $row['col_cf'] = (intval($row['col_cf']) > 1?'A':'NA');
                        if($row['col_ext'] != '') $row['col_ext'] = (intval($row['col_ext']) > 1?'A':'NA');
                        if($row['col_ts'] != '') $row['col_ts'] = (intval($row['col_ts']) > 1?'A':'NA');
                     }

                     $materia = getMateria('col_clave', $row['col_materia_clave'], $this->db, $row['col_periodoid'], $alumno->col_carrera);
                     if($materia->nombre_maestro == ''){
                        $materia->nombre_maestro = getMaestroByClaveMateria($row['col_materia_clave'], $alumno->col_id, $this->db);
                    }


                     $row['nombreMateria'] = fixEncode($materia->col_nombre);
                     $row['nombreMaestro'] = fixEncode($materia->nombre_maestro, true);

                     //if(trim($row['nombreMaestro']) == '') {
                    // if(substr($row['col_created_at'], 0, 10) !== '2019-01-17' && trim($row['nombreMaestro']) == ''){
                    //     $queryMaestro = 'SELECT * FROM tbl_users WHERE col_id="'.$row['col_created_by'].'" AND col_maestro=1';
                    //     $sthMaestro = $this->db->prepare($queryMaestro);
                    //     $sthMaestro->execute();
                    //     $dataMaestro = $sthMaestro->fetch(PDO::FETCH_OBJ);
                    //     $row['nombreMaestro'] = fixEncode($dataMaestro->col_firstname. ' '.$dataMaestro->col_lastname, true);
                    // }
                    // if(trim($row['nombreMaestro']) == '') $row['nombreMaestro'] = '-';


                    $queryMaestro = 'SELECT * FROM tbl_users WHERE col_id="'.$row['col_updated_by'].'" AND col_maestro=1';
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

                     //}

                    // $materia = getMateria('col_id', $row['col_materiaid'], $this->db, $row['col_periodoid']);
                    if($carrera['tipo'] == 0 && $carrera['modalidad_numero'] == '2'){
                        ?>
                        <tr>
                            <td><?php echo fixEncode($row['nombreMateria'])." (".$row['col_materia_clave'].")"; ?></td>
                            <td><?php echo fixEncode($row['nombreMaestro']); ?></td>
                            <td><?php echo $row['col_p1']; ?></td>
                            <td><?php echo $row['col_ef']; ?></td>
                            <td><?php echo $row['col_cf']; ?></td>
                            <td><?php echo $row['col_ext']; ?></td>
                            <td><?php echo $row['col_ts']; ?></td>
                        </tr>
                        <?php
                    }else if($carrera['tipo'] == 0 && $carrera['modalidad_numero'] == '1'){
                        ?>
                        <tr>
                            <td><?php echo fixEncode($row['nombreMateria'])." (".$row['col_materia_clave'].")"; ?></td>
                            <td><?php echo fixEncode($row['nombreMaestro']); ?></td>
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
                            <td><?php echo fixEncode($row['nombreMateria'])." (".$row['col_materia_clave'].")"; ?></td>
                            <td><?php echo fixEncode($row['nombreMaestro']); ?></td>
                            <td><?php echo $row['col_cf']; ?></td>
                        </tr>
                        <?php
                    }






                }
                echo '</tbody></table>';
                echo '</td></tr>';



                // $result[$i]['col_id'] = $materia;
                // $result[$i]['alumno'] = fixEncode($alumno->col_nombres.' '.$alumno->col_apellidos);
                // $result[$i]['maestro'] = fixEncode($materia->nombre_maestro);
                // $result[$i]['materia'] = fixEncode($materia->col_nombre);
                // $result[$i]['p1']  = $item['col_p1'];
                // $result[$i]['p2']  = $item['col_p2'];
                // $result[$i]['ef']  = $item['col_ef'];
                // $result[$i]['cf']  = $item['col_cf'];
                // $result[$i]['ext'] = $item['col_ext'];
                // $result[$i]['ts']  = $item['col_ts'];
                // $i++;
            }


        }else{
            ?>
            <tr>
                <td align="center" class="text-danger">Sin Resultados</td>
            </tr>
            <?php
        }
        ?>
        </table>
        <?php

        $html = ob_get_contents();
        ob_clean();

        $_response['data'] = $html;
        $_response['type'] = $tipoCarrera;
        return $this->response->withJson($_response);
    });

    $this->get('/listOld', function (Request $request, Response $response, array $args) {

        $currentPeriodo = getCurrentPeriodo($db);

        $queryAlumno = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval($_REQUEST['alumno']).'"';
        $sth = $this->db->prepare($queryAlumno);
        $sth->execute();
        $dataAlumno = $sth->fetch(PDO::FETCH_OBJ);
        $periodoDataAlumno = getPeriodo($dataAlumno->col_periodoid, $this->db, false);

        if(intval($_REQUEST[alumno]) > 0){

            $carrera = getCarrera(intval($_REQUEST[carrera]), $this->db);
            $query = "SELECT * FROM tbl_calificaciones WHERE col_alumnoid='".intval($_REQUEST[alumno])."' AND col_periodoid='".intval($periodoDataAlumno->col_id)."' GROUP BY col_alumnoid";

        }else if(intval($_REQUEST[carrera]) > 0 && intval($_REQUEST[alumno]) == 0){

            $carrera = getCarrera(intval($_REQUEST[carrera]), $this->db);

            $sth_mat = $this->db->prepare("SELECT * FROM tbl_materias WHERE col_carrera='".intval($_REQUEST[carrera])."' ORDER BY col_id");
            $sth_mat->execute();
            $materias = $sth_mat->fetchAll();
            foreach($materias as $item){
                $_materias[] = $item['col_id'];
                $_materias_clave[] = $item['col_clave'];
            }


            $query = "SELECT c.* FROM tbl_calificaciones c WHERE c.col_periodoid='".intval($periodoDataAlumno->col_id)."' AND c.col_materia_clave IN ('".implode(',', $_materias_clave)."') GROUP BY c.col_alumnoid ";
            // $query = "SELECT c.* FROM tbl_calificaciones c WHERE c.col_materia_clave IN (".implode(',', $_materias).") GROUP BY c.col_alumnoid";

        }else{

            $query = "SELECT c.* FROM tbl_calificaciones c WHERE c.col_periodoid='".intval($periodoDataAlumno->col_id)."' GROUP BY c.col_alumnoid";

        }

        ob_start();
        ?>
        <table class="table calificaciones">
        <?php
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        if($sth->rowCount() > 0){

            $i = 0;

            foreach($todos as $item){
                echo '<tr><td>';
                $query = "SELECT c.* FROM tbl_calificaciones c WHERE c.col_periodoid='".intval($item[col_periodoid])."' AND col_alumnoid='".intval($item['col_alumnoid'])."'";
                $cal = $this->db->prepare($query);
                $cal->execute();
                $calificaciones = $cal->fetchAll();

                $alumno = getAlumno('col_id', $item['col_alumnoid'], $this->db);
                $carrera = getCarreraByMateria($alumno->col_carrera, $this->db);
                if($carrera['tipo'] == 0 && $carrera['modalidad'] == 'Cuatrimestral'){
                    ?>
                    <table class="alumno">
                        <thead>
                            <tr>
                                <th colspan="3">Alumno: <?php echo fixEncode($alumno->col_nombres.' '.$alumno->col_apellidos); ?></th>
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
                    <table class="table alumno">
                        <thead>
                            <tr>
                                <th colspan="4">Alumno: <?php echo fixEncode($alumno->col_nombres.' '.$alumno->col_apellidos); ?></th>
                                <th colspan="4">Carrera: <?php echo fixEncode($carrera[nombre]); ?></th>
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
                    <table class="alumno">
                        <thead>
                            <tr>
                                <th colspan="2">Alumno: <?php echo fixEncode($alumno->col_nombres.' '.$alumno->col_apellidos); ?></th>
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
                foreach($calificaciones as $row){


                    $materia = getMateria('col_id', $row['col_materiaid'], $this->db, $row['col_periodoid']);
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






                }
                echo '</tbody></table>';
                echo '</td></tr>';



                // $result[$i]['col_id'] = $materia;
                // $result[$i]['alumno'] = fixEncode($alumno->col_nombres.' '.$alumno->col_apellidos);
                // $result[$i]['maestro'] = fixEncode($materia->nombre_maestro);
                // $result[$i]['materia'] = fixEncode($materia->col_nombre);
                // $result[$i]['p1']  = $item['col_p1'];
                // $result[$i]['p2']  = $item['col_p2'];
                // $result[$i]['ef']  = $item['col_ef'];
                // $result[$i]['cf']  = $item['col_cf'];
                // $result[$i]['ext'] = $item['col_ext'];
                // $result[$i]['ts']  = $item['col_ts'];
                // $i++;
            }


        }else{
            ?>
            <tr>
                <td align="center" class="text-danger">Sin Resultados</td>
            </tr>
            <?php
        }
        ?>
        </table>
        <?php

        $html = ob_get_contents();
        ob_clean();

        $_response['data'] = $html;
        $_response['type'] = $tipoCarrera;
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

        $query = 'UPDATE tbl_alumnos SET
        col_nombres="'.$input->nombres.'",
        col_apellidos="'.$input->apellidos.'",
        col_correo="'.$input->correo.'",
        col_fecha_nacimiento="'.substr($input->fechaNacimiento[0], 0, 10).'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$input->_userid.'" WHERE col_id="'.$input->id.'"';

        $dblog = new DBLog($query, 'tbl_alumnos', '', '', 'Alumnos', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);

        if($sth->execute()){
            $dblog->saveLog();
            $_response['status'] = 'true';
        }

        return $this->response->withJson($_response);

    });


    $this->post('/add', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $query = 'INSERT INTO tbl_materias (col_nombres, col_apellidos, col_correo, col_fecha_nacimiento, col_created_at, col_created_by, col_updated_at, col_updated_by)
        VALUES("'.$input->nombres.'", "'.$input->apellidos.'", "'.$input->correo.'", "'.$input->fechaNacimiento.'", "'.date("Y-m-d H:i:s").'", "'.$input->_userid.'", "'.date("Y-m-d H:i:s").'", "'.$input->_userid.'")';

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

        $query = 'DELETE FROM tbl_alumnos WHERE col_id="'.intval($_REQUEST['id']).'"';

        $dblog = new DBLog($query, 'tbl_alumnos', '', '', 'Alumnos', $this->db);
        $dblog->where = array('col_id' => intval($_REQUEST['id']));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();
        // $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->get('/listPeriodos', function (Request $request, Response $response, array $args) {

        $query = "SELECT p.* FROM tbl_calificaciones c LEFT OUTER JOIN tbl_periodos p ON p.col_id=c.col_periodoid GROUP BY p.col_nombre ORDER BY p.col_nombre ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['label'] = fixEncode($item['col_nombre']);
            $result[$i]['text'] = fixEncode($item['col_nombre']);
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listGrados', function (Request $request, Response $response, array $args) {


        $query = "SELECT * FROM tbl_periodos WHERE col_groupid='".getPeriodoTaxoID(intval($_REQUEST['periodo']), $this->db)."' GROUP BY col_grado ORDER BY col_grado ASC";
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

    $this->get('/listGrupos', function (Request $request, Response $response, array $args) {


        $query = "SELECT * FROM tbl_periodos WHERE col_groupid='".getPeriodoTaxoID(intval($_REQUEST['periodo']), $this->db)."' AND col_grado='".intval($_REQUEST['grado'])."' GROUP BY col_grupo ORDER BY col_grupo ASC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){
            $result[$i]['value'] = $item['col_grupo'];
            $result[$i]['label'] = fixEncode($item['col_grupo']);
            $result[$i]['text'] = fixEncode($item['col_grupo']);
            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->get('/listCarreras', function (Request $request, Response $response, array $args) {

        //$groupid = getPeriodoTaxoID(intval($_REQUEST[periodo]), $this->db);
//
        //$query = 'SELECT * FROM tbl_periodos WHERE col_grado="'.intval($_REQUEST['grado']).'" AND col_grupo="'.trim($_REQUEST['grupo']).'" AND col_groupid="'.intval($groupid).'"';
        //$sth = $this->db->prepare($query);
        //$sth->execute();
        //$data = $sth->fetch(PDO::FETCH_OBJ);

        $querym = 'SELECT * FROM tbl_materias WHERE col_semestre="'.intval($_REQUEST['grado']).'" GROUP BY col_carrera';
        $sthm = $this->db->prepare($querym);
        $sthm->execute();
        $materias = $sthm->fetchAll();
        foreach($materias as $materia){
            if(intval($materia['col_carrera']) > 0) $carreras[] = $materia['col_carrera'];
        }

        $queryc = "SELECT * FROM tbl_carreras WHERE col_id IN (".implode(',', $carreras).") ORDER BY col_id";
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

    $this->get('/listAlumnos', function (Request $request, Response $response, array $args) {
        $groupid = getPeriodoTaxoID(intval($_REQUEST[periodo]), $this->db);

        $query = 'SELECT * FROM tbl_periodos WHERE col_grado="'.intval($_REQUEST['grado']).'" AND col_grupo="'.trim($_REQUEST['grupo']).'" AND col_groupid="'.intval($groupid).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $periodoid = $data->col_id;

        $sth_taxo = $this->db->prepare("SELECT * FROM tbl_alumnos_taxonomia WHERE col_periodoid='".intval($periodoid)."' ORDER BY col_id ASC");
        $sth_taxo->execute();
        $data_taxo = $sth_taxo->fetchAll();
        foreach($data_taxo as $item_taxo){
            $alumnos[] = $item_taxo['col_alumnoid'];
        }

        $query = "SELECT a.*, IF(c.col_modalidad = 0, 'Semestral', 'Cuatrimestral') as modalidad, CONCAT(p.col_grado, '-', p.col_grupo) as grado FROM tbl_alumnos a ".
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
            $nombre = $item['col_nombres']." ".$item['col_apellidos'];
            $result[$i]['value'] = $item['col_id'];
            $result[$i]['info'] = $item['modalidad'].', '.$item['grado'];
            $result[$i]['label'] = (preg_match('//u', $nombre) == 0?utf8_encode($nombre):$nombre);
            $result[$i]['text'] = (preg_match('//u', $nombre) == 0?utf8_encode($nombre):$nombre);
            $i++;
        }

        return $this->response->withJson($result);

    });

});
