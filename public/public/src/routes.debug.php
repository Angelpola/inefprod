<?php

/**
 * Archivo utilizado para realizar pruebas con diferentes tipos de registros, el cual nos permite identificar errores de forma rápida y solucionar otros,
 * este archivo no es accesible a traves de ningun modulo y es de uso exlusivo del desarrollador a cargo.
 */

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

set_time_limit(0);
$app->group('/debug', function () {

    $this->get('/roomTest', function (Request $request, Response $response, array $args) {

        if(!isset($_REQUEST['code'])) exit;
        if(isset($_REQUEST['code']) && $_REQUEST['code'] != "J0rg32!0683") exit;


        $asistencias = getAsistenciasByAlumnoAndMateria(intval($_REQUEST['alumnoid']), $this->db, '2020-03-14', '2020-05-08');


        //print_r($asistencias);
        header('Content-Type: application/json');
        echo json_encode($asistencias);
        exit;

     });

     $this->get('/documentosOrdenDebug', function (Request $request, Response $response, array $args) {


        $query = 'SELECT * FROM tbl_documentos WHERE col_alumnos="1" ORDER BY col_nombre ASC';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        $i=1;
        foreach($todos as $row){
            $queryDocs = 'UPDATE tbl_documentos SET col_orden="'.$i.'" WHERE col_id="'.$row['col_id'].'"';
            $sthInsert = $this->db->prepare($queryDocs);
            $sthInsert->execute();
            $i++;
        }

     });

     $this->get('/documentosDebug', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare('TRUNCATE tbl_alumnos_documentos');
        $sth->execute();

        $query = 'SELECT * FROM tbl_alumnos';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $todos = $sth->fetchAll();
        foreach($todos as $row){
            $docs = base64_decode($row['col_documentos']);
            $docs = unserialize($docs);
            if(count($docs)) {
                foreach($docs AS $k => $v) {
                    if($v == '') continue;
                    $query = 'SELECT * FROM tbl_documentos WHERE col_id="'.$k.'"';
                    $sthc = $this->db->prepare($query);
                    $sthc->execute();
                    if($sthc->rowCount() > 0) {
                        $queryDocs = 'INSERT INTO tbl_alumnos_documentos (col_alumnoid, col_documentoid, col_original, col_fecha_creacion, col_autor_creacion) VALUES("'.$row['col_id'].'", "'.$k.'", 1, "'.date("Y-m-d H:i:s").'", 1)';
                        $sthInsert = $this->db->prepare($queryDocs);
                        $sthInsert->execute();
                    }
                }
            }
        }

     });


    $this->get('/testMaterias', function (Request $request, Response $response, array $args) {

       if(!isset($_REQUEST['code'])) exit;
       if(isset($_REQUEST['code']) && $_REQUEST['code'] != "J0rg32!0683") exit;

       if(isset($_REQUEST['periodo'])) {
            $periodoid = intval($_REQUEST['periodo']);
       }else if(isset($_REQUEST['alumno'])) {

            $alumnoid = intval($_REQUEST['alumno']);
            $sth = $this->db->prepare('SELECT * FROM tbl_alumnos WHERE col_id="'.$alumnoid.'"');
            $sth->execute();
            $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
            $periodoid = $alumnoData->col_periodoid;
       }

       $i = 0;
       $query = 'SELECT * FROM tbl_horarios WHERE col_periodoid="'.$periodoid.'"';
       $sth = $this->db->prepare($query);
       $sth->execute();
       $todos = $sth->fetchAll();
       foreach($todos as $row){

            $materiaData = getMateriaData($row['col_materiaid'], $this->db, true);

            $horario[0]['lunes']['horario'] = $row['col_lunes'];
            $horario[0]['lunes']['horas'] = diferenciaHoras($row['col_lunes']);
            $horario[1]['martes']['horario'] = $row['col_martes'];
            $horario[1]['martes']['horas'] = diferenciaHoras($row['col_martes']);
            $horario[2]['miercoles']['horario'] = $row['col_miercoles'];
            $horario[2]['miercoles']['horas'] = diferenciaHoras($row['col_miercoles']);
            $horario[3]['jueves']['horario'] = $row['col_jueves'];
            $horario[3]['jueves']['horas'] = diferenciaHoras($row['col_jueves']);
            $horario[4]['viernes']['horario'] = $row['col_viernes'];
            $horario[4]['viernes']['horas'] = diferenciaHoras($row['col_viernes']);
            $horario[5]['sabado']['horario'] = $row['col_sabado'];
            $horario[5]['sabado']['horas'] = diferenciaHoras($row['col_sabado']);
            $horario[6]['domingo']['horario'] = $row['col_domingo'];
            $horario[6]['domingo']['horas'] = diferenciaHoras($row['col_domingo']);

            $result[$i]['periodoid'] = $periodoid;
            $result[$i]['materiaid'] = $row['col_materiaid'];
            //$result[$i]['horario'] = $horario;
            $result[$i]['materiaData'] = $materiaData;
            $i++;
       }

       header('Content-Type: application/json');
       echo json_encode($result);
    exit;

    });

    $this->get('/regenerarclaves', function (Request $request, Response $response, array $args) {
        header('Content-Type: application/json');
       if(!isset($_REQUEST['code'])) exit;
       if(isset($_REQUEST['code']) && $_REQUEST['code'] != "J0rg32!0683") exit;

       $sth = $this->db->prepare("SELECT * FROM tbl_materias_tipos WHERE col_estatus=1");
       $sth->execute();
       $todos = $sth->fetchAll();
       foreach($todos as $row){
        $tiposMaterias[$row['col_letras']] = $row['col_id'];
       }

       $y = $x = 0;
       $z = 0;
       $a = 0;

       $sth = $this->db->prepare("SELECT * FROM tbl_materias");
       $sth->execute();
       $todos = $sth->fetchAll();
       foreach($todos as $row){
           //$sufijo = strtoupper(substr($row['col_clave'], 0, 2));
           $sufijo = trim(strtoupper(preg_replace('/[0-9]+/', '', $row['col_clave'])));
           $numeroCompleto = preg_replace('/[a-zA-Z]+/', '', $row['col_clave']);

           //$semestre = $row['col_semestre'];
           //if($row['col_semestre'] == 99) $semestre = substr($row['col_clave'], -1);

           $row['col_clave'] = strtoupper($row['col_clave']);
           if($tiposMaterias[$sufijo] > 0) {
               $sufijoID = $tiposMaterias[$sufijo];


               $claveNueva2 = $sufijo.$numeroCompleto;
               $arr['claveNueva2'] = $claveNueva2;
               $arr['claveActual'] = $row['col_clave'];
               $arr['sufijo'] = $sufijo;
               $arr['numeroCompleto'] = $numeroCompleto;

               if($claveNueva2 != $row['col_clave']) {
                   $dataNoIguales[$z] = $arr;
                   $z++;
                }else{
                    $dataIguales[$a] = $arr;
                    $a++;

                    $queryUpdate = 'UPDATE tbl_materias SET col_tipo_materia="'.$sufijoID.'", col_numero_clave="'.$numeroCompleto.'" WHERE col_id="'.$row['col_id'].'"';
                    $u = $this->db->prepare($queryUpdate);
                    $u->execute();
                }


               $x++;



           }else{
               $noTipo[] = $sufijo;
           }
       }

        echo json_encode(array('Iguales' => $dataIguales, 'NoIguales' => $dataNoIguales));
        echo json_encode(array_unique($noTipo));
        exit;
    });


    $this->get('/permisos', function (Request $request, Response $response, array $args) {
        header('Content-Type: application/json');
       if(!isset($_REQUEST['code'])) exit;
       if(isset($_REQUEST['code']) && $_REQUEST['code'] != "J0rg32!0683") exit;
       echo '<style> * {font-family: Arial; font-size:11px;}</style>';
       echo '<ul>';
       $sth = $this->db->prepare("SELECT * FROM tbl_menu WHERE col_parent=0");
       $sth->execute();
       $todos = $sth->fetchAll();
       foreach($todos as $row){
           echo '<li>';
           echo '<b>'.fixEncode($row['col_titulo']).'</b>';
                echo '<ul>';
                echo '<li>Tipo de Usuario:';
                    echo '<ul>';
                        if($row['col_admins'] == 1) echo '<li>Administrativos</li>';
                        if($row['col_alumnos'] == 1) echo '<li>Alumnos</li>';
                        if($row['col_maestros'] == 1) echo '<li>Maestros</li>';
                        if($row['col_representantes'] == 1) echo '<li>Representantes</li>';
                    echo '</ul>';
                echo '</li>';
                if($row['col_admins'] == 1) {
                    echo '<li>Departamentos con Acceso:';
                    echo '<ul>';
                        if($row['col_deptos'] == '') {
                            echo '<li>Todos los departamentos</li>';
                        }else{
                            foreach(explode(',', $row['col_deptos']) as $item){
                                $sth = $this->db->prepare('SELECT * FROM tbl_departamentos WHERE col_id="'.$item.'"');
                                $sth->execute();
                                $deptoData = $sth->fetch(PDO::FETCH_OBJ);
                                echo '<li>'.fixEncode($deptoData->col_nombre).'</li>';
                            }
                        }
                    echo '</ul>';
                    echo '</li>';
                }




                $sth = $this->db->prepare("SELECT * FROM tbl_menu WHERE col_parent='".$row['col_id']."'");
                $sth->execute();
                $todosSubs = $sth->fetchAll();
                foreach($todosSubs as $rowSub){
                    echo '<li>';
                    echo '<b>Submódulo: '.fixEncode($rowSub['col_titulo']).'</b>';
                         echo '<ul>';
                         echo '<li>Tipo de Usuario:';
                         echo '<ul>';
                             if($rowSub['col_admins'] == 1) echo '<li>Administrativos</li>';
                             if($rowSub['col_alumnos'] == 1) echo '<li>Alumnos</li>';
                             if($rowSub['col_maestros'] == 1) echo '<li>Maestros</li>';
                             if($rowSub['col_representantes'] == 1) echo '<li>Representantes</li>';
                         echo '</ul>';
                        echo '</li>';
                        if($rowSub['col_admins'] == 1) {
                            echo '<li>Departamentos con Acceso:';
                            echo '<ul>';
                         if($rowSub['col_deptos'] == '') {
                             echo '<li>Todos los departamentos</li>';
                         }else{
                             foreach(explode(',', $rowSub['col_deptos']) as $item){
                                 $sth = $this->db->prepare('SELECT * FROM tbl_departamentos WHERE col_id="'.$item.'"');
                                 $sth->execute();
                                 $deptoData = $sth->fetch(PDO::FETCH_OBJ);
                                 echo '<li>'.fixEncode($deptoData->col_nombre).'</li>';
                             }
                         }
                         echo '</ul>';
                         echo '</li>';
                        }
                         echo '</ul>';
                    echo '</li>';
                }


                echo '</ul>';
           echo '</li>';
       }
       echo '</ul>';

    });

    $this->get('/inscritosAcademias', function (Request $request, Response $response, array $args) {
        header('Content-Type: application/json');
       if(!isset($_REQUEST['code'])) exit;
       if(isset($_REQUEST['code']) && $_REQUEST['code'] != "J0rg32!0683") exit;

    });

    $this->get('/debugAlumno', function (Request $request, Response $response, array $args) {
         header('Content-Type: application/json');
        if(!isset($_REQUEST['code'])) exit;
        if(isset($_REQUEST['code']) && $_REQUEST['code'] != "J0rg32!0683") exit;
        $req = 'all';
        if(isset($_REQUEST['req']))$req = trim($_REQUEST['req']);

        $alumnoid = intval($_REQUEST['id']);
        $queryAlumno = "SELECT * FROM tbl_alumnos WHERE col_id='".$alumnoid."'";

        if(isset($_REQUEST['correo'])) {
            $correo = trim($_REQUEST['correo']);
            $queryAlumno = "SELECT * FROM tbl_alumnos WHERE col_correo='".$correo."'";
        }

        if(isset($_REQUEST['nombre'])) {
            $nombre = trim($_REQUEST['nombre']);
            $queryAlumno = "SELECT * FROM tbl_alumnos WHERE CONCAT(col_nombres, ' ', col_apellidos) LIKE '%".$nombre."%' LIMIT 1";
        }

        $sth = $this->db->prepare($queryAlumno);
        $sth->execute();
        $alumnoData = $sth->fetch(PDO::FETCH_OBJ);
        $alumnoid = $alumnoData->col_id;


        $periodoData = getPeriodo($alumnoData->col_periodoid, $this->db, false);
        $carreraData = getCarrera($alumnoData->col_carrera, $this->db);

        echo "\n\nAlumno\n\n";
        echo json_encode($alumnoData);

        echo "\n\nCarrera\n\n";
        echo json_encode($carreraData);

        echo "\n\nPeriodos Actual\n\n";
        echo json_encode($periodoData);

        if($req == 'all' || $req == 'periodos') {
            echo "\n\nPeriodos Inscritos\n\n";
            $sth = $this->db->prepare("SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid='".$alumnoid."'");
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach($todos as $row){
                echo json_encode($row);
            }
        }

        if($req == 'all' || $req == 'actividades') {
            echo "\n\nActividades Periodo Actual\n\n";
            $sth = $this->db->prepare("SELECT * FROM tbl_actividades WHERE col_visible_excepto LIKE '%".$alumnoData->col_periodoid."%'");
            $sth->execute();
            $todos = $sth->fetchAll();
            foreach($todos as $row){
                echo json_encode($row);
            }
        }


        exit;
    });

    $this->get('/fixAlumnosCheck', function (Request $request, Response $response, array $args) {

        if(!isset($_REQUEST['code'])) exit;
        if(isset($_REQUEST['code']) && $_REQUEST['code'] != "J0rg32!0683") exit;

        $x = $i = 0;

        $sth = $this->db->prepare("SELECT * FROM tbl_alumnos");
        $sth->execute();
        $todos = $sth->fetchAll();
        foreach($todos as $row) {
            $periodoActivo = $row['col_periodoid'];
            if($periodoActivo == 0) continue;
            $periodoData = getPeriodo($periodoActivo, $this->db, false);

            $query = 'SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid="'.$row['col_id'].'" AND col_status="1"';
            $c = $this->db->prepare($query);
            $c->execute();
            $taxData = $c->fetch(PDO::FETCH_OBJ);

            if($periodoActivo != $taxData->col_periodoid) {
                $i++;
            }


        }

        echo "Total fallas: ".$i;

    });


    $this->get('/fixPeriodos', function (Request $request, Response $response, array $args) {

        if(!isset($_REQUEST['code'])) exit;
        if(isset($_REQUEST['code']) && $_REQUEST['code'] != "J0rg32!0683") exit;

        $x = $i = 0;

        $sth = $this->db->prepare("SELECT * FROM tbl_periodos");
        $sth->execute();
        $todos = $sth->fetchAll();
        foreach($todos as $row) {
            $needed = false;
            if(strtotime($row['col_created_at']) < strtotime('2019-01-18') && $row['col_created_at'] == $row['col_updated_at']) {
                $needed = true;
            }

            if($needed == true) {
                $queryUpdate = 'UPDATE tbl_periodos SET col_modalidad=col_modalidad+1 WHERE col_id="'.$row['col_id'].'"';
                $u = $this->db->prepare($queryUpdate);
                if($u->execute()) $x++;
                $i++;
            }


        }

        echo "Total fallas: ".$i;
        echo "<br/>";
        echo "Total Actualizados: ".$x;

    });


    $this->get('/fixAlumnos', function (Request $request, Response $response, array $args) {

        if(!isset($_REQUEST['code'])) exit;
        if(isset($_REQUEST['code']) && $_REQUEST['code'] != "J0rg32!0683") exit;

        $x = $i = 0;

        $sth = $this->db->prepare("SELECT * FROM tbl_alumnos");
        $sth->execute();
        $todos = $sth->fetchAll();
        foreach($todos as $row) {
            $periodoActivo = $row['col_periodoid'];
            if($periodoActivo == 0) continue;
            $periodoData = getPeriodo($periodoActivo, $this->db, false);

            $query = 'SELECT * FROM tbl_alumnos_taxonomia WHERE col_alumnoid="'.$row['col_id'].'" AND col_status="1"';
            $c = $this->db->prepare($query);
            $c->execute();
            $taxData = $c->fetch(PDO::FETCH_OBJ);

            if($periodoActivo != $taxData->col_periodoid) {
                $queryUpdate = 'UPDATE tbl_alumnos_taxonomia SET col_periodoid="'.$periodoData->col_id.'", col_groupid="'.$periodoData->col_groupid.'" WHERE col_alumnoid="'.$row['col_id'].'" AND col_status="1"';
                $u = $this->db->prepare($queryUpdate);
                if($u->execute()) $x++;
                $i++;
            }


        }

        echo "Total fallas: ".$i;
        echo "<br/>";
        echo "Total Actualizados: ".$x;

    });

    $this->get('/getLogActividades', function (Request $request, Response $response, array $args) {

        if(!isset($_REQUEST['code'])) exit;
        if(isset($_REQUEST['code']) && $_REQUEST['code'] != "J0rg32!0683") exit;
        if(!isset($_REQUEST['fecha'])) exit;


        if(file_exists('/var/www/html/logsuploads/log_'.$_REQUEST['fecha'].'.log')) {

            $fichero = file_get_contents('/var/www/html/logsuploads/log_'.$_REQUEST['fecha'].'.log', FILE_USE_INCLUDE_PATH);
            print($fichero);

        }else{
            echo 'No existe el log para esta fecha';
        }

    });


    $this->get('/getInfo', function (Request $request, Response $response, array $args) {

        if(!isset($_REQUEST['code'])) exit;
        if(isset($_REQUEST['code']) && $_REQUEST['code'] != "J0rg32!0683") exit;


        $max_upload = (int)(ini_get('upload_max_filesize'));
        $max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);

        echo 'Limit of uploads: '. $upload_mb.' ('.$max_upload. ' - '. $max_post. ' - '. $memory_limit.')';


    });

});
//Termina routes.debug.php
