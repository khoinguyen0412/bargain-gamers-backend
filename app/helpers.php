<?php

function responseData($code,$message,$resultData=NULL,$statusCode = 200){
    $response = [
        'code' =>$code
    ];
    if ($message !== NULL && $code != config('apiconst.API_OK') && is_array($message) && isset($message['error_message'])) {
        $response['message'] = $message['error_message'];
    }
    if ($message == NULL && $code == 1){
        $response['message'] = config('apiconst.INVALID_PARAMETERS_MESS');
    }
    else if ($message == NULL && $code == 4) {
        $response['message'] = config('apiconst.INTERNAL_SERVER_ERROR_MESS');
    }
    else if ($message !== NULL && $code ==2){
        $response['message'] = $message;
        $statusCode = 404;
    }
    else if($message !== NULL && $code == 4){
        $response['message'] = $message;
    }
    else if($message !== NULL && $code == 5){
        $response['message'] = $message;
        $statusCode = 401;
    }
   else if ($message !== NULL){
        $response['message'] = $message;
    }
    if ($message !== NULL && $code == 1) {
        $response['messageObject'] = (object) $message;
    }
    // If there is no error
    else if ($resultData !== NULL) {
        $response['resultData'] = (object) $resultData;
    }
    return response()->json($response,$statusCode,['Content-Type' => 'application/json; charset=utf-8']);
}