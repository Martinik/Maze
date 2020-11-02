<?php

namespace PathFinder\Models;

use JMGQ\AStar\AbstractNode;
use JMGQ\AStar\Node;

class MyNode extends AbstractNode
{
    const TYPE_REGULAR = "R";
    const TYPE_DARK = "D";
    const TYPE_STAIRCASE = "S";
    const TYPE_EXIT = "E";
    const TYPE_WALL = "W";

    const WEIGHTS = [
        self::TYPE_REGULAR => 0.5,
        self::TYPE_DARK => 1,
        self::TYPE_STAIRCASE => 0.5,
        self::TYPE_EXIT => 0,
        self::TYPE_WALL => PHP_INT_MAX
    ];

    const STAIRCASE_TO_STAIRCASE_WEIGHT = 2;

    private $x;
    private $y;
    private $z;

    public function __construct($x, $y, $z = 0)
    {
        $this->x = $this->filterInteger($x);
        $this->y = $this->filterInteger($y);
        $this->z = $this->filterInteger($z);
    }

    /**
     * @param Node $node
     * @return MyNode
     */
    public static function fromNode(Node $node)
    {
        $coordinates = explode('x', $node->getID());

        if (count($coordinates) !== 3) {
            throw new \InvalidArgumentException('Invalid node: ' . print_r($node, true));
        }

        $x = $coordinates[0];
        $y = $coordinates[1];
        $z = $coordinates[2];

        return new MyNode($x, $y, $z);
    }

    public function getX()
    {
        return $this->x;
    }

    public function getY()
    {
        return $this->y;
    }

    public function getZ()
    {
        return $this->z;
    }

    /**
     * {@inheritdoc}
     */
    public function getID()
    {
        return $this->x . 'x' . $this->y . 'x' . $this->z;
    }

    private function filterInteger($value)
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);

        if ($integer === false) {
            throw new \InvalidArgumentException('Invalid integer: ' . print_r($value, true));
        }

        return $integer;
    }
}
