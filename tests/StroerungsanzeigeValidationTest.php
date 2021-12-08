<?php

declare(strict_types=1);
include_once __DIR__ . '/stubs/Validator.php';
class StoerungsanzeigeValidationTest extends TestCaseSymconValidation
{
    public function testValidateStoerungsanzeige(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }
    public function testValidateStoerungsanzeigeModule(): void
    {
        $this->validateModule(__DIR__ . '/../Stoerungsanzeige');
    }
}