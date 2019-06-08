<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\Interfaces;

interface FieldInterface
{
    public function commit();

    public function getEntriesDataCreateTableSyntax();

    public function hasAssociations();
}
