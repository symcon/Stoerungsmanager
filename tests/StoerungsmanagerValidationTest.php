<?php

declare(strict_types=1);
include_once __DIR__ . '/stubs/Validator.php';
class StoerungsmanagerValidationTest extends TestCaseSymconValidation
{
    public function testValidateStoerungsmanager(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }
    public function testValidateStoerungsmanagerModule(): void
    {
        $this->validateModule(__DIR__ . '/../Stoerungsmanager');
    }
}