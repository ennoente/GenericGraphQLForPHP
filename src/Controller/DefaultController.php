<?php


namespace App\Controller;


use GraphQL\Language\Parser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    private $selectedObjects = array();

    private $result = [];

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

        /** Return the first definition */
        //return new JsonResponse($definitions[0]);

        /** Return the calculated JSON response structure */
        //return new JsonResponse($this->returnJson($definitions[0]));

        /** Return the calculated SQL query for the request */
        //return new JsonResponse(["sql" => $this->returnQuerySQL($definitions[0]), "selectedObjects" => $this->selectedObjects]);

        //$query = $this->returnQuerySQL($definitions[0])[0];
        //return new JsonResponse(["query" => $query]);

        $connection = mysqli_connect("127.0.0.1", "", "", "brabo_two", "3306");
        //$connection = mysqli_connect("127.0.0.1", "", "", "graphqldb", "3306");
        $connection->set_charset("utf8");

        $response = [];
        $queries = $this->returnQuerySQL($toplevelObject);

        for ($j = 0; $j < count($toplevelSelections); $j++) {
            $current = [];

            $query = $queries[$j];
            $result = $connection->query($query);

            if ($error = $connection->error)
                return new JsonResponse(["error" => $error], Response::HTTP_UNPROCESSABLE_ENTITY);

            $this->result = $result->fetch_assoc();

            for ($i = 0; $i < $result->num_rows; $i++) {
                array_push($current, $this->returnJson($toplevelSelections[$j], 0, $toplevelSelections[$j]["name"]["value"]));
                $this->result = $result->fetch_assoc();
            }
            $response[$toplevelSelections[$j]["name"]["value"]] = $current;
        }

        return new JsonResponse(["data" => $response]);
        //return new JsonResponse($this->returnJson($definitions[0]));


        //return new JsonResponse([$assoc, $this->returnJson($definitions[0])]);
        //return new JsonResponse(["data" => $this->returnJson($definitions[0])]);
        //return new JsonResponse([$assoc, array_keys($assoc), $values, "errorKey" => $errorKey]);

        //return new JsonResponse($connection->query($query)->num_rows);

    }


    function returnQuerySQL($queryDefinitions)
    {
        $sqlQueries = [];
        $selections = $queryDefinitions["selectionSet"]["selections"];
        for ($i = 0; $i < count($selections); $i++) {
            array_push($sqlQueries, $this->returnSQL($selections[$i]));
            $this->selectedObjects = [];
        }
        return $sqlQueries;
    }


    function returnSQL($queryNode)
    {
        $sql = $this->addSqlFields($queryNode);
        $sql .= " FROM " . $queryNode["name"]["value"];
        $sql .= $this->addJoins();

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

    function hasArgument($node, $argumentKey) {
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


    function addJoins()
    {
        $sql = "";
        foreach ($this->selectedObjects as $obj) {
            $sql .= " LEFT JOIN " . $obj["to"] . " ON " . $obj["to"] . ".id = " . $obj["from"] . "." . $obj["to"] . "_id";
        }
        return $sql;
    }


    function addSqlFields($currentNode, $branchDepth = 0, $sql = "SELECT ", $isFirstField = true)
    {
        $selections = $currentNode["selectionSet"]["selections"];
        $rootFieldName = $currentNode["name"]["value"];
        //$sql .= "SELECT ";
        //return $selections;
        // Add fields to SQL query
        for ($i = 0; $i < count($selections); $i++) {
            $field = $selections[$i];
            $fieldName = $field["name"]["value"];
            if (self::isSpread($field)) {
                //array_push($selectedObjects, $field);
                //array_push($this->selectedObjects, [$rootFieldName => $fieldName]);
                array_push($this->selectedObjects, ["from" => $rootFieldName, "to" => $fieldName]);
                $sql .= $this->addSqlFields($field, $branchDepth + 1, "", $isFirstField);
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
        /*
        for ($i = 0; $i < count($selections); $i++) {
            $field = $selections[$i];
            $fieldName = $field["name"]["value"];
            // LEFT JOIN
            if (self::isSpread($field)) {
                //$sql .= " LEFT JOIN " . $fieldName . " ON " . $fieldName . "_id" . " = " . $rootFieldName . ".id" . " ";
                $sql .= " LEFT JOIN " . $fieldName . " ON " . $fieldName . ".id" . " = " . $rootFieldName . "." . $fieldName . "_id";
            } else {
                // Current node is not object but scalar type field
                $sql .= $isFirstField ?
                    $rootFieldName . "." . $fieldName :
                    ", " . $rootFieldName . "." . $fieldName;
                $isFirstField = false;
            }
        }
        $sql .= " FROM " . $rootFieldName;
        return $sql;
        */
    }


    function returnJson($currentNode, $branchDepth = 0, $parentNodeName = "")
    {
        $selections = $currentNode["selectionSet"]["selections"];
        $newObj = [];
        for ($i = 0; $i < count($selections); $i++) {
            $field = $selections[$i];
            $fieldName = $field["name"]["value"];
            $arguments = $field["arguments"];
            if (self::isSpread($field)) {
                $newObj[$fieldName] = $this->returnJson($field, $branchDepth + 1, $fieldName);
            } else {
                //$newObj[$fieldName] = null;
                //$newObj[$fieldName] = $parentNodeName;

                $newObj[$fieldName] = $this->result[$parentNodeName . "." . $fieldName];

                //$newObj[$fieldName] = $this->getDoctrine()->getRepository($currentDoctrineEntity);
            }
        }
        return $newObj;
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