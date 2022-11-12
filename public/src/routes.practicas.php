<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de practicas profesionales.
 *
 * Lista de funciones
 *
 * /practicas
 * - /set
 * - /listReportes
 * - /listHistorial
 * - /borrar
 * - /updateComments
 * - /agregar
 * - /getInfo
 * - /getOficio
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

/*
 Al aprobar llenar el campo next
*/

$app->group('/practicas', function () {

    $this->get('/set', function (Request $request, Response $response, array $args) {
        global $dblog;
        if(esRepresentante()) {
            $_response['status'] = 'false';
            $_response['reason'] = 'Error representante';
            return $this->response->withJson($_response);
        }
        $query = "SELECT * FROM tbl_practicas_archivos WHERE col_id='".intval($_REQUEST['idreporte'])."' AND col_practicaid='".intval($_REQUEST['id'])."'";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        $next = date('Y-m-d', strtotime('next thursday'));

        $query = 'UPDATE tbl_practicas_archivos SET col_estatus="'.intval($_REQUEST['status']).'", col_next="'.$next.'" WHERE col_id="'.$data->col_id.'"';

        $dblog = new DBLog($query, 'tbl_practicas_archivos', '', '', 'Practicas', $this->db);
        $dblog->where = array('col_id' => intval($data->col_id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();
        $dblog->saveLog();

        $_response['status'] = 'true';

        return $this->response->withJson($_response);
    });


    $this->get('/listReportes', function (Request $request, Response $response, array $args) {
        global $download_url;

        // $alumnoid = getCurrentUserID();
        // $periodoid = getLastPeriodoAlumno(intval($alumnoid), $this->db);
        $periodosActivos = getCurrentPeriodos($this->db);
        $query = "SELECT a.*, p.col_lugar AS lugar, p.col_periodoid AS periodoID FROM tbl_practicas p LEFT OUTER JOIN tbl_alumnos a ON a.col_id=p.col_alumnoid WHERE p.col_id='".intval($_REQUEST['id'])."'";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

        $periodoData = getPeriodo($alumnoData->periodoID, $this->db, false);

        $query = "SELECT * FROM tbl_practicas_archivos WHERE col_practicaid='".intval($_REQUEST['id'])."' ORDER BY col_id DESC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){


            $result[$i]['id'] = $item['col_id'];
            $result[$i]['comentarios'] = $item['col_comentarios'];
            $result[$i]['fecha'] = fechaTexto($item['col_created_at']).' '.substr($item['col_created_at'], 11, strlen($item['col_created_at']));
            $result[$i]['descargar'] = '<a class="text-primary" href="'.$download_url.'practicas/'.$item['col_archivo'].'" target="_blank"><i class="fas fa-file"></i> Descargar</a>';
// 0: En Revision
// 1: Rechazado
// 2; Aprobado
// 3: Extemporaneo
// 4: Falsificado
// 5: Liberación

            $result[$i]['aprobar'] =  '<span class="'.($item['col_estatus'] == 0?'text-white badge badge-warning':'text-secondary').'" title="En Revisión"><i class="fas fa-check-circle"></i> En Revisión</span>';
            $result[$i]['aprobar'] .= '<br/><a class="'.($item['col_estatus'] == 2?'text-white badge badge-success':'text-secondary').'" title="Aprobado" href="#/pages/alumnos/reportes-practicas/'.intval($_REQUEST['id']).'/aprobado/'.intval($item['col_id']).'"><i class="fas fa-check-circle"></i> Aprobado</a>';
            $result[$i]['aprobar'] .= '<br/><a class="'.($item['col_estatus'] == 1?'text-white badge badge-danger':'text-secondary').'" title="Rechazado" href="#/pages/alumnos/reportes-practicas/'.intval($_REQUEST['id']).'/rechazado/'.intval($item['col_id']).'"><i class="fas fa-times-circle"></i> Rechazado</a>';
            $result[$i]['aprobar'] .= '<br/><a class="'.($item['col_estatus'] == 3?'text-white badge badge-info':'text-secondary').'" title="Extemporaneo" href="#/pages/alumnos/reportes-practicas/'.intval($_REQUEST['id']).'/extemporaneo/'.intval($item['col_id']).'"><i class="fas fa-check-square"></i> Extemporaneo</a>';
            $result[$i]['aprobar'] .= '<br/><a class="'.($item['col_estatus'] == 4?'text-white badge badge-danger':'text-secondary').'" title="Falsificación" href="#/pages/alumnos/reportes-practicas/'.intval($_REQUEST['id']).'/falsificacion/'.intval($item['col_id']).'"><i class="fas fa-exclamation-triangle"></i> Falsificación</a>';
            $result[$i]['aprobar'] .= '<br/><a class="'.($item['col_estatus'] == 5?'text-white badge badge-success':'text-secondary').'" title="Carta de Liberación" href="#/pages/alumnos/reportes-practicas/'.intval($_REQUEST['id']).'/liberacion/'.intval($item['col_id']).'"><i class="fas fa-check-double"></i> Carta de Liberación Entregada</a>';


            // if($item['col_estatus'] == 0) $result[$i]['aprobar'] .= '<br/><br/>Estatus Actual: <span class="badge badge-warning"><i class="fas fa-clock"></i> Esperando Revisión</span>';
            // if($item['col_estatus'] == 1) $result[$i]['aprobar'] .= '<br/><br/>Estatus Actual: <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Rechazado</span>';
            // if($item['col_estatus'] == 2) $result[$i]['aprobar'] .= '<br/><br/>Estatus Actual: <span class="badge badge-success"><i class="fas fa-check-circle"></i> Aprobado</span>';
            // if($item['col_estatus'] == 3) $result[$i]['aprobar'] .= '<br/><br/>Estatus Actual: <span class="badge badge-info"><i class="fas fa-check-square"></i> Extemporaneo</span>';
            // if($item['col_estatus'] == 4) $result[$i]['aprobar'] .= '<br/><br/>Estatus Actual: <span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> Falsificación</span>';
            // if($item['col_estatus'] == 5) $result[$i]['aprobar'] .= '<br/><br/>Estatus Actual: <span class="badge badge-success"><i class="fas fa-check-double"></i> Carta de Liberación Entregada</span>';
            $i++;
        }
        $_response['periodoActivo'] = 'false';
        $_response['nombreAlumno'] = fixEncode($alumnoData->col_nombres.' '.$alumnoData->col_apellidos);
        $_response['periodo'] = fixEncode($periodoData->col_nombre). ' ('.$periodoData->col_grado.'-'.$periodoData->col_grupo.')';
        if(in_array($periodoData->col_id, $periodosActivos)) {
            $_response['periodoActivo'] = 'true';
        }
        $_response['lugar'] = fixEncode($alumnoData->lugar);
        $_response['result'] = $result;
        return $this->response->withJson($_response);
    });

    $this->get('/listHistorial', function (Request $request, Response $response, array $args) {
        global $download_url;

        $alumnoid = getCurrentUserID();

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $r = $c->fetch(PDO::FETCH_OBJ);
        $estatus_ss = $r->col_reportes_practicas;

        // $periodoid = getLastPeriodoAlumno(intval($alumnoid), $this->db);
        if(date('N') != 3 && strtotime('now') > strtotime($item->col_next)){
            $allow = 'true';
            if(date('N') == 2 && date('G') >= 19){
                $allow = 'false';
            }
        }else{
            $allow = 'false';
        }
        $query = "SELECT * FROM tbl_practicas_archivos WHERE col_alumnoid='".intval($alumnoid)."' ORDER BY col_id DESC";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();

        $i = 0;
        foreach($todos as $item){

            $result[$i]['id'] = $item['col_id'];
            $result[$i]['comentarios'] = $item['col_comentarios'];
            $fecha = fechaTexto($item['col_created_at']).' '.substr($item['col_created_at'], 11, strlen($item['col_created_at']));
            $result[$i]['fecha'] = '<a class="text-primary" href="'.$download_url.'practicas/'.$item['col_archivo'].'" target="_blank">'.$fecha.'</a>';

            if($item['col_estatus'] == 0) {
                $result[$i]['estatus'] = '<span class="text-info"><i class="fas fa-clock"></i> En Revisión</span>';
                if($allow == true || $estatus_ss == 1) $result[$i]['estatus'] .= '&nbsp;&nbsp;<a class="text-danger" href="#/pages/practicas-profesionales/borrar/'.$item['col_id'].'"><i class="fas fa-trash"></i> Borrar Archivo</a>';
            }
            if($item['col_estatus'] == 1) $result[$i]['estatus'] = '<span class="text-danger"><i class="fas fa-times-circle"></i> Rechazado</span>';
            if($item['col_estatus'] == 2) $result[$i]['estatus'] = '<span class="text-success"><i class="fas fa-check-circle"></i> Aprobado</span>';
            if($item['col_estatus'] == 3) $result[$i]['estatus'] = '<span class="text-info"><i class="fas fa-check-square"></i> Extemporaneo</span>';
            if($item['col_estatus'] == 4) $result[$i]['estatus'] = '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Falsificación</span>';
            if($item['col_estatus'] == 5) $result[$i]['estatus'] = '<span class="text-success"><i class="fas fa-check-double"></i> Cara de Liberación Aprobada</span>';

            $i++;
        }

        return $this->response->withJson($result);
    });

    $this->post('/borrar', function (Request $request, Response $response, $args) {
        global $dblog;

        $input = json_decode($request->getBody());
        $_response['status'] = 'false';
        $alumnoid = getCurrentUserID();
        if(esRepresentante()) {
            $_response['status'] = 'false';
            $_response['reason'] = 'Error representante';
            return $this->response->withJson($_response);
        }
        $query = 'DELETE FROM tbl_practicas_archivos WHERE col_id="'.intval($input->id).'" AND col_alumnoid="'.$alumnoid.'"';

        $dblog = new DBLog($query, 'tbl_practicas_archivos', '', '', 'Archivos de Practicas', $this->db);
        $dblog->where = array('col_id' => intval($input->id), 'col_alumnoid' => intval($alumnoid));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();

        $dblog->saveLog();

        $query = 'SELECT * FROM tbl_practicas_archivos WHERE col_id="'.intval($input->id).'" AND col_alumnoid="'.$alumnoid.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        if($sth->rowCount() == 0) {
            $_response['status'] = 'true';
        }
        return $this->response->withJson($_response);
    });


    $this->post('/updateComments', function (Request $request, Response $response, $args) {
        global $dblog;
        $input = json_decode($request->getBody());
        if(esRepresentante()) {
            $_response['status'] = 'false';
            $_response['reason'] = 'Error representante';
            return $this->response->withJson($_response);
        }

        $query = 'UPDATE tbl_practicas_archivos SET col_comentarios="'.addslashes($input->comentario).'" WHERE col_id="'.intval($input->id).'"';

        $dblog = new DBLog($query, 'tbl_practicas_archivos', '', '', 'Practicas', $this->db);
        $dblog->where = array('col_id' => intval($input->id));
        $dblog->prepareLog();

        $sth = $this->db->prepare($query);
        $sth->execute();
        $dblog->saveLog();

        $_response['status'] = 'true';
        return $this->response->withJson($_response);
    });

    $this->post('/agregar', function (Request $request, Response $response, $args) {
        global $uploaddir, $download_url, $allowExtensions, $limitFileSize;
        global $dblog;

        $userID = getCurrentUserID();
        $periodo = getCurrentAlumnoPeriodoID($this->db);



        if (!file_exists($uploaddir.'practicas')) mkdir($uploaddir.'practicas', 0777, true);

        if(esRepresentante()) {
            $_response['status'] = 'No tienes permisos suficientes para subir este archivo.';
            $_response['reason'] = 'Error representante';
            return $this->response->withJson($_response);
        }

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        // if($input->adjunto->filename == ''){
        //     $_response['status'] = 'false';
        //     return $this->response->withJson($_response);
        // }

        if($input->adjunto->filename == ''){
            $_response['status'] = 'No se puede guardar el registro, debido a que no estas enviando ningun archivo, debes enviar un archivo como evidencia de tu reporte.';
            return $this->response->withJson($_response);
        }

        list($type, $dataFile) = explode(';', $input->adjunto->value);
        list(, $dataFile)      = explode(',', $dataFile);

        if ( base64_encode(base64_decode($dataFile, true)) !== $dataFile){
            $_response['status'] = 'El archivo que estas intentando enviar parece ser invaliddo, puedes probar abriendolo con un editor y guardar como un nuevo archivo antes de volver a intentar subirlo.';
            return $this->response->withJson($_response);
        }

        $array_ext = explode('.', $input->adjunto->filename);
        $extension = end($array_ext);

        if (!in_array(strtoupper($extension), $allowExtensions)) {
            $_response['status'] = 'No se permite el tipo de archivo que deseas agregar, solo se permite: '.implode(', ', $allowExtensions);
            return $this->response->withJson($_response);
        }

        $fileSizeUploaded = getBase64Size($dataFile);

        $max_upload = (int)(ini_get('upload_max_filesize'));
        $max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);
        $_response['mus'] = $upload_mb;

        if($fileSizeUploaded > $limitFileSize) {
            $_response['status'] = 'El archivo que deseas agregar supera el limite permitido de: '.($limitFileSize / 1e+6).' Megas';
            return $this->response->withJson($_response);
        }

        $query = 'SELECT * FROM tbl_practicas WHERE col_alumnoid="'.$userID.'" AND col_periodoid="'.$periodo.'"';
        $sthCurrent = $this->db->prepare($query);
        $sthCurrent->execute();
        $practicas = $sthCurrent->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_practicas_archivos WHERE col_alumnoid="'.$userID.'" AND col_practicaid="'.$practicas->col_id.'" AND col_estatus=0';
        $sthCurrent = $this->db->prepare($query);
        $sthCurrent->execute();
        if($sthCurrent->rowCount() == 0) {

            $data = array(
                'col_alumnoid' => $userID,
                'col_practicaid' => $practicas->col_id,
                'col_archivo' => '',
                'col_created_at' => date("Y-m-d H:i:s"),
                'col_created_by' => $userID
            );

            $query = 'INSERT INTO tbl_practicas_archivos ('.implode(",", array_keys($data)).')
            VALUES("'.implode('", "', array_values($data)).'")';

            $sth = $this->db->prepare($query);

            if($sth->execute()){
                $_response['status'] = 'true';
                if($input->adjunto->filename){
                    $lastID = $this->db->lastInsertId();
                    $filename = 'practica-'.strtotime('now').'.'.$extension;
                    $query = 'UPDATE tbl_practicas_archivos SET col_archivo="'.$filename.'" WHERE col_id="'.$lastID.'"';

                    $dblog = new DBLog($query, 'tbl_practicas_archivos', '', '', 'Practicas', $this->db);
                    $dblog->where = array('col_id' => intval($lastID));
                    $dblog->prepareLog();

                    $archivo = $this->db->prepare($query);
                    $archivo->execute();

                    $dblog->saveLog();

                    if(!file_exists($uploaddir.'practicas')) @mkdir($uploaddir.'practicas', 0777);

                    $_response['uploaded'] = file_put_contents($uploaddir.'practicas/'.$filename, base64_decode($dataFile));
                }
            }else{
                $_response['status'] = 'No se puedo guardar el registro.';
            }

        }else{
            $_response['status'] = 'Actualmente tienes un reporte en revisión, no puedes subir mas archivos mientras tengas un reporte con estatus "en revisión."';
        }
        return $this->response->withJson($_response);

    });

    $this->get('/getInfo', function (Request $request, Response $response, array $args) {
        global $download_url;

        $userID = getCurrentUserID();
        $periodo = getCurrentAlumnoPeriodoID($this->db);

        $query = 'SELECT * FROM tbl_practicas WHERE col_alumnoid="'.$userID.'" AND col_periodoid="'.$periodo.'"';
        $sthCurrent = $this->db->prepare($query);
        $sthCurrent->execute();
        if($sthCurrent->rowCount() == 0){
            $_response['allow'] = 'nada';

            //Significa que aun no tiene practica asignada
            // return $this->response->withJson($_response);
        }else{
            $practicaData = $sthCurrent->fetch(PDO::FETCH_OBJ);

            $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
            $c = $this->db->prepare($query);
            $c->execute();
            $r = $c->fetch(PDO::FETCH_OBJ);
            $estatus_ss = $r->col_reportes_practicas;


            $sth = $this->db->prepare("SELECT * FROM tbl_practicas_archivos WHERE  col_alumnoid='".$userID."' AND col_practicaid='".$practicaData->col_id."' ORDER BY col_id DESC LIMIT 1");
            $sth->execute();
            $item = $sth->fetch(PDO::FETCH_OBJ);
            if($sth->rowCount()){
            $i = 0;

                if($item->col_estatus == 0) { //Subido sin revision
                    $_response['status'] = $item->col_estatus;
                    $_response['allow'] = 'wait';
                    $_response['currentArchivo'] = $download_url.'practicas/'.$item->col_archivo;
                }

                if($item->col_estatus == 1) { //Revisado Denegado
                    $_response['status'] = $item->col_estatus;
                    $_response['allow'] = 'true';
                    // if(date('N') != 3 && strtotime('now') > strtotime($item->col_next)){
                    if(date('N') != 3){
                        $_response['allow'] = 'true';
                        if(date('N') == 2 && date('G') >= 19){
                            $_response['allow'] = 'false';
                        }
                    }else{
                        $_response['allow'] = 'false';
                    }
                }

                if($item->col_estatus == 2 || $item->col_estatus == 3 || $item->col_estatus == 4 || $item->col_estatus == 5) { //Revisado Aprobado
                    $_response['allow'] = 'aprobado';
                    $_response['status'] = $item->col_estatus;
                    if(date('N') != 3){
                        $_response['allow'] = 'true';
                        if(date('N') == 2 && date('G') >= 19){
                            $_response['allow'] = 'false';
                        }
                    }else{
                        $_response['allow'] = 'false';
                    }
                }
            }else{
                if(date('N') != 3){
                    $_response['allow'] = 'true';
                    if(date('N') == 2 && date('G') >= 19){
                        $_response['allow'] = 'false';
                    }
                }else{
                    $_response['allow'] = 'false';
                }
            }

            if($estatus_ss == 1){
                $_response['allow'] = 'true';
                $_response['byadmin'] = 'true';
            }
        }
        $_response['fecha_dia'] = date('N');
        $_response['fecha_hora'] = date('G');
        $_response['fecha_full'] = date('Y-m-d h:i:s');
        $_response['periodo'] = $periodo;
        return $this->response->withJson($_response);

    });

    $this->get('/getOficio', function () {
        global $nombreInstituto, $inicialesInstituto, $_indicacionInstituto;

        $practicasid = intval($_REQUEST['id']);
        $query = 'SELECT * FROM tbl_practicas WHERE col_id="'.$practicasid.'" ';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $practicasData = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$practicasData->col_alumnoid.'" ';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

        $carreraData = getCarrera($alumnodData->col_carrera, $this->db);
        $periodoData = getPeriodo($alumnodData->col_periodoid, $this->db, false);
        $meses = explode('-', preg_replace('/\d/', '', $periodoData->col_nombre));
        $inicio = trim($meses[0]);
        $fin = trim($meses[1]);

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $this->db->prepare($query);
        $c->execute();
        $config = $c->fetch(PDO::FETCH_OBJ);

        $horasPracticas = 130;
        // $horasPracticas = 150; // Modificado el 2020-02-06

        $tipoCurso = 'Semestre';
        if($carreraData['modalidad'] == 'Cuatrimestral') {
            $tipoCurso = 'Cuatrimestre';
        }
        ob_start();
        ?>
        <table border="0" width="100%">
            <tr>
                <td align="right">Tuxtla Gutiérrez, Chiapas., a <?php echo date('d'); ?> de <?php echo getMes(date('F')); ?> del <?php echo date('Y'); ?></td>
            </tr>
        </table>
        <br/>
        <table border="0" width="100%">
            <tr>
                <td><?php echo fixEncode($practicasData->col_titular, true); ?></td>
            </tr>
            <tr>
                <td><?php echo fixEncode($practicasData->col_cargo_titular, true); ?></td>
            </tr>
            <tr>
                <td><?php echo fixEncode($practicasData->col_lugar, true); ?></td>
            </tr>
            <tr>
                <td>P R E S E N T E</td>
            </tr>
        </table><br/><br/>
        <p style="text-align: justify;">Con base al acuerdo establecido con nuestra institución, le solicito de la manera más
atenta designar actividades al C. <?php echo strtoupper(fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos, true, true)); ?> alumno de <?php echo $periodoData->col_grado; ?>º <?php echo $tipoCurso; ?> de
la <?php echo fixEncode($carreraData['nombre'], true); ?>,<?php echo $_indicacionInstituto; ?> <?php echo $nombreInstituto; ?>. Le informo que
el horario para realizar sus practicas profesionales es de lunes a jueves de 10:30 a.m. a
1:30 p.m., sin remuneración económica. Exhorto a mantener una comunicación efectiva
haciéndonos de conocimiento que si el alumno asignado llega a registrar más de tres
faltas consecutivas sin justificar,el jefe inmediato tiene la opción de darle de baja
notificando al correo <a href="mailto:<?php echo $config->col_correo_practicas; ?>"><?php echo $config->col_correo_practicas; ?></a> los hechos ocurridos y el
motivo de la baja.</p>
<p style="text-align: justify;">No omito manifestarle que las fechas que corresponden a exámenes parciales, días
feriados y vacaciones oficiales los estudiantes no asistirán a las actividades.</p>
<p style="text-align: justify;">El período de duración de las prácticas profesionales es del <?php echo getMes(date('d', strtotime($periodoData->col_fecha_inicio))); ?> de <?php echo getMes(date('F', strtotime($periodoData->col_fecha_inicio))); ?> al <?php echo getMes(date('d', strtotime($periodoData->col_fecha_fin))); ?> de
<?php echo getMes(date('F', strtotime($periodoData->col_fecha_fin))); ?> debiendo cubrir un total de <?php echo $horasPracticas; ?> horas. En caso de que el alumno necesite
ausentarse de su horario regular por cuestiones académicas, se solicitara el permiso vía
oficio por la coordinación de nuestra Facultad.</p>
<p style="text-align: justify;">Comunico a usted que el oficio de aceptación y el de liberación de practicas deberán ir
dirigidos a la <b>Mtra. Susana Palacios Morales</b> Directora General.</p>
<p style="text-align: justify;">Agradezco la atención en la formación de la nueva generación de juristas chiapanecos.</p>

        <br/><br/>
        <h3 style="text-align: center;font-weight: normal;">Atentamente</h3><br/><br/><br/>
        <table border="0" width="100%">
            <tr>
                <td align="center" width="35%"></td>
                <td align="center" width="30%" class="firma_up"><?php echo fixEncode($config->col_encargado_control_escolar, true); ?><br/><small>Departamento de Calidad Educativa</small></td>
                <td align="center" width="35%"></td>
            </tr>
        </table>

        <?php
        $html = ob_get_contents();
        ob_end_clean();



        include_once(__DIR__ . '/../src/mpdf/mpdf.php');

        $mpdf=new mPDF('c','A4', '','', 20, 20, 30, 35);

        $mpdf->SetHTMLHeader(pdfHeader());
        $mpdf->SetHTMLFooter(pdfFooter());

        $mpdf->SetDisplayMode('fullpage');
        $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

        $mpdf->WriteHTML(pdfCSS(), 1);
        $mpdf->WriteHTML($html, 2);

        $mpdf->Output('Amonestacion.pdf', 'I');

        die();

    });

});
// Termina routes.practicas.php
