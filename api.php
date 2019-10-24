<?php
/**
 * Elatus Cabal API
 *
 * A single file wrapper for executing SQL commands of Cabal Online databases
 */

const API_KEY = 'please_update_this_key';
const DATABASE_HOST = 'localhost';
const DATABASE_PORT = 1433;
const DATABASE_USERNAME = 'sa';
const DATABASE_PASSWORD = 'inferno';
const ACCOUNT_DATABASE = 'Account';
const SERVER_DATABASE = 'Server01';
const CASH_DATABASE = 'CabalCash';
const ENABLE_SELECT = 1; // Whether SELECT queries are enabled. 1 = Enable, 0 = Disable
const ENABLE_INSERT = 1; // Whether INSERT queries are enabled. 1 = Enable, 0 = Disable
const ENABLE_UPDATE = 1; // Whether UPDATE queries are enabled. 1 = Enable, 0 = Disable
const ENABLE_DELETE = 1; // Whether DELETE queries are enabled. 1 = Enable, 0 = Disable
const ENABLE_SP = 1;  // Whether running stored procedures are enabled. 1 = Enable, 0 = Disable
const API_VERSION = 1.1;

const SELECT_QUERY = 'SELECT';
const INSERT_QUERY = 'INSERT';
const UPDATE_QUERY = 'UPDATE';
const DELETE_QUERY = 'DELETE';
const SP_EXEC = 'SP_EXEC';

// Setting max execution time to 1 minute so that most queries can be complete without timing out
ini_set('max_execution_time', '60');

// Setting CORS and content type headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// DB options that will be set for all connections
$databaseConnectionOptions = [
    PDO::ATTR_TIMEOUT => 30,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::SQLSRV_ATTR_DIRECT_QUERY => false,
    PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => true
];

try {
    // Checking whether key provided was correct
    if (!isset($_GET['key'])) {
        throw new Exception('Key was not provided');
    }
    if ($_GET['key'] !== API_KEY) {
        throw new Exception('Incorrect key provided');
    }

    // Initialize PDO for each database
    $accountDb = new PDO(
        buildDsn(ACCOUNT_DATABASE),
        DATABASE_USERNAME,
        DATABASE_PASSWORD,
        $databaseConnectionOptions
    );
    $serverDb = new PDO(
        buildDsn(SERVER_DATABASE),
        DATABASE_USERNAME,
        DATABASE_PASSWORD,
        $databaseConnectionOptions
    );
    $cashDb = new PDO(
        buildDsn(CASH_DATABASE),
        DATABASE_USERNAME,
        DATABASE_PASSWORD,
        $databaseConnectionOptions
    );

    // If GET request was done return success message with version number
    if (!isPost()) {
        echo json_encode(['success' => true, 'msg' => 'Running Elatus Cabal API', 'version' => API_VERSION]);
    } else {
        if (startsWith($_SERVER["CONTENT_TYPE"], 'application/json')) {
            $postData = json_decode(file_get_contents('php://input'), true);
        } else {
            $postData = $_POST;
        }
        if (!isset($postData['database'])) {
            $currentDb = $accountDb;
        } else {
            switch (strtolower($postData['database'])) {
                case 'cash':
                    $currentDb = $cashDb;
                    break;
                case 'server':
                    $currentDb = $serverDb;
                    break;
                default:
                    $currentDb = $accountDb;
                    break;
            }
        }
        if (!isset($postData['query'])) {
            throw new Exception('\'query\' field is required');
        }
        customLog('Received query request:');
        customLog(json_encode($postData));
        $query = $postData['query'];
        if (!isset($postData['params'])) {
            $params = [];
        } else {
            $params = $postData['params'];
        }
        checkQueryPermissions($query);
        $queryType = getQueryType($query);
        $pdoStatement = $currentDb->prepare($query);
        if (!empty($params)) {
            $pdoStatement->execute($params);
        } else {
            $pdoStatement->execute();
        }
        switch ($queryType) {
            case SELECT_QUERY:
                $data = $pdoStatement->fetchAll();
                echo json_encode([
                    'success' => true,
                    'msg' => 'Query successfully executed',
                    'data' => utf8ize($data)
                ]);
                break;
            default:
                echo json_encode([
                    'success' => true,
                    'msg' => 'Query successfully executed',
                    'data' => $pdoStatement->rowCount()
                ]);
                break;
        }
        customLog('Query successfully executed!');
    }
} catch (PDOException $e) {
    // Report all PDO driver exceptions
    header("HTTP/1.0 500 Internal Server Error");
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    customLog('Query execution failed. ERROR: ' . $e->getMessage() . '!');
} catch (Exception $e) {
    // Catch all exceptions and return 400 response
    header("HTTP/1.0 400 Bad Request");
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    customLog('Query execution failed. ERROR: ' . $e->getMessage() . '!');
}

/**
 * Returns whether the current request is POST
 *
 * @return bool
 */
function isPost()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Returns DSN string of a particular database
 *
 * @param $databaseName
 * @return string
 */
function buildDsn($databaseName)
{
    return 'sqlsrv:Server=' . DATABASE_HOST . ',' . DATABASE_PORT . ';Database=' . $databaseName;
}

/**
 * Returns whether a string starts with another string
 *
 * @param $haystack
 * @param $needle
 * @return bool
 */
function startsWith($haystack, $needle)
{
    return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
}

/**
 * Returns whether a string ends with another string
 *
 * @param $haystack
 * @param $needle
 * @return bool
 */
function endsWith($haystack, $needle)
{
    return substr_compare($haystack, $needle, -strlen($needle)) === 0;
}

/**
 * Check query permission and throw exception if not allowed
 * @param $query
 * @throws Exception
 */
function checkQueryPermissions($query)
{
    if (getQueryType($query) == SELECT_QUERY && !ENABLE_SELECT) {
        throw new Exception('SELECT queries have been disabled');
    }
    if (getQueryType($query) == INSERT_QUERY && !ENABLE_INSERT) {
        throw new Exception('INSERT queries have been disabled');
    }
    if (getQueryType($query) == UPDATE_QUERY && !ENABLE_UPDATE) {
        throw new Exception('UPDATE queries have been disabled');
    }
    if (getQueryType($query) == DELETE_QUERY && !ENABLE_DELETE) {
        throw new Exception('DELETE queries have been disabled');
    }
    if (getQueryType($query) == SP_EXEC && !ENABLE_SP) {
        throw new Exception('Stored procedure execution has been disabled');
    }
}

/**
 * Returns the type of query that was input
 *
 * @param $query
 * @return string
 */
function getQueryType($query)
{
    if (startsWith($query, 'SELECT') || startsWith($query, 'select')) {
        return SELECT_QUERY;
    }
    if (startsWith($query, 'INSERT') || startsWith($query, 'insert')) {
        return INSERT_QUERY;
    }
    if (startsWith($query, 'UPDATE') || startsWith($query, 'update')) {
        return UPDATE_QUERY;
    }
    if (startsWith($query, 'DELETE') || startsWith($query, 'delete')) {
        return DELETE_QUERY;
    }
    return SP_EXEC;
}

/**
 * Convert output into UTF8 format
 *
 * Ref: https://stackoverflow.com/questions/19361282/why-would-json-encode-return-an-empty-string
 *
 * @param $d
 * @return array|string
 */
function utf8ize($d)
{
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = utf8ize($v);
        }
    } else if (is_string($d)) {
        return utf8_encode($d);
    }
    return $d;
}

/**
 * Simple logger which saves date wise query request logs into logs folder
 *
 * @param $data
 */
function customLog($data)
{
    try {
        if (!is_dir('logs')) {
            mkdir('logs');
        }
        $errorFile = 'logs' . DIRECTORY_SEPARATOR . date('Y-m-d') . '_query_request.log';
        if (!file_exists($errorFile)) {
            touch($errorFile);
        }
        error_log('[' . date('Y-m-d H:i O'). '] ' .$data . "\n", 3, $errorFile);
    } catch (Exception $e) {
    }
}