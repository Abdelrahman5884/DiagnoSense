<?php

namespace App\Http\Controllers\V1\Patients;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Http\Resources\SearchResource;
use App\Http\Responses\ApiResponse;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    public function __invoke(SearchRequest $request): JsonResponse
    {
        try {
            $term = $request->validated('search');
            $doctorId = auth()->user()->doctor->id;
            $patients = $this->searchService->search(
                doctorId: $doctorId,
                term: $term
            );

            if ($patients->isEmpty()) {
                return ApiResponse::error('No patients match your search', null, 404);
            }

            return ApiResponse::success(
                'Patients retrieved successfully',
                SearchResource::collection($patients)->response()->getData(true),
                200
            );
        } catch (\Exception $e) {
            \Log::error('Search Error: '.$e->getMessage());

            return ApiResponse::error('An error occurred during search', null, 500);
        }
    }
}
