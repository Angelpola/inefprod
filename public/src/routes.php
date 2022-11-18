<?php
/**
 *
 * Archivo que incluye la funciones principales que permiten al API (Slim Framework) funcionar, integrando todos los archivos
 * de cada modulo que a su vez incluyen las funciones a donde la plataforma realiza las diversas peticiones.
 *
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;


error_reporting(E_ERROR);
set_time_limit(0);



if(strpos($_SERVER['HTTP_HOST'], 'ranascreativas')  !== false){
    $apiURL = 'http://fldch.ranascreativas.com/public';
    $uploaddir = $_SERVER['DOCUMENT_ROOT'].'/universidad/dist/uploads/';
    $apidir = $_SERVER['DOCUMENT_ROOT'].'/universidad/api/';
    $download_url = '//'.$_SERVER['HTTP_HOST'].'/universidad/dist/uploads/';
}else{
    $apiURL = '/public';
    $uploaddir = $_SERVER['DOCUMENT_ROOT'].'/uploads/';
    $apidir = $_SERVER['DOCUMENT_ROOT'].'/';
    $download_url = 'uploads/';
}


$redondearCalculosCalificacionesFinales = false;
$isINEF = false;
$nombreInstituto = 'Facultad Libre de Derecho de Chiapas';
$_indicacionInstituto = 'de la';
$claveInstitulo = '07PSU0128K';
$inicialesInstituto = 'FLDCH';
$logoInstituto = 'https://plataforma.fldch.edu.mx/assets/images/logo-fldch-rojo.png';
if($_SERVER['SERVER_ADDR'] == '192.168.12.83') {
    $isINEF = true;
    $nombreInstituto = 'Instituto Nacional de Estudios Fiscales';
    $_indicacionInstituto = 'del';
    $claveInstitulo = '07PSU0019D';
    $inicialesInstituto = 'INEF';
    $logoInstituto = 'https://plataforma.inef.edu.mx/assets/images/logo-inef.png';
}

define('_MPDF_TTFONTPATH', $apidir.'ttfonts/');
define('_MPDF_TTFONTDATAPATH', $apidir.'ttfontdata/');
define('_MPDF_TTFONTDA_MPDF_TEMP_PATHTAPATH', $apidir.'tmp/');

$alertaAsistencias = 20;
$firmas = array('rector' => 73, 'director'=> 2);
$allowExtensions = array('XLS', 'XLSX', 'DOC', 'DOCX', 'PDF', 'JPG', 'JPEG', 'PNG', 'PPT', 'PPTX', 'ZIP', 'RAR');
$limitFileSize = 20000000; // Bytes
$totalCreditos['licenciatura'] = 308;
$totalCreditos['maestria'] = 80;
$totalCreditos['doctorado'] = 80;

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

function insert( $table, $variables = array() ) {
    //Make sure the array isn't empty
    if( empty( $variables ) )
    {
        return false;
    }

    $sql = "INSERT INTO ". $table;
    $fields = array();
    $values = array();
    foreach( $variables as $field => $value )
    {
        $fields[] = $field;
        $values[] = "'".$value."'";
    }
    $fields = ' (' . implode(', ', $fields) . ')';
    $values = '('. implode(', ', $values) .')';

    $sql .= $fields .' VALUES '. $values;

    return $sql;
}

// Routes

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->post('/auth/request-pass', function (Request $request, Response $response) {
    global $nombreInstituto, $inicialesInstituto, $logoInstituto;
    $input = $request->getParsedBody();

    $ip = $_SERVER['REMOTE_ADDR'];
    $nombre = '';
    $id = 0;
    $input['email'] = filter_var($input['email'], FILTER_SANITIZE_EMAIL);

    if(!filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL)){
        $_response['response'] = 'invalid';
        $_response['statusText'] = 'Correo invalido, debes ingresar una cuenta de correo valida.';
        return $response->withStatus(400, 'Correo Invalido');
    }

    $query = 'SELECT * FROM tbl_users WHERE col_status="1" AND col_email="'.$input['email'].'"';
    $sth = $this->db->prepare($query);
    $sth->execute();
    if($sth->rowCount() > 0){
        $user = $sth->fetch(PDO::FETCH_OBJ);
        $id = $user->col_id;
        $nombre = fixEncode($user->col_firstname.' '.$user->col_lastname);
        $es = 'admin';
    }

    if($es == '') {
        $querya = 'SELECT * FROM tbl_alumnos WHERE col_correo="'.$input['email'].'" AND col_estatus="activo"';
        $sth = $this->db->prepare($querya);
        $sth->execute();
        if($sth->rowCount() > 0){
            $user = $sth->fetch(PDO::FETCH_OBJ);
            $id = $user->col_id;
            $nombre = fixEncode($user->col_nombres.' '.$user->col_apellidos);
            $es = 'alumno';
        }
    }

    if($es == '') {
        $querya = 'SELECT * FROM tbl_padres_familia WHERE col_correo="'.$input['email'].'"';
        $sth = $this->db->prepare($querya);
        $sth->execute();
        if($sth->rowCount() > 0){
            $user = $sth->fetch(PDO::FETCH_OBJ);
            $id = $user->col_id;
            $nombre = fixEncode($user->col_nombre);
            $es = 'papa';
            $querya = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$user->col_alumnoid.'" AND col_estatus="activo"';
            $sth = $this->db->prepare($querya);
            $sth->execute();
            if($sth->rowCount() == 0){
                unset($user);
                return $response->withStatus(400, 'Alumno inactivo');
            }
        }
    }

    if($id == 0) return $response->withStatus(400, 'No existe el correo');

    $pass = randomPassword();
    $md5 = md5($pass);

    if($es == 'alumno') {
        $query = 'UPDATE tbl_alumnos SET
        col_password="'.$md5.'",
        col_password_lastchage="0000-00-00 00:00:00",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$id.'" WHERE col_id="'.$id.'"';
    }

    if($es == 'admin') {
        $query = 'UPDATE tbl_users SET
        col_pass="'.$md5.'",
        col_password_lastchage="0000-00-00 00:00:00",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$id.'" WHERE col_id="'.$id.'"';
    }

    if($es == 'papa') {
        $query = 'UPDATE tbl_padres_familia SET
        col_password="'.$md5.'",
        col_updated_at="'.date("Y-m-d H:i:s").'",
        col_updated_by="'.$id.'" WHERE col_id="'.$id.'"';
    }

    ob_start();
    ?>
    <p>Hola <?php echo $nombre; ?>,</p>
    <p>Este correo es para hacerte llegar la nueva contraseña de acceso que haz solicitado, es una<br/>contraseña temporal, te recomendamos cambiarla por una contraseña que te sea facil de recordar<br/>después de acceder a la aplicación nuevamente.</p>
        <ul>
            <li>Correo: <?php echo $input['email']; ?></li>
            <li>Nueva contraseña: <?php echo $pass; ?></li>
        </ul>
    <p>Saludos,<br/><?php echo $nombreInstituto; ?></p>
    <p><img src="<?php echo $logoInstituto; ?>" style="width:auto;height: 50px;" alt="<?php echo $inicialesInstituto; ?> Logo"></p>
    <?php
    $texto = ob_get_contents();
    ob_end_clean();
    enviarCorreo(array('to' => $input['email'], 'nombre' => $nombre), 'Nueva contraseña - '.$inicialesInstituto, $texto);
    $sth = $this->db->prepare($query);
    $sth->execute();

    // $_response['nombre'] = $nombre;
    // $_response['texto'] = $responseMail;
    // $_response['correo'] = $input['email'];
    // return $this->response->withJson($_response);
    // return $response->withStatus(400, 'Debug');
    return $response->withStatus(200);
});

$app->post('/auth/signin', function (Request $request, Response $response) {
    $input = $request->getParsedBody();
    // if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    //     $ip = $_SERVER['HTTP_CLIENT_IP'];
    // } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    //     $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    // } else {
    //     $ip = $_SERVER['REMOTE_ADDR'];
    // }
    $ip = $_SERVER['REMOTE_ADDR'];
    // $input['password'] = trim($input['password']);
    $input['password'] = str_replace("\r\n", "", $input['password']);
    $input['password'] = str_replace(" ", "", $input['password']);
    $input['password'] = preg_replace('/[^\PC\s]/u', '', $input['password']);




    $input['email'] = filter_var($input['email'], FILTER_SANITIZE_EMAIL);

    if(!filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL)){
        $_response['response'] = 'invalid';
        $_response['statusText'] = 'Correo invalido, debes ingresar una cuenta de correo valida.';
        return $this->response->withJson($_response);
    }

    $query = 'SELECT count(*) as totalIntentos FROM tbl_bitacora_ingresos WHERE col_estatus="0" AND col_correo="'.$input['email'].'"';
    $sth = $this->db->prepare($query);
    $sth->execute();
    $intentos = $sth->fetch(PDO::FETCH_OBJ);
    // if($intentos->totalIntentos >= 3) {
    //     $query = "INSERT INTO tbl_bitacora_ingresos (col_correo, col_pass, col_ip, col_fecha, col_estatus) VALUES('".$input['email']."', '".addslashes(strip_tags($input['password']))."', '".$ip."', NOW(), 0)";
    //     $sth = $this->db->prepare($query);
    //     $sth->execute();
    //     $_response['response'] = 'invalid';
    //     $_response['statusText'] = 'Haz superado el número de intentos validos (3) para iniciar tu sesión, favor de contactar a control escolar para que desbloqueen tu cuenta.';
    //     return $this->response->withJson($_response);
    // }


    $validPass = false;
    $md5 = '';
    $query = 'SELECT * FROM tbl_users WHERE col_status="1" AND col_email="'.$input['email'].'"';
    $sth = $this->db->prepare($query);
    $sth->execute();
    $user = $sth->fetch(PDO::FETCH_OBJ);
    if(md5($input['password']) === $user->col_pass) $validPass = true;
    $md5 = $user->col_pass;
    $is_alumno = false;
    $_is_alumno = 0;
    $is_alumno_representante = false;
    $_is_alumno_representante = 0;
    $representanteID = 0;

    $sourceDevice = 'desktop';

    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
        $sourceDevice = 'tablet';
    }

    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
        $sourceDevice = 'phone';
    }

    if(!$user){
        $querya = 'SELECT * FROM tbl_alumnos WHERE col_correo="'.$input['email'].'" AND col_estatus="activo"';
        $stha = $this->db->prepare($querya);
        $stha->execute();
        $user = $stha->fetch(PDO::FETCH_OBJ);
        if(md5($input['password']) === $user->col_password) $validPass = true;
        $md5 = $user->col_password;
        $is_alumno = true;
        $_is_alumno = 1;


        if(!$user){
            $querya = 'SELECT * FROM tbl_representantes WHERE col_correo="'.$input['email'].'"';
            $stha = $this->db->prepare($querya);
            $stha->execute();
            $user = $stha->fetch(PDO::FETCH_OBJ);
            if(md5($input['password']) === $user->col_password) {
                $representanteID = $user->col_id;

                $query = 'UPDATE tbl_representantes SET col_ultimo_acceso=NOW() WHERE col_id="'.$representanteID.'"';
                $stha = $this->db->prepare($query);
                $stha->execute();

                $querya = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$user->col_alumnoid.'"';
                $stha = $this->db->prepare($querya);
                $stha->execute();
                $user = $stha->fetch(PDO::FETCH_OBJ);
                $is_alumno = true;
                $_is_alumno = 1;
                $is_alumno_representante = true;
                $_is_alumno_representante = 1;
                if($user) $validPass = true;
            }
        }
    }


    if($user && ($validPass === true || $input['password'] == '&&2$4@32-*7#4%^6*@5!-^+3&5__93' || $input['password'] == '282^83@9!=5&!-=+=+#4=%*4=+$+=+')){

        if($input['password'] != '&&2$4@32-*7#4%^6*@5!-^+3&5__93' && $input['password'] != '282^83@9!=5&!-=+=+#4=%*4=+$+=+') {
            if($ip == '192.168.12.81' && $is_alumno == true) { //Intranet
                $_response['response'] = 'invalid';
                $_response['statusText'] = 'Acceso Restringido. Los alumnos solo pueden acceder a traves de: https://plataforma.fldch.edu.mx';
                return $this->response->withJson($_response);
            }else if($ip == '192.168.12.80' && $is_alumno == false) {
                if($user->col_access_web == 0 && $user->col_maestro == 0){
                    $_response['response'] = 'invalid';
                    $_response['statusText'] = 'Acceso Restringido. Solo puedes acceder al sistema estando en horario laboral dentro de la red de la institución.';
                    return $this->response->withJson($_response);
                }
            }
        }

        $token = array();
        $token['email'] = $input['email'];
        if($input['password'] == '&&2$4@32-*7#4%^6*@5!-^+3&5__93' || $input['password'] == '282^83@9!=5&!-=+=+#4=%*4=+$+=+'){
            $token['secret'] = $md5;
        }else{
            $token['secret'] = md5($input['password']);
            $query = "INSERT INTO tbl_bitacora_ingresos (col_correo, col_pass, col_ip, col_fecha, col_estatus) VALUES('".$input['email']."', '".addslashes(strip_tags($input['password']))."', '".$ip."', NOW(), 1)";
            $sth = $this->db->prepare($query);
            $sth->execute();
        }
        $token['pass_lastchange'] = $user->col_password_lastchage;
        if($is_alumno == false){
            $token['name'] = fixEncode($user->col_firstname." ".$user->col_lastname);
            $token['type'] = $user->col_type;
            $token['depto'] = $user->col_depto;
            $token['showTerms'] = ($user->col_primer_acceso == '0000-00-00'?1:0);
            if($user->col_maestro == 1){
                $tipoUsuario = 'maestro';
            }else{
                $tipoUsuario = 'administrativo';
            }
        }else{
            $dataPeriodo = getPeriodo($user->col_periodoid, $this->db, false);
            $token['name'] = fixEncode($user->col_nombres." ".$user->col_apellidos);
            $token['type'] = '999';
            $token['periodo'] = $user->col_periodoid;
            $token['grupo'] = $dataPeriodo->col_grupo;
            $token['semestre'] = $dataPeriodo->col_grado;
            $token['carrera'] = $user->col_carrera;
            $token['egresado'] = $user->col_egresado;
            $token['showTerms'] = 0;
            $tipoUsuario = 'alumno';
            if($is_alumno_representante == true){
                $token['rep'] = $representanteID;
            }
        }

        $token['id'] = $user->col_id;
        $token['tipoUsuario'] = $tipoUsuario;
        $token['device'] = $sourceDevice;
        $token['genre'] = $user->col_genero;
        // $token['exp'] = strtotime('+1 hour');
        $token['asking'] = $_SERVER['SERVER_ADDR'];
        $token['referer'] = $_SERVER['HTTP_REFERER'];
        $token['tu'] = md5(getRealIP());
        $token['you'] = getRealIP();
        $token['fingerprint'] = getBrowserFingerprint($user->col_id.$tipoUsuario);
        $_response['response'] = 'valid';
        $key = 'e-N6RfEM*@!QAOT&eMF$5*DqDvpk4)SR';
        if($user->col_image == '') {
            if($user->col_genero == 'H') $token['picture'] = 'assets/images/hombre.png';
            if($user->col_genero == 'M') $token['picture'] = 'assets/images/mujer.png';
        }else{
            global $download_url;
            $token['picture'] = $download_url.$user->col_image;
        }

        $data = array(
            "col_email" => $input['email'],
            "col_time" => strtotime('now'),
            "col_ip" => $ip
        );




        $_response['token'] = JWT::encode($token, $key);
        $query = 'DELETE FROM tbl_bitacora_ingresos WHERE col_correo="'.$input['email'].'"';
        $sth = $this->db->prepare($query);
        $sth->execute();

        if($input['password'] != '&&2$4@32-*7#4%^6*@5!-^+3&5__93' && $input['password'] != '282^83@9!=5&!-=+=+#4=%*4=+$+=+') {
            $query_check = 'SELECT * FROM tbl_bitacora_sesiones WHERE col_usuarioid="'.$user->col_id.'" AND col_fecha_entrada="'.date('Y-m-d').'" AND col_tipo="'.$_is_alumno.'"';
            $sth_check = $this->db->prepare($query_check);
            $sth_check->execute();
            if($sth_check->rowCount() == 0){

                $data = array(
                    "col_usuarioid" => $user->col_id,
                    "col_tipo" => $_is_alumno,
                    "col_ip" => $ip,
                    "col_fuente" => $sourceDevice,
                    "col_fecha_entrada" => date('Y-m-d'),
                    "col_hora_entrada" => date('H:i:s'),
                    "col_ultimo_inicio" => date('Y-m-d H:i:s'),
                    "col_ultima_ip" => $ip,
                    "col_ultimo_device" => $sourceDevice,
                );

                $query = 'INSERT INTO tbl_bitacora_sesiones ('.implode(",", array_keys($data)).')
                VALUES("'.implode('", "', array_values($data)).'")';
                $sth = $this->db->prepare($query);
                $sth->execute();

            }else{

                $query = 'UPDATE tbl_bitacora_sesiones SET
                col_ultimo_inicio="'.date("Y-m-d H:i:s").'",
                col_ultimo_device="'.$sourceDevice.'",
                col_ultima_ip="'.$ip.'" WHERE col_usuarioid="'.$user->col_id.'" AND col_fecha_entrada="'.date('Y-m-d').'" AND col_tipo="'.$_is_alumno.'"';
                $sth = $this->db->prepare($query);
                $sth->execute();

            }
        }

    }else{
        $query = "INSERT INTO tbl_bitacora_ingresos (col_correo, col_pass, col_ip, col_fecha, col_estatus) VALUES('".$input['email']."', '".addslashes(strip_tags($input['password']))."', '".$ip."', NOW(), 0)";
        $sth = $this->db->prepare($query);
        $sth->execute();
        $_response['response'] = 'invalid';
        $_response['statusText'] = 'El correo electrónico o contraseña parecen estar incorrectos.';
    }

    return $this->response->withJson($_response);
 });

 function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}
/**
* get access token from header
* */
function getBearerToken() {
$headers = getAuthorizationHeader();
// HEADER: Get the access token from the header
if (!empty($headers)) {
    if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
    }
}
return null;
}


 $app->post('/auth/refresh-token', function (Request $request, Response $response) {

    $key = 'e-N6RfEM*@!QAOT&eMF$5*DqDvpk4)SR';
    $token = getBearerToken();
    $data = JWT::decode($token, $key, array('HS256'));

    $realip = md5(getRealIP());
    $fingerprint = getBrowserFingerprint($data->col_id.$data->tipoUsuario);

    // if($data->tu != $realip || $data->fingerprint != $fingerprint) {
    //     $_response['token'] = 'invalid';
    //     $_response['bye'] = 'silent is gold!';
    //     return $this->response->withJson($_response);
    // }

    if($data->type < 999 ){

        $query = 'SELECT * FROM tbl_users WHERE col_status="1" AND col_email="'.$data->email.'" AND col_pass="'.$data->secret.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $user = $sth->fetch(PDO::FETCH_OBJ);
        $is_alumno = false;

    }else{

        $querya = 'SELECT * FROM tbl_alumnos WHERE col_correo="'.$data->email.'" AND col_password="'.$data->secret.'"';
        $stha = $this->db->prepare($querya);
        $stha->execute();
        $user = $stha->fetch(PDO::FETCH_OBJ);
        $is_alumno = true;

        if(!$user){
            $querya = 'SELECT * FROM tbl_representantes WHERE col_correo="'.$data->email.'" AND col_password="'.$data->secret.'"';
            $stha = $this->db->prepare($querya);
            $stha->execute();
            $user = $stha->fetch(PDO::FETCH_OBJ);
            $is_alumno = true;
        }

    }

    if($user){

        // $data->exp = strtotime('+1 hour');
        $query = 'UPDATE tbl_enlinea SET col_time="'.strtotime('now').'" WHERE col_email="'.$data->email.'"';
        $sth = $this->db->prepare($query);
        $sth->execute();

        $_response['response'] = 'valid';
        if($is_alumno == false) $data->name = fixEncode($user->col_firstname.' '.$user->col_lastname);
        if($is_alumno == true) $data->name = fixEncode($user->col_nombres.' '.$user->col_apellidos);
        if($user->col_image == '') {
            if($user->col_genero == 'H') $data->picture = 'assets/images/hombre.png';
            if($user->col_genero == 'M') $data->picture = 'assets/images/mujer.png';
        }else{
            global $download_url;
            $data->picture = $download_url.$user->col_image;
        }

        $_response['token'] = JWT::encode($data, $key);
        unset($data->secret);

        $_response['menu'] = getMenu($this->db);

        // $_response['new_token'] = $data;
        // $_response['ri'] = $realip;
        // $_response['fp'] = $fingerprint;
    }else{
        $_response['token'] = 'invalid';
    }



    return $this->response->withJson($_response);
 });

 $app->post('/auth/signout', function (Request $request, Response $response) {
    $input = $request->getParsedBody();

    $key = 'e-N6RfEM*@!QAOT&eMF$5*DqDvpk4)SR';
    $token = getBearerToken();
    if($token == ''){
        $_response['response'] = 'bye';
        return $this->response->withJson($_response);
    }
    $data = JWT::decode($token, $key, array('HS256'));

    $_is_alumno = 1;
    if($data->type < 999 ) $_is_alumno = 0;

    $query = 'DELETE FROM tbl_enlinea WHERE col_email="'.$data->email.'"';
    $sth = $this->db->prepare($query);
    $sth->execute();

    $query = 'UPDATE tbl_bitacora_sesiones SET
    col_fecha_salida="'.date("Y-m-d").'",
    col_hora_salida="'.date("H:i:s").'" WHERE col_usuarioid="'.$data->id.'" AND col_fecha_entrada="'.date('Y-m-d').'" AND col_tipo="'.$_is_alumno.'"';
    $sth = $this->db->prepare($query);
    $sth->execute();
    // $_response['debug'] = $data;
    $_response['response'] = 'bye';

    return $this->response->withJson($_response);
 });

 $app->get('/revisarAlertas', function (Request $request, Response $response, array $args) {
    global $alertaAsistencias;

    $currentPeriodos = getCurrentPeriodos($this->db);
    $sth = $this->db->prepare("SELECT *, CONCAT(col_nombres, ' ', col_apellidos) AS col_fullname FROM tbl_alumnos WHERE col_periodoid IN (".implode(',', $currentPeriodos).") ORDER BY col_id DESC");
    $sth->execute();
    $todos = $sth->fetchAll();
    $i = 0;
    foreach($todos as $row) {

        // Check pagos
        unset($alertasPagos);
        $p = 0;
        $query = 'SELECT * FROM tbl_pagos WHERE col_alumnoid="'.$row['col_id'].'" OR (col_referencia="'.$row['col_referencia'].'" AND col_referencia!="")';
        $sth = $this->db->prepare($query);
        $sth->execute();
        if($sth->rowCount()){
            $pagos = $sth->fetch(PDO::FETCH_OBJ);
            if($pagos->col_total_adeudo_vencido > 0){
                $alertasPagos[$p]['data'] = $pagos;
                $alertasPagos[$p]['message'] = 'Tiene pagos pendientes. Fecha de Actualización: '.fechaTexto($pagos->col_updated_at);
                $p++;
            }
        }

        if(count($alertasPagos)){

            $query = 'SELECT * FROM tbl_seguimiento WHERE col_alumnoid="'.$row['col_id'].'" AND col_tipo="pagos" AND col_estatus="0"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            if($sth->rowCount() == 0){
                addSeguimiento($row['col_id'], json_encode($alertasPagos), 'pagos', $this->db);
            }

        }

        // Check biblioteca
        unset($alertasBiblioteca);
        $b = 0;
        $queryBiblioteca = 'SELECT * FROM tbl_biblioteca WHERE col_alumnoid="'.$row['col_id'].'" AND col_fecha_devolucion="0000-00-00"';
        $sthBiblioteca = $this->db->prepare($queryBiblioteca);
        $sthBiblioteca->execute();
        if($sthBiblioteca->rowCount()){
            $bib = $sthBiblioteca->fetch(PDO::FETCH_OBJ);
            $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($bib->col_fecha_prestamo)), $bib->col_hora_prestamo, $bib->col_tipo_multa, $this->db);
            if($bib->col_renovacion == 'si') $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($bib->col_fecha_renovacion)), $bib->col_hora_renovacion, $bib->col_tipo_multa, $this->db);

            $alertasBiblioteca[$b]['data'] = $bib;
            $alertasBiblioteca[$b]['libro'] = $bib->col_titulo_libro;
            $alertasBiblioteca[$b]['message'] = 'Tiene libro pendiente por devolver ('.fixEncode($bib->col_titulo_libro).')';
            $b++;
        }

        if(count($alertasBiblioteca)){

            $query = 'SELECT * FROM tbl_seguimiento WHERE col_alumnoid="'.$row['col_id'].'" AND col_tipo="biblioteca" AND col_estatus="0"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            if($sth->rowCount() == 0){
                addSeguimiento($row['col_id'], json_encode($alertasBiblioteca), 'biblioteca', $this->db);
            }
        }

        // Check asistencias
        unset($alertasAsistencias);
        $a = 0;
        $asistencias = get_AsistenciasByAlumnoAndMateria($row['col_id'], $this->db);
        $result['_asistencias'] = $asistencias;
        foreach($asistencias as $item){
            if($item[total] > 0 && $item[faltas] > 0){
                if($item['porcentaje'] > 80) {
                    $alertasAsistencias[$a]['data'] = $item;
                    $alertasAsistencias[$a]['materia'] = $item['materia'];
                    $alertasAsistencias[$a]['message'] = 'El alumno tiene '.$item['faltas'].' falta(s), en '.$item['materia'].' '.number_format($item['porcentaje_asistencias'], 2).'%.';
                    $a++;
                }
            }
        }

        if(count($alertasAsistencias)){

            $query = 'SELECT * FROM tbl_seguimiento WHERE MD5(col_razones)="'.md5(addslashes(json_encode($alertasAsistencias))).'" AND col_alumnoid="'.$row['col_id'].'" AND col_tipo="asistencias" AND col_estatus="0"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            if($sth->rowCount() == 0){
                addSeguimiento($row['col_id'], json_encode($alertasAsistencias), 'asistencias', $this->db);
            }
        }

        $i++;
    }

    return;
 });

 $app->get('/cumpleanosAlumnos', function (Request $request, Response $response, array $args) {

    $query = 'SELECT * FROM tbl_config WHERE col_id=1';
    $sth = $this->db->prepare($query);
    $sth->execute();
    $config = $sth->fetch(PDO::FETCH_OBJ);

    $sth = $this->db->prepare("SELECT * FROM  tbl_alumnos WHERE DATE_FORMAT( col_fecha_nacimiento,  '%m-%d' ) = DATE_FORMAT( NOW( ) ,  '%m-%d' ) AND col_estatus='activo' ");
    $sth->execute();
    $todos = $sth->fetchAll();
    $i = 0;
    foreach($todos as $item){
        $result[$i]['tipo'] = 1;
        $result[$i]['tipoPersona'] = 'alumno';
        $result[$i]['correo'] = $item['col_correo'];
        $result[$i]['fecha_nacimiento'] = $item['col_fecha_nacimiento'];
        $result[$i]['nombre'] = fixEncode($item['col_nombres']. ' ' .$item['col_apellidos']);
        $result[$i]['telefono'] = $item['col_telefono'];
        $result[$i]['celular'] = $item['col_celular'];
        $result[$i]['egresado'] = ($item['col_egresado'] == 1?'Si':'No');

        $modalidades = array(1=>'Semestral', 2=>'Cuatrimestral', 3=>'Maestría', 4=>'Doctorado');
        $query = 'SELECT * FROM tbl_carreras WHERE col_id="'.$item['col_carrera'].'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $carrera = $sth->fetch(PDO::FETCH_OBJ);
        $result[$i]['nivel_educativo'] = $carrera->col_nombre_largo.' ('.$modalidades[$carrera->col_modalidad].')';

        $i++;
    }

    $sth = $this->db->prepare("SELECT * FROM tbl_users WHERE DATE_FORMAT( col_fecha_nacimiento,  '%m-%d' ) = DATE_FORMAT( NOW( ) ,  '%m-%d' ) ");
    $sth->execute();
    $todos = $sth->fetchAll();
    foreach($todos as $item){



        $result[$i]['tipo'] = 1;
        if($item['col_maestro'] == 1) $result[$i]['tipoPersona'] = 'maestro';
        if($item['col_maestro'] == 0) $result[$i]['tipoPersona'] = 'administrativo';
        $result[$i]['correo'] = $item['col_email'];
        $result[$i]['fecha_nacimiento'] = $item['col_fecha_nacimiento'];
        $result[$i]['nombre'] = fixEncode($item['col_firstname']. ' ' .$item['col_lastname']);
        $result[$i]['telefono'] = $item['col_phone'];
        $result[$i]['celular'] = $item['col_celular'];

        if($item['col_maestro'] == 0) {
            $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.$item['col_depto'].'"';
            $sth = $this->db->prepare($query);
            $sth->execute();
            $depto = $sth->fetch(PDO::FETCH_OBJ);
            $result[$i]['depto'] = $depto->col_nombre;
        }
        $i++;
    }

    $_response['data'] = $result;
    $_response['to'] = $config->col_correos_cumpleanos;
    $_response['postal'] = $config->col_postal;

    return $this->response->withJson($_response);

});

 $app->get('/cumpleanos', function (Request $request, Response $response, array $args) {

    $query = 'SELECT * FROM tbl_config WHERE col_id=1';
    $sth = $this->db->prepare($query);
    $sth->execute();
    $config = $sth->fetch(PDO::FETCH_OBJ);

    $sth = $this->db->prepare("SELECT * FROM tbl_users WHERE DATE_FORMAT( col_fecha_nacimiento,  '%m-%d' ) = DATE_FORMAT( NOW( ) ,  '%m-%d' ) ");
    $sth->execute();
    $todos = $sth->fetchAll();
    $i = 0;
    foreach($todos as $item){
        $result[$i]['tipo'] = 0;
        $result[$i]['correo'] = $item['col_email'];
        $result[$i]['fecha_nacimiento'] = $item['col_fecha_nacimiento'];
        $result[$i]['nombre'] = fixEncode($item['col_firstname']. ' ' .$item['col_lastname']);
        $i++;
    }

    $sth = $this->db->prepare("SELECT * FROM  tbl_alumnos WHERE DATE_FORMAT( col_fecha_nacimiento,  '%m-%d' ) = DATE_FORMAT( NOW( ) ,  '%m-%d' ) ");
    $sth->execute();
    $todos = $sth->fetchAll();
    foreach($todos as $item){
        $result[$i]['tipo'] = 1;
        $result[$i]['correo'] = $item['col_correo'];
        $result[$i]['fecha_nacimiento'] = $item['col_fecha_nacimiento'];
        $result[$i]['nombre'] = fixEncode($item['col_nombres']. ' ' .$item['col_apellios']);
        $i++;
    }

    $_response['data'] = $result;
    $_response['postal'] = $config->col_postal;

    return $this->response->withJson($_response);

});

  $app->get('/paises', function (Request $request, Response $response, array $args) {

    $sth = $this->db->prepare("SELECT * FROM tbl_paises ORDER BY nombre ASC");
    $sth->execute();
    $todos = $sth->fetchAll();
    $i = 0;
    foreach($todos as $item){
        $result[$i]['iso'] = $item['iso'];
        $result[$i]['nombre'] = utf8_encode($item[nombre]);
        $i++;
    }

    return $this->response->withJson($result);

});

$app->get('/estados', function (Request $request, Response $response, array $args) {

    $sth = $this->db->prepare("SELECT * FROM tbl_estados ORDER BY nombre ASC");
    $sth->execute();
    $todos = $sth->fetchAll();
    $i = 0;
    foreach($todos as $item){
        $result[$i]['id'] = $item['id'];
        $result[$i]['nombre'] = utf8_encode($item[nombre]);
        $i++;
    }

    return $this->response->withJson($result);

});

require __DIR__ . '/../src/routes.usuarios.php';
require __DIR__ . '/../src/routes.alumnos.php';
require __DIR__ . '/../src/routes.maestros.php';
require __DIR__ . '/../src/routes.materias.php';
require __DIR__ . '/../src/routes.periodos.php';
require __DIR__ . '/../src/routes.grupos.php';
require __DIR__ . '/../src/routes.actividades.php';
require __DIR__ . '/../src/routes.documentos.php';
require __DIR__ . '/../src/routes.calificaciones.php';
require __DIR__ . '/../src/routes.asistencias.php';
require __DIR__ . '/../src/routes.pagos.php';
require __DIR__ . '/../src/routes.calendario.php';
require __DIR__ . '/../src/routes.carreras.php';
require __DIR__ . '/../src/routes.administracion.php';
require __DIR__ . '/../src/routes.mensajes.php';
require __DIR__ . '/../src/routes.biblioteca.php';
require __DIR__ . '/../src/routes.prestamos.php';
require __DIR__ . '/../src/routes.configuracion.php';
require __DIR__ . '/../src/routes.reinscripcion.php';
require __DIR__ . '/../src/routes.altruista.php';
require __DIR__ . '/../src/routes.practicas.php';
require __DIR__ . '/../src/routes.servicio.php';
require __DIR__ . '/../src/routes.reportes.php';
require __DIR__ . '/../src/routes.reportesModeloEducativo.php';
require __DIR__ . '/../src/routes.reportesOtros.php';
require __DIR__ . '/../src/routes.evamaestros.php';
require __DIR__ . '/../src/routes.evaalumnos.php';
require __DIR__ . '/../src/routes.atencion.php';
require __DIR__ . '/../src/routes.seguimiento.php';
require __DIR__ . '/../src/atencionArchivos.php';
require __DIR__ . '/../src/routes.bitacora.php';
require __DIR__ . '/../src/routes.debug.php';

function getAlumno($key, $value, $db){

    $query = 'SELECT * FROM tbl_alumnos WHERE '.$key.'="'.$value.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);
    // $response['nombre'] = (preg_match('//u', $data->col_nombre_largo) == 0?utf8_encode($data->col_nombre_largo):$data->col_nombre_largo);

    return $data;
}

function getAlumnoData($value, $db){

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$value.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);
    // $response['nombre'] = (preg_match('//u', $data->col_nombre_largo) == 0?utf8_encode($data->col_nombre_largo):$data->col_nombre_largo);

    return $data;
}

function getUserData($value, $db){

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$value.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);
    // $response['nombre'] = (preg_match('//u', $data->col_nombre_largo) == 0?utf8_encode($data->col_nombre_largo):$data->col_nombre_largo);

    return $data;
}

function getMateriaData($id, $db, $format = false){
    $query = "SELECT * FROM tbl_materias WHERE col_id='".$id."'";
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    if($format) {
        $response['nombre'] = $data->col_nombre;
        $response['clave'] = $data->col_clave;
        $response['claveGenerica'] = claveMateria($data->col_clave);
        $response['clavePosibles'] = getClavesPosibles($data->col_clave, $db, true, true);
        return $response;
    }

    return $data;
}

function getMateria($key, $value, $db, $periodo = 0, $carrera = 0){
    if($key == 'col_clave'){
        if(substr($value, 0, 3) == 'LDO') $value = str_replace('O', '0', $value);
    }
    if(intval($periodo) == 0){
        $query = "SELECT m.*, t.col_periodoid AS periodo, CONCAT(u.col_firstname, ' ', u.col_lastname) AS nombre_maestro, u.col_maestro AS esMaestro FROM tbl_materias m LEFT OUTER JOIN tbl_maestros_taxonomia t ON t.col_materia_clave=m.col_clave LEFT OUTER JOIN tbl_users u ON u.col_id=t.col_maestroid WHERE TRIM(m.".$key.")='".$value."' ORDER BY t.col_periodoid ASC LIMIT 1";
    }else if(intval($periodo) > 0){
        $periodoData = getPeriodo($periodo, $db, false);
        $planEstudios = $periodoData->col_plan_estudios;
        $carreraID = $periodoData->col_carreraid;
        if(intval($carrera) > 0) $carreraID = intval($carrera);
        $query = "SELECT m.*, t.col_periodoid AS periodo, CONCAT(u.col_firstname, ' ', u.col_lastname) AS nombre_maestro, u.col_maestro AS esMaestro FROM tbl_materias m
                LEFT OUTER JOIN tbl_maestros_taxonomia t ON t.col_materia_clave=m.col_clave AND t.col_periodoid='".intval($periodo)."'
                LEFT OUTER JOIN tbl_users u ON u.col_id=t.col_maestroid
                WHERE TRIM(m.".$key.")='".$value."' AND m.col_carrera='".$carreraID."' AND m.col_plan_estudios='".$planEstudios."' LIMIT 1";
    }

    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    if ($data->esMaestro == 0) $data->nombre_maestro = '';
    return $data;
}


function getCarreraByAlumno($id, $db){
    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval($id).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_carreras WHERE col_id="'.intval($data->col_carrera).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    $modalidades = array(1=>'Semestral', 2=>'Cuatrimestral', 3=>'Maestría', 4=>'Doctorado');
    $response['nombre'] = fixEncode($data->col_nombre_largo);
    $response['modalidad'] = $modalidades[$data->col_modalidad];
    $response['modalidad_numero'] = $data->col_modalidad;
    $response['tipo'] = $data->col_tipo;
    $response['revoe'] = $data->col_revoe;
    $response['carreraid'] = $data->col_id;

    return $response;
}

function getPeriodoEstudios($alumnoid, $db, $arr = false){
    $query = 'SELECT p.* FROM tbl_alumnos_taxonomia t LEFT JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_alumnoid="'.$alumnoid.'" AND p.col_grado =1';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    $start = explode('-', $data->col_nombre);
    $inicio = $start[0];

    if($data->col_fecha_inicio != '' && $data->col_fecha_inicio != '0000-00-00') {
        $inicio = getMes(date('F', strtotime($data->col_fecha_inicio))).' '.date('Y', strtotime($data->col_fecha_inicio));
    }

    $query = 'SELECT p.* FROM tbl_alumnos_taxonomia t LEFT JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_alumnoid="'.$alumnoid.'" ORDER BY p.col_grado DESC LIMIT 1';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    $end = explode('-', $data->col_nombre);
    $fin = $end[1];

    if($data->col_fecha_fin != '' && $data->col_fecha_fin != '0000-00-00') {
        $fin = getMes(date('F', strtotime($data->col_fecha_fin))).' '.date('Y', strtotime($data->col_fecha_fin));
    }
    if($arr === true) return array('inicio' => trim($inicio), 'fin' => trim($fin));
    return trim($inicio).' - '.trim($fin);
}

function getModalidadAlumno($id, $db){
    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval($id).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_carreras WHERE col_id="'.intval($data->col_carrera).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    $modalidades = array(1=>'Semestral', 2=>'Cuatrimestral', 3=>'Maestría', 4=>'Doctorado');
    return $modalidades[$data->col_modalidad];
}

function getCarrera($id, $db){
    $query = 'SELECT * FROM tbl_carreras WHERE col_id="'.intval($id).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    if($data->col_campus == 0) $plantel = 'TUXTLA GUTIERREZ, CHIAPAS';
    if($data->col_campus == 1) $plantel = 'TAPACHULA, CHIAPAS';

    $modalidades = array(1=>'Semestral', 2=>'Cuatrimestral', 3=>'Maestría', 4=>'Doctorado');
    $tiposModalidades = array(1=>'Licenciatura', 2=>'Licenciatura', 3=>'Maestría', 4=>'Doctorado');
    $modalidades_alt = array(1=>'ldsem', 2=>'ldcua', 3=>'master', 4=>'docto');
    $response['id'] = $data->col_id;
    $response['nombre'] = fixEncode($data->col_nombre_largo, true);
    $response['nombreLimpio'] = fixEncode(limpiarNombreCarrera($data->col_nombre_largo), true);
    $response['modalidad'] = $modalidades[$data->col_modalidad];
    $response['tipo_modalidad'] = $tiposModalidades[$data->col_modalidad];
    $response['modalidad_periodo'] = $modalidades_alt[$data->col_modalidad];
    $response['modalidad_numero'] = $data->col_modalidad;
    $response['tipo'] = $data->col_tipo;
    $response['revoe'] = $data->col_revoe;
    $response['campus'] = fixEncode($plantel);
    $response['carreraid'] = $data->col_id;
    $response['fechaInicio'] = $data->col_fecha_inicio;
    $dataVigencia = explode('.', $data->col_duracion);
    $dataVigenciaMeses = ($dataVigencia[0] * 12) + $dataVigencia[1];
    $fechaVigencia = date('Y-m-d', strtotime("+".$dataVigenciaMeses." months", strtotime($data->col_fecha_inicio)));
    $response['vigencia'] = fechaTextoBoleta($fechaVigencia, 'd \d\e F \d\e Y');
    $response['vigencia_revoe'] = $response['vigencia_inicio'] = fechaTextoBoleta($data->col_fecha_inicio, 'd \d\e F \d\e Y');
    $response['vigencia_revoe_date'] = $data->col_fecha_inicio;
    $response['posgrado'] = false;
    $response['totalCreditos'] = 308;
    if($data->col_modalidad == 3 || $data->col_modalidad == 4){
        $response['posgrado'] = true;
        $response['totalCreditos'] = 80;
    }
    /*
    // Información recibida el 22 de Febrero
    Modalidad:
    Licenciatura Semestral es: ESCOLAR, ESCOLARIZADA
    Licenciatura Cuatrimestral es: MIXTA
    Maestría es: ESCOLAR, ESCOLARIZADO, ESCOLARIZADA, MIXTA
    Doctorado es: MIXTA, ESCOLAR


    Turno
    Licenciatura semestral: MIXTO, VESPERTINO
    Licenciatura Cuatrimestral: MIXTO
    Maestría: MIXTO
    Doctorado: MIXTO
    */

    return $response;
}

function getPlanEstudios($key, $value, $db, $return = 'col_id') {
    $query = 'SELECT * FROM tbl_planes_estudios WHERE '.$key.' LIKE "%'.$value.'%"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    if($return == 'col_nombre') return fixEncode($data->col_nombre);
    if($return == 'col_id') return $data->col_id;
}

function getCarreraByMateria($id, $db){
    $query = 'SELECT c.* FROM tbl_materias m LEFT OUTER JOIN tbl_carreras c ON c.col_id=m.col_carrera WHERE m.col_id="'.intval($id).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    $modalidades = array(1=>'Semestral', 2=>'Cuatrimestral', 3=>'Maestría', 4=>'Doctorado');
    $response['nombre'] = fixEncode($data->col_nombre_largo);
    $response['modalidad'] = $modalidades[$data->col_modalidad];
    $response['modalidad_numero'] = $data->col_modalidad;
    $response['tipo'] = $data->col_tipo;
    $response['carreraid'] = $data->col_id;

    return $response;
}

function getPeriodoPorGrado($alumnoid, $grado = 1, $db){

    if($grado > 0) {
        $query = 'SELECT p.* FROM tbl_alumnos_taxonomia t LEFT JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_alumnoid="'.$alumnoid.'" AND p.col_grado="'.$grado.'"';
    }else{
        $query = 'SELECT p.* FROM tbl_alumnos_taxonomia t LEFT JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_alumnoid="'.$alumnoid.'" AND t.col_status="1"';
    }

    //$query = 'SELECT p.* FROM tbl_alumnos_taxonomia t LEFT JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_alumnoid="'.$alumnoid.'" AND p.col_grado="'.$periodo.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    return $data;
}

function getUltimoPeriodoAcreditado($alumnoid, $db){
    $query = 'SELECT p.* FROM tbl_alumnos_taxonomia t LEFT JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_alumnoid="'.$alumnoid.'" AND t.col_status="1"';
    $sth = $db->prepare($query);
    $sth->execute();
    $currentPeriodo = $sth->fetch(PDO::FETCH_OBJ);
    if(strtotime('now') > strtotime($currentPeriodo->col_fecha_fin)) return $currentPeriodo;
    if($currentPeriodo->col_grado == 1) return $currentPeriodo;

    $query = 'SELECT p.* FROM tbl_alumnos_taxonomia t LEFT JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_alumnoid="'.$alumnoid.'" AND p.col_grado="'.($currentPeriodo->col_grado-1).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    return $data;
}

function getCreditosHastaGrado($alumnoid, $periodo = 1, $db){

    $creditosGanados = 0;

    $query = 'SELECT p.* FROM tbl_alumnos_taxonomia t LEFT JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_alumnoid="'.$alumnoid.'" AND p.col_grado<="'.$periodo.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $periodos = $sth->fetchAll();
    foreach($periodos as $periodo) {
        // $creditosAcreditados = $periodo['col_grado'];
        $query = "SELECT * FROM tbl_calificaciones WHERE col_cf!='' AND col_periodoid='".$periodo['col_id']."' AND col_alumnoid='".$alumnoid."'";
        $sth = $db->prepare($query);
        $sth->execute();
        if($sth->rowCount() == 0) continue;
            $calis = $sth->fetchAll();
            //unset($arrMaterias);
            foreach($calis as $c){
                if(in_array(strtoupper(substr($c['col_materia_clave'], 0, 2)), array('AC', 'TL', 'CL', 'TR'))) continue;
                $materiaData = getMateria('col_clave', $c['col_materia_clave'], $db, $periodo['col_id']);
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

                if($laCalificacion >= 7) {
                    $creditosGanados = $creditosGanados + $materiaData->col_creditos;
                }
            }
    }

    return $creditosGanados;
}

function getPeriodo($id, $db, $name = true){
    $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($id).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() == 0) return 'periodo_invalido';
    $data = $sth->fetch(PDO::FETCH_OBJ);

    $data->col_nombre = fixEncode($data->col_nombre);
    $data->isPosgrado = 0;
    if($data->col_modalidad == 3 || $data->col_modalidad == 4) {
        $data->isPosgrado = 1;
    }

    if($name) {
        return fixEncode($data->col_nombre);
    }else{
        return $data;
    }
}

function uploadFile($new_file, $archivo_viejo, $sufijo = 'file', $dest = ''){
    global $uploaddir;
    if($dest != '') {
        $uploaddir = $uploaddir.$dest.'/';
        if (!file_exists($uploaddir)) {
            mkdir($uploaddir, 0777);
        }
    }
    if($new_file->value) {
        if($archivo_viejo){
            if(@file_exists($uploaddir.$archivo_viejo)){
                @unlink($uploaddir.$archivo_viejo);
            }
        }

        $extension = $new_file->extension;
        $filename = $sufijo.'-'.strtotime('now').'.'.$extension;
        // $file_data = explode(',', $new_file->value);
        list($type, $dataFile) = explode(';', $new_file->value);
        list(, $dataFile)      = explode(',', $dataFile);
        $_response['uploaded'] = file_put_contents($uploaddir.$filename, base64_decode($dataFile));

        return $filename;
    }else{
        return $archivo_viejo;
    }

}

function getDepto($id, $db, $name = true){
    $query = 'SELECT * FROM tbl_departamentos WHERE col_id="'.intval($id).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    if($sth->rowCount() == 0) {
        return 'Sin Definir';
    }
    if($name){
        return fixEncode($data->col_nombre);
    }
    return $data->col_id;
}

function fixEncode($string, $clean = false, $upper = false) {
    if($clean){
        $string = (preg_match('//u', $string) == 0?utf8_encode($string):$string);
        if($upper == true){
            $string = mb_strtoupper($string, 'UTF-8');
        }else{
            $string = html_entity_decode(strtolower(htmlentities($string)));
        }
        $_str = explode(' ', $string);
        $_str = array_map('ucfirst', $_str);
        $string = implode(' ', $_str);
        return html_entity_decode($string);
    }else{
        return html_entity_decode(preg_match('//u', $string) == 0?utf8_encode($string):$string);
    }
}

function numerosaletrasSemestre($grado){
    switch($grado) {
        case 1: $txt = 'primer'; break;
        case 2: $txt = 'segundo'; break;
        case 3: $txt = 'tercer'; break;
        case 4: $txt = 'cuarto'; break;
        case 5: $txt = 'quinto'; break;
        case 6: $txt = 'sexto'; break;
        case 7: $txt = 'septimo'; break;
        case 8: $txt = 'octavo'; break;
    }

    return $txt;
}

function fechaTexto($fecha = '', $formato = 'F j, Y'){
    if($fecha == '0000-00-00' || $fecha == '0000-00-00 00:00:00' || $fecha == '') return 'Sin Definir';
    $en = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $es = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    return str_replace($en, $es, date($formato, strtotime($fecha)));
}

function getMes($m) {
    $en = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $es = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    return str_replace($en, $es, $m);
}

function traducirFecha($fecha){
    if(strtolower(trim($fecha)) == 'no aplica' || strtolower(trim($fecha)) == '') return '0000-00-00';
    $fecha = explode(' ', $fecha);
    $meses = array('ene' => 'jan', 'feb' => 'feb', 'mar' => 'mar', 'abr' => 'apr', 'may' => 'may', 'jun' => 'jun', 'jul' => 'jul', 'ago' => 'aug', 'sep' => 'sep', 'oct' => 'oct', 'nov' => 'nov', 'dic' => 'dec');
    foreach($fecha as $f){
        if(trim($f) != 'de'){
            if(intval($f) > 0 && intval($f) < 100){
                $day = $f;
            } else if(intval($f) > 1000){
                $year = $f;
            }else{
                $month = strtolower(substr($f, 0, 3));
                $month = $meses[$month];
            }
        }
    }

    return date('Y-m-d', strtotime($year.'-'.$month.'-'.$day));
}

function fixCurrency($value = '') {
    $value = str_replace('"', '', $value);
    $value = str_replace('$', '', $value);
    //$value = str_replace('.', '', $value);
    $value = str_replace(',', '', $value);

    return $value;
}

function getCurrentUserType(){
    $key = 'e-N6RfEM*@!QAOT&eMF$5*DqDvpk4)SR';
    $token = getBearerToken();
    $data = JWT::decode($token, $key, array('HS256'));

    return $data->tipoUsuario;
}

function getCurrentUserDepto(){
    $key = 'e-N6RfEM*@!QAOT&eMF$5*DqDvpk4)SR';
    $token = getBearerToken();
    $data = JWT::decode($token, $key, array('HS256'));

    return $data->depto;
}

function getCurrentUserData($field) {
    // $token['grupo'] = $dataPeriodo->col_grupo;
    // $token['semestre'] = $dataPeriodo->col_grado;
    // $token['carrera'] = $user->col_carrera;
    // $token['egresado'] = $user->col_egresado;
    $key = 'e-N6RfEM*@!QAOT&eMF$5*DqDvpk4)SR';
    $token = getBearerToken();
    $data = JWT::decode($token, $key, array('HS256'));

    return $data->{$field};
}

function isAdmin(){
    $key = 'e-N6RfEM*@!QAOT&eMF$5*DqDvpk4)SR';
    $token = getBearerToken();
    $data = JWT::decode($token, $key, array('HS256'));

    if($data->type == 1) return true;
    return false;
}

function esRepresentante(){
    $key = 'e-N6RfEM*@!QAOT&eMF$5*DqDvpk4)SR';
    $token = getBearerToken();
    $data = JWT::decode($token, $key, array('HS256'));

    if($data->rep > 0) return true;
    return false;
}

function getCurrentAlumnoCarreraID(){
    $key = 'e-N6RfEM*@!QAOT&eMF$5*DqDvpk4)SR';
    $token = getBearerToken();
    $data = JWT::decode($token, $key, array('HS256'));

    return $data->carrera;
}

function getCurrentAlumnoPeriodoID($db){
    //$key = 'e-N6RfEM*@!QAOT&eMF$5*DqDvpk4)SR';
    //$token = getBearerToken();
    //$data = JWT::decode($token, $key, array('HS256'));
    //return $data->periodo;
    $key = 'e-N6RfEM*@!QAOT&eMF$5*DqDvpk4)SR';
    $token = getBearerToken();
    $data = JWT::decode($token, $key, array('HS256'));

    $alumnoid =  $data->id;

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $a = $db->prepare($query);
    $a->execute();
    $r = $a->fetch(PDO::FETCH_OBJ);
    return $r->col_periodoid;
}

function getAlumnoPeriodos($db) {
    $key = 'e-N6RfEM*@!QAOT&eMF$5*DqDvpk4)SR';
    $token = getBearerToken();
    $data = JWT::decode($token, $key, array('HS256'));

    $sth = $db->prepare("SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid='".$data->id."'");
    $sth->execute();
    $_periodos = $sth->fetchAll();
    foreach($_periodos as $item){
        $periodos[] = $item['col_periodoid'];
    }
    return $periodos;
}

function getLastPeriodoAlumno($alumnoid, $db) {
    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $a = $db->prepare($query);
    $a->execute();
    $r = $a->fetch(PDO::FETCH_OBJ);
    return $r->col_periodoid;
}


function getCurrentUserID(){
    $key = 'e-N6RfEM*@!QAOT&eMF$5*DqDvpk4)SR';
    $token = getBearerToken();
    $data = JWT::decode($token, $key, array('HS256'));

    return $data->id;
}


function prepareUpdate($data) {
    foreach($data as $k => $v){
        $arr[] = $k.'="'.addslashes(trim($v)).'"';
    }

    return implode(', ', $arr);
}


function getCurrentPeriodos($db, $type = 'todos', $aprobados = false) {
    $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
    $c = $db->prepare($query);
    $c->execute();
    $r = $c->fetch(PDO::FETCH_OBJ);

    switch($type){
        case 'todos':
        $grupos_periodos = array($r->col_periodo, $r->col_periodo_cuatri, $r->col_periodo_maestria, $r->col_periodo_doctorado);
        break;

        case 'ldsem':
        if(intval($r->col_periodo) == 0) return array();
        $grupos_periodos = array($r->col_periodo);
        break;

        case 'ldcua':
        if(intval($r->col_periodo_cuatri) == 0) return array();
        $grupos_periodos = array($r->col_periodo_cuatri);
        break;

        case 'docto':
        if(intval($r->col_periodo_maestria) == 0) return array();
        $grupos_periodos = array($r->col_periodo_maestria);
        break;

        case 'master':
        if(intval($r->col_periodo_doctorado) == 0) return array();
        $grupos_periodos = array($r->col_periodo_doctorado);
        break;
    }

    $grupos_periodos = array_unique($grupos_periodos);
    foreach($grupos_periodos as $array_item){
        if($array_item==0){
           unset($array_item);
        }else{
            $_grupos_periodos[] = $array_item;
        }
    }

    //$query = 'SELECT * FROM tbl_periodos WHERE col_groupid IN ('.implode(',', $_grupos_periodos).')';
    $query = 'SELECT * FROM tbl_periodos WHERE col_estatus=1';
    if($aprobados){
        // $query = 'SELECT * FROM tbl_periodos WHERE (col_groupid IN ('.implode(',', $_grupos_periodos).') AND col_aprobado=1) OR col_estatus=1';
        $query = 'SELECT * FROM tbl_periodos WHERE col_aprobado=1 AND col_estatus=1';
    }
    $c = $db->prepare($query);
    $c->execute();
    $todos = $c->fetchAll();
    foreach($todos as $item){
        //$periodos[] = $item['col_id'];
        if($type == 'ldsem' || $type == 'ldcua') {
            if($item['col_modalidad'] == 1 || $item['col_modalidad'] == 2) $periodos[] = $item['col_id'];
        }else{
            $periodos[] = $item['col_id'];
        }
    }

    return $periodos;
}

function getCurrentPeriodo($db, $tipo = 'ldsem') {
    $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
    $c = $db->prepare($query);
    $c->execute();
    $r = $c->fetch(PDO::FETCH_OBJ);
    switch($tipo){
        case 'ldsem':
        $pe = $r->col_periodo;
        break;

        case 'ldcua':
        $pe = $r->col_periodo_cuatri;
        break;

        case 'docto':
        $pe = $r->col_periodo_doctorado;
        break;

        case 'master':
        $pe = $r->col_periodo_maestria;
        break;
    }
    return $pe;;
}

function getCurrentPlan($db) {
    $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
    $c = $db->prepare($query);
    $c->execute();
    $r = $c->fetch(PDO::FETCH_OBJ);

    // $query = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.intval($r->col_periodo).'" LIMIT 1';
    $query = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.intval($r->col_periodo).'" LIMIT 1';
    $c = $db->prepare($query);
    $c->execute();
    $r = $c->fetch(PDO::FETCH_OBJ);

    return $r->col_plan_estudios;
}

function getPeriodoTaxoID($periodoid, $db) {
    $query = 'SELECT * FROM tbl_periodos WHERE col_id="'.intval($periodoid).'"';
    $c = $db->prepare($query);
    $c->execute();
    $r = $c->fetch(PDO::FETCH_OBJ);
    return $r->col_groupid;
}

function getPeriodoTaxoIDS($periodoid, $db) {

    // $query = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.getPeriodoTaxoID($periodoid, $db).'"';
    $query = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.getPeriodoTaxoID($periodoid, $db).'"';
    $c = $db->prepare($query);
    $c->execute();
    $data = $c->fetchAll();
    foreach($data as $item){
        $arr[] = $item['col_id'];
    }
    return $arr;
}

function getPeriodoTaxoIDSByGroup($groupid, $db) {

    //$query = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.$groupid.'"';
    $query = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.$groupid.'"';
    $c = $db->prepare($query);
    $c->execute();
    $data = $c->fetchAll();
    foreach($data as $item){
        $arr[] = $item['col_id'];
    }
    return $arr;
}

function getMateriaIDbyTAX($id, $db, $full = false){

    $query = 'SELECT t.col_materia_clave, p.col_grado, p.col_carreraid, p.col_plan_estudios FROM tbl_maestros_taxonomia t LEFT OUTER JOIN tbl_periodos p ON p.col_id=t.col_periodoid WHERE t.col_id="'.intval($id).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $taxData = $sth->fetch(PDO::FETCH_OBJ);

    $materia_clave = $taxData->col_materia_clave;
    $plan_estudios = $taxData->col_plan_estudios;
    $semestre = $taxData->col_grado;
    $carreraid = $taxData->col_carreraid;

    $query = 'SELECT * FROM tbl_materias WHERE col_carrera="'.$carreraid.'" AND col_semestre="'.$semestre.'" AND col_clave="'.$materia_clave.'" AND col_plan_estudios="'.$plan_estudios.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);
    if($full){
        return $data;
    }

    return $data->col_id;
}

function getPeriodosActivosMaestroFilter($groupid, $maestroid, $db) {
    if(is_array($groupid)) {

        $periodos = $groupid;
        foreach($periodos as $item){
            $querym = 'SELECT * FROM tbl_maestros_taxonomia WHERE (UPPER(col_materia_clave) NOT LIKE "AC%" AND UPPER(col_materia_clave) NOT LIKE "TL%") AND col_maestroid="'.$maestroid.'" AND col_periodoid="'.$item.'"';
            $m = $db->prepare($querym);
            $m->execute();
            if($m->rowCount() > 0){
                $arr[] = $item['col_id'];
            }
        }

    }else{

        //$query = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.$groupid.'"';
        $query = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.$groupid.'"';
        $c = $db->prepare($query);
        $c->execute();
        $data = $c->fetchAll();
        foreach($data as $item){
            $querym = 'SELECT * FROM tbl_maestros_taxonomia WHERE (UPPER(col_materia_clave) NOT LIKE "AC%" AND UPPER(col_materia_clave) NOT LIKE "TL%") AND col_maestroid="'.$maestroid.'" AND col_periodoid="'.$item['col_id'].'"';
            $m = $db->prepare($querym);
            $m->execute();
            if($m->rowCount() > 0){
                $arr[] = $item['col_id'];
            }
        }
    }
    return $arr;
}

function getPeriodosActivosMaestro($groupid, $maestroid, $db) {
    if(is_array($groupid)) {

        $periodos = $groupid;
        foreach($periodos as $item){
            $querym = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_maestroid="'.$maestroid.'" AND col_periodoid="'.$item.'"';
            $m = $db->prepare($querym);
            $m->execute();
            if($m->rowCount() > 0){
                $arr[] = $item['col_id'];
            }
        }

    }else{
        //$query = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.$groupid.'"';
        $query = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.$groupid.'"';
        $c = $db->prepare($query);
        $c->execute();
        $data = $c->fetchAll();
        foreach($data as $item){
            $querym = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_maestroid="'.$maestroid.'" AND col_periodoid="'.$item['col_id'].'"';
            $m = $db->prepare($querym);
            $m->execute();
            if($m->rowCount() > 0){
                $arr[] = $item['col_id'];
            }
        }
    }

    return $arr;
}

function getPeriodosActivos($groupid, $db) {

    //$query = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.$groupid.'"';
    $query = 'SELECT * FROM tbl_periodos WHERE col_groupid="'.$groupid.'"';
    $c = $db->prepare($query);
    $c->execute();
    $data = $c->fetchAll();
    foreach($data as $item){
        $arr[] = $item['col_id'];
    }
    return $arr;
}

function phoneFormat($number) {
	if(ctype_digit($number) && strlen($number) == 10) {
  	$number = substr($number, 0, 3) .'-'. substr($number, 3, 3) .'-'. substr($number, 6);
	} else {
		if(ctype_digit($number) && strlen($number) == 7) {
			$number = substr($number, 0, 3) .'-'. substr($number, 3, 4);
		}
	}
	return $number;
}

function inPeriodo($data, $where) {
    foreach($data as $item){
        if(in_array($item, $where)) return true;
    }
    return false;

}

function esMaestro($id, $db){
    $query = 'SELECT * FROM tbl_users WHERE col_id="'.intval($id).'"';
    $c = $db->prepare($query);
    $c->execute();
    $r = $c->fetch(PDO::FETCH_OBJ);
    if($r->col_maestro == 1) return true;

    return false;
}

function getMultaBiblioteca($fecha, $hora, $tipo, $db) {
    $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
    $c = $db->prepare($query);
    $c->execute();
    $config = $c->fetch(PDO::FETCH_OBJ);
    $multaBase = $config->col_multa_biblioteca;


    $multa = 0;
    switch($tipo) {
        case 0: //Domiciliario
        $diasPasados = countDays($fecha) + 1;
        if($diasPasados > 3){
            $multa = ($multaBase * ($diasPasados - 3));
        }
        break;

        case 1: //Clase
        // $fecha = date('Y-m-d', strtotime(date('Y-m-d', $fecha).' +1 day')).' 08:00:00';
        $fecha = date('Y-m-d', $fecha).' 08:00:00';
        $horasPasadas = countHours($fecha);

        if(strtotime('now') > strtotime($fecha)){
            $multa = (10 * $horasPasadas);
        }
        break;

        case 2: //Nocturno
        $fecha = date('Y-m-d', $fecha).' 08:00:00';
        $horasPasadas = countHours($fecha);
        if(strtotime('now') > strtotime($fecha)){
            $multa = (10 * $horasPasadas);
        }
        break;
    }

    return number_format($multa, 2);

}

function countHours($from){
    return round((strtotime('now') - strtotime($from))/(60*60));
}

function countDays($fecha){
    $date1 = date_create(date('Y-m-d', $fecha));
    $date2 = date_create(date('Y-m-d'));

    //difference between two dates
    $diff = date_diff($date1, $date2);

    //count days
    // return date('Y-m-d', strtotime($fecha));
    return $diff->format("%a");
}

/**
 * Count the number of working days between two dates.
 *
 * This function calculate the number of working days between two given dates,
 * taking account of the Public festivities, Easter and Easter Morning days,
 * the day of the Patron Saint (if any) and the working Saturday.
 *
 * @param   string  $date1    Start date ('YYYY-MM-DD' format)
 * @param   string  $date2    Ending date ('YYYY-MM-DD' format)
 * @param   boolean $workSat  TRUE if Saturday is a working day
 * @param   string  $patron   Day of the Patron Saint ('MM-DD' format)
 * @return  integer           Number of working days ('zero' on error)
 *
 * @author Massimo Simonini <massiws@gmail.com>
 */
function _countDays($date1, $date2 = 'now', $workSat = FALSE, $patron = NULL) {
    if (!defined('SATURDAY')) define('SATURDAY', 6);
    if (!defined('SUNDAY')) define('SUNDAY', 0);
    // Array of all public festivities
    // $publicHolidays = array('01-01', '01-06', '04-25', '05-01', '06-02', '08-15', '11-01', '12-08', '12-25', '12-26');
    $publicHolidays = array();
    // The Patron day (if any) is added to public festivities
    if ($patron) {
      $publicHolidays[] = $patron;
    }
    /*
     * Array of all Easter Mondays in the given interval
     */
    $yearStart = date('Y', strtotime($date1));
    $yearEnd   = date('Y', strtotime($date2));
    for ($i = $yearStart; $i <= $yearEnd; $i++) {
      $easter = date('Y-m-d', easter_date($i));
      list($y, $m, $g) = explode("-", $easter);
      $monday = mktime(0,0,0, date($m), date($g)+1, date($y));
      $easterMondays[] = $monday;
    }
    $start = strtotime($date1);
    $end   = strtotime($date2);
    $workdays = 0;
    for ($i = $start; $i <= $end; $i = strtotime("+1 day", $i)) {
      $day = date("w", $i);  // 0=sun, 1=mon, ..., 6=sat
      $mmgg = date('m-d', $i);
      if ($day != SUNDAY &&
        !in_array($mmgg, $publicHolidays) &&
        !in_array($i, $easterMondays) &&
        !($day == SATURDAY && $workSat == FALSE)) {
          $workdays++;
      }
    }
    return intval($workdays);
  }


function getAsistenciasByAlumno($alumnoid, $db, $from = '', $to = ''){
    if($from != '' && $to != '') {
        $query = 'SELECT COUNT( a.col_id ) AS total, SUM( IF( a.col_asistencia =  "A", 1, 0 ) ) AS asistio, SUM( IF( a.col_asistencia = "F", 1, 0 ) ) AS falta, SUM( IF( a.col_asistencia = "R", 1, 0 ) ) AS retardo, SUM( IF( a.col_asistencia = "P", 1, 0 ) ) AS permiso FROM tbl_asistencia s LEFT OUTER JOIN tbl_asistencia_alumnos a ON a.col_listaid=s.col_id WHERE (s.col_fecha >= "'.$from.'" AND s.col_fecha <= "'.$to.'") AND a.col_alumnoid="'.$alumnoid.'" ';
    } else {
        $query = 'SELECT COUNT( * ) AS total, SUM( IF( col_asistencia =  "A", 1, 0 ) ) AS asistio, SUM( IF( col_asistencia = "F", 1, 0 ) ) AS falta, SUM( IF( col_asistencia = "R", 1, 0 ) ) AS retardo, SUM( IF( col_asistencia = "P", 1, 0 ) ) AS permiso FROM  tbl_asistencia_alumnos WHERE col_alumnoid="'.$alumnoid.'"';
    }
    $sth = $db->prepare($query);
    $sth->execute();

    return $sth->fetch(PDO::FETCH_OBJ);
}

function getListasAsistenciasByAlumnoID($alumnoid, $db){

    $query = "SELECT * FROM tbl_asistencia_alumnos WHERE col_alumnoid='".$alumnoid."' GROUP BY col_listaid";
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetchAll();
    foreach($data as $item){
        $IDS[] = $item['col_listaid'];
    }
    return $IDS;
}

function esClaseDosHoras($materiaid, $periodoid, $dia, $db){

    $sth = $db->prepare('SELECT * FROM tbl_materias WHERE col_id = "'.$materiaid.'"');
    $sth->execute();
    $dataMateria = $sth->fetch(PDO::FETCH_OBJ);

    if(strtoupper(substr($dataMateria->col_clave, 0, 2)) == 'TL') {
        return true;
    }
    if(in_array(strtoupper(substr($dataMateria->col_clave, 0, 2)), array('AC', 'CL', 'TR'))) {
        return false;
    }


    if(in_array(strtoupper(substr($dataMateria->col_clave, 0, 2)), array('TL', 'AC'))) {
        $currentPeriodos = getCurrentPeriodos($db);
        $query ="SELECT * FROM tbl_horarios WHERE col_materiaid='".$materiaid."' AND col_periodoid IN (".implode(',', $currentPeriodos).") ";
    }else{
        $query ="SELECT * FROM tbl_horarios WHERE col_materiaid='".$materiaid."' AND col_periodoid='".$periodoid."' ";
    }
    // if ($materiaid == 1933) echo $query;exit;
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);
    switch($dia) {
        case 1: $horario = $data->col_lunes; break;
        case 2: $horario = $data->col_martes; break;
        case 3: $horario = $data->col_miercoles; break;
        case 4: $horario = $data->col_jueves; break;
        case 5: $horario = $data->col_viernes; break;
        case 6: $horario = $data->col_sabado; break;
        case 7: $horario = $data->col_domingo; break;
    }

    list($inicio, $fin) = explode('-', $horario);
    $inicio = intval(substr($inicio, 0, 2));
    $fin = intval(substr($fin, 0, 2));

    if(($fin - $inicio) == 2) return true;
    return false;

}

function diferenciaHoras($horario){
    list($inicio, $fin) = explode('-', $horario);
    $inicio = intval(substr($inicio, 0, 2));
    $fin = intval(substr($fin, 0, 2));

    if(($fin - $inicio) == 2) return 2;
    return 1;
}

function get_AsistenciasByAlumnoAndMateria($alumnoid, $db, $_fechaLimite = '', $_materiaID = ''){


    $queryAlumno = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $sth = $db->prepare($queryAlumno);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
    $periodoDataAlumno = getPeriodo($alumnoData->col_periodoid, $db, false);

    $currentPeriodos = getCurrentPeriodos($db);
    if($_materiaID == '') {
        $subquery = 'SELECT col_listaid FROM tbl_asistencia_alumnos WHERE col_alumnoid="'.$alumnoid.'"';
        $masterQuery = 'SELECT * FROM tbl_asistencia WHERE col_id IN ('.$subquery.') GROUP BY col_materiaid';
        $sth = $db->prepare($masterQuery);
        $sth->execute();
        $mats = $sth->fetchAll();
    }else{
        $mats[]['col_materiaid'] = $_materiaID;
    }

    foreach($mats as $item) {
        $taxos[] = $item['col_materiaid'];

        $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_id="'.$item['col_materiaid'].'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $taxData = $sth->fetch(PDO::FETCH_OBJ);
        //echo substr($taxData->col_materia_clave, 0, 2).'==';
        if(substr($taxData->col_materia_clave, 0, 2) !== 'AC' && substr($taxData->col_materia_clave, 0, 2) !== 'TL'){
            if($taxData->col_periodoid != $alumnoData->col_periodoid) continue;
        }

        // if(substr($taxData->col_materia_clave, 0, 2) == 'AC'){
        //     print_r($taxData->col_materia_clave);exit;
        // }

        if(!in_array($taxData->col_periodoid, $currentPeriodos)) continue;
        $periodoData = getPeriodo($taxData->col_periodoid, $db, false);
        $from = $periodoData->col_fecha_inicio;
        $today = $to = date('Y-m-d');

        if(substr($taxData->col_materia_clave, 0, 2) == 'AC' || substr($taxData->col_materia_clave, 0, 2) == 'TL'){


            $queryAlt = 'SELECT col_id FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($taxData->col_materia_clave).'%" AND col_semestre="'.$periodoDataAlumno->col_grado.'" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';

            $query = 'SELECT * FROM tbl_materias WHERE LENGTH(col_clave)>'.strlen(claveMateria($taxData->col_materia_clave)).' AND col_clave LIKE "'.claveMateria($taxData->col_materia_clave).'%" AND col_semestre="'.$periodoDataAlumno->col_grado.'" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';

            $sth = $db->prepare($query);
            $sth->execute();
            $dataMateria = $sth->fetch(PDO::FETCH_OBJ);

        }else{

            $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.trim($taxData->col_materia_clave).'" AND col_semestre="'.$periodoDataAlumno->col_grado.'" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
            $sth = $db->prepare($query);
            $sth->execute();
            $dataMateria = $sth->fetch(PDO::FETCH_OBJ);
        }



        // if(substr($taxData->col_materia_clave, 0, 2) == 'AC' OR substr($taxData->col_materia_clave, 0, 2) == 'TL'){
        if(substr($taxData->col_materia_clave, 0, 2) == 'AC'){

            $queryAcademia = 'SELECT * FROM tbl_academias WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$alumnoData->col_periodoid.'" AND col_materiaid IN ('.$queryAlt.')';
            $sth = $db->prepare($queryAcademia);
            $sth->execute();
            if($sth->rowCount() == 0) continue;
        }

        if(substr($taxData->col_materia_clave, 0, 2) == 'TL'){

            $queryTaller = 'SELECT * FROM tbl_talleres WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$alumnoData->col_periodoid.'" AND col_materiaid IN ('.$queryAlt.')';
            $sth = $db->prepare($queryTaller);
            $sth->execute();
            if($sth->rowCount() == 0) continue;
        }


        $materias[$item['col_materiaid']]['id'] = $dataMateria->col_id;
        $materias[$item['col_materiaid']]['periodoid'] = $taxData->col_periodoid;
        $materias[$item['col_materiaid']]['taxid'] = $item['col_materiaid'];
        $materias[$item['col_materiaid']]['clave'] = $dataMateria->col_clave;
        $materias[$item['col_materiaid']]['grado'] = $dataMateria->col_semestre;
        $materias[$item['col_materiaid']]['nombre'] = fixEncode($dataMateria->col_nombre);

        if(in_array(strtoupper(substr($taxData->col_materia_clave, 0, 2)), array('AC', 'TL'))) {
            $queryP1 = 'SELECT * FROM tbl_actividades WHERE (col_visible_excepto LIKE "%'.strtoupper($taxData->col_materia_clave).'%" AND col_visible_excepto LIKE "%'.$taxData->col_periodoid.'%") AND col_tipo="5"';
            $queryP2 = 'SELECT * FROM tbl_actividades WHERE (col_visible_excepto LIKE "%'.strtoupper($taxData->col_materia_clave).'%" AND col_visible_excepto LIKE "%'.$taxData->col_periodoid.'%") AND col_tipo="6"';
            $queryEF = 'SELECT * FROM tbl_actividades WHERE (col_visible_excepto LIKE "%'.strtoupper($taxData->col_materia_clave).'%" AND col_visible_excepto LIKE "%'.$taxData->col_periodoid.'%") AND col_tipo="7"';
        }else{
            $queryP1 = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.strtoupper($taxData->col_periodoid).'%" AND col_materiaid="'.$dataMateria->col_id.'" AND col_tipo="5"';
            $queryP2 = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.strtoupper($taxData->col_periodoid).'%" AND col_materiaid="'.$dataMateria->col_id.'" AND col_tipo="6"';
            $queryEF = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.strtoupper($taxData->col_periodoid).'%" AND col_materiaid="'.$dataMateria->col_id.'" AND col_tipo="7"';
            // $queryP1 = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.strtoupper($taxData->col_periodoid).'%" AND col_materiaid="'.$dataMateria->col_id.'" AND col_tipo="5" AND col_created_by="'.$taxData->col_maestroid.'"';
            // $queryP2 = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.strtoupper($taxData->col_periodoid).'%" AND col_materiaid="'.$dataMateria->col_id.'" AND col_tipo="6" AND col_created_by="'.$taxData->col_maestroid.'"';
            // $queryEF = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.strtoupper($taxData->col_periodoid).'%" AND col_materiaid="'.$dataMateria->col_id.'" AND col_tipo="7" AND col_created_by="'.$taxData->col_maestroid.'"';
        }

        $queryCalificaciones = 'SELECT * FROM tbl_calificaciones WHERE col_alumnoid="'.$alumnoid.'" AND col_groupid="'.$periodoData->col_groupid.'" AND col_materia_clave LIKE "'.strtoupper(claveMateria($dataMateria->col_clave)).'%"';
        // $materias[$item['col_materiaid']]['queryCalificaciones'] = $queryCalificaciones;
        $sth = $db->prepare($queryCalificaciones);
        $sth->execute();
        $calificaciones = $sth->fetch(PDO::FETCH_OBJ);
        switch(strtoupper(substr($dataMateria->col_clave, 0, 2))){
            case 'AC':
            if($calificaciones->col_p1 == 1 || $calificaciones->col_p1 >= 7) { $calificaciones->col_p1 = 'A'; }else if($calificaciones->col_p1 == '' OR intval($calificaciones->col_p1) < 1){ $calificaciones->col_p1 = 'NA'; }
            if($calificaciones->col_p2 == 1 || $calificaciones->col_p2 >= 7) { $calificaciones->col_p2 = 'A'; }else if($calificaciones->col_p2 == '' OR intval($calificaciones->col_p2) < 1){ $calificaciones->col_p2 = 'NA'; }
            if($calificaciones->col_ef == 1 || $calificaciones->col_ef >= 7) { $calificaciones->col_ef = 'A'; }else if($calificaciones->col_ef == '' OR intval($calificaciones->col_ef) < 1){ $calificaciones->col_ef = 'NA'; }
            break;

            case 'TR':
            case 'TL':
            case 'CL':
            if(intval($calificaciones->col_p1) > 7) { $calificaciones->col_p1 = 'A'; }else if(intval($calificaciones->col_p1) < 7){ $calificaciones->col_p1 = 'NA'; }
            if(intval($calificaciones->col_p2) > 7) { $calificaciones->col_p2 = 'A'; }else if(intval($calificaciones->col_p2) < 7){ $calificaciones->col_p2 = 'NA'; }
            if(intval($calificaciones->col_ef) > 7) { $calificaciones->col_ef = 'A'; }else if(intval($calificaciones->col_ef) < 7){ $calificaciones->col_ef = 'NA'; }
            break;
        }

        $sth = $db->prepare($queryP1);
        $sth->execute();
        $parcialP1 = $sth->fetch(PDO::FETCH_OBJ);

        $materias[$item['col_materiaid']]['parcial1ID'] = $parcialP1->col_id;
        $materias[$item['col_materiaid']]['parcial1'] = $parcialP1->col_fecha_inicio;
        $materias[$item['col_materiaid']]['parcial1_calificacion'] = $calificaciones->col_p1;
        $materias[$item['col_materiaid']]['calificacion_observaciones'] = $calificaciones->col_observaciones;
        $materias[$item['col_materiaid']]['parcial1_orden'] = getExamenOrden($parcialP1->col_id, $db);
        // $materias[$item['col_materiaid']]['debug_parcial1'] = $queryP1;

        $sth = $db->prepare($queryP2);
        $sth->execute();
        $parcialP2 = $sth->fetch(PDO::FETCH_OBJ);
        $materias[$item['col_materiaid']]['parcial2ID'] = $parcialP2->col_id;
        $materias[$item['col_materiaid']]['parcial2'] = $parcialP2->col_fecha_inicio;
        $materias[$item['col_materiaid']]['parcial2_calificacion'] = $calificaciones->col_p2;
        $materias[$item['col_materiaid']]['parcial2_orden'] = getExamenOrden($parcialP2->col_id, $db);


        $sth = $db->prepare($queryEF);
        $sth->execute();
        $examenFinal = $sth->fetch(PDO::FETCH_OBJ);
        $materias[$item['col_materiaid']]['examenFinalID'] = $examenFinal->col_id;
        $materias[$item['col_materiaid']]['examenFinal'] = $examenFinal->col_fecha_inicio;
        $materias[$item['col_materiaid']]['examenFinal_calificacion'] = $calificaciones->col_ef;
        $materias[$item['col_materiaid']]['examenFinal_orden'] = getExamenOrden($examenFinal->col_id, $db);
        //$materias[$item['col_materiaid']]['debug_examenFinal'] = $queryEF;


        if($_fechaLimite == '') {
            // Si hay fecha de 1 parcial y la fecha de hoy es mayor que la del 1 parcial (el parcial ya paso)
            if($parcialP1->col_fecha_inicio && strtotime('now') > strtotime($parcialP1->col_fecha_inicio)) {
                $fromP1 = $from = date('Y-m-d', strtotime(substr($parcialP1->col_fecha_inicio, 0, 10) .' +1 day'));
                // $from = $parcialP1->col_fecha_inicio;
            } else if($parcialP1->col_fecha_inicio && strtotime('now') < strtotime($parcialP1->col_fecha_inicio)) {
                $to = $parcialP1->col_fecha_inicio;
            }

            if($parcialP1->col_fecha_inicio && $parcialP2->col_fecha_inicio) {
                if(strtotime('now') > strtotime($parcialP1->col_fecha_inicio)) {
                    $from = date('Y-m-d', strtotime(substr($parcialP1->col_fecha_inicio, 0, 10) .' 00:00:00 +1 day'));
                    // $from = $parcialP1->col_fecha_inicio;
                    $to = $parcialP2->col_fecha_inicio;
                }else{
                    $to = $parcialP1->col_fecha_inicio;
                }
            }

            if(strtotime('now') > strtotime($parcialP2->col_fecha_inicio.' +1 day')) {
                $from = date('Y-m-d', strtotime(substr($parcialP2->col_fecha_inicio, 0, 10) .' 00:00:00 +1 day'));
                if($examenFinal->col_fecha_inicio) {
                    $to = $examenFinal->col_fecha_inicio;
                }else{
                    $to = date('Y-m-d', strtotime('now'));
                }
            }

        }else{
            if($parcialP1->col_fecha_inicio && strtotime($_fechaLimite) > strtotime($parcialP1->col_fecha_inicio)) {
                $from = date('Y-m-d', strtotime(substr($parcialP1->col_fecha_inicio, 0, 10) .' +1 day'));
                // $from = $parcialP1->col_fecha_inicio;
                $to = $_fechaLimite;
            } else if($parcialP1->col_fecha_inicio && strtotime($_fechaLimite) < strtotime($parcialP1->col_fecha_inicio)) {
                $to = $parcialP1->col_fecha_inicio;
            }

            if($parcialP2->col_fecha_inicio && strtotime($_fechaLimite) > strtotime($parcialP2->col_fecha_inicio)) {
                $from = date('Y-m-d', strtotime(substr($parcialP2->col_fecha_inicio, 0, 10) .' +1 day'));
                // $from = $parcialP1->col_fecha_inicio;
                $to = $_fechaLimite;
            } else if($parcialP2->col_fecha_inicio && strtotime($_fechaLimite) < strtotime($parcialP2->col_fecha_inicio)) {
                $to = $parcialP2->col_fecha_inicio;
            }
        }


        $materias[$item['col_materiaid']]['from'] = $from;
        $materias[$item['col_materiaid']]['to'] = $to;

    }
    // echo '<pre>';
    // print_r($materias);
    // echo '</pre>';
    // exit;

    foreach($materias as $k => $materia) {
        unset($asistio);
        unset($falto);
        unset($retardo);
        unset($permiso);
        unset($retardoCount);
        unset($totalHoras);
        unset($clasesFechas);
        unset($clasesInfo);
        unset($clasesFechas);
        unset($participacionesCount);
        //print_r($materia);exit;
        $materiaid = $materia['id'];
        $queryMateriaAsistencias = 'SELECT col_id, col_materiaid, col_fecha FROM tbl_asistencia WHERE col_fecha >= "'.$materia['from'].'" AND col_fecha <= "'.$materia['to'].'" AND col_materiaid="'.$materia['taxid'].'"';
        // if($dataMateria->col_clave == 'CL07') {
        //     echo $queryMateriaAsistencias;exit;
        // }
        // $materias[$materia['taxid']]['query'] = $queryMateriaAsistencias;
        // if($materia['taxid'] == 383) {
        //     print_r($queryMateriaAsistencias);
        //     exit;
        // }
        $sth = $db->prepare($queryMateriaAsistencias);
        $sth->execute();
        if($sth->rowCount()) {
            $queryMateriaAsistenciasTotal = $sth->rowCount();
            $data = $sth->fetchAll();

            foreach($data as $item) {

                $query = 'SELECT * FROM tbl_asistencia_alumnos WHERE col_listaid="'.$item['col_id'].'" AND col_alumnoid="'.$alumnoid.'"';
                $sth = $db->prepare($query);
                $sth->execute();
                $itemAsistencia = $sth->fetch(PDO::FETCH_OBJ);
                if($sth->rowCount() == 0){
                    $itemAsistencia->col_asistencia = 'A';
                    $itemAsistencia->col_segunda = '1';
                }


                $horas = 1;


                if(esClaseDosHoras($materiaid, $materia['periodoid'], date('N', strtotime($item['col_fecha'])), $db)){
                    $horas = 2;

                    if($itemAsistencia->col_segunda == 0 && $itemAsistencia->col_asistencia == 'A') { // 1 asistencia + 1 Falta

                        $asistio[$materiaid] = intval($asistio[$materiaid]) + 1;
                        $falto[$materiaid] = intval($falto[$materiaid]) + 1;

                    } else if($itemAsistencia->col_segunda == 1 && $itemAsistencia->col_asistencia == 'A') { // 2 Asistencias

                        $asistio[$materiaid] = intval($asistio[$materiaid]) + 2;

                    } else if($itemAsistencia->col_segunda  == 0 && $itemAsistencia->col_asistencia == 'R') { // 1 Retardo + 1 Falta

                        $retardo[$materiaid] = floatval($retardo[$materiaid]) + 0.3;
                        $retardoCount[$materiaid] = intval($retardoCount[$materiaid]) + 1;
                        $falto[$materiaid] = intval($falto[$materiaid]) + 1;

                    } else if($itemAsistencia->col_segunda  == 1 && $itemAsistencia->col_asistencia == 'R') { // 1 Retardo + 1 Asistencia

                        $asistio[$materiaid] = intval($asistio[$materiaid]) + 1;
                        $retardoCount[$materiaid] = intval($retardoCount[$materiaid]) + 1;
                        $retardo[$materiaid] = floatval($retardo[$materiaid]) + 0.3;

                    } else if($itemAsistencia->col_segunda  == 0 && $itemAsistencia->col_asistencia == 'F') { // 2 Faltas

                        $falto[$materiaid] = intval($falto[$materiaid]) + 2;

                    } else if($itemAsistencia->col_segunda  == 1 && $itemAsistencia->col_asistencia == 'F') { // 1 asistencia + 1 Falta

                        $asistio[$materiaid] = intval($asistio[$materiaid]) + 1;
                        $falto[$materiaid] = intval($falto[$materiaid]) + 1;

                    }


                } else {
                    $asistio[$materiaid] = ($itemAsistencia->col_asistencia == 'A'?intval($asistio[$materiaid]) + 1:intval($asistio[$materiaid]));
                    $falto[$materiaid] = ($itemAsistencia->col_asistencia == 'F'?intval($falto[$materiaid]) + 1:intval($falto[$materiaid]));
                    $retardo[$materiaid] = ($itemAsistencia->col_asistencia == 'R'?floatval($retardo[$materiaid]) + 0.3:floatval($retardo[$materiaid]));
                    $retardoCount[$materiaid] = ($itemAsistencia->col_asistencia == 'R'?intval($retardoCount[$materiaid]) + 1:intval($retardoCount[$materiaid]));
                    $permiso[$materiaid] = ($itemAsistencia->col_asistencia == 'P'?intval($permiso[$materiaid]) + 1:intval($permiso[$materiaid]));

                }
                $totalHoras[$materiaid] = intval($totalHoras[$materiaid]) + $horas;
                $clasesFechas[$materiaid][] = $item['col_fecha'].'('.$horas.')';
                $clasesInfo[$materiaid][] = $materiaid.' - '.$materia['periodoid'].' - '.$materia['taxid'];
                $participacionesCount[$materiaid] = intval($participacionesCount[$materiaid]) + $itemAsistencia->col_participacion;

            }
        }


        $asistencias[$materiaid] = array(
            'taxos' => $taxos,
            'alumnoid' => $alumnoid,
            'materiaid' => $materiaid,
            'materiaGrado' => $materia['grado'],
            'periodoID' => $materia['periodoid'],
            // 'materiaDebugQueries' => $losQueries,
            // 'materiaDebugTotal' => $queryP,
            // 'materiaDebug' => $queryMateriaAsistencias,
            'materiaData' => $materia,
            'materiaTax' => $materia['taxid'],
            'materia' => fixEncode($materia['nombre']),
            'clave' => fixEncode($materia['clave']),
            'asistencias' => $asistio[$materiaid],
            'faltas' => $falto[$materiaid],
            'retardos' => $retardo[$materiaid],
            'permisos' => $permiso[$materiaid],
            'retardosCount' => $retardoCount[$materiaid],
            'totalHoras' => $totalHoras[$materiaid],
            'clasesFechas' => $clasesFechas[$materiaid],
            'clasesInfo' => $clasesInfo[$materiaid],
            'clasesTotal' => count($clasesFechas[$materiaid]),
            'participaciones' => $participacionesCount[$materiaid]
        );
        unset($materiaid);
    }



    foreach($asistencias as $k => $v){
        if(floor(($v['retardosCount'] / 3)) > 0) {
            $asistencias[$k]['faltas'] = $asistencias[$k]['faltas'] + floor(($v['retardosCount'] / 3));
        }
    }

    foreach($asistencias as $k => $v){
        $asistencias[$k]['total'] = ($asistencias[$k]['asistencias'] + $asistencias[$k]['faltas'] + $asistencias[$k]['retardos'] + $asistencias[$k]['permisos']);
        $asistencias[$k]['porcentaje'] = 0;
	    if(intval($asistencias[$k]['totalHoras']) > 0 && intval($asistencias[$k]['faltas']) > 0) {
	        $asistencias[$k]['porcentaje'] = ($asistencias[$k]['faltas']/$asistencias[$k]['totalHoras']) * 100;
	    }
        $asistencias[$k]['porcentaje_asistencias'] = 100 - $asistencias[$k]['porcentaje'];

        //$porcentaje = number_format((100 - (($dataTotalesAlumnos[$alumno['col_id']]['faltas'] / $dataTotalesAlumnos[$alumno['col_id']]['horas']) * 100)), 2);
    }
    // Test: 101
    // return $materias;

    return $asistencias;

}

function getAsistenciasByAlumnoAndMateria($alumnoid, $db, $from = '', $to = '', $materiaid = 0, $ignorarMaestro = false){

    $maestroid = 0;
    if($ignorarMaestro === false) {
        if(getBearerToken()) {
            if(getCurrentUserType() == 'maestro') $maestroid = getCurrentUserID();
        }
    }

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
    $carreraData = getCarrera($alumnoData->col_carrera, $db);
    $periodosActivosModalidad = getCurrentPeriodos($db, $carreraData['modalidad_periodo']);

    if($materiaid > 0) {
        $_TaxsMateria = array();
        $query = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaid.'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $_materiaData = $sth->fetch(PDO::FETCH_OBJ);
        $laClave = substr(strtoupper($_materiaData->col_clave), 0, 2);
        if($laClave == 'AC' || $laClave == 'TL'){
            //$queryTaxs = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_periodoid IN ('.implode(',', $periodosActivosModalidad).') AND (col_materia_clave="'.$_materiaData->col_clave.'" OR col_materia_clave LIKE "'.claveMateria($_materiaData->col_clave).'%") GROUP BY col_materia_clave';
            $queryTaxs = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_periodoid IN ('.implode(',', $periodosActivosModalidad).') AND col_materia_clave="'.$_materiaData->col_clave.'" GROUP BY col_materia_clave';
        }else{
            //$queryTaxs = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_periodoid="'.$alumnoData->col_periodoid.'" AND (col_materia_clave="'.$_materiaData->col_clave.'" OR col_materia_clave="'.claveMateria($_materiaData->col_clave).'") GROUP BY col_materia_clave';
            $queryTaxs = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_periodoid="'.$alumnoData->col_periodoid.'" AND col_materia_clave="'.$_materiaData->col_clave.'" GROUP BY col_materia_clave';
        }
        $sth = $db->prepare($queryTaxs);
        $sth->execute();
        $lasTaxMateria = $sth->fetchAll();
        foreach($lasTaxMateria as $elm) {
            $_TaxsMateria[] = $elm['col_id'];
        }

    }else{
        $queryTaxs = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_periodoid="'.$alumnoData->col_periodoid.'" GROUP BY col_materia_clave';
        $sth = $db->prepare($queryTaxs);
        $sth->execute();
        $lasTaxMateria = $sth->fetchAll();
        foreach($lasTaxMateria as $elm) {
            $_TaxsMateria[] = $elm['col_id'];
        }
    }




    if($maestroid > 0) {
        $queryAsistencias2 = 'SELECT s.*, a.col_materiaid AS taxID, a.col_fecha FROM tbl_asistencia_alumnos s '.
        'LEFT OUTER JOIN tbl_asistencia a ON a.col_id=s.col_listaid '.
        'WHERE s.col_alumnoid="'.$alumnoid.'" AND s.col_listaid IN (SELECT col_id FROM tbl_asistencia WHERE col_maestroid="'.$maestroid.'" AND col_fecha >= "'.$from.'" AND col_fecha <= "'.$to.'") GROUP BY taxID';
    }else{
        $queryAsistencias2 = 'SELECT s.*, a.col_materiaid AS taxID, a.col_fecha FROM tbl_asistencia_alumnos s '.
        'LEFT OUTER JOIN tbl_asistencia a ON a.col_id=s.col_listaid '.
        'WHERE s.col_alumnoid="'.$alumnoid.'" AND s.col_listaid IN (SELECT col_id FROM tbl_asistencia WHERE col_fecha >= "'.$from.'" AND col_fecha <= "'.$to.'") GROUP BY taxID';
    }
    //if($alumnoid == 228){
    //echo $query;exit;
    //}
    $sth = $db->prepare($queryAsistencias2);
    $sth->execute();
    $TodoMateriasTax = $sth->fetchAll();

    foreach($TodoMateriasTax as $itemTax) {

        //if($materiaid > 0 && count($_TaxsMateria) > 0) {
        //    if(!in_array($itemTax['taxID'], $_TaxsMateria)) continue;
        //}
        if(!in_array($itemTax['taxID'], $_TaxsMateria)) continue;

        $query = 'SELECT col_id, col_materiaid, col_fecha FROM tbl_asistencia WHERE col_fecha >= "'.$from.'" AND col_fecha <= "'.$to.'" AND col_materiaid="'.$itemTax['taxID'].'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $data = $sth->fetchAll();

            foreach($data as $item) {

                $query = 'SELECT * FROM tbl_asistencia_alumnos WHERE col_listaid="'.$item['col_id'].'" AND col_alumnoid="'.$alumnoid.'"';
                $sth = $db->prepare($query);
                $sth->execute();
                $itemAsistencia = $sth->fetch(PDO::FETCH_OBJ);
                if($sth->rowCount() == 0){
                    $itemAsistencia->col_asistencia = 'A';
                    $itemAsistencia->col_segunda = '1';
                }

                $materiaData = getMateriaIDbyTAX($itemTax['taxID'], $db, true);
                $periodoData = getPeriodo($alumnoData->col_periodoid, $db, false);
                if(in_array(strtoupper(substr($materiaData->col_clave, 0, 2)), array('TL', 'AC'))) {
                    //$queryGetMateriaLink = "SELECT col_id FROM tbl_materias WHERE LENGTH(col_clave)>".strlen(claveMateria($materiaData->col_clave))." AND col_carrera='".$materiaData->col_carrera."' AND col_clave LIKE '".claveMateria($materiaData->col_clave)."%' AND col_plan_estudios='".$periodoData->col_plan_estudios."'";
                    $queryGetMateriaLink = "SELECT col_id FROM tbl_materias WHERE col_carrera='".$materiaData->col_carrera."' AND col_clave='".$materiaData->col_clave."' AND col_plan_estudios='".$periodoData->col_plan_estudios."'";
                    $queryGetMateriaHorarios = "SELECT * FROM tbl_horarios WHERE col_materiaid IN (".$queryGetMateriaLink.") AND col_periodoid IN (".implode(',', $periodosActivosModalidad).") LIMIT 1";
                    $sth = $db->prepare($queryGetMateriaHorarios);
                    $sth->execute();
                    $materiaDataHorarios = $sth->fetch(PDO::FETCH_OBJ);

                    $query = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaDataHorarios->col_materiaid.'"';
                    $sth = $db->prepare($query);
                    $sth->execute();
                    $materiaData = $sth->fetch(PDO::FETCH_OBJ);

                }else{
                    //$queryGetMateria = "SELECT * FROM tbl_materias WHERE col_carrera='".$materiaData->col_carrera."' AND col_clave LIKE '".claveMateria($materiaData->col_clave)."%' AND col_semestre='".$periodoData->col_grado."' AND col_plan_estudios='".$periodoData->col_plan_estudios."'";
                    $queryGetMateria = "SELECT * FROM tbl_materias WHERE col_carrera='".$materiaData->col_carrera."' AND col_clave='".$materiaData->col_clave."' AND col_semestre='".$periodoData->col_grado."' AND col_plan_estudios='".$periodoData->col_plan_estudios."'";
                    $sth = $db->prepare($queryGetMateria);
                    $sth->execute();
                    $materiaData = $sth->fetch(PDO::FETCH_OBJ);
                }


                if(!in_array(strtoupper(substr($materiaData->col_clave, 0, 2)), array('TL', 'AC'))) {
                    if(intval($materiaid) > 0) $materiaData->col_id = $materiaid;
                }
                $set[] = $materiaData->col_id;
                $horas = 1;

                if(esClaseDosHoras($materiaData->col_id, $alumnoData->col_periodoid, date('N', strtotime($item['col_fecha'])), $db)){
                    $horas = 2;

                    if($itemAsistencia->col_segunda == 0 && $itemAsistencia->col_asistencia == 'A') { // 1 asistencia + 1 Falta

                        $asistio[$materiaData->col_id] = intval($asistio[$materiaData->col_id]) + 1;
                        $falto[$materiaData->col_id] = intval($falto[$materiaData->col_id]) + 1;

                    } else if($itemAsistencia->col_segunda == 1 && $itemAsistencia->col_asistencia == 'A') { // 2 Asistencias

                        $asistio[$materiaData->col_id] = intval($asistio[$materiaData->col_id]) + 2;

                    } else if($itemAsistencia->col_segunda  == 0 && $itemAsistencia->col_asistencia == 'R') { // 1 Retardo + 1 Falta

                        $retardo[$materiaData->col_id] = floatval($retardo[$materiaData->col_id]) + 0.3;
                        $retardoCount[$materiaData->col_id] = intval($retardoCount[$materiaData->col_id]) + 1;
                        $falto[$materiaData->col_id] = intval($falto[$materiaData->col_id]) + 1;

                    } else if($itemAsistencia->col_segunda  == 1 && $itemAsistencia->col_asistencia == 'R') { // 1 Retardo + 1 Asistencia

                        $asistio[$materiaData->col_id] = intval($asistio[$materiaData->col_id]) + 1;
                        $retardoCount[$materiaData->col_id] = intval($retardoCount[$materiaData->col_id]) + 1;
                        $retardo[$materiaData->col_id] = floatval($retardo[$materiaData->col_id]) + 0.3;

                    } else if($itemAsistencia->col_segunda  == 0 && $itemAsistencia->col_asistencia == 'F') { // 2 Faltas

                        $falto[$materiaData->col_id] = intval($falto[$materiaData->col_id]) + 2;

                    } else if($itemAsistencia->col_segunda  == 1 && $itemAsistencia->col_asistencia == 'F') { // 1 asistencia + 1 Falta

                        $asistio[$materiaData->col_id] = intval($asistio[$materiaData->col_id]) + 1;
                        $falto[$materiaData->col_id] = intval($falto[$materiaData->col_id]) + 1;

                    }


                } else {
                    $asistio[$materiaData->col_id] = ($itemAsistencia->col_asistencia == 'A'?intval($asistio[$materiaData->col_id]) + 1:intval($asistio[$materiaData->col_id]));
                    $falto[$materiaData->col_id] = ($itemAsistencia->col_asistencia == 'F'?intval($falto[$materiaData->col_id]) + 1:intval($falto[$materiaData->col_id]));
                    $retardo[$materiaData->col_id] = ($itemAsistencia->col_asistencia == 'R'?floatval($retardo[$materiaData->col_id]) + 0.3:floatval($retardo[$materiaData->col_id]));
                    $retardoCount[$materiaData->col_id] = ($itemAsistencia->col_asistencia == 'R'?intval($retardoCount[$materiaData->col_id]) + 1:intval($retardoCount[$materiaData->col_id]));
                    $permiso[$materiaData->col_id] = ($itemAsistencia->col_asistencia == 'P'?intval($permiso[$materiaData->col_id]) + 1:intval($permiso[$materiaData->col_id]));

                }
                $totalHoras[$materiaData->col_id] = intval($totalHoras[$materiaData->col_id]) + $horas;
                $clasesFechas[$materiaData->col_id][] = $item['col_fecha'].'('.$horas.') ('.$itemAsistencia->col_asistencia.')';
                $clasesInfo[$materiaData->col_id][] = $materiaData->col_id.' - '.$alumnoData->col_periodoid.' - '.$itemTax['taxID'];
                $participacionesCount[$materiaData->col_id] = intval($participacionesCount[$materiaData->col_id]) + $itemAsistencia->col_participacion;

            }

            $asistencias[$materiaData->col_id] = array(
            'materia' => fixEncode($materiaData->col_nombre),
            'clave' => fixEncode($materiaData->col_clave),
            'asistencias' => $asistio[$materiaData->col_id],
            'faltas' => $falto[$materiaData->col_id],
            'retardos' => $retardo[$materiaData->col_id],
            'permisos' => $permiso[$materiaData->col_id],
            'retardosCount' => $retardoCount[$materiaData->col_id],
            'totalHoras' => $totalHoras[$materiaData->col_id],
            'clasesFechas' => $clasesFechas[$materiaData->col_id],
            'clasesInfo' => $clasesInfo[$materiaData->col_id],
            'clasesTotal' => count($clasesFechas[$materiaData->col_id]),
            'participaciones' => $participacionesCount[$materiaData->col_id]
         );

        }
        //1211
        foreach($asistencias as $k => $v){
            if(floor(($v['retardosCount'] / 3)) > 0) {
                $asistencias[$k]['faltas'] = $asistencias[$k]['faltas'] + floor(($v['retardosCount'] / 3));
            }
        }

        foreach($asistencias as $k => $v){
            $asistencias[$k]['total'] = ($asistio[$k] + $falto[$k] + $retardo[$k] + $permiso[$k]);
            $asistencias[$k]['porcentaje'] = ($falto[$k]/$asistencias[$k]['totalHoras']) * 100;
            $asistencias[$k]['porcentaje_asistencias'] = 100 - $asistencias[$k]['porcentaje'];
            // $asistencias[$k]['porcentaje_asistencias_materiaid'] = $materiaData->col_id;
        }


        if(count($asistencias) == 0) {
            return 'sin-listas';
        }

    return $asistencias;
}

function getMiMateriaPorMaestro($maestroid, $db, $actividadid = 0) {

    if(intval($actividadid) > 0) {
        $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.intval($actividadid).'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $act = $sth->fetch(PDO::FETCH_OBJ);

        if(intval($act->col_materiaid) > 0) {
            $query = 'SELECT * FROM tbl_materias WHERE col_id="'.intval($act->col_materiaid).'"';
            $sth = $db->prepare($query);
            $sth->execute();
            $mat = $sth->fetch(PDO::FETCH_OBJ);
            if($mat->col_nombre != '') return fixEncode($mat->col_nombre);
        }else{
            $periodoID = 0;
            $carreraID = 0;
            if(getCurrentUserType() == 'alumno'){
                $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.intval(getCurrentUserID()).'"';
                $sth = $db->prepare($query);
                $sth->execute();
                $alum = $sth->fetch(PDO::FETCH_OBJ);
                $periodoID = $alum->col_periodoid;
                $periodoData = getPeriodo($alum->col_periodoid, $db, false);
                $carreraID = $alum->col_carrera;
                $grado = $periodoData->col_grado;
            }

            $extract = explode('|', unserialize($act->col_visible_excepto));
            $_clave = strtoupper($extract[1]);

            if($periodoID > 0 && $carreraID > 0) {
                $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.$_clave.'" AND col_carrera="'.$carreraID.'" AND col_semestre>0';
            }else{
                $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.$_clave.'"';
            }
            // echo $query.'<br/>';
            $sth = $db->prepare($query);
            $sth->execute();
            $mat = $sth->fetch(PDO::FETCH_OBJ);
            if($mat->col_nombre != '') return fixEncode($mat->col_nombre);
        }

    }

    $maestros = getTodosMisMaestros($db, 0, true);
    foreach($maestros as $mmItem){
        list($mid, $materiaid) = explode('|', $mmItem);
        if($maestroid == $mid) {
            $_materiaid = $materiaid;
            break;
        }
    }
    $query ="SELECT * FROM tbl_materias WHERE col_id='".$_materiaid."'";
    $sth = $db->prepare($query);
    $sth->execute();
    $materia = $sth->fetch(PDO::FETCH_OBJ);
    return fixEncode($materia->col_nombre);
}

function getMateriasByAlumno($alumnoid, $db){

    $query = 'SELECT p.col_carreraid AS carrera, p.col_plan_estudios AS planEstudios, p.col_id AS periodo, p.col_grado AS semestre FROM tbl_alumnos a LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid WHERE a.col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumno = $sth->fetch(PDO::FETCH_OBJ);

    $query ="SELECT * FROM tbl_academias WHERE col_alumnoid='".$alumnoid."' AND col_periodoid='".$alumno->periodo."'";
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount()){
        $alumnoAcademia = $sth->fetch(PDO::FETCH_OBJ);
        $query ="SELECT * FROM tbl_materias WHERE col_id='".$alumnoAcademia->col_materiaid."'";
        $sth = $db->prepare($query);
        $sth->execute();
        $materiaAcademia = $sth->fetch(PDO::FETCH_OBJ);
    }

    $query ="SELECT * FROM tbl_talleres WHERE col_alumnoid='".$alumnoid."' AND col_periodoid='".$alumno->periodo."'";
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount()){
        $alumnoTaller = $sth->fetch(PDO::FETCH_OBJ);
        $query ="SELECT * FROM tbl_materias WHERE col_id='".$alumnoTaller->col_materiaid."'";
        $sth = $db->prepare($query);
        $sth->execute();
        $materiaTaller = $sth->fetch(PDO::FETCH_OBJ);
    }


    $query = "SELECT * FROM tbl_materias WHERE (col_clave NOT LIKE 'AC%' AND col_clave NOT LIKE 'TL%') AND col_plan_estudios='".intval($alumno->planEstudios)."' AND col_carrera='".$alumno->carrera."' AND col_semestre='".$alumno->semestre."' GROUP BY col_clave ORDER BY col_id ASC";
    $sth_materias = $db->prepare($query);
    $sth_materias->execute();
    $materias = $sth_materias->fetchAll();
    foreach($materias as $itemMateria) {
        $result[] = $itemMateria['col_id'];
    }

    $query = "SELECT * FROM tbl_materias WHERE (col_clave LIKE '".claveMateria($materiaAcademia->col_clave)."%' OR col_clave LIKE '".claveMateria($materiaTaller->col_clave)."%') AND col_plan_estudios='".intval($alumno->planEstudios)."' AND col_carrera='".$alumno->carrera."' AND col_semestre='".$alumno->semestre."' GROUP BY col_clave ORDER BY col_id ASC";
    $sth_materias = $db->prepare($query);
    $sth_materias->execute();
    $materias = $sth_materias->fetchAll();
    foreach($materias as $itemMateria) {
        $result[] = $itemMateria['col_id'];
    }


    return $result;
}

function getMateriasByAlumnoWithPlaneaciones($alumnoid, $db) {
    $query = 'SELECT p.col_carreraid AS carrera, p.col_plan_estudios AS planEstudios, p.col_id AS periodo, p.col_grado AS semestre FROM tbl_alumnos a LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid WHERE a.col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumno = $sth->fetch(PDO::FETCH_OBJ);

    $query ="SELECT * FROM tbl_academias WHERE col_alumnoid='".$alumnoid."' AND col_periodoid='".$alumno->periodo."'";
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount()){
        $alumnoAcademia = $sth->fetch(PDO::FETCH_OBJ);
        $query ="SELECT * FROM tbl_materias WHERE col_id='".$alumnoAcademia->col_materiaid."'";
        $sth = $db->prepare($query);
        $sth->execute();
        $materiaAcademia = $sth->fetch(PDO::FETCH_OBJ);
    }

    $query ="SELECT * FROM tbl_talleres WHERE col_alumnoid='".$alumnoid."' AND col_periodoid='".$alumno->periodo."'";
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount()){
        $alumnoTaller = $sth->fetch(PDO::FETCH_OBJ);
        $query ="SELECT * FROM tbl_materias WHERE col_id='".$alumnoTaller->col_materiaid."'";
        $sth = $db->prepare($query);
        $sth->execute();
        $materiaTaller = $sth->fetch(PDO::FETCH_OBJ);
    }


    $query = "SELECT * FROM tbl_materias WHERE (col_clave NOT LIKE 'AC%' AND col_clave NOT LIKE 'TL%') AND col_plan_estudios='".intval($alumno->planEstudios)."' AND col_carrera='".$alumno->carrera."' AND col_semestre='".$alumno->semestre."' GROUP BY col_clave ORDER BY col_id ASC";
    $sth_materias = $db->prepare($query);
    $sth_materias->execute();
    $materias = $sth_materias->fetchAll();
    foreach($materias as $itemMateria) {
        $regular[] = $itemMateria['col_id'];
    }

    $query = "SELECT m.* FROM tbl_materias m WHERE (m.col_clave LIKE '".claveMateria($materiaAcademia->col_clave)."%' OR m.col_clave LIKE '".claveMateria($materiaTaller->col_clave)."%') AND col_plan_estudios='".intval($alumno->planEstudios)."' AND m.col_carrera='".$alumno->carrera."'  GROUP BY col_clave ORDER BY col_id ASC";
    $sth_materias = $db->prepare($query);
    $sth_materias->execute();
    $materias = $sth_materias->fetchAll();

    $periodoData = getPeriodo($alumno->periodo, $db, false);

    foreach($materias as $itemMateria) {
        $query = "SELECT * FROM tbl_materias_maestros_planeacion WHERE col_materiaid='".$itemMateria['col_id']."' AND col_periodoid IN (".implode(',', getPeriodosActivos($periodoData->col_groupid, $db)).")";
        $sth = $db->prepare($query);
        $sth->execute();
        if($sth->rowCount()){
            $query ="SELECT * FROM tbl_materias WHERE col_id='".$itemMateria['col_id']."'";
            $sth = $db->prepare($query);
            $sth->execute();
            $materiaData = $sth->fetch(PDO::FETCH_OBJ);
            $mClave = strtoupper(substr($materiaData->col_clave, 0, 2));
            if($mClave == 'AC' || $mClave == 'TL') $acata[] = $itemMateria['col_id'];
        }
    }

    $result['regulares'] = $regular;
    $result['acata'] = $acata;
    return $result;
}

function getTodosMisMaestros($db, $alumnoid = 0, $related = false, $transversales = false) {
    if(getCurrentUserType() == 'alumno') $alumnoid = getCurrentUserID();
    $periodos = getCurrentPeriodos($db);

    $query = 'SELECT p.col_carreraid AS carrera, p.col_plan_estudios AS planEstudios, p.col_id AS periodo, p.col_grado AS semestre FROM tbl_alumnos a LEFT OUTER JOIN tbl_periodos p ON p.col_id=a.col_periodoid WHERE a.col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumno = $sth->fetch(PDO::FETCH_OBJ);
    $periodoAlumnoID = $alumno->periodo;

    $periodoData = getPeriodo($periodoAlumnoID, $db, false);
    $transversalID = $periodoData->col_transversal;

    $query ="SELECT * FROM tbl_academias WHERE col_alumnoid='".$alumnoid."' AND col_periodoid='".$periodoAlumnoID."'";
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount()){
        $alumnoAcademia = $sth->fetch(PDO::FETCH_OBJ);
        $extraIDS[] = $alumnoAcademia->col_materiaid;
    }

    $query ="SELECT * FROM tbl_talleres WHERE col_alumnoid='".$alumnoid."' AND col_periodoid='".$periodoAlumnoID."'";
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount()){
        $alumnoTaller = $sth->fetch(PDO::FETCH_OBJ);
        $extraIDS[] = $alumnoTaller->col_materiaid;
    }

    // echo $alumnoid.'-';
    // echo $periodoAlumnoID.'-';
    // echo $transversalID.'-';
    // exit;
    $query = "SELECT * FROM tbl_materias WHERE (col_clave NOT LIKE 'AC%' AND col_clave NOT LIKE 'TL%') AND col_plan_estudios='".intval($alumno->planEstudios)."' AND col_carrera='".$alumno->carrera."' AND col_semestre='".$alumno->semestre."' GROUP BY col_clave ORDER BY col_id ASC";
    // echo $query;exit;
    $sth_materias = $db->prepare($query);
    $sth_materias->execute();
    $materias = $sth_materias->fetchAll();

    foreach($materias as $item){
            if($transversales == false && strtoupper(substr(trim($item['col_clave']), 0, 2)) == 'TR' && $item['col_id'] != $transversalID) continue;

            $query_tax = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_materia_clave="'.$item['col_clave'].'" AND col_periodoid="'.$alumno->periodo.'"';
            $sth_tax = $db->prepare($query_tax);
            $sth_tax->execute();
            $tax = $sth_tax->fetch(PDO::FETCH_OBJ);
            if(intval($tax->col_maestroid) > 0) {
                $idsmaestros[] = $tax->col_maestroid;
                $idsmaestros_realted[] = $tax->col_maestroid.'|'.$item['col_id'];
            }
    }
    if(count($extraIDS)) {
        $query = "SELECT * FROM tbl_materias WHERE col_id IN (".implode(',', $extraIDS).") GROUP BY col_clave ORDER BY col_id ASC";
        $sth_materias = $db->prepare($query);
        $sth_materias->execute();
        $materias = $sth_materias->fetchAll();

        foreach($materias as $item){
                $query_tax = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_materia_clave="'.$item['col_clave'].'" AND col_periodoid IN ('.implode(',', $periodos).')';
                $sth_tax = $db->prepare($query_tax);
                $sth_tax->execute();
                $tax = $sth_tax->fetch(PDO::FETCH_OBJ);
                if(intval($tax->col_maestroid) > 0) {
                    $idsmaestros[] = $tax->col_maestroid;
                    $idsmaestros_realted[] = $tax->col_maestroid.'|'.$item['col_id'];
                }
        }
    }

    if($related){
        return $idsmaestros_realted;
    }else{
        return $idsmaestros;
    }
}


function hasAcademias($id, $db){
    return hasTipoMateria($id, 'AC', $db);
}

function hasTalleres($id, $db){
    return hasTipoMateria($id, 'TL', $db);
}

function hasClub($id, $db){
    return hasTipoMateria($id, 'CL', $db);
}

function hasTipoMateria($id, $tipo, $db){
    $maestroID = $id;
    $periodos = getCurrentPeriodos($db);
    $has = 0;

    $query = "SELECT p.col_carreraid, p.col_plan_estudios, t.col_materia_clave, t.col_id AS ID, t.col_periodoid AS periodoid, p.col_aprobado AS horario_aprobado, p.col_groupid AS periodo_groupid, p.col_grado AS grado, p.col_grupo AS grupo ".
    "FROM tbl_maestros_taxonomia t ".
    "LEFT OUTER JOIN tbl_periodos p ON p.col_id = t.col_periodoid ".
    "WHERE t.col_maestroid='".intval($maestroID)."' ".
    "AND t.col_periodoid ".
    "ORDER BY t.col_id";

    $sth = $db->prepare($query);
    $sth->execute();
    $todos = $sth->fetchAll();
    $i = 0;


    $modalidad = array(0=>'Semestral', 1=>'Cuatrimestral');
    foreach($todos as $item){
        $queryMateria = 'SELECT * FROM tbl_materias WHERE col_semestre="'.$item['grado'].'" AND col_clave="'.trim($item['col_materia_clave']).'" AND col_plan_estudios="'.$item['col_plan_estudios'].'"';
        $sthm = $db->prepare($queryMateria);
        $sthm->execute();
        $materiaData = $sthm->fetch(PDO::FETCH_OBJ);

        $grupos = $item['grado']."-".$item['grupo'];


        if(in_array($item['periodoid'], $periodos) && $materiaData->col_nombre) {
            // $result[] = $materiaData->col_id;
            if(substr(strtoupper($materiaData->col_clave), 0 ,2) == $tipo){
                $has++;
            }
        }
    }

    if($has > 0) return true;

    return false;
}


function getTodasMisMateriasTAX($db){
    $maestroID = getCurrentUserID();
    $periodos = getCurrentPeriodos($db);

    $query = "SELECT p.col_carreraid, p.col_plan_estudios, t.col_materia_clave, t.col_id AS ID, t.col_periodoid AS periodoid, p.col_aprobado AS horario_aprobado, p.col_groupid AS periodo_groupid, p.col_grado AS grado, p.col_grupo AS grupo ".
    "FROM tbl_maestros_taxonomia t ".
    "LEFT OUTER JOIN tbl_periodos p ON p.col_id = t.col_periodoid ".
    "WHERE t.col_maestroid='".intval($maestroID)."' ".
    "AND t.col_periodoid ".
    "ORDER BY t.col_id";

    $sth = $db->prepare($query);
    $sth->execute();
    $todos = $sth->fetchAll();
    $i = 0;


    $modalidad = array(0=>'Semestral', 1=>'Cuatrimestral');
    foreach($todos as $item){
        $queryMateria = 'SELECT * FROM tbl_materias WHERE col_semestre="'.$item['grado'].'" AND col_clave="'.trim($item['col_materia_clave']).'" AND col_plan_estudios="'.$item['col_plan_estudios'].'"';
        $sthm = $db->prepare($queryMateria);
        $sthm->execute();
        $materiaData = $sthm->fetch(PDO::FETCH_OBJ);

        $grupos = $item['grado']."-".$item['grupo'];


        if(in_array($item['periodoid'], $periodos) && $materiaData->col_nombre) {
            $result[$materiaData->col_id] = $item['ID'];
        }
    }

    return $result;
}

function getTodasMisMaterias($db){
    $maestroID = getCurrentUserID();
    $periodos = getCurrentPeriodos($db);

    $query = "SELECT p.col_carreraid, p.col_plan_estudios, t.col_materia_clave, t.col_id AS ID, t.col_periodoid AS periodoid, p.col_aprobado AS horario_aprobado, p.col_groupid AS periodo_groupid, p.col_grado AS grado, p.col_grupo AS grupo ".
    "FROM tbl_maestros_taxonomia t ".
    "LEFT OUTER JOIN tbl_periodos p ON p.col_id = t.col_periodoid ".
    "WHERE t.col_maestroid='".intval($maestroID)."' ".
    "AND t.col_periodoid ".
    "ORDER BY t.col_id";

    $sth = $db->prepare($query);
    $sth->execute();
    $todos = $sth->fetchAll();
    $i = 0;


    $modalidad = array(0=>'Semestral', 1=>'Cuatrimestral');
    foreach($todos as $item){
        $queryMateria = 'SELECT * FROM tbl_materias WHERE col_semestre="'.$item['grado'].'" AND col_clave="'.trim($item['col_materia_clave']).'" AND col_plan_estudios="'.$item['col_plan_estudios'].'"';
        $sthm = $db->prepare($queryMateria);
        $sthm->execute();
        $materiaData = $sthm->fetch(PDO::FETCH_OBJ);

        $grupos = $item['grado']."-".$item['grupo'];


        if(in_array($item['periodoid'], $periodos) && $materiaData->col_nombre) {
            $result[] = $materiaData->col_id;
        }
    }

    return $result;
}

function getTodosMisAlumnos($db){

        $userType = getCurrentUserType(); // maestro - administrativo - alumno
        $currentGroupPeriodo = getCurrentPeriodo($db);
        $maestroID = getCurrentUserID();
        $currentPeriodo = getPeriodoTaxoIDSByGroup($currentGroupPeriodo, $db);
        $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');

        $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
        $c = $db->prepare($query);
        $c->execute();
        $config = $c->fetch(PDO::FETCH_OBJ);


            $periodos = getCurrentPeriodos($db);
            $query = "SELECT p.col_carreraid, p.col_plan_estudios, t.col_materia_clave, t.col_id AS ID, t.col_periodoid AS periodoid, p.col_aprobado AS horario_aprobado, p.col_groupid AS periodo_groupid, p.col_grado AS grado, p.col_grupo AS grupo ".
            "FROM tbl_maestros_taxonomia t ".
            "LEFT OUTER JOIN tbl_periodos p ON p.col_id = t.col_periodoid ".
            "WHERE t.col_maestroid='".intval($maestroID)."' ".
            "AND t.col_periodoid IN (".implode(',', $periodos).") ".
            "ORDER BY t.col_id";

            $sth = $db->prepare($query);
            $sth->execute();
            $todos = $sth->fetchAll();
            $i = 0;

            foreach($todos as $item){
                $periodoData = getPeriodo($item['periodoid'], $db, false);
                $queryMateria = 'SELECT * FROM tbl_materias WHERE col_semestre="'.$item['grado'].'" AND col_clave="'.trim($item['col_materia_clave']).'" AND col_plan_estudios="'.$item['col_plan_estudios'].'"';
                //echo '----'.$item['col_materia_clave'].'---';
                $sthm = $db->prepare($queryMateria);
                $sthm->execute();
                $materiaData = $sthm->fetch(PDO::FETCH_OBJ);
                if(in_array($item['periodoid'], $periodos) && $materiaData->col_nombre) {

                    if(strtoupper(substr($materiaData->col_clave, 0, 2)) == 'AC'){

                        $queryx = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($materiaData->col_clave).'%" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';

                        $sthx = $db->prepare($queryx);
                        $sthx->execute();
                        $dataMateriaMulti = $sthx->fetchAll();
                        unset($multis);
                        foreach($dataMateriaMulti as $mm) {
                            $multis[] = $mm['col_id'];
                        }

                        //echo '---'.$types[$periodoData->col_modalidad].'----';
                        $losPeriodosAC = getCurrentPeriodos($db, $types[$periodoData->col_modalidad]);

                        $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
                        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                        "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_academias ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodosAC).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
                        "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";
                        $sth = $db->prepare($query);
                        $sth->execute();
                        $todos = $sth->fetchAll();
                        foreach($todos as $item) {
                            $alumno[] = $item['col_id'];
                        }

                    }else if(strtoupper(substr($materiaData->col_clave, 0, 2)) == 'TL'){
                        $queryx = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($materiaData->col_clave).'%" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';

                        $sthx = $db->prepare($queryx);
                        $sthx->execute();
                        $dataMateriaMulti = $sthx->fetchAll();
                        unset($multis);
                        foreach($dataMateriaMulti as $mm) {
                            $multis[] = $mm['col_id'];
                        }
                        //echo '---'.$types[$periodoData->col_modalidad].'----';
                        $losPeriodosTL = getCurrentPeriodos($db, $types[$periodoData->col_modalidad]);

                        $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
                        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                        "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_talleres ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodosTL).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
                        "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";
                        $sth = $db->prepare($query);
                        $sth->execute();
                        $todos = $sth->fetchAll();
                        foreach($todos as $item) {
                            $alumno[] = $item['col_id'];
                        }

                    } else {
                        // echo $config->col_periodo;
                        //$types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');
                        //echo $types[$periodoData->col_modalidad];

                        //$losPeriodos = getCurrentPeriodos($this->db, $types[$periodoData->col_modalidad]);
                        switch($types[$periodoData->col_modalidad]){
                            case 'ldsem': $grupoPeriodos = $config->col_periodo; break;
                            case 'ldcua': $grupoPeriodos = $config->col_periodo_cuatri; break;
                            case 'docto': $grupoPeriodos = $config->col_periodo_doctorado; break;
                            case 'maester': $grupoPeriodos = $config->col_periodo_maestria; break;
                        }
                        $losPeriodosRE = getPeriodosActivosMaestroFilter($grupoPeriodos, $maestroID, $db);
                        $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
                        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                        "WHERE a.col_estatus='activo' AND t.col_periodoid IN (".implode(',', $losPeriodosRE).") ORDER BY a.col_apellidos ASC";
                        // "WHERE a.col_estatus='activo' AND t.col_periodoid IN (".implode(',', $losPeriodos).") ORDER BY a.col_apellidos ASC";

                        $sth = $db->prepare($query);
                        $sth->execute();
                        $todos = $sth->fetchAll();
                        foreach($todos as $item) {
                            $alumno[] = $item['col_id'];
                        }
                    }

                }
            }

        $alumno = array_unique($alumno);
        if(count($losPeriodosAC)) foreach($losPeriodosAC as $_item){ $todosLosPeriodos[] = $_item; }
        if(count($losPeriodosRE)) foreach($losPeriodosRE as $_item){ $todosLosPeriodos[] = $_item; }
        if(count($losPeriodosTL)) foreach($losPeriodosTL as $_item){ $todosLosPeriodos[] = $_item; }
        // $todosLosPeriodos = array_merge($losPeriodosAC, $losPeriodosRE, $losPeriodosTL);
        // print_r($todosLosPeriodos);exit;
        // $todosLosPeriodos = array_unique($todosLosPeriodos);

        $query = "SELECT a.*, CONCAT(a.col_nombres, ' ', a.col_apellidos) AS col_fullname, pp.col_id AS periodo_id, pp.col_grado AS periodo_semestre, pp.col_carreraid AS periodo_carrera, pp.col_plan_estudios AS periodo_plan, CONCAT(pp.col_grado, '-', pp.col_grupo) AS grupo ".
        "FROM tbl_alumnos_taxonomia t ".
        "LEFT OUTER JOIN tbl_periodos pp ON pp.col_id=t.col_periodoid ".
        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
        "WHERE t.col_periodoid IN (".implode(',', $todosLosPeriodos).") AND t.col_alumnoid IN (".implode(',', $alumno).")";
        $sth = $db->prepare($query);

        $sth->execute();
        $todos = $sth->fetchAll();
        $result = array();
        $i = 0;
        $tipos = array(
            0 => 'Visual',
            1 => 'Auditivo',
            2 => 'Cinestésico'
        );
        foreach($todos as $item){
            $result[] = $item['col_id'];
        }


        return $result;
}

function __getTodosMisAlumnos($db){
    $userType = getCurrentUserType(); // maestro - administrativo - alumno
    $currentGroupPeriodo = getCurrentPeriodo($db);
    $maestroID = getCurrentUserID();
    $currentPeriodo = getPeriodoTaxoIDSByGroup($currentGroupPeriodo, $db);

    $query = 'SELECT * FROM tbl_config WHERE col_id="1"';
    $c = $db->prepare($query);
    $c->execute();
    $config = $c->fetch(PDO::FETCH_OBJ);


        $periodos = getCurrentPeriodos($db);
        $query = "SELECT p.col_carreraid, p.col_plan_estudios, t.col_materia_clave, t.col_id AS ID, t.col_periodoid AS periodoid, p.col_aprobado AS horario_aprobado, p.col_groupid AS periodo_groupid, p.col_grado AS grado, p.col_grupo AS grupo ".
        "FROM tbl_maestros_taxonomia t ".
        "LEFT OUTER JOIN tbl_periodos p ON p.col_id = t.col_periodoid ".
        "WHERE t.col_maestroid='".intval($maestroID)."' ".
        "AND t.col_periodoid IN (".implode(',', $periodos).") ".
        "ORDER BY t.col_id";
        $sth = $db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;

        foreach($todos as $item){
            $periodoData = getPeriodo($item['periodoid'], $db, false);
            $queryMateria = 'SELECT * FROM tbl_materias WHERE col_semestre="'.$item['grado'].'" AND col_clave="'.trim($item['col_materia_clave']).'" AND col_plan_estudios="'.$item['col_plan_estudios'].'"';
            //echo '----'.$item['col_materia_clave'].'---';
            $sthm = $db->prepare($queryMateria);
            $sthm->execute();
            $materiaData = $sthm->fetch(PDO::FETCH_OBJ);

            $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');
            $losPeriodos = getCurrentPeriodos($db, $types[$periodoData->col_modalidad]);

            if(in_array($item['periodoid'], $periodos) && $materiaData->col_nombre) {

                if(strtoupper(substr($materiaData->col_clave, 0, 2)) == 'AC'){

                    $queryx = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($materiaData->col_clave).'%" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';

                    $sthx = $db->prepare($queryx);
                    $sthx->execute();
                    $dataMateriaMulti = $sthx->fetchAll();
                    unset($multis);
                    foreach($dataMateriaMulti as $mm) {
                        $multis[] = $mm['col_id'];
                    }

                    $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
                    "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                    "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_academias ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
                    "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";
                    $sth = $db->prepare($query);
                    $sth->execute();
                    $todos = $sth->fetchAll();
                    foreach($todos as $item) {
                        $alumno[] = $item['col_id'];
                    }

                }else if(strtoupper(substr($materiaData->col_clave, 0, 2)) == 'TL'){
                    $queryx = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($materiaData->col_clave).'%" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';

                    $sthx = $db->prepare($queryx);
                    $sthx->execute();
                    $dataMateriaMulti = $sthx->fetchAll();
                    unset($multis);
                    foreach($dataMateriaMulti as $mm) {
                        $multis[] = $mm['col_id'];
                    }

                    $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
                    "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                    "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_talleres ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
                    "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";
                    $sth = $db->prepare($query);
                    $sth->execute();
                    $todos = $sth->fetchAll();
                    foreach($todos as $item) {
                        $alumno[] = $item['col_id'];
                    }

                } else {

                    $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
                    "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
                    "WHERE a.col_estatus='activo' AND t.col_periodoid IN (".implode(',', getPeriodosActivosMaestroFilter($losPeriodos, $maestroID, $db)).") ORDER BY a.col_apellidos ASC";
                    $sth = $db->prepare($query);
                    $sth->execute();
                    $todos = $sth->fetchAll();
                    foreach($todos as $item) {
                        $alumno[] = $item['col_id'];
                    }
                }

            }
        }

    $alumno = array_unique($alumno);

    $query = "SELECT a.*, CONCAT(a.col_nombres, ' ', a.col_apellidos) AS col_fullname, pp.col_id AS periodo_id, pp.col_grado AS periodo_semestre, pp.col_carreraid AS periodo_carrera, pp.col_plan_estudios AS periodo_plan, CONCAT(pp.col_grado, '-', pp.col_grupo) AS grupo ".
    "FROM tbl_alumnos_taxonomia t ".
    "LEFT OUTER JOIN tbl_periodos pp ON pp.col_id=t.col_periodoid ".
    "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
    "WHERE t.col_periodoid IN (".implode(',', getPeriodosActivosMaestro($losPeriodos, $maestroID, $db)).") AND t.col_alumnoid IN (".implode(',', $alumno).")";
    $sth = $db->prepare($query);
    $sth->execute();
    $todos = $sth->fetchAll();
    foreach($todos as $item) {
        $result[] = $item['col_id'];
    }

    return $result;
}


function getAlumnosByTaxMateria($tax, $db){
    $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_id="'.intval($tax).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    $periodoData = getPeriodo($data->col_periodoid, $db, false);
    $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.trim($data->col_materia_clave).'" AND col_semestre="'.$periodoData->col_grado.'" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataMateria = $sth->fetch(PDO::FETCH_OBJ);

    if(strtoupper(substr($dataMateria->col_clave, 0, 2)) == 'AC'){

        $queryx = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($data->col_materia_clave).'%" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
        $sthx = $db->prepare($queryx);
        $sthx->execute();
        $dataMateriaMulti = $sthx->fetchAll();
        unset($multis);
        foreach($dataMateriaMulti as $mm) {
            $multis[] = $mm['col_id'];
        }
        $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');
        $losPeriodos = getCurrentPeriodos($db, $types[$periodoData->col_modalidad]);

        $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
        "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_academias ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
        "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";

    } else if(strtoupper(substr($dataMateria->col_clave, 0, 2)) == 'TL'){

            $queryx = 'SELECT * FROM tbl_materias WHERE col_clave LIKE "'.claveMateria($data->col_materia_clave).'%" AND col_carrera="'.$periodoData->col_carreraid.'" AND col_plan_estudios="'.$periodoData->col_plan_estudios.'"';
            $sthx = $db->prepare($queryx);
            $sthx->execute();
            $dataMateriaMulti = $sthx->fetchAll();
            unset($multis);
            foreach($dataMateriaMulti as $mm) {
                $multis[] = $mm['col_id'];
            }
            $types = array(1 => 'ldsem', 2=> 'ldcua', 3=>'docto', 4=>'master');
            $losPeriodos = getCurrentPeriodos($db, $types[$periodoData->col_modalidad]);

            $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
            "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
            "WHERE a.col_estatus='activo' AND t.col_alumnoid IN (SELECT ac.col_alumnoid FROM tbl_talleres ac LEFT OUTER JOIN tbl_alumnos ax ON ax.col_id=ac.col_alumnoid WHERE ac.col_periodoid IN (".implode(',', $losPeriodos).") AND ac.col_materiaid IN (".implode(',', $multis).")) ".
            "GROUP BY t.col_alumnoid ORDER BY a.col_apellidos ASC";
    } else {
        $query = "SELECT a.* FROM tbl_alumnos_taxonomia t ".
        "LEFT OUTER JOIN tbl_alumnos a ON a.col_id=t.col_alumnoid ".
        "WHERE a.col_estatus='activo' AND t.col_periodoid='".$data->col_periodoid."' ORDER BY a.col_apellidos ASC";
    }

    $sth = $db->prepare($query);
    $sth->execute();
    $todos = $sth->fetchAll();
    $i = 0;
    foreach($todos as $item){
        $result[$i] = $item['col_id'];
        $i++;
    }

    return $result;
}

function checkPassword($password){
    if ( strlen( $password ) == 0 )
    {
        return "La contraseña no puede estar vacia.";
    }
    $pwd = $password;

    if( strlen($pwd) < 8 ) {
        return "La contraseña debe tener minimo 8 caracteres entre números, lestras y simbolos.";
    }


    if( !preg_match("#[0-9]+#", $pwd) ) {
        return "La contraseña debe contener al menos un número.";
    }

    if( !preg_match("#[a-z]+#", $pwd) ) {
        return "La contraseña debe contener al menos una letra.";
    }

    if( !preg_match("#[A-Z]+#", $pwd) ) {
        return "La contraseña debe contener al menos una letra MAYÚSCULA.";
    }

    if( !preg_match("#\W+#", $pwd) ) {
        return "La contraseña debe contener al menos un simbolo: #$%!@=?.";
    }

    return 'safe';
}

function getBrowserFingerprint($clientid) {
    $client_ip = getRealIP();
    $useragent = $_SERVER['HTTP_USER_AGENT'];
    $accept   = $_SERVER['HTTP_ACCEPT'];
    $charset  = $_SERVER['HTTP_ACCEPT_CHARSET'];
    $encoding = $_SERVER['HTTP_ACCEPT_ENCODING'];
    $language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    $cook = $_SERVER['HTTP_COOKIE'];
    $data = '';
    $data .= $client_ip;
    $data .= $useragent;
    // $data .= $cook;
    $data .= $accept;
    $data .= $charset;
    $data .= $encoding;
    $data .= $language;
    // $data .= $clientid;
    // return $data;
    /* Apply SHA256 hash to the browser fingerprint */
    $hash = hash('sha256', $data);
    return $hash;
  }

  /**
  * Determines the client IP address
  *
  * @return string
  */

  function getRealIP() {
    foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
      if (array_key_exists($key, $_SERVER) === true) {
        foreach (explode(',', $_SERVER[$key]) as $ip) {
          if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
            return $ip;
          }
        }
      }
    }
  }

  function getBaseURL(){
    if($_SERVER['SERVER_ADDR'] == '37.247.52.225') {
        return "https://ranascreativas.com/universidad/src";
    }else{
        return "http://192.168.12.81";
    }
  }

  function getImage($tipo) {
    switch($tipo){
        case 'foto':
        $img = "/assets/images/foto.png";
        break;

        case 'encabezado':
        $img = "/assets/images/imgencabezado.png";
        break;

        case 'posgrado':
        $img = "/assets/images/imaposgrados.png";
        break;

        case 'protestamujer':
        $img = "/assets/images/protestamujer.jpg";
        break;

        case 'protestahombre':
        $img = "/assets/images/protestahombre.jpg";
        break;
    }

    if($_SERVER['SERVER_ADDR'] == '37.247.52.225') {
        return "https://ranascreativas.com/universidad/src".$img;
    }else{
        return "http://192.168.12.81".$img;
        return "https://plataforma.fldch.edu.mx".$img;
    }
  }

function getLogo($tipo = 'default', $svg = false) {

    switch($tipo){
        case 'fondo_azul':
        $img = "/assets/images/azul.png";
        break;

        case 'borde_izq':
            $img = "/assets/images/egel_izq.png";
        break;

        case 'borde_der':
            $img = "/assets/images/egel_der.png";
        break;

        case 'diagonal':
        $img = "/assets/images/diagonal.jpg";
        break;

        case 'diagonal2':
        $img = "/assets/images/diagonal2.jpg";
        break;

        case 'sep_chiapas_nuevo':
        $img = "/assets/images/sep-chiapas-nuevo.png";
        break;

        case 'sep_chiapas':
        $img = "/assets/images/sep-chiapas.jpg";
        break;

        case 'sep_chiapas_png':
            $img = "/assets/images/sep-chiapas.png";
            break;

        case 'sep_chiapas_icono':
        $img = "/assets/images/sep-chiapas-icono.jpg";
        break;

        case 'gobernacion':
        $img = "/assets/images/gobernacion.png";
        break;

        case 'iconoLogo':
        if($_SERVER['SERVER_ADDR'] == '192.168.12.83') {
            $img = "/assets/images/icono-inef-azul.png";
        }else{
            $img = "/assets/images/icono-fldch-rojo.png";
        }
        break;

        case 'big':
        if($_SERVER['SERVER_ADDR'] == '192.168.12.83') {
            $img = "/assets/images/logo-inef-impresion-grande.jpg";
        }else{
            $img = "/assets/images/logo-fldch-impresion-grande.jpg";
        }
        break;

        case 'default':
        if($_SERVER['SERVER_ADDR'] == '192.168.12.83') {
            $img = "/assets/images/logo-inef-impresion.jpg";
            if($svg) $img = "/assets/images/logo-inef-impresion.svg";
        }else{
            $img = "/assets/images/logo-fldch-impresion.jpg";
            if($svg) $img = "/assets/images/logo-fldch-impresion.svg";
        }
        break;
    }


    if($_SERVER['SERVER_ADDR'] == '37.247.52.225') {
        $imgURL = "https://ranascreativas.com/universidad/src".$img;
    }else{
        $imgURL = "http://192.168.12.81".$img;
        // $imgURL = "https://plataforma.fldch.edu.mx".$img;
    }

    if($svg){
        return $imgURL;
    }else{
        return $imgURL;
    }
}

function enviarCorreo($destinatarios, $asunto, $texto, $reply = '', $attach = '', $attachName = '') {
    $post['a1'] = $asunto;
    $post['a2'] = $texto;
    $post['a3'][] = $destinatarios;
    $post['a4'] = $reply;
    $post['a5'] = urlencode($attach);
    $post['a6'] = $attachName;

    if($_SERVER['SERVER_ADDR'] == '192.168.12.82') {
        $url = 'http://192.168.12.81/mail/send.php';
    } else if($_SERVER['SERVER_ADDR'] == '192.168.12.83') {
        $url = 'http://192.168.12.81/mail/send_inef.php';
    } else {
        $url = 'https://fldch.ranascreativas.com/mail/send.php';
    }

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $server_output = curl_exec($ch);
    if($server_output === FALSE) {
        die(curl_error($ch));
    }
    curl_close ($ch);

    return true;
}

function getMateriaByActividad($data, $db, $_maestroid = 0, $actividadid = 0) {
    $data = unserialize(stripslashes($data));
    $maestroID = getCurrentUserID();
    if(intval($_maestroid) > 0) $maestroID = $_maestroid;

    if(intval($actividadid) > 0) {

        $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.intval($actividadid).'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $act = $sth->fetch(PDO::FETCH_OBJ);

        if(intval($act->col_materiaid) > 0) {
            $query = 'SELECT * FROM tbl_materias WHERE col_id="'.intval($act->col_materiaid).'"';
            $sth = $db->prepare($query);
            $sth->execute();
            $mat = $sth->fetch(PDO::FETCH_OBJ);
            if($mat->col_nombre != '') return fixEncode($mat->col_nombre);
        }

    }


    if($data){
        if(intval($data) > 0){
            $periodoData = getPeriodo($data, $db, false);
            $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_maestroid="'.intval($maestroID).'" AND col_periodoid="'.$data.'"';
            $sth = $db->prepare($query);
            $sth->execute();
            $tax = $sth->fetch(PDO::FETCH_OBJ);

            $query = 'SELECT * FROM tbl_materias WHERE col_id="'.getMateriaIDbyTAX($tax->col_id, $db).'"';
            $sth = $db->prepare($query);
            $sth->execute();
            $mat = $sth->fetch(PDO::FETCH_OBJ);

            return fixEncode($mat->col_nombre);

        }else{
            // $data = explode('|', $data);
            // print_r($data);
            $dataVisiblePara = explode('|', $data);
            $materiaClave = claveMateria($dataVisiblePara[1]);
            $periodosVisiblePara = explode(',', $dataVisiblePara[2]);

            $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_periodoid IN('.$dataVisiblePara[2].') AND col_materia_clave="'.$dataVisiblePara[1].'" AND col_maestroid="'.intval($maestroID).'"';
            $sth = $db->prepare($query);
            $sth->execute();
            $tax = $sth->fetch(PDO::FETCH_OBJ);


            $query = 'SELECT * FROM tbl_materias WHERE col_id="'.getMateriaIDbyTAX($tax->col_id, $db).'"';
            $sth = $db->prepare($query);
            $sth->execute();
            $mat = $sth->fetch(PDO::FETCH_OBJ);

            return fixEncode($mat->col_nombre);
        }
    }
}


function getMateriaByActividadID($data, $db, $_maestroid = 0, $actividadid = 0, $carreraID = 0) {
    $data = unserialize(stripslashes($data));
    if(intval($_maestroid) > 0) {

        $maestroID = $_maestroid;
    }else{
        $maestroID = getCurrentUserID();
    }

    if(intval($actividadid) > 0) {

        $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.intval($actividadid).'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $act = $sth->fetch(PDO::FETCH_OBJ);

        if(intval($act->col_materiaid) > 0) {
            return $act->col_materiaid;
            // $query = 'SELECT * FROM tbl_materias WHERE col_id="'.intval($act->col_materiaid).'"';
            // $sth = $db->prepare($query);
            // $sth->execute();
            // $mat = $sth->fetch(PDO::FETCH_OBJ);
            // if($mat->col_nombre != '') return fixEncode($mat->col_nombre);
        }

    }



    if(intval($data) > 0){
        $periodoData = getPeriodo($data, $db, false);
        $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_maestroid="'.intval($maestroID).'" AND col_periodoid="'.$data.'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $tax = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_materias WHERE col_id="'.getMateriaIDbyTAX($tax->col_id, $db).'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $mat = $sth->fetch(PDO::FETCH_OBJ);

        return intval($mat->col_id);
        // return fixEncode($mat->col_nombre);

    }else{
        // $data = explode('|', $data);
        $dataVisiblePara = explode('|', $data);
        $materiaClave = claveMateria($dataVisiblePara[1]);
        $periodosVisiblePara = explode(',', $dataVisiblePara[2]);

        $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_periodoid IN('.$dataVisiblePara[2].') AND col_materia_clave="'.$dataVisiblePara[1].'" AND col_maestroid="'.intval($maestroID).'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $tax = $sth->fetch(PDO::FETCH_OBJ);

        $query = 'SELECT * FROM tbl_materias WHERE col_id="'.getMateriaIDbyTAX($tax->col_id, $db).'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $mat = $sth->fetch(PDO::FETCH_OBJ);

        return intval($mat->col_id);
        // return fixEncode($mat->col_nombre);
    }
}

function calcularResultados($preguntaid, $evaid, $maestroid, $subquery, $db){
    $query = 'SELECT * FROM `tbl_eva_maestros_respuestas` WHERE col_preguntaid="'.$preguntaid.'" AND col_evaid="'.$evaid.'" AND col_maestroid="'.$maestroid.'" AND col_materiaid IN ( '.$subquery.' ) GROUP BY col_alumnoid';
    $sth = $db->prepare($query);
    $sth->execute();
    $todos = $sth->fetchAll();
    foreach($todos as $item){
        $results[$item['col_respuesta']] = intval($results[$item['col_respuesta']]) + 1;
    }

    return $results;
}

function makeClickeable($text){
    // The Regular Expression filter
    $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
    // Check if there is a url in the text
    if(preg_match($reg_exUrl, $text, $url)) {
           // make the urls hyper links
           return preg_replace($reg_exUrl, '<a href="'.$url[0].'">'.$url[0].'</a> ', $text);
    } else {
           // if no urls in the text just return the text
           return $text;
    }
}


function getResultadoEvaAlumnos($alumnoid, $db, $evaid) {
    $query = "SELECT CONCAT(u.col_nombres, ' ', u.col_apellidos) AS alumno, r.col_alumnoid, r.col_aprobado FROM tbl_eva_alumnos_respuestas r LEFT OUTER JOIN tbl_alumnos u ON u.col_id=r.col_alumnoid WHERE r.col_evaid='".intval($evaid)."' AND r.col_alumnoid='".$alumnoid."'";
    $sth = $db->prepare($query);
    $sth->execute();
    $todos = $sth->fetchAll();
    $i = 0;
    $result['resultado'] = '-';

    foreach($todos as $item){


        $query = 'SELECT SUM(IF(col_respuesta = "A", 1, 0)) AS visual, SUM(IF(col_respuesta = "B", 1, 0)) AS auditivo, SUM(IF(col_respuesta = "C", 1, 0)) AS cinestesico FROM tbl_eva_alumnos_respuestas WHERE col_alumnoid="'.$alumnoid.'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $dataRespuestas = $sth->fetch(PDO::FETCH_OBJ);


        $result['alumno'] = fixEncode(trim($item['alumno'])).$item['col_alumnoid'];
        $result['visual'] = $dataRespuestas->visual;
        $result['auditivo'] = $dataRespuestas->auditivo;
        $result['cinestesico'] = $dataRespuestas->cinestesico;
        $estilo = "Visual";
        if($dataRespuestas->visual > $dataRespuestas->auditivo && $dataRespuestas->visual > $dataRespuestas->cinestesico) $estilo = 'Visual';
        if($dataRespuestas->auditivo > $dataRespuestas->visual && $dataRespuestas->auditivo > $dataRespuestas->cinestesico) $estilo = 'Auditivo';
        if($dataRespuestas->cinestesico > $dataRespuestas->visual && $dataRespuestas->cinestesico > $dataRespuestas->auditivo) $estilo = 'Cinestésico';
        $result['resultado'] = $estilo;


    }

    return $result;
}


function getRangoFechas($db, $alumnoid, $periodoid, $materiaid = 0, $fechaBaseFin = '', $tipoActividad = ''){
    $query = 'SELECT * FROM tbl_config WHERE col_id=1';
    $sth = $db->prepare($query);
    $sth->execute();
    $config = $sth->fetch(PDO::FETCH_OBJ);



    $periodoData = getPeriodo($periodoid, $db, false);

    if(intval($materiaid) > 0){
        $query = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaid.'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $materiaData = $sth->fetch(PDO::FETCH_OBJ);
        if($fechaBaseFin == ''){
            if(strtoupper(substr($materiaData->col_clave, 0, 2)) == 'AC' || strtoupper(substr($materiaData->col_clave, 0, 2)) == 'TL'){
                $query = 'SELECT * FROM tbl_actividades WHERE UPPER(col_visible_excepto) LIKE "%'.strtoupper($materiaData->col_clave).'%" AND col_fecha_inicio>"'.$periodoData->col_fecha_inicio.'" AND col_tipo=5';
            }else{
                $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.$periodoid.'%" AND col_materiaid="'.$materiaid.'" AND col_fecha_inicio>"'.$periodoData->col_fecha_inicio.'" AND col_tipo=5';
            }
        }else{
            $ElTipoActividad = 5;
            if($tipoActividad == 6) $ElTipoActividad = 5;
            if($tipoActividad == 7) $ElTipoActividad = 6;

            if(strtoupper(substr($materiaData->col_clave, 0, 2)) == 'AC' || strtoupper(substr($materiaData->col_clave, 0, 2)) == 'TL'){
                $query = 'SELECT * FROM tbl_actividades WHERE UPPER(col_visible_excepto) LIKE "%'.strtoupper($materiaData->col_clave).'%" AND col_fecha_inicio>="'.$periodoData->col_fecha_inicio.'" AND col_fecha_inicio<"'.$fechaBaseFin.'" AND col_tipo="'.$ElTipoActividad.'"';
            }else{
                $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE "%'.$periodoid.'%" AND col_materiaid="'.$materiaid.'" AND col_fecha_inicio>="'.$periodoData->col_fecha_inicio.'" AND col_fecha_inicio<"'.$fechaBaseFin.'" AND col_tipo="'.$ElTipoActividad.'"';
            }
        }

        $sth = $db->prepare($query);
        $sth->execute();
        if($sth->rowCount() > 0){
            $actividadData = $sth->fetch(PDO::FETCH_OBJ);
            return array(date('Y-m-d', strtotime($actividadData->col_fecha_inicio.' +1 day')), date('Y-m-d', strtotime('now')));
        }else{
            return array($periodoData->col_fecha_inicio, $fechaBaseFin);
        }
    }



        $modalidadAlumno = getModalidadAlumno($alumnoid, $db);
        switch($modalidadAlumno) {
            case 'Semestral':
            $primerParcial = explode(',', $config->col_primer_parcial_semestral);
            $segundoParcial = explode(',', $config->col_segundo_parcial_semestral);
            break;

            case 'Cuatrimestral':
            $primerParcial = explode(',', $config->col_primer_parcial_cuatrimestral);
            $segundoParcial = explode(',', $config->col_segundo_parcial_cuatrimestral);
            break;

            case 'Maestria':
            $primerParcial = explode(',', $config->col_primer_parcial_maestria);
            $segundoParcial = explode(',', $config->col_segundo_parcial_maestria);
            break;

            case 'Doctorado':
            $primerParcial = explode(',', $config->col_primer_parcial_doctorado);
            $segundoParcial = explode(',', $config->col_segundo_parcial_doctorado);
            break;
        }

        //print_r($primerParcial);
        //exit;
        $rangoFechaInicio = $periodoData->col_fecha_inicio;
        $rangoFechaFin = $primerParcial[0];
        if($primerParcial[1] != '' && (strtotime($primerParcial[1]) < strtotime('now'))) {
            $rangoFechaInicio = $primerParcial[1];
            $rangoFechaFin = $segundoParcial[0];
        }

        return array($rangoFechaInicio, $rangoFechaFin);
}

function acreditaPresentarByActividad($db, $alumnoid, $actividadid = 0, $_materiaid = 0, $fechaLimite = '') {
    global $alertaAsistencias;

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

    if($actividadid == 0 && $_materiaid > 0) {
        $materiaid = $_materiaid;

        $query = 'SELECT * FROM tbl_actividades WHERE col_materiaid="'.$materiaid.'" AND col_visible_excepto LIKE "%'.$alumnoData->col_periodoid.'%" ORDER BY col_id ASC LIMIT 1';
        // $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $actividadData = $sth->fetch(PDO::FETCH_OBJ);

    }else{
        $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $actividadData = $sth->fetch(PDO::FETCH_OBJ);

        $tipoActividad = $actividadData->col_tipo;
        $materiaid = $actividadData->col_materiaid;

        if($materiaid == 0) {
            $materiaid = getMateriaByActividadID($actividadData->col_visible_excepto, $db, $actividadData->col_created_by, $actividadid);
        }

    }


    list($rangoFechaInicio, $rangoFechaFin) = getRangoFechas($db, $alumnoid, $alumnoData->col_periodoid, $materiaid, $actividadData->col_fecha_inicio, $tipoActividad);
    if($fechaLimite != '') $rangoFechaFin = $fechaLimite;

    if($actividadData->col_tipo != 8 && $actividadData->col_tipo != 9) {
        $asistencias = getAsistenciasByAlumnoAndMateria($alumnoid, $db, $rangoFechaInicio, $rangoFechaFin, $materiaid);



        if($asistencias[$materiaid]['porcentaje'] > $alertaAsistencias) {
            $reasons[] = 'Por Inasistencias';
        }
    }


    // Revisamos si tiene deudas economicas
    if($actividadid == 0){
        $deudas = tieneDeudas($db, $alumnoid, $alumnoData->col_referencia);
    }else{
        $deudas = tieneDeudas($db, $alumnoid, $alumnoData->col_referencia, $actividadData->col_fecha_inicio);
    }
    if($deudas['status'] == 'true') {
        $reasons[] = 'Detalles Administrativos';
    }

    // Revisamos si tiene libros prestados
    if($actividadid == 0){
        $biblioteca = librosPendientes($db, $alumnoid);
    }else{
        $biblioteca = librosPendientes($db, $alumnoid, $actividadData->col_fecha_inicio);
    }
    if($biblioteca['status'] == 'true') {
        $reasons[] = 'Libros sin devolver';
    }

    if(count($reasons) > 0) {
        return array('status' => 'sin-derecho', 'reason' => implode(',', $reasons), 'debug' => $asistencias);
    }

    return array('status' => 'con-derecho', 'debug' => $asistencias);
}

function tieneDeudas($db, $alumnoid, $referencia = '', $_fecha = '') {
    if($referencia == '') {
        $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
        $referencia = $alumnoData->col_referencia;
    }
    if($_fecha != '') {
        $query = 'SELECT * FROM tbl_pagos WHERE (col_alumnoid="'.$alumnoid.'" OR (col_referencia="'.$referencia.'" AND col_referencia!="")) AND col_created_at<"'.substr($_fecha, 0, 10).' 23:59:59"';
    }else{
        $query = 'SELECT * FROM tbl_pagos WHERE (col_alumnoid="'.$alumnoid.'" OR (col_referencia="'.$referencia.'" AND col_referencia!=""))';
    }
    // $query = 'SELECT * FROM tbl_pagos WHERE col_alumnoid="'.$data->col_id.'" OR col_referencia="'.$data->col_referencia.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount()){
        $pagos = $sth->fetch(PDO::FETCH_OBJ);
        if($pagos->col_total_adeudo_vencido > 0){
            return array('status' => 'true', 'data' => $pagos);
        }
    }
    return array('status' => 'false');
}

function librosPendientes($db, $alumnoid, $_fecha = '') {
    if($_fecha != ''){
        $queryBiblioteca = 'SELECT * FROM tbl_biblioteca WHERE col_alumnoid="'.$alumnoid.'" AND col_fecha_devolucion="0000-00-00" AND col_created_at<"'.substr($_fecha, 0, 10).' 23:59:59"';
    }else{
        $queryBiblioteca = 'SELECT * FROM tbl_biblioteca WHERE col_alumnoid="'.$alumnoid.'" AND col_fecha_devolucion="0000-00-00"';
    }
    $sthBiblioteca = $db->prepare($queryBiblioteca);
    $sthBiblioteca->execute();
    if($sthBiblioteca->rowCount()){
        $bib = $sthBiblioteca->fetch(PDO::FETCH_OBJ);
        $bib->col_fecha_prestamo.'--'.$bib->col_hora_prestamo.'--'.$bib->col_tipo_multa;
        $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($bib->col_fecha_prestamo)), $bib->col_hora_prestamo, $bib->col_tipo_multa, $db);
        if($bib->col_renovacion == 'si'){
            $multa = getMultaBiblioteca(strtotime('+1 day', strtotime($bib->col_fecha_renovacion)), $bib->col_hora_renovacion, $bib->col_tipo_multa, $db);
        }
        if(intval($multa) > 0){
            return array('status' => 'true', 'libro' => fixEncode($bib->col_titulo_libro), 'multa' => $multa);
        }
    }

    return array('status' => 'false');
}

function puedeCursarMateria($alumnoid, $taxid = 0, $materiaid, $db) {

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

    // $periodoData = getPeriodo($alumnoData->col_periodoid, $db, false);

    $query = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $materiaData = $sth->fetch(PDO::FETCH_OBJ);
    if($materiaData->col_serie != '') {
        if($materiaData->col_serie > 0) {
            $query = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaData->col_serie.'"';
            $sth = $db->prepare($query);
            $sth->execute();
            $materiaSerieData = $sth->fetch(PDO::FETCH_OBJ);

            $claveSeriada = claveMateria($materiaSerieData->col_clave);
        }else{
            $claveSeriada = claveMateria($materiaData->col_serie);
        }

        $subQuery = 'SELECT col_periodoid FROM tbl_alumnos_taxonomia WHERE col_alumnoid="'.$alumnoid.'" AND col_periodoid!="'.$alumnoData->col_periodoid.'"';


        $query = 'SELECT * FROM tbl_calificaciones WHERE col_periodoid IN ('.$subQuery.') AND col_materia_clave LIKE "'.$claveSeriada.'%" AND col_alumnoid="'.$alumnoid.'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $calificacionesData = $sth->fetch(PDO::FETCH_OBJ);
        // return $calificacionesData->col_cf.'--'.$alumnoid;
        if(intval($calificacionesData->col_cf < 6) && intval($calificacionesData->col_ext) < 6) {
            return '*'; // No puede presentar
        }
        // return $calificacionesData->col_cf;
        // return $materiaData->col_serie;
    }

    return '-'; // Si puede presentar
}

// getPlagiosPorTaxMateria($alumno['col_id'], $clave['col_id'], $this->db);
function getPlagiosPorTaxMateria($alumnoid, $taxid, $db){
    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnoData = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_id="'.$taxid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $taxData = $sth->fetch(PDO::FETCH_OBJ);
    $periodoData = getPeriodo($taxData->col_periodoid, $db, false);

    $laClave = substr(strtoupper($taxData->col_materia_clave), 0, 2);
    $_TaxsMateria = array();
    $carreraData = getCarrera($alumnoData->col_carrera, $db);
    $periodosActivosModalidad = getCurrentPeriodos($db, $carreraData['modalidad_periodo']);

    if($laClave == 'AC' || $laClave == 'TL'){
        $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_periodoid IN ('.implode(',', $periodosActivosModalidad).') AND (col_materia_clave="'.$taxData->col_materia_clave.'" OR col_materia_clave="'.claveMateria($taxData->col_materia_clave).'") GROUP BY col_materia_clave';
    }else{
        $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_periodoid="'.$alumnoData->col_periodoid.'" AND (col_materia_clave="'.$taxData->col_materia_clave.'" OR col_materia_clave="'.claveMateria($taxData->col_materia_clave).'") GROUP BY col_materia_clave';
    }
    $sth = $db->prepare($query);
    $sth->execute();
    $lasTaxMateria = $sth->fetchAll();
    foreach($lasTaxMateria as $elm) {
        $_TaxsMateria[] = $elm['col_id'];
    }


    // return implode(',', $_TaxsMateria);

    $query = 'SELECT s.*, a.col_materiaid AS taxID, a.col_fecha FROM tbl_asistencia_alumnos s '.
    'LEFT OUTER JOIN tbl_asistencia a ON a.col_id=s.col_listaid '.
    'WHERE s.col_alumnoid="'.$alumnoid.'" AND s.col_listaid IN (SELECT col_id FROM tbl_asistencia WHERE col_fecha >= "'.$from.'" AND col_fecha <= "'.$to.'") GROUP BY taxID';
    $sth = $db->prepare($query);
    $sth->execute();
    $TodoMateriasTax = $sth->fetchAll();

    return 0;
}

function randomPassword() {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

function numerosaletras($xcifra){ #Función Principal
    $valor = trim($xcifra); //variable que va a recibir los digitos sin espacios.
    if(strtoupper($valor) == 'NP') {
        return 'NO PRESENTO';
    }
    if($valor == '') return '';
    if(strpos($xcifra, '.') !== false) {
        $split = explode('.', $xcifra);
        return _numerosaletras($split[0]).' punto '.strtolower(_numerosaletras($split[1]));
    }else{
        return _numerosaletras($xcifra);
    }
}

function _numerosaletras($xcifra){ #Función Principal

    $valor = trim($xcifra); //variable que va a recibir los digitos sin espacios.
    if($valor == '') return '';
    $tamaño = strlen($xcifra);//variable que registra la cantidad de digitos que hay.
    switch ($tamaño) {//caso por tamaño, para tomar una función diferente
        case '4':
            $cadena = cuatro($valor); // se llama a la función 4 (cuatro) que significa 4 Digitos
            break;
        case '3':
            $cadena = tres($valor);// se llama a la función 3 (tres) que significa 3 Digitos
            break;
        case '2':
            $cadena = dos($valor);// se llama a la funcion 2 (dos) que significa 2 Digitos
            break;
        case '1':# aqui se llama directamente a la función donde se asigna las letras a los números.
            $digito = array();//variable en forma de arreglo donde se obtiene el valor en número y letra.
            for ($i=0; $i < $tamaño; $i++) {
            $digito[$i] = letras($valor[$i]);//se le asigna a $digito el valor obtenido de la función letra.
            }
            $cadena = trim(substr($digito[0], 1));//Se subtrae solamente el nombre del número pasado por paramentros.
            break;
        default:
            $cadena = " No se puede procesar una cifra mayor de 4 digitos";
            break;
    }
    return trim($cadena);//se devuelve el valor obtenido.
}
function cuatro($valor)//función para digitos de 4 Cifra, se puede usar (copiando el codigo) tambien para 8 cifras y mas solo le cambias el Mil por Millones y así sucesivamente
{
    $principal = "";//recibe el primer valor en letra mas el termino Mil
    $digito = array();
    $tamaño = strlen($valor);
    for ($i=0; $i < $tamaño; $i++) {
        $digito[$i] = letras($valor[$i]);
    }
    if (substr($digito[0], 0, 1) == "1") {#si el valor es igual 1 (uno) se coloca solamente Mil,
        $principal = "Mil";
        $axu = tres(trim(substr($valor, 1)));//esta variable llama a la función tres, por si hay mas digitos en $valor
    }elseif (substr($digito[0], 0, 1) == "0"){ //si es igual a cero no se coloca nada en principal y se llama a la función siguiente.
        $principal = " ";
        $axu = tres(trim(substr($valor, 1)));
    }else{//sino se coloca el nombre de numero mas MIL y se llama a la siguiente función
        $principal = trim(substr($digito[0], 1))." Mil";
        $axu = tres(trim(substr($valor, 1)));
    }
    return $principal." ".$axu; // devuelve el valor obtenido en letras de primer digitos y de las siquientes funciones.
}
function tres($valor)//es como la funcion cuatro, se puede copiar para 6 digitos o mas.
{
    $principal = "";
    $digito = array();
    $tamaño = strlen($valor);
    for ($i=0; $i < $tamaño; $i++) {
        $digito[$i] = letras($valor[$i]);
    }
    if (substr($digito[0], 0, 1) == "1") {// lo mismo que en la función anterior.
        if ($valor == 100) {//si valor es igual a cien directamente, se coloca Cien y no se llama a siguientes funciones.
            $principal = "Cien";
            $axu = " ";
        }else{//sino se llama a la funcion dos y se coloca Ciento.
        $principal = "Ciento";
        $axu = dos(trim(substr($valor, 1)));
        }
    }elseif (substr($digito[0], 0, 1) == "0"){// lo mismo que en la función cuatro.
        $principal = " ";
        $axu = dos(trim(substr($valor, 1)));
        // esto es por obios motivos, lo tuve que separa en casos expeficicos porque los nombres en letras son diferentes.
    }elseif (substr($digito[0], 0, 1) == "5") {
        $principal = "Quinientos";
        $axu = dos(trim(substr($valor, 1)));
    }elseif (substr($digito[0], 0, 1) == "7") {
        $principal = "Setecientos";
        $axu = dos(trim(substr($valor, 1)));
    }elseif (substr($digito[0], 0, 1) == "9") {
        $principal = "Novecientos";
        $axu = dos(trim(substr($valor, 1)));
        // Fin, esto es por obios motivos, lo tuve que separa en casos expeficicos porque los nombres en letras son diferentes.
    }else{//sino como todo es normal con los valores que no tienes nombres diferentes
        $principal = trim(substr($digito[0], 1))."cientos";
        $axu = dos(trim(substr($valor, 1)));
    }
    return $principal." ".$axu;
}
function dos($valor)// esta funcion puede servir para mas valores, como las dos de arribas
{
    $digito = array();
    $tamaño = strlen($valor);
    for ($i=0; $i < $tamaño; $i++) {
        $digito[$i] = letras($valor[$i]);
    }
    switch (substr($digito[0], 0, 1)) {
        case '1':
            // por como pasa arriba por obios motivos, lo tuve que separa en casos expeficicos porque los nombres en letras son diferentes.
            if ($valor == 10) {
                $principal = "Diez";
            }elseif ($valor == 11) {
                $principal = "Once";
            }elseif ($valor == 12) {
                $principal = "Doce";
            }elseif ($valor == 13) {
                $principal = 'Trece';
            }elseif ($valor == 14) {
                $principal = 'Catorce';
            }elseif ($valor == 15) {
                $principal = 'Quince';
            // Fin, esto es por obios motivos, lo tuve que separa en casos expeficicos porque los nombres en letras son diferentes.
            }else{//sino como todo es normal con los valores que no tienes nombres diferentes
                $principal = "Dieci".strtolower(trim(substr($digito[1], 1)));
            }
            break;
        case '0'://aqui si le asigna a $principal el valor siguiente en el arreglo
            $principal = trim(substr($digito[1], 1));
            break;
        // por como pasa arriba por obios motivos, lo tuve que separa en casos expeficicos porque los nombres en letras son diferentes.
        case '2':
            if ($valor == 20) {
                $principal = "Veinte";
            }else{
                $principal = "Veinti".strtolower(trim(substr($digito[1], 1)));
            }
            break;
        case '3':
            if ($valor == 30) {
                $principal = "Treinta";
            }else{
                $principal = "Treinta y ".strtolower(trim(substr($digito[1], 1)));
            }
            break;
        case '4':
            if ($valor == 40) {
                $principal = "Cuarenta";
            }else{
                $principal = "Cuarenta y ".strtolower(trim(substr($digito[1], 1)));
            }
            break;
        case '5':
            if ($valor == 50) {
                $principal = "Cincuenta";
            }else{
                $principal = "Cincuenta y ".strtolower(trim(substr($digito[1], 1)));
            }
            break;
        case '6':
            if ($valor == 60) {
                $principal = "Sesenta";
            }else{
                $principal = "Sesenta y ".strtolower(trim(substr($digito[1], 1)));
            }
            break;
        case '7':
            if ($valor == 70) {
                $principal = "Setenta";
            }else{
                $principal = "Sesenta y ".strtolower(trim(substr($digito[1], 1)));
            }
            break;
        case '8':
            if ($valor == 80) {
                $principal = "Ochenta";
            }else{
                $principal = "Ochenta y ".strtolower(trim(substr($digito[1], 1)));
            }
            break;
        case '9':
            if ($valor == 90) {
                $principal = "Noventa";
            }else{
                $principal = "Noventa y ".strtolower(trim(substr($digito[1], 1)));
            }
            break;
        // Fin, esto es por obios motivos, lo tuve que separa en casos expeficicos porque los nombres en letras son diferentes.
        default:
            $principal = "Valor no encontrado";
            break;
    }
    return $principal;
}
function letras($digito)// Función donde todo se le asigna el valor en letra y numero.
{
    switch ($digito) {
        case '1':
            return "1 Uno";
            break;
        case '2':
            return "2 Dos";
            break;
        case '3':
            return "3 Tres";
            break;
        case '4':
            return "4 Cuatro";
            break;
        case '5':
            return "5 Cinco";
            break;
        case '6':
            return "6 Seis";
            break;
        case '7':
            return "7 Siete";
            break;
        case '8':
            return "8 Ocho";
            break;
        case '9':
            return "9 Nueve";
            break;
        case '0':
            return "0 Cero";
            break;
        default:
            return "No es Nigún Número";
            break;
    }
}

function getCorrectTaxID($materiaid, $actividadid, $db) {
    $query = 'SELECT * FROM tbl_materias WHERE col_id="'.$materiaid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $materiaData = $sth->fetch(PDO::FETCH_OBJ);

    $materiaClave = strtoupper(($materiaData->col_clave));

    $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataActividad = $sth->fetch(PDO::FETCH_OBJ);
    $maestroid = $dataActividad->col_created_by;

    $periodoIDorIDS = unserialize(stripslashes($dataActividad->col_visible_excepto));
    if(intval($periodoIDorIDS) > 0) {
        // $periodoData = getPeriodo(intval($periodoIDorIDS), $db, false);
        // $periodoNombre = fixEncode($periodoData->col_nombre);
        // $periodoGrupo = $periodoData->col_grado.'-'.$periodoData->col_grupo;
        $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_materia_clave="'.$materiaClave.'" AND col_maestroid="'.$maestroid.'" AND col_periodoid="'.intval($periodoIDorIDS).'"';
        $sth = $db->prepare($query);
        $sth->execute();
        $taxData = $sth->fetch(PDO::FETCH_OBJ);
        $taxIDS[] = $taxData->col_id;
    }else{
        $_arrayVD = explode('|', $periodoIDorIDS);
        $_periodosIDS = explode(',', $_arrayVD[2]);
        // $periodoData = getPeriodo(intval($_periodosVD[0]), $this->db, false);

        $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_materia_clave LIKE "'.claveMateria($materiaClave).'%" AND col_maestroid="'.$dataActividad->col_created_by.'" AND col_periodoid IN ('.implode(',', $_periodosIDS).')';
        $sth = $db->prepare($query);
        $sth->execute();
        $taxs = $sth->fetchAll();
        foreach($taxs as $_tax){
            $taxIDS[] = $_tax['col_id'];
        }

    }

    return $taxIDS;
}

function getMaxTotalParticipaciones($query, $db) {
    // getAlumnosByTaxMateria()
    $sth = $db->prepare($query);
    $sth->execute();
    $asistencias = $sth->fetchAll();
    foreach($asistencias as $item) {
        $query = 'SELECT * FROM tbl_asistencia_alumnos WHERE  col_listaid="'.$item['col_id'].'"';
        $sth = $db->prepare($query);
        $sth->execute();
        if($sth->rowCount()){
            $data = $sth->fetchAll();
            foreach($data as $row) {
                $alumnos[$row['col_alumnoid']] = intval($alumnos[$row['col_alumnoid']]) + $row['col_participacion'];
            }
        }
    }

    $max = 0;
    if(count($alumnos) == 0) return $max;
    foreach($alumnos as $k => $v){
        if($v > $max) $max = $v;
    }


    return $max;
}

function getTotalParticipaciones($alumnoid, $materiaid, $actividadid, $db, $fechaLimite = ''){
    $participaciones = 0;

    $query = 'SELECT * FROM tbl_actividades WHERE col_id="'.$actividadid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataActividad = $sth->fetch(PDO::FETCH_OBJ);
    $maestroid = $dataActividad->col_created_by;

    $taxIDS = getCorrectTaxID($materiaid, $actividadid, $db);
    $visibleData = unserialize(stripslashes($dataActividad->col_visible_excepto));
    $elPeriodoID = 0;
    if(intval($visibleData) > 0) {
        $periodoData = getPeriodo(intval($visibleData), $db, false);
        $elPeriodoID = intval($visibleData);
    }else{
        $_arrayVD = explode('|', $visibleData);
        $_periodosVD = explode(',', $_arrayVD[2]);
        $periodoData = getPeriodo(intval($_periodosVD[0]), $db, false);
        $elPeriodoID = $_periodosVD[0];
    }
    // 5: 1 Parcial
    // 6: 2 Parcial
    // 7: Final
    if($dataActividad->col_tipo == 5) {
        // Extraida de configuracion
        $fechaInicio = $periodoData->col_fecha_inicio;
        $fechaFin = $dataActividad->col_fecha_inicio;
    }
    if($dataActividad->col_tipo == 6) {
        // Extraida primer parcial
        $query = 'SELECT * FROM tbl_actividades WHERE col_visible_excepto="'.addslashes($dataActividad->col_visible_excepto).'" AND col_tipo="5" AND col_created_by="'.$dataActividad->col_created_by.'" ORDER BY col_fecha_inicio DESC LIMIT 1 ';
        $sth = $db->prepare($query);
        $sth->execute();
        $dataActividadPrimerParcial = $sth->fetch(PDO::FETCH_OBJ);
        $fechaInicio = $dataActividadPrimerParcial->col_fecha_inicio;
        $fechaFin = $dataActividad->col_fecha_inicio;

    }

    $totales['total'] = 0;
    $totales['suma'] = 0;
    if($fechaInicio != '' && $fechaFin != '' && $maestroid != '' && is_array($taxIDS)) {

        $query = 'SELECT * FROM tbl_asistencia WHERE col_fecha>="'.$fechaInicio.'" AND col_fecha<="'.$fechaFin.'" AND col_maestroid="'.$maestroid.'" AND col_materiaid IN ('.implode(',', $taxIDS).')';
        $maxParticipaciones = getMaxTotalParticipaciones($query, $db);
        $sth = $db->prepare($query);
        $sth->execute();
        $asistencias = $sth->fetchAll();
        foreach($asistencias as $item){
            $totales['total'] = intval($totales['total']) + 1;
            $query = 'SELECT * FROM tbl_asistencia_alumnos WHERE col_alumnoid="'.$alumnoid.'" AND col_listaid="'.$item['col_id'].'"';
            $sth = $db->prepare($query);
            $sth->execute();
            if($sth->rowCount()){
                $dataAlumno = $sth->fetch(PDO::FETCH_OBJ);
                $totales['suma'] = intval($totales['suma']) + $dataAlumno->col_participacion;
                // $asIDS[] = $item['col_id'];
            }
        }

    }

    // if(intval($totales['suma']) > 0) $totales['suma'] = $totales['total'];
    $totales['max'] = $maxParticipaciones;
    $totales['fechaInicio'] = $fechaInicio;
    $totales['fechaFin'] = $fechaFin;
    // return implode(',', $asIDS);
    return $totales;
}

function formatPonderacionPercent($value) {
    $value = trim($value);
    if ($value == '' OR !is_numeric($value)) return '';
    if($value == intval($value)){
        // return number_format($value, 2);
        return $value;
    }else{
        return number_format($value, 2);
    }
}

function removePorcentaje($v){
    $v = trim(str_replace('%', '', $v));
    return intval($v);
}

function getMaestroByClaveMateria($clave, $alumnoid, $db){
    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataAlumno = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_maestros_taxonomia WHERE col_materia_clave LIKE "%'.claveMateria($clave).'%" AND col_periodoid="'.$dataAlumno->col_periodoid.'" LIMIT 1';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataTax = $sth->fetch(PDO::FETCH_OBJ);

    $query = 'SELECT * FROM tbl_users WHERE col_id="'.$dataTax->col_maestroid.'" AND col_maestro=1';
    $sth = $db->prepare($query);
    $sth->execute();
    $dataMaestro = $sth->fetch(PDO::FETCH_OBJ);

    return fixEncode($dataMaestro->col_firstname.' '.$dataMaestro->col_lastname);
}

function pdfHeader($titulo = '', $width = '180') {
    ob_start();
    ?>
    <table border="0" width="100%">
        <tr>
            <td width="25%">
                <img src="<?php echo getLogo(); ?>" style="max-width: <?php echo $width; ?>px;height:auto;" alt="FLDCH" border="0"/>
            </td>
            <td width="50%" align="center"><?php echo $titulo; ?></td>
            <td width="25%"></td>
        </tr>
    </table>
    <?php
    $header = ob_get_contents();
    ob_end_clean();
    return $header;
}

function pdfFooter() {
    ob_start();
    ?>
    <table border="0" width="100%">
        <tr>
            <td valign="top" width="70%"><small><b>Formando a los mejores Profesionistas del Sureste   <u>www.fldch.edu.mx</u></b><br/>5a. Poniente Norte #633 Col. Centro Tuxtla Gutiérrez, Chiapas. CP.29000<br/>Tel. (961) 61 2 60 89 ó (961) 61 3 55 42</small></td>
            <td valign="top" width="30%" align="right"><small><p>Pag. {PAGENO}</p></small></td>
        </tr>
    </table>
    <?php
    $footer = ob_get_contents();
    ob_end_clean();
    return $footer;
}

function pdfCSS($fontSize = '13px', $bodyfontSize = '13px') {
    ob_start();
    ?>
    body {
        font-family: Arial, Tahoma, Sans Serif;
        font-size: <?php echo $bodyfontSize; ?> !important;
    }

    .cursiva {
        font-family: 'dancingscript', cursive;
        font-size: 20px;
    }

    .serif {
        font-family: 'Times', Serif;
    }


    .certificado {
        font-family: 'Tahoma', Sans Serif;
    }

    .papyrus {
        font-family: 'papyrus', cursive;
    }

    .papyrusBig {
        font-family: 'papyrus', cursive;
        font-size: 16px !important;
    }

    p.papyrusBig {
        line-height: 130%;
    }

    p.papyrus {
        line-height: 150%;
    }

    .lucida {
        font-family: 'lucida', cursive;
    }

    p {
        margin-bottom: 15px;
        text-align: justify;
        font-size: <?php echo $fontSize; ?> !important;
    }

    td {
        font-size: <?php echo $fontSize; ?> !important;
    }

    ul li, ol li{
        text-align: justify;
    }


    table.bordered {
        border-collapse: collapse;
        border: 1px solid #222222;
    }

    table.bordered td,
    table.bordered th {
        border-collapse: collapse;
        border: 1px solid #222222;
        padding: 1px 5px;
        font-size: 10px;
        line-height: 100%;
    }

    table.bordered td.noborder{
        border: 0;
    }

    table.bordered td.bordersides{
        border: 0;
        border-right: 1px solid #222222;
        border-left: 1px solid #222222;
    }

    table tr.heightCali td{
        height: 30px;
        padding: 1px 2px 0 2px;
        font-size: 6.8pt;
    }

    table tr.heightCali2 td{
        height: 25px;
        padding: 1px 2px 0 2px;
        font-size: 6.8pt;
    }

    table tr.heightCali2 td{
        height: auto;
        padding: 1px 2px 0 2px;
        font-size: 6.8pt;
    }

    .forzarCarta p{
        margin: 0 0 5px !important;
        text-align: justify;
        line-height: 110% !important;
        font-size: <?php echo $fontSize; ?> !important;
    }

    .tablasFlotantes{
        width:50%;
        float:left;
        display: block;
        padding: 0;
        margin: 0;
    }

    .tablasFlotantes table.bordered {
        border-collapse: collapse;
        border: 1px solid #222222;
    }

    .tablasFlotantes table.bordered td,
    .tablasFlotantes table.bordered th {
        border-collapse: collapse;
        border: 1px solid #222222;
        padding: 1px 3px;
    }

    .tablasFlotantes table.bordered td.noborder{
        border: 0;
    }

    .tablasFlotantes table.bordered td.bordersides{
        border: 0;
        border-right: 1px solid #222222;
        border-left: 1px solid #222222;
    }

    .tablasFlotantes table.no-bordered {
        border-collapse: collapse;
        border: 1px solid #222222;
    }

    .tablasFlotantes table.no-bordered th {
        border-collapse: collapse;
        border-width: 0 1px;
        border-style: solid;
        border-color: #222222;
        padding: 0 3px 1px;
    }

    .tablasFlotantes table.no-bordered td {
        border-collapse: collapse;
        border-width: 0 1px;
        border-style: solid;
        border-color: #222222;
        padding: 1px 3px;
    }

    .txtCenter {text-align: center;}

    .tablasFlotantes .tablaCalificaciones th{
        font-size: 9px;
    }

    .tablasFlotantes .tablaCalificaciones td{
        font-size: 10px;
    }

    .tablasFlotantes .tablaDocumentos {
        width: 100%;
        display: block;
    }

    .tablasFlotantes .tablaDocumentos th{
        font-size: 11px;
    }

    .tablasFlotantes .tablaDocumentos td{
        font-size: 11px;
    }


    .tablasFlotantes .tablaCalificaciones td.1lines {height: 14px;}
    .tablasFlotantes .tablaCalificaciones td.2lines {height: 25px;}
    .tablasFlotantes .tablaCalificaciones td.3lines {height: 35px;}

    .tablasFlotantes .tablaCalificaciones td.col50 {width: 35px;}
    .tablasFlotantes .tablaCalificaciones td.col70 {width: 50px;}


    .tablasFlotantes .crossedTable{
        background: url(<?php echo getLogo('diagonal2'); ?>) no-repeat;
        background-size: cover;
        background-position: 0 50%;
    }


    table.basica {
        border-collapse: collapse;
        border: 1px solid #222222;
    }

    table.basica thead tr {
        background-color: #CCCCCC;
        font-weight: bold;
        border-collapse: collapse;
        border: 1px solid #222222;
    }

    table.basica tbody.grid td {
        border-left: 1px solid #222222;
        border-right: 1px solid #222222;
    }

    table.basica tbody.grid tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    table.basica tbody.grid tr:nth-child(odd) {
        background-color: #ffffff;
    }

    table.basica thead th,
    table.basica tbody td {
        padding: 2px 5px;
    }

    table td.firma_up {
        border-top: 1px solid #222;
    }

    table.infoCalificaciones {
        pading: 5px;
        border-collapse: collapse;
    }

    table.infoCalificaciones td {
        padding: 5px;
        border: 1px solid #cccccc;
        font-size: 10px;
        margin: 0;
    }
    table.infoCalificaciones td.nocollapse {
        padding: 0;
    }

    table.infoCalificaciones td table {
        pading: 5px;
        border-collapse: collapse;
    }

    table.infoCalificaciones td table td {
        padding: 2px 5px;
        border-bottom: 1px solid #cccccc;
        font-size: 10px;
        margin: 0;
    }

    table.listaCalificaciones {
        border: 1px solid #222;
        pading: 5px;
        border-collapse: collapse;
    }

    table.listaCalificaciones th {
        background-color: #f2f2f2;
        padding: 12px 5px;
        border: 1px solid #222;
        margin: 0;
        font-size: 10px;
    }

    table.listaCalificaciones td {
        padding: 5px;
        border: 1px solid #cccccc;
        margin: 0;
        font-size: 10px;
    }

    td.titulo {
        color: #222;
        text-align: center;
        font-weight: bold;
        font-size: 20px;
    }

    td.subtitulo {
        color: #222;
        text-align: center;
        font-weight: bold;
    }

    table.headerTabla td {
        font-size: 10px;
    }

    table td.fill_line {
        border-bottom: 1px solid #222;
    }


    .box_genre{
        border: 1px solid #ccc;
    }

    .box_genre_checked {
        border: 2px solid #222;
    }

    .sobreFondoAzul {
        position: static;
        z-index: 999999;
    }

    .fondoAzul {
        position: absolute;
        left: 220px;
        top: 170px;
        width: 370px;
        height: 600px;
        z-index: 0;
        background: url(<?php echo getLogo('fondo_azul'); ?>) repeat-x;
        background-size: 0 100%;
    }

    .lateralText{
        position: absolute; top: 130mm; left: 2mm; rotate: -90;
        text-align: center;font-size: 10px;
    }

    .certificadoFolio {
        position: absolute; top: 20mm; right: 9mm;
    }

    .crossed{
        background: #ffcc00;
        height: 210px;
        background: url(<?php echo getLogo('diagonal'); ?>) no-repeat 50%;
    }

    .crossed2{
        background: #ffcc00;
        height: 175px;
        background: url(<?php echo getLogo('diagonal'); ?>) no-repeat 50%;
    }

    span.underlined {
        border-bottom: 1px solid #000000;
        display: inline-block;
    }

    span.underlinedRed {
        border-bottom: 1px solid #cc0000;
        display: inline-block;
    }

    <?php
    $stylesheet = ob_get_contents();
    ob_end_clean();
    return $stylesheet;
}

function fontBold($txt){
    return "<span style='font-weight:bold;'>".$txt."</span>";
}

function fixSpaces($arr) {
    foreach($arr as $k => $v){
        if(is_array($v)) {
            $_arr[$k] = fixSpaces($v);
        }else{
            $_arr[$k] = str_replace('  ', '&nbsp;&nbsp;', $v);
        }
    }

    return $_arr;
}

function getSpaces($n){
    $txt = '';
    for($i = 0; $i < $n; $i++){
        $txt .= '&nbsp;&nbsp;';
    }
    return $txt;
}

function undelinedCSS($txt, $size = '5') {
    return '<span class="underlined">'.getSpaces($size).fixEncode($txt).getSpaces($size).'</span>';
}

function undelined($txt, $left = '&nbsp;&nbsp;', $right = '&nbsp;&nbsp;') {
    return '<span class="underlined">'.$left.fixEncode($txt).$right.'</span>';
}

function undelinedRed($txt, $left = '&nbsp;&nbsp;', $right = '&nbsp;&nbsp;') {
    return '<span class="underlinedRed">'.$left.fixEncode($txt).$right.'</span>';
}

function heightSize($strlen) {
    //if($strlen > 106) return '3lines';
    if($strlen > 79) return '3lines';
    if($strlen > 44) return '2lines';
    return '1lines';
}

function generarBoleta($alumnoid, $periodoid, $db, $filename = 'descarga-fldch', $action = 'I', $watermark = ''){
    $periodoData = getPeriodo($periodoid, $db, false);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $periodoDataAlumno = getPeriodo($alumnodData->col_periodoid, $db, false);
    $carreraData = getCarrera($alumnodData->col_carrera, $db);

    // $query = 'SELECT * FROM tbl_calificaciones WHERE (col_materia_clave NOT LIKE "%AC%" AND col_materia_clave NOT LIKE "%TL%" AND col_materia_clave NOT LIKE "%CL%" AND col_materia_clave NOT LIKE "%TR%") AND col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$periodoDataAlumno->col_id.'" AND col_groupid="'.$periodoDataAlumno->col_groupid.'" ORDER BY col_materia_clave ASC';
    $query = 'SELECT * FROM tbl_calificaciones WHERE (col_materia_clave NOT LIKE "%AC%" AND col_materia_clave NOT LIKE "%TL%" AND col_materia_clave NOT LIKE "%CL%" AND col_materia_clave NOT LIKE "%TR%") AND col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$periodoData->col_id.'" AND col_groupid="'.$periodoData->col_groupid.'" ORDER BY col_materia_clave ASC';
    $sth = $db->prepare($query);
    $sth->execute();
    $calificacionesData = $sth->fetchAll();

    if(!$calificacionesData){
        $query = 'SELECT * FROM tbl_calificaciones WHERE (col_materia_clave NOT LIKE "%AC%" AND col_materia_clave NOT LIKE "%TL%" AND col_materia_clave NOT LIKE "%CL%" AND col_materia_clave NOT LIKE "%TR%") AND col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$periodoData->col_id.'" AND col_groupid="'.$periodoData->col_groupid.'" ORDER BY col_materia_clave ASC';
        $sth = $db->prepare($query);
        $sth->execute();
        $calificacionesData = $sth->fetchAll();
    }
    // 5: Examen Parcial 1
    // 6: Examen Parcial 2
    // 7: Examen Final

    ob_start();
    ?>
    <?php if($carreraData['modalidad'] == 'Maestría' OR $carreraData['modalidad'] == 'Doctorado'){ ?>
        <table width="100%" class="listaCalificaciones">
            <thead>
                <tr>
                    <th align="left" rowspan="2" style="width: 350px;">MATERIA</th>
                    <th align="center" colspan="2">CALIFICACIÓN</th>
                    <th align="center" rowspan="2">OBSERVACIONES</th>
                </tr>
                <tr>
                    <th align="center" width="12%">NUMERO</th>
                    <th align="center" width="12%">LETRA</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $sumCF = 0;

                    foreach($calificacionesData as $item){
                        $item['col_materia_clave'] = strtoupper($item['col_materia_clave']);
                        if(strtoupper(substr($item['col_materia_clave'], 0, 3)) == 'LDO'){
                            $item['col_materia_clave'] = str_replace('O', '0', $item['col_materia_clave']);
                        }
                        // $queryMateria = 'SELECT * FROM tbl_materias WHERE col_clave="'.$item['col_materia_clave'].'" ';
                        // $sth = $db->prepare($queryMateria);
                        // $sth->execute();
                        // $materiaData = $sth->fetch(PDO::FETCH_OBJ);

                        $materiaData = getMateria('col_clave', $item['col_materia_clave'], $db, $periodoData->col_id, $alumnodData->col_carrera);

                        $sumP1 = $sumP1 + $item['col_p1'];
                        $sumP2 = $sumP2 + $item['col_p2'];
                        $sumEF = $sumEF + $item['col_ef'];
                        $sumCF = $sumCF + $item['col_cf'];
                        $sumEXT = $sumEXT + $item['col_ext'];
                        $sumTS = $sumTS + $item['col_ts'];
                        $noMaterias++;

                        ?>
                        <tr>
                            <td><?php echo fixEncode($materiaData->col_nombre); ?></td>
                            <td align="center"><?php echo $item['col_cf']; ?></td>
                            <td align="center"><?php
                                if($item['col_cf'] == 'NP') {
                                    echo 'NO PRESENTO';
                                }else{
                                    echo numerosaletras(intval($item['col_cf']));
                                }
                            ?></td>
                            <td align="left"><?php echo fixEncode($item['col_observaciones']); ?></td>
                        </tr>
                        <?php


                    }

                ?>
                        <tr>
                            <td align="right"><b>PROMEDIO GENERAL</b></td>
                            <td align="center"><?php echo (intval($sumCF) > 0?formatoPromedio(($sumCF / $noMaterias)):''); ?></td>
                            <td align="center"><?php echo numerosaletras(intval($sumCF) > 0?formatoPromedio(($sumCF / $noMaterias)):''); ?></td>
                            <td></td>
                        </tr>
            </tbody>
        </table>
        <table width="100%" class="listaCalificaciones">
            <tr>
                <td width="50%" align="center"><br/><br/>________________________________<br/>CONTROL ESCOLAR</td>
                <td width="50%" align="center"><br/><br/>________________________________<br/>COORDINACIÓN</td>
            </tr>
        </table>
        <br/><br/>
    <?php }else{ ?>
        <table width="100%" class="listaCalificaciones">
            <thead>
                <tr>
                    <th align="left">MATERIAS CURRICULARES</th>
                    <th align="center" width="5%">P1</th>
                    <?php if($carreraData['modalidad'] == 'Semestral'){ ?>
                    <th align="center" width="5%">P2</th>
                    <?php } ?>
                    <th align="center" width="5%">EF</th>
                    <th align="center" width="5%">CF</th>
                    <th align="center" width="5%">EX</th>
                    <th align="center" width="5%">TS</th>
                    <th align="center">OBSERVACIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $noMaterias = 0;
                    $sumPromedio = 0;
                    $sumP1 = 0;
                    $sumP2 = 0;
                    $sumEF = 0;
                    $sumCF = 0;
                    $sumEXT = 0;
                    $sumTS = 0;

                    foreach($calificacionesData as $item){
                        $item['col_materia_clave'] = strtoupper($item['col_materia_clave']);
                        if(strtoupper(substr($item['col_materia_clave'], 0, 3)) == 'LDO'){
                            $item['col_materia_clave'] = str_replace('O', '0', $item['col_materia_clave']);
                        }
                        $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.$item['col_materia_clave'].'" ';
                        $sth = $db->prepare($query);
                        $sth->execute();
                        $materiaData = $sth->fetch(PDO::FETCH_OBJ);

                        $sumP1 = $sumP1 + $item['col_p1'];
                        $sumP2 = $sumP2 + $item['col_p2'];
                        $sumEF = $sumEF + $item['col_ef'];
                        $sumCF = $sumCF + $item['col_cf'];
                        $sumEXT = $sumEXT + $item['col_ext'];
                        $sumTS = $sumTS + $item['col_ts'];
                        if(floatval($item['col_ts']) > 0){
                            $sumPromedio = $sumPromedio + $item['col_ts'];
                        }else if(floatval($item['col_ext']) > 0){
                            $sumPromedio = $sumPromedio + $item['col_ext'];
                        }else{
                            $sumPromedio = $sumPromedio + $item['col_cf'];
                        }
                        $noMaterias++;

                        ?>
                        <tr>
                            <td><?php echo fixEncode($materiaData->col_nombre); ?></td>
                            <td align="center"><?php echo (intval($item['col_p1']) == 10?intval($item['col_p1']):$item['col_p1']); ?></td>
                            <?php if($carreraData['modalidad'] == 'Semestral'){ ?>
                            <td align="center"><?php echo (intval($item['col_p2']) == 10?intval($item['col_p2']):$item['col_p2']); ?></td>
                            <?php } ?>
                            <td align="center"><?php echo (intval($item['col_ef']) == 10?intval($item['col_ef']):$item['col_ef']); ?></td>
                            <td align="center"><?php echo (intval($item['col_cf']) == 10?intval($item['col_cf']):$item['col_cf']); ?></td>
                            <td align="center"><?php echo (intval($item['col_ext']) == 10?intval($item['col_ext']):$item['col_ext']); ?></td>
                            <td align="center"><?php echo (intval($item['col_ts']) == 10?intval($item['col_ts']):$item['col_ts']); ?></td>
                            <td align="left"><?php echo fixEncode($item['col_observaciones']); ?></td>
                        </tr>
                        <?php


                    }

                ?>
                        <tr>
                            <td align="right"><b>PROMEDIO GENERAL</b></td>
                            <td align="center">
                                <?php
                                    if(intval($sumP1) > 0) {
                                        $_p1Materias = numberFormat(($sumP1 / $noMaterias), 1);
                                        if(intval($_p1Materias) == 10) {
                                            echo '<b>10</b>';
                                        } else {
                                            echo '<b>'.$_p1Materias.'</b>';
                                        }
                                    }
                                ?>
                            </td>
                            <?php if($carreraData['modalidad'] == 'Semestral'){ ?>
                                <td align="center">
                                    <?php
                                    if(intval($sumP2) > 0) {
                                        $_p2Materias = numberFormat(($sumP2 / $noMaterias), 1);
                                        if(intval($_p2Materias) == 10) {
                                            echo '<b>10</b>';
                                        } else {
                                            echo '<b>'.$_p2Materias.'</b>';
                                        }
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                            <td align="center">
                                <?php
                                    if(intval($sumEF) > 0) {
                                        $_efMaterias = numberFormat(($sumEF / $noMaterias), 1);
                                        if(intval($_efMaterias) == 10) {
                                            echo '<b>10</b>';
                                        } else {
                                            echo '<b>'.$_efMaterias.'</b>';
                                        }
                                    }
                                ?>
                            </td>
                            <td align="center" style="font-size:1em;background-color: #f2f2f2;">
                                <?php
                                if(intval($sumPromedio) > 0) {
                                    $_promedioMaterias = numberFormat(($sumPromedio / $noMaterias), 1);
                                    if(intval($_promedioMaterias) == 10) {
                                        echo '<b>10</b>';
                                    } else {
                                        echo '<b>'.$_promedioMaterias.'</b>';
                                    }
                                }
                                ?>
                            </td>
                            <td align="center"></td>
                            <td align="center"></td>
                            <!--
                            <td align="center"><?php echo (intval($sumEXT) > 0?number_format(($sumEXT / $noMaterias), 1):''); ?></td>
                            <td align="center"><?php echo (intval($sumTS) > 0?number_format(($sumTS / $noMaterias), 1):''); ?></td>
                            -->


                            <td></td>
                        </tr>
            </tbody>
        </table>
        <table width="100%" class="listaCalificaciones">
            <tr>
                <td width="50%" align="center"><br/><br/>________________________________<br/>CONTROL ESCOLAR</td>
                <td width="50%" align="center"><br/><br/>________________________________<br/>COORDINACIÓN</td>
            </tr>
        </table>
        <br/><br/>
    <?php } ?>
    <?php if($carreraData['modalidad'] == 'Semestral'){ ?>
    <table width="100%" class="listaCalificaciones">
        <thead>
            <tr>
                <th align="left">MODELO EDUCATIVO</th>
                <th align="center" width="5%">P1</th>
                <th align="center" width="5%">P2</th>
                <th align="left">MODELO EDUCATIVO</th>
                <th align="center" width="8%">FINAL</th>
            </tr>
        </thead>
        <tbody>
            <?php
                // $query = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave LIKE "%AC%" AND col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$periodoData->col_id.'" AND col_groupid="'.$periodoData->col_groupid.'" ';
                $query = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave LIKE "%AC%" AND col_alumnoid="'.$alumnoid.'" AND col_groupid="'.$periodoDataAlumno->col_groupid.'" ';
                $sth = $db->prepare($query);
                $sth->execute();
                $row = $sth->fetch(PDO::FETCH_OBJ);
            ?>
            <tr>
                <td>ACADEMIA</td>
                <td align="center"><?php echo ($row->col_p1 != ''?($row->col_p1 >= 7?'A':'NA'):''); ?></td>
                <td align="center"><?php echo ($row->col_p2 != ''?($row->col_p2 >= 7?'A':'NA'):''); ?></td>
                <td>ACADEMIA</td>
                <td align="center"><?php echo ($row->col_cf != ''?($row->col_cf >= 7?'A':'NA'):''); ?></td>
            </tr>
            <?php
                // $query = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave LIKE "%TL%" AND col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$periodoData->col_id.'" AND col_groupid="'.$periodoData->col_groupid.'" ';
                $query = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave LIKE "%TL%" AND col_alumnoid="'.$alumnoid.'" AND col_groupid="'.$periodoDataAlumno->col_groupid.'" ';
                $sth = $db->prepare($query);
                $sth->execute();
                $row = $sth->fetch(PDO::FETCH_OBJ);
            ?>
            <tr>
                <td>TALLER</td>
                <td align="center"><?php echo ($row->col_p1 != ''?($row->col_p1 >= 7?'A':'NA'):''); ?></td>
                <td align="center"><?php echo ($row->col_p2 != ''?($row->col_p2 >= 7?'A':'NA'):''); ?></td>
                <td></td>
                <td align="center"></td>
            </tr>
            <?php
                $query = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave LIKE "%CL%" AND col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$periodoDataAlumno->col_id.'" AND col_groupid="'.$periodoDataAlumno->col_groupid.'" ';
                $sth = $db->prepare($query);
                $sth->execute();
                $row = $sth->fetch(PDO::FETCH_OBJ);
            ?>
            <tr>
                <td>CLUB DE LECTURA</td>
                <td align="center"><?php echo ($row->col_p1 != ''?($row->col_p1 >= 7?'A':'NA'):''); ?></td>
                <td align="center"><?php echo ($row->col_p2 != ''?($row->col_p2 >= 7?'A':'NA'):''); ?></td>
                <td></td>
                <td align="center"></td>
            </tr>
            <?php
                $query = 'SELECT * FROM tbl_calificaciones WHERE col_materia_clave LIKE "%TR%" AND col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$periodoDataAlumno->col_id.'" AND col_groupid="'.$periodoDataAlumno->col_groupid.'" ';
                $sth = $db->prepare($query);
                $sth->execute();
                $row = $sth->fetch(PDO::FETCH_OBJ);
            ?>
            <tr>
                <td>TRANSVERSAL</td>
                <td align="center"><?php echo ($row->col_p1 != ''?($row->col_p1 >= 7?'A':'NA'):''); ?></td>
                <td align="center"><?php echo ($row->col_p2 != ''?($row->col_p2 >= 7?'A':'NA'):''); ?></td>
                <td></td>
                <td align="center"></td>
            </tr>
            <tr>
                <td colspan="5">
                    <b>RUBROS DE CALIFICACIONES: A: ACREDITADO Y NA: NO ACREDITADO</b> (Los parciales son independientes)
                </td>
            </tr>
        </tbody>
    </table>
    <br/><br/>
    <?php } ?>

    <?php if($carreraData['modalidad'] != 'Maestría' AND $carreraData['modalidad'] != 'Doctorado'){ ?>
    <table width="100%" class="infoCalificaciones">
        <tr>
            <td colspan="6" align="center"><b>INDICADORES</b></td>
        </tr>
        <tr>
            <td align="center"><b>P1:</b> 1º Parcial</td>
            <?php if($carreraData['modalidad'] == 'Semestral'){ ?>
            <td align="center"><b>P2:</b> 2º Parcial</td>
            <?php } ?>
            <td align="center"><b>EX:</b> Examen Final</td>
            <td align="center"><b>CF:</b> Calificación Final</td>
            <td align="center"><b>EX:</b> Examen extraordinario</td>
            <td align="center"><b>TS:</b> Titulo de suficiencia</td>
        </tr>
    </table><br/>
    <?php } ?>
    <?php if($carreraData['modalidad'] == 'Semestral'){ ?>
    <table width="100%" class="infoCalificaciones">
        <tr>
            <td colspan="2" align="center"><b>REQUISITOS PARA PRESENTAR EXÁMENES</b></td>
        </tr>
        <tr>
            <td width="50%" align="center">1º y 2º PARCIAL (No Acreditar)</td>
            <td width="50%" align="center">FINAL (No Acreditar)</td>
        </tr>
        <tr>
            <td width="50%" valign="top" class="nocollapse">
                <table width="100%">
                    <tr>
                        <td width="50%"><b>Academias de Investigación</b></td>
                        <td width="50%">Sin derecho al 50% del valor del 1er examen programado.</td>
                    </tr>
                    <tr>
                        <td width="50%"><b>Talleres</b></td>
                        <td width="50%">Sin derecho al 50% del valor del 2do examen programado.</td>
                    </tr>
                    <tr>
                        <td width="50%"><b>Club de lectura</b></td>
                        <td width="50%">Sin derecho al 50% del valor del 3er examen programado.</td>
                    </tr>
                    <tr>
                        <td width="50%"><b>Materia transversal</b></td>
                        <td width="50%">Sin derecho al 50% del valor del 4to examen programado.</td>
                    </tr>
                    <?php if($periodoData->col_grado < 8){ ?>
                    <tr>
                        <td width="50%"><b>Prácticas Profesionales</b></td>
                        <td width="50%">Sin derecho al 50% del valor del 5to examen programado.</td>
                    </tr>
                    <?php } ?>
                </table>
            </td>
            <td width="50%" valign="top" class="nocollapse">
                <table width="100%">
                    <tr>
                        <td width="50%"><b>Academias de Investigación</b></td>
                        <td width="50%">Sin derecho al 50% del valor del 1er examen programado.</td>
                    </tr>
                    <tr>
                        <td>&nbsp;<br/>&nbsp;</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td width="50%"><b>Actividad Altruista</b></td>
                        <td width="50%">Sin derecho al 50% del valor del 2do examen programado.</td>
                    </tr>
                    <tr>
                        <td>&nbsp;<br/>&nbsp;</td>
                        <td></td>
                    </tr>
                    <?php if($periodoData->col_grado < 8){ ?>
                    <tr>
                        <td width="50%"><b>Prácticas Profesionales</b></td>
                        <td width="50%">Sin derecho al 50% del valor del 7mo examen programado.</td>
                    </tr>
                    <?php } ?>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2">
            <b>Nota: el No cumplir con el 80% de asistencias en el Modelo Educativo y Materias curriculares</b> = Te deja Sin derecho al 100% del valor del examen en la Materia programada.
            </td>
        </tr>
    </table>
    <?php
    }
    $html = ob_get_contents();
    ob_end_clean();

    ob_start();
    if($carreraData['modalidad'] == 'Maestría'  OR $carreraData['modalidad'] == 'Doctorado'){
        ?>
        <table border="0" width="100%">
            <tr>
                <td width="25%">
                    <img src="<?php echo getLogo(); ?>" style="max-width: 180px;height:auto;" alt="FLDCH" border="0"/>
                </td>
                <td width="25%"></td>
                <td width="50%" align="right" class="titulo">BOLETA DE CALIFICACIONES</td>
            </tr>
        </table>
        <table border="0" width="100%" class="headerTabla">
            <tr>
                <td width="25%"></td>
                <td width="25%"></td>
                <td width="50%" align="right">TUXTLA GUTIÉRREZ, CHIAPAS, <?php echo fechaTextoBoleta(date('Y-m-d')); ?> </td>
            </tr>
        </table>
        <table border="0" width="100%" class="headerTabla">
            <tr>
                <td width="25%"></td>
                <td width="50%" align="center"><b>POSGRADO</b>: <?php echo strtoupper(fixEncode($carreraData['nombre'])); ?></td>
                <td width="25%"></td>
            </tr>
        </table>
        <table border="0" width="100%" class="headerTabla">
            <tr>
                <td width="100%" colspan="2">NOMBRE DEL ALUMNO(A): <?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos); ?></td>
            </tr>
            <tr>
                <td width="60%">No. DE CONTROL: <?php echo $alumnodData->col_control; ?></td>
                <td width="40%" align="right">SEMESTRE: <?php echo $periodoData->col_grado; ?> GRUPO: <?php echo $periodoData->col_grupo; ?></td>
            </tr>
            <tr>
                <td width="60%">GENERACIÓN: <?php echo strtoupper(fixEncode($periodoData->col_nombre)); ?></td>
                <td width="40%"></td>
            </tr>
        </table>
        <?php
    }else{
        ?>
        <table border="0" width="100%">
            <tr>
                <td width="25%">
                    <img src="<?php echo getLogo(); ?>" style="max-width: 180px;height:auto;" alt="FLDCH" border="0"/>
                </td>
                <td width="25%"></td>
                <td width="50%" align="right" class="titulo">BOLETA DE CALIFICACIONES</td>
            </tr>
        </table>
        <table border="0" width="100%" class="headerTabla">
            <tr>
                <td width="25%"></td>
                <td width="25%"></td>
                <td width="50%" align="right">TUXTLA GUTIÉRREZ, CHIAPAS, <?php echo fechaTextoBoleta(date('Y-m-d')); ?> </td>
            </tr>
        </table>
        <table border="0" width="100%" class="headerTabla">
            <tr>
                <td width="60%">NOMBRE DEL ALUMNO(A): <?php echo fixEncode($alumnodData->col_nombres.' '.$alumnodData->col_apellidos); ?></td>
                <td width="40%" align="right">NIVEL: <?php echo strtoupper(fixEncode($carreraData['nombre'])); ?></td>
            </tr>
            <tr>
                <td width="60%">CICLO ESCOLAR: <?php echo strtoupper(fixEncode($periodoData->col_nombre)); ?></td>
                <td width="40%"></td>
            </tr>
            <tr>
                <td width="60%">No. DE CONTROL: <?php echo $alumnodData->col_control; ?></td>
                <td width="40%" align="right">GRADO: <?php echo $periodoData->col_grado; ?>º <?php echo ($carreraData['modalidad'] == 'Semestral'?'SEMESTRE':'CUATRIMESTRE') ?>  GRUPO: <?php echo $periodoData->col_grupo; ?></td>
            </tr>
        </table>
        <?php
    }
    $header = ob_get_contents();
    ob_end_clean();

    ob_start();
    ?>
    <table border="0" width="100%">
        <tr>
            <td valign="top" width="70%"><small><b>Formando a los mejores Profesionistas del Sureste   <u>www.fldch.edu.mx</u></b><br/>5a. Poniente Norte #633 Col. Centro Tuxtla Gutiérrez, Chiapas. CP.29000<br/>Tel. (961) 61 2 60 89 ó (961) 61 3 55 42</small></td>
            <td valign="top" width="30%" align="right">Pag. {PAGENO}</td>
        </tr>
    </table>
    <?php
    $footer = ob_get_contents();
    ob_end_clean();

    include_once(__DIR__ . '/../src/mpdf/mpdf.php');

    $mpdf=new mPDF('c','A4', '','', '8', '8', 50, 30);
    // mPDF($mode='',$format='A4',$default_font_size=0,$default_font='',$mgl=15,$mgr=15,$mgt=16,$mgb=16,$mgh=9,$mgf=9, $orientation='P')
    // $mpdf->showImageErrors = true;
    $mpdf->SetHTMLHeader($header);
    $mpdf->SetHTMLFooter($footer);
    if($watermark != '') {
        $mpdf->showWatermarkText = true;
        $mpdf->SetWatermarkText($watermark, 0.3);
    }



    $mpdf->SetDisplayMode('fullpage');
    $mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list
    // $stylesheet = file_get_contents(ROOT_PATH."/scss/styles.min.css");
    ob_start();
    ?>
    body {
        font-family: Helvetica, Arial, Tahoma, Sans Serif;
        font-size: 12px;
    }

    table.infoCalificaciones {
        pading: 5px;
        border-collapse: collapse;
    }

    table.infoCalificaciones td {
        padding: 5px;
        border: 1px solid #cccccc;
        font-size: 10px;
        margin: 0;
    }
    table.infoCalificaciones td.nocollapse {
        padding: 0;
    }

    table.infoCalificaciones td table {
        pading: 5px;
        border-collapse: collapse;
    }

    table.infoCalificaciones td table td {
        padding: 2px 5px;
        border-bottom: 1px solid #cccccc;
        font-size: 10px;
        margin: 0;
    }

    table.listaCalificaciones {
        border: 1px solid #222;
        pading: 5px;
        border-collapse: collapse;
    }

    table.listaCalificaciones th {
        background-color: #f2f2f2;
        padding: 12px 5px;
        border: 1px solid #222;
        margin: 0;
        font-size: 10px;
    }

    table.listaCalificaciones td {
        padding: 5px;
        border: 1px solid #cccccc;
        margin: 0;
        font-size: 10px;
    }

    td.titulo {
        color: #222;
        text-align: center;
        font-weight: bold;
        font-size: 20px;
    }

    td.subtitulo {
        color: #222;
        text-align: center;
        font-weight: bold;
    }

    table.headerTabla td {
        font-size: 10px;
    }
    <?php
    $stylesheet = ob_get_contents();
    ob_end_clean();

    $mpdf->WriteHTML($stylesheet, 1);
    $mpdf->WriteHTML($html, 2);



    if($action == 'S') {
        return $mpdf->Output($filename.'.pdf', $action);
    }else{
        $mpdf->Output($filename.'.pdf', $action);
    }
}

function numberFormat($num, $dec = 2) {
    $num = explode('.', floatval($num));
    if($num[1] == '') $num[1] = '000000';
    return $num[0].'.'.substr($num[1], 0, $dec);
}


function array_sort($array, $on, $order=SORT_ASC){

    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
                break;
            case SORT_DESC:
                arsort($sortable_array);
                break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

function getAlumnosReprobadosPorActividad($actividadID, $db) {

    $queryActividad = "SELECT * FROM tbl_actividades WHERE col_id='".$actividadID."'";
    $sth = $db->prepare($queryActividad);
    $sth->execute();
    $actividadData = $sth->fetch(PDO::FETCH_OBJ);

    if($actividadData->col_tipo != 8) return array();


    $periodoID = intval(unserialize($actividadData->col_visible_excepto));

    $queryMateria = "SELECT * FROM tbl_materias WHERE col_id='".$actividadData->col_materiaid."'";
    $sth = $db->prepare($queryMateria);
    $sth->execute();
    $materiaData = $sth->fetch(PDO::FETCH_OBJ);

    $queryCalificaciones = "SELECT * FROM tbl_calificaciones WHERE col_periodoid='".$periodoID."' AND col_materia_clave='".$materiaData->col_clave."' AND col_cf<=5";
    $sth = $db->prepare($queryCalificaciones);
    $sth->execute();
    $todos = $sth->fetchAll();
    foreach($todos as $item){
        $alumnos[] = $item['col_alumnoid'];
    }

    return $alumnos;

}

function getAlumnosReprobadosExtPorActividad($actividadID, $db) {

    $queryActividad = "SELECT * FROM tbl_actividades WHERE col_id='".$actividadID."'";
    $sth = $db->prepare($queryActividad);
    $sth->execute();
    $actividadData = $sth->fetch(PDO::FETCH_OBJ);

    if($actividadData->col_tipo != 9) return array();


    $periodoID = intval(unserialize($actividadData->col_visible_excepto));

    $queryMateria = "SELECT * FROM tbl_materias WHERE col_id='".$actividadData->col_materiaid."'";
    $sth = $db->prepare($queryMateria);
    $sth->execute();
    $materiaData = $sth->fetch(PDO::FETCH_OBJ);

    $queryCalificaciones = "SELECT * FROM tbl_calificaciones WHERE col_periodoid='".$periodoID."' AND col_materia_clave='".$materiaData->col_clave."' AND (col_cf<=5 AND col_ext<=5)";
    $sth = $db->prepare($queryCalificaciones);
    $sth->execute();
    $todos = $sth->fetchAll();
    foreach($todos as $item){
        $alumnos[] = $item['col_alumnoid'];
    }

    return $alumnos;

}

function alumnoTieneReprobadas($periodoid, $db, $alumnoid){
    $tieneReprobadas = 0;
    $queryCalificaciones = 'SELECT * FROM tbl_calificaciones WHERE col_periodoid ="'.$periodoid.'" AND col_alumnoid="'.$alumnoid.'"';
    $sth = $db->prepare($queryCalificaciones);
    $sth->execute();
    $todos = $sth->fetchAll();
    foreach($todos as $item){
        if(!in_array(substr(strtoupper($item['col_materia_clave']), 0, 2), array('AC', 'TL', 'CL', 'TR'))) {
            if($item['col_cf'] == 5) {
                if(intval($item['col_ext']) <= 5) {
                    if(intval($item['col_ts']) <= 5) {
                        $tieneReprobadas++;
                    }
                }
            }
        }
    }

    return $tieneReprobadas;
}

function puedeReinscribirse($periodoid, $db, $alumnoid){

    $periodoData = getPeriodo($periodoid, $db, false);
    if($periodoData == 'periodo_invalido') return 0;

    $queryPeriodo = "SELECT * FROM tbl_periodos WHERE col_id>".$periodoid." AND col_groupid>".$periodoData->col_groupid." AND col_plan_estudios='".$periodoData->col_plan_estudios."' AND col_modalidad='".$periodoData->col_modalidad."' AND col_aprobado=1 AND col_carreraid='".$periodoData->col_carreraid."' AND col_grado='".($periodoData->col_grado + 1)."'";
    // $queryPeriodo = "SELECT * FROM tbl_periodos WHERE col_id>".$periodoid." AND col_groupid>".$periodoData->col_groupid." AND col_plan_estudios='".$periodoData->col_plan_estudios."' AND col_modalidad='".$periodoData->col_modalidad."' AND col_aprobado=1 AND col_carreraid='".$periodoData->col_carreraid."' AND col_grado='".($periodoData->col_grado + 1)."' AND col_grupo='".$periodoData->col_grupo."'";
    $sth = $db->prepare($queryPeriodo);
    $sth->execute();
    $nuevoPeriodo = $sth->fetch(PDO::FETCH_OBJ);
    if($sth->rowCount() == 0) return 0;


    // if(alumnoTieneReprobadas($periodoid, $db, $alumnoid) > 0) return 0;

    return $nuevoPeriodo->col_id;

}

function generarPromediosFinales($db) {

}

function getPromedioFinalAlumno($alumnoid, $db) {

    $query = "SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid='".$alumnoid."' AND col_baja=0 ORDER BY col_periodoid DESC";
    $sth = $db->prepare($query);
    $sth->execute();
    $taxonomias = $sth->fetchAll();
    $promedio = 0;
    $n = 0;
    foreach($taxonomias as $item){
        $promedio = $promedio + getPromedioAlumno($alumnoid, $item['col_periodoid'], $db);
        $n++;
    }



    return formatoPromedio($promedio / $n);
}

function getPromedioAlumno($alumnoid, $periodoid, $db){

    $periodoData = getPeriodo($periodoid, $db, false);

    $query = 'SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $alumnodData = $sth->fetch(PDO::FETCH_OBJ);

    $carreraData = getCarrera($alumnodData->col_carrera, $db);

    $query = 'SELECT * FROM tbl_calificaciones WHERE (col_materia_clave NOT LIKE "%AC%" AND col_materia_clave NOT LIKE "%TL%" AND col_materia_clave NOT LIKE "%CL%" AND col_materia_clave NOT LIKE "%TR%") AND col_alumnoid="'.$alumnoid.'" AND col_periodoid="'.$periodoData->col_id.'" AND col_groupid="'.$periodoData->col_groupid.'" ORDER BY col_materia_clave ASC';
    $sth = $db->prepare($query);
    $sth->execute();
    $calificacionesData = $sth->fetchAll();

    // 5: Examen Parcial 1
    // 6: Examen Parcial 2
    // 7: Examen Final


        if($carreraData['modalidad'] == 'Maestría'){

                    $sumCF = 0;

                    foreach($calificacionesData as $item){
                        $item['col_materia_clave'] = strtoupper($item['col_materia_clave']);
                        if(strtoupper(substr($item['col_materia_clave'], 0, 3)) == 'LDO'){
                            $item['col_materia_clave'] = str_replace('O', '0', $item['col_materia_clave']);
                        }
                        $queryMateria = 'SELECT * FROM tbl_materias WHERE col_clave="'.$item['col_materia_clave'].'" ';
                        $sth = $db->prepare($query);
                        $sth->execute();
                        $materiaData = $sth->fetch(PDO::FETCH_OBJ);

                        $sumP1 = $sumP1 + $item['col_p1'];
                        $sumP2 = $sumP2 + $item['col_p2'];
                        $sumEF = $sumEF + $item['col_ef'];
                        $sumCF = $sumCF + $item['col_cf'];
                        $sumEXT = $sumEXT + $item['col_ext'];
                        $sumTS = $sumTS + $item['col_ts'];
                        $noMaterias++;


                    }

                    $promedioFinalAlumno = (intval($sumCF) > 0?round(($sumCF / $noMaterias)):'');
                    $promedioFinalAlumnoLetras = numerosaletras(intval($sumCF) > 0?round(($sumCF / $noMaterias)):'');
        }else{
                    $noMaterias = 0;
                    $sumPromedio = 0;
                    $sumP1 = 0;
                    $sumP2 = 0;
                    $sumEF = 0;
                    $sumCF = 0;
                    $sumEXT = 0;
                    $sumTS = 0;

                    foreach($calificacionesData as $item){
                        $item['col_materia_clave'] = strtoupper($item['col_materia_clave']);
                        if(strtoupper(substr($item['col_materia_clave'], 0, 3)) == 'LDO'){
                            $item['col_materia_clave'] = str_replace('O', '0', $item['col_materia_clave']);
                        }
                        $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.$item['col_materia_clave'].'" ';
                        $sth = $db->prepare($query);
                        $sth->execute();
                        $materiaData = $sth->fetch(PDO::FETCH_OBJ);

                        $sumP1 = $sumP1 + $item['col_p1'];
                        $sumP2 = $sumP2 + $item['col_p2'];
                        $sumEF = $sumEF + $item['col_ef'];
                        $sumCF = $sumCF + $item['col_cf'];
                        $sumEXT = $sumEXT + $item['col_ext'];
                        $sumTS = $sumTS + $item['col_ts'];
                        if(floatval($item['col_ts']) > 0){
                            $sumPromedio = $sumPromedio + $item['col_ts'];
                        }else if(floatval($item['col_ext']) > 0){
                            $sumPromedio = $sumPromedio + $item['col_ext'];
                        }else{
                            $sumPromedio = $sumPromedio + $item['col_cf'];
                        }
                        $noMaterias++;

                    }


                    $promedioFinalAlumno = (intval($sumPromedio) > 0?number_format(($sumPromedio / $noMaterias), 1):'');
                    $promedioFinalAlumnoLetras = numerosaletras((intval($sumPromedio) > 0?number_format(($sumPromedio / $noMaterias), 1):''));


    }



   return $promedioFinalAlumno;
}


function getBase64Size($base64) {
    $size_in_bytes = (int) (strlen(rtrim($base64, '=')) * 3 / 4);
    $size_in_kb    = $size_in_bytes / 1024;
    $size_in_mb    = $size_in_kb / 1024;

    return $size_in_bytes;
}

function claveMateria($clave) {
    $clave = trim(strtoupper($clave));
    if(substr($clave, 0, 2) == 'AC' || substr($clave, 0, 2) == 'TL') {
        if(strlen($clave) > 4) $clave = substr($clave, 0, (strlen($clave) - 1));
    }
    return $clave;
}

function switchGenero($val) {
    $val = strtoupper($val);
    if($val == 'H') return 'M';
    if($val == 'M') return 'F';
}

function splittext($text, $limit = 20, $css = ''){
    //return $text;
    if(strlen($text) <= $limit) return $text;
    $textSplitted = wordwrap($text, $limit, '|', true);
    $textSplittedArr = explode('|', $textSplitted);
    $html = '<table style="width:100%;"><tr>';
    foreach($textSplittedArr as $item) {
        if($css != '') {
            $html .= '<td style="font-weight:bold;'.$css.'">'.$item.'</td>';
        }else{
            $html .= '<td>'.$item.'</td>';
        }
    }
    $html .= '</tr></table>';

    return $html;

}


function getClavesCurriculares($db, $carrera = 0){

    $query = 'SELECT * FROM tbl_materias_tipos WHERE col_tipo=1 AND col_estatus=1';
    if($carrera > 0) {
        $query .= ' AND col_carrera="'.$carrera.'"';
    }
    $sth = $db->prepare($query);
    $sth->execute();
    $todos = $sth->fetchAll();
    foreach($todos as $item) {
        if(trim($item['col_letras']) != ''){
            $claves[] = strtoupper(trim($item['col_letras']));
        }
    }

    return $claves;
}

function getClavesPosibles($clave, $db, $planEstudios = false, $formatID = false){

    $query = 'SELECT * FROM tbl_materias WHERE col_clave="'.trim($clave).'" ';
    $sth = $db->prepare($query);
    $sth->execute();
    $materiaData = $sth->fetch(PDO::FETCH_OBJ);

    if($planEstudios){
        $query = 'SELECT * FROM tbl_materias WHERE col_plan_estudios="'.$materiaData->col_plan_estudios.'" AND col_nombre LIKE "%'.trim($materiaData->col_nombre).'%" ';
    }else{
        $query = 'SELECT * FROM tbl_materias WHERE col_nombre LIKE "%'.trim($materiaData->col_nombre).'%" ';
    }

    $sth = $db->prepare($query);
    $sth->execute();
    $todos = $sth->fetchAll();
    foreach($todos as $item) {
        if($formatID){
            $claves[$item['col_id']] = $item['col_clave'];
        }else{
            $claves[] = $item['col_clave'];
        }
    }

    return $claves;
}

function limpiarNombreCarrera($texto) {

    $texto = mb_strtolower(htmlentities(($texto)));
    $search[] = 'licenciatura en';
    $search[] = 'maestr&iacute;a en';
    $search[] = 'maestria en';
    $search[] = 'doctorado en';
    $search[] = 'licenciatura';
    $search[] = 'maestr&iacute;a';
    $search[] = 'maestria';
    $search[] = 'doctorado';
    return ucfirst(trim(str_replace($search, array('', '', ''), $texto)));
}

function formatClave($str){
    $clave = trim($str);
    $letrasClave = strtoupper(preg_replace("/[^A-Za-z]/", '', $clave));
    $numerosClave = (preg_replace("/[^0-9]/", '', $clave));

    return $letrasClave.$numerosClave;
}

function formatNombrePeriodo($str){
    $nombre = explode('-', trim($str));

    $inicioLetras = strtoupper(preg_replace("/[^A-Za-z]/", '', trim($nombre[0])));
    $inicioAnio = (preg_replace("/[^0-9]/", '', trim($nombre[0])));
    $inicio = $inicioLetras.' '.$inicioAnio;

    $finLetras = strtoupper(preg_replace("/[^A-Za-z]/", '', trim($nombre[1])));
    $finAnio = (preg_replace("/[^0-9]/", '', trim($nombre[1])));
    $fin = $finLetras.' '.$finAnio;

    return $inicio.' - '.$fin;
}

function formatPlanEstudios($str) {
    $planEstudios = trim($str);
    $letrasPlanEstudios = strtoupper(preg_replace("/[^A-Za-z]/", '', trim($planEstudios)));
    $numerosPlanEstudios = preg_replace("/[^0-9]/", '', trim($planEstudios));
    return $letrasPlanEstudios.' '.$numerosPlanEstudios;
}

function formatoPromedio($number) {
    if(intval($number) == 10) return 10;
    $_number = explode('.', $number);

    $entero = $_number[0];
    $decimal = substr($_number[1], 0, 1);
    if(intval($decimal) == 0) return $entero;

    return $entero.'.'.$decimal;

}

function fechaTextoEspecial($fecha = ''){
    // NUEVE DEL MES DE NOVIEMBRE DE DOS MIL DIECINUEVE
    if($fecha == '0000-00-00' || $fecha == '0000-00-00 00:00:00' || $fecha == '') return 'Sin Definir';
    $en = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $es = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    $txt = numerosaletras(date('j', strtotime($fecha))).' DEL MES DE ';
    $txt .= str_replace($en, $es, date('F', strtotime($fecha))).' DE ';
    $txt .= numerosaletras(date('Y', strtotime($fecha)));
    return strtoupper($txt);
}

function checarCorreo($correo, $db) {
    $response['status'] = false;
    $query = 'SELECT * FROM tbl_users WHERE col_email="'.trim($correo).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() > 0){
        $userData = $sth->fetch(PDO::FETCH_OBJ);
        $response['status'] = true;
        $response['error'] = 'mail_exists';
        $response['tipo'] = 'usuario';
        $response['recordID'] = $userData->col_id;
        $response['nombre'] = fixEncode($userData->col_firstname.' '.$userData->col_lastname);
        if(getCurrentUserType() == 'administrativo') {
            $response['mensaje'] = sprintf('Este correo ya esta siendo utilizado por el usuario: %s', $response['nombre']);
        }else{
            $response['mensaje'] = 'Este correo ya esta siendo utilizado.';
        }
    }


    $query = 'SELECT * FROM tbl_alumnos WHERE col_correo="'.trim($correo).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() > 0){
        $userData = $sth->fetch(PDO::FETCH_OBJ);
        $response['status'] = true;
        $response['error'] = 'mail_exists';
        $response['tipo'] = 'alumno';
        $response['recordID'] = $userData->col_id;
        $response['nombre'] = $userData->col_nombres.' '.$userData->col_apellidos;
        if(getCurrentUserType() == 'administrativo') {
            $response['mensaje'] = sprintf('Este correo ya esta siendo utilizado por el alumno: %s', $response['nombre']);
        }else{
            $response['mensaje'] = 'Este correo ya esta siendo utilizado.';
        }
    }

    $query = 'SELECT * FROM tbl_alumnos WHERE col_rep_correo="'.trim($correo).'"';
    $sth = $db->prepare($query);
    $sth->execute();
    if($sth->rowCount() > 0){
        $userData = $sth->fetch(PDO::FETCH_OBJ);
        $response['status'] = true;
        $response['error'] = 'mail_exists';
        $response['tipo'] = 'padre';
        $response['recordID'] = $userData->col_id;
        $response['nombre'] = $userData->col_nombres.' '.$userData->col_apellidos;
        if(getCurrentUserType() == 'administrativo') {
            $response['mensaje'] = sprintf('Este correo ya esta siendo utilizado por el representante del alumno: %s', $response['nombre']);
        }else{
            $response['mensaje'] = 'Este correo ya esta siendo utilizado.';
        }
    }

    return $response;
}

function getPonderacion($materiaid, $periodoid, $db) {
    $queryPonderacion = "SELECT * FROM tbl_materias_ponderacion WHERE col_periodoid='".$periodoid."' AND col_materiaid='".$materiaid."' LIMIT 1";
    $sth = $db->prepare($queryPonderacion);
    $sth->execute();
    $data = $sth->fetch(PDO::FETCH_OBJ);

    return json_decode(stripslashes($data->col_ponderacion), true);
}

function formatoFechaLista($fecha) {
    $meses = array('Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic');
    $txt = date('d', strtotime($fecha)).'-'.strtoupper($meses[date('n', strtotime($fecha))]).'-'.date('y', strtotime($fecha));

    return $txt;
}


function formatoFechaListaPosgrado($data, $db) {

    $days = array('L', 'M', 'M', 'J', 'V', 'S', 'D');
    $query = 'SELECT * FROM tbl_asistencia WHERE col_sesion=2 AND col_fecha="'.$data['col_fecha'].'" AND col_maestroid="'.$data['col_maestroid'].'" AND col_materiaid="'.$data['col_materiaid'].'"';
    $sesionDos = $db->prepare($query);
    $sesionDos->execute();
    if($sesionDos->rowCount() == 0){
        return $days[date('w', strtotime($data['col_fecha']))];
    }else{
        if($data['col_sesion'] == 1) return $days[date('w', strtotime($data['col_fecha']))].'M';
        return $days[date('w', strtotime($data['col_fecha']))].'V';
    }
}

function getPeriodosGeneracion($periodoid, $alumnoid, $db) {

    $queryPeriodo = "SELECT * FROM tbl_periodos WHERE col_id='".$periodoid."'";
    $sth = $db->prepare($queryPeriodo);
    $sth->execute();
    $dataPeriodo = $sth->fetch(PDO::FETCH_OBJ);

    $queryPeriodoGen = "SELECT * FROM tbl_periodos WHERE col_grado<='".$dataPeriodo->col_grado."' AND col_plan_estudios='".$dataPeriodo->col_plan_estudios."' AND col_modalidad='".$dataPeriodo->col_modalidad."' AND col_carreraid='".$dataPeriodo->col_carreraid."' ORDER BY col_grado ASC";
    $sth = $db->prepare($queryPeriodoGen);
    $sth->execute();
    $todos = $sth->fetchAll();
    foreach($todos as $item) {
        $queryAlumno = "SELECT * FROM tbl_alumnos_taxonomia WHERE col_baja=0 AND col_alumnoid='".$alumnoid."' AND col_periodoid='".$item['col_id']."'";
        $sthc = $db->prepare($queryAlumno);
        $sthc->execute();
        if($sthc->rowCount() > 0){
            $arr[$item['col_grado']] = $item;
        }
    }
    sort($arr);
    return $arr;
}

function mostrarCalificacion($val) {
    if($val == '') return 0;
    return $val;
}

class DBLog {
    var $tbl = '';
    var $tbl_col_id = 'col_id';
    var $tbl_id_value = 0;
    var $modulo = '';
    var $db = '';
    var $query = '';
    var $info = '';

    var $where = '';
    var $type = 'INSERT';
    var $old_data = '';
    var $new_data = '';


    function __construct($query, $tbl, $tbl_col_id, $tbl_id_value, $modulo, $db, $info = '') {

        $this->query = addslashes($query);
        $this->tbl = $tbl;
        $this->tbl_col_id = $tbl_col_id;
        $this->tbl_id_value = $tbl_id_value;
        $this->modulo = $modulo;
        $this->db = $db;
        $this->info = $info;
        $this->where = array((string) $this->tbl_col_id => (string) $this->tbl_id_value);



        if(strpos($this->query, 'INSERT') !== false) $this->type = 'INSERT';
        if(strpos($this->query, 'UPDATE') !== false) $this->type = 'UPDATE';
        if(strpos($this->query, 'DELETE') !== false) $this->type = 'DELETE';
    }

    function prepareLog() {
        if(is_object($this->db) && $this->type != 'INSERT') {

            $_query = "SELECT * FROM {$this->tbl} WHERE {$this->getWhere()}";
            $pdo = $this->db->prepare($_query);
            $pdo->execute();
            $this->old_data = json_encode($pdo->fetch(PDO::FETCH_ASSOC));
        }
    }

    function saveLog() {
        if(is_object($this->db)) {
            if($this->type != 'DELETE') {
                $_query = "SELECT * FROM {$this->tbl} WHERE {$this->getWhere()}";
                $pdo = $this->db->prepare($_query);
                $pdo->execute();
                $this->new_data = json_encode($pdo->fetch(PDO::FETCH_ASSOC));
            }
            $this->finish();
        }
    }

    function getWhere() {
        if(is_array($this->where)) {

            foreach($this->where as $k => $v) {
                $strings[] = "{$k}='{$v}'";
            }

        }

        return implode(' AND ', $strings);
    }

    function finish() {
        if(is_object($this->db)) {
            // $variables['col_id'] = '';
            $variables['col_type'] = $this->type;
            $variables['col_tbl_id'] = json_encode($this->where);
            $variables['col_tbl'] = $this->tbl;
            $variables['col_query'] = $this->query;
            $variables['col_old_data'] = $this->old_data;
            $variables['col_new_data'] = $this->new_data;
            $variables['col_modulo'] = $this->modulo;
            $variables['col_user_type'] = getCurrentUserData('tipoUsuario');
            $variables['col_userid'] = getCurrentUserData('id');
            $variables['col_datetime'] = date('Y-m-d H:i:s');
            $variables['col_info'] = $this->info;
            $variables['col_ip'] = getCurrentUserData('asking');
            $variables['col_source'] = getCurrentUserData('referer');
            $variables['col_device'] = getCurrentUserData('device');
            $query = insert('tbl_log', $variables);
            $sth = $this->db->prepare($query);
            $sth->execute();
        }
    }

}

function dbprepare_log($query, $tbl, $tbl_id, $modulo, $db){

    $type = '';
    if(strpos($query, 'INSERT INTO') !== false) $type = 'INSERT';
    if(strpos($query, 'UPDATE') !== false) $type = 'UPDATE';
    if(strpos($query, 'DELETE FROM') !== false) $type = 'DELETE';

    if($type == '') return;



}


require __DIR__ . '/../src/general.php';
require __DIR__ . '/../src/me.php';


// Termina routes.php
