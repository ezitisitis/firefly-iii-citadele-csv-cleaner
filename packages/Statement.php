<?php

class Statement
{
    public const NUMBER_OF_ROWS_BEFORE_DATA = 4;
    public const NUMBER_OF_ROWS_AFTER_DATA  = 6;

    public array $dataForParsing;
    public array $parsedData;

    public function __construct($data)
    {
        $this->dataForParsing = $data;
        $this->parsedData     = [];
    }

    /**
     * Remove first NUMBER_OF_ROWS_BEFORE_DATA and last
     * NUMBER_OF_ROWS_AFTER_DATA lines
     *
     * @return void
     */
    public function clearData(): void
    {
        for ($row = 0; $row < self::NUMBER_OF_ROWS_BEFORE_DATA; $row++) {
            array_shift($this->dataForParsing);
        }

        for ($row = 0; $row < self::NUMBER_OF_ROWS_AFTER_DATA; $row++) {
            array_pop($this->dataForParsing);
        }
    }

    /**
     * @return array
     */
    public function getNewData(): array
    {
        return $this->newData;
    }

    /**
     * @return void
     */
    public function transformData(): void
    {
        foreach ($this->dataForParsing as $line) {
            $newLine[] = $this->parseLine($line);
        }

        $this->newData = $newLine;
    }

    /**
     * @param $field
     * @return string
     */
    private function removeCardData($field): string
    {
        return preg_replace('/\d{6}[*]{6}\d{4}/', '', $field, 1);
    }

    /**
     * @param  array  $line
     * @return bool
     */
    private function IsCardTransaction(array $line): bool
    {
        return preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/', substr($line[2], 0, 10));
    }

    /**
     * @param  array  $line
     * @return array
     */
    private function parseLine(array $line): array
    {
        return $this->isCardTransaction($line) ? $this->getCardTransactionData($line) : $this->getPaymentTransactionData($line);
    }

    /**
     * @param  array  $line
     * @return array
     */
    protected function getCardTransactionData(array $line): array
    {
        $line = $this->clearLine($line);

        return [
            $this->getDate($line),          // Date
            $this->getType($line),          // Type
            $this->getPaymentDate($line),   // Transaction date
            $this->getBeneficiary($line),   // Beneficiary
            '',                                  // IBAN
            $this->getDescription($line),   // Description
            $this->getPaymentNumber($line), // Payment No.
            $this->getReference($line),     // Bank reference (internal)
            $this->getNote($line),          // Note
            $this->getAmount($line),        // Amount
        ];
    }

    /**
     * @param $line
     * @return array
     */
    protected function clearLine($line): array
    {
        $line[2] = $this->removeCardData($line[2]);

        return $line;
    }

    /**
     * @param  array  $line
     * @return string
     */
    protected function getType(array $line): string
    {
        return $line[1];
    }

    /**
     * @param $line
     * @return string
     */
    protected function getDescription($line, bool $isCardTransaction = true): string
    {
        return $isCardTransaction
            ? $line[2]
            : $this->getTransactionDescription($line);
    }

    /**
     * @param  array  $line
     * @return string
     */
    protected function getPaymentNumber(array $line): string
    {
        return $line[3];
    }

    /**
     * @param $line
     * @return string
     */
    protected function getReference($line): string
    {
        return $line[4];
    }

    /**
     * Get note from csv's Narrative field. This lead that Note will contain
     * whole Narrative field.
     *
     * @param $line
     * @return string
     */
    protected function getNote($line): string
    {
        return $line[2];
    }

    /**
     * @param $line
     * @return string
     */
    protected function getAmount($line): string
    {
        return $line[5];
    }

    /**
     * @param  array  $line
     * @return array
     */
    protected function getPaymentTransactionData(array $line): array
    {
        $line = $this->clearLine($line);

        return [
            $this->getDate($line),                    // Date
            $this->getType($line),                    // Type
            $this->getDate($line),                    // Transaction date
            '',                                            // Beneficiary
            $this->getIBAN($line),                    // IBAN
            $this->getDescription($line, false),  // Description
            $this->getPaymentNumber($line),           // Payment No.
            $this->getReference($line),               // Bank reference (internal)
            $this->getNote($line),                    // Note
            $this->getAmount($line),                  // Amount
        ];
    }

    /**
     * @param array $line
     * @return bool|string
     */
    protected function getDate(array $line): bool|string
    {
        $dateField = $line[0];

        if (str_contains($dateField, '.')) {
            return $dateField;
        }

        return false;
    }

    /**
     * @param $line
     * @return string
     */
    protected function getPaymentDate(array $line): string
    {
        $date = substr($line[2], 0, 10);

        return str_replace('/', '.', $date);
    }

    /**
     * @param $line
     * @return string
     */
    protected function getBeneficiary(array $line): string
    {
        $field  = $line[2];
        $string = strstr($field, 'REF: ');

        $string = str_replace('REF: ', '', $string);
        $string = preg_replace('/^\d*[ ]/', '', $string);

        return $string;
    }

    /**
     * @param $line
     * @return null|string
     */
    protected function getIBAN(array $line): ?string
    {
        preg_match('/[A-Z]{2}[0-9]{2}[A-Z]{4}[0-9]{13}$/m', $line[2], $iban);
        return !empty($iban) ? $iban[0] : null;
    }

    /**
     * @param  array  $line
     * @return string
     */
    protected function getTransactionDescription(array $line): string
    {
        preg_match('/[A-Z,]{2,}[[:blank:]]{1,2}[A-Z]{2,}/', $line[2], $regexCatch);
        return !empty($regexCatch)
            ? substr($line[2], 0, strpos($line[2], $regexCatch[0]))
            : $line[2];
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return [
            'Date', 'Type', 'Transaction date', 'Beneficiary', 'IBAN', 'Description', 'Payment No.',
            'Bank reference (Internal)', 'Note', 'Amount',
        ];
    }
}