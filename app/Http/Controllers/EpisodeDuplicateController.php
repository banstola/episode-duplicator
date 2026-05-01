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
        private readonly LoggerInterface           $logger,
        private readonly EpisodeDuplicationService $duplicationService
    ) {
    }

    public function scheduleEpisodeDuplication(EpisodeDuplicateRequest $request, string $originalEpisodeUuid): JsonResponse
    {
        $this->logger->info('DUPLICATE_EPISODE_STARTED', [
            'episode_uuid' => $originalEpisodeUuid,
        ]);

        try {
            $result = $this->duplicationService->schedule($originalEpisodeUuid);
            return response()->json($result->toArray(), Response::HTTP_ACCEPTED);
        } catch (\Throwable $exception) {

            $this->logger->error('DUPLICATE_EPISODE_FAILED', [
                'exception' => $exception->getMessage(),
            ]);


        }


        return \response()->json(
            [
                'error' => 'UNABLE_TO_PROCESS_REQUEST'
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );


    }

    public function getDuplicationStatus(EpisodeDuplicateRequest $request, string $duplicationId): JsonResponse
    {
        $status = $this->duplicationService->getStatus($duplicationId);

        return response()->json($status->toArray());

    }

    public function cancelDuplication(EpisodeDuplicateRequest $request, string $duplicationId): JsonResponse
    {
        $this->logger->info('EPISODE_DUPLICATE_CANCELLED', [
            'duplicate_episode_id' => $duplicationId,
        ]);
        $result = $this->duplicationService->cancel($duplicationId);

        return response()->json($result->toArray());
    }
}
