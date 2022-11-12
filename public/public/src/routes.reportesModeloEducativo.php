<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de reportes para modelos educativos.
 *
 * Lista de funciones
 *
 * /reportesME
 * /academias
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;
set_time_limit(0);
$app->group('/reportesME', function () {


    $this->get('/academias', function (Request $request, Response $response, array $args) {
        include_once(__DIR__ . '/../src/xlsxwriter.class.php');

        $fechas = explode(',', trim($_REQUEST['fechas']));
        $from = explode('GMT', $fechas[0]);
        $to = explode('GMT', $fechas[1]);
        $from = date('Y-m-d', strtotime(trim($from[0])));
        $to = date('Y-m-d', strtotime($to[0]));

        $periodos = getCurrentPeriodos($this->db);
        $query = "SELECT * FROM `tbl_maestros_taxonomia` WHERE col_materia_clave LIKE 'AC%' AND col_periodoid IN (".implode(',', $periodos).") GROUP BY col_maestroid";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todasAcademias = $sth->fetchAll();


        $filename = 'reporte_academias_'.date('Y-m-d').'.xls';
        header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($filename).'"');
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        $modalidades = array(0 => "", 1 => "Semestral Licenciatura", 2 => "Cuatrimestral Licenciatura", 3 => "Semestral Maestria", 4 => "Semestral Doctorado");
        $writer = new XLSXWriter();
        if(!file_exists(__DIR__ . '/../temp')) {
            @mkdir(__DIR__ . '/../temp', 0777);
        }
        $writer->setTempDir(__DIR__ . '/../temp');
        $writer->setAuthor('FLDCH');
        $gruposPeriodos = getCurrentPeriodos($this->db, 'todos');

        $styles_head = array( 'height' => '1', 'valign'=>'center', 'font'=>'Arial','font-size'=>12,'font-style'=>'bold', 'fill'=>'#eee', 'halign'=>'center', 'border'=>'left,right,top,bottom', 'widths'=>[10,60,30,30,30,30,30,30]);
        $styles_heading_big = array( 'height' => '50', 'valign'=>'center', 'font'=>'Arial','font-size'=>20,'font-style'=>'bold', 'fill'=>'#eee', 'halign'=>'left', 'border'=>'bottom');
        $styles_heading = array( 'height' => '30', 'valign'=>'center', 'font'=>'Arial','font-size'=>12,'font-style'=>'bold', 'fill'=>'#eee', 'halign'=>'left', 'border'=>'left,right,top,bottom');
        $styles1 = array( 'height' => '30', 'valign'=>'center', 'font'=>'Arial','font-size'=>12,'font-style'=>'bold', 'fill'=>'#eee', 'halign'=>'center', 'border'=>'left,right,top,bottom', 'widths'=>[40,60]);
        $styles2 = array( 'height' => '30', 'valign'=>'center', 'font'=>'Arial','font-size'=>12,'font-style'=>'bold', 'fill'=>'#eee', 'halign'=>'center', 'border'=>'left,right,top,bottom', 'widths'=>[10,60,30,30,30,30,30,30]);
        $styles3 = array( 'height' => '30', 'valign'=>'center', 'font'=>'Arial','font-size'=>10,'font-style'=>'bold', 'fill'=>'#eee', 'halign'=>'center', 'border'=>'left,right,top,bottom', 'widths'=>[40,60,40,60,60,60,70,40]);
        $styles4 = array( 'height' => '30', 'valign'=>'center', 'font'=>'Arial','font-size'=>10,'font-style'=>'normal', 'fill'=>'#fff', 'halign'=>'left', 'border'=>'left,right,top,bottom');
        $styles5 = array( 'height' => '30', 'valign'=>'center', 'collapsed' => true, 'font'=>'Arial','font-size'=>10,'font-style'=>'normal', 'fill'=>'#fff', 'halign'=>'center', 'border'=>'left,right,top,bottom','widths'=>[20,100,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40,40]);
        // $gruposPeriodos = array(128);

        // $sheet = 'Por Inasistencias';
        // $writer->writeSheetHeader($sheet, array('' => string), $styles_head);
        // $writer->writeSheetRow($sheet, array('Reporte de Academias- Inasistencias: '.$from.' - '.$to, '', '', ''), $styles_heading_big);
        // $writer->writeSheetRow($sheet, array(''));
        // $writer->writeSheetRow($sheet, array(''));



        foreach($gruposPeriodos as $periodoid){
            unset($arrayClaves);

            $sth = $this->db->prepare("SELECT * FROM tbl_periodos WHERE col_id='".$periodoid."'");
            $sth->execute();
            $periodoData = $sth->fetch(PDO::FETCH_OBJ);

            $sth = $this->db->prepare("SELECT * FROM tbl_maestros_taxonomia WHERE col_materia_clave LIKE 'AC%' AND col_id IN (SELECT col_materiaid FROM tbl_asistencia WHERE col_fecha >= '".$from."' AND col_fecha <= '".$to."') AND col_periodoid='".$periodoid."' GROUP BY col_materia_clave");
            $sth->execute();
            $claves = $sth->fetchAll();
            if($sth->rowCount()) {


                $sheet = 'AC: '.$modalidades[$periodoData->col_modalidad]." ".$periodoData->col_grado.$periodoData->col_grupo;
                $sheet = 'Por Inasistencias';
                $writer->writeSheetHeader($sheet, array('' => string), $styles_head);
                $writer->writeSheetRow($sheet, array('Reporte de Academias- Inasistencias: '.$from.' - '.$to, '', '', ''), $styles_heading_big);
                $writer->writeSheetRow($sheet, array('', fixEncode($periodoData->col_nombre), fixEncode($sheet)), $styles_heading );
                $writer->writeSheetRow($sheet, array(''));
                $writer->writeSheetRow($sheet, array(''));


                foreach($claves as $clave) {
                    if(!in_array(claveMateria($clave['col_materia_clave']), $arrayClaves)) {
                        $arrayClaves[] = claveMateria($clave['col_materia_clave']);

                        $sth = $this->db->prepare("SELECT * FROM tbl_materias WHERE col_clave LIKE '".claveMateria($clave['col_materia_clave'])."%' AND col_semestre='".$periodoData->col_grado."' AND col_plan_estudios='".$periodoData->col_plan_estudios."'");
                        $sth->execute();
                        $materia = $sth->fetch(PDO::FETCH_OBJ);

                        $query1 = "SELECT * FROM tbl_asistencia WHERE col_materiaid='".$clave['col_id']."'";
                        $sth = $this->db->prepare($query1);
                        $sth->execute();
                        $_listas = $sth->fetchAll();
                         foreach($_listas AS $_lista) {
                            // $sth = $this->db->prepare("SELECT col_id, CONCAT(col_apellidos, ' ', col_nombres) AS nombreAlumno FROM tbl_alumnos WHERE col_estatus='activo' AND col_id IN (SELECT col_alumnoid FROM tbl_asistencia_alumnos WHERE col_listaid='".$_lista['col_id']."') ORDER BY col_apellidos ASC");
                            $queryx = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($clave['col_materia_clave']).'%" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
                            $sthx = $this->db->prepare($queryx);
                            $sthx->execute();
                            $dataMateriaMulti = $sthx->fetchAll();
                            unset($multis);
                            foreach($dataMateriaMulti as $mm) {
                                $multis[] = $mm['col_id'];
                            }
                            $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');
                            $losPeriodos = getCurrentPeriodos($this->db, $types[$periodoData->col_modalidad]);

                            $query = "SELECT a.col_id, CONCAT(a.col_apellidos, ' ', a.col_nombres) AS nombreAlumno FROM tbl_alumnos_taxonomia t ".
                            "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                            "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_academias ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
                            "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";
                            $sth = $this->db->prepare($query);
                            $sth->execute();
                            if($sth->rowCount()){
                                $alumnos = $sth->fetchAll();
                                break;
                            }
                        }


                        $query2 = "SELECT * FROM tbl_asistencia WHERE col_materiaid='".$clave['col_id']."' AND col_fecha >= '".$from."' AND col_fecha <= '".$to."' ORDER BY col_fecha DESC";
                        $sth = $this->db->prepare($query2);
                        $sth->execute();
                        $dias = $sth->fetchAll();
                        $totalDias = $sth->rowCount();


                            $sth = $this->db->prepare("SELECT * FROM tbl_users WHERE col_id='".$clave['col_maestroid']."'");
                            $sth->execute();
                            $maestro = $sth->fetch(PDO::FETCH_OBJ);

                            $nombreMaestro = fixEncode($maestro->col_firstname.' '.$maestro->col_lastname);

                            unset($prethead);
                            $prethead[0] = $materia->col_clave;
                            $prethead[1] = fixEncode($materia->col_nombre);
                            $prethead[2] = $nombreMaestro;
                            $pi = 3;
                            for($xi = 0; $xi < $totalDias; $xi++){
                                $prethead[$pi] = '';
                                $pi++;
                            }
                            $prethead[$pi+1] = '';
                            $prethead[$pi+2] = '';
                            $writer->writeSheetRow($sheet, $prethead, $styles_heading);

                            unset($thead);
                            $thead[0] = 'No.';
                            $thead[1] = 'NOMBRE DEL ALUMNO';
                            $thead[2] = 'Total de Faltas';
                            $thead[3] = '% de Asistencias';
                            $thead[4] = '# Clases';
                            $thead[5] = 'Pagios';
                            $writer->writeSheetRow($sheet, $thead, $styles2);


                            unset($_listasid);
                            foreach($dias as $dia){
                                $_listasid[] = $dia['col_id'];
                            }

                            $x = 0;

                            unset($dataAlumnoRow);
                            unset($dataTotalesAlumnos);
                            foreach($alumnos AS $alumno) {
                                $dataAlumnoRow[0] = ($x+1);
                                $dataAlumnoRow[1] = fixEncode($alumno['nombreAlumno']);
                                $a = 0;
                                foreach($_listasid as $itemID) {


                                    $sth = $this->db->prepare("SELECT * FROM tbl_asistencia WHERE col_id='".$itemID."'");
                                    $sth->execute();
                                    $_listaPreData = $sth->fetch(PDO::FETCH_OBJ);
                                    $horasClase = 1;
                                    if(esClaseDosHoras($materia->col_id, $periodoid, date('N', strtotime($_listaPreData->col_fecha)), $this->db)){
                                        $horasClase = 2;
                                    }

                                    $sth = $this->db->prepare("SELECT * FROM tbl_asistencia_alumnos WHERE col_alumnoid='".$alumno['col_id']."' AND col_listaid='".$itemID."'");
                                    $sth->execute();
                                    if($sth->rowCount()){
                                        $dataListAlumno = $sth->fetch(PDO::FETCH_OBJ);


                                        if($horasClase == 2){
                                            if($dataListAlumno->col_asistencia == 'A') $dataAlumno[$a] = 0;
                                            if($dataListAlumno->col_asistencia == 'R') $dataAlumno[$a] = 0.3;
                                            if($dataListAlumno->col_asistencia == 'P') $dataAlumno[$a] = 'P';
                                            if($dataListAlumno->col_segunda == 0 && $dataListAlumno->col_asistencia == 'A') {
                                                $dataAlumno[$a] = 1;
                                            } else if($dataListAlumno->col_segunda == 0 && $dataListAlumno->col_asistencia == 'R') {
                                                $dataAlumno[$a] = '1.3';
                                            } else if($dataListAlumno->col_segunda == 0 && $dataListAlumno->col_asistencia == 'F') {
                                                $dataAlumno[$a] = 2;
                                            } else if($dataListAlumno->col_segunda == 1 && $dataListAlumno->col_asistencia == 'F') {
                                                $dataAlumno[$a] = 1;
                                            }
                                        } else {
                                            if($dataListAlumno->col_asistencia == 'A') $dataAlumno[$a] = 0;
                                            if($dataListAlumno->col_asistencia == 'R') $dataAlumno[$a] = 0.3;
                                            if($dataListAlumno->col_asistencia == 'F') $dataAlumno[$a] = 1;
                                            if($dataListAlumno->col_asistencia == 'P') $dataAlumno[$a] = 'P';
                                        }
                                        if($dataListAlumno->col_asistencia == 'R') $dataAlumnoCountRetardo[$a] = intval($dataAlumnoCountRetardo[$a]) + 1;
                                    }else{
                                        $dataAlumno[$a] = 0;
                                    }
                                    if(floatval($dataAlumno[$a]) > 0) {
                                        $dataTotalesAlumnos[$alumno['col_id']]['faltas'] = floatval($dataAlumno[$a]) + floatval($dataTotalesAlumnos[$alumno['col_id']]['faltas']);
                                    }
                                    $dataTotalesAlumnos[$alumno['col_id']]['horas'] = intval($dataTotalesAlumnos[$alumno['col_id']]['horas']) + $horasClase;
                                    $a++;
                                }

                                $dataAlumnoRow[2] = floatval($dataTotalesAlumnos[$alumno['col_id']]['faltas']);
                                $porcentaje = number_format((100 - (($dataTotalesAlumnos[$alumno['col_id']]['faltas'] / $dataTotalesAlumnos[$alumno['col_id']]['horas']) * 100)), 2);
                                if(floatval($porcentaje) < 0) $porcentaje = '0.00';
                                $dataAlumnoRow[3] = $porcentaje.'%';
                                $dataAlumnoRow[4] = $dataTotalesAlumnos[$alumno['col_id']]['horas'];
                                $dataAlumnoRow[5] = getPlagiosPorTaxMateria($alumno['col_id'], $clave['col_id'], $this->db);
                                $writer->writeSheetRow($sheet, $dataAlumnoRow, $styles5);
                                $x++;
                            }


                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array(''));

                    }
                }
            }
        }




        $sheet = 'Sin Aprobar';
        $writer->writeSheetHeader($sheet, array('' => string), $styles_head);
        $writer->writeSheetRow($sheet, array('Reporte de Academias- Sin Aprobar: '.$from.' - '.$to, '', '', ''), $styles_heading_big);
        $writer->writeSheetRow($sheet, array(''));
        $writer->writeSheetRow($sheet, array(''));

        $writer->writeSheetRow($sheet, array('Materia:', 'Nombre Materia'), $styles2);
        $writer->writeSheetRow($sheet, array('Alumno', 'Grupo', 'Calificación', '# Actividades'), $styles2);




        $sheet = 'Si Acreditan';
        $writer->writeSheetHeader($sheet, array('' => string), $styles_head);
        $writer->writeSheetRow($sheet, array('Reporte de Academias- Si Acreditan: '.$from.' - '.$to, '', '', ''), $styles_heading_big);
        $writer->writeSheetRow($sheet, array(''));
        $writer->writeSheetRow($sheet, array(''));

        $writer->writeSheetRow($sheet, array('Materia:', 'Nombre Materia'), $styles2);
        $writer->writeSheetRow($sheet, array('Alumno', 'Grupo', 'Calificación', '# Actividades'), $styles2);



        $writer->writeToStdOut();
        exit();
    });


});
//Termina routes.reportesME.php
