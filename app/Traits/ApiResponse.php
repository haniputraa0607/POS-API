<?php

namespace App\Traits;

trait ApiResponse
{
    protected function ok($message, $data)
    {
        return response()->json([
            'status' => "success",
            'message' => $message,
            'result' => $data
        ], 200);
    }

    protected function created($message, $data)
    {
        return response()->json([
            'status' => "success",
            'message' => $message,
            'result' => $data
        ], 201);
    }

    protected function delete($message, $data)
    {
        return response()->json([
            'status' => "success",
            'message' => $message,
            'result' => $data
        ]);
    }

    protected function unauthorized($message)
    {
        return response()->json([
            'status' => "fail",
            'message' => $message,
            'error' => "Unauthorized action",
        ], 401);
    }

    protected function invalidNoPermission($message, $error)
    {
        return response()->json([
            'status' => "fail",
            'message' => $message,
            'error' => $error
        ], 403);
    }

    protected function notFound($error)
    {
        return response()->json([
            'status' => "fail",
            'message' => "Data Not Found",
            'error' => $error
        ], 404);
    }

    protected function invalidValidation($message, $error)
    {
        return response()->json([
            'status' => "fail",
            'message' => $message,
            'error' => $error
        ], 422);
    }

    protected function error($message)
    {
        return response()->json([
            'status' => "fail",
            'message' => $message,
            'error' => ''
        ], 400);
    }
}
