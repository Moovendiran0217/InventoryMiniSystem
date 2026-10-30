<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderHistoryRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Services\CreateOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, CreateOrder $createOrder): JsonResponse
    {
        $order = $createOrder->execute($request->validated());

        return (new OrderResource($order))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function history(OrderHistoryRequest $request): AnonymousResourceCollection
    {
        $email = mb_strtolower(trim($request->validated('email')));
        $customer = Customer::query()
            ->where('email', $email)
            ->firstOrFail();

        return OrderResource::collection(
            $customer->orders()->with(['customer', 'items.product'])->latest()->get(),
        );
    }
}
