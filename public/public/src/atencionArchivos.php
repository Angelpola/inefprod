<?php

/**
 *
 * Este archivo incluye todas las funciones que permiten generar los archivos PDF que se generan en el modulo
 * de atención de alumnos.
 *
 * Lista de funciones
 *
 * valeDocumentos
 * atencionAmonestacion
 * atencionPsicopedagogica
 * atencionCoordinacion
 * atencionInasistencias
 * atencionModeloEducativo
 * generarKardex
 * generarHistorialAcademico
 * generarHistorialAcademico2
 * generarConstanciaServicio
 * generarCartaPasante
 * generarConstanciaTerminacion
 * generarDiploma
 * generarCertificadoParcial
 * generarCertificadoTotal
 * generarConstanciaSencilla
 * getLetraGrado
 * getSuffixGrado
 * generarConstanciaCalificaciones
 * generarRegistroEscolaridad
 * generarFormatoBuenaConducta
 * generarConstanciaSustentante
 * generarConstanciaSinodales
 * generarTomaProtesta
 * generarTomaProtestaPosgrado
 * generarConstanciaExamenConocimientos
 *
 */

function nuevoValeDocumentos($id, $db, $output = 'I') {
    global $nombreInstituto, $claveInstitulo;
    $alumnoid = intval($id);


    $query = 'SELECT * FROM tbl_vale_documentos WHERE col_id="'.$id.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $valeData = $sth->fetch(PDO::FETCH_OBJ);


    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$valeData->col_autor_creacion.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $selOriginales = json_decode(stripslashes($valeData->col_documentos_originales), true);
    $selCopias = json_decode(stripslashes($valeData->col_documentos_copia), true);

    $alumnoid = $valeData->col_alumnoid;
    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

    $nombreAlumno = fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos);
    // $docs = unserialize(base64_decode($alumnoData->col_documentos));
    // $observaciones = fixEncode($alumnoData->col_documentos_observaciones).' ';
    // $observaciones .= fixEncode($alumnoData->col_documentos_otros);

    $sth = $db->prepare("SELECT * FROM tbl_documentos WHERE col_alumnos='1' AND col_copia=1 ORDER BY col_nombre ASC");
    $sth->execute();
    $todosDocsConCopia = $sth->fetchAll();

    $sth = $db->prepare("SELECT * FROM tbl_documentos WHERE col_alumnos='1' AND col_copia=0 ORDER BY col_nombre ASC");
    $sth->execute();
    $todosDocsSinCopia = $sth->fetchAll();

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnoData->col_carrera, $db);
    $periodoData = getPeriodo($alumnoData->col_periodoid, $db, false);

    $tipoCurso = $carreraData['modalidad_periodo'] ;
    switch($tipoCurso) {
        case 'ldsem':
        case 'ldcua':
        $modalidad = '<span class="box_genre_checked">&nbsp;&#10003;&nbsp;</span> LICENCIATURA&nbsp;&nbsp;&nbsp;&nbsp;<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> MAESTRIA&nbsp;&nbsp;&nbsp;&nbsp;<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> POSGRADO';
        break;

        case 'master':
        $modalidad = '<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> LICENCIATURA&nbsp;&nbsp;&nbsp;&nbsp;<span class="box_genre_checked">&nbsp;&#10003;&nbsp;</span> MAESTRIA&nbsp;&nbsp;&nbsp;&nbsp;<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> POSGRADO';
        break;

        case 'docto':
        $modalidad = '<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> LICENCIATURA&nbsp;&nbsp;&nbsp;&nbsp;<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> MAESTRIA&nbsp;&nbsp;&nbsp;&nbsp;<span class="box_genre_checked">&nbsp;&#10003;&nbsp;</span> POSGRADO';
        break;
    }

    ob_start();
    ?>
    <table border="0" width="100%">
        <tr>
            <td width="25%">
                <img src="<?php echo getLogo('iconoLogo'); ?>" style="width: autopx;height:90px;" alt="FLDCH" border="0"/>
            </td>
            <td width="50%" align="center"><b><?php echo strtoupper($nombreInstituto); ?></b><br/><small>VALE DE ENTREGA-RECEPCIÓN DE DOCUMENTOS</small></td>
            <td width="25%"></td>
        </tr>
    </table>
    <table border="0" width="100%">
        <tr>
            <td align="right">
                <span style="color: #cc0000;">FOLIO Nº: <?php echo undelinedRed(str_pad($valeData->col_folio, 5, "0", STR_PAD_LEFT)); ?></span>
            </td>
        </tr>
        <tr>
            <td align="right">
                <small><?php echo $carreraData['campus']; ?>, A: <?php echo date('d', strtotime($valeData->col_fecha)); ?> DE <?php echo strtoupper(getMes(date('F', strtotime($valeData->col_fecha)))); ?> DE <?php echo date('Y', strtotime($valeData->col_fecha)); ?></small>
            </td>
        </tr>
    </table><br/>
    <table border="0" width="100%">
        <tr>
            <td align="center"><small><?php echo $modalidad; ?></small></td>
        </tr>
    </table><br/>
    <table border="0" width="100%">
        <tr>
            <td align="center"><small>
                <?php if($valeData->col_tipo == 1) { ?>
                    <span class="box_genre_checked">&nbsp;&#10003;&nbsp;</span> RECIBE
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> ENTREGA
                <?php } else { ?>
                    <span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> RECIBE
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <span class="box_genre_checked">&nbsp;&#10003;&nbsp;</span> ENTREGA
                <?php } ?>
            </small></td>
        </tr>
    </table><br/>
    <table border="0" width="100%" style="font-size: 11px;">
        <tr>
            <td width="30%" align="left" valign="top">NOMBRE DEL ALUMNO:</td>
            <td width="70%" class="fill_line" align="left" valign="top"><?php echo strtoupper(fixEncode($nombreAlumno, true, true)); ?></td>
        </tr>
        <tr>
            <td width="30%" align="left" valign="top">PARA REALIZAR EL TRAMITE:</td>
            <td width="70%" class="fill_line" align="left" valign="top"><?php echo fixEncode($valeData->col_tramite); ?></td>
        </tr>
        <tr>
            <td width="30%" align="left" valign="top">CORRESPONDIENTE A:</td>
            <td width="70%" class="fill_line" align="left" valign="top"><?php echo fixEncode($carreraData['nombre']); ?></td>
        </tr>
    </table>
            <br/>
            <div class="tablasFlotantes">
                <table border="0" class="tablaDocumentos">
                        <tr>
                            <th>.</th>
                            <th>ORIGINAL</th>
                            <th>COPIAS</th>
                        </tr>
                    <?php
                    // <td width="10%" align="center" valign="top"><?php echo ($docs[$item['col_id']] != ''?'<span class="box_genre_checked">&nbsp;&#10003;&nbsp;</span>':'<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span>');</td>
                    foreach($todosDocsConCopia as $item) {

                        ?>
                        <tr>
                            <td style="font-size: 10px;" align="left" valign="top"><?php echo fixEncode($item['col_nombre']); ?></td>
                            <td align="center" valign="top">
                                <?php if(in_array($item['col_id'], $selOriginales)) { ?>
                                    <span class="box_genre_checked">&nbsp;&#10003;&nbsp;</span>
                                <?php }else{ ?>
                                    <span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                                <?php } ?>
                            </td>
                            <td align="center" valign="top">
                                <?php if(in_array($item['col_id'], $selCopias)) { ?>
                                    <span class="box_genre_checked">&nbsp;&#10003;&nbsp;</span>
                                <?php }else{ ?>
                                    <span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>

                </table>
            </div>
            <div class="tablasFlotantes">
                <table border="0" class="tablaDocumentos">
                        <tr>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                        </tr>
                    <?php
                    foreach($todosDocsSinCopia as $item) {
                        ?>
                        <tr>
                            <td width="90%" style="font-size: 10px;" align="left" valign="top"><?php echo fixEncode($item['col_nombre']); ?></td>
                            <td width="10%" align="center" valign="top">
                                <?php if(in_array($item['col_id'], $selOriginales)) { ?>
                                    <span class="box_genre_checked">&nbsp;&#10003;&nbsp;</span>
                                <?php }else{ ?>
                                    <span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>

                </table>
            </div>
            <div class="clear:both"></div>

    <br/>
    <p style="text-align: left;font-size: 11px;"><b>PAGOS DE HACIENDA ORIGINALES:</b>&nbsp;&nbsp;<?php echo undelined($valeData->col_hacienda); ?></p>
    <p style="text-align: left;font-size: 11px;"><b>OBSERVACIONES:</b>&nbsp;&nbsp;<?php echo undelined($valeData->col_observaciones); ?></p><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td align="center" width="40%"><small style="text-align: center;font-weight: normal;">RECIBIO</small></td>
            <td align="center" width="20%"></td>
            <td align="center" width="40%"><small style="text-align: center;font-weight: normal;">ENTREGO</small></td>
        </tr>
    </table><br/>
    <table border="0" width="100%">
        <?php
        $firmaRecibio = 'NOMBRE Y FIRMA';
        $firmaEntrego = 'NOMBRE Y FIRMA';
        if($valeData->col_tipo == 1){
            $firmaRecibio = fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true);
        }else{
            $firmaEntrego = fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true);
        }
        ?>
        <tr>
            <td align="center" width="40%" class="firma_up"><?php echo $firmaRecibio; ?></td>
            <td align="center" width="20%"></td>
            <td align="center" width="40%" class="firma_up"><?php echo $firmaEntrego; ?></td>
        </tr>
    </table>
    <table border="0" width="100%" style="margin-top:20px;">
        <tr>
            <td align="center" style="font-size: 9px;"><b>ESTE DOCUMENTO NO ES VALIDO SI PRESENTA RASPADURAS O ENMENDADURAS</b></td>
        <tr>
    </table>
    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader();
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS(), 1);
    $mpdf->WriteHTML($html, 2);

    $mpdf->Output('valeDocumentos.pdf', $output);

    // die();
}

function valeDocumentos($id, $userID, $db, $output = 'I') {
    global $nombreInstituto, $claveInstitulo;
    $alumnoid = intval($id);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

    $nombreAlumno = fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos);
    $docs = unserialize(base64_decode($alumnoData->col_documentos));
    $observaciones = fixEncode($alumnoData->col_documentos_observaciones).' ';
    $observaciones .= fixEncode($alumnoData->col_documentos_otros);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$userID.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $sth = $db->prepare("SELECT * FROM tbl_documentos WHERE col_alumnos='1' ORDER BY col_nombre ASC");
    $sth->execute();
    $todosDocs = $sth->fetchAll();

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnoData->col_carrera, $db);
    $periodoData = getPeriodo($alumnoData->col_periodoid, $db, false);

    $tipoCurso = $carreraData['modalidad_periodo'] ;
    switch($tipoCurso) {
        case 'ldsem':
        case 'ldcua':
        $modalidad = '<span class="box_genre_checked">&nbsp;&#10003;&nbsp;</span> LICENCIATURA&nbsp;&nbsp;&nbsp;&nbsp;<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> MAESTRIA&nbsp;&nbsp;&nbsp;&nbsp;<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> POSGRADO';
        break;

        case 'master':
        $modalidad = '<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> LICENCIATURA&nbsp;&nbsp;&nbsp;&nbsp;<span class="box_genre_checked">&nbsp;&#10003;&nbsp;</span> MAESTRIA&nbsp;&nbsp;&nbsp;&nbsp;<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> POSGRADO';
        break;

        case 'docto':
        $modalidad = '<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> LICENCIATURA&nbsp;&nbsp;&nbsp;&nbsp;<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> MAESTRIA&nbsp;&nbsp;&nbsp;&nbsp;<span class="box_genre_checked">&nbsp;&#10003;&nbsp;</span> POSGRADO';
        break;
    }

    ob_start();
    echo pdfHeader('<b>'.strtoupper($nombreInstituto).'</b><br/><small>VALE DE ENTREGA-RECEPCIÓN DE DOCUMENTOS</small>');
    ?>
    <table border="0" width="100%">
        <tr>
            <td align="right"><small><?php echo $carreraData['campus']; ?>, A: <?php echo date('d'); ?> DE <?php echo strtoupper(getMes(date('F'))); ?> DE <?php echo date('Y'); ?></small></td>
        </tr>
    </table><br/>
    <table border="0" width="100%">
        <tr>
            <td align="center"><small><?php echo $modalidad; ?></small></td>
        </tr>
    </table><br/>
    <table border="0" width="100%">
        <tr>
            <td align="center"><small><span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> RECIBE&nbsp;&nbsp;&nbsp;&nbsp;<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span> ENTREGA</small></td>
        </tr>
    </table><br/>
    <table border="0" width="100%" style="font-size: 11px;">
        <tr>
            <td width="30%" align="left" valign="top">NOMBRE DEL ALUMNO:</td>
            <td width="70%" class="fill_line" align="left" valign="top"><?php echo strtoupper(fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos, true, true)); ?></td>
        </tr>
        <tr>
            <td width="30%" align="left" valign="top">PARA REALIZAR EL TRAMITE:</td>
            <td width="70%" class="fill_line" align="left" valign="top"></td>
        </tr>
        <tr>
            <td width="30%" align="left" valign="top">CORRESPONDIENTE A:</td>
            <td width="70%" class="fill_line" align="left" valign="top"></td>
        </tr>
    </table>
    <br/><br/>
    <table border="0" width="100%" style="font-size: 11px;">
        <tr>
        <?php
        $i = 0;
        foreach($todosDocs as $item) {
            ?>
            <td width="40%" align="left" valign="top"><?php echo fixEncode($item['col_nombre']); ?></td>
            <td width="10%" align="center" valign="top"><?php echo ($docs[$item['col_id']] != ''?'<span class="box_genre_checked">&nbsp;&#10003;&nbsp;</span>':'<span class="box_genre">&nbsp;&nbsp;&nbsp;&nbsp;</span>'); ?></td>
            <?php
            $i++;
            if($i == 2){
                echo '</tr><tr>';
                $i = 0;
            }
        }
        ?>
    </table>


    <br/><br/>
    <p style="text-align: left;font-size: 11px;"><b>PAGOS DE HACIENDA ORIGINALES:</b>&nbsp;&nbsp;_______________________________________________________</p>
    <p style="text-align: left;font-size: 11px;"><b>OBSERVACIONES:</b>&nbsp;&nbsp;<?php echo $observaciones; ?></p><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td align="center" width="40%"><small style="text-align: center;font-weight: normal;">RECIBIO</small></td>
            <td align="center" width="20%"></td>
            <td align="center" width="40%"><small style="text-align: center;font-weight: normal;">ENTREGO</small></td>
        </tr>
    </table><br/>
    <table border="0" width="100%">
        <tr>
            <td align="center" width="40%" class="firma_up"><?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($dataDepto->col_nombre); ?></small></td>
            <td align="center" width="20%"></td>
            <td align="center" width="40%" class="firma_up"><?php echo fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos, true); ?><br/><small>Firma del Alumno</small></td>
        </tr>
    </table>

    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader();
    $mpdf->SetHTMLFooter(pdfFooter());

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS(), 1);
    $mpdf->WriteHTML($html, 2);

    $mpdf->Output('valeDocumentos.pdf', $output);

    // die();
}

function atencionAmonestacion($id, $db, $output = 'I') {
    global $nombreInstituto, $_indicacionInstituto;

        $atencionid = intval($id);
        $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
        $sth = $db->prepare($query);
        $sth->execute();
        $atencionData = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
        $sth = $db->prepare($query);
        $sth->execute();
        $userData = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
        $sth = $db->prepare($query);
        $sth->execute();
        $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
        $sth = $db->prepare($query);
        $sth->execute();
        $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
        $nombreDepto = $dataDepto->col_nombre;

        $carreraData = getCarrera($alumnodData->col_carrera, $db);
        $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

        $tipoCurso = 'semestre';
        if($carreraData['modalidad'] == 'Cuatrimestral') {
            $tipoCurso = 'cuatrimestre';
        }

        ob_start();
        echo pdfHeader();
        ?>
        <table border="0" width="100%">
            <tr>
                <td align="right">Tuxtla Gutiérrez, Chiapas., a <?php echo date('d', strtotime($atencionData->col_fecha)); ?> de <?php echo getMes(date('F', strtotime($atencionData->col_fecha))); ?> del <?php echo date('Y', strtotime($atencionData->col_fecha)); ?></td>
            </tr>
        </table>
        <br/>
        <table border="0" width="100%">
            <tr>
                <td align="right">ASUNTO: <b><?php echo fixEncode($atencionData->col_observaciones); ?></b></td>
            </tr>
        </table><br/><br/>
        <p>A través de este medio se le informa que su hijo (a) <u>&nbsp;&nbsp;<?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true); ?>&nbsp;&nbsp;</u> quien cursa el <u>&nbsp;&nbsp;<?php echo $periodoData->col_grado; ?>&nbsp;&nbsp;</u> <?php echo $tipoCurso; ?> del grupo de la <?php echo fixEncode($carreraData['nombre'], true); ?>, incurrió en las faltas contenidas en el reglamento de Licenciatura <?php echo $_indicacionInstituto; ?> <?php echo $nombreInstituto; ?>, en su Título Noveno, capítulo I “Faltas Disciplinares”, mismas que establecen que:</p><br/>
        <div style="text-align: justify;"><?php echo fixEncode(nl2br($atencionData->col_articulos)); ?></div>
        <br/>

        <p>Así mismo, se le condiciona al alumno (a), que de incurrir en faltas disciplinarias reincidentes traerán como consecuencia la suspensión o expulsión del alumno previo dictamen de la Coordinación Académica.</p>
        <br/><br/>
        <h3 style="text-align: center;font-weight: normal;">ATENTAMENTE</h3><br/><br/>
        <table border="0" width="100%">
            <tr>
                <td align="center" width="40%" class="firma_up"><?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($nombreDepto, true);?></small></td>
                <td align="center" width="20%"></td>
                <td align="center" width="40%" class="firma_up">Mtra. Susana Palacios Morales<br/><small>Dirección General</small></td>
            </tr>
        </table><br/>
        <h3 style="text-align: center;font-weight: normal;">ENTERADO</h3><br/><br/>
        <table border="0" width="100%">
            <tr>
                <td align="center" width="40%" class="firma_up"><?php echo fixEncode($alumnodData->col_rep_nombres.' '.$alumnodData->col_rep_apellidos, true); ?><br/><small>Firma del Representante</small></td>
                <td align="center" width="20%"></td>
                <td align="center" width="40%" class="firma_up"><?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true); ?><br/><small>Firma del Alumno</small></td>
            </tr>
        </table>

        <?php
        $html = ob_get_contents();
        ob_end_clean();



        include_once(__DIR__ . '/../src/mpdf/mpdf.php');

        $m = getMargins($db);
        $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

        $mpdf->SetHTMLHeader();
        $mpdf->SetHTMLFooter(pdfFooter());

        $mpdf->SetDisplayMode('fullpage');
        $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

        $mpdf->WriteHTML(pdfCSS(), 1);
        $mpdf->WriteHTML($html, 2);

        if($action == 'S') {
            return $mpdf->Output('Amonestacion.pdf', $output);
        }else{
            $mpdf->Output('Amonestacion.pdf', $output);
        }
        // die();
}

function atencionPsicopedagogica($id, $db, $output = 'I') {
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);


    $query = 'SELECT * FROM tbl_atencion WHERE col_tipo="'.$atencionData->col_tipo.'" AND col_created_at<"'.$atencionData->col_created_at.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $folio = ($sth->rowCount() + 1);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $tipoCurso = 'semestre';
    if($carreraData['modalidad'] == 'Cuatrimestral') {
        $tipoCurso = 'cuatrimestre';
    }

    ob_start();
    ?>
    <table border="0" width="100%">
        <tr>
            <td width="20%" valign="top"><img src="<?php echo getLogo(); ?>" style="max-width: 180px;height:auto;" alt="FLDCH" border="0"/></td>
            <td align="center" width="60%">ATENCIÓN PSICODEPAGÓGICO</td>
            <td align="right" width="20%"><span style="color: #CC0000;">FOLIO:</span> <?php echo str_pad($folio, 5, '0', STR_PAD_LEFT); ?></td>
        </tr>
    </table><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="100%" colspan="3" align="left">Nombre del alumno(a): <u>&nbsp;&nbsp;<?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true); ?>&nbsp;&nbsp;</u></td>
        </tr>
        <tr>
            <td width="34%">Fecha: <u>&nbsp;&nbsp;<?php echo fechaTexto($atencionData->col_fecha); ?>&nbsp;&nbsp;</u></td>
            <td width="33%">Hora de Entrada: <u>&nbsp;&nbsp;<?php echo $atencionData->col_hora_entrada; ?>&nbsp;&nbsp;</u></td>
            <td width="33%">Hora de Salida: <u>&nbsp;&nbsp;<?php echo $atencionData->col_hora_salida; ?>&nbsp;&nbsp;</u></td>
        </tr>
    </table>
    <br/>

    <table border="0" width="100%" class="basica">
        <thead>
            <tr>
                <th width="40%">Materias</th>
                <th width="15%">Faltas</th>
                <th width="15%">Calf.</th>
                <th width="30%">Observaciones</th>
            </tr>
        </thead>
        <tbody class="grid">
            <?php
            $asistencias = get_AsistenciasByAlumnoAndMateria($atencionData->col_alumnoid, $db);
            //print_r($asistencias);
            // $result['_asistencias'] = $asistencias;
            foreach($asistencias as $item){
            if(intval($item['materiaData']['examenFinalID']) > 0 && strtotime($atencionData->col_fecha) >= strtotime($item['materiaData']['examenFinal'])){
                $calificacion = $item['materiaData']['examenFinal_calificacion'];
            }else if(intval($item['materiaData']['parcial2ID']) > 0 && strtotime($atencionData->col_fecha) >= strtotime($item['materiaData']['parcial2'])){
                $calificacion = $item['materiaData']['parcial2_calificacion'];
            }else if(intval($item['materiaData']['parcial1ID']) > 0 && strtotime($atencionData->col_fecha) >= strtotime($item['materiaData']['parcial1'])){
                $calificacion = $item['materiaData']['parcial1_calificacion'];
            }else{
                $calificacion = 'SC';
            }
            ?>
            <tr>
                <td valign="top"><?php echo fixEncode($item['materia']); ?></td>
                <td valign="top" align="center"><?php echo intval($item['faltas']); ?></td>
                <td valign="top" align="center"><?php echo $calificacion; ?></td>
                <td valign="top"><?php echo fixEncode($item['materiaData']['calificacion_observaciones']); ?></td>
            </tr>
            <?php
            }
            ?>
        </tbody>
    </table><br/>

    <table border="0" width="100%" class="basica">
        <thead>
            <tr>
                <th align="left" width="70%">Observaciones:</th>
                <th align="left" width="30%">Nueva Cita</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td valign="top"><?php echo fixEncode(nl2br($atencionData->col_observaciones)); ?></td>
                <td valign="top">
                    <?php echo ($atencionData->col_fecha_cita == '0000-00-00'?'Fecha sin Definir':fechaTexto($atencionData->col_fecha_cita)); ?>
                    <?php echo ($atencionData->col_hora_cita == ''?'<br/>Hora sin Definir':'<br/>'.($atencionData->col_hora_cita)); ?>
                </td>
            </tr>
        </tbody>
    </table><br/><br/><br/><br/>


    <table border="0" width="100%">
        <tr>
            <td align="center" valign="top" width="40%" class="firma_up"><?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($nombreDepto, true);?></small></td>
            <td align="center" valign="top" width="20%"></td>
            <td align="center" valign="top" width="40%" class="firma_up"><?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true); ?><br/><small>Firma del alumno(a)</small></td>
        </tr>
    </table><br/>


    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader('');
    $mpdf->SetHTMLFooter(pdfFooter());

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS(), 1);
    $mpdf->WriteHTML($html, 2);

    if($output == 'S') {
        return $mpdf->Output('Psicopedagogico.pdf', $output);
    }else{
        $mpdf->Output('Psicopedagogico.pdf', $output);
    }

    // die();
}

function atencionCoordinacion($id, $db, $output = 'I') {
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);


    $query = 'SELECT * FROM tbl_atencion WHERE col_tipo="'.$atencionData->col_tipo.'" AND col_created_at<"'.$atencionData->col_created_at.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $folio = ($sth->rowCount() + 1);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $tipoCurso = 'Semestre';
    if($carreraData['modalidad'] == 'Cuatrimestral') {
        $tipoCurso = 'Cuatrimestre';
    }

    ob_start();
    ?>
    <table border="0" width="100%">
        <tr><td rowspan="3" valign="top"><img src="<?php echo getLogo(); ?>" style="max-width: 180px;height:auto;" alt="FLDCH" border="0"/></td><td align="right">COORDINACIÓN ACADEMICA</td></tr>
        <tr><td align="right"><span style="color: #CC0000;">FOLIO:</span> <?php echo str_pad($folio, 5, '0', STR_PAD_LEFT); ?></td></tr>
        <tr>
            <td align="right">Tuxtla Gutiérrez, Chiapas., a <?php echo date('d', strtotime($atencionData->col_fecha)); ?> de <?php echo getMes(date('F', strtotime($atencionData->col_fecha))); ?> del <?php echo date('Y', strtotime($atencionData->col_fecha)); ?></td>
        </tr>
    </table>
    <table border="0" width="100%">
        <tr>
            <td width="20%"></td>
            <td align="center" width="60%">Atención Alumno</td>
            <td width="20%"></td>
        </tr>
    </table><br/>
    <table border="0" width="100%">
        <tr>
            <td width="100%" colspan="4" align="left">Nombre del alumno(a): <u>&nbsp;&nbsp;<?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true); ?>&nbsp;&nbsp;</u></td>
        </tr>
        <tr>
            <td width="25%"><?php echo $tipoCurso; ?>: <u>&nbsp;&nbsp;<?php echo ($periodoData->col_grado); ?>&nbsp;&nbsp;</u></td>
            <td width="25%">Grupo: <u>&nbsp;&nbsp;<?php echo ($periodoData->col_grupo); ?>&nbsp;&nbsp;</u></td>
            <td width="25%">Hora de Entrada: <u>&nbsp;&nbsp;<?php echo $atencionData->col_hora_entrada; ?>&nbsp;&nbsp;</u></td>
            <td width="25%">Hora de Salida: <u>&nbsp;&nbsp;<?php echo $atencionData->col_hora_salida; ?>&nbsp;&nbsp;</u></td>
        </tr>
    </table>
    <br/>

    <table border="0" width="100%" class="basica">
        <thead>
            <tr>
                <th width="40%">Materias</th>
                <th width="15%">Faltas</th>
                <th width="15%">Calf.</th>
                <th width="30%">Observaciones</th>
            </tr>
        </thead>
        <tbody class="grid">
            <?php
            $asistencias = get_AsistenciasByAlumnoAndMateria($atencionData->col_alumnoid, $db);
            foreach($asistencias as $item){
            if(intval($item['materiaData']['examenFinalID']) > 0 && strtotime($atencionData->col_fecha) >= strtotime($item['materiaData']['examenFinal'])){
                $calificacion = $item['materiaData']['examenFinal_calificacion'];
            }else if(intval($item['materiaData']['parcial2ID']) > 0 && strtotime($atencionData->col_fecha) >= strtotime($item['materiaData']['parcial2'])){
                $calificacion = $item['materiaData']['parcial2_calificacion'];
            }else if(intval($item['materiaData']['parcial1ID']) > 0 && strtotime($atencionData->col_fecha) >= strtotime($item['materiaData']['parcial1'])){
                $calificacion = $item['materiaData']['parcial1_calificacion'];
            }else{
                $calificacion = 'SC';
            }
            ?>
            <tr>
                <td valign="top"><?php echo fixEncode($item['materia']); ?></td>
                <td valign="top" align="center"><?php echo intval($item['faltas']); ?></td>
                <td valign="top" align="center"><?php echo $calificacion; ?></td>
                <td valign="top"><?php echo fixEncode($item['materiaData']['calificacion_observaciones']); ?></td>
            </tr>
            <?php
            }
            ?>
        </tbody>
    </table><br/>

    <table border="0" width="100%" class="basica">
        <tbody>
            <tr>
                <td width="100%" valign="top">Observaciones Generales: <?php echo fixEncode(nl2br($atencionData->col_observaciones)); ?></td>
            </tr>
        </tbody>
    </table><br/><br/><br/><br/>


    <table border="0" width="100%">
        <tr>
            <td align="center" valign="top" width="30%" class="firma_up"><?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($nombreDepto, true);?></small></td>
            <td width="5%"></td>
            <td align="center" valign="top" width="30%" class="firma_up"><?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true); ?><br/><small>Firma del alumno(a)</small></td>
            <td width="5%"></td>
            <td align="center" valign="top" width="30%" class="firma_up"><?php echo fixEncode($alumnodData->col_rep_nombres.' '.$alumnodData->col_rep_apellidos, true); ?><br/><small>Firma del padre o tutor</small></td>
        </tr>
    </table><br/>


    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader('');
    $mpdf->SetHTMLFooter(pdfFooter());

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS(), 1);
    $mpdf->WriteHTML($html, 2);

    if($output == 'S') {
        return $mpdf->Output('Coordinacion.pdf', $output);
    }else{
        $mpdf->Output('Coordinacion.pdf', $output);
    }

    // die();
}

function atencionInasistencias($id, $db, $output = 'I') {
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);


    $query = 'SELECT * FROM tbl_atencion WHERE col_tipo="'.$atencionData->col_tipo.'" AND col_created_at<"'.$atencionData->col_created_at.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $folio = ($sth->rowCount() + 1);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $tipoCurso = 'Semestre';
    if($carreraData['modalidad'] == 'Cuatrimestral') {
        $tipoCurso = 'Cuatrimestre';
    }

    ob_start();
    ?>
    <table border="0" width="100%">
        <tr>
            <td valign="top">
                <img src="<?php echo getLogo(); ?>" style="max-width: 180px;height:auto;" alt="FLDCH" border="0"/>
            </td>
            <td valign="top" align="right">Tuxtla Gutiérrez, Chiapas., a <?php echo date('d', strtotime($atencionData->col_fecha)); ?> de <?php echo getMes(date('F', strtotime($atencionData->col_fecha))); ?> del <?php echo date('Y', strtotime($atencionData->col_fecha)); ?></td>
        </tr>
    </table>

    <p align="center"><b>Atención Alumno</b></p>

    <p>Estimado alumno (a): <u>&nbsp;&nbsp;<?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true); ?>&nbsp;&nbsp;</u></p>
    <p>Hemos detectado inasistencias en algunas materias, mismas que se describen abajo. Es importante recordar que para tener derecho a examen nuestros alumnos deben contar como mínimo con el 80% de asistencias.</p><br/>

    <table border="0" width="100%" class="basica">
        <thead>
            <tr>
                <th width="70%">Materia</th>
                <th width="15%">Inasistencias acumuladas</th>
                <th width="15%">%</th>
            </tr>
        </thead>
        <tbody class="grid">
            <?php
            $asistencias = get_AsistenciasByAlumnoAndMateria($atencionData->col_alumnoid, $db);
            foreach($asistencias as $item){
                if((!in_array(strtoupper(substr($item['clave'], 0, 2)), array('AC', 'TL', 'TR', 'CL')) && $item['materiaGrado'] == $periodoData->col_grado) || in_array(strtoupper(substr($item['clave'], 0, 2)), array('AC', 'TL', 'TR', 'CL'))) {
                    if(intval($item['materiaData']['examenFinalID']) > 0 && strtotime($atencionData->col_fecha) >= strtotime($item['materiaData']['examenFinal'])){
                        $calificacion = $item['materiaData']['examenFinal_calificacion'];
                    }else if(intval($item['materiaData']['parcial2ID']) > 0 && strtotime($atencionData->col_fecha) >= strtotime($item['materiaData']['parcial2'])){
                        $calificacion = $item['materiaData']['parcial2_calificacion'];
                    }else if(intval($item['materiaData']['parcial1ID']) > 0 && strtotime($atencionData->col_fecha) >= strtotime($item['materiaData']['parcial1'])){
                        $calificacion = $item['materiaData']['parcial1_calificacion'];
                    }else{
                        $calificacion = 'SC';
                    }
                    ?>
                    <tr>
                        <td valign="top"><?php echo fixEncode($item['materia']); ?></td>
                        <td valign="top" align="center"><?php echo intval($item['faltas']); ?></td>
                        <td valign="top" align="center"><?php echo intval($item['porcentaje_asistencias']); ?>%</td>
                    </tr>
                    <?php
                }

            }
            ?>
        </tbody>
    </table><br/>

    <p>
    Observaciones: <?php echo fixEncode(nl2br($atencionData->col_observaciones)); ?>
    </p><br/><br/><br/><br/>

    <table border="0" width="100%">
        <tr>
            <td align="center" valign="top" width="30%"></td>
            <td width="5%"></td>
            <td align="center" valign="top" width="30%" class="firma_up"><?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($nombreDepto, true);?></small></td>
            <td width="5%"></td>
            <td align="center" valign="top" width="30%"></td>
        </tr>
    </table><br/>

    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader('');
    $mpdf->SetHTMLFooter(pdfFooter());

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS(), 1);
    $mpdf->WriteHTML($html, 2);

    if($output == 'S') {
        return $mpdf->Output('Inasistencias.pdf', $output);
    }else{
        $mpdf->Output('Inasistencias.pdf', $output);
    }


    // die();
}

function atencionModeloEducativo($id, $db, $output = 'I') {
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_atencion WHERE col_tipo="'.$atencionData->col_tipo.'" AND col_created_at<"'.$atencionData->col_created_at.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $folio = ($sth->rowCount() + 1);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $tipoCurso = 'Semestre';
    if($carreraData['modalidad'] == 'Cuatrimestral') {
        $tipoCurso = 'Cuatrimestre';
    }

    ob_start();
    ?>
    <table border="0" width="100%">
        <tr>
            <td valign="top"><img src="<?php echo getLogo(); ?>" style="max-width: 180px;height:auto;" alt="FLDCH" border="0"/></td>
            <td valign="top" align="right">Tuxtla Gutiérrez, Chiapas., a <?php echo date('d', strtotime($atencionData->col_fecha)); ?> de <?php echo getMes(date('F', strtotime($atencionData->col_fecha))); ?> del <?php echo date('Y', strtotime($atencionData->col_fecha)); ?></td>
        </tr>
    </table>
    <p align="center"><b>Atención Alumno</b></p>

    <p>Estimado alumno (a): <u>&nbsp;&nbsp;<?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true); ?>&nbsp;&nbsp;</u></p>
    <p>Por este medio te informo que no cumpliste con el Modelo educativo que es parte importante para tener  derecho a exámenes Parciales o Finales.</p><br/>

    <table border="0" width="100%" class="basica">
        <thead>
            <tr>
                <th width="55%">Materia</th>
                <th width="15%">Calificación</th>
                <th width="30%">Afecta a la Materia</th>
            </tr>
        </thead>
        <tbody class="grid">
            <?php
            $asistencias = get_AsistenciasByAlumnoAndMateria($atencionData->col_alumnoid, $db);

            // echo '<pre>';
            // print_r($asistencias);
            // echo '</pre>';
            // exit;

            // $result['_asistencias'] = $asistencias;
            foreach($asistencias as $item){
                $_clave = strtoupper(substr($item['materiaData']['clave'], 0, 2));
                if(!in_array($_clave, array('AC', 'TL', 'CL', 'TR')) && intval($item['materiaid']) > 0) {

                    //if(intval($item['materiaData']['examenFinalID']) > 0 && strtotime($atencionData->col_fecha) >= strtotime($item['materiaData']['examenFinal'])){
                    if($extraData['tipoExamen'] == 3){
                        $calificacion = $item['materiaData']['examenFinal_calificacion'];
                        $ordenFinal = $orden = $item['materiaData']['examenFinal_orden'];
                    //}else if(intval($item['materiaData']['parcial2ID']) > 0 && strtotime($atencionData->col_fecha) >= strtotime($item['materiaData']['parcial2'])){
                    }else if($extraData['tipoExamen'] == 2){
                        $calificacion = $item['materiaData']['parcial2_calificacion'];
                        $ordenP2 = $orden = $item['materiaData']['parcial2_orden'];
                    //}else if(intval($item['materiaData']['parcial1ID']) > 0 && strtotime($atencionData->col_fecha) >= strtotime($item['materiaData']['parcial1'])){
                    }else if($extraData['tipoExamen'] == 1){
                        $calificacion = $item['materiaData']['parcial1_calificacion'];
                        $ordenP1 = $orden = $item['materiaData']['parcial1_orden'];
                    }else{
                        $calificacion = 'SC';
                    }


                    $ordenMaterias[$orden] = fixEncode($item['materia']);
                }
            }

            // echo '<pre>';
            // print_r($ordenMaterias);
            // echo '</pre>';
            // exit;

            foreach($asistencias as $item){
                // if(intval($item['materiaData']['parcial2ID']) > 0 && strtotime($atencionData->col_fecha) >= strtotime($item['materiaData']['parcial2'])){
                if($extraData['tipoExamen'] == 2){
                    $calificacion = $item['materiaData']['parcial2_calificacion'];
                    $orden = $item['materiaData']['parcial2_orden'];
                    $actividadID = $item['materiaData']['parcial2ID'];
                    $examenConsidera = 'Parcial 2';
                    $meArray = array('AC', 'TL', 'CL', 'TR');
                //}else if(intval($item['materiaData']['parcial1ID']) > 0 && strtotime($atencionData->col_fecha) >= strtotime($item['materiaData']['parcial1'])){
                }else if($extraData['tipoExamen'] == 1){
                    $calificacion = $item['materiaData']['parcial1_calificacion'];
                    $orden = $item['materiaData']['parcial1_orden'];
                    $actividadID = $item['materiaData']['parcial1ID'];
                    $examenConsidera = 'Parcial 1';
                    $meArray = array('AC', 'TL', 'CL', 'TR');
                // }else{
                }else if($extraData['tipoExamen'] == 3){
                    $calificacion = $item['materiaData']['examenFinal_calificacion'];
                    $orden = $item['materiaData']['examenFinal_orden'];
                    $actividadID = $item['materiaData']['examenFinalID'];
                    $examenConsidera = 'Examen Final';
                    $meArray = array('AC');
                }
                $_clave = strtoupper(substr($item['materiaData']['clave'], 0, 2));

                if(in_array($_clave, $meArray)) {

                    if($_clave == 'AC' && intval($actividadID) > 0) {
                        $me = acreditaMEAcademias($atencionData->col_alumnoid, $actividadID, $db);
                        $afectaOrden = 1;
                    }
                    if($_clave == 'TL' && intval($actividadID) > 0) {
                        $me = acreditaMETalleres($atencionData->col_alumnoid, $actividadID, $db);
                        $afectaOrden = 2;
                    }

                    if($_clave == 'CL' && intval($actividadID) > 0) {
                        $me = acreditaMEClubLectura($atencionData->col_alumnoid, $actividadID, $db);
                        if($me['hasCalificacion'] == 0) $calificacion = '-';
                        $afectaOrden = 3;
                    }

                    if($_clave == 'TR' && intval($actividadID) > 0) {
                         $me = acreditaMETransversales($atencionData->col_alumnoid, $actividadID, $db);
                         if($me['hasCalificacion'] == 0) {
                            $calificacion = '-';
                         }
                         $afectaOrden = 4;
                    }
                    /*
                    ===========================
                    if($_reponse['tipoActiviad'] == 5 OR $_reponse['tipoActiviad'] == 6){
                        if($ordenExamen == 1) $me = acreditaMEAcademias($item['col_alumnoid'], intval($_REQUEST['id']), $this->db);
                        if($ordenExamen == 2) $me = acreditaMETalleres($item['col_alumnoid'], intval($_REQUEST['id']), $this->db);
                        if($ordenExamen == 3) $me = acreditaMEClubLectura($item['col_alumnoid'], intval($_REQUEST['id']), $this->db);
                        if($ordenExamen == 4) $me = acreditaMETransversales($item['col_alumnoid'], intval($_REQUEST['id']), $this->db);
                        if($ordenExamen == 5) $me = acreditaMEPracticas($item['col_alumnoid'], intval($_REQUEST['id']), $_reponse['tipoActiviad'], $this->db);
                        if($ordenExamen == 7) $me = acreditaMEServicio($item['col_alumnoid'], intval($_REQUEST['id']), $_reponse['tipoActiviad'], $this->db);
                    }else if($_reponse['tipoActiviad'] == 7){

                        if($periodoData->col_grado < 7) {
                            if($ordenExamen == 1) $me = acreditaMEAcademias($item['col_alumnoid'], intval($_REQUEST['id']), $this->db);
                            if($ordenExamen == 2) $me = acreditaMEAltruista($item['col_alumnoid'], intval($_REQUEST['id']), $_reponse['tipoActiviad'], $this->db);
                            if($ordenExamen == 7) $me = acreditaMEPracticas($item['col_alumnoid'], intval($_REQUEST['id']), $_reponse['tipoActiviad'], $this->db);
                        }
                        if($periodoData->col_grado > 6) {
                            if($ordenExamen == 1) $me = acreditaMEAcademias($item['col_alumnoid'], intval($_REQUEST['id']), $this->db);
                            if($ordenExamen == 2) $me = acreditaMEAltruista($item['col_alumnoid'], intval($_REQUEST['id']), $_reponse['tipoActiviad'], $this->db);
                        }
                    }
                    ===========================
                    */


                    if(intval($calificacion) > 0) {
                        if(intval($calificacion) >= 7) {
                           $calificacion = 'A';
                        }else{
                           $calificacion = 'NA';
                        }
                     }

                ?>
                <tr>
                    <td valign="top"><?php echo fixEncode($item['materia']); ?></td>
                    <td valign="top" align="center"><?php echo $calificacion; ?></td>
                    <td valign="top" align="center">
                        <?php if($me['reduccion'] > 0) { ?>
                        SD al <?php echo $me['reduccion']; ?>% del valor Examen de <?php echo $ordenMaterias[$afectaOrden]; ?>
                        <?php }else{
                            if($calificacion == 'NA'){ ?>
                            Examen de <?php echo $ordenMaterias[$afectaOrden]; ?>
                        <?php }} ?>
                    </td>
                </tr>
                <?php
                //}
                }
            }

            if($extraData['tipoExamen'] < 3){
                $tipoActividad = ($extraData['tipoExamen'] == 1?5:6);
                // $me = acreditaMETransversales($atencionData->col_alumnoid, $actividadID, $db);
                $mePractica = acreditaMEPracticas($atencionData->col_alumnoid, $actividadID, $tipoActividad, $db);
                if(is_array($mePractica) && $mePractica['acredita'] != ''){
                ?>
                <tr>
                    <td valign="top">Practicas Profesionales</td>
                    <td valign="top" align="center"><?php echo $mePractica['acredita']; ?></td>
                    <td valign="top" align="center">
                        <?php if($mePractica['reduccion'] > 0) { ?>
                        SD al <?php echo $mePractica['reduccion']; ?>% del valor Examen de <?php echo $ordenMaterias[5]; ?>
                        <?php }else{
                            if($mePractica['acredita'] == 'NA'){ ?>
                            Examen de <?php echo $ordenMaterias[5]; ?>
                        <?php }} ?>
                    </td>
                </tr>
                <?php
                }
                $meServicio = acreditaMEServicio($atencionData->col_alumnoid, $actividadID, $tipoActividad, $db);
                if(is_array($meServicio) && $meServicio['acredita'] != ''){
                ?>
                <tr>
                    <td valign="top">Servicio Social</td>
                    <td valign="top" align="center"><?php echo $meServicio['acredita']; ?></td>
                    <td valign="top" align="center">
                        <?php if($meServicio['reduccion'] > 0) { ?>
                        SD al <?php echo $meServicio['reduccion']; ?>% del valor Examen de <?php echo $ordenMaterias[7]; ?>
                        <?php }else{
                            if($meServicio['acredita'] == 'NA'){ ?>
                            Examen de <?php echo $ordenMaterias[7]; ?>
                        <?php }} ?>
                    </td>
                </tr>
                <?php
                }
            }

            if($extraData['tipoExamen'] == 3){

                $me = acreditaMEAltruista($atencionData->col_alumnoid, $db, $periodoData->col_id);
                ?>
                <tr>
                    <td valign="top">Actividad Altruista</td>
                    <td valign="top" align="center"><?php echo $me['acredita']; ?></td>
                    <td valign="top" align="center">
                        <?php if($me['reduccion'] > 0) { ?>
                        SD al <?php echo $me['reduccion']; ?>% del valor Examen de <?php echo $ordenMaterias[2]; ?>
                        <?php }else{
                            if($me['acredita'] == 'NA'){ ?>
                            Examen de <?php echo $ordenMaterias[2]; ?>
                        <?php }} ?>
                    </td>
                </tr>
                <?php

                if($periodoData->col_grado < 7) {
                    $me = acreditaMEPracticas($atencionData->col_alumnoid, $actividadID, 7, $db);
                    ?>
                    <tr>
                        <td valign="top">Practicas Profesionales</td>
                        <td valign="top" align="center"><?php echo $me['acredita']; ?></td>
                        <td valign="top" align="center">
                            <?php if($me['reduccion'] > 0) { ?>
                            SD al <?php echo $me['reduccion']; ?>% del valor Examen de <?php echo $ordenMaterias[5]; ?>
                            <?php }else{
                                if($me['acredita'] == 'NA'){ ?>
                                Examen de <?php echo $ordenMaterias[5]; ?>
                            <?php }} ?>
                        </td>
                    </tr>
                    <?php
                }

            }
            ?>
        </tbody>
    </table><br/>
    <?php if($atencionData->col_observaciones != ''){ ?>
    <p><b>Observaciones:</b> <u><?php echo fixEncode($atencionData->col_observaciones); ?></u></p>
    <?php } ?>
    <p>Te sugerimos mejorar esos malos hábitos que conllevan a un resultado negativo, te recomendamos tengas mayor compromiso con tus estudios, recuerda que un profesionista debe ser comprometido, responsable, tener pasión por lo que hace y debe desarrollar sus habilidades; es por ello la importancia de acreditar  tus materias del Modelo Educativo.</p>
    <p>Si tiene alguna duda favor de contactarse o acudir al área de coordinación Academica, con gusto le atenderé.</p>

    <br/><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td align="center" valign="top" width="30%"></td>
            <td width="5%"></td>
            <td align="center" valign="top" width="30%" class="firma_up"><?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($nombreDepto, true);?></small></td>
            <td width="5%"></td>
            <td align="center" valign="top" width="30%"></td>
        </tr>
    </table><br/>

    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');


    $m = getMargins($db);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);


    $mpdf->SetHTMLHeader('');
    $mpdf->SetHTMLFooter(pdfFooter());

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS(), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('modelo-educativo.pdf', $output);
    }else{
        $mpdf->Output('modelo-educativo.pdf', $output);
    }
    // die();
}

function generarKardex($id, $db, $output = 'I') {
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);


    $query = 'SELECT * FROM tbl_atencion WHERE col_tipo="'.$atencionData->col_tipo.'" AND col_created_at<"'.$atencionData->col_created_at.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $folio = ($sth->rowCount() + 1);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $tipoCurso = 'SÉM';
    if($carreraData['modalidad'] == 'Cuatrimestral') {
        $tipoCurso = 'CUA';
    }
    $laModalidad = $carreraData['modalidad'];
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'LICENCIATURA';
    }

    $isPosgrado = $carreraData['posgrado'];

    ob_start();
    ?>
    <table border="0" width="100%" style="font-size: 11px;">
        <tr>
            <td width="25%">
                <img src="<?php echo getLogo(); ?>" style="max-width: 180px;height:auto;" alt="FLDCH" border="0"/>
            </td>
            <td width="75%" align="center"><span class="titulo"><b>KARDEX DEL ALUMNO</b></span></td>
        </tr>
    </table><br/>
    <table border="0" width="100%" style="font-size: 11px;">
        <tr>
            <td width="30%" align="left" valign="top">NOMBRE:</td>
            <td width="70%" class="fill_line" align="left" valign="top"><?php echo strtoupper(fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true, true)); ?></td>
        </tr>
    </table>
    <table border="0" width="100%" style="font-size: 11px;">
        <tr>
            <td width="30%">FECHA DE NACIMIENTO:</td>
            <td width="40%" class="fill_line" align="left" style="text-align:left;"><?php echo strtoupper(fechaTexto($alumnodData->col_fecha_nacimiento)); ?></td>
            <td width="10%">SEXO</td>
            <td width="20%"><span class="box_genre<?php echo (strtolower($alumnodData->col_genero) == 'h'?'_checked':''); ?>">&nbsp;M&nbsp;</span>&nbsp;&nbsp;&nbsp;&nbsp;<span class="box_genre<?php echo (strtolower($alumnodData->col_genero) == 'm'?'_checked':''); ?>">&nbsp;F&nbsp;</span></td>
        </tr>
    </table>
    <table border="0" width="100%" style="font-size: 11px;">
        <tr>
            <td width="30%" align="left" valign="top">DOMICILIO:</td>
            <td width="70%" class="fill_line" valign="top" align="left"><?php echo strtoupper(fixEncode($alumnodData->col_direccion, true, true)); ?>, <?php echo strtoupper(fixEncode($alumnodData->col_ciudad, true, true)); ?>,<?php echo strtoupper(fixEncode($alumnodData->col_estado, true, true)); ?></td>
        </tr>
        <tr>
            <td width="30%" align="left" valign="top"><?php echo strtoupper($laModalidad); ?>:</td>
            <td width="70%" class="fill_line" valign="top" align="left"><?php echo strtoupper(fixEncode($carreraData['nombre'], true, true)); ?></td>
        </tr>
        <tr>
            <td width="30%" align="left" valign="top">No. DE CONTROL:</td>
            <td width="70%" class="fill_line" align="left" valign="top"><?php echo $alumnodData->col_control; ?></td>
        </tr>
    </table>
    <?php
    $header = ob_get_contents();
    ob_end_clean();

    ob_start();
            $promGeneral = 0;
            $quaPeriodos = 0;
            $subQuery = "SELECT col_periodoid FROM tbl_alumnos_taxonomia WHERE col_alumnoid='".$atencionData->col_alumnoid."'";
            $queryPeriodos = "SELECT * FROM tbl_periodos WHERE col_id IN (".$subQuery.") ORDER BY col_grado ASC";
            $sth = $db->prepare($queryPeriodos);
            $sth->execute();
            $periodos = $sth->fetchAll();

            $periodoCount = 0;
            $promGeneralTotal = 0;
            $reprobadas = 0;
            foreach($periodos AS $periodo) {
                $query = "SELECT * FROM tbl_calificaciones WHERE col_periodoid='".$periodo['col_id']."' AND col_alumnoid='".$atencionData->col_alumnoid."'";
                //$query = "SELECT * FROM tbl_calificaciones WHERE col_cf!='' AND col_periodoid='".$periodo['col_id']."' AND col_alumnoid='".$atencionData->col_alumnoid."'";
                $sth = $db->prepare($query);
                $sth->execute();
                $calis = $sth->fetchAll();
                if($sth->rowCount()) {
                    if($periodoCount > 0){
                        ?>
                        <pagebreak>
                        <?php
                    }
                ?>
                <table border="0" width="100%" class="bordered">
                    <?php if($carreraData['modalidad_periodo'] == 'ldsem' || $carreraData['modalidad_periodo'] == 'ldcua') { ?>
                    <thead>
                        <tr>
                            <th rowspan="3" width="6%"><?php echo $tipoCurso; ?></th>
                            <th rowspan="3" width="20%">MATERIAS</th>
                            <th colspan="2" rowspan="2">CALIFICACIÓN</th>
                            <th colspan="5">REGULARIZACIÓN</th>
                        </tr>
                        <tr>
                            <th colspan="2">EXTRAORDINARIO</th>
                            <th colspan="2">TITULO DE SUFICIENCIA</th>
                            <th rowspan="2" width="20%">OBSERVACIONES</th>
                        </tr>
                        <tr>
                            <th>NÚMERO</th>
                            <th>LETRA</th>
                            <th>CAL</th>
                            <th>FECHA</th>
                            <th>CAL</th>
                            <th>FECHA</th>
                        </tr>
                    </thead>
                    <?php } ?>
                    <?php if($carreraData['modalidad_periodo'] == 'master' || $carreraData['modalidad_periodo'] == 'docto') { ?>
                    <thead>
                        <tr>
                            <th rowspan="2"><?php echo $tipoCurso; ?></th>
                            <th rowspan="2">MATERIAS</th>
                            <th colspan="2">CALIFICACIÓN</th>
                            <th width="30" rowspan="2">OBSERVACIONES</th>
                        </tr>
                        <tr>
                            <th>NÚMERO</th>
                            <th>LETRA</th>
                        </tr>
                    </thead>
                    <?php } ?>
                    <tbody class="grid">
                        <?php
                        $sumCF = 0;
                        $qua = 0;
                        $promGeneral = 0;
                        unset($dataMaterias);

                        foreach($calis as $row) {
                            if(substr($row['col_materia_clave'], 0, 3) == 'LDO') $row['col_materia_clave'] = str_replace('O', '0', $row['col_materia_clave']);

                            if(in_array(substr($row['col_materia_clave'], 0, 2), array('AC', 'TL', 'CL', 'TR'))) continue;
                            $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.$row['col_materia_clave'].'" ';
                            $sth = $db->prepare($query);
                            $sth->execute();
                            $materia = $sth->fetch(PDO::FETCH_OBJ);
                            if($sth->rowCount() == 0) $materia->col_nombre = $row['col_materia_clave'];
                            ob_start();
                            ?>
                            <tr>
                                <td valign="top" align="center"><?php echo $periodo['col_grado'];?></td>
                                <td valign="top"><?php echo mb_strtoupper(fixEncode($materia->col_nombre, true)); ?></td>
                                <td valign="top" align="center"><?php
                                    echo $row['col_cf'];
                                ?></td>
                                <td valign="top" align="center">
                                    <?php
                                        if($row['col_cf'] == 'NP') {
                                            echo 'NO PRESENTO';
                                        }else{
                                            echo numerosaletras(($row['col_cf']==''?0:$row['col_cf']));
                                        }
                                    ?></td>

                                <?php if($isPosgrado == false){ ?> <td width="30" valign="top" align="center"><?php echo $row['col_ext']; ?></td> <?php } ?>
                                <?php if($isPosgrado == false){ ?> <td valign="top" align="center"><?php echo getFechaExt($db, $row['col_created_at'], $row['col_created_by'], $row['col_ext'], $atencionData->col_alumnoid, $row['col_materia_clave'], $periodo['col_id']); ?></td> <?php } ?>
                                <?php if($isPosgrado == false){ ?> <td width="30" valign="top" align="center"><?php echo $row['col_ts']; ?></td> <?php } ?>
                                <?php if($isPosgrado == false){ ?> <td valign="top" align="center"><?php echo getFechaTS($db, $row['col_created_at'], $row['col_created_by'], $row['col_ts'], $atencionData->col_alumnoid, $row['col_materia_clave'], $periodo['col_id']); ?></td> <?php } ?>
                                <td valign="top" align="left"><?php echo fixEncode($row['col_observaciones']); ?></td>
                            </tr>
                            <?php
                            $dataHTML = ob_get_contents();
                            ob_end_clean();
                            //$dataMaterias[strtoupper(fixEncode($materia->col_nombre, true))] = $dataHTML;
                            $dataMaterias[$row['col_materia_clave']] = $dataHTML;
                            if($row['col_cf'] < 7 && $row['col_cf'] != '') {
                                // if($row['col_ext'] == '' || ($row['col_ext'] != '' && $row['col_ext'] < 7))
                                $reprobadas++;
                            }
                            $caliParaPromedio = $row['col_cf'];
                            if(intval($row['col_ext']) > 0) $caliParaPromedio = $row['col_ext'];
                            if(intval($row['col_ts']) > 0) $caliParaPromedio = $row['col_ts'];
                            $sumCF = $sumCF + $caliParaPromedio;
                            $qua++;
                        }
                        //$dataMaterias =
                        ksort($dataMaterias);
                        foreach($dataMaterias as $item_dataMaterias){
                            echo $item_dataMaterias;
                        }


                        $promPeriodo = $sumCF / $qua;

                        $promGeneral = $promGeneral + $promPeriodo;
                        $promGeneralTotal = $promGeneralTotal + $promGeneral;
                        $quaPeriodos++;
                        $ex_promGeneral = explode('.', $promGeneral);
                        $ex_promGeneral[1] = intval(substr($ex_promGeneral[1], 0, 1));
                        $promGeneral = $ex_promGeneral[0].'.'.$ex_promGeneral[1];
                        ?>
                            <tr>
                                <td valign="top" align="center"></td>
                                <td valign="top"><b>PROMEDIO</b></td>
                                <td valign="top" align="center"><?php echo $promGeneral; ?></td>
                                <td valign="top" align="center">
                                <?php
                                    if($ex_promGeneral[1] > 0){
                                        echo numerosaletras($ex_promGeneral[0]).' punto '.numerosaletras($ex_promGeneral[1]);
                                    }else{
                                        echo numerosaletras($ex_promGeneral[0]);
                                        // echo $ex_promGeneral[1];
                                    }
                                    ?>
                                </td>
                                <?php if($isPosgrado == false){ ?> <td valign="top" align="center"></td> <?php } ?>
                                <?php if($isPosgrado == false){ ?> <td valign="top" align="center"></td> <?php } ?>
                                <?php if($isPosgrado == false){ ?> <td valign="top" align="center"></td> <?php } ?>
                                <?php if($isPosgrado == false){ ?> <td valign="top" align="center"></td> <?php } ?>
                                <td valign="top" align="left"></td>
                            </tr>
                    </tbody>
                </table><br/><br/>


                 <br/><br/>

                 <table border="0" width="100%">
                     <tr>
                         <td align="center" valign="top" width="30%"></td>
                         <td align="center" valign="top" width="40%" class="firma_up"><?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><?php echo fixEncode($nombreDepto, true);?></td>
                         <td align="center" valign="top" width="30%"></td>
                     </tr>
                     <tr>
                         <td align="center" valign="top" width="10%"></td>
                         <td align="center" valign="top" width="80%" style="color: #cc0000;">NOTA: La presente será válida solo si cuenta con la firma y sello de la institución.</td>
                         <td align="center" valign="top" width="10%"></td>
                     </tr>
                 </table>

                 <?php
                 $periodoCount++;
                }

            }
            $promGeneralTotal = formatoPromedio($promGeneralTotal / $periodoCount);

            ?>

                <br/><br/>
                <table border="0" width="100%">
                     <tr>
                         <td align="center" valign="top" width="10%"></td>
                         <td align="right" valign="top" width="80%"><b>TOTAL DE MATERIAS REPROBADAS:</b></td>
                         <td align="center" style="border-bottom: 1px solid #222;" valign="top" width="10%"><?php echo $reprobadas; ?></td>
                     </tr>
                     <tr>
                         <td align="center" valign="top" width="10%"></td>
                         <td align="right" valign="top" width="80%"><b>PROMEDIO GENERAL <?php echo strtoupper($laModalidad); ?>:</b></td>
                         <td align="center" style="border-bottom: 1px solid #222;" valign="top" width="10%"><?php echo $promGeneralTotal; ?></td>
                     </tr>
                 </table>



    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    $mpdf=new mPDF('c','A4-L', '','', $m['left'], $m['right'], 63, 25, $m['top'], $m['bottom']);

    $mpdf->SetHTMLHeader($header);
    $mpdf->SetHTMLFooter(pdfFooter());

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS(), 1);
    $mpdf->WriteHTML($html, 2);

    if($output == 'S') {
        return $mpdf->Output('Kardex.pdf', $output);
    }else{
        $mpdf->Output('Kardex.pdf', $output);
    }

    // die();
}

function generarHistorialAcademico($id, $db, $output = 'I') {
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);


    $query = 'SELECT * FROM tbl_atencion WHERE col_tipo="'.$atencionData->col_tipo.'" AND col_created_at<"'.$atencionData->col_created_at.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $folio = ($sth->rowCount() + 1);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $tipoCurso = 'Semestre';
    if($carreraData['modalidad'] == 'Cuatrimestral') {
        $tipoCurso = 'Cuatrimestre';
    }

    ob_start();
    ?>
    <table border="0" width="100%">
        <tr>
            <td width="25%">
                <img src="<?php echo getLogo(); ?>" style="max-width: 180px;height:auto;" alt="FLDCH" border="0"/>
            </td>
            <td width="75%" align="center"><span class="titulo"><b>HISTORIAL DEL ALUMNO</b></span><br/><b><small><?php echo strtoupper(fixEncode($carreraData['nombre'], true)); ?></small></b></td>
        </tr>
    </table><br/>
    <table border="0" width="100%" class="bordered">
        <tr>
            <td width="10%" align="left" valign="top">ALUMNO:</td>
            <td width="50%" align="left" valign="top"><?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true); ?></td>
            <td width="10%" align="left" valign="top">MARÍCULA:</td>
            <td width="30%" align="left" valign="top"><?php echo $alumnodData->col_control; ?></td>
        </tr>
        <tr>
            <td width="10%" align="left" valign="top">CURP:</td>
            <td width="50%" align="left" valign="top"></td>
            <td width="10%" align="left" valign="top">FEC. NAC.:</td>
            <td width="30%" align="left" valign="top"><?php echo fechaTexto($alumnodData->col_fecha_nacimiento); ?></td>
        </tr>
        <tr>
            <td width="10%" align="left" valign="top">DOMICILIO:</td>
            <td width="30%" colspan="3" valign="top" align="left"><?php echo strtoupper(fixEncode($alumnodData->col_direccion, true)); ?>, <?php echo strtoupper(fixEncode($alumnodData->col_ciudad, true)); ?>,<?php echo strtoupper(fixEncode($alumnodData->col_estado, true)); ?></td>
        </tr>
        <tr>
            <td width="10%" align="left" valign="top">TEL. CASA:</td>
            <td width="30%" align="left" valign="top"><?php echo $alumnodData->col_telefono; ?></td>
            <td width="10%" align="left" valign="top">CELULAR:</td>
            <td width="30%" align="left" valign="top"><?php echo $alumnodData->col_celular; ?></td>
        </tr>
    </table>
    <?php
    $header = ob_get_contents();
    ob_end_clean();

    ob_start();

            $promGeneral = 0;
            $quaPeriodos = 0;
            $subQuery = "SELECT col_periodoid FROM tbl_alumnos_taxonomia WHERE col_alumnoid='".$atencionData->col_alumnoid."'";
            $queryPeriodos = "SELECT * FROM tbl_periodos WHERE col_id IN (".$subQuery.") ORDER BY col_grado ASC";
            $sth = $db->prepare($queryPeriodos);
            $sth->execute();
            $periodos = $sth->fetchAll();
            foreach($periodos AS $periodo) {
                $periodoData = getPeriodo($periodo['col_id'], $db, false);
                $query = 'SELECT * FROM tbl_calificaciones WHERE (col_materia_clave NOT LIKE "%AC%" AND col_materia_clave NOT LIKE "%TL%" AND col_materia_clave NOT LIKE "%CL%" AND col_materia_clave NOT LIKE "%TR%") AND
                col_alumnoid="'.$atencionData->col_alumnoid.'" AND col_periodoid="'.$periodoData->col_id.'" AND col_groupid="'.$periodoData->col_groupid.'" ORDER BY col_materia_clave ASC';
                $sth = $db->prepare($query);
                $sth->execute();
                $calis = $sth->fetchAll();
                if($sth->rowCount()) {
                ?>
                <table border="0" width="100%" class="bordered" style="margin-bottom: 10px;">
                    <thead>
                        <tr>
                            <th width="55"><?php echo $tipoCurso; ?></th>
                            <th width="60%">Asignatura</th>
                            <th width="5%">P1</th>
                            <?php if($carreraData['modalidad'] == 'Semestral'){ ?><th width="5%">P2</th><?php } ?>
                            <th width="5%">EF</th>
                            <th width="5%">CF</th>
                            <th width="5%">EX</th>
                            <th width="5%">TS</th>
                        </tr>
                    </thead>
                    <tbody class="grid">
                        <?php
                        $sumCF = 0;
                        $qua = 0;
                        foreach($calis as $row) {
                            if(substr($row['col_materia_clave'], 0, 3) == 'LDO') $row['col_materia_clave'] = str_replace('O', '0', $row['col_materia_clave']);
                            $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.$row['col_materia_clave'].'" ';
                            $sth = $db->prepare($query);
                            $sth->execute();
                            $materia = $sth->fetch(PDO::FETCH_OBJ);
                            if($sth->rowCount() == 0) $materia->col_nombre = $row['col_materia_clave'];
                            ?>
                            <tr>
                                <td valign="top" align="center"><?php echo $periodo['col_grado'];?></td>
                                <td valign="top"><?php echo strtoupper(fixEncode($materia->col_nombre, true)); ?></td>
                                <td valign="top" align="center"><?php echo $row['col_p1']; ?></td>
                                <?php if($carreraData['modalidad'] == 'Semestral'){ ?><td valign="top" align="center"><?php echo $row['col_p2']; ?></td><?php } ?>
                                <td valign="top" align="center"><?php echo $row['col_ef']; ?></td>
                                <td valign="top" align="center"><?php echo $row['col_cf']; ?></td>
                                <td valign="top" align="center"><?php echo $row['col_ext']; ?></td>
                                <td valign="top" align="center"><?php echo $row['col_ts']; ?></td>
                            </tr>
                            <?php
                            if(floatval($row['col_ts']) > 0){
                                $cf = $row['col_ts'];
                            }else if(floatval($row['col_ext'])) {
                                $cf = $row['col_ext'];
                            }else{
                                $cf = $row['col_cf'];
                            }
                            $sumCF = $sumCF + $cf;
                            $qua++;
                        }
                        $promPeriodo = $sumCF / $qua;
                        ?>
                        <tr>
                            <td class="noborder" valign="top"></td>
                            <td class="noborder" valign="top" align="right"><b>Promedio <?php echo $tipoCurso; ?></b></td>
                            <td class="noborder" valign="top"></td>
                            <?php if($carreraData['modalidad'] == 'Semestral'){ ?> <td class="noborder" valign="top"></td><?php } ?>
                            <td class="noborder" valign="top"></td>
                            <td class="noborder" valign="top" align="center"><?php echo formatoPromedio($promPeriodo); ?></td>
                            <td class="noborder" valign="top"></td>
                            <td class="noborder" valign="top"></td>
                        </tr>
                    </tbody>
                </table>
                <?php
                 $promGeneral = $promGeneral + $promPeriodo;
                 $quaPeriodos++;
                }
            }


            ?>
            <br/>
            <table border="0" width="100%">
                <tr>
                    <td align="right" valign="top" width="85%" class="firma_up"><b>Promedio General</b></td>
                    <td align="center" valign="top" width="15%" class="firma_up"><?php echo formatoPromedio($promGeneral/$quaPeriodos); ?></td>
                </tr>
            </table>
    <br/><br/><br/><br/><br/>


    <table border="0" width="100%">
        <tr>
            <td align="center" valign="top" width="30%"></td>
            <td align="center" valign="top" width="40%" class="firma_up"><?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><?php echo fixEncode($nombreDepto, true);?></td>
            <td align="center" valign="top" width="30%"></td>
        </tr>
        <tr>
            <td align="center" valign="top" width="10%"></td>
            <td align="center" valign="top" width="80%" style="color: #cc0000;">NOTA: La presente será válida solo si cuenta con la firma y sello de la institución.</td>
            <td align="center" valign="top" width="10%"></td>
        </tr>
    </table>


    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], 50, 25, $m['top'], $m['bottom']);

    $mpdf->SetHTMLHeader($header);
    $mpdf->SetHTMLFooter(pdfFooter());

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS(), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('HistorialAcademico.pdf', $output);
    }else{
        $mpdf->Output('HistorialAcademico.pdf', $output);
    }

    // die();
}


function generarHistorialAcademico2($id, $db, $output = 'I') {
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);


    $query = 'SELECT * FROM tbl_atencion WHERE col_tipo="'.$atencionData->col_tipo.'" AND col_created_at<"'.$atencionData->col_created_at.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $folio = ($sth->rowCount() + 1);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $tipoCurso = 'Semestre';
    if($carreraData['modalidad'] == 'Cuatrimestral') {
        $tipoCurso = 'Cuatrimestre';
    }

    ob_start();
    ?>
    <table border="0" width="100%">
        <tr>
            <td width="25%">
                <img src="<?php echo getLogo(); ?>" style="max-width: 180px;height:auto;" alt="FLDCH" border="0"/>
            </td>
            <td width="75%" align="center"><span class="titulo"><b>KARDEX DEL ALUMNO</b></span><br/><b><small><?php echo strtoupper(fixEncode($carreraData['nombre'], true)); ?></small></b></td>
        </tr>
    </table><br/>
    <table border="0" width="100%" class="bordered">
        <tr>
            <td width="10%" align="left" valign="top">ALUMNO:</td>
            <td width="50%" align="left" valign="top"><?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true); ?></td>
            <td width="10%" align="left" valign="top">MARÍCULA:</td>
            <td width="30%" align="left" valign="top"><?php echo $alumnodData->col_control; ?></td>
        </tr>
        <tr>
            <td width="10%" align="left" valign="top">CURP:</td>
            <td width="50%" align="left" valign="top"></td>
            <td width="10%" align="left" valign="top">FEC. NAC.:</td>
            <td width="30%" align="left" valign="top"><?php echo fechaTexto($alumnodData->col_fecha_nacimiento); ?></td>
        </tr>
        <tr>
            <td width="10%" align="left" valign="top">DOMICILIO:</td>
            <td width="30%" colspan="3" valign="top" align="left"><?php echo strtoupper(fixEncode($alumnodData->col_direccion, true)); ?>, <?php echo strtoupper(fixEncode($alumnodData->col_ciudad, true)); ?>,<?php echo strtoupper(fixEncode($alumnodData->col_estado, true)); ?></td>
        </tr>
        <tr>
            <td width="10%" align="left" valign="top">TEL. CASA:</td>
            <td width="30%" align="left" valign="top"><?php echo $alumnodData->col_telefono; ?></td>
            <td width="10%" align="left" valign="top">CELULAR:</td>
            <td width="30%" align="left" valign="top"><?php echo $alumnodData->col_celular; ?></td>
        </tr>
    </table>
    <?php
    $header = ob_get_contents();
    ob_end_clean();

    ob_start();
    ?>
            <?php
            $promGeneral = 0;
            $quaPeriodos = 0;
            $subQuery = "SELECT col_periodoid FROM tbl_alumnos_taxonomia WHERE col_alumnoid='".$atencionData->col_alumnoid."'";
            $queryPeriodos = "SELECT * FROM tbl_periodos WHERE col_id IN (".$subQuery.") ORDER BY col_grado DESC";
            $sth = $db->prepare($queryPeriodos);
            $sth->execute();
            $periodos = $sth->fetchAll();
            foreach($periodos AS $periodo) {
                $query = "SELECT * FROM tbl_calificaciones WHERE col_cf!='' AND col_periodoid='".$periodo['col_id']."' AND col_alumnoid='".$atencionData->col_alumnoid."'";
                $sth = $db->prepare($query);
                $sth->execute();
                $calis = $sth->fetchAll();
                if($sth->rowCount()) {
                ?>
                <table border="0" width="100%" class="bordered">
                    <thead>
                        <tr>
                            <th width="15%"><?php echo $tipoCurso; ?></th>
                            <th width="70%">Asignatura</th>
                            <th width="15%">Calficación</th>
                        </tr>
                    </thead>
                    <tbody class="grid">
                        <?php
                        $sumCF = 0;
                        $qua = 0;
                        foreach($calis as $row) {
                            if(substr($row['col_materia_clave'], 0, 3) == 'LDO') $row['col_materia_clave'] = str_replace('O', '0', $row['col_materia_clave']);
                            $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.$row['col_materia_clave'].'" ';
                            $sth = $db->prepare($query);
                            $sth->execute();
                            $materia = $sth->fetch(PDO::FETCH_OBJ);
                            if($sth->rowCount() == 0) $materia->col_nombre = $row['col_materia_clave'];
                            ?>
                            <tr>
                                <td valign="top" align="center"><?php echo $periodo['col_grado'];?></td>
                                <td valign="top"><?php echo strtoupper(fixEncode($materia->col_nombre, true)); ?></td>
                                <td valign="top" align="center"><?php echo $row['col_cf']; ?></td>
                            </tr>
                            <?php

                            if(floatval($row['col_ts']) > 0){
                                $cf = $row['col_ts'];
                            }else if(floatval($row['col_ext'])) {
                                $cf = $row['col_ext'];
                            }else{
                                $cf = $row['col_cf'];
                            }
                            $sumCF = $sumCF + $cf;
                            $qua++;
                        }
                        $promPeriodo = $sumCF / $qua;
                        ?>
                        <tr>
                            <td class="noborder" valign="top"></td>
                            <td class="noborder" valign="top" align="right"><b>Promedio <?php echo $tipoCurso; ?></b></td>
                            <td class="noborder" valign="top" align="center"><?php echo formatoPromedio($promPeriodo); ?></td>
                        </tr>
                    </tbody>
                </table><br/><br/>
                <?php
                 $promGeneral = $promGeneral + $promPeriodo;
                 $quaPeriodos++;
                }
            }


            ?>
            <br/>
            <table border="0" width="100%">
                <tr>
                    <td align="right" valign="top" width="85%" class="firma_up"><b>Promedio General</b></td>
                    <td align="center" valign="top" width="15%" class="firma_up"><?php echo formatoPromedio($promGeneral/$quaPeriodos); ?></td>
                </tr>
            </table>
    <br/><br/><br/><br/><br/>


    <table border="0" width="100%">
        <tr>
            <td align="center" valign="top" width="30%"></td>
            <td align="center" valign="top" width="40%" class="firma_up"><?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><?php echo fixEncode($nombreDepto, true);?></td>
            <td align="center" valign="top" width="30%"></td>
        </tr>
        <tr>
            <td align="center" valign="top" width="10%"></td>
            <td align="center" valign="top" width="80%" style="color: #cc0000;">NOTA: La presente será válida solo si cuenta con la firma y sello de la institución.</td>
            <td align="center" valign="top" width="10%"></td>
        </tr>
    </table><br/>


    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db, true);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], $m['bottom']);

    $mpdf->SetHTMLHeader($header);
    $mpdf->SetHTMLFooter(pdfFooter());

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS(), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('HistorialAcademico.pdf', $output);
    }else{
        $mpdf->Output('HistorialAcademico.pdf', $output);
    }

    // die();
}

function generarConstanciaServicio($id, $db, $output = 'I') {
    global $nombreInstituto;
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);


    $query = 'SELECT * FROM tbl_users WHERE col_id="11" LIMIT 1 ';
    if(intval($extraData['segundaFirma']) > 0) $query = 'SELECT * FROM tbl_users WHERE col_id="'.intval($extraData['segundaFirma']).'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $laModalidad = $carreraData['modalidad'];
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
    }

    $periodosActivos = getCurrentPeriodos($db);
    $query = 'SELECT * FROM tbl_servicio_social WHERE col_alumnoid="'.$atencionData->col_alumnoid.'" AND col_lugar!="" AND col_periodoid="'.$extraData['periodoServicio'].'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $ssData = $sth->fetch(PDO::FETCH_OBJ);

    ob_start();
    ?>
    <table width="100%">
        <tr>
            <td width="10%" valign="top" align="left"><img width="70" src="<?php echo getLogo('sep_chiapas_icono'); ?>" /></td>
            <td width="80%" valign="top" align="center">
                <h2>GOBIERNO DEL ESTADO DE CHIAPAS<BR/>SECRETARÍA DE EDUCACIÓN<BR/>SUBSECRETARÍA DE EDUCACIÓN ESTATAL</h2>
                <p>DIRECCIÓN DE EDUCACIÓN SUPERIOR<BR/>DEPARTAMENTO DE SERVICIOS ESCOLARES</p>
                <p>RVOE: <?php echo $carreraData['revoe']; ?> VIGENCIA: <?php echo $carreraData['vigencia_revoe']; ?></p>
            </td>
            <td width="10%"></td>
        </tr>
    </table>

    <div class="fondoAzul"></div>
    <div class="sobreFondoAzul">
    <h1 style="font-size: 50pt;font-family: Serif;text-align: center;padding-top:10px;margin-top:0;margin-bottom:0;padding-bottom:0;">C  o  n  s  t  a  n  c  i  a</h1>
    <h3 style="font-family: Serif;text-align: center;">DE SERVICIO SOCIAL QUE OTORGA A</h3>
    <p style="font-family: Serif;text-align: center;border-bottom:1px solid #222222;"><?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos);?></p>

    <h3 style="font-family: Serif;text-align: center;">DE LA CARRERA</h3>
    <p style="font-family: Serif;text-align: center;border-bottom:1px solid #222222;"><?php echo strtoupper($carreraData['nombre']); ?></p>

    <h3 style="font-family: Serif;text-align: center;">DE LA INSTITUCIÓN</h3>
    <p style="font-family: Serif;text-align: center;border-bottom:1px solid #222222;"><?php echo strtoupper($nombreInstituto); ?></p>

    <h3 style="font-family: Serif;text-align: center;">REGISTRO No.</h3>
    <p style="font-family: Serif;text-align: center;border-bottom:1px solid #222222;"><?php echo fixEncode($extraData['registroServicio']);?></p>

    <h3 style="font-family: Serif;text-align: center;">POR HABER PRESENTADO SU SERVICIO SOCIAL EN EL PROGRAMA</h3>
    <p style="font-family: Serif;text-align: center;border-bottom:1px solid #222222;"><?php echo fixEncode($extraData['programaServicio']);?></p>

    <h3 style="font-family: Serif;text-align: center;">EN<br/>(DEPENDENCIA, INSTITUCIÓN, U ORGANISMOS)</h3>
    <p style="font-family: Serif;text-align: center;border-bottom:1px solid #222222;"><?php echo fixEncode($ssData->col_lugar);?></p>

    <h3 style="font-family: Serif;text-align: center;">DURANTE EL PERIODO</h3>
    <p style="font-family: Serif;text-align: center;border-bottom:1px solid #222222;"><?php echo fixEncode($extraData['periodoDescripcionServicio']);?></p>

    <h3 style="font-family: Serif;text-align: center;">CON DURACIÓN DE</h3>
    <p style="font-family: Serif;text-align: center;border-bottom:1px solid #222222;"><?php echo fixEncode($extraData['duracionServicio']);?></p>

    <h3 style="font-family: Serif;text-align: center;border-bottom:1px solid #222222;">Y PARA LOS EFECTOS PROCEDENTES SE EXTIENDE<BR/>LA PRESENTE CONSTANCIA EN LA CIUDAD DE<BR/>TUXTLA GUTIÉRREZ, CHIAPAS</h3>
    <h3 style="font-family: Serif;text-align: center;border-bottom:1px solid #222222;">A LOS <?php echo date('d', strtotime($atencionData->col_fecha)); ?> DÍAS DEL MES DE <?php echo strtoupper(getMes(date('F', strtotime($atencionData->col_fecha)))); ?> DE <?php echo date('Y', strtotime($atencionData->col_fecha)); ?></h3>
    </div>

    <?php

    $jefeFirma = 'JEFE DEL DEPARTAMENTO DE SERVICIOS ESCOLARES';
    if(strtoupper($userData->col_genero) == 'M') $jefeFirma = 'JEFA DEL DEPARTAMENTO DE SERVICIOS ESCOLARES';
    ?>
    <table border="0" width="100%" style="margin-top: 50px;">
        <tr>
            <td align="center" valign="top" width="45%" style="text-transform: uppercase;"><span style="border-bottom: 1px solid #000;">&nbsp;&nbsp;<?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?>&nbsp;&nbsp;</span><br/><small><?php echo $jefeFirma;?></small></td>
            <td width="10%"></td>
            <td align="center" valign="top" width="45%" style="text-transform: uppercase;"><span style="border-bottom: 1px solid #000;">&nbsp;&nbsp;<?php echo fixEncode($directoraData->col_titulo.' '.$directoraData->col_firstname.' '.$directoraData->col_lastname, true); ?>&nbsp;&nbsp;</span><br/><small>POR LA INSTITUCIÓN EDUCATIVA</small></td>
        </tr>
    </table>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader('');
    // $mpdf->SetHTMLFooter(pdfFooter());
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('12px', '12px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('constancia-servicio-social.pdf', $output);
    }else{
        $mpdf->Output('constancia-servicio-social.pdf', $output);
    }

    // die();
}

function generarCartaPasante($id, $db, $output = 'I') {
    global $firmas;
    global $nombreInstituto, $claveInstitulo;

    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$firmas['director'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$firmas['rector'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $rectorData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $laModalidad = $carreraData['modalidad'];

    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
    }

    // $turno = 'VESPERTINO';
    // if($carreraData['modalidad'] == 'Cuatrimestral') $turno = 'MIXTO';

    $turno = 'MIXTO';
    if($carreraData['modalidad'] == 'Semestral') $turno = 'VESPERTINO';

    $periodoEstudios = getPeriodoEstudios($alumnodData->col_id, $db);

    ob_start();
    ?>
    <table width="100%">
        <tr>
            <td width="50%" valign="top" align="left"><img width="240" src="<?php echo getLogo('sep_chiapas_nuevo'); ?>" /></td>
            <td width="50%" valign="middle" align="right"><img width="270" src="<?php echo getLogo(); ?>" /></td>
        </tr>
        <tr>
            <td width="50%" align="left"></td>
            <td width="50%" align="right"><p>FOLIO: <span style="font-weight:bold;color:#cc0000;"><?php echo $atencionData->col_folio; ?></span></p></td>
        </tr>
    </table>
    <?php
    $header = ob_get_contents();
    ob_end_clean();
    $diaLetras = 'a un día';
    if(intval(date('j', strtotime($atencionData->col_fecha))) > 1) {
        $diaLetras = 'a los '.strtolower(numerosaletras(date('d', strtotime($atencionData->col_fecha)))).' días';
    }
    ob_start();
    echo $header;
    ?>
    <br/><br/>
    <table width="100%">
        <tr>
            <td width="33%" align="left"><p>CLAVE: <?php echo $claveInstitulo; ?></p></td>
            <td width="34%" align="center"><p>RÉGIMEN: PARTICULAR</p></td>
            <td width="33%" align="right"><p>TURNO: <?php echo $turno; ?></p></td>
        </tr>
    </table>
    <br/><br/>
    <table width="100%">
        <tr>
            <td width="30%" valign="top" align="center"><img width="130" src="<?php echo getImage('foto'); ?>" /></td>
            <td width="70%" valign="top" align="center">
                <h2 style="text-align: center;"><b>CARTA DE PASANTE<br/><br/>A:</b></h2><br/>
                <h2 style="text-align: center;"><?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos);?></h2>
                <br/><br/><br/>
                <p>En atención a que terminó íntegramente sus estudios conforme al plan vigente de la<br/><br/><?php echo $carreraData['nombre']; ?></p><br/><br/>
                <p>En el periodo de <?php echo fixEncode($periodoEstudios); ?>.</p><br/><br/><br/>
            </td>
        </tr>
    </table>
    <table width="100%">
        <tr>
            <td width="100%" valign="top">
                <p>Incorporado al Sistema Educativo Estatal según acuerdo No. <?php echo $carreraData['revoe']?>. A partir del <?php echo date('d', strtotime($carreraData['vigencia_revoe_date'])); ?> de <?php echo getMes(date('F', strtotime($carreraData['vigencia_revoe_date']))); ?> de <?php echo date('Y', strtotime($carreraData['vigencia_revoe_date'])); ?>.</p><br/><br/>
                <p>En cumplimiento de las disposiciones reglamentarias y para los usos legales que procedan se expide la
                    presente <?php echo $diaLetras; ?> de <?php echo strtolower(getMes(date('F', strtotime($atencionData->col_fecha)))); ?>
                    de <?php echo strtolower(numerosaletras(date('Y', strtotime($atencionData->col_fecha)))); ?>, en la ciudad de Tuxtla Gutiérrez, Chiapas.
                </p>
            </td>
        </tr>
    </table>
    <br/><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="5%"></td>
            <td align="center" valign="top" width="43%" class="firma_up" style="text-transform: uppercase;"><small><?php echo $rectorData->col_titulo.' '.fixEncode($rectorData->col_firstname.' '.$rectorData->col_lastname, true); ?><br/>DIRECTOR DE LA FLDCH</small></td>
            <td width="4%"></td>
            <td align="center" valign="top" width="43%" class="firma_up" style="text-transform: uppercase;"><small><?php echo $directoraData->col_titulo.' '.fixEncode($directoraData->col_firstname.' '.$directoraData->col_lastname, true); ?><br/>RECTORA DE LA FLDCH</small></td>
            <td width="5%"></td>
        </tr>
    </table>
    <br/><br/><br/>
    <p style="text-align: center;">5ª Poniente Norte No. 633 Col. Centro  Tuxtla Gutiérrez, Chiapas.</p>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    include_once(__DIR__ . '/../src/mpdf/mpdf.php');


    $m = getMargins($db);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader('');
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('15px', '15px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('carta-pasante.pdf', $output);
    }else{
        $mpdf->Output('carta-pasante.pdf', $output);
    }

    // die();
}

function generarConstanciaTerminacion($id, $db, $output = 'I') {
    global $nombreInstituto, $claveInstitulo, $_indicacionInstituto;

    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_depto="11" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $laModalidad = $carreraData['modalidad'];
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
    }

    $obtiene = 'a la obtención del título profesional';
    if($carreraData['modalidad_periodo'] == 'master'){
        $obtiene = 'a la obtención del grado de maestra';
        if(strtolower($alumnodData->col_genero) == 'h') $obtiene = 'al grado de maestro';
    }

    if($carreraData['modalidad_periodo'] == 'docto'){
        $obtiene = 'a la obtención del grado de doctora';
        if(strtolower($alumnodData->col_genero) == 'h') $obtiene = 'al grado de doctor';
    }

    ob_start();
    ?>
    <br/>
    <table width="100%">
        <tr>
            <td width="50%" valign="top" align="left"><img width="70" src="<?php echo getLogo('gobernacion'); ?>" /></td>
            <td width="50%" valign="top" align="right"><img width="240" src="<?php echo getLogo(); ?>" /></td>
        </tr>
    </table>
    <?php
    $header = ob_get_contents();
    ob_end_clean();

    $diaLetras = 'a un día';
    if(intval(date('j', strtotime($atencionData->col_fecha))) > 1) {
        $diaLetras = 'a los '.strtolower(numerosaletras(date('d', strtotime($atencionData->col_fecha)))).' días';
    }
    ob_start();
    ?>
    <table width="100%">
        <tr>
            <td align="right" class="lucida"><p><b>Asunto: Constancia de Terminación de Estudios</b></p></td>
        </tr>
    </table>
    <br/><br/>
    <h3 class="lucida">A QUIEN CORRESPONDA</h3><br/><br/>
    <p class="lucida" style="text-align: justify;">La que suscribe, Rectora <?php echo $_indicacionInstituto; ?> <?php echo $nombreInstituto; ?>, Régimen particular con clave <?php echo $claveInstitulo; ?>.</p>
    <h3 class="lucida" style="padding:15px 0;text-align:center;">HACE CONSTAR:</h3>
    <p class="lucida" style="text-align: justify;">Que <?php echo (strtolower($alumnodData->col_genero) == 'm'?'la alumna':'el alumno'); ?><b>
        <?php echo trim(fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos));?></b>, ha concluido sus estudios
    <b><?php echo trim(strtoupper($carreraData['nombre'])); ?></b>, con No. de RVOE ante la Secretaría de Educación del
    Estado <?php echo strtoupper($carreraData['revoe']); ?> vigente a partir del <?php echo strtolower($carreraData['vigencia_revoe']); ?>; lo cual lo
    hace <b><?php echo (strtolower($alumnodData->col_genero) == 'm'?'Candidata':'Candidato'); ?> <?php echo $obtiene; ?></b>, faltando por cubrir los requisitos de
    titulación establecidos en el Reglamento de esta Facultad.</p>
    <p class="lucida" style="text-align: justify;">Sin otro particular, se extiende la presente
    <?php echo $diaLetras; ?> del mes de <?php echo strtolower(getMes(date('F', strtotime($atencionData->col_fecha)))); ?> del año dos mil <?php echo strtolower(numerosaletras(substr(date('Y', strtotime($atencionData->col_fecha)), -2))); ?>, en la Ciudad de Tuxtla Gutiérrez, Chiapas.</p>
    <br/>
    <p class="lucida" style="text-align: center;"><b>Atentamente<br/>Por el engrandecimiento del Estado de Chiapas</b></p>
    <br/><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="20%"></td>
            <td align="center" valign="top" width="60%" class="firma_up lucida" style="text-transform: uppercase;">
            <?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small>RECTORA DE LA FLDCH</small></td>
            <td width="20%"></td>
        </tr>
    </table>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    ob_start();
    ?>
    <br/><br/><br/><br/>
    <?php
    $footer = ob_get_contents();
    ob_end_clean();

    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $mpdf=new mPDF('s','Letter', 0, '', 20, 20, 45, 35);

    $mpdf->SetHTMLHeader($header);
    $mpdf->SetHTMLFooter($footer);

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('15px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('constancia-terminacion-estudios.pdf', $output);
    }else{
        $mpdf->Output('constancia-terminacion-estudios.pdf', $output);
    }

    // die();
}


function generarDiploma($id, $db, $output = 'I') {
    global $firmas, $nombreInstituto, $claveInstitulo, $_indicacionInstituto;

    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$firmas['director'].'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_depto="11" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $laModalidad = $carreraData['modalidad'];
    $tipoCurso = 'semestre';
    if($carreraData['modalidad'] == 'Cuatrimestral'){
        $tipoCurso = 'cuatrimestre';
    }
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
    }

    $lael = 'la';
    if($carreraData['modalidad_periodo'] == 'docto') {
        $lael = 'el';
    }

    ob_start();
    ?>
    <table width="100%">
        <tr>
            <td width="100%" valign="top" align="center"><img width="370" src="<?php echo getLogo('big'); ?>" /></td>
        </tr>
    </table>
    <?php
    $header = ob_get_contents();
    ob_end_clean();

    ob_start();
    echo $header;
    ?>
    <p style="text-align:center;">
        <span style="font-weight: bold;" class="lucida">Otorga el presente</span><br/>
        <span style="font-size: 80pt;line-height: 10px;font-family: crimson;font-weight:normal;">DIPLOMA</span><br/>
        <span style="font-size: 30px;font-family: crimson;text-align: center;">a</span>
        <p style="font-size: 30px;font-family: crimson;text-align: center;"><?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos);?></p>
        <p style="line-height: 1pt;font-size: 20px;font-family: crimson;text-align: center;">Por haber cursado <?php echo $lael; ?></p>
        <p style="line-height: 35px;font-size: 30px;font-family: crimson;text-align: center;"><?php echo wordwrap(mb_strtoupper(fixEncode($carreraData['nombre'], true, true)), 40, '<br/>', true);?></p>
        <p style="font-size: 20px;font-family: crimson;text-align: center;">Generación: <?php echo strtoupper($alumnodData->col_generacion_start); ?> – <?php echo strtoupper($alumnodData->col_generacion_end); ?></p>
        <p style="text-align: center;"> Tuxtla Gutiérrez, Chiapas, <?php echo getMes(date('F', strtotime($atencionData->col_fecha))); ?> de <?php echo date('Y', strtotime($atencionData->col_fecha)); ?></p>
    </p>
    <br/><br/>
    <p style="text-align: center;"><?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small>Rectora <?php echo $_indicacionInstituto; ?> <?php echo $nombreInstituto; ?></small></p>
    <?php
    $html = ob_get_contents();
    ob_end_clean();


    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $mpdf=new mPDF('s','Letter-L', '','', 20, 20, 10, 10, 0, 0);
    $mpdf->useFixedNormalLineHeight = false;
    $mpdf->useFixedTextBaseline = false;
    $mpdf->adjustFontDescLineheight = 1;

    $mpdf->SetHTMLHeader('');
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('15px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('diploma.pdf', $output);
    }else{
        $mpdf->Output('diploma.pdf', $output);
    }

    // die();
}

function generarCertificadoParcial($id, $db, $output = 'I') {
    global $nombreInstituto, $claveInstitulo;

    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT t.col_id, t.col_alumnoid, t.col_periodoid, t.col_status, p.col_grado FROM `tbl_alumnos_taxonomia` t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_baja=0 AND p.col_grado=1 AND t.col_alumnoid="'.$atencionData->col_alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoPeriodoInicial = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT t.col_id, t.col_alumnoid, t.col_periodoid, t.col_status, p.col_grado FROM `tbl_alumnos_taxonomia` t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_baja=0 AND t.col_alumnoid="'.$atencionData->col_alumnoid.'" ORDER BY p.col_grado DESC LIMIT 1';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoPeriodoFinal = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_depto="11" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);
    $periodoDataInicio = getPeriodo($alumnoPeriodoInicial->col_periodoid, $db, false);

    $periodoDataFin = getPeriodo($alumnoPeriodoFinal->col_periodoid, $db, false);


    $laModalidad = $carreraData['modalidad'];
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
    }

    if($carreraData['modalidad'] == 'Semestral') $carreraMod = 'SEMESTRE';
    if($carreraData['modalidad'] == 'Cuatrimestral') $carreraMod = 'CUATRIMESTRE';
    $promGeneral = 0;
    $creditosGanados = 0;
    // $carreraData['modalidad'] = 'ESCOLARIZADA';
    $carreraDataModalidad = 'ESCOLARIZADA';
    if($carreraData['modalidad'] == 'Cuatrimestral') $carreraDataModalidad = 'MIXTA';

    $turno = 'VESPERTINO';
    if($carreraData['modalidad'] == 'Cuatrimestral') $turno = 'MIXTO';

    $elLa = 'LA';
    if($alumnodData->col_genero != 'M') $elLa = 'EL';


    $minimaAprobatoria = 'MÍNIMA APROBATORIA 7 (SIETE)';
    if($carreraData['modalidad'] == 'Doctorado') $minimaAprobatoria = 'MÍNIMA APROBATORIA 8 (OCHO)';

    ob_start();
    ?>
    <div class="lateralText">
        Este documento no es válido si presenta raspaduras o enmendaduras.
    </div>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td colspan="3" align="right" style="padding-bottom:12px;"><small>SE-CP-<?php echo substr(date('Y', strtotime($atencionData->col_fecha)), -2); ?></small></td>
        </tr>
        <tr>
            <td width="18%" valign="top" align="center" style="padding-top:15px;"><img width="86" src="<?php echo getLogo('gobernacion'); ?>" /></td>
            <td width="64%" valign="top" align="center">
                <p style="font-size:20px;">GOBIERNO DEL ESTADO DE CHIAPAS<BR/>SECRETARÍA DE EDUCACIÓN</p>
                <p style="font-size:14px;margin: 5px 0;">SUBSECRETARÍA DE EDUCACIÓN ESTATAL<br/>DIRECCIÓN DE EDUCACIÓN SUPERIOR<br/>DEPARTAMENTO DE SERVICIOS ESCOLARES</p>
            </td>
            <td width="18%" valign="top" align="right">
                <br/><br/>FOLIO: <i style="font-style:normal;border-bottom: 2px solid #222;color:#cc0000;"><?php echo $atencionData->col_folio; ?></i>
            </td>
        </tr>
        <tr>
            <td width="18%" valign="top" align="left"></td>
            <td colspan="2" style="font-size:13px;">RVOE: ACUERDO NÚMERO <?php echo $carreraData['revoe']?>&nbsp;&nbsp;&nbsp;&nbsp;VIGENTE: <?php echo strtoupper($atencionData->col_vigente); ?></td>
        </tr>
    </table>

    <table width="100%" border="0" style="font-size: 12px;margin-bottom: 10px;" cellpadding="0" cellspacing="0">
        <tr>
            <td width="18%" valign="bottom" align="left"><img align="left" style="padding:0;margin:0;" width="130" src="<?php echo getImage('foto'); ?>" /></td>
            <td width="82%" valign="top">

                <table width="100%" style="margin: 10px 0;">
                    <?php if($atencionData->col_duplicado == 1) {?>
                        <tr><td align="center" style="padding-bottom: 5px;"><h2 style="text-align: center;font-size:20px;">DUPLICADO</h2></tr>
                    <?php }else{ ?>
                        <tr><td align="center" style="padding-bottom: 5px;"><h2 style="text-align: center;font-size:20px;">PARCIAL</h2></tr>
                    <?php } ?>
                    <tr>
                        <td align="center">
                            <h2 style="text-align: center;font-size:20px;"><?php echo strtoupper($nombreInstituto); ?></h2>
                        </td>
                    </tr>
                </table>
                <table width="100%">
                    <tr>
                        <td align="center" style="font-size:12px;">
                        RÉGIMEN: PARTICULAR&nbsp;&nbsp;
                        TURNO: <?php echo $turno; ?>&nbsp;&nbsp;
                        MODALIDAD: <?php echo strtoupper(fixEncode($carreraDataModalidad)); ?>&nbsp;&nbsp;
                        CLAVE: <?php echo $claveInstitulo; ?>
                        </td>
                    </tr>
                </table>
                <table width="100%" style="margin: 0;">
                    <tr>
                        <td align="justify" style="font-size:14px;line-height: 150%;">
                            <p>CERTIFICA QUE EL(LA) C. <u>&nbsp;&nbsp;<?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, false, true);?>&nbsp;&nbsp;</u> CON No. DE CONTROL <u>&nbsp;&nbsp;<?php echo $alumnodData->col_control; ?>&nbsp;&nbsp;</u> ACREDITÓ LAS MATERIAS QUE INTEGRAN
                            EL PLAN DE ESTUDIOS DE LA <u>&nbsp;&nbsp;<?php echo mb_strtoupper(fixEncode($carreraData['nombre'])); ?>&nbsp;&nbsp;</u> EN EL PERIODO DE <u>&nbsp;&nbsp;<?php echo strtoupper(getMes(date('F', strtotime($periodoDataInicio->col_fecha_inicio)))); ?></u> DE <u><?php echo date('Y', strtotime($periodoDataInicio->col_fecha_inicio)); ?>&nbsp;&nbsp;</u> A
                            <u><?php echo strtoupper(getMes(date('F', strtotime($periodoDataFin->col_fecha_fin)))); ?></u> DE <u><?php echo date('Y', strtotime($periodoDataFin->col_fecha_fin)); ?></u> CON LOS RESULTADOS QUE A CONTINUACIÓN SE ANOTÁN:</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

        <?php
            $query = "SELECT p.col_id, p.col_grado, p.col_plan_estudios FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_baja=0 AND t.col_alumnoid='".$alumnodData->col_id."' ORDER BY p.col_grado";
            $sth = $db->prepare($query);
            $sth->execute();
            $periodos = $sth->fetchAll();
            $i = 0;
            foreach($periodos as $periodo) {
                $query = "SELECT * FROM tbl_calificaciones WHERE col_cf!='' AND col_periodoid='".$periodo['col_id']."' AND col_alumnoid='".$atencionData->col_alumnoid."'";
                $sth = $db->prepare($query);
                $sth->execute();
                if($sth->rowCount() == 0) continue;
                $calis = $sth->fetchAll();
                ?>
                <div style="width:50%;float:left;">
                    <table width="400" class="bordered">
                        <thead>
                            <tr>
                                <th rowspan="2"><?php echo strtoupper(getLetraGrado($periodo['col_grado'])); ?> <?php echo $carreraMod; ?></th>
                                <th colspan="2" align="center">CALIFICACIÓN</th>
                                <th rowspan="2" align="center"></th>
                            </tr>
                            <tr>
                                <th style="text-align: center;">CIFRA</th>
                                <th style="text-align: center;">LETRA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $mc = 0;
                            $sc = 0;
                            $crow = 0;
                            unset($arrMaterias);
                            foreach($calis as $c){
                                if(in_array(strtoupper(substr($c['col_materia_clave'], 0, 2)), array('AC', 'TL', 'CL', 'TR'))) continue;
                                $materiaData = getMateria('col_clave', $c['col_materia_clave'], $db, $periodo['col_id']);
                                $laCalificacion = $c['col_cf'];
                                $anotacion = '';
                                if(intval($c['col_ext']) > 0) {
                                    $laCalificacion = $c['col_ext'];
                                    $anotacion = 'EXT';
                                }
                                if(intval($c['col_ts']) > 0) {
                                    $laCalificacion = $c['col_ts'];
                                    $anotacion = 'TS';
                                }
                                if(strpos($laCalificacion, '.') !== false) {
                                    $splitCalificacion = explode('.', $laCalificacion);
                                    if(intval($splitCalificacion[1]) > 0){
                                        $laCalificacion = $splitCalificacion[0].'.'.substr($splitCalificacion[1], 0, 1);
                                    }else{
                                        $laCalificacion = intval($laCalificacion);
                                    }
                                }else{
                                    $laCalificacion = intval($laCalificacion);
                                }
                                ob_start();
                                ?>
                                <tr class="heightCali">
                                    <td valign="top" class="noborder"><?php echo fixEncode($materiaData->col_nombre, true, true); ?></td>
                                    <td valign="top" style="width: 70px;" class="bordersides" align="center"><?php echo $laCalificacion; ?></td>
                                    <td valign="top" style="width: 70px;" class="bordersides" align="center"><?php echo strtoupper(is_numeric($laCalificacion)?numerosaletras($laCalificacion):''); ?></td>
                                    <td valign="top" style="width: 60px;" class="bordersides" align="center"><?php echo $anotacion; ?></td>
                                </tr>
                                <?php
                                $htmlMateria = ob_get_contents();
                                ob_end_clean();
                                //$arrMaterias[fixEncode($materiaData->col_nombre, true, true)] = $htmlMateria;
                                $arrMaterias[$materiaData->col_clave] = $htmlMateria;
                                $mc++;
                                $sc = $laCalificacion + $sc;
                                if($laCalificacion >= 7) {
                                    $creditosGanados = $creditosGanados + $materiaData->col_creditos;
                                }
                                $crow++;
                            }
                            ksort($arrMaterias);
                            foreach($arrMaterias as $item_arrMaterias) {
                                echo $item_arrMaterias;
                            }

                            if($crow < 7) {
                                for($cr = 0; $cr < (7 - $crow); $cr++){
                                    ?>
                                    <tr class="heightCali">
                                        <td valign="top" class="noborder">&nbsp;</td>
                                        <td valign="top" style="width: 70px;" class="bordersides" align="center">&nbsp;</td>
                                        <td valign="top" style="width: 70px;" class="bordersides" align="center">&nbsp;</td>
                                        <td valign="top" style="width: 60px;" class="bordersides" align="center">&nbsp;</td>
                                    </tr>
                                    <?php
                                }
                            }

                            $sc = $sc / $mc;
                            $promGeneral = $sc + $promGeneral;
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
                $i++;
            }

            if($i < 8) {
                for($p = $i; $p < 8; $p++) {
                    ?>
                        <div style="width:50%;float:left;">
                            <table width="400" class="bordered">
                                <thead>
                                    <tr>
                                        <th rowspan="2"><?php echo strtoupper(getLetraGrado(($p+1))); ?> <?php echo $carreraMod; ?></th>
                                        <th colspan="2" align="center">CALIFICACIÓN</th>
                                        <th rowspan="2" align="center"></th>
                                    </tr>
                                    <tr>
                                        <th style="text-align: center;">CIFRA</th>
                                        <th style="text-align: center;">LETRA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td  class="crossed"></td>
                                        <td style="width: 70px;" class="bordersides" align="center"></td>
                                        <td style="width: 70px;" class="bordersides" align="center"></td>
                                        <td style="width: 60px;" class="bordersides" align="center"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php
                }
            }
        ?>
        <div style="clear:both;"></div>

    <?php
    $promGeneral = formatoPromedio($promGeneral / $i);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaDirector'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaRector'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $rectorData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaServiciosEcolares'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $escolaresData = $sth->fetch(PDO::FETCH_OBJ);

    $jefeServiciosEscolaresFirma = 'JEFE DEL DEPARTAMENTO DE SERVICIOS ESCOLARES';
    if(strtoupper($escolaresData->col_genero) == 'M') $jefeServiciosEscolaresFirma = 'JEFA DEL DEPARTAMENTO DE SERVICIOS ESCOLARES';

    $directorEducacionFirmaTitulo = 'DIRECTOR DE EDUCACIÓN SUPERIOR';
    if(strtoupper($extraData['firmaDirectorEducacion']) == 'M') $directorEducacionFirmaTitulo = 'DIRECTORA DE EDUCACIÓN SUPERIOR';
    ?>
    <table style="width:380px;" class="bordered" align="center">
        <tr>
            <td width="50%" align="center">
                <b>PROMEDIO</b>
            </td>
            <td width="50%" align="center">
                ----
            </td>
        </tr>
    </table>
    <pagebreak>
    <br/><br/>
    <p style="line-height: 170%;">LA ESCALA DE CALIFICACIONES ES DE 6 A 10, CONSIDERANDO COMO <?php echo $minimaAprobatoria; ?>, ESTE CERTIFICADO AMPARA <?php echo fixEncode($atencionData->col_ampara); ?> MATERIAS DEL PLAN DE ESTUDIOS VIGENTES Y EN CUMPLIMIENTO A LAS PRESCRIPCIONES LEGALES, SE EXTIENDE EN TUXTLA GUTIÉRREZ, CHIAPAS A LOS <?php echo strtoupper(numerosaletras(date('d', strtotime($atencionData->col_fecha)))); ?> DÍAS DEL MES DE <?php echo strtoupper((getMes(date('F', strtotime($atencionData->col_fecha))))); ?> DEL AÑO DOS MIL <?php echo strtoupper(numerosaletras(substr(date('Y', strtotime($atencionData->col_fecha)), -2))); ?>.</p>
    <br/><br/><br/><br/><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="10%"></td>
            <td align="center" valign="top" width="35%" style="text-transform: uppercase;">DIRECTOR DE LA FACULTAD</td>
            <td width="10%"></td>
            <td align="center" valign="top" width="35%" style="text-transform: uppercase;">RECTOR DE LA FACULTAD</td>
            <td width="10%"></td>
        </tr>
    </table>
    <br/><br/><br/><br/><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="5%"></td>
            <td align="center" valign="top" width="40%" class="firma_up" style="font-size:12px;text-transform: uppercase;"><?php echo fixEncode($directoraData->col_titulo.' '.$directoraData->col_firstname.' '.$directoraData->col_lastname, true); ?></td>
            <td width="10%"></td>
            <td align="center" valign="top" width="40%" class="firma_up" style="font-size:12px;text-transform: uppercase;"><?php echo fixEncode($rectorData->col_titulo.' '.$rectorData->col_firstname.' '.$rectorData->col_lastname, true); ?></td>
            <td width="5%"></td>
        </tr>
    </table>
    <br/><br/><br/><br/><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="5%"></td>
            <td align="center" valign="top" width="40%" style="text-transform: uppercase;"><?php echo $jefeServiciosEscolaresFirma; ?></td>
            <td width="10%"></td>
            <td align="center" valign="top" width="40%" style="text-transform: uppercase;"><?php echo $directorEducacionFirmaTitulo; ?></td>
            <td width="5%"></td>
        </tr>
    </table>
    <br/><br/><br/><br/><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="5%"></td>
            <td align="center" valign="top" width="40%" class="firma_up" style="font-size:12px;text-transform: uppercase;"><?php echo fixEncode($escolaresData->col_titulo.' '.$escolaresData->col_firstname.' '.$escolaresData->col_lastname, true); ?></td>
            <td width="10%"></td>
            <td align="center" valign="top" width="40%" class="firma_up" style="font-size:12px;text-transform: uppercase;"><?php echo fixEncode($atencionData->col_director_educacion, true); ?></td>
            <td width="5%"></td>
        </tr>
    </table>
    <br/><br/><br/><br/><br/><br/><br/>
    <div style="width: 220px; text-align: center; float: left;border:1px solid #333;border-radius: 10px;">
                <p align="center" style="border-bottom: 1px solid #333;">REGISTRADO EN EL DEPARTAMENTO DE SERVICIOS ESCOLARES</p>
                <table border="0" width="100%" style="padding: 0 10px;">
                    <tr>
                        <td align="left" valign="top" width="30%">No.</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;"></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" width="30%">LIBRO</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;"></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" width="30%">FOJA</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;"></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" width="30%">FECHA</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;"></td>
                    </tr>
                </table>
                <br/>
                <p align="center" style="border-bottom: 1px solid #333;border-top: 1px solid #333;">COTEJO</p>
                <br/>
                <p align="center" style="font-size: 10px;"><?php echo fixEncode($extraData['firmaCotejo'], true, true); ?></p>
                <p align="center" style="border-bottom: 1px solid #333;border-top: 1px solid #333;"><?php echo fixEncode($extraData['tituloJefeOficina'], true, true); ?></p>
                <br/>
                <p align="center" style="font-size: 10px;"><?php echo fixEncode($atencionData->col_jefe_oficina, true, true); ?></p>


        </div>
        <div style="width: 400px; text-align: center;float: right;">
                <p>CON FUNDAMENTO EN EL ARTÍCULO 29; FRACCIÓN X DE LA LEY ORGÁNICA DE LA ADMINISTRACIÓN PÚBLICA DEL ESTADO DE CHIAPAS Y 38; FRACCIÓN IX DEL REGLAMENTO INTERIOR DE LA SECRETARÍA GENERAL DE GOBIERNO.</p>
                <p>Se LEGALIZA, previo cotejo con la existente en el control respectivo, la firma que antecede corresponde al Director de Educación Superior.</p>
                <?php
                // $atencionData->col_observaciones = 'There are many issues with this. As mentioned, it fails if the string is less than the 260 character length';
                ?>
                <p style="height: 20px;border-bottom: 1px solid #222;"><?php echo substr(fixEncode($atencionData->col_observaciones), 0, 70); ?></p>
                <p style="height: 20px;border-bottom: 1px solid #222;"><?php echo substr(fixEncode($atencionData->col_observaciones), 70); ?></p>
                <p>Tuxtla Gutiérrez, Chiapas, A:<u><?php for($x = 0; $x < 70; $x++){ echo '&nbsp;';} ?></u></p>
                <center><?php echo fixEncode($extraData['tituloSecretario'], true, true); ?></center>
                <br/><br/>
                <center>_____________________________________________________</center>
                <center><?php echo fixEncode($atencionData->col_subsecretario, true, true); ?></center>
        </div>
    <?php
    $html = ob_get_contents();
    ob_end_clean();
    // {creditos}


    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db, false, true);
    $mpdf=new mPDF('c','LEGAL', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);
    // $mpdf=new mPDF('c','Legal', '','', 15, 15, 10, 10);

    $mpdf->SetHTMLHeader('');
    // $mpdf->SetHTMLFooter(pdfFooter());
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('12px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('CertificadoParcial.pdf', $output);
    }else{
        $mpdf->Output('CertificadoParcial.pdf', $output);
    }

    // die();
}

function generarCertificadoTotal($id, $db, $output = 'I') {
    global $nombreInstituto, $claveInstitulo;
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);
    // print_r($atencionData);exit;
    $extraData = fixSpaces(unserialize($atencionData->col_extra));
    // print_r($extraData);exit;
    // echo $extraData['firmaServiciosEcolares'];exit;

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT t.col_id, t.col_alumnoid, t.col_periodoid, t.col_status, p.col_grado FROM `tbl_alumnos_taxonomia` t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_baja=0 AND p.col_grado=1 AND t.col_alumnoid="'.$atencionData->col_alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoPeriodoInicial = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT t.col_id, t.col_alumnoid, t.col_periodoid, t.col_status, p.col_grado FROM `tbl_alumnos_taxonomia` t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_baja=0 AND t.col_alumnoid="'.$atencionData->col_alumnoid.'" ORDER BY p.col_grado DESC LIMIT 1';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoPeriodoFinal = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);


    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaServiciosEcolares'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $serviciosEscolares = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaDirector'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directorData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaRector'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $rectorData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $laModalidad = $carreraData['modalidad'];
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
    }

    if($carreraData['modalidad'] == 'Semestral') $carreraMod = 'SEMESTRE';
    if($carreraData['modalidad'] == 'Cuatrimestral') $carreraMod = 'CUATRIMESTRE';

    $carreraDataModalidad = 'ESCOLARIZADA';
    if($carreraData['modalidad'] == 'Cuatrimestral') $carreraDataModalidad = 'MIXTA';

    $turno = 'VESPERTINO';
    if($carreraData['modalidad'] == 'Cuatrimestral') $turno = 'MIXTO';

    $promGeneral = 0;
    $creditosGanados = 0;

    $periodoDataInicio = getPeriodo($alumnoPeriodoInicial->col_periodoid, $db, false);

    $periodoDataFin = getPeriodo($alumnoPeriodoFinal->col_periodoid, $db, false);

    $elLa = 'LA';
    if($alumnodData->col_genero != 'M') $elLa = 'EL';

    $minimaAprobatoria = 'MÍNIMA APROBATORIA 7 (SIETE)';
    if($carreraData['modalidad'] == 'Doctorado') $minimaAprobatoria = 'MÍNIMA APROBATORIA 8 (OCHO)';

    ob_start();
    ?>
    <div class="lateralText">
        Este documento no es válido si presenta raspaduras o enmendaduras.
    </div>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td colspan="3" align="right" style="padding-bottom:12px;"><small>SE-CL-<?php echo substr(date('Y', strtotime($atencionData->col_fecha)), -2); ?></small></td>
        </tr>
        <tr>
            <td width="18%" valign="top" align="center" style="padding-top:15px;"><img width="86" src="<?php echo getLogo('gobernacion'); ?>" /></td>
            <td width="64%" valign="top" align="center">
                <p style="font-size:20px;">GOBIERNO DEL ESTADO DE CHIAPAS<BR/>SECRETARÍA DE EDUCACIÓN</p>
                <p style="font-size:14px;margin: 5px 0;">SUBSECRETARÍA DE EDUCACIÓN ESTATAL<br/>DIRECCIÓN DE EDUCACIÓN SUPERIOR<br/>DEPARTAMENTO DE SERVICIOS ESCOLARES</p>
            </td>
            <td width="18%" valign="top" align="right">
                <br/><br/>FOLIO: <i style="font-style:normal;border-bottom: 2px solid #222;color:#cc0000;"><?php echo $atencionData->col_folio; ?></i>
            </td>
        </tr>
        <tr>
            <td width="18%" valign="top" align="left"></td>
            <td colspan="2" style="font-size:13px;">RVOE: ACUERDO NÚMERO <?php echo $carreraData['revoe']?>&nbsp;&nbsp;&nbsp;&nbsp;VIGENTE: <?php echo strtoupper($atencionData->col_vigente); ?></td>
        </tr>
    </table>

    <table width="100%" border="0" style="font-size: 12px;margin-bottom: 10px;" cellpadding="0" cellspacing="0">
        <tr>
            <td width="18%" valign="bottom" align="left"><img align="left" style="padding:0;margin:0;" width="130" src="<?php echo getImage('foto'); ?>" /></td>
            <td width="82%" valign="top">

                <table width="100%" style="margin: 10px 0;">
                    <?php if($atencionData->col_duplicado == 1) {?>
                        <tr><td align="center" style="padding-bottom: 5px;"><h2 style="text-align: center;font-size:20px;">DUPLICADO</h2></td></tr>
                    <?php }else{ ?>
                        <!-- <tr><td align="center" style="padding-bottom: 5px;"><h2 style="text-align: center;font-size:20px;">TOTAL</h2></tr> -->
                    <?php } ?>
                    <tr>
                        <td align="center">
                            <h2 style="text-align: center;font-size:20px;"><?php echo strtoupper($nombreInstituto); ?></h2>
                        </td>
                    </tr>
                </table>
                <table width="100%">
                    <tr>
                        <td align="center" style="font-size:12px;">
                        RÉGIMEN: PARTICULAR&nbsp;&nbsp;
                        TURNO: <?php echo $turno; ?>&nbsp;&nbsp;
                        MODALIDAD: <?php echo strtoupper(fixEncode($carreraDataModalidad)); ?>&nbsp;&nbsp;
                        CLAVE: <?php echo $claveInstitulo; ?>
                        </td>
                    </tr>
                </table>
                <table width="100%" style="margin: 0;">
                    <tr>
                        <td align="justify" style="font-size:14px;line-height: 150%;">
                            <p>CERTIFICA QUE EL(LA) C. <u>&nbsp;&nbsp;<?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, false, true);?>&nbsp;&nbsp;</u> CON No. DE CONTROL <u>&nbsp;&nbsp;<?php echo $alumnodData->col_control; ?>&nbsp;&nbsp;</u> ACREDITÓ LAS MATERIAS QUE INTEGRAN
                            EL PLAN DE ESTUDIOS DE LA <u>&nbsp;&nbsp;<?php echo mb_strtoupper(fixEncode($carreraData['nombre'])); ?>&nbsp;&nbsp;</u> EN EL PERIODO DE <u>&nbsp;&nbsp;<?php echo strtoupper(getMes(date('F', strtotime($periodoDataInicio->col_fecha_inicio)))); ?></u> DE <u><?php echo date('Y', strtotime($periodoDataInicio->col_fecha_inicio)); ?>&nbsp;&nbsp;</u> A
                            <u><?php echo strtoupper(getMes(date('F', strtotime($periodoDataFin->col_fecha_fin)))); ?></u> DE <u><?php echo date('Y', strtotime($periodoDataFin->col_fecha_fin)); ?></u> CON LOS RESULTADOS QUE A CONTINUACIÓN SE ANOTÁN:</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

        <?php
            $query = "SELECT p.col_id, p.col_grado, p.col_plan_estudios FROM tbl_alumnos_taxonomia t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_alumnoid='".$alumnodData->col_id."' ORDER BY p.col_grado";
            $sth = $db->prepare($query);
            $sth->execute();
            $periodos = $sth->fetchAll();
            $i = 0;
            foreach($periodos as $periodo) {
                $query = "SELECT * FROM tbl_calificaciones WHERE col_cf!='' AND col_periodoid='".$periodo['col_id']."' AND col_alumnoid='".$atencionData->col_alumnoid."'";
                $sth = $db->prepare($query);
                $sth->execute();
                if($sth->rowCount() == 0) continue;
                $calis = $sth->fetchAll();

                ?>
                <div style="width:50%;float:left;">
                    <table width="400" class="bordered">
                        <thead>
                            <tr>
                                <th rowspan="2"><?php echo strtoupper(getLetraGrado($periodo['col_grado'])); ?> <?php echo $carreraMod; ?></th>
                                <th colspan="2" align="center">CALIFICACIÓN</th>
                                <th rowspan="2" align="center"></th>
                            </tr>
                            <tr>

                                <th style="text-align: center;">CIFRA</th>
                                <th style="text-align: center;">LETRA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $mc = 0;
                            $sc = 0;
                            unset($matHTMLarr);
                            foreach($calis as $c){
                                if(in_array(strtoupper(substr($c['col_materia_clave'], 0, 2)), array('AC', 'TL', 'TR', 'CL'))) continue;
                                $materiaData = getMateria('col_clave', $c['col_materia_clave'], $db, $periodo['col_id']);
                                $laCalificacion = $c['col_cf'];
                                $anotacion = '';
                                if(intval($c['col_ext']) > 0) {
                                    $laCalificacion = $c['col_ext'];
                                    $anotacion = 'EXT';
                                }
                                if(intval($c['col_ts']) > 0) {
                                    $laCalificacion = $c['col_ts'];
                                    $anotacion = 'TS';
                                }
                                if(strpos($laCalificacion, '.') !== false) {
                                    $splitCalificacion = explode('.', $laCalificacion);
                                    if(intval($splitCalificacion[1]) > 0){
                                        $laCalificacion = $splitCalificacion[0].'.'.substr($splitCalificacion[1], 0, 1);
                                    }else{
                                        $laCalificacion = intval($laCalificacion);
                                    }
                                }else{
                                    $laCalificacion = intval($laCalificacion);
                                }
                                ob_start();
                                ?>
                                <tr class="heightCali">
                                    <td valign="top" class="noborder"><?php echo fixEncode($materiaData->col_nombre, true, true); ?></td>
                                    <td valign="top" style="width: 70px;" class="bordersides" align="center"><?php echo $laCalificacion; ?></td>
                                    <td valign="top" style="width: 70px;" class="bordersides" align="center"><?php echo strtoupper(is_numeric($laCalificacion)?numerosaletras($laCalificacion):''); ?></td>
                                    <td valign="top" style="width: 60px;" class="bordersides" align="center"><?php echo $anotacion; ?></td>
                                </tr>
                                <?php
                                $matHTML = ob_get_contents();
                                ob_end_clean();
                                // $matHTMLarr[fixEncode($materiaData->col_nombre)] = $matHTML;
                                $matHTMLarr[$materiaData->col_clave] = $matHTML;
                                $mc++;
                                $sc = $laCalificacion + $sc;
                                if($laCalificacion >= 7) {
                                    $creditosGanados = $creditosGanados + $materiaData->col_creditos;
                                }
                            }
                            ksort($matHTMLarr);
                            foreach($matHTMLarr as $item_matHTMLarr) {
                                echo $item_matHTMLarr;
                            }
                            $sc = $sc / $mc;
                            $promGeneral = $sc + $promGeneral;
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
                $i++;
            }
            if($i < 8) {
                for($p = $i; $p < 8; $p++) {
                    ?>
                        <div style="width:50%;float:left;">
                            <table width="400" class="bordered">
                                <thead>
                                    <tr>
                                        <th rowspan="2"><?php echo strtoupper(getLetraGrado(($p+1))); ?> <?php echo $carreraMod; ?></th>
                                        <th colspan="2" align="center">CALIFICACIÓN</th>
                                        <th rowspan="2" align="center"></th>
                                    </tr>
                                    <tr>
                                        <th style="text-align: center;">CIFRA</th>
                                        <th style="text-align: center;">LETRA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td  class="crossed"></td>
                                        <td style="width: 70px;" class="bordersides" align="center"></td>
                                        <td style="width: 70px;" class="bordersides" align="center"></td>
                                        <td style="width: 60px;" class="bordersides" align="center"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php
                }
            }
        ?>


    <?php
    $promGeneral = explode('.', ($promGeneral / $i));
    $promGeneral = $promGeneral[0].'.'.substr($promGeneral[1], 0, 1);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaDirector'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaRector'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $rectorData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaServiciosEcolares'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $escolaresData = $sth->fetch(PDO::FETCH_OBJ);

    $jefeServiciosEscolaresFirma = 'JEFE DEL DEPARTAMENTO DE SERVICIOS ESCOLARES';
    if(strtoupper($escolaresData->col_genero) == 'M') $jefeServiciosEscolaresFirma = 'JEFA DEL DEPARTAMENTO DE SERVICIOS ESCOLARES';

    $directorEducacionFirmaTitulo = 'DIRECTOR DE EDUCACIÓN SUPERIOR';
    if(strtoupper($extraData['firmaDirectorEducacion']) == 'M') $directorEducacionFirmaTitulo = 'DIRECTORA DE EDUCACIÓN SUPERIOR';

    ?>
    <table style="width:380px;" class="bordered" align="center">
        <tr>
            <td width="50%" align="center">
                <b>PROMEDIO</b>
            </td>
            <td width="50%" align="center">
            <?php echo $promGeneral; ?>
            </td>
        </tr>
    </table>

    <pagebreak>
    <br/><br/>
    <p style="line-height: 170%;">LA ESCALA DE CALIFICACIONES ES DE 6 A 10, CONSIDERANDO COMO <?php echo $minimaAprobatoria; ?>, ESTE CERTIFICADO AMPARA <?php echo fixEncode($atencionData->col_ampara); ?> MATERIAS DEL PLAN DE ESTUDIOS VIGENTES Y EN CUMPLIMIENTO A LAS PRESCRIPCIONES LEGALES, SE EXTIENDE EN TUXTLA GUTIÉRREZ, CHIAPAS A LOS <?php echo strtoupper(numerosaletras(date('d', strtotime($atencionData->col_fecha)))); ?> DÍAS DEL MES DE <?php echo strtoupper((getMes(date('F', strtotime($atencionData->col_fecha))))); ?> DEL AÑO DOS MIL <?php echo strtoupper(numerosaletras(substr(date('Y', strtotime($atencionData->col_fecha)), -2))); ?>.</p>
    <br/><br/><br/><br/><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="10%"></td>
            <td align="center" valign="top" width="35%" style="text-transform: uppercase;">DIRECTOR DE LA FACULTAD</td>
            <td width="10%"></td>
            <td align="center" valign="top" width="35%" style="text-transform: uppercase;">RECTOR DE LA FACULTAD</td>
            <td width="10%"></td>
        </tr>
    </table>
    <br/><br/><br/><br/><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="5%"></td>
            <td align="center" valign="top" width="40%" class="firma_up" style="font-size:12px;text-transform: uppercase;"><?php echo fixEncode($directoraData->col_titulo.' '.$directoraData->col_firstname.' '.$directoraData->col_lastname, true); ?></td>
            <td width="10%"></td>
            <td align="center" valign="top" width="40%" class="firma_up" style="font-size:12px;text-transform: uppercase;"><?php echo fixEncode($rectorData->col_titulo.' '.$rectorData->col_firstname.' '.$rectorData->col_lastname, true); ?></td>
            <td width="5%"></td>
        </tr>
    </table>
    <br/><br/><br/><br/><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="5%"></td>
            <td align="center" valign="top" width="40%" style="text-transform: uppercase;"><?php echo $jefeServiciosEscolaresFirma; ?></td>
            <td width="10%"></td>
            <td align="center" valign="top" width="40%" style="text-transform: uppercase;"><?php echo $directorEducacionFirmaTitulo; ?></td>
            <td width="5%"></td>
        </tr>
    </table>
    <br/><br/><br/><br/><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="5%"></td>
            <td align="center" valign="top" width="40%" class="firma_up" style="font-size:12px;text-transform: uppercase;"><?php echo fixEncode($escolaresData->col_titulo.' '.$escolaresData->col_firstname.' '.$escolaresData->col_lastname, true); ?></td>
            <td width="10%"></td>
            <td align="center" valign="top" width="40%" class="firma_up" style="font-size:12px;text-transform: uppercase;"><?php echo fixEncode($atencionData->col_director_educacion, true); ?></td>
            <td width="5%"></td>
        </tr>
    </table>
    <br/><br/><br/><br/><br/><br/><br/>
    <div style="width: 220px; text-align: center; float: left;border:1px solid #333;border-radius: 10px;">
                <p align="center" style="border-bottom: 1px solid #333;">REGISTRADO EN EL DEPARTAMENTO DE SERVICIOS ESCOLARES</p>
                <table border="0" width="100%" style="padding: 0 10px;">
                    <tr>
                        <td align="left" valign="top" width="30%">No.</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;"></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" width="30%">LIBRO</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;"></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" width="30%">FOJA</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;"></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" width="30%">FECHA</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;"></td>
                    </tr>
                </table>
                <br/>
                <p align="center" style="border-bottom: 1px solid #333;border-top: 1px solid #333;">COTEJO</p>
                <br/>
                <p align="center" style="font-size: 10px;"><?php echo fixEncode($extraData['firmaCotejo'], true, true); ?></p>
                <p align="center" style="border-bottom: 1px solid #333;border-top: 1px solid #333;"><?php echo fixEncode($extraData['tituloJefeOficina'], true, true); ?></p>
                <br/>
                <p align="center" style="font-size: 10px;"><?php echo fixEncode($atencionData->col_jefe_oficina, true, true); ?></p>


        </div>
        <div style="width: 400px; text-align: center;float: right;">
                <p>CON FUNDAMENTO EN EL ARTÍCULO 29; FRACCIÓN X DE LA LEY ORGÁNICA DE LA ADMINISTRACIÓN PÚBLICA DEL ESTADO DE CHIAPAS Y 38; FRACCIÓN IX DEL REGLAMENTO INTERIOR DE LA SECRETARÍA GENERAL DE GOBIERNO.</p>
                <p>Se LEGALIZA, previo cotejo con la existente en el control respectivo, la firma que antecede corresponde al Director de Educación Superior.</p>
                <p style="height: 20px;border-bottom: 1px solid #222;"><?php echo substr(fixEncode($atencionData->col_observaciones), 0, 70); ?></p>
                <p style="height: 20px;border-bottom: 1px solid #222;"><?php echo substr(fixEncode($atencionData->col_observaciones), 70); ?></p>
                <p>Tuxtla Gutiérrez, Chiapas, A:<u><?php for($x = 0; $x < 70; $x++){ echo '&nbsp;';} ?></u></p>
                <center><?php echo fixEncode($extraData['tituloSecretario'], true, true); ?></center>
                <br/><br/>
                <center>_____________________________________________________</center>
                <center><?php echo fixEncode($atencionData->col_subsecretario, true, true); ?></center>
        </div>
    <?php
    $html = ob_get_contents();
    ob_end_clean();
    // {creditos}


    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    // $mpdf=new mPDF('c','Legal', '','', 15, 15, 10, 10);
    $m = getMargins($db, false, true);
    $mpdf=new mPDF('c','LEGAL', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader('');
    // $mpdf->SetHTMLFooter(pdfFooter());
    $mpdf->SetHTMLFooter('');
    $mpdf->normalLineheight = 1.53;

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('12px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('CertificadoTotal.pdf', $output);
    }else{
        $mpdf->Output('CertificadoTotal.pdf', $output);
    }

    // die();
}

function generarConstanciaSencilla($id, $db, $output = 'I') {
    global $totalCreditos;
    global $nombreInstituto, $claveInstitulo, $_indicacionInstituto;

    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);
    $elGeneroAlumno = (strtoupper($alumnodData->col_genero) == 'H'?'el':'la');

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_depto="11" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);

    if($userData->col_genero == 'M'){
        $nombreDepto = 'Encargada de '.$dataDepto->col_nombre;
    }else{
        $nombreDepto = 'Encargado de '.$dataDepto->col_nombre;
    }
    //$nombreDepto = ($alumnodData->col_genero == 'M'?'RECTORA':'RECTOR');

    $elGenero = ($userData->col_genero == 'M'?'la':'el');

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);
    // $periodoData = getPeriodoPorGrado($alumnodData->col_id, $extraData['periodoFin'], $db);


    $laModalidad = $carreraData['modalidad'];
    $tipoCurso = 'semestre';
    if($carreraData['modalidad'] == 'Cuatrimestral'){
        $tipoCurso = 'cuatrimestre';
    }
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
        $_laModalidad = 'la';
    }

    $_laModalidad = 'la';
    if($carreraData['modalidad'] == 'Doctorado') {
        $_laModalidad = 'el';
    }

    $carreraDataModalidad = 'Escolarizada';
    if($carreraData['modalidad'] == 'Cuatrimestral') $carreraDataModalidad = 'Mixta';


    $vinicio = $atencionData->col_vacaciones_inicio;
    $vfin = $atencionData->col_vacaciones_fin;
    $diaLetras = 'a un día';
    if(intval(date('j', strtotime($atencionData->col_fecha))) > 1) {
        $diaLetras = 'a los '.strtolower(numerosaletras(date('d', strtotime($atencionData->col_fecha)))).' días';
    }

    if($carreraData['modalidad_periodo'] == 'ldsem' || $carreraData['modalidad_periodo'] == 'ldcua') {
        $creditosPorModalidad = $totalCreditos['licenciatura'];
    }
    if($carreraData['modalidad_periodo'] == 'docto') {
        $creditosPorModalidad = $totalCreditos['doctorado'];
    }
    if($carreraData['modalidad_periodo'] == 'master') {
        $creditosPorModalidad = $totalCreditos['maestria'];
    }


    ob_start();
    ?>
    <br/>
    <table width="100%">
        <tr>
            <td align="right"><b>Asunto: Constancia de Estudios</b></td>
        </tr>
    </table>
    <br/><br/>
    <h3>A QUIEN CORRESPONDA</h3><br/><br/>
    <p style="text-align: center;">La suscrita, <?php echo fixEncode($nombreDepto); ?> <?php echo $_indicacionInstituto; ?> <?php echo $nombreInstituto; ?>,
    incorporado al Sistema Estatal. Modalidad: <?php echo $carreraDataModalidad; ?>. Clave <?php echo $claveInstitulo; ?>:</p>
    <br/>
    <h3 style="text-align:center;">H  A  C  E&nbsp;&nbsp;&nbsp;&nbsp;C  O  N  S  T  A  R</h3><br/>

        <p style="text-align: justify;">Que <?php echo $elGeneroAlumno; ?> C. <?php echo trim(fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos));?>, con
        número de matrícula <?php echo fixEncode($alumnodData->col_control);?> está cursando
        el <?php echo strtolower(numerosaletrasSemestre(fixEncode($periodoData->col_grado)));?> <?php echo $tipoCurso; ?> en el periodo de
        <?php echo strtoupper($periodoData->col_nombre); ?>, de <?php echo $_laModalidad; ?> <?php echo strtoupper($carreraData['nombre']); ?>,
        (con RVOE <?php echo strtoupper($carreraData['revoe']); ?>. De fecha <?php echo strtolower($carreraData['vigencia_revoe']); ?>), de la
        generación <?php echo strtoupper($alumnodData->col_generacion_start); ?> – <?php echo strtoupper($alumnodData->col_generacion_end); ?>.
        Teniendo un periodo vacacional del <?php echo date('d', strtotime($vinicio)); ?> de <?php echo getMes(date('F', strtotime($vinicio))); ?> de
        <?php echo date('Y', strtotime($vinicio)); ?> al <?php echo date('d', strtotime($vfin)); ?> de <?php echo getMes(date('F', strtotime($vfin))); ?> de
        <?php echo date('Y', strtotime($vfin)); ?>.</p>


    <p style="text-align: justify;">A petición  de la  parte interesada se extiende la presente, <?php echo $diaLetras; ?> del mes
    de <?php echo strtolower(getMes(date('F', strtotime($atencionData->col_fecha)))); ?> del año dos
    mil <?php echo strtolower(numerosaletras(substr(date('Y', strtotime($atencionData->col_fecha)), -2))); ?>, en la Ciudad de Tuxtla Gutiérrez, Chiapas.</p>
    <br/><br/><br/>
    <p style="text-align: center;"><b>Atentamente<br/><span class="cursiva">"Por el engrandecimiento del Estado de Chiapas"</span></b></p>
    <br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="30%"></td>
            <td align="center" valign="top" width="40%" class="firma_up" style="text-transform: uppercase;">
            <?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($nombreDepto); ?></small></td>
            <td width="30%"></td>
        </tr>
    </table>
    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db, true);
    $mpdf=new mPDF('s','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);


    $mpdf->SetHTMLHeader('');
    $mpdf->SetHTMLFooter('');
    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('14px'), 1);
    $mpdf->WriteHTML($html, 2);

    if($output == 'S') {
        return $mpdf->Output('constancia-sencilla.pdf', $output);
    }else{
        $mpdf->Output('constancia-sencilla.pdf', $output);
    }

    // die();
}


function generarConstanciaTerminoSemestre($id, $db, $output = 'I') {
    global $totalCreditos;
    global $nombreInstituto, $claveInstitulo, $_indicacionInstituto;

    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);
    $elGeneroAlumno = (strtoupper($alumnodData->col_genero) == 'H'?'el':'la');

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_depto="11" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);

    if($userData->col_genero == 'M'){
        $nombreDepto = 'Encargada de '.$dataDepto->col_nombre;
    }else{
        $nombreDepto = 'Encargado de '.$dataDepto->col_nombre;
    }
    //$nombreDepto = ($alumnodData->col_genero == 'M'?'RECTORA':'RECTOR');

    $elGenero = ($userData->col_genero == 'M'?'la':'el');

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    //$periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);
    $periodoData = getUltimoPeriodoAcreditado($alumnodData->col_id, $db);
    //$periodoData = getPeriodoPorGrado($alumnodData->col_id, $alumnodData->col_periodoid, $db);


    $laModalidad = $carreraData['modalidad'];
    $tipoCurso = 'semestre';
    if($carreraData['modalidad'] == 'Cuatrimestral'){
        $tipoCurso = 'cuatrimestre';
    }
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
        $_laModalidad = 'la';
    }

    $_laModalidad = 'la';
    if($carreraData['modalidad'] == 'Doctorado') {
        $_laModalidad = 'el';
    }

    $carreraDataModalidad = 'Escolarizada';
    if($carreraData['modalidad'] == 'Cuatrimestral') $carreraDataModalidad = 'Mixta';


    $vinicio = $atencionData->col_vacaciones_inicio;
    $vfin = $atencionData->col_vacaciones_fin;
    $diaLetras = 'a un día';
    if(intval(date('j', strtotime($atencionData->col_fecha))) > 1) {
        $diaLetras = 'a los '.strtolower(numerosaletras(date('d', strtotime($atencionData->col_fecha)))).' días';
    }

    if($carreraData['modalidad_periodo'] == 'ldsem' || $carreraData['modalidad_periodo'] == 'ldcua') {
        $creditosPorModalidad = $totalCreditos['licenciatura'];
    }
    if($carreraData['modalidad_periodo'] == 'docto') {
        $creditosPorModalidad = $totalCreditos['doctorado'];
    }
    if($carreraData['modalidad_periodo'] == 'master') {
        $creditosPorModalidad = $totalCreditos['maestria'];
    }

    $creditosObtenidos = getCreditosHastaGrado($alumnodData->col_id, $periodoData->col_grado, $db);

    ob_start();
    ?>
    <br/>
    <table width="100%">
        <tr>
            <td align="right"><b>Asunto: Constancia de Estudios</b></td>
        </tr>
    </table>
    <br/><br/>
    <h3>A QUIEN CORRESPONDA</h3><br/><br/>
    <p style="text-align: center;">La suscrita, <?php echo fixEncode($nombreDepto); ?> <?php echo $_indicacionInstituto; ?> <?php echo $nombreInstituto; ?>,
    incorporado al Sistema Estatal. Modalidad: <?php echo $carreraDataModalidad; ?>. Clave <?php echo $claveInstitulo; ?>:</p>
    <br/>
    <h3 style="text-align:center;">H  A  C  E&nbsp;&nbsp;&nbsp;&nbsp;C  O  N  S  T  A  R</h3><br/>

        <p style="text-align: justify;">Que <?php echo $elGeneroAlumno; ?> C. <?php echo trim(fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos));?>, con
        número de matrícula <?php echo fixEncode($alumnodData->col_control);?> concluyo el <?php echo strtolower(numerosaletrasSemestre(fixEncode($periodoData->col_grado)));?> <?php echo $tipoCurso; ?> en el periodo de
        <?php echo strtoupper($periodoData->col_nombre); ?>, de <?php echo $_laModalidad; ?> <?php echo strtoupper($carreraData['nombre']); ?>,
        (con RVOE <?php echo strtoupper($carreraData['revoe']); ?>. De fecha <?php echo ($carreraData['vigencia_revoe']); ?>), de la
        generación <?php echo strtoupper($alumnodData->col_generacion_start); ?> – <?php echo strtoupper($alumnodData->col_generacion_end); ?>.
        Obteniendo <?php echo $creditosObtenidos; ?> créditos de un total de <?php echo $creditosPorModalidad; ?> correspondientes al plan de estudios.</p>

    <p style="text-align: justify;">A petición  de la  parte interesada se extiende la presente, <?php echo $diaLetras; ?> del mes
    de <?php echo strtolower(getMes(date('F', strtotime($atencionData->col_fecha)))); ?> del año dos
    mil <?php echo strtolower(numerosaletras(substr(date('Y', strtotime($atencionData->col_fecha)), -2))); ?>, en la Ciudad de Tuxtla Gutiérrez, Chiapas.</p>
    <br/><br/><br/>
    <p style="text-align: center;"><b>Atentamente<br/><span class="cursiva">"Por el engrandecimiento del Estado de Chiapas"</span></b></p>
    <br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="30%"></td>
            <td align="center" valign="top" width="40%" class="firma_up" style="text-transform: uppercase;">
            <?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($nombreDepto); ?></small></td>
            <td width="30%"></td>
        </tr>
    </table>
    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db, true);
    $mpdf=new mPDF('s','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);


    $mpdf->SetHTMLHeader('');
    $mpdf->SetHTMLFooter('');
    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('14px'), 1);
    $mpdf->WriteHTML($html, 2);

    if($output == 'S') {
        return $mpdf->Output('constancia-estudios-termino-semestre.pdf', $output);
    }else{
        $mpdf->Output('constancia-estudios-termino-semestre.pdf', $output);
    }

    // die();
}

function getLetraGrado($grado) {
    switch($grado){
        case 1: $grado = 'Primer'; break;
        case 2: $grado = 'Segundo'; break;
        case 3: $grado = 'Tercer'; break;
        case 4: $grado = 'Cuarto'; break;
        case 5: $grado = 'Quinto'; break;
        case 6: $grado = 'Sexto'; break;
        case 7: $grado = 'Septimo'; break;
        case 8: $grado = 'Octavo'; break;
    }

    return $grado;
}

function getSuffixGrado($grado) {
    switch($grado){
        case 1: $grado = '1er'; break;
        case 2: $grado = '2o'; break;
        case 3: $grado = '3o'; break;
        case 4: $grado = '4o'; break;
        case 5: $grado = '5o'; break;
        case 6: $grado = '6o'; break;
        case 7: $grado = '7o'; break;
        case 8: $grado = '8o'; break;
    }

    return $grado;
}

function generarConstanciaCalificaciones($id, $db, $output = 'I') {
    global $nombreInstituto, $claveInstitulo, $_indicacionInstituto;

    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $generoElLA = 'el';
    if($alumnodData->col_genero == 'M') $generoElLA = 'la';

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_depto="11" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);
    $limiteCreditos = $carreraData['totalCreditos'];

    $laModalidad = $carreraData['modalidad'];
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
    }

    //if($carreraData['modalidad'] == 'Semestral')
    $carreraMod = 'SEMESTRE';
    if($carreraData['modalidad'] == 'Cuatrimestral') $carreraMod = 'CUATRIMESTRE';

    //if($carreraData['modalidad'] == 'Semestral')
    $tipoCurso = 'SEMESTRE';
    if($carreraData['modalidad'] == 'Cuatrimestral') $tipoCurso = 'CUATRIMESTRE';

    $carreraLaEl = 'la';
    if($carreraData['modalidad'] == 'Doctorado') {
        $carreraLaEl = 'el';
    }

    if($extraData['tipoTextoConstanciaCalificaciones'] == 0) {
        //se encuentra cursando el octavo semestre en el periodo de febrero 2020 – julio 2020
        //se encuentra cursando el OCTAVO SEMESTRE
        $textoTipoDocumento = "se encuentra cursando el ".strtoupper(numerosaletrasSemestre($periodoData->col_grado)).' '.$tipoCurso.' de';
    }

    if($extraData['tipoTextoConstanciaCalificaciones'] == 99) {
        $textoTipoDocumento = 'concluyo';
    }

    if($extraData['tipoTextoConstanciaCalificaciones'] > 0 && $extraData['tipoTextoConstanciaCalificaciones'] < 9) {
        //concluyo el séptimo semestre en el periodo de agosto 2019 – enero 2020
        $textoTipoDocumento = "concluyo el ".strtoupper(numerosaletrasSemestre($extraData['tipoTextoConstanciaCalificaciones'])).' '.$tipoCurso.' de';
    }

    $promGeneral = 0;
    $creditosGanados = 0;
    $creditosTotal = 0;
    $count = 0;
    ob_start();
    ?>
    <div class="forzarCarta">
    <p style="text-align:right;">
        <b>ASUNTO: Constancia de estudios.</b>
    </p>
    <p>
        A QUIEN CORRESPONDA<br/>
        PRESENTE
    </p>

    <p style="text-align: center;">
        <b>La suscrita, encargada de <?php echo $nombreDepto; ?> <?php echo $_indicacionInstituto; ?> <?php echo $nombreInstituto; ?>, incorporado al Sistema Estatal, clave <?php echo $claveInstitulo; ?>:</b></p>
    <p style="text-align: center;"><b>H  A  C  E&nbsp;&nbsp;&nbsp;&nbsp;C  O  N  S  T  A  R</b></p>
    <p>Que <?php echo $generoElLA; ?> C. <u><?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos);?></u>, con número de matrícula <?php echo fixEncode($alumnodData->col_control);?> <?php echo $textoTipoDocumento; ?> <?php echo $carreraLaEl; ?> <?php echo fixEncode($carreraData['nombre'], true); ?>, de la
    generación <?php echo strtoupper($alumnodData->col_generacion_start); ?> – <?php echo strtoupper($alumnodData->col_generacion_end); ?>, (con número de acuerdo <?php echo strtoupper(fixEncode($carreraData['revoe'])); ?>.
    A partir del <?php echo strtolower($carreraData['vigencia_revoe']); ?>).
    Acreditando {creditos} créditos de un total de {limiteCreditos} del plan de estudios. Obteniendo las siguientes calificaciones.</p>

        <?php

        $data = bloquesCalificaciones(1, 2, $atencionData->col_alumnoid, $carreraMod, $db);

        echo $data['html'];
        // echo $data['promedios'];
        $promGeneral = $promGeneral + $data['promedio'];
        $creditosGanados = $creditosGanados + $data['creditos'];
        $creditosTotal = $creditosTotal + $data['creditosMateria1'] + $data['creditosMateria2'];
        $count = $count + $data['count'];

        $data = bloquesCalificaciones(3, 4, $atencionData->col_alumnoid, $carreraMod, $db);
        echo $data['html'];
        // echo $data['promedios'];
        $promGeneral = $promGeneral + $data['promedio'];
        $creditosGanados = $creditosGanados + $data['creditos'];
        $creditosTotal = $creditosTotal + $data['creditosMateria1'] + $data['creditosMateria2'];
        $count = $count + $data['count'];

        if($carreraData['posgrado'] == false) {
            $data = bloquesCalificaciones(5, 6, $atencionData->col_alumnoid, $carreraMod, $db);
            echo $data['html'];
            // echo $data['promedios'];
            $promGeneral = $promGeneral + $data['promedio'];
            $creditosGanados = $creditosGanados + $data['creditos'];
            $creditosTotal = $creditosTotal + $data['creditosMateria1'] + $data['creditosMateria2'];
            $count = $count + $data['count'];

            $data = bloquesCalificaciones(7, 8, $atencionData->col_alumnoid, $carreraMod, $db);
            echo $data['html'];
            // echo $data['promedios'];
            $promGeneral = $promGeneral + $data['promedio'];
            $creditosGanados = $creditosGanados + $data['creditos'];
            $creditosTotal = $creditosTotal + $data['creditosMateria1'] + $data['creditosMateria2'];
            $count = $count + $data['count'];

        }

        ?>


    <?php
    $promGeneral = formatoPromedio($promGeneral / $count);
    //if(intval($promGeneral) == 10) $promGeneral = 10;
    $diaLetras = 'a un día';
    if(intval(date('j', strtotime($atencionData->col_fecha))) > 1) {
        $diaLetras = 'a los '.strtolower(numerosaletras(date('d', strtotime($atencionData->col_fecha)))).' días';
    }
    ?>
    <div style="clear:both;"></div>
    <p><b>Promedio General:</b> <?php echo $promGeneral; ?></p>
    <?php if($carreraData['posgrado'] == true) {
        echo '<div style="width:100%;height:10px;"></div>';
    }
    ?>
    <p style="text-align: center;">A petición de la parte interesada y para los usos legales que mejor convenga, se extiende la presente, <?php echo $diaLetras; ?> de <?php echo strtolower(getMes(date('F', strtotime($atencionData->col_fecha)))); ?>
    de <?php echo strtolower(numerosaletras(date('Y', strtotime($atencionData->col_fecha)))); ?>, en la ciudad de Tuxtla Gutiérrez, Chiapas.</p>
    <br/>
    <table border="0" width="100%">
        <tr>
            <td width="30%"></td>
            <td align="center" valign="top" width="40%" class="firma_up" style="text-transform: uppercase;"><?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($nombreDepto, true);?></small></td>
            <td width="30%"></td>
        </tr>
    </table>
    </div>
    <?php
    $html = ob_get_contents();
    ob_end_clean();
    // {creditos}
    $html = str_replace('{creditos}', $creditosGanados, $html);
    // $html = str_replace('{limiteCreditos}', $creditosTotal, $html);
    $html = str_replace('{limiteCreditos}', $limiteCreditos, $html);




    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db, true);
    $mpdf=new mPDF('s','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);
    $mpdf->shrink_tables_to_fit = 1;
    $mpdf->SetHTMLHeader('');
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('12px', '12px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('ConstanciaCalificaciones.pdf', $output);
    }else{
        $mpdf->Output('ConstanciaCalificaciones.pdf', $output);
    }

    // die();
}

function bloquesCalificaciones($p1, $p2, $alumnoid, $modalidad, $db, $totalMaterias = 7) {

    $periodo = getPeriodoPorGrado($alumnoid, $p1, $db);
    $bloque1 = bloqueCalificaciones($periodo, $p1, $alumnoid, $modalidad, $totalMaterias, $db);

    $periodo = getPeriodoPorGrado($alumnoid, $p2, $db);
    $bloque2 = bloqueCalificaciones($periodo, $p2, $alumnoid, $modalidad, $totalMaterias, $db);

    ob_start();
    echo base64_decode($bloque1['html']);
    echo base64_decode($bloque2['html']);
    $filaHTML = ob_get_contents();
    ob_end_clean();

    foreach($bloque1['data'] as $leftData) {
        $filaDataLenght = $leftData['lenght'];
        if($bloque2['data'][$leftData['row']]['lenght'] > $leftData['lenght']) $filaDataLenght = $bloque2['data'][$leftData['row']]['lenght'];
        $filaHTML = str_replace('%hcol'.$leftData['row'].'%', heightSize($filaDataLenght), $filaHTML);
    }
    return Array(
        'html' => $filaHTML,
        'dataBloque1' => $bloque1,
        'dataBloque2' => $bloque2,
        'promedio' => number_format(($bloque1['promedio'] + $bloque2['promedio']), 2),
        'promedios' => $bloque1['promedio'] .'---'. $bloque2['promedio'],
        'creditos' => ($bloque1['creditos'] + $bloque2['creditos']),
        'creditosMateria1' => ($bloque1['creditosMateria'] > 0?$bloque1['creditosMateria']:0),
        'creditosMateria2' => ($bloque2['creditosMateria'] > 0?$bloque2['creditosMateria']:0),
        'count' => ($bloque1['hasDataCalificaciones'] + $bloque2['hasDataCalificaciones'])
    );

}

function bloqueCalificaciones($periodo, $grado, $alumnoid, $modalidad, $totalMaterias, $db) {
    $hasDataCalificaciones = false;
    $nombrePeriodo = '&nbsp;';
    $arrMaterias = '';
    $creditosGanados = 0;
    $creditosMateria = 0;
    $filaData = '';
    $promGeneral = 0;

    $gradoInfo = $grado.'o '.$modalidad;
    if(intval($periodo->col_id) > 0) {
        $cssTabla = 'tablaCalificaciones';
        $nombrePeriodo = strtoupper($periodo->col_nombre);

        $query = "SELECT * FROM tbl_calificaciones WHERE col_cf!='' AND col_periodoid='".$periodo->col_id."' AND col_alumnoid='".$alumnoid."'";
        $sth = $db->prepare($query);
        $sth->execute();
        if($sth->rowCount()) $hasDataCalificaciones = true;
    }

    if(strtotime('now') < strtotime($periodo->col_fecha_fin)) $hasDataCalificaciones = false;

    ob_start();
    ?>
    <div class="tablasFlotantes">
            <?php
            if($hasDataCalificaciones) {
                $calis = $sth->fetchAll();
                ?>
                <table width="100%" class="bordered tablaCalificaciones">
                <thead>
                    <tr>
                        <th colspan="4" align="center"><?php echo $nombrePeriodo; ?></th>
                    </tr>
                    <tr>
                        <th colspan="4" align="center"><?php echo $gradoInfo; ?></th>
                    </tr>
                    <tr>
                        <th>MATERIA</th>
                        <th class="txtCenter">CREDITOS</th>
                        <th class="txtCenter">CALIFICACIÓN</th>
                        <th class="txtCenter"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $mc = 1;
                    $mx = 0;
                    $sc = 0;
                    unset($arrMaterias);
                    foreach($calis as $c){
                        if(in_array(strtoupper(substr($c['col_materia_clave'], 0, 2)), array('AC', 'TL', 'CL', 'TR'))) continue;
                        $materiaData = getMateria('col_clave', $c['col_materia_clave'], $db, $periodo->col_id);
                        $laCalificacionTipo = '';
                        $laCalificacion = $c['col_cf'];
                        if(intval($c['col_ext']) > 0) {
                            $laCalificacion = number_format($c['col_ext'], 0);
                            $laCalificacionTipo = 'EXT';
                        }
                        if(intval($c['col_ts']) > 0) {
                            $laCalificacion = number_format($c['col_ts'], 0);
                            $laCalificacionTipo = 'TS';
                        }

                        ob_start();
                        ?>
                        <tr>
                            <td valign="top" class="%hcolx% noborder"><?php echo fixEncode($materiaData->col_nombre); ?></td>
                            <td valign="top" class="col70 %hcolx% bordersides txtCenter" ><?php echo $materiaData->col_creditos; ?></td>
                            <td valign="top" class="col50 %hcolx% bordersides txtCenter" ><?php echo $laCalificacion; ?></td>
                            <td valign="top" class="col50 %hcolx% bordersides txtCenter" ></td>
                        </tr>
                        <?php
                        $htmlMateria = ob_get_contents();
                        ob_end_clean();
                        $arrMaterias[fixEncode($materiaData->col_clave)]['html'] = $htmlMateria;
                        $arrMaterias[fixEncode($materiaData->col_clave)]['nombre'] = $materiaData->col_nombre;

                        $sc = $laCalificacion + $sc;
                        if($laCalificacion >= 7) {
                            $creditosGanados = $creditosGanados + $materiaData->col_creditos;
                        }
                        $creditosMateria = $creditosMateria + $materiaData->col_creditos;

                        $mx++;

                    }

                    if($mx < 7) {
                        for($falto = 0; $falto < (7 - $mx); $falto++){
                            ob_start();
                            ?>
                            <tr>
                                <td valign="top" class="%hcolx% noborder">&nbsp;</td>
                                <td valign="top" class="col70 %hcolx% bordersides txtCenter" >&nbsp;</td>
                                <td valign="top" class="col50 %hcolx% bordersides txtCenter" >&nbsp;</td>
                                <td valign="top" class="col50 %hcolx% bordersides txtCenter" >&nbsp;</td>
                            </tr>
                            <?php
                            $htmlMateria = ob_get_contents();
                            ob_end_clean();
                            $arrMaterias['ZZZ']['html'] = $htmlMateria;
                            $arrMaterias['ZZZ']['nombre'] = "";
                        }
                    }

                    ksort($arrMaterias);
                    foreach($arrMaterias as $item_arrMaterias){
                        echo str_replace('x%', $mc.'%', $item_arrMaterias['html']);

                        if(!is_array($filaData[$mc])){
                            $filaData[$mc] = array('row' => $mc, 'lenght' => strlen(fixEncode($item_arrMaterias['nombre'])), 'text' => fixEncode($item_arrMaterias['nombre']));
                        }else{
                            if($filaData[$mc]['lenght'] < strlen(fixEncode($item_arrMaterias['nombre']))) {
                                $filaData[$mc] = array('row' => $mc, 'lenght' => strlen(fixEncode($item_arrMaterias['nombre'])), 'text' => fixEncode($item_arrMaterias['nombre']));
                            }
                        }
                        $mc++;
                    }
                    $sc = $sc / ($mc - 1);
                    $promGeneral = $sc + $promGeneral;
                    ?>
                </tbody>
                </table>
            <?php
            }else{
                ?>
                <table width="100%" class="no-bordered tablaCalificaciones crossedTable">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                    </tr>
                    <tr>
                        <th>&nbsp;</th>
                    </tr>
                    <tr>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for($n = 1; $n < ($totalMaterias+1); $n++){ ?>
                    <tr>
                        <td class="%hcol<?php echo $n; ?>%"></td>
                    </tr>
                    <?php

                    $filaData[$n] = array('row' => $n, 'lenght' => 0, 'text' => '');
                    } ?>
                </tbody>
                </table>
                <?php
            }
            ?>
        </table>
    </div>
    <?php
    $html = ob_get_contents();
    ob_end_clean();


    return Array('html' => base64_encode($html), 'data' => $filaData, 'promedio' => $promGeneral, 'creditos' => $creditosGanados, 'creditosMateria' => $creditosMateria, 'hasDataCalificaciones' => intval($hasDataCalificaciones));
}


function generarRegistroEscolaridad($id, $db, $output = 'I') {
    global $nombreInstituto, $claveInstitulo;
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_depto="11" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $laModalidad = $carreraData['modalidad'];
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
    }

    ob_start();
    ?>
    <table width="100%">
        <tr>
            <td width="50%" align="left"></td>
            <td width="50%" align="right">FOLIO: <span style="font-weight:bold;color:#cc0000;"><?php echo $atencionData->col_folio; ?></span></td>
        </tr>
        <tr>
            <td width="50%" valign="top" align="left"><img width="190" src="<?php echo getLogo('sep_chiapas_nuevo'); ?>" /></td>
            <td width="50%" valign="top" align="right"><img width="190" src="<?php echo getLogo(); ?>" /></td>
        </tr>
    </table>
    <?php
    $header = ob_get_contents();
    ob_end_clean();

    ob_start();
    echo $header;
    ?>
    <br/>
    <table width="100%">
        <tr>
            <td width="33%" align="left">CLAVE: <?php echo $claveInstitulo; ?></td>
            <td width="34%" align="center">RÉGIMEN: <?php echo $atencionData->col_regimen; ?></td>
            <td width="33%" align="right">TURNO: <span style="font-weight:bold;color:#cc0000;"><?php echo $atencionData->col_folio; ?></span></td>
        </tr>
    </table>
    <br/><br/>
    <table width="100%">
        <tr>
            <td width="30%" valign="top" align="center"><img width="130" src="<?php echo getImage('foto'); ?>" /></td>
            <td width="70%" valign="top" align="center">
                <h2 style="text-align: center;"><b>CARTA DE PASANTE A:</b></h2><br/>
                <h2 style="text-align: center;"><?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos);?></h2>
                <br/><br/>
                <p>En atención a que terminó íntegramente sus estudios conforme al plan vigente de la <u><?php echo $carreraData['nombre']; ?></u></p><br/><br/>
                <p>En el periodo de <u><?php echo fixEncode($periodoData->col_nombre); ?></u>.</p><br/><br/>
                <p>Incorporado al Sistema Educativo Estatal según acuerdo No. <u><?php echo $carreraData['revoe']?></u>. </p><br/><br/>
                <p>En cumplimiento de las disposiciones reglamentarias y para los usos legales que procedan se expide la
                    presente a los <u><?php echo date('d', strtotime($atencionData->col_fecha)); ?></u> días de <u><?php echo getMes(date('F', strtotime($atencionData->col_fecha))); ?></u> de <u><?php echo date('Y', strtotime($atencionData->col_fecha)); ?></u>, en la ciudad de Tuxtla Gutiérrez, Chiapas.</p>.</>
            </td>
        </tr>
    </table>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    ob_start();
    ?>
    <table border="0" width="100%">
        <tr>
            <td width="10%"></td>
            <td align="center" valign="top" width="35%" class="firma_up" style="text-transform: uppercase;"><?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($nombreDepto, true);?></small></td>
            <td width="10%"></td>
            <td align="center" valign="top" width="35%" class="firma_up" style="text-transform: uppercase;"><?php echo fixEncode($directoraData->col_firstname.' '.$directoraData->col_lastname, true); ?><br/><small>DIRECTORA</small></td>
            <td width="10%"></td>
        </tr>
    </table>
    <br/><br/><br/><br/>
    <p style="text-align: center;">5ª Poniente Norte No. 633 Col. Centro  Tuxtla Gutiérrez, Chiapas.</p>
    <?php
    $footer = ob_get_contents();
    ob_end_clean();

    include_once(__DIR__ . '/../src/mpdf/mpdf.php');


    $m = getMargins($db);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader('');
    $mpdf->SetHTMLFooter($footer);

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('15px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('RegistroEscolaridad.pdf', $output);
    }else{
        $mpdf->Output('RegistroEscolaridad.pdf', $output);
    }

    // die();
}

function generarFormatoBuenaConducta($id, $db, $output = 'I') {
    global $nombreInstituto, $claveInstitulo, $_indicacionInstituto;
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);
    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_depto="11" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoDataInicio = getPeriodoPorGrado($alumnodData->col_id, 1, $db);
    // $periodoDataFin = getPeriodoPorGrado($alumnodData->col_id, intval($extraData['periodoFin']), $db);
    //$periodoDataFin = getPeriodoPorGrado($alumnodData->col_id, 0, $db);
    $periodoDataFin = getUltimoPeriodoAcreditado($alumnodData->col_id, $db);
    // print_r($periodoDataFin);exit;

    $periodoInicio = getMes(date('F', strtotime($periodoDataInicio->col_fecha_inicio))).' '.date('Y', strtotime($periodoDataInicio->col_fecha_inicio));
    $periodoFin = getMes(date('F', strtotime($periodoDataFin->col_fecha_fin))).' '.date('Y', strtotime($periodoDataFin->col_fecha_fin));

    $laModalidad = $carreraData['modalidad'];
    $tipoCurso = 'semestre';
    if($carreraData['modalidad'] == 'Cuatrimestral'){
        $tipoCurso = 'cuatrimestre';
    }
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
    }

    $generoAlumno = 'el alumno';
    if(strtolower($alumnodData->col_genero) != 'h') $generoAlumno = 'la alumna';
    $diaLetras = 'a un día';
    if(intval(date('j', strtotime($atencionData->col_fecha))) > 1) {
        $diaLetras = 'a los '.strtolower(numerosaletras(date('d', strtotime($atencionData->col_fecha)))).' días';
    }
    ob_start();
    ?>
    <table width="100%">
        <tr>
            <td align="right"><b>ASUNTO: Carta de Buena Conducta.</b></td>
        </tr>
    </table>
    <br/><br/>
    <h3>A QUIEN CORRESPONDA</h3><br/>
    <p style="text-align: justify;">La suscrita, Responsable de <?php echo fixEncode($nombreDepto); ?> <?php echo $_indicacionInstituto; ?> <?php echo $nombreInstituto; ?> incorporado al Sistema Estatal, clave: <?php echo $claveInstitulo; ?>,
    hace constar que <?php echo $generoAlumno; ?>: <?php echo trim(fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos));?>,
    durante el periodo <?php echo $periodoInicio; ?> - <?php echo $periodoFin; ?> que realizó sus estudios de Licenciatura en esta institución observó:</p>
    <br/><br/>
    <h2 style="text-align: center;">BUENA CONDUCTA</h2><br/><br/>
    <p style="text-align: justify;">A petición de la parte interesada y para los usos legales que mejor convenga, se extiende la presente, <?php echo $diaLetras; ?> del mes de
    <?php echo strtolower(getMes(date('F', strtotime($atencionData->col_fecha)))); ?> del año dos mil <?php echo strtolower(numerosaletras(substr(date('Y', strtotime($atencionData->col_fecha)), -2))); ?>,
    en la Ciudad de Tuxtla Gutiérrez, Chiapas.</p>
    <br/>
    <p style="text-align: center;"><b>Atentamente<br/>"Por el engrandecimiento del Estado de Chiapas"</b></p>
    <br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="30%"></td>
            <td align="center" valign="top" width="40%" class="firma_up" style="text-transform: uppercase;"><?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($nombreDepto); ?></small></td>
            <td width="30%"></td>
        </tr>
    </table>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db, true);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader('');
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('15px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('constancia-buena-conducta.pdf', $output);
    }else{
        $mpdf->Output('constancia-buena-conducta.pdf', $output);
    }

    // die();
}

function generarConstanciaSustentante($id, $db, $output = 'I') {
    global $nombreInstituto, $claveInstitulo, $_indicacionInstituto;

    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_depto="11" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);


    $opcionesTitulacion[1] = "PROMEDIO GENERAL DE CALIFICACIONES";
    $opcionesTitulacion[2] = "ESTUDIOS DE POSGRADO (50% DE MAESTRÍA)";
    $opcionesTitulacion[3] = "SUSTENTACIÓN DE EXAMEN POR ÁREAS DE CONOCIMIENTO";
    $opcionesTitulacion[17] = "SUSTENTACIÓN DE EXAMEN POR ÁREAS DE CONOCIMIENTO (CENEVAL)";
    $opcionesTitulacion[4] = "CENEVAL";
    $opcionesTitulacion[5] = "TESIS PROFESIONAL";
    $opcionesTitulacion[6] = "CURSO DE TITULACIÓN";
    $opcionesTitulacion[7] = "INFORME O MEMORIA DE PRESTACIÓN DEL SERVICIO SOCIAL OBLIGATORIO";
    $opcionesTitulacion[8] = "MEMORIA DE EXPERIENCIA PROFESIONAL";
    $opcionesTitulacion[9] = "PRODUCCIÓN DE UNA UNIDAD AUDIOVISUAL, ELABORACIÓN DE TEXTOS, PROTOTIPOS DIDÁCTICOS O INSTRUCTIVOS PARA PRESENTACIONES DE UNIDADES TEMÁTICAS O PRÁCTICAS DE LABORATORIO O TALLER";
    $opcionesTitulacion[10] = "TESIS INDIVIDUAL Y RÉPLICA ORAL";
    $opcionesTitulacion[11] = "TESIS COLECTIVA Y RÉPLICA ORAL";
    $opcionesTitulacion[12] = "POR EL 50%  (CINCUENTA POR CIENTO) DE CRÉDITOS DE DOCTORADO";
    $opcionesTitulacion[13] = "EXAMEN GENERAL DE CONOCIMIENTOS";
    $opcionesTitulacion[14] = "POR PROMEDIO";
    $opcionesTitulacion[15] = "TESIS INDIVIDUAL Y REPLICA ORAL";
    $opcionesTitulacion[16] = "PROMEDIO GENERAL DE CALIFICACIONES";

    $laModalidad = $carreraData['modalidad'];
    $tipoCurso = 'semestre';
    if($carreraData['modalidad'] == 'Cuatrimestral'){
        $tipoCurso = 'cuatrimestre';
    }
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
    }
    if($carreraData['modalidad_numero'] < 3) $modalidadTexto = 'la licenciatura';
    if($carreraData['modalidad_numero'] == 3) $modalidadTexto = 'la maestría';
    if($carreraData['modalidad_numero'] == 4) $modalidadTexto = 'el doctorado';
    $promedioFinal = getPromedioAlumno($atencionData->col_alumnoid, $alumnodData->col_periodoid, $db);

    ob_start();
    ?>
    <table width="100%">
        <tr>
            <td width="100%" valign="top" align="left"><img width="250" src="<?php echo getLogo(); ?>" /></td>
        </tr>
    </table>
    <?php
    $header = ob_get_contents();
    ob_end_clean();
    $diaLetras = 'a un día';
    if(intval(date('j', strtotime($atencionData->col_fecha))) > 1) {
        $diaLetras = 'a los '.strtolower(numerosaletras(date('d', strtotime($atencionData->col_fecha)))).' días';
    }
    ob_start();
    $_promedioFinal = explode('.', $promedioFinal);

    $stringPromedio = $promedioFinal." (".strtolower(numerosaletras($_promedioFinal[0]).' punto '.numerosaletras($_promedioFinal[1])).')';
    if($_promedioFinal[0] == 10) {
        $stringPromedio = intval($promedioFinal)." (".strtolower(numerosaletras($_promedioFinal[0])).')';
    }

    echo $header;
    ?>
    <br/>
    <table width="100%">
        <tr>
            <td align="right"><b>Asunto: Constancia de sustentante.</b></td>
        </tr>
    </table>
    <br/><br/>
    <h3>A QUIEN CORRESPONDA</h3><br/><br/>
    <p style="text-align: justify;">La suscrita, Directora <?php echo $_indicacionInstituto; ?> <?php echo strtoupper($nombreInstituto); ?> CAMPUS TUXTLA, incorporada al Sistema Estatal, clave <?php echo $claveInstitulo; ?>.</p>
    <br/><h3 style="text-align:center;">HACE CONSTAR:</h3><br/>
    <p style="text-align: justify;">Que <?php echo (strtolower($alumnodData->col_genero) == 'm'?'la':'el'); ?> C. <?php echo trim(fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos));?>,
    de la generación <?php echo strtoupper($alumnodData->col_generacion_start); ?> – <?php echo strtoupper($alumnodData->col_generacion_end); ?>, observo
    <?php echo $extraData['observacionesSustentate']; ?> durante <?php echo $modalidadTexto; ?>, cumplió con el porcentaje mínimo de asistencia y obtuvo un promedio de <?php echo $stringPromedio; ?>.</p>
    <p style="text-align: justify;">A continuación se cita el nombre de la modalidad de examen y del jurado.</p>
    <br/>
    <p  style="text-align: justify;">MODALIDAD DE TITULACIÓN: <?php echo fixEncode($opcionesTitulacion[$alumnodData->col_opciones_titulacion]); ?></p>
    <p  style="text-align: justify;">SINODAL PRESIDENTE.- <?php echo fixEncode($atencionData->col_sinodal_presidente); ?></pEste>
    <p  style="text-align: justify;">SINODAL SECRETARIO.- <?php echo fixEncode($atencionData->col_sinodal_secretario); ?></p>
    <p  style="text-align: justify;">SINODAL VOCAL.- <?php echo fixEncode($atencionData->col_sinodal_vocal); ?></p>
    <p style="text-align: justify;padding: 8px 0;">Se extiende la presente <?php echo $diaLetras; ?> del mes de <?php echo strtolower(getMes(date('F', strtotime($atencionData->col_fecha)))); ?> del año <?php echo strtolower(numerosaletras(date('Y', strtotime($atencionData->col_fecha)))); ?>, en la Ciudad de Tuxtla Gutiérrez, Chiapas.</p>
    <p style="text-align: center;"><b>Atentamente<br/>"Por el engrandecimiento del Estado de Chiapas"</b></p>
    <br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="30%"></td>
            <td align="center" valign="top" width="40%" class="firma_up" style="text-transform: uppercase;"><?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small>DIRECTORA DE LA FACULTAD</small></td>
            <td width="30%"></td>
        </tr>
    </table>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    ob_start();
    ?>
    <?php
    $footer = ob_get_contents();
    ob_end_clean();

    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader('');
    // $mpdf->SetHTMLFooter(pdfFooter());
    $mpdf->SetHTMLFooter($footer);

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('15px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('constancia-sustentante.pdf', $output);
    }else{
        $mpdf->Output('constancia-sustentante.pdf', $output);
    }

    // die();
}

function generarConstanciaSinodales($id, $db, $output = 'I') {
    global $nombreInstituto, $claveInstitulo;

    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_depto="11" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $laModalidad = $carreraData['modalidad'];
    $tipoCurso = 'semestre';
    if($carreraData['modalidad'] == 'Cuatrimestral'){
        $tipoCurso = 'cuatrimestre';
    }
    $tipoExamen = 'DE GRADO';
    $lael = 'LA';
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
        $tipoExamen = 'PROFESIONAL';
    }
    if($carreraData['modalidad_numero'] < 3) $lael = 'DE LA';
    if($carreraData['modalidad_numero'] == 4) $lael = 'DEL';
    if($carreraData['modalidad_numero'] == 3) $lael = 'DE LA';
    $laelGenero = 'EL';
    if(strtoupper($alumnodData->col_genero) == 'M') $laelGenero = 'LA';

    // $diaLetras = 'a un día';
    // if(intval(date('j', strtotime($atencionData->col_fecha))) > 1) {
    //     $diaLetras = 'a los '.strtolower(numerosaletras(date('d', strtotime($atencionData->col_fecha)))).' días';
    // }

    $diaLetras = strtoupper(numerosaletras(date('d', strtotime($atencionData->col_fecha))));

    ob_start();
    ?>
    <table width="100%">
        <tr>
            <td width="100%" valign="top" align="center"><img width="190" src="<?php echo getLogo('iconoLogo'); ?>" /></td>
        </tr>
        <tr>
            <td width="100%" valign="top" align="center"><h2><?php echo strtoupper($nombreInstituto); ?><br/>CAMPUS TUXTLA</h2><br/><h3>Licenciaturas y Posgrados<br/>Clave: <?php echo $claveInstitulo; ?></h3></td>
        </tr>
    </table>
    <?php
    $header = ob_get_contents();
    ob_end_clean();
    $words = explode( "\n", wordwrap( fixEncode($atencionData->col_calificacion_jurado), 90));
    $paragraphs = count($words);
    ob_start();
    echo $header;
    ?>
    <br/>
    <h1 style="text-align: center;">C O N S T A N C I A</h1>
    <br/><br/>
    <p>EN EL EXAMEN <?php echo $tipoExamen; ?> <?php echo $lael; ?> <?php echo mb_strtoupper(fixEncode($carreraData['nombre'], true, true));?>, EFECTUADO POR <?php echo $laelGenero; ?> C. <?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos);?> EL DÍA <?php echo $diaLetras; ?> DE <?php echo strtoupper(getMes(date('F', strtotime($atencionData->col_fecha)))); ?> DE <?php echo strtoupper(numerosaletras(date('Y', strtotime($atencionData->col_fecha)))); ?>, EL JURADO DETERMINÓ OTORGAR LA CALIFICACIÓN DE:</p>
    <?php for($i = 0; $i < $paragraphs; $i++) { ?>
    <p style="border-bottom: 1px solid #000000;"><?php echo $words[$i]; ?></p>
    <?php } ?>
    <?php if($paragraphs == 1){ ?>
    <p style="border-bottom: 1px solid #000000;height: 17px;"></p>
    <?php } ?>
    <br/><br/><br/><br/>
    <p style="text-align: center;"><b>ATENTAMENTE<br/>"POR EL ENGRANDECIMIENTO DEL ESTADO DE CHIAPAS"</b></p>
    <p style="text-align: center;"><b>TUXTLA GUTIÉRREZ, CHIAPAS; <?php echo date('d', strtotime($atencionData->col_fecha)); ?> DE <?php echo strtoupper(getMes(date('F', strtotime($atencionData->col_fecha)))); ?> DE <?php echo date('Y', strtotime($atencionData->col_fecha)); ?></b></p>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    ob_start();
    ?>
    <p style="text-align: center;">EL SECRETARIO</p><br/><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="30%"></td>
            <td align="center" valign="top" width="40%" class="firma_up" style="text-transform: uppercase;"><?php echo fixEncode($extraData['nombreSecretario'], true); ?></td>
            <td width="30%"></td>
        </tr>
    </table>
    <br/><br/><br/><br/>
    <?php
    $footer = ob_get_contents();
    ob_end_clean();

    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader('');
    // $mpdf->SetHTMLFooter(pdfFooter());
    $mpdf->SetHTMLFooter($footer);

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('15px'), 1);
    $mpdf->WriteHTML($html, 2);

    if($output == 'S') {
        return $mpdf->Output('constancia-sindales.pdf', $output);
    }else{
        $mpdf->Output('constancia-sindales.pdf', $output);
    }

    // die();
}
function generarTomaProtesta($id, $db, $output = 'I') {
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);
    $nombreAlumno = fixEncode($alumnodData->col_nombres. ' '.$alumnodData->col_apellidos, true);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_depto="11" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $laModalidad = $carreraData['modalidad'];
    $tipoCurso = 'semestre';
    if($carreraData['modalidad'] == 'Cuatrimestral'){
        $tipoCurso = 'cuatrimestre';
    }
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
    }

    $tipoDoc = 'Licenciada';
    if(strtolower($alumnodData->col_genero) == 'h') $tipoDoc = 'Licenciado';
    $tipoEntrega = 'Titulo';

    if($carreraData['modalidad_periodo'] == 'master') {
        $tipoDoc = 'Maestra';
        $tipoEntrega = 'Grado';
        if(strtolower($alumnodData->col_genero) == 'h') $tipoDoc = 'Maestro';
    }

    if($carreraData['modalidad_periodo'] == 'docto') {
        $tipoDoc = 'Doctora';
        $tipoEntrega = 'Grado';
        if(strtolower($alumnodData->col_genero) == 'h') $tipoDoc = 'Doctor';
    }

    $imgLateral = getImage('protestamujer');
    if(strtolower($alumnodData->col_genero) == 'h') $imgLateral = getImage('protestahombre');

    $caballeroGuerrera = 'la guerrera';
    if(strtolower($alumnodData->col_genero) == 'h') $caballeroGuerrera = 'el caballero';

    ob_start();
    ?>
    <table width="100%">
        <tr>
            <td width="10%" valign="top" align="left"><img width="90" src="<?php echo getLogo('iconoLogo'); ?>" /></td>
            <td width="80%" valign="top" align="center">
            <img width="300" src="<?php echo getImage('encabezado'); ?>" />
            </td>
            <td width="10%" valign="top" align="left"><img width="60" src="<?php echo getLogo('sep_chiapas_icono'); ?>" /></td>
        </tr>
    </table>
    <?php
    $header = ob_get_contents();
    ob_end_clean();

    ob_start();
    echo $header;
    ?>
    <h2 class="papyrus" style="text-align: center;">Este Jurado, después de haber analizado, discutido y deliberado, ha decidido otorgarle el <?php echo $tipoEntrega; ?> de:</h2>
    <h2 class="papyrus" style="text-align: center;"><?php echo $tipoDoc; ?> en <?php echo fixEncode($carreraData['nombreLimpio']); ?></h2>
    <p class="papyrusBig"><img width="300" style="float:left;" src="<?php echo $imgLateral; ?>" />El acto solemne en el cual participa, constituye apenas el inicio de una nueva etapa en su vida, que entraña serias responsabilidades y le obliga a enriquecer permanentemente sus conocimientos. La distinción académica que hoy usted recibe, deberá honrarla y dignificarla, deberá tener presente que, ante todo, velará por el cumplimiento de la ley y la justicia, que tendrá que luchar diariamente por las causas justas con la espada del Derecho en diestra; que procurará siempre convertirse en <?php echo $caballeroGuerrera; ?> andante que represente los intereses de la sociedad en la que vive y los de su patria y que, por encima de todo, defenderá los valores humanos para conseguir la justicia y la paz.
    <br/>Recordaráis así estos deberes consagrados por el <?php echo $tipoEntrega; ?> que hoy se os otorga para el resto de su vida, por lo cual procederé a tomarle la protesta:</p>
    <br/>
    <h2 class="papyrus" style="text-align: center;"><?php echo $tipoDoc; ?> <?php echo $nombreAlumno; ?></h2>
    <p class="papyrusBig">¿Protestáis cumplir y hacer cumplir los sagrados deberes que conllevan el <?php echo $tipoEntrega; ?> de <?php echo $tipoDoc; ?> que hoy recibís?<br/>Si así lo hicieres que la Patria y el Estado de Chiapas os lo premie y si no que os lo demande.</p>
    <br/>
    <p class="papyrus" style="text-align: center;"><b>Atentamente<br/>"Por el engrandecimiento del Estado de Chiapas"</b></p>
    <br/>
    <?php
    $firmante = fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true);
    if($atencionData->col_sinodal_presidente != '') $firmante = fixEncode($atencionData->col_sinodal_presidente);
    ?>
    <p class="papyrus" style="text-align: center;"><b><?php echo $firmante; ?></b><br/><b>Tuxtla Gutiérrez, Chiapas; <?php echo date('d', strtotime($atencionData->col_fecha)); ?> De <?php echo getMes(date('F', strtotime($atencionData->col_fecha))); ?> De <?php echo date('Y', strtotime($atencionData->col_fecha)); ?></b></p>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    ob_start();
    ?>
    <?php
    $footer = ob_get_contents();
    ob_end_clean();

    include_once(__DIR__ . '/../src/mpdf/mpdf.php');


    $m = getMargins($db);
    $mpdf=new mPDF('s','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader('');
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('14px', '14px'), 1);
    $mpdf->WriteHTML($html, 2);

    if($output == 'S') {
        return $mpdf->Output('toma-protesta.pdf', $output);
    }else{
        $mpdf->Output('toma-protesta.pdf', $output);
    }

    // die();
}


function generarTomaProtestaPosgrado($id, $db, $output = 'I') {
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);
    $nombreAlumno = fixEncode($alumnodData->col_nombres. ' '.$alumnodData->col_apellidos, true);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_depto="11" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $laModalidad = $carreraData['modalidad'];
    $tipoCurso = 'semestre';
    if($carreraData['modalidad'] == 'Cuatrimestral'){
        $tipoCurso = 'cuatrimestre';
    }
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
    }


    $tipoDoc = 'Licenciada';
    if(strtolower($alumnodData->col_genero) == 'h') $tipoDoc = 'Licenciado';
    $tipoEntrega = 'Titulo';

    if($carreraData['modalidad_periodo'] == 'master') {
        $tipoDoc = 'Maestra';
        $tipoEntrega = 'Grado';
        if(strtolower($alumnodData->col_genero) == 'h') $tipoDoc = 'Maestro';
    }

    if($carreraData['modalidad_periodo'] == 'docto') {
        $tipoDoc = 'Doctora';
        $tipoEntrega = 'Grado';
        if(strtolower($alumnodData->col_genero) == 'h') $tipoDoc = 'Doctor';
    }

    $imgLateral = getImage('protestamujer');
    if(strtolower($alumnodData->col_genero) == 'h') $imgLateral = getImage('protestahombre');

    $caballeroGuerrera = 'la guerrera';
    if(strtolower($alumnodData->col_genero) == 'h') $caballeroGuerrera = 'el caballero';

    $caballeroGuerreraDigna = 'digna guerrera';
    if(strtolower($alumnodData->col_genero) == 'h') $caballeroGuerreraDigna = 'digno caballero';

    ob_start();
    ?>
    <table width="100%">
        <tr>
            <td width="10%" valign="top" align="left"><img width="90" src="<?php echo getLogo('iconoLogo'); ?>" /></td>
            <td width="80%" valign="top" align="center">
            <img width="300" src="<?php echo getImage('encabezado'); ?>" />
            </td>
            <td width="10%" valign="top" align="left"><img width="60" src="<?php echo getLogo('sep_chiapas_icono'); ?>" /></td>
        </tr>
    </table>
    <?php
    $header = ob_get_contents();
    ob_end_clean();

    ob_start();
    echo $header;
    ?>
    <br/>
    <h2 class="papyrus" style="text-align: center;">Este Jurado, después de haber analizado, discutido y deliberado, ha decidido otorgarle el <?php echo $tipoEntrega; ?> de:</h2>
    <br/>
    <h2 class="papyrus" style="text-align: center;"><?php echo $tipoDoc; ?> en <?php echo fixEncode($carreraData['nombreLimpio']); ?></h2>
    <p class="papyrusBig"><img width="300" style="float:left;" src="<?php echo $imgLateral; ?>" />La distinción académica que hoy usted recibe, deberá honrarla y dignificarla cual espada de <?php echo $caballeroGuerreraDigna; ?>, deberá tener presente que, ante todo, velará por el cumplimiento de la ley y la justicia aún cuando en ello se vaya su propia vida; deberá defender al necesitado y al desvalido frente al déspota y al arbitrario; que tendrá que luchar diariamente por las causas justas con la espada del Derecho en diestra; que procurará siempre convertirse en <?php echo $caballeroGuerrera; ?> andante que represente los intereses de la sociedad en la que vive y los de su patria y que, por encima de todo, defenderá los valores humanos para conseguir la justicia y la paz entre las naciones de la tierra.
    <br/>Recordaráis así estos deberes consagrados por el <?php echo $tipoEntrega; ?> que hoy se os otorga para el resto de su vida, por lo cual procederé a tomarle la protesta:</p>
    <h2 class="papyrus" style="text-align: center;"><?php echo $tipoDoc; ?> <?php echo $nombreAlumno; ?></h2>
    <p class="papyrusBig">¿Protestáis cumplir y hacer cumplir los sagrados deberes que conllevan el <?php echo $tipoEntrega; ?> de <?php echo $tipoDoc; ?> que hoy recibís?<br/>Si así lo hicieres que la Patria y el Estado de Chiapas os lo premie y si no que os lo demande.</p>
    <br/>
    <p class="papyrus" style="text-align: center;"><b>Atentamente<br/>"Por el engrandecimiento del Estado de Chiapas"</b></p>
    <br/>
    <?php
        $firmante = fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true);
        if($atencionData->col_sinodal_presidente != '') $firmante = fixEncode($atencionData->col_sinodal_presidente);
    ?>
    <p class="papyrus" style="text-align: center;"><b><?php echo $firmante; ?></b><br/><b>Tuxtla Gutiérrez, Chiapas; <?php echo date('d', strtotime($atencionData->col_fecha)); ?> de <?php echo strtolower(getMes(date('F', strtotime($atencionData->col_fecha)))); ?> de <?php echo date('Y', strtotime($atencionData->col_fecha)); ?></b></p>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    ob_start();
    ?>
    <?php
    $footer = ob_get_contents();
    ob_end_clean();

    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    $mpdf=new mPDF('s','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader('');
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('14px', '14px'), 1);
    $mpdf->WriteHTML($html, 2);

    if($output == 'S') {
        return $mpdf->Output('toma-protestas.pdf', $output);
    }else{
        $mpdf->Output('toma-protesta.pdf', $output);
    }

    // die();
}

function generarConstanciaExamenConocimientos($id, $db, $output = 'I') {
    // die();
}

function generarDictamenTitulacion($id, $db, $output = 'I') {
    global $opcionesTitulacion;
    global $nombreInstituto, $claveInstitulo, $_indicacionInstituto;

    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);


    $query = 'SELECT * FROM tbl_users WHERE col_id="11" LIMIT 1 ';
    if(intval($extraData['segundaFirma']) > 0) $query = 'SELECT * FROM tbl_users WHERE col_id="'.intval($extraData['segundaFirma']).'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $egresadoTexto = 'egresada';
    if(strtolower($alumnodData->col_genero) == 'h') $egresadoTexto = 'egresado';

    ob_start();
    ?>
    <br/>
    <img src="<?php echo getLogo('big'); ?>" style="max-width: 310px;height:auto;" alt="FLDCH" border="0"/>
    <br/><br/>
    <table border="0" width="100%">
        <tr>
            <td align="right">TUXTLA GUTIÉRREZ, CHIAPAS; <?php echo date('d', strtotime($atencionData->col_fecha)); ?> DE <?php echo getMes(date('F', strtotime($atencionData->col_fecha))); ?> DE <?php echo date('Y', strtotime($atencionData->col_fecha)); ?></td>
        </tr>
    </table>
    <br/>
    <table border="0" width="100%">
        <tr>
            <td align="right">ASUNTO: DICTAMEN DE TITULACIÓN</td>
        </tr>
    </table><br/><br/>
    <p><?php echo mb_strtoupper(fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true), 'UTF-8'); ?><br/>
    <b>PRESENTE</b></p>

    <p style="text-align: justify;text-transform:uppercase;">La suscrita, <?php echo $extraData['suscritaTomaProtesta']; ?> <?php echo $_indicacionInstituto; ?> <b><?php echo $nombreInstituto; ?></b>,
incorporado al Sistema Estatal; con Clave: <?php echo $claveInstitulo; ?> informa a usted en
su calidad de <?php echo $egresadoTexto; ?> de la <b><?php echo strtoupper($carreraData['nombre']); ?></b>, de la
Generación <?php echo $alumnodData->col_generacion_start; ?>-<?php echo $alumnodData->col_generacion_end; ?>, que ha cumplido con todos los requisitos para el
trámite de titulación. Por lo que ha sido dictaminado favorablemente
para realizar la Toma de Protesta <?php echo $extraData['tomaProtesta']; ?>, mediante la opción de
Titulación: <b><?php echo fixEncode($opcionesTitulacion[$alumnodData->col_opciones_titulacion]); ?></b>.</p>
<br/>
<p style="text-align: justify;text-transform:uppercase;">Debido a lo antes expuesto, deberá presentarse a la toma de protesta el
día <?php echo date('d', strtotime($extraData['fechaTomaProtesta'])); ?> de <?php echo getMes(date('F', strtotime($extraData['fechaTomaProtesta']))); ?> de <?php echo date('Y', strtotime($extraData['fechaTomaProtesta'])); ?> a las <?php echo $extraData['horaTomaProtesta']; ?> horas en <?php echo $extraData['lugarTomaProtesta']; ?>.</p>
<br/>
<p style="text-transform:uppercase;">Sin otro particular, reciba un cordial saludo.</p>

    <br/><br/>
    <h3 style="text-align: center;font-weight: bold;">A T E N T A M E N T E:</h3>
    <p style="text-align: center;text-transform:uppercase;">“Por el engrandecimiento del estado de Chiapas”</p><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="20%"></td>
            <td align="center" valign="top" width="60%" class="firma_up" style="text-transform: uppercase;">
            <?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo $extraData['suscritaTomaProtesta']; ?></small></td>
            <!-- Especificaron que siempre debe ser directora el depto, se dejo como campo abierto -->
            <td width="20%"></td>
        </tr>
    </table>

    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);

    $mpdf->SetHTMLHeader('');
    // $mpdf->SetHTMLFooter(pdfFooter());
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('15px', '15px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('dictamen-titulacion.pdf', $output);
    }else{
        $mpdf->Output('dictamen-titulacion.pdf', $output);
    }

    // die();
}

function generarConstanciaEstudios($id, $db, $output = 'I') {
    global $opcionesTitulacion;
    global $nombreInstituto, $claveInstitulo, $_indicacionInstituto;

    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT t.col_periodoid, p.col_grado FROM `tbl_alumnos_taxonomia` t LEFT JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_alumnoid="'.$atencionData->col_alumnoid.'" AND p.col_grado="'.$extraData['semestreCursando'].'" LIMIT 1';
    $sth = $db->prepare($query);
    $sth->execute();
    $periodoSeleccionado = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    //$extraData['semestreCursando']


    $query = 'SELECT * FROM tbl_users WHERE col_id="11" LIMIT 1 ';
    if(intval($extraData['segundaFirma']) > 0) $query = 'SELECT * FROM tbl_users WHERE col_id="'.intval($extraData['segundaFirma']).'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;
    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($periodoSeleccionado->col_periodoid, $db, false);


    $carreraDataModalidad = 'ESCOLARIZADA';
    if($carreraData['modalidad'] == 'Cuatrimestral') $carreraDataModalidad = 'MIXTA';

    $egresadoTexto = 'la';
    if(strtolower($alumnodData->col_genero) == 'h') $egresadoTexto = 'el';

    if($carreraData['modalidad_periodo'] == 'ldsem' || $carreraData['modalidad_periodo'] == 'ldcua') {
        $tituloAlumno = 'Lic.';
        $tipoCarrera = 'de la licenciatura';
    }
    if($carreraData['modalidad_periodo'] == 'docto') {
        $tituloAlumno = 'Doc.';
        $tipoCarrera = 'del doctorado';
    }
    if($carreraData['modalidad_periodo'] == 'master') {
        $tituloAlumno = 'Mtra.';
        $tipoCarrera = 'de la maestría';
    }
    if($carreraData['modalidad_periodo'] == 'master' && strtolower($alumnodData->col_genero) == 'h') $tituloAlumno = 'Mtro.';

    $diaLetras = 'a un día';
    if(intval(date('j', strtotime($atencionData->col_fecha))) > 1) {
        $diaLetras = 'a los '.strtolower(numerosaletras(date('d', strtotime($atencionData->col_fecha)))).' días';
    }

    $inicioVacaciones =  date('d', strtotime($extraData['inicioPeriodoVacacional'])).' de '.strtolower(getMes(date('F', strtotime($extraData['inicioPeriodoVacacional'])))).' de '.date('Y', strtotime($extraData['inicioPeriodoVacacional']));
    $finVacaciones = date('d', strtotime($extraData['finPeriodoVacacional'])).' de '.strtolower(getMes(date('F', strtotime($extraData['finPeriodoVacacional'])))).' de '.date('Y', strtotime($extraData['finPeriodoVacacional']));


    $inicioPeriodo =  date('d', strtotime($periodoData->col_fecha_inicio)).' de '.strtolower(getMes(date('F', strtotime($periodoData->col_fecha_inicio)))).' de '.date('Y', strtotime($periodoData->col_fecha_inicio));
    $finPeriodo = date('d', strtotime($periodoData->col_fecha_fin)).' de '.strtolower(getMes(date('F', strtotime($periodoData->col_fecha_fin)))).' de '.date('Y', strtotime($periodoData->col_fecha_fin));

    $fechasPeriodo = $inicioPeriodo.' al '.$finPeriodo;
    ob_start();
    ?>
    <table border="0" width="100%">
        <tr>
            <td align="right">ASUNTO: Constancia de estudios.</td>
        </tr>
    </table><br/><br/>
    <b>A QUIEN CORRESPONDA.</b></p>

    <p style="text-align: justify;">La suscrita, <?php echo $extraData['suscritaTomaProtesta']; ?> <?php echo $_indicacionInstituto; ?> <?php echo $nombreInstituto; ?>,
incorporado al Sistema Estatal. Modalidad: <?php echo $carreraDataModalidad; ?>. Clave: <?php echo $claveInstitulo; ?>:</p><br/>
<h3 style="text-align: center;font-weight: bold;">H A C E  C O N S T A R:</h3><br/>
<p style="text-align: justify;">
Que <?php echo $egresadoTexto; ?> C. <?php echo mb_strtoupper(fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true), 'UTF-8'); ?> con número de
matrícula <?php echo $alumnodData->col_control; ?> está cursando el <?php echo ucfirst(numerosaletrasSemestre($extraData['semestreCursando'])); ?> semestre en
el periodo del <?php echo $fechasPeriodo; ?>, de la <?php echo mb_strtoupper($carreraData['nombre'], 'UTF-8'); ?>, (con RVOE Acuerdo número <?php echo $carreraData['revoe']; ?>. De
fecha <?php echo $carreraData['vigencia_revoe']; ?>), de la generación <?php echo $alumnodData->col_generacion_start; ?> – <?php echo $alumnodData->col_generacion_end; ?>. Acreditando
el <?php echo $extraData['porcentajeCreditos']; ?> de créditos del plan de estudios. Con un
periodo vacacional del <?php echo $inicioVacaciones; ?> al <?php echo $finVacaciones; ?>.</p>
<p style="text-align: justify;">A petición de la parte interesada se extiende la presente, <?php echo $diaLetras; ?> del mes de <?php echo strtolower(getMes(date('F', strtotime($atencionData->col_fecha)))); ?> del año dos mil <?php echo strtolower(numerosaletras(substr(date('Y', strtotime($atencionData->col_fecha)), -2))); ?>, en la Ciudad de Tuxtla Gutiérrez, Chiapas.</p>
    <br/><br/>
    <h3 style="text-align: center;font-weight: bold;">A T E N T A M E N T E:</h3>
    <p style="text-align: center;"><span class="cursiva">"Por el engrandecimiento del Estado de Chiapas"</span></p><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="20%"></td>
            <td align="center" valign="top" width="60%" class="firma_up" style="text-transform: uppercase;">
            <?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($nombreDepto); ?></small></td>
            <td width="20%"></td>
        </tr>
    </table>

    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    // $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);
    $mpdf=new mPDF('s','Letter', '','', $m['left'], $m['right'], 50, 25, $m['top'], $m['bottom']);

    $mpdf->SetHTMLHeader('');
    // $mpdf->SetHTMLFooter(pdfFooter());
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('15px', '15px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('constancia-estudios.pdf', $output);
    }else{
        $mpdf->Output('constancia-estudios.pdf', $output);
    }

}

function generarConstanciaTerminoEstudios($id, $db, $output = 'I') {
    global $opcionesTitulacion, $totalCreditos;
    global $nombreInstituto, $claveInstitulo, $_indicacionInstituto;

    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);


    $query = 'SELECT * FROM tbl_users WHERE col_id="11" LIMIT 1 ';
    if(intval($extraData['segundaFirma']) > 0) $query = 'SELECT * FROM tbl_users WHERE col_id="'.intval($extraData['segundaFirma']).'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);
    // $periodoData = getPeriodoPorGrado($alumnodData->col_id, $extraData['semestreCursando'], $db);
    $creditosConseguidos = getCreditosHastaGrado($alumnodData->col_id, $periodoData->col_grado, $db);


    $carreraDataModalidad = 'ESCOLARIZADA';
    if($carreraData['modalidad'] == 'Cuatrimestral') $carreraDataModalidad = 'MIXTA';

    $egresadoTexto = 'la';
    if(strtolower($alumnodData->col_genero) == 'h') $egresadoTexto = 'el';

    $firmanteTexto = 'La suscrita, encargada de';
    if(strtolower($userData->col_genero) == 'h') $firmanteTexto = 'El suscrito, encargado de';

    if($carreraData['modalidad_periodo'] == 'ldsem' || $carreraData['modalidad_periodo'] == 'ldcua') {
        $tituloAlumno = 'Lic.';
        $tipoCarrera = 'de la licenciatura';
        $creditosPorModalidad = $totalCreditos['licenciatura'];
    }
    if($carreraData['modalidad_periodo'] == 'docto') {
        $tituloAlumno = 'Doc.';
        $tipoCarrera = 'del doctorado';
        $creditosPorModalidad = $totalCreditos['doctorado'];
    }
    if($carreraData['modalidad_periodo'] == 'master') {
        $tituloAlumno = 'Mtra.';
        $tipoCarrera = 'de la maestría';
        $creditosPorModalidad = $totalCreditos['maestria'];
    }
    if($carreraData['modalidad_periodo'] == 'master' && strtolower($alumnodData->col_genero) == 'h') $tituloAlumno = 'Mtro.';

    $diaLetras = 'a un día';
    if(intval(date('j', strtotime($atencionData->col_fecha))) > 1) {
        $diaLetras = 'a los '.strtolower(numerosaletras(date('d', strtotime($atencionData->col_fecha)))).' días';
    }

    //echo $creditosConseguidos .'/'. $creditosPorModalidad;exit;
    $porcentaje = ($creditosConseguidos / $creditosPorModalidad)*100;
    if(intval($porcentaje) < 100) {
        $porcentaje = number_format($porcentaje, 2);
    }

    ob_start();
    ?>
    <table border="0" width="100%">
        <tr>
            <td align="right"><b>ASUNTO: Constancia de Estudios</b></td>
        </tr>
    </table><br/><br/>
    <b>A QUIEN CORRESPONDA.</b></p>

    <p style="text-align: justify;"><?php echo $firmanteTexto; ?> <?php echo fixEncode($nombreDepto); ?> <?php echo $_indicacionInstituto; ?> <?php echo $nombreInstituto; ?>,
incorporado al Sistema Estatal. Modalidad: <?php echo $carreraDataModalidad; ?>. Clave: <?php echo $claveInstitulo; ?>:</p><br/>
<h3 style="text-align: center;font-weight: bold;">H A C E  C O N S T A R:</h3><br/>
<p style="text-align: justify;">
Que <?php echo $egresadoTexto; ?> <?php echo $tituloAlumno; ?> <?php echo mb_strtoupper(fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true), 'UTF-8'); ?> con número de
matrícula <?php echo $alumnodData->col_control; ?> concluyó la <?php echo mb_strtoupper($carreraData['nombre'], 'UTF-8'); ?>, (con RVOE Acuerdo número <?php echo $carreraData['revoe']; ?>. De
fecha <?php echo $carreraData['vigencia_revoe']; ?>), de la generación <?php echo $alumnodData->col_generacion_start; ?> – <?php echo $alumnodData->col_generacion_end; ?>.
Con un total de <?php echo $creditosConseguidos; ?> créditos cursados correspondientes al <?php echo $porcentaje; ?>% del plan de estudios.</p>
<p style="text-align: justify;">A petición de la parte interesada se extiende la presente, <?php echo $diaLetras; ?> del mes de <?php echo strtolower(getMes(date('F', strtotime($atencionData->col_fecha)))); ?> del año dos mil <?php echo strtolower(numerosaletras(substr(date('Y', strtotime($atencionData->col_fecha)), -2))); ?>, en la Ciudad de Tuxtla Gutiérrez, Chiapas.</p>
    <br/><br/>
    <h3 style="text-align: center;font-weight: bold;">A T E N T A M E N T E:</h3>
    <p style="text-align: center;"><span class="cursiva">"Por el engrandecimiento del Estado de Chiapas"</span></p><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="20%"></td>
            <td align="center" valign="top" width="60%" class="firma_up" style="text-transform: uppercase;">
            <?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($nombreDepto); ?></small></td>
            <td width="20%"></td>
        </tr>
    </table>

    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    // $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);
    $mpdf=new mPDF('s','Letter', '','', $m['left'], $m['right'], 50, 25, $m['top'], $m['bottom']);

    $mpdf->SetHTMLHeader('');
    // $mpdf->SetHTMLFooter(pdfFooter());
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('15px', '15px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('constancia-termino-estudios.pdf', $output);
    }else{
        $mpdf->Output('constancia-termino-estudios.pdf', $output);
    }

    // die();
}

function generarConstanciaTramiteTitulacion($id, $db, $output = 'I') {
    global $opcionesTitulacion, $_indicacionInstituto, $nombreInstituto, $claveInstitulo;

    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);


    $query = 'SELECT * FROM tbl_users WHERE col_id="11" LIMIT 1 ';
    if(intval($extraData['segundaFirma']) > 0) $query = 'SELECT * FROM tbl_users WHERE col_id="'.intval($extraData['segundaFirma']).'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnodData->col_carrera, $db);
    $periodoData = getPeriodo($alumnodData->col_periodoid, $db, false);

    $firmanteTexto = 'La suscrita, encargada de';
    if(strtolower($userData->col_genero) == 'h') $firmanteTexto = 'El suscrito, encargado de';

    $carreraDataModalidad = 'ESCOLARIZADA';
    if($carreraData['modalidad'] == 'Cuatrimestral') $carreraDataModalidad = 'MIXTA';

    $egresadoTexto = 'la';
    if(strtolower($alumnodData->col_genero) == 'h') $egresadoTexto = 'el';

    if($carreraData['modalidad_periodo'] == 'ldsem' || $carreraData['modalidad_periodo'] == 'ldcua') {
        $tituloAlumno = 'Lic.';
        $tipoCarrera = 'de la licenciatura';
    }
    if($carreraData['modalidad_periodo'] == 'docto') {
        $tituloAlumno = 'Doc.';
        $tipoCarrera = 'del doctorado';
    }
    if($carreraData['modalidad_periodo'] == 'master') {
        $tituloAlumno = 'Mtra.';
        $tipoCarrera = 'de la maestría';
    }
    if($carreraData['modalidad_periodo'] == 'master' && strtolower($alumnodData->col_genero) == 'h') $tituloAlumno = 'Mtro.';

    $diaLetras = 'a un día';
    if(intval(date('j', strtotime($atencionData->col_fecha))) > 1) {
        $diaLetras = 'a los '.strtolower(numerosaletras(date('d', strtotime($atencionData->col_fecha)))).' días';
    }

    $totalCreditos = 308;

    ob_start();
    ?>
    <table border="0" width="100%">
        <tr>
            <td align="right"><b>ASUNTO: Constancia de Trámite de Titulación.</b></td>
        </tr>
    </table><br/><br/>
    <b>A QUIEN CORRESPONDA.</b></p>

    <p style="text-align: center;"><?php echo $firmanteTexto; ?> <?php echo fixEncode($nombreDepto); ?> <?php echo $_indicacionInstituto; ?> <?php echo $nombreInstituto; ?>,
incorporado al Sistema Estatal, clave: <?php echo $claveInstitulo; ?>:</p><br/>
<h3 style="text-align: center;font-weight: bold;">H A C E&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;C O N S T A R:</h3><br/>
<p style="text-align: justify;">
Que <?php echo $egresadoTexto; ?> <?php echo $tituloAlumno; ?> <?php echo mb_strtoupper(fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true), 'UTF-8'); ?> con número de
matrícula <?php echo $alumnodData->col_control; ?> concluyó la <?php echo mb_strtoupper($carreraData['nombre'], 'UTF-8'); ?>, (con RVOE Acuerdo número <?php echo $carreraData['revoe']; ?>. De
fecha <?php echo $carreraData['vigencia_revoe']; ?>), de la generación <?php echo $alumnodData->col_generacion_start; ?> – <?php echo $alumnodData->col_generacion_end; ?>.
Obteniendo un total de <?php echo $totalCreditos; ?> créditos correspondientes al 100% del plan de estudios. Actualmente se encuentra en trámite de <?php echo $extraData['tramitePendiente']; ?>.</p>
<p style="text-align: justify;">A petición de la parte interesada se extiende la presente, <?php echo $diaLetras; ?> del mes de <?php echo strtolower(getMes(date('F', strtotime($atencionData->col_fecha)))); ?> del año dos mil <?php echo strtolower(numerosaletras(substr(date('Y', strtotime($atencionData->col_fecha)), -2))); ?>, en la Ciudad de Tuxtla Gutiérrez, Chiapas.</p>
    <br/><br/>
    <h3 style="text-align: center;font-weight: bold;">A T E N T A M E N T E:</h3>
    <p style="text-align: center;"><span class="cursiva">"Por el engrandecimiento del Estado de Chiapas"</span></p><br/><br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="20%"></td>
            <td align="center" valign="top" width="60%" class="firma_up" style="text-transform: uppercase;">
            <?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($nombreDepto); ?></small></td>
            <td width="20%"></td>
        </tr>
    </table>

    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    // $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);
    $mpdf=new mPDF('s','Letter', '','', $m['left'], $m['right'], 50, 25, $m['top'], $m['bottom']);

    $mpdf->SetHTMLHeader('');
    // $mpdf->SetHTMLFooter(pdfFooter());
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('15px', '15px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('constancia-tramite-titulacion.pdf', $output);
    }else{
        $mpdf->Output('constancia-tramite-titulacion.pdf', $output);
    }

}

function generarActaExamenLicenciatura($id, $db, $output = 'I') {
    global $nombreInstituto, $claveInstitulo, $_indicacionInstituto, $isINEF;
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);
    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

    // $query = 'SELECT t.col_id, t.col_alumnoid, t.col_periodoid, t.col_status, p.col_grado FROM `tbl_alumnos_taxonomia` t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_baja=0 AND p.col_grado=1 AND t.col_alumnoid="'.$atencionData->col_alumnoid.'"';
    // $sth = $db->prepare($query);
    // $sth->execute();
    // $alumnoPeriodoInicial = $sth->fetch(PDO::FETCH_OBJ);

    // $query = 'SELECT t.col_id, t.col_alumnoid, t.col_periodoid, t.col_status, p.col_grado FROM `tbl_alumnos_taxonomia` t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_baja=0 AND t.col_alumnoid="'.$atencionData->col_alumnoid.'" ORDER BY p.col_grado DESC LIMIT 1';
    // $sth = $db->prepare($query);
    // $sth->execute();
    // $alumnoPeriodoFinal = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_depto="11" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;

    $carreraData = getCarrera($alumnoData->col_carrera, $db);
    // $periodoData = getPeriodo($alumnoData->col_periodoid, $db, false);
    // $periodoDataInicio = getPeriodo($alumnoPeriodoInicial->col_periodoid, $db, false);
    // $periodoDataFin = getPeriodo($alumnoPeriodoFinal->col_periodoid, $db, false);

    $extraData['fechaVigencia'] = $carreraData['vigencia_revoe_date'];


    $laModalidad = $carreraData['modalidad'];
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
    }

    if($carreraData['modalidad'] == 'Semestral') $carreraMod = 'SEMESTRE';
    if($carreraData['modalidad'] == 'Cuatrimestral') $carreraMod = 'CUATRIMESTRE';
    $promGeneral = 0;
    $creditosGanados = 0;
    // $carreraData['modalidad'] = 'ESCOLARIZADA';
    $carreraDataModalidad = 'ESCOLARIZADA';
    if($carreraData['modalidad'] == 'Cuatrimestral') $carreraDataModalidad = 'MIXTA';

    $turno = 'VESPERTINO';
    if($carreraData['modalidad'] == 'Cuatrimestral') $turno = 'MIXTO';

    $elLa = 'LA';
    if($alumnoData->col_genero != 'M') $elLa = 'EL';

    $nombreAlumno = mb_strtoupper(fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos));

    $opcionesTitulacion[1] = "PROMEDIO GENERAL DE CALIFICACIONES";
    $opcionesTitulacion[2] = "ESTUDIOS DE POSGRADO (50% DE MAESTRÍA)";
    $opcionesTitulacion[3] = "SUSTENTACIÓN DE EXAMEN POR ÁREAS DE CONOCIMIENTO";
    $opcionesTitulacion[17] = "SUSTENTACIÓN DE EXAMEN POR ÁREAS DE CONOCIMIENTO (CENEVAL)";
    $opcionesTitulacion[4] = "CENEVAL";
    $opcionesTitulacion[5] = "TESIS PROFESIONAL";
    $opcionesTitulacion[6] = "CURSO DE TITULACIÓN";
    $opcionesTitulacion[7] = "INFORME O MEMORIA DE PRESTACIÓN DEL SERVICIO SOCIAL OBLIGATORIO";
    $opcionesTitulacion[8] = "MEMORIA DE EXPERIENCIA PROFESIONAL";
    $opcionesTitulacion[9] = "PRODUCCIÓN DE UNA UNIDAD AUDIOVISUAL, ELABORACIÓN DE TEXTOS, PROTOTIPOS DIDÁCTICOS O INSTRUCTIVOS PARA PRESENTACIONES DE UNIDADES TEMÁTICAS O PRÁCTICAS DE LABORATORIO O TALLER";
    $opcionesTitulacion[10] = "TESIS INDIVIDUAL Y RÉPLICA ORAL";
    $opcionesTitulacion[11] = "TESIS COLECTIVA Y RÉPLICA ORAL";
    $opcionesTitulacion[12] = "POR EL 50%  (CINCUENTA POR CIENTO) DE CRÉDITOS DE DOCTORADO";
    $opcionesTitulacion[13] = "EXAMEN GENERAL DE CONOCIMIENTOS";
    $opcionesTitulacion[14] = "POR PROMEDIO";
    $opcionesTitulacion[15] = "TESIS INDIVIDUAL Y REPLICA ORAL";
    $opcionesTitulacion[16] = "PROMEDIO GENERAL DE CALIFICACIONES";




    if($alumnoData->col_opciones_titulacion == 1){
        $promedioFinal = getPromedioFinalAlumno($atencionData->col_alumnoid, $db);
        $modoTitulacion = $opcionesTitulacion[$alumnoData->col_opciones_titulacion].' '.$promedioFinal.' ('.mb_strtoupper(numerosaletras($promedioFinal)).')';
    }else{
        $modoTitulacion = $opcionesTitulacion[$alumnoData->col_opciones_titulacion];
    }
    $ala = 'A LA';
    if(strtolower($alumnoData->col_genero) == 'h') $ala = 'AL';

    $tipoDoc = 'LICENCIADA';
    if(strtolower($alumnoData->col_genero) == 'h') $tipoDoc = 'LICENCIADO';
    $tipoEntrega = 'Titulo';

    if($carreraData['modalidad_periodo'] == 'master') {
        $tipoDoc = 'MAESTRA';
        $tipoEntrega = 'GRADO';
        if(strtolower($alumnoData->col_genero) == 'h') $tipoDoc = 'MAESTRO';
    }

    if($carreraData['modalidad_periodo'] == 'docto') {
        $tipoDoc = 'DOCTORA';
        $tipoEntrega = 'GRADO';
        if(strtolower($alumnoData->col_genero) == 'h') $tipoDoc = 'DOCTOR';
    }

    $carredaTitulacion = $tipoDoc.' EN '.mb_strtoupper($carreraData['nombreLimpio']);

    $minimaAprobatoria = 'MÍNIMA APROBATORIA 7 (SIETE)';
    if($carreraData['modalidad'] == 'Doctorado') $minimaAprobatoria = 'MÍNIMA APROBATORIA 8 (OCHO)';

    $arrMeses = array('ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE');
    $laFecha = undelined(mb_strtoupper(numerosaletras(date('j', strtotime($extraData['fechaActaPosgrado'])))));
    $laFecha .= ' DEL MES DE ';
    $laFecha .= undelined($arrMeses[date('n', strtotime($extraData['fechaActaPosgrado']))]);
    $laFecha .= ' DE ';
    $laFecha .= undelined(mb_strtoupper(numerosaletras(date('Y', strtotime($extraData['fechaActaPosgrado'])))));

    $lineHeight = 150;

    ob_start();
    ?>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td colspan="2" align="right" style="padding-bottom:12px;"><small>AEP-16-<?php echo date('Y', strtotime($atencionData->col_fecha)); ?></small></td>
        </tr>
        <tr>
            <td width="13%" valign="top" align="center" style="padding-top:15px;"><img width="106" src="<?php echo getLogo('gobernacion'); ?>" /></td>
            <td width="87%" valign="top" align="center">
                <p style="font-size:20px;font-weight:bold;">GOBIERNO CONSTITUCIONAL DEL ESTADO DE CHIAPAS</p>
                <p style="font-size:20px;font-weight:bold;">SECRETARÍA DE EDUCACIÓN</p>
                <p style="font-size:20px;">SUBSECRETARÍA DE EDUCACIÓN ESTATAL<br/>DIRECCIÓN DE EDUCACIÓN SUPERIOR<br/>DEPARTAMENTO DE SERVICIOS ESCOLARES</p>
            </td>
        </tr>
        <tr>
            <td width="15%" valign="top" align="left"></td>
            <td width="85%" align="center" style="font-size:12px;word-spacing: -1px;">RVOE:ACUERDO NÚMERO <?php echo $carreraData['revoe']?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;VIGENCIA:A PARTIR DEL <?php echo strtoupper(fechaTexto($extraData['fechaVigencia'], 'j \d\e F \d\e Y')); ?><br/>REGIMEN:PARTICULAR</td>
        </tr>
        <tr>
            <td width="100%" colspan="2" valign="top" align="right">
                <br/>
                <p style="font-size:14px;font-weight:bold;">No: <i style="font-style:normal;border-bottom: 2px solid #222;color:#cc0000;">&nbsp;&nbsp;<?php echo $atencionData->col_folio; ?>&nbsp;&nbsp;</i></p>
            </td>
        </tr>
    </table>

    <table width="100%" border="0" style="font-size:15px;margin-top: 20px;margin-bottom: 10px;" cellpadding="0" cellspacing="0">
        <tr>
            <td width="18%" valign="top" align="left"><img align="left" style="padding:0;margin:0;" width="130" src="<?php echo getImage('foto'); ?>" /></td>
            <td width="82%" valign="top">


            <table border="0" style='width: 550px;'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:260px;">ACTA DE EXAMEN PROFESIONAL No.</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:80px;"><?php echo $extraData['numeroExamenGrado']; ?></td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:140px;">AUTORIZACIÓN No.</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;"><?php echo $extraData['numeroAutorizacion']; ?></td>
                </tr>
            </table>

            <table border="0" style='width: 100%'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:21%;">EN LA CIUDAD DE</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:79%;"><?php echo $extraData['ciudad']; ?></td>
                </tr>
            </table>

            <table border="0" style='width: 100%'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:15%;">SIENDO LAS</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:35%;"><?php echo $extraData['horaLetras']; ?></td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:19%;">HORAS DEL DIA</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:31%;"><?php echo mb_strtoupper(numerosaletras(date('j', strtotime($extraData['fechaActaPosgrado'])))); ?></td>
                </tr>
            </table>

            <table border="0" style='width: 100%'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:15%;">DEL MES DE</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:40%;"><?php echo $arrMeses[date('n', strtotime($extraData['fechaActaPosgrado']))]; ?></td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:5%;">DE</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:40%;"><?php echo mb_strtoupper(numerosaletras(date('Y', strtotime($extraData['fechaActaPosgrado'])))); ?></td>
                </tr>
            </table>

            <table border="0" style='width: 100%'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:8%;">EN EL</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:92%;"><?php echo $extraData['lugarActaPosgrado']; ?></td>
                </tr>
            </table>

            <table border="0" style='width: 100%'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:8%;"><?php echo strtoupper($_indicacionInstituto); ?></td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:92%;"><?php echo strtoupper($nombreInstituto); ?></td>
                </tr>
            </table>

            <table border="0" style='width: 550px;'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:90px;">CON CLAVE:</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:120px;"><?php echo strtoupper($claveInstitulo); ?></td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify">TURNO:</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:120px;"><?php echo $turno; ?></td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;">MODALIDAD:</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:140px;"><?php echo $carreraDataModalidad; ?></td>
                </tr>
            </table>

            <table border="0" style='width: 100%'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;">SE REUNIÓ EL JURADO INTEGRADO POR LOS C.C.:</td>
                </tr>
            </table>

            <table border="0" style='width: 100%'>
                <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:left;">PRESIDENTE:</td><td style="line-height:<?php echo $lineHeight; ?>%;text-align:left;border-bottom: 1px solid #000;width:84%;"><b><?php echo mb_strtoupper(fixEncode($extraData['presidenteActaPosgrado'])); ?></b></td></tr>
                <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:left;">SECRETARIO:</td><td style="line-height:<?php echo $lineHeight; ?>%;text-align:left;border-bottom: 1px solid #000;width:84%;"><b><?php echo mb_strtoupper(fixEncode($extraData['secretarioActaPosgrado'])); ?></b></td></tr>
                <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:left;">VOCAL:</td><td style="line-height:<?php echo $lineHeight; ?>%;text-align:left;border-bottom: 1px solid #000;width:84%;"><b><?php echo mb_strtoupper(fixEncode($extraData['vocalActaPosgrado'])); ?></b></td></tr>
            </table>

            </td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;">PARA REALIZAR EL EXAMEN PROFESIONAL AL (A) C. PASANTE:</td>
            <td style="border-bottom: 1px solid #000;width:43%;">&nbsp;</td>
        </tr>
    </table>
    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;"><?php echo $nombreAlumno; ?></td>
        </tr>
    </table>
    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:26%;">CON NÚMERO DE CONTROL</td>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:24%;"><?php echo $alumnoData->col_control; ?></td>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:50%;">A QUIEN SE EXAMINÓ CON BASE EN LA OPCIÓN DE:</td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;">&nbsp;<?php echo $modoTitulacion; ?>&nbsp;</td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;">PARA OBTENER EL TITULO DE:</td>
            <td style="border-bottom: 1px solid #000;width:71%;">&nbsp;</td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;"><?php echo $carredaTitulacion; ?></td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
    <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 18px;">ACTO EFECTUADO DE ACUERDO A LAS NORMAS ESTABLECIDAS POR LA DIRECCIÓN</td></tr>
    <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 16px;">DE EDUCACIÓN SUPERIOR DE LA SUBSECRETARÍA DE EDUCACIÓN ESTATAL, UNA VEZ</td></tr>
    <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 23px;">CONCLUIDO EL EXAMEN, EL JURADO DELIBERÓ SOBRE LOS CONOCIMIENTOS Y</td></tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;">APTITUDES DEMOSTRADAS Y DETERMINÓ</td>
            <td style="border-bottom: 1px solid #000;width:60%;">&nbsp;</td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:170%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;">APROBARLO</td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 17px;">A CONTINUACIÓN EL PRESIDENTE DEL JURADO COMUNICÓ A EL(A) C. PASANTE EL</td></tr>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 11px;">RESULTADO OBTENIDO Y LE TOMÓ LA PROTESTA DE LEY EN LOS TÉRMINOS SIGUIENTES:</td></tr>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;letter-spacing: 11px;word-spacing: -12px;"><p>¿PROTESTA USTED EJERCER SU PROFESIÓN DE</p></td></tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:170%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;"><?php echo $carredaTitulacion; ?></td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 14px;">CON ENTUSIASMO Y HONRADEZ, VELAR SIEMPRE POR EL PRESTIGIO Y BUEN NOMBRE</td></tr>
        <?php if($isINEF){ ?>
            <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 16px;">DEL INSTITUTO Y CONTINUAR ESFORZÁNDOSE POR MEJORAR SU PREPARACIÓN EN</td></tr>
        <?php }else{ ?>
            <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 16px;">DE LA FACULTAD Y CONTINUAR ESFORZÁNDOSE POR MEJORAR SU PREPARACIÓN EN</td></tr>
        <?php } ?>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 18px;">TODOS LOS ÓRDENES, PARA GARANTIZAR LOS INTERESES DEL PUEBLO Y DE LA</td></tr>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 23px;">PATRIA?</td></tr>
    </table>


    <table border="0" style='width: 100%'>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;padding-top:15px;"><b>¡SI PROTESTO!</b></td></tr>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;padding-top:40px;"><p style="border-top: 1px solid #000000;padding-top:5px;">&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $nombreAlumno; ?>&nbsp;&nbsp;&nbsp;&nbsp;</p></td></tr>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;padding-top:15px;font-family: Times;font-size:12px;">SI ASÍ LO HICIERE, QUE LA SOCIEDAD Y LA NACIÓN SE LO PREMIEN Y SI NO, SE LO DEMANDEN.</td></tr>
    </table>


    <?php
    // $promGeneral = formatoPromedio($promGeneral / $i);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaDirector'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaRector'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $rectorData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaServiciosEcolares'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $escolaresData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaDirectorJuridico'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $juridicoData = $sth->fetch(PDO::FETCH_OBJ);

    $jefeServiciosEscolaresFirma = 'JEFE DEL DEPARTAMENTO DE<br/>SERVICIOS ESCOLARES';
    if(strtoupper($escolaresData->col_genero) == 'M') $jefeServiciosEscolaresFirma = 'JEFA DEL DEPARTAMENTO DE<br/>SERVICIOS ESCOLARES';

    $directorEducacionFirmaTitulo = 'DIRECTOR<br/>DE EDUCACIÓN SUPERIOR';
    if(strtoupper($directoraData->col_genero) == 'M') $directorEducacionFirmaTitulo = 'DIRECTORA<br/>DE EDUCACIÓN SUPERIOR';

    // $directorJuridicoFirmaTitulo = 'DIRECTOR GENERAL DE ASUNTOS JURÍDICOS<br/>DE GOBIERNO';
    // if(strtoupper($juridicoData->col_genero) == 'M') $directorJuridicoFirmaTitulo = 'DIRECTORA GENERAL DE ASUNTOS JURÍDICOS<br/>DE GOBIERNO';

    $directorJuridicoFirmaTitulo = 'COORDINADOR DE ASUNTOS JURÍDICOS DE<br/>GOBIERNO';
    if(strtoupper($juridicoData->col_genero) == 'M') $directorJuridicoFirmaTitulo = 'COORDINADORA DE ASUNTOS JURÍDICOS DE<br/>GOBIERNO';
    ?>
    <pagebreak>
    <table border="0" style='width: 100%;margin-top: 100px;font-size:15px;margin-bottom: 10px;'>
        <tr><td align="center" style="line-height:150%;font-weight:bold;"><span class="serif">TERMINADO EL ACTO SE LEVANTA PARA CONSTANCIA LA PRESENTE ACTA</span></td></tr>
        <?php if($isINEF){ ?>
            <tr><td align="center" style="line-height:150%;font-weight:bold;"><span class="serif">FIRMANDO DE CONFORMIDAD LOS INTEGRANTES DEL JURADO Y EL RECTOR</span></td></tr>
            <tr><td align="center" style="line-height:150%;font-weight:bold;"><span class="serif">DEL INSTITUTO QUE DA FE.</span></td></tr>
        <?php }else{ ?>
            <tr><td align="center" style="line-height:150%;font-weight:bold;"><span class="serif">FIRMANDO DE CONFORMIDAD LOS INTEGRANTES DEL JURADO Y EL RECTOR</span></td></tr>
            <tr><td align="center" style="line-height:150%;font-weight:bold;"><span class="serif">DE LA FACULTAD QUE DA FE.</span></td></tr>
        <?php } ?>
        <tr><td class="serif" style="line-height:200%;padding-top: 20px;text-align:center;font-weight:bold;">JURADO DEL EXAMEN</td></tr>
    </table>

    <table border="0" style='width: 100%;margin-top: 25px;font-size:13px;'>
        <tr>
            <td class="serif" style="width:50%;line-height:200%;text-align:center;font-weight:bold;">NOMBRE</td>
            <td style="width:15%;"></td>
            <td class="serif" style="line-height:200%;text-align:center;font-weight:bold;">FIRMA</td>
        </tr>
    </table>

    <table border="0" style='width: 100%;margin-top: 30px;font-size:13px;'>
        <tr>
            <td style="width:50%;line-height:200%;font-weight:bold;text-align:center;border-bottom: 1px solid #000000;"><?php echo mb_strtoupper(fixEncode($extraData['presidenteActaPosgrado'])); ?></td>
            <td style="width:15%;"></td>
            <td style="line-height:200%;text-align:center;border-bottom: 1px solid #000000;"></td>
        </tr>
        <tr>
            <td style="width:50%;text-align:center;font-size:13px;">PRESIDENTE</td>
            <td style="width:15%"></td>
            <td style="text-align:center;font-size:13px;">CÉDULA PROF. No. <span style="border-bottom: 1px solid #000000;padding: 0 10px;"><?php echo fixEncode($extraData['presidenteActaPosgradoCedula']); ?></span></td>
        </tr>
    </table>

    <table border="0" style='width: 100%;margin-top: 30px;font-size:13px;'>
        <tr>
            <td style="width:50%;line-height:200%;font-weight:bold;text-align:center;border-bottom: 1px solid #000000;"><?php echo mb_strtoupper(fixEncode($extraData['secretarioActaPosgrado'])); ?></td>
            <td style="width:15%;"></td>
            <td style="line-height:200%;text-align:center;border-bottom: 1px solid #000000;"></td>
        </tr>
        <tr>
            <td style="width:50%;text-align:center;font-size:13px;">SECRETARIO</td>
            <td style="width:15%"></td>
            <td style="text-align:center;font-size:13px;">CÉDULA PROF. No. <span style="border-bottom: 1px solid #000000;padding: 0 10px;"><?php echo fixEncode($extraData['secretarioActaPosgradoCedula']); ?></span></td>
        </tr>
    </table>

    <table border="0" style='width: 100%;margin-top: 30px;font-size:13px;'>
        <tr>
            <td style="width:50%;line-height:200%;font-weight:bold;text-align:center;border-bottom: 1px solid #000000;"><?php echo mb_strtoupper(fixEncode($extraData['vocalActaPosgrado'])); ?></td>
            <td style="width:15%;"></td>
            <td style="line-height:200%;text-align:center;border-bottom: 1px solid #000000;"></td>
        </tr>
        <tr>
            <td style="width:50%;text-align:center;font-size:13px;">VOCAL</td>
            <td style="width:15%"></td>
            <td style="text-align:center;font-size:13px;">CÉDULA PROF. No. <span style="border-bottom: 1px solid #000000;padding: 0 10px;"><?php echo fixEncode($extraData['vocalActaPosgradoCedula']); ?></span></td>
        </tr>
    </table>

    <table border="0" style='width: 100%;margin-top: 20px;font-size:13px;'>
        <tr><td style="line-height:200%;text-align:center;font-weight:bold;">DIRECTOR DE LA FACULTAD</td></tr>
        <tr><td style="line-height:200%;text-align:center;font-weight:bold;padding-top: 40px;">
            <p style="border-top: 1px solid #000000;font-size:13px;">&nbsp;&nbsp;<?php echo mb_strtoupper(fixEncode($rectorData->col_titulo.' '.$rectorData->col_firstname.' '.$rectorData->col_lastname));?>&nbsp;&nbsp;</p>
        </td></tr>
    </table>

    <table border="0" style='width: 100%;margin-top: 20px;font-size:13px;'>
        <tr>
            <td style="width:50%;">
                <table border="0" style='width: 100%;margin-top: 30px;font-size:13px;'>
                    <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;font-weight:bold;"><?php echo $jefeServiciosEscolaresFirma; ?></td></tr>
                    <tr><td style="line-height:200%;text-align:center;font-weight:bold;padding-top: 45px;">
                        <p style="border-top: 1px solid #000000;font-size:13px;">&nbsp;&nbsp;<?php echo mb_strtoupper(fixEncode($escolaresData->col_titulo.' '.$escolaresData->col_firstname.' '.$escolaresData->col_lastname));?>&nbsp;&nbsp;</p>
                    </td></tr>
                </table>
            </td>
            <td style="width:50%;">
                <table border="0" style='width: 100%;margin-top: 30px;font-size:13px;'>
                    <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;font-weight:bold;"><?php echo $directorEducacionFirmaTitulo; ?></td></tr>
                    <tr><td style="line-height:200%;text-align:center;font-weight:bold;padding-top: 45px;">
                        <p style="border-top: 1px solid #000000;font-size:13px;">&nbsp;&nbsp;<?php echo mb_strtoupper(fixEncode($directoraData->col_titulo.' '.$directoraData->col_firstname.' '.$directoraData->col_lastname));?>&nbsp;&nbsp;</p>
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    <br/><br/><br/><br/>
    <div style="width: 220px; text-align: center; float: left;border:1px solid #333;border-radius: 10px;">
                <p align="center" style="border-bottom: 1px solid #333;font-size:12px;font-weight:bold;padding-bottom: 10px;">REGISTRADO EN EL DEPARTAMENTO<br/>DE SERVICIOS ESCOLARES</p>
                <table border="0" width="100%" style="padding: 0 10px;">
                    <tr>
                        <td align="left" valign="top" width="30%">No.</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;font-size: 12px;"></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" width="30%">LIBRO</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;font-size: 12px;"></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" width="30%">FOJA</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;font-size: 12px;"></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" width="30%">FECHA</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;font-size: 12px;"></td>
                    </tr>
                </table>
                <p align="center" style="border-bottom: 1px solid #333;border-top: 1px solid #333;font-size: 12px;font-weight:bold;">COTEJO</p>
                <br/>
                <p align="center" style="font-size: 10px;font-weight:bold;"><?php echo fixEncode($extraData['firmaCotejo'], true, true); ?></p>
                <p align="center" style="border-bottom: 1px solid #333;border-top: 1px solid #333;font-size: 12px;font-weight:bold;"><?php echo fixEncode($extraData['tituloJefeOficina'], true, true); ?></p>
                <br/>
                <p align="center" style="font-size: 10px;font-weight:bold;"><?php echo fixEncode($extraData['jefeOficina'], true ,true); ?></p>


        </div>
        <div style="width: 400px; text-align: center;float: right;">
                <!-- <p style="font-size: 13px;font-weight:bold;">CON FUNDAMENTO EN EL ARTÍCULO 29; FRACCIÓN X DE LA LEY ORGÁNICA DE LA ADMINISTRACIÓN PÚBLICA DEL ESTADO DE CHIAPAS Y 38; FRACCIÓN IX DEL REGLAMENTO INTERIOR DE LA SECRETARÍA GENERAL DE GOBIERNO.</p>-->
                <p style="font-size: 13px;font-weight:bold;">POR ACUERDO DEL SECRETARIO GENERAL DE GOBIERNO Y CON FUNDAMENTO EN EL ART. 29, FRACCIÓN X, DE LA LEY ORGÁNICA DE LA ADMINISTRACIÓN PÚBLICA DEL ESTADO DE CHIAPAS.</p>
                <p style="font-size: 13px;font-weight:bold;">Se LEGALIZA previo cotejo con la existente en el control respectivo, la firma que antecede correspondiente al Director de Educación Superior.</p>
                <?php
                // $atencionData->col_observaciones = 'There are many issues with this. As mentioned, it fails if the string is less than the 260 character length';
                ?>
                <p style="font-size: 13px;height: 20px;border-bottom: 1px solid #222;"><?php echo substr(fixEncode($atencionData->col_observaciones), 0, 70); ?></p>
                <p style="font-size: 13px;height: 20px;border-bottom: 1px solid #222;"><?php echo substr(fixEncode($atencionData->col_observaciones), 70); ?></p>
                <p style="font-size: 13px;text-align: left;"><b>Tuxtla Gutiérrez, Chiapas, A:</b> _______________________</p>
                <table border="0" width="100%" style="padding-top: 20px;">
                    <tr><td align="center" style="font-size: 13px;font-weight:bold;padding-bottom:35px;font-weight:bold;"><?php echo $directorJuridicoFirmaTitulo; ?></td></tr>
                    <tr><td align="center" style="font-size: 13px;border-top: 1px solid #000000;font-weight:bold;"><?php echo mb_strtoupper(fixEncode($juridicoData->col_titulo.' '.$juridicoData->col_firstname.' '.$juridicoData->col_lastname));?></td></tr>
                </table>
        </div>
        <div style="width: 800px;float: none; clear:both;"></div>
        <table border="0" width="100%" style="padding-top: 20px;">
            <tr><td align="center" style="font-size: 13px;">Este documento <b>NO es válido</b> si presenta raspaduras o enmendaduras.</td></tr>
        </table>


    <?php
    $html = ob_get_contents();
    ob_end_clean();
    // {creditos}


    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db, false, true);
    $mpdf=new mPDF('s','LEGAL', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);
    // $mpdf=new mPDF('s','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);
    // $mpdf=new mPDF('c','Legal', '','', 15, 15, 10, 10);
    $mpdf->justifyB4br = true;
    $mpdf->SetHTMLHeader('');
    // $mpdf->SetHTMLFooter(pdfFooter());
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('15px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('AcataExamenLicenciatura.pdf', $output);
    }else{
        $mpdf->Output('AcataExamenLicenciatura.pdf', $output);
    }

    // die();
}

function generarActaExamenPosgrado($id, $db, $output = 'I') {
    global $nombreInstituto, $claveInstitulo, $_indicacionInstituto, $isINEF;
    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

    // $query = 'SELECT t.col_id, t.col_alumnoid, t.col_periodoid, t.col_status, p.col_grado FROM `tbl_alumnos_taxonomia` t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_baja=0 AND p.col_grado=1 AND t.col_alumnoid="'.$atencionData->col_alumnoid.'"';
    // $sth = $db->prepare($query);
    // $sth->execute();
    // $alumnoPeriodoInicial = $sth->fetch(PDO::FETCH_OBJ);

    // $query = 'SELECT t.col_id, t.col_alumnoid, t.col_periodoid, t.col_status, p.col_grado FROM `tbl_alumnos_taxonomia` t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_baja=0 AND t.col_alumnoid="'.$atencionData->col_alumnoid.'" ORDER BY p.col_grado DESC LIMIT 1';
    // $sth = $db->prepare($query);
    // $sth->execute();
    // $alumnoPeriodoFinal = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_depto="11" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$userData->col_depto.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataDepto = $sth->fetch(PDO::FETCH_OBJ);
    $nombreDepto = $dataDepto->col_nombre;
    $carreraData = getCarrera($alumnoData->col_carrera, $db);

    // $periodoData = getPeriodo($alumnoData->col_periodoid, $db, false);
    // $periodoDataInicio = getPeriodo($alumnoPeriodoInicial->col_periodoid, $db, false);
    // $periodoDataFin = getPeriodo($alumnoPeriodoFinal->col_periodoid, $db, false);

    $extraData['fechaVigencia'] = $carreraData['vigencia_revoe_date'];

    $laModalidad = $carreraData['modalidad'];
    if($carreraData['modalidad'] == 'Semestral' || $carreraData['modalidad'] == 'Cuatrimestral') {
        $laModalidad = 'Licenciatura';
    }

    if($carreraData['modalidad'] == 'Semestral') $carreraMod = 'SEMESTRE';
    if($carreraData['modalidad'] == 'Cuatrimestral') $carreraMod = 'CUATRIMESTRE';
    $promGeneral = 0;
    $creditosGanados = 0;
    // $carreraData['modalidad'] = 'ESCOLARIZADA';

    $carreraDataModalidad = 'ESCOLARIZADA';
    if($carreraData['modalidad'] == 'Cuatrimestral' || $carreraData['modalidad_periodo'] == 'master' || $carreraData['modalidad_periodo'] == 'docto') $carreraDataModalidad = 'MIXTA';

    $turno = 'VESPERTINO';
    if($carreraData['modalidad'] == 'Cuatrimestral' || $carreraData['modalidad_periodo'] == 'master' || $carreraData['modalidad_periodo'] == 'docto') $turno = 'MIXTO';

    $elLa = 'LA';
    if($alumnoData->col_genero != 'M') $elLa = 'EL';

    $nombreAlumno = mb_strtoupper(fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos));

    $opcionesTitulacion[1] = "PROMEDIO GENERAL DE CALIFICACIONES";
    $opcionesTitulacion[2] = "ESTUDIOS DE POSGRADO (50% DE MAESTRÍA)";
    $opcionesTitulacion[3] = "SUSTENTACIÓN DE EXAMEN POR ÁREAS DE CONOCIMIENTO";
    $opcionesTitulacion[17] = "SUSTENTACIÓN DE EXAMEN POR ÁREAS DE CONOCIMIENTO (CENEVAL)";
    $opcionesTitulacion[4] = "CENEVAL";
    $opcionesTitulacion[5] = "TESIS PROFESIONAL";
    $opcionesTitulacion[6] = "CURSO DE TITULACIÓN";
    $opcionesTitulacion[7] = "INFORME O MEMORIA DE PRESTACIÓN DEL SERVICIO SOCIAL OBLIGATORIO";
    $opcionesTitulacion[8] = "MEMORIA DE EXPERIENCIA PROFESIONAL";
    $opcionesTitulacion[9] = "PRODUCCIÓN DE UNA UNIDAD AUDIOVISUAL, ELABORACIÓN DE TEXTOS, PROTOTIPOS DIDÁCTICOS O INSTRUCTIVOS PARA PRESENTACIONES DE UNIDADES TEMÁTICAS O PRÁCTICAS DE LABORATORIO O TALLER";
    $opcionesTitulacion[10] = "TESIS INDIVIDUAL Y RÉPLICA ORAL";
    $opcionesTitulacion[11] = "TESIS COLECTIVA Y RÉPLICA ORAL";
    $opcionesTitulacion[12] = "POR EL 50%  (CINCUENTA POR CIENTO) DE CRÉDITOS DE DOCTORADO";
    $opcionesTitulacion[13] = "EXAMEN GENERAL DE CONOCIMIENTOS";
    $opcionesTitulacion[14] = "POR PROMEDIO";
    $opcionesTitulacion[15] = "TESIS INDIVIDUAL Y REPLICA ORAL";
    $opcionesTitulacion[16] = "PROMEDIO GENERAL DE CALIFICACIONES";

    $modoTitulacion = $opcionesTitulacion[$alumnoData->col_opciones_titulacion];
    if($alumnoData->col_opciones_titulacion == 1){
        $promedioFinal = getPromedioFinalAlumno($atencionData->col_alumnoid, $db);
        $modoTitulacion = $opcionesTitulacion[$alumnoData->col_opciones_titulacion].' '.$promedioFinal.' ('.mb_strtoupper(numerosaletras($promedioFinal)).')';
    }

    if($carreraData['modalidad_periodo'] == 'docto' && $alumnoData->col_opciones_titulacion == 16) {

    }

    $ala = 'A LA';
    if(strtolower($alumnoData->col_genero) == 'h') $ala = 'AL';

    $tipoDoc = 'LICENCIADA';
    if(strtolower($alumnoData->col_genero) == 'h') $tipoDoc = 'LICENCIADO';
    $tipoEntrega = 'Titulo';

    if($carreraData['modalidad_periodo'] == 'master') {
        $tipoDoc = 'MAESTRA';
        $tipoEntrega = 'GRADO';
        if(strtolower($alumnoData->col_genero) == 'h') $tipoDoc = 'MAESTRO';
    }

    if($carreraData['modalidad_periodo'] == 'docto') {
        $tipoDoc = 'DOCTORA';
        $tipoEntrega = 'GRADO';
        if(strtolower($alumnoData->col_genero) == 'h') $tipoDoc = 'DOCTOR';
    }

    $carredaTitulacion = $tipoDoc.' EN '.mb_strtoupper($carreraData['nombreLimpio']);

    $minimaAprobatoria = 'MÍNIMA APROBATORIA 7 (SIETE)';
    if($carreraData['modalidad'] == 'Doctorado') $minimaAprobatoria = 'MÍNIMA APROBATORIA 8 (OCHO)';

    $arrMeses = array('ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE');
    $laFecha = undelined(mb_strtoupper(numerosaletras(date('j', strtotime($extraData['fechaActaPosgrado'])))));
    $laFecha .= ' DEL MES DE ';
    $laFecha .= undelined($arrMeses[date('n', strtotime($extraData['fechaActaPosgrado']))]);
    $laFecha .= ' DE ';
    $laFecha .= undelined(mb_strtoupper(numerosaletras(date('Y', strtotime($extraData['fechaActaPosgrado'])))));
    $lineHeight = 150;

    ob_start();
    ?>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td colspan="2" align="right" style="padding-bottom:12px;"><small>AEG-16-<?php echo date('Y', strtotime($atencionData->col_fecha)); ?></small></td>
        </tr>
        <tr>
            <td width="13%" valign="top" align="center" style="padding-top:15px;"><img width="106" src="<?php echo getLogo('gobernacion'); ?>" /></td>
            <td width="87%" valign="top" align="center">
                <p style="font-size:20px;font-weight:bold;">GOBIERNO CONSTITUCIONAL DEL ESTADO DE CHIAPAS</p>
                <p style="font-size:20px;font-weight:bold;">SECRETARÍA DE EDUCACIÓN</p>
                <p style="font-size:20px;">SUBSECRETARÍA DE EDUCACIÓN ESTATAL<br/>DIRECCIÓN DE EDUCACIÓN SUPERIOR<br/>DEPARTAMENTO DE SERVICIOS ESCOLARES</p>
            </td>
        </tr>
        <tr>
            <td width="15%" valign="top" align="left"></td>
            <td width="85%" align="center" style="font-size:12px;word-spacing: -1px;">RVOE:ACUERDO NÚMERO <?php echo $carreraData['revoe']?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;VIGENCIA:A PARTIR DEL <?php echo strtoupper(fechaTexto($extraData['fechaVigencia'], 'j \d\e F \d\e Y')); ?><br/>REGIMEN:PARTICULAR</td>
        </tr>
        <tr>
            <td width="100%" colspan="2" valign="top" align="right">
                <br/>
                <p style="font-size:14px;font-weight:bold;">No: <i style="font-style:normal;border-bottom: 2px solid #222;color:#cc0000;">&nbsp;&nbsp;<?php echo $atencionData->col_folio; ?>&nbsp;&nbsp;</i></p>
            </td>
        </tr>
    </table>

    <table width="100%" border="0" style="font-size:15px;margin-top: 20px;margin-bottom: 10px;" cellpadding="0" cellspacing="0">
        <tr>
            <td width="18%" valign="top" align="left"><img align="left" style="padding:0;margin:0;" width="130" src="<?php echo getImage('foto'); ?>" /></td>
            <td width="82%" valign="top">

            <table border="0" style='width: 100%'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:38%;">ACTA DE EXAMEN DE GRADO No.</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;"><?php echo $extraData['numeroExamenGrado']; ?></td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:23%;">AUTORIZACIÓN No.</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;"><?php echo $extraData['numeroAutorizacion']; ?></td>
                </tr>
            </table>

            <table border="0" style='width: 100%'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:21%;">EN LA CIUDAD DE</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:79%;"><?php echo $extraData['ciudad']; ?></td>
                </tr>
            </table>

            <table border="0" style='width: 100%'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:15%;">SIENDO LAS</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:35%;"><?php echo $extraData['horaLetras']; ?></td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:19%;">HORAS DEL DIA</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:31%;"><?php echo mb_strtoupper(numerosaletras(date('j', strtotime($extraData['fechaActaPosgrado'])))); ?></td>
                </tr>
            </table>

            <table border="0" style='width: 100%'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:15%;">DEL MES DE</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:40%;"><?php echo $arrMeses[date('n', strtotime($extraData['fechaActaPosgrado']))]; ?></td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:5%;">DE</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:40%;"><?php echo mb_strtoupper(numerosaletras(date('Y', strtotime($extraData['fechaActaPosgrado'])))); ?></td>
                </tr>
            </table>

            <table border="0" style='width: 100%'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:8%;">EN EL</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:92%;"><?php echo $extraData['lugarActaPosgrado']; ?></td>
                </tr>
            </table>

            <table border="0" style='width: 100%'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:8%;"><?php echo strtoupper($_indicacionInstituto); ?></td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:92%;"><?php echo strtoupper($nombreInstituto); ?></td>
                </tr>
            </table>

            <table border="0" style='width: 550px;'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:90px;">CON CLAVE:</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:120px;"><?php echo strtoupper($claveInstitulo); ?></td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify">TURNO:</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:120px;"><?php echo $turno; ?></td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;">MODALIDAD:</td>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:140px;"><?php echo $carreraDataModalidad; ?></td>
                </tr>
            </table>

            <table border="0" style='width: 100%'>
                <tr>
                    <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;">SE REUNIÓ EL JURADO INTEGRADO POR LOS C.C.:</td>
                </tr>
            </table>

            <table border="0" style='width: 100%'>
                <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:left;">PRESIDENTE:</td><td style="line-height:<?php echo $lineHeight; ?>%;text-align:left;border-bottom: 1px solid #000;width:84%;"><b><?php echo mb_strtoupper(fixEncode($extraData['presidenteActaPosgrado'])); ?></b></td></tr>
                <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:left;">SECRETARIO:</td><td style="line-height:<?php echo $lineHeight; ?>%;text-align:left;border-bottom: 1px solid #000;width:84%;"><b><?php echo mb_strtoupper(fixEncode($extraData['secretarioActaPosgrado'])); ?></b></td></tr>
                <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:left;">VOCAL:</td><td style="line-height:<?php echo $lineHeight; ?>%;text-align:left;border-bottom: 1px solid #000;width:84%;"><b><?php echo mb_strtoupper(fixEncode($extraData['vocalActaPosgrado'])); ?></b></td></tr>
            </table>

            </td>
        </tr>
    </table>
    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;">PARA REALIZAR EL EXAMEN DE GRADO AL (A) C. SUSTENTATE:</td>
            <td style="border-bottom: 1px solid #000;width:41%;">&nbsp;</td>
        </tr>
    </table>
    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;"><?php echo $nombreAlumno; ?></td>
        </tr>
    </table>
    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:26%;">CON NÚMERO DE CONTROL</td>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;width:24%;"><?php echo $alumnoData->col_control; ?></td>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;width:50%;">A QUIEN SE EXAMINÓ CON BASE EN LA OPCIÓN DE:</td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;">&nbsp;<?php echo $modoTitulacion; ?>&nbsp;</td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;">PARA OBTENER EL GRADO DE:</td>
            <td style="border-bottom: 1px solid #000;width:71%;">&nbsp;</td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;"><?php echo $carredaTitulacion; ?></td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
    <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 18px;">ACTO EFECTUADO DE ACUERDO A LAS NORMAS ESTABLECIDAS POR LA DIRECCIÓN</td></tr>
    <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 16px;">DE EDUCACIÓN SUPERIOR DE LA SUBSECRETARÍA DE EDUCACIÓN ESTATAL, UNA VEZ</td></tr>
    <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 23px;">CONCLUIDO EL EXAMEN, EL JURADO DELIBERÓ SOBRE LOS CONOCIMIENTOS Y</td></tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;">APTITUDES DEMOSTRADAS Y DETERMINÓ</td>
            <td style="border-bottom: 1px solid #000;width:60%;">&nbsp;</td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:170%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;">APROBARLO</td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 14px;">A CONTINUACIÓN EL PRESIDENTE DEL JURADO COMUNICÓ A EL(A) C. SUSTENTANTE EL</td></tr>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 11px;">RESULTADO OBTENIDO Y LE TOMÓ LA PROTESTA DE LEY EN LOS TÉRMINOS SIGUIENTES:</td></tr>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;letter-spacing: 13px;word-spacing: -12px;"><p>¿PROTESTA USTED EJERCER EL GRADO DE</p></td></tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr>
            <td style="line-height:170%;text-align:center;border-bottom: 1px solid #000;font-weight:bold;"><?php echo $carredaTitulacion; ?></td>
        </tr>
    </table>

    <table border="0" style='width: 100%'>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 14px;">CON ENTUSIASMO Y HONRADEZ, VELAR SIEMPRE POR EL PRESTIGIO Y BUEN NOMBRE</td></tr>
        <?php if($isINEF){ ?>
            <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 16px;">DEL INSTITUTO Y CONTINUAR ESFORZÁNDOSE POR MEJORAR SU PREPARACIÓN EN</td></tr>
        <?php }else{ ?>
            <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 16px;">DE LA FACULTAD Y CONTINUAR ESFORZÁNDOSE POR MEJORAR SU PREPARACIÓN EN</td></tr>
        <?php } ?>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 18px;">TODOS LOS ÓRDENES, PARA GARANTIZAR LOS INTERESES DEL PUEBLO Y DE LA</td></tr>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align: justify;word-spacing: 23px;">PATRIA?</td></tr>
    </table>


    <table border="0" style='width: 100%'>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;padding-top:15px;"><b>¡SI PROTESTO!</b></td></tr>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;padding-top:40px;"><p style="border-top: 1px solid #000000;padding-top:5px;">&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $nombreAlumno; ?>&nbsp;&nbsp;&nbsp;&nbsp;</p></td></tr>
        <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;padding-top:15px;font-family: Times;font-size:12px;">SI ASÍ LO HICIERE, QUE LA SOCIEDAD Y LA NACIÓN SE LO PREMIEN Y SI NO, SE LO DEMANDEN.</td></tr>
    </table>


    <?php
    // $promGeneral = formatoPromedio($promGeneral / $i);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaDirector'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $directoraData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaRector'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $rectorData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaServiciosEcolares'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $escolaresData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$extraData['firmaDirectorJuridico'].'" LIMIT 1 ';
    $sth = $db->prepare($query);
    $sth->execute();
    $juridicoData = $sth->fetch(PDO::FETCH_OBJ);

    $jefeServiciosEscolaresFirma = 'JEFE DEL DEPARTAMENTO DE<br/>SERVICIOS ESCOLARES';
    if(strtoupper($escolaresData->col_genero) == 'M') $jefeServiciosEscolaresFirma = 'JEFA DEL DEPARTAMENTO DE<br/>SERVICIOS ESCOLARES';

    $directorEducacionFirmaTitulo = 'DIRECTOR<br/>DE EDUCACIÓN SUPERIOR';
    if(strtoupper($directoraData->col_genero) == 'M') $directorEducacionFirmaTitulo = 'DIRECTORA<br/>DE EDUCACIÓN SUPERIOR';

    // $directorJuridicoFirmaTitulo = 'DIRECTOR GENERAL DE ASUNTOS JURÍDICOS<br/>DE GOBIERNO';
    // if(strtoupper($juridicoData->col_genero) == 'M') $directorJuridicoFirmaTitulo = 'DIRECTORA GENERAL DE ASUNTOS JURÍDICOS<br/>DE GOBIERNO';

    $directorJuridicoFirmaTitulo = 'COORDINADORA DE ASUNTOS JURÍDICOS DE<br/>GOBIERNO';
    if(strtoupper($juridicoData->col_genero) == 'M') $directorJuridicoFirmaTitulo = 'COORDINADORA DE ASUNTOS JURÍDICOS DE<br/>GOBIERNO';
    ?>
    <pagebreak>
    <table border="0" style='width: 100%;margin-top: 100px;font-size:15px;margin-bottom: 10px;'>
        <tr><td align="center" style="line-height:150%;font-weight:bold;"><span class="serif">TERMINADO EL ACTO SE LEVANTA PARA CONSTANCIA LA PRESENTE ACTA</span></td></tr>
        <tr><td align="center" style="line-height:150%;font-weight:bold;"><span class="serif">FIRMANDO DE CONFORMIDAD LOS INTEGRANTES DEL JURADO Y EL RECTOR</span></td></tr>
        <?php if($isINEF){ ?>
            <tr><td align="center" style="line-height:150%;font-weight:bold;"><span class="serif">DEL INSTITUTO QUE DA FE.</span></td></tr>
        <?php }else{ ?>
            <tr><td align="center" style="line-height:150%;font-weight:bold;"><span class="serif">DE LA FACULTAD QUE DA FE.</span></td></tr>
        <?php } ?>
        <tr><td class="serif" style="line-height:200%;padding-top: 20px;text-align:center;font-weight:bold;">JURADO DEL EXAMEN</td></tr>
    </table>

    <table border="0" style='width: 100%;margin-top: 25px;font-size:13px;'>
        <tr>
            <td class="serif" style="width:50%;line-height:200%;text-align:center;font-weight:bold;">NOMBRE</td>
            <td style="width:15%;"></td>
            <td class="serif" style="line-height:200%;text-align:center;font-weight:bold;">FIRMA</td>
        </tr>
    </table>

    <table border="0" style='width: 100%;margin-top: 30px;font-size:13px;'>
        <tr>
            <td style="width:50%;line-height:200%;font-weight:bold;text-align:center;border-bottom: 1px solid #000000;"><?php echo mb_strtoupper(fixEncode($extraData['presidenteActaPosgrado'])); ?></td>
            <td style="width:15%;"></td>
            <td style="line-height:200%;text-align:center;border-bottom: 1px solid #000000;"></td>
        </tr>
        <tr>
            <td style="width:50%;text-align:center;font-size:13px;">PRESIDENTE</td>
            <td style="width:15%"></td>
            <td style="text-align:center;font-size:13px;">CÉDULA PROF. No. <span style="border-bottom: 1px solid #000000;padding: 0 10px;"><?php echo fixEncode($extraData['presidenteActaPosgradoCedula']); ?></span></td>
        </tr>
    </table>

    <table border="0" style='width: 100%;margin-top: 30px;font-size:13px;'>
        <tr>
            <td style="width:50%;line-height:200%;font-weight:bold;text-align:center;border-bottom: 1px solid #000000;"><?php echo mb_strtoupper(fixEncode($extraData['secretarioActaPosgrado'])); ?></td>
            <td style="width:15%;"></td>
            <td style="line-height:200%;text-align:center;border-bottom: 1px solid #000000;"></td>
        </tr>
        <tr>
            <td style="width:50%;text-align:center;font-size:13px;">SECRETARIO</td>
            <td style="width:15%"></td>
            <td style="text-align:center;font-size:13px;">CÉDULA PROF. No. <span style="border-bottom: 1px solid #000000;padding: 0 10px;"><?php echo fixEncode($extraData['secretarioActaPosgradoCedula']); ?></span></td>
        </tr>
    </table>

    <table border="0" style='width: 100%;margin-top: 30px;font-size:13px;'>
        <tr>
            <td style="width:50%;line-height:200%;font-weight:bold;text-align:center;border-bottom: 1px solid #000000;"><?php echo mb_strtoupper(fixEncode($extraData['vocalActaPosgrado'])); ?></td>
            <td style="width:15%;"></td>
            <td style="line-height:200%;text-align:center;border-bottom: 1px solid #000000;"></td>
        </tr>
        <tr>
            <td style="width:50%;text-align:center;font-size:13px;">VOCAL</td>
            <td style="width:15%"></td>
            <td style="text-align:center;font-size:13px;">CÉDULA PROF. No. <span style="border-bottom: 1px solid #000000;padding: 0 10px;"><?php echo fixEncode($extraData['vocalActaPosgradoCedula']); ?></span></td>
        </tr>
    </table>

    <table border="0" style='width: 100%;margin-top: 20px;font-size:13px;'>
        <tr><td style="line-height:200%;text-align:center;font-weight:bold;">RECTOR DE LA FACULTAD</td></tr>
        <tr><td style="line-height:200%;text-align:center;font-weight:bold;padding-top: 40px;">
            <p style="border-top: 1px solid #000000;font-size:13px;">&nbsp;&nbsp;<?php echo mb_strtoupper(fixEncode($rectorData->col_titulo.' '.$rectorData->col_firstname.' '.$rectorData->col_lastname));?>&nbsp;&nbsp;</p>
        </td></tr>
    </table>

    <table border="0" style='width: 100%;margin-top: 20px;font-size:13px;'>
        <tr>
            <td style="width:50%;">
                <table border="0" style='width: 100%;margin-top: 30px;font-size:13px;'>
                    <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;font-weight:bold;"><?php echo $jefeServiciosEscolaresFirma; ?></td></tr>
                    <tr><td style="line-height:200%;text-align:center;font-weight:bold;padding-top: 45px;">
                        <p style="border-top: 1px solid #000000;font-size:13px;">&nbsp;&nbsp;<?php echo mb_strtoupper(fixEncode($escolaresData->col_titulo.' '.$escolaresData->col_firstname.' '.$escolaresData->col_lastname));?>&nbsp;&nbsp;</p>
                    </td></tr>
                </table>
            </td>
            <td style="width:50%;">
                <table border="0" style='width: 100%;margin-top: 30px;font-size:13px;'>
                    <tr><td style="line-height:<?php echo $lineHeight; ?>%;text-align:center;font-weight:bold;"><?php echo $directorEducacionFirmaTitulo; ?></td></tr>
                    <tr><td style="line-height:200%;text-align:center;font-weight:bold;padding-top: 45px;">
                        <p style="border-top: 1px solid #000000;font-size:13px;">&nbsp;&nbsp;<?php echo mb_strtoupper(fixEncode($directoraData->col_titulo.' '.$directoraData->col_firstname.' '.$directoraData->col_lastname));?>&nbsp;&nbsp;</p>
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    <br/><br/><br/><br/>
    <div style="width: 220px; text-align: center; float: left;border:1px solid #333;border-radius: 10px;">
                <p align="center" style="border-bottom: 1px solid #333;font-size:12px;font-weight:bold;padding-bottom: 10px;">REGISTRADO EN EL DEPARTAMENTO<br/>DE SERVICIOS ESCOLARES</p>
                <table border="0" width="100%" style="padding: 0 10px;">
                    <tr>
                        <td align="left" valign="top" width="30%">No.</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;font-size: 12px;"></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" width="30%">LIBRO</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;font-size: 12px;"></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" width="30%">FOJA</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;font-size: 12px;"></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" width="30%">FECHA</td>
                        <td valign="top" width="70%" style="border-bottom:1px solid #222;font-size: 12px;"></td>
                    </tr>
                </table>
                <p align="center" style="border-bottom: 1px solid #333;border-top: 1px solid #333;font-size: 12px;font-weight:bold;">COTEJO</p>
                <br/>
                <p align="center" style="font-size: 10px;font-weight:bold;"><?php echo fixEncode($extraData['firmaCotejo'], true, true); ?></p>
                <p align="center" style="border-bottom: 1px solid #333;border-top: 1px solid #333;font-size: 12px;font-weight:bold;"><?php echo fixEncode($extraData['tituloJefeOficina'], true, true); ?></p>
                <br/>
                <p align="center" style="font-size: 10px;font-weight:bold;"><?php echo fixEncode($extraData['jefeOficina'], true ,true); ?></p>


        </div>
        <div style="width: 400px; text-align: center;float: right;">
                <!-- <p style="font-size: 13px;font-weight:bold;">CON FUNDAMENTO EN EL ARTÍCULO 29; FRACCIÓN X DE LA LEY ORGÁNICA DE LA ADMINISTRACIÓN PÚBLICA DEL ESTADO DE CHIAPAS Y 38; FRACCIÓN IX DEL REGLAMENTO INTERIOR DE LA SECRETARÍA GENERAL DE GOBIERNO.</p>-->
                <p style="font-size: 13px;font-weight:bold;">POR ACUERDO DEL SECRETARIO GENERAL DE GOBIERNO Y CON FUNDAMENTO EN EL ART. 29, FRACCIÓN X, DE LA LEY ORGÁNICA DE LA ADMINISTRACIÓN PÚBLICA DEL ESTADO DE CHIAPAS.</p>
                <p style="font-size: 13px;font-weight:bold;">Se LEGALIZA previo cotejo con la existente en el control respectivo, la firma que antecede correspondiente al Director de Educación Superior.</p>
                <?php
                // $atencionData->col_observaciones = 'There are many issues with this. As mentioned, it fails if the string is less than the 260 character length';
                ?>
                <p style="font-size: 13px;height: 20px;border-bottom: 1px solid #222;"><?php echo substr(fixEncode($atencionData->col_observaciones), 0, 70); ?></p>
                <p style="font-size: 13px;height: 20px;border-bottom: 1px solid #222;"><?php echo substr(fixEncode($atencionData->col_observaciones), 70); ?></p>
                <p style="font-size: 13px;text-align: left;"><b>Tuxtla Gutiérrez, Chiapas, A:</b> _______________________</p>
                <table border="0" width="100%" style="padding-top: 20px;">
                    <tr><td align="center" style="font-size: 13px;font-weight:bold;padding-bottom:35px;font-weight:bold;"><?php echo $directorJuridicoFirmaTitulo; ?></td></tr>
                    <tr><td align="center" style="font-size: 13px;border-top: 1px solid #000000;font-weight:bold;"><?php echo mb_strtoupper(fixEncode($juridicoData->col_titulo.' '.$juridicoData->col_firstname.' '.$juridicoData->col_lastname));?></td></tr>
                </table>
        </div>
        <div style="width: 800px;float: none; clear:both;"></div>
        <table border="0" width="100%" style="padding-top: 20px;">
            <tr><td align="center" style="font-size: 13px;">Este documento <b>NO es válido</b> si presenta raspaduras o enmendaduras.</td></tr>
        </table>


    <?php
    $html = ob_get_contents();
    ob_end_clean();


    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db, false, true);
    $mpdf=new mPDF('s','LEGAL', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);
    // $mpdf=new mPDF('c','Legal', '','', 15, 15, 10, 10);

    $mpdf->justifyB4br = true;

    $mpdf->SetHTMLHeader('');
    // $mpdf->SetHTMLFooter(pdfFooter());
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('15px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('AcataExamenPosgrado.pdf', $output);
    }else{
        $mpdf->Output('AcataExamenPosgrado.pdf', $output);
    }

    // die();
}

function generarConstanciaDesempeno($id, $db, $output = 'I') {
    global $opcionesTitulacion, $totalCreditos;
    global $nombreInstituto, $claveInstitulo, $_indicacionInstituto;

    $atencionid = intval($id);
    $query = 'SELECT * FROM tbl_atencion WHERE col_id="'.$atencionid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $atencionData = $sth->fetch(PDO::FETCH_OBJ);

    $extraData = fixSpaces(unserialize($atencionData->col_extra));

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$atencionData->col_alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$atencionData->col_firma_userid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $userData = $sth->fetch(PDO::FETCH_OBJ);

    $carreraData = getCarrera($alumnodData->col_carrera, $db);

    $nombreDepto = 'Directora General';
    if(strtolower($userData->col_genero) == 'h') $nombreDepto = 'Director General';


    ob_start();
    ?>
    <div style="text-align: center;margin-bottom: 40px;"><img src="<?php echo getLogo('big'); ?>" style="width: autopx;height:120px;" alt="FLDCH" border="0"/></div>
    <div style="position:absolute;top: 50px;left:15px;height:970px;"><img src="<?php echo getLogo('borde_izq'); ?>"border="0"/></div>
    <div style="position:absolute;top: 50px;right:15px;height:970px;"><img src="<?php echo getLogo('borde_der'); ?>"border="0"/></div>
    <table border="0" width="100%">
        <tr>
            <td align="right"><span style="color: #cc0000;"><?php echo undelinedRed($atencionData->col_folio); ?></span></td>
        </tr>
    </table><br/>
    <div style="text-align: center;margin-bottom: 35px;">Otorga a</div>
    <div style="text-align: center;margin-bottom: 35px;text-decoration:underline;"><?php echo mb_strtoupper(fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true), 'UTF-8'); ?></div>
    <div style="text-align: center;margin-bottom: 35px;">el presente</div>
    <?php if($extraData['satisfactorio'] == 1) { ?>
    <div style="font-size:24px;text-align: center;margin-bottom: 25px;font-weight:bold;">Testimonio de Desempeño Satisfactorio</div>
    <?php } else { ?>
    <div style="font-size:24px;text-align: center;margin-bottom: 25px;font-weight:bold;">Testimonio de Desempeño<br/>No Satisfactorio</div>
    <?php } ?>
    <div style="text-align: center;margin-bottom: 30px;">Obtenido en el Examen General de<br/>Conocimientos para el Egreso de la Licenciatura en</div>
    <div style="text-align: center;margin-bottom: 30px;"><?php echo $carreraData['nombreLimpio']; ?></div>

    <div style="text-align: center;margin-bottom: 30px;">conforme a los requerimientos establecidos por la<br/>FLDCH</div>
    <div style="text-align: center;">Tuxtla Gutiérrez, Chiapas. A <?php echo date('d', strtotime($atencionData->col_fecha)); ?> de <?php echo strtolower(getMes(date('F', strtotime($atencionData->col_fecha)))); ?> de <?php echo date('Y', strtotime($atencionData->col_fecha)); ?></div>
    <br/><br/>
    <table border="0" width="100%">
        <tr>
            <td width="20%"></td>
            <td align="center" valign="top" width="60%" style="text-transform: uppercase;font-size:13pt;">
            <?php echo fixEncode($userData->col_titulo.' '.$userData->col_firstname.' '.$userData->col_lastname, true); ?><br/><small><?php echo fixEncode($nombreDepto); ?></small></td>
            <td width="20%"></td>
        </tr>
    </table>

    <?php
    $html = ob_get_contents();
    ob_end_clean();



    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $m = getMargins($db);
    // $mpdf=new mPDF('c','Letter', '','', $m['left'], $m['right'], $m['top'], 0, 0, $m['bottom']);
    $mpdf=new mPDF('s','Letter', '','', $m['left'], $m['right'], 5, 35, $m['top'], $m['bottom']);

    $mpdf->SetHTMLHeader('');
    // $mpdf->SetHTMLFooter(pdfFooter());
    $mpdf->SetHTMLFooter('');

    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

    $mpdf->WriteHTML(pdfCSS('23px', '23px'), 1);
    $mpdf->WriteHTML($html, 2);


    if($output == 'S') {
        return $mpdf->Output('constancia-desempeno.pdf', $output);
    }else{
        $mpdf->Output('constancia-desempeno.pdf', $output);
    }

    // die();
}

// Termina atencionArchivos.php
?>
