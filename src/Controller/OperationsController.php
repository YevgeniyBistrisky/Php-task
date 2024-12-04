<?php

namespace App\Controller;

use App\Dto\Request\OperationRequestDto;
use App\Exception\Validation\ValidationException;
use App\Service\OperationsService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OperationsController extends AbstractController
{
    public function __construct(
        private readonly OperationsService $operationsService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/transactions', name: 'app_operations')]
    public function transactions(Request $request): JsonResponse
    {
        try {
            $dto = $this->serializer->deserialize($request->getContent(), OperationRequestDto::class, 'json');
            $result = $this->validator->validate($dto);
            if ($result->count() > 0) {
                $formattedErrors = [];
                foreach ($result as $error) {
                    $errorMessage = $error->getMessage();
                    $propertyPath = $error->getPropertyPath();
                    $message = sprintf('%s: %s', $propertyPath, $errorMessage);
                    $formattedErrors[] = $message;
                }
                throw ValidationException::operationValidationFailed(implode('; ', $formattedErrors));
            }
            $this->operationsService->processOperationRequest($dto);
        } catch (\Exception $e) {
            $this->logger->debug("doro" . $e::class, ['exception' => $e]);
            return $this->error($e->getMessage(), $e->getCode());
        }

        return $this->json(
            Response::$statusTexts[Response::HTTP_OK],
            Response::HTTP_OK
        );
    }

    private function error(string $message, ?int $code): JsonResponse
    {
        if (empty($code)) {
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        }
        return $this->json(
            [
                'message' => $message,
            ],
            $code
        );
    }
}
