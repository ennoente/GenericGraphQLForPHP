<?php


namespace App\Controller;


use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    private $selectedObjects = array();

    private $result = [];

    private $connection;

    public function __construct()
    {
        $this->connection = mysqli_connect("127.0.0.1", "", "", "brabo_two", "3306");
        $this->connection->set_charset("utf8");
    }

    /**
     * @Route(path="/graphql", methods={"POST"})
     */
    public function handleGraphQL(Request $request)
    {
        $body = $request->getContent();
        $query = json_decode($body, true);

        $documentNode = Parser::parse($query["query"]);
        $docNodeAsResource = $documentNode->toArray(true);
        $definitions = $docNodeAsResource["definitions"];

        $toplevelObject = $definitions[0];
        $toplevelSelections = $toplevelObject["selectionSet"]["selections"];

        /** Return the DocumentNode calculated from the request query */
        //return new JsonResponse($docNodeAsResource);

        //return new JsonResponse(self::columnExistsInTable("sys_hersteller", "sys_maschine"));
        //return new JsonResponse(self::getCombinedTableName("sys_maschine", "document"));

        /** Return the first definition */
        //return new JsonResponse($definitions[0]);

        //$this->columnExistsInTable("name", "sys_maschine");
        //$this->columnExistsInTable("id", "sys_maschine");
        //return new JsonResponse(["queriedColumnsInTable" => $this->queriedColumnsInTable,
        //    "bla" => array_key_exists("sys_maschine", $this->queriedColumnsInTable),
        //    "bla2" => in_array("name", $this->queriedColumnsInTable["sys_maschine"])]);
        //return new JsonResponse($this->columnExistsInTable("name", "sys_maschine"));

        /*
        $arr = [];
        $arr["obj"] = [];
        array_push($arr["obj"], "Hallo");
        array_push($arr["obj"], "Hallo2");
        return new JsonResponse($arr);
        */


        /** Return the calculated JSON response structure */
        //return new JsonResponse($this->returnJson($definitions[0]));

        /** Return the calculated SQL query for the request */
        //return new JsonResponse(["sql" => $this->returnQuerySQL($definitions[0]), "selectedObjects" => $this->selectedObjects]);

        //$query = $this->returnQuerySQL($definitions[0])[0];
        //return new JsonResponse(["query" => $query]);

        //$this->connection = mysqli_connect("127.0.0.1", "", "", "graphqldb", "3306");

        $response = [];

        //$queries = $this->returnQuerySQL($toplevelObject);


        //for ($i = 0; $i < count($toplevelSelections); $i++) {
        //for ($j = 0; $j < count($queries); $j++) {
        for ($j = 0; $j < count($toplevelSelections); $j++) {
            $current = [];
            $this->selectedObjects = [];

            //$query = $queries[$j];
            $query = $this->returnSQL($toplevelSelections[$j]);

            //$result = $this->connection->query($query);
            $result = $this->queryDb($query);

            if ($error = $this->connection->error)
                return new JsonResponse(["error" => $error], Response::HTTP_UNPROCESSABLE_ENTITY);

            $this->result = $result->fetch_assoc();

            for ($i = 0; $i < $result->num_rows; $i++) {
                array_push($current, $this->returnJson($toplevelSelections[$j], 0, $toplevelSelections[$j]["name"]["value"], $this->result));
                $this->result = $result->fetch_assoc();
            }
            $response[$toplevelSelections[$j]["name"]["value"]] = $current;
        }
        //}

        //return new JsonResponse(["connectionStats" => $this->connection->get_connection_stats(), "count" => $this->dbCount, "data" => $response]);
        return new JsonResponse(["data" => $response]);
        //return new JsonResponse($this->returnJson($definitions[0]));


        //return new JsonResponse([$assoc, $this->returnJson($definitions[0])]);
        //return new JsonResponse(["data" => $this->returnJson($definitions[0])]);
        //return new JsonResponse([$assoc, array_keys($assoc), $values, "errorKey" => $errorKey]);

        //return new JsonResponse($this->connection->query($query)->num_rows);

    }


    function returnQuerySQL($queryDefinitions)
    {
        $sqlQueries = [];
        $selections = $queryDefinitions["selectionSet"]["selections"];
        for ($i = 0; $i < count($selections); $i++) {
            array_push($sqlQueries, $this->returnSQL($selections[$i]));
            //$this->selectedObjects = [];
        }
        return $sqlQueries;
    }

    private $queriedColumnsInTable = [];
    private $columnNotInTable = [];

    function columnExistsInTable($columnName, $tableName): bool
    {
        if (array_key_exists($tableName, $this->queriedColumnsInTable))
            if (in_array($columnName, $this->queriedColumnsInTable[$tableName])) return true;

        //var_dump("_______");
        //var_dump(array_key_exists($tableName, $this->columnNotInTable));

        if (array_key_exists($tableName, $this->columnNotInTable)) {
            //var_dump("$columnName exists in columnNotInTable: " . in_array($columnName, $this->columnNotInTable));
            if (in_array($columnName, $this->columnNotInTable[$tableName])) {
                //var_dump("SQL");
                return false;
            }
        }

        $sql = "SHOW COLUMNS FROM " . $tableName . " LIKE " . "'" . $columnName . "'";

        //$results = $this->connection->query($sql);
        $results = $this->queryDb($sql);

        //var_dump($this->columnNotInTable);
        //var_dump(array_key_exists($columnName, $this->columnNotInTable));

        if ($results->num_rows > 0) {
            if (!array_key_exists($tableName, $this->queriedColumnsInTable)) {
                //array_push($this->queriedColumnsInTable, $tableName => array());
                $this->queriedColumnsInTable[$tableName] = [];
                //var_dump($this->queriedColumnsInTable);
            } else
                //var_dump("In queried columns!");

                //$this->queriedColumnsInTable[$tableName] = $columnName;
                array_push($this->queriedColumnsInTable[$tableName], $columnName);

            return true;
        } else {
            if (!array_key_exists($tableName, $this->columnNotInTable)) {
                //var_dump("Adding $tableName");
                $this->columnNotInTable[$tableName] = [];
            }
            //var_dump("Adding columnName $columnName to table $tableName");
            array_push($this->columnNotInTable[$tableName], $columnName);
            //var_dump($this->columnNotInTable);
        }

        return false;
        //return $results->num_rows > 0;
    }

    private $tableExistsInDatabaseCount = 0;

    private $tablesInDb = [];
    private $tablesNotInDb = [];

    function tableExistsInDatabase($tableName): bool
    {
        if (in_array($tableName, $this->tablesInDb)) return true;
        if (in_array($tableName, $this->tablesNotInDb)) return false;


        $this->tableExistsInDatabaseCount++;
        $sql = "SHOW TABLES LIKE '$tableName'";

        //$result = $this->connection->query($sql);
        $result = $this->queryDb($sql);
        //return !is_null($result);

        // Tables seems to exist
        if ($result->num_rows > 0) {
            array_push($this->tablesInDb, $tableName);
            return true;
        } else {
            array_push($this->tablesNotInDb, $tableName);
            return false;
        }
    }

    private $dbCount = 0;

    function queryDb($sql)
    {
        $this->dbCount++;
        return $this->connection->query($sql);
    }


    function returnSQL($queryNode)
    {
        $sql = $this->addSqlFields($queryNode);
        $sql .= " FROM " . $queryNode["name"]["value"];
        $sql .= $this->addJoins();

        if (self::hasArgument($queryNode, "id"))
            $sql .= $this->addIdClause($queryNode["name"]["value"], self::getArgumentValue($queryNode, "id"));

        if (self::hasArgument($queryNode, "first"))
            $sql .= $this->addLimit(self::getArgumentValue($queryNode, "first"));

        if (self::hasArgument($queryNode, "offset"))
            $sql .= $this->addOffset(self::getArgumentValue($queryNode, "offset"));

        return $sql;
    }

    function getArgumentValue($node, $argumentKey)
    {
        $arguments = $node["arguments"];

        foreach ($arguments as $argument) {
            if ($argument["name"]["value"] == $argumentKey)
                return $argument["value"]["value"];
        }

        return -1;
    }

    function hasArgument($node, $argumentKey)
    {
        $arguments = $node["arguments"];

        foreach ($arguments as $argument) {
            if ($argument["name"]["value"] == $argumentKey)
                return true;
        }

        return false;
    }

    function addLimit($value)
    {
        return " LIMIT " . $value;
    }

    function addOffset($value)
    {
        return " OFFSET " . $value;
    }

    function getCombinedTableName($ass1, $ass2)
    {
        $combination1 = $ass1 . "_" . $ass2;
        $combination2 = $ass2 . "_" . $ass1;

        if (self::tableExistsInDatabase($combination1))
            return $combination1;
        else if (self::tableExistsInDatabase($combination2))
            return $combination2;

        return "ERROR";
    }


    function addJoins()
    {
        $sql = "";
        foreach ($this->selectedObjects as $obj) {
            switch ($obj["type"]) {
                case "one-to-one":
                    $sql .= " LEFT JOIN " . $obj["to"] . " ON " . $obj["to"] . ".id = " . $obj["from"] . "." . $obj["to"] . "_id";
                    break;

                /*
            case "many-to-many":
                $from = $obj["from"];
                $to = $obj["to"];
                $combinedTableName = self::getCombinedTableName($from, $to);
                $sql .= " LEFT JOIN $combinedTableName ON $from" . ".id = $combinedTableName" . "." . $from . "_id";
                $sql .= " LEFT JOIN $to ON $combinedTableName" . "." . $to . "_id = " . $to . ".id";
                */
            }
        }
        return $sql;
    }


    function addIdClause($nodeName, $id)
    {
        return " WHERE " . $nodeName . ".id" . " = " . $id;
    }


    function addSqlFields($currentNode, $branchDepth = 0, $sql = "SELECT ", $isFirstField = true)
    {
        $selections = $currentNode["selectionSet"]["selections"];
        $rootFieldName = $currentNode["name"]["value"];
        //$sql .= "SELECT ";
        //return $selections;
        // Add fields to SQL query

        $sql .= $isFirstField ?
            $rootFieldName . "." . "id" :
            ", " . $rootFieldName . "." . "id";

        $sql .= " AS " . "'" . $rootFieldName . "." . "id" . "'";
        $isFirstField = false;

        for ($i = 0; $i < count($selections); $i++) {
            $field = $selections[$i];
            $fieldName = $field["name"]["value"];

            if ($fieldName == "id") {
                continue;
            }

            if (self::isSpread($field)) {
                // Requested fieldname exists as column in table
                if (self::columnExistsInTable($fieldName . "_id", $rootFieldName)) {
                    array_push($this->selectedObjects, ["type" => "one-to-one", "from" => $rootFieldName, "to" => $fieldName]);

                    $sql .= $this->addSqlFields($field, $branchDepth + 1, "", $isFirstField);
                } else {
                    // Many to many (for now) TODO

                    array_push($this->selectedObjects, ["type" => "many-to-many", "from" => $rootFieldName, "to" => $fieldName]);
                }
            } else {
                // Current node is not object but scalar type field
                $sql .= $isFirstField ?
                    $rootFieldName . "." . $fieldName :
                    ", " . $rootFieldName . "." . $fieldName;

                $sql .= " AS " . "'" . $rootFieldName . "." . $fieldName . "'";
                $isFirstField = false;
            }
        }
        return $sql;
    }


    function returnJson($currentNode, $branchDepth = 0, $parentNodeName = "", $resultSet = [])
    {
        $selections = $currentNode["selectionSet"]["selections"];
        $newObj = [];
        for ($i = 0; $i < count($selections); $i++) {
            $field = $selections[$i];
            $fieldName = $field["name"]["value"];
            $arguments = $field["arguments"];

            if (self::isSpread($field)) {
                $key = $parentNodeName . "." . $fieldName;
                //if (array_key_exists($key, $resultSet))
                if (self::columnExistsInTable($fieldName . "_id", $parentNodeName))
                    $newObj[$fieldName] = $this->returnJson($field, $branchDepth + 1, $fieldName, $resultSet);
                else
                    //$newObj[$fieldName] = $this->addSqlFields($currentNode);
                    //$newObj[$fieldName] = "My id is '" . $resultSet[$parentNodeName . ".id"] . "', parentNodeName=" . $parentNodeName;

                    //$newObj[$fieldName] = array();

                    $newObj[$fieldName] = self::getManyToManyAsArray($field, $fieldName, $parentNodeName, $resultSet[$parentNodeName . ".id"]);
            } else {
                //$newObj[$fieldName] = null;
                //$newObj[$fieldName] = $parentNodeName;

                //if (array_key_exists($key, $this->result))
                //    $newObj[$fieldName] = $this->result[$parentNodeName . "." . $fieldName];
                $newObj[$fieldName] = $resultSet[$parentNodeName . "." . $fieldName];

                //$newObj[$fieldName] = json_encode($this->selectedObjects);
                //$newObj[$fieldName] = json_encode(self::createManyToManyQuery());
                //$newObj[$fieldName] = json_encode($this->returnJ)

                // Is the field inside the selectedObjects array as a many-to-many object?

                //$newObj[$fieldName] = $this->getDoctrine()->getRepository($currentDoctrineEntity);
            }
        }
        return $newObj;
    }

    function getManyToManyAsArray($node, $fieldName, $parentNodeName, $nodeId)
    {
        //$selections = $node["selectionSet"]["selections"];
        $sql = $this->addSqlFields($node);
        $sql .= " FROM " . $parentNodeName;

        $from = $parentNodeName;
        $to = $fieldName;

        //return $fieldName . ", " . $parentNodeName;

        $combinedTableName = self::getCombinedTableName($from, $to);
        $sql .= " INNER JOIN $combinedTableName ON $from" . ".id = $combinedTableName" . "." . $from . "_id";
        $sql .= " INNER JOIN $to ON $combinedTableName" . "." . $to . "_id = " . $to . ".id";

        $sql .= $this->addIdClause($parentNodeName, $nodeId);

        if (self::hasArgument($node, "first"))
            $sql .= $this->addLimit(self::getArgumentValue($node, "first"));

        //return $sql;

        //$result = $this->connection->query($sql);
        $result = $this->queryDb($sql);
        $resultArray = [];

        while ($row = $result->fetch_assoc()) {
            array_push($resultArray, $row);
        }

        //return $sql;
        //return json_encode($resultArray);

        return count($resultArray) > 0 ? $resultArray : null;

        //return $this->connection->query($sql)->fetch_assoc();
    }

    function createManyToManyQuery()
    {

    }


    public static function isSpread($node): bool
    {
        return array_key_exists("selectionSet", $node);
    }


    // TODO Build actual function
    public static function isManyToMany($node, $node2): bool
    {
        return false;
    }
}