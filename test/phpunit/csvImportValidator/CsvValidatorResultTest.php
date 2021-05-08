<?php

/**
 * @internal
 * @covers \CsvValidatorResult
 */
class CsvValidatorResultTest extends \PHPUnit\Framework\TestCase
{
    protected $vdbcon;
    protected $context;

    public function setUp(): void
    {
        $this->context = sfContext::getInstance();
    }

    public function testSetTitleOption()
    {
        $csvValidator = new CsvValidatorResult();
        $this->assertSame('', $csvValidator->getTitle());

        $csvValidator->setTitle('Title');
        $this->assertSame('Title', $csvValidator->getTitle());

        $csvValidator = new CsvValidatorResult('Title');
        $this->assertSame('Title', $csvValidator->getTitle());
    }

    public function testSetFilenameOption()
    {
        $csvValidator = new CsvValidatorResult();
        $this->assertSame('', $csvValidator->getFilename());

        $csvValidator->setFilename('Filename');
        $this->assertSame('Filename', $csvValidator->getFilename());

        $csvValidator = new CsvValidatorResult('Title', 'Filename');
        $this->assertSame('Filename', $csvValidator->getFilename());
    }

    public function testSetClassnameOption()
    {
        $csvValidator = new CsvValidatorResult();
        $this->assertSame('', $csvValidator->getClassname());

        $csvValidator->setClassname('Classname');
        $this->assertSame('Classname', $csvValidator->getClassname());

        $csvValidator = new CsvValidatorResult('Title', 'Filename', 'Classname');
        $this->assertSame('Classname', $csvValidator->getClassname());
    }

    public function testSetVerbosityOption()
    {
        $csvValidator = new CsvValidatorResult();
        $this->assertSame(false, $csvValidator->getVerbosity());

        $csvValidator->setVerbosity(true);
        $this->assertSame(true, $csvValidator->getVerbosity());

        $csvValidator = new CsvValidatorResult('Title', 'Filename', 'Classname', true);
        $this->assertSame(true, $csvValidator->getVerbosity());
    }

    public function testSetStatus()
    {
        $csvValidator = new CsvValidatorResult('Title', 'Filename', 'Classname', true);
        // INFO status by default.
        $this->assertSame(CsvValidatorResult::RESULT_INFO, $csvValidator->getStatus());

        $csvValidator->setStatusWarn();
        $this->assertSame(CsvValidatorResult::RESULT_WARN, $csvValidator->getStatus());

        $csvValidator->setStatusError();
        $this->assertSame(CsvValidatorResult::RESULT_ERROR, $csvValidator->getStatus());

        $csvValidator->setStatusWarn();
        // Should still be in error state.
        $this->assertSame(CsvValidatorResult::RESULT_ERROR, $csvValidator->getStatus());

        $csvValidator = new CsvValidatorResult('Title', 'Filename', 'Classname', true);
        $csvValidator->setStatus(CsvValidatorResult::RESULT_ERROR);
        $this->assertSame(CsvValidatorResult::RESULT_ERROR, $csvValidator->getStatus());
    }

    public function testSetResult()
    {
        $csvValidator = new CsvValidatorResult('Title', 'Filename', 'Classname', true);
        $csvValidator->addResult('Result');
        $this->assertSame(['Result'], $csvValidator->getResults());
    }

    public function testSetDetail()
    {
        $csvValidator = new CsvValidatorResult('Title', 'Filename', 'Classname', true);
        $csvValidator->addDetail('Detail');
        $this->assertSame(['Detail'], $csvValidator->getDetails());
    }

    public function testFormatStatus()
    {
        //$csvValidator = new CsvValidatorResult('Title', 'Filename', 'Classname', true);
        $this->assertSame('info', CsvValidatorResult::formatStatus(CsvValidatorResult::RESULT_INFO));
        $this->assertSame('warning', CsvValidatorResult::formatStatus(CsvValidatorResult::RESULT_WARN));
        $this->assertSame('error', CsvValidatorResult::formatStatus(CsvValidatorResult::RESULT_ERROR));
    }

    public function testToArray()
    {
        $csvValidator = new CsvValidatorResult('Title', 'Filename', 'Classname', true);
        $result = $csvValidator->toArray();
        $this->assertSame(
            [
                'title' => 'Title',
                'status' => 0,
                'results' => [],
                'details' => [],
            ],
            $result
        );
    }
}
