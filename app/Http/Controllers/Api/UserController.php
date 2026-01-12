<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group User Management
 *
 * APIs for managing users and their email addresses
 */
class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Display a listing of users.
     *
     * @group User Management
     */
    public function index(Request $request): UserCollection
    {
        $perPage = min($request->integer('per_page', 15), 100);
        $search = $request->query('search');
        $sortBy = $request->query('sort', 'created_at');
        $sortOrder = $request->query('order', 'desc');

        $users = User::with('emails')
            ->search($search)
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);

        return new UserCollection($users);
    }

    /**
     * Store a newly created user.
     *
     * @group User Management
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());

            return (new UserResource($user))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified user.
     *
     * @group User Management
     */
    public function show(User $user): UserResource
    {
        $user->load('emails');
        return new UserResource($user);
    }

    /**
     * Update the specified user.
     *
     * @group User Management
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $user = $this->userService->updateUser($user, $request->validated());

            return (new UserResource($user))
                ->response()
                ->setStatusCode(Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified user.
     *
     * @group User Management
     */
    public function destroy(User $user): Response
    {
        try {
            $this->userService->deleteUser($user);

            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Send welcome emails to all user's email addresses.
     *
     * @group User Management
     */
    public function sendWelcome(User $user): JsonResponse
    {
        try {
            $this->userService->sendWelcomeEmails($user);

            return response()->json([
                'message' => 'Welcome emails queued successfully',
                'user_id' => $user->id,
            ], Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to queue welcome emails',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
