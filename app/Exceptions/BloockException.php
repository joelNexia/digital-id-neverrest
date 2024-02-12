<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Exception;


// Error to manage users restrictions
class BloockException extends Exception
{
    protected array $customMessage;
    public function __construct($customMessage)
    {
        $this->customMessage=$customMessage;
    }

    public function getCustomMessage(){
        return $this->customMessage;
    }
    /** 
     * Report the exception. 
     * 
     * @return void 
     */
    public function report()
    {
    }
    /** 
     * Render the exception into an HTTP response. 
     * 
     * @param \Illuminate\Http\Request 
     * @return \Illuminate\Http\Response 
     */
    public function render()
    {
        return response()->json(
            [
                'errorCode' => 'ERROR_BLOOCK',
                'errorData' => $this->getCustomMessage(),
            ]
            ,Response::HTTP_CONFLICT
        );

    }
}
