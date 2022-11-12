<?php

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el modulo de mensajes (Centro de Mensajes).
 *
 * Lista de funciones
 *
 * /mensajes
 * - /send
 * - /sendSugerencia
 * - /destinatarios
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->group('/mensajes', function () {


    $this->post('/send', function (Request $request, Response $response, $args) {
        global $download_url, $uploaddir;
        //$debug = true;

        $userType = getCurrentUserType(); // maestro - administrativo - alumno

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());

        if($userType == 'alumno' && $input->reply == '') {
            $alumnoid = getCurrentUserID();
            $al = $this->db->prepare("SELECT col_correo FROM tbl_alumnos WHERE col_id='".$alumnoid."'");
            $al->execute();
            $alumnoData = $al->fetch(PDO::FETCH_OBJ);
            $input->reply = $alumnoData->col_correo;
        }

        if($input->adjunto->filename != ''){
            //$file = base64_decode($input->adjunto->value);
            $extension = $input->adjunto->extension;
            $filename = 'attachment-'.strtotime('now').'.'.$extension;

            // $file_data = explode(',', $input->adjunto->value);
            list($type, $dataFile) = explode(';', $input->adjunto->value);
            list(, $dataFile)      = explode(',', $dataFile);
            $_response['uploaded'] = file_put_contents($uploaddir.$filename, base64_decode($dataFile));
        }

        if($input->texto == ''){
            $_response['status'] = 'false';
            $_response['errorMessage'] = 'El contenido de tu mensaje esta vacio. Debes agregar algo de contenido antes de enviar un mensaje.';
            return $this->response->withJson($_response);
        }

        $destinatarios = $input->destinatario;
        if(count($destinatarios) == 0){
            $_response['status'] = 'false';
            $_response['errorMessage'] = 'Debes especificar al menos un destinatario para enviar este mensaje.';
            return $this->response->withJson($_response);
        }


        // $sends = 0;



        if(in_array('todos', $destinatarios)){

            $sth = $this->db->prepare("SELECT * FROM tbl_users ORDER BY col_id");
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach ($todos as $row) {
                if($row[col_id] == 1  && getCurrentUserID() != 1) continue;
                $destinatario_nombre = $row['col_firstname']." ".$row['col_lastname'];
                $destinatario_nombre = (preg_match('//u', $destinatario_nombre) == 0?utf8_encode($destinatario_nombre):$destinatario_nombre);
                $_desitnatarios[] = array('to' => $row['col_email'], 'nombre' => $destinatario_nombre);
            }

            $sth = $this->db->prepare("SELECT * FROM tbl_alumnos ORDER BY col_id");
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach ($todos as $row) {
                $destinatario_nombre = $row['col_nombres']." ".$row['col_apellidos'];
                $destinatario_nombre = (preg_match('//u', $destinatario_nombre) == 0?utf8_encode($destinatario_nombre):$destinatario_nombre);
                $_desitnatarios[] = array('to' => $row['col_correo'], 'nombre' => $destinatario_nombre);
            }

            unset($destinatarios['todos']);
        }

        if(in_array('administrativos', $destinatarios)){
            $sth = $this->db->prepare("SELECT * FROM tbl_users WHERE col_maestro=0 ORDER BY col_id");
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach ($todos as $row) {
                if($row[col_id] == 1  && getCurrentUserID() != 1) continue;
                $destinatario_nombre = $row['col_firstname']." ".$row['col_lastname'];
                $destinatario_nombre = (preg_match('//u', $destinatario_nombre) == 0?utf8_encode($destinatario_nombre):$destinatario_nombre);
                $_desitnatarios[] = array('to' => $row['col_email'], 'nombre' => $destinatario_nombre);
            }
            unset($destinatarios['administrativos']);
        }

        if(in_array('maestros', $destinatarios)){
            if($userType == 'alumno') {
                $misMaestros = getTodosMisMaestros($this->db);
                $sth = $this->db->prepare("SELECT * FROM tbl_users WHERE col_maestro=1 AND col_id IN (".implode(',', $misMaestros).") ORDER BY col_id");
                $sth->execute();
                $todos = $sth->fetchAll();
                foreach ($todos as $row) {
                    $destinatario_nombre = $row['col_firstname']." ".$row['col_lastname'];
                    $destinatario_nombre = (preg_match('//u', $destinatario_nombre) == 0?utf8_encode($destinatario_nombre):$destinatario_nombre);
                    $_desitnatarios[] = array('to' => $row['col_email'], 'nombre' => $destinatario_nombre);
                }
                unset($destinatarios['maestros']);
            } else {
                $sth = $this->db->prepare("SELECT * FROM tbl_users WHERE col_maestro=1 ORDER BY col_id");
                $sth->execute();
                $todos = $sth->fetchAll();
                foreach ($todos as $row) {
                    $destinatario_nombre = $row['col_firstname']." ".$row['col_lastname'];
                    $destinatario_nombre = (preg_match('//u', $destinatario_nombre) == 0?utf8_encode($destinatario_nombre):$destinatario_nombre);
                    $_desitnatarios[] = array('to' => $row['col_email'], 'nombre' => $destinatario_nombre);
                }
                unset($destinatarios['maestros']);
            }
        }

        if(in_array('alumnos', $destinatarios)){
            $sth = $this->db->prepare("SELECT * FROM tbl_alumnos ORDER BY col_id");
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach ($todos as $row) {
                $destinatario_nombre = $row['col_nombres']." ".$row['col_apellidos'];
                $destinatario_nombre = (preg_match('//u', $destinatario_nombre) == 0?utf8_encode($destinatario_nombre):$destinatario_nombre);
                $_desitnatarios[] = array('to' => $row['col_correo'], 'nombre' => $destinatario_nombre);
            }
            unset($destinatarios['alumnos']);
        }

        $_currentPeriodoSemLD = getCurrentPeriodo($this->db, 'ldsem');
        $_currentPeriodoCuaLD = getCurrentPeriodo($this->db, 'ldcua');
        $_currentPeriodoMaster = getCurrentPeriodo($this->db, 'docto');
        $_currentPeriodoDoctorado = getCurrentPeriodo($this->db, 'master');

        if(in_array('alumnos_sem_ld', $destinatarios)){
            $sth = $this->db->prepare("SELECT a.* FROM tbl_alumnos a WHERE a.col_periodoid IN (SELECT p.col_id FROM tbl_periodos p WHERE p.col_modalidad=1 AND p.col_groupid='".$_currentPeriodoSemLD."')");
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach ($todos as $row) {
                $destinatario_nombre = $row['col_nombres']." ".$row['col_apellidos'];
                $destinatario_nombre = (preg_match('//u', $destinatario_nombre) == 0?utf8_encode($destinatario_nombre):$destinatario_nombre);
                $_desitnatarios[] = array('to' => $row['col_correo'], 'nombre' => $destinatario_nombre);
            }
            unset($destinatarios['alumnos_sem_ld']);
        }

        if(in_array('alumnos_cua_ld', $destinatarios)){
            $sth = $this->db->prepare("SELECT a.* FROM tbl_alumnos a WHERE a.col_periodoid IN (SELECT p.col_id FROM tbl_periodos p WHERE p.col_modalidad=2 AND p.col_groupid='".$_currentPeriodoCuaLD."')");
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach ($todos as $row) {
                $destinatario_nombre = $row['col_nombres']." ".$row['col_apellidos'];
                $destinatario_nombre = (preg_match('//u', $destinatario_nombre) == 0?utf8_encode($destinatario_nombre):$destinatario_nombre);
                $_desitnatarios[] = array('to' => $row['col_correo'], 'nombre' => $destinatario_nombre);
            }
            unset($destinatarios['alumnos_cua_ld']);
        }

        if(in_array('alumnos_master', $destinatarios)){
            $sth = $this->db->prepare("SELECT a.* FROM tbl_alumnos a WHERE a.col_periodoid IN (SELECT p.col_id FROM tbl_periodos p WHERE p.col_modalidad=3 AND p.col_groupid='".$_currentPeriodoMaster."')");
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach ($todos as $row) {
                $destinatario_nombre = $row['col_nombres']." ".$row['col_apellidos'];
                $destinatario_nombre = (preg_match('//u', $destinatario_nombre) == 0?utf8_encode($destinatario_nombre):$destinatario_nombre);
                $_desitnatarios[] = array('to' => $row['col_correo'], 'nombre' => $destinatario_nombre);
            }
            unset($destinatarios['alumnos_master']);
        }

        if(in_array('alumnos_docto', $destinatarios)){
            $sth = $this->db->prepare("SELECT a.* FROM tbl_alumnos a WHERE a.col_periodoid IN (SELECT p.col_id FROM tbl_periodos p WHERE p.col_modalidad=4 AND p.col_groupid='".$_currentPeriodoDoctorado."')");
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach ($todos as $row) {
                $destinatario_nombre = $row['col_nombres']." ".$row['col_apellidos'];
                $destinatario_nombre = (preg_match('//u', $destinatario_nombre) == 0?utf8_encode($destinatario_nombre):$destinatario_nombre);
                $_desitnatarios[] = array('to' => $row['col_correo'], 'nombre' => $destinatario_nombre);
            }
            unset($destinatarios['alumnos_docto']);
        }

        foreach($destinatarios as $destinatario){
            $destinatario = explode(':', $destinatario);
            $debug[] = $destinatario;
            switch($destinatario[0]){

                case 'a'://Allumnos
                $user_query = 'SELECT * FROM tbl_alumnos WHERE col_correo="'.$destinatario[1].'"';
                $user_obj = $this->db->prepare($user_query);
                $user_obj->execute();
                $user = $user_obj->fetch(PDO::FETCH_OBJ);
                $destinatario_nombre = $user->col_nombres." ".$user->col_apellidos;
                $destinatario_nombre = (preg_match('//u', $destinatario_nombre) == 0?utf8_encode($destinatario_nombre):$destinatario_nombre);
                $_desitnatarios[] = array('to' => $destinatario[1], 'nombre' => $destinatario_nombre);
                break;

                case 'g'://Grupos
                $alumnosIDS = getAlumnosByTaxMateria($destinatario[3], $this->db);
                $user_query = 'SELECT * FROM tbl_alumnos WHERE col_id IN ('.implode(',', $alumnosIDS).')';
                $user_obj = $this->db->prepare($user_query);
                $user_obj->execute();
                $todosAlumnos = $user_obj->fetchAll();
                foreach ($todosAlumnos as $alumno) {
                    $destinatario_nombre = $alumno['col_nombres']." ".$alumno['col_apellidos'];
                    $destinatario_nombre = (preg_match('//u', $destinatario_nombre) == 0?utf8_encode($destinatario_nombre):$destinatario_nombre);
                    $_desitnatarios[] = array('to' => $alumno['col_correo'], 'nombre' => $destinatario_nombre);
                }


                break;

                case 'm': //Maestro/Administrativo
                $user_query = 'SELECT * FROM tbl_users WHERE col_email="'.$destinatario[1].'"';
                $user_obj = $this->db->prepare($user_query);
                $user_obj->execute();
                $user = $user_obj->fetch(PDO::FETCH_OBJ);
                $destinatario_nombre = $user->col_nombres." ".$user->col_apellidos;
                $destinatario_nombre = (preg_match('//u', $destinatario_nombre) == 0?utf8_encode($destinatario_nombre):$destinatario_nombre);
                $_desitnatarios[] = array('to' => $destinatario[1], 'nombre' => $destinatario_nombre);
                break;

            }
        }

        $texto = $input->texto;
        if($input->adjunto->filename != ''){
            if(strpos($texto, '#adjunto#') !== false) {
                $allowed = array('jpg', 'bmp', 'gif', 'png');
                if($input->adjunto->filename != '' && in_array($extension, $allowed)){
                    $texto = str_replace('#adjunto#', '<img src="https://plataforma.fldch.edu.mx/'.$download_url.$filename.'" />', $texto);
                }
            }else{
                $texto = $texto."<br/><br/><p>Descargar Archivo: <a href='https://plataforma.fldch.edu.mx/".$download_url.$filename."'>".$input->adjunto->filename."</a></p>";
            }
        }

        $subject = (preg_match('//u', $input->asunto) == 0?utf8_encode($input->asunto):$input->asunto);


        $_response['asunto'] = urlencode($subject);
        $_response['texto'] = urlencode($texto);
        $_response['reply'] = urlencode($input->reply);
        $_response['debug'] = $debug;
        $_response['destinatarios'] = $_desitnatarios;
        $_response['callback'] = 'sent';
        $_response['status'] = 'true';

        $data = array(
            "col_senderid" => getCurrentUserID(),
            "col_tipo" => trim(getCurrentUserType()),
            "col_asunto" => addslashes($subject),
            "col_texto" => addslashes($texto),
            "col_reply" => addslashes($input->reply),
            "col_destinatarios" => addslashes(serialize($_desitnatarios)),
            "col_destinatarios_form" => addslashes(serialize($input->destinatario)),
            "col_created_at" => date("Y-m-d H:i:s"),
            "col_created_by" => getCurrentUserID(),
        );

        $query = 'INSERT INTO tbl_mensajes ('.implode(",", array_keys($data)).')
        VALUES("'.implode('", "', array_values($data)).'")';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $mensajeID = $this->db->lastInsertId();

        if($input->adjunto->filename != ''){
            $data = array(
                "col_mensajeid" => $mensajeID,
                "col_adjunto" => trim($filename),
                "col_adjunto_nombre" => addslashes($input->adjunto->filename),
                "col_created_at" => date("Y-m-d H:i:s"),
                "col_created_by" => getCurrentUserID(),
            );

            $query = 'INSERT INTO tbl_mensajes_adjuntos ('.implode(",", array_keys($data)).')
            VALUES("'.implode('", "', array_values($data)).'")';
            $sth = $this->db->prepare($query);
            $sth->execute();
        }

        return $this->response->withJson($_response);
    });

    $this->post('/sendSugerencia', function (Request $request, Response $response, $args) {
        global $download_url, $uploaddir, $isINEF;
        //$debug = true;

        $_response['status'] = 'false';
        $input = json_decode($request->getBody());


        if($input->texto == ''){
            $_response['status'] = 'false';
            $_response['errorMessage'] = 'El contenido de tu mensaje esta vacio. Debes agregar algo de contenido antes de enviar un mensaje.';
            return $this->response->withJson($_response);
        }

        if($isINEF == false) {
            //$_desitnatarios[] = array('to' => 'jorge.x3@gmail.com', 'nombre' => 'Contacto FLDCH');
            $_desitnatarios[] = array('to' => 'direccioninef@inef.com.mx', 'nombre' => 'Contacto FLDCH');
            $_desitnatarios[] = array('to' => 'coordinacionacademia@fldch.edu.mx', 'nombre' => 'Contacto FLDCH');
            $_desitnatarios[] = array('to' => 'academicolicenciatura@fldch.edu.mx', 'nombre' => 'Contacto FLDCH');
        }else{
            $_desitnatarios[] = array('to' => 'direccioninef@inef.com.mx', 'nombre' => 'Contacto INEF');
            $_desitnatarios[] = array('to' => 'coordinacionacademia@fldch.edu.mx', 'nombre' => 'Contacto INEF');
            $_desitnatarios[] = array('to' => 'academicolicenciatura@fldch.edu.mx', 'nombre' => 'Contacto INEF');
        }


        $texto = $input->texto;
        $subject = (preg_match('//u', $input->asunto) == 0?utf8_encode($input->asunto):$input->asunto);


        $_response['asunto'] = urlencode($subject);
        $_response['texto'] = urlencode($texto);

        $_response['debug'] = $debug;
        $_response['destinatarios'] = $_desitnatarios;
        $_response['callback'] = 'sent';
        $_response['status'] = 'true';


        return $this->response->withJson($_response);
    });

    $this->get('/destinatarios', function (Request $request, Response $response, array $args) {

        $userDepto = getCurrentUserDepto();
        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $userID = getCurrentUserID();
        $periodoAlumnoID = getCurrentAlumnoPeriodoID($this->db);

        if($userType == 'alumno'){
            /*
            $query = 'SELECT p.col_carreraid AS carrera, p.col_plan_estudios AS planEstudios, p.col_id AS periodo, p.col_grado AS semestre FROM tbl_alumnos a LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid WHERE a.col_id="'.$userID.'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $alumno = $sth->fetch(PDO::FETCH_OBJ);

            $query ="SELECT * FROM tbl_academias WHERE col_alumnoid='".$userID."' AND col_periodoid='".$periodoAlumnoID."'";
            $sth = $this->db->prepare($query);
            $sth->execute();
            if($sth->rowCount()){
                $alumnoAcademia = $sth->fetch(PDO::FETCH_OBJ);
                $extraIDS[] = $alumnoAcademia->col_materiaid;
            }

            $query ="SELECT * FROM tbl_talleres WHERE col_alumnoid='".$userID."' AND col_periodoid='".$periodoAlumnoID."'";
            $sth = $this->db->prepare($query);
            $sth->execute();
            if($sth->rowCount()){
                $alumnoTaller = $sth->fetch(PDO::FETCH_OBJ);
                $extraIDS[] = $alumnoTaller->col_materiaid;
            }

            if(count($extraIDS)){
                $query = "SELECT * FROM tbl_materias WHERE (col_clave NOT LIKE 'AC%' AND col_clave NOT LIKE 'TL%' OR col_id IN (".implode(',', $extraIDS).")) AND col_plan_estudios='".intval($alumno->planEstudios)."' AND col_carrera='".$alumno->carrera."' AND col_semestre='".$alumno->semestre."' GROUP BY col_clave ORDER BY col_id ASC";
            }else{
                $query = "SELECT * FROM tbl_materias WHERE col_plan_estudios='".intval($alumno->planEstudios)."' AND col_carrera='".$alumno->carrera."' AND col_semestre='".$alumno->semestre."' GROUP BY col_clave ORDER BY col_id ASC";
            }
            $sth_materias = $this->db->prepare($query);
            $sth_materias->execute();
            $materias = $sth_materias->fetchAll();

            foreach($materias as $item){
                    $query_tax = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_materia_clave="'.$item['col_clave'].'" AND col_periodoid="'.$alumno->periodo.'"';
                    $sth_tax = $this->db->prepare($query_tax);
                    $sth_tax->execute();
                    $tax = $sth_tax->fetch(PDO::FETCH_OBJ);
                    if(intval($tax->col_maestroid) > 0) $idsmaestros[] = $tax->col_maestroid;
            }
            */
            $idsmaestros = getTodosMisMaestros($this->db);
            if(count($idsmaestros)){
                $result[0]['class'] = 'grupos';
                $result[0]['value'] = 'maestros';
                $result[0]['label'] = 'Todos los Maestros';
                $result[0]['text'] = 'Todos los Maestros';

                $i = 1;

                $sth2 = $this->db->prepare("SELECT *, CONCAT(col_firstname, ' ', col_lastname) AS col_fullname FROM tbl_users WHERE col_id IN (".implode(',', $idsmaestros).") ORDER BY col_id DESC");
                $sth2->execute();
                $todos2 = $sth2->fetchAll();
                foreach($todos2 as $item){
                    $result[$i]['class'] = 'maestros';
                    $result[$i]['value'] = 'm:'.$item['col_email'];
                    $result[$i]['label'] =fixEncode($item['col_fullname']);
                    $result[$i]['text'] = fixEncode($item['col_fullname']);
                    $i++;
                }
            }

        }

        if($userType == 'administrativo'){

            $result[0]['class'] = 'grupos';
            $result[0]['value'] = 'todos';
            $result[0]['label'] = 'Todos (Alumnos & Maestros)';
            $result[0]['text'] = 'Todos (Alumnos & Maestros)';

            $result[1]['class'] = 'grupos';
            $result[1]['value'] = 'administrativos';
            $result[1]['label'] = 'Todos los Administrativos';
            $result[1]['text'] = 'Todos los Administrativos';

            $result[2]['class'] = 'grupos';
            $result[2]['value'] = 'maestros';
            $result[2]['label'] = 'Todos los Maestros';
            $result[2]['text'] = 'Todos los Maestros';

            $result[3]['class'] = 'grupos';
            $result[3]['value'] = 'alumnos';
            $result[3]['label'] = 'Todos los Alumnos';
            $result[3]['text'] = 'Todos los Alumnos';

            $result[4]['class'] = 'grupos';
            $result[4]['value'] = 'alumnos_sem_ld';
            $result[4]['label'] = 'Todos los Alumnos (Semestral Licenciatura)';
            $result[4]['text'] = 'Todos los Alumnos (Semestral Licenciatura)';

            $result[5]['class'] = 'grupos';
            $result[5]['value'] = 'alumnos_cua_ld';
            $result[5]['label'] = 'Todos los Alumnos (Cuatrimestral Licenciatura)';
            $result[5]['text'] = 'Todos los Alumnos (Cuatrimestral Licenciatura)';

            $result[6]['class'] = 'grupos';
            $result[6]['value'] = 'alumnos_master';
            $result[6]['label'] = 'Todos los Alumnos (Maestría)';
            $result[6]['text'] = 'Todos los Alumnos (Maestría)';

            $result[7]['class'] = 'grupos';
            $result[7]['value'] = 'alumnos_docto';
            $result[7]['label'] = 'Todos los Alumnos (Doctorado)';
            $result[7]['text'] = 'Todos los Alumnos (Doctorado)';
            $i = 8;

            $sth = $this->db->prepare("SELECT *, CONCAT(col_nombres, ' ', col_apellidos) AS col_fullname FROM tbl_alumnos ORDER BY col_id DESC");
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach($todos as $item){
                $result[$i]['class'] = 'alumnos';
                $result[$i]['value'] = 'a:'.$item['col_correo'];
                $result[$i]['label'] = fixEncode($item['col_fullname']);
                $result[$i]['text'] =  fixEncode($item['col_fullname']);
                $i++;
            }

            $sth2 = $this->db->prepare("SELECT *, CONCAT(col_firstname, ' ', col_lastname) AS col_fullname FROM tbl_users ORDER BY col_id DESC");
            $sth2->execute();
            $todos2 = $sth2->fetchAll();
            foreach($todos2 as $item){
                if($item[col_id] == 1  && getCurrentUserID() != 1) continue;
                $result[$i]['class'] = 'maestros';
                $result[$i]['value'] = 'm:'.$item['col_email'];
                $result[$i]['label'] = fixEncode($item['col_fullname']);
                $result[$i]['text'] =  fixEncode($item['col_fullname']);
                $i++;
            }

        }

        if($userType == 'maestro'){

            $result[0]['class'] = 'grupos';
            $result[0]['value'] = 'alumnos';
            $result[0]['label'] = 'Todos los Alumnos';
            $result[0]['text'] = 'Todos los Alumnos';
            $i = 1;

            $periodos = getCurrentPeriodos($this->db);

            $query = "SELECT p.col_carreraid, p.col_plan_estudios, t.col_materia_clave, t.col_id AS ID, t.col_periodoid AS periodoid, p.col_aprobado AS horario_aprobado, p.col_groupid AS periodo_groupid, p.col_grado AS grado, p.col_grupo AS grupo ".
            "FROM tbl_maestros_taxonomia t ".
            "LEFT OUTER JOIN tbl_periodos p ON p.col_id = t.col_periodoid ".
            "WHERE t.col_maestroid='".intval($userID)."' ".
            "AND t.col_periodoid ".
            "ORDER BY t.col_id";

            $sth = $this->db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();
            $i = 0;
            foreach($todos as $item){
                $queryMateria = 'SELECT * FROM tbl_materias WHERE col_semestre="'.$item['grado'].'" AND col_clave="'.trim($item['col_materia_clave']).'" AND col_plan_estudios="'.$item['col_plan_estudios'].'"';
                $sthm = $this->db->prepare($queryMateria);
                $sthm->execute();
                $materiaData = $sthm->fetch(PDO::FETCH_OBJ);

                $grado = $item['grado'];
                $grupo = $item['grupo'];

                if(in_array($item['periodoid'], $periodos) && $materiaData->col_nombre) {
                    if(strpos(strtoupper($item[col_materia_clave]), 'AC') !== false || strpos(strtoupper($item[col_materia_clave]), 'TL') !== false){
                        $grado = 'Multigrupo';
                        $grupo = '';

                        if(strlen($item['col_materia_clave']) > 4){
                            $laClave = substr($item['col_materia_clave'], 0, -1);
                        }else{
                            $laClave = $item['col_materia_clave'];
                        }
                        if(is_array($mata) && in_array($laClave, $mata)) continue;

                        if(strlen($item['col_materia_clave']) > 4){
                            $mata[] = substr($item['col_materia_clave'], 0, -1);
                        }else{
                            $mata[] = $item['col_materia_clave'];
                        }
                        $mata = array_unique($mata);
                    }

                    $periodoData = getPeriodo($item['periodoid'], $this->db, false);
                    $carreraData = getCarrera($periodoData->col_carreraid, $this->db);

                    $result[$i]['class'] = 'materias';
                    $result[$i]['value'] = 'g:'.$materiaData->col_clave.':'.$userID.':'.$item['ID'];
                    if($grupo){
                        $result[$i]['label'] = fixEncode($materiaData->col_nombre.', Grupo: '.$grado.'-'.$grupo.', '.$carreraData['modalidad'], true);
                        $result[$i]['text'] =  fixEncode($materiaData->col_nombre.', Grupo: '.$grado.'-'.$grupo.', '.$carreraData['modalidad'], true);
                    }else{
                        $result[$i]['label'] = fixEncode($materiaData->col_nombre.', Grupo: '.$grado.', '.$carreraData['modalidad'], true);
                        $result[$i]['text'] =  fixEncode($materiaData->col_nombre.', Grupo: '.$grado.', '.$carreraData['modalidad'], true);
                    }
                    $i++;
                }
            }
/*
            $misMaesterias = getTodasMisMaterias($this->db);
            $misMateriasTax = getTodasMisMateriasTAX($this->db);
            $query = "SELECT * FROM tbl_materias WHERE col_id IN (".implode(',', $misMaesterias).") GROUP BY col_nombre";
            $sth = $this->db->prepare($query);
            $sth->execute();

            if($sth->rowCount()){
                $materias = $sth->fetchAll();
                foreach($materias as $item){
                    if($item['col_nombre'] != ''){
                        $result[$i]['class'] = 'materias';
                        $result[$i]['value'] = 'g:'.$item['col_clave'].':'.$userID.':'.$misMateriasTax[$item['col_id']];
                        $result[$i]['label'] = fixEncode($item['col_nombre']);
                        $result[$i]['text'] =  fixEncode($item['col_nombre']);
                        $i++;
                    }
                }
            }
*/

            $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
            $c = $this->db->prepare($query);
            $c->execute();
            $config = $c->fetch(PDO::FETCH_OBJ);

            $query = "SELECT a.*, CONCAT(a.col_nombres, ' ', a.col_apellidos) AS col_fullname ".
            "FROM tbl_alumnos a ".
            "WHERE a.col_id IN (".implode(',', getTodosMisAlumnos($this->db)).")";
             // $_response['main_debug'] = $query;

            $sth = $this->db->prepare($query);
            $sth->execute();
            if($sth->rowCount()){
                $alumnos = $sth->fetchAll();
                foreach($alumnos as $item){
                    $result[$i]['class'] = 'alumnos';
                    $result[$i]['value'] = 'a:'.$item['col_correo'];
                    $result[$i]['label'] = fixEncode($item['col_fullname']);
                    $result[$i]['text'] =  fixEncode($item['col_fullname']);
                    $i++;
                }
            }

        }

        return $this->response->withJson($result);

    });

});
// Termina routes.mensajes.php
