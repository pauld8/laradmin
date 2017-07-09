<?php

namespace Shemi\Laradmin\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Shemi\Laradmin\Facades\Laradmin;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $status_code = 200;

    public function __construct()
    {
        $user = null;

        if(Auth::check()) {
            $user = Laradmin::model('User')->find(Auth::id());
        }

        view()->share('user', $user);
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }

    /**
     * @param int $status_code
     * @return $this
     */
    public function setStatusCode($status_code)
    {
        $this->status_code = $status_code;

        return $this;
    }


    public function responseNotFound($message = 'Not found.')
    {
        return $this->setStatusCode(404)->responseWithError($message);
    }



    public function responseNotAuthorized($message = 'not Authorized.')
    {
        return $this->setStatusCode(401)->responseWithError($message);
    }

    public function responseValidationError($messages = 'Check all your fields.')
    {
        if(is_string($messages)) {
            $messages = [
                'form' => [$messages]
            ];
        }

        return response()->json($messages, 422);
    }

    public function responseInternalError($message = 'Internal error.')
    {
        return $this->setStatusCode(500)->responseWithError($message);
    }

    public function responseBadRequest($message = 'Bad Request')
    {
        return $this->setStatusCode(400)->responseWithError($message);
    }

    public function responseWithError($message, $resultCode = 'err')
    {

        return response()->json([
            'message' => $message,
            'resultCode' => $resultCode,
            'code'    => $this->getStatusCode()
        ], $this->getStatusCode());
    }



    public function response($data, $headers = [], $resultCode = 'ok')
    {
        $data = [
            'data' => $data,
            'resultCode' => $resultCode,
            'code'    => $this->getStatusCode()
        ];


        return response($data, $this->getStatusCode(), $headers);
    }
}