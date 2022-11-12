<?php
/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de configuración.
 *
 * Lista de funciones
 *
 * /configuracion
 * - /guardar
 * - /guardarPeriodos
 * - /get
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/configuracion', function () {

    $this->post('/guardar', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $config = $c->fetch(PDO::FETCH_OBJ);

        if($input->postal->value){
            $postal = uploadFile($input->postal, $config->col_postal, 'postal');
            $query = 'UPDATE tbl_config SET
            col_postal="'.$postal.'",
            col_updated_at="'.date("Y-m-d H:i:s").'",
            col_updated_by="'.$input->userid.'" WHERE col_id="1"';

            $dblog = new DBLog($query, 'tbl_config', '', '', 'Configuración', $this->db);
            $dblog->where = array('col_id' => 1);
            $dblog->prepareLog();

            $conf_postal = $this->db->prepare($query);
            $conf_postal->execute();

            $dblog->saveLog();
        }

        $query = 'UPDATE tbl_config SET
        col_candados_asistencias="'.intval($input->candadosAsistencias).'",
        col_reportes_servicio_social="'.intval($input->reportesServicioSocial).'",
        col_reportes_practicas="'.intval($input->reportesPracticas).'",
        col_calificaciones_estatus="'.intval($input->calificacionesEstatus).'",
        col_correos_cumpleanos="'.addslashes($input->correosCumpleanos).'",
        col_multa_biblioteca="'.floatval($input->multaBiblioteca).'",
        col_encargado_control_escolar="'.addslashes($input->encargadoControlEscolar).'",
        col_correo_practicas="'.trim($input->correoPracticas).'",
        col_mtop="'.intval($input->mtop).'",
        col_mbottom="'.intval($input->mbottom).'",
        col_mright="'.intval($input->mright).'",
        col_mleft="'.intval($input->mleft).'",
        col_mtop_alt="'.intval($input->mtopAlt).'",
        col_mbottom_alt="'.intval($input->mbottomAlt).'",
        col_mright_alt="'.intval($input->mrightAlt).'",
        col_mleft_alt="'.intval($input->mleftAlt).'",
        col_mtop_cert="'.intval($input->mtopCert).'",
        col_mbottom_cert="'.intval($input->mbottomCert).'",
        col_mright_cert="'.intval($input->mrightCert).'",
        col_mleft_cert="'.intval($input->mleftCert).'",
        col_folio_vale_documentos="'.intval($input->valeDocumentosFolio).'",
        col_practicas="'.intval($input->practicas).'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$input->userid.'" WHERE col_id="1"';

        $dblog = new DBLog($query, 'tbl_config', '', '', 'Configuración', $this->db);
        $dblog->where = array('col_id' => 1);
        $dblog->prepareLog();

        $config = $this->db->prepare($query);
        $config->execute();

        $dblog->saveLog();

        $_response['status'] = 'true';

        return $this->response->withJson($_response);

    });

    $this->post('/guardarPeriodos', function (Request $request, Response $response, $args) {
        global $dblog;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $config = $c->fetch(PDO::FETCH_OBJ);

        $query = 'UPDATE tbl_config SET
        col_periodo="'.$input->periodo.'",
        col_periodo_cuatri="'.$input->periodoCuatri.'",
        col_periodo_maestria="'.$input->periodoMaestria.'",
        col_periodo_doctorado="'.$input->periodoDoctorado.'",
        col_primer_parcial_semestral="'.substr($input->primerParcialSemestral[0], 0, 10).','.substr($input->primerParcialSemestral[1], 0, 10).'",
        col_primer_parcial_cuatrimestral="'.substr($input->primerParcialCuatrimestral[0], 0, 10).','.substr($input->primerParcialCuatrimestral[1], 0, 10).'",
        col_primer_parcial_maestria="'.substr($input->primerParcialMaestria[0], 0, 10).','.substr($input->primerParcialMaestria[1], 0, 10).'",
        col_primer_parcial_doctorado="'.substr($input->primerParcialDoctorado[0], 0, 10).','.substr($input->primerParcialDoctorado[1], 0, 10).'",
        col_segundo_parcial_semestral="'.substr($input->segundoParcialSemestral[0], 0, 10).','.substr($input->segundoParcialSemestral[1], 0, 10).'",
        col_segundo_parcial_cuatrimestral="'.substr($input->segundoParcialCuatrimestral[0], 0, 10).','.substr($input->segundoParcialCuatrimestral[1], 0, 10).'",
        col_segundo_parcial_maestria="'.substr($input->segundoParcialMaestria[0], 0, 10).','.substr($input->segundoParcialMaestria[1], 0, 10).'",
        col_segundo_parcial_doctorado="'.substr($input->segundoParcialDoctorado[0], 0, 10).','.substr($input->segundoParcialDoctorado[1], 0, 10).'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$input->userid.'" WHERE col_id="1"';

        $dblog = new DBLog($query, 'tbl_config', '', '', 'Configuración', $this->db);
        $dblog->where = array('col_id' => 1);
        $dblog->prepareLog();

        $config = $this->db->prepare($query);
        $config->execute();

        $dblog->saveLog();

        $query = 'UPDATE tbl_periodos SET col_estatus=0 WHERE col_estatus=1';
        $periodoUpdate = $this->db->prepare($query);
        $periodoUpdate->execute();

        if($input->periodo > 0){
            $query = 'UPDATE tbl_periodos SET col_estatus=1 WHERE col_groupid="'.$input->periodo.'"';
            $periodoUpdate = $this->db->prepare($query);
            $periodoUpdate->execute();
        }
        if($input->periodoCuatri > 0){
            $query = 'UPDATE tbl_periodos SET col_estatus=1 WHERE col_groupid="'.$input->periodoCuatri.'"';
            $periodoUpdate = $this->db->prepare($query);
            $periodoUpdate->execute();
        }
        if($input->periodoMaestria > 0){
            $query = 'UPDATE tbl_periodos SET col_estatus=1 WHERE col_groupid="'.$input->periodoMaestria.'"';
            $periodoUpdate = $this->db->prepare($query);
            $periodoUpdate->execute();
        }
        if($input->periodoDoctorado > 0){
            $query = 'UPDATE tbl_periodos SET col_estatus=1 WHERE col_groupid="'.$input->periodoDoctorado.'"';
            $periodoUpdate = $this->db->prepare($query);
            $periodoUpdate->execute();
        }


        $_response['status'] = 'true';

        return $this->response->withJson($_response);

    });

    $this->get('/get', function (Request $request, Response $response, $args) {
        global $download_url;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        $query = 'SELECT c.*, p1.col_nombre AS periodo_nombre, p2.col_nombre AS periodo_nombre_cuatri, p3.col_nombre AS periodo_nombre_maestria, p4.col_nombre AS periodo_nombre_doctorado FROM tbl_config c
        LEFT OUTER JOIN tbl_periodos_nombres p1 ON p1.col_id=c.col_periodo
        LEFT OUTER JOIN tbl_periodos_nombres p2 ON p2.col_id=c.col_periodo_cuatri
        LEFT OUTER JOIN tbl_periodos_nombres p3 ON p3.col_id=c.col_periodo_maestria
        LEFT OUTER JOIN tbl_periodos_nombres p4 ON p4.col_id=c.col_periodo_doctorado
        WHERE c.col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $config = $c->fetch(PDO::FETCH_OBJ);
        if($config->col_postal != ''){
            $config->col_postal = $download_url.$config->col_postal;
        }

        return $this->response->withJson($config);

    });

});


