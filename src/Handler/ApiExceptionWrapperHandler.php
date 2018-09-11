<?php
namespace App\Handler;

use FOS\RestBundle\View\ExceptionWrapperHandlerInterface;

class ApiExceptionWrapperHandler implements ExceptionWrapperHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function wrap($data)
    {
        if (isset($data['exception'])) {
            $exception = $data['exception'];
            $message = $exception->getMessage();
            $code = $data['status_code'];
            return array(
                'code' => $code,
                'message' => $message
            );
        } else {
        	$data1 = array();
 			foreach($data as $key=>$temp){
 				if ($key == 'status_code'){
 					$data1['code'] = $temp;
 				}else{
 					$data1[$key] = $temp;
 				}
 			}
        	return $data1;
        }
    }
}