<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWT;

class ExchangeController extends Controller
{
    public function index(Request $request)
    {
        //:::::::::::::::::::::::::::::::::::::::::: GET FILTER
        $validated = $request->validate([
            'per_page'       => 'integer|min:1|max:100',
            'keyword'        => 'nullable|string|max:255',
            'sort_by'        => 'nullable|string|in:id,rate,source,is_active,created_at,updated_at,created_by,updated_by',
            'sort_direction' => 'in:asc,desc',
        ]);

        //:::::::::::::::::::::::::::::::::::::::::: VALIDATE FILTER
        $perPage       = $validated['per_page'] ?? 10;
        $keyword       = $validated['keyword'] ?? null;
        $sortBy        = $validated['sort_by'] ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';

        //:::::::::::::::::::::::::::::::::::::::::: QUERY
        $exchangeRatesQuery = ExchangeRate::with(['createdBy:id,name', 'updatedBy:id,name']);

        //:::::::::::::::::::::::::::::::::::::::::: SEARCH
        if ($keyword) {
            $exchangeRatesQuery->where(function ($query) use ($keyword) {
                $query->where('source', 'like', "%{$keyword}%")
                    ->orWhere('rate', 'like', "%{$keyword}%");
            });
        }

        //:::::::::::::::::::::::::::::::::::::::::: SORT
        $exchangeRatesQuery->orderBy($sortBy, $sortDirection);

        //:::::::::::::::::::::::::::::::::::::::::: PAGINATION
        $exchangeRates = $exchangeRatesQuery->paginate($perPage);

        //:::::::::::::::::::::::::::::::::::::::::: RESPONSE
        return response()->json([
            'data' => $exchangeRates->items(),
            'meta' => [
                'current_page' => $exchangeRates->currentPage(),
                'per_page'     => $exchangeRates->perPage(),
                'total'        => $exchangeRates->total(),
                'last_page'    => $exchangeRates->lastPage(),
            ],
        ], 200);
    }

    public function create(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'rate'      => 'required|numeric|min:0',
                'source'    => 'required|string|min:1|max:100',
                'is_active' => 'required|boolean',
            ]);

            if ($validated['is_active']) {
                ExchangeRate::where('is_active', true)->update(['is_active' => false]);
            }

            ExchangeRate::create([
                'rate'       => $validated['rate'],
                'source'     => $validated['source'],
                'is_active'  => $validated['is_active'],
                'created_by' => JWTAuth::user()->id,
                'updated_by' => JWTAuth::user()->id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Created successfully']);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'errors' => $e->errors()
                ],
                422
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json(
                [
                    'error' => 'Failed to create'
                ],
                500
            );
        }
    }

    public function makeActive(ExchangeRate $exchangeRate)
    {
        try {
            if ($exchangeRate->is_active) {
                return response()->json(
                    [
                        'error' => 'Can not inactive this.'
                    ],
                    422
                );
            }
            DB::beginTransaction();

            ExchangeRate::where('is_active', true)->update(['is_active' => false]);

            $exchangeRate->update([
                'is_active' => true,
                'updated_by' => JWTAuth::user()->id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Created successfully']);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'errors' => $e->errors()
                ],
                422
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json(
                [
                    'error' => 'Failed to create'
                ],
                500
            );
        }
    }

    public function getExchangeRate()
    {
        $exchangeRate = ExchangeRate::where('is_active', true)
            ->latest('created_at')
            ->first();

        if (!$exchangeRate) {
            return response()->json([
                'success' => false,
                'message' => 'No active exchange rate found',
                'meta' => [
                    'requested_at' => now()->toIso8601String(),
                    'version' => '1.0',
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'             => $exchangeRate->id,
                'from_currency'  => 'USD',
                'to_currency'    => 'KHR',
                'rate'           => (float) $exchangeRate->rate,
                'source'         => $exchangeRate->source,
                'is_active'      => (bool) $exchangeRate->is_active,
                'effective_date' => $exchangeRate->created_at,
            ],
            'meta' => [
                'requested_at' => now(),
            ],
        ], 200);
    }

    public function getExchangeRateDoc()
    {
        $exchangeRate = ExchangeRate::where('is_active', true)
            ->latest('created_at')
            ->first();

        if (!$exchangeRate) {
            return response()->json([
                'success' => false,
                'message' => 'No active exchange rate found',
                'meta' => [
                    'requested_at' => now()->toIso8601String(),
                    'version' => '1.0',
                ]
            ], 404);
        }

        $endpoint = "http://localhost:8000/api/v1/exchange-rate";
        $apiKey = env('API_KEY', null);

        return response()->json([
            'data' => [
                'success' => true,
                'data' => [
                    'id'             => $exchangeRate->id,
                    'from_currency'  => 'USD',
                    'to_currency'    => 'KHR',
                    'rate'           => (float) $exchangeRate->rate,
                    'source'         => $exchangeRate->source,
                    'is_active'      => (bool) $exchangeRate->is_active,
                    'effective_date' => $exchangeRate->created_at,
                ],
                'meta' => [
                    'requested_at' => now(),
                ],
            ],
            'endpoint' => $endpoint,
            'api_key'  => $apiKey
        ], 200);
    }
}
