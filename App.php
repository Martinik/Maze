<?php

use PathFinder\Models\Graph;
use PathFinder\Models\Link;
use PathFinder\Models\MyAStar;
use PathFinder\Models\MyNode;
use PathFinder\Models\SequencePrinter;

class App
{
    const VIEWS_DIR = __DIR__ .'/views/';
    const COMPONENTS_DIR = 'components/';
    const BEGIN_VIEW_COMPONENT = 'begin.phtml';
    const END_VIEW_COMPONENT = 'end.phtml';

    protected $uri;
    protected $pathMap;
    protected $getParams;
    protected $postParams;
    protected $allParams;
    protected $actionName;
    protected $methodName;
    protected $db;
    protected $viewBag;

    public function __construct(DbService $db)
    {
        $uri = $_GET['url'] ?? "$_SERVER[REQUEST_URI]";
        $uri = explode('?', $uri)[0];
        $uri = strstr($uri, '.', true) ?: $uri;
        $this->uri = $uri;
        $this->pathMap = array_values(array_filter(explode('/', $this->uri)));

        $this->getParams = $_GET;
        $this->postParams = $_POST;
        $this->allParams = array_merge($this->getParams, $this->postParams);

        $this->db = $db;
    }

    public function handlePath()
    {
        $this->actionName = $actionName = $this->pathMap[0] ?? false;
        $this->methodName = $methodName = $actionName . 'Action';
        $this->beforeActionExec();

        if (empty($actionName))
        {
            $this->indexAction();
        } else if (method_exists($this, $methodName))
        {
            $this->$methodName();
        } else {
            $this->notFoundAction();
        }
        $this->afterActionExec();
    }

    protected function beforeActionExec()
    {
    }

    protected function afterActionExec()
    {
        include self::VIEWS_DIR. self::COMPONENTS_DIR . self::BEGIN_VIEW_COMPONENT;

        if (file_exists(self::VIEWS_DIR . $this->actionName . '.phtml')){
            $viewBag = $this->viewBag; //the initialized variable is used inside the .phtml file below
            include self::VIEWS_DIR . $this->actionName . '.phtml';

        }
        include self::VIEWS_DIR. self::COMPONENTS_DIR . self::END_VIEW_COMPONENT;
        exit();
    }

    public function indexAction()
    {
        // use this action to link to the checkFloor action for now, as it is more polished
        // this action can be reworked and expanded on in the future, if more functionality is added
    }

    public function checkFloorAction()
    {
        $startNode = false;
        if (isset($this->allParams['start']) && !empty($this->allParams['start']))
        {
            $startPointParams = explode('x', $this->allParams['start']);

            if (!empty($startPointParams))
            {
                $startX = (int)($startPointParams[0] ?? 0);
                $startY = (int)($startPointParams[1] ?? 0);
                $startZ = (int)($startPointParams[2] ?? 0);

                $startNode = new MyNode($startX, $startY, $startZ);
            }
        }
        if ($startNode)
        {
            $getFloorsDataSql = "SELECT * FROM floors";
            $allCells = $this->db->execSql($getFloorsDataSql);
            $graph = $this->generateGraph($allCells);
            $nearestExitData = $this->generateNearestExit($startNode, $graph);
        } else {
            $nearestExitData = false;
        }

        $floor = (int)($this->pathMap[1] ?? 0);

        $getFloorDataSql = "SELECT * FROM floors WHERE z = :floor";
        $floorData = $this->db->execSql($getFloorDataSql, ['floor' => $floor]);

        $matrix = [];

        foreach ($floorData as $cell)
        {
            $cellX = $cell['x'];
            $cellY = $cell['y'];
            $cellType = $cell['type'];

            if (!isset($matrix[$cellY]))
                $matrix[$cellY] = [];

            $matrix[$cellY][$cellX] = $cellType;
        }

       $this->viewBag = [
         'floor' => $floor,
         'matrix' => $matrix,
         'startNode' => $startNode,
         'nearestExitData' => $nearestExitData,
       ];
    }

    public function notFoundAction()
    {
        $this->actionName = 'notFound';
    }

    protected function generateGraph($allCells)
    {
        $floor_row_col_map = [];
        $links = [];
        $exits = [];

        foreach ($allCells as $cellInfoRow)
        {
            $cellX = $cellInfoRow['x'];
            $cellY = $cellInfoRow['y'];
            $cellZ = $cellInfoRow['z'];
            $cellType = $cellInfoRow['type'];

            if (!isset($floor_row_col_map[$cellZ]))
                $floor_row_col_map[$cellZ] = [];

            if (!isset($floor_row_col_map[$cellZ][$cellY]))
                $floor_row_col_map[$cellZ][$cellY] = [];

            // make links

            // check same floor
            $currentFloorCells = $floor_row_col_map[$cellZ];

            // check north row
            if (isset($currentFloorCells[$cellY - 1][$cellX]))
            {
                $this->addBothDirectionsLinks($cellInfoRow, $currentFloorCells[$cellY - 1][$cellX], $links);
            }
            // check south row
            if (isset($currentFloorCells[$cellY + 1][$cellX]))
            {
                $this->addBothDirectionsLinks($cellInfoRow, $currentFloorCells[$cellY + 1][$cellX], $links);
            }
            // check west row
            if (isset($currentFloorCells[$cellY][$cellX - 1]))
            {
                $this->addBothDirectionsLinks($cellInfoRow, $currentFloorCells[$cellY][$cellX - 1], $links);
            }
            // check east row
            if (isset($currentFloorCells[$cellY][$cellX + 1]))
            {
                $this->addBothDirectionsLinks($cellInfoRow, $currentFloorCells[$cellY][$cellX + 1], $links);
            }

            if (strtoupper($cellType) === MyNode::TYPE_STAIRCASE)
            {
                // check upper floor adjacent cell
                if (isset($floor_row_col_map[$cellZ + 1]) && isset($floor_row_col_map[$cellZ + 1][$cellY]) && isset($floor_row_col_map[$cellZ + 1][$cellY][$cellX]))
                {
                    $this->addBothDirectionsLinks($cellInfoRow, $floor_row_col_map[$cellZ + 1][$cellY][$cellX], $links);
                }
                // check lower floor adjacent cell
                if (isset($floor_row_col_map[$cellZ - 1]) && isset($floor_row_col_map[$cellZ - 1][$cellY]) && isset($floor_row_col_map[$cellZ - 1][$cellY][$cellX]))
                {
                    $this->addBothDirectionsLinks($cellInfoRow, $floor_row_col_map[$cellZ - 1][$cellY][$cellX], $links);
                }
            }

            $floor_row_col_map[$cellZ][$cellY][$cellX] = $cellInfoRow;
            if (strtoupper($cellType) === MyNode::TYPE_EXIT)
            {
                $exits[] = $cellInfoRow;
            }
        }

        $graph = new Graph($links, $exits);

        return $graph;
    }

    protected function generateNearestExit($startNode,Graph $graph)
    {
        $aStar = new MyAStar($graph);

        $nearestExit = false;
        $minScore = PHP_INT_MAX;

        foreach ($graph->getExits() as $exitCell)
        {
            $exitNode = new MyNode($exitCell['x'], $exitCell['y'], $exitCell['z']);
            $solution = $aStar->run($startNode, $exitNode);
            $score = SequencePrinter::calculateTotalDistance($graph, $solution);

            if ($score <= $minScore)
            {
                $nearestExit =
                    [
                        'node' => $exitNode,
                        'sequence' => $solution,
                        'sequenceMapped' => SequencePrinter::mapSequenceAsArray($solution),
                        'score' => $score,
                    ];

                $minScore = $score;
            }
        }

        return $nearestExit;
    }

    protected function addBothDirectionsLinks($cell1, $cell2, &$allLinks)
    {
        if ($cell1['type'] == MyNode::TYPE_WALL || $cell2['type'] == MyNode::TYPE_WALL)
        {
            // if one of the cells is a wall, do not link
            return;
        }

        if ($cell1['type'] == MyNode::TYPE_STAIRCASE && $cell2['type'] == MyNode::TYPE_STAIRCASE)
        {
            // if both cells are stairways, the weight is greater
            $cell1ToCell2Weight = $cell2ToCell1Weight = MyNode::STAIRCASE_TO_STAIRCASE_WEIGHT;
        } else {
            $cell1ToCell2Weight = MyNode::WEIGHTS[strtoupper($cell2['type'])];
            $cell2ToCell1Weight = MyNode::WEIGHTS[strtoupper($cell1['type'])];
        }

        $node1 = new MyNode($cell1['x'], $cell1['y'], $cell1['z']);
        $node2 = new MyNode($cell2['x'], $cell2['y'], $cell2['z']);

        $firstToSecondLink = new Link($node1,$node2, $cell1ToCell2Weight);
        $secondToFirstLink = new Link($node2,$node1, $cell2ToCell1Weight);

        $allLinks[] = $firstToSecondLink;
        $allLinks[] = $secondToFirstLink;
    }
}

?>
