<?php

class Statement
{
    public const NUMBER_OF_ROWS_BEFORE_DATA = 4;
    public const NUMBER_OF_ROWS_AFTER_DATA  = 6;

    public $data;
    public $newData;

    public function __construct($data)
    {
        $this->data    = $data;
        $this->newData = null;
    }

    public function clearData(): void
    {
        for ($row = 0; $row < self::NUMBER_OF_ROWS_BEFORE_DATA; $row++) {
            array_shift($this->data);
        }

        for ($row = 0; $row < self::NUMBER_OF_ROWS_AFTER_DATA; $row++) {
            array_pop($this->data);
        }
    }

    public function getNewData()
    {
        return $this->newData;
    }

    public function transformData()
    {
        foreach ($this->data as $line) {
            $narrativeContainsMultipleFields = $this->narrativeHasMultipleFields($line);
            $line[2]                         = $this->removeCardData($line[2]);
            $newLine[]                       = $this->generateNewLine($line, $narrativeContainsMultipleFields);
        }

        $this->newData = $newLine;
    }

    private function removeCardData($field)
    {
        return preg_replace('/\d{6}[*]{6}\d{4}/', '', $field, 1);
    }

    private function narrativeHasMultipleFields(array $line): bool
    {
        return preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/', substr($line[2], 0, 10));
    }

    private function generateNewLine($line, bool $splitField = false): array
    {
        return [
            $this->getDate($line[0]), // Transaction execution date
            $line[1], // Type
            $splitField ? $this->getData($line[2], 'payment_date') : $this->getDate($line[0]), // Payment date
            $splitField ? $this->getData($line[2], 'beneficiary') : '', // Beneficiary
            !$splitField ? $line[2] : '', // Description
            $line[3], // Payment No.
            $line[4], // Bank reference (internal)
            $line[5], //Amount
        ];
    }

    private function getDate($field): string
    {
        if (str_contains($field, '.')) {
            return $field;
        }

        return str_replace('/', '.', $field);
    }

    private function getData($field, $type)
    {
        switch ($type) {
            case 'payment_date':
                $date = substr($field, 0, 10);
                return $this->getDate($date);
            case 'beneficiary':
                return $this->getBeneficiary($field);
            default:
                exit('Unsupported "type"');
        }
    }

    private function getBeneficiary($field): string
    {
        $string = strstr($field, 'REF: ');

        $string = str_replace('REF: ', '', $string);
        $string = preg_replace('/^\d*[ ]/', '', $string);

        return $string;
    }
}
