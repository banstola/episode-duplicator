<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\EpisodeDuplicateRequest;
use App\Service\EpisodeDuplicationService;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class EpisodeDuplicateController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EpisodeDuplicationService $duplicationService
    ) {}

    public function scheduleEpisodeDuplication(EpisodeDuplicateRequest $request, string $originalEpisodeUuid): JsonResponse
    {
        $this->logger->info('DUPLICATE_EPISODE_STARTED', [
            'episode_uuid' => $originalEpisodeUuid,
        ]);

        try {
            $result = $this->duplicationService->schedule($originalEpisodeUuid);

            return response()->json($result->toArray(), Response::HTTP_ACCEPTED);
        } catch (\RuntimeException $e) {
            $this->logger->error('DUPLICATE_EPISODE_FAILED', [
                'episode_uuid' => $originalEpisodeUuid,
                'error' => $e->getMessage(),
            ]);

            $status = $e->getMessage() === 'DUPLICATION_IN_PROGRESS'
                ? Response::HTTP_CONFLICT
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return response()->json(['error' => $e->getMessage()], $status);
        }
    }

    public function getDuplicationStatus(string $duplicationId): JsonResponse
    {
        try {
            $status = $this->duplicationService->getStatus($duplicationId);

            return response()->json($status->toArray());
        } catch (\RuntimeException) {
            return response()->json(
                ['error' => 'DUPLICATION_NOT_FOUND'],
                Response::HTTP_NOT_FOUND,
            );
        }
    }

    public function cancelDuplication(string $duplicationId): JsonResponse
    {
        try {
            $result = $this->duplicationService->cancel($duplicationId);

            $this->logger->info('EPISODE_DUPLICATE_CANCELLED', [
                'duplication_id' => $duplicationId,
            ]);

            return response()->json($result->toArray());
        } catch (\RuntimeException) {
            return response()->json(
                ['error' => 'DUPLICATION_NOT_FOUND'],
                Response::HTTP_NOT_FOUND,
            );
        }
    }
}
