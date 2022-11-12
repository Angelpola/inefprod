<?php

/**
 *
 * Este archivo incluye las funciones que fueron utilizadas para la importación de archivos Excel (bases de datos), mismos que fueron enviados
 * al inicio de proceso de captura, anterior a la puesta en funcionamiento de la plataforma
 *
 * /administracion
 *  - /import
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/administracion', function () {

    $this->post('/import', function (Request $request, Response $response, $args) {

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());


        if($input->archivoCalificaciones->filename != ''){
            $_response['archivoNombre'] = $input->archivoCalificaciones->filename;
             $file = base64_decode($input->archivoCalificaciones->value);
             // $sthr = $this->db->prepare('TRUNCATE TABLE tbl_calificaciones');
             // $sthr->execute();

            $lines = explode("\n", $file);
            $add = 0;
            $not_add = 0;
            $i = 0;

            $periodoid= 0;
            $lastperiodo = '';
            $missing_periodos = 0;
            $missing_alumno = 0;

            $lastmateria = '';
            $materiaid = '';

            foreach($lines as $line){
                $cols = explode("\t", $line);

                        //Periodo (Variable)
                        if(strtolower(str_replace(array(' ', ':'), array('-', ''), trim($cols[1]))) == 'periodo-escolar' ){
                            $lastperiodo = trim($cols[2]);
                        }
                        if(strtolower(str_replace(array(' ', ':'), array('-', ''), trim($cols[1]))) == 'semestre-y-grupo' ){
                            if(trim($cols[3]) == '') $cols[3] = 'A';
                            $query_periodo = 'SELECT * FROM tbl_periodos WHERE col_nombre LIKE "%'.formatNombrePeriodo($lastperiodo).'%" AND col_grado="'.intval($cols[2]).'" AND col_grupo="'.trim($cols[3]).'"';

                            $sthperiodo = $this->db->prepare($query_periodo);
                            $sthperiodo->execute();
                            if($sthperiodo->rowCount() > 0){
                                $periodo_data = $sthperiodo->fetch(PDO::FETCH_OBJ);
                                $periodoid = $periodo_data->col_id;
                                $periodo_groupid = $periodo_data->col_groupid;
                            }else{
                                $missing_periodos++;
                                $_response['debug_query_periodo'][] = $query_periodo;
                            }

                        }
                        if(strpos($cols[2], '@') !== false ){
                            $cols[2] = filter_var(trim($cols[2]), FILTER_SANITIZE_EMAIL);
                        }
                        // if(strpos('@', $cols[2]) !== false ){
                        //     $cols[2] = filter_var(trim($cols[2]), FILTER_SANITIZE_EMAIL);
                        // }

                        if (filter_var(trim($cols[2]), FILTER_VALIDATE_EMAIL) && $periodoid > 0) {
                            //Alumno

                            $query_alumno = 'SELECT * FROM tbl_alumnos WHERE LOWER(col_correo) LIKE "%'.filter_var(trim($cols[2]), FILTER_SANITIZE_EMAIL).'%"';
                            $obj_alumno = $this->db->prepare($query_alumno);
                            $obj_alumno->execute();
                            $alumno = $obj_alumno->fetch(PDO::FETCH_OBJ);
                            $alumnoid = $alumno->col_id;
                            if(intval($alumnoid) > 0){


                                $query_taxo = 'SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$periodoid.'"';
                                $sth_taxo = $this->db->prepare($query_taxo);
                                $sth_taxo->execute();
                                if($sth_taxo->rowCount() == 0){
                                    //$periodoid = 0;
                                    $add_taxto = 'INSERT INTO tbl_alumnos_taxonomia (col_alumnoid, col_periodoid, col_groupid)
                                    VALUES("'.$alumnoid.'", "'.$periodoid.'", "'.getPeriodoTaxoID($periodoid, $this->db).'")';
                                    $sth_add_taxo = $this->db->prepare($add_taxto);
                                    $sth_add_taxo->execute();
                                }

                                $query_check_exist = 'SELECT * FROM tbl_calificaciones WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$periodoid.'" AND col_materia_clave="'.trim($cols[3]).'" ';
                                $_response['debug_exec'][] = $query_check_exist;
                                $exist = $this->db->prepare($query_check_exist);
                                $exist->execute();
                                if($exist->rowCount() == 0){

                                    $data = array(
                                        "col_alumnoid" => $alumnoid,
                                        "col_periodoid" => $periodoid,
                                        "col_groupid" => $periodo_groupid,
                                        "col_materia_clave" => trim($cols[3]),
                                        "col_p1" => $cols[4],
                                        "col_ef" => $cols[5],
                                        "col_cf" => $cols[6],
                                        "col_ext" => $cols[7],
                                        "col_ts" => $cols[8],
                                        "col_estatus" => 1,
                                        "col_observaciones" => '',
                                        "col_created_at" => date("Y-m-d H:i:s"),
                                        "col_created_by" => $input->userid,
                                        "col_updated_at" => date("Y-m-d H:i:s"),
                                        "col_updated_by" => $input->userid,
                                    );


                                    $query = 'INSERT INTO tbl_calificaciones ('.implode(",", array_keys($data)).')
                                    VALUES("'.implode('", "', array_values($data)).'")';
                                    $sth = $this->db->prepare($query);
                                    // $add++;
                                    $_response['calificaciones_query_add'][] = $query;
                                    if($sth->execute()){
                                        $add++;
                                    }
                                }
                             } else {
                                 $missing_alumno++;
                                 $missing_alumno_query[] = $cols[2];
                             }
                        }else{
                            $not_add++;
                            $_response['calificaciones_qper'][] = $query_periodo;
                        }

                $i++;
            }

            $_response['calificaciones_agregadas'] = $add;
            $_response['calificaciones_no_agregadas'] = $not_add;
            $_response['calificaciones_sin_periodos'] = $missing_periodos;
            $_response['calificaciones_sin_periodos_query'] = $missing_periodos_query;
            $_response['calificaciones_sin_alumno'] = $missing_alumno;
            $_response['calificaciones_sin_alumno_query'] = array_unique($missing_alumno_query);
        }

        if($input->archivoCalificacionesSemestral->filename != ''){
            $_response['archivoNombre'] = $input->archivoCalificacionesSemestral->filename;
            $file = base64_decode($input->archivoCalificacionesSemestral->value);
            // $sthr = $this->db->prepare('TRUNCATE TABLE tbl_calificaciones');
            // $sthr->execute();

           $lines = explode("\n", $file);
           $add = 0;
           $not_add = 0;
           $i = 0;

           $periodoid= 0;
           $lastperiodo = '';
           $missing_periodos = 0;
           $missing_alumno = 0;

           $lastmateria = '';
           $materiaid = '';

           foreach($lines as $line){
               $cols = explode("\t", $line);

                       //Periodo (Variable)
                       if(strtolower(str_replace(array(' ', ':'), array('-', ''), trim($cols[1]))) == 'periodo-escolar' ){
                           $lastperiodo = trim($cols[2]);
                       }
                       if(strtolower(str_replace(array(' ', ':'), array('-', ''), trim($cols[1]))) == 'semestre-y-grupo' ){
                           if(trim($cols[3]) == '') $cols[3] = 'A';
                           $query_periodo = 'SELECT * FROM tbl_periodos WHERE col_nombre="'.formatNombrePeriodo($lastperiodo).'" AND col_grado="'.intval($cols[2]).'" AND col_grupo="'.trim($cols[3]).'"';
                           $sthperiodo = $this->db->prepare($query_periodo);
                           $sthperiodo->execute();
                           if($sthperiodo->rowCount() > 0){
                               $periodo_data = $sthperiodo->fetch(PDO::FETCH_OBJ);
                               $periodoid = $periodo_data->col_id;
                               $periodo_groupid = $periodo_data->col_groupid;
                           }else{
                               $missing_periodos++;
                           }

                       }
                       if (filter_var(trim($cols[2]), FILTER_VALIDATE_EMAIL) && $periodoid > 0) {
                           //Alumno
                           $query_alumno = 'SELECT * FROM tbl_alumnos WHERE LOWER(col_correo) LIKE "%'.filter_var(trim($cols[2]), FILTER_SANITIZE_EMAIL).'%"';
                           $obj_alumno = $this->db->prepare($query_alumno);
                           $obj_alumno->execute();
                           $alumno = $obj_alumno->fetch(PDO::FETCH_OBJ);
                           $alumnoid = $alumno->col_id;
                           if(intval($alumnoid) > 0){

                               $query_taxo = 'SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$periodoid.'"';
                               $sth_taxo = $this->db->prepare($query_taxo);
                               $sth_taxo->execute();
                               if($sth_taxo->rowCount() == 0){
                                   //$periodoid = 0;
                                   $add_taxto = 'INSERT INTO tbl_alumnos_taxonomia (col_alumnoid, col_periodoid, col_groupid)
                                   VALUES("'.$alumnoid.'", "'.$periodoid.'", "'.getPeriodoTaxoID($periodoid, $this->db).'")';
                                   $sth_add_taxo = $this->db->prepare($add_taxto);
                                   $sth_add_taxo->execute();
                               }

                               $query_check_exist = 'SELECT * FROM tbl_calificaciones WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$periodoid.'" AND col_materia_clave="'.trim($cols[3]).'" ';
                               $exist = $this->db->prepare($query_check_exist);
                               $exist->execute();
                               if($exist->rowCount() == 0){

                                   $data = array(
                                       "col_alumnoid" => $alumnoid,
                                       "col_periodoid" => $periodoid,
                                       "col_groupid" => $periodo_groupid,
                                       "col_materia_clave" => trim($cols[3]),
                                       "col_p1" => $cols[4],
                                       "col_p2" => $cols[5],
                                       "col_ef" => $cols[6],
                                       "col_cf" => $cols[7],
                                       "col_ext" => $cols[8],
                                       "col_ts" => $cols[9],
                                       "col_estatus" => 1,
                                       "col_observaciones" => '',
                                       "col_created_at" => date("Y-m-d H:i:s"),
                                       "col_created_by" => $input->userid,
                                       "col_updated_at" => date("Y-m-d H:i:s"),
                                       "col_updated_by" => $input->userid,
                                   );


                                   $query = 'INSERT INTO tbl_calificaciones ('.implode(",", array_keys($data)).')
                                   VALUES("'.implode('", "', array_values($data)).'")';
                                   $sth = $this->db->prepare($query);
                                   $_response['calificaciones_query_add'][] = $query;
                                   if($sth->execute()){
                                       $add++;
                                   }
                               }
                            } else {
                                $missing_alumno++;
                                $missing_alumno_query[] = $cols[2];
                            }
                       }else{
                           $not_add++;
                           $_response['calificaciones_qper'][] = $query_periodo;
                       }

               $i++;
           }

            $_response['calificaciones_agregadas'] = $add;
            $_response['calificaciones_no_agregadas'] = $not_add;
            $_response['calificaciones_sin_periodos'] = $missing_periodos;
            $_response['calificaciones_sin_periodos_query'] = $missing_periodos_query;
            $_response['calificaciones_sin_alumno'] = $missing_alumno;
            $_response['calificaciones_sin_alumno_query'] = array_unique($missing_alumno_query);
        }

        if($input->archivoCalificacionesPostgrados->filename != ''){
            $_response['archivoNombre'] = $input->archivoCalificacionesPostgrados->filename;
            $file = base64_decode($input->archivoCalificacionesPostgrados->value);
            // $sthr = $this->db->prepare('TRUNCATE TABLE tbl_calificaciones');
            // $sthr->execute();

           $lines = explode("\n", $file);
           $add = 0;
           $not_add = 0;
           $i = 0;

           $periodoid= 0;
           $lastperiodo = '';
           $missing_periodos = 0;
           $missing_alumno = 0;

           $lastmateria = '';
           $materiaid = '';

           foreach($lines as $line){
               $cols = explode("\t", $line);

                       //Periodo (Variable)
                       if(strtolower(str_replace(array(' ', ':'), array('-', ''), trim($cols[1]))) == 'periodo-escolar' ){
                           $lastperiodo = trim($cols[2]);
                       }
                       if(strtolower(str_replace(array(' ', ':'), array('-', ''), trim($cols[1]))) == 'semestre-y-grupo' ){
                           if(trim($cols[3]) == '') $cols[3] = 'A';
                           $query_periodo = 'SELECT * FROM tbl_periodos WHERE col_nombre="'.formatNombrePeriodo($lastperiodo).'" AND col_grado="'.intval($cols[2]).'" AND col_grupo="'.trim($cols[3]).'"';
                           $sthperiodo = $this->db->prepare($query_periodo);
                           $sthperiodo->execute();
                           if($sthperiodo->rowCount() > 0){
                               $periodo_data = $sthperiodo->fetch(PDO::FETCH_OBJ);
                               $periodoid = $periodo_data->col_id;
                               $periodo_groupid = $periodo_data->col_groupid;
                           }else{
                               $missing_periodos++;
                           }

                       }
                       if (filter_var(trim($cols[2]), FILTER_VALIDATE_EMAIL) && $periodoid > 0) {
                           //Alumno
                           $query_alumno = 'SELECT * FROM tbl_alumnos WHERE LOWER(col_correo) LIKE "%'.filter_var(trim($cols[2]), FILTER_SANITIZE_EMAIL).'%"';
                           $obj_alumno = $this->db->prepare($query_alumno);
                           $obj_alumno->execute();
                           $alumno = $obj_alumno->fetch(PDO::FETCH_OBJ);
                           $alumnoid = $alumno->col_id;
                           if(intval($alumnoid) > 0){

                               $query_taxo = 'SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$periodoid.'"';
                               $sth_taxo = $this->db->prepare($query_taxo);
                               $sth_taxo->execute();
                               if($sth_taxo->rowCount() == 0){
                                   //$periodoid = 0;
                                   $add_taxto = 'INSERT INTO tbl_alumnos_taxonomia (col_alumnoid, col_periodoid, col_groupid)
                                   VALUES("'.$alumnoid.'", "'.$periodoid.'", "'.getPeriodoTaxoID($periodoid, $this->db).'")';
                                   $sth_add_taxo = $this->db->prepare($add_taxto);
                                   $sth_add_taxo->execute();
                               }

                               $cols[3] = strtoupper(preg_replace("/[^A-Za-z0-9]/", '', trim($cols[3])));

                               $query_check_exist = 'SELECT * FROM tbl_calificaciones WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$periodoid.'" AND col_materia_clave="'.trim($cols[3]).'" ';
                               $exist = $this->db->prepare($query_check_exist);
                               $exist->execute();
                               if($exist->rowCount() == 0){

                                   $data = array(
                                       "col_alumnoid" => intval($alumnoid),
                                       "col_periodoid" => intval($periodoid),
                                       "col_groupid" => intval($periodo_groupid),
                                       "col_materia_clave" => trim($cols[3]),
                                       "col_p1" => '',
                                       "col_p2" => '',
                                       "col_ef" => '',
                                       "col_cf" => trim($cols[4]),
                                       "col_ext" => '',
                                       "col_ts" => '',
                                       "col_observaciones" => '',
                                       "col_estatus" => 1,
                                       "col_created_at" => date("Y-m-d H:i:s"),
                                       "col_created_by" => $input->userid,
                                       "col_updated_at" => date("Y-m-d H:i:s"),
                                       "col_updated_by" => $input->userid,
                                   );

                                   $_response['debug'][] = $cols;
                                   $query = 'INSERT INTO tbl_calificaciones ('.implode(",", array_keys($data)).')
                                   VALUES("'.implode('", "', array_values($data)).'")';
                                   $sth = $this->db->prepare($query);
                                   $_response['calificaciones_query_add'][] = $query;
                                   if($sth->execute()){
                                       $add++;
                                   }
                               }
                            } else {
                                $missing_alumno++;
                                $missing_alumno_query[] = $cols[2];
                            }
                       }else{
                           $not_add++;
                           $_response['calificaciones_qper'][] = $query_periodo;
                       }

               $i++;
           }

            $_response['calificaciones_postgrados_agregadas'] = $add;
            $_response['calificaciones_postgrados_no_agregadas'] = $not_add;
            $_response['calificaciones_postgrados_sin_periodos'] = $missing_periodos;
            $_response['calificaciones_postgrados_sin_periodos_query'] = $missing_periodos_query;
            $_response['calificaciones_postgrados_sin_alumno'] = $missing_alumno;
            $_response['calificaciones_postgrados_sin_alumno_query'] = array_unique($missing_alumno_query);
        }


        if($input->archivoMateriasMaestros->filename != ''){
            $file = base64_decode($input->archivoMateriasMaestros->value);
            $sthr = $this->db->prepare('TRUNCATE TABLE tbl_maestros_taxonomia');
            $sthr->execute();

            $add = 0;
            $lines = explode("\n", $file);

            foreach($lines as $line){
                    $cols = explode("\t", $line);

                    if (filter_var(trim($cols[6]), FILTER_VALIDATE_EMAIL)) {
                        // $_response['materias_maestros_ag'][] = $cols[6];

                        $query_maestro = 'SELECT * FROM tbl_users WHERE LOWER(col_email) LIKE "%'.trim($cols[6]).'%"';
                        $obj_maestro = $this->db->prepare($query_maestro);
                        $obj_maestro->execute();
                        $maestro = $obj_maestro->fetch(PDO::FETCH_OBJ);
                        $maestroid = $maestro->col_id;

                        if(intval($maestroid) > 0) {
                            $query_materia = 'SELECT * FROM tbl_materias WHERE LOWER(col_clave)= "'.strtolower(formatClave($cols[1])).'"';
                            $obj_materia = $this->db->prepare($query_materia);
                            $obj_materia->execute();
                            $materia = $obj_materia->fetch(PDO::FETCH_OBJ);
                            $materiaid = $materia->col_id;

                            if(trim($cols[5]) == '') $cols[5] = 'A';

                            $query_periodo = 'SELECT * FROM tbl_periodos WHERE LOWER(col_nombre)= "'.strtolower(formatNombrePeriodo($cols[3])).'" AND col_grado="'.trim($cols[4]).'" AND col_grupo="'.trim($cols[5]).'"';
                            $obj_periodo = $this->db->prepare($query_periodo);
                            $obj_periodo->execute();
                            $periodo = $obj_periodo->fetch(PDO::FETCH_OBJ);
                            $periodoid = $periodo->col_id;
                            // if(intval($periodoid) == 0) {
                            //     $add_periodo = 'INSERT INTO tbl_periodos (col_nombre, col_grado, col_grupo, col_created_at, col_created_by, col_updated_at, col_updated_by)
                            //     VALUES("'.trim($cols[3]).'", "'.$cols[4].'", "'.$cols[5].'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'")';
                            //     $sth_addperiodo = $this->db->prepare($add_periodo);
                            //     $sth_addperiodo->execute();
                            //     $periodoid = $this->db->lastInsertId();
                            // }

                            if($materiaid == 0){
                                $_response['materias_maestros_agregadas'][] = $cols[3];
                            }

                            $data = array(
                                "col_maestroid" => $maestroid,
                                "col_materia_clave" => $cols[1],
                                "col_periodoid" => $periodoid,
                                "col_created_at" => date("Y-m-d H:i:s"),
                                "col_created_by" => $input->userid,
                                "col_updated_at" => date("Y-m-d H:i:s"),
                                "col_updated_by" => $input->userid,
                            );


                            $query = 'INSERT INTO tbl_maestros_taxonomia ('.implode(",", array_keys($data)).')
                            VALUES("'.implode('", "', array_values($data)).'")';
                            $sth = $this->db->prepare($query);
                            if($sth->execute()){
                                $add++;
                            }

                        }
                    }
                $i++;
            }

            //$_response['materias_maestros_agregadas_name'] = $input->archivoMateriasMaestros->value;
            $_response['materias_maestros_agregadas'] = $add;
        }


        if($input->archivoDocumentos->filename != ''){
            $file = base64_decode($input->archivoDocumentos->value);
            // $sthr = $this->db->prepare('TRUNCATE TABLE tbl_documentos');
            // $sthr->execute();

            $lines = explode("\n", $file);
            $add = 0;
            $i = 0;
            $relColumns = array();
            foreach($lines as $line){
                if($i == 0){
                    $hcols = explode("\t", $line);
                    $h = 0;
                    foreach($hcols as $v){
                        if($h > 5 && $h < 44){
                            if($v != ''){

                                $ask_query = 'SELECT * FROM tbl_documentos WHERE col_nombre="'.$v.'"';
                                $askq = $this->db->prepare($ask_query);
                                $askq->execute();
                                $askq_data = $askq->fetch(PDO::FETCH_OBJ);
                                if(intval($askq_data->col_id) == 0){

                                    $data = array(
                                        "col_nombre" => $v,
                                        "col_alumnos" => 1,
                                        "col_created_at" => date("Y-m-d H:i:s"),
                                        "col_created_by" => $input->userid,
                                        "col_updated_at" => date("Y-m-d H:i:s"),
                                        "col_updated_by" => $input->userid,
                                    );

                                    $query = 'INSERT INTO tbl_documentos ('.implode(",", array_keys($data)).')
                                    VALUES("'.implode('", "', array_values($data)).'")';
                                    $docq = $this->db->prepare($query);
                                    $docq->execute();

                                    $relColumns[$h] = $this->db->lastInsertId();
                                }else{
                                    $relColumns[$h] = $askq_data->col_id;
                                }
                            }
                        }
                        $h++;
                    }
                    //$_response['relColumns'] = $relColumns;
                }

                $docsData = array();
                if($i > 0){
                    $dcols = explode("\t", $line);
                    $ch = 0;
                    $alumnoid = 0;
                    $ask_alu_query = 'SELECT * FROM tbl_alumnos WHERE col_correo = "'.filter_var(trim($dcols[3]), FILTER_SANITIZE_EMAIL).'"';
                    $ask_aluq = $this->db->prepare($ask_alu_query);
                    $ask_aluq->execute();
                    if($ask_aluq->rowCount() == 0){
                        if(filter_var(trim($dcols[3]), FILTER_SANITIZE_EMAIL)) $_response['missing_alumnos'][] = $dcols[3];
                    }
                    $ask_aluq_data = $ask_aluq->fetch(PDO::FETCH_OBJ);
                    $alumnoid = $ask_aluq_data->col_id;

                    foreach($dcols as $col){
                        if($ch > 5 && $ch < 44){
                            $docsData[$relColumns[$ch]] = $col;
                        }
                        $ch++;
                    }

                    $rel[] = array('id' => $alumnoid, 'correo' => filter_var(trim($dcols[3]), FILTER_SANITIZE_EMAIL));


                        $query = 'UPDATE tbl_alumnos SET '.
                        'col_documentos="'.base64_encode(serialize($docsData)).'", '.
                        'col_updated_at="'.date("Y-m-d H:i:s").'", '.
                        'col_updated_by="'.$input->userid.'" '.
                        'WHERE col_id="'.$alumnoid.'"';

                        $sth = $this->db->prepare($query);
                        if($sth->execute()){
                            $add++;
                        }
                        //$_response['docsColumns'][] = $docsData;
                        unset($docsData);
                    }

                $i++;
            }

            // $_response['documentos_agregados_rel'] = $rel;
            $_response['documentos_agregados'] = $add;
        }
        if($input->archivoAlumnos->filename != ''){
            $_response['archivoNombre'] = $input->archivoAlumnos->filename;

            // $sthr = $this->db->prepare('TRUNCATE TABLE tbl_alumnos');
            // $sthr->execute();
            // $sthrt = $this->db->prepare('TRUNCATE TABLE tbl_alumnos_taxonomia');
            // $sthrt->execute();
            // $sth_p = $this->db->prepare('TRUNCATE TABLE tbl_periodos');
            // $sth_p->execute();
            // $sth_p = $this->db->prepare('TRUNCATE TABLE tbl_periodos_nombres');
            // $sth_p->execute();


            $file = base64_decode($input->archivoAlumnos->value);

            $lines = explode("\n", $file);
            $add = 0;
            $i = 0;
            $mail = 0;
            $up = 0;
            $in = 0;
            foreach($lines as $line){
                if($i > 0){
                    $cols = explode("\t", $line);
                    // if(trim($cols[0]) != '' && in_array(trim($cols[0]), array('avelazquez@fldch.com', 'gerardomartinez@fldch.edu.mx')) == false){
                    if(trim($cols[0]) != ''){

                        $generacion = explode('-', $cols[12]); //Generacion
                        $egresado = 0; //$cols[42]

                        if(trim(strtolower($cols[42])) == 'si'){
                            $egresado = 1;
                        }


                        $queryc = 'SELECT * FROM tbl_carreras WHERE LOWER(col_revoe)="'.strtolower(trim($cols[40])).'"';
                        $sthc = $this->db->prepare($queryc);
                        $sthc->execute();
                        $carrera_obj = $sthc->fetch(PDO::FETCH_OBJ);
                        $carrera = $carrera_obj->col_id;

                        $planestudiosid = getPlanEstudios('col_nombre', formatPlanEstudios($cols[44]), $this->db, 'col_id');

                        if(trim($cols[11]) != ''){
                            $query_periodo = 'SELECT * FROM tbl_periodos WHERE col_nombre="'.formatNombrePeriodo($cols[11]).'" AND col_grado="'.$cols[13].'" AND col_grupo="'.$cols[14].'"';
                            $sthperiodo = $this->db->prepare($query_periodo);
                            $sthperiodo->execute();
                            if($sthperiodo->rowCount() > 0){
                                $periodo_data = $sthperiodo->fetch(PDO::FETCH_OBJ);
                                $periodoid = $periodo_data->col_id;
                            }else{

                                $query_group_periodo = 'SELECT * FROM tbl_periodos_nombres WHERE LOWER(col_nombre)="'.strtolower(formatNombrePeriodo($cols[11])).'"';
                                $sthperiodo_group = $this->db->prepare($query_group_periodo);
                                $sthperiodo_group->execute();
                                if($sthperiodo_group->rowCount() > 0){
                                    $periodo_group_data = $sthperiodo_group->fetch(PDO::FETCH_OBJ);
                                    $groupid = $periodo_group_data->col_id;
                                }else{
                                    $add_periodo_group = 'INSERT INTO tbl_periodos_nombres (col_nombre, col_created_at, col_updated_at)
                                    VALUES("'.formatNombrePeriodo($cols[11]).'", "'.date("Y-m-d H:i:s").'", "'.date("Y-m-d H:i:s").'")';
                                    $sth_addperiodo_group = $this->db->prepare($add_periodo_group);
                                    $sth_addperiodo_group->execute();
                                    $groupid = $this->db->lastInsertId();
                                }


                                $add_periodo = 'INSERT INTO tbl_periodos (col_groupid, col_nombre, col_grado, col_grupo, col_carreraid, col_plan_estudios, col_created_at, col_created_by, col_updated_at, col_updated_by)
                                VALUES("'.$groupid.'", "'.formatNombrePeriodo($cols[11]).'", "'.$cols[13].'", "'.$cols[14].'", "'.$carrera.'", "'.$planestudiosid.'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'")';
                                $sth_addperiodo = $this->db->prepare($add_periodo);
                                $sth_addperiodo->execute();
                                $periodoid = $this->db->lastInsertId();
                                if($carrera == 0 || $cols[14] == '') {
                                    echo "<br/><br/>";
                                    if($carrera == 0) echo 'No hay carrera variable';
                                    echo "<br/><br/>";
                                    if($cols[13] == 0) echo 'No hay carrera grado';
                                    echo "<br/><br/>";
                                    if($cols[14] == '') echo 'No hay carrera grupo';
                                    echo "<br/><br/>";
                                    print_r($cols);
                                    echo "<br/><br/>";
                                    echo $queryc;
                                    echo "<br/><br/>";
                                    echo $add_periodo;
                                    echo "<br/><br/>";
                                    echo $periodoid;
                                    continue;
                                }
                            }
                        }else{
                            $periodoid = 0;
                        }

                        $query_check_alumno = 'SELECT * FROM tbl_alumnos WHERE col_correo LIKE "%'.filter_var(trim($cols[0]), FILTER_SANITIZE_EMAIL).'%"';
                        $objCheckAlumno = $this->db->prepare($query_check_alumno);
                        $objCheckAlumno->execute();
                        $dataCheckAlumno = $objCheckAlumno->fetch(PDO::FETCH_OBJ);
                        $alumnoID = $dataCheckAlumno->col_id;
                        if($objCheckAlumno->rowCount() == 0){
                            $in++;
                            $data = array(
                                "col_correo" => filter_var(trim($cols[0]), FILTER_SANITIZE_EMAIL),
                                "col_password" => md5($cols[1]),
                                "col_control" => $cols[2],
                                "col_cedula" => trim($cols[45]),
                                "col_apellidos" => $cols[3],
                                "col_nombres" => $cols[4],
                                "col_fecha_nacimiento" => (trim($cols[5]) == ''?'0000-00-00':date('Y-m-d', strtotime($cols[5]))),
                                "col_telefono" => (trim($cols[6]) == ''?'-':trim($cols[6])),
                                "col_celular" => $cols[7],
                                "col_correo_personal" => (trim($cols[8]) == ''?filter_var(trim($cols[0]), FILTER_SANITIZE_EMAIL):filter_var($cols[8], FILTER_SANITIZE_EMAIL)),
                                "col_genero" => (trim($cols[9]) == ''?'H':trim($cols[9])),
                                "col_sangre" => $cols[10],
                                "col_periodoid" => $periodoid, //$cols[11] $cols[13] $cols[14]
                                "col_generacion_start" => $generacion[0],//$cols[12]
                                "col_generacion_end" => $generacion[1],//$cols[12]
                                "col_direccion" => $cols[15],
                                "col_ciudad" => $cols[16],
                                "col_estado" => $cols[17],
                                "col_pais" => $cols[18], // S
                                "col_rep_nombres" => $cols[19],
                                "col_rep_apellidos" => $cols[20],
                                "col_rep_telefono" => $cols[21],
                                "col_rep_celular" => $cols[22],
                                "col_rep_correo" => $cols[23],
                                "col_rep_empresa" => $cols[24],
                                "col_rep_empresa_telefono" => $cols[25],
                                "col_rep_parentesco" => $cols[26],
                                "col_rep_ciudad" => $cols[27],
                                "col_rep_estado" => $cols[28],
                                "col_rep_pais" => $cols[29], // AD
                                "col_enfermedades" => $cols[30],
                                "col_proce_prepa" => $cols[31],
                                "col_proce_prepa_promedio" => $cols[32],
                                "col_proce_universidad_lic" => $cols[33],
                                "col_proce_licenciatura" => $cols[34],
                                "col_proce_universidad_master" => $cols[35],
                                "col_proce_maestria" => $cols[36], // AK
                                "col_seguro_folio" => $cols[37],
                                "col_trabajo" => $cols[38],
                                "col_cargo_trabajo" => $cols[39],
                                "col_carrera" => $carrera, //$cols[40] // AO // Revoe
                                "col_fecha_ingreso" => '0000-00-00',
                                "col_documentos" => '',
                                "col_seguro" => '',
                                "col_egresado" => $egresado, // Col 42
                                "col_tipo_seguro" => '',
                                "col_referencia" => trim($cols[43]),
                                "col_plan_estudios" => $planestudiosid,
                                "col_estatus" => trim($cols[46]),
                                "col_created_at" => date("Y-m-d H:i:s"),
                                "col_created_by" => $input->userid,
                                "col_updated_at" => date("Y-m-d H:i:s"),
                                "col_updated_by" => $input->userid
                            );

                            $query = 'INSERT INTO tbl_alumnos ('.implode(",", array_keys($data)).')
                            VALUES("'.implode('", "', array_values($data)).'")';
                            //$_response['query'][] = $query;
                            $sth = $this->db->prepare($query);
                            if($sth->execute()){
                                $add++;
                            }
                            $alumnoID = $this->db->lastInsertId();
                            $query_taxo = 'INSERT INTO tbl_alumnos_taxonomia (col_alumnoid, col_periodoid, col_groupid, col_status)
                            VALUES("'.$this->db->lastInsertId().'", "'.$periodoid.'", "'.$groupid.'", 0)';
                            $sth_taxo = $this->db->prepare($query_taxo);
                            $sth_taxo->execute();


                        }else{
                            $up++;

                            $query_update_alumnos = 'UPDATE tbl_alumnos SET '.
                            'col_control="'.$cols[2].'", '.
                            'col_cedula="'.trim($cols[45]).'", '.
                            'col_fecha_nacimiento="'.(trim($cols[5]) == ''?'0000-00-00':date('Y-m-d', strtotime($cols[5]))).'", '.
                            'col_telefono="'.(trim($cols[6]) == ''?'-':trim($cols[6])).'", '.
                            'col_celular="'.$cols[7].'", '.
                            'col_correo_personal="'.(trim($cols[8]) == ''?filter_var(trim($cols[0]), FILTER_SANITIZE_EMAIL):filter_var($cols[8], FILTER_SANITIZE_EMAIL)).'", '.
                            'col_genero="'.(trim($cols[9]) == ''?'H':trim($cols[9])).'", '.
                            'col_sangre="'.$cols[10].'", '.
                            'col_periodoid="'.$periodoid.'", '. //$cols[11] $cols[13] $cols[14]
                            'col_generacion_start="'.$generacion[0].'", './/$cols[12]
                            'col_generacion_end="'.$generacion[1].'", './/$cols[12]
                            'col_direccion="'.$cols[15].'", '.
                            'col_enfermedades="'.$cols[30].'", '.
                            'col_proce_prepa="'.$cols[31].'", '.
                            'col_proce_prepa_promedio="'.$cols[32].'", '.
                            'col_proce_universidad_lic="'.$cols[33].'", '.
                            'col_proce_licenciatura="'.$cols[34].'", '.
                            'col_proce_universidad_master="'.$cols[35].'", '.
                            'col_proce_maestria="'.$cols[36].'", '. // AK
                            'col_seguro_folio="'.$cols[37].'", '.
                            'col_trabajo="'.$cols[38].'", '.
                            'col_cargo_trabajo="'.$cols[39].'", '.
                            'col_carrera="'.$carrera.'", '. //$cols[40] // AO // Revoe
                            'col_egresado="'.$egresado.'", '. // Col 42
                            'col_referencia="'.trim($cols[43]).'", '.
                            'col_plan_estudios="'.$planestudiosid.'", '.
                            'col_estatus="'.trim($cols[46]).'", '.
                            'col_updated_at="'.date("Y-m-d H:i:s").'", '.
                            'col_updated_by="'.$input->userid.'" '.
                            'WHERE col_id="'.$alumnoID.'"';
                            $sth_update_alumnos = $this->db->prepare($query_update_alumnos);
                            $sth_update_alumnos->execute();


                            $query_check_alumnoTAX = 'SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid="'.$alumnoID.'" AND col_periodoid="'.$periodoid.'"';
                            $objCheckAlumnoTAX = $this->db->prepare($query_check_alumnoTAX);
                            $objCheckAlumnoTAX->execute();
                            if($objCheckAlumnoTAX->rowCount() == 0){
                                $query_taxo = 'INSERT INTO tbl_alumnos_taxonomia (col_alumnoid, col_periodoid, col_groupid, col_status)
                                VALUES("'.$alumnoID.'", "'.$periodoid.'", "'.$groupid.'", 0)';
                                $sth_taxo = $this->db->prepare($query_taxo);
                                $sth_taxo->execute();
                            }


                        }


                        $fixPeriodos = $this->db->prepare('SELECT t.col_id AS ID, p.* FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_alumnoid="'.$alumnoID.'"');
                        $fixPeriodos->execute();
                        $listPeriodos = $fixPeriodos->fetchAll();
                        $i = 0;
                        $topPeriodo = 0;
                        $topPeriodoIDTax = 0;
                        $topPeriodoID = 0;
                        foreach($listPeriodos as $item){
                            if($item['col_grado'] > $topPeriodo){
                                $topPeriodo = $item['col_grado'];
                                $topPeriodoIDTax = $item['ID'];
                                $topPeriodoID = $item['col_id'];
                            }
                        }

                        $updatePeriodoTax = $this->db->prepare('UPDATE tbl_alumnos_taxonomia SET col_status=1 WHERE col_id="'.$topPeriodoIDTax.'"');
                        $updatePeriodoTax->execute();
                        $updateAlumnoPeriodoTax = $this->db->prepare('UPDATE tbl_alumnos SET col_periodoid="'.$topPeriodoID.'" WHERE col_id="'.$alumnoID.'"');
                        $updateAlumnoPeriodoTax->execute();
                        $updatePeriodoTax = $this->db->prepare('UPDATE tbl_alumnos_taxonomia SET col_status=0 WHERE col_id!="'.$topPeriodoIDTax.'" AND col_alumnoid="'.$alumnoID.'"');
                        $updatePeriodoTax->execute();


                    }else{
                        $_response['cols'][] = $cols;
                    }

                }
                $i++;
            }
            $_response['updates'] = $up;
            $_response['inserts'] = $in;
            $_response['alumnos_agregados'] = $add;
        }
        if($input->archivoUsuarios->filename != ''){
            $sthr = $this->db->prepare('DELETE FROM tbl_users WHERE col_id != 1 AND col_id!=61');
            $sthr->execute();
            $stha = $this->db->prepare('ALTER TABLE tbl_users AUTO_INCREMENT=2');
            $stha->execute();
            $sthm = $this->db->prepare('DELETE FROM tbl_maestros');
            $sthm->execute();


            $sth_p = $this->db->prepare('DELETE FROM tbl_departamentos WHERE col_id != 1');
            $sth_p->execute();
            $sth_pa = $this->db->prepare('ALTER TABLE tbl_departamentos AUTO_INCREMENT=2');
            $sth_pa->execute();

            $file = base64_decode($input->archivoUsuarios->value);
            //$data = str_getcsv($file);
            $lines = explode("\n", $file);

            $maestros = 0; $add = 0; $i = 0;
            foreach($lines as $line){
                if($i > 0){
                    $cols = explode("\t", $line);

                    if(trim($cols[2]) != '') {

                        $qCheckUsers = 'SELECT * FROM tbl_users WHERE col_email="'.trim($cols[2]).'" ';
                        $obj_CheckUsers = $this->db->prepare($qCheckUsers);
                        $obj_CheckUsers->execute();
                        if($obj_CheckUsers->rowCount() == 0){

                            $maestro = 1;
                            if(trim(strtolower($cols[0])) == 'no') $maestro = 0;
                            // $cols[17] Departamento
                            $departamento = 1;
                            if($cols[17] != ''){
                                $query_deptos = 'SELECT * FROM tbl_departamentos WHERE col_nombre="'.$cols[17].'" ';
                                $sthdeptos = $this->db->prepare($query_deptos);
                                $sthdeptos->execute();
                                if($sthdeptos->rowCount() > 0){
                                    $depto_data = $sthdeptos->fetch(PDO::FETCH_OBJ);
                                    $departamento = $depto_data->col_id;
                                }else{
                                    $add_depto_query = 'INSERT INTO tbl_departamentos (col_nombre) VALUES("'.$cols[17].'")';
                                    $sth_add_depto = $this->db->prepare($add_depto_query);
                                    $sth_add_depto->execute();
                                    $departamento = $this->db->lastInsertId();
                                }
                            }


                            $data = array(
                                "col_cedula" => ($cols[1]),
                                "col_email" => trim($cols[2]),
                                "col_pass" => md5($cols[3]),
                                "col_lastname" => ($cols[4]),
                                "col_firstname" => ($cols[5]),
                                "col_fecha_nacimiento" => date('Y-m-d', strtotime($cols[6])),
                                "col_genero" => $cols[7],
                                "col_phone" => $cols[10],
                                "col_ext" => $cols[11],
                                "col_celular" => $cols[12], // M
                                "col_direccion" => ($cols[13]),
                                "col_ciudad" => ($cols[14]),
                                "col_estado" => ($cols[15]),
                                "col_pais" => $cols[16],
                                "col_type" => 0, //Departamento
                                "col_depto" => $departamento, //Departamento
                                "col_rfc" => $cols[18],
                                "col_nss" => $cols[19], // T
                                "col_sangre" => $cols[20],
                                "col_nomina" => $cols[21],
                                "col_patronal" => $cols[22], // W
                                "col_fecha_ingreso_semestral" => date('Y-m-d', strtotime($cols[23])),
                                "col_fecha_termino_semestral" => date('Y-m-d', strtotime($cols[24])),
                                "col_fecha_ingreso_cuatri" => date('Y-m-d', strtotime($cols[25])),
                                "col_fecha_termino_cuatri" => date('Y-m-d', strtotime($cols[26])),  // AA
                                "col_estudios" => ($cols[27]),
                                "col_perfil_profesional" => $cols[28],
                                "col_911_nombre" => ($cols[29]),
                                "col_911_telefono" => $cols[30],
                                "col_911_celular" => $cols[31],
                                "col_dependencia" => $cols[32],
                                "col_status" => 1,
                                "col_maestro" => $maestro,
                                "col_created_at" => date("Y-m-d H:i:s"),
                                "col_created_by" => $input->userid,
                                "col_updated_at" => date("Y-m-d H:i:s"),
                                "col_updated_by" => $input->userid,
                            );

                            $query = 'INSERT INTO tbl_users ('.implode(",", array_keys($data)).')
                            VALUES("'.implode('", "', array_values($data)).'")';
                            //$_response['querys'][] = $query;

                            $sth = $this->db->prepare($query);
                            if($sth->execute()){
                                if($maestro == 1){
                                    $query = 'INSERT INTO tbl_maestros (col_userid, col_costo_clase, col_costo_clase_academia, col_costo_clase_postgrado, col_created_at, col_created_by, col_updated_at, col_updated_by)
                                    VALUES("'.$this->db->lastInsertId().'", "'.$cols[8].'", "'.$cols[9].'", "'.$cols[33].'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'", "'.date("Y-m-d H:i:s").'", "'.$input->userid.'")';
                                    $_response['querys2'][] = $query;
                                    $ma = $this->db->prepare($query);
                                    $ma->execute();
                                    $maestros++;
                                }
                                $add++;
                            }
                        }

                    }
                }
                $i++;
            }

            $_response['usuarios_agregados'] = $add;
            $_response['maestros_agregados'] = $maestros;
        }
        if($input->archivoCarreras->filename != ''){
            $file = base64_decode($input->archivoCarreras->value);
            $sthr = $this->db->prepare('TRUNCATE TABLE tbl_carreras');
            $sthr->execute();

            $lines = explode("\n", $file);
            $add = 0;
            $i = 0;
            foreach($lines as $line){
                if($i > 0){
                    $cols = explode("\t", $line);

                        $du = explode('y', $cols[5]); //Duracion
                        if(count($du) > 0){
                            $duracion = intval($du[0]).'.'.intval($du[1]);
                        }else{
                            $duracion = intval($cols[5]);
                        }
                        $modalidad = array('semestral' => 1, 'cuatrimestral' => 2, 'maestria' => 3, 'maestría' => 3, 'doctorado' => 4);
                        $campus = array('tuxtla' => 0, 'tapachula' => 1);
                        $estatus = array('activo' => 1, 'inactivo' => 0);

                        $data = array(
                            "col_nombre_largo" => trim($cols[0]),
                            "col_nombre_corto" => trim($cols[1]),
                            "col_revoe" => trim($cols[2]),
                            "col_fecha_inicio" => traducirFecha($cols[3]),
                            "col_actualizacion" => traducirFecha($cols[4]),
                            "col_duracion" => $duracion,
                            "col_modalidad"=> $modalidad[strtolower(trim($cols[6]))],
                            "col_campus" => trim($campus[$cols[7]]),
                            "col_estatus" => $estatus[strtolower(trim($cols[8]))],
                            "col_created_at" => date("Y-m-d H:i:s"),
                            "col_created_by" => $input->userid,
                            "col_updated_at" => date("Y-m-d H:i:s"),
                            "col_updated_by" => $input->userid,
                        );
                        $_response['debug2'][] = $cols;


                        $query = 'INSERT INTO tbl_carreras ('.implode(",", array_keys($data)).')
                        VALUES("'.implode('", "', array_values($data)).'")';
                        $_response['query'][] = $query;
                        $sth = $this->db->prepare($query);
                        if($sth->execute()){
                            $add++;
                        }
                    }

                $i++;
            }

            $_response['carreras_agregadas'] = $add;
        }
        if($input->archivoMaterias->filename != ''){
            $file = base64_decode($input->archivoMaterias->value);
            $sthr = $this->db->prepare('TRUNCATE TABLE tbl_materias');
            $sthr->execute();
            $sthr = $this->db->prepare('TRUNCATE TABLE tbl_planes_estudios');
            $sthr->execute();
            $sthr = $this->db->prepare('TRUNCATE TABLE tbl_materias_tipos');
            $sthr->execute();

            $lines = explode("\n", $file);
            $add = 0;
            $i = 0;
            foreach($lines as $line){
                if($i > 0){
                    $cols = explode("\t", $line);
                    if($cols[1] != 'Clave') {
                            $plan = array('semestral' => 0, 'cuatrimestral' => 1);
                            $lu = explode('-', $cols[3]);
                            $ma = explode('-', $cols[4]);
                            $mi = explode('-', $cols[5]);
                            $ju = explode('-', $cols[6]);
                            $vi = explode('-', $cols[7]);
                            if(trim($cols[8]) == '') $cols[8] = '00:00 - 00:00';
                            $sa = explode('-', $cols[8]);
                            if(trim($cols[9]) == '') $cols[9] = '00:00 - 00:00';
                            $do = explode('-', $cols[9]);
                            $horario['lunes'][0] = $lu[0];
                            $horario['lunes'][1] = $lu[1];
                            $horario['martes'][0] = $ma[0];
                            $horario['martes'][1] = $ma[1];
                            $horario['miercoles'][0] = $mi[0];
                            $horario['miercoles'][1] = $mi[1];
                            $horario['jueves'][0] = $ju[0];
                            $horario['jueves'][1] = $ju[1];
                            $horario['viernes'][0] = $vi[0];
                            $horario['viernes'][1] = $vi[1];
                            $horario['sabado'][0] = $sa[0];
                            $horario['sabado'][1] = $sa[1];
                            $horario['domingo'][0] = $do[0];
                            $horario['domingo'][1] = $do[1];

                            $queryc = 'SELECT * FROM tbl_carreras WHERE LOWER(col_revoe)="'.strtolower(trim($cols[12])).'"';
                            $sthc = $this->db->prepare($queryc);
                            $sthc->execute();
                            $carrera = $sthc->fetch(PDO::FETCH_OBJ);

                            $planEstudios = formatPlanEstudios($cols[15]);

                            if(trim($cols[15]) != ''){
                                $queryplanes = 'SELECT * FROM tbl_planes_estudios WHERE LOWER(col_nombre) LIKE "'.strtolower(trim($planEstudios)).'"';
                                $sthpl = $this->db->prepare($queryplanes);
                                $sthpl->execute();

                                if($sthpl->rowCount() > 0){
                                    $planestudio = $sthpl->fetch(PDO::FETCH_OBJ);
                                    $panestudiosid = $planestudio->col_id;
                                }else{
                                    $add_planestudio_query = 'INSERT INTO tbl_planes_estudios (col_nombre) VALUES("'.trim($planEstudios).'")';
                                    $sth_add_planestudios = $this->db->prepare($add_planestudio_query);
                                    $sth_add_planestudios->execute();
                                    $panestudiosid = $this->db->lastInsertId();
                                }
                            }


                            $claveGenerada = $cols[1];
                            $letrasClave = strtoupper(preg_replace("/[^A-Za-z]/", '', trim($claveGenerada)));
                            $numerosClave = (preg_replace("/[^0-9]/", '', trim($claveGenerada)));

                            $queryc = 'SELECT * FROM tbl_materias_tipos WHERE col_letras="'.$letrasClave.'"';
                            $sthc = $this->db->prepare($queryc);
                            $sthc->execute();
                            if($sthc->rowCount() > 0){
                                $tiposMaterias = $sthc->fetch(PDO::FETCH_OBJ);
                                $tipoMateria = $tiposMaterias->col_id;
                            }else{
                                $addTipoMateria = 'INSERT INTO tbl_materias_tipos (col_nombre, col_carrera, col_letras, ) VALUES("'.trim($cols[15]).'")';

                                $dataTipoMateria = array(
                                    "col_nombre" => $letrasClave,
                                    "col_carrera" => $carrera->col_id,
                                    "col_letras" => $letrasClave,
                                    "col_tipo" => 1,
                                    "col_estatus" => 1,
                                    "col_created_at" => date("Y-m-d H:i:s"),
                                    "col_created_by" => $input->userid,
                                    "col_updated_at" => date("Y-m-d H:i:s"),
                                    "col_updated_by" => $input->userid,
                                );

                                $addTipoMateria = 'INSERT INTO tbl_materias_tipos ('.implode(",", array_keys($dataTipoMateria)).')
                                VALUES("'.implode('", "', array_values($dataTipoMateria)).'")';

                                $sth_add_tiposMaterias = $this->db->prepare($addTipoMateria);
                                $sth_add_tiposMaterias->execute();
                                $tipoMateria = $this->db->lastInsertId();
                            }



                            $data = array(
                                "col_nombre" => $cols[0],
                                "col_clave" => $claveGenerada,
                                'col_numero_clave' => $numerosClave,
                                'col_tipo_materia' => $tipoMateria,
                                "col_serie" => $cols[2],
                                "col_plan" => $plan[strtolower($cols[13])],
                                "col_horario"=> addslashes(serialize($horario)),
                                "col_carrera" => $carrera->col_id,
                                "col_semestre" => $cols[14],
                                "col_plan_estudios" => $panestudiosid,
                                "col_creditos" => ($cols[16]),
                                "col_created_at" => date("Y-m-d H:i:s"),
                                "col_created_by" => $input->userid,
                                "col_updated_at" => date("Y-m-d H:i:s"),
                                "col_updated_by" => $input->userid,
                            );

                            $query = 'INSERT INTO tbl_materias ('.implode(",", array_keys($data)).')
                            VALUES("'.implode('", "', array_values($data)).'")';
                            $sth = $this->db->prepare($query);
                            if($sth->execute()){
                                $add++;
                            }
                        }
                    }

                $i++;
            }

            $_response['materias_agregadas'] = $add;
        }

        if($input->archivoPagos->filename != ''){
            $file = base64_decode($input->archivoPagos->value);
            $sthr = $this->db->prepare('TRUNCATE TABLE tbl_pagos');
            $sthr->execute();

            $lines = explode("\n", $file);
            $add = 0;
            $i = 0;
            $oCols = [];
            foreach($lines as $line){
                $cols = explode("\t", $line);

                if($cols[0] != ''){
                    $generacion = explode('-', trim($cols[0]));
                    if(intval($generacion[0]) > 0 && intval($generacion[1]) > 0){

                        $queryc = 'SELECT * FROM tbl_alumnos WHERE LOWER(col_referencia)="'.strtolower(trim($oCols[8])).'"';
                        $sthc = $this->db->prepare($queryc);
                        $sthc->execute();
                        $alumno = $sthc->fetch(PDO::FETCH_OBJ);


                            $data = array(
                                "col_alumnoid" => $alumno->col_id,
                                "col_referencia" => trim($oCols[8]),
                                "col_cargos_pagados" => fixCurrency($cols[11]),
                                "col_recargos_pagados" => fixCurrency($cols[13]),
                                "col_total_pagado" => fixCurrency($cols[14]),
                                "col_cargos_vencidos" => fixCurrency($cols[15]),
                                "col_total_recargos" => fixCurrency($cols[17]),
                                "col_total_adeudo_vencido" => fixCurrency($cols[19]),
                                "col_total_adeudo_no_vencido" => fixCurrency($cols[20]),
                                "col_created_at" => date("Y-m-d H:i:s"),
                                "col_created_by" => $input->userid,
                                "col_updated_at" => date("Y-m-d H:i:s"),
                                "col_updated_by" => $input->userid,
                            );

                            $query = 'INSERT INTO tbl_pagos ('.implode(",", array_keys($data)).')
                            VALUES("'.implode('", "', array_values($data)).'")';
                            // $_response['query'][] = $data;
                            $sth = $this->db->prepare($query);
                            if($sth->execute()){
                                $add++;
                            }

                        if(intval($alumno->col_id) == 0){
                            $_response['missing_alumnos'][] = $oCols[8];
                        }
                    }
                }
                $oCols = $cols;
                $i++;
            }


            $_response['pagos_agregados'] = $add;
            $_response['pagos_debug'] = $file;
        }

        if($input->archivoLectura->filename != ''){
            $file = base64_decode($input->archivoLectura->value);
            $sthr = $this->db->prepare('TRUNCATE TABLE tbl_club_lectura');
            $sthr->execute();

            $lines = explode("\n", $file);
            $add = 0;
            $i = 0;
            foreach($lines as $line){
                $cols = explode("\t", $line);
                if(filter_var(trim($cols[1]), FILTER_VALIDATE_EMAIL)){

                        $queryc = 'SELECT * FROM tbl_alumnos WHERE col_correo="'.trim($cols[1]).'"';
                        $sthc = $this->db->prepare($queryc);
                        $sthc->execute();
                        $alumno = $sthc->fetch(PDO::FETCH_OBJ);

                        $queryc = 'SELECT * FROM tbl_carreras WHERE col_revoe="'.trim($cols[7]).'" LIMIT 1';
                        $sthc = $this->db->prepare($queryc);
                        $sthc->execute();
                        $carrera = $sthc->fetch(PDO::FETCH_OBJ);

                        $querym = 'SELECT * FROM tbl_materias WHERE col_clave="'.formatClave($cols[5]).'" AND col_carrera="'.$carrera->col_id.'"';
                        $sthm = $this->db->prepare($querym);
                        $sthm->execute();
                        $materia = $sthm->fetch(PDO::FETCH_OBJ);

                        $queryp = 'SELECT * FROM tbl_periodos WHERE col_nombre="'.formatNombrePeriodo($cols[4]).'" AND col_grado="'.trim($cols[2]).'" AND col_grupo="'.trim($cols[3]).'"';
                        $sthp = $this->db->prepare($queryp);
                        $sthp->execute();
                        $periodo = $sthp->fetch(PDO::FETCH_OBJ);

                        $data = array(
                            "col_alumnoid" => $alumno->col_id,
                            "col_periodoid" => $periodo->col_id,
                            "col_materiaid" => $materia->col_id,
                        );

                        if(intval($periodo->col_id) > 0){
                            $query = 'INSERT INTO tbl_club_lectura ('.implode(",", array_keys($data)).')
                            VALUES("'.implode('", "', array_values($data)).'")';
                            $sth = $this->db->prepare($query);
                            if($sth->execute()){
                                $add++;
                                $query = "UPDATE tbl_periodos SET col_club_lectura='".addslashes(trim($cols[6]))."' WHERE col_id='".$periodo->col_id."'";
                                $sth = $this->db->prepare($query);
                                $sth->execute();
                            }
                        }

                        if(intval($alumno->col_id) == 0){
                            $_response['missing_alumnos'][] = $cols[1];
                        }

                }

                $i++;
            }
            $_response['club_lectura_agregadas'] = $add;

        }

        if($input->archivoTransversales->filename != ''){
            $file = base64_decode($input->archivoTransversales->value);
            $sthr = $this->db->prepare('TRUNCATE TABLE tbl_transversales');
            $sthr->execute();

            $lines = explode("\n", $file);
            $add = 0;
            $i = 0;
            foreach($lines as $line){
                $cols = explode("\t", $line);
                if(filter_var(trim($cols[1]), FILTER_VALIDATE_EMAIL)){

                        $queryc = 'SELECT * FROM tbl_alumnos WHERE col_correo="'.trim($cols[1]).'"';
                        $sthc = $this->db->prepare($queryc);
                        $sthc->execute();
                        $alumno = $sthc->fetch(PDO::FETCH_OBJ);

                        $queryc = 'SELECT * FROM tbl_carreras WHERE col_revoe="'.trim($cols[7]).'" LIMIT 1';
                        $sthc = $this->db->prepare($queryc);
                        $sthc->execute();
                        $carrera = $sthc->fetch(PDO::FETCH_OBJ);

                        $querym = 'SELECT * FROM tbl_materias WHERE col_clave="'.formatClave($cols[5]).'" AND col_carrera="'.$carrera->col_id.'"';
                        $sthm = $this->db->prepare($querym);
                        $sthm->execute();
                        $materia = $sthm->fetch(PDO::FETCH_OBJ);

                        $queryp = 'SELECT * FROM tbl_periodos WHERE col_nombre="'.formatNombrePeriodo($cols[4]).'" AND col_grado="'.trim($cols[2]).'" AND col_grupo="'.trim($cols[3]).'"';
                        $sthp = $this->db->prepare($queryp);
                        $sthp->execute();
                        $periodo = $sthp->fetch(PDO::FETCH_OBJ);

                            $data = array(
                                "col_alumnoid" => $alumno->col_id,
                                "col_periodoid" => $periodo->col_id,
                                "col_materiaid" => $materia->col_id,
                            );

                            $query = 'INSERT INTO tbl_transversales ('.implode(",", array_keys($data)).')
                            VALUES("'.implode('", "', array_values($data)).'")';
                            $sth = $this->db->prepare($query);
                            if($sth->execute()){
                                $add++;
                            }

                        if(intval($alumno->col_id) == 0){
                            $_response['missing_alumnos'][] = $cols[1];
                        }

                }

                $i++;
            }
            $_response['transversales_agregadas'] = $add;

        }

        if($input->archivoTalleres->filename != ''){
            $file = base64_decode($input->archivoTalleres->value);
            $sthr = $this->db->prepare('TRUNCATE TABLE tbl_talleres');
            $sthr->execute();

            $lines = explode("\n", $file);
            $add = 0;
            $i = 0;
            foreach($lines as $line){
                $cols = explode("\t", $line);
                if(filter_var(trim($cols[1]), FILTER_VALIDATE_EMAIL)){

                        $queryc = 'SELECT * FROM tbl_alumnos WHERE col_correo="'.trim($cols[1]).'"';
                        $sthc = $this->db->prepare($queryc);
                        $sthc->execute();
                        $alumno = $sthc->fetch(PDO::FETCH_OBJ);

                        $queryc = 'SELECT * FROM tbl_carreras WHERE col_revoe="'.trim($cols[7]).'" LIMIT 1';
                        $sthc = $this->db->prepare($queryc);
                        $sthc->execute();
                        $carrera = $sthc->fetch(PDO::FETCH_OBJ);

                        $querym = 'SELECT * FROM tbl_materias WHERE col_clave="'.formatClave($cols[5]).'" AND col_carrera="'.$carrera->col_id.'"';
                        $sthm = $this->db->prepare($querym);
                        $sthm->execute();
                        $materia = $sthm->fetch(PDO::FETCH_OBJ);

                        $queryp = 'SELECT * FROM tbl_periodos WHERE col_nombre="'.formatNombrePeriodo($cols[4]).'" AND col_grado="'.trim($cols[2]).'" AND col_grupo="'.trim($cols[3]).'"';
                        $sthp = $this->db->prepare($queryp);
                        $sthp->execute();
                        $periodo = $sthp->fetch(PDO::FETCH_OBJ);

                            $data = array(
                                "col_alumnoid" => $alumno->col_id,
                                "col_periodoid" => $periodo->col_id,
                                "col_materiaid" => $materia->col_id,
                            );

                            $query = 'INSERT INTO tbl_talleres ('.implode(",", array_keys($data)).')
                            VALUES("'.implode('", "', array_values($data)).'")';
                            $sth = $this->db->prepare($query);
                            if($sth->execute()){
                                $add++;
                            }

                        if(intval($alumno->col_id) == 0){
                            $_response['missing_alumnos'][] = $cols[1];
                        }

                }

                $i++;
            }
            $_response['talleres_agregados'] = $add;

        }
        if($input->archivoAcademias->filename != ''){
            $file = base64_decode($input->archivoAcademias->value);
            $sthr = $this->db->prepare('TRUNCATE TABLE tbl_academias');
            $sthr->execute();

            $lines = explode("\n", $file);
            $add = 0;
            $i = 0;
            foreach($lines as $line){
                $cols = explode("\t", $line);
                if(filter_var(trim($cols[1]), FILTER_VALIDATE_EMAIL)){

                        $queryc = 'SELECT * FROM tbl_alumnos WHERE col_correo="'.trim($cols[1]).'"';
                        $sthc = $this->db->prepare($queryc);
                        $sthc->execute();
                        $alumno = $sthc->fetch(PDO::FETCH_OBJ);

                        $queryc = 'SELECT * FROM tbl_carreras WHERE col_revoe="'.trim($cols[7]).'" LIMIT 1';
                        $sthc = $this->db->prepare($queryc);
                        $sthc->execute();
                        $carrera = $sthc->fetch(PDO::FETCH_OBJ);

                        $querym = 'SELECT * FROM tbl_materias WHERE col_clave="'.formatClave($cols[5]).'" AND col_carrera="'.$carrera->col_id.'"';
                        $sthm = $this->db->prepare($querym);
                        $sthm->execute();
                        $materia = $sthm->fetch(PDO::FETCH_OBJ);

                        $queryp = 'SELECT * FROM tbl_periodos WHERE col_nombre="'.formatNombrePeriodo($cols[4]).'" AND col_grado="'.trim($cols[2]).'" AND col_grupo="'.trim($cols[3]).'"';
                        $sthp = $this->db->prepare($queryp);
                        $sthp->execute();
                        $periodo = $sthp->fetch(PDO::FETCH_OBJ);

                            $data = array(
                                "col_alumnoid" => $alumno->col_id,
                                "col_periodoid" => $periodo->col_id,
                                "col_materiaid" => $materia->col_id,
                            );

                            $query = 'INSERT INTO tbl_academias ('.implode(",", array_keys($data)).')
                            VALUES("'.implode('", "', array_values($data)).'")';
                            $sth = $this->db->prepare($query);
                            if($sth->execute()){
                                $add++;
                            }

                        if(intval($alumno->col_id) == 0){
                            $_response['missing_alumnos'][] = $cols[1];
                        }

                }

                $i++;
            }
            $_response['academias_agregados'] = $add;

        }
        if($input->archivoPracticas->filename != ''){
            $file = base64_decode($input->archivoPracticas->value);
            $sthr = $this->db->prepare('TRUNCATE TABLE tbl_practicas');
            $sthr->execute();

            $lines = explode("\n", $file);
            $add = 0;
            $i = 0;
            foreach($lines as $line){
                $cols = explode("\t", $line);
                if(filter_var(trim($cols[2]), FILTER_VALIDATE_EMAIL)){

                        $queryc = 'SELECT * FROM tbl_alumnos WHERE col_correo="'.trim($cols[2]).'"';
                        $sthc = $this->db->prepare($queryc);
                        $sthc->execute();
                        $alumno = $sthc->fetch(PDO::FETCH_OBJ);

                        $querym = 'SELECT * FROM tbl_carreras WHERE col_revoe="'.trim($cols[14]).'"';
                        $sthm = $this->db->prepare($querym);
                        $sthm->execute();
                        $materia = $sthm->fetch(PDO::FETCH_OBJ);

                        $queryp = 'SELECT * FROM tbl_periodos WHERE col_nombre LIKE "%'.formatNombrePeriodo($cols[13]).'%" AND col_grado="'.trim($cols[11]).'" AND col_grupo="'.trim($cols[12]).'"';
                        $sthp = $this->db->prepare($queryp);
                        $sthp->execute();
                        if($sthp->rowCount() == 0) $_response['debug'][] = $queryp;
                        $periodo = $sthp->fetch(PDO::FETCH_OBJ);

                            $data = array(
                                "col_alumnoid" => $alumno->col_id,
                                "col_periodoid" => $periodo->col_id,
                                "col_carreraid" => $materia->col_id,
                                "col_convenio" => addslashes(trim($cols[3])),
                                "col_oficio" => addslashes(trim($cols[4])),
                                "col_lugar" => addslashes(trim($cols[5])),
                                "col_titular" => addslashes(trim($cols[6])),
                                "col_jefe" => addslashes(trim($cols[7])),
                                "col_area" => addslashes(trim($cols[8])),
                                "col_direccion" => addslashes(trim($cols[9])),
                                "col_telefono" => trim($cols[10]),
                                "col_created_at" => date("Y-m-d H:i:s"),
                                "col_created_by" => $input->userid,
                                "col_updated_at" => date("Y-m-d H:i:s"),
                                "col_updated_by" => $input->userid,
                            );

                            $query = 'INSERT INTO tbl_practicas ('.implode(",", array_keys($data)).')
                            VALUES("'.implode('", "', array_values($data)).'")';
                            $sth = $this->db->prepare($query);
                            if($sth->execute()){
                                $add++;
                            }

                        if(intval($alumno->col_id) == 0){
                            $_response['missing_alumnos'][] = $cols[1];
                        }

                }

                $i++;
            }
            $_response['practicas_agregados'] = $add;

        }

        if($input->archivoServicio->filename != ''){
            $file = base64_decode($input->archivoServicio->value);
            $sthr = $this->db->prepare('TRUNCATE TABLE tbl_servicio_social');
            $sthr->execute();

            $lines = explode("\n", $file);
            $add = 0;
            $i = 0;
            foreach($lines as $line){
                $cols = explode("\t", $line);
                if(filter_var(trim($cols[1]), FILTER_VALIDATE_EMAIL)){

                        $queryc = 'SELECT * FROM tbl_alumnos WHERE col_correo="'.trim($cols[1]).'"';
                        $sthc = $this->db->prepare($queryc);
                        $sthc->execute();
                        $alumno = $sthc->fetch(PDO::FETCH_OBJ);

                        $querym = 'SELECT * FROM tbl_carreras WHERE col_revoe="'.trim($cols[14]).'"';
                        $sthm = $this->db->prepare($querym);
                        $sthm->execute();
                        $materia = $sthm->fetch(PDO::FETCH_OBJ);

                        $queryp = 'SELECT * FROM tbl_periodos WHERE col_nombre="'.formatNombrePeriodo($cols[13]).'" AND col_grado="'.trim($cols[11]).'" AND col_grupo="'.trim($cols[12]).'"';
                        $sthp = $this->db->prepare($queryp);
                        $sthp->execute();
                        $periodo = $sthp->fetch(PDO::FETCH_OBJ);

                            $data = array(
                                "col_alumnoid" => $alumno->col_id,
                                "col_periodoid" => $periodo->col_id,
                                "col_carreraid" => $materia->col_id,
                                "col_oficio" => trim($cols[2]),
                                "col_lugar" => trim($cols[3]),
                                "col_titular" => trim($cols[4]),
                                "col_jefe" => trim($cols[5]),
                                "col_area" => trim($cols[6]),
                                "col_direccion" => trim($cols[7]),
                                "col_telefono" => trim($cols[8]),
                                "col_created_at" => date("Y-m-d H:i:s"),
                                "col_created_by" => $input->userid,
                                "col_updated_at" => date("Y-m-d H:i:s"),
                                "col_updated_by" => $input->userid,
                            );

                            $query = 'INSERT INTO tbl_servicio_social ('.implode(",", array_keys($data)).')
                            VALUES("'.implode('", "', array_values($data)).'")';
                            $sth = $this->db->prepare($query);
                            if($sth->execute()){
                                $add++;
                            }

                        if(intval($alumno->col_id) == 0){
                            $_response['missing_alumnos'][] = $cols[1];
                        }

                }

                $i++;
            }
            $_response['serviciosocial_agregados'] = $add;

        }

        $_response['status'] = 'true';

        return $this->response->withJson($_response);

    });

});
