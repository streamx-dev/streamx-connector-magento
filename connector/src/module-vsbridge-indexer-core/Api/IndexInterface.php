<?php

namespace Divante\VsbridgeIndexerCore\Api;

interface IndexInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getAlias();

    /**
     * @return boolean
     */
    public function isNew();

    /**
     * @return \Divante\VsbridgeIndexerCore\Api\Index\TypeInterface[]
     */
    public function getTypes();

    /**
     * @param $typeName
     *
     * @return \Divante\VsbridgeIndexerCore\Api\Index\TypeInterface
     * @throws \InvalidArgumentException When the type does not exists.
     */
    public function getType($typeName);
}
