<?php

namespace App\Validator;

use App\Repository\LedgerRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ExistingLedgerIdValidator extends ConstraintValidator
{
    public function __construct(
        private readonly LedgerRepository $ledgerRepository
    ) {
    }

    public function validate(mixed $value, Constraint|ExistingLedgerId $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        $ledger = $this->ledgerRepository->find($value);
        if ($ledger === null) {
            /** @var ExistingLedgerId $constraint */
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
