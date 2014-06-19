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
	protected function prepare(Request $request, Response $response, &$stream = null)
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

        if ($stream) {
            curl_setopt($connection, CURLOPT_WRITEFUNCTION, function ($connection, $string) use ($stream) {
                return fwrite($stream, $string, strlen($string));
            });
        }

        $data = $request->data->get();

		foreach ($request->files->get() as $name => $file) {
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
	public function getResponse(Request $request, Response $response = null, &$stream = null)
	{
        if (!$response) {
            $response = new Response;
        }

        if ($stream && (!is_resource($stream) || (get_resource_type($stream) !== 'stream'))) {
            throw new \Exception('Not valid stream resource provided');
        }

		$connection = $this->prepare($request, $response, $stream);

        $return = curl_exec($connection);

        if (!$stream) {
            $response->setContent($return);
        }

        $info = curl_getinfo($connection);
		curl_close($connection);

        $response->setStatus($info['http_code']);

		return $response;
	}
}
