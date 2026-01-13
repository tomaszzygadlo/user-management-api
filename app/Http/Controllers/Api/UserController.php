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
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="first_name", type="string", maxLength=255, example="John"),
 *     @OA\Property(property="last_name", type="string", maxLength=255, example="Doe"),
 *     @OA\Property(property="full_name", type="string", readOnly=true, example="John Doe"),
 *     @OA\Property(property="phone_number", type="string", maxLength=20, example="+1234567890"),
 *     @OA\Property(property="emails", type="array", @OA\Items(ref="#/components/schemas/Email")),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 *
 * @OA\Schema(
 *     schema="Email",
 *     title="Email",
 *     description="Email model",
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="is_primary", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 *
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
     * @OA\Get(
     *      path="/api/users",
     *      operationId="getUsersList",
     *      tags={"Users"},
     *      summary="Get list of users",
     *      description="Returns list of users",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="Number of items per page",
     *          required=false,
     *          @OA\Schema(type="integer", default=15)
     *      ),
     *      @OA\Parameter(
     *          name="search",
     *          in="query",
     *          description="Search term",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="sort",
     *          in="query",
     *          description="Sort by column",
     *          required=false,
     *          @OA\Schema(type="string", default="created_at")
     *      ),
     *      @OA\Parameter(
     *          name="order",
     *          in="query",
     *          description="Sort order",
     *          required=false,
     *          @OA\Schema(type="string", default="desc")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/UserCollection")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      )
     * )
     *
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
     * @OA\Post(
     *      path="/api/users",
     *      operationId="storeUser",
     *      tags={"Users"},
     *      summary="Store new user",
     *      description="Returns user data",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/StoreUserRequest")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/UserResource")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      )
     * )
     *
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
     * @OA\Get(
     *      path="/api/users/{id}",
     *      operationId="getUserById",
     *      tags={"Users"},
     *      summary="Get user information",
     *      description="Returns user data",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="User id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/UserResource")
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found"
     *      )
     * )
     *
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
     * @OA\Put(
     *      path="/api/users/{id}",
     *      operationId="updateUser",
     *      tags={"Users"},
     *      summary="Update existing user",
     *      description="Returns updated user data",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="User id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateUserRequest")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/UserResource")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found"
     *      )
     * )
     *
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
     * @OA\Delete(
     *      path="/api/users/{id}",
     *      operationId="deleteUser",
     *      tags={"Users"},
     *      summary="Delete existing user",
     *      description="Deletes a record and returns no content",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="User id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="Successful operation",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found"
     *      )
     * )
     *
     * Remove the specified user.
     *
     * @group User Management
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            $this->userService->deleteUser($user);

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *      path="/api/users/{id}/welcome",
     *      operationId="sendWelcomeEmail",
     *      tags={"Users"},
     *      summary="Send welcome email to user",
     *      description="Sends a welcome email to the user",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="User id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=202,
     *          description="Accepted - emails queued for sending",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Welcome emails queued successfully"),
     *              @OA\Property(property="user_id", type="integer", example=1)
     *          )
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found"
     *      )
     * )
     *
     * Send a welcome email to the specified user.
     *
     * @group User Management
     */
    public function sendWelcome(User $user): JsonResponse
    {
        \Log::info('=== WELCOME EMAIL REQUEST START ===', [
            'user_id' => $user->id,
            'user_name' => $user->first_name . ' ' . $user->last_name,
            'timestamp' => now()->toIso8601String(),
        ]);

        try {
            \Log::info('Calling UserService->sendWelcomeEmails()', ['user_id' => $user->id]);

            $this->userService->sendWelcomeEmails($user);

            \Log::info('=== WELCOME EMAIL REQUEST SUCCESS ===', [
                'user_id' => $user->id,
                'message' => 'Welcome emails queued successfully',
            ]);

            return response()->json([
                'message' => 'Welcome emails queued successfully',
                'user_id' => $user->id,
            ], Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            \Log::error('=== WELCOME EMAIL REQUEST FAILED ===', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to send welcome email',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
