<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de reportes.
 *
 * Lista de funciones
 *
 * /reportesOtros
 * - /registroEscolaridad
 * - /reporteSDME
 * - /reporteME
 * - /reporteBajasAlumnos
 * - /reporteTramitesTitulacion
 * - /credencialesAlumnos
 * - /inventarioDocumentos
 * - /alumnosReprobados
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;
set_time_limit(0);
$app->group('/reportesOtros', function () {

    $this->get('/registroEscolaridad', function (Request $request, Response $response, array $args) {
        global $nombreInstituto, $claveInstitulo;
        $periodoid = intval($_REQUEST['periodo']);

        if(!isset($_REQUEST['debug']) || $_REQUEST['debug'] == 0){
            // $filename = 'registro_escolaridad_anverso'.date('Y-m-d').'.xls';
            // header('Content-disposition: attachment; filename="'.$filename.'"');
            // header('Content-Type: application/vnd.ms-excel');
            // header('Cache-Control: must-revalidate');
            // header('Pragma: public');
            // ob_start();
        }
        if(isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1){
            header('Content-Type: text/html; charset=utf-8');
            ?>
            <style>
                * {
                    font-family: Arial, Helvetica, sans-serif;
                    font-size: 12px;
                }
            </style>
            <?php
        }

        $tipoBaja = array('baja' => 'BAJA', 'bajatemporal' => 'BAJA TEMPORAL');
        $situacion = array('activo' => 'P', 'baja' => 'B', 'bajatemporal' => 'BT');

        $periodoData = getPeriodo($periodoid, $this->db, false);
        $carreraData = getCarrera($periodoData->col_carreraid, $this->db);

        $sth = $this->db->prepare("SELECT c.col_id, c.col_alumnoid, m.col_nombre, m.col_clave, IF( c.col_ts, c.col_ts, c.col_ext ) AS calificacionExamen, IF( c.col_ts,  'TS',  'EXT' ) AS tipoExamen FROM tbl_calificaciones c LEFT OUTER JOIN tbl_materias m ON m.col_clave=c.col_materia_clave WHERE (c.col_ext OR c.col_ts) AND c.col_periodoid='".$periodoid."' AND SUBSTRING(c.col_materia_clave, 1, 2) NOT IN ('AC', 'TL', 'CL', 'TR') GROUP BY c.col_alumnoid, c.col_materia_clave");
        $sth->execute();
        $todasMateriasRegularizacion = $sth->fetchAll();
        foreach($todasMateriasRegularizacion as $row) {
            if($row['col_clave'])
            $regularizacion[$row['col_alumnoid']][] = array('clave' => $row['col_clave'], 'tipo' => $row['tipoExamen'], 'calificacion' => $row['calificacionExamen']);
        }

        $maxRegularizacion = 0;
        foreach($regularizacion as $rdata) {
            if(count($rdata) > $maxRegularizacion) $maxRegularizacion = count($rdata);
        }
        if($maxRegularizacion < 3) $maxRegularizacion = 3;


        $sth = $this->db->prepare("SELECT m.col_nombre, m.col_clave FROM tbl_calificaciones c LEFT OUTER JOIN tbl_materias m ON m.col_clave=c.col_materia_clave WHERE c.col_periodoid='".$periodoid."' AND SUBSTRING(c.col_materia_clave, 1, 2) NOT IN ('AC', 'TL', 'CL', 'TR') GROUP BY c.col_materia_clave ORDER BY c.col_materia_clave ASC");
        $sth->execute();
        $todasMaterias = $sth->fetchAll();


        $sth = $this->db->prepare("SELECT a.col_id AS alumnoid, a.col_estatus, a.col_control, a.col_genero, a.col_id, a.col_nombres, a.col_apellidos FROM tbl_alumnos_taxonomia c LEFT OUTER JOIN tbl_alumnos a ON a.col_id=c.col_alumnoid WHERE c.col_periodoid='".$periodoid."' ORDER BY SUBSTRING(a.col_apellidos, 1, 4) ASC");
        $sth->execute();
        $todosAlumnos = $sth->fetchAll();

        $abc = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L');
        $mx = 0;
        foreach($todasMaterias as $materia) {
            $abcClave[$materia['col_clave']] = $abc[$mx];
            $mx++;
        }
        $totalTodasLasMaterias = $mx;

        $turno = 'VESPERTINO';
        if($carreraData['modalidad'] == 'Cuatrimestral') $turno = 'MIXTO';
        $hombres = 0;
        $mujeres = 0;
        $hombres_baja = 0;
        $mujeres_baja = 0;
        $hm = 0;
        $rotateCSS = 'font-size:9px;text-rotate:90;';
        $borders = 'border-width:0 1px 1px 1px;border-style:solid;border-color:#000000;border-collapse: collapse;';
        ob_start();
        ?>
        <table border="0" width="100%" style="border-collapse:collapse;" cellpadding="0" cellspacing="0">
            <tr>
                <td valign="top">
                    <img src="<?php echo getLogo('sep_chiapas_png');?>" border="0" width="160" />
                </td>
                <td align="center" width="60%" style="font-size: 1.2em;">
                    <b>
                    GOBIERNO CONSTITUCIONAL DEL ESTADO DE CHIAPAS<br/>
                    SECRETARIA DE EDUCACION<br/>
                    SUBSECRETARIA DE EDUCACION ESTATAL<br/>
                    DIRECCION DE EDUCACION SUPERIOR<br/>
                    DEPARTAMENTO DE SERVICIOS ESCOLARES</b>
                    <h3>REGISTRO DE ESCOLARIDAD</h3>
                </td>
                <td valign="top" align="right"><img src="<?php echo getLogo('iconoLogo');?>" border="0" width="80" /></td>
                <td>
                    <table border="1" cellspacing="0" cellpadding="2" style="table-layout: fixed; width: 100%;margin-left:20px;">
                        <tr>
                            <td>CONCEPTO</td>
                            <td style="text-rotate:90;text-align:center;"><small>HOMBRES</small></td>
                            <td style="text-rotate:90;text-align:center;"><small>MUJERES</small></td>
                            <td style="text-rotate:90;text-align:center;"><small>TOTALES</small></td>
                        </tr>
                        <tr>
                            <td>INICIO DE CURSOS</td>
                            <td align="center">%h%</td><td align="center">%m%</td><td align="center">%hm%</td>
                        </tr>
                        <tr>
                            <td>FIN DE CURSO</td>
                            <td align="center">%hf%</td><td align="center">%mf%</td><td align="center">%hmf%</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table border="0" width="100%" cellpadding="0" cellspacing="0" style="margin: 5px 0;">
         <tr>
            <td style="width: 100px;"><b>ESCUELA:</b></td>
            <td style="width: 300px;"><?php echo strtoupper($nombreInstituto); ?></td>
            <td style="width: 100px;">&nbsp;&nbsp;</td>
            <td style="width: 100px;"><b>CLAVE:</b></td>
            <td><?php echo $claveInstitulo; ?></td>
            <td style="width: 100px;">&nbsp;&nbsp;</td>
            <td><b>CICLO ESCOLAR:</b></td>
            <td>-</td>
            <td style="width: 100px;">&nbsp;&nbsp;</td>
            <td></td>
            <td></td>
            <td>&nbsp;&nbsp;</td>
         </tr>
         <tr>
            <td><b>LOCALIDAD:</b></td>
            <td><?php echo $carreraData['campus']; ?></td>
            <td>&nbsp;&nbsp;</td>
            <td><b>TURNO:</b></td>
            <td><?php echo $turno; ?></td>
            <td>&nbsp;&nbsp;</td>
            <td><b>SEMESTRE:</b> <?php echo $periodoData->col_grado; ?></td>
            <td><b>GRUPO:</b> <?php echo $periodoData->col_grupo; ?></td>
            <td>&nbsp;&nbsp;</td>
            <td></td>
            <td></td>
            <td>&nbsp;&nbsp;</td>
         </tr>
         <tr>
            <td><b>CARRERA:</b></td>
            <td><?php echo strtoupper($carreraData['nombre']); ?></td>
            <td>&nbsp;&nbsp;</td>
            <td><b>RVOE:</b></td>
            <td><?php echo $carreraData['revoe']; ?></td>
            <td>&nbsp;&nbsp;</td>
            <td><b>PER. ESC.:</b></td>
            <td><?php echo strtoupper($periodoData->col_nombre); ?></td>
            <td>&nbsp;&nbsp;</td>
            <td><b>HOJA:</b> 1&nbsp;</td>
            <td><b>DE</b> 2</td>
            <td>&nbsp;&nbsp;</td>
         </tr>
         <tr>
            <td><b>MODALIDAD:</b></td>
            <td><?php echo strtoupper($carreraData['modalidad']); ?></td>
            <td>&nbsp;&nbsp;</td>
            <td></td>
            <td></td>
            <td>&nbsp;&nbsp;</td>
            <td></td>
            <td></td>
            <td>&nbsp;&nbsp;</td>
            <td></td>
            <td></td>
            <td>&nbsp;&nbsp;</td>
         </tr>
        </table>

        <table border="1" cellspacing="0" cellpadding="1" style="table-layout: fixed; width: 100%;">
        <tbody>
            <tr bgcolor="#ded9c3">
                <td colspan="4" style="text-align: center;font-weight:bold;" valign="middle" >ANTECEDENTES</td>
                <td colspan="3" style="text-align: center;font-weight:bold;" valign="middle" >NOMBRE DEL ALUMNO</td>
                <td rowspan="3" style="<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" >SEXO</td>
                <td colspan="7" style="text-align: center;font-weight:bold;" valign="middle" >CALIFICACIONES FINALES</td>
                <td colspan="9" style="text-align: center;font-weight:bold;" valign="middle" >CALIFICACIONES DE REGULARIZACI&Oacute;N</td>
                <td rowspan="3" style="<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;">ASIGNATURAS NO ACREDITADAS</td>
                <td rowspan="3" style="<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" >SITUACI&Oacute;N ESCOLAR</td>
            </tr>
            <tr>
                <!-- Antecedentes -->
                <td rowspan="2" style="<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;font-size:8px;" valign="middle" >NUM. PROGRE.</td>
                <td rowspan="2" style="<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;font-size:8px;" valign="middle" >ASIGNATURAS NO ACREDITADAS</td>
                <td rowspan="2" style="<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;font-size:8px;" valign="middle" >SITUACI&Oacute;N ESCOLAR</td>
                <td rowspan="2" style="text-align: center;font-weight:bold;" valign="top" >N&Uacute;MERO DE<br/>CONTROL</td>
                <!-- Nombre del Alumno -->
                <td rowspan="2" style="text-align: center;font-weight:bold;" valign="top" >AP. PATERNO</td>
                <td rowspan="2" style="text-align: center;font-weight:bold;border-left:0;border-right:0;" valign="top" >AP. MATERNO</td>
                <td rowspan="2" style="text-align: center;font-weight:bold;" valign="top" >NOMBRE(S)</td>
                <!-- Calificaciones Finales -->
                <?php

                $m = 0;
                foreach($todasMaterias as $materia) { ?>
                <td style="text-align: center;font-weight:bold;" valign="middle" ><?php echo $abc[$m]; ?></td>
                <?php
                $m++;
                } ?>
                <!-- Calificaciones Regularizacion -->
                <?php for($r = 0; $r < $maxRegularizacion; $r++){ ?>
                    <td rowspan="2" style="<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" >CLAVE MATERIA</td>
                    <td rowspan="2" style="<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" >CALIFICACI&Oacute;N</td>
                    <td rowspan="2" style="<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" >TIPO DE EXAMEN</td>
                <?php } ?>

            </tr>
            <tr>
                <!-- Calificaciones Finales -->
                <?php foreach($todasMaterias as $materia) {
                    $nombreMateria = fixEncode($materia['col_nombre']);
                    if(strlen($nombreMateria) <= 20){
                        ?>
                            <td style="border:1px solid #000000;<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" ><?php echo $nombreMateria; ?></td>
                        <?php
                        }else {
                        ?>
                            <td style="border:1px solid #000000;width:20px;text-align: center;font-weight:bold;" valign="middle" ><?php echo splittext($nombreMateria, 20, $rotateCSS); ?></td>
                        <?php
                        }
                    } ?>
            </tr>
            <?php
                $i = 0;
                $totalAlumnosRepe = 0;
                $totalAlumnosAlta = 0;
                foreach($todosAlumnos as $row){
                $noAcreditadasFin = 0;
                $apellidos = explode(' ', trim($row['col_apellidos']), 2);
                $paterno = $apellidos[0];
                $materno = $apellidos[1];
                $estatus = $row['col_estatus'];
                $reprobadas = [];

                $sthAlumnoBaja = $this->db->prepare('SELECT * FROM tbl_alumnos_bajas WHERE col_alumnoid="'.$row['alumnoid'].'" AND col_periodoid="'.$periodoid.'"');
                $sthAlumnoBaja->execute();
                $alumnoFueBaja = $sthAlumnoBaja->rowCount();

                if($alumnoFueBaja > 0) {
                    if(trim(strtoupper($row['col_genero'])) == 'M') {
                        $mujeres_baja++;
                    }else{
                        $hombres_baja++;
                    }
                }

                if(trim(strtoupper($row['col_genero'])) == 'M') {
                    $mujeres++;
                }else{
                    $hombres++;
                }

                $sthAlumnoRepe = $this->db->prepare('SELECT p.* FROM tbl_alumnos_taxonomia at LEFT JOIN tbl_periodos p ON p.col_id=at.col_periodoid WHERE at.col_alumnoid="'.$row['alumnoid'].'" AND at.col_periodoid!="'.$periodoid.'"');
                $sthAlumnoRepe->execute();
                $sthAlumnoRepeCheck = $sthAlumnoRepe->fetchAll();
                $alumnoRepes = 0;
                foreach($sthAlumnoRepeCheck as $checkRepe) {
                    if($periodoData->col_grado == $checkRepe['col_grado']) $alumnoRepes++;
                }
                if($alumnoRepes > 0) {
                    $totalAlumnosRepe++;
                    continue;
                }

                $alumnoPrimeraVez = 'no';
                $sthAlumnoAlta = $this->db->prepare('SELECT * FROM tbl_alumnos_bajas WHERE col_alumnoid="'.$row['alumnoid'].'" AND col_periodoid!="'.$periodoid.'"');
                $sthAlumnoAlta->execute();
                $alumnoPeriodosAntes = $sthAlumnoAlta->rowCount();
                if($alumnoPeriodosAntes > 0) $alumnoPrimeraVez = 'si';


                ?>
                <tr>
                    <!-- Antecedentes -->
                    <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo $i+1; ?></td>
                    <td style="<?php echo $borders; ?>text-align: center;" valign="middle" >-</td>
                    <td style="<?php echo $borders; ?>text-align: center;" valign="middle" >P</td>
                    <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo $row['col_control']; ?></td>
                    <!-- Nombre del Alumno -->
                    <td style="<?php echo $borders; ?>text-align: left;" valign="middle" ><?php echo fixEncode($paterno); ?></td>
                    <td style="<?php echo $borders; ?>text-align: left;" valign="middle" ><?php echo fixEncode($materno); ?></td>
                    <td style="<?php echo $borders; ?>text-align: left;" valign="middle" ><?php echo fixEncode($row['col_nombres']); ?></td>
                    <!-- Genero -->
                    <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo switchGenero(strtoupper($row['col_genero'])).'---'.$alumnoRepes; ?></td>
                    <!-- Calificaciones Finales -->
                    <?php
                    $sth = $this->db->prepare("SELECT m.col_nombre, m.col_clave, c.col_cf FROM tbl_calificaciones c LEFT OUTER JOIN tbl_materias m ON m.col_clave=c.col_materia_clave WHERE c.col_periodoid='".$periodoid."' AND c.col_alumnoid='".$row['col_id']."' AND SUBSTRING(c.col_materia_clave, 1, 2) NOT IN ('AC', 'TL', 'CL', 'TR') GROUP BY c.col_materia_clave ORDER BY c.col_materia_clave ASC");
                    $sth->execute();
                    $todasMateriasAlumno = $sth->fetchAll();
                    $printedCalificiiones = 0;
                    foreach($todasMateriasAlumno as $cf) {

                        if($estatus == 'activo'){
                            $calificacion = number_format($cf['col_cf'], 1);
                            if(intval($calificacion) <= 5) {
                                $reprobadas[$cf['col_clave']] = $cf['col_cf'];
                                $reprobadasReal[$cf['col_clave']] = $cf['col_cf'];
                            }
                            ?>
                                <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo number_format($calificacion, 0); ?></td>
                            <?php
                        }else{
                            ?>
                            <td style="<?php echo $borders; ?>text-align: center;" valign="middle" >-</td>
                            <?php
                        }
                        $printedCalificiiones++;
                    }

                    ?>
                    <!-- Calificaciones Regularizacion -->
                    <?php

                    if(($totalTodasLasMaterias - $printedCalificiiones) > 0){
                        for($colsMat = 0; $colsMat < ($totalTodasLasMaterias - $printedCalificiiones); $colsMat++){
                            ?>
                                <td style="<?php echo $borders; ?>text-align: center;" valign="middle" >-</td>
                            <?php
                        }
                    }

                    $columnasBaja = $maxRegularizacion * 3;
                    if($estatus != 'activo'){ ?>

                        <td colspan="<?php echo $columnasBaja;?>" style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo $tipoBaja[$estatus]; ?></td>
                    <?php }else{
                        $regX = 0;
                        for($r = 0; $r < $maxRegularizacion; $r++){
                            $dataRegularizacion = $regularizacion[$row['col_id']];
                            if(isset($reprobadas[$dataRegularizacion[$r]['clave']])){
                                if(intval($dataRegularizacion[$r]['calificacion']) > 5){
                                    unset($reprobadas[$dataRegularizacion[$r]['clave']]);
                                }
                            }
                            ?>
                                <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo (isset($dataRegularizacion[$r]['clave'])?$abcClave[$dataRegularizacion[$r]['clave']]:'-'); ?></td>
                                <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo (isset($dataRegularizacion[$r]['calificacion'])?number_format($dataRegularizacion[$r]['calificacion'],0):'-'); ?></td>
                                <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo (isset($dataRegularizacion[$r]['tipo'])?$dataRegularizacion[$r]['tipo']:'-'); ?></td>
                        <?php
                        $regX++;
                        }

                        ?>
                    <?php } ?>
                    <!-- Otros -->
                    <?php

                    ?>
                    <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo count($reprobadas); ?></td>
                    <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php
                        if(count($reprobadasReal) > 0) {
                            echo 'PI';
                        }else{
                            echo $situacion[$estatus];
                        }
                    ?></td>
                </tr>
            <?php
                $i++;
                } ?>
        </tbody>
        </table>
        <b>Materias:</b>
        <?php
        $m = 0;
        foreach($todasMaterias as $materia) {
            echo $abc[$m].') '.fixEncode($materia['col_nombre']).',  ';
            $m++;
        } ?>
        <pagebreak>
        <table border="1" cellspacing="0" cellpadding="5" style="table-layout: fixed; width: 100%;">
        <tbody>
            <tr bgcolor="#ded9c3">
                <td rowspan="2" style="<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" >NUMERO PROGRESIVO</td>
                <td colspan="2" style="text-align: center;font-weight:bold;" valign="middle" >ANTECEDENTES</td>
                <td style="text-align: center;font-weight:bold;" valign="middle" ></td>
                <td rowspan="2" style="text-align: center;font-weight:bold;" valign="middle" >NOMBRE DEL ALUMNO</td>
                <td rowspan="2" style="text-align: center;font-weight:bold;" valign="middle" >SEXO</td>
                <td colspan="7" style="text-align: center;font-weight:bold;" valign="middle" >CALIFICACIONES FINALES</td>
                <td colspan="9" style="text-align: center;font-weight:bold;" valign="middle" >CALIFICACIONES DE REGULARIZACI&Oacute;N</td>
                <td rowspan="2" style="<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" >ASIGNATURAS NO ACREDITADAS</td>
                <td rowspan="2" style="<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" >SITUACI&Oacute;N ESCOLAR</td>
            </tr>
            <tr>
                <!-- Antecedentes -->
                <td style="<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" >ASIGNATURAS NO<br/>ACREDITADAS</td>
                <td style="<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" >SITUACI&Oacute;N ESCOLAR</td>
                <td style="text-align: center;font-weight:bold;" valign="middle" >N&Uacute;MERO DE<br/>CONTROL</td>
                <!-- Nombre del Alumno -->

                <!-- Calificaciones Finales -->
                <?php foreach($todasMaterias as $materia) {
                    $nombreMateria = fixEncode($materia['col_nombre']);
                    if(strlen($nombreMateria) <= 20){
                        ?>
                            <td style="border:1px solid #000000;<?php echo $rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" ><?php echo $nombreMateria; ?></td>
                        <?php
                        }else {
                        ?>
                            <td style="border:1px solid #000000;width:20px;text-align: center;font-weight:bold;" valign="middle" ><?php echo splittext($nombreMateria, 20, $rotateCSS); ?></td>
                        <?php
                        }
                    } ?>

                <!-- Calificaciones Regularizacion -->
                <?php for($r = 0; $r < $maxRegularizacion; $r++){ ?>
                    <td style="<?php echo $borders.$rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" >CLAVE MATERIA</td>
                    <td style="<?php echo $borders.$rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" >CALIFICACI&Oacute;N</td>
                    <td style="<?php echo $borders.$rotateCSS; ?>text-align: center;font-weight:bold;" valign="middle" >TIPO DE EXAMEN</td>
                <?php } ?>

            </tr>
            <?php $columnas = (8 + ($m) + ($maxRegularizacion * 3)); ?>
                <?php if($totalAlumnosRepe == 0) {?>
                    <tr>
                        <td colspan="<?php echo $columnas; ?>" style="text-align: center;font-weight:bold;" valign="middle" >ALUMNOS QUE REPITEN CURSO</td>
                    </tr>
                <?php }else{ ?>
                    <tr>
                        <td colspan="<?php echo $columnas; ?>" style="text-align: center;font-weight:bold;border-bottom:1px solid #000000;" valign="middle" >ALUMNOS QUE REPITEN CURSO</td>
                    </tr>
                <?php } ?>
            <?php
                $i = 0;
                foreach($todosAlumnos as $row){
                $noAcreditadasFin = 0;
                $apellidos = explode(' ', trim($row['col_apellidos']), 2);
                $paterno = $apellidos[0];
                $materno = $apellidos[1];
                $estatus = $row['col_estatus'];
                $reprobadas = [];


                $sthAlumnoRepe = $this->db->prepare('SELECT p.* FROM tbl_alumnos_taxonomia at LEFT JOIN tbl_periodos p ON p.col_id=at.col_periodoid WHERE at.col_alumnoid="'.$row['alumnoid'].'" AND at.col_periodoid!="'.$periodoid.'"');
                $sthAlumnoRepe->execute();
                $sthAlumnoRepeCheck = $sthAlumnoRepe->fetchAll();
                $alumnoRepes = 0;
                foreach($sthAlumnoRepeCheck as $checkRepe) {
                    if($periodoData->col_grado == $checkRepe['col_grado']) $alumnoRepes++;
                }
                if($alumnoRepes == 0) continue;


                ?>
                <tr>
                    <!-- Antecedentes -->
                    <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo $i+1; ?></td>
                    <td style="<?php echo $borders; ?>text-align: center;" valign="middle" >-</td>
                    <td style="<?php echo $borders; ?>text-align: center;" valign="middle" >P</td>
                    <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo $row['col_control']; ?></td>
                    <!-- Nombre del Alumno -->
                    <td style="<?php echo $borders; ?>text-align: left;" valign="middle" ><?php echo fixEncode($paterno.' '.$materno.' '.$row['col_nombres']); ?></td>
                    <!-- Genero -->
                    <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo switchGenero(strtoupper($row['col_genero'])).'---'.$alumnoRepes; ?></td>
                    <!-- Calificaciones Finales -->
                    <?php
                    $sth = $this->db->prepare("SELECT m.col_nombre, m.col_clave, c.col_cf FROM tbl_calificaciones c LEFT OUTER JOIN tbl_materias m ON m.col_clave=c.col_materia_clave WHERE c.col_periodoid='".$periodoid."' AND c.col_alumnoid='".$row['col_id']."' AND SUBSTRING(c.col_materia_clave, 1, 2) NOT IN ('AC', 'TL', 'CL', 'TR') GROUP BY c.col_materia_clave ORDER BY c.col_materia_clave ASC");
                    $sth->execute();
                    $todasMateriasAlumno = $sth->fetchAll();
                    $printedCalificiiones = 0;
                    foreach($todasMateriasAlumno as $cf) {

                        if($estatus == 'activo'){
                            $calificacion = number_format($cf['col_cf'], 1);
                            if(intval($calificacion) <= 5) {
                                $reprobadas[$cf['col_clave']] = $cf['col_cf'];
                                $reprobadasReal[$cf['col_clave']] = $cf['col_cf'];
                            }
                            ?>
                                <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo number_format($calificacion, 0); ?></td>
                            <?php
                        }else{
                            ?>
                            <td style="<?php echo $borders; ?>text-align: center;" valign="middle" >-</td>
                            <?php
                        }
                        $printedCalificiiones++;
                    }

                    ?>
                    <!-- Calificaciones Regularizacion -->
                    <?php

                    if(($totalTodasLasMaterias - $printedCalificiiones) > 0){
                        for($colsMat = 0; $colsMat < ($totalTodasLasMaterias - $printedCalificiiones); $colsMat++){
                            ?>
                                <td style="<?php echo $borders; ?>text-align: center;" valign="middle" >-</td>
                            <?php
                        }
                    }

                    $columnasBaja = $maxRegularizacion * 3;
                    if($estatus != 'activo'){ ?>

                        <td colspan="<?php echo $columnasBaja;?>" style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo $tipoBaja[$estatus]; ?></td>
                    <?php }else{
                        $regX = 0;
                        for($r = 0; $r < $maxRegularizacion; $r++){
                            $dataRegularizacion = $regularizacion[$row['col_id']];
                            if(isset($reprobadas[$dataRegularizacion[$r]['clave']])){
                                if(intval($dataRegularizacion[$r]['calificacion']) > 5){
                                    unset($reprobadas[$dataRegularizacion[$r]['clave']]);
                                }
                            }
                            ?>
                                <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo (isset($dataRegularizacion[$r]['clave'])?$abcClave[$dataRegularizacion[$r]['clave']]:'-'); ?></td>
                                <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo (isset($dataRegularizacion[$r]['calificacion'])?number_format($dataRegularizacion[$r]['calificacion'],0):'-'); ?></td>
                                <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo (isset($dataRegularizacion[$r]['tipo'])?$dataRegularizacion[$r]['tipo']:'-'); ?></td>
                        <?php
                        $regX++;
                        }

                        ?>
                    <?php } ?>
                    <!-- Otros -->
                    <?php

                    ?>
                    <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php echo count($reprobadas); ?></td>
                    <td style="<?php echo $borders; ?>text-align: center;" valign="middle" ><?php
                        if(count($reprobadasReal) > 0) {
                            echo 'PI';
                        }else{
                            echo $situacion[$estatus];
                        }
                    ?></td>
                </tr>
            <?php
                $i++;
                } ?>
            <tr>
                <td colspan="<?php echo $columnas; ?>" style="text-align: center;font-weight:bold;" valign="middle" >ALUMNOS DADOS DE ALTA</td>
            </tr>
            <tr>
                <!-- Antecedentes -->
                <td style="text-align: center;" valign="middle" ></td>
                <td style="text-align: center;" valign="middle" ></td>
                <td style="text-align: center;" valign="middle" ></td>
                <td style="text-align: center;" valign="middle" ></td>
                <!-- Nombre del Alumno -->
                <td style="text-align: left;" valign="middle" ></td>
                <!-- Genero -->
                <td style="text-align: center;" valign="middle" ></td>
                <!-- Calificaciones Finales -->
                <?php
                foreach($todasMaterias as $cf) { ?>
                    <td style="text-align: center;" valign="middle" ></td>
                <?php } ?>
                <!-- Calificaciones Regularizacion -->

                <?php for($r = 0; $r < $maxRegularizacion; $r++){ ?>
                        <td style="text-align: center;" valign="middle" ></td>
                        <td style="text-align: center;" valign="middle" ></td>
                        <td style="text-align: center;" valign="middle" ></td>
                <?php } ?>

                <!-- Otros -->
                <td style="text-align: center;" valign="middle" ></td>
                <td style="text-align: center;" valign="middle" ></td>
            </tr>
        </tbody>
        </table>

        <br/><br/><br/>
        <table cellspacing="0" width="100%">
            <tr>
                <td colspan="2" style="text-align: center;font-weight: bold;">INSCRIPCI&Oacute;N O REINSCRIPCI&Oacute;N</td>
                <td></td>
                <td colspan="2" style="text-align: center;font-weight: bold;">ACREDITACI&Oacute;N Y REGULARIZACI&Oacute;N</td>
                <td></td>
                <td colspan="2" style="text-align: center;font-weight: bold;">LEGALIZACI&Oacute;N DEL DOCUMENTO</td>
            </tr>
            <tr>
                <td width="100" style="border-left: 1px solid #222;border-top: 1px solid #222;" ></td>
                <td align="center" style="border: 1px solid #222;">DEPARTAMENTO DE SERVICIOS ESCOLARES</td>
                <td></td>
                <td width="100" style="border-left: 1px solid #222;border-top: 1px solid #222;" ></td>
                <td align="center" style="border: 1px solid #222;">DEPARTAMENTO DE SERVICIOS ESCOLARES</td>
                <td></td>
                <td colspan="2" align="center" style="border: 1px solid #222;">DEPARTAMENTO DE SERVICIOS ESCOLARES</td>
            </tr>
            <tr>
                <td width="100" style="border-left: 1px solid #222;" ></td>
                <td style="border-left: 1px solid #222;border-right: 1px solid #222;"></td>
                <td></td>
                <td width="100" style="border-left: 1px solid #222;" ></td>
                <td style="border-left: 1px solid #222;border-right: 1px solid #222;"></td>
                <td></td>
                <td colspan="2" align="center" style="border: 1px solid #222;">PERIODO LEGALIZADO: <?php echo $periodoData->col_nombre; ?></td>
            </tr>
            <tr>
                <td width="100" style="border-left: 1px solid #222;" ></td>
                <td style="border-left: 1px solid #222;border-right: 1px solid #222;"></td>
                <td></td>
                <td width="100" style="border-left: 1px solid #222;" ></td>
                <td style="border-left: 1px solid #222;border-right: 1px solid #222;"></td>
                <td></td>
                <td colspan="2" style="height:100px;border-right: 1px solid #222;border-left: 1px solid #222;"></td>
            </tr>
            <tr>
                <td width="100" style="border-left: 1px solid #222;" ></td>
                <td style="border-top: 1px solid #222;border-left: 1px solid #222;border-right: 1px solid #222;">FECHA</td>
                <td></td>
                <td width="100" style="border-left: 1px solid #222;" ></td>
                <td style="border-top: 1px solid #222;border-left: 1px solid #222;border-right: 1px solid #222;">FECHA</td>
                <td></td>
                <td rowspan="2" colspan="2" style="border: 1px solid #222;">FECHA</td>
            </tr>
            <tr>
                <td width="100" style="text-align:center; border: 1px solid #222;" >SELLO DEL PLANTEL</td>
                <td style="text-align:center; border: 1px solid #222;" >FECHA Y SELLO DEL VALIDACI&Oacute;N</td>
                <td></td>
                <td width="100" style="text-align:center; border: 1px solid #222;" >SELLO DEL PLANTEL</td>
                <td style="text-align:center; border: 1px solid #222;" >FECHA Y SELLO DEL VALIDACI&Oacute;N</td>
                <td></td>
            </tr>
            <tr>
                <td width="100" style="border-left: 1px solid #222;border-right: 1px solid #222;" ></td>
                <td style="border-left: 1px solid #222;border-right: 1px solid #222;" ></td>
                <td></td>
                <td width="100" style="border-left: 1px solid #222;border-right: 1px solid #222;" ></td>
                <td style="border-left: 1px solid #222;border-right: 1px solid #222;" ></td>
                <td></td>
                <td width="100" style="border-left: 1px solid #222;border-right: 1px solid #222;" ></td>
                <td style="height:100px;border-left: 1px solid #222;border-right: 1px solid #222;" ></td>
            </tr>
            <tr>
                <td valign="middle" align="center" width="100" style="border: 1px solid #222;" >MTRA. SUSANA PALACIOS MORALES<BR/>NOMBRE Y FIRMA DIRECTOR Y/O RECTOR DEL PLANTEL</td>
                <td valign="middle" align="center" style="border: 1px solid #222;" >LIC. LUCIA RU&Iacute;Z NARCIA<BR/>NOMBRE Y FIRMA DE QUIEN VALIDA</td>
                <td></td>
                <td valign="middle" align="center" width="100" style="border: 1px solid #222;" >MTRA. SUSANA PALACIOS MORALES<BR/>NOMBRE Y FIRMA DIRECTOR Y/O RECTOR DEL PLANTEL</td>
                <td valign="middle" align="center" style="border: 1px solid #222;" >LIC. LUCIA RU&Iacute;Z NARCIA<BR/>NOMBRE Y FIRMA DE QUIEN VALIDA</td>
                <td></td>
                <td valign="middle" align="center" width="100" style="border: 1px solid #222;" >BIOL. JULIAN MORENO TORAL<BR/>NOMBRE Y FIRMA DEL JEFE DE OFICINA</td>
                <td valign="middle" align="center" style="border: 1px solid #222;" >ING. MARTHA MARLENE ESTRADA ESTRADA<BR/>NOMBRE Y FIRMA DE LA JEFA DEL DEPTO.</td>
            </tr>
        </table>


        <?php
        $html = ob_get_contents();
        ob_end_clean();

        $hm = $hombres + $mujeres;

        $hombres_final = $hombres - $hombres_baja;
        $mujeres_final = $mujeres - $mujeres_baja;
        $hm_final = $hombres_final + $mujeres_final;

        $html = str_replace(array('%h%', '%m%', '%hm%'), array($hombres, $mujeres, $hm), $html);
        $html = str_replace(array('%hf%', '%mf%', '%hmf%'), array($hombres_final, $mujeres_final, $hm_final), $html);

        if(!isset($_REQUEST['debug']) || $_REQUEST['debug'] == 0){

            include_once(__DIR__ . '/../src/mpdf/mpdf.php');
            if(count($todosAlumnos) > 34) {
                $mpdf=new mPDF('c', [216, 356], 0,'', 5, 5, 3, 3, 0, 0, 'L');
            }else{
                $mpdf=new mPDF('c', [216, 356], 0,'', 5, 5, 5, 5, 0, 0, 'L');
            }
            $mpdf->SetDisplayMode('fullpage');
            $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list
            if(count($todosAlumnos) > 34) {
                $mpdf->WriteHTML(pdfCSS('8px', '8px'), 1);
            }else if(count($todosAlumnos) > 30) {
                $mpdf->WriteHTML(pdfCSS('9px', '9px'), 1);
            }else{
                $mpdf->WriteHTML(pdfCSS('10px', '10px'), 1);
            }
            $mpdf->WriteHTML($html, 2);
            $mpdf->Output('RegistroEscolaridad.pdf', 'I');

        }else{
            echo $html;
        }
        exit;
    });

    $this->get('/reporteSDME', function (Request $request, Response $response, array $args) {
        //ini_set('memory_limit','956MB');
        $periodoid = intval($_REQUEST['periodo']);
        $tipoActividad = intval($_REQUEST['rango']);

        switch($tipoActividad){
            case 5:
            $tipoRango = 'Primer Parcial';
            break;

            case 6:
            $tipoRango = 'Segundo Parcial';
            break;

            case 7:
            $tipoRango = 'Examen Final';
            break;
        }

        if(!isset($_REQUEST['debug']) || $_REQUEST['debug'] == 0){
            $filename = 'reporte_sin_derecho_modelo_educativo_'.date('Y-m-d').'.xls';
            header('Content-disposition: attachment; filename="'.$filename.'"');
            //header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
        }


        $periodoData = getPeriodo($periodoid, $this->db, false);
        $carreraData = getCarrera($periodoData->col_carreraid, $this->db);

        //$query = "SELECT DISTINCT c.col_alumnoid, a.col_control, a.col_id, CONCAT(a.col_nombres, ' ', a.col_apellidos) as nombreAlumno FROM tbl_calificaciones c LEFT OUTER JOIN tbl_alumnos a ON a.col_id=c.col_alumnoid WHERE c.col_periodoid='".$periodoid."'";
        $query = "SELECT DISTINCT c.col_alumnoid, a.col_control, a.col_id, CONCAT(a.col_nombres, ' ', a.col_apellidos) as nombreAlumno
        FROM tbl_calificaciones c LEFT OUTER JOIN tbl_alumnos a ON a.col_id=c.col_alumnoid WHERE c.col_alumnoid IN (SELECT col_alumnoid FROM tbl_alumnos_taxonomia WHERE col_periodoid='".$periodoid."' AND col_baja=0)  ORDER BY a.col_apellidos ASC";

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todosAlumnos = $sth->fetchAll();

         $query = "SELECT a.col_id AS actividadID, a.col_titulo as actividadTitulo, a.col_fecha_inicio, m.col_id AS materiaid, m.col_nombre AS materia, m.col_clave AS clave FROM tbl_actividades a ".
         "LEFT OUTER JOIN tbl_materias m ON m.col_id=a.col_materiaid WHERE a.col_visible_excepto LIKE '%".$periodoid."%' AND a.col_tipo='".$tipoActividad."' AND a.col_materiaid ".
         "AND (m.col_clave NOT LIKE 'TR%' AND m.col_clave NOT LIKE 'CL%') ORDER BY a.col_fecha_inicio ASC";
         // echo $query;exit;
         $sth = $this->db->prepare($query);
         $sth->execute();
         $actividades = $sth->fetchAll();
         $i = 0;
         foreach($actividades as $item){
             $ordenExamenes[$i] = array('actividadID' => $item['actividadID'], 'actividadTitulo' => $item['actividadTitulo'], 'materiaID' => $item['materiaid'], 'materiaNombre' => $item['materia']);

             $i++;
         }


         // echo '<pre>';
         // print_r($ordenExamenes);
         // echo '</pre>';
         // exit;

        ob_start();
            $a = 1;
            foreach($todosAlumnos as $item){
                $promedio = 0;
                $alumnoID = $item['col_id'];
                $numberoControl = $item['col_control'];
                $nombrealumno = utf8_decode($item['nombreAlumno']);

                //$ordenExamen = 1;
                foreach($ordenExamenes as $k=>$examen){
                    $ordenExamen = ($k + 1);
                    if($tipoActividad == 5 OR $tipoActividad == 6){

                        if($ordenExamen == 1 && intval($examen['actividadID']) > 0) {
                            $me = acreditaMEAcademias($alumnoID, intval($examen['actividadID']), $this->db);
                            $meTipo = 'Academia';
                        }
                        if($ordenExamen == 2 && intval($examen['actividadID']) > 0) {
                            $me = acreditaMETalleres($alumnoID, intval($examen['actividadID']), $this->db);
                            $meTipo = 'Taller';
                        }
                        if($ordenExamen == 3 && intval($examen['actividadID']) > 0) {
                            $me = acreditaMEClubLectura($alumnoID, intval($examen['actividadID']), $this->db);
                            $meTipo = 'Club de Lectura';
                        }
                        if($ordenExamen == 4 && intval($examen['actividadID']) > 0) {
                            $me = acreditaMETransversales($alumnoID, intval($examen['actividadID']), $this->db);
                            $meTipo = 'Transversal';
                        }
                        if($ordenExamen == 5 && intval($examen['actividadID']) > 0) {
                            $me = acreditaMEPracticas($alumnoID, intval($examen['actividadID']), $tipoActividad, $this->db);
                            $meTipo = 'Practicas Profesionales';
                        }
                        if($ordenExamen == 7 && intval($examen['actividadID']) > 0) {
                            $me = acreditaMEServicio($alumnoID, intval($examen['actividadID']), $tipoActividad, $this->db);
                            $meTipo = 'Servicio Social';
                        }

                    }else if($tipoActividad == 7){

                        if($periodoData->col_grado < 7) {
                            if($ordenExamen == 1 && intval($examen['actividadID']) > 0) {
                                $me = acreditaMEAcademias($alumnoID, intval($examen['actividadID']), $this->db);
                                $meTipo = 'Academia';
                            }
                            if($ordenExamen == 2 && intval($examen['actividadID']) > 0) {
                                $me = acreditaMEAltruista($alumnoID, $this->db, $periodoData->col_id);
                                $meTipo = 'Labor Altruista';
                            }
                            if($ordenExamen == 7 && intval($examen['actividadID']) > 0) {
                                $me = acreditaMEPracticas($alumnoID, intval($examen['actividadID']), $tipoActividad, $this->db);
                                $meTipo = 'Practicas Profesionales';
                            }
                        }
                        if($periodoData->col_grado > 6) {
                            if($ordenExamen == 1 && intval($examen['actividadID']) > 0) {
                                $me = acreditaMEAcademias($alumnoID, intval($examen['actividadID']), $this->db);
                                $meTipo = 'Academia';
                            }
                            if($ordenExamen == 2 && intval($examen['actividadID']) > 0) {
                                $me = acreditaMEAltruista($alumnoID, $this->db, $periodoData->col_id);
                                $meTipo = 'Labor Altruista';
                            }
                        }
                    }
                    if($me['reduccion'] > 0) {
                    ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $a; ?></td>
                        <td style="text-align: center;"><?php echo $numberoControl; ?></td>
                        <td style="text-align: left;"><?php echo $nombrealumno; ?></td>
                        <td style="text-align: center;"><?php echo $periodoData->col_grado; ?></td>
                        <td style="text-align: left;"><?php echo $meTipo; ?></td>
                        <td style="text-align: left;"><?php echo $examen['materiaNombre'].' ('.$ordenExamen.' Examen)'; ?></td>
                        <td style="text-align: left;"><?php echo intval($me['reduccion']).'% de Reducci&oacute;n';?></td>
                        <td style="text-align: left;">
                            <?php
                            if(isset($_REQUEST['debug']) || $_REQUEST['debug'] == 1){
                                if($meTipo == 'Practicas Profesionales') {
                                print_r($me);
                                echo '---';
                                }
                                echo 'ActividadID: '.$examen['actividadID'].' - AlumnoID: '.$alumnoID;
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                    $a++;
                    }

                    //$ordenExamen++;
                }

            }
        ?>


        <?php
        $html = ob_get_contents();
        ob_end_clean();



        ?>
        <b>REPORTE SIN DERECHO Y MODELO EDUCATIVO</b><br/>
        <b>CARRERA:</b> <?php echo $carreraData['nombre']; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <b>PERIODO ESCOLAR:</b> <?php echo $periodoData->col_nombre; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <b>SEMESTRE:</b> <?php echo $periodoData->col_grado; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <b>Tipo:</b> <?php echo $tipoRango; ?>
        <br/><br/>
        <table border="1" cellspacing="0" cellpadding="5" style="table-layout: fixed; width: 100%;">
        <tbody>
            <tr bgcolor="f2f2f2">
                <td style="width: 40px; text-align: center;font-weight:bold;" valign="middle" height="60">No.</td>
                <td style="width: 70px; text-align: center;font-weight:bold;" valign="middle" >Control</td>
                <td style="width: 300px; text-align: center;font-weight:bold;" valign="middle" >Alumno</td>
                <td style="width: 120px; text-align: center;font-weight:bold;" valign="middle" >Semestre</td>
                <td style="width: 300px; text-align: center;font-weight:bold;" valign="middle" >Modelo Educativo No Acreditado</td>
                <td style="width: 300px; text-align: center;font-weight:bold;" valign="middle" >Materia Afectada por Modelo Educativo</td>
                <td style="width: 300px; text-align: center;font-weight:bold;" valign="middle" >Tipo de Sanci&oacute;n</td>
                <td style="width: 300px; text-align: center;font-weight:bold;" valign="middle" >Observaciones</td>
            </tr>
        <?php
        echo $html;
        ?>
        </tbody>
        </table>
        <br/><br/>
        <?php
        exit;
    });

    $this->get('/reporteME', function (Request $request, Response $response, array $args) {
        include_once(__DIR__ . '/../src/xlsxwriter.class.php');

        $periodoid = intval($_REQUEST['periodo']);


        if(!isset($_REQUEST['debug']) || $_REQUEST['debug'] == 0){
            $filename = 'reporte_modelo_educativo_'.date('Y-m-d').'.xls';
            header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($filename).'"');

            // header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            // header('Content-Type: application/vnd.ms-excel');
            // header('Content-Transfer-Encoding: binary');
            // header('Cache-Control: must-revalidate');
            // header('Pragma: public');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8");
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
        }else{
            header('Content-Type: text/html; charset=utf-8');
            ?>
            <style>
            body, *{
                font-size: 11px;
                font-family: Arial, Helvetica, sans-serif;
            }
            </style>
            <?php
        }

        $isSemestral = true;
        $isPosgrado = false;

        $periodoData = getPeriodo($periodoid, $this->db, false);
        $carreraData = getCarrera($periodoData->col_carreraid, $this->db);
        if($carreraData['modalidad_periodo'] != 'ldsem') $isSemestral = false;
        if($carreraData['modalidad_periodo'] == 'master' OR $carreraData['modalidad_periodo'] == 'docto') $isPosgrado = true;

        if($isPosgrado == false) {

            // Cambio de query para mostrar todos los alumnos incluso los de baja, peticion 18 de Septiembre de 2020
            $query = "SELECT a.col_control, a.col_id, CONCAT(a.col_apellidos, ' ', a.col_nombres) as nombreAlumno, col_estatus FROM tbl_alumnos_taxonomia c LEFT OUTER JOIN tbl_alumnos a ON a.col_id=c.col_alumnoid WHERE a.col_nombres!='' AND c.col_periodoid='".$periodoid."' GROUP BY c.col_alumnoid ORDER BY a.col_apellidos ASC";
            $sth = $this->db->prepare($query);
            $sth->execute();
            $todosAlumnos = $sth->fetchAll();

            $sth = $this->db->prepare("SELECT * FROM tbl_calificaciones WHERE col_periodoid='".$periodoid."' GROUP BY col_materia_clave");
            $sth->execute();
            $todos = $sth->fetchAll();
            $a = 1;

            foreach($todos as $item){
                if(!in_array(strtoupper(substr($item['col_materia_clave'], 0, 2)), array('TR', 'TL', 'AC', 'CL'))){
                    $queryMaestro = 'SELECT mt.col_id AS taxID, u.col_id AS maestroID, CONCAT(u.col_firstname, " ", u.col_lastname) AS nombreMaestro FROM tbl_maestros_taxonomia mt LEFT OUTER JOIN tbl_users u ON u.col_id=mt.col_maestroid WHERE mt.col_materia_clave="'.$item['col_materia_clave'].'" AND mt.col_periodoid="'.$periodoid.'"';
                }else{

                    $periodosRelacionados = getPeriodoTaxoIDS($periodoid, $this->db);
                    $queryMaestro = 'SELECT GROUP_CONCAT( mt.col_id SEPARATOR  ",") AS taxID, GROUP_CONCAT(u.col_id SEPARATOR  ",") AS maestroID, CONCAT(u.col_firstname, " ", u.col_lastname) AS nombreMaestro FROM tbl_maestros_taxonomia mt LEFT OUTER JOIN tbl_users u ON u.col_id=mt.col_maestroid WHERE mt.col_materia_clave LIKE "'.claveMateria($item['col_materia_clave']).'%" AND mt.col_periodoid IN ('.implode(',', $periodosRelacionados).')';
                }
                $maestroSQL = $this->db->prepare($queryMaestro);
                $maestroSQL->execute();
                $maestroData = $maestroSQL->fetch(PDO::FETCH_OBJ);


                if(!in_array(strtoupper(substr($item['col_materia_clave'], 0, 2)), array('TR', 'TL', 'AC', 'CL'))){
                    $queryMateria = 'SELECT * FROM tbl_materias WHERE col_clave="'.$item['col_materia_clave'].'" AND col_semestre="'.$periodoData->col_grado.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'" ';
                }else{
                    $queryMateria = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($item['col_materia_clave']).'%" AND col_semestre="'.$periodoData->col_grado.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'" LIMIT 1';
                }
                $materiaSQL = $this->db->prepare($queryMateria);
                $materiaSQL->execute();
                $materiaData = $materiaSQL->fetch(PDO::FETCH_OBJ);

                if(!in_array(strtoupper(substr($item['col_materia_clave'], 0, 2)), array('TR', 'TL', 'AC', 'CL'))){
                    $materiasCR[] = array('clave' => $item['col_materia_clave'], 'debug' => $queryMateria, 'debug2' => $queryMaestro, 'taxID' => $maestroData->taxID, 'maestroID' => $maestroData->maestroID, 'maestro' => $maestroData->nombreMaestro, 'materia' => $materiaData->col_nombre);
                }

                if(strtoupper(substr($item['col_materia_clave'], 0, 2)) == 'TR'){
                    $materiasTR[] = array('clave' => $item['col_materia_clave'], 'debug' => $queryMateria, 'debug2' => $queryMaestro, 'taxID' => implode(',', array_unique(explode(',', $maestroData->taxID))), 'maestroID' => implode(',', array_unique(explode(',', $maestroData->maestroID))), 'maestro' => $maestroData->nombreMaestro, 'materia' => $materiaData->col_nombre);
                }

                if(strtoupper(substr($item['col_materia_clave'], 0, 2)) == 'CL'){
                    $materiasCL[] = array('clave' => $item['col_materia_clave'], 'debug' => $queryMateria, 'debug2' => $queryMaestro, 'taxID' => implode(',', array_unique(explode(',', $maestroData->taxID))), 'maestroID' => implode(',', array_unique(explode(',', $maestroData->maestroID))), 'maestro' => $maestroData->nombreMaestro, 'materia' => $materiaData->col_nombre);
                }

            }

            $sth = $this->db->prepare("SELECT * FROM tbl_calificaciones WHERE col_groupid='".$periodoData->col_groupid."' GROUP BY col_materia_clave");
            $sth->execute();
            $todos = $sth->fetchAll();
            $a = 1;
            foreach($todos as $item){
                if(!in_array(strtoupper(substr($item['col_materia_clave'], 0, 2)), array('TR', 'TL', 'AC', 'CL'))){
                    $queryMaestro = 'SELECT mt.col_id AS taxID, u.col_id AS maestroID, CONCAT(u.col_firstname, " ", u.col_lastname) AS nombreMaestro FROM tbl_maestros_taxonomia mt LEFT OUTER JOIN tbl_users u ON u.col_id=mt.col_maestroid WHERE mt.col_materia_clave="'.$item['col_materia_clave'].'" AND mt.col_periodoid="'.$periodoid.'"';
                }else{

                    $periodosRelacionados = getPeriodoTaxoIDS($periodoid, $this->db);
                    $queryMaestro = 'SELECT GROUP_CONCAT( mt.col_id SEPARATOR  ",") AS taxID, GROUP_CONCAT(u.col_id SEPARATOR  ",") AS maestroID, CONCAT(u.col_firstname, " ", u.col_lastname) AS nombreMaestro FROM tbl_maestros_taxonomia mt LEFT OUTER JOIN tbl_users u ON u.col_id=mt.col_maestroid WHERE mt.col_materia_clave LIKE "'.claveMateria($item['col_materia_clave']).'%" AND mt.col_periodoid IN ('.implode(',', $periodosRelacionados).')';
                }
                $maestroSQL = $this->db->prepare($queryMaestro);
                $maestroSQL->execute();
                $maestroData = $maestroSQL->fetch(PDO::FETCH_OBJ);


                if(!in_array(strtoupper(substr($item['col_materia_clave'], 0, 2)), array('TR', 'TL', 'AC', 'CL'))){
                    $queryMateria = 'SELECT * FROM tbl_materias WHERE col_clave="'.$item['col_materia_clave'].'" AND col_semestre="'.$periodoData->col_grado.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'" ';
                }else{
                    $queryMateria = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($item['col_materia_clave']).'%" AND col_semestre="'.$periodoData->col_grado.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'" LIMIT 1';
                }
                $materiaSQL = $this->db->prepare($queryMateria);
                $materiaSQL->execute();
                $materiaData = $materiaSQL->fetch(PDO::FETCH_OBJ);

                if(strtoupper(substr($item['col_materia_clave'], 0, 2)) == 'TL'){
                    $materiasTL[] = array('clave' => $item['col_materia_clave'], 'debug' => $queryMateria, 'debug2' => $queryMaestro, 'taxID' => implode(',', array_unique(explode(',', $maestroData->taxID))), 'maestroID' => implode(',', array_unique(explode(',', $maestroData->maestroID))), 'maestro' => $maestroData->nombreMaestro, 'materia' => $materiaData->col_nombre);
                }

                if(strtoupper(substr($item['col_materia_clave'], 0, 2)) == 'AC'){
                    $materiasAC[] = array('clave' => $item['col_materia_clave'], 'debug' => $queryMateria, 'debug2' => $queryMaestro, 'taxID' => implode(',', array_unique(explode(',', $maestroData->taxID))), 'maestroID' => implode(',', array_unique(explode(',', $maestroData->maestroID))), 'maestro' => $maestroData->nombreMaestro, 'materia' => $materiaData->col_nombre);
                }
            }


            $tipoModalidad = 'SEMESTRE';
            if($carreraData['modalidad'] == 'Cuatrimestral') $tipoModalidad = 'CUATRIMESTRE';


            ob_start();
            ?>
            <table border="0" cellspacing="0" cellpadding="5" style="table-layout: fixed; width: 100%;">
            <tbody>
                <tr>
                    <td></td><td></td><td></td>
                    <td colspan="7">CONTROL MODELO EDUCATIVO</td>
                </tr>
                <tr>
                    <td></td><td></td>
                    <td colspan="8">
                        <b>CARRERA:</b> <?php echo htmlentities(fixEncode($carreraData['nombre'])); ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <b>PERIODO ESCOLAR:</b> <?php echo $periodoData->col_nombre; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <b><?php echo $tipoModalidad?>:</b> <?php echo $periodoData->col_grado; ?>-<?php echo $periodoData->col_grupo; ?>
                    </td>
                </tr>
            </tbody>
            </table>
            <br/><br/>
            <table border="1" cellspacing="0" cellpadding="5" bgcolor="f2f2f2" style="table-layout: fixed; width: 100%;">
            <tbody>
                <tr>
                    <td style="width: 40px; text-align: center;font-weight:bold;" valign="middle" rowspan="3" height="60">No.</td>
                    <td style="width: 70px; text-align: center;font-weight:bold;" valign="middle" rowspan="3">Control</td>
                    <td style="width: 300px; text-align: center;font-weight:bold;" valign="middle" rowspan="3">Alumno</td>
                    <?php
                        $colsCalificiaciones = 6;
                        if($isPosgrado == true) $colsCalificiaciones = 1;

                        foreach($materiasCR as $mt){ ?>
                        <td style="width: 360px; text-align: center;font-weight:bold;" colspan="<?php echo $colsCalificiaciones; ?>"><?php echo htmlentities(fixEncode($mt['materia'])); ?></td>
                    <?php } ?>

                    <td style="width: 120px; text-align: center;font-weight:bold;" valign="middle" rowspan="3">Promedio</td>
                    <?php if($carreraData['modalidad'] !== 'Cuatrimestral'){ ?>
                        <td style="width: 120px; text-align: center;font-weight:bold;" valign="middle" rowspan="2" colspan="2">AC&Aacute;DEMIA</td>
                        <td style="width: 120px; text-align: center;font-weight:bold;" valign="middle" rowspan="2" colspan="2">TALLER</td>
                        <td style="width: 120px; text-align: center;font-weight:bold;" valign="middle" rowspan="2" colspan="2">CLUB DE<br/>LECTURA</td>
                        <td style="width: 120px; text-align: center;font-weight:bold;" valign="middle" rowspan="2" colspan="2">TRANSVERSAL</td>
                        <td style="width: 180px; text-align: center;font-weight:bold;" valign="middle" rowspan="2" colspan="3">PRACTICAS<br/>PROFESIONALES</td>
                    <?php } ?>
                    <td style="width: 150px; text-align: center;font-weight:bold;" valign="middle" rowspan="2">SERVICIO<br/>SOCIAL</td>
                    <td style="width: 180px; text-align: center;font-weight:bold;" valign="middle" rowspan="2" colspan="2">SEGUIMIENTO</td>
                    <td style="width: 180px; text-align: center;font-weight:bold;" valign="middle" rowspan="2">ASESORIA</td>
                    <td style="width: 180px; text-align: center;font-weight:bold;" valign="middle" rowspan="2">OBSERVACIONES</td>
                </tr>
                <tr>
                    <?php foreach($materiasCR as $mt){ ?>
                        <td style="text-align: center;font-weight:bold;" colspan="<?php echo $colsCalificiaciones; ?>" height="20"><?php echo htmlentities(fixEncode($mt['maestro'])); ?></td>
                    <?php } ?>
                </tr>
                <tr>
                    <?php
                    if($isPosgrado == false) {
                        foreach($materiasCR as $mt){ ?>
                        <td style="width: 60px; text-align: center;font-weight:bold;" height="20">P1</td>
                        <td style="width: 60px; text-align: center;font-weight:bold;">P2</td>
                        <td style="width: 60px; text-align: center;font-weight:bold;">EF</td>
                        <td style="width: 60px; text-align: center;font-weight:bold;">CF</td>
                        <td style="width: 60px; text-align: center;font-weight:bold;">EX</td>
                        <td style="width: 60px; text-align: center;font-weight:bold;">TS</td>
                    <?php }
                    }else{
                        foreach($materiasCR as $mt){ ?>
                            <td style="width: 60px; text-align: center;font-weight:bold;" height="20">CF</td>
                        <?php }
                    }

                    ?>
                    <?php if($carreraData['modalidad'] !== 'Cuatrimestral'){ ?>
                        <td style="width: 60px; text-align: center;font-weight:bold;">IN</td>
                        <td style="width: 65px; text-align: center;font-weight:bold;">CAL</td>
                        <td style="width: 60px; text-align: center;font-weight:bold;">IN</td>
                        <td style="width: 65px; text-align: center;font-weight:bold;">CAL</td>
                        <td style="width: 60px; text-align: center;font-weight:bold;">IN</td>
                        <td style="width: 65px; text-align: center;font-weight:bold;">CAL</td>
                        <td style="width: 60px; text-align: center;font-weight:bold;">IN</td>
                        <td style="width: 65px; text-align: center;font-weight:bold;">CAL</td>
                        <td style="width: 60px; text-align: center;font-weight:bold;">1P</td>
                        <td style="width: 60px; text-align: center;font-weight:bold;">2P</td>
                        <td style="width: 75px; text-align: center;font-weight:bold;">FINAL</td>
                    <?php } ?>
                    <td style="width: 75px; text-align: center;font-weight:bold;"></td>
                    <td style="width: 180px; text-align: center;font-weight:bold;">COORDINACI&Oacute;N</td>
                    <td style="width: 180px; text-align: center;font-weight:bold;">PSICOLOGA</td>
                    <td style="width: 75px; text-align: center;font-weight:bold;"></td>
                    <td style="width: 75px; text-align: center;font-weight:bold;"></td>
                </tr>
            </tbody>
            </table>
            <table border="1" cellspacing="0" cellpadding="5" style="table-layout: fixed; width: 100%;">
                <tbody>
                    <?php
                    $a = 1;
                    foreach($todosAlumnos as $item){
                        $promedio = 0;
                        $alumnoID = $item['col_id'];
                        ?>
                        <tr>
                            <td style="width: 40px; text-align: center;"><?php echo $a; ?></td>
                            <td style="width: 70px; text-align: center;"><?php echo $item['col_control']; ?></td>
                            <td style="width: 300px; text-align: left;"><?php echo htmlentities(fixEncode($item['nombreAlumno'])); ?></td>
                            <?php foreach($materiasCR as $mt){
                                $queryCalificaciones = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave="'.$mt['clave'].'" AND col_periodoid="'.$periodoid.'" AND col_alumnoid="'.$item['col_id'].'" ';
                                $calificacionesSQL = $this->db->prepare($queryCalificaciones);
                                $calificacionesSQL->execute();
                                $data = $calificacionesSQL->fetch(PDO::FETCH_OBJ);
                                ?>
                                <?php if($isPosgrado == false) { ?> <td style="width: 50px; text-align: center;"><?php echo $data->col_p1; ?></td> <?php } ?>
                                <?php if($isPosgrado == false) { ?> <td style="width: 50px; text-align: center;"><?php echo $data->col_p2; ?></td> <?php } ?>
                                <?php if($isPosgrado == false) { ?> <td style="width: 50px; text-align: center;"><?php echo $data->col_ef; ?></td> <?php } ?>
                                <td style="width: 50px; text-align: center;"><?php echo $data->col_cf; ?></td>
                                <?php if($isPosgrado == false) { ?> <td style="width: 50px; text-align: center;"><?php echo $data->col_ext; ?></td> <?php } ?>
                                    <?php if($isPosgrado == false) { ?> <td style="width: 50px; text-align: center;"><?php echo $data->col_ts; ?></td> <?php } ?>
                            <?php

                            if($data->col_ts){
                                $promedio = $promedio + $data->col_ts;
                            }else if($data->col_ext){
                                $promedio = $promedio + $data->col_ext;
                            }else{
                                $promedio = $promedio + $data->col_cf;
                            }
                        }

                        $calPromedio = number_format($promedio / count($materiasCR), 1);
                        // $calPromedio = $promedio;

                        ?>
                        <td style="width: 120px; text-align: center;"><?php echo $calPromedio; ?></td>
                        <?php if($carreraData['modalidad'] !== 'Cuatrimestral'){ ?>
                        <?php foreach($materiasAC as $mt){
                            $queryCalificaciones = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave="'.$mt['clave'].'" AND col_groupid="'.$periodoData->col_groupid.'" AND col_alumnoid="'.$item['col_id'].'" ';
                            $calificacionesSQL = $this->db->prepare($queryCalificaciones);
                            $calificacionesSQL->execute();
                            if($calificacionesSQL->rowCount()){
                                // $data = $calificacionesSQL->fetch(PDO::FETCH_OBJ);
                                $dataCalificaciones = $calificacionesSQL->fetchAll();
                                foreach($dataCalificaciones as $row){
                                    if($row['col_p1'] != '') $data_p1 = $row['col_p1'];
                                    if($row['col_p2'] != '') $data_p2 = $row['col_p2'];
                                }

                                $inasistencias = '';
                                if($mt['taxID'] != ''){
                                    $queryAsistencias = "SELECT COUNT(*) as Inasistencias FROM tbl_asistencia_alumnos WHERE col_alumnoid='".$alumnoID."' AND col_asistencia='F' AND col_listaid IN (SELECT col_id FROM tbl_asistencia WHERE col_maestroid='54' AND col_materiaid IN (".$mt['taxID']."))";
                                    $asistenciasSQL = $this->db->prepare($queryAsistencias);
                                    $asistenciasSQL->execute();
                                    $dataAsistencias = $asistenciasSQL->fetch(PDO::FETCH_OBJ);

                                    $inasistencias = $dataAsistencias->Inasistencias;
                                }
                                ?>
                        <?php }
                            } ?>
                            <td style="width: 54px; text-align: center;"><?php echo intval($inasistencias); ?></td>
                            <td style="width: 54px; text-align: center;"><?php echo ($data_p1 != ''?($data_p1 >= 7?'A':'NA'):''); ?>/<?php echo ($data_p2 != ''?($data_p2 >= 7?'A':'NA'):''); ?></td>

                            <?php
                            $inasistencias = '';
                            foreach($materiasTL as $mt){
                                $queryCalificaciones = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave="'.$mt['clave'].'" AND col_periodoid="'.$periodoid.'" AND col_alumnoid="'.$item['col_id'].'" ';
                                $calificacionesSQL = $this->db->prepare($queryCalificaciones);
                                $calificacionesSQL->execute();
                                if($calificacionesSQL->rowCount()){
                                    $dataCalificaciones = $calificacionesSQL->fetchAll();
                                    foreach($dataCalificaciones as $row){
                                        if($row['col_p1'] != '') $data_p1 = $row['col_p1'];
                                        if($row['col_p2'] != '') $data_p2 = $row['col_p2'];
                                    }

                                    if($inasistencias == '' && $mt['taxID'] != ''){
                                        $queryAsistencias = "SELECT COUNT(*) as Inasistencias FROM tbl_asistencia_alumnos WHERE col_alumnoid='".$alumnoID."' AND col_asistencia='F' AND col_listaid IN (SELECT col_id FROM tbl_asistencia WHERE col_maestroid IN (".$mt['maestroID'].") AND col_materiaid IN (".$mt['taxID']."))";
                                        $asistenciasSQL = $this->db->prepare($queryAsistencias);
                                        $asistenciasSQL->execute();
                                        $dataAsistencias = $asistenciasSQL->fetch(PDO::FETCH_OBJ);
                                        $inasistencias = $dataAsistencias->Inasistencias;
                                    }
                                }
                            }
                            ?>
                            <td style="width: 54px; text-align: center;"><?php echo intval($inasistencias); ?></td>
                            <td style="width: 54px; text-align: center;"><?php echo ($data_p1 != ''?($data_p1 >= 7?'A':'NA'):''); ?>/<?php echo ($data_p2 != ''?($data_p2 >= 7?'A':'NA'):''); ?></td>


                            <?php
                            $inasistencias = '';
                            foreach($materiasCL as $mt){
                                $queryCalificaciones = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave="'.$mt['clave'].'" AND col_periodoid="'.$periodoid.'" AND col_alumnoid="'.$item['col_id'].'" ';
                                $calificacionesSQL = $this->db->prepare($queryCalificaciones);
                                $calificacionesSQL->execute();
                                if($calificacionesSQL->rowCount()){
                                    $dataCalificaciones = $calificacionesSQL->fetchAll();
                                    foreach($dataCalificaciones as $row){
                                        if($row['col_p1'] != '') $data_p1 = $row['col_p1'];
                                        if($row['col_p2'] != '') $data_p2 = $row['col_p2'];
                                    }

                                    if($inasistencias == ''){
                                        $queryAsistencias = "SELECT COUNT(*) as Inasistencias FROM tbl_asistencia_alumnos WHERE col_alumnoid='".$alumnoID."' AND col_asistencia='F' AND col_listaid IN (SELECT col_id FROM tbl_asistencia WHERE col_maestroid IN (".$mt['maestroID'].") AND col_materiaid IN (".$mt['taxID']."))";
                                        // if($alumnoID == 132) echo $queryAsistencias;
                                        $asistenciasSQL = $this->db->prepare($queryAsistencias);
                                        $asistenciasSQL->execute();
                                        $dataAsistencias = $asistenciasSQL->fetch(PDO::FETCH_OBJ);
                                        $inasistencias = $dataAsistencias->Inasistencias;
                                    }
                                }
                            }
                            ?>
                            <td style="width: 54px; text-align: center;"><?php echo intval($inasistencias); ?></td>
                            <td style="width: 54px; text-align: center;"><?php echo ($data_p1 != ''?($data_p1 >= 7?'A':'NA'):''); ?>/<?php echo ($data_p2 != ''?($data_p2 >= 7?'A':'NA'):''); ?></td>


                            <?php
                            $inasistencias = '';
                            foreach($materiasTR as $mt){
                                $queryCalificaciones = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave="'.$mt['clave'].'" AND col_periodoid="'.$periodoid.'" AND col_alumnoid="'.$item['col_id'].'" ';
                                $calificacionesSQL = $this->db->prepare($queryCalificaciones);
                                $calificacionesSQL->execute();
                                if($calificacionesSQL->rowCount()){
                                    $dataCalificaciones = $calificacionesSQL->fetchAll();
                                    foreach($dataCalificaciones as $row){
                                        if($row['col_p1'] != '') $data_p1 = $row['col_p1'];
                                        if($row['col_p2'] != '') $data_p2 = $row['col_p2'];
                                    }

                                    if($inasistencias == ''){
                                        $queryAsistencias = "SELECT COUNT(*) as Inasistencias FROM tbl_asistencia_alumnos WHERE col_alumnoid='".$alumnoID."' AND col_asistencia='F' AND col_listaid IN (SELECT col_id FROM tbl_asistencia WHERE col_maestroid IN (".$mt['maestroID'].") AND col_materiaid IN (".$mt['taxID']."))";
                                        // if($alumnoID == 132) echo $queryAsistencias;
                                        $asistenciasSQL = $this->db->prepare($queryAsistencias);
                                        $asistenciasSQL->execute();
                                        $dataAsistencias = $asistenciasSQL->fetch(PDO::FETCH_OBJ);
                                        $inasistencias = $dataAsistencias->Inasistencias;
                                    }
                                }
                            }
                            ?>
                            <td style="width: 54px; text-align: center;"><?php echo intval($inasistencias); ?></td>
                            <td style="width: 54px; text-align: center;"><?php echo ($data_p1 != ''?($data_p1 >= 7?'A':'NA'):''); ?>/<?php echo ($data_p2 != ''?($data_p2 >= 7?'A':'NA'):''); ?></td>

                            <!-- PP -->
                            <td style="width: 52px; text-align: center;"></td>
                            <td style="width: 52px; text-align: center;"></td>
                            <td style="width: 52px; text-align: center;"></td>
                            <?php } ?>
                            <!-- SS -->
                            <?php
                            $queryServicioSocial = "SELECT IF(COUNT(*) > 0, 'Si', 'No') AS estatus FROM tbl_servicio_social_archivos WHERE col_alumnoid='".$alumnoID."' AND col_estatus=5 AND col_servicioid IN (SELECT col_id FROM tbl_servicio_social WHERE col_alumnoid ='".$alumnoID."' AND col_periodoid='".$periodoid."')";
                            $servicioSocialSQL = $this->db->prepare($queryServicioSocial);
                            $servicioSocialSQL->execute();
                            $dataSS = $servicioSocialSQL->fetch(PDO::FETCH_OBJ);
                            ?>
                            <td style="width: 120px; text-align: center;"><?php echo $dataSS->estatus; ?></td>
                            <td style="width: 120px; text-align: center;"></td>
                            <td style="width: 120px; text-align: center;"></td>
                            <td style="width: 120px; text-align: center;"></td>
                            <td style="width: 120px; text-align: center;"></td>
                        </tr>
                    <?php
                    $a++;
                    } ?>
                </tbody>
            </table>
            <br/><br/>
            <table border="1" cellspacing="0" cellpadding="5" style="table-layout: fixed; width: 100%;">
                <tbody>
                    <tr><td colspan="2"><b>RUBROS</b></td></tr>
                    <tr><td colspan="2">IN: INASISTENENCIAS</td></tr>
                    <tr><td colspan="2">CAL: CALIFICACIONES</td></tr>
                    <tr><td colspan="2">P1: Primer Parcial</td></tr>
                    <tr><td colspan="2">EF: Examen Final </td></tr>
                    <tr><td colspan="2">P2: Segundo Parcial</td></tr>
                    <tr><td colspan="2">CF: Calificacion Final</td></tr>
                    <tr><td colspan="2">ET: Examen Extraordinario</td></tr>
                    <tr><td colspan="2">TS: Titulo de Suficiencia</td></tr>
                </tbody>
            </table>

            <?php
            $html = ob_get_contents();
            ob_end_clean();
        }
        if($isPosgrado == true) {

            // Cambio de query para mostrar todos los alumnos incluso los de baja, peticion 18 de Septiembre de 2020
            $query = "SELECT a.col_generacion_start, a.col_generacion_end, a.col_control, a.col_id, CONCAT(a.col_apellidos, ' ', a.col_nombres) as nombreAlumno, col_estatus FROM tbl_alumnos_taxonomia c LEFT OUTER JOIN tbl_alumnos a ON a.col_id=c.col_alumnoid WHERE a.col_nombres!='' AND c.col_periodoid='".$periodoid."' GROUP BY c.col_alumnoid ORDER BY a.col_apellidos ASC";
            $sth = $this->db->prepare($query);
            $sth->execute();
            $todosAlumnos = $sth->fetchAll();

            $generacion = '';
            $alumnoIDExtra = 0;
            foreach($todosAlumnos as $alGen) {
                if( $alGen['col_generacion_start'] != '' &&  $alGen['col_generacion_end'] != ''){
                    $generacion = $alGen['col_generacion_start'].' - '.$alGen['col_generacion_end'];
                    $alumnoIDExtra = $alGen['col_id'];
                    break;
                }
            }

            $peridoosGeneracion = getPeriodosGeneracion($periodoid, $alumnoIDExtra, $this->db);



            $tipoModalidad = 'SEMESTRE';
            if($carreraData['modalidad'] == 'Cuatrimestral') $tipoModalidad = 'CUATRIMESTRE';


            ob_start();
            ?>
            <table border="0" cellspacing="0" cellpadding="5" style="table-layout: fixed; width: 100%;">
            <tbody>
                <tr>
                    <td></td><td></td><td></td>
                    <td colspan="7">CONCRETADO DE CALIFICACIONES</td>
                </tr>
                <tr>
                    <td></td><td></td>
                    <td colspan="8">
                        <?php echo htmlentities(fixEncode($carreraData['nombre'], false, true)); ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <b>Generaci&oacute;n:</b> <?php echo $generacion; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    </td>
                </tr>
            </tbody>
            </table>

            <br/><br/>


            <?php
                $color1 = '#f5d8a6';
                $color2 = '#afe8fa';
                $color3 = '#e8cfff';
                $color4 = '#cfffcf';

                foreach($peridoosGeneracion as $periodo) {

                    $periodoid = $periodo['col_id'];
                    $sth = $this->db->prepare("SELECT * FROM tbl_calificaciones WHERE col_periodoid='".$periodoid."' GROUP BY col_materia_clave");
                    $sth->execute();
                    $todos = $sth->fetchAll();
                    $a = 1;

                    foreach($todos as $item){

                        $queryMaestro = 'SELECT mt.col_id AS taxID, u.col_id AS maestroID, CONCAT(u.col_firstname, " ", u.col_lastname) AS nombreMaestro FROM tbl_maestros_taxonomia mt LEFT OUTER JOIN tbl_users u ON u.col_id=mt.col_maestroid WHERE mt.col_materia_clave="'.$item['col_materia_clave'].'" AND mt.col_periodoid="'.$periodoid.'"';
                        $maestroSQL = $this->db->prepare($queryMaestro);
                        $maestroSQL->execute();
                        $maestroData = $maestroSQL->fetch(PDO::FETCH_OBJ);


                        $queryMateria = 'SELECT * FROM tbl_materias WHERE col_clave="'.$item['col_materia_clave'].'" AND col_semestre="'.$periodo['col_grado'].'" AND col_plan_estudios="'.$periodo['col_plan_estudios'].'" ';
                        $materiaSQL = $this->db->prepare($queryMateria);
                        $materiaSQL->execute();
                        $materiaData = $materiaSQL->fetch(PDO::FETCH_OBJ);

                        if($periodo['col_grado'] == 1) {
                            $periodoid1 = intval($periodoid);
                            $nombreMaestro1['mat'.$a] = htmlentities(fixEncode($maestroData->nombreMaestro));
                            $nombreMateria1['mat'.$a] = htmlentities(fixEncode($materiaData->col_nombre));
                            $materiasCR1[] = array('clave' => $item['col_materia_clave'], 'taxID' => $maestroData->taxID, 'maestroID' => $maestroData->maestroID, 'maestro' => $maestroData->nombreMaestro, 'materia' => $materiaData->col_nombre);
                        }

                        if($periodo['col_grado'] == 2) {
                            $periodoid2 = intval($periodoid);
                            $nombreMaestro2['mat'.$a] = htmlentities(fixEncode($maestroData->nombreMaestro));
                            $nombreMateria2['mat'.$a] = htmlentities(fixEncode($materiaData->col_nombre));
                            $materiasCR2[] = array('clave' => $item['col_materia_clave'], 'taxID' => $maestroData->taxID, 'maestroID' => $maestroData->maestroID, 'maestro' => $maestroData->nombreMaestro, 'materia' => $materiaData->col_nombre);
                        }

                        if($periodo['col_grado'] == 3) {
                            $periodoid3 = intval($periodoid);
                            $nombreMaestro3['mat'.$a] = htmlentities(fixEncode($maestroData->nombreMaestro));
                            $nombreMateria3['mat'.$a] = htmlentities(fixEncode($materiaData->col_nombre));
                            $materiasCR3[] = array('clave' => $item['col_materia_clave'], 'taxID' => $maestroData->taxID, 'maestroID' => $maestroData->maestroID, 'maestro' => $maestroData->nombreMaestro, 'materia' => $materiaData->col_nombre);
                        }

                        if($periodo['col_grado'] == 4) {
                            $periodoid4 = intval($periodoid);
                            $nombreMaestro4['mat'.$a] = htmlentities(fixEncode($maestroData->nombreMaestro));
                            $nombreMateria4['mat'.$a] = htmlentities(fixEncode($materiaData->col_nombre));
                            $materiasCR4[] = array('clave' => $item['col_materia_clave'], 'taxID' => $maestroData->taxID, 'maestroID' => $maestroData->maestroID, 'maestro' => $maestroData->nombreMaestro, 'materia' => $materiaData->col_nombre);
                        }
                        $a++;
                    }
                }

                // print_r($nombreMaestro1);
                // print_r($nombreMateria1);
                // exit;
            ?>

            <table border="1" cellspacing="0" cellpadding="5" style="table-layout: fixed;width:400%;">
                <tbody>
                    <tr>
                        <td rowspan="3" style="width:80px;">No. Progresivo</td>
                        <td rowspan="3" style="width:100px;">N&deg; DE CONTROL</td>
                        <td rowspan="3" style="width:300px;">NOMBRE DEL ALUMNO (A)</td>
                        <?php if(is_array($nombreMateria1)){ ?>
                        <td colspan="7" style="text-align: center;">P R I M E R&nbsp;&nbsp;&nbsp; S E M E S T R E</td>
                        <?php } ?>
                        <?php if(is_array($nombreMateria2)){ ?>
                        <td colspan="7" style="text-align: center;">S E G U N D O&nbsp;&nbsp;&nbsp; S E M E S T R E</td>
                        <?php } ?>
                        <?php if(is_array($nombreMateria3)){ ?>
                        <td colspan="7" style="text-align: center;">T E R C E R&nbsp;&nbsp;&nbsp; S E M E S T R E</td>
                        <?php } ?>
                        <?php if(is_array($nombreMateria4)){ ?>
                        <td colspan="7" style="text-align: center;">C U A R T O&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; S E M E S T R E</td>
                        <?php } ?>
                        <?php if(is_array($nombreMateria1)){ ?>
                        <td style="text-align:center;" rowspan="3">PROMEDIO 1s</td>
                        <?php } ?>
                        <?php if(is_array($nombreMateria2)){ ?>
                        <td style="text-align:center;" rowspan="3">PROMEDIO 2s</td>
                        <?php } ?>
                        <?php if(is_array($nombreMateria3)){ ?>
                        <td style="text-align:center;" rowspan="3">PROMEDIO 3s</td>
                        <?php } ?>
                        <?php if(is_array($nombreMateria4)){ ?>
                        <td style="text-align:center;" rowspan="3">PROMEDIO 4s</td>
                        <?php } ?>
                        <td style="text-align:center;" rowspan="3">PROMEDIO FINAL</td>
                    </tr>
                    <tr>
                        <?php if(is_array($nombreMaestro1)){ ?>
                            <td style="background-color:<?php echo $color1; ?>;"><?php echo $nombreMaestro1['mat1']; ?></td>
                            <td style="background-color:<?php echo $color1; ?>;"><?php echo $nombreMaestro1['mat2']; ?></td>
                            <td style="background-color:<?php echo $color1; ?>;"><?php echo $nombreMaestro1['mat3']; ?></td>
                            <td style="background-color:<?php echo $color1; ?>;"><?php echo $nombreMaestro1['mat4']; ?></td>
                            <td style="background-color:<?php echo $color1; ?>;"><?php echo $nombreMaestro1['mat5']; ?></td>
                            <td style="background-color:<?php echo $color1; ?>;"><?php echo $nombreMaestro1['mat6']; ?></td>
                            <td style="width:80px;text-align:center;background-color:<?php echo $color1; ?>;" rowspan="2">PROMEDIO</td>
                        <?php } ?>
                        <?php if(is_array($nombreMaestro2)){ ?>
                            <td style="background-color:<?php echo $color2; ?>;"><?php echo $nombreMaestro2['mat1']; ?></td>
                            <td style="background-color:<?php echo $color2; ?>;"><?php echo $nombreMaestro2['mat2']; ?></td>
                            <td style="background-color:<?php echo $color2; ?>;"><?php echo $nombreMaestro2['mat3']; ?></td>
                            <td style="background-color:<?php echo $color2; ?>;"><?php echo $nombreMaestro2['mat4']; ?></td>
                            <td style="background-color:<?php echo $color2; ?>;"><?php echo $nombreMaestro2['mat5']; ?></td>
                            <td style="background-color:<?php echo $color2; ?>;"><?php echo $nombreMaestro2['mat6']; ?></td>
                            <td style="width:80px;text-align:center;background-color:<?php echo $color2; ?>;" rowspan="2">PROMEDIO</td>
                        <?php } ?>
                        <?php if(is_array($nombreMaestro3)){ ?>
                            <td style="background-color:<?php echo $color3; ?>;"><?php echo $nombreMaestro3['mat1']; ?></td>
                            <td style="background-color:<?php echo $color3; ?>;"><?php echo $nombreMaestro3['mat2']; ?></td>
                            <td style="background-color:<?php echo $color3; ?>;"><?php echo $nombreMaestro3['mat3']; ?></td>
                            <td style="background-color:<?php echo $color3; ?>;"><?php echo $nombreMaestro3['mat4']; ?></td>
                            <td style="background-color:<?php echo $color3; ?>;"><?php echo $nombreMaestro3['mat5']; ?></td>
                            <td style="background-color:<?php echo $color3; ?>;"><?php echo $nombreMaestro3['mat6']; ?></td>
                            <td style="width:80px;text-align:center;background-color:<?php echo $color3; ?>;" rowspan="2">PROMEDIO</td>
                        <?php } ?>
                        <?php if(is_array($nombreMaestro4)){ ?>
                            <td style="background-color:<?php echo $color4; ?>;"><?php echo $nombreMaestro4['mat1']; ?></td>
                            <td style="background-color:<?php echo $color4; ?>;"><?php echo $nombreMaestro4['mat2']; ?></td>
                            <td style="background-color:<?php echo $color4; ?>;"><?php echo $nombreMaestro4['mat3']; ?></td>
                            <td style="background-color:<?php echo $color4; ?>;"><?php echo $nombreMaestro4['mat4']; ?></td>
                            <td style="background-color:<?php echo $color4; ?>;"><?php echo $nombreMaestro4['mat5']; ?></td>
                            <td style="background-color:<?php echo $color4; ?>;"><?php echo $nombreMaestro4['mat6']; ?></td>
                            <td style="width:80px;text-align:center;background-color:<?php echo $color4; ?>;" rowspan="2">PROMEDIO</td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <?php if(is_array($nombreMateria1)){ ?>
                            <td><?php echo $nombreMateria1['mat1']; ?></td>
                            <td><?php echo $nombreMateria1['mat2']; ?></td>
                            <td><?php echo $nombreMateria1['mat3']; ?></td>
                            <td><?php echo $nombreMateria1['mat4']; ?></td>
                            <td><?php echo $nombreMateria1['mat5']; ?></td>
                            <td><?php echo $nombreMateria1['mat6']; ?></td>
                        <?php } ?>
                        <?php if(is_array($nombreMateria2)){ ?>
                            <td><?php echo $nombreMateria2['mat1']; ?></td>
                            <td><?php echo $nombreMateria2['mat2']; ?></td>
                            <td><?php echo $nombreMateria2['mat3']; ?></td>
                            <td><?php echo $nombreMateria2['mat4']; ?></td>
                            <td><?php echo $nombreMateria2['mat5']; ?></td>
                            <td><?php echo $nombreMateria2['mat6']; ?></td>
                        <?php } ?>
                        <?php if(is_array($nombreMateria3)){ ?>
                            <td><?php echo $nombreMateria3['mat1']; ?></td>
                            <td><?php echo $nombreMateria3['mat2']; ?></td>
                            <td><?php echo $nombreMateria3['mat3']; ?></td>
                            <td><?php echo $nombreMateria3['mat4']; ?></td>
                            <td><?php echo $nombreMateria3['mat5']; ?></td>
                            <td><?php echo $nombreMateria3['mat6']; ?></td>
                        <?php } ?>
                        <?php if(is_array($nombreMateria4)){ ?>
                            <td><?php echo $nombreMateria4['mat1']; ?></td>
                            <td><?php echo $nombreMateria4['mat2']; ?></td>
                            <td><?php echo $nombreMateria4['mat3']; ?></td>
                            <td><?php echo $nombreMateria4['mat4']; ?></td>
                            <td><?php echo $nombreMateria4['mat5']; ?></td>
                            <td><?php echo $nombreMateria4['mat6']; ?></td>
                        <?php } ?>
                    </tr>
                    <?php
                    $a = 1;
                    foreach($todosAlumnos as $item){
                        $promedio = 0;
                        $promedio1 = 0;
                        $promedio2 = 0;
                        $promedio3 = 0;
                        $promedio4 = 0;
                        $alumnoID = $item['col_id'];
                        ?>
                        <tr>
                            <td><?php echo $a; ?></td>
                            <td><?php echo $item['col_control']; ?></td>
                            <td><?php echo htmlentities(fixEncode($item['nombreAlumno'])); ?></td>

                            <?php if(is_array($nombreMateria1)){ ?>
                            <?php
                                foreach($materiasCR1 as $mt){
                                    $queryCalificaciones = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave="'.$mt['clave'].'" AND col_periodoid="'.$periodoid1.'" AND col_alumnoid="'.$alumnoID.'" ';
                                    $calificacionesSQL = $this->db->prepare($queryCalificaciones);
                                    $calificacionesSQL->execute();
                                    $data = $calificacionesSQL->fetch(PDO::FETCH_OBJ);
                                    ?>
                                    <td style="text-align: center;"><?php echo mostrarCalificacion($data->col_cf); ?></td>
                                <?php

                                    if($data->col_ts){
                                        $promedio = $promedio + $data->col_ts;
                                    }else if($data->col_ext){
                                        $promedio = $promedio + $data->col_ext;
                                    }else{
                                        $promedio = $promedio + $data->col_cf;
                                    }
                                    $promedio1 = $calPromedio = number_format($promedio / count($materiasCR1), 1);
                                    //$sumaPromedio = $sumaPromedio + $promedio1;
                                }
                                ?>
                                <td style="text-align: center;background-color:<?php echo $color1; ?>;"><?php echo formatoPromedio($calPromedio); ?></td>
                            <?php } ?>

                            <?php if(is_array($nombreMateria2)){ ?>
                            <?php
                                foreach($materiasCR2 as $mt){
                                    $queryCalificaciones = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave="'.$mt['clave'].'" AND col_periodoid="'.$periodoid2.'" AND col_alumnoid="'.$alumnoID.'" ';
                                    $calificacionesSQL = $this->db->prepare($queryCalificaciones);
                                    $calificacionesSQL->execute();
                                    $data = $calificacionesSQL->fetch(PDO::FETCH_OBJ);
                                    ?>
                                    <td style="text-align: center;"><?php echo mostrarCalificacion($data->col_cf); ?></td>
                                <?php

                                    if($data->col_ts){
                                        $promedio = $promedio + $data->col_ts;
                                    }else if($data->col_ext){
                                        $promedio = $promedio + $data->col_ext;
                                    }else{
                                        $promedio = $promedio + $data->col_cf;
                                    }
                                    $promedio2 = $calPromedio = number_format($promedio / count($materiasCR2), 1);
                                    //$sumaPromedio = $sumaPromedio + $promedio2;
                                }
                                ?>
                                <td style="text-align: center;background-color:<?php echo $color2; ?>;"><?php echo formatoPromedio($calPromedio); ?></td>
                            <?php } ?>

                            <?php if(is_array($nombreMateria3)){ ?>
                            <?php
                                foreach($materiasCR3 as $mt){
                                    $queryCalificaciones = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave="'.$mt['clave'].'" AND col_periodoid="'.$periodoid3.'" AND col_alumnoid="'.$alumnoID.'" ';
                                    $calificacionesSQL = $this->db->prepare($queryCalificaciones);
                                    $calificacionesSQL->execute();
                                    $data = $calificacionesSQL->fetch(PDO::FETCH_OBJ);
                                    ?>
                                    <td style="text-align: center;"><?php echo mostrarCalificacion($data->col_cf); ?></td>
                                <?php

                                    if($data->col_ts){
                                        $promedio = $promedio + $data->col_ts;
                                    }else if($data->col_ext){
                                        $promedio = $promedio + $data->col_ext;
                                    }else{
                                        $promedio = $promedio + $data->col_cf;
                                    }
                                    $promedio3 = $calPromedio = number_format($promedio / count($materiasCR3), 1);
                                    //$sumaPromedio = $sumaPromedio + $promedio3;
                                }
                                ?>
                                <td style="text-align: center;background-color:<?php echo $color3; ?>;"><?php echo formatoPromedio($calPromedio); ?></td>
                            <?php } ?>

                            <?php if(is_array($nombreMateria4)){ ?>
                            <?php
                                foreach($materiasCR4 as $mt){
                                    $queryCalificaciones = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave="'.$mt['clave'].'" AND col_periodoid="'.$periodoid4.'" AND col_alumnoid="'.$alumnoID.'" ';
                                    $calificacionesSQL = $this->db->prepare($queryCalificaciones);
                                    $calificacionesSQL->execute();
                                    $data = $calificacionesSQL->fetch(PDO::FETCH_OBJ);
                                    ?>
                                    <td style="text-align: center;"><?php echo mostrarCalificacion($data->col_cf); ?></td>
                                <?php

                                    if($data->col_ts){
                                        $promedio = $promedio + $data->col_ts;
                                    }else if($data->col_ext){
                                        $promedio = $promedio + $data->col_ext;
                                    }else{
                                        $promedio = $promedio + $data->col_cf;
                                    }
                                    $promedio4 = $calPromedio = number_format($promedio / count($materiasCR4), 1);
                                    //$sumaPromedio = $sumaPromedio + $promedio4;
                                }
                                ?>
                                <td style="text-align: center;background-color:<?php echo $color4; ?>;"><?php echo formatoPromedio($calPromedio); ?></td>
                            <?php } ?>

                            <?php if(is_array($nombreMateria1)){ ?><td style="text-align: center;"><?php echo formatoPromedio($promedio1); ?></td><?php } ?>
                            <?php if(is_array($nombreMateria2)){ ?><td style="text-align: center;"><?php echo formatoPromedio($promedio2); ?></td><?php } ?>
                            <?php if(is_array($nombreMateria3)){ ?><td style="text-align: center;"><?php echo formatoPromedio($promedio3); ?></td><?php } ?>
                            <?php if(is_array($nombreMateria4)){ ?><td style="text-align: center;"><?php echo formatoPromedio($promedio4); ?></td><?php } ?>

                            <?php
                                if(is_array($nombreMateria1)) $totalPromedios = 1;
                                if(is_array($nombreMateria2)) $totalPromedios = 2;
                                if(is_array($nombreMateria3)) $totalPromedios = 3;
                                if(is_array($nombreMateria4)) $totalPromedios = 4;
                                $sumaPromedio = $promedio1 + $promedio2 + $promedio3 + $promedio4;
                                $promedioFinal = number_format($sumaPromedio / $totalPromedios, 1);
                            ?>
                            <td style="text-align: center;"><?php echo formatoPromedio($promedioFinal); ?></td>
                        </tr>
                    <?php
                    $a++;
                    } ?>
                </tbody>
            </table>



            <?php
            $html = ob_get_contents();
            ob_end_clean();

        }

        echo $html;
        exit;
    });


    $this->get('/reporteBajasAlumnos', function (Request $request, Response $response, array $args) {
        include_once(__DIR__ . '/../src/xlsxwriter.class.php');

        $periodoid = intval($_REQUEST['periodo']);


        $filename = 'reporte_titulacion_'.date('Y-m-d').'.xls';
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
        // $gruposPeriodos = getCurrentPeriodos($this->db, 'todos');

        $styles_head = array( 'height' => '1', 'valign'=>'center', 'font'=>'Arial','font-size'=>10,'font-style'=>'bold', 'fill'=>'#fff', 'halign'=>'center', 'border'=>'left,right,top,bottom', 'widths'=>[10,20,55,15,15,20,15,15,50]);
        $styles_heading_big = array( 'height' => '50', 'valign'=>'center', 'font'=>'Arial','font-size'=>12,'font-style'=>'bold', 'fill'=>'#fff', 'halign'=>'left', 'border'=>'bottom');
        $styles2 = array( 'height' => '30', 'valign'=>'center', 'font'=>'Arial','font-size'=>9,'font-style'=>'normal', 'fill'=>'#eee', 'halign'=>'center', 'border'=>'left,right,top,bottom');
        $styles3 = array( 'height' => '30', 'valign'=>'center', 'font'=>'Arial','font-size'=>9,'font-style'=>'normal', 'fill'=>'#fff', 'halign'=>'center', 'border'=>'left,right,top,bottom');
        // $gruposPeriodos = array(128);

        // $periodoData = getPeriodo($periodoid, $this->db, false);
        // $carreraData = getCarrera($periodoData->col_carreraid, $this->db);

        $sheet = 'BAJAS TEMPORALES & DEFINITIVAS';
        $writer->writeSheetHeader($sheet, array('' => string), $styles_head);
        $writer->writeSheetRow($sheet, array(''));
        $writer->writeSheetRow($sheet, array('BAJAS TEMPORALES Y DEFINITIVAS'), $styles_heading_big);
        $writer->writeSheetRow($sheet, array(''));
        $writer->writeSheetRow($sheet, array(''));

        $columnas = array('No.', 'No. de Control', 'Nombre Completo', 'Semestre', 'Grupo', 'Fecha de Baja', 'Temporal', 'Definitiva', 'Motivo de Baja');

        $writer->writeSheetRow($sheet, $columnas, $styles2);

        $sth = $this->db->prepare("SELECT *, CONCAT(col_nombres, ' ', col_apellidos) as nombreAlumno FROM tbl_alumnos ORDER BY col_nombres DESC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $a = 1;
        foreach($todos as $item){

            $query_alumno = 'SELECT col_alumnoid, MAX(col_fecha_baja) AS fechaBaja, COUNT(*) as totalBajas FROM tbl_alumnos_bajas WHERE col_alumnoid="'.$item['col_id'].'"';
            $bajAlumno = $this->db->prepare($query_alumno);
            $bajAlumno->execute();
            $alumnoData = $bajAlumno->fetch(PDO::FETCH_OBJ);

            if($alumnoData->totalBajas > 0) {

                if($alumnoData->totalBajas == 1) {
                    $temporal = 'Si';
                    $definitiva = 'No';
                }

                if($alumnoData->totalBajas >= 2) {
                    $temporal = 'No';
                    $definitiva = 'Si';
                }
                $carreraData = getCarrera($item['col_carrera'], $this->db);
                $periodoData = getPeriodo($item['col_periodoid'], $this->db, false);
                $nivel = $carreraData['modalidad'];
                if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral'){
                    $nivel = 'Licenciatura';
                }

                $_columnas = array($a, $item['col_control'], $item['nombreAlumno'], $periodoData->col_grado, $periodoData->col_grupo, $alumnoData->fechaBaja, $temporal, $definitiva, '');
                $writer->writeSheetRow($sheet, $_columnas, $styles3);
                $a++;
            }
        }





        $writer->writeToStdOut();
        exit();
    });

    $this->get('/reporteTramitesTitulacion', function (Request $request, Response $response, array $args) {

        // $anio = intval($_REQUEST['anio']);

        if(!isset($_REQUEST['debug']) || $_REQUEST['debug'] == 0){
            $filename = 'reporte_titulacion.xls';
            header('Content-disposition: attachment; filename="'.$filename.'"');
            //header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
        }else{
            header('Content-Type: text/html; charset=utf-8');
        }

        ob_start();
        ?>
        <table border="0" cellspacing="0" cellpadding="5" style="table-layout: fixed; width: 100%;">
        <tbody>
            <tr>
                <td></td><td></td><td></td><td></td><td></td>
                <td>REPORTE DE TITULACIÓN</td>
                <td></td><td></td><td></td><td></td><td></td>
            </tr>
        </tbody>
        </table>
        <br/><br/>
        <table border="1" cellspacing="0" cellpadding="5" style="table-layout: fixed; width: 100%;">
        <thead>
            <tr>
                <td style="width: 300px; text-align: center;font-weight:bold;" valign="middle">NOMBRE DEL ALUMNO (A)</td>
                <td style="width: 100px; text-align: center;font-weight:bold;" valign="middle">NIVEL</td>
                <td style="width: 120px; text-align: center;font-weight:bold;" valign="middle">GENERACIÓN</td>
                <td style="width: 100px; text-align: center;font-weight:bold;" valign="middle">PROMEDIO<br/>FINAL</td>
                <td style="width: 300px; text-align: center;font-weight:bold;" valign="middle">TIPO DE TRAMITE</td>
                <td style="width: 100px; text-align: center;font-weight:bold;" valign="middle">FOLIO</td>
                <td style="width: 100px; text-align: center;font-weight:bold;" valign="middle">AÑO DE<br/>EXPEDICION</td>
                <td style="width: 100px; text-align: center;font-weight:bold;" valign="middle">PAGOS DE<br/>HACIENDA</td>
                <td style="width: 100px; text-align: center;font-weight:bold;" valign="middle">PAGOS A LA<br/>FLDCH/INEF</td>
                <td style="width: 120px; text-align: center;font-weight:bold;" valign="middle">FECHA TOMA<br/>DE PROTESTA</td>
                <td style="width: 120px; text-align: center;font-weight:bold;" valign="middle">FECHA DE<br/>ENTREGA A S.E.</td>
            </tr>
        </thead>
        <tbody>
            <?php
                    $query = "SELECT a.*, CONCAT(a.col_apellidos,' ', a.col_nombres) as nombreAlumno, c.col_modalidad AS modalidadCarrera, c.col_nombre_corto AS nivelCarrera "
                    ."FROM tbl_alumnos a "
                    ."LEFT JOIN tbl_periodos p ON p.col_id=a.col_periodoid "
                    ."LEFT JOIN tbl_carreras c ON c.col_id=a.col_carrera "
                    ."WHERE a.col_estatus='activo' AND (c.col_modalidad<3 AND p.col_grado=8) OR (c.col_modalidad>2 AND p.col_grado=4) ORDER BY a.col_apellidos ASC";
                    $sth = $this->db->prepare($query);
                    $sth->execute();
                    $todos = $sth->fetchAll();
                    $a = 1;
                    foreach($todos as $item){
                        $queryDocumento = 'SELECT * FROM tbl_atencion WHERE col_alumnoid="'.$item['col_id'].'" AND col_tipo=14';
                        $stDocumento = $this->db->prepare($queryDocumento);
                        $stDocumento->execute();
                        $dataDocumento = $stDocumento->fetch(PDO::FETCH_OBJ);

                        ?>
                            <tr>
                                <td valign="middle"><?php echo fixEncode($item['nombreAlumno']); ?></td>
                                <td valign="middle" style="text-align: center;"><?php echo $item['nivelCarrera']; ?></td>
                                <td valign="middle" style="text-align: center;"><?php echo $item['col_generacion_start'].'-'.$item['col_generacion_end']; ?></td>
                                <td valign="middle" style="text-align: center;"><?php echo getPromedioFinalAlumno($item['col_id'], $this->db); ?></td>
                                <td valign="middle">CERTIFICADO TOTAL</td>
                                <td valign="middle"><?php echo (intval($dataDocumento->col_folio) == 0?'':str_pad($dataDocumento->col_folio, 5, "0", STR_PAD_LEFT)); ?></td>
                                <td valign="middle"><?php echo ($dataDocumento->col_fecha == ''?'':date('Y', strtotime($dataDocumento->col_fecha))); ?></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                            </tr>
                        <?php

                        $queryDocumento = 'SELECT * FROM tbl_atencion WHERE col_alumnoid="'.$item['col_id'].'" AND col_tipo=13';
                        $stDocumento = $this->db->prepare($queryDocumento);
                        $stDocumento->execute();
                        $dataDocumento = $stDocumento->fetch(PDO::FETCH_OBJ);

                        ?>
                            <tr>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle">CERTIFICADO PARCIAL</td>
                                <td valign="middle"><?php echo (intval($dataDocumento->col_folio) == 0?'':str_pad($dataDocumento->col_folio, 5, "0", STR_PAD_LEFT)); ?></td>
                                <td valign="middle"><?php echo ($dataDocumento->col_fecha == ''?'':date('Y', strtotime($dataDocumento->col_fecha))); ?></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                            </tr>
                            <?php

                            $tipoActa = 30;
                            if($item['modalidadCarrera'] > 2) $tipoActa = 29;
                            $queryDocumento = 'SELECT * FROM tbl_atencion WHERE col_alumnoid="'.$item['col_id'].'" AND col_tipo='.$tipoActa;
                            $stDocumento = $this->db->prepare($queryDocumento);
                            $stDocumento->execute();
                            $dataDocumento = $stDocumento->fetch(PDO::FETCH_OBJ);

                            ?>
                            <tr>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle">ACTA DE EXAMEN</td>
                                <td valign="middle"><?php echo (intval($dataDocumento->col_folio) == 0?'':str_pad($dataDocumento->col_folio, 5, "0", STR_PAD_LEFT)); ?></td>
                                <td valign="middle"><?php echo ($dataDocumento->col_fecha == ''?'':date('Y', strtotime($dataDocumento->col_fecha))); ?></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                            </tr>
                            <?php

                            $queryDocumento = 'SELECT * FROM tbl_atencion WHERE col_alumnoid="'.$item['col_id'].'" AND col_tipo=100';
                            $stDocumento = $this->db->prepare($queryDocumento);
                            $stDocumento->execute();
                            $dataDocumento = $stDocumento->fetch(PDO::FETCH_OBJ);

                            ?>
                            <tr>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle">TITULO</td>
                                <td valign="middle"><?php echo (intval($dataDocumento->col_folio) == 0?'':str_pad($dataDocumento->col_folio, 5, "0", STR_PAD_LEFT)); ?></td>
                                <td valign="middle"><?php echo ($dataDocumento->col_fecha == ''?'':date('Y', strtotime($dataDocumento->col_fecha))); ?></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                            </tr>
                            <?php

                            $queryDocumento = 'SELECT * FROM tbl_atencion WHERE col_alumnoid="'.$item['col_id'].'" AND col_tipo=100';
                            $stDocumento = $this->db->prepare($queryDocumento);
                            $stDocumento->execute();
                            $dataDocumento = $stDocumento->fetch(PDO::FETCH_OBJ);

                            ?>
                            <tr>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle">GRADO</td>
                                <td valign="middle"><?php echo (intval($dataDocumento->col_folio) == 0?'':str_pad($dataDocumento->col_folio, 5, "0", STR_PAD_LEFT)); ?></td>
                                <td valign="middle"><?php echo ($dataDocumento->col_fecha == ''?'':date('Y', strtotime($dataDocumento->col_fecha))); ?></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                            </tr>
                            <?php

                            $queryDocumento = 'SELECT * FROM tbl_atencion WHERE col_alumnoid="'.$item['col_id'].'" AND col_tipo=19';
                            $stDocumento = $this->db->prepare($queryDocumento);
                            $stDocumento->execute();
                            $dataDocumento = $stDocumento->fetch(PDO::FETCH_OBJ);

                            ?>
                            <tr>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle">CONSTANCIA CERTIFICADA</td>
                                <td valign="middle"><?php echo (intval($dataDocumento->col_folio) == 0?'':str_pad($dataDocumento->col_folio, 5, "0", STR_PAD_LEFT)); ?></td>
                                <td valign="middle"><?php echo ($dataDocumento->col_fecha == ''?'':date('Y', strtotime($dataDocumento->col_fecha))); ?></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                            </tr>
                            <?php


                            $queryDocumento = 'SELECT * FROM tbl_atencion WHERE col_alumnoid="'.$item['col_id'].'" AND col_tipo=9';
                            $stDocumento = $this->db->prepare($queryDocumento);
                            $stDocumento->execute();
                            $dataDocumento = $stDocumento->fetch(PDO::FETCH_OBJ);

                            ?>
                            <tr>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle">SERVICIO SOCIAL</td>
                                <td valign="middle"><?php echo (intval($dataDocumento->col_folio) == 0?'':str_pad($dataDocumento->col_folio, 5, "0", STR_PAD_LEFT)); ?></td>
                                <td valign="middle"><?php echo ($dataDocumento->col_fecha == ''?'':date('Y', strtotime($dataDocumento->col_fecha))); ?></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                                <td valign="middle"></td>
                            </tr>
                        <?php

                    }
            ?>
        </tbody>
        </table>
        <?php
        $html = ob_get_contents();
        ob_end_clean();

        echo $html;

        exit();
    });

    $this->get('/credencialesAlumnos', function (Request $request, Response $response, array $args) {
        include_once(__DIR__ . '/../src/xlsxwriter.class.php');

        $periodoid = intval($_REQUEST['periodo']);


        $filename = 'reporte_credenciales_'.date('Y-m-d').'.xls';
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
        // $gruposPeriodos = getCurrentPeriodos($this->db, 'todos');

        $styles_head = array( 'height' => '1', 'valign'=>'center', 'font'=>'Arial','font-size'=>10,'font-style'=>'bold', 'fill'=>'#fff', 'halign'=>'center', 'border'=>'left,right,top,bottom', 'widths'=>[10,50,20,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50,50]);
        $styles_heading_big = array( 'height' => '50', 'valign'=>'center', 'font'=>'Arial','font-size'=>12,'font-style'=>'bold', 'fill'=>'#fff', 'halign'=>'left', 'border'=>'bottom');
        $styles2 = array( 'height' => '30', 'valign'=>'center', 'font'=>'Arial','font-size'=>9,'font-style'=>'normal', 'fill'=>'#eee', 'halign'=>'center', 'border'=>'left,right,top,bottom');
        $styles3 = array( 'height' => '30', 'valign'=>'center', 'font'=>'Arial','font-size'=>9,'font-style'=>'normal', 'fill'=>'#fff', 'halign'=>'center', 'border'=>'left,right,top,bottom');
        // $gruposPeriodos = array(128);

        // $periodoData = getPeriodo($periodoid, $this->db, false);
        // $carreraData = getCarrera($periodoData->col_carreraid, $this->db);

        $sheet = 'Credenciales';
        $writer->writeSheetHeader($sheet, array('' => string), $styles_head);
        $writer->writeSheetRow($sheet, array(''));
        $writer->writeSheetRow($sheet, array('DATOS PARA CREDENCIALES: LICENCIATURAS SEMESTRAL, CUATRIMESTRAL y POSGRADOS'), $styles_heading_big);
        $writer->writeSheetRow($sheet, array(''));
        $writer->writeSheetRow($sheet, array(''));

        $columnas = array('No.', 'Nombre Completo', 'No. de Control', 'Vigencia', 'Nivel de Estudios', 'Tipo de Sangre', 'Alergias', 'No. de Poliza de Seguro', 'No. de Seguridad Social', 'Domicilio', 'Nombre y No. de Contacto de Emergencia');

        $writer->writeSheetRow($sheet, $columnas, $styles2);

        $sth = $this->db->prepare("SELECT *, CONCAT(col_apellidos,' ', col_nombres) as nombreAlumno FROM tbl_alumnos WHERE col_estatus='activo' AND col_egresado=0 ORDER BY col_apellidos ASC");
        $sth->execute();
        $todos = $sth->fetchAll();
        $a = 1;
        foreach($todos as $item){
            $carreraData = getCarrera($item['col_carrera'], $this->db);
            $nivel = $carreraData['modalidad'];
            $carreraNombre = $carreraData['nombre'];
            if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral'){
                $nivel = 'Licenciatura';
            }

            $_columnas = array($a, $item['nombreAlumno'], $item['col_control'], '', $carreraNombre, $item['col_sangre'], $item['col_enfermedades'], $item['col_seguro_folio'], '', $item['col_direccion'], $item['col_rep_nombres'].' '.$item['col_rep_apellidos'].', '.$item['col_rep_telefono']);
            $writer->writeSheetRow($sheet, $_columnas, $styles3);
            $a++;
        }





        $writer->writeToStdOut();
        exit();
    });

    $this->get('/inventarioDocumentos', function (Request $request, Response $response, array $args) {
        global $nombreInstituto, $claveInstitulo;
        include_once(__DIR__ . '/../src/xlsxwriter.class.php');

        //$periodoid = intval($_REQUEST['periodo']);


        $filename = 'reporte_inventario_documentos_'.date('Y-m-d').'.xls';
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
        $gruposPeriodos = getCurrentPeriodos($this->db, 'todos');

        $styles_head = array( 'height' => '1', 'valign'=>'center', 'font'=>'Arial','font-size'=>10,'font-style'=>'bold', 'fill'=>'#fff', 'halign'=>'center', 'border'=>'left,right,top,bottom', 'widths'=>[10,20,50,30,25,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30,30]);
        $styles_heading_big = array( 'height' => '50', 'valign'=>'center', 'font'=>'Arial','font-size'=>16,'font-style'=>'bold', 'fill'=>'#fff', 'halign'=>'left', 'border'=>'bottom');
        $styles2 = array( 'height' => '30', 'valign'=>'center', 'font'=>'Arial','font-size'=>9,'font-style'=>'normal', 'fill'=>'#eee', 'halign'=>'center', 'border'=>'left,right,top,bottom');
        $styles3 = array( 'height' => '30', 'valign'=>'center', 'font'=>'Arial','font-size'=>9,'font-style'=>'normal', 'fill'=>'#fff', 'halign'=>'center', 'border'=>'left,right,top,bottom');
        // $gruposPeriodos = array(128);

        //$periodoData = getPeriodo($periodoid, $this->db, false);
        //$carreraData = getCarrera($periodoData->col_carreraid, $this->db);

        $sheet = 'Inventario';
        $writer->writeSheetHeader($sheet, array('' => string), $styles_head);
        $writer->writeSheetRow($sheet, array(''));
        $writer->writeSheetRow($sheet, array(strtoupper($nombreInstituto)), $styles_heading_big);
        //$writer->writeSheetRow($sheet, array($carreraData['nombre'], '', '', 'Generación:'));
        $writer->writeSheetRow($sheet, array(''));
        $writer->writeSheetRow($sheet, array(''));

        $columnas = array('No.', 'No. de Control', 'Nombre del Alumno', 'Nivel de Estudios', 'Generación');

        $sth = $this->db->prepare("SELECT * FROM tbl_documentos WHERE col_alumnos='1' ORDER BY TRIM(col_nombre) ASC");
        $sth->execute();
        $todosDocumentos = $sth->fetchAll();

        $i = 0;
        foreach($todosDocumentos as $item){
            $columnasIndex[$i] = intval($item['col_id']);
            $columnas[] = utf8_encode($item['col_nombre']);
            $i++;
        }
        $writer->writeSheetRow($sheet, $columnas, $styles2);
        $a = 1;
        $query = "SELECT t.*, a.col_carrera AS carreraID, CONCAT(a.col_generacion_start, '-', a.col_generacion_end) AS generacion, a.col_documentos AS documentos, t.col_alumnoid AS alumnoID, CONCAT(a.col_apellidos, ' ', a.col_nombres) as nombreAlumno, a.col_control AS numeroControl FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid LEFT JOIN tbl_carreras c ON c.col_id=a.col_carrera WHERE c.col_modalidad=1 OR c.col_modalidad=2 GROUP BY t.col_alumnoid ORDER BY a.col_nombres DESC";

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        foreach($todos as $item){
            if(trim($item['nombreAlumno']) == '') continue;
            $carreraData = getCarrera($item['carreraID'], $this->db);
            $docs = unserialize(base64_decode($item['documentos']));


            $_columnas = array($a, $item['numeroControl'], $item['nombreAlumno'], $carreraData['tipo_modalidad'], $item['generacion']);
            $i = 0;
            foreach($todosDocumentos as $item){
                $_columnas[] = ($docs[$item['col_id']] != ''?'si':'no');
                $i++;
            }

            $writer->writeSheetRow($sheet, $_columnas, $styles3);
            $a++;
        }

        $query = "SELECT t.*, a.col_carrera AS carreraID, CONCAT(a.col_generacion_start, '-', a.col_generacion_end) AS generacion, a.col_documentos AS documentos, t.col_alumnoid AS alumnoID, CONCAT(a.col_apellidos, ' ', a.col_nombres) as nombreAlumno, a.col_control AS numeroControl FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid LEFT JOIN tbl_carreras c ON c.col_id=a.col_carrera WHERE c.col_modalidad=3 GROUP BY t.col_alumnoid ORDER BY a.col_nombres DESC";

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        foreach($todos as $item){
            if(trim($item['nombreAlumno']) == '') continue;
            $carreraData = getCarrera($item['carreraID'], $this->db);
            $docs = unserialize(base64_decode($item['documentos']));


            $_columnas = array($a, $item['numeroControl'], $item['nombreAlumno'], $carreraData['tipo_modalidad'], $item['generacion']);
            $i = 0;
            foreach($todosDocumentos as $item){
                $_columnas[] = ($docs[$item['col_id']] != ''?'si':'no');
                $i++;
            }

            $writer->writeSheetRow($sheet, $_columnas, $styles3);
            $a++;
        }


        $query = "SELECT t.*, a.col_carrera AS carreraID, CONCAT(a.col_generacion_start, '-', a.col_generacion_end) AS generacion, a.col_documentos AS documentos, t.col_alumnoid AS alumnoID, CONCAT(a.col_apellidos, ' ', a.col_nombres) as nombreAlumno, a.col_control AS numeroControl FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid LEFT JOIN tbl_carreras c ON c.col_id=a.col_carrera WHERE c.col_modalidad=4 GROUP BY t.col_alumnoid ORDER BY a.col_nombres DESC";

        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        foreach($todos as $item){
            if(trim($item['nombreAlumno']) == '') continue;
            $carreraData = getCarrera($item['carreraID'], $this->db);
            $docs = unserialize(base64_decode($item['documentos']));


            $_columnas = array($a, $item['numeroControl'], $item['nombreAlumno'], $carreraData['tipo_modalidad'], $item['generacion']);
            $i = 0;
            foreach($todosDocumentos as $item){
                $_columnas[] = ($docs[$item['col_id']] != ''?'si':'no');
                $i++;
            }

            $writer->writeSheetRow($sheet, $_columnas, $styles3);
            $a++;
        }




        $writer->writeToStdOut();
        exit();
    });

    $this->get('/alumnosReprobados', function (Request $request, Response $response, array $args) {

        $periodoid = intval($_REQUEST['periodo']);


        if(!isset($_REQUEST['debug']) || $_REQUEST['debug'] == 0){
            $filename = 'reporte_reprobados_'.date('Y-m-d').'.xls';
            header('Content-disposition: attachment; filename="'.$filename.'"');
            //header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
        }


        // $gruposPeriodos = getCurrentPeriodos($this->db, 'todos');
        // $gruposPeriodos = array(128);

        $periodoData = getPeriodo($periodoid, $this->db, false);
        $carreraData = getCarrera($periodoData->col_carreraid, $this->db);


        $sth = $this->db->prepare("SELECT a.col_control, a.col_id, CONCAT(a.col_nombres, ' ', a.col_apellidos) as nombreAlumno FROM tbl_calificaciones c LEFT OUTER JOIN tbl_alumnos a ON a.col_id=c.col_alumnoid WHERE a.col_periodoid='".$periodoid."' AND c.col_periodoid='".$periodoid."' GROUP BY c.col_alumnoid");
        $sth->execute();
        $todosAlumnos = $sth->fetchAll();

        $sth = $this->db->prepare("SELECT * FROM tbl_calificaciones WHERE col_periodoid='".$periodoid."' GROUP BY col_materia_clave");
        $sth->execute();
        $todos = $sth->fetchAll();
        $a = 1;
        foreach($todos as $item){
            if(!in_array(strtoupper(substr($item['col_materia_clave'], 0, 2)), array('TR', 'TL', 'AC', 'CL'))){
                $queryMaestro = 'SELECT mt.col_id AS taxID, u.col_id AS maestroID, CONCAT(u.col_firstname, " ", u.col_lastname) AS nombreMaestro FROM tbl_maestros_taxonomia mt LEFT OUTER JOIN tbl_users u ON u.col_id=mt.col_maestroid WHERE mt.col_materia_clave="'.$item['col_materia_clave'].'" AND mt.col_periodoid="'.$periodoid.'"';
            }else{

                $periodosRelacionados = getPeriodoTaxoIDS($periodoid, $this->db);
                $queryMaestro = 'SELECT GROUP_CONCAT( mt.col_id SEPARATOR  ",") AS taxID, GROUP_CONCAT(u.col_id SEPARATOR  ",") AS maestroID, CONCAT(u.col_firstname, " ", u.col_lastname) AS nombreMaestro FROM tbl_maestros_taxonomia mt LEFT OUTER JOIN tbl_users u ON u.col_id=mt.col_maestroid WHERE mt.col_materia_clave LIKE "'.claveMateria($item['col_materia_clave']).'%" AND mt.col_periodoid IN ('.implode(',', $periodosRelacionados).')';
            }
            $maestroSQL = $this->db->prepare($queryMaestro);
            $maestroSQL->execute();
            $maestroData = $maestroSQL->fetch(PDO::FETCH_OBJ);


            if(!in_array(strtoupper(substr($item['col_materia_clave'], 0, 2)), array('TR', 'TL', 'AC', 'CL'))){
                $queryMateria = 'SELECT * FROM tbl_materias WHERE col_clave="'.$item['col_materia_clave'].'" AND col_semestre="'.$periodoData->col_grado.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'" ';
            }else{
                $queryMateria = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($item['col_materia_clave']).'%" AND col_semestre="'.$periodoData->col_grado.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'" LIMIT 1';
            }
            $materiaSQL = $this->db->prepare($queryMateria);
            $materiaSQL->execute();
            $materiaData = $materiaSQL->fetch(PDO::FETCH_OBJ);

            if(!in_array(strtoupper(substr($item['col_materia_clave'], 0, 2)), array('TR', 'TL', 'AC', 'CL'))){
                $materiasCR[] = array('clave' => $item['col_materia_clave'], 'debug' => $queryMateria, 'debug2' => $queryMaestro, 'taxID' => $maestroData->taxID, 'maestroID' => $maestroData->maestroID, 'maestro' => $maestroData->nombreMaestro, 'materia' => $materiaData->col_nombre);
            }

        }

        $tipoModalidad = 'SEMESTRE';
        if($carreraData['modalidad'] == 'Cuatrimestral') $tipoModalidad = 'CUATRIMESTRE';
        ob_start();
        ?>
        <table border="0" cellspacing="0" cellpadding="5" style="table-layout: fixed; width: 100%;">
        <tbody>
            <tr>
                <td></td><td></td><td></td><td></td><td></td>
                <td>REPORTE DE REPROBADOS</td>
                <td></td><td></td><td></td><td></td><td></td>
            </tr>
            <tr>
                <td></td><td></td>
                <td colspan="8">
                    <b>CARRERA:</b> <?php echo $carreraData['nombre']; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b>PERIODO ESCOLAR:</b> <?php echo $periodoData->col_nombre; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <b><?php echo $tipoModalidad; ?>:</b> <?php echo $periodoData->col_grado; ?>-<?php echo $periodoData->col_grupo; ?>
                </td>
            </tr>
        </tbody>
        </table>
        <br/><br/>
        <table border="1" cellspacing="0" cellpadding="5" bgcolor="f2f2f2" style="table-layout: fixed; width: 100%;">
        <tbody>
            <tr>
                <td style="width: 40px; text-align: center;font-weight:bold;" valign="middle" rowspan="3" height="60">No.</td>
                <td style="width: 70px; text-align: center;font-weight:bold;" valign="middle" rowspan="3">Control</td>
                <td style="width: 300px; text-align: center;font-weight:bold;" valign="middle" rowspan="3">Alumno</td>
                <?php foreach($materiasCR as $mt){ ?>
                    <td style="width: 360px; text-align: center;font-weight:bold;" colspan="3"><?php echo $mt['materia']; ?></td>
                <?php } ?>
            </tr>
            <tr>
                <?php foreach($materiasCR as $mt){ ?>
                    <td style="text-align: center;font-weight:bold;" colspan="3" height="20"><?php echo $mt['maestro']; ?></td>
                <?php } ?>
            </tr>
            <tr>
                <?php foreach($materiasCR as $mt){ ?>
                    <td style="width: 60px; text-align: center;font-weight:bold;" height="20">CF</td>
                    <td style="width: 60px; text-align: center;font-weight:bold;">EX</td>
                    <td style="width: 60px; text-align: center;font-weight:bold;">TS</td>
                <?php } ?>
            </tr>
        </tbody>
        </table>
        <table border="1" cellspacing="0" cellpadding="5" style="table-layout: fixed; width: 100%;">
            <tbody>
                <?php
                $a = 1;
                foreach($todosAlumnos as $item){
                    $promedio = 0;
                    $alumnoID = $item['col_id'];
                    ?>
                    <tr>
                        <td style="width: 40px; text-align: center;"><?php echo $a; ?></td>
                        <td style="width: 70px; text-align: center;"><?php echo $item['col_control']; ?></td>
                        <td style="width: 300px; text-align: left;"><?php echo $item['nombreAlumno']; ?></td>
                        <?php foreach($materiasCR as $mt){
                            $queryCalificaciones = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave="'.$mt['clave'].'" AND col_periodoid="'.$periodoid.'" AND col_alumnoid="'.$item['col_id'].'" ';
                            $calificacionesSQL = $this->db->prepare($queryCalificaciones);
                            $calificacionesSQL->execute();
                            $data = $calificacionesSQL->fetch(PDO::FETCH_OBJ);
                            ?>
                            <td style="width: 50px; text-align: center;"><?php echo ($data->col_cf == ''?'-':$data->col_cf); ?></td>
                            <td style="width: 50px; text-align: center;"><?php echo ($data->col_ext == ''?'-':$data->col_ext); ?></td>
                            <td style="width: 50px; text-align: center;"><?php echo ($data->col_ts == ''?'-':$data->col_ts); ?></td>
                        <?php

                    }


                    ?>
                    </tr>
                <?php
                $a++;
                } ?>
            </tbody>
        </table>
        <br/><br/>
        <table border="1" cellspacing="0" cellpadding="5" style="table-layout: fixed; width: 100%;">
            <tbody>
                <tr><td><b>RUBROS</b></td></tr>
                <tr><td>CF: Calificacion Final</td></tr>
                <tr><td>ET: Examen Extraordinario</td></tr>
                <tr><td>TS: Titulo de Suficiencia</td></tr>
            </tbody>
        </table>

        <?php
        $html = ob_get_contents();
        ob_end_clean();


        echo $html;
        exit;
    });


});
//Termina routes.reportesOtros.php
