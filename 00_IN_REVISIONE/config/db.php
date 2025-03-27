<? 

/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/


$hostname_std_conn = "localhost";
$database_std_conn = "esvieguz_JINGB2B"; ////database su dominio vitaletti.it
$username_std_conn = "esvieguz_JINGB2B";
$password_std_conn = "RiQNPz#H0B&j";

//require_once $_SERVER['DOCUMENT_ROOT']."/class/medoo1.4.5/src/Medoo.php";
$pathInPieces = explode('/', $_SERVER['DOCUMENT_ROOT']);
$PathRoot='/'.$pathInPieces[1].'/'.$pathInPieces[2].'/';
require_once $PathRoot.'vendor/autoload.php';
use Medoo\Medoo;
// Initialize
$medooDB = new Medoo([
    'database_type' => 'mysql',
    'database_name' => $database_std_conn,
    'server' => 'localhost',
    'username' => $username_std_conn,
    'password' => $password_std_conn,
    'charset' => 'utf8'
]);


define('TUO_TOKEN_API', 'ABCD123Makbrash11');


?>