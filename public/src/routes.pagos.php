<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de pagos.
 *
 * Lista de funciones
 *
 * /pagos
 * - /download
 * - /list
 * - /get
 * - /limpiar
 * - /actualizar
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/pagos', function () {

    $this->get('/download', function (Request $request, Response $response, array $args) {
        $result['test'] = 'Hola';
        $sth = $this->db->prepare("SELECT * FROM tbl_alumnos ORDER BY col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        $data = "# de Control,Nombres,Apellidos,Correo\n";
        foreach($todos as $row){
            $data .= $row['col_control'].",".$row['col_nombres'].",".$row['col_apellidos'].",".$row['col_correo']."\n";
        }

          header('Content-Type: application/csv');
          header('Content-Disposition: attachment; filename="db_alumnos_'.date('Y-m-d').'.csv"');
          echo $data; exit();

        // return $this->response->withJson($result);
    });

    $this->get('/list', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_pagos ORDER BY col_id DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        $stO = array(0 => 'no', 1 => 'yes');
        $stI = array(0 => 'far fa-square', 1 => 'far fa-check-square');
        foreach($todos as $item){

            $al = $this->db->prepare("SELECT CONCAT(col_nombres, ' ', col_apellidos) AS col_fullname FROM tbl_alumnos WHERE col_id='".$item['col_alumnoid']."'");
            $al->execute();
            $alumno = $al->fetch(PDO::FETCH_OBJ);

            $co = $this->db->prepare("SELECT * FROM tbl_conceptos WHERE col_id='".$item['col_concepto']."'");
            $co->execute();
            $concepto = $co->fetch(PDO::FETCH_OBJ);

            $result[$i]['col_referencia'] = $item['col_referencia'];
            $result[$i]['alumno'] = fixEncode($alumno->col_fullname);
            $result[$i]['col_cargos_pagados'] = '$'.number_format($item['col_cargos_pagados'], 2);
            $result[$i]['col_recargos_pagados'] = '$'.number_format($item['col_recargos_pagados'], 2);
            $result[$i]['col_total_pagado'] = '$'.number_format($item['col_total_pagado'], 2);
            $result[$i]['col_recargos_vencidos'] = '$'.number_format($item['col_recargos_vencidos'], 2);
            $result[$i]['col_total_recargos'] = '$'.number_format($item['col_total_recargos'], 2);
            $result[$i]['col_total_adeudo_vencido'] = '$'.number_format($item['col_total_adeudo_vencido'], 2);
            $result[$i]['col_total_adeudo_no_vencido'] = '$'.number_format($item['col_total_adeudo_no_vencido'], 2);
            $result[$i]['col_fecha'] = $item['col_created_at'];

            $i++;
        }
        return $this->response->withJson($result);

    });

    $this->post('/get', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $query = 'SELECT * FROM tbl_pagos WHERE col_id="'.intval($input['params']['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson($data);

    });

    $this->post('/limpiar', function (Request $request, Response $response, $args) {
            $sthr = $this->db->prepare('TRUNCATE TABLE tbl_pagos');
            $sthr->execute();

            $_response['status'] = 'true';

            return $this->response->withJson($_response);
    });

    $this->post('/actualizar', function (Request $request, Response $response, $args) {

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        foreach($input->archivoPagos as $archivo) {
            if($archivo->filename != ''){
                $file = base64_decode($archivo->value);
                // $sthr = $this->db->prepare('TRUNCATE TABLE tbl_pagos');
                // $sthr->execute();

                $lines = explode("\n", $file);
                $add = 0;
                $i = 0;
                $oCols = [];
                foreach($lines as $line){
                    $cols = explode("\t", $line);

                    if($cols[0] != ''){
                        $generacion = explode('-', trim($cols[0]));
                        if(intval($generacion[0]) > 0 && intval($generacion[1]) > 0){


                            if(strtolower(trim(str_replace('"', '', $oCols[8]))) != '') {
                                $queryc = 'SELECT * FROM tbl_alumnos WHERE TRIM(LOWER(col_referencia))="'.strtolower(trim(str_replace('"', '', $oCols[8]))).'"';
                                $sthc = $this->db->prepare($queryc);
                                $sthc->execute();
                                $alumno = $sthc->fetch(PDO::FETCH_OBJ);

                                $queryc = 'SELECT * FROM tbl_pagos WHERE TRIM(LOWER(col_referencia))="'.strtolower(trim(str_replace('"', '', $oCols[8]))).'"';
                                $sthc = $this->db->prepare($queryc);
                                $sthc->execute();
                                if($sthc->rowCount() == 0) {


                                    $data = array(
                                        "col_alumnoid" => $alumno->col_id,
                                        "col_referencia" => trim(str_replace('"', '', $oCols[8])),
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
                                }
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
                //$_response['pagos_debug'] = $file;
            }
        }

        $_response['status'] = 'true';

        return $this->response->withJson($_response);

    });

});
// Termina routes.pagos.php
