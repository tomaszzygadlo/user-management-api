<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Email;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 *     name="User Emails",
 *     description="API endpoints for managing user emails"
 * )
 */
class UserEmailController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/users/{userId}/emails",
     *     summary="Get all emails for a user",
     *     tags={"User Emails"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of user emails",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Email")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function index(User $user): JsonResponse
    {
        $emails = $user->emails()->paginate(15);

        return response()->json($emails);
    }

    /**
     * @OA\Post(
     *     path="/api/users/{userId}/emails",
     *     summary="Add a new email to user",
     *     tags={"User Emails"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="newemail@example.com"),
     *             @OA\Property(property="is_primary", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Email added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email added successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Email")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:emails,email',
            'is_primary' => 'sometimes|boolean',
        ]);

        // If setting as primary, unset other primary emails
        if (isset($validated['is_primary']) && $validated['is_primary']) {
            $user->emails()->update(['is_primary' => false]);
        }

        $email = $user->emails()->create($validated);

        return response()->json([
            'message' => 'Email added successfully',
            'data' => $email,
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{userId}/emails/{emailId}",
     *     summary="Get a specific email",
     *     tags={"User Emails"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="emailId",
     *         in="path",
     *         description="Email ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email details",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Email")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Email does not belong to this user"),
     *     @OA\Response(response=404, description="Email not found")
     * )
     */
    public function show(User $user, Email $email): JsonResponse
    {
        if ($email->user_id !== $user->id) {
            return response()->json([
                'message' => 'Email does not belong to this user',
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'data' => $email,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{userId}/emails/{emailId}",
     *     summary="Update an email",
     *     tags={"User Emails"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="emailId",
     *         in="path",
     *         description="Email ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email", example="updated@example.com"),
     *             @OA\Property(property="is_primary", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Email")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Email does not belong to this user"),
     *     @OA\Response(response=404, description="Email not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, User $user, Email $email): JsonResponse
    {
        if ($email->user_id !== $user->id) {
            return response()->json([
                'message' => 'Email does not belong to this user',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'email' => 'sometimes|required|email|unique:emails,email,' . $email->id,
            'is_primary' => 'sometimes|boolean',
        ]);

        // If setting as primary, unset other primary emails
        if (isset($validated['is_primary']) && $validated['is_primary']) {
            $user->emails()->where('id', '!=', $email->id)->update(['is_primary' => false]);
        }

        $email->update($validated);

        return response()->json([
            'message' => 'Email updated successfully',
            'data' => $email->fresh(),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{userId}/emails/{emailId}",
     *     summary="Delete an email",
     *     tags={"User Emails"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="emailId",
     *         in="path",
     *         description="Email ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Email deleted successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Email does not belong to this user"),
     *     @OA\Response(response=404, description="Email not found")
     * )
     */
    public function destroy(User $user, Email $email): JsonResponse
    {
        if ($email->user_id !== $user->id) {
            return response()->json([
                'message' => 'Email does not belong to this user',
            ], Response::HTTP_FORBIDDEN);
        }

        $email->delete();

        return response()->json([
            'message' => 'Email deleted successfully',
        ], Response::HTTP_NO_CONTENT);
    }
}

