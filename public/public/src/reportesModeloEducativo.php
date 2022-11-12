<?php

/**
 *
 * Archivo que incluye las funciones que permiten generar los archivos Excel de los reportes de Modelo Educativo
 * mismos que se pueden descargar desde el modulo de Reportes.
 *
 * Lista de funciones
 *
 * /reportesME
 * - /academias
 * - /asistenciasReporte1
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

        $filename = 'reporte_asistencias_maestros_'.date('Y-m-d').'.xls';
        header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($filename).'"');
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        $writer = new XLSXWriter();
        if(!file_exists(__DIR__ . '/../temp')) {
            @mkdir(__DIR__ . '/../temp', 0777);
        }
        $writer->setTempDir(__DIR__ . '/../temp');
        $writer->setAuthor('FLDCH');
        $sheet = 'Reporte';

        $styles_head = array( 'height' => '1', 'valign'=>'center', 'font'=>'Arial','font-size'=>12,'font-style'=>'bold', 'fill'=>'#eee', 'halign'=>'center', 'border'=>'left,right,top,bottom', 'widths'=>[10,60,30,30,30,30,30,30]);
        $styles_heading_big = array( 'height' => '50', 'valign'=>'center', 'font'=>'Arial','font-size'=>20,'font-style'=>'bold', 'fill'=>'#eee', 'halign'=>'left', 'border'=>'bottom');
        $styles_heading = array( 'height' => '30', 'valign'=>'center', 'font'=>'Arial','font-size'=>12,'font-style'=>'bold', 'fill'=>'#eee', 'halign'=>'left', 'border'=>'left,right,top,bottom');
        $styles1 = array( 'height' => '30', 'valign'=>'center', 'font'=>'Arial','font-size'=>12,'font-style'=>'bold', 'fill'=>'#eee', 'halign'=>'center', 'border'=>'left,right,top,bottom', 'widths'=>[40,60]);
        $styles2 = array( 'height' => '30', 'valign'=>'center', 'font'=>'Arial','font-size'=>12,'font-style'=>'bold', 'fill'=>'#eee', 'halign'=>'center', 'border'=>'left,right,top,bottom', 'widths'=>[10,60,30,30,30,30,30,30]);


        $writer->writeSheetHeader($sheet, array('' => string), $styles_head);
        $writer->writeSheetRow($sheet, array('Reporte de Asistencias Maestros - Generado: '.$from.' - '.$to, '', '', ''), $styles_heading_big);
        $writer->writeSheetRow($sheet, array('', fixEncode($periodoData->col_nombre), fixEncode($sheet)), $styles_heading );
        $writer->writeSheetRow($sheet, array(''));
        $writer->writeSheetRow($sheet, array(''));

        $writer->writeSheetRow($sheet, array('Nombre', 'Tipo de Usuario', 'Entro desde', 'Entro con', 'Fecha', 'Hora Primera Entrada', 'Hora de Salida', 'Hora Ultima Entrada', 'Ultima Entrada con'), $styles2);

        $query = "SELECT * FROM tbl_bitacora_sesiones WHERE col_fecha_entrada >= '".$from."' AND col_fecha_entrada <= '".$to."'";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $datos = $sth->fetchAll();

        $fuentes = array('192.168.12.80'=>'Portal Web', '192.168.12.81'=>'Intranet');

        foreach($datos as $item) {

            if($item['col_tipo'] == 0) {
                $query = 'SELECT * FROM tbl_users WHERE col_id="'.$item['col_usuarioid'].'"';
                $stha = $this->db->prepare($query);
                $stha->execute();
                $user = $stha->fetch(PDO::FETCH_OBJ);

                $tipo = 'Administrativo';
                if($user->col_maestro == 1) $tipo = 'Maestro';
                $nombreUsuario = fixEncode($user->col_firstname.' '.$user->col_lastname);
            } else {
                $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$item['col_usuarioid'].'"';
                $stha = $this->db->prepare($query);
                $stha->execute();
                $user = $stha->fetch(PDO::FETCH_OBJ);
                $nombreUsuario = fixEncode($user->col_nombres.' '.$user->col_apellidos);
                $tipo = 'Alumno';
            }

            $tipoEntrada = $fuentes[$item['col_ip']];

            $writer->writeSheetRow($sheet, array($nombreUsuario, $tipo, $tipoEntrada, $item['col_fuente'], $item['col_fecha_entrada'], $item['col_hora_entrada'], $item['col_hora_salida'], date('H:i:s', strtotime($item['col_ultimo_inicio'])), $item['col_ultimo_device']));


        }
        $writer->writeToStdOut();
        exit();
    });

    $this->get('/asistenciasReporte1', function (Request $request, Response $response, array $args) {
        include_once(__DIR__ . '/../src/xlsxwriter.class.php');

        $print_regular  = false;
        $print_taller   = false;
        $print_academia = false;
        $print_club     = false;

        $tipoReporte = intval($_REQUEST['tipo']);
        $fechas = explode(',', trim($_REQUEST['fechas']));
        $from = explode('GMT', $fechas[0]);
        $to = explode('GMT', $fechas[1]);
        $from = date('Y-m-d', strtotime(trim($from[0])));
        $to = date('Y-m-d', strtotime($to[0]));

        switch($tipoReporte) {
            case '0':
                $print_regular  = true;
                $print_taller   = true;
                $print_academia = true;
                $print_club     = true;
            break;
            case '1':
                $print_regular  = true;
            break;
            case '2':
                $print_taller  = true;
            break;
            case '3':
                $print_academia  = true;
            break;
            case '4':
                $print_club  = true;
            break;
        }

        // enviarCorreo(array('to' => 'jorge.x3@gmail.com', 'nombre' => 'Jorge Aldana'), 'Reporte Generado', $from.' '.$to);

        $filename = 'reporte_asistencias_'.date('Y-m-d').'.xls';
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

        // Solo Curriculares
    if($print_regular == true) {
        foreach($gruposPeriodos as $periodoid){
            unset($arrayClaves);

            $sth = $this->db->prepare("SELECT * FROM tbl_periodos WHERE col_id='".$periodoid."'");
            $sth->execute();
            $periodoData = $sth->fetch(PDO::FETCH_OBJ);

            $sth = $this->db->prepare("SELECT * FROM tbl_maestros_taxonomia WHERE (col_materia_clave NOT LIKE 'CL%' AND col_materia_clave NOT LIKE 'TL%' AND col_materia_clave NOT LIKE 'AC%') AND col_id IN (SELECT col_materiaid FROM tbl_asistencia WHERE col_fecha >= '".$from."' AND col_fecha <= '".$to."') AND col_periodoid='".$periodoid."' GROUP BY col_materia_clave");
            $sth->execute();
            $claves = $sth->fetchAll();
            if($sth->rowCount()) {
            $sheet = $modalidades[$periodoData->col_modalidad]." ".$periodoData->col_grado.$periodoData->col_grupo;

            $writer->writeSheetHeader($sheet, array('' => string), $styles_head);
            $writer->writeSheetRow($sheet, array('Reporte de Asistencias - Generado: '.$from.' - '.$to, '', '', ''), $styles_heading_big);
            $writer->writeSheetRow($sheet, array('', fixEncode($periodoData->col_nombre), fixEncode($sheet)), $styles_heading );
            $writer->writeSheetRow($sheet, array(''));
            $writer->writeSheetRow($sheet, array());



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
                        $query = "SELECT a.col_id, CONCAT(a.col_apellidos, ' ', a.col_nombres) AS nombreAlumno FROM tbl_alumnos_taxonomia t ".
                        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                        "WHERE a.col_estatus='activo' AND t.col_periodoid='".$periodoData->col_id."' ORDER BY a.col_apellidos ASC";
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
                        $i = 2;
                        unset($_listasid);
                        foreach($dias as $dia){
                            $str_horasClase = 1;
                            if(esClaseDosHoras($materia->col_id, $periodoid, date('N', strtotime($dia['col_fecha'])), $this->db)){
                                $str_horasClase = 2;
                            }

                            $thead[$i] = date('d/m/Y', strtotime($dia['col_fecha'])).' ('.$str_horasClase.')';
                            $_listasid[] = $dia['col_id'];
                            $i++;
                        }
                        $thead[$i] = 'Total de Faltas';
                        $thead[$i+1] = '% de Asistencias';
                        $thead[$i+2] = '# Horas';
                        $writer->writeSheetRow($sheet, $thead, $styles2);

                        $x = 0;
                        unset($dataAlumno);
                        unset($dataTotalesAlumnos);
                        foreach($alumnos AS $alumno) {
                            $dataAlumno[0] = ($x+1);
                            $dataAlumno[1] = fixEncode($alumno['nombreAlumno']);
                            $a = 2;
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

                            $dataAlumno[$a] = floatval($dataTotalesAlumnos[$alumno['col_id']]['faltas']);
                            $porcentaje = number_format((100 - (($dataTotalesAlumnos[$alumno['col_id']]['faltas'] / $dataTotalesAlumnos[$alumno['col_id']]['horas']) * 100)), 2);
                            if(floatval($porcentaje) < 0) $porcentaje = '0.00';
                            $dataAlumno[$a+1] = $porcentaje.'%';
                            $dataAlumno[$a+2] = $dataTotalesAlumnos[$alumno['col_id']]['horas'];
                            if($dataTotalesAlumnos[$alumno['col_id']]['faltas'] > 0) $writer->writeSheetRow($sheet, $dataAlumno, $styles5);
                            $x++;
                        }

                        $writer->writeSheetRow($sheet, array(''));
                        $writer->writeSheetRow($sheet, array(''));
                        $writer->writeSheetRow($sheet, array('', 'Comentarios'), $styles2);
                        foreach($dias as $dia){
                            $writer->writeSheetRow($sheet, array('', date('d/m/Y', strtotime($dia['col_fecha'])), ($dia['col_observaciones'] == ''?'-':$dia['col_observaciones'])), $styles5);
                        }


                        $writer->writeSheetRow($sheet, array(''));
                        $writer->writeSheetRow($sheet, array(''));
                        $writer->writeSheetRow($sheet, array(''));
                        $writer->writeSheetRow($sheet, array(''));
                        $writer->writeSheetRow($sheet, array(''));
                        $writer->writeSheetRow($sheet, array(''));

                }
            }
            }

        }
    }

    if($print_taller == true) {
        // Talleres
        foreach($gruposPeriodos as $periodoid){
            unset($arrayClaves);

                $sth = $this->db->prepare("SELECT * FROM tbl_periodos WHERE col_id='".$periodoid."'");
                $sth->execute();
                $periodoData = $sth->fetch(PDO::FETCH_OBJ);

                $sth = $this->db->prepare("SELECT * FROM tbl_maestros_taxonomia WHERE col_materia_clave LIKE 'TL%' AND col_id IN (SELECT col_materiaid FROM tbl_asistencia WHERE col_fecha >= '".$from."' AND col_fecha <= '".$to."') AND col_periodoid='".$periodoid."' GROUP BY col_materia_clave");
                $sth->execute();
                $claves = $sth->fetchAll();
                if($sth->rowCount()) {


                    $sheet = 'TL: '.$modalidades[$periodoData->col_modalidad]." ".$periodoData->col_grado.$periodoData->col_grupo;
                    $sheet = 'TALLERES';
                    $writer->writeSheetHeader($sheet, array('' => string), $styles_head);
                    $writer->writeSheetRow($sheet, array('Reporte de Asistencias - Generado: '.$from.' - '.$to, '', '', ''), $styles_heading_big);
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
                            unset($multis);
                            foreach($_listas AS $_lista) {
                                //$sth = $this->db->prepare("SELECT col_id, CONCAT(col_apellidos, ' ', col_nombres) AS nombreAlumno FROM tbl_alumnos WHERE col_estatus='activo' AND col_id IN (SELECT col_alumnoid FROM tbl_asistencia_alumnos WHERE col_listaid='".$_lista['col_id']."') ORDER BY col_apellidos ASC");
                                $queryx = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($clave['col_materia_clave']).'%" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
                                $sthx = $this->db->prepare($queryx);
                                $sthx->execute();
                                $dataMateriaMulti = $sthx->fetchAll();
                                foreach($dataMateriaMulti as $mm) {
                                    $multis[] = $mm['col_id'];
                                }
                                $multis = array_unique($multis);
                                $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');
                                $losPeriodos = getCurrentPeriodos($this->db, $types[$periodoData->col_modalidad]);

                                $queryAlumnos = "SELECT a.col_id, CONCAT(a.col_apellidos, ' ', a.col_nombres) AS nombreAlumno FROM tbl_alumnos_taxonomia t ".
                                "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                                "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_talleres ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
                                "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";
                                $sth = $this->db->prepare($queryAlumnos);
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
                                $i = 2;
                                unset($_listasid);
                                foreach($dias as $dia){
                                    $str_horasClase = 1;
                                    if(esClaseDosHoras($materia->col_id, $periodoid, date('N', strtotime($dia['col_fecha'])), $this->db)){
                                        $str_horasClase = 2;
                                    }

                                    $thead[$i] = date('d/m/Y', strtotime($dia['col_fecha'])).' ('.$str_horasClase.')';
                                    $_listasid[] = $dia['col_id'];
                                    $i++;
                                }
                                $thead[$i] = 'Total de Faltas';
                                $thead[$i+1] = '% de Asistencias';
                                $thead[$i+2] = '# Clases';
                                $writer->writeSheetRow($sheet, $thead, $styles2);

                                $x = 0;
                                unset($dataAlumno);
                                unset($dataTotalesAlumnos);
                                foreach($alumnos AS $alumno) {
                                    $dataAlumno[0] = ($x+1);
                                    $dataAlumno[1] = fixEncode($alumno['nombreAlumno']);
                                    $a = 2;
                                    foreach($_listasid as $itemID) {

                                        $sth = $this->db->prepare("SELECT * FROM tbl_asistencia WHERE col_id='".$itemID."'");
                                        $sth->execute();
                                        $_listaPreData = $sth->fetch(PDO::FETCH_OBJ);
                                        $horasClase = 1;
                                        if(esClaseDosHoras($materia->col_id, $periodoid, date('N', strtotime($_listaPreData->col_fecha)), $this->db)){
                                            $horasClase = 2;
                                        }

                                        $xquery = "SELECT * FROM tbl_asistencia_alumnos WHERE col_alumnoid='".$alumno['col_id']."' AND col_listaid='".$itemID."'";
                                        $sth = $this->db->prepare($xquery);
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
                                            // $dataAlumno[$a] = '-';
                                            $dataAlumno[$a] = 0;
                                        }
                                        if(floatval($dataAlumno[$a]) > 0) {
                                            $dataTotalesAlumnos[$alumno['col_id']]['faltas'] = floatval($dataAlumno[$a]) + floatval($dataTotalesAlumnos[$alumno['col_id']]['faltas']);
                                        }
                                        // $dataTotalesAlumnos[$alumno['col_id']]['clases']++;
                                        $dataTotalesAlumnos[$alumno['col_id']]['horas'] = intval($dataTotalesAlumnos[$alumno['col_id']]['horas']) + $horasClase;
                                        $a++;
                                    }

                                    $dataAlumno[$a] = floatval($dataTotalesAlumnos[$alumno['col_id']]['faltas']);
                                    $porcentaje = number_format((100 - (($dataTotalesAlumnos[$alumno['col_id']]['faltas'] / $dataTotalesAlumnos[$alumno['col_id']]['horas']) * 100)), 2);
                                    if(floatval($porcentaje) < 0) $porcentaje = '0.00';
                                    $dataAlumno[$a+1] = $porcentaje.'%';
                                    // $dataAlumno[$a+2] = $dataTotalesAlumnos[$alumno['col_id']]['clases'];
                                    $dataAlumno[$a+2] = $dataTotalesAlumnos[$alumno['col_id']]['horas'];
                                    if($dataTotalesAlumnos[$alumno['col_id']]['faltas'] > 0) $writer->writeSheetRow($sheet, $dataAlumno, $styles5);
                                    $x++;
                                }

                                $writer->writeSheetRow($sheet, array(''));
                                $writer->writeSheetRow($sheet, array(''));
                                $writer->writeSheetRow($sheet, array('', 'Comentarios'), $styles2);
                                foreach($dias as $dia){
                                    $writer->writeSheetRow($sheet, array('', date('d/m/Y', strtotime($dia['col_fecha'])), ($dia['col_observaciones'] == ''?'-':$dia['col_observaciones'])), $styles5);
                                }


                                $writer->writeSheetRow($sheet, array(''));
                                $writer->writeSheetRow($sheet, array(''));
                                $writer->writeSheetRow($sheet, array(''));
                                $writer->writeSheetRow($sheet, array(''));
                                $writer->writeSheetRow($sheet, array(''));
                                $writer->writeSheetRow($sheet, array(''));

                        }
                    }
                }

            }
        }

        if($print_academia == true) {
        // Academias
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
                $sheet = 'ACADEMIAS';
                $writer->writeSheetHeader($sheet, array('' => string), $styles_head);
                $writer->writeSheetRow($sheet, array('Reporte de Asistencias - Generado: '.$from.' - '.$to, '', '', ''), $styles_heading_big);
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
                        unset($multis);
                         foreach($_listas AS $_lista) {
                            // $sth = $this->db->prepare("SELECT col_id, CONCAT(col_apellidos, ' ', col_nombres) AS nombreAlumno FROM tbl_alumnos WHERE col_estatus='activo' AND col_id IN (SELECT col_alumnoid FROM tbl_asistencia_alumnos WHERE col_listaid='".$_lista['col_id']."') ORDER BY col_apellidos ASC");
                            $queryx = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($clave['col_materia_clave']).'%" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
                            $sthx = $this->db->prepare($queryx);
                            $sthx->execute();
                            $dataMateriaMulti = $sthx->fetchAll();
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
                            $i = 2;
                            unset($_listasid);
                            foreach($dias as $dia){
                                $str_horasClase = 1;
                                if(esClaseDosHoras($materia->col_id, $periodoid, date('N', strtotime($dia['col_fecha'])), $this->db)){
                                    $str_horasClase = 2;
                                }

                                $thead[$i] = date('d/m/Y', strtotime($dia['col_fecha'])).' ('.$str_horasClase.')';
                                $_listasid[] = $dia['col_id'];
                                $i++;
                            }
                            $thead[$i] = 'Total de Faltas';
                            $thead[$i+1] = '% de Asistencias';
                            $thead[$i+2] = '# Clases';
                            $writer->writeSheetRow($sheet, $thead, $styles2);

                            $x = 0;
                            unset($dataAlumno);
                            unset($dataTotalesAlumnos);
                            foreach($alumnos AS $alumno) {
                                $dataAlumno[0] = ($x+1);
                                $dataAlumno[1] = fixEncode($alumno['nombreAlumno']);
                                $a = 2;
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
                                        // $dataAlumno[$a] = '-';
                                        $dataAlumno[$a] = 0;
                                    }
                                    if(floatval($dataAlumno[$a]) > 0) {
                                        $dataTotalesAlumnos[$alumno['col_id']]['faltas'] = floatval($dataAlumno[$a]) + floatval($dataTotalesAlumnos[$alumno['col_id']]['faltas']);
                                    }
                                    // $dataTotalesAlumnos[$alumno['col_id']]['clases']++;
                                    $dataTotalesAlumnos[$alumno['col_id']]['horas'] = intval($dataTotalesAlumnos[$alumno['col_id']]['horas']) + $horasClase;
                                    $a++;
                                }

                                $dataAlumno[$a] = floatval($dataTotalesAlumnos[$alumno['col_id']]['faltas']);
                                $porcentaje = number_format((100 - (($dataTotalesAlumnos[$alumno['col_id']]['faltas'] / $dataTotalesAlumnos[$alumno['col_id']]['horas']) * 100)), 2);
                                if(floatval($porcentaje) < 0) $porcentaje = '0.00';
                                $dataAlumno[$a+1] = $porcentaje.'%';
                                // $dataAlumno[$a+2] = $dataTotalesAlumnos[$alumno['col_id']]['clases'];
                                $dataAlumno[$a+2] = $dataTotalesAlumnos[$alumno['col_id']]['horas'];
                                if($dataTotalesAlumnos[$alumno['col_id']]['faltas'] > 0) $writer->writeSheetRow($sheet, $dataAlumno, $styles5);
                                $x++;
                            }

                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array('', 'Comentarios'), $styles2);
                            foreach($dias as $dia){
                                $writer->writeSheetRow($sheet, array('', date('d/m/Y', strtotime($dia['col_fecha'])), ($dia['col_observaciones'] == ''?'-':$dia['col_observaciones'])), $styles5);
                            }


                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array(''));

                    }
                }
            }

        }
    }

    if($print_club == true) {
        // CLUB de Lectura
        foreach($gruposPeriodos as $periodoid){
            unset($arrayClaves);

            $sth = $this->db->prepare("SELECT * FROM tbl_periodos WHERE col_id='".$periodoid."'");
            $sth->execute();
            $periodoData = $sth->fetch(PDO::FETCH_OBJ);

            $sth = $this->db->prepare("SELECT * FROM tbl_maestros_taxonomia WHERE col_materia_clave LIKE 'CL%' AND col_id IN (SELECT col_materiaid FROM tbl_asistencia WHERE col_fecha >= '".$from."' AND col_fecha <= '".$to."') AND col_periodoid='".$periodoid."' GROUP BY col_materia_clave");
            $sth->execute();
            $claves = $sth->fetchAll();
            if($sth->rowCount()) {


                $sheet = 'CL: '.$modalidades[$periodoData->col_modalidad]." ".$periodoData->col_grado.$periodoData->col_grupo;
                $sheet = 'CLUB DE LECTURA';
                $writer->writeSheetHeader($sheet, array('' => string), $styles_head);
                $writer->writeSheetRow($sheet, array('Reporte de Asistencias - Generado: '.$from.' - '.$to, '', '', ''), $styles_heading_big);
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
                            $query = "SELECT a.col_id, CONCAT(a.col_apellidos, ' ', a.col_nombres) AS nombreAlumno FROM tbl_alumnos_taxonomia t ".
                            "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                            "WHERE a.col_estatus='activo' AND t.col_periodoid='".$periodoData->col_id."' ORDER BY a.col_apellidos ASC";
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
                            $i = 2;
                            unset($_listasid);
                            foreach($dias as $dia){
                                $str_horasClase = 1;
                                if(esClaseDosHoras($materia->col_id, $periodoid, date('N', strtotime($dia['col_fecha'])), $this->db)){
                                    $str_horasClase = 2;
                                }

                                $thead[$i] = date('d/m/Y', strtotime($dia['col_fecha'])).' ('.$str_horasClase.')';
                                $_listasid[] = $dia['col_id'];
                                $i++;
                            }
                            $thead[$i] = 'Total de Faltas';
                            $thead[$i+1] = '% de Asistencias';
                            $thead[$i+2] = '# Clases';
                            $writer->writeSheetRow($sheet, $thead, $styles2);

                            $x = 0;
                            unset($dataAlumno);
                            unset($dataTotalesAlumnos);
                            foreach($alumnos AS $alumno) {
                                $dataAlumno[0] = ($x+1);
                                $dataAlumno[1] = fixEncode($alumno['nombreAlumno']);
                                $a = 2;
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
                                            if($dataListAlumno->col_asistencia == 'R') {
                                                $dataAlumno[$a]['retardosCount'] = intval($dataAlumno[$a]['retardosCount']) + 1;
                                                $dataAlumno[$a] = 0.3;
                                            }

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
                                            if($dataListAlumno->col_asistencia == 'R') {
                                                $dataAlumno[$a]['retardosCount'] = intval($dataAlumno[$a]['retardosCount']) + 1;
                                                // $dataAlumno[$a] = 0.3;
                                            }

                                            if($dataListAlumno->col_asistencia == 'F') $dataAlumno[$a] = 1;
                                            if($dataListAlumno->col_asistencia == 'P') $dataAlumno[$a] = 'P';
                                        }
                                        if($dataListAlumno->col_asistencia == 'R') $dataAlumnoCountRetardo[$a] = intval($dataAlumnoCountRetardo[$a]) + 1;
                                    }else{
                                        // $dataAlumno[$a] = '-';
                                        $dataAlumno[$a] = 0;
                                    }
                                    if(floatval($dataAlumno[$a]) > 0) {
                                        $dataTotalesAlumnos[$alumno['col_id']]['faltas'] = floatval($dataAlumno[$a]) + floatval($dataTotalesAlumnos[$alumno['col_id']]['faltas']);
                                    }
                                    // $dataTotalesAlumnos[$alumno['col_id']]['clases']++;
                                    $dataTotalesAlumnos[$alumno['col_id']]['horas'] = intval($dataTotalesAlumnos[$alumno['col_id']]['horas']) + $horasClase;
                                    $a++;
                                }

                                if(floor(($dataAlumnoCountRetardo[$a] / 3)) > 0) {
                                    // $dataTotalesAlumnos[$alumno['col_id']]['faltas'] = $dataTotalesAlumnos[$alumno['col_id']]['faltas'] + floor(($dataAlumnoCountRetardo[$a] / 3));
                                }

                                $dataAlumno[$a] = floatval($dataTotalesAlumnos[$alumno['col_id']]['faltas']);
                                $porcentaje = number_format((100 - (($dataTotalesAlumnos[$alumno['col_id']]['faltas'] / $dataTotalesAlumnos[$alumno['col_id']]['horas']) * 100)), 2);
                                if(floatval($porcentaje) < 0) $porcentaje = '0.00';
                                $dataAlumno[$a+1] = $porcentaje.'%';
                                // $dataAlumno[$a+2] = $dataTotalesAlumnos[$alumno['col_id']]['clases'];
                                $dataAlumno[$a+2] = $dataTotalesAlumnos[$alumno['col_id']]['horas'];
                                if($dataTotalesAlumnos[$alumno['col_id']]['faltas'] > 0) $writer->writeSheetRow($sheet, $dataAlumno, $styles5);
                                $x++;
                            }

                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array('', 'Comentarios'), $styles2);
                            foreach($dias as $dia){
                                $writer->writeSheetRow($sheet, array('', date('d/m/Y', strtotime($dia['col_fecha'])), ($dia['col_observaciones'] == ''?'-':$dia['col_observaciones'])), $styles5);
                            }


                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array(''));
                            $writer->writeSheetRow($sheet, array(''));

                    }
                }
            }

            }
        }

        $writer->writeToStdOut();
        exit();
    });

});
//Termina routes.reportesME.php
