<?php

namespace de\xovatec\financeAnalyzer\Dto\Import\FileRecord;

use League\Csv\Serializer;

class Camt52V8CsvSetter extends TransactionRecord
{
    /**
     *
     * @param string|null $bankAccountIban
     * @return self
     */
    #[Serializer\MapCell(column: 'Auftragskonto')]
    public function setBankAccountIban(?string $bankAccountIban): self
    {
        $this->bankAccountIban = $bankAccountIban;
        return $this;
    }

    /**
     *
     * @param string|null $transactionDate
     * @return self
     */
    #[Serializer\MapCell(column: 'Buchungstag', cast: '@format_date', convertEmptyStringToNull: true)]
    public function setTransactionDate(?string $transactionDate): self
    {
        $this->transactionDate = $transactionDate;
        return $this;
    }

    /**
     *
     * @param string|null $exchangeDate
     * @return self
     */
    #[Serializer\MapCell(column: 'Valutadatum', cast: '@format_date', convertEmptyStringToNull: true)]
    public function setExchangeDate(?string $exchangeDate): self
    {
        $this->exchangeDate = $exchangeDate;
        return $this;
    }

    /**
     *
     * @param string|null $transactionType
     * @return self
     */
    #[Serializer\MapCell(column: 'Buchungstext')]
    public function setTransactionType(?string $transactionType): self
    {
        $this->transactionType = $transactionType;
        return $this;
    }

    /**
     *
     * @param string|null $reasonForPayment
     * @return self
     */
    #[Serializer\MapCell(column: 'Verwendungszweck', cast: '@utf8_encode', convertEmptyStringToNull: true)]
    public function setReasonForPayment(?string $reasonForPayment): self
    {
        $this->reasonForPayment = $reasonForPayment;
        return $this;
    }

    /**
     *
     * @param string|null $creditorId
     * @return self
     */
    #[Serializer\MapCell(column: 'Glaeubiger ID')]
    public function setCreditorId(?string $creditorId): self
    {
        $this->creditorId = $creditorId;
        return $this;
    }

    /**
     *
     * @param string|null $mandateReference
     * @return self
     */
    #[Serializer\MapCell(column: 'Mandatsreferenz')]
    public function setMandateReference(?string $mandateReference): self
    {
        $this->mandateReference = $mandateReference;
        return $this;
    }

    /**
     *
     * @param string|null $customerReference
     * @return self
     */
    #[Serializer\MapCell(column: 'Kundenreferenz (End-to-End)')]
    public function setCustomerReference(?string $customerReference): self
    {
        $this->customerReference = $customerReference;
        return $this;
    }

    /**
     *
     * @param string|null $collectorReference
     * @return self
     */
    #[Serializer\MapCell(column: 'Sammlerreferenz')]
    public function setCollectorReference(?string $collectorReference): self
    {
        $this->collectorReference = $collectorReference;
        return $this;
    }

    /**
     *
     * @param float|null $debitOriginalAmount
     * @return self
     */
    #[Serializer\MapCell(column: 'Lastschrift Ursprungsbetrag', cast: '@replace_comma', convertEmptyStringToNull: true)]
    public function setDebitOriginalAmount(?float $debitOriginalAmount): self
    {
        $this->debitOriginalAmount = $debitOriginalAmount;
        return $this;
    }

    /**
     *
     * @param float|null $reimbursementOfExpensesReturnDebit
     * @return self
     */
    #[Serializer\MapCell(column: 'Auslagenersatz Ruecklastschrift', cast: '@replace_comma', convertEmptyStringToNull: true)]
    public function setReimbursementOfExpensesReturnDebit(?float $reimbursementOfExpensesReturnDebit): self
    {
        $this->reimbursementOfExpensesReturnDebit = $reimbursementOfExpensesReturnDebit;
        return $this;
    }

    /**
     *
     * @param string|null $beneficiaryPayee
     * @return self
     */
    #[Serializer\MapCell(column: 'Beguenstigter/Zahlungspflichtiger')]
    public function setBeneficiaryPayee(?string $beneficiaryPayee): self
    {
        $this->beneficiaryPayee = $beneficiaryPayee;
        return $this;
    }

    /**
     *
     * @param string|null $creditorIban
     * @return self
     */
    #[Serializer\MapCell(column: 'Kontonummer/IBAN')]
    public function setCreditorIban(?string $creditorIban): self
    {
        $this->creditorIban = $creditorIban;
        return $this;
    }

    /**
     *
     * @param string|null $creditorBic
     * @return self
     */
    #[Serializer\MapCell(column: 'BIC (SWIFT-Code)')]
    public function setCreditorBic(?string $creditorBic): self
    {
        $this->creditorBic = $creditorBic;
        return $this;
    }

    /**
     *
     * @param float|null $amount
     * @return self
     */
    #[Serializer\MapCell(column: 'Betrag', cast: '@replace_comma', convertEmptyStringToNull: true)]
    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     *
     * @param string|null $currency
     * @return self
     */
    #[Serializer\MapCell(column: 'Waehrung')]
    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     *
     * @param string|null $status
     * @return self
     */
    #[Serializer\MapCell(column: 'Info')]
    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }
}
