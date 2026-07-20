<?php

namespace App\Http\Controllers\Api\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\AcceptOfferRequest;
use App\Http\Requests\Recruitment\StoreOfferRequest;
use App\Http\Resources\OfferResource;
use App\Models\Offer;
use App\Services\Recruitment\CandidateService;
use App\Services\Recruitment\OfferService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class OfferController extends Controller
{
    public function __construct(
        private readonly OfferService $offers,
        private readonly CandidateService $candidates,
    ) {}

    public function index(int $candidate): JsonResponse
    {
        $model = $this->candidates->find($candidate);
        $this->authorize('viewAny', Offer::class);

        $offers = $this->offers->listForCandidate($model);

        return ApiResponse::success(
            $offers->map(fn (Offer $offer) => (new OfferResource($offer))->resolve())->all(),
        );
    }

    public function store(StoreOfferRequest $request, int $candidate): JsonResponse
    {
        $model = $this->candidates->find($candidate);
        $this->authorize('create', Offer::class);

        $offer = $this->offers->create($model, $request->validated());

        return ApiResponse::created(
            (new OfferResource($offer))->resolve(),
            'Offer created.',
        );
    }

    public function send(int $offer): JsonResponse
    {
        $model = $this->offers->find($offer);
        $this->authorize('send', $model);

        $model = $this->offers->send($model, request()->user());

        return ApiResponse::success(
            (new OfferResource($model))->resolve(),
            'Offer sent.',
        );
    }

    public function accept(AcceptOfferRequest $request, int $offer): JsonResponse
    {
        $model = $this->offers->find($offer);
        $this->authorize('accept', $model);

        $model = $this->offers->accept($model, $request->validated());

        return ApiResponse::success(
            (new OfferResource($model))->resolve(),
            'Offer accepted.',
        );
    }

    public function reject(int $offer): JsonResponse
    {
        $model = $this->offers->find($offer);
        $this->authorize('reject', $model);

        $model = $this->offers->reject($model);

        return ApiResponse::success(
            (new OfferResource($model))->resolve(),
            'Offer rejected.',
        );
    }
}
