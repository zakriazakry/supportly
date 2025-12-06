<?php
function responseFormat($data, int $code = 200)
{
    $status = $code >= 200 && $code < 300;
    return response()->json(
        [
            'status' => $status,
            'data' => $data,
        ],
        $code
    );
}
