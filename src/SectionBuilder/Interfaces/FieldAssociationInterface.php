<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\SectionBuilder\Interfaces;

interface FieldAssociationInterface
{
    public function associationParentSectionId();

    public function associationParentSectionFieldId();
}
