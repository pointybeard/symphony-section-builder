<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Interfaces;

interface FieldInterface
{
    public function commit();
    public function getEntriesDataCreateTableSyntax();
}
