<?php

namespace de\xovatec\financeAnalyzer\Dto\Import\FileRecord;

class TransactionRecord
{
    /**
     *
     * @var string|null
     */
    protected ?string $bankAccountIban = null;

    /**
     *
     * @var string|null
     */
    protected ?string $transactionDate = null;

    /**
     *
     * @var string|null
     */
    protected ?string $exchangeDate = null;

    /**
     *
     * @var string|null
     */
    protected ?string $transactionType = null;

    /**
     *
     * @var string|null
     */
    protected ?string $reasonForPayment = null;

    /**
     *
     * @var string|null
     */
    protected ?string $creditorId = null;

    /**
     *
     * @var string|null
     */
    protected ?string $mandateReference = null;

    /**
     *
     * @var string|null
     */
    protected ?string $customerReference = null;

    /**
     *
     * @var string|null
     */
    protected ?string $collectorReference = null;

    /**
     *
     * @var float|null
     */
    protected ?float $debitOriginalAmount = null;

    /**
     *
     * @var float|null
     */
    protected ?float $reimbursementOfExpensesReturnDebit = null;

    /**
     *
     * @var string|null
     */
    protected ?string $beneficiaryPayee = null;

    /**
     *
     * @var string|null
     */
    protected ?string $creditorIban = null;

    /**
     *
     * @var string|null
     */
    protected ?string $creditorBic = null;

    /**
     *
     * @var float|null
     */
    protected ?float $amount = null;

    /**
     *
     * @var string|null
     */
    protected ?string $currency = null;

    /**
     *
     * @var string|null
     */
    protected ?string $status = null;

    /**
     *
     * @return string|null
     */
    public function getBankAccountIban(): ?string
    {
        return $this->bankAccountIban;
    }

    /**
     *
     * @return string|null
     */
    public function getTransactionDate(): ?string
    {
        return $this->transactionDate;
    }

    /**
     *
     * @return string|null
     */
    public function getExchangeDate(): ?string
    {
        return $this->exchangeDate;
    }

    /**
     *
     * @return string|null
     */
    public function getTransactionType(): ?string
    {
        return $this->transactionType;
    }

    /**
     *
     * @return string|null
     */
    public function getReasonForPayment(): ?string
    {
        return $this->reasonForPayment;
    }

    /**
     *
     * @return string|null
     */
    public function getCreditorId(): ?string
    {
        return $this->creditorId;
    }

    /**
     *
     * @return string|null
     */
    public function getMandateReference(): ?string
    {
        return $this->mandateReference;
    }

    /**
     *
     * @return string|null
     */
    public function getCustomerReference(): ?string
    {
        return $this->customerReference;
    }

    /**
     *
     * @return string|null
     */
    public function getCollectorReference(): ?string
    {
        return $this->collectorReference;
    }

    /**
     *
     * @return float|null
     */
    public function getDebitOriginalAmount(): ?float
    {
        return $this->debitOriginalAmount;
    }

    /**
     *
     * @return float|null
     */
    public function getReimbursementOfExpensesReturnDebit(): ?float
    {
        return $this->reimbursementOfExpensesReturnDebit;
    }

    /**
     *
     * @return string|null
     */
    public function getBeneficiaryPayee(): ?string
    {
        return $this->beneficiaryPayee;
    }

    /**
     *
     * @return string|null
     */
    public function getCreditorIban(): ?string
    {
        return $this->creditorIban;
    }

    /**
     *
     * @return string|null
     */
    public function getCreditorBic(): ?string
    {
        return $this->creditorBic;
    }

    /**
     *
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     *
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     *
     * @param string|null $bankAccountIban
     * @return self
     */
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
    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     *
     * @return array
     */
    public function getRecord(): array
    {
        return [
            $this->bankAccountIban,
            $this->transactionDate,
            $this->exchangeDate,
            $this->transactionType,
            $this->reasonForPayment,
            $this->creditorId,
            $this->mandateReference,
            $this->customerReference,
            $this->collectorReference,
            $this->debitOriginalAmount,
            $this->reimbursementOfExpensesReturnDebit,
            $this->beneficiaryPayee,
            $this->creditorIban,
            $this->creditorBic,
            $this->amount,
            $this->currency,
        ];
    }

    /**
     *
     * @return string
     */
    public function getHashIdentifier(): string
    {
        $values = array_map(
            static fn ($value) => $value === null ? '' : (string)$value,
            $this->getRecord()
        );

        return hash('sha256', implode('|', $values));
    }
}
