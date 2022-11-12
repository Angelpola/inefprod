<?php

/**
 * Archivo de configuración de conexión con la base de datos de consulta de la API
 */
$dev_inef= false;

if(strpos($_SERVER['HTTP_HOST'], 'localhost')  !== false){
    $config = [
        'settings' => [
            'displayErrorDetails' => true, // set to false in production
            'addContentLengthHeader' => false, // Allow the web server to send the content-length header

            //Config DB
             /*'db' => [ //
                 'host' => '34.68.63.8',
                 'user' => 'inef-bd',
                 'pass' => '1gS{kLQ]>tJS3<g',
                 'dbname' => 'fldch'
	 ],*/
            // Renderer settings
            'renderer' => [
                'template_path' => __DIR__ . '/../templates/',
            ],

            // Monolog settings
            'logger' => [
                'name' => 'slim-app',
                'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                'level' => \Monolog\Logger::DEBUG,
            ],
        ],
    ];
    if($dev_inef == false) {
    $config['settings']['db'] = Array(
        'host' => '34.68.63.8', // Host del servidor al que se conecta la base de datos
                'user' => 'inef-bd', // Nombre de usuario de la base de datos
                'pass' => '1gS{kLQ]>tJS3<gA:A', // Contraseña de la base de datos
                'dbname' => 'plat_inef' // Nombre de la base de datos a la que se conecta la plataforma
    );}
    if($dev_inef == true) {
    $config['settings']['db'] = Array(
        'host' => '34.68.63.8', // Host del servidor al que se conecta la base de datos
                'user' => 'inef-bd', // Nombre de usuario de la base de datos
                'pass' => '1gS{kLQ]>tJS3<g', // Contraseña de la base de datos
                'dbname' => 'plat_inef' // Nombre de la base de datos a la que se conecta la plataforma
    );
    }

}
else{ //Production
    $config = [
        'settings' => [
            'displayErrorDetails' => true, // set to false in production
            'addContentLengthHeader' => false, // Allow the web server to send the content-length header

            //Config DB
            'db' => [
                'host' => '34.68.63.8', // Host del servidor al que se conecta la base de datos
                'user' => 'inef-bd', // Nombre de usuario de la base de datos
                'pass' => '1gS{kLQ]>tJS3<g', // Contraseña de la base de datos
                'dbname' => 'plat_inef' // Nombre de la base de datos a la que se conecta la plataforma
            ],
            // Renderer settings
            'renderer' => [
                'template_path' => __DIR__ . '/../templates/',
            ],

            // Monolog settings
            'logger' => [
                'name' => 'slim-app',
                'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                'level' => \Monolog\Logger::DEBUG,
	     ]	    
        ],
    ];
}

return $config;
