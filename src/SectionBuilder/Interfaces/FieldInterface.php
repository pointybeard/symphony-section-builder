<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\SectionBuilder\Interfaces;

interface FieldInterface
{
    public function commit();

    public function getEntriesDataCreateTableSyntax();

    public function hasAssociations();
}
