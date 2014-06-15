<?php
/**
 * Fol\Http\CurlDispatcher
 *
 * Class to send http requests using curl
 */
namespace Fol\Http;

class CurlDispatcher
{
    /**
     * Prepares the curl connection before execute
     * 
     * @param Request  $request
     * @param Response $response
     * 
     * @return resource The cURL handle
     */
	protected function prepare(Request $request, Response $response)
	{
		$connection = curl_init();

		curl_setopt_array($connection, array(
            CURLOPT_URL => $request->getUrl(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_COOKIE => $request->cookies->getAsString(null, ''),
            CURLOPT_SAFE_UPLOAD => true
        ));

        curl_setopt($connection, CURLOPT_HTTPHEADER, $request->headers->getAsString());

        if ($request->getMethod() === 'POST') {
        	curl_setopt($connection, CURLOPT_POST, true);
        } else if ($request->getMethod() === 'PUT') {
        	curl_setopt($connection, CURLOPT_PUT, true);
        } else if ($request->getMethod() !== 'GET') {
        	curl_setopt($connection, CURLOPT_CUSTOMREQUEST, $request->getMethod());
        }

        curl_setopt($connection, CURLOPT_HEADERFUNCTION, function ($connection, $string) use ($response) {
        	if (strpos($string, ':')) {
                $response->headers->setFromString($string, false);

        		if (strpos($string, 'Set-Cookie') === 0) {
                    $response->cookies->setFromString($string);
                }
        	}

        	return strlen($string);
        });

        $data = $this->request->data->get();

		foreach ($request->files as $name => $file) {
			$data[$name] = new CURLFile($file, '', $name);
		}

    	if ($data) {
    		curl_setopt($connection, CURLOPT_POSTFIELDS, $data);
    	}

    	return $connection;
	}


    /**
     * Executes a request and returns a response
     * 
     * @param Request  $request
     * 
     * @return Response
     */
	public function getResponse(Request $request)
	{
		$response = new Response;
		$connection = $this->prepare($request, $response);

        $response->setContent(curl_exec($connection));

        $info = curl_getinfo($connection);
		curl_close($connection);

        $response->setStatus($info['http_code']);

		return $response;
	}
}
