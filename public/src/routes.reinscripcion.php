<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de inscripción/reinscripción.
 *
 * Lista de funciones
 *
 * /reinscripcion
 * - /guardarGrupos
 * - /guardar
 * - /update
 * - /delete
 * - /deleteTaller
 * - /deleteAcademia
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/reinscripcion', function () {

    $this->post('/guardarGrupos', function (Request $request, Response $response, $args) {
        $userid = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());
        $alumnos = $input->grupos;
        $periodoid = $input->periodo; // Current or OLD Periodo

        $periodoData = getPeriodo($periodoid, $this->db, false);

        foreach($alumnos as $k => $v) {
            if(!empty($v)){
                // $_result[] = $k.":".$v;
                $current = 'SELECT * FROM tbl_alumnos_taxonomia WHERE col_id="'.intval($k).'"';
                $currentObj = $this->db->prepare($current);
                $currentObj->execute();
                $cdata = $currentObj->fetch(PDO::FETCH_OBJ);
                $_groupid = $cdata->col_groupid;
                $_periodoid = $cdata->col_periodoid;
                $_alumnoid = $cdata->col_alumnoid;

                $newPeriodo = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.intval($_groupid).'" AND col_grado="'.$periodoData->col_grado.'" AND col_grupo="'.$v.'"';
                $newObj = $this->db->prepare($newPeriodo);
                $newObj->execute();
                $ndata = $newObj->fetch(PDO::FETCH_OBJ);
                $nuevoPeriodo = $ndata->col_id;

                $query = 'UPDATE tbl_alumnos_taxonomia SET col_periodoid="'.$nuevoPeriodo.'", col_updated_at="'.date('Y-m-d h:i:s').'", col_updated_by="'.$userid.'" WHERE col_id="'.intval($k).'"';
                // $_result[] = $query;
                $update = $this->db->prepare($query);
                $update->execute();

                $update = $this->db->prepare('UPDATE tbl_alumnos SET col_periodoid="'.$nuevoPeriodo.'", col_updated_at="'.date('Y-m-d h:i:s').'", col_updated_by="'.$userid.'" WHERE col_id="'.$_alumnoid.'"');
                $update->execute();

                $update = $this->db->prepare('UPDATE tbl_talleres SET col_periodoid="'.$nuevoPeriodo.'", col_updated_at="'.date('Y-m-d h:i:s').'", col_updated_by="'.$userid.'" WHERE col_periodoid="'.$periodoid.'" AND col_alumnoid="'.$_alumnoid.'"');
                $update->execute();

                $update = $this->db->prepare('UPDATE tbl_academias SET col_periodoid="'.$nuevoPeriodo.'", col_updated_at="'.date('Y-m-d h:i:s').'", col_updated_by="'.$userid.'" WHERE col_periodoid="'.$periodoid.'" AND col_alumnoid="'.$_alumnoid.'"');
                $update->execute();

            }
        }
        $_result['status'] = 'true';

        return $this->response->withJson($_result);

    });

    $this->post('/guardar', function (Request $request, Response $response, $args) {
        $userid = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        if(getCurrentUserType() == 'alumno'){
            $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval($input->alumnoid).'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
            $periodoParaReinscribirse = puedeReinscribirse($alumnoData->col_periodoid, $this->db, $input->alumnoid);
            $periodoParaReinscribirse = getPeriodo($periodoParaReinscribirse, $this->db, false);

            $queryAskPeriodoSemestre = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.intval($periodoParaReinscribirse->col_groupid).'" AND col_grado="'.intval($periodoParaReinscribirse->col_grado).'"';
            $objPeriodoSemestre = $this->db->prepare($queryAskPeriodoSemestre);
            $objPeriodoSemestre->execute();
            $dataPeriodoSemestre = $objPeriodoSemestre->fetch(PDO::FETCH_OBJ);
            if(intval($dataPeriodoSemestre->col_id) == 0){
                $_response['status'] = 'error';
                $_response['message'] = 'Por favor ponte en contacto con el departamento de control escolar.<br/>Error: Periodo Inexistente';

                return $this->response->withJson($_response);
            }
            $input->periodo = $dataPeriodoSemestre->col_id;
            $input->periodoid = $dataPeriodoSemestre->col_id;
        }
        if(getCurrentUserType() == 'administrativo'){

            $queryAskPeriodoSemestre = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.getPeriodoTaxoID($input->periodoid, $this->db).'" AND col_grupo="'.trim($input->grupo).'" AND col_grado="'.intval($input->grado).'"';
            $objPeriodoSemestre = $this->db->prepare($queryAskPeriodoSemestre);
            $objPeriodoSemestre->execute();
            $dataPeriodoSemestre = $objPeriodoSemestre->fetch(PDO::FETCH_OBJ);

            $input->periodo = $dataPeriodoSemestre->col_id;
            $input->periodoid = $dataPeriodoSemestre->col_id;
        }

        $data = array(
            'col_periodoid' => $input->periodoid,
            'col_fecha_nacimiento' => substr($input->fechaNacimiento[0], 0, 10),
            'col_telefono' => $input->telefono,
            'col_celular' => $input->celular,
            'col_correo_personal' => $input->correoPersonal,
            'col_direccion' => $input->direccion,
            'col_ciudad' => $input->ciudad,
            'col_estado' => $input->estado,
            'col_pais' => $input->pais,
            'col_sangre' => $input->sangre,
            'col_enfermedades' => $input->enfermedades,
            'col_trabajo' => $input->trabajo,
            'col_cargo_trabajo' => $input->cargoTrabajo,
            "col_rep_nombres" => $input->repNombres,
            "col_rep_apellidos" => $input->repApellidos,
            "col_rep_telefono" => $input->repTelefono,
            "col_rep_celular" => $input->repCelular,
            "col_rep_correo" => $input->repCorreo,
            "col_rep_empresa" => $input->repEmpresa,
            "col_rep_empresa_telefono" => $input->repEmpresaTelefono,
            "col_rep_parentesco" => $input->repParentesco,
            'col_updated_at' => date("Y-m-d H:i:s"),
            'col_updated_by' => $input->userid
        );

        $query = 'UPDATE tbl_alumnos SET '.prepareUpdate($data).' WHERE col_id="'.intval($input->alumnoid).'"';
        $alumno = $this->db->prepare($query);
        $alumno->execute();

        // Taxonomia de Alumnos
        $_response['taxonomia'] = 'empty';
        $queryTax = 'SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid="'.intval($input->alumnoid).'" AND col_periodoid="'.intval($input->periodoid).'"';
        $tax = $this->db->prepare($queryTax);
        $tax->execute();
        if($tax->rowCount() == 0){

            $updatePeriodoTax = $this->db->prepare('UPDATE tbl_alumnos_taxonomia SET col_status=0, col_updated_at="'.date('Y-m-d h:i:s').'", col_updated_by="'.$userid.'" WHERE col_alumnoid="'.intval($input->alumnoid).'"');
            $updatePeriodoTax->execute();

            $queryInsertTax = 'INSERT INTO tbl_alumnos_taxonomia (col_alumnoid, col_periodoid, col_groupid, col_status, col_created_at, col_created_by, col_updated_at, col_updated_by)
            VALUES("'.intval($input->alumnoid).'", "'.intval($input->periodoid).'", "'.getPeriodoTaxoID(intval($input->periodoid), $this->db).'", 1, "'.date('Y-m-d h:i:s').'", "'.$userid.'", "'.date('Y-m-d h:i:s').'", "'.$userid.'")';
            $taxInsert = $this->db->prepare($queryInsertTax);
            $taxInsert->execute();
            $_response['taxonomia'] = 'inserted';
        }

        // Talleres Alumnos
        $_response['taller'] = 'empty';
        if(intval($input->tallerid) > 0){
            $queryInsertTalleres = 'INSERT INTO tbl_talleres (col_alumnoid, col_periodoid, col_materiaid, col_created_by, col_created_at, col_updated_by, col_updated_at)
            VALUES("'.intval($input->alumnoid).'", "'.intval($input->periodoid).'", "'.intval($input->tallerid).'", "'.$userid.'", "'.date('Y-m-d h:i:s').'", "'.$userid.'", "'.date('Y-m-d h:i:s').'")';
            $talleresInsert = $this->db->prepare($queryInsertTalleres);
            $talleresInsert->execute();
            $_response['taller'] = 'inserted';
        }

        // Academias Alumnos
        $_response['academia'] = 'empty';
        if(intval($input->academiaid) > 0){
            $queryInsertAcademias = 'INSERT INTO tbl_academias (col_alumnoid, col_periodoid, col_materiaid, col_created_by, col_created_at, col_updated_by, col_updated_at)
            VALUES("'.intval($input->alumnoid).'", "'.intval($input->periodoid).'", "'.intval($input->academiaid).'", "'.$userid.'", "'.date('Y-m-d h:i:s').'", "'.$userid.'", "'.date('Y-m-d h:i:s').'")';
            $academiasInsert = $this->db->prepare($queryInsertAcademias);
            $academiasInsert->execute();
            $_response['academia'] = 'inserted';
        }

        $_response['status'] = 'true';

        return $this->response->withJson($_response);

    });

    $this->post('/update', function (Request $request, Response $response, $args) {
        $userid = getCurrentUserID();
        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        if($input->grupo == '') $input->grupo = $input->grupoid;

        $queryAskPeriodoSemestre = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.getPeriodoTaxoID($input->periodoid, $this->db).'" AND col_grupo="'.trim($input->grupo).'" AND col_grado="'.intval($input->grado).'"';
        $objPeriodoSemestre = $this->db->prepare($queryAskPeriodoSemestre);
        $objPeriodoSemestre->execute();
        $dataPeriodoSemestre = $objPeriodoSemestre->fetch(PDO::FETCH_OBJ);

        $taxID = $dataPeriodoSemestre->col_groupid;
        $input->periodo = $dataPeriodoSemestre->col_id;
        $input->periodoid = $dataPeriodoSemestre->col_id;

        $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval($input->alumnoid).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $alumnoCurrentData = $sth->fetch(PDO::FETCH_OBJ);

        if($alumnoCurrentData->col_periodoid != $input->periodoid){
            $data = array(
                'col_periodoid' => $input->periodoid,
                'col_updated_at' => date("Y-m-d H:i:s"),
                'col_updated_by' => $input->userid
            );

            //$query = 'UPDATE tbl_alumnos SET '.prepareUpdate($data).' WHERE col_id="'.intval($input->alumnoid).'"';
            //$alumno = $this->db->prepare($query);
            //$alumno->execute();


            $query = 'DELETE FROM tbl_talleres WHERE col_alumnoid="'.intval($input->alumnoid).'" AND col_periodoid="'.intval($alumnoCurrentData->col_periodoid).'"';
            $del = $this->db->prepare($query);
            $del->execute();

            $query = 'DELETE FROM tbl_academias WHERE col_alumnoid="'.intval($input->alumnoid).'" AND col_periodoid="'.intval($alumnoCurrentData->col_periodoid).'"';
            $del = $this->db->prepare($query);
            $del->execute();

            // Taxonomia de Alumnos
            //$queryTax = 'SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid="'.intval($input->alumnoid).'" AND col_periodoid="'.intval($alumnoCurrentData->col_periodoid).'"';
            //$tax = $this->db->prepare($queryTax);
            //$tax->execute();
            //$taxData = $sth->fetch(PDO::FETCH_OBJ);

            //$updatePeriodoTax = $this->db->prepare('UPDATE tbl_alumnos_taxonomia SET col_periodoid="'.$input->periodoid.'", col_updated_at="'.date('Y-m-d h:i:s').'", col_updated_by="'.$userid.'" WHERE col_alumnoid="'.intval($taxData->col_id).'"');
            //$updatePeriodoTax->execute();

            $_response['taxonomia'] = 'updated';
        }

        // Talleres Alumnos
        $_response['taller'] = 'empty';
        if(intval($input->tallerid) > 0){
            $queryTalleres = 'SELECT * FROM tbl_talleres WHERE col_alumnoid="'.intval($input->alumnoid).'" AND col_periodoid="'.intval($input->periodoid).'"';
            $talleres = $this->db->prepare($queryTalleres);
            $talleres->execute();
            if($talleres->rowCount() > 0){
                $talleresData = $talleres->fetch(PDO::FETCH_OBJ);
                $queryUpdateTalleres = 'UPDATE tbl_talleres SET col_materiaid="'.intval($input->tallerid).'", col_updated_by="'.$userid.'", col_updated_at="'.date('Y-m-d h:i:s').'" WHERE col_id="'.intval($talleresData->col_id).'"';
                $updateTalleres = $this->db->prepare($queryUpdateTalleres);
                $updateTalleres->execute();
                $_response['taller'] = 'updated';
            }else{
                $queryInsertTalleres = 'INSERT INTO tbl_talleres (col_alumnoid, col_periodoid, col_materiaid, col_created_by, col_created_at, col_updated_by, col_updated_at)
                VALUES("'.intval($input->alumnoid).'", "'.intval($input->periodoid).'", "'.intval($input->tallerid).'", "'.$userid.'", "'.date('Y-m-d h:i:s').'", "'.$userid.'", "'.date('Y-m-d h:i:s').'")';
                $talleresInsert = $this->db->prepare($queryInsertTalleres);
                $talleresInsert->execute();
                $_response['taller'] = 'inserted';
            }
        }

        // Academias Alumnos
        $_response['academia'] = 'empty';
        if(intval($input->academiaid) > 0){

            $queryAcademias = 'SELECT * FROM tbl_academias WHERE col_alumnoid="'.intval($input->alumnoid).'"  AND col_periodoid="'.intval($input->periodoid).'"';
            $academias = $this->db->prepare($queryAcademias);
            $academias->execute();
            if($academias->rowCount() > 0){
                $academiasData = $academias->fetch(PDO::FETCH_OBJ);
                $queryUpdateAcademias = 'UPDATE tbl_academias SET col_materiaid="'.intval($input->academiaid).'", col_updated_by="'.$userid.'", col_updated_at="'.date('Y-m-d h:i:s').'"  WHERE col_id="'.intval($academiasData->col_id).'"';
                $updateAcademias = $this->db->prepare($queryUpdateAcademias);
                $updateAcademias->execute();
                $_response['academia'] = 'updated';
            }else{
                $queryInsertAcademias = 'INSERT INTO tbl_academias (col_alumnoid, col_periodoid, col_materiaid, col_created_by, col_created_at, col_updated_by, col_updated_at)
                VALUES("'.intval($input->alumnoid).'", "'.intval($input->periodoid).'", "'.intval($input->academiaid).'", "'.$userid.'", "'.date('Y-m-d h:i:s').'", "'.$userid.'", "'.date('Y-m-d h:i:s').'")';
                $academiasInsert = $this->db->prepare($queryInsertAcademias);
                $academiasInsert->execute();
                $_response['academia'] = 'inserted';
            }
        }

        $_response['status'] = 'true';

        return $this->response->withJson($_response);

    });

    $this->delete('/delete', function (Request $request, Response $response, array $args) {

        $query = 'DELETE FROM tbl_alumnos_taxonomia WHERE col_id="'.intval($_REQUEST['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->delete('/deleteTaller', function (Request $request, Response $response, array $args) {

        $query = 'DELETE FROM tbl_talleres WHERE col_id="'.intval($_REQUEST['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();

        return $this->response->withJson(array('status' => 'true'));

    });

    $this->delete('/deleteAcademia', function (Request $request, Response $response, array $args) {

        $query = 'DELETE FROM tbl_academias WHERE col_id="'.intval($_REQUEST['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();

        return $this->response->withJson(array('status' => 'true'));

    });




});


