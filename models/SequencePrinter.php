<?php

namespace PathFinder\Models;

class SequencePrinter
{
    public static function printSequence(Graph $graph, array $sequence)
    {
        $nodesAsString = array();

        foreach ($sequence as $node) {
            $nodesAsString[] = self::getNodeAsString($node);
        }

        if (!empty($nodesAsString)) {
            echo implode(PHP_EOL, $nodesAsString);
            echo PHP_EOL;
        }
    }

    private static function getNodeAsString(MyNode $node)
    {
        return "({$node->getX()}, {$node->getY()}, {$node->getZ()})";
    }

    public static function mapSequenceAsArray(array $sequence)
    {
        $floor_row_col_map = [];

        foreach ($sequence as $node) {
            if (!isset($floor_row_col_map[$node->getZ()]))
                $floor_row_col_map[$node->getZ()] = [];

            if (!isset($floor_row_col_map[$node->getZ()][$node->getY()]))
                $floor_row_col_map[$node->getZ()][$node->getY()] = [];

            $floor_row_col_map[$node->getZ()][$node->getY()][$node->getX()] = $node;
        }

        return $floor_row_col_map;
    }

    public static function calculateTotalDistance(Graph $graph, array $sequence)
    {
        if (count($sequence) < 2) {
            return 0;
        }

        $totalDistance = 0;

        $previousNode = array_shift($sequence);
        foreach ($sequence as $node) {
            $totalDistance += $graph->getLink($previousNode, $node)->getDistance();

            $previousNode = $node;
        }

        return $totalDistance;
    }
}
