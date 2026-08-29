<?php

declare(strict_types=1);

namespace JovianGtkParityFixture;

class ChildWin extends ParentWin
{
    public static function new(int $application): self
    {
        return new self();
    }
}
