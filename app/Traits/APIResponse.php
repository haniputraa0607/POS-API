<?php

namespace App\Traits;

trait ApiResponse
{
    protected function ok($message, $data)
    {
        return response()->json([
            'status' => 200,
            'message' => $message,
            'result' => $data
        ], 200);
    }

    protected function created($message, $data)
    {
        return response()->json([
            'status' => 201,
            'message' => $message,
            'result' => $data
        ], 201);
    }

    protected function delete($message, $data)
    {
        return response()->json([
            'status' => 204,
            'message' => $message,
            'data' => $data
        ]);
    }

    protected function unauthorized($message, $error)
    {
        return response()->json([
            'status' => 401,
            'message' => $message,
            'error' => $error
        ], 401);
    }

    protected function invalidNoPermission($message, $error)
    {
        return response()->json([
            'status' => 403,
            'message' => $message,
            'error' => $error
        ], 403);
    }

    protected function NotFound($error)
    {
        return response()->json([
            'status' => 404,
            'message' => "Data Not Found",
            'error' => $error
        ], 404);
    }

    protected function InvalidValidation($message, $error)
    {
        return response()->json([
            'status' => 422,
            'message' => $message,
            'error' => $error
        ], 422);
    }

    protected function error($message)
    {
        return response()->json([
            'status' => 400,
            'message' => $message,
            'error' => ''
        ], 400);
    }
}
