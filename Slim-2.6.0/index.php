<?php
/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */
@session_start();
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();

/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new \Slim\Slim();

/*Configuramos la aplicacion*/
$app->config(array(
    'templates.path' => 'vistas',
));

// Indicamos el tipo de contenido y condificación que devolvemos desde el framework Slim.
$app->contentType('text/html; charset=utf-8');
 

// Definimos conexion de la base de datos.
// Lo haremos utilizando PDO con el driver mysql.
define('BD_SERVIDOR', 'localhost');
define('BD_NOMBRE', 'contactdb');
define('BD_USUARIO', 'root');
define('BD_PASSWORD', '');
 
// Hacemos la conexión a la base de datos con PDO.
// Para activar las collations en UTF8 podemos hacerlo al crear la conexión por PDO
// o bien una vez hecha la conexión con
// $db->exec("set names utf8");
try{
    $db = new PDO('mysql:host=' . BD_SERVIDOR . ';dbname=' . BD_NOMBRE . ';charset=utf8', BD_USUARIO, BD_PASSWORD);
} catch (PDOException $e) {
    echo 'Falló la conexión: ' . $e->getMessage();
}

//Cabeceras CORS
if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST,DELETE, OPTIONS");         

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers:        {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }
/*
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Content-Type: application/json; charset="UTF-8"');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
*/
/*
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, `Slim::patch`, and `Slim::delete`
 * is an anonymous function.
 */

$app->get('/', function() {
            echo "Pagina de gestión API REST de mi aplicación.";
        });
 
// Get con todos los contactos
$app->get('/contacts', function() use($db) {//usamos use(&db) para acceder a las variables de internas de slim

            $consulta = $db->prepare("select * from contacts");
            $consulta->execute();
            // Almacenamos los resultados en un array asociativo.
            $resultados = $consulta->fetchAll(PDO::FETCH_ASSOC);
            // Devolvemos ese array asociativo como un string JSON.


              /* header("Access-Control-Allow-Origin: *");
               header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
               header('Content-Type: application/json; charset="UTF-8"');*/
            echo json_encode($resultados);
        });

//Get de un contacto
$app->get('/contact/:id', function($contactID) use($db) {

            $consulta = $db->prepare("select * from contacts where id=:param1");
 
            // Aasociamos el :param1 con el valor que le toque.
            $consulta->execute(array(':param1' => $contactID));
 
            // Almacenamos los resultados en un array asociativo.
            $resultados = $consulta->fetchAll(PDO::FETCH_ASSOC);
 
            // Devolvemos ese array asociativo como un string JSON.
            echo json_encode($resultados);
        });


// Post en la api rest
$app->post('/contact',function() use($db,$app) {
    // recibimos el formulario y lo guardamos en datosform
    $datosform=file_get_contents("php://input");
 
    $datos = json_decode($datosform); 
    // Los datos serán accesibles de esta forma:
    // $datosform->post('atributo')
 
    // Preparamos la consulta de insert.
    $consulta=$db->prepare("insert into contacts(id,name,email,number) 
                    values (:id,:name,:email,:number)");
 
    $estado=$consulta->execute(
            array(
                ':id'=> null,
                ':name'=> $datos->{'name'},
                ':email'=> $datos->{'email'},
                ':number'=> $datos->{'number'}
                )
            );
    
    if ($estado)
        echo json_encode(array('estado'=>true,'mensaje'=>'Datos insertados correctamente.'));
    else
        echo json_encode(array('estado'=>false,'mensaje'=>'Error al insertar datos en la tabla.'));
});

// Delete de api
$app->delete('/contact/:id',function($id) use($db)
{
    

   $consulta=$db->prepare("delete from contacts where id=:id");
 
   $consulta->execute(array(':id'=>$id));
 
if ($consulta->rowCount() == 1)
   echo json_encode(array('estado'=>true,'mensaje'=>'El usuario '.$id.' ha sido borrado correctamente.'));
 else
   echo json_encode(array('estado'=>false,'mensaje'=>'ERROR: ese registro no se ha encontrado en la tabla.'));
 
});
 
 
// Put api
$app->put('/contact/:id',function($id) use($db,$app) {
    // cargamos el formulario
    $datosform=$app->request;
 
    // Preparamos la consulta de update.
    $consulta=$db->prepare("update contacts set name=:name, email=:email, number=:number 
                            where id=:id");
 
    $estado=$consulta->execute(
            array(
                ':idusuario'=>$id,
                ':name'=> $datosform->post('name'),
                ':email'=> $datosform->post('email'),
                ':number'=> $datosform->post('number')
                )
            );
 
    // Si se han modificado datos...
    if ($consulta->rowCount()==1)
      echo json_encode(array('estado'=>true,'mensaje'=>'Datos actualizados correctamente.'));
    else
      echo json_encode(array('estado'=>false,'mensaje'=>'Error al actualizar datos, datos 
                        no modificados o registro no encontrado.'));
});


/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
