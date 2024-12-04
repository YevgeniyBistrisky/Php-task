<?php

namespace App\Dto\Request;

use App\Constant\OperationType;
use App\Entity\Constant\Currency;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator as AppAssert;

class OperationRequestDto
{
    public function __construct(
        #[Assert\NotBlank()]
        #[Assert\Positive()]
        public readonly null|float|string $amount,
        #[Assert\NotBlank()]
        #[Assert\Type(type: Currency::class, message: 'The value you selected is not a valid choice.')]
        public readonly null|Currency|string $currency,
        #[Assert\NotBlank()]
        #[Assert\Type(type: OperationType::class, message: 'The value you selected is not a valid choice.')]
        public readonly null|OperationType|string $operationType,
        #[Assert\Length(min: 1, max: 255)]
        #[Assert\NotBlank()]
        public readonly ?string $transactionId,
        #[AppAssert\ExistingLedgerId()]
        #[Assert\NotBlank()]
        public readonly ?string $ledgerId
    ) {
    }
}
