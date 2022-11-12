<?php
use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

/**
 *
 * Archivo que incluye exclusivamente todas las funciones relacionadas con el calendario que es visible para todos los tipos de usuarios.
 *
 * Lista de funciones
 *
 * /calendario
 * - /list
 * - /get
 *
 */

$app->group('/calendario', function () {

    $this->get('/list', function (Request $request, Response $response, array $args) {

        $sth = $this->db->prepare("SELECT * FROM tbl_calendario ORDER BY col_id");
        $sth->execute();
        $todos = $sth->fetchAll();
        $i = 0;
        foreach($todos as $item){
            // https://fullcalendar.io/docs/event-object
            $result[$i]['id'] = $item['col_id'];
            $result[$i]['title'] = $item['col_nombre'];
            $result[$i]['description'] = $item['col_descripcion'];
            $result[$i]['start'] = $item['col_fecha_inicio'];
            $result[$i]['end'] = ($item['col_fecha_fin'] == '0000-00-00'?$item['col_fecha_inicio']:$item['col_fecha_fin']);
            $result[$i]['backgroundColor'] = '#'.$item['col_color_fondo'];
            $result[$i]['borderColor'] = '#'.$item['col_color_fondo'];
            $result[$i]['textColor'] = '#'.$item['col_color_letra'];

            $i++;
        }

        return $this->response->withJson($result);

    });

    $this->post('/get', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $query = 'SELECT * FROM tbl_users WHERE col_id="'.intval($input['params']['id']).'"';
        $sth = $this->db->prepare($query);
        $sth->execute();
        $data = $sth->fetch(PDO::FETCH_OBJ);

        return $this->response->withJson($data);

    });


});
